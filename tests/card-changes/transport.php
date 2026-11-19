<?php
// Deterministic card-history boundary. Never loaded outside the disposable site.
if (wp_get_environment_type() !== 'local' || wp_parse_url(home_url(), PHP_URL_HOST) !== '127.0.0.1') {
    throw new RuntimeException('Disposable loopback WordPress required');
}
add_filter('pre_http_request', static function ($pre, $args, $url) {
    if (!preg_match('#^https://api\.kolodahearthstone\.com/api/v1/card-history/(constructed_card|card)/([A-Za-z0-9_]+)(?:\?|$)#', $url, $m)) return $pre;
    update_option('hs_cc_fixture_calls', (int) get_option('hs_cc_fixture_calls', 0) + 1, false);
    if (get_option('hs_cc_fixture_mode') === 'down') return new WP_Error('http_request_failed', 'Fixture outage');
    $file = ABSPATH . ".integration/card-changes/{$m[1]}-{$m[2]}.json";
    if (!is_readable($file)) return ['response' => ['code' => 404], 'headers' => [], 'body' => ''];
    return ['response' => ['code' => 200], 'headers' => ['content-type' => 'application/json; charset=utf-8'], 'body' => file_get_contents($file)];
}, 10, 3);
// Lets the browser test prove which endpoints reach the provider: it reads the call counter before and after.
add_action('wp_ajax_hs_cc_fixture_calls', static function () {
    if (!current_user_can('edit_posts')) wp_send_json_error(null, 403);
    wp_send_json_success((int) get_option('hs_cc_fixture_calls', 0));
});
