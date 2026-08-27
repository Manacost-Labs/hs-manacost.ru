<?php

/**
 * This setup runs just on first theme installation
 */
function td_first_install_setup() {

	$td_isFirstInstall = td_util::get_option('firstInstall' );

	if ( empty( $td_isFirstInstall ) ) {

		td_options::update('firstInstall', 'themeInstalled' );
		td_options::update('td_log_status', 'off' );

		//this was added to not affect existing users
		//by default is off, but we need it on mobile in case the shortcode is used
		td_options::update('tds_login_sign_in_widget', 'show' );


		/*
         * add the theme featured category
         */
		wp_insert_term( 'Featured', 'category', array(
			'description' => 'Featured posts',
			'slug' => 'featured',
			'parent' => 0
		));

		// bulk enable all the theme thumbs!
		$td_theme_thumbs = td_api_thumb::get_all();
		foreach ( $td_theme_thumbs as $td_theme_thumb_id => $td_theme_thumb_params ) {
			td_options::update('tds_thumb_' . $td_theme_thumb_id, 'yes' );
		}
	}
}
td_first_install_setup();

function td_theme_migration() {
	$td_db_version = td_util::get_option('td_version');

    /*
     * Social drag and drop (post/CPT share).
     */
    $social_drag_and_drop = td_options::get_array('td_social_drag_and_drop');
    $update_social_drag_and_drop_option = false;

	// Update TO version 8.6 - add social networks.
    // @since 14.12.2017
	if ( empty($social_drag_and_drop) ) {
        td_options::update_array('td_social_drag_and_drop', array (
            'facebook'      => true,
            'twitter'       => true,
            'pinterest'     => true,
            'whatsapp'      => true,
            'linkedin'      => '',
            'reddit'        => '',
            'mail'          => '',
            'print'         => '',
            'tumblr'        => '',
            'telegram'      => '',
            'stumbleupon'   => '',
            'vk'            => '',
            'digg'          => '',
            'line'          => '',
            'viber'         => '',
            'naver'         => '',
            'flipboard'     => '',
            'kakao'     => '',
            'gettr'     => '',
            'koo'     => '',
            'copy_url'      => ''
        ));

        $update_social_drag_and_drop_option = true;
    }

    // Remove Google+.
    if ( array_key_exists('googleplus', $social_drag_and_drop) ) {
        unset($social_drag_and_drop['googleplus']);
        $update_social_drag_and_drop_option = true;
    }

    // Add new social networks.
    $new_social_arr = array('naver', 'flipboard', 'copy_url', 'kakao', 'gettr', 'koo');
    if ( $social_drag_and_drop != '' ) {
        foreach ( $new_social_arr as $social ) {
            if ( array_key_exists($social, $social_drag_and_drop) === false ) {
                $social_drag_and_drop[$social] = '';
                $update_social_drag_and_drop_option = true;
            }
        }
    }

    // Save the updated social share option.
    if ( $update_social_drag_and_drop_option ) {
        td_options::update_array('td_social_drag_and_drop', $social_drag_and_drop);
    }

    /*
     * Check for new social network options.
     */
    $update_social_networks_option = false;

    // Remove google+ from social icons
    $td_social_networks = td_options::get_array('td_social_networks');
    if ( array_key_exists('googleplus', $td_social_networks) ) {
        unset($td_social_networks['googleplus']);
        $update_social_networks_option = true;
    }

    // Save the updated social networks option.
    if ( $update_social_networks_option ) {
        td_options::update_array('td_social_networks', $td_social_networks);
    }

    /*
     * When updating the theme past 12.6.9, check whether we have to migrate the theme panel
     * generated CSS from the 'td_011|td_010' option to its dedicated option.
     * When downgrading to a version bellow 12.6.9, check whether we have to migrate the theme
     * panel generated CSS from its dedicated option back to 'td_011|td_010'.
     */
    if ( TD_THEME_NAME == 'Newspaper' ) {
        if ( version_compare($td_db_version, '12.7', '<') || TD_DEPLOY_MODE == 'dev' ) {
            if ( get_option(TD_THEME_OPTIONS_NAME . '_generated_css') === false ) {
                $generated_css = td_options::get('tds_user_compile_css', '', true);
                td_options::delete('tds_user_compile_css');
                td_options::update('tds_user_compile_css', td_resources_optimize::css_minifier($generated_css));
            } else if ( !empty(td_options::get('tds_user_compile_css')) ) {
                td_options::delete('tds_user_compile_css');
            }
        }
    }


    // empty -> any version older version - probably 6?
	if (empty($td_db_version)) {

		// wp_parse_args format
		$args = array(
			'post_type' => array('page', 'post'),
			'numberposts' => '200',
			'orderby' => 'post_date',
			'order' => 'DESC',

			'meta_query' => array(
				'relation' => 'OR',
				array('key' => 'td_homepage_loop_filter'),
				array('key' => 'td_unique_articles'),
				array('key' => 'td_smart_list'),
				array('key' => 'td_review')
			),
			'update_post_term_cache' => false,
		);

		$recent_posts = wp_get_recent_posts($args);

		foreach ($recent_posts as $recent_post) {

			// page settings
			$update_td_homepage_loop = false;
			$td_homepage_loop = td_util::get_post_meta_array($recent_post['ID'], 'td_homepage_loop');
			$td_page = td_util::get_post_meta_array($recent_post['ID'], 'td_page');
			$td_homepage_loop_filter = td_util::get_post_meta_array($recent_post['ID'], 'td_homepage_loop_filter');
			$td_unique_articles = td_util::get_post_meta_array($recent_post['ID'], 'td_unique_articles');

			if (!empty($td_homepage_loop_filter) and is_array($td_homepage_loop_filter) and (count($td_homepage_loop_filter) > 0)) {
				foreach ($td_homepage_loop_filter[0] as $filter_key => $filter_value) {
					$td_homepage_loop[0][$filter_key] = $filter_value;
				}
				$update_td_homepage_loop = true;
			}

			if (!empty($td_unique_articles) and is_array($td_unique_articles) and (count($td_unique_articles) > 0)) {
				foreach ($td_unique_articles[0] as $filter_key => $filter_value) {
					$td_homepage_loop[0][$filter_key] = $filter_value;
					$td_page[0][$filter_key] = $filter_value;
				}
				$update_td_homepage_loop = true;
			}

			if ($update_td_homepage_loop === true) {
				update_post_meta($recent_post['ID'], 'td_homepage_loop', $td_homepage_loop[0]);
				update_post_meta($recent_post['ID'], 'td_page', $td_page[0]);
			}


			// post settings
			$update_td_post_theme_settings = false;
			$td_post_theme_settings = td_util::get_post_meta_array($recent_post['ID'], 'td_post_theme_settings');
			$td_smart_list = td_util::get_post_meta_array($recent_post['ID'], 'td_smart_list');
			$td_review = td_util::get_post_meta_array($recent_post['ID'], 'td_review');

			if (!empty($td_review) and is_array($td_review) and (count($td_review) > 0)) {
				foreach ($td_review[0] as $filter_key => $filter_value) {
					$td_post_theme_settings[0][$filter_key] = $filter_value;
				}
				$update_td_post_theme_settings = true;
			}

			if (!empty($td_smart_list) and is_array($td_smart_list) and (count($td_smart_list) > 0)) {
				foreach ($td_smart_list[0] as $filter_key => $filter_value) {
					$td_post_theme_settings[0][$filter_key] = $filter_value;
				}
				$update_td_post_theme_settings = true;
			}

			if ($update_td_post_theme_settings === true) {
				update_post_meta($recent_post['ID'], 'td_post_theme_settings', $td_post_theme_settings[0]);
			}
		}
	}


	/**
	 * auto update of posts should be done on 'after_setup_theme' because of computing shortcodes. Shortcodes are registered here. // && version_compare( $td_version, '10.2', '<' )
	 */
	add_action( 'after_setup_theme', function() {

		$td_updated_fonts = td_util::get_option('td_updated_fonts');

		if ( empty( $td_updated_fonts ) && 'Newspaper' == TD_THEME_NAME && TD_DEPLOY_MODE === 'deploy' ) {

			// wp_parse_args format
			$args = array(
				'post_type' => array( 'page', 'tdb_templates' ),
				'numberposts' => '100',

				'update_post_term_cache' => false,
			);

			$recent_posts = wp_get_recent_posts($args);

			foreach ($recent_posts as $recent_post) {

				$template_id = $recent_post['ID'];
				$template_content = $recent_post['post_content'];
				$template_type = $recent_post['post_type'];

				$tdb_template_type_exists = metadata_exists( 'post', $template_id, 'tdb_template_type' );

				$tdb_template_type = '';
				if ( $tdb_template_type_exists ) {
					$tdb_template_type = get_post_meta( $template_id, 'tdb_template_type', true );
				}

				if ( 'page' === $template_type || ( ! empty( $tdb_template_type ) && 'header' !== $tdb_template_type ) ) {

					// Set icon fonts used in post
					$google_font_list = td_util::get_required_google_fonts_ids( $template_id );
					update_post_meta( $template_id, 'tdc_google_fonts_settings', $google_font_list );

				} else if ( 'header' === $tdb_template_type ) {

					$extra_google_fonts_ids = [];

			        if ( base64_decode( $template_content, true ) && base64_encode( base64_decode( $template_content, true ) ) === $template_content ) {
			            $template_content = json_decode( base64_decode( $template_content ), true );

			            foreach ( ['tdc_header_desktop', 'tdc_header_desktop_sticky', 'tdc_header_mobile', 'tdc_header_mobile_sticky'] as $viewport ) {
			                if ( ! empty( $template_content[ $viewport ] ) ) {
			                    $google_fonts_ids = td_util::get_content_google_fonts_ids( $template_content[ $viewport ] );

			                    foreach ( $google_fonts_ids as $google_fonts_id => $font_settings ) {
			                        if ( array_key_exists( $google_fonts_id, $extra_google_fonts_ids ) ) {
			                            $extra_google_fonts_ids[ $google_fonts_id ] = array_unique( array_merge( $extra_google_fonts_ids[ $google_fonts_id ], $google_fonts_ids[ $google_fonts_id ] ) );
			                        } else {
			                            $extra_google_fonts_ids[ $google_fonts_id ] = $font_settings;
			                        }
				                }
				            }
				        }
				    }

				    if ( ! empty( $extra_google_fonts_ids ) ) {
				        update_post_meta( $template_id, 'tdc_google_fonts_settings', $extra_google_fonts_ids );
				    }
				}
			}

			td_util::update_option('td_updated_fonts', true);
		}

	});


	// auto update enable_post_create/enable_form_emailing page/cloud templates metas that use the tdb_form_submit shortcode
	add_action( 'after_setup_theme', function() {

		// updated td_posts_form_submit metas option
		$td_updated_td_posts_form_submit_meta = td_util::get_option('td_updated_td_posts_form_submit_meta');

		// check if td_posts_form_submit metas update is needed
		if ( empty($td_updated_td_posts_form_submit_meta) && 'Newspaper' == TD_THEME_NAME && TD_DEPLOY_MODE === 'deploy' ) {

			// get page/cloud templates that use the tdb_form_submit shortcode
			$posts = get_posts( array(
				's' => '[tdb_form_submit',
				'post_type' => array( 'page', 'tdb_templates' ),
				'post_status' => 'publish',
				'numberposts' => -1,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			));

			// update the td_posts_form_submit_ `enable_post_create/enable_form_emailing` custom meta fields
			// using these fields we will enable/disable post create/form emailing on tdb_posts_form_on_submit ajax handler
			// @see tdb_ajax > tdb_posts_form_on_submit
			foreach ( $posts as $post ) {
				$post_id = $post->ID;
				$post_content = $post->post_content;

				// extract shortcode from the post content
				preg_match_all('/\[(tdb_form_submit)[\s\S]*?\]/', $post_content, $matches );

				// if we don't have a match
				if ( !isset( $matches[0] ) )
					continue;

				// init vars
				$enable_post_create = false;
				$enable_form_emailing = false;

				// process matches
				foreach ( $matches[0] as $tdb_form_submit_shortcode ) {

					// parse shortcode atts
					$shortcode_atts = shortcode_parse_atts( str_replace( array( '[',']' ), '', $tdb_form_submit_shortcode ) );

					// get the enable_post_create/enable_form_emailing shortcode atts
					$enable_post_create = $shortcode_atts['enable_post_create'] ?? '';
					$enable_form_emailing = $shortcode_atts['enable_form_emailing'] ?? '';

				}

				// update enable_post_create meta
				if ( !empty($enable_post_create) ) {
					update_post_meta( $post_id, 'td_posts_form_submit_enable_post_create', true );
				}

				// update enable_form_emailing meta
				if ( !empty($enable_form_emailing) ) {
					update_post_meta( $post_id, 'td_posts_form_submit_enable_form_emailing', true );
				}

			}

			td_util::update_option('td_updated_td_posts_form_submit_meta', true );

		}

	});

    // flag to get logo size in TP
    $tds_logo_width_height = td_util::get_option('tds_logo_width_height');

    // Only execute if the flag is not set.
    if ( empty( $tds_logo_width_height ) ) {

        $td_logo_options = array('tds_logo_upload', 'tds_logo_menu_upload', 'tds_footer_logo_upload', 'tds_logo_menu_upload_mob', 'tds_footer_logo_upload_mob');

        foreach ($td_logo_options as $logo_option) {

            if ( !empty( td_util::get_option($logo_option) ) && empty( td_util::get_option($logo_option . '_width') ) ) {

                $td_image_id = attachment_url_to_postid(td_util::get_option($logo_option));

                if ( $td_image_id !== 0 ) {
                    $info_img = wp_get_attachment_image_src( $td_image_id, 'full' );
                    if ( false !== $info_img ) {
                            td_util::update_option($logo_option . '_width', $info_img[1] );
                            td_util::update_option($logo_option . '_height', $info_img[2]);
                        }
                }
            }
        }

        // Set a flag specifying that the procedure has been executed.
        td_util::update_option('tds_logo_width_height', true);
    }

    // update the database version
    if ($td_db_version != TD_THEME_VERSION) {
        td_util::update_option('td_version', TD_THEME_VERSION);
    }
}
td_theme_migration();



