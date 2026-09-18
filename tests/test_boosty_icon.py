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
		function is_front_page() {{ return false; }}
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

    def test_injects_a_labeled_telegram_news_link_inside_public_page_content(self) -> None:
        script = f"""
        define('ABSPATH', '/');
        $actions = [];
        function add_action($hook, $callback, $priority = 10) {{
            $GLOBALS['actions'][$hook][] = [$callback, $priority];
        }}
        function is_admin() {{ return false; }}
        function is_feed() {{ return false; }}
        function is_preview() {{ return false; }}
        function is_front_page() {{ return false; }}
        require {json.dumps(str(PLUGIN))};
        $markup = manacost_telegram_news_strip_markup();
        echo manacost_inject_telegram_news_strip(
            '<div class="td-header-wrap"></div><div class="td-main-content-wrap"></div>'
        );
        echo "\nMARKUP:\n" . $markup;
        """
        completed = subprocess.run(
            [PHP_BINARY, "-r", script], check=False, capture_output=True, text=True
        )

        self.assertEqual(completed.returncode, 0, completed.stderr)
        self.assertIn('href="https://t.me/manacost_ru"', completed.stdout)
        self.assertIn('class="manacost-telegram-news td-container"', completed.stdout)
        self.assertIn("Актуальные и быстрые новости в Telegram", completed.stdout)
        self.assertIn('aria-label="Открыть канал Manacost в Telegram"', completed.stdout)
        self.assertIn('rel="noopener noreferrer"', completed.stdout)
        self.assertLess(
            completed.stdout.index("td-main-content-wrap"),
            completed.stdout.index("manacost-telegram-news"),
        )

    def test_starts_the_telegram_strip_on_non_homepage_public_pages(self) -> None:
        script = f"""
        define('ABSPATH', '/');
        $actions = [];
        function add_action($hook, $callback, $priority = 10) {{
            $GLOBALS['actions'][$hook][] = [$callback, $priority];
        }}
        function is_admin() {{ return false; }}
        function is_feed() {{ return false; }}
        function is_preview() {{ return false; }}
        function is_front_page() {{ return false; }}
        require {json.dumps(str(PLUGIN))};
        manacost_start_telegram_news_strip_buffer();
        $started = ob_get_level() > 0;
        ob_end_clean();
        echo $started ? 'started' : 'not-started';
        """
        completed = subprocess.run(
            [PHP_BINARY, "-r", script], check=False, capture_output=True, text=True
        )

        self.assertEqual(completed.returncode, 0, completed.stderr)
        self.assertEqual("started", completed.stdout)


if __name__ == "__main__":
    unittest.main()
