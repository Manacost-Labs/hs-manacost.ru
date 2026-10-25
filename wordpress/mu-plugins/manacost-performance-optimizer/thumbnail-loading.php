<?php
/**
 * Native loading for the site's existing Newspaper background thumbnails.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Migrates only simple thumbnail backgrounds, retaining their exact URLs. */
final class Manacost_Thumbnail_Loading {

	private const EAGER_THUMBNAILS = 14;
	private const STYLE_ID         = 'manacost-native-thumbnails';

	/**
	 * Leaves protected HTML and the first viewport's thumbnails unchanged.
	 *
	 * @param string $html Rendered public HTML.
	 * @return string
	 */
	public static function optimize_html( string $html ): string {
		// Templates can nest; leave these documents to their existing renderer.
		if ( ! preg_match( '~</head\s*>~i', $html ) || preg_match( '~<template\b~i', $html ) ) {
			return $html;
		}
		$attributes = '(?:[^"\'<>]|"[^"]*"|\'[^\']*\')*';
		$pattern    = '~<!--[\s\S]*?(?:-->|\z)|<(script|style|textarea|template|noscript)\b' . $attributes
			. '>[\s\S]*?(?:</\1\s*>|\z)|(<span\b' . $attributes . '>)(\s*</span\s*>)?|<' . $attributes . '>~i';
		$seen       = 0;
		$converted  = 0;
		$result     = preg_replace_callback(
			$pattern,
			static function ( array $matches ) use ( &$seen, &$converted ): string {
				if ( empty( $matches[2] ) ) {
					return $matches[0];
				}
				$classes = preg_split( '/\s+/', trim( Manacost_Performance_Optimizer::extract_attr( $matches[2], 'class' ) ) );
				if ( false === $classes ) {
					return $matches[0];
				}
				if ( ! in_array( 'entry-thumb', $classes, true ) || ! in_array( 'td-thumb-css', $classes, true ) ) {
					return $matches[0];
				}
				++$seen;
				if ( $seen <= self::EAGER_THUMBNAILS || empty( $matches[3] ) ) {
					return $matches[0];
				}
				$replacement = self::convert_thumbnail( $matches[2], $classes );
				if ( '' === $replacement ) {
					return $matches[0];
				}
				++$converted;
				return $replacement . $matches[3];
			},
			$html
		);
		if ( null === $result || 0 === $converted ) {
			return $html;
		}
		$result = self::preserve_composer_positions( $result );
		$css    = '<style id="' . self::STYLE_ID . '">'
			. '.entry-thumb>.manacost-native-thumbnail{position:absolute;inset:0;display:block;width:100%;height:100%;max-width:none;object-fit:cover;object-position:var(--manacost-thumbnail-position,center center);border-radius:inherit;pointer-events:none}'
			. '</style>';
		return preg_replace( '~</head\s*>~i', $css . '</head>', $result, 1 ) ?? $html;
	}

	/**
	 * Shares Composer's background alignment with the image through the cascade.
	 *
	 * Custom properties retain selector specificity and media queries; no font
	 * declaration or external stylesheet is rewritten.
	 *
	 * @param string $html Rendered public HTML.
	 * @return string
	 */
	private static function preserve_composer_positions( string $html ): string {
		$attributes = '(?:[^"\'<>]|"[^"]*"|\'[^\']*\')*';
		$pattern    = '~<!--[\s\S]*?(?:-->|\z)|<(script|textarea|noscript)\b' . $attributes
			. '>[\s\S]*?(?:</\1\s*>|\z)|(<style\b' . $attributes . '>[\s\S]*?</style\s*>)~i';
		return preg_replace_callback(
			$pattern,
			static function ( array $style ): string {
				if ( empty( $style[2] ) ) {
					return $style[0];
				}
				return preg_replace_callback(
					'/(?<=[{;])\s*background-position\s*:\s*((?:center|left|right|top|bottom|[0-9.]+%)(?:\s+(?:center|left|right|top|bottom|[0-9.]+%))?\s*(?:!important)?)\s*(?=[;}])/i',
					static function ( array $position ): string {
						return '--manacost-thumbnail-position:' . $position[1] . ';' . $position[0];
					},
					$style[0]
				) ?? $style[0];
			},
			$html
		) ?? $html;
	}

	/**
	 * Builds a decorative native image inside the existing thumbnail box.
	 *
	 * @param string $tag     Existing opening span tag.
	 * @param array  $classes Existing class names.
	 * @phpstan-param list<string> $classes
	 * @return string Empty when the thumbnail has another loader or custom styling.
	 */
	private static function convert_thumbnail( string $tag, array $classes ): string {
		if ( array_diff( $classes, array( 'entry-thumb', 'td-thumb-css' ) ) || preg_match( '/\sdata-(?:bg|img-url|type)\s*=/i', $tag ) ) {
			return '';
		}
		$style      = Manacost_Performance_Optimizer::extract_attr( $tag, 'style' );
		$background = self::parse_background( $style );
		if ( null === $background ) {
			return '';
		}
		$new_tag = preg_replace_callback(
			'/(\sstyle=)(["\'])(.*?)\2/is',
			static function ( array $matches ) use ( $background ): string {
				return $matches[1] . '"' . esc_attr( $background['style'] ) . '"';
			},
			$tag,
			1
		) ?? $tag;
		return $new_tag . '<img class="manacost-native-thumbnail skip-lazy" src="' . esc_url( $background['url'] )
			. '" loading="lazy" decoding="async" data-no-lazy="1" alt="" aria-hidden="true" style="object-position:var(--manacost-thumbnail-position,'
			. esc_attr( $background['position'] ) . ')">';
	}

	/**
	 * Accepts one cover background while preserving all unrelated declarations.
	 *
	 * @param string $style Decoded inline CSS.
	 * @return array{url:string,style:string,position:string}|null
	 */
	private static function parse_background( string $style ): ?array {
		$pattern = '~(?:^|;)\s*background-image\s*:\s*url\(\s*(?:"([^"]+)"|\'([^\']+)\'|([^\s()"\']+))\s*\)\s*(?:!important\s*)?(?=;|$)~i';
		if ( ! preg_match( $pattern, $style, $match, PREG_OFFSET_CAPTURE ) ) {
			return null;
		}
		$url = '';
		foreach ( array_slice( $match, 1 ) as $part ) {
			if ( '' !== $part[0] ) {
				$url = $part[0];
				break;
			}
		}
		if ( ! preg_match( '~^https?://[^\s\\\\<>"\']+$~i', $url ) ) {
			return null;
		}
		$remaining = substr_replace( $style, '', $match[0][1], strlen( $match[0][0] ) );
		if ( preg_match( '/(?:^|;)\s*background(?:-image)?\s*:/i', $remaining ) ) {
			return null;
		}
		if ( preg_match( '/(?:^|;)\s*background-size\s*:\s*([^;]+)/i', $remaining, $size ) && 'cover' !== trim( $size[1] ) ) {
			return null;
		}
		$position = 'center center';
		if ( preg_match( '/(?:^|;)\s*background-position\s*:\s*([^;]+)/i', $remaining, $position_match ) ) {
			$position = trim( $position_match[1] );
			if ( ! preg_match( '/^(?:center|left|right|top|bottom|[0-9.]+%)(?:\s+(?:center|left|right|top|bottom|[0-9.]+%))?$/i', $position ) ) {
				return null;
			}
			$remaining .= ';--manacost-thumbnail-position:' . $position;
		}
		return array(
			'url'      => $url,
			'style'    => trim( $remaining, '; ' ),
			'position' => $position,
		);
	}
}
