<?php
/**
 * Register schema setup and cleanup.
 *
 * @package Manacost
 */

namespace Manacost\ApiGallery;

defined( 'ABSPATH' ) || exit;

/** Register schema setup and cleanup. */
final class Votes {
	/**
	 * Register schema setup and cleanup.
	 */
	public static function register(): void {
		add_action( 'admin_init', array( self::class, 'install' ) );
		add_action( 'hs_api_gallery_expire_votes', array( self::class, 'expire' ) );
		add_action( 'before_delete_post', array( self::class, 'delete_post' ) );
		add_action( 'delete_attachment', array( self::class, 'delete_post' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( self::class, 'exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( self::class, 'erasers' ) );
	}

	/**
	 * Schema version 1: additive only; never changes existing WP tables.
	 */
	public static function install(): void {
		global $wpdb;
		if ( '1' === get_option( 'hs_api_gallery_schema' ) ) {
			return;
		}
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = $wpdb->prefix . 'hs_gallery_votes';
		$collate = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE $table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			attachment_id bigint(20) unsigned NOT NULL,
			voter_key char(64) DEFAULT NULL,
			score tinyint(3) unsigned NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY vote (post_id,attachment_id,voter_key),
			KEY article (post_id,attachment_id),
			KEY retention (updated_at),
			KEY voter (voter_key)
			) $collate;"
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The owned vote table needs prepared, atomic writes or fresh identity-scoped reads; core offers no storage API.
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) !== $table ) {
			return;
		}
		update_option( 'hs_api_gallery_schema', '1', false );
		if ( ! wp_next_scheduled( 'hs_api_gallery_expire_votes' ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'hs_api_gallery_expire_votes' );
		}
	}

	/**
	 * Atomically insert, update or delete one vote.
	 *
	 * @param int    $post_id Article.
	 * @param int    $image_id Attachment.
	 *
	 * @param string $key Voter storage key.
	 * @param int    $score 1–5, or 0 to remove this person's vote.
	 *
	 * @return bool Successful write.
	 */
	public static function save( int $post_id, int $image_id, string $key, int $score ): bool {
		global $wpdb;
		if ( $post_id < 1 || $image_id < 1 || ! preg_match( '/^[a-f0-9]{64}$/D', $key ) || $score < 0 || $score > 5 ) {
			return false;
		}
		$table = $wpdb->prefix . 'hs_gallery_votes';
		if ( 0 === $score ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The owned vote table needs prepared, atomic writes or fresh identity-scoped reads; core offers no storage API.
			return false !== $wpdb->delete(
				$table,
				array(
					'post_id'       => $post_id,
					'attachment_id' => $image_id,
					'voter_key'     => $key,
				),
				array( '%d', '%d', '%s' )
			);
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The owned vote table needs prepared, atomic writes or fresh identity-scoped reads; core offers no storage API.
		return false !== $wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (post_id,attachment_id,voter_key,score,updated_at) VALUES (%d,%d,%s,%d,%s) ON DUPLICATE KEY UPDATE score=VALUES(score), updated_at=VALUES(updated_at)',
				$table,
				$post_id,
				$image_id,
				$key,
				$score,
				gmdate( 'Y-m-d H:i:s' ),
			),
		);
	}

	/**
	 * Fresh values are returned by uncached AJAX, never baked into article caches.
	 *
	 * @param int    $post_id Article.
	 * @param int    $image_id Attachment.
	 *
	 * @param string $key Current viewer's key, possibly empty.
	 * @return array{average:float,count:int,mine:int}
	 */
	public static function summary( int $post_id, int $image_id, string $key ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The owned vote table needs prepared, atomic writes or fresh identity-scoped reads; core offers no storage API.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT AVG(score) AS average,COUNT(*) AS count,MAX(IF(voter_key=%s,score,0)) AS mine FROM %i WHERE post_id=%d AND attachment_id=%d',
				$key,
				$wpdb->prefix . 'hs_gallery_votes',
				$post_id,
				$image_id,
			),
			ARRAY_A,
		);
		return array(
			'average' => round( (float) ( $row['average'] ?? 0 ), 2 ),
			'count'   => (int) ( $row['count'] ?? 0 ),
			'mine'    => (int) ( $row['mine'] ?? 0 ),
		);
	}

	/**
	 * Read fresh scores for one public article.
	 *
	 * @param int    $post_id Article ID.
	 * @param string $key Viewer key.
	 *
	 * @return array<int,array{average:float,count:int,mine:int}>
	 */
	public static function summaries( int $post_id, string $key ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The owned vote table needs prepared, atomic writes or fresh identity-scoped reads; core offers no storage API.
		$rows   = $wpdb->get_results( $wpdb->prepare( 'SELECT attachment_id,AVG(score) AS average,COUNT(*) AS count,MAX(IF(voter_key=%s,score,0)) AS mine FROM %i WHERE post_id=%d GROUP BY attachment_id LIMIT 400', $key, $wpdb->prefix . 'hs_gallery_votes', $post_id ), ARRAY_A );
		$result = array();
		foreach ( $rows as $row ) {
			$result[ (int) $row['attachment_id'] ] = array(
				'average' => round( (float) $row['average'], 2 ),
				'count'   => (int) $row['count'],
				'mine'    => (int) $row['mine'],
			);
		}
		return $result;
	}

	/**
	 * Anonymize up to 1,000 old votes, keeping historical averages and counts.
	 */
	public static function expire(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The owned vote table needs prepared, atomic writes or fresh identity-scoped reads; core offers no storage API.
		$wpdb->query( $wpdb->prepare( 'UPDATE %i SET voter_key=NULL, updated_at=%s WHERE voter_key IS NOT NULL AND updated_at < %s LIMIT 1000', $wpdb->prefix . 'hs_gallery_votes', '2000-01-01 00:00:00', gmdate( 'Y-m-d H:i:s', time() - YEAR_IN_SECONDS ) ) );
	}

	/**
	 * Remove votes owned by deleted content.
	 *
	 * @param int $post_id Deleted article or attachment ID.
	 */
	public static function delete_post( int $post_id ): void {
		global $wpdb;
		if ( '1' === get_option( 'hs_api_gallery_schema' ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The owned vote table needs prepared, atomic writes or fresh identity-scoped reads; core offers no storage API.
			$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE post_id=%d OR attachment_id=%d', $wpdb->prefix . 'hs_gallery_votes', $post_id, $post_id ) );
		}
	}

	/**
	 * Register the WordPress privacy exporter.
	 *
	 * @param array<string,mixed> $items WP exporters.
	 * @return array<string,mixed>
	 */
	public static function exporters( array $items ): array {
		$items['hs-gallery-votes'] = array(
			'exporter_friendly_name' => __( 'Оценки галерей', 'manacost' ),
			'callback'               => array( self::class, 'export' ),
		);
		return $items;
	}

	/**
	 * Register the WordPress privacy eraser.
	 *
	 * @param array<string,mixed> $items WP erasers.
	 * @return array<string,mixed>
	 */
	public static function erasers( array $items ): array {
		$items['hs-gallery-votes'] = array(
			'eraser_friendly_name' => __( 'Оценки галерей', 'manacost' ),
			'callback'             => array( self::class, 'erase' ),
		);
		return $items;
	}

	/**
	 * Export one bounded page of account votes.
	 *
	 * @param string $email Account email.
	 * @param int    $page Export page.
	 *
	 * @return array<string,mixed>
	 */
	public static function export( string $email, int $page = 1 ): array {
		global $wpdb;
		$user = get_user_by( 'email', $email );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The owned vote table needs prepared, atomic writes or fresh identity-scoped reads; core offers no storage API.
		$rows = $user ? $wpdb->get_results( $wpdb->prepare( 'SELECT post_id,attachment_id,score FROM %i WHERE voter_key=%s ORDER BY id LIMIT 100 OFFSET %d', $wpdb->prefix . 'hs_gallery_votes', Identity::user_key( $user->ID ), max( 0, $page - 1 ) * 100 ), ARRAY_A ) : array();
		$data = array();
		foreach ( $rows as $row ) {
			$data[] = array(
				'group_id'    => 'hs-gallery',
				'group_label' => __( 'Оценки галерей', 'manacost' ),
				'item_id'     => $row['post_id'] . '-' . $row['attachment_id'],
				'data'        => array(
					array(
						'name'  => __( 'Статья', 'manacost' ),
						'value' => get_permalink( (int) $row['post_id'] ),
					),
					array(
						'name'  => __( 'Изображение', 'manacost' ),
						'value' => $row['attachment_id'],
					),
					array(
						'name'  => __( 'Оценка', 'manacost' ),
						'value' => $row['score'],
					),
				),
			);
		}
		return array(
			'data' => $data,
			'done' => count( $data ) < 100,
		);
	}

	/**
	 * Remove one bounded batch of account votes.
	 *
	 * @param string $email Account email.
	 * @return array<string,mixed>
	 */
	public static function erase( string $email ): array {
		global $wpdb;
		$user = get_user_by( 'email', $email );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The owned vote table needs prepared, atomic writes or fresh identity-scoped reads; core offers no storage API.
		$count = $user ? $wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE voter_key=%s LIMIT 100', $wpdb->prefix . 'hs_gallery_votes', Identity::user_key( $user->ID ) ) ) : 0;
		return array(
			'items_removed'  => (bool) $count,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => $count < 100,
		);
	}
}
