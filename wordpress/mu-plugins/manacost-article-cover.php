<?php
/**
 * Plugin Name: Manacost Article Cover Loading
 * Description: Keeps the Newspaper article cover eager and its preload aligned with the rendered image.
 * Version: 1.2.0
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Optimizes only the public article cover, not images in the article body. */
final class Manacost_Article_Cover {
	private const MOBILE_MIN_WIDTH = 640;
	private const MOBILE_MAX_WIDTH = 768;

	/** Registers a non-Rocket fallback and a final, cacheable Rocket pass. */
	public static function boot(): void {
		add_action( 'template_redirect', array( __CLASS__, 'start' ), -201 );
		add_filter( 'rocket_buffer', array( __CLASS__, 'filter_html' ), PHP_INT_MAX );
	}

	/** Starts the public article buffer before the other first-view optimizers. */
	public static function start(): void {
		if ( self::is_eligible() ) {
			ob_start( array( __CLASS__, 'optimize_html' ) );
		}
	}

	/**
	 * Corrects Rocket's final HTML before it is written to page cache.
	 *
	 * @param string $html Rendered HTML.
	 * @return string
	 */
	public static function filter_html( string $html ): string {
		return self::is_eligible() ? self::optimize_html( $html ) : $html;
	}

	/** Excludes authenticated, non-article and non-document requests. */
	private static function is_eligible(): bool {
		if ( defined( 'MANACOST_ARTICLE_COVER_ENABLED' ) && ! MANACOST_ARTICLE_COVER_ENABLED ) {
			return false;
		}

		return is_singular( 'post' )
			&& ! is_admin()
			&& ! is_user_logged_in()
			&& ! wp_doing_ajax()
			&& ! is_feed()
			&& ! is_preview()
			&& ! is_embed()
			&& ! ( defined( 'REST_REQUEST' ) && REST_REQUEST );
	}

	/**
	 * Restores responsive attributes and promotes only the supported cover IMG.
	 *
	 * @param string $html Rendered HTML, possibly already processed by Rocket.
	 * @return string
	 */
	public static function optimize_html( string $html ): string {
		if ( false === stripos( $html, '</head>' ) ) {
			return $html;
		}

		$cover = self::find_cover( $html );
		if ( null === $cover ) {
			return $html;
		}

		$attributes = array();
		foreach ( array( 'src', 'srcset', 'sizes' ) as $name ) {
			$value               = $cover->get_attribute( 'data-lazy-' . $name ) ?? $cover->get_attribute( $name );
			$attributes[ $name ] = is_string( $value ) ? $value : '';
		}

		if ( ! preg_match( '#^(?:https?:)?//#i', $attributes['src'] ) ) {
			return $html;
		}
		$source_url = $attributes['src'];
		$attributes = self::bound_mobile_candidates( $attributes );

		foreach ( $attributes as $name => $value ) {
			if ( '' !== $value ) {
				$cover->set_attribute( $name, $value );
			}
			$cover->remove_attribute( 'data-lazy-' . $name );
		}
		$cover->set_attribute( 'loading', 'eager' );
		$cover->set_attribute( 'fetchpriority', 'high' );
		$cover->set_attribute( 'data-no-lazy', '1' );

		$html = self::align_preload( $cover->get_updated_html(), $attributes, $source_url );
		return self::defer_mobile_module_thumbnails( $html );
	}

	/**
	 * Defers article module thumbnails that sit outside the mobile first view.
	 *
	 * @param string $html Rendered article HTML.
	 * @return string
	 */
	private static function defer_mobile_module_thumbnails( string $html ): string {
		if ( ! wp_is_mobile() ) {
			return $html;
		}

		$processor = new WP_HTML_Tag_Processor( $html );
		while ( $processor->next_tag(
			array(
				'tag_name'   => 'DIV',
				'class_name' => 'td-module-thumb',
			)
		) ) {
			while ( $processor->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
				$tag = $processor->get_tag();
				if ( 'DIV' === $tag ) {
					break;
				}
				if ( 'IMG' !== $tag ) {
					continue;
				}
				$processor->set_attribute( 'loading', 'lazy' );
				$processor->set_attribute( 'decoding', 'async' );
				$processor->remove_attribute( 'data-no-lazy' );
				break;
			}
		}

		return $processor->get_updated_html();
	}

	/**
	 * Caps Retina mobile covers at a generated size without changing desktop.
	 *
	 * @param array<string, string> $attributes Responsive cover attributes.
	 * @return array<string, string>
	 */
	private static function bound_mobile_candidates( array $attributes ): array {
		if ( ! wp_is_mobile() || '' === $attributes['srcset'] ) {
			return $attributes;
		}

		$candidates = preg_split( '/\s*,\s*/', $attributes['srcset'], -1, PREG_SPLIT_NO_EMPTY );
		if ( false === $candidates ) {
			return $attributes;
		}

		$bounded     = array();
		$largest_url = '';
		$largest     = 0;
		foreach ( $candidates as $candidate ) {
			if ( ! preg_match( '/^(\S+)\s+(\d+)w$/', trim( $candidate ), $matches ) ) {
				continue;
			}
			$width = (int) $matches[2];
			if ( $width < self::MOBILE_MIN_WIDTH || $width > self::MOBILE_MAX_WIDTH ) {
				continue;
			}
			$bounded[] = $matches[1] . ' ' . $width . 'w';
			if ( $width > $largest ) {
				$largest     = $width;
				$largest_url = $matches[1];
			}
		}

		if ( '' === $largest_url ) {
			return $attributes;
		}

		$attributes['src']    = $largest_url;
		$attributes['srcset'] = implode( ', ', $bounded );
		return $attributes;
	}

	/**
	 * Finds the first standard featured image without crossing its container.
	 *
	 * @param string $html Rendered HTML.
	 * @return WP_HTML_Tag_Processor|null
	 */
	private static function find_cover( string $html ): ?WP_HTML_Tag_Processor {
		$processor = new WP_HTML_Tag_Processor( $html );
		if ( ! $processor->next_tag(
			array(
				'tag_name'   => 'DIV',
				'class_name' => 'td-post-featured-image',
			)
		) ) {
			return null;
		}

		while ( $processor->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
			$tag = $processor->get_tag();
			// Unknown nested layouts/art direction retain their existing policy.
			if ( in_array( $tag, array( 'DIV', 'PICTURE', 'NOSCRIPT' ), true ) ) {
				return null;
			}
			if ( 'IMG' === $tag ) {
				return $processor;
			}
		}

		return null;
	}

	/**
	 * Aligns only matching preloads, preserving unrelated banners and hints.
	 *
	 * @param string                $html       Rendered HTML with eager cover.
	 * @param array<string, string> $attributes The cover's final responsive attributes.
	 * @param string                $source_url The cover URL before mobile candidate selection.
	 * @return string
	 */
	private static function align_preload( string $html, array $attributes, string $source_url ): string {
		$processor = new WP_HTML_Tag_Processor( $html );
		while ( $processor->next_tag( array( 'tag_name' => 'LINK' ) ) ) {
			if ( 'preload' !== $processor->get_attribute( 'rel' ) || 'image' !== $processor->get_attribute( 'as' ) ) {
				continue;
			}
			$href = $processor->get_attribute( 'href' );
			if ( ! is_string( $href ) ) {
				continue;
			}
			// Nginx negotiates formats on the original URL; do not force a sidecar.
			$original = preg_replace( '/(\.(?:jpe?g|png))\.(?:webp|avif)(?=[?#]|$)/i', '$1', $href );
			if ( ! in_array( $href, array( $source_url, $attributes['src'] ), true ) && ! in_array( $original, array( $source_url, $attributes['src'] ), true ) ) {
				continue;
			}
			$preload_attributes = array(
				'href'        => 'src',
				'imagesrcset' => 'srcset',
				'imagesizes'  => 'sizes',
			);
			foreach ( $preload_attributes as $name => $source ) {
				if ( '' === $attributes[ $source ] ) {
					$processor->remove_attribute( $name );
				} else {
					$processor->set_attribute( $name, $attributes[ $source ] );
				}
			}
			$processor->set_attribute( 'fetchpriority', 'high' );
		}

		return $processor->get_updated_html();
	}
}

Manacost_Article_Cover::boot();
