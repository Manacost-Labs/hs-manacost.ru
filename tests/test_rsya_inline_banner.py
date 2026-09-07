from __future__ import annotations

import json
import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/manacost-rsya-inline.php"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"
TARGET_SLUG = "kvest-zhrecz-odna-iz-luchshih-kolod-v-mete-ametistovoj-kreposti"


class RsyaInlineBannerTest(unittest.TestCase):
    def render_result(
        self,
        *,
        slug: str = TARGET_SLUG,
        admin: bool = False,
        singular: bool = True,
        content_in_loop: bool = True,
        content_main_query: bool = True,
        rsya_enabled: bool = True,
    ) -> dict:
        content = (
            "<p><img src=\"cover.jpg\" alt=\"\"></p>"
            "<p>Первый текстовый абзац.</p>"
            "<p>Второй текстовый абзац.</p>"
            "<p>Третий текстовый абзац.</p>"
            "<h2>Основной раздел</h2><p>Продолжение материала.</p>"
        )
        rsya_flag = "" if rsya_enabled else "define('MANACOST_RSYA_INLINE_ENABLED', false);"
        script = f"""
        define('ABSPATH', '/');
        {rsya_flag}
        class WP_Post {{ public string $post_name; public function __construct($slug) {{ $this->post_name = $slug; }} }}
        $phase = 'head';
        $actions = [];
        $filters = [];
        $scripts = [];
        $inline_scripts = [];
        $script_data = [];
        function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {{
            $GLOBALS['actions'][$tag][] = [$callback, $priority, $accepted_args];
        }}
        function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {{
            $GLOBALS['filters'][$tag][] = [$callback, $priority, $accepted_args];
        }}
        function is_admin() {{ return {json.dumps(admin)}; }}
        function is_singular($type = null) {{ return {json.dumps(singular)}; }}
        function in_the_loop() {{ return 'content' === $GLOBALS['phase'] ? {json.dumps(content_in_loop)} : false; }}
        function is_main_query() {{ return 'content' === $GLOBALS['phase'] ? {json.dumps(content_main_query)} : true; }}
        function is_feed() {{ return false; }}
        function is_preview() {{ return false; }}
        function wp_doing_ajax() {{ return false; }}
        function get_queried_object() {{ return new WP_Post({json.dumps(slug)}); }}
        function wp_strip_all_tags($value) {{ return trim(strip_tags($value)); }}
        function wp_json_encode($value) {{ return json_encode($value); }}
        function esc_attr($value) {{ return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }}
        function esc_url($value) {{ return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }}
        function get_privacy_policy_url() {{ return 'https://hs-manacost.ru/privacy-policy/'; }}
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
        require {json.dumps(str(PLUGIN))};
        foreach ($actions['wp_enqueue_scripts'] ?? [] as $registered) {{ call_user_func($registered[0]); }}
        ob_start();
        foreach ($actions['wp_head'] ?? [] as $registered) {{ call_user_func($registered[0]); }}
        $head = ob_get_clean();
        $phase = 'content';
        echo json_encode([
            'content' => apply_test_filter('the_content', {json.dumps(content, ensure_ascii=False)}),
            'head' => $head,
            'scripts' => $scripts,
            'inline_scripts' => $inline_scripts,
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

    def test_target_article_loads_yandex_once_and_places_banner_after_intro(self) -> None:
        result = self.render_result()

        self.assertEqual(
            result["scripts"]["manacost-rsya-loader"],
            ["https://yandex.ru/ads/system/context.js", [], "R-A-16113237-5", {"strategy": "async"}],
        )
        self.assertEqual(result["inline_scripts"][0][2], "before")
        self.assertIn("window.yaContextCb = window.yaContextCb || []", result["inline_scripts"][0][1])

        content = result["content"]
        self.assertEqual(content.count('id="yandex_rtb_R-A-16113237-5"'), 1)
        self.assertIn('data-manacost-rsya-unit', content)
        self.assertNotIn("manacost-rsya-consent", content)
        self.assertNotIn("Показать рекламу", content)
        self.assertIn("Ya.Context.AdvManager.render", content)
        self.assertLess(
            content.index("Третий текстовый абзац."),
            content.index('id="yandex_rtb_R-A-16113237-5"'),
        )
        self.assertLess(
            content.index('id="yandex_rtb_R-A-16113237-5"'),
            content.index("Основной раздел"),
        )

    def test_banner_does_not_run_for_other_articles_or_admin(self) -> None:
        for kwargs in (
            {"slug": "another-post"},
            {"admin": True},
            {"rsya_enabled": False},
        ):
            with self.subTest(**kwargs):
                result = self.render_result(**kwargs)
                self.assertNotIn("yandex_rtb", result["content"])
                self.assertEqual(result["scripts"], [])

    def test_banner_does_not_run_for_secondary_content(self) -> None:
        for kwargs in ({"content_in_loop": False}, {"content_main_query": False}):
            with self.subTest(**kwargs):
                result = self.render_result(**kwargs)
                self.assertNotIn("yandex_rtb", result["content"])
                self.assertIn("manacost-rsya-loader", result["scripts"])


if __name__ == "__main__":
    unittest.main()
