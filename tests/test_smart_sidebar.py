import json
import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / 'wordpress/mu-plugins/manacost-smart-sidebar.php'


class SmartSidebarTest(unittest.TestCase):
    def settings(self, value, *, admin=False, theme='Newspaper_new'):
        script = f'''
        define('ABSPATH', '/');
        $filters = [];
        $actions = [];
        $styles = [];
        $scripts = [];
        function add_filter($hook, $callback) {{ $GLOBALS['filters'][$hook] = $callback; }}
        function add_action($hook, $callback) {{ $GLOBALS['actions'][$hook] = $callback; }}
        function plugin_dir_url($file) {{ return '/wp-content/mu-plugins/'; }}
        function wp_enqueue_style($handle, $src, $deps, $version) {{ $GLOBALS['styles'][$handle] = $src; }}
        function wp_enqueue_script($handle, $src, $deps, $version, $footer) {{ $GLOBALS['scripts'][$handle] = ['src' => $src, 'deps' => $deps, 'footer' => $footer]; }}
        function is_admin() {{ return {json.dumps(admin)}; }}
        function get_template() {{ return {json.dumps(theme)}; }}
        require {json.dumps(str(PLUGIN))};
        $original = json_decode({json.dumps(json.dumps(value))}, true);
        $result = call_user_func($filters['option_td_011'], $original);
        call_user_func($actions['wp_enqueue_scripts']);
        echo json_encode(['result' => $result, 'original' => $original, 'styles' => $styles, 'scripts' => $scripts]);
        '''
        completed = subprocess.run([shutil.which('php'), '-r', script], capture_output=True, text=True)
        self.assertEqual(completed.returncode, 0, completed.stderr)
        return json.loads(completed.stdout)

    def test_enables_native_following_without_mutating_stored_settings(self):
        stored = {'tds_smart_sidebar': '', 'tds_snap_menu': 'smart_snap_always'}
        result = self.settings(stored)
        self.assertEqual(result['result'], {**stored, 'tds_smart_sidebar': 'enabled'})
        self.assertEqual(result['original'], stored)

    def test_admin_and_other_themes_keep_their_settings(self):
        stored = {'tds_smart_sidebar': ''}
        self.assertEqual(self.settings(stored, admin=True)['result'], stored)
        self.assertEqual(self.settings(stored, theme='twentytwentysix')['result'], stored)

    def test_malformed_settings_are_preserved(self):
        for value in (False, None, 'invalid'):
            with self.subTest(value=value):
                self.assertEqual(self.settings(value)['result'], value)

    def test_keyboard_styles_only_load_for_public_newspaper(self):
        self.assertEqual(self.settings({})['styles'], {
            'manacost-smart-sidebar': '/wp-content/mu-plugins/manacost-smart-sidebar.css'
        })
        self.assertEqual(self.settings({}, admin=True)['styles'], [])
        self.assertEqual(self.settings({}, theme='twentytwentysix')['styles'], [])

    def test_stable_controller_depends_on_the_native_library_and_is_public_only(self):
        self.assertEqual(self.settings({})['scripts'], {
            'manacost-smart-sidebar': {
                'src': '/wp-content/mu-plugins/manacost-smart-sidebar.js',
                'deps': ['tdSmartSidebar'], 'footer': True
            }
        })
        self.assertEqual(self.settings({}, admin=True)['scripts'], [])
        self.assertEqual(self.settings({}, theme='twentytwentysix')['scripts'], [])

    def test_article_html_trim_keeps_the_native_sidebar_script(self):
        sidebar = '<script src="/tdSmartSidebar.js" id="tdSmartSidebar-js"></script>'
        unused = '<script src="/tdToTop.js" id="tdToTop-js"></script>'
        counter = '<script src="/tdAjaxCount.js" id="tdAjaxCount-js"></script>'
        trim = ROOT / 'wordpress/mu-plugins/hs-manacost-newspaper-trim.php'
        script = f'''
        define('ABSPATH', '/');
        function add_filter(...$args) {{}}
        function add_action(...$args) {{}}
        function is_admin() {{ return false; }}
        function is_feed() {{ return false; }}
        function is_preview() {{ return false; }}
        function is_single() {{ return true; }}
        require {json.dumps(str(trim))};
        echo hs_manacost_newspaper_trim_front_html({json.dumps(sidebar + unused + counter)});
        '''
        completed = subprocess.run([shutil.which('php'), '-r', script], capture_output=True, text=True)
        self.assertEqual(completed.returncode, 0, completed.stderr)
        self.assertEqual(completed.stdout, sidebar)
