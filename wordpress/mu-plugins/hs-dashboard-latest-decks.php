<?php
/**
 * Dashboard widget with the latest public deck feed items.
 *
 * @package HS_Manacost
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_dashboard_setup',
	static function () {
		if (
			! function_exists( 'wp_add_dashboard_widget' ) ||
			! function_exists( 'get_current_screen' ) ||
			! function_exists( 'add_meta_box' ) ||
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered by the deck plugin.
			! current_user_can( 'edit_hs_decks' )
		) {
			return;
		}

		wp_add_dashboard_widget(
			'hs_dashboard_latest_decks_feed',
			'Последние колоды в ленте',
			'hs_dashboard_render_latest_decks_feed'
		);
	}
);

/**
 * Keep query results as post objects even if a filter changes query fields.
 *
 * @param array<int|WP_Post> $posts Query results.
 * @return WP_Post[]
 */
function hs_dashboard_deck_post_objects( array $posts ): array {
	$objects = array();
	foreach ( $posts as $post ) {
		if ( $post instanceof WP_Post ) {
			$objects[] = $post;
		}
	}
	return $objects;
}

/**
 * Return the current public feed decks, reusing the expensive visibility query.
 *
 * @return WP_Post[]
 */
function hs_dashboard_get_latest_decks_feed_posts(): array {
	$cache_key  = 'hs_dashboard_latest_deck_ids_v1';
	$cached_ids = get_transient( $cache_key );
	if ( is_array( $cached_ids ) && count( $cached_ids ) <= 10 ) {
		$ids = array();
		foreach ( $cached_ids as $id ) {
			if ( ! is_int( $id ) || $id <= 0 ) {
				$ids = null;
				break;
			}
			$ids[] = $id;
		}
		if ( is_array( $ids ) ) {
			if ( ! $ids ) {
				return array();
			}
			$cached_query = new WP_Query(
				array(
					'post_type'              => 'hs_deck',
					'post_status'            => 'publish',
					'post__in'               => $ids,
					'posts_per_page'         => count( $ids ),
					'orderby'                => 'post__in',
					'ignore_sticky_posts'    => true,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);
			return hs_dashboard_deck_post_objects( $cached_query->posts );
		}
	}

	$query = new WP_Query(
		array(
			'post_type'              => 'hs_deck',
			'post_status'            => 'publish',
			'posts_per_page'         => 10,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Exact feed flags; IDs are cached and invalidated.
			'meta_query'             => array(
				'relation' => 'AND',
				array(
					'relation' => 'OR',
					array(
						'key'     => '_hide_from_feed',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_hide_from_feed',
						'value'   => '1',
						'compare' => '!=',
					),
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => '_hs_deck_archived',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_hs_deck_archived',
						'value'   => '1',
						'compare' => '!=',
					),
				),
			),
		)
	);
	$decks = hs_dashboard_deck_post_objects( $query->posts );
	set_transient(
		$cache_key,
		array_map( static fn ( WP_Post $deck ): int => (int) $deck->ID, $decks ),
		5 * MINUTE_IN_SECONDS
	);
	return $decks;
}

/** Invalidate the shared ID list after a deck changes. */
function hs_dashboard_invalidate_latest_decks_cache(): void {
	delete_transient( 'hs_dashboard_latest_deck_ids_v1' );
}
add_action( 'save_post_hs_deck', 'hs_dashboard_invalidate_latest_decks_cache' );

/**
 * Invalidate when either feed visibility flag changes.
 *
 * @param int    $meta_id  Metadata row ID.
 * @param int    $post_id  Deck post ID.
 * @param string $meta_key Metadata key.
 */
function hs_dashboard_latest_decks_meta_changed( $meta_id, $post_id, $meta_key ): void {
	if (
		'hs_deck' === get_post_type( (int) $post_id ) &&
		in_array( $meta_key, array( '_hide_from_feed', '_hs_deck_archived' ), true )
	) {
		hs_dashboard_invalidate_latest_decks_cache();
	}
}
foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $meta_hook ) {
	add_action( $meta_hook, 'hs_dashboard_latest_decks_meta_changed', 10, 3 );
}

/**
 * Invalidate before deletion and after trash or restoration.
 *
 * @param int $post_id Deck post ID.
 */
function hs_dashboard_latest_decks_post_removed( $post_id ): void {
	if ( 'hs_deck' === get_post_type( (int) $post_id ) ) {
		hs_dashboard_invalidate_latest_decks_cache();
	}
}
foreach ( array( 'before_delete_post', 'trashed_post', 'untrashed_post' ) as $post_hook ) {
	add_action( $post_hook, 'hs_dashboard_latest_decks_post_removed' );
}

add_action(
	'post_updated',
	static function ( $post_id, $post_after, $post_before ): void {
		if ( 'hs_deck' === $post_before->post_type && 'hs_deck' !== $post_after->post_type ) {
			hs_dashboard_invalidate_latest_decks_cache();
		}
	},
	10,
	3
);

/** Render the shared deck list with per-user edit permissions. */
function hs_dashboard_render_latest_decks_feed(): void {
	$decks = hs_dashboard_get_latest_decks_feed_posts();

	if ( ! $decks ) {
		echo '<p>В ленте пока нет опубликованных колод.</p>';
		return;
	}

	echo '<style>
		#hs_dashboard_latest_decks_feed .hs-latest-decks-list {
			margin: 0;
		}
		#hs_dashboard_latest_decks_feed .hs-latest-decks-item {
			display: grid;
			grid-template-columns: 118px minmax(0, 1fr) auto;
			gap: 8px;
			align-items: center;
			margin: 0;
			padding: 6px 0;
			border-bottom: 1px solid #f0f0f1;
		}
		#hs_dashboard_latest_decks_feed .hs-latest-decks-item:last-child {
			border-bottom: 0;
		}
		#hs_dashboard_latest_decks_feed .hs-latest-decks-date {
			color: #646970;
			font-size: 12px;
			white-space: nowrap;
		}
		#hs_dashboard_latest_decks_feed .hs-latest-decks-title {
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}
		#hs_dashboard_latest_decks_feed .hs-latest-decks-actions {
			display: flex;
			gap: 8px;
			white-space: nowrap;
		}
		#hs_dashboard_latest_decks_feed .hs-latest-decks-footer {
			margin: 10px 0 0;
		}
		@media (max-width: 782px) {
			#hs_dashboard_latest_decks_feed .hs-latest-decks-item {
				grid-template-columns: 1fr;
				gap: 2px;
			}
			#hs_dashboard_latest_decks_feed .hs-latest-decks-actions {
				justify-content: flex-start;
			}
		}
	</style>';

	echo '<ul class="hs-latest-decks-list">';

	foreach ( $decks as $deck ) {
		$edit_link = get_edit_post_link( $deck->ID, '' );
		$view_link = get_permalink( $deck );
		$timestamp = get_post_time( 'U', false, $deck );
		$date      = wp_date( 'd.m.Y, H:i', is_numeric( $timestamp ) ? (int) $timestamp : null );
		$date      = is_string( $date ) ? $date : '';
		$title     = get_the_title( $deck );

		echo '<li class="hs-latest-decks-item">';
		echo '<span class="hs-latest-decks-date">' . esc_html( $date ) . '</span>';

		if ( $edit_link && current_user_can( 'edit_post', $deck->ID ) ) {
			echo '<a class="hs-latest-decks-title" href="' . esc_url( $edit_link ) . '">' . esc_html( $title ) . '</a>';
		} else {
			echo '<span class="hs-latest-decks-title">' . esc_html( $title ) . '</span>';
		}

		echo '<span class="hs-latest-decks-actions">';

		if ( $view_link ) {
			echo '<a href="' . esc_url( $view_link ) . '" target="_blank" rel="noopener noreferrer">Открыть</a>';
		}

		echo '</span>';
		echo '</li>';
	}

	echo '</ul>';

	wp_reset_postdata();

	echo '<p class="hs-latest-decks-footer"><a href="' . esc_url( admin_url( 'edit.php?post_type=hs_deck' ) ) . '">Все колоды</a></p>';
}
