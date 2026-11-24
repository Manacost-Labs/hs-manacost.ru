<?php
/**
 * Reduce AIOSEO background pressure on wp-admin.
 *
 * The image sitemap scanner defaults to 10 posts every minute, which creates
 * constant Action Scheduler/admin-ajax churn on large archives. Keep roughly
 * the same hourly throughput with fewer runs.
 *
 * @package HS_Manacost
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'aioseo_image_sitemap_scan_interval',
	static function ( $interval ) {
		return max( (int) $interval, 5 * MINUTE_IN_SECONDS );
	}
);

add_filter(
	'aioseo_image_sitemap_posts_per_scan',
	static function ( $posts_per_scan ) {
		return max( (int) $posts_per_scan, 50 );
	}
);

/**
 * Reuse the last completed maintenance pass while an editor navigates between screens.
 * The callbacks below only reconcile site jobs and Search Console notices in 4.9.8.
 * Worker hooks, SEO settings, writes and other versions keep the plugin's normal path.
 */
function hs_admin_aioseo_reuse_maintenance_pass(): void {
	global $pagenow;

	if ( ! function_exists( 'hs_admin_load_trim_remove_object_callback' ) || ! wp_using_ext_object_cache()
		|| ! function_exists( 'aioseo' ) || '4.9.8' !== aioseo()->version
		|| ! isset( $_SERVER['REQUEST_METHOD'] ) || 'GET' !== $_SERVER['REQUEST_METHOD'] || wp_doing_ajax() || wp_doing_cron()
		|| ! in_array( $pagenow, array( 'index.php', 'edit.php', 'post-new.php', 'post.php', 'upload.php' ), true )
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Navigation key names only.
		|| array_diff( array_keys( $_GET ), array( 'action', 'post', 'post_type', 'paged', 's', 'mode', 'author', 'm', 'cat', 'post_status' ) )
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only allow the read-only edit screen.
		|| ( isset( $_GET['action'] ) && 'edit' !== $_GET['action'] ) ) {
		return;
	}

	$key = get_current_user_id() . ':' . determine_locale();
	if ( true !== wp_cache_get( $key, 'hs_aioseo_maintenance' ) ) {
		// Record completion after the native callbacks. A failed request can retry immediately.
		add_action(
			'admin_init',
			static function () use ( $key ) {
				wp_cache_set( $key, true, 'hs_aioseo_maintenance', MINUTE_IN_SECONDS );
			},
			PHP_INT_MAX
		);
		return;
	}

	$callbacks = array(
		'Lite\Admin\Usage'                    => array( 'init' ),
		'Common\Utils\Addons'                 => array( 'scheduleRefresh' ),
		'Common\Utils\Features'               => array( 'scheduleRefresh' ),
		'Lite\Admin\Notices\Notices'          => array( 'scheduleNotificationsUpdate' ),
		'Common\SearchStatistics\Site'        => array( 'init' ),
		'Common\SearchStatistics\Sitemap'     => array( 'init' ),
		'Common\SearchStatistics\Notices'     => array( 'init' ),
		'Common\Ai\Ai'                        => array( 'scheduleGetAccessToken', 'scheduleCreditFetchAction' ),
		'Common\Llms\Llms'                    => array( 'scheduleRecurrentGenerationForLlmsTxt' ),
		'Common\EmailReports\Summary\Summary' => array( 'maybeSchedule' ),
		'Common\Sitemap\Image\Image'          => array( 'scheduleScan' ),
	);
	foreach ( $callbacks as $class => $methods ) {
		foreach ( $methods as $method ) {
			hs_admin_load_trim_remove_object_callback( 'admin_init', 'AIOSEO\\Plugin\\' . $class, $method );
		}
	}
}
add_action( 'admin_init', 'hs_admin_aioseo_reuse_maintenance_pass', 0 );
