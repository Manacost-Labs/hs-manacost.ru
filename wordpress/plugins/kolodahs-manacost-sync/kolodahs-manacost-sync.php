<?php
/**
 * Plugin Name: Kolodahs API Sync for Manacost Decks
 * Description: Adds Kolodahs image, archetype title, dust sync, and editor insert controls for Manacost hs_deck posts.
 * Version: 1.2.4
 * Author: Manacost Dev
 * Requires PHP: 7.4
 * Text Domain: kolodahs-manacost-sync
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Kolodahs_Manacost_Sync {
    const OPTION_ENDPOINT = 'kolodahs_api_endpoint';
    const OPTION_KEY = 'kolodahs_api_key';
    const OPTION_ENABLED = 'kolodahs_api_enabled';
    const OPTION_WAIT_SECONDS = 'kolodahs_api_wait_seconds';
    const API_CARD_DATA_SOURCE = 'hearthstonejson';
    const ARCHETYPE_ENDPOINT = 'https://api.blizzcore.ru/archetype';
    const ARCHETYPES_ENDPOINT = 'https://api.blizzcore.ru/archetypes';
    const CRON_SYNC = 'kolodahs_api_sync_deck';
    const CRON_RESULT = 'kolodahs_api_fetch_result';
    const META_CODE_HASH = '_kolodahs_deck_code_hash';
    const META_JOB_HASH = '_kolodahs_job_hash';
    const META_JOB_ID = '_kolodahs_job_id';
    const META_IMAGE_URL = '_kolodahs_image_url';
    const META_IMAGE_ATTACHMENT_ID = '_kolodahs_image_attachment_id';
    const META_API_STATE = '_kolodahs_api_state';
    const META_LAST_ERROR = '_kolodahs_last_error';
    const META_SYNCED_AT = '_kolodahs_synced_at';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_settings_page'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
        add_action('admin_footer', array(__CLASS__, 'render_shortcode_modal'));
        add_action('wp_ajax_kolodahs_api_sync_now', array(__CLASS__, 'ajax_sync_now'));
        add_action('wp_ajax_kolodahs_api_create_shortcode', array(__CLASS__, 'ajax_create_shortcode'));
        add_action('wp_ajax_kolodahs_api_refresh_nonces', array(__CLASS__, 'ajax_refresh_nonces'));
        add_filter('mce_external_plugins', array(__CLASS__, 'register_tinymce_plugin'));
        add_filter('mce_buttons', array(__CLASS__, 'register_tinymce_button'));
        // Do not auto-generate image/dust on a normal editor save. Editors can
        // still run Kolodahs explicitly via the "Сделать картинку и пыль" button,
        // shortcode modal, or REST integrations that call queue_sync().
        add_action(self::CRON_SYNC, array(__CLASS__, 'run_sync'), 10, 1);
        add_action(self::CRON_RESULT, array(__CLASS__, 'run_result_fetch'), 10, 3);
    }

    public static function activate() {
        add_option(self::OPTION_ENDPOINT, 'https://api.kolodahs.ru/v1/deck', '', false);
        add_option(self::OPTION_KEY, '', '', false);
        add_option(self::OPTION_ENABLED, '1', '', false);
        add_option(self::OPTION_WAIT_SECONDS, 20, '', false);
    }

    public static function add_settings_page() {
        add_submenu_page('edit.php?post_type=hs_deck', 'Kolodahs API', 'Kolodahs API', 'manage_options', 'kolodahs-api-sync', array(__CLASS__, 'render_settings_page'));
    }

    public static function register_settings() {
        register_setting('kolodahs_api_sync', self::OPTION_ENDPOINT, array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_endpoint'), 'default' => 'https://api.kolodahs.ru/v1/deck'));
        register_setting('kolodahs_api_sync', self::OPTION_KEY, array('type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => ''));
        register_setting('kolodahs_api_sync', self::OPTION_ENABLED, array('type' => 'string', 'sanitize_callback' => array(__CLASS__, 'sanitize_bool'), 'default' => '1'));
        register_setting('kolodahs_api_sync', self::OPTION_WAIT_SECONDS, array('type' => 'integer', 'sanitize_callback' => array(__CLASS__, 'sanitize_wait_seconds'), 'default' => 20));
    }

    public static function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Not allowed.', 'kolodahs-manacost-sync'));
        }
        $endpoint = get_option(self::OPTION_ENDPOINT, 'https://api.kolodahs.ru/v1/deck');
        $key = get_option(self::OPTION_KEY, '');
        $enabled = get_option(self::OPTION_ENABLED, '1');
        $wait = (int) get_option(self::OPTION_WAIT_SECONDS, 20);
        ?>
        <div class="wrap">
            <h1>Kolodahs API Sync</h1>
            <form method="post" action="options.php">
                <?php settings_fields('kolodahs_api_sync'); ?>
                <table class="form-table" role="presentation">
                    <tr><th scope="row"><label for="<?php echo esc_attr(self::OPTION_ENABLED); ?>">Enabled</label></th><td><label><input type="checkbox" id="<?php echo esc_attr(self::OPTION_ENABLED); ?>" name="<?php echo esc_attr(self::OPTION_ENABLED); ?>" value="1" <?php checked($enabled, '1'); ?>> Run sync automatically when a deck code is saved.</label></td></tr>
                    <tr><th scope="row"><label for="<?php echo esc_attr(self::OPTION_ENDPOINT); ?>">API endpoint</label></th><td><input type="url" class="regular-text" id="<?php echo esc_attr(self::OPTION_ENDPOINT); ?>" name="<?php echo esc_attr(self::OPTION_ENDPOINT); ?>" value="<?php echo esc_attr($endpoint); ?>"><p class="description">Default: https://api.kolodahs.ru/v1/deck</p></td></tr>
                    <tr><th scope="row"><label for="<?php echo esc_attr(self::OPTION_KEY); ?>">API key</label></th><td><input type="password" class="regular-text" id="<?php echo esc_attr(self::OPTION_KEY); ?>" name="<?php echo esc_attr(self::OPTION_KEY); ?>" value="<?php echo esc_attr($key); ?>" autocomplete="new-password"><p class="description">Stored only in wp_options. Do not commit it to Git.</p></td></tr>
                    <tr><th scope="row"><label for="<?php echo esc_attr(self::OPTION_WAIT_SECONDS); ?>">Wait seconds</label></th><td><input type="number" min="0" max="25" step="1" id="<?php echo esc_attr(self::OPTION_WAIT_SECONDS); ?>" name="<?php echo esc_attr(self::OPTION_WAIT_SECONDS); ?>" value="<?php echo esc_attr($wait); ?>"></td></tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public static function enqueue_admin_assets($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        wp_enqueue_script('jquery');
        wp_enqueue_style('thickbox');
        wp_enqueue_script('thickbox');
        wp_add_inline_style('common', self::admin_css());
        $asset = plugin_dir_path(__FILE__) . 'assets/kolodahs-admin.js';
        wp_enqueue_script('kolodahs-admin-sync', set_url_scheme(plugins_url('assets/kolodahs-admin.js', __FILE__), 'https'), array('jquery', 'thickbox'), is_file($asset) ? filemtime($asset) : '1.2.4', true);
        wp_localize_script('kolodahs-admin-sync', 'KolodahsDeckSync', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kolodahs_api_sync_now'),
            'shortcodeNonce' => wp_create_nonce('kolodahs_api_create_shortcode'),
        ));
    }

    public static function register_tinymce_plugin($plugins) {
        if ((!current_user_can('edit_posts') && !current_user_can('edit_pages')) || get_user_option('rich_editing') !== 'true') {
            return $plugins;
        }
        $asset = plugin_dir_path(__FILE__) . 'assets/kolodahs-tinymce.js';
        $plugins['kolodahs_deck_shortcode'] = set_url_scheme(add_query_arg('v', is_file($asset) ? filemtime($asset) : '1.2.4', plugins_url('assets/kolodahs-tinymce.js', __FILE__)), 'https');
        return $plugins;
    }

    public static function register_tinymce_button($buttons) {
        if (current_user_can('edit_posts') || current_user_can('edit_pages')) {
            $buttons[] = 'kolodahs_deck_shortcode';
        }
        return $buttons;
    }

    public static function render_shortcode_modal() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || ($screen->base !== 'post' && $screen->base !== 'post-new') || (!current_user_can('edit_posts') && !current_user_can('edit_pages'))) {
            return;
        }
        ?>
        <div id="kolodahs-shortcode-modal" style="display:none;">
            <div class="kolodahs-shortcode-modal">
                <label for="kolodahs-shortcode-code">Код колоды</label>
                <textarea id="kolodahs-shortcode-code" rows="6" placeholder="AAECA..."></textarea>
                <div class="kolodahs-shortcode-actions">
                    <button type="button" class="button button-primary" id="kolodahs-shortcode-insert">Вставить</button>
                    <span id="kolodahs-shortcode-status" aria-live="polite"></span>
                </div>
                <div id="kolodahs-shortcode-preview"></div>
            </div>
        </div>
        <?php
    }

    public static function ajax_sync_now() {
        if (!self::verify_ajax_nonce('kolodahs_api_sync_now')) {
            wp_send_json_error(array('message' => 'Сессия редактора устарела. Повторяю запрос с новым nonce.'), 403);
        }
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $code = isset($_POST['deck_code']) ? self::normalize_deck_code(sanitize_textarea_field(wp_unslash($_POST['deck_code']))) : '';
        if (!$post_id || get_post_type($post_id) !== 'hs_deck' || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => 'Колода не найдена или нет прав.'));
        }
        if ($code === '' || !self::has_api_credentials()) {
            wp_send_json_error(array('message' => $code === '' ? 'Вставьте код колоды.' : 'Не настроен ключ Kolodahs API.'));
        }
        self::save_code_and_title($post_id, $code);
        delete_transient('kolodahs_api_sync_' . $post_id);
        self::run_sync($post_id);
        self::send_payload($post_id, 'Готово: пыль и картинка обновлены.');
    }

    public static function ajax_create_shortcode() {
        if (!self::verify_ajax_nonce('kolodahs_api_create_shortcode')) {
            wp_send_json_error(array('message' => 'Сессия редактора устарела. Повторяю запрос с новым nonce.'), 403);
        }
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
            wp_send_json_error(array('message' => 'Недостаточно прав.'));
        }
        $code = isset($_POST['deck_code']) ? self::normalize_deck_code(sanitize_textarea_field(wp_unslash($_POST['deck_code']))) : '';
        if ($code === '' || !self::has_api_credentials()) {
            wp_send_json_error(array('message' => $code === '' ? 'Вставьте код колоды.' : 'Не настроен ключ Kolodahs API.'));
        }
        $title = self::resolve_archetype_title($code);
        $post_id = wp_insert_post(array('post_type' => 'hs_deck', 'post_status' => 'publish', 'post_title' => $title !== '' ? $title : 'Колода Hearthstone', 'post_content' => ''), true);
        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => $post_id->get_error_message()));
        }
        update_post_meta($post_id, '_hide_from_feed', '1');
        update_post_meta($post_id, '_show_proof_single', '0');
        self::save_code_and_title($post_id, $code, false);
        delete_transient('kolodahs_api_sync_' . $post_id);
        self::run_sync($post_id);
        self::send_payload($post_id, 'Колода создана, шорткод вставлен.', array('shortcode' => '[hs_deck id="' . $post_id . '"]', 'edit_url' => get_edit_post_link($post_id, 'raw')));
    }

    public static function ajax_refresh_nonces() {
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
            wp_send_json_error(array('message' => 'Недостаточно прав.'), 403);
        }
        wp_send_json_success(self::nonce_payload());
    }

    public static function schedule_from_save($post_id, $post = null, $update = null) {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        $post = $post ?: get_post($post_id);
        if ($post && $post->post_type === 'hs_deck') {
            self::schedule_sync($post_id);
        }
    }

    public static function schedule_from_meta($meta_id, $post_id, $meta_key, $meta_value) {
        if ($meta_key === '_deck_code' && get_post_type($post_id) === 'hs_deck') {
            self::schedule_sync($post_id);
        }
    }

    public static function queue_sync($post_id) {
        self::schedule_sync($post_id);
    }

    private static function schedule_sync($post_id) {
        if (get_option(self::OPTION_ENABLED, '1') !== '1' || !self::has_api_credentials()) {
            return;
        }
        $code = self::get_deck_code($post_id);
        $hash = self::deck_code_hash($code);
        $attachment_id = absint(get_post_meta($post_id, self::META_IMAGE_ATTACHMENT_ID, true));
        if ($code === '' || ((string) get_post_meta($post_id, self::META_CODE_HASH, true) === $hash && $attachment_id && get_post($attachment_id) && get_post_meta($post_id, '_dust_cost', true) !== '')) {
            return;
        }
        update_post_meta($post_id, self::META_API_STATE, 'queued');
        delete_post_meta($post_id, self::META_LAST_ERROR);
        if (!wp_next_scheduled(self::CRON_SYNC, array((int) $post_id))) {
            wp_schedule_single_event(time() + 5, self::CRON_SYNC, array((int) $post_id));
        }
    }

    public static function run_sync($post_id) {
        $post_id = absint($post_id);
        if (!$post_id || get_post_type($post_id) !== 'hs_deck' || !self::has_api_credentials()) {
            return;
        }
        $lock = 'kolodahs_api_sync_' . $post_id;
        if (get_transient($lock)) {
            return;
        }
        set_transient($lock, 1, 2 * MINUTE_IN_SECONDS);
        $code = self::get_deck_code($post_id);
        if ($code === '') {
            delete_transient($lock);
            return;
        }
        update_post_meta($post_id, self::META_API_STATE, 'requesting');
        $wait = self::sanitize_wait_seconds(get_option(self::OPTION_WAIT_SECONDS, 20));
        $result = self::remote_json('POST', self::endpoint(), array(
            'deck_code' => $code,
            'title' => html_entity_decode(get_the_title($post_id), ENT_QUOTES, get_bloginfo('charset')),
            'wait_seconds' => $wait,
            'card_data_source' => self::API_CARD_DATA_SOURCE,
        ), max(12, $wait + 8), true);
        if (is_wp_error($result)) {
            self::set_error($post_id, $result->get_error_message());
            delete_transient($lock);
            return;
        }
        $job = isset($result['job']) && is_array($result['job']) ? $result['job'] : array();
        self::apply_job_data($post_id, $job, $code);
        if (empty($job['ready']) && !empty($job['hash'])) {
            self::schedule_result_fetch($post_id, (string) $job['hash'], 1);
        }
        delete_transient($lock);
    }

    public static function run_result_fetch($post_id, $hash, $attempt = 1) {
        $post_id = absint($post_id);
        $hash = sanitize_text_field((string) $hash);
        $attempt = max(1, absint($attempt));
        if (!$post_id || $hash === '' || get_post_type($post_id) !== 'hs_deck') {
            return;
        }
        $result = self::remote_json('GET', untrailingslashit(self::endpoint()) . '/' . rawurlencode($hash), null, 12, true);
        if (is_wp_error($result)) {
            self::set_error($post_id, $result->get_error_message());
            return;
        }
        $job = isset($result['job']) && is_array($result['job']) ? $result['job'] : array();
        self::apply_job_data($post_id, $job, self::get_deck_code($post_id));
        if (empty($job['ready']) && $attempt < 10) {
            self::schedule_result_fetch($post_id, $hash, $attempt + 1);
        }
    }

    private static function save_code_and_title($post_id, $code, $resolve_title = true) {
        if ($resolve_title) {
            $title = self::resolve_archetype_title($code);
            if ($title !== '') {
                wp_update_post(array('ID' => $post_id, 'post_title' => $title));
            }
        }
        update_post_meta($post_id, '_deck_code', $code);
        if (class_exists('Unified_HS_Deckstring_Helper')) {
            Unified_HS_Deckstring_Helper::assign_terms_from_code($post_id, $code);
        }
    }

    private static function resolve_archetype_title($code) {
        $recognized = self::remote_json('POST', self::ARCHETYPE_ENDPOINT, array('deck_code' => $code), 20, false);
        if (is_wp_error($recognized) || empty($recognized['success'])) {
            return '';
        }
        $raw = '';
        foreach (array('archetype_raw', 'deck_name_raw') as $field) {
            if (!empty($recognized[$field])) {
                $raw = trim((string) $recognized[$field]);
                break;
            }
        }
        if ($raw !== '') {
            $translated = self::translate_archetype($raw);
            if ($translated !== '') {
                return $translated;
            }
        }
        return !empty($recognized['archetype']) ? sanitize_text_field((string) $recognized['archetype']) : '';
    }

    private static function translate_archetype($raw) {
        $table = self::remote_json('GET', self::ARCHETYPES_ENDPOINT . '?search=' . rawurlencode($raw) . '&limit=50', null, 12, false);
        if (is_wp_error($table) || empty($table['success']) || empty($table['items']) || !is_array($table['items'])) {
            return '';
        }
        $needle = function_exists('mb_strtolower') ? mb_strtolower($raw, 'UTF-8') : strtolower($raw);
        $fallback = '';
        foreach ($table['items'] as $item) {
            if (empty($item['name_ru'])) {
                continue;
            }
            $fallback = $fallback === '' ? sanitize_text_field((string) $item['name_ru']) : $fallback;
            $name = isset($item['name_en']) ? trim((string) $item['name_en']) : '';
            $name = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
            if ($name === $needle) {
                return sanitize_text_field((string) $item['name_ru']);
            }
        }
        return $fallback;
    }

    private static function remote_json($method, $url, $body = null, $timeout = 20, $auth = true) {
        $headers = array('Accept' => 'application/json');
        if ($auth) {
            $headers['X-Kolodahs-Api-Key'] = self::api_key();
        }
        $args = array('method' => $method, 'timeout' => $timeout, 'headers' => $headers);
        if ($body !== null) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($body);
        }
        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) {
            return $response;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($data)) {
            return new WP_Error('kolodahs_bad_json', 'Remote API returned invalid JSON.');
        }
        if ($code < 200 || $code >= 300 || empty($data['success'])) {
            $message = isset($data['error']['message']) ? $data['error']['message'] : (isset($data['error']) ? (string) $data['error'] : 'Remote API request failed.');
            return new WP_Error('kolodahs_remote_error', $message, array('status' => $code));
        }
        return $data;
    }

    private static function apply_job_data($post_id, array $job, $code) {
        if (empty($job)) {
            self::set_error($post_id, 'Kolodahs API response has no job data.');
            return;
        }
        if (isset($job['id'])) {
            update_post_meta($post_id, self::META_JOB_ID, absint($job['id']));
        }
        if (!empty($job['hash'])) {
            update_post_meta($post_id, self::META_JOB_HASH, sanitize_text_field($job['hash']));
        }
        if (isset($job['state'])) {
            update_post_meta($post_id, self::META_API_STATE, sanitize_key($job['state']));
        }
        if (isset($job['dust'])) {
            update_post_meta($post_id, '_dust_cost', absint($job['dust']));
        }
        if (!empty($job['image_url'])) {
            $image_url = esc_url_raw($job['image_url']);
            $attachment_id = self::sideload_image($post_id, $image_url, isset($job['hash']) ? $job['hash'] : '');
            update_post_meta($post_id, self::META_IMAGE_URL, $image_url);
            if ($attachment_id) {
                update_post_meta($post_id, self::META_IMAGE_ATTACHMENT_ID, $attachment_id);
                set_post_thumbnail($post_id, $attachment_id);
                self::bump_hs_decks_cache();
            }
        }
        if (!empty($job['ready'])) {
            update_post_meta($post_id, self::META_CODE_HASH, self::deck_code_hash($code));
            update_post_meta($post_id, self::META_SYNCED_AT, current_time('mysql'));
            delete_post_meta($post_id, self::META_LAST_ERROR);
        }
    }

    private static function sideload_image($post_id, $url, $hash) {
        $existing_url = (string) get_post_meta($post_id, self::META_IMAGE_URL, true);
        $existing_id = absint(get_post_meta($post_id, self::META_IMAGE_ATTACHMENT_ID, true));
        if ($existing_url === $url && $existing_id && get_post($existing_id)) {
            return $existing_id;
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $title = sanitize_file_name(get_the_title($post_id));
        $title = ($title !== '' ? $title : 'deck') . ($hash !== '' ? '-' . sanitize_file_name($hash) : '');
        $attachment_id = media_sideload_image($url, $post_id, $title, 'id');
        if (is_wp_error($attachment_id)) {
            self::set_error($post_id, $attachment_id->get_error_message());
            return 0;
        }
        return absint($attachment_id);
    }

    private static function send_payload($post_id, $ok_message, $extra = array()) {
        $state = (string) get_post_meta($post_id, self::META_API_STATE, true);
        $error = (string) get_post_meta($post_id, self::META_LAST_ERROR, true);
        if ($state === 'error') {
            wp_send_json_error(array_merge(array('message' => $error !== '' ? $error : 'Kolodahs API вернул ошибку.', 'post_id' => $post_id), $extra));
        }
        $attachment_id = absint(get_post_meta($post_id, self::META_IMAGE_ATTACHMENT_ID, true));
        $thumb = $attachment_id ? wp_get_attachment_image_url($attachment_id, 'medium') : '';
        $featured_html = '';
        if ($attachment_id) {
            if (!function_exists('_wp_post_thumbnail_html')) {
                require_once ABSPATH . 'wp-admin/includes/post.php';
            }
            if (function_exists('_wp_post_thumbnail_html')) {
                $featured_html = _wp_post_thumbnail_html($attachment_id, $post_id);
            }
        }
        $payload = array(
            'message' => $ok_message,
            'post_id' => $post_id,
            'title' => get_the_title($post_id),
            'state' => $state,
            'dust' => get_post_meta($post_id, '_dust_cost', true),
            'image_url' => get_post_meta($post_id, self::META_IMAGE_URL, true),
            'thumbnail_url' => $thumb ?: ($attachment_id ? wp_get_attachment_url($attachment_id) : ''),
            'full_image_url' => $attachment_id ? wp_get_attachment_url($attachment_id) : '',
            'featured_image_html' => $featured_html,
            'attachment_id' => $attachment_id,
        );
        wp_send_json_success(array_merge($payload, $extra));
    }

    private static function verify_ajax_nonce($action) {
        if (check_ajax_referer($action, 'nonce', false)) {
            return true;
        }
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
            return false;
        }
        $referer = wp_get_referer();
        return $referer && strpos($referer, admin_url()) === 0;
    }

    private static function nonce_payload() {
        return array(
            'nonce' => wp_create_nonce('kolodahs_api_sync_now'),
            'shortcodeNonce' => wp_create_nonce('kolodahs_api_create_shortcode'),
        );
    }

    private static function schedule_result_fetch($post_id, $hash, $attempt) {
        wp_schedule_single_event(time() + min(300, 30 * max(1, (int) $attempt)), self::CRON_RESULT, array((int) $post_id, (string) $hash, (int) $attempt));
    }

    private static function get_deck_code($post_id) {
        return self::normalize_deck_code(get_post_meta($post_id, '_deck_code', true));
    }

    private static function deck_code_hash($code) {
        return hash('sha256', self::normalize_deck_code($code));
    }

    private static function normalize_deck_code($code) {
        return preg_replace('/\s+/', '', trim((string) $code));
    }

    private static function has_api_credentials() {
        return self::endpoint() !== '' && self::api_key() !== '';
    }

    private static function endpoint() {
        return self::sanitize_endpoint(get_option(self::OPTION_ENDPOINT, 'https://api.kolodahs.ru/v1/deck'));
    }

    private static function api_key() {
        return sanitize_text_field((string) get_option(self::OPTION_KEY, ''));
    }

    private static function set_error($post_id, $message) {
        update_post_meta($post_id, self::META_API_STATE, 'error');
        update_post_meta($post_id, self::META_LAST_ERROR, sanitize_text_field($message));
    }

    private static function bump_hs_decks_cache() {
        if (class_exists('HS_Decks_Manager')) {
            $ver = HS_Decks_Manager::get_decks_cache_version();
            update_option('hs_decks_cache_version', $ver + 1, false);
        }
    }

    public static function sanitize_endpoint($value) {
        $value = esc_url_raw(trim((string) $value));
        return $value !== '' ? untrailingslashit($value) : 'https://api.kolodahs.ru/v1/deck';
    }

    public static function sanitize_bool($value) {
        return !empty($value) ? '1' : '0';
    }

    public static function sanitize_wait_seconds($value) {
        return max(0, min(25, absint($value)));
    }

    private static function admin_css() {
        return '.kolodahs-code-action-row{display:grid;grid-template-columns:minmax(0,1fr) 230px;gap:12px;align-items:start;max-width:100%}.kolodahs-code-action-row textarea{width:100%}.kolodahs-code-action-panel .button,#kolodahs-shortcode-insert{width:100%;text-align:center;background:#2563eb;border-color:#1d4ed8;color:#fff;font-weight:600;border-radius:6px}.kolodahs-code-action-panel .button:hover,#kolodahs-shortcode-insert:hover{background:#1d4ed8;border-color:#1e40af;color:#fff}.kolodahs-code-action-panel .button:disabled,#kolodahs-shortcode-insert:disabled{background:#9ca3af!important;border-color:#9ca3af!important}.kolodahs-code-action-status{display:block;margin-top:8px;font-size:12px;line-height:1.4}.kolodahs-code-action-preview{margin-top:8px}.kolodahs-code-action-preview img{max-width:180px;height:auto;display:block;border:1px solid #ccd0d4;border-radius:4px}.kolodahs-shortcode-modal{padding:16px 4px 4px}.kolodahs-shortcode-modal label{display:block;font-weight:600;margin-bottom:6px}.kolodahs-shortcode-modal textarea{width:100%;min-height:140px;font-family:monospace}.kolodahs-shortcode-actions{display:grid;grid-template-columns:180px minmax(0,1fr);gap:12px;align-items:center;margin-top:12px}#kolodahs-shortcode-preview{margin-top:14px}#kolodahs-shortcode-preview img{max-width:220px;height:auto;display:block;border:1px solid #ccd0d4;border-radius:6px}@media(max-width:782px){.kolodahs-code-action-row,.kolodahs-shortcode-actions{grid-template-columns:1fr}.kolodahs-code-action-panel .button,#kolodahs-shortcode-insert{width:auto}}';
    }
}

Kolodahs_Manacost_Sync::init();
register_activation_hook(__FILE__, array('Kolodahs_Manacost_Sync', 'activate'));
