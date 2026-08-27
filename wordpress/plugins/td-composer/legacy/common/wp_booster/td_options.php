<?php
/**
 * Created by ra on 9/22/2016.
 */


class td_options {

	/**
	 * @var bool flag used to hook the shutdown action only once
	 */
	private static $is_shutdown_hooked = false;

	/**
	 * @var null keep a local copy of all the settings
	 */
	static $td_options = NULL;

    /**
     * @var null keep a local copy of the CSS generated from the theme panel
     */
    static $td_generated_css = NULL;

	/**
	 * get one td_option
     *
	 * @param $option_name
	 * @param string  $default_value - what you get if the option is empty or not set, default is EMPTY STRING ''
     * @param boolean $legacy
     *
	 * @return mixed string|$default_value
	 */
	static function get($option_name, $default_value = '', $legacy = false) {
		self::read_from_db();

        if ( TD_THEME_NAME == 'Newspaper' && !$legacy && $option_name == 'tds_user_compile_css' ) {
            return self::get_generated_css();
        }

		if ( !empty(self::$td_options[$option_name]) ) {
			return self::$td_options[$option_name];
		} else {
			if ( !empty($default_value) ) {
				return $default_value;
			} else {
				return '';
			}
		}
	}

    /**
     * Retrieves the CSS generated from the theme panel.
     *
     * @return string
     */
    static function get_generated_css() {
        self::read_from_db();

        return self::$td_generated_css;
    }

	/**
	 * Updates a string td_option
     *
	 * @param string  $option_name
	 * @param string  $new_value
	 * @param boolean $legacy
	 */
	static function update( $option_name, $new_value, $legacy = false ) {
        if ( TD_THEME_NAME == 'Newspaper' && !$legacy && $option_name == 'tds_user_compile_css' ) {
            self::update_generated_css($new_value);
            return;
        }

        //php 8.1 warning
        if ( self::$td_options === false ) {
            self::$td_options = [];
        }

		self::$td_options[$option_name] = $new_value;
		self::schedule_save();
	}

	/**
	 * Deletes an option.
     *
	 * @param $option_name string
	 */
	static function delete($option_name) {
        //php 8.1 warning
        if ( self::$td_options === false ) {
            self::$td_options = [];
        }

		unset(self::$td_options[$option_name]);
		self::schedule_save();
	}

    /**
     * Updates the theme generated CSS option.
     *
     * @param string $generated_css
     */
    static function update_generated_css( $generated_css ) {
        if ( TD_THEME_NAME != 'Newspaper' ) {
            return;
        }

        self::$td_generated_css = $generated_css;
        self::schedule_save();
    }

    /**
     * Read an array from the options
     *
     * @param $option_name
     * @param array $default_value - what you get if the setting is not set
     *
     * @return array
     */
	static function get_array($option_name, $default_value = array()) {

	    // check the default value to be array
	    if (!is_array($default_value)) {
	        td_util::error(__FILE__, 'td_options::get_array - $default_value is not an array!', $default_value);
	        die;
        }

        self::read_from_db();

	    // if we have a setting in the database and IT IS ARRAY
        if ( !empty(self::$td_options[$option_name]) && is_array(self::$td_options[$option_name]) ) {
            return self::$td_options[$option_name];
        }

        // log strings
        if (!empty(self::$td_options[$option_name]) && !is_array(self::$td_options[$option_name])) {
            td_log::log(__FILE__, __FUNCTION__, 'td_options::get_array - option is not an array!', self::$td_options[$option_name]);
        }

        // no setting or the setting is stored as a string
        return $default_value;
    }

    /**
     * Updates an array td_option
     *
     * @param $option_name string
     * @param $new_value array
     */
    static function update_array($option_name, $new_value) {

        // check the $new_value value to be array
//        if (!is_array($new_value)) {
//            td_util::error(__FILE__, 'td_options::get_array - $default_value is not an array!', $new_value);
//            die;
//        }

        self::$td_options[$option_name] = $new_value;
        self::schedule_save();
    }

	/**
	 * @param $optionName
	 * @param $newValue
	 * @deprecated - do not use, it's used as a hack in td composer and we will remove it soon
	 */
	static function update_temp($optionName, $newValue) {
		self::$td_options[$optionName] = $newValue;
	}

	/**
	 * This method is used to port the OLD global reading and updating to this new class so we don't have to refactor all the code at once.
	 *  - schedule_save() must be called after modifying the reference
	 * @return mixed
	 */
	static function &get_all_by_ref() {
		self::read_from_db();
		return self::$td_options;
	}

	/**
	 * Used to read all the options.
	 * @return mixed
	 */
	static function get_all() {
		self::read_from_db();
		return self::$td_options;
	}

	/**
	 * read the setting from db only once
	 */
	static private function read_from_db() {
		if ( is_null(self::$td_options) ) {
			self::$td_options = get_option(TD_THEME_OPTIONS_NAME);
		}

        if ( TD_THEME_NAME == 'Newspaper' && is_null(self::$td_generated_css) ) {
            self::$td_generated_css = get_option(TD_THEME_OPTIONS_NAME . '_generated_css', '');
        }
	}

	/**
	 * Schedules a save on the shutdown hook. It's public because it's also used with @see td_options::get_all_by_ref()
	 */
	static function schedule_save() {
		if ( self::$is_shutdown_hooked === false ) {
			add_action('shutdown', array(__CLASS__, 'on_shutdown_save_options'));
			self::$is_shutdown_hooked = true;
		}
	}

	/**
	 * @internal
	 * save the options hook
	 */
	static function on_shutdown_save_options() {
		update_option( TD_THEME_OPTIONS_NAME, self::$td_options );
        if ( TD_THEME_NAME == 'Newspaper' ) {
		    update_option( TD_THEME_OPTIONS_NAME . '_generated_css', self::$td_generated_css );
        }
	}

	static function save_panel_history($to_version = '') {

		$options_settings_id = TD_THEME_OPTIONS_NAME . '_settings';

		$option_settings_on = tagdiv_util::get_option($options_settings_id . '_disabled');
		if (empty($option_settings_on)) {

			$current_time = current_time( 'Y-m-d H:i:s' ) . (empty($to_version) ? '' : ' UPDATE ' . TD_THEME_VERSION . ' to ' . $to_version);

			$option_settings = get_option( $options_settings_id );
			if ( false === $option_settings || ! is_array( $option_settings ) ) {
				update_option( $options_settings_id, array(
					$current_time => self::$td_options
				) );
			} else {
				foreach ( $option_settings as $key => $option_setting ) {
					if ( $option_setting !== self::$td_options ) {
						$option_settings = array_merge(
							array(
								$current_time => self::$td_options
							),
							$option_settings
						);
					}
					break;
				}
                // default limit is 5 positions
                $limit = td_util::get_option('td_tp_backup_limit', 5);
                $option_settings = array_slice( $option_settings, 0, $limit );
				update_option( $options_settings_id, $option_settings );
			}
		}
	}

	/**
	 * used only by panel import settings
	 * @param $options
	 */
	static function save_all_options( $options ) {
		self::$td_options = $options;
	}

}
