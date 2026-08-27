<?php

/**
 * Class tdb_module_excerpt - shortcode for cloud template modules (renders post title)
 */
class tdb_module_excerpt extends tdb_module_template_part {

    public function get_custom_css() {

		$style_selector = self::$style_selector;
		$style_atts_uid = self::$style_atts_uid;
		

        $compiled_css = '';

        $raw_css = "<style>
		
			/* @style_general_tdb_module_excerpt */
			.tdb_module_excerpt {
				display: block;
				position: relative;
				margin: 0;
				font-family: var(--td_default_google_font_2, 'Roboto', sans-serif);
				font-size: 14px;
				font-weight: 400;
				line-height: 1.5;
                word-wrap: break-word;
                overflow-wrap: anywhere;
				color: #767676;
			}
			.tdb_module_excerpt .td-element-style {
			    z-index: -1;
			}
			.tdb_module_excerpt p:last-child {
				margin-bottom: 0;
			}



			/* @tdb_mts_align_horiz_$style_atts_uid */
			.$style_selector {
				text-align: @tdb_mts_align_horiz_$style_atts_uid;
			}

			/* @tdb_mts_excerpt_col_$style_atts_uid */
			.$style_selector {
				column-count: @tdb_mts_excerpt_col_$style_atts_uid;
			}
			/* @tdb_mts_excerpt_gap_$style_atts_uid */
			.$style_selector {
				column-gap: @tdb_mts_excerpt_gap_$style_atts_uid;
			}



			/* @tdb_mts_color_$style_atts_uid */
			.$style_selector {
				color: @tdb_mts_color_$style_atts_uid;
			}



			/* @tdb_mts_f_txt_$style_atts_uid */
			.$style_selector {
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
		$res_ctx->load_settings_raw( 'style_general_tdb_module_excerpt', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_module_excerpt_composer', 1 );
        }




		/* --
		-- EXCERPT
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

		// Columns
		$excerpt_col = $res_ctx->get_shortcode_att( 'excerpt_col' );
		$excerpt_col = !empty( $excerpt_col ) ? $excerpt_col : '1';
		$res_ctx->load_settings_raw( 'tdb_mts_excerpt_col_' . $style_atts_uid, $excerpt_col );

		// Columns gap
		$excerpt_gap = $res_ctx->get_shortcode_att( 'excerpt_gap' );
		$excerpt_gap = $excerpt_gap != '' ? $excerpt_gap : '48px';
		$excerpt_gap .= is_numeric( $excerpt_gap ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'tdb_mts_excerpt_gap_' . $style_atts_uid, $excerpt_gap );


		/* -- Colors -- */
		$res_ctx->load_settings_raw( 'tdb_mts_color_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'color' ) );


		/* -- Fonts -- */
		$res_ctx->load_font_settings( 'f_txt', '', 'tdb_mts_', '_' . $style_atts_uid );

	}


    function render( $atts, $content = null ) {

		$additional_classes_array = array();

		/* -- Call the parent render method -- */
        parent::render($atts);


		/* -- Block atts -- */
		// Tag
		$excerpt_tag = $this->get_att('excerpt_tag') != '' ? $this->get_att('excerpt_tag') : 'div';

		// Length
	    $excerpt_length = $this->get_att('excerpt_length') != '' ? $this->get_att('excerpt_length') : 25;

		/* -- Retrieve the module post data -- */
		$post_obj = self::$post_obj;

		$post_excerpt = $post_excerpt_sample = 'Sample module excerpt. Interdum et malesuada fames ac ante ipsum primis in faucibus. Morbi aliquam, tortor hendrerit ultrices fringilla, sapien lorem malesuada ante, placerat aliquet dui sem ac justo.';
        $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();
		if ( gettype($post_obj) === 'object' && get_class($post_obj) === 'WP_Post' ) {

			// If the user has set a custom excerpt, return that,
            // otherwise get the post content
			if( $post_obj->post_excerpt != '' ) {
				$post_excerpt = $post_obj->post_excerpt;
			} else {
				$post_excerpt = $post_obj->post_content;
			}

			// Cut the excerpt at the length provided by the user
			if ( !empty($excerpt_length) ) {
				$post_excerpt = td_util::excerpt( $post_excerpt, $excerpt_length );
			}

		}

        // in composer return if no excerpt
        if ( empty($post_excerpt) && $in_composer ) {
            $post_excerpt = $post_excerpt_sample;
        }

		/* -- Output the module element HTML -- */
        $buffy = '';

		$buffy .= '<' . $excerpt_tag . '  class="' . $this->get_block_classes($additional_classes_array) . '" ' . $this->get_block_html_atts() . '>';
            // get the block css
            $buffy .= $this->get_block_css();

            // get the js for this block
            $buffy .= $this->get_block_js();

			$buffy .= $post_excerpt;
		$buffy .= '</' . $excerpt_tag . '>';


        return $buffy;

    }

}