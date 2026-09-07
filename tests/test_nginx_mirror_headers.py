import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]


class MirrorHeaderTests(unittest.TestCase):
    def test_both_mirror_servers_protect_cached_and_nested_responses(self):
        config = (ROOT / "ops/nginx/mirror.conf").read_text()
        for directive in (
            'add_header X-Robots-Tag "noindex, follow" always;',
            'add_header X-Manacost-Mirror "active" always;',
            'add_header_inherit merge;',
            'fastcgi_hide_header X-Manacost-Mirror;',
        ):
            self.assertEqual(config.count(directive), 2, directive)
        # A stricter per-page upstream robots policy must not be discarded.
        self.assertNotIn('fastcgi_hide_header X-Robots-Tag', config)

    def test_primary_does_not_receive_mirror_policy(self):
        config = (ROOT / "ops/nginx/origin.conf").read_text()
        self.assertNotRegex(config, r'add_header\s+X-Manacost-Mirror')
        self.assertNotRegex(config, r'add_header\s+X-Robots-Tag\s+.*noindex')

    def test_smoke_requires_robots_not_only_mirror_marker(self):
        config = (ROOT / "ops/smoke-check.sh").read_text()
        self.assertIn("header_value 'X-Robots-Tag'", config)
        self.assertIn('noindex', config)
        self.assertIn('assert_host_policy "$domain" "$path"', config)
        self.assertIn('for ip in "$origin_ip" "${edge_ips[@]}"', config)
        self.assertIn("'/__manacost_header_check_missing__/' '404'", config)
        self.assertIn("'/wp-content' '301'", config)
        self.assertIn("primary $domain$path received noindex", config)


if __name__ == '__main__':
    unittest.main()
