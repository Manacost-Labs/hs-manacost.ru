<?php

/**
 * Class tdb_module_author_photo - shortcode for cloud template modules (renders post title)
 */
class tdb_module_author_photo extends tdb_module_template_part {

    public function get_custom_css() {

		$style_selector = self::$style_selector;
		$style_atts_uid = self::$style_atts_uid;
		

        $compiled_css = '';

        $raw_css = "<style>
		
			/* @style_general_tdb_module_author_photo */
			.tdb_module_author_photo {
				position: relative;
				display: flex;
				margin: 0;
			}
			.tdb_module_author_photo a {
				transform: translateZ(0);
            }
			.tdb_module_author_photo img {
				display: block;
			}



			/* @tdb_mts_align_horiz_$style_atts_uid */
			.$style_selector {
				justify-content: @tdb_mts_align_horiz_$style_atts_uid;
			}
			
			/* @tdb_mts_size_$style_atts_uid */
			.$style_selector img {
				width: @tdb_mts_size_$style_atts_uid;
				height: @tdb_mts_size_$style_atts_uid;
			}
			
			/* @tdb_mts_all_border_$style_atts_uid */
			.$style_selector img {
				border: @tdb_mts_all_border_$style_atts_uid @tdb_mts_all_border_style_$style_atts_uid @tdb_mts_all_border_color_$style_atts_uid;
			}
			/* @tdb_mts_radius_$style_atts_uid */
			.$style_selector img {
				border-radius: @tdb_mts_radius_$style_atts_uid;
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
		$res_ctx->load_settings_raw( 'style_general_tdb_module_author_photo', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_module_author_photo_composer', 1 );
        }




		/* --
		-- AUTHOR PHOTO
		-- */
		/* -- Layout -- */
		// Horizontal align
		$align_horiz = $res_ctx->get_shortcode_att( 'align_horiz' );
		switch( $align_horiz ) {
			case '':
			case 'content-horiz-left':
				$align_horiz = 'flex-start';
				break;
			case 'content-horiz-center':
				$align_horiz = 'center';
				break;
			case 'content-horiz-right':
				$align_horiz = 'flex-end';
				break;
		}
		$res_ctx->load_settings_raw( 'tdb_mts_align_horiz_' . $style_atts_uid, $align_horiz );

		// Image size
		$size = $res_ctx->get_shortcode_att( 'size' );
		$size = ( $size != '' && is_numeric( $size ) ) ? $size . 'px' : '20px';
		$res_ctx->load_settings_raw( 'tdb_mts_size_' . $style_atts_uid, $size );

		// Border size
		$all_border = $res_ctx->get_shortcode_att( 'all_border' );
		$all_border .= $all_border != '' && is_numeric( $all_border ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'tdb_mts_all_border_' . $style_atts_uid, $all_border );

		// Border style
		$all_border_style = $res_ctx->get_shortcode_att( 'all_border_style' );
		$all_border_style = !empty( $all_border_style ) ? $all_border_style : 'solid';
		$res_ctx->load_settings_raw( 'tdb_mts_all_border_style_' . $style_atts_uid, $all_border_style );

		// Border radius
		$radius = $res_ctx->get_shortcode_att( 'radius' );
		$radius = $radius != '' ? $radius : '100%';
		$radius .= is_numeric( $radius ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'tdb_mts_radius_' . $style_atts_uid, $radius );

		
		/* -- Colors -- */
		$all_border_color = $res_ctx->get_shortcode_att( 'all_border_color' );
		$all_border_color = !empty( $all_border_color ) ? $all_border_color : '#000';
		$res_ctx->load_settings_raw( 'tdb_mts_all_border_color_' . $style_atts_uid, $all_border_color );
		

	}


    function render( $atts, $content = null ) {

		$additional_classes_array = array();


		/* -- Call the parent render method -- */
        parent::render($atts);



		/* -- Block atts -- */
		// Photo sizes
		$image_sizes = $this->get_att('size');

		// Open link in new tab
		$open_in_new_tab = $this->get_att( 'open_in_new_tab' );
		$link_target = $open_in_new_tab != '' ? ' target="blank"' : '';



		/* -- Retrieve the module post data -- */
		$post_obj = self::$post_obj;

		$author_name = 'John Doe';
		$author_url = '#';
		$img_src_attr = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAJYAAACWCAIAAACzY+a1AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA3ZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDkuMC1jMDAxIDc5LjE0ZWNiNDJmMmMsIDIwMjMvMDEvMTMtMTI6MjU6NDQgICAgICAgICI+IDxyZGY6UkRGIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyI+IDxyZGY6RGVzY3JpcHRpb24gcmRmOmFib3V0PSIiIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIiB4bWxuczpzdFJlZj0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlUmVmIyIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bXBNTTpPcmlnaW5hbERvY3VtZW50SUQ9InhtcC5kaWQ6ZjRiNmU5OTgtYzYwOS0zNjQxLTkwMDEtMDE4ZjRlMTdjMGFkIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOkQ5NEIxNjAyQjY3NzExRURBQzhGRDRCQTQ4RDg2QkMyIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOkQ5NEIxNjAxQjY3NzExRURBQzhGRDRCQTQ4RDg2QkMyIiB4bXA6Q3JlYXRvclRvb2w9IkFkb2JlIFBob3Rvc2hvcCBDQyAyMDE0IChXaW5kb3dzKSI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOjA3MmJmMTgxLTIwZTgtNzg0NC04NzBjLTA3YmRjNjBjMDY1YyIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDo4ODJBQTk0RjJERUMxMUU0QTE5NzgyNkY3OUNBRUQyOCIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PmeVakwAAADzSURBVHja7NFBDQAACMQwwL/d+yODkHQS1k5S+txYgFAIhRChEAqhECIUQiEUQoRCKIRCiFAIhVAIEQqhEAohQiEUQiFEKIRCKIQIhVAIhRChEAqhECIUQiEUQoRCKIRCiFAIhVAIEQqhEAohQiEUQiFEKIRCKIQIhVAIhRChEAqhECIUQiEUQoRCKIRCiFAIhVAIEQqhEAohQiEUQiFEKIRCKIQIhVAIhRChEAqhECIUQiEUQoRCKIRCiFAIhVAIEQqhEAohQiEUQiFEKIRCKIQIhVAIEQqhEAohQiEUQiFEKIRCKIQIhVAIhRChEOqmFWAAxDwEC9ljc+gAAAAASUVORK5CYII=';
		$img_srcset_attr = '';
		$img_sizes_attr = '';
		$img_width_attr = '';
		$img_height_attr = '';

		if ( gettype($post_obj) === 'object' && get_class($post_obj) === 'WP_Post' ) {

			// Set the author name and url
			$author_name = get_the_author_meta('display_name', $post_obj->post_author);
			$author_url = get_author_posts_url($post_obj->post_author);


			// Check to see if we are dealing with a responsive value for the
			// image size; if so, then we need to create the 'srcset' and 'sizes'
			// img attributes to ensure the responsiveness
			if ( td_util::is_base64($image_sizes) ) {
				$image_sizes_decoded = json_decode( base64_decode( $image_sizes ) );

				// Define the media queries for each screen type
				$screens_media_queries = array(
					'phone' => '(max-width: 767px)',
					'portrait' => '(min-width: 768px) and (max-width: 1018px)',
					'landscape' => '(min-width: 1019px) and (max-width: 1140px)',
					'all' => '(min-width: 1019px)',
				);

				// If the images sizes do not include one for the desktop screen
				// then we must define a default one
				if( !property_exists($image_sizes_decoded, 'all') ) {
					$image_sizes_decoded->all = 20;
				}

				// Go through each image size screen and set the required img attributes
				foreach( $image_sizes_decoded as $screen => $image_size ) {
					// If the image size is not numeric, then just skip this one;
					// the 'size' parameter of the 'get_avatar_url' only accepts numeric values
					if( !is_numeric( $image_size ) ) {
						continue;
					}

					// Retrieve the image url for this screen type
					$image_url = get_avatar_url( $post_obj->post_author, array(
						'size' => $image_size
					) );

					// Set the 'scrset' and 'sizes' img attributes
					$img_srcset_attr .= ( !empty( $img_srcset_attr ) ? ', ' : '' ) . $image_url . ' ' . $image_size . 'w';
					$img_sizes_attr .= ( !empty( $img_sizes_attr ) ? ', ' : '' ) . $screens_media_queries[$screen] . ' ' . $image_size . 'px';

					// If the current screen is for desktop, then also set the
					// 'src', 'width' & 'height' img attributes
					if( $screen == 'all' ) {
						$img_src_attr = $image_url;
						$img_width_attr = $image_size;
						$img_height_attr = $image_size;
					}
				}
			} else {
				// The image size is not responsive, so just retrieve the image url
				// and fill in the required img attributes
				$image_sizes = ( !empty( $image_sizes ) && is_numeric( $image_sizes ) ) ? $image_sizes : 20;

				$img_src_attr = get_avatar_url( $post_obj->post_author, array(
					'size' => $image_sizes
				) );
				$img_width_attr = $image_sizes;
				$img_height_attr = $image_sizes;
			}

		}



		/* -- Output the module element HTML -- */
        $buffy = '';

		$buffy .= '<div class="' . $this->get_block_classes($additional_classes_array) . '" ' . $this->get_block_html_atts() . '>';
            // get the block css
            $buffy .= $this->get_block_css();

            // get the js for this block
            $buffy .= $this->get_block_js();

			$buffy .= '<a href="' . $author_url . '"' . $link_target . '>';
				$buffy .= '<img src="' . $img_src_attr . '" 
							title="' . $author_name . '"' . 
							( !empty( $img_width_attr ) ? 'width="' . $img_width_attr . '"' : '' ) .
							( !empty( $img_height_attr ) ? 'height="' . $img_height_attr . '"' : '' ) .
							( !empty( $img_srcset_attr ) ? ' srcset="' . $img_srcset_attr . '"' : '' ) . 
							( !empty( $img_sizes_attr ) ? ' sizes="' . $img_sizes_attr . '"' : '' ) .  
						'/>';
			$buffy .= '</a>';
		$buffy .= '</div>';


        return $buffy;

    }

}