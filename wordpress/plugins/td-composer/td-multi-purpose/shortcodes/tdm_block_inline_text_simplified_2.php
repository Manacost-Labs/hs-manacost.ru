<?php
class tdm_block_inline_text_simplified_2 extends td_block {

	protected $additional_classes = array();
    static $style_selector = '';
	static $style_atts_prefix = '';
	static $style_atts_uid = '';
	static $module_template_part_index = '';
    static $aib_component_style = null;

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
        -- Check to see if the element is being called into a tdb module template
        -- */
        if ( td_global::get_in_tdb_module_template() ) {
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

        /* --
        -- Reset the Ai Builder specific static variables.
        -- */
        self::$aib_component_style = null;
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

        // AI Builder component style CSS output.
        $aib_component_style_css_output = "";
        if ( !empty(self::$aib_component_style) ) {
            $aib_component_style_css_output =
                "/* @style_general_component_style_" . self::$aib_component_style->id . " */ 
                @style_general_component_style_" . self::$aib_component_style->id;
        }
        
        /* -- Build the CSS. -- */
        $raw_css =
            "<style>
                /* @style_general_inline_text_simplified_2 */
                .tdm_block_inline_text_simplified_2 {
                    display: inline-block;
                    margin-bottom: 0;
                    vertical-align: top;
                    font-family: var(--td_default_google_font_1, 'Open Sans', 'Open Sans Regular', sans-serif);
                    font-size: 16px;
                    line-height: 1.8;
                    text-align: left !important;
                    color: #666;
                    transform: translateZ(0);
                }
                .tdm_block_inline_text_simplified_2 .td-element-style {
                    z-index: -1;
                }
                
                $aib_component_style_css_output

                /* @" . $style_atts_prefix . "display$style_atts_uid */
                .$style_selector {
                    display: @" . $style_atts_prefix . "display$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "text_align$style_atts_uid */
                .$style_selector {
                    text-align: @" . $style_atts_prefix . "text_align$style_atts_uid !important;
                }

                /* @" . $style_atts_prefix . "color$style_atts_uid */
                .$style_selector{
                    color: @" . $style_atts_prefix . "color$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "a_color$style_atts_uid */
                .$style_selector a {
                    color: @" . $style_atts_prefix . "a_color$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "a_color_h$style_atts_uid */
                .$style_selector a:hover {
                    color: @" . $style_atts_prefix . "a_color_h$style_atts_uid;
                }

				/* @" . $style_atts_prefix . "f_txt$style_atts_uid */
				.$style_selector {
					@" . $style_atts_prefix . "f_txt$style_atts_uid
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

        /*
         * General.
         */
        $res_ctx->load_settings_raw('style_general_inline_text_simplified_2', 1);
        if ( !empty(self::$aib_component_style) ) {
            $res_ctx->load_settings_raw('style_general_component_style_' . self::$aib_component_style->id, self::$aib_component_style->css_output);
        }

		/* --
		-- LAYOUT.
		-- */
        // Display.
        $res_ctx->load_settings_raw($style_atts_prefix . 'display' . $style_atts_uid, $res_ctx->get_shortcode_att('display'));

        // Horizontal align.
        $content_align = $res_ctx->get_shortcode_att('content_align_horizontal');
        $text_align_value = '';
        switch ( $content_align ) {
            case 'content-horiz-left':
                $text_align_value = 'left';
                break;
            case 'content-horiz-center':
                $text_align_value = 'center';
                break;
            case 'content-horiz-right':
                $text_align_value = 'right';
                break;
        }

        $res_ctx->load_settings_raw($style_atts_prefix . 'text_align' . $style_atts_uid, $text_align_value);

		// Colors
        $res_ctx->load_settings_raw($style_atts_prefix . 'color' . $style_atts_uid, $res_ctx->get_shortcode_att('color'));
        $res_ctx->load_settings_raw($style_atts_prefix . 'a_color' . $style_atts_uid, $res_ctx->get_shortcode_att('a_color'));
        $res_ctx->load_settings_raw($style_atts_prefix . 'a_color_h' . $style_atts_uid, $res_ctx->get_shortcode_att('a_color_h'));

		// Fonts.
        $res_ctx->load_font_settings('f_txt', '', $style_atts_prefix, $style_atts_uid);
    }

    /**
     * Renders the shortcode.
     *
     * @param array $atts
     * @param null $content
     * @return string
     */
    function render($atts, $content = null) {
        // Call the parent render method.
        parent::render($atts);

        // In composer flag.
        $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();

        /* --
        -- Shortcode attributes.
        -- */
        /* -- Text. -- */
        $text = td_util::get_custom_field_value_from_string(rawurldecode(base64_decode(strip_tags($this->get_att('text')))));
        $text = td_util::parse_footer_texts($text);
        $text = td_util::get_cloud_tpl_var_value_from_string($text);

        // When no text is set:
        // - on front-end return an empty string;
        // - in composer add a placeholder text.
        if ( $text == '' ) {
            if ( td_util::tdc_is_live_editor_ajax() || td_util::tdc_is_live_editor_iframe() ) {
                return $this->get_block_error('The description field is empty.');
            }
            return '';
        }
        
        // Chheck for display restrictions.
        if ( $this->is_display_restricted() ) {
            return '';
        }

        // Set AI Builder settings.
        $this->set_aib_settings('text');

        /* --
        -- Build the shortcode HTML.
        -- */
        $buffy = '';
        $buffy .= '<div class="' . $this->get_block_classes($this->additional_classes) . '" ' . $this->get_block_html_atts() . '>';
		    // Get the block CSS.
		    $buffy .= $this->get_block_css();

            $buffy .= $text;
        $buffy .= '</div>';

        /* --
        -- Return the shortcode HTML.
        -- */
        return $buffy;
    }

    /**
     * Sets AI Builder settings.
     *
     * @param $component_type
     *
     */
    private function set_aib_settings( $component_type ) {
        $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();
        $td_aib_section = td_global::get_td_aib_section();

        if ( !empty($td_aib_section) ) {
            self::$aib_component_style = TagDiv\AiBuilder\Components::get_shortcode_style($this->get_att('aib_component_style'), $td_aib_section->style, $component_type);
            if ( !empty(self::$aib_component_style) ) {
                $this->additional_classes[] = 'td_aib_component_style_' . self::$aib_component_style->id;

                if ( !$in_composer ) {
                    // On front-end, load only the CSS of the inherited/specific style for this component.
                    self::$aib_component_style->css_output = self::$aib_component_style->template_css;
                } else {
                    // Otherwise, load all styles related to this component type.
                    global $td_aib_styles;
                    if ( !empty($td_aib_styles) ) {
                        $component_styles = array_filter($td_aib_styles, function ( $style ) use ( $component_type ) {
                            return $style->component == $component_type;
                        });

                        self::$aib_component_style->css_output = '';
                        foreach ( $component_styles as $component_style ) {
                            self::$aib_component_style->css_output .= $component_style->template_css;
                        }
                    }
                }
            }
        }
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