<?php
/**
 * Plugin Name: HS Admin Media Months Cache
 * Description: Reuses core's media month list without repeating the attachment archive scan.
 * Version: 1.0.0
 *
 * @package HS_Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cache shared date pairs, never media permissions, URLs, HTML or translated labels.
 */
final class HS_Admin_Media_Months_Cache {

	private const TTL = 600;

	/**
	 * Cold core query awaiting capture.
	 *
	 * @var array{group:string,epoch:string,built_at:int}|null
	 */
	private static ?array $pending = null;

	/**
	 * Scope the two fixed keys to the database and site, including multisite switches.
	 *
	 * @return string
	 */
	private static function group(): string {
		global $wpdb;
		return 'hs_media_months_' . hash( 'sha256', 'v1' . ( defined( 'DB_NAME' ) ? DB_NAME : '' ) . "\0" . $wpdb->prefix );
	}

	/**
	 * An equal-priority provider has ambiguous ordering; leave its results uncached.
	 *
	 * @param string $hook     Core filter name.
	 * @param int    $priority Required priority.
	 * @return bool
	 */
	private static function sole( string $hook, int $priority ): bool {
		global $wp_filter;
		return isset( $wp_filter[ $hook ] ) && 1 === count( $wp_filter[ $hook ]->callbacks[ $priority ] ?? array() );
	}

	/**
	 * Core's placeholder unescaper is safe; custom SQL rewriting is not shared.
	 *
	 * @return bool
	 */
	private static function core_query(): bool {
		global $wpdb, $wp_filter;
		foreach ( $wp_filter['query']->callbacks ?? array() as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( array( $wpdb, 'remove_placeholder_escape' ) !== $callback['function'] ) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Validate raw SQL date strings and preserve their exact types and ordering.
	 *
	 * @param mixed $rows Core rows or cached pairs.
	 * @return array<int,array{year:string,month:string}>|null
	 */
	private static function pairs( $rows ): ?array {
		if ( ! is_array( $rows ) || ! $rows || count( $rows ) > 1200 ) {
			return null;
		}
		$pairs = array();
		foreach ( $rows as $row ) {
			if ( ! is_object( $row ) && ! is_array( $row ) ) {
				return null;
			}
			$year  = is_object( $row ) ? ( $row->year ?? null ) : ( $row['year'] ?? null );
			$month = is_object( $row ) ? ( $row->month ?? null ) : ( $row['month'] ?? null );
			if ( ! is_string( $year ) || ! is_string( $month ) || ! preg_match( '/^[0-9]{1,4}$/D', $year ) || ! preg_match( '/^(?:[0-9]|1[0-2])$/D', $month ) ) {
				return null;
			}
			$pairs[] = array(
				'year'  => $year,
				'month' => $month,
			);
		}
		return $pairs;
	}

	/**
	 * A miss delegates SQL and error handling to installed WordPress core.
	 *
	 * @param mixed $months Existing provider's override, or null.
	 * @return mixed Existing override, fresh raw rows, or null for core.
	 */
	public static function months( $months ) {
		global $wpdb;
		self::$pending = null;
		if ( 'wpdb' !== get_class( $wpdb ) || ! self::core_query() || null !== $months || ! is_admin() || ! current_user_can( 'upload_files' ) || ! wp_using_ext_object_cache()
			|| ! self::sole( 'media_library_months_with_files', PHP_INT_MAX ) || ! self::sole( 'media_view_settings', PHP_INT_MIN ) ) {
			return $months;
		}

		$group  = self::group();
		$values = wp_cache_get_multiple( array( 'epoch', 'payload' ), $group, true );
		$epoch  = $values['epoch'];
		if ( ! is_string( $epoch ) || '' === $epoch ) {
			$candidate = wp_generate_uuid4();
			if ( wp_cache_add( 'epoch', $candidate, $group ) ) {
				$epoch = $candidate;
			} else {
				$epoch = wp_cache_get( 'epoch', $group, true );
			}
		}
		if ( ! is_string( $epoch ) || '' === $epoch ) {
			return null;
		}

		$payload = $values['payload'];
		if ( is_array( $payload ) && ( $payload['epoch'] ?? null ) === $epoch && is_int( $payload['built_at'] ?? null ) ) {
			$age   = time() - $payload['built_at'];
			$pairs = self::pairs( $payload['pairs'] ?? null );
			if ( $age >= 0 && $age < self::TTL && null !== $pairs ) {
				return array_map( static fn ( array $pair ): object => (object) $pair, $pairs );
			}
		}
		self::$pending = array(
			'group'    => $group,
			'epoch'    => $epoch,
			'built_at' => time(),
		);
		return null;
	}

	/**
	 * Capture before user-specific settings filters and discard translated labels.
	 *
	 * @param array<string,mixed> $settings Core media settings, unchanged.
	 * @return array<string,mixed>
	 */
	public static function capture( array $settings ): array {
		$pending       = self::$pending;
		self::$pending = null;
		if ( null === $pending || self::group() !== $pending['group'] || ! self::sole( 'media_view_settings', PHP_INT_MIN ) ) {
			return $settings;
		}
		$pairs = self::pairs( $settings['months'] ?? null );
		if ( null !== $pairs ) {
			wp_cache_set(
				'payload',
				array(
					'epoch'    => $pending['epoch'],
					'built_at' => $pending['built_at'],
					'pairs'    => $pairs,
				),
				$pending['group'],
				self::TTL
			);
		}
		return $settings;
	}

	/**
	 * Successful attachment mutations advance the generation before later hooks run.
	 *
	 * @return void
	 */
	public static function invalidate(): void {
		$group = self::group();
		if ( ! wp_cache_set( 'epoch', wp_generate_uuid4(), $group ) ) {
			wp_cache_delete( 'epoch', $group );
		}
		wp_cache_delete( 'payload', $group );
	}

	/**
	 * Core supplies the original post after successful deletion.
	 *
	 * @param int     $post_id Unused deleted ID.
	 * @param WP_Post $post    Deleted post.
	 * @return void
	 */
	public static function deleted( int $post_id, WP_Post $post ): void {
		unset( $post_id );
		if ( 'attachment' === $post->post_type ) {
			self::invalidate();
		}
	}

	/**
	 * Also cover attachments converted to other post types through wp_update_post().
	 *
	 * @param int     $post_id Unused updated ID.
	 * @param WP_Post $after   Updated post.
	 * @param WP_Post $before  Original post.
	 * @return void
	 */
	public static function updated( int $post_id, WP_Post $after, WP_Post $before ): void {
		unset( $post_id );
		if ( 'attachment' === $before->post_type && 'attachment' !== $after->post_type ) {
			self::invalidate();
		}
	}
}

add_filter( 'media_library_months_with_files', array( 'HS_Admin_Media_Months_Cache', 'months' ), PHP_INT_MAX );
add_filter( 'media_view_settings', array( 'HS_Admin_Media_Months_Cache', 'capture' ), PHP_INT_MIN );
add_action( 'add_attachment', array( 'HS_Admin_Media_Months_Cache', 'invalidate' ), PHP_INT_MIN );
add_action( 'edit_attachment', array( 'HS_Admin_Media_Months_Cache', 'invalidate' ), PHP_INT_MIN );
add_action( 'deleted_post', array( 'HS_Admin_Media_Months_Cache', 'deleted' ), PHP_INT_MIN, 2 );
add_action( 'post_updated', array( 'HS_Admin_Media_Months_Cache', 'updated' ), PHP_INT_MIN, 3 );
