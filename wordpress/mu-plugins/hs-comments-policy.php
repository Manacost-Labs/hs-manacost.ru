<?php
/**
 * Plugin Name: HS Comments Policy
 * Description: Disables public comments while preserving stored comments and admin access.
 * Version: 1.0.0
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hides public comment lists without modifying stored comments or admin lists.
 *
 * @param WP_Comment[] $comments Existing comments selected by WordPress.
 * @return WP_Comment[]
 */
function hs_comments_policy_frontend_comments( array $comments ): array {
	return is_admin() ? $comments : array();
}

/**
 * Hides public comment-count text while retaining admin labels and raw counts.
 *
 * @param string $number_text Formatted comment-count text.
 * @return string
 */
function hs_comments_policy_frontend_number( string $number_text ): string {
	return is_admin() ? $number_text : '';
}

/** Hides legacy theme counter wrappers without loading styles in the admin. */
function hs_comments_policy_enqueue_styles(): void {
	if ( is_admin() ) {
		return;
	}

	wp_register_style( 'hs-comments-policy', false, array(), '1.0.0' );
	wp_enqueue_style( 'hs-comments-policy' );
	wp_add_inline_style(
		'hs-comments-policy',
		'.td-post-comments,.td-module-comments{display:none!important;}'
	);
}

add_filter( 'comments_open', '__return_false', PHP_INT_MAX );
add_filter( 'pings_open', '__return_false', PHP_INT_MAX );
add_filter( 'comments_array', 'hs_comments_policy_frontend_comments', PHP_INT_MAX );
add_filter( 'comments_number', 'hs_comments_policy_frontend_number', PHP_INT_MAX );
add_action( 'wp_enqueue_scripts', 'hs_comments_policy_enqueue_styles' );
