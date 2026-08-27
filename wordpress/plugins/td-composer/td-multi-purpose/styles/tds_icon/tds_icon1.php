<?php
/**
 * Created by PhpStorm.
 * User: tagdiv
 * Date: 13.07.2017
 * Time: 9:38
 */

class tds_icon1 extends td_style {

    private $unique_block_class;
	private $unique_style_class;
    private $atts = array();
    private $index_style;

    static $style_selector = '';
	static $style_atts_prefix = '';
	static $style_atts_uid = '';
	static $module_template_part_index = '';

    function __construct( $atts, $unique_block_class = '', $index_style = '') {

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
		$unique_block_class_hover = '';

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

            $style_selector = $tdb_module_template_params['template_class'] . ' .' . $style_selector .  self::get_class_style(__CLASS__) . '_' . self::$module_template_part_index;

			$unique_block_class_hover = '.' . $style_selector . ':hover';
        } else {
            $style_selector .= $this->unique_style_class;

			$unique_block_class_hover = '.' . $this->unique_style_class . ':hover';
			if ( ! empty( $this->unique_block_class ) ) {
				$unique_block_class_hover = '.' . $this->unique_block_class . ':hover .' . $this->unique_style_class;
			}
        }

        
        $compiled_css = '';

		$raw_css =
			"<style>

                /* @" . $style_atts_prefix . "transition$style_atts_uid */
				.$style_selector {
				    -webkit-transition: all 0.2s ease;
                    -moz-transition: all 0.2s ease;
                    -o-transition: all 0.2s ease;
                    transition: all 0.2s ease;
				} 
				.$style_selector:before {
				    -webkit-transition: all 0.2s ease;
                    -moz-transition: all 0.2s ease;
                    -o-transition: all 0.2s ease;
                    transition: all 0.2s ease;
				}

				/* @" . $style_atts_prefix . "text_color_solid$style_atts_uid */
				.$style_selector:before {
					color: @" . $style_atts_prefix . "text_color_solid$style_atts_uid;
				}
				.$style_selector svg {
				    fill: @" . $style_atts_prefix . "text_color_solid$style_atts_uid;
				}
				.$style_selector svg * {
				    fill: inherit;
				}
				/* @" . $style_atts_prefix . "text_color_gradient$style_atts_uid */
				.$style_selector:before {
					@" . $style_atts_prefix . "text_color_gradient$style_atts_uid
					-webkit-background-clip: text;
					-webkit-text-fill-color: transparent;
				}
				html[class*='ie'] .$style_selector:before {
				    background: none;
					color: @" . $style_atts_prefix . "text_color_gradient_1$style_atts_uid;
				}
				.$style_selector svg {
				    fill: @" . $style_atts_prefix . "text_color_gradient_1$style_atts_uid;
				}
				.$style_selector svg * {
				    fill: inherit;
				}
				/* @" . $style_atts_prefix . "text_hover_color$style_atts_uid */
				body $unique_block_class_hover:before {
					color: @" . $style_atts_prefix . "text_hover_color$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "text_hover_gradient$style_atts_uid */
				body $unique_block_class_hover:before {
					-webkit-text-fill-color: unset;
					background: transparent;
					transition: none;
				}
				body $unique_block_class_hover svg {
				    fill: @" . $style_atts_prefix . "text_hover_color$style_atts_uid;
				}
				body $unique_block_class_hover svg * {
				    fill: inherit;
				}

				/* @" . $style_atts_prefix . "background_solid$style_atts_uid */
				.$style_selector {
					background-color: @" . $style_atts_prefix . "background_solid$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "background_gradient$style_atts_uid */
				.$style_selector {
					@" . $style_atts_prefix . "background_gradient
				}
				/* @" . $style_atts_prefix . "background_hover_solid$style_atts_uid */
				.$style_selector:after {
					background-color: @" . $style_atts_prefix . "background_hover_solid$style_atts_uid;
				}
				$unique_block_class_hover:after {
					opacity: 1;
				}
				/* @" . $style_atts_prefix . "background_hover_gradient$style_atts_uid */
				.$style_selector:after {
					@" . $style_atts_prefix . "background_hover_gradient$style_atts_uid
				}
				$unique_block_class_hover:after {
					opacity: 1;
				}







				/* @" . $style_atts_prefix . "hover_color$style_atts_uid */
				$unique_block_class_hover:before {
				    color: @" . $style_atts_prefix . "hover_color$style_atts_uid;
				}
				$unique_block_class_hover svg {
				    fill: @" . $style_atts_prefix . "hover_color$style_atts_uid;
				}
				$unique_block_class_hover svg * {
				    fill: inherit;
				}

				
				/* @" . $style_atts_prefix . "shadow$style_atts_uid */
				.$style_selector {
				    box-shadow: @" . $style_atts_prefix . "shadow$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "shadow_hover$style_atts_uid */
				$unique_block_class_hover {
				    box-shadow: @" . $style_atts_prefix . "shadow_hover$style_atts_uid;
				}
				
			
				/* @" . $style_atts_prefix . "all_border_size$style_atts_uid */
				.$style_selector {
				    border: @" . $style_atts_prefix . "all_border_size$style_atts_uid @" . $style_atts_prefix . "all_border_style$style_atts_uid @" . $style_atts_prefix . "all_border_color$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "hover_border_color$style_atts_uid */
				$unique_block_class_hover {
				    border-color: @" . $style_atts_prefix . "hover_border_color$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "border_radius$style_atts_uid */
				.$style_selector,
				.$style_selector:after {
				    border-radius: @" . $style_atts_prefix . "border_radius$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "hover_border_radius$style_atts_uid */
				$unique_block_class_hover,
				$unique_block_class_hover:after {
				    border-radius: @" . $style_atts_prefix . "hover_border_radius$style_atts_uid;
				}
                          
               
			</style>";

        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->atts );

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

        // transition
        $res_ctx->load_settings_raw( $style_atts_prefix . 'transition' . $style_atts_uid, 1);

        /*-- BACKGROUND -- */
        $res_ctx->load_color_settings( 'bg_color', $style_atts_prefix . 'background_solid' . $style_atts_uid, $style_atts_prefix . 'background_gradient' . $style_atts_uid, '', '', __CLASS__ );

        // background hover
        $res_ctx->load_color_settings( 'hover_bg_color', $style_atts_prefix . 'background_hover_solid' . $style_atts_uid, $style_atts_prefix . 'background_hover_gradient' . $style_atts_uid, '', '', __CLASS__ );

        /*-- TEXT -- */
        // text color
        $res_ctx->load_color_settings( 'color', $style_atts_prefix . 'text_color_solid' . $style_atts_uid, $style_atts_prefix . 'text_color_gradient' . $style_atts_uid, $style_atts_prefix . 'text_color_gradient_1' . $style_atts_uid, '', __CLASS__ );

        // text hover color
        $hover_title_color = $res_ctx->get_style_att( 'hover_color', __CLASS__ );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'hover_color' . $style_atts_uid, $hover_title_color );
        if ( !empty ($hover_title_color ) ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'text_hover_gradient' . $style_atts_uid, 1 );
        }

        /*-- SHADOW -- */
        $res_ctx->load_shadow_settings( 0, 0, 0, 0, 'rgba(0, 0, 0, 0.15)', 'shadow', __CLASS__, false, $style_atts_prefix, $style_atts_uid  );
        $res_ctx->load_shadow_settings( 0, 0, 0, 0, 'rgba(0, 0, 0, 0.15)', 'shadow_hover', __CLASS__, false, $style_atts_prefix, $style_atts_uid  );

        /*-- BORDER -- */
        // border size
        $border_size = $res_ctx->get_style_att( 'all_border_size', __CLASS__ );
		$border_size .= $border_size != '' && is_numeric( $border_size ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'all_border_size' . $style_atts_uid, $border_size );

        // border style
        $border_style = $res_ctx->get_style_att( 'all_border_style', __CLASS__ );
		$border_style = $border_style != '' ? $border_style : 'solid';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'all_border_style' . $style_atts_uid, $border_style );

        // border color
        $border_color = $res_ctx->get_style_att( 'all_border_color', __CLASS__ );
		$border_color = $border_color != '' ? $border_color : '#666';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'all_border_color' . $style_atts_uid, $border_color );

        // border hover color
        $res_ctx->load_settings_raw( $style_atts_prefix . 'hover_border_color' . $style_atts_uid, $res_ctx->get_style_att( 'hover_border_color', __CLASS__ ) );

        // border radius
        $border_radius = $res_ctx->get_style_att( 'border_radius', __CLASS__ );
		$border_radius .= $border_radius != '' && is_numeric( $border_radius ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'border_radius' . $style_atts_uid, $border_radius );

        // border hover radius
        $border_hover_radius = $res_ctx->get_style_att( 'hover_border_radius', __CLASS__ );
		$border_hover_radius .= $border_hover_radius != '' && is_numeric( $border_hover_radius ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'hover_border_radius' . $style_atts_uid, $border_hover_radius );

    }

    function render( $index_style = '' ) {

        if ( ! empty( $index_style ) ) {
            $this->index_style = $index_style;
        }
	    $this->unique_style_class = td_global::td_generate_unique_id();

        // icon
        $icon = $this->get_icon_att('tdicon_id');
        $data_icon = '';
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $data_icon = 'data-td-svg-icon="' . $this->get_att('tdicon_id') . '"';
        }

        $svg_code = rawurldecode( base64_decode( strip_tags( $this->get_shortcode_att('svg_code') ) ) );
        if( $svg_code == '' ) {
            if( base64_encode( base64_decode( $icon ) ) == $icon ) {
                $svg_code = base64_decode( $icon );
            }
        }

		// Check to see if the element is being called into a tdb module template
		$in_tdb_module_template_class = '';
		if( td_global::get_in_tdb_module_template() ) {
            $in_tdb_module_template_class = self::get_class_style(__CLASS__) . '_' . self::$module_template_part_index;
        }

	    $buffy = $this->get_style( $this->get_css() );

	    if( $svg_code == '' ) {
            $buffy .= '<i class="' . self::get_group_style( __CLASS__ ) . ' ' . $icon . ' ' . $this->unique_style_class . ' td-fix-index ' . $in_tdb_module_template_class . '"></i>';
        } else {
	        $buffy .= '<div class="' . self::get_group_style( __CLASS__ ) . ' tds-icon-svg-wrap ' . $this->unique_style_class . ' td-fix-index ' . $in_tdb_module_template_class . '"><div class="tds-icon-svg" ' . $data_icon . '>' . $svg_code . '</div></div>';
        }

	    return $buffy;
	}

    function get_style_att( $att_name ) {
        return $this->get_att( $att_name ,__CLASS__, $this->index_style );
    }

    function get_atts() {
        return $this->atts;
    }
}
