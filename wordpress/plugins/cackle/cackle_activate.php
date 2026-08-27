<?php

function cackle_activate() {
    cackle_install();
}

function cackle_enabled() {
    if (get_option('cackle_apiId') && get_option('cackle_siteApiKey') && get_option('cackle_accountApiKey')) {
        return true;
    }
}



function cackle_activated() {
    if (!empty($_POST['api_id']) && isset($_POST['site_api_key']) && strlen($_POST['site_api_key']) == 64 && isset($_POST['account_api_key']) && strlen($_POST['account_api_key']) == 64) {
        return true;
    }
}

function cackle_install() {
    //print_r('test');die();
    global $wpdb;
    $table_name = $wpdb->prefix . "cackle_channel";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

        $sql = "CREATE TABLE " . $table_name . " (
	            id bigint(20) NOT NULL,
	            time bigint(11) NOT NULL,
	            modified varchar(25) DEFAULT NULL,
	            last_comment varchar(250) DEFAULT NULL,
                PRIMARY KEY (id),
	            UNIQUE KEY id (id)
	        );";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $wpdb->query($sql);

    }
    $wpdb->query("alter table $table_name modify id bigint(20) NOT NULL");
    //Delete Cackle comments for resync
    //$wpdb->query("DELETE FROM `" . $wpdb->prefix . "commentmeta` WHERE meta_key IN ('cackle_post_id', 'cackle_parent_post_id')");
    //$wpdb->query("DELETE FROM `" . $wpdb->prefix . "comments` WHERE comment_agent LIKE 'Cackle:%%'");
    //$wpdb->query("DELETE FROM `" . $wpdb->prefix . "cackle_channel`");
    //delete_option("cackle_monitor");
    //delete_option("cackle_monitor_short");
    //delete_option("cackle_modified_trigger");
    update_option("cackle_plugin_version", CACKLE_VERSION);

}
function cackle_plugin_is_current_version(){
    $version = get_option( 'cackle_plugin_version','4.07');
    return version_compare($version, CACKLE_VERSION, '=') ? true : false;
}
if ( !cackle_plugin_is_current_version() ) cackle_install();

//checking activation errors
//add_action('activated_plugin', 'cackle_plugin_activation_error');
//function cackle_plugin_activation_error() {
//    file_put_contents( plugin_dir_path(__FILE__) . '/error_activation.html', ob_get_contents());
//}
?>