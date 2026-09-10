<?php
/**
 * Plugin Name: HS Guide Export Policy
 * Description: Disables article PDF and TXT exports without changing article content.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Keeps the retired guide-export surface disabled at every public entry point. */
final class HS_Guide_Export_Policy {
	private const PDF_QUERY_VAR = 'manacost_guide_pdf';
	private const TXT_QUERY_VAR = 'manacost_guide_txt';

	/** Registers the policy after every MU-plugin has registered its hooks. */
	public static function boot(): void {
		add_action( 'muplugins_loaded', array( __CLASS__, 'disable_legacy_exports' ) );
	}

	/**
	 * Removes export controls and intercepts old export URLs before their legacy handler.
	 *
	 * @return void
	 */
	public static function disable_legacy_exports(): void {
		remove_filter( 'the_content', array( 'Manacost_Guide_PDF', 'add_download_button' ), 12 );
		add_action( 'template_redirect', array( __CLASS__, 'block_legacy_requests' ), -1 );
	}

	/** Returns a terminal response instead of an article download for historical URLs. */
	public static function block_legacy_requests(): void {
		if ( ! is_singular( 'post' ) || ! self::has_export_request() ) {
			return;
		}

		status_header( 410 );
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow', true );
		wp_die(
			esc_html__( 'Скачивание статей отключено.', 'manacost' ),
			esc_html__( 'Загрузка недоступна', 'manacost' ),
			array( 'response' => 410 )
		);
	}

	/** Checks only the two historical export query variables. */
	private static function has_export_request(): bool {
		foreach ( array( self::PDF_QUERY_VAR, self::TXT_QUERY_VAR ) as $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- A read-only legacy URL is being recognized before returning a terminal response.
			if ( isset( $_GET[ $key ] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Scalar shape is validated before use.
				$value = wp_unslash( $_GET[ $key ] );
				if ( is_scalar( $value ) && '' !== (string) $value ) {
					return true;
				}
			}
		}

		return false;
	}
}

HS_Guide_Export_Policy::boot();
