<?php



class tdb_util {

    /**
     * debug kill that prints the calling function + class
     * @param string $message
     * @param array $debug_backtrace result of debug_backtrace()
     * @param string $get_called_class result of $get_called_class
     */
    static function kill ($message, $debug_backtrace = array(), $get_called_class = '') {


        echo $get_called_class . ' - : ' . $message . "\n";

        if (isset($debug_backtrace[0])) {

            if (isset($debug_backtrace[0]['file']) && isset($debug_backtrace[0]['line']) ) {
                echo 'File: ' . $debug_backtrace[0]['file'] . "\n";
                echo 'Line: ' . $debug_backtrace[0]['line'] . "\n";
            } else {
                print_r($debug_backtrace[0]);
            }


        } else {
            echo 'No debug_backtrace :( ';
        }
    }

    static function get_get_val($_get_name) {
        if (isset($_GET[$_get_name])) {
            return esc_html($_GET[$_get_name]); // xss - no html in get
        }

        return false;
    }

    static function get_shortcode_att( $content, $shortcode, $att ) {

        // parse content shortcode
        preg_match_all( '/\[(.*?)\]/', $content, $matches );

        // search for the shortcode
        if ( !empty( $matches[0] ) and is_array( $matches[0] ) ) {
            foreach ( $matches[0] as $match ) {
                if ( strpos( $match, $shortcode ) !== false ) {
                    $shortcode = $match;
                }
            }
        }

        // get the shortcode att if we have a shortcode match
        if ( !empty( $shortcode ) ) {
            $shortcode = str_replace( array( '[',']' ), '', $shortcode );
            $shortcode_atts = shortcode_parse_atts( $shortcode );

            if ( isset( $shortcode_atts[$att] ) ){
                return $shortcode_atts[$att];
            }
        }

        return '';
    }

    static function get_shortcode_atts( $content, $shortcode ) {

        // parse content shortcode
        preg_match_all( '/\[(.*?)\]/', $content, $matches );

        $shortcode_atts = array();

        // search for the shortcode
        if ( !empty( $matches[0] ) and is_array( $matches[0] ) ) {
            foreach ( $matches[0] as $match ) {
                if ( strpos( $match, $shortcode ) !== false ) {

                	$shortcode = $match;

                    // get the shortcode att if we have a shortcode match
			        if ( !empty( $shortcode ) ) {
			            $shortcode = str_replace( array( '[',']' ), '', $shortcode );
			            $parsed_shortcode_atts = shortcode_parse_atts( $shortcode );

			            foreach ( $parsed_shortcode_atts as $att => $val ) {
			            	if ( ! empty( $parsed_shortcode_atts[$att] ) ){
			                	$shortcode_atts[$att] = $parsed_shortcode_atts[$att];
				            }
			            }
			        }
                    break;
                }
            }
        }

        return $shortcode_atts;
    }

    static function get_shortcode_content( $content, $shortcode ) {

        // parse content shortcode
        preg_match_all( '/\[(.*?)\]/', $content, $matches );

        // search for the shortcode
        if ( !empty( $matches[0] ) and is_array( $matches[0] ) ) {
            foreach ( $matches[0] as $match ) {
                if ( strpos( $match, $shortcode ) !== false ) {
                	return $match;
                }
            }
        }

        return '';
    }

    static function get_shortcode( $content, $shortcode ) {

        // parse content shortcode
        preg_match_all( '/\[(.*?)\]/', $content, $matches );

        // search for the shortcode
        if ( !empty( $matches[0] ) and is_array( $matches[0] ) ) {
            foreach ( $matches[0] as $match ) {
                if ( strpos( $match, $shortcode ) !== false ) {
	                return true;
                }
            }
        }

        return false;
    }

    static function get_api_url($ext = 'api') {
    	$api_url = '';

	    if ( TDB_CLOUD_LOCATION === 'local' ) {
		    $api_url = 'http://' . $_SERVER['SERVER_ADDR'] . '/td_cloud/' . $ext;
		    //$api_url = 'http://localhost/td_cloud/' . $ext;
	    } else {
	    	$cloud = get_option('tdb_work_cloud');
	    	if ( empty($cloud) || 'false' === $cloud ) {
	    	    $api_url = 'https://cloud.tagdiv.com/' . $ext;
		    } else {
	    	    $api_url = 'https://work-cloud.tagdiv.com/' . $ext;
		    }
	    }

	    return $api_url;

    }

	static function enqueue_js_files_array($js_files_array, $dependency_array) {
		$last_js_file_id = '';
		foreach ($js_files_array as $js_file_id => $js_file) {
			if ($last_js_file_id == '') {
				wp_enqueue_script($js_file_id, TDB_URL . $js_file, $dependency_array, TD_CLOUD_LIBRARY, true); //first, load it with jQuery dependency
			} else {
				wp_enqueue_script($js_file_id, TDB_URL . $js_file, array($last_js_file_id), TD_CLOUD_LIBRARY, true);  //not first - load with the last file dependency
			}
			$last_js_file_id = $js_file_id;
		}
	}

	static function check_in_range( $int, $min, $max ){
		return ( $int >= $min && $int <= $max );
	}

	static function change_key($array, $old_key, $new_key) {

		if( !array_key_exists($old_key, $array) )
			return $array;

		$keys = array_keys($array);
		$keys[array_search($old_key, $keys)] = $new_key;

		return array_combine($keys, $array);
	}

	static function parse_template_shortcodes( &$content = null, $options = [] ) {

		$new_content = '';

		if ( preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as &$shortcode ) {
				//var_dump($shortcode[ 2 ]);

				$attributes = shortcode_parse_atts( $shortcode[ 3 ] );

				//var_dump($matches);
				//var_dump($attributes);

				$wrapper_shortcode = false;

				if (strpos( $content, "[/" . $shortcode[ 2 ] . "]") > 0 ) {
					$wrapper_shortcode = true;
				}


				if ( ! empty( $shortcode[5] ) ) {
					$new_content .= '[' . $shortcode[2];

					if (is_array($attributes)) {
						self::parse_template_attr( $new_content, $shortcode[2], $attributes, $options );
					}

					$new_content .= ']';

					$new_content .= self::parse_template_shortcodes($shortcode[5], $options );

					$new_content .= '[/' . $shortcode[2] . ']';

				} else {

					$new_content .= '[' . $shortcode[2];

					if (is_array($attributes)) {
						self::parse_template_attr( $new_content, $shortcode[ 2 ], $attributes, $options );
					}

					$new_content .= ']';
					if ( $wrapper_shortcode ) {
						$new_content .= '[/' . $shortcode[ 2 ] . ']';
					}
				}
			}
			return $new_content;
		} else {
			return $content;
		}
	}

	private static function parse_template_attr( &$content, $shortcode, $attributes, $options = []) {

		// filter registered shortcodes to find properties which have specific types
		$filtered_shortcodes = [];

		foreach ( td_api_block::get_all() as $block_settings_key => $block_settings_value ) {
            if ( !empty( $block_settings_value['params'] ) ) {
	            foreach( $block_settings_value['params'] as $param ) {
		            if ( isset($param['type']) && 'attach_image' === $param['type'] ) {
		            	if ( empty( $filtered_shortcodes[$block_settings_key] ) ) {
		            		$filtered_shortcodes[$block_settings_key] = [$param['param_name']];
			            } else {
		            	    $filtered_shortcodes[$block_settings_key][] = $param['param_name'];
			            }
		            }
	            }
            }
		}


		foreach ( $attributes as $key => $val ) {
			if ( !empty( $options ) && array_key_exists('new_images', $options ) ) {

				switch ($key) {
					case 'tdc_css':
						$decoded_val = base64_decode($val);

						foreach ( $options['new_images'] as $img ) {
							$decoded_val = str_replace( substr( $img['uid'], 8 ), 'url(\"' . $img['url'] . '\")', $decoded_val );
						}

		                $val = base64_encode( $decoded_val );

						break;
				}

				if ( !empty( $filtered_shortcodes[$shortcode] ) ) {
					foreach ( $filtered_shortcodes[$shortcode] as $param_name ) {
						if ( $param_name === $key ) {
							foreach ( $options['new_images'] as $img ) {
								$img_val = substr( $img['uid'], 8 );
								if ( $val === $img_val ) {
									$val = $img['attachment_id'];
									break;
								}
							}
						}
					}
				}
			}

			$content .= " $key=\"$val\"";
		}
	}

	static function clean( $var ) {
		if ( is_array( $var ) ) {
			return array_map( array( __CLASS__, 'clean' ), $var );
		} else {
			return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
		}
	}

	static function get_icon_att( $icon_class ) {
        $svg_list = td_global::$svg_theme_font_list;

        if( array_key_exists( $icon_class, $svg_list ) ) {
            return $svg_list[$icon_class];
        }

        return $icon_class;
    }

	/**
	 * determines if a module is a tdb cpt module
	 * @param $module_id
	 * @param $and_exist
	 * @return bool
	 */
	static function is_tdb_module( $module_id, $and_exist = false ) {

		if ( substr( $module_id, 0, strlen('tdb_module') ) == 'tdb_module' ) {

			if ( $and_exist ) {

				$id = self::tdb_get_module_id($module_id);

				if ( !empty($id) ) {
					$posts = get_posts(
						array(
							'p' => self::tdb_get_module_id($module_id),
							'post_type' => 'tdb_templates',
							'post_status' => 'publish',
							'numberposts' => -1,
							'meta_key' => 'tdb_template_type',
							'meta_value' => 'module',
						)
					);

					return count($posts) > 0;
				}

				return false;

			}

			return true;

		}

		return false;
	}

	/**
	 * extract the module id from a module ID
	 * @param $module_id
	 * @return int
	 */
	static function tdb_get_module_id( $module_id ) {
		return (int) str_replace('tdb_module_', '', $module_id );
	}

	/**
	 * used to bind the $tdb_form_content_pages transient to the 'user_has_cap' callback function
	 *
	 * @param $tdb_form_content_pages - an array that contains the ids of the pages that use the tdb_form_content shortcode
	 *
	 * @return Closure - the 'user_has_cap' callback function
	 */
	static function tdb_create_user_has_cap_filter_callback( $tdb_form_content_pages ) {
		return function( $allcaps, $caps, $args, $user ) use ( $tdb_form_content_pages ) {
			return self::on_user_has_cap( $allcaps, $caps, $args, $user, $tdb_form_content_pages );
		};
	}

	/**
	 * add capabilities via user_has_cap
	 * the 'user_has_cap' callback function
	 *
	 * @param $allcaps
	 * @param $caps
	 * @param $args
	 * @param $user
	 * @param $tdb_form_content_pages
	 *
	 * @return mixed
	 */
	static function on_user_has_cap( $allcaps, $caps, $args, $user, $tdb_form_content_pages ) {

		// if the current_user_can('user_has_cap') request is for an 'upload_files' capability
		if ( isset($args[0]) && $args[0] === 'upload_files' ) {

			global $post;
			$post_id = $post ? $post->ID : ( $_REQUEST['post_id'] ?? '' );

			if ( !empty($post_id) && in_array( $post_id, $tdb_form_content_pages ) ) {
				$allcaps['upload_files'] = true;
			}

		}

		// if the current_user_can('user_has_cap') request is for an 'edit_post' capability
		if ( isset($args[0]) && $args[0] === 'edit_post' ) {

			// set the requested capability check > object ID
			$req_obj_id = $args[2] ?? '';

			// if the request is made specifically for a page that holds the tdb_form_content shortcode
			if ( $req_obj_id && in_array( $req_obj_id, $tdb_form_content_pages ) ) {

				// get the requested post obj
				$page = get_post($req_obj_id);

				// allow only if it's a page
				if ( $page && $page->post_type === 'page' ) {

					// grant edit_others_pages & edit_published_pages
					$allcaps['edit_others_pages'] = true;
					$allcaps['edit_published_pages'] = true;

				}

			}

		}

		return $allcaps;

	}


	static function get_all_acf_fields() {

		$acf_fields = array();


		if( class_exists( 'ACF' ) ) {

			$acf_field_groups = acf_get_field_groups();

			if ( !empty( $acf_field_groups ) ) {
                foreach ( $acf_field_groups as $acf_field_group ) {
                    $acf_fields = array_merge(
						$acf_fields,
						self::get_acf_fields_from_group($acf_field_group['ID'])
					);
                }
            }

		}


		return $acf_fields;

	}

	static function get_acf_fields_from_group( $group_id ) {

		$acf_group_fields = array();


		if( class_exists( 'ACF' ) ) {

			$acf_fields = acf_get_raw_fields( $group_id );

			foreach ( $acf_fields as $acf_field ) {
				if ( $acf_field['type'] == 'group' ) {
					$acf_group_fields = array_merge(
						$acf_group_fields,
						self::get_acf_fields_from_group($acf_field['ID'])
					);
				} else {
					$acf_group_fields[] = $acf_field;
				}
			}

		}


		return $acf_group_fields;

	}

}
