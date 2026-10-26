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
	 * Allowlisted presentation presets. First value is the default.
	 *
	 * @return array<string,array<int|string,string>>
	 */
	public static function presets(): array {
		return array(
			'columns' => array(
				'2' => '2',
				'3' => '3',
			),
			'image'   => array(
				'normal' => __( 'Средняя', 'hs-article-cards' ),
				'small'  => __( 'Маленькая', 'hs-article-cards' ),
				'large'  => __( 'Крупная', 'hs-article-cards' ),
			),
			'spacing' => array(
				'normal' => __( 'Обычные', 'hs-article-cards' ),
				'tight'  => __( 'Плотные', 'hs-article-cards' ),
				'airy'   => __( 'Свободные', 'hs-article-cards' ),
			),
			'radius'  => array(
				'soft'   => __( 'Мягкие', 'hs-article-cards' ),
				'square' => __( 'Прямые', 'hs-article-cards' ),
				'round'  => __( 'Округлые', 'hs-article-cards' ),
			),
			'date'    => array(
				'show' => __( 'Показывать', 'hs-article-cards' ),
				'hide' => __( 'Скрывать', 'hs-article-cards' ),
			),
			'width'   => array(
				'full'   => __( 'По ширине текста', 'hs-article-cards' ),
				'narrow' => __( 'Узкий блок по центру', 'hs-article-cards' ),
			),
		);
	}

	/**
	 * Normalize defensive direct renderer calls to safe class tokens.
	 *
	 * @param array<string,string> $settings Requested presets.
	 * @return array<string,string>
	 */
	public static function normalize( array $settings ): array {
		$normalized = array();
		foreach ( self::presets() as $key => $choices ) {
			$value              = $settings[ $key ] ?? '';
			$normalized[ $key ] = isset( $choices[ $value ] ) ? $value : (string) array_key_first( $choices );
		}
		return $normalized;
	}

	/**
	 * Render one snapshot of a validated catalog item.
	 *
	 * @param array<string,int|string> $article Validated public metadata.
	 * @param string                   $layout Selected presentation.
	 * @param array<string,string>     $settings Presentation presets.
	 * @phpstan-param Article $article
	 * @return string
	 */
	public static function html( array $article, string $layout, array $settings = array() ): string {
		$layout   = in_array( $layout, array( 'compact', 'cover', 'grid', 'list', 'folded' ), true ) ? $layout : 'compact';
		$settings = self::normalize( $settings );
		$classes  = 'hs-article-card hs-article-card--' . $layout;
		foreach ( array( 'image', 'radius' ) as $key ) {
			$classes .= ' hs-article-card--' . $key . '-' . $settings[ $key ];
		}
		$date  = 'show' === $settings['date'] ? '<span class="hs-article-card__date">' . esc_html( $article['date_label'] ) . '</span>' : '';
		$image = '';
		if ( '' !== $article['image'] ) {
			$image = '<img class="hs-article-card__image" src="' . esc_url( $article['image'] ) . '" alt="" width="640" height="360" loading="lazy" />';
		} else {
			$image = '<span class="hs-article-card__placeholder" aria-hidden="true">↗</span>';
		}
		return '<div class="' . esc_attr( $classes ) . '"><a class="hs-article-card__link" href="' . esc_url( $article['url'] ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( $article['title'] . ' — ' . __( 'открывается в новой вкладке', 'hs-article-cards' ) ) . '">' . $image
			. '<span class="hs-article-card__text"><span class="hs-article-card__title">' . esc_html( $article['title'] ) . '</span>'
			. $date . '</span>'
			. '<span class="hs-article-card__arrow" aria-hidden="true">↗</span></a></div>';
	}

	/** Load the small stylesheet only on singular documents containing cards. */
	public static function enqueue(): void {
		$post = get_queried_object();
		if ( is_singular() && $post instanceof WP_Post && str_contains( $post->post_content, 'hs-article-card' ) ) {
			wp_enqueue_style( 'hs-article-cards', self::css_url(), array(), '1.4.0' );
			if ( str_contains( $post->post_content, 'hs-article-cards--folded' ) ) {
				wp_enqueue_script( 'hs-article-cards-folded', plugins_url( 'folded.js', __FILE__ ), array(), '1.4.0', true );
			}
		}
		if ( is_singular() && $post instanceof WP_Post && ( str_contains( $post->post_content, 'hs-article-component' ) || str_contains( $post->post_content, 'hs-inline-image' ) ) ) {
			self::component_assets();
		}
	}

	/** Load scoped Manacost component assets; no theme or global document changes. */
	public static function component_assets(): void {
		wp_enqueue_style( 'hs-article-components-tokens', plugins_url( 'manacost-tokens.css', __FILE__ ), array(), '1.4.0' );
		wp_enqueue_style( 'hs-article-components', plugins_url( 'components.css', __FILE__ ), array( 'hs-article-components-tokens' ), '1.4.0' );
		wp_enqueue_script( 'hs-article-components', plugins_url( 'components.js', __FILE__ ), array(), '1.4.0', true );
	}

	/** Include scoped styles when Gutenberg places its canvas in an iframe. */
	public static function block_editor_assets(): void {
		if ( is_admin() ) {
			self::component_assets();
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
		return $styles . ( '' === $styles ? '' : ',' ) . self::css_url() . ',' . add_query_arg( 'ver', '1.4.0', plugins_url( 'manacost-tokens.css', __FILE__ ) ) . ',' . add_query_arg( 'ver', '1.4.0', plugins_url( 'components.css', __FILE__ ) );
	}

	/**
	 * Public asset URL.
	 *
	 * @return string
	 */
	public static function css_url(): string {
		return add_query_arg( 'ver', '1.4.0', plugins_url( 'card.css', __FILE__ ) );
	}
}
