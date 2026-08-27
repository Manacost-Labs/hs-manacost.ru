<?php

class Shortcodes_Ultimate_Addon_Updater {

	private $plugin_basename;
	private $license_option;

	public function __construct( $plugin_file, $addon_id ) {

		$this->plugin_basename = plugin_basename( $plugin_file );
		$this->license_option  = "su_option_{$addon_id}_license";

	}

	/**
	 * This tweak prevents HTTP errors on sites with many plugins
	 */
	public function disable_puc_plugins_arg( $args ) {

		if ( isset( $args['plugins'] ) ) {
			$args['plugins'] = '';
		}

		return $args;

	}

	public function validate_license_before_updating( $update ) {

		if (
			! $this->is_license_activated()
			&& isset( $update->download_url )
		) {
			$update->download_url = '';
		}

		return $update;

	}

	public function activate_key_plugin_action_link( $actions, $plugin_basename ) {

		if (
			$this->plugin_basename === $plugin_basename &&
			! $this->is_license_activated()
		) {

			$url = sprintf(
				'%s#%s',
				add_query_arg(
					'page',
					'shortcodes-ultimate-settings',
					admin_url( 'admin.php' )
				),
				$this->license_option
			);

			$actions['activate_license_key'] = sprintf(
				'<a href="%s" style="color:darkorange;font-weight:bold">%s</a>',
				esc_attr( $url ),
				esc_html__( 'Activate License Key', 'shortcodes-ultimate-skins' )
			);

		}

		return $actions;

	}

	private function is_license_activated() {

		$key = get_option( $this->license_option, '' );

		return ! empty( $key );

	}

}
