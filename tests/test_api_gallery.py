"""Executable catalog policy: no network, remote paths or unsafe image hosts."""
import json
from pathlib import Path
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[1]


class ApiGalleryPolicy(unittest.TestCase):
    def evaluate(self, expression):
        source = "<?php define('ABSPATH', '/unused/'); function __($s,$d=''){return $s;} function wp_parse_url($s){return parse_url($s);} function wp_strip_all_tags($s){return strip_tags($s);} "
        source += "require 'wordpress/mu-plugins/hs-api-gallery/class-catalog.php'; "
        source += "echo json_encode(" + expression + ");"
        result = subprocess.run(['php'], input=source, text=True, capture_output=True, cwd=ROOT)
        self.assertEqual(result.returncode, 0, result.stderr)
        return json.loads(result.stdout)

    def test_only_known_libraries(self):
        self.assertEqual(self.evaluate("count(Manacost\\ApiGallery\\Catalog::libraries())"), 14)

    def test_strict_https_image_sources(self):
        for url in ['http://api.kolodahearthstone.com/uploads/a.png', 'https://localhost/a.png',
                    'https://api.kolodahearthstone.com.evil.test/a.png',
                    'https://user@api.kolodahearthstone.com/a.png',
                    'https://api.kolodahearthstone.com:8443/a.png', 'file:///etc/passwd']:
            with self.subTest(url=url):
                self.assertFalse(self.evaluate('Manacost\\ApiGallery\\Catalog::image_url(' + json.dumps(url) + ')'))

    def test_api_image_variants_are_not_arbitrary_urls(self):
        row = {'card_id': 'TEST_1', 'name': {'ru': 'Тест'}, 'images': {
            'card': 'https://api.kolodahearthstone.com/uploads/test.png',
            'full_art_source': 'https://evil.test/not-an-image',
            'golden': 'https://api.kolodahearthstone.com/uploads/gold.png'}}
        data = self.evaluate('Manacost\\ApiGallery\\Catalog::normalize(' +
                             'json_decode(' + json.dumps(json.dumps(row)) + ',true))')
        self.assertEqual(data['name'], 'Тест')
        self.assertEqual(list(data['images']), ['card', 'golden'])


if __name__ == '__main__':
    unittest.main()
