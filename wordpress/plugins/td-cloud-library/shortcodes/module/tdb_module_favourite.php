<?php

/**
 * Class tdb_module_exclusive_tag - shortcode for cloud template modules (renders post title)
 */
class tdb_module_favourite extends tdb_module_template_part {

    public function get_custom_css() {

        $style_selector = self::$style_selector;
        $style_atts_uid = self::$style_atts_uid;
        $style_atts_prefix = 'tdb_mts_';


        $compiled_css = '';

        $raw_css = "<style>
		
			/* @style_general_tdb_module_favourite */
			.tdb_module_favourite {
				display: flex;
				position: relative;
				margin: 0;
			}
			.tdb_module_favourite .tdb-block-inner {
			    position: relative;
			    width: 80px;
                height: 80px;
                font-size: 28px;
                background-color: #fff;
                border-radius: 100%;
                cursor: pointer;
             }
            .tdb_module_favourite .tdb-favorite-ico {
                display: block;
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: auto;
                height: auto;
                color: #000;
                transition: opacity .2s ease-in-out;
                pointer-events: none;
            }
            .tdb_module_favourite .tdb-favorite-ico svg {
                display: block;
                width: 1em;
                height: 1em;
            }
            .tdb_module_favourite .tdb-favorite-ico svg,
            .tdb_module_favourite .tdb-favorite-ico svg * {
                fill: currentColor;
            }
            .tdb_module_favourite .tdb-favorite-ico-full {
                opacity: 0;
            }
            .tdb_module_favourite .tdb-block-inner:hover .tdb-favorite-ico-empty {
                opacity: 0;
            }
            .tdb_module_favourite .tdb-favorite-selected .tdb-favorite-ico-empty {
                opacity: 0;
            }
            .tdb_module_favourite .tdb-block-inner:hover .tdb-favorite-ico-full {
                opacity: 1;
            }
            .tdb_module_favourite .tdb-favorite-selected .tdb-favorite-ico-full {
                opacity: 1;
            }
            
            /* @style_general_tdb_module_favourite_composer */
            .tdb_module_favourite .tdb-block-inner {
                pointer-events: none;
            }
            


            /* @" . $style_atts_prefix . "fav_size$style_atts_uid */
            body .$style_selector .tdb-favorite-ico {
                font-size: @" . $style_atts_prefix . "fav_size$style_atts_uid;
            }
            /* @" . $style_atts_prefix . "fav_space$style_atts_uid */
            .$style_selector .tdb-block-inner {
                width: @" . $style_atts_prefix . "fav_space$style_atts_uid;
                height: @" . $style_atts_prefix . "fav_space$style_atts_uid;
            }
            /* @" . $style_atts_prefix . "vert_align$style_atts_uid */
            .$style_selector .tdb-favorite-ico {
                top: calc(50% + @" . $style_atts_prefix . "vert_align$style_atts_uid);
            }
            /* @" . $style_atts_prefix . "content_align_horiz$style_atts_uid */
            .$style_selector {
                justify-content: @" . $style_atts_prefix . "content_align_horiz$style_atts_uid;
            }
            
            
            /* @" . $style_atts_prefix . "fav_ico_color$style_atts_uid */
            body .$style_selector .tdb-favorite-ico {
                color: @" . $style_atts_prefix . "fav_ico_color$style_atts_uid;
            }
            /* @" . $style_atts_prefix . "fav_ico_color_h$style_atts_uid */
            body .$style_selector .tdb-favorite-ico-full,
            body .$style_selector .tdb-block-inner.tdb-favorite-selected .tdb-favorite-ico-full {
                color: @" . $style_atts_prefix . "fav_ico_color_h$style_atts_uid;
            }
            /* @" . $style_atts_prefix . "fav_bg$style_atts_uid */
            body .$style_selector .tdb-block-inner {
                background-color: @" . $style_atts_prefix . "fav_bg$style_atts_uid;
            }
            /* @" . $style_atts_prefix . "fav_bg_h$style_atts_uid */
            body .$style_selector .tdb-block-inner:hover,
            body .$style_selector .tdb-block-inner.tdb-favorite-selected {
                background-color: @" . $style_atts_prefix . "fav_bg_h$style_atts_uid;
            }
            /* @" . $style_atts_prefix . "fav_shadow$style_atts_uid */
            body .$style_selector .tdb-block-inner {
                box-shadow: @" . $style_atts_prefix . "fav_shadow$style_atts_uid;
            }
		
		</style>";

        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts() );

        $compiled_css .= $td_css_res_compiler->compile_css();
        return $compiled_css;

    }

    static function cssMedia( $res_ctx ) {

        $style_atts_uid = self::$style_atts_uid;
        $style_atts_prefix = 'tdb_mts_';




        /* --
        -- GENERAL
        -- */
        $res_ctx->load_settings_raw( 'style_general_tdb_module_favourite', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_module_favourite_composer', 1 );
        }




        /* --
        -- FAVORITE BUTTON
        -- */
        /* -- Layout -- */
        // favorite button size
        $fav_size = $res_ctx->get_shortcode_att('fav_size');
        $res_ctx->load_settings_raw( $style_atts_prefix . 'fav_size' . $style_atts_uid, $fav_size . 'px' );

        // favorite button space
        $fav_space = $res_ctx->get_shortcode_att('fav_space');
        $icon_spacing = $fav_size * $fav_space . 'px';

        if( $fav_space != '' && is_numeric( $fav_space ) ) {
            $res_ctx->load_settings_raw($style_atts_prefix . 'fav_space' . $style_atts_uid, $icon_spacing);
        }

        // icon vertical align
        $res_ctx->load_settings_raw( $style_atts_prefix . 'vert_align' . $style_atts_uid, $res_ctx->get_shortcode_att( 'vert_align' ) . 'px' );

        // content horiz align
        $content_horiz_align = $res_ctx->get_shortcode_att( 'content_align_horizontal' );
        switch( $content_horiz_align ) {
            case '':
            case 'content-horiz-left':
                $content_horiz_align = 'flex-start';
                break;
            case 'content-horiz-center':
                $content_horiz_align = 'center';
                break;
            case 'content-horiz-right':
                $content_horiz_align = 'flex-end';
                break;
        }
        $res_ctx->load_settings_raw( $style_atts_prefix . 'content_align_horiz' . $style_atts_uid, $content_horiz_align );


        /* -- Colors -- */
        $res_ctx->load_settings_raw( $style_atts_prefix . 'fav_ico_color' . $style_atts_uid, $res_ctx->get_shortcode_att('fav_ico_color') );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'fav_ico_color_h' . $style_atts_uid, $res_ctx->get_shortcode_att('fav_ico_color_h') );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'fav_bg' . $style_atts_uid, $res_ctx->get_shortcode_att('fav_bg') );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'fav_bg_h' . $style_atts_uid, $res_ctx->get_shortcode_att('fav_bg_h') );
        $res_ctx->load_shadow_settings( 4, 1, 1, 0, 'rgba(0, 0, 0, 0.2)', 'fav_shadow','', false, 'tdb_mts_',$style_atts_uid );

    }


    function render( $atts, $content = null ) {

        $additional_classes_array = array();



        /* -- Set some default atts -- */
        $atts['tdicon_fav1'] = !empty( $atts['tdicon_fav1'] ) ? $atts['tdicon_fav1'] : 'td-icon-bookmark-empty';
        $atts['tdicon_fav2'] = !empty( $atts['tdicon_fav2'] ) ? $atts['tdicon_fav2'] : 'td-icon-bookmark-full';



        /* -- Call the parent render method -- */
        parent::render($atts);



        /* -- Block atts -- */
        // Default icon
        $tdicon_fav1 = $this->get_icon_att( 'tdicon_fav1' );

        $tdicon_fav1_html = '<span class="tdb-favorite-ico tdb-favorite-ico-empty"';
            if( base64_encode( base64_decode( $tdicon_fav1 ) ) == $tdicon_fav1 ) {
                if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
                    $tdicon_fav1_html .= ' data-td-svg-icon="' . $this->get_att( 'tdicon_fav1' ) . '"';
                }
                $tdicon_fav1_html .= '>' . base64_decode( $tdicon_fav1 ) ;
            } else {
                $tdicon_fav1_html .= '><i class="' . $tdicon_fav1 . '"></i>';
            }
        $tdicon_fav1_html .= '</span>';


        // Hover/bookmarked post icon
        $tdicon_fav2 = $this->get_icon_att( 'tdicon_fav2' );

        $tdicon_fav2_html = '<span class="tdb-favorite-ico tdb-favorite-ico-full"';
        if( base64_encode( base64_decode( $tdicon_fav2 ) ) == $tdicon_fav2 ) {
            if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
                $tdicon_fav2_html .= ' data-td-svg-icon="' . $this->get_att( 'tdicon_fav2' ) . '"';
            }
            $tdicon_fav2_html .= '>' . base64_decode( $tdicon_fav2 ) ;
        } else {
            $tdicon_fav2_html .= '><i class="' . $tdicon_fav2 . '"></i>';
        }
        $tdicon_fav2_html .= '</span>';



        /* -- Retrieve the module post data -- */
        $post_obj = self::$post_obj;
        $post_obj_id = 0;
        $is_article_favourite = false;

        if ( gettype($post_obj) === 'object' && get_class($post_obj) === 'WP_Post' ) {
            $post_obj_id = $post_obj->ID;

            if( !( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) ) {
                $is_article_favourite = td_util::is_article_favourite($post_obj_id);
            }
        }



        /* -- Output the module element HTML -- */
        $buffy = '';

        $buffy .= '<div class="' . $this->get_block_classes($additional_classes_array) . '" ' . $this->get_block_html_atts() . '>';
            // get the block css
            $buffy .= $this->get_block_css();

            // get the js for this block
            $buffy .= $this->get_block_js();

            $buffy .= '<div class="tdb-block-inner td-fix-index tdb-favorite ' . ( $is_article_favourite ? 'tdb-favorite-selected' : '' ) . '" data-post-id="' . $post_obj_id . '">';
                $buffy .= $tdicon_fav1_html;
                $buffy .= $tdicon_fav2_html;
            $buffy .= '</div>';
        $buffy .= '</div>';

        td_resources_load::render_script( TDB_SCRIPTS_URL . '/tdbFavourites.js' . TDB_SCRIPTS_VER, 'tdbFavourites-js', '', 'footer' );

        return $buffy;
    }

}