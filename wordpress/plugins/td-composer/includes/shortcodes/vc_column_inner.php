<?php
/**
 * Created by PhpStorm.
 * User: tagdiv
 * Date: 16.02.2016
 * Time: 13:55
 */

class vc_column_inner extends tdc_composer_block {

    private $atts;

    public function get_custom_css() {
        // $unique_block_class - the unique class that is on the block. use this to target the specific instance via css
        $unique_block_class = $this->get_att('tdc_css_class');

        $compiled_css = '';

        $raw_css =
            "<style>

                /* @vertical_align */
                .$unique_block_class {
                    vertical-align: @vertical_align;
                }
                
                /* @col_bg_solid */
                 .$unique_block_class > .td-element-style:after {
                    content: '' !important;
                    width: 100% !important;
                    height: 100% !important;
                    position: absolute !important;
                    top: 0 !important;
                    left: 0 !important;
                    z-index: 0 !important;
                    display: block !important;
                    background-color: @col_bg_solid !important;
                }
                /* @col_bg_gradient */
                 .$unique_block_class > .td-element-style:after {
                    content: '' !important;
                    width: 100% !important;
                    height: 100% !important;
                    position: absolute !important;
                    top: 0 !important;
                    left: 0 !important;
                    z-index: 0 !important;
                    display: block !important;
                    @col_bg_gradient;
                }
                
                /* @column_height */
                .$unique_block_class .vc_column-inner > .wpb_wrapper,
				.$unique_block_class .vc_column-inner > .wpb_wrapper .tdc-elements {
                    min-height: @column_height;
                }
				
				
				/* @flex_display */
				.$unique_block_class .vc_column-inner > .wpb_wrapper,
				.$unique_block_class .vc_column-inner > .wpb_wrapper .tdc-elements {
				    display: @flex_display;
				}
				.$unique_block_class .vc_column-inner > .wpb_wrapper .tdc-elements {
				    width: 100%;
				}
				/* @flex_layout */
				.$unique_block_class .vc_column-inner > .wpb_wrapper,
				.$unique_block_class .vc_column-inner > .wpb_wrapper .tdc-elements {
				    flex-direction: @flex_layout;
				}
				/* @flex_full_height */
				.$unique_block_class .vc_column-inner,
				.$unique_block_class .vc_column-inner > .wpb_wrapper {
				    width: 100%;
				    height: 100%;
				}
				/* @flex_auto_height */
				.$unique_block_class .vc_column-inner > .wpb_wrapper {
				    width: auto;
				    height: auto;
				}
				/* @flex_wrap */
				.$unique_block_class .vc_column-inner > .wpb_wrapper,
				.$unique_block_class .vc_column-inner > .wpb_wrapper .tdc-elements {
				    flex-wrap: @flex_wrap;
				}
				/* @flex_horiz_align */
				.$unique_block_class .vc_column-inner > .wpb_wrapper,
				.$unique_block_class .vc_column-inner > .wpb_wrapper .tdc-elements {
				    justify-content: @flex_horiz_align;
				}
				/* @flex_vert_align */
				.$unique_block_class .vc_column-inner > .wpb_wrapper,
				.$unique_block_class .vc_column-inner > .wpb_wrapper .tdc-elements {
				    align-items: @flex_vert_align;
				}
				/* @flex_order_0 */
				.$unique_block_class {
				    order: 0;
				}
				/* @flex_order */
				.$unique_block_class {
				    order: @flex_order;
				}
				/* @flex_width */
				div.$unique_block_class {
				    width: @flex_width !important;
				}
				/* @flex_grow_enable */
				.$unique_block_class {
				    flex-grow: 1;
				}
				/* @flex_grow_disable */
				.$unique_block_class {
				    flex-grow: 0;
				}

			</style>";


        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->atts );

        $compiled_css .= $td_css_res_compiler->compile_css();
        return $compiled_css;
    }

    static function cssMedia( $res_ctx ) {

        // vertical align
        $vertical_align = $res_ctx->get_shortcode_att('vertical_align');
        $res_ctx->load_settings_raw( 'vertical_align', 'baseline' );
        if( $vertical_align != '' ) {
            $res_ctx->load_settings_raw('vertical_align', $vertical_align);
        }

        // background gradient
        $res_ctx->load_color_settings( 'col_bg_gradient', 'col_bg_solid', 'col_bg_gradient', '', '' );

        // height
        $column_height = $res_ctx->get_shortcode_att('column_height');
        $res_ctx->load_settings_raw( 'column_height', $column_height );
        if( $column_height != '' && is_numeric( $column_height ) ) {
            $res_ctx->load_settings_raw( 'column_height', $column_height . 'px' );
        }



        /*-- FLEX SETTINGS -- */
        if( 'Newspaper' === TD_THEME_NAME ) {
            $flex_layout = $res_ctx->get_shortcode_att('flex_layout');

            if ($flex_layout != 'block') {

                $res_ctx->load_settings_raw('flex_display', 'flex');

                // layout reverse
                $flex_layout_reverse = $res_ctx->get_shortcode_att('flex_layout_reverse');
                if ($flex_layout_reverse != '') {
                    if ($flex_layout == 'row') {
                        $res_ctx->load_settings_raw('flex_layout', 'row-reverse');
                    } else if ($flex_layout == 'column') {
                        $res_ctx->load_settings_raw('flex_layout', 'column-reverse');
                    }
                } else {
                    if ($flex_layout == 'row') {
                        $res_ctx->load_settings_raw('flex_layout', 'row');
                    } else if ($flex_layout == 'column') {
                        $res_ctx->load_settings_raw('flex_layout', 'column');
                    }
                }
                if ($flex_layout != 'block') {
                    $res_ctx->load_settings_raw('flex_full_height', 1);
                } else {
                    $res_ctx->load_settings_raw('flex_auto_height', 1);
                }

                // flex wrap
                $flex_wrap = $res_ctx->get_shortcode_att('flex_wrap');
                if ($flex_wrap == '') {
                    $res_ctx->load_settings_raw('flex_wrap', 'nowrap');
                } else {
                    $res_ctx->load_settings_raw('flex_wrap', 'wrap');
                }


                // horizontal align
                $flex_horizontal_align = $res_ctx->get_shortcode_att('flex_horiz_align');
                $res_ctx->load_settings_raw('flex_horiz_align', $flex_horizontal_align);

                // vertical align
                $flex_vertical_align = $res_ctx->get_shortcode_att('flex_vert_align');
                $res_ctx->load_settings_raw('flex_vert_align', $flex_vertical_align);

            } else {
                $res_ctx->load_settings_raw('flex_display', 'block');
            }

            // order
            $flex_order = $res_ctx->get_shortcode_att('flex_order');
            if ($flex_order != '' && is_numeric($flex_order)) {
                if ($flex_order == '0') {
                    $res_ctx->load_settings_raw('flex_order_0', 1);
                } else {
                    $res_ctx->load_settings_raw('flex_order', $flex_order);
                }
            }

            // width
            $flex_width = $res_ctx->get_shortcode_att('flex_width');
            $res_ctx->load_settings_raw('flex_width', $flex_width);
            if ($flex_width != '' && is_numeric($flex_width)) {
                $res_ctx->load_settings_raw('flex_width', $flex_width . 'px');
            }

            // grow
            $flex_grow = $res_ctx->get_shortcode_att('flex_grow');
            if ($flex_grow == 'on') {
                $res_ctx->load_settings_raw('flex_grow_enable', 1);
            } else if ($flex_grow == 'off') {
                $res_ctx->load_settings_raw('flex_grow_disable', 1);
            }
        }

    }

	function render($atts, $content = null) {
		parent::render($atts);

        $this->atts = shortcode_atts( array(
			'width' => '1/1',
            'is_sticky' => '',
            'sticky_offset' => '',
            'vertical_align' => '',
            'col_bg_gradient' => '',
            'column_height' => '',

            'flex_layout' => 'block',
            'flex_layout_reverse' => '',
            'flex_wrap' => '',
            'flex_horiz_align' => 'flex-start',
            'flex_vert_align' => 'flex-start',
            'flex_order' => '',
            'flex_width' => '',
            'flex_grow' => '',

            'hide_for_user_type' => '',
            'logged_plan_id' => '',
            'author_plan_id' => '',

            'tdc_css' => ''
		), $atts);


		// Set inner column width
		// For 'page_title_sidebar' template, modify the $atts['width']
		if ($this->atts['width'] === '1/1') {
			td_global::set_inner_column_width($this->atts['width']);
		} else {
			td_global::set_inner_column_width($this->atts['width']);
		}


		$td_pb_class = '';

		switch ($this->atts['width']) {
			case '1/1': //full
				$td_pb_class = 'td-pb-span12';
				break;
			case '2/3': //2 of 3
				$td_pb_class = 'td-pb-span8';
				break;
			case '1/3': // 1 of 3
				$td_pb_class = 'td-pb-span4';
				break;
			case '1/2': // 1 of 2
				$td_pb_class = 'td-pb-span6';
				break;
			case '1/4': // 1 of 4
				$td_pb_class = 'td-pb-span3';
				break;
			case '3/4': // 3 of 4
				$td_pb_class = 'td-pb-span9';
				break;
			case '7': // 7 of 12
				$td_pb_class = 'td-pb-span7';
				break;
			case '5': // 5 of 12
				$td_pb_class = 'td-pb-span5';
				break;
		}

		$inner_column_class = 'tdc-inner-column';

		if ( td_global::get_in_element() && ( tdc_state::is_live_editor_ajax() || tdc_state::is_live_editor_iframe() ) ) {
		    $inner_column_class .= '-composer';
        }


        /* --
        -- Sticky sidebar.
        -- */
        $is_sticky_param = $this->atts['is_sticky'];
        $is_sticky = false;
        $sticky_enabled_on = array(false, false, false, false);
        $sticky_offset = '20';
        $sticky_is_width_auto = array(false, false, false, false);

        if ( !empty( $is_sticky_param ) ) {
			$td_pb_class .= ' td-is-sticky';

            /* -- Determine on which viewports the sticky functionality -- */
            /* -- should be enabled on. -- */
            $sticky_enabled_on = array(true, true, true, true);

            if( td_util::is_base64($is_sticky_param) ) {
                $is_sticky_param_decoded = json_decode( base64_decode( $is_sticky_param ) );

                if( !empty( $is_sticky_param_decoded ) ) {
                    if ( property_exists($is_sticky_param_decoded, 'all' ) ) {
                        $sticky_enabled_on[3] = $is_sticky_param_decoded->all === 'yes';
                    }
                    if ( property_exists($is_sticky_param_decoded, 'landscape') ) {
                        $sticky_enabled_on[2] = $is_sticky_param_decoded->landscape === 'yes';
                    }
                    if ( property_exists($is_sticky_param_decoded, 'portrait') ) {
                        $sticky_enabled_on[1] = $is_sticky_param_decoded->portrait === 'yes';
                    }
                    if ( property_exists($is_sticky_param_decoded, 'phone') ) {
                        $sticky_enabled_on[0] = $is_sticky_param_decoded->phone === 'yes';
                    }
                }
            }

            $sticky_enabled_on = base64_encode( json_encode( $sticky_enabled_on ) );

            /* -- Offset. -- */
            if ( !empty( $this->atts['sticky_offset'] ) ) {
                $sticky_offset = $this->atts['sticky_offset'];
            }

            /* -- Check if the column has width 'auto' on any of the viewports. -- */
            // First check for the 'width' option in the 'CSS' tab.
            $tdc_css = $this->atts['tdc_css'];
            $tdc_css_decoded = json_decode( base64_decode( $tdc_css ) );
            if( !empty( $tdc_css_decoded ) ) {
                if( property_exists($tdc_css_decoded, 'all') && property_exists($tdc_css_decoded->all, 'width') && $tdc_css_decoded->all->width === 'auto' ) {
                    $sticky_is_width_auto = array(true, true, true, true);
                }
                if( property_exists($tdc_css_decoded, 'landscape') && property_exists($tdc_css_decoded->landscape, 'width') ) {
                    $sticky_is_width_auto[2] = $tdc_css_decoded->landscape->width === 'auto';
                }
                if( property_exists($tdc_css_decoded, 'portrait') && property_exists($tdc_css_decoded->portrait, 'width') ) {
                    $sticky_is_width_auto[1] = $tdc_css_decoded->portrait->width === 'auto';
                }
                if( property_exists($tdc_css_decoded, 'phone') && property_exists($tdc_css_decoded->phone, 'width') ) {
                    $sticky_is_width_auto[0] = $tdc_css_decoded->phone->width === 'auto';
                }
            }

            // Next check for the 'Flex width' option, which should have higher
            // priority than the previous one.
            $flex_width = $this->atts['flex_width'];
            if( !empty( $flex_width ) ) {
                if( $flex_width === 'auto' ) {
                    $sticky_is_width_auto = array(true, true, true, true);
                } else {
                    $flex_width_decoded = json_decode( base64_decode( $flex_width ) );

                    if( !empty( $flex_width_decoded ) ) {
                        if (property_exists($flex_width_decoded, 'all') && $flex_width_decoded->all === 'auto') {
                            $sticky_is_width_auto = array(true, true, true, true);
                        }
                        if (property_exists($flex_width_decoded, 'landscape')) {
                            $sticky_is_width_auto[2] = $flex_width_decoded->landscape === 'auto';
                        }
                        if (property_exists($flex_width_decoded, 'portrait')) {
                            $sticky_is_width_auto[1] = $flex_width_decoded->portrait === 'auto';
                        }
                        if (property_exists($flex_width_decoded, 'phone')) {
                            $sticky_is_width_auto[0] = $flex_width_decoded->phone === 'auto';
                        }
                    }
                }
            }

            $sticky_is_width_auto = base64_encode( json_encode( $sticky_is_width_auto ) );

            /* -- Set the $is_sticky flag. -- */
            $is_sticky = true;

            if( TD_THEME_NAME == "Newspaper" ) {
                td_resources_load::render_script( TDC_SCRIPTS_URL . '/tdSmartSidebar.js' . TDC_SCRIPTS_VER, 'tdSmartSidebar-js', '', 'footer');
            }
		}

        // display restrictions
        $hide_for_user_type = $this->atts['hide_for_user_type'];
        if( $hide_for_user_type != '' ) {
            if( !( td_util::tdc_is_live_editor_ajax() || td_util::tdc_is_live_editor_iframe() ) &&
                (
                    ( $hide_for_user_type == 'logged-in' && is_user_logged_in() ) ||
                    ( $hide_for_user_type == 'guests' && !is_user_logged_in() )
                )
            ) {
                $inner_column_class .= ' tdc-restr-display-none';
            }
        } else {
            $author_plan_ids = $this->atts['author_plan_id'];
            $all_users_plan_ids = $this->atts['logged_plan_id'];

            if( !td_util::plan_limit($author_plan_ids, $all_users_plan_ids) ) {
                $inner_column_class .= ' tdc-restr-display-none';
            }
        }

        td_global::set_in_inner_column( true );


		$buffy = '<div class="' . $this->get_block_classes(array('wpb_column', 'vc_column_container', $inner_column_class, $td_pb_class)) . '">';

		// get_block_css is out of wpb_wrapper for FF
		$buffy .= $this->get_block_css();

			$buffy .= '<div class="vc_column-inner">'; // requiered to maintain vc compatibility
            $buffy .= '<div class="wpb_wrapper" ' .
            (
            ( $is_sticky && TD_THEME_NAME == 'Newspaper' )
                ? 'data-sticky-enabled-on="' . $sticky_enabled_on . '" data-sticky-offset="' . $sticky_offset . '" data-sticky-is-width-auto="' . $sticky_is_width_auto . '"'
                : ''
            )
            . '>';
                    $buffy .= $this->do_shortcode($content);
				$buffy .= '</div>';
			$buffy .= '</div>';
		$buffy .= '</div>';

		td_global::set_in_inner_column( false );


//		if (tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax()) {
//			$buffy = '<div class="tdc-inner-column">' . $buffy . '</div>';
//		}

		return $buffy;
	}
}