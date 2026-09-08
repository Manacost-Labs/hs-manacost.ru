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
	 * Replaces the legacy Website entry and inserts GitHub immediately before Boosty.
	 *
	 * @param array<string, string> $networks Existing Newspaper social network URLs.
	 * @return array<string, string>
	 */
	public static function transform_networks( array $networks ): array {
		$updated          = array();
		$targets_inserted = false;

		foreach ( $networks as $network => $url ) {
			if ( 'website' === $network || 'github' === $network || 'boosty' === $network ) {
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
		$networks['github'] = self::GITHUB_URL;
		$networks['boosty'] = self::BOOSTY_URL;
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
				align-items: center;
				background-color: #f15f2c;
				border-radius: 50%;
				color: #fff;
				content: "B";
				display: inline-flex;
				font-family: Arial, sans-serif;
				font-size: 12px;
				font-style: normal;
				font-weight: 700;
				height: 24px;
				justify-content: center;
				line-height: 1;
				width: 24px;
			}

			.td-social-icon-wrap:hover a[href*="boosty.to"] .td-icon-boosty::before {
				background-color: #d94e22;
			}
		</style>
		<?php
	}
}

Manacost_Social_Links::boot();
