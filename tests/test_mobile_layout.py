from __future__ import annotations

import json
import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/manacost-mobile-layout.php"
STYLESHEET = ROOT / "wordpress/mu-plugins/manacost-mobile-layout/mobile-layout.css"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"


class MobileLayoutTest(unittest.TestCase):
    def run_plugin(self, *, admin: bool = False, feed: bool = False) -> dict[str, object]:
        script = f"""
        define('ABSPATH', '/');
        $actions = [];
        $styles = [];
        $admin = {json.dumps(admin)};
        $feed = {json.dumps(feed)};

        function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {{
            $GLOBALS['actions'][$hook][] = [$callback, $priority, $accepted_args];
        }}
        function is_admin() {{ return $GLOBALS['admin']; }}
        function is_feed() {{ return $GLOBALS['feed']; }}
        function is_preview() {{ return false; }}
        function is_robots() {{ return false; }}
        function is_trackback() {{ return false; }}
        function plugin_dir_url($file) {{ return '/wp-content/mu-plugins/'; }}
        function wp_enqueue_style($handle, $src, $dependencies = [], $version = false) {{
            $GLOBALS['styles'][$handle] = [$src, $dependencies, $version];
        }}

        require {json.dumps(str(PLUGIN))};
        foreach ($actions['wp_enqueue_scripts'] ?? [] as $entry) {{
            call_user_func($entry[0]);
        }}

        echo json_encode([
            'actions' => array_keys($actions),
            'styles' => $styles,
        ], JSON_UNESCAPED_SLASHES);
        """
        completed = subprocess.run(
            [PHP_BINARY, "-r", script],
            check=False,
            capture_output=True,
            text=True,
        )
        self.assertEqual(completed.returncode, 0, completed.stderr)
        return json.loads(completed.stdout)

    def test_enqueues_scoped_public_mobile_layout(self) -> None:
        result = self.run_plugin()

        self.assertIn("wp_enqueue_scripts", result["actions"])
        self.assertIn("manacost-mobile-layout", result["styles"])
        self.assertEqual(
            "/wp-content/mu-plugins/manacost-mobile-layout/mobile-layout.css",
            result["styles"]["manacost-mobile-layout"][0],
        )
        self.assertEqual("1.0.0", result["styles"]["manacost-mobile-layout"][2])

        self.assertEqual([], self.run_plugin(admin=True)["styles"])
        self.assertEqual([], self.run_plugin(feed=True)["styles"])

    def test_mobile_css_owns_the_article_header_cards_and_logo(self) -> None:
        css = STYLESHEET.read_text(encoding="utf-8")

        self.assertIn("@media (max-width: 767px)", css)
        self.assertIn(".td-post-header-holder.td-image-gradient", css)
        self.assertIn("display: grid", css)
        self.assertIn(".td-post-title", css)
        self.assertIn("position: relative", css)
        self.assertIn(".td-big-grid-flex-post", css)
        self.assertIn(".td-module-meta-info", css)
        self.assertIn("inline-size: calc(100% - 16px)", css)
        self.assertIn("block-size: min(80vw, 300px)", css)
        self.assertIn("margin-inline: 8px", css)
        self.assertIn("margin-block-end: 8px", css)
        self.assertIn("font-size: clamp(16px, 4.4vw, 17px)", css)
        self.assertNotIn("flex-direction: column", css)
        self.assertNotIn("position: static", css)
        self.assertIn(".td-mobile-logo img", css)
        self.assertIn("block-size: 56px", css)

    def test_mobile_css_preserves_focus_and_reduced_motion_contracts(self) -> None:
        css = STYLESHEET.read_text(encoding="utf-8")

        self.assertIn(":focus-visible", css)
        self.assertIn("@media (prefers-reduced-motion: reduce)", css)
        self.assertNotIn("user-scalable=no", css)
        self.assertNotIn("overflow-x: hidden", css)


if __name__ == "__main__":
    unittest.main()
