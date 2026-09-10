"""Offline contracts for the privacy-bounded 5xx attribution log."""
import json
import re
import shutil
import socket
import subprocess
import tempfile
import time
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
ATTRIBUTION = ROOT / "ops/nginx/wordfence-503-attribution-http.conf"
RESOURCE = ROOT / "ops/nginx/resources/31-wordfence-503-attribution.conf"
PLAUSIBLE_RESOURCE = ROOT / "ops/nginx/resources/plausible-first-party.conf"
NGINX = shutil.which("nginx") or ("/usr/sbin/nginx" if Path("/usr/sbin/nginx").is_file() else None)


class WordfenceAttributionNginxTests(unittest.TestCase):
    def test_http_context_configuration_is_privacy_bounded(self) -> None:
        config = ATTRIBUTION.read_text(encoding="utf-8")

        self.assertIn("map $status $hs_manacost_5xx_loggable", config)
        self.assertIn("~^5 1;", config)
        self.assertIn('map "$status:$host" $hs_manacost_5xx_attribution_enabled', config)
        self.assertIn(r"~^5\d\d:hs-manacost\.ru$ 1;", config)
        self.assertIn("map $upstream_http_retry_after $hs_manacost_retry_after_present", config)
        self.assertIn('"retry_after_present":$hs_manacost_retry_after_present', config)
        self.assertIn('"endpoint":"$hs_manacost_5xx_endpoint"', config)
        self.assertIn('"owner":"$hs_manacost_5xx_owner"', config)

        for forbidden in (
            "$remote_addr",
            "$realip_remote_addr",
            "$request",
            "$request_uri",
            "$args",
            "$http_referer",
            "$http_user_agent",
        ):
            self.assertNotIn(forbidden, config)

    def test_only_endpoint_classes_are_logged(self) -> None:
        config = ATTRIBUTION.read_text(encoding="utf-8")

        self.assertIn('"/wp-login.php" login;', config)
        self.assertIn("~^/wp-admin(?:/|$) admin;", config)
        self.assertIn("~^/wp-json(?:/|$) rest;", config)
        self.assertIn("~^/mca(?:/|$) analytics;", config)
        self.assertIn('"/views/hit" views;', config)
        self.assertIn("~^/wp-(?:content|includes)(?:/|$) media;", config)
        self.assertIn("default page;", config)

    def test_only_bounded_owner_classes_are_logged(self) -> None:
        config = ATTRIBUTION.read_text(encoding="utf-8")

        self.assertIn("map $uri $hs_manacost_5xx_owner_hint", config)
        self.assertIn("map \"$hs_manacost_5xx_owner_hint:$upstream_status\" $hs_manacost_5xx_owner", config)
        for owner in ("wordpress", "plausible", "views", "media_fallback", "nginx"):
            self.assertIn(owner, config)
        self.assertIn(r"~^/wp-content/(?:uploads|uploads-webpc)(?:/|$) media_fallback;", config)

    def test_plausible_locations_keep_their_log_and_add_sanitized_attribution(self) -> None:
        config = PLAUSIBLE_RESOURCE.read_text(encoding="utf-8")
        existing = "access_log /var/www/httpd-logs/hs-manacost.ru.plausible.access.log;"
        attribution = (
            "access_log /var/www/httpd-logs/hs-manacost.ru.5xx-attribution.log "
            "hs_manacost_5xx_attribution if=$hs_manacost_5xx_attribution_enabled;"
        )

        self.assertEqual(2, config.count(existing))
        self.assertEqual(2, config.count(attribution))
        self.assertNotIn("hs-manacost.ru.access.log", config)

    def test_shared_resource_only_attaches_to_the_canonical_host(self) -> None:
        resource = RESOURCE.read_text(encoding="utf-8")
        directive = (
            "access_log /var/www/httpd-logs/hs-manacost.ru.5xx-attribution.log "
            "hs_manacost_5xx_attribution if=$hs_manacost_5xx_attribution_enabled;"
        )

        self.assertEqual(directive, resource.strip())

    def test_staging_uses_the_same_sanitized_log_format(self) -> None:
        staging = (ROOT / "ops/nginx/staging.conf").read_text(encoding="utf-8")
        directive = (
            "access_log /var/www/httpd-logs/test.hs-manacost.ru.5xx-attribution.log "
            "hs_manacost_5xx_attribution if=$hs_manacost_5xx_loggable;"
        )

        self.assertEqual(1, staging.count(directive))

    def test_rollback_removes_only_targets_created_by_the_release(self) -> None:
        runbook = (ROOT / "docs/operations/wordfence-503-attribution.md").read_text(encoding="utf-8")

        self.assertIn("whether each target exists", runbook)
        self.assertIn("rollback must remove the file that this release created", runbook)
        self.assertIn("remove each target that was absent", runbook)
        self.assertIn("before the release", runbook)
        self.assertIn("plausible-first-party.conf", runbook)
        self.assertIn("first restores the pre-change Plausible location resource", runbook)

    @unittest.skipUnless(NGINX, "nginx is unavailable")
    def test_http_context_file_passes_nginx_syntax_check(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            directory = Path(temporary)
            config = directory / "nginx.conf"
            plausible = directory / "plausible-first-party.conf"
            plausible.write_text(
                PLAUSIBLE_RESOURCE.read_text(encoding="utf-8")
                .replace("/var/www/httpd-logs/hs-manacost.ru.plausible.access.log", "/dev/null")
                .replace("/var/www/httpd-logs/hs-manacost.ru.5xx-attribution.log", "/dev/null"),
                encoding="utf-8",
            )
            config.write_text(
                f"pid {directory / 'nginx.pid'};\n"
                "events {}\n"
                "http {\n"
                f"  include {ATTRIBUTION};\n"
                "  server {\n"
                "    listen 127.0.0.1:18882;\n"
                "    access_log /dev/null hs_manacost_5xx_attribution if=$hs_manacost_5xx_loggable;\n"
                f"    include {plausible};\n"
                "    return 204;\n"
                "  }\n"
                "}\n",
                encoding="utf-8",
            )
            result = subprocess.run(
                [NGINX, "-t", "-p", str(directory), "-c", str(config)],
                capture_output=True,
                text=True,
                timeout=10,
            )

        self.assertEqual(0, result.returncode, result.stderr)

    @unittest.skipUnless(NGINX, "nginx is unavailable")
    def test_5xx_only_log_emits_the_bounded_attribution_shape(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            directory = Path(temporary)
            socket_path = directory / "nginx.sock"
            log_path = directory / "attribution.log"
            config = directory / "nginx.conf"
            config.write_text(
                f"pid {directory / 'nginx.pid'};\n"
                f"error_log {directory / 'error.log'} notice;\n"
                "events {}\n"
                "http {\n"
                f"  include {ATTRIBUTION};\n"
                "  server {\n"
                f"    listen unix:{socket_path};\n"
                "    server_name hs-manacost.ru;\n"
                f"    access_log {log_path} hs_manacost_5xx_attribution if=$hs_manacost_5xx_attribution_enabled;\n"
                "    location = /healthy { return 200; }\n"
                "    location = /blocked { return 503; }\n"
                "  }\n"
                "}\n",
                encoding="utf-8",
            )
            process = subprocess.Popen(
                [NGINX, "-g", "daemon off;", "-p", str(directory), "-c", str(config)],
                stdout=subprocess.DEVNULL,
                stderr=subprocess.DEVNULL,
            )
            try:
                deadline = time.monotonic() + 2
                while not socket_path.exists() and time.monotonic() < deadline:
                    if process.poll() is not None:
                        self.fail("candidate nginx exited before opening its socket")
                    time.sleep(0.01)
                self.assertTrue(socket_path.exists(), "candidate nginx did not open its socket")

                def request(path: str) -> bytes:
                    with socket.socket(socket.AF_UNIX, socket.SOCK_STREAM) as client:
                        client.connect(str(socket_path))
                        client.sendall(
                            f"GET {path} HTTP/1.1\r\nHost: hs-manacost.ru\r\nConnection: close\r\n\r\n".encode()
                        )
                        chunks = []
                        while chunk := client.recv(4096):
                            chunks.append(chunk)
                        return b"".join(chunks)

                self.assertIn(b" 200 ", request("/healthy"))
                if log_path.exists():
                    self.assertEqual("", log_path.read_text(encoding="utf-8"))

                self.assertIn(b" 503 ", request("/blocked"))
                deadline = time.monotonic() + 1
                lines = []
                while time.monotonic() < deadline:
                    lines = log_path.read_text(encoding="utf-8").splitlines() if log_path.exists() else []
                    if lines:
                        break
                    time.sleep(0.01)
            finally:
                process.terminate()
                process.wait(timeout=5)

        self.assertEqual(1, len(lines))
        entry = json.loads(lines[0])
        self.assertEqual(
            {"time", "status", "endpoint", "owner", "upstream_status", "retry_after_present"},
            set(entry),
        )
        self.assertRegex(entry["time"], r"^\d{4}-\d{2}-\d{2}T")
        self.assertEqual(
            {"status": 503, "endpoint": "page", "owner": "nginx", "upstream_status": "",
             "retry_after_present": 0},
            {key: entry[key] for key in entry if key != "time"},
        )


if __name__ == "__main__":
    unittest.main()
