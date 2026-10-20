<?php
/**
 * Plugin Name: HS Edge Cache Guard
 * Description: Keeps regional proxies from storing public HTML that WP Rocket deliberately rendered outside its page cache.
 * Version: 1.0.0
 * Author: Manacost
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Marks WP Rocket-rejected renders private for shared caches.
 *
 * Regional proxies share one HTML key for guests. A request with a WP Rocket
 * rejected cookie (for example comment_author_*) or user agent (WhatsApp link
 * previews) receives a raw PHP render without the optimized page cache. Without
 * a private marker an edge stores that render and serves it to every guest.
 */
final class HS_Edge_Cache_Guard {
	private const HEADER = 'Cache-Control: private';

	/** Registers the response header hook. */
	public static function boot(): void {
		add_action( 'send_headers', array( __CLASS__, 'send_header' ) );
	}

	/** Sends the private marker for the current front-end request when needed. */
	public static function send_header(): void {
		if ( headers_sent() ) {
			return;
		}

		$header = self::header_for_request( $_SERVER, $_COOKIE, headers_list() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Only matched against WP Rocket patterns, never output.
		if ( null !== $header ) {
			header( $header );
		}
	}

	/**
	 * Returns the header to send, or null when the shared cache policy stays unchanged.
	 *
	 * @param array<string, mixed> $server  Request server values.
	 * @param array<string, mixed> $cookies Request cookies; only names are inspected.
	 * @param array<int, string>   $sent    Headers already queued for the response.
	 */
	public static function header_for_request( array $server, array $cookies, array $sent ): ?string {
		$method = strtoupper( (string) ( $server['REQUEST_METHOD'] ?? 'GET' ) );
		if ( is_admin() || ! in_array( $method, array( 'GET', 'HEAD' ), true ) ) {
			return null;
		}

		if ( ! self::rejected_by_wp_rocket( (string) ( $server['HTTP_USER_AGENT'] ?? '' ), array_keys( $cookies ) ) ) {
			return null;
		}

		foreach ( $sent as $line ) {
			if ( preg_match( '/^cache-control:.*\b(?:private|no-store|no-cache)\b/i', $line ) ) {
				return null;
			}
		}

		return self::HEADER;
	}

	/**
	 * Mirrors WP Rocket's cookie-name and user-agent rejection checks.
	 *
	 * @param string            $user_agent   Request user agent.
	 * @param array<int, mixed> $cookie_names Request cookie names.
	 */
	private static function rejected_by_wp_rocket( string $user_agent, array $cookie_names ): bool {
		if ( ! function_exists( 'get_rocket_cache_reject_cookies' ) || ! function_exists( 'get_rocket_cache_reject_ua' ) ) {
			return false;
		}

		$cookies = (string) get_rocket_cache_reject_cookies();
		if ( '' !== $cookies ) {
			foreach ( $cookie_names as $name ) {
				if ( self::matches( '#' . $cookies . '#', (string) $name ) ) {
					return true;
				}
			}
		}

		$agents = (string) get_rocket_cache_reject_ua();
		return '' !== $agents && '' !== $user_agent && self::matches( '#' . $agents . '#', $user_agent );
	}

	/**
	 * Matches a configured pattern; an invalid pattern keeps the existing cache policy.
	 *
	 * @param string $pattern Delimited regular expression.
	 * @param string $subject Value to test.
	 */
	private static function matches( string $pattern, string $subject ): bool {
		return 1 === @preg_match( $pattern, $subject ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- Invalid stored patterns must not emit warnings into public HTML.
	}
}

HS_Edge_Cache_Guard::boot();
