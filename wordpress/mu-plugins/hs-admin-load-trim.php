<?php
/**
 * Small wp-admin load trims.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'heartbeat_settings',
	static function ( $settings ) {
		if ( is_admin() ) {
			$settings['interval'] = max( (int) ( $settings['interval'] ?? 15 ), 60 );
		}

		return $settings;
	}
);

foreach (
	[
		'aioseo_show_seo_setup',
		'aioseo_show_seo_checklist',
		'aioseo_show_seo_overview',
		'aioseo_show_seo_news',
	] as $aioseo_dashboard_filter
) {
	add_filter( $aioseo_dashboard_filter, '__return_false', 99 );
}

add_action(
	'wp_dashboard_setup',
	static function () {
		foreach (
			[
				'dashboard_primary',
				'dashboard_quick_press',
				'wordfence_activity_report_widget',
				'aioseo-seo-setup',
				'aioseo-seo-checklist',
				'aioseo-overview',
				'aioseo-rss-feed',
			] as $widget_id
		) {
			foreach ( [ 'normal', 'side', 'advanced', 'column3', 'column4' ] as $context ) {
				remove_meta_box( $widget_id, 'dashboard', $context );
			}
		}
	},
	99
);

add_filter(
	'rocket_metabox_options_post_types',
	static function () {
		return [];
	},
	99
);

add_action(
	'add_meta_boxes',
	static function () {
		global $wp_filter;

		if ( empty( $wp_filter['add_meta_boxes'] ) || ! is_object( $wp_filter['add_meta_boxes'] ) ) {
			return;
		}

		foreach ( $wp_filter['add_meta_boxes']->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = $callback['function'] ?? null;

				if (
					is_array( $function ) &&
					isset( $function[0], $function[1] ) &&
					is_object( $function[0] ) &&
					'AIOSEO\\Plugin\\Common\\Admin\\WritingAssistant' === get_class( $function[0] ) &&
					'addMetabox' === $function[1]
				) {
					remove_action( 'add_meta_boxes', $function, $priority );
				}
			}
		}
	},
	0
);

add_action(
	'add_meta_boxes',
	static function () {
		$post_types = get_post_types( [ 'public' => true ], 'names' );

		unset( $post_types['attachment'] );

		foreach ( $post_types as $post_type ) {
			remove_meta_box( 'rocket_post_exclude', $post_type, 'side' );
			remove_meta_box( 'aioseo-writing-assistant-metabox', $post_type, 'normal' );
		}
	},
	99
);

add_action(
	'admin_head',
	static function () {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! in_array( $screen->base, [ 'dashboard', 'edit', 'post' ], true ) ) {
			return;
		}
		?>
		<style>
			.aioseo-notice:not(.notice-error):not(.notice-warning),
			.aioseo-admin-notice:not(.notice-error):not(.notice-warning),
			.aioseo-app-notice:not(.notice-error):not(.notice-warning),
			.wpr-notice:not(.notice-error):not(.notice-warning),
			.wp-rocket-notice:not(.notice-error):not(.notice-warning) {
				display: none !important;
			}
		</style>
		<?php
	}
);
