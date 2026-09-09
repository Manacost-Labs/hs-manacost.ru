<?php
/**
 * Route-scoped integrations for the reader workspace, not article monetization.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Match only the provisioned frontend account; leave Composer previews intact. */
function hs_reader_account_request(): bool {
	if ( is_admin() || is_preview() ) {
		return false;
	}
	$page = hs_manacost_reader_page();
	return $page && is_page( $page->ID );
}

/** The account is an ad-free workspace; register no changes on other routes. */
function hs_reader_account_integrations(): void {
	if ( ! hs_reader_account_request() ) {
		return;
	}
	// Supported Ad Inserter hooks also cover its header/footer code blocks.
	add_filter( 'ai_block_insertion_check', '__return_false' );
	add_filter( 'ai_block_code', '__return_empty_string' );
	add_filter( 'ai_block_code_after_php', '__return_empty_string' );
	// Ad Inserter prints its frontend JS directly, bypassing wp_enqueue_script().
	// Keep its separate output-buffer cleanup callback intact.
	remove_action( 'wp_footer', 'ai_wp_footer_hook', 9999999 );
	remove_action( 'wp_head', 'td_header_analytics_code', 40 );
	remove_action( 'wp_footer', 'td_footer_script_code', 40 );
	remove_action( 'wp_head', array( 'Manacost_Plausible_Analytics', 'render_tracker' ), 20 );
}

/** Keep navigation/search dependencies; the account has none of these article widgets. */
function hs_reader_account_trim_assets(): void {
	if ( ! hs_reader_account_request() ) {
		return;
	}
	foreach ( array( 'tdPostImages', 'tdSocialSharing', 'tdModalPostImages', 'comment-reply' ) as $handle ) {
		wp_dequeue_script( $handle );
	}
}
