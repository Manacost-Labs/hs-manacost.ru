<?php

/**
 * Class tdb_module_author_name - shortcode for cloud template modules (renders post title)
 */
class tdb_module_author_name extends tdb_module_template_part {

    public function get_custom_css() {

		$style_selector = self::$style_selector;
		$style_atts_uid = self::$style_atts_uid;
		

        $compiled_css = '';

        $raw_css = "<style>
		
			/* @style_general_tdb_module_author_name */
			.tdb_module_author_name {
				display: flex;
				position: relative;
				margin: 0;
			}
			.tdb_module_author_name a {
				transform: translateZ(0);
				font-family: var(--td_default_google_font_1, 'Open Sans', 'Open Sans Regular', sans-serif);
				font-size: 11px;
				font-weight: 700;
				line-height: 1.2;
				color: #000;
			}



			/* @tdb_mts_align_horiz_$style_atts_uid */
			.$style_selector {
				justify-content: @tdb_mts_align_horiz_$style_atts_uid;
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
		$res_ctx->load_settings_raw( 'style_general_tdb_module_author_name', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_module_author_name_composer', 1 );
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


		/* -- Colors -- */
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

		$author_name = 'John Doe';
		$author_url = '#';

		if ( gettype($post_obj) === 'object' && get_class($post_obj) === 'WP_Post' ) {

			$author_name = get_the_author_meta('display_name', $post_obj->post_author);
			$author_url = get_author_posts_url($post_obj->post_author);

		}



		/* -- Output the module element HTML -- */
        $buffy = '';

		$buffy .= '<div class="' . $this->get_block_classes($additional_classes_array) . '" ' . $this->get_block_html_atts() . '>';
            // get the block css
            $buffy .= $this->get_block_css();

            // get the js for this block
            $buffy .= $this->get_block_js();

			$buffy .= '<a href="' . $author_url . '"' . $link_target . '>';
				$buffy .= $author_name;
			$buffy .= '</a>';
		$buffy .= '</div>';


        return $buffy;

    }

}