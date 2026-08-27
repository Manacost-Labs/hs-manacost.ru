<?php

/**
 * Class tdb_module_views - shortcode for cloud template modules (renders post title)
 */
class tdb_module_views extends tdb_module_template_part {

    public function get_custom_css() {

		$style_selector = self::$style_selector;
		$style_atts_uid = self::$style_atts_uid;
		

        $compiled_css = '';

        $raw_css = "<style>
		
			/* @style_general_tdb_module_views */
			.tdb_module_views {
				display: block;
				position: relative;
				margin: 0;
				font-family: var(--td_default_google_font_1, 'Open Sans', 'Open Sans Regular', sans-serif);
				font-size: 11px;
				line-height: 1.2;
				color: #767676;
			}
			.tdb_module_views .td-element-style {
			    z-index: -1;
			}


			/* @tdb_mts_align_horiz_$style_atts_uid */
			.$style_selector {
				text-align: @tdb_mts_align_horiz_$style_atts_uid;
			}

			/* @tdb_mts_ico_size_$style_atts_uid */
			.$style_selector .tdb-mvw-icon {
				font-size: @tdb_mts_ico_size_$style_atts_uid;
			}
			.$style_selector .tdb-mvw-icon svg{
				width: @tdb_mts_ico_size_$style_atts_uid;
                height: auto;
			}
			/* @tdb_mts_ico_align_$style_atts_uid */
			.$style_selector .tdb-mvw-icon {
				position: relative;
				top: @tdb_mts_ico_align_$style_atts_uid;
			}
			/* @tdb_mts_ico_space_$style_atts_uid */
			.$style_selector .tdb-mvw-icon {
				@tdb_mts_ico_space_$style_atts_uid
			}
			/* @tdb_mts_ico_color_$style_atts_uid */
			.$style_selector .tdb-mvw-icon {
				color: @tdb_mts_ico_color_$style_atts_uid;
			}
			/* @tdb_mts_ico_color_h_$style_atts_uid */
			.$style_selector a:hover .tdb-mvw-icon {
				color: @tdb_mts_ico_color_h_$style_atts_uid;
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
		$res_ctx->load_settings_raw( 'style_general_tdb_module_views', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_module_views_composer', 1 );
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

		/* -- Colors -- */
		$res_ctx->load_settings_raw( 'tdb_mts_color_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'color' ) );

		/* -- Fonts -- */
		$res_ctx->load_font_settings( 'f_txt', '', 'tdb_mts_', '_' . $style_atts_uid );

	}

    function render( $atts, $content = null ) {

		$additional_classes_array = array();


		/* -- Call the parent render method -- */
        parent::render($atts);

        $in_composer = tdc_state::is_live_editor_ajax() || tdc_state::is_live_editor_iframe();
        $tds_p_show_views = td_util::get_option('tds_p_show_views' );

		/* -- Block atts -- */
		// Date type
		$views_type = $this->get_att('views_type') != '' ? $this->get_att('views_type') : 'theme';

        // Icon
        $icon = $this->get_icon_att( 'tdicon' );
        $icon_html = '';
        if ( $icon != '' ) {
            if( base64_encode( base64_decode( $icon ) ) == $icon ) {
                $icon_data = '';
                if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
                    $icon_data = 'data-td-svg-icon="' . $this->get_att('tdicon') . '"';
                }

                $icon_html = '<span class="tdb-mvw-icon" ' . $icon_data . '>' . base64_decode( $icon ) . '</span>';
            } else {
                $icon_html = '<i class="tdb-mvw-icon ' . $icon . '"></i>';
            }
        }

        // Icon position
        $icon_position = $this->get_att( 'ico_pos' ) != '' ? $this->get_att( 'ico_pos' ) : 'before';


		/* -- Retrieve the module post data -- */
		$post_obj = self::$post_obj;

		$post_views_count = '123';

		if ( gettype($post_obj) === 'object' && get_class($post_obj) === 'WP_Post' ) {

			$post_obj_id = $post_obj->ID;

			// If the post views is set to come from the WP-Post Views Counter,
			// check also if the plugin is active; if not, then just get the default
			// theme views counter
			if( $views_type == 'wp-post-plugin' && function_exists('the_views') ) {
				$post_views_count = the_views( false );
			} else {
				$post_views_count = td_page_views::get_page_views( $post_obj_id );
			}

		}



		/* -- Output the module element HTML -- */
        $buffy = '';

		$buffy .= '<div class="' . $this->get_block_classes($additional_classes_array) . '" ' . $this->get_block_html_atts() . '>';
            // get the block css
            $buffy .= $this->get_block_css();

            // get the js for this block
            $buffy .= $this->get_block_js();

            if ( $in_composer && $tds_p_show_views === 'hide' ) {
                $buffy .= td_util::get_block_error('Module Views', 'Please enable Post Views option from Theme Panel > Post Settings.' );
                $buffy .= '</div>';

                return $buffy;
            }

            if( $icon_position == 'before' ) {
                $buffy .= $icon_html;
            }

			$buffy .= $post_views_count;

            if( $icon_position == 'after' ) {
                $buffy .= $icon_html;
            }
		$buffy .= '</div>';


        return $buffy;

    }

}