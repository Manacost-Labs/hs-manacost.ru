<?php
/**
 * Plugin Name: Separator & Placeholder Quick Insert
 * Description: Быстрая вставка разделителей и заглушек прямо из редактора. Управление наборами в Инструменты → Разделители и заглушки.
 * Version:     0.2.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:      Zulut
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: spqi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SPQI_VERSION', '0.2.0' );
define( 'SPQI_FILE', __FILE__ );
define( 'SPQI_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPQI_URL', plugin_dir_url( __FILE__ ) );

require_once SPQI_DIR . 'includes/class-spqi-store.php';
require_once SPQI_DIR . 'includes/class-spqi-admin.php';
require_once SPQI_DIR . 'includes/class-spqi-rest.php';
require_once SPQI_DIR . 'includes/class-spqi-assets.php';

add_action(
	'plugins_loaded',
	static function () {
		load_plugin_textdomain( 'spqi', false, dirname( plugin_basename( SPQI_FILE ) ) . '/languages' );
		( new SPQI_Admin() )->register();
		( new SPQI_REST() )->register();
		( new SPQI_Assets() )->register();
	}
);
