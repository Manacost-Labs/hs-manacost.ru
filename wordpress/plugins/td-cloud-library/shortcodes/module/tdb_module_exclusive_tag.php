<?php

/**
 * Class tdb_module_exclusive_tag - shortcode for cloud template modules (renders post title)
 */
class tdb_module_exclusive_tag extends tdb_module_template_part {

    public function get_custom_css() {

		$style_selector = self::$style_selector;
		$style_atts_uid = self::$style_atts_uid;
		

        $compiled_css = '';

        $raw_css = "<style>
		
			/* @style_general_tdb_module_exclusive_tag */
			.tdb_module_exclusive_tag {
				display: flex;
				position: relative;
				margin: 0;
			}
			.tdb_module_exclusive_tag span {
			    transform: translateZ(0);
				background-color: #ff0000;
				padding: 4px 8px 2px;
				margin-right: 8px;
				font-family: var(--td_default_google_font_2, 'Roboto', sans-serif);
				font-size: 14px;
				font-weight: 500;
				line-height: 1;
				color: #fff;
			}



			/* @tdb_mts_align_horiz_$style_atts_uid */
			.$style_selector {
				justify-content: @tdb_mts_align_horiz_$style_atts_uid;
			}
			
			/* @tdb_mts_excl_padd_$style_atts_uid */
			.$style_selector span {
				padding: @tdb_mts_excl_padd_$style_atts_uid;
			}
			/* @tdb_mts_all_excl_border_$style_atts_uid */
			.$style_selector span {
				border: @tdb_mts_all_excl_border_$style_atts_uid @tdb_mts_all_excl_border_style_$style_atts_uid @tdb_mts_all_excl_border_color_$style_atts_uid;
			}
			/* @tdb_mts_excl_radius_$style_atts_uid */
			.$style_selector span {
				border-radius: @tdb_mts_excl_radius_$style_atts_uid;
			}



			/* @tdb_mts_excl_color_$style_atts_uid */
			.$style_selector span {
				color: @tdb_mts_excl_color_$style_atts_uid;
			}
			/* @tdb_mts_excl_bg_$style_atts_uid */
			.$style_selector span {
				background-color: @tdb_mts_excl_bg_$style_atts_uid;
			}



			/* @tdb_mts_f_excl_$style_atts_uid */
			.$style_selector span {
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
		$res_ctx->load_settings_raw( 'style_general_tdb_module_exclusive_tag', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_module_exclusive_tag_composer', 1 );
        }




		/* --
		-- Exclusive label
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
		$res_ctx->load_settings_raw( 'tdb_mts_excl_bg_' . $style_atts_uid, $res_ctx->get_shortcode_att('excl_bg') );

		$excl_border_color = $res_ctx->get_shortcode_att('all_excl_border_color');
		$excl_border_color = !empty( $excl_border_color ) ? $excl_border_color : '#000';
		$res_ctx->load_settings_raw( 'tdb_mts_all_excl_border_color_' . $style_atts_uid, $excl_border_color );


		/* -- Fonts -- */
		$res_ctx->load_font_settings( 'f_excl', '', 'tdb_mts_', '_' . $style_atts_uid );

	}


    function render( $atts, $content = null ) {

		$additional_classes_array = array();


		/* -- Call the parent render method -- */
        parent::render($atts);



		/* -- Block atts -- */
		$excl_txt = !empty( $this->get_att('excl_txt') ) ? $this->get_att('excl_txt') : 'EXCLUSIVE';


		/* -- Retrieve the module post data -- */
		$post_obj = self::$post_obj;

		$is_post_exclusive = true;

		if ( gettype($post_obj) === 'object' && get_class($post_obj) === 'WP_Post' ) {

			$post_obj_id = $post_obj->ID;

			$is_post_exclusive = td_util::is_post_exclusive($post_obj_id);

			// If the post is not exclusive, but we are in composer
			// and currently editing the actual module template, then set
			// the post as exclusive
			if( 
				!$is_post_exclusive &&
				( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) &&
				tdb_state_template::get_template_type() == 'module'
			) {
				$is_post_exclusive = true;
			}

		}



		/* -- Output the module element HTML -- */
        $buffy = '';

		if( !$is_post_exclusive ) {
			return $buffy;
		}

		$buffy .= '<div class="' . $this->get_block_classes($additional_classes_array) . '" ' . $this->get_block_html_atts() . '>';
            // get the block css
            $buffy .= $this->get_block_css();

            // get the js for this block
            $buffy .= $this->get_block_js();

			$buffy .= '<span>' . $excl_txt . '</span>';
		$buffy .= '</div>';

        return $buffy;
    }

}