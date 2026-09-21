<?php
/**
 * Plugin Name: Manacost Listing Thumbnails
 * Description: Reuses an existing proportional thumbnail when a homepage card's Newspaper size is missing.
 * Version: 1.0.0
 * Author: Manacost
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Avoids downloading originals for missing homepage card sizes. */
final class Manacost_Listing_Thumbnails {

	/**
	 * Falls back only when WordPress could not find the requested theme size.
	 *
	 * @param array{0: string, 1: int, 2: int, 3: bool}|false $image Image source tuple.
	 * @param int                                             $attachment_id Attachment ID.
	 * @param string|int[]                                    $size Requested size.
	 * @param bool                                            $icon Whether an icon was requested.
	 * @return array{0: string, 1: int, 2: int, 3: bool}|false
	 */
	public static function fallback( $image, $attachment_id, $size, bool $icon ) {
		if (
			! in_array( $size, array( 'td_696x0', 'td_485x360' ), true )
			|| false === $image
			|| $image[3]
			|| $icon
			|| ! self::eligible_request()
		) {
			return $image;
		}

		if ( ! self::has_proportional_thumbnail( $attachment_id, 'td_696x0' === $size ? 696 : 485 ) ) {
			return $image;
		}

		// This different size cannot re-enter the fallback. WordPress retains URL/offload filters.
		$candidate = wp_get_attachment_image_src( $attachment_id, 'medium_large' );
		if ( false === $candidate || ! $candidate[3] || $candidate[0] === $image[0] ) {
			return $image;
		}

		return $candidate;
	}

	/**
	 * Checks real pixels, not the display dimensions constrained by WordPress.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @param int $minimum Required card width.
	 * @return bool
	 */
	private static function has_proportional_thumbnail( int $attachment_id, int $minimum ): bool {
		$metadata = wp_get_attachment_metadata( $attachment_id );
		$variant  = $metadata['sizes']['medium_large'] ?? array();
		$width    = (int) ( $variant['width'] ?? 0 );
		$height   = (int) ( $variant['height'] ?? 0 );
		$original = (int) ( $metadata['width'] ?? 0 );
		if ( $width < $minimum || $width >= $original || $height < 1 ) {
			return false;
		}

		// Keep composition, allowing one pixel of normal resize rounding.
		$expected_height = (int) round( (int) ( $metadata['height'] ?? 0 ) * $width / $original );
		return abs( $height - $expected_height ) <= 1;
	}

	/** Keeps articles, editors, previews and non-HTML requests unchanged. */
	private static function eligible_request(): bool {
		return ! is_admin()
			&& ! is_user_logged_in()
			&& ! wp_doing_ajax()
			&& ! is_preview()
			&& ! is_feed()
			&& ! is_embed()
			&& ! ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			&& ( is_front_page() || is_home() );
	}
}

add_filter( 'wp_get_attachment_image_src', array( 'Manacost_Listing_Thumbnails', 'fallback' ), 10, 4 );
