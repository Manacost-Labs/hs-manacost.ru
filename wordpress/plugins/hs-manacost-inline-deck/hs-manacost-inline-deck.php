<?php
/**
 * Plugin Name: Manacost: inline deck images
 * Description: Создаёт изображение колоды через Kolodahs, сохраняет его в медиатеку и привязывает к выделенной фразе в Classic Editor.
 * Version: 1.0.1
 * Author: Manacost
 * Requires at least: 6.4
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HS_MANACOST_INLINE_DECK_VERSION', '1.0.1');
define('HS_MANACOST_INLINE_DECK_FILE', __FILE__);
define('HS_MANACOST_INLINE_DECK_URL', plugin_dir_url(__FILE__));
define('HS_MANACOST_INLINE_DECK_PATH', plugin_dir_path(__FILE__));

require_once HS_MANACOST_INLINE_DECK_PATH . 'includes/class-hs-manacost-inline-deck.php';

final class HS_Manacost_Inline_Deck_Plugin {
    private $renderer;
    private $frontend_assets_enqueued = false;

    public function __construct() {
        $this->renderer = new HS_Manacost_Inline_Deck(HS_MANACOST_INLINE_DECK_FILE);

        add_action('admin_init', array($this, 'register_editor_integration'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_editor_config'));
        add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_frontend_assets'), 20);

        add_filter('rocket_delay_js_exclusions', array($this, 'exclude_from_delayed_javascript'));
        add_filter('perfmatters_delay_js_exclusions', array($this, 'exclude_from_delayed_javascript'));
    }

    public function register_editor_integration() {
        if (!current_user_can('edit_posts') || !user_can_richedit()) {
            return;
        }

        // Newspaper, Advanced Editor Tools and Ad Inserter all modify these
        // filters. A deliberately late priority keeps our button available.
        add_filter('mce_external_plugins', array($this, 'add_tinymce_plugin'), 100000);
        add_filter('mce_buttons_2', array($this, 'add_tinymce_button'), 100000, 2);
    }

    public function add_tinymce_plugin($plugins) {
        if (!is_array($plugins)) {
            $plugins = array();
        }

        $plugins['hs_manacost_inline_deck'] =
            HS_MANACOST_INLINE_DECK_URL . 'assets/admin.js?ver=' . rawurlencode(HS_MANACOST_INLINE_DECK_VERSION);

        return $plugins;
    }

    public function add_tinymce_button($buttons, $editor_id = '') {
        if ($editor_id !== '' && $editor_id !== 'content') {
            return $buttons;
        }

        if (!is_array($buttons)) {
            $buttons = array();
        }

        if (!in_array('hs_manacost_inline_deck', $buttons, true)) {
            $buttons[] = 'hs_manacost_inline_deck';
        }

        return $buttons;
    }

    public function enqueue_editor_config($hook_suffix) {
        if (!in_array($hook_suffix, array('post.php', 'post-new.php'), true) || !current_user_can('edit_posts')) {
            return;
        }

        $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
        $config = array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hs_manacost_inline_deck_nonce'),
            'postId' => $post_id,
            'startAction' => 'hs_manacost_inline_deck_start',
            'finishAction' => 'hs_manacost_inline_deck_finish',
        );

        wp_enqueue_script('jquery');
        wp_add_inline_script(
            'jquery-core',
            'window.hsManacostInlineDeck=' . wp_json_encode($config) . ';',
            'after'
        );
    }

    public function maybe_enqueue_frontend_assets() {
        if (!is_singular()) {
            return;
        }

        $post = get_queried_object();
        if (!$post instanceof WP_Post || !has_shortcode((string) $post->post_content, 'hs_deck_link')) {
            return;
        }

        $this->enqueue_frontend_assets();
    }

    public function enqueue_frontend_assets() {
        if ($this->frontend_assets_enqueued) {
            return;
        }

        $this->frontend_assets_enqueued = true;
        wp_enqueue_style(
            'hs-manacost-inline-deck',
            HS_MANACOST_INLINE_DECK_URL . 'assets/frontend.css',
            array(),
            HS_MANACOST_INLINE_DECK_VERSION
        );
        wp_enqueue_script(
            'hs-manacost-inline-deck',
            HS_MANACOST_INLINE_DECK_URL . 'assets/frontend.js',
            array(),
            HS_MANACOST_INLINE_DECK_VERSION,
            true
        );
        wp_script_add_data('hs-manacost-inline-deck', 'strategy', 'defer');
    }

    public function exclude_from_delayed_javascript($excluded) {
        if (!is_array($excluded)) {
            $excluded = array();
        }

        $needles = array(
            'hs-manacost-inline-deck',
            '/assets/frontend.js',
        );

        foreach ($needles as $needle) {
            if (!in_array($needle, $excluded, true)) {
                $excluded[] = $needle;
            }
        }

        return $excluded;
    }
}

new HS_Manacost_Inline_Deck_Plugin();
