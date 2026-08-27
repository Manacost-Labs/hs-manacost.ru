<?php

/**
 * Class tdb_module_img - shortcode for modules images( renders post featured img )
 */
class tdb_module_img extends tdb_module_template_part {

	protected $post_thumb_id = null;


    public function get_custom_css() {

        $style_selector = self::$style_selector;
		$style_atts_uid = self::$style_atts_uid;
		$unique_block_modal_class = $style_atts_uid . '_m';


        $compiled_css = '';

        $raw_css = "<style>
		
			/* @style_general_tdb_module_img */
			.tdb_module_img {
				display: block;
				position: relative;
				margin: 0;
				transform: translateZ(0);
			}
			.tdb_module_img .entry-thumb {
			    z-index: -1;
			}
			.tdb_module_img img {
				display: block;
				width: 100%;
			}
			.tdb_module_img .td-video-play-ico {
				z-index: 12;
			}



			/* @tdb_mts_display_cover_$style_atts_uid */
			.$style_selector {
				height: 0;
			}
			.$style_selector img {
				position: absolute;
				width: 100%;
				height: 100%;
				object-fit: cover;
			}

			/* @tdb_mts_height_$style_atts_uid */
			.$style_selector {
				padding-bottom: @tdb_mts_height_$style_atts_uid;
			}

			/* @tdb_mts_all_border_$style_atts_uid */
			.$style_selector img {
				border: @tdb_mts_all_border_$style_atts_uid @tdb_mts_all_border_style_$style_atts_uid @tdb_mts_all_border_color_$style_atts_uid;
			}

			/* @tdb_mts_radius_$style_atts_uid */
			.$style_selector,
			html:not([class*='ie']) .$style_selector:before,
			html:not([class*='ie']) .$style_selector:after,
			.$style_selector .td-element-style,
			.$style_selector img {
				border-radius: @tdb_mts_radius_$style_atts_uid;
			}


			/* @tdb_mts_icon_show_$style_atts_uid */
			.$style_selector .td-video-play-ico {
				display: @tdb_mts_icon_show_$style_atts_uid;
			}

			/* @tdb_mts_icon_size_$style_atts_uid */
			body .$style_selector .td-video-play-ico {
				width: @tdb_mts_icon_size_$style_atts_uid;
				height: @tdb_mts_icon_size_$style_atts_uid;
				font-size: @tdb_mts_icon_size_$style_atts_uid;
			}

			/* @tdb_mts_icon_space_$style_atts_uid */
			.$style_selector .td-video-play-ico {
				margin: @tdb_mts_icon_space_$style_atts_uid;
			}

			/* @tdb_mts_icon_align_top_left_$style_atts_uid */
			.$style_selector .td-video-play-ico {
				top: 0;
				bottom: auto;
				left: 0;
				right: auto;
				transform: none;
			}
			/* @tdb_mts_icon_align_top_center_$style_atts_uid */
			.$style_selector .td-video-play-ico {
				top: 0;
				bottom: auto;
				left: 50%;
				right: auto;
				transform: translateX(-50%);
			}
			/* @tdb_mts_icon_align_top_right_$style_atts_uid */
			.$style_selector .td-video-play-ico {
				top: 0;
				bottom: auto;
				left: auto;
				right: 0;
				transform: none;
			}
			/* @tdb_mts_icon_align_center_left_$style_atts_uid */
			.$style_selector .td-video-play-ico {
				top: 50%;
				bottom: auto;
				left: 0;
				right: auto;
				transform: translateY(-50%);
			}
			/* @tdb_mts_icon_align_center_center_$style_atts_uid */
			.$style_selector .td-video-play-ico {
				top: 50%;
				bottom: auto;
				left: 50%;
				right: auto;
				transform: translate(-50%, -50%);
			}
			/* @tdb_mts_icon_align_center_right_$style_atts_uid */
			.$style_selector .td-video-play-ico {
				top: 50%;
				bottom: auto;
				left: auto;
				right: 0;
				transform: translateY(-50%);
			}
			/* @tdb_mts_icon_align_bottom_left_$style_atts_uid */
			.$style_selector .td-video-play-ico {
				top: auto;
				bottom: 0;
				left: 0;
				right: auto;
				transform: none;
			}
			/* @tdb_mts_icon_align_bottom_center_$style_atts_uid */
			.$style_selector .td-video-play-ico {
				top: auto;
				bottom: 0;
				left: 50%;
				right: 0;
				transform: translateX(-50%);
			}
			/* @tdb_mts_icon_align_bottom_right_$style_atts_uid */
			.$style_selector .td-video-play-ico {
				top: auto;
				bottom: 0;
				left: auto;
				right: 0;
				transform: none;
			}



			/* @tdb_mts_shadow_$style_atts_uid */
			.$style_selector {
				box-shadow: @tdb_mts_shadow_$style_atts_uid;
			}
			
			/* @tdb_mts_mix_type_$style_atts_uid */
			html:not([class*='ie']) .$style_selector:before {
				content: '';
				width: 100%;
				height: 100%;
				position: absolute;
				top: 0;
				left: 0;
				opacity: 1;
				transition: opacity 1s ease;
				-webkit-transition: opacity 1s ease;
				mix-blend-mode: @tdb_mts_mix_type_$style_atts_uid;
				z-index: 10;
			}
			/* @tdb_mts_color_solid_$style_atts_uid */
			html:not([class*='ie']) .$style_selector:before {
				background: @tdb_mts_color_solid_$style_atts_uid;
			}
			/* @tdb_mts_mix_gradient_$style_atts_uid */
			html:not([class*='ie']) .$style_selector:before {
				@tdb_mts_mix_gradient_$style_atts_uid;
			}
			
			/* @tdb_mts_mix_type_h_$style_atts_uid */
			@media (min-width: 1141px) {
				html:not([class*='ie']) .$style_selector:after {
					content: '';
					width: 100%;
					height: 100%;
					position: absolute;
					top: 0;
					left: 0;
					opacity: 0;
					transition: opacity 1s ease;
					-webkit-transition: opacity 1s ease;
					mix-blend-mode: @tdb_mts_mix_type_h_$style_atts_uid;
					z-index: 11;
				}
				html:not([class*='ie']) .$style_selector:hover:after {
					opacity: 1;
				}
			}
			
			/* @tdb_mts_color_solid_h_$style_atts_uid */
			html:not([class*='ie']) .$style_selector:after {
				background: @tdb_mts_color_solid_h_$style_atts_uid;
			}
			/* @tdb_mts_mix_gradient_h_$style_atts_uid */
			html:not([class*='ie']) .$style_selector :after {
				@tdb_mts_mix_gradient_h_$style_atts_uid;
			}
			/* @tdb_mts_mix_type_off_$style_atts_uid */
			html:not([class*='ie']) .$style_selector:hover:before {
				opacity: 0;
			}
				
			/* @tdb_mts_effect_on_$style_atts_uid */
			html:not([class*='ie']) .$style_selector .entry-thumb {
				filter: @fe_brightness_$style_atts_uid @fe_contrast_$style_atts_uid @fe_saturate_$style_atts_uid;
				transition: all 1s ease;
				-webkit-transition: all 1s ease;
			}
			/* @tdb_mts_effect_on_h_$style_atts_uid */
			@media (min-width: 1141px) {
				html:not([class*='ie']) .$style_selector:hover .entry-thumb {
					filter: @fe_brightness_h_$style_atts_uid @fe_contrast_h_$style_atts_uid @fe_saturate_h_$style_atts_uid;
				}
			}


			/* @tdb_mts_icon_bg_$style_atts_uid */
			.$style_selector .td-video-play-ico {
				background-color: @tdb_mts_icon_bg_$style_atts_uid;
			}
			/* @tdb_mts_icon_border_$style_atts_uid */
			.$style_selector .td-video-play-ico {
				border-color: @tdb_mts_icon_border_$style_atts_uid;
			}
			/* @tdb_mts_icon_color_$style_atts_uid */
			.$style_selector .td-video-play-ico i {
				color: @tdb_mts_icon_color_$style_atts_uid;
			}
			/* @tdb_mts_icon_shadow_$style_atts_uid */
			.$style_selector .td-video-play-ico {
				box-shadow: @tdb_mts_icon_shadow_$style_atts_uid;
			}

			/* @tdb_mts_video_rec_color_$style_atts_uid */
			#td-video-modal.$unique_block_modal_class .td-vm-rec-title {
				color: @tdb_mts_video_rec_color_$style_atts_uid;
			}
			/* @tdb_mts_video_title_color_$style_atts_uid */
			#td-video-modal.$unique_block_modal_class .td-vm-title a {
				color: @tdb_mts_video_title_color_$style_atts_uid;
			}
			/* @tdb_mts_video_title_color_h_$style_atts_uid */
			#td-video-modal.$unique_block_modal_class .td-vm-title a:hover {
				color: @tdb_mts_video_title_color_h_$style_atts_uid;
			}
			/* @tdb_mts_video_bg_color_$style_atts_uid */
			#td-video-modal.$unique_block_modal_class .td-vm-content-wrap {
				background-color: @tdb_mts_video_bg_color_$style_atts_uid;
			}
			/* @tdb_mts_video_bg_gradient_$style_atts_uid */
			#td-video-modal.$unique_block_modal_class .td-vm-content-wrap {
				@tdb_mts_video_bg_gradient_$style_atts_uid
			}
			/* @tdb_mts_video_overlay_color_$style_atts_uid */
			#td-video-modal.$unique_block_modal_class .td-vm-overlay {
				background-color: @tdb_mts_video_overlay_color_$style_atts_uid;
			}
			/* @tdb_mts_video_overlay_gradient_$style_atts_uid */
			#td-video-modal.$unique_block_modal_class .td-vm-overlay {
				background-color: @tdb_mts_video_overlay_gradient_$style_atts_uid;
			}

			/* @tdb_mts_f_vid_title_$style_atts_uid */
			#td-video-modal.$unique_block_modal_class .td-vm-title {
				@tdb_mts_f_vid_title_$style_atts_uid
			}
		
		</style>";

        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts() );

        $compiled_css .= $td_css_res_compiler->compile_css();
        return $compiled_css;
    }

    static function cssMedia( $res_ctx ) {

        $style_atts_uid = self::$style_atts_uid;
		
		/* --
		-- GENERAL
		-- */
		$res_ctx->load_settings_raw( 'style_general_tdb_module_img', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_module_img_composer', 1 );
        }




		/* --
		-- LAYOUT
		-- */
		// Image display & height
		$display = $res_ctx->get_shortcode_att('display');
		switch( $display ) {
			case '':
				$res_ctx->load_settings_raw( 'tdb_mts_display_cover_' . $style_atts_uid, 1 );

				$height = $res_ctx->get_shortcode_att('height');
				$height = !empty( $height ) ? $height : '50%';
				$height .= is_numeric( $height ) ? 'px' : '';
				$res_ctx->load_settings_raw( 'tdb_mts_height_' . $style_atts_uid, $height );

				break;

			case 'auto':
				$res_ctx->load_settings_raw( 'tdb_mts_display_auto_' . $style_atts_uid, 1 );

				break;
		}

		// Border size
		$all_border = $res_ctx->get_shortcode_att('all_border');
		$all_border .= is_numeric( $all_border ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'tdb_mts_all_border_' . $style_atts_uid, $all_border );

		// Border style
		$all_border_style = $res_ctx->get_shortcode_att('all_border_style');
		$all_border_style = $all_border_style != '' ? $all_border_style : 'solid';
		$res_ctx->load_settings_raw( 'tdb_mts_all_border_style_' . $style_atts_uid, $all_border_style );

		// Border radius
		$radius = $res_ctx->get_shortcode_att('radius');
		$radius .= is_numeric( $radius ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'tdb_mts_radius_' . $style_atts_uid, $radius );


		/* -- Post type icon -- */
		// Show icon
		$icon_show = $res_ctx->get_shortcode_att('icon_show');
		$icon_show = !empty( $icon_show ) ? $icon_show : 'block';
		$res_ctx->load_settings_raw( 'tdb_mts_icon_show_' . $style_atts_uid, $icon_show );

		// Icon size
		$icon_size = $res_ctx->get_shortcode_att('icon_size');
		$icon_size .= is_numeric( $icon_size ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'tdb_mts_icon_size_' . $style_atts_uid, $icon_size );
		
		// Icon space
		$icon_space = $res_ctx->get_shortcode_att('icon_space');
		$icon_space .= is_numeric( $icon_space ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'tdb_mts_icon_space_' . $style_atts_uid, $icon_space );
		
		// Vertical & horizontal align
		$icon_align = $res_ctx->get_shortcode_att('icon_align');
		switch( $icon_align ) {
			case '':
			case 'top-left':
				$res_ctx->load_settings_raw( 'tdb_mts_icon_align_top_left_' . $style_atts_uid, 1 );
				break;
			case 'top-left':
				$res_ctx->load_settings_raw( 'tdb_mts_icon_align_top_center_' . $style_atts_uid, 1 );
				break;
			case 'top-right':
				$res_ctx->load_settings_raw( 'tdb_mts_icon_align_top_right_' . $style_atts_uid, 1 );
				break;
			case 'center-left':
				$res_ctx->load_settings_raw( 'tdb_mts_icon_align_center_left_' . $style_atts_uid, 1 );
				break;
			case 'center-center':
				$res_ctx->load_settings_raw( 'tdb_mts_icon_align_center_center_' . $style_atts_uid, 1 );
				break;
			case 'center-right':
				$res_ctx->load_settings_raw( 'tdb_mts_icon_align_center_right_' . $style_atts_uid, 1 );
				break;
			case 'bottom-left':
				$res_ctx->load_settings_raw( 'tdb_mts_icon_align_bottom_left_' . $style_atts_uid, 1 );
				break;
			case 'bottom-center':
				$res_ctx->load_settings_raw( 'tdb_mts_icon_align_bottom_center_' . $style_atts_uid, 1 );
				break;
			case 'bottom-right':
				$res_ctx->load_settings_raw( 'tdb_mts_icon_align_bottom_right_' . $style_atts_uid, 1 );
				break;
		}
		



		/* --
		-- STYLE
		-- */
        // Shadow
        $res_ctx->load_shadow_settings( 0, 0, 0, 0, '#000', 'shadow', '', false, 'tdb_mts_', '_' . $style_atts_uid );

		/* -- Mix blend/effects -- */
		// Border color
		$all_border_color = $res_ctx->get_shortcode_att('all_border_color');
		$all_border_color = $all_border_color != '' ? $all_border_color : '#000';
		$res_ctx->load_settings_raw( 'tdb_mts_all_border_color_' . $style_atts_uid, $all_border_color );

		// Mix blend
        $mix_type = $res_ctx->get_shortcode_att('mix_type');
        if ( $mix_type != '' ) {
            $res_ctx->load_settings_raw('tdb_mts_mix_type_' . $style_atts_uid, $res_ctx->get_shortcode_att('mix_type'));
        }
        $res_ctx->load_color_settings( 'mix_color', 'tdb_mts_color_solid_' . $style_atts_uid, 'tdb_mts_mix_gradient_' . $style_atts_uid, '', '' );

        $mix_type_h = $res_ctx->get_shortcode_att('mix_type_h');
        if ( $mix_type_h != '' ) {
            $res_ctx->load_settings_raw('tdb_mts_mix_type_h_' . $style_atts_uid, $res_ctx->get_shortcode_att('mix_type_h'));
        } else {
            $res_ctx->load_settings_raw('tdb_mts_mix_type_off_' . $style_atts_uid, 1);
        }
        $res_ctx->load_color_settings( 'mix_color_h', 'tdb_mts_color_solid_h_' . $style_atts_uid, 'tdb_mts_mix_gradient_h_' . $style_atts_uid, '', '' );

		// Effects
		$res_ctx->load_settings_raw('fe_brightness_' . $style_atts_uid, 'brightness(1)');
        $res_ctx->load_settings_raw('fe_contrast_' . $style_atts_uid, 'contrast(1)');
        $res_ctx->load_settings_raw('fe_saturate_' . $style_atts_uid, 'saturate(1)');

        $fe_brightness = $res_ctx->get_shortcode_att('fe_brightness');
        if ($fe_brightness != '1') {
            $res_ctx->load_settings_raw('fe_brightness_' . $style_atts_uid, 'brightness(' . $fe_brightness . ')');
            $res_ctx->load_settings_raw('tdb_mts_effect_on_' . $style_atts_uid, 1);
        }

        $fe_contrast = $res_ctx->get_shortcode_att('fe_contrast');
        if ($fe_contrast != '1') {
            $res_ctx->load_settings_raw('fe_contrast_' . $style_atts_uid, 'contrast(' . $fe_contrast . ')');
            $res_ctx->load_settings_raw('tdb_mts_effect_on_' . $style_atts_uid, 1);
        }
        $fe_saturate = $res_ctx->get_shortcode_att('fe_saturate');
        if ($fe_saturate != '1') {
            $res_ctx->load_settings_raw('fe_saturate_' . $style_atts_uid, 'saturate(' . $fe_saturate . ')');
            $res_ctx->load_settings_raw('tdb_mts_effect_on_' . $style_atts_uid, 1);
        }

        // effects hover
        $res_ctx->load_settings_raw('fe_brightness_h_' . $style_atts_uid, 'brightness(1)');
        $res_ctx->load_settings_raw('fe_contrast_h_' . $style_atts_uid, 'contrast(1)');
        $res_ctx->load_settings_raw('fe_saturate_h_' . $style_atts_uid, 'saturate(1)');

        $fe_brightness_h = $res_ctx->get_shortcode_att('fe_brightness_h');
        $fe_contrast_h = $res_ctx->get_shortcode_att('fe_contrast_h');
        $fe_saturate_h = $res_ctx->get_shortcode_att('fe_saturate_h');

        if ($fe_brightness_h != '1') {
            $res_ctx->load_settings_raw('fe_brightness_h_' . $style_atts_uid, 'brightness(' . $fe_brightness_h . ')');
            $res_ctx->load_settings_raw('tdb_mts_effect_on_h_' . $style_atts_uid, 1);
        }
        if ($fe_contrast_h != '1') {
            $res_ctx->load_settings_raw('fe_contrast_h_' . $style_atts_uid, 'contrast(' . $fe_contrast_h . ')');
            $res_ctx->load_settings_raw('tdb_mts_effect_on_h_' . $style_atts_uid, 1);
        }
        if ($fe_saturate_h != '1') {
            $res_ctx->load_settings_raw('fe_saturate_h_' . $style_atts_uid, 'saturate(' . $fe_saturate_h . ')');
            $res_ctx->load_settings_raw('tdb_mts_effect_on_h_' . $style_atts_uid, 1);
        }
        // make hover to work
        if ($fe_brightness_h != '1' || $fe_contrast_h != '1' || $fe_saturate_h != '1') {
            $res_ctx->load_settings_raw('tdb_mts_effect_on_' . $style_atts_uid, 1);
        }
        if ($fe_brightness != '1' || $fe_contrast != '1' || $fe_saturate != '1') {
            $res_ctx->load_settings_raw('tdb_mts_effect_on_h_' . $style_atts_uid, 1);
        }

		/* -- Post type icon -- */
		$res_ctx->load_settings_raw( 'tdb_mts_icon_bg_' . $style_atts_uid, $res_ctx->get_shortcode_att('icon_bg') );
		$res_ctx->load_settings_raw( 'tdb_mts_icon_border_' . $style_atts_uid, $res_ctx->get_shortcode_att('icon_border') );
		$res_ctx->load_settings_raw( 'tdb_mts_icon_color_' . $style_atts_uid, $res_ctx->get_shortcode_att('icon_color') );

		$res_ctx->load_shadow_settings( 6, 0, 0, 0, 'rgba(0, 0, 0, 0.4)', 'icon_shadow', '', false, 'tdb_mts_', '_' . $style_atts_uid );


		/* -- Video pop-up -- */
		$res_ctx->load_settings_raw( 'tdb_mts_video_rec_color_' . $style_atts_uid, $res_ctx->get_shortcode_att('video_rec_color') );
		$res_ctx->load_settings_raw( 'tdb_mts_video_title_color_' . $style_atts_uid, $res_ctx->get_shortcode_att('video_title_color') );
		$res_ctx->load_settings_raw( 'tdb_mts_video_title_color_h_' . $style_atts_uid, $res_ctx->get_shortcode_att('video_title_color_h') );
        $res_ctx->load_color_settings( 'video_bg', 'tdb_mts_video_bg_color_' . $style_atts_uid, 'tdb_mts_video_bg_gradient_' . $style_atts_uid, '', '' );
        $res_ctx->load_color_settings( 'video_overlay', 'tdb_mts_video_overlay_color_' . $style_atts_uid, 'tdb_mts_video_overlay_gradient_' . $style_atts_uid, '', '' );




		/* --
		-- FONTS
		-- */
		$res_ctx->load_font_settings( 'f_vid_title', '', 'tdb_mts_', '_' . $style_atts_uid );
	
	}


    function render( $atts, $content = null ) {

		/* -- Call the parent render method -- */
        parent::render($atts);



		/* -- Retrieve the module post data -- */
		$post_obj = self::$post_obj;
		$post_type = null;

	    $attachment_alt = ' alt="sample attachment alt"';
	    $attachment_title = ' title="sample attachment title"';
	    $srcset_sizes = '';
		$image_size = !empty( $this->get_att('image_size') ) ? $this->get_att('image_size') : 'td_696x0';
	    $no_thumb_path = rtrim( td_api_thumb::get_key( $image_size, 'no_image_path' ), '/' );
	    $image_info = array (
		    'alt' => '',
		    'caption' => '',
		    'description' => '',
		    'href' => '',
		    'src' => $no_thumb_path . '/images/no-thumb/' . $image_size . '.png',
		    'title' => '',
		    'width' => '',
		    'height' => ''
	    );

		$video_popup_class = '';
		$video_popup_data = '';

		$post_icon = '';

	    $post_link = '#';
	    $post_title_attribute = 'sample post title attribute';

		if ( gettype( $post_obj ) === 'object' && get_class( $post_obj ) === 'WP_Post' ) {

			$post_obj_id = $post_obj->ID; // the module post id
			$post_type = get_post_format($post_obj_id);
			$post_link = esc_url( get_permalink( $post_obj_id ) );
			$post_title_attribute = esc_attr( strip_tags( get_the_title( $post_obj_id ) ) );

			$tds_hide_featured_image_placeholder = td_util::get_option('tds_hide_featured_image_placeholder');
            $this->post_thumb_id = has_post_thumbnail($post_obj_id) ? get_post_thumbnail_id($post_obj_id) : NULL;

            /* -- Check to see if we have a post thumb or placeholders are enabled -- */
            if( !is_null($this->post_thumb_id) ) {
                // There is a post thumb, check to see if its size is enabled in the panel
                if ( td_util::get_option('tds_thumb_' . $image_size ) != 'yes' && $image_size != 'thumbnail' && $image_size != 'medium_large' ) {
                    // The thumb size is disabled, so show a placeholder thumb
                    global $_wp_additional_image_sizes;

                    if( !empty( $_wp_additional_image_sizes[$image_size]['width'] ) ) {
                        $image_info['width'] = $_wp_additional_image_sizes[$image_size]['width'];
                    }

                    if( !empty( $_wp_additional_image_sizes[$image_size]['height'] ) ) {
                        $image_info['height'] = $_wp_additional_image_sizes[$image_size]['height'];
                    }

                    $thumb_disabled_path = td_global::$get_template_directory_uri;
                    if ( strpos( $image_size, 'td_' ) === 0 ) {
                        $thumb_disabled_path = td_api_thumb::get_key( $image_size, 'no_image_path' );
                    }
                    $image_info['src'] = $thumb_disabled_path . '/images/thumb-disabled/' . $image_size . '.png';

                    $attachment_alt = 'alt=""';
                    $attachment_title = '';
                } else {
                    // The thumbnail size is enabled in the panel, try to get the image
                    $image_info = td_util::attachment_get_full_info( $this->post_thumb_id, $image_size );

                    $attachment_alt = !empty($image_info['alt']) ? ' alt="' . esc_attr( strip_tags( $image_info['alt'] ) ) . '"' : '';
                    $attachment_title = !empty($image_info['title']) ? ' title="' . esc_attr( strip_tags( $image_info['title'] ) ) . '"' : '';

                    if( TD_DEPLOY_MODE != 'demo' ) {
                        $srcset_sizes = td_util::get_srcset_sizes( $this->post_thumb_id, $image_size, $image_info['width'], $image_info['src'] );
                    }
                }
            } else {
                global $_wp_additional_image_sizes;

                if( !empty( $_wp_additional_image_sizes[$image_size]['width'] ) ) {
                    $image_info['width'] = $_wp_additional_image_sizes[$image_size]['width'];
                }

                if( !empty( $_wp_additional_image_sizes[$image_size]['height'] ) ) {
                    $image_info['height'] = $_wp_additional_image_sizes[$image_size]['height'];
                }

                if ( !empty( $_wp_additional_image_sizes ) && array_key_exists( $image_size, $_wp_additional_image_sizes ) && ( $image_info['width'] == '' || $image_info['height'] == '' ) ) {
                    $td_thumb_parameters = td_api_thumb::get_by_id($image_size);
                    $image_info['width'] = $td_thumb_parameters['width'];
                    $image_info['height'] = $td_thumb_parameters['height'];
                }

                $custom_placeholder_exists = false;
                $custom_placeholder = td_util::get_option('tds_thumb_placeholder');
                if ( $custom_placeholder != '' && !empty($_wp_additional_image_sizes) && array_key_exists($image_size, $_wp_additional_image_sizes) ) {
                    global $wpdb;
                    $placeholder_id = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $custom_placeholder ));

                    if( !empty( $placeholder_id ) ) {
                        $custom_placeholder_info = wp_get_attachment_image_src($placeholder_id[0], $image_size);

                        if( $custom_placeholder_info != false ) {
                            $custom_placeholder_headers = @get_headers($custom_placeholder);

                            if( $custom_placeholder_headers && $custom_placeholder_headers[0] != 'HTTP/1.1 404 Not Found' ) {
                                $image_info['width'] = $custom_placeholder_info[1];
                                $image_info['height'] = $custom_placeholder_info[2];
                                $image_info['src'] = $custom_placeholder_info[0];

                                $custom_placeholder_exists = true;
                            }
                        }
                    }
                }

                if( !$custom_placeholder_exists ) {
                    if( strpos($image_size, 'td_') === 0 ) {
                        $no_thumb_path = rtrim(td_api_thumb::get_key($image_size, 'no_image_path'), '/');
                        $image_info['src'] = $no_thumb_path . '/images/no-thumb/' . $image_size . '.png';
                    } else {
                        $no_thumb_path = TDC_URL_LEGACY;
                        $image_info['src'] = $no_thumb_path . '/assets/images/no-thumb/' . $image_size . '.png';
                    }
                }

                $attachment_alt = 'alt=""';
                $attachment_title = '';
            }




			// Video modal
			$show_video_modal = $this->get_att('video_popup') != '';

			

			if ( $post_type == 'video' && $show_video_modal ) {
				$video_url = get_post_meta($post_obj_id, 'td_post_video');

				if( isset($video_url[0]['td_video']) && $video_url[0]['td_video'] != '' ) {
					$video_source = td_video_support::detect_video_service($video_url[0]['td_video']);

					$autoplay_vid = $this->get_att('autoplay_vid');

					$video_popup_class = 'td-module-video-modal';
					$video_popup_data = 'data-video-source="' . $video_source . '" data-video-autoplay="' . $autoplay_vid . '" data-video-url="'. esc_url( $video_url[0]['td_video'] ) . '"';

					$video_rec = rawurldecode( base64_decode( strip_tags( $this->get_att('video_rec') ) ) );
					$video_rec_title = $this->get_att('video_rec_title');
					$video_rec_disable = ( current_user_can('administrator') || current_user_can('editor') ) && $this->get_att('video_rec_disable') != '';

					$video_popup_ad = array(
						'code' => do_shortcode( stripslashes( $video_rec ) ),
						'title' => $video_rec_title,
						'disable' => $video_rec_disable,
					);

					if( $video_popup_ad['code'] == '' ) {
						$video_popup_ad['code'] = stripslashes( td_options::get( 'tds_modal_video_ad') );
					}
					if( $video_popup_ad['title'] == '' ) {
						$video_popup_ad['title'] = td_options::get( 'tds_modal_video_ad_title');
					}
					if( !$video_popup_ad['disable'] && ( current_user_can('administrator') || current_user_can('editor') ) ) {
						if( td_options::get( 'tds_modal_video_ad_disable') != '' ) {
							$video_popup_ad['disable'] = true;
						}
					}

					if( $video_popup_ad['code'] != '' ) {
						$video_popup_data .= 'data-video-rec="' . base64_encode( json_encode($video_popup_ad) ) . '"';
					}

                    td_resources_load::render_script( TDC_SCRIPTS_URL . '/tdAjaxVideoModal.js' . TDC_SCRIPTS_VER, 'tdAjaxVideoModal-js', '', 'footer');
				}
			}

		}


		/* -- Set the post type icon -- */
		$version = $this->get_att('version');
		$post_type_icon = '';

		if( ( tdc_state::is_live_editor_ajax() || tdc_state::is_live_editor_iframe() ) && $version != '' ) {
			$post_type_icon = $version;
		} else {
			$post_type_icon = $post_type;
		}

		if( $post_type_icon != '' ) {
			$post_icon = '<span class="td-video-play-ico"><i class="td-icon-' . $post_type_icon . '-thumb-play"></i></span>';
		}




		/* -- Block atts -- */
		// Nofollow attribute
	    $nofollow = '';
	    if ( td_util::get_option('tds_m_nofollow_image') == 'yes') {
		    $nofollow = 'nofollow ';
	    }

		// Open link in new tab
		$open_in_new_tab = $this->get_att( 'open_in_new_tab' );
		$link_target = $open_in_new_tab != '' ? ' target="blank"' : '';

		// Additional classes
		$additional_classes = array();

        $tds_animation_stack = td_util::get_option('tds_animation_stack');
        if ( empty($tds_animation_stack) ) { //lazyload animation is ON
            $additional_classes[] = 'td-animation-stack';
        }

        $td_use_webp = '';
        $wp_directory = trailingslashit(ABSPATH);
        $wp_dirname = basename(rtrim($wp_directory, '/'));
        $webp_img_path = parse_url($image_info['src'], PHP_URL_PATH) . '.webp';
        $webp_img_path = str_replace("/$wp_dirname/", '/', $webp_img_path);
        $webp_img_path = rtrim(ABSPATH, '/') . '/' . ltrim($webp_img_path, '/');

        if (file_exists($webp_img_path)) {
            $td_use_webp = ( td_util::browser_supports_webp() && td_util::get_option('tds_load_webp') === 'yes' ) ? '.webp' : $td_use_webp;
        }


        $additional_classes[] = $video_popup_class;



		/* -- Output the module element HTML -- */
        $buffy = '';


		$buffy .= '<a 
						href="' . $post_link . '" 
						rel="' . $nofollow . 'bookmark" 
						class="td-image-wrap ' . $this->get_block_classes($additional_classes) . '" 
						' . $this->get_block_html_atts() . ' 
						data-tdb-module-template-class="' . ( self::$template_class . '_' . self::$template_part_index ) . '" 
						title="' . $post_title_attribute . '" 
						' . $video_popup_data . 
						$link_target . 
					'>';

            // get the block css
            $buffy .= $this->get_block_css();

            // get the js for this block
            $buffy .= $this->get_block_js();

			if ( empty( $tds_animation_stack ) && !wp_doing_ajax() && ! td_util::tdc_is_live_editor_ajax() && ! td_util::tdc_is_live_editor_iframe() ) {
				$retina_image = '';

				if ( td_util::get_option('tds_thumb_' . $image_size . '_retina') == 'yes' && !empty( $image_info['width'] ) ) {
					$retina_url = wp_get_attachment_image_src( $this->post_thumb_id, $image_size . '_retina' );
					if ( !empty( $retina_url[0] ) ) {
						$retina_image = 'data-img-retina-url="' . $retina_url[0] . $td_use_webp . '"';
					}
				}

				$base64 = '';
				if ( strpos( $image_size, 'td_' ) === 0 ) {
					$thumbs = td_api_thumb::get_all();
					foreach ($thumbs as $thumb_id => $thumb_data ) {
						if ( $thumb_id === $image_size ) {
							if ( isset($thumb_data['b64_encoded'] ) ) {
								$base64 = td_api_thumb::get_key( $image_size, 'b64_encoded' );
							}
						}
					}
				}

				$buffy .= '<img class="entry-thumb" src="' . $base64 . '"' . $attachment_alt . $attachment_title . ' data-type="image_tag" data-img-url="' . $image_info['src'] . $td_use_webp . '" ' . $retina_image . ' width="' . $image_info['width'] . '" height="' . $image_info['height'] . '" />';
			} else {
				$buffy .= '<img class="entry-thumb" width="' . $image_info['width'] . '" height="' . $image_info['height'] . '" src="' . $image_info['src'] . $td_use_webp . '" ' . $srcset_sizes . ' ' . $attachment_alt . $attachment_title . ' />';
			}

			$buffy .= $post_icon;

		$buffy .='</a>';

        return $buffy;
    }

}

