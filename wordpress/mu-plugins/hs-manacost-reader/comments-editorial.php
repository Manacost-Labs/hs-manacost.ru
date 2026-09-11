<?php
/**
 * Read-only editorial boundary for the isolated reader discussion pilot.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Enable reader features only on their isolated staging origin. */
function hs_reader_comments_enabled(): bool {
	return defined( 'HS_MANACOST_READER_COMMENTS_ENABLED' ) && true === HS_MANACOST_READER_COMMENTS_ENABLED
		&& 'staging' === wp_get_environment_type()
		&& 'https://test.hs-manacost.ru' === home_url();
}

/**
 * Recheck public article safety once per request; no visitor/WP-admin role grants access.
 *
 * A single page can ask for the same article through the discussion template,
 * favorite control and asset loader. This intentionally stays request-local:
 * it avoids duplicate WordPress lookups without retaining visibility metadata
 * between visitors or requests.
 *
 * @param int $post_id Editorial identifier.
 * @return array<string, bool|int|string>
 */
function hs_reader_public_article( int $post_id ): array {
	static $articles = array();
	if ( isset( $articles[ $post_id ] ) ) {
		return $articles[ $post_id ];
	}
	$denied = array(
		'postId'  => $post_id,
		'allowed' => false,
	);
	if ( ! hs_reader_comments_enabled() ) {
		$articles[ $post_id ] = $denied;
		return $articles[ $post_id ];
	}
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status || '' !== $post->post_password
		|| str_contains( $post->post_content, '[' ) ) {
		// Shortcode-controlled access is deliberately unsupported until its owner is integrated.
		$articles[ $post_id ] = $denied;
		return $articles[ $post_id ];
	}
	$url = filter_var( get_permalink( $post ), FILTER_VALIDATE_URL );
	if ( ! is_string( $url ) || ! str_starts_with( $url, 'https://test.hs-manacost.ru/' ) ) {
		$articles[ $post_id ] = $denied;
		return $articles[ $post_id ];
	}
	$path = wp_parse_url( $url, PHP_URL_PATH );
	if ( ! is_string( $path ) || ! str_starts_with( $path, '/' ) || str_starts_with( $path, '//' ) ) {
		$articles[ $post_id ] = $denied;
		return $articles[ $post_id ];
	}
	$articles[ $post_id ] = array(
		'postId'  => $post_id,
		'allowed' => true,
		'title'   => wp_strip_all_tags( $post->post_title ),
		'path'    => $path,
	);
	return $articles[ $post_id ];
}

/**
 * A configured ID certifies a manually reviewed discussion pilot, not all legacy VIP posts.
 *
 * @param int $post_id Editorial post identifier.
 * @return array<string, bool|int|string>
 */
function hs_reader_comment_article( int $post_id ): array {
	$denied  = array(
		'postId'  => $post_id,
		'allowed' => false,
	);
	$allowed = defined( 'HS_MANACOST_READER_COMMENT_POSTS' ) ? HS_MANACOST_READER_COMMENT_POSTS : array();
	if ( ! is_array( $allowed ) || ! in_array( $post_id, $allowed, true ) ) {
		return $denied;
	}
	return hs_reader_public_article( $post_id );
}

/**
 * Favorites are useful on every safe, published public article; they do not enable comments.
 *
 * @param int $post_id Editorial post identifier.
 * @return array<string, bool|int|string>
 */
function hs_reader_favorite_article( int $post_id ): array {
	return hs_reader_public_article( $post_id );
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
 * Validate a short signed ID batch before returning public metadata.
 *
 * @param WP_REST_Request $request Editorial IDs only, never a browser-supplied URL.
 * @return list<int>|WP_Error
 */
function hs_reader_editorial_ids( WP_REST_Request $request ): array|WP_Error {
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
	return $input['ids'];
}

/**
 * Return only public metadata or an indistinguishable denial for each requested ID.
 *
 * @param WP_REST_Request $request Signed ID batch.
 * @param callable        $article Article eligibility resolver.
 */
function hs_reader_editorial_response( WP_REST_Request $request, callable $article ): WP_REST_Response|WP_Error {
	$ids = hs_reader_editorial_ids( $request );
	if ( is_wp_error( $ids ) ) {
		return $ids;
	}
	return new WP_REST_Response(
		array(
			'site'    => 'test.hs-manacost.ru',
			'threads' => array_map( $article, $ids ),
		),
		200,
		array(
			'Cache-Control' => 'private, no-store',
			'X-Robots-Tag'  => 'noindex, nofollow',
		)
	);
}

/**
 * Signed discussion-pilot metadata.
 *
 * @param WP_REST_Request $request Signed discussion-pilot batch.
 * @return WP_REST_Response|WP_Error
 */
function hs_reader_editorial_threads( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	return hs_reader_editorial_response( $request, 'hs_reader_comment_article' );
}

/**
 * Signed favorite metadata for every safe public post.
 *
 * @param WP_REST_Request $request Signed favorite metadata batch.
 * @return WP_REST_Response|WP_Error
 */
function hs_reader_editorial_favorites( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	return hs_reader_editorial_response( $request, 'hs_reader_favorite_article' );
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
	register_rest_route(
		'manacost-reader/v1',
		'/favorites',
		array(
			'methods'             => 'POST',
			'callback'            => 'hs_reader_editorial_favorites',
			'permission_callback' => 'hs_reader_editorial_permission',
		)
	);
}
