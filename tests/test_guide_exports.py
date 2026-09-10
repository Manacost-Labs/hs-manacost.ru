from __future__ import annotations

import json
import shutil
import subprocess
import textwrap
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
LEGACY_PLUGIN = ROOT / "wordpress/mu-plugins/manacost-guide-pdf.php"
POLICY_PLUGIN = ROOT / "wordpress/mu-plugins/hs-guide-export-policy.php"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"


class GuideExportsDisabledTest(unittest.TestCase):
    def run_plugin(self, query: dict[str, str] | None = None) -> dict:
        script = f"""
        define('ABSPATH', '/fixture/');
        $GLOBALS['actions'] = [];
        $GLOBALS['filters'] = [];
        $GLOBALS['removed_filters'] = [];
        $GLOBALS['status'] = 200;
        $_GET = json_decode({json.dumps(json.dumps(query or {}))}, true);
        function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {{ $GLOBALS['actions'][] = [$hook, $priority]; }}
        function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {{ $GLOBALS['filters'][] = [$hook, $priority]; }}
        function remove_filter($hook, $callback, $priority = 10) {{ $GLOBALS['removed_filters'][] = [$hook, $priority]; return true; }}
        function is_singular($post_type = '') {{ return true; }}
        function status_header($status) {{ $GLOBALS['status'] = (int) $status; }}
        function nocache_headers() {{}}
        function wp_unslash($value) {{ return $value; }}
        function esc_html__($value, $domain = null) {{ return (string) $value; }}
        function wp_die($message = '', $title = '', $args = []) {{ throw new RuntimeException((string) ($args['response'] ?? 0)); }}
        ob_start();
        require {json.dumps(str(LEGACY_PLUGIN))};
        require {json.dumps(str(POLICY_PLUGIN))};
        register_shutdown_function(function () {{
            while (ob_get_level()) {{ ob_end_clean(); }}
            echo json_encode([
                'actions' => $GLOBALS['actions'],
                'filters' => $GLOBALS['filters'],
                'removed_filters' => $GLOBALS['removed_filters'],
                'status' => $GLOBALS['status'],
            ]);
        }});
        HS_Guide_Export_Policy::disable_legacy_exports();
        if (!empty($_GET)) {{
            try {{
                HS_Guide_Export_Policy::block_legacy_requests();
            }} catch (RuntimeException $error) {{
                $GLOBALS['status'] = (int) $error->getMessage();
            }}
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

    def test_exports_are_disabled_in_the_interface_and_on_legacy_urls(self) -> None:
        initial = self.run_plugin()
        pdf_request = self.run_plugin({"manacost_guide_pdf": "1"})
        txt_request = self.run_plugin({"manacost_guide_txt": "1"})

        self.assertIn(["the_content", 12], initial["filters"])
        self.assertIn(["the_content", 12], initial["removed_filters"])
        self.assertIn(["template_redirect", -1], initial["actions"])
        self.assertEqual(410, pdf_request["status"])
        self.assertEqual(410, txt_request["status"])


if __name__ == "__main__":
    unittest.main()
