<?php

/**
 * Class tdb_flex_loop_builder
 */

class tdb_flex_loop_builder extends td_block {

	static $posts_count = 0;



    static function cssMedia( $res_ctx ) {

        /*-- GENERAL-- */
        $res_ctx->load_settings_raw( 'general_style_tdb_flex_loop_builder', 1 );



        /*-- BLOCK HEADER -- */
        // *- fonts -* //
        $res_ctx->load_font_settings( 'f_header' );



        /*-- MODULES LIST -- */
		// *- layout -* //
        // modules padding elements
        $padding = 'padding';
        if ( $res_ctx->is( 'all' ) ) {
            $padding = 'padding_desktop';
        }

        // modules per row
        $modules_on_row = $res_ctx->get_shortcode_att('modules_on_row');
        $modules_on_row = $modules_on_row != '' ? $modules_on_row : '100%';
        $res_ctx->load_settings_raw( 'modules_on_row', $modules_on_row );

        $modules_number = str_replace('%','', $modules_on_row);
        $modulo_posts = (int)self::$posts_count % intval((100/intval($modules_number)));

        switch ($modulo_posts) {
            case '0':
                $res_ctx->load_settings_raw( $padding,  '-n+' . intval(100/intval($modules_number)));
                break;
            case '1':
                $res_ctx->load_settings_raw( $padding,  '1' );
                break;
            case '2':
                $res_ctx->load_settings_raw( $padding,  '-n+2' );
                break;
            case '3':
                $res_ctx->load_settings_raw( $padding,  '-n+3' );
                break;
            case '4':
                $res_ctx->load_settings_raw( $padding,  '-n+4' );
                break;
            case '5':
                $res_ctx->load_settings_raw( $padding,  '-n+5' );
                break;
            case '6':
                $res_ctx->load_settings_raw( $padding,  '-n+6' );
                break;
            case '7':
                $res_ctx->load_settings_raw( $padding,  '-n+7' );
                break;
            case '8':
                $res_ctx->load_settings_raw( $padding,  '-n+8' );
                break;
        }

	    // modules gap
	    $modules_gap = $res_ctx->get_shortcode_att('modules_gap');
	    $res_ctx->load_settings_raw( 'modules_gap', $modules_gap );
	    if ( $modules_gap == '' ) {
		    $res_ctx->load_settings_raw( 'modules_gap', '24px');
	    } else if ( is_numeric( $modules_gap ) ) {
		    $res_ctx->load_settings_raw( 'modules_gap', $modules_gap / 2 .'px' );
	    }

	    // modules space
	    $modules_space = $res_ctx->get_shortcode_att('all_modules_space');
	    $res_ctx->load_settings_raw( 'all_modules_space', $modules_space );
	    if ( $modules_space == '' ) {
		    $res_ctx->load_settings_raw( 'all_modules_space', '18px');
	    } else if ( is_numeric( $modules_space ) ) {
		    $res_ctx->load_settings_raw( 'all_modules_space', $modules_space / 2 .'px' );
	    }

        // modules horizontal align
        $modules_horiz_align = $res_ctx->get_shortcode_att('modules_horiz_align');
        $modules_horiz_align = $modules_horiz_align != '' ? $modules_horiz_align : 'flex-start';
        $res_ctx->load_settings_raw( 'modules_horiz_align', $modules_horiz_align );

        // modules vertical align
        $modules_vert_align = $res_ctx->get_shortcode_att('modules_vert_align');
        $modules_vert_align = $modules_vert_align != '' ? $modules_vert_align : 'flex-start';
        $res_ctx->load_settings_raw( 'modules_vert_align', $modules_vert_align );



        /*-- INDIVIDUAL MODULE -- */
        // *- layout -* //
        // modules padding
        $modules_padding = $res_ctx->get_shortcode_att('modules_padding');
        $modules_padding .= !empty( $modules_padding ) && is_numeric( $modules_padding ) ? 'px' : '';
        $res_ctx->load_settings_raw( 'modules_padding', $modules_padding );

        // modules border size
        $all_m_bord = $res_ctx->get_shortcode_att('all_m_bord');
        $all_m_bord .= !empty( $all_m_bord ) && is_numeric( $all_m_bord ) ? 'px' : '';
        $res_ctx->load_settings_raw( 'all_m_bord', $all_m_bord );

        // modules border style
        $all_m_bord_style = $res_ctx->get_shortcode_att('all_m_bord_style');
        $all_m_bord_style = $all_m_bord_style != '' ? $all_m_bord_style : 'solid';
        $res_ctx->load_settings_raw( 'all_m_bord_style', $all_m_bord_style );

        // modules border radius
        $m_bord_radius = $res_ctx->get_shortcode_att('m_bord_radius');
        $m_bord_radius .= !empty( $m_bord_radius ) && is_numeric( $m_bord_radius ) ? 'px' : '';
        $res_ctx->load_settings_raw( 'm_bord_radius', $m_bord_radius );

        // modules divider size
		$all_divider = $res_ctx->get_shortcode_att('all_divider');
		$all_divider .= !empty( $all_divider ) && is_numeric( $all_divider ) ? 'px' : ''; 
        $res_ctx->load_settings_raw( 'all_divider', $all_divider );

        // modules divider style
		$all_divider_style = $res_ctx->get_shortcode_att('all_divider_style');
		$all_divider_style = $all_divider_style != '' ? $all_divider_style : 'solid'; 
        $res_ctx->load_settings_raw( 'all_divider_style', $all_divider_style );


        // *- colors -* //
        $res_ctx->load_settings_raw( 'modules_bg', $res_ctx->get_shortcode_att('modules_bg') );
        $res_ctx->load_settings_raw( 'modules_bg_h', $res_ctx->get_shortcode_att('modules_bg_h') );

        $res_ctx->load_shadow_settings( 0, 0, 0, 0, 'rgba(0, 0, 0, 0.08)', 'm_shadow' );
        $res_ctx->load_shadow_settings( 0, 0, 0, 0, 'rgba(0, 0, 0, 0.08)', 'm_shadow_h' );

        $all_m_bord_color = $res_ctx->get_shortcode_att('all_m_bord_color');
        $all_m_bord_color = $all_m_bord_color != '' ? $all_m_bord_color : '#000';
        $res_ctx->load_settings_raw( 'all_m_bord_color', $all_m_bord_color );
        $res_ctx->load_settings_raw( 'm_bord_color_h', $res_ctx->get_shortcode_att('m_bord_color_h') );

		$all_divider_color = $res_ctx->get_shortcode_att('all_divider_color');
		$all_divider_color = $all_divider_color != '' ? $all_divider_color : '#eaeaea'; 
        $res_ctx->load_settings_raw( 'all_divider_color', $all_divider_color );



        /*-- PAGINATION -- */
        // *- layout -* //
        // pagination space
        $pag_space = $res_ctx->get_shortcode_att('pag_space');
        $res_ctx->load_settings_raw( 'pag_space', $pag_space );
        if( $pag_space != '' && is_numeric( $pag_space ) ) {
            $res_ctx->load_settings_raw( 'pag_space', $pag_space . 'px' );
        }

        // pagination padding
        $pag_padding = $res_ctx->get_shortcode_att('pag_padding');
        $res_ctx->load_settings_raw( 'pag_padding', $pag_padding );
        if( $pag_padding != '' && is_numeric( $pag_padding ) ) {
            $res_ctx->load_settings_raw( 'pag_padding', $pag_padding . 'px' );
        }

        // pagination border width
        $pag_border_width = $res_ctx->get_shortcode_att('pag_border_width');
        $res_ctx->load_settings_raw( 'pag_border_width', $pag_border_width );
        if( $pag_border_width != '' && is_numeric( $pag_border_width ) ) {
            $res_ctx->load_settings_raw( 'pag_border_width', $pag_border_width . 'px' );
        }
        // pagination border radius
        $pag_border_radius = $res_ctx->get_shortcode_att('pag_border_radius');
        $res_ctx->load_settings_raw( 'pag_border_radius', $pag_border_radius );
        if( $pag_border_radius != '' && is_numeric( $pag_border_radius ) ) {
            $res_ctx->load_settings_raw( 'pag_border_radius', $pag_border_radius . 'px' );
        }

        // next/prev icons size
        $pag_icons_size = $res_ctx->get_shortcode_att('pag_icons_size');
        $res_ctx->load_settings_raw( 'pag_icons_size', $pag_icons_size );
        if( $pag_icons_size != '' && is_numeric( $pag_icons_size ) ) {
            $res_ctx->load_settings_raw( 'pag_icons_size', $pag_icons_size . 'px' );
        }

        // *- colors -* //
        $res_ctx->load_settings_raw( 'pag_text', $res_ctx->get_shortcode_att('pag_text') );
        $res_ctx->load_settings_raw( 'pag_bg', $res_ctx->get_shortcode_att('pag_bg') );
        $res_ctx->load_settings_raw( 'pag_border', $res_ctx->get_shortcode_att('pag_border') );
        $res_ctx->load_settings_raw( 'pag_a_text', $res_ctx->get_shortcode_att('pag_a_text') );
        $res_ctx->load_settings_raw( 'pag_a_bg', $res_ctx->get_shortcode_att('pag_a_bg') );
        $res_ctx->load_settings_raw( 'pag_a_border', $res_ctx->get_shortcode_att('pag_a_border') );
        $res_ctx->load_settings_raw( 'pag_h_text', $res_ctx->get_shortcode_att('pag_h_text') );
        $res_ctx->load_settings_raw( 'pag_h_bg', $res_ctx->get_shortcode_att('pag_h_bg') );
        $res_ctx->load_settings_raw( 'pag_h_border', $res_ctx->get_shortcode_att('pag_h_border') );

        // *- fonts -* //
        $res_ctx->load_font_settings( 'f_pag' );

    }

    public function get_custom_css() {

        // $unique_block_class
        $unique_block_class = $this->block_uid;
        $unique_block_modal_class = $this->block_uid . '_m';

        $compiled_css = '';

        $raw_css =
            "<style>
            
                /* @general_style_tdb_flex_loop_builder */
                .tdb_flex_loop_builder {
                    margin-bottom: 48px;
                    padding-bottom: 0;
                    overflow: visible !important;
                    counter-reset: numbers;
                }
                .tdb_flex_loop_builder .td_block_inner {
                    display: flex;
                    flex-wrap: wrap;
                    height: 100%;
				}
				.tdb_flex_loop_builder .td-module-container {
					position: relative;
					height: 100%;
					transition: background-color .2s ease-in-out, box-shadow .2s ease-in-out, border-color .2s ease-in-out;
				}
				.tdb_flex_loop_builder .td-module-container:before {
					content: '';
					position: absolute;
					bottom: 0;
					left: 0;
					width: 100%;
					height: 1px;
				}
				.tdb_flex_loop_builder .td_block_inner .td-module-container > [class*='tdc-zone'],
				.tdb_flex_loop_builder .td_block_inner .td-module-container > [class*='tdc-zone'] > .tdc_zone {
				    height: 100%; 
				}
				.tdb_flex_loop_builder .td_block_inner .td-module-container > [class*='tdc-zone'] > .tdc_zone {
                    display: flex;
                    flex-direction: column;
				}
				.tdb_flex_loop_builder .td_block_inner .td_module_wrap .tdc-row:not([class*='stretch_row_']),
                .tdb_flex_loop_builder .td_block_inner .td_module_wrap .tdc-row-composer:not([class*='stretch_row_'])  {
                    width: 100% !important;
                    max-width: 1240px !important;
                }
				@media (max-width: 767px) {
					.tdb_flex_loop_builder .td_block_inner .td_module_wrap .td-container,
					.tdb_flex_loop_builder .td_block_inner .td_module_wrap .tdc-row,
					.tdb_flex_loop_builder .td_block_inner .td_module_wrap .tdc-row-composer {
						padding-left: 0;
						padding-right: 0;
					}
					.tdb_flex_loop_builder .td_block_inner .td_module_wrap .td-pb-row > .td-element-style {
                        width: 100% !important;
                        left: 0 !important;
                        transform: none !important;
                    }
				}
                .tdb_flex_loop_builder .td-load-more-wrap,
                .tdb_flex_loop_builder .td-next-prev-wrap {
                    margin: 20px 0 0;
                }
                .tdb_flex_loop_builder .page-nav {
                    position: relative;
                    margin: 54px 0 0;
                }
                .tdb_flex_loop_builder .page-nav a,
                .tdb_flex_loop_builder .page-nav span {
                    margin-top: 8px;
                    margin-bottom: 0;
                }
                .tdb_flex_loop_builder .td-next-prev-wrap a {
                    width: auto;
                    height: auto;
                    min-width: 25px;
                    min-height: 25px;
                }
                .tdb_flex_loop_builder .td-block-missing-settings {
                    width: 100%;
                }
                .tdb_flex_loop_builder .tdb-module-tpl-edit-btns {
                    position: absolute;
					top: 0;
					left: 0;
					display: none;
					flex-wrap: wrap;
					gap: 0 4px;
                }
                .tdb_flex_loop_builder .tdb-module-tpl-edit-btn {
					background-color: #000;
					padding: 1px 8px 2px;
					font-size: 11px;
					color: #fff;
					z-index: 100;
				}
                .tdb_flex_loop_builder .td-module-container:hover .tdb-module-tpl-edit-btns {
					display: flex;
				}
                .tdb_flex_loop_builder.tdc-no-posts .td_block_inner {
                    margin-left: 0 !important;
                    margin-right: 0 !important;
                }
                .tdb_flex_loop_builder.tdc-no-posts .td_block_inner .no-results h2 {
                    font-size: 13px;
                    font-weight: normal;
                    text-align: left;
                    padding: 20px;
                    border: 1px solid rgba(190, 190, 190, 0.35);
                    color: rgba(125, 125, 125, 0.8);
                }
                

				
                /* @modules_on_row */
				.$unique_block_class .td_module_wrap {
					width: @modules_on_row;
					float: left;
				}
				.rtl .$unique_block_class .td_module_wrap {
					float: right;
				}
				/* @padding_desktop */
				.$unique_block_class .td_module_wrap:nth-last-child(@padding_desktop) {
					margin-bottom: 0;
					padding-bottom: 0;
				}
				.$unique_block_class .td_module_wrap:nth-last-child(@padding_desktop) .td-module-container:before {
					display: none;
				}
				/* @padding */
				.$unique_block_class .td_module_wrap {
					padding-bottom: @all_modules_space !important;
					margin-bottom: @all_modules_space !important;
				}
				.$unique_block_class .td_module_wrap:nth-last-child(@padding) {
					margin-bottom: 0 !important;
					padding-bottom: 0 !important;
				}
				.$unique_block_class .td_module_wrap .td-module-container:before {
					display: block !important;
				}
				.$unique_block_class .td_module_wrap:nth-last-child(@padding) .td-module-container:before {
					display: none !important;
				}
				/* @modules_gap */
				.$unique_block_class .td_module_wrap {
					padding-left: @modules_gap;
					padding-right: @modules_gap;
				}
				.$unique_block_class .td_block_inner {
					margin-left: -@modules_gap;
					margin-right: -@modules_gap;
				}
				.$unique_block_class .td-block-missing-settings {
					margin-left: @modules_gap;
					margin-right: @modules_gap;
				}
				/* @all_modules_space */
				.$unique_block_class .td_module_wrap {
					padding-bottom: @all_modules_space;
					margin-bottom: @all_modules_space;
				}
				.$unique_block_class .td-module-container:before {
					bottom: -@all_modules_space;
				}
				/* @modules_horiz_align */
				.$unique_block_class .td_block_inner {
					justify-content: @modules_horiz_align;
				}
				/* @modules_vert_align */
				.$unique_block_class .td_block_inner {
					align-items: @modules_vert_align;
				}
				
				/* @modules_padding */
				.$unique_block_class .td-module-container {
					padding: @modules_padding;
				}
				/* @all_m_bord */
				.$unique_block_class .td-module-container {
				 border-width: @all_m_bord;
				 border-style: @all_m_bord_style;
				 border-color: @all_m_bord_color;
				}
				/* @m_bord_color_h */
				.$unique_block_class .td-module-container:hover {
					border-color: @m_bord_color_h;
				}
				/* @m_bord_radius */
				.$unique_block_class .td-module-container {
					border-radius: @m_bord_radius;
				}
				/* @all_divider */
				.$unique_block_class .td-module-container:before {
					border-bottom: @all_divider @all_divider_style @all_divider_color;
				}
				/* @m_shadow */
				.$unique_block_class .td-module-container {
				    box-shadow: @m_shadow;
				}
				/* @m_shadow_h */
				.$unique_block_class .td-module-container:hover {
				    box-shadow: @m_shadow_h;
				}
				/* @modules_bg */
				.$unique_block_class .td-module-container {
				    background-color: @modules_bg;
				}
				/* @modules_bg_h */
				.$unique_block_class .td-module-container:hover {
				    background-color: @modules_bg_h;
				}
				
				
				/* @pag_space */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap,
				.$unique_block_class .page-nav,
				.$unique_block_class .td-load-more-wrap {
					margin-top: @pag_space;
				}
				/* @pag_padding */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a,
				.$unique_block_class .page-nav a,
				.$unique_block_class .page-nav .current,
				.$unique_block_class .page-nav .dots,
				.$unique_block_class .page-nav .pages,
				.$unique_block_class .td-load-more-wrap a {
					padding: @pag_padding;
				}
				.$unique_block_class .page-nav .pages {
				    padding-right: 0;
				}
				/* @pag_border_width */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a,
				.$unique_block_class .page-nav a,
				.$unique_block_class .page-nav .current,
				.$unique_block_class .page-nav .dots,
				.$unique_block_class .page-nav .pages,
				.$unique_block_class .td-load-more-wrap a {
					border-width: @pag_border_width;
				}
				.$unique_block_class .page-nav .extend {
				    border-style: solid;
				    border-color: transparent;
				}
				.$unique_block_class .page-nav .pages {
				    border-style: solid;
				    border-color: transparent;
				    border-right-width: 0;
				}
				/* @pag_border_radius */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a,
				.$unique_block_class .page-nav a,
				.$unique_block_class .page-nav .dots,
				.$unique_block_class .page-nav .current,
				.$unique_block_class .td-load-more-wrap a {
					border-radius: @pag_border_radius;
				}
				/* @pag_icons_size */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a,
				.$unique_block_class .td-load-more-wrap a i,
				.$unique_block_class .page-nav a i {
					font-size: @pag_icons_size;
				}
				.$unique_block_class .td-load-more-wrap a .td-load-more-icon-svg svg,
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap .td-next-prev-icon-svg svg,
				.$unique_block_class .page-nav .page-nav-icon-svg svg {
				    width: @pag_icons_size;
				    height: calc( @pag_icons_size + 1px );
				}
				
				/* @pag_text */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a,
				.$unique_block_class .page-nav a,
				.$unique_block_class .page-nav .dots,
				.$unique_block_class .td-load-more-wrap a {
					color: @pag_text;
				}
				.$unique_block_class .td-load-more-wrap a .td-load-more-icon-svg svg,
				.$unique_block_class .td-load-more-wrap a .td-load-more-icon-svg svg *,
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap .td-next-prev-icon-svg svg,
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap .td-next-prev-icon-svg svg *,
				.$unique_block_class .page-nav .page-nav-icon-svg svg ,
				.$unique_block_class .page-nav .page-nav-icon-svg svg * {
				    fill: @pag_text;
				}
				/* @pag_bg */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a,
				.$unique_block_class .page-nav a,
				.$unique_block_class .td-load-more-wrap a {    
					background-color: @pag_bg;
				}
				/* @pag_border */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a,
				.$unique_block_class .page-nav a,
				.$unique_block_class .page-nav .dots,
				.$unique_block_class .td-load-more-wrap a {
					border-color: @pag_border;
				}
				/* @pag_a_text */
				.$unique_block_class .page-nav .current {
					color: @pag_a_text;
				}
				/* @pag_a_bg */
				.$unique_block_class .page-nav .current {
					background-color: @pag_a_bg;
				}
				/* @pag_a_border */
				.$unique_block_class .page-nav .current {
					border-color: @pag_a_border;
				}
				/* @pag_h_text */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a:hover,
				.$unique_block_class .page-nav a:hover,
				.$unique_block_class .td-load-more-wrap a:hover {
					color: @pag_h_text;
				}
				.$unique_block_class .td-load-more-wrap a:hover .td-load-more-icon-svg svg,
				.$unique_block_class .td-load-more-wrap a:hover .td-load-more-icon-svg svg *,
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a:hover .td-next-prev-icon-svg svg,
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a:hover .td-next-prev-icon-svg svg *,
				.$unique_block_class .page-nav a:hover .page-nav-icon-svg svg ,
				.$unique_block_class .page-nav a:hover .page-nav-icon-svg svg * {
				    fill: @pag_h_text;
				}
				/* @pag_h_bg */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a:hover,
				.$unique_block_class .page-nav a:hover,
				.$unique_block_class .td-load-more-wrap a:hover {    
					background-color: @pag_h_bg;
				}
				/* @pag_h_border */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a:hover,
				.$unique_block_class .page-nav a:hover,
				.$unique_block_class .td-load-more-wrap a:hover {
					border-color: @pag_h_border;
				}
				
				/* @f_pag */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a i,
				.$unique_block_class .page-nav a,
				.$unique_block_class .page-nav span,
				.$unique_block_class .td-load-more-wrap a {
					@f_pag
				}
				
				
				/* @f_header */
				.$unique_block_class .td-block-title a,
				.$unique_block_class .td-block-title span {
					@f_header
				}
            
            </style>";

        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts() );

        $compiled_css .= $td_css_res_compiler->compile_css();

        return $compiled_css;
    }



    function render( $atts, $content = null ) {

        global $tdb_state_category, $tdb_state_author, $tdb_state_search, $tdb_state_date, $tdb_state_tag;

        switch( tdb_state_template::get_template_type() ) {

            case 'cpt_tax':

                if ( $tdb_state_category->is_cpt_post_type_archive() ) {
                    $loop_data = $tdb_state_category->cpt_archive_loop->__invoke($atts);
                    $atts['installed_post_types'] = $loop_data['post_type'];
                } else {
                    $tdb_state_category->set_tax();
                    $loop_data = $tdb_state_category->loop->__invoke($atts);
                    $atts['category_id'] = $loop_data['term_id'];
                }

                break;

            case 'category':
                $loop_data = $tdb_state_category->loop->__invoke( $atts );
                $atts['category_id'] = $loop_data['category_id'];

                break;

            case 'author':
                $loop_data = $tdb_state_author->loop->__invoke( $atts );
                $atts['autors_id'] = $loop_data['author_id'];

                break;

            case 'search':
                $loop_data = $tdb_state_search->loop->__invoke( $atts );
                $atts['search_query'] = $loop_data['search_query'];

                break;

            case 'date':
                $loop_data = $tdb_state_date->loop->__invoke( $atts );
                $atts['date_query'] = $loop_data['date_query'];

                break;

            case 'tag':
                $loop_data = $tdb_state_tag->loop->__invoke( $atts );
                $atts['tag_slug'] = $loop_data['tag_slug'];

                break;

        }


		/* -- Additional block classes -- */
		$additional_classes_array = array();


		/* -- TDB filters init -- */
	    $tdb_filters = array(
		    'tax_terms_ids' => array()
        );


		/* -- Set current tax/category filter -- */
        if ( !empty( $atts['category_id'] ) ) {
            $tdb_filters['tax_terms_ids'][] = $atts['category_id'];
        }


		/* -- Apply tdb filters from url -- */
	    $filters = $_GET;
	    if( !empty( $filters ) && is_array( $filters ) ) {

		    foreach ( $filters as $tax => $tax_terms_filters_list ) {

                // ignore filters not starting with 'tdb_'
                if ( strpos( $tax, 'tdb_' ) === false )
	                continue;

                // taxonomy
			    if ( strpos( $tax, 'tdb_tax' ) !== false ) {
				    $taxonomy = str_replace( 'tdb_tax_', '', $tax );
				    $tax_terms = array_map( 'sanitize_title', explode( ',', $tax_terms_filters_list ) );

                    // check slugs
                    foreach ( $tax_terms as $tax_term ) {
                        $the_tax_term = get_term_by( 'slug', $tax_term, $taxonomy );
                        if ( false !== $the_tax_term ) {
	                        $tdb_filters['tax_terms_ids'][] = $the_tax_term->term_id;
                        }
                    }

                }

		    }

	    }


		/* -- Set tdb filters to block's atts -- */
	    if ( !empty( $tdb_filters['tax_terms_ids'] ) ) {
		    $atts['category_ids'] = implode( ',', $tdb_filters['tax_terms_ids'] );
		    $atts['in_all_terms'] = 'yes'; // filtered results must belong to all taxonomies

            // @note it's enough just to set this attribute in order to not include child terms, by default this option is enabled in wp tax queries @see WP_Tax_Query
		    $atts['include_children'] = 'no'; // do not include child terms
	    }


		/* -- Set post type -- */
	    $atts['installed_post_types'] = empty( $atts['installed_post_types'] ) ? ( !empty($_GET['post_type']) ? tdb_util::clean( wp_unslash( $_GET['post_type'] ) ) : '' ) : $atts['installed_post_types'];


		/* -- Pagination -- */
        $pagination = !empty($atts['ajax_pagination']) ? $atts['ajax_pagination'] : '';
        if ( $pagination === 'numbered' ) {
            // set page
            $atts['page'] = empty( $_GET['tdb-loop-page'] ) ? '' : intval( $_GET['tdb-loop-page'] );

			// Add numbered pagination class
			$additional_classes_array[] = 'tdb-numbered-pagination';
        }


		/* -- Set sorting -- */
	    $atts['sort'] = empty( $_GET['tdb-loop-orderby'] ) ? ( $atts['sort'] ?? '' ) : tdb_util::clean( wp_unslash( $_GET['tdb-loop-orderby'] ) );

        $atts['block_type'] = __CLASS__;


		/* -- Set search query -- */
	    global $wp_query, $tdb_state_search;

	    if ( !empty( $tdb_state_search ) && $tdb_state_search->has_wp_query() ) {

		    $search_template_wp_query = $wp_query;
		    $wp_query = $tdb_state_search->get_wp_query();

		    $atts['search_query'] = get_search_query();

		    $wp_query = $search_template_wp_query;

	    }


		/* -- TDB filters init -- */
        parent::render( $atts );


		/* -- Set the total posts count -- */
		self::$posts_count = !empty( $this->td_query->posts ) ? count( $this->td_query->posts ) : 0;

        // in composer flag .. for debugging @todo remove
        $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();
        //if ( !$in_composer ) {
        //    echo '<pre>' . print_r( $atts, true ) . '</pre>';
        //    echo '<pre>' . print_r( __CLASS__, true ) . '</pre>';
        //    echo '<pre>' . print_r( $this->td_query, true ) . '</pre>';
        //    //echo '<pre>' . print_r( count( $this->td_query->posts ), true ) . '</pre>';
        //    //echo '<pre>' . print_r( $this->td_query->posts, true ) . '</pre>';
        //}


		/* -- Block atts -- */
	    // Get the active module template id
	    $tdb_module_template_id = $this->get_att('cloud_tpl_module_id');


        /* -- Output the module element HTML -- */
	    $buffy = '<div class="' . $this->get_block_classes( $additional_classes_array ) . '" ' . $this->get_block_html_atts() . '>';

            // get the block css
            $buffy .= $this->get_block_css();

            // get the js for this block
            $buffy .= $this->get_block_js();


			// return a warning if no module template has been selected
			if( $tdb_module_template_id == '' ) {
					$buffy .= '<div id=' . $this->block_uid . ' class="td_block_inner tdb-block-inner td-fix-index">';
                        $buffy .= td_util::get_block_error(
							'Flex Loop Builder',
							'Please select a Cloud Library Module Template.');
                    $buffy .= '</div>';
				$buffy .= '</div>';

				return $buffy;
			}


            // return a warning if the module template is not valid
            if( !tdb_util::is_tdb_module( 'tdb_module_' . $tdb_module_template_id, true ) ) {
					$buffy .= '<div id=' . $this->block_uid . ' class="td_block_inner tdb-block-inner td-fix-index">';
                        $buffy .= td_util::get_block_error(
							'Flex Loop Builder',
							'The Cloud Library Module Template set for this block is not valid or it no longer exists. Please select another Module Template.');
                    $buffy .= '</div>';
				$buffy .= '</div>';

				return $buffy;
            }


			// add the block title
			$buffy .= '<div class="td-block-title-wrap">';
				$buffy .= $this->get_block_title(); //get the block title
				$buffy .= $this->get_pull_down_filter(); //get the sub category filter for this block
			$buffy .= '</div>';

            
			// process filters
			if ( !( tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax() ) ) {

				// render js
                td_resources_load::render_script( TDB_SCRIPTS_URL . '/tdbFiltersLoop.js' . TDB_SCRIPTS_VER, 'tdbFiltersLoop-js', '', 'footer' );

				ob_start();

				?>
				<script>
					jQuery().ready(function () {
						var tdbFiltersLoopItem = new tdbFiltersLoop.item();
						tdbFiltersLoopItem.blockUid = '<?php echo $this->block_uid; ?>';
						tdbFiltersLoopItem.blockAtts = '<?php echo json_encode( $this->get_all_atts(), JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT ); ?>';
						tdbFiltersLoopItem.jqueryObj = jQuery('.<?php echo $this->block_uid ?>');
						<?php if ( tdc_state::is_live_editor_ajax() || tdc_state::is_live_editor_iframe() ) { ?>
						tdbFiltersLoopItem.inComposer = true;
						<?php } ?>
						tdbFiltersLoop.addItem( tdbFiltersLoopItem );
					});
				</script>
				<?php

				td_js_buffer::add_to_footer("\n" . td_util::remove_script_tag( ob_get_clean() ) );

			}

			// block inner
			$buffy .= '<div id=' . $this->block_uid . ' class="td_block_inner tdb-block-inner td-fix-index">';
				if ( !empty( $this->td_query->posts ) ) {
					$buffy .= $this->inner( $this->td_query->posts, $tdb_module_template_id );
				} else {

					if ( !empty( tdb_state_template::get_template_type() ) && 'search' === tdb_state_template::get_template_type() ) {
						$buffy .= '<div class="no-results td-pb-padding-side">';
							$buffy .= '<h2>' . __td('No results', TD_THEME_NAME ) . '</h2>';
						$buffy .= '</div>';
					} else {
						/**
						 * no posts to display. This function generates the __td('No posts to display').
						 * the text can be overwritten by the template using the global @see td_global::$custom_no_posts_message
						 */
						$buffy .= td_page_generator::no_posts();
					}

				}
			$buffy .= '</div>';

			if ( $pagination != '' ) {
				if ( $pagination === 'numbered' ) {
					$buffy .= $this->get_numbered_pagination( $this->td_query, $atts );
				} else {
					$prev_icon = $this->get_icon_att('prev_tdicon');
					$prev_icon_class = $this->get_att('prev_tdicon');
					$next_icon = $this->get_icon_att('next_tdicon');
					$next_icon_class = $this->get_att('next_tdicon');

					$buffy .= $this->get_block_pagination( $prev_icon, $next_icon, $prev_icon_class, $next_icon_class );
				}
			}

        $buffy .= '</div>';

	    // reset the module template params global
	    $tdb_module_template_params_reset = true;

		// tpl type
	    $tdb_template_type = null;
		if ( is_singular( array( 'tdb_templates' ) ) ) {
			global $post;
            if ( !empty( $post ) ) {
                $tdb_template_type = get_post_meta( $post->ID, 'tdb_template_type', true );
            }
		}

	    // don't reset on module tpl
	    if ( $tdb_template_type === 'module' ) {
		    $tdb_module_template_params_reset = false;
	    }

		if ( $tdb_module_template_params_reset ) {
			/* -- Reset the module template params global -- */
			global $tdb_module_template_params;
			$tdb_module_template_params = null;
		}

        return $buffy;

    }

	

    function inner( $posts, $module_id ) {

	    $buffy = '';
	    $td_block_layout = new td_block_layout();

	    if ( !empty( $posts ) ) {
		    foreach ( $posts as $post ) {
			    $tdb_module_cpt = new tdb_module_template( $post, $module_id, $this->get_all_atts() );
			    $buffy .= $tdb_module_cpt->render();
		    }
	    }

	    $buffy .= $td_block_layout->close_all_tags();

	    return $buffy;

    }



    function get_numbered_pagination( $td_query, $atts ) {

	    $svg_list = td_global::$svg_theme_font_list;

	    // previous text icon
	    $prev_icon_html = '<i class="page-nav-icon td-icon-menu-left"></i>';
	    if( isset( $atts['prev_tdicon'] ) ) {
		    $prev_icon = $atts['prev_tdicon'];
		    $prev_icon_data = '';
		    if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
			    $prev_icon_data = 'data-td-svg-icon="' . $prev_icon . '"';
		    }

		    if( array_key_exists( $prev_icon, $svg_list ) ) {
			    $prev_icon_html = '<div class="page-nav-icon page-nav-icon-svg" ' . $prev_icon_data . '>' . base64_decode( $svg_list[$prev_icon] ) . '</div>';
		    } else {
			    $prev_icon_html = '<i class="page-nav-icon ' . $prev_icon . '"></i>';
		    }
	    }
	    // next text icon
	    $next_icon_html = '<i class="page-nav-icon td-icon-menu-right"></i>';
	    if( isset( $atts['next_tdicon'] ) ) {
		    $next_icon = $atts['next_tdicon'];
		    $next_icon_data = '';
		    if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
			    $next_icon_data = 'data-td-svg-icon="' . $next_icon . '"';
		    }

		    if( array_key_exists( $next_icon, $svg_list ) ) {
			    $next_icon_html = '<div class="page-nav-icon page-nav-icon-svg" ' . $next_icon_data . '>' . base64_decode( $svg_list[$next_icon] ) . '</div>';
		    } else {
			    $next_icon_html = '<i class="page-nav-icon ' . $next_icon . '"></i>';
		    }
	    }

	    // pagination defaults
	    $pagination_data = array(
		    'pages_text'    => __td( 'Page %CURRENT_PAGE% of %TOTAL_PAGES%', TD_THEME_NAME ),
		    'current_text'  => '%PAGE_NUMBER%',
		    'page_text'     => '%PAGE_NUMBER%',
		    'first_text'    => __td( '1' ),
		    'last_text'     => __td( '%TOTAL_PAGES%' ),
		    'dotright_text' => __td( '...' ),
		    'dotleft_text'  => __td( '...' )
	    );

        // paged
	    global $paged;
	    $paged = intval( $td_query->query['paged'] );
	    if ( $paged === 0 ) {
		    $paged = 1;
	    }

        // max num pages
	    $max_num_pages = $td_query->max_num_pages;

	    $pages_to_show = 3;
	    $pages_to_show_minus_1 = $pages_to_show - 1;
	    $half_page_start = floor( $pages_to_show_minus_1 / 2 );
	    $half_page_end = ceil( $pages_to_show_minus_1 / 2 );
	    $start_page = $paged - $half_page_start;

	    if ( $start_page <= 0 ) {
		    $start_page = 1;
	    }

        // end page
	    $end_page = $paged + $half_page_end;
	    if ( ( $end_page - $start_page ) != $pages_to_show_minus_1 ) {
		    $end_page = $start_page + $pages_to_show_minus_1;
	    }

	    if ( $end_page > $max_num_pages ) {
		    $start_page = $max_num_pages - $pages_to_show_minus_1;
		    $end_page = $max_num_pages;
	    }

	    if ( $start_page <= 0 ) {
		    $start_page = 1;
	    }

	    //global $wp_query;
	    //$global_wp_query = $wp_query;

        // replace global wp query
	    //$wp_query = $td_query;

	    //$previous_posts_link = get_previous_posts_link( $prev_icon_html );
	    //$next_posts_link = get_next_posts_link( $next_icon_html, $max_num_pages );

        // revert global wp query
	    //$wp_query = $global_wp_query;

        $buffy = '';

        // build pagination output
	    if( $max_num_pages > 1  ) {

            $buffy .= '<div class="page-nav td-pb-padding-side">';
                $buffy .= paginate_links(
                    array(
                        'base'      => esc_url_raw( add_query_arg( 'tdb-loop-page', '%#%', false ) ),
                        'format'    => '?tdb-loop-page=%#%',
                        'add_args'  => false,
                        'current'   => max( 1, $paged ),
                        'total'     => $max_num_pages,
                        'prev_text' => $prev_icon_html,
                        'next_text' => $next_icon_html,
                        'type'      => 'plain',
                        'end_size'  => 3,
                        'mid_size'  => 3,
                    )
                );

                $pages_text = str_replace("%CURRENT_PAGE%", number_format_i18n( $paged ), $pagination_data['pages_text'] );
                $pages_text = str_replace("%TOTAL_PAGES%", number_format_i18n( $max_num_pages ), $pages_text );

                if ( !empty( $pages_text ) ) {
                    $buffy .= '<span class="pages">' . $pages_text . '</span>';
                }

                $buffy .= '<div class="clearfix"></div>';
		    $buffy .= '</div>';
	    }

        return $buffy;

    }

    function js_tdc_callback_ajax() {
        $buffy = '';

        // add a new composer block - that one has the delete callback
        $buffy .= $this->js_tdc_get_composer_block();

        ob_start();

        ?>
        <script>
            /* global jQuery:{} */
            ( function () {
                var block = jQuery('.<?php echo $this->block_uid; ?>');
                blockClass = '.<?php echo $this->block_uid; ?>';
            } )();
        </script>
        <?php

        return $buffy . td_util::remove_script_tag( ob_get_clean() );
    }

}
