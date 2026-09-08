<?php
/**
 * Plugin Name: Manacost RСЯ Inline Banner
 * Description: Renders a scoped Yandex RTB banner in the latest article.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the Yandex RTB loader and inline article unit.
 */
final class Manacost_Rsya_Inline_Banner {
	private const TARGET_POST_SLUG        = 'kvest-zhrecz-odna-iz-luchshih-kolod-v-mete-ametistovoj-kreposti';
	private const BLOCK_ID                = 'R-A-16113237-5';
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
		add_filter( 'the_content', array( __CLASS__, 'insert_banner' ), 30 );
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
			self::BLOCK_ID,
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
			|| str_contains( $content, 'yandex_rtb_' . self::BLOCK_ID )
		) {
			return $content;
		}

		$text_paragraphs = 0;
		$intro_banner    = self::render_banner( 'intro' );
		$footer_banner   = self::render_banner( 'after-telegram', '-after-telegram' );

		$result = preg_replace_callback(
			'#<p\\b[^>]*>.*?</p>#is',
			static function ( array $matches ) use ( &$text_paragraphs, $intro_banner ): string {
				$paragraph = $matches[0];

				if ( '' === wp_strip_all_tags( $paragraph ) ) {
					return $paragraph;
				}

				++$text_paragraphs;

				return $paragraph . ( self::TEXT_PARAGRAPH_POSITION === $text_paragraphs ? $intro_banner : '' );
			},
			$content
		);

		return is_string( $result ) ? $result . $footer_banner : $content;
	}

	/**
	 * Renders one scoped Banner call with a page-unique container ID.
	 *
	 * @param string $slot Human-readable placement name.
	 * @param string $container_suffix Unique suffix for an additional placement.
	 * @return string
	 */
	private static function render_banner( string $slot, string $container_suffix = '' ): string {
		return sprintf(
			'<div class="manacost-rsya-inline" data-manacost-rsya-unit data-manacost-rsya-slot="%3$s"><div id="%1$s"></div></div><script>(function () { var container = document.getElementById("%1$s"); var unit = container ? container.closest("[data-manacost-rsya-unit]") : null; var collapse = function () { if (unit) { unit.hidden = true; } }; if (window.manacostRsyaLoaderFailed) { collapse(); } else { window.yaContextCb.push(function () { Ya.Context.AdvManager.render({"blockId": "%2$s", "renderTo": "%1$s", "onError": function (data) { if (data && "error" === data.type) { collapse(); } }, "onRender": function () { if (unit) { unit.setAttribute("data-manacost-rsya-rendered", "true"); } }}, collapse); }); } }());</script>',
			esc_attr( 'yandex_rtb_' . self::BLOCK_ID . $container_suffix ),
			esc_attr( self::BLOCK_ID ),
			esc_attr( $slot )
		);
	}

	/**
	 * Checks whether the requested page is the explicitly enabled target post.
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

		return $post instanceof WP_Post && self::TARGET_POST_SLUG === $post->post_name;
	}
}

Manacost_Rsya_Inline_Banner::boot();
