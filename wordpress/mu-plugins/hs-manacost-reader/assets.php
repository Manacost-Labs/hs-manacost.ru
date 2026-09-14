<?php
/**
 * Reader asset registry and surface-specific enqueue helpers.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Describe every first-party Reader asset in one place.
 *
 * @return array{styles:array<string,array{handle:string,file:string,dependencies:string[]}>,scripts:array<string,array{handle:string,file:string,dependencies:string[]}>}
 */
function hs_manacost_reader_asset_manifest(): array {
	static $assets = array(
		'styles'  => array(
			'ui'             => array(
				'handle'       => 'hs-manacost-reader-ui',
				'file'         => 'ui.css',
				'dependencies' => array(),
			),
			'reader'         => array(
				'handle'       => 'hs-manacost-reader',
				'file'         => 'reader.css',
				'dependencies' => array( 'hs-manacost-reader-ui' ),
			),
			'comments'       => array(
				'handle'       => 'hs-manacost-reader-comments',
				'file'         => 'comments.css',
				'dependencies' => array( 'hs-manacost-reader-ui' ),
			),
			'public-profile' => array(
				'handle'       => 'hs-manacost-reader-public-profile',
				'file'         => 'public-profile.css',
				'dependencies' => array( 'hs-manacost-reader-ui' ),
			),
			'favorite'       => array(
				'handle'       => 'hs-manacost-reader-favorite',
				'file'         => 'article-favorite.css',
				'dependencies' => array( 'hs-manacost-reader-ui' ),
			),
		),
		'scripts' => array(
			'bootstrap'      => array(
				'handle'       => 'hs-manacost-reader-bootstrap',
				'file'         => 'bootstrap.js',
				'dependencies' => array(),
			),
			'profile-editor' => array(
				'handle'       => 'hs-manacost-reader-profile-editor',
				'file'         => 'profile-editor.js',
				'dependencies' => array(),
			),
			'reader'         => array(
				'handle'       => 'hs-manacost-reader',
				'file'         => 'reader.js',
				'dependencies' => array( 'hs-manacost-reader-profile-editor', 'hs-manacost-reader-bootstrap' ),
			),
			'favorite'       => array(
				'handle'       => 'hs-manacost-reader-favorite',
				'file'         => 'article-favorite.js',
				'dependencies' => array( 'hs-manacost-reader-bootstrap' ),
			),
			'community'      => array(
				'handle'       => 'hs-manacost-reader-community-ui',
				'file'         => 'community-ui.js',
				'dependencies' => array(),
			),
			'comments'       => array(
				'handle'       => 'hs-manacost-reader-comments',
				'file'         => 'comments.js',
				'dependencies' => array( 'hs-manacost-reader-community-ui', 'hs-manacost-reader-bootstrap' ),
			),
			'public-profile' => array(
				'handle'       => 'hs-manacost-reader-public-profile',
				'file'         => 'public-profile.js',
				'dependencies' => array(),
			),
		),
	);
	return $assets;
}

/**
 * Give each first-party Reader asset a stable URL until its own content changes.
 *
 * @param string $asset Trusted Reader asset basename.
 * @return string Short content version, or a safe fallback for a missing asset.
 */
function hs_manacost_reader_asset_version( string $asset ): string {
	static $versions = array();
	if ( isset( $versions[ $asset ] ) ) {
		return $versions[ $asset ];
	}
	if ( 1 !== preg_match( '/\A[a-z0-9-]+\.(?:css|js)\z/', $asset ) ) {
		return 'missing';
	}
	$hash               = hash_file( 'sha256', __DIR__ . '/' . $asset );
	$versions[ $asset ] = is_string( $hash ) ? substr( $hash, 0, 12 ) : 'missing';
	return $versions[ $asset ];
}

/**
 * Enqueue one registered Reader stylesheet.
 *
 * @param string $name Logical style name from the manifest.
 */
function hs_manacost_reader_enqueue_style( string $name ): void {
	$asset = hs_manacost_reader_asset_manifest()['styles'][ $name ] ?? null;
	if ( ! is_array( $asset ) ) {
		return;
	}
	wp_enqueue_style(
		$asset['handle'],
		content_url( 'mu-plugins/hs-manacost-reader/' . $asset['file'] ),
		$asset['dependencies'],
		hs_manacost_reader_asset_version( $asset['file'] )
	);
}

/**
 * Enqueue one registered Reader script without blocking parsing.
 *
 * @param string $name Logical script name from the manifest.
 */
function hs_manacost_reader_enqueue_script( string $name ): void {
	$asset = hs_manacost_reader_asset_manifest()['scripts'][ $name ] ?? null;
	if ( ! is_array( $asset ) ) {
		return;
	}

	/*
	 * wp_enqueue_script() only receives a source URL for the requested handle.
	 * Register each first-party dependency itself before its consumer: otherwise
	 * WordPress drops the consumer when it cannot resolve that dependency.
	 */
	foreach ( $asset['dependencies'] as $dependency_handle ) {
		foreach ( hs_manacost_reader_asset_manifest()['scripts'] as $dependency_name => $dependency ) {
			if ( $dependency_handle === $dependency['handle'] ) {
				hs_manacost_reader_enqueue_script( $dependency_name );
				break;
			}
		}
	}
	wp_enqueue_script(
		$asset['handle'],
		content_url( 'mu-plugins/hs-manacost-reader/' . $asset['file'] ),
		$asset['dependencies'],
		hs_manacost_reader_asset_version( $asset['file'] ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
}

/** Load the zero-runtime Tailwind refinement after every active Reader surface. */
function hs_manacost_reader_tailwind_assets(): void {
	$dependencies = array_values(
		array_filter(
			array( 'hs-manacost-reader', 'hs-manacost-reader-comments', 'hs-manacost-reader-public-profile', 'hs-manacost-reader-favorite' ),
			static fn( string $handle ): bool => wp_style_is( $handle, 'enqueued' )
		)
	);
	if ( array() === $dependencies ) {
		return;
	}
	wp_enqueue_style(
		'hs-manacost-reader-tailwind',
		content_url( 'mu-plugins/hs-manacost-reader/tailwind.css' ),
		$dependencies,
		hs_manacost_reader_asset_version( 'tailwind.css' )
	);
}

/** Load the account bundle only on the private account surface. */
function hs_manacost_reader_assets(): void {
	$page = hs_manacost_reader_page();
	if ( ! $page || ! is_page( $page->ID ) || hs_reader_public_profile_request() ) {
		return;
	}
	hs_manacost_reader_enqueue_style( 'ui' );
	hs_manacost_reader_enqueue_style( 'reader' );
	hs_manacost_reader_enqueue_script( 'profile-editor' );
	hs_manacost_reader_enqueue_script( 'reader' );
}
