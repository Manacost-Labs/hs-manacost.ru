<?php
/**
 * Plugin Name: HS Gallery File Links
 * Description: Makes legacy WordPress galleries link to image files so the Manacost lightbox can open them reliably.
 * Version: 1.0.0
 * Author: Manacost
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'shortcode_atts_gallery',
	static function ( array $out, array $pairs, array $atts ): array {
		if ( empty( $atts['link'] ) ) {
			$out['link'] = 'file';
		}

		return $out;
	},
	10,
	3
);
