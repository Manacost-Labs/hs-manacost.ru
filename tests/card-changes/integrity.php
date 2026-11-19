<?php
if (wp_get_environment_type() !== 'local' || wp_parse_url(home_url(), PHP_URL_HOST) !== '127.0.0.1') throw new RuntimeException('Disposable site required');
function cc_check($value, $message) { if (!$value) throw new RuntimeException($message); }
require_once ABSPATH . 'wp-admin/includes/post.php';
wp_set_current_user(1);
$fixture = get_option('hs_card_changes_fixture');
$draft = get_post($fixture['draft']);
cc_check($draft->post_status === 'publish', 'Draft was published through the editor');
cc_check(substr_count($draft->post_content, '[hs_card_change ') === 4 && str_contains($draft->post_content, '[hs_card_changes columns="2"'), 'Stored post_content keeps the shortcodes');
cc_check(!str_contains($draft->post_content, 'api.kolodahearthstone.com') && !str_contains($draft->post_content, '<img'), 'Stored content holds no markup or URLs, only hashes');
cc_check(str_contains($draft->post_content, 'Первый абзац') && str_contains($draft->post_content, 'Последний абзац'), 'Surrounding text untouched');
// Author role: KSES keeps the shortcode text byte for byte.
cc_check(wp_kses_post($draft->post_content) === $draft->post_content, 'KSES does not alter the shortcodes');
// Revisions and autosaves restore the exact text.
$copy = wp_insert_post(['post_title' => 'История изменений карт', 'post_status' => 'draft', 'post_content' => wp_slash($draft->post_content)]);
wp_save_post_revision($copy);
$revision = current(wp_get_post_revisions($copy));
cc_check($revision instanceof WP_Post, 'Revision exists');
wp_update_post(['ID' => $copy, 'post_content' => '<p>Другое.</p>']);
wp_restore_post_revision($revision->ID);
cc_check(get_post($copy)->post_content === $draft->post_content, 'Revision restores the exact shortcode text');
$autosave = wp_create_post_autosave(['post_ID' => $copy, 'post_type' => 'post', 'post_content' => $draft->post_content . '<p>Автосохранение.</p>', 'post_title' => 'История изменений карт']);
cc_check(!is_wp_error($autosave) && str_contains(wp_get_post_autosave($copy, 1)->post_content, '[hs_card_change '), 'Autosave keeps the shortcodes');
// Visitor path: a cold cache is never filled during a page view.
$fixture_article = get_post($fixture['article']);
$requests = HS_Card_Changes_Render::requests($fixture_article->post_content, $fixture_article->ID);
cc_check(count($requests) === 8, 'Article has eight unpinned changes (five distinct cards and dates)');
foreach ($requests as $request) delete_transient('hs_cc_pair_' . HS_Card_Changes_Cache::key(...$request));
delete_transient('hs_cc_cooldown');
update_option('hs_cc_fixture_calls', 0, false);
$cold = apply_filters('the_content', $fixture_article->post_content);
cc_check((int) get_option('hs_cc_fixture_calls') === 0, 'A page view with a cold cache makes no provider request');
cc_check(substr_count($cold, '<figure class="hs-cc__item') === 8 && substr_count($cold, 'hs-cc__item--text') === 8 && !str_contains($cold, '<p><div'), 'Cold cache renders the plates-only fallback for every unpinned change');
// Warm-up fills the cache in the background (the hook cron would run).
do_action('hs_card_changes_warm', $fixture['article']);
$calls = (int) get_option('hs_cc_fixture_calls');
cc_check($calls === 5, 'Warm-up fetched each distinct card once: ' . $calls);
$warm = apply_filters('the_content', $fixture_article->post_content);
cc_check(substr_count($warm, '<figure class="hs-cc__item') === 8 && substr_count($warm, 'hs-cc__item--text') === 0 && substr_count($warm, '<img') === 15, 'Warmed cache renders every card');
cc_check((int) get_option('hs_cc_fixture_calls') === $calls, 'Rendering from the warmed cache makes no request');
// Outage: stale entries keep serving, the failed refresh starts the cooldown.
update_option('hs_cc_fixture_mode', 'down', false);
foreach ($requests as $request) {
    $key = 'hs_cc_pair_' . HS_Card_Changes_Cache::key(...$request);
    $stored = get_transient($key);
    $stored['until'] = time() - 1;
    set_transient($key, $stored, DAY_IN_SECONDS);
}
HS_Card_Changes_Source::refresh($requests);
cc_check(get_transient('hs_cc_cooldown') !== false, 'Failed refresh starts the cooldown');
$down = apply_filters('the_content', $fixture_article->post_content);
cc_check(substr_count($down, '<img') === 15 && substr_count($down, 'hs-cc__item--text') === 0, 'Stale entries keep rendering during an outage');
delete_option('hs_cc_fixture_mode');
delete_transient('hs_cc_cooldown');
// Saving a post schedules the warm-up instead of fetching.
update_option('hs_cc_fixture_calls', 0, false);
wp_update_post(['ID' => $fixture['article'], 'post_content' => wp_slash($fixture_article->post_content . "\n[hs_card_change card=\"BE_285\" date=\"2026-11-03 15:55:30\"]")]);
cc_check((int) get_option('hs_cc_fixture_calls') === 0 && wp_next_scheduled('hs_card_changes_warm', [$fixture['article']]) !== false, 'Saving schedules one warm-up and fetches nothing');
wp_update_post(['ID' => $fixture['article'], 'post_content' => wp_slash($fixture_article->post_content)]);
$content = apply_filters('the_content', get_post($fixture['article'])->post_content);
cc_check(substr_count($content, '<figure class="hs-cc__item') === 8, 'Article renders eight changes');
cc_check(!str_contains($content, '<p><div') && !str_contains($content, '</div></p>'), 'No invalid paragraph nesting around the blocks');

// The real WordPress transport (Requests) against a local server: redirects off, size and time limits, 404 and 5xx.
$port = 18765;
$server = proc_open([PHP_BINARY, '-S', "127.0.0.1:$port", ABSPATH . '.integration/card-changes-server.php'], [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes, ABSPATH, ['PHP_CLI_SERVER_WORKERS' => '4', 'PATH' => getenv('PATH')]);
for ($i = 0; $i < 40 && !@fsockopen('127.0.0.1', $port); $i++) usleep(100000);
@unlink('/tmp/hs-cc-server-hits.log');
add_filter('hs_card_changes_origin', static fn() => "http://127.0.0.1:$port");
$date = '2026-11-04 00:00:00';
$run = static function (string $card) use ($date) {
    delete_transient('hs_cc_cooldown');
    $started = microtime(true);
    HS_Card_Changes_Source::refresh([['constructed', $card, $date]]);
    return [HS_Card_Changes_Source::lookup('constructed', $card, $date)['state'], round(microtime(true) - $started, 1), get_transient('hs_cc_cooldown')];
};
try {
    [$state, , $cool] = $run('SCN_OK');
    cc_check($state === 'single' && $cool === false, 'Real transport: a valid response resolves');
    [$state, , $cool] = $run('SCN_404');
    cc_check($state === 'none' && $cool === false, 'Real transport: 404 is an empty history, not an outage');
    [$state, , $cool] = $run('SCN_500');
    $timeout = (int) get_option('_transient_timeout_hs_cc_cooldown') - time();
    cc_check($state === 'unavailable' && $cool === 'http_500' && $timeout > 100 && $timeout <= 120, 'Real transport: 5xx starts the cooldown with Retry-After: ' . $timeout);
    [$state, , $cool] = $run('SCN_302');
    cc_check($state === 'unavailable' && $cool !== false && !str_contains((string) @file_get_contents('/tmp/hs-cc-server-hits.log'), '/redirected'), 'Real transport: redirects are not followed');
    [$state, , $cool] = $run('SCN_BIG');
    cc_check($state === 'unavailable' && $cool !== false, 'Real transport: an oversized body is rejected');
    [$state, $seconds, $cool] = $run('SCN_SLOW');
    cc_check($state === 'unavailable' && $cool !== false && $seconds < 7.5, 'Real transport: a slow server times out at five seconds: ' . $seconds . 's');
    delete_transient('hs_cc_cooldown');
    $started = microtime(true);
    HS_Card_Changes_Source::refresh(array_map(static fn($n) => ['constructed', "SCN_W$n", $date], [1, 2, 3, 4]));
    $parallel = microtime(true) - $started;
    cc_check($parallel < 3.8 && HS_Card_Changes_Source::lookup('constructed', 'SCN_W4', $date)['state'] === 'single', 'Real transport: four requests run in parallel: ' . round($parallel, 1) . 's');
} finally {
    proc_terminate($server);
}
delete_transient('hs_cc_cooldown');
echo wp_json_encode(['ok' => true, 'figures' => 8, 'revision_exact' => true, 'visitor_path_no_http' => true, 'warm_up' => $calls, 'real_transport' => true]) . "\n";
