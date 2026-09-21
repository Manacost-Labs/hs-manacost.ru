<?php
/**
 * Plugin Name: Manacost: Decks
 * Description: Управление колодами Hearthstone
 * Version: 2.0.5
 * Author: Manacost Dev
 * Requires PHP: 7.4
 * Requires at least: 5.8
 * Text Domain: unified-hs-plugins
 * Domain Path: /languages
 *
 * Архитектура:
 *   includes/class-ingest-logs.php    — таблица + API логов /manacost/v1/ingest-log
 *   includes/class-deck-activity-log.php — история действий редакторов с колодами
 *   includes/class-deck-period-stats.php — импорт и хранение статистики по периодам
 *   includes/class-deck-events.php   — анонимные события интереса к колодам
 *   includes/class-deckstring-helper.php — декодирование deckstring и автоназначение класса/режима
 *   includes/class-archetype-detector.php — автоархетипы по названиям колод
 *   includes/class-ad-tracker.php     — показы и клики рекламного блока в [hs_decks]
 *   includes/class-rest-controller.php— REST endpoints (/manacost/v1/*)
 *   includes/class-installer.php      — активация, миграции, backfill, дефолтные термины
 *   includes/class-admin-dashboard.php— админ-меню и страницы
 *   hs-deck-manager/hs-decks-manager.php — CPT, шорткоды, AJAX, метабоксы (legacy-монолит)
 *
 * Главный файл оставлен максимально тонким: подключение зависимостей + bootstrap.
 */

if (!defined('ABSPATH')) {
    exit;
}

// --- Константы --------------------------------------------------------------
// Keep DB migration version separate from asset/cache busting.
define('UNIFIED_HS_PLUGINS_VERSION', '1.0.22');
define('UNIFIED_HS_ASSETS_VERSION', '1.0.43');
define('UNIFIED_HS_PLUGINS_DIR', plugin_dir_path(__FILE__));
define('UNIFIED_HS_PLUGINS_URL', plugin_dir_url(__FILE__));

// --- Подключение классов ---------------------------------------------------
// Порядок важен: ingest-logs нужен installer'у, rest-controller'у и admin-dashboard'у.
require_once UNIFIED_HS_PLUGINS_DIR . 'includes/class-ingest-logs.php';
require_once UNIFIED_HS_PLUGINS_DIR . 'includes/class-capabilities.php';
require_once UNIFIED_HS_PLUGINS_DIR . 'includes/class-deck-activity-log.php';
require_once UNIFIED_HS_PLUGINS_DIR . 'includes/class-deck-period-stats.php';
require_once UNIFIED_HS_PLUGINS_DIR . 'includes/class-deck-copy-events.php';
require_once UNIFIED_HS_PLUGINS_DIR . 'includes/class-deck-events.php';
require_once UNIFIED_HS_PLUGINS_DIR . 'includes/class-deckstring-helper.php';
require_once UNIFIED_HS_PLUGINS_DIR . 'includes/class-archetype-detector.php';
require_once UNIFIED_HS_PLUGINS_DIR . 'includes/class-english-title-monitor.php';
require_once UNIFIED_HS_PLUGINS_DIR . 'includes/class-ad-tracker.php';
require_once UNIFIED_HS_PLUGINS_DIR . 'includes/class-installer.php';
require_once UNIFIED_HS_PLUGINS_DIR . 'includes/class-rest-controller.php';
if (is_admin() && !wp_doing_ajax()) {
    require_once UNIFIED_HS_PLUGINS_DIR . 'includes/class-admin-dashboard.php';
}
require_once UNIFIED_HS_PLUGINS_DIR . 'hs-deck-manager/hs-decks-manager.php';

Unified_HS_Capabilities::init();
Unified_HS_Deck_Activity_Log::init();
Unified_HS_Deckstring_Helper::init();
Unified_HS_Archetype_Detector::init();
Unified_HS_English_Title_Monitor::init();
Unified_HS_Ad_Tracker::init();

// --- i18n ------------------------------------------------------------------
add_action('plugins_loaded', function () {
    load_plugin_textdomain(
        'unified-hs-plugins',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});

// --- Bootstrap -------------------------------------------------------------
/**
 * Создаём singleton HS_Decks_Manager на plugins_loaded(1), чтобы все его
 * подписки на init/wp_enqueue_scripts/admin_menu/save_post успели зарегистрироваться.
 */
add_action('plugins_loaded', function () {
    if (!class_exists('HS_Decks_Manager')) {
        return;
    }
    global $hs_decks_manager;
    if (!isset($hs_decks_manager)) {
        $hs_decks_manager = new HS_Decks_Manager();
    }
}, 1);

/**
 * Админ-дашборд — singleton, регистрируется только в админке.
 */
add_action('plugins_loaded', function () {
    if (is_admin() && !wp_doing_ajax() && class_exists('Unified_HS_Admin_Dashboard')) {
        Unified_HS_Admin_Dashboard::get_instance();
    }
}, 5);

// --- REST endpoints --------------------------------------------------------
Unified_HS_REST_Controller::init();

// --- Активация и миграции --------------------------------------------------
Unified_HS_Installer::init(__FILE__);
