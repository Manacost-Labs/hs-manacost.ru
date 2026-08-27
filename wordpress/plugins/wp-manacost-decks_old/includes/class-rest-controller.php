<?php
/**
 * REST API endpoints плагина (`/manacost/v1/*`).
 *
 * До рефакторинга роуты регистрировались анонимными функциями прямо в главном
 * файле `unified-hs-plugins.php`. Здесь они переехали в одну точку, что:
 *  - облегчает аудит безопасности (все permission_callback / args / sanitize в одном месте);
 *  - позволяет тестировать через PHPUnit / WP_REST_Server::dispatch() без подгрузки
 *    всего плагина;
 *  - даёт пространство для будущего расширения (новые endpoint'ы добавляются
 *    как методы этого класса).
 *
 * Endpoint'ы используют отдельные capabilities, чтобы роли можно было настраивать
 * через Members или любой другой редактор ролей:
 *  POST /manacost/v1/ingest-log         — пишет в Unified_HS_Ingest_Logs
 *  POST /manacost/v1/decks              — создаёт колоду из внешнего импорта
 *  POST /manacost/v1/deck-meta/{id}     — обновляет meta колоды hs_deck
 */

if (!defined('ABSPATH')) {
    exit;
}

class Unified_HS_REST_Controller {

    const NAMESPACE_V1 = 'manacost/v1';

    /**
     * Регистрирует обработчик `rest_api_init`.
     */
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }

    public static function register_routes() {
        register_rest_route(self::NAMESPACE_V1, '/ingest-log', array(
            'methods'             => 'POST',
            'permission_callback' => array(__CLASS__, 'permission_ingest_log'),
            'callback'            => array(__CLASS__, 'handle_ingest_log'),
        ));

        register_rest_route(self::NAMESPACE_V1, '/decks', array(
            'methods'             => 'POST',
            'permission_callback' => array(__CLASS__, 'permission_create_deck'),
            'callback'            => array(__CLASS__, 'handle_create_deck'),
        ));

        register_rest_route(self::NAMESPACE_V1, '/deck-meta/(?P<id>\d+)', array(
            'methods'             => 'POST',
            'permission_callback' => array(__CLASS__, 'permission_deck_meta'),
            'callback'            => array(__CLASS__, 'handle_deck_meta'),
            'args'                => array(
                'id' => array(
                    'validate_callback' => function ($value) {
                        return is_numeric($value) && (int) $value > 0;
                    },
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));
    }

    /**
     * Логи импорта доступны только ролям, которым явно разрешён импорт колод.
     */
    public static function permission_ingest_log($request = null) {
        return current_user_can(Unified_HS_Capabilities::CAP_IMPORT_DECKS);
    }

    public static function permission_create_deck($request = null) {
        return current_user_can(Unified_HS_Capabilities::CAP_IMPORT_DECKS);
    }

    /**
     * Метаданные можно обновлять либо через отдельное REST-право импорта,
     * либо через обычное право редактирования конкретной колоды.
     */
    public static function permission_deck_meta(WP_REST_Request $request) {
        $post_id = isset($request['id']) ? absint($request['id']) : 0;
        if (!$post_id) {
            return false;
        }

        return current_user_can(Unified_HS_Capabilities::CAP_IMPORT_DECKS)
            || current_user_can('edit_post', $post_id);
    }

    /**
     * POST /manacost/v1/ingest-log
     * Пишет запись в кастомную таблицу логов импорта.
     */
    public static function handle_ingest_log(WP_REST_Request $request) {
        $body = $request->get_json_params();
        if (!is_array($body)) {
            $body = array();
        }
        if (class_exists('Unified_HS_Ingest_Logs')) {
            Unified_HS_Ingest_Logs::add(array(
                'time'      => current_time('mysql'),
                'status'    => isset($body['status']) ? $body['status'] : '',
                'deck_name' => isset($body['deck_name']) ? $body['deck_name'] : '',
                'deck_code' => isset($body['deck_code']) ? $body['deck_code'] : '',
                'streamer'  => isset($body['streamer']) ? $body['streamer'] : '',
                'format'    => isset($body['format']) ? $body['format'] : '',
                'message'   => isset($body['message']) ? $body['message'] : '',
            ));
        }
        return rest_ensure_response(array('ok' => true));
    }

    /**
     * POST /manacost/v1/decks
     * Создаёт новую колоду из Telegram/внешнего импорта.
     */
    public static function handle_create_deck(WP_REST_Request $request) {
        $body = $request->get_json_params();
        if (!is_array($body)) {
            $body = array();
        }

        $title = sanitize_text_field((string) self::first_body_value($body, array('title', 'deck_name', 'name'), ''));
        $deck_code = sanitize_textarea_field((string) self::first_body_value($body, array('deck_code', 'code', 'deckstring'), ''));
        if ($title === '') {
            return new WP_Error('missing_title', 'Deck title is required', array('status' => 400));
        }
        if ($deck_code === '') {
            return new WP_Error('missing_deck_code', 'Deck code is required', array('status' => 400));
        }

        $status = sanitize_key((string) self::first_body_value($body, array('status', 'post_status'), 'publish'));
        if (!in_array($status, array('publish', 'private', 'draft', 'pending'), true)) {
            $status = 'publish';
        }

        if (array_key_exists('publish_to_feed', $body)) {
            $body['hide_from_feed'] = rest_sanitize_boolean($body['publish_to_feed']) ? '0' : '1';
        } elseif (!array_key_exists('hide_from_feed', $body)) {
            $body['hide_from_feed'] = '1';
        }

        if (!empty($body['dedupe_by_deck_code']) && rest_sanitize_boolean($body['dedupe_by_deck_code'])) {
            $existing_id = self::find_existing_deck_by_code($deck_code);
            if ($existing_id) {
                $body['deck_code'] = $deck_code;
                // A repeated automated import must not undo an editor's feed visibility choice.
                unset($body['hide_from_feed'], $body['publish_to_feed']);
                self::preserve_existing_source_on_cross_source_match($existing_id, $body);
                $updated = self::apply_deck_meta($existing_id, $body);

                if (class_exists('Unified_HS_Archetype_Detector')) {
                    Unified_HS_Archetype_Detector::assign_deck($existing_id);
                }
                if (class_exists('HS_Decks_Manager')) {
                    HS_Decks_Manager::sync_deck_filter_taxonomies($existing_id);
                }
                if (class_exists('Unified_HS_Deck_Events')) {
                    Unified_HS_Deck_Events::record_stats_snapshot($existing_id, 'rest_deduplicated');
                }
                $kolodahs_sync = self::maybe_run_kolodahs_sync($existing_id, $body);
                self::bump_decks_cache();

                return rest_ensure_response(array(
                    'success' => true,
                    'created' => false,
                    'duplicate' => true,
                    'post_id' => $existing_id,
                    'feed_status' => get_post_meta($existing_id, '_hide_from_feed', true) === '1' ? 'hidden' : 'published',
                    'updated' => $updated,
                    'kolodahs_sync' => $kolodahs_sync,
                ));
            }
        }

        $post_id = wp_insert_post(array(
            'post_type' => 'hs_deck',
            'post_status' => $status,
            'post_title' => $title,
            'post_author' => get_current_user_id(),
        ), true);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        $body['deck_code'] = $deck_code;

        $updated = self::apply_deck_meta($post_id, $body);

        if (class_exists('Unified_HS_Archetype_Detector')) {
            Unified_HS_Archetype_Detector::assign_deck($post_id);
        }
        if (class_exists('HS_Decks_Manager')) {
            HS_Decks_Manager::sync_deck_filter_taxonomies($post_id);
        }
        if (class_exists('Unified_HS_Deck_Events')) {
            Unified_HS_Deck_Events::record_stats_snapshot($post_id, 'rest_create');
        }
        $kolodahs_sync = self::maybe_run_kolodahs_sync($post_id, $body);
        self::bump_decks_cache();

        if (class_exists('Unified_HS_Ingest_Logs')) {
            Unified_HS_Ingest_Logs::add(array(
                'time' => current_time('mysql'),
                'status' => 'created',
                'deck_name' => $title,
                'deck_code' => $deck_code,
                'streamer' => isset($body['streamer']) ? $body['streamer'] : '',
                'format' => isset($body['format']) ? $body['format'] : 'telegram',
                'message' => !empty($body['hide_from_feed']) && $body['hide_from_feed'] === '0' ? 'created and published to feed' : 'created hidden from feed',
            ));
        }

        $response = rest_ensure_response(array(
            'success' => true,
            'post_id' => $post_id,
            'edit_url' => get_edit_post_link($post_id, ''),
            'feed_status' => get_post_meta($post_id, '_hide_from_feed', true) === '1' ? 'hidden' : 'published',
            'updated' => $updated,
            'kolodahs_sync' => $kolodahs_sync,
        ));
        $response->set_status(201);
        return $response;
    }

    private static function find_existing_deck_by_code($deck_code) {
        global $wpdb;

        $deck_code = trim((string) $deck_code);
        if ($deck_code === '') {
            return 0;
        }

        $sql = $wpdb->prepare(
            "SELECT p.ID
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
             WHERE p.post_type = 'hs_deck'
               AND p.post_status NOT IN ('trash', 'auto-draft')
               AND pm.meta_key = '_deck_code'
               AND TRIM(pm.meta_value) = %s
             ORDER BY (p.post_status = 'publish') DESC, p.ID ASC
             LIMIT 1",
            $deck_code
        );

        return absint($wpdb->get_var($sql));
    }

    private static function preserve_existing_source_on_cross_source_match($post_id, array &$body) {
        if (empty($body['source_url'])) {
            return;
        }

        $existing_url = trim((string) get_post_meta($post_id, '_deck_source_url', true));
        if ($existing_url === '') {
            return;
        }

        $existing_host = strtolower((string) wp_parse_url($existing_url, PHP_URL_HOST));
        $incoming_host = strtolower((string) wp_parse_url((string) $body['source_url'], PHP_URL_HOST));
        if ($existing_host !== '' && $incoming_host !== '' && $existing_host !== $incoming_host) {
            unset($body['source_url']);
        }
    }

    /**
     * POST /manacost/v1/deck-meta/{id}
     * Обновляет meta-поля существующей колоды hs_deck.
     */
    public static function handle_deck_meta(WP_REST_Request $request) {
        $post_id = absint($request['id']);

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'hs_deck') {
            return new WP_Error(
                'invalid_post',
                'Post not found or invalid type',
                array('status' => 404)
            );
        }

        $body = $request->get_json_params();
        if (!is_array($body)) {
            $body = array();
        }

        $updated = self::apply_deck_meta($post_id, $body);
        if (class_exists('Unified_HS_Archetype_Detector')) {
            Unified_HS_Archetype_Detector::assign_deck($post_id);
        }
        if (class_exists('HS_Decks_Manager')) {
            HS_Decks_Manager::sync_deck_filter_taxonomies($post_id);
        }
        if (class_exists('Unified_HS_Deck_Events')) {
            Unified_HS_Deck_Events::record_stats_snapshot($post_id, 'rest_update');
        }
        $kolodahs_sync = self::maybe_run_kolodahs_sync($post_id, $body);
        self::bump_decks_cache();

        return rest_ensure_response(array(
            'success' => true,
            'post_id' => $post_id,
            'updated' => $updated,
            'kolodahs_sync' => $kolodahs_sync,
        ));
    }

    private static function apply_deck_meta($post_id, array $body) {
        // Карта: ключ из тела запроса → meta_key + sanitizer.
        // Добавление новых полей: дописать строку в массив, никаких других правок.
        $field_map = self::deck_meta_field_map();

        $updated = array();
        foreach ($field_map as $body_key => $spec) {
            if (!array_key_exists($body_key, $body)) {
                continue;
            }
            list($meta_key, $sanitizer) = $spec;
            $clean = call_user_func($sanitizer, $body[$body_key]);
            update_post_meta($post_id, $meta_key, $clean);
            $updated[$meta_key] = $clean;
        }

        if (isset($updated['_deck_code']) && class_exists('Unified_HS_Deckstring_Helper')) {
            $assigned = Unified_HS_Deckstring_Helper::assign_terms_from_code($post_id, $updated['_deck_code']);
            if (!empty($assigned)) {
                $updated['assigned_terms'] = $assigned;
            }
        }

        return $updated;
    }

    private static function maybe_run_kolodahs_sync($post_id, array $body = array()) {
        $requested = false;
        foreach (array('kolodahs_sync', 'run_kolodahs_sync', 'generate_kolodahs_image') as $key) {
            if (array_key_exists($key, $body) && rest_sanitize_boolean($body[$key])) {
                $requested = true;
                break;
            }
        }

        if (!$requested) {
            return 'not_requested';
        }

        if (!class_exists('Kolodahs_Manacost_Sync')) {
            return 'plugin_missing';
        }

        $deck_code = trim((string) get_post_meta($post_id, '_deck_code', true));
        if ($deck_code === '') {
            return 'missing_deck_code';
        }

        try {
            if (method_exists('Kolodahs_Manacost_Sync', 'queue_sync')) {
                Kolodahs_Manacost_Sync::queue_sync($post_id);
            } else {
                wp_schedule_single_event(time() + 5, Kolodahs_Manacost_Sync::CRON_SYNC, array((int) $post_id));
            }
            $state = (string) get_post_meta($post_id, '_kolodahs_api_state', true);
            return $state !== '' ? $state : 'queued';
        } catch (Throwable $e) {
            error_log('[Unified HS REST] Kolodahs sync failed for post ' . (int) $post_id . ': ' . $e->getMessage());
            return 'error';
        }
    }

    private static function deck_meta_field_map() {
        return array(
            'deck_code'  => array('_deck_code',       'sanitize_textarea_field'),
            'dust_cost'  => array('_dust_cost',       'absint'),
            'custom_tags'=> array('_custom_tags',     array(__CLASS__, 'sanitize_custom_tags')),
            'streamer'   => array('_deck_streamer',   'sanitize_text_field'),
            'player'     => array('_deck_player',     'sanitize_text_field'),
            'source_url' => array('_deck_source_url', 'esc_url_raw'),
            'wins'       => array('_deck_wins',       'absint'),
            'losses'     => array('_deck_losses',     'absint'),
            'games'      => array('_deck_games',      'absint'),
            'winrate'    => array('_deck_winrate',    array(__CLASS__, 'sanitize_winrate')),
            'peak'       => array('_deck_peak',       'sanitize_text_field'),
            'latest'     => array('_deck_latest',     'sanitize_text_field'),
            'worst'      => array('_deck_worst',      'sanitize_text_field'),
            'hide_from_feed' => array('_hide_from_feed', array(__CLASS__, 'sanitize_bool_flag')),
            'exclude_from_random' => array('_exclude_from_random', array(__CLASS__, 'sanitize_bool_flag')),
            'archived' => array('_hs_deck_archived', array(__CLASS__, 'sanitize_bool_flag')),
            'show_all_class_modes' => array('_show_all_class_modes', array(__CLASS__, 'sanitize_bool_flag')),
        );
    }

    private static function first_body_value(array $body, array $keys, $default = '') {
        foreach ($keys as $key) {
            if (array_key_exists($key, $body) && $body[$key] !== '') {
                return $body[$key];
            }
        }
        return $default;
    }

    private static function bump_decks_cache() {
        if (class_exists('HS_Decks_Manager')) {
            $ver = HS_Decks_Manager::get_decks_cache_version();
            update_option('hs_decks_cache_version', $ver + 1, false);
        }
    }

    public static function sanitize_bool_flag($value) {
        return rest_sanitize_boolean($value) ? '1' : '0';
    }

    public static function sanitize_winrate($value) {
        $value = str_replace(',', '.', (string) $value);
        if (!is_numeric($value)) {
            return 0;
        }

        $value = (float) $value;
        if ($value < 0) {
            return 0;
        }
        if ($value > 100) {
            return 100;
        }

        return round($value, 1);
    }

    public static function sanitize_custom_tags($value) {
        if (class_exists('HS_Decks_Manager')) {
            return HS_Decks_Manager::normalize_custom_tags($value);
        }
        return sanitize_text_field($value);
    }
}
