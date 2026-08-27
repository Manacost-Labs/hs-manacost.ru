<?php
/**
 * Plugin Name: Manacost Domain Mirror
 * Description: Keeps hs-manacost.com as a functional, non-indexed mirror of hs-manacost.ru.
 * Version: 1.0.0
 * Author: Manacost
 */

defined( 'ABSPATH' ) || exit;

final class Manacost_Domain_Mirror {
	private const PRIMARY_HOST = 'hs-manacost.ru';
	private const MIRROR_HOST = 'hs-manacost.com';
	private static bool $buffer_started = false;

	public static function bootstrap(): void {
		add_action( 'init', [ __CLASS__, 'start_output_buffer' ], -1000 );
		add_action( 'send_headers', [ __CLASS__, 'send_mirror_headers' ], 1000 );
		add_filter( 'wp_headers', [ __CLASS__, 'filter_headers' ], 1000 );
		add_filter( 'wp_robots', [ __CLASS__, 'filter_robots' ], 1000 );
		add_filter( 'robots_txt', [ __CLASS__, 'filter_robots_txt' ], 1000, 2 );
		add_filter( 'redirect_canonical', [ __CLASS__, 'filter_redirect' ], 1000, 2 );
		add_filter( 'wp_redirect', [ __CLASS__, 'filter_redirect' ], 1000, 2 );
		add_filter( 'allowed_redirect_hosts', [ __CLASS__, 'allowed_redirect_hosts' ], 1000 );
		add_filter( 'get_canonical_url', [ __CLASS__, 'primary_url' ], 1000 );
		add_filter( 'aioseo_canonical_url', [ __CLASS__, 'primary_url' ], 1000 );

		foreach (
			[
				'attachment_link',
				'author_link',
				'feed_link',
				'page_link',
				'post_link',
				'post_type_link',
				'preview_post_link',
				'term_link',
				'wp_get_attachment_url',
			]
			as $filter
		) {
			add_filter( $filter, [ __CLASS__, 'mirror_url' ], 1000 );
		}

		add_filter( 'rest_post_dispatch', [ __CLASS__, 'filter_rest_response' ], 1000, 3 );
	}

	public static function is_mirror(): bool {
		if ( defined( 'MANACOST_PUBLIC_HOST' ) ) {
			return MANACOST_PUBLIC_HOST === self::MIRROR_HOST;
		}

		$host = strtolower( (string) ( $_SERVER['HTTP_HOST'] ?? '' ) );
		$host = preg_replace( '/:\d+$/', '', $host ) ?: '';

		return in_array( rtrim( $host, '.' ), [ self::MIRROR_HOST, 'www.' . self::MIRROR_HOST ], true );
	}

	public static function start_output_buffer(): void {
		if ( ! self::is_mirror() || self::$buffer_started ) {
			return;
		}

		self::$buffer_started = true;
		ob_start( [ __CLASS__, 'rewrite_output' ] );
	}

	public static function send_mirror_headers(): void {
		if ( ! self::is_mirror() || headers_sent() ) {
			return;
		}

		header( 'X-Robots-Tag: noindex, follow', true );
		header( 'X-Manacost-Mirror: active', true );
	}

	/**
	 * @param array<string,string> $headers
	 * @return array<string,string>
	 */
	public static function filter_headers( array $headers ): array {
		if ( self::is_mirror() ) {
			$headers['X-Robots-Tag'] = 'noindex, follow';
			$headers['X-Manacost-Mirror'] = 'active';
		}

		return $headers;
	}

	/**
	 * @param array<string,bool|string> $robots
	 * @return array<string,bool|string>
	 */
	public static function filter_robots( array $robots ): array {
		if ( self::is_mirror() ) {
			unset( $robots['index'], $robots['noindex'] );
			$robots['noindex'] = true;
			$robots['follow'] = true;
		}

		return $robots;
	}

	public static function filter_robots_txt( string $output, bool $public ): string {
		unset( $public );

		if ( ! self::is_mirror() ) {
			return $output;
		}

		return (string) preg_replace( '/^Sitemap:\s*https?:\/\/[^\r\n]+\R?/mi', '', $output );
	}

	/**
	 * @param mixed $location
	 * @return mixed
	 */
	public static function filter_redirect( $location, ...$unused ) {
		unset( $unused );

		if ( ! self::is_mirror() || ! is_string( $location ) ) {
			return $location;
		}

		return self::mirror_url( $location );
	}

	/**
	 * @param array<int,string> $hosts
	 * @return array<int,string>
	 */
	public static function allowed_redirect_hosts( array $hosts ): array {
		$hosts[] = self::PRIMARY_HOST;
		$hosts[] = self::MIRROR_HOST;

		return array_values( array_unique( $hosts ) );
	}

	/**
	 * @param mixed $url
	 * @return mixed
	 */
	public static function primary_url( $url ) {
		if ( ! is_string( $url ) ) {
			return $url;
		}

		return self::replace_host( $url, self::MIRROR_HOST, self::PRIMARY_HOST );
	}

	/**
	 * @param mixed $url
	 * @return mixed
	 */
	public static function mirror_url( $url ) {
		if ( ! self::is_mirror() || ! is_string( $url ) ) {
			return $url;
		}

		return self::replace_host( $url, self::PRIMARY_HOST, self::MIRROR_HOST );
	}

	/**
	 * @param mixed $response
	 * @return mixed
	 */
	public static function filter_rest_response( $response, ...$unused ) {
		unset( $unused );

		if ( ! self::is_mirror() || ! is_object( $response ) || ! method_exists( $response, 'get_data' ) || ! method_exists( $response, 'set_data' ) ) {
			return $response;
		}

		$response->set_data( self::rewrite_data( $response->get_data() ) );

		return $response;
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public static function rewrite_data( $value ) {
		if ( is_string( $value ) ) {
			return self::mirror_url( $value );
		}

		if ( ! is_array( $value ) ) {
			return $value;
		}

		foreach ( $value as $key => $item ) {
			$value[ $key ] = self::rewrite_data( $item );
		}

		return $value;
	}

	public static function rewrite_output( string $output ): string {
		if ( ! self::is_mirror() || $output === '' || ! self::is_textual_response() ) {
			return $output;
		}

		$canonical_tags = [];
		$output = preg_replace_callback(
			'#<link\b[^>]*\brel=(["\'])canonical\1[^>]*>#i',
			static function ( array $matches ) use ( &$canonical_tags ): string {
				$token = '___MANACOST_PRIMARY_CANONICAL_' . count( $canonical_tags ) . '___';
				$canonical_tags[ $token ] = self::replace_host( $matches[0], self::MIRROR_HOST, self::PRIMARY_HOST );

				return $token;
			},
			$output
		) ?: $output;

		$output = self::replace_host( $output, self::PRIMARY_HOST, self::MIRROR_HOST );

		if ( self::is_robots_request() ) {
			$output = (string) preg_replace( '/^Sitemap:\s*https?:\/\/[^\r\n]+\R?/mi', '', $output );
		}

		if ( $canonical_tags ) {
			$output = strtr( $output, $canonical_tags );
		}

		return $output;
	}

	private static function replace_host( string $value, string $from, string $to ): string {
		$replacements = [
			'https://www.' . $from => 'https://www.' . $to,
			'http://www.' . $from => 'https://www.' . $to,
			'https://' . $from => 'https://' . $to,
			'http://' . $from => 'https://' . $to,
			'//www.' . $from => '//www.' . $to,
			'//' . $from => '//' . $to,
			'https:\\/\\/www.' . $from => 'https:\\/\\/www.' . $to,
			'http:\\/\\/www.' . $from => 'https:\\/\\/www.' . $to,
			'https:\\/\\/' . $from => 'https:\\/\\/' . $to,
			'http:\\/\\/' . $from => 'https:\\/\\/' . $to,
		];

		return strtr( $value, $replacements );
	}

	private static function is_textual_response(): bool {
		foreach ( headers_list() as $header ) {
			if ( stripos( $header, 'Content-Type:' ) !== 0 ) {
				continue;
			}

			return (bool) preg_match( '#(?:text/|application/(?:json|ld\+json|javascript|xml|xhtml\+xml))#i', $header );
		}

		return true;
	}

	private static function is_robots_request(): bool {
		$path = parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );

		return $path === '/robots.txt';
	}
}

if ( function_exists( 'add_action' ) ) {
	Manacost_Domain_Mirror::bootstrap();
}
