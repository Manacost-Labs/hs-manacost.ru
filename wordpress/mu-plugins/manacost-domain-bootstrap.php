<?php
/**
 * Bootstrap the public WordPress URL before WordPress derives content,
 * plugin and cookie URLs. This file is loaded from wp-config.php.
 */

$manacost_request_host = strtolower( (string) ( $_SERVER['HTTP_HOST'] ?? '' ) );
$manacost_request_host = preg_replace( '/:\d+$/', '', $manacost_request_host ) ?: '';
$manacost_request_host = rtrim( $manacost_request_host, '.' );

$manacost_public_host = in_array(
	$manacost_request_host,
	[ 'hs-manacost.com', 'www.hs-manacost.com' ],
	true
)
	? 'hs-manacost.com'
	: 'hs-manacost.ru';

if ( ! defined( 'MANACOST_PUBLIC_HOST' ) ) {
	define( 'MANACOST_PUBLIC_HOST', $manacost_public_host );
}

if ( ! defined( 'MANACOST_PRIMARY_HOST' ) ) {
	define( 'MANACOST_PRIMARY_HOST', 'hs-manacost.ru' );
}

if ( ! defined( 'MANACOST_MIRROR_HOST' ) ) {
	define( 'MANACOST_MIRROR_HOST', 'hs-manacost.com' );
}

$manacost_public_url = 'https://' . $manacost_public_host;

if ( ! defined( 'WP_HOME' ) ) {
	define( 'WP_HOME', $manacost_public_url );
}

if ( ! defined( 'WP_SITEURL' ) ) {
	define( 'WP_SITEURL', $manacost_public_url );
}

if ( ! defined( 'WP_CONTENT_URL' ) ) {
	define( 'WP_CONTENT_URL', $manacost_public_url . '/wp-content' );
}

unset( $manacost_request_host, $manacost_public_host, $manacost_public_url );
