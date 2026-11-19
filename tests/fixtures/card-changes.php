<?php
// Only WordPress HTTP/cache/escaping/shortcode boundaries are substituted; responses are recorded provider data.
define('ABSPATH', '/');
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);
define('DAY_IN_SECONDS', 86400);
if (($argv[1] ?? '') === 'disabled') define('HS_CARD_CHANGES_REMOTE_ENABLED', false);
$cache = []; $calls = []; $events = []; $responses = []; $batches = [];
class WP_Error {
    public function __construct(public $code = '', public $message = '') {}
}
function __($text, $domain = '') { return $text; }
function is_wp_error($v) { return $v instanceof WP_Error; }
function get_transient($key) { return $GLOBALS['cache'][$key] ?? false; }
function set_transient($key, $value, $ttl) { $GLOBALS['cache'][$key] = $value; $GLOBALS['ttl'][$key] = $ttl; }
function apply_filters($hook, $value, ...$args) {
    if ($hook !== 'pre_http_request') return $value;
    $GLOBALS['calls'][] = $args[1];
    return $GLOBALS['responses'][$args[1]] ?? new WP_Error('http_request_failed', 'private transport details');
}
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
function get_post_field($field, $id) { return '2026-11-03 15:55:00'; }
function get_shortcode_regex($tags) { return '\[(\[?)(' . implode('|', $tags) . ')(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*+(?:\[(?!\/\2\])[^\[]*+)*+)\[\/\2\])?)(\]?)'; }
function shortcode_parse_atts($text) { preg_match_all('/([\w-]+)="([^"]*)"/u', $text, $m, PREG_SET_ORDER); $a = []; foreach ($m as $x) $a[$x[1]] = $x[2]; return $a; }
function esc_html($v) { return htmlspecialchars($v, ENT_QUOTES); }
function esc_attr($v) { return htmlspecialchars($v, ENT_QUOTES); }
function esc_url($v) { return htmlspecialchars($v, ENT_QUOTES); }
function check($v, $message) { if (!$v) throw new RuntimeException('FAIL: ' . $message); }
$root = dirname(__DIR__, 2) . '/wordpress/mu-plugins/hs-card-changes/';
require $root . 'pairs.php';
require $root . 'source.php';
require $root . 'render.php';

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

if (($argv[1] ?? '') === 'disabled') {
    $responses[url('constructed_card', 'BE_036')] = ok('constructed_card', 'BE_036');
    $r = HS_Card_Changes_Source::resolve('constructed', 'BE_036', $D);
    check($calls === [] && $r['state'] === 'unavailable', 'Kill switch sends no request and still returns a renderable result');
    echo "PASS\n";
    exit;
}

// 6. Source: concurrency cap, cache, cooldown, last good.
$ids = [['constructed_card', 'BE_036'], ['constructed_card', 'JAIL_444'], ['constructed_card', 'BE_285'], ['card', 'BG36_362']];
foreach ($ids as [$e, $c]) $responses[url($e, $c)] = ok($e, $c);
foreach (['A_1', 'A_2'] as $extra) $responses[url('constructed_card', $extra)] = ok('constructed_card', 'BE_036');
$requests = [];
foreach ($ids as [$e, $c]) $requests[] = [$e === 'card' ? 'bg' : 'constructed', $c, $D];
$requests[] = ['constructed', 'A_1', $D]; $requests[] = ['constructed', 'A_2', $D];
HS_Card_Changes_Source::prefetch($requests);
check($batches === [4, 2] && count($calls) === 6, 'Six cards use batches of at most four parallel requests');
check(str_contains($calls[0], '/constructed_card/BE_036') && str_contains($calls[3], '/card/BG36_362'), 'Constructed and Battlegrounds use their own entity');
$before = count($calls);
$r = HS_Card_Changes_Source::resolve('constructed', 'BE_036', $D);
check($r['state'] === 'pair' && count($calls) === $before, 'Resolved pair is served from the cache without a request');
check($ttl['hs_cc_pair_' . hash('sha256', "constructed|BE_036|$D")] >= 30 * DAY_IN_SECONDS, 'A resolved pair is cached for a long time');
$single = HS_Card_Changes_Source::resolve('constructed', 'BE_036', '2026-11-20 00:00:00');
check($single['state'] === 'single' && $ttl['hs_cc_pair_' . hash('sha256', 'constructed|BE_036|2026-11-20 00:00:00')] <= 600, 'A pair-less result may change, so it expires soon');
check(in_array(['hs_card_changes_provider_result', ['success']], $events, true), 'Outcome is observable');
check(HS_Card_Changes_Source::resolve('constructed', 'bad id!', $D)['state'] === 'unavailable', 'Invalid identifiers cause no request');
check(count($calls) === $before + 1, 'Only the date-specific resolution needed a request');

// Provider failure: last good copy, else plates-only; cooldown stops retry storms.
unset($cache['hs_cc_pair_' . hash('sha256', "constructed|BE_036|$D")]);
$responses[url('constructed_card', 'BE_036')] = status(500, ['retry-after' => '120']);
$count = count($calls);
$r = HS_Card_Changes_Source::resolve('constructed', 'BE_036', $D);
check($r['state'] === 'pair' && $r['after']['at'] === '2026-11-03 20:29:37', 'API failure serves the last good pair');
check(count($calls) === $count + 1, 'One failed request was made');
$fresh = HS_Card_Changes_Source::resolve('constructed', 'JAIL_444', '2026-11-12 00:00:00');
check($fresh['state'] === 'unavailable' && count($calls) === $count + 1, 'Cooldown prevents another request; nothing cached yet gives plates only');
check($cache['hs_cc_cooldown'] === 'http_500' && $ttl['hs_cc_cooldown'] === 120, 'Retry-After is honored');
unset($cache['hs_cc_cooldown']);
$responses[url('constructed_card', 'NOPE_000')] = status(404);
check(HS_Card_Changes_Source::resolve('constructed', 'NOPE_000', $D)['state'] === 'none' && !isset($cache['hs_cc_cooldown']), 'Unknown history is a normal empty outcome, not an outage');
foreach (['{broken', '{"data":{"versions":{"a":1}}}', str_repeat('a', 524289)] as $body) {
    unset($cache['hs_cc_cooldown']);
    $responses[url('constructed_card', 'BAD_1')] = ['response' => ['code' => 200], 'headers' => ['content-type' => 'application/json'], 'body' => $body];
    check(HS_Card_Changes_Source::resolve('constructed', 'BAD_1', $D)['state'] === 'unavailable' && isset($cache['hs_cc_cooldown']), 'Invalid response is rejected');
}
unset($cache['hs_cc_cooldown']);
$responses[url('constructed_card', 'BAD_2')] = ['response' => ['code' => 200], 'headers' => ['content-type' => 'text/html'], 'body' => '{"data":{"versions":[]}}'];
check(HS_Card_Changes_Source::resolve('constructed', 'BAD_2', $D)['state'] === 'unavailable', 'Non-JSON content type is rejected');
unset($cache['hs_cc_cooldown']);
$responses[url('constructed_card', 'BAD_3')] = new WP_Error('http_request_failed', 'timeout');
check(HS_Card_Changes_Source::resolve('constructed', 'BAD_3', $D)['state'] === 'unavailable', 'Transport errors never throw');
unset($cache['hs_cc_cooldown']);

// 7. Rendering.
$R = 'HS_Card_Changes_Render';
foreach ($ids as [$e, $c]) $responses[url($e, $c)] = ok($e, $c);
HS_Card_Changes_Source::prefetch([['constructed', 'BE_036', $D], ['bg', 'BG36_362', $D], ['constructed', 'BE_285', '2026-11-03 00:00:00']]);
$calls = [];
$hash_a = str_repeat('a', 64); $hash_b = str_repeat('b', 64);
$html = $R::container(['columns' => '3', 'size' => 'l', 'captions' => 'yes'], "</p>\n<p>[hs_card_change card=\"BE_036\" date=\"2026-11-03 15:55\"]<br />\n[hs_card_change card=\"BG36_362\" mode=\"bg\" date=\"2026-11-03 15:55\" plates=\"attack:5:6;~Бонус:+1:+2|good\"]\n[[hs_card_change card=\"ESCAPED_1\"]]\n[hs_card_change card=\"x y\"]</p>");
check(str_contains($html, 'hs-cc--cols-3') && str_contains($html, 'hs-cc--size-l') && str_contains($html, 'hs-cc--captions'), 'Closed options');
check(substr_count($html, '<figure') === 2, 'Only valid nested changes render; editor wrapper markup and escaped tags are ignored');
check(str_contains($html, 'alt="МАТРИАРХ до изменения"') && str_contains($html, 'alt="МАТРИАРХ после изменения"'), 'Meaningful alt text');
check(str_contains($html, 'width="404" height="558"') && str_contains($html, 'width="256" height="388"') && str_contains($html, 'loading="lazy"'), 'Reserved geometry and lazy loading');
check(str_contains($html, '<s>9</s>') && str_contains($html, '<b>10</b>') && str_contains($html, 'hs-cc__plate--bad'), 'Automatic plate with struck old value');
check(str_contains($html, '~') === false && str_contains($html, 'Бонус') && str_contains($html, 'hs-cc__plate--good'), 'Explicit plates replace automatic ones, free label supported');
check(str_contains($html, 'data-hs-label="До"') && str_contains($html, 'data-hs-lightbox-pair') && str_contains($html, 'aria-haspopup="dialog"'), 'Lightbox hook and captions metadata');
check(str_contains($html, 'https://api.kolodahearthstone.com/uploads/card-history-media/'), 'Images come only from the immutable archive');
check(!str_contains($html, 'local_image_url') && !str_contains($html, 'constructed-related'), 'Mutable image paths are never emitted');
check(substr_count($html, 'class="hs-cc__open"') === 2 && !str_contains($html, '<div class="hs-cc__panel'), 'No panel around a change');
check(count($calls) === 0, 'Cached pairs render without requests');
$standalone = $R::single(['card' => 'BE_285', 'date' => '2026-11-03', 'name' => '<script>alert(1)</script>Рыцарь']);
check(str_contains($standalone, 'hs-cc--cols-1') && !str_contains($standalone, '<script') , 'Standalone form wraps itself and cleans names');
check($R::single(['card' => '../etc']) === '' && $R::container([], 'no children') === '', 'Invalid input renders nothing');
$pinned = $R::single(['card' => 'BE_036', 'name' => 'Матриарх', 'before' => $hash_a, 'after' => $hash_b, 'plates' => 'mana:9:10']);
check(count($calls) === 0 && str_contains($pinned, $hash_a . '.png') && str_contains($pinned, $hash_b . '.png'), 'Pinned images and plates need no provider');
$bad_hash = $R::single(['card' => 'BE_036', 'name' => 'M', 'before' => '../x', 'after' => 'zzz', 'plates' => 'none']);
check(!str_contains($bad_hash, '../x'), 'Only SHA-256 hashes are accepted as image references');
$cache = []; $responses = [];
$offline = $R::single(['card' => 'BE_036', 'date' => '2026-11-03', 'plates' => 'mana:9:10']);
check(str_contains($offline, 'hs-cc__item--text') && str_contains($offline, 'BE_036') && str_contains($offline, '<b>10</b>') && !str_contains($offline, '<img'), 'Provider down: plates without images, no fatal');
$cache = []; $events = [];
$single_current = $R::single(['card' => 'BE_036', 'before' => '', 'after' => $hash_b, 'name' => 'Матриарх', 'plates' => 'none']);
check(str_contains($single_current, 'hs-cc__item--single') && str_contains($single_current, 'alt="Матриарх"') && !str_contains($single_current, 'до изменения'), 'Current card alone');

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
