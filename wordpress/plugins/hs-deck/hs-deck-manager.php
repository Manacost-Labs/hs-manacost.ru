<?php
/**
 * Plugin Name: HS Deck Manager
 * Description: Manage and display Hearthstone decks via shortcode.
 * Version: 2.0.0
 * Author: Zulut
 * Text Domain: hs_deck_manager
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin Class
 * Implements Singleton pattern and OOP structure for better performance and organization.
 */
class hs_deck_manager_Plugin {

    private static $instance = null;
    private $plugin_name;
    private $version;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->plugin_name = 'hs_deck_manager';
        $this->version = '2.0.0';

        // Initialization
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_action( 'init', array( $this, 'register_deck_meta' ), 6 );
        add_action( 'init', array( $this, 'maybe_backfill_show_in_feed_meta' ), 7 );
        add_action( 'init', array( $this, 'register_shortcodes' ) );
        
        // Scripts (frontend feed: register once, enqueue when shortcode is used)
        add_action( 'wp_enqueue_scripts', array( $this, 'register_feed_script' ), 5 );

        // Admin
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_post_meta' ) );

        // AJAX
        add_action( 'wp_ajax_hs_deck_manager_track', array( $this, 'track_event' ) );
        add_action( 'wp_ajax_nopriv_hs_deck_manager_track', array( $this, 'track_event' ) );
        // Migration: old plugin used copy_deck_code
        add_action( 'wp_ajax_copy_deck_code', array( $this, 'track_copy_legacy' ) );
        add_action( 'wp_ajax_nopriv_copy_deck_code', array( $this, 'track_copy_legacy' ) );

        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
        add_action( 'trashed_post', array( $this, 'invalidate_stats_transient' ) );
    }

    /** Invalidate global stats cache when a deck is trashed */
    public function invalidate_stats_transient( $post_id ) {
        if ( get_post_type( $post_id ) === 'hs_deck' ) {
            delete_transient( 'hs_deck_manager_global_stats' );
        }
    }

    /** Query vars for decks pagination and sort */
    public function add_query_vars( $vars ) {
        $vars[] = 'hs_page';
        $vars[] = 'hs_order';
        return $vars;
    }

    /**
     * Register CPT and Taxonomies
     */
    public function register_cpt() {
        $labels = array(
            'name'               => _x( 'Колоды', 'Post Type General Name', 'hs_deck_manager' ),
            'singular_name'      => _x( 'Колода', 'Post Type Singular Name', 'hs_deck_manager' ),
            'menu_name'          => __( 'Колоды', 'hs_deck_manager' ),
            'all_items'          => __( 'Все колоды', 'hs_deck_manager' ),
            'add_new_item'       => __( 'Добавить колоду', 'hs_deck_manager' ),
            'add_new'            => __( 'Добавить новую', 'hs_deck_manager' ),
            'new_item'           => __( 'Новая колода', 'hs_deck_manager' ),
            'edit_item'          => __( 'Редактировать колоду', 'hs_deck_manager' ),
            'view_item'          => __( 'Просмотр колоды', 'hs_deck_manager' ),
            'search_items'       => __( 'Поиск колод', 'hs_deck_manager' ),
            'not_found'          => __( 'Колоды не найдены', 'hs_deck_manager' ),
            'not_found_in_trash' => __( 'В корзине колоды не найдены', 'hs_deck_manager' ),
        );

        $args = array(
            'label'               => __( 'Колода', 'hs_deck_manager' ),
            'labels'              => $labels,
            'supports'            => array( 'title', 'thumbnail', 'excerpt', 'custom-fields' ),
            'taxonomies'          => array( 'deck_class', 'deck_mode' ),
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-images-alt2',
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => true,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'show_in_rest'        => true,
            'rewrite'             => array( 'slug' => 'deck', 'with_front' => false, 'feeds' => true, 'pages' => true ),
        );
        register_post_type( 'hs_deck', $args );

        register_taxonomy( 'deck_class', array( 'hs_deck' ), array(
            'hierarchical'      => true,
            'labels'            => array(
                'name'          => _x( 'Классы', 'taxonomy general name', 'hs_deck_manager' ),
                'singular_name' => _x( 'Класс', 'taxonomy singular name', 'hs_deck_manager' ),
                'menu_name'     => __( 'Классы', 'hs_deck_manager' ),
            ),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'deck-class' ),
            'show_in_rest'      => true,
        ) );

        register_taxonomy( 'deck_mode', array( 'hs_deck' ), array(
            'hierarchical'      => true,
            'labels'            => array(
                'name'          => _x( 'Режимы', 'taxonomy general name', 'hs_deck_manager' ),
                'singular_name' => _x( 'Режим', 'taxonomy singular name', 'hs_deck_manager' ),
                'menu_name'     => __( 'Режимы', 'hs_deck_manager' ),
            ),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'deck-mode' ),
            'show_in_rest'      => true,
        ) );
    }
    public function register_deck_meta() {
        $meta = array(
            '_deck_code'        => array( 'type' => 'string', 'sanitize' => 'sanitize_textarea_field', 'default' => '' ),
            '_dust_cost'        => array( 'type' => 'integer', 'sanitize' => 'absint', 'default' => 0 ),
            '_custom_tags'      => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field', 'default' => '' ),
            '_deck_player'      => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field', 'default' => '' ),
            '_deck_streamer'    => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field', 'default' => '' ),
            '_deck_source_url'  => array( 'type' => 'string', 'sanitize' => 'esc_url_raw', 'default' => '' ),
            '_deck_wins'        => array( 'type' => 'integer', 'sanitize' => 'absint', 'default' => 0 ),
            '_deck_losses'      => array( 'type' => 'integer', 'sanitize' => 'absint', 'default' => 0 ),
            '_deck_peak'        => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field', 'default' => '' ),
            '_deck_latest'      => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field', 'default' => '' ),
            '_deck_worst'       => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field', 'default' => '' ),
            '_deck_winrate_val' => array( 'type' => 'number', 'sanitize' => 'floatval', 'default' => 0 ),
            '_deck_views'       => array( 'type' => 'integer', 'sanitize' => 'absint', 'default' => 0 ),
            '_deck_copies'      => array( 'type' => 'integer', 'sanitize' => 'absint', 'default' => 0 ),
            '_deck_clicks'      => array( 'type' => 'integer', 'sanitize' => 'absint', 'default' => 0 ),
            '_show_in_feed'     => array( 'type' => 'integer', 'sanitize' => 'absint', 'default' => 1 ),
            '_hide_from_feed'   => array( 'type' => 'integer', 'sanitize' => 'absint', 'default' => 0 ),
        );
        foreach ( $meta as $key => $opts ) {
            register_post_meta( 'hs_deck', $key, array(
                'type'              => $opts['type'],
                'single'            => true,
                'show_in_rest'      => true,
                'default'           => $opts['default'],
                'auth_callback'     => function( $allowed, $meta_key, $post_id ) {
                    return current_user_can( 'edit_post', $post_id );
                },
                'sanitize_callback' => $opts['sanitize'],
            ) );
        }
    }

    public function maybe_backfill_show_in_feed_meta() {
        if ( get_option( 'hs_deck_manager_backfill_show_in_feed' ) ) {
            return;
        }

        global $wpdb;
        $posts_table = $wpdb->posts;
        $meta_table  = $wpdb->postmeta;

        $wpdb->query(
            "INSERT INTO {$meta_table} (post_id, meta_key, meta_value)
             SELECT p.ID, '_show_in_feed', '1'
             FROM {$posts_table} p
             LEFT JOIN {$meta_table} m
               ON p.ID = m.post_id AND m.meta_key = '_show_in_feed'
             WHERE p.post_type = 'hs_deck' AND m.post_id IS NULL"
        );

        $wpdb->query(
            "UPDATE {$meta_table} m
             INNER JOIN {$meta_table} h
               ON h.post_id = m.post_id
              AND h.meta_key = '_hide_from_feed'
              AND h.meta_value = '1'
             SET m.meta_value = '0'
             WHERE m.meta_key = '_show_in_feed'"
        );

        update_option( 'hs_deck_manager_backfill_show_in_feed', 1 );
    }
    public function register_shortcodes() {
        add_shortcode( 'hs_decks', array( $this, 'feed_shortcode' ) );
        add_shortcode( 'hs_deck', array( $this, 'single_deck_shortcode' ) );
    }

    /**
     * Admin Menu
     */
    public function add_admin_menu() {
        add_submenu_page( 'edit.php?post_type=hs_deck', __( 'Статистика', 'hs_deck_manager' ), __( 'Статистика', 'hs_deck_manager' ), 'manage_options', 'hs_deck_manager_stats', array( $this, 'render_stats_page' ) );
        add_submenu_page( 'edit.php?post_type=hs_deck', __( 'Объявления', 'hs_deck_manager' ), __( 'Объявления', 'hs_deck_manager' ), 'manage_options', 'hs_deck_manager_announcements', array( $this, 'render_announcements_page' ) );
        add_submenu_page( 'edit.php?post_type=hs_deck', __( 'Реклама', 'hs_deck_manager' ), __( 'Реклама', 'hs_deck_manager' ), 'manage_options', 'hs_deck_manager_ads', array( $this, 'render_ads_page' ) );
    }
    public function register_settings() {
        // Ads
        register_setting( 'hs_deck_manager_ads_group', 'hs_deck_manager_ads_config' );
        register_setting( 'hs_deck_manager_ads_group', 'hs_deck_manager_ad_start' );
        register_setting( 'hs_deck_manager_ads_group', 'hs_deck_manager_ad_repeat' );
        register_setting( 'hs_deck_manager_ads_group', 'hs_deck_manager_ad_enabled' );

        // Announcements
        register_setting( 'hs_deck_manager_announcements_group', 'hs_deck_manager_announcement_text' );
        register_setting( 'hs_deck_manager_announcements_group', 'hs_deck_manager_announcement_image' );
        register_setting( 'hs_deck_manager_announcements_group', 'hs_deck_manager_announcement_enabled' );
    }

    /**
     * Register feed script (enqueued only when [hs_decks] shortcode is used).
     */
    public function register_feed_script() {
        $plugin_url  = plugin_dir_url( __FILE__ );
        $plugin_path = plugin_dir_path( __FILE__ );
        $script_path = $plugin_path . 'build/feed.js';
        $asset_path  = $plugin_path . 'build/feed.asset.php';

        if ( ! file_exists( $script_path ) ) {
            return;
        }
        $version = $this->version;
        $deps    = array();
        if ( file_exists( $asset_path ) ) {
            $asset = include $asset_path;
            $version = $asset['version'];
            $deps    = $asset['dependencies'];
        }
        wp_register_script(
            'hs-deck-feed',
            $plugin_url . 'build/feed.js',
            $deps,
            $version,
            true
        );
    }

    /**
     * Enqueue Admin Assets
     * Loads built React bundle when available; falls back to WordPress scripts + inline when not built.
     */
    public function enqueue_admin_assets( $hook ) {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $is_deck_screen = $screen && isset( $screen->post_type ) && $screen->post_type === 'hs_deck';
        $is_stats_page  = ( strpos( $hook, 'hs_deck_manager_stats' ) !== false );
        $is_ads_page    = ( strpos( $hook, 'hs_deck_manager_ads' ) !== false );
        $is_ann_page    = ( strpos( $hook, 'hs_deck_manager_announcements' ) !== false );

        if ( $is_ads_page || $is_ann_page ) {
            wp_enqueue_media();
        }

        if ( ! ( $is_deck_screen || $is_stats_page || $is_ads_page || $is_ann_page ) ) {
            return;
        }

        $plugin_url  = plugin_dir_url( __FILE__ );
        $plugin_path = plugin_dir_path( __FILE__ );

        wp_enqueue_style( 'hs-deck-admin', $plugin_url . 'assets/css/hs-deck-admin.css', array(), $this->version );
        wp_enqueue_script( 'hs-deck-admin', $plugin_url . 'assets/js/hs-deck-admin.js', array(), $this->version, true );
        wp_localize_script( 'hs-deck-admin', 'hsDeckAdminL10n', array(
            'mediaTitle'  => __( 'Выберите изображение', 'hs_deck_manager' ),
            'mediaButton' => __( 'Использовать', 'hs_deck_manager' ),
        ) );

        if ( $is_stats_page ) {
            wp_enqueue_style( 'hs-deck-admin-stats', $plugin_url . 'assets/css/hs-deck-admin-stats.css', array(), $this->version );
            wp_enqueue_script(
                'chart-js',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
                array(),
                '4.4.1',
                false
            );
            wp_enqueue_script( 'hs-deck-admin-stats', $plugin_url . 'assets/js/hs-deck-admin-stats.js', array( 'chart-js' ), $this->version, true );
        }

        if ( ! $is_ads_page ) {
            return;
        }

        $asset_path  = $plugin_path . 'build/ads-manager.asset.php';
        if ( file_exists( $asset_path ) ) {
            $asset = include $asset_path;
            $deps = is_array( $asset['dependencies'] ) ? $asset['dependencies'] : array();
            $deps = array_diff( $deps, array( 'react', 'react-dom' ) );
            if ( ! in_array( 'wp-element', $deps, true ) ) {
                $deps[] = 'wp-element';
            }
            if ( ! in_array( 'wp-components', $deps, true ) ) {
                $deps[] = 'wp-components';
            }
            wp_enqueue_script(
                'hs-deck-ads-manager',
                $plugin_url . 'build/ads-manager.js',
                array_values( $deps ),
                $asset['version'],
                true
            );
            wp_enqueue_style( 'wp-components' );
        }
    }
    public function add_meta_boxes() {
        add_meta_box( 'hs_deck_manager_stats', __( 'Информация о колоде', 'hs_deck_manager' ), array( $this, 'render_meta_box_stats' ), 'hs_deck', 'normal', 'high' );
        add_meta_box( 'hs_deck_manager_shortcode', __( 'Шорткод', 'hs_deck_manager' ), array( $this, 'render_meta_box_shortcode' ), 'hs_deck', 'side', 'high' );
        add_meta_box( 'hs_deck_manager_analytics', __( 'Статистика', 'hs_deck_manager' ), array( $this, 'render_meta_box_analytics' ), 'hs_deck', 'normal', 'default' );
    }
    public function render_meta_box_stats( $post ) {
        wp_nonce_field( 'hs_deck_manager_save_stats', 'hs_deck_manager_stats_nonce' );

        $dust   = get_post_meta( $post->ID, '_dust_cost', true );
        $author = get_post_meta( $post->ID, '_deck_player', true );
        $wins   = (int) get_post_meta( $post->ID, '_deck_wins', true );
        $losses = (int) get_post_meta( $post->ID, '_deck_losses', true );
        $code   = get_post_meta( $post->ID, '_deck_code', true );
        $show_in_feed = get_post_meta( $post->ID, '_show_in_feed', true );
        if ( $show_in_feed === '' ) {
            $show_in_feed = '1';
        }
        $total_games = $wins + $losses;
        ?>
        <div class="hs-meta-grid">
            <p>
                <label for="hs-deck-dust"><strong><?php esc_html_e( 'Стоимость (пыль)', 'hs_deck_manager' ); ?></strong></label>
                <input id="hs-deck-dust" type="number" name="deck_dust" value="<?php echo esc_attr( $dust ); ?>" class="widefat">
            </p>
            <p>
                <label for="hs-deck-author"><strong><?php esc_html_e( 'Автор', 'hs_deck_manager' ); ?></strong></label>
                <input id="hs-deck-author" type="text" name="deck_author" value="<?php echo esc_attr( $author ); ?>" class="widefat">
            </p>
            <p class="hs-meta-full">
                <label for="hs-deck-code"><strong><?php esc_html_e( 'Код колоды', 'hs_deck_manager' ); ?></strong></label>
                <textarea id="hs-deck-code" name="deck_code" class="widefat" rows="3"><?php echo esc_textarea( $code ); ?></textarea>
            </p>
            <p class="hs-meta-full">
                <label>
                    <input type="checkbox" name="deck_show_in_feed" value="1" <?php checked( $show_in_feed, '1' ); ?>>
                    <?php esc_html_e( 'Показывать в ленте', 'hs_deck_manager' ); ?>
                </label>
            </p>
            <div class="hs-meta-full hs-stats-mode">
                <strong><?php esc_html_e( 'Режим ввода статистики:', 'hs_deck_manager' ); ?></strong>
                <label><input type="radio" name="stats_mode" value="standard" checked> <?php esc_html_e( 'Победы / Поражения', 'hs_deck_manager' ); ?></label>
                <label><input type="radio" name="stats_mode" value="total"> <?php esc_html_e( 'Победы / Всего игр', 'hs_deck_manager' ); ?></label>
            </div>
            <div class="hs-stats-fields is-active" id="fields_standard">
                <p>
                    <label for="hs-deck-wins"><strong><?php esc_html_e( 'Победы', 'hs_deck_manager' ); ?></strong></label>
                    <input id="hs-deck-wins" type="number" name="deck_wins" value="<?php echo esc_attr( $wins ); ?>" class="widefat">
                </p>
                <p id="field_losses">
                    <label for="hs-deck-losses"><strong><?php esc_html_e( 'Поражения', 'hs_deck_manager' ); ?></strong></label>
                    <input id="hs-deck-losses" type="number" name="deck_losses" value="<?php echo esc_attr( $losses ); ?>" class="widefat">
                </p>
            </div>
            <div class="hs-stats-fields" id="fields_total">
                <p>
                    <label for="hs-deck-wins-total"><strong><?php esc_html_e( 'Победы', 'hs_deck_manager' ); ?></strong></label>
                    <input id="hs-deck-wins-total" type="number" name="deck_wins_total" value="<?php echo esc_attr( $wins ); ?>" class="widefat">
                </p>
                <p id="field_total">
                    <label for="hs-deck-total"><strong><?php esc_html_e( 'Всего игр', 'hs_deck_manager' ); ?></strong></label>
                    <input id="hs-deck-total" type="number" name="deck_total" value="<?php echo esc_attr( $total_games ); ?>" class="widefat">
                </p>
            </div>
        </div>
        <?php
    }
    public function render_meta_box_shortcode( $post ) {
        $code = '[hs_deck id="' . $post->ID . '"]';
        echo '<input type="text" value="' . esc_attr( $code ) . '" class="widefat hs-shortcode-copy" readonly>';
        echo '<p class="description">' . esc_html__( 'Скопируйте шорткод для вставки.', 'hs_deck_manager' ) . '</p>';
    }
    public function render_meta_box_analytics( $post ) {
        $views  = (int) get_post_meta( $post->ID, '_deck_views', true );
        $copies = (int) get_post_meta( $post->ID, '_deck_copies', true );
        $clicks = (int) get_post_meta( $post->ID, '_deck_clicks', true );
        ?>
        <div class="hs-analytics-grid">
            <div class="hs-analytics-item">
                <div class="hs-analytics-value"><?php echo $views; ?></div>
                <div class="hs-analytics-label"><?php esc_html_e( 'Просмотры', 'hs_deck_manager' ); ?></div>
            </div>
            <div class="hs-analytics-item">
                <div class="hs-analytics-value hs-analytics-primary"><?php echo $copies; ?></div>
                <div class="hs-analytics-label"><?php esc_html_e( 'Копии', 'hs_deck_manager' ); ?></div>
            </div>
            <div class="hs-analytics-item">
                <div class="hs-analytics-value"><?php echo $clicks; ?></div>
                <div class="hs-analytics-label"><?php esc_html_e( 'Клики', 'hs_deck_manager' ); ?></div>
            </div>
        </div>
        <?php
    }
    public function save_post_meta( $post_id ) {
        if ( ! isset( $_POST['hs_deck_manager_stats_nonce'] ) || ! wp_verify_nonce( $_POST['hs_deck_manager_stats_nonce'], 'hs_deck_manager_save_stats' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['deck_dust'] ) ) {
            update_post_meta( $post_id, '_dust_cost', absint( wp_unslash( $_POST['deck_dust'] ) ) );
        }
        if ( isset( $_POST['deck_author'] ) ) {
            update_post_meta( $post_id, '_deck_player', sanitize_text_field( wp_unslash( $_POST['deck_author'] ) ) );
        }
        if ( isset( $_POST['deck_code'] ) ) {
            update_post_meta( $post_id, '_deck_code', sanitize_textarea_field( wp_unslash( $_POST['deck_code'] ) ) );
        }

        $show_in_feed = isset( $_POST['deck_show_in_feed'] ) ? '1' : '0';
        update_post_meta( $post_id, '_show_in_feed', $show_in_feed );
        update_post_meta( $post_id, '_hide_from_feed', $show_in_feed === '1' ? '0' : '1' );

        $mode = isset( $_POST['stats_mode'] ) ? sanitize_key( wp_unslash( $_POST['stats_mode'] ) ) : 'standard';

        if ( $mode === 'total' ) {
            $wins  = isset( $_POST['deck_wins_total'] ) ? absint( wp_unslash( $_POST['deck_wins_total'] ) ) : ( isset( $_POST['deck_wins'] ) ? absint( wp_unslash( $_POST['deck_wins'] ) ) : 0 );
            $total = isset( $_POST['deck_total'] ) ? absint( wp_unslash( $_POST['deck_total'] ) ) : 0;
            $losses = max( 0, $total - $wins );
        } else {
            $wins   = isset( $_POST['deck_wins'] ) ? absint( wp_unslash( $_POST['deck_wins'] ) ) : 0;
            $losses = isset( $_POST['deck_losses'] ) ? absint( wp_unslash( $_POST['deck_losses'] ) ) : 0;
            $total  = $wins + $losses;
        }

        update_post_meta( $post_id, '_deck_wins', $wins );
        update_post_meta( $post_id, '_deck_losses', $losses );

        $winrate_val = $total > 0 ? round( ( $wins / $total ) * 100, 2 ) : 0;
        update_post_meta( $post_id, '_deck_winrate_val', $winrate_val );

        delete_transient( 'hs_deck_manager_global_stats' );
    }
    public function render_ads_page() {
        $ads_config = get_option( 'hs_deck_manager_ads_config', '[]' );
        if ( empty( $ads_config ) ) {
            $ads_config = '[]';
        }

        $plugin_path = plugin_dir_path( __FILE__ );
        $has_bundle  = file_exists( $plugin_path . 'build/ads-manager.asset.php' );

        if ( $has_bundle ) {
            wp_localize_script( 'hs-deck-ads-manager', 'hsDeckManagerAds', array(
                'adsConfig' => $ads_config,
                'inputId'   => 'hs_deck_manager_ads_config',
            ) );
        }
        ?>
        <div class="wrap hs-admin-page">
            <h1><?php esc_html_e( 'Реклама', 'hs_deck_manager' ); ?></h1>
            <?php if ( ! $has_bundle ) : ?>
                <div class="notice notice-warning"><p><?php esc_html_e( 'Сборка интерфейса рекламы не найдена. Отредактируйте JSON вручную.', 'hs_deck_manager' ); ?></p></div>
            <?php endif; ?>
            <form method="post" action="options.php" id="hs-ads-form">
                <?php settings_fields( 'hs_deck_manager_ads_group' ); ?>
                <?php do_settings_sections( 'hs_deck_manager_ads_group' ); ?>
                <div id="hs-ads-root"></div>
                <textarea name="hs_deck_manager_ads_config" id="hs_deck_manager_ads_config" class="<?php echo $has_bundle ? 'hs-hidden-field' : 'large-text code'; ?>" rows="8"><?php echo esc_textarea( $ads_config ); ?></textarea>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
        if ( ! $has_bundle ) {
            $this->render_ads_page_inline_fallback( $ads_config );
        }
    }
    private function render_ads_page_inline_fallback( $ads_config ) {
        ?>
        <div class="wrap">
            <p class="description">
                <?php esc_html_e( 'Пример формата JSON: [{"content":"<b>Текст</b>","image":"https://...","btnText":"Подробнее","btnUrl":"https://..."}]', 'hs_deck_manager' ); ?>
            </p>
        </div>
        <?php
    }
    public function single_deck_shortcode( $atts ) {
        $atts = shortcode_atts( array( 'id' => 0 ), $atts, 'hs_deck' );
        if ( empty( $atts['id'] ) ) return '';
        return $this->feed_shortcode( array( 'id' => $atts['id'], 'columns' => 1, 'posts_per_page' => 1 ) );
    }

    /**
     * Shortcode: Feed
     * Attributes: id, posts_per_page (per_page), columns, paged (hs_page), sort (hs_order).
     * Default 8 decks per page; lazy-loaded images; fast sort (date, dust, popular).
     */
    public function feed_shortcode( $atts ) {
        static $feed_index = 0;
        $feed_index++;
        $wrapper_id = 'hs-feed-' . $feed_index;

        $atts = shortcode_atts( array(
            'posts_per_page' => 8,
            'per_page'       => 8,
            'columns'        => 3,
            'id'             => '',
        ), $atts, 'hs_decks' );

        $per_page  = max( 1, (int) ( $atts['per_page'] ? $atts['per_page'] : $atts['posts_per_page'] ) );
        $columns   = max( 1, min( 6, (int) $atts['columns'] ) );
        $single_id = ! empty( $atts['id'] ) ? (int) $atts['id'] : 0;

        $order_param = isset( $_GET['hs_order'] ) ? sanitize_key( $_GET['hs_order'] ) : 'date';
        $allowed_orders = array( 'date' => 1, 'dust' => 1, 'popular' => 1 );
        if ( ! isset( $allowed_orders[ $order_param ] ) ) {
            $order_param = 'date';
        }

        $args = array(
            'post_type'      => 'hs_deck',
            'post_status'    => 'publish',
            'posts_per_page' => $single_id ? 1 : $per_page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if ( $order_param === 'dust' ) {
            $args['meta_key'] = '_dust_cost';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = 'ASC';
        } elseif ( $order_param === 'popular' ) {
            $args['meta_key'] = '_deck_copies';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = 'DESC';
        }

        if ( $single_id ) {
            $args['p'] = $single_id;
        } else {
            $current_page = isset( $_GET['hs_page'] ) ? max( 1, (int) $_GET['hs_page'] ) : 1;
            $args['paged'] = $current_page;
            $args['meta_query'] = array(
                array(
                    'key'     => '_show_in_feed',
                    'value'   => '1',
                    'compare' => '=',
                ),
            );
        }

        $query = new WP_Query( $args );

        $plugin_path = plugin_dir_path( __FILE__ );
        $plugin_url  = plugin_dir_url( __FILE__ );
        wp_enqueue_style( 'hs-deck-frontend', $plugin_url . 'assets/css/hs-deck-frontend.css', array(), $this->version );

        if ( file_exists( $plugin_path . 'build/feed.js' ) ) {
            wp_enqueue_script( 'hs-deck-feed' );
            wp_localize_script( 'hs-deck-feed', 'hsDeckAjax', array(
                'url'   => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'hs_decks_nonce' ),
            ) );
        } else {
            wp_enqueue_script( 'hs-deck-frontend', $plugin_url . 'assets/js/hs-deck-frontend.js', array(), $this->version, true );
            wp_localize_script( 'hs-deck-frontend', 'hsDeckAjax', array(
                'url'   => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'hs_decks_nonce' ),
            ) );
        }

        $ads_config = json_decode( get_option( 'hs_deck_manager_ads_config', '[]' ), true );
        $ad_start   = (int) get_option( 'hs_deck_manager_ad_start', 3 );
        $ad_repeat  = (int) get_option( 'hs_deck_manager_ad_repeat', 7 );
        $ad_enabled = get_option( 'hs_deck_manager_ad_enabled' );

        $class_terms = get_terms( array( 'taxonomy' => 'deck_class', 'hide_empty' => true ) );
        $mode_terms  = get_terms( array( 'taxonomy' => 'deck_mode', 'hide_empty' => true ) );

        $total_pages     = $single_id ? 1 : (int) $query->max_num_pages;
        $current_page    = $single_id ? 1 : ( isset( $_GET['hs_page'] ) ? max( 1, (int) $_GET['hs_page'] ) : 1 );
        $base_url        = get_permalink();
        $pagination_base = $base_url;
        if ( $order_param !== 'date' ) {
            $pagination_base = add_query_arg( 'hs_order', $order_param, $pagination_base );
        }
        $page_links = '';
        if ( ! $single_id && $total_pages > 1 ) {
            $page_links = paginate_links( array(
                'base'      => add_query_arg( 'hs_page', '%#%', $pagination_base ),
                'format'    => '',
                'current'   => $current_page,
                'total'     => $total_pages,
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'type'      => 'list',
            ) );
        }
        $sort_url_date    = remove_query_arg( array( 'hs_page', 'hs_order' ), $base_url );
        $sort_url_dust    = add_query_arg( 'hs_order', 'dust', remove_query_arg( 'hs_page', $base_url ) );
        $sort_url_popular = add_query_arg( 'hs_order', 'popular', remove_query_arg( 'hs_page', $base_url ) );

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $wrapper_id ); ?>" class="hs-wrapper">
            <div class="hs-filters">
                <input type="text" id="<?php echo esc_attr( $wrapper_id ); ?>-search" placeholder="<?php esc_attr_e( 'Поиск...', 'hs_deck_manager' ); ?>" class="hs-control hs-search">
                <select id="<?php echo esc_attr( $wrapper_id ); ?>-class" class="hs-control hs-filter-class">
                    <option value=""><?php esc_html_e( 'Все классы', 'hs_deck_manager' ); ?></option>
                    <?php if ( ! is_wp_error( $class_terms ) ) { foreach ( $class_terms as $term ) { ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
                    <?php } } ?>
                </select>
                <select id="<?php echo esc_attr( $wrapper_id ); ?>-mode" class="hs-control hs-filter-mode">
                    <option value=""><?php esc_html_e( 'Все режимы', 'hs_deck_manager' ); ?></option>
                    <?php if ( ! is_wp_error( $mode_terms ) ) { foreach ( $mode_terms as $term ) { ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
                    <?php } } ?>
                </select>
                <?php if ( ! $single_id ) { ?>
                <span class="hs-sort-label"><?php esc_html_e( 'Сортировка:', 'hs_deck_manager' ); ?></span>
                <div class="hs-sort">
                    <a href="<?php echo esc_url( $sort_url_date ); ?>" class="hs-sort-btn <?php echo $order_param === 'date' ? 'hs-sort-active' : ''; ?>"><?php esc_html_e( 'Сначала новые', 'hs_deck_manager' ); ?></a>
                    <a href="<?php echo esc_url( $sort_url_dust ); ?>" class="hs-sort-btn <?php echo $order_param === 'dust' ? 'hs-sort-active' : ''; ?>"><?php esc_html_e( 'По пыли', 'hs_deck_manager' ); ?></a>
                    <a href="<?php echo esc_url( $sort_url_popular ); ?>" class="hs-sort-btn <?php echo $order_param === 'popular' ? 'hs-sort-active' : ''; ?>"><?php esc_html_e( 'По популярности', 'hs_deck_manager' ); ?></a>
                </div>
                <?php } ?>
            </div>

            <div id="<?php echo esc_attr( $wrapper_id ); ?>-grid" class="hs-deck-feed hs-columns-<?php echo (int) $columns; ?>">
                <?php
                $count = 0;
                if ( $query->have_posts() ) {
                    while ( $query->have_posts() ) {
                        $query->the_post();
                        $count++;
                        $this->render_deck_card( get_the_ID() );
                        if ( $ad_enabled && ! empty( $ads_config ) && $count >= $ad_start && ( $count - $ad_start ) % $ad_repeat === 0 ) {
                            $ad_index = ( ( $count - $ad_start ) / $ad_repeat ) % count( $ads_config );
                            $this->render_ad_card( $ads_config[ $ad_index ] );
                        }
                    }
                    wp_reset_postdata();
                } else {
                    echo '<p class="hs-no-decks">' . esc_html__( 'Колоды не найдены.', 'hs_deck_manager' ) . '</p>';
                }
                ?>
            </div>

            <?php if ( $page_links ) { ?>
                <nav class="hs-deck-pagination" aria-label="<?php esc_attr_e( 'Навигация по страницам колод', 'hs_deck_manager' ); ?>">
                    <?php echo $page_links; ?>
                </nav>
            <?php } ?>

            <div class="hs-lightbox" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Изображение в полном размере', 'hs_deck_manager' ); ?>">
                <img src="" alt="">
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    private function render_deck_card( $post_id ) {
        $title  = get_the_title( $post_id );
        $image  = get_the_post_thumbnail_url( $post_id, 'large' );
        $image_full = get_the_post_thumbnail_url( $post_id, 'full' );
        if ( ! $image_full ) {
            $image_full = $image;
        }
        $dust   = get_post_meta( $post_id, '_dust_cost', true );
        $code   = get_post_meta( $post_id, '_deck_code', true );
        $author = get_post_meta( $post_id, '_deck_player', true );
        $wins   = (int) get_post_meta( $post_id, '_deck_wins', true );
        $losses = (int) get_post_meta( $post_id, '_deck_losses', true );
        $total  = $wins + $losses;
        $winrate_pct = $total > 0 ? ( $wins / $total ) * 100 : 0;
        $winrate_str = $total > 0 ? number_format( $winrate_pct, 1, ',', ' ' ) . '%' : '—';

        $classes = wp_get_post_terms( $post_id, 'deck_class', array( 'fields' => 'slugs' ) );
        $modes   = wp_get_post_terms( $post_id, 'deck_mode', array( 'fields' => 'slugs' ) );
        $class_str = ! is_wp_error( $classes ) ? implode( ' ', $classes ) : '';
        $mode_str  = ! is_wp_error( $modes ) ? implode( ' ', $modes ) : '';
        ?>
        <div class="hs-card" data-class="<?php echo esc_attr( $class_str ); ?>" data-mode="<?php echo esc_attr( $mode_str ); ?>" data-author="<?php echo esc_attr( $author ); ?>">
            <?php if ( $image ) { ?>
                <div class="hs-deck-img-wrap hs-card-image" role="button" tabindex="0" data-hs-full="<?php echo esc_url( $image_full ); ?>" data-hs-title="<?php echo esc_attr( $title ); ?>" title="<?php esc_attr_e( 'Открыть в полном размере', 'hs_deck_manager' ); ?>">
                    <img src="<?php echo esc_url( $image ); ?>" loading="lazy" decoding="async" alt="<?php echo esc_attr( $title ); ?>">
                </div>
            <?php } else { ?>
                <div class="hs-no-image"><?php esc_html_e( 'Нет изображения', 'hs_deck_manager' ); ?></div>
            <?php } ?>
            <div class="hs-card-body">
                <h3 class="hs-card-title"><?php echo esc_html( $title ); ?></h3>
                <div class="hs-card-meta">
                    <span class="hs-meta-tag hs-dust"><?php echo esc_html( $dust ); ?></span>
                    <span class="hs-meta-tag"><?php echo esc_html( $winrate_str ); ?></span>
                </div>
                <?php if ( $author ) { ?>
                    <p class="hs-card-author"><span class="hs-author-icon" aria-hidden="true"></span><?php echo esc_html( $author ); ?></p>
                <?php } ?>
                <button type="button" class="hs-copy-code-btn" data-code="<?php echo esc_attr( $code ); ?>" data-label="<?php esc_attr_e( 'Копировать код', 'hs_deck_manager' ); ?>" data-done="<?php esc_attr_e( 'Скопировано', 'hs_deck_manager' ); ?>">
                    <?php esc_html_e( 'Копировать код', 'hs_deck_manager' ); ?>
                </button>
                <div class="hs-card-footer">
                    <?php echo (int) $wins; ?> <?php esc_html_e( 'побед', 'hs_deck_manager' ); ?> / <?php echo (int) $losses; ?> <?php esc_html_e( 'поражений', 'hs_deck_manager' ); ?><?php if ( $total > 0 ) { ?> · <?php esc_html_e( 'всего', 'hs_deck_manager' ); ?> <?php echo (int) $total; ?><?php } ?>
                </div>
            </div>
        </div>
        <?php
    }
    private function render_ad_card( $ad ) {
        if ( empty( $ad ) || ! is_array( $ad ) ) {
            return;
        }
        $image   = isset( $ad['image'] ) ? $ad['image'] : '';
        $content = isset( $ad['content'] ) ? $ad['content'] : '';
        $btnText = isset( $ad['btnText'] ) ? $ad['btnText'] : '';
        $btnUrl  = isset( $ad['btnUrl'] ) ? $ad['btnUrl'] : '';
        ?>
        <div class="hs-ad-card">
            <?php if ( ! empty( $image ) ) : ?>
                <img src="<?php echo esc_url( $image ); ?>" class="hs-ad-card-image" alt="">
            <?php endif; ?>
            <?php if ( ! empty( $content ) ) : ?>
                <div class="hs-ad-card-content"><?php echo wp_kses_post( $content ); ?></div>
            <?php endif; ?>
            <?php if ( ! empty( $btnUrl ) ) : ?>
                <a href="<?php echo esc_url( $btnUrl ); ?>" class="hs-ad-card-link"><?php echo esc_html( $btnText ); ?></a>
            <?php endif; ?>
        </div>
        <?php
    }
    public function track_event() {
        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $event = isset( $_POST['event_type'] ) ? sanitize_text_field( $_POST['event_type'] ) : '';
        if ( ! $post_id ) wp_send_json_error();

        if ( $event === 'view' ) {
            $views = (int) get_post_meta( $post_id, '_deck_views', true );
            update_post_meta( $post_id, '_deck_views', $views + 1 );
            $this->record_daily_stat( 'views' );
        } elseif ( $event === 'copy' ) {
            $total = (int) get_post_meta( $post_id, '_deck_copies', true );
            update_post_meta( $post_id, '_deck_copies', $total + 1 );
            $this->record_daily_stat( 'copies' );
        } elseif ( $event === 'click' ) {
            $clicks = (int) get_post_meta( $post_id, '_deck_clicks', true );
            update_post_meta( $post_id, '_deck_clicks', $clicks + 1 );
            $this->record_daily_stat( 'clicks' );
        }
        wp_send_json_success();
    }

    /**
     * Legacy AJAX: copy_deck_code (wp-manacost-decks) — same meta _deck_copies for migration.
     */
    public function track_copy_legacy() {
        if ( isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'hs_decks_nonce' ) ) {
            $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
            if ( $post_id && get_post_type( $post_id ) === 'hs_deck' ) {
                $copies = (int) get_post_meta( $post_id, '_deck_copies', true );
                update_post_meta( $post_id, '_deck_copies', $copies + 1 );
                $this->record_daily_stat( 'copies' );
            }
        }
        wp_send_json_success();
    }

    /**
     * Record one event in daily stats (for dashboard charts).
     * Stores last 365 days in option hs_deck_manager_daily_stats.
     */
    private function record_daily_stat( $type ) {
        $key = 'hs_deck_manager_daily_stats';
        $today = gmdate( 'Y-m-d' );
        $data = get_option( $key, array() );
        if ( ! isset( $data[ $today ] ) ) {
            $data[ $today ] = array( 'views' => 0, 'copies' => 0, 'clicks' => 0 );
        }
        if ( isset( $data[ $today ][ $type ] ) ) {
            $data[ $today ][ $type ]++;
        }
        $cut = array_slice( array_keys( $data ), -365, null, true );
        $data = array_intersect_key( $data, array_flip( $cut ) );
        update_option( $key, $data, false );
    }

    /**
     * Get daily stats for the last N days (for charts). Fills missing days with zeros.
     */
    public function get_daily_stats_for_period( $days = 30 ) {
        $key = 'hs_deck_manager_daily_stats';
        $raw = get_option( $key, array() );
        $result = array( 'labels' => array(), 'views' => array(), 'copies' => array(), 'clicks' => array() );
        for ( $i = $days - 1; $i >= 0; $i-- ) {
            $date = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
            $result['labels'][] = $date;
            $row = isset( $raw[ $date ] ) ? $raw[ $date ] : array( 'views' => 0, 'copies' => 0, 'clicks' => 0 );
            $result['views'][]  = (int) ( $row['views'] ?? 0 );
            $result['copies'][] = (int) ( $row['copies'] ?? 0 );
            $result['clicks'][] = (int) ( $row['clicks'] ?? 0 );
        }
        return $result;
    }

    /**
     * Get top decks by copies (with views/clicks). For dashboard table.
     */
    public function get_top_decks( $limit = 10 ) {
        $query = new WP_Query( array(
            'post_type'      => 'hs_deck',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'orderby'        => 'meta_value_num',
            'meta_key'       => '_deck_copies',
            'order'          => 'DESC',
            'fields'         => 'ids',
        ) );
        $ids = $query->posts;
        $out = array();
        foreach ( $ids as $id ) {
            $out[] = array(
                'id'     => $id,
                'title'  => get_the_title( $id ),
                'views'  => (int) get_post_meta( $id, '_deck_views', true ),
                'copies' => (int) get_post_meta( $id, '_deck_copies', true ),
                'clicks' => (int) get_post_meta( $id, '_deck_clicks', true ),
                'edit'   => get_edit_post_link( $id, 'raw' ),
            );
        }
        return $out;
    }

    /**
     * Get aggregated stats by taxonomy (deck_class or deck_mode): term name => count, views, copies, clicks.
     */
    public function get_stats_by_taxonomy( $taxonomy ) {
        $terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
        if ( is_wp_error( $terms ) ) {
            return array();
        }
        global $wpdb;
        $meta = $wpdb->postmeta;
        $rel = $wpdb->term_relationships;
        $tax = $wpdb->term_taxonomy;
        $posts = $wpdb->posts;
        $out = array();
        foreach ( $terms as $term ) {
            $post_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT tr.object_id FROM {$rel} tr INNER JOIN {$tax} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                 INNER JOIN {$posts} p ON p.ID = tr.object_id
                 WHERE tt.term_id = %d AND p.post_type = 'hs_deck' AND p.post_status = 'publish'",
                $term->term_id
            ) );
            $count = count( $post_ids );
            $views = $copies = $clicks = 0;
            if ( $count > 0 ) {
                $ids_placeholders = implode( ',', array_fill( 0, $count, '%d' ) );
                foreach ( array( '_deck_views' => 'views', '_deck_copies' => 'copies', '_deck_clicks' => 'clicks' ) as $meta_key => $var ) {
                    $val = $wpdb->get_var( $wpdb->prepare(
                        "SELECT COALESCE(SUM(CAST(meta_value AS UNSIGNED)), 0) FROM {$meta} WHERE post_id IN ($ids_placeholders) AND meta_key = %s",
                        array_merge( $post_ids, array( $meta_key ) )
                    ) );
                    $$var = (int) $val;
                }
            }
            $out[] = array(
                'name'   => $term->name,
                'count'  => $count,
                'views'  => $views,
                'copies' => $copies,
                'clicks' => $clicks,
            );
        }
        return $out;
    }
}

/**
 * Activation: flush rewrite rules and create default taxonomy terms (migration‑compatible with wp-manacost-decks)
 */
function hs_deck_manager_activate() {
	$plugin = hs_deck_manager_Plugin::get_instance();
	$plugin->register_cpt();
	flush_rewrite_rules();
	if ( function_exists( 'term_exists' ) && function_exists( 'wp_insert_term' ) ) {
		$classes = array( 'Друид', 'Охотник', 'Маг', 'Паладин', 'Жрец', 'Разбойник', 'Шаман', 'Чернокнижник', 'Воин', 'Рыцарь смерти' );
		foreach ( $classes as $class ) {
			if ( ! term_exists( $class, 'deck_class' ) ) {
				wp_insert_term( $class, 'deck_class' );
			}
		}
		$modes = array( 'Стандарт', 'Вольный', 'Классический', 'Арена' );
		foreach ( $modes as $mode ) {
			if ( ! term_exists( $mode, 'deck_mode' ) ) {
				wp_insert_term( $mode, 'deck_mode' );
			}
		}
	}
}
register_activation_hook( __FILE__, 'hs_deck_manager_activate' );

// Initialize
hs_deck_manager_Plugin::get_instance();
