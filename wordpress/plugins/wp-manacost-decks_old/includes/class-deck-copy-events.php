<?php
/**
 * Stores per-copy events so archetype trends can be calculated by date.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Unified_HS_Deck_Copy_Events {

    const SCHEMA_VERSION = '1';

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'manacost_deck_copy_events';
    }

    public static function install() {
        global $wpdb;

        $table = self::table_name();
        $stored_version = get_option('unified_hs_deck_copy_events_db_version');
        if ($stored_version === self::SCHEMA_VERSION && self::table_exists()) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            event_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            visitor_hash VARCHAR(64) NOT NULL DEFAULT '',
            PRIMARY KEY  (id),
            KEY post_time (post_id, event_time),
            KEY event_time (event_time)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('unified_hs_deck_copy_events_db_version', self::SCHEMA_VERSION, false);
    }

    public static function record($post_id) {
        global $wpdb;

        $post_id = absint($post_id);
        if (!$post_id || get_post_type($post_id) !== 'hs_deck' || !self::table_exists()) {
            return false;
        }

        return false !== $wpdb->insert(
            self::table_name(),
            array(
                'post_id' => $post_id,
                'event_time' => current_time('mysql'),
                'visitor_hash' => self::visitor_hash(),
            ),
            array('%d', '%s', '%s')
        );
    }

    public static function daily_counts_for_archetype($term_id, $from, $to) {
        global $wpdb;

        $term_id = absint($term_id);
        if (!$term_id || !taxonomy_exists('deck_archetype') || !self::table_exists()) {
            return array();
        }

        $sql = $wpdb->prepare(
            "SELECT DATE(e.event_time) AS event_day, COUNT(*) AS copies
             FROM " . self::table_name() . " e
             INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = e.post_id
             INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
             WHERE tt.taxonomy = %s
               AND tt.term_id = %d
               AND e.event_time >= %s
               AND e.event_time <= %s
             GROUP BY DATE(e.event_time)
             ORDER BY event_day ASC",
            'deck_archetype',
            $term_id,
            $from . ' 00:00:00',
            $to . ' 23:59:59'
        );

        $rows = $wpdb->get_results($sql, ARRAY_A);
        return is_array($rows) ? $rows : array();
    }

    public static function counts_by_post($from, $to) {
        global $wpdb;

        if (!self::table_exists()) {
            return array();
        }

        $where = array();
        $params = array();

        if ($from !== '') {
            $where[] = 'event_time >= %s';
            $params[] = $from . ' 00:00:00';
        }
        if ($to !== '') {
            $where[] = 'event_time <= %s';
            $params[] = $to . ' 23:59:59';
        }

        $sql = "SELECT post_id, COUNT(*) AS copies
             FROM " . self::table_name();

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' GROUP BY post_id ORDER BY copies DESC';

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);
        if (!is_array($rows)) {
            return array();
        }

        $counts = array();
        foreach ($rows as $row) {
            $post_id = absint($row['post_id']);
            if ($post_id) {
                $counts[$post_id] = absint($row['copies']);
            }
        }

        return $counts;
    }

    private static function visitor_hash() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';

        return hash('sha256', $ip . '|' . $ua);
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
