<?php

/**
 * Class tdb_flex_block_builder
 */

class tdb_flex_block_builder extends td_block {

	static $posts_count = 0;



    static function cssMedia( $res_ctx ) {

        /*-- GENERAL-- */
        $res_ctx->load_settings_raw( 'style_general_tdb_flex_block_builder', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_flex_block_builder_composer', 1 );
        }



        /*-- BLOCK HEADER -- */
        // *- fonts -* //
        $res_ctx->load_font_settings( 'f_header' );
        $res_ctx->load_font_settings( 'f_ajax' );



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
        $res_ctx->load_settings_raw( 'pag_h_text', $res_ctx->get_shortcode_att('pag_h_text') );
        $res_ctx->load_settings_raw( 'pag_h_bg', $res_ctx->get_shortcode_att('pag_h_bg') );
        $res_ctx->load_settings_raw( 'pag_h_border', $res_ctx->get_shortcode_att('pag_h_border') );

        // *- fonts -* //
        $res_ctx->load_font_settings( 'f_pag' );

    }

    public function get_custom_css() {

        // $unique_block_class
        $unique_block_class = $this->block_uid;

        $compiled_css = '';

        $raw_css =
            "<style>
            
                /* @style_general_tdb_flex_block_builder */
                .tdb_flex_block_builder {
					margin-bottom: 48px;
					padding-bottom: 0;
					overflow: visible !important;
					counter-reset: numbers;
                }
                .tdb_flex_block_builder .td_block_inner {
                    display: flex;
                    flex-wrap: wrap;
                    height: 100%;
				}
				.tdb_flex_block_builder .td-module-container {
					position: relative;
					height: 100%;
					transition: background-color .2s ease-in-out, box-shadow .2s ease-in-out, border-color .2s ease-in-out;
				}
				.tdb_flex_block_builder .td-module-container:before {
					content: '';
					position: absolute;
					bottom: 0;
					left: 0;
					width: 100%;
					height: 1px;
				}
				.tdb_flex_block_builder .td_block_inner .td-module-container > [class*='tdc-zone'],
				.tdb_flex_block_builder .td_block_inner .td-module-container > [class*='tdc-zone'] > .tdc_zone {
				    height: 100%; 
				}
				.tdb_flex_block_builder .td_block_inner .td-module-container > [class*='tdc-zone'] > .tdc_zone {
                    display: flex;
                    flex-direction: column;
				}
				.tdb_flex_block_builder .td_block_inner .td_module_wrap .tdc-row:not([class*='stretch_row_']),
                .tdb_flex_block_builder .td_block_inner .td_module_wrap .tdc-row-composer:not([class*='stretch_row_'])  {
                    width: 100% !important;
                    max-width: 1240px !important;
                }
				@media (max-width: 767px) {
					.tdb_flex_block_builder .td_block_inner .td_module_wrap .td-container,
					.tdb_flex_block_builder .td_block_inner .td_module_wrap .tdc-row,
					.tdb_flex_block_builder .td_block_inner .td_module_wrap .tdc-row-composer {
						padding-left: 0;
						padding-right: 0;
					}
					.tdb_flex_block_builder .td_block_inner .td_module_wrap .td-pb-row > .td-element-style {
                        width: 100% !important;
                        left: 0 !important;
                        transform: none !important;
                    }
				}
                .tdb_flex_block_builder .td-load-more-wrap,
                .tdb_flex_block_builder .td-next-prev-wrap {
                    margin: 20px 0 0;
                }
                .tdb_flex_block_builder .td-next-prev-wrap a {
                    width: auto;
                    height: auto;
                    min-width: 25px;
                    min-height: 25px;
                }
                .tdb_flex_block_builder .td-block-missing-settings {
                    width: 100%;
                }
                .tdb_flex_block_builder .tdb-module-tpl-edit-btns {
                    position: absolute;
					top: 0;
					left: 0;
					display: none;
					flex-wrap: wrap;
					gap: 0 4px;
                }
                .tdb_flex_block_builder .tdb-module-tpl-edit-btn {
					background-color: #000;
					padding: 1px 8px 2px;
					font-size: 11px;
					color: #fff;
					z-index: 100;
				}
                .tdb_flex_block_builder .td-module-container:hover .tdb-module-tpl-edit-btns {
					display: flex;
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
				/* @modules_horiz_align */
				.$unique_block_class .td_block_inner {
					justify-content: @modules_horiz_align;
				}
				/* @modules_vert_align */
				.$unique_block_class .td_block_inner {
					align-items: @modules_vert_align;
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
				body .$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap,
				body .$unique_block_class .td-load-more-wrap {
					margin-top: @pag_space;
				}
				/* @pag_padding */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a,
				.$unique_block_class .td-load-more-wrap a {
					padding: @pag_padding;
				}
				.$unique_block_class .page-nav .pages {
				    padding-right: 0;
				}
				/* @pag_border_width */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a,
				.$unique_block_class .td-load-more-wrap a {
					border-width: @pag_border_width;
				}
				/* @pag_border_radius */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a,
				.$unique_block_class .td-load-more-wrap a {
					border-radius: @pag_border_radius;
				}
				/* @pag_icons_size */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a,
				.$unique_block_class .td-load-more-wrap a i {
					font-size: @pag_icons_size;
				}
				.$unique_block_class .td-load-more-wrap a .td-load-more-icon-svg svg,
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap .td-next-prev-icon-svg svg {
				    width: @pag_icons_size;
				    height: calc( @pag_icons_size + 1px );
				}
				
				/* @pag_text */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a,
				.$unique_block_class .td-load-more-wrap a {
					color: @pag_text;
				}
				.$unique_block_class .td-load-more-wrap a .td-load-more-icon-svg svg,
				.$unique_block_class .td-load-more-wrap a .td-load-more-icon-svg svg *,
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap .td-next-prev-icon-svg svg,
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap .td-next-prev-icon-svg svg * {
				    fill: @pag_text;
				}
				/* @pag_bg */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a,
				.$unique_block_class .td-load-more-wrap a {    
					background-color: @pag_bg;
				}
				/* @pag_border */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a,
				.$unique_block_class .td-load-more-wrap a {
					border-color: @pag_border;
				}
				/* @pag_h_text */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a:hover,
				.$unique_block_class .td-load-more-wrap a:hover {
					color: @pag_h_text;
				}
				.$unique_block_class .td-load-more-wrap a:hover .td-load-more-icon-svg svg,
				.$unique_block_class .td-load-more-wrap a:hover .td-load-more-icon-svg svg *,
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a:hover .td-next-prev-icon-svg svg,
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a:hover .td-next-prev-icon-svg svg * {
				    fill: @pag_h_text;
				}
				/* @pag_h_bg */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a:hover,
				.$unique_block_class .td-load-more-wrap a:hover {    
					background-color: @pag_h_bg;
				}
				/* @pag_h_border */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a:hover,
				.$unique_block_class .td-load-more-wrap a:hover {
					border-color: @pag_h_border;
				}
				

				/* @f_header */
				.$unique_block_class .td-block-title a,
				.$unique_block_class .td-block-title span {
					@f_header
				}
				/* @f_ajax */
				.$unique_block_class .td-subcat-list a,
				.$unique_block_class .td-subcat-dropdown span,
				.$unique_block_class .td-subcat-dropdown a {
					@f_ajax
				}
				/* @f_pag */
				.$unique_block_class.td_with_ajax_pagination .td-next-prev-wrap a i,
				.$unique_block_class .td-load-more-wrap a {
					@f_pag
				}
            
            </style>";

        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts() );

        $compiled_css .= $td_css_res_compiler->compile_css();

        return $compiled_css;
    }
	


    function render( $atts, $content = null ) {

        parent::render( $atts );

		/* -- Set the total posts count -- */
		self::$posts_count = !empty( $this->td_query->posts ) ? count( $this->td_query->posts ) : 0;


		/* -- Block atts -- */
        // Get the active module template id
        $tdb_module_template_id = $this->get_att('cloud_tpl_module_id');

        // block classes
        $block_classes = $this->get_block_classes();

        // remove tdc-no-posts class if no module template has been selected or it's not valid
        if( $tdb_module_template_id == '' || !tdb_util::is_tdb_module( 'tdb_module_' . $tdb_module_template_id, true ) ) {
            $block_classes = str_replace( 'tdc-no-posts', '', $block_classes );
        }


        /* -- Output the module element HTML -- */
	    $buffy = '<div class="' . $block_classes . ' td_flex_block" ' . $this->get_block_html_atts() . '>';

            // get the block css
            $buffy .= $this->get_block_css();

            // get the js for this block
            $buffy .= $this->get_block_js();


			// return a warning if no module template has been selected
			if( $tdb_module_template_id == '' ) {
					$buffy .= '<div id=' . $this->block_uid . ' class="td_block_inner tdb-block-inner td-fix-index">';
                        $buffy .= td_util::get_block_error(
							'Flex Block Builder',
							'Please select a Cloud Library Module Template.');
                    $buffy .= '</div>';
				$buffy .= '</div>';

				return $buffy;
			}


            // return a warning if the module template is not valid
            if( !tdb_util::is_tdb_module( 'tdb_module_' . $tdb_module_template_id, true ) ) {
					$buffy .= '<div id=' . $this->block_uid . ' class="td_block_inner tdb-block-inner td-fix-index">';
                        $buffy .= td_util::get_block_error(
							'Flex Block Builder',
							'The Cloud Library Module Template set for this block is not valid or it no longer exists. Please select another Module Template.');
                    $buffy .= '</div>';
				$buffy .= '</div>';

				return $buffy;
            }

            // return No bookmarked posts msg
            if ( isset($atts['favourite_only']) && $atts['favourite_only'] !== '' && empty(td_util::get_favourite_articles()) ) {
                td_global::$custom_no_posts_message = __td('No bookmarked posts', TD_THEME_NAME);

                    $buffy .= '<div id=' . $this->block_uid . ' class="td_block_inner tdb-block-inner td-fix-index">';
                    $buffy .= td_page_generator::no_posts();
                    $buffy .= '</div>';
                    $buffy .= '</div>';

                    return $buffy;
            }

			// add the block title
			$buffy .= '<div class="td-block-title-wrap">';
				$buffy .= $this->get_block_title(); //get the block title
				$buffy .= $this->get_pull_down_filter(); //get the sub category filter for this block
			$buffy .= '</div>';

            // in composer flag .. for debugging @todo remove
            $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();
            //if ( !$in_composer ) {
                //echo '<pre>' . print_r( $atts, true ) . '</pre>';
                //echo '<pre>' . print_r( $this->td_query, true ) . '</pre>';
                //echo '<pre>' . print_r( count( $this->td_query->posts ), true ) . '</pre>';
                //echo '<pre>' . print_r( $this->td_query->posts, true ) . '</pre>';
            //}


			// render the inner part of the block
			$buffy .= '<div id=' . $this->block_uid . ' class="td_block_inner tdb-block-inner td-fix-index">';
				$buffy .= $this->inner( $this->td_query->posts, $tdb_module_template_id );  // inner content of the block
			$buffy .= '</div>';


			// get the ajax pagination for this block
			$prev_icon = $this->get_icon_att('prev_tdicon');
			$prev_icon_class = $this->get_att('prev_tdicon');
			$next_icon = $this->get_icon_att('next_tdicon');
			$next_icon_class = $this->get_att('next_tdicon');
			$buffy .= $this->get_block_pagination( $prev_icon, $next_icon, $prev_icon_class, $next_icon_class );

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



    function inner( $posts, $tdb_module_template_id ) {

	    $buffy = '';
	    $td_block_layout = new td_block_layout();

	    if ( !empty( $posts ) ) {
		    foreach ( $posts as $post ) {
			    $tdb_module_cpt = new tdb_module_template( $post, $tdb_module_template_id, $this->get_all_atts() );
			    $buffy .= $tdb_module_cpt->render();
		    }
	    }

	    $buffy .= $td_block_layout->close_all_tags();

	    return $buffy;

    }

}
