<?php
/**
 * Plugin Name: HS API Gallery
 * Description: Native WordPress galleries with immutable Koloda images and optional reader ratings.
 * Version: 1.0.4
 * Requires at least: 6.9
 * Requires PHP: 8.2
 * Text Domain: manacost
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'HS_API_GALLERY_ENABLED' ) && ! HS_API_GALLERY_ENABLED ) {
	return;
}

foreach ( array( 'catalog', 'importer', 'votes', 'identity', 'gallery', 'ajax', 'admin' ) as $hs_gallery_component ) {
	require_once __DIR__ . '/hs-api-gallery/class-' . $hs_gallery_component . '.php';
}
unset( $hs_gallery_component );

Manacost\ApiGallery\Admin::register();
Manacost\ApiGallery\Gallery::register();
Manacost\ApiGallery\Ajax::register();
Manacost\ApiGallery\Votes::register();
