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
    def test_reader_has_a_dedicated_nonpersistent_upstream(self):
        upstream = directives("proxy-staging-upstream.conf")
        self.assertIn("upstream hs_manacost_reader_origin {", upstream)
        self.assertIn("zone hs_manacost_reader_origin 64k;", upstream)
        self.assertNotRegex(upstream, r"\bkeepalive\b")
        self.assertEqual(
            re.findall(r"server ([^;]+);", upstream),
            [f"127.0.0.1:{port} max_fails=2 fail_timeout=2s" for port in (18443, 18444, 18445)],
        )

    def test_reader_never_borrows_unverified_connections_or_tls_sessions(self):
        proxy = directives("proxy-staging-reader.conf")
        self.assertIn("proxy_pass https://hs_manacost_reader_origin;", proxy)
        self.assertNotIn("https://hs_manacost_origin;", proxy)
        self.assertIn("proxy_set_header Connection close;", proxy)
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
