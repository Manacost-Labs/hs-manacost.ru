<?php
/**
 * Activity log for editorial actions on Hearthstone decks.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Unified_HS_Deck_Activity_Log {

    const MAX_ROWS = 5000;
    const SCHEMA_VERSION = '1';
    const CREATED_META_KEY = '_hs_deck_activity_created_logged';

    private static $pending_meta_changes = array();

    public static function init() {
        add_action('wp_after_insert_post', array(__CLASS__, 'log_created_deck'), 20, 4);
        add_action('before_delete_post', array(__CLASS__, 'log_deleted_deck'), 5);
        add_filter('update_post_metadata', array(__CLASS__, 'capture_meta_before_update'), 10, 5);
        add_action('updated_post_meta', array(__CLASS__, 'log_updated_meta'), 10, 4);
        add_action('added_post_meta', array(__CLASS__, 'log_added_meta'), 10, 4);
        add_action('set_object_terms', array(__CLASS__, 'log_terms_change'), 10, 6);
    }

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'manacost_deck_activity_logs';
    }

    public static function install() {
        global $wpdb;

        $stored_version = get_option('unified_hs_deck_activity_logs_db_version');
        if ($stored_version === self::SCHEMA_VERSION) {
            return;
        }

        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            post_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            action VARCHAR(60) NOT NULL DEFAULT '',
            deck_title VARCHAR(255) NOT NULL DEFAULT '',
            field_key VARCHAR(100) NOT NULL DEFAULT '',
            old_value TEXT NOT NULL,
            new_value TEXT NOT NULL,
            message TEXT NOT NULL,
            context VARCHAR(60) NOT NULL DEFAULT '',
            PRIMARY KEY  (id),
            KEY created_at (created_at),
            KEY post_id (post_id),
            KEY user_id (user_id),
            KEY action (action)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('unified_hs_deck_activity_logs_db_version', self::SCHEMA_VERSION, false);
    }

    public static function add(array $entry, $do_prune = true) {
        global $wpdb;

        $post_id = isset($entry['post_id']) ? absint($entry['post_id']) : 0;
        $post = $post_id ? get_post($post_id) : null;
        $deck_title = isset($entry['deck_title']) ? $entry['deck_title'] : '';
        if ($deck_title === '' && $post) {
            $deck_title = $post->post_title;
        }

        $row = array(
            'post_id'    => $post_id,
            'user_id'    => isset($entry['user_id']) ? absint($entry['user_id']) : get_current_user_id(),
            'action'     => isset($entry['action']) ? sanitize_key((string) $entry['action']) : '',
            'deck_title' => sanitize_text_field((string) $deck_title),
            'field_key'  => isset($entry['field_key']) ? sanitize_key((string) $entry['field_key']) : '',
            'old_value'  => isset($entry['old_value']) ? sanitize_textarea_field((string) $entry['old_value']) : '',
            'new_value'  => isset($entry['new_value']) ? sanitize_textarea_field((string) $entry['new_value']) : '',
            'message'    => isset($entry['message']) ? sanitize_textarea_field((string) $entry['message']) : '',
            'context'    => isset($entry['context']) ? sanitize_key((string) $entry['context']) : self::detect_context(),
        );

        if ($row['action'] === '') {
            return;
        }

        $wpdb->insert(
            self::table_name(),
            $row,
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($do_prune && mt_rand(1, 50) === 1) {
            self::prune();
        }
    }

    public static function recent($limit = 200) {
        global $wpdb;

        $limit = max(1, min(1000, (int) $limit));
        $table = self::table_name();
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, created_at, post_id, user_id, action, deck_title, field_key, old_value, new_value, message, context
             FROM {$table} ORDER BY id DESC LIMIT %d",
            $limit
        ), ARRAY_A);

        return is_array($rows) ? $rows : array();
    }

    public static function clear() {
        global $wpdb;
        $wpdb->query('DELETE FROM ' . self::table_name());
    }

    public static function prune() {
        global $wpdb;
        $table = self::table_name();
        $max = (int) self::MAX_ROWS;

        $boundary = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} ORDER BY id DESC LIMIT 1 OFFSET %d",
            $max
        ));

        if ($boundary !== null) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$table} WHERE id <= %d",
                (int) $boundary
            ));
        }
    }

    public static function log_created_deck($post_id, $post, $update, $post_before) {
        if (!$post || $post->post_type !== 'hs_deck' || $post->post_status === 'auto-draft') {
            return;
        }
        if (wp_is_post_revision($post_id) || metadata_exists('post', $post_id, self::CREATED_META_KEY)) {
            return;
        }

        self::add(array(
            'post_id' => $post_id,
            'action' => 'created',
            'message' => 'Создана колода',
            'new_value' => $post->post_title,
        ));

        update_post_meta($post_id, self::CREATED_META_KEY, '1');
    }

    public static function log_deleted_deck($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'hs_deck') {
            return;
        }

        self::add(array(
            'post_id' => $post_id,
            'deck_title' => $post->post_title,
            'action' => 'deleted',
            'message' => 'Удалена колода',
            'old_value' => $post->post_title,
        ));
    }

    public static function capture_meta_before_update($check, $object_id, $meta_key, $meta_value, $prev_value) {
        if (!self::is_tracked_meta_key($meta_key) || get_post_type($object_id) !== 'hs_deck') {
            return $check;
        }

        if (!isset(self::$pending_meta_changes[$object_id])) {
            self::$pending_meta_changes[$object_id] = array();
        }

        self::$pending_meta_changes[$object_id][$meta_key] = get_post_meta($object_id, $meta_key, true);
        return $check;
    }

    public static function log_updated_meta($meta_id, $object_id, $meta_key, $meta_value) {
        if (!self::is_tracked_meta_key($meta_key) || get_post_type($object_id) !== 'hs_deck') {
            return;
        }

        $old_value = '';
        if (isset(self::$pending_meta_changes[$object_id]) && array_key_exists($meta_key, self::$pending_meta_changes[$object_id])) {
            $old_value = self::$pending_meta_changes[$object_id][$meta_key];
            unset(self::$pending_meta_changes[$object_id][$meta_key]);
        }

        self::log_meta_change($object_id, $meta_key, $old_value, $meta_value);
    }

    public static function log_added_meta($meta_id, $object_id, $meta_key, $meta_value) {
        if (!in_array($meta_key, array('_deck_code', '_custom_tags'), true) || get_post_type($object_id) !== 'hs_deck') {
            return;
        }

        $post = get_post($object_id);
        if (!$post || $post->post_status === 'auto-draft' || trim((string) $meta_value) === '') {
            return;
        }

        self::log_meta_change($object_id, $meta_key, '', $meta_value);
    }

    public static function log_terms_change($object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids) {
        if (get_post_type($object_id) !== 'hs_deck' || !in_array($taxonomy, array('deck_class', 'deck_mode'), true)) {
            return;
        }

        $old_ids = array_map('intval', is_array($old_tt_ids) ? $old_tt_ids : array());
        $new_ids = array_map('intval', is_array($tt_ids) ? $tt_ids : array());
        sort($old_ids);
        sort($new_ids);

        if ($old_ids === $new_ids) {
            return;
        }

        self::add(array(
            'post_id' => $object_id,
            'action' => $taxonomy === 'deck_class' ? 'deck_class_updated' : 'deck_mode_updated',
            'field_key' => $taxonomy,
            'old_value' => implode(', ', self::term_names_from_tt_ids($old_ids, $taxonomy)),
            'new_value' => implode(', ', self::term_names_from_tt_ids($new_ids, $taxonomy)),
            'message' => $taxonomy === 'deck_class' ? 'Изменён класс колоды' : 'Изменён режим колоды',
        ));
    }

    public static function action_label($action) {
        $labels = array(
            'created' => 'Создание',
            'deleted' => 'Удаление',
            'deck_code_updated' => 'Код колоды',
            'custom_tags_updated' => 'Теги',
            'opened_in_feed' => 'Открыта в ленте',
            'hidden_from_feed' => 'Скрыта из ленты',
            'archived' => 'Архивирована',
            'restored_from_archive' => 'Восстановлена из архива',
            'excluded_from_random' => 'Исключена из рандома',
            'included_in_random' => 'Возвращена в рандом',
            'deck_class_updated' => 'Класс',
            'deck_mode_updated' => 'Режим',
        );

        return isset($labels[$action]) ? $labels[$action] : $action;
    }

    private static function log_meta_change($post_id, $meta_key, $old_value, $new_value) {
        $old_value = self::stringify_value($old_value);
        $new_value = self::stringify_value($new_value);

        if ($old_value === $new_value) {
            return;
        }

        $spec = self::meta_action_spec($meta_key, $old_value, $new_value);
        if (!$spec) {
            return;
        }

        self::add(array(
            'post_id' => $post_id,
            'action' => $spec['action'],
            'field_key' => $meta_key,
            'old_value' => $old_value,
            'new_value' => $new_value,
            'message' => $spec['message'],
        ));
    }

    private static function meta_action_spec($meta_key, $old_value, $new_value) {
        if ($meta_key === '_deck_code') {
            return array('action' => 'deck_code_updated', 'message' => 'Изменён код колоды');
        }
        if ($meta_key === '_custom_tags') {
            return array('action' => 'custom_tags_updated', 'message' => 'Изменены теги колоды');
        }
        if ($meta_key === '_hide_from_feed') {
            if ($old_value === '1' && $new_value === '0') {
                return array('action' => 'opened_in_feed', 'message' => 'Колода открыта в ленте');
            }
            if ($old_value === '0' && $new_value === '1') {
                return array('action' => 'hidden_from_feed', 'message' => 'Колода скрыта из ленты');
            }
        }
        if ($meta_key === '_exclude_from_random') {
            if ($old_value === '0' && $new_value === '1') {
                return array('action' => 'excluded_from_random', 'message' => 'Колода исключена из случайных подборок');
            }
            if ($old_value === '1' && $new_value === '0') {
                return array('action' => 'included_in_random', 'message' => 'Колода возвращена в случайные подборки');
            }
        }
        if ($meta_key === '_hs_deck_archived') {
            if ($new_value === '1') {
                return array('action' => 'archived', 'message' => 'Колода перемещена в архив');
            }
            if ($old_value === '1' && $new_value === '0') {
                return array('action' => 'restored_from_archive', 'message' => 'Колода восстановлена из архива');
            }
        }

        return null;
    }

    private static function is_tracked_meta_key($meta_key) {
        return in_array($meta_key, array(
            '_deck_code',
            '_custom_tags',
            '_hide_from_feed',
            '_exclude_from_random',
            '_hs_deck_archived',
        ), true);
    }

    private static function stringify_value($value) {
        if (is_array($value) || is_object($value)) {
            return wp_json_encode($value);
        }

        return (string) $value;
    }

    private static function term_names_from_tt_ids(array $tt_ids, $taxonomy) {
        $names = array();
        foreach ($tt_ids as $tt_id) {
            $term = get_term_by('term_taxonomy_id', (int) $tt_id, $taxonomy);
            if ($term && !is_wp_error($term)) {
                $names[] = $term->name;
            }
        }

        return $names;
    }

    private static function detect_context() {
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return 'rest';
        }
        if (defined('WP_CLI') && WP_CLI) {
            return 'cli';
        }
        if (wp_doing_ajax()) {
            return 'ajax';
        }
        if (is_admin()) {
            return 'admin';
        }

        return 'frontend';
    }
}
