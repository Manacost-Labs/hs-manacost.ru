<?php

if (!defined('ABSPATH') && !defined('HS_MANACOST_INLINE_DECK_TESTING')) {
    exit;
}

final class HS_Manacost_Inline_Deck {
    private const API_BASE = 'https://api.kolodahs.ru';
    private const ATTACHMENT_HASH_META = '_hs_manacost_inline_deck_code_hash';
    private const ATTACHMENT_CODE_META = '_hs_manacost_inline_deck_code';
    private const ATTACHMENT_LAYOUT_META = '_hs_manacost_inline_deck_layout_version';
    private const LAYOUT_VERSION = '2';
    private const MAX_CODE_LENGTH = 2048;
    private const MAX_IMAGE_BYTES = 8388608;

    private $plugin_file;

    public function __construct($plugin_file, $register_hooks = true) {
        $this->plugin_file = (string) $plugin_file;

        if ($register_hooks) {
            add_shortcode('hs_deck_link', array($this, 'shortcode'));
            add_action('wp_ajax_hs_manacost_inline_deck_start', array($this, 'ajax_start'));
            add_action('wp_ajax_hs_manacost_inline_deck_finish', array($this, 'ajax_finish'));
        }
    }

    public static function normalize_deck_code($raw_code) {
        $code = preg_replace('/\s+/', '', (string) $raw_code);
        if (!is_string($code) || strlen($code) < 16 || strlen($code) > self::MAX_CODE_LENGTH) {
            return '';
        }

        if (!preg_match('/^[A-Za-z0-9+\/=]+$/', $code)) {
            return '';
        }

        return $code;
    }

    public static function is_allowed_image_url($url) {
        $parts = wp_parse_url((string) $url);
        if (!is_array($parts)) {
            return false;
        }

        if (($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') !== 'api.kolodahs.ru') {
            return false;
        }

        $path = $parts['path'] ?? '';
        return (bool) preg_match('#^/output/[A-Za-z0-9._-]+\.(?:jpe?g|png|webp)$#i', $path);
    }

    public static function deck_code_hash($deck_code) {
        $code = self::normalize_deck_code($deck_code);
        return $code === '' ? '' : hash('sha256', $code);
    }

    public static function is_valid_job_hash($job_hash) {
        return (bool) preg_match('/^[a-f0-9]{32}$/', (string) $job_hash);
    }

    public static function parse_api_job($payload) {
        if (!is_array($payload) || empty($payload['success']) || !isset($payload['job']) || !is_array($payload['job'])) {
            return array('status' => 'error', 'message' => 'Некорректный ответ сервиса генерации.');
        }

        $job = $payload['job'];
        $job_hash = isset($job['hash']) ? (string) $job['hash'] : '';
        if (!self::is_valid_job_hash($job_hash)) {
            return array('status' => 'error', 'message' => 'Сервис вернул некорректный идентификатор задания.');
        }

        $state = isset($job['state']) ? (string) $job['state'] : '';
        if ($state === 'error') {
            return array('status' => 'error', 'message' => 'Не удалось создать изображение колоды.');
        }

        $is_ready = !empty($job['ready']) || $state === 'done';
        if (!$is_ready) {
            return array(
                'status' => 'pending',
                'job_hash' => $job_hash,
            );
        }

        $image_url = isset($job['image_url']) ? (string) $job['image_url'] : '';
        if (!self::is_allowed_image_url($image_url)) {
            return array('status' => 'error', 'message' => 'Сервис вернул недопустимый адрес изображения.');
        }

        return array(
            'status' => 'ready',
            'job_hash' => $job_hash,
            'image_url' => $image_url,
        );
    }

    public static function build_shortcode($attachment_id, $deck_code, $selected_html) {
        $attachment_id = absint($attachment_id);
        $deck_code = self::normalize_deck_code($deck_code);
        $selected_html = wp_kses_post((string) $selected_html);

        if (!$attachment_id || $deck_code === '' || trim(wp_strip_all_tags($selected_html)) === '') {
            return '';
        }

        return sprintf(
            '[hs_deck_link image_id="%d"]%s[/hs_deck_link]',
            $attachment_id,
            $selected_html
        );
    }

    public function shortcode($atts, $content = '') {
        $atts = shortcode_atts(
            array(
                'image_id' => 0,
                'code' => '',
            ),
            is_array($atts) ? $atts : array(),
            'hs_deck_link'
        );

        $label = wp_kses_post((string) $content);
        $attachment_id = absint($atts['image_id']);
        $deck_code = self::normalize_deck_code($atts['code']);
        if ($deck_code === '' && $attachment_id) {
            $deck_code = self::normalize_deck_code(
                get_post_meta($attachment_id, self::ATTACHMENT_CODE_META, true)
            );
        }
        $image_url = '';
        if ($attachment_id) {
            $image_url = wp_get_attachment_image_url($attachment_id, '1536x1536');
            if (!$image_url) {
                $image_url = wp_get_attachment_image_url($attachment_id, 'large');
            }
            if (!$image_url) {
                $image_url = wp_get_attachment_image_url($attachment_id, 'full');
            }
        }

        if ($label === '' || $deck_code === '' || !$image_url) {
            return $label;
        }

        $title = trim(wp_strip_all_tags($label));
        $icon_url = plugins_url('assets/code-to-game-inline.png', $this->plugin_file);

        return sprintf(
            '<a class="hs-mi-deck-link no-lightbox" href="%1$s" rel="nofollow noopener" data-hs-mi-title="%2$s" data-hs-mi-code="%3$s" aria-label="%4$s"><img class="hs-mi-deck-icon" src="%5$s" width="168" height="62" alt="" aria-hidden="true" decoding="async" fetchpriority="low"><span class="hs-mi-deck-label">%6$s</span></a>',
            esc_url($image_url),
            esc_attr($title),
            esc_attr($deck_code),
            esc_attr(sprintf('Открыть изображение колоды «%s»', $title)),
            esc_url($icon_url),
            $label
        );
    }

    public function ajax_start() {
        $input = $this->validated_editor_input();
        if (is_wp_error($input)) {
            wp_send_json_error(array('message' => $input->get_error_message()), 400);
        }

        $cached_id = $this->find_cached_attachment($input['code_hash']);
        if ($cached_id) {
            wp_send_json_success($this->editor_payload($cached_id, $input, true));
        }

        $api_key = $this->get_api_key();
        if ($api_key === '') {
            wp_send_json_error(array('message' => 'Генератор изображений пока не настроен. Сообщите администратору.'), 503);
        }

        $lock_key = 'hs_mi_lock_' . get_current_user_id() . '_' . substr($input['code_hash'], 0, 24);
        $known_job = get_transient($lock_key);
        if (is_string($known_job) && self::is_valid_job_hash($known_job)) {
            wp_send_json_success(array(
                'status' => 'pending',
                'job_hash' => $known_job,
                'message' => 'Изображение создаётся…',
            ));
        }

        $response = wp_remote_post(
            self::API_BASE . '/v1/deck',
            array(
                'timeout' => 25,
                'redirection' => 0,
                'headers' => array(
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-Kolodahs-Api-Key' => $api_key,
                ),
                'body' => wp_json_encode(array(
                    'title' => $input['title'],
                    'deck_code' => $input['code'],
                    'wait_seconds' => 20,
                    'card_data_source' => 'hearthstonejson',
                )),
            )
        );

        $job = $this->parse_remote_job_response($response, array(200, 201));
        if (is_wp_error($job)) {
            wp_send_json_error(array('message' => $job->get_error_message()), 502);
        }

        if ($job['status'] === 'pending') {
            set_transient($lock_key, $job['job_hash'], 15 * MINUTE_IN_SECONDS);
            set_transient(
                'hs_mi_job_' . $job['job_hash'],
                array(
                    'user_id' => get_current_user_id(),
                    'code_hash' => $input['code_hash'],
                ),
                15 * MINUTE_IN_SECONDS
            );

            wp_send_json_success(array(
                'status' => 'pending',
                'job_hash' => $job['job_hash'],
                'message' => 'Изображение создаётся…',
            ));
        }

        $attachment_id = $this->import_image_to_media_library(
            $job['image_url'],
            $input['code_hash'],
            $input['post_id'],
            $input['title']
        );
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()), 502);
        }

        wp_send_json_success($this->editor_payload($attachment_id, $input, false));
    }

    public function ajax_finish() {
        $input = $this->validated_editor_input();
        if (is_wp_error($input)) {
            wp_send_json_error(array('message' => $input->get_error_message()), 400);
        }

        $cached_id = $this->find_cached_attachment($input['code_hash']);
        if ($cached_id) {
            wp_send_json_success($this->editor_payload($cached_id, $input, true));
        }

        $job_hash = isset($_POST['job_hash']) ? sanitize_key(wp_unslash($_POST['job_hash'])) : '';
        if (!self::is_valid_job_hash($job_hash)) {
            wp_send_json_error(array('message' => 'Некорректное задание генерации.'), 400);
        }

        $job_access = get_transient('hs_mi_job_' . $job_hash);
        if (
            !is_array($job_access) ||
            (int) ($job_access['user_id'] ?? 0) !== get_current_user_id() ||
            !hash_equals((string) ($job_access['code_hash'] ?? ''), $input['code_hash'])
        ) {
            wp_send_json_error(array('message' => 'Задание генерации истекло. Запустите создание ещё раз.'), 403);
        }

        $response = wp_safe_remote_get(
            self::API_BASE . '/v1/deck/' . rawurlencode($job_hash),
            array(
                'timeout' => 12,
                'redirection' => 0,
                'headers' => array('Accept' => 'application/json'),
            )
        );
        $job = $this->parse_remote_job_response($response, array(200));
        if (is_wp_error($job)) {
            wp_send_json_error(array('message' => $job->get_error_message()), 502);
        }

        if ($job['status'] === 'pending') {
            wp_send_json_success(array(
                'status' => 'pending',
                'job_hash' => $job_hash,
                'message' => 'Изображение создаётся…',
            ));
        }

        $attachment_id = $this->import_image_to_media_library(
            $job['image_url'],
            $input['code_hash'],
            $input['post_id'],
            $input['title']
        );
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()), 502);
        }

        delete_transient('hs_mi_job_' . $job_hash);
        delete_transient('hs_mi_lock_' . get_current_user_id() . '_' . substr($input['code_hash'], 0, 24));

        wp_send_json_success($this->editor_payload($attachment_id, $input, false));
    }

    private function validated_editor_input() {
        check_ajax_referer('hs_manacost_inline_deck_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            return new WP_Error('forbidden', 'Недостаточно прав для создания изображения.');
        }

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        if ($post_id && !current_user_can('edit_post', $post_id)) {
            return new WP_Error('forbidden_post', 'Недостаточно прав для изменения этой записи.');
        }

        $code = isset($_POST['code']) ? self::normalize_deck_code(wp_unslash($_POST['code'])) : '';
        if ($code === '') {
            return new WP_Error('invalid_code', 'Введите корректный код колоды Hearthstone.');
        }

        $selected_html = isset($_POST['selected_html']) ? wp_kses_post(wp_unslash($_POST['selected_html'])) : '';
        $title = trim(wp_strip_all_tags($selected_html));
        if ($title === '') {
            return new WP_Error('missing_selection', 'Сначала выделите слово или фразу в редакторе.');
        }

        if (function_exists('mb_substr')) {
            $title = mb_substr($title, 0, 120, 'UTF-8');
        } else {
            $title = substr($title, 0, 120);
        }

        return array(
            'post_id' => $post_id,
            'code' => $code,
            'code_hash' => self::deck_code_hash($code),
            'selected_html' => $selected_html,
            'title' => $title,
        );
    }

    private function get_api_key() {
        if (defined('KOLODAHS_API_KEY') && is_string(KOLODAHS_API_KEY)) {
            return trim(KOLODAHS_API_KEY);
        }

        $value = getenv('KOLODAHS_API_KEY');
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        // Оба приложения работают на одном сервере под одним владельцем.
        // Читаем существующий серверный конфиг напрямую, не копируя секрет
        // в базу WordPress и никогда не передавая его в браузер.
        $shared_config_file = '/var/www/koloda/data/www/kolodahs.ru/config/api-local.php';
        if (is_readable($shared_config_file)) {
            $config = require $shared_config_file;
            $keys = is_array($config) && isset($config['keys']) && is_array($config['keys'])
                ? array_keys($config['keys'])
                : array();
            if ($keys && is_string($keys[0])) {
                return trim($keys[0]);
            }
        }

        return '';
    }

    private function parse_remote_job_response($response, array $allowed_statuses) {
        if (is_wp_error($response)) {
            return new WP_Error('api_unavailable', 'Сервис генерации временно недоступен.');
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        if (!in_array($status, $allowed_statuses, true)) {
            return new WP_Error('api_status', 'Сервис не принял код колоды.');
        }

        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        $job = self::parse_api_job($payload);
        if ($job['status'] === 'error') {
            return new WP_Error('api_payload', $job['message']);
        }

        return $job;
    }

    private function find_cached_attachment($code_hash) {
        if (!preg_match('/^[a-f0-9]{64}$/', (string) $code_hash)) {
            return 0;
        }

        $ids = get_posts(array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => array('image/jpeg', 'image/png', 'image/webp'),
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => self::ATTACHMENT_HASH_META,
                    'value' => $code_hash,
                ),
                array(
                    'key' => self::ATTACHMENT_LAYOUT_META,
                    'value' => self::LAYOUT_VERSION,
                ),
            ),
        ));

        if (!$ids) {
            return 0;
        }

        $attachment_id = absint($ids[0]);
        return wp_get_attachment_url($attachment_id) ? $attachment_id : 0;
    }

    private function import_image_to_media_library($image_url, $code_hash, $post_id, $title) {
        $cached_id = $this->find_cached_attachment($code_hash);
        if ($cached_id) {
            return $cached_id;
        }

        if (!self::is_allowed_image_url($image_url)) {
            return new WP_Error('invalid_image_url', 'Сервис вернул недопустимый адрес изображения.');
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp_file = wp_tempnam('hs-manacost-inline-deck-image');
        if (!$tmp_file) {
            return new WP_Error('temp_file', 'Не удалось подготовить временный файл изображения.');
        }

        $response = wp_safe_remote_get(
            $image_url,
            array(
                'timeout' => 30,
                'redirection' => 0,
                'stream' => true,
                'filename' => $tmp_file,
                'limit_response_size' => self::MAX_IMAGE_BYTES,
            )
        );

        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            @unlink($tmp_file);
            return new WP_Error('image_download', 'Не удалось загрузить готовое изображение.');
        }

        $file_size = is_file($tmp_file) ? (int) filesize($tmp_file) : 0;
        $mime = function_exists('wp_get_image_mime') ? (string) wp_get_image_mime($tmp_file) : '';
        $extensions = array(
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        );

        if ($file_size < 1 || $file_size >= self::MAX_IMAGE_BYTES || !isset($extensions[$mime])) {
            @unlink($tmp_file);
            return new WP_Error('invalid_image', 'Полученный файл не является допустимым изображением.');
        }

        $filename = 'manacost-deck-' . substr($code_hash, 0, 20) . '.' . $extensions[$mime];
        $attachment_id = media_handle_sideload(
            array(
                'name' => $filename,
                'tmp_name' => $tmp_file,
            ),
            $post_id,
            $title
        );

        if (is_wp_error($attachment_id)) {
            @unlink($tmp_file);
            return new WP_Error('media_import', 'Не удалось сохранить изображение в медиатеку.');
        }

        update_post_meta($attachment_id, self::ATTACHMENT_HASH_META, $code_hash);
        update_post_meta($attachment_id, self::ATTACHMENT_LAYOUT_META, self::LAYOUT_VERSION);
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $title);

        return absint($attachment_id);
    }

    private function editor_payload($attachment_id, array $input, $cached) {
        update_post_meta($attachment_id, self::ATTACHMENT_CODE_META, $input['code']);

        $shortcode = self::build_shortcode(
            $attachment_id,
            $input['code'],
            $input['selected_html']
        );

        if ($shortcode === '') {
            return array(
                'status' => 'error',
                'message' => 'Не удалось подготовить вставку для редактора.',
            );
        }

        return array(
            'status' => 'ready',
            'attachment_id' => absint($attachment_id),
            'image_url' => (string) wp_get_attachment_url($attachment_id),
            'shortcode' => $shortcode,
            'cached' => (bool) $cached,
        );
    }
}
