<?php
/**
 * Plugin Name: HS Manacost S3 REST Image Restore
 * Description: Restores offloaded originals while WordPress completes REST image processing.
 * Version: 1.0.0
 * Author: Manacost
 *
 * @package HS_Manacost
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check whether a REST request is WordPress deferred image processing.
 *
 * @param mixed $request Matched REST request.
 * @return bool Whether the request creates image sub-sizes.
 */
function hs_manacost_s3_rest_is_subsize_request( $request ): bool {
	if ( ! is_object( $request ) || ! method_exists( $request, 'get_route' ) || ! method_exists( $request, 'get_param' ) ) {
		return false;
	}

	$route = (string) $request->get_route();

	return 1 === preg_match( '#^/wp/v2/media/[0-9]+/post-process$#', $route )
		&& 'create-image-subsizes' === $request->get_param( 'action' );
}

/**
 * Mark the current REST request as allowed to hydrate an S3 original.
 *
 * @param mixed $response Current REST response.
 * @param mixed $handler Matched REST route handler (unused).
 * @param mixed $request Matched REST request.
 * @return mixed Unchanged response.
 */
function hs_manacost_s3_rest_before_callbacks( $response, $handler, $request ) {
	unset( $handler );

	$GLOBALS['hs_manacost_s3_rest_restore_context'] = hs_manacost_s3_rest_is_subsize_request( $request );

	return $response;
}

/**
 * Clear the request-local S3 hydration context.
 *
 * @param mixed $response Current REST response.
 * @param mixed $handler Matched REST route handler (unused).
 * @param mixed $request Matched REST request (unused).
 * @return mixed Unchanged response.
 */
function hs_manacost_s3_rest_after_callbacks( $response, $handler, $request ) {
	unset( $handler, $request );
	$GLOBALS['hs_manacost_s3_rest_restore_context'] = false;

	return $response;
}

/**
 * Restore a missing original requested by deferred REST image processing.
 *
 * @param string|false $file Attached file path.
 * @param mixed        $attachment_id Attachment identifier (unused).
 * @return string|false Original file path.
 */
function hs_manacost_s3_rest_restore_file( string|false $file, $attachment_id ): string|false {
	unset( $attachment_id );
	if (
		empty( $GLOBALS['hs_manacost_s3_rest_restore_context'] )
		|| false === $file
		|| '' === $file
		|| file_exists( $file )
		|| ! function_exists( 'hs_manacost_s3_hydrator' )
		|| ! defined( 'HS_MANACOST_S3_PUBLIC_BASE_URL' )
		|| ! defined( 'HS_MANACOST_S3_UPLOAD_PREFIX' )
	) {
		return $file;
	}

	$uploads        = wp_get_upload_dir();
	$base_directory = isset( $uploads['basedir'] ) ? (string) $uploads['basedir'] : '';

	if ( '' !== $base_directory ) {
		hs_manacost_s3_hydrator()->restore(
			$file,
			$base_directory,
			HS_MANACOST_S3_PUBLIC_BASE_URL,
			HS_MANACOST_S3_UPLOAD_PREFIX
		);
	}

	return $file;
}

add_filter( 'rest_request_before_callbacks', 'hs_manacost_s3_rest_before_callbacks', 10, 3 );
add_filter( 'rest_request_after_callbacks', 'hs_manacost_s3_rest_after_callbacks', 10, 3 );
add_filter( 'get_attached_file', 'hs_manacost_s3_rest_restore_file', 19, 2 );
