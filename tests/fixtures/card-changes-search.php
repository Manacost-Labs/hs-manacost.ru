<?php
// Editor search: dictionary first, archive second, on recorded archive responses and a controlled HTTP/cache boundary.
require __DIR__ . '/card-changes/stubs.php';
require __DIR__ . '/card-changes/requests-stub.php';
$root = dirname(__DIR__, 2) . '/wordpress/mu-plugins/hs-card-changes/';
require $root . 'pairs.php';
require $root . 'cache.php';
require $root . 'source.php';
require $root . 'search.php';
// The hs-tooltip dictionary is substituted by its public functions: it lacks BG36_362 (new season) but knows BE_036.
$dictionary_active = true;
function hs_smart_tooltip_get_search_catalog() { return [['id' => 'BE_036', 'name' => 'МАТРИАРХ', 'img' => 'https://art.hearthstonejson.com/v1/render/latest/ruRU/256x/BE_036.png', '_nl' => 'матриарх']]; }
function hs_smart_tooltip_get_bgs_search_catalog() { return [['id' => 'BG35_155', 'name' => 'Злобный страж гнева', 'img' => 'https://art.hearthstonejson.com/v1/bgs/latest/ruRU/256x/BG35_155.png', '_nl' => 'злобный страж гнева']]; }
function hs_smart_tooltip_run_search($catalog, $term, $limit) {
    $found = array_filter($catalog, fn($e) => str_contains(mb_strtolower($e['name']), mb_strtolower($term)));
    return array_slice(array_values($found), 0, $limit);
}
function fx($name) { return file_get_contents(__DIR__ . "/card-changes/$name.json"); }
function archive_url($path, $term) { return 'https://api.kolodahearthstone.com' . $path . '?' . http_build_query(['q' => $term, 'per_page' => 12]); }
function status($code, $headers = []) { return ['response' => ['code' => $code], 'headers' => $headers, 'body' => '']; }
function json_ok($body) { return ['response' => ['code' => 200], 'headers' => ['content-type' => 'application/json; charset=utf-8'], 'body' => $body]; }
$S = 'HS_Card_Changes_Search';
$allow = fn() => true;
$ids = fn($r) => array_map(fn($i) => $i['mode'] . ':' . $i['id'], $r['items']);

$responses[archive_url('/api/v1/cards', 'Жертвенный страж гнева')] = json_ok(fx('search-bg-wrathguard'));
$responses[archive_url('/api/v1/constructed-cards', 'Жертвенный страж гнева')] = json_ok('{"data":[],"pagination":{"total":0}}');
$responses[archive_url('/api/v1/cards', 'Матриарх')] = json_ok('{"data":[],"pagination":{"total":0}}');
$responses[archive_url('/api/v1/constructed-cards', 'Матриарх')] = json_ok(fx('search-constructed-matriarch'));

// The owner's case: a new Battlegrounds card absent from the dictionary is found by its Russian name.
$r = $S::run('Жертвенный страж гнева', 'all', $allow);
check($ids($r) === ['bg:BG36_362'] && $r['archive'] === 'ok', 'BG36_362 is found through the archive: ' . json_encode($ids($r)));
check($r['items'][0]['name'] === 'Жертвенный страж гнева' && $r['items'][0]['source'] === 'archive' && str_contains($r['items'][0]['img'], '/uploads/card-history-media/c5a1e1ead804ed3f8c173eec57e2bf43dc293d3abbd225de8e8af960f00e296e.png') && !str_contains($r['items'][0]['img'], 'card-tiles'), 'Name, source and a card-shaped thumbnail (not the wide tile) come from the archive');
check(count($calls) === 2 && str_contains($calls[0], '/api/v1/constructed-cards?q=') && str_contains($calls[1], '/api/v1/cards?q=') && str_contains($calls[0], 'per_page=12'), 'Both modes are queried in one parallel batch: ' . json_encode($calls));
$count = count($calls);
$again = $S::run('Жертвенный страж гнева', 'all', $allow);
check($ids($again) === ['bg:BG36_362'] && $again['archive'] === 'cached' && count($calls) === $count, 'An identical search is served from the cache');

// Dictionary first, archive adds what it lacks, one row per card.
$r = $S::run('Матриарх', 'constructed', $allow);
$list = $ids($r);
check($list[0] === 'constructed:BE_036' && $r['items'][0]['source'] === 'dictionary', 'Dictionary result comes first');
check(count($list) === 10 && count(array_unique($list)) === 10 && in_array('constructed:TOY_380', $list, true), 'Archive-only cards are merged, duplicates dropped: ' . implode(',', $list));
check(!in_array('bg:BG35_155', $list, true), 'The kind filter restricts the modes');
$responses[archive_url('/api/v1/cards', 'Злобный страж')] = json_ok('{"data":[]}');
$r = $S::run('Злобный страж', 'bg', $allow);
check($ids($r) === ['bg:BG35_155'], 'Dictionary-only results still work (archive returns nothing for this query): ' . json_encode($ids($r)));

// Short terms and the rate limit make no request.
$count = count($calls);
check($S::run('ж', 'all', $allow)['items'] === [] && count($calls) === $count, 'Short terms are not searched');
$limited = $S::run('Другая карта', 'all', fn() => false);
check($limited['archive'] === 'limited' && count($calls) === $count, 'When rate limited the archive is skipped, no request');

// Outage: the dictionary keeps answering, cooldown starts, nothing throws.
$responses[archive_url('/api/v1/cards', 'Падение')] = status(500, ['retry-after' => '90']);
$responses[archive_url('/api/v1/constructed-cards', 'Падение')] = status(500, ['retry-after' => '90']);
$r = $S::run('Падение', 'all', $allow);
check($r['items'] === [] && $r['archive'] === 'unavailable' && $cache['hs_cc_cooldown'] === 'http_500' && $ttl['hs_cc_cooldown'] === 90, 'A failing archive starts the cooldown and yields no rows: ' . json_encode([$r, $cache['hs_cc_cooldown'] ?? null, $ttl['hs_cc_cooldown'] ?? null], JSON_UNESCAPED_UNICODE));
$count = count($calls);
$r = $S::run('Матриарх жгучего холода', 'all', $allow);
check($count === count($calls) && $r['archive'] === 'unavailable', 'Cooldown: no archive request, dictionary only');
unset($cache['hs_cc_cooldown']);

// Invalid rows are dropped; thumbnails must be HTTPS.
$bad = ['data' => [
    ['card_id' => 'BG99_001', 'name' => ['ru' => 'Хорошая'], 'images' => ['card' => 'https://api.kolodahearthstone.com/uploads/card-history-media/x.png', 'tile' => 'https://api.kolodahearthstone.com/uploads/card-tiles/x.png']],
    ['card_id' => '../x', 'name' => ['ru' => 'Плохой идентификатор']],
    ['card_id' => 'BG99_002', 'name' => ['ru' => '<script>alert(1)</script>Имя'], 'images' => ['card' => 'http://insecure.example/x.png']],
    ['card_id' => 'BG99_003', 'name' => 'строка вместо объекта'],
    ['card_id' => 'BG99_004', 'name' => ['ru' => '']],
    'not-an-array',
]];
$responses[archive_url('/api/v1/cards', 'Тест строк')] = json_ok(json_encode($bad, JSON_UNESCAPED_UNICODE) === false ? '' : json_encode($bad, JSON_UNESCAPED_UNICODE));
$responses[archive_url('/api/v1/constructed-cards', 'Тест строк')] = json_ok('{"data":[]}');
$r = $S::run('Тест строк', 'all', $allow);
check($ids($r) === ['bg:BG99_001', 'bg:BG99_002'], 'Only valid rows survive: ' . json_encode($ids($r)));
check(!str_contains(json_encode($r['items'], JSON_UNESCAPED_UNICODE), '<') && $r['items'][1]['img'] === '', 'Names lose markup and insecure thumbnails are dropped');
$responses[archive_url('/api/v1/cards', 'Форма')] = json_ok('{"data":{"a":1}}');
$responses[archive_url('/api/v1/constructed-cards', 'Форма')] = json_ok('{"data":{"a":1}}');
$r = $S::run('Форма', 'all', $allow);
check($r['archive'] === 'unavailable' && isset($cache['hs_cc_cooldown']), 'A response of the wrong shape counts as an outage');
unset($cache['hs_cc_cooldown']);

// Kill switch is covered by the main fixture; a missing dictionary plugin leaves the archive working.
echo "PASS\n";
