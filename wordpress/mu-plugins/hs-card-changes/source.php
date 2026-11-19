<?php
/**
 * Bounded, cached transport to the public card-history API.
 *
 * Resolved pairs are immutable and cached for a long time; every failure
 * degrades to the last good copy or to plates without images. Nothing here
 * ever throws into a page render.
 *
 * @package HSManacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * History provider client with a per-card cache, failure cooldown and a concurrency cap.
 *
 * @phpstan-import-type Resolution from HS_Card_Changes_Pairs
 */
final class HS_Card_Changes_Source {
	private const HOST        = 'api.kolodahearthstone.com';
	private const MAX_BODY    = 524288;
	private const CONCURRENCY = 4;
	private const MAX_PAGES   = 3;
	private const BUDGET      = 9;
	private const PAIR_TTL    = 30 * DAY_IN_SECONDS;

	/**
	 * Resolve one card for a patch date. Always returns a usable structure.
	 *
	 * @param string $mode Card mode.
	 * @param string $card Card identifier.
	 * @param string $date Normalized UTC date.
	 * @phpstan-return Resolution
	 * @return array<string,mixed>
	 */
	public static function resolve( string $mode, string $card, string $date ): array {
		$cached = self::cached( $mode, $card, $date );
		if ( null !== $cached ) {
			return $cached;
		}
		self::prefetch( array( array( $mode, $card, $date ) ) );
		return self::cached( $mode, $card, $date ) ?? self::last_good( $mode, $card, $date ) ?? self::unavailable();
	}

	/**
	 * Fill the cache for several cards with at most four parallel requests.
	 *
	 * @param list<array{0:string,1:string,2:string}> $requests Mode, card and date triples.
	 */
	public static function prefetch( array $requests ): void {
		$missing = array();
		foreach ( $requests as $request ) {
			if ( null === self::cached( ...$request ) && HS_Card_Changes_Pairs::valid_card( $request[1] ) && in_array( $request[0], HS_Card_Changes_Pairs::modes(), true ) ) {
				$missing[ self::key( ...$request ) ] = $request;
			}
		}
		if ( ! $missing || self::disabled() || false !== get_transient( 'hs_cc_cooldown' ) ) {
			return;
		}
		$started = microtime( true );
		foreach ( array_chunk( $missing, self::CONCURRENCY, true ) as $chunk ) {
			if ( microtime( true ) - $started > self::BUDGET ) {
				return;
			}
			do_action( 'hs_card_changes_batch', count( $chunk ) );
			self::fetch_chunk( $chunk );
			if ( false !== get_transient( 'hs_cc_cooldown' ) ) {
				return;
			}
		}
	}

	/**
	 * Operator kill switch: cached data keeps rendering, no request leaves the server.
	 */
	private static function disabled(): bool {
		return defined( 'HS_CARD_CHANGES_REMOTE_ENABLED' ) && ! HS_CARD_CHANGES_REMOTE_ENABLED;
	}

	/**
	 * Fetch the first history page of each card in parallel and resolve them.
	 *
	 * @param array<string,array{0:string,1:string,2:string}> $chunk Requests keyed by cache key.
	 */
	private static function fetch_chunk( array $chunk ): void {
		$urls = array();
		foreach ( $chunk as $key => $request ) {
			$urls[ $key ] = self::url( $request[0], $request[1], 1 );
		}
		$responses = self::http( $urls );
		foreach ( $chunk as $key => $request ) {
			$versions = self::versions( $responses[ $key ] ?? null, $request );
			if ( null !== $versions ) {
				self::store( $request, HS_Card_Changes_Pairs::pick( $versions, $request[0], $request[2] ) );
			}
		}
	}

	/**
	 * Decode a first page and follow older pages only while the patch date is not yet covered.
	 *
	 * @param array{status:int,type:string,body:string,retry:int}|null $response First-page response.
	 * @param array{0:string,1:string,2:string}                        $request Mode, card and date.
	 * @return list<mixed>|null Null when the provider failed.
	 */
	private static function versions( ?array $response, array $request ): ?array {
		$page = self::decode( $response );
		if ( null === $page ) {
			return null;
		}
		$versions = $page['versions'];
		$total    = $page['pages'];
		$last     = min( $total, self::MAX_PAGES );
		$number   = 2;
		while ( $number <= $last && self::needs_older( $versions, $request[2] ) ) {
			$older = self::decode( self::http( array( 'older' => self::url( $request[0], $request[1], $number ) ) )['older'] ?? null );
			if ( null === $older ) {
				return null;
			}
			$versions = array_merge( $versions, $older['versions'] );
			++$number;
		}
		return $versions;
	}

	/**
	 * History is newest first: older pages matter while the oldest loaded row is not before the date.
	 *
	 * @param array<int,mixed> $versions Loaded versions.
	 * @param string           $date Patch date.
	 */
	private static function needs_older( array $versions, string $date ): bool {
		$last = $versions ? end( $versions ) : null;
		return is_array( $last ) && is_string( $last['observed_at'] ?? null ) && substr( $last['observed_at'], 0, 19 ) >= $date;
	}

	/**
	 * Provider URL built only from validated parts.
	 *
	 * @param string $mode Card mode.
	 * @param string $card Card identifier.
	 * @param int    $page Page number.
	 */
	private static function url( string $mode, string $card, int $page ): string {
		$url = 'https://' . self::HOST . '/api/v1/card-history/' . HS_Card_Changes_Pairs::mode( $mode )['entity'] . '/' . rawurlencode( $card );
		return $page > 1 ? $url . '?page=' . $page : $url;
	}

	/**
	 * Fetch URLs concurrently. Result entries are normalized or null on a transport failure.
	 *
	 * @param array<string,string> $urls URLs keyed by caller identifier.
	 * @return array<string,array{status:int,type:string,body:string,retry:int}|null>
	 */
	private static function http( array $urls ): array {
		$args    = array(
			'timeout'             => 5,
			'redirection'         => 0,
			'limit_response_size' => self::MAX_BODY + 1,
			'user-agent'          => 'ManacostCardChanges/1.0 (+https://hs-manacost.ru)',
			'headers'             => array( 'Accept' => 'application/json' ),
		);
		$results = array();
		$pending = array();
		foreach ( $urls as $id => $url ) {
			$pre = apply_filters( 'pre_http_request', false, $args, $url );
			if ( false !== $pre ) {
				$results[ $id ] = self::normalize( $pre );
			} else {
				$pending[ $id ] = array(
					'url'     => $url,
					'headers' => $args['headers'],
					'type'    => 'GET',
				);
			}
		}
		if ( $pending ) {
			$options = array(
				'timeout'         => $args['timeout'],
				'connect_timeout' => 3,
				'redirects'       => 0,
				'useragent'       => $args['user-agent'],
				'max_bytes'       => $args['limit_response_size'],
				'verify'          => defined( 'ABSPATH' ) && defined( 'WPINC' ) ? ABSPATH . WPINC . '/certificates/ca-bundle.crt' : true,
			);
			foreach ( WpOrg\Requests\Requests::request_multiple( $pending, $options ) as $id => $response ) {
				$results[ $id ] = self::normalize( $response );
			}
		}
		return $results;
	}

	/**
	 * Reduce WordPress array responses and Requests objects to one shape.
	 *
	 * @param mixed $response Transport result.
	 * @return array{status:int,type:string,body:string,retry:int}|null
	 */
	private static function normalize( $response ): ?array {
		if ( is_array( $response ) && isset( $response['response']['code'] ) ) {
			return array(
				'status' => (int) $response['response']['code'],
				'type'   => self::text( wp_remote_retrieve_header( $response, 'content-type' ) ),
				'body'   => wp_remote_retrieve_body( $response ),
				'retry'  => self::retry_delay( self::text( wp_remote_retrieve_header( $response, 'retry-after' ) ) ),
			);
		}
		if ( $response instanceof WpOrg\Requests\Response ) {
			return array(
				'status' => (int) $response->status_code,
				'type'   => self::text( $response->headers['content-type'] ?? '' ),
				'body'   => $response->body,
				'retry'  => self::retry_delay( self::text( $response->headers['retry-after'] ?? '' ) ),
			);
		}
		return null;
	}

	/**
	 * Header values may be arrays when repeated; only the plain string form is used.
	 *
	 * @param mixed $value Header value.
	 */
	private static function text( $value ): string {
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Honor numeric and HTTP-date Retry-After inside a bounded window.
	 *
	 * @param string $value Header value.
	 */
	private static function retry_delay( string $value ): int {
		if ( '' === $value ) {
			return 60;
		}
		$delay = ctype_digit( $value ) ? (int) $value : (int) strtotime( $value ) - time();
		return max( 60, min( 3600, $delay ) );
	}

	/**
	 * Validate a response and start the cooldown on failure.
	 *
	 * Absent history (HTTP 404) is a normal "no data" outcome, not a provider failure.
	 *
	 * @param array{status:int,type:string,body:string,retry:int}|null $response Normalized response.
	 * @return array{versions:list<mixed>,pages:int}|null
	 */
	private static function decode( ?array $response ): ?array {
		if ( null === $response ) {
			self::fail( 60, 'unavailable' );
			return null;
		}
		if ( 404 === $response['status'] ) {
			do_action( 'hs_card_changes_provider_result', 'not_found' );
			return array(
				'versions' => array(),
				'pages'    => 0,
			);
		}
		$data = 200 === $response['status'] && strlen( $response['body'] ) <= self::MAX_BODY && 1 === preg_match( '#^application/json(?:\s*;|$)#i', $response['type'] )
			? json_decode( $response['body'], true, 32 ) : null;
		$rows = is_array( $data ) && is_array( $data['data'] ?? null ) ? $data['data'] : null;
		if ( null === $rows || ! is_array( $rows['versions'] ?? null ) || ! array_is_list( $rows['versions'] ) || count( $rows['versions'] ) > 100 ) {
			self::fail( 200 === $response['status'] ? 60 : $response['retry'], 'http_' . $response['status'] );
			return null;
		}
		do_action( 'hs_card_changes_provider_result', 'success' );
		$pages = is_array( $rows['pagination'] ?? null ) ? (int) ( $rows['pagination']['total_pages'] ?? 1 ) : 1;
		return array(
			'versions' => $rows['versions'],
			'pages'    => max( 1, min( 1000, $pages ) ),
		);
	}

	/**
	 * Provider-wide cooldown so a failing API is not retried by every request.
	 *
	 * @param int    $seconds Cooldown length.
	 * @param string $code Safe operational outcome.
	 */
	private static function fail( int $seconds, string $code ): void {
		set_transient( 'hs_cc_cooldown', $code, $seconds );
		do_action( 'hs_card_changes_provider_result', $code );
	}

	/**
	 * Cache key of one resolution.
	 *
	 * @param string $mode Card mode.
	 * @param string $card Card identifier.
	 * @param string $date Normalized UTC date.
	 */
	private static function key( string $mode, string $card, string $date ): string {
		return hash( 'sha256', $mode . '|' . $card . '|' . $date );
	}

	/**
	 * Valid cached resolution, if any.
	 *
	 * @param string $mode Card mode.
	 * @param string $card Card identifier.
	 * @param string $date Normalized UTC date.
	 * @phpstan-return Resolution|null
	 * @return array<string,mixed>|null
	 */
	private static function cached( string $mode, string $card, string $date ): ?array {
		return self::valid( get_transient( 'hs_cc_pair_' . self::key( $mode, $card, $date ) ) );
	}

	/**
	 * Last good copy kept longer than the short-lived negative results.
	 *
	 * @param string $mode Card mode.
	 * @param string $card Card identifier.
	 * @param string $date Normalized UTC date.
	 * @phpstan-return Resolution|null
	 * @return array<string,mixed>|null
	 */
	private static function last_good( string $mode, string $card, string $date ): ?array {
		return self::valid( get_transient( 'hs_cc_good_' . self::key( $mode, $card, $date ) ) );
	}

	/**
	 * Cached values cross a trust boundary too; re-check the shape.
	 *
	 * @param mixed $value Cached candidate.
	 * @phpstan-return Resolution|null
	 * @return array<string,mixed>|null
	 */
	private static function valid( $value ): ?array {
		if ( ! is_array( $value ) || ! in_array( $value['state'] ?? '', array( 'pair', 'single', 'none' ), true ) || ! is_string( $value['name'] ?? null ) || ! is_array( $value['plates'] ?? null ) ) {
			return null;
		}
		$sides = array();
		foreach ( array( 'before', 'after' ) as $side ) {
			$row = $value[ $side ] ?? null;
			if ( null === $row ) {
				$sides[ $side ] = null;
				continue;
			}
			if ( ! is_array( $row ) || ! is_string( $row['hash'] ?? null ) || 1 !== preg_match( '/^[a-f0-9]{64}$/D', $row['hash'] ) || ! is_string( $row['at'] ?? null ) ) {
				return null;
			}
			$sides[ $side ] = array(
				'hash' => $row['hash'],
				'at'   => $row['at'],
			);
		}
		$plates = array();
		foreach ( $value['plates'] as $plate ) {
			$clean = is_array( $plate ) ? self::plate( $plate ) : null;
			if ( null === $clean ) {
				return null;
			}
			$plates[] = $clean;
		}
		return array(
			'state'  => (string) $value['state'],
			'name'   => $value['name'],
			'before' => $sides['before'],
			'after'  => $sides['after'],
			'plates' => $plates,
		);
	}

	/**
	 * A cached plate with only string fields.
	 *
	 * @param array<mixed> $plate Cached plate.
	 * @return array{key:string,label:string,old:string,new:string,tone:string}|null
	 */
	private static function plate( array $plate ): ?array {
		foreach ( array( 'key', 'label', 'old', 'new', 'tone' ) as $field ) {
			if ( ! is_string( $plate[ $field ] ?? null ) ) {
				return null;
			}
		}
		return array(
			'key'   => $plate['key'],
			'label' => $plate['label'],
			'old'   => $plate['old'],
			'new'   => $plate['new'],
			'tone'  => $plate['tone'],
		);
	}

	/**
	 * Store a resolution. Only a complete pair is immutable; the rest may change as history grows.
	 *
	 * @param array{0:string,1:string,2:string} $request Mode, card and date.
	 * @param array<string,mixed>               $resolution Resolution.
	 */
	private static function store( array $request, array $resolution ): void {
		$ttl = 'pair' === $resolution['state'] ? self::PAIR_TTL : ( 'none' === $resolution['state'] ? HOUR_IN_SECONDS : 10 * MINUTE_IN_SECONDS );
		set_transient( 'hs_cc_pair_' . self::key( ...$request ), $resolution, $ttl );
		if ( 'none' !== $resolution['state'] ) {
			set_transient( 'hs_cc_good_' . self::key( ...$request ), $resolution, self::PAIR_TTL );
		}
	}

	/**
	 * Result when the provider is down and nothing was cached: render plates without images.
	 *
	 * @phpstan-return Resolution
	 * @return array<string,mixed>
	 */
	private static function unavailable(): array {
		return array(
			'state'  => 'unavailable',
			'name'   => '',
			'before' => null,
			'after'  => null,
			'plates' => array(),
		);
	}
}
