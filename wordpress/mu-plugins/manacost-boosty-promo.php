<?php
/**
 * Plugin Name: Manacost Boosty Promo
 * Description: Renders the managed Boosty banner on the homepage and article sidebar, plus footer support links.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Keeps the managed support promotion and footer help links update-safe.
 */
final class Manacost_Boosty_Promo {
	private const BOOSTY_URL               = 'https://boosty.to/kolodahearthstone';
	private const LEGACY_SIDEBAR_WIDGET_ID = 'block-37';
	private const LEGACY_SIDEBAR_IMAGE     = '/wp-content/uploads/2026/01/64b4112a-4bbb-4093-a12a-1f92b1b1defc.jpg';
	private const LEGACY_SIDEBAR_URL       = 'https://web.tribute.tg/s/xz9';

	/**
	 * Registers public WordPress extension points.
	 *
	 * @return void
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
		add_filter( 'wp_nav_menu_items', array( __CLASS__, 'append_footer_links' ), 10, 2 );
		add_filter( 'pre_do_shortcode_tag', array( __CLASS__, 'replace_homepage_pricing_card' ), 10, 3 );
		add_filter( 'widget_block_content', array( __CLASS__, 'replace_legacy_sidebar_banner' ), 10, 3 );
		add_filter( 'style_loader_tag', array( __CLASS__, 'exclude_layout_styles_from_minification' ), 10, 4 );
		add_filter( 'perfmatters_minify_css_exclusions', array( __CLASS__, 'exclude_layout_styles_from_perfmatters' ) );
	}

	/**
	 * Loads the navigation and managed banner presentation on public pages.
	 *
	 * @return void
	 */
	public static function enqueue_styles(): void {
		if ( is_admin() ) {
			return;
		}

		/* Composer emits a font declaration after stylesheet assets. Keep navigation legible with a local system stack. */
		wp_enqueue_style(
			'manacost-site-navigation',
			plugin_dir_url( __FILE__ ) . 'manacost-site-navigation.css',
			array(),
			'1.5.1'
		);

		wp_enqueue_style(
			'manacost-boosty-promo',
			plugin_dir_url( __FILE__ ) . 'manacost-boosty-promo.css',
			array(),
			'1.3.2'
		);
	}

	/**
	 * Keeps the tiny layout styles out of the long-lived combined CSS cache.
	 *
	 * These two assets are intentionally separate so an emergency layout fix is
	 * visible immediately after the stylesheet version changes.
	 *
	 * @param string $html   Generated stylesheet tag.
	 * @param string $handle Registered stylesheet handle.
	 * @param string $href   Stylesheet URL.
	 * @param string $media  Stylesheet media attribute.
	 * @return string
	 */
	public static function exclude_layout_styles_from_minification( string $html, string $handle, string $href, string $media ): string {
		unset( $href, $media );

		if ( ! in_array( $handle, array( 'manacost-site-navigation', 'manacost-boosty-promo' ), true ) ) {
			return $html;
		}

		return str_replace( '<link ', '<link data-no-minify="1" ', $html );
	}

	/**
	 * Prevents Perfmatters from replacing these versioned emergency layout files
	 * with a stale cache artifact.
	 *
	 * @param array<int,string> $exclusions Existing CSS source exclusions.
	 * @return array<int,string>
	 */
	public static function exclude_layout_styles_from_perfmatters( array $exclusions ): array {
		$exclusions[] = 'manacost-site-navigation.css';
		$exclusions[] = 'manacost-boosty-promo.css';

		return array_values( array_unique( $exclusions ) );
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

		return self::render_homepage_banner();
	}

	/**
	 * Replaces only the known legacy Telegram image widget in article sidebars.
	 *
	 * Matching both the widget identity and its legacy content means an editor can
	 * replace the block later without this compatibility layer taking it over.
	 *
	 * @param string          $content  Rendered block widget content.
	 * @param array<mixed>    $instance Widget settings.
	 * @param WP_Widget_Block $widget   Block widget instance.
	 * @return string
	 */
	public static function replace_legacy_sidebar_banner( string $content, array $instance, WP_Widget_Block $widget ): string {
		unset( $instance );

		if ( self::LEGACY_SIDEBAR_WIDGET_ID !== $widget->id ) {
			return $content;
		}

		if (
			false === strpos( $content, self::LEGACY_SIDEBAR_IMAGE )
			&& false === strpos( $content, self::LEGACY_SIDEBAR_URL )
		) {
			return $content;
		}

		return self::render_sidebar_banner();
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
	 * Renders the previous Boosty creative selected for the homepage.
	 *
	 * @return string
	 */
	private static function render_homepage_banner(): string {
		return self::render_banner( 'manacost-boosty-promo/homepage-banner.webp?ver=44d5f1f12b09', 1172, 1342 );
	}

	/**
	 * Renders the current Boosty creative selected for article sidebars.
	 *
	 * @return string
	 */
	private static function render_sidebar_banner(): string {
		return self::render_banner( 'manacost-boosty-promo/banner.webp?ver=5247cc0c54b8', 1173, 1341 );
	}

	/**
	 * Renders an accessible, dimensioned image link to the existing Boosty target.
	 *
	 * @param string $asset_path Banner path relative to the MU-plugin directory.
	 * @param int    $width      Intrinsic image width.
	 * @param int    $height     Intrinsic image height.
	 * @return string
	 */
	private static function render_banner( string $asset_path, int $width, int $height ): string {
		$banner_url = plugin_dir_url( __FILE__ ) . $asset_path;

		return sprintf(
			'<a class="manacost-boosty-promo" href="%1$s" aria-label="%2$s"><img src="%3$s" width="%4$d" height="%5$d" alt="" decoding="async"></a>',
			esc_url( self::BOOSTY_URL ),
			esc_attr( 'Поддержать Manacost на Boosty' ),
			esc_url( $banner_url ),
			$width,
			$height
		);
	}
}

Manacost_Boosty_Promo::boot();
