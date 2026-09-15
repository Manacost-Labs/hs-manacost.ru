from __future__ import annotations

import json
import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/manacost-boosty-promo.php"
BANNER = ROOT / "wordpress/mu-plugins/manacost-boosty-promo/banner.webp"
PROMO_CSS = ROOT / "wordpress/mu-plugins/manacost-boosty-promo.css"
NAVIGATION_CSS = ROOT / "wordpress/mu-plugins/manacost-site-navigation.css"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"


class BoostyPromoTest(unittest.TestCase):
    def test_banner_asset_is_versioned_webp(self) -> None:
        self.assertTrue(BANNER.is_file())
        self.assertEqual(b"RIFF", BANNER.read_bytes()[:4])

    def test_homepage_geometry_keeps_the_promo_and_navigation_in_one_flow(self) -> None:
        promo_css = PROMO_CSS.read_text(encoding="utf-8")
        navigation_css = NAVIGATION_CSS.read_text(encoding="utf-8")

        self.assertIn(".manacost-boosty-promo + .td_block_wrap", promo_css)
        self.assertIn("margin-top: 48px", promo_css)
        self.assertIn("margin-top: 28px", promo_css)
        self.assertIn("font-size: 14px", navigation_css)
        self.assertIn("padding-right: 6px", navigation_css)
        self.assertIn("padding-left: 6px", navigation_css)
        self.assertIn("width: 1116px", navigation_css)
        self.assertIn("li.mc-reader-entry > a", navigation_css)
        self.assertIn("li.menu-item-has-children > a", navigation_css)
        self.assertIn("right: 6px", navigation_css)
        self.assertIn("border-radius: 8px", navigation_css)
        self.assertIn(":focus-visible", navigation_css)
        self.assertIn("prefers-reduced-motion", navigation_css)

    def run_plugin(self, is_front_page: bool) -> dict[str, object]:
        script = f"""
        define('ABSPATH', '/');
        $filters = [];
        $actions = [];
        $styles = [];
        $front_page = {str(is_front_page).lower()};
        function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {{
            $GLOBALS['filters'][$hook][] = [$callback, $priority, $accepted_args];
        }}
        function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {{
            $GLOBALS['actions'][$hook][] = [$callback, $priority, $accepted_args];
        }}
        function wp_enqueue_style($handle, $src, $dependencies = [], $version = false) {{
            $GLOBALS['styles'][$handle] = [$src, $dependencies, $version];
        }}
        function is_admin() {{ return false; }}
        function is_front_page() {{ return $GLOBALS['front_page']; }}
        function esc_url($value) {{ return $value; }}
        function esc_attr($value) {{ return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }}
        function esc_html($value) {{ return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }}
        function plugin_dir_url($file) {{ return '/wp-content/mu-plugins/'; }}
        require {json.dumps(str(PLUGIN))};

        foreach ($actions['wp_enqueue_scripts'] ?? [] as $entry) {{
            call_user_func($entry[0]);
        }}

        $navigation_tag = '<link rel="stylesheet" href="/navigation.css">';
        foreach ($filters['style_loader_tag'] ?? [] as $entry) {{
            $navigation_tag = call_user_func($entry[0], $navigation_tag, 'manacost-site-navigation', '/navigation.css', 'all');
        }}

        $other_tag = '<link rel="stylesheet" href="/other.css">';
        foreach ($filters['style_loader_tag'] ?? [] as $entry) {{
            $other_tag = call_user_func($entry[0], $other_tag, 'other-style', '/other.css', 'all');
        }}

        $perfmatters_exclusions = [];
        foreach ($filters['perfmatters_minify_css_exclusions'] ?? [] as $entry) {{
            $perfmatters_exclusions = call_user_func($entry[0], $perfmatters_exclusions);
        }}

        $footer = '<li class="menu-item"><a href="/existing/">Existing</a></li>';
        foreach ($filters['wp_nav_menu_items'] ?? [] as $entry) {{
            $footer = call_user_func($entry[0], $footer, (object) ['theme_location' => 'footer-menu']);
        }}

        $other_menu = '<li class="menu-item"><a href="/existing/">Existing</a></li>';
        foreach ($filters['wp_nav_menu_items'] ?? [] as $entry) {{
            $other_menu = call_user_func($entry[0], $other_menu, (object) ['theme_location' => 'header-menu']);
        }}

        $shortcode = false;
        foreach ($filters['pre_do_shortcode_tag'] ?? [] as $entry) {{
            $shortcode = call_user_func(
                $entry[0],
                $shortcode,
                'tdm_block_pricing',
                [
                    'button_url' => 'https://boosty.to/kolodahearthstone',
                    'tds_pricing' => 'tds_pricing1',
                ],
                []
            );
        }}

        $other_shortcode = false;
        foreach ($filters['pre_do_shortcode_tag'] ?? [] as $entry) {{
            $other_shortcode = call_user_func(
                $entry[0],
                $other_shortcode,
                'tdm_block_pricing',
                [
                    'button_url' => 'https://boosty.to/another',
                    'tds_pricing' => 'tds_pricing1',
                ],
                []
            );
        }}

        echo json_encode([
            'footer' => $footer,
            'other_menu' => $other_menu,
            'shortcode' => $shortcode,
            'other_shortcode' => $other_shortcode,
            'styles' => $styles,
            'navigation_tag' => $navigation_tag,
            'other_tag' => $other_tag,
            'perfmatters_exclusions' => $perfmatters_exclusions,
        ]);
        """
        completed = subprocess.run(
            [PHP_BINARY, "-r", script],
            check=False,
            capture_output=True,
            text=True,
        )
        self.assertEqual(completed.returncode, 0, completed.stderr)
        return json.loads(completed.stdout)

    def test_adds_requested_links_only_to_the_footer_menu(self) -> None:
        result = self.run_plugin(is_front_page=False)

        self.assertIn('href="https://hs-manacost.ru/reklama-na-sajte/"', result["footer"])
        self.assertIn('Реклама на сайте', result["footer"])
        self.assertIn('href="https://t.me/manacostcard_bot"', result["footer"])
        self.assertIn('Конструктор колод', result["footer"])
        self.assertIn(
            'href="https://hs-manacost.ru/hearthpulse-chto-eto-i-kak-polzovatsya-servisom-zametki-taverny-2/"',
            result["footer"],
        )
        self.assertIn('Как пользоваться Hearthpulse', result["footer"])
        self.assertEqual('<li class="menu-item"><a href="/existing/">Existing</a></li>', result["other_menu"])

    def test_replaces_only_the_homepage_boosty_pricing_shortcode(self) -> None:
        result = self.run_plugin(is_front_page=True)

        self.assertIn('class="manacost-boosty-promo"', result["shortcode"])
        self.assertIn('href="https://boosty.to/kolodahearthstone"', result["shortcode"])
        self.assertIn('src="/wp-content/mu-plugins/manacost-boosty-promo/banner.webp"', result["shortcode"])
        self.assertIn('aria-label="Поддержать Manacost на Boosty"', result["shortcode"])
        self.assertFalse(result["other_shortcode"])
        self.assertIn("manacost-site-navigation", result["styles"])
        self.assertIn("manacost-boosty-promo", result["styles"])
        self.assertIn('data-no-minify="1"', result["navigation_tag"])
        self.assertNotIn('data-no-minify="1"', result["other_tag"])
        self.assertIn("manacost-site-navigation.css", result["perfmatters_exclusions"])
        self.assertIn("manacost-boosty-promo.css", result["perfmatters_exclusions"])

    def test_navigation_typography_loads_beyond_the_homepage(self) -> None:
        result = self.run_plugin(is_front_page=False)

        self.assertIn("manacost-site-navigation", result["styles"])
        self.assertNotIn("manacost-boosty-promo", result["styles"])

    def test_keeps_pricing_shortcode_outside_the_homepage(self) -> None:
        result = self.run_plugin(is_front_page=False)

        self.assertFalse(result["shortcode"])


if __name__ == "__main__":
    unittest.main()
