<?php
/**
 * Plugin Name: Manacost Performance Optimizer
 * Description: Public-page performance hardening for hs-manacost.ru without delaying partner advertising.
 * Version: 1.1.0
 * Author: Manacost
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Applies bounded performance changes to anonymous public pages. */
final class Manacost_Performance_Optimizer {
	private const PRIMARY_HOST = 'hs-manacost.ru';
	private const MIRROR_HOST  = 'hs-manacost.com';

	/** Registers the frontend output-buffer hook. */
	public static function boot(): void {
		add_action( 'template_redirect', array( __CLASS__, 'start' ), -200 );
	}

	/** Starts buffering only eligible public home-page requests. */
	public static function start(): void {
		if ( ! self::should_optimize() ) {
			return;
		}

		ob_start( array( __CLASS__, 'optimize_html' ) );
	}

	/**
	 * Optimizes the rendered page while retaining theme-owned font assets.
	 *
	 * @param string $html Rendered page HTML.
	 * @return string
	 */
	public static function optimize_html( string $html ): string {
		if ( stripos( $html, '<html' ) === false || stripos( $html, '</head>' ) === false ) {
			return $html;
		}

		if ( self::feature_enabled( 'MANACOST_MOBILE_LITE_ENABLED', true ) ) {
			$html = self::remove_legacy_first_view_assets( $html );
			$html = self::optimize_first_view( $html );
			$html = self::add_mobile_critical_assets( $html );

			if ( self::is_mobile_request() ) {
				$html = self::remove_mobile_webfonts( $html );
			}
		}

		if ( self::feature_enabled( 'MANACOST_DEFER_THIRD_PARTY_ENABLED', true ) ) {
			$html = self::remove_third_party_hints( $html );
			$html = self::defer_third_party_scripts( $html );
			$html = self::delay_yandex_metrica_inline( $html );
			$html = self::delay_liveinternet_counter( $html );
		}

		if ( strpos( $html, 'manacost-perf-active' ) === false ) {
			$html = self::inject_into_head( $html, '<meta name="manacost-perf-active" content="1">' . "\n" );
		}

		return $html;
	}

	/**
	 * Checks the host and public request context.
	 *
	 * @return bool
	 */
	private static function should_optimize(): bool {
		if ( ! self::feature_enabled( 'MANACOST_PERF_ENABLED', true ) ) {
			return false;
		}

		if (
			is_admin()
			|| is_user_logged_in()
			|| wp_doing_ajax()
			|| is_feed()
			|| is_preview()
			|| is_robots()
			|| is_trackback()
			|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		) {
			return false;
		}

		$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ! in_array( strtolower( $host ), array( self::PRIMARY_HOST, self::MIRROR_HOST ), true ) ) {
			return false;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '/';
		$path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );

		foreach ( array( '/wp-admin', '/wp-login.php', '/wp-json', '/reader-api', '/account' ) as $excluded_path ) {
			if ( str_starts_with( $path, $excluded_path ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Reads an optional boolean feature constant.
	 *
	 * @param string $constant     Constant name.
	 * @param bool   $default_on   Fallback when the constant is undefined.
	 * @return bool
	 */
	private static function feature_enabled( string $constant, bool $default_on ): bool {
		if ( ! defined( $constant ) ) {
			return $default_on;
		}

		$value = constant( $constant );

		if ( is_bool( $value ) ) {
			return $value;
		}

		return ! in_array( strtolower( trim( (string) $value ) ), array( '0', 'false', 'off', 'no' ), true );
	}

	/**
	 * Detects mobile requests using WordPress and the legacy UA fallback.
	 *
	 * @return bool
	 */
	private static function is_mobile_request(): bool {
		if ( function_exists( 'wp_is_mobile' ) && wp_is_mobile() ) {
			return true;
		}

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) && is_string( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';

		return (bool) preg_match( '/Mobile|Android|iPhone|iPod|Opera Mini|IEMobile/i', $user_agent );
	}

	/**
	 * Optimizes the first meaningful image on public listing pages.
	 *
	 * @param string $html Rendered page HTML.
	 * @return string
	 */
	private static function optimize_first_view( string $html ): string {
		if ( ! self::is_listing_request() ) {
			return $html;
		}

		$lcp_url = '';
		$html    = self::promote_home_grid_cards( $html, $lcp_url );

		if ( '' === $lcp_url ) {
			$html = self::promote_first_listing_thumbnail( $html, $lcp_url );
		}

		if ( '' === $lcp_url ) {
			return $html;
		}

		$html = preg_replace(
			'#<link\b(?=[^>]*\brel=["\']preload["\'])(?=[^>]*\bas=["\']image["\'])[^>]*>\s*#i',
			'',
			$html
		) ?? $html;

		$preload = '<link id="manacost-lcp-preload" rel="preload" as="image" href="' . esc_url( $lcp_url ) . '" fetchpriority="high">' . "\n";

		return self::inject_into_head( $html, $preload );
	}

	/**
	 * Converts the first two homepage grid backgrounds into discoverable images.
	 *
	 * @param string $html    Rendered page HTML.
	 * @param string $lcp_url Selected LCP URL, populated by reference.
	 * @return string
	 */
	private static function promote_home_grid_cards( string $html, string &$lcp_url ): string {
		$grid_offset = stripos( $html, 'td-big-grid-flex' );
		if ( false === $grid_offset ) {
			return $html;
		}

		$before = substr( $html, 0, $grid_offset );
		$grid   = substr( $html, $grid_offset );
		$seen   = 0;

		$grid = preg_replace_callback(
			'#(<a\b(?=[^>]*\bclass=["\'][^"\']*\btd-image-wrap\b)[^>]*>)(\s*)(<span\b(?=[^>]*\bclass=["\'][^"\']*\bentry-thumb\b)[^>]*>)(\s*</span>)(\s*</a>)#i',
			static function ( array $matches ) use ( &$seen, &$lcp_url ): string {
				$desktop_url = self::image_url_from_tag( $matches[3] );
				if ( '' === $desktop_url ) {
					return $matches[0];
				}

				$mobile_url = self::mobile_size_url( $desktop_url );
				$alt        = self::extract_attr( $matches[1], 'title' );
				$priority   = 0 === $seen;
				$seen++;

				if ( $priority ) {
					$lcp_url = self::is_mobile_request() ? $mobile_url : $desktop_url;
				}

				$thumb = '<span class="entry-thumb td-thumb-css manacost-lcp-thumb">'
					. '<picture class="manacost-lcp-picture">'
					. '<source media="(max-width: 767px)" srcset="' . esc_url( $mobile_url ) . '">'
					. '<img src="' . esc_url( $desktop_url ) . '" alt="' . esc_attr( $alt ) . '" width="1068" height="542" decoding="async" loading="eager"'
					. ( $priority ? ' fetchpriority="high"' : '' )
					. ' sizes="(max-width: 767px) 100vw, 50vw">'
					. '</picture></span>';

				return $matches[1] . $matches[2] . $thumb . $matches[5];
			},
			$grid,
			2
		) ?? $grid;

		return $before . $grid;
	}

	/**
	 * Promotes the first real Newspaper listing thumbnail out of Rocket lazyload.
	 *
	 * @param string $html    Rendered page HTML.
	 * @param string $lcp_url Selected LCP URL, populated by reference.
	 * @return string
	 */
	private static function promote_first_listing_thumbnail( string $html, string &$lcp_url ): string {
		$result = preg_replace_callback(
			'#(<div\b(?=[^>]*\bclass=["\'][^"\']*\btd-module-thumb\b)[^>]*>[\s\S]*?<img\b(?=[^>]*\bclass=["\'][^"\']*\bentry-thumb\b)(?=[^>]*\bdata-lazy-src=)[^>]*>)#i',
			static function ( array $matches ) use ( &$lcp_url ): string {
				$tag     = $matches[1];
				$img_pos = strripos( $tag, '<img' );
				if ( false === $img_pos ) {
					return $tag;
				}

				$prefix  = substr( $tag, 0, $img_pos );
				$img_tag = substr( $tag, $img_pos );
				$lcp_url = self::extract_attr( $img_tag, 'data-lazy-src' );
				if ( '' === $lcp_url ) {
					return $tag;
				}

				$srcset  = self::extract_attr( $img_tag, 'data-lazy-srcset' );
				$sizes   = self::extract_attr( $img_tag, 'data-lazy-sizes' );
				$img_tag = preg_replace( '/\s(?:src|data-lazy-src|data-lazy-srcset|data-lazy-sizes|loading|fetchpriority)=(?:["\']).*?["\']/i', '', $img_tag ) ?? $img_tag;
				$attrs   = ' src="' . esc_url( $lcp_url ) . '" loading="eager" fetchpriority="high" decoding="async"';
				$attrs  .= '' !== $srcset ? ' srcset="' . esc_attr( $srcset ) . '"' : '';
				$attrs  .= '' !== $sizes ? ' sizes="' . esc_attr( $sizes ) . '"' : '';
				$img_tag = preg_replace( '/\s*\/?\>$/', $attrs . '>', $img_tag, 1 ) ?? $img_tag;

				return $prefix . $img_tag;
			},
			$html,
			1
		);

		return $result ?? $html;
	}

	/**
	 * Adds mobile background and typography budgets without affecting icon fonts.
	 *
	 * @param string $html Rendered page HTML.
	 * @return string
	 */
	private static function add_mobile_critical_assets( string $html ): string {
		$critical = '<style id="manacost-mobile-lite-critical">@media(max-width:767px){'
			. 'html,body{background:#010101!important;background-image:none!important;}'
			. '.td-header-wrap,.td-mobile-header-wrap,.td-header-menu-wrap-full{background:#002844;}'
			. '.td-a-rec img{max-width:100%;height:auto;}'
			. '.manacost-lcp-thumb{background-image:none!important;overflow:hidden;}'
			. '.manacost-lcp-picture,.manacost-lcp-picture img{display:block;width:100%;height:100%;}'
			. '.manacost-lcp-picture img{object-fit:cover;}'
			. '}</style>' . "\n";
		$fonts    = '<style id="manacost-mobile-font-budget">@media(max-width:1024px){'
			. 'body,body .entry-title,body .entry-title a,body .td-block-title,body .td-block-title *,body .td-post-category,body .td-pulldown-size,body .tdm-descr,body .td-author-date,body .td-editor-date,body .sf-menu>li>a,body .td-module-comments,body .td-read-more a{font-family:Arial,"Helvetica Neue",sans-serif!important;}'
			. '}</style>' . "\n";

		return self::inject_into_head( $html, $critical . $fonts );
	}

	/**
	 * Removes hosted Google Fonts only for mobile responses using the system-font budget above.
	 *
	 * @param string $html Rendered page HTML.
	 * @return string
	 */
	private static function remove_mobile_webfonts( string $html ): string {
		$patterns = array(
			'#<link\b(?=[^>]*\brel=["\'](?:preconnect|dns-prefetch)["\'])(?=[^>]*\bhref=["\'](?:https?:)?//fonts\.(?:googleapis|gstatic)\.com)[^>]*>\s*#i',
			'#<link\b(?=[^>]*(?:data-wpr-hosted-gf-parameters|href=["\'][^"\']*fonts\.googleapis\.com))[^>]*>\s*#i',
			'#<noscript\b(?=[^>]*data-wpr-hosted-gf-parameters)[^>]*>[\s\S]*?</noscript>\s*#i',
			'#<link\b(?=[^>]*\brel=["\']preload["\'])(?=[^>]*\bas=["\']font["\'])(?=[^>]*\bhref=["\'][^"\']*/google-fonts/)[^>]*>\s*#i',
		);

		return preg_replace( $patterns, '', $html ) ?? $html;
	}

	/**
	 * Removes stale first-view assets emitted by the older overlapping optimizers.
	 *
	 * @param string $html Rendered page HTML.
	 * @return string
	 */
	private static function remove_legacy_first_view_assets( string $html ): string {
		return preg_replace(
			'#<style\b[^>]*id=["\'](?:manacost-mobile-lite-critical|manacost-mobile-font-budget|hs-mobile-first-view-assets)["\'][^>]*>[\s\S]*?</style>\s*#i',
			'',
			$html
		) ?? $html;
	}

	/**
	 * Returns the first background URL stored on a Newspaper thumbnail span.
	 *
	 * @param string $tag Thumbnail tag.
	 * @return string
	 */
	private static function image_url_from_tag( string $tag ): string {
		$url = self::extract_attr( $tag, 'data-bg' );
		if ( '' !== $url ) {
			return $url;
		}

		if ( preg_match( '#background-image:\s*url\((?:&quot;|["\']?)(https?://[^)"\']+)(?:&quot;|["\']?)\)#i', $tag, $match ) ) {
			return html_entity_decode( $match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}

		return '';
	}

	/**
	 * Derives the registered Newspaper mobile crop from its large-grid crop.
	 *
	 * @param string $url Large-grid image URL.
	 * @return string
	 */
	private static function mobile_size_url( string $url ): string {
		$result = preg_replace( '/-1068x542(?=\.(?:jpe?g|png|webp)(?:[?#]|$))/i', '-696x353', $url, 1 );
		return $result && $result !== $url ? $result : $url;
	}

	/** Returns whether the current request is a public content listing. */
	private static function is_listing_request(): bool {
		if ( is_front_page() || is_home() ) {
			return true;
		}

		foreach ( array( 'is_category', 'is_tag', 'is_archive', 'is_search' ) as $conditional ) {
			if ( function_exists( $conditional ) && $conditional() ) {
				return true;
			}
		}

		$request_uri = '/';
		if ( isset( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] ) ) {
			$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}
		$path = (string) wp_parse_url( $request_uri, PHP_URL_PATH );

		return (bool) preg_match( '#^/(?:category|tag|author|search)/#', $path );
	}

	/**
	 * Retains the legacy noncritical stylesheet transformer for compatibility.
	 *
	 * @param string $html Rendered page HTML.
	 * @return string
	 */
	private static function async_mobile_noncritical_css( string $html ): string {
		$ids = array(
			'hs-smart-tooltip-css',
			'td-plugin-multi-purpose-css',
			'td-standard-pack-framework-front-style-css',
		);

		foreach ( $ids as $id ) {
			$result = preg_replace_callback(
				'#<link\b(?=[^>]*\bid=["\']' . preg_quote( $id, '#' ) . '["\'])(?![^>]*\bdata-manacost-mobile-async-css\b)[^>]*>#i',
				static function ( array $matches ): string {
					$tag         = $matches[0];
					$updated_tag = preg_replace( '/\smedia=(["\']).*?\1/i', ' media="print"', $tag, 1, $count );
					$tag         = $updated_tag ? $updated_tag : $tag;

					if ( 0 === $count ) {
						$updated_tag = preg_replace( '/\s*\/?>$/', ' media="print"$0', $tag, 1 );
						$tag         = $updated_tag ? $updated_tag : $tag;
					}

					if ( stripos( $tag, 'onload=' ) === false ) {
						$updated_tag = preg_replace( '/\s*\/?>$/', ' onload="this.media=\'all\'"$0', $tag, 1 );
						$tag         = $updated_tag ? $updated_tag : $tag;
					}

					if ( stripos( $tag, 'data-manacost-mobile-async-css=' ) === false ) {
						$updated_tag = preg_replace( '/\s*\/?>$/', ' data-manacost-mobile-async-css="1"$0', $tag, 1 );
						$tag         = $updated_tag ? $updated_tag : $tag;
					}

					return $tag;
				},
				$html
			);
			$html   = $result ? $result : $html;
		}

		return $html;
	}

	/**
	 * Removes early connection hints for deferred third-party services only.
	 *
	 * @param string $html Rendered page HTML.
	 * @return string
	 */
	private static function remove_third_party_hints( string $html ): string {
		$domains = '(?:www\.googletagmanager\.com|www\.google-analytics\.com|mc\.yandex\.ru|counter\.yadro\.ru)';

		$result = preg_replace(
			'#<link\b(?=[^>]*rel=["\'](?:preconnect|dns-prefetch)["\'])(?=[^>]*href=["\'](?:https?:)?//' . $domains . '[^"\']*["\'])[^>]*>\s*#i',
			'',
			$html
		);
		return $result ? $result : $html;
	}

	/**
	 * Defers selected rendered scripts and adds their existing interaction gate.
	 *
	 * @param string $html Rendered page HTML.
	 * @return string
	 */
	private static function defer_third_party_scripts( string $html ): string {
		if ( strpos( $html, 'data-manacost-delayed-src' ) === false ) {
			$result = preg_replace_callback(
				'#<script\b(?=[^>]*\bsrc=["\']([^"\']+)["\'])[^>]*>\s*</script>#is',
				array( __CLASS__, 'delay_external_script_tag' ),
				$html
			);
			$html   = $result ? $result : $html;
		}

		if ( strpos( $html, 'id="manacost-third-party-gate"' ) !== false ) {
			return $html;
		}

		$loader = <<<'HTML'
<script id="manacost-third-party-gate">!function(){var d=document,w=window,done=!1;function run(){if(done)return;done=!0;w.setTimeout(function(){d.querySelectorAll("script[data-manacost-delayed-src]").forEach(function(n){var s=d.createElement("script");s.src=n.getAttribute("data-manacost-delayed-src");s.async=!0;["id","crossorigin","referrerpolicy"].forEach(function(a){var v=n.getAttribute("data-"+a);v&&s.setAttribute(a,v)});d.body.appendChild(s)})},1200)}["pointerdown","keydown","touchstart","scroll"].forEach(function(e){w.addEventListener(e,run,{once:!0,passive:!0})});w.addEventListener("load",function(){w.setTimeout(run,12000)},{once:!0})}();</script>
HTML;

		return str_replace( '</body>', $loader . "\n</body>", $html );
	}

	/**
	 * Defers the legacy inline Yandex Metrica loader until well after first paint.
	 *
	 * @param string $html Rendered page HTML.
	 * @return string
	 */
	private static function delay_yandex_metrica_inline( string $html ): string {
		if ( false === strpos( $html, 'yandex-metrica-watch/watch.js' ) ) {
			return $html;
		}

		$replacement = <<<'JS'
if (w.opera == "[object Opera]") {
            d.addEventListener("DOMContentLoaded", function () { w.setTimeout(f, 15000); }, false);
        } else if (d.readyState === "complete") {
            w.setTimeout(f, 15000);
        } else {
            w.addEventListener("load", function () { w.setTimeout(f, 15000); }, false);
        }
JS;

		$result = preg_replace(
			'#if\s*\(\s*w\.opera\s*==\s*"\[object Opera\]"\s*\)\s*\{\s*d\.addEventListener\("DOMContentLoaded",\s*f,\s*false\);\s*\}\s*else\s*\{\s*f\(\);\s*\}#',
			$replacement,
			$html,
			1
		);

		return $result ?? $html;
	}

	/**
	 * Rewrites one existing script as inert markup for the delay gate.
	 *
	 * @param array<int, string> $matches Full regex match and captured script URL.
	 * @return string
	 */
	public static function delay_external_script_tag( array $matches ): string {
		$tag = $matches[0];
		$src = html_entity_decode( $matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		if ( ! preg_match( '#(?:googletagmanager\.com|google-analytics\.com|mc\.yandex\.ru)#i', $src ) ) {
			return $tag;
		}

		$attrs = array(
			'id'             => self::extract_attr( $tag, 'id' ),
			'crossorigin'    => self::extract_attr( $tag, 'crossorigin' ),
			'referrerpolicy' => self::extract_attr( $tag, 'referrerpolicy' ),
		);

		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Optimizer owns this inert rewrite of an already-rendered script; remove only when the output-buffer gate is retired.
		$out = '<script type="text/plain" data-manacost-delayed-src="' . esc_url( $src ) . '"';

		foreach ( $attrs as $name => $value ) {
			if ( '' !== $value ) {
				$out .= ' data-' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
			}
		}

		return $out . '></script>';
	}

	/**
	 * Replaces the legacy counter in place without adding a second counter.
	 *
	 * @param string $html Rendered page HTML.
	 * @return string
	 */
	private static function delay_liveinternet_counter( string $html ): string {
		if ( strpos( $html, 'id="manacost-liveinternet-delay"' ) !== false ) {
			return $html;
		}

		$result = preg_replace(
			'#<!--LiveInternet counter--><script\b[\s\S]*?</script><!--/LiveInternet-->#i',
			'<script id="manacost-liveinternet-delay">window.addEventListener("load",function(){setTimeout(function(){var i=new Image;i.width=1;i.height=1;i.alt="";i.src="//counter.yadro.ru/hit?t50.6;r"+encodeURIComponent(document.referrer)+";u"+encodeURIComponent(location.href)+";"+Math.random()},4000)},{once:true});</script>',
			$html,
			1
		);
		return $result ? $result : $html;
	}

	/**
	 * Reads and decodes one quoted attribute before context-specific escaping.
	 *
	 * @param string $tag  Existing HTML tag.
	 * @param string $name Attribute name.
	 * @return string
	 */
	private static function extract_attr( string $tag, string $name ): string {
		if ( preg_match( '/\s' . preg_quote( $name, '/' ) . '=(["\'])(.*?)\1/i', $tag, $match ) ) {
			return html_entity_decode( $match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}

		return '';
	}

	/**
	 * Inserts generated assets after the first opening head tag.
	 *
	 * @param string $html    Rendered page HTML.
	 * @param string $content Generated markup to insert.
	 * @return string
	 */
	private static function inject_into_head( string $html, string $content ): string {
		$result = preg_replace( '/<head([^>]*)>/i', '<head$1>' . "\n" . $content, $html, 1 );
		return $result ? $result : $html;
	}
}

Manacost_Performance_Optimizer::boot();
