<?php
/*
Plugin Name: Cackle
Plugin URI: http://cackle.me
Description: This plugin allows your website's audience communicate through social networks like Facebook, Vkontakte, Twitter, e.t.c.
Version: 4.33
Author: Cackle
Author URI: http://cackle.me
Text Domain: cackle
Domain Path: /locales
*/

define('CACKLE_VERSION', '4.28');
require_once(dirname(__FILE__) . '/cackle_api.php');
require_once(dirname(__FILE__) . '/sync.php');
require_once(dirname(__FILE__) . '/channel.php');
require_once(dirname(__FILE__) . '/monitor.php');
function cackle_manage() {
    include_once (dirname(__FILE__) . '/manage.php');
}

function cackle_admin() {
    include_once (dirname(__FILE__) . '/cackle_admin.php');
}

$cackle_api = new CackleAPI();
require_once(dirname(__FILE__) . '/sync_handler.php');

class cackle {

    function __construct() {
        add_filter('comments_template', array($this, 'comments_template'),1000);
        add_filter('comments_number', array($this, 'comments_text'));
        add_action('admin_menu', array($this, 'cackle_add_pages'), 1);
        add_action('admin_menu', array($this, 'cackle_add_pages2'), 1);

    }

    function cackle_add_pages() {
        global $submenu;
        unset($submenu["edit-comments.php"][0]);
        add_submenu_page('edit-comments.php', 'Cackle', __('Cackle moderate', 'cackle'), 'moderate_comments', 'cackle', 'cackle_manage');
        $submenu["edit-comments.php"][0]=$submenu["edit-comments.php"][1];
        unset($submenu["edit-comments.php"][1]);
    }
    function cackle_add_pages2() {
        add_submenu_page('edit-comments.php', 'Cackle settings', __('Cackle settings', 'cackle'), 'moderate_comments', 'cackle_settings', 'cackle_admin');
    }
    function comments_text($comment_text) {
        global $post;
        return '<span class="cackle-postid" id="c' . htmlspecialchars($this->identifier_for_post($post)) . '">' . $comment_text . '</span>';
    }

    function identifier_for_post($post) {
        return $post->ID;
    }

    function comments_template() {
        global $wpdb;
        $this->sync_handler(); //uncomment for debug without 5min delay
        if (cackle_enabled()) {
            return dirname(__FILE__) . '/comment-template.php';
        }
    }

    function sync_handler() {

        SyncHandler::init();
    }

}

//Init cackle module
function cackle_init() {
    $a = new cackle;
}
add_action('init', 'cackle_init');

function lang_init() {
    $plugin_dir = basename(dirname(__FILE__));
    load_plugin_textdomain( 'cackle', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action('plugins_loaded', 'lang_init');

//Init adding counter widget
function cackle_output_footer_comment_js() {
    require_once(dirname(__FILE__) . '/counter.php');
    CackleCounter::init();
}
add_action('wp_footer', 'cackle_output_footer_comment_js');

//Init request handler
function cackle_request_handler() {
    global $wpdb;
    if (!empty($_POST)&& !isset($_POST["interval"])) {
        require_once(dirname(__FILE__) . '/request_handler.php');
    }
}
add_action('init', 'cackle_request_handler');

//Activation hooks
require_once(dirname(__FILE__) . '/cackle_activate.php');
register_activation_hook( __FILE__, 'cackle_activate' );