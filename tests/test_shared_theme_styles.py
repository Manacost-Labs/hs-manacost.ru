import json
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/manacost-shared-theme-styles.php"


class SharedThemeStylesTest(unittest.TestCase):
    def test_shared_styles_keep_one_url_and_existing_exclusions(self):
        script = r'''
        define('ABSPATH', '/');
        $filters = [];
        $GLOBALS['admin'] = false;
        function is_admin() { return $GLOBALS['admin']; }
        function add_filter($name, $callback) { $GLOBALS['filters'][$name] = $callback; }
        if (is_readable($argv[1])) { require $argv[1]; }
        $original = ['custom' => '/custom.css', 'font' => '/fonts.css'];
        $output = [];
        foreach (['rocket_exclude_css', 'perfmatters_minify_css_exclusions'] as $hook) {
            $callback = $filters[$hook] ?? fn($value) => $value;
            $GLOBALS['admin'] = false;
            $public = $callback($original);
            $repeat = $callback($public);
            $GLOBALS['admin'] = true;
            $output[$hook] = ['public' => $public, 'repeat' => $repeat, 'admin' => $callback($original)];
        }
        echo json_encode(['original' => $original, 'hooks' => $output]);
        '''
        result = subprocess.run(["php", "-r", script, str(PLUGIN)],
                                check=True, text=True, capture_output=True)
        data = json.loads(result.stdout)
        shared = [
            "/wp-content/themes/Newspaper_new/style.css",
            "/wp-content/plugins/td-composer/legacy/Newspaper/assets/css/td_legacy_main.css",
            "/wp-content/plugins/td-standard-pack/Newspaper/assets/css/td_standard_pack_main.css",
        ]
        for hook, values in data["hooks"].items():
            with self.subTest(hook=hook):
                self.assertEqual(data["original"], values["admin"])
                self.assertEqual(values["public"], values["repeat"])
                self.assertEqual("/custom.css", values["public"]["custom"])
                self.assertEqual("/fonts.css", values["public"]["font"])
                self.assertEqual(shared, [v for k, v in values["public"].items() if k not in data["original"]])


if __name__ == "__main__":
    unittest.main()
