<?php
class tdm_block_inline_image_simplified extends td_block {

    protected $additional_classes = array();
    protected $additional_html_atts = array();
    private static $video_popup_enabled = false;
    private static $modal_image_enabled = false;
    private static $caption_enabled = false;
    private static $wrapper_tag_open = 'div';
    private static $wrapper_tag_close = 'div';
    private static $image_info = array();
    private static $image_wrapper_classes = array();
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
        self::$video_popup_enabled = false;
        self::$modal_image_enabled = false;
        self::$caption_enabled = false;
        self::$wrapper_tag_open = 'div';
        self::$wrapper_tag_close = 'div';
        self::$image_info = array();
        self::$image_wrapper_classes = array();
        self::$aib_component_style = null;

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

        $unique_block_modal_class = $this->block_uid . '_m';

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
                /* @style_general_tdm_block_inline_image_simplified */
                .tdm_block_inline_image_simplified {
                    display: inline-flex;
                    flex-direction: column;
                    align-items: flex-start;
                    position: relative;
                    margin-bottom: 0;
                }
				.tdm_block_inline_image_simplified > .td-element-style {
                    z-index: -1;
                }
                .tdm_block_inline_image_simplified .tdm-ii-wrap {
                    position: relative;
                    max-width: 100%;
                }
                .tdm_block_inline_image_simplified .tdm-ii-wrap:before,
                .tdm_block_inline_image_simplified .tdm-ii-wrap:after {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    transition: opacity 1s ease;
                    z-index: 1;
                }
                .tdm_block_inline_image_simplified img {
                    display: block;
                    width: 100%;
                    border: 0 solid #000;
                    transition: all 1s ease;
                }
                /* @style_general_tdm_block_inline_image_simplified_video_popup */
                .tdm_block_inline_image_simplified.td-image-video-modal {
                    cursor: pointer;
                }
                /* @style_general_tdm_block_inline_image_simplified_caption */
                .tdm_block_inline_image_simplified .tdm-ii-caption {
                    position: relative;
                    max-width: 100%;
                    font-family: Verdana, BlinkMacSystemFont, -apple-system, \"Segoe UI\", Roboto, Oxygen, Ubuntu, Cantarell, \"Open Sans\", \"Helvetica Neue\", sans-serif;
                    padding-top: 6px;
                    font-size: 12px;
                    font-style: italic;
                    font-weight: normal;
                    line-height: 1.4;
                    color: #444;
                    z-index: 2;
                }
                
                $aib_component_style_css_output
				
                /* @" . $style_atts_prefix . "display$style_atts_uid */
				.$style_selector {
				    display: @" . $style_atts_prefix . "display$style_atts_uid;
				}
                /* @" . $style_atts_prefix . "image_position_background$style_atts_uid */
				.$style_selector img {
				    position: absolute;
				    top: 0;
				    left: 0;
				    width: 100%;
				    height: 100%;
				    object-fit: cover;
				}
                /* @" . $style_atts_prefix . "width$style_atts_uid */
				.$style_selector .tdm-ii-wrap {
				    width: @" . $style_atts_prefix . "width$style_atts_uid;
				}
				.$style_selector .tdm-ii-caption {
				    width: @" . $style_atts_prefix . "caption_width$style_atts_uid !important;
				}
                /* @" . $style_atts_prefix . "image_container_height$style_atts_uid */
				.$style_selector .tdm-ii-wrap {
				    padding-bottom: @" . $style_atts_prefix . "image_container_height$style_atts_uid;
				}
                /* @" . $style_atts_prefix . "image_height$style_atts_uid */
				.$style_selector img {
				    height: @" . $style_atts_prefix . "height$style_atts_uid;
				}
                /* @" . $style_atts_prefix . "content_align_horiz$style_atts_uid */
				.$style_selector {
				    align-items: @" . $style_atts_prefix . "content_align_horiz$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "overlay_color$style_atts_uid */
				.$style_selector .tdm-ii-wrap:after {
				    content: '';
					background: @" . $style_atts_prefix . "overlay_color$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "overlay_color_gradient$style_atts_uid */
				.$style_selector .tdm-ii-wrap:after {
					@" . $style_atts_prefix . "overlay_color_gradient$style_atts_uid
				}
                /* @" . $style_atts_prefix . "border_size$style_atts_uid */
				.$style_selector img {
				    border-width: @" . $style_atts_prefix . "border_size$style_atts_uid;
				}
                /* @" . $style_atts_prefix . "border_style$style_atts_uid */
				.$style_selector img {
				    border-style: @" . $style_atts_prefix . "border_style$style_atts_uid;
				}
                /* @" . $style_atts_prefix . "border_color$style_atts_uid */
				.$style_selector img {
				    border-color: @" . $style_atts_prefix . "border_color$style_atts_uid;
				}
                /* @" . $style_atts_prefix . "border_radius$style_atts_uid */
				.$style_selector img,
				.$style_selector .tdm-ii-wrap:before,
				.$style_selector .tdm-ii-wrap:after {
				    border-radius: @" . $style_atts_prefix . "border_radius$style_atts_uid;
				}
                /* @" . $style_atts_prefix . "shadow$style_atts_uid */
				.$style_selector img {
				    box-shadow: @" . $style_atts_prefix . "shadow$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "mix_type$style_atts_uid */
                .$style_selector .tdm-ii-wrap:before {
                    content: '';
                    opacity: 1;
                    mix-blend-mode: @" . $style_atts_prefix . "mix_type$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "color$style_atts_uid */
                .$style_selector .tdm-ii-wrap:before {
                    background: @" . $style_atts_prefix . "color$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "mix_gradient$style_atts_uid */
                .$style_selector .tdm-ii-wrap:before {
                    @" . $style_atts_prefix . "mix_gradient$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "effect_on$style_atts_uid */
                .$style_selector img {
                    filter: @" . $style_atts_prefix . "fe_brightness$style_atts_uid @" . $style_atts_prefix . "fe_contrast$style_atts_uid @" . $style_atts_prefix . "fe_saturate$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "mix_type_h$style_atts_uid */
                @" . $style_atts_prefix . "media (min-width: 1141px) {
                    .$style_selector .tdm-ii-wrap:after {
                        content: '';
                        opacity: 0;
                        mix-blend-mode: @" . $style_atts_prefix . "mix_type_h$style_atts_uid;
                    }
                    .$style_selector .tdm-ii-wrap:hover:before {
                        opacity: 0;
                    }
                    .$style_selector .tdm-ii-wrap:hover:after {
                        opacity: 1;
                    }
                }
                /* @" . $style_atts_prefix . "color_h$style_atts_uid */
                .$style_selector .tdm-ii-wrap:after {
                    background: @" . $style_atts_prefix . "color_h$style_atts_uid;
                    opacity: 0;
                }
                /* @" . $style_atts_prefix . "mix_gradient_h$style_atts_uid */
                .$style_selector .tdm-ii-wrap:after {
                    @" . $style_atts_prefix . "mix_gradient_h$style_atts_uid;
                    opacity: 0;
                }
                /* @" . $style_atts_prefix . "effect_on_h$style_atts_uid */
                @" . $style_atts_prefix . "media (min-width: 1141px) {
                    .$style_selector .tdm-ii-wrap:hover img {
                        filter: @" . $style_atts_prefix . "fe_brightness_h$style_atts_uid @" . $style_atts_prefix . "fe_contrast_h$style_atts_uid @" . $style_atts_prefix . "fe_saturate_h$style_atts_uid;
                    }
                }

                /* @" . $style_atts_prefix . "video_icon_size$style_atts_uid */
				.$style_selector .td-video-play-ico {
					width: @" . $style_atts_prefix . "video_icon_size$style_atts_uid;
					height: @" . $style_atts_prefix . "video_icon_size$style_atts_uid;
					font-size: @" . $style_atts_prefix . "video_icon_size$style_atts_uid;
				}
                /* @" . $style_atts_prefix . "video_rec_color$style_atts_uid */
				#td-video-modal.$unique_block_modal_class .td-vm-rec-title {
				    color: @" . $style_atts_prefix . "video_rec_color$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "video_bg_color$style_atts_uid */
				#td-video-modal.$unique_block_modal_class .td-vm-content-wrap {
				    background-color: @" . $style_atts_prefix . "video_bg_color$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "video_bg_gradient$style_atts_uid */
				#td-video-modal.$unique_block_modal_class .td-vm-content-wrap {
				    @" . $style_atts_prefix . "video_bg_gradient$style_atts_uid
				}
				/* @" . $style_atts_prefix . "video_overlay_color$style_atts_uid */
				#td-video-modal.$unique_block_modal_class .td-vm-overlay {
				    background-color: @" . $style_atts_prefix . "video_overlay_color$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "video_overlay_gradient$style_atts_uid */
				#td-video-modal.$unique_block_modal_class .td-vm-overlay {
				    background-color: @" . $style_atts_prefix . "video_overlay_gradient$style_atts_uid;
				}

                /* @" . $style_atts_prefix . "caption_pos_over_image$style_atts_uid */
                .$style_selector .tdm-ii-caption {
                    position: absolute;
                    left: 0;
                    bottom: 0;
                }
                /* @" . $style_atts_prefix . "caption_float_horiz$style_atts_uid */
				.$style_selector .tdm-ii-caption {
				    @" . $style_atts_prefix . "caption_float_horiz$style_atts_uid
				}
                /* @" . $style_atts_prefix . "caption_text_align$style_atts_uid */
                .$style_selector .tdm-ii-caption {
                    text-align: @" . $style_atts_prefix . "caption_text_align$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "caption_padding$style_atts_uid */
                .$style_selector .tdm-ii-caption {
                    padding: @" . $style_atts_prefix . "caption_padding$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "caption_text_color$style_atts_uid */
                .$style_selector .tdm-ii-caption {
                    color: @" . $style_atts_prefix . "caption_text_color$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "caption_background_color$style_atts_uid */
                .$style_selector .tdm-ii-caption {
                    background-color: @" . $style_atts_prefix . "caption_background_color$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "caption_background_gradient$style_atts_uid */
                .$style_selector .tdm-ii-caption {
                    @" . $style_atts_prefix . "caption_background_gradient$style_atts_uid
                }
				/* @" . $style_atts_prefix . "f_caption$style_atts_uid */
				.$style_selector .tdm-ii-caption {
					@" . $style_atts_prefix . "f_caption$style_atts_uid
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
        $res_ctx->load_settings_raw( 'style_general_tdm_block_inline_image_simplified', 1 );
        if ( !empty(self::$aib_component_style) ) {
            $res_ctx->load_settings_raw('style_general_component_style_' . self::$aib_component_style->id, self::$aib_component_style->css_output);
        }

        /* --
        -- IMAGE.
        -- */
        /* -- Layout. -- */
        // Image position.
        $image_position = $res_ctx->get_shortcode_att('image_position');
        if ( $image_position == 'background' ) {
            $res_ctx->load_settings_raw($style_atts_prefix . 'image_position_background' . $style_atts_uid, 1);
        }

        // Display.
        $display = $res_ctx->get_shortcode_att('display');
        $display = $image_position == 'background' ? 'flex' : $display;
        $res_ctx->load_settings_raw($style_atts_prefix . 'display' . $style_atts_uid, $display);

        // Width.
        $width = $res_ctx->get_shortcode_att( 'width' );
        $width .= is_numeric($width) ? 'px' : '';
        $width = $image_position == 'background' ? '100%' : $width;
        $res_ctx->load_settings_raw( $style_atts_prefix . 'width' . $style_atts_uid, $width );

        // Height.
        $height = $res_ctx->get_shortcode_att( 'height' );
        $height .= is_numeric($height) ? 'px' : '';
        if ( $image_position == 'background' ) {
            if ( empty($height) ) {
                $height = ( ( (int) self::$image_info['height'] * 100 ) / (int) self::$image_info['width'] ) . '%';
            }
            $res_ctx->load_settings_raw( $style_atts_prefix . 'image_container_height' . $style_atts_uid, $height );
        } else {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'image_height' . $style_atts_uid, $height );
        }

        // Horizontal align.
        if ( $image_position != 'background' ) {
            $content_align_horiz = $res_ctx->get_shortcode_att( 'content_align_horiz' );
            $content_align_horiz_value = '';
            switch ( $content_align_horiz ) {
                case 'content-horiz-left':
                    $content_align_horiz_value = 'flex-start';
                    break;
                case 'content-horiz-center':
                    $content_align_horiz_value = 'center';
                    break;
                case 'content-horiz-right':
                    $content_align_horiz_value = 'flex-end';
                    break;
            }

            $res_ctx->load_settings_raw( $style_atts_prefix . 'content_align_horiz' . $style_atts_uid, $content_align_horiz_value );
        }

        // Border size.
        $border_size = $res_ctx->get_shortcode_att( 'border_size' );
        $border_size .= is_numeric($border_size) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'border_size' . $style_atts_uid, $border_size );

        // Border style.
        $border_style = $res_ctx->get_shortcode_att( 'border_style' );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'border_style' . $style_atts_uid, $border_style );

        // Border radius.
        $border_radius = $res_ctx->get_shortcode_att( 'border_radius' );
        $border_radius .= is_numeric($border_radius) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'border_radius' . $style_atts_uid, $border_radius );

        /* -- Colors. -- */
        $mix_type = $res_ctx->get_shortcode_att('mix_type');
        if ( $mix_type != '' ) {
            $res_ctx->load_settings_raw($style_atts_prefix . 'mix_type' . $style_atts_uid, $res_ctx->get_shortcode_att('mix_type'));
            $res_ctx->load_color_settings( 'mix_color', $style_atts_prefix . 'color' . $style_atts_uid, $style_atts_prefix . 'mix_gradient' . $style_atts_uid, '', '' );
            $res_ctx->load_settings_raw($style_atts_prefix . 'fe_brightness' . $style_atts_uid, 'brightness(1)');
            $res_ctx->load_settings_raw($style_atts_prefix . 'fe_contrast' . $style_atts_uid, 'contrast(1)');
            $res_ctx->load_settings_raw($style_atts_prefix . 'fe_saturate' . $style_atts_uid, 'saturate(1)');
            $fe_brightness = $res_ctx->get_shortcode_att('fe_brightness');
            if ( $fe_brightness != '1' ) {
                $res_ctx->load_settings_raw($style_atts_prefix . 'fe_brightness' . $style_atts_uid, 'brightness(' . $fe_brightness . ')');
                $res_ctx->load_settings_raw($style_atts_prefix . 'effect_on' . $style_atts_uid, 1);
            }
            $fe_contrast = $res_ctx->get_shortcode_att('fe_contrast');
            if ( $fe_contrast != '1' ) {
                $res_ctx->load_settings_raw($style_atts_prefix . 'fe_contrast' . $style_atts_uid, 'contrast(' . $fe_contrast . ')');
                $res_ctx->load_settings_raw($style_atts_prefix . 'effect_on' . $style_atts_uid, 1);
            }
            $fe_saturate = $res_ctx->get_shortcode_att('fe_saturate');
            if ( $fe_saturate != '1' ) {
                $res_ctx->load_settings_raw($style_atts_prefix . 'fe_saturate' . $style_atts_uid, 'saturate(' . $fe_saturate . ')');
                $res_ctx->load_settings_raw($style_atts_prefix . 'effect_on' . $style_atts_uid, 1);
            }
        }
        $mix_type_h = $res_ctx->get_shortcode_att('mix_type_h');
        if ( $mix_type_h != '' ) {
            $res_ctx->load_settings_raw($style_atts_prefix . 'mix_type_h' . $style_atts_uid, $res_ctx->get_shortcode_att('mix_type_h'));
            $res_ctx->load_color_settings( 'mix_color_h', $style_atts_prefix . 'color_h' . $style_atts_uid, $style_atts_prefix . 'mix_gradient_h' . $style_atts_uid, '', '' );
            $res_ctx->load_settings_raw($style_atts_prefix . 'fe_brightness_h' . $style_atts_uid, 'brightness(1)');
            $res_ctx->load_settings_raw($style_atts_prefix . 'fe_contrast_h' . $style_atts_uid, 'contrast(1)');
            $res_ctx->load_settings_raw($style_atts_prefix . 'fe_saturate_h' . $style_atts_uid, 'saturate(1)');
            $fe_brightness_h = $res_ctx->get_shortcode_att('fe_brightness_h');
            $fe_contrast_h = $res_ctx->get_shortcode_att('fe_contrast_h');
            $fe_saturate_h = $res_ctx->get_shortcode_att('fe_saturate_h');
            if ( $fe_brightness_h != '1' ) {
                $res_ctx->load_settings_raw($style_atts_prefix . 'fe_brightness_h' . $style_atts_uid, 'brightness(' . $fe_brightness_h . ')');
                $res_ctx->load_settings_raw($style_atts_prefix . 'effect_on_h' . $style_atts_uid, 1);
            }
            if ( $fe_contrast_h != '1' ) {
                $res_ctx->load_settings_raw($style_atts_prefix . 'fe_contrast_h' . $style_atts_uid, 'contrast(' . $fe_contrast_h . ')');
                $res_ctx->load_settings_raw($style_atts_prefix . 'effect_on_h' . $style_atts_uid, 1);
            }
            if ( $fe_saturate_h != '1' ) {
                $res_ctx->load_settings_raw($style_atts_prefix . 'fe_saturate_h' . $style_atts_uid, 'saturate(' . $fe_saturate_h . ')');
                $res_ctx->load_settings_raw($style_atts_prefix . 'effect_on_h' . $style_atts_uid, 1);
            }
        }
        if ( $mix_type == '' && $mix_type_h == '' ) {
            $res_ctx->load_color_settings( 'overlay_color', $style_atts_prefix . 'overlay_color' . $style_atts_uid, $style_atts_prefix . 'overlay_color_gradient' . $style_atts_uid, '', '');
        }
        $res_ctx->load_shadow_settings( 0, 0, 0, 0, 'rgba(0, 0, 0, 0.08)', 'shadow' );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'border_color' . $style_atts_uid, $res_ctx->get_shortcode_att('border_color') );

        /* --
        -- Video pop-up.
        -- */
        if ( self::$video_popup_enabled ) {
            $res_ctx->load_settings_raw( 'style_general_tdm_block_inline_image_simplified_video_popup', 1 );

            /* -- Layout. -- */
            // Video play icon size.
            $video_icon_size = $res_ctx->get_shortcode_att('video_icon_size');
            if ( $video_icon_size != '' && is_numeric( $video_icon_size ) ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'video_icon_size' . $style_atts_uid, $video_icon_size . 'px' );
            }

            /*-- Colors. -- */
            $res_ctx->load_settings_raw( $style_atts_prefix . 'video_rec_color' . $style_atts_uid, $res_ctx->get_shortcode_att('video_rec_color') );
            $res_ctx->load_color_settings( 'video_bg', $style_atts_prefix . 'video_bg_color' . $style_atts_uid, $style_atts_prefix . 'video_bg_gradient' . $style_atts_uid, '', '' );
            $res_ctx->load_color_settings( 'video_overlay', $style_atts_prefix . 'video_overlay_color' . $style_atts_uid, $style_atts_prefix . 'video_overlay_gradient' . $style_atts_uid, '', '' );
        }

        /* --
        -- Caption.
        -- */
        if ( self::$caption_enabled ) {
            $res_ctx->load_settings_raw( 'style_general_tdm_block_inline_image_simplified_caption', 1 );

            /* -- Layout. -- */
            // Position.
            $caption_position = $res_ctx->get_shortcode_att( 'caption_position' );
            if ( $caption_position == 'over-image' ) {
                $res_ctx->load_settings_raw( $style_atts_prefix . 'caption_pos_over_image' . $style_atts_uid, 1 );

                if ( $image_position != 'background' ) {
                    $caption_float_horiz_value = '';
                    switch ( $content_align_horiz ) {
                        case 'content-horiz-left':
                            $caption_float_horiz_value = 'left:0;right:auto;';
                            break;
                        case 'content-horiz-center':
                            $caption_float_horiz_value = 'left:auto;right:auto;';
                            break;
                        case 'content-horiz-right':
                            $caption_float_horiz_value = 'left:auto;right:0;';
                            break;
                    }
                    $res_ctx->load_settings_raw( $style_atts_prefix . 'caption_float_horiz' . $style_atts_uid, $caption_float_horiz_value );
                }
            }

            // Horizontal align.
            $caption_align_horiz = $res_ctx->get_shortcode_att( 'caption_align_horiz' );
            $caption_align_horiz_value = '';
            switch ( $caption_align_horiz ) {
                case 'content-horiz-left':
                    $caption_align_horiz_value = 'left';
                    break;
                case 'content-horiz-center':
                    $caption_align_horiz_value = 'center';
                    break;
                case 'content-horiz-right':
                    $caption_align_horiz_value = 'right';
                    break;
            }

            $res_ctx->load_settings_raw( $style_atts_prefix . 'caption_text_align' . $style_atts_uid, $caption_align_horiz_value );

            // Caption padding.
            $caption_padding = $res_ctx->get_shortcode_att( 'caption_padding' );
            $caption_padding .= is_numeric($caption_padding) ? 'px' : '';
            $res_ctx->load_settings_raw( $style_atts_prefix . 'caption_padding' . $style_atts_uid, $caption_padding );

            /* -- Colors. -- */
            $res_ctx->load_settings_raw( $style_atts_prefix . 'caption_text_color' . $style_atts_uid, $res_ctx->get_shortcode_att( 'caption_text_color' ) );
            $res_ctx->load_color_settings( 'caption_background_color', $style_atts_prefix . 'caption_background_color' . $style_atts_uid, $style_atts_prefix . 'caption_background_gradient' . $style_atts_uid, '', '');

            /*-- Fonts. -- */
            $res_ctx->load_font_settings( 'f_caption' );
        }
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

        // Flags.
        $in_composer = td_util::tdc_is_live_editor_ajax() || td_util::tdc_is_live_editor_iframe();
        $is_mobile_theme = td_util::is_mobile_theme();
        $is_amp = td_util::is_amp();

        // Set AI Builder settings.
        $this->set_aib_settings('image');

        /*
         * Get the image.
         */
        $image = $this->get_att('image');
        $external_image = $this->get_att('image_external');

        // Check whether both image sources are empty. If so:
        // * in composer: return a block error;
        // * on front-end: return an empty string.
        if ( empty($image) && empty($external_image) ) {
            if ( !$in_composer ) {
                return '';
            }
            return $this->get_block_error('You have not selected any image.');
        }

        // First try retrieving the external image URL.
        if ( !empty($external_image) ) {
            // Check whether a custom field was specified, and retrieve the image URL from there.
            if ( strpos($external_image, '{cf_') !== false ) {
                $custom_field_value = td_util::get_custom_field_value_from_string($external_image);

                if ( !empty($custom_field_value) ) {
                    if ( is_array($custom_field_value) ) {
                        $image_id = $custom_field_value['ID'];

                        self::$image_info['url'] = $custom_field_value['url'];
                        self::$image_info['title'] = $custom_field_value['title'];
                        self::$image_info['alt'] = $custom_field_value['alt'];

                        $image_attachment_image_src = wp_get_attachment_image_src($image_id, 'full');
                        if ( is_array($image_attachment_image_src) ) {
                            self::$image_info['width'] = $image_attachment_image_src[1];
                            self::$image_info['height'] = $image_attachment_image_src[2];
                        }
                    } else if ( is_numeric($custom_field_value) ) {
                        self::$image_info = tdc_util::get_image(array('image' => $custom_field_value));
                    } else if ( is_string($custom_field_value) ) {
                        self::$image_info = $this->get_image_info_from_url($custom_field_value);
                    }
                }
            } else {
                // Otherwise assume a direct URL to the image was provided.
                // If the image is self-hosted, retrieve its info, otherwise just save the URL.
                if ( $_SERVER['HTTP_HOST'] === parse_url($external_image, PHP_URL_HOST) ) {
                    self::$image_info = $this->get_image_info_from_url($external_image);
                } else {
                    $external_image_dimensions = @getimagesize($external_image);
                    if ( empty($external_image_dimensions) ) {
                        if ( !$in_composer ) {
                            return '';
                        }
                        return $this->get_block_error('The external image is not accessible.');
                    }
                    self::$image_info['url'] = $external_image;
                    self::$image_info['width'] = $external_image_dimensions[0];
                    self::$image_info['height'] = $external_image_dimensions[1];
                }
            }
        }

        // If either no external image was provided, or its info could not be retrieved,
        // fallback to the original shortcode image attribute.
        if ( empty(self::$image_info['url']) && !empty($image) ) {
            if ( is_numeric($image) ) {
                self::$image_info = tdc_util::get_image($atts);
            } else if ( strpos($image, 'td-composer/assets/images/placeholders/thumb_07.jpg') !== false ) {
                self::$image_info['url'] = $image;
                self::$image_info['width'] = 400;
                self::$image_info['height'] = 283;
            }
        }

        // When no image info could be retrieved:
        // - on front-end return an empty string;
        // - in composer display an error message image.
        if ( empty(self::$image_info['url']) ) {
            if ( !$in_composer ) {
                return '';
            }
            return $this->get_block_error('The selected image does not exist.');
        }

        /*
         * Set image classes & attributes.
         */
        self::$image_info['classes'] = array();
        $image_html_atts = array();

        // Check whether lazyload animation is enabled.
        if ( empty(td_util::get_option('tds_animation_stack')) && !$in_composer && !$is_mobile_theme && !$is_amp ) {
            $this->additional_classes[] = 'td-animation-stack';
            self::$image_info['classes'][] = 'td-lazy-img';
            $image_html_atts[] = 'data-type="image_tag"';
            $image_html_atts[] = 'data-img-url="' . self::$image_info['url'] . '"';

            if ( isset(self::$image_info['size']) ) {
                $size = strpos( self::$image_info['size'], 'td_' ) === 0 ? self::$image_info['size'] : 'td_1068x0';
                $thumbs = td_api_thumb::get_all();

                foreach ( $thumbs as $thumb_id => $thumb_data ) {
                    if ( $thumb_id === $size ) {
                        if ( isset( $thumb_data['b64_encoded'] ) ) {
                            $image_src = td_api_thumb::get_key( $size, 'b64_encoded' );
                        }
                    }
                }
            }
        }

        // Title & alt.
        if ( !empty(self::$image_info['title']) ) {
            $image_html_atts[] = 'title="' . self::$image_info['title'] .  '"';
        }
        if ( !empty(self::$image_info['alt']) ) {
            $image_html_atts[] = 'alt="' . self::$image_info['alt'] .  '"';
        }

        // Width & height.
        $image_html_atts[] = 'width="' . self::$image_info['width'] .  '"';
        $image_html_atts[] = 'height="' . self::$image_info['height'] .  '"';

        // Video pop-up.
        $this->set_video_popup();

        // Modal image.
        $this->set_modal_image();

        // Custom URL.
        $this->set_custom_url();

        /*
         * Caption.
         */
        // Caption text.
        $caption_text = rawurldecode(base64_decode(strip_tags($this->get_att('caption_text'))));
        if ( !empty($caption_text) ) {
            self::$caption_enabled = true;
        }

        /*
         * Build the shortcode HTML.
         */
        $buffy = '<' . self::$wrapper_tag_open . ' class="' . $this->get_block_classes($this->additional_classes) . '" ' . $this->get_block_html_atts($this->additional_html_atts) . '>';
            // Get the block CSS.
            $buffy .= $this->get_block_css();

            $buffy .= '<div class="tdm-ii-wrap ' . implode(' ', self::$image_wrapper_classes) . '">';
                // Set the image tag.
                $buffy .= '<img class="' . implode(' ', self::$image_info['classes']) . '" src="' . self::$image_info['url']. '"' . ( !empty($image_html_atts) ? ( ' ' . implode(' ', $image_html_atts) ) : '' )  . ' />';

                // If video pop-up is enabled, display a video play icon.
                if ( self::$video_popup_enabled ) {
                    $buffy .= '<span class="td-video-play-ico"><i class="td-icon-video-thumb-play"></i></span>';
                }
            $buffy .= '</div>';

            if ( self::$caption_enabled ) {
                $buffy .= '<div class="tdm-ii-caption" style="width:' . self::$image_info['width'] . 'px">' . $caption_text . '</div>';
            }
        $buffy .= '</' . self::$wrapper_tag_close . '>';

        return $buffy;
    }

    /**
     * Retrieves the info of an image based on the URL.
     *
     * @param string $image_url
     *
     * @return array
     */
    private function get_image_info_from_url( $image_url ) {
        return tdc_util::get_image(array('image' => attachment_url_to_postid($image_url)));
    }

    /**
     * Checks for and processes the video pop-up functionality for this shortcode.
     */
    private function set_video_popup() {
        if ( $this->get_att('video_enable') === '' ) {
            return;
        }

        $video_url = $this->get_att('video_url');
        if ( empty($video_url) ) {
            return;
        }
        $video_source = td_video_support::detect_video_service($video_url);
        if ( $video_source === false ) {
            return;
        }

        self::$video_popup_enabled = true;

        // Add specific classes and attributes for the shortcode wrapper.
        $this->additional_classes[] = 'td-image-video-modal';
        $this->additional_html_atts[] = 'data-video-source="' . $video_source . '"';
        $this->additional_html_atts[] = 'data-video-autoplay="' . $this->get_att( 'video_autoplay' ) . '"';
        $this->additional_html_atts[] = 'data-video-url="' . esc_url( $video_url ) . '"';

        // Check if an AD has been provided.
        $video_rec = rawurldecode( base64_decode( strip_tags( $this->get_att( 'video_rec' ) ) ) );

        if ( $video_rec != '' ) {
            $video_popup_ad = array(
                'code' => $video_rec,
                'title' => $this->get_att( 'video_rec_title' )
            );

            $this->additional_html_atts[] = 'data-video-rec="' . base64_encode( json_encode($video_popup_ad) ) . '"';
        }

        // Load the required JS file (Newspaper only).
        if ( TD_THEME_NAME == "Newspaper" ) {
            td_resources_load::render_script( TDC_SCRIPTS_URL . '/tdAjaxVideoModal.js' . TDC_SCRIPTS_VER, 'tdAjaxVideoModal-js', '', 'footer');
        }
    }

    /**
     * Checks for and processes the modal image functionality for this shortcode.
     */
    private function set_modal_image() {
        // Bail if the video pop-up feature has already been enabled.
        // The logic here is that the modal image feature has lower priority.
        if ( self::$video_popup_enabled ) {
            return;
        }

        if ( $this->get_att('modal_image') === '' ) {
            return;
        }
        if ( empty(self::$image_info['url']) ) {
            return;
        }

        /*
         * Set the shortcode wrapper and image tag specific settings.
         */
        self::$modal_image_enabled = true;

        // Set the opening and closing tag of the shortcode wrapper.
        self::$wrapper_tag_open = self::$wrapper_tag_close = 'a';

        // Set the 'href' attribute on the shortcode wrapper.
        $this->additional_html_atts[] = 'href="' . self::$image_info['url'] . '"';

        // Set the modal image feature specific class on the image tag.
        self::$image_wrapper_classes[] = 'td-modal-image';
    }

    /**
     * Checks for and processes the custom URL functionality for this shortcode.
     */
    private function set_custom_url() {
        // Bail if either the video pop-up or modal image features
        // have been already enabled.
        // The logic here is that the custom URL feature has lower priority.
        if ( self::$video_popup_enabled || self::$modal_image_enabled ) {
            return;
        }

        /*
         * Get the custom URL.
         */
        $custom_url = $this->get_att('url');
        if ( empty($custom_url) ) {
            return;
        }

        // Process the URL for variables or custom fields.
        $custom_url = td_util::get_cloud_tpl_var_value_from_string(td_util::get_custom_field_value_from_string($custom_url));

        /*
         * Set the shortcode wrapper and image tag specific settings.
         */
        // Set the opening and closing tag of the shortcode wrapper.
        self::$wrapper_tag_open = self::$wrapper_tag_close = 'a';

        // Set the 'href' attribute on the shortcode wrapper.
        $this->additional_html_atts[] = 'href="' . $custom_url . '"';

        // Shortcode wrapper target & rel attributes.
        if ( $this->get_att('url_target') != '' ) {
            $this->additional_html_atts[] = 'target="blank"';
        }
        if ( $this->get_att('url_rel') != '' ) {
            $this->additional_html_atts[] = 'rel="' . $this->get_att('url_rel') . '"';
        }
    }

    /**
     * Sets AI Builder settings.
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
     * Retrieves the block's HTML atts.
     *
     * @param array $additional_html_atts_array
     * @return string
     */
    protected function get_block_html_atts( $additional_html_atts_array = array() ) {
        $html_atts = ' data-td-block-uid="' . $this->block_uid . '" ';

        if ( !empty($additional_html_atts_array) ) {
            $html_atts .= implode(' ', $additional_html_atts_array);
        }

        return $html_atts;
    }

    /**
     * Builds block error for composer.
     *
     * @param string $message
     * @return string
     */
    protected function get_block_error( $message ) {
        $buffy = '<div class="' . $this->get_block_classes($this->additional_classes) . '" ' . $this->get_block_html_atts() . '>';
            $buffy .= $this->get_block_css();
            $buffy .= td_util::get_block_error('Single image', $message, 'span');
        $buffy .= '</div>';

        return $buffy;
    }

}