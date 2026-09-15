<?php
/**
 * Newspaper theme-option cache invalidation for Manacost Cache Purge.
 *
 * @package ManacostCachePurge
 */

defined( 'ABSPATH' ) || exit;

/**
 * Invalidates public caches after Newspaper changes its consolidated option.
 */
trait Manacost_Cache_Purge_Theme_Options {
	/**
	 * Schedules a purge after theme customization or activation.
	 *
	 * @return void
	 */
	public static function purge_after_theme_change(): void {
		self::run_automatic_purge( 'theme' );
	}

	/**
	 * Schedules cache invalidation when Newspaper public settings change.
	 *
	 * @param mixed  $old_value Previous consolidated theme settings.
	 * @param mixed  $new_value New consolidated theme settings.
	 * @param string $option    Updated option name.
	 * @return void
	 */
	public static function purge_after_newspaper_options_change( $old_value, $new_value, string $option = 'td_011' ): void {
		unset( $option );

		if ( $old_value === $new_value ) {
			return;
		}

		self::run_automatic_purge( 'newspaper_theme_options', true );
	}

	/**
	 * Guarantees a later purge when another recent change owns the throttle.
	 *
	 * @return void
	 */
	private static function schedule_deferred_automatic_purge(): void {
		$source    = 'deferred_change';
		$args      = array( $source );
		$timestamp = time() + self::AUTO_PURGE_THROTTLE_SECONDS + 1;

		if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( self::ASYNC_PURGE_HOOK, $args, 'manacost-cache' ) ) {
			return;
		}

		if ( function_exists( 'as_schedule_single_action' ) && as_schedule_single_action( $timestamp, self::ASYNC_PURGE_HOOK, $args, 'manacost-cache', true ) ) {
			return;
		}

		self::run_async_purge( $source );
	}
}
