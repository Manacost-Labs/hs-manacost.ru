<?php
/**
 * Temporary, staging-only phase timing for an authenticated article save.
 * Install as a must-use plugin for a controlled diagnostic run, then remove it.
 */

if ( ! defined( 'ABSPATH' ) || wp_get_environment_type() !== 'staging' ) {
	return;
}

if ( ( $_SERVER['HTTP_HOST'] ?? '' ) !== 'test.hs-manacost.ru'
	|| ( $_SERVER['REQUEST_METHOD'] ?? '' ) !== 'POST'
	|| ! str_starts_with( $_SERVER['REQUEST_URI'] ?? '', '/wp-admin/post.php' )
	|| ( $_SERVER['HTTP_X_HS_ADMIN_PERF_PROBE'] ?? '' ) !== '1'
) {
	return;
}

$hs_perf_start = hrtime( true );
$hs_perf_points = array();

foreach ( array( 'admin_init', 'load-post.php', 'pre_post_update', 'post_updated', 'save_post', 'wp_after_insert_post' ) as $hs_perf_hook ) {
	add_filter(
		$hs_perf_hook,
		static function ( $value = null ) use ( $hs_perf_hook, $hs_perf_start, &$hs_perf_points ) {
			if ( current_user_can( 'manage_options' ) ) {
				$hs_perf_points[ $hs_perf_hook . '_start' ] = (int) round( ( hrtime( true ) - $hs_perf_start ) / 1000000 );
			}
			return $value;
		},
		-1000000
	);
	add_filter(
		$hs_perf_hook,
		static function ( $value = null ) use ( $hs_perf_hook, $hs_perf_start, &$hs_perf_points ) {
			if ( current_user_can( 'manage_options' ) ) {
				$hs_perf_points[ $hs_perf_hook . '_end' ] = (int) round( ( hrtime( true ) - $hs_perf_start ) / 1000000 );
			}
			return $value;
		},
		1000000
	);
}

add_filter(
	'wp_redirect',
	static function ( $location ) use ( $hs_perf_start, &$hs_perf_points ) {
		if ( current_user_can( 'manage_options' ) && ! headers_sent() ) {
			$hs_perf_points['redirect'] = (int) round( ( hrtime( true ) - $hs_perf_start ) / 1000000 );
			header( 'X-HS-Perf-Phases: ' . wp_json_encode( $hs_perf_points ) );
		}
		return $location;
	},
	10000000
);
