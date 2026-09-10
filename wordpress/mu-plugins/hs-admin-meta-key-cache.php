<?php
/**
 * Plugin Name: HS Admin Meta Key Cache
 * Description: Reuses the Custom Fields key dropdown without rescanning the postmeta archive.
 * Version: 1.0.0
 *
 * @package HS_Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cache only core's shared key names, never metadata values or editor HTML.
 */
final class HS_Admin_Meta_Key_Cache {

	private const GROUP = 'hs_admin_meta_keys';

	/**
	 * Successful by-ID renames can remove public keys even when the new key is private.
	 *
	 * @var array<string, bool>
	 */
	private static array $pending_renames = array();

	/**
	 * Supply the same bounded key list that core would otherwise query.
	 *
	 * @param mixed $keys A preceding plugin's override, or null for core behavior.
	 * @return mixed Preserved override, cached key names, or null for core fallback.
	 */
	public static function keys( $keys ) {
		if ( null !== $keys || ! is_admin() ) {
			return $keys;
		}

		$limit = (int) apply_filters( 'postmeta_form_limit', 30 );
		if ( $limit < 1 || $limit > 1000 ) {
			return null;
		}

		// A late fill belongs to the old generation if a write races this query.
		$cache_key = 'v1:' . $limit . ':' . wp_cache_get_last_changed( self::GROUP );
		$cached    = wp_cache_get( $cache_key, self::GROUP );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Core has no key-list API; cache the exact meta_form() query without loading metadata values.
		$keys = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT meta_key
				FROM $wpdb->postmeta
				WHERE meta_key NOT BETWEEN '_' AND '_z'
				HAVING meta_key NOT LIKE %s
				ORDER BY meta_key
				LIMIT %d",
				$wpdb->esc_like( '_' ) . '%',
				$limit
			)
		);
		if ( '' !== $wpdb->last_error ) {
			return null;
		}

		wp_cache_set( $cache_key, $keys, self::GROUP, 5 * MINUTE_IN_SECONDS );
		return $keys;
	}

	/**
	 * Observe a possible rename without changing whether WordPress allows it.
	 *
	 * @param mixed        $check      Existing metadata API override.
	 * @param int          $meta_id    Metadata row ID.
	 * @param mixed        $meta_value Unused metadata value; never cached.
	 * @param string|false $meta_key   Proposed key, or false for a value-only update.
	 * @return mixed The existing metadata API override, unchanged.
	 */
	public static function track_rename( $check, $meta_id, $meta_value, $meta_key ) {
		unset( $meta_value );
		if ( null === $check && is_string( $meta_key ) ) {
			global $wpdb;
			self::$pending_renames[ $wpdb->postmeta . ':' . $meta_id ] = true;
		}
		return $check;
	}

	/**
	 * Invalidate only this dropdown after a successful metadata insertion/deletion.
	 *
	 * @param int|int[] $meta_id  Updated row ID, or a list of deleted row IDs.
	 * @param int       $post_id  Unused post ID; core's key list is site-wide.
	 * @param string    $meta_key Changed key, or empty when all keys were deleted.
	 * @return void
	 */
	public static function invalidate( $meta_id, $post_id, $meta_key ): void {
		unset( $meta_id, $post_id );
		if ( str_starts_with( $meta_key, '_' ) ) {
			return;
		}
		wp_cache_set_last_changed( self::GROUP );
	}

	/**
	 * Value updates, including public view counters, cannot change the key list.
	 *
	 * @param int $meta_id Successfully updated metadata row ID.
	 * @return void
	 */
	public static function invalidate_rename( $meta_id ): void {
		global $wpdb;
		$pending_key = $wpdb->postmeta . ':' . $meta_id;
		if ( isset( self::$pending_renames[ $pending_key ] ) ) {
			unset( self::$pending_renames[ $pending_key ] );
			wp_cache_set_last_changed( self::GROUP );
		}
	}
}

add_filter( 'postmeta_form_keys', array( 'HS_Admin_Meta_Key_Cache', 'keys' ), PHP_INT_MAX );
add_filter( 'update_post_metadata_by_mid', array( 'HS_Admin_Meta_Key_Cache', 'track_rename' ), PHP_INT_MAX, 4 );
add_action( 'added_post_meta', array( 'HS_Admin_Meta_Key_Cache', 'invalidate' ), 10, 3 );
add_action( 'updated_post_meta', array( 'HS_Admin_Meta_Key_Cache', 'invalidate_rename' ) );
add_action( 'deleted_post_meta', array( 'HS_Admin_Meta_Key_Cache', 'invalidate' ), 10, 3 );
