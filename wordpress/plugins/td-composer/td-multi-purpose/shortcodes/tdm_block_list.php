<?php
class tdm_block_list extends td_block {

    protected $shortcode_atts = array(); //the atts used for rendering the current block
    static $list_items_count = 0;

    static $style_selector = '';
	static $style_atts_prefix = '';
	static $style_atts_uid = '';
	static $module_template_part_index = '';


    function __construct() {

        /**
         * Disable loop block features. This block does not use a loop and it dosn't need to run a query.
         */
        parent::disable_loop_block_features();


        /* --
        -- Check to see if the element is being called into a tdb module template
        -- */
        if( td_global::get_in_tdb_module_template() ) {

            global $tdb_module_template_params;

            /* -- Set the current module template part index, used for ensuring -- */
		    /* -- uniqueness between template parts of the same type -- */
            if( isset( $tdb_module_template_params['shortcodes'][get_class($this)] ) ) {
                $tdb_module_template_params['shortcodes'][get_class($this)]++;
            } else {
                $tdb_module_template_params['shortcodes'][get_class($this)] = 0;
            }

            self::$module_template_part_index = $tdb_module_template_params['shortcodes'][get_class($this)];

            // In composer, add an extra random string to ensure uniqueness
            if( tdc_state::is_live_editor_ajax() || tdc_state::is_live_editor_iframe() || is_admin() ) {
                $uniquid = uniqid();
                $newuniquid = '';
                while ( strlen( $newuniquid ) < 3 ) {
                    $newuniquid .= $uniquid[rand(0, 12)];
                }

                self::$module_template_part_index .= '_' . $newuniquid;
            }


            /* -- Set the template part unique style vars -- */
            // Set the style atts prefix
            self::$style_atts_prefix = 'tdb_mts_';

            // Set the style atts uid
            self::$style_atts_uid = $tdb_module_template_params['template_class'] . '_' . get_class($this) . '_' . self::$module_template_part_index;

        } else {

	        // reset static properties
	        self::$style_selector = '';
	        self::$style_atts_prefix = '';
	        self::$style_atts_uid = '';
	        self::$module_template_part_index = '';

        }

    }


    public function get_custom_css() {

		$style_atts_prefix = self::$style_atts_prefix;
		$style_atts_uid = self::$style_atts_uid;

        /* -- Set the style selector -- */
        $style_selector = '';

        $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();
        $in_element = td_global::get_in_element();
		if( $in_element && $in_composer ) {
			$style_selector .= 'tdc-row-composer .';
		} else if( $in_element || $in_composer ) {
			$style_selector .= 'tdc-row .';
		}

        // Check to see if the element is being called into a tdb module template
        if( td_global::get_in_tdb_module_template() ) {
            global $tdb_module_template_params;

            $style_selector = $tdb_module_template_params['template_class'] . ' .' . $style_selector .  get_class($this) . '_' . self::$module_template_part_index;
        } else {
            $style_selector .= $this->block_uid;
        }
        
        $compiled_css = '';

        $raw_css =
            "<style>
            
				/* @style_general_list */
				.tdm_block_list .tdm-list-items {
				    display: flex;
				    flex-wrap: wrap;
                    margin: 0;
                    font-family: var(--td_default_google_font_1, 'Open Sans', 'Open Sans Regular', sans-serif);
                    font-size: 15px;
                    line-height: 24px;
                    color: #666;
                }
                .tdm_block_list.tdm-content-horiz-center .tdm-list-items {
                    align-items: center;
                }
                .tdm_block_list.tdm-content-horiz-right .tdm-list-items {
                    align-items: flex-end;
                }
                .tdm_block_list .tdm-list-item {
                    margin-bottom: 8px;
                    margin-left: 0;
                }
                .tdm_block_list .tdm-list-item:after {
                    content: '';
                    display: table;
                    clear: both;
                }
                .tdm_block_list .tdm-list-item .tdm-list-icon {
                    vertical-align: middle;
                }
                .tdm_block_list .tdm-list-item i {
                    position: relative;
                    float: left;
                    line-height: inherit;
                    vertical-align: middle;
                    color: var(--td_theme_color, #4db2ec);
                }
                .tdm_block_list .tdm-list-item .tdm-list-icon-svg {
                    margin-top: -3px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }
                .tdm_block_list .tdm-list-item svg {
                    width: 15px;
                    height: auto;
                }
                .tdm_block_list .tdm-list-item svg,
                .tdm_block_list .tdm-list-item svg * {
                    fill: var(--td_theme_color, #4db2ec);
                }
                .tdm_block_list.tdm-list-with-icons .tdm-list-item {
                    list-style-type: none;
                }



                /* @" . $style_atts_prefix . "columns$style_atts_uid */
                .$style_selector .tdm-list-item {
                    width: @" . $style_atts_prefix . "columns$style_atts_uid;
                }

                /* @" . $style_atts_prefix . "remove_space$style_atts_uid */
                .$style_selector .tdm-list-item {
                    margin-bottom: @" . $style_atts_prefix . "all_space$style_atts_uid !important;
                }
                .$style_selector .tdm-list-item:nth-last-child(@" . $style_atts_prefix . "remove_space$style_atts_uid) {
                    margin-bottom: 0 !important;
                }
                /* @" . $style_atts_prefix . "remove_space_desktop$style_atts_uid */
                .$style_selector .tdm-list-item:nth-last-child(@" . $style_atts_prefix . "remove_space_desktop$style_atts_uid) {
                    margin-bottom: 0;
                }
                
                /* @" . $style_atts_prefix . "all_space$style_atts_uid */
                .$style_selector .tdm-list-item {
                    margin-bottom: @" . $style_atts_prefix . "all_space$style_atts_uid;
                }

                /* @" . $style_atts_prefix . "keep_aligned$style_atts_uid */
                .$style_selector .tdm-list-item {
                    display: flex;
                }

                /* @" . $style_atts_prefix . "horizontal_align_left$style_atts_uid */
                .$style_selector {
                    text-align: left;
                }
                /* @" . $style_atts_prefix . "horizontal_align_center$style_atts_uid */
                .$style_selector {
                    text-align: center;
                }
                /* @" . $style_atts_prefix . "horizontal_align_right$style_atts_uid */
                .$style_selector {
                    text-align: right;
                }
                /* @" . $style_atts_prefix . "horizontal_align_left_flex$style_atts_uid */
                .$style_selector .tdm-list-item {
                    justify-content: flex-start;
                }
                /* @" . $style_atts_prefix . "horizontal_align_center_flex$style_atts_uid */
                .$style_selector .tdm-list-item {
                    justify-content: center;
                }
                /* @" . $style_atts_prefix . "horizontal_align_right_flex$style_atts_uid */
                .$style_selector .tdm-list-item {
                    justify-content: flex-end;
                }

				
				/* @" . $style_atts_prefix . "text_color$style_atts_uid */
				.$style_selector .tdm-list-text,
				.$style_selector .tdm-list-text a {
				    color: @" . $style_atts_prefix . "text_color$style_atts_uid;
				}
				
				/* @" . $style_atts_prefix . "icon_color$style_atts_uid */
				.$style_selector .tdm-list-item i {
				    color: @" . $style_atts_prefix . "icon_color$style_atts_uid;
				}
				.$style_selector .tdm-list-item svg,
				.$style_selector .tdm-list-item svg * {
				    fill: @" . $style_atts_prefix . "icon_color$style_atts_uid;
				}

				/* @" . $style_atts_prefix . "hover_text_color$style_atts_uid */
				.$style_selector .tdm-list-item:hover .tdm-list-text,
				.$style_selector .tdm-list-item:hover a {
				    color: @" . $style_atts_prefix . "hover_text_color$style_atts_uid;
				}

				/* @" . $style_atts_prefix . "hover_icon_color$style_atts_uid */
				.$style_selector .tdm-list-item:hover i {
				    color: @" . $style_atts_prefix . "hover_icon_color$style_atts_uid;
				}
				
				/* @" . $style_atts_prefix . "icon_size$style_atts_uid */
				.$style_selector .tdm-list-item i {
				    font-size: @" . $style_atts_prefix . "icon_size$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "icon_svg_size$style_atts_uid */
				.$style_selector .tdm-list-item svg {
					width: @" . $style_atts_prefix . "icon_svg_size$style_atts_uid;
                    height: auto;
				}
				
				/* @" . $style_atts_prefix . "icon_align$style_atts_uid */
				.$style_selector .tdm-list-item .tdm-list-icon {
				    top: @" . $style_atts_prefix . "icon_align$style_atts_uid;
				}
				
				/* @" . $style_atts_prefix . "icon_space$style_atts_uid */
				.$style_selector .tdm-list-item .tdm-list-icon {
				    margin-right: @" . $style_atts_prefix . "icon_space$style_atts_uid;
				}



				/* @" . $style_atts_prefix . "f_list$style_atts_uid */
				.$style_selector .tdm-list-item {
					@" . $style_atts_prefix . "f_list$style_atts_uid
				}

			</style>";

        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->shortcode_atts);

        $compiled_css .= $td_css_res_compiler->compile_css();
        return $compiled_css;

    }


    /**
     * Callback pe media
     *
     * @" . $style_atts_prefix . "param $responsive_context td_res_context
     * @" . $style_atts_prefix . "param $atts
     */
    static function cssMedia( $res_ctx ) {

		$style_atts_prefix = self::$style_atts_prefix;
		$style_atts_uid = self::$style_atts_uid;

        $res_ctx->load_settings_raw( 'style_general_list', 1 );

        // columns
        $columns = $res_ctx->get_shortcode_att('columns');
        if ( $columns == '' ) {
            $columns = '100%';
        }
        $res_ctx->load_settings_raw( $style_atts_prefix . 'columns' . $style_atts_uid, $columns );

        $columns_number = str_replace('%', '', $columns);
        $modulo_columns = (int)self::$list_items_count % intval( ( 100 / intval( $columns_number ) ) );

        $remove_space = $style_atts_prefix . 'remove_space' . $style_atts_uid;
        if ( $res_ctx->is( 'all' ) ) {
            $remove_space = $style_atts_prefix . 'remove_space_desktop' . $style_atts_uid;
        }

        switch ($modulo_columns) {
            case '0':
                $res_ctx->load_settings_raw( $remove_space,  '-n+' . intval( 100 / intval( $columns_number ) ) );
                break;
            case '1':
                $res_ctx->load_settings_raw( $remove_space,  '1' );
                break;
            case '2':
                $res_ctx->load_settings_raw( $remove_space,  '-n+2' );
                break;
            case '3':
                $res_ctx->load_settings_raw( $remove_space,  '-n+3' );
                break;
            case '4':
                $res_ctx->load_settings_raw( $remove_space,  '-n+4' );
                break;
            case '5':
                $res_ctx->load_settings_raw( $remove_space,  '-n+5' );
                break;
            case '6':
                $res_ctx->load_settings_raw( $remove_space,  '-n+6' );
                break;
            case '7':
                $res_ctx->load_settings_raw( $remove_space,  '-n+7' );
                break;
            case '8':
                $res_ctx->load_settings_raw( $remove_space,  '-n+8' );
                break;
        }

        // Items bottom space
        $all_space = $res_ctx->get_shortcode_att('all_space');
        $all_space = $all_space != '' ? $all_space : '8px';
        $all_space .= is_numeric( $all_space ) ? 'px' : '';
        $res_ctx->load_settings_raw( 'all_space', $all_space );

        // Keep icon and text aligned
        $keep_aligned = $res_ctx->get_shortcode_att('keep_aligned') != '';
        $res_ctx->load_settings_raw($style_atts_prefix . 'keep_aligned' . $style_atts_uid, $keep_aligned);

        // Horizontal align
        $content_align_horizontal = $res_ctx->get_shortcode_att('content_align_horizontal');
        $content_align_horizontal = !empty($content_align_horizontal) ? $content_align_horizontal : 'content-horiz-left';

        switch ( $content_align_horizontal ) {
            case 'content-horiz-left':
                if ( $keep_aligned ) {
                    $res_ctx->load_settings_raw($style_atts_prefix . 'horizontal_align_left_flex' . $style_atts_uid, 1);
                } else {
                    $res_ctx->load_settings_raw($style_atts_prefix . 'horizontal_align_left' . $style_atts_uid, 1);
                }
                break;

            case 'content-horiz-center':
                if ( $keep_aligned ) {
                    $res_ctx->load_settings_raw($style_atts_prefix . 'horizontal_align_center_flex' . $style_atts_uid, 1);
                } else {
                    $res_ctx->load_settings_raw($style_atts_prefix . 'horizontal_align_center' . $style_atts_uid, 1);
                }
                break;

            case 'content-horiz-right':
                if ( $keep_aligned ) {
                    $res_ctx->load_settings_raw($style_atts_prefix . 'horizontal_align_right_flex' . $style_atts_uid, 1);
                } else {
                    $res_ctx->load_settings_raw($style_atts_prefix . 'horizontal_align_right' . $style_atts_uid, 1);
                }
                break;
        }


        /*-- TEXT -- */
        // text color
        $res_ctx->load_settings_raw( $style_atts_prefix . 'text_color' . $style_atts_uid, $res_ctx->get_shortcode_att( 'text_color' ) );

        // text hover color
        $res_ctx->load_settings_raw( $style_atts_prefix . 'hover_text_color' . $style_atts_uid, $res_ctx->get_shortcode_att( 'hover_text_color' ) );


        /*-- ICON -- */
        // icon size
        $icon = $res_ctx->get_icon_att('tdicon' );
        $icon_size = $res_ctx->get_shortcode_att( 'icon_size' );
        $icon_size .= $icon_size != '' && is_numeric( $icon_size ) ? 'px' : '';
        if( base64_encode( base64_decode( $icon ) ) == $icon ) {
            $res_ctx->load_settings_raw($style_atts_prefix . 'icon_svg_size' . $style_atts_uid, $icon_size);
        } else {
            $res_ctx->load_settings_raw($style_atts_prefix . 'icon_size' . $style_atts_uid, $icon_size);
        }

        // icon_align
        $icon_align = $res_ctx->get_shortcode_att('icon_align');
        if ( $icon_align != 0 || !empty($icon_align) ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_align', $icon_align . 'px' );
        }

        // icon space
        $icon_space = $res_ctx->get_shortcode_att( 'icon_space' );
        $icon_space = $icon_space != '' ? $icon_space : '11px';
        $icon_space .= is_numeric( $icon_space ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_space' . $style_atts_uid, $icon_space );

        // icon color
        $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_color' . $style_atts_uid, $res_ctx->get_shortcode_att( 'icon_color' ) );

        // icon hover color
        $res_ctx->load_settings_raw( $style_atts_prefix . 'hover_icon_color' . $style_atts_uid, $res_ctx->get_shortcode_att( 'hover_icon_color' ) );


        /*-- FONTS -- */
        $res_ctx->load_font_settings( 'f_list', '', $style_atts_prefix, $style_atts_uid );

    }



    function render($atts, $content = null) {
        parent::render($atts);

        $this->shortcode_atts = shortcode_atts(
			array_merge(
				td_api_multi_purpose::get_mapped_atts( __CLASS__ ))
			, $atts);

        $additional_classes = array();


        // Items
        $items = td_util::get_custom_field_value_from_string( rawurldecode( base64_decode( strip_tags( $this->get_shortcode_att( 'items' ) ) ) ) );
        $items = td_util::get_cloud_tpl_var_value_from_string( $items );
        $items = explode( "\n", $items );

        self::$list_items_count = count( $items );


        // Content align horizontal
	    // $content_align_horizontal = $this->get_shortcode_att( 'content_align_horizontal' );
        // if ( ! empty( $content_align_horizontal ) ) {
        //     $additional_classes[] = 'tdm-' . $content_align_horizontal;
        // }

        // Check to see if the element is being called into a tdb module template
        if( td_global::get_in_tdb_module_template() ) {
            $additional_classes[] = get_class($this) . '_' . self::$module_template_part_index;
        }


        // Icon
        $icon = $this->get_icon_att( 'tdicon' );

        $buffy_icon = '';
        if ( !empty( $icon ) ) {
            if( base64_encode( base64_decode( $icon ) ) == $icon ) {
                $buffy_icon .= '<span class="tdm-list-icon tdm-list-icon-svg">' . base64_decode( $icon ) . '</span>';
            } else {
                $buffy_icon .= '<i class="tdm-list-icon ' . $icon . '"></i>';
            }
        }


        $buffy = '';

        $buffy .= '<div class="tdm_block ' . $this->get_block_classes($additional_classes) . ' tdm-list-with-icons" ' . $this->get_block_html_atts() . '>';

            //get the block css
            $buffy .= $this->get_block_css();

            $buffy .= '<div class="tdm-col td-fix-index">';
                if ( ! empty( $items ) ) {
                    $buffy .= '<ul class="tdm-list-items">';
                        foreach( $items as $item ) {
                            $buffy .= '<li class="tdm-list-item">';
                                if ( !empty( $buffy_icon ) ) {
                                    $buffy .= $buffy_icon;
                                }
                                $buffy .= '<span class="tdm-list-text">' . $item . '</span>';
                            $buffy .= '</li>';
                        }
                    $buffy .= '</ul>';
                }
            $buffy .= '</div>';

        $buffy .= '</div>';

        return $buffy;
    }
}