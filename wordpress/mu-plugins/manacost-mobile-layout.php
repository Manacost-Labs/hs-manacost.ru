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
		add_action( 'wp_head', array( __CLASS__, 'render_critical_styles' ), 999 );
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
			'1.0.8'
		);
	}

	/** Prints cache-independent mobile overrides after theme and optimizer styles. */
	public static function render_critical_styles(): void {
		if ( is_admin() || is_feed() || is_preview() || is_robots() || is_trackback() ) {
			return;
		}
		?>
		<style id="manacost-mobile-layout-critical">
			@media (max-width: 767px) {
				body .td-header-wrap .td-header-menu-wrap-full,
				body .td-header-wrap .td-header-menu-wrap,
				body .td-header-wrap .td-header-main-menu,
				#td-top-mobile-toggle a,
				#td-header-search-button-mob { block-size: 62px !important; }

				body .td-header-wrap .td-mobile-logo img { block-size: 56px !important; max-block-size: 56px !important; }

				body.single-post .td-post-template-3 .td-post-title .td-post-sub-title {
					letter-spacing: 0 !important;
					word-spacing: 0 !important;
					text-wrap: wrap !important;
				}
			}
		</style>
		<?php
	}
}

Manacost_Mobile_Layout::boot();
