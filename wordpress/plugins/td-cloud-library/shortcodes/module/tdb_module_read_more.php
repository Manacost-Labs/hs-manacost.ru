<?php

/**
 * Class tdb_module_read_more - shortcode for cloud template modules (renders post title)
 */
class tdb_module_read_more extends tdb_module_template_part {

    public function get_custom_css() {

		$style_selector = self::$style_selector;
		$style_atts_uid = self::$style_atts_uid;
		

        $compiled_css = '';

        $raw_css = "<style>
		
			/* @style_general_tdb_module_read_more */
			.tdb_module_read_more {
				display: flex;
				position: relative;
				margin: 0;
			}
			.tdb_module_read_more a {
			    transform: translateZ(0);
				background-color: var(--td_theme_color, #4db2ec);
				padding: 10px 15px;
				font-size: 13px;
				font-weight: 500;
				line-height: 1;
				color: #fff;
				-webkit-transition: all 0.4s;
				transition: all 0.4s;
			}
			.tdb_module_read_more a:hover {
				background-color: #222;
			}
			.tdb_module_read_more .tdb-mrm-icon {
				font-size: 13px;
				color: #fff;
				display: inline-flex;
                align-items: center;
                justify-content: center;
                pointer-events: none;
                vertical-align: middle;
                line-height: 1;
                
			}
			.tdb_module_read_more .tdb-mrm-icon svg {
				display: block;
				width: 1em;
				height: auto;
			}
			.tdb_module_read_more .tdb-mrm-icon svg,
			.tdb_module_read_more .tdb-mrm-icon svg * {
				fill: currentColor;
			}



			/* @tdb_mts_align_horiz_$style_atts_uid */
			.$style_selector {
				justify-content: @tdb_mts_align_horiz_$style_atts_uid;
			}


			/* @tdb_mts_padding_$style_atts_uid */
			.$style_selector a {
				padding: @tdb_mts_padding_$style_atts_uid;
			}

			/* @tdb_mts_bg_$style_atts_uid */
			.$style_selector a {
				background-color: @tdb_mts_bg_$style_atts_uid;
			}
			/* @tdb_mts_bg_h_$style_atts_uid */
			.$style_selector a:hover {
				background-color: @tdb_mts_bg_h_$style_atts_uid;
			}
			/* @tdb_mts_color_$style_atts_uid */
			.$style_selector a,
			.$style_selector .tdb-mrm-icon {
				color: @tdb_mts_color_$style_atts_uid;
			}
			/* @tdb_mts_color_h_$style_atts_uid */
			.$style_selector a:hover,
			.$style_selector a:hover .tdb-mrm-icon {
				color: @tdb_mts_color_h_$style_atts_uid;
			}
			/* @tdb_mts_shadow_$style_atts_uid */
			.$style_selector a {
				box-shadow: @tdb_mts_shadow_$style_atts_uid;
			}
			/* @tdb_mts_shadow_h_$style_atts_uid */
			.$style_selector a:hover {
				box-shadow: @tdb_mts_shadow_h_$style_atts_uid;
			}

			/* @tdb_mts_f_txt_$style_atts_uid */
			.$style_selector a {
				@tdb_mts_f_txt_$style_atts_uid
			}


			/* @tdb_mts_all_border_$style_atts_uid */
			.$style_selector a {
				border: @tdb_mts_all_border_$style_atts_uid @tdb_mts_all_border_style_$style_atts_uid @tdb_mts_all_all_border_color_$style_atts_uid;
			}
			
			/* @tdb_mts_all_border_radius_$style_atts_uid */
			.$style_selector a {
				border-radius: @tdb_mts_all_border_radius_$style_atts_uid;
			}

			/* @tdb_mts_border_color_h_$style_atts_uid */
			.$style_selector a:hover {
				border-color: @tdb_mts_border_color_h_$style_atts_uid;
			}
			
			/* @tdb_mts_ico_size_$style_atts_uid */
			.$style_selector .tdb-mrm-icon {
				font-size: @tdb_mts_ico_size_$style_atts_uid;
			}
			.$style_selector .tdb-mrm-icon svg{
				width: @tdb_mts_ico_size_$style_atts_uid;
                height: auto;
			}
			/* @tdb_mts_ico_align_$style_atts_uid */
			.$style_selector .tdb-mrm-icon {
				position: relative;
				top: @tdb_mts_ico_align_$style_atts_uid;
			}
			/* @tdb_mts_ico_space_$style_atts_uid */
			.$style_selector .tdb-mrm-icon {
				@tdb_mts_ico_space_$style_atts_uid
			}
			/* @tdb_mts_ico_color_$style_atts_uid */
			.$style_selector .tdb-mrm-icon {
				color: @tdb_mts_ico_color_$style_atts_uid;
			}
			/* @tdb_mts_ico_color_h_$style_atts_uid */
			.$style_selector a:hover .tdb-mrm-icon {
				color: @tdb_mts_ico_color_h_$style_atts_uid;
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
		$res_ctx->load_settings_raw( 'style_general_tdb_module_read_more', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_module_read_more_composer', 1 );
        }




		/* --
		-- BUTTON
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
		
		// Padding
		$padding = $res_ctx->get_shortcode_att( 'padding' );
		$padding .= ( $padding != '' && is_numeric( $padding ) ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'tdb_mts_padding_' . $style_atts_uid, $padding );


		/* -- Colors -- */
		$res_ctx->load_settings_raw( 'tdb_mts_bg_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'bg' ) );
		$res_ctx->load_settings_raw( 'tdb_mts_bg_h_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'bg_h' ) );
		$res_ctx->load_settings_raw( 'tdb_mts_color_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'color' ) );
		$res_ctx->load_settings_raw( 'tdb_mts_color_h_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'color_h' ) );

		$res_ctx->load_shadow_settings( 0, 0, 0, 0, '#000', 'shadow', '', false, 'tdb_mts_', '_' . $style_atts_uid );
		$res_ctx->load_shadow_settings( 0, 0, 0, 0, '#000', 'shadow_h', '', false, 'tdb_mts_', '_' . $style_atts_uid );


		/* -- Fonts -- */
		$res_ctx->load_font_settings( 'f_txt', '', 'tdb_mts_', '_' . $style_atts_uid );




		/* --
		-- BORDER
		-- */
		/* -- Layout -- */
		// Size
		$all_border = $res_ctx->get_shortcode_att( 'all_border' );
		$all_border .= ( $all_border != '' && is_numeric( $all_border ) ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'tdb_mts_all_border_' . $style_atts_uid, $all_border );

		// Style
		$all_border_style = $res_ctx->get_shortcode_att( 'all_border_style' );
		$all_border_style = $all_border_style != '' ? $all_border_style : 'solid';
		$res_ctx->load_settings_raw( 'tdb_mts_all_border_style_' . $style_atts_uid, $all_border_style );

        // Radius
        $all_border_radius = $res_ctx->get_shortcode_att( 'all_border_radius' );
        $all_border_radius .= ( $all_border_radius != '' && is_numeric( $all_border_radius ) ) ? 'px' : '';
        $res_ctx->load_settings_raw( 'tdb_mts_all_border_radius_' . $style_atts_uid, $all_border_radius );

		/* -- Colors -- */
		$all_border_color = $res_ctx->get_shortcode_att( 'all_border_color' );
		$all_border_style = $all_border_style != '' ? $all_border_style : '#000';
		$res_ctx->load_settings_raw( 'tdb_mts_all_all_border_color_' . $style_atts_uid, $all_border_color );
		$res_ctx->load_settings_raw( 'tdb_mts_border_color_h_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'border_color_h' ) );




		/* --
		-- ICON
		-- */
		/* -- Layout -- */
		// Size
		$ico_size = $res_ctx->get_shortcode_att( 'ico_size' );
		$ico_size .= ( $ico_size != '' && is_numeric( $ico_size ) ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'tdb_mts_ico_size_' . $style_atts_uid, $ico_size );

		// Vertical align
		$res_ctx->load_settings_raw( 'tdb_mts_ico_align_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'ico_align' ) . 'px' );

		// Space
		$ico_pos = $res_ctx->get_shortcode_att( 'ico_pos' ) != '' ? $res_ctx->get_shortcode_att( 'ico_pos' ) : 'before';
		$ico_space = $res_ctx->get_shortcode_att( 'ico_space' );
		$ico_space = $ico_space != '' ? $ico_space : '8px';
		$ico_space .= ( $ico_space != '' && is_numeric( $ico_space ) ) ? 'px' : '';
		if( $ico_pos == 'before' ) {
			$res_ctx->load_settings_raw( 'tdb_mts_ico_space_' . $style_atts_uid, 'margin: 0 ' . $ico_space . ' 0 0;' );
		} else {
			$res_ctx->load_settings_raw( 'tdb_mts_ico_space_' . $style_atts_uid, 'margin: 0 0 0 ' . $ico_space . ';' );
		}


		/* -- Colors -- */
		$res_ctx->load_settings_raw( 'tdb_mts_ico_color_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'ico_color' ) );
		$res_ctx->load_settings_raw( 'tdb_mts_ico_color_h_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'ico_color_h' ) );

	}


    function render( $atts, $content = null ) {

		$additional_classes_array = array();


		/* -- Call the parent render method -- */
        parent::render($atts);



		/* -- Block atts -- */
		// Button text
		$btn_txt = $this->get_att( 'text' ) != '' ? td_util::get_custom_field_value_from_string($this->get_att( 'text' )) : 'Read more';

		// Open link in new tab
		$open_in_new_tab = $this->get_att( 'open_in_new_tab' );
		$link_target = $open_in_new_tab != '' ? ' target="blank"' : '';

		// Icon
		$icon = $this->get_icon_att( 'tdicon' );
        $icon_html = '';
        if ( $icon != '' ) {
            if( base64_encode( base64_decode( $icon ) ) == $icon ) {
				$icon_data = '';
				if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
					$icon_data = 'data-td-svg-icon="' . $this->get_att('tdicon') . '"';
				}

                $icon_html = '<span class="tdb-mrm-icon" ' . $icon_data . '>' . base64_decode( $icon ) . '</span>';
            } else {
                $icon_html = '<i class="tdb-mrm-icon ' . $icon . '"></i>';
            }
        }

		// Icon position
		$icon_position = $this->get_att( 'ico_pos' ) != '' ? $this->get_att( 'ico_pos' ) : 'before';


		/* -- Retrieve the module post data -- */
		$post_obj = self::$post_obj;

		$post_link = '#';

		if ( gettype($post_obj) === 'object' && get_class($post_obj) === 'WP_Post' ) {

			$post_obj_id = $post_obj->ID;

			$post_link = esc_url( get_permalink( $post_obj_id ) );

		}



		/* -- Output the module element HTML -- */
        $buffy = '';


		$buffy .= '<div class="' . $this->get_block_classes($additional_classes_array) . '" ' . $this->get_block_html_atts() . '>';
            // get the block css
            $buffy .= $this->get_block_css();

            // get the js for this block
            $buffy .= $this->get_block_js();

			$buffy .= '<a href="' . $post_link . '"' . $link_target . ' title="' . $btn_txt . '">';
				if( $icon_position == 'before' ) {
					$buffy .= $icon_html;
				}

				$buffy .= $btn_txt;

				if( $icon_position == 'after' ) {
					$buffy .= $icon_html;
				}
			$buffy .= '</a>';
		$buffy .= '</div>';


        return $buffy;

    }

}