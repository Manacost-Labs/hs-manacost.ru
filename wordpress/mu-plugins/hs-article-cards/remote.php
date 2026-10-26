<?php
/**
 * Bounded public WordPress catalog transport. Never used during article rendering.
 *
 * @package HSManacost
 */

defined( 'ABSPATH' ) || exit;

/** Public, credential-free transport to the one supported external provider. */
final class HS_Article_Cards_Remote {
	/**
	 * Both providers are explicit; no arbitrary remote hosts.
	 *
	 * @param string $source Catalog identifier.
	 */
	public static function host( string $source ): string {
		return 'manacost' === $source ? 'hs-manacost.ru' : 'kolodahearthstone.com';
	}

	/** Maximum JSON response bytes. */
	private const MAX_BODY = 524288;

	/**
	 * Fetch public metadata, with a short cache and a provider-wide failure cooldown.
	 *
	 * @param string                    $endpoint Allowed REST resource.
	 * @param array<string, int|string> $query Public filters only.
	 * @param string                    $source Catalog provider.
	 * @return array{items:list<array<string,mixed>>,pages:int}|WP_Error
	 */
	public static function get( string $endpoint, array $query, string $source = 'koloda' ) {
		if ( ! in_array( $source, array( 'manacost', 'koloda' ), true ) ) {
			return self::error( 'invalid_source' );
		}
		if ( defined( 'HS_ARTICLE_CARDS_REMOTE_ENABLED' ) && ! HS_ARTICLE_CARDS_REMOTE_ENABLED ) {
			return self::error( 'disabled' );
		}
		if ( 'koloda' === $source && defined( 'HS_ARTICLE_CARDS_KOLODA_ENABLED' ) && ! HS_ARTICLE_CARDS_KOLODA_ENABLED ) {
			return self::error( 'disabled' );
		}
		if ( ! preg_match( '#^(posts(/[1-9][0-9]*)?|categories)$#D', $endpoint ) ) {
			return self::error( 'invalid_resource' );
		}
		// Koloda omits embedded covers when _embed precedes _fields.
		// Keep the catalog's request order; the complete URL remains the cache key.
		$url = add_query_arg( $query, 'https://' . self::host( $source ) . '/wp-json/wp/v2/' . $endpoint );
		$key = 'hs_article_cards_' . hash( 'sha256', $url );
		$hit = get_transient( $key );
		if ( is_array( $hit ) && isset( $hit['pages'] ) && is_int( $hit['pages'] ) ) {
			$items = self::items( $hit['items'] ?? null );
			if ( null !== $items ) {
				return array(
					'items' => $items,
					'pages' => $hit['pages'],
				);
			}
		}
		$failure = get_transient( 'hs_article_cards_cooldown_' . $source );
		if ( is_wp_error( $failure ) ) {
			return $failure;
		}
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 5,
				'redirection'         => 0,
				'limit_response_size' => self::MAX_BODY + 1,
				'headers'             => array( 'Accept' => 'application/json' ),
			)
		);
		$result   = self::decode( $response, str_starts_with( $endpoint, 'posts/' ) );
		if ( is_wp_error( $result ) ) {
			$delay = is_wp_error( $response ) ? 30 : self::retry_delay( $response );
			set_transient( 'hs_article_cards_cooldown_' . $source, $result, $delay );
			do_action( 'hs_article_cards_provider_result', $result->get_error_code() );
			return $result;
		}
		set_transient( $key, $result, 5 * MINUTE_IN_SECONDS );
		do_action( 'hs_article_cards_provider_result', 'success' );
		return $result;
	}

	/**
	 * Validate status, content type, bounded size and list shape.
	 *
	 * @param array<string,mixed>|WP_Error $response WordPress HTTP result.
	 * @param bool                         $single Whether a single post was requested.
	 * @return array{items:list<array<string,mixed>>,pages:int}|WP_Error
	 */
	private static function decode( $response, bool $single ) {
		if ( is_wp_error( $response ) ) {
			return self::error( 'unavailable' );
		}
		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status ) {
			return self::error( 'http_' . $status );
		}
		$body = wp_remote_retrieve_body( $response );
		$type = wp_remote_retrieve_header( $response, 'content-type' );
		if ( strlen( $body ) > self::MAX_BODY || ! is_string( $type ) || ! preg_match( '#^application/json(?:\s*;|$)#i', $type ) ) {
			return self::error( 'invalid_response' );
		}
		$items = json_decode( $body, true, 32 );
		if ( $single && is_array( $items ) && isset( $items['id'] ) ) {
			$items = array( $items );
		}
		$items = self::items( $items );
		if ( null === $items ) {
			return self::error( 'invalid_response' );
		}
		return array(
			'items' => $items,
			'pages' => max( 1, min( 10000, (int) wp_remote_retrieve_header( $response, 'x-wp-totalpages' ) ) ),
		);
	}

	/**
	 * Validate HTTP and cached lists through the same boundary.
	 *
	 * @param mixed $items Candidate list.
	 * @return list<array<string,mixed>>|null
	 */
	private static function items( $items ): ?array {
		if ( ! is_array( $items ) || ! array_is_list( $items ) || count( $items ) > 100 ) {
			return null;
		}
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || ! isset( $item['id'] ) || ! is_int( $item['id'] ) || $item['id'] < 1 ) {
				return null;
			}
		}
		return $items;
	}

	/**
	 * Respect both standard Retry-After forms, within a bounded cooldown.
	 *
	 * @param array<string,mixed> $response HTTP result.
	 * @return int
	 */
	private static function retry_delay( array $response ): int {
		$value = wp_remote_retrieve_header( $response, 'retry-after' );
		if ( ! is_string( $value ) ) {
			return 30;
		}
		$delay = ctype_digit( $value ) ? (int) $value : (int) strtotime( $value ) - time();
		return max( 30, min( 3600, $delay ) );
	}

	/**
	 * Safe public-facing error, without provider diagnostics or response bodies.
	 *
	 * @param string $code Operational code.
	 * @return WP_Error
	 */
	private static function error( string $code ): WP_Error {
		return new WP_Error( 'article_cards_' . $code, __( 'Не удалось загрузить статьи Koloda. Повторите запрос через минуту или выберите Manacost.', 'hs-article-cards' ) );
	}
}
