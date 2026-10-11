<?php
/**
 * Plugin Name: Manacost Smart Sidebar
 * Description: Keeps Newspaper's public sidebar stable when scroll direction changes.
 * Version: 1.1.0
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Enables the native markup and scopes stable positioning to classic sidebars. */
final class Manacost_Smart_Sidebar {
	/** Loads positioning and keyboard support after the native library. */
	public static function enqueue_styles(): void {
		if ( is_admin() || 'Newspaper_new' !== get_template() ) {
			return;
		}

		wp_enqueue_style(
			'manacost-smart-sidebar',
			plugin_dir_url( __FILE__ ) . 'manacost-smart-sidebar.css',
			array(),
			'1.1.0'
		);

		wp_enqueue_script(
			'manacost-smart-sidebar',
			plugin_dir_url( __FILE__ ) . 'manacost-smart-sidebar.js',
			array( 'tdSmartSidebar' ),
			'1.1.0',
			true
		);
	}

	/**
	 * Enables native asset loading and initialization through the same setting.
	 *
	 * Newspaper reads td_011 before the main query, so register this as an MU
	 * plugin rather than waiting for conditional tags or the enqueue hook.
	 *
	 * @param mixed $settings Stored Newspaper options.
	 * @return mixed Public options or the untouched value outside Newspaper.
	 */
	public static function enable_public_sidebar( $settings ) {
		if ( is_admin() || 'Newspaper_new' !== get_template() || ! is_array( $settings ) ) {
			return $settings;
		}

		$settings['tds_smart_sidebar'] = 'enabled';

		return $settings;
	}
}

add_filter( 'option_td_011', array( Manacost_Smart_Sidebar::class, 'enable_public_sidebar' ) );
add_action( 'wp_enqueue_scripts', array( Manacost_Smart_Sidebar::class, 'enqueue_styles' ), 20 );
