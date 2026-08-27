<?php
/**
 * Dashboard widget with the latest public deck feed items.
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

function hs_dashboard_render_latest_decks_feed() {
	$query = new WP_Query(
		[
			'post_type'              => 'hs_deck',
			'post_status'            => 'publish',
			'posts_per_page'         => 10,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => [
				'relation' => 'AND',
				[
					'relation' => 'OR',
					[
						'key'     => '_hide_from_feed',
						'compare' => 'NOT EXISTS',
					],
					[
						'key'     => '_hide_from_feed',
						'value'   => '1',
						'compare' => '!=',
					],
				],
				[
					'relation' => 'OR',
					[
						'key'     => '_hs_deck_archived',
						'compare' => 'NOT EXISTS',
					],
					[
						'key'     => '_hs_deck_archived',
						'value'   => '1',
						'compare' => '!=',
					],
				],
			],
		]
	);

	if ( ! $query->have_posts() ) {
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

	foreach ( $query->posts as $deck ) {
		$edit_link = get_edit_post_link( $deck->ID, '' );
		$view_link = get_permalink( $deck );
		$date      = wp_date( 'd.m.Y, H:i', get_post_time( 'U', false, $deck ) );
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
