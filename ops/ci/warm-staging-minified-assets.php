<?php

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	throw new RuntimeException( 'This script must be run through WP-CLI.' );
}

if ( wp_parse_url( home_url(), PHP_URL_HOST ) !== 'test.hs-manacost.ru' ) {
	WP_CLI::error( 'Refusing to warm assets outside test.hs-manacost.ru.' );
}

if ( ! function_exists( 'rocket_get_constant' ) || ! function_exists( 'get_rocket_option' ) || ! function_exists( 'is_rocket_post_excluded_option' ) ) {
	WP_CLI::error( 'WP Rocket minify APIs are unavailable.' );
}

$account_page = get_page_by_path( 'account' );
if ( ! $account_page instanceof WP_Post ) {
	WP_CLI::error( 'The staging reader account page is unavailable.' );
}

$minify_cache_path = rocket_get_constant( 'WP_ROCKET_MINIFY_CACHE_PATH' );
$minify_cache_url  = rocket_get_constant( 'WP_ROCKET_MINIFY_CACHE_URL' );

if ( ! is_string( $minify_cache_path ) || '' === $minify_cache_path || ! is_string( $minify_cache_url ) || '' === $minify_cache_url ) {
	WP_CLI::error( 'WP Rocket minify cache paths are unavailable.' );
}

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI']    = '/account/';

$account_query = new WP_Query(
	array(
		'page_id'     => $account_page->ID,
		'post_status' => 'publish',
	)
);

if ( ! $account_query->have_posts() ) {
	WP_CLI::error( 'The staging reader account page cannot be queried.' );
}

$original_query = $GLOBALS['wp_query'] ?? null;
$GLOBALS['wp_query'] = $account_query;
$account_query->the_post();

do_action( 'wp' );

ob_start();
get_header();
the_content();
get_footer();
$markup    = (string) ob_get_clean();
$optimized = apply_filters( 'rocket_buffer', $markup );

$expects_minified_assets = (
	( (bool) get_rocket_option( 'minify_css' ) && ! (bool) is_rocket_post_excluded_option( 'minify_css' ) )
	|| ( (bool) get_rocket_option( 'minify_js' ) && ! (bool) is_rocket_post_excluded_option( 'minify_js' ) )
);

wp_reset_postdata();
if ( $original_query instanceof WP_Query ) {
	$GLOBALS['wp_query'] = $original_query;
}

if ( ! is_string( $optimized ) ) {
	WP_CLI::error( 'WP Rocket did not return optimized account markup.' );
}

if ( ! $expects_minified_assets ) {
	WP_CLI::success( 'The reader account page has no active WP Rocket minification.' );
	return;
}

if ( ! str_contains( $optimized, 'data-minify="1"' ) ) {
	WP_CLI::error( 'The reader account page expected minified assets but emitted none.' );
}

$minify_url_path = wp_parse_url( $minify_cache_url, PHP_URL_PATH );
if ( ! is_string( $minify_url_path ) || '' === $minify_url_path ) {
	WP_CLI::error( 'The WP Rocket minify cache URL has no path.' );
}

$minify_uri_prefix = rtrim( $minify_url_path, '/' ) . '/' . get_current_blog_id() . '/';
$minify_root       = realpath( trailingslashit( $minify_cache_path ) . get_current_blog_id() );

if ( false === $minify_root ) {
	WP_CLI::error( 'The WP Rocket minify cache directory was not created.' );
}

$minify_root = wp_normalize_path( $minify_root ) . '/';

preg_match_all( '#(?:href|src)=[\'\"](?<url>[^\'\"]+)[\'\"]#', $optimized, $matches );
$warmed_assets = array();

foreach ( array_unique( $matches['url'] ?? array() ) as $asset_url ) {
	$asset_path = wp_parse_url( html_entity_decode( $asset_url, ENT_QUOTES ), PHP_URL_PATH );

	if ( ! is_string( $asset_path ) || ! str_starts_with( $asset_path, $minify_uri_prefix ) ) {
		continue;
	}

	if ( ! preg_match( '/\\.(?:css|js)$/', $asset_path ) ) {
		continue;
	}

	$relative_path = rawurldecode( ltrim( substr( $asset_path, strlen( $minify_uri_prefix ) ), '/' ) );
	if ( '' === $relative_path || str_contains( $relative_path, '..' ) ) {
		WP_CLI::error( 'The optimized account markup contains an unsafe minified asset path.' );
	}

	$minified_file = realpath( $minify_root . $relative_path );
	if ( false === $minified_file || ! str_starts_with( wp_normalize_path( $minified_file ), $minify_root ) || ! is_file( $minified_file ) ) {
		WP_CLI::error( "A generated account asset is missing or empty: {$relative_path}" );
	}

	$minified_size = filesize( $minified_file );
	if ( false === $minified_size || 0 === $minified_size ) {
		WP_CLI::error( "A generated account asset is missing or empty: {$relative_path}" );
	}

	$warmed_assets[] = $relative_path;
}

if ( empty( $warmed_assets ) ) {
	WP_CLI::error( 'WP Rocket marked account assets as minified but did not emit verifiable files.' );
}

WP_CLI::success( sprintf( 'Warmed %d minified reader account asset(s).', count( $warmed_assets ) ) );
