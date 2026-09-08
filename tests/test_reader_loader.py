"""Execute the WordPress bootstrap with isolated public-hook ports, never live user data."""
import json
from pathlib import Path
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[1]
LOADER = ROOT / 'wordpress/mu-plugins/hs-manacost-reader.php'

class ReaderLoaderTests(unittest.TestCase):
    def run_loader(self, enabled):
        php = '''<?php
define('ABSPATH', '/fixture/');
define('HS_MANACOST_READER_ENABLED', ENABLED);
$hooks = array(); $shortcodes = array();
function add_action($name, $callback, ...$rest) { global $hooks; $hooks[] = $name; }
function add_filter($name, $callback, ...$rest) { global $hooks; $hooks[] = $name; }
function add_shortcode($name, $callback) { global $shortcodes; $shortcodes[] = $name; }
require LOADER;
hs_manacost_reader_bootstrap();
echo json_encode(array('hooks' => $hooks, 'shortcodes' => $shortcodes));
'''.replace('ENABLED);', ('true' if enabled else 'false') + ');').replace('require LOADER;', 'require ' + json.dumps(str(LOADER)) + ';')
        result = subprocess.run(['php'], input=php, text=True, capture_output=True, check=True)
        return json.loads(result.stdout)

    def test_disabled_flag_registers_no_reader_ui_or_auth(self):
        result = self.run_loader(False)
        self.assertEqual(result['shortcodes'], [])
        self.assertEqual(result['hooks'], ['init'])

    def test_enabled_bootstrap_uses_public_hooks_and_shortcode(self):
        result = self.run_loader(True)
        self.assertEqual(result['shortcodes'], ['hs_manacost_reader_account'])
        self.assertIn('wp_nav_menu_items', result['hooks'])
        self.assertIn('wp_enqueue_scripts', result['hooks'])
        source = LOADER.read_text()
        for forbidden in ('wp_insert_user', 'wp_signon', 'wp_set_auth_cookie', 'comments_open'):
            self.assertNotIn(forbidden, source)
