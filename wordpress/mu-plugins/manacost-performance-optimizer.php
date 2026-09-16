<?php
/**
 * Plugin Name: Manacost Performance Optimizer
 * Description: Front-page performance hardening for hs-manacost.ru without WP Rocket Delay JS.
 * Version: 1.0.0
 * Author: Manacost
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Applies bounded performance changes to the anonymous home page. */
final class Manacost_Performance_Optimizer {
	private const PRIMARY_HOST       = 'hs-manacost.ru';
	private const MIRROR_HOST        = 'hs-manacost.com';
	private const DESKTOP_LCP        = 'https://hs-manacost.ru/wp-content/uploads/2026/05/budget-decks-1068x542.webp';
	private const MOBILE_LCP         = 'https://hs-manacost.ru/wp-content/uploads/2026/05/budget-decks-696x353.webp';
	private const DESKTOP_SECOND     = 'https://hs-manacost.ru/wp-content/uploads/2026/05/obzor-patcha-1068x542.webp';
	private const MOBILE_SECOND      = 'https://hs-manacost.ru/wp-content/uploads/2026/05/obzor-patcha-696x353.webp';

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
			$html = self::add_first_view_assets( $html );

			if ( self::is_mobile_request() ) {
				$html = self::replace_top_card_backgrounds( $html );
			}
		}

		if ( self::feature_enabled( 'MANACOST_DEFER_THIRD_PARTY_ENABLED', true ) ) {
			$html = self::remove_third_party_hints( $html );
			$html = self::defer_third_party_scripts( $html );
			$html = self::delay_liveinternet_counter( $html );
		}

		if ( strpos( $html, 'manacost-perf-active' ) === false ) {
			$html = self::inject_into_head( $html, '<meta name="manacost-perf-active" content="1">' . "\n" );
		}

		return $html;
	}

	/**
	 * Checks the host, public request context, and home-page route.
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

		return '/' === $path || is_front_page() || is_home();
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
	 * Adds responsive image preloads and the existing first-view styles once.
	 *
	 * @param string $html Rendered page HTML.
	 * @return string
	 */
	private static function add_first_view_assets( string $html ): string {
		$desktop_preload    = '<link rel="preload" as="image" href="' . self::DESKTOP_LCP . '" fetchpriority="high">';
		$responsive_preload =
			'<link rel="preload" as="image" href="' . self::MOBILE_LCP . '" media="(max-width: 767px)" fetchpriority="high">' . "\n" .
			'<link rel="preload" as="image" href="' . self::DESKTOP_LCP . '" media="(min-width: 768px)" fetchpriority="high">';

		if ( strpos( $html, $desktop_preload ) !== false ) {
			$html = str_replace( $desktop_preload, $responsive_preload, $html );
		} elseif ( strpos( $html, self::MOBILE_LCP ) === false ) {
			$html = self::inject_into_head( $html, $responsive_preload . "\n" );
		}

		if ( strpos( $html, 'id="manacost-mobile-lite-critical"' ) !== false ) {
			return $html;
		}

		$css = '<style id="manacost-mobile-lite-critical">'
			. '@media(max-width:767px){'
			. 'html,body{background:#010101;}'
			. '.td-header-wrap,.td-mobile-header-wrap,.td-header-menu-wrap-full{background:#002844;}'
			. '.td-a-rec img{max-width:100%;height:auto;}'
			. 'a[href*="budzhetnye-kolody-hearthstone-kataklizm"] .entry-thumb.td-thumb-css{background-image:url("' . self::MOBILE_LCP . '")!important;}'
			. 'a[href*="obzor-patcha-35-4-2"] .entry-thumb.td-thumb-css{background-image:url("' . self::MOBILE_SECOND . '")!important;}'
			. '.manacost-lcp-thumb{background-image:none!important;overflow:hidden;}'
			. '.manacost-lcp-picture,.manacost-lcp-picture img{display:block;width:100%;height:100%;}'
			. '.manacost-lcp-picture img{object-fit:cover;}'
			. '}</style>' . "\n";

		return self::inject_into_head( $html, $css );
	}

	/**
	 * Converts the two configured top-card backgrounds to responsive images.
	 *
	 * @param string $html Rendered page HTML.
	 * @return string
	 */
	private static function replace_top_card_backgrounds( string $html ): string {
		$html = self::replace_top_card_background(
			$html,
			'budzhetnye-kolody-hearthstone-kataklizm',
			self::DESKTOP_LCP,
			self::MOBILE_LCP,
			'11 лучших бюджетных колод КАТАКЛИЗМА до 3000 пыли',
			true
		);

		return self::replace_top_card_background(
			$html,
			'obzor-patcha-35-4-2',
			self::DESKTOP_SECOND,
			self::MOBILE_SECOND,
			'Обзор патча 35.4.2',
			false
		);
	}

	/**
	 * Converts one matching card while preserving its existing link.
	 *
	 * @param string $html          Rendered page HTML.
	 * @param string $slug          Target article slug.
	 * @param string $desktop_url   Desktop image URL.
	 * @param string $mobile_url    Mobile image URL.
	 * @param string $alt           Existing article title.
	 * @param bool   $high_priority Whether to prioritize the image request.
	 * @return string
	 */
	private static function replace_top_card_background(
		string $html,
		string $slug,
		string $desktop_url,
		string $mobile_url,
		string $alt,
		bool $high_priority
	): string {
		$pattern = '#(<a\b(?=[^>]*href=["\'][^"\']*' . preg_quote( $slug, '#' ) . '[^"\']*["\'])(?=[^>]*\bclass=["\'][^"\']*\btd-image-wrap\b)[^>]*>)(<span\b(?=[^>]*\bentry-thumb\b)(?=[^>]*\btd-thumb-css\b)[^>]*>\s*</span>)(</a>)#i';

		$result = preg_replace_callback(
			$pattern,
			static function ( array $matches ) use ( $desktop_url, $mobile_url, $alt, $high_priority ): string {
				if ( strpos( $matches[2], 'manacost-lcp-thumb' ) !== false ) {
					return $matches[0];
				}

				$priority_attr = $high_priority ? ' fetchpriority="high"' : '';
				$thumb         = '<span class="entry-thumb td-thumb-css manacost-lcp-thumb">'
					. '<picture class="manacost-lcp-picture">'
					. '<source media="(max-width: 767px)" srcset="' . esc_url( $mobile_url ) . '">'
					. '<img src="' . esc_url( $desktop_url ) . '" alt="' . esc_attr( $alt ) . '" width="1068" height="542" decoding="async" loading="eager"' . $priority_attr . '>'
					. '</picture>'
					. '</span>';

				return $matches[1] . $thumb . $matches[3];
			},
			$html,
			1
		);
		return $result ? $result : $html;
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
		$domains = '(?:pagead2\.googlesyndication\.com|fundingchoicesmessages\.google\.com|www\.googletagmanager\.com|www\.google-analytics\.com|mc\.yandex\.ru|counter\.yadro\.ru)';

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
	 * Rewrites one existing script as inert markup for the delay gate.
	 *
	 * @param array<int, string> $matches Full regex match and captured script URL.
	 * @return string
	 */
	public static function delay_external_script_tag( array $matches ): string {
		$tag = $matches[0];
		$src = html_entity_decode( $matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		if ( ! preg_match( '#(?:pagead2\.googlesyndication\.com|fundingchoicesmessages\.google\.com|googletagmanager\.com|google-analytics\.com|mc\.yandex\.ru|/wp-content/plugins/ad-inserter/js/ai-functions\.min\.js)#i', $src ) ) {
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
