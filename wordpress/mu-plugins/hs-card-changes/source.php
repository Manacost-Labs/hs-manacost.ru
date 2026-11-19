<?php
/**
 * Bounded, cached transport to the public card-history API.
 *
 * Visitors read the cache only. Misses and stale short-lived results are refreshed
 * in the background (one deduplicated job, per-key lock, shared time budget), resolved
 * pairs are immutable. Every failure degrades to the stale copy or to plates without
 * images and never throws into a page render.
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
	private const BUDGET      = 20;
	private const QUEUE_TTL   = 5 * MINUTE_IN_SECONDS;
	private const COOLDOWN    = 'hs_cc_cooldown';
	private const SEARCH_COOL = 'hs_cc_search_cooldown';

	/**
	 * Cooldown end per transient key set by this process, so a later shorter delay never cuts an earlier longer one.
	 *
	 * @var array<string,int>
	 */
	private static array $cooldown_until = array();

	/** Background hooks; page views and saves only schedule them. */
	public static function register(): void {
		add_action( 'hs_card_changes_refresh', array( self::class, 'cron_refresh' ), 10, 3 );
	}

	/**
	 * Visitor path: reads the cache only, never the network.
	 *
	 * A miss or a stale short-lived result is served as it is (or as plates without images) and one
	 * deduplicated background refresh is scheduled when the caller names a queue target.
	 *
	 * @param string   $mode Card mode.
	 * @param string   $card Card identifier.
	 * @param string   $date Normalized UTC date.
	 * @param int|null $queue_for Post ID to warm in the background, 0 for a single-card refresh, null to schedule nothing.
	 * @phpstan-return Resolution
	 * @return array<string,mixed>
	 */
	public static function lookup( string $mode, string $card, string $date, ?int $queue_for = null ): array {
		$entry = HS_Card_Changes_Cache::entry( $mode, $card, $date );
		if ( ( null === $entry || ! $entry['fresh'] ) && null !== $queue_for ) {
			self::queue( array( $mode, $card, $date ), $queue_for );
		}
		return $entry['resolution'] ?? HS_Card_Changes_Cache::unavailable();
	}

	/**
	 * Editor path (admin AJAX only): fetch now, then answer from the cache.
	 *
	 * @param string $mode Card mode.
	 * @param string $card Card identifier.
	 * @param string $date Normalized UTC date.
	 * @phpstan-return Resolution
	 * @return array<string,mixed>
	 */
	public static function resolve_live( string $mode, string $card, string $date ): array {
		self::refresh( array( array( $mode, $card, $date ) ) );
		return HS_Card_Changes_Cache::entry( $mode, $card, $date )['resolution'] ?? HS_Card_Changes_Cache::unavailable();
	}

	/**
	 * Background path: refresh missing and stale entries, at most four requests in parallel.
	 *
	 * One shared time budget covers every batch and every older page. A per-key lock keeps
	 * concurrent workers from fetching the same card twice.
	 *
	 * @param list<array{0:string,1:string,2:string}> $requests Mode, card and date triples.
	 */
	public static function refresh( array $requests ): void {
		if ( self::disabled() || false !== get_transient( self::COOLDOWN ) ) {
			return;
		}
		$claimed = array();
		foreach ( $requests as $request ) {
			$key   = HS_Card_Changes_Cache::key( ...$request );
			$entry = HS_Card_Changes_Cache::entry( ...$request );
			if ( HS_Card_Changes_Pairs::valid_card( $request[1] ) && in_array( $request[0], HS_Card_Changes_Pairs::modes(), true ) && ( null === $entry || ! $entry['fresh'] ) && ! isset( $claimed[ $key ] ) && HS_Card_Changes_Cache::lock( $key ) ) {
				$claimed[ $key ] = $request;
			}
		}
		$deadline = microtime( true ) + self::BUDGET;
		foreach ( array_chunk( $claimed, self::CONCURRENCY, true ) as $chunk ) {
			if ( microtime( true ) > $deadline ) {
				break;
			}
			do_action( 'hs_card_changes_batch', count( $chunk ) );
			self::fetch_chunk( $chunk, $deadline );
			if ( false !== get_transient( self::COOLDOWN ) ) {
				break;
			}
		}
		foreach ( array_keys( $claimed ) as $key ) {
			HS_Card_Changes_Cache::unlock( $key );
		}
	}

	/**
	 * Cron callback for a single-card refresh.
	 *
	 * @param string $mode Card mode.
	 * @param string $card Card identifier.
	 * @param string $date Normalized UTC date.
	 */
	public static function cron_refresh( string $mode, string $card, string $date ): void {
		self::refresh( array( array( $mode, $card, $date ) ) );
		delete_transient( 'hs_cc_queued_' . HS_Card_Changes_Cache::key( $mode, $card, $date ) );
	}

	/**
	 * Forget a queue marker once its background job has run.
	 *
	 * @param int $post_id Warmed post.
	 */
	public static function dequeue_post( int $post_id ): void {
		delete_transient( 'hs_cc_queued_post_' . $post_id );
	}

	/**
	 * Schedule one deduplicated refresh. Nothing is scheduled during an outage or when disabled.
	 *
	 * @param array{0:string,1:string,2:string} $request Mode, card and date.
	 * @param int                               $post_id Post to warm as a whole, or 0 for the single card.
	 */
	public static function queue( array $request, int $post_id ): void {
		if ( self::disabled() || false !== get_transient( self::COOLDOWN ) ) {
			return;
		}
		$marker = 'hs_cc_queued_' . ( $post_id > 0 ? 'post_' . $post_id : HS_Card_Changes_Cache::key( ...$request ) );
		if ( false !== get_transient( $marker ) ) {
			return;
		}
		set_transient( $marker, 1, self::QUEUE_TTL );
		if ( $post_id > 0 ) {
			wp_schedule_single_event( time(), 'hs_card_changes_warm', array( $post_id ) );
		} else {
			wp_schedule_single_event( time(), 'hs_card_changes_refresh', $request );
		}
	}

	/**
	 * Operator kill switch and WP_HTTP_BLOCK_EXTERNAL: cached data keeps rendering, no request leaves the server.
	 */
	private static function disabled(): bool {
		if ( defined( 'HS_CARD_CHANGES_REMOTE_ENABLED' ) && ! HS_CARD_CHANGES_REMOTE_ENABLED ) {
			return true;
		}
		return function_exists( '_wp_http_get_object' ) && _wp_http_get_object()->block_request( self::origin() . '/' );
	}

	/**
	 * Provider origin. Only a disposable "local" environment may point it at a loopback test server.
	 */
	private static function origin(): string {
		$origin = 'https://' . self::HOST;
		if ( function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type() ) {
			$filtered = apply_filters( 'hs_card_changes_origin', $origin );
			if ( is_string( $filtered ) && 1 === preg_match( '#^https?://(?:127\.0\.0\.1|localhost)(?::\d{1,5})?$#D', $filtered ) ) {
				return $filtered;
			}
		}
		return $origin;
	}

	/**
	 * Editor search (admin AJAX only): GET provider list endpoints in parallel through the same bounded transport.
	 *
	 * Honours the kill switch and WP_HTTP_BLOCK_EXTERNAL. Search has its own cooldown: a search outage never
	 * pauses card history resolution or the background refresh.
	 *
	 * @param array<string,string> $paths Provider paths with query strings, keyed by caller identifier.
	 * @return array<string,list<mixed>|null> Rows per identifier; null when the provider failed or is unavailable.
	 */
	public static function get_lists( array $paths ): array {
		$out = array_fill_keys( array_keys( $paths ), null );
		if ( ! $paths || self::disabled() || false !== get_transient( self::SEARCH_COOL ) ) {
			return $out;
		}
		$urls = array();
		foreach ( $paths as $id => $path ) {
			$urls[ $id ] = self::origin() . $path;
		}
		foreach ( self::http( $urls ) as $id => $response ) {
			$out[ $id ] = self::decode_list( $response );
		}
		return $out;
	}

	/**
	 * Validate a list response. A missing list (404) is an empty result, never an outage.
	 *
	 * @param array{status:int,type:string,body:string,retry:int}|null $response Normalized response.
	 * @return list<mixed>|null
	 */
	private static function decode_list( ?array $response ): ?array {
		if ( null === $response ) {
			self::fail( 60, 'unavailable', self::SEARCH_COOL );
			return null;
		}
		if ( 404 === $response['status'] ) {
			return array();
		}
		$data = 200 === $response['status'] && strlen( $response['body'] ) <= self::MAX_BODY && 1 === preg_match( '#^application/json(?:\s*;|$)#i', $response['type'] )
			? json_decode( $response['body'], true, 32 ) : null;
		$rows = is_array( $data ) ? ( $data['data'] ?? null ) : null;
		// json_decode() turns {"data":{}} into an empty array: an empty list must be a JSON array.
		if ( array() === $rows && 1 !== preg_match( '#"data"\s*:\s*\[\s*\]#', $response['body'] ) ) {
			$rows = null;
		}
		if ( ! is_array( $rows ) || ! array_is_list( $rows ) || count( $rows ) > 200 ) {
			self::fail( 200 === $response['status'] ? 60 : $response['retry'], 'http_' . $response['status'], self::SEARCH_COOL );
			return null;
		}
		do_action( 'hs_card_changes_provider_result', 'success' );
		return $rows;
	}

	/**
	 * Fetch the first history page of each card in parallel and resolve them.
	 *
	 * @param array<string,array{0:string,1:string,2:string}> $chunk Requests keyed by cache key.
	 * @param float                                           $deadline Shared time budget end (microtime).
	 */
	private static function fetch_chunk( array $chunk, float $deadline ): void {
		$urls = array();
		foreach ( $chunk as $key => $request ) {
			$urls[ $key ] = self::url( $request[0], $request[1], 1 );
		}
		$responses = self::http( $urls );
		foreach ( $chunk as $key => $request ) {
			$versions = self::versions( $responses[ $key ] ?? null, $request, $deadline );
			if ( null !== $versions ) {
				HS_Card_Changes_Cache::store( $request, HS_Card_Changes_Pairs::pick( $versions, $request[0], $request[2] ) );
			}
		}
	}

	/**
	 * Decode a first page and follow older pages only while the patch date is not yet covered.
	 *
	 * @param array{status:int,type:string,body:string,retry:int}|null $response First-page response.
	 * @param array{0:string,1:string,2:string}                        $request Mode, card and date.
	 * @param float                                                    $deadline Shared time budget end (microtime).
	 * @return list<mixed>|null Null when the provider failed or the budget ended.
	 */
	private static function versions( ?array $response, array $request, float $deadline ): ?array {
		$page = self::decode( $response );
		if ( null === $page ) {
			return null;
		}
		$versions = $page['versions'];
		$total    = $page['pages'];
		$last     = min( $total, self::MAX_PAGES );
		$number   = 2;
		while ( $number <= $last && self::needs_older( $versions, $request[2] ) ) {
			if ( microtime( true ) > $deadline ) {
				return null;
			}
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
	 * Pages are newest first: older pages matter while even the oldest loaded observation is not before the date.
	 *
	 * @param array<int,mixed> $versions Loaded versions.
	 * @param string           $date Patch date.
	 */
	private static function needs_older( array $versions, string $date ): bool {
		$oldest = '';
		foreach ( $versions as $version ) {
			$at = is_array( $version ) ? HS_Card_Changes_Pairs::observed( $version['observed_at'] ?? null ) : '';
			if ( '' !== $at && ( '' === $oldest || $at < $oldest ) ) {
				$oldest = $at;
			}
		}
		return '' !== $oldest && $oldest >= $date;
	}

	/**
	 * Provider URL built only from validated parts.
	 *
	 * @param string $mode Card mode.
	 * @param string $card Card identifier.
	 * @param int    $page Page number.
	 */
	private static function url( string $mode, string $card, int $page ): string {
		$url = self::origin() . '/api/v1/card-history/' . HS_Card_Changes_Pairs::mode( $mode )['entity'] . '/' . rawurlencode( $card );
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
			try {
				foreach ( WpOrg\Requests\Requests::request_multiple( $pending, $options ) as $id => $response ) {
					$results[ $id ] = self::normalize( $response );
				}
			} catch ( Throwable $error ) {
				// A transport failure is an outage, never a fatal error.
				foreach ( array_keys( $pending ) as $id ) {
					$results[ $id ] = null;
				}
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
	 * Cooldown so a failing API is not retried by every request. Within a batch the longest delay wins.
	 *
	 * @param int    $seconds Cooldown length.
	 * @param string $code Safe operational outcome.
	 * @param string $key History (default) or search cooldown.
	 */
	private static function fail( int $seconds, string $code, string $key = self::COOLDOWN ): void {
		$until = time() + $seconds;
		if ( false !== get_transient( $key ) && ( self::$cooldown_until[ $key ] ?? 0 ) > $until ) {
			$until = self::$cooldown_until[ $key ];
		}
		self::$cooldown_until[ $key ] = $until;
		set_transient( $key, $code, max( 1, $until - time() ) );
		do_action( 'hs_card_changes_provider_result', $code );
	}
}
