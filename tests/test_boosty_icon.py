from __future__ import annotations

import json
import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/manacost-boosty-icon.php"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"


class BoostyIconTest(unittest.TestCase):
    def test_renders_a_centered_white_boosty_mark(self) -> None:
        script = f"""
        define('ABSPATH', '/');
        $actions = [];
        function add_action($hook, $callback, $priority = 10) {{
            $GLOBALS['actions'][$hook][] = [$callback, $priority];
        }}
        function is_admin() {{ return false; }}
        require {json.dumps(str(PLUGIN))};
        ob_start();
        foreach ($actions['wp_head'] ?? [] as $entry) {{
            call_user_func($entry[0]);
        }}
        echo ob_get_clean();
        """
        completed = subprocess.run(
            [PHP_BINARY, "-r", script], check=False, capture_output=True, text=True
        )

        self.assertEqual(completed.returncode, 0, completed.stderr)
        self.assertIn("background: url(\"data:image/svg+xml", completed.stdout)
        self.assertIn("fill='%23fff'", completed.stdout)
        self.assertIn("display: inline-flex", completed.stdout)
        self.assertIn("align-items: center", completed.stdout)
        self.assertIn("justify-content: center", completed.stdout)
        self.assertNotIn("#f15f2c", completed.stdout)


if __name__ == "__main__":
    unittest.main()
