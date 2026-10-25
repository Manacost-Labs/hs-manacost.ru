from __future__ import annotations

import unittest
import json
import subprocess
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OPTIMIZER = ROOT / "wordpress/mu-plugins/manacost-performance-optimizer.php"


class PerformanceOptimizerTest(unittest.TestCase):
    def test_both_critical_grid_images_are_high_without_changing_sources(self) -> None:
        script = r'''
        define('ABSPATH', '/');
        define('MANACOST_DEFER_THIRD_PARTY_ENABLED', false);
        define('MANACOST_NATIVE_THUMBNAILS_ENABLED', false);
        function add_action(...$args) {}
        function add_filter(...$args) {}
        function is_front_page() { return true; }
        function is_home() { return true; }
        function wp_is_mobile() { return $GLOBALS['mobile']; }
        function esc_url($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
        function esc_attr($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
        require $argv[1];
        $style = '<style>@font-face{font-family:Test;src:url(font.woff2)}h1{font-family:Test}</style>';
        $html = '<html><head>' . $style . '</head><body><div class="td-big-grid-flex">';
        for ($index = 1; $index <= 3; ++$index) {
          $html .= '<a class="td-image-wrap" title="Статья &amp; ' . $index . '"><span class="entry-thumb td-thumb-css" data-bg="https://hs-manacost.ru/wp-content/uploads/grid-' . $index . '-1068x542.jpg"></span></a>';
        }
        $html .= '</div></body></html>';
        $outputs = [];
        foreach ([false, true] as $mobile) {
          $GLOBALS['mobile'] = $mobile;
          $outputs[] = Manacost_Performance_Optimizer::optimize_html($html);
        }
        echo json_encode($outputs);
        '''
        result = subprocess.run(["php", "-r", script, str(OPTIMIZER)],
                                text=True, capture_output=True, check=True)
        from html.parser import HTMLParser

        class Tags(HTMLParser):
            def __init__(self):
                super().__init__()
                self.rows = []
            def handle_starttag(self, tag, attributes):
                self.rows.append((tag, dict(attributes)))

        for mobile, html in zip([False, True], json.loads(result.stdout)):
            tags = Tags()
            tags.feed(html)
            images = [attributes for tag, attributes in tags.rows if tag == "img"]
            self.assertEqual(2, len(images))
            self.assertEqual(["high", "high"], [image.get("fetchpriority") for image in images])
            for index, image in enumerate(images, 1):
                self.assertEqual(f"https://hs-manacost.ru/wp-content/uploads/grid-{index}-1068x542.jpg", image["src"])
                self.assertEqual("1068", image["width"])
                self.assertEqual("542", image["height"])
                self.assertEqual("eager", image["loading"])
                self.assertEqual("Статья & " + str(index), image["alt"])
                self.assertEqual("(max-width: 767px) 100vw, 50vw", image["sizes"])
            sources = [attributes for tag, attributes in tags.rows if tag == "source"]
            self.assertEqual([f"https://hs-manacost.ru/wp-content/uploads/grid-{index}-696x353.jpg" for index in [1, 2]], [source["srcset"] for source in sources])
            preload = next(attributes for tag, attributes in tags.rows if tag == "link" and attributes.get("id") == "manacost-lcp-preload")
            crop = "696x353" if mobile else "1068x542"
            self.assertEqual(f"https://hs-manacost.ru/wp-content/uploads/grid-1-{crop}.jpg", preload["href"])
            self.assertIn('<style>@font-face{font-family:Test;src:url(font.woff2)}h1{font-family:Test}</style>', html)
            self.assertIn('data-bg="https://hs-manacost.ru/wp-content/uploads/grid-3-1068x542.jpg"', html)

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

    def test_backdrop_keeps_a_placeholder_layer_until_it_decodes(self) -> None:
        script = r'''
        define('ABSPATH', '/');
        define('MANACOST_DEFER_THIRD_PARTY_ENABLED', false);
        define('MANACOST_NATIVE_THUMBNAILS_ENABLED', false);
        define('MANACOST_MOBILE_LITE_ENABLED', false);
        function add_action(...$args) {}
        function add_filter(...$args) {}
        require $argv[1];
        $rule = "body {\n\tbackground-color:#010101;\n\tbackground-image:url(\"https://hs-manacost.ru/wp-content/uploads/2026/09/temnaya-versiya-v2.png?mc_background=opaque-20261012\");\n\tbackground-attachment:fixed;\n}";
        $other = "body {\n\tbackground-image:url(\"https://hs-manacost.ru/wp-content/uploads/2026/09/other.png\");\n}";
        $page = fn($css) => '<html><head><style>' . $css . '</style></head><body></body></html>';
        echo json_encode([
          'backdrop' => Manacost_Performance_Optimizer::optimize_html($page($rule . $rule)),
          'other' => Manacost_Performance_Optimizer::optimize_html($page($other)),
        ]);
        '''
        result = json.loads(subprocess.run(["php", "-r", script, str(OPTIMIZER)],
                                           text=True, capture_output=True, check=True).stdout)
        backdrop = result["backdrop"]
        self.assertEqual(1, backdrop.count(",url(data:image/webp;base64,"))
        self.assertIn('temnaya-versiya-v2.png?mc_background=opaque-20261012"),url(data:image/webp;base64,', backdrop)
        # The placeholder must cover exactly the area of the 2400x1500 original.
        self.assertIn(");background-size:auto,2400px 1500px;", backdrop)
        self.assertIn("background-attachment:fixed;", backdrop)
        self.assertNotIn("data:image", result["other"])

    def test_jquery_leaves_the_head_only_for_anonymous_public_pages(self) -> None:
        script = r'''
        define('ABSPATH', '/');
        class Scripts { public $registered = ['jquery' => 1, 'jquery-core' => 1]; public $data = [];
          function add_data($handle, $key, $value) { $this->data[$handle][$key] = $value; } }
        function add_action(...$args) {}
        function add_filter(...$args) {}
        function is_admin() { return false; }
        function is_user_logged_in() { return $GLOBALS['logged_in']; }
        function wp_doing_ajax() { return false; }
        function is_feed() { return false; }
        function is_preview() { return false; }
        function is_robots() { return false; }
        function is_trackback() { return false; }
        function home_url() { return 'https://hs-manacost.ru'; }
        function wp_parse_url($url, $part = -1) { return parse_url($url, $part); }
        function sanitize_text_field($value) { return $value; }
        function wp_unslash($value) { return $value; }
        function wp_scripts() { return $GLOBALS['scripts']; }
        require $argv[1];
        $out = [];
        foreach ([false, true] as $logged_in) {
          $GLOBALS['logged_in'] = $logged_in;
          $GLOBALS['scripts'] = new Scripts();
          $_SERVER['REQUEST_URI'] = '/some-article/';
          Manacost_Performance_Optimizer::move_jquery_to_footer();
          $out[] = $GLOBALS['scripts']->data;
        }
        $GLOBALS['logged_in'] = false;
        $GLOBALS['scripts'] = new Scripts();
        $_SERVER['REQUEST_URI'] = '/account/';
        Manacost_Performance_Optimizer::move_jquery_to_footer();
        $out[] = $GLOBALS['scripts']->data;
        echo json_encode($out);
        '''
        anonymous, logged_in, account = json.loads(subprocess.run(["php", "-r", script, str(OPTIMIZER)],
                                                                 text=True, capture_output=True, check=True).stdout)
        self.assertEqual({"jquery": {"group": 1}, "jquery-core": {"group": 1}}, anonymous)
        self.assertEqual([], logged_in)
        self.assertEqual([], account)

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
