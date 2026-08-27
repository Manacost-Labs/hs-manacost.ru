<?php

/**
 * Class tdb_module_title - shortcode for cloud template modules (renders post title)
 */
class tdb_module_title extends tdb_module_template_part {

    public function get_custom_css() {

		$style_selector = self::$style_selector;
		$style_atts_uid = self::$style_atts_uid;
		

        $compiled_css = '';

        $raw_css = "<style>
		
			/* @style_general_tdb_module_title */
			.tdb_module_title {
				display: block;
				position: relative;
				margin: 0;
				font-family: var(--td_default_google_font_2, 'Roboto', sans-serif);
				font-size: 21px;
				font-weight: 400;
				line-height: 1.2;
			}
			.tdb_module_title a {
			    transform: translateZ(0);
				transition: box-shadow 0.2s ease;
				-webkit-transition: box-shadow 0.2s ease;
			}
			.tdb_module_title .tdb-module-title-excl {
				font-family: var(--td_default_google_font_2, 'Roboto', sans-serif);
				color: #fff;
				background-color: #ff0000;
				padding: 4px 8px 2px;
				margin-right: 8px;
				font-size: 14px;
				font-weight: 500;
				line-height: 1;
				vertical-align: middle;
			}
			.tdb_module_title .td-element-style {
			    z-index: -1;
			}



			/* @tdb_mts_align_horiz_$style_atts_uid */
			.$style_selector {
				text-align: @tdb_mts_align_horiz_$style_atts_uid;
			}


			/* @tdb_mts_all_underline_$style_atts_uid */
			.$style_selector a {
				box-shadow: inset 0 -@tdb_mts_all_underline_$style_atts_uid 0 0 @tdb_mts_all_underline_color_$style_atts_uid;
			}
			/* @tdb_mts_all_underline_h_$style_atts_uid */
			.$style_selector:hover a {
				box-shadow: inset 0 -@tdb_mts_all_underline_h_$style_atts_uid 0 0 @tdb_mts_all_underline_color_h_$style_atts_uid;
			}



			/* @tdb_mts_color_$style_atts_uid */
			.$style_selector a {
				color: @tdb_mts_color_$style_atts_uid;
			}
			/* @tdb_mts_color_h_$style_atts_uid */
			.$style_selector:hover a {
				color: @tdb_mts_color_h_$style_atts_uid;
			}



			/* @tdb_mts_f_txt_$style_atts_uid */
			.$style_selector {
				@tdb_mts_f_txt_$style_atts_uid
			}
                
                
			/* @tdb_mts_excl_show_$style_atts_uid */
			.$style_selector .tdb-module-title-excl {
				display: @tdb_mts_excl_show_$style_atts_uid;
			}
			/* @tdb_mts_excl_txt_$style_atts_uid */
			.$style_selector .tdb-module-title-excl {
				content: '@tdb_mts_excl_txt_$style_atts_uid';
			}
			/* @tdb_mts_excl_margin_$style_atts_uid */
			.$style_selector .tdb-module-title-excl {
				margin: @tdb_mts_excl_margin_$style_atts_uid;
			}
			/* @tdb_mts_excl_padd_$style_atts_uid */
			.$style_selector .tdb-module-title-excl {
				padding: @tdb_mts_excl_padd_$style_atts_uid;
			}
			/* @tdb_mts_all_excl_border_$style_atts_uid */
			.$style_selector .tdb-module-title-excl {
				border: @tdb_mts_all_excl_border_$style_atts_uid @tdb_mts_all_excl_border_style_$style_atts_uid @tdb_mts_all_excl_border_color_$style_atts_uid;
			}
			/* @tdb_mts_excl_radius_$style_atts_uid */
			.$style_selector .tdb-module-title-excl {
				border-radius: @tdb_mts_excl_radius_$style_atts_uid;
			}
			/* @tdb_mts_excl_color_$style_atts_uid */
			.$style_selector .tdb-module-title-excl {
				color: @tdb_mts_excl_color_$style_atts_uid;
			}
			/* @tdb_mts_excl_color_h_$style_atts_uid */
			.$style_selector .tdb-module-title-excl {
				color: @tdb_mts_excl_color_h_$style_atts_uid;
			}
			/* @tdb_mts_excl_bg_$style_atts_uid */
			.$style_selector .tdb-module-title-excl {
				background-color: @tdb_mts_excl_bg_$style_atts_uid;
			}
			/* @tdb_mts_excl_bg_h_$style_atts_uid */
			.$style_selector .tdb-module-title-excl {
				background-color: @tdb_mts_excl_bg_h_$style_atts_uid;
			}
			/* @tdb_mts_excl_border_color_h_$style_atts_uid */
			.$style_selector .tdb-module-title-excl {
				border-color: @tdb_mts_excl_border_color_h_$style_atts_uid;
			}
			/* @tdb_mts_f_excl_$style_atts_uid */
			.$style_selector .tdb-module-title-excl {
				@tdb_mts_f_excl_$style_atts_uid
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
		$res_ctx->load_settings_raw( 'style_general_tdb_module_title', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_module_title_composer', 1 );
        }




		/* --
		-- TITLE
		-- */
		/* -- Layout -- */
		// Horizontal align
		$align_horiz = $res_ctx->get_shortcode_att( 'align_horiz' );
		switch( $align_horiz ) {
			case '':
			case 'content-horiz-left':
				$align_horiz = 'left';
				break;
			case 'content-horiz-center':
				$align_horiz = 'center';
				break;
			case 'content-horiz-right':
				$align_horiz = 'right';
				break;
		}
		$res_ctx->load_settings_raw( 'tdb_mts_align_horiz_' . $style_atts_uid, $align_horiz );

		// Underline height
		$all_underline = $res_ctx->get_shortcode_att( 'all_underline' );
		$all_underline .= ( $all_underline != '' && is_numeric( $all_underline ) ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'tdb_mts_all_underline_' . $style_atts_uid, $all_underline );

		// Underiline hover height
		$all_underline_h = $res_ctx->get_shortcode_att( 'all_underline_h' );
		$all_underline_h = !empty( $all_underline_h ) ? $all_underline_h : $all_underline;
		$all_underline_h .= ( $all_underline_h != '' && is_numeric( $all_underline_h ) ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'tdb_mts_all_underline_h_' . $style_atts_uid, $all_underline_h );


		/* -- Colors -- */
		$res_ctx->load_settings_raw( 'tdb_mts_color_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'color' ) );
		$res_ctx->load_settings_raw( 'tdb_mts_color_h_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'color_h' ) );

		$all_underline_color = $res_ctx->get_shortcode_att( 'all_underline_color' );
		$all_underline_color = !empty( $all_underline_color ) ? $all_underline_color : '#000';
		$res_ctx->load_settings_raw( 'tdb_mts_all_underline_color_' . $style_atts_uid, $all_underline_color );

		$all_underline_color_h = $res_ctx->get_shortcode_att( 'all_underline_color_h' );
		$all_underline_color_h = !empty( $all_underline_color_h ) ? $all_underline_color_h : '#000';
		$res_ctx->load_settings_raw( 'tdb_mts_all_underline_color_h_' . $style_atts_uid, $all_underline_color_h );


		/* -- Fonts -- */
		$res_ctx->load_font_settings( 'f_txt', '', 'tdb_mts_', '_' . $style_atts_uid );




		/* --
		-- Exclusive label
		-- */
		if( is_plugin_active('td-subscription/td-subscription.php') && !empty( has_filter('td_composer_map_exclusive_label_array', 'td_subscription::add_exclusive_label_settings') ) ) {
			/* -- Layout -- */
            // show exclusive label
            $excl_show = $res_ctx->get_shortcode_att('excl_show');
			$excl_show = !empty( $excl_show ) ? $excl_show : 'inline-block';
            $res_ctx->load_settings_raw( 'tdb_mts_excl_show_' . $style_atts_uid, $excl_show );

            // exclusive label text
            $res_ctx->load_settings_raw( 'tdb_mts_excl_txt_' . $style_atts_uid, $res_ctx->get_shortcode_att('excl_txt') );

            // exclusive label margin
            $excl_margin = $res_ctx->get_shortcode_att('excl_margin');
			$excl_margin .= !empty( $excl_margin ) && is_numeric( $excl_margin ) ? 'px' : '';
            $res_ctx->load_settings_raw( 'tdb_mts_excl_margin_' . $style_atts_uid, $excl_margin );

            // exclusive label padding
            $excl_padd = $res_ctx->get_shortcode_att('excl_padd');
			$excl_padd .= !empty( $excl_padd ) && is_numeric( $excl_padd ) ? 'px' : '';
            $res_ctx->load_settings_raw( 'tdb_mts_excl_padd_' . $style_atts_uid, $excl_padd );

            // exclusive label border size
            $excl_border = $res_ctx->get_shortcode_att('all_excl_border');
			$excl_border .= !empty( $excl_border ) && is_numeric( $excl_border ) ? 'px' : '';
            $res_ctx->load_settings_raw( 'tdb_mts_all_excl_border_' . $style_atts_uid, $excl_border );

            // exclusive label border style
            $res_ctx->load_settings_raw( 'tdb_mts_all_excl_border_style_' . $style_atts_uid, $res_ctx->get_shortcode_att('all_excl_border_style') );

            // exclusive label border radius
            $excl_radius = $res_ctx->get_shortcode_att('excl_radius');
			$excl_radius .= !empty( $excl_radius ) && is_numeric( $excl_radius ) ? 'px' : '';
            $res_ctx->load_settings_raw( 'tdb_mts_excl_radius_' . $style_atts_uid, $excl_radius );


			/* -- Colors -- */
            $res_ctx->load_settings_raw( 'tdb_mts_excl_color_' . $style_atts_uid, $res_ctx->get_shortcode_att('excl_color') );
            $res_ctx->load_settings_raw( 'tdb_mts_excl_color_h_' . $style_atts_uid, $res_ctx->get_shortcode_att('excl_color_h') );
            $res_ctx->load_settings_raw( 'tdb_mts_excl_bg_' . $style_atts_uid, $res_ctx->get_shortcode_att('excl_bg') );
            $res_ctx->load_settings_raw( 'tdb_mts_excl_bg_h_' . $style_atts_uid, $res_ctx->get_shortcode_att('excl_bg_h') );

            $excl_border_color = $res_ctx->get_shortcode_att('all_excl_border_color');
			$excl_border_color = !empty( $excl_border_color ) ? $excl_border_color : '#000';
            $res_ctx->load_settings_raw( 'tdb_mts_all_excl_border_color_' . $style_atts_uid, $excl_border_color );
			
            $res_ctx->load_settings_raw( 'etdb_mts_xcl_border_color_h_' . $style_atts_uid, $res_ctx->get_shortcode_att('excl_border_color_h') );


			/* -- Fonts -- */
            $res_ctx->load_font_settings( 'f_excl', '', 'tdb_mts_', '_' . $style_atts_uid );
        }

	}


    function render( $atts, $content = null ) {

		$additional_classes_array = array();


		/* -- Call the parent render method -- */
        parent::render($atts);



		/* -- Retrieve the module post data -- */
		$post_obj = self::$post_obj;

		$post_title = 'Sample post title';
		$post_link = '#';
		$post_title_attribute = 'sample post title attribute';

		$excl_label = '';

		if ( gettype($post_obj) === 'object' && get_class($post_obj) === 'WP_Post' ) {

			$post_obj_id = $post_obj->ID;

			$post_title = get_the_title( $post_obj_id );
			$post_link = esc_url( get_permalink( $post_obj_id ) );
			$post_title_attribute = esc_attr( strip_tags( get_the_title( $post_obj_id ) ) );

			if( td_util::is_post_exclusive($post_obj_id) ) {
				$excl_label_txt = !empty( $this->get_att('excl_txt') ) ? $this->get_att('excl_txt') : 'EXCLUSIVE';
	
				$excl_label = '<span class="tdb-module-title-excl">' . $excl_label_txt . '</span>';
			}

		}



		/* -- Block atts -- */
		// Tag
	    $title_tag = !empty( $this->get_att( 'title_tag' ) ) ? $this->get_att( 'title_tag' ) : 'h3';

		// Length
	    $title_length = $this->get_att('title_length') != '' ? $this->get_att('title_length') : 12;

		// Open link in new tab
		$open_in_new_tab = $this->get_att( 'open_in_new_tab' );
		$link_target = $open_in_new_tab != '' ? ' target="blank"' : '';



		/* -- Output the module element HTML -- */
        $buffy = '';

		$buffy .= '<' . $title_tag . '  class="' . $this->get_block_classes($additional_classes_array) . '" ' . $this->get_block_html_atts() . '>';
            // get the block css
            $buffy .= $this->get_block_css();

            // get the js for this block
            $buffy .= $this->get_block_js();

			$buffy .= '<a href="' . $post_link . '" title="' . $post_title_attribute . '"' . $link_target . '>';

				$buffy .= $excl_label;

				if ( !empty( $title_length ) ) {
					$buffy .= td_util::excerpt( $post_title, $title_length, 'show_shortcodes' );
				} else {
					$buffy .= $post_title;
				}

			$buffy .= '</a>';
		$buffy .= '</' . $title_tag . '>';

        return $buffy;
    }

}