<?php
/**
 * Register supported core hooks.
 *
 * @package Manacost
 */

namespace Manacost\ApiGallery;

defined( 'ABSPATH' ) || exit;

/** Register supported core hooks. */
final class Gallery {
	/**
	 * Recursion guard for native rendering.
	 *
	 * @var bool
	 */
	private static bool $rendering = false;

	/**
	 * Register supported core hooks.
	 */
	public static function register(): void {
		// TagDiv's priority-10 callback replaces previous output even for core galleries.
		add_filter( 'post_gallery', array( self::class, 'render' ), 20, 2 );
		add_filter( 'wp_get_attachment_image_attributes', array( self::class, 'image_attributes' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'assets' ) );
	}

	/**
	 * Mark the attachment ID while rendering a rated gallery.
	 *
	 * @param array<string,mixed> $attributes Core image attributes.
	 * @param \WP_Post            $attachment Current image.
	 *
	 * @return array<string,mixed>
	 */
	public static function image_attributes( array $attributes, \WP_Post $attachment ): array {
		if ( self::$rendering ) {
			$attributes['data-hs-gallery-image'] = (string) $attachment->ID;
		}
		return $attributes;
	}

	/**
	 * Append rating controls to native WordPress gallery figures.
	 *
	 * @param string              $output Existing gallery override.
	 * @param array<string,mixed> $attributes Gallery shortcode.
	 *
	 * @return string Existing output or native gallery with ratings.
	 */
	public static function render( string $output, array $attributes ): string {
		if ( self::$rendering || '1' !== (string) ( $attributes['hs_ratings'] ?? '' ) || $output ) {
			return $output;
		}
		self::$rendering = true;
		unset( $attributes['hs_ratings'], $attributes['td_select_gallery_slide'] );
		try {
			$html = gallery_shortcode( $attributes );
		} finally {
			self::$rendering = false;
		}
		$columns = max( 1, min( 9, (int) ( $attributes['columns'] ?? 3 ) ) );
		$html    = preg_replace( '/class=([\'\"])gallery /', 'style="--hs-gallery-columns:' . $columns . '" class=$1gallery hs-gallery-rated ', $html, 1 );
		$post_id = get_the_ID();
		return (string) preg_replace_callback(
			'~<(figure|dl)\b[^>]*class=[\'\"]gallery-item[\'\"][^>]*>.*?</\1>~s',
			static function ( array $fragment ) use ( $post_id ): string {
				if ( ! preg_match( '/data-hs-gallery-image="([0-9]+)"/', $fragment[0], $image ) ) {
					return $fragment[0];
				}
				$rating = self::controls( (int) $post_id, (int) $image[1] );
				if ( 'dl' === $fragment[1] ) {
					$rating = '<dd>' . $rating . '</dd>';
				}
				return substr( $fragment[0], 0, -strlen( $fragment[1] ) - 3 ) . $rating . '</' . $fragment[1] . '>';
			},
			$html,
		);
	}

	/**
	 * Build accessible rating controls.
	 *
	 * @param int $post_id Article ID.
	 * @param int $image_id Attachment ID.
	 *
	 * @return string Accessible, background-free controls beneath the image.
	 */
	private static function controls( int $post_id, int $image_id ): string {
		$name = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
		$name = is_string( $name ) && $name ? $name : get_the_title( $image_id );
		$html = '<div class="hs-gallery-rating" data-post="' . esc_attr( (string) $post_id ) . '" data-image="' . esc_attr( (string) $image_id ) . '">';
		/* translators: %s: Image name. */
		$html .= '<div class="hs-gallery-rating__stars" role="group" aria-label="' . esc_attr( sprintf( __( 'Оценить: %s', 'manacost' ), $name ) ) . '">';
		for ( $score = 1; $score <= 5; ++$score ) {
			/* translators: %d: Selected number of stars. */
			$html .= '<button type="button" data-score="' . esc_attr( (string) $score ) . '" aria-pressed="false" disabled aria-label="' . esc_attr( sprintf( __( '%d из 5', 'manacost' ), $score ) ) . '"><span aria-hidden="true">★</span></button>';
		}
		$html .= '</div><div class="hs-gallery-rating__summary" aria-live="polite">' . esc_html__( 'Загрузка оценок…', 'manacost' ) . '</div>';
		$html .= '<button type="button" class="hs-gallery-rating__remove" hidden>' . esc_html__( 'Удалить мою оценку', 'manacost' ) . '</button></div>';
		return $html;
	}

	/**
	 * Eligibility comes from saved shortcode IDs, not a browser assertion.
	 *
	 * @param int $post_id Article ID.
	 * @return list<int> Public, rating-enabled gallery attachments.
	 */
	public static function eligible( int $post_id ): array {
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status || $post->post_password || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
			return array();
		}
		$ids = array();
		preg_match_all( '/' . get_shortcode_regex( array( 'gallery' ) ) . '/s', $post->post_content, $matches, PREG_SET_ORDER );
		foreach ( array_slice( $matches, 0, 100 ) as $match ) {
			if ( '[' === $match[1] && ']' === $match[6] ) {
				continue;
			}
			$attrs = shortcode_parse_atts( $match[3] );
			if ( '1' === (string) ( $attrs['hs_ratings'] ?? '' ) && 'slide' !== ( $attrs['td_select_gallery_slide'] ?? '' ) ) {
				$ids = array_merge( $ids, wp_parse_id_list( $attrs['ids'] ?? $attrs['include'] ?? '' ) );
			}
		}
		return array_values( array_unique( array_slice( $ids, 0, 400 ) ) );
	}

	/**
	 * Load rating assets only on a singular article with an enabled gallery.
	 */
	public static function assets(): void {
		if ( ! is_singular() || is_feed() ) {
			return;
		}
		$post = get_post();
		if ( ! $post || ! preg_match( '/\[gallery\b[^\]]*hs_ratings=[\'\"]?1/', $post->post_content ) ) {
			return;
		}
		$url = plugins_url( 'hs-api-gallery/', dirname( __DIR__ ) . '/hs-api-gallery.php' );
		wp_enqueue_style( 'hs-api-gallery-ratings', $url . 'ratings.css', array(), '1.0.0' );
		wp_enqueue_script( 'hs-api-gallery-ratings', $url . 'ratings.js', array( 'wp-i18n' ), '1.0.0', true );
		wp_add_inline_script(
			'hs-api-gallery-ratings',
			'window.hsGalleryRatings=' . wp_json_encode(
				array(
					'url'     => admin_url( 'admin-ajax.php', 'relative' ),
					'preview' => is_preview(),
				)
			) . ';',
			'before'
		);
	}
}
