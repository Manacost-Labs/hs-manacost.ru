from __future__ import annotations

import json
import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLAUSIBLE_PLUGIN = ROOT / "wordpress/mu-plugins/plausible-analytics.php"
WEB_VITALS_PLUGIN = ROOT / "wordpress/mu-plugins/manacost-web-vitals.php"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"


class PlausibleWebVitalsTest(unittest.TestCase):
    def render(
        self, *, host: str, environment: str, web_vitals_enabled: bool = True
    ) -> dict[str, object]:
        script = f"""
        define('ABSPATH', '/');
        define('MANACOST_WEB_VITALS_ENABLED', {str(web_vitals_enabled).lower()});
        $GLOBALS['removed_actions'] = [];
        function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {{}}
        function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {{}}
        function remove_action($tag, $callback, $priority = 10) {{
            $GLOBALS['removed_actions'][] = [$tag, $callback, $priority];
        }}
        function is_admin() {{ return false; }}
        function is_singular($type = '') {{ return false; }}
        function is_page() {{ return false; }}
        function is_front_page() {{ return true; }}
        function is_home() {{ return true; }}
        function is_category() {{ return false; }}
        function is_tag() {{ return false; }}
        function is_search() {{ return false; }}
        function is_404() {{ return false; }}
        function is_archive() {{ return false; }}
        function home_url($path = '') {{ return 'https://{host}' . $path; }}
        function wp_parse_url($url, $component = -1) {{ return parse_url($url, $component); }}
        function wp_get_environment_type() {{ return {json.dumps(environment)}; }}
        function esc_attr($value) {{ return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }}
        require {json.dumps(str(PLAUSIBLE_PLUGIN))};
        require {json.dumps(str(WEB_VITALS_PLUGIN))};
        ob_start();
        Manacost_Web_Vitals::disable_staging_tracker();
        if ({json.dumps(environment == 'production')}) {{
            Manacost_Plausible_Analytics::render_tracker();
        }}
        Manacost_Web_Vitals::render();
        $html = ob_get_clean();
        echo json_encode(['html' => $html, 'removed' => $GLOBALS['removed_actions']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        """
        completed = subprocess.run(
            [PHP_BINARY, "-r", script],
            check=False,
            capture_output=True,
            text=True,
        )
        self.assertEqual(completed.returncode, 0, completed.stderr)
        return json.loads(completed.stdout)

    def test_staging_is_inert(self) -> None:
        result = self.render(host="test.hs-manacost.ru", environment="staging")

        self.assertEqual(result["html"], "")
        self.assertEqual(result["removed"][0][0], "wp_head")
        self.assertEqual(result["removed"][0][2], 20)

    def test_production_emits_one_tracker_and_sampled_web_vitals(self) -> None:
        result = self.render(host="hs-manacost.ru", environment="production")["html"]

        self.assertEqual(result.count('data-domain="hs-manacost.ru"'), 1)
        self.assertEqual(result.count('src="https://hs-manacost.ru/mca/script.js"'), 1)
        self.assertIn('id="manacost-web-vitals"', result)
        self.assertIn('Math.random()>0.05', result)
        self.assertIn('largest-contentful-paint', result)
        self.assertIn('layout-shift', result)
        self.assertIn("type:'event'", result)
        self.assertIn("plausible('Web Vital'", result)
        self.assertNotIn('location.href', result)
        self.assertNotIn('localStorage', result)
        self.assertNotIn('document.cookie', result)

    def test_production_mirror_uses_same_property_without_second_tracker(self) -> None:
        result = self.render(host="hs-manacost.com", environment="production")["html"]

        self.assertEqual(result.count('data-domain="hs-manacost.ru"'), 1)
        self.assertEqual(result.count('id="manacost-web-vitals"'), 1)

    def test_web_vitals_can_be_disabled_without_disabling_the_existing_tracker(self) -> None:
        result = self.render(
            host="hs-manacost.ru",
            environment="production",
            web_vitals_enabled=False,
        )["html"]

        self.assertEqual(result.count('data-domain="hs-manacost.ru"'), 1)
        self.assertNotIn('id="manacost-web-vitals"', result)


if __name__ == "__main__":
    unittest.main()
