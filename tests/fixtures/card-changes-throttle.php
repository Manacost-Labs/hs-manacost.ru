<?php
// Per-editor search limit: with a persistent object cache the counter is an atomic increment, so a request that
// read the counter before another one finished cannot overwrite it (the old get/set pair lost increments).
define('ABSPATH', '/');
define('MINUTE_IN_SECONDS', 60);
function check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException('FAIL: ' . $message); }
$external = true; $objects = []; $transients = [];
function wp_using_ext_object_cache() { return $GLOBALS['external']; }
function wp_cache_add($key, $value, $group = '', $ttl = 0) { if (isset($GLOBALS['objects']["$group:$key"])) return false; $GLOBALS['objects']["$group:$key"] = $value; return true; }
function wp_cache_incr($key, $offset = 1, $group = '') { if (!isset($GLOBALS['objects']["$group:$key"])) return false; return $GLOBALS['objects']["$group:$key"] += $offset; }
function get_transient($key) { return $GLOBALS['transients'][$key] ?? false; }
function set_transient($key, $value, $ttl) { $GLOBALS['transients'][$key] = $value; }
function get_current_user_id() { return 7; }
function add_action(...$a) {}
require dirname(__DIR__, 2) . '/wordpress/mu-plugins/hs-card-changes/admin.php';
$throttled = (new ReflectionMethod('HS_Card_Changes_Admin', 'throttled'));
$throttled->setAccessible(true);
$limit = (new ReflectionClassConstant('HS_Card_Changes_Admin', 'SEARCHES_PER_MINUTE'))->getValue();
$allowed = 0;
for ($i = 0; $i < $limit + 3; $i++) $allowed += $throttled->invoke(null, 'search') ? 0 : 1;
check($allowed === $limit, "Exactly the limit is allowed per minute with an object cache: $allowed of $limit");
check($transients === [], 'With an object cache the counter does not use transients');
// A count stored by a parallel request between our read and write is never lost: the store is incremented, not set.
$key = array_key_first($objects);
$before = $objects[$key];
$throttled->invoke(null, 'search');
check($objects[$key] === $before + 1, 'Each request adds one to the shared counter');
$external = false; $transients = [];
$allowed = 0;
for ($i = 0; $i < $limit + 3; $i++) $allowed += $throttled->invoke(null, 'search') ? 0 : 1;
check($allowed === $limit, "Without an object cache the transient fallback still enforces the limit: $allowed of $limit");
echo "PASS\n";
