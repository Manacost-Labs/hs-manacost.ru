<?php
// Execute the real filter/invalidator with isolated in-memory DB/cache effects.
define('ABSPATH', __DIR__);
define('MINUTE_IN_SECONDS', 60);
$hooks = $cache = [];
$admin = true;
$blog = 1;
$now = 1000;
$cache_writes = true;
$generation = 0;
function add_filter($hook, $callback, $priority = 10, $accepted = 1) {
    $GLOBALS['hooks'][$hook][$priority][] = [$callback, $accepted];
}
function add_action(...$args) { add_filter(...$args); }
function apply_filters($hook, $value, ...$args) {
    $groups = $GLOBALS['hooks'][$hook] ?? [];
    ksort($groups);
    foreach ($groups as $callbacks) {
        foreach ($callbacks as [$callback, $accepted]) {
            $value = $callback(...array_slice([$value, ...$args], 0, $accepted));
        }
    }
    return $value;
}
function do_action($hook, ...$args) { apply_filters($hook, ...$args); }
function is_admin() { return $GLOBALS['admin']; }
function wp_cache_get($key, $group = '', $force = false, &$found = null) {
    $entry = $GLOBALS['cache'][$GLOBALS['blog']][$group][$key] ?? null;
    $found = $entry !== null && (!$entry[1] || $entry[1] > $GLOBALS['now']);
    return $found ? $entry[0] : false;
}
function wp_cache_set($key, $value, $group = '', $ttl = 0) {
    if (!$GLOBALS['cache_writes']) { return false; }
    $GLOBALS['cache'][$GLOBALS['blog']][$group][$key] = [$value, $ttl ? $GLOBALS['now'] + $ttl : 0];
    return true;
}
function wp_cache_get_last_changed($group) {
    return wp_cache_get('last_changed', $group) ?: wp_cache_set_last_changed($group);
}
function wp_cache_set_last_changed($group) {
    $value = (string) ++$GLOBALS['generation'];
    wp_cache_set('last_changed', $value, $group);
    return $value;
}
class MetaChoiceDatabase {
    public $postmeta = 'wp_postmeta';
    public $last_error = '';
    public $queries = 0;
    public $rows = ['alpha', 'beta'];
    public $fail = false;
    public $during_query = null;
    public function esc_like($value) { return addcslashes($value, '_%\\'); }
    public function prepare($sql, ...$args) {
        return vsprintf(str_replace('%s', "'%s'", $sql), $args);
    }
    public function get_col($query) {
        ++$this->queries;
        $this->last_error = $this->fail ? 'fixture failure' : '';
        if ($this->fail) { return []; }
        $rows = $this->rows;
        if ($this->during_query) { ($this->during_query)(); $this->during_query = null; }
        preg_match('/LIMIT\s+(\d+)/', $query, $matches);
        $rows = array_filter($rows, fn($key) => !str_starts_with($key, '_'));
        sort($rows);
        return array_slice(array_values(array_unique($rows)), 0, (int)($matches[1] ?? 30));
    }
}
$wpdb = new MetaChoiceDatabase();
if (is_file($argv[1])) { require $argv[1]; }
function choices() {
    $keys = apply_filters('postmeta_form_keys', null, null);
    if ($keys !== null) { return $keys; }
    $limit = apply_filters('postmeta_form_limit', 30);
    return $GLOBALS['wpdb']->get_col('SELECT DISTINCT meta_key FROM wp_postmeta LIMIT ' . (int)$limit);
}
function same($expected, $actual, $message) {
    if ($expected !== $actual) { throw new RuntimeException($message . ': expected ' . json_encode($expected) . ', got ' . json_encode($actual)); }
}
$scenario = $argv[2];
switch ($scenario) {
    case 'warm':
        same(['alpha', 'beta'], choices(), 'initial keys');
        same(['alpha', 'beta'], choices(), 'cached keys');
        same(1, $wpdb->queries, 'one archive scan'); break;
    case 'empty':
        $wpdb->rows = [];
        same([], choices(), 'empty'); same([], choices(), 'empty cached');
        same(1, $wpdb->queries, 'empty result cached'); break;
    case 'upstream':
        add_filter('postmeta_form_keys', fn() => ['owned']);
        same(['owned'], choices(), 'other plugin owns results');
        same(0, $wpdb->queries, 'no replacement query'); break;
    case 'frontend':
        $admin = false;
        same(null, apply_filters('postmeta_form_keys', null), 'frontend untouched');
        same(0, $wpdb->queries, 'no frontend query'); break;
    case 'limit':
        same(['alpha', 'beta'], choices(), 'default');
        add_filter('postmeta_form_limit', fn() => 1);
        same(['alpha'], choices(), 'custom limit'); same(['alpha'], choices(), 'cached custom limit');
        same(2, $wpdb->queries, 'independent cache per limit'); break;
    case 'invalid_limit':
        add_filter('postmeta_form_limit', fn() => -1);
        same(null, apply_filters('postmeta_form_keys', null), 'invalid limit delegates to core'); break;
    case 'default_limit':
        $wpdb->rows = array_map(fn($i) => sprintf('key%04d', $i), range(1, 35));
        same(array_slice($wpdb->rows, 0, 30), choices(), 'core default exposes exactly thirty keys'); break;
    case 'upper_limit':
        add_filter('postmeta_form_limit', fn() => 1000);
        same(['alpha', 'beta'], apply_filters('postmeta_form_keys', null), 'upper limit is inclusive');
        add_filter('postmeta_form_limit', fn() => 1001, 20);
        same(null, apply_filters('postmeta_form_keys', null), 'oversized dropdown delegates to core'); break;
    case 'coerced_limit':
        add_filter('postmeta_form_limit', fn() => 1000.9);
        same(['alpha', 'beta'], apply_filters('postmeta_form_keys', null), 'core integer coercion happens before bounds'); break;
    case 'add': case 'delete': case 'delete_all':
        choices();
        $wpdb->rows = $scenario === 'add' ? ['alpha', 'beta', 'new'] : ['beta'];
        do_action($scenario === 'add' ? 'added_post_meta' : 'deleted_post_meta', 7, 9, $scenario === 'delete_all' ? '' : 'alpha', 'fixture');
        same($wpdb->rows, choices(), 'changed keys immediately visible');
        same($wpdb->rows, choices(), 'new result cached');
        same(2, $wpdb->queries, 'one scan after mutation'); break;
    case 'private_value':
        choices();
        do_action('updated_post_meta', 7, 9, '_edit_lock', 'fixture');
        do_action('added_post_meta', 7, 9, '_thumbnail_id', 'fixture');
        do_action('deleted_post_meta', 7, 9, '_private', 'fixture');
        choices(); same(1, $wpdb->queries, 'private values do not invalidate key list'); break;
    case 'public_value':
        choices();
        do_action('updated_post_meta', 7, 9, 'post_views_count', 42);
        same(['alpha', 'beta'], choices(), 'value-only update preserves key choices');
        same(1, $wpdb->queries, 'reader view counters do not invalidate dropdown'); break;
    case 'rename_private':
        choices();
        same(null, apply_filters('update_post_metadata_by_mid', null, 7, 'fixture', '_private'), 'rename allowed');
        $wpdb->rows = ['_private', 'beta'];
        do_action('updated_post_meta', 7, 9, '_private', 'fixture');
        same(['beta'], choices(), 'public to private rename invalidated'); break;
    case 'rename_failure':
        choices();
        apply_filters('update_post_metadata_by_mid', null, 7, 'fixture', '_private');
        same(['alpha', 'beta'], choices(), 'failed rename preserves choices');
        same(1, $wpdb->queries, 'no invalidation before successful write'); break;
    case 'failed_rename_filter':
        same(false, apply_filters('update_post_metadata_by_mid', false, 7, 'fixture', '_private'), 'other veto respected'); break;
    case 'query_failure':
        $wpdb->fail = true;
        same(null, apply_filters('postmeta_form_keys', null), 'query error delegates');
        $wpdb->fail = false;
        same(['alpha', 'beta'], choices(), 'error not cached as empty'); break;
    case 'cache_failure':
        $cache_writes = false;
        same(['alpha', 'beta'], choices(), 'cache failure returns SQL result');
        same(['alpha', 'beta'], choices(), 'subsequent request still works'); break;
    case 'race':
        $wpdb->during_query = function() use ($wpdb) {
            $wpdb->rows = ['beta'];
            do_action('deleted_post_meta', 7, 9, 'alpha', 'fixture');
        };
        same(['alpha', 'beta'], choices(), 'in-flight snapshot');
        same(['beta'], choices(), 'late fill cannot overwrite new generation'); break;
    case 'blog':
        choices(); $blog = 2; $wpdb->rows = ['other']; $wpdb->postmeta = 'wp_2_postmeta';
        same(['other'], choices(), 'site isolation'); $blog = 1;
        $wpdb->postmeta = 'wp_postmeta';
        same(['alpha', 'beta'], choices(), 'original site cache'); break;
    case 'expiry':
        choices(); $wpdb->rows = ['changed-outside-hooks']; $now += 601;
        same(['changed-outside-hooks'], choices(), 'bounded TTL'); break;
    default: throw new RuntimeException('unknown scenario');
}
echo "PASS\n";
