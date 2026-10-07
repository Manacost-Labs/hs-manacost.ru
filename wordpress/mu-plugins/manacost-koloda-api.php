<?php
/**
 * Plugin Name: Manacost Koloda Data API
 * Description: Cached, read-only access to Hearthstone cards and statistics.
 * Version: 1.0.0
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Shared server-side client. Loading this file never makes an HTTP request. */
final class Manacost_Koloda_API {

	private const BASE_URL  = 'https://api.kolodahearthstone.com';
	private const MAX_BODY  = 2097152;
	private const FRESH_TTL = 300;
	private const STALE_TTL = 86400;

	/**
	 * Read a documented public data resource; no admin or authentication routes.
	 *
	 * @param string               $path  Absolute API path, without query string.
	 * @param array<string, mixed> $query Public filters and pagination only.
	 * @return array<string, mixed>|WP_Error Original JSON in data, fetched_at,
	 *                                      stale flag and optional error code.
	 */
	public static function get( string $path, array $query = array() ) {
		if ( defined( 'MANACOST_KOLODA_API_ENABLED' ) && ! MANACOST_KOLODA_API_ENABLED ) {
			return new WP_Error( 'koloda_disabled', __( 'Koloda data access is disabled.', 'manacost' ) );
		}
		if ( ! self::valid_path( $path ) || ! self::valid_query( $query ) ) {
			return new WP_Error( 'koloda_invalid_request', __( 'Invalid Koloda data request.', 'manacost' ) );
		}

		ksort( $query );
		$url    = add_query_arg( $query, self::BASE_URL . $path );
		$key    = 'hs_koloda_' . hash( 'sha256', $url );
		$cached = get_transient( $key );
		if ( is_array( $cached ) && $cached['fresh_until'] > time() ) {
			return self::result( $path, $cached );
		}

		$error = get_transient( 'hs_koloda_rate_limit' );
		if ( ! is_wp_error( $error ) ) {
			$error = get_transient( $key . '_error' );
		}
		if ( ! is_wp_error( $error ) ) {
			$error = self::fetch( $url );
		}
		if ( is_wp_error( $error ) ) {
			return self::failure( $path, $key, $cached, $error );
		}

		$entry = array(
			'data'        => $error,
			'fetched_at'  => time(),
			'fresh_until' => time() + self::FRESH_TTL,
		);
		set_transient( $key, $entry, self::STALE_TTL );
		delete_transient( $key . '_error' );
		return self::result( $path, $entry );
	}

	/**
	 * Only the provider's documented data paths are reachable.
	 *
	 * @param string $path API path.
	 * @return bool
	 */
	private static function valid_path( string $path ): bool {
		$library = 'cards|constructed-cards|heroes|hero-skins|pets|coins|timewarped-cards|diamond-cards|anomalies|quests|dark-gifts|darkmoon-prizes|rewards|trinkets';
		$routes  = array(
			'#^/api/v1/(?:' . $library . ')(?:/(?:by-dbf/)?[A-Za-z0-9_-]+(?:/wiki)?)?$#D',
			'#^/api/v1/(?:meta|libraries/(?:anomaly|quest|darkmoon_prize|reward|trinket))$#D',
			'#^/v1/(?:constructed/(?:decks|archetypes|deck-radar|hsguru-deck)|(?:bg|battlegrounds)/(?:heroes|minions)|arena/classes|hsguru/(?:meta|archetypes(?:/(?:history|analysis))?))$#D',
			'#^/api/(?:db/(?:decks|archetypes(?:/[0-9]+(?:/(?:mulligan|matchups|decks|history))?)?|cards/trends|bg/minions(?:/[0-9]+(?:/history)?)?)|bg/(?:trinkets|heroes(?:/(?:duos|[0-9]+(?:/(?:tavern-up|hero-power|best-composition))?))?)|hsreplay/archetypes|patches(?:/[0-9][A-Za-z0-9_.-]*)?)$#D',
		);
		foreach ( $routes as $route ) {
			if ( preg_match( $route, $path ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Bound filters, pagination and request cardinality; never accept credentials.
	 *
	 * @param array<string, mixed> $query Public query parameters.
	 * @return bool
	 */
	private static function valid_query( array $query ): bool {
		$allowed = explode( ' ', 'page per_page limit offset q search format format_name rank rank_range period coin min_games has_decks sort order class class_name source_id min_win_rate game_type mode mmr timeRange time_range tavern_tier card_type include card_set rarity creature_type minion_type spell_school mana_cost collectible in_pool duos_only variant active_only trinket_tier archetype card_name status region display_only include_cards hl match_state include_content' );
		if ( count( $query ) > 20 ) {
			return false;
		}
		foreach ( $query as $name => $value ) {
			if ( ! in_array( $name, $allowed, true ) || ! is_scalar( $value ) || strlen( (string) $value ) > 200 ) {
				return false;
			}
			if ( in_array( $name, array( 'page', 'per_page', 'limit', 'offset' ), true ) ) {
				$maximum = in_array( $name, array( 'limit', 'per_page' ), true ) ? 100 : 100000;
				$minimum = 'offset' === $name ? 0 : 1;
				if ( ! ctype_digit( (string) $value ) || (int) $value < $minimum || (int) $value > $maximum ) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Perform one bounded GET with verified TLS and no redirects or site cookies.
	 *
	 * @param string $url Fixed-host URL.
	 * @return array<mixed>|WP_Error
	 */
	private static function fetch( string $url ) {
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 5,
				'redirection'         => 0,
				'sslverify'           => true,
				'limit_response_size' => self::MAX_BODY + 1,
				'headers'             => array( 'Accept' => 'application/json' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return self::error( 'koloda_unavailable' );
		}
		// Core may return a numeric string; normalize the HTTP boundary once.
		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status ) {
			$retry = wp_remote_retrieve_header( $response, 'retry-after' );
			$delay = is_string( $retry ) && ctype_digit( $retry ) ? (int) $retry : 0;
			if ( is_string( $retry ) && 0 === $delay ) {
				$delay = max( 0, (int) strtotime( $retry ) - time() );
			}
			return self::error( 'koloda_http_' . $status, $status, max( 30, min( 3600, $delay ) ) );
		}

		$body = wp_remote_retrieve_body( $response );
		$type = wp_remote_retrieve_header( $response, 'content-type' );
		if ( strlen( $body ) > self::MAX_BODY || ! is_string( $type ) || ! preg_match( '#^application/json(?:\s*;|$)#i', $type ) ) {
			return self::error( 'koloda_invalid_response' );
		}
		$data = json_decode( $body, true, 64 );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
			return self::error( 'koloda_invalid_response' );
		}
		return $data;
	}

	/**
	 * Return a safe error without transport details or remote payloads.
	 *
	 * @param string $code   Error code.
	 * @param int    $status HTTP status or zero for transport/schema failures.
	 * @param int    $retry  Cooldown seconds.
	 * @return WP_Error
	 */
	private static function error( string $code, int $status = 0, int $retry = 30 ): WP_Error {
		return new WP_Error(
			$code,
			__( 'Koloda data is temporarily unavailable.', 'manacost' ),
			array(
				'upstream_status' => $status,
				'retry_after'     => $retry,
			)
		);
	}

	/**
	 * Back off without blocking sleeps; reuse public data only for transient faults.
	 *
	 * @param string   $path   API path.
	 * @param string   $key    Cache key.
	 * @param mixed    $cached Last successful response.
	 * @param WP_Error $error  Safe error.
	 * @return array<string, mixed>|WP_Error
	 */
	private static function failure( string $path, string $key, $cached, WP_Error $error ) {
		$details = $error->get_error_data();
		$status  = (int) $details['upstream_status'];
		if ( false === get_transient( $key . '_error' ) ) {
			set_transient( $key . '_error', $error, (int) $details['retry_after'] );
		}
		if ( 429 === $status && false === get_transient( 'hs_koloda_rate_limit' ) ) {
			set_transient( 'hs_koloda_rate_limit', $error, (int) $details['retry_after'] );
		}
		if ( is_array( $cached ) && ( 0 === $status || 429 === $status || $status >= 500 ) ) {
			return self::result( $path, $cached, (string) $error->get_error_code() );
		}
		do_action( 'manacost_koloda_api_result', $path, $error->get_error_code(), 0 );
		return $error;
	}

	/**
	 * Make freshness explicit and emit metadata only, without queries or payloads.
	 *
	 * @param string               $path  Public API path.
	 * @param array<string, mixed> $entry Cached JSON and timestamps.
	 * @param string|null          $error Stale response reason.
	 * @return array<string, mixed>
	 */
	private static function result( string $path, array $entry, ?string $error = null ): array {
		do_action( 'manacost_koloda_api_result', $path, $error ?? 'ok', time() - $entry['fetched_at'] );
		return array(
			'data'       => $entry['data'],
			'fetched_at' => $entry['fetched_at'],
			'stale'      => null !== $error,
			'error'      => $error,
		);
	}
}
