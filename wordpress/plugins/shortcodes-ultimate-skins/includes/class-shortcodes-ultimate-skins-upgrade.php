<?php

/**
 * The class responsible for plugin upgrade procedures.
 *
 * @since        1.5.1
 * @package      Shortcodes_Ultimate_Skins
 * @subpackage   Shortcodes_Ultimate_Skins/includes
 */
final class Shortcodes_Ultimate_Skins_Upgrade {

	/**
	 * The path to the main plugin file.
	 *
	 * @since    1.5.1
	 * @access   private
	 * @var      string    $plugin_file   The path to the main plugin file.
	 */
	private $plugin_file;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.5.1
	 * @access   private
	 * @var      string    $current_version   The current version of the plugin.
	 */
	private $current_version;

	/**
	 * Name of the option with plugin version.
	 *
	 * @since    1.5.1
	 * @access   private
	 * @var      string    $version_option   Name of the option with plugin version.
	 */
	private $version_option;

	/**
	 * The previous saved version.
	 *
	 * @since    1.5.1
	 * @access   private
	 * @var      string    $saved_version   The previous saved version.
	 */
	private $saved_version;

	/**
	 * Define the functionality of the updater.
	 *
	 * @since   1.5.1
	 * @param string  $plugin_file    The path to the main plugin file.
	 * @param string  $plugin_version The current version of the plugin.
	 */
	public function __construct( $plugin_file, $plugin_version ) {

		$this->plugin_file     = $plugin_file;
		$this->current_version = $plugin_version;
		$this->version_option  = 'su_option_additional-skins_version';
		$this->saved_version   = get_option( $this->version_option, 0 );

	}

	/**
	 * Run upgrade procedures.
	 *
	 * @since  1.5.1
	 */
	public function upgrade() {

		if ( ! $this->is_version_changed() ) {
			return;
		}

		if ( $this->is_previous_version_less_than( '1.5.1' ) ) {
			$this->upgrade_to_1_5_1();
		}

		$this->update_version();

	}

	/**
	 * Conditional check if previous version of the plugin less than passed one.
	 *
	 * @since  1.5.1
	 * @access private
	 * @return boolean True if previous version of the plugin less than passed one, False otherwise.
	 */
	private function is_previous_version_less_than( $version ) {
		return version_compare( $this->saved_version, $version, '<' );
	}

	/**
	 * Conditional check if plugin was updated.
	 *
	 * @since  1.5.1
	 * @access private
	 * @return boolean True if plugin was updated, False otherwise.
	 */
	private function is_version_changed() {
		return $this->is_previous_version_less_than( $this->current_version );
	}

	/**
	 * Save current version number.
	 *
	 * @since  1.5.1
	 * @access private
	 */
	private function update_version() {
		update_option( $this->version_option, $this->current_version );
	}

	/**
	 * Upgrade to 1.5.1.
	 *
	 * 1. Add 'su_option_additional-skins_license' option.
	 *
	 * @access  private
	 * @since   1.5.1
	 */
	private function upgrade_to_1_5_1() {

		/**
		 * 1. Add 'su_option_additional-skins_license' option.
		 */
		if ( get_option( 'su_option_additional-skins_license', 0 ) === 0 ) {
			add_option( 'su_option_additional-skins_license', '', '', false );
		}

	}

}
