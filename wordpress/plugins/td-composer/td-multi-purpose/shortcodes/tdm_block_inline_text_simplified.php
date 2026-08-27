<?php
class tdm_block_inline_text_simplified extends td_block {

	protected $additional_classes = array();

    static $style_selector = '';
	static $style_atts_prefix = '';
	static $style_atts_uid = '';
	static $module_template_part_index = '';




    /**
     * Class constructor.
     *
     * @return void
     */
    function __construct() {

        /**
         * Disable loop block features. This block does not use a loop and it dosn't need to run a query.
         */

        parent::disable_loop_block_features();


		/* --
		-- Set up the element additional classes.
		-- */
        $this->additional_classes[] = 'tdm-descr';


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


            /* -- Add an additional class to the element -- */
            if( td_global::get_in_tdb_module_template() ) {
                $this->additional_classes[] = get_class($this) . '_' . self::$module_template_part_index;
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




    /**
     * Compiles the shortcode's CSS rules.
     *
     * @return string
     */
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


        /* -- Build the CSS. -- */
        $raw_css =
            "<style>

                /* @style_general_inline_text_simplified */
                .tdm_block_inline_text_simplified {
                  margin-bottom: 0;
                  vertical-align: top;
                  transform: translateZ(0);
                }
                .tdm_block_inline_text_simplified .td-element-style{
                    z-index: -1;
                }

                /* @" . $style_atts_prefix . "display$style_atts_uid */
                .$style_selector {
                    display: @" . $style_atts_prefix . "display$style_atts_uid;
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
                .$style_selector{
                    color: @" . $style_atts_prefix . "description_color$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "links_color$style_atts_uid */
                .$style_selector a {
                    color: @" . $style_atts_prefix . "links_color$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "links_color_h$style_atts_uid */
                .$style_selector a:hover {
                    color: @" . $style_atts_prefix . "links_color_h$style_atts_uid;
                }


				/* @" . $style_atts_prefix . "f_descr$style_atts_uid */
				.$style_selector {
					@" . $style_atts_prefix . "f_descr$style_atts_uid
				}

			</style>";


        /* -- Compile the CSS and return it. -- */
        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts());


        return $td_css_res_compiler->compile_css();

    }



    
    /**
     * Sets up the various CSS rules which will later be compiled.
     *
     * @param td_res_context $res_ctx
     * @return void
     */
    static function cssMedia( $res_ctx ) {

		$style_atts_prefix = self::$style_atts_prefix;
		$style_atts_uid = self::$style_atts_uid;




		/* --
		-- GENERAL.
		-- */
        $res_ctx->load_settings_raw( 'style_general_inline_text_simplified', 1 );




		/* --
		-- LAYOUT.
		-- */
        // Display.
        $display = $res_ctx->get_shortcode_att('display_inline');
        $display = $display == 'yes' ? 'inline-block' : 'block';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'display' . $style_atts_uid, $display );

        // Horizontal align.
        $content_align = $res_ctx->get_shortcode_att('content_align_horizontal');
        $content_align = $content_align != '' ? $content_align : 'content-horiz-left';
        if ( $content_align == 'content-horiz-center' ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'align_center' . $style_atts_uid, 1 );
        } else if ( $content_align == 'content-horiz-right' ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'align_right' . $style_atts_uid, 1 );
        } else if ( $content_align == 'content-horiz-left' ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'align_left' . $style_atts_uid, 1 );
        }




		/* --
		-- COLORS.
		-- */
        $res_ctx->load_settings_raw( $style_atts_prefix . 'description_color' . $style_atts_uid, $res_ctx->get_shortcode_att( 'description_color' ) );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'links_color' . $style_atts_uid, $res_ctx->get_shortcode_att( 'links_color' ) );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'links_color_h' . $style_atts_uid, $res_ctx->get_shortcode_att( 'links_color_h' ) );




		/* --
		-- FONTS.
		-- */
        $res_ctx->load_font_settings( 'f_descr', '', $style_atts_prefix, $style_atts_uid );

    }




    /**
     * Renders the shortcode.
     *
     * @param array $atts
     * @param null $content
     * @return string
     */
    function render($atts, $content = null) {

        /* -- Call the parent render method. -- */
        parent::render($atts);



        /* --
        -- Shortcode attributes.
        -- */
        /* -- Description. -- */
        $description = $this->get_att( 'description' );

        // Check whether the description field is empty. If so:
        // * in composer: return a block error;
        // * on front-end: return an empty string.
        if ( $description == '' ) {
            if ( td_util::tdc_is_live_editor_ajax() || td_util::tdc_is_live_editor_iframe() ) {
                return $this->get_block_error('The description field is empty.');
            }
            
            return '';
        }

        
        /* -- Chheck for display restrictions. -- */
        if ( $this->is_display_restricted() ) {
            return '';
        }



        /* --
        -- Build the shortcode HTML.
        -- */
        $buffy = '';


        $buffy .= '<div class="' . $this->get_block_classes($this->additional_classes) . '" ' . $this->get_block_html_atts() . '>';

		    // Get the block CSS.
		    $buffy .= $this->get_block_css();

            // Decode the description and parse it for variables. 
            $description = rawurldecode( base64_decode( strip_tags( $description ) ) );
            $description = td_util::parse_footer_texts($description);
            $description = td_util::get_custom_field_value_from_string($description);
            $description = td_util::get_cloud_tpl_var_value_from_string($description);

            $buffy .= $description;

        $buffy .= '</div>';



        /* --
        -- Return the shortcode HTML.
        -- */
        return $buffy;

    }





    /**
     * Builds block error for composer.
     *
     * @param string $message
     * @return string
     */
    protected function get_block_error( $message ) {

        $buffy = '<div class="' . $this->get_block_classes($this->additional_classes) . '" ' . $this->get_block_html_atts() . '>';
            $buffy .= td_util::get_block_error('Inline text', $message);
        $buffy .= '</div>';

        return $buffy;

    }

}