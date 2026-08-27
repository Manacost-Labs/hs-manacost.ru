<?php

class tdm_block_button_simplified extends td_block {

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
                /* @style_general_tdm_block_button_simplified */
                .tdm_block_button_simplified {
                    display: inline-block;
                    margin-bottom: 0;
                    transform: translateZ(0);
                    vertical-align: middle;
                    text-align: center !important;
                    line-height: 0;
                }
                .tdm_block_button_simplified > .td-element-style {
                    z-index: -1;
                }
                .tdm_block_button_simplified a {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    column-gap: 14px;
                    position: relative;
                    padding: 17px 36px 19px 36px;
                    background-color: var(--td_theme_color, #4db2ec);
                    font-size: 15px;
                    line-height: 1.3;
                    font-weight: 500;
                    transition: box-shadow .2s ease-in-out;
                    transform: translateZ(0);
                }
                .tdm_block_button_simplified a:before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: #222;
                    z-index: -1;
                    opacity: 0;
                    transition: opacity .2s ease-in-out;
                }
                .tdm_block_button_simplified a:hover:before {
                    opacity: 1;
                }
                .tdm_block_button_simplified a:after {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 0;
                    border: 0 solid #000;
                    transition: border-color .2s ease-in-out;
                    pointer-events: none;
                }
                .tdm_block_button_simplified .tdm-btn-s-txt {
                    color: #fff;
                    transition: color .2s ease-in-out;
                    z-index: 1;
                }
                .tdm_block_button_simplified .tdm-btn-s-icon {
                    position: relative;
                    font-size: 1em;
                    color: #fff;
                    transition: color .2s ease-in-out;
                    z-index: 1;
                }
                .tdm_block_button_simplified .tdm-btn-s-icon svg {
                    display: block;
                    width: 1em;
                    height: auto;
                    line-height: 1;
                    fill: currentColor;
                    transition: fill .2s ease-in-out;
                }
                
                $aib_component_style_css_output
        
                /* @" . $style_atts_prefix . "display$style_atts_uid */
                .$style_selector {
                    display: @" . $style_atts_prefix . "display$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "gap$style_atts_uid */
                .$style_selector a {
                    column-gap: @" . $style_atts_prefix . "gap$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "width$style_atts_uid */
                .$style_selector a {
                    min-width: @" . $style_atts_prefix . "width$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "minimum_width$style_atts_uid */
                .$style_selector a {
                    min-width: @" . $style_atts_prefix . "minimum_width$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "padding$style_atts_uid */
                .$style_selector a {
                    padding: @" . $style_atts_prefix . "padding$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "wrapper_horizontal_align$style_atts_uid */
                .$style_selector {
                    text-align: @" . $style_atts_prefix . "wrapper_horizontal_align$style_atts_uid !important;
                }
                /* @" . $style_atts_prefix . "text_horizontal_align$style_atts_uid */
                .$style_selector a {
                    justify-content: @" . $style_atts_prefix . "text_horizontal_align$style_atts_uid;
                }
                
                /* @" . $style_atts_prefix . "icon_pos$style_atts_uid */
                .$style_selector a {
                    flex-direction: @" . $style_atts_prefix . "icon_pos$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "icon_size$style_atts_uid */
                .$style_selector .tdm-btn-s-icon {
                    font-size: @" . $style_atts_prefix . "icon_size$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "icon_align$style_atts_uid */
                .$style_selector .tdm-btn-s-icon {
                    top: @" . $style_atts_prefix . "icon_align$style_atts_uid;
                }
                
                /* @" . $style_atts_prefix . "bg_color_solid$style_atts_uid */
				.$style_selector a {
					background: @" . $style_atts_prefix . "bg_color_solid$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "bg_color_gradient$style_atts_uid */
				.$style_selector a {
					@" . $style_atts_prefix . "bg_color_gradient$style_atts_uid
				}
                /* @" . $style_atts_prefix . "bg_color_h_solid$style_atts_uid */
				.$style_selector a:before {
					background: @" . $style_atts_prefix . "bg_color_h_solid$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "bg_color_h_gradient$style_atts_uid */
				.$style_selector a:before {
					@" . $style_atts_prefix . "bg_color_h_gradient$style_atts_uid
				}
				/* @" . $style_atts_prefix . "bg_active_solid$style_atts_uid */
				.$style_selector.td-scroll-in-view a:before {
					background-color: @" . $style_atts_prefix . "bg_active_solid$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "bg_active_gradient$style_atts_uid */
				.$style_selector.td-scroll-in-view a:before {
					@" . $style_atts_prefix . "bg_active_gradient$style_atts_uid
				}
				.$style_selector.td-scroll-in-view a:before {
					opacity: 1;
				}
				
				/* @" . $style_atts_prefix . "text_color_solid$style_atts_uid */
				.$style_selector .tdm-btn-s-txt,
				.$style_selector .tdm-btn-s-icon {
					color: @" . $style_atts_prefix . "text_color_solid$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "text_color_gradient$style_atts_uid */
				.$style_selector .tdm-btn-s-txt,
				.$style_selector .tdm-btn-s-icon {
					@" . $style_atts_prefix . "text_color_gradient$style_atts_uid
					-webkit-background-clip: text;
					-webkit-text-fill-color: transparent;
				}
				html[class*='ie'] .$style_selector .tdm-btn-s-txt,
				html[class*='ie'] .$style_selector .tdm-btn-s-icon {
				    background: none;
					color: @" . $style_atts_prefix . "text_color_gradient_1$style_atts_uid;
				}
				.$style_selector .tdm-btn-s-icon svg {
				    fill: @" . $style_atts_prefix . "text_color_gradient_1$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "text_hover_color$style_atts_uid */
				.$style_selector a:hover .tdm-btn-s-txt,
				.$style_selector a:hover .tdm-btn-s-icon {
					color: @" . $style_atts_prefix . "text_hover_color$style_atts_uid;
				}
				.$style_selector a:hover .tdm-btn-s-icon svg {
				    fill: currentColor;
				}
				/* @" . $style_atts_prefix . "text_hover_gradient$style_atts_uid */
				.$style_selector a:hover .tdm-btn-s-txt,
				.$style_selector a:hover .tdm-btn-s-icon {
					-webkit-text-fill-color: unset;
					background: transparent;
					transition: none;
				}
				/* @" . $style_atts_prefix . "text_active_color$style_atts_uid */
				.$style_selector.td-scroll-in-view .tdm-btn-s-txt,
				.$style_selector.td-scroll-in-view .tdm-btn-s-icon {
					color: @" . $style_atts_prefix . "text_active_color$style_atts_uid;
				}
				.$style_selector.td-scroll-in-view .tdm-btn-s-icon svg {
				    fill: currentColor;
				}
				
				/* @" . $style_atts_prefix . "icon_color_solid$style_atts_uid */
				.$style_selector a .tdm-btn-s-icon {
					color: @" . $style_atts_prefix . "icon_color_solid$style_atts_uid;
				    -webkit-text-fill-color: unset;
    				background: transparent;
				}
				/* @" . $style_atts_prefix . "icon_color_gradient$style_atts_uid */
				.$style_selector a .tdm-btn-s-icon {
					@" . $style_atts_prefix . "icon_color_gradient$style_atts_uid
					-webkit-background-clip: text;
					-webkit-text-fill-color: transparent;
				}
				html[class*='ie'] .$style_selector a .tdm-btn-s-icon {
				    background: none;
					color: @" . $style_atts_prefix . "icon_color_gradient_1$style_atts_uid;
				}
				.$style_selector a .tdm-btn-s-icon svg {
				    fill: @" . $style_atts_prefix . "icon_color_gradient_1$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "icon_hover_color$style_atts_uid */
				.$style_selector:hover a .tdm-btn-s-icon {
					color: @" . $style_atts_prefix . "icon_hover_color$style_atts_uid;
				}
				.$style_selector a:hover a .tdm-btn-s-icon svg {
				    fill: currentColor;
				}
				/* @" . $style_atts_prefix . "icon_hover_gradient$style_atts_uid */
				.$style_selector:hover a .tdm-btn-s-icon {
					-webkit-text-fill-color: unset;
					background: transparent;
					transition: none;
				}
				/* @" . $style_atts_prefix . "icon_active_color$style_atts_uid */
				.$style_selector.td-scroll-in-view a .tdm-btn-s-icon {
					color: @" . $style_atts_prefix . "icon_active_color$style_atts_uid;
				}
				.$style_selector.td-scroll-in-view a .tdm-btn-s-icon svg {
				    fill: currentColor;
				}
				/* @" . $style_atts_prefix . "icon_active_gradient$style_atts_uid */
				.$style_selector.td-scroll-in-view a .tdm-btn-s-icon {
					-webkit-text-fill-color: unset;
					background: transparent;
					transition: none;
				}
				
				/* @" . $style_atts_prefix . "border_size$style_atts_uid */
				.$style_selector a:after {
					border-width: @" . $style_atts_prefix . "border_size$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "border_style$style_atts_uid */
				.$style_selector a:after {
					border-style: @" . $style_atts_prefix . "border_style$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "border_color_solid$style_atts_uid */
				.$style_selector a:after {
					border-color: @" . $style_atts_prefix . "border_color_solid$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "border_color_params$style_atts_uid */
				.$style_selector a:after {
				    border-image: linear-gradient(@" . $style_atts_prefix . "border_color_params$style_atts_uid);
				    border-image: -webkit-linear-gradient(@" . $style_atts_prefix . "border_color_params$style_atts_uid);
				    border-image-slice: 1;
				    transition: none;
				}
				.$style_selector a:hover:after {
				    border-image: linear-gradient(@" . $style_atts_prefix . "border_hover_color$style_atts_uid, @" . $style_atts_prefix . "border_hover_color$style_atts_uid);
				    border-image: -webkit-linear-gradient(@" . $style_atts_prefix . "border_hover_color$style_atts_uid, @" . $style_atts_prefix . "border_hover_color$style_atts_uid);
				    border-image-slice: 1;
				    transition: none;
				}
				/* @" . $style_atts_prefix . "border_hover_color$style_atts_uid */
				.$style_selector a:hover:after {
					border-color: @" . $style_atts_prefix . "border_hover_color$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "border_radius$style_atts_uid */
				.$style_selector a,
				.$style_selector a:before,
				.$style_selector a:after {
					border-radius: @" . $style_atts_prefix . "border_radius$style_atts_uid;
				}
				
				/* @" . $style_atts_prefix . "shadow$style_atts_uid */
				.$style_selector a {
					box-shadow: @" . $style_atts_prefix . "shadow$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "shadow_h$style_atts_uid */
				.$style_selector a:hover {
					box-shadow: @" . $style_atts_prefix . "shadow_h$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "shadow_active$style_atts_uid */
				.$style_selector a {
					box-shadow: @" . $style_atts_prefix . "shadow_active$style_atts_uid;
				}
				
				/* @" . $style_atts_prefix . "f_txt$style_atts_uid */
				.$style_selector a {
					@" . $style_atts_prefix . "f_txt$style_atts_uid
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
        $scroll_to_class_active = !empty($res_ctx->get_shortcode_att('scroll_to_class')) && !td_global::get_in_tdb_module_template();

		/*
         * General.
         */
        $res_ctx->load_settings_raw('style_general_tdm_block_button_simplified', 1);
        if ( !empty(self::$aib_component_style) ) {
            $res_ctx->load_settings_raw('style_general_component_style_' . self::$aib_component_style->id, self::$aib_component_style->css_output);
        }

        // Display.
        $res_ctx->load_settings_raw($style_atts_prefix . 'display' . $style_atts_uid, $res_ctx->get_shortcode_att('display'));

        // Minimum width.
        $minimum_width = $res_ctx->get_shortcode_att('min_width');
        $minimum_width .= is_numeric($minimum_width) ? 'px' : '';
        $res_ctx->load_settings_raw($style_atts_prefix . 'minimum_width' . $style_atts_uid, $minimum_width);

        // Padding.
        $padding = $res_ctx->get_shortcode_att('padding');
        $padding .= is_numeric($padding) ? 'px' : '';
        $res_ctx->load_settings_raw($style_atts_prefix . 'padding' . $style_atts_uid, $padding);

        // Text horizontal align.
        $text_horizontal_align = $res_ctx->get_shortcode_att('text_align_horizontal');
        $text_horizontal_align_value = '';
        switch ( $text_horizontal_align ) {
            case 'content-horiz-left':
                $text_horizontal_align_value = 'flex-start';
                break;
            case 'content-horiz-center':
                $text_horizontal_align_value = 'center';
                break;
            case 'content-horiz-right':
                $text_horizontal_align_value = 'flex-end';
                break;
        }
        $res_ctx->load_settings_raw($style_atts_prefix . 'text_horizontal_align' . $style_atts_uid, $text_horizontal_align_value);

        // Button horizontal align.
        $horizontal_align = $res_ctx->get_shortcode_att('content_align_horizontal');
        $wrapper_horizontal_align_value = '';
        switch ( $horizontal_align ) {
            case 'content-horiz-left':
                $wrapper_horizontal_align_value = 'left';
                break;
            case 'content-horiz-center':
                $wrapper_horizontal_align_value = 'center';
                break;
            case 'content-horiz-right':
                $wrapper_horizontal_align_value = 'right';
                break;
        }
        $res_ctx->load_settings_raw($style_atts_prefix . 'wrapper_horizontal_align' . $style_atts_uid, $wrapper_horizontal_align_value);

        // Icon size.
        $icon_size = $res_ctx->get_shortcode_att('icon_size');
        $icon_size .= is_numeric($icon_size) ? 'px' : '';
        $res_ctx->load_settings_raw($style_atts_prefix . 'icon_size' . $style_atts_uid, $icon_size);

        // Icon space.
        $icon_space = $res_ctx->get_shortcode_att('icon_space');
        $icon_space .= is_numeric($icon_space) ? 'px' : '';
        $res_ctx->load_settings_raw($style_atts_prefix . 'gap' . $style_atts_uid, $icon_space);

        // Icon vertical align.
        $icon_align = $res_ctx->get_shortcode_att('icon_align');
        if ( !$res_ctx->is('all') || $icon_align != '0' ) {
            $res_ctx->load_settings_raw($style_atts_prefix . 'icon_align' . $style_atts_uid, $icon_align . 'px');
        }

        /*
         * Style.
         */
        // Background color.
        $res_ctx->load_color_settings('bg_color', $style_atts_prefix . 'bg_color_solid' . $style_atts_uid, $style_atts_prefix . 'bg_color_gradient' . $style_atts_uid);
        $res_ctx->load_color_settings('bg_color_h', $style_atts_prefix . 'bg_color_h_solid' . $style_atts_uid, $style_atts_prefix . 'bg_color_h_gradient' . $style_atts_uid);
        if ( $scroll_to_class_active ) {
            $res_ctx->load_color_settings('bg_color_h', $style_atts_prefix . 'bg_active_solid' . $style_atts_uid, $style_atts_prefix . 'bg_active_gradient' . $style_atts_uid,);
        }

        // Text color.
        $res_ctx->load_color_settings('text_color', $style_atts_prefix . 'text_color_solid' . $style_atts_uid, $style_atts_prefix . 'text_color_gradient' . $style_atts_uid, $style_atts_prefix . 'text_color_gradient_1' . $style_atts_uid);
        $text_hover_color = $res_ctx->get_shortcode_att('text_color_h');
        $res_ctx->load_settings_raw($style_atts_prefix . 'text_hover_color' . $style_atts_uid, $text_hover_color);
        if ( !empty ( $text_hover_color ) ) {
            $res_ctx->load_settings_raw($style_atts_prefix . 'text_hover_gradient' . $style_atts_uid, 1);
        }
        if ( $scroll_to_class_active ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'text_active_color' . $style_atts_uid, $text_hover_color);
            if ( !empty($text_hover_color) ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'text_active_gradient' . $style_atts_uid, 1 );
            }
        }

        // Icon color.
        $res_ctx->load_color_settings('icon_color', $style_atts_prefix . 'icon_color_solid' . $style_atts_uid, $style_atts_prefix . 'icon_color_gradient' . $style_atts_uid, $style_atts_prefix . 'icon_color_gradient_1' . $style_atts_uid);
        $icon_hover_color = $res_ctx->get_shortcode_att('icon_color_h');
        $res_ctx->load_settings_raw($style_atts_prefix . 'icon_hover_color' . $style_atts_uid, $icon_hover_color);
        if ( !empty($icon_hover_color) ) {
            $res_ctx->load_settings_raw($style_atts_prefix . 'icon_hover_gradient' . $style_atts_uid, 1);
        }
        if ( $scroll_to_class_active ) {
            $res_ctx->load_settings_raw($style_atts_prefix . 'icon_active_color' . $style_atts_uid, $icon_hover_color);
            if ( !empty($icon_hover_color ) ) {
                $res_ctx->load_settings_raw($style_atts_prefix . 'icon_active_gradient' . $style_atts_uid, 1 );
            }
        }

        // Border.
        $border_size = $res_ctx->get_shortcode_att('border_size');
        $border_size .= is_numeric($border_size) ? 'px' : '';
        $res_ctx->load_settings_raw($style_atts_prefix . 'border_size' . $style_atts_uid, $border_size);

        $border_style = $res_ctx->get_shortcode_att('border_style');
        $res_ctx->load_settings_raw($style_atts_prefix . 'border_style' . $style_atts_uid, $border_style);

        $res_ctx->load_color_settings('border_color', $style_atts_prefix . 'border_color_solid' . $style_atts_uid, $style_atts_prefix . 'border_color_gradient' . $style_atts_uid, $style_atts_prefix . 'border_color_gradient_1' . $style_atts_uid, $style_atts_prefix . 'border_color_params' . $style_atts_uid);
        $border_hover_color = $res_ctx->get_shortcode_att('border_color_h');
        if ( !empty($border_hover_color) ) {
            $res_ctx->load_settings_raw($style_atts_prefix . 'border_hover_color' . $style_atts_uid, $border_hover_color);
        }

        $border_radius = $res_ctx->get_shortcode_att('border_radius');
        $border_radius .= is_numeric($border_radius) ? 'px' : '';
        $res_ctx->load_settings_raw($style_atts_prefix . 'border_radius' . $style_atts_uid, $border_radius);

        // Shadow.
        $res_ctx->load_shadow_settings( 0, 0, 2, 0, 'rgba(0, 0, 0, 0.1)', 'shadow', '', false, $style_atts_prefix, $style_atts_uid );
        $res_ctx->load_shadow_settings( 0, 0, 2, 0, 'rgba(0, 0, 0, 0.1)', 'shadow_h', '', false, $style_atts_prefix, $style_atts_uid );
        if ( $scroll_to_class_active ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'shadow_active' . $style_atts_uid, 1 );
        }

        /*
         * Fonts.
         */
        $res_ctx->load_font_settings( 'f_txt', '', $style_atts_prefix, $style_atts_uid );
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

        // Check for display restrictions.
        if ( $this->is_display_restricted() ) {
            return '';
        }

        // In composer flag.
        $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();

        /*
         * Shortcode attributes.
         */
        // Text.
        $text = td_util::get_custom_field_value_from_string($this->get_att('text'));

        // Icon
        $icon = $this->get_icon_att('tdicon');
        $buffy_icon = '';
        if ( !empty( $icon ) ) {
            $tdicon_data = $in_composer ? ' data-td-svg-icon="' . $this->get_att('tdicon') . '"' : '';

            if ( base64_encode(base64_decode($icon)) == $icon ) {
                $buffy_icon .= '<span class="tdm-btn-s-icon tdm-btn-s-icon-svg"' . $tdicon_data . '>' . base64_decode($icon) . '</span>';
            } else {
                $buffy_icon .= '<i class="tdm-btn-s-icon ' . $icon . '"></i>';
            }
        }

        $icon_pos = $this->get_att('icon_pos');
        $icon_pos = !empty($icon_pos) ? $icon_pos : 'after';

        // When both the text and icon are not set:
        // - on front-end return an empty string;
        // - in composer add a placeholder text.
        if ( empty($text) && empty($icon) ) {
            if ( !$in_composer ) {
                return '';
            }
            $text = 'Button text';
        }

        // Icon aria label.
        $icon_aria_label = $this->get_att('icon_aria');
        $icon_aria_label = ( !empty($icon) && !empty($icon_aria_label) ) ? ' aria-label="' . $icon_aria_label . '"' : '';
        $title_attr = $icon_aria_label == '' ? ( !empty($text) ? ' title="' . $text . '"' : '' ) : $icon_aria_label;

        // Url.
        $url = td_util::get_cloud_tpl_var_value_from_string(td_util::get_custom_field_value_from_string($this->get_att('url')));
        $url = !empty($url) ? $url : '#';

        $url_rel = $this->get_att('url_rel');
        $url_rel_attr = !empty($url_rel) ? ' rel="' . $url_rel . '"' : '';

        $url_new_window = !empty($this->get_att('url_new_window'));
        $url_target_attr = $url_new_window ? ' target="_blank"' : '';

        $hide_no_url = !empty($this->get_att('hide_no_url'));
        if ( $hide_no_url && empty($this->get_att('url')) && !$in_composer ) {
            return '';
        }

        // Video popup.
        $video_popup_enabled = !empty($this->get_att('video_enable'));
        $data_video_popup = '';

        if ( $video_popup_enabled ) {
            $video_url = $this->get_att('video_url');
            if ( !empty($video_url) ) {
                $data_video_popup = ' data-mfp-src="' . $video_url . '"';
            }
        }

        // Scroll to class.
        $scroll_to_class = $this->get_att('scroll_to_class');
        $data_scroll_to_class = '';
        $data_scroll_offset = '';

        if ( !$in_composer ) {
            if ( !empty($scroll_to_class) ) {
                $data_scroll_to_class = ' data-scroll-to-class="' . $scroll_to_class . '"';
            }

            $scroll_offset = $this->get_att('scroll_offset');
            if ( !empty($scroll_to_class) && !empty($scroll_offset) ) {
                $data_scroll_offset = ' data-scroll-offset="' . $scroll_offset . '"';
            }
        }

        // Google Analytics tracking.
        $has_analytics_events = false;
        $data_ga_event_cat = '';
        $data_ga_event_action = '';
        $data_ga_event_label = '';

        if ( !$in_composer ) {
            $ga_event_category = $this->get_att('ga_event_category');
            if ( !empty($ga_event_category) ) {
                $data_ga_event_cat = ' data-ga-event-cat="' . $ga_event_category . '"';
                $has_analytics_events = true;
            }

            $ga_event_action = $this->get_att('ga_event_action');
            if ( !empty($ga_event_action) ) {
                $data_ga_event_action = ' data-ga-event-action="' . $ga_event_action . '"';
                $has_analytics_events = true;
            }

            $ga_event_label = $this->get_att('ga_event_label');
            if ( !empty($ga_event_label) ) {
                $data_ga_event_label = ' data-ga-event-label="' . $ga_event_label . '"';
                $has_analytics_events = true;
            }
        }

        // FB pixel tracking.
        $data_fb_event_name = '';
        $data_fb_event_cotent_name = '';

        if ( !$in_composer ) {
            $fb_event_name = $this->get_att('fb_pixel_event_name');
            if ( !empty($fb_event_name) ) {
                $data_fb_event_name = ' data-fb-event-name="' . $fb_event_name . '"';
                $has_analytics_events = true;
            }

            $fb_event_content_name = $this->get_att('fb_pixel_event_content_name');
            if ( !empty($fb_event_content_name) ) {
                $data_fb_event_cotent_name = ' data-fb-event-content-name="' . $fb_event_content_name . '"';
                $has_analytics_events = true;
            }
        }

        // Set AI Builder settings.
        $this->set_aib_settings('button');

        /*
         * Build the shortcode HTML.
         */
        $buffy = '';
        $buffy .= '<div class="' . $this->get_block_classes($this->additional_classes) . '" ' . $this->get_block_html_atts() . $data_video_popup . $data_scroll_to_class . $data_scroll_offset . '>';
            // Get the block CSS.
            $buffy .= $this->get_block_css();

            $buffy .= '<a href="' . $url . '"' . $url_rel_attr . $url_target_attr . $title_attr . $data_ga_event_cat . $data_ga_event_action . $data_ga_event_label . $data_fb_event_name . $data_fb_event_cotent_name . '>';
                if ( $icon_pos == 'before' ) {
                    $buffy .= $buffy_icon;
                }

                if ( !empty($text) ) {
                    $buffy .= '<span class="tdm-btn-s-txt">' . $text . '</span>';
                }

                if ( $icon_pos == 'after' ) {
                    $buffy .= $buffy_icon;
                }
            $buffy .= '</a>';

            if ( $has_analytics_events ) {
                td_resources_load::render_script( TDC_SCRIPTS_URL . '/tdAnalytics.js' . TDC_SCRIPTS_VER, 'tdAnalytics-js', '', 'footer' );
            }
        $buffy .= '</div>';

        return $buffy;
    }
    
    /**
     * Sets AI Builder settings.
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
            $buffy .= td_util::get_block_error('Button', $message);
        $buffy .= '</div>';

        return $buffy;
    }

}