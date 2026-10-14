"""Exercise the actual font locations without changing font bytes or access rules."""
import base64
from http.client import HTTPConnection
from pathlib import Path
import re
import shutil
import socket
import subprocess
import tempfile
import time
import unittest


ROOT = Path(__file__).resolve().parents[1]
NGINX = shutil.which("nginx") or ("/usr/sbin/nginx" if Path("/usr/sbin/nginx").is_file() else None)
FONT = b"synthetic-font-fixture\x00\x01\x02"


def free_port():
    with socket.socket() as sock:
        sock.bind(("127.0.0.1", 0))
        return sock.getsockname()[1]


def staging_font_location():
    source = (ROOT / "ops/nginx/staging.conf").read_text()
    start = next(m.start() for m in re.finditer(r"    location [^\n]+", source)
                 if "woff" in m.group())
    end = source.index("\n    }", start) + len("\n    }")
    return source[start:end]


@unittest.skipUnless(NGINX and shutil.which("openssl"), "nginx and openssl required")
class PublicFontHeadersTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.temp = tempfile.TemporaryDirectory(prefix="hs-font-")
        cls.directory = Path(cls.temp.name)
        cls.directory.chmod(0o755)
        public = cls.directory / "public"
        public.mkdir(mode=0o755)
        for extension in ("woff", "woff2", "ttf", "otf", "eot", "css", "js", "png"):
            (public / f"fixture.{extension}").write_bytes(FONT)
        digest = subprocess.check_output(["openssl", "passwd", "-apr1", "synthetic-pass"], text=True).strip()
        (cls.directory / "auth").write_text("fixture:" + digest + "\n")
        cls.public_port, cls.stage_port = free_port(), free_port()
        config = cls.directory / "nginx.conf"
        config.write_text(f"""
daemon off;
master_process off;
pid {cls.directory}/nginx.pid;
error_log {cls.directory}/error.log;
events {{}}
http {{
    access_log off;
    client_body_temp_path {cls.directory}/body;
    proxy_temp_path {cls.directory}/proxy;
    fastcgi_temp_path {cls.directory}/fastcgi;
    uwsgi_temp_path {cls.directory}/uwsgi;
    scgi_temp_path {cls.directory}/scgi;
    server {{
        listen 127.0.0.1:{cls.public_port};
        root {public};
        include {ROOT}/ops/nginx/resources/static-cache.conf;
    }}
    server {{
        listen 127.0.0.1:{cls.stage_port};
        root {public};
        auth_basic "Synthetic staging";
        auth_basic_user_file {cls.directory}/auth;
        {staging_font_location()}
    }}
}}
""")
        cls.process = subprocess.Popen([NGINX, "-p", str(cls.directory), "-c", str(config)],
                                       stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        for _ in range(100):
            if cls.process.poll() is not None:
                cls.temp.cleanup()
                raise RuntimeError("Isolated font-header nginx failed to start")
            try:
                with socket.create_connection(("127.0.0.1", cls.public_port), timeout=0.1):
                    return
            except OSError:
                time.sleep(0.02)
        cls.process.terminate()
        cls.process.wait(timeout=5)
        cls.temp.cleanup()
        raise RuntimeError("Isolated font-header nginx did not become ready")

    @classmethod
    def tearDownClass(cls):
        cls.process.terminate()
        cls.process.wait(timeout=5)
        cls.temp.cleanup()

    def request(self, extension, *, stage=False, authenticated=False, method="GET"):
        connection = HTTPConnection("127.0.0.1", self.stage_port if stage else self.public_port, timeout=3)
        headers = {"Origin": "https://hs-manacost.com"}
        if authenticated:
            headers["Authorization"] = "Basic " + base64.b64encode(b"fixture:synthetic-pass").decode()
        try:
            connection.request(method, "/fixture." + extension, headers=headers)
            response = connection.getresponse()
            return response.status, dict((k.lower(), v) for k, v in response.getheaders()), response.read()
        finally:
            connection.close()

    def test_public_font_get_and_head_preserve_bytes_and_cache_policy(self):
        for extension in ("woff", "woff2", "ttf", "otf", "eot"):
            with self.subTest(extension=extension):
                status, headers, body = self.request(extension)
                self.assertEqual(status, 200)
                self.assertEqual(body, FONT)
                self.assertEqual(headers.get("access-control-allow-origin"), "*")
                self.assertNotIn("access-control-allow-credentials", headers)
                self.assertIn("immutable", headers["cache-control"])
                self.assertEqual(headers.get("x-content-type-options"), "nosniff")
                status, head, body = self.request(extension, method="HEAD")
                self.assertEqual(status, 200)
                self.assertEqual(body, b"")
                self.assertEqual(head.get("access-control-allow-origin"), "*")
                self.assertEqual(head["etag"], headers["etag"])

    def test_other_static_resources_do_not_receive_font_cors(self):
        for extension in ("css", "js", "png"):
            status, headers, body = self.request(extension)
            self.assertEqual(status, 200)
            self.assertEqual(body, FONT)
            self.assertNotIn("access-control-allow-origin", headers)

    def test_staging_fonts_remain_authenticated_private_and_noindex(self):
        status, _, _ = self.request("woff2", stage=True)
        self.assertEqual(status, 401)
        status, headers, body = self.request("woff2", stage=True, authenticated=True)
        self.assertEqual(status, 200)
        self.assertEqual(body, FONT)
        self.assertEqual(headers.get("access-control-allow-origin"), "*")
        self.assertIn("private", headers["cache-control"])
        self.assertIn("noindex", headers["x-robots-tag"])
        self.assertNotIn("access-control-allow-credentials", headers)
