<?php
/**
 * Plugin Name: HS Manacost PWA
 * Description: Adds installable web-app metadata for the canonical site and staging.
 * Version: 1.0.0
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Return the normalized request host without trusting aliases or suffix matches. */
function hs_manacost_pwa_request_host(): string {
	$host = isset( $_SERVER['HTTP_HOST'] )
		? strtolower( trim( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) )
		: '';
	$host = preg_replace( '/:\d+\z/', '', $host );

	return rtrim( is_string( $host ) ? $host : '', '.' );
}

/** Whether the current host may advertise and register the PWA. */
function hs_manacost_pwa_is_enabled_host(): bool {
	return in_array( hs_manacost_pwa_request_host(), array( 'hs-manacost.ru', 'test.hs-manacost.ru' ), true );
}

/** Render install metadata after the legacy WordPress site icon tags. */
function hs_manacost_pwa_head(): void {
	?>
	<link rel="manifest" href="/manifest.webmanifest">
	<link rel="icon" href="/favicon.ico" sizes="any">
	<link rel="icon" type="image/png" sizes="192x192" href="/pwa-icons/icon-192.png">
	<link rel="apple-touch-icon" sizes="180x180" href="/pwa-icons/apple-touch-icon.png">
	<meta name="theme-color" content="#062f3b">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<?php
}

/** Register the root-scoped service worker after the page becomes usable. */
function hs_manacost_pwa_footer(): void {
	?>
	<script id="hs-manacost-pwa-registration">
		if ('serviceWorker' in navigator) {
			window.addEventListener('load', function () {
				navigator.serviceWorker.register('/service-worker.js', {
					scope: '/',
					updateViaCache: 'none'
				});
			});
		}
	</script>
	<?php
}

if ( hs_manacost_pwa_is_enabled_host() ) {
	add_action( 'wp_head', 'hs_manacost_pwa_head', 100 );
	add_action( 'wp_footer', 'hs_manacost_pwa_footer', 100 );
}
