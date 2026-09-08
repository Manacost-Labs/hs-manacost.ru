<?php
/**
 * Plugin Name: Manacost RСЯ Inline Banner
 * Description: Renders Yandex RTB banners in recent and future articles.
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
	 * includes every normally published article that follows them.
	 */
	private const ENABLED_FROM_GMT        = '2026-08-31 09:00:39';
	private const INTRO_BLOCK_ID          = 'R-A-16113237-6';
	private const FOOTER_BLOCK_ID         = 'R-A-16113237-5';
	private const FLOOR_BLOCK_ID          = 'R-A-16113237-7';
	private const SCRIPT_HANDLE           = 'manacost-rsya-loader';
	private const TEXT_PARAGRAPH_POSITION = 3;

	/**
	 * Registers the page hooks.
	 *
	 * @return void
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_loader' ) );
		add_action( 'wp_head', array( __CLASS__, 'render_styles' ), 39 );
		add_action( 'wp_footer', array( __CLASS__, 'render_floor_ad' ), 90 );
		add_filter( 'the_content', array( __CLASS__, 'insert_banner' ), 30 );
	}

	/**
	 * Renders the desktop-only fixed Floor Ad after article content.
	 *
	 * @return void
	 */
	public static function render_floor_ad(): void {
		if ( ! self::should_render() ) {
			return;
		}
		?>
		<script id="manacost-rsya-floor-ad" data-manacost-rsya-state="queued">
			window.yaContextCb = window.yaContextCb || [];
			if (!window.manacostRsyaFloorQueued) {
			window.manacostRsyaFloorQueued = true;
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
			}
		</script>
		<?php
	}

	/**
	 * Enqueues the Yandex RTB loader once in the document head.
	 *
	 * @return void
	 */
	public static function enqueue_loader(): void {
		if ( ! self::should_render() ) {
			return;
		}
		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			'https://yandex.ru/ads/system/context.js',
			array(),
			self::INTRO_BLOCK_ID,
			array(
				'strategy' => 'async',
			)
		);
		wp_add_inline_script(
			self::SCRIPT_HANDLE,
			'window.yaContextCb = window.yaContextCb || []; window.manacostRsyaLoaderFailed = false; window.addEventListener("error", function (event) { var target = event.target; if (target && "manacost-rsya-loader-js" === target.id) { window.manacostRsyaLoaderFailed = true; document.querySelectorAll("[data-manacost-rsya-unit]").forEach(function (unit) { unit.hidden = true; }); } }, true);',
			'before'
		);
	}

	/**
	 * Emits the responsive unit container styles.
	 *
	 * @return void
	 */
	public static function render_styles(): void {
		if ( ! self::should_render() ) {
			return;
		}
		?>
		<style id="manacost-rsya-inline-style">
			.manacost-rsya-inline {
				box-sizing: border-box;
				width: min(100%, 970px);
				max-width: 970px;
				height: 90px;
				margin: 28px auto;
			}

			.manacost-rsya-inline > div {
				width: 100%;
				height: 100%;
			}

			@media (max-width: 767px) {
				.manacost-rsya-inline {
					width: min(100%, 320px);
					max-width: 320px;
					height: 100px;
					margin: 22px auto;
				}
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
			! self::should_render()
			|| ! in_the_loop()
			|| ! is_main_query()
		) {
			return $content;
		}

		$footer_banner = self::contains_banner_markup( $content, self::FOOTER_BLOCK_ID )
			? '' : self::render_banner( 'after-telegram', self::FOOTER_BLOCK_ID, '-after-telegram' );

		if ( self::contains_banner_markup( $content, self::INTRO_BLOCK_ID ) ) {
			return $content . $footer_banner;
		}

		$text_paragraphs = 0;
		$intro_inserted  = false;
		$intro_banner    = self::render_banner( 'intro', self::INTRO_BLOCK_ID );

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
	 * Renders one scoped Banner call with a page-unique container ID.
	 *
	 * @param string $slot Human-readable placement name.
	 * @param string $block_id         Yandex RTB unit identifier for this placement.
	 * @param string $container_suffix Unique suffix for an additional placement.
	 * @return string
	 */
	private static function render_banner( string $slot, string $block_id, string $container_suffix = '' ): string {
		return sprintf(
			'<div class="manacost-rsya-inline" data-manacost-rsya-unit data-manacost-rsya-slot="%3$s"><div id="%1$s"></div></div><script>(function () {
				var container = document.getElementById("%1$s");
				var unit = container ? container.closest("[data-manacost-rsya-unit]") : null;
				if (!unit || unit.getAttribute("data-manacost-rsya-state")) { return; }
				var state = function (value) { unit.setAttribute("data-manacost-rsya-state", value); };
				var collapse = function (value) { unit.hidden = true; state(value); };
				state("queued");
				if (window.manacostRsyaLoaderFailed) { collapse("loader-error"); return; }
				var start = function () {
					// A paragraph inside a collapsed shortcode is not a visible ad slot.
					var wrapper = unit.closest(".mtp-spoiler-wrapper, .su-spoiler, details");
					while (wrapper) {
						wrapper.after(unit);
						wrapper = unit.closest(".mtp-spoiler-wrapper, .su-spoiler, details");
					}
				window.yaContextCb = window.yaContextCb || [];
				window.yaContextCb.push(function () {
					if (window.manacostRsyaLoaderFailed) { collapse("loader-error"); return; }
					state("requested");
					Ya.Context.AdvManager.render({
						"blockId": "%2$s", "renderTo": "%1$s",
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
				if (document.readyState === "loading") {
					document.addEventListener("DOMContentLoaded", start, { once: true });
				} else { start(); }
			}());</script>',
			esc_attr( 'yandex_rtb_' . $block_id . $container_suffix ),
			esc_attr( $block_id ),
			esc_attr( $slot )
		);
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
	 * Checks whether the requested page is an enabled published article.
	 *
	 * @return bool
	 */
	private static function should_render(): bool {
		if (
			( defined( 'MANACOST_RSYA_INLINE_ENABLED' ) && ! MANACOST_RSYA_INLINE_ENABLED )
			|| is_admin()
			|| ! is_singular( 'post' )
			|| is_feed()
			|| is_preview()
			|| wp_doing_ajax()
			|| defined( 'MANACOST_GUIDE_PDF_RENDERING' )
		) {
			return false;
		}

		$post = get_queried_object();

		return $post instanceof WP_Post
			&& 'publish' === $post->post_status
			&& self::ENABLED_FROM_GMT <= $post->post_date_gmt;
	}
}

Manacost_Rsya_Inline_Banner::boot();
