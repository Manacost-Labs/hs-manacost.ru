<?php
/**
 * Stable native HTML cards: no shortcode, save hook or remote frontend dependency.
 *
 * @package HSManacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Card representation shared by catalog, editor and frontend.
 *
 * @phpstan-type Article array{id:int,title:string,url:string,image:string,date:string,date_label:string}
 */
final class HS_Article_Cards_Render {
	/**
	 * Render one snapshot of a validated catalog item.
	 *
	 * @param array<string,int|string> $article Validated public metadata.
	 * @param string                   $layout Selected presentation.
	 * @phpstan-param Article $article
	 * @return string
	 */
	public static function html( array $article, string $layout ): string {
		$layout = 'cover' === $layout ? 'cover' : 'compact';
		$image  = '';
		if ( '' !== $article['image'] ) {
			$image = '<img class="hs-article-card__image" src="' . esc_url( $article['image'] ) . '" alt="" width="640" height="360" loading="lazy" />';
		} else {
			$image = '<span class="hs-article-card__placeholder" aria-hidden="true">↗</span>';
		}
		return '<div class="hs-article-card hs-article-card--' . esc_attr( $layout ) . '"><a class="hs-article-card__link" href="' . esc_url( $article['url'] ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( $article['title'] . ' — ' . __( 'открывается в новой вкладке', 'hs-article-cards' ) ) . '">' . $image
			. '<span class="hs-article-card__text"><span class="hs-article-card__title">' . esc_html( $article['title'] ) . '</span>'
			. '<span class="hs-article-card__date">' . esc_html( $article['date_label'] ) . '</span></span>'
			. '<span class="hs-article-card__arrow" aria-hidden="true">↗</span></a></div>';
	}

	/** Load the small stylesheet only on singular documents containing cards. */
	public static function enqueue(): void {
		$post = get_queried_object();
		if ( is_singular() && $post instanceof WP_Post && str_contains( $post->post_content, 'hs-article-card' ) ) {
			wp_enqueue_style( 'hs-article-cards', self::css_url(), array(), '1.0.0' );
		}
	}

	/**
	 * Match the Classic Editor canvas to the published card.
	 *
	 * @param string $styles Existing editor styles.
	 * @return string
	 */
	public static function editor_css( string $styles ): string {
		$screen = is_admin() ? get_current_screen() : null;
		if ( ! $screen || 'post' !== $screen->base || ! in_array( $screen->post_type, array( 'post', 'page' ), true ) ) {
			return $styles;
		}
		return $styles . ( '' === $styles ? '' : ',' ) . self::css_url();
	}

	/**
	 * Public asset URL.
	 *
	 * @return string
	 */
	public static function css_url(): string {
		return plugins_url( 'card.css', __FILE__ );
	}
}
