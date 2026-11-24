<?php
/**
 * Reduce white first paint and preload only the leading LCP image.
 *
 * @package Manacost
 */

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_dequeue_style( 'font_awesome' );
		wp_deregister_style( 'font_awesome' );
	},
	100
);

add_action(
	'wp_head',
	function () {
		echo '<style id="hs-fontawesome-fallback">.tdc-font-fa-check:before{content:"\2713";font-family:Arial,sans-serif;font-weight:700;line-height:1}.tdc-font-fa{font-family:Arial,sans-serif}</style>' . "\n";
	},
	1
);

/**
 * Resolve a local upload to its existing WebP sibling.
 *
 * @param string $url Original upload URL.
 */
function hs_manacost_webp_url_for_upload( string $url ): string|false {
	if ( ! preg_match( '/\.(?:jpe?g|png)(?:[?#].*)?$/i', $url ) ) {
		return false;
	}

	$parts     = wp_parse_url( $url );
	$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( ! is_string( $site_host ) || empty( $parts['host'] ) || strcasecmp( $parts['host'], $site_host ) !== 0 || empty( $parts['path'] ) ) {
		return false;
	}

	$uploads   = wp_get_upload_dir();
	$base_path = wp_parse_url( $uploads['baseurl'], PHP_URL_PATH );
	if ( ! $base_path || ! str_starts_with( $parts['path'], $base_path . '/' ) ) {
		return false;
	}

	$relative_path = rawurldecode( substr( $parts['path'], strlen( $base_path ) ) );
	$source_path   = $uploads['basedir'] . $relative_path;
	$webp_path     = preg_replace( '/\.(?:jpe?g|png)$/i', '.webp', $source_path );
	if ( $webp_path && is_readable( $webp_path ) ) {
		return preg_replace( '/\.(?:jpe?g|png)([?#].*)?$/i', '.webp$1', $url ) ?? false;
	}

	$webp_path = $source_path . '.webp';
	if ( is_readable( $webp_path ) ) {
		return preg_replace( '/([?#].*)?$/', '.webp$1', $url ) ?? false;
	}

	return false;
}

/**
 * Prefer available WebP files for Newspaper thumbnail backgrounds.
 *
 * @param string $html Rendered document.
 */
function hs_manacost_rewrite_backgrounds_to_webp( string $html ): string {
	return preg_replace_callback(
		'/<span\b(?=[^>]+class=["\'][^"\']*entry-thumb[^"\']*["\'])[^>]*>/i',
		function ( $matches ) {
			return preg_replace_callback(
				'/background-image:\s*url\((?:&quot;|["\']?)(https?:\/\/[^)"\']+\.(?:jpe?g|png))(?:&quot;|["\']?)\)/i',
				function ( $url_matches ) {
					$webp_url = hs_manacost_webp_url_for_upload( html_entity_decode( $url_matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
					if ( ! $webp_url ) {
						return $url_matches[0];
					}
					return 'background-image: url(&quot;' . esc_url( $webp_url ) . '&quot;)';
				},
				$matches[0]
			) ?? $matches[0];
		},
		$html
	) ?? $html;
}

/**
 * Prefer available WebP files in CSS background declarations.
 *
 * @param string $html Rendered document.
 */
function hs_manacost_rewrite_css_backgrounds_to_webp( string $html ): string {
	return preg_replace_callback(
		'/background-image:\s*url\((&quot;|["\']?)(https?:\/\/[^)"\'&]+?\.(?:jpe?g|png)(?:[?#][^)"\'&]*)?)\1?\)/i',
		function ( $matches ) {
			$webp_url = hs_manacost_webp_url_for_upload( html_entity_decode( $matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
			if ( ! $webp_url ) {
				return $matches[0];
			}

			return 'background-image: url(' . $matches[1] . esc_url( $webp_url ) . $matches[1] . ')';
		},
		$html
	) ?? $html;
}

/**
 * Use available WebP files for high-density image attributes.
 *
 * @param string $html Rendered document.
 */
function hs_manacost_rewrite_retina_uploads_to_webp( string $html ): string {
	return preg_replace_callback(
		'/\b(data-retina)=(["\'])(https?:\/\/[^"\']+?\.(?:jpe?g|png)(?:[?#][^"\']*)?)\2/i',
		function ( $matches ) {
			$webp_url = hs_manacost_webp_url_for_upload( html_entity_decode( $matches[3], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
			if ( ! $webp_url ) {
				return $matches[0];
			}

			return $matches[1] . '=' . $matches[2] . esc_url( $webp_url ) . $matches[2];
		},
		$html
	) ?? $html;
}

/**
 * Fit the Neverest artwork to the gutters of Newspaper's 1164px boxed layout.
 * Use the theme's rendered body rule so category backgrounds and later campaigns
 * retain their own styling, including when a signed-in visitor opens the page.
 *
 * @param string $html Rendered document.
 */
function hs_manacost_partner_wallpaper_css( string $html ): string {
	$head_end = stripos( $html, '</head>' );
	$asset    = 'neverest_manacost_wallpaper_2400x1500';
	$offset   = strpos( $html, $asset );
	if ( false === $head_end || false === $offset || $offset >= $head_end || strpos( $html, 'id="manacost-partner-wallpaper"' ) !== false ) {
		return '';
	}

	$head = new WP_HTML_Tag_Processor( substr( $html, 0, $head_end ) );
	while ( $head->next_tag( array( 'tag_name' => 'STYLE' ) ) ) {
		$css = $head->get_modifiable_text();
		if ( strpos( $css, $asset ) === false ) {
			continue;
		}
		// Match only a body background declaration, never a thumbnail or preload.
		$pattern = '~(?:^|\}|\*/)\s*body\s*\{[^{}]*?\bbackground-image\s*:\s*url\(\s*(["\']?)'
			. '((?:https?://[^"\'()<>\s\\\\]+)?/wp-content/uploads/\d{4}/\d{2}/'
			. $asset . '\.(?:png(?:\.webp)?|webp)(?:\?[^"\'()<>\s\\\\]*)?)\1\s*\)~i';
		if ( ! preg_match( $pattern, $css, $match ) ) {
			continue;
		}
		$image = $match[2];
		// Use the smaller derivative for the landscape and portraits on standard-density laptops.
		$compact = preg_replace( '~' . $asset . '\.(?:png(?:\.webp)?|webp)(?=\?|$)~', $asset . '-768x480.png.webp', $image, 1 ) ?? $image;
		$layout  = file_get_contents( __DIR__ . '/manacost-partner-placements/neverest-wallpaper.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Bundled local stylesheet.
		if ( false === $layout ) {
			return '';
		}
		return '<style id="manacost-partner-wallpaper">body.td-boxed-layout{--mc-full-art:url("' . $image
			. '");--mc-compact-art:image-set(url("' . $compact . '") 1x,var(--mc-full-art) 2x)}'
			. $layout . '</style>';
	}
	return '';
}

/**
 * Attach one native sponsored link to the active Newspaper wallpaper.
 *
 * @param string $html Rendered document.
 */
function hs_manacost_apply_partner_wallpaper( string $html ): string {
	$css = hs_manacost_partner_wallpaper_css( $html );
	if ( '' === $css ) {
		return $html;
	}
	$link     = '<a class="mc-wallpaper-link" href="https://sirus.one/registration?ref=3586993&amp;utm_source=hsmanacost&amp;utm_medium=twitch"'
		. ' target="_blank" rel="sponsored noopener noreferrer" aria-label="Регистрация на Sirus — откроется в новой вкладке">'
		. '<span class="mc-wallpaper-gutter mc-wallpaper-gutter-left"></span><span class="mc-wallpaper-gutter mc-wallpaper-gutter-right"></span>'
		. '<span class="mc-wallpaper-wing mc-wallpaper-wing-left"></span><span class="mc-wallpaper-wing mc-wallpaper-wing-right"></span></a>';
	$head_end = stripos( $html, '</head>' );
	if ( false === $head_end ) {
		return $html;
	}
	$body = substr( $html, $head_end );
	$tags = new WP_HTML_Tag_Processor( $body );
	while ( $tags->next_tag( array( 'tag_name' => 'DIV' ) ) ) {
		if ( $tags->get_attribute( 'id' ) === 'td-outer-wrap' ) {
			$tags->set_bookmark( 'boxed-layout' );
			break;
		}
	}
	if ( ! $tags->has_bookmark( 'boxed-layout' ) ) {
		return $html;
	}
	// Keep the portraits in the header's own stacking context, below its menu.
	if ( ! $tags->next_tag(
		array(
			'tag_name'   => 'DIV',
			'class_name' => 'td-header-wrap',
		)
	) ) {
		$tags->seek( 'boxed-layout' );
	}
	// The HTML API can mark the actual tag but cannot append a child element.
	$marker = 'data-mc-wallpaper-mount';
	if ( stripos( $body, $marker ) !== false || ! $tags->set_attribute( $marker, true ) ) {
		return $html;
	}
	$attributes = '(?:[^"\'<>]|"[^"]*"|\'[^\']*\')*';
	$updated    = preg_replace_callback( '~ ' . $marker . '(' . $attributes . '>)~', static fn( $m ) => $m[1] . $link, $tags->get_updated_html(), 1, $count );
	if ( 1 !== $count || null === $updated ) {
		return $html;
	}
	return substr( $html, 0, $head_end ) . $css . $updated;
}

/**
 * Remove redundant image preload hints generated by WP Rocket.
 *
 * @param string $html Rendered document.
 */
function hs_manacost_remove_rocket_image_preloads( string $html ): string {
	return preg_replace( '/<link(?=[^>]+\bdata-rocket-preload\b)(?=[^>]+\bas=["\']image["\'])[^>]*>\s*/i', '', $html ) ?? $html;
}

add_action(
	'template_redirect',
	function () {
		if ( is_admin() || wp_doing_ajax() || is_feed() || is_robots() || is_trackback() ) {
			return;
		}

		$preload_home_thumbs = is_front_page() || is_home();

		ob_start(
			function ( $html ) use ( $preload_home_thumbs ) {
				if ( stripos( $html, '<html' ) === false || stripos( $html, '</head>' ) === false ) {
					return $html;
				}

				$html = hs_manacost_rewrite_backgrounds_to_webp( $html );
				$html = hs_manacost_rewrite_css_backgrounds_to_webp( $html );
				$html = hs_manacost_rewrite_retina_uploads_to_webp( $html );
				if ( $preload_home_thumbs ) {
					$html = hs_manacost_remove_rocket_image_preloads( $html );
				}
				$html = preg_replace( '/<link(?=[^>]+rel=["\'](?:preconnect|dns-prefetch)["\'])(?=[^>]+href=["\'](?:https?:)?\/\/fonts\.(?:gstatic|googleapis)\.com["\'])[^>]*>\s*/i', '', $html ) ?? $html;

				$html = hs_manacost_apply_partner_wallpaper( $html );
				$head = '';

				if ( stripos( $html, 'id="hs-early-paint"' ) === false && stripos( $html, "id='hs-early-paint'" ) === false ) {
					$head .= '<style id="hs-early-paint">html{background:#010101;}body{background-color:#010101;}</style>' . "\n";
				}

				if ( $preload_home_thumbs && preg_match_all( '/<span[^>]+class=["\'][^"\']*entry-thumb[^"\']*["\'][^>]+style=["\'][^"\']*background-image:\s*url\((?:&quot;|["\']?)(https?:\/\/[^)"\']+?\.(?:jpe?g|png|webp))(?:&quot;|["\']?)\)/i', $html, $matches ) ) {
					$urls = array();
					foreach ( $matches[1] as $url ) {
						$url = html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
						if ( isset( $urls[ $url ] ) ) {
							continue;
						}
						$urls[ $url ] = true;
						break;
					}

					$i = 0;
					foreach ( array_keys( $urls ) as $url ) {
						if ( strpos( $html, 'href="' . esc_url( $url ) . '"' ) !== false || strpos( $html, "href='" . esc_url( $url ) . "'" ) !== false ) {
							$i++;
							continue;
						}
						$head .= '<link rel="preload" as="image" href="' . esc_url( $url ) . '"' . ( 0 === $i ? ' fetchpriority="high"' : '' ) . '>' . "\n";
						$i++;
					}
				}

				if ( '' === $head ) {
					return $html;
				}

				return preg_replace_callback( '/<head(?:\s[^>]*)?>/i', static fn( $head_match ) => $head_match[0] . $head, $html, 1 ) ?? $html;
			}
		);
	},
	0
);
