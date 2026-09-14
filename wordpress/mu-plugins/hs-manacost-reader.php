<?php
/**
 * Plugin Name: HS Manacost Reader
 * Description: Opt-in anonymous account shell for the independent HearthPulse reader service.
 * Version: 0.8.1
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Return the normalized HTTP host without accepting aliases or suffix matches. */
function hs_manacost_reader_request_host(): string {
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Compared only with an exact static allowlist.
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( trim( (string) wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : '';
	$host = preg_replace( '/:\d+\z/', '', $host );

	return rtrim( is_string( $host ) ? $host : '', '.' );
}

/** Reader UI is available only on the canonical production and staging hosts. */
function hs_manacost_reader_is_application_host(): bool {
	return in_array( hs_manacost_reader_request_host(), array( 'hs-manacost.ru', 'test.hs-manacost.ru' ), true );
}

/** Whether the current request resolves to the Reader account route. */
function hs_manacost_reader_is_account_request(): bool {
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Used only for a normalized static path comparison.
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$query_start = strpos( $request_uri, '?' );
	$path        = false === $query_start ? $request_uri : substr( $request_uri, 0, $query_start );
	$path        = rawurldecode( $path );
	$segments    = array();

	foreach ( explode( '/', $path ) as $segment ) {
		if ( '' === $segment || '.' === $segment ) {
			continue;
		}
		if ( '..' === $segment ) {
			array_pop( $segments );
			continue;
		}
		$segments[] = $segment;
	}
	$path = '/' . implode( '/', $segments );

	return 0 === strcasecmp( '/account', $path );
}

/** Mark the account response private before cache plugins decide to store it. */
function hs_manacost_reader_disable_shared_cache(): void {
	foreach ( array( 'DONOTCACHEPAGE', 'DONOTCDN', 'DONOTCACHEOBJECT' ) as $constant ) {
		if ( ! defined( $constant ) ) {
			define( $constant, true );
		}
	}
}

/**
 * Headers which keep the account shell private and out of search indexes.
 *
 * @return array<string, string>
 */
function hs_manacost_reader_private_headers(): array {
	return array(
		'Cache-Control'     => 'private, no-store, no-cache, must-revalidate, max-age=0',
		'Pragma'            => 'no-cache',
		'Expires'           => 'Wed, 11 Jan 1984 05:00:00 GMT',
		'Surrogate-Control' => 'no-store',
		'X-Robots-Tag'      => 'noindex, nofollow',
	);
}

/** Send the complete private response policy while headers are still mutable. */
function hs_manacost_reader_send_private_headers(): void {
	hs_manacost_reader_disable_shared_cache();
	if ( headers_sent() ) {
		return;
	}

	nocache_headers();
	foreach ( hs_manacost_reader_private_headers() as $name => $value ) {
		header( $name . ': ' . $value, true );
	}
}

/**
 * Keep every WordPress-resolvable account alias out of WP Rocket's early cache.
 *
 * WP Rocket evaluates this generated pattern before must-use plugins load, so
 * the production runbook regenerates its config before the page is published.
 *
 * @param array<int, string> $uris Existing rejected URI patterns.
 * @return array<int, string>
 */
function hs_manacost_reader_rocket_cache_reject_uri( array $uris ): array {
	$uris[] = '/+(?:.+/)?(?:a|%41|%61)(?:c|%43|%63)(?:c|%43|%63)(?:o|%4f|%6f)(?:u|%55|%75)(?:n|%4e|%6e)(?:t|%54|%74)(?:(?:/|%2f).*)?';

	return array_values( array_unique( $uris ) );
}

/** No page creation, user mapping or authentication happens inside WordPress. */
function hs_manacost_reader_bootstrap(): void {
	if ( ! defined( 'HS_MANACOST_READER_ENABLED' ) || true !== HS_MANACOST_READER_ENABLED ) {
		return;
	}
	add_action( 'template_redirect', 'hs_manacost_reader_account_route_policy', 0 );
	if ( ! hs_manacost_reader_is_application_host() ) {
		return;
	}
	if ( hs_manacost_reader_is_account_request() ) {
		hs_manacost_reader_disable_shared_cache();
	}
	require_once __DIR__ . '/hs-manacost-reader/assets.php';
	require_once __DIR__ . '/hs-manacost-reader/account.php';
	require_once __DIR__ . '/hs-manacost-reader/account-assets.php';
	require_once __DIR__ . '/hs-manacost-reader/comments-loader.php';
	hs_reader_comments_bootstrap();
	add_shortcode( 'hs_manacost_reader_account', 'hs_manacost_reader_account_shell' );
	add_filter( 'wp_nav_menu_items', 'hs_manacost_reader_menu', 20, 2 );
	add_filter( 'template_include', 'hs_manacost_reader_template' );
	add_action( 'wp_enqueue_scripts', 'hs_manacost_reader_assets' );
	add_action( 'wp_enqueue_scripts', 'hs_manacost_reader_tailwind_assets', 30 );
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
 * Resolve direct account query aliases without relying on host-sensitive query flags.
 *
 * @param WP_Post|null $page Published account page, when provisioned.
 * @return bool
 */
function hs_manacost_reader_resolves_account_page( ?WP_Post $page ): bool {
	if ( $page && is_page( $page->ID ) ) {
		return true;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only route classification before a fail-closed 404.
	$page_id_raw = isset( $_GET['page_id'] ) && is_scalar( $_GET['page_id'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['page_id'] ) ) : '';
	$page_id     = filter_var( $page_id_raw, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
	if ( false !== $page_id ) {
		if ( $page && (int) $page->ID === $page_id ) {
			return true;
		}
		$requested_page = get_post( $page_id );
		if ( $requested_page instanceof WP_Post && 'publish' === $requested_page->post_status && has_shortcode( $requested_page->post_content, 'hs_manacost_reader_account' ) ) {
			return true;
		}
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only route classification before a fail-closed 404.
	$pagename_raw = isset( $_GET['pagename'] ) && is_scalar( $_GET['pagename'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['pagename'] ) ) : '';
	$pagename     = trim( rawurldecode( (string) $pagename_raw ), '/' );

	return '' !== $pagename && 0 === strcasecmp( 'account', $pagename );
}

/**
 * Give the account a uniquely named template that Composer does not remap.
 *
 * @param string $template Theme template selected by WordPress.
 */
function hs_manacost_reader_template( string $template ): string {
	$page = hs_manacost_reader_page();
	return $page && is_page( $page->ID ) && hs_manacost_reader_is_account_request()
		? __DIR__ . '/hs-manacost-reader/reader-account-page.php'
		: $template;
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

/**
 * Fail closed for query aliases and for the production mirror.
 *
 * Only a normalized account path on an explicitly allowed host may render the
 * Reader shell. Resolved aliases still receive private headers before the 404.
 */
function hs_manacost_reader_account_route_policy(): void {
	$page             = hs_manacost_reader_page();
	$resolves_account = hs_manacost_reader_resolves_account_page( $page );
	$is_account_path  = hs_manacost_reader_is_account_request();

	if ( ! $is_account_path && ! $resolves_account ) {
		return;
	}
	// Core can redirect path aliases next, so seal the response before it runs.
	hs_manacost_reader_send_private_headers();
	add_filter( 'redirect_canonical', '__return_false', PHP_INT_MAX );
	if ( hs_manacost_reader_is_application_host() && $is_account_path ) {
		return;
	}

	global $wp_query;
	if ( is_object( $wp_query ) && method_exists( $wp_query, 'set_404' ) ) {
		$wp_query->set_404();
	}
	status_header( 404 );
}

/** Keep the account page out of shared caches and search indexes. */
function hs_manacost_reader_cache_policy(): void {
	$is_account = hs_manacost_reader_is_account_request();
	if ( ! $is_account ) {
		$page       = hs_manacost_reader_page();
		$is_account = $page && is_page( $page->ID );
	}
	if ( ! $is_account ) {
		return;
	}

	hs_manacost_reader_send_private_headers();
}

add_filter( 'rocket_cache_reject_uri', 'hs_manacost_reader_rocket_cache_reject_uri' );
add_action( 'init', 'hs_manacost_reader_bootstrap' );
