<?php
/**
 * Plugin Name: Manacost Partner Placements
 * Description: Renders transparent first-party partner placements outside ad-network wrappers.
 * Version: 1.0.2
 * Author: Manacost
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Owns the direct Playerok and Sirus placements on public Manacost pages. */
final class Manacost_Partner_Placements {
	private const PLAYEROK_URL   = 'https://plrk.co/p/hsmanacostru1708';
	private const SIRUS_URL      = 'https://sirus.cc/hsmanacost';
	private const PLAYEROK_IMAGE = '/wp-content/uploads/2026/07/728x90.jpg.webp';
	private const SIRUS_IMAGE    = '/wp-content/uploads/2026/03/728h90.png.webp';

	/** Prevents themes that fire the header hook twice from duplicating the placement. */
	private static bool $rendered = false;

	/** Registers supported public extension points before regular plugins and the theme load. */
	public static function boot(): void {
		add_filter( 'option_td_011', array( __CLASS__, 'suppress_legacy_header' ), 100 );
		add_filter( 'option_ad_inserter', array( __CLASS__, 'suppress_legacy_article_block' ), 100 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
		add_action( 'td_wp_booster_after_header', array( __CLASS__, 'render_header' ), 20 );

		// The disposable integration theme omits Standard Pack, which owns the production hook.
		if ( function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type() ) {
			add_action( 'wp_body_open', array( __CLASS__, 'render_header' ), 100 );
		}
	}

	/**
	 * Removes only the known direct-partner header code from Newspaper's runtime copy.
	 *
	 * @param mixed $options Newspaper theme options.
	 * @return mixed
	 */
	public static function suppress_legacy_header( $options ) {
		if ( ! self::should_filter_legacy_options() || ! is_array( $options ) ) {
			return $options;
		}

		$code = $options['td_ads']['header']['ad_code'] ?? '';
		if ( ! is_string( $code ) || ! self::is_legacy_header_code( $code ) ) {
			return $options;
		}

		$options['td_ads']['header']['ad_code'] = '';

		return $options;
	}

	/**
	 * Removes only Ad Inserter block 2 when it is still the known duplicate Sirus creative.
	 *
	 * Ad Inserter stores its array in a versioned string, so the filtered return value must
	 * retain that format. This changes only the frontend copy and never writes the option.
	 *
	 * @param mixed $stored_value Raw Ad Inserter option.
	 * @return mixed
	 */
	public static function suppress_legacy_article_block( $stored_value ) {
		if ( ! self::should_filter_legacy_options() ) {
			return $stored_value;
		}

		$encoded = is_string( $stored_value ) && str_starts_with( $stored_value, ':AI:' );
		$value   = $encoded ? self::decode_ad_inserter_option( $stored_value ) : $stored_value;
		$code    = is_array( $value ) && isset( $value[2]['code'] ) && is_string( $value[2]['code'] )
			? $value[2]['code']
			: '';

		if ( ! self::is_legacy_article_code( $code ) ) {
			return $stored_value;
		}

		$value[2]['code'] = '';

		return $encoded ? ':AI:' . base64_encode( serialize( $value ) ) : $value; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
	}

	/** Loads the small layout stylesheet only on eligible public requests. */
	public static function enqueue_styles(): void {
		if ( ! self::should_render_public_request() ) {
			return;
		}

		wp_enqueue_style(
			'manacost-partner-placements',
			plugin_dir_url( __FILE__ ) . 'manacost-partner-placements/partner-placements.css',
			array(),
			'1.0.2'
		);
	}

	/** Renders both direct partners once after the public Newspaper header. */
	public static function render_header(): void {
		if ( self::$rendered || ! self::should_render_public_request() ) {
			return;
		}

		self::$rendered = true;
		?>
		<aside class="site-partnership" aria-label="<?php echo esc_attr( 'Партнёры сайта' ); ?>">
			<div class="site-partnership__inner">
				<p class="site-partnership__label"><?php echo esc_html( 'Реклама' ); ?></p>
				<div class="site-partnership__items">
					<a class="site-partnership__item" href="<?php echo esc_url( self::PLAYEROK_URL ); ?>" target="_blank" rel="sponsored noopener noreferrer" aria-label="<?php echo esc_attr( 'Playerok — партнёр Manacost' ); ?>">
						<img src="<?php echo esc_url( self::PLAYEROK_IMAGE ); ?>" width="729" height="90" alt="" decoding="async" fetchpriority="high">
					</a>
					<a class="site-partnership__item" href="<?php echo esc_url( self::SIRUS_URL ); ?>" target="_blank" rel="sponsored noopener noreferrer" aria-label="<?php echo esc_attr( 'Sirus — партнёр Manacost' ); ?>">
						<img src="<?php echo esc_url( self::SIRUS_IMAGE ); ?>" width="728" height="90" alt="" decoding="async">
					</a>
				</div>
			</div>
		</aside>
		<?php
	}

	/**
	 * Decodes Ad Inserter's trusted database format without allowing object creation.
	 *
	 * @param string $stored_value Encoded option value.
	 * @return mixed
	 */
	private static function decode_ad_inserter_option( string $stored_value ) {
		$serialized = base64_decode( substr( $stored_value, 4 ), true );
		if ( false === $serialized ) {
			return null;
		}

		return unserialize( $serialized, array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
	}

	/** Checks whether the Newspaper slot still contains the direct-partner rotator. */
	private static function is_legacy_header_code( string $code ): bool {
		return false !== strpos( $code, 'banner-rotator' )
			&& (
				false !== strpos( $code, 'plrk.co/p/' )
				|| false !== strpos( $code, 'sirus.cc/hsmanacost' )
			);
	}

	/** Checks whether Ad Inserter block 2 is still the known Sirus duplicate. */
	private static function is_legacy_article_code( string $code ): bool {
		return false !== strpos( $code, 'sirus.cc/hsmanacost' )
			&& false !== strpos( $code, '728h90.png' );
	}

	/** Returns whether the current request is a public page on a project host. */
	private static function should_render_public_request(): bool {
		if ( ! self::should_filter_legacy_options() ) {
			return false;
		}

		if (
			is_feed()
			|| is_preview()
			|| is_robots()
			|| is_trackback()
		) {
			return false;
		}

		return true;
	}

	/**
	 * Checks only request state that is safe while regular plugins load options.
	 *
	 * Query-dependent conditional tags belong in should_render_public_request().
	 */
	private static function should_filter_legacy_options(): bool {
		if (
			! self::feature_enabled()
			|| is_admin()
			|| wp_doing_ajax()
			|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		) {
			return false;
		}

		$host = self::request_host();
		if ( in_array( $host, array( 'hs-manacost.ru', 'www.hs-manacost.ru', 'hs-manacost.com', 'www.hs-manacost.com', 'test.hs-manacost.ru' ), true ) ) {
			return true;
		}

		return function_exists( 'wp_get_environment_type' )
			&& 'local' === wp_get_environment_type()
			&& in_array( $host, array( '127.0.0.1', 'localhost' ), true );
	}

	/** Gets the current request host without assuming the shared WordPress home URL. */
	private static function request_host(): string {
		$host = isset( $_SERVER['HTTP_HOST'] ) && is_string( $_SERVER['HTTP_HOST'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) )
			: (string) wp_parse_url( home_url(), PHP_URL_HOST );
		$host = (string) wp_parse_url( 'https://' . $host, PHP_URL_HOST );

		return strtolower( rtrim( $host, '.' ) );
	}

	/** Reads the emergency kill switch, which defaults to enabled. */
	private static function feature_enabled(): bool {
		if ( ! defined( 'MANACOST_PARTNER_PLACEMENTS_ENABLED' ) ) {
			return true;
		}

		$value = constant( 'MANACOST_PARTNER_PLACEMENTS_ENABLED' );
		if ( is_bool( $value ) ) {
			return $value;
		}

		return ! in_array( strtolower( trim( (string) $value ) ), array( '0', 'false', 'off', 'no' ), true );
	}
}

Manacost_Partner_Placements::boot();
