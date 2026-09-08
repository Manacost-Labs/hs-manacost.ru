from __future__ import annotations

import json
import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/manacost-social-links.php"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"


class SocialLinksTest(unittest.TestCase):
    def run_plugin(self, networks: dict[str, str]) -> dict:
        script = f"""
        define('ABSPATH', '/');
        $actions = [];
        $filters = [];
        function add_action($hook, $callback, $priority = 10) {{
            $GLOBALS['actions'][$hook][] = [$callback, $priority];
        }}
        function add_filter($hook, $callback, $priority = 10) {{
            $GLOBALS['filters'][$hook][] = [$callback, $priority];
        }}
        function is_admin() {{ return false; }}
        class td_social_icons {{
            public static array $td_social_icons_array = [
                'website' => 'Website',
                'github' => 'GitHub',
            ];
        }}
        require {json.dumps(str(PLUGIN))};
        foreach ($actions['after_setup_theme'] ?? [] as $entry) {{
            call_user_func($entry[0]);
        }}
        ob_start();
        foreach ($actions['wp_head'] ?? [] as $entry) {{
            call_user_func($entry[0]);
        }}
        $styles = ob_get_clean();
        $options = ['td_social_networks' => json_decode({json.dumps(json.dumps(networks))}, true)];
        foreach ($filters['option_td_011'] ?? [] as $entry) {{
            $options = call_user_func($entry[0], $options);
        }}
        echo json_encode([
            'networks' => $options['td_social_networks'],
            'boosty_label' => td_social_icons::$td_social_icons_array['boosty'] ?? null,
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

    def test_replaces_website_with_support_links_once(self) -> None:
        result = self.run_plugin(
            {
                "telegram": "https://t.me/manacost_ru",
                "twitch": "https://www.twitch.tv/hsmanacost",
                "vk": "https://vk.com/manacost",
                "website": "https://old.example/",
            }
        )

        self.assertEqual(
            {
                "telegram": "https://t.me/manacost_ru",
                "twitch": "https://www.twitch.tv/hsmanacost",
                "vk": "https://vk.com/manacost",
                "github": "https://github.com/Manacost-Labs",
                "boosty": "https://boosty.to/kolodahearthstone",
                "patreon": "https://www.patreon.com/cw/manacostru",
            },
            result["networks"],
        )
        self.assertEqual("Boosty", result["boosty_label"])

    def test_preserves_other_networks_and_is_idempotent(self) -> None:
        first = self.run_plugin(
            {
                "telegram": "https://t.me/manacost_ru",
                "github": "https://github.com/other",
                "boosty": "https://boosty.to/other",
                "patreon": "https://www.patreon.com/other",
                "discord": "https://discord.com/invite/manacost",
            }
        )["networks"]
        second = self.run_plugin(first)["networks"]

        self.assertEqual(first, second)
        self.assertEqual("https://discord.com/invite/manacost", first["discord"])
        self.assertEqual("https://github.com/Manacost-Labs", first["github"])
        self.assertEqual("https://boosty.to/kolodahearthstone", first["boosty"])
        self.assertEqual("https://www.patreon.com/cw/manacostru", first["patreon"])

    def test_outputs_the_scoped_boosty_icon_style(self) -> None:
        styles = self.run_plugin({})["styles"]

        self.assertIn('a[href*="boosty.to"] .td-icon-boosty', styles)
        self.assertIn('background-color: #f15f2c', styles)

    def test_keeps_non_array_theme_options_unchanged(self) -> None:
        script = f"""
        define('ABSPATH', '/');
        function add_action($hook, $callback, $priority = 10) {{}}
        function add_filter($hook, $callback, $priority = 10) {{}}
        function is_admin() {{ return false; }}
        require {json.dumps(str(PLUGIN))};
        var_export(Manacost_Social_Links::filter_theme_options('invalid'));
        """
        completed = subprocess.run(
            [PHP_BINARY, "-r", script], check=False, capture_output=True, text=True
        )
        self.assertEqual(completed.returncode, 0, completed.stderr)
        self.assertEqual("'invalid'", completed.stdout)


if __name__ == "__main__":
    unittest.main()
