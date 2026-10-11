<?php
/**
 * Three scoped Classic Editor controls and an accessible review dialog.
 *
 * @package Manacost
 */

namespace Manacost\EditorAi;

defined( 'ABSPATH' ) || exit;

/** Native editor extension; no vendor or theme modifications. */
final class Admin {
	/**
	 * Register editor-only hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'media_buttons', array( self::class, 'buttons' ), 25 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
		add_action( 'admin_footer', array( self::class, 'dialog' ) );
	}

	/**
	 * Resolve an authorized Classic Editor article.
	 *
	 * @return int
	 */
	private static function post_id(): int {
		global $post;
		$screen = get_current_screen();
		if ( ! Jobs::allowed() || ! Provider::enabled() || ! $screen || 'post' !== $screen->base || 'post' !== $screen->post_type || $screen->is_block_editor() ) {
			return 0;
		}
		return $post instanceof \WP_Post && current_user_can( 'edit_post', $post->ID ) ? $post->ID : 0;
	}

	/**
	 * Native media-toolbar actions; scope selection is captured before opening.
	 *
	 * @param string $editor_id Classic editor ID.
	 * @return void
	 */
	public static function buttons( string $editor_id = 'content' ): void {
		if ( 'content' !== $editor_id || ! self::post_id() ) {
			return;
		}
		foreach ( array(
			'cards'     => 'Заполнить карты',
			'editor'    => 'Редактура EditorTeam',
			'proofread' => 'Пунктуация и опечатки',
		) as $mode => $label ) {
			printf( '<button type="button" class="button hs-editor-ai-open" data-mode="%s">%s</button> ', esc_attr( $mode ), esc_html( $label ) );
		}
	}

	/**
	 * No API calls or card catalogs during editor startup.
	 *
	 * @return void
	 */
	public static function assets(): void {
		$post_id = self::post_id();
		if ( ! $post_id ) {
			return;
		}
		$base = plugin_dir_url( __FILE__ );
		wp_enqueue_style( 'hs-editor-ai', $base . 'admin.css', array(), '1.1.0' );
		wp_enqueue_script( 'hs-editor-ai', $base . 'admin.js', array(), '1.1.0', true );
		wp_localize_script(
			'hs-editor-ai',
			'hsEditorAi',
			array(
				'postId' => $post_id,
				'url'    => rest_url( 'hs-editor-ai/v1/jobs' ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Result remains in the current editor until the editor chooses Apply.
	 *
	 * @return void
	 */
	public static function dialog(): void {
		if ( ! self::post_id() ) {
			return;
		}
		?>
		<dialog id="hs-editor-ai-dialog" aria-labelledby="hs-editor-ai-title" aria-describedby="hs-editor-ai-description">
			<div class="hs-editor-ai-header">
				<div><span class="hs-editor-ai-kicker">Помощник редактора · Claude Opus 5.5</span><h2 id="hs-editor-ai-title"></h2></div>
				<button type="button" class="button hs-editor-ai-close">Закрыть</button>
			</div>
			<div class="hs-editor-ai-body">
				<p id="hs-editor-ai-description"></p>
				<p id="hs-editor-ai-scope"></p>
				<ol class="hs-editor-ai-steps" aria-label="Этапы обработки" hidden>
					<li data-step="queued">Отправка</li><li data-step="running">Обработка</li><li data-step="review">Проверка</li><li data-step="ready">Результат</li>
				</ol>
				<p id="hs-editor-ai-status" role="status" aria-live="polite"></p>
				<details class="hs-editor-ai-input" open><summary>Текст для обработки</summary><pre id="hs-editor-ai-input"></pre></details>
				<div class="hs-editor-ai-compare" hidden>
					<label class="hs-editor-ai-markup"><input type="checkbox" id="hs-editor-ai-markup"> Показать HTML-разметку</label>
					<section><h3>Было</h3><pre id="hs-editor-ai-before" tabindex="0" aria-label="Исходный текст"></pre></section>
					<section><h3>Предложение</h3><pre id="hs-editor-ai-after" tabindex="0" aria-label="Предлагаемый текст"></pre></section>
				</div>
			</div>
			<div class="hs-editor-ai-footer">
				<p class="description">Текст и контекст отправляются Anthropic. Применение — только после вашего просмотра.</p>
				<div class="hs-editor-ai-actions">
					<button type="button" class="button hs-editor-ai-close" id="hs-editor-ai-dismiss">Оставить исходный текст</button>
					<button type="button" class="button button-primary" id="hs-editor-ai-run">Отправить текст</button>
					<button type="button" class="button button-primary" id="hs-editor-ai-apply" hidden>Применить изменения</button>
				</div>
			</div>
		</dialog>
		<?php
	}
}
