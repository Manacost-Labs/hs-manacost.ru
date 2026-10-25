<?php
// Real tooltip rebuild against an isolated filesystem and fake HTTP/cache boundary.
define('ABSPATH', '/');
define('HOUR_IN_SECONDS', 3600);
define('MINUTE_IN_SECONDS', 60);
define('DAY_IN_SECONDS', 86400);
$bgs = str_starts_with($argv[1], 'bg_');
$scenario = $argv[1] === 'render' ? 'success' : ($bgs ? substr($argv[1], 3) : $argv[1]);
if ($scenario === 'disabled') { define('MANACOST_KOLODA_API_ENABLED', false); }
$cache = [];
$http_calls = [];
class WP_Error {
    public function __construct(public $code, public $message = '', public $data = []) {}
    public function get_error_code() { return $this->code; }
    public function get_error_data() { return $this->data; }
    public function get_error_message() { return $this->message; }
}
function is_wp_error($v) { return $v instanceof WP_Error; }
function __($text, $domain = '') { return $text; }
function add_action(...$args) {}
function add_filter(...$args) {}
function add_shortcode(...$args) {}
function register_deactivation_hook(...$args) {}
function do_action(...$args) {}
function get_transient($key) { return $GLOBALS['cache'][$key] ?? false; }
function set_transient($key, $value, $ttl) { $GLOBALS['cache'][$key] = $value; return true; }
function delete_transient($key) { unset($GLOBALS['cache'][$key]); return true; }
function wp_cache_get($key, $group, $force, &$found) { $found = false; return false; }
function wp_cache_set(...$args) { return true; }
function wp_cache_delete(...$args) { return true; }
function wp_json_encode($v, $flags = 0) { return json_encode($v, $flags); }
function wp_parse_url($url) { return parse_url($url); }
function wp_strip_all_tags($text) { return strip_tags($text); }
function plugin_dir_path($file) { return dirname($file) . '/'; }
function plugin_dir_url($file) { return 'https://test.hs-manacost.ru/plugin/'; }
function add_query_arg($query, $url) { return $url . '?' . http_build_query($query); }
function wp_remote_retrieve_body($r) { return $r['body']; }
function wp_remote_retrieve_response_code($r) { return $r['response']['code']; }
function wp_remote_retrieve_header($r, $name) { return $r['headers'][$name] ?? ''; }
function esc_url($v) { return htmlspecialchars($v, ENT_QUOTES); }
function esc_attr($v) { return htmlspecialchars($v, ENT_QUOTES); }
function esc_html($v) { return htmlspecialchars($v, ENT_QUOTES); }
function home_url($path) { return (getenv('HS_TOOLTIP_TEST_HOME') ?: 'https://test.hs-manacost.ru') . $path; }
function wp_unslash($v) { return $v; }
function status_header($code) { $GLOBALS['status'] = $code; }
function wp_kses($v, $tags) { return strip_tags($v, '<b><strong>'); }
function shortcode_atts($defaults, $atts, $shortcode) { return array_merge($defaults, $atts); }
function sanitize_file_name($v) { return basename($v); }
function check($ok, $message) { if (!$ok) { throw new RuntimeException($message); } }
function wp_safe_remote_get($url, $args) {
    $GLOBALS['http_calls'][] = $url;
    if ($GLOBALS['scenario'] === 'proxy') {
        check($args['redirection'] === 0, 'Koloda proxy must not follow redirects');
        return ['response' => ['code' => 200], 'headers' => ['content-type' => 'image/png'], 'body' => base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aL1cAAAAASUVORK5CYII=')];
    }
    if (str_contains($url, 'api.hearthstonejson.com')) {
        return ['response' => ['code' => 200], 'body' => json_encode(str_contains($url, 'cards.collectible.json') ? array_values(array_filter($GLOBALS['hsjson'], fn($card) => !empty($card['collectible']))) : $GLOBALS['hsjson'])];
    }
    $scenario = $GLOBALS['scenario'];
    if ($scenario === 'timeout') { return new WP_Error('http_request_failed'); }
    parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
    $page = (int) ($query['page'] ?? 1);
    $all = str_contains($url, '/api/v1/cards?') ? $GLOBALS['bg_rows'] : $GLOBALS['rows'];
    $size = str_contains($url, '/api/v1/cards?') ? 1 : 2;
    $rows = array_slice($all, ($page - 1) * $size, $size);
    $pagination = ['page' => $page, 'per_page' => $size, 'total' => count($all), 'total_pages' => 2, 'has_next' => $page === 1];
    if ($scenario === 'partial' && $page === 2) { array_pop($rows); }
    if ($scenario === 'duplicate' && $page === 2) { $rows[0] = $all[0]; }
    if ($scenario === 'wrong_page') { $pagination['page'] = 99; }
    if ($scenario === 'empty') { $rows = []; $pagination['total'] = 0; $pagination['total_pages'] = 1; $pagination['has_next'] = false; }
    if ($scenario === 'unsafe_image') { $rows[0]['images']['card'] = 'https://localhost/private.png'; }
    $code = ['unauthorized' => 401, 'rate_limit' => 429, 'server_error' => 503][$scenario] ?? 200;
    return ['response' => ['code' => $code], 'headers' => ['content-type' => 'application/json'],
        'body' => $scenario === 'malformed' ? '{broken' : json_encode(['data' => $rows, 'pagination' => $pagination])];
}
$root = dirname(__DIR__, 2);
$source = $root . '/wordpress/plugins/hs-tooltip';
$temp = sys_get_temp_dir() . '/tooltip-koloda-' . bin2hex(random_bytes(8));
mkdir($temp . '/includes', 0700, true);
register_shutdown_function(function () use ($temp) {
    foreach (glob($temp . '/includes/*') as $file) { unlink($file); }
    foreach (glob($temp . '/*') as $file) { if (is_file($file)) { unlink($file); } }
    rmdir($temp . '/includes'); rmdir($temp);
});
copy($source . '/hs-smart-tooltip.php', $temp . '/hs-smart-tooltip.php');
foreach (glob($source . '/includes/*.php') as $file) { copy($file, $temp . '/includes/' . basename($file)); }
$rows = json_decode(file_get_contents(__DIR__ . '/tooltip-koloda-cards.json'), true);
$bg_rows = json_decode(file_get_contents(__DIR__ . '/tooltip-koloda-bgs-cards.json'), true);
$bg_rows[] = ['card_id' => 'BG19_010', 'dbf' => 64038, 'name' => ['ru' => 'Сточная крыса'], 'card_type' => ['slug' => 'minion'], 'tavern_tier' => 2, 'images' => ['card' => 'https://api.kolodahearthstone.com/uploads/cards/BG19_010.png']];
$bg_old = json_decode(file_get_contents($source . '/hs_bgs_dictionary.json'), true);
unset($bg_old['by_id']['BG36_111']);
$bg_before = json_encode($bg_old, JSON_UNESCAPED_UNICODE);
file_put_contents($temp . '/hs_bgs_dictionary.json', $bg_before);
$old = json_decode(file_get_contents($source . '/hs_dictionary.json'), true);
foreach ($rows as $row) {
    unset($old['by_id'][$row['card_id']], $old['by_id'][$row['dbf']]);
    unset($old['by_name'][mb_strtolower($row['name']['ru'], 'UTF-8')]);
}
if ($scenario === 'tokens') {
    unset($old['by_id']['BE_222t'], $old['by_id'][130406], $old['by_name'][mb_strtolower('Безднокрыл', 'UTF-8')]);
}
$before = json_encode($old, JSON_UNESCAPED_UNICODE);
file_put_contents($temp . '/hs_dictionary.json', $before);
$hsjson = [];
$numeric_ids = [];
foreach ($old['by_id'] as $id => $entry) {
    if (is_numeric($id)) { $numeric_ids[json_encode($entry)] = $id; }
}
foreach ($old['by_id'] as $id => $entry) {
    if (is_numeric($id)) { continue; }
    $hsjson[] = ['collectible' => true, 'id' => $id, 'dbfId' => $numeric_ids[json_encode($entry)] ?? '', 'name' => $entry['name'], 'type' => 'MINION', 'set' => 'TEST', 'rarity' => strtoupper($entry['rarity'])];
}
if ($scenario === 'tokens') {
    $hsjson[] = ['id' => 'BE_222t', 'dbfId' => 130406, 'name' => 'Безднокрыл', 'type' => 'MINION', 'set' => 'BE'];
}
require $root . '/wordpress/mu-plugins/manacost-koloda-api.php';
require $temp . '/hs-smart-tooltip.php';
check($http_calls === [], 'Loading must not fetch remote cards');
if ($scenario === 'proxy' || $scenario === 'proxy_reject') {
    $_GET['hs_tooltip_img'] = $argv[2];
    register_shutdown_function(function () use ($scenario) {
        check(count($GLOBALS['http_calls']) === ($scenario === 'proxy' ? 1 : 0), 'Only approved image URLs can reach HTTP');
        check(($GLOBALS['status'] ?? 200) === ($scenario === 'proxy' ? 200 : 403), 'Proxy returns expected status');
    });
    hs_smart_tooltip_handle_image_proxy();
    throw new RuntimeException('Image proxy must handle the request');
}
if ($scenario === 'stale') {
    foreach ([1, 2] as $page) { Manacost_Koloda_API::get($bgs ? '/api/v1/cards' : '/api/v1/constructed-cards', ['page' => $page, 'per_page' => 100]); }
    foreach ($cache as &$entry) { if (is_array($entry) && isset($entry['fresh_until'])) { $entry['fresh_until'] = time() - 1; } }
    unset($entry);
    $scenario = 'timeout';
}
function verify_bg(): string {
    global $hsjson, $bg_old, $bg_before, $bg_rows, $temp, $scenario, $http_calls;
    $hsjson = [];
    foreach ($bg_old['by_id'] as $id => $entry) {
        $hsjson[] = ['collectible' => true, 'id' => $id, 'name' => $entry['name'], 'set' => 'BATTLEGROUNDS', 'type' => $entry['type'], 'techLevel' => $entry['techLevel']];
    }
    $result = hs_smart_tooltip_rebuild_bgs_dictionary();
    if ($scenario !== 'success') {
        check(!$result['ok'], 'Failed BG import must fail rebuild');
        check(file_get_contents($temp . '/hs_bgs_dictionary.json') === $bg_before, 'BG failure preserves dictionary bytes');
        return '';
    }
    check($result['ok'], 'BG rebuild succeeds');
    check(hs_smart_tooltip_koloda_bgs_image_url('https://art.hearthstonejson.com/v1/bgs/latest/ruRU/512x/BGMinionAbilityTest.png', 'BGMinionAbilityTest') !== '', 'Accept exact provider HSJSON fallback');
    check(hs_smart_tooltip_koloda_bgs_image_url('https://art.hearthstonejson.com/private.png', 'BGMinionAbilityTest') === '', 'Reject other HSJSON paths');
    $dict = json_decode(file_get_contents($temp . '/hs_bgs_dictionary.json'), true);
    check($dict['by_id']['BG19_010'] === $bg_old['by_id']['BG19_010'], 'Existing BG entry survives unchanged');
    check($dict['by_id']['BG36_111']['img'] === $bg_rows[0]['images']['card'], 'Sha uses Koloda image');
    $calls = count($http_calls);
    $results = hs_smart_tooltip_run_search(hs_smart_tooltip_get_bgs_search_catalog(), 'Ша Страха', 15);
    check(in_array('BG36_111', array_column($results, 'id'), true), 'Sha appears in BG editor search');
    $html = hs_smart_tooltip_bgs_shortcode(['id' => 'BG36_111'], 'Ша Страха');
    check(str_contains($html, 'hs-card-tooltip') && str_contains($html, 'hs_tooltip_img='), 'Sha shortcode renders image proxy');
    check(count($http_calls) === $calls, 'BG search/render remains local');
    return '<p>' . $html . '</p>';
}
if ($bgs) { verify_bg(); echo "PASS\n"; exit; }
$result = hs_smart_tooltip_rebuild_main_dictionary();
if (!in_array($scenario, ['success', 'tokens'], true)) {
    check(!$result['ok'], 'Failed/partial provider response must fail rebuild');
    check(file_get_contents($temp . '/hs_dictionary.json') === $before, 'Failure must preserve dictionary bytes');
    echo "PASS\n"; exit;
}
check($result['ok'], 'Rebuild succeeds');
$dict = json_decode(file_get_contents($temp . '/hs_dictionary.json'), true);
foreach ($old['by_id'] as $id => $entry) {
    check(isset($dict['by_id'][$id]), 'Existing card IDs and numeric aliases survive rebuilding');
}
check($dict['by_id']['AT_001']['img'] === $old['by_id']['AT_001']['img'], 'Existing HSJSON image stays unchanged');
check(hs_smart_tooltip_koloda_entry(['card_id' => 'blizzard:129892', 'card_type' => ['slug' => null]]) === null, 'Skip only known dbf-only unrevealed records');
foreach (['http://api.kolodahearthstone.com/uploads/constructed-related/BE_036.png',
    'https://api.kolodahearthstone.com:443/uploads/constructed-related/BE_036.png',
    'https://api.kolodahearthstone.com/api/v1/constructed-cards',
    'https://api.kolodahearthstone.com/uploads/constructed-related/../private.png'] as $url) {
    check(hs_smart_tooltip_koloda_image_url($url) === '', 'Reject unsafe image proxy URLs');
}
check(file_get_contents($temp . '/hs_dictionary.bak.json') === $before, 'Backup preserves previous dictionary');
if ($scenario === 'tokens') {
    check(isset($dict['by_id']['BE_222t'], $dict['by_id'][130406]), 'Non-collectible token and dbf alias survive rebuild');
    check($dict['by_id']['BE_222t']['name'] === 'Безднокрыл', 'Token keeps its localized name');
    check(str_contains(hs_smart_tooltip_shortcode(['id' => 'BE_222t']), 'hs-card-tooltip'), 'Token shortcode renders tooltip');
    $matches = hs_smart_tooltip_run_search(hs_smart_tooltip_get_search_catalog(), 'Безднокрыл', 15);
    check(in_array('BE_222t', array_column($matches, 'id'), true), 'Token appears in editor search');
    $shared = ['name' => 'Совпавшее имя', 'type' => 'MINION', 'set' => 'BE'];
    $collision = hs_smart_tooltip_build_dictionary_from_cards([
        $shared + ['id' => 'TOKEN_1', 'rarity' => 'LEGENDARY'],
        $shared + ['id' => 'CARD_1', 'rarity' => 'COMMON', 'collectible' => true],
    ]);
    check(str_ends_with($collision['by_name'][hs_smart_tooltip_dict_key('Совпавшее имя')]['img'], '/CARD_1.png'), 'Collectible card retains automatic name match over a token');
}
$calls_before_render = count($http_calls);
$catalog = hs_smart_tooltip_get_search_catalog();
$rendered = '';
foreach ($rows as $row) {
    $id = $row['card_id']; $name = $row['name']['ru'];
    check(isset($dict['by_id'][$id], $dict['by_id'][$row['dbf']]), 'Canonical and numeric IDs resolve');
    check($dict['by_name'][hs_smart_tooltip_dict_key($name)]['name'] === $name, 'Name resolves for auto replacement');
    check($dict['by_id'][$id]['img'] === $row['images']['card'], 'Use Koloda image');
    $results = hs_smart_tooltip_run_search($catalog, $name, 15);
    check(in_array($id, array_column($results, 'id'), true), 'Card appears in editor search');
    $html = hs_smart_tooltip_shortcode(['id' => $id], $name);
    $rendered .= '<p>' . $html . '</p>';
    check(str_contains($html, 'hs-card-tooltip') && str_contains($html, 'hs_tooltip_img='), 'Shortcode renders tooltip with image proxy');
}
check(count($http_calls) === $calls_before_render, 'Search and rendering use local dictionary');
if ($argv[1] === 'render') { $rendered .= verify_bg(); }
echo $argv[1] === 'render' ? $rendered : "PASS\n";
