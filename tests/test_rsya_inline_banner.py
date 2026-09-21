from __future__ import annotations

import json
import re
import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/manacost-rsya-inline.php"
EDITOR_PLUGIN = ROOT / "wordpress/mu-plugins/manacost-rsya-inline/editor.js"
PUBLIC_PROFILE = ROOT / "wordpress/mu-plugins/hs-manacost-reader/public-profile.php"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"
NODE_BINARY = shutil.which("node") or "/usr/bin/node"
RECENT_SLUG = "kvest-zhrecz-odna-iz-luchshih-kolod-v-mete-ametistovoj-kreposti"
FIRST_ENABLED_POST_GMT = "2026-08-31 09:00:39"
INTRO_BLOCK_ID = "R-A-16113237-6"
FOOTER_BLOCK_ID = "R-A-16113237-5"
FLOOR_BLOCK_ID = "R-A-16113237-7"
EDITOR_BANNER_BLOCK_ID = "R-A-16113237-12"
SIDEBAR_BLOCK_ID = "R-A-16113237-13"
SIDEBAR_INSERT_BEFORE_WIDGET = "td_block_8_widget-9"


class RsyaInlineBannerTest(unittest.TestCase):
    def render_result(
        self,
        *,
        slug: str = RECENT_SLUG,
        published_at: str = "2026-09-07 14:31:11",
        status: str = "publish",
        logged_in: bool = False,
        admin: bool = False,
        singular: bool = True,
        post_type: str = "post",
        not_found: bool = False,
        account_request: bool = False,
        content_in_loop: bool = True,
        content_main_query: bool = True,
        rsya_enabled: bool = True,
        public_profile: bool = False,
        sidebar_ad: bool = False,
        sidebar_bottom_only: bool = False,
        content: str | None = None,
    ) -> dict:
        content = content or (
            "<p><img src=\"cover.jpg\" alt=\"\"></p>"
            "<p>Первый текстовый абзац.</p>"
            "<p>Второй текстовый абзац.</p>"
            "<p>Третий текстовый абзац.</p>"
            "<h2>Основной раздел</h2><p>Продолжение материала.</p>"
            "<div class=\"su-note\"><p><a href=\"https://t.me/manacost_ru\">"
            "t.me/manacost_ru</a></p></div>"
        )
        rsya_flag = "" if rsya_enabled else "define('MANACOST_RSYA_INLINE_ENABLED', false);"
        script = f"""
        define('ABSPATH', '/');
        {rsya_flag}
        class WP_Post {{
            public string $post_name;
            public string $post_date_gmt;
            public string $post_status;
            public string $post_content;
            public function __construct($slug, $published_at, $status, $post_content) {{
                $this->post_name = $slug;
                $this->post_date_gmt = $published_at;
                $this->post_status = $status;
                $this->post_content = $post_content;
            }}
        }}
        $phase = 'head';
        $public_profile = {json.dumps(public_profile)};
        $sidebar_widgets = json_decode({json.dumps(json.dumps({"td-default": [SIDEBAR_INSERT_BEFORE_WIDGET] if sidebar_ad else (["td_block_popular_categories_widget-5"] if sidebar_bottom_only else [])}))}, true);
        $actions = [];
        $filters = [];
        $shortcodes = [];
        $scripts = [];
        $inline_scripts = [];
        $script_data = [];
        function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {{
            $GLOBALS['actions'][$tag][] = [$callback, $priority, $accepted_args];
        }}
        function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {{
            $GLOBALS['filters'][$tag][] = [$callback, $priority, $accepted_args];
        }}
        function add_shortcode($tag, $callback) {{ $GLOBALS['shortcodes'][$tag] = $callback; }}
        function is_admin() {{ return {json.dumps(admin)}; }}
        function is_singular($type = null) {{
            if (!{json.dumps(singular)}) {{ return false; }}
            return null === $type || {json.dumps(post_type)} === $type;
        }}
        function is_404() {{ return {json.dumps(not_found)}; }}
        function in_the_loop() {{ return 'content' === $GLOBALS['phase'] ? {json.dumps(content_in_loop)} : false; }}
        function is_main_query() {{ return 'content' === $GLOBALS['phase'] ? {json.dumps(content_main_query)} : true; }}
        function is_feed() {{ return false; }}
        function is_preview() {{ return false; }}
        function wp_doing_ajax() {{ return false; }}
        function is_user_logged_in() {{ return {json.dumps(logged_in)}; }}
        function hs_reader_public_profile_request() {{ return $GLOBALS['public_profile']; }}
        function hs_manacost_reader_is_account_request() {{ return {json.dumps(account_request)}; }}
        function wp_get_sidebars_widgets() {{ return $GLOBALS['sidebar_widgets']; }}
        function get_queried_object() {{ return new WP_Post({json.dumps(slug)}, {json.dumps(published_at)}, {json.dumps(status)}, {json.dumps(content, ensure_ascii=False)}); }}
        function has_shortcode($content, $tag) {{ return false !== strpos($content, '[' . $tag); }}
        function wp_strip_all_tags($value) {{ return trim(strip_tags($value)); }}
        function sanitize_key($value) {{ return strtolower(preg_replace('/[^a-z0-9_-]/', '', $value)); }}
        function wp_json_encode($value) {{ return json_encode($value); }}
        function esc_attr($value) {{ return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }}
		function esc_js($value) {{ return $value; }}
        function esc_url($value) {{ return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }}
        function get_privacy_policy_url() {{ return 'https://hs-manacost.ru/privacy-policy/'; }}
        function content_url($path = '') {{ return 'https://hs-manacost.ru/wp-content/' . ltrim($path, '/'); }}
        function wp_register_script($handle, $source = '', $dependencies = [], $version = false, $args = false) {{
            $GLOBALS['scripts'][$handle] = [$source, $dependencies, $version, $args];
        }}
        function wp_enqueue_script($handle, $source = '', $dependencies = [], $version = false, $args = false) {{
            if ($source !== '' || !isset($GLOBALS['scripts'][$handle])) {{
                $GLOBALS['scripts'][$handle] = [$source, $dependencies, $version, $args];
            }}
        }}
        function wp_add_inline_script($handle, $script, $position = 'after') {{
            $GLOBALS['inline_scripts'][] = [$handle, $script, $position];
        }}
        function wp_script_add_data($handle, $key, $value) {{
            $GLOBALS['script_data'][$handle][$key] = $value;
            return true;
        }}
        function apply_test_filter($tag, $value) {{
            foreach ($GLOBALS['filters'][$tag] ?? [] as $registered) {{
                $value = call_user_func($registered[0], $value);
            }}
            return $value;
        }}
        function run_test_action($tag, ...$args) {{
            foreach ($GLOBALS['actions'][$tag] ?? [] as $registered) {{
                call_user_func_array($registered[0], array_slice($args, 0, $registered[2]));
            }}
        }}
        require {json.dumps(str(PLUGIN))};
        foreach ($actions['wp_enqueue_scripts'] ?? [] as $registered) {{ call_user_func($registered[0]); }}
        ob_start();
        foreach ($actions['wp_head'] ?? [] as $registered) {{ call_user_func($registered[0]); }}
        $head = ob_get_clean();
        ob_start();
        foreach ($actions['wp_footer'] ?? [] as $registered) {{ call_user_func($registered[0]); }}
        $footer = ob_get_clean();
        $phase = 'content';
        $manual_banner = call_user_func($shortcodes['manacost_rsya'], ['format' => 'banner']);
        $manual_feed = call_user_func($shortcodes['manacost_rsya'], ['format' => 'feed']);
        $profile_banner = Manacost_Rsya_Inline_Banner::render_public_profile_banner();

        $sidebar_params = apply_test_filter('dynamic_sidebar_params', [[
            'id' => 'td-default',
            'widget_id' => {json.dumps(SIDEBAR_INSERT_BEFORE_WIDGET)},
            'before_widget' => '<aside class="widget">',
        ]]);
        $sidebar_params_second = apply_test_filter('dynamic_sidebar_params', [[
            'id' => 'td-default',
            'widget_id' => {json.dumps(SIDEBAR_INSERT_BEFORE_WIDGET)},
            'before_widget' => '<aside class="widget">',
        ]]);
        ob_start();
        run_test_action('dynamic_sidebar_after', 'td-default', true);
        $sidebar_footer = ob_get_clean();
        ob_start();
        run_test_action('dynamic_sidebar_after', 'td-default', true);
        $sidebar_footer_second = ob_get_clean();
        echo json_encode([
            'content' => apply_test_filter('the_content', {json.dumps(content, ensure_ascii=False)}),
            'head' => $head,
            'footer' => $footer,
            'scripts' => $scripts,
            'inline_scripts' => $inline_scripts,
            'manual_banner' => $manual_banner,
            'manual_feed' => $manual_feed,
            'profile_banner' => $profile_banner,
            'sidebar_params' => $sidebar_params,
            'sidebar_params_second' => $sidebar_params_second,
            'sidebar_footer' => $sidebar_footer,
            'sidebar_footer_second' => $sidebar_footer_second,
        ], JSON_UNESCAPED_UNICODE);
        """
        completed = subprocess.run(
            [PHP_BINARY, "-r", script],
            check=False,
            capture_output=True,
            text=True,
        )
        self.assertEqual(completed.returncode, 0, completed.stderr)
        return json.loads(completed.stdout)

    def test_enabled_article_gates_yandex_before_placing_banners_after_intro(self) -> None:
        result = self.render_result()

        self.assertEqual(
            result["scripts"]["manacost-rsya-gate"],
            ["", [], INTRO_BLOCK_ID, {"strategy": "async"}],
        )
        self.assertEqual(result["inline_scripts"][0][2], "before")
        self.assertIn("window.yaContextCb = window.yaContextCb || []", result["inline_scripts"][0][1])
        self.assertIn('/reader-api/v1/ad-status', result["inline_scripts"][0][1])
        self.assertIn('status.adFree !== false', result["inline_scripts"][0][1])
        self.assertIn('loader.src = "https://yandex.ru/ads/system/context.js"', result["inline_scripts"][0][1])
        self.assertIn('credentials: "same-origin"', result["inline_scripts"][0][1])

        content = result["content"]
        self.assertEqual(content.count(f'id="yandex_rtb_{INTRO_BLOCK_ID}"'), 1)
        self.assertEqual(content.count('class="manacost-rsya-inline manacost-rsya-inline--banner"'), 2)
        self.assertIn(f'id="yandex_rtb_{FOOTER_BLOCK_ID}-after-telegram"', content)
        self.assertIn('data-manacost-rsya-unit', content)
        self.assertNotIn("manacost-rsya-consent", content)
        self.assertNotIn("Показать рекламу", content)
        self.assertIn("Ya.Context.AdvManager.render", content)
        self.assertIn(FLOOR_BLOCK_ID, result["footer"])
        self.assertIn('"type": "floorAd"', result["footer"])
        self.assertIn('"platform": "desktop"', result["footer"])
        self.assertLess(
            content.index("Третий текстовый абзац."),
            content.index(f'id="yandex_rtb_{INTRO_BLOCK_ID}"'),
        )
        self.assertLess(
            content.index(f'id="yandex_rtb_{INTRO_BLOCK_ID}"'),
            content.index("Основной раздел"),
        )
        self.assertLess(
            content.index("t.me/manacost_ru"),
            content.index(f'id="yandex_rtb_{FOOTER_BLOCK_ID}-after-telegram"'),
        )

    def test_all_published_articles_get_automatic_placements(self) -> None:
        for published_at in ("2020-01-01 00:00:00", FIRST_ENABLED_POST_GMT, "2026-10-01 00:00:00"):
            with self.subTest(published_at=published_at):
                result = self.render_result(published_at=published_at)
                self.assertEqual(result["content"].count('data-manacost-rsya-slot='), 2)
                self.assertIn("manacost-rsya-gate", result["scripts"])
                self.assertIn(FLOOR_BLOCK_ID, result["footer"])

    def test_public_non_article_pages_get_the_loader_and_floor_ad(self) -> None:
        for kwargs in ({"singular": False}, {"post_type": "page"}):
            with self.subTest(**kwargs):
                result = self.render_result(**kwargs)
                self.assertIn("manacost-rsya-gate", result["scripts"])
                self.assertIn(FLOOR_BLOCK_ID, result["footer"])
                self.assertNotIn("data-manacost-rsya-slot=", result["content"])

    def test_editor_shortcodes_render_only_supported_compact_manual_formats(self) -> None:
        result = self.render_result(published_at="2026-10-01 00:00:00")

        self.assertIn(EDITOR_BANNER_BLOCK_ID, result["manual_banner"])
        self.assertIn('data-manacost-rsya-slot="editor-banner"', result["manual_banner"])
        self.assertIn(EDITOR_BANNER_BLOCK_ID, result["manual_feed"])
        self.assertIn('data-manacost-rsya-slot="editor-horizontal"', result["manual_feed"])
        self.assertNotIn('"type": "feed"', result["manual_feed"])
        self.assertIn('manacost-rsya-inline--banner', result["manual_feed"])
        self.assertIn('class="manacost-rsya-inline__label">Реклама</p>', result["manual_banner"])

    def test_explicit_shortcode_coexists_with_automatic_article_placements(self) -> None:
        result = self.render_result(
            published_at="2026-10-01 00:00:00",
            content='<p>Текст.</p>[manacost_rsya format="banner"]',
        )

        self.assertIn("manacost-rsya-gate", result["scripts"])
        self.assertEqual(result["content"].count('data-manacost-rsya-slot='), 2)
        self.assertIn(FLOOR_BLOCK_ID, result["footer"])

    def test_public_profile_has_one_viewer_gated_banner_but_private_pages_do_not(self) -> None:
        public = self.render_result(
            published_at="2026-10-01 00:00:00",
            public_profile=True,
        )
        private = self.render_result(published_at="2026-10-01 00:00:00")

        self.assertIn("R-A-16113237-13", public["profile_banner"])
        self.assertIn('data-manacost-rsya-slot="public-profile"', public["profile_banner"])
        self.assertIn("manacost-rsya-gate", public["scripts"])
        self.assertEqual(private["profile_banner"], "")
        self.assertIn("render_public_profile_banner", PUBLIC_PROFILE.read_text(encoding="utf-8"))

    def test_article_sidebar_inserts_one_compact_viewer_gated_unit_before_latest_posts(self) -> None:
        result = self.render_result(sidebar_ad=True)

        self.assertIn("manacost-rsya-gate", result["scripts"])
        sidebar_html = result["sidebar_params"][0]["before_widget"]
        self.assertIn(SIDEBAR_BLOCK_ID, sidebar_html)
        self.assertIn('data-manacost-rsya-slot="sidebar"', sidebar_html)
        self.assertIn('manacost-rsya-inline--sidebar', sidebar_html)
        self.assertIn('class="widget"', sidebar_html)
        self.assertEqual(result["sidebar_params_second"][0]["before_widget"], '<aside class="widget">')

        absent = self.render_result(sidebar_ad=False)
        self.assertEqual(absent["sidebar_params"][0]["before_widget"], '<aside class="widget">')

    def test_article_sidebar_appends_one_compact_viewer_gated_unit_after_its_last_widget(self) -> None:
        result = self.render_result(sidebar_ad=True)

        self.assertIn("manacost-rsya-gate", result["scripts"])
        self.assertIn(SIDEBAR_BLOCK_ID, result["sidebar_footer"])
        self.assertIn('data-manacost-rsya-slot="sidebar-bottom"', result["sidebar_footer"])
        self.assertIn('manacost-rsya-inline--sidebar', result["sidebar_footer"])
        self.assertEqual(result["sidebar_footer_second"], "")

        absent = self.render_result(sidebar_ad=False)
        self.assertEqual(absent["sidebar_footer"], "")

        bottom_only = self.render_result(sidebar_bottom_only=True)
        self.assertEqual(bottom_only["sidebar_params"][0]["before_widget"], '<aside class="widget">')
        self.assertIn('data-manacost-rsya-slot="sidebar-bottom"', bottom_only["sidebar_footer"])

    def test_classic_editor_offers_only_compact_manual_banner_controls(self) -> None:
        editor = EDITOR_PLUGIN.read_text(encoding="utf-8")

        self.assertIn('insertContent(\'[manacost_rsya format="\' + format + \'"]\')', editor)
        self.assertIn('text: "Баннер РСЯ"', editor)
        self.assertIn('text: "Горизонтальная лента РСЯ"', editor)
        self.assertIn("editor.addButton", editor)
        self.assertNotIn("fullscreen", editor.lower())
        self.assertNotIn("prebid", editor.lower())

    def test_paid_gate_never_requests_yandex_and_unpaid_gate_loads_once(self) -> None:
        gate = self.render_result()["inline_scripts"][0][1]
        for ad_free, expected_loader in ((True, 0), (False, 1)):
            with self.subTest(ad_free=ad_free):
                node_script = f"""
                const units = [{{ hidden: false }}, {{ hidden: false }}];
                let requested = 0;
                const head = {{ appendChild: loader => {{ requested += 1; loader.onload(); }} }};
                global.window = {{ yaContextCb: [], location: {{ hostname: 'hs-manacost.ru' }}, addEventListener: () => {{}} }};
                global.document = {{
                    querySelectorAll: () => units,
                    createElement: () => ({{}}),
                    head,
                }};
                global.fetch = async (url, options) => {{
                    if (url !== '/reader-api/v1/ad-status' || options.credentials !== 'same-origin' || options.cache !== 'no-store') throw new Error('bad Reader gate request');
                    return {{ ok: true, json: async () => ({{ adFree: {str(ad_free).lower()} }}) }};
                }};
                (async () => {{
                    {gate}
                    const allowed = await window.manacostRsyaReady;
                    process.stdout.write(JSON.stringify({{ allowed, requested, hidden: units.every(unit => unit.hidden) }}));
                }})().catch(error => {{ console.error(error); process.exitCode = 1; }});
                """
                completed = subprocess.run([NODE_BINARY, "-e", node_script], check=False, capture_output=True, text=True)
                self.assertEqual(completed.returncode, 0, completed.stderr)
                result = json.loads(completed.stdout)
                self.assertEqual(result["allowed"], not ad_free)
                self.assertEqual(result["requested"], expected_loader)
                self.assertEqual(result["hidden"], ad_free)

    def test_mirror_loads_yandex_without_calling_the_ru_subscriber_gate(self) -> None:
        gate = self.render_result()["inline_scripts"][0][1]
        node_script = f"""
        const units = [{{ hidden: false }}];
        let requested = 0;
        global.window = {{ yaContextCb: [], location: {{ hostname: 'hs-manacost.com' }}, addEventListener: () => {{}} }};
        global.document = {{
            querySelectorAll: () => units,
            createElement: () => ({{}}),
            head: {{ appendChild: loader => {{ requested += 1; loader.onload(); }} }},
        }};
        global.fetch = async () => {{ throw new Error('mirror must not call the RU subscriber gate'); }};
        (async () => {{
            {gate}
            const allowed = await window.manacostRsyaReady;
            process.stdout.write(JSON.stringify({{ allowed, requested, hidden: units[0].hidden }}));
        }})().catch(error => {{ console.error(error); process.exitCode = 1; }});
        """
        completed = subprocess.run([NODE_BINARY, "-e", node_script], check=False, capture_output=True, text=True)
        self.assertEqual(completed.returncode, 0, completed.stderr)
        self.assertEqual(json.loads(completed.stdout), {"allowed": True, "requested": 1, "hidden": False})

    def test_short_article_keeps_the_intro_placement_separate_from_the_footer(self) -> None:
        result = self.render_result(
            slug="future-short-post",
            published_at="2026-09-07 00:00:00",
            content="<p>Короткий, но полноценный материал.</p>",
        )

        content = result["content"]
        self.assertEqual(content.count(f'id="yandex_rtb_{INTRO_BLOCK_ID}"'), 1)
        self.assertEqual(content.count(f'id="yandex_rtb_{FOOTER_BLOCK_ID}-after-telegram"'), 1)
        self.assertLess(
            content.index("Короткий, но полноценный материал.</p>"),
            content.index(f'id="yandex_rtb_{INTRO_BLOCK_ID}"'),
        )
        self.assertLess(
            content.index(f'id="yandex_rtb_{INTRO_BLOCK_ID}"'),
            content.index(f'id="yandex_rtb_{FOOTER_BLOCK_ID}-after-telegram"'),
        )

    def test_article_without_paragraph_tags_still_has_both_placements(self) -> None:
        result = self.render_result(
            slug="future-embed-only-post",
            published_at="2026-09-07 00:00:00",
            content="<figure><img src=\"cover.jpg\" alt=\"Обложка\"></figure>",
        )

        content = result["content"]
        self.assertEqual(content.count(f'id="yandex_rtb_{INTRO_BLOCK_ID}"'), 1)
        self.assertEqual(content.count(f'id="yandex_rtb_{FOOTER_BLOCK_ID}-after-telegram"'), 1)
        self.assertLess(
            content.index(f'id="yandex_rtb_{INTRO_BLOCK_ID}"'),
            content.index(f'id="yandex_rtb_{FOOTER_BLOCK_ID}-after-telegram"'),
        )

    def test_banner_renders_for_guest_and_authenticated_visitors(self) -> None:
        for logged_in in (False, True):
            with self.subTest(logged_in=logged_in):
                result = self.render_result(logged_in=logged_in)
                self.assertIn(f'id="yandex_rtb_{INTRO_BLOCK_ID}"', result["content"])
                self.assertIn(f'id="yandex_rtb_{FOOTER_BLOCK_ID}-after-telegram"', result["content"])
                self.assertIn(FLOOR_BLOCK_ID, result["footer"])

    def test_existing_single_placement_does_not_prevent_the_missing_placement(self) -> None:
        for block_id in (INTRO_BLOCK_ID, FOOTER_BLOCK_ID):
            with self.subTest(block_id=block_id):
                original = f'<p>Introduction.</p><div id="yandex_rtb_{block_id}"></div><p>Article.</p>'
                content = self.render_result(content=original)["content"]
                for expected_id in (INTRO_BLOCK_ID, FOOTER_BLOCK_ID):
                    self.assertEqual(len(re.findall(f'id="yandex_rtb_{expected_id}(?:-after-telegram)?"', content)), 1)
                self.assertEqual(self.render_result(content=content)["content"], content)

    def test_mentioning_an_ad_identifier_is_not_a_placement(self) -> None:
        result = self.render_result(content=f'<p>Example: yandex_rtb_{INTRO_BLOCK_ID}</p>')
        self.assertEqual(result["content"].count('data-manacost-rsya-slot='), 2)

    def test_ads_do_not_run_on_private_or_non_public_requests(self) -> None:
        for kwargs in (
            {"status": "private"},
            {"admin": True},
            {"not_found": True},
            {"post_type": "page", "account_request": True},
            {"rsya_enabled": False, "singular": False},
        ):
            with self.subTest(**kwargs):
                result = self.render_result(**kwargs)
                self.assertNotIn("yandex_rtb", result["content"])
                self.assertEqual(result["scripts"], [])
                self.assertEqual(result["footer"], "")

    def test_banner_does_not_run_for_secondary_content(self) -> None:
        for kwargs in ({"content_in_loop": False}, {"content_main_query": False}):
            with self.subTest(**kwargs):
                result = self.render_result(**kwargs)
                self.assertNotIn("yandex_rtb", result["content"])
                self.assertIn("manacost-rsya-gate", result["scripts"])

    def test_banner_uses_bounded_responsive_sizes_and_collapses_on_error(self) -> None:
        result = self.render_result()

        self.assertIn("max-width: 970px", result["head"])
        self.assertIn("height: 90px", result["head"])
        self.assertIn("max-width: 320px", result["head"])
        self.assertIn("width: calc(100vw - 32px)", result["head"])
        self.assertIn("height: 100px", result["head"])
        self.assertIn("min-height: 180px", result["head"])
        self.assertIn("border-radius: 16px", result["head"])
        self.assertIn("box-shadow: 0 14px 34px", result["head"])
        self.assertIn("overflow: hidden", result["head"])
        self.assertIn("onError", result["content"])
        self.assertIn("onRender", result["content"])
        self.assertIn("data-manacost-rsya-rendered", result["content"])

    def run_banner_script(self, content: str, scenario: str, script_index: int = 0) -> dict:
        script_matches = re.findall(r"<script>(.*?)</script>", content, re.S)
        self.assertGreater(len(script_matches), script_index)

        actions = {
            "no_fill": "fallback();",
            "error": 'renderOptions.onError({ type: "error" });',
            "warning": 'renderOptions.onError({ type: "warning" });',
            "rendered": "renderOptions.onRender({ product: \"direct\" });",
            "recovered": 'renderOptions.onError({ type: "error" }); renderOptions.onRender({ product: "rtb" });',
            "loader_failure": "",
            "silent": "",
        }
        self.assertIn(scenario, actions)
        loader_failed = "true" if scenario == "loader_failure" else "false"
        node_script = f"""
        const callbacks = [];
        const attributes = {{}};
        const bannerUnit = {{
            hidden: true,
            closest: () => null,
            getAttribute: (name) => attributes[name] || null,
            setAttribute: (name, value) => {{ attributes[name] = value; }},
        }};
        const targetContainer = {{ closest: () => bannerUnit }};
        let renderCalls = 0;
        let renderOptions;
        let fallback;
        global.window = {{
            yaContextCb: callbacks,
            manacostRsyaLoaderFailed: {loader_failed},
            manacostRsyaReady: Promise.resolve(true),
        }};
        global.document = {{ getElementById: () => targetContainer }};
        global.Ya = {{
            Context: {{
                AdvManager: {{
                    render: (options, noFillCallback) => {{
                        renderCalls += 1;
                        renderOptions = options;
                        fallback = noFillCallback;
                    }},
                }},
            }},
        }};
        (async () => {{
            {script_matches[script_index]}
            await Promise.resolve();
            if (!window.manacostRsyaLoaderFailed) {{
                callbacks[0]();
            }}
            {actions[scenario]}
            process.stdout.write(JSON.stringify({{
                hidden: bannerUnit.hidden,
                rendered: attributes["data-manacost-rsya-rendered"] || null,
                renderCalls,
            }}));
        }})().catch(error => {{ console.error(error); process.exitCode = 1; }});
        """
        completed = subprocess.run(
            [NODE_BINARY, "-e", node_script],
            check=False,
            capture_output=True,
            text=True,
        )
        self.assertEqual(completed.returncode, 0, completed.stderr)
        return json.loads(completed.stdout)

    def run_loader_error_handler(self, loader_script: str) -> dict:
        node_script = f"""
        const units = [{{ hidden: false }}];
        let errorHandler;
        global.window = {{
            yaContextCb: [],
            addEventListener: (eventName, handler, useCapture) => {{
                if ("error" === eventName && true === useCapture) {{
                    errorHandler = handler;
                }}
            }},
        }};
        global.document = {{ querySelectorAll: () => units }};
        {loader_script}
        errorHandler({{ target: {{ id: "manacost-rsya-loader-js" }} }});
        process.stdout.write(JSON.stringify({{
            failed: window.manacostRsyaLoaderFailed,
            hidden: units[0].hidden,
        }}));
        """
        completed = subprocess.run(
            [NODE_BINARY, "-e", node_script],
            check=False,
            capture_output=True,
            text=True,
        )
        self.assertEqual(completed.returncode, 0, completed.stderr)
        return json.loads(completed.stdout)

    def test_banner_script_handles_no_fill_error_warning_render_and_loader_failure(self) -> None:
        result = self.render_result()
        content = result["content"]

        self.assertEqual(
            {"hidden": True, "rendered": None, "renderCalls": 1},
            self.run_banner_script(content, "no_fill"),
        )
        self.assertEqual(
            {"hidden": True, "rendered": None, "renderCalls": 1},
            self.run_banner_script(content, "error"),
        )
        self.assertEqual(
            {"hidden": False, "rendered": None, "renderCalls": 1},
            self.run_banner_script(content, "warning"),
        )
        self.assertEqual(
            {"hidden": False, "rendered": None, "renderCalls": 1},
            self.run_banner_script(content, "silent"),
        )
        self.assertEqual(
            {"hidden": False, "rendered": "true", "renderCalls": 1},
            self.run_banner_script(content, "rendered"),
        )
        self.assertEqual(
            {"hidden": True, "rendered": None, "renderCalls": 0},
            self.run_banner_script(content, "loader_failure"),
        )
        self.assertEqual(
            {"failed": True, "hidden": True},
            self.run_loader_error_handler(result["inline_scripts"][0][1]),
        )

    def test_footer_banner_follows_telegram_and_uses_a_unique_container(self) -> None:
        content = self.render_result()["content"]

        self.assertEqual(
            {"hidden": False, "rendered": "true", "renderCalls": 1},
            self.run_banner_script(content, "rendered", script_index=1),
        )

    def test_successful_render_restores_a_previously_hidden_placement(self) -> None:
        content = self.render_result()["content"]
        for index in (0, 1):
            with self.subTest(index=index):
                self.assertEqual(
                    {"hidden": False, "rendered": "true", "renderCalls": 1},
                    self.run_banner_script(content, "recovered", script_index=index),
                )


if __name__ == "__main__":
    unittest.main()
