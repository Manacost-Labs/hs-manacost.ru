<?php
/**
 * Reduce AIOSEO background pressure on wp-admin.
 *
 * The image sitemap scanner defaults to 10 posts every minute, which creates
 * constant Action Scheduler/admin-ajax churn on large archives. Keep roughly
 * the same hourly throughput with fewer runs.
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
