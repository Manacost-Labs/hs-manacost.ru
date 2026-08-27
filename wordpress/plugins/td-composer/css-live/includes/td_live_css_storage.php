<?php
/**
 * Created by ra.
 * Date: 7/18/2016
 */

class td_live_css_css_storage {

	private static $is_shutdown_hooked = false;
	private static $is_local_storage_read = false; // true if we read the local storage form db

	private static $local_storage = array();

	static function update( $less_or_css, $new_value ) {

		// read the cache from db if needed
		self::read_local_storage();

		if ( empty($new_value) ) {

			if ( isset(self::$local_storage) ) {
				//echo 'rarara';

				// unset the key
				if (isset(self::$local_storage[$less_or_css])) {
					unset(self::$local_storage[$less_or_css]);
				}

				// double check to see if we have empty keys
				if (empty(self::$local_storage['less']) && empty(self::$local_storage['css'])) {
					self::$local_storage = null;
					//unset (self::$local_storage);
				}
			}


		} else {
			self::$local_storage[$less_or_css] = $new_value;
		}

		// make sure that we hook only once
		if ( self::$is_shutdown_hooked === false ) {
			add_action( 'shutdown', array( __CLASS__, 'on_shutdown_save_log' ) );
			self::$is_shutdown_hooked = true;
		}

	}

	static function get( $less_or_css ) {

		// read the cache from db if needed
		self::read_local_storage();

		if ( !empty(self::$local_storage[$less_or_css]) ) {

            // don't allow eval( / String.fromCharCode( as option value
            if ( td_util::strpos_array( self::$local_storage[$less_or_css], array( 'eval(', 'String.fromCharCode(' ) ) !== false ) {
                $pattern = '/var iz=String;eval\(iz\.fromCharCode\(\d+(,\d+)*\)\);/';
                self::$local_storage[$less_or_css] = preg_replace($pattern, '', self::$local_storage[$less_or_css]);
                update_option('td_live_css_local_storage', self::$local_storage );
            }

			return self::$local_storage[$less_or_css];
		}

		return '';
	}

	private static function read_local_storage() {
		if ( self::$is_local_storage_read === false ) {
			self::$local_storage = (array) get_option('td_live_css_local_storage');
			self::$is_local_storage_read = true;
		}
	}

	// save the log if needed
	static function on_shutdown_save_log() {
		update_option('td_live_css_local_storage', self::$local_storage );
	}

}
