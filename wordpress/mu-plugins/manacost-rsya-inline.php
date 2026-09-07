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
		wp_add_inline_script( self::SCRIPT_HANDLE, 'window.yaContextCb = window.yaContextCb || [];', 'before' );
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
				display: flex;
				min-height: 120px;
				margin: 28px auto;
				align-items: center;
				justify-content: center;
				overflow: hidden;
			}

			.manacost-rsya-inline > div {
				width: 100%;
			}

			@media (max-width: 767px) {
				.manacost-rsya-inline {
					min-height: 100px;
					margin: 22px auto;
				}
			}
		</style>
		<?php
	}

	/**
	 * Inserts the unit after the third text paragraph of the main article loop.
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
		$banner          = sprintf(
			'<div class="manacost-rsya-inline" data-manacost-rsya-unit><div id="%1$s"></div></div><script>window.yaContextCb.push(function () { Ya.Context.AdvManager.render({"blockId": "%2$s", "renderTo": "%1$s"}); });</script>',
			esc_attr( 'yandex_rtb_' . self::BLOCK_ID ),
			esc_attr( self::BLOCK_ID )
		);

		$result = preg_replace_callback(
			'#<p\\b[^>]*>.*?</p>#is',
			static function ( array $matches ) use ( &$text_paragraphs, $banner ): string {
				$paragraph = $matches[0];

				if ( '' === wp_strip_all_tags( $paragraph ) ) {
					return $paragraph;
				}

				++$text_paragraphs;

				return $paragraph . ( self::TEXT_PARAGRAPH_POSITION === $text_paragraphs ? $banner : '' );
			},
			$content
		);

		return is_string( $result ) ? $result : $content;
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
