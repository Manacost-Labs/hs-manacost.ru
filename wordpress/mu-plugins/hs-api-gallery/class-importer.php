<?php
/**
 * Import or reuse an immutable media snapshot.
 *
 * @package Manacost
 */

namespace Manacost\ApiGallery;

defined( 'ABSPATH' ) || exit;

/** Import or reuse an immutable media snapshot. */
final class Importer {
	private const MAX_BYTES = 8388608;

	/**
	 * Import or reuse an immutable media snapshot.
	 *
	 * @param int    $post_id Destination article.
	 *
	 * @param string $library Provider library.
	 * @param string $id Provider object ID.
	 *
	 * @param string $variant Static image variant.
	 * @return int|\WP_Error Attachment or error.
	 */
	public static function import( int $post_id, string $library, string $id, string $variant ) {
		global $wpdb;
		$lock = 'hs_gallery_' . substr( hash( 'sha256', $wpdb->prefix . ':' . $post_id . ':' . $library . ':' . $id . ':' . $variant ), 0, 48 );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- A connection-scoped MariaDB lease serializes snapshots and is released on disconnect, including crashes.
		$acquired = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,0)', $lock ) );
		if ( '1' !== $acquired ) {
			return new \WP_Error( 'gallery_busy', __( 'Это изображение уже сохраняется. Повторите попытку через несколько секунд.', 'manacost' ) );
		}
		try {
			return self::snapshot( $post_id, $library, $id, $variant );
		} finally {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Release the exact lease acquired above; no persistent state or cache is involved.
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		}
	}

	/**
	 * Create or reuse a snapshot while holding the import lease.
	 *
	 * @param int    $post_id Destination article.
	 * @param string $library Provider library.
	 * @param string $id Provider object ID.
	 * @param string $variant Static image variant.
	 * @return int|\WP_Error Attachment or error.
	 */
	private static function snapshot( int $post_id, string $library, string $id, string $variant ) {
		$key = hash( 'sha256', $library . ':' . $id . ':' . $variant );
		$old = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_parent'    => $post_id,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One bounded lookup on an explicit import; never on article rendering.
				'meta_key'       => '_hs_api_gallery_snapshot',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Exact snapshot hash scoped to this article for retry reuse.
				'meta_value'     => $key,
			)
		);
		if ( $old ) {
			return (int) $old[0];
		}
		$item = Catalog::resolve( $library, $id, $variant );
		if ( is_wp_error( $item ) ) {
			return $item;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$tmp = wp_tempnam( 'hs-gallery' );
		if ( ! $tmp ) {
			return new \WP_Error( 'gallery_temp', __( 'Не удалось подготовить загрузку.', 'manacost' ) );
		}
		try {
			$result = self::download( $item['url'], $tmp );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			return self::attach( $tmp, $item, $post_id, $key, $variant );
		} finally {
			wp_delete_file( $tmp );
		}
	}

	/**
	 * Safe streaming download. Redirects must stay on the same explicit host set.
	 *
	 * @param string $url Image URL.
	 * @param string $tmp Temporary path.
	 *
	 * @return true|\WP_Error
	 */
	private static function download( string $url, string $tmp ) {
		for ( $attempt = 0; $attempt < 3; ++$attempt ) {
			if ( ! Catalog::image_url( $url ) ) {
				break;
			}
			$response = wp_safe_remote_get(
				$url,
				array(
					'timeout'             => 8,
					'redirection'         => 0,
					'stream'              => true,
					'filename'            => $tmp,
					'limit_response_size' => self::MAX_BYTES + 1,
				),
			);
			if ( is_wp_error( $response ) ) {
				return new \WP_Error( 'gallery_download', __( 'Изображение не загрузилось. Повторите попытку.', 'manacost' ) );
			}
			$status = wp_remote_retrieve_response_code( $response );
			if ( 200 === $status ) {
				return true;
			}
			if ( ! in_array( $status, array( 301, 302, 303, 307, 308 ), true ) ) {
				break;
			}
			$location = wp_remote_retrieve_header( $response, 'location' );
			$url      = is_string( $location ) ? \WP_Http::make_absolute_url( $location, $url ) : '';
		}
		return new \WP_Error( 'gallery_download', __( 'Источник изображения недоступен.', 'manacost' ) );
	}

	/**
	 * Verify actual bytes and create an attachment without replacing old media.
	 *
	 * @param string                                                 $tmp Downloaded file.
	 * @param array{library:string,id:string,name:string,url:string} $item Resolved object.
	 *
	 * @param int                                                    $post_id Destination.
	 * @param string                                                 $key Snapshot key.
	 *
	 * @param string                                                 $variant Image variant.
	 * @return int|\WP_Error
	 */
	private static function attach( string $tmp, array $item, int $post_id, string $key, string $variant ) {
		$bytes = filesize( $tmp );
		$size  = wp_getimagesize( $tmp );
		$types = array(
			'image/png'  => 'png',
			'image/jpeg' => 'jpg',
			'image/webp' => 'webp',
			'image/gif'  => 'gif',
		);
		$mime  = wp_get_image_mime( $tmp );
		if ( ! $bytes || $bytes > self::MAX_BYTES || ! $size || $size[0] * $size[1] > 24000000 || ! isset( $types[ $mime ] ) ) {
			return new \WP_Error( 'gallery_file', __( 'Нужен файл изображения до 8 МБ и 24 мегапикселей.', 'manacost' ) );
		}
		$sha = hash_file( 'sha256', $tmp );
		// Libraries may reuse provider IDs; concurrent snapshots need distinct filenames.
		$filename   = 'koloda-' . $item['id'] . '-' . $variant . '-' . wp_generate_uuid4() . '.' . $types[ $mime ];
		$attachment = media_handle_sideload(
			array(
				'name'     => sanitize_file_name( $filename ),
				'tmp_name' => $tmp,
			),
			$post_id,
			$item['name'],
			array(
				'post_title'   => $item['name'],
				'post_excerpt' => $item['name'],
			),
		);
		if ( is_wp_error( $attachment ) ) {
			return $attachment;
		}
		update_post_meta( $attachment, '_wp_attachment_image_alt', $item['name'] );
		update_post_meta( $attachment, '_hs_api_gallery_snapshot', $key );
		update_post_meta( $attachment, '_hs_api_gallery_sha256', $sha );
		update_post_meta(
			$attachment,
			'_hs_api_gallery_source',
			array(
				'library'     => $item['library'],
				'library_url' => $item['url'],
				'object_id'   => $item['id'],
				'variant'     => $variant,
			)
		);
		do_action( 'hs_api_gallery_imported', $attachment, $post_id );
		return $attachment;
	}
}
