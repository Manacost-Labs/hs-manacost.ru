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

    def test_guest_home_preserves_font_loading_and_theme_typography(self) -> None:
        for mobile in (False, True):
            for legacy_font_trim in (None, True):
                with self.subTest(mobile=mobile, legacy_font_trim=legacy_font_trim):
                    result = self.render_page(
                        mobile=mobile, legacy_font_trim=legacy_font_trim
                    )
                    for markup in FONT_MARKUP:
                        self.assertIn(markup, result)
                    self.assertNotIn('id="manacost-font-trim"', result)
                    self.assertNotIn('font-family:Arial', result)
                    self.assertIn('name="manacost-perf-active"', result)

    def test_font_repair_keeps_other_default_optimizations_active(self) -> None:
        for mobile in (False, True):
            with self.subTest(mobile=mobile):
                result = self.render_page(mobile=mobile)
                self.assertIn('id="manacost-mobile-lite-critical"', result)
                self.assertIn('class="banner-rotator manacost-banner-rotator"', result)
                self.assertIn('class="manacost-banner-slide manacost-banner-slide-1"', result)
                self.assertIn('class="manacost-banner-slide manacost-banner-slide-2"', result)
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

    def test_authenticated_or_admin_requests_still_bypass_optimizer(self) -> None:
        for request in ({"logged_in": True}, {"admin": True}):
            with self.subTest(**request):
                self.assertEqual(self.render_page(**request), PAGE)

    def test_request_sanitization_preserves_home_and_article_routing(self) -> None:
        for request_uri in ("/", "/?search=meta", "/?search=meta%20decks"):
            with self.subTest(request_uri=request_uri):
                self.assertIn(
                    'name="manacost-perf-active"',
                    self.render_page(request_uri=request_uri, front_page=False),
                )
        self.assertEqual(
            self.render_page(request_uri="/article/?search=meta", front_page=False),
            PAGE,
        )

    def test_sanitized_user_agent_retains_mobile_fallback(self) -> None:
        card = (
            '<a href="/budzhetnye-kolody-hearthstone-kataklizm/" class="td-image-wrap">'
            '<span class="entry-thumb td-thumb-css"></span></a>'
        )
        page = PAGE.replace("</body>", card + "</body>")
        desktop = self.render_page(page=page)
        mobile = self.render_page(page=page, user_agent="Mozilla/5.0 (iPhone; Mobile)")
        self.assertIn(card, desktop)
        self.assertNotIn(card, mobile)
        self.assertIn('<picture class="manacost-lcp-picture">', mobile)
        self.assertEqual(mobile, self.render_page(page=page, mobile=True))


if __name__ == "__main__":
    unittest.main()
