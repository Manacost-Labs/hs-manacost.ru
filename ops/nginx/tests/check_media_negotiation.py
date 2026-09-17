#!/usr/bin/env python3
"""Exercise the real media delivery configuration with an isolated fake S3.

Only loopback sockets and a private temporary directory are used. No live
configuration, WordPress, credentials, media objects or services are mutated.
"""
import base64
import hashlib
import http.client
import http.server
import shutil
import socket
import subprocess
import tempfile
import threading
import time
from pathlib import Path
from urllib.parse import unquote, urlsplit

ROOT = Path(__file__).resolve().parents[3]
SOURCE = ROOT / "ops/nginx/media-negotiation"
BUCKET = "https://hs-manacost-media-3az.s3.eu-west-par.io.cloud.ovh.net"
PREFIX = "/wp-content/uploads/2026/09/hs-media-7716-dfxxbv2-production-jpg/"
CURRENT_UPLOAD_PREFIX = "/wp-content/uploads/2026/09/real-article/"
FUTURE_UPLOAD_PREFIX = "/wp-content/uploads/2027/01/real-article/"
HISTORICAL_UPLOAD_PREFIX = "/wp-content/uploads/2025/12/archive/"


class ObjectStorage(http.server.BaseHTTPRequestHandler):
    objects = {}
    requests = []

    def do_HEAD(self):
        self.do_GET()

    def do_GET(self):
        path = unquote(urlsplit(self.path).path)
        self.requests.append((path, dict(self.headers)))
        status, mime, body = self.objects.get(path, (403, "application/xml", b"absent"))
        total = len(body)
        if status == 200 and self.headers.get("Range") == "bytes=0-1":
            status, body = 206, body[:2]
        self.send_response(status)
        self.send_header("Content-Type", mime)
        self.send_header("Content-Length", str(len(body)))
        self.send_header("Cache-Control", "public, max-age=31536000, immutable")
        self.send_header("x-amz-request-id", "private-upstream-marker")
        if status == 206:
            self.send_header("Content-Range", f"bytes 0-1/{total}")
        self.end_headers()
        if self.command != "HEAD":
            self.wfile.write(body)

    def log_message(self, *_args):
        pass


def main():
    nginx = shutil.which("nginx") or "/usr/sbin/nginx"
    # Verified TLS must not share the legacy implicit upstream peer/session
    # cache with proxy locations that do not load trusted certificates.
    assert "upstream hs_media_verified_s3" in (SOURCE / "http.conf").read_text()
    assert "proxy_pass https://hs_media_verified_s3;" in (SOURCE / "proxy.conf").read_text()
    with tempfile.TemporaryDirectory(prefix="hs-media-negotiation-") as temporary:
        work = Path(temporary)
        upstream = http.server.ThreadingHTTPServer(("127.0.0.1", 0), ObjectStorage)
        thread = threading.Thread(target=upstream.serve_forever, daemon=True)
        thread.start()
        ports = []
        for _ in range(3):
            with socket.socket() as candidate:
                candidate.bind(("127.0.0.1", 0))
                ports.append(candidate.getsockname()[1])
        port = ports[0]
        # Transport/path substitutions only; routing is the exact versioned code.
        for source in SOURCE.glob("*.conf"):
            content = source.read_text().replace(str(SOURCE), str(work))
            content = content.replace("/etc/nginx/hs-media-negotiation", str(work))
            content = content.replace(BUCKET, f"http://127.0.0.1:{upstream.server_port}")
            content = content.replace("https://hs_media_verified_s3", "http://hs_media_verified_s3")
            content = content.replace(BUCKET.removeprefix("https://") + ":443", f"127.0.0.1:{upstream.server_port}")
            (work / source.name).write_text(content)
        if (work / "server.conf").exists():
            route = f"include {work}/server.conf;"
            for filename in ("00-object-storage-fallback.conf", "01-image-variants.conf"):
                route += (ROOT / "ops/nginx/resources" / filename).read_text().replace(
                    BUCKET, f"http://127.0.0.1:{upstream.server_port}"
                )
            maps = f"include {work}/http.conf;"
        else:
            # RED: current production route only returns the original format.
            route = (ROOT / "ops/nginx/resources/01-image-variants.conf").read_text()
            route += "location @hs_manacost_media_object_storage { return 404; }"
            maps = ""
        config = work / "nginx.conf"
        # Disposable fixture credential, never a production account/session.
        fixture_secret = "isolated-nginx-test"
        fixture_hash = base64.b64encode(hashlib.sha1(fixture_secret.encode()).digest()).decode()
        (work / "htpasswd").write_text(f"fixture:{{SHA}}{fixture_hash}\n")
        authenticated = "Basic " + base64.b64encode(f"fixture:{fixture_secret}".encode()).decode()
        edge_config = ""
        for index, edge_port in enumerate(ports[1:]):
            edge_config += f"""
                proxy_cache_path {work}/edge-{index} keys_zone=edge{index}:1m;
                server {{ listen 127.0.0.1:{edge_port};
                    location / {{
                        proxy_pass http://127.0.0.1:{port};
                        proxy_set_header Host $host;
                        proxy_cache edge{index}; proxy_cache_valid 200 1m;
                        add_header X-Test-Cache $upstream_cache_status always;
                    }}
                }}"""
        config.write_text(f"""daemon off; master_process off;
            error_log {work}/error.log; pid {work}/nginx.pid;
            events {{ worker_connections 64; }}
            http {{
                include /etc/nginx/mime.types;
                access_log off; client_body_temp_path {work}/body;
                proxy_temp_path {work}/proxy;
                {maps}
                {edge_config}
                server {{
                    listen 127.0.0.1:{port}; server_name hs-manacost.ru;
                    root {work}/public; {route}
                    location / {{ return 404; }}
                }}
                server {{
                    listen 127.0.0.1:{port}; server_name test.hs-manacost.ru;
                    root {work}/public;
                    auth_basic "Isolated staging fixture";
                    auth_basic_user_file {work}/htpasswd;
                    client_max_body_size 1k;
                    {route}
                    location /upload {{ return 204; }}
                }}
            }}""")
        image = work / "public" / (PREFIX + "all.jpg").lstrip("/")
        image.parent.mkdir(parents=True)
        image.write_bytes(b"original")
        image.with_suffix(".jpg.webp").write_bytes(b"webp")
        image.with_suffix(".jpg.avif").write_bytes(b"avif")
        subprocess.run([nginx, "-t", "-c", str(config), "-p", temporary], check=True)
        process = subprocess.Popen([nginx, "-c", str(config), "-p", temporary])
        try:
            for attempt in range(50):
                try:
                    with socket.create_connection(("127.0.0.1", port), timeout=0.1):
                        break
                except OSError:
                    if process.poll() is not None or attempt == 49:
                        raise RuntimeError("isolated nginx failed to start")
                    time.sleep(0.05)

            def request(name, accept=None, method="GET", extra=None, target=port):
                return request_from_prefix(PREFIX, name, accept, method, extra, target)

            def request_from_prefix(prefix, name, accept=None, method="GET", extra=None, target=port):
                client = http.client.HTTPConnection("127.0.0.1", target, timeout=5)
                headers = {"Host": "hs-manacost.ru", **(extra or {})}
                if accept is not None:
                    headers["Accept"] = accept
                try:
                    client.request(method, prefix + name, headers=headers)
                    response = client.getresponse()
                    return response.status, dict(response.getheaders()), response.read()
                finally:
                    client.close()

            status, headers, body = request("all.jpg", "image/avif,image/webp")
            assert (status, body) == (200, b"avif"), (status, headers, body)
            assert headers["Content-Type"] == "image/avif", headers
            assert request("all.jpg.webp")[2] == b"webp"
            assert request("all.jpg.avif")[2] == b"avif"

            # New real article uploads must receive the same safe negotiation
            # as the synthetic canary; historical uploads remain out of scope.
            current_image = work / "public" / (CURRENT_UPLOAD_PREFIX + "fresh.jpg").lstrip("/")
            current_image.parent.mkdir(parents=True)
            current_image.write_bytes(b"current-original")
            current_image.with_suffix(".jpg.webp").write_bytes(b"current-webp")
            status, headers, body = request_from_prefix(
                CURRENT_UPLOAD_PREFIX,
                "fresh.jpg",
                "image/webp",
            )
            assert (status, body) == (200, b"current-webp"), (status, headers, body)
            assert headers["Content-Type"] == "image/webp", headers
            assert request_from_prefix(CURRENT_UPLOAD_PREFIX, "fresh.jpg")[2] == b"current-original"

            future_image = work / "public" / (FUTURE_UPLOAD_PREFIX + "fresh.jpg").lstrip("/")
            future_image.parent.mkdir(parents=True)
            future_image.write_bytes(b"future-original")
            future_image.with_suffix(".jpg.webp").write_bytes(b"future-webp")
            assert request_from_prefix(FUTURE_UPLOAD_PREFIX, "fresh.jpg", "image/webp")[2] == b"future-webp"

            historical_image = work / "public" / (HISTORICAL_UPLOAD_PREFIX + "old.jpg").lstrip("/")
            historical_image.parent.mkdir(parents=True)
            historical_image.write_bytes(b"historical-original")
            historical_image.with_suffix(".jpg.webp").write_bytes(b"historical-webp")
            status, headers, body = request_from_prefix(
                HISTORICAL_UPLOAD_PREFIX,
                "old.jpg",
                "image/webp",
            )
            assert (status, body) == (200, b"historical-original"), (status, headers, body)
            assert headers["Content-Type"] == "image/jpeg", headers

            # Offload removes every local copy. New uploads must still negotiate
            # sidecars in S3 and always retain a remotely served original.
            for extension, mime, payload in (
                ("", "image/jpeg", b"current-remote-original"),
                (".webp", "image/webp", b"current-remote-webp"),
                (".avif", "image/avif", b"current-remote-avif"),
            ):
                ObjectStorage.objects[CURRENT_UPLOAD_PREFIX + "offloaded.jpg" + extension] = (200, mime, payload)
            for accept, expected in (("image/avif,image/webp", b"current-remote-avif"),
                                     ("image/webp", b"current-remote-webp"),
                                     (None, b"current-remote-original")):
                status, headers, body = request_from_prefix(CURRENT_UPLOAD_PREFIX, "offloaded.jpg", accept)
                assert (status, body) == (200, expected), (status, headers, body)
            for failure in (403, 404, 429, 503):
                ObjectStorage.objects[CURRENT_UPLOAD_PREFIX + "offloaded.jpg.avif"] = (failure, "text/plain", b"error")
                assert request_from_prefix(CURRENT_UPLOAD_PREFIX, "offloaded.jpg", "image/avif,image/webp")[2] == b"current-remote-webp", failure
                ObjectStorage.objects[CURRENT_UPLOAD_PREFIX + "offloaded.jpg.webp"] = (failure, "text/plain", b"error")
                assert request_from_prefix(CURRENT_UPLOAD_PREFIX, "offloaded.jpg", "image/avif,image/webp")[2] == b"current-remote-original", failure
                ObjectStorage.objects[CURRENT_UPLOAD_PREFIX + "offloaded.jpg.webp"] = (200, "image/webp", b"current-remote-webp")
            ObjectStorage.objects[CURRENT_UPLOAD_PREFIX + "offloaded.jpg.avif"] = (200, "image/avif", b"current-remote-avif")

            for accept, expected in (
                (None, b"original"), ("*/*", b"original"), ("image/*", b"original"),
                ("image/webp", b"webp"), ("image/avif;q=0,image/webp", b"webp"),
                ("image/avif;q=0.000,image/webp;q=0", b"original"),
                ("image/avif;q=0.001,image/webp", b"avif"),
                ("image/avif;q=2,image/webp;q=no", b"original"),
                ("image/avif;q=0,image/avif;q=1", b"original"),
                ("text/image/avif,image/webp-extra", b"original"),
                ("IMAGE/WEBP;Q=1.000", b"webp"),
                ("image/avif;unknown=value", b"original"),
            ):
                assert request("all.jpg", accept)[2] == expected, accept
                assert request("all.jpg", accept, "HEAD")[0] == 200, accept

            # No local copies: same URL must select remote sidecars or original.
            for extension, mime, payload in (
                ("", "image/jpeg", b"remote-original"),
                (".webp", "image/webp", b"remote-webp"),
                (".avif", "image/avif", b"remote-avif"),
            ):
                ObjectStorage.objects[PREFIX + "remote.jpg" + extension] = (200, mime, payload)
            for accept, expected in (("image/avif,image/webp", b"remote-avif"),
                                     ("image/webp", b"remote-webp"), (None, b"remote-original")):
                status, headers, body = request("remote.jpg", accept, extra={
                    "Cookie": "synthetic-private-cookie", "Authorization": "Bearer synthetic-fixture"
                })
                assert (status, body) == (200, expected), (status, headers, body)
                assert headers["Vary"] == "Accept" and "max-age=604800" in headers["Cache-Control"]
                assert "stale-while-revalidate=2592000" in headers["Cache-Control"]
                assert "x-amz-request-id" not in headers
                assert request("remote.jpg", accept, "HEAD")[2] == b""
            assert all("Cookie" not in headers and "Authorization" not in headers
                       for _, headers in ObjectStorage.requests)
            assert request("remote.jpg", "image/webp", extra={"Range": "bytes=0-1"})[0] == 200
            # An S3 implementation may ignore If-Range. Do not splice bytes from
            # a new representation into an old resumable download.
            status, _, body = request("remote.jpg", "image/webp", extra={
                "Range": "bytes=0-1", "If-Range": '"old-original-validator"'
            })
            assert (status, body) == (200, b"remote-webp"), (status, body)
            assert "Range" not in ObjectStorage.requests[-1][1]

            # OVH returns 403 for some absent keys. Every unavailable sidecar
            # falls through; canonical errors keep their actual error status.
            for failure in (403, 404, 429, 500, 502, 503, 504):
                ObjectStorage.objects[PREFIX + "remote.jpg.avif"] = (failure, "text/plain", b"error")
                assert request("remote.jpg", "image/avif,image/webp")[2] == b"remote-webp", failure
                ObjectStorage.objects[PREFIX + "remote.jpg.webp"] = (failure, "text/plain", b"error")
                assert request("remote.jpg", "image/avif,image/webp")[2] == b"remote-original", failure
                ObjectStorage.objects[PREFIX + "remote.jpg.webp"] = (200, "image/webp", b"remote-webp")
            status, headers, _ = request("missing.jpg", "image/avif,image/webp")
            assert status == 403 and headers["Cache-Control"] == "no-store", (status, headers)
            ObjectStorage.objects[PREFIX + "missing.jpg"] = (503, "text/plain", b"unavailable")
            assert request("missing.jpg")[0] == 503

            ObjectStorage.objects[PREFIX + "space # имя.jpg"] = (200, "image/jpeg", b"escaped-original")
            assert request("space%20%23%20%D0%B8%D0%BC%D1%8F.jpg?version=a%26b")[2] == b"escaped-original"
            assert ObjectStorage.requests[-1][0] == PREFIX + "space # имя.jpg"
            status, headers, body = request("all.jpg", "image/avif", extra={"Range": "bytes=0-1"})
            assert (status, body) == (200, b"avif") and "Content-Range" not in headers
            original_date = request("all.jpg")[1]["Last-Modified"]
            assert request("all.jpg", "image/avif", extra={
                "Range": "bytes=0-1", "If-Range": original_date
            })[2] == b"avif"

            # BasicAuth must survive every early/fallback branch. Credentials
            # must never reach S3 even after a successful authenticated request.
            for accept in (None, "image/webp", "image/avif,image/webp"):
                for name in ("all.jpg", "remote.jpg", "missing.jpg"):
                    before = len(ObjectStorage.requests)
                    assert request(name, accept, extra={"Host": "test.hs-manacost.ru"})[0] == 401
                    assert len(ObjectStorage.requests) == before
            status, headers, body = request("remote.jpg", "image/webp", extra={
                "Host": "test.hs-manacost.ru", "Authorization": authenticated
            })
            assert (status, body) == (200, b"remote-webp"), (status, body)
            assert headers["X-Robots-Tag"] == "noindex, nofollow, noarchive"
            assert headers["Cache-Control"] == "private, no-store"
            assert "Authorization" not in ObjectStorage.requests[-1][1]
            assert request("all.jpg", "image/webp", method="POST")[0] == 403
            client = http.client.HTTPConnection("127.0.0.1", port, timeout=5)
            try:
                client.request("POST", PREFIX + "all.jpg", body=b"x" * 2048,
                               headers={"Host": "test.hs-manacost.ru", "Authorization": authenticated})
                response = client.getresponse()
                response.read()
                assert response.status == 413
            finally:
                client.close()

            # Two independent local cache proxies: order/reload must not poison
            # representation choice. This does not prove live regional config.
            for edge_port in ports[1:]:
                for accept, expected in (("image/avif,image/webp", b"avif"),
                                         (None, b"original"), ("image/webp", b"webp")):
                    for pass_number in range(2):
                        status, headers, body = request("all.jpg", accept, target=edge_port)
                        assert (status, body) == (200, expected), (headers, body)
                        assert headers["X-Test-Cache"] == ("MISS" if pass_number == 0 else "HIT"), headers
            print("Media negotiation: local/S3, errors, Accept, HEAD/Range, escaping, auth and two cache proxies PASS")
        finally:
            process.terminate()
            try:
                process.wait(timeout=5)
            except subprocess.TimeoutExpired:
                process.kill()
                process.wait(timeout=5)
            upstream.shutdown()
            upstream.server_close()
            thread.join(timeout=5)


if __name__ == "__main__":
    main()
