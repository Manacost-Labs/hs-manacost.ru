<?php
/**
 * Plugin Name: Manacost Mobile Layout
 * Description: Applies update-safe mobile presentation fixes to public Newspaper surfaces.
 * Version: 1.0.5
 * Author: Manacost
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Loads the project-owned responsive stylesheet on eligible public pages. */
final class Manacost_Mobile_Layout {
	/** Registers the public stylesheet at the normal frontend asset hook. */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ), 25 );
	}

	/** Enqueues no layout assets for admin, feed, preview, robots or trackback requests. */
	public static function enqueue_styles(): void {
		if ( is_admin() || is_feed() || is_preview() || is_robots() || is_trackback() ) {
			return;
		}

		wp_enqueue_style(
			'manacost-mobile-layout',
			plugin_dir_url( __FILE__ ) . 'manacost-mobile-layout/mobile-layout.css',
			array(),
			'1.0.5'
		);
	}
}

Manacost_Mobile_Layout::boot();
