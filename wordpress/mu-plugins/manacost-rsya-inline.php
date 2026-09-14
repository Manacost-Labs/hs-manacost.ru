<?php
/**
 * Plugin Name: Manacost RСЯ Inline Banner
 * Description: Renders privacy-gated Yandex RTB placements.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the Yandex RTB loader and inline article unit.
 */
final class Manacost_Rsya_Inline_Banner {
	/**
	 * Publication time of the tenth latest post when the placements were enabled.
	 *
	 * The inclusive cutoff keeps those ten articles covered and automatically
	 * includes every normally published article that followed them.
	 */
	private const ENABLED_FROM_GMT           = '2026-08-31 09:00:39';
	private const LEGACY_AUTOMATIC_UNTIL_GMT = '2026-09-14 20:00:00';
	private const INTRO_BLOCK_ID             = 'R-A-16113237-6';
	private const FOOTER_BLOCK_ID            = 'R-A-16113237-5';
	private const FLOOR_BLOCK_ID             = 'R-A-16113237-7';
	private const EDITOR_BANNER_BLOCK_ID     = 'R-A-16113237-12';
	private const EDITOR_FEED_BLOCK_ID       = 'R-A-16113237-10';
	private const PUBLIC_PROFILE_BLOCK_ID    = 'R-A-16113237-13';
	private const GATE_HANDLE                = 'manacost-rsya-gate';
	private const TEXT_PARAGRAPH_POSITION    = 3;
	private const SHORTCODE                  = 'manacost_rsya';

	/**
	 * Tracks manually placed units on the current document.
	 *
	 * @var int
	 */
	private static int $manual_unit_index = 0;

	/**
	 * Registers the page hooks.
	 *
	 * @return void
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_gate' ) );
		add_action( 'wp_head', array( __CLASS__, 'render_styles' ), 39 );
		add_action( 'wp_footer', array( __CLASS__, 'render_floor_ad' ), 90 );
		add_filter( 'the_content', array( __CLASS__, 'insert_banner' ), 30 );
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'render_shortcode' ) );
		add_filter( 'mce_external_plugins', array( __CLASS__, 'register_editor_plugin' ) );
		add_filter( 'mce_buttons', array( __CLASS__, 'register_editor_button' ) );
	}

	/**
	 * Renders the desktop-only fixed Floor Ad after article content.
	 *
	 * @return void
	 */
	public static function render_floor_ad(): void {
		if ( ! self::should_render_legacy_article() ) {
			return;
		}
		?>
		<script id="manacost-rsya-floor-ad" data-manacost-rsya-state="queued">
			if (!window.manacostRsyaFloorQueued && window.manacostRsyaReady) {
			window.manacostRsyaFloorQueued = true;
			window.manacostRsyaReady.then((allowed) => {
				if (!allowed) { return; }
				window.yaContextCb = window.yaContextCb || [];
				window.yaContextCb.push(() => {
				const unit = document.getElementById('manacost-rsya-floor-ad');
				const state = value => unit.setAttribute('data-manacost-rsya-state', value);
				if (window.manacostRsyaLoaderFailed) { state('loader-error'); return; }
				state('requested');
				Ya.Context.AdvManager.render({
					"blockId": "<?php echo esc_js( self::FLOOR_BLOCK_ID ); ?>",
					"type": "floorAd",
					"platform": "desktop",
					"onError": data => {
						unit.setAttribute('data-manacost-rsya-code', String(data.code || '').slice(0, 80));
						if (data.type === 'error') { state('error'); }
					},
					"onClose": () => state('closed'),
					"onRender": () => state('rendered')
				}, () => state('no-fill'));
			});
			});
			}
		</script>
		<?php
	}

	/**
	 * Loads the Yandex RTB loader only after the private Reader ad gate.
	 *
	 * @return void
	 */
	public static function enqueue_gate(): void {
		if ( ! self::should_render_gate() ) {
			return;
		}
		wp_register_script(
			self::GATE_HANDLE,
			'',
			array(),
			self::INTRO_BLOCK_ID,
			array(
				'strategy' => 'async',
			)
		);
		wp_enqueue_script( self::GATE_HANDLE );
		wp_add_inline_script(
			self::GATE_HANDLE,
			'(function () { var hide = function () { document.querySelectorAll("[data-manacost-rsya-unit]").forEach(function (unit) { unit.hidden = true; }); }; var host = window.location ? window.location.hostname : ""; window.yaContextCb = window.yaContextCb || []; window.manacostRsyaLoaderFailed = false; if ("hs-manacost.com" === host || "www.hs-manacost.com" === host) { hide(); window.manacostRsyaReady = Promise.resolve(false); return; } window.addEventListener("error", function (event) { var target = event.target; if (target && "manacost-rsya-loader-js" === target.id) { window.manacostRsyaLoaderFailed = true; hide(); } }, true); window.manacostRsyaReady = (async function () { try { var response = await fetch("/reader-api/v1/ad-status", { credentials: "same-origin", cache: "no-store", headers: { "accept": "application/json" } }); var status = await response.json(); if (!response.ok || !status || status.adFree !== false) { hide(); return false; } } catch (error) { hide(); return false; } return await new Promise(function (resolve) { var loader = document.createElement("script"); loader.id = "manacost-rsya-loader-js"; loader.async = true; loader.src = "https://yandex.ru/ads/system/context.js"; loader.onload = function () { resolve(true); }; loader.onerror = function () { window.manacostRsyaLoaderFailed = true; hide(); resolve(false); }; document.head.appendChild(loader); }); }()); }());',
			'before'
		);
	}

	/**
	 * Emits the responsive unit container styles.
	 *
	 * @return void
	 */
	public static function render_styles(): void {
		if ( ! self::should_render_gate() ) {
			return;
		}
		?>
		<style id="manacost-rsya-inline-style">
			.manacost-rsya-inline {
				box-sizing: border-box;
				width: min(100%, 970px);
				max-width: 970px;
				margin: 28px auto;
				padding: 10px;
				border: 1px solid #dce6ea;
				border-radius: 12px;
				background: #f8fbfc;
				box-shadow: 0 6px 18px rgba(18, 51, 67, .06);
			}

			.manacost-rsya-inline__label {
				margin: 0 0 7px;
				color: #70808a;
				font-size: 11px;
				font-weight: 600;
				letter-spacing: .08em;
				line-height: 1;
				text-transform: uppercase;
			}

			.manacost-rsya-inline__canvas {
				width: 100%;
				height: 90px;
			}

			.manacost-rsya-inline--feed .manacost-rsya-inline__canvas {
				height: auto;
				min-height: 180px;
			}

			@media (max-width: 767px) {
				.manacost-rsya-inline {
					width: min(100%, 320px);
					max-width: 320px;
					margin: 22px auto;
				}

				.manacost-rsya-inline__canvas { height: 100px; }
				.manacost-rsya-inline--feed .manacost-rsya-inline__canvas { min-height: 180px; }
			}
		</style>
		<?php
	}

	/**
	 * Inserts the units after the introduction and Telegram callout of the main article loop.
	 *
	 * @param string $content Current rendered content.
	 * @return string
	 */
	public static function insert_banner( string $content ): string {
		if (
			! self::should_render_legacy_article()
			|| ! in_the_loop()
			|| ! is_main_query()
		) {
			return $content;
		}

		$footer_banner = self::contains_banner_markup( $content, self::FOOTER_BLOCK_ID )
			? '' : self::render_unit( 'after-telegram', self::FOOTER_BLOCK_ID, 'banner', '-after-telegram' );

		if ( self::contains_banner_markup( $content, self::INTRO_BLOCK_ID ) ) {
			return $content . $footer_banner;
		}

		$text_paragraphs = 0;
		$intro_inserted  = false;
		$intro_banner    = self::render_unit( 'intro', self::INTRO_BLOCK_ID, 'banner' );

		$result = preg_replace_callback(
			'#<p\\b[^>]*>.*?</p>#is',
			static function ( array $matches ) use ( &$text_paragraphs, &$intro_inserted, $intro_banner ): string {
				$paragraph = $matches[0];

				if ( '' === wp_strip_all_tags( $paragraph ) ) {
					return $paragraph;
				}

				++$text_paragraphs;

				if ( self::TEXT_PARAGRAPH_POSITION === $text_paragraphs ) {
					$intro_inserted = true;

					return $paragraph . $intro_banner;
				}

				return $paragraph;
			},
			$content
		);

		if ( ! is_string( $result ) ) {
			return $content;
		}

		if ( ! $intro_inserted && $text_paragraphs > 0 ) {
			$result = preg_replace_callback(
				'#<p\\b[^>]*>.*?</p>#is',
				static function ( array $matches ) use ( &$intro_inserted, $intro_banner ): string {
					$paragraph = $matches[0];

					if ( $intro_inserted || '' === wp_strip_all_tags( $paragraph ) ) {
						return $paragraph;
					}

					$intro_inserted = true;

					return $paragraph . $intro_banner;
				},
				$result
			);
		}

		if ( ! $intro_inserted ) {
			$result .= $intro_banner;
		}

		return is_string( $result ) ? $result . $footer_banner : $content;
	}

	/**
	 * Renders a conservative, labelled RTB unit with a page-unique container ID.
	 *
	 * @param string $slot Human-readable placement name.
	 * @param string $block_id         Yandex RTB unit identifier for this placement.
	 * @param string $format RTB display format: banner or feed.
	 * @param string $container_suffix Unique suffix for an additional placement.
	 * @return string
	 */
	private static function render_unit( string $slot, string $block_id, string $format, string $container_suffix = '' ): string {
		$render_type = 'feed' === $format ? ', "type": "feed"' : '';
		return sprintf(
			'<aside class="manacost-rsya-inline manacost-rsya-inline--%4$s" data-manacost-rsya-unit data-manacost-rsya-slot="%3$s" aria-label="Реклама" hidden><p class="manacost-rsya-inline__label">Реклама</p><div class="manacost-rsya-inline__canvas" id="%1$s"></div></aside><script>(function () {
				var container = document.getElementById("%1$s");
				var unit = container ? container.closest("[data-manacost-rsya-unit]") : null;
				if (!unit || unit.getAttribute("data-manacost-rsya-state")) { return; }
				var state = function (value) { unit.setAttribute("data-manacost-rsya-state", value); };
				var collapse = function (value) { unit.hidden = true; state(value); };
				state("queued");
				if (!window.manacostRsyaReady) { collapse("gate-unavailable"); return; }
				var start = function () {
					// A paragraph inside a collapsed shortcode is not a visible ad slot.
					var wrapper = unit.closest(".mtp-spoiler-wrapper, .su-spoiler, details");
					while (wrapper) {
						wrapper.after(unit);
						wrapper = unit.closest(".mtp-spoiler-wrapper, .su-spoiler, details");
					}
				if (window.manacostRsyaLoaderFailed) { collapse("loader-error"); return; }
				window.yaContextCb = window.yaContextCb || [];
				window.yaContextCb.push(function () {
					if (window.manacostRsyaLoaderFailed) { collapse("loader-error"); return; }
					state("requested");
					Ya.Context.AdvManager.render({
						"blockId": "%2$s", "renderTo": "%1$s"%5$s,
						"onError": function (data) {
							if (!data) { return; }
							unit.setAttribute("data-manacost-rsya-code", String(data.code || "").slice(0, 80));
							if ("error" === data.type) { collapse("error"); }
						},
						"onRender": function () {
							unit.hidden = false;
							unit.setAttribute("data-manacost-rsya-rendered", "true");
							state("rendered");
						}
					}, function () { collapse("no-fill"); });
				});
				};
				window.manacostRsyaReady.then(function (allowed) {
					if (!allowed) { collapse("subscriber"); return; }
					if (document.readyState === "loading") {
						document.addEventListener("DOMContentLoaded", start, { once: true });
					} else { start(); }
				});
			}());</script>',
			esc_attr( 'yandex_rtb_' . $block_id . $container_suffix ),
			esc_attr( $block_id ),
			esc_attr( $slot ),
			esc_attr( $format ),
			$render_type
		);
	}

	/**
	 * Renders an editor-selected placement. New articles never get automatic ads.
	 *
	 * @param array<string, string> $attributes Shortcode attributes.
	 * @return string
	 */
	public static function render_shortcode( array $attributes = array() ): string {
		if ( ! self::should_render_public_document() ) {
			return '';
		}

		$format = isset( $attributes['format'] ) ? sanitize_key( $attributes['format'] ) : 'banner';
		if ( ! in_array( $format, array( 'banner', 'feed' ), true ) ) {
			return '';
		}

		++self::$manual_unit_index;
		$block_id = 'feed' === $format ? self::EDITOR_FEED_BLOCK_ID : self::EDITOR_BANNER_BLOCK_ID;

		return self::render_unit( 'editor-' . $format, $block_id, $format, '-manual-' . self::$manual_unit_index );
	}

	/**
	 * Renders one non-sticky unit below an anonymous public Reader profile.
	 * The shared ad gate evaluates the viewing visitor, never the profile owner.
	 *
	 * @return string
	 */
	public static function render_public_profile_banner(): string {
		if ( ! self::should_render_public_profile() ) {
			return '';
		}

		return self::render_unit( 'public-profile', self::PUBLIC_PROFILE_BLOCK_ID, 'banner', '-public-profile' );
	}

	/**
	 * Adds an accessible Classic Editor menu for explicit new-content placements.
	 *
	 * @param array<string, string> $plugins TinyMCE plugins.
	 * @return array<string, string>
	 */
	public static function register_editor_plugin( array $plugins ): array {
		$plugins['manacost_rsya'] = content_url( 'mu-plugins/manacost-rsya-inline/editor.js' );

		return $plugins;
	}

	/**
	 * Places the RTB menu alongside the standard Classic Editor controls.
	 *
	 * @param string[] $buttons Existing TinyMCE buttons.
	 * @return string[]
	 */
	public static function register_editor_button( array $buttons ): array {
		$buttons[] = 'manacost_rsya';

		return array_values( array_unique( $buttons ) );
	}

	/**
	 * Detects an existing container for one placement, not a mention in prose.
	 *
	 * @param string $content Current rendered content.
	 * @param string $block_id Placement to detect independently of other units.
	 * @return bool
	 */
	private static function contains_banner_markup( string $content, string $block_id ): bool {
		return 1 === preg_match(
			'~<div\b[^>]*\bid\s*=\s*(["\'])yandex_rtb_' . preg_quote( $block_id, '~' ) . '(?:-[^"\']*)?\1~i',
			$content
		);
	}

	/**
	 * Checks whether this request may render any privacy-gated RTB unit.
	 *
	 * @return bool
	 */
	private static function should_render_gate(): bool {
		return self::should_render_legacy_article()
			|| self::should_render_public_profile()
			|| self::document_has_shortcode();
	}

	/**
	 * Keeps automatic legacy placements fixed to content published before the cutoff.
	 *
	 * @return bool
	 */
	private static function should_render_legacy_article(): bool {
		if ( ! self::should_render_public_document() || ! is_singular( 'post' ) ) {
			return false;
		}

		$post = get_queried_object();

		return $post instanceof WP_Post
			&& self::ENABLED_FROM_GMT <= $post->post_date_gmt
			&& self::LEGACY_AUTOMATIC_UNTIL_GMT > $post->post_date_gmt;
	}

	/**
	 * Checks whether the requested document is a public, rendered WordPress post.
	 *
	 * @return bool
	 */
	private static function should_render_public_document(): bool {
		if (
			( defined( 'MANACOST_RSYA_INLINE_ENABLED' ) && ! MANACOST_RSYA_INLINE_ENABLED )
			|| is_admin()
			|| ! is_singular()
			|| is_feed()
			|| is_preview()
			|| wp_doing_ajax()
			|| defined( 'MANACOST_GUIDE_PDF_RENDERING' )
		) {
			return false;
		}

		$post = get_queried_object();

		return $post instanceof WP_Post && 'publish' === $post->post_status;
	}

	/**
	 * Detects an explicit editor placement before WordPress processes shortcodes.
	 *
	 * @return bool
	 */
	private static function document_has_shortcode(): bool {
		if ( ! self::should_render_public_document() ) {
			return false;
		}

		$post = get_queried_object();

		return $post instanceof WP_Post && has_shortcode( $post->post_content, self::SHORTCODE );
	}

	/**
	 * Whether the current account page is a public Reader profile, not a workspace.
	 *
	 * @return bool
	 */
	private static function should_render_public_profile(): bool {
		return self::should_render_public_document()
			&& function_exists( 'hs_reader_public_profile_request' )
			&& hs_reader_public_profile_request();
	}
}

Manacost_Rsya_Inline_Banner::boot();
