"""Execute the WordPress bootstrap with isolated public-hook ports, never live user data."""
import json
from pathlib import Path
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[1]
LOADER = ROOT / 'wordpress/mu-plugins/hs-manacost-reader.php'

class ReaderLoaderTests(unittest.TestCase):
    def run_loader(self, enabled, host='hs-manacost.ru'):
        php = '''<?php
define('ABSPATH', '/fixture/');
define('HS_MANACOST_READER_ENABLED', ENABLED);
$_SERVER['HTTP_HOST'] = HOST;
$hooks = array(); $shortcodes = array();
function add_action($name, $callback, ...$rest) { global $hooks; $hooks[] = $name; }
function add_filter($name, $callback, ...$rest) { global $hooks; $hooks[] = $name; }
function add_shortcode($name, $callback) { global $shortcodes; $shortcodes[] = $name; }
function wp_unslash($value) { return stripslashes($value); }
require LOADER;
hs_manacost_reader_bootstrap();
echo json_encode(array('hooks' => $hooks, 'shortcodes' => $shortcodes));
'''.replace('ENABLED);', ('true' if enabled else 'false') + ');').replace('HOST;', json.dumps(host) + ';').replace('require LOADER;', 'require ' + json.dumps(str(LOADER)) + ';')
        result = subprocess.run(['php'], input=php, text=True, capture_output=True, check=True)
        return json.loads(result.stdout)

    def test_disabled_flag_registers_no_reader_ui_or_auth(self):
        result = self.run_loader(False)
        self.assertEqual(result['shortcodes'], [])
        self.assertEqual(result['hooks'], ['rocket_cache_reject_uri', 'init'])

    def test_enabled_bootstrap_uses_public_hooks_and_shortcode(self):
        result = self.run_loader(True)
        self.assertEqual(result['shortcodes'], ['hs_manacost_reader_account'])
        self.assertIn('wp_nav_menu_items', result['hooks'])
        self.assertIn('wp_enqueue_scripts', result['hooks'])
        source = LOADER.read_text()
        for forbidden in ('wp_insert_user', 'wp_signon', 'wp_set_auth_cookie', 'comments_open'):
            self.assertNotIn(forbidden, source)

    def test_mirror_registers_only_the_fail_closed_account_policy(self):
        result = self.run_loader(True, 'hs-manacost.com')
        self.assertEqual(result['shortcodes'], [])
        self.assertEqual(result['hooks'], ['rocket_cache_reject_uri', 'init', 'template_redirect'])
