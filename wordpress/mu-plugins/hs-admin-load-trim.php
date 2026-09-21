<?php
/**
 * Small wp-admin load trims.
 *
 * @package HsManacost
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove a registered object callback without booting the owning plugin again.
 *
 * @param string $hook_name  WordPress hook name.
 * @param string $class_name Fully qualified callback class name.
 * @param string $method     Callback method name.
 * @return void
 */
function hs_admin_load_trim_remove_object_callback( $hook_name, $class_name, $method ) {
	global $wp_filter;

	if ( empty( $wp_filter[ $hook_name ] ) || ! ( $wp_filter[ $hook_name ] instanceof WP_Hook ) ) {
		return;
	}

	foreach ( $wp_filter[ $hook_name ]->callbacks as $priority => $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$function = $callback['function'] ?? null;

			if (
				is_array( $function ) &&
				isset( $function[0], $function[1] ) &&
				is_object( $function[0] ) &&
				get_class( $function[0] ) === $class_name &&
				$method === $function[1]
			) {
				remove_action( $hook_name, $function, $priority );
			}
		}
	}
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
	array(
		'aioseo_show_seo_setup',
		'aioseo_show_seo_checklist',
		'aioseo_show_seo_overview',
		'aioseo_show_seo_news',
	) as $aioseo_dashboard_filter
) {
	add_filter( $aioseo_dashboard_filter, '__return_false', 99 );
}

// The per-row Rocket Insights column performs two database lookups for every post.
add_filter( 'rocket_insights_excluded_post_type', '__return_true', 99 );

// AIOSEO has no public switch for its post-list column; keep its editor features intact.
add_action(
	'current_screen',
	static function () {
		hs_admin_load_trim_remove_object_callback(
			'current_screen',
			'AIOSEO\\Plugin\\Common\\Standalone\\DetailsColumn',
			'registerColumnHooks'
		);
	},
	0
);

add_action(
	'wp_dashboard_setup',
	static function () {
		foreach (
			array(
				'dashboard_primary',
				'dashboard_quick_press',
				'wordfence_activity_report_widget',
				'aioseo-seo-setup',
				'aioseo-seo-checklist',
				'aioseo-overview',
				'aioseo-rss-feed',
			) as $widget_id
		) {
			foreach ( array( 'normal', 'side', 'advanced', 'column3', 'column4' ) as $context ) {
				remove_meta_box( $widget_id, 'dashboard', $context );
			}
		}
	},
	99
);

add_filter(
	'rocket_metabox_options_post_types',
	static function () {
		return array();
	},
	99
);

add_action(
	'add_meta_boxes',
	static function () {
		hs_admin_load_trim_remove_object_callback(
			'add_meta_boxes',
			'AIOSEO\\Plugin\\Common\\Admin\\WritingAssistant',
			'addMetabox'
		);
	},
	0
);

add_action(
	'add_meta_boxes',
	static function () {
		$post_types = get_post_types( array( 'public' => true ), 'names' );

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

		if ( ! $screen || ! in_array( $screen->base, array( 'dashboard', 'edit', 'post' ), true ) ) {
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
