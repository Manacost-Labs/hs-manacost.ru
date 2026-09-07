from __future__ import annotations

import json
import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/hs-comments-policy.php"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"


class CommentsPolicyTest(unittest.TestCase):
    def policy_result(self, *, admin: bool = False) -> dict:
        script = f"""
        define('ABSPATH', '/');
        $hooks = [];
        $actions = [];
        $styles = [];
        function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {{
            $GLOBALS['hooks'][$tag] = [$callback, $priority, $accepted_args];
        }}
        function is_admin() {{ return {json.dumps(admin)}; }}
        function __return_false() {{ return false; }}
        function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {{
            $GLOBALS['actions'][$tag] = $callback;
        }}
        function wp_register_style($handle, $source, $dependencies, $version) {{
            $GLOBALS['styles']['registered'] = [$handle, $source, $dependencies, $version];
        }}
        function wp_enqueue_style($handle) {{ $GLOBALS['styles']['enqueued'] = $handle; }}
        function wp_add_inline_style($handle, $css) {{
            $GLOBALS['styles']['inline'] = [$handle, $css];
        }}
        function apply_test_filter($tag, $value) {{
            return isset($GLOBALS['hooks'][$tag])
                ? call_user_func($GLOBALS['hooks'][$tag][0], $value)
                : $value;
        }}
        require {json.dumps(str(PLUGIN))};
        if (isset($actions['wp_enqueue_scripts'])) {{
            call_user_func($actions['wp_enqueue_scripts']);
        }}
        $stored_comments = [(object) ['comment_ID' => 42, 'comment_content' => 'Stored comment']];
        $filtered_comments = apply_test_filter('comments_array', $stored_comments);
        echo json_encode([
            'hooks' => array_map(static function ($hook) {{
                return [$hook[0], $hook[1] === PHP_INT_MAX, $hook[2]];
            }}, $hooks),
            'comments_open' => apply_test_filter('comments_open', true),
            'pings_open' => apply_test_filter('pings_open', true),
            'comments' => $filtered_comments,
            'number_text' => apply_test_filter('comments_number', '1 comment'),
            'count' => apply_test_filter('get_comments_number', 1),
            'stored_comments' => $stored_comments,
            'admin_objects_preserved' => $filtered_comments === $stored_comments,
            'actions' => $actions,
            'styles' => $styles,
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

    def test_registers_only_scoped_comment_policy_hooks_at_final_priority(self) -> None:
        hooks = self.policy_result()["hooks"]
        self.assertEqual(
            set(hooks), {"comments_open", "pings_open", "comments_array", "comments_number"}
        )
        self.assertEqual(hooks["comments_open"], ["__return_false", True, 1])
        self.assertEqual(hooks["pings_open"], ["__return_false", True, 1])
        self.assertTrue(all(hook[1:] == [True, 1] for hook in hooks.values()))

    def test_frontend_hides_old_comments_and_number_without_changing_data(self) -> None:
        result = self.policy_result()
        self.assertEqual(result["comments"], [])
        self.assertEqual(result["number_text"], "")
        self.assertEqual(result["count"], 1)
        self.assertEqual(
            result["stored_comments"],
            [{"comment_ID": 42, "comment_content": "Stored comment"}],
        )

    def test_comment_and_ping_submission_remain_closed_in_every_context(self) -> None:
        for admin in (False, True):
            with self.subTest(admin=admin):
                result = self.policy_result(admin=admin)
                self.assertFalse(result["comments_open"])
                self.assertFalse(result["pings_open"])

    def test_admin_retains_existing_comment_objects_and_number(self) -> None:
        result = self.policy_result(admin=True)
        self.assertTrue(result["admin_objects_preserved"])
        self.assertEqual(result["comments"], result["stored_comments"])
        self.assertEqual(result["number_text"], "1 comment")
        self.assertEqual(result["count"], 1)

    def test_frontend_counter_styles_use_the_enqueue_api(self) -> None:
        result = self.policy_result()
        self.assertEqual(
            result["actions"],
            {"wp_enqueue_scripts": "hs_comments_policy_enqueue_styles"},
        )
        self.assertEqual(
            result["styles"]["registered"],
            ["hs-comments-policy", False, [], "1.0.0"],
        )
        self.assertEqual(result["styles"]["enqueued"], "hs-comments-policy")
        self.assertEqual(
            result["styles"]["inline"],
            ["hs-comments-policy", ".td-post-comments,.td-module-comments{display:none!important;}"],
        )

    def test_admin_does_not_register_or_enqueue_frontend_counter_styles(self) -> None:
        self.assertEqual(self.policy_result(admin=True)["styles"], [])


if __name__ == "__main__":
    unittest.main()
