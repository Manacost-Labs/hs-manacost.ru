"""Exercise source-owned Reader locations with disposable loopback TLS peers."""

from contextlib import contextmanager
import http.client
import http.server
import json
from pathlib import Path
import socket
import ssl
import subprocess
import tempfile
import threading
import time
import unittest


SOURCE = Path(__file__).resolve().parents[1] / "ops/reader"


class ReaderFixture(http.server.BaseHTTPRequestHandler):
    protocol_version = "HTTP/1.1"

    def do_GET(self):
        if self.server.unavailable:
            self.close_connection = True
            return
        body = json.dumps({
            "cookie": self.headers.get("Cookie", ""),
            "host": self.headers.get("Host"),
            "authorization": self.headers.get("Authorization", ""),
            "connection": self.client_address[1],
            "peer": self.server.server_port,
        }).encode()
        self.send_response(200)
        self.send_header("Content-Length", str(len(body)))
        self.send_header("Cache-Control", "private, no-store")
        self.end_headers()
        self.wfile.write(body)

    def do_POST(self):
        self.rfile.read(int(self.headers.get("Content-Length", "0")))
        self.server.writes += 1
        # Simulate an accepted write followed by a lost response.
        self.close_connection = True

    def log_message(self, *_args):
        pass


class ReaderProxyRuntimeTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.temporary = tempfile.TemporaryDirectory(prefix="reader-proxy-tls-")
        cls.root = Path(cls.temporary.name)
        for name in ("trusted", "untrusted"):
            subprocess.run([
                "openssl", "req", "-x509", "-newkey", "rsa:2048", "-nodes",
                "-keyout", str(cls.root / (name + ".key")),
                "-out", str(cls.root / (name + ".pem")), "-days", "1",
                "-subj", "/CN=hs-manacost.ru",
                "-addext", "subjectAltName=DNS:hs-manacost.ru,DNS:test.hs-manacost.ru",
            ], check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        tls = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
        tls.load_cert_chain(cls.root / "trusted.pem", cls.root / "trusted.key")
        cls.peers = []
        for _ in range(3):
            peer = http.server.ThreadingHTTPServer(("127.0.0.1", 0), ReaderFixture)
            peer.socket = tls.wrap_socket(peer.socket, server_side=True)
            peer.unavailable = False
            peer.writes = 0
            threading.Thread(target=peer.serve_forever, daemon=True).start()
            cls.peers.append(peer)

    @classmethod
    def tearDownClass(cls):
        for peer in cls.peers:
            peer.shutdown()
            peer.server_close()
        cls.temporary.cleanup()

    @contextmanager
    def proxy(self, environment, invalid=None):
        with tempfile.TemporaryDirectory(dir=self.root) as directory:
            root = Path(directory)
            upstream = (SOURCE / f"proxy-{environment}-upstream.conf").read_text()
            for old, peer in zip((18443, 18444, 18445), self.peers):
                upstream = upstream.replace(f":{old} ", f":{peer.server_port} ")
            locations = (SOURCE / f"proxy-{environment}-reader.conf").read_text()
            for trust in ("/etc/nginx/ssl/hs-manacost-reader-origin-ca.pem",
                          "/etc/ssl/certs/ca-certificates.crt"):
                locations = locations.replace(trust, str(self.root / (
                    "untrusted.pem" if invalid == "trust" else "trusted.pem")))
            if invalid == "hostname":
                locations = locations.replace("proxy_ssl_name hs-manacost.ru;",
                                              "proxy_ssl_name invalid.example;")
                locations = locations.replace("proxy_ssl_name test.hs-manacost.ru;",
                                              "proxy_ssl_name invalid.example;")
            with socket.socket() as available:
                available.bind(("127.0.0.1", 0))
                front = available.getsockname()[1]
            config = f"""daemon off; master_process off;
                error_log {root}/error.log; pid {root}/nginx.pid;
                events {{ worker_connections 128; }}
                http {{ access_log off; client_body_temp_path {root}/body;
                    proxy_temp_path {root}/proxy;
                    {upstream}
                    upstream ordinary_unverified {{
                        server 127.0.0.1:{self.peers[0].server_port}; keepalive 8;
                    }}
                    server {{ listen 127.0.0.1:{front};
                        location /legacy {{ proxy_pass https://ordinary_unverified;
                            proxy_http_version 1.1; proxy_set_header Connection "";
                            proxy_ssl_verify off;
                        }}
                        {locations}
                    }}
                }}"""
            path = root / "nginx.conf"
            path.write_text(config)
            command = ["/usr/sbin/nginx", "-p", directory, "-c", str(path)]
            subprocess.run(command + ["-t"], check=True, capture_output=True)
            process = subprocess.Popen(command, stdout=subprocess.DEVNULL,
                                       stderr=subprocess.DEVNULL)
            try:
                for attempt in range(100):
                    try:
                        with socket.create_connection(("127.0.0.1", front), timeout=.1):
                            break
                    except OSError:
                        if process.poll() is not None or attempt == 99:
                            self.fail("disposable nginx failed to start")
                        time.sleep(.02)
                yield front
            finally:
                process.terminate()
                try:
                    process.wait(timeout=5)
                except subprocess.TimeoutExpired:
                    process.kill()
                    process.wait(timeout=5)

    def request(self, port, cookie="", path="/reader-api/v1/bootstrap", method="GET"):
        client = http.client.HTTPConnection("127.0.0.1", port, timeout=5)
        try:
            client.request(method, path, body=b"{}" if method == "POST" else None,
                           headers={"Cookie": cookie, "Authorization": "Basic fixture"})
            response = client.getresponse()
            return response.status, response.getheader("Cache-Control"), response.read()
        finally:
            client.close()

    def test_reused_connections_isolate_users_hosts_and_private_responses(self):
        for environment, host in (("production", "hs-manacost.ru"),
                                  ("staging", "test.hs-manacost.ru")):
            with self.subTest(environment=environment), self.proxy(environment) as port:
                connections = set()
                users_by_connection = {}
                peers = set()
                for index in range(24):
                    cookie = ("reader=alice", "reader=bob", "", "reader=expired")[index % 4]
                    path = "/account/" if environment == "production" and index % 2 else "/reader-api/v1/bootstrap"
                    status, cache, raw = self.request(port, cookie, path)
                    self.assertEqual(status, 200)
                    self.assertEqual(cache, "private, no-store")
                    data = json.loads(raw)
                    self.assertEqual(data["cookie"], cookie)
                    self.assertEqual(data["host"], host)
                    self.assertEqual(data["authorization"],
                                     "Basic fixture" if environment == "staging" else "")
                    connections.add((data["peer"], data["connection"]))
                    users_by_connection.setdefault((data["peer"], data["connection"]), set()).add(cookie)
                    peers.add(data["peer"])
                self.assertEqual(len(peers), 3)
                self.assertLessEqual(len(connections), 3, "TLS connections not reused")
                self.assertTrue(all(len(users) == 4 for users in users_by_connection.values()))

    def test_untrusted_or_wrong_hostname_is_rejected_even_after_legacy_traffic(self):
        for environment in ("production", "staging"):
            for invalid in ("trust", "hostname"):
                with self.subTest(environment=environment, invalid=invalid):
                    with self.proxy(environment, invalid) as port:
                        self.assertEqual(self.request(port, path="/legacy")[0], 200)
                        self.assertEqual(self.request(port)[0], 502)

    def test_failed_peer_recovers_reads_and_does_not_replay_accepted_post(self):
        with self.proxy("production") as port:
            for _ in range(3):
                self.assertEqual(self.request(port)[0], 200)
            self.peers[0].unavailable = True
            try:
                for _ in range(9):
                    self.assertEqual(self.request(port)[0], 200)
            finally:
                self.peers[0].unavailable = False
        before = sum(peer.writes for peer in self.peers)
        with self.proxy("production") as port:
            self.assertEqual(self.request(port, method="POST")[0], 502)
        self.assertEqual(sum(peer.writes for peer in self.peers) - before, 1)


if __name__ == "__main__":
    unittest.main()
