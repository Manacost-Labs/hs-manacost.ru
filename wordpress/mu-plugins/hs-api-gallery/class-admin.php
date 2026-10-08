<?php
/**
 * Register editor-only UI hooks.
 *
 * @package Manacost
 */

namespace Manacost\ApiGallery;

defined( 'ABSPATH' ) || exit;

/** Register editor-only UI hooks. */
final class Admin {
	/**
	 * Register editor-only UI hooks.
	 */
	public static function register(): void {
		add_action( 'media_buttons', array( self::class, 'button' ), 20 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
		add_action( 'admin_footer', array( self::class, 'dialog' ) );
	}

	/**
	 * Resolve the authorized editor article.
	 *
	 * @return int Eligible article ID, or zero outside the intended editor.
	 */
	private static function post_id(): int {
		global $post;
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base || ! $post instanceof \WP_Post || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
			return 0;
		}
		return current_user_can( 'upload_files' ) && current_user_can( 'edit_post', $post->ID ) ? $post->ID : 0;
	}

	/**
	 * Add a button beside the native media uploader.
	 *
	 * @param string $editor_id Core editor identifier.
	 */
	public static function button( string $editor_id ): void {
		if ( 'content' === $editor_id && self::post_id() ) {
			echo '<button type="button" class="button" id="hs-api-gallery-open"><span class="dashicons dashicons-format-gallery" aria-hidden="true"></span> ' . esc_html__( 'Создать галерею из API', 'manacost' ) . '</button>';
		}
	}

	/**
	 * Scope assets to authorized post/page editors, never all wp-admin.
	 */
	public static function assets(): void {
		$post_id = self::post_id();
		if ( ! $post_id ) {
			return;
		}
		wp_enqueue_media( array( 'post' => $post_id ) );
		$url = plugins_url( 'hs-api-gallery/', dirname( __DIR__ ) . '/hs-api-gallery.php' );
		wp_enqueue_style( 'hs-api-gallery-editor', $url . 'editor.css', array(), '1.0.3' );
		wp_enqueue_script( 'hs-api-gallery-editor', $url . 'editor.js', array( 'media-editor', 'media-views', 'wp-i18n' ), '1.0.4', true );
		wp_add_inline_script(
			'hs-api-gallery-editor',
			'window.hsApiGalleryEditor=' . wp_json_encode(
				array(
					'url'    => admin_url( 'admin-ajax.php', 'relative' ),
					'postId' => $post_id,
					'nonce'  => wp_create_nonce( 'hs_api_gallery_editor' ),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Render a dialog outside the article form; never nest forms.
	 */
	public static function dialog(): void {
		if ( ! self::post_id() ) {
			return;
		}
		?>
		<dialog id="hs-api-gallery-dialog" aria-labelledby="hs-api-gallery-title">
			<div class="hs-api-gallery__header">
				<h2 id="hs-api-gallery-title"><?php esc_html_e( 'Создать галерею из API', 'manacost' ); ?></h2>
				<button type="button" class="button" id="hs-api-gallery-close"><?php esc_html_e( 'Закрыть', 'manacost' ); ?></button>
			</div>
			<p class="hs-api-gallery__intro"><?php esc_html_e( 'Выберите карты нажатием на изображение, затем настройте галерею WordPress.', 'manacost' ); ?></p>
			<form id="hs-api-gallery-search">
				<div class="hs-api-gallery__filters">
					<label><?php esc_html_e( 'Библиотека', 'manacost' ); ?><select id="hs-api-gallery-library">
						<?php foreach ( Catalog::libraries() as $slug => $name ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select></label>
					<label id="hs-api-gallery-format-label"><?php esc_html_e( 'Формат', 'manacost' ); ?><select id="hs-api-gallery-format">
						<option value="standard"><?php esc_html_e( 'Стандарт', 'manacost' ); ?></option>
						<option value="wild"><?php esc_html_e( 'Вольный', 'manacost' ); ?></option>
						<option value="all"><?php esc_html_e( 'Все карты', 'manacost' ); ?></option>
					</select></label>
					<label><?php esc_html_e( 'Название или ID', 'manacost' ); ?><input type="search" id="hs-api-gallery-query" maxlength="120" /></label>
					<button type="submit" class="button"><?php esc_html_e( 'Найти', 'manacost' ); ?></button>
				</div>
			</form>
			<p id="hs-api-gallery-status" role="status" aria-live="polite"></p>
			<div class="hs-api-gallery__body">
				<div class="hs-api-gallery__catalog">
					<div id="hs-api-gallery-results" aria-label="<?php esc_attr_e( 'Объекты библиотеки', 'manacost' ); ?>"></div>
					<button type="button" class="button" id="hs-api-gallery-more" hidden><?php esc_html_e( 'Показать ещё', 'manacost' ); ?></button>
				</div>
				<aside class="hs-api-gallery__selection" aria-label="<?php esc_attr_e( 'Выбранные изображения', 'manacost' ); ?>">
					<div class="hs-api-gallery__selection-header">
						<strong id="hs-api-gallery-count" aria-live="polite" aria-atomic="true"></strong>
						<button type="button" class="button" id="hs-api-gallery-clear" disabled><?php esc_html_e( 'Очистить', 'manacost' ); ?></button>
					</div>
					<p id="hs-api-gallery-empty"><?php esc_html_e( 'Выбранные карты появятся здесь.', 'manacost' ); ?></p>
					<div id="hs-api-gallery-selected"></div>
				</aside>
			</div>
			<div class="hs-api-gallery__footer">
				<label><input type="checkbox" id="hs-api-gallery-ratings" /> <?php esc_html_e( 'Добавить оценки читателей: 5 звёзд под каждым изображением', 'manacost' ); ?></label>
				<button type="button" class="button button-primary" id="hs-api-gallery-create" disabled><?php esc_html_e( 'Настроить галерею', 'manacost' ); ?></button>
			</div>
		</dialog>
		<?php
	}
}
