<?php
/**
 * Staging-only discussion integration. Native comments and WP identities stay disabled.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/comments-editorial.php';
require_once __DIR__ . '/article-favorite.php';

/** Register opt-in public shells, never a WordPress authentication adapter. */
function hs_reader_comments_bootstrap(): void {
	if ( ! hs_reader_comments_enabled() ) {
		return;
	}
	require_once __DIR__ . '/comments.php';
	require_once __DIR__ . '/public-profile.php';
	add_action( 'rest_api_init', 'hs_reader_editorial_routes' );
	add_filter( 'comments_template', 'hs_reader_comments_template', 100 );
	add_filter( 'the_content', 'hs_reader_article_favorite_content', 20 );
	add_action( 'wp_enqueue_scripts', 'hs_reader_comments_assets', 20 );
	// These small dynamic bundles must survive minify cache cleanup and RUCSS.
	add_filter( 'rocket_exclude_js', 'hs_reader_comments_asset_exclusions' );
	add_filter( 'rocket_exclude_css', 'hs_reader_comments_css_asset_exclusions' );
	add_filter( 'rocket_delay_js_exclusions', 'hs_reader_comments_asset_exclusions' );
	add_filter( 'rocket_rucss_external_exclusions', 'hs_reader_comments_asset_exclusions' );
	add_filter( 'perfmatters_minify_js_exclusions', 'hs_reader_comments_asset_exclusions' );
	add_filter( 'perfmatters_minify_css_exclusions', 'hs_reader_comments_asset_exclusions' );
	add_filter( 'perfmatters_delay_js_exclusions', 'hs_reader_comments_asset_exclusions' );
	add_filter( 'perfmatters_rucss_excluded_stylesheets', 'hs_reader_comments_asset_exclusions' );
}

/**
 * Preserve source URLs and styles for Reader without disabling site optimization.
 *
 * @param string[] $exclusions Existing optimizer exclusions.
 * @return string[]
 */
function hs_reader_comments_asset_exclusions( array $exclusions ): array {
	return array_values( array_unique( array_merge( $exclusions, array( '/wp-content/mu-plugins/hs-manacost-reader/' ) ) ) );
}

/**
 * Rocket CSS anchors the complete pathname, unlike its JS and literal exclusions.
 *
 * @param string[] $exclusions Existing CSS exclusion patterns.
 * @return string[]
 */
function hs_reader_comments_css_asset_exclusions( array $exclusions ): array {
	return array_values( array_unique( array_merge( $exclusions, array( '/wp-content/mu-plugins/hs-manacost-reader/(.*)' ) ) ) );
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

/** Load discussion and favorite bundles only on their matching safe public surfaces. */
function hs_reader_comments_assets(): void {
	$page     = hs_manacost_reader_page();
	$public   = $page && is_page( $page->ID ) && hs_reader_public_profile_request();
	$thread   = is_singular( 'post' ) && hs_reader_comment_article( (int) get_the_ID() )['allowed'];
	$favorite = is_singular( 'post' ) && hs_reader_favorite_article( (int) get_the_ID() )['allowed'];
	if ( ! $public && ! $thread && ! $favorite ) {
		return;
	}
	$base = content_url( 'mu-plugins/hs-manacost-reader/' );
	wp_enqueue_style( 'hs-manacost-reader-ui', $base . 'ui.css', array(), hs_manacost_reader_asset_version( 'ui.css' ) );
	if ( $public || $thread ) {
		wp_enqueue_style( 'hs-manacost-reader-comments', $base . 'comments.css', array( 'hs-manacost-reader-ui' ), hs_manacost_reader_asset_version( 'comments.css' ) );
	}
	if ( $favorite ) {
		wp_enqueue_style( 'hs-manacost-reader-favorite', $base . 'article-favorite.css', array( 'hs-manacost-reader-ui' ), hs_manacost_reader_asset_version( 'article-favorite.css' ) );
		wp_enqueue_script(
			'hs-manacost-reader-favorite',
			$base . 'article-favorite.js',
			array(),
			hs_manacost_reader_asset_version( 'article-favorite.js' ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}
	if ( $thread ) {
		wp_enqueue_script(
			'hs-manacost-reader-community-ui',
			$base . 'community-ui.js',
			array(),
			hs_manacost_reader_asset_version( 'community-ui.js' ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}
	if ( $public || $thread ) {
		wp_enqueue_script(
			$public ? 'hs-manacost-reader-public-profile' : 'hs-manacost-reader-comments',
			$base . ( $public ? 'public-profile.js' : 'comments.js' ),
			$public ? array() : array( 'hs-manacost-reader-community-ui' ),
			hs_manacost_reader_asset_version( $public ? 'public-profile.js' : 'comments.js' ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}
}
