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
        // CPT: hs_deck
        $labels = array(
            'name'                  => _x( 'Decks', 'Post Type General Name', 'hs_deck_manager' ),
            'singular_name'         => _x( 'Deck', 'Post Type Singular Name', 'hs_deck_manager' ),
            'menu_name'             => __( 'Decks', 'hs_deck_manager' ),
            'all_items'             => __( 'Все колоды', 'hs_deck_manager' ),
            'add_new_item'          => __( 'Добавить новую колоду', 'hs_deck_manager' ),
            'add_new'               => __( 'Добавить новую', 'hs_deck_manager' ),
            'new_item'              => __( 'Новая колода', 'hs_deck_manager' ),
            'edit_item'             => __( 'Редактировать колоду', 'hs_deck_manager' ),
            'view_item'             => __( 'Просмотр колоды', 'hs_deck_manager' ),
            'search_items'          => __( 'Поиск колод', 'hs_deck_manager' ),
            'not_found'             => __( 'Колоды не найдены', 'hs_deck_manager' ),
        );
        
        $args = array(
            'label'                 => __( 'Deck', 'hs_deck_manager' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'thumbnail', 'excerpt', 'custom-fields' ),
            'taxonomies'            => array( 'deck_class', 'deck_mode' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-images-alt2',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rewrite'               => array( 'slug' => 'deck', 'with_front' => false, 'feeds' => true, 'pages' => true ),
        );
        register_post_type( 'hs_deck', $args );

        // Taxonomy: Class
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

        // Taxonomy: Mode
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

    /**
     * Register post meta for hs_deck (REST/migration‑compatible with wp-manacost-decks)
     */
    public function register_deck_meta() {
        $meta = array(
            '_deck_code'        => array( 'type' => 'string', 'sanitize' => 'sanitize_textarea_field' ),
            '_dust_cost'         => array( 'type' => 'integer', 'sanitize' => 'absint' ),
            '_custom_tags'       => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
            '_deck_player'       => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
            '_deck_streamer'     => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
            '_deck_source_url'   => array( 'type' => 'string', 'sanitize' => 'esc_url_raw' ),
            '_deck_wins'         => array( 'type' => 'integer', 'sanitize' => 'absint' ),
            '_deck_losses'       => array( 'type' => 'integer', 'sanitize' => 'absint' ),
            '_deck_peak'         => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
            '_deck_latest'       => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
            '_deck_worst'        => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
            '_hide_from_feed'    => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
            '_deck_views'        => array( 'type' => 'integer', 'sanitize' => 'absint' ),
            '_deck_copies'       => array( 'type' => 'integer', 'sanitize' => 'absint' ),
            '_deck_clicks'       => array( 'type' => 'integer', 'sanitize' => 'absint' ),
        );
        foreach ( $meta as $key => $opts ) {
            register_post_meta( 'hs_deck', $key, array(
                'type'              => $opts['type'],
                'single'            => true,
                'show_in_rest'      => true,
                'auth_callback'     => function( $allowed, $meta_key, $post_id ) {
                    return current_user_can( 'edit_post', $post_id );
                },
                'sanitize_callback' => $opts['sanitize'],
            ) );
        }
    }

    /**
     * Register Shortcodes
     */
    public function register_shortcodes() {
        add_shortcode( 'hs_decks', array( $this, 'feed_shortcode' ) );
        add_shortcode( 'hs_deck', array( $this, 'single_deck_shortcode' ) );
    }

    /**
     * Admin Menu
     */
    public function add_admin_menu() {
        add_submenu_page( 'edit.php?post_type=hs_deck', __( 'Дашборд', 'hs_deck_manager' ), __( 'Дашборд', 'hs_deck_manager' ), 'manage_options', 'hs_deck_manager_stats', array( $this, 'render_stats_page' ) );
        add_submenu_page( 'edit.php?post_type=hs_deck', __( 'Объявления', 'hs_deck_manager' ), __( 'Объявления', 'hs_deck_manager' ), 'manage_options', 'hs_deck_manager_announcements', array( $this, 'render_announcements_page' ) );
        add_submenu_page( 'edit.php?post_type=hs_deck', __( 'Реклама', 'hs_deck_manager' ), __( 'Реклама', 'hs_deck_manager' ), 'manage_options', 'hs_deck_manager_ads', array( $this, 'render_ads_page' ) );
    }

    /**
     * Register Settings
     */
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
        wp_enqueue_media();

        $stats_hook = ( strpos( $hook, 'hs_deck_manager_stats' ) !== false );
        if ( $stats_hook ) {
            wp_enqueue_script(
                'chart-js',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
                array(),
                '4.4.1',
                false
            );
        }

        if ( strpos( $hook, 'hs_deck_manager_ads' ) === false && ! $stats_hook ) {
            return;
        }

        $plugin_url  = plugin_dir_url( __FILE__ );
        $plugin_path = plugin_dir_path( __FILE__ );
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
        } else {
            wp_enqueue_script( 'wp-element' );
            wp_enqueue_script( 'wp-components' );
            wp_enqueue_script( 'wp-i18n' );
            wp_enqueue_style( 'wp-components' );
        }
    }

    /**
     * Meta Boxes
     */
    public function add_meta_boxes() {
        add_meta_box( 'hs_deck_manager_stats', __( 'Информация о колоде', 'hs_deck_manager' ), array( $this, 'render_meta_box_stats' ), 'hs_deck', 'normal', 'high' );
        add_meta_box( 'hs_deck_manager_shortcode', __( 'Шорткод', 'hs_deck_manager' ), array( $this, 'render_meta_box_shortcode' ), 'hs_deck', 'side', 'high' );
        add_meta_box( 'hs_deck_manager_analytics', __( 'Статистика (Live)', 'hs_deck_manager' ), array( $this, 'render_meta_box_analytics' ), 'hs_deck', 'normal', 'default' );
    }

    /**
     * Render Meta Box: Stats
     */
    public function render_meta_box_stats( $post ) {
        wp_nonce_field( 'hs_deck_manager_save_stats', 'hs_deck_manager_stats_nonce' );
        
        $dust = get_post_meta( $post->ID, '_dust_cost', true );
        $author = get_post_meta( $post->ID, '_deck_player', true );
        $wins = get_post_meta( $post->ID, '_deck_wins', true );
        $losses = get_post_meta( $post->ID, '_deck_losses', true );
        $code = get_post_meta( $post->ID, '_deck_code', true );
        $exclude = get_post_meta( $post->ID, '_hide_from_feed', true );
        
        // Simple HTML for meta box (React is overkill here for now, but could be upgraded)
        ?>
        <div class="hs-meta-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <p>
                <label><strong>Стоимость (Пыль)</strong></label>
                <input type="number" name="deck_dust" value="<?php echo esc_attr($dust); ?>" class="widefat">
            </p>
            <p>
                <label><strong>Автор</strong></label>
                <input type="text" name="deck_author" value="<?php echo esc_attr($author); ?>" class="widefat">
            </p>
            <p style="grid-column: span 2;">
                <label><strong>Код колоды</strong></label>
                <textarea name="deck_code" class="widefat" rows="3"><?php echo esc_textarea($code); ?></textarea>
            </p>
            <p style="grid-column: span 2;">
                <label>
                    <input type="checkbox" name="deck_exclude_feed" value="1" <?php checked($exclude, '1'); ?>>
                    Не добавлять в ленту топ колод
                </label>
            </p>
            
            <div style="grid-column: span 2; background: #f0f0f1; padding: 10px; border-radius: 4px;">
                <strong>Режим ввода статистики:</strong><br>
                <label style="margin-right: 15px;"><input type="radio" name="stats_mode" value="standard" checked onchange="toggleStatsMode(this.value)"> Победы / Поражения</label>
                <label><input type="radio" name="stats_mode" value="total" onchange="toggleStatsMode(this.value)"> Победы / Всего игр</label>
            </div>
            
            <p>
                <label><strong>Победы</strong></label>
                <input type="number" name="deck_wins" value="<?php echo esc_attr($wins); ?>" class="widefat">
            </p>
            <p id="field_losses">
                <label><strong>Поражения</strong></label>
                <input type="number" name="deck_losses" value="<?php echo esc_attr($losses); ?>" class="widefat">
            </p>
            <p id="field_total" style="display:none;">
                <label><strong>Всего игр</strong></label>
                <input type="number" name="deck_total" value="<?php echo esc_attr((int)$wins + (int)$losses); ?>" class="widefat">
            </p>
        </div>
        <script>
        function toggleStatsMode(mode) {
            document.getElementById('field_losses').style.display = mode === 'standard' ? 'block' : 'none';
            document.getElementById('field_total').style.display = mode === 'total' ? 'block' : 'none';
        }
        </script>
        <?php
    }

    /**
     * Render Meta Box: Shortcode
     */
    public function render_meta_box_shortcode( $post ) {
        $code = '[hs_deck id="' . $post->ID . '"]';
        echo '<input type="text" value="' . esc_attr( $code ) . '" class="widefat" readonly onclick="this.select()">';
        echo '<p class="description">Скопируйте для вставки.</p>';
    }

    /**
     * Render Meta Box: Analytics
     */
    public function render_meta_box_analytics( $post ) {
        $views = (int) get_post_meta( $post->ID, '_deck_views', true );
        $copies = (int) get_post_meta( $post->ID, '_deck_copies', true );
        $clicks = (int) get_post_meta( $post->ID, '_deck_clicks', true );
        ?>
        <div style="display: flex; justify-content: space-around; text-align: center;">
            <div><div style="font-size: 20px; font-weight: bold;"><?php echo $views; ?></div><div style="color: #666;">Просмотры</div></div>
            <div><div style="font-size: 20px; font-weight: bold; color: #2271b1;"><?php echo $copies; ?></div><div style="color: #666;">Копии</div></div>
            <div><div style="font-size: 20px; font-weight: bold;"><?php echo $clicks; ?></div><div style="color: #666;">Клики</div></div>
        </div>
        <?php
    }

    /**
     * Save Post Meta
     */
    public function save_post_meta( $post_id ) {
        if ( ! isset( $_POST['hs_deck_manager_stats_nonce'] ) || ! wp_verify_nonce( $_POST['hs_deck_manager_stats_nonce'], 'hs_deck_manager_save_stats' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        // Direct mapping (meta keys match old plugin wp-manacost-decks for migration)
        if ( isset( $_POST['deck_dust'] ) ) update_post_meta( $post_id, '_dust_cost', absint( $_POST['deck_dust'] ) );
        if ( isset( $_POST['deck_author'] ) ) update_post_meta( $post_id, '_deck_player', sanitize_text_field( $_POST['deck_author'] ) );
        if ( isset( $_POST['deck_code'] ) ) update_post_meta( $post_id, '_deck_code', sanitize_textarea_field( $_POST['deck_code'] ) );
        
        $exclude = isset( $_POST['deck_exclude_feed'] ) ? '1' : '0';
        update_post_meta( $post_id, '_hide_from_feed', $exclude );

        // Stats Logic
        $wins = isset($_POST['deck_wins']) ? intval($_POST['deck_wins']) : 0;
        update_post_meta( $post_id, '_deck_wins', $wins );
        
        $losses = 0;
        if ( isset($_POST['stats_mode']) && $_POST['stats_mode'] === 'total' ) {
            $total = isset($_POST['deck_total']) ? intval($_POST['deck_total']) : 0;
            $losses = max(0, $total - $wins);
        } else {
            $losses = isset($_POST['deck_losses']) ? intval($_POST['deck_losses']) : 0;
        }
        update_post_meta( $post_id, '_deck_losses', $losses );

        delete_transient( 'hs_deck_manager_global_stats' );
    }

    /**
     * Render Ads Page (React)
     * Uses built ads-manager.js when available; data passed via wp_localize_script.
     */
    public function render_ads_page() {
        $ads_config = get_option( 'hs_deck_manager_ads_config', '[]' );
        if ( empty( $ads_config ) ) {
            $ads_config = '[]';
        }

        $plugin_path = plugin_dir_path( __FILE__ );
        if ( file_exists( $plugin_path . 'build/ads-manager.asset.php' ) ) {
            wp_localize_script( 'hs-deck-ads-manager', 'hsDeckManagerAds', array(
                'adsConfig' => $ads_config,
                'inputId'   => 'hs_deck_manager_ads_config',
            ) );
        }
        ?>
        <div class="wrap">
            <h1><?php _e( 'Настройки рекламы (React)', 'hs_deck_manager' ); ?></h1>
            <form method="post" action="options.php" id="hs-ads-form">
                <?php settings_fields( 'hs_deck_manager_ads_group' ); ?>
                <?php do_settings_sections( 'hs_deck_manager_ads_group' ); ?>
                <div id="hs-ads-root"></div>
                <textarea name="hs_deck_manager_ads_config" id="hs_deck_manager_ads_config" style="display:none;"><?php echo esc_textarea( $ads_config ); ?></textarea>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
        if ( ! file_exists( $plugin_path . 'build/ads-manager.asset.php' ) ) {
            $this->render_ads_page_inline_fallback( $ads_config );
        }
    }

    /**
     * Fallback: inline React when build is not present (e.g. before first npm run build).
     */
    private function render_ads_page_inline_fallback( $ads_config ) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var el = wp.element.createElement, r = wp.element.render, u = wp.element.useState, e = wp.element.useEffect;
            var B = wp.components.Button, T = wp.components.TextControl, Ta = wp.components.TextareaControl, C = wp.components.Card, Ch = wp.components.CardHeader, Cb = wp.components.CardBody;
            function AdsManager() {
                var state = u(<?php echo $ads_config; ?>), ads = state[0], setAds = state[1];
                e(function(){ var t = document.getElementById('hs_deck_manager_ads_config'); if(t) t.value = JSON.stringify(ads); }, [ads]);
                function add(){ setAds(ads.concat([{content:'',image:'',btnText:'Подробнее',btnUrl:''}])); }
                function remove(i){ var n = ads.slice(); n.splice(i,1); setAds(n); }
                function update(i,f,v){ var n = ads.slice(); n[i][f]=v; setAds(n); }
                function selectImg(i){ var f = wp.media({title:'Выберите изображение',button:{text:'Использовать'},multiple:false}); f.on('select',function(){ update(i,'image',f.state().get('selection').first().toJSON().url); }); f.open(); }
                return el('div',{className:'hs-ads-manager'}, el('div',{style:{marginBottom:'20px'}}, ads.map(function(ad,index){ return el(C,{key:index,style:{marginBottom:'15px',border:'1px solid #ddd'}}, el(Ch,{style:{display:'flex',justifyContent:'space-between',alignItems:'center',padding:'10px',background:'#f0f0f1'}}, el('strong',{},'Рекламный блок #'+(index+1)), el(B,{isDestructive:true,variant:'link',onClick:function(){ remove(index); }},'Удалить')), el(Cb,{}, el(Ta,{label:'HTML / Текст',value:ad.content,onChange:function(v){ update(index,'content',v); },rows:3}), el('div',{style:{display:'flex',gap:'15px'}}, el('div',{style:{flex:1}}, el(T,{label:'Текст кнопки',value:ad.btnText,onChange:function(v){ update(index,'btnText',v); }})), el('div',{style:{flex:1}}, el(T,{label:'Ссылка кнопки',value:ad.btnUrl,onChange:function(v){ update(index,'btnUrl',v); }}))), el('div',{style:{marginTop:'10px'}}, el('label',{style:{display:'block',marginBottom:'5px'}},'Изображение'), el('div',{style:{display:'flex',gap:'10px'}}, el(T,{value:ad.image,onChange:function(v){ update(index,'image',v); },style:{flex:1}}), el(B,{variant:'secondary',onClick:function(){ selectImg(index); }},'Выбрать')))))); })), el(B,{variant:'primary',onClick:add},'+ Добавить блок'));
            }
            var root = document.getElementById('hs-ads-root'); if(root) r(el(AdsManager), root);
        });
        </script>
        <?php
    }

    /**
     * Fallback: inline feed script when build/feed.js is not present.
     */
    private function render_feed_inline_script( $wrapper_id ) {
        ?>
        <script>
        (function() {
            var w = document.getElementById('<?php echo esc_js( $wrapper_id ); ?>');
            if (!w) return;
            var search = w.querySelector('.hs-search');
            var filterClass = w.querySelector('.hs-filter-class');
            var filterMode = w.querySelector('.hs-filter-mode');
            var cards = w.querySelectorAll('.hs-card');
            var lightbox = w.querySelector('.hs-lightbox');
            var lightboxImg = lightbox ? lightbox.querySelector('img') : null;
            function filterDecks() {
                var q = (search && search.value) ? search.value.toLowerCase() : '';
                var cls = (filterClass && filterClass.value) ? filterClass.value : '';
                var mode = (filterMode && filterMode.value) ? filterMode.value : '';
                cards.forEach(function(card) {
                    var h3 = card.querySelector('h3');
                    var title = h3 ? h3.textContent.toLowerCase() : '';
                    var cardClass = card.getAttribute('data-class') || '';
                    var cardMode = card.getAttribute('data-mode') || '';
                    var visible = true;
                    if (q && title.indexOf(q) === -1) visible = false;
                    if (cls && cardClass.indexOf(cls) === -1) visible = false;
                    if (mode && cardMode.indexOf(mode) === -1) visible = false;
                    card.style.display = visible ? '' : 'none';
                });
            }
            if (search) search.addEventListener('input', filterDecks);
            if (filterClass) filterClass.addEventListener('change', filterDecks);
            if (filterMode) filterMode.addEventListener('change', filterDecks);
            if (lightbox && lightboxImg) {
                w.addEventListener('click', function(e) {
                    var wrap = e.target.closest('.hs-deck-img-wrap');
                    if (wrap) {
                        e.preventDefault();
                        var src = wrap.getAttribute('data-hs-full');
                        if (src) { lightboxImg.src = src; lightboxImg.alt = wrap.querySelector('img') ? wrap.querySelector('img').alt : ''; lightbox.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
                    }
                });
                function closeLightbox() { lightbox.style.display = 'none'; document.body.style.overflow = ''; }
                lightbox.addEventListener('click', closeLightbox);
                document.addEventListener('keydown', function keyClose(ev) { if (ev.key === 'Escape' && lightbox.style.display === 'flex') closeLightbox(); });
            }
            w.querySelectorAll('.hs-copy-code-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var code = this.getAttribute('data-code') || '';
                    var label = this.getAttribute('data-label') || 'Копировать код';
                    var done = this.getAttribute('data-done') || 'Скопировано';
                    var self = this;
                    function copyDone() {
                        self.classList.add('hs-copy-done');
                        self.style.transform = 'scale(0.97)';
                        self.textContent = done;
                        setTimeout(function() { self.style.transform = ''; setTimeout(function() { self.classList.remove('hs-copy-done'); self.textContent = label; }, 180); }, 200);
                    }
                    if (typeof navigator.clipboard !== 'undefined' && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(code).then(copyDone).catch(function() {
                            try { var ta = document.createElement('textarea'); ta.value = code; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta); copyDone(); } catch (err) {}
                        });
                    } else {
                        try { var ta = document.createElement('textarea'); ta.value = code; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta); copyDone(); } catch (err) {}
                    }
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Render Announcements Page
     */
    public function render_announcements_page() {
        // ... (Keep existing simple HTML logic for now, or upgrade similarly)
        echo '<div class="wrap"><h1>Объявления</h1><form method="post" action="options.php">';
        settings_fields( 'hs_deck_manager_announcements_group' );
        do_settings_sections( 'hs_deck_manager_announcements_group' );
        ?>
        <table class="form-table">
            <tr>
                <th>Текст</th>
                <td><textarea name="hs_deck_manager_announcement_text" class="large-text" rows="5"><?php echo esc_textarea(get_option('hs_deck_manager_announcement_text')); ?></textarea></td>
            </tr>
            <tr>
                <th>Изображение</th>
                <td>
                    <input type="text" name="hs_deck_manager_announcement_image" id="ann_img" value="<?php echo esc_attr(get_option('hs_deck_manager_announcement_image')); ?>" class="regular-text">
                    <button type="button" class="button" id="ann_img_btn">Выбрать</button>
                    <script>
                    jQuery(document).ready(function($){
                        $('#ann_img_btn').click(function(e){
                            e.preventDefault();
                            var frame = wp.media({title:'Выбрать', multiple:false});
                            frame.on('select', function(){
                                $('#ann_img').val(frame.state().get('selection').first().toJSON().url);
                            });
                            frame.open();
                        });
                    });
                    </script>
                </td>
            </tr>
            <tr>
                <th>Включено</th>
                <td><input type="checkbox" name="hs_deck_manager_announcement_enabled" value="1" <?php checked(1, get_option('hs_deck_manager_announcement_enabled')); ?>></td>
            </tr>
        </table>
        <?php
        submit_button();
        echo '</form></div>';
    }

    /**
     * Render Stats Page — Dashboard with charts, top decks, activity by class/mode.
     */
    public function render_stats_page() {
        $stats = get_transient( 'hs_deck_manager_global_stats' );
        if ( false === $stats ) {
            global $wpdb;
            $posts_table = $wpdb->posts;
            $meta_table  = $wpdb->postmeta;
            $stats = array( 'views' => 0, 'copies' => 0, 'clicks' => 0 );
            foreach ( array( '_deck_views' => 'views', '_deck_copies' => 'copies', '_deck_clicks' => 'clicks' ) as $meta_key => $key ) {
                $sum = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COALESCE(SUM(CAST(m.meta_value AS UNSIGNED)), 0) FROM {$meta_table} m INNER JOIN {$posts_table} p ON p.ID = m.post_id WHERE p.post_type = 'hs_deck' AND p.post_status = 'publish' AND m.meta_key = %s",
                    $meta_key
                ) );
                $stats[ $key ] = (int) $sum;
            }
            set_transient( 'hs_deck_manager_global_stats', $stats, HOUR_IN_SECONDS );
        }

        $period = isset( $_GET['period'] ) ? (int) $_GET['period'] : 30;
        if ( ! in_array( $period, array( 7, 30, 90 ), true ) ) {
            $period = 30;
        }
        $daily   = $this->get_daily_stats_for_period( $period );
        $top     = $this->get_top_decks( 10 );
        $by_class = $this->get_stats_by_taxonomy( 'deck_class' );
        $by_mode  = $this->get_stats_by_taxonomy( 'deck_mode' );

        $chart_labels = array_map( function ( $d ) {
            return date_i18n( 'd.m', strtotime( $d ) );
        }, $daily['labels'] );

        $dashboard_data = array(
            'labels'   => $chart_labels,
            'views'   => $daily['views'],
            'copies'  => $daily['copies'],
            'clicks'  => $daily['clicks'],
        );
        ?>
        <div class="wrap hs-deck-dashboard">
            <h1><?php esc_html_e( 'Дашборд', 'hs_deck_manager' ); ?></h1>

            <div class="hs-dashboard-cards" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin: 20px 0;">
                <div class="card" style="padding: 20px; border-left: 4px solid #2271b1;">
                    <h2 style="margin: 0 0 4px;"><?php echo number_format_i18n( $stats['views'] ); ?></h2>
                    <p style="margin: 0; color: #646970;"><?php esc_html_e( 'Просмотры', 'hs_deck_manager' ); ?></p>
                </div>
                <div class="card" style="padding: 20px; border-left: 4px solid #00a32a;">
                    <h2 style="margin: 0 0 4px;"><?php echo number_format_i18n( $stats['copies'] ); ?></h2>
                    <p style="margin: 0; color: #646970;"><?php esc_html_e( 'Копии кода', 'hs_deck_manager' ); ?></p>
                </div>
                <div class="card" style="padding: 20px; border-left: 4px solid #d63638;">
                    <h2 style="margin: 0 0 4px;"><?php echo number_format_i18n( $stats['clicks'] ); ?></h2>
                    <p style="margin: 0; color: #646970;"><?php esc_html_e( 'Клики', 'hs_deck_manager' ); ?></p>
                </div>
            </div>

            <div class="hs-dashboard-section" style="margin: 24px 0;">
                <h2 style="margin-bottom: 12px;"><?php esc_html_e( 'Активность за период', 'hs_deck_manager' ); ?></h2>
                <p style="margin-bottom: 12px;">
                    <a href="<?php echo esc_url( add_query_arg( 'period', 7 ) ); ?>" class="button <?php echo $period === 7 ? 'button-primary' : ''; ?>">7 <?php esc_html_e( 'дней', 'hs_deck_manager' ); ?></a>
                    <a href="<?php echo esc_url( add_query_arg( 'period', 30 ) ); ?>" class="button <?php echo $period === 30 ? 'button-primary' : ''; ?>">30 <?php esc_html_e( 'дней', 'hs_deck_manager' ); ?></a>
                    <a href="<?php echo esc_url( add_query_arg( 'period', 90 ) ); ?>" class="button <?php echo $period === 90 ? 'button-primary' : ''; ?>">90 <?php esc_html_e( 'дней', 'hs_deck_manager' ); ?></a>
                </p>
                <div style="max-width: 900px; height: 280px;">
                    <canvas id="hs-deck-chart" role="img" aria-label="<?php esc_attr_e( 'График просмотров, копий и кликов по дням', 'hs_deck_manager' ); ?>"></canvas>
                </div>
            </div>

            <div class="hs-dashboard-section" style="margin: 24px 0;">
                <h2 style="margin-bottom: 12px;"><?php esc_html_e( 'Топ колод по копиям', 'hs_deck_manager' ); ?></h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Колода', 'hs_deck_manager' ); ?></th>
                            <th><?php esc_html_e( 'Просмотры', 'hs_deck_manager' ); ?></th>
                            <th><?php esc_html_e( 'Копии', 'hs_deck_manager' ); ?></th>
                            <th><?php esc_html_e( 'Клики', 'hs_deck_manager' ); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( empty( $top ) ) : ?>
                            <tr><td colspan="5"><?php esc_html_e( 'Нет данных', 'hs_deck_manager' ); ?></td></tr>
                        <?php else : ?>
                            <?php foreach ( $top as $row ) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $row['title'] ); ?></strong></td>
                                    <td><?php echo number_format_i18n( $row['views'] ); ?></td>
                                    <td><?php echo number_format_i18n( $row['copies'] ); ?></td>
                                    <td><?php echo number_format_i18n( $row['clicks'] ); ?></td>
                                    <td><?php if ( $row['edit'] ) { ?><a href="<?php echo esc_url( $row['edit'] ); ?>"><?php esc_html_e( 'Изменить', 'hs_deck_manager' ); ?></a><?php } ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div class="hs-dashboard-section">
                    <h2 style="margin-bottom: 12px;"><?php esc_html_e( 'По классам', 'hs_deck_manager' ); ?></h2>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Класс', 'hs_deck_manager' ); ?></th>
                                <th><?php esc_html_e( 'Колод', 'hs_deck_manager' ); ?></th>
                                <th><?php esc_html_e( 'Просмотры', 'hs_deck_manager' ); ?></th>
                                <th><?php esc_html_e( 'Копии', 'hs_deck_manager' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $by_class as $row ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $row['name'] ); ?></td>
                                    <td><?php echo number_format_i18n( $row['count'] ); ?></td>
                                    <td><?php echo number_format_i18n( $row['views'] ); ?></td>
                                    <td><?php echo number_format_i18n( $row['copies'] ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ( empty( $by_class ) ) : ?>
                                <tr><td colspan="4"><?php esc_html_e( 'Нет данных', 'hs_deck_manager' ); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="hs-dashboard-section">
                    <h2 style="margin-bottom: 12px;"><?php esc_html_e( 'По режимам', 'hs_deck_manager' ); ?></h2>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Режим', 'hs_deck_manager' ); ?></th>
                                <th><?php esc_html_e( 'Колод', 'hs_deck_manager' ); ?></th>
                                <th><?php esc_html_e( 'Просмотры', 'hs_deck_manager' ); ?></th>
                                <th><?php esc_html_e( 'Копии', 'hs_deck_manager' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $by_mode as $row ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $row['name'] ); ?></td>
                                    <td><?php echo number_format_i18n( $row['count'] ); ?></td>
                                    <td><?php echo number_format_i18n( $row['views'] ); ?></td>
                                    <td><?php echo number_format_i18n( $row['copies'] ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ( empty( $by_mode ) ) : ?>
                                <tr><td colspan="4"><?php esc_html_e( 'Нет данных', 'hs_deck_manager' ); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="description" style="margin-top: 24px;"><?php esc_html_e( 'Суммарные показатели кэшируются на 1 час. График по дням строится из накопленной статистики с момента включения записи.', 'hs_deck_manager' ); ?></p>
        </div>

        <script>
        ( function() {
            var data = <?php echo wp_json_encode( $dashboard_data ); ?>;
            var el = document.getElementById( 'hs-deck-chart' );
            if ( ! el || typeof Chart === 'undefined' ) return;
            new Chart( el, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        { label: '<?php echo esc_js( __( 'Просмотры', 'hs_deck_manager' ) ); ?>', data: data.views, borderColor: '#2271b1', backgroundColor: 'rgba(34, 113, 177, 0.1)', fill: true, tension: 0.2 },
                        { label: '<?php echo esc_js( __( 'Копии', 'hs_deck_manager' ) ); ?>', data: data.copies, borderColor: '#00a32a', backgroundColor: 'rgba(0, 163, 42, 0.1)', fill: true, tension: 0.2 },
                        { label: '<?php echo esc_js( __( 'Клики', 'hs_deck_manager' ) ); ?>', data: data.clicks, borderColor: '#d63638', backgroundColor: 'rgba(214, 54, 56, 0.1)', fill: true, tension: 0.2 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top' } },
                    scales: { y: { beginAtZero: true } }
                }
            } );
        } )();
        </script>
        <?php
    }

    /**
     * Shortcode: Single Deck
     */
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

        $per_page = max( 1, (int) ( $atts['per_page'] ? $atts['per_page'] : $atts['posts_per_page'] ) );
        $columns  = max( 1, min( 6, (int) $atts['columns'] ) );
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
                'relation' => 'OR',
                array( 'key' => '_hide_from_feed', 'value' => '1', 'compare' => '!=' ),
                array( 'key' => '_hide_from_feed', 'compare' => 'NOT EXISTS' ),
            );
        }

        $query = new WP_Query( $args );

        $plugin_path = plugin_dir_path( __FILE__ );
        if ( file_exists( $plugin_path . 'build/feed.js' ) ) {
            wp_enqueue_script( 'hs-deck-feed' );
        }

        $ads_config = json_decode( get_option( 'hs_deck_manager_ads_config', '[]' ), true );
        $ad_start   = (int) get_option( 'hs_deck_manager_ad_start', 3 );
        $ad_repeat  = (int) get_option( 'hs_deck_manager_ad_repeat', 7 );
        $ad_enabled = get_option( 'hs_deck_manager_ad_enabled' );

        $class_terms = get_terms( array( 'taxonomy' => 'deck_class', 'hide_empty' => true ) );
        $mode_terms  = get_terms( array( 'taxonomy' => 'deck_mode', 'hide_empty' => true ) );

        $total_pages   = $single_id ? 1 : (int) $query->max_num_pages;
        $current_page  = $single_id ? 1 : ( isset( $_GET['hs_page'] ) ? max( 1, (int) $_GET['hs_page'] ) : 1 );
        $base_url      = get_permalink();
        $pagination_base = $base_url;
        if ( $order_param !== 'date' ) {
            $pagination_base = add_query_arg( 'hs_order', $order_param, $pagination_base );
        }
        $page_links = '';
        if ( ! $single_id && $total_pages > 1 ) {
            $page_links = paginate_links( array(
                'base'      => add_query_arg( 'hs_page', '%#%', $pagination_base ),
                'format'   => '',
                'current'  => $current_page,
                'total'    => $total_pages,
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'type'     => 'list',
            ) );
        }
        $sort_url_date   = remove_query_arg( array( 'hs_page', 'hs_order' ), $base_url );
        $sort_url_dust   = add_query_arg( 'hs_order', 'dust', remove_query_arg( 'hs_page', $base_url ) );
        $sort_url_popular = add_query_arg( 'hs_order', 'popular', remove_query_arg( 'hs_page', $base_url ) );

        ob_start();
        ?>
        <style>.hs-sort-btn:hover{ opacity: 0.9; cursor: pointer; }.hs-copy-code-btn:active{ transform: scale(0.98); }.hs-copy-code-btn.hs-copy-done{ background-color: #00a32a !important; }</style>
        <div id="<?php echo esc_attr( $wrapper_id ); ?>" class="hs-wrapper">
            <div class="hs-filters" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <input type="text" id="<?php echo esc_attr( $wrapper_id ); ?>-search" placeholder="Поиск..." class="hs-search" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; flex: 1; min-width: 120px;">
                <select id="<?php echo esc_attr( $wrapper_id ); ?>-class" class="hs-filter-class" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">Все классы</option>
                    <?php if ( ! is_wp_error( $class_terms ) ) { foreach ( $class_terms as $term ) { ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
                    <?php } } ?>
                </select>
                <select id="<?php echo esc_attr( $wrapper_id ); ?>-mode" class="hs-filter-mode" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">Все режимы</option>
                    <?php if ( ! is_wp_error( $mode_terms ) ) { foreach ( $mode_terms as $term ) { ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
                    <?php } } ?>
                </select>
                <?php if ( ! $single_id ) { ?>
                <span style="white-space: nowrap;">Сортировка:</span>
                <div class="hs-sort" style="display: flex; gap: 4px; flex-wrap: wrap;">
                    <a href="<?php echo esc_url( $sort_url_date ); ?>" class="hs-sort-btn <?php echo $order_param === 'date' ? 'hs-sort-active' : ''; ?>" style="padding: 6px 10px; border-radius: 4px; text-decoration: none; border: 1px solid #ddd; background: <?php echo $order_param === 'date' ? '#2271b1' : '#fff'; ?>; color: <?php echo $order_param === 'date' ? '#fff' : '#333'; ?>;">Сначала новые</a>
                    <a href="<?php echo esc_url( $sort_url_dust ); ?>" class="hs-sort-btn <?php echo $order_param === 'dust' ? 'hs-sort-active' : ''; ?>" style="padding: 6px 10px; border-radius: 4px; text-decoration: none; border: 1px solid #ddd; background: <?php echo $order_param === 'dust' ? '#2271b1' : '#fff'; ?>; color: <?php echo $order_param === 'dust' ? '#fff' : '#333'; ?>;">По пыли</a>
                    <a href="<?php echo esc_url( $sort_url_popular ); ?>" class="hs-sort-btn <?php echo $order_param === 'popular' ? 'hs-sort-active' : ''; ?>" style="padding: 6px 10px; border-radius: 4px; text-decoration: none; border: 1px solid #ddd; background: <?php echo $order_param === 'popular' ? '#2271b1' : '#fff'; ?>; color: <?php echo $order_param === 'popular' ? '#fff' : '#333'; ?>;">По популярности</a>
                </div>
                <?php } ?>
            </div>

            <div id="<?php echo esc_attr( $wrapper_id ); ?>-grid" class="hs-deck-feed" style="display: grid; grid-template-columns: repeat(<?php echo (int) $columns; ?>, 1fr); gap: 20px;">
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
                    echo '<p class="hs-no-decks">Колоды не найдены.</p>';
                }
                ?>
            </div>

            <?php if ( $page_links ) { ?>
                <nav class="hs-deck-pagination" style="margin-top: 24px;" aria-label="Навигация по страницам колод">
                    <?php echo $page_links; ?>
                </nav>
            <?php } ?>

            <div class="hs-lightbox" role="dialog" aria-modal="true" aria-label="Изображение в полном размере" style="display: none; position: fixed; inset: 0; z-index: 100000; background: rgba(0,0,0,0.9); align-items: center; justify-content: center; cursor: pointer;">
                <img src="" alt="" style="max-width: 95%; max-height: 95%; object-fit: contain; pointer-events: none;">
            </div>
        </div>
        <?php
        if ( ! file_exists( $plugin_path . 'build/feed.js' ) ) {
            $this->render_feed_inline_script( $wrapper_id );
        }
        return ob_get_clean();
    }

    /**
     * Render Helper: Deck Card
     * Win rate with one decimal; wins/losses below copy button; full-size image with click-to-fullscreen.
     */
    private function render_deck_card( $post_id ) {
        $title  = get_the_title( $post_id );
        $image  = get_the_post_thumbnail_url( $post_id, 'large' );
        $image_full = get_the_post_thumbnail_url( $post_id, 'full' );
        if ( ! $image_full ) {
            $image_full = $image;
        }
        $dust   = get_post_meta( $post_id, '_dust_cost', true );
        $code   = get_post_meta( $post_id, '_deck_code', true );
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
        <div class="hs-card" data-class="<?php echo esc_attr( $class_str ); ?>" data-mode="<?php echo esc_attr( $mode_str ); ?>" style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #fff; transition: transform 0.2s;">
            <?php if ( $image ) { ?>
            <div class="hs-deck-img-wrap" style="cursor: pointer; line-height: 0;" role="button" tabindex="0" data-hs-full="<?php echo esc_url( $image_full ); ?>" title="Открыть в полном размере">
                <img src="<?php echo esc_url( $image ); ?>" loading="lazy" decoding="async" alt="<?php echo esc_attr( $title ); ?>" style="width: 100%; height: auto; max-height: 320px; object-fit: contain; display: block;">
            </div>
            <?php } else { ?>
            <div style="width: 100%; height: 200px; background: #f0f0f1; display: flex; align-items: center; justify-content: center; color: #666;">Нет изображения</div>
            <?php } ?>
            <div style="padding: 15px;">
                <h3 style="margin: 0 0 10px; font-size: 1.1em;"><?php echo esc_html( $title ); ?></h3>
                <div style="font-size: 0.85em; color: #666; display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span><span class="dashicons dashicons-hammer"></span> <?php echo esc_html( $dust ); ?></span>
                    <span><span class="dashicons dashicons-chart-bar"></span> <?php echo esc_html( $winrate_str ); ?></span>
                </div>
                <button type="button" class="hs-copy-code-btn" data-code="<?php echo esc_attr( $code ); ?>" data-label="Копировать код" data-done="Скопировано" style="width: 100%; padding: 8px; background: #2271b1; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: 500; transition: transform 0.15s ease, background-color 0.2s ease;">Копировать код</button>
                <p class="hs-deck-wins-losses" style="margin: 10px 0 0; font-size: 0.85em; color: #666; text-align: center;">
                    <?php echo (int) $wins; ?> побед / <?php echo (int) $losses; ?> поражений<?php if ( $total > 0 ) { ?> · всего <?php echo (int) $total; ?> игр<?php } ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Render Helper: Ad Card
     */
    private function render_ad_card( $ad ) {
        if(empty($ad)) return;
        ?>
        <div class="hs-ad-card" style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #f9f9f9; padding: 15px; text-align: center;">
            <?php if(!empty($ad['image'])): ?>
                <img src="<?php echo esc_url($ad['image']); ?>" style="max-width:100%; height: auto; margin-bottom: 10px;">
            <?php endif; ?>
            <div style="margin-bottom: 10px;"><?php echo wp_kses_post($ad['content']); ?></div>
            <?php if(!empty($ad['btnUrl'])): ?>
                <a href="<?php echo esc_url($ad['btnUrl']); ?>" class="button" style="display: inline-block; padding: 8px 16px; background: #d63638; color: #fff; text-decoration: none; border-radius: 4px;"><?php echo esc_html($ad['btnText']); ?></a>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX Track Event
     */
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
