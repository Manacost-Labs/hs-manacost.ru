<?php
/**
 * Plugin Name: Manacost Social Links
 * Description: Registers update-safe GitHub and Boosty social links for Newspaper.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Keeps the public social menu consistent across Newspaper surfaces.
 */
final class Manacost_Social_Links {
	private const THEME_OPTIONS_OPTION = 'td_011';
	private const GITHUB_URL           = 'https://github.com/Manacost-Labs';
	private const BOOSTY_URL           = 'https://boosty.to/kolodahearthstone';
	private const PATREON_URL          = 'https://www.patreon.com/cw/manacostru';

	/**
	 * Registers the theme extension points.
	 *
	 * @return void
	 */
	public static function boot(): void {
		add_filter( 'option_' . self::THEME_OPTIONS_OPTION, array( __CLASS__, 'filter_theme_options' ) );
		add_action( 'after_setup_theme', array( __CLASS__, 'register_boosty_icon' ), 100 );
		add_action( 'wp_head', array( __CLASS__, 'render_boosty_style' ), 99 );
	}

	/**
	 * Applies the managed social menu without writing to the production options table.
	 *
	 * @param mixed $options Newspaper theme options.
	 * @return mixed
	 */
	public static function filter_theme_options( $options ) {
		if ( ! is_array( $options ) ) {
			return $options;
		}

		$networks = isset( $options['td_social_networks'] ) && is_array( $options['td_social_networks'] )
			? $options['td_social_networks']
			: array();

		$options['td_social_networks'] = self::transform_networks( $networks );

		return $options;
	}

	/**
	 * Makes Boosty available to Newspaper's social-icon renderer.
	 *
	 * @return void
	 */
	public static function register_boosty_icon(): void {
		if ( ! class_exists( 'td_social_icons' ) ) {
			return;
		}

		td_social_icons::$td_social_icons_array['boosty'] = 'Boosty';
	}

	/**
	 * Replaces the legacy Website entry and adds the managed support links.
	 *
	 * @param array<string, string> $networks Existing Newspaper social network URLs.
	 * @return array<string, string>
	 */
	public static function transform_networks( array $networks ): array {
		$updated          = array();
		$targets_inserted = false;

		foreach ( $networks as $network => $url ) {
			if ( 'website' === $network || 'github' === $network || 'boosty' === $network || 'patreon' === $network ) {
				if ( ! $targets_inserted ) {
					self::append_target_networks( $updated );
					$targets_inserted = true;
				}

				continue;
			}

			$updated[ $network ] = $url;
		}

		if ( ! $targets_inserted ) {
			self::append_target_networks( $updated );
		}

		return $updated;
	}

	/**
	 * Adds the managed links in their intended visual order.
	 *
	 * @param array<string, string> $networks Social network URLs to extend.
	 * @return void
	 */
	private static function append_target_networks( array &$networks ): void {
		$networks['github']  = self::GITHUB_URL;
		$networks['boosty']  = self::BOOSTY_URL;
		$networks['patreon'] = self::PATREON_URL;
	}

	/**
	 * Renders the compact Boosty mark without modifying Newspaper's icon font.
	 *
	 * @return void
	 */
	public static function render_boosty_style(): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="manacost-boosty-social-icon">
			.td-social-icon-wrap a[href*="boosty.to"] .td-icon-boosty::before {
				background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='80 45 165 205'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='188.3014' y1='75.5591' x2='123.8106' y2='295.4895' gradientUnits='userSpaceOnUse'%3E%3Cstop offset='0' stop-color='%23EF7829'/%3E%3Cstop offset='1' stop-color='%23F15A2C'/%3E%3C/linearGradient%3E%3C/defs%3E%3Cpath fill='url(%23g)' d='M87.5,163.9L120.2,51h50.1l-10.1,35c-.1.2-.2.4-.3.6L133.3,179h24.8c-10.4,25.9-18.5,46.2-24.3,60.9-45.8-.5-58.6-33.3-47.4-72.1M133.9,240l60.4-86.9h-25.6l22.3-55.7c38.2,4,56.2,34.1,45.6,70.5-11.3,39.1-57.2,72.1-101.8,72.1h-.9z'/%3E%3C/svg%3E");
				background-position: center;
				background-repeat: no-repeat;
				background-size: contain;
				content: "";
				display: inline-block;
				height: 24px;
				width: 24px;
			}
		</style>
		<?php
	}
}

Manacost_Social_Links::boot();
