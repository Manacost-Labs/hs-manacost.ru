<?php
// Only WordPress HTTP/cache/escaping/shortcode boundaries are substituted; responses are recorded provider data.
define('ABSPATH', '/');
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);
define('DAY_IN_SECONDS', 86400);
if (($argv[1] ?? '') === 'disabled') define('HS_CARD_CHANGES_REMOTE_ENABLED', false);
$cache = []; $calls = []; $events = []; $responses = []; $batches = []; $scheduled = []; $posts = []; $ttl = []; $main_query = false; $passthrough = false; $purged = [];
class WP_Error {
    public function __construct(public $code = '', public $message = '') {}
}
function __($text, $domain = '') { return $text; }
function is_wp_error($v) { return $v instanceof WP_Error; }
function get_transient($key) { return $GLOBALS['cache'][$key] ?? false; }
function set_transient($key, $value, $ttl) { $GLOBALS['cache'][$key] = $value; $GLOBALS['ttl'][$key] = $ttl; }
function apply_filters($hook, $value, ...$args) {
    if ($hook !== 'pre_http_request') return $value;
    if (!empty($GLOBALS['passthrough'])) return false;
    $GLOBALS['calls'][] = $args[1];
    return $GLOBALS['responses'][$args[1]] ?? new WP_Error('http_request_failed', 'private transport details');
}
function delete_transient($key) { unset($GLOBALS['cache'][$key]); }
function wp_using_ext_object_cache() { return false; }
function wp_schedule_single_event($time, $hook, $args = []) { $GLOBALS['scheduled'][] = [$hook, $args]; return true; }
function wp_get_environment_type() { return 'production'; }
function is_main_query() { return $GLOBALS['main_query']; }
function is_singular() { return $GLOBALS['main_query']; }
function in_the_loop() { return $GLOBALS['main_query']; }
function is_feed() { return false; }
function wp_is_json_request() { return false; }
function wp_is_post_revision($id) { return false; }
function wp_is_post_autosave($id) { return false; }
class WP_Post { public $post_type = 'post'; public $post_status = 'draft'; public $post_content = ''; }
function get_post($id) { return $GLOBALS['posts'][$id] ?? null; }
function do_action($hook, ...$args) { $GLOBALS['events'][] = [$hook, $args]; if ($hook === 'hs_card_changes_batch') $GLOBALS['batches'][] = $args[0]; }
function wp_remote_retrieve_body($r) { return $r['body']; }
function wp_remote_retrieve_header($r, $key) { return $r['headers'][$key] ?? ''; }
function add_shortcode($tag, $cb) {}
function add_filter(...$a) {}
function add_action(...$a) {}
function has_shortcode($content, $tag) { return str_contains($content, '[' . $tag); }
function wp_enqueue_style(...$a) { $GLOBALS['styles'][] = $a[0]; }
function plugins_url($path = '', $file = '') { return 'https://example.test/' . $path; }
function get_the_ID() { return 77; }
function get_queried_object_id() { return 77; }
function clean_post_cache($id) { $GLOBALS['purged'][] = $id; }
function wp_json_encode($v) { return json_encode($v); }
function get_post_field($field, $id) { return $id === 78 ? '0000-00-00 00:00:00' : '2026-11-03 15:55:00'; }
function get_shortcode_regex($tags) { return '\[(\[?)(' . implode('|', $tags) . ')(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*+(?:\[(?!\/\2\])[^\[]*+)*+)\[\/\2\])?)(\]?)'; }
function shortcode_parse_atts($text) { preg_match_all('/([\w-]+)="([^"]*)"/u', $text, $m, PREG_SET_ORDER); $a = []; foreach ($m as $x) $a[$x[1]] = $x[2]; return $a; }
function esc_html($v) { return htmlspecialchars($v, ENT_QUOTES); }
function esc_attr($v) { return htmlspecialchars($v, ENT_QUOTES); }
function esc_url($v) { return htmlspecialchars($v, ENT_QUOTES); }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function esc_url_raw($v) { return preg_match('/[\x00-\x20<>"]/', $v) ? '' : $v; }
function check($v, $message) { if (!$v) throw new RuntimeException('FAIL: ' . $message); }
