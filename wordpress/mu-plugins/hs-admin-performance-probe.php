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
 * Enable bounded SQL attribution only for an opted-in diagnostic navigation.
 *
 * @return void
 */
function hs_admin_performance_probe_enable_sql_profile(): void {
	if ( ! in_array( wp_get_environment_type(), array( 'local', 'staging' ), true )
		|| ! is_admin() || wp_doing_ajax() || defined( 'SAVEQUERIES' ) ) {
		return;
	}

	if ( ! isset( $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_X_HS_ADMIN_SQL_PROFILE'] )
		|| 'GET' !== $_SERVER['REQUEST_METHOD'] || '1' !== $_SERVER['HTTP_X_HS_ADMIN_SQL_PROFILE']
		|| ! current_user_can( 'manage_options' ) ) {
		return;
	}

	global $wpdb;
	$GLOBALS['hs_admin_sql_profile'] = array(
		'queries_before'   => (int) $wpdb->num_queries,
		'profiled_queries' => 0,
		'total_ms'         => 0.0,
		'callers'          => array(),
	);
	// Never change another profiler's log. This request owns SAVEQUERIES exclusively.
	define( 'SAVEQUERIES', true );
	add_filter( 'log_query_custom_data', 'hs_admin_performance_probe_collect_query', PHP_INT_MAX, 4 );
}
add_action( 'plugins_loaded', 'hs_admin_performance_probe_enable_sql_profile', PHP_INT_MAX );

/**
 * Aggregate numeric query costs without retaining query text or argument values.
 *
 * @param array<string, mixed> $data      Existing custom query data.
 * @param string               $query     SQL supplied by core, never exported.
 * @param float                $seconds   Query execution duration.
 * @param string               $callstack Core's function-name summary.
 * @return array<string, mixed>
 */
function hs_admin_performance_probe_collect_query( array $data, string $query, float $seconds, string $callstack ): array {
	global $wpdb;
	/** Owned, bounded diagnostic state.
	 *
	 * @var array{queries_before: int, profiled_queries: int, total_ms: float, callers: array<string, array{caller: string, count: int, total_ms: float}>} $profile
	 */
	$profile = &$GLOBALS['hs_admin_sql_profile'];
	$caller  = 'other';
	foreach ( array_slice( explode( ',', substr( $callstack, 0, 4096 ) ), 0, 64 ) as $name ) {
		$name = trim( $name );
		if ( ! preg_match( '/^[A-Za-z_\\\\][A-Za-z0-9_\\\\:>\-]{0,79}$/D', $name ) ) {
			continue;
		}
		if ( preg_match( '/^(wpdb|WP_Hook)(->|::)|^(do_action|apply_filters|do_action_ref_array|apply_filters_ref_array)$/', $name ) ) {
			continue;
		}
		if ( 'other' === $caller ) {
			$caller = $name;
		}
		if ( preg_match( '/^(hs_|Manacost|wf|td_|AIOSEO|WPRocket)/i', $name ) ) {
			$caller = $name;
			break;
		}
	}
	if ( ! isset( $profile['callers'][ $caller ] ) && count( $profile['callers'] ) >= 63 ) {
		$caller = 'other';
	}
	$milliseconds = is_finite( $seconds ) ? max( 0.0, $seconds * 1000 ) : 0.0;
	$summary      = $profile['callers'][ $caller ] ?? array(
		'caller'   => $caller,
		'count'    => 0,
		'total_ms' => 0.0,
	);
	++$profile['profiled_queries'];
	++$summary['count'];
	$summary['total_ms']          += $milliseconds;
	$profile['total_ms']          += $milliseconds;
	$profile['callers'][ $caller ] = $summary;
	// Core appends the current query after this filter: retain at most one SQL tuple.
	$wpdb->queries = array();
	return $data;
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
	if ( isset( $GLOBALS['hs_admin_sql_profile'] ) ) {
		$metrics['sql_profile'] = $GLOBALS['hs_admin_sql_profile'];
		$wpdb->queries          = array();
	}
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
