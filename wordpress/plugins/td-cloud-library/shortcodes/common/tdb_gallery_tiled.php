<?php
class tdb_gallery_tiled extends td_block {

    static $style_selector = '';
    static $style_atts_prefix = '';
    static $style_atts_uid = '';
    static $module_template_part_index = '';
    private $gallery_uid = '';



    /**
     * Disable loop block features. This block does not use a loop and it doesn't need to run a query.
     */
    function __construct() {

        /* --
        -- Check to see if the element is being called into a tdb module template
        -- */
        if( td_global::get_in_tdb_module_template() ) {

            global $tdb_module_template_params;


            /* -- Set the current module template part index, used for ensuring -- */
            /* -- uniqueness between template parts of the same type -- */
            if( isset( $tdb_module_template_params['shortcodes'][get_class($this)] ) ) {
                $tdb_module_template_params['shortcodes'][get_class($this)]++;
            } else {
                $tdb_module_template_params['shortcodes'][get_class($this)] = 0;
            }

            self::$module_template_part_index = $tdb_module_template_params['shortcodes'][get_class($this)];

            // In composer, add an extra random string to ensure uniqueness
            if( tdc_state::is_live_editor_ajax() || tdc_state::is_live_editor_iframe() || is_admin() ) {
                $uniquid = uniqid();
                $newuniquid = '';
                while ( strlen( $newuniquid ) < 3 ) {
                    $newuniquid .= $uniquid[rand(0, 12)];
                }

                self::$module_template_part_index .= '_' . $newuniquid;
            }


            /* -- Set the template part unique style vars -- */
            // Set the style atts prefix
            self::$style_atts_prefix = 'tdb_mts_';

            // Set the style atts uid
            self::$style_atts_uid = $tdb_module_template_params['template_class'] . '_' . get_class($this) . '_' . self::$module_template_part_index;

        } else {

            // reset static properties
            self::$style_selector = '';
            self::$style_atts_prefix = '';
            self::$style_atts_uid = '';
            self::$module_template_part_index = '';

        }

        parent::disable_loop_block_features();

    }


    public function get_custom_css() {

        $style_atts_prefix = self::$style_atts_prefix;
        $style_atts_uid = self::$style_atts_uid;


        /* -- Set the style selector -- */
        $style_selector = '';

        $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();
        $in_element = td_global::get_in_element();
        if( $in_element && $in_composer ) {
            $style_selector .= 'tdc-row-composer .tdc-column-composer .';
        } else if( $in_element || $in_composer ) {
            $style_selector .= 'tdc-row .tdc-column .';
        }

        // Check to see if the element is being called into a tdb module template
        if( td_global::get_in_tdb_module_template() ) {
            global $tdb_module_template_params;

            $style_selector = $tdb_module_template_params['template_class'] . ' .' . $style_selector .  get_class($this) . '_' . self::$module_template_part_index;
        } else {
            $style_selector .= $this->block_uid;
        }


        $compiled_css = '';

        $raw_css =
            "<style>

                /* @style_general_tdb_gallery_tiled */
                .tdb_gallery_tiled {
                    transform: translateZ(0);
                }
                .tdb_gallery_tiled .tdb-gallery-wrap {
                    position: relative;
                    overflow: visible;
                    height: auto;
                    opacity: 1;
                    display: grid;
                    gap: 4px;
                }
                .tdb_gallery_tiled .tdb-gi-inner {
                    position: relative;
                    display: block;
                    overflow: hidden;
                }
                .tdb_gallery_tiled .tdb-gi-inner:after {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    pointer-events: none; 
                }
                .tdb_gallery_tiled img {
                    position: absolute;
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }
                .tdb_gallery_tiled .tdb-gi-caption {
                    position: absolute;
                    left: 0;
                    bottom: 0;
                    width: auto;
                    max-width: 100%;
                    padding: 6px 10px;
                    background-color: rgba(0, 0, 0, .7);
                    font-size: 11px;
                    line-height: 1.3;
                    color: #fff;
                    z-index: 10;
                }
                
                
                /* @style_general_tdb_gallery_tiled_composer */
                .tdb_gallery_tiled .tdb-block-inner {
                    pointer-events: none;
                }

                /* @" . $style_atts_prefix . "images_on_row$style_atts_uid */
				body .$style_selector .tdb-gallery-wrap {
					grid-template-columns: @" . $style_atts_prefix . "images_on_row$style_atts_uid;
				}
               
                /* @" . $style_atts_prefix . "gap$style_atts_uid */
                body .$style_selector .tdb-gallery-wrap {
                    gap: @" . $style_atts_prefix . "gap$style_atts_uid;
                }


                /* @" . $style_atts_prefix . "height$style_atts_uid */
                body .$style_selector .tdb-gi-inner {
                    padding-bottom: @" . $style_atts_prefix . "height$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "show_caption$style_atts_uid */
                body .$style_selector .tdb-gi-caption {
                    display: @" . $style_atts_prefix . "show_caption$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "caption_pos_top$style_atts_uid */
                body .$style_selector .tdb-gi-caption {
                    top: 0;
                    bottom: auto;
                }
                /* @" . $style_atts_prefix . "caption_pos_bottom$style_atts_uid */
                body .$style_selector .tdb-gi-caption {
                    top: auto;
                    bottom: 0;
                }
                /* @" . $style_atts_prefix . "caption_width$style_atts_uid */
                body .$style_selector .tdb-gi-caption {
                    width: @" . $style_atts_prefix . "caption_width$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "caption_padd$style_atts_uid */
                body .$style_selector .tdb-gi-caption {
                    padding: @" . $style_atts_prefix . "caption_padd$style_atts_uid;
                }

                /* @" . $style_atts_prefix . "caption_bg$style_atts_uid */
                body .$style_selector .tdb-gi-caption {
                    background-color: @" . $style_atts_prefix . "caption_bg$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "caption_color$style_atts_uid */
                body .$style_selector .tdb-gi-caption {
                    color: @" . $style_atts_prefix . "caption_color$style_atts_uid;
                }
                
                /* @" . $style_atts_prefix . "f_caption$style_atts_uid */
                body .$style_selector .tdb-gi-caption {
                    @" . $style_atts_prefix . "f_caption$style_atts_uid
                }
            </style>";


        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts() );

        $compiled_css .= $td_css_res_compiler->compile_css();
        return $compiled_css;

    }

    static function cssMedia( $res_ctx ) {


        $style_atts_prefix = self::$style_atts_prefix;
        $style_atts_uid = self::$style_atts_uid;

        /*-- GENERAL STYLES -- */
        $res_ctx->load_settings_raw( 'style_general_tdb_gallery_tiled', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_gallery_tiled_composer', 1 );
        }

        /*-- GENERAL -- */
        /* -- Layout -- */
        $gap = $res_ctx->get_shortcode_att( 'gap' );
        $gap = is_numeric( $gap ) ? ($gap) . 'px' : $gap;
        $res_ctx->load_settings_raw( $style_atts_prefix . 'gap' . $style_atts_uid, $gap );


        // modules per row
        $images_on_row = $res_ctx->get_shortcode_att('images_on_row');
        $res_ctx->load_settings_raw( $style_atts_prefix . 'images_on_row' . $style_atts_uid, $images_on_row );


        /* -- Layout -- */

        // Height ------------------------------------------
        $height = $res_ctx->get_shortcode_att( 'height' );
        $height = $height != '' ? $height : '50%';
        $height .= is_numeric( $height ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'height' . $style_atts_uid, $height );


        // Show caption
        $show_caption = $res_ctx->get_shortcode_att( 'show_caption' );
        $show_caption = $show_caption != '' ? $show_caption : 'block';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'show_caption' . $style_atts_uid, $show_caption );

        // Caption position
        $caption_pos = $res_ctx->get_shortcode_att( 'caption_pos' );
        $caption_pos = $caption_pos != '' ? $caption_pos : 'bottom';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'caption_pos_' . $caption_pos . $style_atts_uid, 1 );

        // Caption width
        $caption_width = $res_ctx->get_shortcode_att( 'caption_width' );
        $caption_width .= is_numeric( $caption_width ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'caption_width' . $style_atts_uid, $caption_width );

        // Caption padding
        $caption_padd = $res_ctx->get_shortcode_att( 'caption_padd' );
        $caption_padd .= is_numeric( $caption_padd ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'caption_padd' . $style_atts_uid, $caption_padd );

        /* -- Style -- */
        $res_ctx->load_settings_raw( $style_atts_prefix . 'caption_bg' . $style_atts_uid, $res_ctx->get_shortcode_att( 'caption_bg' ) );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'caption_color' . $style_atts_uid, $res_ctx->get_shortcode_att( 'caption_color' ) );

        /* -- Fonts -- */
        $res_ctx->load_font_settings( 'f_caption', '', $style_atts_prefix, $style_atts_uid );
    }


    function render( $atts, $content = null ) {

        parent::render($atts); // sets the live atts, $this->atts, $this->block_uid, $this->td_query (it runs the query)

        $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();


        $images_on_row = $this->get_att('images_on_row');


        // Template type
        $template_type = tdb_state_template::get_template_type();


        // Gallery source
        $source = $this->get_att('source');


        // ACF field
        $acf_field = $this->get_att('acf_field');


        // Images size
        $images_size = $this->get_att('images_size');
        $images_size = $images_size != '' ? $images_size : 'td_1068x0';


        // Modal images
        $modal_images = $this->get_att('modal_imgs') != '';
        $modal_images_size = $this->get_att('modal_imgs_size');
        $modal_images_size = $modal_images_size != '' ? $modal_images_size : 'td_1920x0';



        /* --
        -- RETRIEVE THE GALLERY IMAGES
        -- */
        /* -- Retrieve the gallery images -- */
        $gallery_images = array();
        $block_error = '';

        if( $source == '' ) {

            $block_error = td_util::get_block_error(
                'Gallery',
                'Please select a source for the gallery.');

        } else {

            global $tdb_module_template_params;

            if( $tdb_module_template_params !== NULL ) {
                $post_obj = $tdb_module_template_params['post_obj'];

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

                if ( gettype($post_obj) === 'object' && get_class($post_obj) === 'WP_Post' ) {
                    $post_id = $post_obj->ID;
                    $gallery_images_ids = array();

                    switch( $source ) {
                        case 'post_gallery':
                            $post_theme_settings_meta = td_util::get_post_meta_array( $post_id, 'td_post_theme_settings' );
                            $post_gallery_imgs_ids_meta = isset( $post_theme_settings_meta['td_gallery_imgs'] ) ? $post_theme_settings_meta['td_gallery_imgs'] : '';

                            if( !empty( $post_gallery_imgs_ids_meta ) ) {
                                $gallery_images_ids = explode(',', $post_gallery_imgs_ids_meta);
                            }

                            break;

                        case 'acf_field':
                            if( !class_exists( 'ACF' ) ) {
                                $block_error = td_util::get_block_error(
                                    'Gallery',
                                    'The Advanced Custom Fields (ACF) plugin is disabled.');
                            } else if( empty( $acf_field ) ) {
                                $block_error = td_util::get_block_error(
                                    'Gallery',
                                    'Please select an ACF field first.');
                            } else {
                                $field_data = td_util::get_acf_field_data( $acf_field, $post_id );

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
                                    if( metadata_exists('post', $post_id, $acf_field ) ) {
                                        $gallery_images_ids = get_post_meta( $post_id, $acf_field, true );
                                    }
                                }
                            }

                            break;
                    }

                    if( !empty( $gallery_images_ids ) ) {
                        foreach( $gallery_images_ids as $gallery_image_id ) {
                            if( empty( $gallery_image_id ) ) {
                                continue;
                            }

                            $img_info = get_post( $gallery_image_id );

                            if( $img_info ) {
                                $gallery_image = array(
                                    'id' => $img_info->ID,
                                    'alt' => get_post_meta($gallery_image_id, '_wp_attachment_image_alt', true),
                                    'title' => $img_info->post_title,
                                    'caption' => $img_info->post_excerpt,
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

                    if( empty( $gallery_images ) && ( tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax() ) ) {
                        // If we are in composer, display dummy data only if we
                        // are editing the actual module
                        if( tdb_state_template::get_template_type() == 'module' ) {
                            $gallery_images = $dummy_gallery_images;
                        }
                    }
                } else {
                    $gallery_image = $dummy_gallery_images;
                }
            } else {
                switch ( $source ) {
                    case 'post_gallery':
                        // Throw an error if the shortcode is not in a post/cpt template
                        if( $template_type != 'single' && $template_type != 'cpt' ) {
                            $block_error = td_util::get_block_error(
                                'Gallery',
                                '\'Post gallery\' was selected as the source, but the current template is not a post or CPT.');
                        } else {
                            global $tdb_state_single;
                            $gallery_images = $tdb_state_single->post_gallery->__invoke( $atts );
                        }

                        break;

                    case 'acf_field':
                        // Throw an error if the ACF plugin is disabled
                        if( !class_exists( 'ACF' ) ) {
                            $block_error = td_util::get_block_error(
                                'Gallery',
                                'The Advanced Custom Fields (ACF) plugin is disabled.');
                        } else if( empty( $acf_field ) ) {
                            $block_error = td_util::get_block_error(
                                'Gallery',
                                'Please select an ACF field first.');
                        } else {

                            global $tdb_state_single, $tdb_state_category, $tdb_state_tag, $tdb_state_author, $tdb_state_attachment, $tdb_state_single_page;

                            switch( $template_type ) {
                                case 'cpt':
                                case 'single':
                                    $gallery_images = $tdb_state_single->post_gallery->__invoke( $atts );
                                    break;
                                case 'category':
                                    $gallery_images = $tdb_state_category->category_gallery->__invoke( $atts );
                                    break;
                                case 'cpt_tax':

                                    if ( $tdb_state_category->is_cpt_post_type_archive() ) {

                                        $buffy = '<div class="' . $this->get_block_classes() . '" ' . $this->get_block_html_atts() . '>';
                                        $buffy .= '<div class="tdb-block-inner td-fix-index">';
                                        $buffy .= td_util::get_block_error(
                                            'Gallery',
                                            'This shortcode is not supported by this template.'
                                        );
                                        $buffy .= '</div>';
                                        $buffy .= '</div>';

                                        return $buffy;

                                    } else {
                                        $tdb_state_category->set_tax();
                                        $gallery_images = $tdb_state_category->category_gallery->__invoke( $atts );
                                    }

                                    break;
                                case 'tag':
                                    $gallery_images = $tdb_state_tag->tag_gallery->__invoke( $atts );
                                    break;
                                case 'author':
                                    $gallery_images = $tdb_state_author->author_gallery->__invoke( $atts );
                                    break;
                                case 'attachment':
                                    $gallery_images = $tdb_state_attachment->attachment_gallery->__invoke( $atts );
                                    break;
                                default:
                                    $gallery_images = $tdb_state_single_page->page_gallery->__invoke( $atts );
                                    break;
                            }

                        }

                        break;
                }
            }

        }



        /* --
        -- RENDER THE SHORTCODE
        -- */
        /* -- Additional classes -- */
        $additional_classes_array = array();
        $this->gallery_uid = $this->block_uid;

        // Check to see if the element is being called into a tdb module template
        if( td_global::get_in_tdb_module_template() ) {
            $additional_classes_array[] = get_class($this) . '_' . self::$module_template_part_index;
            $this->gallery_uid = get_class($this) . '_' . self::$module_template_part_index;
        }


        $buffy = '';

        if( empty( $gallery_images ) && empty( $block_error ) ) {
            return $buffy;
        }

        $buffy .= '<div class="' . $this->get_block_classes($additional_classes_array) . '" ' . $this->get_block_html_atts() . '>';

        //get the block css
        $buffy .= $this->get_block_css();

        //get the js for this block
        $buffy .= $this->get_block_js();


        $buffy .= '<div class="tdb-block-inner td-fix-index">';

        if( !empty( $block_error ) ) {
            $buffy .= $block_error;
        } else if( !empty( $gallery_images ) ) {
            $buffy .= '<div class="tdb-gallery-wrap">';

            $limit_images = $this->get_att('limit');
            if( $limit_images != '' ) {
                $gallery_images = array_slice($gallery_images, 0, $limit_images);
            }

            foreach( $gallery_images as $gallery_image ) {
                $buffy .= '<div class="tdb-gallery-image">';
                $buffy .= '<' . ( $modal_images ? 'a href="' . $gallery_image['url_modal'] . '"' : 'div' ) . ' class="tdb-gi-inner" ' . ( $modal_images && !empty( $gallery_image['caption'] ) ? 'data-caption="' . $gallery_image['caption'] . '"' : '' ) . '>';
                $buffy .= '<img src="' . $gallery_image['url'] . '"' .
                    ( !empty( $gallery_image['alt'] ) ? ' alt="' . $gallery_image['alt'] . '"' : '' ) .
                    ( !empty( $gallery_image['title'] ) ? ' title="' . $gallery_image['title'] . '"' : '' ) .
                    ( $modal_images ? ' class="td-modal-image"' : '' ) .
                    ' />';

                if( !empty( $gallery_image['caption'] ) ) {
                    $buffy .= '<figcaption class="tdb-gi-caption">' . $gallery_image['caption'] . '</figcaption>';
                }
                $buffy .= '</' . ( $modal_images ? 'a' : 'div' ) . '>';
                $buffy .= '</div>';
            }
            $buffy .= '</div>';
        }

        $buffy .= '</div>';

        $buffy .= '</div>';

        return $buffy;

    }
}
