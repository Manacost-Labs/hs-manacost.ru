<?php
/**
 * Plugin Name: Manacost Performance Optimizer
 * Description: Front-page performance hardening for hs-manacost.ru without WP Rocket Delay JS.
 * Version: 1.0.0
 * Author: Manacost
 */

defined( 'ABSPATH' ) || exit;

final class Manacost_Performance_Optimizer {
	private const PRIMARY_HOST = 'hs-manacost.ru';
	private const MIRROR_HOST = 'hs-manacost.com';
	private const DESKTOP_LCP = 'https://hs-manacost.ru/wp-content/uploads/2026/05/budget-decks-1068x542.webp';
	private const MOBILE_LCP = 'https://hs-manacost.ru/wp-content/uploads/2026/05/budget-decks-696x353.webp';
	private const DESKTOP_SECOND = 'https://hs-manacost.ru/wp-content/uploads/2026/05/obzor-patcha-1068x542.webp';
	private const MOBILE_SECOND = 'https://hs-manacost.ru/wp-content/uploads/2026/05/obzor-patcha-696x353.webp';
	private const DESKTOP_TOP_BANNER = 'https://hs-manacost.ru/wp-content/uploads/2026/07/728x90.jpg';
	private const MOBILE_TOP_BANNER = 'https://hs-manacost.ru/wp-content/uploads/2026/07/728x90.jpg';
	private const SECOND_TOP_BANNER = 'https://hs-manacost.ru/wp-content/uploads/2026/03/728h90.png.webp';

	public static function boot(): void {
		add_action( 'template_redirect', [ __CLASS__, 'start' ], -200 );
	}

	public static function start(): void {
		if ( ! self::should_optimize() ) {
			return;
		}

		ob_start( [ __CLASS__, 'optimize_html' ] );
	}

	public static function optimize_html( string $html ): string {
		if ( stripos( $html, '<html' ) === false || stripos( $html, '</head>' ) === false ) {
			return $html;
		}

		if ( self::feature_enabled( 'MANACOST_MOBILE_LITE_ENABLED', true ) ) {
			$html = self::add_first_view_assets( $html );
			$html = self::normalize_banner_rotator( $html );

			if ( self::is_mobile_request() ) {
				$html = self::fix_mobile_banner_rotator_css( $html );
				$html = self::replace_top_card_backgrounds( $html );
				$html = str_replace( self::DESKTOP_TOP_BANNER, self::MOBILE_TOP_BANNER, $html );
			}
		}

		if ( self::feature_enabled( 'MANACOST_FONT_TRIM_ENABLED', true ) ) {
			$html = self::trim_front_page_fonts( $html );
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

	private static function normalize_banner_rotator( string $html ): string {
		if ( strpos( $html, 'class="banner-rotator manacost-banner-rotator"' ) !== false || strpos( $html, 'banner-rotator' ) === false ) {
			return $html;
		}

		$first_banner = self::is_mobile_request() ? self::MOBILE_TOP_BANNER : self::DESKTOP_TOP_BANNER;
		$replacement  = '<div class="banner-rotator manacost-banner-rotator">'
			. '<a class="manacost-banner-slide manacost-banner-slide-1" href="https://plrk.co/p/dr_hsmanacostru1806" rel="noopener" target="_blank">'
			. '<img src="' . esc_url( $first_banner ) . '" alt="" width="729" height="90" fetchpriority="high" decoding="async">'
			. '</a>'
			. '<a class="manacost-banner-slide manacost-banner-slide-2" href="https://sirus.cc/hsmanacost" rel="noopener" target="_blank">'
			. '<img src="' . esc_url( self::SECOND_TOP_BANNER ) . '" alt="" width="728" height="90" decoding="async">'
			. '</a>'
			. '</div>';

		return preg_replace(
			'#<div class="banner-rotator">\s*<a\b[\s\S]*?</a>\s*<a\b[\s\S]*?</a>\s*</div>#',
			$replacement,
			$html,
			1
		) ?: $html;
	}

	private static function fix_mobile_banner_rotator_css( string $html ): string {
		$replacement = '@media screen and (max-width: 768px) {' . "\n"
			. '.banner-rotator { min-height: 50px; height: 50px; width: calc(100vw - 40px); max-width: 768px; min-width: 0; left: 50%; transform: translateX(-50%); overflow: hidden; background: #002844 url("' . self::MOBILE_TOP_BANNER . '") center/contain no-repeat; }' . "\n"
			. '.banner-rotator a { position: absolute; top: 0; left: 0; height: 100%; min-height: 0; }' . "\n"
			. '.banner-rotator a:nth-child(1) { animation: manacostBannerFirst 10s infinite !important; animation-delay: 0s !important; }' . "\n"
			. '.banner-rotator a:nth-child(2) { display: block !important; animation: manacostBannerSecond 10s infinite !important; animation-delay: 0s !important; }' . "\n"
			. '.banner-rotator img { width: 100%; height: 100%; max-height: 50px; object-fit: contain; }' . "\n"
			. '}';

		return preg_replace(
			'#@media\s+screen\s+and\s+\(max-width:\s*768px\)\s*\{\s*\.banner-rotator\s*\{.*?\.banner-rotator\s+img\s*\{.*?\}\s*\}#s',
			$replacement,
			$html,
			1
		) ?: $html;
	}

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
		if ( ! in_array( strtolower( $host ), [ self::PRIMARY_HOST, self::MIRROR_HOST ], true ) ) {
			return false;
		}

		$path = (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH );

		return $path === '/' || is_front_page() || is_home();
	}

	private static function feature_enabled( string $constant, bool $default ): bool {
		if ( ! defined( $constant ) ) {
			return $default;
		}

		$value = constant( $constant );

		if ( is_bool( $value ) ) {
			return $value;
		}

		return ! in_array( strtolower( trim( (string) $value ) ), [ '0', 'false', 'off', 'no' ], true );
	}

	private static function is_mobile_request(): bool {
		if ( function_exists( 'wp_is_mobile' ) && wp_is_mobile() ) {
			return true;
		}

		$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

		return (bool) preg_match( '/Mobile|Android|iPhone|iPod|Opera Mini|IEMobile/i', $user_agent );
	}

	private static function add_first_view_assets( string $html ): string {
		$desktop_preload = '<link rel="preload" as="image" href="' . self::DESKTOP_LCP . '" fetchpriority="high">';
		$responsive_preload =
			'<link rel="preload" as="image" href="' . self::MOBILE_LCP . '" media="(max-width: 767px)" fetchpriority="high">' . "\n" .
			'<link rel="preload" as="image" href="' . self::DESKTOP_LCP . '" media="(min-width: 768px)" fetchpriority="high">' . "\n" .
			'<link rel="preload" as="image" href="' . self::MOBILE_TOP_BANNER . '" media="(max-width: 767px)">';

		if ( strpos( $html, $desktop_preload ) !== false ) {
			$html = str_replace( $desktop_preload, $responsive_preload, $html );
		} elseif ( strpos( $html, self::MOBILE_LCP ) === false ) {
			$html = self::inject_into_head( $html, $responsive_preload . "\n" );
		}

		if ( strpos( $html, 'id="manacost-mobile-lite-critical"' ) !== false ) {
			return $html;
		}

		$css = '<style id="manacost-mobile-lite-critical">'
			. '.banner-rotator a:nth-child(1){animation:manacostBannerFirst 10s infinite!important;animation-delay:0s!important;}'
			. '.banner-rotator a:nth-child(2){display:block!important;animation:manacostBannerSecond 10s infinite!important;animation-delay:0s!important;}'
			. '.manacost-banner-rotator{position:relative!important;width:100%;max-width:729px;height:90px!important;min-height:90px!important;overflow:hidden;margin:0 auto;background:#002844;}'
			. '.manacost-banner-rotator .manacost-banner-slide{position:absolute!important;inset:0;width:100%;height:100%!important;display:block!important;opacity:0;z-index:0;transition:none!important;}'
			. '.manacost-banner-rotator img{display:block!important;width:100%!important;height:100%!important;max-height:none!important;object-fit:contain;}'
			. '@keyframes manacostBannerFirst{0%,48%{opacity:1;z-index:1;}52%,98%{opacity:0;z-index:0;}100%{opacity:1;z-index:1;}}'
			. '@keyframes manacostBannerSecond{0%,48%{opacity:0;z-index:0;}52%,98%{opacity:1;z-index:1;}100%{opacity:0;z-index:0;}}'
			. '@media(max-width:767px){'
			. 'html,body{background:#010101;}'
			. '.td-header-wrap,.td-mobile-header-wrap,.td-header-menu-wrap-full{background:#002844;}'
			. '.manacost-banner-rotator{height:50px!important;min-height:50px!important;width:calc(100vw - 40px)!important;max-width:768px!important;min-width:0;left:50%;transform:translateX(-50%);}'
			. '.td-a-rec img{max-width:100%;height:auto;}'
			. 'a[href*="budzhetnye-kolody-hearthstone-kataklizm"] .entry-thumb.td-thumb-css{background-image:url("' . self::MOBILE_LCP . '")!important;}'
			. 'a[href*="obzor-patcha-35-4-2"] .entry-thumb.td-thumb-css{background-image:url("' . self::MOBILE_SECOND . '")!important;}'
			. '.manacost-lcp-thumb{background-image:none!important;overflow:hidden;}'
			. '.manacost-lcp-picture,.manacost-lcp-picture img{display:block;width:100%;height:100%;}'
			. '.manacost-lcp-picture img{object-fit:cover;}'
			. '}</style>' . "\n";

		return self::inject_into_head( $html, $css );
	}

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

	private static function replace_top_card_background(
		string $html,
		string $slug,
		string $desktop_url,
		string $mobile_url,
		string $alt,
		bool $high_priority
	): string {
		$pattern = '#(<a\b(?=[^>]*href=["\'][^"\']*' . preg_quote( $slug, '#' ) . '[^"\']*["\'])(?=[^>]*\bclass=["\'][^"\']*\btd-image-wrap\b)[^>]*>)(<span\b(?=[^>]*\bentry-thumb\b)(?=[^>]*\btd-thumb-css\b)[^>]*>\s*</span>)(</a>)#i';

		return preg_replace_callback(
			$pattern,
			static function ( array $matches ) use ( $desktop_url, $mobile_url, $alt, $high_priority ): string {
				if ( strpos( $matches[2], 'manacost-lcp-thumb' ) !== false ) {
					return $matches[0];
				}

				$thumb = '<span class="entry-thumb td-thumb-css manacost-lcp-thumb">'
					. '<picture class="manacost-lcp-picture">'
					. '<source media="(max-width: 767px)" srcset="' . esc_url( $mobile_url ) . '">'
					. '<img src="' . esc_url( $desktop_url ) . '" alt="' . esc_attr( $alt ) . '" width="1068" height="542" decoding="async" loading="eager"' . ( $high_priority ? ' fetchpriority="high"' : '' ) . '>'
					. '</picture>'
					. '</span>';

				return $matches[1] . $thumb . $matches[3];
			},
			$html,
			1
		) ?: $html;
	}

	private static function trim_front_page_fonts( string $html ): string {
		$html = preg_replace( '#<link\b(?=[^>]*\bdata-wpr-hosted-gf-parameters\b)[^>]*>\s*#i', '', $html ) ?: $html;
		$html = preg_replace( '#<noscript\b(?=[^>]*\bdata-wpr-hosted-gf-parameters\b)[\s\S]*?</noscript>\s*#i', '', $html ) ?: $html;
		$html = preg_replace( '#<link\b(?=[^>]*href=["\']https://fonts\.googleapis\.com/[^"\']*["\'])[^>]*>\s*#i', '', $html ) ?: $html;
		$html = preg_replace( '#<link\b(?=[^>]*rel=["\'](?:preconnect|dns-prefetch)["\'])(?=[^>]*href=["\'](?:https?:)?//fonts\.(?:googleapis|gstatic)\.com["\'])[^>]*>\s*#i', '', $html ) ?: $html;

		if ( strpos( $html, 'id="manacost-font-trim"' ) !== false ) {
			return $html;
		}

		$css = '<style id="manacost-font-trim">body,button,input,select,textarea,.entry-title,.td-module-title,.td_block_wrap,.td-header-wrap{font-family:Arial,"Helvetica Neue",Helvetica,sans-serif!important;}</style>' . "\n";

		return self::inject_into_head( $html, $css );
	}

	private static function async_mobile_noncritical_css( string $html ): string {
		$ids = [
			'hs-smart-tooltip-css',
			'td-plugin-multi-purpose-css',
			'td-standard-pack-framework-front-style-css',
		];

		foreach ( $ids as $id ) {
			$html = preg_replace_callback(
				'#<link\b(?=[^>]*\bid=["\']' . preg_quote( $id, '#' ) . '["\'])(?![^>]*\bdata-manacost-mobile-async-css\b)[^>]*>#i',
				static function ( array $matches ): string {
					$tag = $matches[0];
					$tag = preg_replace( '/\smedia=(["\']).*?\1/i', ' media="print"', $tag, 1, $count ) ?: $tag;

					if ( $count === 0 ) {
						$tag = preg_replace( '/\s*\/?>$/', ' media="print"$0', $tag, 1 ) ?: $tag;
					}

					if ( stripos( $tag, 'onload=' ) === false ) {
						$tag = preg_replace( '/\s*\/?>$/', ' onload="this.media=\'all\'"$0', $tag, 1 ) ?: $tag;
					}

					if ( stripos( $tag, 'data-manacost-mobile-async-css=' ) === false ) {
						$tag = preg_replace( '/\s*\/?>$/', ' data-manacost-mobile-async-css="1"$0', $tag, 1 ) ?: $tag;
					}

					return $tag;
				},
				$html
			) ?: $html;
		}

		return $html;
	}

	private static function remove_third_party_hints( string $html ): string {
		$domains = '(?:pagead2\.googlesyndication\.com|fundingchoicesmessages\.google\.com|www\.googletagmanager\.com|www\.google-analytics\.com|mc\.yandex\.ru|counter\.yadro\.ru)';

		return preg_replace(
			'#<link\b(?=[^>]*rel=["\'](?:preconnect|dns-prefetch)["\'])(?=[^>]*href=["\'](?:https?:)?//' . $domains . '[^"\']*["\'])[^>]*>\s*#i',
			'',
			$html
		) ?: $html;
	}

	private static function defer_third_party_scripts( string $html ): string {
		if ( strpos( $html, 'data-manacost-delayed-src' ) === false ) {
			$html = preg_replace_callback(
				'#<script\b(?=[^>]*\bsrc=["\']([^"\']+)["\'])[^>]*>\s*</script>#is',
				[ __CLASS__, 'delay_external_script_tag' ],
				$html
			) ?: $html;
		}

		if ( strpos( $html, 'id="manacost-third-party-gate"' ) !== false ) {
			return $html;
		}

		$loader = <<<'HTML'
<script id="manacost-third-party-gate">!function(){var d=document,w=window,done=!1;function run(){if(done)return;done=!0;w.setTimeout(function(){d.querySelectorAll("script[data-manacost-delayed-src]").forEach(function(n){var s=d.createElement("script");s.src=n.getAttribute("data-manacost-delayed-src");s.async=!0;["id","crossorigin","referrerpolicy"].forEach(function(a){var v=n.getAttribute("data-"+a);v&&s.setAttribute(a,v)});d.body.appendChild(s)})},1200)}["pointerdown","keydown","touchstart","scroll"].forEach(function(e){w.addEventListener(e,run,{once:!0,passive:!0})});w.addEventListener("load",function(){w.setTimeout(run,12000)},{once:!0})}();</script>
HTML;

		return str_replace( '</body>', $loader . "\n</body>", $html );
	}

	public static function delay_external_script_tag( array $matches ): string {
		$tag = $matches[0];
		$src = html_entity_decode( $matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		if ( ! preg_match( '#(?:pagead2\.googlesyndication\.com|fundingchoicesmessages\.google\.com|googletagmanager\.com|google-analytics\.com|mc\.yandex\.ru|/wp-content/plugins/ad-inserter/js/ai-functions\.min\.js)#i', $src ) ) {
			return $tag;
		}

		$attrs = [
			'id'             => self::extract_attr( $tag, 'id' ),
			'crossorigin'    => self::extract_attr( $tag, 'crossorigin' ),
			'referrerpolicy' => self::extract_attr( $tag, 'referrerpolicy' ),
		];

		$out = '<script type="text/plain" data-manacost-delayed-src="' . esc_url( $src ) . '"';

		foreach ( $attrs as $name => $value ) {
			if ( $value !== '' ) {
				$out .= ' data-' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
			}
		}

		return $out . '></script>';
	}

	private static function delay_liveinternet_counter( string $html ): string {
		if ( strpos( $html, 'id="manacost-liveinternet-delay"' ) !== false ) {
			return $html;
		}

		return preg_replace(
			'#<!--LiveInternet counter--><script\b[\s\S]*?</script><!--/LiveInternet-->#i',
			'<script id="manacost-liveinternet-delay">window.addEventListener("load",function(){setTimeout(function(){var i=new Image;i.width=1;i.height=1;i.alt="";i.src="//counter.yadro.ru/hit?t50.6;r"+encodeURIComponent(document.referrer)+";u"+encodeURIComponent(location.href)+";"+Math.random()},4000)},{once:true});</script>',
			$html,
			1
		) ?: $html;
	}

	private static function extract_attr( string $tag, string $name ): string {
		if ( preg_match( '/\s' . preg_quote( $name, '/' ) . '=(["\'])(.*?)\1/i', $tag, $match ) ) {
			return html_entity_decode( $match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}

		return '';
	}

	private static function inject_into_head( string $html, string $content ): string {
		return preg_replace( '/<head([^>]*)>/i', '<head$1>' . "\n" . $content, $html, 1 ) ?: $html;
	}
}

Manacost_Performance_Optimizer::boot();
