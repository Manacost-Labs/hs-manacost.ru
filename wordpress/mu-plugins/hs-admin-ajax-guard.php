<?php
/**
 * Plugin Name: HS Admin Ajax Guard
 * Description: Short-circuits noisy public AJAX actions and audits admin-ajax load safely.
 * Version: 1.1.7
 * Author: Manacost
 */

defined( 'ABSPATH' ) || exit;

final class HS_Admin_Ajax_Guard {
	private const AUDIT_LOG = '/var/log/hs-manacost-admin-ajax.log';
	private const SLOW_MS   = 1000;
	private const SEARCH_MIN_CHARS      = 3;
	private const SEARCH_MAX_PER_MINUTE = 30;

	private static float $started_at = 0.0;
	private static string $guard_result = '';

	public static function boot(): void {
		self::$started_at = microtime( true );

		if ( self::should_audit_current_request() ) {
			register_shutdown_function( [ __CLASS__, 'audit_shutdown' ] );
		}

		add_action( 'admin_init', [ __CLASS__, 'short_circuit_noisy_ajax' ], 0 );
		add_action( 'wp_ajax_td_ajax_update_post_views', [ __CLASS__, 'lightweight_newspaper_view_json' ], 0 );
		add_action( 'wp_ajax_nopriv_td_ajax_update_post_views', [ __CLASS__, 'lightweight_newspaper_view_json' ], 0 );
		add_action( 'wp_ajax_td_ajax_update_views', [ __CLASS__, 'lightweight_newspaper_view_json' ], 0 );
		add_action( 'wp_ajax_nopriv_td_ajax_update_views', [ __CLASS__, 'lightweight_newspaper_view_json' ], 0 );
		add_action( 'wp_ajax_td_ajax_get_views', [ __CLASS__, 'newspaper_views_json' ], 0 );
		add_action( 'wp_ajax_nopriv_td_ajax_get_views', [ __CLASS__, 'newspaper_views_json' ], 0 );
	}

	public static function short_circuit_noisy_ajax(): void {
		if ( ! wp_doing_ajax() ) {
			return;
		}

		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';

		if ( in_array( $action, [ 'td_ajax_update_post_views', 'td_ajax_update_views' ], true ) ) {
			self::lightweight_newspaper_view_json();
		}

		self::short_circuit_public_deck_analytics( $action );
		self::short_circuit_public_monitoring_hit( $action );
		self::short_circuit_public_action_scheduler_runner( $action );
		self::short_circuit_public_newspaper_search( $action );

		if ( ! self::is_logged_in() && self::is_public_newspaper_views_action( $action ) ) {
			self::newspaper_views_json();
		}

		if ( ! self::is_logged_in() && self::is_public_newspaper_ping( $action ) ) {
			self::no_content();
		}
	}

	private static function is_public_newspaper_views_action( string $action ): bool {
		return in_array( $action, [ 'td_ajax_update_views', 'td_ajax_get_views' ], true );
	}

	private static function is_public_newspaper_ping( string $action ): bool {
		if ( $action !== '' && $action !== 'td_ajax_update_post_views' ) {
			return false;
		}

		$theme_name = isset( $_REQUEST['td_theme_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['td_theme_name'] ) ) : '';

		return strcasecmp( $theme_name, 'Newspaper' ) === 0;
	}

	public static function no_content(): void {
		http_response_code( 204 );
		status_header( 204 );
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		exit;
	}

	public static function empty_json(): void {
		wp_send_json( [] );
	}

	public static function lightweight_newspaper_view_json(): void {
		self::record_lightweight_newspaper_view();
		self::newspaper_views_json();
	}

	public static function newspaper_views_json(): void {
		$post_ids = self::request_post_ids();

		if ( empty( $post_ids ) ) {
			self::send_legacy_json( [] );
		}

		global $wpdb;

		$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
		$sql = $wpdb->prepare(
			"SELECT post_id, meta_value
			FROM {$wpdb->postmeta}
			WHERE meta_key = %s AND post_id IN ($placeholders)",
			array_merge( [ 'post_views_count' ], $post_ids )
		);
		$rows = $wpdb->get_results( $sql, OBJECT_K );
		$views = [];

		foreach ( $post_ids as $post_id ) {
			$views[ $post_id ] = isset( $rows[ $post_id ] ) ? (int) $rows[ $post_id ]->meta_value : 0;
		}

		self::send_legacy_json( $views );
	}

	private static function send_legacy_json( array $payload ): void {
		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ), true );
		echo wp_json_encode( $payload );
		exit;
	}

	public static function audit_shutdown(): void {
		if ( ! self::should_audit_current_request() ) {
			return;
		}

		$elapsed_ms = (int) round( ( microtime( true ) - self::$started_at ) * 1000 );
		$action     = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
		$status     = http_response_code();
		$status     = $status ? (int) $status : 200;

		$entry = [
			'ts'         => gmdate( 'c' ),
			'action'     => $action !== '' ? $action : '-',
			'class'      => self::classify_action( $action ),
			'method'     => isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_key( $_SERVER['REQUEST_METHOD'] ) : '-',
			'status'     => $status,
			'elapsed_ms' => $elapsed_ms,
			'logged_in'  => self::is_logged_in() ? 1 : 0,
			'role'       => self::current_user_role(),
			'bytes_in'   => isset( $_SERVER['CONTENT_LENGTH'] ) ? min( (int) $_SERVER['CONTENT_LENGTH'], 104857600 ) : 0,
			'referer'    => self::referer_path(),
			'ip_hash'    => self::client_ip_hash(),
			'ua_hash'    => self::user_agent_hash(),
		];

		if ( self::$guard_result !== '' ) {
			$entry['guard'] = self::$guard_result;
		}

		if ( $elapsed_ms >= self::SLOW_MS || $status >= 400 ) {
			$entry['flag'] = $elapsed_ms >= self::SLOW_MS ? 'slow' : 'error';
		}

		self::write_audit_log( $entry );
	}

	private static function should_audit_current_request(): bool {
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	private static function classify_action( string $action ): string {
		if ( in_array( $action, [ 'td_ajax_update_post_views', 'td_ajax_update_views', 'td_ajax_get_views', 'manacost_monitoring_hit', 'manacost_monitoring_unique' ], true ) ) {
			return 'views';
		}

		if ( in_array( $action, [ 'hs_decks_filter', 'hs_decks_track_event', 'hs_decks_ad_impression', 'hs_deck_manager_track', 'copy_deck_code', 'vote_deck' ], true ) ) {
			return 'deck';
		}

		if ( str_starts_with( $action, 'tdc_' ) || str_starts_with( $action, 'tdb_' ) || in_array( $action, [ 'heartbeat', 'td_ajax_block', 'td_ajax_search', 'td_ajax_loop' ], true ) ) {
			return 'theme';
		}

		if ( str_starts_with( $action, 'wordfence' ) ) {
			return 'security';
		}

		if ( in_array( $action, [ 'upload-attachment', 'query-attachments', 'imagify_optimize_media', 'imagifybeat' ], true ) ) {
			return 'media';
		}

		if ( $action === 'rocket_beacon' ) {
			return 'performance';
		}

		if ( $action === 'as_async_request_queue_runner' ) {
			return 'scheduler';
		}

		return $action === '' ? 'empty' : 'other';
	}

	private static function short_circuit_public_monitoring_hit( string $action ): void {
		if ( $action !== 'manacost_monitoring_hit' || self::is_logged_in() || ( $_SERVER['REQUEST_METHOD'] ?? '' ) !== 'POST' ) {
			return;
		}

		if ( self::monitoring_uid() === '' ) {
			self::$guard_result = 'monitoring_bad_uid';
			wp_send_json_success( [ 'stored' => 0 ] );
		}

			if ( ! self::monitoring_post_id() ) {
				self::$guard_result = 'monitoring_nonpost_skipped';
				wp_send_json_success( [ 'stored' => 0 ] );
			}

				self::$guard_result = 'monitoring_view_not_counted_go_primary';

			$post_key = self::monitoring_post_dedupe_key();
			if ( $post_key !== '' && ! wp_cache_add( $post_key, 1, 'hs_ajax_guard', 300 ) ) {
				self::$guard_result = 'monitoring_post_deduped';
				wp_send_json_success( [ 'stored' => 0 ] );
		}

		$event_key = self::monitoring_event_dedupe_key();
		if ( $event_key !== '' && ! wp_cache_add( $event_key, 1, 'hs_ajax_guard', 120 ) ) {
			self::$guard_result = 'monitoring_event_deduped';
			wp_send_json_success( [ 'stored' => 0 ] );
		}

		if ( self::monitoring_minute_budget_exceeded() ) {
			self::$guard_result = 'monitoring_budget_skipped';
			wp_send_json_success( [ 'stored' => 0 ] );
		}
	}

	private static function monitoring_event_dedupe_key(): string {
		$uid = self::monitoring_uid();
		$event_id = isset( $_POST['event_id'] ) ? sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) : '';
		$event_id = preg_replace( '/[^a-zA-Z0-9_.:-]/', '', (string) $event_id );

		if ( $uid === '' || strlen( $event_id ) < 12 ) {
			return '';
		}

		return 'monitoring_event_' . md5( $uid . '|' . $event_id );
	}

	private static function monitoring_post_dedupe_key(): string {
		$uid = self::monitoring_uid();
		$post_id = self::monitoring_post_id();

		if ( $uid === '' || ! $post_id ) {
			return '';
		}

		return 'monitoring_post_' . md5( $uid . '|' . $post_id );
	}

	private static function monitoring_post_id(): int {
		return isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	}

	private static function monitoring_uid(): string {
		$uid = isset( $_POST['uid'] ) ? sanitize_text_field( wp_unslash( $_POST['uid'] ) ) : '';
		return self::normalize_monitoring_uid( $uid );
	}

	private static function monitoring_cookie_uid(): string {
		$uid = isset( $_COOKIE['mm_uid'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['mm_uid'] ) ) : '';
		return self::normalize_monitoring_uid( $uid );
	}

	private static function normalize_monitoring_uid( string $uid ): string {
		$uid = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $uid );

		if ( strlen( $uid ) < 16 || strlen( $uid ) > 96 ) {
			return '';
		}

		return $uid;
	}

	private static function monitoring_minute_budget_exceeded(): bool {
		$key   = 'monitoring_budget_' . gmdate( 'YmdHi' );
		$count = wp_cache_incr( $key, 1, 'hs_ajax_guard' );

		if ( false === $count ) {
			wp_cache_add( $key, 1, 'hs_ajax_guard', 90 );
			return false;
		}

		return (int) $count > 120;
	}

	private static function short_circuit_public_deck_analytics( string $action ): void {
		if ( self::is_logged_in() || ! in_array( $action, [ 'hs_decks_track_event', 'hs_decks_ad_impression' ], true ) ) {
			return;
		}

		if ( ! self::has_valid_hs_decks_nonce() ) {
			self::$guard_result = 'deck_bad_nonce';
			wp_send_json_error( [ 'message' => 'bad_nonce' ], 403 );
		}

		$key = $action === 'hs_decks_ad_impression'
			? self::deck_ad_dedupe_key()
			: self::deck_event_dedupe_key();

		if ( $key === '' ) {
			return;
		}

		$ttl = $action === 'hs_decks_ad_impression' ? 120 : self::deck_event_dedupe_ttl();
		if ( ! wp_cache_add( $key, 1, 'hs_ajax_guard', $ttl ) ) {
			self::$guard_result = 'deck_deduped';
			wp_send_json_success();
		}
	}

	private static function short_circuit_public_action_scheduler_runner( string $action ): void {
		if ( $action !== 'as_async_request_queue_runner' ) {
			return;
		}

		self::$guard_result = 'scheduler_cli_only';
		wp_send_json_success( [ 'queued' => 0 ] );
	}

	private static function short_circuit_public_newspaper_search( string $action ): void {
		if ( $action !== 'td_ajax_search' || self::is_logged_in() ) {
			return;
		}

		$query = self::newspaper_search_query();

		if ( self::string_length( $query ) < self::SEARCH_MIN_CHARS ) {
			self::$guard_result = 'search_short_query';
			self::send_newspaper_search_json( $query );
		}

		if ( self::newspaper_search_rate_limited() ) {
			self::$guard_result = 'search_rate_limited';
			self::send_newspaper_search_json( $query );
		}
	}

	private static function newspaper_search_query(): string {
		$query = isset( $_POST['td_string'] ) ? sanitize_text_field( wp_unslash( $_POST['td_string'] ) ) : '';
		$query = preg_replace( '/\s+/u', ' ', trim( (string) $query ) );

		return is_string( $query ) ? $query : '';
	}

	private static function newspaper_search_rate_limited(): bool {
		if ( ! function_exists( 'wp_using_ext_object_cache' ) || ! wp_using_ext_object_cache() ) {
			return false;
		}

		$key   = 'newspaper_search_' . gmdate( 'YmdHi' ) . '_' . self::client_ip_hash();
		$count = wp_cache_incr( $key, 1, 'hs_ajax_guard' );

		if ( false === $count ) {
			wp_cache_add( $key, 1, 'hs_ajax_guard', 90 );
			return false;
		}

		return (int) $count > self::SEARCH_MAX_PER_MINUTE;
	}

	private static function send_newspaper_search_json( string $query ): void {
		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ), true );
		echo wp_json_encode(
			[
				'td_data'          => '',
				'td_total_results' => 0,
				'td_total_in_list' => 0,
				'td_search_query'  => esc_attr( $query ),
			]
		);
		exit;
	}

	private static function string_length( string $value ): int {
		if ( function_exists( 'mb_strlen' ) ) {
			return (int) mb_strlen( $value );
		}

		return strlen( $value );
	}

	private static function has_valid_hs_decks_nonce(): bool {
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';

		return $nonce !== '' && (bool) wp_verify_nonce( $nonce, 'hs_decks_nonce' );
	}

	private static function deck_event_dedupe_key(): string {
		$event_type = isset( $_POST['event_type'] ) ? sanitize_key( wp_unslash( $_POST['event_type'] ) ) : '';

		if ( $event_type === '' ) {
			return '';
		}

		$parts = [
			'action'     => 'hs_decks_track_event',
			'event_type' => $event_type,
			'post_id'    => isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0,
			'post_ids'   => self::deck_event_post_ids(),
			'source'     => isset( $_POST['source'] ) ? sanitize_key( wp_unslash( $_POST['source'] ) ) : '',
			'referer'    => self::referer_path(),
			'ip'         => self::client_ip_hash(),
			'ua'         => self::user_agent_hash(),
		];

		return 'deck_event_' . md5( wp_json_encode( $parts ) );
	}

	private static function deck_event_post_ids(): array {
		$ids = [];

		if ( isset( $_POST['post_ids'] ) && is_array( $_POST['post_ids'] ) ) {
			foreach ( (array) wp_unslash( $_POST['post_ids'] ) as $post_id ) {
				$post_id = absint( $post_id );
				if ( $post_id ) {
					$ids[] = $post_id;
				}
			}
		}

		if ( empty( $ids ) && isset( $_POST['post_id'] ) ) {
			$post_id = absint( $_POST['post_id'] );
			if ( $post_id ) {
				$ids[] = $post_id;
			}
		}

		$ids = array_values( array_unique( $ids ) );
		sort( $ids, SORT_NUMERIC );

		return array_slice( $ids, 0, 30 );
	}

	private static function deck_event_dedupe_ttl(): int {
		$event_type = isset( $_POST['event_type'] ) ? sanitize_key( wp_unslash( $_POST['event_type'] ) ) : '';

		if ( $event_type === 'deck_view' ) {
			return HOUR_IN_SECONDS;
		}

		if ( in_array( $event_type, [ 'image_open', 'proof_open', 'source_click', 'archetype_open' ], true ) ) {
			return 30;
		}

		return 60;
	}

	private static function deck_ad_dedupe_key(): string {
		$ad_key = isset( $_POST['ad'] ) ? sanitize_key( wp_unslash( $_POST['ad'] ) ) : 'boosty_feed';

		if ( $ad_key === '' ) {
			return '';
		}

		$parts = [
			'action'  => 'hs_decks_ad_impression',
			'ad'      => $ad_key,
			'referer' => self::referer_path(),
			'ip'      => self::client_ip_hash(),
			'ua'      => self::user_agent_hash(),
		];

		return 'deck_ad_' . md5( wp_json_encode( $parts ) );
	}

	private static function record_lightweight_newspaper_view(): void {
		self::record_lightweight_newspaper_view_for_post( self::request_post_id(), self::monitoring_cookie_uid() );
	}

	private static function record_lightweight_newspaper_view_from_monitoring(): void {
		self::record_lightweight_newspaper_view_for_post( self::monitoring_post_id(), self::monitoring_uid() );
	}

	private static function record_lightweight_newspaper_view_for_post( int $post_id, string $visitor_uid = '' ): bool {
		if ( self::is_logged_in() && function_exists( 'current_user_can' ) && current_user_can( 'edit_posts' ) ) {
			return false;
		}

		if ( ! $post_id || 'publish' !== get_post_status( $post_id ) || 'attachment' === get_post_type( $post_id ) ) {
			return false;
		}

		if ( ! self::should_record_newspaper_view( $post_id, $visitor_uid ) ) {
			self::$guard_result = 'newspaper_view_deduped';
			return false;
		}

		self::$guard_result = 'newspaper_view_go_primary';

		return true;
	}

		private static function should_record_newspaper_view( int $post_id, string $visitor_uid = '' ): bool {
			$keys = self::newspaper_view_dedupe_keys( $post_id, $visitor_uid );

			foreach ( $keys as $key ) {
				if ( wp_cache_get( $key, 'hs_ajax_guard' ) ) {
					return false;
				}
			}

			$primary_key = $keys[0] ?? '';
			if ( $primary_key === '' || ! wp_cache_add( $primary_key, 1, 'hs_ajax_guard', 60 ) ) {
				return false;
			}

			foreach ( array_slice( $keys, 1 ) as $key ) {
				wp_cache_add( $key, 1, 'hs_ajax_guard', 60 );
			}

			return true;
		}

		private static function newspaper_view_dedupe_keys( int $post_id, string $visitor_uid = '' ): array {
			$keys = [];

			if ( $visitor_uid !== '' ) {
				$keys[] = 'newspaper_view_uid_' . md5( $post_id . '|' . $visitor_uid );
			}

			$keys[] = 'newspaper_view_' . md5( $post_id . '|' . self::client_ip_hash() . '|' . self::user_agent_hash() );

			return array_values( array_unique( $keys ) );
		}

		private static function update_newspaper_rolling_counters( int $post_id ): void {
			$current_day  = (int) date( 'N' ) - 1;
			$current_date = (int) date( 'U' );
			$current_hour = (int) date( 'G' );

			$count_7_day_array = get_post_meta( $post_id, 'post_views_count_7_day_arr', true );
			if ( ! self::is_valid_newspaper_rolling_array( $count_7_day_array ) ) {
				$count_7_day_array = self::empty_newspaper_rolling_array();
			}

			$count_7_day_array = self::normalize_newspaper_rolling_array( $count_7_day_array );
			$stored_date      = (int) ( $count_7_day_array[ $current_day ]['date'] ?? 0 );
			$stored_day_key   = $stored_date > 0 ? date( 'Y-m-d', $stored_date ) : '';
			$current_day_key  = date( 'Y-m-d', $current_date );

			if ( $stored_day_key === $current_day_key ) {
				$count_7_day_array[ $current_day ]['count']++;
				if ( ! isset( $count_7_day_array[ $current_day ]['per_hour_count'][ $current_hour ] ) ) {
					$count_7_day_array[ $current_day ]['per_hour_count'][ $current_hour ] = 0;
				}
				$count_7_day_array[ $current_day ]['per_hour_count'][ $current_hour ]++;
			} else {
				$count_7_day_array[ $current_day ] = [
					'date'           => $current_date,
					'count'          => 1,
					'per_hour_count' => [ $current_hour => 1 ],
				];

				update_post_meta( $post_id, 'post_view_7days_last_day', $current_day );
				update_post_meta( $post_id, 'post_views_count_7_day_last_date', $current_date );
			}

			$one_week_ago = $current_date - 604800;
			foreach ( $count_7_day_array as $day => $parameters ) {
				if ( $day !== $current_day && ! empty( $parameters['date'] ) && (int) $parameters['date'] < $one_week_ago ) {
					$count_7_day_array[ $day ] = [
						'date'           => 0,
						'count'          => 0,
						'per_hour_count' => [],
					];
				}
			}

			$sum_7_day_count = 0;
			foreach ( $count_7_day_array as $parameters ) {
				$sum_7_day_count += isset( $parameters['count'] ) ? (int) $parameters['count'] : 0;
			}

			update_post_meta( $post_id, 'post_views_count_7_day_arr', $count_7_day_array );
			update_post_meta( $post_id, 'post_views_count_7_day_total', $sum_7_day_count );
			update_post_meta( $post_id, 'post_views_last_24_hours', self::sum_newspaper_recent_hours( $count_7_day_array, $current_date, 24 ) );
			update_post_meta( $post_id, 'post_views_last_48_hours', self::sum_newspaper_recent_hours( $count_7_day_array, $current_date, 48 ) );
			update_post_meta( $post_id, 'post_views_last_72_hours', self::sum_newspaper_recent_hours( $count_7_day_array, $current_date, 72 ) );
		}

		private static function is_valid_newspaper_rolling_array( $value ): bool {
			return is_array( $value ) && isset( $value[0] ) && is_array( $value[0] );
		}

		private static function empty_newspaper_rolling_array(): array {
			$array = [];

			for ( $day = 0; $day < 7; $day++ ) {
				$array[ $day ] = [
					'date'           => 0,
					'count'          => 0,
					'per_hour_count' => [],
				];
			}

			return $array;
		}

		private static function normalize_newspaper_rolling_array( array $array ): array {
			for ( $day = 0; $day < 7; $day++ ) {
				if ( ! isset( $array[ $day ] ) || ! is_array( $array[ $day ] ) ) {
					$array[ $day ] = [
						'date'           => 0,
						'count'          => 0,
						'per_hour_count' => [],
					];
				}

				$array[ $day ]['date'] = isset( $array[ $day ]['date'] ) ? (int) $array[ $day ]['date'] : 0;
				$array[ $day ]['count'] = isset( $array[ $day ]['count'] ) ? (int) $array[ $day ]['count'] : 0;

				if ( ! isset( $array[ $day ]['per_hour_count'] ) || ! is_array( $array[ $day ]['per_hour_count'] ) ) {
					$array[ $day ]['per_hour_count'] = [];
				}
			}

			ksort( $array );

			return array_slice( $array, 0, 7, true );
		}

		private static function sum_newspaper_recent_hours( array $count_7_day_array, int $current_date, int $hours ): int {
			$start = $current_date - ( $hours * 3600 );
			$sum   = 0;

			foreach ( $count_7_day_array as $parameters ) {
				$date = isset( $parameters['date'] ) ? (int) $parameters['date'] : 0;
				if ( $date <= 0 ) {
					continue;
				}

				$per_hour_count = isset( $parameters['per_hour_count'] ) && is_array( $parameters['per_hour_count'] )
					? $parameters['per_hour_count']
					: [];

				if ( empty( $per_hour_count ) ) {
					if ( $date >= $start && $date <= $current_date ) {
						$sum += isset( $parameters['count'] ) ? (int) $parameters['count'] : 0;
					}
					continue;
				}

				$day_start = mktime( 0, 0, 0, (int) date( 'n', $date ), (int) date( 'j', $date ), (int) date( 'Y', $date ) );

				foreach ( $per_hour_count as $hour => $count ) {
					$hour_start = $day_start + ( (int) $hour * 3600 );
					if ( $hour_start >= $start && $hour_start <= $current_date ) {
						$sum += (int) $count;
					}
				}
			}

			return $sum;
		}

		private static function request_post_id(): int {
		$post_ids = self::request_post_ids();

		return ! empty( $post_ids[0] ) ? (int) $post_ids[0] : 0;
	}

	private static function request_post_ids(): array {
		if ( ! empty( $_REQUEST['post_id'] ) ) {
			$post_id = absint( $_REQUEST['post_id'] );

			return $post_id ? [ $post_id ] : [];
		}

		if ( empty( $_REQUEST['td_post_ids'] ) ) {
			return [];
		}

		$decoded = json_decode( stripslashes( (string) wp_unslash( $_REQUEST['td_post_ids'] ) ), true );

		if ( ! is_array( $decoded ) ) {
			return [];
		}

		$post_ids = array_values( array_unique( array_filter( array_map( 'absint', $decoded ) ) ) );

		return array_slice( $post_ids, 0, 30 );
	}

	private static function current_user_role(): string {
		if ( ! self::is_logged_in() ) {
			return 'guest';
		}

		$user = function_exists( 'wp_get_current_user' ) ? wp_get_current_user() : null;
		if ( ! $user ) {
			return 'logged_in';
		}

		$role = is_array( $user->roles ) && ! empty( $user->roles[0] ) ? $user->roles[0] : 'logged_in';

		return sanitize_key( $role );
	}

	private static function is_logged_in(): bool {
		return function_exists( 'is_user_logged_in' ) && is_user_logged_in();
	}

	private static function referer_path(): string {
		$referer = function_exists( 'wp_get_referer' )
			? wp_get_referer()
			: ( $_SERVER['HTTP_REFERER'] ?? '' );

		if ( ! $referer ) {
			return '-';
		}

		$path = function_exists( 'wp_parse_url' )
			? wp_parse_url( $referer, PHP_URL_PATH )
			: parse_url( $referer, PHP_URL_PATH );

		if ( ! is_string( $path ) || $path === '' ) {
			return '/';
		}

		return substr( $path, 0, 160 );
	}

	private static function client_ip_hash(): string {
		$ip = '';
		foreach ( [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ] as $key ) {
			if ( empty( $_SERVER[ $key ] ) ) {
				continue;
			}

			$value = (string) $_SERVER[ $key ];
			$first = trim( explode( ',', $value )[0] );
			if ( filter_var( $first, FILTER_VALIDATE_IP ) ) {
				$ip = $first;
				break;
			}
		}

		return $ip !== '' ? substr( hash_hmac( 'sha256', $ip, self::audit_salt() ), 0, 16 ) : '-';
	}

	private static function audit_salt(): string {
		if ( function_exists( 'wp_salt' ) ) {
			return wp_salt( 'auth' );
		}

		if ( defined( 'AUTH_SALT' ) ) {
			return (string) AUTH_SALT;
		}

		return __FILE__;
	}

	private static function user_agent_hash(): string {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';

		return $ua !== '' ? substr( hash( 'sha256', $ua ), 0, 12 ) : '-';
	}

	private static function write_audit_log( array $entry ): void {
		$line = function_exists( 'wp_json_encode' )
			? wp_json_encode( $entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
			: json_encode( $entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( ! is_string( $line ) || $line === '' ) {
			return;
		}

		error_log( $line . PHP_EOL, 3, self::AUDIT_LOG );
	}
}

HS_Admin_Ajax_Guard::boot();
