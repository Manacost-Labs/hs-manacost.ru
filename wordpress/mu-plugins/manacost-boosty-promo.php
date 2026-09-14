<?php
/**
 * Plugin Name: Manacost Boosty Promo
 * Description: Renders the managed Boosty banner on the homepage and footer support links.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Keeps the homepage support promotion and footer help links update-safe.
 */
final class Manacost_Boosty_Promo {
	private const BOOSTY_URL = 'https://boosty.to/kolodahearthstone';

	/**
	 * Registers public WordPress extension points.
	 *
	 * @return void
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
		add_filter( 'wp_nav_menu_items', array( __CLASS__, 'append_footer_links' ), 10, 2 );
		add_filter( 'pre_do_shortcode_tag', array( __CLASS__, 'replace_homepage_pricing_card' ), 10, 3 );
	}

	/**
	 * Loads the banner presentation only where it can render.
	 *
	 * @return void
	 */
	public static function enqueue_styles(): void {
		if ( is_admin() || ! is_front_page() ) {
			return;
		}

		wp_enqueue_style(
			'manacost-boosty-promo',
			plugin_dir_url( __FILE__ ) . 'manacost-boosty-promo.css',
			array(),
			'1.0.0'
		);
	}

	/**
	 * Adds requested links to the public footer location without mutating its menu data.
	 *
	 * @param string   $items Rendered menu items.
	 * @param stdClass $args  Menu render arguments.
	 * @return string
	 */
	public static function append_footer_links( string $items, stdClass $args ): string {
		if ( empty( $args->theme_location ) || 'footer-menu' !== $args->theme_location ) {
			return $items;
		}

		foreach ( self::footer_links() as $label => $url ) {
			$items .= sprintf(
				'<li class="menu-item manacost-footer-link"><a href="%1$s">%2$s</a></li>',
				esc_url( $url ),
				esc_html( $label )
			);
		}

		return $items;
	}

	/**
	 * Replaces only the managed homepage pricing card with the supplied Boosty banner.
	 *
	 * @param false|string        $output Short-circuit output from an earlier filter.
	 * @param string              $tag    Shortcode tag.
	 * @param array<string,mixed> $attr   Shortcode attributes.
	 * @return false|string
	 */
	public static function replace_homepage_pricing_card( $output, string $tag, array $attr ) {
		if ( false !== $output || ! is_front_page() || 'tdm_block_pricing' !== $tag ) {
			return $output;
		}

		if (
			! isset( $attr['button_url'], $attr['tds_pricing'] )
			|| self::BOOSTY_URL !== $attr['button_url']
			|| 'tds_pricing1' !== $attr['tds_pricing']
		) {
			return $output;
		}

		return self::render_banner();
	}

	/**
	 * Supplies the requested support navigation labels and destinations.
	 *
	 * @return array<string,string>
	 */
	private static function footer_links(): array {
		return array(
			'Реклама на сайте'             => 'https://hs-manacost.ru/reklama-na-sajte/',
			'Конструктор колод'            => 'https://t.me/manacostcard_bot',
			'Как пользоваться Hearthpulse' => 'https://hs-manacost.ru/hearthpulse-chto-eto-i-kak-polzovatsya-servisom-zametki-taverny-2/',
		);
	}

	/**
	 * Renders an accessible, dimensioned image link to the existing Boosty target.
	 *
	 * @return string
	 */
	private static function render_banner(): string {
		$banner_url = plugin_dir_url( __FILE__ ) . 'manacost-boosty-promo/banner.webp';

		return sprintf(
			'<a class="manacost-boosty-promo" href="%1$s" aria-label="%2$s"><img src="%3$s" width="1172" height="1342" alt="" decoding="async"></a>',
			esc_url( self::BOOSTY_URL ),
			esc_attr( 'Поддержать Manacost на Boosty' ),
			esc_url( $banner_url )
		);
	}
}

Manacost_Boosty_Promo::boot();
