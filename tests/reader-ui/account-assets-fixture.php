<?php
// Lightweight WP hook boundary, no WordPress runtime/database or user data.
define('ABSPATH', '/fixture/');
$route = $argv[1];
$font = $argv[3] ?? 'pt-sans';
$filters = array();
$removed_actions = array();
$scripts = array('tdPostImages', 'tdSocialSharing', 'tdModalPostImages', 'comment-reply', 'tdMenu', 'tdAjaxSearch', 'jquery-core', 'hs-manacost-reader');
$actions = array('wp_footer:ai_wp_footer_hook:9999999' => 'ai_wp_footer_hook', 'wp_footer:fixture_theme_footer:20' => 'fixture_theme_footer');
// Ad Inserter emits this raw script in its footer callback, not the WP queue.
function ai_wp_footer_hook() { echo '<script id="ai-functions" src="/fixture/ai-functions.min.js"></script>'; }
function fixture_theme_footer() { echo 'Theme footer'; }
function do_action($name) { foreach ($GLOBALS['actions'] as $key => $callback) { if (str_starts_with($key, $name . ':')) $callback(); } }
function is_admin() { return $GLOBALS['route'] === 'admin'; }
function is_preview() { return $GLOBALS['route'] === 'preview'; }
function hs_manacost_reader_page() { return $GLOBALS['route'] === 'missing' ? null : (object) array('ID' => 42); }
function is_page($id) { return $id === 42 && !in_array($GLOBALS['route'], array('article', 'other'), true); }
function add_filter($name, $callback, $priority = 10, $args = 1) { $GLOBALS['filters'][$name] = $callback; }
function remove_action($name, $callback, $priority = 10) { $key = $name . ':' . (is_array($callback) ? implode('::', $callback) : $callback) . ':' . $priority; $GLOBALS['removed_actions'][] = $key; unset($GLOBALS['actions'][$key]); }
function wp_dequeue_script($name) { $GLOBALS['scripts'] = array_values(array_diff($GLOBALS['scripts'], array($name))); }
function wp_style_is($name, $status = 'enqueued') { return $name === 'google-fonts-style' && !in_array($GLOBALS['font'], array('missing', 'dequeued'), true); }
function wp_styles() {
    $src = 'https://fonts.googleapis.com/css?family=PT+Sans%3A400%7CRoboto%3A400&display=swap';
    if ($GLOBALS['font'] === 'different') $src = 'https://fonts.googleapis.com/css?family=Roboto%3A400';
    if ($GLOBALS['font'] === 'local') $src = '/local-fonts.css?family=PT+Sans%3A400';
    return (object) array('registered' => $GLOBALS['font'] === 'missing' ? array() : array('google-fonts-style' => (object) array('src' => $src)));
}
function wp_parse_url($url, $component) { return parse_url($url, $component); }
function __return_empty_string() { return ''; }
function __return_false() { return false; }
function filtered($name, $value) { return isset($GLOBALS['filters'][$name]) ? $GLOBALS['filters'][$name]($value) : $value; }
require $argv[2];
hs_reader_account_integrations();
hs_reader_account_trim_assets();
ob_start(); do_action('wp_footer'); $footer = ob_get_clean();
echo json_encode(array('code' => filtered('ai_block_code', 'fixture advertising'), 'insert' => filtered('ai_block_insertion_check', true), 'removed_actions' => $removed_actions, 'scripts' => $scripts, 'footer' => $footer, 'preloads' => filtered('wp_preload_resources', array(array('href' => '/fixture/existing.css', 'as' => 'style')))));
