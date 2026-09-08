<?php
/**
 * Read-only editorial boundary for the isolated reader discussion pilot.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** A configured ID certifies a manually reviewed public pilot, not all legacy VIP posts. */
function hs_reader_comments_enabled(): bool {
	return defined( 'HS_MANACOST_READER_COMMENTS_ENABLED' ) && true === HS_MANACOST_READER_COMMENTS_ENABLED
		&& 'staging' === wp_get_environment_type()
		&& 'https://test.hs-manacost.ru' === home_url();
}

/**
 * Recheck current publication on every request; no visitor/WP-admin role grants access.
 *
 * @param int $post_id Editorial identifier.
 * @return array<string, bool|int|string>
 */
function hs_reader_comment_article( int $post_id ): array {
	$denied  = array(
		'postId'  => $post_id,
		'allowed' => false,
	);
	$allowed = defined( 'HS_MANACOST_READER_COMMENT_POSTS' ) ? HS_MANACOST_READER_COMMENT_POSTS : array();
	if ( ! hs_reader_comments_enabled() || ! is_array( $allowed ) || ! in_array( $post_id, $allowed, true ) ) {
		return $denied;
	}
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status || '' !== $post->post_password
		|| str_contains( $post->post_content, '[' ) ) {
		// Shortcode-controlled access is deliberately unsupported until its owner is integrated.
		return $denied;
	}
	$url = filter_var( get_permalink( $post ), FILTER_VALIDATE_URL );
	if ( ! is_string( $url ) || ! str_starts_with( $url, 'https://test.hs-manacost.ru/' ) ) {
		return $denied;
	}
	$path = wp_parse_url( $url, PHP_URL_PATH );
	if ( ! is_string( $path ) || ! str_starts_with( $path, '/' ) || str_starts_with( $path, '//' ) ) {
		return $denied;
	}
	return array(
		'postId'  => $post_id,
		'allowed' => true,
		'title'   => wp_strip_all_tags( $post->post_title ),
		'path'    => $path,
	);
}

/**
 * Authenticate only the BFF; no WP user/cookie or public reader token is accepted.
 *
 * @param WP_REST_Request $request Signed read-only batch.
 */
function hs_reader_editorial_permission( WP_REST_Request $request ): bool|WP_Error {
	$key       = defined( 'HS_MANACOST_READER_EDITORIAL_KEY' ) ? HS_MANACOST_READER_EDITORIAL_KEY : '';
	$timestamp = $request->get_header( 'x-reader-time' ) ?? '';
	$signature = $request->get_header( 'x-reader-signature' ) ?? '';
	if ( ! hs_reader_comments_enabled() || ! is_string( $key ) || strlen( $key ) < 43
		|| '' !== ( $request->get_header( 'origin' ) ?? '' ) || 'cross-site' === $request->get_header( 'sec-fetch-site' )
		|| ! preg_match( '/^[0-9]{10}$/D', $timestamp ) || abs( time() - (int) $timestamp ) > 60
		|| ! preg_match( '/^[a-f0-9]{64}$/D', $signature ) || strlen( $request->get_body() ) > 1024 ) {
		return new WP_Error( 'reader_forbidden', __( 'Нет доступа.', 'hs-manacost-reader' ), array( 'status' => 403 ) );
	}
	$payload = $request->get_method() . "\n" . $request->get_route() . "\n" . $timestamp . "\n" . $request->get_body();
	return hash_equals( hash_hmac( 'sha256', $payload, $key ), $signature )
		? true : new WP_Error( 'reader_forbidden', __( 'Нет доступа.', 'hs-manacost-reader' ), array( 'status' => 403 ) );
}

/**
 * Return only public metadata or an indistinguishable denial for each requested ID.
 *
 * @param WP_REST_Request $request Editorial IDs only, never a browser-supplied URL.
 */
function hs_reader_editorial_threads( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$input = json_decode( $request->get_body(), true );
	if ( ! is_array( $input ) || array( 'ids' ) !== array_keys( $input ) || ! is_array( $input['ids'] )
		|| ! array_is_list( $input['ids'] ) || count( $input['ids'] ) < 1 || count( $input['ids'] ) > 20 ) {
		return new WP_Error( 'reader_invalid_request', __( 'Неверный запрос.', 'hs-manacost-reader' ), array( 'status' => 400 ) );
	}
	$seen = array();
	foreach ( $input['ids'] as $post_id ) {
		if ( ! is_int( $post_id ) || $post_id < 1 || $post_id > 9007199254740991 || isset( $seen[ $post_id ] ) ) {
			return new WP_Error( 'reader_invalid_request', __( 'Неверный запрос.', 'hs-manacost-reader' ), array( 'status' => 400 ) );
		}
		$seen[ $post_id ] = true;
	}
	return new WP_REST_Response(
		array(
			'site'    => 'test.hs-manacost.ru',
			'threads' => array_map( 'hs_reader_comment_article', $input['ids'] ),
		),
		200,
		array(
			'Cache-Control' => 'private, no-store',
			'X-Robots-Tag'  => 'noindex, nofollow',
		)
	);
}

/** Register an opt-in server-to-server read; no native comments route is changed. */
function hs_reader_editorial_routes(): void {
	if ( ! hs_reader_comments_enabled() ) {
		return;
	}
	register_rest_route(
		'manacost-reader/v1',
		'/threads',
		array(
			'methods'             => 'POST',
			'callback'            => 'hs_reader_editorial_threads',
			'permission_callback' => 'hs_reader_editorial_permission',
		)
	);
}
