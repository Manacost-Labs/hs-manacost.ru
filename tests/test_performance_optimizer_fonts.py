from __future__ import annotations

import json
import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/manacost-performance-optimizer.php"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"
FONT_MARKUP = (
    '<link rel="preconnect" href="https://fonts.googleapis.com">',
    '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>',
    '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Oswald:wght@600">',
    '<link data-wpr-hosted-gf-parameters="family=Ubuntu:wght@800" '
    'rel="stylesheet" href="/wp-content/cache/fonts/1/google-fonts/css/theme.css" '
    'media="print" onload="this.media=\'all\'">',
    '<noscript data-wpr-hosted-gf-parameters="family=Ubuntu:wght@800">'
    '<link rel="stylesheet" href="/wp-content/cache/fonts/1/google-fonts/css/theme.css">'
    '</noscript>',
    '<link rel="preload" as="font" type="font/woff2" '
    'href="/wp-content/cache/fonts/1/google-fonts/fonts/ubuntu-cyrillic-800.woff2" '
    'crossorigin>',
    '<style id="theme-typography">.td_block_wrap h4{font-family:Oswald;font-weight:600}'
    '.td_block_wrap .entry-title{font-family:Ubuntu;font-weight:800}</style>',
)
PAGE = (
    '<!doctype html><html lang="ru"><head>'
    + "\n".join(FONT_MARKUP)
    + '</head><body><div class="banner-rotator">'
    '<a href="https://example.com/first"><img src="first.jpg"></a>'
    '<a href="https://example.com/second"><img src="second.jpg"></a></div>'
    '<div class="td_block_wrap"><h4>Потасовка недели</h4>'
    '<a class="entry-title">Колоды</a></div>'
    '<script src="https://www.googletagmanager.com/gtag/js?id=test"></script>'
    '<script src="/wp-includes/js/jquery/jquery.min.js"></script></body></html>'
)


class PerformanceOptimizerFontsTest(unittest.TestCase):
    def render_page(
        self,
        *,
        mobile: bool = False,
        legacy_font_trim: bool | None = None,
        logged_in: bool = False,
        admin: bool = False,
        request_uri: str = "/",
        front_page: bool = True,
        user_agent: str = "",
        page: str = PAGE,
    ) -> str:
        constants = ""
        if legacy_font_trim is not None:
            constants = (
                "define('MANACOST_FONT_TRIM_ENABLED', "
                + json.dumps(legacy_font_trim)
                + ");"
            )
        script = f"""
        define('ABSPATH', '/');
        {constants}
        function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {{}}
        function is_admin() {{ return {json.dumps(admin)}; }}
        function is_user_logged_in() {{ return {json.dumps(logged_in)}; }}
        function wp_doing_ajax() {{ return false; }}
        function is_feed() {{ return false; }}
        function is_preview() {{ return false; }}
        function is_robots() {{ return false; }}
        function is_trackback() {{ return false; }}
        function is_front_page() {{ return {json.dumps(front_page)}; }}
        function is_home() {{ return {json.dumps(front_page)}; }}
        function wp_is_mobile() {{ return {json.dumps(mobile)}; }}
        function home_url() {{ return 'https://hs-manacost.ru'; }}
        function wp_parse_url($url, $component = -1) {{ return parse_url($url, $component); }}
        function wp_unslash($value) {{ return stripslashes($value); }}
        function sanitize_text_field($value) {{ return trim(strip_tags($value)); }}
        function esc_url($value) {{ return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }}
        function esc_attr($value) {{ return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }}
        require {json.dumps(str(PLUGIN))};
        $_SERVER['REQUEST_URI'] = {json.dumps(request_uri)};
        $_SERVER['HTTP_USER_AGENT'] = {json.dumps(user_agent)};
        ob_start();
        $outer_level = ob_get_level();
        Manacost_Performance_Optimizer::start();
        echo {json.dumps(page, ensure_ascii=False)};
        if (ob_get_level() > $outer_level) {{ ob_end_flush(); }}
        echo json_encode(ob_get_clean(), JSON_UNESCAPED_UNICODE);
        """
        completed = subprocess.run(
            [PHP_BINARY, "-r", script],
            check=False,
            capture_output=True,
            text=True,
        )
        self.assertEqual(completed.returncode, 0, completed.stderr)
        return json.loads(completed.stdout)

    def test_guest_home_keeps_theme_fonts_for_every_user_agent(self) -> None:
        for mobile in (False, True):
            for legacy_font_trim in (None, True):
                with self.subTest(mobile=mobile, legacy_font_trim=legacy_font_trim):
                    result = self.render_page(
                        mobile=mobile, legacy_font_trim=legacy_font_trim
                    )
                    for markup in FONT_MARKUP:
                        self.assertIn(markup, result)
                    self.assertNotIn('id="manacost-mobile-font-budget"', result)
                    self.assertNotIn('id="manacost-font-trim"', result)
                    self.assertIn('name="manacost-perf-active"', result)

    def test_mobile_home_promotes_current_first_cards_instead_of_stale_urls(self) -> None:
        grid = (
            '<div class="td-big-grid-flex td_block_big_grid_flex_1">'
            '<a href="/first/" class="td-image-wrap" title="Первая статья">'
            '<span data-bg="https://hs-manacost.ru/wp-content/uploads/2026/09/first-1068x542.jpg" '
            'class="entry-thumb td-thumb-css rocket-lazyload" style=""></span></a>'
            '<a href="/second/" class="td-image-wrap" title="Вторая статья">'
            '<span data-bg="https://hs-manacost.ru/wp-content/uploads/2026/09/second-1068x542.jpg" '
            'class="entry-thumb td-thumb-css rocket-lazyload" style=""></span></a>'
            '</div>'
        )
        stale = (
            '<link rel="preload" data-rocket-preload as="image" '
            'href="https://hs-manacost.ru/wp-content/uploads/2026/09/stale.jpg" '
            'fetchpriority="high">'
        )
        page = PAGE.replace('</head>', stale + '</head>').replace('</body>', grid + '</body>')

        result = self.render_page(mobile=True, page=page)

        self.assertNotIn('stale.jpg', result)
        self.assertNotIn('budget-decks-', result)
        self.assertIn(
            '<link id="manacost-lcp-preload" rel="preload" as="image" '
            'href="https://hs-manacost.ru/wp-content/uploads/2026/09/first-696x353.jpg" '
            'fetchpriority="high">',
            result,
        )
        self.assertEqual(result.count('class="manacost-lcp-picture"'), 2)
        self.assertIn('alt="Первая статья"', result)
        self.assertIn('loading="eager" fetchpriority="high"', result)
        self.assertNotIn('data-bg="https://hs-manacost.ru/wp-content/uploads/2026/09/first-', result)

    def test_public_archive_promotes_first_lazy_thumbnail(self) -> None:
        card = (
            '<div class="td-module-thumb"><a href="/guide/" class="td-image-wrap">'
            '<img width="324" height="235" class="entry-thumb" '
            'src="data:image/svg+xml,placeholder" '
            'data-lazy-src="https://hs-manacost.ru/wp-content/uploads/2026/09/guide-324x235.jpg" '
            'data-lazy-srcset="https://hs-manacost.ru/wp-content/uploads/2026/09/guide-324x235.jpg 324w" '
            'data-lazy-sizes="100vw" alt="Гайд"></a></div>'
        )
        page = PAGE.replace('</body>', card + '</body>')

        result = self.render_page(
            mobile=True,
            page=page,
            request_uri='/category/gajdy-hearthstone/',
            front_page=False,
        )

        self.assertIn('src="https://hs-manacost.ru/wp-content/uploads/2026/09/guide-324x235.jpg"', result)
        self.assertIn('srcset="https://hs-manacost.ru/wp-content/uploads/2026/09/guide-324x235.jpg 324w"', result)
        self.assertIn('sizes="100vw"', result)
        self.assertIn('loading="eager"', result)
        self.assertIn('fetchpriority="high"', result)
        self.assertNotIn('data-lazy-src=', result)
        self.assertIn('name="manacost-perf-active"', result)

    def test_mobile_critical_css_suppresses_body_art_without_replacing_theme_fonts(self) -> None:
        result = self.render_page(mobile=True)

        self.assertIn('background-image:none!important', result)
        self.assertNotIn('id="manacost-mobile-font-budget"', result)
        self.assertNotIn('font-family:Arial', result)

    def test_font_repair_keeps_non_ad_default_optimizations_active(self) -> None:
        for mobile in (False, True):
            with self.subTest(mobile=mobile):
                result = self.render_page(mobile=mobile)
                self.assertIn('id="manacost-mobile-lite-critical"', result)
                self.assertIn(
                    '<div class="banner-rotator"><a href="https://example.com/first">',
                    result,
                )
                self.assertNotIn("manacost-banner-rotator", result)
                self.assertNotIn("manacost-banner-slide", result)
                self.assertNotIn("manacostBannerFirst", result)
                self.assertNotIn("/wp-content/uploads/2026/07/728x90.jpg", result)
                self.assertIn(
                    'data-manacost-delayed-src="https://www.googletagmanager.com/gtag/js?id=test"',
                    result,
                )
                self.assertNotIn(
                    '<script src="https://www.googletagmanager.com/gtag/js?id=test">',
                    result,
                )
                self.assertIn(
                    '<script src="/wp-includes/js/jquery/jquery.min.js"></script>',
                    result,
                )
                self.assertIn('id="manacost-third-party-gate"', result)

    def test_partner_ad_scripts_are_not_held_by_analytics_gate(self) -> None:
        ads = (
            '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>'
            '<script async id="ai-functions" src="https://hs-manacost.ru/wp-content/plugins/'
            'ad-inserter/js/ai-functions.min.js?ver=2.8.15"></script>'
        )
        result = self.render_page(page=PAGE.replace('</body>', ads + '</body>'))

        self.assertIn('<script async src="https://pagead2.googlesyndication.com/', result)
        self.assertIn('<script async id="ai-functions" src="https://hs-manacost.ru/', result)
        self.assertNotIn('data-manacost-delayed-src="https://pagead2.googlesyndication.com/', result)
        self.assertNotIn('data-manacost-delayed-src="https://hs-manacost.ru/wp-content/plugins/ad-inserter/', result)

    def test_category_delays_inline_metrica_without_delaying_ads(self) -> None:
        metrica = (
            '<script>if (w.opera == "[object Opera]") {'
            'd.addEventListener("DOMContentLoaded", f, false);'
            '} else { f(); }'
            's.src = "https://cdn.jsdelivr.net/npm/yandex-metrica-watch/watch.js";'
            '</script>'
        )
        page = PAGE.replace('</body>', metrica + '</body>')

        result = self.render_page(
            page=page,
            request_uri='/category/gajdy-hearthstone/',
            front_page=False,
        )

        self.assertIn('setTimeout(f, 15000)', result)
        self.assertNotIn('else { f(); }', result)

    def test_authenticated_or_admin_requests_still_bypass_optimizer(self) -> None:
        for request in ({"logged_in": True}, {"admin": True}):
            with self.subTest(**request):
                self.assertEqual(self.render_page(**request), PAGE)

    def test_request_sanitization_optimizes_public_pages_but_not_account(self) -> None:
        for request_uri in ("/", "/?search=meta", "/?search=meta%20decks"):
            with self.subTest(request_uri=request_uri):
                self.assertIn(
                    'name="manacost-perf-active"',
                    self.render_page(request_uri=request_uri, front_page=False),
                )
        self.assertIn(
            'name="manacost-perf-active"',
            self.render_page(request_uri="/article/?search=meta", front_page=False),
        )
        self.assertEqual(self.render_page(request_uri="/account/", front_page=False), PAGE)

    def test_sanitized_user_agent_retains_mobile_fallback(self) -> None:
        card = (
            '<div class="td-big-grid-flex"><a href="/current/" class="td-image-wrap" title="Статья">'
            '<span data-bg="https://hs-manacost.ru/wp-content/uploads/2026/09/current-1068x542.jpg" '
            'class="entry-thumb td-thumb-css rocket-lazyload" style=""></span></a></div>'
        )
        page = PAGE.replace("</body>", card + "</body>")
        desktop = self.render_page(page=page)
        mobile = self.render_page(page=page, user_agent="Mozilla/5.0 (iPhone; Mobile)")
        self.assertIn('href="https://hs-manacost.ru/wp-content/uploads/2026/09/current-1068x542.jpg"', desktop)
        self.assertIn('href="https://hs-manacost.ru/wp-content/uploads/2026/09/current-696x353.jpg"', mobile)
        self.assertIn('<picture class="manacost-lcp-picture">', mobile)
        self.assertEqual(mobile, self.render_page(page=page, mobile=True))


if __name__ == "__main__":
    unittest.main()
