<?php
/*
Plugin Name: Hearthstone Decks Manager
Description: Управление колодами Hearthstone с голосованием и фильтрацией (модуль Manacost: Decks)
Version: 1.0.19
Author: Manacost Dev
*/

if (!defined('ABSPATH')) exit;

if (!function_exists('hs_mb_lower')) {
    function hs_mb_lower($value) {
        $value = (string) $value;
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8');
        }
        return strtr(strtolower($value), array(
            'А' => 'а', 'Б' => 'б', 'В' => 'в', 'Г' => 'г', 'Д' => 'д',
            'Е' => 'е', 'Ё' => 'е', 'Ж' => 'ж', 'З' => 'з', 'И' => 'и',
            'Й' => 'й', 'К' => 'к', 'Л' => 'л', 'М' => 'м', 'Н' => 'н',
            'О' => 'о', 'П' => 'п', 'Р' => 'р', 'С' => 'с', 'Т' => 'т',
            'У' => 'у', 'Ф' => 'ф', 'Х' => 'х', 'Ц' => 'ц', 'Ч' => 'ч',
            'Ш' => 'ш', 'Щ' => 'щ', 'Ъ' => 'ъ', 'Ы' => 'ы', 'Ь' => 'ь',
            'Э' => 'э', 'Ю' => 'ю', 'Я' => 'я',
        ));
    }
}

/**
 * HS_Decks_Manager — основной класс плагина.
 *
 * НАВИГАЦИЯ ПО МЕТОДАМ (порядок в файле):
 *   ┌─ Конструктор и cache-инвалидация ─────────────── строки   22 –  98
 *   │   __construct, bump_decks_cache_version*, get_decks_cache_version
 *   │   handle_flush_rewrite_rules, force_flush_rewrite_rules
 *   ├─ Регистрация CPT и таксономий ────────────────── строки  131 – 306
 *   │   register_post_type, register_taxonomies, register_rest_meta
 *   ├─ Метабоксы и сохранение ──────────────────────── строки  307 – 549
 *   │   add_meta_boxes, render_meta_box, save_meta_boxes
 *   │   enqueue_admin_scripts
 *   ├─ Фронт-ассеты ─────────────────────────────────── строки  550 – 587
 *   │   enqueue_scripts (регистрация), enqueue_front_assets (use)
 *   ├─ Админ-страницы (Help/About/Announcement/Layout/Tags) ── 588 – 1024
 *   │   add_*_page, register_*_settings, render_*_page
 *   ├─ Колонки списка постов ────────────────────────── строки 1025 – 1092
 *   │   add_shortcode_column, render_shortcode_column
 *   ├─ Шорткоды (рендеринг + helpers) ────────────────── строки 1093 – 1822
 *   │   decks_shortcode, decks_random_shortcode, single_deck_shortcode,
 *   │   deck_group_shortcode, get_decks_html, get_single_deck_html,
 *   │   render_deck_card, filter_decks, get_total_games
 *   ├─ AJAX (голосование, копирование) ──────────────── строки 1823 – 1908
 *   │   handle_vote, track_copy, get_voter_id
 *   ├─ Bulk-действия ────────────────────────────────── строки 1909 – 1953
 *   │   register_bulk_actions, handle_bulk_actions, bulk_action_admin_notice
 *   └─ Внутренние helpers (announcement, layout, tags) ─ строки 1954 – конец
 *       get_announcement_data, has_announcement, get_global_announcement_box,
 *       get_layout_block_html, get_all_custom_tags_with_counts,
 *       remove_tag_from_all_decks, inject_deck_into_single
 *
 * TODO (split на отдельные классы — после введения тестов):
 *   • HS_Decks_Cpt_Registrar     (CPT + таксономии + REST meta)
 *   • HS_Decks_Meta_Boxes        (admin метабоксы)
 *   • HS_Decks_Admin_Pages       (Help/About/Layout/Announcement/Tags)
 *   • HS_Decks_Shortcodes        (рендеринг + кэш)
 *   • HS_Decks_Ajax_Handlers     (vote, copy)
 *   • HS_Decks_Bulk_Actions      (генерация шорткода группы)
 */
class HS_Decks_Manager {
    const DEFAULT_PER_PAGE = 13;
    const MOBILE_IMAGE_MIN_WIDTH = 640;
    const MOBILE_IMAGE_MAX_WIDTH = 768;

    public function __construct() {
        // --- Общие хуки (фронт + админка) ---
        add_action('init', array($this, 'register_post_type'), 5);
        add_action('init', array($this, 'register_taxonomies'), 5);
        add_action('init', array($this, 'register_rest_meta'), 6);
        add_action('after_setup_theme', array($this, 'register_archetype_image_sizes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('save_post_hs_deck', array($this, 'bump_decks_cache_version'));
        add_action('save_post_hs_deck', array($this, 'sync_filter_taxonomies'), 20);
        add_action('before_delete_post', array($this, 'bump_decks_cache_version_on_delete'));
        add_action('added_post_meta', array($this, 'bump_decks_cache_version_on_meta_change'), 10, 4);
        add_action('updated_post_meta', array($this, 'bump_decks_cache_version_on_meta_change'), 10, 4);
        add_action('deleted_post_meta', array($this, 'bump_decks_cache_version_on_meta_change'), 10, 4);

        // --- Шорткоды и фронт-AJAX (нужны и на фронте, и при wp-admin/admin-ajax.php) ---
        add_shortcode('hs_decks', array($this, 'decks_shortcode'));
        add_shortcode('hs_deck', array($this, 'single_deck_shortcode'));
        add_shortcode('tape-deck', array($this, 'single_deck_shortcode'));
        add_shortcode('hs_deck_group', array($this, 'deck_group_shortcode'));
        add_shortcode('hs_decks_random', array($this, 'decks_random_shortcode'));
        add_shortcode('hs_deck_archetypes', array($this, 'archetypes_shortcode'));
        add_shortcode('hs_deck_archetype', array($this, 'archetype_shortcode'));
        add_action('wp_ajax_vote_deck', array($this, 'handle_vote'));
        add_action('wp_ajax_nopriv_vote_deck', array($this, 'handle_vote'));
        add_action('wp_ajax_hs_decks_vote_counts', array($this, 'handle_vote_counts'));
        add_action('wp_ajax_nopriv_hs_decks_vote_counts', array($this, 'handle_vote_counts'));
        add_action('wp_ajax_copy_deck_code', array($this, 'track_copy'));
        add_action('wp_ajax_nopriv_copy_deck_code', array($this, 'track_copy'));
        add_action('wp_ajax_hs_decks_filter', array($this, 'handle_filter_decks_ajax'));
        add_action('wp_ajax_nopriv_hs_decks_filter', array($this, 'handle_filter_decks_ajax'));
        add_action('wp_ajax_hs_deck_archetypes_page', array($this, 'handle_archetypes_index_ajax'));
        add_action('wp_ajax_nopriv_hs_deck_archetypes_page', array($this, 'handle_archetypes_index_ajax'));
        add_action('wp_ajax_hs_decks_track_event', array($this, 'track_event'));
        add_action('wp_ajax_nopriv_hs_decks_track_event', array($this, 'track_event'));

        // --- Только фронт ---
        if (!is_admin()) {
            // Ассеты регистрируются лениво из enqueue_front_assets(), только
            // когда страница действительно отрендерила deck-shortcode.
            add_filter('the_content', array($this, 'inject_deck_into_single'));
            add_filter('wp_robots', array($this, 'filter_wp_robots'));
            add_filter('aioseo_robots_meta', array($this, 'filter_aioseo_robots_meta'), 20);
            add_filter('document_title_parts', array($this, 'filter_document_title_parts'));
            add_filter('aioseo_title', array($this, 'filter_aioseo_archetype_title'), 20);
            add_filter('aioseo_description', array($this, 'filter_aioseo_archetype_description'), 20);
            add_filter('aioseo_canonical_url', array($this, 'filter_aioseo_archetype_canonical_url'), 20);
            add_filter('aioseo_facebook_tags', array($this, 'filter_aioseo_archetype_facebook_tags'), 20);
            add_filter('aioseo_twitter_tags', array($this, 'filter_aioseo_archetype_twitter_tags'), 20);
            add_filter('template_include', array($this, 'template_include_archetype_archive'));
            add_action('wp_head', array($this, 'render_archetype_seo_meta'), 2);
            add_action('wp_head', array($this, 'render_deck_single_noindex_meta'), 1);
            return;
        }

        // --- Только админка ---
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('admin_menu', array($this, 'add_help_page'));
        add_action('admin_menu', array($this, 'add_about_page'));
        add_action('admin_menu', array($this, 'add_announcement_page'));
        add_action('admin_menu', array($this, 'add_layout_settings_page'));
        add_action('admin_menu', array($this, 'add_tags_manager_page'));
        add_action('admin_init', array($this, 'register_help_settings'));
        add_action('admin_init', array($this, 'register_plugin_settings'));
        add_action('admin_init', array($this, 'register_announcement_settings'));
        add_action('admin_init', array($this, 'register_layout_settings'));
        add_action('admin_init', array($this, 'force_flush_rewrite_rules'));
        add_filter('option_page_capability_hs_decks_help_settings', array($this, 'settings_capability'));
        add_filter('option_page_capability_hs_decks_plugin_settings', array($this, 'settings_capability'));
        add_filter('option_page_capability_hs_decks_announcement_settings', array($this, 'announcement_capability'));
        add_filter('option_page_capability_hs_decks_layout_settings', array($this, 'layout_capability'));
        add_filter('bulk_actions-edit-hs_deck', array($this, 'register_bulk_actions'));
        add_filter('handle_bulk_actions-edit-hs_deck', array($this, 'handle_bulk_actions'), 10, 3);
        add_action('restrict_manage_posts', array($this, 'render_bulk_term_controls'), 10, 2);
        add_action('admin_notices', array($this, 'bulk_action_admin_notice'));
        add_filter('manage_hs_deck_posts_columns', array($this, 'add_shortcode_column'));
        add_action('manage_hs_deck_posts_custom_column', array($this, 'render_shortcode_column'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_post_hs_decks_flush_rewrite_rules', array($this, 'handle_flush_rewrite_rules'));
        add_filter('post_row_actions', array($this, 'add_archive_row_action'), 10, 2);
        add_action('admin_post_hs_deck_archive_toggle', array($this, 'handle_archive_row_action'));
    }

    /**
     * Версия кэша для колод. Используется как часть ключа транзиентов,
     * чтобы инвалидировать весь пул одним инкрементом без сканирования wp_options.
     */
    public static function get_decks_cache_version() {
        $ver = get_option('hs_decks_cache_version');
        if (!$ver) {
            $ver = 1;
            update_option('hs_decks_cache_version', $ver, false);
        }
        return (int) $ver;
    }

    public function register_archetype_image_sizes() {
        add_image_size('hs_archetype_card', 640, 360, true);
        add_image_size('hs_archetype_hero', 960, 360, true);
    }

    public function bump_decks_cache_version($post_id = 0) {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        $ver = self::get_decks_cache_version();
        update_option('hs_decks_cache_version', $ver + 1, false);
    }

    public function bump_decks_cache_version_on_delete($post_id) {
        if (get_post_type($post_id) === 'hs_deck') {
            $this->bump_decks_cache_version($post_id);
        }
    }

    public function bump_decks_cache_version_on_meta_change($meta_id, $post_id, $meta_key = '', $meta_value = null) {
        unset($meta_id, $meta_value);

        $post_id = absint($post_id);
        if (!$post_id || get_post_type($post_id) !== 'hs_deck') {
            return;
        }

        if ((string) $meta_key !== '' && !$this->deck_meta_key_affects_feed_cache((string) $meta_key)) {
            return;
        }

        static $bumped_in_request = false;
        if ($bumped_in_request) {
            return;
        }
        $bumped_in_request = true;

        $this->bump_decks_cache_version($post_id);
    }

    private function deck_meta_key_affects_feed_cache($meta_key) {
        $deck_feed_meta_keys = array(
            '_deck_code',
            '_deck_streamer',
            '_deck_player',
            '_deck_source_url',
            '_deck_wins',
            '_deck_losses',
            '_deck_games',
            '_deck_winrate',
            '_deck_peak',
            '_deck_latest',
            '_deck_worst',
            '_deck_stats',
            '_deck_win_loss',
            '_thumbnail_id',
        );

        return in_array($meta_key, array_merge($deck_feed_meta_keys, array(
            '_custom_tags',
            '_dust_cost',
            '_rank_proof',
            '_hide_from_feed',
            '_hs_deck_archived',
            '_exclude_from_random',
            '_use_feed_shortcode',
            '_feed_shortcode',
            '_show_all_class_modes',
            '_show_announcement_single',
        )), true);
    }

    public static function custom_tags_to_array($raw_tags) {
        if (is_array($raw_tags)) {
            $raw_tags = implode(',', array_map('strval', $raw_tags));
        }

        $raw_tags = wp_unslash((string) $raw_tags);
        $raw_tags = str_replace(array("\r\n", "\r", "\n", ';'), ',', $raw_tags);
        $parts = explode(',', $raw_tags);
        $tags = array();
        $seen = array();

        foreach ($parts as $part) {
            $tag = trim(wp_strip_all_tags((string) $part));
            $tag = preg_replace('/\s+/', ' ', $tag);
            if ($tag === '') {
                continue;
            }

            $key = hs_mb_lower($tag);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $tags[] = $tag;
        }

        return $tags;
    }

    public static function normalize_custom_tags($raw_tags) {
        return implode(', ', self::custom_tags_to_array($raw_tags));
    }

    public function sync_filter_taxonomies($post_id) {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        self::sync_deck_filter_taxonomies($post_id);
    }

    public static function sync_deck_filter_taxonomies($post_id) {
        $post_id = absint($post_id);
        if (!$post_id || get_post_type($post_id) !== 'hs_deck') {
            return;
        }

        $streamer_terms = array();
        $streamer = trim((string) get_post_meta($post_id, '_deck_streamer', true));
        $player = trim((string) get_post_meta($post_id, '_deck_player', true));
        if ($streamer !== '') {
            $streamer_terms[] = $streamer;
        }
        if ($player !== '' && hs_mb_lower($player) !== hs_mb_lower($streamer)) {
            $streamer_terms[] = $player;
        }
        wp_set_object_terms($post_id, $streamer_terms, 'deck_streamer', false);

        $tag_terms = self::custom_tags_to_array(get_post_meta($post_id, '_custom_tags', true));
        wp_set_object_terms($post_id, $tag_terms, 'deck_custom_tag', false);

        $source_terms = array();
        $source_url = trim((string) get_post_meta($post_id, '_deck_source_url', true));
        if ($source_url !== '') {
            $host = wp_parse_url($source_url, PHP_URL_HOST);
            $host = is_string($host) ? preg_replace('/^www\./', '', hs_mb_lower($host)) : '';
            if ($host !== '') {
                $source_terms[] = $host;
            }
        }
        wp_set_object_terms($post_id, $source_terms, 'deck_source', false);
    }

    public static function sync_existing_filter_taxonomies($limit = 5000) {
        $limit = max(1, absint($limit));
        $post_ids = get_posts(array(
            'post_type' => 'hs_deck',
            'post_status' => array('publish', 'private', 'draft', 'pending', 'future'),
            'posts_per_page' => $limit,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'DESC',
            'no_found_rows' => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
            'ignore_sticky_posts' => true,
        ));

        foreach ($post_ids as $post_id) {
            self::sync_deck_filter_taxonomies($post_id);
        }
    }

    private function display_token_key($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = str_replace(array('ё', 'Ё'), array('е', 'е'), hs_mb_lower($value));
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '', $value);
        return is_string($value) ? $value : '';
    }

    private function is_unknown_deck_author($value) {
        $key = $this->display_token_key($value);
        return in_array($key, array('unknown', 'unk', 'none', 'noauthor'), true);
    }

    private function get_upload_asset_url($relative_path) {
        $relative_path = ltrim((string) $relative_path, '/');
        if ($relative_path === '') {
            return '';
        }

        $content_relative_path = 'uploads/' . $relative_path;
        $absolute_path = trailingslashit(WP_CONTENT_DIR) . $content_relative_path;
        if (file_exists($absolute_path)) {
            return set_url_scheme(content_url('/' . $content_relative_path), 'https');
        }

        if (substr($relative_path, -5) === '.webp') {
            $fallback_relative_path = substr($relative_path, 0, -5);
            $fallback_absolute_path = trailingslashit(WP_CONTENT_DIR) . 'uploads/' . $fallback_relative_path;
            if (file_exists($fallback_absolute_path)) {
                return set_url_scheme(content_url('/uploads/' . $fallback_relative_path), 'https');
            }
        }

        return '';
    }

    private function get_deck_class_icon_url(array $class_slugs, array $class_names) {
        $icons = array(
            'чернокнижник' => '2026/01/heroes_warlock_icon.png.webp',
            'chernoknizhnik' => '2026/01/heroes_warlock_icon.png.webp',
            'warlock' => '2026/01/heroes_warlock_icon.png.webp',
            'demonolog' => '2026/01/heroes_warlock_icon.png.webp',
            'маг' => '2026/01/heroes_mage_icon.png.webp',
            'mag' => '2026/01/heroes_mage_icon.png.webp',
            'mage' => '2026/01/heroes_mage_icon.png.webp',
            'друид' => '2026/01/heroes_druid_icon.png.webp',
            'druid' => '2026/01/heroes_druid_icon.png.webp',
            'паладин' => '2026/01/heroes_paladin_icon.png.webp',
            'paladin' => '2026/01/heroes_paladin_icon.png.webp',
            'воин' => '2026/01/heroes_warrior_icon.png.webp',
            'voin' => '2026/01/heroes_warrior_icon.png.webp',
            'warrior' => '2026/01/heroes_warrior_icon.png.webp',
            'шаман' => '2026/01/heroes_shaman_icon.png.webp',
            'shaman' => '2026/01/heroes_shaman_icon.png.webp',
            'разбойник' => '2026/01/heroes_rogue_icon.png.webp',
            'razbojnik' => '2026/01/heroes_rogue_icon.png.webp',
            'rogue' => '2026/01/heroes_rogue_icon.png.webp',
            'жрец' => '2026/01/heroes_priest_icon.png.webp',
            'zhrec' => '2026/01/heroes_priest_icon.png.webp',
            'priest' => '2026/01/heroes_priest_icon.png.webp',
            'охотник' => '2026/01/heroes_hunter_icon.png.webp',
            'oxotnik' => '2026/01/heroes_hunter_icon.png.webp',
            'hunter' => '2026/01/heroes_hunter_icon.png.webp',
            'охотникнадемонов' => '2026/01/icon_heroes_dh.png.webp',
            'oxotniknademonov' => '2026/01/icon_heroes_dh.png.webp',
            'demonhunter' => '2026/01/icon_heroes_dh.png.webp',
            'demonhunterclass' => '2026/01/icon_heroes_dh.png.webp',
            'dh' => '2026/01/icon_heroes_dh.png.webp',
            'рыцарьсмерти' => '2022/11/heroes_dk_icon.png',
            'deathknight' => '2022/11/heroes_dk_icon.png',
            'dk' => '2022/11/heroes_dk_icon.png',
        );

        foreach (array_merge($class_slugs, $class_names) as $class_label) {
            $key = $this->display_token_key($class_label);
            if ($key !== '' && isset($icons[$key])) {
                return $this->get_upload_asset_url($icons[$key]);
            }
        }

        return '';
    }

    private function filter_duplicate_visible_tags(array $tags, array $already_visible_labels) {
        $seen = array();
        foreach ($already_visible_labels as $label) {
            $key = $this->display_token_key($label);
            if ($key !== '') {
                $seen[$key] = true;
            }
        }

        $filtered = array();
        foreach ($tags as $tag) {
            $key = $this->display_token_key($tag);
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $filtered[] = $tag;
        }

        return $filtered;
    }
    
    /**
     * Обработка принудительного сброса rewrite rules
     */
    public function handle_flush_rewrite_rules() {
        if (!current_user_can(Unified_HS_Capabilities::CAP_MANAGE_SETTINGS)) {
            wp_die('Недостаточно прав для выполнения этого действия.');
        }
        
        check_admin_referer('hs_decks_flush_rewrite_rules');
        
        flush_rewrite_rules();
        
        wp_redirect(add_query_arg(array(
            'page' => 'hs-decks-help',
            'flushed' => '1'
        ), admin_url('admin.php')));
        exit;
    }
    
    /**
     * Принудительный сброс rewrite rules
     */
    public function force_flush_rewrite_rules() {
        // Проверяем, нужно ли сбросить правила
        $needs_flush = get_option('hs_decks_rewrite_flush_needed', false);
        
        if ($needs_flush) {
            flush_rewrite_rules(false);
            delete_option('hs_decks_rewrite_flush_needed');
        }
    }
    
    /**
     * Автоматическое связывание с архетипом при сохранении колоды
     */
    public function register_post_type() {
        $labels = array(
            'name' => 'Колоды',
            'singular_name' => 'Колода',
            'add_new' => 'Добавить колоду',
            'add_new_item' => 'Добавить новую колоду',
            'edit_item' => 'Редактировать колоду',
            'new_item' => 'Новая колода',
            'view_item' => 'Просмотреть колоду',
            'search_items' => 'Искать колоды',
            'not_found' => 'Колоды не найдены',
            'menu_name' => 'Колоды HS'
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-images-alt2',
            'supports' => array('title', 'thumbnail'),
            'show_in_rest' => true,
            'show_in_menu' => false, // Скрываем отдельное меню, используем подменю в "База колод"
            'rewrite' => array(
                'slug' => 'deck',
                'with_front' => false,
                'feeds' => true,
                'pages' => true,
            ),
            'publicly_queryable' => true,
            'query_var' => true,
            'capability_type' => array('hs_deck', 'hs_decks'),
            'capabilities' => Unified_HS_Capabilities::post_type_capabilities(),
            'map_meta_cap' => true,
            'hierarchical' => false,
        );
        
        register_post_type('hs_deck', $args);
        
        // Устанавливаем флаг для сброса rewrite rules при следующей загрузке админки
        // Это нужно, если post type был изменен
        $current_version = get_option('hs_decks_post_type_version', '0');
        $new_version = '1.2'; // Увеличиваем версию при изменении параметров
        
        if ($current_version !== $new_version) {
            update_option('hs_decks_rewrite_flush_needed', true);
            update_option('hs_decks_post_type_version', $new_version);
        }
    }
    
    public function register_taxonomies() {
        register_taxonomy('deck_class', 'hs_deck', array(
            'label' => 'Класс',
            'hierarchical' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'capabilities' => Unified_HS_Capabilities::taxonomy_capabilities(),
        ));
        
        register_taxonomy('deck_mode', 'hs_deck', array(
            'label' => 'Режим',
            'hierarchical' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'capabilities' => Unified_HS_Capabilities::taxonomy_capabilities(),
        ));

        register_taxonomy('deck_archetype', 'hs_deck', array(
            'label' => 'Архетип',
            'hierarchical' => false,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'deck-archetype'),
            'capabilities' => Unified_HS_Capabilities::taxonomy_capabilities(),
        ));

        register_taxonomy('deck_streamer', 'hs_deck', array(
            'label' => 'Стример',
            'hierarchical' => false,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => false,
            'capabilities' => Unified_HS_Capabilities::taxonomy_capabilities(),
        ));

        register_taxonomy('deck_custom_tag', 'hs_deck', array(
            'label' => 'Теги колоды',
            'hierarchical' => false,
            'show_admin_column' => false,
            'show_in_rest' => true,
            'rewrite' => false,
            'capabilities' => Unified_HS_Capabilities::taxonomy_capabilities(),
        ));

        register_taxonomy('deck_source', 'hs_deck', array(
            'label' => 'Источник колоды',
            'hierarchical' => false,
            'show_admin_column' => false,
            'show_in_rest' => true,
            'rewrite' => false,
            'capabilities' => Unified_HS_Capabilities::taxonomy_capabilities(),
        ));
    }

    public function register_rest_meta() {
        // REST meta writes are limited to users who can edit the specific deck.
        $auth_callback = function($allowed, $meta_key, $post_id, $user_id, $cap, $caps) {
            // Allow if user can edit the specific post
            return user_can($user_id, 'edit_post', $post_id);
        };
        
        // Sanitize callback for strings
        $sanitize_string = function($value) {
            return sanitize_text_field($value);
        };

        $sanitize_tags = function($value) {
            return self::normalize_custom_tags($value);
        };
        
        // Sanitize callback for deck code (preserve special chars)
        $sanitize_deck_code = function($value) {
            return sanitize_textarea_field($value);
        };
        
        // Sanitize callback for integers
        $sanitize_int = function($value) {
            return absint($value);
        };

        $sanitize_bool_flag = function($value) {
            return rest_sanitize_boolean($value) ? '1' : '0';
        };

        $sanitize_winrate = function($value) {
            return self::sanitize_winrate_meta($value);
        };

        register_post_meta('hs_deck', '_deck_code', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => $auth_callback,
            'sanitize_callback' => $sanitize_deck_code,
        ));
        
        register_post_meta('hs_deck', '_dust_cost', array(
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => $auth_callback,
            'sanitize_callback' => $sanitize_int,
        ));
        
        register_post_meta('hs_deck', '_custom_tags', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => $auth_callback,
            'sanitize_callback' => $sanitize_tags,
        ));
        
        register_post_meta('hs_deck', '_deck_streamer', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => $auth_callback,
            'sanitize_callback' => $sanitize_string,
        ));
        
        register_post_meta('hs_deck', '_deck_player', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => $auth_callback,
            'sanitize_callback' => $sanitize_string,
        ));
        
        register_post_meta('hs_deck', '_deck_source_url', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => $auth_callback,
            'sanitize_callback' => 'esc_url_raw',
        ));
        
        // Статистика побед и поражений
        register_post_meta('hs_deck', '_deck_wins', array(
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => $auth_callback,
            'sanitize_callback' => $sanitize_int,
        ));
        
        register_post_meta('hs_deck', '_deck_losses', array(
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => $auth_callback,
            'sanitize_callback' => $sanitize_int,
        ));

        register_post_meta('hs_deck', '_deck_games', array(
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => $auth_callback,
            'sanitize_callback' => $sanitize_int,
        ));

        register_post_meta('hs_deck', '_deck_winrate', array(
            'type' => 'number',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => $auth_callback,
            'sanitize_callback' => $sanitize_winrate,
        ));
        
        // Ранги (Peak, Latest, Worst)
        register_post_meta('hs_deck', '_deck_peak', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => $auth_callback,
            'sanitize_callback' => $sanitize_string,
        ));
        
        register_post_meta('hs_deck', '_deck_latest', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => $auth_callback,
            'sanitize_callback' => $sanitize_string,
        ));
        
        register_post_meta('hs_deck', '_deck_worst', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => $auth_callback,
            'sanitize_callback' => $sanitize_string,
        ));

        register_post_meta('hs_deck', '_hide_from_feed', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => $auth_callback,
            'sanitize_callback' => $sanitize_bool_flag,
        ));

        register_post_meta('hs_deck', '_exclude_from_random', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => $auth_callback,
            'sanitize_callback' => $sanitize_bool_flag,
        ));

        register_post_meta('hs_deck', '_hs_deck_archived', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => $auth_callback,
            'sanitize_callback' => $sanitize_bool_flag,
        ));

        register_post_meta('hs_deck', '_show_all_class_modes', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => $auth_callback,
            'sanitize_callback' => $sanitize_bool_flag,
        ));
    }

    public static function sanitize_winrate_meta($value) {
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
    
    public function add_meta_boxes() {
        add_meta_box(
            'deck_details',
            'Информация о колоде',
            array($this, 'render_meta_box'),
            'hs_deck',
            'normal',
            'high'
        );
        
    }
    
    public function render_meta_box($post) {
        wp_nonce_field('hs_deck_nonce', 'hs_deck_nonce');
        
        $deck_code = get_post_meta($post->ID, '_deck_code', true);
        $rank_proof = get_post_meta($post->ID, '_rank_proof', true);
        $custom_tags = get_post_meta($post->ID, '_custom_tags', true);
        $dust_cost = get_post_meta($post->ID, '_dust_cost', true);
        $deck_streamer = get_post_meta($post->ID, '_deck_streamer', true);
        $deck_player = get_post_meta($post->ID, '_deck_player', true);
        $deck_source_url = get_post_meta($post->ID, '_deck_source_url', true);
        $deck_games = get_post_meta($post->ID, '_deck_games', true);
        $deck_winrate = get_post_meta($post->ID, '_deck_winrate', true);
        $show_proof = get_post_meta($post->ID, '_show_proof_single', true);
        $hide_from_feed = get_post_meta($post->ID, '_hide_from_feed', true);
        // Для новых колод (auto-draft) по умолчанию включаем скрытие из ленты.
        // Существующие записи не затрагиваем — их значение читается как есть.
        if ($hide_from_feed === '' && isset($post->post_status) && $post->post_status === 'auto-draft') {
            $hide_from_feed = '1';
        }
        $use_feed_shortcode = get_post_meta($post->ID, '_use_feed_shortcode', true);
        $feed_shortcode = get_post_meta($post->ID, '_feed_shortcode', true);
        $show_all_class_modes = get_post_meta($post->ID, '_show_all_class_modes', true);
        $exclude_from_random = get_post_meta($post->ID, '_exclude_from_random', true);
        $archived = get_post_meta($post->ID, '_hs_deck_archived', true);
        $show_announcement_single = get_post_meta($post->ID, '_show_announcement_single', true);
        $likes = get_post_meta($post->ID, '_deck_likes', true) ?: 0;
        $dislikes = get_post_meta($post->ID, '_deck_dislikes', true) ?: 0;
        ?>
        <table class="form-table">
            <tr>
                <th><label>Шорткод для этой колоды</label></th>
                <td>
                    <input type="text" value='[hs_deck id="<?php echo $post->ID; ?>"]' readonly style="width:100%; background:#f0f0f0; padding:8px; border-radius:4px; font-family:monospace;">
                    <p class="description">Скопируйте этот шорткод для вставки колоды в статью или на страницу</p>
                </td>
            </tr>
            <tr>
                <th><label for="hide_from_feed">Не публиковать в ленту колод</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="hide_from_feed" name="hide_from_feed" value="1" <?php checked($hide_from_feed, '1'); ?>>
                        Скрыть эту колоду из ленты всех колод
                    </label>
                    <p class="description">Если включено, колода не будет отображаться в шортkoде [hs_decks], но будет доступна через прямой шортkод [hs_deck]</p>
                </td>
            </tr>
            <tr>
                <th><label for="hs_deck_archived">Архив плагина</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="hs_deck_archived" name="hs_deck_archived" value="1" <?php checked($archived, '1'); ?>>
                        Переместить колоду в архив
                    </label>
                    <p class="description">Архивные колоды не показываются в ленте, случайных подборках и публичном одиночном шорткоде. Редакторы могут восстановить их позже.</p>
                </td>
            </tr>
            <tr>
                <th><label for="show_proof_single">Показывать доказательство</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="show_proof_single" name="show_proof_single" value="1" <?php checked($show_proof, '1'); ?>>
                        Показывать кнопку "Доказательство Легенды" в одиночном шортkoде
                    </label>
                    <p class="description">Если включено, кнопка доказательства будет отображаться при использовании шортkода [hs_deck]</p>
                </td>
            </tr>
            <tr>
                <th><label for="deck_code">Код колоды</label></th>
                <td>
                    <textarea id="deck_code" name="deck_code" rows="3" style="width:100%"><?php echo esc_textarea($deck_code); ?></textarea>
                    <p class="description">Вставьте код колоды из Hearthstone. При сохранении класс и режим будут заполнены автоматически из deckstring.</p>
                </td>
            </tr>
            <tr>
                <th><label for="dust_cost">Стоимость в пыли</label></th>
                <td>
                    <input type="number" id="dust_cost" name="dust_cost" value="<?php echo esc_attr($dust_cost); ?>" min="0" step="20" style="width:200px;">
                    <p class="description">Укажите стоимость колоды в пыли (например: 3200, 8000, 12400)</p>
                </td>
            </tr>
            <tr>
                <th><label for="deck_games">Количество игр</label></th>
                <td>
                    <input type="number" id="deck_games" name="deck_games" value="<?php echo esc_attr($deck_games); ?>" min="0" step="1" style="width:200px;">
                    <p class="description">Общее число сыгранных игр. Если оставить пустым, плагин попробует посчитать игры из побед и поражений.</p>
                </td>
            </tr>
            <tr>
                <th><label for="deck_winrate">Winrate (%)</label></th>
                <td>
                    <input type="number" id="deck_winrate" name="deck_winrate" value="<?php echo esc_attr($deck_winrate); ?>" min="0" max="100" step="0.1" style="width:200px;">
                    <p class="description">Процент побед от 0 до 100. Если оставить пустым, плагин посчитает winrate из побед и поражений.</p>
                </td>
            </tr>
            <tr>
                <th><label for="custom_tags">Теги (через запятую)</label></th>
                <td>
                    <input type="text" id="custom_tags" name="custom_tags" value="<?php echo esc_attr($custom_tags); ?>" style="width:100%">
                    <p class="description">Например: агро, контроль, комбо, бюджетная, мета</p>
                </td>
            </tr>
            <tr>
                <th><label for="deck_streamer">Стример / автор</label></th>
                <td>
                    <input type="text" id="deck_streamer" name="deck_streamer" value="<?php echo esc_attr($deck_streamer); ?>" style="width:100%">
                    <p class="description">Имя стримера, автора сборки или канала-источника.</p>
                </td>
            </tr>
            <tr>
                <th><label for="deck_player">Игрок</label></th>
                <td>
                    <input type="text" id="deck_player" name="deck_player" value="<?php echo esc_attr($deck_player); ?>" style="width:100%">
                    <p class="description">Ник игрока, если отличается от автора или стримера.</p>
                </td>
            </tr>
            <tr>
                <th><label for="deck_source_url">Ссылка на источник</label></th>
                <td>
                    <input type="url" id="deck_source_url" name="deck_source_url" value="<?php echo esc_attr($deck_source_url); ?>" style="width:100%" placeholder="https://">
                    <p class="description">Опциональная ссылка на пост, видео, твит или страницу с колодой.</p>
                </td>
            </tr>
            <tr>
                <th><label for="rank_proof">Доказательство ранга</label></th>
                <td>
                    <?php if ($rank_proof): ?>
                        <img src="<?php echo esc_url($rank_proof); ?>" style="max-width:300px;display:block;margin-bottom:10px;">
                    <?php endif; ?>
                    <input type="text" id="rank_proof" name="rank_proof" value="<?php echo esc_attr($rank_proof); ?>" style="width:70%">
                    <button type="button" class="button" id="upload_rank_proof">Загрузить изображение</button>
                    <p class="description">Загрузите скриншот доказательства ранга</p>
                </td>
            </tr>
            <tr>
                <th><label for="use_feed_shortcode">Шорткод вместо карточки</label></th>
                <td>
                    <label style="display:block;margin-bottom:8px;">
                        <input type="checkbox" id="use_feed_shortcode" name="use_feed_shortcode" value="1" <?php checked($use_feed_shortcode, '1'); ?>>
                        В ленте колод выводить шорткод вместо карточки этой колоды
                    </label>
                    <textarea id="feed_shortcode" name="feed_shortcode" rows="4" style="width:100%;" placeholder="[my_shortcode]"><?php echo esc_textarea($feed_shortcode); ?></textarea>
                    <p class="description">Содержимое будет обработано как шорткод и показано только в шорткоде [hs_decks]. В одиночной странице колоды карточка останется стандартной.</p>
                </td>
            </tr>
            <tr>
                <th><label for="show_all_class_modes">Отображение во всех классах/режимах</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="show_all_class_modes" name="show_all_class_modes" value="1" <?php checked($show_all_class_modes, '1'); ?>>
                        Показывать эту колоду при выборе любого класса и режима в фильтрах
                    </label>
                    <p class="description">Полезно для общих гайдов: колода будет попадать во все фильтры без привязки к конкретному классу и режиму.</p>
                </td>
            </tr>
            <tr>
                <th><label for="exclude_from_random">Исключить из случайных подборок</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="exclude_from_random" name="exclude_from_random" value="1" <?php checked($exclude_from_random, '1'); ?>>
                        Не показывать эту колоду в шорткодах с параметром random_row и [hs_decks_random]
                    </label>
                    <p class="description">Колода останется в основной ленте [hs_decks], но не попадёт в виджеты со случайными колодами.</p>
                </td>
            </tr>
            <tr>
                <th><label for="show_announcement_single">Показывать объявление в шорткоде колоды</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="show_announcement_single" name="show_announcement_single" value="1" <?php checked($show_announcement_single, '1'); ?>>
                        Отображать глобальное объявление в [hs_deck id="..."] и на странице записи
                    </label>
                    <p class="description">По умолчанию объявление показывается только в основной ленте. Отметьте, если нужно показать его для этой колоды отдельно.</p>
                </td>
            </tr>
            <tr>
                <th>Голосование</th>
                <td>
                    <p>👍 Лайков: <strong><?php echo $likes; ?></strong> | 👎 Дизлайков: <strong><?php echo $dislikes; ?></strong></p>
                </td>
            </tr>
        </table>
        
        <script>
        jQuery(document).ready(function($){
            var mediaUploader;
            $('#upload_rank_proof').click(function(e) {
                e.preventDefault();
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                mediaUploader = wp.media({
                    title: 'Выберите изображение',
                    button: { text: 'Использовать это изображение' },
                    multiple: false
                });
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#rank_proof').val(attachment.url);
                });
                mediaUploader.open();
            });
        });
        </script>
        <?php
    }
    /**
     * Подключение скриптов для админки
     */
    public function enqueue_admin_scripts($hook) {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== 'hs_deck') {
            return;
        }

        $ver = defined('UNIFIED_HS_PLUGINS_VERSION') ? UNIFIED_HS_PLUGINS_VERSION : false;
        wp_enqueue_style(
            'hs-decks-admin',
            UNIFIED_HS_PLUGINS_URL . 'assets/css/hs-decks-admin.css',
            array(),
            $ver
        );

        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_media();
            // В админке WordPress ajaxurl доступен автоматически, но на всякий случай добавляем
            wp_add_inline_script('jquery', 'var ajaxurl = ajaxurl || "' . admin_url('admin-ajax.php') . '";', 'before');
        }
    }
    
    public function save_meta_boxes($post_id) {
        if (!isset($_POST['hs_deck_nonce']) || !wp_verify_nonce($_POST['hs_deck_nonce'], 'hs_deck_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'hs_deck') {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['deck_code'])) {
            $deck_code = sanitize_textarea_field(wp_unslash($_POST['deck_code']));
            update_post_meta($post_id, '_deck_code', $deck_code);
            if ($deck_code !== '' && class_exists('Unified_HS_Deckstring_Helper')) {
                Unified_HS_Deckstring_Helper::assign_terms_from_code($post_id, $deck_code);
            }
        }
        
        if (isset($_POST['dust_cost'])) {
            update_post_meta($post_id, '_dust_cost', intval($_POST['dust_cost']));
        }

        if (isset($_POST['deck_games'])) {
            $deck_games = trim((string) wp_unslash($_POST['deck_games']));
            if ($deck_games === '') {
                delete_post_meta($post_id, '_deck_games');
            } else {
                update_post_meta($post_id, '_deck_games', absint($deck_games));
            }
        }

        if (isset($_POST['deck_winrate'])) {
            $deck_winrate = trim((string) wp_unslash($_POST['deck_winrate']));
            if ($deck_winrate === '') {
                delete_post_meta($post_id, '_deck_winrate');
            } else {
                update_post_meta($post_id, '_deck_winrate', self::sanitize_winrate_meta($deck_winrate));
            }
        }
        
        if (isset($_POST['custom_tags'])) {
            update_post_meta($post_id, '_custom_tags', self::normalize_custom_tags($_POST['custom_tags']));
        }

        if (isset($_POST['deck_streamer'])) {
            update_post_meta($post_id, '_deck_streamer', sanitize_text_field(wp_unslash($_POST['deck_streamer'])));
        }

        if (isset($_POST['deck_player'])) {
            update_post_meta($post_id, '_deck_player', sanitize_text_field(wp_unslash($_POST['deck_player'])));
        }

        if (isset($_POST['deck_source_url'])) {
            update_post_meta($post_id, '_deck_source_url', esc_url_raw(wp_unslash($_POST['deck_source_url'])));
        }
        
        if (isset($_POST['rank_proof'])) {
            update_post_meta($post_id, '_rank_proof', esc_url_raw($_POST['rank_proof']));
        }
        
        $show_proof = isset($_POST['show_proof_single']) ? '1' : '0';
        update_post_meta($post_id, '_show_proof_single', $show_proof);
        
        $hide_from_feed = isset($_POST['hide_from_feed']) ? '1' : '0';
        update_post_meta($post_id, '_hide_from_feed', $hide_from_feed);
        
        $use_feed_shortcode = isset($_POST['use_feed_shortcode']) ? '1' : '0';
        update_post_meta($post_id, '_use_feed_shortcode', $use_feed_shortcode);
        
        if (isset($_POST['feed_shortcode'])) {
            $shortcode_content = trim(wp_unslash($_POST['feed_shortcode']));
            if ($shortcode_content !== '') {
                update_post_meta($post_id, '_feed_shortcode', $shortcode_content);
            } else {
                delete_post_meta($post_id, '_feed_shortcode');
            }
        }
        
        $show_all_class_modes = isset($_POST['show_all_class_modes']) ? '1' : '0';
        update_post_meta($post_id, '_show_all_class_modes', $show_all_class_modes);
        
        $exclude_from_random = isset($_POST['exclude_from_random']) ? '1' : '0';
        update_post_meta($post_id, '_exclude_from_random', $exclude_from_random);

        $archived = isset($_POST['hs_deck_archived']) ? '1' : '0';
        update_post_meta($post_id, '_hs_deck_archived', $archived);
        
        $show_announcement_single = isset($_POST['show_announcement_single']) ? '1' : '0';
        update_post_meta($post_id, '_show_announcement_single', $show_announcement_single);
    }

    /**
     * Версия фронт-рендера/ассетов. Отдельна от UNIFIED_HS_PLUGINS_VERSION,
     * потому что версия плагина запускает DB-миграции через installer.
     */
    private function get_frontend_version($fallback = false) {
        $version = '';
        if (defined('UNIFIED_HS_ASSETS_VERSION')) {
            $version = (string) UNIFIED_HS_ASSETS_VERSION;
        } elseif (defined('UNIFIED_HS_PLUGINS_VERSION')) {
            $version = (string) UNIFIED_HS_PLUGINS_VERSION;
        }

        $render_stamp = file_exists(__FILE__) ? (string) filemtime(__FILE__) : '';
        if ($version !== '') {
            return $render_stamp !== '' ? $version . '-' . $render_stamp : $version;
        }

        if ($render_stamp !== '') {
            return $render_stamp;
        }

        return $fallback;
    }

    /**
     * Регистрация фронт-ассетов. Сами enqueue вызываются позже из шорткодов,
     * чтобы CSS/JS грузились ТОЛЬКО на страницах, где есть [hs_decks*] / [hs_deck*].
     * wp_enqueue_*() из shortcode-callback'а корректно отрабатывает: WP выведет
     * зарегистрированные ассеты в wp_head/wp_footer.
     */
    public function enqueue_scripts() {
        $ver = $this->get_frontend_version(false);
        $front_css_ver = $ver;
        $front_css_file = defined('UNIFIED_HS_PLUGINS_DIR') ? UNIFIED_HS_PLUGINS_DIR . 'assets/css/hs-decks-front.css' : '';
        $front_js_ver = $ver;
        $front_js_file = defined('UNIFIED_HS_PLUGINS_DIR') ? UNIFIED_HS_PLUGINS_DIR . 'assets/js/hs-decks-front.js' : '';

        if ($front_css_file && file_exists($front_css_file)) {
            $front_css_ver = ($ver ? $ver : '1') . '-' . filemtime($front_css_file);
        }

        if ($front_js_file && file_exists($front_js_file)) {
            $front_js_ver = ($ver ? $ver : '1') . '-' . filemtime($front_js_file);
        }

        wp_register_style(
            'hs-decks-front',
            UNIFIED_HS_PLUGINS_URL . 'assets/css/hs-decks-front.css',
            array(),
            $front_css_ver
        );

        wp_register_script(
            'hs-decks-front',
            UNIFIED_HS_PLUGINS_URL . 'assets/js/hs-decks-front.js',
            array('jquery'),
            $front_js_ver,
            true // в футер
        );

        wp_localize_script('hs-decks-front', 'hsDecks', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('hs_decks_nonce'),
            'adImpressionAction' => 'hs_decks_ad_impression',
            'filterAction' => 'hs_decks_filter',
            'archetypesAction' => 'hs_deck_archetypes_page',
            'eventAction' => 'hs_decks_track_event',
            'voteCountsAction' => 'hs_decks_vote_counts',
        ));
    }

    /**
     * Гарантирует, что фронт-ассеты подключены. Идемпотентно — wp_enqueue_*
     * безопасно вызывать несколько раз. Используется во всех шорткодах.
     */
    private function enqueue_front_assets() {
        // Регистрируем на лету при первом реальном shortcode-render. Это также
        // покрывает do_shortcode() в admin-ajax/REST-контексте.
        if (!wp_style_is('hs-decks-front', 'registered')) {
            $this->enqueue_scripts();
        }
        wp_enqueue_style('hs-decks-front');
        wp_enqueue_script('hs-decks-front');

        // Один modal на страницу. Он выводится непосредственно в footer/body,
        // поэтому не наследует overflow/visibility от spoiler-контейнеров.
        if (!has_action('wp_footer', array($this, 'render_image_modal'))) {
            add_action('wp_footer', array($this, 'render_image_modal'), 5);
        }
    }

    /**
     * Единый lightbox для всех deck-shortcode на странице.
     * JS также умеет создать его лениво для AJAX/REST-контекстов без wp_footer.
     */
    public function render_image_modal() {
        ?>
        <div id="image-modal" class="hs-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Просмотр изображения колоды">
            <button type="button" class="hs-modal-close" aria-label="Закрыть просмотр изображения">
                <span aria-hidden="true">&times;</span>
            </button>
            <img class="hs-modal-content" id="modal-image" alt="">
        </div>
        <?php
    }

    public function settings_capability($capability = '') {
        return Unified_HS_Capabilities::CAP_MANAGE_SETTINGS;
    }

    public function announcement_capability($capability = '') {
        return Unified_HS_Capabilities::CAP_MANAGE_ANNOUNCEMENTS;
    }

    public function layout_capability($capability = '') {
        return Unified_HS_Capabilities::CAP_MANAGE_LAYOUT;
    }
    
    public function add_help_page() {
        add_submenu_page(
            'edit.php?post_type=hs_deck',
            'Помощь',
            'Помощь',
            Unified_HS_Capabilities::CAP_MANAGE_SETTINGS,
            'hs-decks-help',
            array($this, 'render_help_page')
        );
    }
    
    public function add_about_page() {
        add_submenu_page(
            'edit.php?post_type=hs_deck',
            'О плагине',
            'О плагине',
            Unified_HS_Capabilities::CAP_VIEW_SHORTCODES,
            'hs-decks-about',
            array($this, 'render_about_page')
        );
    }
    
    public function add_announcement_page() {
        add_submenu_page(
            'edit.php?post_type=hs_deck',
            'Глобальное объявление',
            'Объявление',
            Unified_HS_Capabilities::CAP_MANAGE_ANNOUNCEMENTS,
            'hs-decks-announcement',
            array($this, 'render_announcement_page')
        );
    }
    
    public function add_layout_settings_page() {
        add_submenu_page(
            'edit.php?post_type=hs_deck',
            'Конфигурация колод',
            'Конфигурация',
            Unified_HS_Capabilities::CAP_MANAGE_LAYOUT,
            'hs-decks-layout',
            array($this, 'render_layout_settings_page')
        );
    }
    
    public function add_tags_manager_page() {
        add_submenu_page(
            'edit.php?post_type=hs_deck',
            'Управление тегами колод',
            'Теги',
            Unified_HS_Capabilities::CAP_MANAGE_TAGS,
            'hs-decks-tags',
            array($this, 'render_tags_manager_page')
        );
    }
    
    public function register_help_settings() {
        register_setting('hs_decks_help_settings', 'hs_decks_help_text');
    }
    
    public function register_plugin_settings() {
        register_setting('hs_decks_plugin_settings', 'hs_decks_per_page', array(
            'type' => 'integer',
            'default' => self::DEFAULT_PER_PAGE,
            'sanitize_callback' => 'absint'
        ));
    }
    
    public function register_announcement_settings() {
        register_setting('hs_decks_announcement_settings', 'hs_decks_announcement_text', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'wp_kses_post'
        ));
        register_setting('hs_decks_announcement_settings', 'hs_decks_announcement_image', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'esc_url_raw'
        ));
        register_setting('hs_decks_announcement_settings', 'hs_decks_announcement_button_text', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('hs_decks_announcement_settings', 'hs_decks_announcement_button_url', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'esc_url_raw'
        ));
    }
    
    public function register_layout_settings() {
        register_setting('hs_decks_layout_settings', 'hs_decks_single_top_content', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'wp_kses_post'
        ));
        register_setting('hs_decks_layout_settings', 'hs_decks_single_bottom_content', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'wp_kses_post'
        ));
    }
    
    public function render_help_page() {
        if (!current_user_can(Unified_HS_Capabilities::CAP_MANAGE_SETTINGS)) {
            wp_die(esc_html__('Недостаточно прав', 'unified-hs-plugins'));
        }

        // Показываем уведомление об успешном сбросе
        if (isset($_GET['flushed']) && $_GET['flushed'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>Правила постоянных ссылок успешно обновлены!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Настройки помощи для колод</h1>
            <form method="post" action="options.php">
                <?php settings_fields('hs_decks_help_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="hs_decks_help_text">Текст помощи</label>
                        </th>
                        <td>
                            <?php
                            $help_text = get_option('hs_decks_help_text', 'Используйте фильтры для поиска колод по классу, режиму или тегам. Нажмите на кнопку "Скопировать код" чтобы получить код колоды для игры.');
                            wp_editor($help_text, 'hs_decks_help_text', array(
                                'textarea_rows' => 10,
                                'media_buttons' => false,
                            ));
                            ?>
                            <p class="description">Этот текст будет отображаться при наведении на значок "?" в правом верхнем углу блока с колодами</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Сохранить настройки'); ?>
            </form>
            
            <hr style="margin: 30px 0;">
            
            <h2>Исправление постоянных ссылок</h2>
            <p>Если ссылки на колоды не работают (ошибка 404), нажмите кнопку ниже для обновления правил постоянных ссылок:</p>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('hs_decks_flush_rewrite_rules'); ?>
                <input type="hidden" name="action" value="hs_decks_flush_rewrite_rules">
                <?php submit_button('Обновить правила постоянных ссылок', 'secondary'); ?>
            </form>
            <p class="description">Также можно зайти в <a href="<?php echo admin_url('options-permalink.php'); ?>">Настройки → Постоянные ссылки</a> и просто нажать "Сохранить изменения"</p>
            
            <hr style="margin: 40px 0;">
            
            <h2>Настройки отображения</h2>
            <form method="post" action="options.php">
                <?php settings_fields('hs_decks_plugin_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="hs_decks_per_page">Колод на странице</label>
                        </th>
                        <td>
                            <input type="number" id="hs_decks_per_page" name="hs_decks_per_page"
                                   value="<?php echo esc_attr(get_option('hs_decks_per_page', self::DEFAULT_PER_PAGE)); ?>"
                                   min="1" max="100" step="1" style="width: 100px;">
                            <p class="description">Количество колод, отображаемых на одной странице (по умолчанию: 13)</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Сохранить настройки'); ?>
            </form>
        </div>
        <?php
    }
    
    public function render_about_page() {
        if (!current_user_can(Unified_HS_Capabilities::CAP_VIEW_SHORTCODES)) {
            wp_die(esc_html__('Недостаточно прав', 'unified-hs-plugins'));
        }

        ?>
        <div class="wrap">
            <h1>О плагине Hearthstone Decks Manager</h1>
            
            <div style="background: #fff; padding: 20px; border-left: 4px solid #2271b1; margin: 20px 0;">
                <h2>Шорткод ленты</h2>
                <p><strong>Базовая лента с фильтрами (включая объявления и кнопку помощи):</strong></p>
                <input type="text" value="[hs_decks]" readonly onclick="this.select()" 
                       style="width: 100%; padding: 10px; font-family: monospace; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; margin-bottom: 10px;">
                
                <p><strong>Фиксированное количество карточек (с пагинацией):</strong></p>
                <input type="text" value='[hs_decks deck="6"]' readonly onclick="this.select()" 
                       style="width: 100%; padding: 10px; font-family: monospace; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; margin-bottom: 10px;">
                
                <p><strong>Гибкая витрина из случайных колод за 2 дня (независимый шорткод):</strong></p>
                <input type="text" value='[hs_decks_random count="2"]' readonly onclick="this.select()" 
                       style="width: 100%; padding: 10px; font-family: monospace; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; margin-bottom: 10px;">
                
                <p class="description">
                    <strong>deck</strong> — задаёт число карточек на странице с пагинацией.<br>
                    <strong>hs_decks_random</strong> — отдельный шорткод, выводящий указанное количество случайных колод за последние 48 часов. Учитывает атрибуты <code>class</code> и <code>time</code> по аналогии с основной лентой.
                </p>
            </div>
            
            <div style="background: #fff; padding: 20px; border-left: 4px solid #72aee6; margin: 20px 0;">
                <h2>Группы и одиночные колоды</h2>
                <p><strong>Группа:</strong></p>
                <input type="text" value='[hs_deck_group ids="1,2,3,4" columns="2"]' readonly onclick="this.select()" 
                       style="width: 100%; padding: 10px; font-family: monospace; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; margin-bottom: 10px;">
                <p class="description">IDs перечисляйте через запятую, параметр <code>columns</code> управляет числом карточек в ряд.</p>
                
                <p><strong>Одна колода:</strong></p>
                <input type="text" value='[hs_deck id="123"]' readonly onclick="this.select()" 
                       style="width: 100%; padding: 10px; font-family: monospace; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                <p class="description">ID можно скопировать прямо в редакторе колоды (вверху страницы).</p>
            </div>
            
            <div style="background: #fff; padding: 20px; border-left: 4px solid #d4af37; margin: 20px 0;">
                <h2>Гибкость ленты</h2>
                <ul style="line-height: 1.8; margin-left: 18px;">
                    <li><strong>Шорткоды внутри ленты:</strong> в карточке отметьте «Шорткод вместо карточки» — произвольный контент появится в `[hs_decks]`, но не затронет одиночные страницы.</li>
                    <li><strong>Глобальные блоки перед/после колод:</strong> страница «Конфигурация» добавляет рекламные шорткоды над и под каждой карточкой и на страницах записей.</li>
                    <li><strong>Глобальное объявление:</strong> страница «Объявление» позволяет вывести единый баннер (текст + изображение) во всех шорткодах.</li>
                    <li><strong>Исключение из случайных подборок:</strong> опция «Исключить из случайных» оставляет колоду только в основной ленте `[hs_decks]`.</li>
                    <li><strong>Отображение во всех классах/режимах:</strong> опция расширяет участие колоды во всех фильтрах.</li>
                    <li><strong>Автоматический код колоды:</strong> в одиночном посте колоды всегда выводятся карточка, кнопки и блок с кодом.</li>
                </ul>
            </div>
            
            <div style="background: #fff; padding: 20px; border-left: 4px solid #00a32a; margin: 20px 0;">
                <h2>Админ-инструменты</h2>
                <ul style="line-height: 1.8; margin-left: 18px;">
                    <li><strong>«Помощь»</strong> — текст тултипа для пользователей ленты.</li>
                    <li><strong>«Конфигурация»</strong> — глобальные блоки перед/после колод.</li>
                    <li><strong>«Объявление»</strong> — единый баннер, который отображается во всех шорткодах.</li>
                    <li><strong>«Теги»</strong> — просмотр и массовое удаление тегов из всех колод.</li>
                    <li><strong>Метабоксы колоды:</strong> настройка доказательства легенды, скрытия из ленты, замены карточки шорткодом, принудительного показа во всех классах/режимах и исключения из случайных подборок.</li>
                </ul>
            </div>
            
            <div style="background: #fff; padding: 20px; border-left: 4px solid #d63638; margin: 20px 0;">
                <h2>Памятка по созданию колоды</h2>
                <ol style="line-height: 2;">
                    <li>Создайте запись «Колода HS», добавьте миниатюру и код колоды.</li>
                    <li>Заполните стоимость пыли, теги, класс и режим.</li>
                    <li>Загрузите доказательство легенды (если нужно) и отметьте, где показывать кнопку.</li>
                    <li>Используйте дополнительные опции: скрыть из ленты, заменить карточку шорткодом, показать во всех классах, исключить из случайных подборок.</li>
                    <li>Сохраните — вверху появится шорткод вида `[hs_deck id="123"]`.</li>
                </ol>
            </div>
            
            <div style="background: #f0f6fc; padding: 20px; border-radius: 4px; margin: 20px 0;">
                <h2>Ключевые возможности</h2>
                <ul style="line-height: 1.8; margin-left: 18px;">
                    <li>✅ Голосование с защитой от повторов и визуальной статистикой.</li>
                    <li>✅ Фильтры по классу (включая «все классы»), режиму, тегам, дате, стоимости.</li>
                    <li>✅ Поиск по названию и тегам, сортировки по лайкам/дизлайкам.</li>
                    <li>✅ Пагинация «Загрузить ещё», мобильная адаптация.</li>
                    <li>✅ Автоматическое отслеживание копирований кода.</li>
                    <li>✅ Глобальные объявления и блоки для рекламы/служебного текста.</li>
                    <li>✅ Отдельный шорткод случайных подборок с учётом исключений.</li>
                    <li>✅ Возможность заменять карточку произвольным шорткодом.</li>
                    <li>✅ Страница тегов для массового удаления и чистки ленты.</li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    public function render_announcement_page() {
        if (!current_user_can(Unified_HS_Capabilities::CAP_MANAGE_ANNOUNCEMENTS)) {
            wp_die(esc_html__('Недостаточно прав', 'unified-hs-plugins'));
        }

        wp_enqueue_media();
        $announcement_text = get_option('hs_decks_announcement_text', '');
        $announcement_image = get_option('hs_decks_announcement_image', '');
        $announcement_btn_text = get_option('hs_decks_announcement_button_text', '');
        $announcement_btn_url = get_option('hs_decks_announcement_button_url', '');
        ?>
        <div class="wrap">
            <h1>Глобальное объявление</h1>
            <p>Этот блок отображается во всех лентах и шорткодах плагина. Оставьте поля пустыми, чтобы скрыть объявление.</p>
            <form method="post" action="options.php">
                <?php settings_fields('hs_decks_announcement_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="hs_decks_announcement_text">Текст объявления</label></th>
                        <td>
                            <textarea id="hs_decks_announcement_text" name="hs_decks_announcement_text" rows="6" style="width:100%;"><?php echo esc_textarea($announcement_text); ?></textarea>
                            <p class="description">Можно использовать базовый HTML для ссылок и выделений.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hs_decks_announcement_image">Изображение</label></th>
                        <td>
                            <div style="margin-bottom:10px;">
                                <input type="text" id="hs_decks_announcement_image" name="hs_decks_announcement_image" value="<?php echo esc_attr($announcement_image); ?>" style="width:60%; max-width:400px;">
                                <button type="button" class="button" id="hs-decks-upload-announcement-image">Загрузить/выбрать</button>
                            </div>
                            <?php if ($announcement_image): ?>
                                <img src="<?php echo esc_url($announcement_image); ?>" alt="" style="max-width:300px; display:block; border:1px solid #ccc; border-radius:6px;">
                            <?php endif; ?>
                            <p class="description">Опционально. Показывается слева от текста (при ширине более 500px).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hs_decks_announcement_button_text">Текст кнопки</label></th>
                        <td>
                            <input type="text" id="hs_decks_announcement_button_text" name="hs_decks_announcement_button_text" value="<?php echo esc_attr($announcement_btn_text); ?>" style="width:300px;">
                            <p class="description">Если оставить пустым, кнопка не будет показываться.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hs_decks_announcement_button_url">Ссылка кнопки</label></th>
                        <td>
                            <input type="url" id="hs_decks_announcement_button_url" name="hs_decks_announcement_button_url" value="<?php echo esc_attr($announcement_btn_url); ?>" style="width:100%;">
                            <p class="description">Укажите полный URL. Кнопка откроется в новой вкладке.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Сохранить объявление'); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($){
            var file_frame;
            $('#hs-decks-upload-announcement-image').on('click', function(e){
                e.preventDefault();
                if (file_frame) {
                    file_frame.open();
                    return;
                }
                file_frame = wp.media.frames.file_frame = wp.media({
                    title: 'Выберите изображение объявления',
                    button: { text: 'Использовать' },
                    multiple: false
                });
                file_frame.on('select', function(){
                    var attachment = file_frame.state().get('selection').first().toJSON();
                    $('#hs_decks_announcement_image').val(attachment.url);
                });
                file_frame.open();
            });
        });
        </script>
        <?php
    }
    
    public function render_layout_settings_page() {
        if (!current_user_can(Unified_HS_Capabilities::CAP_MANAGE_LAYOUT)) {
            wp_die(esc_html__('Недостаточно прав', 'unified-hs-plugins'));
        }

        $top_content = get_option('hs_decks_single_top_content', '');
        $bottom_content = get_option('hs_decks_single_bottom_content', '');
        ?>
        <div class="wrap">
            <h1>Конфигурация страниц колод</h1>
            <p>Используйте эти поля, чтобы добавить рекламные шорткоды, текст или HTML в каждую страницу колоды. Содержимое будет автоматически добавлено до и после блока колоды.</p>
            <form method="post" action="options.php">
                <?php settings_fields('hs_decks_layout_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="hs_decks_single_top_content">Контент перед колодой</label></th>
                        <td>
                            <textarea id="hs_decks_single_top_content" name="hs_decks_single_top_content" rows="6" style="width:100%;"><?php echo esc_textarea($top_content); ?></textarea>
                            <p class="description">Можно использовать любой текст, HTML или шорткоды. Отображается перед блоком колоды на всех страницах.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hs_decks_single_bottom_content">Контент после колоды</label></th>
                        <td>
                            <textarea id="hs_decks_single_bottom_content" name="hs_decks_single_bottom_content" rows="6" style="width:100%;"><?php echo esc_textarea($bottom_content); ?></textarea>
                            <p class="description">Появляется сразу после блока колоды и кода. Подходит для ссылок, подписок и пр.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Сохранить конфигурацию'); ?>
            </form>
        </div>
        <?php
    }
    
    public function render_tags_manager_page() {
        if (!current_user_can(Unified_HS_Capabilities::CAP_MANAGE_TAGS)) {
            wp_die(esc_html__('Недостаточно прав', 'unified-hs-plugins'));
        }

        $message = '';
        $tags_data = $this->get_all_custom_tags_with_counts();
        
        if (!empty($_POST['hs_decks_manage_tags_action'])) {
            check_admin_referer('hs_decks_manage_tags');
            $action = sanitize_text_field(wp_unslash($_POST['hs_decks_manage_tags_action']));
            $selected_tag = isset($_POST['hs_decks_selected_tag']) ? sanitize_text_field(wp_unslash($_POST['hs_decks_selected_tag'])) : '';
            
            if ($action === 'delete_tag' && !empty($selected_tag)) {
                $affected = $this->remove_tag_from_all_decks($selected_tag);
                $message = sprintf('Тег "%s" удалён из %d колод.', esc_html($selected_tag), $affected);
                $tags_data = $this->get_all_custom_tags_with_counts();
            } elseif ($action === 'normalize_tags') {
                $affected = $this->normalize_tags_for_all_decks();
                $message = sprintf('Теги нормализованы в %d колодах: дубли убраны, разделители приведены к запятым.', $affected);
                $tags_data = $this->get_all_custom_tags_with_counts();
            }
        }
        
        ?>
        <div class="wrap">
            <h1>Управление тегами колод</h1>
            <p>Теги берутся из поля «Теги» у каждой колоды и хранятся как текст. Здесь вы можете удалить любой тег сразу из всех колод.</p>

            <form method="post" style="margin:16px 0 20px;">
                <?php wp_nonce_field('hs_decks_manage_tags'); ?>
                <input type="hidden" name="hs_decks_manage_tags_action" value="normalize_tags">
                <?php submit_button('Убрать дубли тегов во всех колодах', 'secondary', 'submit', false); ?>
            </form>
            
            <?php if ($message): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (empty($tags_data)): ?>
                <p>Теги не найдены.</p>
            <?php else: ?>
                <form method="post">
                    <?php wp_nonce_field('hs_decks_manage_tags'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="hs_decks_selected_tag">Выберите тег</label></th>
                            <td>
                                <select id="hs_decks_selected_tag" name="hs_decks_selected_tag" style="min-width:250px;">
                                    <option value="">— Выберите тег —</option>
                                    <?php foreach ($tags_data as $tag => $count): ?>
                                        <option value="<?php echo esc_attr($tag); ?>"><?php echo esc_html($tag); ?> (<?php echo intval($count); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">В скобках указано количество колод, в которых есть тег.</p>
                            </td>
                        </tr>
                    </table>
                    <input type="hidden" name="hs_decks_manage_tags_action" value="delete_tag">
                    <?php submit_button('Удалить тег из всех колод', 'delete'); ?>
                </form>
                
                <h2>Список тегов</h2>
                <table class="widefat striped" style="max-width:600px;">
                    <thead>
                        <tr>
                            <th>Тег</th>
                            <th>Кол-во колод</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tags_data as $tag => $count): ?>
                        <tr>
                            <td><?php echo esc_html($tag); ?></td>
                            <td><?php echo intval($count); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function add_shortcode_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                // Добавляем колонки после заголовка
                $new_columns['feed_status'] = 'Статус';
                $new_columns['deck_author'] = 'Автор';
                $new_columns['streamer'] = 'Стример';
                $new_columns['stats'] = 'Игры / Winrate';
                $new_columns['engagement'] = 'Оценки / Копии';
                $new_columns['last_updated'] = 'Обновлено';
                $new_columns['shortcode'] = 'Шорткод';
            }
        }
        return $new_columns;
    }
    
    public function render_shortcode_column($column, $post_id) {
        if ($column === 'shortcode') {
            $shortcode = '[hs_deck id="' . $post_id . '"]';
            echo '<input type="text" value="' . esc_attr($shortcode) . '" readonly onclick="this.select()" style="width:100%; font-family:monospace; background:#f9f9f9; border:1px solid #ddd; padding:4px; font-size:11px;">';
        } elseif ($column === 'feed_status') {
            $hide_from_feed = get_post_meta($post_id, '_hide_from_feed', true) === '1';
            $exclude_from_random = get_post_meta($post_id, '_exclude_from_random', true) === '1';
            $archived = get_post_meta($post_id, '_hs_deck_archived', true) === '1';
            $feed_label = $archived ? 'В архиве' : ($hide_from_feed ? 'Скрыта' : 'В ленте');
            $feed_class = $archived ? 'archive' : ($hide_from_feed ? 'hidden' : 'live');
            echo '<span class="hs-admin-badge hs-admin-badge--' . esc_attr($feed_class) . '">' . esc_html($feed_label) . '</span>';
            echo '<br><span class="hs-admin-badge hs-admin-badge--small hs-admin-badge--' . esc_attr($exclude_from_random || $archived ? 'muted' : 'random') . '">';
            echo esc_html($archived ? 'Не показывается' : ($exclude_from_random ? 'Без рандома' : 'В рандоме'));
            echo '</span>';
        } elseif ($column === 'deck_author') {
            $post = get_post($post_id);
            $author_id = $post ? absint($post->post_author) : 0;
            $author = $author_id ? get_userdata($author_id) : null;
            if ($author) {
                $label = $author->display_name ? $author->display_name : $author->user_login;
                if (current_user_can('edit_user', $author_id)) {
                    echo '<a href="' . esc_url(get_edit_user_link($author_id)) . '">' . esc_html($label) . '</a>';
                } else {
                    echo esc_html($label);
                }
            } else {
                echo '<span style="color:#999;">—</span>';
            }
        } elseif ($column === 'streamer') {
            $streamer = get_post_meta($post_id, '_deck_streamer', true);
            if ($streamer && $streamer !== '') {
                echo '<span class="hs-admin-badge hs-admin-badge--source">' . esc_html($streamer) . '</span>';
            } else {
                echo '<span style="color:#999;">—</span>';
            }
        } elseif ($column === 'stats') {
            $stats = $this->get_deck_stats($post_id);

            if ($stats['has_stats']) {
                $color = '#46b450';
                if ($stats['games'] > 0 && $stats['games'] < 10) {
                    $color = '#dc3232';
                }

                echo '<span style="color:' . esc_attr($color) . '; font-weight:bold;">';
                echo esc_html($stats['games']) . ' игр';
                echo '</span>';
                echo '<br><small style="color:#2271b1;">Winrate: ' . esc_html($this->format_winrate($stats['winrate'])) . '%</small>';
                if ($stats['has_record']) {
                    echo '<br><small style="color:#666;">' . esc_html($stats['wins']) . ' - ' . esc_html($stats['losses']) . '</small>';
                }
            } else {
                echo '<span style="color:#999;">—</span>';
            }
        } elseif ($column === 'engagement') {
            $likes = (int) get_post_meta($post_id, '_deck_likes', true);
            $dislikes = (int) get_post_meta($post_id, '_deck_dislikes', true);
            $copies = (int) get_post_meta($post_id, '_deck_copies', true);
            echo '<span style="color:#008a20;">Лайки: ' . esc_html($likes) . '</span>';
            echo '<br><span style="color:#b32d2e;">Дизлайки: ' . esc_html($dislikes) . '</span>';
            echo '<br><span style="color:#2271b1;">Копии: ' . esc_html($copies) . '</span>';
        } elseif ($column === 'last_updated') {
            $modified = get_post_modified_time('d.m.Y H:i', false, $post_id);
            echo $modified ? esc_html($modified) : '<span style="color:#999;">—</span>';
        }
    }

    private function is_deck_visible_in_feed($post_id) {
        return get_post_meta($post_id, '_hide_from_feed', true) !== '1'
            && get_post_meta($post_id, '_hs_deck_archived', true) !== '1';
    }

    private function filter_deck_feed_flags(array $post_ids, $exclude_random = false) {
        $post_ids = array_values(array_filter(array_map('absint', $post_ids)));
        if (empty($post_ids)) {
            return array();
        }

        update_meta_cache('post', $post_ids);

        $filtered = array();
        foreach ($post_ids as $post_id) {
            if (!$this->is_deck_visible_in_feed($post_id)) {
                continue;
            }
            if ($exclude_random && get_post_meta($post_id, '_exclude_from_random', true) === '1') {
                continue;
            }
            $filtered[] = $post_id;
        }

        return $filtered;
    }

    private function should_insert_feed_ad_after($deck_index) {
        return $deck_index > 0 && $deck_index % 6 === 0;
    }

    private function get_filter_terms($taxonomy) {
        if (!taxonomy_exists($taxonomy)) {
            return array();
        }

        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
        ));

        return is_wp_error($terms) ? array() : $terms;
    }

    private function read_deck_feed_filters(array $atts = array(), array $source = null) {
        $source = $source === null ? $_GET : $source;
        $read = static function($key, $default = '') use ($source) {
            if (!isset($source[$key])) {
                return $default;
            }
            return is_array($source[$key]) ? $source[$key] : wp_unslash($source[$key]);
        };

        $tags = $read('hs_tags', array());
        if (!is_array($tags)) {
            $tags = explode(',', (string) $tags);
        }

        $filters = array(
            'search' => sanitize_text_field((string) $read('hs_search')),
            'period' => absint($read('hs_period')),
            'class' => sanitize_title((string) $read('hs_class', isset($atts['class']) ? $atts['class'] : '')),
            'mode' => sanitize_title((string) $read('hs_mode')),
            'archetype' => sanitize_title((string) $read('hs_archetype')),
            'streamer' => sanitize_title((string) $read('hs_streamer')),
            'source' => sanitize_title((string) $read('hs_source')),
            'tags' => array_values(array_filter(array_map('sanitize_title', $tags))),
            'tags_mode' => $read('hs_tags_mode') === 'all' ? 'all' : 'any',
            'dust_max' => absint($read('hs_dust_max')),
            'games_min' => absint($read('hs_games_min')),
            'winrate_min' => max(0, min(100, (float) str_replace(',', '.', (string) $read('hs_winrate_min')))),
            'sort' => sanitize_key((string) $read('hs_sort', 'date')),
            'page' => max(1, absint($read('hs_page', 1))),
        );

        if (!in_array($filters['period'], array(0, 1, 3, 7, 14, 30, 90, 365), true)) {
            $filters['period'] = 0;
        }

        if (!in_array($filters['sort'], array('date', 'dust', 'winrate', 'games', 'likes', 'dislikes', 'copies'), true)) {
            $filters['sort'] = 'date';
        }

        return $filters;
    }

    private function get_search_post_ids($search) {
        global $wpdb;

        $search = trim((string) $search);
        if ($search === '') {
            return array();
        }

        $like = '%' . $wpdb->esc_like($search) . '%';
        $title_sql = $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type = %s
               AND post_status = %s
               AND post_title LIKE %s
             LIMIT 500",
            'hs_deck',
            'publish',
            $like
        );
        $meta_sql = $wpdb->prepare(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta}
             WHERE meta_key IN ('_deck_code', '_custom_tags', '_deck_streamer', '_deck_player', '_deck_source_url')
               AND meta_value LIKE %s
             LIMIT 500",
            $like
        );

        $ids = array_merge(
            array_map('absint', (array) $wpdb->get_col($title_sql)),
            array_map('absint', (array) $wpdb->get_col($meta_sql))
        );

        return array_values(array_unique(array_filter($ids)));
    }

    private function build_deck_feed_query_args(array $filters, $per_page, array $shortcode_atts = array()) {
        $args = array(
            'post_type' => 'hs_deck',
            'post_status' => 'publish',
            'posts_per_page' => max(1, absint($per_page)),
            'paged' => max(1, absint($filters['page'])),
            'ignore_sticky_posts' => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'relation' => 'OR',
                    array('key' => '_hide_from_feed', 'compare' => 'NOT EXISTS'),
                    array('key' => '_hide_from_feed', 'value' => '1', 'compare' => '!='),
                ),
                array(
                    'relation' => 'OR',
                    array('key' => '_hs_deck_archived', 'compare' => 'NOT EXISTS'),
                    array('key' => '_hs_deck_archived', 'value' => '1', 'compare' => '!='),
                ),
            ),
            'tax_query' => array('relation' => 'AND'),
        );

        if ($filters['search'] !== '') {
            $search_ids = $this->get_search_post_ids($filters['search']);
            $args['post__in'] = !empty($search_ids) ? $search_ids : array(0);
        }

        $skip_mode_tax_query = false;
        if (!empty($filters['archetype']) && !empty($filters['mode'])) {
            $decoded_mode_ids = $this->get_archetype_deck_ids_for_decoded_mode($filters['archetype'], $filters['mode']);
            $args['post__in'] = isset($args['post__in'])
                ? array_values(array_intersect(array_map('absint', $args['post__in']), $decoded_mode_ids))
                : $decoded_mode_ids;
            if (empty($args['post__in'])) {
                $args['post__in'] = array(0);
            }
            $skip_mode_tax_query = true;
        }

        if ($filters['period'] > 0) {
            $args['date_query'] = array(array(
                'after' => wp_date('Y-m-d', current_time('timestamp') - ($filters['period'] * DAY_IN_SECONDS)),
                'inclusive' => true,
            ));
        } elseif (!empty($shortcode_atts['time'])) {
            $time_parts = array_map('trim', explode(',', $shortcode_atts['time']));
            if (count($time_parts) === 1) {
                $date_from = DateTime::createFromFormat('d.m.Y', $time_parts[0]);
                if ($date_from) {
                    $args['date_query'] = array(array('after' => $date_from->format('Y-m-d'), 'inclusive' => true));
                }
            } elseif (count($time_parts) === 2) {
                $date_from = DateTime::createFromFormat('d.m.Y', $time_parts[0]);
                $date_to = DateTime::createFromFormat('d.m.Y', $time_parts[1]);
                if ($date_from && $date_to) {
                    $args['date_query'] = array(array(
                        'after' => $date_from->format('Y-m-d'),
                        'before' => $date_to->format('Y-m-d'),
                        'inclusive' => true,
                    ));
                }
            }
        }

        foreach (array(
            'deck_class' => $filters['class'],
            'deck_mode' => $filters['mode'],
            'deck_archetype' => $filters['archetype'],
            'deck_streamer' => $filters['streamer'],
            'deck_source' => $filters['source'],
        ) as $taxonomy => $slug) {
            if ($taxonomy === 'deck_mode' && $skip_mode_tax_query) {
                continue;
            }
            if ($slug !== '') {
                $args['tax_query'][] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $slug,
                );
            }
        }

        if (!empty($filters['tags'])) {
            if ($filters['tags_mode'] === 'all') {
                foreach ($filters['tags'] as $tag_slug) {
                    $args['tax_query'][] = array(
                        'taxonomy' => 'deck_custom_tag',
                        'field' => 'slug',
                        'terms' => $tag_slug,
                    );
                }
            } else {
                $args['tax_query'][] = array(
                    'taxonomy' => 'deck_custom_tag',
                    'field' => 'slug',
                    'terms' => $filters['tags'],
                    'operator' => 'IN',
                );
            }
        }

        if (count($args['tax_query']) <= 1) {
            unset($args['tax_query']);
        }

        if ($filters['dust_max'] > 0) {
            $args['meta_query'][] = array('key' => '_dust_cost', 'value' => $filters['dust_max'], 'compare' => '<=', 'type' => 'NUMERIC');
        }
        if ($filters['games_min'] > 0) {
            $args['meta_query'][] = array('key' => '_deck_games', 'value' => $filters['games_min'], 'compare' => '>=', 'type' => 'NUMERIC');
        }
        if ($filters['winrate_min'] > 0) {
            $args['meta_query'][] = array('key' => '_deck_winrate', 'value' => $filters['winrate_min'], 'compare' => '>=', 'type' => 'NUMERIC');
        }

        switch ($filters['sort']) {
            case 'dust':
                $args['meta_key'] = '_dust_cost';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                break;
            case 'winrate':
                $args['meta_key'] = '_deck_winrate';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'games':
                $args['meta_key'] = '_deck_games';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'likes':
                $args['meta_key'] = '_deck_likes';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'dislikes':
                $args['meta_key'] = '_deck_dislikes';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'copies':
                $args['meta_key'] = '_deck_copies';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            default:
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
        }

        return $args;
    }

    private function render_decks_feed(array $filters, $per_page, array $shortcode_atts = array()) {
        $query_args = $this->build_deck_feed_query_args($filters, $per_page, $shortcode_atts);
        $cache_ttl = $this->cap_vote_sort_cache_ttl(
            (int) apply_filters('hs_decks_server_feed_cache_ttl', 12 * HOUR_IN_SECONDS),
            $filters
        );
        $cache_key = 'hs_decks_server_' . md5(wp_json_encode(array(
            'v' => self::get_decks_cache_version(),
            'plugin' => $this->get_frontend_version(''),
            'args' => $query_args,
        )));

        if ($cache_ttl > 0) {
            $cached = get_transient($cache_key);
            if (is_array($cached) && isset($cached['html'], $cached['pagination'], $cached['total'], $cached['pages'])) {
                return $cached;
            }
        }

        $page_data = $this->get_filtered_feed_page_data($query_args, $per_page);
        $deck_ids = $page_data['post_ids'];
        $total_items = $page_data['total'];
        $total_pages = $page_data['pages'];

        if (empty($deck_ids) && $page_data['raw_total'] > 0 && !$page_data['exhaustive']) {
            $query = new WP_Query($query_args);
            $deck_ids = $this->filter_deck_feed_flags(wp_list_pluck((array) $query->posts, 'ID'));
            $total_items = (int) $query->found_posts;
            $total_pages = max(1, (int) $query->max_num_pages);
        }

        ob_start();
        if (!empty($deck_ids)) {
            $deck_index = 0;
            $ad_index = 0;
            foreach ($deck_ids as $post_id) {
                $deck_index++;
                echo $this->render_deck_card($post_id, true, true, $filters['page'] <= 1 && $deck_index === 1, false);
                if ($this->should_render_feed_ads($query_args, $shortcode_atts) && $this->should_insert_feed_ad_after($deck_index)) {
                    $ad_index++;
                    echo $this->render_feed_ad_card($ad_index);
                }
            }
        } else {
            echo '<p class="no-decks">Колоды не найдены</p>';
        }

        $result = array(
            'html' => ob_get_clean(),
            'pagination' => $this->render_decks_feed_pagination($filters['page'], $total_pages, $total_items, $per_page),
            'total' => $total_items,
            'pages' => $total_pages,
            'page' => max(1, absint($filters['page'])),
        );

        if ($cache_ttl > 0) {
            set_transient($cache_key, $result, $cache_ttl);
        }

        return $result;
    }

    private function get_filtered_feed_page_data(array $query_args, $per_page) {
        $current_page = isset($query_args['paged']) ? max(1, absint($query_args['paged'])) : 1;
        $per_page = max(1, absint($per_page));
        $scan_limit = (int) apply_filters('hs_decks_feed_filter_scan_limit', 600, $query_args, $per_page, $current_page);
        $scan_limit = max($per_page, $scan_limit);
        $candidate_limit = max(60, $per_page * max(6, $current_page * 4));
        $candidate_limit = min($candidate_limit, $scan_limit);

        $scan_args = $query_args;
        $scan_args['posts_per_page'] = $candidate_limit;
        $scan_args['paged'] = 1;
        unset($scan_args['offset']);

        $scan_query = new WP_Query($scan_args);
        $raw_ids = wp_list_pluck((array) $scan_query->posts, 'ID');
        $raw_ids = $this->filter_deck_feed_flags($raw_ids);
        $filtered_ids = $this->filter_decks($raw_ids);

        $offset = ($current_page - 1) * $per_page;
        $exhaustive = (int) $scan_query->found_posts <= $candidate_limit;
        $total_items = $exhaustive ? count($filtered_ids) : (int) $scan_query->found_posts;

        return array(
            'post_ids' => array_slice($filtered_ids, $offset, $per_page),
            'total' => $total_items,
            'pages' => max(1, (int) ceil($total_items / $per_page)),
            'raw_total' => (int) $scan_query->found_posts,
            'exhaustive' => $exhaustive,
        );
    }

    private function render_decks_feed_pagination($current_page, $total_pages, $total_items, $per_page) {
        $current_page = max(1, absint($current_page));
        $total_pages = max(1, absint($total_pages));
        $total_items = max(0, absint($total_items));
        $per_page = max(1, absint($per_page));

        if ($total_items <= $per_page) {
            return '<div class="hs-load-more-info">Показано ' . esc_html(number_format_i18n($total_items)) . ' колод</div>';
        }

        ob_start();
        ?>
        <nav class="hs-feed-pagination hs-feed-pagination-server" aria-label="Пагинация колод">
            <button type="button" class="hs-page-btn hs-page-prev" data-page="<?php echo esc_attr($current_page - 1); ?>"<?php disabled($current_page <= 1); ?>>Назад</button>
            <?php
            $last_rendered = 0;
            for ($page = 1; $page <= $total_pages; $page++) {
                $is_edge = $page === 1 || $page === $total_pages;
                $is_near = abs($page - $current_page) <= 2;
                if (!$is_edge && !$is_near) {
                    if ($last_rendered !== -1) {
                        echo '<span class="hs-page-ellipsis" aria-hidden="true">…</span>';
                        $last_rendered = -1;
                    }
                    continue;
                }
                ?>
                <button type="button" class="hs-page-btn<?php echo $page === $current_page ? ' active' : ''; ?>" data-page="<?php echo esc_attr($page); ?>"<?php echo $page === $current_page ? ' aria-current="page"' : ''; ?>><?php echo esc_html($page); ?></button>
                <?php
                $last_rendered = $page;
            }
            ?>
            <button type="button" class="hs-page-btn hs-page-next" data-page="<?php echo esc_attr($current_page + 1); ?>"<?php disabled($current_page >= $total_pages); ?>>Вперёд</button>
        </nav>
        <div class="hs-load-more-info">
            <?php
            $start = (($current_page - 1) * $per_page) + 1;
            $end = min($total_items, $current_page * $per_page);
            echo esc_html(sprintf('Страница %d из %d · показано %d-%d из %d колод', $current_page, $total_pages, $start, $end, $total_items));
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function normalize_deck_feed_filters_for_cache(array $filters) {
        $normalized_filters = $filters;
        if (isset($normalized_filters['tags']) && is_array($normalized_filters['tags'])) {
            $normalized_filters['tags'] = array_values(array_unique($normalized_filters['tags']));
            sort($normalized_filters['tags']);
        }
        ksort($normalized_filters);

        return $normalized_filters;
    }

    /**
     * Сортировка по голосам должна реагировать быстрее обычного 12-часового
     * HTML-cache. Числа на карточках при этом гидратируются отдельным batch AJAX.
     */
    private function cap_vote_sort_cache_ttl($cache_ttl, array $filters) {
        $cache_ttl = (int) $cache_ttl;
        $sort = isset($filters['sort']) ? sanitize_key((string) $filters['sort']) : '';
        if ($cache_ttl > 5 * MINUTE_IN_SECONDS && in_array($sort, array('likes', 'dislikes'), true)) {
            return 5 * MINUTE_IN_SECONDS;
        }

        return $cache_ttl;
    }

    private function get_filter_decks_ajax_cache_key(array $filters, $per_page) {
        return 'hs_decks_filter_ajax_' . md5(wp_json_encode(array(
            'v' => self::get_decks_cache_version(),
            'plugin' => $this->get_frontend_version(''),
            'filters' => $this->normalize_deck_feed_filters_for_cache($filters),
            'per_page' => max(1, absint($per_page)),
        )));
    }

    public function handle_filter_decks_ajax() {
        check_ajax_referer('hs_decks_nonce', 'nonce');

        $filters = $this->read_deck_feed_filters(array(), $_POST);
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : (int) get_option('hs_decks_per_page', self::DEFAULT_PER_PAGE);
        if ($per_page < 1) {
            $per_page = self::DEFAULT_PER_PAGE;
        }

        $cache_ttl = $this->cap_vote_sort_cache_ttl(
            (int) apply_filters('hs_decks_filter_ajax_cache_ttl', 12 * HOUR_IN_SECONDS),
            $filters
        );
        $cache_key = '';
        if ($cache_ttl > 0) {
            $cache_key = $this->get_filter_decks_ajax_cache_key($filters, $per_page);
            $cached_response = get_transient($cache_key);
            if (is_array($cached_response) && isset($cached_response['html'], $cached_response['pagination'], $cached_response['total'], $cached_response['pages'])) {
                if (!headers_sent()) {
                    header('X-HS-Decks-Filter-Cache: HIT');
                }
                wp_send_json_success($cached_response);
            }
        }

        if (class_exists('Unified_HS_Deck_Events')) {
            Unified_HS_Deck_Events::record(0, Unified_HS_Deck_Events::EVENT_FILTER_APPLY, $filters, 'feed');
        }

        $response = $this->render_decks_feed($filters, $per_page);
        if ($cache_ttl > 0 && $cache_key !== '') {
            set_transient($cache_key, $response, $cache_ttl);
        }
        if (!headers_sent()) {
            header('X-HS-Decks-Filter-Cache: MISS');
        }
        wp_send_json_success($response);
    }

    public function decks_shortcode($atts) {
        // Устанавливаем флаг, что был вызван основной шорткод
        do_action('hs_decks_shortcode_called');
        
        $default_per_page = (int) get_option('hs_decks_per_page', self::DEFAULT_PER_PAGE);
        if ($default_per_page < 1) {
            $default_per_page = self::DEFAULT_PER_PAGE;
        }
        $atts = shortcode_atts(array(
            'per_page' => $default_per_page,
            'deck' => $default_per_page,
            'class' => '',
            'time' => '',
            'random_row' => '',
            'ads' => '1',
        ), $atts);
        
        // Приоритет параметру deck если он указан
        $per_page = !empty($atts['deck']) ? intval($atts['deck']) : intval($atts['per_page']);
        $random_mode = !empty($atts['random_row']) && intval($atts['random_row']) > 0;
        $random_count = $random_mode ? max(1, intval($atts['random_row'])) : 0;

        if (!$random_mode) {
            $filters = $this->read_deck_feed_filters($atts);
            $classes = $this->get_filter_terms('deck_class');
            $modes = $this->get_filter_terms('deck_mode');
            $archetypes = $this->get_filter_terms('deck_archetype');
            $streamers = $this->get_filter_terms('deck_streamer');
            $sources = $this->get_filter_terms('deck_source');
            $tags = $this->get_filter_terms('deck_custom_tag');
            $help_text = get_option('hs_decks_help_text', 'Используйте фильтры для поиска колод по классу, режиму или тегам.');
            $feed = $this->render_decks_feed($filters, $per_page, $atts);

            $this->enqueue_front_assets();

            ob_start();
            ?>
            <div class="hs-decks-container hs-decks-container-server">
                <form class="hs-decks-filters hs-decks-filters-server" id="hs-decks-filter-form" method="get">
                    <input type="hidden" name="hs_page" id="hs-page" value="<?php echo esc_attr($filters['page']); ?>">
                    <input type="hidden" name="hs_tags" id="hs-tags-value" value="<?php echo esc_attr(implode(',', $filters['tags'])); ?>">
                    <input type="hidden" name="hs_tags_mode" id="hs-tags-mode-value" value="<?php echo esc_attr($filters['tags_mode']); ?>">

                    <div class="filter-row filter-row-main">
                        <label class="hs-filter-field hs-filter-search">
                            <span>Поиск</span>
                            <input type="text" id="deck-search" name="hs_search" value="<?php echo esc_attr($filters['search']); ?>" placeholder="Название, код, тег или автор">
                        </label>

                        <label class="hs-filter-field">
                            <span>Период</span>
                            <select id="filter-period" name="hs_period">
                                <option value=""<?php selected($filters['period'], 0); ?>>За всё время</option>
                                <option value="1"<?php selected($filters['period'], 1); ?>>24 часа</option>
                                <option value="3"<?php selected($filters['period'], 3); ?>>3 дня</option>
                                <option value="7"<?php selected($filters['period'], 7); ?>>7 дней</option>
                                <option value="14"<?php selected($filters['period'], 14); ?>>2 недели</option>
                                <option value="30"<?php selected($filters['period'], 30); ?>>Месяц</option>
                                <option value="90"<?php selected($filters['period'], 90); ?>>90 дней</option>
                                <option value="365"<?php selected($filters['period'], 365); ?>>Год</option>
                            </select>
                        </label>

                        <label class="hs-filter-field">
                            <span>Класс</span>
                            <select id="filter-class" name="hs_class">
                                <option value="">Все классы</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo esc_attr($class->slug); ?>"<?php selected($filters['class'], $class->slug); ?>><?php echo esc_html($class->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="hs-filter-field">
                            <span>Режим</span>
                            <select id="filter-mode" name="hs_mode">
                                <option value="">Все режимы</option>
                                <?php foreach ($modes as $mode): ?>
                                    <option value="<?php echo esc_attr($mode->slug); ?>"<?php selected($filters['mode'], $mode->slug); ?>><?php echo esc_html($mode->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>

                    <div class="filter-row filter-row-metrics">
                        <label class="hs-filter-field hs-filter-field--compact">
                            <span>Игр от</span>
                            <input type="number" id="filter-games" name="hs_games_min" value="<?php echo esc_attr($filters['games_min'] ?: ''); ?>" placeholder="20" min="0" step="1">
                        </label>
                        <label class="hs-filter-field hs-filter-field--compact">
                            <span>Winrate от</span>
                            <input type="number" id="filter-winrate" name="hs_winrate_min" value="<?php echo esc_attr($filters['winrate_min'] ?: ''); ?>" placeholder="55" min="0" max="100" step="0.1">
                        </label>
                        <label class="hs-filter-field hs-filter-field--compact">
                            <span>Пыль до</span>
                            <input type="number" id="filter-dust" name="hs_dust_max" value="<?php echo esc_attr($filters['dust_max'] ?: ''); ?>" placeholder="4000" min="0" step="100">
                        </label>
                        <label class="hs-filter-field">
                            <span>Сортировка</span>
                            <select id="filter-sort" name="hs_sort">
                                <option value="date"<?php selected($filters['sort'], 'date'); ?>>Сначала новые</option>
                                <option value="games"<?php selected($filters['sort'], 'games'); ?>>Больше игр</option>
                                <option value="winrate"<?php selected($filters['sort'], 'winrate'); ?>>Выше winrate</option>
                                <option value="copies"<?php selected($filters['sort'], 'copies'); ?>>Больше копий</option>
                                <option value="likes"<?php selected($filters['sort'], 'likes'); ?>>Больше лайков</option>
                                <option value="dust"<?php selected($filters['sort'], 'dust'); ?>>Дешевле</option>
                                <option value="dislikes"<?php selected($filters['sort'], 'dislikes'); ?>>Больше дизлайков</option>
                            </select>
                        </label>
                        <button type="button" class="hs-filter-toggle" id="hs-advanced-toggle" aria-expanded="<?php echo ($filters['archetype'] || $filters['streamer'] || $filters['source'] || !empty($filters['tags'])) ? 'true' : 'false'; ?>" aria-controls="hs-advanced-filters">
                            Еще фильтры
                        </button>
                        <button type="button" class="hs-filter-reset" id="hs-filter-reset">Сбросить</button>
                    </div>

                    <div class="hs-advanced-filters" id="hs-advanced-filters"<?php echo ($filters['archetype'] || $filters['streamer'] || $filters['source'] || !empty($filters['tags'])) ? '' : ' hidden'; ?>>
                        <label class="hs-filter-field">
                            <span>Архетип</span>
                            <select id="filter-archetype" name="hs_archetype">
                                <option value="">Все архетипы</option>
                                <?php foreach ($archetypes as $archetype): ?>
                                    <option value="<?php echo esc_attr($archetype->slug); ?>"<?php selected($filters['archetype'], $archetype->slug); ?>><?php echo esc_html($archetype->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="hs-filter-field hs-filter-streamer">
                            <span>Стример / автор</span>
                            <select id="filter-streamer" name="hs_streamer">
                                <option value="">Все стримеры</option>
                                <?php foreach ($streamers as $streamer): ?>
                                    <option value="<?php echo esc_attr($streamer->slug); ?>"<?php selected($filters['streamer'], $streamer->slug); ?>><?php echo esc_html($streamer->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="hs-filter-field">
                            <span>Источник</span>
                            <select id="filter-source" name="hs_source">
                                <option value="">Все источники</option>
                                <?php foreach ($sources as $source): ?>
                                    <option value="<?php echo esc_attr($source->slug); ?>"<?php selected($filters['source'], $source->slug); ?>><?php echo esc_html($source->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <div class="hs-tags-filter" id="tags-filter">
                            <div class="tags-filter-header">
                                <div class="tags-filter-title">Теги</div>
                                <label class="tags-filter-logic" id="tags-logic-label"<?php echo count($filters['tags']) >= 2 ? '' : ' style="display:none;"'; ?>>
                                    <input type="checkbox" id="tags-all-checkbox"<?php checked($filters['tags_mode'], 'all'); ?>>
                                    <span>Только колоды со всеми выбранными тегами</span>
                                </label>
                            </div>
                            <div class="tags-filter-list">
                                <?php foreach ($tags as $tag): ?>
                                    <button type="button" class="tag-filter-btn<?php echo in_array($tag->slug, $filters['tags'], true) ? ' active' : ''; ?>" data-tag="<?php echo esc_attr($tag->slug); ?>">
                                        <?php echo esc_html($tag->name); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="hs-help-link" onclick="document.getElementById('help-modal').style.display='flex'">Помощь</div>
                </form>

                <div class="hs-decks-grid" id="decks-grid" data-server-feed="1" data-per-page="<?php echo esc_attr($per_page); ?>">
                    <?php echo $feed['html']; ?>
                </div>
                <div class="hs-load-more-container" id="hs-load-more"><?php echo $feed['pagination']; ?></div>
            </div>

            <div id="help-modal" class="hs-help-modal" onclick="if(event.target === this) this.style.display='none'">
                <div class="hs-help-modal-content">
                    <span class="hs-help-modal-close" onclick="document.getElementById('help-modal').style.display='none'">&times;</span>
                    <h3>Помощь</h3>
                    <div class="hs-help-modal-text"><?php echo wp_kses_post($help_text); ?></div>
                </div>
            </div>

            <?php
            return ob_get_clean();
        }
        
        ob_start();
        
        $classes = get_terms(array('taxonomy' => 'deck_class', 'hide_empty' => false));
        $modes = get_terms(array('taxonomy' => 'deck_mode', 'hide_empty' => false));
        $help_text = get_option('hs_decks_help_text', 'Используйте фильтры для поиска колод по классу, режиму или тегам.');
        
        // Получаем все уникальные теги с учетом фильтра по классу и дате
        $deck_args = array(
            'post_type' => 'hs_deck',
            'posts_per_page' => 500,
            'fields' => 'ids',
            'no_found_rows' => true,
            'update_post_term_cache' => false,
            'ignore_sticky_posts' => true,
        );

        // Добавляем фильтр по классу
        if (!empty($atts['class'])) {
            $deck_args['tax_query'] = array(
                array(
                    'taxonomy' => 'deck_class',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($atts['class'])
                )
            );
        }
        
        // Добавляем фильтр по дате
        if (!empty($atts['time'])) {
            $time_parts = array_map('trim', explode(',', $atts['time']));
            
            if (count($time_parts) == 1) {
                // Одна дата - показываем от этой даты и новее
                $date_from = DateTime::createFromFormat('d.m.Y', $time_parts[0]);
                if ($date_from) {
                    $deck_args['date_query'] = array(
                        array(
                            'after' => $date_from->format('Y-m-d'),
                            'inclusive' => true
                        )
                    );
                }
            } elseif (count($time_parts) == 2) {
                // Две даты - показываем диапазон
                $date_from = DateTime::createFromFormat('d.m.Y', $time_parts[0]);
                $date_to = DateTime::createFromFormat('d.m.Y', $time_parts[1]);
                
                if ($date_from && $date_to) {
                    $deck_args['date_query'] = array(
                        array(
                            'after' => $date_from->format('Y-m-d'),
                            'before' => $date_to->format('Y-m-d'),
                            'inclusive' => true
                        )
                    );
                }
            }
        }
        
        // Кэш опций фильтров: инвалидируется через версию (bump_decks_cache_version)
        $cache_version = self::get_decks_cache_version();
        $cache_key = 'hs_decks_filters_v' . $cache_version . '_' . md5(wp_json_encode(array(
            isset($atts['class']) ? $atts['class'] : '',
            isset($atts['time']) ? $atts['time'] : '',
        )));
        $filter_options = get_transient($cache_key);
        $all_tags = array();
        $all_streamers = array();

        if (is_array($filter_options) && isset($filter_options['tags'], $filter_options['streamers'])) {
            $all_tags = is_array($filter_options['tags']) ? $filter_options['tags'] : array();
            $all_streamers = is_array($filter_options['streamers']) ? $filter_options['streamers'] : array();
        } else {
            $all_tags = array();
            $all_streamers = array();
            $seen = array();
            $seen_streamers = array();
            $scan_limit = (int) apply_filters('hs_decks_tag_scan_max_posts', 2000);
            $batch_size = 500;
            $paged = 1;
            $scanned = 0;

            do {
                $remaining = $scan_limit > 0 ? $scan_limit - $scanned : $batch_size;
                if ($remaining <= 0) {
                    break;
                }

                $batch_args = $deck_args;
                $batch_args['posts_per_page'] = min($batch_size, $remaining);
                $batch_args['paged'] = $paged;
                $batch_args['orderby'] = 'ID';
                $batch_args['order'] = 'ASC';

                $raw_deck_ids = get_posts($batch_args);
                if (empty($raw_deck_ids)) {
                    break;
                }

                $deck_ids = $this->filter_deck_feed_flags($raw_deck_ids);

                foreach ($deck_ids as $deck_id) {
                    $tags = get_post_meta($deck_id, '_custom_tags', true);
                    if ($tags) {
                        $tags_array = self::custom_tags_to_array($tags);
                        foreach ($tags_array as $tag) {
                            $key = hs_mb_lower($tag);
                            if ($tag !== '' && !isset($seen[$key])) {
                                $seen[$key] = true;
                                $all_tags[] = $tag;
                            }
                        }
                    }

                    $streamer = trim((string) get_post_meta($deck_id, '_deck_streamer', true));
                    if ($streamer !== '') {
                        $streamer_key = hs_mb_lower($streamer);
                        if (!isset($seen_streamers[$streamer_key])) {
                            $seen_streamers[$streamer_key] = true;
                            $all_streamers[] = $streamer;
                        }
                    }
                }

                $scanned += count($raw_deck_ids);
                $paged++;
            } while (count($raw_deck_ids) === $batch_size);

            sort($all_tags);
            sort($all_streamers);

            // 1 час; при любом сохранении колоды версия кэша инкрементируется и ключ меняется
            set_transient($cache_key, array(
                'tags' => $all_tags,
                'streamers' => $all_streamers,
            ), HOUR_IN_SECONDS);
        }
        
        $this->enqueue_front_assets();
        ?>
        <div class="hs-decks-container<?php echo $random_mode ? ' hs-decks-container-random' : ''; ?>">
            <?php if (!$random_mode): ?>
            <div class="hs-decks-filters">
                <div class="filter-row filter-row-main">
                    <label class="hs-filter-field hs-filter-search">
                        <span>Поиск</span>
                        <input type="text" id="deck-search" placeholder="Название, код, тег или автор">
                    </label>

                    <label class="hs-filter-field">
                        <span>Период</span>
                        <select id="filter-period">
                            <option value="">За всё время</option>
                            <option value="1">24 часа</option>
                            <option value="3">3 дня</option>
                            <option value="7">7 дней</option>
                            <option value="14">2 недели</option>
                            <option value="30">Месяц</option>
                        </select>
                    </label>

                    <label class="hs-filter-field">
                        <span>Класс</span>
                        <select id="filter-class">
                            <option value="">Все классы</option>
                            <?php
                            foreach ($classes as $class) {
                                echo '<option value="' . esc_attr($class->slug) . '">' . esc_html($class->name) . '</option>';
                            }
                            ?>
                        </select>
                    </label>

                    <label class="hs-filter-field">
                        <span>Режим</span>
                        <select id="filter-mode">
                            <option value="">Все режимы</option>
                            <?php
                            foreach ($modes as $mode) {
                                echo '<option value="' . esc_attr($mode->slug) . '">' . esc_html($mode->name) . '</option>';
                            }
                            ?>
                        </select>
                    </label>
                </div>

                <div class="filter-row filter-row-metrics">
                    <label class="hs-filter-field hs-filter-field--compact">
                        <span>Пыль до</span>
                        <input type="number" id="filter-dust" placeholder="например 4000" min="0" step="100">
                    </label>
                    <label class="hs-filter-field hs-filter-field--compact">
                        <span>Игр от</span>
                        <input type="number" id="filter-games" placeholder="например 20" min="0" step="1">
                    </label>
                    <label class="hs-filter-field hs-filter-field--compact">
                        <span>Winrate от</span>
                        <input type="number" id="filter-winrate" placeholder="например 55" min="0" max="100" step="0.1">
                    </label>
                    <label class="hs-filter-field">
                        <span>Сортировка</span>
                        <select id="filter-sort">
                            <option value="date">Сначала новые</option>
                            <option value="dust">Дешевле</option>
                            <option value="winrate">Выше winrate</option>
                            <option value="games">Больше игр</option>
                            <option value="likes">Больше лайков</option>
                            <option value="dislikes">Больше дизлайков</option>
                        </select>
                    </label>
                    <button type="button" class="hs-filter-toggle" id="hs-advanced-toggle" aria-expanded="false" aria-controls="hs-advanced-filters">
                        Теги и стримеры
                    </button>
                    <button type="button" class="hs-filter-reset" id="hs-filter-reset">Сбросить</button>
                </div>

                <div class="hs-advanced-filters" id="hs-advanced-filters" hidden>
                    <label class="hs-filter-field hs-filter-streamer">
                        <span>Стример / автор</span>
                        <select id="filter-streamer">
                            <option value="">Все стримеры</option>
                            <?php foreach ($all_streamers as $streamer): ?>
                                <option value="<?php echo esc_attr(hs_mb_lower($streamer)); ?>"><?php echo esc_html($streamer); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <div class="hs-tags-filter" id="tags-filter">
                    <div class="tags-filter-header">
                        <div class="tags-filter-title">Теги</div>
                        <label class="tags-filter-logic" id="tags-logic-label" style="display:none;">
                            <input type="checkbox" id="tags-all-checkbox">
                            <span>Только колоды со всеми выбранными тегами</span>
                        </label>
                    </div>
                    <div class="tags-filter-list">
                        <?php foreach ($all_tags as $tag): ?>
                            <button class="tag-filter-btn" data-tag="<?php echo esc_attr(hs_mb_lower($tag)); ?>">
                                <?php echo esc_html($tag); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    </div>
                </div>
                
                <div class="hs-help-link" onclick="document.getElementById('help-modal').style.display='flex'">
                    Помощь
                </div>
            </div>
            <?php endif; ?>
            
            <div class="hs-decks-grid<?php echo $random_mode ? ' hs-decks-grid-random' : ''; ?>" 
                 id="decks-grid" 
                 data-per-page="<?php echo $per_page; ?>"
                 <?php if ($random_mode): ?>data-random-columns="<?php echo $random_count; ?>"<?php endif; ?>>
                <?php
                if ($random_mode) {
                    $random_args = array(
                        'posts_per_page' => max($random_count, $random_count * 6),
                        'orderby' => 'rand',
                        'date_query' => array(
                            array(
                                'after' => date('Y-m-d', strtotime('-2 days')),
                                'inclusive' => true
                            )
                        )
                    );
                    echo $this->get_decks_html($random_args, $atts);
                } else {
                    /**
                     * Жёсткий cap на размер фида, чтобы не тянуть тысячи колод
                     * со всеми мета-полями за один запрос. JS-пагинация идёт сверху,
                     * но серверу нет смысла отдавать больше hs_decks_feed_max_posts постов.
                     */
                    $max_feed = (int) apply_filters('hs_decks_feed_max_posts', 250);
                    if ($max_feed < 1) {
                        $max_feed = 250;
                    }
                    echo $this->get_decks_html(array('posts_per_page' => $max_feed), $atts);
                }
                ?>
            </div>
            
            <?php if (!$random_mode): ?>
            <div class="hs-load-more-container" id="hs-load-more"></div>
            <?php endif; ?>
        </div>
        
        <?php if (!$random_mode): ?>
        <div id="help-modal" class="hs-help-modal" onclick="if(event.target === this) this.style.display='none'">
            <div class="hs-help-modal-content">
                <span class="hs-help-modal-close" onclick="document.getElementById('help-modal').style.display='none'">&times;</span>
                <h3>Помощь</h3>
                <div class="hs-help-modal-text">
                    <?php echo wp_kses_post($help_text); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php
        return ob_get_clean();
    }

    public function decks_random_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 2,
            'class' => '',
            'time' => '',
        ), $atts);
        
        $atts['random_row'] = max(1, intval($atts['count']));
        unset($atts['count']);
        
        return $this->decks_shortcode($atts);
    }

    private function get_current_archetypes_index_url() {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        if (is_string($request_uri) && $request_uri !== '') {
            return home_url($request_uri);
        }
        return get_permalink() ?: home_url('/');
    }

    private function render_archetype_preserved_query_inputs(array $exclude_keys) {
        $exclude_map = array_fill_keys($exclude_keys, true);
        foreach ($_GET as $key => $value) {
            $key = sanitize_key((string) $key);
            if ($key === '' || isset($exclude_map[$key]) || is_array($value)) {
                continue;
            }
            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr(sanitize_text_field(wp_unslash($value))) . '">';
        }
    }

    private function get_archetype_term_ids_for_class($class_slug) {
        $class_slug = sanitize_title($class_slug);
        if ($class_slug === '') {
            return array();
        }

        $class = get_term_by('slug', $class_slug, 'deck_class');
        if (!$class || is_wp_error($class)) {
            return array();
        }

        global $wpdb;
        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT tt_arch.term_id
             FROM {$wpdb->term_relationships} tr_class
             INNER JOIN {$wpdb->term_taxonomy} tt_class
                ON tt_class.term_taxonomy_id = tr_class.term_taxonomy_id
             INNER JOIN {$wpdb->term_relationships} tr_arch
                ON tr_arch.object_id = tr_class.object_id
             INNER JOIN {$wpdb->term_taxonomy} tt_arch
                ON tt_arch.term_taxonomy_id = tr_arch.term_taxonomy_id
             INNER JOIN {$wpdb->posts} p
                ON p.ID = tr_class.object_id
             WHERE tt_class.taxonomy = %s
                AND tt_class.term_id = %d
                AND tt_arch.taxonomy = %s
                AND p.post_type = %s
                AND p.post_status = %s",
            'deck_class',
            absint($class->term_id),
            'deck_archetype',
            'hs_deck',
            'publish'
        ));

        return array_map('absint', (array) $ids);
    }

    private function get_deck_mode_term_data($mode_slug) {
        $mode_slug = sanitize_title((string) $mode_slug);
        if ($mode_slug === '') {
            return array();
        }

        $mode = get_term_by('slug', $mode_slug, 'deck_mode');
        if (!$mode || is_wp_error($mode)) {
            return array();
        }

        return array(
            'term_id' => absint($mode->term_id),
            'slug' => (string) $mode->slug,
            'name' => (string) $mode->name,
        );
    }

    private function get_deck_mode_data_from_format($format) {
        $map = array(
            1 => array('slug' => 'volnyj', 'name' => 'Вольный'),
            2 => array('slug' => 'standart', 'name' => 'Стандарт'),
            3 => array('slug' => 'klassicheskij', 'name' => 'Классический'),
            4 => array('slug' => 'tavern', 'name' => 'Потасовка'),
        );
        $format = absint($format);
        if (empty($map[$format])) {
            return array();
        }

        $mode = $this->get_deck_mode_term_data($map[$format]['slug']);
        if (!empty($mode)) {
            return $mode;
        }

        return array(
            'term_id' => 0,
            'slug' => $map[$format]['slug'],
            'name' => $map[$format]['name'],
        );
    }

    private function get_kolodahs_generator_db() {
        static $db = null;
        static $checked = false;

        if ($checked) {
            return $db;
        }
        $checked = true;

        if (!class_exists('wpdb')) {
            return null;
        }

        $config_path = (string) apply_filters(
            'hs_decks_kolodahs_db_config_path',
            '/var/www/koloda/data/www/kolodahs.ru/config/db.php'
        );
        if ($config_path === '' || !is_readable($config_path)) {
            return null;
        }

        $config = include $config_path;
        if (!is_array($config) || empty($config['dsn']) || empty($config['username'])) {
            return null;
        }

        $dsn = (string) $config['dsn'];
        $host = 'localhost';
        $dbname = 'generator';
        if (preg_match('/(?:^|;)host=([^;]+)/', $dsn, $host_match)) {
            $host = $host_match[1];
        }
        if (preg_match('/(?:^|;)dbname=([^;]+)/', $dsn, $db_match)) {
            $dbname = $db_match[1];
        }

        $candidate = new wpdb(
            (string) $config['username'],
            isset($config['password']) ? (string) $config['password'] : '',
            $dbname,
            $host
        );
        $candidate->suppress_errors(true);
        $has_cards = $candidate->get_var("SHOW TABLES LIKE 'cards'");
        if ($has_cards !== 'cards') {
            return null;
        }

        $db = $candidate;
        return $db;
    }

    private function get_standard_deck_set_map() {
        static $set_map = null;
        if (is_array($set_map)) {
            return $set_map;
        }

        $sets = array();
        $db = $this->get_kolodahs_generator_db();
        if ($db) {
            $raw_sets = (string) $db->get_var(
                "SELECT option_value FROM options WHERE option_name IN ('hs_standard_sets', 'standard_sets', 'current_standard_sets') ORDER BY FIELD(option_name, 'hs_standard_sets', 'standard_sets', 'current_standard_sets') LIMIT 1"
            );
            if ($raw_sets !== '') {
                $decoded = json_decode($raw_sets, true);
                if (is_array($decoded)) {
                    $sets = $decoded;
                } else {
                    $sets = preg_split('/[\s,;|]+/', $raw_sets);
                }
            }
        }

        $sets = (array) apply_filters('hs_decks_standard_sets', $sets);
        $set_map = array();
        foreach ($sets as $set) {
            $key = strtoupper(trim((string) $set));
            if ($key !== '') {
                $set_map[$key] = true;
            }
        }

        return $set_map;
    }

    private function get_generator_all_card_format_rows() {
        $cache_key = 'hs_generator_all_card_sets_v1';
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $db = $this->get_kolodahs_generator_db();
        if (!$db) {
            return array();
        }

        $rows = $db->get_results(
            "SELECT c.dbf, c.`set`, CASE WHEN cc.dbf IS NULL THEN 0 ELSE 1 END AS is_core
             FROM cards c
             LEFT JOIN corecard cc ON cc.dbf = c.dbf",
            ARRAY_A
        );
        if (!is_array($rows)) {
            $rows = array();
        }

        $map = array();
        foreach ($rows as $row) {
            $dbf = isset($row['dbf']) ? absint($row['dbf']) : 0;
            if (!$dbf) {
                continue;
            }
            $map[$dbf] = array(
                'set' => isset($row['set']) ? strtoupper(trim((string) $row['set'])) : '',
                'is_core' => !empty($row['is_core']),
            );
        }

        set_transient($cache_key, $map, 24 * HOUR_IN_SECONDS);
        return $map;
    }

    private function get_generator_card_format_rows(array $dbf_ids) {
        $dbf_ids = array_values(array_unique(array_filter(array_map('absint', $dbf_ids))));
        if (empty($dbf_ids)) {
            return array();
        }

        $all_rows = $this->get_generator_all_card_format_rows();
        if (empty($all_rows)) {
            return array();
        }

        $rows = array();
        foreach ($dbf_ids as $dbf_id) {
            if (isset($all_rows[$dbf_id])) {
                $rows[$dbf_id] = $all_rows[$dbf_id];
            }
        }

        return $rows;
    }

    private function validate_standard_mode_with_generator(array $decoded, array $mode) {
        if (empty($mode['slug']) || sanitize_title($mode['slug']) !== 'standart' || empty($decoded['cards']) || !is_array($decoded['cards'])) {
            return $mode;
        }

        $card_rows = $this->get_generator_card_format_rows(array_keys($decoded['cards']));
        if (empty($card_rows)) {
            return $mode;
        }

        $standard_sets = $this->get_standard_deck_set_map();
        if (empty($standard_sets)) {
            return $mode;
        }
        foreach (array_keys($decoded['cards']) as $dbf_id) {
            $dbf_id = absint($dbf_id);
            if (!$dbf_id || empty($card_rows[$dbf_id])) {
                continue;
            }

            $set = isset($card_rows[$dbf_id]['set']) ? (string) $card_rows[$dbf_id]['set'] : '';
            $is_core = !empty($card_rows[$dbf_id]['is_core']);
            if ($is_core || ($set !== '' && isset($standard_sets[$set]))) {
                continue;
            }

            if ($set === '') {
                continue;
            }

            $wild_mode = $this->get_deck_mode_data_from_format(1);
            if (!empty($wild_mode)) {
                $wild_mode['source'] = 'generator_set_validation';
                $wild_mode['format_reason'] = 'non_standard_set:' . $set;
                return $wild_mode;
            }
        }

        return $mode;
    }

    private function get_deck_code_mode_data($deck_code, $post_id = 0) {
        $deck_code = trim((string) $deck_code);
        $post_id = absint($post_id);
        $runtime_key = md5($deck_code . '|' . $post_id);
        static $runtime = array();
        if (isset($runtime[$runtime_key])) {
            return $runtime[$runtime_key];
        }

        $mode = array();
        if ($deck_code !== '' && class_exists('Unified_HS_Deckstring_Helper')) {
            $decoded = Unified_HS_Deckstring_Helper::decode($deck_code);
            if (!is_wp_error($decoded) && isset($decoded['format'])) {
                $mode = $this->get_deck_mode_data_from_format($decoded['format']);
                if (!empty($mode)) {
                    $mode = $this->validate_standard_mode_with_generator($decoded, $mode);
                }
                if (!empty($mode)) {
                    if (empty($mode['source'])) {
                        $mode['source'] = 'deck_code';
                    }
                }
            }
        }

        if (empty($mode) && $post_id) {
            $terms = get_the_terms($post_id, 'deck_mode');
            if ($terms && !is_wp_error($terms)) {
                $term = reset($terms);
                if ($term && !is_wp_error($term)) {
                    $mode = array(
                        'term_id' => absint($term->term_id),
                        'slug' => (string) $term->slug,
                        'name' => (string) $term->name,
                        'source' => 'taxonomy_fallback',
                    );
                }
            }
        }

        $runtime[$runtime_key] = $mode;
        return $mode;
    }

    private function get_latest_archetype_mode_data($term_id) {
        $term_id = absint($term_id);
        if (!$term_id) {
            return array();
        }

        $cache_key = 'hs_arch_latest_mode_decoded_v3_' . $term_id . '_v' . self::get_decks_cache_version();
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT p.ID AS post_id, code.meta_value AS deck_code
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->term_relationships} tr_arch
                ON tr_arch.object_id = p.ID
             INNER JOIN {$wpdb->term_taxonomy} tt_arch
                ON tt_arch.term_taxonomy_id = tr_arch.term_taxonomy_id
             LEFT JOIN {$wpdb->postmeta} code
                ON code.post_id = p.ID
               AND code.meta_key = %s
             WHERE p.post_type = %s
                AND p.post_status = %s
                AND tt_arch.taxonomy = %s
                AND tt_arch.term_id = %d
                AND NOT EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} hidden
                    WHERE hidden.post_id = p.ID
                       AND hidden.meta_key = %s
                       AND hidden.meta_value = %s
                )
                AND NOT EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} archived
                    WHERE archived.post_id = p.ID
                       AND archived.meta_key = %s
                       AND archived.meta_value = %s
                )
             ORDER BY p.post_date DESC, p.ID DESC
             LIMIT 1",
            '_deck_code',
            'hs_deck',
            'publish',
            'deck_archetype',
            $term_id,
            '_hide_from_feed',
            '1',
            '_hs_deck_archived',
            '1'
        ), ARRAY_A);

        $data = empty($row) ? array() : $this->get_deck_code_mode_data(
            isset($row['deck_code']) ? (string) $row['deck_code'] : '',
            isset($row['post_id']) ? absint($row['post_id']) : 0
        );

        set_transient($cache_key, $data, 12 * HOUR_IN_SECONDS);
        return $data;
    }

    private function get_archetype_page_mode_data($term_id, $mode_slug = '') {
        $mode = $this->get_deck_mode_term_data($mode_slug);
        if (!empty($mode)) {
            return $mode;
        }

        return $this->get_latest_archetype_mode_data($term_id);
    }

    private function get_archetype_mode_url($url, $mode_slug) {
        $mode_slug = sanitize_title((string) $mode_slug);
        if ($mode_slug === '') {
            return $url;
        }

        return add_query_arg('hs_mode', $mode_slug, $url);
    }

    private function get_archetype_index_mode_entries(array $terms) {
        if (empty($terms)) {
            return array();
        }

        $term_map = array();
        $term_ids = array();
        foreach ($terms as $term) {
            if (!$term || is_wp_error($term) || empty($term->term_id)) {
                continue;
            }
            $term_id = absint($term->term_id);
            $term_ids[] = $term_id;
            $term_map[$term_id] = $term;
        }

        $term_ids = array_values(array_unique(array_filter($term_ids)));
        if (empty($term_ids)) {
            return array();
        }

        $cache_key = 'hs_arch_mode_entries_decoded_v3_' . md5(implode(',', $term_ids) . '|v' . self::get_decks_cache_version());
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;
        $ids_sql = implode(',', array_map('absint', $term_ids));
        $rows = $wpdb->get_results(
            "SELECT
                tt_arch.term_id AS term_id,
                p.ID AS post_id,
                p.post_date AS latest_date,
                code.meta_value AS deck_code
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->term_relationships} tr_arch
                ON tr_arch.object_id = p.ID
             INNER JOIN {$wpdb->term_taxonomy} tt_arch
                ON tt_arch.term_taxonomy_id = tr_arch.term_taxonomy_id
             LEFT JOIN {$wpdb->postmeta} code
                ON code.post_id = p.ID
               AND code.meta_key = '_deck_code'
             WHERE p.post_type = 'hs_deck'
                AND p.post_status = 'publish'
                AND tt_arch.taxonomy = 'deck_archetype'
                AND tt_arch.term_id IN ({$ids_sql})
                AND NOT EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} hidden
                    WHERE hidden.post_id = p.ID
                       AND hidden.meta_key = '_hide_from_feed'
                       AND hidden.meta_value = '1'
                )
                AND NOT EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} archived
                    WHERE archived.post_id = p.ID
                       AND archived.meta_key = '_hs_deck_archived'
                       AND archived.meta_value = '1'
                )
             ORDER BY p.post_date DESC, p.ID DESC",
            ARRAY_A
        );

        $entries = array();
        foreach ((array) $rows as $row) {
            $term_id = absint($row['term_id']);
            if (empty($term_map[$term_id])) {
                continue;
            }
            $term = $term_map[$term_id];
            $mode = $this->get_deck_code_mode_data(
                isset($row['deck_code']) ? (string) $row['deck_code'] : '',
                isset($row['post_id']) ? absint($row['post_id']) : 0
            );
            $mode_slug = !empty($mode['slug']) ? sanitize_title($mode['slug']) : '';
            $group_key = $term_id . ':' . $mode_slug;
            if (!isset($entries[$group_key])) {
                $entries[$group_key] = array(
                    'term_id' => $term_id,
                    'term_name' => (string) $term->name,
                    'term_slug' => (string) $term->slug,
                    'term_count' => absint($term->count),
                    'mode_id' => !empty($mode['term_id']) ? absint($mode['term_id']) : 0,
                    'mode_slug' => $mode_slug,
                    'mode_name' => !empty($mode['name']) ? (string) $mode['name'] : '',
                    'mode_source' => !empty($mode['source']) ? (string) $mode['source'] : '',
                    'deck_count' => 0,
                    'latest_date' => isset($row['latest_date']) ? (string) $row['latest_date'] : '',
                );
            }
            $entries[$group_key]['deck_count']++;
            if ((string) $row['latest_date'] > (string) $entries[$group_key]['latest_date']) {
                $entries[$group_key]['latest_date'] = (string) $row['latest_date'];
            }
        }

        $entries = array_values($entries);
        usort($entries, static function($a, $b) {
            if ((int) $a['deck_count'] !== (int) $b['deck_count']) {
                return (int) $b['deck_count'] <=> (int) $a['deck_count'];
            }
            if ((string) $a['latest_date'] !== (string) $b['latest_date']) {
                return strcmp((string) $b['latest_date'], (string) $a['latest_date']);
            }
            return strnatcasecmp((string) $a['term_name'], (string) $b['term_name']);
        });

        set_transient($cache_key, $entries, 12 * HOUR_IN_SECONDS);
        return $entries;
    }

    private function get_archetype_deck_ids_for_decoded_mode($archetype, $mode_slug) {
        $mode_slug = sanitize_title((string) $mode_slug);
        if ($mode_slug === '') {
            return array();
        }

        if (is_numeric($archetype)) {
            $term = get_term(absint($archetype), 'deck_archetype');
        } else {
            $term = get_term_by('slug', sanitize_title((string) $archetype), 'deck_archetype');
        }
        if (!$term || is_wp_error($term)) {
            return array();
        }

        $term_id = absint($term->term_id);
        $cache_key = 'hs_arch_decoded_mode_ids_v3_' . $term_id . '_' . md5($mode_slug) . '_v' . self::get_decks_cache_version();
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return array_values(array_filter(array_map('absint', $cached)));
        }

        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID AS post_id, code.meta_value AS deck_code
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->term_relationships} tr_arch
                ON tr_arch.object_id = p.ID
             INNER JOIN {$wpdb->term_taxonomy} tt_arch
                ON tt_arch.term_taxonomy_id = tr_arch.term_taxonomy_id
             LEFT JOIN {$wpdb->postmeta} code
                ON code.post_id = p.ID
               AND code.meta_key = %s
             WHERE p.post_type = %s
                AND p.post_status = %s
                AND tt_arch.taxonomy = %s
                AND tt_arch.term_id = %d
                AND NOT EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} hidden
                    WHERE hidden.post_id = p.ID
                       AND hidden.meta_key = %s
                       AND hidden.meta_value = %s
                )
                AND NOT EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} archived
                    WHERE archived.post_id = p.ID
                       AND archived.meta_key = %s
                       AND archived.meta_value = %s
                )
             ORDER BY p.post_date DESC, p.ID DESC",
            '_deck_code',
            'hs_deck',
            'publish',
            'deck_archetype',
            $term_id,
            '_hide_from_feed',
            '1',
            '_hs_deck_archived',
            '1'
        ), ARRAY_A);

        $ids = array();
        foreach ((array) $rows as $row) {
            $post_id = isset($row['post_id']) ? absint($row['post_id']) : 0;
            if (!$post_id) {
                continue;
            }
            $mode = $this->get_deck_code_mode_data(isset($row['deck_code']) ? (string) $row['deck_code'] : '', $post_id);
            if (!empty($mode['slug']) && sanitize_title($mode['slug']) === $mode_slug) {
                $ids[] = $post_id;
            }
        }

        $ids = array_values(array_unique(array_filter(array_map('absint', $ids))));
        set_transient($cache_key, $ids, 12 * HOUR_IN_SECONDS);
        return $ids;
    }

    private function get_archetype_primary_class_data($term_id) {
        $term_id = absint($term_id);
        if (!$term_id) {
            return array();
        }

        $cache_key = 'hs_arch_class_' . $term_id . '_v' . self::get_decks_cache_version();
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT t.slug, t.name, COUNT(*) AS deck_count
             FROM {$wpdb->term_relationships} tr_arch
             INNER JOIN {$wpdb->term_taxonomy} tt_arch
                ON tt_arch.term_taxonomy_id = tr_arch.term_taxonomy_id
             INNER JOIN {$wpdb->posts} p
                ON p.ID = tr_arch.object_id
             INNER JOIN {$wpdb->term_relationships} tr_class
                ON tr_class.object_id = tr_arch.object_id
             INNER JOIN {$wpdb->term_taxonomy} tt_class
                ON tt_class.term_taxonomy_id = tr_class.term_taxonomy_id
             INNER JOIN {$wpdb->terms} t
                ON t.term_id = tt_class.term_id
             WHERE tt_arch.taxonomy = %s
                AND tt_arch.term_id = %d
                AND tt_class.taxonomy = %s
                AND p.post_type = %s
                AND p.post_status = %s
             GROUP BY t.term_id
             ORDER BY deck_count DESC, t.name ASC
             LIMIT 1",
            'deck_archetype',
            $term_id,
            'deck_class',
            'hs_deck',
            'publish'
        ), ARRAY_A);

        if (empty($row)) {
            set_transient($cache_key, array(), 6 * HOUR_IN_SECONDS);
            return array();
        }

        $data = array(
            'slug' => isset($row['slug']) ? (string) $row['slug'] : '',
            'name' => isset($row['name']) ? (string) $row['name'] : '',
        );
        $data['icon_url'] = $this->get_deck_class_icon_url(array($data['slug']), array($data['name']));

            set_transient($cache_key, $data, 12 * HOUR_IN_SECONDS);
        return $data;
    }

    private function get_tooltip_card_asset_id(array $entry, $fallback = '') {
        $img = isset($entry['img']) ? (string) $entry['img'] : '';
        if ($img !== '') {
            $path = wp_parse_url($img, PHP_URL_PATH);
            if (is_string($path) && preg_match('#/([^/]+)\.(?:png|webp|jpg|jpeg)$#i', $path, $matches)) {
                $asset_id = sanitize_file_name($matches[1]);
                if ($asset_id !== '' && !ctype_digit($asset_id)) {
                    return $asset_id;
                }
            }
        }

        $fallback = sanitize_file_name((string) $fallback);
        return ctype_digit($fallback) ? '' : $fallback;
    }

    private function get_tooltip_card_data($dbf_id) {
        static $bundle = null;

        $dbf_id = absint($dbf_id);
        if (!$dbf_id
            || !function_exists('hs_smart_tooltip_get_dictionary')
            || !function_exists('hs_smart_tooltip_resolve_card_id')
            || !function_exists('hs_smart_tooltip_normalize_entry')
        ) {
            return array();
        }

        if ($bundle === null) {
            $bundle = hs_smart_tooltip_get_dictionary(false);
        }

        $by_id = isset($bundle['by_id']) && is_array($bundle['by_id']) ? $bundle['by_id'] : array();
        if (empty($by_id)) {
            return array();
        }

        $resolved = hs_smart_tooltip_resolve_card_id($by_id, (string) $dbf_id);
        if (!$resolved || empty($by_id[$resolved])) {
            return array();
        }

        $entry = hs_smart_tooltip_normalize_entry($by_id[$resolved]);
        if (empty($entry['img'])) {
            return array();
        }

        $asset_id = $this->get_tooltip_card_asset_id($entry, $resolved);
        if ($asset_id === '') {
            return array();
        }

        $art_id = rawurlencode($asset_id);
        $tile_image = 'https://art.hearthstonejson.com/v1/tiles/' . $art_id . '.webp';
        $background_image = 'https://art.hearthstonejson.com/v1/256x/' . $art_id . '.webp';
        $raw_image = (string) $entry['img'];
        $display_image = function_exists('hs_smart_tooltip_image_proxy_url')
            ? hs_smart_tooltip_image_proxy_url($background_image)
            : $background_image;
        $fallback_image = function_exists('hs_smart_tooltip_image_proxy_url')
            ? hs_smart_tooltip_image_proxy_url($tile_image)
            : $tile_image;

        return array(
            'dbf_id' => $dbf_id,
            'card_id' => $resolved,
            'asset_id' => $asset_id,
            'name' => isset($entry['name']) ? (string) $entry['name'] : '',
            'image' => $display_image,
            'art_image' => $display_image,
            'art_image_raw' => $background_image,
            'raw_image' => $raw_image,
            'fallback_image' => $fallback_image,
            'tile_image' => $tile_image,
            'rarity' => isset($entry['rarity']) ? (string) $entry['rarity'] : 'common',
            'set_icon' => isset($entry['set_icon']) ? (string) $entry['set_icon'] : '',
        );
    }

    private function get_archetype_preview_cards($term_id, $limit = 5, $mode_slug = '') {
        $term_id = absint($term_id);
        $limit = max(1, min(8, absint($limit)));
        $mode_slug = sanitize_title((string) $mode_slug);
        if (!$term_id) {
            return array();
        }

        $cache_key = 'hs_arch_cards_img5_' . $term_id . '_' . $limit . '_' . md5($mode_slug) . '_v' . self::get_decks_cache_version();
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $tax_query = array(
            array(
                'taxonomy' => 'deck_archetype',
                'field' => 'term_id',
                'terms' => $term_id,
            ),
        );
        $post__in = array();
        if ($mode_slug !== '') {
            $post__in = $this->get_archetype_deck_ids_for_decoded_mode($term_id, $mode_slug);
            if (empty($post__in)) {
                set_transient($cache_key, array(), 12 * HOUR_IN_SECONDS);
                return array();
            }
        }

        $deck_query_args = array(
            'post_type' => 'hs_deck',
            'post_status' => 'publish',
            'fields' => 'ids',
            'posts_per_page' => 12,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
            'tax_query' => $tax_query,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'relation' => 'OR',
                    array('key' => '_hide_from_feed', 'compare' => 'NOT EXISTS'),
                    array('key' => '_hide_from_feed', 'value' => '1', 'compare' => '!='),
                ),
                array(
                    'relation' => 'OR',
                    array('key' => '_hs_deck_archived', 'compare' => 'NOT EXISTS'),
                    array('key' => '_hs_deck_archived', 'value' => '1', 'compare' => '!='),
                ),
            ),
        );
        if (!empty($post__in)) {
            $deck_query_args['post__in'] = $post__in;
        }

        $deck_ids = get_posts($deck_query_args);

        $scores = array();
        if (!empty($deck_ids) && class_exists('Unified_HS_Deckstring_Helper')) {
            update_meta_cache('post', $deck_ids);
            foreach ($deck_ids as $deck_id) {
                $deck_code = get_post_meta($deck_id, '_deck_code', true);
                if ($deck_code === '') {
                    continue;
                }
                $decoded = Unified_HS_Deckstring_Helper::decode($deck_code);
                if (is_wp_error($decoded) || empty($decoded['cards']) || !is_array($decoded['cards'])) {
                    continue;
                }
                foreach ($decoded['cards'] as $dbf_id => $quantity) {
                    $dbf_id = absint($dbf_id);
                    $quantity = absint($quantity);
                    if (!$dbf_id || !$quantity) {
                        continue;
                    }
                    if (!isset($scores[$dbf_id])) {
                        $scores[$dbf_id] = 0;
                    }
                    $scores[$dbf_id] += $quantity;
                }
            }
        }

        if (empty($scores)) {
            set_transient($cache_key, array(), 12 * HOUR_IN_SECONDS);
            return array();
        }

        arsort($scores, SORT_NUMERIC);
        $cards = array();
        foreach (array_keys($scores) as $dbf_id) {
            $card = $this->get_tooltip_card_data($dbf_id);
            if (empty($card)) {
                continue;
            }
            $card['score'] = $scores[$dbf_id];
            $cards[] = $card;
            if (count($cards) >= $limit) {
                break;
            }
        }

        set_transient($cache_key, $cards, 12 * HOUR_IN_SECONDS);
        return $cards;
    }

    private function render_archetype_preview_card_icon(array $card) {
        $name = isset($card['name']) && $card['name'] !== '' ? $card['name'] : ('DBF ' . absint($card['dbf_id']));
        $image = !empty($card['image']) ? (string) $card['image'] : '';
        $fallback = !empty($card['fallback_image']) ? (string) $card['fallback_image'] : (!empty($card['raw_image']) ? (string) $card['raw_image'] : '');
        $label = '<span class="hs-archetype-row-card-art"';
        if ($image !== '') {
            $label .= ' style="background-image:url(' . esc_url($image) . ')"';
        }
        $label .= '>';
        if ($image !== '') {
            $label .= '<img src="' . esc_url($image) . '"';
            if ($fallback !== '') {
                $label .= ' data-fallback="' . esc_url($fallback) . '"';
            }
            $label .= ' alt="' . esc_attr($name) . '" loading="eager" decoding="async">';
        }
        $label .= '</span><span class="screen-reader-text">' . esc_html($name) . '</span>';

        if (!empty($card['raw_image'])) {
            $tooltip_image = function_exists('hs_smart_tooltip_image_proxy_url')
                ? hs_smart_tooltip_image_proxy_url((string) $card['raw_image'])
                : (string) $card['raw_image'];
            $rarity = isset($card['rarity']) ? (string) $card['rarity'] : 'common';
            if (!in_array($rarity, array('common', 'rare', 'epic', 'legendary'), true)) {
                $rarity = 'common';
            }

            return '<span class="hs-archetype-row-card"><span class="hs-card-tooltip hs-rarity-' . esc_attr($rarity) .
                '" data-image="' . esc_url($tooltip_image) . '" data-image-raw="' . esc_url($card['raw_image']) . '">' .
                $label . '</span></span>';
        }

        return '<span class="hs-archetype-row-card hs-archetype-row-card--plain">' . $label . '</span>';
    }

    private function render_archetype_background_arts(array $cards) {
        if (empty($cards)) {
            return '';
        }

        $html = '<span class="hs-archetype-row__bg" aria-hidden="true">';
        foreach (array_slice($cards, 0, 3) as $card) {
            if (empty($card['art_image'])) {
                continue;
            }
            $html .= '<span class="hs-archetype-row__art-panel">';
            $html .= '<span class="hs-archetype-row__art" style="background-image:url(' . esc_url($card['art_image']) . ');"></span>';
            $html .= '</span>';
        }
        $html .= '</span>';

        return $html;
    }

    public function handle_archetypes_index_ajax() {
        check_ajax_referer('hs_decks_nonce', 'nonce');

        $search_key = 'hs_arch_search';
        $class_key = 'hs_arch_class';
        $page_key = 'hs_arch_page';
        $base_url = isset($_POST['base_url']) ? esc_url_raw(wp_unslash($_POST['base_url'])) : '';
        if ($base_url === '') {
            $base_url = wp_get_referer() ?: home_url('/');
        }

        $base_parts = wp_parse_url($base_url);
        $base_query = array();
        if (!empty($base_parts['query'])) {
            parse_str($base_parts['query'], $base_query);
        }

        $_GET = array_merge($base_query, array(
            $search_key => isset($_POST[$search_key]) ? sanitize_text_field(wp_unslash($_POST[$search_key])) : '',
            $class_key => isset($_POST[$class_key]) ? sanitize_title(wp_unslash($_POST[$class_key])) : '',
            $page_key => isset($_POST[$page_key]) ? max(1, absint($_POST[$page_key])) : 1,
        ));

        if ($_GET[$search_key] === '') {
            unset($_GET[$search_key]);
        }
        if ($_GET[$class_key] === '') {
            unset($_GET[$class_key]);
        }
        if (absint($_GET[$page_key]) <= 1) {
            unset($_GET[$page_key]);
        }

        $path = !empty($base_parts['path']) ? $base_parts['path'] : '/';
        $request_uri = add_query_arg($_GET, $path);
        $_SERVER['REQUEST_URI'] = $request_uri;

        $atts = array(
            'per_page' => isset($_POST['per_page']) ? max(1, min(48, absint($_POST['per_page']))) : 12,
            'limit' => isset($_POST['limit']) ? absint($_POST['limit']) : 0,
            'min_decks' => isset($_POST['min_decks']) ? max(1, absint($_POST['min_decks'])) : 5,
        );

        $html = $this->archetypes_shortcode($atts);
        wp_send_json_success(array(
            'html' => $html,
            'url' => home_url($request_uri),
        ));
    }

    public function archetypes_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 0,
            'per_page' => 12,
            'min_decks' => 5,
        ), $atts);

        $search_key = 'hs_arch_search';
        $class_key = 'hs_arch_class';
        $page_key = 'hs_arch_page';
        $search = isset($_GET[$search_key]) ? sanitize_text_field(wp_unslash($_GET[$search_key])) : '';
        $class_filter = isset($_GET[$class_key]) ? sanitize_title(wp_unslash($_GET[$class_key])) : '';
        $current_page = isset($_GET[$page_key]) ? max(1, absint($_GET[$page_key])) : 1;
        $per_page = max(1, min(48, absint($atts['per_page'])));
        $limit = absint($atts['limit']);
        $min_decks = max(1, absint($atts['min_decks']));
        $current_url = $this->get_current_archetypes_index_url();

        $this->enqueue_front_assets();

        $cache_ttl = (int) apply_filters('hs_deck_archetypes_index_cache_ttl', 12 * HOUR_IN_SECONDS);
        $cache_key = '';
        if ($cache_ttl > 0) {
            $cache_key = 'hs_arch_index_' . md5(wp_json_encode(array(
                'v' => self::get_decks_cache_version(),
                'plugin' => $this->get_frontend_version(''),
                'limit' => $limit,
                'min_decks' => $min_decks,
                'per_page' => $per_page,
                'search' => $search,
                'class' => $class_filter,
                'page' => $current_page,
                'url' => remove_query_arg($page_key, $current_url),
            )));
            $cached_html = get_transient($cache_key);
            if (is_string($cached_html) && $cached_html !== '') {
                return $cached_html;
            }
        }

        $terms = get_terms(array(
            'taxonomy' => 'deck_archetype',
            'hide_empty' => true,
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => 0,
        ));

        if (is_wp_error($terms) || empty($terms)) {
            return '<p class="no-decks">Архетипы пока не найдены.</p>';
        }

        if ($search !== '') {
            $needle = hs_mb_lower($search);
            $terms = array_values(array_filter($terms, function($term) use ($needle) {
                $haystack = hs_mb_lower($term->name . ' ' . $term->slug);
                return strpos($haystack, $needle) !== false;
            }));
        }

        if ($class_filter !== '') {
            $allowed_ids = $this->get_archetype_term_ids_for_class($class_filter);
            $allowed_map = array_fill_keys($allowed_ids, true);
            $terms = array_values(array_filter($terms, function($term) use ($allowed_map) {
                return isset($allowed_map[(int) $term->term_id]);
            }));
        }

        $entries = $this->get_archetype_index_mode_entries($terms);
        $entries = array_values(array_filter($entries, static function($entry) use ($min_decks) {
            return isset($entry['deck_count']) && absint($entry['deck_count']) >= $min_decks;
        }));
        if ($limit > 0 && count($entries) > $limit) {
            $entries = array_slice($entries, 0, $limit);
        }

        $total_terms = count($entries);
        $total_pages = max(1, (int) ceil($total_terms / $per_page));
        if ($current_page > $total_pages) {
            $current_page = $total_pages;
        }
        $visible_entries = array_slice($entries, ($current_page - 1) * $per_page, $per_page);
        $classes = get_terms(array(
            'taxonomy' => 'deck_class',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
        ));
        if (is_wp_error($classes)) {
            $classes = array();
        }

        $reset_url = remove_query_arg(array($search_key, $class_key, $page_key), $current_url);

        ob_start();
        ?>
        <section class="hs-archetypes-index hs-archetypes-index--rows" data-hs-archetypes data-per-page="<?php echo esc_attr($per_page); ?>" data-limit="<?php echo esc_attr($limit); ?>" data-min-decks="<?php echo esc_attr($min_decks); ?>">
            <form class="hs-archetypes-toolbar" method="get">
                <?php $this->render_archetype_preserved_query_inputs(array($search_key, $class_key, $page_key)); ?>
                <label class="hs-filter-field hs-filter-search">
                    <span>Поиск архетипа</span>
                    <input type="search" class="hs-archetypes-search" name="<?php echo esc_attr($search_key); ?>" value="<?php echo esc_attr($search); ?>" placeholder="Например: Маг, Паладин, Агро">
                </label>
                <label class="hs-filter-field hs-filter-class">
                    <span>Класс</span>
                    <select name="<?php echo esc_attr($class_key); ?>">
                        <option value="">Все классы</option>
                        <?php foreach ($classes as $class_term): ?>
                            <option value="<?php echo esc_attr($class_term->slug); ?>"<?php selected($class_filter, $class_term->slug); ?>>
                                <?php echo esc_html($class_term->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="hs-archetypes-submit">Найти</button>
                <?php if ($search !== '' || $class_filter !== ''): ?>
                    <a class="hs-archetypes-reset" href="<?php echo esc_url($reset_url); ?>">Сброс</a>
                <?php endif; ?>
            </form>

            <div class="hs-archetypes-summary">
                <span><?php echo esc_html(number_format_i18n($total_terms)); ?> вариантов архетипов</span>
                <?php if ($total_terms > 0): ?>
                    <span>Страница <?php echo esc_html(number_format_i18n($current_page)); ?> из <?php echo esc_html(number_format_i18n($total_pages)); ?></span>
                <?php endif; ?>
            </div>

            <?php if (empty($visible_entries)): ?>
                <p class="no-decks">По этим фильтрам архетипы не найдены.</p>
            <?php else: ?>
                <div class="hs-archetype-rows">
                    <?php foreach ($visible_entries as $entry): ?>
                        <?php
                        $term_id = absint($entry['term_id']);
                        $term_name = isset($entry['term_name']) ? (string) $entry['term_name'] : '';
                        $term_slug = isset($entry['term_slug']) ? (string) $entry['term_slug'] : '';
                        $mode_slug = isset($entry['mode_slug']) ? sanitize_title($entry['mode_slug']) : '';
                        $mode_name = isset($entry['mode_name']) ? (string) $entry['mode_name'] : '';
                        $deck_count = isset($entry['deck_count']) ? absint($entry['deck_count']) : 0;
                        $url = get_term_link($term_id, 'deck_archetype');
                        if (is_wp_error($url)) {
                            $url = add_query_arg('hs_archetype', $term_slug, get_permalink());
                        }
                        $url = $this->get_archetype_mode_url($url, $mode_slug);
                        $preview_cards = $this->get_archetype_preview_cards($term_id, 5, $mode_slug);
                        $class_data = $this->get_archetype_primary_class_data($term_id);
                        $search_text = trim($term_name . ' ' . $term_slug . ' ' . $mode_name . ' ' . $mode_slug);
                        $cards_label = sprintf(
                            'Ключевые карты архетипа %s%s',
                            $term_name,
                            $mode_name !== '' ? ' — ' . $mode_name : ''
                        );
                        ?>
                        <article class="hs-archetype-card hs-archetype-row" data-search="<?php echo esc_attr(hs_mb_lower($search_text)); ?>" data-mode="<?php echo esc_attr($mode_slug); ?>">
                            <?php echo $this->render_archetype_background_arts($preview_cards); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <span class="hs-archetype-row__content">
                                <?php if (!empty($class_data['icon_url'])): ?>
                                    <img class="hs-archetype-row__class-icon" src="<?php echo esc_url($class_data['icon_url']); ?>" alt="<?php echo esc_attr($class_data['name']); ?>" loading="lazy" decoding="async">
                                <?php endif; ?>
                                <span class="hs-archetype-row__main">
                                    <a class="hs-archetype-row__title" href="<?php echo esc_url($url); ?>"><?php echo esc_html($term_name); ?></a>
                                    <span class="hs-archetype-row__meta-row">
                                        <span class="hs-archetype-row__meta"><?php echo esc_html(number_format_i18n($deck_count)); ?> колод</span>
                                        <?php if ($mode_name !== ''): ?>
                                            <span class="hs-archetype-row__mode hs-archetype-row__mode--<?php echo esc_attr($mode_slug); ?>"><?php echo esc_html($mode_name); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </span>
                                <span class="hs-archetype-row__cards" aria-label="<?php echo esc_attr($cards_label); ?>">
                                    <?php if (!empty($preview_cards)): ?>
                                        <?php foreach ($preview_cards as $card): ?>
                                            <?php echo $this->render_archetype_preview_card_icon($card); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="hs-archetype-row__empty">Карты скоро появятся</span>
                                    <?php endif; ?>
                                </span>
                            </span>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($total_pages > 1): ?>
                <?php
                $pagination_base = str_replace(
                    999999999,
                    '%#%',
                    add_query_arg($page_key, 999999999, remove_query_arg($page_key, $current_url))
                );
                $pagination_links = paginate_links(array(
                    'base' => $pagination_base,
                    'format' => '',
                    'current' => $current_page,
                    'total' => $total_pages,
                    'type' => 'array',
                    'prev_text' => 'Назад',
                    'next_text' => 'Вперёд',
                ));
                ?>
                <?php if (!empty($pagination_links)): ?>
                    <nav class="hs-archetype-pagination" aria-label="Пагинация архетипов">
                        <?php foreach ($pagination_links as $link): ?>
                            <?php echo wp_kses_post($link); ?>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
        <?php
        $html = ob_get_clean();
        if ($cache_key !== '' && $cache_ttl > 0) {
            set_transient($cache_key, $html, $cache_ttl);
        }
        return $html;
    }

    public function archetype_shortcode($atts) {
        $default_per_page = (int) get_option('hs_decks_per_page', self::DEFAULT_PER_PAGE);
        if ($default_per_page < 1) {
            $default_per_page = self::DEFAULT_PER_PAGE;
        }

        $atts = shortcode_atts(array(
            'slug' => '',
            'per_page' => $default_per_page,
            'ads' => '1',
        ), $atts);

        $slug = sanitize_title($atts['slug']);
        if ($slug === '' && is_tax('deck_archetype')) {
            $term = get_queried_object();
            $slug = $term && !is_wp_error($term) ? $term->slug : '';
        }

        if ($slug === '') {
            return '<p class="no-decks">Укажите slug архетипа.</p>';
        }

        $term = get_term_by('slug', $slug, 'deck_archetype');
        if (!$term || is_wp_error($term)) {
            return '<p class="no-decks">Архетип не найден.</p>';
        }

        $filters = $this->read_deck_feed_filters(array(), $_GET);
        $filters['archetype'] = $term->slug;
        $mode_data = $this->get_archetype_page_mode_data($term->term_id, $filters['mode']);
        $filters['mode'] = !empty($mode_data['slug']) ? sanitize_title($mode_data['slug']) : '';
        $per_page = max(1, absint($atts['per_page']));

        if (class_exists('Unified_HS_Deck_Events')) {
            Unified_HS_Deck_Events::record(0, Unified_HS_Deck_Events::EVENT_ARCHETYPE_OPEN, array('term_id' => $term->term_id, 'slug' => $term->slug), 'shortcode');
        }

        $this->enqueue_front_assets();

        $cache_ttl = $this->cap_vote_sort_cache_ttl(
            (int) apply_filters('hs_deck_archetype_page_cache_ttl', 12 * HOUR_IN_SECONDS),
            $filters
        );
        $cache_key = '';
        if ($cache_ttl > 0) {
            $cache_key = 'hs_arch_page_' . md5(wp_json_encode(array(
                'v' => self::get_decks_cache_version(),
                'plugin' => $this->get_frontend_version(''),
                'term' => $term->term_id,
                'slug' => $term->slug,
                'filters' => $this->normalize_deck_feed_filters_for_cache($filters),
                'per_page' => $per_page,
                'atts' => $atts,
            )));
            $cached_html = get_transient($cache_key);
            if (is_string($cached_html) && $cached_html !== '') {
                return $cached_html;
            }
        }

        $feed = $this->render_decks_feed($filters, $per_page, $atts);

        $image_id = absint(get_term_meta($term->term_id, '_hs_archetype_image_id', true));
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'hs_archetype_hero') : '';
        if (!$image_url && $image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'large');
        }

        ob_start();
        ?>
        <section class="hs-archetype-page" data-archetype="<?php echo esc_attr($term->slug); ?>" data-mode="<?php echo esc_attr($filters['mode']); ?>">
            <header class="hs-archetype-page__header<?php echo $image_url ? ' has-image' : ''; ?>">
                <?php if ($image_url): ?>
                    <span class="hs-archetype-page__media" style="background-image:url(<?php echo esc_url($image_url); ?>);"></span>
                <?php endif; ?>
                <span class="hs-archetype-page__heading">
                    <h2><?php echo esc_html($term->name); ?></h2>
                    <?php if (!empty($mode_data['name'])): ?>
                        <span class="hs-archetype-page__mode hs-archetype-row__mode hs-archetype-row__mode--<?php echo esc_attr($filters['mode']); ?>"><?php echo esc_html($mode_data['name']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($term->description)): ?>
                        <p><?php echo esc_html($term->description); ?></p>
                    <?php endif; ?>
                </span>
            </header>
            <div class="hs-decks-grid" id="decks-grid" data-server-feed="1" data-per-page="<?php echo esc_attr($per_page); ?>" data-archetype="<?php echo esc_attr($term->slug); ?>" data-mode="<?php echo esc_attr($filters['mode']); ?>">
                <?php echo $feed['html']; ?>
            </div>
            <div class="hs-load-more-container" id="hs-load-more"><?php echo $feed['pagination']; ?></div>
        </section>
        <?php
        $html = ob_get_clean();
        if ($cache_key !== '' && $cache_ttl > 0) {
            set_transient($cache_key, $html, $cache_ttl);
        }
        return $html;
    }

    public function template_include_archetype_archive($template) {
        if (!is_tax('deck_archetype')) {
            return $template;
        }

        $custom_template = UNIFIED_HS_PLUGINS_DIR . 'templates/taxonomy-deck-archetype.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }

        return $template;
    }

    public function filter_wp_robots($robots) {
        if (is_singular('hs_deck')) {
            unset($robots['index']);
            $robots['noindex'] = true;
            $robots['follow'] = true;
        }

        if (is_tax('deck_archetype')) {
            unset($robots['noindex']);
            $robots['index'] = true;
            $robots['follow'] = true;
        }

        return $robots;
    }

    public function filter_aioseo_robots_meta($attributes) {
        $attributes = is_array($attributes) ? $attributes : array();

        if (is_singular('hs_deck')) {
            unset($attributes['index'], $attributes['nofollow']);
            $attributes['noindex'] = 'noindex';
            $attributes['follow'] = 'follow';
        }

        if (is_tax('deck_archetype')) {
            unset($attributes['noindex'], $attributes['nofollow']);
            $attributes['index'] = 'index';
            $attributes['follow'] = 'follow';
        }

        return $attributes;
    }

    public function render_deck_single_noindex_meta() {
        if (!is_singular('hs_deck')) {
            return;
        }
        echo '<meta name="robots" content="noindex, follow">' . "\n";
    }

    private function get_current_archetype_term_for_seo() {
        if (!is_tax('deck_archetype')) {
            return null;
        }

        $term = get_queried_object();
        if (!$term || is_wp_error($term) || empty($term->term_id)) {
            return null;
        }

        return $term;
    }

    private function normalize_archetype_aliases(array $aliases) {
        $normalized = array();
        $seen = array();

        foreach ($aliases as $alias) {
            if (is_array($alias)) {
                $normalized = array_merge($normalized, $this->normalize_archetype_aliases($alias));
                continue;
            }

            foreach (preg_split('/[,;|]+/u', (string) $alias) as $part) {
                $part = trim(wp_strip_all_tags($part));
                if ($part === '') {
                    continue;
                }
                $key = $this->display_token_key($part);
                if ($key === '' || isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $normalized[] = $part;
            }
        }

        usort($normalized, static function($a, $b) {
            return strlen((string) $b) <=> strlen((string) $a);
        });

        return array_values($normalized);
    }

    private function fetch_blizzcore_archetype_aliases($ru_name) {
        $ru_name = trim((string) $ru_name);
        if ($ru_name === '') {
            return array();
        }

        $url = add_query_arg(array(
            'search' => $ru_name,
            'limit' => 20,
        ), 'http://127.0.0.1:5000/deckview-api/v1/archetypes');

        $response = wp_remote_get($url, array(
            'timeout' => 0.8,
            'redirection' => 0,
            'user-agent' => 'hs-manacost-archetype-seo/1.0',
        ));
        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            return array();
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['items']) || !is_array($data['items'])) {
            return array();
        }

        $ru_key = $this->display_token_key($ru_name);
        $exact = array();
        $loose = array();
        foreach ($data['items'] as $item) {
            $name_en = isset($item['name_en']) ? trim((string) $item['name_en']) : '';
            $name_ru = isset($item['name_ru']) ? trim((string) $item['name_ru']) : '';
            if ($name_en === '' || $name_ru === '') {
                continue;
            }

            $item_key = $this->display_token_key($name_ru);
            if ($item_key === $ru_key) {
                $exact[] = $name_en;
            } elseif ($ru_key !== '' && $item_key !== '' && (strpos($item_key, $ru_key) !== false || strpos($ru_key, $item_key) !== false)) {
                $loose[] = $name_en;
            }
        }

        return $this->normalize_archetype_aliases(!empty($exact) ? $exact : $loose);
    }

    private function get_archetype_english_aliases($term) {
        if (!$term || is_wp_error($term) || empty($term->term_id)) {
            return array();
        }

        static $runtime = array();
        $term_id = absint($term->term_id);
        if (isset($runtime[$term_id])) {
            return $runtime[$term_id];
        }

        $cache_key = 'hs_arch_en_aliases_' . $term_id . '_' . md5((string) $term->name . '|' . (string) $term->slug);
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            $runtime[$term_id] = $cached;
            return $cached;
        }

        $aliases = array();
        foreach (array('_hs_archetype_name_en', '_hs_archetype_en', '_hs_archetype_names_en', '_hs_archetype_english_names') as $meta_key) {
            $aliases = array_merge($aliases, (array) get_term_meta($term_id, $meta_key, false));
        }

        $aliases = array_merge($aliases, $this->fetch_blizzcore_archetype_aliases($term->name));
        $aliases = array_slice($this->normalize_archetype_aliases($aliases), 0, 5);

        set_transient($cache_key, $aliases, !empty($aliases) ? DAY_IN_SECONDS : 2 * HOUR_IN_SECONDS);
        $runtime[$term_id] = $aliases;
        return $aliases;
    }

    private function get_archetype_seo_image($term_id, array $preview_cards) {
        $term_id = absint($term_id);
        $image_id = $term_id ? absint(get_term_meta($term_id, '_hs_archetype_image_id', true)) : 0;
        if ($image_id) {
            $url = wp_get_attachment_image_url($image_id, 'large');
            if ($url) {
                return $url;
            }
        }

        foreach ($preview_cards as $card) {
            if (!empty($card['raw_image'])) {
                return (string) $card['raw_image'];
            }
            if (!empty($card['art_image'])) {
                return (string) $card['art_image'];
            }
        }

        return '';
    }

    private function get_archetype_seo_deck_items($term_id, $limit = 8, $mode_slug = '') {
        $term_id = absint($term_id);
        $limit = max(1, min(12, absint($limit)));
        $mode_slug = sanitize_title((string) $mode_slug);
        if (!$term_id) {
            return array();
        }

        $cache_key = 'hs_arch_seo_decks_' . $term_id . '_' . $limit . '_' . md5($mode_slug) . '_v' . self::get_decks_cache_version();
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $tax_query = array(
            array(
                'taxonomy' => 'deck_archetype',
                'field' => 'term_id',
                'terms' => $term_id,
            ),
        );
        $post__in = array();
        if ($mode_slug !== '') {
            $post__in = $this->get_archetype_deck_ids_for_decoded_mode($term_id, $mode_slug);
            if (empty($post__in)) {
                set_transient($cache_key, array(), 12 * HOUR_IN_SECONDS);
                return array();
            }
        }

        $query_args = array(
            'post_type' => 'hs_deck',
            'post_status' => 'publish',
            'fields' => 'ids',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
            'tax_query' => $tax_query,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'relation' => 'OR',
                    array('key' => '_hide_from_feed', 'compare' => 'NOT EXISTS'),
                    array('key' => '_hide_from_feed', 'value' => '1', 'compare' => '!='),
                ),
                array(
                    'relation' => 'OR',
                    array('key' => '_hs_deck_archived', 'compare' => 'NOT EXISTS'),
                    array('key' => '_hs_deck_archived', 'value' => '1', 'compare' => '!='),
                ),
            ),
        );
        if (!empty($post__in)) {
            $query_args['post__in'] = $post__in;
        }

        $ids = get_posts($query_args);

        $items = array();
        foreach ($ids as $post_id) {
            $post_id = absint($post_id);
            $title = get_the_title($post_id);
            $url = get_permalink($post_id);
            if ($title === '' || !$url) {
                continue;
            }
            $items[] = array(
                'name' => wp_strip_all_tags($title),
                'url' => $url,
            );
        }

        set_transient($cache_key, $items, 12 * HOUR_IN_SECONDS);
        return $items;
    }

    private function trim_seo_text($text, $max_length) {
        $text = preg_replace('/\s+/u', ' ', trim(wp_strip_all_tags((string) $text)));
        $max_length = max(20, absint($max_length));
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') <= $max_length) {
                return $text;
            }
            return rtrim(mb_substr($text, 0, $max_length - 1, 'UTF-8'), " \t\n\r\0\x0B.,;:") . '…';
        }
        if (strlen($text) <= $max_length) {
            return $text;
        }
        return rtrim(substr($text, 0, $max_length - 1), " \t\n\r\0\x0B.,;:") . '…';
    }

    private function get_archetype_seo_data($term = null) {
        if (!$term) {
            $term = $this->get_current_archetype_term_for_seo();
        }
        if (!$term || is_wp_error($term)) {
            return array();
        }

        static $runtime = array();
        $term_id = absint($term->term_id);
        $requested_mode_slug = isset($_GET['hs_mode']) ? sanitize_title(wp_unslash($_GET['hs_mode'])) : '';
        $mode_data = $this->get_archetype_page_mode_data($term_id, $requested_mode_slug);
        $mode_slug = !empty($mode_data['slug']) ? sanitize_title($mode_data['slug']) : '';
        $runtime_key = $term_id . ':' . $mode_slug;
        if (isset($runtime[$runtime_key])) {
            return $runtime[$runtime_key];
        }

        $url = get_term_link($term);
        if (is_wp_error($url)) {
            return array();
        }
        $url = $this->get_archetype_mode_url($url, $mode_slug);

        $aliases = $this->get_archetype_english_aliases($term);
        $primary_en = !empty($aliases) ? $aliases[0] : '';
        $class_data = $this->get_archetype_primary_class_data($term_id);
        $class_name = !empty($class_data['name']) ? (string) $class_data['name'] : '';
        $mode_name = !empty($mode_data['name']) ? (string) $mode_data['name'] : '';
        $preview_cards = $this->get_archetype_preview_cards($term_id, 5, $mode_slug);
        $card_names = array();
        foreach ($preview_cards as $card) {
            if (!empty($card['name'])) {
                $card_names[] = (string) $card['name'];
            }
        }
        $card_names = array_slice(array_values(array_unique($card_names)), 0, 5);
        $deck_count = absint($term->count);
        if ($mode_slug !== '') {
            foreach ($this->get_archetype_index_mode_entries(array($term)) as $entry) {
                if (!empty($entry['mode_slug']) && sanitize_title($entry['mode_slug']) === $mode_slug) {
                    $deck_count = absint($entry['deck_count']);
                    break;
                }
            }
        }
        $count_text = $deck_count > 0 ? number_format_i18n($deck_count) . ' колод' : 'актуальные колоды';
        $alias_title = $primary_en !== '' ? ' (' . $primary_en . ')' : '';
        $mode_title = $mode_name !== '' ? ' — ' . $mode_name : '';
        $title = $this->trim_seo_text(sprintf('%s%s%s: лучшие колоды Hearthstone, коды и winrate', $term->name, $mode_title, $alias_title), 78);

        $description_parts = array();
        if (!empty($term->description)) {
            $description_parts[] = wp_strip_all_tags($term->description);
        } else {
            $description_parts[] = sprintf(
                '%s — %s Hearthstone%s%s с кодами, winrate, числом игр и свежими вариантами архетипа.',
                $term->name,
                $count_text,
                $class_name !== '' ? ' для класса ' . $class_name : '',
                $mode_name !== '' ? ' в режиме ' . $mode_name : ''
            );
        }
        if (!empty($aliases)) {
            $description_parts[] = 'English aliases: ' . implode(', ', $aliases) . '.';
        }
        if (!empty($card_names)) {
            $description_parts[] = 'Ключевые карты: ' . implode(', ', $card_names) . '.';
        }
        $description = $this->trim_seo_text(implode(' ', $description_parts), 245);

        $keywords = array_merge(
            array(
                (string) $term->name,
                (string) $term->name . ' колода',
                (string) $term->name . ' код колоды',
                'Hearthstone decks',
                'Hearthstone deck code',
                'топ колоды Hearthstone',
                'коды колод Hearthstone',
                'winrate Hearthstone',
            ),
            $aliases,
            $mode_name !== '' ? array($mode_name, $mode_name . ' Hearthstone') : array(),
            $mode_slug === 'standart' ? array('Standard Hearthstone', 'стандарт Hearthstone') : array(),
            $mode_slug === 'volnyj' ? array('Wild Hearthstone', 'вольный Hearthstone') : array(),
            $class_name !== '' ? array($class_name, $class_name . ' Hearthstone') : array(),
            $card_names
        );
        $keywords = $this->normalize_archetype_aliases($keywords);

        $deck_items = $this->get_archetype_seo_deck_items($term_id, 8, $mode_slug);
        $image = $this->get_archetype_seo_image($term_id, $preview_cards);

        $list_items = array();
        foreach ($deck_items as $index => $item) {
            $list_items[] = array(
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'url' => $item['url'],
            );
        }

        $graph = array(
            array(
                '@type' => 'WebSite',
                '@id' => home_url('/#website'),
                'name' => get_bloginfo('name'),
                'url' => home_url('/'),
                'inLanguage' => 'ru-RU',
            ),
            array(
                '@type' => 'CollectionPage',
                '@id' => $url . '#webpage',
                'url' => $url,
                'name' => $title,
                'alternateName' => array_values(array_filter(array_merge(array((string) $term->name), $aliases))),
                'description' => $description,
                'inLanguage' => 'ru-RU',
                'isPartOf' => array('@id' => home_url('/#website')),
                'about' => array(
                    '@type' => 'VideoGame',
                    'name' => 'Hearthstone',
                    'genre' => 'Digital collectible card game',
                ),
                'keywords' => implode(', ', array_slice($keywords, 0, 24)),
            ),
            array(
                '@type' => 'BreadcrumbList',
                '@id' => $url . '#breadcrumb',
                'itemListElement' => array(
                    array(
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Манакост',
                        'item' => home_url('/'),
                    ),
                    array(
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => 'Архетипы Hearthstone',
                        'item' => home_url('/hearthstone-top-decks/'),
                    ),
                    array(
                        '@type' => 'ListItem',
                        'position' => 3,
                        'name' => $mode_name !== '' ? (string) $term->name . ' — ' . $mode_name : (string) $term->name,
                        'item' => $url,
                    ),
                ),
            ),
        );
        if (!empty($list_items)) {
            $graph[] = array(
                '@type' => 'ItemList',
                '@id' => $url . '#deck-list',
                'name' => sprintf('Колоды архетипа %s%s', $term->name, $mode_name !== '' ? ' — ' . $mode_name : ''),
                'numberOfItems' => $deck_count,
                'itemListElement' => $list_items,
            );
        }
        if ($image !== '') {
            $graph[1]['image'] = $image;
            $graph[1]['primaryImageOfPage'] = array(
                '@type' => 'ImageObject',
                'url' => $image,
            );
        }

        $runtime[$runtime_key] = array(
            'title' => $title,
            'description' => $description,
            'keywords' => implode(', ', array_slice($keywords, 0, 24)),
            'url' => $url,
            'image' => $image,
            'aliases' => $aliases,
            'mode' => $mode_data,
            'schema' => array(
                '@context' => 'https://schema.org',
                '@graph' => $graph,
            ),
        );

        return $runtime[$runtime_key];
    }

    public function filter_document_title_parts($parts) {
        $data = $this->get_archetype_seo_data();
        if (!empty($data['title'])) {
            $parts['title'] = $data['title'];
        }
        return $parts;
    }

    public function filter_aioseo_archetype_title($title) {
        $data = $this->get_archetype_seo_data();
        return !empty($data['title']) ? $data['title'] : $title;
    }

    public function filter_aioseo_archetype_description($description) {
        $data = $this->get_archetype_seo_data();
        return !empty($data['description']) ? $data['description'] : $description;
    }

    public function filter_aioseo_archetype_canonical_url($url) {
        $data = $this->get_archetype_seo_data();
        return !empty($data['url']) ? $data['url'] : $url;
    }

    public function filter_aioseo_archetype_facebook_tags($meta) {
        $data = $this->get_archetype_seo_data();
        if (empty($data)) {
            return $meta;
        }

        $meta['og:type'] = 'website';
        $meta['og:title'] = $data['title'];
        $meta['og:description'] = $data['description'];
        $meta['og:url'] = $data['url'];
        $meta['og:locale'] = 'ru_RU';
        if (!empty($data['image'])) {
            $meta['og:image'] = $data['image'];
            $meta['og:image:secure_url'] = $data['image'];
        }

        return $meta;
    }

    public function filter_aioseo_archetype_twitter_tags($meta) {
        $data = $this->get_archetype_seo_data();
        if (empty($data)) {
            return $meta;
        }

        $meta['twitter:card'] = !empty($data['image']) ? 'summary_large_image' : 'summary';
        $meta['twitter:title'] = $data['title'];
        $meta['twitter:description'] = $data['description'];
        if (!empty($data['image'])) {
            $meta['twitter:image'] = $data['image'];
        }

        return $meta;
    }

    public function render_archetype_seo_meta() {
        $data = $this->get_archetype_seo_data();
        if (empty($data)) {
            return;
        }

        $has_aioseo = function_exists('aioseo');
        ?>
        <?php if (!$has_aioseo): ?>
            <link rel="canonical" href="<?php echo esc_url($data['url']); ?>">
        <?php endif; ?>
        <meta name="description" content="<?php echo esc_attr($data['description']); ?>">
        <meta property="og:type" content="website">
        <meta property="og:title" content="<?php echo esc_attr($data['title']); ?>">
        <meta property="og:description" content="<?php echo esc_attr($data['description']); ?>">
        <meta property="og:url" content="<?php echo esc_url($data['url']); ?>">
        <meta property="og:locale" content="ru_RU">
        <?php if (!empty($data['image'])): ?>
            <meta property="og:image" content="<?php echo esc_url($data['image']); ?>">
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:image" content="<?php echo esc_url($data['image']); ?>">
        <?php else: ?>
            <meta name="twitter:card" content="summary">
        <?php endif; ?>
        <meta name="twitter:title" content="<?php echo esc_attr($data['title']); ?>">
        <meta name="twitter:description" content="<?php echo esc_attr($data['description']); ?>">
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        <meta name="keywords" content="<?php echo esc_attr($data['keywords']); ?>">
        <?php if (!empty($data['aliases'])): ?>
            <meta name="hs-archetype-aliases" content="<?php echo esc_attr(implode(', ', $data['aliases'])); ?>">
        <?php endif; ?>
        <link rel="alternate" hreflang="ru-RU" href="<?php echo esc_url($data['url']); ?>">
        <link rel="alternate" hreflang="x-default" href="<?php echo esc_url($data['url']); ?>">
        <script type="application/ld+json"><?php echo wp_json_encode($data['schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
        <?php
    }
    
    public function single_deck_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);
        
        $deck_id = intval($atts['id']);
        
        if (!$deck_id || !in_array(get_post_type($deck_id), array('hs_deck', 'top_deck_legend'), true)) {
            return '<p style="color:red;">Колода не найдена</p>';
        }

        if (get_post_meta($deck_id, '_hs_deck_archived', true) === '1' && !current_user_can('edit_post', $deck_id)) {
            return '<p class="no-decks">Колода находится в архиве</p>';
        }
        
        $show_announcement_single = get_post_meta($deck_id, '_show_announcement_single', true) === '1';
        
        ob_start();

        $this->enqueue_front_assets();
        // Маленький per-shortcode тюнинг — добавляем как inline style к нашему handle,
        // чтобы основной CSS-файл оставался кэшируемым и одинаковым для всех страниц.
        wp_add_inline_style(
            'hs-decks-front',
            '.hs-single-deck-container{max-width:600px;margin:20px auto}'
            . '.hs-single-deck-container .deck-card{margin:0}'
            . '.hs-single-deck-container .deck-card.deck-single{display:block !important}'
        );
        ?>
        <div class="hs-single-deck-container">
            <?php $announcement_box = $show_announcement_single ? $this->get_global_announcement_box(true) : ''; ?>
            <?php if ($announcement_box): ?>
            <div class="hs-announcement-wrapper">
                <?php echo $announcement_box; ?>
            </div>
            <?php endif; ?>
            <?php echo $this->get_single_deck_html($deck_id); ?>
        </div>
        
        <?php
        return ob_get_clean();
    }

    /**
     * Фильтрация колод: убирает последовательные дубликаты, ограничивает повторы архетипов
     * и скрывает колоды с малым числом игр.
     * 
     * Фильтрует:
     * 1. Последовательные дубликаты (одинаковые названия колод подряд)
     * 2. Повторы одного архетипа в скользящем окне последних карточек
     * 3. Колоды с малым числом игр (по умолчанию меньше 20 игр)
     */
    private function filter_decks($post_ids) {
        // Можно отключить фильтрацию через фильтр
        if (apply_filters('hs_decks_disable_filtering', false)) {
            return $post_ids;
        }

        if (empty($post_ids)) {
            return array();
        }

        $filtered = array();
        $previous_deck_name = null;
        // Минимальное количество игр для показа колоды (можно изменить через фильтр)
        $min_games = apply_filters('hs_decks_min_games_filter', 20);
        // Можно отключить фильтрацию дубликатов отдельно
        $filter_duplicates = apply_filters('hs_decks_filter_duplicates', true);
        // Можно отключить фильтрацию по количеству игр отдельно
        $filter_low_games = apply_filters('hs_decks_filter_low_games', true);
        $archetype_window_size = max(1, absint(apply_filters('hs_decks_archetype_window_size', 6)));
        $archetype_window_limit = max(1, absint(apply_filters('hs_decks_archetype_window_max', 2)));
        $filter_archetype_window = apply_filters('hs_decks_filter_archetype_window', true)
            && $archetype_window_size > 1
            && $archetype_window_limit > 0;
        $recent_archetypes = array();
        $archetype_lookback = max(0, $archetype_window_size - 1);

        // Прогреваем мета-кэш одним запросом — get_total_games() ниже делает несколько
        // get_post_meta() на пост, без префетча это N×3+ запросов к БД.
        if ($filter_low_games) {
            update_meta_cache('post', $post_ids);
        }
        if ($filter_archetype_window) {
            update_object_term_cache($post_ids, 'hs_deck');
        }

        // Заголовки берём напрямую из объекта поста без второго get_posts():
        // WP_Cache уже содержит их после основного WP_Query в get_decks_html().
        // Фильтруем по исходному порядку
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post) {
                continue; // Пост не найден
            }

            $deck_name = $post->post_title;
            $normalized_name = $this->normalize_deck_name($deck_name);
            
            // Пропускаем последовательные дубликаты
            if ($filter_duplicates && $previous_deck_name !== null && $normalized_name === $previous_deck_name) {
                continue;
            }
            
            // Проверяем количество игр.
            if ($filter_low_games) {
                $total_games = $this->get_total_games($post_id);
                // Фильтруем только если есть статистика и она меньше минимума.
                if ($total_games > 0 && $total_games < $min_games) {
                    continue; // Пропускаем колоды с малым числом игр.
                }
            }

            $archetype_key = '';
            if ($filter_archetype_window) {
                $archetype_key = $this->get_deck_primary_archetype_key($post_id);
                if ($archetype_key !== '') {
                    $same_archetype_count = 0;
                    foreach ($recent_archetypes as $recent_archetype_key) {
                        if ($recent_archetype_key === $archetype_key) {
                            $same_archetype_count++;
                        }
                    }
                    if ($same_archetype_count >= $archetype_window_limit) {
                        continue;
                    }
                }
            }
            
            // Колода прошла фильтрацию
            $filtered[] = $post_id;
            $previous_deck_name = $normalized_name;
            if ($filter_archetype_window && $archetype_key !== '') {
                $recent_archetypes[] = $archetype_key;
                if ($archetype_lookback > 0 && count($recent_archetypes) > $archetype_lookback) {
                    $recent_archetypes = array_slice($recent_archetypes, -1 * $archetype_lookback);
                }
            }
        }
        
        return $filtered;
    }
    
    /**
     * Нормализация названия колоды для сравнения (убирает лишние пробелы, приводит к нижнему регистру)
     */
    private function normalize_deck_name($name) {
        $normalized = trim($name);
        $normalized = hs_mb_lower($normalized);
        // Убираем множественные пробелы
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return $normalized;
    }

    private function get_deck_stats($post_id) {
        $wins = 0;
        $losses = 0;
        $has_record = false;

        $wins_meta = get_post_meta($post_id, '_deck_wins', true);
        $losses_meta = get_post_meta($post_id, '_deck_losses', true);

        if ($wins_meta !== '' && $losses_meta !== '') {
            $wins = absint($wins_meta);
            $losses = absint($losses_meta);
            $has_record = true;
        } else {
            foreach (array('_deck_win_loss', '_deck_stats') as $legacy_key) {
                $legacy_value = get_post_meta($post_id, $legacy_key, true);
                if (!empty($legacy_value) && preg_match('/(\d+)\s*-\s*(\d+)/', $legacy_value, $matches)) {
                    $wins = absint($matches[1]);
                    $losses = absint($matches[2]);
                    $has_record = true;
                    break;
                }
            }
        }

        $record_games = $has_record ? $wins + $losses : 0;
        $games_meta = get_post_meta($post_id, '_deck_games', true);
        $winrate_meta = get_post_meta($post_id, '_deck_winrate', true);
        $games = $games_meta !== '' ? absint($games_meta) : $record_games;

        if ($winrate_meta !== '') {
            $winrate = self::sanitize_winrate_meta($winrate_meta);
        } else {
            $winrate = $record_games > 0 ? round(($wins / $record_games) * 100, 1) : 0;
        }

        return array(
            'wins' => $wins,
            'losses' => $losses,
            'games' => $games,
            'winrate' => $winrate,
            'has_record' => $has_record,
            'has_stats' => $games > 0 || $winrate_meta !== '' || $has_record,
        );
    }

    private function format_winrate($value) {
        $formatted = number_format((float) $value, 1, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');
        return $formatted === '' ? '0' : $formatted;
    }
    
    /**
     * Получение общего количества игр для колоды
     * Проверяет различные возможные мета-поля для хранения статистики
     */
    private function get_total_games($post_id) {
        $stats = $this->get_deck_stats($post_id);
        return (int) $stats['games'];
    }
    
    private function get_decks_html($args = array(), $shortcode_atts = array()) {
        $default_args = array(
            'post_type' => 'hs_deck',
            'posts_per_page' => (int) apply_filters('hs_decks_feed_max_posts', 250),
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
            // Прогреваем term cache: render_deck_card() ниже зовёт get_the_terms()
            // для deck_class/deck_mode на каждую колоду — без префетча это N+1.
            'update_post_term_cache' => true,
        );

        $args = wp_parse_args($args, $default_args);

        // --- Транзиент-кэш HTML карточек ---
        // Ключ привязан к версии (bump_decks_cache_version на save/delete колоды) и к параметрам.
        // НЕ кэшируем random-режим (orderby=rand) — иначе все посетители увидят одинаковую "случайность".
        // TTL увеличен до 12 часов для оптимальной производительности — кэш сбрасывается автоматически при сохранении колод
        $is_random = (isset($args['orderby']) && $args['orderby'] === 'rand');
        $cache_ttl = (int) apply_filters('hs_decks_html_cache_ttl', 12 * HOUR_IN_SECONDS);
        $cache_key = '';
        if (!$is_random && $cache_ttl > 0) {
            $key_payload = array(
                'v'    => self::get_decks_cache_version(),
                'plugin' => $this->get_frontend_version(''),
                'args' => $args,
                'sc'   => $shortcode_atts,
            );
            // Транзиенты в WP-БД ограничены 172 байтами на ключ — md5 укладывается.
            $cache_key = 'hs_decks_html_' . md5(wp_json_encode($key_payload));
            $cached = get_transient($cache_key);
            if (false !== $cached && is_string($cached)) {
                return $cached;
            }
        }
        
        // Добавляем фильтр по классу из шортkода
        if (!empty($shortcode_atts['class'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'deck_class',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($shortcode_atts['class'])
                )
            );
        }
        
        // Добавляем фильтр по дате из шортkода
        if (!empty($shortcode_atts['time'])) {
            $time_parts = array_map('trim', explode(',', $shortcode_atts['time']));
            
            if (count($time_parts) == 1) {
                // Одна дата - показываем от этой даты и новее
                $date_from = DateTime::createFromFormat('d.m.Y', $time_parts[0]);
                if ($date_from) {
                    $args['date_query'] = array(
                        array(
                            'after' => $date_from->format('Y-m-d'),
                            'inclusive' => true
                        )
                    );
                }
            } elseif (count($time_parts) == 2) {
                // Две даты - показываем диапазон
                $date_from = DateTime::createFromFormat('d.m.Y', $time_parts[0]);
                $date_to = DateTime::createFromFormat('d.m.Y', $time_parts[1]);
                
                if ($date_from && $date_to) {
                    $args['date_query'] = array(
                        array(
                            'after' => $date_from->format('Y-m-d'),
                            'before' => $date_to->format('Y-m-d'),
                            'inclusive' => true
                        )
                    );
                }
            }
        }
        
        $query = new WP_Query($args);
        
        ob_start();
        
        if ($query->have_posts()) {
            // Собираем ID без the_post()/wp_reset_postdata() — нам не нужен глобальный $post,
            // и get_post() ниже работает из object cache, который WP_Query уже прогрел.
            $all_posts = wp_list_pluck($query->posts, 'ID');

            $all_posts = $this->filter_deck_feed_flags($all_posts, $is_random);

            // Фильтруем колоды: убираем последовательные дубликаты и колоды с малым числом игр
            $filtered_posts = $this->filter_decks($all_posts);
            if ($is_random && !empty($shortcode_atts['random_row'])) {
                $filtered_posts = array_slice($filtered_posts, 0, max(1, absint($shortcode_atts['random_row'])));
            }

            $show_feed_ads = $this->should_render_feed_ads($args, $shortcode_atts);

            // Выводим отфильтрованные колоды
            if (!empty($filtered_posts)) {
                $deck_index = 0;
                $ad_index = 0;
                foreach ($filtered_posts as $post_id) {
                    echo $this->render_deck_card($post_id, true);
                    $deck_index++;
                    if ($show_feed_ads && $this->should_insert_feed_ad_after($deck_index)) {
                        $ad_index++;
                        echo $this->render_feed_ad_card($ad_index);
                    }
                }
            } else {
                echo '<p class="no-decks">Колоды не найдены</p>';
            }
        } else {
            echo '<p class="no-decks">Колоды не найдены</p>';
        }

        $html = ob_get_clean();

        if ($cache_key !== '') {
            set_transient($cache_key, $html, $cache_ttl);
        }

        return $html;
    }

    private function should_render_feed_ads(array $args, array $shortcode_atts) {
        if (!class_exists('Unified_HS_Ad_Tracker')) {
            return false;
        }
        if (!empty($shortcode_atts['random_row'])) {
            return false;
        }
        if (isset($args['orderby']) && $args['orderby'] === 'rand') {
            return false;
        }
        if (isset($shortcode_atts['ads']) && in_array((string) $shortcode_atts['ads'], array('0', 'false', 'no'), true)) {
            return false;
        }

        return (bool) apply_filters('hs_decks_feed_ads_enabled', true, $args, $shortcode_atts);
    }

    private function render_feed_ad_card($slot) {
        if (!class_exists('Unified_HS_Ad_Tracker')) {
            return '';
        }

        $image_url = UNIFIED_HS_PLUGINS_URL . 'assets/images/boosty-feed-banner.png';
        $image_webp_url = UNIFIED_HS_PLUGINS_URL . 'assets/images/boosty-feed-banner.webp';
        $click_url = Unified_HS_Ad_Tracker::click_url(Unified_HS_Ad_Tracker::DEFAULT_AD_KEY);

        return '<article class="deck-card hs-decks-ad-card" data-ad-key="' . esc_attr(Unified_HS_Ad_Tracker::DEFAULT_AD_KEY) . '" data-ad-slot="' . esc_attr(absint($slot)) . '">' .
            '<a class="hs-decks-ad-link" href="' . esc_url($click_url) . '" target="_blank" rel="nofollow sponsored noopener">' .
            '<picture><source srcset="' . esc_url($image_webp_url) . '" type="image/webp">' .
            '<img src="' . esc_url($image_url) . '" alt="Поддержать команду Manacost на Boosty" width="900" height="1031" loading="lazy" decoding="async"></picture>' .
            '</a>' .
            '</article>';
    }

    private function get_single_deck_html($post_id) {
        $show_proof_option = get_post_meta($post_id, '_show_proof_single', true);
        return $this->render_deck_card($post_id, $show_proof_option === '1', false);
    }

    private function get_legacy_deck_terms($post_id, $taxonomy) {
        global $wpdb;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT t.name, t.slug
             FROM {$wpdb->term_relationships} tr
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
             WHERE tr.object_id = %d AND tt.taxonomy = %s",
            $post_id,
            $taxonomy
        ));

        if (empty($rows)) {
            return array();
        }

        $terms = array();
        foreach ($rows as $row) {
            $terms[] = (object) array(
                'name' => $row->name,
                'slug' => $row->slug,
            );
        }

        return $terms;
    }

    private function get_attachment_image_render_data($attachment_id, array $preferred_sizes = array('large', 'medium_large', 'full')) {
        $attachment_id = absint($attachment_id);
        if (!$attachment_id) {
            return array();
        }

        $image_data = array();
        $resolved_size = 'full';
        foreach ($preferred_sizes as $preferred_size) {
            $candidate = wp_get_attachment_image_src($attachment_id, $preferred_size);
            if (!empty($candidate[0])) {
                $image_data = $candidate;
                $resolved_size = $preferred_size;
                break;
            }
        }

        if (empty($image_data[0])) {
            $fallback_url = wp_get_attachment_url($attachment_id);
            if ($fallback_url === '') {
                return array();
            }

            return array(
                'src' => $fallback_url,
                'fallback_src' => $fallback_url,
                'width' => 0,
                'height' => 0,
                'srcset' => '',
            );
        }

        $fallback_src = wp_get_attachment_url($attachment_id);
        if (!is_string($fallback_src) || $fallback_src === '') {
            $fallback_src = (string) $image_data[0];
        }

        return array(
            'src' => (string) $image_data[0],
            'fallback_src' => $fallback_src,
            'width' => !empty($image_data[1]) ? absint($image_data[1]) : 0,
            'height' => !empty($image_data[2]) ? absint($image_data[2]) : 0,
            'srcset' => (string) wp_get_attachment_image_srcset($attachment_id, $resolved_size),
        );
    }

    private function build_html_attributes(array $attributes) {
        $parts = array();

        foreach ($attributes as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $escaped_value = in_array($name, array('src', 'data-fallback'), true)
                ? esc_url((string) $value)
                : esc_attr((string) $value);
            $parts[] = sprintf('%s="%s"', esc_attr($name), $escaped_value);
        }

        return empty($parts) ? '' : ' ' . implode(' ', $parts);
    }

    private function bound_mobile_image_srcset($srcset) {
        if (!wp_is_mobile() || $srcset === '') {
            return $srcset;
        }

        $bounded_candidates = array();
        $has_adequate_candidate = false;

        foreach (explode(',', $srcset) as $candidate) {
            $candidate = trim($candidate);
            if (!preg_match('/\s+([0-9]+)w$/', $candidate, $matches)) {
                return $srcset;
            }

            $width = absint($matches[1]);
            if ($width > self::MOBILE_IMAGE_MAX_WIDTH) {
                continue;
            }

            $bounded_candidates[] = $candidate;
            if ($width >= self::MOBILE_IMAGE_MIN_WIDTH) {
                $has_adequate_candidate = true;
            }
        }

        if (!$has_adequate_candidate) {
            return $srcset;
        }

        return implode(', ', $bounded_candidates);
    }

    private function render_deck_image_markup($thumbnail_id, $title, $priority_image = false, $wide_image = false) {
        $image = $this->get_attachment_image_render_data($thumbnail_id);
        if (empty($image['src'])) {
            return '';
        }
        $image['srcset'] = $this->bound_mobile_image_srcset((string) $image['srcset']);

        $default_sizes = $wide_image
            ? '(max-width: 767px) calc(100vw - 28px), 560px'
            : '(max-width: 767px) calc(100vw - 28px), (max-width: 1080px) calc(50vw - 32px), 320px';
        $responsive_sizes = (string) apply_filters(
            'hs_decks_image_sizes',
            $default_sizes,
            (bool) $wide_image,
            (bool) $priority_image
        );

        $attributes = array(
            'src' => $image['src'],
            'alt' => $title,
            'class' => 'deck-img-clickable',
            'role' => 'button',
            'tabindex' => '0',
            'aria-label' => sprintf('Открыть изображение колоды «%s»', $title),
            'loading' => $priority_image ? 'eager' : 'lazy',
            'decoding' => 'async',
            'sizes' => $responsive_sizes,
        );

        if ($image['fallback_src'] !== '' && $image['fallback_src'] !== $image['src']) {
            $attributes['data-fallback'] = $image['fallback_src'];
        }
        if (!empty($image['srcset'])) {
            $attributes['srcset'] = $image['srcset'];
        }
        if (!empty($image['width'])) {
            $attributes['width'] = (string) absint($image['width']);
        }
        if (!empty($image['height'])) {
            $attributes['height'] = (string) absint($image['height']);
        }
        if ($priority_image) {
            $attributes['fetchpriority'] = 'high';
        }

        return '<img' . $this->build_html_attributes($attributes) . '>';
    }

    private function get_deck_primary_archetype_key($post_id) {
        $terms = get_the_terms($post_id, 'deck_archetype');
        if (empty($terms) || is_wp_error($terms)) {
            return $this->normalize_deck_name(get_the_title($post_id));
        }

        $keys = array();
        foreach ($terms as $term) {
            $key = $this->display_token_key($term->slug . ' ' . $term->name);
            if ($key !== '') {
                $keys[] = $key;
            }
        }

        if (empty($keys)) {
            return $this->normalize_deck_name(get_the_title($post_id));
        }

        sort($keys, SORT_STRING);
        return implode('|', $keys);
    }
    
    private function render_deck_card($post_id, $show_proof = true, $show_dust = true, $priority_image = false, $wide_image = null) {
        $post_type = get_post_type($post_id);
        // Определяем контекст до построения responsive image attributes.
        $is_single = !did_action('hs_decks_shortcode_called');
        $use_wide_image = is_bool($wide_image) ? $wide_image : $is_single;
        $single_class = $is_single ? ' deck-single' : '';
        $deck_code = get_post_meta($post_id, '_deck_code', true);
        if (empty($deck_code) && $post_type === 'top_deck_legend') {
            $deck_code = get_post_meta($post_id, 'code', true);
        }
        $rank_proof = get_post_meta($post_id, '_rank_proof', true);
        if (empty($rank_proof) && $post_type === 'top_deck_legend') {
            $rank_proof = get_post_meta($post_id, 'evidence', true);
        }
        $custom_tags = get_post_meta($post_id, '_custom_tags', true);
        $custom_tags_array = array();
        foreach (self::custom_tags_to_array($custom_tags) as $custom_tag) {
            if (!$this->is_unknown_deck_author($custom_tag)) {
                $custom_tags_array[] = $custom_tag;
            }
        }
        $title = get_the_title($post_id);
        $dust_cost = get_post_meta($post_id, '_dust_cost', true) ?: 0;
        $likes = get_post_meta($post_id, '_deck_likes', true) ?: 0;
        $dislikes = get_post_meta($post_id, '_deck_dislikes', true) ?: 0;
        $thumbnail_id = get_post_thumbnail_id($post_id);
        $deck_image = $thumbnail_id ? $this->render_deck_image_markup($thumbnail_id, $title, $priority_image, $use_wide_image) : '';
        
        $stats = $this->get_deck_stats($post_id);
        $wins = $stats['wins'];
        $losses = $stats['losses'];
        $total_games = $stats['games'];
        $winrate = $stats['winrate'];
        $winrate_label = $this->format_winrate($winrate);
        $winrate_class = $winrate >= 50 ? 'good' : ($winrate >= 40 ? 'medium' : 'low');
        
        // Ранги
        $peak = get_post_meta($post_id, '_deck_peak', true);
        $latest = get_post_meta($post_id, '_deck_latest', true);
        $worst = get_post_meta($post_id, '_deck_worst', true);
        
        // Стример
        $streamer = get_post_meta($post_id, '_deck_streamer', true);
        $player = get_post_meta($post_id, '_deck_player', true);
        $source_url = get_post_meta($post_id, '_deck_source_url', true);
        $use_feed_shortcode = get_post_meta($post_id, '_use_feed_shortcode', true) === '1';
        $feed_shortcode = get_post_meta($post_id, '_feed_shortcode', true);
        
        $classes = get_the_terms($post_id, 'deck_class');
        if ((empty($classes) || is_wp_error($classes)) && $post_type === 'top_deck_legend') {
            $classes = $this->get_legacy_deck_terms($post_id, 'tags_top_deck_legend');
        }
        $class_names = array();
        $class_slugs = array();
        if ($classes && !is_wp_error($classes)) {
            foreach ($classes as $term) {
                $class_names[] = $term->name;
                $class_slugs[] = $term->slug;
            }
        }
        $class_name_display = !empty($class_names) ? implode(', ', $class_names) : '';
        $class_icon_url = $this->get_deck_class_icon_url($class_slugs, $class_names);
        
        $modes = get_the_terms($post_id, 'deck_mode');
        if ((empty($modes) || is_wp_error($modes)) && $post_type === 'top_deck_legend') {
            $modes = $this->get_legacy_deck_terms($post_id, 'tapes_top_deck_legend');
        }
        $mode_names = array();
        $mode_slugs = array();
        if ($modes && !is_wp_error($modes)) {
            foreach ($modes as $term) {
                $mode_names[] = $term->name;
                $mode_slugs[] = $term->slug;
            }
        }
        $decoded_mode = $this->get_deck_code_mode_data($deck_code, $post_id);
        if (!empty($decoded_mode['slug']) && !empty($decoded_mode['name']) && (!isset($decoded_mode['source']) || $decoded_mode['source'] === 'deck_code')) {
            $mode_names = array((string) $decoded_mode['name']);
            $mode_slugs = array((string) $decoded_mode['slug']);
        }
        $mode_name_display = !empty($mode_names) ? implode(', ', $mode_names) : '';
        
        $show_all_class_modes = get_post_meta($post_id, '_show_all_class_modes', true) === '1';
        $display_streamer = $this->is_unknown_deck_author($streamer) ? '' : trim((string) $streamer);
        $display_player_raw = $this->is_unknown_deck_author($player) ? '' : trim((string) $player);
        $display_player = $this->display_token_key($display_player_raw) !== $this->display_token_key($display_streamer)
            ? $display_player_raw
            : '';
        $custom_tags_array = $this->filter_duplicate_visible_tags(
            $custom_tags_array,
            array_merge($class_names, $mode_names, array($display_streamer, $display_player))
        );
        
        $date = get_the_date('d.m.Y', $post_id);
        $date_timestamp = get_post_time('U', true, $post_id);
        $tag_keys = array_values(array_map('hs_mb_lower', $custom_tags_array));
        $streamer_key = hs_mb_lower($display_streamer);
        $search_string = hs_mb_lower($title . ' ' . $deck_code . ' ' . implode(' ', $custom_tags_array) . ' ' . $display_streamer . ' ' . $display_player . ' ' . implode(' ', $class_names) . ' ' . implode(' ', $mode_names));
        $permalink = get_permalink($post_id);
        
        $is_feed_context = !$is_single;
        if ($is_feed_context && $use_feed_shortcode && !empty(trim($feed_shortcode))) {
            $normalized_shortcode = trim($feed_shortcode);
            if (strpos($normalized_shortcode, '[') !== false && substr($normalized_shortcode, -1) !== ']') {
                $normalized_shortcode .= ']';
            }
            $shortcode_html = do_shortcode(shortcode_unautop($normalized_shortcode));
            ob_start();
            ?>
            <div class="deck-card deck-shortcode<?php echo $single_class; ?>"
                 data-deck-id="<?php echo $post_id; ?>"
                 data-class="<?php echo esc_attr($show_all_class_modes ? 'all' : implode(',', $class_slugs)); ?>"
                 data-mode="<?php echo esc_attr($show_all_class_modes ? 'all' : implode(',', $mode_slugs)); ?>"
                 data-likes="<?php echo $likes; ?>"
                 data-dislikes="<?php echo $dislikes; ?>"
                 data-dust="<?php echo $dust_cost; ?>"
                 data-games="<?php echo esc_attr($total_games); ?>"
                 data-winrate="<?php echo esc_attr($winrate); ?>"
                 data-date="<?php echo esc_attr($date_timestamp); ?>"
                 data-streamer="<?php echo esc_attr($streamer_key); ?>"
                 data-tags="<?php echo esc_attr(wp_json_encode($tag_keys)); ?>"
                 data-title="<?php echo esc_attr(hs_mb_lower($title)); ?>"
                 data-search="<?php echo esc_attr($search_string); ?>">
                <div class="deck-shortcode-content">
                    <?php echo $shortcode_html; ?>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }
        
        ob_start();
        ?>
        <div class="deck-card<?php echo $single_class; ?>" 
             data-deck-id="<?php echo $post_id; ?>" 
             data-class="<?php echo esc_attr($show_all_class_modes ? 'all' : implode(',', $class_slugs)); ?>" 
             data-mode="<?php echo esc_attr($show_all_class_modes ? 'all' : implode(',', $mode_slugs)); ?>" 
             data-likes="<?php echo $likes; ?>"
             data-dislikes="<?php echo $dislikes; ?>"
             data-dust="<?php echo $dust_cost; ?>" 
             data-games="<?php echo esc_attr($total_games); ?>"
             data-winrate="<?php echo esc_attr($winrate); ?>"
             data-date="<?php echo esc_attr($date_timestamp); ?>"
             data-streamer="<?php echo esc_attr($streamer_key); ?>"
             data-tags="<?php echo esc_attr(wp_json_encode($tag_keys)); ?>"
             data-title="<?php echo esc_attr(hs_mb_lower($title)); ?>"
             data-search="<?php echo esc_attr($search_string); ?>">
            
            <div class="deck-header">
                <h3 class="deck-title">
                    <?php if ($class_icon_url): ?>
                    <img class="deck-class-icon" src="<?php echo esc_url($class_icon_url); ?>" alt="<?php echo esc_attr($show_all_class_modes ? 'Все классы' : $class_name_display); ?>" loading="lazy" decoding="async">
                    <?php endif; ?>
                    <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
                </h3>
                <div class="deck-meta">
                    <span class="deck-class"><?php echo esc_html($show_all_class_modes ? 'Все классы' : $class_name_display); ?></span>
                    <span class="deck-mode"><?php echo esc_html($show_all_class_modes ? 'Все режимы' : $mode_name_display); ?></span>
                    <?php if ($show_dust && $dust_cost > 0): ?>
                    <span class="deck-dust">
                        <?php echo number_format($dust_cost, 0, ',', ' '); ?> пыли
                    </span>
                    <?php endif; ?>
                </div>
                <div class="deck-meta-secondary">
                    <span class="deck-date"><?php echo $date; ?></span>
                    <?php if ($display_streamer): ?>
                    <span class="deck-streamer"><?php echo esc_html($display_streamer); ?></span>
                    <?php endif; ?>
                    <?php if ($display_player): ?>
                    <span class="deck-player"><?php echo esc_html($display_player); ?></span>
                    <?php endif; ?>
                    <?php if ($source_url): ?>
                    <a class="deck-source" href="<?php echo esc_url($source_url); ?>" target="_blank" rel="nofollow noopener">Источник</a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($custom_tags_array)): ?>
                <div class="deck-tags">
                    <?php 
                    foreach ($custom_tags_array as $tag) {
                        echo '<span class="deck-tag">' . esc_html($tag) . '</span>';
                    }
                    ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($deck_image): ?>
            <div class="deck-image">
                <?php echo $deck_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
            <?php endif; ?>
            
            <div class="deck-actions">
                <button type="button" class="copy-code-btn" data-code="<?php echo esc_attr($deck_code); ?>">
                    Скопировать код
                </button>
                <?php if ($show_proof && $rank_proof): ?>
                    <button type="button" class="proof-btn" data-proof="<?php echo esc_url($rank_proof); ?>">
                        Доказательство Легенды
                    </button>
                <?php endif; ?>
            </div>
            
            <?php if ($stats['has_stats']): ?>
            <div class="deck-stats deck-stats-modern">
                <div class="deck-stats-row">
                    <div class="deck-stat-item deck-stat-games">
                        <span class="deck-stat-value"><?php echo esc_html($total_games); ?></span>
                        <span class="deck-stat-label">ИГР</span>
                    </div>
                    <div class="deck-stat-item deck-stat-winrate-main">
                        <span class="deck-stat-value deck-stat-winrate-value winrate-<?php echo esc_attr($winrate_class); ?>">
                            <?php echo esc_html($winrate_label); ?>%
                        </span>
                        <span class="deck-stat-label">WINRATE</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($post_type === 'hs_deck'): ?>
            <div class="deck-voting" role="group" aria-label="Оценить колоду">
                <button type="button" class="vote-btn like-btn" data-vote="like" aria-label="Поставить лайк колоде">
                    <span class="vote-sign" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path d="M7 10v12"></path>
                            <path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"></path>
                        </svg>
                    </span>
                    <span class="vote-label">Лайк</span>
                    <span class="vote-count" aria-live="polite"><?php echo esc_html((int) $likes); ?></span>
                </button>
                <button type="button" class="vote-btn dislike-btn" data-vote="dislike" aria-label="Поставить дизлайк колоде">
                    <span class="vote-sign" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path d="M7 10v12"></path>
                            <path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"></path>
                        </svg>
                    </span>
                    <span class="vote-label">Дизлайк</span>
                    <span class="vote-count" aria-live="polite"><?php echo esc_html((int) $dislikes); ?></span>
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function handle_vote() {
        check_ajax_referer('hs_decks_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $vote_type = isset($_POST['vote_type']) ? sanitize_key($_POST['vote_type']) : '';

        // Жёсткий allow-list типа голоса.
        if (!in_array($vote_type, array('like', 'dislike'), true)) {
            wp_send_json_error(array('message' => 'Некорректный тип голоса'));
            return;
        }

        // Проверяем существование колоды.
        if (!$post_id || get_post_type($post_id) !== 'hs_deck') {
            wp_send_json_error(array('message' => 'Колода не найдена'));
            return;
        }

        // Идентификатор голосующего: user_id для залогиненных, иначе хеш IP+UA.
        // Cookie-защита остаётся как первый барьер, но настоящая защита — серверный transient.
        $voter_id = $this->get_voter_id();
        $lock_key = 'hs_voted_' . $post_id . '_' . $voter_id;
        if (get_transient($lock_key)) {
            wp_send_json_error(array('message' => 'Вы уже голосовали'));
            return;
        }
        $cookie_name = 'hs_voted_' . $post_id;
        if (isset($_COOKIE[$cookie_name])) {
            wp_send_json_error(array('message' => 'Вы уже голосовали'));
            return;
        }

        if ($vote_type === 'like') {
            $likes = (int) get_post_meta($post_id, '_deck_likes', true);
            $new_count = $likes + 1;
            update_post_meta($post_id, '_deck_likes', $new_count);
        } else {
            $dislikes = (int) get_post_meta($post_id, '_deck_dislikes', true);
            $new_count = $dislikes + 1;
            update_post_meta($post_id, '_deck_dislikes', $new_count);
        }

        // Серверный lock на 30 дней (как и cookie). Безопасно от очистки cookie.
        set_transient($lock_key, 1, 30 * DAY_IN_SECONDS);
        setcookie($cookie_name, '1', time() + (30 * DAY_IN_SECONDS), COOKIEPATH ? COOKIEPATH : '/');

        if (class_exists('Unified_HS_Deck_Events')) {
            Unified_HS_Deck_Events::record(
                $post_id,
                $vote_type === 'like' ? Unified_HS_Deck_Events::EVENT_LIKE : Unified_HS_Deck_Events::EVENT_DISLIKE,
                array(),
                'vote'
            );
        }

        wp_send_json_success(array(
            'new_count' => $new_count,
            'likes' => (int) get_post_meta($post_id, '_deck_likes', true),
            'dislikes' => (int) get_post_meta($post_id, '_deck_dislikes', true),
            'vote_type' => $vote_type,
        ));
    }

    /**
     * Возвращает актуальные счётчики для уже отрендеренных карточек одним запросом.
     * Это сохраняет долгий HTML-cache ленты, не заставляя сбрасывать его после
     * каждого голоса, и при этом не показывает посетителям устаревшие числа.
     */
    public function handle_vote_counts() {
        if (!check_ajax_referer('hs_decks_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'bad_nonce'), 403);
        }

        $raw_ids = isset($_POST['post_ids']) ? (array) wp_unslash($_POST['post_ids']) : array();
        $post_ids = array_values(array_unique(array_filter(array_map('absint', $raw_ids))));
        $post_ids = array_slice($post_ids, 0, 100);

        if (empty($post_ids)) {
            wp_send_json_success(array('counts' => array()));
        }

        $valid_ids = get_posts(array(
            'post_type' => 'hs_deck',
            'post_status' => 'publish',
            'post__in' => $post_ids,
            'posts_per_page' => count($post_ids),
            'fields' => 'ids',
            'orderby' => 'post__in',
            'no_found_rows' => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
            'ignore_sticky_posts' => true,
        ));

        // WP_Query с fields=ids не прогревает meta cache, даже если флаг выше
        // установлен. Делаем это явно одним запросом, чтобы избежать N+1.
        if (!empty($valid_ids)) {
            update_meta_cache('post', $valid_ids);
        }

        $counts = array();
        foreach ($valid_ids as $valid_id) {
            $counts[(string) $valid_id] = array(
                'likes' => (int) get_post_meta($valid_id, '_deck_likes', true),
                'dislikes' => (int) get_post_meta($valid_id, '_deck_dislikes', true),
            );
        }

        wp_send_json_success(array('counts' => $counts));
    }

    public function track_copy() {
        check_ajax_referer('hs_decks_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        if (!$post_id || get_post_type($post_id) !== 'hs_deck') {
            wp_send_json_error(array('message' => 'Колода не найдена'));
            return;
        }

        // Анти-флуд: один копир от одного клиента не чаще раза в минуту.
        $voter_id = $this->get_voter_id();
        $lock_key = 'hs_copied_' . $post_id . '_' . $voter_id;
        if (get_transient($lock_key)) {
            wp_send_json_success(); // молчаливо ок — пользователь уже зачёлся
            return;
        }
        set_transient($lock_key, 1, MINUTE_IN_SECONDS);

        $copies = (int) get_post_meta($post_id, '_deck_copies', true);
        update_post_meta($post_id, '_deck_copies', $copies + 1);
        if (class_exists('Unified_HS_Deck_Copy_Events')) {
            Unified_HS_Deck_Copy_Events::record($post_id);
        }
        if (class_exists('Unified_HS_Deck_Events')) {
            Unified_HS_Deck_Events::record($post_id, Unified_HS_Deck_Events::EVENT_DECK_COPY, array(), 'copy_button');
        }

        wp_send_json_success();
    }

    public function track_event() {
        check_ajax_referer('hs_decks_nonce', 'nonce');

        if (!class_exists('Unified_HS_Deck_Events')) {
            wp_send_json_success();
            return;
        }

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $event_type = isset($_POST['event_type']) ? sanitize_key(wp_unslash($_POST['event_type'])) : '';
        $source = isset($_POST['source']) ? sanitize_text_field(wp_unslash($_POST['source'])) : 'frontend';
        $context = array();

        if (isset($_POST['context']) && is_array($_POST['context'])) {
            foreach ($_POST['context'] as $key => $value) {
                $context[sanitize_key((string) $key)] = is_array($value)
                    ? array_map('sanitize_text_field', array_map('strval', $value))
                    : sanitize_text_field((string) wp_unslash($value));
            }
        }

        if ($event_type === Unified_HS_Deck_Events::EVENT_DECK_VIEW && isset($_POST['post_ids']) && is_array($_POST['post_ids'])) {
            $post_ids = array();
            foreach ((array) wp_unslash($_POST['post_ids']) as $batch_post_id) {
                $batch_post_id = absint($batch_post_id);
                if ($batch_post_id) {
                    $post_ids[] = $batch_post_id;
                }
            }
            $post_ids = array_slice(array_values(array_unique($post_ids)), 0, 30);
            $unlocked_ids = array();

            foreach ($post_ids as $batch_post_id) {
                $lock_key = 'hs_viewed_' . $batch_post_id . '_' . $this->get_voter_id();
                if (get_transient($lock_key)) {
                    continue;
                }
                set_transient($lock_key, 1, HOUR_IN_SECONDS);
                $unlocked_ids[] = $batch_post_id;
            }

            $batch_result = Unified_HS_Deck_Events::record_batch($unlocked_ids, $event_type, $context, $source);
            if ($batch_result === false) {
                // Сохраняем прежнее поведение, если multi-row INSERT недоступен.
                $recorded = 0;
                foreach ($unlocked_ids as $batch_post_id) {
                    if (Unified_HS_Deck_Events::record($batch_post_id, $event_type, $context, $source)) {
                        $recorded++;
                    }
                }
            } else {
                $recorded = (int) $batch_result;
            }

            wp_send_json_success(array('recorded' => $recorded));
            return;
        }

        if ($event_type === Unified_HS_Deck_Events::EVENT_DECK_VIEW && $post_id) {
            $lock_key = 'hs_viewed_' . $post_id . '_' . $this->get_voter_id();
            if (get_transient($lock_key)) {
                wp_send_json_success();
                return;
            }
            set_transient($lock_key, 1, HOUR_IN_SECONDS);
        }

        Unified_HS_Deck_Events::record($post_id, $event_type, $context, $source);
        wp_send_json_success();
    }

    /**
     * Стабильный идентификатор посетителя для serverside rate-limit / lock.
     * Для залогиненных — user_id; для гостей — хеш IP+UA (короткий, безопасный).
     */
    private function get_voter_id() {
        if (is_user_logged_in()) {
            return 'u' . get_current_user_id();
        }
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        return 'g' . substr(md5($ip . '|' . $ua), 0, 12);
    }
    
    public function register_bulk_actions($bulk_actions) {
        $bulk_actions['generate_shortcode'] = 'Сформировать шорткод группы';
        $bulk_actions['hs_hide_from_feed'] = 'Скрыть из ленты';
        $bulk_actions['hs_show_in_feed'] = 'Показать в ленте';
        $bulk_actions['hs_exclude_from_random'] = 'Исключить из рандома';
        $bulk_actions['hs_include_in_random'] = 'Вернуть в рандом';
        $bulk_actions['hs_archive_deck'] = 'Переместить в архив';
        $bulk_actions['hs_restore_deck'] = 'Восстановить из архива';
        $bulk_actions['hs_assign_class'] = 'Назначить класс';
        $bulk_actions['hs_assign_mode'] = 'Назначить режим';
        return $bulk_actions;
    }

    public function add_archive_row_action($actions, $post) {
        if (!$post || $post->post_type !== 'hs_deck' || !current_user_can('edit_post', $post->ID)) {
            return $actions;
        }

        $archived = get_post_meta($post->ID, '_hs_deck_archived', true) === '1';
        $target_value = $archived ? '0' : '1';
        $label = $archived ? 'Восстановить' : 'В архив';
        $action_key = $archived ? 'hs_restore_deck' : 'hs_archive_deck';
        $url = add_query_arg(array(
            'action' => 'hs_deck_archive_toggle',
            'deck_id' => $post->ID,
            'archive' => $target_value,
        ), admin_url('admin-post.php'));

        $actions[$action_key] = sprintf(
            '<a href="%s">%s</a>',
            esc_url(wp_nonce_url($url, 'hs_deck_archive_toggle_' . $post->ID)),
            esc_html($label)
        );

        return $actions;
    }

    public function handle_archive_row_action() {
        $post_id = isset($_GET['deck_id']) ? absint($_GET['deck_id']) : 0;
        if (!$post_id || get_post_type($post_id) !== 'hs_deck') {
            wp_die('Колода не найдена.');
        }

        check_admin_referer('hs_deck_archive_toggle_' . $post_id);

        if (!current_user_can('edit_post', $post_id)) {
            wp_die('Недостаточно прав для изменения этой колоды.');
        }

        $archive = isset($_GET['archive']) && (string) wp_unslash($_GET['archive']) === '1';
        $old_value = get_post_meta($post_id, '_hs_deck_archived', true);
        update_post_meta($post_id, '_hs_deck_archived', $archive ? '1' : '0');

        $updated = (string) $old_value !== ($archive ? '1' : '0') ? 1 : 0;
        if ($updated) {
            $this->bump_decks_cache_version($post_id);
        }

        $redirect_to = wp_get_referer();
        if (!$redirect_to) {
            $redirect_to = admin_url('edit.php?post_type=hs_deck');
        }

        wp_safe_redirect(add_query_arg(array(
            'hs_decks_bulk_action' => $archive ? 'hs_archive_deck' : 'hs_restore_deck',
            'hs_decks_bulk_updated' => $updated,
        ), $redirect_to));
        exit;
    }

    public function render_bulk_term_controls($post_type, $which = 'top') {
        if ($post_type !== 'hs_deck' || $which !== 'top') {
            return;
        }
        if (!current_user_can(Unified_HS_Capabilities::CAP_ASSIGN_TERMS)) {
            return;
        }

        $classes = get_terms(array(
            'taxonomy' => 'deck_class',
            'hide_empty' => false,
        ));
        $modes = get_terms(array(
            'taxonomy' => 'deck_mode',
            'hide_empty' => false,
        ));
        if (is_wp_error($classes)) {
            $classes = array();
        }
        if (is_wp_error($modes)) {
            $modes = array();
        }
        ?>
        <select name="hs_decks_bulk_class" id="hs-decks-bulk-class">
            <option value="">Класс для назначения</option>
            <?php foreach ($classes as $term): ?>
                <option value="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="hs_decks_bulk_mode" id="hs-decks-bulk-mode">
            <option value="">Режим для назначения</option>
            <?php foreach ($modes as $term): ?>
                <option value="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    public function handle_bulk_actions($redirect_to, $action, $post_ids) {
        $supported_actions = array(
            'generate_shortcode',
            'hs_hide_from_feed',
            'hs_show_in_feed',
            'hs_exclude_from_random',
            'hs_include_in_random',
            'hs_archive_deck',
            'hs_restore_deck',
            'hs_assign_class',
            'hs_assign_mode',
        );

        if (!in_array($action, $supported_actions, true)) {
            return $redirect_to;
        }

        if (!current_user_can(Unified_HS_Capabilities::CAP_MANAGE_DECKS)) {
            return $redirect_to;
        }

        $post_ids = array_values(array_filter(array_map('absint', (array) $post_ids)));
        
        if ($action === 'generate_shortcode') {
            $shortcode = '[hs_deck_group ids="' . implode(',', $post_ids) . '"]';
            $redirect_to = add_query_arg('bulk_shortcode_generated', urlencode($shortcode), $redirect_to);
            return $redirect_to;
        }

        $updated = 0;
        $error = '';

        if ($action === 'hs_assign_class' || $action === 'hs_assign_mode') {
            if (!current_user_can(Unified_HS_Capabilities::CAP_ASSIGN_TERMS)) {
                return add_query_arg('hs_decks_bulk_error', 'terms_capability', $redirect_to);
            }

            $taxonomy = $action === 'hs_assign_class' ? 'deck_class' : 'deck_mode';
            $request_key = $action === 'hs_assign_class' ? 'hs_decks_bulk_class' : 'hs_decks_bulk_mode';
            $term_id = isset($_REQUEST[$request_key]) ? absint($_REQUEST[$request_key]) : 0;
            $term = $term_id ? get_term($term_id, $taxonomy) : null;

            if (!$term_id || !$term || is_wp_error($term)) {
                $error = $action === 'hs_assign_class' ? 'missing_class' : 'missing_mode';
            } else {
                foreach ($post_ids as $post_id) {
                    if (get_post_type($post_id) !== 'hs_deck' || !current_user_can('edit_post', $post_id)) {
                        continue;
                    }
                    $result = wp_set_object_terms($post_id, array($term_id), $taxonomy, false);
                    if (!is_wp_error($result)) {
                        $updated++;
                    }
                }
            }
        } else {
            $meta_map = array(
                'hs_hide_from_feed' => array('_hide_from_feed', '1'),
                'hs_show_in_feed' => array('_hide_from_feed', '0'),
                'hs_exclude_from_random' => array('_exclude_from_random', '1'),
                'hs_include_in_random' => array('_exclude_from_random', '0'),
                'hs_archive_deck' => array('_hs_deck_archived', '1'),
                'hs_restore_deck' => array('_hs_deck_archived', '0'),
            );
            list($meta_key, $meta_value) = $meta_map[$action];

            foreach ($post_ids as $post_id) {
                if (get_post_type($post_id) !== 'hs_deck' || !current_user_can('edit_post', $post_id)) {
                    continue;
                }
                $old_value = get_post_meta($post_id, $meta_key, true);
                update_post_meta($post_id, $meta_key, $meta_value);
                if ((string) $old_value !== (string) $meta_value) {
                    $updated++;
                }
            }
        }

        if ($updated > 0) {
            $this->bump_decks_cache_version();
        }

        if ($error !== '') {
            $redirect_to = add_query_arg('hs_decks_bulk_error', $error, $redirect_to);
        } else {
            $redirect_to = add_query_arg(array(
                'hs_decks_bulk_action' => $action,
                'hs_decks_bulk_updated' => $updated,
            ), $redirect_to);
        }
        
        return $redirect_to;
    }
    
    public function bulk_action_admin_notice() {
        // Только админы, которые редактируют колоды, могут видеть это уведомление.
        if (!current_user_can(Unified_HS_Capabilities::CAP_MANAGE_DECKS)) {
            return;
        }

        if (!empty($_REQUEST['hs_decks_bulk_error'])) {
            $error = sanitize_key(wp_unslash($_REQUEST['hs_decks_bulk_error']));
            $messages = array(
                'missing_class' => 'Выберите класс в выпадающем списке перед массовым действием.',
                'missing_mode' => 'Выберите режим в выпадающем списке перед массовым действием.',
                'terms_capability' => 'Недостаточно прав для назначения классов или режимов.',
            );
            $message = isset($messages[$error]) ? $messages[$error] : 'Массовое действие не выполнено.';
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }

        if (isset($_REQUEST['hs_decks_bulk_updated'], $_REQUEST['hs_decks_bulk_action'])) {
            $updated = absint($_REQUEST['hs_decks_bulk_updated']);
            $action = sanitize_key(wp_unslash($_REQUEST['hs_decks_bulk_action']));
            $labels = array(
                'hs_hide_from_feed' => 'скрыто из ленты',
                'hs_show_in_feed' => 'показано в ленте',
                'hs_exclude_from_random' => 'исключено из рандома',
                'hs_include_in_random' => 'возвращено в рандом',
                'hs_archive_deck' => 'перемещено в архив',
                'hs_restore_deck' => 'восстановлено из архива',
                'hs_assign_class' => 'получило новый класс',
                'hs_assign_mode' => 'получило новый режим',
            );
            if (isset($labels[$action])) {
                printf(
                    '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                    esc_html(sprintf('Колод обработано: %d, действие: %s.', $updated, $labels[$action]))
                );
            }
        }

        if (empty($_REQUEST['bulk_shortcode_generated'])) {
            return;
        }

        // Раскодируем + снимаем magic quotes + санитизируем как обычный текст.
        $raw = wp_unslash($_REQUEST['bulk_shortcode_generated']);
        $raw = is_string($raw) ? urldecode($raw) : '';
        $raw = sanitize_text_field($raw);

        // Жёсткий allow-list: показываем уведомление ТОЛЬКО для нашего шорткода.
        // Любая другая строка (XSS-payload, чужой шорткод) игнорируется.
        if (!preg_match('/^\[hs_deck_group ids="[\d,]+"(?: columns="[\d]+")?\]$/', $raw)) {
            return;
        }
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong><?php esc_html_e('Шорткод группы колод сформирован!', 'unified-hs-plugins'); ?></strong></p>
            <p><?php esc_html_e('Скопируйте шорткод ниже:', 'unified-hs-plugins'); ?></p>
            <input type="text" readonly value="<?php echo esc_attr($raw); ?>" style="width:100%; padding:8px; font-family:monospace; background:#f0f0f0;" onclick="this.select();">
        </div>
        <?php
    }
    
    public function deck_group_shortcode($atts) {
        $atts = shortcode_atts(array(
            'ids' => '',
            'columns' => '3'
        ), $atts);
        
        $deck_ids = array_map('intval', explode(',', $atts['ids']));
        $columns = intval($atts['columns']);
        $announcement_box = $this->get_global_announcement_box();
        
        if (empty($deck_ids)) {
            return '<p>Не указаны ID колод</p>';
        }
        
        ob_start();

        $this->enqueue_front_assets();
        // Static CSS для группы (cacheable inline-style на handle).
        // grid-template-columns динамический → задаётся через CSS-переменную в data-атрибуте.
        wp_add_inline_style(
            'hs-decks-front',
            '.hs-deck-group-container{max-width:1200px;margin:20px auto}'
            . '.hs-deck-group-grid{display:grid;grid-template-columns:repeat(var(--hs-cols,3),1fr);gap:20px}'
            . '.hs-deck-group-card{background:linear-gradient(135deg,#ded5c5 0%,#b9a889 100%);border-radius:8px;padding:15px;box-shadow:0 5px 15px rgba(0,0,0,0.2)}'
            . '.hs-deck-group-card h4{margin:0 0 10px 0;color:#2c3e50;font-size:16px;text-align:center}'
            . '.hs-deck-group-card img{width:100%;border-radius:8px;margin-bottom:10px}'
            . '.hs-deck-group-card .copy-code-btn{width:100%}'
            . '@media (max-width:768px){.hs-deck-group-grid{grid-template-columns:1fr}}'
        );
        $cols_safe = max(1, min(6, intval($columns)));
        ?>
        <div class="hs-deck-group-container" style="--hs-cols:<?php echo $cols_safe; ?>;">
            <?php if ($announcement_box): ?>
            <div class="hs-announcement-wrapper">
                <?php echo $announcement_box; ?>
            </div>
            <?php endif; ?>
            <div class="hs-deck-group-grid">
                <?php foreach ($deck_ids as $deck_id): 
                    if (get_post_meta($deck_id, '_hs_deck_archived', true) === '1' && !current_user_can('edit_post', $deck_id)) {
                        continue;
                    }
                    $deck_code = get_post_meta($deck_id, '_deck_code', true);
                    $deck_image = get_the_post_thumbnail_url($deck_id, 'medium');
                    $title = get_the_title($deck_id);
                ?>
                <div class="hs-deck-group-card" data-deck-id="<?php echo $deck_id; ?>">
                    <h4><?php echo esc_html($title); ?></h4>
                    <?php if ($deck_image): ?>
                        <img src="<?php echo esc_url($deck_image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" decoding="async">
                    <?php endif; ?>
                    <button class="copy-code-btn" data-code="<?php echo esc_attr($deck_code); ?>">
                        Скопировать код
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_announcement_data() {
        return array(
            'text' => wp_kses_post(get_option('hs_decks_announcement_text', '')),
            'image' => esc_url_raw(get_option('hs_decks_announcement_image', '')),
            'button_text' => sanitize_text_field(get_option('hs_decks_announcement_button_text', '')),
            'button_url' => esc_url_raw(get_option('hs_decks_announcement_button_url', ''))
        );
    }
    
    private function has_announcement() {
        $data = $this->get_announcement_data();
        return !empty(trim(wp_strip_all_tags($data['text']))) || !empty($data['image']);
    }
    
    private function get_global_announcement_box($allow_render = false) {
        if (!$allow_render || !$this->has_announcement()) {
            return '';
        }
        $data = $this->get_announcement_data();
        $image_html = '';
        if (!empty($data['image'])) {
            $image_html = '<div class="hs-announcement-image"><img src="' . esc_url($data['image']) . '" alt=""></div>';
        }
        $text_html = '';
        if (!empty($data['text'])) {
            $text_html = wpautop($data['text']);
        }
        $button_html = '';
        if (!empty($data['button_text']) && !empty($data['button_url'])) {
            $button_html = '<div class="hs-announcement-actions"><a class="hs-announcement-btn" href="' . esc_url($data['button_url']) . '" target="_blank" rel="noopener noreferrer">' . esc_html($data['button_text']) . '</a></div>';
        }
        return '<div class="hs-announcement"><div class="hs-announcement-inner">' . $image_html . '<div class="hs-announcement-text">' . $text_html . $button_html . '</div></div></div>';
    }
    
    private function get_layout_block_html($option_name, $position) {
        $raw = get_option($option_name, '');
        if (!$raw || trim($raw) === '') {
            return '';
        }
        $content = do_shortcode(wp_kses_post($raw));
        return '<div class="hs-deck-layout-block hs-deck-layout-block--' . esc_attr($position) . '">' . $content . '</div>';
    }
    
    private function get_all_custom_tags_with_counts() {
        $tags = array();

        $this->for_each_deck_id_batch(function($deck_ids) use (&$tags) {
            update_meta_cache('post', $deck_ids);

            foreach ($deck_ids as $deck_id) {
                $raw_tags = get_post_meta($deck_id, '_custom_tags', true);
                if (!$raw_tags) {
                    continue;
                }
                $tags_array = self::custom_tags_to_array($raw_tags);
                foreach ($tags_array as $tag) {
                    if (empty($tag)) continue;
                    $tags[$tag] = isset($tags[$tag]) ? $tags[$tag] + 1 : 1;
                }
            }
        });
        
        ksort($tags, SORT_NATURAL | SORT_FLAG_CASE);
        return $tags;
    }
    
    private function remove_tag_from_all_decks($tag_to_remove) {
        $removed = 0;
        $target = hs_mb_lower($tag_to_remove);

        $this->for_each_deck_id_batch(function($deck_ids) use (&$removed, $target) {
            update_meta_cache('post', $deck_ids);

            foreach ($deck_ids as $deck_id) {
                $raw_tags = get_post_meta($deck_id, '_custom_tags', true);
                if (!$raw_tags) {
                    continue;
                }
                $tags_array = self::custom_tags_to_array($raw_tags);
                $new_tags = array();
                $changed = false;
                
                foreach ($tags_array as $tag) {
                    if (hs_mb_lower($tag) === $target) {
                        $changed = true;
                        continue;
                    }
                    $new_tags[] = $tag;
                }
                
                if ($changed) {
                    $removed++;
                    update_post_meta($deck_id, '_custom_tags', self::normalize_custom_tags($new_tags));
                }
            }
        });
        
        return $removed;
    }

    private function normalize_tags_for_all_decks() {
        $updated = 0;

        $this->for_each_deck_id_batch(function($deck_ids) use (&$updated) {
            update_meta_cache('post', $deck_ids);

            foreach ($deck_ids as $deck_id) {
                $raw_tags = get_post_meta($deck_id, '_custom_tags', true);
                $normalized = self::normalize_custom_tags($raw_tags);
                if ((string) $raw_tags !== $normalized) {
                    update_post_meta($deck_id, '_custom_tags', $normalized);
                    $updated++;
                }
            }
        });

        return $updated;
    }

    private function for_each_deck_id_batch($callback, $batch_size = 500) {
        $batch_size = max(1, min(1000, (int) $batch_size));
        $paged = 1;

        do {
            $deck_ids = get_posts(array(
                'post_type' => 'hs_deck',
                'posts_per_page' => $batch_size,
                'paged' => $paged,
                'fields' => 'ids',
                'orderby' => 'ID',
                'order' => 'ASC',
                'no_found_rows' => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'ignore_sticky_posts' => true,
            ));

            if (empty($deck_ids)) {
                break;
            }

            call_user_func($callback, $deck_ids);
            $paged++;
        } while (count($deck_ids) === $batch_size);
    }
    
    public function inject_deck_into_single($content) {
        if (is_admin() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $post = get_post();
        if (!$post || $post->post_type !== 'hs_deck') {
            return $content;
        }
        
        if (has_shortcode($content, 'hs_deck') || has_shortcode($content, 'hs_decks') || has_shortcode($content, 'hs_deck_group')) {
            return $content;
        }
        
        $top_block = $this->get_layout_block_html('hs_decks_single_top_content', 'top');
        $bottom_block = $this->get_layout_block_html('hs_decks_single_bottom_content', 'bottom');
        
        $deck_html = $this->single_deck_shortcode(array('id' => $post->ID));
        $deck_code = get_post_meta($post->ID, '_deck_code', true);
        
        $code_block = '';
        if (!empty($deck_code)) {
            $code_block = '<div class="hs-deck-code-block"><h4>Код колоды</h4><textarea readonly>' . esc_textarea($deck_code) . '</textarea><button class="copy-code-btn" data-code="' . esc_attr($deck_code) . '">Скопировать код</button></div>';
        }
        
        return $top_block . $deck_html . $code_block . $bottom_block . $content;
    }
    
}

// Инициализация и хук активации находятся в unified-hs-plugins.php.
