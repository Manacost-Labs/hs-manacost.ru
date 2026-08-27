<?php
/**
 * Anonymous frontend engagement events for deck review decisions.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Unified_HS_Deck_Events {

    const SCHEMA_VERSION = '1';

    const EVENT_DECK_VIEW = 'deck_view';
    const EVENT_DECK_COPY = 'deck_copy';
    const EVENT_LIKE = 'like';
    const EVENT_DISLIKE = 'dislike';
    const EVENT_IMAGE_OPEN = 'image_open';
    const EVENT_PROOF_OPEN = 'proof_open';
    const EVENT_ARCHETYPE_OPEN = 'archetype_open';
    const EVENT_SOURCE_CLICK = 'source_click';
    const EVENT_FILTER_APPLY = 'filter_apply';
    const EVENT_STATS_SNAPSHOT = 'stats_snapshot';

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'manacost_deck_events';
    }

    public static function allowed_events() {
        return array(
            self::EVENT_DECK_VIEW,
            self::EVENT_DECK_COPY,
            self::EVENT_LIKE,
            self::EVENT_DISLIKE,
            self::EVENT_IMAGE_OPEN,
            self::EVENT_PROOF_OPEN,
            self::EVENT_ARCHETYPE_OPEN,
            self::EVENT_SOURCE_CLICK,
            self::EVENT_FILTER_APPLY,
            self::EVENT_STATS_SNAPSHOT,
        );
    }

    public static function install() {
        global $wpdb;

        $stored_version = get_option('unified_hs_deck_events_db_version');
        if ($stored_version === self::SCHEMA_VERSION) {
            return;
        }

        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            event_type VARCHAR(40) NOT NULL DEFAULT '',
            event_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            visitor_hash VARCHAR(64) NOT NULL DEFAULT '',
            source VARCHAR(120) NOT NULL DEFAULT '',
            context LONGTEXT NULL,
            PRIMARY KEY  (id),
            KEY event_time (event_time),
            KEY event_type_time (event_type, event_time),
            KEY post_event_time (post_id, event_type, event_time),
            KEY visitor_event (visitor_hash, event_type, event_time)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('unified_hs_deck_events_db_version', self::SCHEMA_VERSION, false);
    }

    public static function record($post_id, $event_type, array $context = array(), $source = '') {
        global $wpdb;

        $event_type = sanitize_key((string) $event_type);
        if (!in_array($event_type, self::allowed_events(), true) || !self::table_exists()) {
            return false;
        }

        $post_id = absint($post_id);
        if ($post_id && get_post_type($post_id) !== 'hs_deck') {
            return false;
        }

        return false !== $wpdb->insert(
            self::table_name(),
            array(
                'post_id' => $post_id,
                'event_type' => $event_type,
                'event_time' => current_time('mysql'),
                'visitor_hash' => self::visitor_hash(),
                'source' => sanitize_text_field((string) $source),
                'context' => !empty($context) ? wp_json_encode(self::sanitize_context($context)) : null,
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Записывает несколько однотипных событий одним INSERT.
     * Возвращает число вставленных строк, 0 для пустого набора или false при
     * ошибке — вызывающий код может безопасно вернуться к record().
     */
    public static function record_batch(array $post_ids, $event_type, array $context = array(), $source = '') {
        global $wpdb;

        $event_type = sanitize_key((string) $event_type);
        if (!in_array($event_type, self::allowed_events(), true) || !self::table_exists()) {
            return false;
        }

        $valid_ids = array();
        foreach (array_values(array_unique(array_map('absint', $post_ids))) as $post_id) {
            if (!$post_id || get_post_type($post_id) !== 'hs_deck') {
                continue;
            }
            $valid_ids[] = $post_id;
        }

        if (empty($valid_ids)) {
            return 0;
        }

        $event_time = current_time('mysql');
        $visitor_hash = self::visitor_hash();
        $source = sanitize_text_field((string) $source);
        $context_json = !empty($context) ? wp_json_encode(self::sanitize_context($context)) : '';
        $placeholders = array();
        $values = array();

        foreach ($valid_ids as $post_id) {
            $placeholders[] = "(%d, %s, %s, %s, %s, NULLIF(%s, ''))";
            $values[] = $post_id;
            $values[] = $event_type;
            $values[] = $event_time;
            $values[] = $visitor_hash;
            $values[] = $source;
            $values[] = $context_json;
        }

        $sql = 'INSERT INTO ' . self::table_name()
            . ' (post_id, event_type, event_time, visitor_hash, source, context) VALUES '
            . implode(', ', $placeholders);
        $prepared = $wpdb->prepare($sql, $values);

        return is_string($prepared) ? $wpdb->query($prepared) : false;
    }

    public static function record_stats_snapshot($post_id, $source = 'rest') {
        $post_id = absint($post_id);
        if (!$post_id || get_post_type($post_id) !== 'hs_deck') {
            return false;
        }

        $games = absint(get_post_meta($post_id, '_deck_games', true));
        $wins = absint(get_post_meta($post_id, '_deck_wins', true));
        $losses = absint(get_post_meta($post_id, '_deck_losses', true));
        $winrate = str_replace(',', '.', (string) get_post_meta($post_id, '_deck_winrate', true));
        $winrate = is_numeric($winrate) ? (float) $winrate : 0;

        if ($games <= 0 && ($wins + $losses) > 0) {
            $games = $wins + $losses;
        }

        if ($games <= 0 && $winrate <= 0) {
            return false;
        }

        return self::record($post_id, self::EVENT_STATS_SNAPSHOT, array(
            'games' => $games,
            'wins' => $wins,
            'losses' => $losses,
            'winrate' => round(max(0, min(100, $winrate)), 2),
            'snapshot_date' => current_time('Y-m-d'),
        ), $source);
    }

    public static function counts_by_post($from = '', $to = '', array $event_types = array()) {
        global $wpdb;

        if (!self::table_exists()) {
            return array();
        }

        $where = array('post_id > 0');
        $params = array();
        self::append_period_where($where, $params, $from, $to);

        $event_types = array_values(array_intersect(array_map('sanitize_key', $event_types), self::allowed_events()));
        if (!empty($event_types)) {
            $where[] = 'event_type IN (' . implode(',', array_fill(0, count($event_types), '%s')) . ')';
            $params = array_merge($params, $event_types);
        }

        $sql = "SELECT post_id, event_type, COUNT(*) AS events
             FROM " . self::table_name() . "
             WHERE " . implode(' AND ', $where) . "
             GROUP BY post_id, event_type";

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
            $event_type = sanitize_key($row['event_type']);
            if (!$post_id || $event_type === '') {
                continue;
            }
            if (!isset($counts[$post_id])) {
                $counts[$post_id] = array();
            }
            $counts[$post_id][$event_type] = absint($row['events']);
        }

        return $counts;
    }

    public static function summary_counts($from = '', $to = '') {
        global $wpdb;

        if (!self::table_exists()) {
            return array();
        }

        $where = array();
        $params = array();
        self::append_period_where($where, $params, $from, $to);

        $sql = "SELECT event_type, COUNT(*) AS events FROM " . self::table_name();
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY event_type';

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);
        $counts = array_fill_keys(self::allowed_events(), 0);
        if (!is_array($rows)) {
            return $counts;
        }

        foreach ($rows as $row) {
            $type = sanitize_key($row['event_type']);
            if (isset($counts[$type])) {
                $counts[$type] = absint($row['events']);
            }
        }

        return $counts;
    }

    public static function daily_counts($from = '', $to = '', $event_type = '') {
        global $wpdb;

        if (!self::table_exists()) {
            return array();
        }

        $where = array();
        $params = array();
        self::append_period_where($where, $params, $from, $to);

        $event_type = sanitize_key((string) $event_type);
        if ($event_type !== '' && in_array($event_type, self::allowed_events(), true)) {
            $where[] = 'event_type = %s';
            $params[] = $event_type;
        }

        $sql = "SELECT DATE(event_time) AS event_day, event_type, COUNT(*) AS events
             FROM " . self::table_name();
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY DATE(event_time), event_type ORDER BY event_day ASC';

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);
        return is_array($rows) ? $rows : array();
    }

    public static function daily_counts_for_archetype($term_id, $from, $to, $event_type = self::EVENT_DECK_COPY) {
        global $wpdb;

        $term_id = absint($term_id);
        $event_type = sanitize_key($event_type);
        if (!$term_id || !taxonomy_exists('deck_archetype') || !self::table_exists() || !in_array($event_type, self::allowed_events(), true)) {
            return array();
        }

        $sql = $wpdb->prepare(
            "SELECT DATE(e.event_time) AS event_day, COUNT(*) AS events
             FROM " . self::table_name() . " e
             INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = e.post_id
             INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
             WHERE tt.taxonomy = %s
               AND tt.term_id = %d
               AND e.event_type = %s
               AND e.event_time >= %s
               AND e.event_time <= %s
             GROUP BY DATE(e.event_time)
             ORDER BY event_day ASC",
            'deck_archetype',
            $term_id,
            $event_type,
            $from . ' 00:00:00',
            $to . ' 23:59:59'
        );

        $rows = $wpdb->get_results($sql, ARRAY_A);
        return is_array($rows) ? $rows : array();
    }

    public static function review_score(array $events, $games = 0, $published_ts = 0) {
        $weights = apply_filters('unified_hs_review_score_weights', array(
            self::EVENT_DECK_VIEW => 0.15,
            self::EVENT_DECK_COPY => 4.0,
            self::EVENT_LIKE => 3.0,
            self::EVENT_DISLIKE => -2.0,
            self::EVENT_IMAGE_OPEN => 0.75,
            self::EVENT_PROOF_OPEN => 1.0,
            self::EVENT_SOURCE_CLICK => 1.0,
            'games' => 0.05,
            'freshness' => 3.0,
        ));

        $score = 0.0;
        foreach (self::allowed_events() as $event_type) {
            if (!isset($weights[$event_type])) {
                continue;
            }
            $score += (float) $weights[$event_type] * absint(isset($events[$event_type]) ? $events[$event_type] : 0);
        }
        $score += (float) $weights['games'] * absint($games);

        $published_ts = absint($published_ts);
        if ($published_ts > 0) {
            $age_days = max(0, floor((current_time('timestamp') - $published_ts) / DAY_IN_SECONDS));
            $score += max(0, (float) $weights['freshness'] - min((float) $weights['freshness'], $age_days * 0.15));
        }

        return round($score, 2);
    }

    public static function event_value(array $events, $event_type) {
        $event_type = sanitize_key($event_type);
        return absint(isset($events[$event_type]) ? $events[$event_type] : 0);
    }

    private static function append_period_where(array &$where, array &$params, $from, $to) {
        $from = self::normalize_date($from);
        $to = self::normalize_date($to);

        if ($from !== '') {
            $where[] = 'event_time >= %s';
            $params[] = $from . ' 00:00:00';
        }
        if ($to !== '') {
            $where[] = 'event_time <= %s';
            $params[] = $to . ' 23:59:59';
        }
    }

    private static function normalize_date($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $date = DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : '';
    }

    private static function sanitize_context(array $context) {
        $clean = array();
        foreach ($context as $key => $value) {
            $key = sanitize_key((string) $key);
            if ($key === '') {
                continue;
            }
            if (is_array($value)) {
                $clean[$key] = array_map('sanitize_text_field', array_map('strval', $value));
            } else {
                $clean[$key] = sanitize_text_field((string) $value);
            }
        }
        return $clean;
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
