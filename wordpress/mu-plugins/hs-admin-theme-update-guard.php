<?php
/**
 * Plugin Name: HS Admin Theme Update Guard
 * Description: Preserves WordPress' fresh theme-update cache when Newspaper rebuilds its update response.
 * Version: 1.0.0
 *
 * @package HS_Manacost
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps Newspaper's update integration without forcing a remote check on every admin page.
 */
final class HS_Admin_Theme_Update_Guard {
	private const FRESH_FOR_SECONDS = 12 * HOUR_IN_SECONDS;

	/**
	 * Theme-update data captured before the active theme loads.
	 *
	 * @var object|null
	 */
	private static ?object $snapshot = null;

	/**
	 * Whether the current request has a fresh cache snapshot.
	 *
	 * @var bool
	 */
	private static bool $watching = false;

	/**
	 * Whether Newspaper deleted the cache during theme bootstrap.
	 *
	 * @var bool
	 */
	private static bool $was_deleted = false;

	/**
	 * Registers the narrow bootstrap-window hooks.
	 */
	public static function register(): void {
		add_action( 'setup_theme', array( __CLASS__, 'capture_fresh_cache' ), 0 );
		add_action( 'delete_site_transient_update_themes', array( __CLASS__, 'note_cache_deletion' ), 0, 1 );
		add_action( 'after_setup_theme', array( __CLASS__, 'restore_fresh_cache' ), PHP_INT_MAX );
	}

	/**
	 * Captures only the cache state that WordPress core already considers fresh.
	 */
	public static function capture_fresh_cache(): void {
		self::reset_request_state();

		if ( ! self::should_watch_request() ) {
			return;
		}

		$current = get_site_transient( 'update_themes' );
		if ( ! is_object( $current ) || ! isset( $current->last_checked ) || ! is_numeric( $current->last_checked ) ) {
			return;
		}

		$age = time() - (int) $current->last_checked;
		if ( self::FRESH_FOR_SECONDS <= $age ) {
			return;
		}

		self::$snapshot = clone $current;
		self::$watching = true;
	}

	/**
	 * Records deletion only inside the short theme-bootstrap window.
	 *
	 * @param string $transient Deleted site-transient name.
	 */
	public static function note_cache_deletion( $transient ): void {
		if ( self::$watching && 'update_themes' === $transient ) {
			self::$was_deleted = true;
		}
	}

	/**
	 * Restores a deleted fresh cache through Newspaper's registered update filter.
	 */
	public static function restore_fresh_cache(): void {
		$snapshot       = self::$snapshot;
		$should_restore = self::$watching
			&& self::$was_deleted
			&& is_object( $snapshot )
			&& 'Newspaper_new' === get_template()
			&& false === get_site_transient( 'update_themes' );

		self::reset_request_state();

		if ( ! $should_restore ) {
			return;
		}

		set_site_transient( 'update_themes', $snapshot );
	}

	/**
	 * Limits the guard to eligible administrative requests.
	 */
	private static function should_watch_request(): bool {
		if ( ! is_admin() || wp_doing_ajax() ) {
			return false;
		}

		if ( wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Prevents one request's state from leaking into another test or long-running process.
	 */
	private static function reset_request_state(): void {
		self::$snapshot    = null;
		self::$watching    = false;
		self::$was_deleted = false;
	}
}

HS_Admin_Theme_Update_Guard::register();
