<?php
// Only WordPress HTTP/cache/escaping/shortcode boundaries are substituted; responses are recorded provider data.
define('ABSPATH', '/');
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);
define('DAY_IN_SECONDS', 86400);
if (($argv[1] ?? '') === 'disabled') define('HS_CARD_CHANGES_REMOTE_ENABLED', false);
$cache = []; $calls = []; $events = []; $responses = []; $batches = []; $scheduled = []; $posts = []; $ttl = []; $main_query = false; $passthrough = false;
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
function get_post_field($field, $id) { return $id === 78 ? '0000-00-00 00:00:00' : '2026-11-03 15:55:00'; }
function get_shortcode_regex($tags) { return '\[(\[?)(' . implode('|', $tags) . ')(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*+(?:\[(?!\/\2\])[^\[]*+)*+)\[\/\2\])?)(\]?)'; }
function shortcode_parse_atts($text) { preg_match_all('/([\w-]+)="([^"]*)"/u', $text, $m, PREG_SET_ORDER); $a = []; foreach ($m as $x) $a[$x[1]] = $x[2]; return $a; }
function esc_html($v) { return htmlspecialchars($v, ENT_QUOTES); }
function esc_attr($v) { return htmlspecialchars($v, ENT_QUOTES); }
function esc_url($v) { return htmlspecialchars($v, ENT_QUOTES); }
function check($v, $message) { if (!$v) throw new RuntimeException('FAIL: ' . $message); }
$root = dirname(__DIR__, 2) . '/wordpress/mu-plugins/hs-card-changes/';
require $root . 'pairs.php';
require __DIR__ . '/card-changes/requests-stub.php';
require $root . 'cache.php';
require $root . 'source.php';
require $root . 'render.php';
require $root . 'warm.php';

function fixture($entity, $card) { return json_decode(file_get_contents(__DIR__ . "/card-changes/$entity-$card.json"), true)['data']['versions']; }
function url($entity, $card, $page = 1) { return "https://api.kolodahearthstone.com/api/v1/card-history/$entity/$card" . ($page > 1 ? "?page=$page" : ''); }
function ok($entity, $card) { return ['response' => ['code' => 200], 'headers' => ['content-type' => 'application/json; charset=utf-8'], 'body' => file_get_contents(__DIR__ . "/card-changes/$entity-$card.json")]; }
function status($code, $headers = []) { return ['response' => ['code' => $code], 'headers' => $headers, 'body' => '']; }
function plate_text($plates) { return implode(';', array_map(fn($p) => "{$p['key']}:{$p['old']}>{$p['new']}|{$p['tone']}", $plates)); }
$P = 'HS_Card_Changes_Pairs';
$D = '2026-11-03 15:55:00';

// 1. Pair rule and plates, on recorded patch 37.0.2 responses.
$bg = $P::pick(fixture('card', 'BG36_362'), 'bg', $D);
check($bg['state'] === 'pair' && $bg['name'] === 'Жертвенный страж гнева', 'BG pair and Russian name');
check(str_ends_with($bg['before']['hash'], '330b44e99f') && str_ends_with($bg['after']['hash'], '60f00e296e'), 'BG before is the pre-patch render, after is the post-patch one');
check(plate_text($bg['plates']) === 'attack:5>6|good', 'BG attack 5→6 is an improvement and carries no health/tier plate');
$matriarch = $P::pick(fixture('constructed_card', 'BE_036'), 'constructed', $D);
check($matriarch['state'] === 'pair' && str_ends_with($matriarch['before']['hash'], 'eab0224958') && str_ends_with($matriarch['after']['hash'], 'e7eb6bbfbc'), 'Recorded Matriarch hashes');
check($matriarch['before']['at'] === '2026-10-13 20:29:02' && $matriarch['after']['at'] === '2026-11-03 20:29:37', 'Matriarch: newest archived before, oldest differing after the patch');
check(plate_text($matriarch['plates']) === 'mana:9>10|bad', 'Higher mana cost is worse');
$bone = $P::pick(fixture('constructed_card', 'JAIL_444'), 'constructed', $D);
check(plate_text($bone['plates']) === 'mana:5>6|bad;attack:5>6|good', 'Two plates, in fixed order');
check($bone['after']['at'] === '2026-11-03 20:29:37', 'Later identical-image rows are not picked as the after side');
$hybrid = $P::pick(fixture('constructed_card', 'BE_285'), 'constructed', $D);
check(plate_text($hybrid['plates']) === 'health:3>4|good' && $hybrid['before']['at'] === '2026-10-21 05:29:31', 'Health up is good; newest pre-patch version is before');
check($matriarch['name'] === 'МАТРИАРХ', 'Constructed name comes from name_ru');

// 2. No pair: before the history began, and after the last change.
$early = $P::pick(fixture('constructed_card', 'BE_036'), 'constructed', '2026-10-01 00:00:00');
check($early['state'] === 'single' && $early['before'] === null && $early['plates'] === [] && $early['after']['at'] === '2026-11-15 06:39:57', 'Patch older than history renders the current card alone');
$late = $P::pick(fixture('constructed_card', 'BE_036'), 'constructed', '2026-11-20 00:00:00');
check($late['state'] === 'single' && $late['before'] === null, 'No change after the date renders the current card alone');
check($P::pick([], 'constructed', $D)['state'] === 'none', 'No history at all');
$unverified = [['observed_at' => '2026-10-11 23:57:09', 'snapshot' => ['name_ru' => 'X'], 'media' => ['image_url' => ['status' => 'unverified', 'archive_url' => null]]]];
check($P::pick($unverified, 'constructed', $D)['state'] === 'none', 'Unverified mutable images are never used');
$mutable = [['observed_at' => '2026-11-04 00:00:00', 'snapshot' => [], 'media' => ['image_url' => ['status' => 'archived', 'archive_url' => '/uploads/constructed-related/BE_036.png']]]];
check($P::pick($mutable, 'constructed', $D)['state'] === 'none', 'Only content-addressed archive paths are used');

// 3. Text plate: real text change only, whitespace is not a change.
$v = fn($text) => ['mana_cost' => 1, 'text_ru' => $text];
check($P::plates($v('<b>А</b> 1'), $v('<b>А</b> 2'), 'constructed')[0]['label'] === 'Текст', 'Text change yields the Текст plate');
check($P::plates($v("А  1"), $v("А\n1"), 'constructed') === [], 'Whitespace-only text change is ignored');
check($P::plates($v('a'), $v('b'), 'bg') === [], 'Battlegrounds has no text field');
check($P::plates(['tavern_tier' => 4], ['tavern_tier' => 5], 'bg')[0]['tone'] === 'neutral', 'Tier is neutral');
check($P::plates(['mana_cost' => 5], ['mana_cost' => 4], 'constructed')[0]['tone'] === 'good', 'Lower mana is better');

// 4. Dates.
check($P::normalize_date('2026-11-03') === '2026-11-03 00:00:00' && $P::normalize_date('2026-11-03T15:55') === '2026-11-03 15:55:00', 'Date forms');
foreach (['2026-02-31', '2026-11-03 25:00', 'yesterday', '', "2026-11-03'; DROP"] as $bad) check($P::normalize_date($bad) === '', 'Rejects ' . $bad);

// 5. Plate grammar and editor validation.
$parsed = $P::parse_plates('mana:9:10; attack:5:6|bad; text; ~Новый эффект:а:б|good; bogus:1:2; mana:1', 'constructed');
check(count($parsed) === 4 && $parsed[1]['tone'] === 'bad' && $parsed[2]['key'] === 'text' && $parsed[3]['key'] === 'custom', 'Grammar parses; invalid tokens are dropped');
check($P::parse_plates('', 'bg') === null && $P::parse_plates('none', 'bg') === [], 'Empty is automatic; none removes plates');
check($P::parse_plates('durability:1:2', 'bg') === [], 'Battlegrounds has no durability plate');
check($P::plate_from_editor(['key' => 'custom', 'label' => '<b>x</b>', 'old' => '', 'new' => ''], 'bg') === null, 'Markup in a label is rejected');
check($P::plate_from_editor(['key' => 'mana', 'old' => '5', 'new' => '"><x'], 'constructed') === null, 'Markup in a value is rejected');
check($P::plate_from_editor(['key' => 'mana', 'old' => '5', 'new' => '4', 'tone' => 'auto'], 'constructed')['tone'] === 'good', 'Automatic tone is recomputed after editing');
check($P::serialize_plates($parsed) === 'mana:9:10;attack:5:6|bad;text;~Новый эффект:а:б|good', 'Serialization round trip');
check($P::serialize_plates([]) === 'none', 'Empty plates serialize as none');

$S = 'HS_Card_Changes_Source';
$K = fn($mode, $card, $date) => 'hs_cc_pair_' . HS_Card_Changes_Cache::key($mode, $card, $date);
$ids = [['constructed_card', 'BE_036'], ['constructed_card', 'JAIL_444'], ['constructed_card', 'BE_285'], ['card', 'BG36_362']];
foreach ($ids as [$e, $c]) $responses[url($e, $c)] = ok($e, $c);
foreach (['A_1', 'A_2'] as $extra) $responses[url('constructed_card', $extra)] = ok('constructed_card', 'BE_036');

if (($argv[1] ?? '') === 'disabled') {
    $r = $S::lookup('constructed', 'BE_036', $D, 77);
    $S::refresh([['constructed', 'BE_036', $D]]);
    check($calls === [] && $r['state'] === 'unavailable' && $scheduled === [], 'Kill switch: no request, nothing scheduled, still renderable');
    echo "PASS\n";
    exit;
}

// 6. Visitor path: cache only, deduplicated background refresh.
$r = $S::lookup('constructed', 'BE_036', $D);
check($r['state'] === 'unavailable' && $calls === [] && $scheduled === [], 'A miss without a queue target makes no request and schedules nothing');
$r = $S::lookup('constructed', 'BE_036', $D, 77);
$S::lookup('constructed', 'BE_285', $D, 77);
$S::lookup('constructed', 'BE_036', $D, 77);
check($r['state'] === 'unavailable' && $calls === [], 'A visitor miss never calls the provider');
check($scheduled === [['hs_card_changes_warm', [77]]], 'One deduplicated warm-up is scheduled for the post: ' . json_encode($scheduled));
$scheduled = []; unset($cache['hs_cc_queued_post_77']);
$S::lookup('constructed', 'BE_036', $D, 0);
$S::lookup('constructed', 'BE_036', $D, 0);
check($scheduled === [['hs_card_changes_refresh', ['constructed', 'BE_036', $D]]], 'A single-card refresh is deduplicated too');
$scheduled = [];

// Background refresh: four parallel requests at most, one shared budget.
$requests = [];
foreach ($ids as [$e, $c]) $requests[] = [$e === 'card' ? 'bg' : 'constructed', $c, $D];
$requests[] = ['constructed', 'A_1', $D]; $requests[] = ['constructed', 'A_2', $D];
$S::refresh($requests);
check($batches === [4, 2] && count($calls) === 6, 'Six cards use batches of at most four parallel requests');
check(str_contains($calls[0], '/constructed_card/BE_036') && str_contains($calls[3], '/card/BG36_362'), 'Constructed and Battlegrounds use their own entity');
$before = count($calls);
$r = $S::lookup('constructed', 'BE_036', $D);
check($r['state'] === 'pair' && count($calls) === $before, 'Resolved pair is served from the cache without a request');
check($cache[$K('constructed', 'BE_036', $D)]['until'] === PHP_INT_MAX && $ttl[$K('constructed', 'BE_036', $D)] >= 30 * DAY_IN_SECONDS, 'A resolved pair never goes stale and is kept for a long time');
$S::refresh($requests);
check(count($calls) === $before, 'Fresh entries are not fetched again');
check(in_array(['hs_card_changes_provider_result', ['success']], $events, true), 'Outcome is observable');
check($S::lookup('constructed', 'bad id!', $D, 77)['state'] === 'unavailable' && count($calls) === $before, 'Invalid identifiers cause no request');

// Stale while revalidate and the per-key lock.
$S::refresh([['constructed', 'BE_036', '2026-11-20 00:00:00']]);
$single_key = $K('constructed', 'BE_036', '2026-11-20 00:00:00');
check($cache[$single_key]['value']['state'] === 'single' && $cache[$single_key]['until'] <= time() + 600 && $ttl[$single_key] >= 30 * DAY_IN_SECONDS, 'A pair-less result is fresh for minutes but kept for weeks');
$cache[$single_key]['until'] = time() - 1;
$count = count($calls); $scheduled = []; unset($cache['hs_cc_queued_post_77']);
$stale = $S::lookup('constructed', 'BE_036', '2026-11-20 00:00:00', 77);
check($stale['state'] === 'single' && count($calls) === $count && $scheduled !== [], 'A stale result is served as is while one refresh is queued');
$cache['hs_cc_lock_' . HS_Card_Changes_Cache::key('constructed', 'BE_036', '2026-11-20 00:00:00')] = 1;
$S::refresh([['constructed', 'BE_036', '2026-11-20 00:00:00']]);
check(count($calls) === $count, 'A held lock keeps concurrent workers from refetching the same card');
unset($cache['hs_cc_lock_' . HS_Card_Changes_Cache::key('constructed', 'BE_036', '2026-11-20 00:00:00')]);
$S::refresh([['constructed', 'BE_036', '2026-11-20 00:00:00']]);
check(count($calls) === $count + 1 && $cache[$single_key]['until'] > time() && !isset($cache['hs_cc_lock_' . HS_Card_Changes_Cache::key('constructed', 'BE_036', '2026-11-20 00:00:00')]), 'Refresh renews the entry and releases the lock');

// Provider failure: stale copy keeps serving, cooldown stops retry storms, nothing cached gives plates only.
$cache[$single_key]['until'] = time() - 1;
$responses[url('constructed_card', 'BE_036')] = status(500, ['retry-after' => '120']);
$count = count($calls);
$S::refresh([['constructed', 'BE_036', '2026-11-20 00:00:00']]);
check(count($calls) === $count + 1 && $S::lookup('constructed', 'BE_036', '2026-11-20 00:00:00')['state'] === 'single', 'API failure serves the stale copy');
check($cache['hs_cc_cooldown'] === 'http_500' && $ttl['hs_cc_cooldown'] === 120, 'Retry-After is honored');
$S::refresh([['constructed', 'JAIL_444', '2026-11-12 00:00:00']]);
$scheduled = []; $S::queue(['constructed', 'JAIL_444', '2026-11-12 00:00:00'], 77);
check(count($calls) === $count + 1 && $scheduled === [], 'Cooldown: no refetch and nothing scheduled');
check($S::lookup('constructed', 'JAIL_444', '2026-11-12 00:00:00')['state'] === 'unavailable', 'Nothing cached during an outage: plates only');
unset($cache['hs_cc_cooldown']);
$responses[url('constructed_card', 'BE_036')] = ok('constructed_card', 'BE_036');
$responses[url('constructed_card', 'NOPE_000')] = status(404);
$S::refresh([['constructed', 'NOPE_000', $D]]);
check($S::lookup('constructed', 'NOPE_000', $D)['state'] === 'none' && !isset($cache['hs_cc_cooldown']), 'Unknown history is a normal empty outcome, not an outage');
foreach (['{broken', '{"data":{"versions":{"a":1}}}', str_repeat('a', 524289)] as $body) {
    unset($cache['hs_cc_cooldown']);
    $responses[url('constructed_card', 'BAD_1')] = ['response' => ['code' => 200], 'headers' => ['content-type' => 'application/json'], 'body' => $body];
    $S::refresh([['constructed', 'BAD_1', $D]]);
    check($S::lookup('constructed', 'BAD_1', $D)['state'] === 'unavailable' && isset($cache['hs_cc_cooldown']), 'Invalid response is rejected');
}
unset($cache['hs_cc_cooldown']);
$responses[url('constructed_card', 'BAD_2')] = ['response' => ['code' => 200], 'headers' => ['content-type' => 'text/html'], 'body' => '{"data":{"versions":[]}}'];
$S::refresh([['constructed', 'BAD_2', $D]]);
check($S::lookup('constructed', 'BAD_2', $D)['state'] === 'unavailable', 'Non-JSON content type is rejected');
unset($cache['hs_cc_cooldown']);
$responses[url('constructed_card', 'BAD_3')] = new WP_Error('http_request_failed', 'timeout');
$S::refresh([['constructed', 'BAD_3', $D]]);
check(isset($cache['hs_cc_cooldown']), 'Transport errors start the cooldown and never throw');
unset($cache['hs_cc_cooldown']);

// The real transport path (Requests::request_multiple): objects are normalized, exceptions become an outage.
$passthrough = true;
Requests_Stub::$mode = 'ok'; $count = count($calls);
$S::refresh([['constructed', 'BE_285', '2026-11-04 00:00:00']]);
check($S::lookup('constructed', 'BE_285', '2026-11-04 00:00:00')['state'] === 'single' && Requests_Stub::$seen[0]['options']['redirects'] === 0 && Requests_Stub::$seen[0]['options']['max_bytes'] === 524289 && Requests_Stub::$seen[0]['options']['timeout'] === 5 && str_contains(Requests_Stub::$seen[0]['options']['useragent'], 'ManacostCardChanges'), 'Requests path: redirects off, size and time bounded, service User-Agent');
Requests_Stub::$mode = 'throw';
$S::refresh([['constructed', 'JAIL_444', '2026-11-04 00:00:00']]);
check($cache['hs_cc_cooldown'] === 'unavailable' && $S::lookup('constructed', 'JAIL_444', '2026-11-04 00:00:00')['state'] === 'unavailable', 'A throwing transport becomes a cooldown, not a fatal error');
unset($cache['hs_cc_cooldown']);
$passthrough = false;

// Observation times: any ISO form, UTC, and no reliance on provider order.
check($P::observed('2026-11-03T20:29:37.5Z') === '2026-11-03 20:29:37' && $P::observed('2026-11-03 22:29:37+02:00') === '2026-11-03 20:29:37' && $P::observed('2026-11-03 20:29') === '2026-11-03 20:29:00' && $P::observed('2026-11-03 20:29:37.150301') === '2026-11-03 20:29:37', 'observed_at forms normalize to UTC');
foreach (['yesterday', '2026-13-40 00:00:00', null, 5] as $bad) check($P::observed($bad) === '', 'Rejects observed_at ' . json_encode($bad));
$shuffled = fixture('constructed_card', 'BE_036');
shuffle($shuffled); $reordered = array_reverse(fixture('constructed_card', 'BE_036'));
foreach ([$shuffled, $reordered] as $rows) check($P::pick($rows, 'constructed', $D)['after']['at'] === '2026-11-03 20:29:37' && $P::pick($rows, 'constructed', $D)['before']['at'] === '2026-10-13 20:29:02', 'Pair selection does not depend on provider order');
$offset = fixture('constructed_card', 'BE_036');
foreach ($offset as &$row) $row['observed_at'] = substr($row['observed_at'], 0, 10) . 'T' . substr($row['observed_at'], 11, 8) . 'Z';
unset($row);
check($P::pick($offset, 'constructed', $D)['state'] === 'pair', 'T/Z timestamps work');

// 7. Rendering reads the cache only.
$R = 'HS_Card_Changes_Render';
foreach ($ids as [$e, $c]) $responses[url($e, $c)] = ok($e, $c);
$cache = []; $scheduled = [];
$S::refresh([['constructed', 'BE_036', $D], ['bg', 'BG36_362', $D], ['constructed', 'BE_285', '2026-11-03 00:00:00'], ['constructed', 'JAIL_444', $D]]);
$calls = []; $scheduled = [];
$R::begin_content('');
$html = $R::container(['columns' => '3', 'size' => 'l', 'captions' => 'yes'], "</p>\n<p>[hs_card_change card=\"BE_036\" date=\"2026-11-03 15:55\"]<br />\n[hs_card_change card=\"BG36_362\" mode=\"bg\" date=\"2026-11-03 15:55\" plates=\"attack:5:6;~Бонус:+1:+2|good\"]\n[[hs_card_change card=\"ESCAPED_1\"]]\n[hs_card_change card=\"x y\"]</p>");
check(str_contains($html, 'hs-cc--cols-3') && str_contains($html, 'hs-cc--size-l') && str_contains($html, 'hs-cc--captions'), 'Closed options');
check(substr_count($html, '<figure') === 2, 'Only valid nested changes render; editor wrapper markup and escaped tags are ignored');
check(str_contains($html, 'alt="МАТРИАРХ до изменения"') && str_contains($html, 'alt="МАТРИАРХ после изменения"'), 'Meaningful alt text');
check(str_contains($html, 'width="404" height="558"') && str_contains($html, 'width="256" height="388"') && str_contains($html, 'loading="lazy"'), 'Reserved geometry and lazy loading');
check(str_contains($html, '<s>9</s>') && str_contains($html, '<b>10</b>') && str_contains($html, 'hs-cc__plate--bad'), 'Automatic plate with struck old value');
check(str_contains($html, '~') === false && str_contains($html, 'Бонус') && str_contains($html, 'hs-cc__plate--good'), 'Explicit plates replace automatic ones, free label supported');
check(str_contains($html, 'data-hs-label="До"') && str_contains($html, 'data-hs-lightbox-pair') && str_contains($html, 'aria-haspopup="dialog"'), 'Lightbox hook and captions metadata');
check(str_contains($html, 'https://api.kolodahearthstone.com/uploads/card-history-media/') && !str_contains($html, 'local_image_url') && !str_contains($html, 'constructed-related'), 'Images come only from the immutable archive');
check(substr_count($html, 'class="hs-cc__open"') === 2 && !str_contains($html, '<div class="hs-cc__panel'), 'No panel around a change');
check($calls === [] && $scheduled === [], 'Rendering from the cache makes no request and schedules nothing');
$standalone = $R::single(['card' => 'BE_285', 'date' => '2026-11-03', 'name' => '<script>alert(1)</script>Рыцарь']);
check(str_contains($standalone, 'hs-cc--cols-1') && !str_contains($standalone, '<script'), 'Standalone form wraps itself and cleans names');
check($R::single(['card' => '../etc']) === '' && $R::container([], 'no children') === '', 'Invalid input renders nothing');
$pinned = $R::single(['card' => 'BE_036', 'name' => 'Матриарх', 'before' => $hash_a = str_repeat('a', 64), 'after' => $hash_b = str_repeat('b', 64), 'plates' => 'mana:9:10']);
check($calls === [] && str_contains($pinned, $hash_a . '.png') && str_contains($pinned, $hash_b . '.png'), 'Pinned images and plates need no provider');
check(!str_contains($R::single(['card' => 'BE_036', 'name' => 'M', 'before' => '../x', 'after' => 'zzz', 'plates' => 'none']), '../x'), 'Only SHA-256 hashes are accepted as image references');
$cache = []; $scheduled = [];
$offline = $R::single(['card' => 'BE_036', 'date' => '2026-11-03', 'plates' => 'mana:1:2']);
check(str_contains($offline, 'hs-cc__item--text') && str_contains($offline, 'BE_036') && str_contains($offline, '<b>2</b>') && !str_contains($offline, '<img') && $calls === [] && $scheduled === [], 'Cache miss: plates without images, no request, no scheduling outside the main query');
$main_query = true;
$R::single(['card' => 'BE_036', 'date' => '2026-11-03', 'plates' => 'none']);
$R::single(['card' => 'BE_285', 'date' => '2026-11-03', 'plates' => 'none']);
check($scheduled === [['hs_card_changes_warm', [77]]] && $calls === [], 'The main query schedules one warm-up per post and still makes no request');
$main_query = false;
$single_current = $R::single(['card' => 'BE_036', 'before' => '', 'after' => $hash_b, 'name' => 'Матриарх', 'plates' => 'none']);
check(str_contains($single_current, 'hs-cc__item--single') && str_contains($single_current, 'alt="Матриарх"') && !str_contains($single_current, 'до изменения'), 'Current card alone');
$unpinned_preview = $R::html([$R::item(['card' => 'BE_036', 'date' => '2026-11-03', 'name' => 'Матриарх', 'plates' => 'mana:9:10'])], ['columns' => '1', 'size' => 'm', 'captions' => 'no'], true);
check($calls === [] && !str_contains($unpinned_preview, '<img') && str_contains($unpinned_preview, '<b>10</b>'), 'Pinned-only rendering never consults the provider cache');

// Caps: nested and standalone changes share one per-post limit.
$R::begin_content('');
$rendered = 0;
for ($i = 0; $i < 30; $i++) if ($R::single(['card' => 'BE_285', 'date' => '2026-11-03', 'plates' => 'none']) !== '') $rendered++;
check($rendered === 24, 'Standalone changes are capped per post');
$R::begin_content('');
$children = str_repeat("[hs_card_change card=\"BE_285\" date=\"2026-11-03\" plates=\"none\"]\n", 30);
check(substr_count($R::container([], $children), '<figure') === 24, 'A container is capped too');
check(substr_count($R::container([], $children), '<figure') === 0, 'The cap is shared by the whole post until the next content pass');
$R::begin_content('');

// Warm-up and drafts.
$post = new WP_Post(); $post->post_content = "[hs_card_changes]\n[hs_card_change card=\"BE_036\" date=\"2026-11-03 15:55\"]\n[hs_card_change card=\"JAIL_444\" before=\"$hash_a\" after=\"$hash_b\" plates=\"none\"]\n[/hs_card_changes]\n[hs_card_change card=\"BG36_362\" mode=\"bg\"]";
$requests = $R::requests($post->post_content, 77);
check($requests === [['constructed', 'BE_036', $D], ['bg', 'BG36_362', '2026-11-03 15:55:00']], 'Warm-up covers unpinned changes only, with the post date as default');
check($R::requests('[hs_card_change card="BE_036"]', 78) === [['constructed', 'BE_036', gmdate('Y-m-d 00:00:00')]] && $R::requests('[hs_card_change card="BE_036"]', 78) === $R::requests('[hs_card_change card="BE_036"]', 78), 'A draft without a date falls back to the stable start of the UTC day');
$scheduled = []; $cache = [];
HS_Card_Changes_Warm::on_save(77, $post);
HS_Card_Changes_Warm::on_save(77, $post);
check($scheduled === [['hs_card_changes_warm', [77]]] && $calls === [], 'Saving schedules one warm-up and makes no request');
$scheduled = []; $cache = [];
$plain = new WP_Post(); $plain->post_content = '<p>Без изменений</p>';
HS_Card_Changes_Warm::on_save(79, $plain);
$pinned_post = new WP_Post(); $pinned_post->post_content = "[hs_card_change card=\"BE_036\" before=\"$hash_a\" after=\"$hash_b\" plates=\"none\"]";
HS_Card_Changes_Warm::on_save(80, $pinned_post);
$trashed = new WP_Post(); $trashed->post_status = 'trash'; $trashed->post_content = $post->post_content;
HS_Card_Changes_Warm::on_save(81, $trashed);
check($scheduled === [], 'Nothing is scheduled for posts without unpinned changes or in the trash');
$posts[77] = $post; $cache = ['hs_cc_queued_post_77' => 1];
HS_Card_Changes_Warm::run(77);
check(count($calls) === 2 && !isset($cache['hs_cc_queued_post_77']) && $S::lookup('constructed', 'BE_036', $D)['state'] === 'pair', 'The warm-up job fetches the post\'s cards and clears its marker');
check($R::asset_version('card-changes.css') !== 'missing' && $R::asset_version('nope.css') === 'missing', 'Assets are versioned by content hash');

// 8. Editor boundary and shortcode generation.
$item = $R::item_from_editor(['card' => 'BE_036', 'mode' => 'constructed', 'date' => '2026-11-03T15:55', 'name' => '<b>Матриарх</b>"]', 'before' => $hash_a, 'after' => $hash_b, 'plates' => [['key' => 'mana', 'old' => '9', 'new' => '10', 'tone' => 'bad']]]);
check($item !== null && !str_contains($item['name'], '<') && !str_contains($item['name'], '"'), 'Editor names lose markup and quote characters');
check($R::item_from_editor(['card' => 'BE_036', 'mode' => 'constructed', 'date' => 'soon', 'plates' => []]) === null, 'Invalid date is rejected');
check($R::item_from_editor(['card' => 'BE_036', 'date' => '2026-11-03', 'plates' => [['key' => 'evil']]]) === null, 'Unknown plate key is rejected');
$one = $R::build_shortcode([$item], ['columns' => '2', 'size' => 'm', 'captions' => 'no']);
check(str_starts_with($one, '[hs_card_change card="BE_036" mode="constructed"') && !str_contains($one, 'hs_card_changes') && str_contains($one, 'plates="mana:9:10"'), 'One change uses the single form');
$two = $R::build_shortcode([$item, $item], ['columns' => '1', 'size' => 's', 'captions' => 'yes']);
check(!str_contains($one, 'captions="no"') && !str_contains($one, 'columns='), 'Defaults stay implicit');
check(!preg_match('/[<>&]/', $one . $R::build_shortcode([$item, $item], ['columns' => '2', 'size' => 'm', 'captions' => 'no'])), 'Shortcode text has no characters that texturize or TinyMCE would rewrite');
check(str_starts_with($two, '[hs_card_changes columns="1" size="s" captions="yes"]') && str_ends_with($two, '[/hs_card_changes]') && substr_count($two, "\n[hs_card_change ") === 2, 'Container with one change per line');
$calls = [];
$round = $R::container(['columns' => '1'], $two);
check(substr_count($round, '<figure') === 2 && str_contains($round, $hash_a . '.png'), 'Generated shortcode renders back to the same changes');
check($calls === [], 'Round trip needs no provider');
echo "PASS\n";
