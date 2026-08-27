<?php

class td_transients_manager {

	function __construct() {

		// wp cron action to remove invalid/expired transients
		if ( !wp_next_scheduled( 'td_clear_transients' ) ) {
			wp_schedule_event( time(), 'daily', 'td_clear_transients' );
		}

		// wp cron action to remove invalid/expired transients
		add_action( 'td_clear_transients', array( __CLASS__, 'td_clear_transients' ) );

		// on switch theme remove wp cron task
		add_action( 'switch_theme', array( __CLASS__, 'on_switch_theme_remove_cron_task' ) );

	}

	/**
	 * retrieves td_query blocks cache transients
	 */
	static function get_transients( $expired = false ) {
		global $wpdb;

		if ( $expired ) {
			$prepared = $wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value + 0 < %d",
				$wpdb->esc_like( '_transient_timeout_td_query_' ) . '%',
				time()
			);
			$transients_results = $wpdb->get_col( $prepared );
		} else {
			$prepared = $wpdb->prepare(
				"SELECT * FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s ",
				"_transient_td_query_%",
				"_transient_timeout_td_query_%"
			);
			$transients_results = $wpdb->query( $prepared );
		}

		return $transients_results;

	}

	/**
	 * deletes all theme related transients
	 */
	static function delete_transients() {
		global $wpdb;

		$transient_groups = [
			'td_query_%',
			'tdb_form_content_pages'
		];

		$where_clause = [];

		foreach ( $transient_groups as $transient_group ) {
			if ( false !== strpos( $transient_group, '%' ) ) {
				$where_clause[] = $wpdb->prepare(
					' option_name LIKE %s OR option_name LIKE %s ',
					"_transient_$transient_group",
					"_transient_timeout_$transient_group"
				);
			} else {
				$where_clause[] = $wpdb->prepare(
					' option_name = %s OR option_name = %s ',
					"_transient_$transient_group",
					"_transient_timeout_$transient_group"
				);
			}
		}

		$wpdb->query( "DELETE FROM $wpdb->options WHERE " . implode( ' OR ', $where_clause ) );

	}

	/**
	 * deletes all theme related expired transients
	 */
	static function delete_expired_transients() {
		global $wpdb;

		// query
		$esc_time = $wpdb->esc_like( '_transient_timeout_td_query_' ) . '%';
		$prepared = $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} where option_name LIKE %s AND option_value + 0 < %d", $esc_time, time() );
		$expired_transients = $wpdb->get_col( $prepared );

		// bulk delete, bail if empty or error
		if ( empty($expired_transients) || is_wp_error($expired_transients) ) {
			return false;
		}

		// loop through transients, and delete them
		foreach ( $expired_transients as $transient ) {

			// strip prefix from name
			$name = str_replace( '_transient_timeout_', '', $transient );

			// delete
			delete_transient($name);

		}

		return true;

	}

	/**
	 * wp cron action to remove invalid/expired transients
	 */
	static function td_clear_transients() {
		self::delete_expired_transients(); // clear expired/invalid transients
	}

	/**
	 * remove wp cron task
	 */
	static function on_switch_theme_remove_cron_task() {
		wp_clear_scheduled_hook( 'td_clear_transients' );
	}

}

new td_transients_manager();