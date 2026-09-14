<?php
/**
 * Plugin Name: HS Homepage Visibility
 * Description: Lets editors keep individual articles out of TagDiv blocks on the front page.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Controls per-article visibility in the site's front-page article blocks.
 */
final class HS_Homepage_Visibility {
	private const FIELD_NAME   = 'hs_show_on_homepage';
	private const META_KEY     = '_hs_show_on_homepage';
	private const NONCE_ACTION = 'hs_homepage_visibility_save';
	private const NONCE_NAME   = 'hs_homepage_visibility_nonce';

	/** Registers the editor UI and the TagDiv query adapter. */
	public static function boot(): void {
		add_action( 'add_meta_boxes_post', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'render_submit_box_control' ) );
		add_action( 'save_post_post', array( __CLASS__, 'save_meta_box' ), 10, 2 );
		add_action( 'wp_footer', array( __CLASS__, 'mark_front_page_ajax_blocks' ), 100 );
		add_filter( 'td_data_source_blocks_query_args', array( __CLASS__, 'filter_block_query' ), 20, 2 );
	}

	/** Adds the visibility control to the article editor sidebar. */
	public static function add_meta_box(): void {
		if ( self::rendering_in_submit_box() ) {
			return;
		}

		add_meta_box(
			'hs-homepage-visibility',
			'Главная страница',
			array( __CLASS__, 'render_meta_box' ),
			'post',
			'side',
			'high'
		);
	}

	/**
	 * Renders the visibility control in the native Classic Editor publish box.
	 *
	 * The Gutenberg fallback remains a standard meta box because its publish
	 * panel does not execute the Classic Editor submit-box hook.
	 *
	 * @param WP_Post $post Current article.
	 */
	public static function render_submit_box_control( WP_Post $post ): void {
		if ( 'post' !== $post->post_type || ! self::rendering_in_submit_box() ) {
			return;
		}

		echo '<div class="misc-pub-section hs-homepage-visibility">';
		self::render_meta_box( $post );
		echo '</div>';
	}

	/**
	 * Renders the article-level homepage visibility control.
	 *
	 * @param WP_Post $post Current article.
	 */
	public static function render_meta_box( WP_Post $post ): void {
		$show_on_homepage = ! self::is_hidden( $post->ID );

		wp_nonce_field( self::nonce_action( $post->ID ), self::NONCE_NAME );

		echo '<p><label for="hs_show_on_homepage">';
		echo '<input type="checkbox" id="hs_show_on_homepage" name="' . esc_attr( self::FIELD_NAME ) . '" value="1" ' . checked( $show_on_homepage, true, false ) . '>';
		echo ' Показывать на главной';
		echo '</label></p>';
		echo '<p class="description">Снимите галочку, чтобы оставить статью в её рубрике и по прямой ссылке, но убрать из блоков главной.</p>';
	}

	/**
	 * Saves a valid visibility choice from the article editor.
	 *
	 * @param int     $post_id Current article ID.
	 * @param WP_Post $post    Current article.
	 */
	public static function save_meta_box( int $post_id, WP_Post $post ): void {
		if (
			'post' !== $post->post_type ||
			wp_is_post_autosave( $post_id ) ||
			wp_is_post_revision( $post_id ) ||
			! isset( $_POST[ self::NONCE_NAME ] )
		) {
			return;
		}

		$raw_nonce = wp_unslash( $_POST[ self::NONCE_NAME ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The scalar value is sanitized before verification.
		if ( ! is_string( $raw_nonce ) ) {
			return;
		}

		$nonce = sanitize_text_field( $raw_nonce );
		if ( ! wp_verify_nonce( $nonce, self::nonce_action( $post_id ) ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$value = '0';
		if ( isset( $_POST[ self::FIELD_NAME ] ) ) {
			$raw_value = wp_unslash( $_POST[ self::FIELD_NAME ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The scalar value is sanitized before use.
			if ( ! is_string( $raw_value ) ) {
				return;
			}

			$value = sanitize_text_field( $raw_value );
		}

		if ( ! in_array( $value, array( '0', '1' ), true ) ) {
			return;
		}

		if ( '1' === $value ) {
			if ( self::is_hidden( $post_id ) ) {
				delete_post_meta( $post_id, self::META_KEY );
			}
			return;
		}

		update_post_meta( $post_id, self::META_KEY, '0' );
	}

	/**
	 * Marks TagDiv blocks rendered on the front page for their AJAX follow-up queries.
	 *
	 * TagDiv sends a block's serialized attributes back to WordPress when a visitor
	 * uses next/previous pagination. The request itself is admin-ajax.php, so it is
	 * no longer a WordPress front-page request. This marker carries that context
	 * without changing the theme or the block configuration.
	 */
	public static function mark_front_page_ajax_blocks(): void {
		if ( ! function_exists( 'is_front_page' ) || ! is_front_page() ) {
			return;
		}

		echo '<script id="hs-homepage-visibility-ajax">';
		echo '(function (blocks) {';
		echo 'if (!Array.isArray(blocks)) { return; }';
		echo 'blocks.forEach(function (block) {';
		echo 'if (!block || typeof block.atts !== "string") { return; }';
		echo 'try {';
		echo 'var atts = JSON.parse(block.atts);';
		echo 'atts.hs_homepage_visibility = "1";';
		echo 'block.atts = JSON.stringify(atts);';
		echo '} catch (error) {}';
		echo '});';
		echo '}(window.tdBlocksArray));';
		echo '</script>';
	}

	/**
	 * Keeps opted-out articles out of TagDiv blocks rendered on the front page.
	 *
	 * Legacy articles have no meta value and remain visible by default.
	 *
	 * @param array<string, mixed> $args Query arguments prepared by TagDiv.
	 * @param array<string, mixed> $atts Block attributes prepared by TagDiv.
	 * @return array<string, mixed>
	 */
	public static function filter_block_query( array $args, array $atts ): array {
		if ( ! self::is_homepage_block_query( $atts ) || ! self::query_includes_posts( $args ) ) {
			return $args;
		}

		$existing_meta_query = $args['meta_query'] ?? array();
		if ( ! is_array( $existing_meta_query ) ) {
			$existing_meta_query = array();
		}

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- The per-post opt-out marker is the required query constraint for current TagDiv blocks.
		$args['meta_query'] = array(
			'relation' => 'AND',
			array(
				'key'     => self::META_KEY,
				'compare' => 'NOT EXISTS',
			),
		);

		if ( array() !== $existing_meta_query ) {
			$args['meta_query'][] = $existing_meta_query;
		}

		return $args;
	}

	/**
	 * Determines whether a TagDiv query belongs to the front page.
	 *
	 * @param array<string, mixed> $atts Block attributes prepared by TagDiv.
	 * @return bool Whether the query must respect homepage visibility.
	 */
	private static function is_homepage_block_query( array $atts ): bool {
		if ( function_exists( 'is_front_page' ) && is_front_page() ) {
			return true;
		}

		$state_class = 'tdc_state';
		if ( ! class_exists( $state_class, false ) || ! is_callable( array( $state_class, 'is_td_block_ajax' ) ) ) {
			return false;
		}

		if ( ! isset( $atts['hs_homepage_visibility'] ) || ! is_string( $atts['hs_homepage_visibility'] ) ) {
			return false;
		}

		return true === call_user_func( array( $state_class, 'is_td_block_ajax' ) )
			&& '1' === $atts['hs_homepage_visibility'];
	}

	/**
	 * Determines whether an article has opted out of the homepage.
	 *
	 * @param int $post_id Article ID.
	 * @return bool Whether the article is hidden from the homepage.
	 */
	private static function is_hidden( int $post_id ): bool {
		return '0' === get_post_meta( $post_id, self::META_KEY, true );
	}

	/**
	 * Determines whether the current editor uses the Classic Editor publish box.
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

	/**
	 * Creates an article-specific action name for its editor nonce.
	 *
	 * @param int $post_id Article ID.
	 * @return string Nonce action name.
	 */
	private static function nonce_action( int $post_id ): string {
		return self::NONCE_ACTION . '_' . $post_id;
	}

	/**
	 * Determines whether a TagDiv block query can contain standard articles.
	 *
	 * @param array<string, mixed> $args Query arguments prepared by TagDiv.
	 * @return bool Whether the query can contain standard articles.
	 */
	private static function query_includes_posts( array $args ): bool {
		$post_type = $args['post_type'] ?? 'post';

		if ( is_array( $post_type ) ) {
			return in_array( 'post', $post_type, true ) || in_array( 'any', $post_type, true );
		}

		return in_array( $post_type, array( '', 'any', 'post' ), true );
	}
}

HS_Homepage_Visibility::boot();
