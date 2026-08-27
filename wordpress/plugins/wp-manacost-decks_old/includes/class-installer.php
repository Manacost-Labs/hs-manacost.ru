<?php
/**
 * Активация плагина и автомиграции.
 *
 * Содержит:
 *  - `activate()`            — однократно при включении плагина (register_activation_hook).
 *  - `maybe_upgrade()`       — на каждой загрузке: сравнивает константу версии плагина
 *                              со значением в опции; если разошлись — прогоняет миграции.
 *  - `ensure_default_meta()` — хук `wp_insert_post`: дозаполняет boolean-флаги колоды
 *                              (`_hide_from_feed`, `_exclude_from_random`, `_hs_deck_archived`) для всех
 *                              способов создания (REST, wp_insert_post, импорт, CLI).
 *  - `backfill_deck_flags()` — пакетная миграция дефолтных мет для существующих колод.
 *  - `seed_taxonomy_terms()` — создаёт дефолтные классы и режимы при активации.
 *  - таблицы логов импорта, истории действий, статистики по периодам, событий и рекламы.
 *
 * Все методы статические — класс служит просто пространством имён для группировки.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Unified_HS_Installer {

    /**
     * Имя опции, в которой хранится последняя применённая версия миграций.
     */
    const DB_VERSION_OPTION = 'unified_hs_plugins_db_version';

    /**
     * Meta keys-флаги, которые должны существовать у каждой колоды для
     * предсказуемой фильтрации ленты без дорогих frontend meta_query.
     */
    const NEW_DECK_DEFAULT_FLAG_META = array(
        '_hide_from_feed' => '1',
        '_exclude_from_random' => '0',
        '_hs_deck_archived' => '0',
    );

    /**
     * Для старых колод без meta_key оставляем историческое поведение: они видны в ленте.
     */
    const EXISTING_DECK_BACKFILL_FLAG_META = array(
        '_hide_from_feed' => '0',
        '_exclude_from_random' => '0',
        '_hs_deck_archived' => '0',
    );

    /**
     * Регистрирует все хуки, относящиеся к установке/миграциям.
     */
    public static function init($plugin_main_file) {
        register_activation_hook($plugin_main_file, array(__CLASS__, 'activate'));

        // Автомиграция при апгрейде плагина (если файлы залили без деактивации).
        add_action('init', array(__CLASS__, 'maybe_upgrade'), 20);

        // Страховка для всех новых вставок hs_deck.
        add_action('wp_insert_post', array(__CLASS__, 'ensure_default_meta'), 10, 3);
    }

    /**
     * Хук активации: гарантирует, что CPT/таксономии зарегистрированы (для flush_rewrite_rules),
     * заводит дефолтные термины классов/режимов, прогоняет миграции и сбрасывает rewrite-правила.
     */
    public static function activate() {
        if (!function_exists('term_exists') || !function_exists('wp_insert_term')) {
            return;
        }

        // CPT регистрируется конструктором HS_Decks_Manager на init(5). На активации
        // (запрос /wp-admin/plugins.php) `init` уже отработал — поэтому достаточно
        // загрузить класс и инстанцировать его, чтобы register_post_type/taxonomies
        // были выполнены до flush_rewrite_rules() ниже.
        if (!class_exists('HS_Decks_Manager') &&
            file_exists(UNIFIED_HS_PLUGINS_DIR . 'hs-deck-manager/hs-decks-manager.php')) {
            require_once UNIFIED_HS_PLUGINS_DIR . 'hs-deck-manager/hs-decks-manager.php';
        }

        if (class_exists('HS_Decks_Manager')) {
            $manager = new HS_Decks_Manager();
            $manager->register_post_type();
            $manager->register_taxonomies();
        }

        self::seed_taxonomy_terms();
        self::ensure_default_options();
        self::backfill_deck_flags();
        if (class_exists('Unified_HS_Capabilities')) {
            Unified_HS_Capabilities::add_caps_to_administrator();
        }

        if (class_exists('Unified_HS_Ingest_Logs')) {
            Unified_HS_Ingest_Logs::install();
        }
        if (class_exists('Unified_HS_Deck_Activity_Log')) {
            Unified_HS_Deck_Activity_Log::install();
        }
        if (class_exists('Unified_HS_Deck_Period_Stats')) {
            Unified_HS_Deck_Period_Stats::install();
        }
        if (class_exists('Unified_HS_Deck_Copy_Events')) {
            Unified_HS_Deck_Copy_Events::install();
        }
        if (class_exists('Unified_HS_Deck_Events')) {
            Unified_HS_Deck_Events::install();
        }
        if (class_exists('Unified_HS_Ad_Tracker')) {
            Unified_HS_Ad_Tracker::install();
        }
        if (class_exists('Unified_HS_Archetype_Detector')) {
            Unified_HS_Archetype_Detector::assign_all_existing();
        }
        if (class_exists('HS_Decks_Manager')) {
            HS_Decks_Manager::sync_existing_filter_taxonomies();
        }

        // Записываем текущую версию, чтобы maybe_upgrade на следующих загрузках
        // не делал лишних проходов миграции.
        update_option(self::DB_VERSION_OPTION, UNIFIED_HS_PLUGINS_VERSION, false);

        flush_rewrite_rules();
    }

    /**
     * Идемпотентный апгрейд при загрузке. Запускается ТОЛЬКО в админке/CLI,
     * чтобы фронт-запросы не платили за разовые миграции.
     */
    public static function maybe_upgrade() {
        $stored = get_option(self::DB_VERSION_OPTION);
        if ($stored === UNIFIED_HS_PLUGINS_VERSION) {
            if (is_admin() || (defined('WP_CLI') && WP_CLI)) {
                self::ensure_statistics_tables();
            }
            return;
        }
        if (!is_admin() && !(defined('WP_CLI') && WP_CLI)) {
            return;
        }
        self::seed_taxonomy_terms();
        self::ensure_default_options();
        self::backfill_deck_flags();
        if (class_exists('Unified_HS_Capabilities')) {
            Unified_HS_Capabilities::add_caps_to_administrator();
        }
        if (class_exists('Unified_HS_Ingest_Logs')) {
            Unified_HS_Ingest_Logs::install();
        }
        if (class_exists('Unified_HS_Deck_Activity_Log')) {
            Unified_HS_Deck_Activity_Log::install();
        }
        if (class_exists('Unified_HS_Deck_Period_Stats')) {
            Unified_HS_Deck_Period_Stats::install();
        }
        if (class_exists('Unified_HS_Deck_Copy_Events')) {
            Unified_HS_Deck_Copy_Events::install();
        }
        if (class_exists('Unified_HS_Deck_Events')) {
            Unified_HS_Deck_Events::install();
        }
        if (class_exists('Unified_HS_Ad_Tracker')) {
            Unified_HS_Ad_Tracker::install();
        }
        if (class_exists('Unified_HS_Archetype_Detector')) {
            Unified_HS_Archetype_Detector::assign_all_existing();
        }
        if (class_exists('HS_Decks_Manager')) {
            HS_Decks_Manager::sync_existing_filter_taxonomies();
        }
        update_option(self::DB_VERSION_OPTION, UNIFIED_HS_PLUGINS_VERSION, false);
    }

    /**
     * Repairs optional analytics tables even when the main migration version
     * was already bumped by an earlier deploy.
     */
    private static function ensure_statistics_tables() {
        if (class_exists('Unified_HS_Deck_Period_Stats')) {
            Unified_HS_Deck_Period_Stats::install();
        }
        if (class_exists('Unified_HS_Deck_Copy_Events')) {
            Unified_HS_Deck_Copy_Events::install();
        }
        if (class_exists('Unified_HS_Deck_Events')) {
            Unified_HS_Deck_Events::install();
        }
        if (class_exists('Unified_HS_Ad_Tracker')) {
            Unified_HS_Ad_Tracker::install();
        }
    }

    /**
     * Заводит дефолтные термины классов и режимов, если их ещё нет.
     */
    public static function seed_taxonomy_terms() {
        $classes = array(
            'Друид', 'Охотник', 'Маг', 'Паладин', 'Жрец', 'Разбойник',
            'Шаман', 'Чернокнижник', 'Воин', 'Рыцарь смерти', 'Охотник на демонов', 'Демонолог',
        );
        foreach ($classes as $class) {
            if (!term_exists($class, 'deck_class')) {
                wp_insert_term($class, 'deck_class');
            }
        }

        $modes = array('Стандарт', 'Вольный', 'Классический', 'Потасовка', 'Арена');
        foreach ($modes as $mode) {
            if (!term_exists($mode, 'deck_mode')) {
                wp_insert_term($mode, 'deck_mode');
            }
        }
    }

    public static function ensure_default_options() {
        $per_page = get_option('hs_decks_per_page', null);
        if ($per_page === null || $per_page === false || (int) $per_page === 12) {
            update_option('hs_decks_per_page', 13, false);
        }
    }

    /**
     * Хук на wp_insert_post: дозаполняет дефолтные флаги для свежесозданных колод.
     * Не перетирает уже выставленные значения.
     */
    public static function ensure_default_meta($post_id, $post, $update) {
        if (!$post || $post->post_type !== 'hs_deck') {
            return;
        }
        if ($update) {
            return;
        }
        foreach (self::NEW_DECK_DEFAULT_FLAG_META as $meta_key => $default_value) {
            if (!metadata_exists('post', $post_id, $meta_key)) {
                update_post_meta($post_id, $meta_key, $default_value);
            }
        }
    }

    /**
     * Пакетная миграция: для каждой колоды без нужного meta_key пишет '0'.
     * Безопасно для больших каталогов (батчи по 500), идемпотентно.
     */
    public static function backfill_deck_flags() {
        global $wpdb;
        $batch = 500;

        foreach (self::EXISTING_DECK_BACKFILL_FLAG_META as $meta_key => $default_value) {
            do {
                $ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT p.ID FROM {$wpdb->posts} p
                     WHERE p.post_type = %s
                       AND NOT EXISTS (
                           SELECT 1 FROM {$wpdb->postmeta} pm
                           WHERE pm.post_id = p.ID AND pm.meta_key = %s
                       )
                     LIMIT %d",
                    'hs_deck',
                    $meta_key,
                    $batch
                ));
                if (empty($ids)) {
                    break;
                }
                foreach ($ids as $id) {
                    update_post_meta((int) $id, $meta_key, $default_value);
                }
            } while (count($ids) === $batch);
        }
    }
}
