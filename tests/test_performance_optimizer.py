from __future__ import annotations

import unittest
import json
import subprocess
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OPTIMIZER = ROOT / "wordpress/mu-plugins/manacost-performance-optimizer.php"


class PerformanceOptimizerTest(unittest.TestCase):
    def test_only_the_optimized_background_gets_a_new_delivery_cache_key(self) -> None:
        script = r'''
        define('ABSPATH', '/');
        $filters = [];
        function add_action(...$args) {}
        function add_filter($hook, $callback, ...$args) { $GLOBALS['filters'][$hook] = $callback; }
        function is_admin() { return $GLOBALS['admin'] ?? false; }
        function get_template() { return 'Newspaper_new'; }
        function wp_parse_url($url, $part) { return parse_url($url, $part); }
        function add_query_arg($name, $value, $url) { return $url . (str_contains($url, '?') ? '&' : '?') . $name . '=' . $value; }
        require $argv[1];
        $urls = [
            'https://hs-manacost.ru/wp-content/uploads/2026/09/temnaya-versiya-v2.png',
            'https://hs-manacost.com/wp-content/uploads/2026/09/temnaya-versiya-v2.png',
            'https://test.hs-manacost.ru/wp-content/uploads/2026/09/temnaya-versiya-v2.png',
            'https://hs-manacost.ru/wp-content/uploads/another-background.png',
            'https://outside.example/wp-content/uploads/2026/09/temnaya-versiya-v2.png',
            false,
        ];
        $callback = $filters['option_td_011'] ?? null;
        if (!$callback) { echo json_encode(['missing' => true]); exit; }
        $options = array_map(fn($url) => ['tds_site_background_image' => $url, 'unrelated' => 'keep'], $urls);
        $public = array_map($callback, $options);
        $GLOBALS['admin'] = true;
        echo json_encode(['public' => $public, 'admin' => array_map($callback, $options), 'original' => $options, 'invalid' => $callback(false)]);
        '''
        result = subprocess.run(["php", "-r", script, str(OPTIMIZER)], text=True, capture_output=True, check=True)
        data = json.loads(result.stdout)
        self.assertNotIn("missing", data)
        for value in data["public"][:3]:
            self.assertIn("mc_background=opaque-20261012", value["tds_site_background_image"])
            self.assertEqual("keep", value["unrelated"])
        self.assertEqual(data["original"][3:], data["public"][3:])
        self.assertEqual(data["original"], data["admin"])
        self.assertFalse(data["invalid"])

    def test_responsive_page_does_not_have_a_user_agent_typography_variant(self) -> None:
        source = OPTIMIZER.read_text(encoding="utf-8")

        self.assertNotIn("remove_mobile_webfonts", source)
        self.assertNotIn("manacost-mobile-font-budget", source)
        self.assertNotIn('font-family:Arial,"Helvetica Neue",sans-serif!important', source)

    def test_mobile_request_detection_remains_limited_to_image_preload_selection(self) -> None:
        source = OPTIMIZER.read_text(encoding="utf-8")

        self.assertIn("$lcp_url = self::is_mobile_request() ? $mobile_url : $desktop_url;", source)
        self.assertNotIn("if ( self::is_mobile_request() ) {\n\t\t\t\t$html = self::remove_mobile_webfonts", source)


if __name__ == "__main__":
    unittest.main()
