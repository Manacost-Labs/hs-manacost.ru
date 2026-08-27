<?php
/**
 * Feed advertisement tracking for the main [hs_decks] shortcode.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Unified_HS_Ad_Tracker {

    const SCHEMA_VERSION = '1';
    const DEFAULT_AD_KEY = 'boosty_feed';
    const TARGET_URL = 'https://boosty.to/kolodahearthstone';

    public static function init() {
        add_action('admin_post_hs_decks_ad_click', array(__CLASS__, 'handle_click'));
        add_action('admin_post_nopriv_hs_decks_ad_click', array(__CLASS__, 'handle_click'));
        add_action('wp_ajax_hs_decks_ad_impression', array(__CLASS__, 'handle_impression'));
        add_action('wp_ajax_nopriv_hs_decks_ad_impression', array(__CLASS__, 'handle_impression'));
    }

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'manacost_deck_ad_events';
    }

    public static function install() {
        global $wpdb;

        $table = self::table_name();
        $stored_version = get_option('unified_hs_ad_tracker_db_version');
        if ($stored_version === self::SCHEMA_VERSION && self::table_exists()) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ad_key VARCHAR(64) NOT NULL DEFAULT '',
            event_type VARCHAR(24) NOT NULL DEFAULT '',
            event_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            page_url TEXT NULL,
            PRIMARY KEY  (id),
            KEY ad_event_time (ad_key, event_type, event_time),
            KEY event_time (event_time)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('unified_hs_ad_tracker_db_version', self::SCHEMA_VERSION, false);
    }

    public static function click_url($ad_key = self::DEFAULT_AD_KEY) {
        return add_query_arg(array(
            'action' => 'hs_decks_ad_click',
            'ad' => sanitize_key($ad_key),
        ), admin_url('admin-post.php'));
    }

    public static function handle_click() {
        $ad_key = isset($_GET['ad']) ? sanitize_key(wp_unslash($_GET['ad'])) : self::DEFAULT_AD_KEY;
        self::record_event($ad_key, 'click');

        nocache_headers();
        wp_redirect(self::TARGET_URL, 302, 'Manacost Decks');
        exit;
    }

    public static function handle_impression() {
        if (!check_ajax_referer('hs_decks_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'bad_nonce'), 403);
        }

        $ad_key = isset($_POST['ad']) ? sanitize_key(wp_unslash($_POST['ad'])) : self::DEFAULT_AD_KEY;
        self::record_event($ad_key, 'impression');

        wp_send_json_success(array('ok' => true));
    }

    public static function record_event($ad_key, $event_type) {
        global $wpdb;

        if (!self::table_exists()) {
            return false;
        }

        $ad_key = sanitize_key($ad_key);
        $event_type = sanitize_key($event_type);
        if ($ad_key === '' || !in_array($event_type, array('click', 'impression'), true)) {
            return false;
        }

        return false !== $wpdb->insert(
            self::table_name(),
            array(
                'ad_key' => $ad_key,
                'event_type' => $event_type,
                'event_time' => current_time('mysql'),
                'page_url' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '',
            ),
            array('%s', '%s', '%s', '%s')
        );
    }

    public static function event_counts($from = '', $to = '', $ad_key = self::DEFAULT_AD_KEY) {
        global $wpdb;

        if (!self::table_exists()) {
            return array('impressions' => 0, 'clicks' => 0, 'ctr' => 0);
        }

        $ad_key = sanitize_key($ad_key);
        $where = array('ad_key = %s');
        $params = array($ad_key);

        if ($from !== '') {
            $where[] = 'event_time >= %s';
            $params[] = $from . ' 00:00:00';
        }
        if ($to !== '') {
            $where[] = 'event_time <= %s';
            $params[] = $to . ' 23:59:59';
        }

        $sql = $wpdb->prepare(
            "SELECT event_type, COUNT(*) AS total
             FROM " . self::table_name() . "
             WHERE " . implode(' AND ', $where) . "
             GROUP BY event_type",
            $params
        );

        $rows = $wpdb->get_results($sql, ARRAY_A);
        $counts = array('impressions' => 0, 'clicks' => 0, 'ctr' => 0);
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if ($row['event_type'] === 'impression') {
                    $counts['impressions'] = absint($row['total']);
                } elseif ($row['event_type'] === 'click') {
                    $counts['clicks'] = absint($row['total']);
                }
            }
        }

        $counts['ctr'] = $counts['impressions'] > 0 ? round(($counts['clicks'] / $counts['impressions']) * 100, 2) : 0;
        return $counts;
    }

    private static function table_exists() {
        global $wpdb;

        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }

        $table = self::table_name();
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
        return $exists;
    }
}
