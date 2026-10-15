<?php
/**
 * Plugin Name: Manacost Shared Theme Styles
 * Description: Keeps shared Newspaper styles at one cacheable URL across fresh and cached pages.
 * Version: 1.0.0
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Preserves the source URLs of the shared frontend CSS libraries. */
final class Manacost_Shared_Theme_Styles {

	/** Registers the supported exclusion hooks of both active minifiers. */
	public static function boot(): void {
		add_filter( 'rocket_exclude_css', array( __CLASS__, 'exclude_shared_styles' ) );
		add_filter( 'perfmatters_minify_css_exclusions', array( __CLASS__, 'exclude_shared_styles' ) );
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
}

Manacost_Shared_Theme_Styles::boot();
