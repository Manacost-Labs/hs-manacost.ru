from __future__ import annotations

import shutil
import subprocess
import tempfile
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/hs-admin-media-months-cache.php"

HARNESS = r"""<?php
const ABSPATH = '/'; const DB_NAME = 'fixture'; const MINUTE_IN_SECONDS = 60;
class wpdb { public $prefix = 'one_'; }
$wpdb = new wpdb();
$cache = []; $hooks = []; $persistent = true; $admin = true; $allowed = true; $fail = false; $lose_add = false;
function add_filter($hook, $cb, $priority = 10, $argc = 1) {
 global $wp_filter; $wp_filter[$hook] ??= (object) ['callbacks' => []];
 $wp_filter[$hook]->callbacks[$priority][implode('::', $cb)] = ['function' => $cb];
}
function add_action(...$args) { add_filter(...$args); }
function has_filter($hook) { return false; }
function is_admin() { return $GLOBALS['admin']; }
function current_user_can($cap) { return $GLOBALS['allowed']; }
function wp_using_ext_object_cache() { return $GLOBALS['persistent']; }
function wp_generate_uuid4() { static $n = 0; return sprintf('00000000-0000-4000-8000-%012d', ++$n); }
function wp_cache_get_multiple($keys, $group, $force = false) { $r = []; foreach ($keys as $k) $r[$k] = $GLOBALS['cache'][$group][$k] ?? false; return $r; }
function wp_cache_get($key, $group = '', $force = false) { return $GLOBALS['cache'][$group][$key] ?? false; }
function wp_cache_add($key, $value, $group = '', $ttl = 0) { if ($GLOBALS['lose_add'] || isset($GLOBALS['cache'][$group][$key])) return false; return wp_cache_set($key, $value, $group, $ttl); }
function wp_cache_set($key, $value, $group = '', $ttl = 0) { if ($GLOBALS['fail']) return false; $GLOBALS['cache'][$group][$key] = $value; return true; }
function wp_cache_delete($key, $group = '') { unset($GLOBALS['cache'][$group][$key]); return true; }
function check($ok, $why) { if (!$ok) throw new RuntimeException($why); }
require $argv[1];
$rows = [(object) ['year'=>'2026','month'=>'10','text'=>'translated'], (object) ['year'=>'2025','month'=>'1','text'=>'other']];
check(null === HS_Admin_Media_Months_Cache::months(null), 'cold fallback');
HS_Admin_Media_Months_Cache::capture(['months'=>$rows]);
$warm = HS_Admin_Media_Months_Cache::months(null);
check(is_array($warm) && count($warm) === 2 && $warm[0]->year === '2026' && !isset($warm[0]->text), 'raw warm rows');
$warm[0]->year = '1900';
check(HS_Admin_Media_Months_Cache::months(null)[0]->year === '2026', 'independent rows');
check(['override'] === HS_Admin_Media_Months_Cache::months(['override']), 'override');
$admin = false; check(null === HS_Admin_Media_Months_Cache::months(null), 'frontend'); $admin = true;
$allowed = false; check(null === HS_Admin_Media_Months_Cache::months(null), 'role'); $allowed = true;
$persistent = false; check(null === HS_Admin_Media_Months_Cache::months(null), 'nonpersistent'); $persistent = true;
HS_Admin_Media_Months_Cache::invalidate();
check(null === HS_Admin_Media_Months_Cache::months(null), 'invalidation');
HS_Admin_Media_Months_Cache::invalidate(); // Writer still holds the previous generation.
HS_Admin_Media_Months_Cache::capture(['months'=>$rows]);
check(null === HS_Admin_Media_Months_Cache::months(null), 'late writer');
HS_Admin_Media_Months_Cache::capture(['months'=>[]]);
check(null === HS_Admin_Media_Months_Cache::months(null), 'empty not cached');
HS_Admin_Media_Months_Cache::capture(['months'=>[(object) ['year'=>'2026','month'=>'99']]]);
check(null === HS_Admin_Media_Months_Cache::months(null), 'invalid not cached');
HS_Admin_Media_Months_Cache::capture(['months'=>$rows]);
$wpdb->prefix = 'two_'; check(null === HS_Admin_Media_Months_Cache::months(null), 'blog isolation');
$fail = true; HS_Admin_Media_Months_Cache::capture(['months'=>$rows]);
check(null === HS_Admin_Media_Months_Cache::months(null), 'write failure');
$fail = false;
$wpdb->prefix = 'race_'; $lose_add = true;
check(null === HS_Admin_Media_Months_Cache::months(null), 'lost add with no readback');
HS_Admin_Media_Months_Cache::capture(['months'=>$rows]);
check(empty($cache['hs_media_months_' . hash('sha256', 'v1' . DB_NAME . "\0" . $wpdb->prefix)]), 'failed initialization not captured');
$lose_add = false;
$wpdb->prefix = 'one_';
HS_Admin_Media_Months_Cache::invalidate();
HS_Admin_Media_Months_Cache::months(null);
HS_Admin_Media_Months_Cache::capture(['months'=>$rows]);
$group = 'hs_media_months_' . hash('sha256', 'v1' . DB_NAME . "\0" . $wpdb->prefix);
$cache[$group]['payload']['built_at'] = time() - 600;
check(null === HS_Admin_Media_Months_Cache::months(null), 'expired');
HS_Admin_Media_Months_Cache::capture(['months'=>$rows]);
$cache[$group]['payload']['built_at'] = time() + 1;
check(null === HS_Admin_Media_Months_Cache::months(null), 'future timestamp');
$wp_filter['media_library_months_with_files']->callbacks[PHP_INT_MAX]['other'] = ['function'=>'third_party'];
check(null === HS_Admin_Media_Months_Cache::months(null), 'ambiguous provider');
unset($wp_filter['media_library_months_with_files']->callbacks[PHP_INT_MAX]['other']);
$wp_filter['query'] = (object) ['callbacks'=>[10=>[['function'=>'per_user_sql']]]];
check(null === HS_Admin_Media_Months_Cache::months(null), 'custom SQL filter');
unset($wp_filter['query']);
HS_Admin_Media_Months_Cache::invalidate();
HS_Admin_Media_Months_Cache::months(null);
$wp_filter['media_view_settings']->callbacks[PHP_INT_MIN]['other'] = ['function'=>'third_party'];
HS_Admin_Media_Months_Cache::capture(['months'=>$rows]);
unset($wp_filter['media_view_settings']->callbacks[PHP_INT_MIN]['other']);
check(null === HS_Admin_Media_Months_Cache::months(null), 'ambiguous capture');
$fail = true; HS_Admin_Media_Months_Cache::invalidate();
check(!isset($cache[$group]['epoch']) && !isset($cache[$group]['payload']), 'failed set invalidation deletes');
echo "PASS";
"""


class AdminMediaMonthsCacheTest(unittest.TestCase):
    def test_cache_preserves_core_rows_and_rejects_stale_or_untrusted_results(self):
        with tempfile.TemporaryDirectory() as folder:
            fixture = Path(folder) / "fixture.php"
            fixture.write_text(HARNESS)
            result = subprocess.run(
                [shutil.which("php") or "/usr/bin/php", str(fixture), str(PLUGIN)],
                capture_output=True, text=True, timeout=10, check=False,
            )
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertEqual(result.stdout, "PASS")


if __name__ == "__main__":
    unittest.main()
