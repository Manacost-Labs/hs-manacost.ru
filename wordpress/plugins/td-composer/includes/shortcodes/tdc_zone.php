<?php
/**
 * Created by PhpStorm.
 * User: tagdiv
 * Date: 16.10.2018
 * Time: 15:47
 */


class tdc_zone extends tdc_composer_block {

	private $atts;

	private static $suffix_class = '';

	public function get_custom_css() {
		// $unique_block_class - the unique class that is on the block. use this to target the specific instance via css
		$unique_block_class = $this->get_att('tdc_css_class');
        $unique_block_id = $this->block_uid;

        $compiled_css = '';

		$raw_css =
			"<style>
                /* @row_full_height */
                .$unique_block_class {
                    min-height: 100vh;
                }
                /* @row_auto_height */
                .$unique_block_class {
                    min-height: 0;
                }

                /* @row_fixed */
                @media (min-width: 768px) {
	                .$unique_block_class > .td-element-style > .td-element-style-before {
	                    background-attachment: fixed;
	                }
	                .tdc-row[class*='stretch_row'] > .$unique_block_class.td-pb-row > .td-element-style {
	                    left: calc((-100vw + 100%)/2) !important;
	                    transform: none !important;
	                }
                }
                
                /* @row_bg_solid */
                 .$unique_block_class > .td-element-style:after {
                    content: '' !important;
                    width: 100% !important;
                    height: 100% !important;
                    position: absolute !important;
                    top: 0 !important;
                    left: 0 !important;
                    z-index: 0 !important;
                    display: block !important;
                    background-color: @row_bg_solid !important;
                }
                /* @row_bg_gradient */
                 .$unique_block_class > .td-element-style:after {
                    content: '' !important;
                    width: 100% !important;
                    height: 100% !important;
                    position: absolute !important;
                    top: 0 !important;
                    left: 0 !important;
                    z-index: 0 !important;
                    display: block !important;
                    @row_bg_gradient
                }
                
                
                /* @zone_shadow */
                .$unique_block_class:before {
                    content: '';
                    display: block;
                    width: 100vw;
                    height: 100%;
                    position: absolute;
                    left: 50%;
                    transform: translateX(-50%);
                    box-shadow: @zone_shadow;
                    z-index: 20;
                    pointer-events: none;
                }
                @media (max-width: 767px) {
                    .$unique_block_class:before {
                        width: 100%;
                    }
                }
                
                
                /* @h_display */
                .td-header-desktop-wrap {
                    position: @h_display;
                }
                /* @h_absolute */
                .td-header-desktop-wrap {
                    top: auto;
                    bottom: auto;
                }
                /* @h_position_top */
                .td-header-desktop-wrap {
                    top: 0;
                    bottom: auto;
                }
                /* @h_position_bottom */
                .td-header-desktop-wrap {
                    top: auto;
                    bottom: 0;
                }
                
                
                /* @hs_opacity */
                .td-header-desktop-sticky-wrap.td-header-active {
                    opacity: @hs_opacity;
                }
                /* @hs_slide_down */
                .td-header-desktop-sticky-wrap {
                    transform: translateY(-120%);
                    -webkit-transform: translateY(-120%);
                    -moz-transform: translateY(-120%);
                    -ms-transform: translateY(-120%);
                    -o-transform: translateY(-120%);
                }
                .td-header-desktop-sticky-wrap.td-header-active {
                    transform: translateY(0);
                    -webkit-transform: translateY(0);
                    -moz-transform: translateY(0);
                    -ms-transform: translateY(0);
                    -o-transform: translateY(0);
                }
                /* @hs_transitions_speed */
                .td-header-desktop-sticky-wrap {
                    -webkit-transition: all @hs_transitions_speed ease-in-out;
                    -moz-transition: all @hs_transitions_speed ease-in-out;
                    -o-transition: all @hs_transitions_speed ease-in-out;
                    transition: all @hs_transitions_speed ease-in-out;
                }
                
                
                /* @m_display */
                .td-header-mobile-wrap {
                    position: @m_display;
                    width: 100%;
                }
                /* @m_absolute */
                .td-header-desktop-wrap {
                    top: auto;
                    bottom: auto;
                }
                /* @m_position_top */
                .td-header-mobile-wrap {
                    top: 0;
                    bottom: auto;
                }
                /* @m_position_bottom */
                .td-header-mobile-wrap {
                    top: auto;
                    bottom: 0;
                }
                
                
                /* @ms_opacity */
                .td-header-mobile-sticky-wrap.td-header-active {
                    opacity: @ms_opacity;
                }
                /* @ms_slide_down */
                .td-header-mobile-sticky-wrap {
                    transform: translateY(-120%);
                    -webkit-transform: translateY(-120%);
                    -moz-transform: translateY(-120%);
                    -ms-transform: translateY(-120%);
                    -o-transform: translateY(-120%);
                }
                .td-header-mobile-sticky-wrap.td-header-active {
                    transform: translateY(0);
                    -webkit-transform: translateY(0);
                    -moz-transform: translateY(0);
                    -ms-transform: translateY(0);
                    -o-transform: translateY(0);
                }
                /* @ms_transitions_speed */
                .td-header-mobile-sticky-wrap {
                    -webkit-transition: all @ms_transitions_speed ease-in-out;
                    -moz-transition: all @ms_transitions_speed ease-in-out;
                    -o-transition: all @ms_transitions_speed ease-in-out;
                    transition: all @ms_transitions_speed ease-in-out;
                }

			</style>";


        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->atts );

        $compiled_css .= $td_css_res_compiler->compile_css();
        return $compiled_css;
	}

    static function cssMedia( $res_ctx ) {

        // full height
        $full_height = $res_ctx->get_shortcode_att('row_full_height');
        if( $full_height != '' ) {
            $res_ctx->load_settings_raw( 'row_full_height', 1 );
        } else {
            $res_ctx->load_settings_raw( 'row_auto_height', 1 );
        }

        // fixed background image
        $res_ctx->load_settings_raw( 'row_fixed', $res_ctx->get_shortcode_att('row_fixed') );

        // background gradient
        $res_ctx->load_color_settings( 'row_bg_gradient', 'row_bg_solid', 'row_bg_gradient', '', '' );

        // shadow
        $res_ctx->load_shadow_settings( 0, 0, 6, 0, 'rgba(0, 0, 0, 0.08)', 'zone_shadow' );




        $type = $res_ctx->get_shortcode_att('type');

        // Main menu
        if( $type == 'tdc_header_desktop' ) {
            $h_display = $res_ctx->get_shortcode_att('h_display');
            if( $h_display == '' ) {
                $res_ctx->load_settings_raw('h_display', 'relative');
            }
            if( $h_display == 'absolute' ) {
                $res_ctx->load_settings_raw('h_display', 'absolute');
                $res_ctx->load_settings_raw('h_absolute', 1);
            }
            if( $h_display == 'fixed' ) {
                $res_ctx->load_settings_raw('h_display', 'fixed');
                $res_ctx->load_settings_raw('h_position_top', 1);
            }
            if( $h_display == 'fixed_bottom' ) {
                $res_ctx->load_settings_raw('h_display', 'fixed');
                $res_ctx->load_settings_raw('h_position_bottom', 1);
            }
        }

        // Main menu sticky
        if( $type == 'tdc_header_desktop_sticky' ) {
            $hs_transition_effect = $res_ctx->get_shortcode_att('hs_transition_effect');
            if( $hs_transition_effect == 'opacity' || $hs_transition_effect == 'slide' ) {
                $res_ctx->load_settings_raw('hs_transitions_speed', $res_ctx->get_shortcode_att('hs_transitions_speed') . 's');
            }
            if( $hs_transition_effect == 'slide' ) {
                $res_ctx->load_settings_raw('hs_slide_down', 1);
            }

            // opacity
            $res_ctx->load_settings_raw('hs_opacity', $res_ctx->get_shortcode_att('hs_opacity'));
        }

        // Mobile menu
        if( $type == 'tdc_header_mobile' ) {
            $m_display = $res_ctx->get_shortcode_att('m_display');
            if( $m_display == '' ) {
                $res_ctx->load_settings_raw('m_display', 'relative');
            }
            if( $m_display == 'absolute' ) {
                $res_ctx->load_settings_raw('m_display', 'absolute');
                $res_ctx->load_settings_raw('m_absolute', 1);
            }
            if( $m_display == 'fixed' ) {
                $res_ctx->load_settings_raw('m_display', 'fixed');
                $res_ctx->load_settings_raw('m_position_top', 1);
            }
            if( $m_display == 'fixed_bottom' ) {
                $res_ctx->load_settings_raw('m_display', 'fixed');
                $res_ctx->load_settings_raw('m_position_bottom', 1);
            }
        }

        // Mobile menu sticky
        if( $type == 'tdc_header_mobile_sticky' ) {
            $ms_transition_effect = $res_ctx->get_shortcode_att('ms_transition_effect');
            if( $ms_transition_effect == 'opacity' || $ms_transition_effect == 'slide' ) {
                $res_ctx->load_settings_raw('ms_transitions_speed', $res_ctx->get_shortcode_att('ms_transitions_speed') . 's');
            }
            if( $ms_transition_effect == 'slide' ) {
                $res_ctx->load_settings_raw('ms_slide_down', 1);
            }

            // opacity
            $res_ctx->load_settings_raw('ms_opacity', $res_ctx->get_shortcode_att('ms_opacity'));
        }

    }


	function render($atts, $content = null) {
		parent::render($atts);

		$this->atts = shortcode_atts( array(

			'row_full_height' => '',
			'row_parallax' => '',
			'row_fixed' => '',
            'row_bg_gradient' => '',
            'zone_shadow_shadow_size' => '',
            'zone_shadow_shadow_offset_horizontal' => '',
            'zone_shadow_shadow_offset_vertical' => '',
            'zone_shadow_shadow_spread' => '',
            'zone_shadow_shadow_color' => '',
			'video_background' => '',
            'mobile_youtube_autoplay' => '',
            'is_mobile_video_or_image' => '',
            'mobile_video_image' => '',
            'mobile_video' => '',
            'mobile_video_image_js' => '',
			'video_start' => '2',
			'video_scale' => '',
			'video_opacity' => '',

            'h_display' => '',

            'hs_sticky_type' => '',
            'hs_sticky_offset' => '0',
            'hs_transition_effect' => '',
            'hs_opacity' => '1',
            'hs_transitions_speed' => '0.3',

            'm_display' => '',

            'ms_sticky_type' => '',
            'ms_sticky_offset' => '0',
            'ms_transition_effect' => '',
            'ms_opacity' => '1',
            'ms_transitions_speed' => '0.3',

            'type' => '',
            'mob_load' => '',
            'desktop_load' => '',

		), $atts );

		$zone_class = 'tdc-zone';

		if ( td_global::get_in_element() && ( tdc_state::is_live_editor_ajax() || tdc_state::is_live_editor_iframe() ) ) {
		    $zone_class .= '-composer';
        }

		td_global::set_in_zone(true);

		$buffy = '';

        if ( 'Newspaper' === TD_THEME_NAME ) {
            $block_classes = array('wpb_row', 'td-pb-row');
        } else {
            $block_classes = '';
        }

		$addElementStyle = false;
		$css_elements = $this->get_block_css($clearfixColumns, $addElementStyle);

		if ( $addElementStyle ) {
			$block_classes[] = 'tdc-element-style';
		}

		$type = $this->atts['type'];

		$sticky_offset = '';
		if( $type == 'tdc_header_desktop_sticky' ) {
		    if( $this->atts['hs_sticky_type'] != '' ) {
                $block_classes[] = 'td-header-sticky-smart';
            }
            if( $this->atts['hs_sticky_offset'] != '' ) {
                $sticky_offset = 'data-sticky-offset="' . $this->atts['hs_sticky_offset'] . '"';
            }
        }
        if( $type == 'tdc_header_mobile_sticky' ) {
            if( $this->atts['ms_sticky_type'] != '' ) {
                $block_classes[] = 'td-header-sticky-smart';
            }
            if( $this->atts['ms_sticky_offset'] != '' ) {
                $raw = $this->atts['ms_sticky_offset'];
                $value = 0;

                if ( is_numeric( $raw ) ) {
                    $value = absint( $raw );
                } else {
                    $decoded = base64_decode( $raw, true );
                    if ( $decoded === false ) {
                        $decoded = base64_decode( rawurldecode( $raw ), true );
                    }
                    if ( $decoded !== false ) {
                        $map = json_decode( $decoded, true );
                        if ( is_array( $map ) ) {
                            // normalize numeric strings
                            foreach ( $map as $k => $v ) {
                                if ( is_numeric( $v ) ) {
                                    $map[$k] = (int) $v;
                                } else {
                                    unset( $map[$k] );
                                }
                            }
                            // precedence for mobile sticky
                            if ( isset( $map['phone'] ) ) {
                                $value = absint( $map['phone'] );
                            } elseif ( isset( $map['portrait'] ) ) {
                                $value = absint( $map['portrait'] );
                            } elseif ( isset( $map['landscape'] ) ) {
                                $value = absint( $map['landscape'] );
                            } elseif ( isset( $map['all'] ) ) {
                                $value = absint( $map['all'] );
                            }
                        }
                    }
                }

                $sticky_offset = 'data-sticky-offset="' . esc_attr( $value ) . '"';
            }
        }

        // zone render check when not in composer
        if ( !td_util::tdc_is_live_editor_iframe() && !td_util::tdc_is_live_editor_ajax() ) {

            // on mobile
            if ( td_util::is_mobile() ) {

                if ( in_array( $type, [ 'tdc_header_desktop', 'tdc_header_desktop_sticky' ] ) ) {

                    // read the mobile load attribute
                    $mob_load = $this->atts['mob_load'];

                    // stop here if mobile rendering is stopped
                    if ( !empty($mob_load) ) {
                        //$buffy .= 'zone mobile render stopped';
                        return $buffy;
                    }

                }

            // on desktop
            } else {

                if ( in_array( $type, [ 'tdc_header_mobile', 'tdc_header_mobile_sticky' ] ) ) {

                    // read the desktop load attribute
                    $desktop_load = $this->atts['desktop_load'];

                    // stop here if desktop rendering is stopped
                    if ( !empty($desktop_load) ) {
                        //$buffy .= 'zone desktop render stopped';
                        return $buffy;
                    }

                }

            }

        }

		$buffy .= '<div ' . $this->get_block_dom_id() . 'class="' . $this->get_block_classes($block_classes) . '" ' . $sticky_offset . ' >';
		//get the block css

		// Flag used to know outside if the '.clearfix' element is added as last child in vc_row and vc_row_inner
		// '.clearfix' was necessary to apply '::after' css settings from TagDiv Composer (the '::after' element comes with absolute position and at the same time a 'clear' is necessary)
		$clearfixColumns = false;

        // Video background.
        $video_background = $this->atts['video_background'];
        $videos_info = null;

        if( !empty( $video_background ) ) {
            // Detect video service.
            $video_service = 'self-hosted';
            if( preg_match('/^[a-zA-Z0-9_-]{11}$/', $video_background) ) {
                $url = 'https://www.youtube.com/oembed?url=http://www.youtube.com/watch?v=' . $video_background . '&format=json';
                $headers = get_headers($url);

                if (strpos($headers[0], '200') !== false) {
                    $video_service = 'youtube';
                    $videos_info = td_remote_video::api_get_videos_info( array( $video_background ), 'youtube');
                }
            }

            // Deal with possible errors.
            if( $video_service != 'self-hosted' && $video_service != 'youtube' ) {
                // Video is neither self-hosted, nor from YouTube.
                $buffy .= $this->video_background_error('Please enter a <strong>self-hosted</strong> or a <strong>YouTube</strong> video.');
            } else if( $video_service == 'youtube' && td_util::get_option('tds_yt_api_key') == '' && TD_DEPLOY_MODE == 'deploy' ) {
                // Video is from YouTube, but the YT API is missing.
                $buffy .= $this->video_background_error('<strong>A YouTube API key</strong> has not been provided. Go to <strong>Theme Panel > Social Networks > YouTube API Configuration</strong>');
            } else if( $video_service == 'youtube' && !( is_array( $videos_info ) && count( $videos_info ) ) ) {
                // Video is from YouTube, but not info could not be retrieved for it.
                $buffy .= $this->video_background_error('<strong>Video id</strong> was not found or can\'t be retrieved.');
            } else {
                // Define output variable.
                $video_html = '';

                // Detect mobile device.
                $is_mobile = false;
                if( class_exists( 'Mobile_Detect' ) ) {
                    $mobile_detect = new Mobile_Detect();
                    if( $mobile_detect->isMobile() ) {
                        $is_mobile = true;
                    }
                }

                // Video seems alright, proceed with rendering it, depending on the service.
                // Add row specific class.
//                $row_class .= ' tdc-row-video-background';

                // Render general style.
                ob_start();
                ?>
                <style>

                    /* custom css - generated by TagDiv Composer */
                    .tdc-row-video-background {
                        position: relative;
                    }
                    .tdc-video-outer-wrapper {
                        position: absolute;
                        width: 100%;
                        height: 100%;
                        overflow: hidden;
                        left: 0;
                        right: 0;
                        pointer-events: none;
                        top: 0;
                    }
                    .tdc-video-thumb-on-mobile {
                        display: none;
                        background-size: cover;
                        background-position: center top;
                        width: 100%;
                        height: 100%;
                        position: absolute;
                        top: 0;
                        left: 0;
                    }
                    @media (max-width: 767px) {
                        .tdc-video-outer-wrapper {
                            width: 100vw;
                            left: 50%;
                            transform: translateX(-50%);
                            -webkit-transform: translateX(-50%);
                        }
                    }
                    .tdc-video-parallax-wrapper,
                    .tdc-video-inner-wrapper {
                        position: absolute;
                        width: 100%;
                        height: 100%;
                        left: 0;
                        right: 0;
                    }
                    .tdc-video-inner-wrapper iframe,
                    .tdc-video-inner-wrapper video {
                        position: absolute;
                        left: 50%;
                        top: 50%;
                        transform: translate3d(-50%, -50%, 0);
                        -webkit-transform: translate3d(-50%, -50%, 0);
                        -moz-transform: translate3d(-50%, -50%, 0);
                        -ms-transform: translate3d(-50%, -50%, 0);
                        -o-transform: translate3d(-50%, -50%, 0);
                    }
                    .tdc-video-inner-wrapper iframe {
                        opacity: 0;
                        transition: opacity 0.4s;
                    }
                    .tdc-video-inner-wrapper video {
                        max-width: none;
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                    }
                    .tdc-video-inner-wrapper iframe.tdc-video-background-visible {
                        opacity: 1 !important;
                    }
                    .tdc-row[class*="stretch_row"] .tdc-video-outer-wrapper {
                        width: 100vw;
                        left: 50%;
                        transform: translateX(-50%);
                        -webkit-transform: translateX(-50%);
                        -moz-transform: translateX(-50%);
                        -ms-transform: translateX(-50%);
                        -o-transform: translateX(-50%);
                    }

                </style>
                <?php
                $video_html .= ob_get_clean();

                // Define video wrapper data atts.
                $video_data_atts = array(
                    'video-js-switch' => 'false'
                );

                // Depending on the service, construct the video HTML.
                if( $video_service == 'self-hosted' ) {
                    // We are dealing with self-hosted videos.
                    // Generate the HTML for the desktop video.
                    $desktop_video_html = td_video_support::render_video($video_background, '','yes', 'yes');

                    // Get specific attributes.
                    $mobile_video_image_enabled = !empty( $this->atts['is_mobile_video_or_image'] );
                    $video_js_switch = !empty( $this->atts['mobile_video_image_js'] );
                    $mobile_image = $this->atts['mobile_video_image'];
                    $mobile_video = $this->atts['mobile_video'];

                    // Update the video wrapper data atts.
                    if( $mobile_video_image_enabled && $video_js_switch && ( !empty( $mobile_image ) || !empty( $mobile_video ) ) ) {
                        $video_data_atts['video-js-switch'] = 'true';
                        $video_data_atts = array_merge(
                            $video_data_atts,
                            array(
                                'desktop-video' => $video_background,
                                'mobile-image' =>  !empty( $mobile_image ) ? wp_get_attachment_url($mobile_image) : '',
                                'mobile-video' => $mobile_video
                            )
                        );
                    }

                    // Check whether mobile version image/video is enabled, and if at least
                    // one of them is not empty; otherwise get the desktop video.
                    if( $mobile_video_image_enabled && ( !empty( $mobile_image ) || !empty( $mobile_video ) ) ) {
                        // Only get the image/video if the JS switch option is disabled.
                        if( !$video_js_switch ) {
                            // If on mobile, output the image/video; otherwise output the desktop video.
                            if( $is_mobile ) {
                                if( !empty( $mobile_image ) ) {
                                    $video_html .= '<div class="tdc-video-thumb-on-mobile tdc-is-video-image" style="background-image:url(' . wp_get_attachment_url($mobile_image) . ');"></div>';
                                } else if( !empty( $mobile_video ) ) {
                                    $video_html .= td_video_support::render_video($mobile_video, '', 'yes', 'yes');
                                }
                            } else {
                                $video_html .= $desktop_video_html;
                            }
                        }
                    } else {
                        $video_html .= $desktop_video_html;
                    }
                } else {
                    // We are dealing with a YouTube video.
                    foreach( $videos_info as $video_id => $video_info ) {
                        $video_html .= $videos_info[ $video_id ]['embedHtml'];
                        $video_html .= '<div class="tdc-video-thumb-on-mobile" style="background-image:url(' . $video_info['standard'] . ');"></div>';
                        break;
                    }
                }

                // Render the wrapper and video HTML.
                $video_data_atts_string = '';
                foreach ( $video_data_atts as $key => $value ) {
                    $video_data_atts_string .= 'data-' . $key . '="' . $value . '" ';
                }

                $buffy .= '<div class="tdc-video-outer-wrapper">';
                    $buffy .= '<div class="tdc-video-parallax-wrapper">';
                        $buffy .= '<div class="tdc-video-inner-wrapper" data-video-service="' . $video_service . '" ' . $video_data_atts_string . ' data-video-scale="' . $this->atts['video_scale'] . '" data-video-opacity="' . $this->atts['video_opacity'] . '">';
                            $buffy .= $video_html;
                        $buffy .= '</div>';
                    $buffy .= '</div>';
                $buffy .= '</div>';

                if( TD_THEME_NAME == "Newspaper" ) {
                    td_resources_load::render_script( TDC_SCRIPTS_URL . '/tdAnimationScroll.js' . TDC_SCRIPTS_VER, 'tdAnimationScroll-js', '', 'footer');
                }
                ob_start();
                ?>
                <script>
                    jQuery(window).ready(function () {

                        // We need timeout because the content must be rendered and interpreted on client.
                        setTimeout(function() {

                            let $content = jQuery('body').find('#tdc-live-iframe')
                            if ($content.length) {
                                $content = $content.contents()
                            } else {
                                $content = jQuery('body')
                            }

                            const $tdcVideoInnerWrappers = $content.find('#<?php echo $this->block_uid ?> .tdc-video-inner-wrapper:first')
                            $tdcVideoInnerWrappers.each(function() {
                                const $wrapper = jQuery(this)
                                const videoService = $wrapper.data('video-service')

                                if ('undefined' !== typeof $wrapper.data('video-scale')) {
                                    $wrapper.css({
                                        transform: 'scale(' + $wrapper.data('video-scale') + ')'
                                    });
                                }
                                if ('undefined' !== typeof $wrapper.data('video-opacity')) {
                                    $wrapper.css({
                                        opacity: $wrapper.data('video-opacity')
                                    });
                                }

                                switch ( videoService ) {
                                    case 'self-hosted':
                                        const videoJSSwitch = $wrapper.data('video-js-switch')

                                        if ( videoJSSwitch ) {
                                            const $window = jQuery(window)
                                            const isMobile = $window.outerWidth() <= 500
                                            const desktopVideoURL = $wrapper.data('desktop-video')
                                            const mobileImageURL = $wrapper.data('mobile-image')
                                            const mobileVideoURL = $wrapper.data('mobile-video')
                                            let contentHTML = ''

                                            if ( !isMobile || ( isMobile && mobileImageURL === '' ) ) {
                                                contentHTML = '<div class="wpb_video_wrapper">'
                                                    contentHTML += '<video autoplay muted playsinline loop>'
                                                        contentHTML += '<source src="' + ( !isMobile ? desktopVideoURL : mobileVideoURL ) + '">'
                                                        contentHTML += 'Your browser does not support the video tag.'
                                                    contentHTML += '</video>'
                                                contentHTML += '</div>'
                                            } else {
                                                contentHTML = '<div class="tdc-video-thumb-on-mobile tdc-is-video-image" style="background-image:url(' + mobileImageURL + ')"></div>'
                                            }

                                            $wrapper.append(contentHTML)
                                        }

                                        $content.find('.tdc-video-thumb-on-mobile').show()

                                        break;

                                    case 'youtube':
                                        const $iframe = $wrapper.find('iframe')
                                        const autoplayOnMobile = <?php echo json_encode($this->atts['mobile_youtube_autoplay'] != '') ?>;

                                        if ( $iframe.length ) {
                                            if ('undefined' === typeof $iframe.data('src-src')) {
                                                $iframe.data('api-src', $iframe.attr('src'));
                                            }

                                            let iframeSettingsStr = '',
                                                iframeSettings = {
                                                    autoplay: 1,
                                                    loop: 1,
                                                    mute: 1,
                                                    showinfo: 0,
                                                    controls: 0,
                                                    start: <?php echo (int)$this->atts['video_start']; ?>,
                                                    playlist: '<?php echo $video_background; ?>',
                                                };

                                            for ( let prop in iframeSettings ) {
                                                iframeSettingsStr += prop + '=' + iframeSettings[prop] + '&';
                                            }

                                            $iframe.attr('src', $iframe.data('api-src') + '?' + iframeSettingsStr);

                                            $iframe.on( 'load', function () {
                                                var $iframe = jQuery(this),
                                                    iframeWidth = $iframe.width(),
                                                    iframeHeight = $iframe.height(),
                                                    iframeAspectRatio = iframeHeight / iframeWidth,
                                                    wrapperWidth = $wrapper.width(),
                                                    wrapperHeight = $wrapper.height(),
                                                    wrapperAspectRatio = wrapperHeight / wrapperWidth;

                                                $iframe.attr( 'aspect-ratio', iframeAspectRatio );

                                                if (iframeAspectRatio < wrapperAspectRatio) {
                                                    $iframe.css({
                                                        width: wrapperHeight / iframeAspectRatio,
                                                        height: wrapperHeight
                                                    });
                                                } else if (iframeAspectRatio > wrapperAspectRatio) {
                                                    $iframe.css({
                                                        width: '100%',
                                                        height: iframeAspectRatio * wrapperWidth
                                                    });
                                                }

                                                setTimeout(function () {
                                                    $iframe.addClass('tdc-video-background-visible');
                                                }, 100);
                                            });

                                            if ( !autoplayOnMobile && tdDetect.isMobileDevice ) {
                                                $content.find('.tdc-video-parallax-wrapper iframe').remove();
                                                $content.find('.tdc-video-thumb-on-mobile').show();
                                            }
                                        }

                                        break;
                                }
                            });

                        }, 200);

                    });
                </script>
                <?php
                if ( defined( 'TD_SPEED_BOOSTER' ) ) {
                    td_js_buffer::add_to_footer( td_util::remove_script_tag( ob_get_clean() ) );
                } else {
                    $buffy .= ob_get_clean();
                }
            }
        }

		$buffy .= $css_elements;

		$buffy .= $this->do_shortcode($content);

		// Add '.clearfix' element as last child in vc_row and vc_row_inner
		if ($clearfixColumns) {
			$buffy .= PHP_EOL . '<span class="clearfix"></span>';
		}

		$buffy .= '</div>';


		// The following commented code is for the new theme
		//if (tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax()) {
		$buffy = '<div id="' . $this->block_uid . '" class="' . $zone_class . self::$suffix_class . '">' . $buffy . '</div>';
		//}


		ob_start();

		if ( defined( 'TD_SPEED_BOOSTER' ) ) {
			td_js_buffer::add_to_footer( td_util::remove_script_tag( ob_get_clean() ) );
		} else {
			$buffy .= ob_get_clean();
		}

		td_global::set_in_zone(false);

		// td-composer PLUGIN uses to add blockUid output param when this shortcode is retrieved with ajax (@see tdc_ajax)
		do_action( 'td_block_set_unique_id', array( &$this ) );

		return $buffy;
	}

	static function set_suffix_class( $suffix_class ) {
	    self::$suffix_class = $suffix_class;
    }

	/**
	 * Safe way to read $this->atts. It makes sure that you read them when they are ready and set!
	 * @param $att_name
	 * @param $default_value
	 * @return mixed
	 */
	protected function get_custom_att($att_name, $default_value = '') {
		if ( !isset( $this->atts ) ) {
		    echo 'TD Composer Internal error: The atts are not set yet(AKA: the LOCAL render method was not called yet and the system tried to read an att)';
			die;
		}

		if ( !isset( $this->atts[$att_name] ) ) {
			var_dump($this->atts);
			echo 'TD Composer Internal error: The system tried to use an LOCAL att that does not exists! class_name: ' . get_class($this) . '  Att name: "' . $att_name . '" The list with available atts is in vc_row::render';

			//die;
            return $default_value;
		}
		return $this->atts[$att_name];
	}

    /**
     * Outputs video background error.
     *
     * @param string $message
     * @return string
     */
    public function video_background_error( $message ) {

        /* -- Bail if user is not logged in. -- */
        if ( !is_user_logged_in() ) {
            return '';
        }


        /* -- Construct the error HTML. -- */
        // Styles.
        ob_start();
        ?>
        <style>
            /* custom css - generated by TagDiv Composer */

            .tdc-zone-video-background-error {
                position: absolute;
                top: 100px;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 100%;
                pointer-events: none;
                z-index: 1000;
            }
            @media (min-width: 1140px) {
                .tdc-zone-video-background-error {
                    padding-right: 24px;
                    padding-left: 24px;
                }
            }

            .tdc-zone-video-background-error .td-block-missing-settings {
                background-color: rgba(255, 255, 255, .85);
            }

        </style>
        <?php
        $output = ob_get_clean();

        // HTML.
        $output .= '<div class="tdc-zone-video-background-error">';
        $output .= td_util::get_block_error('Zone video background', $message);
        $output .= '</div>';

        // Return.
        return $output;

    }

}
