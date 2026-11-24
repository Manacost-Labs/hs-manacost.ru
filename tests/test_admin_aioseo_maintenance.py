"""Check the request and completion boundaries for AIOSEO maintenance coalescing."""
import json
from pathlib import Path
import subprocess
import unittest


ROOT = Path(__file__).resolve().parents[1]


class AioseoMaintenanceTest(unittest.TestCase):
    def test_only_a_completed_pass_is_reused_on_editorial_gets(self):
        program = r"""
define('ABSPATH', '/fixture/');
define('MINUTE_IN_SECONDS', 60);
function add_filter(...$args) {}
function add_action($hook, $fn, $priority = 10) {
    if ($priority === PHP_INT_MAX) $GLOBALS['completed'] = $fn;
}
function wp_using_ext_object_cache() { return $GLOBALS["cacheEnabled"] ?? true; }
function wp_doing_ajax() { return $GLOBALS["ajax"] ?? false; }
function wp_doing_cron() { return $GLOBALS["cron"] ?? false; }
function aioseo() { return (object) ['version' => $GLOBALS['version']]; }
function get_current_user_id() { return 5; }
function determine_locale() { return 'ru_RU'; }
function wp_cache_get($key, $group) { return $GLOBALS['warm'] ?? false; }
function wp_cache_set($key, $value, $group, $ttl) { $GLOBALS['warm'] = ($ttl === 60 && $value); }
function hs_admin_load_trim_remove_object_callback($hook, $class, $method) {
    $GLOBALS['removed'][] = [$hook, $class, $method];
}
require 'wordpress/mu-plugins/hs-admin-aioseo-throttle.php';
$pagenow = 'post.php';
$version = '4.9.8';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['action' => 'edit', 'post' => '5'];
hs_admin_aioseo_reuse_maintenance_pass();
if (isset($warm) || !isset($completed) || isset($removed)) exit(1);
$completed();
hs_admin_aioseo_reuse_maintenance_pass();
$normal = $removed;
foreach ([['POST', 'post.php', []], ['GET', 'admin.php', []],
          ['GET', 'post.php', ['action' => 'trash']], ['GET', 'index.php', ['page' => 'aioseo']]] as [$verb, $pagenow, $_GET]) {
    $removed = [];
    $_SERVER['REQUEST_METHOD'] = $verb;
    hs_admin_aioseo_reuse_maintenance_pass();
    if ($removed) exit(2);
}
$pagenow = 'post.php';
$_GET = [];
foreach ([[true, false, true], [false, true, true], [false, false, false]] as [$ajax, $cron, $cacheEnabled]) {
    hs_admin_aioseo_reuse_maintenance_pass();
    if ($removed) exit(4);
}
$cacheEnabled = true;
$version = 'next';
hs_admin_aioseo_reuse_maintenance_pass();
if ($removed) exit(3);
echo json_encode($normal);
"""
        rows = json.loads(subprocess.check_output(["php", "-r", program], cwd=ROOT))
        self.assertIn(["admin_init", r"AIOSEO\Plugin\Common\Sitemap\Image\Image", "scheduleScan"], rows)
        self.assertTrue(all(hook == "admin_init" for hook, _, _ in rows))
