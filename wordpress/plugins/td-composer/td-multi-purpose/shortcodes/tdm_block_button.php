<?php
class tdm_block_button extends td_block {

	protected $shortcode_atts = array(); //the atts used for rendering the current block
    private $unique_block_class;

	static $style_atts_prefix = '';
	static $style_atts_uid = '';
	static $module_template_part_index = '';


    function __construct() {

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
                
                /* @style_general_button */
                .tdm_block.tdm_block_button {
                  margin-bottom: 0;
                }
                .tdm_block.tdm_block_button .tds-button {
                  line-height: 0;
                }
                .tdm_block.tdm_block_button.tdm-block-button-inline {
                  display: inline-block;
                }
                .tdm_block.tdm_block_button.tdm-block-button-full,
                .tdm_block.tdm_block_button.tdm-block-button-full .tdm-btn {
                  display: block;
                }
                
                /* @" . $style_atts_prefix . "float_right$style_atts_uid */
                .$style_selector {
                    float: right;
                    clear: none;
                }
                
                /* @" . $style_atts_prefix . "button_padding$style_atts_uid */
                .$style_selector .tdm-btn {
                    height: auto;
                    padding: @" . $style_atts_prefix . "button_padding$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "icon_align$style_atts_uid */
                .$style_selector .tdm-btn-icon {
                    position: relative;
                    top: @" . $style_atts_prefix . "icon_align$style_atts_uid;
                }
				
			</style>";

        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts() );

        $compiled_css .= $td_css_res_compiler->compile_css();

        return $compiled_css;

    }

    static function cssMedia( $res_ctx ) {

		$style_atts_prefix = self::$style_atts_prefix;
		$style_atts_uid = self::$style_atts_uid;
        

        $res_ctx->load_settings_raw( 'style_general_button', 1 );

        // make inline
        $res_ctx->load_settings_raw( $style_atts_prefix . 'float_right' . $style_atts_uid, $res_ctx->get_shortcode_att('float_right'));

        // button padding
        $button_padding = $res_ctx->get_shortcode_att('button_padding');
        $button_padding .= $button_padding != '' && is_numeric( $button_padding ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'button_padding' . $style_atts_uid, $button_padding);

        $icon_align = $res_ctx->get_shortcode_att( 'icon_align' );
        if ( $icon_align != '0' ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_align' . $style_atts_uid, $icon_align . 'px');
        }
    }

	function render( $atts, $content = null ) {

		parent::render($atts);

        $this->unique_block_class = $this->block_uid;

		$this->shortcode_atts = shortcode_atts(
			array_merge(
				td_api_multi_purpose::get_mapped_atts( __CLASS__ ),
                td_api_style::get_style_group_params( 'tds_button' )
			),
			$atts
		);

		$additional_classes = array();

		// button display
		$button_display = $this->get_shortcode_att('button_display');
        $data_inline = '';
        if ( '' !== $button_display ) {
            $additional_classes[] = $button_display;
        }

        // content align horizontal
		$content_align_horizontal = $this->get_shortcode_att('content_align_horizontal');
        if( ! empty( $content_align_horizontal ) ) {
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

		$buffy .= '<div class="tdm_block ' . $this->get_block_classes($additional_classes) . '" ' . $this->get_block_html_atts() . ' ' . $data_inline . ' ' . $data_video_popup . ' ' . $data_scroll_to_class . ' ' . $data_scroll_offset . '>';

            // get the block css
            $buffy .= $this->get_block_css();

            // button
            $button_text = $this->get_shortcode_att('button_text');
            $button_icon = $this->get_shortcode_att( 'button_tdicon' );

            // hide button if no URL
            $hide_button_no_url = $this->get_shortcode_att( 'button_hide_no_url' );
            $button_url = td_util::get_custom_field_value_from_string($this->get_shortcode_att('button_url'));
            $button_url = td_util::get_cloud_tpl_var_value_from_string( $button_url );

            $button_hide = '';
            if ( $hide_button_no_url == 'yes' && $button_url == '') {
                $button_hide = 'hide';
            }

            if ( (!empty($button_text) || !empty($button_icon)) && $button_hide !== 'hide' ) {
                $tds_button = $this->get_shortcode_att('tds_button');
                if ( empty($tds_button) ) {
                    $tds_button = td_util::get_option('tds_button', 'tds_button1');
                }
                $tds_button_instance = new $tds_button($this->shortcode_atts, '', $this->unique_block_class);
                $buffy .= $tds_button_instance->render();
            }
		$buffy .= '</div>';

		return $buffy;

	}

}