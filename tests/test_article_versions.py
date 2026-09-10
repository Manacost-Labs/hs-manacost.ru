from __future__ import annotations

import json
import shutil
import subprocess
import textwrap
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/hs-article-versions.php"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"


class ArticleVersionsTest(unittest.TestCase):
    def run_plugin(
        self,
        *,
        versions: list[dict[str, str]] | None = None,
        can_edit: bool = True,
        front_end: bool = True,
        in_loop: bool = True,
        endpoint_version: int = 1,
        post_status: str = "publish",
        post_password: str = "",
        password_required: bool = False,
    ) -> dict:
        if versions is None:
            versions = [
                {
                    "title": "Старая версия",
                    "content": "<p>Архивный текст</p>",
                    "excerpt": "",
                    "created_gmt": "2026-09-09 12:00:00",
                }
            ]
        script = f"""
        define('ABSPATH', '/fixture/');
        $GLOBALS['actions'] = [];
        $GLOBALS['filters'] = [];
        $seed_versions = json_decode({json.dumps(json.dumps(versions))}, true);
        $GLOBALS['meta'] = [77 => ['_hs_article_versions' => $seed_versions]];
        $GLOBALS['updates'] = [];
        $GLOBALS['redirect'] = '';
        $GLOBALS['can_edit'] = {json.dumps(can_edit)};
        $GLOBALS['front_end'] = {json.dumps(front_end)};
        $GLOBALS['in_loop'] = {json.dumps(in_loop)};
        $GLOBALS['password_required'] = {json.dumps(password_required)};
        $GLOBALS['post'] = null;

        class WP_Post {{
            public int $ID;
            public string $post_type;
            public string $post_status;
            public string $post_title;
            public string $post_content;
            public string $post_excerpt;
            public string $post_password;
            public function __construct(
                int $id,
                string $post_type = 'post',
                string $post_status = 'publish',
                string $post_title = 'Актуальная версия',
                string $post_content = '<p>Текущий текст</p>',
                string $post_excerpt = '',
                string $post_password = ''
            ) {{
                $this->ID = $id;
                $this->post_type = $post_type;
                $this->post_status = $post_status;
                $this->post_title = $post_title;
                $this->post_content = $post_content;
                $this->post_excerpt = $post_excerpt;
                $this->post_password = $post_password;
            }}
        }}
        class WP_REST_Server {{ const READABLE = 'GET'; }}
        class WP_Query {{
            public function __construct($args) {{}}
            public function have_posts() {{ return true; }}
            public function the_post() {{ $GLOBALS['post'] = $GLOBALS['posts'][77]; }}
        }}
        class WP_REST_Request {{
            private array $params;
            public function __construct(array $params) {{ $this->params = $params; }}
            public function get_param($name) {{ return $this->params[$name] ?? null; }}
        }}
        class WP_Error {{
            public string $code;
            public string $message;
            public array $data;
            public function __construct($code, $message = '', $data = []) {{
                $this->code = (string) $code;
                $this->message = (string) $message;
                $this->data = is_array($data) ? $data : [];
            }}
        }}

        $GLOBALS['posts'] = [77 => new WP_Post(77, 'post', {json.dumps(post_status)}, 'Актуальная версия', '<p>Текущий текст</p>', '', {json.dumps(post_password)})];
        $GLOBALS['post'] = $GLOBALS['posts'][77];

        function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {{
            $GLOBALS['actions'][$hook][] = [$callback, $priority, $accepted_args];
        }}
        function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {{
            $GLOBALS['filters'][$hook][] = [$callback, $priority, $accepted_args];
        }}
        function register_rest_route($namespace, $route, $args) {{
            $GLOBALS['rest_route'] = [$namespace, $route, $args];
        }}
        function get_post($post_id) {{ return $GLOBALS['posts'][(int) $post_id] ?? null; }}
        function get_post_meta($post_id, $key, $single = true) {{ return $GLOBALS['meta'][(int) $post_id][$key] ?? ''; }}
        function update_post_meta($post_id, $key, $value) {{
            $GLOBALS['meta'][(int) $post_id][$key] = $value;
            $GLOBALS['updates'][] = [(int) $post_id, $key, $value];
            return 1;
        }}
        function current_user_can($capability, $post_id = 0) {{ return $GLOBALS['can_edit']; }}
        function check_admin_referer($action, $name = '_wpnonce') {{ return true; }}
        function wp_unslash($value) {{ return $value; }}
        function wp_slash($value) {{ return $value; }}
        function post_password_required($post = null) {{ return $GLOBALS['password_required']; }}
        function absint($value) {{ return abs((int) $value); }}
        function current_time($type, $gmt = false) {{ return '2026-09-09 12:00:00'; }}
        function get_edit_post_link($post_id, $context = 'display') {{ return 'https://example.test/wp-admin/post.php?post=' . (int) $post_id; }}
        function add_query_arg($key, $value, $url) {{ return $url . '&' . rawurlencode($key) . '=' . rawurlencode((string) $value); }}
        function wp_safe_redirect($url) {{ $GLOBALS['redirect'] = $url; return true; }}
        function wp_die($message = '', $status = 0) {{ throw new RuntimeException((string) $message . ':' . (string) $status); }}
        function admin_url($path = '') {{ return 'https://example.test/wp-admin/' . ltrim($path, '/'); }}
        function wp_nonce_url($url, $action = -1, $name = '_wpnonce') {{ return $url . '&' . $name . '=fixture'; }}
        function esc_html($value) {{ return (string) $value; }}
        function esc_attr($value) {{ return (string) $value; }}
        function esc_url($value) {{ return (string) $value; }}
        function __($value, $domain = null) {{ return $value; }}
        function esc_html__($value, $domain = null) {{ return (string) $value; }}
        function esc_attr__($value, $domain = null) {{ return (string) $value; }}
        function is_admin() {{ return false; }}
        function is_singular($post_type = '') {{ return $GLOBALS['front_end']; }}
        function in_the_loop() {{ return $GLOBALS['front_end'] && $GLOBALS['in_loop']; }}
        function is_main_query() {{ return $GLOBALS['front_end'] && $GLOBALS['in_loop']; }}
        function get_the_ID() {{ return 77; }}
        function get_queried_object_id() {{ return 77; }}
        function rest_url($path = '') {{ return 'https://example.test/wp-json/' . ltrim($path, '/'); }}
        function wp_json_encode($value) {{ return json_encode($value); }}
        function wp_kses_post($value) {{ return (string) $value; }}
        function apply_filters($hook, $value) {{ return 'rendered:' . (string) $value; }}
        function rest_ensure_response($value) {{ return $value; }}
        function wp_reset_postdata() {{ $GLOBALS['post'] = $GLOBALS['posts'][77]; }}

        require {json.dumps(str(PLUGIN))};

        ob_start();
        HS_Article_Versions::render_media_button('content');
        $editor_html = ob_get_clean();
        $frontend_html = HS_Article_Versions::prepend_switcher('<p>Текущий текст</p>');
        ob_start();
        HS_Article_Versions::render_styles();
        $styles = ob_get_clean();
        ob_start();
        HS_Article_Versions::render_script();
        $script = ob_get_clean();
        HS_Article_Versions::register_rest_route();
        $response = HS_Article_Versions::get_public_version(new WP_REST_Request([
            'post_id' => 77,
            'version' => {int(endpoint_version)},
        ]));

        echo json_encode([
            'actions' => array_keys($GLOBALS['actions']),
            'filters' => array_keys($GLOBALS['filters']),
            'editor_html' => $editor_html,
            'frontend_html' => $frontend_html,
            'styles' => $styles,
            'script' => $script,
            'rest_route' => $GLOBALS['rest_route'],
            'response' => $response instanceof WP_Error ? ['error' => $response->code] : $response,
        ]);
        """
        completed = subprocess.run(
            [PHP_BINARY, "-r", textwrap.dedent(script)],
            check=False,
            capture_output=True,
            text=True,
        )
        self.assertEqual(completed.returncode, 0, completed.stderr)
        return json.loads(completed.stdout)

    def run_snapshot_handler(
        self,
        *,
        can_edit: bool = True,
        post_status: str = "publish",
        post_password: str = "",
        persist: bool = True,
        content: str = "<p>Старый текст</p>",
    ) -> dict:
        script = f"""
        define('ABSPATH', '/fixture/');
        $GLOBALS['meta'] = [77 => ['_hs_article_versions' => []]];
        $GLOBALS['updates'] = [];
        $GLOBALS['redirect'] = '';
        $GLOBALS['can_edit'] = {json.dumps(can_edit)};
        $GLOBALS['persist'] = {json.dumps(persist)};
        $GLOBALS['cache_cleared'] = [];
        $GLOBALS['result'] = 'created';
        class WP_Post {{
            public int $ID = 77;
            public string $post_type = 'post';
            public string $post_status = {json.dumps(post_status)};
            public string $post_title = 'Снимок';
            public string $post_content = {json.dumps(content)};
            public string $post_excerpt = 'Кратко';
            public string $post_password = {json.dumps(post_password)};
        }}
        $GLOBALS['post_fixture'] = new WP_Post();
        function add_action(...$args) {{}}
        function add_filter(...$args) {{}}
        function get_post($id) {{ return 77 === (int) $id ? $GLOBALS['post_fixture'] : null; }}
        function get_post_meta($post_id, $key, $single = true) {{ return $GLOBALS['meta'][(int) $post_id][$key] ?? ''; }}
        function fixture_unslash_deep($value) {{
            if (is_array($value)) {{ return array_map('fixture_unslash_deep', $value); }}
            return is_string($value) ? stripslashes($value) : $value;
        }}
        function update_post_meta($post_id, $key, $value) {{
            if (! $GLOBALS['persist']) {{ return false; }}
            $stored = fixture_unslash_deep($value);
            $GLOBALS['updates'][] = [(int) $post_id, $key, $stored];
            return 1;
        }}
        function current_user_can($capability, $post_id = 0) {{ return $GLOBALS['can_edit']; }}
        function check_admin_referer($action, $name = '_wpnonce') {{ return true; }}
        function wp_unslash($value) {{ return $value; }}
        function wp_slash($value) {{
            if (is_array($value)) {{ return array_map('wp_slash', $value); }}
            return is_string($value) ? addslashes($value) : $value;
        }}
        function absint($value) {{ return abs((int) $value); }}
        function current_time($type, $gmt = false) {{ return '2026-09-09 12:00:00'; }}
        function get_edit_post_link($post_id, $context = 'display') {{ return 'https://example.test/wp-admin/post.php?post=' . (int) $post_id; }}
        function add_query_arg($key, $value, $url) {{ return $url . '&' . $key . '=' . $value; }}
        function wp_safe_redirect($url) {{ $GLOBALS['redirect'] = $url; return true; }}
        function esc_html__($value, $domain = null) {{ return (string) $value; }}
        function clean_post_cache($post_id) {{ $GLOBALS['cache_cleared'][] = (int) $post_id; }}
        function wp_die($message = '', $title = '', $args = []) {{
            $status = is_array($args) ? ($args['response'] ?? 0) : 0;
            throw new RuntimeException((string) $status);
        }}
        $_GET = ['post_id' => '77', '_wpnonce' => 'fixture'];
        require {json.dumps(str(PLUGIN))};
        register_shutdown_function(function () {{
            echo json_encode([
                'updates' => $GLOBALS['updates'],
                'cache_cleared' => $GLOBALS['cache_cleared'],
                'redirect' => $GLOBALS['redirect'],
                'result' => $GLOBALS['result'],
            ]);
        }});
        try {{
            HS_Article_Versions::handle_create_version();
        }} catch (RuntimeException $error) {{
            $GLOBALS['result'] = $error->getMessage();
        }}
        """
        completed = subprocess.run(
            [PHP_BINARY, "-r", textwrap.dedent(script)],
            check=False,
            capture_output=True,
            text=True,
        )
        self.assertEqual(completed.returncode, 0, completed.stderr)
        return json.loads(completed.stdout)

    def test_editor_entrypoint_and_frontend_switcher_are_registered(self) -> None:
        result = self.run_plugin()

        self.assertIn("media_buttons", result["actions"])
        self.assertIn("admin_post_hs_create_article_version", result["actions"])
        self.assertIn("rest_api_init", result["actions"])
        self.assertIn("the_content", result["filters"])
        self.assertIn("Создать новую версию", result["editor_html"])
        self.assertIn("admin-post.php", result["editor_html"])
        self.assertIn("Актуальная версия", result["frontend_html"])
        self.assertIn("Версия 1", result["frontend_html"])
        self.assertIn('id="hs-article-version-content"', result["frontend_html"])
        self.assertIn("hs-article-versions", result["styles"])
        self.assertIn("fetch(", result["script"])
        self.assertNotIn("location.href", result["script"])

    def test_frontend_is_absent_until_an_editor_creates_a_snapshot(self) -> None:
        result = self.run_plugin(versions=[])

        self.assertEqual("<p>Текущий текст</p>", result["frontend_html"])
        self.assertEqual("", result["styles"])
        self.assertEqual("", result["script"])

    def test_assets_are_available_outside_the_content_loop_but_switcher_is_not(self) -> None:
        result = self.run_plugin(in_loop=False)

        self.assertEqual("<p>Текущий текст</p>", result["frontend_html"])
        self.assertIn("hs-article-versions", result["styles"])
        self.assertIn("fetch(", result["script"])

    def test_public_endpoint_only_returns_a_saved_version_of_a_published_article(self) -> None:
        result = self.run_plugin()
        missing = self.run_plugin(endpoint_version=2)
        draft = self.run_plugin(post_status="draft")
        protected = self.run_plugin(post_password="secret", password_required=True)

        self.assertEqual("manacost/v1", result["rest_route"][0])
        self.assertIn("article-versions", result["rest_route"][1])
        self.assertEqual(1, result["response"]["version"])
        self.assertEqual("Старая версия", result["response"]["title"])
        self.assertEqual("rendered:<p>Архивный текст</p>", result["response"]["content"])
        self.assertEqual({"error": "hs_article_version_not_found"}, missing["response"])
        self.assertEqual({"error": "hs_article_version_not_found"}, draft["response"])
        self.assertEqual({"error": "hs_article_version_not_found"}, protected["response"])
        self.assertEqual("", protected["editor_html"])
        self.assertEqual("<p>Текущий текст</p>", protected["frontend_html"])

    def test_editor_snapshot_action_stores_the_current_article_before_redirecting_back_to_it(self) -> None:
        content = '<p data-payload=\'{"deck":"C:\\\\Decks"}\'>Archived text</p>'
        result = self.run_snapshot_handler(content=content)

        self.assertEqual(1, len(result["updates"]))
        post_id, key, snapshots = result["updates"][0]
        self.assertEqual(77, post_id)
        self.assertEqual("_hs_article_versions", key)
        self.assertEqual("Снимок", snapshots[0]["title"])
        self.assertEqual(content, snapshots[0]["content"])
        self.assertEqual([77], result["cache_cleared"])
        self.assertIn("hs_article_version_created=1", result["redirect"])

    def test_snapshot_action_rejects_non_public_sources_and_persistence_failures(self) -> None:
        draft = self.run_snapshot_handler(post_status="draft")
        protected = self.run_snapshot_handler(post_password="secret")
        failed = self.run_snapshot_handler(persist=False)

        for result, status in ((draft, "403"), (protected, "403"), (failed, "500")):
            self.assertEqual(status, result["result"])
            self.assertEqual([], result["updates"])
            self.assertEqual([], result["cache_cleared"])
            self.assertEqual("", result["redirect"])


if __name__ == "__main__":
    unittest.main()
