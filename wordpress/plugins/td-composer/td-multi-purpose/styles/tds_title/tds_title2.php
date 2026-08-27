<?php
/**
 * Created by PhpStorm.
 * User: tagdiv
 * Date: 13.07.2017
 * Time: 9:38
 */

class tds_title2 extends td_style {

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
        } else {
            $style_selector .= $this->unique_style_class;
        }

        
        $compiled_css = '';

		$raw_css =
			"<style>

				/* @" . $style_atts_prefix . "margin$style_atts_uid */
				body .$style_selector .tdm-title:not(p):not(div) {
					margin: @" . $style_atts_prefix . "margin$style_atts_uid;
				}
				
				/* @" . $style_atts_prefix . "title_color_solid$style_atts_uid */
				.$style_selector .tdm-title {
					color: @" . $style_atts_prefix . "title_color_solid$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "title_color_gradient$style_atts_uid */
				.$style_selector .tdm-title {
					@" . $style_atts_prefix . "title_color_gradient$style_atts_uid
					-webkit-background-clip: text;
					-webkit-text-fill-color: transparent;
				}
				html[class*='ie'] .$style_selector .tdm-title {
				    background: none;
					color: @" . $style_atts_prefix . "title_color_gradient_1$style_atts_uid;
				}

				/* @" . $style_atts_prefix . "hover_title_color$style_atts_uid */
				body .$style_selector:hover .tdm-title,
				body .tds_icon_box5_wrap:hover .$style_selector .tdm-title {
					color: @" . $style_atts_prefix . "hover_title_color$style_atts_uid;
				}
				.$style_selector:hover .tdm-title {
					cursor: default;
				}
				/* @" . $style_atts_prefix . "hover_gradient$style_atts_uid */
				body .$style_selector:hover .tdm-title,
				body .tds_icon_box5_wrap:hover .$style_selector .tdm-title {
					-webkit-text-fill-color: unset;
					background: transparent;
					transition: none;
				}
				
				/* @" . $style_atts_prefix . "line_color_gradient$style_atts_uid */
				body .$style_selector .tdm-title-line:after {
					@" . $style_atts_prefix . "line_color_gradient$style_atts_uid
				}
				/* @" . $style_atts_prefix . "line_color$style_atts_uid */
				body .$style_selector .tdm-title-line:after {
					background: @" . $style_atts_prefix . "line_color$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "hover_line_color_gradient$style_atts_uid */
				.$style_selector:hover .tdm-title-line:after,
				body .tds_icon_box5_wrap:hover .$style_selector .tdm-title-line:after {
					@" . $style_atts_prefix . "hover_line_color_gradient$style_atts_uid
				}
				/* @" . $style_atts_prefix . "hover_line_color$style_atts_uid */
				.$style_selector:hover .tdm-title-line:after,
				body .tds_icon_box5_wrap:hover .$style_selector .tdm-title-line:after {
					background: @" . $style_atts_prefix . "hover_line_color$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "line_width$style_atts_uid */
				.$style_selector .tdm-title-line  {
					width: @" . $style_atts_prefix . "line_width$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "hover_line_width$style_atts_uid */
				.$style_selector:hover .tdm-title-line,
				body .tds_icon_box5_wrap:hover .$style_selector .tdm-title-line  {
					width: @" . $style_atts_prefix . "hover_line_width$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "line_height$style_atts_uid */
				.$style_selector .tdm-title-line:after  {
					height: @" . $style_atts_prefix . "line_height$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "line_space$style_atts_uid */
				.$style_selector .tdm-title-line  {
					height: @" . $style_atts_prefix . "line_space$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "line_alignment$style_atts_uid */
				.$style_selector .tdm-title-line:after   {
					bottom: @" . $style_atts_prefix . "line_alignment$style_atts_uid;
				}



				/* @" . $style_atts_prefix . "f_title$style_atts_uid */
				.$style_selector .tdm-title {
					@" . $style_atts_prefix . "f_title$style_atts_uid
				}

			</style>";

        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->atts);

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

        /*-- TEXT -- */
        // H1-H6 tag margin
        $margin = $res_ctx->get_style_att( 'margin', __CLASS__ );
        $margin .= $margin != '' && is_numeric( $margin ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'margin' . $style_atts_uid, $margin );

        // text color
        $res_ctx->load_color_settings( 'title_color', $style_atts_prefix . 'title_color_solid' . $style_atts_uid, $style_atts_prefix . 'title_color_gradient' . $style_atts_uid, $style_atts_prefix . 'title_color_gradient_1' . $style_atts_uid, '', __CLASS__ );

        // text hover color
        $hover_title_color = $res_ctx->get_style_att( 'hover_title_color', __CLASS__ );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'hover_title_color' . $style_atts_uid, $hover_title_color );
        if ( !empty ($hover_title_color ) ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'hover_gradient' . $style_atts_uid, 1 );
        }

        /*-- LINE -- */
        // line width
        $td_line_width = $res_ctx->get_style_att( 'line_width', __CLASS__ );
		$td_line_width = $td_line_width != '' ? $td_line_width : '180px';
		$td_line_width .= is_numeric( $td_line_width ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'line_width' . $style_atts_uid, $td_line_width );

        // hover line width
        $td_hover_line_width = $res_ctx->get_style_att( 'hover_line_width', __CLASS__ );
		$td_hover_line_width .= $td_hover_line_width != '' && is_numeric( $td_hover_line_width ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'hover_line_width' . $style_atts_uid, $td_hover_line_width );

        // line height
        $td_line_height = $res_ctx->get_style_att( 'line_height', __CLASS__ );
		$td_line_height = $td_line_height != '' ? $td_line_height : '2px';
		$td_line_height .= is_numeric( $td_line_height ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'line_height' . $style_atts_uid, $td_line_height );

        // line space
        $td_line_space = $res_ctx->get_style_att( 'line_space', __CLASS__ );
		$td_line_space = $td_line_space != '' ? $td_line_space : '49px';
		$td_line_space .= is_numeric( $td_line_space ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'line_space' . $style_atts_uid, $td_line_space );


        // line alignment
        $td_line_alignment = $res_ctx->get_style_att( 'line_alignment', __CLASS__ );
        if( is_numeric( $td_line_alignment ) ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'line_alignment' . $style_atts_uid, $td_line_alignment . '%' );
        }

        // line color
        $res_ctx->load_color_settings( 'line_color', $style_atts_prefix . 'line_color' . $style_atts_uid, $style_atts_prefix . 'line_color_gradient' . $style_atts_uid, '', '', __CLASS__ );

        // hover line color
        $res_ctx->load_color_settings( 'hover_line_color', $style_atts_prefix . 'hover_line_color' . $style_atts_uid, $style_atts_prefix . 'hover_line_color_gradient' . $style_atts_uid, '', '', __CLASS__ );


        /*-- FONTS -- */
        $res_ctx->load_font_settings( 'f_title', __CLASS__, $style_atts_prefix, $style_atts_uid );

    }


	function render( $index_style = '' ) {

		if ( !empty($index_style) ) {
			$this->index_style = $index_style;
		}
        $this->unique_style_class = td_global::td_generate_unique_id();

		$title_tag = $this->get_shortcode_att( 'title_tag' );
		$title_size = $this->get_shortcode_att( 'title_size' );
        $title_text = td_util::get_custom_field_value_from_string( rawurldecode( base64_decode( strip_tags( $this->get_shortcode_att( 'title_text' ) ) ) ) );
        $title_text = td_util::get_cloud_tpl_var_value_from_string( $title_text );

		// Check to see if the element is being called into a tdb module template
		$in_tdb_module_template_class = '';
		if( td_global::get_in_tdb_module_template() ) {
            $in_tdb_module_template_class = self::get_class_style(__CLASS__) . '_' . self::$module_template_part_index;
        }

		$buffy = $this->get_style( $this->get_css() );

        $buffy .= '<div class="' . self::get_group_style( __CLASS__ ) . ' ' . self::get_class_style(__CLASS__) . ' td-fix-index ' . $this->unique_style_class . ' ' . $in_tdb_module_template_class . '">';
            $buffy .= '<' . $title_tag . ' class="tdm-title ' . $title_size . '">' . $title_text . '</' . $title_tag . '>';
            $buffy .= '<div class="tdm-title-line"></div>';
        $buffy .= '</div>';

		return $buffy;
	}

	function get_style_att( $att_name ) {
		return $this->get_att( $att_name ,__CLASS__, $this->index_style );
	}

	function get_atts() {
		return $this->atts;
	}
}
