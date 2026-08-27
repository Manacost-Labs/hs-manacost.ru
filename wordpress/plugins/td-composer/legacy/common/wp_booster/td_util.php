<?php
class td_util {

    private static $is_no_header = false;

    private static $is_template_header = false;

    private static $header_template_id = null;

    private static $header_template_content = array(
        'tdc_header_mobile',
        'tdc_header_mobile_sticky',
        'tdc_header_desktop',
        'tdc_header_desktop_sticky',
    );


    private static $is_no_footer = false;

    private static $is_template_footer = false;

    private static $footer_template_id = null;

    private static $footer_template_content = null;


    private static $authors_array_cache = ''; // cache the results from  create_array_authors

	private static $shortcodes_with_icons = null; // shortcodes with icon type params

	private static $check_installed_plugins = false;

    public static $e_keys = array('dGRfMDEx' => '', 'dGRfMDExXw==' => 2);

    private static $font_family_list = array();

    private static $font_family_list_flip = array();

    private static $favourite_articles;

    const FAVOURITE_COOKIE_ID = 'tdb_favourites';

    /**
     * The detector object.
     * @var object $mobile_detect object
     */
    private static $mobile_detect;

    static function is_no_header() {
        return self::$is_no_header;
    }

    static function is_template_header() {
        return self::$is_template_header;
    }

    static function get_header_template_content() {
        return self::$header_template_content;
    }

    static function get_header_template_id() {
        return self::$header_template_id;
    }

    static function get_footer_template_content() {
        return self::$footer_template_content;
    }

    static function get_footer_template_id() {
        return self::$footer_template_id;
    }

    static function check_mobile( $post = null ) {

        if ( empty( $post ) ) {
            global $post;
        }


        // is_single should not have been working for attachments! But it does.
        if ( is_single() && ! is_attachment() && $post instanceof WP_Post && 'tdb_templates' !== $post->post_type ) {

            // read the per post single_template
            $post_meta_values = td_util::get_post_meta_array( $post->ID, 'td_post_theme_settings' );

            // if we don't have any single_template set on this post, try to load the default global setting
            if ( ! empty( $post_meta_values['td_post_template'] ) ) {

                $td_site_post_template = $post_meta_values['td_post_template'];

                if ( td_global::is_tdb_template( $td_site_post_template, true ) ) {
                    $template_id = td_global::tdb_get_template_id( $td_site_post_template );
                }

            } else {

                $td_primary_category = td_global::get_primary_category_id();

                if ( ! empty( $td_primary_category ) ) {

                    $post_category_template = td_util::get_category_option( $td_primary_category, 'tdb_post_category_template' );

                    // make sure the template exists, maybe it was deleted or something
                    if ( td_global::is_tdb_template( $post_category_template, true ) ) {
                        $template_id = td_global::tdb_get_template_id($post_category_template);
                    }
                }

                if (empty($template_id)) {

	                $option_id = 'td_default_site_post_template';
	                if ( class_exists( 'SitePress', false ) ) {
		                global $sitepress;
		                $sitepress_settings = $sitepress->get_settings();
                        if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                            $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                            if (1 === $translation_mode) {
                                $option_id .= $sitepress->get_current_language();
                            }
                        }
	                }
	                $td_default_site_post_template = td_util::get_option( $option_id );

	                if ( ! empty( $td_default_site_post_template ) && td_global::is_tdb_template( $td_default_site_post_template, true ) ) {
		                $template_id = td_global::tdb_get_template_id( $td_default_site_post_template );
	                }
                }
            }

        } else if ( is_category() ) {

            $current_category_id  = get_query_var( 'cat' );
            $current_category_obj = get_category( $current_category_id );

            if ( $current_category_obj instanceof WP_Term ) {

                // read the individual cat template
                $tdb_individual_category_template = td_util::get_category_option( $current_category_id, 'tdb_category_template' );

                if ( empty( $tdb_individual_category_template ) ) {

                    $option_id = 'tdb_category_template';
                    if (class_exists('SitePress', false )) {
                        global $sitepress;
                        $sitepress_settings = $sitepress->get_settings();
                        if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                            $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                            if (1 === $translation_mode) {
                                $option_id .= $sitepress->get_current_language();
                            }
                        }
                    }

                    // read the global template
                    $tdb_category_template = td_options::get( $option_id );

                    if ( td_global::is_tdb_template( $tdb_category_template, true ) ) {
                        $template_id = td_global::tdb_get_template_id( $tdb_category_template );
                    }
                }

                if ( td_global::is_tdb_template( $tdb_individual_category_template, true ) ) {
                    $template_id = td_global::tdb_get_template_id( $tdb_individual_category_template );
                }
            }

        } else if ( is_author() ) {

            $author_query_var = get_query_var( 'author' );
            if ( ! empty( $author_query_var ) ) {

                $option_id = 'tdb_author_templates';
                if (class_exists('SitePress', false )) {
                    global $sitepress;
                    $sitepress_settings = $sitepress->get_settings();
                    if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                        $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                        if (1 === $translation_mode) {
                            $option_id .= $sitepress->get_current_language();
                        }
                    }
                }

                // user templates
                $tdb_author_templates = td_util::get_option( $option_id );

                if ( ! empty( $tdb_author_templates[ $author_query_var ] ) && td_global::is_tdb_template( $tdb_author_templates[ $author_query_var ], true ) ) {

                    $template_id = td_global::tdb_get_template_id( $tdb_author_templates[ $author_query_var ] );

                } else {

                    $option_id = 'tdb_author_template';
                    if (class_exists('SitePress', false )) {
                        global $sitepress;
                        $sitepress_settings = $sitepress->get_settings();
                        if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                            $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                            if (1 === $translation_mode) {
                                $option_id .= $sitepress->get_current_language();
                            }
                        }
                    }

                    $template_id = td_options::get( $option_id );
                    if ( td_global::is_tdb_template( $template_id, true ) ) {
                        $template_id = td_global::tdb_get_template_id( $template_id );
                    }
                }
            }

        } else if ( is_search() ) {

            $option_id = 'tdb_search_template';
            if (class_exists('SitePress', false )) {
                global $sitepress;
                $sitepress_settings = $sitepress->get_settings();
                if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                    $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                    if (1 === $translation_mode) {
                        $option_id .= $sitepress->get_current_language();
                    }
                }
            }

            $tdb_search_template = td_options::get( $option_id );
            if ( td_global::is_tdb_template( $tdb_search_template, true ) ) {
                $template_id = td_global::tdb_get_template_id( $tdb_search_template );
            }

        } else if ( is_date() ) {

            $option_id = 'tdb_date_template';
            if (class_exists('SitePress', false )) {
                global $sitepress;
                $sitepress_settings = $sitepress->get_settings();
                if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                    $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                    if (1 === $translation_mode) {
                        $option_id .= $sitepress->get_current_language();
                    }
                }
            }

            // read template
            $tdb_date_template = td_options::get( $option_id );
            if ( td_global::is_tdb_template( $tdb_date_template, true ) ) {
                $template_id = td_global::tdb_get_template_id( $tdb_date_template );
            }

        } else if ( is_tag() ) {

            $option_id = 'tdb_tag_template';
            if (class_exists('SitePress', false )) {
                global $sitepress;
                $sitepress_settings = $sitepress->get_settings();
                if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                    $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                    if (1 === $translation_mode) {
                        $option_id .= $sitepress->get_current_language();
                    }
                }
            }

            // read template
            $tdb_tag_template = td_options::get( $option_id );
            if ( td_global::is_tdb_template( $tdb_tag_template, true ) ) {
                $template_id = td_global::tdb_get_template_id( $tdb_tag_template );
            }
        } else if ( is_attachment() ) {

            $option_id = 'tdb_attachment_template';
            if (class_exists('SitePress', false )) {
                global $sitepress;
                $sitepress_settings = $sitepress->get_settings();
                if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                    $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                    if (1 === $translation_mode) {
                        $option_id .= $sitepress->get_current_language();
                    }
                }
            }

            // read template
            $tdb_tag_template = td_options::get( $option_id );
            if ( td_global::is_tdb_template( $tdb_tag_template, true ) ) {
                $template_id = td_global::tdb_get_template_id( $tdb_tag_template );
            }
        } else if ( is_404() ) {

            $option_id = 'tdb_404_template';
            if (class_exists('SitePress', false )) {
                global $sitepress;
                $sitepress_settings = $sitepress->get_settings();
                if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                    $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                    if (1 === $translation_mode) {
                        $option_id .= $sitepress->get_current_language();
                    }
                }
            }

            // read template
            $tdb_404_template = td_options::get( $option_id );
            if ( td_global::is_tdb_template( $tdb_404_template, true ) ) {
                $template_id = td_global::tdb_get_template_id( $tdb_404_template );
            }

            if ( empty( $template_id ) ) {

                // Get general header template
                $tdb_header_template = td_util::get_option( 'tdb_header_template' );
                if ( td_global::is_tdb_template( $tdb_header_template, true ) ) {
                    $template_id = td_global::tdb_get_template_id( $tdb_header_template );
                }
            }
        }


        if ( ! empty( $template_id ) ) {
            $ref_id = $template_id;
        } else if ( $post instanceof WP_Post ) {
            $ref_id = $post->ID;
        }

        if ( !empty($ref_id ) ) {
	        $meta_is_mobile_template = get_post_meta( $ref_id, 'tdc_is_mobile_template', true );
	        if ( ( ! empty( $meta_is_mobile_template ) && '1' === $meta_is_mobile_template ) ) {
		        tdc_state::set_start_composer_for_mobile(true);
	        }
        }
    }

    static function check_header( $post = null ) {

        if ( empty($post) ) {
            global $post;
        }

        $template_id = self::get_template_id($post);

        if ( !empty($template_id) || is_page() ) {
            add_filter('body_class', function($classes) {
                $classes[] = 'tdb-template';
                return $classes;
            });
        }


        if ( !empty($template_id) ) {
            // Take header template if from the associated template used to render the current post
            $ref_id = $template_id;
        } else if ( $post instanceof WP_Post ) {
            // Take header template if from the current post itself
            $ref_id = $post->ID;
        }


        if ( !empty($ref_id ) ) {
            $tdc_header_template_id = get_post_meta( $ref_id, 'tdc_header_template_id', true );
            $tdb_template_type = get_post_meta( $ref_id, 'tdb_template_type', true );

            // header templates must have set as header their contents, to allow editing them in composer
            if ('header' === $tdb_template_type) {
                update_post_meta( $ref_id, 'tdc_header_template_id', $ref_id );
                $tdc_header_template_id = $ref_id;
            }
        }


        if ( !empty($ref_id ) ) {
	        $meta_is_mobile_template = get_post_meta( $ref_id, 'tdc_is_mobile_template', true );
	        if ( ( ! empty( $meta_is_mobile_template ) && '1' === $meta_is_mobile_template ) ) {
		        tdc_state::set_is_mobile_template(true);
	        }
        }


        if ( !tdc_state::is_mobile_template() ) {

            $is_mobile = false;

            if ( class_exists('Mobile_Detect')) {
	            $mobile_detect = new Mobile_Detect();
	            if ( $mobile_detect->isMobile() ) {

	                if ( !empty($ref_id ) ) {
	                    $ref_id = get_post_meta( $ref_id, 'tdc_mobile_template_id', true );

		                if ( !empty($ref_id ) && 'publish' === get_post_status( $ref_id ) ) {
		                    $tdc_header_template_id = get_post_meta( $ref_id, 'tdc_header_template_id', true );
		                    $is_mobile = true;
		                }
	                }
	            }
            }

            // Remove content for header templates, because their content is shown as their associated header template
            if ( td_util::tdc_is_live_editor_iframe() && !empty( $tdb_template_type ) && 'header' === $tdb_template_type ) {
                add_filter( 'the_content', function( $content ) {
                    return '<div class="tdc-dummy-content" style="height: 1500px; background-color: #f9f9f9; background-image: url(\'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAZAAAAGQBAMAAABykSv/AAAAFVBMVEUAAAAtKiYtKiYtKiYtKiYtKiYtKiazrNZrAAAAB3RSTlMAGwQIEQwV0M30jwAABqdJREFUeNrsncuS0zAQRYON2fMIa/PIrA0FrEPisHZ4rSHk/7+BKFMVGUl2R5LHlsM5C9VM102oPKqYvu6+XrzeLE58U8eqPB1FdTqyr+r3H+p4ey42qthSLrRSF13Kj1rZuP4hrfxwUeYuZd6rVNI/6vmO6sef6lF3a6VSxUWtjk/qAU8uRUt5UMXaLBrK5kGVx1JVf79Qhafq56e/Tsf+2el49FIX15dikHItKuOLzfmzb73Plfoq6Te/NoqG8i5cWQ77nOUCAAAmo1LHa3Xk6shKdZaOYqaVb+KUWb8yM55TViq+vFD6p6rye3063qn/MZ+0io1VlJVLD2Vgcd8qnl/j56leyL5bufRUVvqr9f7ykeXG55jrYkuZXhEAAKbhzebSV34495XN6ci36vf7Ynk6Hq9V8azcXYpKaRc7lYUufr9XGkWttB4uKJ3mw7tLU59L5oOolIuvApSZbD7opn5eRcwHAIDpsD2BeRbb5sNnj55dMApkpVxcCMoxzIcXXuZDYb1kSWkUq4Xu3+fkM2A+AAAkxJtzt7izzQfbUji4lLYnoJS6WPW7BwenpeBb7DEfpMmH8NGFeJsC8wEAAAzGm3x4WKXu+wSl2SG2zYfltebDY5dSthSSMR+GLVrugay0zQf7T5SU3APMBwCAWbIyzYf1xXyoDfPBZVMUW8c8Qy5YCkMXPdYunie8dvG4bT7U2rpRqmLrKprKplN58FTa77OtdH/KrF0AAAQxb/Mh6zQfMj/zYRFmPoQtUwjK+GLhYT6MtnahlYU4+aCV1UKRinuA+QAAMEtW5pBD02k+uNt/P+XOU/k/mQ968sERsHB9cauLZYDy8K/yzleJ+QAAMCVG59XjHkQpcz9l5q28rmefPPNhL60wVGmFV6y7Rjj28giHQv4aqGJQOkR8jkSO+QAAkDgr3VcKkw/FVrh6rpUb59VzP+XOqUw182GMtQvbPUhVydoFAMCE2GMGk+9iZIIyaynJfHiIzIc8JvNBmw9kPgAAQDDx19n/XHOdvelUHnTRXOUQrrN7mA9JZT7E3+1Cd/rDKctOZWO++cL9R5h8AACYEt0hSkZBlDIXlGbf56+U1y7kMYDZmA8hxSWZD5gPAAAzo2U+fLhmdH/XnUVQutIAetIh/AInDze/diFlPtgDKsNlPpShmQ81mQ8AAKkRYim88VAGxEjGBU7KlgKZD8OlQ5D5AAAAAjHmg327Cvcuhqk0mmI5cNLd6srpEGQ+TJT5ICt18Q7zAQAgCfryGeY0MJ9Qz95cua0gZz58TivzwVbGZz5k5eDpELKSzAcAgDmgW11xnkFUDnqdfedSHnX/O9e1i1eh5kN8PkP9sJkPDZkPAABJMNDahRzrJyhjipPmNO4DchoLp3KGgZPdL0R/N4b/arlDQph8AAC4LYRWd6yi3OoeOlvd+mbNB2cE6PdqaDto+AhQzAcAgImIW6bwU2YDKyMCJ0dY0Ai6SUS6axfeIxzxQzWyUvC1mHwAAJg5cuZDTKtrK8l8EM2HrZWYEb8Is7l6EWbjuQiD+QAAkBr/zdpFk15UQsfaxY2EV4wRJ5INECeS9SkBAGASdKtbz7/VvR3zYSQ7KD4CFPMBACBFxr7oPIfMh3Xk2kVs5sON3LYjbKMnvfEbAACYBDnzoUop8+FeSebDkEXplqbmw/uVWwInAQCmxe77pKw/v3X7/PYnH4bLfBg7cHIpKSP2R7oHEszimMrsKiXmAwBAEty3useQeMNUlMfbMh9WQuaDEQEaY/KQ+QAA8Le9M1ZBGIai6BD7A6K7i3tB3aWCc8Ff8P+/QQoSJYm9jZH6Gs4ZOjwudOnSm9z7aqRg0YBQFsbtM5RVLYmw0/mQMRSdDyJMESqdUKpPK1PJzQcAAJMUn7OXLHaMLQWx2PGeXOyYEbtY249d7C9q+amKt+QHYY50PgAA1IOV0gZDsYvZOh/Ofrh5i11MfulqiZ0Pop9BKAs7H9JKOh8AAEyiYhfHdvI5e5s+Z89XdpnKSjofVv7mg//TH4+3uFDZT2ly+Kzs6XwAAKgAM+6BIfMhbxhbClrZ+GF8rHDdWu182JnqfHB0PgAA1M0pefdgWUMRu2iWGLsILAWXNB+EpaDMhw7zAQCgQp7/70tabDFj58PmJ4WT83Y+bG0VTtL5AAAAmA/FnQ/OhPngfYbIfIjDFLFS9zNopTYfiF0AANhlWjR+hoBGaeeD2cLJEWXdnQ/98DwMj/3wcO0wbMOhV95ew9248vCFsgk+mOjtaSUAAPyLB4YdjhTNmj87AAAAAElFTkSuQmCC\')"></div>';
                });
            }

            if ( empty( $tdc_header_template_id ) ) {

                // Show the global template if it is set
                // Check the status of the header template and if it's not 'publish', show the legacy global template
                // Do nothing for 'header' type templates, because they don't need to use global header template

                if ( ( isset( $tdb_template_type ) && 'header' !== $tdb_template_type ) || ! isset( $tdb_template_type ) ) {

                    $global_header_template_id = td_api_header_style::get_header_template_id( $is_mobile );

                    if ( !empty( $global_header_template_id ) && td_global::is_tdb_template( $global_header_template_id, true ) ) {

                        $global_header_template_id = td_global::tdb_get_template_id( $global_header_template_id );
                        self::$header_template_id  = $global_header_template_id;

                        $meta_header_template_content = get_post_field( 'post_content', str_replace( 'tdb_template_', '', $global_header_template_id ) );
                        if ( ! empty( $meta_header_template_content ) ) {
                            self::$is_template_header = true;

                            if ( self::tdc_is_installed() ) {
                                self::$header_template_content = json_decode( tdc_b64_decode( $meta_header_template_content ), true );
                            }
                        }
                    }

                    if ( $is_mobile && !empty( self::$header_template_id ) ) {
                        $meta_header_template_content = get_post_field( 'post_content', str_replace( 'tdb_template_', '', self::$header_template_id ) );
                        if ( !empty( $meta_header_template_content ) ) {
                            self::$is_template_header = true;

                            if ( self::tdc_is_installed() ) {
                                self::$header_template_content = json_decode( tdc_b64_decode( $meta_header_template_content ), true );
                            }
                        } else {
                            self::$is_template_header = false;
                        }
                    }
                }

            } else {

                if ( 'no_header' === $tdc_header_template_id ) {

                    self::$is_no_header = true;

                } else {

                    // Check the status of the header template and if it's not 'publish', show the global template
                    $post_status = get_post_status( $tdc_header_template_id );

                    if ( 'publish' === $post_status ) {

                        self::$header_template_id = $tdc_header_template_id;
                        $meta_header_template_content = get_post_field( 'post_content', $tdc_header_template_id );

                        self::$is_template_header = true;

                        if ( self::tdc_is_installed() ) {
                            self::$header_template_content = json_decode( tdc_b64_decode( $meta_header_template_content ), true );
                        }

                        if ( $is_mobile && !empty( self::$header_template_id ) ) {
                            $meta_header_template_content = get_post_field( 'post_content', self::$header_template_id );
                            if ( !empty( $meta_header_template_content ) ) {
                                self::$is_template_header = true;

                                if ( self::tdc_is_installed() ) {
                                    self::$header_template_content = json_decode( tdc_b64_decode( $meta_header_template_content ), true );
                                }
                            } else {
                                self::$is_template_header = false;
                            }
                        }

                    } else {
                        // Show the global template if it is set
                        // Check the status of the header template and if it's not 'publish', show the legacy global template
                        // Do nothing for 'header' type templates, because they don't need to use global header template
                        if ( isset( $tdb_template_type ) && 'header' !== $tdb_template_type ) {
                            $global_header_template_id = td_api_header_style::get_header_template_id( $is_mobile );

                            if ( !empty( $global_header_template_id ) && td_global::is_tdb_template( $global_header_template_id, true ) ) {
                                $global_header_template_id = td_global::tdb_get_template_id( $global_header_template_id );
                                self::$header_template_id  = $global_header_template_id;

                                $meta_header_template_content = get_post_field( 'post_content', str_replace( 'tdb_template_', '', $global_header_template_id ) );
                                if ( !empty( $meta_header_template_content ) ) {
                                    self::$is_template_header = true;

                                    if ( self::tdc_is_installed() ) {
                                        self::$header_template_content = json_decode( tdc_b64_decode( $meta_header_template_content ), true );
                                    }
                                }
                            }
                        }
                    }
                }
            }

        } else {

            // Remove content for header templates, because their content is shown as their associated header template
            if ( td_util::tdc_is_live_editor_iframe() && !empty( $tdb_template_type ) && 'header' === $tdb_template_type ) {
                add_filter( 'the_content', function( $content ) {
                    return '<div class="tdc-dummy-content" style="height: 1500px; background-color: #f9f9f9; background-image: url(\'data:image/png;base64,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\')"></div>';
                });
            }

            if ( empty( $tdc_header_template_id ) ) {
                // Show the global template if it is set
                // Check the status of the header template and if it's not 'publish', show the legacy global template
                // Do nothing for 'header' type templates, because they don't need to use global header template

                if ( ( isset( $tdb_template_type ) && 'header' !== $tdb_template_type ) || !isset( $tdb_template_type ) ) {
                    $global_header_template_id = td_api_header_style::get_header_template_id( true );

                    if ( !empty( $global_header_template_id ) && td_global::is_tdb_template( $global_header_template_id, true ) ) {
                        $global_header_template_id = td_global::tdb_get_template_id( $global_header_template_id );
                        self::$header_template_id  = $global_header_template_id;

                        $meta_header_template_content = get_post_field( 'post_content', str_replace( 'tdb_template_', '', $global_header_template_id ) );
                        if ( !empty( $meta_header_template_content ) ) {
                            self::$is_template_header = true;

                            if ( self::tdc_is_installed() ) {
                                self::$header_template_content = json_decode( tdc_b64_decode( $meta_header_template_content ), true );
                            }
                        }
                    }

                    if ( !empty( self::$header_template_id ) ) {
                        $meta_header_template_content = get_post_field( 'post_content', str_replace( 'tdb_template_', '', self::$header_template_id ) );
                        if ( !empty( $meta_header_template_content ) ) {
                            self::$is_template_header = true;

                            if ( self::tdc_is_installed() ) {
                                self::$header_template_content = json_decode( tdc_b64_decode( $meta_header_template_content ), true );
                            }
                        } else {
                            self::$is_template_header = false;
                        }
                    }
                }

            } else {
                if ( 'no_header' === $tdc_header_template_id ) {
                    self::$is_no_header = true;

                } else {
                    // Check the status of the header template and if it's not 'publish', show the global template
                    $post_status = get_post_status( $tdc_header_template_id );

                    if ( 'publish' === $post_status ) {
                        self::$header_template_id = $tdc_header_template_id;
                        $meta_header_template_content = get_post_field( 'post_content', $tdc_header_template_id );

                        self::$is_template_header = true;

                        if ( self::tdc_is_installed() ) {
                            self::$header_template_content = json_decode( tdc_b64_decode( $meta_header_template_content ), true );
                        }

                    } else {
                        // Show the global template if it is set
                        // Check the status of the header template and if it's not 'publish', show the legacy global template
                        // Do nothing for 'header' type templates, because they don't need to use global header template
                        if ( isset( $tdb_template_type ) && 'header' !== $tdb_template_type ) {
                            $global_header_template_id = td_api_header_style::get_header_template_id( true );

                            if ( !empty( $global_header_template_id ) && td_global::is_tdb_template( $global_header_template_id, true ) ) {
                                $global_header_template_id = td_global::tdb_get_template_id( $global_header_template_id );
                                self::$header_template_id  = $global_header_template_id;

                                $meta_header_template_content = get_post_field( 'post_content', str_replace( 'tdb_template_', '', $global_header_template_id ) );
                                if ( !empty( $meta_header_template_content ) ) {
                                    self::$is_template_header = true;

                                    if ( self::tdc_is_installed() ) {
                                        self::$header_template_content = json_decode( tdc_b64_decode( $meta_header_template_content ), true );
                                    }
                                }
                            }
                        }
                    }
                }
            }

            //// Important!
            //// We have $tdc_header_template_id equal with ('') empty string - when there is no mobile template header set on the normal template header
            //// We have $tdc_header_template_id equal with (false) boolean - when there is no mobile template header set on GLOBAL template header
            //
            //if ( td_util::tdc_is_live_editor_iframe() && $start_composer_for_mobile && empty( self::$header_template_id ) && 'no_header' !== $tdc_header_template_id ) {
            //    add_filter( 'the_content', function( $content ) {
            //        return
            //            '<div class="tdc-dummy-mobile-header">
            //                 <div class="tdc-dummy-mobile-header-inner">
            //                    <span class="tdc-dmh-icon"><img src="' . TDC_URL . '/assets/images/sidebar/icon-notice.png"></span>
            //
            //                    <span class="tdc-dmh-txt">No mobile header template has been set! Please add one.</span>
            //                </div>
            //            </div>'
            //            . $content;
            //    });
            //}

        }


        if ( self::$is_template_header ) {

            add_filter( 'body_class', function( $classes ) {
                $classes[] = ' tdc-header-template';
                return $classes;
            });

            // old method to get all used Google fonts
            add_filter( 'td_filter_google_fonts', function( $extra_google_fonts_ids ) {

                $content = '';
                $header_template_content = td_util::get_header_template_content();

                foreach ( $header_template_content as $header_template ) {
                    $content .= $header_template;
                }

                if ( td_util::tdc_is_installed() ) {
                    $google_fonts_ids = tdc_util::get_content_google_fonts_ids( $content );
                    $extra_google_fonts_ids = array_unique( array_merge( $extra_google_fonts_ids, $google_fonts_ids ) );
                }

                return $extra_google_fonts_ids;

            });

            // new method to get all used google fonts
            add_filter( 'td_filter_google_fonts_settings', function( $extra_google_fonts_ids ) {

                $header_template_id = td_util::get_header_template_id();

                if ( td_util::tdc_is_installed() ) {
                    $new_meta_exists = metadata_exists( 'post', $header_template_id, 'tdc_google_fonts_settings' );

                    if ( $new_meta_exists ) {
	                    $google_fonts_ids = get_post_meta( $header_template_id, 'tdc_google_fonts_settings', true );

	                    foreach ( $google_fonts_ids as $google_fonts_id => $font_settings ) {
		                    if ( array_key_exists( $google_fonts_id, $extra_google_fonts_ids ) ) {
			                    $extra_google_fonts_ids[ $google_fonts_id ] = array_unique( array_merge( $extra_google_fonts_ids[ $google_fonts_id ], $google_fonts_ids[ $google_fonts_id ] ) );
		                    } else {
			                    $extra_google_fonts_ids[ $google_fonts_id ] = $font_settings;
		                    }
	                    }
                    }
                }

                // Also retrieve the fonts used by Ai Builder component styles.
                if ( defined('TD_AIB_PLUGIN_NAME') ) {
                    if ( is_array(self::$header_template_content) ) {
                        foreach ( self::$header_template_content as $header_template_zone_content ) {
                            $aib_component_style_fonts = \TagDiv\AiBuilder\Typography::get_component_style_fonts_from_content($header_template_zone_content);

                            foreach ( $aib_component_style_fonts as $font_id => $font_weights ) {
                                if ( array_key_exists($font_id, $extra_google_fonts_ids) && is_array($extra_google_fonts_ids[$font_id]) ) {
                                    $extra_google_fonts_ids[$font_id] = array_unique( array_merge( $extra_google_fonts_ids[$font_id], $font_weights) );
                                } else {
                                    $extra_google_fonts_ids[$font_id] = $font_weights;
                                }
                            }
                        }
                    }
                }

                return $extra_google_fonts_ids;
            });

            // extract icon fonts from header template content
            add_filter('td_filter_icon_fonts', function( $extra_icon_fonts = array()) {
                $header_template_id = td_util::get_header_template_id();

                if ( td_util::tdc_is_installed() ) {
                    $header_icon_fonts = get_post_meta( $header_template_id, 'tdc_icon_fonts', true );

                    if ( ! empty( $header_icon_fonts ) && is_array( $header_icon_fonts ) ) {
                        foreach ( $header_icon_fonts as $font_id => $font_settings ) {
                            if ( empty($extra_icon_fonts) ) {
                                $extra_icon_fonts = array();
                            }
                            $extra_icon_fonts[ $font_id ] = $font_settings;
                        }
                    }
                }

                return $extra_icon_fonts;
            });

            // don't apply on Mobile Theme
            if ( !self::is_mobile_theme() ) {

                // check header tpl content for tdb_header_menu shortcodes, we needed to render menu shortcodes to set the ids of pages used as mega menu @see td_global::$mega_menu_pages_ids
                $header_tpl_content = '';
                $header_template_content = td_util::get_header_template_content();
                if ( is_array($header_template_content) ) {
                    foreach ( $header_template_content as $header_template ) {
                        $header_tpl_content .= $header_template;
                    }
                }

                // extract shortcodes from the page content
                preg_match_all('/\[(tdb_header_menu)[\s\S]*?\]/', $header_tpl_content, $matches);

                // if the shortcodes are found
                if ( isset($matches[0]) ) {
                    foreach ( $matches[0] as $tdb_header_menu_shortcode ) {

                        // add a custom attribute used to flag this do_shortcode run on shortcode's callback
                        // used to not render tdbMenu items js on header check shortcode render
                        $tdb_header_menu_shortcode = str_replace( '[tdb_header_menu', '[tdb_header_menu context="check_header"', $tdb_header_menu_shortcode );

                        do_shortcode($tdb_header_menu_shortcode); // render shortcode

                    }
                }
            }
        }

        if ( self::$is_no_header ) {
            add_filter( 'body_class', function( $classes ) {
                $classes[] = ' tdc-no-header';
                return $classes;
            });
        }
    }

    static function get_mega_menu_pages_google_fonts_ids() {

	    $extra_google_fonts_ids = array();

	    if ( td_global::$mega_menu_pages_ids ) {

		    foreach ( td_global::$mega_menu_pages_ids as $page_id ) {

			    if ( td_util::tdc_is_installed() ) {

				    $meta_page_content = get_post_field( 'post_content', $page_id );
				    if ( !empty($meta_page_content) ) {
					    $google_fonts_ids = tdc_util::get_content_google_fonts_ids( $meta_page_content );
					    $extra_google_fonts_ids = array_unique( array_merge( $extra_google_fonts_ids, $google_fonts_ids ) );
				    }

				    $new_meta_exists = metadata_exists( 'post', $page_id, 'tdc_google_fonts_settings' );
				    if ( $new_meta_exists ) {
				        $google_fonts_ids = get_post_meta( $page_id, 'tdc_google_fonts_settings', true );
				        foreach ( $google_fonts_ids as $gf_id => $font_settings ) {
				            if ( !in_array( $gf_id, $extra_google_fonts_ids ) ) {
				                $extra_google_fonts_ids[] = $gf_id;
				            }
				        }
				    }

			    }

		    }

        }

        if ( !empty( $extra_google_fonts_ids ) ) {

	        $tdc_wm_global_fonts = td_util::get_option('tdc_wm_global_fonts' );
	        foreach ( $extra_google_fonts_ids as &$font_id ) {

		        if ( strpos( $font_id, '_global' ) !== false ) {
			        $font_setting_option_id = str_replace( '_global', '', $font_id );

			        if ( !empty( $tdc_wm_global_fonts ) && is_array( $tdc_wm_global_fonts ) ) {
				        foreach ( $tdc_wm_global_fonts as $font_option_id => $font_data ) {

					        if ( $font_option_id === $font_setting_option_id && !empty( td_fonts::$font_names_google_list[$font_data['key']] ) ) {
						        $font_id = $font_data['key'];
					        }

				        }
			        }

		        }

	        }

        }

        return $extra_google_fonts_ids;

    }

    static function get_mega_menu_pages_icon_fonts_ids() {

	    $extra_icon_fonts_ids = array();

	    if ( td_global::$mega_menu_pages_ids ) {

		    foreach ( td_global::$mega_menu_pages_ids as $page_id ) {

			    if ( td_util::tdc_is_installed() ) {

				    $meta_page_content = get_post_field( 'post_content', $page_id );
				    if ( !empty($meta_page_content) ) {
					    $icon_fonts_ids = tdc_util::get_content_icon_fonts_ids( $meta_page_content );
					    $extra_icon_fonts_ids = array_unique( array_merge( $extra_icon_fonts_ids, $icon_fonts_ids ) );
				    }

			    }

		    }

        }

        return $extra_icon_fonts_ids;

    }

	static function get_modules_ct_google_fonts_ids($post_id) {

		$extra_google_fonts_ids = array();

        // get post content
		$content = $post_id ? get_post( $post_id )->post_content : '';

		/*
		 * deal with shortcodes that use cloud module templates, like [tdb_flex_block_builder] | [tdb_flex_loop_builder]
		 * ex. [tdb_flex_block_builder cloud_tpl_module_id="41012"]
		 * we will get the icons fonts from module tpl 'tdc_google_fonts_settings' meta
		 */

		// extract shortcodes from post content
		preg_match_all( '/\[(tdb_flex_(?:loop|block)_builder)[\s\S]*?\]/', $content, $matches );

		// if we have matches
		if ( isset( $matches[0] ) ) {
			foreach ( $matches[0] as $tdb_flex_block_builder_shortcode ) {

				// parse shortcode atts
                $shortcode_atts = shortcode_parse_atts( str_replace( array( '[',']' ), '', $tdb_flex_block_builder_shortcode ) );

				// get the module tpl id shortcode att
				$module_tpl_id = $shortcode_atts['cloud_tpl_module_id'] ?? '';

				if ( td_global::is_tdb_registered() && tdb_util::is_tdb_module( 'tdb_module_' . $module_tpl_id, true ) ) {

                    // for now rely just on module's tpl meta (the module tpl required google fonts ids should be present on module tpl 'tdc_google_fonts_settings' meta)
                    //$meta_module_tpl_content = get_post_field( 'post_content', $module_tpl_id );
                    //if ( !empty($meta_module_tpl_content) ) {
                    //    $module_tpl_content_google_fonts_ids = tdc_util::get_content_google_fonts_ids( $meta_module_tpl_content );
                    //    $extra_google_fonts_ids = array_unique( array_merge( $extra_google_fonts_ids, $module_tpl_content_google_fonts_ids ) );
                    //}

                    // get the module tpl required google fonts ids from module tpl 'tdc_google_fonts_settings' meta
					$google_fonts_ids = get_post_meta( $module_tpl_id, 'tdc_google_fonts_settings', true );
					if ( !empty($google_fonts_ids) ) {
						foreach ( $google_fonts_ids as $gf_id => $font_settings ) {
							if ( !in_array( $gf_id, $extra_google_fonts_ids ) ) {
								$extra_google_fonts_ids[] = $gf_id;
							}
						}
					}

				}

			}
		}

        // process global fonts
		if ( !empty( $extra_google_fonts_ids ) ) {

			$tdc_wm_global_fonts = td_util::get_option('tdc_wm_global_fonts' );
			foreach ( $extra_google_fonts_ids as &$font_id ) {

				if ( strpos( $font_id, '_global' ) !== false ) {
					$font_setting_option_id = str_replace( '_global', '', $font_id );

					if ( !empty( $tdc_wm_global_fonts ) && is_array( $tdc_wm_global_fonts ) ) {
						foreach ( $tdc_wm_global_fonts as $font_option_id => $font_data ) {

							if ( $font_option_id === $font_setting_option_id && !empty( td_fonts::$font_names_google_list[$font_data['key']] ) ) {
								$font_id = $font_data['key'];
							}

						}
					}

				}

			}

		}

		return $extra_google_fonts_ids;

	}


    static function get_template_id( $post = null ) {

        global $wp_query;

        $template_id = null;

        if ( empty( $post ) ) {
            global $post;
        }

        // is_single should not have been working for attachments! But it does.
        if ( is_single() && ! is_attachment() && $post instanceof WP_Post && 'tdb_templates' !== $post->post_type && 'product' !== $post->post_type ) {

	        // read the per post single_template
	        $post_meta_values = td_util::get_post_meta_array( $post->ID, 'td_post_theme_settings' );

	        // check if the current post/CPT has a specific template set
	        if ( ! empty( $post_meta_values[ 'td_post_template' ] ) ) {

		        $td_site_post_template = $post_meta_values[ 'td_post_template' ];

		        if ( td_global::is_tdb_template( $td_site_post_template, true ) ) {
			        $template_id = td_global::tdb_get_template_id( $td_site_post_template );
		        }

            // we don't, so check next what type of post we are dealing with;
	        } else if ( 'post' !== $post->post_type ) {

                // we have a CPT

                $lang = '';
                if ( class_exists('SitePress', false ) ) {
                    global $sitepress;
                    $sitepress_settings = $sitepress->get_settings();
                    if ( isset( $sitepress_settings['custom_posts_sync_option'][ 'tdb_templates'] ) ) {
                        $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                        if ( 1 === $translation_mode ) {
                            $lang = $sitepress->get_current_language();
                        }
                    }
                }

                $td_cpt = td_util::get_option('td_cpt');
                $option_id = 'td_default_site_post_template' . $lang;

                $default_template_id = !empty( $td_cpt[$post->post_type][$option_id] ) ? $td_cpt[$post->post_type][$option_id] : '';

                if ( td_global::is_tdb_template( $default_template_id, true ) ) {
                    $template_id = td_global::tdb_get_template_id( $default_template_id );
                }

            } else {

                // we have a regular post

		        $td_primary_category = td_global::get_primary_category_id();

		        if ( ! empty( $td_primary_category ) ) {

			        $post_category_template = td_util::get_category_option( $td_primary_category, 'tdb_post_category_template' );

			        // make sure the template exists, maybe it was deleted or something
			        if ( td_global::is_tdb_template( $post_category_template, true ) ) {
				        $template_id = td_global::tdb_get_template_id( $post_category_template );
			        }
		        }

		        if ( empty( $template_id ) ) {

			        $option_id = 'td_default_site_post_template';
			        if ( class_exists( 'SitePress', false ) ) {
				        global $sitepress;
				        $sitepress_settings = $sitepress->get_settings();
				        if ( isset( $sitepress_settings[ 'custom_posts_sync_option' ][ 'tdb_templates' ] ) ) {
					        $translation_mode = (int) $sitepress_settings[ 'custom_posts_sync_option' ][ 'tdb_templates' ];
					        if ( 1 === $translation_mode ) {
						        $option_id .= $sitepress->get_current_language();
					        }
				        }
			        }
			        $td_default_site_post_template = td_util::get_option( $option_id );

			        if ( ! empty( $td_default_site_post_template ) && td_global::is_tdb_template( $td_default_site_post_template, true ) ) {
				        $template_id = td_global::tdb_get_template_id( $td_default_site_post_template );
			        }
		        }
	        }

        } else if ( function_exists('is_product_category') && is_product_category() ) {

            $lang = '';
            if (class_exists('SitePress', false)) {
                global $sitepress;
                $sitepress_settings = $sitepress->get_settings();
                if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                    $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                    if (1 === $translation_mode) {
                        $lang = $sitepress->get_current_language();
                    }
                }
            }

            $tdb_option_key = 'tdb_woo_archive_template' . $lang;
            $queried_object = get_queried_object();

            if ( $queried_object instanceof WP_Term ) {
                $term_id = $queried_object->term_id;
                $tdb_woo_archive_template = get_term_meta( $term_id, $tdb_option_key, true );

                if ( empty( $tdb_woo_archive_template ) ) {
                    $default_template_id = td_util::get_option( $tdb_option_key );

                    if ( td_global::is_tdb_template( $default_template_id, true ) ) {
                        $template_id = td_global::tdb_get_template_id( $default_template_id );
                    }

                } else {
                    $template_id = td_global::tdb_get_template_id($tdb_woo_archive_template);
                }
            }

        } else if ( function_exists('is_product_tag') && is_product_tag() ) {

            $lang = '';
            if (class_exists('SitePress', false)) {
                global $sitepress;
                $sitepress_settings = $sitepress->get_settings();
                if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                    $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                    if (1 === $translation_mode) {
                        $lang = $sitepress->get_current_language();
                    }
                }
            }

            $tdb_option_key = 'tdb_woo_archive_tag_template' . $lang;
            $queried_object = get_queried_object();

            if ( $queried_object instanceof WP_Term ) {
                $term_id = $queried_object->term_id;
                $tdb_woo_archive_tag_template = get_term_meta( $term_id, $tdb_option_key, true );

                if ( empty( $tdb_woo_archive_tag_template ) ) {
                    $default_template_id = td_util::get_option( $tdb_option_key );

                    if ( td_global::is_tdb_template( $default_template_id, true ) ) {
                        $template_id = td_global::tdb_get_template_id( $default_template_id );
                    }

                } else {
                    $template_id = td_global::tdb_get_template_id($tdb_woo_archive_tag_template);
                }
            }

        } else if (
                $wp_query->get( 'wc_query' ) &&
                is_tax() &&
                !empty( get_queried_object() ) &&
                function_exists( 'taxonomy_is_product_attribute' ) &&
                taxonomy_is_product_attribute( get_queried_object()->taxonomy )
        ) {

            $lang = '';
            if (class_exists('SitePress', false)) {
                global $sitepress;
                $sitepress_settings = $sitepress->get_settings();
                if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                    $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                    if (1 === $translation_mode) {
                        $lang = $sitepress->get_current_language();
                    }
                }
            }

            $tdb_option_key = 'tdb_woo_archive_attribute_template' . $lang;
            $queried_object = get_queried_object();

            if ( $queried_object instanceof WP_Term ) {
                $term_id = $queried_object->term_id;
                $tdb_woo_archive_attribute_template = get_term_meta( $term_id, $tdb_option_key, true );

                if ( empty( $tdb_woo_archive_attribute_template ) ) {
                    $default_template_id = td_util::get_option( $tdb_option_key );

                    if ( td_global::is_tdb_template( $default_template_id, true ) ) {
                        $template_id = td_global::tdb_get_template_id( $default_template_id );
                    }

                } else {
                    $template_id = td_global::tdb_get_template_id( $tdb_woo_archive_attribute_template );
                }
            }

        } else if ( is_category() ) {

            $current_category_id  = get_query_var( 'cat' );
            $current_category_obj = get_category( $current_category_id );

            if ( $current_category_obj instanceof WP_Term ) {

                // read the individual cat template
                $tdb_individual_category_template = td_util::get_category_option( $current_category_id, 'tdb_category_template' );

                if ( empty( $tdb_individual_category_template ) ) {

                    $option_id = 'tdb_category_template';
                    if (class_exists('SitePress', false )) {
                        global $sitepress;
                        $sitepress_settings = $sitepress->get_settings();
                        if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                            $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                            if (1 === $translation_mode) {
                                $option_id .= $sitepress->get_current_language();
                            }
                        }
                    }

                    // read the global template
                    $tdb_category_template = td_options::get( $option_id );

                    if ( td_global::is_tdb_template( $tdb_category_template, true ) ) {
                        $template_id = td_global::tdb_get_template_id( $tdb_category_template );
                    }
                }

                if ( td_global::is_tdb_template( $tdb_individual_category_template, true ) ) {
                    $template_id = td_global::tdb_get_template_id( $tdb_individual_category_template );
                }
            }

        } else if ( is_author() ) {

	        $author_query_var = get_query_var( 'author' );
	        if ( ! empty( $author_query_var ) ) {

		        $option_id = 'tdb_author_templates';
		        if ( class_exists( 'SitePress', false ) ) {
			        global $sitepress;
			        $sitepress_settings = $sitepress->get_settings();
			        if ( isset( $sitepress_settings[ 'custom_posts_sync_option' ][ 'tdb_templates' ] ) ) {
				        $translation_mode = (int) $sitepress_settings[ 'custom_posts_sync_option' ][ 'tdb_templates' ];
				        if ( 1 === $translation_mode ) {
					        $option_id .= $sitepress->get_current_language();
				        }
			        }
		        }


		        // user templates
		        $tdb_author_templates = td_util::get_option( $option_id );

		        if ( ! empty( $tdb_author_templates[ $author_query_var ] ) && td_global::is_tdb_template( $tdb_author_templates[ $author_query_var ], true ) ) {

			        $template_id = td_global::tdb_get_template_id( $tdb_author_templates[ $author_query_var ] );

		        } else {

			        $option_id = 'tdb_author_template';
			        if ( class_exists( 'SitePress', false ) ) {
				        global $sitepress;
				        $sitepress_settings = $sitepress->get_settings();
				        if ( isset( $sitepress_settings[ 'custom_posts_sync_option' ][ 'tdb_templates' ] ) ) {
					        $translation_mode = (int) $sitepress_settings[ 'custom_posts_sync_option' ][ 'tdb_templates' ];
					        if ( 1 === $translation_mode ) {
						        $option_id .= $sitepress->get_current_language();
					        }
				        }
			        }

			        $template_id = td_options::get( $option_id );
			        if ( td_global::is_tdb_template( $template_id, true ) ) {
				        $template_id = td_global::tdb_get_template_id( $template_id );
			        }
		        }
	        }

        } else if ( is_search() && $wp_query->get( 'wc_query' ) ) {

            $tdb_template = td_util::get_option( 'tdb_woo_search_archive_template' );
            if ( td_global::is_tdb_template( $tdb_template, true ) ) {
                $template_id = td_global::tdb_get_template_id( $tdb_template );
            }

        } else if ( is_search() ) {

            $option_id = 'tdb_search_template';
            $cpt_search_template_id = null;

            $post_type = get_query_var('post_type');
            if ( !empty($post_type) && $post_type !== 'post' ) {
                // CPT search -- look for a CPT-specific template
                $td_cpt = td_util::get_option('td_cpt');
                if ( !empty( $td_cpt[$post_type]['search_tpl'] ) ) {
                    $cpt_search_template_id = $td_cpt[$post_type]['search_tpl'];
                }
            }

            if ( !empty($cpt_search_template_id) && td_global::is_tdb_template( $cpt_search_template_id, true ) ) {
                $template_id = td_global::tdb_get_template_id( $cpt_search_template_id );
            } else {
                // fallback to global search template
                if (class_exists('SitePress', false )) {
                    global $sitepress;
                    $sitepress_settings = $sitepress->get_settings();
                    if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                        $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                        if (1 === $translation_mode) {
                            $option_id .= $sitepress->get_current_language();
                        }
                    }
                }

                $tdb_search_template = td_options::get( $option_id );
                if ( td_global::is_tdb_template( $tdb_search_template, true ) ) {
                    $template_id = td_global::tdb_get_template_id( $tdb_search_template );
                }
            }
        } else if ( is_date() ) {

            $option_id = 'tdb_date_template';
            if (class_exists('SitePress', false )) {
                global $sitepress;
                $sitepress_settings = $sitepress->get_settings();
                if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                    $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                    if (1 === $translation_mode) {
                        $option_id .= $sitepress->get_current_language();
                    }
                }
            }

            // read template
            $tdb_date_template = td_options::get( $option_id );
            if ( td_global::is_tdb_template( $tdb_date_template, true ) ) {
                $template_id = td_global::tdb_get_template_id( $tdb_date_template );
            }

        } else if ( is_tag() ) {

            $template_found = false;

            $option_id = 'tdb_tag_templates';
            if ( class_exists( 'SitePress', false ) ) {
                global $sitepress;
                $sitepress_settings = $sitepress->get_settings();
                if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                    $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                    if (1 === $translation_mode) {
                        $option_id .= $sitepress->get_current_language();
                    }
                }
            }

            $tdb_tag_templates = td_options::get( $option_id );
            if ( is_array( $tdb_tag_templates ) ) {
                $queried_object = get_queried_object();
                if ( $queried_object ) {
                    foreach ( $tdb_tag_templates as $tdb_tag_template_id => $tags ) {
                        if ( false !== strpos( $tags, $queried_object->slug ) && td_global::is_tdb_template( $tdb_tag_template_id, true ) ) {
                            $template_found = true;
                            $template_id = td_global::tdb_get_template_id( $tdb_tag_template_id );
                        }
                    }
                }
            }

            if ( ! $template_found ) {

                $option_id = 'tdb_tag_template';
                if ( class_exists( 'SitePress', false ) ) {
                    global $sitepress;
                    $sitepress_settings = $sitepress->get_settings();
                    if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                        $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                        if (1 === $translation_mode) {
                            $option_id .= $sitepress->get_current_language();
                        }
                    }
                }

                // read the default tag template
                $tdb_tag_template = td_options::get( $option_id );

                if ( td_global::is_tdb_template( $tdb_tag_template, true ) ) {
                    $template_id = td_global::tdb_get_template_id( $tdb_tag_template );
                }
            }


        } else if ( is_attachment() ) {

            $option_id = 'tdb_attachment_template';
            if (class_exists('SitePress', false )) {
                global $sitepress;
                $sitepress_settings = $sitepress->get_settings();
                if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                    $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                    if (1 === $translation_mode) {
                        $option_id .= $sitepress->get_current_language();
                    }
                }
            }

            // read template
            $tdb_tag_template = td_options::get( $option_id );
            if ( td_global::is_tdb_template( $tdb_tag_template, true ) ) {
                $template_id = td_global::tdb_get_template_id( $tdb_tag_template );
            }

        } else if ( is_404() ) {

            $option_id = 'tdb_404_template';
            if (class_exists('SitePress', false )) {
                global $sitepress;
                $sitepress_settings = $sitepress->get_settings();
                if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                    $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                    if (1 === $translation_mode) {
                        $option_id .= $sitepress->get_current_language();
                    }
                }
            }

            // read template
            $tdb_404_template = td_options::get( $option_id );
            if ( td_global::is_tdb_template( $tdb_404_template, true ) ) {
                $template_id = td_global::tdb_get_template_id( $tdb_404_template );
            }

            if ( empty( $template_id ) ) {

                // Get general header template
                $tdb_header_template = td_util::get_option( 'tdb_header_template' );
                if ( td_global::is_tdb_template( $tdb_header_template, true ) ) {
                    $template_id = td_global::tdb_get_template_id( $tdb_header_template );
                }
            }

        } else if (function_exists('is_product') && is_product()) {

	        // read the per post single_template
	        $post_meta_values = td_util::get_post_meta_array( $post->ID, 'td_post_theme_settings' );

	        // if we don't have any single_template set on this post, try to load the default global setting
	        if ( ! empty( $post_meta_values[ 'td_post_template' ] ) ) {

		        $td_site_post_template = $post_meta_values[ 'td_post_template' ];

		        if ( td_global::is_tdb_template( $td_site_post_template, true ) ) {
			        $template_id = td_global::tdb_get_template_id( $td_site_post_template );
		        }

	        } else {

		        $option_id = 'tdb_woo_product_template';
                if (class_exists('SitePress', false )) {
                    global $sitepress;
                    $sitepress_settings = $sitepress->get_settings();
                    if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                        $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                        if (1 === $translation_mode) {
                            $option_id .= $sitepress->get_current_language();
                        }
                    }
                }

		        $td_default_site_post_template = td_util::get_option( $option_id );

		        if ( ! empty( $td_default_site_post_template ) && td_global::is_tdb_template( $td_default_site_post_template, true ) ) {
			        $template_id = td_global::tdb_get_template_id( $td_default_site_post_template );
		        }
	        }

        } else if (function_exists('is_shop') && is_shop()) {

            $option_id = 'tdb_woo_shop_base_template';
            if (class_exists('SitePress', false )) {
                global $sitepress;
                $sitepress_settings = $sitepress->get_settings();
                if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                    $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                    if (1 === $translation_mode) {
                        $option_id .= $sitepress->get_current_language();
                    }
                }
            }

            $tdb_template = td_util::get_option( $option_id );

            if ( td_global::is_tdb_template( $tdb_template, true ) ) {
                $template_id = td_global::tdb_get_template_id( $tdb_template );
            }
	    } else if ( is_tax() ) {

            $option_id = 'tdb_category_template';
            if ( class_exists('SitePress', false ) ) {
                global $sitepress;
                $sitepress_settings = $sitepress->get_settings();
                if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
                    $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                    if (1 === $translation_mode) {
                        $option_id .= $sitepress->get_current_language();
                    }
                }
            }

            $td_cpt_tax = td_util::get_option( 'td_cpt_tax' );
            $queried_object = get_queried_object();

            if ( $queried_object instanceof WP_Term ) {

                $tax_name = $queried_object->taxonomy;
	            $default_template_id = !empty( $td_cpt_tax[$tax_name][$option_id] ) ? $td_cpt_tax[$tax_name][$option_id] : '';

	            if ( td_global::is_tdb_template( $default_template_id, true ) ) {
		            $template_id = td_global::tdb_get_template_id($default_template_id);
	            }

            }
	    } else if ( is_post_type_archive() ) {

            $lang = '';
            if ( class_exists('SitePress', false ) ) {
                global $sitepress;
                $sitepress_settings = $sitepress->get_settings();
                if ( isset($sitepress_settings['custom_posts_sync_option']['tdb_templates']) ) {
                    $translation_mode = (int) $sitepress_settings['custom_posts_sync_option']['tdb_templates'];
                    if ( 1 === $translation_mode ) {
                        $lang = $sitepress->get_current_language();
                    }
                }
            }

            // get cpt object
            $post_type_object = get_queried_object();

            $tdb_post_type_archive_template = '';
            if ( $post_type_object ) {

                // get cpt settings
                $td_cpt = td_util::get_option('td_cpt');

                // read cpt archive template
                if ( !empty( $td_cpt[$post_type_object->name]['archive_tpl' . $lang] ) ) {
                    $tdb_post_type_archive_template = $td_cpt[$post_type_object->name]['archive_tpl' . $lang];
                }

            }

            // set tpl id
            if ( td_global::is_tdb_template( $tdb_post_type_archive_template, true ) ) {
                $template_id = td_global::tdb_get_template_id( $tdb_post_type_archive_template );
            }

        }

        return $template_id;
    }



    static function check_footer( $post = null ) {

        if ( empty( $post ) ) {
            global $post;
        }

        $template_id = self::get_template_id( $post );

        if ( is_404() && empty( $template_id )) {

            // Get general footer template
            $tdb_footer_template = td_util::get_option( 'tdb_footer_template' );
            if ( td_global::is_tdb_template( $tdb_footer_template, true ) ) {
                $template_id = td_global::tdb_get_template_id( $tdb_footer_template );
            }
        }


        if ( ! empty( $template_id ) || is_page() ) {
            add_filter('body_class', function($classes) {
                $classes[] = 'tdb-template';
                return $classes;
            });
        }



        if ( ! empty( $template_id ) ) {
            // Take footer template if from the associated template used to render the current post
            $ref_id = $template_id;
        } else if ( $post instanceof WP_Post ) {
            // Take footer template if from the current post itself
            $ref_id = $post->ID;
        }


        if ( !empty($ref_id ) ) {
            $tdc_footer_template_id = get_post_meta( $ref_id, 'tdc_footer_template_id', true );
            $tdb_template_type      = get_post_meta( $ref_id, 'tdb_template_type', true );

            // footer templates must have set as footer their contents, to allow editing them in composer
            if ('footer' === $tdb_template_type) {
                update_post_meta( $ref_id, 'tdc_footer_template_id', $ref_id );
                $tdc_footer_template_id = $ref_id;
            }
        }






        if ( !empty($ref_id ) ) {
	        $meta_is_mobile_template = get_post_meta( $ref_id, 'tdc_is_mobile_template', true );
	        if ( ( ! empty( $meta_is_mobile_template ) && '1' === $meta_is_mobile_template ) ) {
		        tdc_state::set_is_mobile_template(true);
	        }
        }


        if ( ! tdc_state::is_mobile_template() ) {

            $is_mobile = false;

            if ( class_exists('Mobile_Detect')) {
	            $mobile_detect = new Mobile_Detect();
	            if ( $mobile_detect->isMobile() ) {

	                if ( !empty($ref_id ) ) {
	                    $ref_id = get_post_meta( $ref_id, 'tdc_mobile_template_id', true );
		                if ( !empty($ref_id ) && 'publish' === get_post_status( $ref_id ) ) {
		                    $tdc_footer_template_id = get_post_meta( $ref_id, 'tdc_footer_template_id', true );
		                    $is_mobile = true;
		                }
	                }
	            }
            }

            // Remove content for footer templates, because their content is shown as their associated footer template
            if ( td_util::tdc_is_live_editor_iframe() && ! empty( $tdb_template_type ) && 'footer' === $tdb_template_type ) {
                add_filter( 'the_content', function( $content ) {
                    return '<div class="tdc-dummy-content" style="height: 1500px; background-color: #f9f9f9; background-image: url(\'data:image/png;base64,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\')"></div>';
                });
            }

            if ( empty( $tdc_footer_template_id ) ) {

                // Show the global template if it is set
                // Check the status of the footer template and if it's not 'publish', show the legacy global template
                // Do nothing for 'footer' type templates, because they don't need to use global footer template

                if ( ( isset( $tdb_template_type ) && 'footer' !== $tdb_template_type ) || ! isset( $tdb_template_type ) ) {

                    $global_footer_template_id = td_api_footer_template::get_footer_template_id( $is_mobile );

                    if ( ! empty( $global_footer_template_id ) && td_global::is_tdb_template( $global_footer_template_id, true ) ) {

                        $global_footer_template_id = td_global::tdb_get_template_id( $global_footer_template_id );
                        self::$footer_template_id  = $global_footer_template_id;

                        $meta_footer_template_content = get_post_field( 'post_content', str_replace( 'tdb_template_', '', $global_footer_template_id ) );
                        if ( ! empty( $meta_footer_template_content ) ) {
                            self::$is_template_footer = true;

                            if ( self::tdc_is_installed() ) {
                                self::$footer_template_content = $meta_footer_template_content;
                            }
                        }
                    }

                    if ( $is_mobile && ! empty( self::$footer_template_id ) ) {
                        $meta_footer_template_content = get_post_field( 'post_content', str_replace( 'tdb_template_', '', self::$footer_template_id ) );
                        if ( ! empty( $meta_footer_template_content ) ) {
                            self::$is_template_footer = true;

                            if ( self::tdc_is_installed() ) {
                                self::$footer_template_content = $meta_footer_template_content;
                            }
                        } else {
                            self::$is_template_footer = false;
                        }
                    }
                }

            } else {

                if ( 'no_footer' === $tdc_footer_template_id ) {

                    self::$is_no_footer = true;

                } else {

                    // Check the status of the footer template and if it's not 'publish', show the global template
                    $post_status = get_post_status( $tdc_footer_template_id );

                    if ( 'publish' === $post_status ) {

                        self::$footer_template_id     = $tdc_footer_template_id;
                        $meta_footer_template_content = get_post_field( 'post_content', $tdc_footer_template_id );

                        self::$is_template_footer      = true;

                        if ( self::tdc_is_installed() ) {
                            self::$footer_template_content = $meta_footer_template_content;
                        }


                        if ( $is_mobile && ! empty( self::$footer_template_id ) ) {
                            $meta_footer_template_content = get_post_field( 'post_content', self::$footer_template_id );
                            if ( ! empty( $meta_footer_template_content ) ) {

                                self::$is_template_footer      = true;

                                if ( self::tdc_is_installed() ) {
                                    self::$footer_template_content = $meta_footer_template_content;
                                }
                            } else {
                                self::$is_template_footer = false;
                            }
                        }

                    } else {


                        // Show the global template if it is set
                        // Check the status of the footer template and if it's not 'publish', show the legacy global template
                        // Do nothing for 'footer' type templates, because they don't need to use global footer template

                        if ( isset( $tdb_template_type ) && 'footer' !== $tdb_template_type ) {

                            $global_footer_template_id = td_api_footer_template::get_footer_template_id( $is_mobile );

                            if ( ! empty( $global_footer_template_id ) && td_global::is_tdb_template( $global_footer_template_id, true ) ) {

                                $global_footer_template_id = td_global::tdb_get_template_id( $global_footer_template_id );
                                self::$footer_template_id  = $global_footer_template_id;

                                $meta_footer_template_content = get_post_field( 'post_content', str_replace( 'tdb_template_', '', $global_footer_template_id ) );
                                if ( ! empty( $meta_footer_template_content ) ) {
                                    self::$is_template_footer      = true;

                                    if ( self::tdc_is_installed() ) {
                                        self::$footer_template_content = $meta_footer_template_content;
                                    }
                                }
                            }
                        }
                    }
                }
            }

        } else {

            // Remove content for footer templates, because their content is shown as their associated footer template
            if ( td_util::tdc_is_live_editor_iframe() && ! empty( $tdb_template_type ) && 'footer' === $tdb_template_type ) {
                add_filter( 'the_content', function( $content ) {
                    return '<div class="tdc-dummy-content" style="height: 1500px; background-color: #f9f9f9; background-image: url(\'data:image/png;base64,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\')"></div>';
                });
            }


            if ( empty( $tdc_footer_template_id ) ) {

                // Show the global template if it is set
                // Check the status of the footer template and if it's not 'publish', show the legacy global template
                // Do nothing for 'footer' type templates, because they don't need to use global footer template

                if ( ( isset( $tdb_template_type ) && 'footer' !== $tdb_template_type ) || ! isset( $tdb_template_type ) ) {

                    $global_footer_template_id = td_api_footer_template::get_footer_template_id( true );

                    if ( ! empty( $global_footer_template_id ) && td_global::is_tdb_template( $global_footer_template_id, true ) ) {

                        $global_footer_template_id = td_global::tdb_get_template_id( $global_footer_template_id );
                        self::$footer_template_id  = $global_footer_template_id;

                        $meta_footer_template_content = get_post_field( 'post_content', str_replace( 'tdb_template_', '', $global_footer_template_id ) );
                        if ( ! empty( $meta_footer_template_content ) ) {
                            self::$is_template_footer = true;

                            if ( self::tdc_is_installed() ) {
                                self::$footer_template_content = $meta_footer_template_content;
                            }
                        }
                    }



                    if ( ! empty( self::$footer_template_id ) ) {
                        $meta_footer_template_content = get_post_field( 'post_content', str_replace( 'tdb_template_', '', self::$footer_template_id ) );
                        if ( ! empty( $meta_footer_template_content ) ) {
                            self::$is_template_footer = true;

                            if ( self::tdc_is_installed() ) {
                                self::$footer_template_content = $meta_footer_template_content;
                            }
                        } else {
                            self::$is_template_footer = false;
                        }
                    }
                }

            } else {

                if ( 'no_footer' === $tdc_footer_template_id ) {

                    self::$is_no_footer = true;

                } else {

                    // Check the status of the footer template and if it's not 'publish', show the global template
                    $post_status = get_post_status( $tdc_footer_template_id );

                    if ( 'publish' === $post_status ) {

                        self::$footer_template_id     = $tdc_footer_template_id;
                        $meta_footer_template_content = get_post_field( 'post_content', $tdc_footer_template_id );

                        self::$is_template_footer      = true;

                        if ( self::tdc_is_installed() ) {
                            self::$footer_template_content = $meta_footer_template_content;
                        }

                    } else {


                        // Show the global template if it is set
                        // Check the status of the footer template and if it's not 'publish', show the legacy global template
                        // Do nothing for 'footer' type templates, because they don't need to use global footer template

                        if ( isset( $tdb_template_type ) && 'footer' !== $tdb_template_type ) {

                            $global_footer_template_id = td_api_footer_template::get_footer_template_id( true );

                            if ( ! empty( $global_footer_template_id ) && td_global::is_tdb_template( $global_footer_template_id, true ) ) {

                                $global_footer_template_id = td_global::tdb_get_template_id( $global_footer_template_id );
                                self::$footer_template_id  = $global_footer_template_id;

                                $meta_footer_template_content = get_post_field( 'post_content', str_replace( 'tdb_template_', '', $global_footer_template_id ) );
                                if ( ! empty( $meta_footer_template_content ) ) {
                                    self::$is_template_footer      = true;

                                    if ( self::tdc_is_installed() ) {
                                        self::$footer_template_content = $meta_footer_template_content;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }




        if ( self::$is_template_footer ) {

            add_filter( 'body_class', function( $classes ) {
                $classes[] = ' tdc-footer-template';
                return $classes;
            });

            // old method to get all used google fonts
            add_filter( 'td_filter_google_fonts', function( $extra_google_fonts_ids ) {

                $footer_template_content = td_util::get_footer_template_content();

                if ( td_util::tdc_is_installed() && ! empty($footer_template_content) ) {
                    $google_fonts_ids = tdc_util::get_content_google_fonts_ids( $footer_template_content );
                    $extra_google_fonts_ids = array_unique( array_merge( $extra_google_fonts_ids, $google_fonts_ids ) );
                }

                return $extra_google_fonts_ids;
            });

            // new method to get all used google fonts
            add_filter( 'td_filter_google_fonts_settings', function( $extra_google_fonts_ids ) {

                $footer_template_id = td_util::get_footer_template_id();

                if ( td_util::tdc_is_installed() ) {
                    $new_meta_exists = metadata_exists( 'post', $footer_template_id, 'tdc_google_fonts_settings' );

                    if ( $new_meta_exists ) {
	                    $google_fonts_ids = get_post_meta( $footer_template_id, 'tdc_google_fonts_settings', true );

	                    foreach ( $google_fonts_ids as $google_fonts_id => $font_settings ) {
		                    if ( array_key_exists( $google_fonts_id, $extra_google_fonts_ids ) && is_array( $extra_google_fonts_ids[ $google_fonts_id ] ) ) {
			                    $extra_google_fonts_ids[ $google_fonts_id ] = array_unique( array_merge( $extra_google_fonts_ids[ $google_fonts_id ], $google_fonts_ids[ $google_fonts_id ] ) );
		                    } else {
			                    $extra_google_fonts_ids[ $google_fonts_id ] = $font_settings;
		                    }
	                    }
                    }
                }

                // Also retrieve the fonts used by Ai Builder component styles.
                if ( defined('TD_AIB_PLUGIN_NAME') ) {
                    $aib_component_style_fonts = \TagDiv\AiBuilder\Typography::get_component_style_fonts_from_content(self::$footer_template_content);

                    foreach ( $aib_component_style_fonts as $font_id => $font_weights ) {
                        if ( array_key_exists($font_id, $extra_google_fonts_ids) && is_array($extra_google_fonts_ids[$font_id]) ) {
                            $extra_google_fonts_ids[$font_id] = array_unique( array_merge( $extra_google_fonts_ids[$font_id], $font_weights) );
                        } else {
                            $extra_google_fonts_ids[$font_id] = $font_weights;
                        }
                    }
                }

                return $extra_google_fonts_ids;
            });

            // extract icon fonts from footer template content
            add_filter('td_filter_icon_fonts', function( $extra_icon_fonts = array()) {
                $footer_template_id = td_util::get_footer_template_id();

                if ( td_util::tdc_is_installed() ) {
                    $footer_icon_fonts = get_post_meta( $footer_template_id, 'tdc_icon_fonts', true );

                    if ( ! empty( $footer_icon_fonts ) && is_array( $footer_icon_fonts ) ) {
                        foreach ( $footer_icon_fonts as $font_id => $font_settings ) {
                            if ( empty($extra_icon_fonts)) {
                                $extra_icon_fonts = array();
                            }
                            $extra_icon_fonts[ $font_id ] = $font_settings;
                        }
                    }
                }

                return $extra_icon_fonts;
            });

        }

        if ( self::$is_no_footer ) {
            add_filter( 'body_class', function( $classes ) {
                $classes[] = ' tdc-no-footer';
                return $classes;
            });
        }

    }

    static function is_template_footer() {
        return self::$is_template_footer;
    }

    static function is_no_footer() {
        return self::$is_no_footer;
    }

    //returns the $class if the variable is not empty or false
    static function if_show($variable, $class) {
        if ($variable !== false and !empty($variable)) {
            return ' ' . $class;
        } else {
            return '';
        }
    }

    //returns the class if the variable is empty or false
    static function if_not_show($variable, $class){
        if ($variable === false or empty($variable)) {
            return ' ' . $class;
        } else {
            return '';
        }
    }

	static function get_shortcodes_with_icons() {
		if ( is_null( self::$shortcodes_with_icons ) ) {
			self::$shortcodes_with_icons = array();

			if ( td_util::tdc_is_installed() ) {
				$mapped_shortcodes = tdc_mapper::get_mapped_shortcodes();
					foreach ( $mapped_shortcodes as $mapped_shortcode ) {
					if ( isset( $mapped_shortcode['params'] ) && isset( $mapped_shortcode['base'] ) ) {
						foreach ( $mapped_shortcode['params'] as $shortcode_param ) {
							if ( isset( $shortcode_param['type'] ) && 'icon' === $shortcode_param['type'] ) {
								self::$shortcodes_with_icons[$mapped_shortcode['base']] = $mapped_shortcode['params'];
								break;
							}
						}
					}
				}
			}
		}
		return self::$shortcodes_with_icons;
	}


    /**
     * gets a category option for a specific category id.
     * - We have no update method because the panel has it's own update
     *   implementation in @see td_panel_data_source::update_category
     * - the panel uses this function to read settings for specific categories
     * - it is used also in the entire theme
     * @param $category_id
     * @param $option_id
     * @return string
     */
    static function get_category_option($category_id, $option_id) {
    	$td_options = td_options::get_all();

        if (isset($td_options['category_options'][$category_id][$option_id])) {
            return $td_options['category_options'][$category_id][$option_id];
        } else {
            return '';
        }
    }



    /**
     * gets a custom post type option for a specific post type name.
     * - We have no update method because the panel has it's own update
     *   implementation in @see td_panel_data_source::update_td_cpt
     * - the panel uses this function to read settings for specific categories
     * - it is used also in the entire theme
     * @param $custom_post_type
     * @param $option_id
     * @return string
     */
    static function get_ctp_option($custom_post_type, $option_id) {
	    $td_options = td_options::get_all();

	    if (isset($td_options['td_cpt'][$custom_post_type][$option_id])) {
            return $td_options['td_cpt'][$custom_post_type][$option_id];
        } else {
            return '';
        }
    }

    /**
     * gets a custom taxonomy option for a specific taxonomy.
     * - We have no update method because the panel has it's own update
     *   implementation in @see td_panel_data_source::update_td_taxonomy
     * - the panel uses this function to read settings for specific categories
     * - it is used also in the entire theme
     * @param $taxonomy_name
     * @param $option_id
     * @return string
     */
    static function get_taxonomy_option($taxonomy_name, $option_id) {
	    $td_options = td_options::get_all();

        if (isset($td_options['td_taxonomy'][$taxonomy_name][$option_id])) {
            return $td_options['td_taxonomy'][$taxonomy_name][$option_id];
        } else {
            return '';
        }
    }





    /**
     * reads an ad from our data
     * @param $ad_position_id - header / sidebar etc...
     * @return string
     */
    static function get_td_ads($ad_position_id) {
	    $td_options = td_options::get_all();

        //print_r(td_global::$td_options);
        if (isset($td_options['td_ads'][$ad_position_id])) {
            return $td_options['td_ads'];
        } else {
            return '';
        }
    }


    /**
     * Checks to see if a adspot is enabled (ex: it has ad code in it)
     * @param $ad_spot_id
     * @return bool
     */
    static function is_ad_spot_enabled($ad_spot_id) {
	    $td_options = td_options::get_all();

        if (empty($td_options['td_ads'][$ad_spot_id]['ad_code'])) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * reads a theme option from wp
     * @param $optionName
     * @param string $default_value
     * @return string|array
     */
    static function get_option($optionName, $default_value = '') {
    	return td_options::get($optionName, $default_value);
    }

    //updates a theme option !!!! sa updateze globala td_util::$td_options
    static function update_option($optionName, $newValue) {
		td_options::update($optionName, $newValue);
    }




    /**
     * Used only on slide big to cut the title to make it wrap
     *
     * @param $cut_parms
     * @param $title
     * @return string
     */
    static function cut_title($cut_parms, $title) {
        //trim and get the excerpt
        $title = trim($title);
        $title = td_util::excerpt($title,$cut_parms['excerpt']);

        //get an array of chars
        $title_chars = str_split($title);
        //$title_chars = preg_split('/(?=(.{16})*$)/u', $title);

        $buffy = '';
        $current_char_on_line = 0;
        $has_to_cut = false; //when true, the string will be cut

        foreach ($title_chars as $title_char) {
            //check if we reached the limit
            if ($cut_parms['char_per_line'] == $current_char_on_line) {
                $has_to_cut = true;
                $current_char_on_line = 0;
            } else {
                $current_char_on_line++;
            }

            if ($title_char == ' ' and $has_to_cut === true) {
                //we have to cut, it's a white space so we ignore it (not added to buffy)
                $buffy .= $cut_parms['line_wrap_end'] . $cut_parms['line_wrap_start'];
                $has_to_cut = false;
            } else {
                //normal loop
                $buffy .= $title_char;
            }

        }

        //wrap the string
        return $cut_parms['line_wrap_start'] . $buffy . $cut_parms['line_wrap_end'];
    }


    /*
     * gets the blog page url (only if the blog page is configured in theme customizer)
     */
    static function get_home_url() {
        if( get_option('show_on_front') == 'page') {
            $posts_page_id = get_option( 'page_for_posts');
            return esc_url(get_permalink($posts_page_id));
        } else {
            return false;
        }
    }

    static function browser_supports_webp() {
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false) {
            return true;
        }
        return false;
    }


    //gets the sidebar setting or default if no sidebar is selected for a specific setting id
    static function show_sidebar($template_id) {
        $tds_cur_sidebar = td_util::get_option('tds_' . $template_id . '_sidebar');
        if (!empty($tds_cur_sidebar)) {
            dynamic_sidebar($tds_cur_sidebar);
        } else {
            //show default
            if (!dynamic_sidebar(TD_THEME_NAME . ' default')) {
                ?>
                <!-- .no sidebar -->
                <?php
            }
        }
    }


    static function get_image_attachment_data($post_id, $size = 'td_180x135', $count = 1 ) {//'thumbnail'
        $objMeta = array();
        $meta = '';// (stdClass)
        $args = array(
            'numberposts' => $count,
            'post_parent' => $post_id,
            'post_type' => 'attachment',
            'nopaging' => false,
            'post_mime_type' => 'image',
            'order' => 'ASC', // change this to reverse the order
            'orderby' => 'menu_order ID', // select which type of sorting
            'post_status' => 'any'
        );

        $attachments = get_children($args);

        if ($attachments) {
            foreach ($attachments as $attachment) {
                $meta = new stdClass();
                $meta->ID = $attachment->ID;
                $meta->title = $attachment->post_title;
                $meta->caption = $attachment->post_excerpt;
                $meta->description = $attachment->post_content;
                $meta->alt = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);

                // Image properties
                $props = wp_get_attachment_image_src( $attachment->ID, $size, false );

                $meta->properties['url'] = $props[0];
                $meta->properties['width'] = $props[1];
                $meta->properties['height'] = $props[2];

                $objMeta[] = $meta;
            }

            return ( count( $attachments ) == 1 ) ? $meta : $objMeta;
        }
    }


    //converts a sidebar name to an id that can be used by word press
    /**
     * !!!! https://github.com/opradu/newspaper/issues/630
     * !!!! the name has issues with multiple spaces, one after another:  "  " -> "--" wp has problems with -- in name
     * @param $sidebar_name
     * @return string
     */
    static function sidebar_name_to_id($sidebar_name) {
        $clean_name = str_replace(array(' '), '-', trim($sidebar_name));
        $clean_name = str_replace(array("'", '"'), '', trim($clean_name));
        return strtolower($clean_name);
    }


	/**
	 * used by the css compiler in /includes/app/td_css_generator.php on 010
	 * @param $hex
	 * @param $steps
	 *
	 * @return string
	 */
    static function adjustBrightness($hex, $steps) {
        // Steps should be between -255 and 255. Negative = darker, positive = lighter
        $steps = max(-255, min(255, $steps));

        // Check if the color received is a css variable
        if( strpos($hex, 'var') !== false ) {
            // extract the variable name
            $hex_var = str_replace('-', '_', str_replace(array('var(--', ')'), array('', ''), $hex));

            // get the global colors list
            $tdc_wm_global_colors = td_util::get_option('tdc_wm_global_colors');

            // bail if global colors list is empty
            if( empty($tdc_wm_global_colors) ) {
                return '';
            }

            // bail if the variable is not in the list
            if( !isset( $tdc_wm_global_colors[$hex_var] ) ) {
                return '';
            }

            // retrieve the color variable's matching hex code
            $hex = $tdc_wm_global_colors[$hex_var]['color'];

            // if the color found is NULL, return nothing
            if( $hex === NULL ) {
                return '';
            }
        }

        // Format the hex color string
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
        }

        // Get decimal values
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));

        // Adjust number of steps and keep it inside 0 to 255
        $r = max(0,min(255,$r + $steps));
        $g = max(0,min(255,$g + $steps));
        $b = max(0,min(255,$b + $steps));

        $r_hex = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
        $g_hex = str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
        $b_hex = str_pad(dechex($b), 2, '0', STR_PAD_LEFT);

        return '#' . $r_hex.$g_hex.$b_hex;
    }


	/**
	 * converts a hex to rgba. Used on 010
	 * @param $hex
	 * @param $opacity
	 *
	 * @return bool|string
	 */
    static function hex2rgba($hex, $opacity) {
        if (empty($hex)) {
            return '';
        }

        // check if the color received is a css variable
        if( strpos($hex, 'var') !== false ) {
            // extract the variable name
            $hex_var = str_replace('-', '_', str_replace(array('var(--', ')'), array('', ''), $hex));

            // get the global colors list
            $tdc_wm_global_colors = td_util::get_option('tdc_wm_global_colors');

            // proceed if the global colors list is not empty
            if( $tdc_wm_global_colors != '' ) {
                // if the color variable received by the function exists in the list
                // then retrieve its matching hex code
                if( isset( $tdc_wm_global_colors[$hex_var] ) ) {
                    $hex = $tdc_wm_global_colors[$hex_var]['color'];

                    // if the color found is NULL, return nothing
                    if( $hex === NULL ) {
                        return '';
                    }
                }
            }
        }

        // if the color received is already of the type rgba, then simply return it
        if( strpos($hex, 'rgba') !== false ) {
            return $hex;
        }

        if ( $hex[0] == '#' ) {
            $hex = substr( $hex, 1 );
        }
        if ( strlen( $hex ) == 6 ) {
            list( $r, $g, $b ) = array( $hex[0] . $hex[1], $hex[2] . $hex[3], $hex[4] . $hex[5] );
        } elseif ( strlen( $hex ) == 3 ) {
            list( $r, $g, $b ) = array( $hex[0] . $hex[0], $hex[1] . $hex[1], $hex[2] . $hex[2] );
        } else {
            return false;
        }
        $r = hexdec( $r );
        $g = hexdec( $g );
        $b = hexdec( $b );
        return "rgba($r, $g, $b, $opacity)";
    }



	/**
	 * converts hex (html) to rga. Used on 010
	 * @param $htmlCode
	 *
	 * @return array
	 */
    static function html2rgb($htmlCode) {
        if($htmlCode[0] == '#') {
            $htmlCode = substr($htmlCode, 1);
        }

        if (strlen($htmlCode) == 3) {
            $htmlCode = $htmlCode[0] . $htmlCode[0] . $htmlCode[1] . $htmlCode[1] . $htmlCode[2] . $htmlCode[2];
        }

        $r = hexdec($htmlCode[0] . $htmlCode[1]);
        $g = hexdec($htmlCode[2] . $htmlCode[3]);
        $b = hexdec($htmlCode[4] . $htmlCode[5]);

        return array($r, $g, $b);
    }

	/**
	 * converts to rga to Hsl. Used on 010
	 * @param $r
	 * @param $g
	 * @param $b
	 *
	 * @return array
	 */
    static function rgb2Hsl( $r, $g, $b ) {
        $oldR = $r;
        $oldG = $g;
        $oldB = $b;

        $r /= 255;
        $g /= 255;
        $b /= 255;

        $max = max( $r, $g, $b );
        $min = min( $r, $g, $b );

        $h = '';
        $s = '';
        $l = ( $max + $min ) / 2;
        $d = $max - $min;

        if( $d == 0 ){
            $h = $s = 0; // achromatic
        } else {
            $s = $d / ( 1 - abs( 2 * $l - 1 ) );

            switch( $max ){
                case $r:
                    $h = 60 * fmod( ( ( $g - $b ) / $d ), 6 );
                    if ($b > $g) {
                        $h += 360;
                    }
                    break;

                case $g:
                    $h = 60 * ( ( $b - $r ) / $d + 2 );
                    break;

                case $b:
                    $h = 60 * ( ( $r - $g ) / $d + 4 );
                    break;
            }
        }

        return array( round( $h, 2 ), round( $s, 2 ), round( $l, 2 ) );
    }

    /**
     * checks for rgba color values
     * @param $rgba
     *
     * @return bool
     */
    static function is_rgba ( $rgba ) {
        if ( strpos($rgba, 'rgba') !== false ) {
            return true;
        }
        return false;
    }

    /**
     * calculate the contrast of a color and return. Used by 011
     * @param $bg - string - background color (ex. #23f100)
     * @param $contrast_limit - integer - contrast limit (ex. 200)
     * @param $color_one - string - returned color (ex. #000)
     * @param $color_two - string - returned color (ex. #fff)
     * @return string - color one or two
     */
    static function readable_colour( $bg, $contrast_limit, $color_one, $color_two ) {

        $r = $g = $b = $a = 0;

        if( preg_match( "/^#([a-f0-9]{2})([a-f0-9]{2})([a-f0-9]{2})([a-f0-9]{2})?$/i", $bg, $matches ) ) {
            // #RRGGBBAA or #RRGGBB.
            $r = hexdec( $matches[1] );
            $g = hexdec( $matches[2] );
            $b = hexdec( $matches[3] );
            $a = isset( $matches[4] ) ? hexdec( $matches[4] ) / 255 : 1;
        } elseif( preg_match( "/^#([a-f0-9]{1})([a-f0-9]{1})([a-f0-9]{1})$/i", $bg, $matches ) ) {
            // #RGB.
            $r = hexdec( $matches[1] . $matches[1] );
            $g = hexdec( $matches[2] . $matches[2] );
            $b = hexdec( $matches[3] . $matches[3] );
        } elseif ( preg_match( "/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d\.]+))?\)$/i", $bg, $matches ) ) {
            // rgba() or rgb().
            $r = $matches[1];
            $g = $matches[2];
            $b = $matches[3];
            $a = isset( $matches[4] ) ? $matches[4] : 1;
        } else {
            // Invalid color format.
            return $bg;
        }

        $contrast = sqrt(
            $r * $r * .241 * $a +
            $g * $g * .691 * $a +
            $b * $b * .068 * $a
        );

        if( $contrast > $contrast_limit ) {
            return $color_one;
        } else {
            return $color_two;
        }

    }



    /**
     * retrieves the matching global color value
     *
     * @param string $variable
     * @return string
     */
    static function get_global_color( $variable ) {

        /* -- Early bail conditions. -- */
        // Bail if the variable entered is empty.
        if ( $variable == '' ) {
            return '';
        }

        // Bail if it's not actually a variable.
        if ( strpos($variable, 'var') === false ) {
            return '';
        }


        /* -- Get the global colors list. -- */
        $tdc_wm_global_colors = td_util::get_option('tdc_wm_global_colors');

        // Bail if the list is empty.
        if ( $tdc_wm_global_colors == '' ) {
            return '';
        }


        /* -- Extract the variable name. -- */
        $variable_name = str_replace('-', '_', str_replace(array('var(--', ')'), array('', ''), $variable));


        /* -- If the variable exists in the global colors list, then -- */
        /* -- retrieve its value. -- */
        if ( empty($tdc_wm_global_colors[$variable_name]) || empty($tdc_wm_global_colors[$variable_name]['color']) ) {
            return '';
        }

        return $tdc_wm_global_colors[$variable_name]['color'];

    }




    /**
     * create $td_authors array in format id_author => display_name_author
     * @return array id_author => display_name_author
     */
    static function create_array_authors() {

        if (is_admin()) {

            //return the cache if available
            if (self::$authors_array_cache != '') {
                return self::$authors_array_cache;
            }

            $td_authors = array();
            $td_return_obj_authors = get_users('role=Administrator');

            $td_authors[' - No author filter - '] = '';
            foreach($td_return_obj_authors as $obj_autor){
                $auth_id = $obj_autor->ID;
                $auth_name = $obj_autor->display_name;

                $td_authors[$auth_name] = $auth_id;
            }

            self::$authors_array_cache = $td_authors;

            //print_r($td_authors);
            return $td_authors;
        }
    }




    /**
     * returns a string containing the numbers of words or chars for the content
     *
     * @param $post_content - the content thats need to be cut
     * @param $limit        - limit to cut
     * @param string $show_shortcodes - if shortcodes
     * @return string
     */
    static function excerpt($post_content, $limit, $show_shortcodes = '') {

        // REMOVE shortscodes and tags
        if ( $show_shortcodes == '' ) {
	        // strip_shortcodes(); this remove all shortcodes and we don't use it, is nor ok to remove all shortcodes like dropcaps
	        // this remove the caption from images - Classic
	        $post_content = preg_replace("/\[caption(.*)\[\/caption\]/i", '', $post_content);
            // this remove the caption from image and gallery - Gutenberg
            $post_content = preg_replace("/\<figcaption(.*)<\/figcaption\>/i", '', $post_content);
            // this remove any script from raw html
            $post_content = preg_replace("/\[vc_raw_html(.*?)\](.*?)\[\/vc_raw_html\]/i",'',$post_content);
            // this remove the shortcodes but leave the text from shortcodes
            $post_content = preg_replace('`\[[^\]]*\]`','',$post_content);
        }

        $post_content = stripslashes( wp_filter_nohtml_kses($post_content) );

        // Option to hide links from excerpts
        if ( td_util::get_option('tds_m_show_links_in_excerpt') == 'hide' ) {
            $post_content = preg_replace('/https?:\/\/\S+/', '', $post_content);//Radu A
        }

        /* only for problems when you need to remove links from content; not 100% bullet prof
        $post_content = htmlentities($post_content, null, 'utf-8');
        $post_content = str_replace("&nbsp;", "", $post_content);
        $post_content = html_entity_decode($post_content, null, 'utf-8');

        //$post_content = preg_replace('(((ht|f)tp(s?)\://){1}\S+)','',$post_content);//Radu A
        $pattern = "/[a-zA-Z]*[:\/\/]*[A-Za-z0-9\-_]+\.+[A-Za-z0-9\.\/%&=\?\-_]+/i";//radu o
        $post_content = preg_replace($pattern,'',$post_content); */

	    // remove the youtube link from excerpt
	    //$post_content = preg_replace('~(?:http|https|)(?::\/\/|)(?:www.|)(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=|\/ytscreeningroom\?v=|\/feeds\/api\/videos\/|\/user\S*[^\w\-\s]|\S*[^\w\-\s]))([\w\-]{11})[a-z0-9;:@?&%=+\/\$_.-]*~i', '', $post_content);

        // excerpt for letters
        if ( td_util::get_option('tds_excerpts_type') == 'letters' ) {

            $post_content = strip_tags( $post_content );

            $ret_excerpt = mb_substr( $post_content, 0, $limit );
            if ( mb_strlen($post_content)>=$limit ) {
                $ret_excerpt = $ret_excerpt.'...';
            }

            // excerpt for words
        } else {
            /*removed and moved to check this first thing when reaches thsi function
             * if ($show_shortcodes == '') {
                $post_content = preg_replace('`\[[^\]]*\]`','',$post_content);
            }

            $post_content = stripslashes(wp_filter_nohtml_kses($post_content));*/

            // remove html comments from Gutenberg blocks
            $post_content = strip_tags( $post_content );
            $excerpt = explode(' ', $post_content, $limit );

            if ( count($excerpt)>=$limit ) {
                array_pop($excerpt);
                $excerpt = implode(" ",$excerpt).'...';
            } else {
                $excerpt = implode(" ",$excerpt);
            }

            $excerpt = esc_attr(strip_tags($excerpt));

            if ( trim($excerpt) == '...' ) {
                return '';
            }

            $ret_excerpt = $excerpt;

        }

        return $ret_excerpt;

    }


    /**
     * generates a category tree, only on /wp_admin/, uses a buffer
     * @param bool $add_all_category = if true ads - All categories - at the beginning of the list (used for dropdowns)
     * @return array
     */
    private static $td_category2id_array_walker_buffer = array();
    static function get_category2id_array($add_all_category = true, $add_special_filters = true) {

        if (is_admin() === false) {
            return array();
        }

        if (empty(self::$td_category2id_array_walker_buffer)) {
            $categories = get_categories(array(
                'hide_empty' => 0,
                'number' => 1500
            ));

            $td_category2id_array_walker = new td_category2id_array_walker;
            $td_category2id_array_walker->walk($categories, 4);
            self::$td_category2id_array_walker_buffer = $td_category2id_array_walker->td_array_buffer;
        }

        if ( $add_all_category === true ) {
            if ( $add_special_filters ) {
                return array_merge(
                    array( '- All categories -' => '' ),
                    array( '-- [Special Filters] --' => '__' ),
                    array( 'Category - Current category' => '_current_cat' ),
                    array( 'Single - More from author' => '_more_author' ),
                    array( 'Single - Related by category' => '_related_cat' ),
                    array( 'Single - Related from tags' => '_related_tag' ),
                    array( 'Single - Related from taxonomy' => '_related_tax' ),
                    array( 'Single - Siblings' => '_related_siblings' ),
                    array( 'Author - Current author' => '_current_author' ),
                    array( 'Tag - Current tag' => '_current_tag' ),
                    array( 'Date - Current date' => '_current_date' ),
                    array( 'Search - Current search' => '_current_search' ),
                    array( 'Taxonomy - Current taxonomy' => '_current_tax' ),
                    array( '-- [By Category] --' => '__' ),
                    self::$td_category2id_array_walker_buffer
                );
            }
            return array_merge(
                array( '- All categories -' => '' ),
                self::$td_category2id_array_walker_buffer
            );
        } else {
            if ( $add_special_filters ) {
                return array_merge(
                    array( '-- [Special Filters] --' => '__' ),
                    array( 'Category - Current category' => '_current_cat' ),
                    array( 'Single - More from author' => '_more_author' ),
                    array( 'Single - Related by category' => '_related_cat' ),
                    array( 'Single - Related from tags' => '_related_tag' ),
                    array( 'Single - Related from taxonomy' => '_related_tax' ),
                    array( 'Single - Siblings' => '_related_siblings' ),
                    array( 'Author - Current author' => '_current_author' ),
	                array( 'Tag - Current tag' => '_current_tag' ),
	                array( 'Date - Current date' => '_current_date' ),
	                array( 'Search - Current search' => '_current_search' ),
	                array( 'Taxonomy - Current taxonomy' => '_current_tax' ),
                    array( '-- [By Category] --' => '__' ),
                    self::$td_category2id_array_walker_buffer
                );
            }
            return self::$td_category2id_array_walker_buffer;
        }
    }


	/**
	 * Get the block template ids
	 * @return array
	 */
	static function get_block_template_ids() {

		if (is_admin() === false) {
            return array();
        }

		$block_template_ids = array();

		foreach (td_api_block_template::get_all() as $block_template_id => $block_template_settings) {
            if (isset($block_template_settings['text'])) {
                $block_template_ids[$block_template_settings['text']] = $block_template_id;
            }
		}

		return array_merge( array( '- Global Header -' => ''), $block_template_ids );
	}


	/**
	 * safe way to call the tdc_state::is_live_editor_iframe() function
     * DO NOT USE THIS IN PLUGINS that are not hooked on wp_booster_loaded with a priority of 10002 or grater!
     * In other plugins we cannot accurately determine if the composer was loaded or not! Maybe the plugin loads before the composer.
	 * @return bool  Note that ajax requests do not toggle this to true
	 */
	static function tdc_is_live_editor_iframe() {
		if (class_exists('tdc_state', false) === true && method_exists('tdc_state', 'is_live_editor_iframe') === true) {
			return tdc_state::is_live_editor_iframe();
		}
		return false;
	}


	/**
     * DO NOT USE THIS IN PLUGINS that are not hooked on wp_booster_loaded with a priority of 10002 or grater!
     * In other plugins we cannot accurately determine if the composer was loaded or not! Maybe the plugin loads before the composer.
	 * @return bool returns true only when the pagebuilder makes an ajax request
	 */
	static function tdc_is_live_editor_ajax() {
		if (class_exists('tdc_state', false) === true && method_exists('tdc_state', 'is_live_editor_ajax') === true) {
			return tdc_state::is_live_editor_ajax();
		}
		return false;
	}

    /**
     * @return bool returns true on ajax block requests.
	 */
	static function tdc_is_td_block_ajax() {
		if ( class_exists('tdc_state', false ) === true && method_exists('tdc_state', 'is_td_block_ajax' ) === true ) {
			return tdc_state::is_td_block_ajax();
		}
		return false;
	}


	/**
     * DO NOT USE THIS IN PLUGINS that are not hooked on wp_booster_loaded with a priority of 10002 or grater!
     * In other plugins we cannot accurately determine if the composer was loaded or not! Maybe the plugin loads before the composer.
	 * @return bool returns true if the TagDiv Composer is installed
	 */
	static function tdc_is_installed() {
		if (class_exists('tdc_state', false) && function_exists( 'tdc_b64_decode' ) ) {
			return true;
		}
		return false;
	}



	/**
	 * Checks if VC is installed
	 * @return bool true if visual composer is installed
	 */
	static function is_vc_installed() {
		if (defined('WPB_VC_VERSION')) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if the default AMP WP plugin is installed
	 * @return bool true if AMP is installed( and it's not the old tagdiv amp plugin )
	 */
	static function is_amp_plugin_installed() {
	    if ( defined('AMP__VERSION') && ! defined('TD_AMP') ) {
	        return true;
        }

        return false;
    }

	static function is_amp() {

	    if ( self::is_amp_plugin_installed() ) {
	        if ( self::get_option('tdm_amp') !== '' ) {
		        if ( function_exists('is_amp_endpoint') ) {
			        return is_amp_endpoint();
		        }
            }
        }

        return false;
    }


	/**
	 * Checks a page content and tries to determine if a page was build with a pagebuilder (tdc or vc)
	 * @param $post WP_Post
	 * @return bool
	 */
	static function is_pagebuilder_content($post) {

		if ( td_util::tdc_is_live_editor_iframe() ) {
			return true;
		}

		if (empty($post->post_content)) {
			return false;
		}

		/**
		 * detect the page builder
         * check for the vc_row, evey pagebuilder page must have vc_row in it
		 */
		$matches = array();
		$preg_match_ret = preg_match('/\[.*vc_row.*\]/s', $post->post_content, $matches);
		if ($preg_match_ret !== 0 && $preg_match_ret !== false ) {
			return true;
		}

		return false;
	}



    /**
     * safe way to call visual composers function vc_is_inline (if we are in the live editor)
     * @deprecated 12/04/2016 by ra
     * @return bool|null
     */
    static function vc_is_inline() {
        if (function_exists('vc_is_inline')) {
            return vc_is_inline();
        } else {
            return false;
        }
    }





    /**
     * receives a VC_MAP array and it removes param_name's from it
     * @param $vc_map_array array contains a VC_MAP array - must have a ex: $vc_map_array[0]['param_name']
     * @param $param_names array of param_name's that we will cut from the VC_MAP array
     * @return array the cut VC_MAP array
     */
    static function vc_array_remove_params($vc_map_array, $param_names) {
        foreach ($vc_map_array as $vc_map_index => $vc_map) {
            if (in_array($vc_map['param_name'], $param_names)) {
	            unset($vc_map_array[$vc_map_index]);
            }
        }
	    // the array_merge is used to remove unset int keys and reindex the array for int keys, preserving string keys - Visual Composer needs this
        return array_merge($vc_map_array);
    }



    static function get_featured_image_src($post_id, $thumb_type) {
        $attachment_id = get_post_thumbnail_id($post_id);
        $td_temp_image_url = wp_get_attachment_image_src($attachment_id, $thumb_type);

        if (!empty($td_temp_image_url[0])) {
            return $td_temp_image_url[0];
        } else {
            return '';
        }
    }


    /**
     * get information about an attachment
     * @param $attachment_id
     * @param string $thumbType
     * @return array
     */
    static function attachment_get_full_info($attachment_id, $thumbType = 'full') {
        $attachment = get_post( $attachment_id );

        // make sure that we get a post
        if (is_null($attachment)) {
            return array (
                'alt' => '',
                'caption' => '',
                'description' => '',
                'href' => '',
                'src' => '',
                'title' => '',
                'width' => '',
                'height' => ''
            );
        }

        $image_src_array = self::attachment_get_src($attachment_id, $thumbType);

        //print_r($attachment);

        return array (
            'alt' => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true ),
            'caption' => $attachment->post_excerpt,
            'description' => $attachment->post_content,
            'href' => esc_url(get_permalink($attachment->ID)),
            'src' => $image_src_array['src'],
            'title' => $attachment->post_title,
            'width' => $image_src_array['width'],
            'height' => $image_src_array['height']
        );
    }


    /**
     * Safe way to get an attachment image src + width and height. It always returns the array
     * @param $attachment_id
     * @param string $thumbType
     * @return mixed
     */
    static function attachment_get_src($attachment_id, $thumbType = 'full') {
        $image_src_array = wp_get_attachment_image_src($attachment_id, $thumbType);
        $buffy = array();

        //init the variable returned from wp_get_attachment_image_src
        if (empty($image_src_array[0])) {
            $buffy['src'] = '';
        } else {
            $buffy['src'] = $image_src_array[0];
        }

        if (empty($image_src_array[1])) {
            $buffy['width'] = '';
        } else {
            $buffy['width'] = $image_src_array[1];
        }


        if (empty($image_src_array[2])) {
            $buffy['height'] = '';
        } else {
            $buffy['height'] = $image_src_array[2];
        }

        return $buffy;
    }


    static function strpos_array($haystack_string, $needle_array, $offset=0) {
        foreach($needle_array as $query) {
            if(strpos($haystack_string, $query, $offset) !== false) {
                return true; // stop on first true result
            }
        }
        return false;
    }





    /**
     * register the thumbs with WordPress only when the thumbs are enabled form the panel
     * @param $id
     * @param $x
     * @param $y
     * @param $crop
     */
    static function add_image_size_if_enabled($id, $x, $y, $crop) {
        if (td_util::get_option('tds_thumb_' . $id) != '') {
            add_image_size($id, $x, $y, $crop);
        }
    }


	/**
	 * Shows a soft error. The site will run as usual if possible. If the user is logged in and has 'switch_themes'
	 * privileges this will also output the caller file path
	 *
	 * @param $file - The file should be __FILE__
	 * @param $message
	 * @param string $more_data
	 */
    static function error( $file, $message, $more_data = '' ) {
	    ob_start();

	    echo '<strong class="td-wp-booster-title">wp_booster error:</strong><br>' . $message;

        echo '<br>' . $file;
        if ( !empty( $more_data ) ) {
            echo '<br><br><pre>';
            echo 'more data:' . PHP_EOL;
            print_r( $more_data );
            echo '</pre>';
        }

	    $buffer = ob_get_clean();

        if ( is_user_logged_in() and current_user_can('switch_themes') ) {

	        $error_uuid = td_global::td_generate_unique_id();
            ?>
                <style>

                    /* custom css - generated by TagDiv Composer */
                    .td-wp-booster-title {
                        color: #ee5734;
                    }
                    .td-wp-booster-error {
                        background-color: #e4e4e4;
                        font-size:12px;
                        padding: 10px;
                        white-space: pre-wrap;
                        word-break: break-all;
                    }
                    .td-wp-booster-error a {
                        background-color:#ee5734;
                        color:white;
                        font-size:12px;
                        padding: 5px 10px;
                        margin-top: 5px;
                        margin-bottom: 5px;
                        display: inline-block;
                    }
                    .td-wp-booster-error pre {
                        display:none;
                        font-size:12px;
                        line-height: 13px;
                    }
                    .td-wp-booster-error-show {
                        display: block !important;
                    }

                </style>

                <div class="td-wp-booster-error">

                    <?php printf( '%1$s', $buffer ) ?>

                    <div>
                        <a href="#" id="<?php echo esc_attr( $error_uuid . '_oc' ) ?>">Display backtrace</a>
                    </div>

                    <pre id="<?php echo esc_attr( $error_uuid . '_pre' ) ?>">
<?php print_r(@debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 7)); ?>
                    </pre>

                    <script>

                        ( function () {

                            function hasClass(element, cls) {
                                return (' ' + element.className + ' ').indexOf(' ' + cls + ' ') > -1;
                            }

                            document.getElementById("<?php printf( '%1$s_oc', $error_uuid ) ?>").addEventListener("click", function() {
                                var preId = "<?php printf( '%1$s_pre', $error_uuid ) ?>";
                                if (hasClass(document.getElementById(preId), "td-wp-booster-error-show")) {
                                    document.getElementById(preId).className = "";
                                } else {
                                    document.getElementById(preId).className = "td-wp-booster-error-show";
                                }


                            }, false);

                        })();

                    </script>

                </div>
            <?php

            td_util::set_check_installed_plugins();

        } else {
	        echo '
		    <!--
		        Error: ' . $buffer . ' (rara-error)
		    -->
		    ';
        }
    }


	static function set_check_installed_plugins() {
		self::$check_installed_plugins = true;
	}

	static function get_check_installed_plugins() {
		return self::$check_installed_plugins;
	}


    static function get_block_error($block_name, $message) {
        if (is_user_logged_in()){
            return '<div class="td-block-missing-settings"><span>' . $block_name . '</span>' . $message . '</div>';
        };

        return '';
    }

    /**
     * makes sure that we return something even if the $_POST of that value is not defined
     * @param $post_variable
     * @return string
     */
    static function get_http_post_val($post_variable) {
        if (isset($_POST[$post_variable])) {
            return $_POST[$post_variable];
        } else {
            return '';
        }
    }


	/**
	 * replace script tag from the parameter $buffer   keywords: js javascript ob_start ob_get
	 * @param $buffer string
	 *
	 * @return string
	 */
	static function remove_script_tag($buffer) {
		return str_replace(array("<script>", "</script>", "<script type='text/javascript'>"), '', $buffer);
	}

	static function remove_style_tag($buffer) {
		return str_replace(array("<style>", "</style>" ), '', $buffer);
	}



    static function tooltip($content, $position = 'top') {
        echo '<a href="#" class="td-tooltip" data-position="' . $position . '" title="' . $content . '">?</a>';
    }

    static function tooltip_html($content, $position = 'top') {
        echo '<a href="#" class="td-tooltip" data-position="' . $position . '" data-content-as-html="true" title="' . esc_attr($content) . '">?</a>';
    }


	/**
	 * Checks if a demo is loaded. If one is loaded the function returns the demo NAME/ID. If no demo is loaded we get FALSE
	 * @see td_demo_state::update_state
	 * @return bool|string - false if no demo is loaded OR string - the demo id
	 */
	static function get_loaded_demo_id() {
		$demo_state = get_option(TD_THEME_NAME . '_demo_state');  // get the current loaded demo... from wp cache
		if (!empty($demo_state['demo_id'])) {
			return $demo_state['demo_id'];
		}

		return false;
	}

	/**
	 * Helper function used to check if the mobile theme is active.
	 * Important! On ajax requests from mobile theme, please consider that the main theme is only known in wp-admin. That's why for this case
	 * we check only for the 'td_mobile_theme' class existence.
	 *
	 * @return bool
	 */
	static function is_mobile_theme() {

		/**
		 * We can't use : global $wp_customize // The instance of WP_Customize_Manager
		 * because it's not initialized @see add_action( 'plugins_loaded', '_wp_customize_include' );
		 */

		if (defined('DOING_AJAX') && DOING_AJAX) {
			if (class_exists('td_mobile_theme', false)) {
				return true;
			}
		} else {
			$current_theme_name = get_template();

			if (empty($current_theme_name) and class_exists('td_mobile_theme', false)) {
				return true;
			}
		}
		return false;
	}


    /**
     * Returns the srcset and sizes parameters or an empty string
     * @param $thumb_id - thumbnail id
     * @param $thumb_type - thumbnail name/type (ex. td_356x220)
     * @param $thumb_width - thumbnail width
     * @param $thumb_url - thumbnail url
     * @return string
     */
	static function get_srcset_sizes($thumb_id, $thumb_type, $thumb_width, $thumb_url) {
        $return_buffer = '';
        //backwards compatibility - check if wp_get_attachment_image_srcset is defined, it was introduced only in WP 4.4
        if (function_exists('wp_get_attachment_image_srcset')) {
            //retina srcset and sizes
            if (td_util::get_option('tds_thumb_' . $thumb_type . '_retina') == 'yes' && !empty($thumb_width)) {
                $thumb_w = ' ' . $thumb_width . 'w';
                $retina_thumb_width = $thumb_width * 2;
                $retina_thumb_w = ' ' . $retina_thumb_width . 'w';
                //retrieve retina thumb url
                $retina_url =  wp_get_attachment_image_src($thumb_id, $thumb_type . '_retina');
                //srcset and sizes
                if ($retina_url !== false) {
                    $return_buffer .= ' srcset="' . esc_url( $thumb_url ) . $thumb_w . ', ' . esc_url( $retina_url[0] ) . $retina_thumb_w . '" sizes="(-webkit-min-device-pixel-ratio: 2) ' . $retina_thumb_width . 'px, (min-resolution: 192dpi) ' . $retina_thumb_width . 'px, ' . $thumb_width . 'px"';
                }

            //responsive srcset and sizes
            } else {
                $thumb_srcset = wp_get_attachment_image_srcset($thumb_id, $thumb_type);
                $thumb_sizes = wp_get_attachment_image_sizes($thumb_id, $thumb_type);
                if ($thumb_srcset !== false && $thumb_sizes !== false) {
                    $return_buffer .=  ' srcset="' . $thumb_srcset . '" sizes="' . $thumb_sizes . '"';
                }
            }
        }

        return $return_buffer;
    }

    /**
     * get the censored key (for display in theme System Status section)
     * @return mixed|string
     */
    static function get_registration() {
        $buffy = '<strong style="color: red;">Your theme is not registered!</strong><a class="td-button-system-status td-theme-activation" href="' . wp_nonce_url(admin_url('admin.php?page=td_cake_panel')) . '">Activate now</a>';
        $ks = array_keys(self::$e_keys);

        if ( self::get_option(td_handle::get_var($ks[1])) == 2 ) {
            $ek = self::get_option(td_handle::get_var($ks[0]));
            //censure key display (for safety)
            if (!empty($ek)) {
                $ek = td_handle::get_var($ek);
                $censored_area = substr($ek, 8, strlen($ek) - 20);
                $replacement = ' - **** - **** - **** - ';
                $buffy = str_replace($censored_area, $replacement, $ek);
                //add key reset button
                $buffy .= ' <a class="td-button-system-status td-action-alert td-reset-key" href="admin.php?page=td_system_status&reset_registration=1" data-action="reset the theme registration key">Reset key</a>';
            }
        }

        return $buffy;
    }



    /**
     * get theme version and update button (if an update is available)
     * @return string
     */
    static function get_theme_version() {
        $td_theme_version = TD_THEME_VERSION;

        //disable update on deploy
        if ($td_theme_version != '__td_deploy_version__' && td_api_features::is_enabled('check_for_updates')) {
            $td_latest_version = td_util::get_option('td_latest_version');
            $td_update_url = td_util::get_option('td_update_url');
            if (!empty($td_latest_version) && !empty($td_update_url)) {
                //compare theme's current version with latest version
                $compare_versions = version_compare($td_theme_version, $td_latest_version, '<');
                if ($compare_versions === true) {
                    $td_theme_version .= ' - <span class="td-theme-update-log">Version ' . $td_latest_version . ' is available</span><a target="_blank" class="td-button-system-status td-theme-update" href="' . $td_update_url . '">Update now</a>';
                }
            }
        }

        return $td_theme_version;
    }



    /**
     * @param $index
     * @param $value
     */
    private static function ajax_update($index, $value) {
        if (empty($index) || empty($value)) {
            return;
        }
        if (!defined( 'DOING_AJAX' ) || !DOING_AJAX) {
            return;
        }
        if (is_admin()) {
            self::update_option($index, $value);
        }
    }



    /**
     * return post meta array
     * if post meta doesn't contain an array return an empty array
     * @param $post_id
     * @param $key
     * @return array|mixed
     */
    static function get_post_meta_array($post_id, $key) {
        $post_meta = get_post_meta($post_id, $key, true);
        if (!is_array($post_meta)) {
            return array();
        }
        return $post_meta;
    }



    /**
     * @param $value_
     */
    static function ajax_handle($value_ = '') {
        if (is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX) {
            $count = 0;
            foreach (self::$e_keys as $index => $value) {
                if ($value_ == '') {
                    $value = '';
                } elseif (empty($value)) {
                    $value = $value_;
                }
                if ($count == 0) {
                    $value = td_handle::set_var($value);
                }
                self::ajax_update(td_handle::get_var($index), $value);
                $count++;
            }
        }
    }


    /**
     * @param $index
     * @param $value
     */
    static function update_option_($index, $value) {
        if (empty($index)) {
            return;
        }
        $ks = array_keys(self::$e_keys);
        $k = td_handle::get_var($ks[1]);

        if ($index == 'td_cake_status') {
            return self::update_option($k, $value);
        }
        if ($index == 'td_cake_status_time') {
            return self::update_option($k . 'tp', $value);
        }
        if ($index == 'td_cake_lp_status') {
            return self::update_option($k . 'ta', $value);
        }
    }


    /**
     * @param $index
     * @return array|string|void
     */
    static function get_option_($index) {
        if (empty($index)) {
            return;
        }
        $ks = array_keys(self::$e_keys);
        $k = td_handle::get_var($ks[1]);

        if ($index == 'td_cake_status') {
            return self::get_option($k);
        }
        if ($index == 'td_cake_status_time') {
            return self::get_option($k . 'tp');
        }
        if ($index == 'td_cake_lp_status') {
            return self::get_option($k . 'ta');
        }
    }

    static function reset_registration() {
        $ks = array_keys(self::$e_keys);
        $k = td_handle::get_var($ks[1]);
        self::update_option($k . 'tp', 0);
        self::update_option($k, 0);
        self::update_option($k . 'ta', '');
        self::update_option(td_handle::get_var($ks[0]), '');

        delete_transient('TD_CHECKED_LICENSE');
        tdc_check_domain::set_transient(false);

        remove_action('shutdown', array( 'tagdiv_options', 'on_shutdown_save_options' ) );
    }


	static function get_font_family_list( $flip = true ) {

        if ( $flip ) {
            if (!empty(self::$font_family_list_flip)) {
                return self::$font_family_list_flip;
            }
        } else {
            if (!empty(self::$font_family_list)) {
                return self::$font_family_list;
            }
        }

		$list = array(
			'' => 'Default font',
		);

		// read global fonts
        $tdc_wm_global_fonts = td_util::get_option('tdc_wm_global_fonts' );
        $tdc_wm_global_fonts_list = array();
        if ( !empty( $tdc_wm_global_fonts ) && is_array( $tdc_wm_global_fonts ) ) {
            $tdc_wm_global_fonts_list['__global'] = '-- Global Fonts --';
            foreach ( $tdc_wm_global_fonts as $font_option_id => $font_data ) {
                $tdc_wm_global_fonts_list[ $font_option_id . '_global' ] = $font_data['name'] /*. ' -- ' . trim( $font_data['key'] )*/;
            }
        }

		$td_options = td_options::get_all();

        // read the user fonts array
		$td_fonts_user_inserted_list = array();
        if( !empty( $td_options['td_fonts_user_inserted'] ) ) {

            $user_fonts = $td_options['td_fonts_user_inserted'];

            // custom font links & typekit
            foreach ( $user_fonts as $key_font => $value_font ) {

                // look for the field number
                $revers_key_font = strrev( $key_font );
                $explode_key_font = explode( '_', $revers_key_font );
                $fld_number = intval( $explode_key_font[0] );

                // add custom user fonts links ( count starts from 1 )
                if( substr( $key_font, 0, 10 ) == 'font_file_' ) {
                    $font_family_field_nr = 'font_family_' . $fld_number;

                    if( !empty( $user_fonts['font_file_' . $fld_number] ) and !empty( $user_fonts[$font_family_field_nr] ) ) {
	                    $td_fonts_user_inserted_list[ 'file_' . $fld_number ] = $user_fonts[$font_family_field_nr];
                    }

                    // add tipekit fonts ( count starts from 1 )
                } elseif( substr( $key_font, 0, 21 ) == 'type_kit_font_family_' ) {
                    $type_kit_font_family_field_nr = 'type_kit_font_family_' . $fld_number;

                    if( !empty( $user_fonts[$type_kit_font_family_field_nr] ) ) {
	                    $td_fonts_user_inserted_list[ 'tk_' . $fld_number ] = $user_fonts[$type_kit_font_family_field_nr];
                    }
                }

            }

            // if there are any fonts in the list prepend identifier
            if ( !empty( $td_fonts_user_inserted_list ) ) {
	            $td_fonts_user_inserted_list = array_merge(
                    $td_fonts_user_inserted_list,
                    array(
                        '__custom' => '-- Custom Fonts Links & Typekit --'
                    )
	            );
            }

        }

		// font stacks
		$td_font_stacks_list = array();
		$td_font_stacks_list['__stacks'] = '-- Font Stacks --';
		foreach ( td_fonts::$font_stack_list as $font_id => $font_name ) {
			$td_font_stacks_list[$font_id] = $font_name;
		}

		// google fonts
		$td_google_fonts_list = array();
		$td_google_fonts_list['__google'] = '-- Google Fonts --';
		asort(td_fonts::$font_names_google_list);
		foreach ( td_fonts::$font_names_google_list as $font_id => $font_name ) {
			$td_google_fonts_list[$font_id] = $font_name;
		}

        // build fonts list
		$list = $list + $tdc_wm_global_fonts_list + $td_fonts_user_inserted_list + $td_font_stacks_list + $td_google_fonts_list;

        $list = apply_filters( 'td_font_family_list', $list );

		if ( $flip ) {
			$list = array_flip( $list );
			self::$font_family_list_flip = $list;
		} else {
			self::$font_family_list = $list;
        }

		return $list;
	}

	static function get_font_style_list( $flip = true ) {
		$list = array();

		$list[''] = 'Def font style';
		$list['italic'] = 'Italic';
		$list['oblique'] = 'Oblique';
		$list['normal'] = 'Normal (Remove italic)';

		if ( $flip ) {
			$list = array_flip( $list );
		}
		return $list;
	}

	static function get_font_weight_list( $flip = true ) {
		$list = array();

		$list[''] = 'Def font weight';
		$list['100'] = '100 - Thin (Hairline)';
		$list['200'] = '200 - Extra light (Ultra light)';
		$list['300'] = '300 - Light';
		$list['400'] = '400 - Normal';
		$list['500'] = '500 - Medium';
		$list['600'] = '600 - Semi Bold (Demi bold)';
		$list['700'] = '700 - Bold';
		$list['800'] = '800 - Extra Bold (Ultra bold)';
		$list['900'] = '900 - Black (Heavy)';

		if ( $flip ) {
			$list = array_flip( $list );
		}
		return $list;
	}

	static function get_font_transform_list( $flip = true ) {
		$list = array();

		$list[''] = 'Def text transform';
		$list['uppercase'] = 'Uppercase';
		$list['capitalize'] = 'Capitalize';
		$list['lowercase'] = 'Lowercase';
		$list['none'] = 'None (normal text)';

		if ( $flip ) {
			$list = array_flip( $list );
		}
		return $list;
	}





	static function parse_footer_texts($in_text) {
        $replace_array = array (
            '##copy##' => '&copy;',
            '##privacy_policy##' => self::get_the_privacy_policy_link(),
            '##year##' => date('Y'),
            '##sitename##' => get_bloginfo('name'),
            '##siteurl##' => get_home_url(),
            '##sitelink##' => '<a href="' . get_home_url() . '">' . get_bloginfo('name') . '</a>'
        );

        foreach ($replace_array as $search_for => $replace_with) {
            $in_text = str_replace($search_for, $replace_with, $in_text);
        }

        return $in_text;
    }

	/**
	 * check if a theme plugin is active
	 *
	 * @param $plugin - the plugin td_config array
	 * @return bool - true if active, false otherwise
	 */
	static function is_active( $plugin ) {

		$plugin_key = str_replace( '-', '_', strtoupper( $plugin['slug'] ) );
		$td_plugins = tagdiv_global::get_td_plugins();

		// check if it's a theme plugin
		if ( array_key_exists( $plugin_key, $td_plugins ) ) {
			if ( class_exists( $plugin['td_class'], false ) ) {
				return true;
			} elseif ( $plugin['slug'] === 'td-mobile-plugin' ) {
				if ( ( defined('TD_MOBILE_PLUGIN') || has_action( 'admin_notices', 'td_mobile_msg' ) ) ) {
					return true;
				}
			}
			return false;
		}
		return false;
	}

	/**
	 * Check whether the string is a unix timestamp
	 *
	 * @param $timestamp - the timestamp
	 * @return bool - true if valid, false otherwise
	 */
	static function is_valid_timeStamp($timestamp) {
		return ((string) (int) $timestamp === $timestamp)
		       && ($timestamp <= PHP_INT_MAX)
		       && ($timestamp >= ~PHP_INT_MAX);
	}



	/**
	 * Get the ids of fonts required in post content
	 *
	 * @param string $post_id
	 *
	 * @return array
	 */
	static function get_required_google_fonts_ids( $post_id = '' ) {

        $content = '';

        if ( td_util::is_template_header() && !td_util::is_no_header() ) {
            $header_template_content = td_util::get_header_template_content();
            if ( is_array($header_template_content) ) {
                foreach ( $header_template_content as $header_template ) {
                    $content .= $header_template;
                }
            }
        }

        if ( td_util::is_template_footer() && !td_util::is_no_footer() ) {
            $content .= td_util::get_footer_template_content();
        }

        $post_content = get_post( $post_id )->post_content;
        if ( base64_decode( $post_content, true ) && base64_encode( base64_decode( $post_content, true ) ) === $post_content && mb_detect_encoding( base64_decode( $post_content, true ) ) === mb_detect_encoding( $post_content ) ) {
            $post_content = stripslashes( base64_decode( $post_content, true ) );
        }

        $content .= $post_content;

        return self::get_content_google_fonts_ids( $content );

	}



	static function get_content_google_fonts_ids( $content ) {
		$font_list = array();
		self::parse_content( $content, $font_list );
		$result = self::compute_fonts( $font_list );
		return $result;
	}


	static function compute_fonts( &$font_list ) {

		$result = [];
		$medias = ['all', 'landscape', 'portrait', 'phone'];

		foreach ( $font_list as &$current_settings ) {
			foreach ( $current_settings as $key => &$current_setting) {

				if ( ! empty( $current_setting ) ) {
					if ( ! is_array( $current_setting )) {
						$current_setting = [
							'all' => $current_setting,
						];
					}
					foreach ( $medias as $media ) {
						if ( empty( $current_setting[$media] ) ) {
							if ( isset( $current_setting[$media] ) && '' === $current_setting[$media] ) {
								$current_setting[$media] = 'DEFAULT';
							} else {
								if ( empty($current_setting['all']) ) {
									$current_setting[$media] = 'DEFAULT';
								} else {
									$current_setting[$media] = $current_setting['all'];
								}
							}
						}
					}
				}
			}
		}

		reset($font_list);

		unset($current_settings);
		unset($current_setting);


		foreach ( $font_list as $current_settings ) {

			if ( empty( $current_settings['family'] ) ) {

				if ( empty( $current_settings['weight'] ) && empty( $current_settings['style'] ) ) {
					continue;
				}

				// default fonts

				if ( empty($result['DEFAULT']) ) {

					// Prepare the default font by registering it in $result
					$result['DEFAULT'] = [];
				}


				foreach ( $medias as $media ) {

					if ( empty( $current_settings['weight'] ) ) {
						$current_settings[ 'weight' ] = [];
					}

					if ( empty( $current_settings['weight'][$media] ) ) {
						$current_settings['weight'][$media] = 'DEFAULT';
					}

					$current_style = '';
					$current_weight = $current_settings['weight'][$media];

					if ( ! empty( $current_settings['style'][$media] ) ) {
						$current_style = $current_settings['style'][$media];
					}

					$next_val = ( $current_weight === 'DEFAULT' ? '400' : $current_weight ) . ( $current_style === 'oblique' ? 'i': '');

					if ( ! in_array( $next_val, $result['DEFAULT'] ) ) {
						$result['DEFAULT'][] = $next_val;
					}
				}

			} else {

				// custom fonts

				foreach ( $medias as $media ) {

					$current_font_id = $current_settings['family'][$media];

					// Start a new position in $result
					if ( empty($result[$current_font_id]) ) {

						// Load a font by registering it in $result
						$result[$current_font_id] = [];
					}

					if ( empty( $current_settings['weight'] ) ) {
						$current_settings[ 'weight' ] = [];
					}

					if ( empty( $current_settings['weight'][$media] ) ) {
						$current_settings[ 'weight' ][ $media ] = 'DEFAULT';
					}

					$current_style = '';
					$current_weight = $current_settings['weight'][$media];

					if ( ! empty( $current_settings['style'][$media] ) ) {
						$current_style = $current_settings['style'][$media];
					}

					$next_val = ( $current_weight === 'DEFAULT' ? '400' : $current_weight ) . ( $current_style === 'oblique' ? 'i': '');

					if ( ! in_array( $next_val, $result[$current_font_id] ) ) {
						$result[$current_font_id][] = $next_val;
					}
				}
			}
		}

		return $result;
	}


	static function parse_content( &$content = null, &$font_list = array()) {

		$new_content = '';

		if ( preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $shortcode ) {
//				var_dump($shortcode[ 2 ]);

				$attributes = shortcode_parse_atts( $shortcode[ 3 ] );

//				var_dump($matches);
//				var_dump($attributes);

				$wrapper_shortcode = false;

				if (strpos( $content, "[/" . $shortcode[ 2 ] . "]") > 0 ) {
					$wrapper_shortcode = true;
				}


				if ( ! empty( $shortcode[5] ) ) {
					$new_content .= '[' . $shortcode[2];

					if (is_array($attributes)) {
						self::parse_shortcode_attr( $new_content, $shortcode[2], $attributes, $font_list );
					}

					$new_content .= ']';

					$new_content .= self::parse_content($shortcode[5], $font_list );

					$new_content .= '[/' . $shortcode[2] . ']';

				} else {

					$new_content .= '[' . $shortcode[2];

					if (is_array($attributes)) {
						self::parse_shortcode_attr( $new_content, $shortcode[ 2 ], $attributes, $font_list );
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


	static function parse_shortcode_attr( &$content, $shortcode, $attributes, &$font_list = array()) {

		$real_keys = [];

		foreach ( $attributes as $key => $val ) {

			$font_value = $val;

			switch ($shortcode) {
				default:
//					switch ( $key ) {
//						case '':
//							break;
//					}

					$matches = array();
					preg_match_all('/(\w+)_font_(\w+)/mi', $key, $matches);

//					echo '<pre>';
//					var_dump( $shortcode );
//					var_dump( $font_value );
//					echo '</pre>';

					if ( ! empty( $matches ) && is_array( $matches ) && 3 === count( $matches ) && ! empty( $matches[1][0] ) ) {

						if ( array_key_exists( $matches[1][0], $real_keys )) {
							$current_key = $real_keys[ $matches[1][0] ];
						} else {
							$current_key = $key . '_' . td_global::td_generate_unique_id();
							$real_keys[ $matches[1][0] ] = $current_key;
						}

						foreach ( $matches[2] as $font_param ) {

							switch ( $font_param ) {

								case 'family':
								case 'weight':
								case 'style':

									// Detect base64 encoding
									if ( !is_numeric($font_value) && base64_decode( $font_value, true ) && base64_encode( base64_decode( $font_value, true ) ) === $font_value && mb_detect_encoding( base64_decode( $font_value, true ) ) === mb_detect_encoding( $font_value ) ) {

										$decoded_values = base64_decode( $font_value, true );
										$values         = json_decode( $decoded_values, true );

										foreach ( $values as $media => $value ) {

											if ( empty($font_list[$current_key])) {
												$font_list[$current_key] = [
													$font_param => []
												];
											} else if ( empty($font_list[$current_key][$font_param])) {
												$font_list[$current_key][$font_param] = [];
											}

                                            if ( is_array( $font_list[$current_key][$font_param] ) ) {
                                                $font_list[$current_key][$font_param][$media] = $value;
                                            }

										}

									} else {
										if ( empty($font_list[ $current_key ])) {
											$font_list[ $current_key ] = [];
										}
										$font_list[ $current_key ][$font_param] = $font_value;
									}
									break;
							}
						}
					}
			}
		}

        //print_r($font_list);

	}

    /**
     * return icon html <svg> or <i>
     * @param $icon
     * @param $extra_class
     * @return string
     */
    static function get_icon_type($icon, $extra_class='') {
        $svg_list = td_global::$svg_theme_font_list;
        if( array_key_exists( $icon, $svg_list ) ) {
            $ico_html = base64_decode( $svg_list[$icon] );
        } else {
            $ico_html = '<i class="' . $extra_class . ' ' . $icon . '"></i>';
        }
        return $ico_html;
    }

	/**
	 * inserts a value or key/value pair after a specific key in an array
     * if key doesn't exist, value is appended to the end of the array
	 *
	 * @param array $array
	 * @param string $key
	 * @param array $new
	 *
	 * @return array
	 */
    static function array_insert_after( array $array, $key, array $new ) {
	    $keys = array_keys( $array );
	    $index = array_search( $key, $keys );
	    $pos = false === $index ? count( $array ) : $index + 1;

	    return array_merge( array_slice( $array, 0, $pos ), $new, array_slice( $array, $pos ) );
    }


    static function td_new_subscriber_user_notifications( $user_id, $notify = '' ) {

		// Accepts only 'user', 'admin' , 'both' or default '' as $notify.
		if ( ! in_array( $notify, array( 'user', 'admin', 'both', '' ), true ) ) {
			return;
		}


        // Get the user data
		$user = get_userdata( $user_id );


        // We are sending an email to the site admin to let them know that someone has just registered a new account
		if ( 'user' !== $notify ) {
			$switched_locale = switch_to_locale( get_locale() );

            if( defined( 'TD_SUBSCRIPTION' ) ) {
                tds_email_notifications::send_admin_email_notification('register', $user_id);
            } else {
                $admin_emails = get_bloginfo('admin_email');
                $email_subject = '[' . td_email::get_email_from_name() . '] New user registration';
                $email_message =
                    '<h3>New user!</h3>
                    <p>Username: ' . $user->user_login . '<br>
                    Email: ' . $user->user_email . '</p>';
                $email_footer_text = td_email::get_email_footer_text();

                td_email::send_mail(
                    $admin_emails,
                    $email_subject,
                    td_email::email_template(
                        $email_subject,
                        $email_message,
                        '',
                        $email_footer_text
                    )
                );
            }

			if ( $switched_locale ) {
				restore_previous_locale();
			}
		}


        // We are notifying the user that they have just registered an account on the website
		$tds_validate = get_user_meta($user_id, 'tds_validate', true);
		if ( !empty($tds_validate) && is_array($tds_validate) && !empty($tds_validate['key']) ) {
		    $key = $tds_validate['key'];

		    $switched_locale = switch_to_locale( get_user_locale( $user ) );

            $subscription_activation_link = network_site_url( "wp-login.php?action=tds_validate&key=$key&login=" . rawurlencode( $user->user_login ), 'login' );

            if( defined( 'TD_SUBSCRIPTION' ) ) {
                $add_tags = array('%verification_link%');
                $add_tags_replacements = array($subscription_activation_link);

                tds_email_notifications::send_user_email_notification('register', $user_id, $add_tags, $add_tags_replacements);
            } else {
                $email_from_name =  td_email::get_email_from_name();
                $email_subject = '[' . $email_from_name . '] Activate account';
                $email_message =
                    '<h3>Welcome onboard!</h3>
                    <p>Hi,</p>
                    <p>Thank you for registering on ' . $email_from_name . '! To activate your account, please visit the following link:</p>
                    <p><a href="' . $subscription_activation_link . '">' . $subscription_activation_link . '</a></p>';

                td_email::send_mail(
                    $user->user_email,
                    $email_subject,
                    td_email::email_template(
                        $email_subject,
                        $email_message,
                        '',
                        $email_footer_text
                    )
                );
            }

            if ( $switched_locale ) {
                restore_previous_locale();
            }
        }
	}

    static function td_new_subscriber_double_opt_in( $email, $notify = '' ) {

		// Accepts only 'user', 'admin' , 'both' or default '' as $notify.
		if ( ! in_array( $notify, array( 'user', 'admin', 'both', '' ), true ) ) {
			return;
		}

        $subscription_activation_link = network_site_url("?action=tds_validate_email&email=$email");

        if( defined( 'TD_SUBSCRIPTION' ) ) {
            $add_tags = array('%optin_confirm_link%');
            $add_tags_replacements = array($subscription_activation_link);

            tds_email_notifications::send_user_email_notification('optin', $email, $add_tags, $add_tags_replacements);
        } else {
            $email_from_name = td_email::get_email_from_name();
            $email_subject = '[' . $email_from_name . '] Confirm subscription';
            $email_message =
                '<h3>Welcome onboard!</h3>
                <p>Hi,</p>
                <p>Thank you for subscribing to ' . $email_from_name . '! To confirm your subscription, please visit the following link:</p>
                <p><a href="' . $subscription_activation_link . '">' . $subscription_activation_link . '</a></p>';
            $email_footer_text = td_email::get_email_footer_text();

            td_email::send_mail(
                $email,
                $email_subject,
                td_email::email_template(
                    $email_subject,
                    $email_message,
                    '',
                    $email_footer_text
                )
            );
        }

	}


	static function check_option_id(&$option_id) {

        if (class_exists('SitePress', false)) {
	        global $sitepress;

	        if ( isset($_GET['td_action']) && ( 'tdc_edit' == $_GET['td_action'] || 'tdc' === $_GET['td_action'] )) {

	    		// we are in composer
			    $page_id = get_the_ID();
			    $t_post_id = $sitepress->get_element_trid( $page_id, 'post_post' );
				$translations = $sitepress->get_element_translations($t_post_id, 'post_post', false, true);

				if ( !empty($translations) && is_array($translations) && count($translations)) {
					foreach ($translations as $translation) {
						if ( !empty( $translation->element_id ) && $page_id === intval($translation->element_id) && !empty($translation->language_code)) {
							$option_id .= $translation->language_code;
						}
					}
				}

	        } else {

	            global $sitepress;
	            $sitepress_settings = $sitepress->get_settings();
	            if ( isset($sitepress_settings['custom_posts_sync_option'][ 'tdb_templates']) ) {
	                $translation_mode = (int)$sitepress_settings['custom_posts_sync_option']['tdb_templates'];
	                if (1 === $translation_mode) {
	                    $option_id .= $sitepress->get_current_language();
	                }
	            }
	        }
	    }
    }

	/**
     * Get the number of comments for a post by post link from disqus api
     *
	 * @param $post - the post object
	 *
	 * @return mixed - null if dsq plugin is not active, it's not configured, is set not to load or if the post link is not valid ...
     * ... the number of comments from dsq otherwise
	 */
    static function get_dsq_comments_number( $post ) {
	    $post_id = $post->ID;
	    $post_link = get_permalink($post_id);
	    $comments_number = null;

	    if ( class_exists( 'Disqus_Conditional_Load' ) && $post_link ) {

		    $dsq_can_load = apply_filters( 'dsq_can_load', 'embed' );
		    if ( is_bool( $dsq_can_load ) ) {
			    return null;
		    }

		    $dsq_forum_url = strtolower( get_option('disqus_forum_url') );
		    $dsq_api_key = esc_attr( get_option('disqus_public_key') );

		    if ( $dsq_api_key && $dsq_forum_url ) {
			    $api_url = 'https://disqus.com/api/3.0/threads/set.json?thread:link=' . $post_link . '&forum=' . $dsq_forum_url . '&api_key=' . $dsq_api_key;
			    $dsq_api_response = wp_remote_get( $api_url );
			    if ( is_wp_error( $dsq_api_response ) ) {
				    $dsq_api_data = false;
				    td_log::log( __FILE__, __FUNCTION__, $dsq_api_response->get_error_message() );
				    //td_util::get_block_error( 'Single Post Comments Counter', $dsq_api_response->get_error_message() );
			    } else {
				    $dsq_api_data = json_decode( $dsq_api_response['body'] );
			    }
			    if ( $dsq_api_data && 0 === $dsq_api_data->code ) {
				    foreach ( $dsq_api_data->response as $comment ) {
					    if ( $post_link === $comment->link ) {
						    $comments_number = $comment->posts;
						    break;
					    }
				    }
			    }
		    }

	    }

	    return $comments_number;
    }




    static function get_cpts() {

    	// detect custom post types
        $post_types = get_post_types( array(
            'public' => true,
        ), 'objects' );

        $cpts = [];
        foreach ($post_types as $post_type) {

            switch ($post_type->name) {
                //case 'post':
                case 'page':
                case 'attachment':
                case 'product':
                case 'tds_locker':
                case 'tds_email':
                case 'tdb_templates':
                case 'tdc-review-email':
                    break;
                default:
                	$cpts[] = $post_type;
            }
        }

        return $cpts;
	}

	static function get_ctaxes() {

    	$ctaxes = [];
    	$taxonomies = get_taxonomies([], 'objects');

    	foreach ($taxonomies as $taxonomy ) {
    		if ( ! $taxonomy->_builtin && $taxonomy->publicly_queryable ) {
    			$ctaxes[] = $taxonomy;
		    }
	    }

    	return $ctaxes;
	}



    static function get_custom_field_value_from_string( $string ) {

        // Replace the custom field names with their values
        $custom_fields_to_replace = [];
        $custom_field_replacements = [];

        preg_match_all('/{cf_(\S*)}/', $string, $custom_field_matches);

        if( !empty($custom_field_matches) &&
            is_array($custom_field_matches) &&
            count($custom_field_matches) >= 2 &&
            is_array($custom_field_matches[0]) && !empty($custom_field_matches[0]) &&
            is_array($custom_field_matches[1]) ) {

            foreach ( $custom_field_matches[1] as $index => $field_name ) {
                if ( $field_name != '' ) {
                    $custom_field_data = array();

                    if ( td_global::is_tdb_registered() ) {
                        global $tdb_module_template_params, $tdb_state_single, $tdb_state_category, $tdb_state_tag, $tdb_state_author, $tdb_state_attachment, $tdb_state_single_page;

                        if ( $tdb_module_template_params !== NULL ) {
                            $post_obj = $tdb_module_template_params['post_obj'];
                
                            // Create a dummy field data array
                            $dummy_field_data = array(
                                'value' => 'Sample field data',
                                'type' => 'text',
                                'meta_exists' => true,
                            );
                
                            $custom_field_data = array(
                                'value' => '',
                                'type' => '',
                                'meta_exists' => false,
                            );
                
                            if ( gettype($post_obj) === 'object' && get_class($post_obj) === 'WP_Post' ) {
                
                                $post_obj_id = $post_obj->ID;
                
                                if( $field_name == 'td_source_title' ) {
                                    $source_post_id = get_post_meta( $post_obj_id, 'tdc-parent-post-id', true );
            
                                    if ( !empty( $source_post_id ) ) {
                                        $custom_field_data['value'] = get_the_title($source_post_id);
                                        $custom_field_data['type'] = 'text';
                                        $custom_field_data['meta_exists'] = true;
                                    }
                                } else {
                                    $custom_field_data = td_util::get_acf_field_data( $field_name, $post_obj_id );
            
                                    if( !$custom_field_data['meta_exists'] ) {
                                        if( metadata_exists('post', $post_obj_id, $field_name ) ) {
                                            $custom_field_data['value'] = get_post_meta( $post_obj_id, $field_name, true );
                                            $custom_field_data['type'] = 'text';
                                            $custom_field_data['meta_exists'] = true;
                                        }
                                    }
                                }
                
                                if( empty( $custom_field_data['value'] ) && ( tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax() ) ) {
                                    // If we are in composer, display dummy data only if we
                                    // are editing the actual module
                                    if( tdb_state_template::get_template_type() == 'module' ) {
                                        $custom_field_data = $dummy_field_data;
                                    }
                                }
                
                            } else {
                                $custom_field_data = $dummy_field_data;
                            }
                        } else {
                            $atts = array('wp_field' => $field_name);
                            
                            switch ( tdb_state_template::get_template_type() ) {
                                case 'cpt':
                                case 'single':
                                    $custom_field_data = $tdb_state_single->post_custom_field->__invoke($atts);
                                    break;
                                case 'category':
                                    $custom_field_data = $tdb_state_category->category_custom_field->__invoke($atts);
                                    break;
                                case 'cpt_tax':
                                    $tdb_state_category->set_tax();
                                    $custom_field_data = $tdb_state_category->category_custom_field->__invoke($atts);
                                    break;
                                case 'tag':
                                    $custom_field_data = $tdb_state_tag->tag_custom_field->__invoke($atts);
                                    break;
                                case 'author':
                                    $custom_field_data = $tdb_state_author->author_custom_field->__invoke($atts);
                                    break;
                                case 'attachment':
                                    $custom_field_data = $tdb_state_attachment->attachment_custom_field->__invoke($atts);
                                    break;
                                case 'woo_product':
                                    if ( defined('TD_WOO') ) {
                                        global $td_woo_state_single_product_page;
                                        $custom_field_data = $td_woo_state_single_product_page->product_custom_field->__invoke($atts);
                                    }
                                    break;
                                case 'woo_archive':
                                    if ( defined('TD_WOO') ) {
                                        global $td_woo_state_archive_product_page;
                                        $custom_field_data = $td_woo_state_archive_product_page->archive_product_custom_field->__invoke($atts);
                                    }
                                    break;
                                default:
                                    $custom_field_data = $tdb_state_single_page->page_custom_field->__invoke($atts);
                                    break;
                            }
                        }
                    } else {
                        global $post;

                        $page_id = $post->ID;
                        $page_obj = get_post($page_id);

                        if ( $page_obj ) {
                            $custom_field_data = self::get_acf_field_data($field_name, $page_obj);

                            if ( !$custom_field_data['meta_exists'] ) {
                                if( metadata_exists('post', $page_id, $field_name) ) {
                                    $custom_field_data['value'] = get_post_meta($page_id, $field_name, true);
                                    $custom_field_data['type'] = 'text';
                                    $custom_field_data['meta_exists'] = true;
                                }
                            }
                        }
                    }

                    $custom_fields_to_replace[$index] = $custom_field_matches[0][$index];
                    $custom_field_replacements[$index] = '';

                    $custom_field_value = $custom_field_data['value'];
                    if( is_array( $custom_field_value ) ) {
                        if ( $custom_field_data['type'] === 'image' ) {
                             if ( !empty($custom_field_value['id']) ) {
                                 $custom_field_replacements[$index] = $custom_field_value['id'];
                             }
                        } else if( $custom_field_data['type'] === 'file' ) {
                            $file_info = array();

                            if( is_array( $custom_field_value ) ) {
                                $file_info = get_post( $custom_field_value['ID'] );
                            } else if( is_string( $custom_field_value ) ) {
                                $file_info = get_post( attachment_url_to_postid( $custom_field_value ) );
                            } else if( is_numeric( $custom_field_value ) ) {
                                $file_info = get_post( $custom_field_value );
                            }

                            if( $file_info ) {
                                $file_url = $file_info->guid;
                                $file_mime = wp_check_filetype($file_url);

                                if( strpos( $file_mime['type'], 'image' ) !== false ) {
                                    $custom_field_replacements[$index] = $file_info->ID;
                                } else {
                                    $custom_field_replacements[$index] = $file_url;
                                }
                            }
                        } else {
                            foreach ( $custom_field_value as $key => $value ) {
                                if( is_array( $value ) ) {
                                    $custom_field_replacements[$index] .= $value['label'];
                                } else if( self::isAssocArray( $custom_field_value ) ) {
                                    if( $key == 'label' ) {
                                        $custom_field_replacements[$index] .= $value;
                                    }
                                } else {
                                    $custom_field_replacements[$index] .= $value;
                                }

                                if( $key != array_key_last( $custom_field_value ) ) {
                                    $custom_field_replacements[$index] .= ', ';
                                }
                            }
                        }
                    } else {
                        if ( $custom_field_data['value'] == 'Sample field data' ) {
                            $custom_field_replacements[$index] = '';
                        } else {
                            $custom_field_value = $custom_field_data['value'];

                            if( $custom_field_data['type'] === 'file' ) {
                                $file_info = array();

                                if( is_string( $custom_field_value ) ) {
                                    $file_info = get_post( attachment_url_to_postid( $custom_field_value ) );
                                } else if( is_numeric( $custom_field_value ) ) {
                                    $file_info = get_post( $custom_field_value );
                                }

                                if( $file_info ) {
                                    $file_url = $file_info->guid;
                                    $file_mime = wp_check_filetype($file_url);

                                    if( strpos( $file_mime['type'], 'image' ) !== false ) {
                                        $custom_field_replacements[$index] = $file_info->ID;
                                    } else {
                                        $custom_field_replacements[$index] = $file_info->guid;
                                    }
                                }
                            } else {
                                $custom_field_replacements[$index] = $custom_field_value;
                            }
                        }
                    }
                }

            }

        }

        if( !empty( $custom_fields_to_replace ) ) {
            $string = str_replace($custom_fields_to_replace, $custom_field_replacements, $string);
        }


        // Replace the {url_post_id} string
        if( !( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) ) {
            if (strpos($string, '{url_post_id}')) {
                $replace_with = '';

                if (isset($_GET['post_id'])) {
                    $replace_with = $_GET['post_id'];
                }

                $string = str_replace('{url_post_id}', $replace_with, $string);
            }
        }


        return $string;

    }

    static function get_acf_field_data($field_name, $queried_obj) {

        $field_data = array(
            'label' => '',
            'value' => '',
            'type' => '',
            'meta_exists' => false,
        );

        if( class_exists('ACF') ) {
            $acf_field = get_field_object($field_name, $queried_obj);

            if ( $acf_field ) {
                $field_data['label'] = $acf_field['label'];
                $field_data['value'] = $acf_field['value'];
                $field_data['type'] = $acf_field['type'];
                $field_data['meta_exists'] = true;

                if( isset($acf_field['taxonomy']) ) {
                    $field_data['taxonomy'] = $acf_field['taxonomy'];
                }
                if( $acf_field['type'] == 'user' || $acf_field['type'] == 'post_object' ) {
                    $field_data['multiple'] = !empty($acf_field['multiple']);
                }

                if ( empty($field_data['value']) && $acf_field['type'] == 'image' && ( tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax() ) ) {
                    $field_data['value'] = array(
                        'ID' => 0,
                        'url' => TDC_URL . '/assets/images/placeholders/custom_field_image_type.png',
                        'title' => '',
                        'alt' => '',
                    );
                }
            }
        }

        return $field_data;

    }



    static function get_cloud_tpl_var_value_from_string( $string ) {

        // Replace the variable names with their values
        $variables_to_replace = [];
        $variables_replacements = [];

        preg_match_all('/{tpl_(\S*)}/', $string, $variables_matches);

        if( !empty($variables_matches) &&
            is_array($variables_matches) &&
            count($variables_matches) >= 2 &&
            is_array($variables_matches[0]) && !empty($variables_matches[0]) &&
            is_array($variables_matches[1]) ) {

            foreach ( $variables_matches[1] as $index => $variable ) {
                if ( $variable != '' ) {
                    if ( td_global::is_tdb_registered() ) {
                        global $tdb_module_template_params;

                        if( $tdb_module_template_params !== NULL ) {
                            $post_obj = $tdb_module_template_params['post_obj'];

                            if ( gettype($post_obj) === 'object' && get_class($post_obj) === 'WP_Post' ) {
                                $post_obj_id = $post_obj->ID;

                                switch( $variable ) {
                                    case 'post_url':
                                        $variables_to_replace[$index] = $variables_matches[0][$index];
                                        $variables_replacements[$index] = get_the_permalink( $post_obj_id );

                                        break;
                                }
                            }
                        }
                    }
                }
            }

        }

        if( !empty( $variables_to_replace ) ) {
            $string = str_replace($variables_to_replace, $variables_replacements, $string);
        }


        return $string;

    }


    static function isAssocArray( $array ) {
        return count( array_filter( array_keys( $array ), 'is_string') ) > 0;
    }

    static function get_gm_api_key() {

        $gm_api_key = '';
        if( td_util::get_option('tds_gm_api_key') != '' ) {
            $gm_api_key = td_util::get_option('tds_gm_api_key');
        }

        return $gm_api_key;
    }

    static function get_bm_api_key() {

        $bm_api_key = '';
        if( td_util::get_option('tds_bm_api_key') != '' ) {
            $bm_api_key = td_util::get_option('tds_bm_api_key');
        }

        return $bm_api_key;
    }

    static function get_overall_post_rating($post_id) {

        $post_linked_posts = get_post_meta($post_id, 'tdc-post-linked-posts', true);

        if( isset( $post_linked_posts['tdc-review'] ) ) {
            $post_reviews_ids = $post_linked_posts['tdc-review'];

            if( !empty( $post_reviews_ids ) ) {
                $post_reviews = get_posts(array(
                    'post__in' => $post_reviews_ids,
                    'post_type' => 'tdc-review'
                ));

                if( !empty( $post_reviews ) ) {
                    $post_reviews_ratings_total = 0;
                    $post_reviews_count = count($post_reviews);

                    foreach ( $post_reviews as $post_review ) {
                        $post_review_ratings_average = self::get_overall_review_rating($post_review->ID);

                        if( $post_review_ratings_average ) {
                            $post_reviews_ratings_total += $post_review_ratings_average;
                        }
                    }

                    return round( ( $post_reviews_ratings_total / $post_reviews_count ) * 2 ) / 2;
                }
            }
        }


        return false;

    }

    static function get_breakdown_post_rating( $post_id ) {

        $reviews_ratings_overall = array();

        $post_linked_posts = get_post_meta($post_id, 'tdc-post-linked-posts', true);

        if( isset( $post_linked_posts['tdc-review'] ) ) {
            $post_reviews_ids = $post_linked_posts['tdc-review'];

            if( !empty( $post_reviews_ids ) ) {
                $post_reviews = get_posts(array(
                    'post__in' => $post_reviews_ids,
                    'post_type' => 'tdc-review'
                ));

                if( !empty( $post_reviews ) ) {
                    $reviews_ratings = array();

                    foreach ( $post_reviews as $post_review ) {
                        $post_review_ratings_meta = get_post_meta($post_review->ID, 'tdc-review-ratings', true);

                        if( !empty( $post_review_ratings_meta ) ) {
                            foreach ( $post_review_ratings_meta as $post_review_rating_data ) {
                                $reviews_ratings[$post_review_rating_data['name']][] = intval($post_review_rating_data['score']);
                            }
                        }
                    }

                    foreach( $reviews_ratings as $rating_name => $rating_scores ) {
                        $review_ratings_total = !empty($rating_scores) ? array_sum($rating_scores) : 0;
                        $review_ratings_count = count($rating_scores);

                        $reviews_ratings_overall[] = array(
                            'name' => $rating_name,
                            'score' => round( ( $review_ratings_total / $review_ratings_count ) * 2 ) / 2
                        );
                    }
                }
            }
        }

        return $reviews_ratings_overall;

    }

    static function get_overall_review_rating($post_id) {

        $post_review_ratings_meta = get_post_meta($post_id, 'tdc-review-ratings', true);

        if( !empty( $post_review_ratings_meta ) ) {
            $post_review_ratings_average = 0;
            $post_review_ratings_sum = 0;

            foreach ( $post_review_ratings_meta as $post_review_rating_id => $post_review_rating_data ) {
                $post_review_ratings_sum += $post_review_rating_data['score'];
            }

            $post_review_ratings_average = $post_review_ratings_sum / count($post_review_ratings_meta);

            return ( round( $post_review_ratings_average * 2 ) / 2 );
        }


        return false;

    }

    static function get_breakdown_review_rating( $post_id ) {

        $post_review_ratings_meta = get_post_meta($post_id, 'tdc-review-ratings', true);
        if( empty( $post_review_ratings_meta ) ) {
            $post_review_ratings_meta = array();
        }

        return $post_review_ratings_meta;

    }

    static function display_user_ratings_stars( $rating_average, $full_star_icon = '', $full_star_icon_data = '', $half_star_icon = '', $half_star_icon_data = '', $empty_star_icon = '', $empty_star_icon_data = '' ) {

        $rating_average_floor = floor($rating_average);
        $rating_average_ceil = ceil($rating_average);

        if( $empty_star_icon == '' ) {
            $empty_star_icon = '<i class="td-icon-user-rev-star-empty"></i>';
        }
        if( $half_star_icon == '' ) {
            $half_star_icon = '<i class="td-icon-user-rev-star-half"></i>';
        }
        if( $full_star_icon == '' ) {
            $full_star_icon = '<i class="td-icon-user-rev-star-full"></i>';
        }

        $buffy = '<div class="td-user-rev-stars">';
            for( $i = 0; $i < $rating_average_floor; $i++ ) {
                $buffy .= '<div class="td-user-rev-star td-user-rev-star-full" ' . $full_star_icon_data . '>' . $full_star_icon . '</div>';
            }
            if( $rating_average_floor != $rating_average ) {
                $buffy .= '<div class="td-user-rev-star td-user-rev-star-half" ' . $half_star_icon_data . '>' . $half_star_icon . '</div>';
            }
            for( $i = 5; $i > $rating_average_ceil; $i-- ) {
                $buffy .= '<div class="td-user-rev-star td-user-rev-star-empty" ' . $empty_star_icon_data . '>' . $empty_star_icon . '</div>';
            }
        $buffy .= '</div>';

        return $buffy;

    }

    static function get_display_restrictions_atts($group = '') {
        $tdbTemplateType = '';
        if ( defined( 'TD_CLOUD_LIBRARY' ) ) {
            $tdbTemplateType = tdb_util::get_get_val('tdbTemplateType');
        }

        $author_plan_id_description = 'This setting only applies on Single Post and Author Cloud Templates.';
        if( $tdbTemplateType == 'single' || $tdbTemplateType == 'cpt' ) {
            $author_plan_id_description = 'Show the element only if the author of this article is subscribed to one of the plan IDs you enter here.';
        } else if ( $tdbTemplateType == 'author' ) {
            $author_plan_id_description = 'Show the element only if the author is subscribed to one of the plan IDs you enter here.';
        }

        $params = array(
            array(
                "param_name" => "separator",
                "type" => "text_separator",
                'heading' => 'Display restrictions',
                "value" => "",
                "class" => "",
                "group" => $group,
            ),
            array(
                'param_name' => 'hide_for_user_type',
                "type" => "dropdown",
                "value" => array(
                    'Off' => '',
                    'Logged in users' => 'logged-in',
                    'Visitors' => 'guests',
                ),
                "heading" => 'Hide for',
                "description" => "Choose the type of user you want this element to be hidden from.",
                "holder" => "div",
                'class' => 'tdc-dropdown-big',
                "group" => $group,
                'toggle_enable_params' => 'subscr-restr',
                'toggle_enable_params_reverse' => true,
            ),
            array(
                "param_name" => "separator",
                "type" => "text_separator",
                'heading' => 'Subscriptions',
                "value" => "",
                "class" => "tdc-separator-small " . ( !defined( 'TD_SUBSCRIPTION' ) || !method_exists( 'tds_util', 'is_user_subscribed_to_plan' ) ? 'tdc-hidden' : '' ),
                "group" => $group,
                'toggle_enabled_by' => 'subscr-restr',
            ),
            array(
                "param_name"  => "logged_plan_id",
                "type"        => "textfield",
                "value"       => '',
                "heading"     => 'Default plans restriction',
                "description" => 'Show the element only if the logged in user is subscribed to one of the plan IDs you enter here.',
                "holder"      => "div",
                "class"       => "tdc-textfield-big " . ( !defined( 'TD_SUBSCRIPTION' ) || !method_exists( 'tds_util', 'is_user_subscribed_to_plan' ) ? 'tdc-hidden' : '' ),
                "placeholder" => "",
                "group" => $group,
                'toggle_enabled_by' => 'subscr-restr',
            ),
            array(
                "param_name"  => "author_plan_id",
                "type"        => "textfield",
                "value"       => '',
                "heading"     => 'Author plans restriction',
                "description" => $author_plan_id_description,
                "holder"      => "div",
                "class"       => "tdc-textfield-big " . ( !defined( 'TD_SUBSCRIPTION' ) || !method_exists( 'tds_util', 'is_user_subscribed_to_plan' ) ? 'tdc-hidden' : '' ),
                "placeholder" => "",
                "group" => $group,
                'toggle_enabled_by' => 'subscr-restr',
            )
        );

        return $params;
    }

    static function plan_limit( $author_plan_ids = '', $all_users_plan_ids = '' ) {

        $current_user = wp_get_current_user();
        $current_user_id = $current_user->ID;
        $is_current_user_admin = in_array('administrator', $current_user->roles);

        $is_subscribed = true;

        if(
            !( tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax() ) &&
            !$is_current_user_admin &&
            defined( 'TD_SUBSCRIPTION' ) &&
            method_exists( 'tds_util', 'is_user_subscribed_to_plan' ) )
        {
            if ( defined( 'TD_CLOUD_LIBRARY' ) ) {
                if( $author_plan_ids != '' ) {
                    $tdb_template_type = tdb_state_template::get_template_type();

                    if( $tdb_template_type == 'cpt' || $tdb_template_type == 'single' || $tdb_template_type == 'author' ) {
                        global $tdb_state_single, $tdb_state_author;

                        $is_subscribed = false;
                        $author_plan_ids = explode(',', $author_plan_ids);
                        $author_id = '';

                        if ( $tdb_template_type == 'cpt' || $tdb_template_type == 'single' ) {
                            $author_id = get_post_field('post_author', $tdb_state_single->post_id->__invoke());
                        } else if ( $tdb_template_type == 'author' ) {
                            $author_id = $tdb_state_author->author_id->__invoke();
                        }

                        foreach ( $author_plan_ids as $plan_id ) {
                            if( tds_util::is_user_subscribed_to_plan( $author_id, $plan_id ) ) {
                                $is_subscribed = true;
                                break;
                            }
                        }
                    }
                }
            }

            if( $all_users_plan_ids != '' ) {
                $is_subscribed = false;
                $all_users_plan_ids = explode(',', $all_users_plan_ids);

                foreach ( $all_users_plan_ids as $plan_id ) {
                    if( tds_util::is_user_subscribed_to_plan( $current_user_id, $plan_id ) ) {
                        $is_subscribed = true;
                        break;
                    }
                }
            }
        }

        return $is_subscribed;

    }

	/**
     * get white label value
	 * @param $val
	 * @param $def_val
	 *
	 * @return array|mixed|string
	 */
    static function get_wl_val($val, $def_val = '') {
	    if ( 'enabled' === td_util::get_option('tds_white_label') && !empty($option = td_util::get_option($val))) {
		    return $option;
	    }
        return $def_val;
    }

    static function get_custom_svg_icon( $icon_id, $custom_class = '' ) {

        $buffy = '';


        $tdc_wm_custom_svg_icons = td_util::get_option('tdc_wm_custom_svg_icons');

        if( $tdc_wm_custom_svg_icons != '' ) {
            if( isset( $tdc_wm_custom_svg_icons[$icon_id] ) ) {
                $tdc_wm_custom_svg_icon = $tdc_wm_custom_svg_icons[$icon_id];
                $data_icon = '';

                if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
                    $data_icon = 'data-td-svg-icon="' . $icon_id . '"';
                }

                $buffy .= '<div class="tdc-wm-custom-svg-icon ' . $custom_class . '" ' . $data_icon . '>';
                    $buffy .= base64_decode($tdc_wm_custom_svg_icon['code']);
                $buffy .= '</div>';
            }
        }


        return $buffy;

    }

    static function get_attachment_id( $url ) {

        global $wpdb;

        $dir  = wp_get_upload_dir();
        $path = $url;

        $site_url   = parse_url( $dir['url'] );
        $image_path = parse_url( $path );

        // Force the protocols to match if needed.
        if ( isset( $image_path['scheme'] ) && ( $image_path['scheme'] !== $site_url['scheme'] ) ) {
            $path = str_replace( $image_path['scheme'], $site_url['scheme'], $path );
        }

        if ( 0 === strpos( $path, $dir['baseurl'] . '/' ) ) {
            $path = substr( $path, strlen( $dir['baseurl'] . '/' ) );
        }

        $sql = $wpdb->prepare(
            "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s",
            '%' . $path . '%'
        );

        $results = $wpdb->get_results( $sql );
        $post_id = null;

        if ( $results ) {
            // Use the first available result, but prefer a case-sensitive match, if exists.
            $post_id = reset( $results )->post_id;

            if ( count( $results ) > 1 ) {
                foreach ( $results as $result ) {
                    if ( $path === $result->meta_value ) {
                        $post_id = $result->post_id;
                        break;
                    }
                }
            }
        }

        /**
         * Filters an attachment ID found by URL.
         *
         * @since 4.2.0
         *
         * @param int|null $post_id The post_id (if any) found by the function.
         * @param string   $url     The URL being looked up.
         */
        return (int) apply_filters( 'attachment_url_to_postid', $post_id, $url );
    }

    static function is_post_exclusive( $post_id ) {

        if( defined( 'TD_SUBSCRIPTION' ) ) {

            $td_post_theme_settings = td_util::get_post_meta_array($post_id, 'td_post_theme_settings');
            if ( !empty($td_post_theme_settings['tds_lock_content']) ) {
                return true;
            }
        }

        return false;

    }

    static function get_tdb_template_type() {
        $tdb_template_type = '';

        if( 'Newspaper' === TD_THEME_NAME && defined( 'TD_CLOUD_LIBRARY' ) ) {
            if ( tdc_state::is_live_editor_ajax() || tdc_state::is_live_editor_iframe() ) {
                $tdb_template_type = tdb_state_template::get_template_type();
            } else {
                global $post;

                if ( !empty($post) ) {
	                $tdb_template_type = get_post_meta( $post->ID, 'tdb_template_type', true );
                }

            }

            if( $tdb_template_type == 'module' || $tdb_template_type == 'footer' ) {
                $hide_header = true;
            }
        }

        return $tdb_template_type;
    }

    static function get_the_privacy_policy_link( $before = '', $after = '' ) {

        $policy_page_id     = (int) get_option( 'wp_page_for_privacy_policy' );

        if( ( new WP_Query(['post_type' => 'any', 'p' => $policy_page_id]))->found_posts == 0 ) {
            return '';
        }

        $link               = '';
        $privacy_policy_url = get_privacy_policy_url();
        $page_title         = ( $policy_page_id ) ? get_the_title( $policy_page_id ) : '';
    
        if ( $privacy_policy_url && $page_title ) {
            $link = sprintf(
                '<a class="privacy-policy-link" href="%s">%s</a>',
                esc_url( $privacy_policy_url ),
                esc_html( $page_title )
            );
        }
    
        /**
         * Filters the privacy policy link.
         *
         * @since 4.9.6
         *
         * @param string $link               The privacy policy link. Empty string if it
         *                                   doesn't exist.
         * @param string $privacy_policy_url The URL of the privacy policy. Empty string
         *                                   if it doesn't exist.
         */
        $link = apply_filters( 'the_privacy_policy_link', $link, $privacy_policy_url );
    
        if ( $link ) {
            return $before . $link . $after;
        }
    
        return '';

    }

    static function is_base64( $string ) {
        if( $string == '' ) return false;

        // Check if there are valid base64 characters
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $string)) return false;

        // Decode the string in strict mode and check the results
        $decoded = base64_decode($string, true);
        if(false === $decoded) return false;

        // Encode the string again
        if(base64_encode($decoded) != $string) return false;

        return true;
    }

    static function is_json( $string ) {
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }

    static function is_serialized( $string ) {
        return (@unserialize($string) !== false);
    }

    static function get_favourite_articles() {
        if (isset(self::$favourite_articles)) {
            return self::$favourite_articles;
        }
        self::$favourite_articles = [];
        if ( empty($_COOKIE[self::FAVOURITE_COOKIE_ID]) || empty( $cookie_val = $_COOKIE[self::FAVOURITE_COOKIE_ID] ) ) {
            return self::$favourite_articles;
        }
        $cookie_val = explode( ',', $cookie_val );
        foreach ($cookie_val as $val ) {
            if (!empty($val)) {
                self::$favourite_articles[] = intval($val);
            }
        }

        return self::$favourite_articles;
    }

    static function is_article_favourite($article_id) {
        if (isset(self::$favourite_articles)) {
            return in_array($article_id, self::$favourite_articles);
        } else {
            if ( empty($_COOKIE[self::FAVOURITE_COOKIE_ID]) || empty( $cookie_val = $_COOKIE[self::FAVOURITE_COOKIE_ID] ) ) {
                self::$favourite_articles = [];
                return false;
            }
            if (in_array($article_id, self::$favourite_articles = explode( ',', $cookie_val ))) {
                return true;
            }
        }
    }

    static function post_exists( $post_id ) {
        return is_string( get_post_status( intval( $post_id ) ) );
    }

    static function get_color_from_var( $var ) {

        if( $var == '' ) {
            return '';
        }

        if( strpos($var, 'var') === false ) {
            return $var;
        }

        // extract the variable name
        $var_name = str_replace('-', '_', str_replace(array('var(--', ')'), array('', ''), $var));

        // get the global colors list
        $tdc_wm_global_colors = td_util::get_option('tdc_wm_global_colors');

        // proceed if the global colors list is not empty
        $var_color = '';
        if( $tdc_wm_global_colors != '' ) {
            // if the color variable received by the function exists in the list
            // then retrieve its matching hex code
            if( isset( $tdc_wm_global_colors[$var_name] ) && $tdc_wm_global_colors[$var_name]['color'] !== NULL ) {
                $var_color = $tdc_wm_global_colors[$var_name]['color'];
            }
        }


        return $var_color;

    }

    static function array_key_first( $array ) {

        if( !function_exists( 'array_key_first' ) ) {
            foreach( $array as $key => $value ) {
                return $key;
            }
        }

        return array_key_first( $array );

    }

    /*
     * This function will encrypt a string using PHP
     *
     * @param   $data (string)
     * @return  (string)
     *
     */
    static function php_openssl_encrypt( $data = '' ) {

        // bail early if no encrypt function
        if ( !function_exists( 'openssl_encrypt' ) ) {
            return base64_encode( $data );
        }

        // generate a key
        $key = wp_hash( 'td_encrypt' );

        // Generate an initialization vector
        $iv = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'aes-256-cbc' ) );

        // Encrypt the data using AES 256 encryption in CBC mode using our encryption key and initialization vector.
        $encrypted_data = openssl_encrypt( $data, 'aes-256-cbc', $key, 0, $iv );

        // The $iv is just as important as the key for decrypting, so save it with our encrypted data using a unique separator (::)
        return base64_encode( $encrypted_data . '::' . $iv );

    }


    /*
     * This function will decrypt an encrypted string using PHP
     *
     * @param   $data (string)
     * @return  (string)
     *
     */
    static function php_openssl_decrypt( $data = '' ) {

        // bail early if no decrypt function
        if ( !function_exists( 'openssl_decrypt' ) ) {
            return base64_decode( $data );
        }

        // generate a key
        $key = wp_hash( 'td_encrypt' );

        // To decrypt, split the encrypted data from our IV - our unique separator used was "::"
        list($encrypted_data, $iv) = explode( '::', base64_decode( $data ), 2 );

        // decrypt
        return openssl_decrypt( $encrypted_data, 'aes-256-cbc', $key, 0, $iv );

    }

    /**
     * Returns if the encryption libraries are loaded and the encrypt method exists.
     *
     * @return bool
     */
    public static function is_encryption_eligible() {
        return extension_loaded( 'openssl' ) && function_exists( 'openssl_encrypt' );
    }



    static function verify_captcha( $token  ) {

        // get google secret key from panel
        $captcha_secret_key = td_util::get_option('tds_captcha_secret_key' );
        $captcha_domain = td_util::get_option('tds_captcha_url') !== '' ? 'www.recaptcha.net' : 'www.google.com';

        // alter captcha result=>score
        $captcha_score = td_util::get_option('tds_captcha_score' );
        if ( $captcha_score == '' ) {
            $captcha_score = 0.5;
        }

        // for cloudflare
        if ( isset( $_SERVER["HTTP_CF_CONNECTING_IP"] ) ) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }
        $captcha_postdata = http_build_query( array(
                'secret' => $captcha_secret_key,
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            )
        );
        $captcha_opts = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $captcha_postdata
            )
        );
        $captcha_context = stream_context_create( $captcha_opts );
        $captcha_response = file_get_contents('https://' . $captcha_domain . '/recaptcha/api/siteverify', false, $captcha_context);
        $captcha_response = json_decode($captcha_response);
        //var_dump( $captcha_response );

        // check also captcha score result - default is 0.5
        if ( $captcha_response->success && $captcha_response->score >= $captcha_score ) {
            $data['success'] = true;
        } elseif ( $captcha_response->success && $captcha_response->score < $captcha_score ) {
            $data['success'] = false;
            $data['error'] = 'score failed';
        }
        else {
            $data['success'] = false;
        }

        return $data;

    }

    /**
     *
     * It detects the mobile.
     *
     * For some situations, especially for debugging, to force the mobile version of the theme for desktop agents.
     * For this, it's enough to set the mobile version from theme panel and then call the function with $force_mobile_on_desktop = true.
     * Without parameter, this function detects just the mobile version.
     *
     * @param bool $force_mobile_on_desktop - Force the mobile version of the theme.
     *
     * @return bool
     *
     */
    public static function is_mobile( $force_mobile_on_desktop = false ) {

        // load the mobile detection library
        if ( !class_exists('Mobile_Detect', false ) ) {
            require_once TDC_PATH . '/includes/Mobile_Detect.php';
        }

        if ( !isset(self::$mobile_detect) ) {
            self::$mobile_detect = new Mobile_Detect();
        }

        //return $force_mobile_on_desktop || ( self::$mobile_detect->isMobile() && !self::$mobile_detect->isTablet() );
        return $force_mobile_on_desktop || self::$mobile_detect->isMobile();

    }


    static function upload_and_attach_external_image($image_url, $post_id) {

        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Get the image content
        $image_content = file_get_contents($image_url);
        if (!$image_content) {
            return new WP_Error('http_request_failed', 'Could not fetch image.');
        }

        // Determine MIME type and suitable extension
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->buffer($image_content);
        $valid_mime_types = array('image/jpeg' => '.jpg', 'image/png' => '.png', 'image/gif' => '.gif');
        $file_ext = isset($valid_mime_types[$mime_type]) ? $valid_mime_types[$mime_type] : '';

        if (!$file_ext) {
            return new WP_Error('invalid_image', 'Unsupported image type.');
        }

        // Save the image
        $upload_dir = wp_upload_dir();
        $filename = wp_unique_filename($upload_dir['path'], uniqid() . $file_ext);
        $filepath = $upload_dir['path'] . '/' . $filename;

        file_put_contents($filepath, $image_content);

        // Prepare an array of post data for the attachment
        $attachment = array(
            'guid'           => $upload_dir['url'] . '/' . $filename,
            'post_mime_type' => $mime_type,
            'post_title'     => sanitize_file_name($filename),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        // Insert the attachment
        $attach_id = wp_insert_attachment($attachment, $filepath, $post_id);

        // Generate and update metadata for the attachment
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Optionally, set the image as the post's featured image
        set_post_thumbnail($post_id, $attach_id);

        return $attach_id;

    }

} // end class td_util



class td_category2id_array_walker extends Walker {
    var $tree_type = 'category';
    var $db_fields = array ('parent' => 'parent', 'id' => 'term_id');

    var $td_array_buffer = array();

    function start_lvl( &$output, $depth = 0, $args = array() ) {
    }

    function end_lvl( &$output, $depth = 0, $args = array() ) {
    }


    function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
        $this->td_array_buffer[str_repeat(' - ', $depth) .  $category->name . ' - [ id: ' . $category->term_id . ' ]' ] = $category->term_id;
    }


    function end_el( &$output, $page, $depth = 0, $args = array() ) {
    }

}


/*  ----------------------------------------------------------------------------
    mbstring support - if missing from host
 */
if (!function_exists('mb_strlen')) {
    function mb_strlen ($string, $encoding = '') {
        return strlen($string);
    }
}
if (!function_exists('mb_strpos')) {
    function mb_strpos($haystack,$needle,$offset=0) {
        return strpos($haystack,$needle,$offset);
    }
}
if (!function_exists('mb_strrpos')) {
    function mb_strrpos ($haystack,$needle,$offset=0) {
        return strrpos($haystack,$needle,$offset);
    }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower($string) {
        return strtolower($string);
    }
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper($string){
        return strtoupper($string);
    }
}
if (!function_exists('mb_substr')) {
    function mb_substr($string,$start,$length, $encoding = '') {
        return substr($string,$start,$length);
    }
}
if (!function_exists('mb_detect_encoding')) {
	function mb_detect_encoding ($string, $enc=null, $ret=null) {

		static $enclist = array(
			'UTF-8', 'ASCII',
			'ISO-8859-1', 'ISO-8859-2', 'ISO-8859-3', 'ISO-8859-4', 'ISO-8859-5',
			'ISO-8859-6', 'ISO-8859-7', 'ISO-8859-8', 'ISO-8859-9', 'ISO-8859-10',
			'ISO-8859-13', 'ISO-8859-14', 'ISO-8859-15', 'ISO-8859-16',
			'Windows-1251', 'Windows-1252', 'Windows-1254',
		);

		$result = false;

		foreach ($enclist as $enc_type) {
			$sample = @iconv($enc_type, $enc_type, $string);
			if (md5($sample) == md5($string)) {
				if ($ret === NULL) { $result = $enc_type; } else { $result = true; }
				break;
			}
		}

		return $result;
	}
}

if (!function_exists('td_pluralize') ) {
	/*
	 * function to pluralize a given string
	 */
	function td_pluralize( $count, $text ) {
		return $count . ( ( $count == 1 ) ? ( " $text" ) : ( " {$text}s" ) );
	}
}
if (!function_exists('td_human_readable_ts') ) {
    /*
     * function to convert given timestamp to human readable time string
     */
	function td_human_readable_ts($timestamp) {

		$future_date = new DateTime("@$timestamp");

		$interval = date_create('now')->diff( $future_date );

		$suffix = ( $interval->invert ? ' ago' : '' );
		if ( $v = $interval->days >= 1 )
		    return td_pluralize( $interval->days, 'day' ) . $suffix;
		if ( $v = $interval->h >= 1 )
		    return td_pluralize( $interval->h, 'hour' ) . $suffix;
		if ( $v = $interval->i >= 1 )
		    return td_pluralize( $interval->i, 'minute' ) . $suffix;

		return td_pluralize( $interval->s, 'second' ) . $suffix;
	}
}

/**
 * legacy code for our Aurora plugin framework that was removed from the theme in Newspaper 7.5
 * This code allows older woo_ plugins to at least run and not give a white screen of death
 */
if (!class_exists('tdx_options')) {
    class tdx_options  {
        static function get_option($datasource, $option_id ) { }
        static function update_option_in_cache($datasource, $option_id, $option_value) {}
        static function update_options_in_cache($datasource, $options_array) {}
        static function flush_options() {}
        static function register_data_source($data_source_id) {}
        static function set_data_to_datasource($datasource, $options_array) {}
    }
}

if (!class_exists('tdx_api_panel')) {
    class tdx_api_panel {
        static function add($panel_spot_id, $params_array) {}
        static function update_panel_spot($panel_spot_id, $update_array) {}
    }
}


class td_handle {

    /**
     * @param $variable
     * @return string
     */
    public static function set_var($variable) {
        if ( td_util::tdc_is_installed() ) {
            return tdc_b64_encode($variable);
        }
        return $variable;
    }

    /**
     * @param $variable
     * @return string
     */
    public static function get_var($variable) {
        if ( td_util::tdc_is_installed() ) {
            return tdc_b64_decode($variable);
        }
        return $variable;
    }

}
