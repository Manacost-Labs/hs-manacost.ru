<?php
/**
 * Plugin Name: Shortcodes Ultimate: Additional Skins
 * Plugin URI: https://getshortcodes.com/add-ons/additional-skins/
 * Version: 1.6.1
 * Author: Vladimir Anokhin
 * Author URI: https://vanokhin.com/
 * Description: Set of additional skins for Shortcodes Ultimate
 * Text Domain: shortcodes-ultimate-skins
 * Domain Path: /languages
 * License: license.txt
 */

/**
 * Begins execution of the plugin.
 *
 * @since 1.5.1
 */
function run_shortcodes_ultimate_skins() {

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-shortcodes-ultimate-skins.php';

	$plugin = new Shortcodes_Ultimate_Skins( __FILE__, '1.6.1' );

	do_action( 'su/skins/ready', $plugin );

}

add_action( 'plugins_loaded', 'run_shortcodes_ultimate_skins' );

/**
 * Run plugin updater.
 *
 * @since  1.5.5
 */
function update_shortcodes_ultimate_skins() {

	require plugin_dir_path( __FILE__ ) . 'vendor/kernl-update-checker/kernl-update-checker.php';

	$update_checker = Puc_v4_Factory::buildUpdateChecker(
		'https://kernl.us/api/v1/updates/59b6733fc876d804d124ec95/',
		__FILE__,
		'shortcodes-ultimate-skins',
		24
	);

}

update_shortcodes_ultimate_skins();

/**
 * The code that runs during plugin activation.
 *
 * @since  1.5.1
 */
function activate_shortcodes_ultimate_skins() {

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-shortcodes-ultimate-skins-activator.php';

	Shortcodes_Ultimate_Skins_Activator::activate();

}

register_activation_hook( __FILE__, 'activate_shortcodes_ultimate_skins' );

/**
 * The code that runs during plugin deactivation.
 *
 * @since  1.5.1
 */
function deactivate_shortcodes_ultimate_skins() {

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-shortcodes-ultimate-skins-deactivator.php';

	Shortcodes_Ultimate_Skins_Deactivator::deactivate();

}

register_deactivation_hook( __FILE__, 'deactivate_shortcodes_ultimate_skins' );

/**
 * Make plugin meta available for translation via POEdit.
 */
// phpcs:disable
if ( false ) {
	__( 'Vladimir Anokhin', 'shortcodes-ultimate-skins' );
	__( 'Shortcodes Ultimate: Additional Skins', 'shortcodes-ultimate-skins' );
	__( 'Set of additional skins for Shortcodes Ultimate', 'shortcodes-ultimate-skins' );
}
// phpcs:enable
