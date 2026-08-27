<?php

/**
 * Class tdb_module_comments - shortcode for cloud template modules (renders post title)
 */
class tdb_module_comments extends tdb_module_template_part {

    public function get_custom_css() {

		$style_selector = self::$style_selector;
		$style_atts_uid = self::$style_atts_uid;
		

        $compiled_css = '';

        $raw_css = "<style>
		
			/* @style_general_tdb_module_comments */
			.tdb_module_comments {
				display: block;
				position: relative;
				margin: 0;
			}
			.tdb_module_comments a {
				display: inline-block;
				position: relative;
				min-width: 1.7em;
				padding: 3px 4px 4px 5px;
				background-color: #000;
				font-family: var(--td_default_google_font_1, 'Open Sans', 'Open Sans Regular', sans-serif);
				font-size: 10px;
				line-height: 1;
				text-align: center;
				color: #fff;
			}
			.tdb_module_comments a:after {
				content: '';
				position: absolute;
				top: 100%;
				left: 0;
				width: 0;
				height: 0;
				border-width: .3em .3em 0 0;
				border-style: solid;
				border-color: #000 transparent transparent transparent;
			}



			/* @tdb_mts_align_horiz_$style_atts_uid */
			.$style_selector {
				text-align: @tdb_mts_align_horiz_$style_atts_uid;
			}

			/* @tdb_mts_show_arrow_$style_atts_uid */
			.$style_selector a:after {
				display: @tdb_mts_show_arrow_$style_atts_uid;
			}



			/* @tdb_mts_bg_$style_atts_uid */
			.$style_selector a {
				background-color: @tdb_mts_bg_$style_atts_uid;
			}
			.$style_selector a:after {
				border-top-color: @tdb_mts_bg_$style_atts_uid;
			}
			/* @tdb_mts_bg_h_$style_atts_uid */
			.$style_selector a:hover {
				background-color: @tdb_mts_bg_h_$style_atts_uid;
			}
			.$style_selector a:hover:after {
				border-top-color: @tdb_mts_bg_h_$style_atts_uid;
			}
			/* @tdb_mts_color_$style_atts_uid */
			.$style_selector a {
				color: @tdb_mts_color_$style_atts_uid;
			}
			/* @tdb_mts_color_h_$style_atts_uid */
			.$style_selector a:hover {
				color: @tdb_mts_color_h_$style_atts_uid;
			}



			/* @tdb_mts_f_txt_$style_atts_uid */
			.$style_selector a {
				@tdb_mts_f_txt_$style_atts_uid
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
		$res_ctx->load_settings_raw( 'style_general_tdb_module_comments', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_module_comments_composer', 1 );
        }




		/* --
		-- AUTHOR NAME
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

		// Show bottom arrow
		$show_arrow = $res_ctx->get_shortcode_att( 'show_arrow' );
		$show_arrow = !empty( $show_arrow ) ? $show_arrow : 'block';
		$res_ctx->load_settings_raw( 'tdb_mts_show_arrow_' . $style_atts_uid, $show_arrow );


		/* -- Colors -- */
		$res_ctx->load_settings_raw( 'tdb_mts_bg_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'bg' ) );
		$res_ctx->load_settings_raw( 'tdb_mts_bg_h_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'bg_h' ) );
		$res_ctx->load_settings_raw( 'tdb_mts_color_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'color' ) );
		$res_ctx->load_settings_raw( 'tdb_mts_color_h_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'color_h' ) );


		/* -- Fonts -- */
		$res_ctx->load_font_settings( 'f_txt', '', 'tdb_mts_', '_' . $style_atts_uid );

	}


    function render( $atts, $content = null ) {

		$additional_classes_array = array();


		/* -- Call the parent render method -- */
        parent::render($atts);



		/* -- Block atts -- */
		// Open link in new tab
		$open_in_new_tab = $this->get_att( 'open_in_new_tab' );
		$link_target = $open_in_new_tab != '' ? ' target="blank"' : '';



		/* -- Retrieve the module post data -- */
		$post_obj = self::$post_obj;

		$comments_count = 3;
		$comments_url = '#';

		if ( gettype($post_obj) === 'object' && get_class($post_obj) === 'WP_Post' ) {

			$comments_count_dsq = td_util::get_dsq_comments_number( $post_obj );
			$comments_count = $comments_count_dsq ? $comments_count_dsq : get_comments_number( $post_obj->ID );
			$comments_url = get_comments_link( $post_obj->ID );

		}


		/* -- Output the module element HTML -- */
        $buffy = '';

		$buffy .= '<div class="' . $this->get_block_classes($additional_classes_array) . '" ' . $this->get_block_html_atts() . '>';
            // get the block css
            $buffy .= $this->get_block_css();

            // get the js for this block
            $buffy .= $this->get_block_js();

			$buffy .= '<a href="' . $comments_url . '"' . $link_target . '>' . $comments_count . '</a>';
		$buffy .= '</div>';


        return $buffy;

    }

}