<?php
// Standalone fake WordPress HTTP/cache boundary; production plugin runs unchanged.
define('ABSPATH', '/');
$scenario = $argv[1];
if ($scenario === 'disabled') {
    define('MANACOST_KOLODA_API_ENABLED', false);
}
$cache = [];
$calls = [];
$events = [];
$response = ['response' => ['code' => 200], 'headers' => ['content-type' => 'application/json'], 'body' => '{"data":[{"card_id":"TEST_001"}]}'];
class WP_Error {
    public function __construct(public $code, public $message = '', public $data = []) {}
    public function get_error_code() { return $this->code; }
    public function get_error_data() { return $this->data; }
}
function is_wp_error($value) { return $value instanceof WP_Error; }
function __($text, $domain = '') { return $text; }
function get_transient($key) { return $GLOBALS['cache'][$key]['value'] ?? false; }
function set_transient($key, $value, $ttl) { $GLOBALS['cache'][$key] = ['value' => $value, 'ttl' => $ttl]; return true; }
function delete_transient($key) { unset($GLOBALS['cache'][$key]); return true; }
function wp_safe_remote_get($url, $args) { $GLOBALS['calls'][] = [$url, $args]; return $GLOBALS['response']; }
function wp_remote_retrieve_body($r) { return $r['body']; }
function wp_remote_retrieve_response_code($r) { return $r['response']['code']; }
function wp_remote_retrieve_header($r, $name) { return $r['headers'][$name] ?? ''; }
function add_query_arg($query, $url) { return $url . ($query ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : ''); }
function do_action($hook, ...$args) { $GLOBALS['events'][] = [$hook, $args]; }
function check($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
require dirname(__DIR__, 2) . '/wordpress/mu-plugins/manacost-koloda-api.php';
check(count($calls) === 0, 'Loading must not fetch data');
$path = '/api/v1/constructed-cards';
$query = ['per_page' => 1, 'page' => 1];
$seed = in_array($scenario, ['warm', 'query_order', 'query_isolation', 'stale', 'recovery'], true);
if ($seed) {
    $first = Manacost_Koloda_API::get($path, $query);
    check(!is_wp_error($first), 'Seed succeeds');
    if (in_array($scenario, ['stale', 'recovery'], true)) {
        foreach ($cache as &$entry) {
            if (isset($entry['value']['fresh_until'])) { $entry['value']['fresh_until'] = time() - 1; }
        }
        unset($entry);
    }
}
switch ($scenario) {
    case 'empty': $response['body'] = '{"data":[]}'; break;
    case 'query_order': $query = ['page' => 1, 'per_page' => 1]; break;
    case 'query_isolation': $query['page'] = 2; break;
    case 'invalid_path':
        foreach (['https://localhost/', '/admin/api-tokens', '/v1/auth/token', '/api/v1/cards/../meta', '/api/v1/cards%2f..', '/api/v1/cards?foo=bar'] as $bad) {
            check(is_wp_error(Manacost_Koloda_API::get($bad)), 'Reject unsafe path');
        }
        check(count($calls) === 0, 'Unsafe paths cause no HTTP'); echo "PASS\n"; exit;
    case 'invalid_query': $query = ['api_key' => 'do-not-send']; break;
    case 'pagination': $query = ['per_page' => 10001]; break;
    case 'timeout': case 'dns': case 'tls': case 'stale': case 'cooldown': case 'recovery':
        $response = new WP_Error('http_request_failed', 'private transport diagnostic'); break;
    case 'unauthorized': $response['response']['code'] = 401; break;
    case 'forbidden': $response['response']['code'] = 403; break;
    case 'not_found': $response['response']['code'] = 404; break;
    case 'redirect': $response['response']['code'] = 302; break;
    case 'rate_limit': case 'retry_date':
        $response['response']['code'] = 429;
        $response['headers']['retry-after'] = $scenario === 'retry_date' ? gmdate('D, d M Y H:i:s', time() + 120) . ' GMT' : '120';
        break;
    case 'server_error': $response['response']['code'] = 503; break;
    case 'malformed': $response['body'] = '{broken'; break;
    case 'wrong_type': $response['headers']['content-type'] = 'text/html'; break;
    case 'too_large': $response['body'] = str_repeat('a', 2097153); break;
}
$result = Manacost_Koloda_API::get($path, $query);
if (in_array($scenario, ['success', 'empty', 'warm', 'query_order', 'query_isolation', 'stale', 'recovery'], true)) {
    check(!is_wp_error($result), 'Returns data');
    check($result['stale'] === in_array($scenario, ['stale', 'recovery'], true), 'Freshness is explicit');
    if ($scenario === 'empty') { check($result['data']['data'] === [], 'Empty results are valid'); }
    if (in_array($scenario, ['warm', 'query_order'], true)) { check(count($calls) === 1, 'Warm cache avoids HTTP'); }
    if ($scenario === 'query_isolation') { check(count($calls) === 2, 'Pages have separate cache'); }
} else {
    check(is_wp_error($result), 'Returns controlled error');
    check(!str_contains($result->message, 'private'), 'No transport diagnostic leaks');
}
if (in_array($scenario, ['disabled', 'invalid_query', 'pagination'], true)) {
    check(count($calls) === 0, 'Disabled/invalid requests cause no HTTP');
}
if (in_array($scenario, ['cooldown', 'rate_limit', 'retry_date'], true)) {
    $second = Manacost_Koloda_API::get($path, $query);
    check(is_wp_error($second) && count($calls) === 1, 'Cooldown prevents retries');
    if ($scenario !== 'cooldown') {
        $other = Manacost_Koloda_API::get('/v1/arena/classes', ['limit' => 1]);
        check(is_wp_error($other) && count($calls) === 1, '429 applies across resources');
        check($result->get_error_data()['retry_after'] >= 119, 'Honours Retry-After');
    }
}
if ($scenario === 'recovery') {
    foreach ($cache as $key => $entry) {
        if ($entry['value'] instanceof WP_Error) { unset($cache[$key]); }
    }
    $response = ['response' => ['code' => 200], 'headers' => ['content-type' => 'application/json'], 'body' => '{"data":[{"card_id":"RECOVERED"}]}'];
    $result = Manacost_Koloda_API::get($path, $query);
    check(!$result['stale'] && $result['data']['data'][0]['card_id'] === 'RECOVERED', 'Recovers after cooldown');
}
foreach ($calls as [$url, $args]) {
    check(str_starts_with($url, 'https://api.kolodahearthstone.com/'), 'Host is fixed');
    check($args['redirection'] === 0 && $args['timeout'] <= 5 && $args['sslverify'], 'Bounded secure HTTP');
    check(!isset($args['headers']['Cookie']) && !isset($args['headers']['Authorization']), 'No site credentials forwarded');
}
echo "PASS\n";
