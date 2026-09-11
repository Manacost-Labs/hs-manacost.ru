#!/usr/bin/env python3
"""Real loopback TLS regression; disposable CA, no external or live certificates."""
import http.client
import http.server
import re
import socket
import subprocess
import tempfile
import threading
import time
from pathlib import Path

SOURCE = Path(__file__).resolve().parent
BUCKET = "hs-manacost-media-3az.s3.eu-west-par.io.cloud.ovh.net"


def port():
    with socket.socket() as candidate:
        candidate.bind(("127.0.0.1", 0))
        return candidate.getsockname()[1]


class RecoveringObjectStorage(http.server.BaseHTTPRequestHandler):
    """Drop the first request, then behave like a recovered object store."""

    def do_GET(self):
        self.server.request_count += 1
        if self.server.request_count == 1:
            self.close_connection = True
            self.connection.shutdown(socket.SHUT_RDWR)
            self.connection.close()
            return
        body = b"recovered object storage"
        self.send_response(200)
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def log_message(self, *_args):
        pass


def verify_transient_failures_do_not_disable_every_peer():
    """Exercise passive peer-down behavior using two recovering loopback peers."""
    nginx = "/usr/sbin/nginx"
    servers = []
    threads = []
    try:
        for _ in range(2):
            server = http.server.ThreadingHTTPServer(("127.0.0.1", 0), RecoveringObjectStorage)
            server.request_count = 0
            thread = threading.Thread(target=server.serve_forever, daemon=True)
            thread.start()
            servers.append(server)
            threads.append(thread)

        with tempfile.TemporaryDirectory(prefix="hs-media-upstream-recovery-") as temporary:
            root = Path(temporary)
            source = (SOURCE / "http.conf").read_text()
            server_pattern = rf"^\s*server\s+{re.escape(BUCKET)}:443(?P<parameters>[^;]*);"
            match = re.search(server_pattern, source, flags=re.MULTILINE)
            assert match, "verified media upstream server directive is missing"
            parameters = match.group("parameters")
            replacements = "\n".join(
                f"    server 127.0.0.1:{server.server_port}{parameters};" for server in servers
            )
            loopback_http = re.sub(server_pattern, replacements, source, count=1, flags=re.MULTILINE)
            front = port()
            config = f"""daemon off; master_process off;
                error_log {root}/error.log notice; pid {root}/nginx.pid;
                events {{ worker_connections 64; }}
                http {{ access_log off; proxy_temp_path {root}/proxy; client_body_temp_path {root}/body;
                    {loopback_http}
                    server {{ listen 127.0.0.1:{front};
                        location / {{
                            proxy_pass http://hs_media_verified_s3;
                            proxy_next_upstream off;
                            proxy_connect_timeout 1s;
                            proxy_read_timeout 1s;
                        }}
                    }}
                }}"""
            (root / "nginx.conf").write_text(config)
            subprocess.run([nginx, "-t", "-p", temporary, "-c", str(root / "nginx.conf")],
                           check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
            process = subprocess.Popen([nginx, "-p", temporary, "-c", str(root / "nginx.conf")])
            try:
                for attempt in range(50):
                    try:
                        with socket.create_connection(("127.0.0.1", front), timeout=0.1):
                            break
                    except OSError:
                        if process.poll() is not None or attempt == 49:
                            raise RuntimeError("upstream recovery fixture did not start")
                        time.sleep(0.02)

                statuses = []
                for _ in range(3):
                    client = http.client.HTTPConnection("127.0.0.1", front, timeout=3)
                    try:
                        client.request("GET", "/object.jpg")
                        response = client.getresponse()
                        response.read()
                        statuses.append(response.status)
                    finally:
                        client.close()
                assert statuses == [502, 502, 200], statuses
                assert "no live upstreams" not in (root / "error.log").read_text()
            finally:
                process.terminate()
                try:
                    process.wait(timeout=5)
                except subprocess.TimeoutExpired:
                    process.kill()
                    process.wait(timeout=5)
    finally:
        for server in servers:
            server.shutdown()
            server.server_close()
        for thread in threads:
            thread.join(timeout=5)


def main():
    verify_transient_failures_do_not_disable_every_peer()
    with tempfile.TemporaryDirectory(prefix="hs-media-tls-") as temporary:
        root = Path(temporary)

        def command(*args):
            subprocess.run(args, cwd=root, check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

        command("openssl", "req", "-x509", "-newkey", "rsa:2048", "-nodes", "-keyout", "ca.key",
                "-out", "ca.pem", "-days", "1", "-subj", "/CN=Disposable media test CA")
        command("openssl", "req", "-newkey", "rsa:2048", "-nodes", "-keyout", "server.key",
                "-out", "server.csr", "-subj", "/CN=localhost")
        (root / "extensions").write_text("subjectAltName=DNS:localhost\nbasicConstraints=CA:FALSE\n"
                                         "keyUsage=digitalSignature,keyEncipherment\nextendedKeyUsage=serverAuth\n")
        command("openssl", "x509", "-req", "-in", "server.csr", "-CA", "ca.pem", "-CAkey", "ca.key",
                "-CAcreateserial", "-out", "server.pem", "-days", "1", "-extfile", "extensions")
        (root / "chain.pem").write_text((root / "server.pem").read_text() + (root / "ca.pem").read_text())
        front, back = port(), port()
        maps = (SOURCE / "http.conf").read_text().replace(BUCKET + ":443", f"127.0.0.1:{back}")
        # Exercise source-owned verified proxy; substitute only test trust/host.
        verified_proxy = (SOURCE / "proxy.conf").read_text().replace(BUCKET, "localhost")
        verified_proxy = verified_proxy.replace("/etc/ssl/certs/ca-certificates.crt", str(root / "ca.pem"))
        verified_proxy = verified_proxy.replace("include /etc/nginx/hs-media-negotiation/headers.conf;",
                                                (SOURCE / "headers.conf").read_text())
        config = f"""daemon off; master_process off;
            error_log {root}/error.log; pid {root}/nginx.pid;
            events {{ worker_connections 64; }}
            http {{ access_log off; proxy_temp_path {root}/proxy; client_body_temp_path {root}/body;
                {maps}
                server {{ listen 127.0.0.1:{front};
                    proxy_ssl_server_name on; proxy_ssl_name localhost;
                    proxy_ssl_protocols TLSv1.2; proxy_ssl_session_reuse on;
                    location /legacy {{ proxy_pass https://127.0.0.1:{back}; proxy_ssl_verify off; }}
                    location /shared {{ proxy_pass https://127.0.0.1:{back};
                        proxy_ssl_verify on; proxy_ssl_trusted_certificate {root}/ca.pem; }}
                    location /isolated {{ {verified_proxy} }}
                }}
                server {{ listen 127.0.0.1:{back} ssl;
                    ssl_certificate {root}/chain.pem; ssl_certificate_key {root}/server.key;
                    ssl_protocols TLSv1.2; ssl_session_cache shared:fixture:1m;
                    location / {{ return 200 'disposable TLS fixture'; }}
                }}
            }}"""
        (root / "nginx.conf").write_text(config)
        command("/usr/sbin/nginx", "-t", "-p", temporary, "-c", str(root / "nginx.conf"))
        process = subprocess.Popen(["/usr/sbin/nginx", "-p", temporary, "-c", str(root / "nginx.conf")])
        try:
            for attempt in range(50):
                try:
                    with socket.create_connection(("127.0.0.1", front), timeout=0.1):
                        break
                except OSError:
                    if process.poll() is not None or attempt == 49:
                        raise RuntimeError("TLS fixture did not start")
                    time.sleep(0.02)
            for path, expected in (("/legacy", 200), ("/shared", 502), ("/isolated", 200), ("/isolated", 200)):
                client = http.client.HTTPConnection("127.0.0.1", front, timeout=3)
                try:
                    client.request("GET", path)
                    response = client.getresponse()
                    response.read()
                    assert response.status == expected, (path, response.status)
                finally:
                    client.close()
            assert "certificate verify error: (19:" in (root / "error.log").read_text()
            print("TLS and transient upstream recovery regressions PASS")
        finally:
            process.terminate()
            try:
                process.wait(timeout=5)
            except subprocess.TimeoutExpired:
                process.kill()
                process.wait(timeout=5)


if __name__ == "__main__":
    main()
