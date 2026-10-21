<?php
/**
 * Plugin Name: Manacost Speculative Navigation
 * Description: Prerenders public internal pages a reader is about to open, so navigation shows a finished page instead of a frozen one.
 * Version: 1.0.0
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tunes WordPress core speculation rules for anonymous readers.
 *
 * Core only emits rules for logged-out visitors with pretty permalinks; that
 * gate is kept. Scripts with side effects (views, ads, analytics events) wait
 * for `prerenderingchange`, so a page that is never shown counts nothing.
 */
final class Manacost_Speculative_Navigation {
	/** Paths that are personal, mutating or not documents; never speculated. */
	private const EXCLUDED_PATHS = array(
		'/account*',
		'/reader*',
		'/profile*',
		'/community*',
		'/login*',
		'/logout*',
		'/search*',
		'/views/*',
		'/mca/*',
		'/feed*',
		'/*/feed*',
		'/comments/*',
	);

	/** Registers the core speculative-loading filters. */
	public static function boot(): void {
		add_filter( 'wp_speculation_rules_configuration', array( __CLASS__, 'configure' ) );
		add_filter( 'wp_speculation_rules_href_exclude_paths', array( __CLASS__, 'exclude_paths' ) );
	}

	/**
	 * Prerenders on hover (desktop) or Chrome's viewport heuristic (mobile).
	 *
	 * @param array<string, string>|null $config Core configuration; null means disabled.
	 * @return array<string, string>|null
	 */
	public static function configure( $config ) {
		if ( ! is_array( $config ) || ( defined( 'MANACOST_PRERENDER_ENABLED' ) && ! MANACOST_PRERENDER_ENABLED ) ) {
			return $config;
		}

		return array(
			'mode'      => 'prerender',
			'eagerness' => 'moderate',
		);
	}

	/**
	 * Adds site-specific private and non-document paths to core's exclusions.
	 *
	 * @param array<int, string> $paths Existing exclusion patterns.
	 * @return array<int, string>
	 */
	public static function exclude_paths( $paths ): array {
		return array_values( array_unique( array_merge( (array) $paths, self::EXCLUDED_PATHS ) ) );
	}
}

Manacost_Speculative_Navigation::boot();
