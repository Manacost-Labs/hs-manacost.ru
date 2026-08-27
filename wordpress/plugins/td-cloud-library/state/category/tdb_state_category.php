<?php


/**
 * Class tdb_state_category
 * @property tdb_method category
 * @property tdb_method category_socials
 * @property tdb_method category_image
 * @property tdb_method category_description
 * @property tdb_method category_title
 * @property tdb_method category_breadcrumbs
 * @property tdb_method category_sibling_categories
 * @property tdb_method category_grid
 * @property tdb_method loop
 * @property tdb_method menu
 * @property tdb_method list_menu
 * @property tdb_method term_description
 * @property tdb_method category_custom_field
 * @property tdb_method category_gallery
 * @property tdb_method cpt
 * @property tdb_method cpt_archive_title
 * @property tdb_method cpt_description
 * @property tdb_method cpt_archive_breadcrumbs
 * @property tdb_method cpt_archive_loop
 *
 */
class tdb_state_category extends tdb_state_base {

    private $category_obj = '';

    private $term_obj = '';

    private $post_type_obj = '';


    /**
     * @param WP_Query $wp_query
     */
    function set_wp_query($wp_query) {

        parent::set_wp_query($wp_query);

        //echo '<pre class="td-container">';
        //var_dump(self::is_cpt_post_type_archive());
        //var_dump(self::is_tax());
        //print_r($wp_query);
        //echo '</pre>';
        //die;

        if ( self::is_cpt_post_type_archive() ) {

            $queried_object = get_queried_object();
            if ( $queried_object instanceof WP_Post_Type ) {
                $this->post_type_obj = $queried_object;
            }

            $cpt_wp_query_post_type = $this->get_wp_query()->get('post_type');
            if ( !empty($cpt_wp_query_post_type) ) {
                $this->post_type_obj = get_post_type_object($cpt_wp_query_post_type);
            }

            //echo '<pre class="td-container">';
            //print_r($this->post_type_obj);
            //echo '</pre>';
            //die;

        } elseif ( self::is_tax() ) {

        	$queried_object = get_queried_object();
	        if ( $queried_object instanceof WP_Term ) {
	            $this->term_obj = $queried_object;
	        }

	        $category_wp_query = $this->get_wp_query();
	        if ( !empty( $category_wp_query->queried_object ) ) {
		        $this->term_obj = $category_wp_query->queried_object;
			} else if ( !empty( $category_wp_query->tax_query ) && $category_wp_query->tax_query instanceof WP_Tax_Query ) {
				$this->term_obj = get_term( $category_wp_query->query_vars['tax_query'][0]['terms'] );
	        }

        } else {

	        $category_wp_query = $this->get_wp_query();
	        if ( isset( $category_wp_query->query['cat'] ) ) {
		        $this->category_obj = get_category( $category_wp_query->query['cat'] );
	        } elseif ( isset( $category_wp_query->query_vars['category_name'] ) ) {
		        $this->category_obj = get_category_by_slug( $category_wp_query->query_vars['category_name'] );
	        } else {
		        $this->category_obj = get_category('1');
	        }

        }

    }


    public function __construct() {


    	if ( is_tax() ) {
    		self::set_tax();
	    }


    	if ( is_post_type_archive() ) {
    		self::set_cpt_post_type_archive();
	    }


        // category loop
        $this->loop = function ( $atts ) {

        	if ( self::is_tax() ) {

        		$svg_list = td_global::$svg_theme_font_list;

				// previous text icon
				$prev_icon_html = '<i class="page-nav-icon td-icon-menu-left"></i>';
				if( isset( $atts['prev_tdicon'] ) ) {
					$prev_icon = $atts['prev_tdicon'];
					$prev_icon_data = '';
					if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
						$prev_icon_data = 'data-td-svg-icon="' . $prev_icon . '"';
					}

					if( array_key_exists( $prev_icon, $svg_list ) ) {
						$prev_icon_html = '<div class="page-nav-icon page-nav-icon-svg" ' . $prev_icon_data . '>' . base64_decode( $svg_list[$prev_icon] ) . '</div>';
					} else {
						$prev_icon_html = '<i class="page-nav-icon ' . $prev_icon . '"></i>';
					}
				}
				// next text icon
				$next_icon_html = '<i class="page-nav-icon td-icon-menu-right"></i>';
				if( isset( $atts['next_tdicon'] ) ) {
					$next_icon = $atts['next_tdicon'];
					$next_icon_data = '';
					if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
						$next_icon_data = 'data-td-svg-icon="' . $next_icon . '"';
					}

					if( array_key_exists( $next_icon, $svg_list ) ) {
						$next_icon_html = '<div class="page-nav-icon page-nav-icon-svg" ' . $next_icon_data . '>' . base64_decode( $svg_list[$next_icon] ) . '</div>';
					} else {
						$next_icon_html = '<i class="page-nav-icon ' . $next_icon . '"></i>';
					}
				}

	            // pagination options
	            $pagenavi_options = array(
	                'pages_text'    => __td( 'Page %CURRENT_PAGE% of %TOTAL_PAGES%', TD_THEME_NAME ),
	                'current_text'  => '%PAGE_NUMBER%',
	                'page_text'     => '%PAGE_NUMBER%',
	                'first_text'    => __td( '1' ),
	                'last_text'     => __td( '%TOTAL_PAGES%' ),
					'next_text'     => $next_icon_html,
					'prev_text'     => $prev_icon_html,
	                'dotright_text' => __td( '...' ),
	                'dotleft_text'  => __td( '...' ),
	                'num_pages'     => 3,
	                'always_show'   => true
	            );

	            // pagination defaults
	            $pagination_defaults = array(
	                'pagenavi_options' => $pagenavi_options,
	                'paged' => 1,
	                'max_page' => 3,
	                'start_page' => 1,
	                'end_page' => 3,
	                'pages_to_show' => 3,
					'previous_posts_link' => '<a href="#">' . $prev_icon_html . '</a>',
					'next_posts_link' => '<a href="#">' . $next_icon_html . '</a>'
	            );

	            // posts limit - by default get the global wp loop posts limit setting
	            $limit = get_option( 'posts_per_page' );
	            if ( isset( $atts['limit'] ) ) {
	                $limit = $atts['limit'];
	            }

	            // posts offset
	            $offset = 0;
	            if ( isset( $atts['offset'] ) ) {
	                $offset = $atts['offset'];
	            }

	            $dummy_data_array = array(
	                'loop_posts' => array(),
	                'limit'      => $limit,
	                'offset'     => $offset
	            );

	            for ( $i = $offset; $i < $limit + $offset; $i++ ) {
	                $dummy_data_array['loop_posts'][$i] = array(
	                    'post_id' => '-' . $i, // negative post_id to avoid conflict with existent posts
	                    'post_type' => 'sample',
	                    'post_link' => '#',
	                    'post_title' => 'Sample post title ' . $i,
	                    'post_title_attribute' => esc_attr( 'Sample post title ' . $i ),
	                    'post_excerpt' => 'Sample post no ' . $i .  ' excerpt.',
	                    'post_content' => 'Sample post no ' . $i .  ' content.',
	                    'post_date_unix' =>  get_the_time( 'U' ),
	                    'post_date' => date( get_option( 'date_format' ), time() ),
	                    'post_modified' => date( get_option( 'date_format' ), time() ),
	                    'post_author_url' => '#',
	                    'post_author_name' => 'Author name',
	                    'post_author_email' => get_the_author_meta( 'email', 1 ),
	                    'post_comments_no' => '11',
	                    'post_comments_link' => '#',
	                    'post_theme_settings' => array(
	                        'td_primary_cat' => '1'
	                    ),
	                );
	            }

	            $dummy_data_array['loop_pagination'] = $pagination_defaults;
	            $dummy_data_array['tag_slug'] = '';
	            $dummy_data_array['term_id'] = '';

	            if ( !$this->has_wp_query() ) {
	                return $dummy_data_array;
	            }

	            $data_array = array();
	            $data_array['limit'] = $limit;

	            $state_wp_query = $this->get_wp_query();

	            foreach ( $state_wp_query->posts as $post ) {

	                $data_array['loop_posts'][$post->ID] = array(
	                    'post_id' => $post->ID,
	                    'post_type' => get_post_type( $post->ID ),
	                    'has_post_thumbnail' => has_post_thumbnail( $post->ID ),
	                    'post_thumbnail_id' => get_post_thumbnail_id( $post->ID ),
	                    'post_link' => esc_url( get_permalink( $post->ID ) ),
	                    'post_title' => get_the_title( $post->ID ),
	                    'post_title_attribute' => esc_attr( strip_tags( get_the_title( $post->ID ) ) ),
	                    'post_excerpt' => $post->post_excerpt,
	                    'post_content' => $post->post_content,
	                    'post_date_unix' =>  get_the_time( 'U', $post->ID ),
	                    'post_date' => get_the_time( get_option( 'date_format' ), $post->ID ),
	                    'post_modified' => get_the_modified_date(get_option( 'date_format' ), $post->ID),
	                    'post_author_url' => get_author_posts_url( $post->post_author ),
	                    'post_author_name' => get_the_author_meta( 'display_name', $post->post_author ),
	                    'post_author_email' => get_the_author_meta( 'email', $post->post_author ),
	                    'post_comments_no' => get_comments_number( $post->ID ),
	                    'post_comments_link' => get_comments_link( $post->ID ),
	                    'post_theme_settings' => td_util::get_post_meta_array( $post->ID, 'td_post_theme_settings' ),
	                );

	            }

	            $data_array['loop_pagination'] = $pagination_defaults;

	            $paged = intval( $state_wp_query->query_vars['paged'] );

	            if ( $paged === 0 ) {
	                $paged = 1;
	            }

	            $max_page = $state_wp_query->max_num_pages;

	            $pages_to_show         = intval( $pagenavi_options['num_pages'] );
	            $pages_to_show_minus_1 = $pages_to_show - 1;
	            $half_page_start       = floor($pages_to_show_minus_1/2 );
	            $half_page_end         = ceil($pages_to_show_minus_1/2 );
	            $start_page            = $paged - $half_page_start;

	            if( $start_page <= 0 ) {
	                $start_page = 1;
	            }

	            $end_page = $paged + $half_page_end;
	            if( ( $end_page - $start_page ) != $pages_to_show_minus_1 ) {
	                $end_page = $start_page + $pages_to_show_minus_1;
	            }

	            if( $end_page > $max_page ) {
	                $start_page = $max_page - $pages_to_show_minus_1;
	                $end_page = $max_page;
	            }

	            if( $start_page <= 0 ) {
	                $start_page = 1;
	            }

	            $data_array['loop_pagination']['paged'] = $paged;
	            $data_array['loop_pagination']['max_page'] = $max_page;
	            $data_array['loop_pagination']['start_page'] = $start_page;
	            $data_array['loop_pagination']['end_page'] = $end_page;
	            $data_array['loop_pagination']['pages_to_show'] = $pages_to_show;

	            global $wp_query, $tdb_state_category, $paged;
	            $template_wp_query = $wp_query;

	            $wp_query = $tdb_state_category->get_wp_query();
	            $paged = intval( $state_wp_query->query_vars['paged'] );

	            $data_array['loop_pagination']['previous_posts_link'] = get_previous_posts_link( $pagenavi_options['prev_text'] );
	            $data_array['loop_pagination']['next_posts_link'] = get_next_posts_link( $pagenavi_options['next_text'], $max_page );

	            $wp_query = $template_wp_query;

	            $data_array['term_obj'] = $this->term_obj;
	            $data_array['term_id'] = $this->term_obj->term_id;
	            $data_array['tag_slug'] = $this->term_obj->slug;

	            return $data_array;

	        } else {

		        $svg_list = td_global::$svg_theme_font_list;

		        // previous text icon
		        $prev_icon_html = '<i class="page-nav-icon td-icon-menu-left"></i>';
		        if ( isset( $atts[ 'prev_tdicon' ] ) ) {
			        $prev_icon = $atts[ 'prev_tdicon' ];
                    $prev_icon_data = '';
                    if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
                        $prev_icon_data = 'data-td-svg-icon="' . $prev_icon . '"';
                    }

			        if ( array_key_exists( $prev_icon, $svg_list ) ) {
				        $prev_icon_html = '<div class="page-nav-icon page-nav-icon-svg" ' . $prev_icon_data . '>' . base64_decode( $svg_list[ $prev_icon ] ) . '</div>';
			        } else {
				        $prev_icon_html = '<i class="page-nav-icon ' . $prev_icon . '"></i>';
			        }
		        }
		        // next text icon
		        $next_icon_html = '<i class="page-nav-icon td-icon-menu-right"></i>';
		        if ( isset( $atts[ 'next_tdicon' ] ) ) {
			        $next_icon = $atts[ 'next_tdicon' ];
                    $next_icon_data = '';
                    if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
                        $next_icon_data = 'data-td-svg-icon="' . $next_icon . '"';
                    }

			        if ( array_key_exists( $next_icon, $svg_list ) ) {
				        $next_icon_html = '<div class="page-nav-icon page-nav-icon-svg" ' . $next_icon_data . '>' . base64_decode( $svg_list[ $next_icon ] ) . '</div>';
			        } else {
				        $next_icon_html = '<i class="page-nav-icon ' . $next_icon . '"></i>';
			        }
		        }

		        // pagination options
		        $pagenavi_options = array(
			        'pages_text'    => __td( 'Page %CURRENT_PAGE% of %TOTAL_PAGES%', TD_THEME_NAME ),
			        'current_text'  => '%PAGE_NUMBER%',
			        'page_text'     => '%PAGE_NUMBER%',
			        'first_text'    => __td( '1' ),
			        'last_text'     => __td( '%TOTAL_PAGES%' ),
			        'next_text'     => $next_icon_html,
			        'prev_text'     => $prev_icon_html,
			        'dotright_text' => __td( '...' ),
			        'dotleft_text'  => __td( '...' ),
			        'num_pages'     => 3,
			        'always_show'   => true
		        );

		        // pagination defaults
		        $pagination_defaults = array(
			        'pagenavi_options'    => $pagenavi_options,
			        'paged'               => 1,
			        'max_page'            => 3,
			        'start_page'          => 1,
			        'end_page'            => 3,
			        'pages_to_show'       => 3,
			        'previous_posts_link' => '<a href="#">' . $prev_icon_html . '</a>',
			        'next_posts_link'     => '<a href="#">' . $next_icon_html . '</a>'
		        );

		        // posts limit - by default get the global wp loop posts limit setting
		        $limit = get_option( 'posts_per_page' );
		        if ( isset( $atts[ 'limit' ] ) ) {
			        $limit = $atts[ 'limit' ];
		        }

		        // posts offset
		        $offset = 0;
		        if ( isset( $atts[ 'offset' ] ) ) {
			        $offset = $atts[ 'offset' ];
		        }

		        $dummy_data_array = array(
			        'loop_posts' => array(),
			        'cat'        => '',
			        'limit'      => $limit,
			        'offset'     => $offset
		        );

		        for ( $i = (int) $offset; $i < (int) $limit + (int) $offset; $i ++ ) {
			        $dummy_data_array[ 'loop_posts' ][ $i ] = array(
				        'post_id'              => '-' . $i, // negative post_id to avoid conflict with existent posts
				        'post_type'            => 'sample',
				        'post_link'            => '#',
				        'post_title'           => 'Sample post title ' . $i,
				        'post_title_attribute' => esc_attr( 'Sample post title ' . $i ),
				        'post_excerpt'         => 'Sample post no ' . $i . ' excerpt.',
				        'post_content'         => 'Sample post no ' . $i . ' content.',
				        'post_date_unix'       => get_the_time( 'U' ),
				        'post_date'            => date( get_option( 'date_format' ), time() ),
				        'post_modified'        => date( get_option( 'date_format' ), time() ),
				        'post_author_url'      => '#',
				        'post_author_name'     => 'Author name',
				        'post_author_email'    => get_the_author_meta( 'email', 1 ),
				        'post_comments_no'     => '11',
				        'post_comments_link'   => '#',
				        'post_theme_settings'  => array(
					        'td_primary_cat' => '1'
				        ),
			        );
		        }

		        $dummy_data_array[ 'loop_pagination' ] = $pagination_defaults;
		        $dummy_data_array[ 'category_id' ]     = '';

		        if ( ! $this->has_wp_query() ) {
			        return $dummy_data_array;
		        }

		        $data_array            = array();
		        $data_array[ 'limit' ] = $limit;
		        $data_array[ 'cat' ]   = $this->category_obj->cat_ID;

		        /*
				 *
				 * category object
				 *
				 * stdClass Object
					(
						[term_id] => 85
						[name] => Category Name
						[slug] => category-name
						[term_group] => 0
						[term_taxonomy_id] => 85
						[taxonomy] => category
						[description] =>
						[parent] => 70
						[count] => 0
						[cat_ID] => 85
						[category_count] => 0
						[category_description] =>
						[cat_name] => Category Name
						[category_nicename] => category-name
						[category_parent] => 70
					)
				 *
				 *
				 * */

		        $state_wp_query = $this->get_wp_query();

		        foreach ( $state_wp_query->posts as $post ) {

			        $data_array[ 'loop_posts' ][ $post->ID ] = array(
				        'post_id'              => $post->ID,
				        'post_type'            => get_post_type( $post->ID ),
				        'has_post_thumbnail'   => has_post_thumbnail( $post->ID ),
				        'post_thumbnail_id'    => get_post_thumbnail_id( $post->ID ),
				        'post_link'            => esc_url( get_permalink( $post->ID ) ),
				        'post_title'           => get_the_title( $post->ID ),
				        'post_title_attribute' => esc_attr( strip_tags( get_the_title( $post->ID ) ) ),
				        'post_excerpt'         => $post->post_excerpt,
				        'post_content'         => $post->post_content,
				        'post_date_unix'       => get_the_time( 'U', $post->ID ),
				        'post_date'            => get_the_time( get_option( 'date_format' ), $post->ID ),
				        'post_modified'        => get_the_modified_date( get_option( 'date_format' ), $post->ID ),
				        'post_author_url'      => get_author_posts_url( $post->post_author ),
				        'post_author_name'     => get_the_author_meta( 'display_name', $post->post_author ),
				        'post_author_email'    => get_the_author_meta( 'email', $post->post_author ),
				        'post_comments_no'     => get_comments_number( $post->ID ),
				        'post_comments_link'   => get_comments_link( $post->ID ),
				        'post_theme_settings'  => td_util::get_post_meta_array( $post->ID, 'td_post_theme_settings' ),
			        );

		        }

		        $data_array[ 'loop_pagination' ] = $pagination_defaults;

		        $paged = intval( $state_wp_query->query_vars[ 'paged' ] );

		        if ( $paged === 0 ) {
			        $paged = 1;
		        }

		        $max_page = $state_wp_query->max_num_pages;

		        $pages_to_show         = intval( $pagenavi_options[ 'num_pages' ] );
		        $pages_to_show_minus_1 = $pages_to_show - 1;
		        $half_page_start       = floor( $pages_to_show_minus_1 / 2 );
		        $half_page_end         = ceil( $pages_to_show_minus_1 / 2 );
		        $start_page            = $paged - $half_page_start;

		        if ( $start_page <= 0 ) {
			        $start_page = 1;
		        }

		        $end_page = $paged + $half_page_end;
		        if ( ( $end_page - $start_page ) != $pages_to_show_minus_1 ) {
			        $end_page = $start_page + $pages_to_show_minus_1;
		        }

		        if ( $end_page > $max_page ) {
			        $start_page = $max_page - $pages_to_show_minus_1;
			        $end_page   = $max_page;
		        }

		        if ( $start_page <= 0 ) {
			        $start_page = 1;
		        }

		        $data_array[ 'loop_pagination' ][ 'paged' ]         = $paged;
		        $data_array[ 'loop_pagination' ][ 'max_page' ]      = $max_page;
		        $data_array[ 'loop_pagination' ][ 'start_page' ]    = $start_page;
		        $data_array[ 'loop_pagination' ][ 'end_page' ]      = $end_page;
		        $data_array[ 'loop_pagination' ][ 'pages_to_show' ] = $pages_to_show;

		        global $wp_query, $tdb_state_category, $paged;
		        $template_wp_query = $wp_query;

		        $wp_query = $tdb_state_category->get_wp_query();
		        $paged    = intval( $state_wp_query->query_vars[ 'paged' ] );

		        $data_array[ 'loop_pagination' ][ 'previous_posts_link' ] = get_previous_posts_link( $pagenavi_options[ 'prev_text' ] );
		        $data_array[ 'loop_pagination' ][ 'next_posts_link' ]     = get_next_posts_link( $pagenavi_options[ 'next_text' ], $max_page );

		        $wp_query = $template_wp_query;

		        $data_array[ 'category_id' ] = $this->category_obj->cat_ID;

		        return $data_array;
	        }

        };


        // post social sharing
        $this->category_socials = function ( $atts ) {

            if ( !$this->has_wp_query() ) {
                return array(
                    'post_permalink' => '#',
                    'is_amp'         => false,
                    'is_tdb_block'   => true,
                    'services'       => array(
                        'facebook',
                        'twitter',
                        'pinterest',
                        'whatsapp',
                        'linkedin',
                        'reddit',
                        'mail',
                        'print',
                        'tumblr',
                        'telegram',
                        'stumbleupon',
                        'vk',
                        'digg',
                        'line',
                        'viber',
                    ),
                    'share_text_show' => 'yes',
                    'style' => $atts['like_share_style']
                );
            }

            $category_id = self::is_tax() ? $this->term_obj->term_id : $this->category_obj->term_id;

            $post_socials_array = array();
            $post_socials_array['is_tdb_block'] = true;
            $post_socials_array['post_id'] = $category_id;
            $post_socials_array['post_permalink'] = esc_url( get_term_link( $category_id ) );
            $post_socials_array['is_amp']         = td_util::is_amp();

            $social_rel = '';
            if ( '' !== $atts['social_rel'] ) {
                $social_rel = ' rel="' . $atts['social_rel'] . '" ';
            }

            $share_text_show = false;
            if ( $atts['like_share_text'] !== 'yes' ) {
                $share_text_show = true;
            }

            $enabled_services = td_api_social_sharing_styles::_helper_get_enabled_socials();

            if ( td_util::is_amp() ) {
                $post_socials_array['services']        = array_slice( $enabled_services, 0, 5);
                $post_socials_array['style']           = 'style1';
                $post_socials_array['share_text_show'] = false;
                $post_socials_array['social_rel']      = '';
                $post_socials_array['el_class']        = '';
            } else {
                $post_socials_array['services']        = $enabled_services;
                $post_socials_array['style']           = $atts['like_share_style'];
                $post_socials_array['share_text_show'] = $share_text_show;
                $post_socials_array['social_rel']      = $social_rel;
                $post_socials_array['el_class']        = '';
            }

            return $post_socials_array;
        };


        // post background featured image
        $this->category_image = function ( $atts ) {

	        $dummy_data_array = array(
		        'background_image_src' => TDB_URL . '/assets/images/td_meta_replacement.png'
	        );

	        if ( !$this->has_wp_query() ) {
		        return $dummy_data_array;
	        }

			// data array init
	        $data_array = array(
		        'background_image_src' => ''
	        );

			// img src init
	        if ( self::is_tax() ) {

                $term_meta_img = 'tdb_filter_image';
                // check if is woo archive
                if ( strpos( $this->term_obj->taxonomy, 'pa_' ) !== false ) {
                    $term_meta_img = 'product_attribute_image';
                } elseif ( $this->term_obj->taxonomy === 'product_cat' ) {
                    $term_meta_img = 'thumbnail_id';
                }
		        // get image
		        $term_meta_img_attachment_id = get_term_meta( $this->term_obj->term_id, $term_meta_img, true );

		        if ( !empty( $term_meta_img_attachment_id ) ) {
			        $image_data = wp_get_attachment_image_src( $term_meta_img_attachment_id, 'full' );

			        if( $image_data ) {
                        $data_array['background_image_src'] = esc_url( $image_data[0] );
			        }

		        }

	        } else {
                $data_array['background_image_src'] = td_util::get_category_option( $this->category_obj->cat_ID, 'tdc_image' );
	        }

            if( $data_array['background_image_src'] == '' ) {
                $default_image = isset( $atts['default_image'] ) ? $atts['default_image'] : '';
                $default_image_data = wp_get_attachment_image_src( $default_image, 'full' );

                if( $default_image_data ) {
                    $data_array['background_image_src'] = esc_url( $default_image_data[0] );
                }
            }

            if( empty( $data_array['background_image_src'] ) && ( tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax() ) ) {
                return $dummy_data_array;
            }

	        return $data_array;

        };


        // category description
        $this->category_description = function () {

            $dummy_data_array = array(
                'cat_desc' => '<p>Sample Category Description. ( Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. )</p>'
            );

            if ( !$this->has_wp_query() ) {
                return $dummy_data_array;
            }

            if ( tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax() ) {
                if ( empty( $this->category_obj->description ) ) {
                    return $dummy_data_array;
                }
            }

            $data_array = array();

            $data_array['cat_desc'] = category_description( $this->category_obj->term_id );

            return $data_array;

        };


        // category title
        $this->category_title = function () {

        	if ( self::is_tax() ) {

        		$dummy_data_array = array(
	                'title' => 'Sample Taxonomy Page Title',
	                'page_number' => '1',
	                'class' => 'tdb-tag-title'
	            );

	            if ( !$this->has_wp_query() ) {
	                return $dummy_data_array;
	            }

                if ( !empty($this->term_obj) && $this->term_obj instanceof WP_Term ) {
                    $page_number = intval( $this->get_wp_query()->query_vars['paged'] );
                    return array(
                        'title' => $this->term_obj->name,
                        'page_number' => $page_number ?: 1,
                        'class' => 'tdb-tag-title'
                    );
                } else {
                    return $dummy_data_array;
                }

	        } else {

		        $dummy_data_array = array(
			        'title'       => 'Sample Category Title',
			        'page_number' => '1',
			        'class'       => 'tdb-category-title'
		        );

		        if ( ! $this->has_wp_query() ) {
			        return $dummy_data_array;
		        }

		        $page_number = intval( $this->get_wp_query()->query_vars[ 'paged' ] );

		        $data_array = array(
			        'title'       => $this->category_obj->name,
			        'page_number' => $page_number ? $page_number : 1,
			        'class'       => 'tdb-category-title'
		        );

		        return $data_array;
	        }
        };


        // category breadcrumbs
        $this->category_breadcrumbs = function ( $atts ) {

        	$dummy_data_array = array();
	        $show_parent      = ( $atts[ 'show_parent' ] != '' ) ? true : false;

        	if (self::is_tax()) {

        		$p_category_custom_title      = ( $atts[ 'parent_category_custom_title' ] != '' ) ? $atts[ 'parent_category_custom_title' ] : 'Parent Taxonomy';
		        $p_category_custom_title_att  = ( $atts[ 'parent_category_custom_title_att' ] != '' ) ? $atts[ 'parent_category_custom_title_att' ] : __td( 'View all posts in', TD_THEME_NAME ) . ' ' . htmlspecialchars( $p_category_custom_title );
		        $p_category_custom_title_link = ( $atts[ 'parent_category_custom_link' ] != '' ) ? $atts[ 'parent_category_custom_link' ] : '#';

		        $c_category_custom_title      = ( $atts[ 'child_category_custom_title' ] != '' ) ? $atts[ 'child_category_custom_title' ] : 'Primary/Child Taxonomy';
		        $c_category_custom_title_att  = ( $atts[ 'child_category_custom_title_att' ] != '' ) ? $atts[ 'child_category_custom_title_att' ] : __td( 'View all posts in', TD_THEME_NAME ) . ' ' . htmlspecialchars( $c_category_custom_title );
		        $c_category_custom_title_link = ( $atts[ 'child_category_custom_link' ] != '' ) ? $atts[ 'child_category_custom_link' ] : '#';

		        if ( $show_parent ) {
			        $dummy_data_array[] = array(
				        'title_attribute' => $p_category_custom_title_att,
				        'url'             => $p_category_custom_title_link,
				        'display_name'    => $p_category_custom_title
			        );
		        }

		        $dummy_data_array[] = array(
			        'title_attribute' => $c_category_custom_title_att,
			        'url'             => $c_category_custom_title_link,
			        'display_name'    => $c_category_custom_title
		        );

		        if ( ! $this->has_wp_query() ) {
			        return $dummy_data_array;
		        }

		        $category_1_name = '';
		        $category_2_name = '';
		        $category_2_url  = '';

		        $primary_category_obj = $this->term_obj;

		        if ( ! empty( $primary_category_obj ) ) {

			        if ( ! empty( $primary_category_obj->name ) ) {
				        $category_1_name = $primary_category_obj->name;
			        }

			        if ( ! empty( $primary_category_obj->parent ) and $primary_category_obj->parent != 0 ) {
				        $parent_category_obj = get_term( $primary_category_obj->parent );

				        if ( ! empty( $parent_category_obj ) ) {
					        $category_2_name = $parent_category_obj->name;
					        $category_2_url  = get_category_link( $parent_category_obj->term_id );
				        }
			        }
		        }

		        $breadcrumbs_array = array();

		        if ( ! empty( $category_1_name ) ) {

			        // parent category ( only if we have one and if it's set to show it )
			        if ( ! empty( $category_2_name ) and $show_parent ) {

				        $parent_category_custom_title     = ( $atts[ 'parent_category_custom_title' ] != '' ) ? $atts[ 'parent_category_custom_title' ] : $category_2_name;
				        $parent_category_custom_title_att = ( $atts[ 'parent_category_custom_title_att' ] != '' ) ? $atts[ 'parent_category_custom_title_att' ] : __td( 'View all posts in', TD_THEME_NAME ) . ' ' . htmlspecialchars( $parent_category_custom_title );
				        $parent_category_custom_link      = ( $atts[ 'parent_category_custom_link' ] != '' ) ? $atts[ 'parent_category_custom_link' ] : $category_2_url;

				        $breadcrumbs_array [] = array(
					        'title_attribute' => $parent_category_custom_title_att,
					        'url'             => esc_url( $parent_category_custom_link ),
					        'display_name'    => $parent_category_custom_title
				        );
			        }

			        $child_category_custom_title     = ( $atts[ 'child_category_custom_title' ] != '' ) ? $atts[ 'child_category_custom_title' ] : $category_1_name;
			        $child_category_custom_title_att = ( $atts[ 'child_category_custom_title_att' ] != '' ) ? $atts[ 'child_category_custom_title_att' ] : '';
			        $child_category_custom_link      = ( $atts[ 'child_category_custom_link' ] != '' ) ? $atts[ 'child_category_custom_link' ] : '';

			        // child category
			        $breadcrumbs_array [] = array(
				        'title_attribute' => $child_category_custom_title_att,
				        'url'             => esc_url( $child_category_custom_link ),
				        'display_name'    => $child_category_custom_title
			        );

		        }

		        return $breadcrumbs_array;

	        } else {

		        $p_category_custom_title      = ( $atts[ 'parent_category_custom_title' ] != '' ) ? $atts[ 'parent_category_custom_title' ] : 'Parent Category';
		        $p_category_custom_title_att  = ( $atts[ 'parent_category_custom_title_att' ] != '' ) ? $atts[ 'parent_category_custom_title_att' ] : __td( 'View all posts in', TD_THEME_NAME ) . ' ' . htmlspecialchars( $p_category_custom_title );
		        $p_category_custom_title_link = ( $atts[ 'parent_category_custom_link' ] != '' ) ? $atts[ 'parent_category_custom_link' ] : '#';

		        $c_category_custom_title      = ( $atts[ 'child_category_custom_title' ] != '' ) ? $atts[ 'child_category_custom_title' ] : 'Primary/Child Category';
		        $c_category_custom_title_att  = ( $atts[ 'child_category_custom_title_att' ] != '' ) ? $atts[ 'child_category_custom_title_att' ] : __td( 'View all posts in', TD_THEME_NAME ) . ' ' . htmlspecialchars( $c_category_custom_title );
		        $c_category_custom_title_link = ( $atts[ 'child_category_custom_link' ] != '' ) ? $atts[ 'child_category_custom_link' ] : '#';

		        if ( $show_parent ) {
			        $dummy_data_array[] = array(
				        'title_attribute' => $p_category_custom_title_att,
				        'url'             => $p_category_custom_title_link,
				        'display_name'    => $p_category_custom_title
			        );
		        }

		        $dummy_data_array[] = array(
			        'title_attribute' => $c_category_custom_title_att,
			        'url'             => $c_category_custom_title_link,
			        'display_name'    => $c_category_custom_title
		        );

		        if ( ! $this->has_wp_query() ) {
			        return $dummy_data_array;
		        }

		        $category_1_name = '';
		        $category_2_name = '';
		        $category_2_url  = '';

		        $primary_category_obj = $this->category_obj;

		        if ( ! empty( $primary_category_obj ) ) {

			        if ( ! empty( $primary_category_obj->name ) ) {
				        $category_1_name = $primary_category_obj->name;
			        }

			        if ( ! empty( $primary_category_obj->parent ) and $primary_category_obj->parent != 0 ) {
				        $parent_category_obj = get_category( $primary_category_obj->parent );

				        if ( ! empty( $parent_category_obj ) ) {
					        $category_2_name = $parent_category_obj->name;
					        $category_2_url  = get_category_link( $parent_category_obj->cat_ID );
				        }
			        }
		        }

		        $breadcrumbs_array = array();

		        if ( ! empty( $category_1_name ) ) {

			        // parent category ( only if we have one and if it's set to show it )
			        if ( ! empty( $category_2_name ) and $show_parent ) {

				        $parent_category_custom_title     = ( $atts[ 'parent_category_custom_title' ] != '' ) ? $atts[ 'parent_category_custom_title' ] : $category_2_name;
				        $parent_category_custom_title_att = ( $atts[ 'parent_category_custom_title_att' ] != '' ) ? $atts[ 'parent_category_custom_title_att' ] : __td( 'View all posts in', TD_THEME_NAME ) . ' ' . htmlspecialchars( $parent_category_custom_title );
				        $parent_category_custom_link      = ( $atts[ 'parent_category_custom_link' ] != '' ) ? $atts[ 'parent_category_custom_link' ] : $category_2_url;

				        $breadcrumbs_array [] = array(
					        'title_attribute' => $parent_category_custom_title_att,
					        'url'             => esc_url( $parent_category_custom_link ),
					        'display_name'    => $parent_category_custom_title
				        );
			        }

			        $child_category_custom_title     = ( $atts[ 'child_category_custom_title' ] != '' ) ? $atts[ 'child_category_custom_title' ] : $category_1_name;
			        $child_category_custom_title_att = ( $atts[ 'child_category_custom_title_att' ] != '' ) ? $atts[ 'child_category_custom_title_att' ] : '';
			        $child_category_custom_link      = ( $atts[ 'child_category_custom_link' ] != '' ) ? $atts[ 'child_category_custom_link' ] : '';

			        // child category
			        $breadcrumbs_array [] = array(
				        'title_attribute' => $child_category_custom_title_att,
				        'url'             => esc_url( $child_category_custom_link ),
				        'display_name'    => $child_category_custom_title
			        );

		        }

		        return $breadcrumbs_array;
	        }
        };


        // category grid
        $this->category_grid = function ( $atts ) {

            // set the grid limit
            $limit = get_option( 'posts_per_page' );
            if ( isset( $atts['tdb_grid_limit'] ) ) {
                $limit = $atts['tdb_grid_limit'];
            }

            // set the grid offset
            $offset = 0;
            if ( isset( $atts['offset'] ) ) {
                $offset = $atts['offset'];
            }

            // set the grid style
            $grid_style = 'td-grid-style-1';
            if ( isset( $atts['tdb_grid_style'] ) ) {
                $grid_style = $atts['tdb_grid_style'];
            }

            $dummy_data_array = array(
                'grid_style' => $grid_style,
                'grid_posts' => array()
            );

            for ( $i = $offset; $i < $limit + $offset; $i++ ) {
                $dummy_data_array['grid_posts'][$i] = array(
                    'post_id' => '-' . $i, // negative post_id to avoid conflict with existent posts
                    'post_type' => 'sample',
                    'post_link' => '#',
                    'post_title' => 'Sample post title ' . $i,
                    'post_title_attribute' => esc_attr( strip_tags( get_the_title( 'Sample post title ' . $i ) ) ),
                    'post_excerpt' => 'Sample post no ' . $i .  ' excerpt.',
                    'post_content' => 'Sample post no ' . $i .  ' content.',
                    'post_date_unix' =>  get_the_time( 'U' ),
                    'post_date' => date( get_option( 'date_format' ), time() ),
                    'post_modified' => date( get_option( 'date_format' ), time() ),
                    'post_author_url' => '#',
                    'post_author_name' => 'Author name',
                    'post_comments_no' => '11',
                    'post_comments_link' => '#',
                    'post_theme_settings' => array(
                        'td_primary_cat' => '1'
                    ),
                );
            }

            if ( !$this->has_wp_query() ) {
                return $dummy_data_array;
            }

            $args = array(
                'ignore_sticky_posts' => true,
                'post_status' => 'publish',
                'cat' => $this->category_obj->cat_ID,
                'posts_per_page' => $limit,
                'paged' => 1,
                'offset' => $offset,
            );

            $td_query = new WP_Query($args);

            $data_array = array(
                'grid_style' => array(),
                'grid_posts' => array()
            );

            $data_array['grid_style'] = $grid_style;

            foreach ( $td_query->posts as $grid_post ) {

                $data_array['grid_posts'][$grid_post->ID] = array(
                    'post_id' => $grid_post->ID,
                    'post_type' => get_post_type( $grid_post->ID ),
                    'has_post_thumbnail' => has_post_thumbnail( $grid_post->ID ),
                    'post_thumbnail_id' => get_post_thumbnail_id( $grid_post->ID ),
                    'post_link' => esc_url( get_permalink( $grid_post->ID ) ),
                    'post_title' => get_the_title( $grid_post->ID ),
                    'post_title_attribute' => esc_attr( strip_tags( get_the_title( $grid_post->ID ) ) ),
                    'post_excerpt' => $grid_post->post_excerpt,
                    'post_content' => $grid_post->post_content,
                    'post_date_unix' =>  get_the_time( 'U', $grid_post->ID ),
                    'post_date' => get_the_time( get_option( 'date_format' ), $grid_post->ID ),
                    'post_modified' => get_the_modified_date(get_option( 'date_format' ), $grid_post->ID ),
                    'post_author_url' => get_author_posts_url( $grid_post->post_author ),
                    'post_author_name' => get_the_author_meta( 'display_name', $grid_post->post_author ),
                    'post_comments_no' => get_comments_number( $grid_post->ID ),
                    'post_comments_link' => get_comments_link( $grid_post->ID ),
                    'post_theme_settings' => td_util::get_post_meta_array( $grid_post->ID, 'td_post_theme_settings' ),
                );

            }

            if ( empty( $data_array['grid_posts'] ) && ( tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax() ) ) {
                $data_array['grid_posts'] = $dummy_data_array['grid_posts'];
            }

            return $data_array;
        };


        // category sibling categories
        $this->category_sibling_categories = function ( $atts, $show_siblings = true ) {

            /* -- Set the in TD Composer flag. -- */
            $in_composer = tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax();


            /* --
            -- Get shortcode attributes.
            -- */
            /* -- Relation. -- */
            $relation = isset( $atts['relation'] ) && !empty( $atts['relation'] ) ? $atts['relation'] : 'siblings';


            /* -- Include/exclude term IDs. -- */
            $terms_ids = !empty($atts['terms_ids']) ? array_map('trim', explode(',', $atts['terms_ids'])) : array();
            $inclusion_type = !empty($atts['inclusion_type']) ? $atts['inclusion_type'] : 'include';


            /* -- Limit terms. -- */
            $limit = !empty($atts['tdb_sibling_categories_limit']) ? (int)$atts['tdb_sibling_categories_limit'] : 100;


            /* -- Terms background color. -- */
            $show_term_bg_color = true;

            if ( isset($atts['show_background_color']) && $atts[ 'show_background_color' ] === '' ) {
                $show_term_bg_color = false;
            }

            $color1 = $show_term_bg_color ? '#e33a77' : '';
            $color2 = $show_term_bg_color ? '#5c69c1' : '';
            $color3 = $show_term_bg_color ? '#a444bd' : '';



            /* --
            -- Define the dummy data array.
            -- */
            $dummy_terms_array = array(
                'categories' => array(
                    array(
                        'color'          => $color1,
                        'class'          => '',
                        'category_link'  => '#',
                        'category_name'  => 'Sample Category I',
                        'category_descr' => 'Phasellus vel mauris fringilla, tincidunt augue a, pellentesque lorem. Proin nunc tellus, placerat sed turpis eget, dapibus tempor dolor.',
                        'category_img'   => TDB_URL . '/assets/images/medium_large.png',
                    ),
                    array(
                        'color'          => $color2,
                        'class'          => '',
                        'category_link'  => '#',
                        'category_name'  => 'Sample Category II',
                        'category_descr' => 'Phasellus vel mauris fringilla, tincidunt augue a, pellentesque lorem. Proin nunc tellus, placerat sed turpis eget, dapibus tempor dolor.',
                        'category_img'   => TDB_URL . '/assets/images/medium_large.png',
                    ),
                    array(
                        'color'          => $color3,
                        'class'          => '',
                        'category_link'  => '#',
                        'category_name'  => 'Sample Category III',
                        'category_descr' => 'Phasellus vel mauris fringilla, tincidunt augue a, pellentesque lorem. Proin nunc tellus, placerat sed turpis eget, dapibus tempor dolor.',
                        'category_img'   => TDB_URL . '/assets/images/medium_large.png',
                    ),
                    array(
                        'color'          => '',
                        'class'          => '',
                        'category_link'  => '#',
                        'category_name'  => 'Sample Category IV',
                        'category_descr' => 'Phasellus vel mauris fringilla, tincidunt augue a, pellentesque lorem. Proin nunc tellus, placerat sed turpis eget, dapibus tempor dolor.',
                        'category_img'   => TDB_URL . '/assets/images/medium_large.png',
                    ),
                )
            );


            /* -- Bail if there is currently no query on this page. -- */
            if ( !$this->has_wp_query() ) {
                return $dummy_terms_array;
            }



            /* --
            -- Define the real data array.
            -- */
            $terms_array = array('categories' => array());



            /* --
            -- Retrieve the current category/taxonomy term siblings.
            -- */
            $terms_objects = array();


            /* -- If there is no current term set: -- */
            /* -- * in TD composer return the dummy terms array; -- */
            /* -- * on front-end return the empty real terms array. -- */
            if ( empty($this->term_obj->term_id) && empty($this->category_obj->cat_ID) ) {
                if ( $in_composer ) {
                    return $dummy_terms_array;
                }

                return $terms_array;
            }


            /* -- Set the current term information. -- */
            $current_term_id = self::is_tax() ? $this->term_obj->term_id : $this->category_obj->cat_ID;
            $taxonomy = self::is_tax() ? $this->term_obj->taxonomy : 'category';


            /* -- Depending on the chosen relation to the current term, get either -- */
            /* -- the term's children or parents. -- */
            if ( $relation == 'siblings' ) {
                // Check for child terms.
                $child_terms_args =  array(
                    'taxonomy' => $taxonomy,
                    'child_of' => $current_term_id,
                    'hide_empty' => false,
                    'number' => $limit
                );

                if ( !empty($terms_ids) ) {
                    if ( $inclusion_type == 'include' ) {
                        $child_terms_args['include'] = $terms_ids;
                    } else {
                        $child_terms_args['exclude'] = $terms_ids;
                    }
                }

                $terms_objects = get_terms($child_terms_args);

                // If no child terms exist and no terms IDs were manually specified, get the siblings.
                if ( empty($terms_objects) && empty($terms_ids) && $show_siblings ) {
                    $sibling_terms_args = array(
                        'taxonomy' => $taxonomy,
                        'hide_empty' => false,
                        'number' => $limit
                    );

                    $sibling_terms_args['parent'] = self::is_tax() ? ( !empty($this->term_obj->parent) ? $this->term_obj->parent : '' ) : ( !empty($this->category_obj->parent) ? $this->category_obj->parent : '' );

                    $terms_objects = get_terms($sibling_terms_args);
                }
            } else {
                // Check for parent terms.
                $parent_terms_ids = get_ancestors($current_term_id, $taxonomy);

                // If the current term has parent terms, then retrieve them.
                if ( !empty($parent_terms_ids) ) {
                    if ( !empty($terms_ids) ) {
                        if ( $inclusion_type == 'include' ) {
                            $parent_terms_ids = array_intersect($parent_terms_ids, $terms_ids);
                        } else {
                            $parent_terms_ids = array_diff($parent_terms_ids, $terms_ids);
                        }
                    }

                    $terms_objects = get_terms(array(
                        'taxonomy' => $taxonomy,
                        'include' => $parent_terms_ids,
                        'hide_empty' => 0,
                        'number' => $limit
                    ));
                }
            }


            /* -- If no terms were found: -- */
            /* -- * in TD Composer return the dummy data array; -- */
            /* -- * on front-end return the empty real data array. -- */
            if ( empty($terms_objects) ) {
                if ( $in_composer ) {
                    return $dummy_terms_array;
                }

                return $terms_array;
            }



            /* --
            -- Process the terms.
            -- */
            foreach ( $terms_objects as $term_object ) {
                /* -- If not set, ignore if it's the current term-- */
                if (
                    (($atts['include_current_cat'] ?? '') !== 'yes')
                    && $term_object->term_id == $current_term_id
                ) {
                    continue;
                }


                /* -- Ignore the 'Featured' and 'Uncategorized' terms. -- */
                if ( $term_object->name == TD_FEATURED_CAT || strtolower($term_object->name) == 'uncategorized' ) {
                    continue;
                }


                /* -- Ignore the term if it's hidden from appearing on posts. -- */
                if ( td_util::get_category_option($term_object->term_id,'tdc_hide_on_post') == 'hide' ) {
                    continue;
                }


                /* -- Set the term classes. -- */
                $term_classes = array();

                // Current term class.
                if ( $term_object->term_id == $current_term_id ) {
                    $term_classes[] = 'td-current-sub-category';
                }


                /* -- Get te term image. -- */
                $term_image = '';

                if ( $taxonomy == 'category' ) {
                    $term_image = td_util::get_category_option($term_object->term_id, 'tdc_image');

                    if ( empty($term_image) ) {
                        $term_image = TDB_URL . '/assets/images/medium_large.png';
                    }
                } else {
                    $term_meta_image_attachment_id = get_term_meta($term_object->term_id, 'tdb_filter_image', true);

                    if ( !empty($term_meta_image_attachment_id) ) {
                        $term_image_data = wp_get_attachment_image_src($term_meta_image_attachment_id, 'full');

                        if ( !empty($term_image_data) ) {
                            $term_image = $term_image_data[0];
                        } else {
                            $term_image = TDB_URL . '/assets/images/medium_large.png';
                        }
                    }
                }


                /* -- Get the term color. -- */
                $term_color = '';

                if ( $taxonomy == 'category' && $show_term_bg_color ) {
                    $term_color = td_util::get_category_option($term_object->term_id, 'tdc_color');
                }


                /* -- Add the term to the final data array. -- */
                $terms_array['categories'][] = array(
                    'class' => implode(' ', $term_classes),
                    'color' => $term_color,
                    'category_link' => get_term_link($term_object->term_id),
                    'category_name' => $term_object->name,
                    'category_descr' => $term_object->description,
                    'category_img' => $term_image
                );
            }


            /* -- If the final terms array is empty, and we are in composer, return the dummy array. -- */
            if ( empty($terms_array) && $in_composer ) {
                return $dummy_terms_array;
            }


            /* -- Return the terms array. -- */
            return $terms_array;

        };


        // menu
        $this->menu = function ( $atts ) {

            $menu_id = ( isset( $atts['menu_id'] ) and $atts['menu_id'] != '' ) ? $atts['menu_id'] : ( ! empty(get_theme_mod('nav_menu_locations')['header-menu'] ) ? get_theme_mod('nav_menu_locations')['header-menu'] : '' );

            if ( !$this->has_wp_query() ) {
                $tdb_menu_instance = tdb_menu::get_instance( $atts );
                add_filter( 'wp_nav_menu_objects', array ( $tdb_menu_instance, 'hook_wp_nav_menu_objects' ), 99999, 2 );
                $wp_nav_menu = wp_nav_menu(
                    array(
                        'menu' => $menu_id,
                        'menu_id'=> '',
                        'container' => false,
                        'menu_class'=> 'tdb-block-menu tdb-menu tdb-menu-items-visible',
                        'walker' => new tdb_tagdiv_walker_nav_menu($atts),
                        'echo' => false,
                        'fallback_cb' => function(){
                            return 'No menu items!';
                        }
                    )
                );
                remove_filter( 'wp_nav_menu_objects', array ( $tdb_menu_instance, 'hook_wp_nav_menu_objects' ), 99999 );
                return $wp_nav_menu;
            }

            global $wp_query;
            $template_wp_query = $wp_query;
            $wp_query = $this->get_wp_query();

            $tdb_menu_instance = tdb_menu::get_instance( $atts );
            add_filter( 'wp_nav_menu_objects', array ( $tdb_menu_instance, 'hook_wp_nav_menu_objects' ), 99999, 2 );
            $wp_nav_menu = wp_nav_menu(
                array(
                    'menu' => $menu_id,
                    'menu_id'=> '',
                    'container' => false,
                    'menu_class'=> 'tdb-block-menu tdb-menu tdb-menu-items-visible',
                    'walker' => new tdb_tagdiv_walker_nav_menu($atts),
                    'echo' => false,
                    'fallback_cb' => function(){
                        return 'No menu items!';
                    }
                )
            );
            remove_filter( 'wp_nav_menu_objects', array ( $tdb_menu_instance, 'hook_wp_nav_menu_objects' ), 99999 );

            $wp_query = $template_wp_query;

            return $wp_nav_menu;
        };


        // list menu
        $this->list_menu = function ( $atts ) {
            $menu_id = ( isset( $atts['menu_id'] ) and $atts['menu_id'] != '' ) ? $atts['menu_id'] : ( ! empty(get_theme_mod('nav_menu_locations')['header-menu'] ) ? get_theme_mod('nav_menu_locations')['header-menu'] : '' );
//            var_dump($menu_id);

            $depth = $atts['depth'];
            // Menu display
            $display = $atts['inline'];
            $menu_display = $display  == 'yes' ? 'horizontal' : ( $display  != '' ? $display  : 'vertical' );
            $tdb_menu_instance = td_menu::get_instance();

            if ( !$this->has_wp_query() ) {

                remove_filter( 'wp_nav_menu_objects', array($tdb_menu_instance, 'hook_wp_nav_menu_objects'), 99999 );
                $wp_nav_menu = wp_nav_menu(
                    array(
                        'menu' => $menu_id,
                        'walker' => new td_block_list_menu_accordion($atts),
                        'depth' => $menu_display == 'horizontal' ? 1 : ( $depth != '' ? $depth : 0 ),
                        'echo' => false,
                    )
                );
                add_filter( 'wp_nav_menu_objects', array($tdb_menu_instance, 'hook_wp_nav_menu_objects'), 99999, 2 );

                return $wp_nav_menu;
            }

            global $wp_query;
            $template_wp_query = $wp_query;
            $wp_query = $this->get_wp_query();

            remove_filter( 'wp_nav_menu_objects', array($tdb_menu_instance, 'hook_wp_nav_menu_objects'), 99999 );
            $wp_nav_menu = wp_nav_menu(
                array(
                    'menu' => $menu_id,
                    'walker' => new td_block_list_menu_accordion($atts),
                    'depth' => $menu_display == 'horizontal' ? 1 : ( $depth != '' ? $depth : 0 ),
                    'echo' => false,
                )
            );
            add_filter( 'wp_nav_menu_objects', array($tdb_menu_instance, 'hook_wp_nav_menu_objects'), 99999, 2 );

            $wp_query = $template_wp_query;
            return $wp_nav_menu;
        };

        // taxonomy description
        $this->term_description = function () {
            $dummy_data_array = array(
                'term_desc' => 'Sample Taxonomy Description. ( Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. )'
            );

            if ( !$this->has_wp_query() ) {
                return $dummy_data_array;
            }

            if (  tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax() ) {
                if ( empty( $this->term_obj->description ) ) {
                    return $dummy_data_array;
                }
            }

            $data_array = array();

            $data_array['term_desc'] = $this->term_obj->description;

            return $data_array;

        };


        // category acf field
        $this->category_custom_field = function ($atts) {

            $dummy_field_data = array(
                'value' => 'Sample field data',
                'type' => 'text',
            );

            if ( !$this->has_wp_query() ) {
                return $dummy_field_data;
            }

			//print_r($this->term_obj);

            $category_object = self::is_tax() ? $this->term_obj : $this->category_obj;
            $category_id = $category_object->term_id;

            $field_data = array(
                'value' => '',
                'type' => '',
                'meta_exists' => false,
            );

            $field_name = '';
            if( isset( $atts['wp_field'] ) ) {
                $field_name = $atts['wp_field'];
            } else if( isset( $atts['acf_field'] ) ) {
                $field_name = $atts['acf_field'];
            }

            if( $field_name != '' ) {
                $field_data = td_util::get_acf_field_data($field_name, $category_object);

	            //print_r($field_name);
	            //var_dump($field_data);

                if( !$field_data['meta_exists'] ) {
                    if( metadata_exists('term', $category_id, $field_name) ) {
                        $field_data['value'] = get_term_meta($category_id, $field_name, true);
                        $field_data['type'] = 'text';
                        $field_data['meta_exists'] = true;
                    }
                }
            }

            if( empty($field_data['value']) && ( tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax() ) ) {
                return $dummy_field_data;
            }

            return $field_data;
        };


        // category gallery
        $this->category_gallery = function($atts) {

            // Shortcode options
            $source = isset( $atts['source'] ) && $atts['source'] != '' ? $atts['source'] : '';
            $images_size = isset( $atts['images_size'] ) && $atts['images_size'] != '' ? $atts['images_size'] : 'td_1068x0';
            $modal_images_size = isset( $atts['modal_imgs_size'] ) && $atts['modal_imgs_size'] != '' ? $atts['modal_imgs_size'] : 'td_1920x0';


            // Create an array with dummy images
            $dummy_gallery_images = array(
                array(
                    'id' => 1,
                    'alt' => '',
                    'title' => 'Sample gallery image 1',
                    'url' => TDB_URL . '/assets/images/td_meta_replacement.png',
                    'url_modal' => TDB_URL . '/assets/images/td_meta_replacement.png',
                    'caption' => 'Sample caption'
                ),
                array(
                    'id' => 2,
                    'alt' => '',
                    'title' => 'Sample gallery image 2',
                    'url' => TDB_URL . '/assets/images/td_meta_replacement.png',
                    'url_modal' => TDB_URL . '/assets/images/td_meta_replacement.png',
                    'caption' => 'Sample caption'
                ),
                array(
                    'id' => 3,
                    'alt' => '',
                    'title' => 'Sample gallery image 3',
                    'url' => TDB_URL . '/assets/images/td_meta_replacement.png',
                    'url_modal' => TDB_URL . '/assets/images/td_meta_replacement.png',
                    'caption' => 'Sample caption'
                ),
                array(
                    'id' => 4,
                    'alt' => '',
                    'title' => 'Sample gallery image 4',
                    'url' => TDB_URL . '/assets/images/td_meta_replacement.png',
                    'url_modal' => TDB_URL . '/assets/images/td_meta_replacement.png',
                    'caption' => 'Sample caption'
                ),
                array(
                    'id' => 5,
                    'alt' => '',
                    'title' => 'Sample gallery image 5',
                    'url' => TDB_URL . '/assets/images/td_meta_replacement.png',
                    'url_modal' => TDB_URL . '/assets/images/td_meta_replacement.png',
                    'caption' => 'Sample caption'
                ),
                array(
                    'id' => 6,
                    'alt' => '',
                    'title' => 'Sample gallery image 6',
                    'url' => TDB_URL . '/assets/images/td_meta_replacement.png',
                    'url_modal' => TDB_URL . '/assets/images/td_meta_replacement.png',
                    'caption' => 'Sample caption'
                )
            );


            // Return the dummy gallery images if we have no query
            if ( !$this->has_wp_query() ) {
                return $dummy_gallery_images;
            }


            // Initiate an array for the real images
            $gallery_images = array();
            $gallery_images_ids = array();


            // Retrieve the list of gallery images IDs
            if( $source == 'acf_field' ) {
                
				$category_object = self::is_tax() ? $this->term_obj : $this->category_obj;
				$category_id = $category_object->term_id;

				$field_name = isset( $atts['acf_field'] ) && $atts['acf_field'] != '' ? $atts['acf_field'] : '';

				if( $field_name != '' ) {
					$field_data = td_util::get_acf_field_data( $field_name, $category_object );

					if( $field_data['meta_exists'] ) {
						foreach( $field_data['value'] as $image ) {
							if( is_array( $image ) ) {
								$gallery_images_ids[] = $image['ID'];
							} else if( is_numeric( $image ) ) {
								$gallery_images_ids[] = $image;
							} else if( is_string( $image ) ) {
								$img_id = attachment_url_to_postid($image);

								if( $img_id ) {
									$gallery_images_ids[] = $img_id;
								}
							}
						}
					} else {
						if( metadata_exists('term', $category_id, $field_name ) ) {
							$gallery_images_ids = get_term_meta( $category_id, $field_name, true );
						}
					}
				}

            }


            // Get the info for the gallery images
            if( !empty( $gallery_images_ids ) ) {
                foreach( $gallery_images_ids as $gallery_image_id ) {
                    $img_info = get_post( $gallery_image_id );

                    if( $img_info ) {
                        $gallery_image = array(
                            'id' => $img_info->ID,
                            'alt' => get_post_meta($gallery_image_id, '_wp_attachment_image_alt', true),
                            'title' => $img_info->post_title
                        );

                        // Get the image URL
                        if( td_util::get_option('tds_thumb_' . $images_size ) != 'yes' ) {
                            // The thumb size is disabled, so show a placeholder thumb
                            $thumb_disabled_path = td_global::$get_template_directory_uri;
                            if ( strpos( $images_size, 'td_' ) === 0 ) {
                                $thumb_disabled_path = td_api_thumb::get_key( $images_size, 'no_image_path' );
                            }

                            $gallery_image['url'] = $thumb_disabled_path . '/images/thumb-disabled/' . $images_size . '.png';
                        } else {
                            // The thumbnail size is enabled in the panel, try to get the image
                            $image_info = td_util::attachment_get_full_info( $gallery_image_id, $images_size );

                            $gallery_image['url'] = $image_info['src'];
                        }

                        // Get the modal image URL
                        if( td_util::get_option('tds_thumb_' . $modal_images_size ) != 'yes' ) {
                            // The thumb size is disabled, so show a placeholder thumb
                            $thumb_disabled_path = td_global::$get_template_directory_uri;
                            if ( strpos( $images_size, 'td_' ) === 0 ) {
                                $thumb_disabled_path = td_api_thumb::get_key( $images_size, 'no_image_path' );
                            }

                            $gallery_image['url_modal'] = $thumb_disabled_path . '/images/thumb-disabled/' . $modal_images_size . '.png';
                        } else {
                            // The thumbnail size is enabled in the panel, try to get the image
                            $image_info = td_util::attachment_get_full_info( $gallery_image_id, $modal_images_size );

                            $gallery_image['url_modal'] = $image_info['src'];
                        }

                        $gallery_images[] = $gallery_image;
                    }
                }
            }


            // If no gallery images were found and we are in composer, return
            // the array with dummy images
            if( empty( $gallery_images ) && ( tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax() ) ) {
                return $dummy_gallery_images;
            }


            return $gallery_images;

        };


        // cpt data
        $this->cpt = function () {
            return $this->post_type_obj;
        };


        // cpt description
        $this->cpt_description = function () {

            $dummy_data_array = array(
                'cpt_desc' => 'Sample Custom Post Type Description. ( Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. )'
            );

            if ( !$this->has_wp_query() ) {
                return $dummy_data_array;
            }

            if (  tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax() ) {
                if ( empty( $this->post_type_obj->description ) ) {
                    return $dummy_data_array;
                }
            }

            $data_array = array();

            $data_array['cpt_desc'] = $this->post_type_obj->description;

            return $data_array;

        };


        // cpt archive page title
        $this->cpt_archive_title = function () {

            $dummy_data_array = array(
                'title' => 'Sample Custom Post Type Archive Page Title',
                'page_number' => '1',
                'class' => 'tdb-tag-title'
            );

            if ( !$this->has_wp_query() ) {
                return $dummy_data_array;
            }

            if ( !empty($this->post_type_obj) && $this->post_type_obj instanceof WP_Post_Type ) {
                $page_number = intval( $this->get_wp_query()->get('paged') );
                return array(
                    'title' => $this->post_type_obj->label,
                    'page_number' => $page_number ?: 1,
                    'class' => 'tdb-tag-title'
                );
            } else {
                return $dummy_data_array;
            }

        };


        // cpt archive breadcrumbs
        $this->cpt_archive_breadcrumbs = function ( $atts ) {

            $dummy_data_array = array(
                array(
                    'title_attribute' => 'Sample Custom Post Type Archive Title',
                    'url' => '',
                    'display_name' => 'Sample Custom Post Type Archive'
                )
            );

            if ( !$this->has_wp_query() ) {
                return $dummy_data_array;
            }

            $breadcrumbs_array = array();

            if ( !empty($this->post_type_obj) && $this->post_type_obj instanceof WP_Post_Type ) {
                $breadcrumbs_array[] = array(
                    'title_attribute' => $this->post_type_obj->label . ' Archive Title',
                    'url' => '',
                    'display_name' => $this->post_type_obj->label . ' Archive'
                );
            } else {
                return $dummy_data_array;
            }

            return $breadcrumbs_array;

        };


        // cpt archive loop
        $this->cpt_archive_loop = function ( $atts ) {

            $svg_list = td_global::$svg_theme_font_list;

            // previous text icon
            $prev_icon_html = '<i class="page-nav-icon td-icon-menu-left"></i>';
            if( isset( $atts['prev_tdicon'] ) ) {
                $prev_icon = $atts['prev_tdicon'];
                $prev_icon_data = '';
                if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
                    $prev_icon_data = 'data-td-svg-icon="' . $prev_icon . '"';
                }

                if( array_key_exists( $prev_icon, $svg_list ) ) {
                    $prev_icon_html = '<div class="page-nav-icon page-nav-icon-svg" ' . $prev_icon_data . '>' . base64_decode( $svg_list[$prev_icon] ) . '</div>';
                } else {
                    $prev_icon_html = '<i class="page-nav-icon ' . $prev_icon . '"></i>';
                }
            }
            // next text icon
            $next_icon_html = '<i class="page-nav-icon td-icon-menu-right"></i>';
            if( isset( $atts['next_tdicon'] ) ) {
                $next_icon = $atts['next_tdicon'];
                $next_icon_data = '';
                if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
                    $next_icon_data = 'data-td-svg-icon="' . $next_icon . '"';
                }

                if( array_key_exists( $next_icon, $svg_list ) ) {
                    $next_icon_html = '<div class="page-nav-icon page-nav-icon-svg" ' . $next_icon_data . '>' . base64_decode( $svg_list[$next_icon] ) . '</div>';
                } else {
                    $next_icon_html = '<i class="page-nav-icon ' . $next_icon . '"></i>';
                }
            }

            // pagination options
            $pagenavi_options = array(
                'pages_text'    => __td( 'Page %CURRENT_PAGE% of %TOTAL_PAGES%', TD_THEME_NAME ),
                'current_text'  => '%PAGE_NUMBER%',
                'page_text'     => '%PAGE_NUMBER%',
                'first_text'    => __td( '1' ),
                'last_text'     => __td( '%TOTAL_PAGES%' ),
                'next_text'     => $next_icon_html,
                'prev_text'     => $prev_icon_html,
                'dotright_text' => __td( '...' ),
                'dotleft_text'  => __td( '...' ),
                'num_pages'     => 3,
                'always_show'   => true
            );

            // pagination defaults
            $pagination_defaults = array(
                'pagenavi_options' => $pagenavi_options,
                'paged' => 1,
                'max_page' => 3,
                'start_page' => 1,
                'end_page' => 3,
                'pages_to_show' => 3,
                'previous_posts_link' => '<a href="#">' . $prev_icon_html . '</a>',
                'next_posts_link' => '<a href="#">' . $next_icon_html . '</a>'
            );

            // posts limit - by default get the global wp loop posts limit setting
            $limit = get_option( 'posts_per_page' );
            if ( isset( $atts['limit'] ) ) {
                $limit = $atts['limit'];
            }

            // posts offset
            $offset = 0;
            if ( isset( $atts['offset'] ) ) {
                $offset = $atts['offset'];
            }

            $dummy_data_array = array(
                'loop_posts' => array(),
                'limit'      => $limit,
                'offset'     => $offset
            );

            for ( $i = $offset; $i < $limit + $offset; $i++ ) {
                $dummy_data_array['loop_posts'][$i] = array(
                    'post_id' => '-' . $i, // negative post_id to avoid conflict with existent posts
                    'post_type' => 'sample',
                    'post_link' => '#',
                    'post_title' => 'Sample post title ' . $i,
                    'post_title_attribute' => esc_attr( 'Sample post title ' . $i ),
                    'post_excerpt' => 'Sample post no ' . $i .  ' excerpt.',
                    'post_content' => 'Sample post no ' . $i .  ' content.',
                    'post_date_unix' =>  get_the_time( 'U' ),
                    'post_date' => date( get_option( 'date_format' ), time() ),
                    'post_modified' => date( get_option( 'date_format' ), time() ),
                    'post_author_url' => '#',
                    'post_author_name' => 'Author name',
                    'post_author_email' => get_the_author_meta( 'email', 1 ),
                    'post_comments_no' => '11',
                    'post_comments_link' => '#',
                    'post_theme_settings' => array(
                        'td_primary_cat' => '1'
                    ),
                );
            }

            $dummy_data_array['loop_pagination'] = $pagination_defaults;
            $dummy_data_array['tag_slug'] = '';
            $dummy_data_array['term_id'] = '';

            if ( !$this->has_wp_query() ) {
                return $dummy_data_array;
            }

            if ( !$this->has_wp_query() ) {
                return $dummy_data_array;
            }

            $data_array = array();
            $data_array['limit'] = $limit;

            $state_wp_query = $this->get_wp_query();

            foreach ( $state_wp_query->posts as $post ) {

                $data_array['loop_posts'][$post->ID] = array(
                    'post_id' => $post->ID,
                    'post_type' => get_post_type( $post->ID ),
                    'has_post_thumbnail' => has_post_thumbnail( $post->ID ),
                    'post_thumbnail_id' => get_post_thumbnail_id( $post->ID ),
                    'post_link' => esc_url( get_permalink( $post->ID ) ),
                    'post_title' => get_the_title( $post->ID ),
                    'post_title_attribute' => esc_attr( strip_tags( get_the_title( $post->ID ) ) ),
                    'post_excerpt' => $post->post_excerpt,
                    'post_content' => $post->post_content,
                    'post_date_unix' =>  get_the_time( 'U', $post->ID ),
                    'post_date' => get_the_time( get_option( 'date_format' ), $post->ID ),
                    'post_modified' => get_the_modified_date(get_option( 'date_format' ), $post->ID),
                    'post_author_url' => get_author_posts_url( $post->post_author ),
                    'post_author_name' => get_the_author_meta( 'display_name', $post->post_author ),
                    'post_author_email' => get_the_author_meta( 'email', $post->post_author ),
                    'post_comments_no' => get_comments_number( $post->ID ),
                    'post_comments_link' => get_comments_link( $post->ID ),
                    'post_theme_settings' => td_util::get_post_meta_array( $post->ID, 'td_post_theme_settings' ),
                );

            }

            $data_array['loop_pagination'] = $pagination_defaults;

            $paged = intval( $state_wp_query->query_vars['paged'] );

            if ( $paged === 0 ) {
                $paged = 1;
            }

            $max_page = $state_wp_query->max_num_pages;

            $pages_to_show         = intval( $pagenavi_options['num_pages'] );
            $pages_to_show_minus_1 = $pages_to_show - 1;
            $half_page_start       = floor($pages_to_show_minus_1/2 );
            $half_page_end         = ceil($pages_to_show_minus_1/2 );
            $start_page            = $paged - $half_page_start;

            if( $start_page <= 0 ) {
                $start_page = 1;
            }

            $end_page = $paged + $half_page_end;
            if( ( $end_page - $start_page ) != $pages_to_show_minus_1 ) {
                $end_page = $start_page + $pages_to_show_minus_1;
            }

            if( $end_page > $max_page ) {
                $start_page = $max_page - $pages_to_show_minus_1;
                $end_page = $max_page;
            }

            if( $start_page <= 0 ) {
                $start_page = 1;
            }

            $data_array['loop_pagination']['paged'] = $paged;
            $data_array['loop_pagination']['max_page'] = $max_page;
            $data_array['loop_pagination']['start_page'] = $start_page;
            $data_array['loop_pagination']['end_page'] = $end_page;
            $data_array['loop_pagination']['pages_to_show'] = $pages_to_show;

            global $wp_query, $tdb_state_category, $paged;
            $template_wp_query = $wp_query;

            $wp_query = $tdb_state_category->get_wp_query();
            $paged = intval( $state_wp_query->query_vars['paged'] );

            $data_array['loop_pagination']['previous_posts_link'] = get_previous_posts_link( $pagenavi_options['prev_text'] );
            $data_array['loop_pagination']['next_posts_link'] = get_next_posts_link( $pagenavi_options['next_text'], $max_page );

            $wp_query = $template_wp_query;

            $data_array['post_type_obj'] = $this->post_type_obj;
            $data_array['post_type'] = $this->post_type_obj->name;

            return $data_array;

        };


        parent::lock_state_definition();

    }

}
