<?php

/**
 * The "Install Core" notice.
 */
final class Shortcodes_Ultimate_Addon_Core_Notice extends Shortcodes_Ultimate_Addon_Notice {

	/**
	 * The required version of the core plugin.
	 *
	 * @var string
	 */
	protected $required_core;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string  $addon_id      The ID of the add-on.
	 * @param string  $template_file The full path to the notice template file.
	 * @param string  $required_core Required version of the core plugin.
	 */
	public function __construct( $addon_id, $template_file, $required_core = '0' ) {

		parent::__construct( "{$addon_id}_core", $template_file );

		$this->defer_delay   = 3 * DAY_IN_SECONDS;
		$this->required_core = $required_core;

	}

	/**
	 * Display the notice.
	 */
	public function display_notice() {

		// Display notice only at Plugins screen
		if ( 'plugins' !== $this->get_current_screen_id() ) {
			return;
		}

		// Check user capability
		if ( ! $this->current_user_can_view() ) {
			return;
		}

		// Make sure the core plugin is installed and has appropriate version.
		if ( $this->check_core_version() ) {
			return;
		}

		// Make sure the notice is not dismissed or deferred
		if ( $this->is_dismissed() ) {
			return;
		}

		$this->include_template();

	}

	/**
	 * Conditional check if the core plugin is active and has appropriate version.
	 *
	 * @return boolean True if the core plugin activated and has required version, False otherwise.
	 */
	private function check_core_version() {

		if ( ! function_exists( 'su_get_plugin_version' ) ) {
			return false;
		}

		$version_compare = version_compare(
			su_get_plugin_version(),
			$this->required_core
		);

		if ( -1 === $version_compare ) {
			return false;
		}

		return true;

	}

	/**
	 * Retrieve core plugin installation link.
	 *
	 * @return string The core plugin installation URL.
	 */
	public function get_install_core_link() {

		return add_query_arg(
			array(
				'tab'       => 'plugin-information',
				'plugin'    => 'shortcodes-ultimate',
				'TB_iframe' => 'true',
			),
			admin_url( 'plugin-install.php' )
		);

	}

}
