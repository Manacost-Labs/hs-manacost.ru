<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since        1.5.7
 * @package      Shortcodes_Ultimate_Extra
 * @subpackage   Shortcodes_Ultimate_Extra/includes
 */
class Shortcodes_Ultimate_Extra_Activator {

	/**
	 * Array with plugin's default options.
	 *
	 * @since  1.5.7
	 * @access private
	 * @var array Array with plugin's default options.
	 */
	private static $defaults;

	/**
	 * Plugin activation.
	 *
	 * @since    1.5.7
	 */
	public static function activate() {

		self::$defaults = array(
			array(
				'name'     => 'su_option_extra-shortcodes_license',
				'value'    => '',
				'autoload' => false,
			),
		);

		self::setup_defaults();

	}

	/**
	 * Setup plugin's default settings.
	 *
	 * @access  private
	 * @since   1.5.7
	 */
	private static function setup_defaults() {

		foreach ( self::$defaults as $option ) {

			if ( get_option( $option['name'], 0 ) === 0 ) {
				add_option( $option['name'], $option['value'], '', $option['autoload'] );
			}

		}

	}

}
