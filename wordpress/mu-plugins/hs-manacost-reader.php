<?php
/**
 * Plugin Name: HS Manacost Reader
 * Description: Opt-in anonymous account shell for the independent HearthPulse reader service.
 * Version: 0.6.0
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** No page creation, user mapping or authentication happens inside WordPress. */
function hs_manacost_reader_bootstrap(): void {
	if ( ! defined( 'HS_MANACOST_READER_ENABLED' ) || true !== HS_MANACOST_READER_ENABLED ) {
		return;
	}
	require_once __DIR__ . '/hs-manacost-reader/account.php';
	require_once __DIR__ . '/hs-manacost-reader/account-assets.php';
	require_once __DIR__ . '/hs-manacost-reader/comments-loader.php';
	hs_reader_comments_bootstrap();
	add_shortcode( 'hs_manacost_reader_account', 'hs_manacost_reader_account_shell' );
	add_filter( 'wp_nav_menu_items', 'hs_manacost_reader_menu', 20, 2 );
	add_filter( 'template_include', 'hs_manacost_reader_template' );
	add_action( 'wp_enqueue_scripts', 'hs_manacost_reader_assets' );
	add_action( 'wp_enqueue_scripts', 'hs_reader_account_trim_assets', 1000 );
	add_action( 'wp', 'hs_reader_account_integrations', 20 );
	add_action( 'template_redirect', 'hs_manacost_reader_cache_policy' );
}

/** Require an explicitly provisioned page; never take over an existing account route. */
function hs_manacost_reader_page(): ?WP_Post {
	$page = get_page_by_path( 'account' );
	return $page && 'publish' === $page->post_status && has_shortcode( $page->post_content, 'hs_manacost_reader_account' ) ? $page : null;
}

/**
 * Give the account a uniquely named template that Composer does not remap.
 *
 * @param string $template Theme template selected by WordPress.
 */
function hs_manacost_reader_template( string $template ): string {
	$page = hs_manacost_reader_page();
	return $page && is_page( $page->ID ) ? __DIR__ . '/hs-manacost-reader/reader-account-page.php' : $template;
}

/**
 * Append the account link only to the primary menu when its page exists.
 *
 * @param string   $items Existing menu markup.
 * @param stdClass $args  WordPress menu rendering arguments.
 */
function hs_manacost_reader_menu( string $items, stdClass $args ): string {
	if ( 'header-menu' !== ( $args->theme_location ?? '' ) ) {
		return $items;
	}
	$page = hs_manacost_reader_page();
	if ( ! $page ) {
		return $items;
	}
	$url = get_permalink( $page );
	return $items . '<li class="menu-item mc-reader-entry"><a data-mc-reader-entry href="'
		. esc_url( $url ) . '">Кабинет</a></li>';
}

/** Load the scoped account bundle only on its explicitly provisioned page. */
function hs_manacost_reader_assets(): void {
	$page = hs_manacost_reader_page();
	if ( ! $page || ! is_page( $page->ID ) ) {
		return;
	}
	$base = content_url( 'mu-plugins/hs-manacost-reader/' );
	wp_enqueue_style( 'hs-manacost-reader', $base . 'reader.css', array(), '0.6.0' );
	if ( hs_reader_public_profile_request() ) {
		return;
	}
	wp_enqueue_script(
		'hs-manacost-reader-profile-editor',
		$base . 'profile-editor.js',
		array(),
		'0.6.0',
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
	wp_enqueue_script(
		'hs-manacost-reader',
		$base . 'reader.js',
		array( 'hs-manacost-reader-profile-editor' ),
		'0.6.0',
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
}

/** Keep the account page out of shared caches and search indexes. */
function hs_manacost_reader_cache_policy(): void {
	$page = hs_manacost_reader_page();
	if ( $page && is_page( $page->ID ) ) {
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow', true );
	}
}

add_action( 'init', 'hs_manacost_reader_bootstrap' );
