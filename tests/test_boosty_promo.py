from __future__ import annotations

import json
import struct
import shutil
import subprocess
import unittest
from html.parser import HTMLParser
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/manacost-boosty-promo.php"
BANNER = ROOT / "wordpress/mu-plugins/manacost-boosty-promo/banner.webp"
HOMEPAGE_BANNER = ROOT / "wordpress/mu-plugins/manacost-boosty-promo/homepage-banner.webp"
PROMO_CSS = ROOT / "wordpress/mu-plugins/manacost-boosty-promo.css"
NAVIGATION_CSS = ROOT / "wordpress/mu-plugins/manacost-site-navigation.css"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"


class BoostyPromoTest(unittest.TestCase):
    def test_cover_intent_is_deferred_and_excludes_private_request_contexts(self) -> None:
        for blocked in ('none', 'admin', 'logged_in', 'feed', 'preview', 'ajax', 'rest', 'disabled'):
            with self.subTest(blocked=blocked):
                flags = {name: json.dumps(blocked == name) for name in ('admin', 'logged_in', 'feed', 'preview', 'ajax')}
                script = f"""
                define('ABSPATH', '/');
                define('REST_REQUEST', {json.dumps(blocked == 'rest')});
                define('MANACOST_COVER_INTENT_ENABLED', {json.dumps(blocked != 'disabled')});
                function add_action(...$args) {{}}
                function add_filter(...$args) {{}}
                function is_admin() {{ return {flags['admin']}; }}
                function is_user_logged_in() {{ return {flags['logged_in']}; }}
                function is_feed() {{ return {flags['feed']}; }}
                function is_preview() {{ return {flags['preview']}; }}
                function wp_doing_ajax() {{ return {flags['ajax']}; }}
                function plugin_dir_url($file) {{ return '/wp-content/mu-plugins/'; }}
                function wp_enqueue_script(...$args) {{ $GLOBALS['scripts'][] = $args; }}
                $scripts = [];
                require {json.dumps(str(PLUGIN))};
                Manacost_Boosty_Promo::enqueue_cover_intent();
                echo json_encode($scripts);
                """
                result = subprocess.run([PHP_BINARY, '-r', script], capture_output=True, text=True, check=True)
                scripts = json.loads(result.stdout)
                if blocked == 'none':
                    self.assertEqual(scripts[0][0:4], ['manacost-cover-intent', '/wp-content/mu-plugins/manacost-site-navigation.js', [], '1.0.0'])
                    self.assertEqual(scripts[0][4], {'strategy': 'defer', 'in_footer': True})
                else:
                    self.assertEqual(scripts, [])

    def test_banner_assets_are_versioned_webp_files(self) -> None:
        self.assertTrue(BANNER.is_file())
        self.assertEqual(b"RIFF", BANNER.read_bytes()[:4])
        data = BANNER.read_bytes()
        self.assertEqual(b"VP8 ", data[12:16])
        width, height = struct.unpack("<HH", data[26:30])
        self.assertEqual((1173, 1341), (width & 0x3FFF, height & 0x3FFF))

        self.assertTrue(HOMEPAGE_BANNER.is_file())
        self.assertEqual(b"RIFF", HOMEPAGE_BANNER.read_bytes()[:4])
        homepage_data = HOMEPAGE_BANNER.read_bytes()
        self.assertEqual(b"VP8 ", homepage_data[12:16])
        width, height = struct.unpack("<HH", homepage_data[26:30])
        self.assertEqual((1172, 1342), (width & 0x3FFF, height & 0x3FFF))

    def test_homepage_geometry_keeps_the_promo_and_navigation_in_one_flow(self) -> None:
        promo_css = PROMO_CSS.read_text(encoding="utf-8")
        navigation_css = NAVIGATION_CSS.read_text(encoding="utf-8")

        self.assertIn(".manacost-boosty-promo + .td_block_wrap", promo_css)
        self.assertNotIn(".manacost-banner-rotator", promo_css)
        self.assertIn("0 22px 60px rgba(15, 40, 70, 0.1)", promo_css)
        self.assertNotIn("0 14px 34px rgba(15, 40, 70, 0.22)", promo_css)
        self.assertIn("margin-top: 48px", promo_css)
        self.assertIn("margin-top: 28px", promo_css)
        self.assertIn("font-size: 13px", navigation_css)
        self.assertIn("padding-right: 9px", navigation_css)
        self.assertIn("padding-left: 9px", navigation_css)
        self.assertIn("width: calc(100% - 144px)", navigation_css)
        self.assertIn("column-gap: 6px", navigation_css)
        self.assertIn("li.mc-reader-entry > a", navigation_css)
        self.assertIn("li.menu-item-has-children > a", navigation_css)
        self.assertIn("right: 8px", navigation_css)
        self.assertIn("border-radius: 8px", navigation_css)
        self.assertNotIn("box-shadow: inset 0 -3px 0", navigation_css)
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
        function is_user_logged_in() {{ return false; }}
        function is_feed() {{ return false; }}
        function is_preview() {{ return false; }}
        function wp_doing_ajax() {{ return false; }}
        function wp_enqueue_script(...$args) {{}}
        function is_front_page() {{ return $GLOBALS['front_page']; }}
        function esc_url($value) {{ return $value; }}
        function esc_attr($value) {{ return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }}
        function esc_html($value) {{ return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }}
        function plugin_dir_url($file) {{ return '/wp-content/mu-plugins/'; }}
        class WP_Widget_Block {{
            public $id;
            public function __construct($id) {{ $this->id = $id; }}
        }}
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

        $legacy_sidebar = '<figure><a href="https://web.tribute.tg/s/xz9"><img src="/wp-content/uploads/2026/01/64b4112a-4bbb-4093-a12a-1f92b1b1defc.jpg"></a></figure>';
        $other_sidebar = '<figure><a href="/editorial/"><img src="/editorial.jpg"></a></figure>';
        $sidebar = $legacy_sidebar;
        $untargeted_sidebar = $legacy_sidebar;
        $updated_target_sidebar = $other_sidebar;
        foreach ($filters['widget_block_content'] ?? [] as $entry) {{
            $sidebar = call_user_func($entry[0], $sidebar, [], new WP_Widget_Block('block-37'));
            $untargeted_sidebar = call_user_func($entry[0], $untargeted_sidebar, [], new WP_Widget_Block('block-99'));
            $updated_target_sidebar = call_user_func($entry[0], $updated_target_sidebar, [], new WP_Widget_Block('block-37'));
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
            'sidebar' => $sidebar,
            'legacy_sidebar' => $legacy_sidebar,
            'untargeted_sidebar' => $untargeted_sidebar,
            'updated_target_sidebar' => $updated_target_sidebar,
            'other_sidebar' => $other_sidebar,
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
        self.assertIn('src="/wp-content/mu-plugins/manacost-boosty-promo/homepage-banner.webp?ver=44d5f1f12b09"', result["shortcode"])
        self.assertIn('width="1172" height="1342"', result["shortcode"])
        self.assertIn('aria-label="Поддержать Manacost на Boosty"', result["shortcode"])
        self.assertFalse(result["other_shortcode"])
        self.assertIn("manacost-site-navigation", result["styles"])
        self.assertIn("manacost-boosty-promo", result["styles"])
        self.assertIn('data-no-minify="1"', result["navigation_tag"])
        self.assertNotIn('data-no-minify="1"', result["other_tag"])
        self.assertIn("manacost-site-navigation.css", result["perfmatters_exclusions"])
        self.assertIn("manacost-boosty-promo.css", result["perfmatters_exclusions"])

    def test_navigation_and_sidebar_promo_styles_load_beyond_the_homepage(self) -> None:
        result = self.run_plugin(is_front_page=False)

        self.assertIn("manacost-site-navigation", result["styles"])
        self.assertIn("manacost-boosty-promo", result["styles"])

    def test_promo_images_offer_small_renditions_and_allow_the_site_lazy_loader(self) -> None:
        for homepage in (True, False):
            result = self.run_plugin(is_front_page=homepage)
            markup = result["shortcode" if homepage else "sidebar"]
            basename = "homepage-banner" if homepage else "banner"
            self.assertIn('loading="lazy"', markup)
            self.assertNotIn('data-no-lazy=', markup)
            self.assertIn('sizes="(max-width: 767px) calc(100vw - 40px), 350px"', markup)
            self.assertIn('srcset="', markup)
            for width in (400, 800):
                self.assertIn(f"{basename}-{width}.webp", markup)
                asset = BANNER.parent / f"{basename}-{width}.webp"
                data = asset.read_bytes()
                self.assertEqual(b"RIFF", data[:4])
                self.assertEqual(b"VP8 ", data[12:16])
                self.assertEqual(width, struct.unpack("<H", data[26:28])[0] & 0x3FFF)
                original = HOMEPAGE_BANNER if homepage else BANNER
                self.assertLess(len(data), original.stat().st_size)

    def test_rocket_defers_promo_sources_and_preserves_the_no_script_fallback(self) -> None:
        rocket_image = ROOT / "wordpress/plugins/wp-rocket/inc/Dependencies/RocketLazyload/Image.php"
        script = f"""
        require {json.dumps(str(rocket_image))};
        $image = new WP_Rocket\\Dependencies\\RocketLazyload\\Image();
        function absint($value) {{ return abs((int) $value); }}
        function esc_url($value) {{ return $value; }}
        function apply_filters($hook, $value, ...$args) {{
            return $hook === 'rocket_lazyload_html'
                ? $GLOBALS['image']->lazyloadResponsiveAttributes($value) : $value;
        }}
        function wpm_apply_filters_typed($type, $hook, $value) {{ return $value; }}
        $markup = stream_get_contents(STDIN);
        echo $image->lazyloadImages($markup, $markup, false);
        """
        for homepage in (True, False):
            markup = self.run_plugin(is_front_page=homepage)["shortcode" if homepage else "sidebar"]
            result = subprocess.run([PHP_BINARY, "-r", script], input=markup,
                                    text=True, capture_output=True, check=True)
            active, fallback = result.stdout.split("<noscript>", 1)
            images = []
            class ImageAttributes(HTMLParser):
                def handle_starttag(self, tag, attrs):
                    if tag == "img":
                        images.append(dict(attrs))
            ImageAttributes().feed(active)
            self.assertEqual(1, len(images))
            self.assertTrue(images[0]["src"].startswith("data:image/"))
            self.assertNotIn("srcset", images[0])
            self.assertIn("400w", images[0]["data-lazy-srcset"])
            self.assertIn("800w", images[0]["data-lazy-srcset"])
            self.assertEqual("350px", images[0]["data-lazy-sizes"].split(", ")[-1])
            self.assertIn('width="117', active)
            self.assertIn(markup.split("<img", 1)[1].split("</a>", 1)[0], fallback)

    def test_replaces_only_the_legacy_article_sidebar_banner(self) -> None:
        result = self.run_plugin(is_front_page=False)

        self.assertIn('class="manacost-boosty-promo"', result["sidebar"])
        self.assertIn('href="https://boosty.to/kolodahearthstone"', result["sidebar"])
        self.assertIn('src="/wp-content/mu-plugins/manacost-boosty-promo/banner.webp?ver=5247cc0c54b8"', result["sidebar"])
        self.assertNotIn("web.tribute.tg", result["sidebar"])
        self.assertNotIn("64b4112a-4bbb-4093-a12a-1f92b1b1defc.jpg", result["sidebar"])
        self.assertEqual(result["legacy_sidebar"], result["untargeted_sidebar"])
        self.assertEqual(result["other_sidebar"], result["updated_target_sidebar"])

    def test_keeps_pricing_shortcode_outside_the_homepage(self) -> None:
        result = self.run_plugin(is_front_page=False)

        self.assertFalse(result["shortcode"])


if __name__ == "__main__":
    unittest.main()
