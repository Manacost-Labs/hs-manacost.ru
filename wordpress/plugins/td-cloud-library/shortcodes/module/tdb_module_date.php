<?php

/**
 * Class tdb_module_date - shortcode for cloud template modules (renders post title)
 */
class tdb_module_date extends tdb_module_template_part {

    public function get_custom_css() {

		$style_selector = self::$style_selector;
		$style_atts_uid = self::$style_atts_uid;
		

        $compiled_css = '';

        $raw_css = "<style>
		
			/* @style_general_tdb_module_date */
			.tdb_module_date {
				display: block;
				position: relative;
				margin: 0;
				font-family: var(--td_default_google_font_1, 'Open Sans', 'Open Sans Regular', sans-serif);
				font-size: 11px;
				line-height: 1.2;
				color: #767676;
			}
			.tdb_module_date .td-element-style {
			    z-index: -1;
			}



			/* @tdb_mts_align_horiz_$style_atts_uid */
			.$style_selector {
				text-align: @tdb_mts_align_horiz_$style_atts_uid;
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
		$res_ctx->load_settings_raw( 'style_general_tdb_module_date', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_module_date_composer', 1 );
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
		// Date type
		$date_type = $this->get_att('date_type') != '' ? $this->get_att('date_type') : 'published';

		// Date format
		$date_format_type = $this->get_att('format') != '' ? $this->get_att('format') : 'wordpress';
		$date_format = get_option('date_format');

		if( $date_format_type == 'custom' ) {
			$date_format = $this->get_att('custom_format') != '' ? $this->get_att('custom_format') : 'F j, Y';
		} else if( $date_format_type == 'time_ago' ) {
            $date_format = 'U';
        }

        $time_ago_add_txt = $this->get_att('time_ago_add_txt');
        $time_ago_txt_pos = $this->get_att('time_ago_txt_pos');


		/* -- Retrieve the module post data -- */
		$post_obj = self::$post_obj;

		$display_date = date($date_format, time());

		if ( gettype($post_obj) === 'object' && get_class($post_obj) === 'WP_Post' ) {

			if ( $date_type == 'published' ) {
				$display_date = get_the_time($date_format, $post_obj->ID);
			} elseif ( $date_type == 'last_modified' ) {
				$display_date = get_the_modified_date($date_format, $post_obj->ID);
			}

		}

		// If selected, apply time ago formatting
		if( $date_format_type == 'time_ago' ) {
            if ( $time_ago_txt_pos == 'yes' ) {
                $display_date = $time_ago_add_txt . ' ' . human_time_diff( $display_date );
            }  else {
                $display_date = human_time_diff( $display_date ) . ' ' . $time_ago_add_txt;
            }
		}



		/* -- Output the module element HTML -- */
        $buffy = '';

		$buffy .= '<time class="' . $this->get_block_classes($additional_classes_array) . '" ' . $this->get_block_html_atts() . '>';
            // get the block css
            $buffy .= $this->get_block_css();

            // get the js for this block
            $buffy .= $this->get_block_js();

			$buffy .= $display_date;
		$buffy .= '</time>';


        return $buffy;

    }

}