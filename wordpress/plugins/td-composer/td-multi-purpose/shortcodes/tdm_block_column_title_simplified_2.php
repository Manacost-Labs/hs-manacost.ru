<?php
class tdm_block_column_title_simplified_2 extends td_block {

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

        // Set the style selector
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

        // Build the CSS.
        $raw_css =
            "<style>
                /* @style_general_tdm_block_column_title_simplified_2 */
                .tdm_block_column_title_simplified_2 {
                    display: flex;
					flex-direction: column;
					margin: 0;
					transform: translateZ(0);
					font-size: 28px;
                    line-height: 1.3;
                    font-weight: 400;
                    color: #111;
				}
				.tdm_block_column_title_simplified_2 > .td-element-style {
					z-index: -1;
				}
				.tdm_block_column_title_simplified_2 .tdm-title-s-text {
                    text-align: left;
				}
				/* @style_general_tdm_block_column_title_simplified_2_subtitle */
				.tdm_block_column_title_simplified_2 .tdm-title-s-subtitle {
				    order: 3;
				    margin: 12px 0 0;
					font-family: var(--td_default_google_font_2, 'Roboto', sans-serif);
					text-transform: uppercase;
					font-size: 15px;
					font-weight: 500;
					color: #666;
				}
                
                $aib_component_style_css_output
				
				/* @" . $style_atts_prefix . "horiz_align$style_atts_uid */
				.$style_selector .tdm-title-s-text,
				.$style_selector .tdm-title-s-subtitle {
					text-align: @" . $style_atts_prefix . "horiz_align$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "title_color_solid$style_atts_uid */
				.$style_selector {
					color: @" . $style_atts_prefix . "title_color_solid$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "title_color_gradient$style_atts_uid */
				.$style_selector {
					@" . $style_atts_prefix . "title_color_gradient$style_atts_uid
					-webkit-background-clip: text;
					-webkit-text-fill-color: transparent;
				}
				html[class*='ie'] .$style_selector {
				    background: none;
					color: @" . $style_atts_prefix . "title_color_gradient_1$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "hover_title_color$style_atts_uid */
				.$style_selector:hover {
					color: @" . $style_atts_prefix . "hover_title_color$style_atts_uid;
				}
				.$style_selector:hover {
					cursor: auto;
				}
				/* @" . $style_atts_prefix . "hover_gradient$style_atts_uid */
				.$style_selector:hover {
					-webkit-text-fill-color: unset;
					background: transparent;
					transition: none;
				}
				/* @" . $style_atts_prefix . "f_title$style_atts_uid */
				.$style_selector {
					@" . $style_atts_prefix . "f_title$style_atts_uid
				}
				
				/* @" . $style_atts_prefix . "line_width$style_atts_uid */
				.$style_selector:after {
				    content: '';
					order: 2;
					position: relative;
					display: block;
					margin: 14px auto 0 0;
					width: @" . $style_atts_prefix . "line_width$style_atts_uid;
					height: 1px;
					background-color: var(--td_theme_color, #4db2ec);
					transition: all 0.2s ease;
				}
				/* @" . $style_atts_prefix . "line_width_h$style_atts_uid */
				.$style_selector:hover:after {
					width: @" . $style_atts_prefix . "line_width_h$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "line_height$style_atts_uid */
				.$style_selector:after {
					height: @" . $style_atts_prefix . "line_height$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "line_position$style_atts_uid */
				.$style_selector:after {
					order: @" . $style_atts_prefix . "line_position$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "line_space$style_atts_uid */
				.$style_selector:after {
					@" . $style_atts_prefix . "line_space$style_atts_uid
				}
				/* @" . $style_atts_prefix . "horiz_align_line$style_atts_uid */
				.$style_selector:after {
					@" . $style_atts_prefix . "horiz_align_line$style_atts_uid
				}
				/* @" . $style_atts_prefix . "line_color$style_atts_uid */
				.$style_selector:after {
					background: @" . $style_atts_prefix . "line_color$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "line_color_gradient$style_atts_uid */
				.$style_selector:after {
					@" . $style_atts_prefix . "line_color_gradient$style_atts_uid
				}
				/* @" . $style_atts_prefix . "hover_line_color$style_atts_uid */
				.$style_selector:hover:after {
					background: @" . $style_atts_prefix . "hover_line_color$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "hover_line_color_gradient$style_atts_uid */
				.$style_selector:hover:after {
					@" . $style_atts_prefix . "hover_line_color_gradient$style_atts_uid
				}
				
				/* @" . $style_atts_prefix . "subtitle_pos$style_atts_uid */
				.$style_selector .tdm-title-s-subtitle {
					order: @" . $style_atts_prefix . "subtitle_pos$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "subtitle_space$style_atts_uid */
				.$style_selector .tdm-title-s-subtitle {
					@" . $style_atts_prefix . "subtitle_space$style_atts_uid
				}
				/* @" . $style_atts_prefix . "subtitle_color$style_atts_uid */
				.$style_selector .tdm-title-s-subtitle {
					color: @" . $style_atts_prefix . "subtitle_color$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "subtitle_color_h$style_atts_uid */
				.$style_selector:hover .tdm-title-s-subtitle {
					color: @" . $style_atts_prefix . "subtitle_color_h$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "f_subtitle$style_atts_uid */
				.$style_selector .tdm-title-s-subtitle {
					@" . $style_atts_prefix . "f_subtitle$style_atts_uid
				}
			</style>";

        // Compile the CSS and return it.
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
        $res_ctx->load_settings_raw('style_general_tdm_block_column_title_simplified_2', 1);
        if ( !empty(self::$aib_component_style) ) {
            $res_ctx->load_settings_raw('style_general_component_style_' . self::$aib_component_style->id, self::$aib_component_style->css_output);
        }

        $subtitle_text = $res_ctx->get_shortcode_att('subtitle_text');
        if ( !empty($subtitle_text) ) {
            $res_ctx->load_settings_raw('style_general_tdm_block_column_title_simplified_2_subtitle', 1);
        }

        // Horizontal align.
        $content_align = $res_ctx->get_shortcode_att('content_align_horizontal');
        switch ( $content_align ) {
            case 'content-horiz-left':
                $res_ctx->load_settings_raw( $style_atts_prefix . 'horiz_align' . $style_atts_uid, 'left' );
                $res_ctx->load_settings_raw( $style_atts_prefix . 'horiz_align_line' . $style_atts_uid, 'margin-left: 0; margin-right: auto;' );
                break;
            case 'content-horiz-center':
                $res_ctx->load_settings_raw( $style_atts_prefix . 'horiz_align' . $style_atts_uid, 'center' );
                $res_ctx->load_settings_raw( $style_atts_prefix . 'horiz_align_line' . $style_atts_uid, 'margin-left: auto; margin-right: auto;' );
                break;
            case 'content-horiz-right':
                $res_ctx->load_settings_raw( $style_atts_prefix . 'horiz_align' . $style_atts_uid, 'right' );
                $res_ctx->load_settings_raw( $style_atts_prefix . 'horiz_align_line' . $style_atts_uid, 'margin-left: auto; margin-right: 0;' );
                break;
        }

        /*
         * Text.
         */
        // Color.
        $res_ctx->load_color_settings('title_color', $style_atts_prefix . 'title_color_solid' . $style_atts_uid, $style_atts_prefix . 'title_color_gradient' . $style_atts_uid, $style_atts_prefix . 'title_color_gradient_1' . $style_atts_uid);

        $hover_title_color = $res_ctx->get_shortcode_att('title_color_h');
        $res_ctx->load_settings_raw($style_atts_prefix . 'hover_title_color' . $style_atts_uid, $hover_title_color);
        if ( !empty ($hover_title_color ) ) {
            $res_ctx->load_settings_raw($style_atts_prefix . 'hover_gradient' . $style_atts_uid, 1);
        }

        // Font.
        $res_ctx->load_font_settings('f_title', '', $style_atts_prefix, $style_atts_uid);

        /*
         * Line.
         */
        $line_width = $res_ctx->get_shortcode_att('line_width');
        if ( $line_width != '' ) {
            // Line width.
            $line_width .= is_numeric($line_width) ? 'px' : '';
            $res_ctx->load_settings_raw($style_atts_prefix . 'line_width' . $style_atts_uid, $line_width);

            // Hover line width.
            $line_width_h = $res_ctx->get_shortcode_att('line_width_h');
            $line_width_h .= is_numeric($line_width_h) ? 'px' : '';
            $res_ctx->load_settings_raw($style_atts_prefix . 'line_width_h' . $style_atts_uid, $line_width_h);

            // Line Height.
            $line_height = $res_ctx->get_shortcode_att('line_height');
            $line_height .= is_numeric($line_height) ? 'px' : '';
            $res_ctx->load_settings_raw($style_atts_prefix . 'line_height' . $style_atts_uid, $line_height);

            // Line position.
            $line_position = $res_ctx->get_shortcode_att('line_pos');
            $line_position_value = $line_position == 'above' ? '-1' : '';
            $res_ctx->load_settings_raw($style_atts_prefix . 'line_position' . $style_atts_uid, $line_position_value);

            // Line space.
            $line_space = $res_ctx->get_shortcode_att('line_space');
            if ( !empty($line_space) ) {
                $line_space .= is_numeric($line_space) ? 'px' : '';
                $line_space_value = $line_position == 'above' ? "margin-top: 0; margin-bottom: {$line_space};" : "margin-top: {$line_space}; margin-bottom: 0;";
                $res_ctx->load_settings_raw($style_atts_prefix . 'line_space' . $style_atts_uid, $line_space_value);
            }

            // Line color.
            $res_ctx->load_color_settings('line_color', $style_atts_prefix . 'line_color' . $style_atts_uid, $style_atts_prefix . 'line_color_gradient' . $style_atts_uid);
            $res_ctx->load_color_settings( 'line_color_h', $style_atts_prefix . 'hover_line_color' . $style_atts_uid, $style_atts_prefix . 'hover_line_color_gradient' . $style_atts_uid);
        }

        /*
         * Subtitle.
         */
        if ( !empty($subtitle_text) ) {
            // Position.
            $subtitle_position = $res_ctx->get_shortcode_att('subtitle_pos');
            $subtitle_position_value = $subtitle_position == 'above' ? '-2' : '';
            $res_ctx->load_settings_raw($style_atts_prefix . 'subtitle_pos' . $style_atts_uid, $subtitle_position_value);

            // Space.
            $subtitle_space = $res_ctx->get_shortcode_att('subtitle_space');
            if ( !empty($subtitle_space) ) {
                $subtitle_space .= is_numeric($subtitle_space) ? 'px' : '';
                $subtitle_space_value = $subtitle_position == 'above' ? "margin: 0 0 {$subtitle_space};" : "margin: {$subtitle_space} 0 0;";
                $res_ctx->load_settings_raw($style_atts_prefix . 'subtitle_space' . $style_atts_uid, $subtitle_space_value);
            }

            // Color.
            $res_ctx->load_settings_raw($style_atts_prefix . 'subtitle_color' . $style_atts_uid, $res_ctx->get_shortcode_att('subtitle_color'));
            $res_ctx->load_settings_raw($style_atts_prefix . 'subtitle_color_h' . $style_atts_uid, $res_ctx->get_shortcode_att('subtitle_color_h'));

            // Font.
            $res_ctx->load_font_settings('f_subtitle', '', $style_atts_prefix, $style_atts_uid);
        }
    }

    /**
     * Renders the shortcode.
     *
     * @param array $atts
     * @param null  $content
     * @return string
     */
    function render( $atts, $content = null ) {
        // Call the parent render method.
        parent::render($atts);

        // In composer flag.
        $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();

        /* --
        -- Shortcode attributes.
        -- */
        // Text.
        $text = td_util::get_custom_field_value_from_string(rawurldecode(base64_decode(strip_tags($this->get_att('text')))));
        $text = td_util::get_cloud_tpl_var_value_from_string($text);

        // When no text is set:
        // - on front-end return an empty string;
        // - in composer add a placeholder text.
        if ( empty($text) ) {
            if ( !$in_composer ) {
                return '';
            }
            $text = 'Custom title';
        }

        // HTML tag.
        $html_tag = $this->get_att('tag');
        $html_tag = !empty($html_tag) ? $html_tag : 'h3';

        // Subtitle.
        $subtitle_text = td_util::get_custom_field_value_from_string($this->get_att('subtitle_text'));
        $subtitle_text = td_util::get_cloud_tpl_var_value_from_string($subtitle_text);

        // Set AI Builder settings.
        $this->set_aib_settings('title');

        /* --
        -- Build the shortcode HTML.
        -- */
        $buffy = '';
        $buffy .= '<' . $html_tag . ' class="' . $this->get_block_classes($this->additional_classes) . '" ' . $this->get_block_html_atts() . '>';
            // Get the block CSS.
            $buffy .= $this->get_block_css();

            $buffy .= '<span class="tdm-title-s-text">' . $text . '</span>';

            if ( !empty($subtitle_text) ) {
                $buffy .= '<span class="tdm-title-s-subtitle">' . $subtitle_text . '</span>';
            }
        $buffy .= '</' . $html_tag . '>';

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
            $buffy .= td_util::get_block_error('Column title', $message);
        $buffy .= '</div>';

        return $buffy;
    }

}