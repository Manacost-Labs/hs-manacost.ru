"""Contract tests for the site-wide first-party content lightbox."""

from __future__ import annotations

import hashlib
import json
from pathlib import Path
import shutil
import subprocess
import textwrap
import unittest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/hs-manacost-lightbox.php"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"


class ContentLightboxContractTest(unittest.TestCase):
    def run_plugin(
        self,
        *,
        admin: bool = False,
        feed: bool = False,
        preview: bool = False,
        singular: bool = True,
        live_editor_iframe: bool = False,
        live_editor_ajax: bool = False,
    ) -> dict[str, object]:
        script = f"""
        define('ABSPATH', '/fixture/');
        $GLOBALS['actions'] = [];
        $GLOBALS['filters'] = [];
        $GLOBALS['enqueued_scripts'] = [];
        $GLOBALS['enqueued_styles'] = [];
        $GLOBALS['dequeued_scripts'] = [];
        $GLOBALS['deregistered_scripts'] = [];
        $GLOBALS['script_data'] = [];
        $GLOBALS['localized'] = [];
        $GLOBALS['admin'] = {json.dumps(admin)};
        $GLOBALS['feed'] = {json.dumps(feed)};
        $GLOBALS['preview'] = {json.dumps(preview)};
        $GLOBALS['singular'] = {json.dumps(singular)};
        $GLOBALS['live_editor_iframe'] = {json.dumps(live_editor_iframe)};
        $GLOBALS['live_editor_ajax'] = {json.dumps(live_editor_ajax)};

        class tdc_state {{
            public static function is_live_editor_iframe() {{ return $GLOBALS['live_editor_iframe']; }}
            public static function is_live_editor_ajax() {{ return $GLOBALS['live_editor_ajax']; }}
        }}

        function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {{
            $GLOBALS['actions'][$hook][] = [$callback, $priority, $accepted_args];
        }}
        function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {{
            $GLOBALS['filters'][$hook][] = [$callback, $priority, $accepted_args];
        }}
        function is_admin() {{ return $GLOBALS['admin']; }}
        function is_feed() {{ return $GLOBALS['feed']; }}
        function is_preview() {{ return $GLOBALS['preview']; }}
        function is_singular() {{ return $GLOBALS['singular']; }}
        function plugin_dir_url($file) {{ return 'https://example.test/wp-content/mu-plugins/'; }}
        function wp_enqueue_style($handle, $src, $deps, $version) {{
            $GLOBALS['enqueued_styles'][$handle] = compact('src', 'deps', 'version');
        }}
        function wp_enqueue_script($handle, $src, $deps, $version, $args) {{
            $GLOBALS['enqueued_scripts'][$handle] = compact('src', 'deps', 'version', 'args');
        }}
        function wp_dequeue_script($handle) {{ $GLOBALS['dequeued_scripts'][] = $handle; }}
        function wp_deregister_script($handle) {{ $GLOBALS['deregistered_scripts'][] = $handle; }}
        function wp_script_add_data($handle, $key, $value) {{
            $GLOBALS['script_data'][$handle][$key] = $value;
        }}
        function wp_localize_script($handle, $object_name, $data) {{
            $GLOBALS['localized'][$handle] = compact('object_name', 'data');
        }}
        function __($text, $domain = null) {{ return $text; }}

        require {json.dumps(str(PLUGIN))};
        foreach ($GLOBALS['actions']['wp_enqueue_scripts'] ?? [] as $action) {{
            call_user_func($action[0]);
        }}
        $optimizer_exclusions = [];
        foreach (['rocket_delay_js_exclusions', 'perfmatters_delay_js_exclusions', 'rocket_rucss_external_exclusions', 'perfmatters_rucss_excluded_stylesheets'] as $hook) {{
            $value = ['/existing-rule'];
            foreach ($GLOBALS['filters'][$hook] ?? [] as $filter) {{
                $value = call_user_func($filter[0], $value);
            }}
            $optimizer_exclusions[$hook] = $value;
        }}
        echo json_encode([
            'actions' => $GLOBALS['actions'],
            'scripts' => $GLOBALS['enqueued_scripts'],
            'styles' => $GLOBALS['enqueued_styles'],
            'dequeued' => $GLOBALS['dequeued_scripts'],
            'deregistered' => $GLOBALS['deregistered_scripts'],
            'script_data' => $GLOBALS['script_data'],
            'localized' => $GLOBALS['localized'],
            'optimizer_exclusions' => $optimizer_exclusions,
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

    def test_singular_frontend_replaces_newspaper_modal_with_deferred_assets(self) -> None:
        result = self.run_plugin()

        self.assertEqual(["tdModalPostImages"], result["dequeued"])
        self.assertEqual(["tdModalPostImages"], result["deregistered"])
        self.assertEqual([], result["scripts"]["hs-manacost-lightbox"]["deps"])
        self.assertEqual(True, result["scripts"]["hs-manacost-lightbox"]["args"]["in_footer"])
        self.assertEqual("defer", result["script_data"]["hs-manacost-lightbox"]["strategy"])
        self.assertIn("lightbox.js", result["scripts"]["hs-manacost-lightbox"]["src"])
        self.assertIn("lightbox.css", result["styles"]["hs-manacost-lightbox"]["src"])
        self.assertRegex(result["scripts"]["hs-manacost-lightbox"]["version"], r"^[a-f0-9]{12}$")
        self.assertRegex(result["styles"]["hs-manacost-lightbox"]["version"], r"^[a-f0-9]{12}$")
        for asset_type, asset_name in (("scripts", "lightbox.js"), ("styles", "lightbox.css")):
            asset = ROOT / "wordpress/mu-plugins/hs-manacost-lightbox" / asset_name
            expected_version = hashlib.sha256(asset.read_bytes()).hexdigest()[:12]
            self.assertEqual(
                expected_version,
                result[asset_type]["hs-manacost-lightbox"]["version"],
                f"{asset_name} content hash is stale",
            )
        self.assertEqual("hsManacostLightboxConfig", result["localized"]["hs-manacost-lightbox"]["object_name"])
        for hook, patterns in result["optimizer_exclusions"].items():
            self.assertEqual("/existing-rule", patterns[0], hook)
            expected_asset = "lightbox.js" if "delay_js" in hook else "lightbox.css"
            self.assertIn(
                f"/wp-content/mu-plugins/hs-manacost-lightbox/{expected_asset}",
                patterns,
                hook,
            )

    def test_non_content_requests_do_not_load_or_modify_frontend_assets(self) -> None:
        for kwargs in (
            {"admin": True},
            {"feed": True},
            {"preview": True},
            {"singular": False},
            {"live_editor_iframe": True},
            {"live_editor_ajax": True},
        ):
            with self.subTest(**kwargs):
                result = self.run_plugin(**kwargs)
                self.assertEqual([], result["scripts"])
                self.assertEqual([], result["styles"])
                self.assertEqual([], result["dequeued"])
                self.assertEqual([], result["deregistered"])

    def test_plugin_has_no_data_writes_or_remote_requests(self) -> None:
        source = PLUGIN.read_text(encoding="utf-8")

        for forbidden in (
            "update_option",
            "update_post_meta",
            "wp_remote_",
            "$wpdb",
            "setcookie",
        ):
            self.assertNotIn(forbidden, source)


if __name__ == "__main__":
    unittest.main()
