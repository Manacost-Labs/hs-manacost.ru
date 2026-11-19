<?php
/**
 * Plugin Name: HS Related Tag Tabs
 * Description: Adds tabs for the article's tags to the Newspaper related-articles block.
 * Version: 1.0.0
 * Author: Manacost
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Extends the tagDiv related-articles block ("Это может быть интересно" /
 * "Еще от автора") with one tab per shared article tag, without editing tagDiv.
 */
final class HS_Related_Tag_Tabs {
	/** Prefix of the td_filter_value sent by a tag tab. */
	public const FILTER_PREFIX = 'hs_related_tag_';

	/** Maximum number of tag tabs after the two built-in tabs. */
	public const MAX_TABS = 2;

	private const HANDLE  = 'hs-related-tag-tabs';
	private const VERSION = '1.0.0';

	/** Registers the frontend asset and the tagDiv AJAX query override. */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_script' ), 30 );
		add_filter( 'td_data_source_blocks_query_args', array( __CLASS__, 'filter_query_args' ), 10, 2 );
	}

	/** Enqueues the tab script with the current article's tag tabs. */
	public static function enqueue_script(): void {
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		$tabs = self::tabs_for_post( (int) get_queried_object_id() );
		if ( array() === $tabs ) {
			return;
		}

		wp_enqueue_script(
			self::HANDLE,
			plugin_dir_url( __FILE__ ) . 'hs-related-tag-tabs/related-tag-tabs.js',
			array(),
			self::VERSION,
			array( 'in_footer' => true )
		);
		wp_add_inline_script(
			self::HANDLE,
			'window.hsRelatedTagTabs=' . wp_json_encode( array( 'tabs' => $tabs ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ) . ';',
			'before'
		);
	}

	/**
	 * Returns tabs for tags shared with at least one other article, most specific first.
	 *
	 * @param int $post_id Current article ID.
	 * @return list<array{value: string, label: string}>
	 */
	public static function tabs_for_post( int $post_id ): array {
		$terms = $post_id > 0 ? get_the_terms( $post_id, 'post_tag' ) : false;
		if ( ! is_array( $terms ) ) {
			return array();
		}

		$terms = array_filter(
			$terms,
			static fn( WP_Term $term ): bool => (int) $term->count > 1
		);
		usort(
			$terms,
			static fn( WP_Term $a, WP_Term $b ): int => array( (int) $a->count, $a->name ) <=> array( (int) $b->count, $b->name )
		);

		$tabs = array();
		foreach ( array_slice( $terms, 0, self::MAX_TABS ) as $term ) {
			$tabs[] = array(
				'value' => self::FILTER_PREFIX . (int) $term->term_id,
				'label' => wp_specialchars_decode( (string) $term->name, ENT_QUOTES ),
			);
		}

		return $tabs;
	}

	/**
	 * Switches the related-posts AJAX query from "same categories" to the requested tag.
	 *
	 * @param mixed $args WP_Query arguments built by tagDiv.
	 * @param mixed $atts Block attributes sent back by the tagDiv block script.
	 * @return mixed
	 */
	public static function filter_query_args( $args, $atts ) {
		if ( ! is_array( $args ) || ! is_array( $atts ) || ! wp_doing_ajax() ) {
			return $args;
		}

		if ( 'td_custom_related' !== ( $atts['td_ajax_filter_type'] ?? '' ) ) {
			return $args;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- td_ajax::on_ajax_block() verifies the td-block nonce before this query runs; the values only select public posts.
		$action       = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';
		$block_type   = isset( $_POST['block_type'] ) ? sanitize_key( wp_unslash( $_POST['block_type'] ) ) : '';
		$filter_value = isset( $_POST['td_filter_value'] ) ? sanitize_key( wp_unslash( $_POST['td_filter_value'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( 'td_ajax_block' !== $action || 'td_block_related_posts' !== $block_type ) {
			return $args;
		}

		$tag_id = self::tag_id_from_filter_value( $filter_value );
		if ( 0 === $tag_id ) {
			return $args;
		}

		// tagDiv's live filter already excludes the current article via post__not_in.
		unset( $args['category__in'] );
		$args['tag__in'] = array( $tag_id );

		return $args;
	}

	/**
	 * Parses "hs_related_tag_<id>" into a positive tag ID.
	 *
	 * @param string $value Sanitized td_filter_value.
	 * @return int Tag ID, or 0 when the value is not a tag tab.
	 */
	private static function tag_id_from_filter_value( string $value ): int {
		if ( 1 !== preg_match( '/^' . self::FILTER_PREFIX . '([1-9][0-9]{0,9})$/', $value, $matches ) ) {
			return 0;
		}

		return (int) $matches[1];
	}
}

HS_Related_Tag_Tabs::boot();
