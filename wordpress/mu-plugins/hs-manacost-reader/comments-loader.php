<?php
/**
 * Staging-only discussion integration. Native comments and WP identities stay disabled.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/comments-editorial.php';

/** Register opt-in public shells, never a WordPress authentication adapter. */
function hs_reader_comments_bootstrap(): void {
	if ( ! hs_reader_comments_enabled() ) {
		return;
	}
	require_once __DIR__ . '/comments.php';
	require_once __DIR__ . '/public-profile.php';
	add_action( 'rest_api_init', 'hs_reader_editorial_routes' );
	add_filter( 'comments_template', 'hs_reader_comments_template', 100 );
	add_action( 'wp_enqueue_scripts', 'hs_reader_comments_assets', 20 );
}

/** Whether this is a public-profile request, including malformed IDs. */
function hs_reader_public_profile_request(): bool {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public, read-only routing; no private identity is read.
	return hs_reader_comments_enabled() && isset( $_GET['reader'] );
}

/** Read one opaque public UUID; invalid input must not fall back to a private editor. */
function hs_reader_public_profile_id(): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public routing, strictly validated below.
	$value = isset( $_GET['reader'] ) && is_string( $_GET['reader'] ) ? sanitize_text_field( wp_unslash( $_GET['reader'] ) ) : '';
	return preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value ) ? strtolower( $value ) : '';
}

/**
 * Replace the native template only for reviewed pilot articles.
 *
 * @param string $template Original template path.
 */
function hs_reader_comments_template( string $template ): string {
	return is_singular( 'post' ) && hs_reader_comment_article( (int) get_the_ID() )['allowed']
		? __DIR__ . '/reader-comments-page.php' : $template;
}

/** Load each community bundle only on the matching eligible public shell. */
function hs_reader_comments_assets(): void {
	$page   = hs_manacost_reader_page();
	$public = $page && is_page( $page->ID ) && hs_reader_public_profile_request();
	$thread = is_singular( 'post' ) && hs_reader_comment_article( (int) get_the_ID() )['allowed'];
	if ( ! $public && ! $thread ) {
		return;
	}
	$base = content_url( 'mu-plugins/hs-manacost-reader/' );
	wp_enqueue_style( 'hs-manacost-reader-ui', $base . 'ui.css', array(), '0.7.7' );
	wp_enqueue_style( 'hs-manacost-reader-comments', $base . 'comments.css', array( 'hs-manacost-reader-ui' ), '0.7.7' );
	wp_enqueue_script(
		$public ? 'hs-manacost-reader-public-profile' : 'hs-manacost-reader-comments',
		$base . ( $public ? 'public-profile.js' : 'comments.js' ),
		array(),
		'0.7.7',
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
}
