<?php
class tdm_block_icon extends td_block {

    protected $shortcode_atts = array(); //the atts used for rendering the current block
	private $unique_block_class;

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

        }  else {

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
			
			    /* @style_general_icon */
			    .tdm_block_icon.tdm_block {
                  margin-bottom: 0;
                }
                .tds-icon {
                  position: relative;
                }
                .tds-icon:after {
                  content: '';
                  width: 100%;
                  height: 100%;
                  left: 0;
                  top: 0;
                  position: absolute;
                  z-index: -1;
                  opacity: 0;
                  -webkit-transition: opacity 0.3s ease;
                  transition: opacity 0.3s ease;
                }
                .tds-icon svg {
                  display: block;
                }
			    
			    /* @" . $style_atts_prefix . "icon_size$style_atts_uid */
				.$style_selector i {
				    font-size: @" . $style_atts_prefix . "icon_size$style_atts_uid;
				    text-align: center;
				}
				/* @" . $style_atts_prefix . "svg_size$style_atts_uid */
                .$style_selector svg {
                    width: @" . $style_atts_prefix . "svg_size$style_atts_uid;
                    height: auto;
                }
				
				/* @" . $style_atts_prefix . "icon_spacing$style_atts_uid */
				.$style_selector i {
				    width: @" . $style_atts_prefix . "icon_spacing$style_atts_uid;
				    height: @" . $style_atts_prefix . "icon_spacing$style_atts_uid;
				    line-height: @" . $style_atts_prefix . "icon_line_height$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "svg_spacing$style_atts_uid */
				.$style_selector .tds-icon-svg-wrap {
				    width: @" . $style_atts_prefix . "svg_spacing$style_atts_uid;
				    height: @" . $style_atts_prefix . "svg_spacing$style_atts_uid;
				    display: flex;
                    align-items: center;
                    justify-content: center;
				}
				
				/* @" . $style_atts_prefix . "vert_align$style_atts_uid */
				.$style_selector i,
				.$style_selector .tds-icon-svg-wrap {
				    position: relative;
				    top: @" . $style_atts_prefix . "vert_align$style_atts_uid;
				}
				
				/*@" . $style_atts_prefix . "icon_display$style_atts_uid */
				.$style_selector {
				    display: inline-block;
				}
				
				/* @" . $style_atts_prefix . "content_align_horizontal_center$style_atts_uid */
				.$style_selector .tds-icon-svg-wrap {
				    margin: 0 auto;
				}
				/* @" . $style_atts_prefix . "content_align_horizontal_right$style_atts_uid */
				.$style_selector .tds-icon-svg-wrap {
				    margin-left: auto;
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
     * @" . $style_atts_prefix . "param $res_ctx td_res_context
     */
    static function cssMedia( $res_ctx ) {

		$style_atts_prefix = self::$style_atts_prefix;
		$style_atts_uid = self::$style_atts_uid;


        //$res_ctx->load_settings_raw( $style_atts_prefix . 'style_general_icon', 1 );
        $res_ctx->load_settings_raw( 'style_general_icon', 1 );

        $icon = $res_ctx->get_icon_att( 'tdicon_id' );
        $svg_code = rawurldecode( base64_decode( strip_tags( $res_ctx->get_shortcode_att('svg_code') ) ) );

        /*-- ICON -- */
        // icon size
        $icon_size = $res_ctx->get_shortcode_att( 'icon_size' ) . 'px';
        if( $svg_code != '' || base64_encode( base64_decode( $icon ) ) == $icon ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'svg_size' . $style_atts_uid, $icon_size );
        } else {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_size' . $style_atts_uid, $icon_size );
        }

        // icon spacing
	    $tds_icon = td_util::get_option( 'tds_icon', 'tds_icon1' );
        $icon_spacing = $res_ctx->get_shortcode_att( 'icon_size' ) * $res_ctx->get_shortcode_att( 'icon_spacing' ) + intval($res_ctx->get_style_att( 'all_border_size', $tds_icon ) ) * 2 . 'px';
        if( $svg_code != '' || base64_encode( base64_decode( $icon ) ) == $icon ) {
            $res_ctx->load_settings_raw($style_atts_prefix . 'svg_spacing' . $style_atts_uid, $icon_spacing);
        } else {
            $res_ctx->load_settings_raw($style_atts_prefix . 'icon_spacing' . $style_atts_uid, $icon_spacing);
        }

        // icon line height
        $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_line_height' . $style_atts_uid, $res_ctx->get_shortcode_att( 'icon_size' ) * $res_ctx->get_shortcode_att( 'icon_spacing' ) . 'px' );

        // icon vertical align
        $res_ctx->load_settings_raw( $style_atts_prefix . 'vert_align' . $style_atts_uid, $res_ctx->get_shortcode_att( 'vert_align' ) . 'px' );

        // icon display
        $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_display' . $style_atts_uid, $res_ctx->get_shortcode_att( 'icon_display' ) );

        // content horiz align
        $content_horiz_align = $res_ctx->get_shortcode_att( 'content_align_horizontal' );
        if( $content_horiz_align == 'content-horiz-center' ) {
            $res_ctx->load_settings_raw($style_atts_prefix . 'content_align_horizontal_center' . $style_atts_uid, 1);
        } else if ( $content_horiz_align == 'content-horiz-right' ) {
            $res_ctx->load_settings_raw($style_atts_prefix . 'content_align_horizontal_right' . $style_atts_uid, 1);
        }

    }


    function render($atts, $content = null) {
        parent::render($atts);

	    // $unique_block_class - the unique class that is on the block. use this to target the specific instance via css
        $this->unique_block_class = $this->block_uid;

        $this->shortcode_atts = shortcode_atts(
			array_merge(
				td_api_multi_purpose::get_mapped_atts( __CLASS__ ),
                td_api_style::get_style_group_params( 'tds_icon' ))
			, $atts);

        $content_align_horizontal = $this->get_shortcode_att( 'content_align_horizontal' );

        $additional_classes = array();

        // content align horizontal
        if ( ! empty( $content_align_horizontal ) ) {
            $additional_classes[] = 'tdm-' . $content_align_horizontal;
        }

        $data_video_popup = '';
        $icon_video_url = $this->get_shortcode_att('icon_video_url');
	    if ( ! empty( $icon_video_url ) ) {
            $data_video_popup = ' data-mfp-src="' . $icon_video_url . '" ';
	    }

        $data_scroll_to_class = '';
	    $scroll_to_class = $this->get_shortcode_att('scroll_to_class');
	    if ( ! empty( $scroll_to_class ) ) {
		    $data_scroll_to_class = ' data-scroll-to-class="' . $scroll_to_class . '" ';
	    }

	    $data_scroll_offset = '';
	    $scroll_offset = $this->get_shortcode_att('scroll_offset');
	    if ( ! empty( $scroll_offset ) ) {
		    $data_scroll_offset = ' data-scroll-offset="' . $scroll_offset . '" ';
	    }

        $style_block_class = $this->unique_block_class;

        // Check to see if the element is being called into a tdb module template
        if( td_global::get_in_tdb_module_template() ) {
            $additional_classes[] = get_class($this) . '_' . self::$module_template_part_index;

            $style_block_class = get_class($this) . '_' . self::$module_template_part_index;
        }


        $buffy = '';

        // Icon style
        $tds_icon = $this->get_shortcode_att('tds_icon');
        if ( empty( $tds_icon ) ) {
            $tds_icon = td_util::get_option( 'tds_icon', 'tds_icon1' );
        }
        $icon_style = new $tds_icon( $this->shortcode_atts, $style_block_class );
        $icon_html = $icon_style->render();

        $buffy .= '<div class="tdm_block ' . $this->get_block_classes($additional_classes) . '" ' . $this->get_block_html_atts() . $data_video_popup . ' ' . $data_scroll_to_class . ' ' . $data_scroll_offset . '>';

        // get the block css
        $buffy .= $this->get_block_css();

	        $icon_url = $this->get_shortcode_att( 'icon_url' );
            $icon_url = td_util::get_custom_field_value_from_string( $icon_url );
            $icon_url = td_util::get_cloud_tpl_var_value_from_string( $icon_url );
            if ( empty( $icon_url ) ) {
                $buffy .= $icon_html;
            } else {

                // with link
                $target_blank = '';
	            $icon_open_in_new_window = $this->get_shortcode_att( 'icon_open_in_new_window' );
                if  ( !empty( $icon_open_in_new_window ) ) {
                    $target_blank = 'target="_blank"';
                }

                $buffy .= '<a href="' . $icon_url . '" ' . $target_blank . ' aria-label="icon">' . $icon_html . '</a>';
            }

        $buffy .= '</div>';

        return $buffy;
    }
}