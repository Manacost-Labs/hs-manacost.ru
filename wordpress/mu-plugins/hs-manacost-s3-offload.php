<?php
/**
 * Plugin Name: HS Manacost S3 Offload
 * Description: Restores offloaded WordPress image originals from OVH Object Storage when administrative tools need a local file.
 * Version: 1.0.0
 *
 * @package Manacost
 */

declare(strict_types=1);

use HsManacost\S3Offload\Hydrator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hs_manacost_s3_class_directory = defined( 'HS_MANACOST_S3_CLASS_DIR' )
	? HS_MANACOST_S3_CLASS_DIR
	: __DIR__ . '/hs-manacost-s3-offload/src';

require_once $hs_manacost_s3_class_directory . '/PathPolicy.php';
require_once $hs_manacost_s3_class_directory . '/Hydrator.php';

const HS_MANACOST_S3_PUBLIC_BASE_URL = 'https://hs-manacost-media-3az.s3.eu-west-par.io.cloud.ovh.net';
const HS_MANACOST_S3_UPLOAD_PREFIX   = 'wp-content/uploads';

/**
 * Limit local hydration to explicit maintenance and media processing callbacks.
 */
function hs_manacost_s3_restore_context(): bool {
	if ( getenv( 'HS_MANACOST_S3_RESTORE' ) === '1' ) {
		return true;
	}

	// A generic cron request or caller-controlled action string is insufficient.
	foreach ( array(
		'hs_media_upload_accelerator_generate_subsizes',
		'manacost_media_upload_accelerator_generate_subsizes',
		'hs_local_image_optimizer_process_attachment',
	) as $hook ) {
		if ( doing_action( $hook ) ) {
			return true;
		}
	}

	// Core checks edit_post and the image-editor nonce before accessing a file.
	return is_admin() && ( doing_action( 'wp_ajax_image-editor' ) || doing_action( 'wp_ajax_imgedit-preview' ) );
}

/**
 * Build a bounded, fixed-origin streaming downloader for the path-safe hydrator.
 */
function hs_manacost_s3_hydrator(): Hydrator {
	static $hydrator = null;

	if ( $hydrator instanceof Hydrator ) {
		return $hydrator;
	}

	$hydrator = new Hydrator(
		static function ( string $url, string $temporary_file ): bool {
			$response = wp_safe_remote_get(
				$url,
				array(
					'timeout'     => 120,
					'redirection' => 0,
					'stream'      => true,
					'filename'    => $temporary_file,
				)
			);

			if ( is_wp_error( $response ) ) {
				return false;
			}

			return wp_remote_retrieve_response_code( $response ) === 200;
		}
	);

	return $hydrator;
}

/**
 * Restore only an absent local upload; preserve the caller's canonical path.
 *
 * @param string|false $file Local upload path supplied by WordPress.
 * @return string|false Unchanged path, including when restoration fails.
 */
function hs_manacost_s3_restore_file( string|false $file ): string|false {
	if ( false === $file || '' === $file || file_exists( $file ) || ! hs_manacost_s3_restore_context() ) {
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

add_filter(
	'hs_local_image_optimizer_source_file',
	'hs_manacost_s3_restore_file',
	20
);

add_filter(
	'get_attached_file',
	'hs_manacost_s3_restore_file',
	20
);

add_filter(
	'wp_get_original_image_path',
	'hs_manacost_s3_restore_file',
	20
);
