<?php
/**
 * Anonymous frontend engagement events for deck review decisions.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Unified_HS_Deck_Events {

    const SCHEMA_VERSION = '3';

    const RETENTION_DAYS = 90;
    const RETENTION_HOOK = 'unified_hs_deck_events_retention';
    const RETENTION_TIME_BUDGET = 20;
    const RETENTION_DELETE_BATCH = 5000;
    const ROLLED_UNTIL_OPTION = 'unified_hs_deck_events_rolled_until';

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

    public static function init() {
        add_action('init', array(__CLASS__, 'ensure_schedule'));
        add_action(self::RETENTION_HOOK, array(__CLASS__, 'run_retention'));
    }

    public static function ensure_schedule() {
        if (!wp_next_scheduled(self::RETENTION_HOOK)) {
            wp_schedule_event(time() + 600, 'hourly', self::RETENTION_HOOK);
        }
    }

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'manacost_deck_events';
    }

    /** Per-day counts for days whose raw rows passed RETENTION_DAYS. */
    public static function days_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'manacost_deck_event_days';
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
            KEY post_event_time (post_id, event_type, event_time)
        ) {$charset_collate};
        CREATE TABLE " . self::days_table_name() . " (
            event_day DATE NOT NULL,
            post_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            event_type VARCHAR(40) NOT NULL DEFAULT '',
            events INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY  (event_day, post_id, event_type),
            KEY post_event_day (post_id, event_type, event_day),
            KEY event_type_day (event_type, event_day)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        if (!self::drop_unused_indexes($table)) {
            return;
        }

        update_option('unified_hs_deck_events_db_version', self::SCHEMA_VERSION, false);
    }

    /**
     * dbDelta() never drops indexes. No query filters by visitor_hash, yet
     * visitor_event was the largest index and every frontend insert paid for it.
     * A failed drop leaves the version unchanged, so the next admin load retries.
     */
    private static function drop_unused_indexes($table) {
        global $wpdb;

        $exists = $wpdb->get_var($wpdb->prepare("SHOW INDEX FROM `{$table}` WHERE Key_name = %s", 'visitor_event'));
        if ($exists === null) {
            return true;
        }

        // Frontend inserts queue behind a waiting ALTER, so give up quickly.
        $lock_wait_timeout = (int) $wpdb->get_var('SELECT @@SESSION.lock_wait_timeout');
        $wpdb->query('SET SESSION lock_wait_timeout = 3');
        $dropped = $wpdb->query("ALTER TABLE `{$table}` DROP INDEX visitor_event, ALGORITHM=INPLACE, LOCK=NONE");
        $wpdb->query($wpdb->prepare('SET SESSION lock_wait_timeout = %d', $lock_wait_timeout));

        return $dropped !== false;
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
        if (!self::table_exists()) {
            return array();
        }

        $where = array('post_id > 0');
        $params = array();

        $event_types = array_values(array_intersect(array_map('sanitize_key', $event_types), self::allowed_events()));
        if (!empty($event_types)) {
            $where[] = 'event_type IN (' . implode(',', array_fill(0, count($event_types), '%s')) . ')';
            $params = array_merge($params, $event_types);
        }

        $rows = self::count_rows(array('post_id' => 'post_id', 'event_type' => 'event_type'), $from, $to, $where, $params);

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
        if (!self::table_exists()) {
            return array();
        }

        $rows = self::count_rows(array('event_type' => 'event_type'), $from, $to);
        $counts = array_fill_keys(self::allowed_events(), 0);

        foreach ($rows as $row) {
            $type = sanitize_key($row['event_type']);
            if (isset($counts[$type])) {
                $counts[$type] = absint($row['events']);
            }
        }

        return $counts;
    }

    public static function daily_counts($from = '', $to = '', $event_type = '') {
        if (!self::table_exists()) {
            return array();
        }

        $where = array();
        $params = array();

        $event_type = sanitize_key((string) $event_type);
        if ($event_type !== '' && in_array($event_type, self::allowed_events(), true)) {
            $where[] = 'event_type = %s';
            $params[] = $event_type;
        }

        return self::count_rows(
            array('event_day' => 'DATE(event_time)', 'event_type' => 'event_type'),
            $from,
            $to,
            $where,
            $params,
            'event_day ASC, event_type ASC'
        );
    }

    public static function daily_counts_for_archetype($term_id, $from, $to, $event_type = self::EVENT_DECK_COPY) {
        $term_id = absint($term_id);
        $event_type = sanitize_key($event_type);
        if (!$term_id || !taxonomy_exists('deck_archetype') || !self::table_exists() || !in_array($event_type, self::allowed_events(), true)) {
            return array();
        }

        global $wpdb;

        // event_time, event_day, post_id and event_type exist only in the events tables.
        $join = "INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = e.post_id
             INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id";

        return self::count_rows(
            array('event_day' => 'DATE(event_time)'),
            $from,
            $to,
            array('tt.taxonomy = %s', 'tt.term_id = %d', 'event_type = %s'),
            array('deck_archetype', $term_id, $event_type),
            'event_day ASC',
            $join
        );
    }

    /**
     * Rolls days older than RETENTION_DAYS into the daily table, then deletes
     * their raw rows in small batches. Each day is rolled before the boundary
     * moves past it, and only rows before the boundary are deleted, so readers
     * never miss or double-count a day. Runs hourly until the backlog is gone.
     */
    public static function run_retention() {
        global $wpdb;

        if (!self::table_exists() || !self::days_table_exists()) {
            return;
        }

        // WP-cron's own lock expires after 60 s; a second runner must not
        // re-roll a day whose raw rows the first one is deleting.
        if ((string) $wpdb->get_var("SELECT GET_LOCK('" . self::RETENTION_HOOK . "', 0)") !== '1') {
            return;
        }

        try {
            self::roll_up_and_delete();
        } finally {
            $wpdb->query("DO RELEASE_LOCK('" . self::RETENTION_HOOK . "')");
        }
    }

    private static function roll_up_and_delete() {
        global $wpdb;

        $table = self::table_name();
        $days_table = self::days_table_name();
        $deadline = microtime(true) + self::RETENTION_TIME_BUDGET;
        $cutoff = (string) $wpdb->get_var($wpdb->prepare('SELECT CURDATE() - INTERVAL %d DAY', self::RETENTION_DAYS));
        if (self::normalize_date($cutoff) === '') {
            error_log('Deck events retention: could not compute the cutoff date');
            return;
        }

        $day = self::rolled_until();
        if ($day === '') {
            // A lost option must not re-roll days whose raw rows are already gone.
            $rolled = self::normalize_date((string) $wpdb->get_var("SELECT MAX(event_day) FROM {$days_table}"));
            $oldest = self::normalize_date((string) $wpdb->get_var("SELECT DATE(MIN(event_time)) FROM {$table}"));
            if ($rolled !== '') {
                $day = self::next_day($rolled);
                update_option(self::ROLLED_UNTIL_OPTION, $day, false);
            } else {
                $day = $oldest !== '' && $oldest < $cutoff ? $oldest : $cutoff;
            }
        }

        while ($day < $cutoff && microtime(true) < $deadline) {
            $next = self::next_day($day);
            // Days before the cutoff never gain events, so a rerun never needs a smaller count.
            $rolled = $wpdb->query($wpdb->prepare(
                "INSERT INTO {$days_table} (event_day, post_id, event_type, events)
                 SELECT %s, post_id, event_type, COUNT(*) FROM {$table}
                 WHERE event_time >= %s AND event_time < %s
                 GROUP BY post_id, event_type
                 ON DUPLICATE KEY UPDATE events = GREATEST(events, VALUES(events))",
                $day,
                $day . ' 00:00:00',
                $next . ' 00:00:00'
            ));
            if ($rolled === false) {
                error_log('Deck events retention: rollup failed for ' . $day . ': ' . $wpdb->last_error);
                return;
            }
            if ($next > self::rolled_until()) {
                update_option(self::ROLLED_UNTIL_OPTION, $next, false);
            }
            $day = $next;
        }

        $rolled_until = self::rolled_until();
        if ($rolled_until === '') {
            return;
        }

        // Stay one day behind the boundary: a report that read the previous
        // boundary still finds that day's raw rows.
        $delete_before = (new DateTimeImmutable($rolled_until, new DateTimeZone('UTC')))->modify('-1 day')->format('Y-m-d');
        while (microtime(true) < $deadline) {
            $deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$table} WHERE event_time < %s ORDER BY event_time, id LIMIT %d",
                $delete_before . ' 00:00:00',
                self::RETENTION_DELETE_BATCH
            ));
            if ($deleted === false) {
                error_log('Deck events retention: delete failed: ' . $wpdb->last_error);
                return;
            }
            if ($deleted === 0) {
                return;
            }
        }
    }

    private static function next_day($day) {
        return (new DateTimeImmutable($day, new DateTimeZone('UTC')))->modify('+1 day')->format('Y-m-d');
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

    /**
     * Days before rolled_until() live only in the daily table and later days
     * only in raw rows, so a period reads both halves without counting a day
     * twice. $columns maps output columns to raw expressions; $where may use
     * post_id and event_type, which both tables share, and columns of $join,
     * which joins against alias e in both halves.
     */
    private static function count_rows(array $columns, $from, $to, array $where = array(), array $params = array(), $order_by = '', $join = '') {
        global $wpdb;

        $from = self::normalize_date($from);
        $to = self::normalize_date($to);
        $rolled_until = self::rolled_until();

        $raw_where = $where;
        $raw_params = $params;
        if ($from !== '') {
            $raw_where[] = 'event_time >= %s';
            $raw_params[] = $from . ' 00:00:00';
        }
        if ($to !== '') {
            $raw_where[] = 'event_time <= %s';
            $raw_params[] = $to . ' 23:59:59';
        }
        if ($rolled_until !== '') {
            $raw_where[] = 'event_time >= %s';
            $raw_params[] = $rolled_until . ' 00:00:00';
        }

        $raw_select = array();
        foreach ($columns as $column => $expression) {
            $raw_select[] = "{$expression} AS {$column}";
        }
        $sql = 'SELECT ' . implode(', ', $raw_select) . ', COUNT(*) AS events FROM ' . self::table_name() . ' e ' . $join
            . self::where_sql($raw_where)
            . ' GROUP BY ' . implode(', ', array_values($columns));
        $all_params = $raw_params;

        if ($rolled_until !== '' && ($from === '' || $from < $rolled_until)) {
            $day_where = $where;
            $day_params = $params;
            $day_where[] = 'event_day < %s';
            $day_params[] = $rolled_until;
            if ($from !== '') {
                $day_where[] = 'event_day >= %s';
                $day_params[] = $from;
            }
            if ($to !== '') {
                $day_where[] = 'event_day <= %s';
                $day_params[] = $to;
            }

            $names = implode(', ', array_keys($columns));
            $sql = "SELECT {$names}, SUM(events) AS events FROM ({$sql}
                 UNION ALL
                 SELECT {$names}, SUM(events) FROM " . self::days_table_name() . ' e ' . $join . self::where_sql($day_where) . "
                 GROUP BY {$names}) u
                 GROUP BY {$names}";
            $all_params = array_merge($raw_params, $day_params);
        }

        if ($order_by !== '') {
            $sql .= ' ORDER BY ' . $order_by;
        }
        if (!empty($all_params)) {
            $sql = $wpdb->prepare($sql, $all_params);
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);
        return is_array($rows) ? $rows : array();
    }

    private static function where_sql(array $where) {
        return empty($where) ? '' : ' WHERE ' . implode(' AND ', $where);
    }

    private static function rolled_until() {
        return self::normalize_date((string) get_option(self::ROLLED_UNTIL_OPTION, ''));
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

    private static function days_table_exists() {
        global $wpdb;

        $table = self::days_table_name();
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }
}
