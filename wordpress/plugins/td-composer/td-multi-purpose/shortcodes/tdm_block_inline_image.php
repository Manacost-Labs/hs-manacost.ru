<?php
class tdm_block_inline_image extends td_block {

	protected $shortcode_atts = array(); //the atts used for rendering the current block
    private $unique_block_class;

    static $style_selector = '';
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
	        self::$style_selector = '';
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

        $unique_block_modal_class = $this->block_uid . '_m';

        $raw_css =
            "<style>

                /* @style_general_inline_image */
                .tdm_block.tdm_block_inline_image {
                  position: relative;
                  margin-bottom: 0;
                  line-height: 0;
                  overflow: hidden;
                }
                .tdm_block.tdm_block_inline_image .tdm-inline-image-wrap {
                  position: relative;
                  display: inline-block;
                }
                .tdm_block.tdm_block_inline_image .td-image-video-modal {
                  cursor: pointer;
                }
                .tdm_block.tdm_block_inline_image .tdm-caption {
                  width: 100%;
                  font-family: Verdana, BlinkMacSystemFont, -apple-system, \"Segoe UI\", Roboto, Oxygen, Ubuntu, Cantarell, \"Open Sans\", \"Helvetica Neue\", sans-serif;
                  padding-top: 6px;
                  padding-bottom: 6px;
                  font-size: 12px;
                  font-style: italic;
                  font-weight: normal;
                  line-height: 17px;
                  color: #444;
                }
                .tdm_block.tdm_block_inline_image.tdm-caption-over-image .tdm-caption {
                  position: absolute;
                  left: 0;
                  bottom: 0;
                  margin-top: 0;
                  padding-left: 10px;
                  padding-right: 10px;
                  width: 100%;
                  background: rgba(0, 0, 0, 0.7);
                  color: #fff;
                }
                
                /* @" . $style_atts_prefix . "caption_text_color$style_atts_uid */
                body .td-theme-wrap .$style_selector .tdm-caption {
                    color: @" . $style_atts_prefix . "caption_text_color$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "caption_background_color$style_atts_uid */
                body .td-theme-wrap .$style_selector .tdm-caption {
                    padding-left: 10px;
                    padding-right: 10px;
                    background-color: @" . $style_atts_prefix . "caption_background_color$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "caption_background_gradient$style_atts_uid */
                body .td-theme-wrap .$style_selector .tdm-caption {
                    padding-left: 10px;
                    padding-right: 10px;
                    @" . $style_atts_prefix . "caption_background_gradient$style_atts_uid
                }

                /* @" . $style_atts_prefix . "img_width$style_atts_uid */
                .$style_selector {
                    width: @" . $style_atts_prefix . "img_width$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "img_height$style_atts_uid */
                .$style_selector img {
                    height: @" . $style_atts_prefix . "img_height$style_atts_uid;
                }
                
				/* @" . $style_atts_prefix . "overlay_color_gradient$style_atts_uid */
				.$style_selector .tdm-inline-image-wrap:after {
				    content: '';
				    position: absolute;
				    top: 0;
				    left: 0;
				    width: 100%;
				    height: 100%;
					@" . $style_atts_prefix . "overlay_color_gradient$style_atts_uid
				}
				/* @" . $style_atts_prefix . "overlay_color$style_atts_uid */
				.$style_selector .tdm-inline-image-wrap:after {
				    content: '';
				    position: absolute;
				    top: 0;
				    left: 0;
				    width: 100%;
				    height: 100%;
					background: @" . $style_atts_prefix . "overlay_color$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "shadow$style_atts_uid */
				.$style_selector .tdm-image {
				    box-shadow: @" . $style_atts_prefix . "shadow$style_atts_uid;
				}
				 div.$style_selector.tdm_block_inline_image {
                  overflow: visible;
                }
				/* @" . $style_atts_prefix . "f_caption$style_atts_uid */
				body .$style_selector .tdm-caption {
					@" . $style_atts_prefix . "f_caption$style_atts_uid
				}
				
				/* @" . $style_atts_prefix . "mix_type$style_atts_uid */
                .$style_selector .tdm-inline-image-wrap:before {
                    content: '';
                    width: 100%;
                    height: 100%;
                    position: absolute;
                    opacity: 1;
                    transition: opacity 1s ease;
                    -webkit-transition: opacity 1s ease;
                    mix-blend-mode: @" . $style_atts_prefix . "mix_type$style_atts_uid;
                    z-index: 1;
                    top: 0;
                    left: 0;
                }
                /* @" . $style_atts_prefix . "color$style_atts_uid */
                .$style_selector .tdm-inline-image-wrap:before {
                    background: @" . $style_atts_prefix . "color$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "mix_gradient$style_atts_uid */
                .$style_selector .tdm-inline-image-wrap:before {
                    @" . $style_atts_prefix . "mix_gradient$style_atts_uid;
                }
                
                
                /* @" . $style_atts_prefix . "mix_type_h$style_atts_uid */
                @" . $style_atts_prefix . "media (min-width: 1141px) {
                    .$style_selector .tdm-inline-image-wrap:after {
                        content: '';
                        width: 100%;
                        height: 100%;
                        position: absolute;
                        opacity: 0;
                        transition: opacity 1s ease;
                        -webkit-transition: opacity 1s ease;
                        mix-blend-mode: @" . $style_atts_prefix . "mix_type_h$style_atts_uid;
                        z-index: 1;
                        top: 0;
                        left: 0;
                    }
                    .$style_selector .tdm-inline-image-wrap:hover:after {
                        opacity: 1;
                    }
                }
                
                /* @" . $style_atts_prefix . "color_h$style_atts_uid */
                .$style_selector .tdm-inline-image-wrap:after {
                    background: @" . $style_atts_prefix . "color_h$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "mix_gradient_h$style_atts_uid */
                .$style_selector .tdm-inline-image-wrap:after {
                    @" . $style_atts_prefix . "mix_gradient_h$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "mix_type_off$style_atts_uid */
                .$style_selector .tdm-inline-image-wrap:hover:before {
                    opacity: 0;
                }
                    
                /* @" . $style_atts_prefix . "effect_on$style_atts_uid */
                .$style_selector .tdm-image {
                    filter: @" . $style_atts_prefix . "fe_brightness$style_atts_uid @" . $style_atts_prefix . "fe_contrast$style_atts_uid @" . $style_atts_prefix . "fe_saturate$style_atts_uid;
                    transition: all 1s ease;
                    -webkit-transition: all 1s ease;
                }
                /* @" . $style_atts_prefix . "effect_on_h$style_atts_uid */
                @" . $style_atts_prefix . "media (min-width: 1141px) {
                    .$style_selector .tdm-inline-image-wrap:hover .tdm-image {
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
				
				/* @" . $style_atts_prefix . "border_size$style_atts_uid */
				.$style_selector .tdm-image {
					border-width:  @" . $style_atts_prefix . "border_size$style_atts_uid;
					border-style: solid;
				}
				/* @" . $style_atts_prefix . "border_style$style_atts_uid */
				.$style_selector .tdm-image {
					border-style:  @" . $style_atts_prefix . "border_style$style_atts_uid;
				}
				/* @" . $style_atts_prefix . "border_color$style_atts_uid */
				.$style_selector .tdm-image {
					border-color:  @" . $style_atts_prefix . "border_color$style_atts_uid;
				}
				
				/* @" . $style_atts_prefix . "border_radius$style_atts_uid */
				.$style_selector .tdm-image {
					border-radius: @" . $style_atts_prefix . "border_radius$style_atts_uid;
				}

			</style>";

        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->shortcode_atts);

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

        $res_ctx->load_settings_raw( 'style_general_inline_image', 1 );

        /*-- IMAGE -- */
        // image width
        $img_width = $res_ctx->get_shortcode_att( 'img_width' );
        $img_width .= $img_width != '' && is_numeric( $img_width ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'img_width' . $style_atts_uid, $img_width );
        // image height
        $img_height = $res_ctx->get_shortcode_att( 'img_height' );
        $img_height .= $img_height != '' && is_numeric( $img_height ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'img_height' . $style_atts_uid, $img_height );


        /*-- VIDEO MODAL -- */
        // video icon size
        $video_icon_size = $res_ctx->get_shortcode_att('video_icon_size');
        if ( $video_icon_size != '' && is_numeric( $video_icon_size ) ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'video_icon_size' . $style_atts_uid, $video_icon_size . 'px' );
        }


        /*-- COLORS -- */
        // shadow
        $res_ctx->load_shadow_settings( 0, 0, 0, 0, 'rgba(0, 0, 0, 0.08)', 'shadow' );

        // border radius
        $border_radius = $res_ctx->get_shortcode_att( 'border_radius');
        $border_radius .= $border_radius != '' && is_numeric( $border_radius ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'border_radius' . $style_atts_uid, $border_radius );
        // border_size
        $border_size = $res_ctx->get_shortcode_att('border_size');
        $res_ctx->load_settings_raw( 'border_size', $border_size );
        if ( is_numeric( $border_size ) ) {
            $res_ctx->load_settings_raw( 'border_size', $border_size . 'px' );
        }
        // border style
        $res_ctx->load_settings_raw( $style_atts_prefix . 'border_style' . $style_atts_uid, $res_ctx->get_shortcode_att('border_style') );
        // border color
        $res_ctx->load_settings_raw( $style_atts_prefix . 'border_color' . $style_atts_uid, $res_ctx->get_shortcode_att('border_color') );


        // overlay color
        $res_ctx->load_color_settings( 'overlay_color', $style_atts_prefix . 'overlay_color' . $style_atts_uid, $style_atts_prefix . 'overlay_color_gradient' . $style_atts_uid, '', '');
        // caption text color
        $res_ctx->load_settings_raw( $style_atts_prefix . 'caption_text_color' . $style_atts_uid, $res_ctx->get_shortcode_att( 'caption_text_color' ) );
        // caption background color
        $res_ctx->load_color_settings( 'caption_background_color', $style_atts_prefix . 'caption_background_color' . $style_atts_uid, $style_atts_prefix . 'caption_background_gradient' . $style_atts_uid, '', '');

        // video modal
        $res_ctx->load_settings_raw( $style_atts_prefix . 'video_rec_color' . $style_atts_uid, $res_ctx->get_shortcode_att('video_rec_color') );
        $res_ctx->load_color_settings( 'video_bg', $style_atts_prefix . 'video_bg_color' . $style_atts_uid, $style_atts_prefix . 'video_bg_gradient' . $style_atts_uid, '', '' );
        $res_ctx->load_color_settings( 'video_overlay', $style_atts_prefix . 'video_overlay_color' . $style_atts_uid, $style_atts_prefix . 'video_overlay_gradient' . $style_atts_uid, '', '' );


        /*-- FONTS -- */
        $res_ctx->load_font_settings( 'f_caption' );


        /*-- EFFECTS -- */
        // mix blend
        $mix_type = $res_ctx->get_shortcode_att('mix_type');
        if ( $mix_type != '' ) {
            $res_ctx->load_settings_raw($style_atts_prefix . 'mix_type' . $style_atts_uid, $res_ctx->get_shortcode_att('mix_type'));
        }
        $res_ctx->load_color_settings( 'mix_color', $style_atts_prefix . 'color' . $style_atts_uid, $style_atts_prefix . 'mix_gradient' . $style_atts_uid, '', '' );

        $mix_type_h = $res_ctx->get_shortcode_att('mix_type_h');
        if ( $mix_type_h != '' ) {
            $res_ctx->load_settings_raw($style_atts_prefix . 'mix_type_h' . $style_atts_uid, $res_ctx->get_shortcode_att('mix_type_h'));
        } else {
            $res_ctx->load_settings_raw($style_atts_prefix . 'mix_type_off' . $style_atts_uid, 1);
        }

        $res_ctx->load_color_settings( 'mix_color_h', $style_atts_prefix . 'color_h' . $style_atts_uid, $style_atts_prefix . 'mix_gradient_h' . $style_atts_uid, '', '' );
        $res_ctx->load_settings_raw($style_atts_prefix . 'fe_brightness' . $style_atts_uid, 'brightness(1)');
        $res_ctx->load_settings_raw($style_atts_prefix . 'fe_contrast' . $style_atts_uid, 'contrast(1)');
        $res_ctx->load_settings_raw($style_atts_prefix . 'fe_saturate' . $style_atts_uid, 'saturate(1)');

        $fe_brightness = $res_ctx->get_shortcode_att('fe_brightness');
        if ($fe_brightness != '1') {
            $res_ctx->load_settings_raw($style_atts_prefix . 'fe_brightness' . $style_atts_uid, 'brightness(' . $fe_brightness . ')');
            $res_ctx->load_settings_raw($style_atts_prefix . 'effect_on' . $style_atts_uid, 1);
        }
        $fe_contrast = $res_ctx->get_shortcode_att('fe_contrast');
        if ($fe_contrast != '1') {
            $res_ctx->load_settings_raw($style_atts_prefix . 'fe_contrast' . $style_atts_uid, 'contrast(' . $fe_contrast . ')');
            $res_ctx->load_settings_raw($style_atts_prefix . 'effect_on' . $style_atts_uid, 1);
        }
        $fe_saturate = $res_ctx->get_shortcode_att('fe_saturate');
        if ($fe_saturate != '1') {
            $res_ctx->load_settings_raw($style_atts_prefix . 'fe_saturate' . $style_atts_uid, 'saturate(' . $fe_saturate . ')');
            $res_ctx->load_settings_raw($style_atts_prefix . 'effect_on' . $style_atts_uid, 1);
        }

        // effects hover
        $res_ctx->load_settings_raw($style_atts_prefix . 'fe_brightness_h' . $style_atts_uid, 'brightness(1)');
        $res_ctx->load_settings_raw($style_atts_prefix . 'fe_contrast_h' . $style_atts_uid, 'contrast(1)');
        $res_ctx->load_settings_raw($style_atts_prefix . 'fe_saturate_h' . $style_atts_uid, 'saturate(1)');

        $fe_brightness_h = $res_ctx->get_shortcode_att('fe_brightness_h');
        $fe_contrast_h = $res_ctx->get_shortcode_att('fe_contrast_h');
        $fe_saturate_h = $res_ctx->get_shortcode_att('fe_saturate_h');

        if ($fe_brightness_h != '1') {
            $res_ctx->load_settings_raw($style_atts_prefix . 'fe_brightness_h' . $style_atts_uid, 'brightness(' . $fe_brightness_h . ')');
            $res_ctx->load_settings_raw($style_atts_prefix . 'effect_on_h' . $style_atts_uid, 1);
        }
        if ($fe_contrast_h != '1') {
            $res_ctx->load_settings_raw($style_atts_prefix . 'fe_contrast_h' . $style_atts_uid, 'contrast(' . $fe_contrast_h . ')');
            $res_ctx->load_settings_raw($style_atts_prefix . 'effect_on_h' . $style_atts_uid, 1);
        }
        if ($fe_saturate_h != '1') {
            $res_ctx->load_settings_raw($style_atts_prefix . 'fe_saturate_h' . $style_atts_uid, 'saturate(' . $fe_saturate_h . ')');
            $res_ctx->load_settings_raw($style_atts_prefix . 'effect_on_h' . $style_atts_uid, 1);
        }
        // make hover to work
        if ($fe_brightness_h != '1' || $fe_contrast_h != '1' || $fe_saturate_h != '1') {
            $res_ctx->load_settings_raw($style_atts_prefix . 'effect_on' . $style_atts_uid, 1);
        }
        if ($fe_brightness != '1' || $fe_contrast != '1' || $fe_saturate != '1') {
            $res_ctx->load_settings_raw($style_atts_prefix . 'effect_on_h' . $style_atts_uid, 1);
        }

    }


    function render($atts, $content = null) {
        parent::render($atts);

	    // $unique_block_class - the unique class that is on the block. use this to target the specific instance via css
        $this->unique_block_class = $this->block_uid;

        $this->shortcode_atts = shortcode_atts(
			array_merge( td_api_multi_purpose::get_mapped_atts( __CLASS__ ) ),
			$atts
        );

        $image = $this->get_shortcode_att( 'image' );

        // external image
        $image_external = td_util::get_custom_field_value_from_string( $this->get_shortcode_att('image_cf') );
        if ( is_numeric($image_external) ) {
            $image_external = wp_get_attachment_image_url($image_external, 'full');
        }

        $caption_text = rawurldecode( base64_decode( strip_tags( $this->get_shortcode_att( 'caption_text' ) ) ) );
        $caption_position = $this->get_shortcode_att( 'caption_position' );

        $modal_image = $this->get_shortcode_att( 'modal_image' );
        $display_inline = $this->get_shortcode_att( 'display_inline' );

        $image_width = $this->get_shortcode_att( 'img_width' );
        $image_height = $this->get_shortcode_att( 'img_height' );

        // get desktop value also on responsive
        $image_width_all = json_decode( tdc_b64_decode($image_width), true );
        $image_height_all = json_decode( tdc_b64_decode($image_height), true );

        $image_width = !empty($image_width_all['all']) ? $image_width_all['all'] : $image_width;
        $image_height = !empty($image_height_all['all']) ? $image_height_all['all'] : $image_height;


        $image_info = array();
        $image_width_html = '';
        $image_height_html = '';

        if ( !empty($atts['image']) || '' !== $image_external ) {

            if ( '' !== $image_external ) {
                $image_info['url'] = $image_external;
            } else {
                // just in case we have an url instead of attachment id (using search and replace or something)
                if ( !is_numeric($image) ) {
                    $url_host = parse_url($image, PHP_URL_HOST);
                    if ( strpos($image, 'placeholders/thumb_07.jpg') ) {
                        $image_info['url'] = $image;
                    } elseif ($url_host === $_SERVER['HTTP_HOST']) {
                        $image = preg_replace("/(?:[-_]?\d{2,4}x\d{2,4})+(?=\.jpg$)/", "", $image);
                        $atts['image'] = attachment_url_to_postid($image);
                    }
                }
                //get image atts
                $image_info = tdc_util::get_image($atts);
                if ($image_width != '' && $image_height != '') {
                    $image_width_html = ' width="' . $image_width . '"';
                    $image_height_html = ' height="' . $image_height . '"';
                } elseif (is_array($image_info)) { // width/height from full img
                    $image_width_html = ' width="' . $image_info ["width"] . '"';
                    $image_height_html = ' height="' . $image_info["height"] . '"';
                }
            }
	    }

	    $image_title = '';
	    if( isset($image_info['title']) && $image_info['title'] !== '' ) {
            $image_title = ' title="' . $image_info['title'] .  '"';
        }
        $image_alt = '';
        if( isset($image_info['alt']) && $image_info['alt'] != '' ) {
            $image_alt = ' alt="' . $image_info['alt'] .  '"';
        }

        $additional_classes = array();

        $tds_animation_stack = td_util::get_option('tds_animation_stack');
        if ( empty($tds_animation_stack) ) { //lazyload animation is ON
            $additional_classes[] = 'td-animation-stack';
        }

        // display inline
        if( !empty ( $display_inline ) ) {
            $additional_classes[] = 'tdm-inline-block';
        }

        // caption position
        if( !empty ( $caption_position ) ) {
            $additional_classes[] = 'tdm-caption-over-image';
        }

        // content align horizontal
	    $content_align_horizontal = $this->get_shortcode_att( 'content_align_horizontal' );
        if( ! empty( $content_align_horizontal ) ) {
            $additional_classes[] = 'tdm-' . $content_align_horizontal;
        }

        // Check to see if the element is being called into a tdb module template
        if( td_global::get_in_tdb_module_template() ) {
            $additional_classes[] = get_class($this) . '_' . self::$module_template_part_index;
        }

        // video pop-up
        $video_popup = $this->get_att( 'video_popup' );
        $video_url = $this->get_att( 'video_url' );
        $video_popup_class = '';
        $video_popup_data = '';

        if( $video_popup != '' ) {

            if( $video_url != '' ) {
                $video_source = td_video_support::detect_video_service($video_url);

                $autoplay_vid = $this->get_att( 'autoplay_vid' );

                $video_popup_class = 'td-image-video-modal';
                $video_popup_data = 'data-video-source="' . $video_source . '" data-video-autoplay="' . $autoplay_vid . '" data-video-url="'. esc_url( $video_url ) . '"';

                $video_rec = '';
                if( $this->get_att( 'video_rec' ) != '' ) {
                    $video_rec = rawurldecode( base64_decode( strip_tags( $this->get_att( 'video_rec' ) ) ) );
                }
                $video_rec_title = '';
                if( $this->get_att( 'video_rec_title' ) != '' ) {
                    $video_rec_title = $this->get_att( 'video_rec_title' );
                }

                $video_popup_ad = array(
                    'code' => $video_rec,
                    'title' => $video_rec_title
                );

                if( $video_popup_ad['code'] != '' ) {
                    $video_popup_data .= ' data-video-rec="' . base64_encode( json_encode($video_popup_ad) ) . '"';
                }

                if( TD_THEME_NAME == "Newspaper" ) {
                    // load js
                    td_resources_load::render_script( TDC_SCRIPTS_URL . '/tdAjaxVideoModal.js' . TDC_SCRIPTS_VER, 'tdAjaxVideoModal-js', '', 'footer');
                }
            }

        }


        // video custom url
        $url = $this->get_att('url');
        $url = td_util::get_custom_field_value_from_string( $url );
        $url = td_util::get_cloud_tpl_var_value_from_string( $url );
        $url_target = '';
        if( $this->get_att('url_target') != '' ) {
            $url_target = 'target="blank"';
        }
        $url_rel = '';
        if( $this->get_att('url_rel') != '' ) {
            $url_rel = 'rel="' . $this->get_att('url_rel') . '"';
        }
        $buffy_wrap_tag_open = '';
        $buffy_wrap_tag_close = '';
        if( $url != '' && $video_popup == '' && empty( $modal_image ) ) {
            $buffy_wrap_tag_open = 'a href="' . $url . '" ' . $url_target . ' ' . $url_rel;
            $buffy_wrap_tag_close = 'a';
        } else {
            $buffy_wrap_tag_open = 'div';
            $buffy_wrap_tag_close = 'div';
        }

	    $buffy = '<div class="tdm_block ' . $this->get_block_classes($additional_classes) . '" ' . $this->get_block_html_atts() . '>';

            if ( empty( $image_info['url'] ) ) {
                $buffy .= td_util::get_block_error( 'Inline single image', "Configure this block/widget's to have an image" );
            } else {

                // get the block css
                $buffy .= $this->get_block_css();

                $buffy .= '<' . $buffy_wrap_tag_open . ' class="tdm-inline-image-wrap ' . $video_popup_class . '" ' . $video_popup_data . '>';
                    if( !empty( $modal_image ) && ( $video_popup == '' || $video_url == '' ) ) {
                        $buffy .= '<a href="' . $image_info['url'] . '">';
                            $buffy .= '<img class="tdm-image td-fix-index td-modal-image" src="' . $image_info['url'] . '" ' . $image_title . $image_alt . $image_width_html . $image_height_html . '>';
                        $buffy .= '</a>';
                    } else {
                        if( $video_popup != '' && $video_url != '' ) {
                            $buffy .= '<span class="td-video-play-ico"><i class="td-icon-video-thumb-play"></i></span>';
                        }
                        if ( empty( $tds_animation_stack ) && !td_util::tdc_is_live_editor_ajax() && !td_util::tdc_is_live_editor_iframe() && !td_util::is_mobile_theme() && !td_util::is_amp() ) {

	                        $base64 = '';

                            if ( isset($image_info['size']) ) {

                                $size = strpos( $image_info['size'], 'td_' ) === 0 ? $image_info['size'] : 'td_1068x0';
                                $thumbs = td_api_thumb::get_all();
                                foreach ( $thumbs as $thumb_id => $thumb_data ) {
                                    if ( $thumb_id === $size ) {
                                        if ( isset( $thumb_data['b64_encoded'] ) ) {
                                            $base64 = td_api_thumb::get_key( $size, 'b64_encoded' );
                                        }
                                    }
                                }
                            }

	                        $src = 'src="' . $base64 . '"';

                            $buffy .= '<img class="tdm-image td-fix-index td-lazy-img" ' . $src . ' data-type="image_tag" data-img-url="' . $image_info['url'] . '" ' . $image_title . $image_alt . $image_width_html . $image_height_html . '>';
                        } else {
                            $buffy .= '<img class="tdm-image td-fix-index" src="' . $image_info['url'] . '" ' . $image_title . $image_alt . $image_width_html . $image_height_html . '>';
                        }
                    }
                $buffy .= '</' . $buffy_wrap_tag_close . '>';

                if( $caption_text != '' ) {
                    $buffy .= '<div class="tdm-caption">' . $caption_text . '</div>';
                }
            }

        $buffy .= '</div>';


        return $buffy;
    }
}