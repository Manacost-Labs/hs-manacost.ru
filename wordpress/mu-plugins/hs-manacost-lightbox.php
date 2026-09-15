<?php
/**
 * Plugin Name: HS Manacost Content Lightbox
 * Description: Provides the lightweight, accessible image viewer for public Manacost content.
 * Version: 1.0.0
 * Author: Manacost
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Decide whether the public content viewer belongs on the current request.
 */
function hs_manacost_lightbox_should_enqueue(): bool {
	return ! is_admin()
		&& ! is_feed()
		&& ! is_preview()
		&& is_singular()
		&& ! hs_manacost_lightbox_is_live_editor();
}

/**
 * Keep tagDiv Composer requests on the vendor script dependency chain.
 */
function hs_manacost_lightbox_is_live_editor(): bool {
	$state_class = 'tdc_state';
	if ( ! class_exists( $state_class, false ) ) {
		return false;
	}

	foreach ( array( 'is_live_editor_iframe', 'is_live_editor_ajax' ) as $method ) {
		if ( is_callable( array( $state_class, $method ) ) && true === call_user_func( array( $state_class, $method ) ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Return the immutable version assigned to one lightbox asset.
 *
 * Values are replaced whenever the corresponding source changes. Keeping the
 * hash in source avoids filesystem hashing on every WordPress request.
 *
 * @param string $asset Trusted asset basename.
 */
function hs_manacost_lightbox_asset_version( string $asset ): string {
	$versions = array(
		'lightbox.css' => '3a330e349dcf',
		'lightbox.js'  => '373ab9d1789d',
	);

	return $versions[ $asset ] ?? 'missing';
}

/**
 * Replace Newspaper's article image modal with the first-party viewer.
 */
function hs_manacost_lightbox_enqueue_assets(): void {
	if ( ! hs_manacost_lightbox_should_enqueue() ) {
		return;
	}

	wp_dequeue_script( 'tdModalPostImages' );
	wp_deregister_script( 'tdModalPostImages' );

	$asset_url = plugin_dir_url( __FILE__ ) . 'hs-manacost-lightbox/';
	wp_enqueue_style(
		'hs-manacost-lightbox',
		$asset_url . 'lightbox.css',
		array(),
		hs_manacost_lightbox_asset_version( 'lightbox.css' )
	);
	wp_enqueue_script(
		'hs-manacost-lightbox',
		$asset_url . 'lightbox.js',
		array(),
		hs_manacost_lightbox_asset_version( 'lightbox.js' ),
		array( 'in_footer' => true )
	);
	wp_script_add_data( 'hs-manacost-lightbox', 'strategy', 'defer' );
	wp_localize_script(
		'hs-manacost-lightbox',
		'hsManacostLightboxConfig',
		array(
			'title'        => __( 'Просмотр изображения', 'hs-manacost-lightbox' ),
			'close'        => __( 'Закрыть', 'hs-manacost-lightbox' ),
			'previous'     => __( 'Предыдущее изображение', 'hs-manacost-lightbox' ),
			'next'         => __( 'Следующее изображение', 'hs-manacost-lightbox' ),
			'openOriginal' => __( 'Открыть оригинал', 'hs-manacost-lightbox' ),
			'loading'      => __( 'Загрузка изображения', 'hs-manacost-lightbox' ),
			'error'        => __( 'Не удалось загрузить изображение.', 'hs-manacost-lightbox' ),
			/* translators: 1: current image number, 2: total image count. */
			'imageCount'   => __( '%1$d из %2$d', 'hs-manacost-lightbox' ),
			/* translators: %s: image alternative text or caption. */
			'openImage'    => __( 'Открыть изображение: %s', 'hs-manacost-lightbox' ),
		)
	);
}

add_action( 'wp_enqueue_scripts', 'hs_manacost_lightbox_enqueue_assets', PHP_INT_MAX );

/**
 * Keep the interaction handler available for the first click and preserve the
 * runtime-created dialog CSS when an optimizer removes unused resources.
 *
 * @param mixed  $exclusions Existing optimizer rules.
 * @param string $asset      Trusted asset basename.
 * @return mixed
 */
function hs_manacost_lightbox_optimizer_exclusion( $exclusions, string $asset ) {
	if ( ! is_array( $exclusions ) ) {
		return $exclusions;
	}

	$exclusions[] = '/wp-content/mu-plugins/hs-manacost-lightbox/' . $asset;

	return array_values( array_unique( $exclusions ) );
}

/**
 * Preserve the first-click JavaScript when optimizer delay is enabled.
 *
 * @param mixed $exclusions Existing optimizer rules.
 * @return mixed
 */
function hs_manacost_lightbox_js_exclusion( $exclusions ) {
	return hs_manacost_lightbox_optimizer_exclusion( $exclusions, 'lightbox.js' );
}

/**
 * Preserve dialog styles that do not exist in the initial HTML response.
 *
 * @param mixed $exclusions Existing optimizer rules.
 * @return mixed
 */
function hs_manacost_lightbox_css_exclusion( $exclusions ) {
	return hs_manacost_lightbox_optimizer_exclusion( $exclusions, 'lightbox.css' );
}

add_filter( 'rocket_delay_js_exclusions', 'hs_manacost_lightbox_js_exclusion' );
add_filter( 'perfmatters_delay_js_exclusions', 'hs_manacost_lightbox_js_exclusion' );
add_filter( 'rocket_rucss_external_exclusions', 'hs_manacost_lightbox_css_exclusion' );
add_filter( 'perfmatters_rucss_excluded_stylesheets', 'hs_manacost_lightbox_css_exclusion' );
