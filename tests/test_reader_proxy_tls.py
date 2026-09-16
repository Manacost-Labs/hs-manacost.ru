"""Keep reader TLS connections separate from the ordinary unverified pool."""

from pathlib import Path
import re
import unittest


ROOT = Path(__file__).resolve().parents[1]


def directives(filename):
    return "\n".join(
        line.split("#", 1)[0].strip()
        for line in (ROOT / "ops/reader" / filename).read_text().splitlines()
    )


class ReaderProxyTlsTests(unittest.TestCase):
    def test_reader_has_a_dedicated_bounded_persistent_upstream(self):
        upstream = directives("proxy-staging-upstream.conf")
        self.assertIn("upstream hs_manacost_reader_origin {", upstream)
        self.assertIn("zone hs_manacost_reader_origin 64k;", upstream)
        self.assertIn("keepalive 8;", upstream)
        self.assertIn("keepalive_timeout 15s;", upstream)
        self.assertIn("keepalive_requests 100;", upstream)
        production = directives("proxy-production-upstream.conf")
        for setting in ("keepalive 8;", "keepalive_timeout 15s;", "keepalive_requests 100;"):
            self.assertIn(setting, production)
        self.assertEqual(
            re.findall(r"server ([^;]+);", upstream),
            [f"127.0.0.1:{port} max_fails=2 fail_timeout=2s" for port in (18443, 18444, 18445)],
        )

    def test_reader_never_borrows_unverified_connections_or_tls_sessions(self):
        proxy = directives("proxy-staging-reader.conf")
        self.assertIn("proxy_pass https://hs_manacost_reader_origin;", proxy)
        self.assertNotIn("https://hs_manacost_origin;", proxy)
        self.assertIn('proxy_set_header Connection "";', proxy)
        self.assertIn("proxy_http_version 1.1;", proxy)
        self.assertNotIn("non_idempotent", proxy)
        self.assertIn("proxy_ssl_session_reuse off;", proxy)
        self.assertIn("proxy_ssl_verify on;", proxy)
        # The staging chain has two untrusted intermediates. Nginx's default
        # depth of one rejects it even though an ordinary browser verifies it.
        self.assertIn("proxy_ssl_verify_depth 4;", proxy)
        self.assertNotIn("proxy_ssl_verify off;", proxy)
        self.assertIn("proxy_ssl_name test.hs-manacost.ru;", proxy)
        self.assertIn("proxy_ssl_trusted_certificate /etc/ssl/certs/ca-certificates.crt;", proxy)

    def test_private_staging_boundary_is_preserved(self):
        proxy = directives("proxy-staging-reader.conf")
        for expected in (
            "location ~ ^/(?:reader-auth|reader-api)/ {",
            "proxy_cache off;",
            "proxy_buffering off;",
            "access_log off;",
            "error_log /dev/null;",
            "proxy_set_header Host test.hs-manacost.ru;",
            "proxy_set_header Authorization $http_authorization;",
            "proxy_set_header X-Forwarded-For $remote_addr;",
            'proxy_set_header Forwarded "";',
        ):
            self.assertIn(expected, proxy)
        self.assertNotIn("auth_basic off;", proxy)

    def test_production_reader_pins_the_origin_certificate(self):
        proxy = directives("proxy-production-reader.conf")
        self.assertEqual(proxy.count('proxy_set_header Connection "";'), 2)
        self.assertEqual(proxy.count('proxy_http_version 1.1;'), 2)
        self.assertEqual(proxy.count('proxy_ssl_session_reuse off;'), 2)
        self.assertNotIn("non_idempotent", proxy)
        self.assertIn("proxy_pass https://hs_manacost_reader_production_origin;", proxy)
        self.assertIn("proxy_ssl_verify on;", proxy)
        self.assertNotIn("proxy_ssl_verify off;", proxy)
        self.assertIn("proxy_ssl_name hs-manacost.ru;", proxy)
        self.assertIn(
            "proxy_ssl_trusted_certificate "
            "/etc/nginx/ssl/hs-manacost-reader-origin-ca.pem;",
            proxy,
        )
        self.assertNotIn(
            "proxy_ssl_trusted_certificate /etc/ssl/certs/ca-certificates.crt;",
            proxy,
        )

    def test_production_account_bypasses_every_shared_edge_cache(self):
        proxy = directives("proxy-production-reader.conf")
        self.assertIn("location ~* ^/account/?$ {", proxy)
        account = proxy.split("location ~* ^/account/?$ {", 1)[1].split(
            "location ~ ^/(?:reader-auth|reader-api)/ {", 1
        )[0]
        for expected in (
            "proxy_pass https://hs_manacost_reader_production_origin;",
            "rewrite ^ /account/ break;",
            "proxy_cache off;",
            "proxy_cache_bypass 1;",
            "proxy_no_cache 1;",
            "proxy_buffering off;",
            "proxy_ssl_verify on;",
            "proxy_ssl_name hs-manacost.ru;",
            "proxy_ssl_trusted_certificate /etc/nginx/ssl/hs-manacost-reader-origin-ca.pem;",
            "proxy_set_header Host hs-manacost.ru;",
            'proxy_set_header Authorization "";',
            "proxy_set_header X-Forwarded-Host hs-manacost.ru;",
        ):
            self.assertIn(expected, account)
        self.assertNotIn("proxy_hide_header Cache-Control;", account)
        self.assertNotIn("proxy_hide_header X-Robots-Tag;", account)
        self.assertNotIn("https://hs_manacost_origin;", account)
        self.assertNotIn("location = /account/ {", proxy)
        self.assertLess(
            proxy.index("location ~* ^/account/?$ {"),
            proxy.index("location ~ ^/(?:reader-auth|reader-api)/ {"),
        )
