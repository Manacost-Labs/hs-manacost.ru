<?php
/**
 * Screen-scoped article picker and private AJAX boundary.
 *
 * @package HSManacost
 */

defined( 'ABSPATH' ) || exit;

/** Editor entrypoints do not replace editor instances or save handlers. */
final class HS_Article_Cards_Admin {
	/** Register the supported WordPress extension points. */
	public static function register(): void {
		add_action( 'media_buttons', array( self::class, 'button' ) );
		add_action( 'add_meta_boxes', array( self::class, 'block_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
		add_action( 'admin_footer', array( self::class, 'dialog' ) );
		add_action( 'wp_ajax_hs_article_cards', array( self::class, 'ajax' ) );
	}

	/**
	 * Only the main article/page editor with per-document edit permission.
	 *
	 * @return WP_Post|null
	 */
	private static function document(): ?WP_Post {
		$screen = get_current_screen();
		$post   = $GLOBALS['post'] ?? null;
		if ( ! $screen || 'post' !== $screen->base || ! in_array( $screen->post_type, array( 'post', 'page' ), true ) || ! $post instanceof WP_Post || ! current_user_can( 'edit_post', $post->ID ) ) {
			return null;
		}
		return $post;
	}

	/**
	 * Add a Classic Editor control beside Add Media.
	 *
	 * @param string $editor_id Editor instance.
	 */
	public static function button( string $editor_id = 'content' ): void {
		if ( 'content' !== $editor_id || ! self::document() ) {
			return;
		}
		echo '<button type="button" class="button hs-article-cards-open"><span class="dashicons dashicons-admin-links" aria-hidden="true"></span> ' . esc_html__( 'Вставить статью', 'hs-article-cards' ) . '</button>';
		echo '<button type="button" class="button hs-components-open">' . esc_html__( 'Компонент Manacost', 'hs-article-cards' ) . '</button>';
		if ( current_user_can( 'upload_files' ) && ! use_block_editor_for_post( self::document() ) ) {
			echo '<button type="button" class="button hs-inline-image-open">' . esc_html__( 'Картинка в строке', 'hs-article-cards' ) . '</button>';
		}
	}

	/** Add the same picker to the block editor's supported meta-box area. */
	public static function block_metabox(): void {
		$post = self::document();
		if ( $post && use_block_editor_for_post( $post ) ) {
			add_meta_box( 'hs-article-cards', __( 'Карточка статьи', 'hs-article-cards' ), array( self::class, 'metabox' ), $post->post_type, 'side' );
		}
	}

	/** Block editor entry point. */
	public static function metabox(): void {
		self::button();
		echo '<p>' . esc_html__( 'Найдите статью по названию или категории и вставьте карточку с обложкой и датой.', 'hs-article-cards' ) . '</p>';
	}

	/** Enqueue assets only on supported editor screens. */
	public static function enqueue(): void {
		$post = self::document();
		if ( ! $post ) {
			return;
		}
		$block = use_block_editor_for_post( $post );
		if ( current_user_can( 'upload_files' ) ) {
			wp_enqueue_media( array( 'post' => $post->ID ) );
			wp_enqueue_script( 'hs-inline-image', plugins_url( 'inline-image.js', __FILE__ ), $block ? array( 'media-editor', 'wp-rich-text', 'wp-block-editor', 'wp-element' ) : array( 'media-editor' ), '1.4.1', true );
		}
		if ( $block ) {
			wp_enqueue_script( 'hs-article-components-block', plugins_url( 'components-block.js', __FILE__ ), array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'hs-article-components' ), '1.4.1', true );
		}
		HS_Article_Cards_Render::component_assets();
		wp_enqueue_script( 'hs-article-cards-folded', plugins_url( 'folded.js', __FILE__ ), array(), '1.4.1', true );
		wp_enqueue_script( 'hs-article-dialog-focus', plugins_url( 'dialog-focus.js', __FILE__ ), array(), '1.4.1', true );
		wp_enqueue_style( 'hs-article-components-editor', plugins_url( 'components-editor.css', __FILE__ ), array(), '1.4.1' );
		wp_enqueue_script( 'hs-article-components-editor', plugins_url( 'components-editor.js', __FILE__ ), array( 'hs-article-cards-picker', 'hs-article-components' ), '1.4.1', true );
		wp_enqueue_style( 'hs-article-cards', HS_Article_Cards_Render::css_url(), array(), '1.4.1' );
		wp_enqueue_style( 'hs-article-cards-picker', plugins_url( 'editor.css', __FILE__ ), array( 'hs-article-cards' ), '1.4.1' );
		wp_enqueue_script( 'hs-article-cards-picker', plugins_url( 'editor.js', __FILE__ ), $block ? array( 'wp-blocks', 'wp-data', 'wp-block-editor', 'hs-article-cards-folded', 'hs-article-dialog-focus' ) : array( 'hs-article-cards-folded', 'hs-article-dialog-focus' ), '1.4.1', true );
		wp_localize_script(
			'hs-article-cards-picker',
			'hsArticleCards',
			array(
				'url'         => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'hs_article_cards' ),
				'postId'      => $post->ID,
				'blockEditor' => $block,
				'defaults'    => HS_Article_Cards_Render::normalize( array() ),
				'labels'      => array(
					'loading'       => __( 'Загружаем статьи…', 'hs-article-cards' ),
					'empty'         => __( 'Ничего не найдено. Измените запрос или выберите другую категорию.', 'hs-article-cards' ),
					'error'         => __( 'Не удалось загрузить данные. Повторите запрос. Если сессия истекла, сохраните текст и войдите снова.', 'hs-article-cards' ),
					'ready'         => __( 'Выберите до 6 статей. Можно искать дальше — выбранные останутся в подборке.', 'hs-article-cards' ),
					'insert'        => __( 'Вставить карточки', 'hs-article-cards' ),
					'inserting'     => __( 'Подготавливаем карточки…', 'hs-article-cards' ),
					'page'          => __( 'Страница', 'hs-article-cards' ),
					'of'            => __( 'из', 'hs-article-cards' ),
					'categories'    => __( 'Все категории', 'hs-article-cards' ),
					'categoryError' => __( 'Категории не загрузились. Нажмите «Ещё категории», чтобы повторить.', 'hs-article-cards' ),
					'noImage'       => __( 'Без обложки', 'hs-article-cards' ),
					'newTab'        => __( 'Откроется в новой вкладке', 'hs-article-cards' ),
					'limit'         => __( 'В подборке уже 6 статей. Удалите одну, чтобы добавить другую.', 'hs-article-cards' ),
					'selected'      => __( 'Выбрано', 'hs-article-cards' ),
					'remove'        => __( 'Убрать', 'hs-article-cards' ),
					'earlier'       => __( 'Выше', 'hs-article-cards' ),
					'later'         => __( 'Ниже', 'hs-article-cards' ),
					'editorError'   => __( 'Редактор ещё не готов. Закройте окно и повторите вставку.', 'hs-article-cards' ),
				),
			)
		);
	}

	/** Render the initially closed dialog. */
	public static function dialog(): void {
		if ( self::document() ) {
			require __DIR__ . '/dialog.php';
			require __DIR__ . '/components-dialog.php';
		}
	}

	/**
	 * Read a scalar request field after the nonce has been verified by ajax().
	 *
	 * @param string $key Field name.
	 * @param string $fallback Default value.
	 * @return string
	 */
	private static function field( string $key, string $fallback = '' ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- ajax() verifies the purpose-specific nonce before reading fields.
		return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : $fallback;
	}

	/**
	 * Validate settings before looking up the public article.
	 *
	 * @param string $source Allowed catalog.
	 * @param int    $id Article ID.
	 * @param string $layout Allowed layout.
	 * @return array{html:string}|WP_Error
	 */
	private static function card( string $source, int $id, string $layout ): array|WP_Error {
		$settings = json_decode( self::field( 'settings', '{}' ), true );
		$presets  = HS_Article_Cards_Render::presets();
		if ( ! is_array( $settings ) || array_diff_key( $settings, $presets ) ) {
			wp_send_json_error( array( 'message' => __( 'Некорректные настройки карточки.', 'hs-article-cards' ) ), 400 );
		}
		foreach ( $settings as $key => $value ) {
			if ( ! is_string( $value ) || ! isset( $presets[ $key ][ $value ] ) ) {
				wp_send_json_error( array( 'message' => __( 'Некорректные настройки карточки.', 'hs-article-cards' ) ), 400 );
			}
		}
		$article = HS_Article_Cards_Catalog::article( $source, $id );
		return is_wp_error( $article ) ? $article : array( 'html' => HS_Article_Cards_Render::html( $article, $layout, $settings ) );
	}

	/** Private metadata/HTML endpoint. No post writes occur here. */
	public static function ajax(): void {
		check_ajax_referer( 'hs_article_cards', 'nonce' );
		$post_id = absint( self::field( 'postId' ) );
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) || ! in_array( get_post_type( $post_id ), array( 'post', 'page' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Нет прав на редактирование этой записи.', 'hs-article-cards' ) ), 403 );
		}
		$source   = self::field( 'source' );
		$mode     = self::field( 'mode' );
		$page     = self::field( 'page', '1' );
		$category = self::field( 'category', '0' );
		$id       = self::field( 'id', '0' );
		$layout   = self::field( 'layout', 'compact' );
		if ( ! in_array( $source, array( 'manacost', 'koloda' ), true ) || ! in_array( $mode, array( 'posts', 'categories', 'card' ), true )
			|| ! ctype_digit( $page ) || (int) $page < 1 || (int) $page > 10000 || ! ctype_digit( $category ) || strlen( $category ) > 9
			|| ! ctype_digit( $id ) || strlen( $id ) > 9 || ! in_array( $layout, array( 'compact', 'cover', 'grid', 'list', 'folded' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Некорректные параметры поиска.', 'hs-article-cards' ) ), 400 );
		}
		if ( 'categories' === $mode ) {
			$result = HS_Article_Cards_Catalog::categories( $source, (int) $page );
		} elseif ( 'card' === $mode ) {
			$result = self::card( $source, (int) $id, $layout );
		} else {
			$result = HS_Article_Cards_Catalog::posts( $source, mb_substr( self::field( 'search' ), 0, 100 ), (int) $category, (int) $page );
		}
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 502 );
		}
		wp_send_json_success( $result );
	}
}
