<?php

declare(strict_types=1);

if ( $argc !== 3 ) {
	fwrite( STDERR, "usage: fixture plugin scenario\n" );
	exit( 2 );
}

$plugin   = $argv[1];
$scenario = $argv[2];
$now      = time();
$admin    = true;
$ajax     = false;
$allowed  = true;
$template = 'Newspaper_new';
$actions  = array();
$filters  = array();
$stored   = null;

define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	global $actions;
	$actions[ $hook ][ $priority ][] = array( $callback, $accepted_args );
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	global $filters;
	$filters[ $hook ][ $priority ][] = array( $callback, $accepted_args );
}

function do_action( $hook, ...$args ) {
	global $actions;

	if ( empty( $actions[ $hook ] ) ) {
		return;
	}

	ksort( $actions[ $hook ] );
	foreach ( $actions[ $hook ] as $callbacks ) {
		foreach ( $callbacks as list( $callback, $accepted_args ) ) {
			$callback( ...array_slice( $args, 0, $accepted_args ) );
		}
	}
}

function apply_filters( $hook, $value ) {
	global $filters;

	if ( empty( $filters[ $hook ] ) ) {
		return $value;
	}

	ksort( $filters[ $hook ] );
	foreach ( $filters[ $hook ] as $callbacks ) {
		foreach ( $callbacks as list( $callback ) ) {
			$value = $callback( $value );
		}
	}

	return $value;
}

function is_admin() {
	global $admin;
	return $admin;
}

function wp_doing_ajax() {
	global $ajax;
	return $ajax;
}

function wp_doing_cron() {
	return defined( 'DOING_CRON' ) && DOING_CRON;
}

function current_user_can( $capability ) {
	global $allowed;
	return $allowed && 'update_themes' === $capability;
}

function get_template() {
	global $template;
	return $template;
}

function get_site_transient( $name ) {
	global $stored;
	return 'update_themes' === $name && null !== $stored ? $stored : false;
}

function set_site_transient( $name, $value, $expiration = 0 ) {
	global $stored;
	if ( 'update_themes' !== $name ) {
		return false;
	}

	$stored = apply_filters( 'pre_set_site_transient_update_themes', $value );
	return true;
}

function same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . "\n" );
		exit( 1 );
	}
}

$fresh = (object) array(
	'last_checked' => $now - 60,
	'response'     => array(),
);

switch ( $scenario ) {
	case 'stale':
		$stored = (object) array( 'last_checked' => $now - ( 12 * HOUR_IN_SECONDS ) );
		break;
	case 'absent':
		$stored = null;
		break;
	case 'frontend':
		$stored = $fresh;
		$admin  = false;
		break;
	case 'ajax':
		$stored = $fresh;
		$ajax   = true;
		break;
	case 'cron':
		$stored = $fresh;
		define( 'DOING_CRON', true );
		break;
	case 'cli':
		$stored = $fresh;
		define( 'WP_CLI', true );
		break;
	case 'capability_disabled':
		$stored  = $fresh;
		$allowed = false;
		break;
	case 'other_theme':
		$stored   = $fresh;
		$template = 'TwentyTwentySix';
		break;
	case 'invalid_last_checked':
		$stored = (object) array( 'last_checked' => 'not-a-timestamp' );
		break;
	default:
		$stored = $fresh;
}

require $plugin;

do_action( 'setup_theme' );

if ( 'not_deleted' !== $scenario ) {
	do_action( 'delete_site_transient_update_themes', 'update_themes' );
	$stored = null;
}

if ( 'newer' === $scenario ) {
	$stored = (object) array(
		'last_checked' => $now,
		'marker'       => 'newer',
	);
}

add_filter(
	'pre_set_site_transient_update_themes',
	static function ( $value ) {
		$value->response['Newspaper_new'] = array( 'new_version' => '12.7.4' );
		return $value;
	}
);

do_action( 'after_setup_theme' );

if ( 'later_manual_check' === $scenario ) {
	do_action( 'delete_site_transient_update_themes', 'update_themes' );
	$stored = null;
}

switch ( $scenario ) {
	case 'fresh':
	case 'capability_disabled':
		same( $now - 60, $stored->last_checked ?? null, 'fresh cache was not restored' );
		same( '12.7.4', $stored->response['Newspaper_new']['new_version'] ?? null, 'tagDiv filter was not applied' );
		break;
	case 'newer':
		same( 'newer', $stored->marker ?? null, 'newer cache was overwritten' );
		break;
	case 'not_deleted':
		same( $fresh, $stored, 'untouched cache changed' );
		break;
	case 'later_manual_check':
		same( null, $stored, 'later manual check was blocked' );
		break;
	default:
		same( null, $stored, 'cache should not have been restored' );
}

echo "PASS\n";
