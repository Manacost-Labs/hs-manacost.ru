<?php


/**
 * Class tdb_state_attachment
 * @property tdb_method title
 * @property tdb_method attachment_breadcrumbs
 * @property tdb_method attachment_image
 * @property tdb_method attachment_description
 * @property tdb_method attachment_date
 * @property tdb_method attachment_image_links
 * @property tdb_method attachment_pag_prev
 * @property tdb_method attachment_pag_next
 * @property tdb_method attachment_custom_field
 * @property tdb_method attachment_gallery
 *
 */
class tdb_state_attachment extends tdb_state_base {

    private $attachment_wp_query = '';

    /**
     * @param WP_Query $wp_query
     */
    function set_wp_query( $wp_query ) {

        parent::set_wp_query( $wp_query );
        $this->attachment_wp_query = $this->get_wp_query();
    }



    public function __construct() {

        // attachment page title
        $this->title = function ($atts) {

            $dummy_data_array = array(
                'title' => 'Attachment Page Sample Title',
                'class' => 'tdb-attachment-title'
            );

            if ( !$this->has_wp_query() ) {
                return $dummy_data_array;
            }

            $title = get_the_title( $this->attachment_wp_query->post->ID );

            $data_array = array(
                'title' => $title,
                'class' => 'tdb-attachment-title'
            );

            if( $title == '' ) {
                if ( tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax() ) {
                    return $dummy_data_array;
                }
            }

            return $data_array;
        };

        // attachment page breadcrumbs
        $this->attachment_breadcrumbs = function ($atts) {

            $dummy_data_array = array();
            $show_parent_post = ( $atts['show_parent_post'] != '' ) ? true : false;

            $a_custom_title = ( $atts['attachment_custom_title'] != '' ) ? $atts['attachment_custom_title'] : 'Attachment Sample Title';
            $a_custom_title_att = ( $atts['attachment_custom_title_att'] != '' ) ? $atts['attachment_custom_title_att'] : '';
            $a_custom_link = ( $atts['attachment_custom_link'] != '' ) ? $atts['attachment_custom_link'] : '';

            if ( $show_parent_post ) {
                $dummy_data_array[] = array(
                    'title_attribute' => '',
                    'url' => '',
                    'display_name' => 'Parent Post Sample Title'
                );
            }

            $dummy_data_array[] = array(
                'title_attribute' => $a_custom_title_att,
                'url' => $a_custom_link,
                'display_name' => $a_custom_title
            );

            if ( !$this->has_wp_query() ) {
                return $dummy_data_array;
            }

            $parent_post_id = $this->attachment_wp_query->post->post_parent;

            $attachment_custom_title = ( $atts['attachment_custom_title'] != '' ) ? $atts['attachment_custom_title'] : get_the_title( $this->attachment_wp_query->post->ID );
            $attachment_custom_link = ( $atts['attachment_custom_link'] != '' ) ? $atts['attachment_custom_link'] : '';
            $attachment_custom_title_att = ( $atts['attachment_custom_title_att'] != '' ) ? $atts['attachment_custom_title_att'] : get_the_title( $this->attachment_wp_query->post->ID );

            $data_array = array();

            if ( !empty( $parent_post_id ) ) {
                $data_array[] = array(
                    'title_attribute' => get_the_title( $parent_post_id ),
                    'url' => esc_url( get_permalink( $parent_post_id ) ),
                    'display_name' => get_the_title( $parent_post_id )
                );
            }

            $data_array[] = array(
                'title_attribute' => $attachment_custom_title_att,
                'url' => $attachment_custom_link,
                'display_name' => $attachment_custom_title
            );

            return $data_array;

        };

        // attachment page image navigation
        $this->attachment_image_links = function ($atts) {

            $no_thumb_placeholder = TDB_URL . '/assets/images/td_meta_replacement_small.png';

            $dummy_data_array = array(
                'previous_image_link' => '<a href="#"><img width="150" height="150" src="' . $no_thumb_placeholder . '" class="attachment-thumbnail size-thumbnail"></a>',
                'next_image_link'     => '<a href="#"><img width="150" height="150" src="' . $no_thumb_placeholder . '" class="attachment-thumbnail size-thumbnail"></a>'
            );

            if ( !$this->has_wp_query() ) {
                return $dummy_data_array;
            }

	        $data_array = array(
		        'previous_image_link' => '',
		        'next_image_link'     => ''
	        );

            global $wp_query;
            $template_wp_query = $wp_query;
            $wp_query = $this->attachment_wp_query;

            if ( have_posts() ) {

                while ( have_posts() ) {
                    the_post();

                    ob_start();
                    previous_image_link();
                    $data_array['previous_image_link'] = ob_get_clean();

                    ob_start();
                    next_image_link();
                    $data_array['next_image_link'] = ob_get_clean();
                }
            }

            $wp_query = $template_wp_query;

            return $data_array;
        };

        // attachment page image navigation
        $this->attachment_pag_prev = function ($atts) {

            $no_thumb_placeholder = TDB_URL . '/assets/images/td_meta_replacement_small.png';

            $dummy_data_array = array(
                'previous_image_link' => '<a href="#"><img width="150" height="150" src="' . $no_thumb_placeholder . '" class="attachment-thumbnail size-thumbnail"></a>'
            );

            if ( !$this->has_wp_query() ) {
                return $dummy_data_array;
            }

	        $data_array = array(
		        'previous_image_link' => ''
	        );

            global $wp_query;
            $template_wp_query = $wp_query;
            $wp_query = $this->attachment_wp_query;

            if ( have_posts() ) {

                while ( have_posts() ) {
                    the_post();

                    ob_start();
                    previous_image_link();
                    $data_array['previous_image_link'] = ob_get_clean();
                }
            }

            $wp_query = $template_wp_query;

            return $data_array;
        };

        // attachment page image navigation
        $this->attachment_pag_next = function ($atts) {

            $no_thumb_placeholder = TDB_URL . '/assets/images/td_meta_replacement_small.png';

            $dummy_data_array = array(
                'next_image_link' => '<a href="#"><img width="150" height="150" src="' . $no_thumb_placeholder . '" class="attachment-thumbnail size-thumbnail"></a>'
            );

            if ( !$this->has_wp_query() ) {
                return $dummy_data_array;
            }

	        $data_array = array(
		        'next_image_link' => ''
	        );

            global $wp_query;
            $template_wp_query = $wp_query;
            $wp_query = $this->attachment_wp_query;

            if ( have_posts() ) {

                while ( have_posts() ) {
                    the_post();

                    ob_start();
                    next_image_link();
                    $data_array['next_image_link'] = ob_get_clean();
                }
            }

            $wp_query = $template_wp_query;

            return $data_array;
        };

        // attachment image
        $this->attachment_image = function ($atts) {

            $no_thumb_placeholder = TDB_URL . '/assets/images/td_meta_replacement.png';

            $dummy_data_array = array(
                'is_image' => true,
                'att_url' => '#',
                'att_title' => 'attachment img sample title',
                'src' => $no_thumb_placeholder,
                'alt' => 'attachment img sample alt'
            );

            if ( !$this->has_wp_query() ) {
                return $dummy_data_array;
            }

            $is_image = wp_attachment_is_image( $this->attachment_wp_query->post->ID );

            $data_array = array(
                'is_image' => $is_image,
                'att_url' => '',
                'att_title' => '',
                'src' => '',
                'alt' => '',
            );

            if ( $is_image === true ) {

                $att_image = wp_get_attachment_image_src( $this->attachment_wp_query->post->ID, 'full' );

                if ( !empty( $att_image[0] ) ) {
                    $data_array['src'] = $att_image[0];
                }

                $image_data = td_util::get_image_attachment_data( $this->attachment_wp_query->post->post_parent );

                if ( !empty( $image_data->alt )) {
                    $data_array['alt'] = $image_data->alt;
                }

                $data_array['att_url'] = wp_get_attachment_url( $this->attachment_wp_query->post->ID );
                $data_array['att_title'] = get_the_title( $this->attachment_wp_query->post->ID );

            }

            return $data_array;
        };

        // attachment desription
        $this->attachment_description = function ($atts) {

            $dummy_data_array = array(
                'description' => 'Sample attachment description.'
            );

            if ( !$this->has_wp_query() ) {
                return $dummy_data_array;
            }

            $data_array = array(
                'description' =>   $this->attachment_wp_query->post->post_content
            );

            if( $data_array['description'] == '' ) {
                if ( tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax() ) {
                    return $dummy_data_array;
                }
            }

            return $data_array;

        };

        // attachment date
        $this->attachment_date = function () {

            $current_time = current_time( 'timestamp' );

            $dummy_data_array = array(
                'date'            => date( DATE_W3C, time() ),
                'time'            => date( get_option( 'date_format' ), time() ),
                'human_time_diff' => human_time_diff( strtotime(date( DATE_W3C, strtotime("-1 week") ) ), $current_time ),
            );

            if ( !$this->has_wp_query() ) {
                return $dummy_data_array;
            }

            $data_array = array(
                'date' => date( DATE_W3C, get_the_time( 'U', $this->attachment_wp_query->post->ID ) ),
                'time' => get_the_time( get_option( 'date_format' ), $this->attachment_wp_query->post->ID ),
                'human_time_diff' => ''
            );

            $post_time_u  = get_the_time('U', $this->attachment_wp_query->ID );
            $diff = (int) abs( $current_time - $post_time_u );
            if ( $diff < WEEK_IN_SECONDS ) {
                $data_array['human_time_diff'] = human_time_diff( $post_time_u, $current_time );
            }

            return $data_array;

        };

        // attachment custom field
        $this->attachment_custom_field = function ($atts) {
            $dummy_field_data = array(
                'value' => 'Sample field data',
                'type' => 'text',
            );

            if ( !$this->has_wp_query() ) {
                return $dummy_field_data;
            }

            $attachment_object = $this->attachment_wp_query->post;
            $attachment_id = $attachment_object->ID;

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
                $field_data = td_util::get_acf_field_data($field_name, $attachment_object);

                if( !$field_data['meta_exists'] ) {
                    if( metadata_exists('post', $attachment_id, $field_name) ) {
                        $field_data['value'] = get_post_meta($attachment_id, $field_name, true);
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


        // attachment gallery
        $this->attachment_gallery = function($atts) {

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
                
				$attachment_object = $this->attachment_wp_query->post;
                $attachment_id = $attachment_object->ID;

				$field_name = isset( $atts['acf_field'] ) && $atts['acf_field'] != '' ? $atts['acf_field'] : '';

				if( $field_name != '' ) {
					$field_data = td_util::get_acf_field_data( $field_name, $attachment_object );

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
						if( metadata_exists('term', $attachment_id, $field_name ) ) {
							$gallery_images_ids = get_term_meta( $attachment_id, $field_name, true );
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

        parent::lock_state_definition();
    }

}