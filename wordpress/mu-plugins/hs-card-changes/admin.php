<?php
/**
 * Editor button, dialog assets and the private AJAX boundary for card changes.
 *
 * @package HSManacost
 */

defined( 'ABSPATH' ) || exit;

/** Editor entrypoints never replace editor instances or save handlers. */
final class HS_Card_Changes_Admin {
	private const MAX_ITEMS           = 12;
	private const RESOLVES_PER_MINUTE = 60;

	/** Register the supported WordPress extension points. */
	public static function register(): void {
		add_action( 'media_buttons', array( self::class, 'button' ) );
		add_action( 'add_meta_boxes', array( self::class, 'block_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
		add_action( 'admin_footer', array( self::class, 'dialog' ) );
		add_action( 'wp_ajax_hs_card_changes', array( self::class, 'ajax' ) );
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
		echo '<button type="button" class="button hs-card-changes-open"><span class="dashicons dashicons-image-flip-horizontal" aria-hidden="true"></span> ' . esc_html__( 'Изменения карт', 'hs-card-changes' ) . '</button>';
	}

	/** Block editor entry point in the supported meta-box area. */
	public static function block_metabox(): void {
		$post = self::document();
		if ( $post && use_block_editor_for_post( $post ) ) {
			add_meta_box( 'hs-card-changes', __( 'Изменения карт', 'hs-card-changes' ), array( self::class, 'metabox' ), $post->post_type, 'side' );
		}
	}

	/** Meta-box content. */
	public static function metabox(): void {
		self::button();
		echo '<p>' . esc_html__( 'Найдите карту по названию и вставьте «до → после» с плашками изменений.', 'hs-card-changes' ) . '</p>';
	}

	/** Enqueue assets only on supported editor screens. */
	public static function enqueue(): void {
		$post = self::document();
		if ( ! $post ) {
			return;
		}
		$block   = use_block_editor_for_post( $post );
		$version = HS_Card_Changes_Render::VERSION;
		HS_Card_Changes_Render::assets();
		wp_enqueue_style( 'hs-card-changes-editor', plugins_url( 'editor.css', __FILE__ ), array( 'hs-card-changes' ), $version );
		wp_enqueue_script( 'hs-card-changes-focus', plugins_url( 'dialog-focus.js', __FILE__ ), array(), $version, true );
		wp_enqueue_script( 'hs-card-changes-editor', plugins_url( 'editor.js', __FILE__ ), $block ? array( 'wp-blocks', 'wp-data', 'wp-block-editor', 'hs-card-changes-focus' ) : array( 'hs-card-changes-focus' ), $version, true );
		wp_localize_script(
			'hs-card-changes-editor',
			'hsCardChanges',
			array(
				'url'         => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'hs_card_changes' ),
				'postId'      => $post->ID,
				'blockEditor' => $block,
				'maxItems'    => self::MAX_ITEMS,
				'defaultDate' => self::default_date( $post ),
				'keys'        => array(
					'constructed' => HS_Card_Changes_Pairs::keys_for( 'constructed' ),
					'bg'          => HS_Card_Changes_Pairs::keys_for( 'bg' ),
				),
				'modeNames'   => array(
					'constructed' => __( 'Constructed', 'hs-card-changes' ),
					'bg'          => __( 'Поля сражений', 'hs-card-changes' ),
				),
			)
		);
	}

	/**
	 * The article's UTC publication moment (or now for a new draft) in datetime-local form.
	 *
	 * @param WP_Post $post Edited post.
	 */
	private static function default_date( WP_Post $post ): string {
		$gmt  = HS_Card_Changes_Pairs::normalize_date( $post->post_date_gmt );
		$date = '' !== $gmt ? $gmt : gmdate( 'Y-m-d H:i:s' );
		return substr( str_replace( ' ', 'T', $date ), 0, 16 );
	}

	/** Render the initially closed dialog. */
	public static function dialog(): void {
		if ( self::document() ) {
			require __DIR__ . '/dialog.php';
		}
	}

	/**
	 * Read a scalar request field after the nonce has been verified by ajax().
	 *
	 * @param string $key Field name.
	 */
	private static function field( string $key ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- ajax() verifies the purpose-specific nonce before reading fields.
		return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
	}

	/**
	 * Raw JSON payload of the compose request. Decoded, then validated field by field.
	 *
	 * @return array<mixed>
	 */
	private static function payload(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- ajax() verifies the nonce; every decoded field is validated against closed lists in HS_Card_Changes_Render::item_from_editor().
		$raw     = isset( $_POST['items'] ) && is_string( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : '';
		$decoded = strlen( $raw ) <= 65536 ? json_decode( $raw, true, 8 ) : null;
		return is_array( $decoded ) && array_is_list( $decoded ) ? $decoded : array();
	}

	/** Private metadata/HTML endpoint. No post writes occur here. */
	public static function ajax(): void {
		check_ajax_referer( 'hs_card_changes', 'nonce' );
		$post_id = absint( self::field( 'postId' ) );
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) || ! in_array( get_post_type( $post_id ), array( 'post', 'page' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Нет прав на редактирование этой записи.', 'hs-card-changes' ) ), 403 );
		}
		$mode = self::field( 'mode' );
		if ( 'resolve' === $mode && self::throttled() ) {
			wp_send_json_error( array( 'message' => __( 'Слишком много запросов истории карт. Подождите минуту.', 'hs-card-changes' ) ), 429 );
		}
		$result = match ( $mode ) {
			'search'  => self::search( mb_substr( self::field( 'term' ), 0, 60 ), self::field( 'kind' ) ),
			'resolve' => self::resolve( self::field( 'card' ), self::field( 'cardMode' ), self::field( 'date' ) ),
			'compose' => self::compose( self::payload() ),
			default   => null,
		};
		if ( null === $result ) {
			wp_send_json_error( array( 'message' => __( 'Некорректные параметры.', 'hs-card-changes' ) ), 400 );
		}
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 'card_changes_catalog' === $result->get_error_code() ? 503 : 400 );
		}
		wp_send_json_success( $result );
	}

	/**
	 * Each resolve may reach the provider; one editor gets a bounded number per minute.
	 */
	private static function throttled(): bool {
		$key   = 'hs_cc_rate_' . get_current_user_id() . '_' . (int) floor( time() / MINUTE_IN_SECONDS );
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, 2 * MINUTE_IN_SECONDS );
		return $count >= self::RESOLVES_PER_MINUTE;
	}

	/**
	 * Search Russian names in the hs-tooltip catalogs (Constructed and Battlegrounds).
	 *
	 * @param string $term Search text.
	 * @param string $kind "all", "constructed" or "bg".
	 * @return array{items:list<array<string,string>>}|WP_Error|null
	 */
	private static function search( string $term, string $kind ) {
		if ( ! in_array( $kind, array( 'all', 'constructed', 'bg' ), true ) ) {
			return null;
		}
		if ( ! function_exists( 'hs_smart_tooltip_get_search_catalog' ) || ! function_exists( 'hs_smart_tooltip_get_bgs_search_catalog' ) || ! function_exists( 'hs_smart_tooltip_run_search' ) ) {
			return new WP_Error( 'card_changes_catalog', __( 'Каталог карт недоступен: плагин HS Smart Tooltip не активен.', 'hs-card-changes' ) );
		}
		if ( mb_strlen( trim( $term ) ) < 2 ) {
			return array( 'items' => array() );
		}
		$items = array();
		foreach ( array( 'constructed', 'bg' ) as $mode ) {
			if ( 'all' !== $kind && $kind !== $mode ) {
				continue;
			}
			$catalog = 'bg' === $mode ? hs_smart_tooltip_get_bgs_search_catalog() : hs_smart_tooltip_get_search_catalog();
			foreach ( hs_smart_tooltip_run_search( $catalog, $term, 12 ) as $entry ) {
				$id = is_string( $entry['id'] ?? null ) ? $entry['id'] : '';
				if ( HS_Card_Changes_Pairs::valid_card( $id ) ) {
					$items[] = array(
						'id'   => $id,
						'mode' => $mode,
						'name' => HS_Card_Changes_Pairs::clean_name( (string) ( $entry['name'] ?? '' ) ),
						'img'  => self::thumbnail( (string) ( $entry['img'] ?? '' ) ),
					);
				}
			}
		}
		return array( 'items' => $items );
	}

	/**
	 * Catalog thumbnails are shown only from HTTPS URLs.
	 *
	 * @param string $url Catalog image URL.
	 */
	private static function thumbnail( string $url ): string {
		return str_starts_with( $url, 'https://' ) ? esc_url_raw( $url ) : '';
	}

	/**
	 * Resolve one card for the dialog: images, state and automatic plates.
	 *
	 * @param string $card Card identifier.
	 * @param string $mode Card mode.
	 * @param string $date Patch date.
	 * @return array<string,mixed>|null
	 */
	private static function resolve( string $card, string $mode, string $date ): ?array {
		$date = HS_Card_Changes_Pairs::normalize_date( $date );
		if ( '' === $date || ! HS_Card_Changes_Pairs::valid_card( $card ) || ! in_array( $mode, HS_Card_Changes_Pairs::modes(), true ) ) {
			return null;
		}
		$found = HS_Card_Changes_Source::resolve( $mode, $card, $date );
		$sides = array();
		foreach ( array( 'before', 'after' ) as $side ) {
			$row            = $found[ $side ] ?? null;
			$sides[ $side ] = $row ? array(
				'hash' => $row['hash'],
				'at'   => substr( $row['at'], 0, 16 ),
				'url'  => HS_Card_Changes_Render::image_url( $row['hash'] ),
			) : null;
		}
		return array(
			'card'   => $card,
			'mode'   => $mode,
			'date'   => $date,
			'name'   => $found['name'],
			'state'  => $found['state'],
			'before' => $sides['before'],
			'after'  => $sides['after'],
			'plates' => $found['plates'],
		);
	}

	/**
	 * Build canonical shortcode text and a preview from the dialog's structured fields.
	 *
	 * @param array<mixed> $rows Decoded item rows.
	 * @return array{shortcode:string,html:string}|WP_Error|null
	 */
	private static function compose( array $rows ) {
		if ( ! $rows || count( $rows ) > self::MAX_ITEMS ) {
			return null;
		}
		$items = array();
		foreach ( $rows as $row ) {
			$item = is_array( $row ) ? HS_Card_Changes_Render::item_from_editor( $row ) : null;
			if ( null === $item ) {
				return new WP_Error( 'card_changes_invalid', __( 'Проверьте даты, названия и значения плашек: часть полей заполнена неверно.', 'hs-card-changes' ) );
			}
			$items[] = $item;
		}
		$options = HS_Card_Changes_Render::options(
			array(
				'columns'  => self::field( 'columns' ),
				'size'     => self::field( 'size' ),
				'captions' => self::field( 'captions' ),
			)
		);
		return array(
			'shortcode' => HS_Card_Changes_Render::build_shortcode( $items, $options ),
			'html'      => HS_Card_Changes_Render::html( $items, array_merge( $options, array( 'columns' => 1 === count( $items ) ? '1' : $options['columns'] ) ) ),
		);
	}
}
