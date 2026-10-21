<?php
/**
 * Plugin Name: Manacost Shared Theme Styles
 * Description: Keeps shared Newspaper styles at one cacheable URL and paints their critical rules without a render-blocking request.
 * Version: 1.1.0
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Preserves the source URLs of the shared frontend CSS libraries. */
final class Manacost_Shared_Theme_Styles {
	private const CRITICAL_DIR = __DIR__ . '/manacost-shared-theme-styles/';

	/** @var array<string, array<string, mixed>>|null Generated critical-CSS manifest by style handle. */
	private static ?array $manifest = null;

	/** Registers the supported exclusion hooks of both active minifiers. */
	public static function boot(): void {
		add_filter( 'rocket_exclude_css', array( __CLASS__, 'exclude_shared_styles' ) );
		add_filter( 'perfmatters_minify_css_exclusions', array( __CLASS__, 'exclude_shared_styles' ) );
		add_filter( 'style_loader_tag', array( __CLASS__, 'inline_critical_rules' ), 20, 4 );
	}

	/**
	 * Retains existing policy and avoids multiple cache URLs for identical CSS.
	 *
	 * The server still compresses the original files. Their theme/plugin version
	 * query remains the cache buster; no font, URL or CSS declaration is rewritten.
	 *
	 * @param array<int|string, string> $excluded Existing minification exclusions.
	 * @return array<int|string, string>
	 */
	public static function exclude_shared_styles( array $excluded ): array {
		if ( is_admin() ) {
			return $excluded;
		}

		$shared = array(
			'/wp-content/themes/Newspaper_new/style.css',
			'/wp-content/plugins/td-composer/legacy/Newspaper/assets/css/td_legacy_main.css',
			'/wp-content/plugins/td-standard-pack/Newspaper/assets/css/td_standard_pack_main.css',
		);
		foreach ( $shared as $file ) {
			if ( ! in_array( $file, $excluded, true ) ) {
				$excluded[] = $file;
			}
		}

		return $excluded;
	}

	/**
	 * Paints the rules public templates use first, then applies the full sheet at its original cascade position.
	 *
	 * The subset is only used for the exact sheet version it was generated from;
	 * any other version keeps the original blocking stylesheet.
	 *
	 * @param string $tag    Original stylesheet tag.
	 * @param string $handle Registered style handle.
	 * @param string $href   Stylesheet URL.
	 * @param string $media  Stylesheet media.
	 * @return string
	 */
	public static function inline_critical_rules( $tag, $handle, $href, $media ): string {
		$tag   = (string) $tag;
		$entry = self::manifest()[ $handle ] ?? null;
		if ( ! is_array( $entry ) || 'all' !== $media || ! self::is_public_render() ) {
			return $tag;
		}

		$style = wp_styles()->registered[ $handle ] ?? null;
		$path  = (string) wp_parse_url( (string) $href, PHP_URL_PATH );
		if ( ! $style || (string) $style->ver !== (string) $entry['version'] || $path !== $entry['source'] ) {
			return $tag;
		}

		$critical = self::read_critical( (string) $entry['critical'] );
		if ( '' === $critical ) {
			return $tag;
		}

		return sprintf(
			"<style id=\"%1\$s-critical-css\">%2\$s</style>\n<link rel=\"preload\" as=\"style\" id=\"%1\$s-css\" href=\"%3\$s\" onload=\"this.onload=null;this.rel='stylesheet'\" />\n<noscript>%4\$s</noscript>\n",
			esc_attr( $handle ),
			$critical,
			esc_url( (string) $href ),
			trim( $tag )
		);
	}

	/** Limits the change to anonymous-equivalent frontend rendering, never editors or previews. */
	private static function is_public_render(): bool {
		return ! is_admin()
			&& ! is_feed()
			&& ! is_preview()
			&& ! is_customize_preview()
			&& ! wp_doing_ajax()
			&& ! ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			// tagDiv Composer edits templates inside an iframe that needs every rule immediately.
			&& ! isset( $_GET['td_action'], $_GET['tdbTemplateType'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& ! ( class_exists( 'td_util' ) && method_exists( 'td_util', 'tdc_is_live_editor_iframe' ) && td_util::tdc_is_live_editor_iframe() )
			&& ! ( defined( 'MANACOST_CRITICAL_THEME_CSS' ) && ! MANACOST_CRITICAL_THEME_CSS );
	}

	/** @return array<string, array<string, mixed>> */
	private static function manifest(): array {
		if ( null === self::$manifest ) {
			$json           = is_readable( self::CRITICAL_DIR . 'critical-manifest.json' )
				? json_decode( (string) file_get_contents( self::CRITICAL_DIR . 'critical-manifest.json' ), true ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				: null;
			self::$manifest = is_array( $json['sheets'] ?? null ) ? $json['sheets'] : array();
		}

		return self::$manifest;
	}

	/** Reads a generated subset that cannot close the surrounding style element. */
	private static function read_critical( string $file ): string {
		if ( ! preg_match( '/^critical-[a-z0-9-]+\.css$/', $file ) || ! is_readable( self::CRITICAL_DIR . $file ) ) {
			return '';
		}

		$css = trim( (string) file_get_contents( self::CRITICAL_DIR . $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return false === stripos( $css, '</style' ) ? $css : '';
	}
}

Manacost_Shared_Theme_Styles::boot();
