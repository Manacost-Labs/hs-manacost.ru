<?php
/**
 * Plugin Name: HS Google Preferred Source
 * Description: Shows a compact "add as a preferred source on Google" link at the end of articles.
 * Version: 1.0.0
 * Author: Manacost
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Appends one compact Google preferred-source link after the article text,
 * right above the related-articles block.
 */
final class HS_Google_Preferred_Source {
	/** Google preferred-source page for the canonical domain. */
	public const URL = 'https://www.google.com/preferences/source?q=https://hs-manacost.ru';

	private const HANDLE  = 'hs-google-preferred-source';
	private const VERSION = '1.0.0';
	private const MARKER  = 'class="hs-google-source"';

	/** Registers the content filter after the RSYA end-of-article banner (priority 30). */
	public static function boot(): void {
		add_filter( 'the_content', array( __CLASS__, 'append_block' ), 40 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_style' ), 30 );
	}

	/** Prints the small stylesheet inline, only on public articles. */
	public static function enqueue_style(): void {
		if ( ! self::is_public_article() ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads a bundled local stylesheet.
		$css = file_get_contents( __DIR__ . '/hs-google-preferred-source/google-source.css' );
		if ( false === $css ) {
			return;
		}

		wp_register_style( self::HANDLE, false, array(), self::VERSION );
		wp_enqueue_style( self::HANDLE );
		wp_add_inline_style( self::HANDLE, $css );
	}

	/**
	 * Appends the block to the main article body once.
	 *
	 * @param mixed $content Filtered post content.
	 * @return mixed
	 */
	public static function append_block( $content ) {
		if (
			! is_string( $content )
			|| ! in_the_loop()
			|| ! is_main_query()
			|| get_the_ID() !== get_queried_object_id()
			|| ! self::is_public_article()
			|| str_contains( $content, self::MARKER )
		) {
			return $content;
		}

		return $content . self::markup();
	}

	/** Returns the block markup; all dynamic values are escaped. */
	public static function markup(): string {
		return '<aside ' . self::MARKER . ' aria-label="' . esc_attr__( 'Манакост в Google', 'manacost' ) . '">'
			. '<a class="hs-google-source__link" href="' . esc_url( self::URL ) . '" target="_blank" rel="noopener nofollow">'
			. '<svg class="hs-google-source__logo" width="22" height="22" viewBox="0 0 48 48" aria-hidden="true" focusable="false">'
			. '<path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>'
			. '<path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>'
			. '<path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>'
			. '<path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>'
			. '</svg>'
			. '<span class="hs-google-source__text">' . esc_html__( 'Добавьте Манакост в предпочитаемые источники Google', 'manacost' ) . '</span>'
			. '<span class="hs-google-source__cta" aria-hidden="true">' . esc_html__( 'Добавить', 'manacost' ) . '</span>'
			. '<span class="hs-google-source__hidden">' . esc_html__( '(откроется в новой вкладке)', 'manacost' ) . '</span>'
			. '</a></aside>';
	}

	/** Whether the request renders a published article for a reader, not a feed, API, preview or PDF. */
	private static function is_public_article(): bool {
		if (
			is_admin()
			|| is_feed()
			|| is_preview()
			|| wp_doing_ajax()
			|| wp_is_json_request()
			|| defined( 'MANACOST_GUIDE_PDF_RENDERING' )
			|| ! is_singular( 'post' )
		) {
			return false;
		}

		$post = get_queried_object();

		return $post instanceof WP_Post && 'publish' === $post->post_status;
	}
}

HS_Google_Preferred_Source::boot();
