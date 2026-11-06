from __future__ import annotations

import json
import re
import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/manacost-partner-placements.php"
STYLESHEET = (
    ROOT
    / "wordpress/mu-plugins/manacost-partner-placements/partner-placements.css"
)
PHP_BINARY = shutil.which("php") or "/usr/bin/php"


class PartnerPlacementsTest(unittest.TestCase):
    def run_plugin(
        self,
        *,
        host: str = "hs-manacost.ru",
        admin: bool = False,
        feed: bool = False,
        preview: bool = False,
        rest: bool = False,
        enabled: bool = True,
        local: bool = False,
        singular_post: bool = False,
    ) -> dict[str, object]:
        feature_flag = (
            "define('MANACOST_PARTNER_PLACEMENTS_ENABLED', false);"
            if not enabled
            else ""
        )
        script = f"""
        define('ABSPATH', '/');
        {feature_flag}
        $actions = [];
        $filters = [];
        $styles = [];
        $scripts = [];
        $conditional_calls = 0;
        $admin = {json.dumps(admin)};
        $feed = {json.dumps(feed)};
        $preview = {json.dumps(preview)};
        $rest = {json.dumps(rest)};
        $environment = {json.dumps("local" if local else "production")};
        $singular_post = {json.dumps(singular_post)};
        $_SERVER['HTTP_HOST'] = {json.dumps(host)};

        function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {{
            $GLOBALS['actions'][$hook][] = [$callback, $priority, $accepted_args];
        }}
        function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {{
            $GLOBALS['filters'][$hook][] = [$callback, $priority, $accepted_args];
        }}
        function is_admin() {{ return $GLOBALS['admin']; }}
        function wp_doing_ajax() {{ return false; }}
        function is_feed() {{ $GLOBALS['conditional_calls']++; return $GLOBALS['feed']; }}
        function is_preview() {{ $GLOBALS['conditional_calls']++; return $GLOBALS['preview']; }}
        function is_robots() {{ $GLOBALS['conditional_calls']++; return false; }}
        function is_trackback() {{ $GLOBALS['conditional_calls']++; return false; }}
        function home_url($path = '') {{ return 'https://' . $_SERVER['HTTP_HOST'] . $path; }}
        function is_singular($type = '') {{ return $GLOBALS['singular_post'] && ($type === '' || $type === 'post'); }}
        function in_the_loop() {{ return true; }}
        function is_main_query() {{ return true; }}
        function wp_get_environment_type() {{ return $GLOBALS['environment']; }}
        function wp_parse_url($url, $component = -1) {{ return parse_url($url, $component); }}
        function wp_unslash($value) {{ return stripslashes($value); }}
        function sanitize_text_field($value) {{ return trim(strip_tags($value)); }}
        function plugin_dir_url($file) {{ return '/wp-content/mu-plugins/'; }}
        function wp_enqueue_style($handle, $src, $dependencies = [], $version = false) {{
            $GLOBALS['styles'][$handle] = [$src, $dependencies, $version];
        }}
        function wp_enqueue_script($handle, $src, $dependencies, $version, $args) {{
            $GLOBALS['scripts'][$handle] = [$src, $dependencies, $version, $args];
        }}
        function wp_script_add_data($handle, $key, $value) {{}}
        function esc_url($value) {{ return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }}
        function esc_attr($value) {{ return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }}
        function esc_html($value) {{ return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }}

        if ($rest) {{ define('REST_REQUEST', true); }}
        require {json.dumps(str(PLUGIN))};

        $optimizer_exclusions = ['keep-existing-rule'];
        foreach ($filters['rocket_delay_js_exclusions'] ?? [] as $entry) {{
            $optimizer_exclusions = call_user_func($entry[0], $optimizer_exclusions);
        }}
        $legacy_header = [
            'td_ads' => [
                'header' => [
                    'ad_code' => '<div class="banner-rotator"><a href="https://plrk.co/p/hsmanacostru1708">Playerok</a><a href="https://sirus.cc/hsmanacost">Sirus</a></div>',
                ],
            ],
        ];
        $network_header = [
            'td_ads' => [
                'header' => [
                    'ad_code' => '<ins class="adsbygoogle"></ins>',
                ],
            ],
        ];
        foreach ($filters['option_td_011'] ?? [] as $entry) {{
            $legacy_header = call_user_func($entry[0], $legacy_header);
            $network_header = call_user_func($entry[0], $network_header);
        }}

        $ad_inserter_settings = [
            2 => [
                'code' => '<a href="https://sirus.cc/hsmanacost"><img src="/wp-content/uploads/2026/03/728h90.png"></a>',
                'display_type' => '1',
            ],
            3 => [
                'code' => '<div id="yandex_rtb_R-A-example"></div>',
                'display_type' => '1',
            ],
        ];
        $ad_inserter = ':AI:' . base64_encode(serialize($ad_inserter_settings));
        foreach ($filters['option_ad_inserter'] ?? [] as $entry) {{
            $ad_inserter = call_user_func($entry[0], $ad_inserter);
        }}
        $filter_conditional_calls = $conditional_calls;
        $decoded_ad_inserter = unserialize(
            base64_decode(substr($ad_inserter, 4), true),
            ['allowed_classes' => false]
        );

        foreach ($actions['wp_enqueue_scripts'] ?? [] as $entry) {{
            call_user_func($entry[0]);
        }}
        ob_start();
        foreach ($actions['td_wp_booster_after_header'] ?? [] as $entry) {{
            call_user_func($entry[0]);
            call_user_func($entry[0]);
        }}
        $markup = ob_get_clean();
        $content = '<p>Article body</p>';
        foreach ($filters['the_content'] ?? [] as $entry) {{
            $content = call_user_func($entry[0], $content);
        }}

        echo json_encode([
            'actions' => array_keys($actions),
            'filters' => array_keys($filters),
            'styles' => $styles,
            'scripts' => $scripts,
            'optimizer_exclusions' => $optimizer_exclusions,
            'legacy_header' => $legacy_header,
            'network_header' => $network_header,
            'ad_inserter' => $decoded_ad_inserter,
            'filter_conditional_calls' => $filter_conditional_calls,
            'markup' => $markup,
            'content' => $content,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        """
        completed = subprocess.run(
            [PHP_BINARY, "-r", script],
            check=False,
            capture_output=True,
            text=True,
        )
        self.assertEqual(completed.returncode, 0, completed.stderr)
        return json.loads(completed.stdout)

    def test_registers_supported_public_hooks(self) -> None:
        result = self.run_plugin()

        self.assertIn("wp_enqueue_scripts", result["actions"])
        self.assertIn("td_wp_booster_after_header", result["actions"])
        self.assertIn("template_redirect", result["actions"])
        self.assertIn("option_td_011", result["filters"])
        self.assertIn("option_ad_inserter", result["filters"])
        self.assertIn("the_content", result["filters"])
        self.assertEqual(0, result["filter_conditional_calls"])

    def test_renders_transparent_first_party_placements_once_on_both_domains(self) -> None:
        for host in ("hs-manacost.ru", "hs-manacost.com"):
            with self.subTest(host=host):
                result = self.run_plugin(host=host)
                markup = str(result["markup"])

                self.assertEqual(1, markup.count('class="site-partnership"'))
                self.assertIn('aria-label="Партнёры сайта"', markup)
                self.assertNotIn('class="site-partnership__label"', markup)
                self.assertIn('href="https://bit.ly/hsmanacostru2110"', markup)
                self.assertNotIn('href="https://plrk.co/p/hsmanacostru1708"', markup)
                self.assertIn('class="site-masthead-mark"', markup)
                self.assertIn(f'href="https://{host}/"', markup)
                self.assertIn('src="/wp-content/uploads/2026/01/unnamed.png"', markup)
                self.assertIn('href="/site-link/secondary/"', markup)
                self.assertIn('rel="sponsored noopener noreferrer"', markup)
                self.assertIn(
                    'src="/site-media/primary-mark/"', markup
                )
                self.assertIn(
                    'src="/site-media/secondary-mark/"', markup
                )
                self.assertIn(
                    'src="/site-media/secondary-mark/" width="729" height="90"',
                    markup,
                )
                self.assertNotIn('width="728" height="90"', markup)
                masthead = re.search(r'<a class="site-masthead-mark".*?<img[^>]*>', markup, re.S).group(0)
                self.assertNotIn("fetchpriority", masthead)
                primary = re.search(r'<img src="/site-media/primary-mark/"[^>]*>', markup).group(0)
                self.assertIn('fetchpriority="high"', primary)
                self.assertIn('data-no-lazy="1"', primary)
                self.assertNotIn("sirus.cc", markup)
                self.assertNotIn("728h90", markup)
                self.assertNotIn("td-a-rec", markup)
                self.assertNotIn("banner-rotator", markup)
                self.assertIn("manacost-partner-placements", result["styles"])
                self.assertEqual(
                    "1.1.0",
                    result["styles"]["manacost-partner-placements"][2],
                )

        local_result = self.run_plugin(host="127.0.0.1:8888", local=True)
        self.assertIn('class="site-partnership"', local_result["markup"])
        self.assertIn("wp_body_open", local_result["actions"])

    def test_optimizer_preserves_controller_and_dynamic_state_styles(self) -> None:
        result = self.run_plugin()
        for hook in ("rocket_delay_js_exclusions", "perfmatters_delay_js_exclusions", "rocket_rucss_external_exclusions", "perfmatters_rucss_excluded_stylesheets"):
            self.assertIn(hook, result["filters"])
        self.assertEqual([
            "keep-existing-rule",
            "/wp-content/mu-plugins/manacost-partner-placements/partner-placements.js",
            "/wp-content/mu-plugins/manacost-partner-placements/partner-placements.css",
        ], result["optimizer_exclusions"])

    def test_header_has_image_failure_fallback_and_keyboard_controls(self) -> None:
        result = self.run_plugin()
        markup = str(result["markup"])
        self.assertIn('class="site-partnership__fallback">Playerok</span>', markup)
        self.assertIn('class="site-partnership__fallback">Sirus</span>', markup)
        self.assertIn('aria-label="Следующий партнёр"', markup)
        self.assertIn('aria-label="Приостановить смену баннеров"', markup)
        self.assertEqual(2, markup.count('data-no-lazy="1"'))
        self.assertEqual(2, markup.count('loading="eager"'))
        self.assertNotIn('728x90', markup)
        self.assertEqual("1.1.0", result["scripts"]["manacost-partner-placements"][2])

    def test_suppresses_only_the_known_legacy_direct_placements(self) -> None:
        result = self.run_plugin()

        self.assertEqual(
            "", result["legacy_header"]["td_ads"]["header"]["ad_code"]
        )
        self.assertEqual(
            '<ins class="adsbygoogle"></ins>',
            result["network_header"]["td_ads"]["header"]["ad_code"],
        )
        self.assertEqual("", result["ad_inserter"]["2"]["code"])
        self.assertIn("yandex_rtb", result["ad_inserter"]["3"]["code"])

    def test_places_sirus_before_the_main_single_article_content(self) -> None:
        result = self.run_plugin(singular_post=True)
        content = str(result["content"])

        self.assertTrue(content.startswith('<aside class="site-opening-note"'))
        self.assertIn('aria-label="Реклама: Sirus"', content)
        self.assertNotIn('class="site-opening-note__label"', content)
        self.assertIn('href="/site-link/secondary/"', content)
        self.assertIn('src="/site-media/secondary-mark/"', content)
        self.assertIn(
            'src="/site-media/secondary-mark/" width="729" height="90"',
            content,
        )
        self.assertNotIn('width="728" height="90"', content)
        self.assertIn('rel="sponsored noopener noreferrer"', content)
        self.assertTrue(content.endswith("<p>Article body</p>"))

        archive_result = self.run_plugin(singular_post=False)
        self.assertEqual("<p>Article body</p>", archive_result["content"])

    def test_first_party_image_route_overrides_wordpress_404_status(self) -> None:
        source = PLUGIN.read_text(encoding="utf-8")

        success_status = source.index("status_header( 200 );")
        content_type = source.index("header( 'Content-Type: image/webp' );")
        body = source.index("echo $body")
        self.assertLess(success_status, content_type)
        self.assertLess(content_type, body)

    def test_fixed_image_routes_validate_upstream_and_never_accept_query_urls(self) -> None:
        for route, filename in (("primary", "2026/07/728x90.jpg.webp"), ("secondary", "2026/03/728h90.png.webp")):
            for invalid in (False, True):
                with self.subTest(route=route, invalid=invalid):
                    script = f"""
                    define('ABSPATH', '/');
                    $_SERVER['HTTP_HOST'] = 'hs-manacost.ru';
                    $_SERVER['REQUEST_URI'] = '/site-media/{route}-mark/?url=https://example.invalid/';
                    function add_action(...$args) {{}}
                    function add_filter(...$args) {{}}
                    function is_admin() {{ return false; }}
                    function wp_doing_ajax() {{ return false; }}
                    function wp_unslash($value) {{ return $value; }}
                    function sanitize_text_field($value) {{ return $value; }}
                    function wp_parse_url($value, $part) {{ return parse_url($value, $part); }}
                    function status_header($status) {{ fwrite(STDERR, "status:$status"); }}
                    function wp_safe_remote_get($url, $args) {{
                        fwrite(STDERR, $url . json_encode($args));
                        return [];
                    }}
                    function is_wp_error($value) {{ return false; }}
                    function wp_remote_retrieve_response_code($value) {{ return 200; }}
                    function wp_remote_retrieve_header($value, $name) {{ return {json.dumps('text/html' if invalid else 'image/webp')}; }}
                    function wp_remote_retrieve_body($value) {{ return str_repeat('x', 2048); }}
                    require {json.dumps(str(PLUGIN))};
                    Manacost_Partner_Placements::maybe_serve_first_party_route();
                    """
                    result = subprocess.run([PHP_BINARY, "-r", script], capture_output=True, text=True, check=False)
                    self.assertEqual(0, result.returncode, result.stderr)
                    self.assertIn("https://hs-manacost.ru/wp-content/uploads/" + filename, result.stderr)
                    self.assertNotIn("example.invalid", result.stderr)
                    self.assertIn('"redirection":0', result.stderr)
                    self.assertIn('"limit_response_size":1048576', result.stderr)
                    self.assertIn("status:503" if invalid else "status:200", result.stderr)
                    self.assertEqual("Asset temporarily unavailable" if invalid else "x" * 2048, result.stdout)

    def test_skips_non_public_requests_and_unknown_hosts(self) -> None:
        contexts = (
            {"admin": True},
            {"feed": True},
            {"preview": True},
            {"rest": True},
            {"host": "example.com"},
            {"enabled": False},
        )
        for context in contexts:
            with self.subTest(**context):
                result = self.run_plugin(**context)
                self.assertEqual("", result["markup"])
                self.assertEqual([], result["styles"])
                if not context.get("feed") and not context.get("preview"):
                    self.assertIn(
                        "banner-rotator",
                        result["legacy_header"]["td_ads"]["header"]["ad_code"],
                    )
                    self.assertIn("sirus.cc", result["ad_inserter"]["2"]["code"])

    def test_styles_place_the_rotator_in_the_header_without_a_white_strip(self) -> None:
        css = STYLESHEET.read_text(encoding="utf-8")

        self.assertIn(".site-partnership__item:focus-visible", css)
        self.assertIn("position: absolute", css)
        self.assertIn("background: transparent", css)
        self.assertIn("border: 0", css)
        self.assertIn("transition: opacity 700ms", css)
        self.assertNotIn("@keyframes site-partnership", css)
        self.assertIn("aspect-ratio: 729 / 90", css)
        self.assertIn("@media (min-width: 768px)", css)
        self.assertIn("@media (max-width: 767px)", css)
        self.assertIn("position: relative", css)
        self.assertIn("background: #002844", css)
        self.assertNotIn("aspect-ratio: 5 / 1", css)
        self.assertNotIn("max-block-size: 70px", css)
        self.assertNotIn("object-fit: cover", css)
        self.assertNotIn("object-position: left center", css)
        self.assertNotIn("#f7f9fb", css)
        self.assertNotIn("grid-template-columns", css)
        self.assertNotIn("transition: all", css)

    def test_styles_reserve_the_desktop_header_when_ad_filters_hide_the_logo_row(self) -> None:
        css = STYLESHEET.read_text(encoding="utf-8")

        header_selector = (
            "#td-outer-wrap:has(> .site-partnership) > .tdc-header-wrap "
            "> .td-header-wrap.td-header-style-1"
        )
        menu_selector = f"{header_selector} > .td-header-menu-wrap-full"

        self.assertIn(header_selector, css)
        self.assertIn(menu_selector, css)
        self.assertIn("min-height: 246px", css)
        self.assertIn("min-height: 250px", css)
        self.assertIn("inset-block-end: 0", css)
        self.assertIn("inset-inline: 0", css)
        self.assertIn(".site-masthead-mark", css)
        self.assertIn(".site-opening-note", css)
        self.assertNotIn(".site-partnership__label", css)
        self.assertNotIn(".site-opening-note__label", css)
        self.assertGreaterEqual(css.count("border-radius: 10px"), 2)
        self.assertIn("box-shadow: 0 10px 28px rgba(2, 8, 18, 0.28)", css)


if __name__ == "__main__":
    unittest.main()
