<?php
/**
 * Plugin Name: Manacost: Arena Feed
 * Description: Cached arena deck image feed from kolodahs.ru with filters and 5-star ratings.
 * Version: 1.1.5
 * Author: Manacost
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Manacost_Arena_Feed {
    const VERSION = '1.1.5';
    const DB_VERSION = '1.1.0';
    const CRON_HOOK = 'manacost_arena_feed_sync';
    const NONCE_ACTION = 'manacost_arena_feed';
    const DEFAULT_API_URL = 'https://api.kolodahs.ru/v1/arena/images';
    const OPTION_API_URL = 'manacost_arena_feed_api_url';
    const OPTION_SYNC_LIMIT = 'manacost_arena_feed_sync_limit';
    const OPTION_LAST_SYNC = 'manacost_arena_feed_last_sync';
    const OPTION_DB_VERSION = 'manacost_arena_feed_db_version';
    const FILTER_CACHE_TTL = 600;

    private static $localized_assets = false;
    private static $voter_hash_cache = null;

    public static function init() {
        add_filter('cron_schedules', array(__CLASS__, 'cron_schedules'));
        add_action(self::CRON_HOOK, array(__CLASS__, 'sync_from_api'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'register_assets'));
        add_shortcode('manacost_arena_decks', array(__CLASS__, 'shortcode'));
        add_shortcode('hs_arena_decks', array(__CLASS__, 'shortcode'));
        add_action('wp_ajax_manacost_arena_filter', array(__CLASS__, 'ajax_filter'));
        add_action('wp_ajax_nopriv_manacost_arena_filter', array(__CLASS__, 'ajax_filter'));
        add_action('wp_ajax_manacost_arena_rate', array(__CLASS__, 'ajax_rate'));
        add_action('wp_ajax_nopriv_manacost_arena_rate', array(__CLASS__, 'ajax_rate'));
        add_action('admin_init', array(__CLASS__, 'admin_init'));
        add_action('admin_menu', array(__CLASS__, 'admin_menu'));
        add_action('admin_post_manacost_arena_feed_sync_now', array(__CLASS__, 'admin_sync_now'));
    }

    public static function activate() {
        self::install_tables();

        if (!get_option(self::OPTION_API_URL)) {
            add_option(self::OPTION_API_URL, self::DEFAULT_API_URL, '', false);
        }
        if (!get_option(self::OPTION_SYNC_LIMIT)) {
            add_option(self::OPTION_SYNC_LIMIT, 120, '', false);
        }

        self::schedule_cron();
    }

    public static function deactivate() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        while ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
            $timestamp = wp_next_scheduled(self::CRON_HOOK);
        }
    }

    public static function cron_schedules($schedules) {
        if (!isset($schedules['manacost_arena_15min'])) {
            $schedules['manacost_arena_15min'] = array(
                'interval' => 15 * MINUTE_IN_SECONDS,
                'display' => 'Every 15 minutes',
            );
        }

        return $schedules;
    }

    private static function schedule_cron() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 300, 'manacost_arena_15min', self::CRON_HOOK);
        }
    }

    public static function admin_init() {
        register_setting('manacost_arena_feed', self::OPTION_API_URL, array(
            'type' => 'string',
            'sanitize_callback' => array(__CLASS__, 'sanitize_api_url'),
            'default' => self::DEFAULT_API_URL,
        ));
        register_setting('manacost_arena_feed', self::OPTION_SYNC_LIMIT, array(
            'type' => 'integer',
            'sanitize_callback' => array(__CLASS__, 'sanitize_sync_limit'),
            'default' => 120,
        ));

        if (get_option(self::OPTION_DB_VERSION) !== self::DB_VERSION) {
            self::install_tables();
        }
        self::schedule_cron();
    }

    public static function admin_menu() {
        add_options_page(
            'Manacost Arena Feed',
            'Arena Feed',
            'manage_options',
            'manacost-arena-feed',
            array(__CLASS__, 'render_admin_page')
        );
    }

    public static function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $last_sync = get_option(self::OPTION_LAST_SYNC, array());
        $counts = self::get_table_counts();
        ?>
        <div class="wrap">
            <h1>Manacost Arena Feed</h1>
            <?php if (isset($_GET['synced'])): ?>
                <div class="notice notice-success is-dismissible"><p>Синхронизация выполнена.</p></div>
            <?php endif; ?>

            <p>Шорткод для страницы: <code>[manacost_arena_decks]</code></p>
            <p>Короткий вариант: <code>[hs_arena_decks per_page="12"]</code></p>
            <p>В кэше: <strong><?php echo esc_html(number_format_i18n($counts['images'])); ?></strong> картинок, <strong><?php echo esc_html(number_format_i18n($counts['votes'])); ?></strong> голосов.</p>

            <?php if (is_array($last_sync) && !empty($last_sync['time'])): ?>
                <p>Последняя синхронизация: <strong><?php echo esc_html($last_sync['time']); ?></strong>, импортировано/обновлено: <?php echo esc_html((int) ($last_sync['imported'] ?? 0)); ?>.</p>
                <?php if (empty($last_sync['ok']) && !empty($last_sync['error'])): ?>
                    <p><strong>Ошибка:</strong> <?php echo esc_html($last_sync['error']); ?></p>
                <?php endif; ?>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields('manacost_arena_feed'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr(self::OPTION_API_URL); ?>">API URL</label></th>
                        <td><input name="<?php echo esc_attr(self::OPTION_API_URL); ?>" id="<?php echo esc_attr(self::OPTION_API_URL); ?>" type="url" class="regular-text" value="<?php echo esc_attr(get_option(self::OPTION_API_URL, self::DEFAULT_API_URL)); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr(self::OPTION_SYNC_LIMIT); ?>">Картинок за синхронизацию</label></th>
                        <td><input name="<?php echo esc_attr(self::OPTION_SYNC_LIMIT); ?>" id="<?php echo esc_attr(self::OPTION_SYNC_LIMIT); ?>" type="number" min="20" max="1000" step="10" value="<?php echo esc_attr((int) get_option(self::OPTION_SYNC_LIMIT, 120)); ?>"></td>
                    </tr>
                </table>
                <?php submit_button('Сохранить'); ?>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('manacost_arena_feed_sync_now'); ?>
                <input type="hidden" name="action" value="manacost_arena_feed_sync_now">
                <?php submit_button('Синхронизировать сейчас', 'secondary'); ?>
            </form>
        </div>
        <?php
    }

    public static function admin_sync_now() {
        if (!current_user_can('manage_options')) {
            wp_die('Forbidden');
        }
        check_admin_referer('manacost_arena_feed_sync_now');
        self::sync_from_api(true);
        wp_safe_redirect(add_query_arg('synced', '1', admin_url('options-general.php?page=manacost-arena-feed')));
        exit;
    }

    public static function sanitize_api_url($url) {
        $url = esc_url_raw(trim((string) $url));
        return $url !== '' ? $url : self::DEFAULT_API_URL;
    }

    public static function sanitize_sync_limit($limit) {
        $limit = absint($limit);
        if ($limit < 20) {
            return 20;
        }
        if ($limit > 1000) {
            return 1000;
        }
        return $limit;
    }

    public static function install_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $images_table = self::images_table();
        $votes_table = self::votes_table();

        $sql_images = "CREATE TABLE $images_table (
            hash char(32) NOT NULL,
            api_id bigint(20) unsigned NOT NULL DEFAULT 0,
            title text NOT NULL,
            class_slug varchar(64) NOT NULL DEFAULT '',
            main_class varchar(128) NOT NULL DEFAULT '',
            main_class_slug varchar(64) NOT NULL DEFAULT '',
            hero_power_class varchar(128) NOT NULL DEFAULT '',
            hero_power_class_slug varchar(64) NOT NULL DEFAULT '',
            record_label varchar(32) NOT NULL DEFAULT '',
            player varchar(128) NOT NULL DEFAULT '',
            draft_id bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at int(10) unsigned NOT NULL DEFAULT 0,
            done_at int(10) unsigned NOT NULL DEFAULT 0,
            image_url varchar(500) NOT NULL DEFAULT '',
            webp_url varchar(500) NOT NULL DEFAULT '',
            thumb_webp_url varchar(500) NOT NULL DEFAULT '',
            file_url varchar(500) NOT NULL DEFAULT '',
            webp_file_url varchar(500) NOT NULL DEFAULT '',
            bytes bigint(20) unsigned NOT NULL DEFAULT 0,
            webp_bytes bigint(20) unsigned NOT NULL DEFAULT 0,
            thumb_webp_bytes bigint(20) unsigned NOT NULL DEFAULT 0,
            rating_count int(10) unsigned NOT NULL DEFAULT 0,
            rating_sum int(10) unsigned NOT NULL DEFAULT 0,
            rating_avg decimal(4,2) NOT NULL DEFAULT 0.00,
            payload longtext NULL,
            synced_at datetime NOT NULL,
            PRIMARY KEY  (hash),
            KEY api_id (api_id),
            KEY created_at (created_at),
            KEY class_created (class_slug, created_at),
            KEY hero_created (hero_power_class_slug, created_at),
            KEY record_created (record_label, created_at),
            KEY rating_avg (rating_avg),
            KEY draft_id (draft_id)
        ) $charset_collate;";

        $sql_votes = "CREATE TABLE $votes_table (
            image_hash char(32) NOT NULL,
            voter_hash char(64) NOT NULL,
            rating tinyint(3) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            ip_hash char(64) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (image_hash, voter_hash),
            KEY voter_hash (voter_hash),
            KEY image_rating (image_hash, rating),
            KEY updated_at (updated_at),
            KEY ip_hash (ip_hash)
        ) $charset_collate;";

        dbDelta($sql_images);
        dbDelta($sql_votes);
        update_option(self::OPTION_DB_VERSION, self::DB_VERSION, false);
    }

    public static function register_assets() {
        $base_url = plugin_dir_url(__FILE__);
        wp_register_style(
            'manacost-arena-feed',
            $base_url . 'assets/arena-feed.css',
            array(),
            self::VERSION
        );
        wp_register_script(
            'manacost-arena-feed',
            $base_url . 'assets/arena-feed.js',
            array('jquery'),
            self::VERSION,
            true
        );

        if (self::current_page_has_shortcode()) {
            self::enqueue_assets();
        }
    }

    private static function enqueue_assets() {
        wp_enqueue_style('manacost-arena-feed');
        wp_enqueue_script('manacost-arena-feed');

        if (!self::$localized_assets) {
            wp_localize_script('manacost-arena-feed', 'manacostArenaFeed', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce(self::NONCE_ACTION),
                'filterAction' => 'manacost_arena_filter',
                'rateAction' => 'manacost_arena_rate',
                'texts' => array(
                    'loading' => 'Загрузка...',
                    'error' => 'Не удалось выполнить действие. Попробуйте позже.',
                    'rated' => 'Оценка сохранена',
                ),
            ));
            self::$localized_assets = true;
        }
    }

    private static function current_page_has_shortcode() {
        if (!is_singular()) {
            return false;
        }

        $post = get_post();
        if (!$post || empty($post->post_content)) {
            return false;
        }

        return has_shortcode($post->post_content, 'manacost_arena_decks')
            || has_shortcode($post->post_content, 'hs_arena_decks');
    }

    public static function shortcode($atts) {
        self::enqueue_assets();

        $atts = shortcode_atts(array(
            'per_page' => 12,
            'deck' => '',
            'class' => '',
            'hero_class' => '',
            'period' => '',
            'sort' => 'date',
            'filters' => '1',
        ), $atts, 'manacost_arena_decks');

        $per_page = !empty($atts['deck']) ? absint($atts['deck']) : absint($atts['per_page']);
        $per_page = max(1, min(48, $per_page));

        self::sync_once_if_empty();

        $filters = self::read_filters($atts, $_GET);
        $feed = self::render_feed($filters, $per_page);
        $show_filters = !in_array((string) $atts['filters'], array('0', 'false', 'no'), true);

        ob_start();
        ?>
        <div class="maf-arena-feed" data-per-page="<?php echo esc_attr($per_page); ?>">
            <?php echo self::render_filter_form($filters, $per_page, $show_filters); ?>
            <div class="maf-feed-status" aria-live="polite"></div>
            <div class="maf-grid" data-maf-grid>
                <?php echo $feed['html']; ?>
            </div>
            <div class="maf-pagination-wrap" data-maf-pagination>
                <?php echo $feed['pagination']; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function ajax_filter() {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 12;
        $per_page = max(1, min(48, $per_page));
        $filters = self::read_filters(array(), $_POST);
        $feed = self::render_feed($filters, $per_page);

        wp_send_json_success($feed);
    }

    public static function ajax_rate() {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $hash = isset($_POST['hash']) ? sanitize_text_field(wp_unslash($_POST['hash'])) : '';
        $rating = isset($_POST['rating']) ? absint($_POST['rating']) : 0;

        if (!preg_match('/^[a-f0-9]{32}$/', $hash) || $rating < 1 || $rating > 5) {
            wp_send_json_error(array('message' => 'Некорректная оценка.'), 400);
        }

        global $wpdb;
        $images_table = self::images_table();
        $exists = $wpdb->get_var($wpdb->prepare("SELECT hash FROM $images_table WHERE hash = %s", $hash));
        if (!$exists) {
            wp_send_json_error(array('message' => 'Картинка не найдена.'), 404);
        }

        $voter_hash = self::get_voter_hash();
        $rate_key = 'maf_rate_' . substr($voter_hash, 0, 24);
        if (get_transient($rate_key)) {
            wp_send_json_error(array('message' => 'Слишком часто. Подождите несколько секунд.'), 429);
        }
        set_transient($rate_key, 1, 8);

        $votes_table = self::votes_table();
        $now = current_time('mysql');
        $user_id = get_current_user_id();
        $ip_hash = self::get_ip_hash();

        $wpdb->query($wpdb->prepare(
            "INSERT INTO $votes_table
                (image_hash, voter_hash, rating, user_id, ip_hash, created_at, updated_at)
             VALUES (%s, %s, %d, %d, %s, %s, %s)
             ON DUPLICATE KEY UPDATE
                rating = VALUES(rating),
                user_id = VALUES(user_id),
                ip_hash = VALUES(ip_hash),
                updated_at = VALUES(updated_at)",
            $hash,
            $voter_hash,
            $rating,
            $user_id,
            $ip_hash,
            $now,
            $now
        ));

        $aggregate = self::recalculate_rating($hash);
        $aggregate['user_rating'] = $rating;
        $aggregate['rating_text'] = self::format_rating_text((float) $aggregate['rating_avg'], (int) $aggregate['rating_count']);

        wp_send_json_success($aggregate);
    }

    public static function sync_from_api($force = false) {
        global $wpdb;

        if (!$force && get_transient('manacost_arena_feed_sync_lock')) {
            return false;
        }
        set_transient('manacost_arena_feed_sync_lock', 1, 5 * MINUTE_IN_SECONDS);

        $api_url = get_option(self::OPTION_API_URL, self::DEFAULT_API_URL);
        $target_limit = self::sanitize_sync_limit(get_option(self::OPTION_SYNC_LIMIT, 120));
        $remaining = $target_limit;
        $offset = 0;
        $imported = 0;
        $error = '';

        while ($remaining > 0) {
            $request_limit = min(100, $remaining);
            $url = add_query_arg(array(
                'limit' => $request_limit,
                'offset' => $offset,
            ), $api_url);

            $response = wp_remote_get($url, array(
                'timeout' => 12,
                'redirection' => 2,
                'headers' => array(
                    'Accept' => 'application/json',
                    'User-Agent' => 'ManacostArenaFeed/' . self::VERSION . '; ' . home_url('/'),
                ),
            ));

            if (is_wp_error($response)) {
                $error = $response->get_error_message();
                break;
            }

            $status = (int) wp_remote_retrieve_response_code($response);
            if ($status < 200 || $status >= 300) {
                $error = 'HTTP ' . $status;
                break;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!is_array($body) || empty($body['success']) || empty($body['result']['images']) || !is_array($body['result']['images'])) {
                $error = 'Invalid API response';
                break;
            }

            foreach ($body['result']['images'] as $image) {
                if (is_array($image) && self::upsert_image($image)) {
                    $imported++;
                }
            }

            $count = count($body['result']['images']);
            $remaining -= $count;
            if ($count < $request_limit || empty($body['result']['next_offset'])) {
                break;
            }
            $offset = absint($body['result']['next_offset']);
        }

        delete_transient('manacost_arena_feed_sync_lock');

        update_option(self::OPTION_LAST_SYNC, array(
            'time' => current_time('mysql'),
            'imported' => $imported,
            'ok' => $error === '',
            'error' => $error,
        ), false);

        if ($error !== '') {
            error_log('Manacost Arena Feed sync failed: ' . $error);
            return false;
        }

        // Keep table stats warm after bulk updates.
        $wpdb->query('ANALYZE TABLE ' . self::images_table());
        self::clear_filter_caches();
        return true;
    }

    private static function sync_once_if_empty() {
        global $wpdb;
        $images_table = self::images_table();
        $has_table = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $images_table));
        if ($has_table !== $images_table) {
            self::install_tables();
        }

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $images_table");
        if ($count === 0) {
            self::sync_from_api(false);
        }
    }

    private static function upsert_image(array $image) {
        global $wpdb;

        $normalized = self::normalize_image($image);
        if ($normalized['hash'] === '' || $normalized['image_url'] === '') {
            return false;
        }

        $table = self::images_table();
        $payload = wp_json_encode($image, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table
                (hash, api_id, title, class_slug, main_class, main_class_slug, hero_power_class, hero_power_class_slug,
                 record_label, player, draft_id, created_at, done_at, image_url, webp_url, thumb_webp_url, file_url, webp_file_url,
                 bytes, webp_bytes, thumb_webp_bytes, payload, synced_at)
             VALUES
                (%s, %d, %s, %s, %s, %s, %s, %s, %s, %s, %d, %d, %d, %s, %s, %s, %s, %s, %d, %d, %d, %s, %s)
             ON DUPLICATE KEY UPDATE
                api_id = VALUES(api_id),
                title = VALUES(title),
                class_slug = VALUES(class_slug),
                main_class = VALUES(main_class),
                main_class_slug = VALUES(main_class_slug),
                hero_power_class = VALUES(hero_power_class),
                hero_power_class_slug = VALUES(hero_power_class_slug),
                record_label = VALUES(record_label),
                player = VALUES(player),
                draft_id = VALUES(draft_id),
                created_at = VALUES(created_at),
                done_at = VALUES(done_at),
                image_url = VALUES(image_url),
                webp_url = VALUES(webp_url),
                thumb_webp_url = VALUES(thumb_webp_url),
                file_url = VALUES(file_url),
                webp_file_url = VALUES(webp_file_url),
                bytes = VALUES(bytes),
                webp_bytes = VALUES(webp_bytes),
                thumb_webp_bytes = VALUES(thumb_webp_bytes),
                payload = VALUES(payload),
                synced_at = VALUES(synced_at)",
            $normalized['hash'],
            $normalized['api_id'],
            $normalized['title'],
            $normalized['class_slug'],
            $normalized['main_class'],
            $normalized['main_class_slug'],
            $normalized['hero_power_class'],
            $normalized['hero_power_class_slug'],
            $normalized['record_label'],
            $normalized['player'],
            $normalized['draft_id'],
            $normalized['created_at'],
            $normalized['done_at'],
            $normalized['image_url'],
            $normalized['webp_url'],
            $normalized['thumb_webp_url'],
            $normalized['file_url'],
            $normalized['webp_file_url'],
            $normalized['bytes'],
            $normalized['webp_bytes'],
            $normalized['thumb_webp_bytes'],
            $payload,
            current_time('mysql')
        ));

        return $wpdb->last_error === '';
    }

    private static function normalize_image(array $image) {
        $arena = isset($image['arena']) && is_array($image['arena']) ? $image['arena'] : array();
        $title = isset($image['title']) ? trim((string) $image['title']) : '';
        $title_classes = self::parse_title_classes($title);

        $api_class_slug = self::normalize_class_slug($image['class'] ?? '');
        $main_class = self::first_non_empty($arena['main_class'] ?? '', $title_classes['main_class']);
        $main_class_slug = self::normalize_class_slug(self::first_non_empty($arena['main_class_slug'] ?? '', $main_class, $api_class_slug));

        if (($main_class === '' || self::is_generic_class_title($main_class)) && $main_class_slug !== '') {
            $main_class = self::class_label($main_class_slug);
        }

        $hero_class = self::first_non_empty($arena['hero_power_class'] ?? '', $title_classes['hero_power_class']);
        $hero_class_slug = self::normalize_class_slug(self::first_non_empty($arena['hero_power_class_slug'] ?? '', $hero_class));

        if ($hero_class === '' && $hero_class_slug !== '') {
            $hero_class = self::class_label($hero_class_slug);
        }

        $class_slug = $api_class_slug !== '' ? $api_class_slug : $main_class_slug;
        if ($main_class_slug === '' && $class_slug !== '') {
            $main_class_slug = $class_slug;
        }
        if ($main_class === '' && $main_class_slug !== '') {
            $main_class = self::class_label($main_class_slug);
        }

        return array(
            'hash' => preg_match('/^[a-f0-9]{32}$/', (string) ($image['hash'] ?? '')) ? (string) $image['hash'] : '',
            'api_id' => absint($image['id'] ?? 0),
            'title' => $title !== '' ? $title : trim($main_class . ' / ' . $hero_class, ' /'),
            'class_slug' => $class_slug,
            'main_class' => $main_class,
            'main_class_slug' => $main_class_slug,
            'hero_power_class' => $hero_class,
            'hero_power_class_slug' => $hero_class_slug,
            'record_label' => sanitize_text_field((string) ($arena['record'] ?? '')),
            'player' => sanitize_text_field((string) ($arena['player'] ?? '')),
            'draft_id' => isset($arena['draft_id']) && $arena['draft_id'] !== null && $arena['draft_id'] !== '' ? absint($arena['draft_id']) : 0,
            'created_at' => absint($image['created_at'] ?? 0),
            'done_at' => absint($image['done_at'] ?? 0),
            'image_url' => esc_url_raw((string) ($image['image_url'] ?? '')),
            'webp_url' => esc_url_raw((string) ($image['webp_url'] ?? '')),
            'thumb_webp_url' => esc_url_raw((string) ($image['thumb_webp_url'] ?? '')),
            'file_url' => esc_url_raw((string) ($image['file_url'] ?? '')),
            'webp_file_url' => esc_url_raw((string) ($image['webp_file_url'] ?? '')),
            'bytes' => absint($image['bytes'] ?? 0),
            'webp_bytes' => absint($image['webp_bytes'] ?? 0),
            'thumb_webp_bytes' => absint($image['thumb_webp_bytes'] ?? 0),
        );
    }

    private static function read_filters(array $atts, array $source = array()) {
        $source = wp_unslash($source);

        $class = self::normalize_class_slug(self::source_value($source, 'arena_class', $atts['class'] ?? ''));
        $hero_class = self::normalize_class_slug(self::source_value($source, 'arena_hero_class', $atts['hero_class'] ?? ''));
        $record = sanitize_text_field((string) self::source_value($source, 'arena_record', ''));
        $search = sanitize_text_field((string) self::source_value($source, 'arena_search', ''));
        $period = absint(self::source_value($source, 'arena_period', $atts['period'] ?? 0));
        $page = absint(self::source_value($source, 'arena_page', 1));
        $sort = sanitize_key((string) self::source_value($source, 'arena_sort', $atts['sort'] ?? 'date'));

        if (!in_array($sort, array('date', 'rating', 'votes'), true)) {
            $sort = 'date';
        }

        $allowed_periods = array(0, 1, 3, 7, 14, 30, 90, 365);
        if (!in_array($period, $allowed_periods, true)) {
            $period = 0;
        }

        return array(
            'class' => $class,
            'hero_class' => $hero_class,
            'record' => $record,
            'search' => $search,
            'period' => $period,
            'sort' => $sort,
            'page' => max(1, $page),
        );
    }

    private static function source_value(array $source, $key, $default = '') {
        return isset($source[$key]) ? $source[$key] : $default;
    }

    private static function render_feed(array $filters, $per_page) {
        $query = self::query_images($filters, $per_page);
        $html = self::render_grid($query['rows']);

        return array(
            'html' => $html,
            'pagination' => self::render_pagination($filters['page'], $query['pages'], $query['total'], $per_page),
            'total' => $query['total'],
            'pages' => $query['pages'],
            'page' => $filters['page'],
        );
    }

    private static function query_images(array $filters, $per_page) {
        global $wpdb;

        $table = self::images_table();
        $where = array('1=1');
        $args = array();

        if ($filters['class'] !== '') {
            $where[] = 'class_slug = %s';
            $args[] = $filters['class'];
        }
        if ($filters['hero_class'] !== '') {
            $where[] = 'hero_power_class_slug = %s';
            $args[] = $filters['hero_class'];
        }
        if ($filters['record'] !== '') {
            $where[] = 'record_label = %s';
            $args[] = $filters['record'];
        }
        if ($filters['period'] > 0) {
            $where[] = 'created_at >= %d';
            $args[] = time() - ($filters['period'] * DAY_IN_SECONDS);
        }
        if ($filters['search'] !== '') {
            $like = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where[] = '(title LIKE %s OR player LIKE %s OR main_class LIKE %s OR hero_power_class LIKE %s OR record_label LIKE %s)';
            array_push($args, $like, $like, $like, $like, $like);
        }

        $where_sql = implode(' AND ', $where);
        $count_sql = self::prepare_sql("SELECT COUNT(*) FROM $table WHERE $where_sql", $args);
        $total = (int) $wpdb->get_var($count_sql);
        $pages = max(1, (int) ceil($total / $per_page));
        $page = min(max(1, absint($filters['page'])), $pages);
        $offset = ($page - 1) * $per_page;

        switch ($filters['sort']) {
            case 'rating':
                $order_sql = 'rating_avg DESC, rating_count DESC, created_at DESC, hash DESC';
                break;
            case 'votes':
                $order_sql = 'rating_count DESC, rating_avg DESC, created_at DESC, hash DESC';
                break;
            case 'date':
            default:
                $order_sql = 'created_at DESC, api_id DESC, hash DESC';
                break;
        }

        $query_args = $args;
        $query_args[] = $per_page;
        $query_args[] = $offset;
        $rows_sql = self::prepare_sql(
            "SELECT * FROM $table WHERE $where_sql ORDER BY $order_sql LIMIT %d OFFSET %d",
            $query_args
        );
        $rows = $wpdb->get_results($rows_sql);

        return array(
            'rows' => is_array($rows) ? $rows : array(),
            'total' => $total,
            'pages' => $pages,
        );
    }

    private static function prepare_sql($sql, array $args) {
        global $wpdb;
        if (empty($args)) {
            return $sql;
        }
        return $wpdb->prepare($sql, $args);
    }

    private static function render_filter_form(array $filters, $per_page, $show_filters) {
        if (!$show_filters) {
            return '<input type="hidden" name="per_page" value="' . esc_attr($per_page) . '">';
        }

        $classes = self::get_class_options('class_slug');
        $hero_classes = self::get_class_options('hero_power_class_slug');
        $records = self::get_record_options();

        ob_start();
        ?>
        <form class="maf-filters" data-maf-filters method="get">
            <input type="hidden" name="arena_page" value="<?php echo esc_attr($filters['page']); ?>" data-maf-page>
            <input type="hidden" name="per_page" value="<?php echo esc_attr($per_page); ?>">

            <label class="maf-field maf-field-search">
                <span>Поиск</span>
                <input type="search" name="arena_search" value="<?php echo esc_attr($filters['search']); ?>" placeholder="Игрок, класс или запись">
            </label>

            <label class="maf-field maf-field-class">
                <span>Класс</span>
                <select name="arena_class">
                    <option value="">Все классы</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo esc_attr($class['slug']); ?>"<?php selected($filters['class'], $class['slug']); ?>><?php echo esc_html($class['label']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="maf-field maf-field-hero">
                <span>Сила героя</span>
                <select name="arena_hero_class">
                    <option value="">Любая сила</option>
                    <?php foreach ($hero_classes as $class): ?>
                        <option value="<?php echo esc_attr($class['slug']); ?>"<?php selected($filters['hero_class'], $class['slug']); ?>><?php echo esc_html($class['label']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="maf-field maf-field-period">
                <span>Период</span>
                <select name="arena_period">
                    <option value="0"<?php selected($filters['period'], 0); ?>>За всё время</option>
                    <option value="1"<?php selected($filters['period'], 1); ?>>24 часа</option>
                    <option value="3"<?php selected($filters['period'], 3); ?>>3 дня</option>
                    <option value="7"<?php selected($filters['period'], 7); ?>>7 дней</option>
                    <option value="14"<?php selected($filters['period'], 14); ?>>2 недели</option>
                    <option value="30"<?php selected($filters['period'], 30); ?>>Месяц</option>
                    <option value="90"<?php selected($filters['period'], 90); ?>>90 дней</option>
                    <option value="365"<?php selected($filters['period'], 365); ?>>Год</option>
                </select>
            </label>

            <?php if (!empty($records)): ?>
                <label class="maf-field maf-field-record">
                    <span>Запись</span>
                    <select name="arena_record">
                        <option value="">Любая запись</option>
                        <?php foreach ($records as $record): ?>
                            <option value="<?php echo esc_attr($record); ?>"<?php selected($filters['record'], $record); ?>><?php echo esc_html($record); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>

            <label class="maf-field maf-field-sort">
                <span>Сортировка</span>
                <select name="arena_sort">
                    <option value="date"<?php selected($filters['sort'], 'date'); ?>>Сначала новые</option>
                    <option value="rating"<?php selected($filters['sort'], 'rating'); ?>>Лучшие оценки</option>
                    <option value="votes"<?php selected($filters['sort'], 'votes'); ?>>Больше голосов</option>
                </select>
            </label>

            <div class="maf-actions">
                <button type="submit" class="maf-apply">Показать</button>
                <button type="button" class="maf-reset" data-maf-reset>Сбросить</button>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    private static function render_grid(array $rows) {
        if (empty($rows)) {
            return '<p class="maf-empty">Аренные колоды не найдены.</p>';
        }

        $hashes = array();
        foreach ($rows as $row) {
            $hashes[] = $row->hash;
        }
        $user_ratings = self::get_user_ratings_for_hashes($hashes);

        ob_start();
        $index = 0;
        foreach ($rows as $row) {
            $user_rating = isset($user_ratings[$row->hash]) ? (int) $user_ratings[$row->hash] : 0;
            echo self::render_card($row, $user_rating, $index);
            $index++;
        }
        return ob_get_clean();
    }

    private static function render_card($row, $user_rating = 0, $index = 0) {
        $hash = (string) $row->hash;
        $avg = (float) $row->rating_avg;
        $count = (int) $row->rating_count;
        $filled = $user_rating > 0 ? $user_rating : (int) round($avg);
        $main_label = $row->main_class !== '' ? $row->main_class : self::class_label($row->class_slug);
        $hero_label = $row->hero_power_class !== '' ? $row->hero_power_class : self::class_label($row->hero_power_class_slug);
        $date = $row->created_at > 0 ? wp_date('d.m.Y', (int) $row->created_at) : '';
        $fallback_image = $row->image_url ?: $row->file_url;
        $webp_image = $row->webp_url ?: $row->webp_file_url;
        $thumb_image = !empty($row->thumb_webp_url) ? $row->thumb_webp_url : $webp_image;
        $preview_image = $thumb_image ?: $fallback_image;
        $lightbox_image = $webp_image ?: $fallback_image;
        $is_priority_image = $index < 3;

        ob_start();
        ?>
        <article class="maf-card" data-maf-card data-hash="<?php echo esc_attr($hash); ?>" data-rating="<?php echo esc_attr($avg); ?>" data-rating-count="<?php echo esc_attr($count); ?>" data-user-rating="<?php echo esc_attr($user_rating); ?>">
            <a class="maf-image-link" href="<?php echo esc_url($fallback_image); ?>" target="_blank" rel="noopener" data-maf-lightbox data-maf-full="<?php echo esc_url($lightbox_image); ?>" data-title="<?php echo esc_attr($row->title); ?>">
                <picture>
                    <?php if ($thumb_image): ?>
                        <source srcset="<?php echo esc_url($thumb_image); ?>" type="image/webp">
                    <?php endif; ?>
                    <img src="<?php echo esc_url($preview_image); ?>" alt="<?php echo esc_attr($row->title); ?>" loading="<?php echo $is_priority_image ? 'eager' : 'lazy'; ?>"<?php echo $is_priority_image ? ' fetchpriority="high"' : ''; ?> decoding="async" width="720" height="450">
                </picture>
            </a>
            <div class="maf-card-body">
                <div class="maf-card-head">
                    <h3><?php echo esc_html($row->title); ?></h3>
                    <?php if ($date): ?>
                        <time datetime="<?php echo esc_attr(gmdate('c', (int) $row->created_at)); ?>"><?php echo esc_html($date); ?></time>
                    <?php endif; ?>
                </div>

                <div class="maf-meta">
                    <?php if ($main_label): ?><span><?php echo esc_html($main_label); ?></span><?php endif; ?>
                    <?php if ($hero_label): ?><span><?php echo esc_html('Сила: ' . $hero_label); ?></span><?php endif; ?>
                    <?php if ($row->record_label): ?><span><?php echo esc_html($row->record_label); ?></span><?php endif; ?>
                    <?php if ($row->player): ?><span><?php echo esc_html($row->player); ?></span><?php endif; ?>
                </div>

                <div class="maf-rating" data-maf-rating>
                    <div class="maf-stars" role="radiogroup" aria-label="Оценка колоды">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" class="maf-star<?php echo $i <= $filled ? ' is-filled' : ''; ?><?php echo $user_rating === $i ? ' is-user' : ''; ?>" data-rating="<?php echo esc_attr($i); ?>" role="radio" aria-checked="<?php echo $user_rating === $i ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr(sprintf('Оценить на %d из 5', $i)); ?>">★</button>
                        <?php endfor; ?>
                    </div>
                    <span class="maf-rating-text" data-maf-rating-text><?php echo esc_html(self::format_rating_text($avg, $count)); ?></span>
                </div>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }

    private static function render_pagination($current_page, $total_pages, $total_items, $per_page) {
        $current_page = max(1, absint($current_page));
        $total_pages = max(1, absint($total_pages));
        $total_items = max(0, absint($total_items));

        if ($total_items <= $per_page) {
            return '<div class="maf-page-info">Показано ' . esc_html(number_format_i18n($total_items)) . ' колод</div>';
        }

        ob_start();
        ?>
        <nav class="maf-pagination" aria-label="Пагинация аренных колод">
            <button type="button" class="maf-page-btn" data-page="<?php echo esc_attr($current_page - 1); ?>"<?php disabled($current_page <= 1); ?>>Назад</button>
            <?php
            $last_rendered = 0;
            for ($page = 1; $page <= $total_pages; $page++) {
                $is_edge = $page === 1 || $page === $total_pages;
                $is_near = abs($page - $current_page) <= 2;
                if (!$is_edge && !$is_near) {
                    if ($last_rendered !== -1) {
                        echo '<span class="maf-ellipsis" aria-hidden="true">…</span>';
                        $last_rendered = -1;
                    }
                    continue;
                }
                ?>
                <button type="button" class="maf-page-btn<?php echo $page === $current_page ? ' is-active' : ''; ?>" data-page="<?php echo esc_attr($page); ?>"<?php echo $page === $current_page ? ' aria-current="page"' : ''; ?>><?php echo esc_html($page); ?></button>
                <?php
                $last_rendered = $page;
            }
            ?>
            <button type="button" class="maf-page-btn" data-page="<?php echo esc_attr($current_page + 1); ?>"<?php disabled($current_page >= $total_pages); ?>>Вперёд</button>
        </nav>
        <div class="maf-page-info">
            <?php
            $start = (($current_page - 1) * $per_page) + 1;
            $end = min($total_items, $current_page * $per_page);
            echo esc_html(sprintf('Страница %d из %d, показано %d-%d из %d колод', $current_page, $total_pages, $start, $end, $total_items));
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function get_user_ratings_for_hashes(array $hashes) {
        global $wpdb;

        $hashes = array_values(array_filter(array_unique($hashes), function($hash) {
            return preg_match('/^[a-f0-9]{32}$/', (string) $hash);
        }));

        if (empty($hashes)) {
            return array();
        }

        $votes_table = self::votes_table();
        $voter_hash = self::get_voter_hash();
        $placeholders = implode(',', array_fill(0, count($hashes), '%s'));
        $args = array_merge(array($voter_hash), $hashes);
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT image_hash, rating FROM $votes_table WHERE voter_hash = %s AND image_hash IN ($placeholders)",
            $args
        ));

        $ratings = array();
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $ratings[$row->image_hash] = (int) $row->rating;
            }
        }

        return $ratings;
    }

    private static function recalculate_rating($hash) {
        global $wpdb;

        $votes_table = self::votes_table();
        $images_table = self::images_table();
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) AS rating_count, COALESCE(SUM(rating), 0) AS rating_sum, COALESCE(AVG(rating), 0) AS rating_avg
             FROM $votes_table
             WHERE image_hash = %s",
            $hash
        ), ARRAY_A);

        $count = isset($row['rating_count']) ? (int) $row['rating_count'] : 0;
        $sum = isset($row['rating_sum']) ? (int) $row['rating_sum'] : 0;
        $avg = $count > 0 ? round((float) $row['rating_avg'], 2) : 0;

        $wpdb->query($wpdb->prepare(
            "UPDATE $images_table SET rating_count = %d, rating_sum = %d, rating_avg = %f WHERE hash = %s",
            $count,
            $sum,
            $avg,
            $hash
        ));

        return array(
            'rating_count' => $count,
            'rating_sum' => $sum,
            'rating_avg' => $avg,
        );
    }

    private static function get_class_options($column) {
        global $wpdb;

        $allowed_columns = array('class_slug', 'hero_power_class_slug');
        if (!in_array($column, $allowed_columns, true)) {
            return array();
        }

        $cache_key = 'maf_class_options_' . $column;
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $table = self::images_table();
        $rows = $wpdb->get_results("SELECT $column AS slug, COUNT(*) AS total FROM $table WHERE $column <> '' GROUP BY $column ORDER BY total DESC");
        $options = array();

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $slug = self::normalize_class_slug($row->slug);
                if ($slug === '') {
                    continue;
                }
                $label = self::class_label($slug);
                $options[$slug] = array(
                    'slug' => $slug,
                    'label' => $label . ' (' . number_format_i18n((int) $row->total) . ')',
                    'sort' => $label,
                );
            }
        }

        uasort($options, function($a, $b) {
            return strnatcasecmp($a['sort'], $b['sort']);
        });

        $options = array_values($options);
        set_transient($cache_key, $options, self::FILTER_CACHE_TTL);
        return $options;
    }

    private static function get_record_options() {
        global $wpdb;
        $cached = get_transient('maf_record_options');
        if (is_array($cached)) {
            return $cached;
        }

        $table = self::images_table();
        $rows = $wpdb->get_col("SELECT DISTINCT record_label FROM $table WHERE record_label <> '' ORDER BY record_label ASC LIMIT 100");
        $records = is_array($rows) ? $rows : array();
        set_transient('maf_record_options', $records, self::FILTER_CACHE_TTL);
        return $records;
    }

    private static function clear_filter_caches() {
        delete_transient('maf_class_options_class_slug');
        delete_transient('maf_class_options_hero_power_class_slug');
        delete_transient('maf_record_options');
    }

    private static function get_table_counts() {
        global $wpdb;
        $images_table = self::images_table();
        $votes_table = self::votes_table();

        return array(
            'images' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $images_table"),
            'votes' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $votes_table"),
        );
    }

    private static function format_rating_text($avg, $count) {
        $count = (int) $count;
        if ($count <= 0) {
            return 'Нет оценок';
        }

        return number_format_i18n((float) $avg, 1) . ' / 5 · ' . number_format_i18n($count) . ' ' . self::votes_word($count);
    }

    private static function votes_word($count) {
        $count = absint($count);
        $mod10 = $count % 10;
        $mod100 = $count % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return 'голос';
        }
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
            return 'голоса';
        }
        return 'голосов';
    }

    private static function get_voter_hash() {
        if (self::$voter_hash_cache !== null) {
            return self::$voter_hash_cache;
        }

        if (is_user_logged_in()) {
            self::$voter_hash_cache = hash_hmac('sha256', 'user|' . get_current_user_id(), wp_salt('auth'));
            return self::$voter_hash_cache;
        }

        $fingerprint = implode('|', array(
            'anon',
            self::get_ip_prefix(),
            substr(self::server_value('HTTP_USER_AGENT'), 0, 300),
            substr(self::server_value('HTTP_ACCEPT_LANGUAGE'), 0, 120),
        ));

        self::$voter_hash_cache = hash_hmac('sha256', $fingerprint, wp_salt('auth'));
        return self::$voter_hash_cache;
    }

    private static function get_ip_hash() {
        return hash_hmac('sha256', self::get_ip_prefix(), wp_salt('nonce'));
    }

    private static function get_ip_prefix() {
        $ip = self::get_client_ip();
        if ($ip === '') {
            return 'unknown';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $expanded = inet_pton($ip);
            if ($expanded !== false) {
                $hex = str_split(bin2hex($expanded), 4);
                return implode(':', array_slice($hex, 0, 4)) . '::';
            }
        }

        return 'unknown';
    }

    private static function get_client_ip() {
        $candidates = array(
            self::server_value('HTTP_CF_CONNECTING_IP'),
            self::server_value('HTTP_X_REAL_IP'),
            self::first_forwarded_ip(self::server_value('HTTP_X_FORWARDED_FOR')),
            self::server_value('REMOTE_ADDR'),
        );

        foreach ($candidates as $ip) {
            $ip = trim((string) $ip);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '';
    }

    private static function first_forwarded_ip($value) {
        if ($value === '') {
            return '';
        }
        $parts = explode(',', $value);
        return trim((string) $parts[0]);
    }

    private static function server_value($key) {
        return isset($_SERVER[$key]) ? (string) wp_unslash($_SERVER[$key]) : '';
    }

    private static function parse_title_classes($title) {
        $parts = array_map('trim', explode('/', (string) $title));
        return array(
            'main_class' => isset($parts[0]) ? $parts[0] : '',
            'hero_power_class' => isset($parts[1]) ? $parts[1] : '',
        );
    }

    private static function first_non_empty() {
        foreach (func_get_args() as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }
        return '';
    }

    private static function is_generic_class_title($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return false;
        }

        $lower = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        return preg_match('/^(колода|deck)\\s+/u', $lower) === 1;
    }

    private static function normalize_class_slug($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $lower = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        $lower = str_replace('ё', 'е', $lower);
        $compact = preg_replace('/[^a-zа-я0-9]+/u', '', $lower);
        if ($compact === null) {
            $compact = preg_replace('/[^a-z0-9]+/', '', strtolower($value));
        }

        $aliases = array(
            'deathknight' => 'deathknight',
            'dk' => 'deathknight',
            'рыцарьсмерти' => 'deathknight',
            'demonhunter' => 'demonhunter',
            'dh' => 'demonhunter',
            'охотникнадемонов' => 'demonhunter',
            'druid' => 'druid',
            'друид' => 'druid',
            'hunter' => 'hunter',
            'охотник' => 'hunter',
            'mage' => 'mage',
            'маг' => 'mage',
            'paladin' => 'paladin',
            'паладин' => 'paladin',
            'priest' => 'priest',
            'жрец' => 'priest',
            'rogue' => 'rogue',
            'разбойник' => 'rogue',
            'shaman' => 'shaman',
            'шаман' => 'shaman',
            'warlock' => 'warlock',
            'чернокнижник' => 'warlock',
            'warrior' => 'warrior',
            'воин' => 'warrior',
        );

        return isset($aliases[$compact]) ? $aliases[$compact] : sanitize_key($compact);
    }

    private static function class_label($slug) {
        $labels = array(
            'deathknight' => 'Рыцарь смерти',
            'demonhunter' => 'Охотник на демонов',
            'druid' => 'Друид',
            'hunter' => 'Охотник',
            'mage' => 'Маг',
            'paladin' => 'Паладин',
            'priest' => 'Жрец',
            'rogue' => 'Разбойник',
            'shaman' => 'Шаман',
            'warlock' => 'Чернокнижник',
            'warrior' => 'Воин',
        );

        return isset($labels[$slug]) ? $labels[$slug] : $slug;
    }

    private static function images_table() {
        global $wpdb;
        return $wpdb->prefix . 'manacost_arena_images';
    }

    private static function votes_table() {
        global $wpdb;
        return $wpdb->prefix . 'manacost_arena_votes';
    }
}

Manacost_Arena_Feed::init();

register_activation_hook(__FILE__, array('Manacost_Arena_Feed', 'activate'));
register_deactivation_hook(__FILE__, array('Manacost_Arena_Feed', 'deactivate'));
