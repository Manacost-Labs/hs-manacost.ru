<?php


/**
 * Class tdb_state_tag
 * @property tdb_method title
 * @property tdb_method tag_breadcrumbs
 * @property tdb_method loop
 * @property tdb_method tag_description
 * @property tdb_method tag_custom_field
 * @property tdb_method tag_gallery
 * @property tdb_method menu
 * @property tdb_method list_menu
 *
 */
class tdb_state_tag extends tdb_state_base {

    // private $tag_wp_query = '';
    private $tag_obj = '';

    /**
     * @param WP_Query $wp_query
     */
    function set_wp_query( $wp_query ) {

        parent::set_wp_query( $wp_query );

        $tag_wp_query = $this->get_wp_query();
        $this->tag_obj = get_term_by('slug', $tag_wp_query->query['tag'], 'post_tag');


    }



    public function __construct() {

        // tag page posts loop
        $this->loop = function ( $atts ) {

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

            for ( $i = (int)$offset; $i < (int)$limit + (int)$offset; $i++ ) {
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

            global $wp_query, $tdb_state_tag, $paged;
            $template_wp_query = $wp_query;

            $wp_query = $tdb_state_tag->get_wp_query();
            $paged = intval( $state_wp_query->query_vars['paged'] );

            $data_array['loop_pagination']['previous_posts_link'] = get_previous_posts_link( $pagenavi_options['prev_text'] );
            $data_array['loop_pagination']['next_posts_link'] = get_next_posts_link( $pagenavi_options['next_text'], $max_page );

            $wp_query = $template_wp_query;

            $data_array['tag_slug'] = $this->tag_obj->slug;

            return $data_array;
        };

        // tag page title
        $this->title = function ( $atts ) {

            $dummy_data_array = array(
                'title' => 'Sample Tag Page Title',
                'page_number' => '1',
                'class' => 'tdb-tag-title'
            );

            if ( !$this->has_wp_query() ) {
                return $dummy_data_array;
            }

            $page_number = intval( $this->get_wp_query()->query_vars['paged'] );

            $data_array = array(
                'title' => $this->tag_obj->name,
                'page_number' => $page_number ? $page_number : 1,
                'class' => 'tdb-tag-title'
            );

            return $data_array;
        };

        // tag page breadcrumbs
        $this->tag_breadcrumbs = function ( $atts ) {

            $t_custom_title = ( $atts['tag_custom_title'] != '' ) ? $atts['tag_custom_title'] : __td( 'Tags', TD_THEME_NAME );
            $t_custom_link = ( $atts['tag_custom_link'] != '' ) ? $atts['tag_custom_link'] : '';
            $t_custom_title_att = ( $atts['tag_custom_title_att'] != '' ) ? $atts['tag_custom_title_att'] : '';

            $dummy_data_array = array(
                array(
                    'title_attribute' => $t_custom_title_att,
                    'url' => esc_url( $t_custom_link ),
                    'display_name' => $t_custom_title
                ),
                array(
                    'title_attribute' => '',
                    'url' => '',
                    'display_name' => 'Sample Tag'
                )
            );

            if ( !$this->has_wp_query() ) {
                return $dummy_data_array;
            }

            $tag_custom_title = ( $atts['tag_custom_title'] != '' ) ? $atts['tag_custom_title'] : __td( 'Tags', TD_THEME_NAME );
            $tag_custom_link = ( $atts['tag_custom_link'] != '' ) ? $atts['tag_custom_link'] : '';
            $tag_custom_title_att = ( $atts['tag_custom_title_att'] != '' ) ? $atts['tag_custom_title_att'] : '';

            $data_array = array(
                array(
                    'title_attribute' => $tag_custom_title_att,
                    'url' => esc_url( $tag_custom_link ),
                    'display_name' => $tag_custom_title
                ),
                array(
                    'title_attribute' => $this->tag_obj->name,
                    'url' => '',
                    'display_name' => ucfirst( $this->tag_obj->name )
                )
            );
            return $data_array;

        };

        // tag description
        $this->tag_description = function () {
            $dummy_data_array = array(
                'tag_desc' => 'Sample Tag Description. ( Lorem ipsum dolor sit amet, adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. )'
            );

            if ( !$this->has_wp_query() ) {
                return $dummy_data_array;
            }

            if ( tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax() ) {
                if ( empty( $this->tag_obj->description ) ) {
                    return $dummy_data_array;
                }
            }

            $data_array = array();

           // $data_array['tag_desc'] = $this->tag_obj->description;
            $data_array['tag_desc'] = tag_description($this->tag_obj->term_id);

            return $data_array;
        };

        // tag acf field
        $this->tag_custom_field = function ($atts) {
            $dummy_field_data = array(
                'value' => 'Sample field data',
                'type' => 'text',
            );

            if ( !$this->has_wp_query() ) {
                return $dummy_field_data;
            }

            $tag_object = $this->tag_obj;
            $tag_id = $tag_object->term_id;

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
                $field_data = td_util::get_acf_field_data($field_name, $tag_object);

                if( !$field_data['meta_exists'] ) {
                    if( metadata_exists('term', $tag_id, $field_name) ) {
                        $field_data['value'] = get_term_meta($tag_id, $field_name, true);
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


        // tag gallery
        $this->tag_gallery = function($atts) {

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
                
				$tag_object = $this->tag_obj;
                $tag_id = $tag_object->term_id;

				$field_name = isset( $atts['acf_field'] ) && $atts['acf_field'] != '' ? $atts['acf_field'] : '';

				if( $field_name != '' ) {
					$field_data = td_util::get_acf_field_data( $field_name, $tag_object );

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
						if( metadata_exists('term', $tag_id, $field_name ) ) {
							$gallery_images_ids = get_term_meta( $tag_id, $field_name, true );
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

        parent::lock_state_definition();
    }

}