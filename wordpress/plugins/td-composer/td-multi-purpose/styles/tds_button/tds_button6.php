<?php
/**
 * Created by PhpStorm.
 * User: tagdiv
 * Date: 13.07.2017
 * Time: 9:39
 */

class tds_button6 extends td_style {

    private $unique_block_class;
    private $unique_style_class;
    private $atts = array();
    private $index_style;

    static $style_selector = '';
	static $style_atts_prefix = '';
	static $style_atts_uid = '';
	static $module_template_part_index = '';


    function __construct( $atts, $index_style = '', $unique_block_class = '') {

        $this->atts = $atts;
        $this->unique_block_class = $unique_block_class;
        $this->index_style = $index_style;



        /* --
        -- Check to see if the element is being called into a tdb module template
        -- */
        if( td_global::get_in_tdb_module_template() ) {

            global $tdb_module_template_params;


            /* -- Set the current module template part index, used for ensuring -- */
		    /* -- uniqueness between template parts of the same type -- */
            if( isset( $tdb_module_template_params['shortcodes'][self::get_class_style(__CLASS__)] ) ) {
                $tdb_module_template_params['shortcodes'][self::get_class_style(__CLASS__)]++;
            } else {
                $tdb_module_template_params['shortcodes'][self::get_class_style(__CLASS__)] = 0;
            }

            self::$module_template_part_index = $tdb_module_template_params['shortcodes'][self::get_class_style(__CLASS__)];

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
            self::$style_atts_uid = $tdb_module_template_params['template_class'] . '_' . self::get_class_style(__CLASS__) . '_' . self::$module_template_part_index;

        } else {

	        // reset static properties
	        self::$style_selector = '';
	        self::$style_atts_prefix = '';
	        self::$style_atts_uid = '';
	        self::$module_template_part_index = '';

        }

    }


    private function get_css() {

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

            $style_selector = $tdb_module_template_params['template_class'] . ' .' . $style_selector . self::get_class_style(__CLASS__) . '_' . self::$module_template_part_index;
        } else {
            $style_selector .= $this->unique_style_class;
        }


        /* -- Set the unique active block class selector; when the element -- */
		/* -- which has been set to be scrolled to by the button comes into view, -- */
		/* -- then this class is toggled -- */
        $unique_block_active_class = '';
        if ( ! empty( $this->unique_block_class ) ) {
            $unique_block_active_class = '.' . $this->unique_block_class . '.td-scroll-in-view .' . $this->unique_style_class;
        }


        $compiled_css = '';

		$raw_css =
			"<style>

                /* @" . $style_atts_prefix . "button_width$style_atts_uid */
                .$style_selector {
                    min-width: @" . $style_atts_prefix . "button_width$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "button_padding$style_atts_uid */
                .$style_selector {
                    padding: @" . $style_atts_prefix . "button_padding$style_atts_uid;
                    height: auto;
                    line-height: 1;
                }
				/* @" . $style_atts_prefix . "button_icon_size$style_atts_uid */
				.$style_selector i {
					font-size: @" . $style_atts_prefix . "button_icon_size$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "button_icon_svg_size$style_atts_uid */
				.$style_selector svg {
					width: @" . $style_atts_prefix . "button_icon_svg_size$style_atts_uid;
                    height: auto;
				}
				/* @" . $style_atts_prefix . "icon_left_margin$style_atts_uid */
				.$style_selector .tdm-btn-icon:last-child {
					margin-left: @" . $style_atts_prefix . "icon_left_margin$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "icon_right_margin$style_atts_uid */
				.$style_selector .tdm-btn-icon:first-child {
					margin-right: @" . $style_atts_prefix . "icon_right_margin$style_atts_uid;
				}

				/* @" . $style_atts_prefix . "text_color_solid$style_atts_uid */
				body .$style_selector .tdm-btn-text,
				body .$style_selector i {
					color: @" . $style_atts_prefix . "text_color_solid$style_atts_uid;
				}
				.$style_selector svg {
				    fill: @" . $style_atts_prefix . "text_color_solid$style_atts_uid;
				}
				.$style_selector svg * {
				    fill: inherit;
				}
				/* @" . $style_atts_prefix . "text_color_gradient$style_atts_uid */
				body .$style_selector .tdm-btn-text,
				body .$style_selector i {
					@" . $style_atts_prefix . "text_color_gradient$style_atts_uid
					-webkit-background-clip: text;
					-webkit-text-fill-color: transparent;
				}
				html[class*='ie'] .$style_selector .tdm-btn-text,
				html[class*='ie'] .$style_selector i {
				    background: none;
					color: @" . $style_atts_prefix . "text_color_gradient_1$style_atts_uid;
				}
				body .$style_selector svg {
				    fill: @" . $style_atts_prefix . "text_color_gradient_1$style_atts_uid;
				}
				body .$style_selector svg * {
				    fill: inherit;
				}
				/* @" . $style_atts_prefix . "text_hover_color$style_atts_uid */
				body .$style_selector:hover .tdm-btn-text,
				body .$style_selector:hover i {
					color: @" . $style_atts_prefix . "text_hover_color$style_atts_uid;
				}
				body .$style_selector:hover svg {
				    fill: @" . $style_atts_prefix . "text_hover_color$style_atts_uid;
				}
				body .$style_selector:hover svg * {
				    fill: inherit;
				}
				/* @" . $style_atts_prefix . "text_active_color$style_atts_uid */
				body $unique_block_active_class .tdm-btn-text,
				body $unique_block_active_class i {
					color: @" . $style_atts_prefix . "text_active_color$style_atts_uid;
				}
				body $unique_block_active_class svg {
				    fill: @" . $style_atts_prefix . "text_active_color$style_atts_uid;
				}
				body $unique_block_active_class svg * {
				    fill: inherit;
				}
				/* @" . $style_atts_prefix . "text_hover_gradient$style_atts_uid */
				body .$style_selector:hover .tdm-btn-text,
				body .$style_selector:hover i {
					-webkit-text-fill-color: unset;
					background: transparent;
					transition: none;
				}
				/* @" . $style_atts_prefix . "text_active_gradient$style_atts_uid */
				body $unique_block_active_class .tdm-btn-text,
				body $unique_block_active_class .tdm-btn-text i {
					-webkit-text-fill-color: unset;
					background: transparent;
					transition: none;
				}

				/* @" . $style_atts_prefix . "icon_color_solid$style_atts_uid */
				body .$style_selector i {
					color: @" . $style_atts_prefix . "icon_color_solid$style_atts_uid;
				    -webkit-text-fill-color: unset;
    				background: transparent;
				}
				body .$style_selector svg {
				    fill: @" . $style_atts_prefix . "icon_color_solid$style_atts_uid;
				}
				body .$style_selector svg * {
				    fill: inherit;
				}
				/* @" . $style_atts_prefix . "icon_color_gradient$style_atts_uid */
				body .$style_selector i {
					@" . $style_atts_prefix . "icon_color_gradient$style_atts_uid
					-webkit-background-clip: text;
					-webkit-text-fill-color: transparent;
				}
				html[class*='ie'] body .$style_selector i {
				    background: none;
					color: @" . $style_atts_prefix . "icon_color_gradient_1$style_atts_uid;
				}
				body .$style_selector svg {
				    fill: @" . $style_atts_prefix . "icon_color_gradient_1$style_atts_uid;
				}
				body .$style_selector svg * {
				    fill: inherit;
				}

				/* @" . $style_atts_prefix . "icon_hover_color$style_atts_uid */
				body .$style_selector:hover i {
					color: @" . $style_atts_prefix . "icon_hover_color$style_atts_uid;
				}
				body .$style_selector:hover svg {
				    fill: @" . $style_atts_prefix . "icon_hover_color$style_atts_uid;
				}
				body .$style_selector:hover svg * {
				    fill: inherit;
				}
				/* @" . $style_atts_prefix . "icon_active_color$style_atts_uid */
				body $unique_block_active_class i {
					color: @" . $style_atts_prefix . "icon_active_color$style_atts_uid;
				}
				body $unique_block_active_class svg {
				    fill: @" . $style_atts_prefix . "icon_active_color$style_atts_uid;
				}
				body $unique_block_active_class svg * {
				    fill: inherit;
				}
				/* @" . $style_atts_prefix . "icon_hover_gradient$style_atts_uid */
				body .$style_selector:hover i {
					-webkit-text-fill-color: unset;
					background: transparent;
					transition: none;
				}
				/* @" . $style_atts_prefix . "icon_active_gradient$style_atts_uid */
				body $unique_block_active_class i {
					-webkit-text-fill-color: unset;
					background: transparent;
					transition: none;
				}


				/* @" . $style_atts_prefix . "border_color_solid$style_atts_uid */
				.$style_selector:before {
					border-color: @" . $style_atts_prefix . "border_color_solid$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "border_color_params$style_atts_uid */
				.$style_selector:before {
				    border-image: linear-gradient(@" . $style_atts_prefix . "border_color_params$style_atts_uid);
				    border-image: -webkit-linear-gradient(@" . $style_atts_prefix . "border_color_params$style_atts_uid);
				    border-image-slice: 1;
				    transition: none;
				}
				body .$style_selector:hover:before {
				    border-image: linear-gradient(@" . $style_atts_prefix . "border_hover_color$style_atts_uid, @" . $style_atts_prefix . "border_hover_color$style_atts_uid);
				    border-image: -webkit-linear-gradient(@" . $style_atts_prefix . "border_hover_color$style_atts_uid, @" . $style_atts_prefix . "border_hover_color$style_atts_uid);
				    border-image-slice: 1;
				    transition: none;
				}
				/* @" . $style_atts_prefix . "border_hover_color$style_atts_uid */
				body .$style_selector:hover:before {
					border-color: @" . $style_atts_prefix . "border_hover_color$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "border_active_color$style_atts_uid */
				body $unique_block_active_class:before {
					border-color: @" . $style_atts_prefix . "border_active_color$style_atts_uid;
				}

				/* @" . $style_atts_prefix . "border_size$style_atts_uid */
				.$style_selector:before {
					border-width: @" . $style_atts_prefix . "border_size$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "border_style$style_atts_uid */
				.$style_selector:before {
					border-style: @" . $style_atts_prefix . "border_style$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "border_radius$style_atts_uid */
				.$style_selector,
				.$style_selector:before,
				.$style_selector:after {
					border-radius: @" . $style_atts_prefix . "border_radius$style_atts_uid;
				}
				
				
				/* @" . $style_atts_prefix . "shadow_offset$style_atts_uid */
				.$style_selector:after {
					top: @" . $style_atts_prefix . "shadow_offset$style_atts_uid;
					left: @" . $style_atts_prefix . "shadow_offset$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "shadow_hover_offset$style_atts_uid */
				.$style_selector:hover:after {
					top: @" . $style_atts_prefix . "shadow_hover_offset$style_atts_uid;
					left: @" . $style_atts_prefix . "shadow_hover_offset$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "shadow_active_offset$style_atts_uid */
				$unique_block_active_class:after {
					top: @" . $style_atts_prefix . "shadow_active_offset$style_atts_uid;
					left: @" . $style_atts_prefix . "shadow_active_offset$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "shadow_color$style_atts_uid */
				.$style_selector:after {
					background-color: @" . $style_atts_prefix . "shadow_color$style_atts_uid;
					opacity: 1;
				}
				/* @" . $style_atts_prefix . "shadow_hover_color$style_atts_uid */
				.$style_selector:hover:after {
					background-color: @" . $style_atts_prefix . "shadow_hover_color$style_atts_uid;
					opacity: 1;
				}
				/* @" . $style_atts_prefix . "shadow_active_color$style_atts_uid */
				$unique_block_active_class:after {
					background-color: @" . $style_atts_prefix . "shadow_active_color$style_atts_uid;
					opacity: 1;
				}



				/* @" . $style_atts_prefix . "f_btn_text$style_atts_uid */
				.$style_selector {
					@" . $style_atts_prefix . "f_btn_text$style_atts_uid
				}
				/* @" . $style_atts_prefix . "f_btn_text_line_height$style_atts_uid */
				.$style_selector {
					height: auto;
				}

			</style>";


        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->atts, $this->index_style );

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

        $atts = $res_ctx->get_atts();
        $scroll_to_class = '';
        if( isset( $atts['scroll_to_class'] ) && !td_global::get_in_tdb_module_template() ) {
            $scroll_to_class = $res_ctx->get_shortcode_att('scroll_to_class');
        }

        // button width
        $button_width = $res_ctx->get_shortcode_att( 'button_width' );
		$button_width .= $button_width != '' && is_numeric( $button_width ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'button_width' . $style_atts_uid, $button_width );

        $button_padding = $res_ctx->get_shortcode_att('button_padding');
		$button_padding .= $button_padding != '' && is_numeric( $button_padding ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'button_padding' . $style_atts_uid, $button_padding );

        /*-- TEXT -- */
        // text color
        $res_ctx->load_color_settings( 'text_color', $style_atts_prefix . 'text_color_solid' . $style_atts_uid, $style_atts_prefix . 'text_color_gradient' . $style_atts_uid, $style_atts_prefix . 'text_color_gradient_1' . $style_atts_uid, '', __CLASS__ );

        // text hover color
        $text_hover_color = $res_ctx->get_style_att( 'text_hover_color', __CLASS__ );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'text_hover_color' . $style_atts_uid, $text_hover_color);
        if ( !empty ($text_hover_color ) ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'text_hover_gradient' . $style_atts_uid, 1 );
        }
        if( $scroll_to_class != '' ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'text_active_color' . $style_atts_uid, $text_hover_color);
            if ( !empty ($text_hover_color ) ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'text_active_gradient' . $style_atts_uid, 1 );
            }
        }


        /*-- ICON -- */
        $button_icon = $res_ctx->get_icon_att('button_tdicon' );
        // icon size
        $icon_size = $res_ctx->get_shortcode_att('button_icon_size' );
		$icon_size .= $button_width != '' && is_numeric( $icon_size ) ? 'px' : '';
        if( base64_encode( base64_decode( $button_icon ) ) == $button_icon ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'button_icon_svg_size' . $style_atts_uid, $icon_size );
        } else {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'button_icon_size' . $style_atts_uid, $icon_size );
        }

        // icon space
        if ( !empty ( $button_icon ) ) {
            $icon_space = $res_ctx->get_shortcode_att( 'button_icon_space' );
            $icon_space .= $icon_space != '' && is_numeric( $icon_space ) ? 'px' : '';

            if ( $res_ctx->get_shortcode_att( 'button_icon_position' ) === '') {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_left_margin' . $style_atts_uid, $icon_space );
            } else {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_right_margin' . $style_atts_uid, $icon_space );
            }
        }

        // icon color
        $res_ctx->load_color_settings( 'icon_color', $style_atts_prefix . 'icon_color_solid' . $style_atts_uid, $style_atts_prefix . 'icon_color_gradient' . $style_atts_uid, $style_atts_prefix . 'icon_color_gradient_1' . $style_atts_uid, '', __CLASS__ );

        // icon hover color
        $icon_hover_color = $res_ctx->get_style_att( 'icon_hover_color', __CLASS__ );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_hover_color' . $style_atts_uid, $icon_hover_color);
        if ( !empty ($icon_hover_color ) ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_hover_gradient' . $style_atts_uid, 1 );
        }
        if( $scroll_to_class != '' ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_active_color' . $style_atts_uid, $icon_hover_color);
            if ( !empty ($icon_hover_color ) ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_active_gradient' . $style_atts_uid, 1 );
            }
        }


        /*-- SHADOW -- */
        // shadow offset
        $res_ctx->load_settings_raw( $style_atts_prefix . 'shadow_offset' . $style_atts_uid, $res_ctx->get_style_att( 'shadow_offset', __CLASS__ ) . 'px' );

        // shadow color
        $res_ctx->load_settings_raw( $style_atts_prefix . 'shadow_color' . $style_atts_uid, $res_ctx->get_style_att( 'shadow_color', __CLASS__ ) );

        // shadow hover offset
        $res_ctx->load_settings_raw( $style_atts_prefix . 'shadow_hover_offset' . $style_atts_uid, $res_ctx->get_style_att( 'shadow_hover_offset', __CLASS__ ) . 'px' );
        if( $scroll_to_class != '' ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'shadow_active_offset' . $style_atts_uid, $res_ctx->get_style_att( 'shadow_hover_offset', __CLASS__ ) . 'px' );
        }

        // shadow hover color
        $res_ctx->load_settings_raw( $style_atts_prefix . 'shadow_hover_color' . $style_atts_uid, $res_ctx->get_style_att( 'shadow_hover_color', __CLASS__ ) );
        if( $scroll_to_class != '' ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'shadow_active_color' . $style_atts_uid, $res_ctx->get_style_att( 'shadow_hover_color', __CLASS__ ) );
        }


        /*-- BORDER -- */
        // border size
        $border_size = $res_ctx->get_style_att( 'border_size', __CLASS__ );
        $border_size = $border_size != '' ? $border_size : '1px';
        $border_size .= is_numeric( $border_size ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'border_size' . $style_atts_uid, $border_size );

        // border style
        $border_style = $res_ctx->get_style_att( 'border_style', __CLASS__ );
		$border_style = $border_style != '' ? $border_style : 'solid';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'border_style' . $style_atts_uid, $border_style );

        // border color
        $res_ctx->load_color_settings( 'border_color', $style_atts_prefix . 'border_color_solid' . $style_atts_uid, $style_atts_prefix . 'border_color_gradient' . $style_atts_uid, $style_atts_prefix . 'icon_color_gradient_1' . $style_atts_uid, '', __CLASS__ );

        // border hover color
        $border_hover_color = $res_ctx->get_style_att( 'border_hover_color', __CLASS__ );
        if ( !empty( $border_hover_color ) ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'border_hover_color' . $style_atts_uid, $border_hover_color);
        }
        if( $scroll_to_class != '' ) {
            if ( !empty( $border_hover_color ) ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'border_active_color' . $style_atts_uid, $border_hover_color);
            }
        }

        // border radius
        $border_radius = $res_ctx->get_style_att( 'border_radius', __CLASS__ );
		$border_radius .= $border_radius != '' && is_numeric( $border_radius ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'border_radius' . $style_atts_uid, $border_radius );


        /*-- FONTS -- */
        $res_ctx->load_font_settings( 'f_btn_text', __CLASS__, $style_atts_prefix, $style_atts_uid );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'f_btn_text_line_height' . $style_atts_uid, $res_ctx->get_style_att( 'f_btn_text_font_line_height', __CLASS__ ) );

    }

    function render( $index_style = '' ) {

        if ( ! empty( $index_style ) ) {
            $this->index_style = $index_style;
        }
        $this->unique_style_class = td_global::td_generate_unique_id();


        $button_text = td_util::get_custom_field_value_from_string($this->get_shortcode_att('button_text', $this->index_style));
        $button_text = td_util::get_cloud_tpl_var_value_from_string( $button_text );

        $icon = $this->get_icon_att('button_tdicon', $this->index_style);
        $icon_data = '';
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $icon_data = 'data-td-svg-icon="' . $this->get_icon_att( 'button_tdicon', $this->index_style ) . '"';
        }

        $icon_aria_label = $this->get_icon_att( 'button_icon_aria', $this->index_style );
        $icon_aria_label = !empty($icon_aria_label) ? 'aria-label="' . $icon_aria_label . '"' : '';
        $title_attr = $icon_aria_label == '' ? ' title="' . $button_text . '"' : $icon_aria_label;

        $icon_position = $this->get_shortcode_att('button_icon_position', $this->index_style);

        $target = '';
        if ( '' !== $this->get_shortcode_att('button_open_in_new_window', $this->index_style)) {
            $target = ' target="_blank" ';
        }

	    $button_url = td_util::get_custom_field_value_from_string($this->get_shortcode_att('button_url', $this->index_style));
        $button_url = td_util::get_cloud_tpl_var_value_from_string( $button_url );
	    if ( '' == $button_url) {
		    $button_url = '#';
	    }

        //set rel attribute on button url
        $td_link_rel = '';
        if ( '' !== $this->get_shortcode_att('button_url_rel', $this->index_style) ) {
            $td_link_rel = ' rel="' . $this->get_shortcode_att('button_url_rel', $this->index_style) . '" ';
        }

        $buffy_icon = '';
        if ( !empty( $icon ) ) {
            if( base64_encode( base64_decode( $icon ) ) == $icon ) {
                $buffy_icon .= '<span class="tdm-btn-icon tdm-btn-icon-svg" ' . $icon_data . '>' . base64_decode( $icon ) . '</span>';
            } else {
                $buffy_icon .= '<i class="tdm-btn-icon ' . $icon . '"></i>';
            }
        }

		// Check to see if the element is being called into a tdb module template
		$in_tdb_module_template_class = '';
		if( td_global::get_in_tdb_module_template() ) {
            $in_tdb_module_template_class = self::get_class_style(__CLASS__) . '_' . self::$module_template_part_index;
        }


        /**
         * Has Analytics tracking flag
         */
        $has_analytics_events = false;


        /**
         * Google Analytics tracking settings
         */
        $data_ga_event_cat = '';
        $data_ga_event_action = '';
        $data_ga_event_label = '';

        // don't add tracking options in td composer
        if ( !tdc_state::is_live_editor_ajax() && !tdc_state::is_live_editor_iframe() ) {
            $ga_event_category = $this->get_shortcode_att('ga_event_category');
            if ( ! empty( $ga_event_category ) ) {
                $data_ga_event_cat = ' data-ga-event-cat="' . $ga_event_category . '" ';
                $has_analytics_events = true;
            }

            $ga_event_action = $this->get_shortcode_att('ga_event_action');
            if ( ! empty( $ga_event_action ) ) {
                $data_ga_event_action = ' data-ga-event-action="' . $ga_event_action . '" ';
                $has_analytics_events = true;
            }

            $ga_event_label = $this->get_shortcode_att('ga_event_label');
            if ( ! empty( $ga_event_label ) ) {
                $data_ga_event_label = ' data-ga-event-label="' . $ga_event_label . '" ';
                $has_analytics_events = true;
            }
        }


        /**
         * FB Pixel tracking settings
         */
        $data_fb_event_name = '';
        $data_fb_event_cotent_name = '';

        // don't add tracking options in td composer
        if ( !tdc_state::is_live_editor_ajax() && !tdc_state::is_live_editor_iframe() ) {
            $fb_event_name = $this->get_shortcode_att('fb_pixel_event_name');
            if ( ! empty( $fb_event_name ) ) {
                $data_fb_event_name = ' data-fb-event-name="' . $fb_event_name . '" ';
                $has_analytics_events = true;
            }
            $fb_event_content_name = $this->get_shortcode_att('fb_pixel_event_content_name');
            if ( ! empty( $fb_event_content_name ) ) {
                $data_fb_event_cotent_name = ' data-fb-event-content-name="' . $fb_event_content_name . '" ';
                $has_analytics_events = true;
            }
        }


        $buffy = $this->get_style( $this->get_css() );

        $buffy .= '<div class="' . self::get_group_style( __CLASS__ ) . ' td-fix-index">';
            $buffy .= '<a href="' . $button_url . '" ' . $title_attr . ' class="' . self::get_class_style(__CLASS__) . ' tdm-btn ' . $this->get_shortcode_att('button_size', $this->index_style) . ' ' . $this->unique_style_class . ' ' . $in_tdb_module_template_class . '" ' . $td_link_rel . $target . $data_ga_event_cat . $data_ga_event_action . $data_ga_event_label . $data_fb_event_name . $data_fb_event_cotent_name . '>';
                if ( $icon_position == 'icon-before' ) {
                    $buffy .= $buffy_icon;
                }

                $buffy .= '<span class="tdm-btn-text">' . $button_text . '</span>';

                if ( $icon_position == '' ) {
                    $buffy .= $buffy_icon;
                }
            $buffy .= '</a>';
        $buffy .= '</div>';


        if( $has_analytics_events ) {
            td_resources_load::render_script( TDC_SCRIPTS_URL . '/tdAnalytics.js' . TDC_SCRIPTS_VER, 'tdAnalytics-js', '', 'footer' );
        }


		return $buffy;
	}

    function get_style_att( $att_name ) {
	    return $this->get_att( $att_name, __CLASS__, $this->index_style );
    }

    function get_atts() {
        return $this->atts;
    }
}
