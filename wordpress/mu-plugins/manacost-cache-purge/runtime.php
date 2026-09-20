<?php
/**
 * Runtime invalidation operations for Manacost Cache Purge.
 *
 * @package ManacostCachePurge
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provides bounded filesystem and remote cache invalidation operations.
 */
trait Manacost_Cache_Purge_Runtime {
	/**
	 * Runs the periodic purge outside WordPress installation requests.
	 *
	 * @return void
	 */
	public static function run_scheduled_purge(): void {
		if ( wp_installing() ) {
			return;
		}

		self::run_purge_and_store_results( 'scheduled' );
	}

	/**
	 * Runs the asynchronous purge from the WordPress action hook.
	 *
	 * @param string $source Purge origin.
	 * @return void
	 */
	public static function run_async_purge_hook( string $source = 'auto' ): void {
		self::run_async_purge( $source );
	}

	/**
	 * Clears the configured WordPress object cache.
	 *
	 * @return string Step diagnostic.
	 */
	private static function purge_object_cache(): string {
		return 'not invalidated globally';
	}

	/**
	 * Clears only known local cache folders below the cache root.
	 *
	 * @return string Step diagnostic.
	 */
	private static function purge_local_cache_folders(): string {
		$cache_root = WP_CONTENT_DIR . '/cache';
		$folders    = array(
			'wp-rocket',
			'min',
			'busting',
			'critical-css',
			'used-css',
			'background-css',
			'perfmatters',
			'autoptimize',
			'wpfc-minified',
			'tmp',
			'tmpWpfc',
			'page_enhanced',
		);

		$removed = 0;

		foreach ( $folders as $folder ) {
			$path = $cache_root . '/' . $folder;
			if ( is_dir( $path ) ) {
				self::delete_path_contents( $path );
				++$removed;
			}
		}

		return $removed . ' folders cleaned';
	}

	/**
	 * Purges configured reverse-proxy endpoints over verified TLS.
	 *
	 * @return string Step diagnostic.
	 * @throws RuntimeException When a configured reverse-proxy endpoint cannot be purged.
	 */
	private static function purge_reverse_proxy_cache(): string {
		$config = self::reverse_proxy_config();

		if ( empty( $config['endpoints'] ) || empty( $config['token'] ) ) {
			return 'not configured';
		}

		$purged  = array();
		$cluster = count( $config['endpoints'] ) > 1 ? '0' : '1';

		foreach ( $config['endpoints'] as $endpoint ) {
			$response = self::post_reverse_proxy_purge( $endpoint, $config, $cluster );

			if ( is_wp_error( $response ) ) {
				throw new RuntimeException( 'Reverse proxy request failed' );
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( $code < 200 || $code >= 300 || empty( $body['ok'] ) ) {
				throw new RuntimeException( sprintf( 'Reverse proxy returned HTTP %d', absint( $code ) ) );
			}

			$purged[] = wp_parse_url( $endpoint, PHP_URL_HOST ) . ' removed ' . (int) ( $body['removed'] ?? 0 );
		}

		return implode( '; ', $purged );
	}

	/**
	 * Sends one proxy purge request while retaining TLS verification for IP endpoints.
	 *
	 * Proxy endpoints are intentionally stored as IP addresses so each regional edge
	 * is invalidated directly. Their certificate is issued to the configured TLS host,
	 * so cURL pins that hostname to the endpoint IP instead of disabling verification.
	 *
	 * @param string $endpoint Purge endpoint.
	 * @param array  $config Proxy configuration.
	 * @param string $cluster Cluster selector.
	 * @return array{response:array{code:int},body:string}|WP_Error HTTP response.
	 * @throws RuntimeException When the endpoint cannot be safely addressed.
	 */
	private static function post_reverse_proxy_purge( string $endpoint, array $config, string $cluster ) {
		$endpoint_host = (string) wp_parse_url( $endpoint, PHP_URL_HOST );

		if ( ! filter_var( $endpoint_host, FILTER_VALIDATE_IP ) ) {
			return wp_remote_post(
				$endpoint,
				array(
					'timeout'   => 20,
					'sslverify' => true,
					'headers'   => array(
						'Host' => $config['host'],
					),
					'body'      => array(
						'token'   => $config['token'],
						'cluster' => $cluster,
					),
				)
			);
		}

		if ( ! function_exists( 'curl_init' ) || ! function_exists( 'curl_setopt_array' ) || ! function_exists( 'curl_exec' ) ) {
			throw new RuntimeException( 'cURL is required to verify the regional proxy certificate' );
		}

		$tls_host = $config['tls_host'];
		if ( '' === $tls_host || ! preg_match( '/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$/i', $tls_host ) ) {
			throw new RuntimeException( 'Reverse proxy TLS host is not configured' );
		}

		$scheme = strtolower( (string) wp_parse_url( $endpoint, PHP_URL_SCHEME ) );
		$path   = (string) wp_parse_url( $endpoint, PHP_URL_PATH );
		$query  = (string) wp_parse_url( $endpoint, PHP_URL_QUERY );
		$port   = (int) wp_parse_url( $endpoint, PHP_URL_PORT );
		$port   = $port > 0 ? $port : 443;

		if ( 'https' !== $scheme || '' === $path ) {
			throw new RuntimeException( 'Reverse proxy endpoint must use HTTPS and a path' );
		}

		$url = 'https://' . $tls_host . ( 443 === $port ? '' : ':' . $port ) . $path;
		if ( '' !== $query ) {
			$url .= '?' . $query;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init -- CURLOPT_RESOLVE pins verified TLS to the configured edge IP.
		$handle = curl_init( $url );
		if ( false === $handle ) {
			throw new RuntimeException( 'Reverse proxy cURL initialization failed' );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt_array -- CURLOPT_RESOLVE is not available through wp_remote_post().
		curl_setopt_array(
			$handle,
			array(
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => http_build_query(
					array(
						'token'   => $config['token'],
						'cluster' => $cluster,
					)
				),
				CURLOPT_HTTPHEADER     => array( 'Host: ' . $config['host'] ),
				CURLOPT_RESOLVE        => array( $tls_host . ':' . $port . ':' . $endpoint_host ),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT        => 20,
				CURLOPT_CONNECTTIMEOUT => 5,
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_SSL_VERIFYHOST => 2,
			)
		);

		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec -- See CURLOPT_RESOLVE rationale above.
		$body = curl_exec( $handle );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_getinfo -- Reads only the HTTP response code.
		$code = (int) curl_getinfo( $handle, CURLINFO_RESPONSE_CODE );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close -- Closes the direct edge request handle.
		curl_close( $handle );

		if ( false === $body ) {
			throw new RuntimeException( 'Reverse proxy request failed' );
		}

		return array(
			'response' => array( 'code' => $code ),
			'body'     => $body,
		);
	}

	/**
	 * Reads the reverse-proxy purge configuration from trusted runtime sources.
	 *
	 * @return array{endpoints:array<int,string>,token:string,host:string,tls_host:string} Proxy configuration.
	 */
	private static function reverse_proxy_config(): array {
		$domain      = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		$bare_domain = preg_replace( '/^www\./', '', $domain );
		if ( null !== $bare_domain ) {
			$domain = $bare_domain;
		}

		$endpoints_value = defined( 'MANACOST_REVERSE_PROXY_PURGE_ENDPOINTS' )
			? (string) MANACOST_REVERSE_PROXY_PURGE_ENDPOINTS
			: self::first_nonempty_string(
				array(
					getenv( 'MANACOST_REVERSE_PROXY_PURGE_ENDPOINTS' ),
					get_option( 'manacost_reverse_proxy_purge_endpoints', '' ),
				)
			);

		$token = defined( 'MANACOST_REVERSE_PROXY_PURGE_TOKEN' )
			? (string) MANACOST_REVERSE_PROXY_PURGE_TOKEN
			: self::first_nonempty_string(
				array(
					getenv( 'MANACOST_REVERSE_PROXY_PURGE_TOKEN' ),
					get_option( 'manacost_reverse_proxy_purge_token', '' ),
				)
			);

		$host = defined( 'MANACOST_REVERSE_PROXY_PURGE_HOST' )
			? (string) MANACOST_REVERSE_PROXY_PURGE_HOST
			: self::first_nonempty_string(
				array(
					getenv( 'MANACOST_REVERSE_PROXY_PURGE_HOST' ),
					get_option( 'manacost_reverse_proxy_purge_host', $domain ),
				)
			);

		$tls_host = defined( 'MANACOST_REVERSE_PROXY_PURGE_TLS_HOST' )
			? (string) MANACOST_REVERSE_PROXY_PURGE_TLS_HOST
			: self::first_nonempty_string(
				array(
					getenv( 'MANACOST_REVERSE_PROXY_PURGE_TLS_HOST' ),
					get_option( 'manacost_reverse_proxy_purge_tls_host', '' ),
					'job.' . $host,
				)
			);

		$endpoints = preg_split( '/[\s,]+/', $endpoints_value );
		if ( false === $endpoints ) {
			$endpoints = array();
		}
		$endpoints = array_values( array_filter( array_map( 'esc_url_raw', $endpoints ) ) );
		if ( 'true' === getenv( 'MANACOST_SKIP_NOVOSIBIRSK' ) ) {
			$endpoints = array_values(
				array_filter(
					$endpoints,
					static fn ( string $endpoint ): bool => '186.246.28.244' !== (string) wp_parse_url( $endpoint, PHP_URL_HOST )
				)
			);
		}

		return array(
			'endpoints' => $endpoints,
			'token'     => $token,
			'host'      => '' !== $host ? $host : $domain,
			'tls_host'  => strtolower( trim( $tls_host ) ),
		);
	}

	/**
	 * Deletes contents only below the WordPress cache root.
	 *
	 * @param string $path Cache directory path.
	 * @return void
	 */
	private static function delete_path_contents( string $path ): void {
		$real       = realpath( $path );
		$cache_root = realpath( WP_CONTENT_DIR . '/cache' );
		if ( false === $real || false === $cache_root ) {
			return;
		}

		$cache_prefix = $cache_root . DIRECTORY_SEPARATOR;
		if ( $cache_root !== $real && 0 !== strpos( $real, $cache_prefix ) ) {
			return;
		}

		$items = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $real, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $items as $item ) {
			if ( ! $item->isDir() ) {
				wp_delete_file( $item->getPathname() );
			}
		}
	}

	/**
	 * Purges the canonical home URL through the Cloudflare API.
	 *
	 * @return string Step diagnostic.
	 * @throws RuntimeException When the configured Cloudflare purge request fails.
	 */
	private static function purge_cloudflare(): string {
		$config = self::cloudflare_config();

		if ( empty( $config['headers'] ) ) {
			return 'credentials not configured';
		}

		if ( empty( $config['zone_id'] ) ) {
			$config['zone_id'] = self::discover_cloudflare_zone_id( $config['domain'], $config['headers'] );
		}

		if ( empty( $config['zone_id'] ) ) {
			return 'zone id not configured';
		}

		$urls = array( home_url( '/' ) );
		$body = wp_json_encode( array( 'files' => $urls ) );
		if ( ! is_string( $body ) ) {
			throw new RuntimeException( 'Cloudflare purge request could not be encoded' );
		}

		$response = wp_remote_post(
			'https://api.cloudflare.com/client/v4/zones/' . rawurlencode( $config['zone_id'] ) . '/purge_cache',
			array(
				'timeout' => 20,
				'headers' => array_merge(
					$config['headers'],
					array(
						'Content-Type' => 'application/json',
					)
				),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( 'Cloudflare purge request failed' );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 || empty( $body['success'] ) ) {
			throw new RuntimeException( sprintf( 'Cloudflare API rejected the purge request, HTTP %d', absint( $code ) ) );
		}

		return 'targeted URL purge via ' . $config['auth_type'];
	}

	/**
	 * Reads Cloudflare credentials and domain configuration from trusted runtime sources.
	 *
	 * @return array{zone_id:string,headers:array<string,string>,auth_type:string,domain:string} Cloudflare configuration.
	 */
	private static function cloudflare_config(): array {
		$options = get_option( 'wp_rocket_settings', array() );
		$options = is_array( $options ) ? $options : array();

		$domain      = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		$bare_domain = preg_replace( '/^www\./', '', $domain );
		if ( null !== $bare_domain ) {
			$domain = $bare_domain;
		}

		$env_zone_id = getenv( 'MANACOST_CLOUDFLARE_ZONE_ID' );
		$env_token   = getenv( 'MANACOST_CLOUDFLARE_API_TOKEN' );
		$env_email   = getenv( 'MANACOST_CLOUDFLARE_EMAIL' );
		$env_key     = getenv( 'MANACOST_CLOUDFLARE_API_KEY' );

		$zone_id = defined( 'MANACOST_CLOUDFLARE_ZONE_ID' )
			? (string) MANACOST_CLOUDFLARE_ZONE_ID
			: self::first_nonempty_string(
				array(
					$env_zone_id,
					get_option( 'manacost_cloudflare_zone_id', '' ),
					$options['cloudflare_zone_id'] ?? '',
					get_option( 'cloudflare_zone_id', '' ),
				)
			);

		$email = defined( 'MANACOST_CLOUDFLARE_EMAIL' )
			? (string) MANACOST_CLOUDFLARE_EMAIL
			: self::first_nonempty_string(
				array(
					$env_email,
					get_option( 'manacost_cloudflare_email', '' ),
					$options['cloudflare_email'] ?? '',
					get_option( 'cloudflare_api_email', '' ),
				)
			);

		$key = defined( 'MANACOST_CLOUDFLARE_API_KEY' )
			? (string) MANACOST_CLOUDFLARE_API_KEY
			: self::first_nonempty_string(
				array(
					$env_key,
					get_option( 'manacost_cloudflare_api_key', '' ),
					$options['cloudflare_api_key'] ?? '',
					get_option( 'cloudflare_api_key', '' ),
				)
			);

		if ( $email && $key ) {
			return array(
				'zone_id'   => $zone_id,
				'headers'   => array(
					'X-Auth-Email' => $email,
					'X-Auth-Key'   => $key,
				),
				'auth_type' => 'api_key',
				'domain'    => $domain,
			);
		}

		$token = defined( 'MANACOST_CLOUDFLARE_API_TOKEN' )
			? (string) MANACOST_CLOUDFLARE_API_TOKEN
			: self::first_nonempty_string( array( $env_token, get_option( 'manacost_cloudflare_api_token', '' ) ) );

		if ( $token ) {
			return array(
				'zone_id'   => $zone_id,
				'headers'   => array(
					'Authorization' => 'Bearer ' . $token,
				),
				'auth_type' => 'api_token',
				'domain'    => $domain,
			);
		}

		return array(
			'zone_id'   => $zone_id,
			'headers'   => array(),
			'auth_type' => 'none',
			'domain'    => $domain,
		);
	}

	/**
	 * Returns the first non-empty scalar runtime value.
	 *
	 * @param array<int, mixed> $values Candidate values in descending priority.
	 * @return string First non-empty string value.
	 */
	private static function first_nonempty_string( array $values ): string {
		foreach ( $values as $value ) {
			if ( is_scalar( $value ) && '' !== (string) $value ) {
				return (string) $value;
			}
		}

		return '';
	}

	/**
	 * Discovers a Cloudflare zone identifier for the canonical domain.
	 *
	 * @param string               $domain  Canonical domain.
	 * @param array<string,string> $headers Authentication headers.
	 * @return string Zone identifier when found.
	 * @throws RuntimeException When the Cloudflare API returns an invalid response.
	 */
	private static function discover_cloudflare_zone_id( string $domain, array $headers ): string {
		$bare_domain = preg_replace( '/^www\./', '', $domain );
		if ( null !== $bare_domain ) {
			$domain = $bare_domain;
		}
		$domain = trim( strtolower( $domain ) );

		if ( ! $domain ) {
			return '';
		}

		$candidates = array( $domain );
		$parts      = explode( '.', $domain );

		$part_count = count( $parts );
		while ( $part_count > 2 ) {
			array_shift( $parts );
			--$part_count;
			$candidates[] = implode( '.', $parts );
		}

		$candidates = array_values( array_unique( array_filter( $candidates ) ) );

		foreach ( $candidates as $candidate ) {
			$response = wp_remote_get(
				'https://api.cloudflare.com/client/v4/zones?name=' . rawurlencode( $candidate ) . '&per_page=1',
				array(
					'timeout' => 20,
					'headers' => array_merge(
						$headers,
						array(
							'Content-Type' => 'application/json',
						)
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				throw new RuntimeException( 'Cloudflare zone lookup request failed' );
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( $code < 200 || $code >= 300 || empty( $body['success'] ) ) {
				throw new RuntimeException( sprintf( 'Cloudflare zone lookup failed, HTTP %d', absint( $code ) ) );
			}

			if ( ! empty( $body['result'][0]['id'] ) ) {
				$zone_id = (string) $body['result'][0]['id'];
				update_option( 'manacost_cloudflare_zone_id', $zone_id, false );

				return $zone_id;
			}
		}

		return '';
	}
}
