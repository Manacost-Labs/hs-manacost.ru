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

	global $wpdb, $pagenow;
	if ( 'wpdb' !== get_class( $wpdb ) || ! empty( $wpdb->queries )
		|| ! in_array( $pagenow, array( 'index.php', 'edit.php', 'upload.php', 'post.php', 'post-new.php', 'plugins.php', 'users.php', 'options-general.php' ), true ) ) {
		return;
	}
	$GLOBALS['hs_admin_sql_profile'] = array(
		'queries_before'   => (int) $wpdb->num_queries,
		'profiled_queries' => 0,
		'total_ms'         => 0.0,
		'callers'          => array(),
		'seq'              => 0,
		'pending'          => null,
		'emitted'          => false,
	);
	add_filter( 'log_query_custom_data', 'hs_admin_performance_probe_collect_query', PHP_INT_MAX, 4 );
	add_action( 'shutdown', 'hs_admin_performance_probe_release_pending', PHP_INT_MAX );
	// Auth has already resolved through core and all plugin/theme authentication hooks.
	define( 'SAVEQUERIES', true );
}
add_action( 'admin_init', 'hs_admin_performance_probe_enable_sql_profile', PHP_INT_MIN );

/**
 * Disarm logging before aborting an incompatible diagnostic, including shutdown.
 *
 * @param string   $message  Non-sensitive diagnostic failure message.
 * @param callable $renderer WordPress renderer, which a custom handler may let return.
 * @phpstan-param callable(string,string,array{response:int}):void $renderer
 * @return never
 */
function hs_admin_performance_probe_abort( string $message, callable $renderer ): never {
	$GLOBALS['hs_admin_sql_profile']['pending'] = null;
	remove_filter( 'log_query_custom_data', 'hs_admin_performance_probe_collect_query', PHP_INT_MAX );
	remove_action( 'shutdown', 'hs_admin_performance_probe_release_pending', PHP_INT_MAX );
	$renderer( esc_html( $message ), 'SQL diagnostic unavailable', array( 'response' => 503 ) );
	// A custom wp_die handler may return. Never continue or pop an unowned tuple.
	exit;
}

/**
 * Release only the previous SQL tuple carrying this diagnostic's sequence marker.
 *
 * @return void
 */
function hs_admin_performance_probe_release_pending(): void {
	global $wpdb;
	$profile = &$GLOBALS['hs_admin_sql_profile'];
	if ( null === $profile['pending'] ) {
		return;
	}
	$last = is_array( $wpdb->queries ) ? array_key_last( $wpdb->queries ) : null;
	if ( null === $last || ( $wpdb->queries[ $last ][4]['hs_sql_profile_seq'] ?? null ) !== $profile['pending'] ) {
		// Never destroy another connection's or profiler's log to keep this probe running.
		hs_admin_performance_probe_abort( 'SQL diagnostic unavailable: incompatible query logging.', 'wp_die' );
	}
	array_pop( $wpdb->queries );
	$profile['pending'] = null;
}

/**
 * Resolve only existing PHP symbols; hook argument fragments are never labels.
 *
 * @param string $name Candidate from the core callstack summary.
 * @return string|null Canonical declared identifier, without arguments or paths.
 */
function hs_admin_performance_probe_canonical_caller( string $name ): ?string {
	if ( ! preg_match( '/\A[A-Za-z_\\\\][A-Za-z0-9_\\\\]{0,79}(?:(?:->|::)[A-Za-z_][A-Za-z0-9_]{0,63})?\z/', $name ) ) {
		return null;
	}
	if ( str_contains( $name, '->' ) || str_contains( $name, '::' ) ) {
		$separator = str_contains( $name, '->' ) ? '->' : '::';
		$parts     = explode( $separator, $name, 2 );
		if ( ! class_exists( $parts[0], false ) || ! method_exists( $parts[0], $parts[1] ) ) {
			return null;
		}
		$method = new ReflectionMethod( $parts[0], $parts[1] );
		$name   = $method->getDeclaringClass()->getName() . $separator . $method->getName();
	} elseif ( function_exists( $name ) ) {
		$name = ( new ReflectionFunction( $name ) )->getName();
	} else {
		return null;
	}
	return strlen( $name ) <= 80 ? $name : null;
}

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
	 * @var array{queries_before: int, profiled_queries: int, total_ms: float, callers: array<string, array{caller: string, count: int, total_ms: float}>, seq: int, pending: int|null, emitted: bool} $profile
	 */
	$profile = &$GLOBALS['hs_admin_sql_profile'];
	hs_admin_performance_probe_release_pending();
	$data['hs_sql_profile_seq'] = ++$profile['seq'];
	$profile['pending']         = $profile['seq'];
	if ( $profile['emitted'] ) {
		return $data;
	}
	if ( $profile['profiled_queries'] >= 2048 ) {
		hs_admin_performance_probe_abort( 'SQL diagnostic query limit reached.', 'wp_die' );
	}
	$caller = 'other';
	foreach ( array_slice( array_reverse( explode( ',', substr( $callstack, -8192 ) ) ), 0, 48 ) as $name ) {
		$name = trim( $name );
		if ( ! preg_match( '/^[A-Za-z_\\\\][A-Za-z0-9_\\\\:>\-]{0,79}$/D', $name ) ) {
			continue;
		}
		if ( preg_match( '/^(wpdb|WP_Hook)(->|::)|^(do_action|apply_filters|do_action_ref_array|apply_filters_ref_array)$/', $name ) ) {
			continue;
		}
		$name = hs_admin_performance_probe_canonical_caller( $name );
		if ( null === $name ) {
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
		$profile                = &$GLOBALS['hs_admin_sql_profile'];
		$metrics['sql_profile'] = array(
			'queries_before'      => $profile['queries_before'],
			'profiled_queries'    => $profile['profiled_queries'],
			'total_ms'            => $profile['total_ms'],
			'callers'             => array_values( $profile['callers'] ),
			'window'              => 'admin_init_to_admin_footer',
			'coverage_consistent' => $profile['profiled_queries'] === $wpdb->num_queries - $profile['queries_before'],
		);
		$profile['emitted']     = true;
		hs_admin_performance_probe_release_pending();
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
