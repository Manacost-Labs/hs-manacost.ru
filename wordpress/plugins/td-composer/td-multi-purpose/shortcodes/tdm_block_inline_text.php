<?php
class tdm_block_inline_text extends td_block {

	protected $shortcode_atts = array(); //the atts used for rendering the current block

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

                /* @style_general_inline_text */
                .tdm_block.tdm_block_inline_text {
                  margin-bottom: 0;
                  vertical-align: top;
                }
                .tdm_block.tdm_block_inline_text .tdm-descr {
                  margin-bottom: 0;
                  -webkit-transform: translateZ(0);
                  transform: translateZ(0);
                }
                .tdc-row-content-vert-center .tdm-inline-text-yes {
                  vertical-align: middle;
                }
                .tdc-row-content-vert-bottom .tdm-inline-text-yes {
                  vertical-align: bottom;
                }
                
                /* @" . $style_atts_prefix . "align_left$style_atts_uid */
                .$style_selector {
                    text-align: left !important;
                }
                
                /* @" . $style_atts_prefix . "align_center$style_atts_uid */
                .$style_selector {
                    text-align: center !important;
                    margin-right: auto; 
                    margin-left: auto;
                }
                
                /* @" . $style_atts_prefix . "align_right$style_atts_uid */
                .$style_selector {
                    text-align: right !important;
                    margin-left: auto;
                }
                
                /* @" . $style_atts_prefix . "description_color$style_atts_uid */
                .$style_selector .tdm-descr {
                    color: @" . $style_atts_prefix . "description_color$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "links_color$style_atts_uid */
                .$style_selector .tdm-descr a {
                    color: @" . $style_atts_prefix . "links_color$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "links_color_h$style_atts_uid */
                .$style_selector .tdm-descr a:hover {
                    color: @" . $style_atts_prefix . "links_color_h$style_atts_uid;
                }



				/* @" . $style_atts_prefix . "f_descr$style_atts_uid */
				.$style_selector .tdm-descr {
					@" . $style_atts_prefix . "f_descr$style_atts_uid
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
     * @param $responsive_context td_res_context
     * @param $atts
     */
    static function cssMedia( $res_ctx ) {

		$style_atts_prefix = self::$style_atts_prefix;
		$style_atts_uid = self::$style_atts_uid;

        $res_ctx->load_settings_raw( 'style_general_inline_text', 1 );

        $content_align = $res_ctx->get_shortcode_att('content_align_horizontal');
        if ( $content_align == 'content-horiz-center' ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'align_center' . $style_atts_uid, 1 );
        } else if ( $content_align == 'content-horiz-right' ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'align_right' . $style_atts_uid, 1 );
        } else if ( $content_align == 'content-horiz-left' ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'align_left' . $style_atts_uid, 1 );
        }

        /*-- DESCRIPTION -- */
        $res_ctx->load_settings_raw( $style_atts_prefix . 'description_color' . $style_atts_uid, $res_ctx->get_shortcode_att( 'description_color' ) );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'links_color' . $style_atts_uid, $res_ctx->get_shortcode_att( 'links_color' ) );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'links_color_h' . $style_atts_uid, $res_ctx->get_shortcode_att( 'links_color_h' ) );


        /*-- FONTS -- */
        $res_ctx->load_font_settings( 'f_descr', '', $style_atts_prefix, $style_atts_uid );

    }

    function render($atts, $content = null) {
        parent::render($atts);

        $this->shortcode_atts = shortcode_atts(
			array_merge(
				td_api_multi_purpose::get_mapped_atts( __CLASS__ ))
			, $atts);

        $description = rawurldecode( base64_decode( strip_tags( $this->get_shortcode_att( 'description' ) ) ) );
        $description = td_util::parse_footer_texts($description);
        $description = td_util::get_custom_field_value_from_string($description);
        $description = td_util::get_cloud_tpl_var_value_from_string($description);
        $display_inline = $this->get_shortcode_att( 'display_inline' );

        $additional_classes = array();

        // display inline
        if( !empty ( $display_inline ) ) {
            $additional_classes[] = 'tdm-inline-block';
        }

        // Check to see if the element is being called into a tdb module template
        if( td_global::get_in_tdb_module_template() ) {
            $additional_classes[] = get_class($this) . '_' . self::$module_template_part_index;
        }

        $buffy = '';
        // display restrictions
        $hide_for_user_type = $this->get_shortcode_att( 'hide_for_user_type' );

        if( $hide_for_user_type != '' ) {
            if( !( td_util::tdc_is_live_editor_ajax() || td_util::tdc_is_live_editor_iframe() ) &&
                (
                    ( $hide_for_user_type == 'logged-in' && is_user_logged_in() ) ||
                    ( $hide_for_user_type == 'guests' && !is_user_logged_in() )
                )
            ) {
                return $buffy;
            }
        } else {
            $author_plan_ids = $this->get_att('author_plan_id');
            $all_users_plan_ids = $this->get_att('logged_plan_id');

            if( !td_util::plan_limit($author_plan_ids, $all_users_plan_ids) ) {
                return $buffy;
            }
        }

        $buffy .= '<div class="tdm_block ' . $this->get_block_classes($additional_classes) . '" ' . $this->get_block_html_atts() . '>';

		    //get the block css
		    $buffy .= $this->get_block_css();

            $buffy .= '<p class="tdm-descr">' . $description . '</p>';

        $buffy .= '</div>';


        return $buffy;
    }
}