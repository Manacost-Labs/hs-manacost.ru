<?php
/**
 * Класс для главной страницы админ панели
 */

if (!defined('ABSPATH')) {
    exit;
}

class Unified_HS_Admin_Dashboard {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('admin_post_unified_hs_stats_template', array($this, 'download_stats_template'));
    }
    
    /**
     * Добавление меню в админ панель
     */
    public function add_admin_menu() {
        // Главное меню
        add_menu_page(
            'Колоды',
            'Колоды',
            Unified_HS_Capabilities::CAP_MANAGE_DECKS,
            'unified-hs-plugins',
            array($this, 'render_dashboard'),
            'dashicons-database',
            5
        );
        
        // Главная страница
        add_submenu_page(
            'unified-hs-plugins',
            'Главная',
            'Главная',
            Unified_HS_Capabilities::CAP_MANAGE_DECKS,
            'unified-hs-plugins',
            array($this, 'render_dashboard')
        );
        
        // Колоды HS
        add_submenu_page(
            'unified-hs-plugins',
            'Колоды HS',
            'Колоды HS',
            Unified_HS_Capabilities::CAP_MANAGE_DECKS,
            'edit.php?post_type=hs_deck'
        );
        
        // Добавить новую колоду
        add_submenu_page(
            'unified-hs-plugins',
            'Добавить колоду',
            'Добавить колоду',
            Unified_HS_Capabilities::CAP_CREATE_DECKS,
            'post-new.php?post_type=hs_deck'
        );
        
        // Галерея шорткодов
        add_submenu_page(
            'unified-hs-plugins',
            'Галерея шорткодов',
            'Шорткоды',
            Unified_HS_Capabilities::CAP_VIEW_SHORTCODES,
            'unified-hs-shortcodes',
            array($this, 'render_shortcodes_gallery')
        );
        
        // Настройки
        add_submenu_page(
            'unified-hs-plugins',
            'Настройки плагина',
            'Настройки',
            Unified_HS_Capabilities::CAP_MANAGE_TERMS,
            'unified-hs-settings',
            array($this, 'render_settings_page')
        );

        // Аналитика по колодам и архетипам
        add_submenu_page(
            'unified-hs-plugins',
            'Статистика',
            'Статистика',
            Unified_HS_Capabilities::CAP_VIEW_STATS,
            'unified-hs-stats',
            array($this, 'render_stats_page')
        );

        add_submenu_page(
            'unified-hs-plugins',
            'Архетипы',
            'Архетипы',
            Unified_HS_Capabilities::CAP_MANAGE_TERMS,
            'unified-hs-archetypes',
            array($this, 'render_archetypes_page')
        );

        // Логи импорта
        add_submenu_page(
            'unified-hs-plugins',
            'Логи импорта',
            'Логи импорта',
            Unified_HS_Capabilities::CAP_VIEW_LOGS,
            'unified-hs-logs',
            array($this, 'render_logs_page')
        );

        // Импорт статистики по периодам
        add_submenu_page(
            'unified-hs-plugins',
            'Импорт статистики',
            'Импорт статистики',
            Unified_HS_Capabilities::CAP_IMPORT_DECKS,
            'unified-hs-stats-import',
            array($this, 'render_stats_import_page')
        );

        // История действий
        add_submenu_page(
            'unified-hs-plugins',
            'История действий',
            'История',
            Unified_HS_Capabilities::CAP_VIEW_ACTIVITY_LOG,
            'unified-hs-activity',
            array($this, 'render_activity_page')
        );
    }
    
    /**
     * Подключение стилей админ панели
     */
    public function enqueue_admin_styles($hook) {
        $ver = defined('UNIFIED_HS_PLUGINS_VERSION') ? UNIFIED_HS_PLUGINS_VERSION : false;

        if ($hook === 'toplevel_page_unified-hs-plugins') {
            wp_enqueue_style(
                'unified-hs-admin-dashboard',
                UNIFIED_HS_PLUGINS_URL . 'assets/css/admin-dashboard.css',
                array(),
                $ver
            );
        }

        if (strpos($hook, 'unified-hs-shortcodes') !== false) {
            wp_enqueue_style(
                'unified-hs-shortcodes-gallery',
                UNIFIED_HS_PLUGINS_URL . 'assets/css/shortcodes-gallery.css',
                array(),
                $ver
            );
        }

        if (strpos($hook, 'unified-hs-settings') !== false || strpos($hook, 'unified-hs-stats') !== false || strpos($hook, 'unified-hs-stats-import') !== false || strpos($hook, 'unified-hs-archetypes') !== false) {
            wp_enqueue_style(
                'unified-hs-admin-dashboard',
                UNIFIED_HS_PLUGINS_URL . 'assets/css/admin-dashboard.css',
                array(),
                $ver
            );
        }

        if (strpos($hook, 'unified-hs-archetypes') !== false) {
            wp_enqueue_media();
        }

        if (strpos($hook, 'unified-hs-stats') !== false && strpos($hook, 'unified-hs-stats-import') === false) {
            wp_enqueue_script(
                'unified-hs-admin-dashboard',
                UNIFIED_HS_PLUGINS_URL . 'assets/js/admin-dashboard.js',
                array('jquery'),
                $ver,
                true
            );
        }

        if (strpos($hook, 'unified-hs-activity') !== false) {
            wp_enqueue_style(
                'unified-hs-admin-dashboard',
                UNIFIED_HS_PLUGINS_URL . 'assets/css/admin-dashboard.css',
                array(),
                $ver
            );
        }
    }

    private function get_dashboard_stats() {
        $cache_version = class_exists('HS_Decks_Manager') ? HS_Decks_Manager::get_decks_cache_version() : 1;
        $cache_key = 'unified_hs_dashboard_stats_' . $cache_version;
        $cached = get_transient($cache_key);
        if (false !== $cached && is_array($cached)) {
            return $cached;
        }

        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN COALESCE(archived, '0') = '1' THEN 1 ELSE 0 END), 0) AS archived,
                COALESCE(SUM(CASE WHEN COALESCE(archived, '0') <> '1' AND COALESCE(hidden, '0') = '1' THEN 1 ELSE 0 END), 0) AS hidden,
                COALESCE(SUM(CASE WHEN COALESCE(archived, '0') <> '1' AND COALESCE(hidden, '0') <> '1' THEN 1 ELSE 0 END), 0) AS visible,
                COALESCE(SUM(CASE WHEN COALESCE(no_random, '0') = '1' THEN 1 ELSE 0 END), 0) AS no_random,
                COALESCE(SUM(CASE WHEN games REGEXP '^[0-9]+$' THEN CAST(games AS UNSIGNED) ELSE 0 END), 0) AS games,
                COALESCE(ROUND(AVG(CASE WHEN winrate REGEXP '^[0-9]+([.][0-9]+)?$' THEN CAST(winrate AS DECIMAL(5,2)) ELSE NULL END), 1), 0) AS avg_winrate,
                COALESCE(SUM(CASE WHEN likes REGEXP '^[0-9]+$' THEN CAST(likes AS UNSIGNED) ELSE 0 END), 0) AS likes,
                COALESCE(SUM(CASE WHEN dislikes REGEXP '^[0-9]+$' THEN CAST(dislikes AS UNSIGNED) ELSE 0 END), 0) AS dislikes,
                COALESCE(SUM(CASE WHEN copies REGEXP '^[0-9]+$' THEN CAST(copies AS UNSIGNED) ELSE 0 END), 0) AS copies
             FROM (
                SELECT
                    p.ID,
                    MAX(CASE WHEN pm.meta_key = '_hs_deck_archived' THEN pm.meta_value END) AS archived,
                    MAX(CASE WHEN pm.meta_key = '_hide_from_feed' THEN pm.meta_value END) AS hidden,
                    MAX(CASE WHEN pm.meta_key = '_exclude_from_random' THEN pm.meta_value END) AS no_random,
                    MAX(CASE WHEN pm.meta_key = '_deck_games' THEN pm.meta_value END) AS games,
                    MAX(CASE WHEN pm.meta_key = '_deck_winrate' THEN pm.meta_value END) AS winrate,
                    MAX(CASE WHEN pm.meta_key = '_deck_likes' THEN pm.meta_value END) AS likes,
                    MAX(CASE WHEN pm.meta_key = '_deck_dislikes' THEN pm.meta_value END) AS dislikes,
                    MAX(CASE WHEN pm.meta_key = '_deck_copies' THEN pm.meta_value END) AS copies
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm
                    ON pm.post_id = p.ID
                    AND pm.meta_key IN (
                        '_hs_deck_archived',
                        '_hide_from_feed',
                        '_exclude_from_random',
                        '_deck_games',
                        '_deck_winrate',
                        '_deck_likes',
                        '_deck_dislikes',
                        '_deck_copies'
                    )
                WHERE p.post_type = %s
                  AND p.post_status IN ('publish', 'draft', 'pending', 'future', 'private')
                GROUP BY p.ID
             ) deck_stats
             LIMIT 1",
            'hs_deck'
        );

        $row = $wpdb->get_row($sql, ARRAY_A);
        $stats = array(
            'total' => isset($row['total']) ? absint($row['total']) : 0,
            'visible' => isset($row['visible']) ? absint($row['visible']) : 0,
            'hidden' => isset($row['hidden']) ? absint($row['hidden']) : 0,
            'archived' => isset($row['archived']) ? absint($row['archived']) : 0,
            'no_random' => isset($row['no_random']) ? absint($row['no_random']) : 0,
            'games' => isset($row['games']) ? absint($row['games']) : 0,
            'avg_winrate' => isset($row['avg_winrate']) ? (float) $row['avg_winrate'] : 0,
            'likes' => isset($row['likes']) ? absint($row['likes']) : 0,
            'dislikes' => isset($row['dislikes']) ? absint($row['dislikes']) : 0,
            'copies' => isset($row['copies']) ? absint($row['copies']) : 0,
        );

        set_transient($cache_key, $stats, 15 * MINUTE_IN_SECONDS);
        return $stats;
    }

    private function render_metric_card($label, $value, $tone = 'neutral', $suffix = '') {
        ?>
        <div class="unified-hs-stat-card unified-hs-stat-card--<?php echo esc_attr($tone); ?>">
            <div class="stat-number"><?php echo esc_html($value); ?><?php echo esc_html($suffix); ?></div>
            <div class="stat-label"><?php echo esc_html($label); ?></div>
        </div>
        <?php
    }
    
    /**
     * Рендеринг главной страницы дашборда
     */
    public function render_dashboard() {
        if (!current_user_can(Unified_HS_Capabilities::CAP_MANAGE_DECKS)) {
            wp_die(esc_html__('Недостаточно прав', 'unified-hs-plugins'));
        }

        $stats = $this->get_dashboard_stats();
        $decks_total = $stats['total'];
        ?>
        <div class="wrap unified-hs-dashboard">
            <h1>Колоды Hearthstone</h1>
            <p class="subtitle">Управление колодами, лентой, архивом и качеством данных</p>
            
            <div class="unified-hs-grid">
                <div class="unified-hs-card" onclick="location.href='<?php echo esc_url(admin_url('edit.php?post_type=hs_deck')); ?>'">
                    <h3>Рабочая база колод</h3>
                    <p>Переход к списку, массовым действиям, архиву, скрытию из ленты и статистике.</p>
                    <div class="card-stats">
                        <span class="stat-number"><?php echo number_format_i18n($decks_total); ?></span>
                        <span class="stat-label">колод</span>
                    </div>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=hs_deck')); ?>" class="button button-primary">Открыть</a>
                </div>

                <div class="unified-hs-card unified-hs-card--quiet" onclick="location.href='<?php echo esc_url(admin_url('admin.php?page=unified-hs-activity')); ?>'">
                    <h3>Контроль изменений</h3>
                    <p>История покажет, кто изменил код, теги, ленту, рандом или архивный статус.</p>
                    <div class="card-stats">
                        <span class="stat-number"><?php echo number_format_i18n($stats['hidden'] + $stats['archived']); ?></span>
                        <span class="stat-label">скрыто / архив</span>
                    </div>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=unified-hs-activity')); ?>" class="button">История</a>
                </div>
            </div>
            
            <div class="unified-hs-stats-grid">
                <?php $this->render_metric_card('Всего колод', number_format_i18n($decks_total), 'primary'); ?>
                <?php $this->render_metric_card('В ленте', number_format_i18n($stats['visible']), 'success'); ?>
                <?php $this->render_metric_card('Скрыты', number_format_i18n($stats['hidden']), 'warning'); ?>
                <?php $this->render_metric_card('В архиве', number_format_i18n($stats['archived']), 'muted'); ?>
                <?php $this->render_metric_card('Без рандома', number_format_i18n($stats['no_random']), 'muted'); ?>
                <?php $this->render_metric_card('Игр в базе', number_format_i18n($stats['games']), 'primary'); ?>
                <?php $this->render_metric_card('Средний winrate', number_format_i18n($stats['avg_winrate'], 1), 'success', '%'); ?>
                <?php $this->render_metric_card('Копирований', number_format_i18n($stats['copies']), 'primary'); ?>
                
                <?php
                $classes_count = wp_count_terms(array('taxonomy' => 'deck_class', 'hide_empty' => false));
                if (!is_wp_error($classes_count)) {
                    $this->render_metric_card('Классов', number_format_i18n($classes_count), 'neutral');
                }
                ?>
            </div>
            
            <div class="unified-hs-actions">
                <h2>Быстрые действия</h2>
                <div class="actions-buttons">
                    <?php if (current_user_can(Unified_HS_Capabilities::CAP_CREATE_DECKS)): ?>
                    <a href="<?php echo esc_url(admin_url('post-new.php?post_type=hs_deck')); ?>" class="button">Добавить колоду</a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=hs_deck')); ?>" class="button">Массовые действия</a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=unified-hs-settings')); ?>" class="button">Классы и режимы</a>
                    <?php if (current_user_can(Unified_HS_Capabilities::CAP_VIEW_STATS)): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=unified-hs-stats')); ?>" class="button button-primary">Статистика</a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=unified-hs-logs')); ?>" class="button">Логи импорта</a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=unified-hs-stats-import')); ?>" class="button">Импорт статистики</a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Рендеринг страницы настроек
     */
    public function render_settings_page() {
        if (!current_user_can(Unified_HS_Capabilities::CAP_MANAGE_TERMS)) {
            wp_die(esc_html__('Недостаточно прав', 'unified-hs-plugins'));
        }

        if (isset($_POST['action']) && check_admin_referer('unified_hs_settings_action')) {
            $this->handle_settings_action();
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
        <div class="wrap unified-hs-settings">
            <h1>Настройки колод</h1>
            <p class="subtitle">Управление классами и режимами для колод Hearthstone</p>
            
            <div class="unified-hs-settings-grid">
                <div class="unified-hs-settings-section">
                    <h2>Классы колод</h2>
                    <form method="post" action="" class="unified-hs-add-form">
                        <?php wp_nonce_field('unified_hs_settings_action'); ?>
                        <input type="hidden" name="action" value="add_class">
                        <div class="form-row">
                            <input type="text" name="class_name" placeholder="Название класса" required>
                            <button type="submit" class="button button-primary">Добавить класс</button>
                        </div>
                    </form>
                    
                    <div class="unified-hs-terms-list">
                        <?php if (!empty($classes)): ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>Название</th>
                                        <th>Slug</th>
                                        <th>Колод</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($class->name); ?></strong></td>
                                            <td><code><?php echo esc_html($class->slug); ?></code></td>
                                            <td><?php echo intval($class->count); ?></td>
                                            <td>
                                                <form method="post" action="" style="display: inline;">
                                                    <?php wp_nonce_field('unified_hs_settings_action'); ?>
                                                    <input type="hidden" name="action" value="delete_class">
                                                    <input type="hidden" name="term_id" value="<?php echo intval($class->term_id); ?>">
                                                    <button type="submit" class="button button-link-delete" onclick="return confirm('Удалить этот класс?')">Удалить</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>Классы не добавлены</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="unified-hs-settings-section">
                    <h2>Режимы колод</h2>
                    <form method="post" action="" class="unified-hs-add-form">
                        <?php wp_nonce_field('unified_hs_settings_action'); ?>
                        <input type="hidden" name="action" value="add_mode">
                        <div class="form-row">
                            <input type="text" name="mode_name" placeholder="Название режима" required>
                            <button type="submit" class="button button-primary">Добавить режим</button>
                        </div>
                    </form>
                    
                    <div class="unified-hs-terms-list">
                        <?php if (!empty($modes)): ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>Название</th>
                                        <th>Slug</th>
                                        <th>Колод</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($modes as $mode): ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($mode->name); ?></strong></td>
                                            <td><code><?php echo esc_html($mode->slug); ?></code></td>
                                            <td><?php echo intval($mode->count); ?></td>
                                            <td>
                                                <form method="post" action="" style="display: inline;">
                                                    <?php wp_nonce_field('unified_hs_settings_action'); ?>
                                                    <input type="hidden" name="action" value="delete_mode">
                                                    <input type="hidden" name="term_id" value="<?php echo intval($mode->term_id); ?>">
                                                    <button type="submit" class="button button-link-delete" onclick="return confirm('Удалить этот режим?')">Удалить</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>Режимы не добавлены</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <style>
        .unified-hs-settings { max-width: 1400px; }
        .unified-hs-settings-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 20px; }
        .unified-hs-settings-section { background: #fff; border: 1px solid #ccd0d4; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
        .unified-hs-settings-section h2 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .unified-hs-add-form { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .form-row { display: flex; gap: 10px; align-items: center; }
        .form-row input[type="text"] { flex: 1; padding: 8px 12px; font-size: 14px; }
        .unified-hs-terms-list { margin-top: 20px; }
        .unified-hs-terms-list table { margin-top: 10px; }
        @media (max-width: 1200px) { .unified-hs-settings-grid { grid-template-columns: 1fr; } }
        </style>
        <?php
    }

    /**
     * Рендеринг страницы логов импорта
     */
    public function render_logs_page() {
        if (!current_user_can(Unified_HS_Capabilities::CAP_VIEW_LOGS)) {
            wp_die(esc_html__('Недостаточно прав', 'unified-hs-plugins'));
        }

        if (isset($_POST['clear_logs']) && check_admin_referer('unified_hs_logs_action')) {
            if (!current_user_can(Unified_HS_Capabilities::CAP_CLEAR_LOGS)) {
                wp_die(esc_html__('Недостаточно прав', 'unified-hs-plugins'));
            }
            if (class_exists('Unified_HS_Ingest_Logs')) {
                Unified_HS_Ingest_Logs::clear();
            }
            echo '<div class="notice notice-success is-dismissible"><p>Логи очищены.</p></div>';
        }

        // Источник логов — кастомная таблица (см. includes/class-ingest-logs.php).
        $logs = class_exists('Unified_HS_Ingest_Logs')
            ? Unified_HS_Ingest_Logs::recent(200)
            : array();
        ?>
        <div class="wrap unified-hs-logs">
            <h1>Логи импорта</h1>
            <?php if (current_user_can(Unified_HS_Capabilities::CAP_CLEAR_LOGS)): ?>
            <form method="post">
                <?php wp_nonce_field('unified_hs_logs_action'); ?>
                <input type="hidden" name="clear_logs" value="1">
                <button type="submit" class="button">Очистить логи</button>
            </form>
            <?php endif; ?>
            <table class="widefat striped" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th>Время</th>
                        <th>Статус</th>
                        <th>Колода</th>
                        <th>Код</th>
                        <th>Стример</th>
                        <th>Формат</th>
                        <th>Сообщение</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html(isset($log['time']) ? $log['time'] : ''); ?></td>
                            <td><?php echo esc_html(isset($log['status']) ? $log['status'] : ''); ?></td>
                            <td><?php echo esc_html(isset($log['deck_name']) ? $log['deck_name'] : ''); ?></td>
                            <td><code><?php echo esc_html(isset($log['deck_code']) ? $log['deck_code'] : ''); ?></code></td>
                            <td><?php echo esc_html(isset($log['streamer']) ? $log['streamer'] : ''); ?></td>
                            <td><?php echo esc_html(isset($log['format']) ? $log['format'] : ''); ?></td>
                            <td><?php echo esc_html(isset($log['message']) ? $log['message'] : ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">Логи пока пусты.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_stats_page() {
        if (!current_user_can(Unified_HS_Capabilities::CAP_VIEW_STATS)) {
            wp_die(esc_html__('Недостаточно прав', 'unified-hs-plugins'));
        }

        $period = $this->get_stats_period();
        $filters = $this->get_stats_filters();
        $data = $this->get_stats_page_data($period['from'], $period['to'], empty($period['is_filtered']), $filters);
        $summary = $data['summary'];
        $engagement = isset($data['engagement']) ? $data['engagement'] : array();
        $ad_counts = class_exists('Unified_HS_Ad_Tracker')
            ? Unified_HS_Ad_Tracker::event_counts($period['from'], $period['to'])
            : array('impressions' => 0, 'clicks' => 0, 'ctr' => 0);
        ?>
        <div class="wrap unified-hs-dashboard unified-hs-analytics">
            <h1>Статистика</h1>
            <p class="subtitle">Популярность колод, архетипов, классов и режимов по выбранному периоду.</p>

            <form method="get" class="unified-hs-stats-filter">
                <input type="hidden" name="page" value="unified-hs-stats">
                <label>
                    <span>Период с</span>
                    <input type="date" name="period_from" value="<?php echo esc_attr($period['from']); ?>">
                </label>
                <label>
                    <span>по</span>
                    <input type="date" name="period_to" value="<?php echo esc_attr($period['to']); ?>">
                </label>
                <?php $this->render_stats_filter_select('Класс', 'deck_class', 'stats_class', $filters['class'], 'Все классы'); ?>
                <?php $this->render_stats_filter_select('Режим', 'deck_mode', 'stats_mode', $filters['mode'], 'Все режимы'); ?>
                <?php $this->render_stats_filter_select('Архетип', 'deck_archetype', 'stats_archetype', $filters['archetype'], 'Все архетипы'); ?>
                <?php $this->render_stats_filter_select('Стример', 'deck_streamer', 'stats_streamer', $filters['streamer'], 'Все стримеры'); ?>
                <?php $this->render_stats_filter_select('Источник', 'deck_source', 'stats_source', $filters['source'], 'Все источники'); ?>
                <label>
                    <span>Игр от</span>
                    <input type="number" name="stats_games_min" value="<?php echo esc_attr($filters['games_min'] ?: ''); ?>" min="0" step="1">
                </label>
                <label>
                    <span>WR от</span>
                    <input type="number" name="stats_winrate_min" value="<?php echo esc_attr($filters['winrate_min'] ?: ''); ?>" min="0" max="100" step="0.1">
                </label>
                <label>
                    <span>Создана</span>
                    <select name="stats_created">
                        <option value=""<?php selected($filters['created'], ''); ?>>Любая дата</option>
                        <option value="period"<?php selected($filters['created'], 'period'); ?>>За выбранный период</option>
                    </select>
                </label>
                <label>
                    <span>Сортировка</span>
                    <select name="stats_sort">
                        <option value="interest"<?php selected($filters['sort'], 'interest'); ?>>Интерес</option>
                        <option value="copies"<?php selected($filters['sort'], 'copies'); ?>>Копии</option>
                        <option value="views"<?php selected($filters['sort'], 'views'); ?>>Просмотры</option>
                        <option value="games"<?php selected($filters['sort'], 'games'); ?>>Игры</option>
                        <option value="winrate"<?php selected($filters['sort'], 'winrate'); ?>>Winrate</option>
                        <option value="likes"<?php selected($filters['sort'], 'likes'); ?>>Лайки</option>
                        <option value="newest"<?php selected($filters['sort'], 'newest'); ?>>Новые</option>
                    </select>
                </label>
                <?php submit_button('Показать', 'primary', 'submit', false); ?>
                <div class="unified-hs-period-presets">
                    <button type="submit" class="button" name="period_preset" value="today">Сегодня</button>
                    <button type="submit" class="button" name="period_preset" value="yesterday">Вчера</button>
                    <button type="submit" class="button" name="period_preset" value="3">3 дня</button>
                    <button type="submit" class="button" name="period_preset" value="7">7 дней</button>
                    <button type="submit" class="button" name="period_preset" value="30">30 дней</button>
                    <button type="submit" class="button" name="period_preset" value="90">90 дней</button>
                    <button type="submit" class="button" name="period_preset" value="365">Год</button>
                </div>
            </form>

            <?php if ($data['source'] === 'card_totals'): ?>
                <div class="notice notice-info">
                    <p>За выбранный период пока нет импортированных строк. Ниже показаны суммарные данные из карточек колод.</p>
                </div>
            <?php elseif ($data['source'] === 'copy_events'): ?>
                <div class="notice notice-info">
                    <p>За этот период найдены копирования кодов, но нет импортированных games/winrate. Популярность показана по копиям за даты.</p>
                </div>
            <?php elseif ($data['source'] === 'engagement_events'): ?>
                <div class="notice notice-info">
                    <p>За этот период найдены события интереса к колодам, но нет импортированных games/winrate. Рейтинг обзоров показан по действиям пользователей.</p>
                </div>
            <?php elseif ($data['source'] === 'created_decks'): ?>
                <div class="notice notice-info">
                    <p>За этот период нет импортированных games/winrate, поэтому показаны новые колоды и архетипы, созданные в выбранные даты.</p>
                </div>
            <?php elseif ($data['source'] === 'empty_period'): ?>
                <div class="notice notice-warning">
                    <p>За выбранный период нет данных games/winrate и событий копирования. Импортируйте статистику за дни/недели или дождитесь новых копирований.</p>
                </div>
            <?php endif; ?>

            <div class="unified-hs-stats-grid unified-hs-stats-grid--analytics">
                <?php $this->render_metric_card('Игр за период', number_format_i18n($summary['games']), 'primary'); ?>
                <?php $this->render_metric_card('Колоды в отчёте', number_format_i18n($summary['decks']), 'neutral'); ?>
                <?php $this->render_metric_card('Новых за период', number_format_i18n($summary['new_decks']), 'success'); ?>
                <?php $this->render_metric_card('Средний winrate', number_format_i18n($summary['winrate'], 1), 'success', '%'); ?>
                <?php $this->render_metric_card('Лайков', number_format_i18n($summary['likes']), 'success'); ?>
                <?php $this->render_metric_card('Дизлайков', number_format_i18n($summary['dislikes']), 'warning'); ?>
                <?php $this->render_metric_card('Копирований кода', number_format_i18n($summary['copies']), 'primary'); ?>
                <?php $this->render_metric_card('Просмотров карточек', number_format_i18n(isset($engagement['deck_view']) ? $engagement['deck_view'] : 0), 'neutral'); ?>
                <?php $this->render_metric_card('Открытий картинок', number_format_i18n(isset($engagement['image_open']) ? $engagement['image_open'] : 0), 'neutral'); ?>
                <?php $this->render_metric_card('Кликов источника', number_format_i18n(isset($engagement['source_click']) ? $engagement['source_click'] : 0), 'neutral'); ?>
                <?php $this->render_metric_card('Показов баннера', number_format_i18n($ad_counts['impressions']), 'neutral'); ?>
                <?php $this->render_metric_card('Кликов по баннеру', number_format_i18n($ad_counts['clicks']), 'primary'); ?>
                <?php $this->render_metric_card('CTR баннера', number_format_i18n($ad_counts['ctr'], 2), 'success', '%'); ?>
            </div>

            <p class="unified-hs-muted unified-hs-data-note">
                Источник: игры и winrate берутся из импортированной периодной статистики, интерес игроков — из анонимных событий карточек за выбранный период.
            </p>

            <?php $this->render_engagement_charts($data); ?>

            <div class="unified-hs-analytics-grid">
                <?php $this->render_stats_rank_panel('Популярные архетипы', $data['archetypes'], 'Архетипы появятся после тегов и статистики.', true, $period); ?>
                <?php $this->render_stats_rank_panel('Классы', $data['classes'], 'Пока нет данных по классам.'); ?>
                <?php $this->render_stats_rank_panel('Режимы', $data['modes'], 'Пока нет данных по режимам.'); ?>
            </div>

            <?php $this->render_archetype_trend_modal(); ?>
            <?php $this->render_popular_decks_table($data['top_decks']); ?>
        </div>
        <?php
    }

    public function render_archetypes_page() {
        if (!current_user_can(Unified_HS_Capabilities::CAP_MANAGE_TERMS)) {
            wp_die(esc_html__('Недостаточно прав', 'unified-hs-plugins'));
        }

        if (isset($_POST['unified_hs_archetypes_save']) && check_admin_referer('unified_hs_archetypes_save')) {
            $images = isset($_POST['archetype_image']) && is_array($_POST['archetype_image'])
                ? wp_unslash($_POST['archetype_image'])
                : array();

            foreach ($images as $term_id => $image_id) {
                $term_id = absint($term_id);
                $image_id = absint($image_id);
                if (!$term_id) {
                    continue;
                }
                if ($image_id > 0) {
                    $this->ensure_archetype_image_sizes($image_id);
                    update_term_meta($term_id, '_hs_archetype_image_id', $image_id);
                } else {
                    delete_term_meta($term_id, '_hs_archetype_image_id');
                }
            }

            echo '<div class="notice notice-success is-dismissible"><p>Картинки архетипов сохранены.</p></div>';
        }

        $terms = get_terms(array(
            'taxonomy' => 'deck_archetype',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ));
        if (is_wp_error($terms)) {
            $terms = array();
        }
        $terms = array_values(array_filter($terms, static function($term) {
            return isset($term->count) && (int) $term->count > 5;
        }));
        ?>
        <div class="wrap unified-hs-dashboard unified-hs-archetypes-admin">
            <h1>Архетипы</h1>
            <p class="subtitle">Назначайте изображения для карточек архетипов и SEO-страниц. Здесь показаны только архетипы, в которых больше 5 колод.</p>

            <form method="post">
                <?php wp_nonce_field('unified_hs_archetypes_save'); ?>
                <input type="hidden" name="unified_hs_archetypes_save" value="1">

                <div class="unified-hs-archetype-admin-grid">
                    <?php if (empty($terms)): ?>
                        <p>Архетипов с количеством колод больше 5 пока нет.</p>
                    <?php else: ?>
                        <?php foreach ($terms as $term): ?>
                            <?php
                            $image_id = absint(get_term_meta($term->term_id, '_hs_archetype_image_id', true));
                            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'hs_archetype_card') : '';
                            if (!$image_url && $image_id) {
                                $image_url = wp_get_attachment_image_url($image_id, 'medium_large');
                            }
                            $term_link = get_term_link($term);
                            ?>
                            <section class="unified-hs-archetype-admin-card" data-archetype-card>
                                <div class="unified-hs-archetype-admin-card__image" data-image-preview style="<?php echo $image_url ? 'background-image:url(' . esc_url($image_url) . ');' : ''; ?>">
                                    <?php if (!$image_url): ?>
                                        <span>Нет картинки</span>
                                    <?php endif; ?>
                                </div>
                                <div class="unified-hs-archetype-admin-card__body">
                                    <h2><?php echo esc_html($term->name); ?></h2>
                                    <p><?php echo esc_html(number_format_i18n($term->count)); ?> колод</p>
                                    <input type="hidden" name="archetype_image[<?php echo esc_attr($term->term_id); ?>]" value="<?php echo esc_attr($image_id); ?>" data-image-id>
                                    <div class="unified-hs-archetype-admin-card__actions">
                                        <button type="button" class="button button-secondary" data-upload-image>Выбрать картинку</button>
                                        <button type="button" class="button" data-remove-image>Убрать</button>
                                    </div>
                                    <code>[hs_deck_archetype slug="<?php echo esc_attr($term->slug); ?>"]</code>
                                    <?php if (!is_wp_error($term_link)): ?>
                                        <a href="<?php echo esc_url($term_link); ?>" target="_blank" rel="noopener">Открыть страницу</a>
                                    <?php endif; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php submit_button('Сохранить картинки архетипов'); ?>
            </form>
        </div>
        <script>
        jQuery(function($) {
            var frame;

            $(document).on('click', '[data-upload-image]', function(event) {
                event.preventDefault();
                var card = $(this).closest('[data-archetype-card]');

                frame = wp.media({
                    title: 'Выберите картинку архетипа',
                    button: { text: 'Использовать картинку' },
                    library: { type: 'image' },
                    multiple: false
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    card.find('[data-image-id]').val(attachment.id);
                    card.find('[data-image-preview]')
                        .css('background-image', 'url(' + attachment.url + ')')
                        .empty();
                });

                frame.open();
            });

            $(document).on('click', '[data-remove-image]', function(event) {
                event.preventDefault();
                var card = $(this).closest('[data-archetype-card]');
                card.find('[data-image-id]').val('');
                card.find('[data-image-preview]').css('background-image', '').html('<span>Нет картинки</span>');
            });
        });
        </script>
        <?php
    }

    private function ensure_archetype_image_sizes($image_id) {
        $image_id = absint($image_id);
        if (!$image_id || !wp_attachment_is_image($image_id)) {
            return;
        }

        $metadata = wp_get_attachment_metadata($image_id);
        $sizes = isset($metadata['sizes']) && is_array($metadata['sizes']) ? $metadata['sizes'] : array();
        if (isset($sizes['hs_archetype_card'], $sizes['hs_archetype_hero'])) {
            return;
        }

        $file = get_attached_file($image_id);
        if (!$file || !file_exists($file)) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $new_metadata = wp_generate_attachment_metadata($image_id, $file);
        if (!empty($new_metadata)) {
            wp_update_attachment_metadata($image_id, $new_metadata);
        }
    }

    private function get_stats_period() {
        $today = current_time('Y-m-d');
        $default_from = wp_date('Y-m-d', current_time('timestamp') - (29 * DAY_IN_SECONDS));
        $preset = isset($_GET['period_preset']) ? sanitize_key(wp_unslash($_GET['period_preset'])) : '';
        $is_filtered = isset($_GET['period_preset']) || isset($_GET['period_from']) || isset($_GET['period_to']);

        if ($preset === 'today') {
            return array('from' => $today, 'to' => $today, 'preset' => $preset, 'is_filtered' => true);
        }
        if ($preset === 'yesterday') {
            $yesterday = wp_date('Y-m-d', current_time('timestamp') - DAY_IN_SECONDS);
            return array('from' => $yesterday, 'to' => $yesterday, 'preset' => $preset, 'is_filtered' => true);
        }
        if (in_array((int) $preset, array(3, 7, 30, 90, 365), true)) {
            $days = (int) $preset;
            $from = wp_date('Y-m-d', current_time('timestamp') - (($days - 1) * DAY_IN_SECONDS));
            return array('from' => $from, 'to' => $today, 'preset' => (string) $days, 'is_filtered' => true);
        }

        $from = isset($_GET['period_from']) ? sanitize_text_field(wp_unslash($_GET['period_from'])) : $default_from;
        $to = isset($_GET['period_to']) ? sanitize_text_field(wp_unslash($_GET['period_to'])) : $today;

        $from = $this->valid_date_or_default($from, $default_from);
        $to = $this->valid_date_or_default($to, $today);

        if ($from > $to) {
            $swap = $from;
            $from = $to;
            $to = $swap;
        }

        return array('from' => $from, 'to' => $to, 'preset' => 'custom', 'is_filtered' => $is_filtered);
    }

    private function get_stats_filters() {
        $created = isset($_GET['stats_created']) ? sanitize_key(wp_unslash($_GET['stats_created'])) : '';
        if (!in_array($created, array('', 'period'), true)) {
            $created = '';
        }

        $sort = isset($_GET['stats_sort']) ? sanitize_key(wp_unslash($_GET['stats_sort'])) : 'interest';
        if (!in_array($sort, array('interest', 'copies', 'views', 'games', 'winrate', 'likes', 'newest'), true)) {
            $sort = 'interest';
        }

        return array(
            'class' => isset($_GET['stats_class']) ? sanitize_title(wp_unslash($_GET['stats_class'])) : '',
            'mode' => isset($_GET['stats_mode']) ? sanitize_title(wp_unslash($_GET['stats_mode'])) : '',
            'archetype' => isset($_GET['stats_archetype']) ? sanitize_title(wp_unslash($_GET['stats_archetype'])) : '',
            'streamer' => isset($_GET['stats_streamer']) ? sanitize_title(wp_unslash($_GET['stats_streamer'])) : '',
            'source' => isset($_GET['stats_source']) ? sanitize_title(wp_unslash($_GET['stats_source'])) : '',
            'games_min' => isset($_GET['stats_games_min']) ? absint($_GET['stats_games_min']) : 0,
            'winrate_min' => isset($_GET['stats_winrate_min']) ? max(0, min(100, (float) str_replace(',', '.', wp_unslash($_GET['stats_winrate_min'])))) : 0,
            'created' => $created,
            'sort' => $sort,
        );
    }

    private function render_stats_filter_select($label, $taxonomy, $name, $selected, $placeholder) {
        if (!taxonomy_exists($taxonomy)) {
            return;
        }

        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
        ));
        if (is_wp_error($terms)) {
            $terms = array();
        }
        ?>
        <label>
            <span><?php echo esc_html($label); ?></span>
            <select name="<?php echo esc_attr($name); ?>">
                <option value=""><?php echo esc_html($placeholder); ?></option>
                <?php foreach ($terms as $term): ?>
                    <option value="<?php echo esc_attr($term->slug); ?>"<?php selected($selected, $term->slug); ?>><?php echo esc_html($term->name); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php
    }

    private function valid_date_or_default($value, $default) {
        $date = DateTime::createFromFormat('Y-m-d', (string) $value);
        if ($date && $date->format('Y-m-d') === $value) {
            return $value;
        }
        return $default;
    }

    private function stats_period_url($from_offset) {
        $to = current_time('Y-m-d');
        $from = wp_date('Y-m-d', strtotime($from_offset, current_time('timestamp')));

        return add_query_arg(array(
            'page' => 'unified-hs-stats',
            'period_from' => $from,
            'period_to' => $to,
        ), admin_url('admin.php'));
    }

    private function get_stats_page_data($from, $to, $allow_card_totals_fallback = true, array $filters = array()) {
        $limit = max(50, min(2000, (int) apply_filters('unified_hs_stats_page_deck_limit', 500)));
        $rows = $this->query_period_stats_rows($from, $to, $limit);
        $copy_counts = class_exists('Unified_HS_Deck_Copy_Events')
            ? Unified_HS_Deck_Copy_Events::counts_by_post($from, $to)
            : array();
        $event_counts = class_exists('Unified_HS_Deck_Events')
            ? Unified_HS_Deck_Events::counts_by_post($from, $to)
            : array();
        $created_rows = $this->query_created_deck_rows($from, $to, $limit);
        $source = empty($rows) ? 'empty_period' : 'period_stats';
        $use_period_copy_counts = true;

        if (!empty($copy_counts)) {
            $rows = $this->merge_copy_count_rows($rows, $copy_counts, $limit);
            if ($source === 'empty_period') {
                $source = 'copy_events';
            }
        }
        if (!empty($event_counts)) {
            $rows = $this->merge_event_count_rows($rows, $event_counts, $limit);
            if ($source === 'empty_period') {
                $source = 'engagement_events';
            }
        }
        if (!empty($created_rows)) {
            $rows = $this->merge_created_deck_rows($rows, $created_rows, $limit);
            if ($source === 'empty_period') {
                $source = 'created_decks';
            }
        }

        if (empty($rows) && $allow_card_totals_fallback) {
            $rows = $this->query_card_total_stats_rows($limit);
            $source = 'card_totals';
            $use_period_copy_counts = false;
        }

        return $this->build_stats_page_data($rows, $source, $copy_counts, $use_period_copy_counts, $event_counts, $filters, $from, $to);
    }

    private function merge_created_deck_rows(array $rows, array $created_rows, $limit) {
        $indexed = array();

        foreach ($rows as $row) {
            $post_id = absint(isset($row['post_id']) ? $row['post_id'] : 0);
            if ($post_id) {
                $indexed[$post_id] = $row;
            }
        }

        foreach ($created_rows as $row) {
            $post_id = absint(isset($row['post_id']) ? $row['post_id'] : 0);
            if (!$post_id || isset($indexed[$post_id])) {
                continue;
            }
            $indexed[$post_id] = $row;
        }

        $merged_limit = min(2000, max(absint($limit), count($indexed)));
        return array_slice(array_values($indexed), 0, max(1, $merged_limit));
    }

    private function merge_copy_count_rows(array $rows, array $copy_counts, $limit) {
        $indexed = array();

        foreach ($rows as $row) {
            $post_id = absint(isset($row['post_id']) ? $row['post_id'] : 0);
            if (!$post_id) {
                continue;
            }
            $indexed[$post_id] = $row;
        }

        foreach ($copy_counts as $post_id => $copies) {
            $post_id = absint($post_id);
            if (!$post_id || isset($indexed[$post_id])) {
                continue;
            }

            $indexed[$post_id] = array(
                'post_id' => $post_id,
                'games' => 0,
                'wins' => 0,
                'losses' => 0,
            );
        }

        $merged_limit = min(2000, max(absint($limit), count($indexed)));
        return array_slice(array_values($indexed), 0, max(1, $merged_limit));
    }

    private function merge_event_count_rows(array $rows, array $event_counts, $limit) {
        $indexed = array();

        foreach ($rows as $row) {
            $post_id = absint(isset($row['post_id']) ? $row['post_id'] : 0);
            if ($post_id) {
                $indexed[$post_id] = $row;
            }
        }

        foreach ($event_counts as $post_id => $events) {
            $post_id = absint($post_id);
            if (!$post_id || isset($indexed[$post_id])) {
                continue;
            }
            $indexed[$post_id] = array(
                'post_id' => $post_id,
                'games' => 0,
                'wins' => 0,
                'losses' => 0,
            );
        }

        $merged_limit = min(2000, max(absint($limit), count($indexed)));
        return array_slice(array_values($indexed), 0, max(1, $merged_limit));
    }

    private function query_period_stats_rows($from, $to, $limit) {
        if (!class_exists('Unified_HS_Deck_Period_Stats')) {
            return array();
        }

        global $wpdb;
        $table = Unified_HS_Deck_Period_Stats::table_name();
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        if ($exists !== $table) {
            return array();
        }

        $row_limit = max($limit, min(10000, $limit * 10));
        $sql = $wpdb->prepare(
            "SELECT s.post_id, s.period_start, s.period_end, s.games, s.wins, s.losses
             FROM {$table} s
             INNER JOIN {$wpdb->posts} p ON p.ID = s.post_id
             WHERE s.period_start <= %s
               AND s.period_end >= %s
               AND p.post_type = %s
               AND p.post_status NOT IN ('trash', 'auto-draft')
             ORDER BY s.period_end DESC, s.games DESC
             LIMIT %d",
            $to,
            $from,
            'hs_deck',
            $row_limit
        );

        $raw_rows = $wpdb->get_results($sql, ARRAY_A);
        if (!is_array($raw_rows) || empty($raw_rows)) {
            return array();
        }

        $aggregated = array();
        foreach ($raw_rows as $row) {
            $post_id = absint($row['post_id']);
            if (!$post_id) {
                continue;
            }

            $ratio = $this->period_overlap_ratio($row['period_start'], $row['period_end'], $from, $to);
            if ($ratio <= 0) {
                continue;
            }

            if (!isset($aggregated[$post_id])) {
                $aggregated[$post_id] = array(
                    'post_id' => $post_id,
                    'games' => 0.0,
                    'wins' => 0.0,
                    'losses' => 0.0,
                );
            }

            $aggregated[$post_id]['games'] += absint($row['games']) * $ratio;
            $aggregated[$post_id]['wins'] += absint($row['wins']) * $ratio;
            $aggregated[$post_id]['losses'] += absint($row['losses']) * $ratio;
        }

        $rows = array();
        foreach ($aggregated as $row) {
            $games = (int) round($row['games']);
            $wins = (int) round($row['wins']);
            $losses = (int) round($row['losses']);
            if ($games <= 0 && ($wins + $losses) > 0) {
                $games = $wins + $losses;
            }
            if ($wins + $losses > $games) {
                $games = $wins + $losses;
            }

            $rows[] = array(
                'post_id' => $row['post_id'],
                'games' => $games,
                'wins' => $wins,
                'losses' => $losses,
            );
        }

        usort($rows, array($this, 'sort_by_games_desc'));
        return array_slice($rows, 0, max(1, absint($limit)));
    }

    private function period_overlap_ratio($row_from, $row_to, $filter_from, $filter_to) {
        $period_start = strtotime((string) $row_from . ' 00:00:00');
        $period_end = strtotime((string) $row_to . ' 00:00:00');
        $filter_start = strtotime((string) $filter_from . ' 00:00:00');
        $filter_end = strtotime((string) $filter_to . ' 00:00:00');

        if (!$period_start || !$period_end || !$filter_start || !$filter_end || $period_start > $period_end || $filter_start > $filter_end) {
            return 0.0;
        }

        $overlap_start = max($period_start, $filter_start);
        $overlap_end = min($period_end, $filter_end);
        if ($overlap_start > $overlap_end) {
            return 0.0;
        }

        $period_days = (int) floor(($period_end - $period_start) / DAY_IN_SECONDS) + 1;
        $overlap_days = (int) floor(($overlap_end - $overlap_start) / DAY_IN_SECONDS) + 1;
        if ($period_days <= 0 || $overlap_days <= 0) {
            return 0.0;
        }

        return min(1.0, $overlap_days / $period_days);
    }

    private function sort_by_games_desc($a, $b) {
        $a_games = isset($a['games']) ? (int) $a['games'] : 0;
        $b_games = isset($b['games']) ? (int) $b['games'] : 0;
        return $b_games <=> $a_games;
    }

    private function query_card_total_stats_rows($limit) {
        $post_ids = get_posts(array(
            'post_type' => 'hs_deck',
            'post_status' => array('publish', 'private', 'draft', 'pending', 'future'),
            'posts_per_page' => $limit,
            'fields' => 'ids',
            'orderby' => 'modified',
            'order' => 'DESC',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'ignore_sticky_posts' => true,
        ));

        if (empty($post_ids)) {
            return array();
        }

        update_meta_cache('post', $post_ids);
        $rows = array();
        foreach ($post_ids as $post_id) {
            $games = absint(get_post_meta($post_id, '_deck_games', true));
            $wins = absint(get_post_meta($post_id, '_deck_wins', true));
            $losses = absint(get_post_meta($post_id, '_deck_losses', true));
            $winrate = $this->float_meta($post_id, '_deck_winrate');

            if ($games <= 0 && ($wins + $losses) > 0) {
                $games = $wins + $losses;
            }
            if ($games > 0 && $wins <= 0 && $losses <= 0 && $winrate > 0) {
                $wins = (int) round($games * ($winrate / 100));
                $losses = max(0, $games - $wins);
            }

            $rows[] = array(
                'post_id' => $post_id,
                'games' => $games,
                'wins' => $wins,
                'losses' => $losses,
            );
        }

        return $rows;
    }

    private function query_created_deck_rows($from, $to, $limit) {
        $from = $this->valid_date_or_default($from, current_time('Y-m-d'));
        $to = $this->valid_date_or_default($to, current_time('Y-m-d'));

        $post_ids = get_posts(array(
            'post_type' => 'hs_deck',
            'post_status' => array('publish', 'private', 'draft', 'pending', 'future'),
            'posts_per_page' => max(1, min(2000, absint($limit))),
            'fields' => 'ids',
            'orderby' => 'date',
            'order' => 'DESC',
            'date_query' => array(
                array(
                    'after' => $from . ' 00:00:00',
                    'before' => $to . ' 23:59:59',
                    'inclusive' => true,
                    'column' => 'post_date',
                ),
            ),
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'ignore_sticky_posts' => true,
        ));

        if (empty($post_ids)) {
            return array();
        }

        update_meta_cache('post', $post_ids);
        $rows = array();
        foreach ($post_ids as $post_id) {
            $games = absint(get_post_meta($post_id, '_deck_games', true));
            $wins = absint(get_post_meta($post_id, '_deck_wins', true));
            $losses = absint(get_post_meta($post_id, '_deck_losses', true));
            $winrate = $this->float_meta($post_id, '_deck_winrate');

            if ($games <= 0 && ($wins + $losses) > 0) {
                $games = $wins + $losses;
            }
            if ($games > 0 && $wins <= 0 && $losses <= 0 && $winrate > 0) {
                $wins = (int) round($games * ($winrate / 100));
                $losses = max(0, $games - $wins);
            }

            $rows[] = array(
                'post_id' => $post_id,
                'games' => $games,
                'wins' => $wins,
                'losses' => $losses,
            );
        }

        return $rows;
    }

    private function build_stats_page_data(array $rows, $source, array $period_copy_counts = array(), $use_period_copy_counts = false, array $event_counts = array(), array $filters = array(), $from = '', $to = '') {
        $post_ids = array_values(array_unique(array_filter(array_map('absint', wp_list_pluck($rows, 'post_id')))));
        if (!empty($post_ids)) {
            update_meta_cache('post', $post_ids);
            update_object_term_cache($post_ids, 'hs_deck');
        }

        $summary = array(
            'decks' => 0,
            'games' => 0,
            'wins' => 0,
            'losses' => 0,
            'likes' => 0,
            'dislikes' => 0,
            'copies' => 0,
            'new_decks' => 0,
            'winrate' => 0,
        );
        $classes = array();
        $modes = array();
        $archetypes = array();
        $top_decks = array();

        foreach ($rows as $row) {
            $post_id = absint($row['post_id']);
            if (!$post_id || get_post_type($post_id) !== 'hs_deck') {
                continue;
            }

            $games = absint(isset($row['games']) ? $row['games'] : 0);
            $wins = absint(isset($row['wins']) ? $row['wins'] : 0);
            $losses = absint(isset($row['losses']) ? $row['losses'] : 0);
            if ($games <= 0 && ($wins + $losses) > 0) {
                $games = $wins + $losses;
            }

            $likes = absint(get_post_meta($post_id, '_deck_likes', true));
            $dislikes = absint(get_post_meta($post_id, '_deck_dislikes', true));
            $copies = $use_period_copy_counts
                ? (isset($period_copy_counts[$post_id]) ? absint($period_copy_counts[$post_id]) : 0)
                : absint(get_post_meta($post_id, '_deck_copies', true));
            $deck_events = isset($event_counts[$post_id]) ? $event_counts[$post_id] : array();
            if (class_exists('Unified_HS_Deck_Events')) {
                $copies = max($copies, Unified_HS_Deck_Events::event_value($deck_events, Unified_HS_Deck_Events::EVENT_DECK_COPY));
                $period_likes = Unified_HS_Deck_Events::event_value($deck_events, Unified_HS_Deck_Events::EVENT_LIKE);
                $period_dislikes = Unified_HS_Deck_Events::event_value($deck_events, Unified_HS_Deck_Events::EVENT_DISLIKE);
                if ($period_likes > 0 || $period_dislikes > 0) {
                    $likes = $period_likes;
                    $dislikes = $period_dislikes;
                }
            }
            $winrate = $games > 0 ? round(($wins / $games) * 100, 1) : $this->float_meta($post_id, '_deck_winrate');
            $published_ts = (int) get_post_time('U', true, $post_id);
            $created_date = get_post_time('Y-m-d', false, $post_id);
            $is_new_in_period = $created_date >= $from && $created_date <= $to;
            $views = class_exists('Unified_HS_Deck_Events') ? Unified_HS_Deck_Events::event_value($deck_events, Unified_HS_Deck_Events::EVENT_DECK_VIEW) : 0;
            $image_opens = class_exists('Unified_HS_Deck_Events') ? Unified_HS_Deck_Events::event_value($deck_events, Unified_HS_Deck_Events::EVENT_IMAGE_OPEN) : 0;
            $proof_opens = class_exists('Unified_HS_Deck_Events') ? Unified_HS_Deck_Events::event_value($deck_events, Unified_HS_Deck_Events::EVENT_PROOF_OPEN) : 0;
            $source_clicks = class_exists('Unified_HS_Deck_Events') ? Unified_HS_Deck_Events::event_value($deck_events, Unified_HS_Deck_Events::EVENT_SOURCE_CLICK) : 0;
            $interest_score = class_exists('Unified_HS_Deck_Events')
                ? Unified_HS_Deck_Events::review_score($deck_events, $games, $published_ts)
                : $this->deck_popularity_score($games, $likes, $dislikes, $copies);
            if ($is_new_in_period) {
                $interest_score += 2.5;
            }
            $score = $this->stats_sort_score($filters['sort'], array(
                'interest' => $interest_score,
                'copies' => $copies,
                'views' => $views,
                'games' => $games,
                'winrate' => $winrate,
                'likes' => $likes,
                'published_ts' => $published_ts,
            ));
            $class_names = $this->get_term_names_for_deck($post_id, 'deck_class');
            $mode_names = $this->get_term_names_for_deck($post_id, 'deck_mode');
            $archetype_terms = taxonomy_exists('deck_archetype')
                ? $this->get_terms_for_deck($post_id, 'deck_archetype')
                : array();
            $archetype_names = array_values(array_map(static function($term) {
                return $term['name'];
            }, $archetype_terms));
            $tags = class_exists('HS_Decks_Manager')
                ? HS_Decks_Manager::custom_tags_to_array(get_post_meta($post_id, '_custom_tags', true))
                : array_filter(array_map('trim', explode(',', (string) get_post_meta($post_id, '_custom_tags', true))));

            if (!$this->deck_matches_stats_filters($post_id, $games, $winrate, $filters, $is_new_in_period)) {
                continue;
            }

            $deck_item = array(
                'id' => $post_id,
                'title' => get_the_title($post_id),
                'games' => $games,
                'wins' => $wins,
                'losses' => $losses,
                'winrate' => $winrate,
                'likes' => $likes,
                'dislikes' => $dislikes,
                'copies' => $copies,
                'score' => $score,
                'interest_score' => $interest_score,
                'views' => $views,
                'image_opens' => $image_opens,
                'proof_opens' => $proof_opens,
                'source_clicks' => $source_clicks,
                'is_new' => $is_new_in_period,
                'classes' => $class_names,
                'modes' => $mode_names,
                'archetypes' => $archetype_names,
                'tags' => $tags,
                'status' => $this->get_deck_visibility_label($post_id),
                'modified' => get_post_modified_time('d.m.Y H:i', false, $post_id),
            );

            $summary['decks']++;
            $summary['games'] += $games;
            $summary['wins'] += $wins;
            $summary['losses'] += $losses;
            $summary['likes'] += $likes;
            $summary['dislikes'] += $dislikes;
            $summary['copies'] += $copies;
            if ($is_new_in_period) {
                $summary['new_decks']++;
            }

            foreach (!empty($class_names) ? $class_names : array('Без класса') as $label) {
                $this->add_stats_bucket($classes, $label, $deck_item);
            }
            foreach (!empty($mode_names) ? $mode_names : array('Без режима') as $label) {
                $this->add_stats_bucket($modes, $label, $deck_item);
            }

            if (!empty($archetype_terms)) {
                foreach ($archetype_terms as $term) {
                    $this->add_stats_bucket($archetypes, $term['name'], $deck_item, $term['id']);
                }
            } else {
                $archetype_labels = array();
                $fallback_tags = !empty($tags) ? $tags : array('Без тега');
                $class_prefix = !empty($class_names) ? $class_names[0] . ': ' : '';
                foreach ($fallback_tags as $tag) {
                    $archetype_labels[] = $class_prefix . $tag;
                }
                foreach ($archetype_labels as $label) {
                    $this->add_stats_bucket($archetypes, $label, $deck_item);
                }
            }

            $top_decks[] = $deck_item;
        }

        $summary['winrate'] = $summary['games'] > 0 ? round(($summary['wins'] / $summary['games']) * 100, 1) : 0;
        usort($top_decks, array($this, 'sort_by_score_desc'));

        return array(
            'source' => $source,
            'summary' => $summary,
            'engagement' => class_exists('Unified_HS_Deck_Events') ? Unified_HS_Deck_Events::summary_counts($from, $to) : array(),
            'daily_events' => class_exists('Unified_HS_Deck_Events') ? Unified_HS_Deck_Events::daily_counts($from, $to) : array(),
            'classes' => $this->sort_stats_buckets($classes, 10),
            'modes' => $this->sort_stats_buckets($modes, 10),
            'archetypes' => $this->sort_stats_buckets($archetypes, 12),
            'top_decks' => array_slice($top_decks, 0, 25),
        );
    }

    private function add_stats_bucket(array &$buckets, $label, array $deck, $term_id = 0) {
        $label = trim((string) $label);
        if ($label === '') {
            $label = 'Без названия';
        }
        $term_id = absint($term_id);
        $bucket_key = $term_id > 0 ? 'term_' . $term_id : $label;

        if (!isset($buckets[$bucket_key])) {
            $buckets[$bucket_key] = array(
                'label' => $label,
                'term_id' => $term_id,
                'decks' => 0,
                'games' => 0,
                'wins' => 0,
                'losses' => 0,
                'likes' => 0,
                'dislikes' => 0,
                'copies' => 0,
                'new_decks' => 0,
                'score' => 0,
            );
        }

        if ($term_id > 0) {
            $buckets[$bucket_key]['term_id'] = $term_id;
        }

        $buckets[$bucket_key]['decks']++;
        $buckets[$bucket_key]['games'] += $deck['games'];
        $buckets[$bucket_key]['wins'] += $deck['wins'];
        $buckets[$bucket_key]['losses'] += $deck['losses'];
        $buckets[$bucket_key]['likes'] += $deck['likes'];
        $buckets[$bucket_key]['dislikes'] += $deck['dislikes'];
        $buckets[$bucket_key]['copies'] += $deck['copies'];
        if (!empty($deck['is_new'])) {
            $buckets[$bucket_key]['new_decks']++;
        }
        $buckets[$bucket_key]['score'] += $deck['score'];
    }

    private function sort_stats_buckets(array $buckets, $limit) {
        $items = array_values($buckets);
        usort($items, array($this, 'sort_by_score_desc'));
        return array_slice($items, 0, $limit);
    }

    private function sort_by_score_desc($a, $b) {
        if ($a['score'] === $b['score']) {
            return $b['games'] <=> $a['games'];
        }
        return $b['score'] <=> $a['score'];
    }

    private function deck_popularity_score($games, $likes, $dislikes, $copies) {
        return round(($likes * 3) + ($copies * 2) + ($games * 0.1) - ($dislikes * 2), 1);
    }

    private function stats_sort_score($sort, array $metrics) {
        switch ($sort) {
            case 'copies':
                return (float) $metrics['copies'];
            case 'views':
                return (float) $metrics['views'];
            case 'games':
                return (float) $metrics['games'];
            case 'winrate':
                return (float) $metrics['winrate'];
            case 'likes':
                return (float) $metrics['likes'];
            case 'newest':
                return (float) $metrics['published_ts'];
            case 'interest':
            default:
                return (float) $metrics['interest'];
        }
    }

    private function get_term_names_for_deck($post_id, $taxonomy) {
        $terms = get_the_terms($post_id, $taxonomy);
        if (!$terms || is_wp_error($terms)) {
            return array();
        }
        return array_values(array_map(static function($term) {
            return $term->name;
        }, $terms));
    }

    private function get_terms_for_deck($post_id, $taxonomy) {
        $terms = get_the_terms($post_id, $taxonomy);
        if (!$terms || is_wp_error($terms)) {
            return array();
        }
        return array_values(array_map(static function($term) {
            return array(
                'id' => absint($term->term_id),
                'name' => $term->name,
            );
        }, $terms));
    }

    private function get_deck_visibility_label($post_id) {
        if (get_post_meta($post_id, '_hs_deck_archived', true) === '1') {
            return 'Архив';
        }
        if (get_post_meta($post_id, '_hide_from_feed', true) === '1') {
            return 'Скрыта';
        }
        return 'В ленте';
    }

    private function deck_matches_stats_filters($post_id, $games, $winrate, array $filters, $is_new_in_period = false) {
        if (!empty($filters['games_min']) && $games < absint($filters['games_min'])) {
            return false;
        }
        if (!empty($filters['winrate_min']) && (float) $winrate < (float) $filters['winrate_min']) {
            return false;
        }
        if (!empty($filters['created']) && $filters['created'] === 'period' && !$is_new_in_period) {
            return false;
        }

        foreach (array(
            'class' => 'deck_class',
            'mode' => 'deck_mode',
            'archetype' => 'deck_archetype',
            'streamer' => 'deck_streamer',
            'source' => 'deck_source',
        ) as $filter_key => $taxonomy) {
            if (empty($filters[$filter_key])) {
                continue;
            }
            if (!has_term($filters[$filter_key], $taxonomy, $post_id)) {
                return false;
            }
        }

        return true;
    }

    private function float_meta($post_id, $meta_key) {
        $value = str_replace(',', '.', (string) get_post_meta($post_id, $meta_key, true));
        return is_numeric($value) ? (float) $value : 0;
    }

    private function render_engagement_charts(array $data) {
        $daily = isset($data['daily_events']) && is_array($data['daily_events']) ? $data['daily_events'] : array();
        $days = array();
        foreach ($daily as $row) {
            $day = isset($row['event_day']) ? $row['event_day'] : '';
            $type = isset($row['event_type']) ? $row['event_type'] : '';
            if ($day === '' || $type === '') {
                continue;
            }
            if (!isset($days[$day])) {
                $days[$day] = array('deck_view' => 0, 'deck_copy' => 0, 'like' => 0);
            }
            if (isset($days[$day][$type])) {
                $days[$day][$type] += absint($row['events']);
            }
        }

        $max = 0;
        foreach ($days as $counts) {
            $max = max($max, $counts['deck_view'], $counts['deck_copy'], $counts['like']);
        }

        $engagement = isset($data['engagement']) ? $data['engagement'] : array();
        $views = absint(isset($engagement['deck_view']) ? $engagement['deck_view'] : 0);
        $copies = absint(isset($engagement['deck_copy']) ? $engagement['deck_copy'] : 0);
        $likes = absint(isset($engagement['like']) ? $engagement['like'] : 0);
        ?>
        <div class="unified-hs-analytics-grid unified-hs-analytics-grid--engagement">
            <section class="unified-hs-rank-panel">
                <h2>Тренд интереса</h2>
                <?php if (empty($days)): ?>
                    <p class="unified-hs-muted">События появятся после просмотров, копирований и лайков на фронте.</p>
                <?php else: ?>
                    <div class="unified-hs-mini-chart" data-chart="engagement">
                        <?php foreach ($days as $day => $counts): ?>
                            <?php $height = $max > 0 ? max(6, min(100, round(($counts['deck_copy'] / $max) * 100))) : 6; ?>
                            <div class="unified-hs-mini-chart__bar" title="<?php echo esc_attr($day . ': ' . $counts['deck_copy'] . ' копий'); ?>">
                                <span style="height: <?php echo esc_attr($height); ?>%;"></span>
                                <em><?php echo esc_html(date_i18n('d.m', strtotime($day))); ?></em>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="unified-hs-muted">Столбцы показывают копирования кода по дням. Популярность в админке считается только по нажатиям на кнопку копирования.</p>
                <?php endif; ?>
            </section>

            <section class="unified-hs-rank-panel">
                <h2>Воронка выбора обзора</h2>
                <div class="unified-hs-funnel">
                    <?php
                    $funnel = array(
                        'Просмотры' => $views,
                        'Копирования' => $copies,
                        'Лайки' => $likes,
                    );
                    $funnel_max = max(1, max($funnel));
                    foreach ($funnel as $label => $value):
                        $width = max(4, min(100, round(($value / $funnel_max) * 100)));
                        ?>
                        <div class="unified-hs-funnel__row">
                            <strong><?php echo esc_html($label); ?></strong>
                            <span><i style="width: <?php echo esc_attr($width); ?>%;"></i></span>
                            <em><?php echo esc_html(number_format_i18n($value)); ?></em>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
        <?php
    }

    private function render_stats_rank_panel($title, array $items, $empty_text, $link_items = false, array $period = array()) {
        $max = 0;
        foreach ($items as $item) {
            $max = max($max, (float) $item['score']);
        }
        ?>
        <section class="unified-hs-rank-panel">
            <h2><?php echo esc_html($title); ?></h2>
            <?php if (empty($items)): ?>
                <p class="unified-hs-muted"><?php echo esc_html($empty_text); ?></p>
            <?php else: ?>
                <div class="unified-hs-rank-list">
                    <?php foreach ($items as $item): ?>
                        <?php
                        $winrate = $item['games'] > 0 ? round(($item['wins'] / $item['games']) * 100, 1) : 0;
                        $width = $max > 0 ? max(4, min(100, round(((float) $item['score'] / $max) * 100))) : 4;
                        $label_html = esc_html($item['label']);
                        if ($link_items && !empty($item['term_id']) && !empty($period['from']) && !empty($period['to'])) {
                            $trend_payload = $this->get_archetype_trend_payload(absint($item['term_id']), $period);
                            $label_html = '<button type="button" class="unified-hs-rank-link unified-hs-trend-trigger" data-trend="' . esc_attr(wp_json_encode($trend_payload)) . '">' . esc_html($item['label']) . '</button>';
                        }
                        ?>
                        <div class="unified-hs-rank-row">
                            <div class="unified-hs-rank-main">
                                <strong><?php echo $label_html; ?></strong>
                                <span><?php echo esc_html(number_format_i18n($item['copies'])); ?> копий · <?php echo esc_html(number_format_i18n($item['decks'])); ?> колод</span>
                            </div>
                            <div class="unified-hs-rank-bar" aria-hidden="true">
                                <span style="width: <?php echo esc_attr($width); ?>%;"></span>
                            </div>
                            <div class="unified-hs-rank-meta">
                                <span><?php echo esc_html(number_format_i18n($item['decks'])); ?> колод</span>
                                <?php if (!empty($item['new_decks'])): ?>
                                    <span><?php echo esc_html(number_format_i18n($item['new_decks'])); ?> новых</span>
                                <?php endif; ?>
                                <span><?php echo esc_html(number_format_i18n($item['games'])); ?> игр</span>
                                <span><?php echo esc_html(number_format_i18n($winrate, 1)); ?>% WR</span>
                                <span><?php echo esc_html(number_format_i18n($item['copies'])); ?> копий</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
    }

    private function render_archetype_trend_modal() {
        ?>
        <div class="unified-hs-trend-modal" id="unified-hs-archetype-trend-modal" aria-hidden="true">
            <div class="unified-hs-trend-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="unified-hs-trend-modal-title">
                <button type="button" class="unified-hs-trend-modal__close" data-trend-close aria-label="Close">&times;</button>
                <div class="unified-hs-trend-header">
                    <div>
                        <h2 id="unified-hs-trend-modal-title" data-trend-title></h2>
                        <p class="unified-hs-muted" data-trend-description></p>
                    </div>
                    <div class="unified-hs-trend-summary">
                        <span data-trend-period-total></span>
                        <span data-trend-current-total></span>
                    </div>
                </div>
                <p class="unified-hs-muted unified-hs-trend-empty" data-trend-empty hidden></p>
                <div class="unified-hs-trend-chart" data-trend-chart aria-label="Trend chart"></div>
            </div>
        </div>
        <?php
    }

    private function get_archetype_trend_payload($term_id, array $period) {
        $term_id = absint($term_id);
        if (!$term_id || !taxonomy_exists('deck_archetype')) {
            return array();
        }

        $term = get_term($term_id, 'deck_archetype');
        if (!$term || is_wp_error($term)) {
            return array();
        }

        $rows = class_exists('Unified_HS_Deck_Events')
            ? Unified_HS_Deck_Events::daily_counts_for_archetype($term_id, $period['from'], $period['to'], Unified_HS_Deck_Events::EVENT_DECK_COPY)
            : (class_exists('Unified_HS_Deck_Copy_Events')
            ? Unified_HS_Deck_Copy_Events::daily_counts_for_archetype($term_id, $period['from'], $period['to'])
            : array());
        $indexed = array();
        foreach ($rows as $row) {
            $indexed[$row['event_day']] = isset($row['events']) ? absint($row['events']) : absint($row['copies']);
        }

        $days = $this->date_counts_for_period($period['from'], $period['to'], $indexed);
        $total = 0;
        foreach ($days as $day) {
            $total += absint($day['copies']);
        }

        return array(
            'termId' => $term_id,
            'title' => $term->name,
            'from' => isset($period['from']) ? $period['from'] : '',
            'to' => isset($period['to']) ? $period['to'] : '',
            'total' => $total,
            'currentTotal' => $this->current_archetype_copy_total($term_id),
            'days' => $days,
        );
    }

    private function render_archetype_trend($term_id, array $period) {
        $term_id = absint($term_id);
        if (!$term_id || !taxonomy_exists('deck_archetype')) {
            return;
        }

        $term = get_term($term_id, 'deck_archetype');
        if (!$term || is_wp_error($term)) {
            return;
        }

        $rows = class_exists('Unified_HS_Deck_Events')
            ? Unified_HS_Deck_Events::daily_counts_for_archetype($term_id, $period['from'], $period['to'], Unified_HS_Deck_Events::EVENT_DECK_COPY)
            : (class_exists('Unified_HS_Deck_Copy_Events')
            ? Unified_HS_Deck_Copy_Events::daily_counts_for_archetype($term_id, $period['from'], $period['to'])
            : array());
        $indexed = array();
        foreach ($rows as $row) {
            $indexed[$row['event_day']] = isset($row['events']) ? absint($row['events']) : absint($row['copies']);
        }

        $days = $this->date_counts_for_period($period['from'], $period['to'], $indexed);
        $max = 0;
        $total = 0;
        foreach ($days as $day) {
            $max = max($max, $day['copies']);
            $total += $day['copies'];
        }
        $current_total = $this->current_archetype_copy_total($term_id);
        ?>
        <section class="unified-hs-trend-panel">
            <div class="unified-hs-trend-header">
                <div>
                    <h2>Тренд архетипа: <?php echo esc_html($term->name); ?></h2>
                    <p class="unified-hs-muted">Копирования кода по дням за выбранный период.</p>
                </div>
                <div class="unified-hs-trend-summary">
                    <span><?php echo esc_html(number_format_i18n($total)); ?> за период</span>
                    <span><?php echo esc_html(number_format_i18n($current_total)); ?> всего в карточках</span>
                </div>
            </div>

            <?php if ($total <= 0): ?>
                <p class="unified-hs-muted">По этому архетипу пока нет дневных событий копирования. Новые копирования начнут попадать в тренд автоматически.</p>
            <?php endif; ?>

            <div class="unified-hs-trend-chart" aria-label="Тренд копирований">
                <?php foreach ($days as $day): ?>
                    <?php
                    $height = $max > 0 ? max(6, min(100, round(($day['copies'] / $max) * 100))) : 6;
                    $label = $day['day'] . ': ' . number_format_i18n($day['copies']);
                    ?>
                    <div class="unified-hs-trend-day" title="<?php echo esc_attr($label); ?>">
                        <span style="height: <?php echo esc_attr($height); ?>%;"></span>
                        <em><?php echo esc_html($day['copies']); ?></em>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }

    private function date_counts_for_period($from, $to, array $counts) {
        $start = DateTime::createFromFormat('Y-m-d', (string) $from);
        $end = DateTime::createFromFormat('Y-m-d', (string) $to);
        if (!$start || !$end || $start > $end) {
            return array();
        }
        $start->setTime(0, 0, 0);
        $end->setTime(0, 0, 0);

        $days = array();
        while ($start <= $end) {
            $day = $start->format('Y-m-d');
            $days[] = array(
                'day' => $day,
                'copies' => isset($counts[$day]) ? absint($counts[$day]) : 0,
            );
            $start->modify('+1 day');
        }

        return $days;
    }

    private function current_archetype_copy_total($term_id) {
        global $wpdb;

        $term_id = absint($term_id);
        if (!$term_id) {
            return 0;
        }

        $sql = $wpdb->prepare(
            "SELECT COALESCE(SUM(CAST(pm.meta_value AS UNSIGNED)), 0)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
             INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
             LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
             WHERE p.post_type = %s
               AND p.post_status NOT IN ('trash', 'auto-draft')
               AND tt.taxonomy = %s
               AND tt.term_id = %d",
            '_deck_copies',
            'hs_deck',
            'deck_archetype',
            $term_id
        );

        return absint($wpdb->get_var($sql));
    }

    private function render_popular_decks_table(array $items) {
        ?>
        <section class="unified-hs-table-panel">
            <h2>Популярные колоды по выбранной сортировке</h2>
            <table class="widefat striped unified-hs-stats-table">
                <thead>
                    <tr>
                        <th>Колода</th>
                        <th>Класс / режим</th>
                        <th>Архетипы</th>
                        <th>Игры</th>
                        <th>WR</th>
                        <th>Лайки</th>
                        <th>Дизлайки</th>
                        <th>Копии</th>
                        <th>Просмотры</th>
                        <th>Открытия</th>
                        <th>Популярность</th>
                        <th>Обновлено</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="12">Пока нет статистики для отображения.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <?php $edit_link = get_edit_post_link($item['id']); ?>
                        <tr>
                            <td>
                                <?php if ($edit_link): ?>
                                    <a href="<?php echo esc_url($edit_link); ?>"><strong><?php echo esc_html($item['title']); ?></strong></a>
                                <?php else: ?>
                                    <strong><?php echo esc_html($item['title']); ?></strong>
                                <?php endif; ?>
                                <br><span class="unified-hs-status-pill"><?php echo esc_html($item['status']); ?></span>
                                <?php if (!empty($item['is_new'])): ?>
                                    <span class="unified-hs-status-pill unified-hs-status-pill--fresh">Новая</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(implode(', ', $item['classes']) ?: 'Без класса'); ?><br><span class="unified-hs-muted"><?php echo esc_html(implode(', ', $item['modes']) ?: 'Без режима'); ?></span></td>
                            <td>
                                <?php
                                $labels = !empty($item['archetypes']) ? $item['archetypes'] : $item['tags'];
                                echo esc_html(implode(', ', array_slice($labels, 0, 4)) ?: 'Без архетипа');
                                ?>
                            </td>
                            <td><?php echo esc_html(number_format_i18n($item['games'])); ?></td>
                            <td><?php echo esc_html(number_format_i18n($item['winrate'], 1)); ?>%</td>
                            <td><?php echo esc_html(number_format_i18n($item['likes'])); ?></td>
                            <td><?php echo esc_html(number_format_i18n($item['dislikes'])); ?></td>
                            <td><?php echo esc_html(number_format_i18n($item['copies'])); ?></td>
                            <td><?php echo esc_html(number_format_i18n(isset($item['views']) ? $item['views'] : 0)); ?></td>
                            <td><?php echo esc_html(number_format_i18n((isset($item['image_opens']) ? $item['image_opens'] : 0) + (isset($item['proof_opens']) ? $item['proof_opens'] : 0))); ?></td>
                            <td><strong><?php echo esc_html(number_format_i18n($item['interest_score'], 1)); ?></strong></td>
                            <td><?php echo esc_html($item['modified']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
        <?php
    }

    public function render_stats_import_page() {
        if (!current_user_can(Unified_HS_Capabilities::CAP_IMPORT_DECKS)) {
            wp_die(esc_html__('Недостаточно прав', 'unified-hs-plugins'));
        }

        $result = null;
        if (isset($_POST['unified_hs_stats_import']) && check_admin_referer('unified_hs_stats_import_action')) {
            $result = $this->handle_stats_import_upload();
        }

        $csv_template_url = wp_nonce_url(
            add_query_arg(array(
                'action' => 'unified_hs_stats_template',
                'format' => 'csv',
            ), admin_url('admin-post.php')),
            'unified_hs_stats_template'
        );
        $json_template_url = wp_nonce_url(
            add_query_arg(array(
                'action' => 'unified_hs_stats_template',
                'format' => 'json',
            ), admin_url('admin-post.php')),
            'unified_hs_stats_template'
        );
        ?>
        <div class="wrap unified-hs-settings unified-hs-stats-import">
            <h1>Импорт статистики</h1>
            <p class="subtitle">Загрузите CSV или JSON со статистикой за день, неделю, месяц или любой другой период.</p>

            <?php if (is_array($result)): ?>
                <div class="notice notice-<?php echo empty($result['errors']) ? 'success' : 'warning'; ?> is-dismissible">
                    <p>
                        <?php
                        printf(
                            esc_html__('Импортировано строк: %1$d. Пропущено: %2$d.', 'unified-hs-plugins'),
                            absint($result['imported']),
                            absint($result['skipped'])
                        );
                        ?>
                    </p>
                    <?php if (!empty($result['errors'])): ?>
                        <ul class="unified-hs-import-errors">
                            <?php foreach (array_slice($result['errors'], 0, 20) as $error): ?>
                                <li><?php echo esc_html($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="unified-hs-settings-grid unified-hs-settings-grid--import">
                <div class="unified-hs-settings-section">
                    <h2>Загрузка файла</h2>
                    <form method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field('unified_hs_stats_import_action'); ?>
                        <input type="hidden" name="unified_hs_stats_import" value="1">

                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="stats_file">Файл статистики</label></th>
                                <td>
                                    <input type="file" id="stats_file" name="stats_file" accept=".csv,.json,application/json,text/csv" required>
                                    <p class="description">Поддерживаются CSV и JSON. Максимум 5 MB за один импорт.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="period_start">Период по умолчанию</label></th>
                                <td>
                                    <input type="date" id="period_start" name="period_start">
                                    <span class="unified-hs-date-separator">—</span>
                                    <input type="date" id="period_end" name="period_end">
                                    <p class="description">Если в файле нет колонок period_start/period_end или period, будет использован этот диапазон.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Карточки колод</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="apply_to_cards" value="1" checked>
                                        Пересчитать суммарные games/winrate для карточек после импорта
                                    </label>
                                </td>
                            </tr>
                        </table>

                        <?php submit_button('Импортировать статистику', 'primary', 'submit', false); ?>
                    </form>
                </div>

                <div class="unified-hs-settings-section">
                    <h2>Формат данных</h2>
                    <p>Минимально нужен ID колоды и статистика. Период можно указать в файле или в форме слева.</p>
                    <div class="unified-hs-actions-inline">
                        <a class="button" href="<?php echo esc_url($csv_template_url); ?>">Скачать CSV-шаблон</a>
                        <a class="button" href="<?php echo esc_url($json_template_url); ?>">Скачать JSON-шаблон</a>
                    </div>
                    <table class="widefat striped unified-hs-format-table">
                        <thead>
                            <tr>
                                <th>Поле</th>
                                <th>Пример</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td><code>deck_id</code></td><td><code>123</code></td></tr>
                            <tr><td><code>period_start</code></td><td><code>2026-05-01</code></td></tr>
                            <tr><td><code>period_end</code></td><td><code>2026-05-22</code></td></tr>
                            <tr><td><code>games</code></td><td><code>80</code></td></tr>
                            <tr><td><code>wins</code></td><td><code>48</code></td></tr>
                            <tr><td><code>losses</code></td><td><code>32</code></td></tr>
                            <tr><td><code>winrate</code></td><td><code>60</code></td></tr>
                            <tr><td><code>source</code></td><td><code>weekly-report</code></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    public function download_stats_template() {
        if (!current_user_can(Unified_HS_Capabilities::CAP_IMPORT_DECKS)) {
            wp_die(esc_html__('Недостаточно прав', 'unified-hs-plugins'));
        }

        check_admin_referer('unified_hs_stats_template');

        $format = isset($_GET['format']) ? sanitize_key(wp_unslash($_GET['format'])) : 'csv';
        $sample = array(
            array(
                'deck_id' => 123,
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-22',
                'games' => 80,
                'wins' => 48,
                'losses' => 32,
                'winrate' => 60,
                'source' => 'weekly-report',
            ),
        );

        if ($format === 'json') {
            nocache_headers();
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="manacost-deck-stats-template.json"');
            echo wp_json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="manacost-deck-stats-template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array_keys($sample[0]));
        foreach ($sample as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    private function handle_stats_import_upload() {
        if (empty($_FILES['stats_file']) || !isset($_FILES['stats_file']['tmp_name'])) {
            return array(
                'imported' => 0,
                'skipped' => 0,
                'errors' => array('Файл не выбран.'),
            );
        }

        $file = $_FILES['stats_file'];
        if (!empty($file['error'])) {
            return array(
                'imported' => 0,
                'skipped' => 0,
                'errors' => array('Ошибка загрузки файла: ' . absint($file['error'])),
            );
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            return array(
                'imported' => 0,
                'skipped' => 0,
                'errors' => array('WordPress не подтвердил загруженный файл.'),
            );
        }

        if (!empty($file['size']) && (int) $file['size'] > 5 * 1024 * 1024) {
            return array(
                'imported' => 0,
                'skipped' => 0,
                'errors' => array('Файл больше 5 MB. Разбейте импорт на несколько частей.'),
            );
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, array('csv', 'json'), true)) {
            return array(
                'imported' => 0,
                'skipped' => 0,
                'errors' => array('Поддерживаются только CSV и JSON.'),
            );
        }

        if (!class_exists('Unified_HS_Deck_Period_Stats')) {
            return array(
                'imported' => 0,
                'skipped' => 0,
                'errors' => array('Модуль статистики по периодам не загружен.'),
            );
        }

        $rows = $extension === 'json'
            ? $this->parse_stats_json_file($file['tmp_name'])
            : $this->parse_stats_csv_file($file['tmp_name']);

        if (is_wp_error($rows)) {
            return array(
                'imported' => 0,
                'skipped' => 0,
                'errors' => array($rows->get_error_message()),
            );
        }

        $defaults = array(
            'period_start' => isset($_POST['period_start']) ? sanitize_text_field(wp_unslash($_POST['period_start'])) : '',
            'period_end' => isset($_POST['period_end']) ? sanitize_text_field(wp_unslash($_POST['period_end'])) : '',
        );
        $apply_to_cards = !empty($_POST['apply_to_cards']);

        return Unified_HS_Deck_Period_Stats::import_rows($rows, $defaults, $apply_to_cards);
    }

    private function parse_stats_json_file($path) {
        $raw = file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            return new WP_Error('empty_json', 'JSON-файл пустой.');
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return new WP_Error('invalid_json', 'JSON не удалось прочитать.');
        }

        if (isset($data['rows']) && is_array($data['rows'])) {
            return $data['rows'];
        }
        if (isset($data['stats']) && is_array($data['stats'])) {
            return $data['stats'];
        }
        if (isset($data['deck_id']) || isset($data['post_id'])) {
            return array($data);
        }

        return $data;
    }

    private function parse_stats_csv_file($path) {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return new WP_Error('invalid_csv', 'CSV-файл не удалось открыть.');
        }

        $first_line = fgets($handle);
        if (!is_string($first_line)) {
            fclose($handle);
            return new WP_Error('empty_csv', 'CSV-файл пустой.');
        }
        $delimiter = substr_count($first_line, ';') > substr_count($first_line, ',') ? ';' : ',';
        rewind($handle);

        $headers = fgetcsv($handle, 0, $delimiter);
        if (!is_array($headers) || empty($headers)) {
            fclose($handle);
            return new WP_Error('invalid_csv_header', 'CSV должен начинаться со строки заголовков.');
        }

        $headers = array_map(array($this, 'normalize_csv_header'), $headers);
        $rows = array();
        while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (!is_array($values) || count(array_filter($values, 'strlen')) === 0) {
                continue;
            }

            $row = array();
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $row[$header] = isset($values[$index]) ? trim((string) $values[$index]) : '';
            }
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    private function normalize_csv_header($header) {
        $header = preg_replace("/^\xEF\xBB\xBF/", '', (string) $header);
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9_]+/', '_', $header);
        return trim($header, '_');
    }

    /**
     * Рендеринг истории редакционных действий с колодами.
     */
    public function render_activity_page() {
        if (!current_user_can(Unified_HS_Capabilities::CAP_VIEW_ACTIVITY_LOG)) {
            wp_die(esc_html__('Недостаточно прав', 'unified-hs-plugins'));
        }

        if (isset($_POST['clear_activity']) && check_admin_referer('unified_hs_activity_action')) {
            if (!current_user_can(Unified_HS_Capabilities::CAP_CLEAR_ACTIVITY_LOG)) {
                wp_die(esc_html__('Недостаточно прав', 'unified-hs-plugins'));
            }
            if (class_exists('Unified_HS_Deck_Activity_Log')) {
                Unified_HS_Deck_Activity_Log::clear();
            }
            echo '<div class="notice notice-success is-dismissible"><p>История действий очищена.</p></div>';
        }

        $rows = class_exists('Unified_HS_Deck_Activity_Log')
            ? Unified_HS_Deck_Activity_Log::recent(300)
            : array();
        ?>
        <div class="wrap unified-hs-activity">
            <h1>История действий с колодами</h1>
            <?php if (current_user_can(Unified_HS_Capabilities::CAP_CLEAR_ACTIVITY_LOG)): ?>
            <form method="post">
                <?php wp_nonce_field('unified_hs_activity_action'); ?>
                <input type="hidden" name="clear_activity" value="1">
                <button type="submit" class="button">Очистить историю</button>
            </form>
            <?php endif; ?>

            <table class="widefat striped" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th>Время</th>
                        <th>Пользователь</th>
                        <th>Действие</th>
                        <th>Колода</th>
                        <th>Было</th>
                        <th>Стало</th>
                        <th>Контекст</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $user_id = isset($row['user_id']) ? absint($row['user_id']) : 0;
                        $user = $user_id ? get_userdata($user_id) : null;
                        $user_label = $user ? $user->display_name : ($user_id ? '#' . $user_id : 'Система');
                        $post_id = isset($row['post_id']) ? absint($row['post_id']) : 0;
                        $deck_title = isset($row['deck_title']) ? $row['deck_title'] : '';
                        $deck_link = $post_id && get_post($post_id)
                            ? '<a href="' . esc_url(get_edit_post_link($post_id)) . '">' . esc_html($deck_title) . '</a>'
                            : esc_html($deck_title);
                        ?>
                        <tr>
                            <td><?php echo esc_html(isset($row['created_at']) ? $row['created_at'] : ''); ?></td>
                            <td><?php echo esc_html($user_label); ?></td>
                            <td>
                                <strong><?php echo esc_html(Unified_HS_Deck_Activity_Log::action_label(isset($row['action']) ? $row['action'] : '')); ?></strong>
                                <?php if (!empty($row['message'])): ?>
                                    <br><small><?php echo esc_html($row['message']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $deck_link; ?></td>
                            <td><code><?php echo esc_html(isset($row['old_value']) ? wp_trim_words($row['old_value'], 18, '...') : ''); ?></code></td>
                            <td><code><?php echo esc_html(isset($row['new_value']) ? wp_trim_words($row['new_value'], 18, '...') : ''); ?></code></td>
                            <td><?php echo esc_html(isset($row['context']) ? $row['context'] : ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">История пока пустая.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Обработка действий настроек
     */
    private function handle_settings_action() {
        if (!current_user_can(Unified_HS_Capabilities::CAP_MANAGE_TERMS)) {
            wp_die(esc_html__('Недостаточно прав', 'unified-hs-plugins'));
        }

        $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';
        
        switch ($action) {
            case 'add_class':
                $class_name = isset($_POST['class_name']) ? sanitize_text_field($_POST['class_name']) : '';
                if (!empty($class_name)) {
                    $result = wp_insert_term($class_name, 'deck_class');
                    if (!is_wp_error($result)) {
                        add_settings_error('unified_hs_settings', 'class_added', 'Класс успешно добавлен', 'updated');
                    } else {
                        add_settings_error('unified_hs_settings', 'class_error', 'Ошибка: ' . $result->get_error_message(), 'error');
                    }
                }
                break;
                
            case 'delete_class':
                $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
                if ($term_id > 0) {
                    $result = wp_delete_term($term_id, 'deck_class');
                    if (!is_wp_error($result) && $result) {
                        add_settings_error('unified_hs_settings', 'class_deleted', 'Класс удален', 'updated');
                    }
                }
                break;
                
            case 'add_mode':
                $mode_name = isset($_POST['mode_name']) ? sanitize_text_field($_POST['mode_name']) : '';
                if (!empty($mode_name)) {
                    $result = wp_insert_term($mode_name, 'deck_mode');
                    if (!is_wp_error($result)) {
                        add_settings_error('unified_hs_settings', 'mode_added', 'Режим успешно добавлен', 'updated');
                    } else {
                        add_settings_error('unified_hs_settings', 'mode_error', 'Ошибка: ' . $result->get_error_message(), 'error');
                    }
                }
                break;
                
            case 'delete_mode':
                $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
                if ($term_id > 0) {
                    $result = wp_delete_term($term_id, 'deck_mode');
                    if (!is_wp_error($result) && $result) {
                        add_settings_error('unified_hs_settings', 'mode_deleted', 'Режим удален', 'updated');
                    }
                }
                break;
        }
        
        settings_errors('unified_hs_settings');
    }
    
    /**
     * Рендеринг галереи шорткодов
     */
    public function render_shortcodes_gallery() {
        if (!current_user_can(Unified_HS_Capabilities::CAP_VIEW_SHORTCODES)) {
            wp_die(esc_html__('Недостаточно прав', 'unified-hs-plugins'));
        }

        $shortcodes = array(
            'Колоды HS' => array(
                array(
                    'name' => 'Список колод',
                    'code' => '[hs_decks]',
                    'desc' => 'Отображает список всех колод с фильтрами по классу, режиму и тегам.',
                    'example' => '[hs_decks]'
                ),
                array(
                    'name' => 'Список колод с параметрами',
                    'code' => '[hs_decks per_page="12" class="druid"]',
                    'desc' => 'per_page - количество на странице, class - класс (slug)',
                    'example' => '[hs_decks per_page="20" class="mage"]'
                ),
                array(
                    'name' => 'Одна колода',
                    'code' => '[hs_deck id="123"]',
                    'desc' => 'Отображает конкретную колоду по ID.',
                    'example' => '[hs_deck id="123"]'
                ),
                array(
                    'name' => 'Группа колод',
                    'code' => '[hs_deck_group ids="123,456,789"]',
                    'desc' => 'Отображает несколько колод по их ID.',
                    'example' => '[hs_deck_group ids="123,456,789"]'
                ),
                array(
                    'name' => 'Случайные колоды',
                    'code' => '[hs_decks_random random_row="5"]',
                    'desc' => 'Отображает указанное количество случайных колод.',
                    'example' => '[hs_decks_random random_row="10"]'
                ),
            ),
        );
        
        ?>
        <div class="wrap">
            <h1>Галерея шорткодов</h1>
            <p>Все доступные шорткоды. Нажмите на код для копирования.</p>
            
            <div id="shortcodes-container">
                <?php foreach ($shortcodes as $category => $items): ?>
                <div class="shortcode-section">
                    <h2><?php echo esc_html($category); ?></h2>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Название</th>
                                <th>Шорткод</th>
                                <th>Описание</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><strong><?php echo esc_html($item['name']); ?></strong></td>
                                <td><code onclick="navigator.clipboard.writeText('<?php echo esc_js($item['code']); ?>'); alert('Скопировано!');" style="cursor:pointer;"><?php echo esc_html($item['code']); ?></code></td>
                                <td><?php echo esc_html($item['desc']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div style="background: white; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px;">
                <h2>Быстрый доступ</h2>
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=hs_deck')); ?>" class="button">Колоды HS</a>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=hs_deck')); ?>" class="button button-primary">Добавить колоду</a>
            </div>
        </div>
        <?php
    }
}
