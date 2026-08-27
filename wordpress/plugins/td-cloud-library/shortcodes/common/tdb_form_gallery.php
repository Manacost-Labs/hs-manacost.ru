<?php

/**
 * Class tdb_form_gallery
 */

class tdb_form_gallery extends td_block {

    public function get_custom_css() {
        // $unique_block_class - the unique class that is on the block. use this to target the specific instance via css
        $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();
        $in_element = td_global::get_in_element();
        $unique_block_class_prefix = '';
        if( $in_element || $in_composer ) {
            $unique_block_class_prefix = 'tdc-row';

            if( $in_element && $in_composer ) {
                $unique_block_class_prefix = 'tdc-row-composer';
            }
        }
        $general_block_class = $unique_block_class_prefix ? '.' . $unique_block_class_prefix : '';
        $unique_block_class = ( $unique_block_class_prefix ? $unique_block_class_prefix . ' .' : '' ) . ( $in_composer ? 'tdc-column' . ( $in_element ? '-composer' : '' ) . ' .' : '' ) . $this->block_uid;

        $compiled_css = '';

        $raw_css =
            "<style>

                /* @style_general_tdb_form_gallery */
                .tdb_form_gallery {
                    transform: translateZ(0);
                    margin-bottom: 28px;
                    font-family: -apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Oxygen-Sans,Ubuntu,Cantarell,\"Helvetica Neue\",sans-serif;
                    font-size: 14px;
                }
                body .tdb_form_gallery .tdb-s-form .tdb-s-form-group {
                    display: flex;
                    margin-bottom: 0;
                }
                body .tdb_form_gallery .tdb-s-form .tdb-s-notif {
                    display: none;
                    margin-top: 28px;
                }
                .tdb_form_gallery .tdb-fg-items {
                    display: flex;
                    flex-wrap: wrap;
                    row-gap: 12px;
                    flex: 1;
                    margin-left: -6px;
                    margin-right: -6px;
                }
                .tdb_form_gallery .tdb-fg-item:not([data-file-type='document']):not(.tdb-s-form-file-previewing) {
                    width: 12.5%;
                    padding-left: 6px;
                    padding-right: 6px;
                    padding-bottom: 0;
                }
                .tdb_form_gallery .tdb-fg-item-inner {
                    position: relative;
                    padding-bottom: 100%;
                }
                .tdb_form_gallery .tdb-s-fg-error .tdb-fg-item-add .tdb-fg-item-inner {
                    border-color: #FF0000;
                    outline-color: rgba(255, 0, 0, 0.1);
                }
                .tdb_form_gallery .tdb-fgi-img {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    padding: .286em;
                    background-color: #fff;
                    object-fit: cover;
                    border: 2px solid #D7D8DE;
                    border-radius: 5px;
                    outline: 3px solid transparent;
                    transition: border-color 0.2s ease-in-out, color 0.2s ease-in-out, outline-color 0.2s ease-in-out;
                }
                .tdb_form_gallery .tdb-fgi-del {
                    position: absolute;
                    top: 0;
                    right: 0;
                    transform: translate(25%, -25%);
                    display: none;
                    align-items: center;
                    justify-content: center;
                    background-color: #000;
                    width: 2.2em;
                    height: 2.2em;
                    padding-bottom: 1px;
                    font-size: .786em;
                    font-weight: 700;
                    line-height: 1;
                    color: #fff;
                    border-radius: 100%;
                    cursor: pointer;
                    transition: background-color 0.2s ease-in-out;
                }
                .tdb_form_gallery .tdb-fgi-del:hover {
                    background-color: red;
                }
                .tdb_form_gallery .tdb-fg-item-inner:hover .tdb-fgi-del {
                    display: flex;
                }
                .tdb_form_gallery .tdb-fg-item-add .tdb-s-form-file-box {
                    font-size: .857em;
                }
                .tdb_form_gallery .tdb-fg-item-add .tdb-s-ffu-ico {
                    margin-bottom: .667em;
                    width: 2.5em;
                    height: auto;
                }
                .tdb_form_gallery .tdb-fg-inputs {
                    display: none;
                }
                
                /* @style_general_tdb_form_gallery_composer */
                .tdb_form_gallery .tdb-s-form-file-input {
                    pointer-events: none;
                }
                
                
                
                /* @all_input_display_row */
                body .$unique_block_class .tdb-s-form-group {
                    flex-direction: column;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-label {
                    width: 100%;
                    margin: 0 0 8px;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-label-descr {
                    margin-bottom: 2px;
                }
                
                /* @all_input_display_columns */
                body .$unique_block_class .tdb-s-form-group {
                    flex-direction: row;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-label {
                    width: @all_label_width;
                    margin: 0 24px 0 0;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-label-descr {
                    margin-bottom: 0;
                }
                
                
                /* @columns */
                body .$unique_block_class .tdb-fg-item:not([data-file-type='document']):not(.tdb-s-form-file-previewing) {
                    width: @columns;
                }

                /* @all_input_border */
                body .$unique_block_class .tdb-fgi-img,
                body .$unique_block_class .tdb-s-form-file-box {
                    border: @all_input_border @all_input_border_style @all_input_border_color;
                }
                /* @input_radius */
                body .$unique_block_class .tdb-fgi-img,
                body .$unique_block_class .tdb-s-form-file-box {
                    border-radius: @input_radius;
                }
                
                /* @notif_radius */
                body .$unique_block_class .tdb-s-notif {
                    border-radius: @notif_radius;
                }
                
                /* @remove_btn_radius */
                body .$unique_block_class .tdb-fgi-del {
                    border-radius: @remove_btn_radius;
                }
                
                
                /* @accent_color */
                body .$unique_block_class .tdb-s-form .tdb-s-form-file:hover .tdb-s-form-file-box,
                body .$unique_block_class .tdb-s-form .tdb-s-form-file.tdb-s-form-file-dragover .tdb-s-form-file-box {
                    color: @accent_color;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-file:hover .tdb-s-ffu-ico,
                body .$unique_block_class .tdb-s-form .tdb-s-form-file.tdb-s-form-file-dragover .tdb-s-ffu-ico {
                    stroke: @accent_color;
                }
                body .$unique_block_class .tdb-fg-item-inner .tdb-s-form-file-box:hover {
                    border-color: @accent_color;
                }
                body .$unique_block_class .tdb-fg-item-inner .tdb-s-form-file-box:hover {
                    outline-color: @input_outline_accent_color;
                }
                
                /* @label_color */
                body .$unique_block_class .tdb-s-form .tdb-s-form-label {
                    color: @label_color;
                }
                /* @descr_color */
                body .$unique_block_class .tdb-s-form .tdb-s-form-label-descr {
                    color: @descr_color;
                }
                /* @input_color */
                body .$unique_block_class .tdb-s-form .tdb-s-form-file-box {
                    color: @input_color;
                }
                /* @input_bg */
                body .$unique_block_class .tdb-s-form .tdb-s-form-file-box,
                body .$unique_block_class .tdb-fg-item:not(.tdb-fg-item-add) .tdb-fg-item-inner {
                    background-color: @input_bg;
                }
                
                /* @remove_btn_color */
                body .$unique_block_class .tdb-fgi-del {
                    color: @remove_btn_color;
                }
                /* @remove_btn_color_h */
                body .$unique_block_class .tdb-fgi-del:hover {
                    color: @remove_btn_color_h;
                }
                /* @remove_btn_bg */
                body .$unique_block_class .tdb-fgi-del {
                    background-color: @remove_btn_bg;
                }
                /* @remove_btn_bg_h */
                body .$unique_block_class .tdb-fgi-del:hover {
                    background-color: @remove_btn_bg_h;
                }
                
                /* @notif_warning_color */
                body .$unique_block_class .tds-s-notif-warning {
                    color: @notif_warning_color;
                }
                /* @notif_warning_bg */
                body .$unique_block_class .tds-s-notif-warning {
                    background-color: @notif_warning_bg;
                }
                
                
                /* @f_text */
                body .$unique_block_class {
                    @f_text
                }
                
			</style>";


        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts() );

        $compiled_css .= $td_css_res_compiler->compile_css();
        return $compiled_css;
    }

    static function cssMedia( $res_ctx ) {

        /*-- GENERAL STYLES -- */
        $res_ctx->load_settings_raw( 'style_general_tdb_form_gallery', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_form_gallery_composer', 1 );
        }



        /*-- LAYOUT -- */
        // inputs display
        $all_input_display = $res_ctx->get_shortcode_att('all_input_display');
        if( $all_input_display == '' || $all_input_display == 'row' ) {
            $res_ctx->load_settings_raw( 'all_input_display_row', 1 );
        } else {
            $res_ctx->load_settings_raw( 'all_input_display_columns', 1 );
        }

        // labels width
        $all_label_width = $res_ctx->get_shortcode_att('all_label_width');
        $res_ctx->load_settings_raw( 'all_label_width', $all_label_width );
        if( $all_label_width != '' ) {
            if( is_numeric( $all_label_width ) ) {
                $res_ctx->load_settings_raw( 'all_label_width', $all_label_width . 'px' );
            }
        } else {
            $res_ctx->load_settings_raw( 'all_label_width', '30%' );
        }

        // columns
        $columns = $res_ctx->get_shortcode_att('columns');
        $columns = $columns != '' ? $columns : '100%';
        $res_ctx->load_settings_raw( 'columns', $columns );

        // inputs border size
        $all_input_border = $res_ctx->get_shortcode_att('all_input_border');
        if( $all_input_border != '' && is_numeric( $all_input_border ) ) {
            $res_ctx->load_settings_raw( 'all_input_border', $all_input_border . 'px' );
        }

        // inputs border style
        $all_input_border_style = $res_ctx->get_shortcode_att('all_input_border_style');
        if( $all_input_border_style != '' ) {
            $res_ctx->load_settings_raw( 'all_input_border_style', $all_input_border_style );
        } else {
            $res_ctx->load_settings_raw( 'all_input_border_style', 'solid' );
        }

        // inputs border radius
        $input_radius = $res_ctx->get_shortcode_att('input_radius');
        $res_ctx->load_settings_raw( 'input_radius', $input_radius );
        if( $input_radius != '' && is_numeric( $input_radius ) ) {
            $res_ctx->load_settings_raw( 'input_radius', $input_radius . 'px' );
        }


        // clear input button border radius
        $remove_btn_radius = $res_ctx->get_shortcode_att('remove_btn_radius');
        $res_ctx->load_settings_raw( 'remove_btn_radius', $remove_btn_radius );
        if( $remove_btn_radius != '' && is_numeric( $remove_btn_radius ) ) {
            $res_ctx->load_settings_raw( 'remove_btn_radius', $remove_btn_radius . 'px' );
        }


        // notifications border radius
        $notif_radius = $res_ctx->get_shortcode_att('notif_radius');
        $res_ctx->load_settings_raw( 'notif_radius', $notif_radius );
        if( $notif_radius != '' && is_numeric( $notif_radius ) ) {
            $res_ctx->load_settings_raw( 'notif_radius', $notif_radius . 'px' );
        }



        /*-- COLORS -- */
        $accent_color = $res_ctx->get_shortcode_att('accent_color');
        $res_ctx->load_settings_raw( 'accent_color', $accent_color );
        if( !empty( $accent_color ) ) {
            $res_ctx->load_settings_raw('input_outline_accent_color', td_util::hex2rgba($accent_color, 0.1));
        }

        $res_ctx->load_settings_raw( 'label_color', $res_ctx->get_shortcode_att('label_color') );
        $res_ctx->load_settings_raw( 'descr_color', $res_ctx->get_shortcode_att('descr_color') );
        $res_ctx->load_settings_raw( 'input_color', $res_ctx->get_shortcode_att('input_color') );
        $res_ctx->load_settings_raw( 'input_bg', $res_ctx->get_shortcode_att('input_bg') );
        $all_input_border_color = $res_ctx->get_shortcode_att('all_input_border_color');
        if( $all_input_border_color != '' ) {
            $res_ctx->load_settings_raw( 'all_input_border_color', $all_input_border_color );
        } else {
            $res_ctx->load_settings_raw( 'all_input_border_color', '#D7D8DE' );
        }

        $res_ctx->load_settings_raw( 'remove_btn_color', $res_ctx->get_shortcode_att('remove_btn_color') );
        $res_ctx->load_settings_raw( 'remove_btn_color_h', $res_ctx->get_shortcode_att('remove_btn_color_h') );
        $res_ctx->load_settings_raw( 'remove_btn_bg', $res_ctx->get_shortcode_att('remove_btn_bg') );
        $res_ctx->load_settings_raw( 'remove_btn_bg_h', $res_ctx->get_shortcode_att('remove_btn_bg_h') );

        $notif_warning_color = $res_ctx->get_shortcode_att('notif_warning_color');
        $res_ctx->load_settings_raw( 'notif_warning_color', $notif_warning_color );
        if( !empty( $notif_warning_color ) ) {
            $res_ctx->load_settings_raw('notif_warning_bg', td_util::hex2rgba($notif_warning_color, 0.08));
        }



        /*-- FONTS -- */
        $res_ctx->load_font_settings( 'f_text' );

    }

    /**
     * Disable loop block features. This block does not use a loop and it doesn't need to run a query.
     */
    function __construct() {
        parent::disable_loop_block_features();
    }

    function render( $atts, $content = null ) {

        parent::render( $atts ); // sets the live atts, $this->atts, $this->block_uid, $this->td_query (it runs the query)


        $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();


        // Supported file extensions
        $file_extensions = array( 'jpg', 'jpeg', 'png', 'gif', 'ico' );


        // Max upload size
        $max_upload_size = wp_max_upload_size();


        // Upload images limit
        $upload_limit = $this->get_att('upload_limit') != '' ? intval( $this->get_att('upload_limit') ) : -1;



        /* --
        -- BLOCK OPTIONS
        -- */
        // Form type
        $form_type = 'post';


        // Gallery source
        $source = $this->get_att('source');


        // ACF field
        $acf_field = $this->get_att('acf_field');


        // Required field
        $required = $this->get_att('required');
        if( $required != '' ) {
            $required = 1;
        } else {
            $required = 0;
        }


        // Enable only for authenticated users
        $authenticated_users = $this->get_att('authenticated_users');
        $disable_for_guests = $authenticated_users != '' && !is_user_logged_in();


        // Currently logged in user
        $current_user = wp_get_current_user();
        $current_user_id = $current_user->ID;
        $is_current_user_admin = in_array('administrator', $current_user->roles);


        // Label text
        $label_txt = $this->get_att('label_txt');
        if( $label_txt == '' ) {
            $label_txt = 'Gallery';
        }

        // Label description
        $label_descr_txt = rawurldecode( base64_decode( strip_tags( $this->get_att('descr_txt') ) ) );


        // Images size
        $images_size = 'td_1068x0';



        /* --
        -- RENDER THE SHORTCODE
        -- */
        $buffy = ''; //output buffer
        $block_error = '';

        $gallery_images = array();
        $uploaded_count = 0;


        if( $source == '' ) {
            $block_error .= td_util::get_block_error(
                'Posts Form Gallery',
                'You have not selected any <strong>option</strong> to save/load a gallery from.' );
        } else if( $source == 'acf_field' && $acf_field == '' ) {
            $block_error .= td_util::get_block_error(
                'Posts Form Gallery',
                'Please select an <strong>ACF field</strong> first.' );
        } else {
            $gallery_images_ids = array();

            /* -- Previewing? -- */
            switch( $form_type ) {
                case 'post':
                    if ( isset($_GET['post_id']) && !( tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax() ) ) {
                        $post = get_post($_GET['post_id']);

                        if( $post && ( $post->post_author == $current_user_id || $is_current_user_admin ) ) {
                            $post_id = $_GET['post_id'];

                            if( $source == 'post_gallery' ) {
                                $td_post_theme_settings = td_util::get_post_meta_array($post->ID, 'td_post_theme_settings');

                                if( !empty($td_post_theme_settings['td_gallery_imgs']) ) {
                                    $gallery_images_ids_meta = $td_post_theme_settings['td_gallery_imgs'];

                                    if( !empty( $gallery_images_ids_meta ) ) {
                                        $gallery_images_ids = explode( ',', $gallery_images_ids_meta );
                                    }
                                }
                            } else if( $source == 'acf_field' ) {
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

                        $gallery_images[] = $gallery_image;
                    }
                }
            } else if( $in_composer ) {
                $gallery_images = array(
                    array(
                        'id' => 1,
                        'alt' => '',
                        'title' => 'Sample gallery image 1',
                        'url' => TDB_URL . '/assets/images/td_meta_replacement.png',
                        'caption' => 'Sample caption'
                    ),
                    array(
                        'id' => 2,
                        'alt' => '',
                        'title' => 'Sample gallery image 2',
                        'url' => TDB_URL . '/assets/images/td_meta_replacement.png',
                        'caption' => 'Sample caption'
                    ),
                    array(
                        'id' => 3,
                        'alt' => '',
                        'title' => 'Sample gallery image 3',
                        'url' => TDB_URL . '/assets/images/td_meta_replacement.png',
                        'caption' => 'Sample caption'
                    ),
                    array(
                        'id' => 4,
                        'alt' => '',
                        'title' => 'Sample gallery image 4',
                        'url' => TDB_URL . '/assets/images/td_meta_replacement.png',
                        'caption' => 'Sample caption'
                    ),
                );
            }

            $uploaded_count = count( $gallery_images );
        }


        $buffy .= '<div class="' . $this->get_block_classes() . ' ' . ( $disable_for_guests ? 'tdb-disabled' : '' ) . '" ' . $this->get_block_html_atts() . ' data-form-type="' . $form_type . '" data-custom-field="' . ( $source != '' ? ( $source == 'post_gallery' ? 'post_gallery' : ( $acf_field != '' ? $acf_field : '' ) ) : '' ) . '" data-required="' . $required . '">';

            //get the block css
            $buffy .= $this->get_block_css();

            //get the js for this block
            $buffy .= $this->get_block_js();


            $buffy .= '<div class="tdb-block-inner td-fix-index tdb-s-form">';
                if( $block_error != '' ) {
                            $buffy .= $block_error;
                        $buffy .= '</div>';
                    $buffy .= '</div>';

                    return $buffy;
                }

                $buffy .= '<div class="tdb-s-form-content">';
                    $buffy .= '<div class="tdb-s-fc-inner">';
                        $buffy .= '<div class="tdb-s-form-group tdb-s-content">';
                            $buffy .= '<label class="tdb-s-form-label">';
                                $buffy .= $label_txt;
                                if( $required ) {
                                    $buffy .= '<span class="tdb-s-form-label-required"> *</span>';
                                }

                                if( $label_descr_txt != '' ) {
                                    $buffy .= '<span class="tdb-s-form-label-descr">' . $label_descr_txt . '</span>';
                                }
                            $buffy .= '</label> ';

                            $buffy .= '<div class="tdb-fg-items">';
                                if( !empty( $gallery_images ) ) {
                                    foreach( $gallery_images as $gallery_image ) {
                                        $buffy .= '<div class="tdb-fg-item" data-image-id="' . $gallery_image['id'] . '">';
                                            $buffy .= '<div class="tdb-fg-item-inner">';
                                                $buffy .= '<img class="tdb-fgi-img" src="' . $gallery_image['url'] . '" ' .
                                                                ( ( !empty( $gallery_image['alt'] ) ? ' alt="' . $gallery_image['alt'] . '"' : '' ) ) .
                                                                ( !empty( $gallery_image['title'] ) ? ' title="' . $gallery_image['title'] . '"' : '' ) .
                                                            ' />';

                                                $buffy .= '<div class="tdb-fgi-del">X</div>';
                                            $buffy .= '</div>';
                                        $buffy .= '</div>';
                                    }
                                }

                                $buffy .= '<div class="tdb-fg-item tdb-fg-item-add tdb-s-form-file tdb-s-content ' . ( $disable_for_guests || ( $upload_limit != -1 && ( $upload_limit == $uploaded_count ) ) ? 'tdb-s-form-file-disabled' : '' ) . '" data-file-type="image">';
                                    $buffy .= '<div class="tdb-fg-item-inner">';
                                        $buffy .= '<div class="tdb-s-form-file-box tdb-s-form-file-upload">';
                                            $buffy .= '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-upload-cloud tdb-s-ffu-ico"><polyline points="16 16 12 12 8 16"></polyline><line x1="12" y1="12" x2="12" y2="21"></line><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path><polyline points="16 16 12 12 8 16"></polyline></svg>';

                                            $buffy .= '<div class="tdb-s-ffu-txt">' . __td( 'Drag and drop or browse', TD_THEME_NAME ) . '</div>';

                                            $buffy .= '<input class="tdb-s-form-file-input" name="tdb-posts-form-file" type="file" accept=".' . implode(',.', $file_extensions) . '" ' . ( $disable_for_guests ? 'disabled' : '' ) . ' multiple />';
                                        $buffy .= '</div>';
                                    $buffy .= '</div>';
                                $buffy .= '</div>';
                            $buffy .= '</div>';

                            $buffy .= '<div class="tdb-fg-inputs"></div>';
                        $buffy .= '</div>';

                        $buffy .= '<div class="tdb-s-notif tdb-s-notif-sm tdb-s-notif-warning"><ul class="tdb-s-notif-list"></ul></div>';
                    $buffy .= '</div>';
                $buffy .= '</div>';
            $buffy .= '</div>';

            if( !( $in_composer ) ) {
                td_resources_load::render_script( TDB_SCRIPTS_URL . '/tdbFormGallery.js' . TDB_SCRIPTS_VER, 'tdbFormGallery-js', '', 'footer' );

                ob_start();
                ?>
                <script>
                    /* global jQuery:{} */
                    jQuery().ready( function () {

                        let uid = '<?php echo $this->block_uid ?>',
                            $blockObj = jQuery('.<?php echo $this->block_uid ?>');

                        let tdbFormGalleryItem = new tdbFormGallery.item();
                        // block uid
                        tdbFormGalleryItem.uid = uid;
                        // block object
                        tdbFormGalleryItem.blockObj = $blockObj;
                        // file extensions
                        tdbFormGalleryItem.fileExtensions = <?php echo json_encode($file_extensions) ?>;
                        // max upload size
                        tdbFormGalleryItem.maxUploadSize = '<?php echo $max_upload_size ?>';
                        // max upload limit
                        tdbFormGalleryItem.uploadLimit = parseInt('<?php echo json_encode( $upload_limit ) ?>');
                        // uploaded images count
                        tdbFormGalleryItem.uploadedCount = parseInt('<?php echo json_encode( $uploaded_count ) ?>');

                        tdbFormGallery.addItem(tdbFormGalleryItem);

                    });
                </script>
                <?php
                td_js_buffer::add_to_footer( "\n" . td_util::remove_script_tag( ob_get_clean() ) );
            }

        $buffy .= '</div> <!-- ./block -->';


        return $buffy;
    }

}