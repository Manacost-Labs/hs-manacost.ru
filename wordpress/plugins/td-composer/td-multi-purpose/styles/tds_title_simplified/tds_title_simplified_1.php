<?php
/**
 * Created by PhpStorm.
 * User: tagdiv
 * Date: 13.07.2017
 * Time: 9:38
 */

class tds_title_simplified_1 extends td_style {
	
	private $atts = array();

	private $parent_block_unique_class = '';
	private $parent_block_css = '';

	private $style_element_classes = '';
	private $style_element_html_atts = '';

    private $unique_style_class;
	private $index_style;

    static $style_selector = '';
	static $style_atts_prefix = '';
	static $style_atts_uid = '';
	static $module_template_part_index = '';




    /**
     * Class constructor.
     *
     * @return void
     */
	function __construct( $atts, $parent_block_unique_class = '', $parent_block_css = '', $style_element_classes = '', $style_element_html_atts = '', $index_style = '' ) {

		$this->atts = $atts;

		$this->parent_block_unique_class = $parent_block_unique_class;
		$this->parent_block_css = $parent_block_css;

		$this->style_element_classes = $style_element_classes;
		$this->style_element_html_atts = $style_element_html_atts;

		$this->unique_style_class = td_global::td_generate_unique_id();
		$this->index_style = $index_style;


		/* --
		-- Set up the style element classes.
		-- */
		$this->style_element_classes .= ( !empty($this->style_element_classes) ? ' ' : '' ) . self::get_group_style( __CLASS__ ) . ' ' . self::get_class_style(__CLASS__) . ' ' . $this->unique_style_class . ' tdm-title';



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


			/* -- Add an additional class to the style element -- */
			$this->style_element_classes .= ' ' . self::get_class_style(__CLASS__) . '_' . self::$module_template_part_index;


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




    /**
     * Compiles the style element's CSS rules.
     *
     * @return string
     */
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


        /* -- Build the CSS. -- */
		$raw_css =
			"<style>

				/* @style_general_tds_title_simplified */
				.tds-title-simplified {
					margin: 0;
					transform: translateZ(0);
				}
				.tds-title-simplified > .td-element-style {
					z-index: -1;
				}
				
				
				/* @" . $style_atts_prefix . "horiz_align$style_atts_uid */
				body .$style_selector {
					text-align: @" . $style_atts_prefix . "horiz_align$style_atts_uid;
				}
				
				
				/* @" . $style_atts_prefix . "title_color_solid$style_atts_uid */
				body .$style_selector {
					color: @" . $style_atts_prefix . "title_color_solid$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "title_color_gradient$style_atts_uid */
				body .$style_selector {
					@" . $style_atts_prefix . "title_color_gradient$style_atts_uid
					-webkit-background-clip: text;
					-webkit-text-fill-color: transparent;
				}
				html[class*='ie'] .$style_selector {
				    background: none;
					color: @" . $style_atts_prefix . "title_color_gradient_1$style_atts_uid;
				}

				/* @" . $style_atts_prefix . "hover_title_color$style_atts_uid */
				body .$style_selector:hover,
				body .tds_icon_box5_wrap:hover .$style_selector {
					color: @" . $style_atts_prefix . "hover_title_color$style_atts_uid;
				}
				.$style_selector:hover {
					cursor: auto;
				}
				/* @" . $style_atts_prefix . "hover_gradient$style_atts_uid */
				body .$style_selector:hover,
				body .tds_icon_box5_wrap:hover .$style_selector {
					-webkit-text-fill-color: unset;
					background: transparent;
					transition: none;
				}


				/* @" . $style_atts_prefix . "f_title$style_atts_uid */
				.$style_selector {
					@" . $style_atts_prefix . "f_title$style_atts_uid
				}

			</style>";


		/* -- Compile the CSS and return it. -- */
        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->atts);


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
        $res_ctx->load_settings_raw( 'style_general_tds_title_simplified', 1 );
        $res_ctx->load_settings_raw( 'style_general_tds_title_simplified_1', 1 );




		/* --
		-- LAYOUT.
		-- */
		/* -- Horizontal align. -- */
		if ( isset($res_ctx->get_atts()['content_align_horizontal']) ) {
			$content_align = $res_ctx->get_shortcode_att('content_align_horizontal');

			switch ( $content_align ) {
				case '':
				case 'content-horiz-left':
					$res_ctx->load_settings_raw( $style_atts_prefix . 'horiz_align' . $style_atts_uid, 'left' );
					break;

				case 'content-horiz-center':
					$res_ctx->load_settings_raw( $style_atts_prefix . 'horiz_align' . $style_atts_uid, 'center' );
					break;

				case 'content-horiz-right':
					$res_ctx->load_settings_raw( $style_atts_prefix . 'horiz_align' . $style_atts_uid, 'right' );
					break;
			}
		}




		/* --
		-- COLORS.
		-- */
        $res_ctx->load_color_settings( 'title_color', $style_atts_prefix . 'title_color_solid' . $style_atts_uid, $style_atts_prefix . 'title_color_gradient' . $style_atts_uid, $style_atts_prefix . 'title_color_gradient_1' . $style_atts_uid, '', __CLASS__ );

        $hover_title_color = $res_ctx->get_style_att( 'hover_title_color', __CLASS__ );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'hover_title_color' . $style_atts_uid, $hover_title_color );
        if ( !empty($hover_title_color ) ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'hover_gradient' . $style_atts_uid, 1 );
        }




		/* --
		-- FONTS.
		-- */
        $res_ctx->load_font_settings( 'f_title', __CLASS__, $style_atts_prefix, $style_atts_uid );

    }




    /**
     * Renders the style element.
     *
     * @param array $atts
     * @param null $content
     * @return string
     */
	function render( $index_style = '' ) {

        /* --
        -- Style element attributes.
        -- */
		/* -- Text. -- */
		$title_text = td_util::get_custom_field_value_from_string( rawurldecode( base64_decode( strip_tags( $this->get_shortcode_att( 'title_text' ) ) ) ) );
		$title_text = td_util::get_cloud_tpl_var_value_from_string( $title_text );

		/* -- HTML tag. -- */
		$title_tag = $this->get_shortcode_att( 'title_tag' );

		/* -- Size class. -- */
		$title_size = $this->get_shortcode_att( 'title_size' ) != '' ? $this->get_shortcode_att( 'title_size' ) : 'tdm-title-md';
		$this->style_element_classes .= ' ' . $title_size;



        /* --
        -- Build the style element HTML.
        -- */
		$buffy = '';

		$buffy .= '<' . $title_tag . ' class="' . $this->style_element_classes . '" ' . $this->style_element_html_atts . '>';

			// Get the parent block CSS.
			if ( !empty( $this->parent_block_css ) ) {
				$buffy .= $this->parent_block_css;
			}

			// Get the style element CSS.
			$buffy .= $this->get_style($this->get_css());

			$buffy .= $title_text;

		$buffy .= '</' . $title_tag . '>';



        /* --
        -- Return the style element HTML.
        -- */
		return $buffy;

	}




	/**
	 * Retrieves a style attribute's value.
	 *
	 * @param string $att_name
	 * @return mixed
	 */
	function get_style_att( $att_name ) {
		return $this->get_att($att_name, __CLASS__, $this->index_style);
	}
	



	/**
	 * Retrieves all of the style element's atts.
	 *
	 * @return array
	 */
	function get_atts() {
		return $this->atts;
	}

}
