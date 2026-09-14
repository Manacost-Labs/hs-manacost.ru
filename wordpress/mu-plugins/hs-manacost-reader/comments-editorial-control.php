<?php
/**
 * Per-article HearthPulse discussion control in the WordPress editor.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Controls one article's independent HearthPulse discussion shell. */
final class HS_Reader_Comments_Editorial_Control {
	private const FIELD_NAME   = 'hs_reader_comments_disabled';
	private const META_KEY     = '_hs_reader_comments_disabled';
	private const NONCE_ACTION = 'hs_reader_comments_editorial_control';
	private const NONCE_NAME   = 'hs_reader_comments_editorial_nonce';

	/** Register the small, native editor extension only while Reader comments are enabled. */
	public static function boot(): void {
		add_action( 'add_meta_boxes_post', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'render_submit_box_control' ) );
		add_action( 'save_post_post', array( __CLASS__, 'save_meta_box' ), 10, 2 );
	}

	/** Add the article-level switch in the standard editor sidebar. */
	public static function add_meta_box(): void {
		if ( self::rendering_in_submit_box() ) {
			return;
		}

		add_meta_box(
			'hs-reader-comments-editorial-control',
			'HearthPulse: комментарии',
			array( __CLASS__, 'render_meta_box' ),
			'post',
			'side',
			'high'
		);
	}

	/**
	 * Render the comment switch in the native Classic Editor publish box.
	 *
	 * Gutenberg keeps the regular meta-box fallback because it does not invoke
	 * the Classic Editor submit-box action.
	 *
	 * @param WP_Post $post Current article.
	 */
	public static function render_submit_box_control( WP_Post $post ): void {
		if ( 'post' !== $post->post_type || ! self::rendering_in_submit_box() ) {
			return;
		}

		echo '<div class="misc-pub-section hs-reader-comments-editorial-control">';
		self::render_meta_box( $post );
		echo '</div>';
	}

	/**
	 * Render the reversible article-level comment switch.
	 *
	 * @param WP_Post $post Current article.
	 */
	public static function render_meta_box( WP_Post $post ): void {
		wp_nonce_field( self::nonce_action( $post->ID ), self::NONCE_NAME );

		echo '<p><label for="' . esc_attr( self::FIELD_NAME ) . '">';
		echo '<input type="checkbox" id="' . esc_attr( self::FIELD_NAME ) . '" name="' . esc_attr( self::FIELD_NAME ) . '" value="1" ' . checked( self::is_disabled( $post->ID ), true, false ) . '>';
		echo ' ' . esc_html__( 'Отключить комментарии', 'hs-manacost-reader' );
		echo '</label></p>';
		echo '<p class="description">' . esc_html__( 'Отключает только комментарии HearthPulse для этой статьи. Комментарии на остальных опубликованных статьях останутся включены.', 'hs-manacost-reader' ) . '</p>';
	}

	/**
	 * Save an explicitly submitted editor choice; autosaves, revisions and other
	 * save paths do not alter the switch.
	 *
	 * @param int     $post_id Current article ID.
	 * @param WP_Post $post Current article.
	 */
	public static function save_meta_box( int $post_id, WP_Post $post ): void {
		if ( 'post' !== $post->post_type || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		$raw_nonce = wp_unslash( $_POST[ self::NONCE_NAME ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Verified as a nonce after scalar validation.
		if ( ! is_string( $raw_nonce ) || ! wp_verify_nonce( sanitize_text_field( $raw_nonce ), self::nonce_action( $post_id ) ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$value = isset( $_POST[ self::FIELD_NAME ] ) ? wp_unslash( $_POST[ self::FIELD_NAME ] ) : '0'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict scalar allowlist below.
		if ( ! is_string( $value ) || ! in_array( sanitize_text_field( $value ), array( '0', '1' ), true ) ) {
			return;
		}

		if ( '1' === $value ) {
			update_post_meta( $post_id, self::META_KEY, '1' );
			return;
		}

		delete_post_meta( $post_id, self::META_KEY );
	}

	/**
	 * Whether Reader discussion is disabled for one article.
	 *
	 * @param int $post_id Article ID.
	 */
	public static function is_disabled( int $post_id ): bool {
		return hs_reader_comment_article_is_disabled( $post_id );
	}

	/**
	 * Build a post-specific nonce action to avoid cross-article reuse.
	 *
	 * @param int $post_id Article ID.
	 */
	private static function nonce_action( int $post_id ): string {
		return self::NONCE_ACTION . ':' . $post_id;
	}

	/**
	 * Determines whether the current screen is the Classic Editor.
	 *
	 * @return bool Whether to render through post_submitbox_misc_actions.
	 */
	private static function rendering_in_submit_box(): bool {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return true;
		}

		$screen = get_current_screen();
		if ( null === $screen ) {
			return true;
		}

		return ! $screen->is_block_editor();
	}
}
