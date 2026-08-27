<?php
/**
 * Period-based statistics storage and import helpers for Hearthstone decks.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Unified_HS_Deck_Period_Stats {

    const SCHEMA_VERSION = '1';

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'manacost_deck_period_stats';
    }

    public static function install() {
        global $wpdb;

        $table = self::table_name();
        $stored_version = get_option('unified_hs_deck_period_stats_db_version');
        if ($stored_version === self::SCHEMA_VERSION && self::table_exists()) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            period_start DATE NOT NULL,
            period_end DATE NOT NULL,
            games INT UNSIGNED NOT NULL DEFAULT 0,
            wins INT UNSIGNED NOT NULL DEFAULT 0,
            losses INT UNSIGNED NOT NULL DEFAULT 0,
            winrate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            source VARCHAR(120) NOT NULL DEFAULT '',
            imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY post_period (post_id, period_start, period_end),
            KEY post_id (post_id),
            KEY period_start (period_start),
            KEY period_end (period_end)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('unified_hs_deck_period_stats_db_version', self::SCHEMA_VERSION, false);
    }

    private static function table_exists() {
        global $wpdb;

        $table = self::table_name();
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }

    public static function import_rows(array $rows, array $defaults = array(), $apply_to_cards = true) {
        global $wpdb;

        $result = array(
            'imported' => 0,
            'skipped' => 0,
            'errors' => array(),
            'affected_post_ids' => array(),
        );

        foreach ($rows as $index => $row) {
            $normalized = self::normalize_row($row, $defaults);
            if (is_wp_error($normalized)) {
                $result['skipped']++;
                $result['errors'][] = sprintf('Строка %d: %s', $index + 1, $normalized->get_error_message());
                continue;
            }

            $ok = $wpdb->replace(
                self::table_name(),
                array(
                    'post_id' => $normalized['post_id'],
                    'period_start' => $normalized['period_start'],
                    'period_end' => $normalized['period_end'],
                    'games' => $normalized['games'],
                    'wins' => $normalized['wins'],
                    'losses' => $normalized['losses'],
                    'winrate' => $normalized['winrate'],
                    'source' => $normalized['source'],
                    'imported_at' => current_time('mysql'),
                    'user_id' => get_current_user_id(),
                ),
                array('%d', '%s', '%s', '%d', '%d', '%d', '%f', '%s', '%s', '%d')
            );

            if (false === $ok) {
                $result['skipped']++;
                $result['errors'][] = sprintf('Строка %d: база данных не приняла запись.', $index + 1);
                continue;
            }

            $result['imported']++;
            $result['affected_post_ids'][$normalized['post_id']] = $normalized['post_id'];
        }

        if ($apply_to_cards && !empty($result['affected_post_ids'])) {
            self::recalculate_deck_totals(array_values($result['affected_post_ids']));
        }

        return $result;
    }

    public static function recalculate_deck_totals(array $post_ids) {
        global $wpdb;

        $post_ids = array_values(array_filter(array_map('absint', $post_ids)));
        if (empty($post_ids)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
        $sql = $wpdb->prepare(
            "SELECT post_id, SUM(games) AS games, SUM(wins) AS wins, SUM(losses) AS losses
             FROM " . self::table_name() . "
             WHERE post_id IN ({$placeholders})
             GROUP BY post_id",
            $post_ids
        );
        $rows = $wpdb->get_results($sql, ARRAY_A);

        foreach ($rows as $row) {
            $post_id = absint($row['post_id']);
            $games = absint($row['games']);
            $wins = absint($row['wins']);
            $losses = absint($row['losses']);
            $winrate = $games > 0 ? round(($wins / $games) * 100, 1) : 0;

            update_post_meta($post_id, '_deck_games', $games);
            update_post_meta($post_id, '_deck_wins', $wins);
            update_post_meta($post_id, '_deck_losses', $losses);
            update_post_meta($post_id, '_deck_winrate', $winrate);
        }

        if (class_exists('HS_Decks_Manager')) {
            $ver = HS_Decks_Manager::get_decks_cache_version();
            update_option('hs_decks_cache_version', $ver + 1, false);
        }
    }

    private static function normalize_row($row, array $defaults) {
        if (is_object($row)) {
            $row = get_object_vars($row);
        }
        if (!is_array($row)) {
            return new WP_Error('invalid_row', 'строка должна быть объектом или массивом');
        }

        $post_id = self::first_int($row, array('post_id', 'deck_id', 'id'));
        if (!$post_id || get_post_type($post_id) !== 'hs_deck') {
            return new WP_Error('invalid_deck', 'не найден deck_id/post_id существующей колоды');
        }

        $period = self::resolve_period($row, $defaults);
        if (is_wp_error($period)) {
            return $period;
        }

        $wins = self::first_int($row, array('wins', 'win'));
        $losses = self::first_int($row, array('losses', 'loss'));
        $games = self::first_int($row, array('games', 'matches', 'total_games'));
        $winrate = self::first_float($row, array('winrate', 'wr', 'win_rate'));

        if ($games <= 0 && ($wins > 0 || $losses > 0)) {
            $games = $wins + $losses;
        }
        if ($games <= 0) {
            return new WP_Error('missing_games', 'укажите games или wins/losses');
        }
        if ($winrate < 0 && $wins > 0) {
            $winrate = round(($wins / $games) * 100, 1);
        }
        if ($winrate < 0) {
            $winrate = 0;
        }
        $winrate = max(0, min(100, (float) $winrate));

        if ($wins <= 0 && $losses <= 0 && $winrate > 0) {
            $wins = (int) round($games * ($winrate / 100));
            $losses = max(0, $games - $wins);
        }
        if ($wins + $losses > $games) {
            $games = $wins + $losses;
        }

        return array(
            'post_id' => $post_id,
            'period_start' => $period['start'],
            'period_end' => $period['end'],
            'games' => $games,
            'wins' => $wins,
            'losses' => $losses,
            'winrate' => round($winrate, 1),
            'source' => sanitize_text_field((string) self::first_value($row, array('source', 'label', 'period_label'), 'import')),
        );
    }

    private static function resolve_period(array $row, array $defaults) {
        $start = self::first_value($row, array('period_start', 'start', 'from'), isset($defaults['period_start']) ? $defaults['period_start'] : '');
        $end = self::first_value($row, array('period_end', 'end', 'to'), isset($defaults['period_end']) ? $defaults['period_end'] : '');
        $period = self::first_value($row, array('period', 'month', 'date'), '');

        if ($start === '' && $end === '' && $period !== '') {
            if (preg_match('/^\d{4}-\d{2}$/', $period)) {
                $start = $period . '-01';
                $date = DateTime::createFromFormat('Y-m-d', $start);
                $end = $date ? $date->format('Y-m-t') : '';
            } else {
                $start = $period;
                $end = $period;
            }
        }

        $start = self::normalize_date($start);
        $end = self::normalize_date($end);
        if (!$start || !$end) {
            return new WP_Error('invalid_period', 'укажите period_start/period_end или period');
        }
        if ($start > $end) {
            return new WP_Error('invalid_period_order', 'period_start не может быть позже period_end');
        }

        return array('start' => $start, 'end' => $end);
    }

    private static function normalize_date($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        foreach (array('Y-m-d', 'd.m.Y', 'Y/m/d') as $format) {
            $date = DateTime::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return '';
    }

    private static function first_value(array $row, array $keys, $default = '') {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== '') {
                return $row[$key];
            }
        }
        return $default;
    }

    private static function first_int(array $row, array $keys) {
        $value = self::first_value($row, $keys, 0);
        return max(0, absint($value));
    }

    private static function first_float(array $row, array $keys) {
        $value = self::first_value($row, $keys, -1);
        $value = str_replace(',', '.', (string) $value);
        return is_numeric($value) ? (float) $value : -1;
    }
}
