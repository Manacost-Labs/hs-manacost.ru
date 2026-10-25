import hashlib
import json
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/manacost-shared-theme-styles.php"
CRITICAL = ROOT / "wordpress/mu-plugins/manacost-shared-theme-styles"


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


    def test_critical_subsets_match_the_committed_theme_sheets(self):
        sheets = json.loads((CRITICAL / "critical-manifest.json").read_text())["sheets"]
        self.assertEqual({"td-theme", "td-legacy-framework-front-style", "td-standard-pack-framework-front-style"}, set(sheets))
        for handle, entry in sheets.items():
            with self.subTest(handle=handle):
                source = ROOT / "wordpress" / entry["source"].removeprefix("/wp-content/")
                # A changed sheet needs: node ops/performance/build-critical-theme-css.mjs
                self.assertEqual(entry["source_sha256"], hashlib.sha256(source.read_bytes()).hexdigest())
                css = (CRITICAL / entry["critical"]).read_text()
                self.assertNotIn("</style", css.lower())
                self.assertNotRegex(css, r"url\(\s*['\"]?(?!['\"]|data:|https?:|/)")

    def test_public_pages_inline_the_subset_and_load_the_full_sheet_without_blocking(self):
        script = r'''
        define('ABSPATH', '/');
        if ($argv[3] === 'on') { define('MANACOST_CRITICAL_THEME_CSS', true); }
        $GLOBALS['filters'] = [];
        $GLOBALS['ver'] = $argv[2];
        function add_filter($name, $callback) { $GLOBALS['filters'][$name] = $callback; }
        function is_admin() { return false; } function is_feed() { return false; } function is_preview() { return false; }
        function is_customize_preview() { return false; } function wp_doing_ajax() { return false; }
        function wp_parse_url($url, $part) { return parse_url($url, $part); }
        function esc_attr($v) { return htmlspecialchars($v, ENT_QUOTES); } function esc_url($v) { return $v; }
        function wp_styles() { return (object) ['registered' => ['td-theme' => (object) ['ver' => $GLOBALS['ver']]]]; }
        require $argv[1];
        $href = 'https://hs-manacost.ru/wp-content/themes/Newspaper_new/style.css?ver=' . $GLOBALS['ver'];
        $tag = "<link rel='stylesheet' id='td-theme-css' href='$href' type='text/css' media='all' />\n";
        $filter = $GLOBALS['filters']['style_loader_tag'];
        $out = ['public' => $filter($tag, 'td-theme', $href, 'all'), 'print' => $filter($tag, 'td-theme', $href, 'print'), 'other' => $filter($tag, 'other', $href, 'all')];
        $_GET['td_action'] = 'tdc_edit';
        $out['composer'] = $filter($tag, 'td-theme', $href, 'all');
        echo json_encode($out);
        '''
        version = json.loads((CRITICAL / "critical-manifest.json").read_text())["sheets"]["td-theme"]["version"]
        run = lambda ver, switch="on": json.loads(subprocess.run(["php", "-r", script, str(PLUGIN), ver, switch], check=True, text=True, capture_output=True).stdout)
        current, stale = run(version), run("0.0.0")
        # Off unless explicitly enabled: it regressed LCP on Slow 4G.
        self.assertNotIn("critical-css", run(version, "off")["public"])
        public = current["public"]
        self.assertTrue(public.startswith('<style id="td-theme-critical-css">'))
        self.assertIn('<link rel="preload" as="style" id="td-theme-css"', public)
        self.assertIn("onload=\"this.onload=null;this.rel='stylesheet'\"", public)
        self.assertIn("<noscript><link rel='stylesheet' id='td-theme-css'", public)
        self.assertLess(public.find("</style>"), public.find('rel="preload"'))
        for key in ["print", "other", "composer"]:
            self.assertNotIn("critical-css", current[key], key)
        self.assertNotIn("critical-css", stale["public"], "a different sheet version keeps the blocking stylesheet")


if __name__ == "__main__":
    unittest.main()
