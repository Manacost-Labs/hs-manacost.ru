<?php
/**
 * Plugin Name: HS Admin Performance Probe
 * Description: Exposes non-sensitive wp-admin runtime counters on local and staging environments.
 *
 * @package HS_Manacost
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print non-sensitive counters for the authenticated performance harness.
 *
 * @return void
 */
function hs_admin_performance_probe_print_metrics(): void {
	if ( ! in_array( wp_get_environment_type(), array( 'local', 'staging' ), true ) ) {
		return;
	}

	if ( wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	global $wpdb;

	$metrics = array(
		'sql_queries'    => (int) $wpdb->num_queries,
		'peak_memory_mb' => round( memory_get_peak_usage( true ) / MB_IN_BYTES, 3 ),
	);
	$encoded = wp_json_encode( $metrics, JSON_HEX_TAG | JSON_HEX_AMP );
	if ( false === $encoded ) {
		return;
	}

	wp_print_inline_script_tag(
		'window.__hsAdminPerformance = ' . $encoded . ';',
		array( 'id' => 'hs-admin-performance-probe' )
	);
}
add_action( 'admin_footer', 'hs_admin_performance_probe_print_metrics', PHP_INT_MAX );
