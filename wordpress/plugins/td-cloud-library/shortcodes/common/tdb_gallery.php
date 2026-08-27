<?php
class tdb_gallery extends td_block {

    private $slider_options = array(
        'infinite' => false,
        'autoplay' => false,
        'autoplaySpeed' => 3000,
        'swipe' => false,
        'arrows' => false,
        'prevArrow' => '<button type="button" class="slick-prev"><i class="td-icon-left-arrow"></i></button>',
        'nextArrow' => '<button type="button" class="slick-next"><i class="td-icon-right-arrow"></i></button>',
        'dots' => false,
        'focusOnSelect' => false,
        'slidesToShow' => 1,
        'slidesToScroll' => 1,
        'responsive' => array(
            array(
                'breakpoint' => 1141,
                'settings' => array(
                    'slidesToShow' => 1,
                    'slidesToScroll' => 1,
                )
            ),
            array(
                'breakpoint' => 1019,
                'settings' => array(
                    'slidesToShow' => 1,
                    'slidesToScroll' => 1,
                )
            ),
            array(
                'breakpoint' => 768,
                'settings' => array(
                    'slidesToShow' => 1,
                    'slidesToScroll' => 1,
                )
            )
        )
    );
    static $style_selector = '';
    static $style_atts_prefix = '';
    static $style_atts_uid = '';
    static $module_template_part_index = '';
    private $gallery_uid = '';

    static $slick_options_loaded = false;


    /**
     * Disable loop block features. This block does not use a loop and it doesn't need to run a query.
     */
    function __construct() {

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

        parent::disable_loop_block_features();

    }


    public function get_custom_css() {

        $style_atts_prefix = self::$style_atts_prefix;
        $style_atts_uid = self::$style_atts_uid;


        /* -- Set the style selector -- */
        $style_selector = '';

        $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();
        $in_element = td_global::get_in_element();
        if( $in_element && $in_composer ) {
            $style_selector .= 'tdc-row-composer .tdc-column-composer .';
        } else if( $in_element || $in_composer ) {
            $style_selector .= 'tdc-row .tdc-column .';
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

                /* @style_general_tdb_gallery */
                .tdb_gallery {
                    transform: translateZ(0);
                }
                .tdb_gallery .tdb-gallery-wrap {
                    position: relative;
                    overflow: hidden;
                    opacity: 0;
                    transition: opacity .2s ease-in-out;
                }
                .tdb_gallery .tdb-gallery-wrap.slick-initialized {
                    overflow: visible;
                    height: auto;
                    opacity: 1;
                }
                .tdb_gallery .slick-list {
                    margin-left: -5px;
                    margin-right: -5px;
                    overflow: hidden;
                }
                .tdb_gallery .slick-track {
                    display: flex;
                    flex-wrap: wrap;
                }
                .tdb_gallery .slick-track .slick-slide {
                    padding-left: 5px;
                    padding-right: 5px;
                    transition: opacity .2s ease-in-out;
                    opacity: 1;
                    outline: none;
                }
                .tdb_gallery .slick-track .slick-slide:not(.slick-active) {
                    opacity: 0;
                    pointer-events: none;
                }
                .tdb_gallery .tdb-gi-inner {
                    position: relative;
                    display: block;
                    overflow: hidden;
                }
                .tdb_gallery .tdb-gi-inner:after {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    pointer-events: none; 
                }
                .tdb_gallery img {
                    position: absolute;
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }
                .tdb_gallery .tdb-gi-caption {
                    position: absolute;
                    left: 0;
                    bottom: 0;
                    width: auto;
                    max-width: 100%;
                    padding: 8px 12px 9px;
                    background-color: rgba(0, 0, 0, .7);
                    font-size: 11px;
                    line-height: 1.3;
                    color: #fff;
                    z-index: 10;
                }
                .tdb_gallery .slick-arrow {
                    position: absolute;
                    top: 50%;
                    transform: translateY(-50%);
                    padding: 0;
                    background-color: transparent;
                    font-size: 34px;
                    line-height: 1;
                    color: #fff;
                    border: none;
                    border-radius: 0;
                    outline: none !important;
                    transition: color .2s ease-in-out, opacity .2s ease-in-out;
                    -webkit-appearance: none;
                    z-index: 10;
                }
                .tdb_gallery .slick-arrow.slick-disabled {
                    opacity: .45;
                }
                .tdb_gallery .slick-arrow svg {
                    display: block;
                    width: 1em;
                    height: auto;
                }
                .tdb_gallery .slick-arrow svg,
                .tdb_gallery .slick-arrow svg * {
                    fill: currentColor;
                }
                .tdb_gallery .slick-dots {
                    list-style: none;
                    display: flex;
                    flex-wrap: wrap;
                    justify-content: center;
                    gap: .833em;
                    width: 100%;
                    font-size: 11px;
                    line-height: 1;
                    margin: 0;
                }
                .tdb_gallery .slick-dots li {
                    margin: 0;
                    line-height: 0;
                }
                .tdb_gallery .slick-dots button {
                    display: block;
                    padding: 0;
                    background-color: transparent;
                    font-size: 0;
                    border: none;
                    border-radius: 0;
                    outline: none !important;
                    -webkit-appearance: none;
                }
                .tdb_gallery .slick-dots button:after {
                    content: '';
                    display: block;
                    background-color: rgba(0, 0, 0, 0.2);
                    width: 1em;
                    height: 1em;
                    font-size: 11px;
                    border-radius: 100%;
                    transition: background-color .2s ease-in-out;
                }
                .tdb_gallery .slick-dots button:hover:after,
                .tdb_gallery .slick-dots .slick-active button:after {
                    background-color: rgba(0, 0, 0, 0.7);
                }
                
                /* @style_general_tdb_gallery_composer */
                .tdb_gallery .tdb-block-inner {
                    pointer-events: none;
                }



                /* @" . $style_atts_prefix . "gap$style_atts_uid */
                body .$style_selector .slick-list {
                    margin-left: -@" . $style_atts_prefix . "gap$style_atts_uid;
                    margin-right: -@" . $style_atts_prefix . "gap$style_atts_uid;
                }
                body .$style_selector .slick-track .slick-slide {
                    padding-left: @" . $style_atts_prefix . "gap$style_atts_uid;
                    padding-right: @" . $style_atts_prefix . "gap$style_atts_uid;
                }
                
                
                /* @" . $style_atts_prefix . "focus_on_select$style_atts_uid */
                body .$style_selector .tdb-gi-inner {
                    cursor: pointer;
                }


                /* @" . $style_atts_prefix . "height$style_atts_uid */
                body .$style_selector .tdb-gi-inner {
                    padding-bottom: @" . $style_atts_prefix . "height$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "list_height$style_atts_uid */
                body .$style_selector .tdb-gallery-wrap {
                    height: @" . $style_atts_prefix . "list_height$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "all_border$style_atts_uid */
                body .$style_selector .tdb-gi-inner:after {
                    border: @" . $style_atts_prefix . "all_border$style_atts_uid @" . $style_atts_prefix . "all_border_style$style_atts_uid @" . $style_atts_prefix . "all_border_color$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "border_radius$style_atts_uid */
                body .$style_selector .tdb-gi-inner,
                body .$style_selector .tdb-gi-inner:after {
                    border-radius: @" . $style_atts_prefix . "border_radius$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "all_border_a$style_atts_uid */
                body .$style_selector .slick-current .tdb-gi-inner:after {
                    border: @" . $style_atts_prefix . "all_border_a$style_atts_uid @" . $style_atts_prefix . "all_border_a_style$style_atts_uid @" . $style_atts_prefix . "all_border_a_color$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "border_radius_a$style_atts_uid */
                body .$style_selector .slick-current .tdb-gi-inner,
                body .$style_selector .slick-current .tdb-gi-inner:after {
                    border-radius: @" . $style_atts_prefix . "border_radius_a$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "show_caption$style_atts_uid */
                body .$style_selector .tdb-gi-caption {
                    display: @" . $style_atts_prefix . "show_caption$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "caption_pos_top$style_atts_uid */
                body .$style_selector .tdb-gi-caption {
                    top: 0;
                    bottom: auto;
                }
                /* @" . $style_atts_prefix . "caption_pos_bottom$style_atts_uid */
                body .$style_selector .tdb-gi-caption {
                    top: auto;
                    bottom: 0;
                }
                /* @" . $style_atts_prefix . "caption_width$style_atts_uid */
                body .$style_selector .tdb-gi-caption {
                    width: @" . $style_atts_prefix . "caption_width$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "caption_padd$style_atts_uid */
                body .$style_selector .tdb-gi-caption {
                    padding: @" . $style_atts_prefix . "caption_padd$style_atts_uid;
                }

                /* @" . $style_atts_prefix . "caption_bg$style_atts_uid */
                body .$style_selector .tdb-gi-caption {
                    background-color: @" . $style_atts_prefix . "caption_bg$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "caption_color$style_atts_uid */
                body .$style_selector .tdb-gi-caption {
                    color: @" . $style_atts_prefix . "caption_color$style_atts_uid;
                }
                
                /* @" . $style_atts_prefix . "f_caption$style_atts_uid */
                body .$style_selector .tdb-gi-caption {
                    @" . $style_atts_prefix . "f_caption$style_atts_uid
                }


                /* @" . $style_atts_prefix . "arrows_pos_inside$style_atts_uid */
                body .$style_selector .slick-prev {
                    left: 13px;
                    right: auto;
                }
                body .$style_selector .slick-next {
                    left: auto;
                    right: 13px;
                }
                /* @" . $style_atts_prefix . "enable_focus_on_click$style_atts_uid */
                body .$style_selector .tdb-gi-inner {
                    cursor: pointer;
                }
                /* @" . $style_atts_prefix . "arrows_pos_outside$style_atts_uid */
                body .$style_selector .slick-prev {
                    left: auto;
                    right: calc(100% + 13px);
                }
                body .$style_selector .slick-next {
                    left: calc(100% + 13px);
                    right: auto;
                }
                /* @" . $style_atts_prefix . "arrows_padding$style_atts_uid */
                body .$style_selector .slick-arrow {
                    padding: @" . $style_atts_prefix . "arrows_padding$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "arrows_space_inside$style_atts_uid */
                body .$style_selector .slick-prev {
                    left: @" . $style_atts_prefix . "arrows_space_inside$style_atts_uid;
                    right: auto;
                }
                body .$style_selector .slick-next {
                    left: auto;
                    right: @" . $style_atts_prefix . "arrows_space_inside$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "arrows_space_outside$style_atts_uid */
                body .$style_selector .slick-prev {
                    left: auto;
                    right: calc(100% + @" . $style_atts_prefix . "arrows_space_outside$style_atts_uid);
                }
                body .$style_selector .slick-next {
                    left: calc(100% + @" . $style_atts_prefix . "arrows_space_outside$style_atts_uid);
                    right: auto;
                }
                /* @" . $style_atts_prefix . "arrows_radius$style_atts_uid */
                body .$style_selector .slick-arrow {
                    border-radius: @" . $style_atts_prefix . "arrows_radius$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "icon_size$style_atts_uid */
                body .$style_selector .slick-arrow {
                    font-size: @" . $style_atts_prefix . "icon_size$style_atts_uid;
                }

                /* @" . $style_atts_prefix . "icon_color$style_atts_uid */
                body .$style_selector .slick-arrow {
                    color: @" . $style_atts_prefix . "icon_color$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "icon_color_h$style_atts_uid */
                body .$style_selector .slick-arrow:hover {
                    color: @" . $style_atts_prefix . "icon_color_h$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "icon_bg$style_atts_uid */
                body .$style_selector .slick-arrow {
                    background-color: @" . $style_atts_prefix . "icon_bg$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "icon_bg_h$style_atts_uid */
                body .$style_selector .slick-arrow:hover {
                    background-color: @" . $style_atts_prefix . "icon_bg_h$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "icons_shadow$style_atts_uid */
                body .$style_selector .slick-arrow i,
                body .$style_selector .slick-arrow svg {
                    filter: drop-shadow(@" . $style_atts_prefix . "icons_shadow$style_atts_uid);
                }
                /* @" . $style_atts_prefix . "icons_bg_shadow$style_atts_uid */
                body .$style_selector .slick-arrow {
                    box-shadow: @" . $style_atts_prefix . "icons_bg_shadow$style_atts_uid;
                }


                /* @" . $style_atts_prefix . "dots_pos_inside$style_atts_uid */
                body .$style_selector .slick-dots {
                    position: absolute;
                    left: 0;
                    bottom: 13px;
                    margin-top: 0;
                }
                /* @" . $style_atts_prefix . "dots_pos_outside$style_atts_uid */
                body .$style_selector .slick-dots {
                    position: relative;
                    bottom: 0;
                    margin-top: 16px;
                }
                /* @" . $style_atts_prefix . "dots_size$style_atts_uid */
                body .$style_selector .slick-dots {
                    font-size: @" . $style_atts_prefix . "dots_size$style_atts_uid;
                }
                body .$style_selector .slick-dots button:after {
                    font-size: @" . $style_atts_prefix . "dots_size$style_atts_uid;
                }

                /* @" . $style_atts_prefix . "dots_color$style_atts_uid */
                body .$style_selector .slick-dots button:after {
                    background-color: @" . $style_atts_prefix . "dots_color$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "dots_color_h$style_atts_uid */
                body .$style_selector .slick-dots button:hover:after,
                body .$style_selector .slick-dots .slick-active button:after {
                    background-color: @" . $style_atts_prefix . "dots_color_h$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "dots_color_a$style_atts_uid */
                body .$style_selector .slick-dots .slick-active button:after {
                    background-color: @" . $style_atts_prefix . "dots_color_a$style_atts_uid;
                }
                /* @" . $style_atts_prefix . "dots_shadow$style_atts_uid */
                body .$style_selector .slick-dots button:after {
                    filter: drop-shadow(@" . $style_atts_prefix . "dots_shadow$style_atts_uid);
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

        /*-- GENERAL STYLES -- */
        $res_ctx->load_settings_raw( 'style_general_tdb_gallery', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_gallery_composer', 1 );
        }



        /*-- GENERAL -- */
        /* -- Layout -- */
        $gap = $res_ctx->get_shortcode_att( 'gap' );
        $gap = is_numeric( $gap ) ? ($gap / 2) . 'px' : $gap;
        $res_ctx->load_settings_raw( $style_atts_prefix . 'gap' . $style_atts_uid, $gap );



        /*-- SLIDES -- */
        /* -- Layout -- */
        // Slides per row
        $slides_per_row = $res_ctx->get_shortcode_att( 'per_row' );
        $slides_per_row = $slides_per_row != '' ? intval( $slides_per_row ) : 1;

        // Height
        $height = $res_ctx->get_shortcode_att( 'height' );
        $height = $height != '' ? $height : '50%';
        $height .= is_numeric( $height ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'height' . $style_atts_uid, $height );
        if( strpos( $height, '%' ) !== false ) {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'list_height' . $style_atts_uid, 'calc( (100 / ' . $slides_per_row . ') * ' . $height . ' )' );
        } else {
            $res_ctx->load_settings_raw( $style_atts_prefix . 'list_height' . $style_atts_uid, $height );
        }



        // Border size
        $all_border = $res_ctx->get_shortcode_att( 'all_border' );
        $all_border .= is_numeric( $all_border ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'all_border' . $style_atts_uid, $all_border );

        // Border style
        $all_border_style = $res_ctx->get_shortcode_att( 'all_border_style' );
        $all_border_style = $all_border_style != '' ? $all_border_style : 'solid';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'all_border_style' . $style_atts_uid, $all_border_style );

        // Border radius
        $border_radius = $res_ctx->get_shortcode_att( 'border_radius' );
        $border_radius .= is_numeric( $border_radius ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'border_radius' . $style_atts_uid, $border_radius );

        if( $res_ctx->get_shortcode_att( 'active_highlight' ) == 'yes' ) {
            // Border size
            $all_border_a = $res_ctx->get_shortcode_att( 'all_border_a' );
            $all_border_a .= is_numeric( $all_border_a ) ? 'px' : '';
            if( ( $all_border_a == '' || $all_border_a == 'inherit' ) && $all_border != '' ) {
                $all_border_a = $all_border;
            }
            $res_ctx->load_settings_raw( $style_atts_prefix . 'all_border_a' . $style_atts_uid, $all_border_a );

            // Border style
            $all_border_a_style = $res_ctx->get_shortcode_att( 'all_border_a_style' );
            if( ( $all_border_a_style == '' || $all_border_a_style == 'inherit' ) && $all_border_style != '' ) {
                $all_border_a_style = $all_border_style;
            }
            $res_ctx->load_settings_raw( $style_atts_prefix . 'all_border_a_style' . $style_atts_uid, $all_border_a_style );

            // Border radius
            $border_radius_a = $res_ctx->get_shortcode_att( 'border_radius_a' );
            $border_radius_a .= is_numeric( $border_radius_a ) ? 'px' : '';
            if( ( $border_radius_a == '' || $border_radius_a == 'inherit' ) && $border_radius != '' ) {
                $border_radius_a = $border_radius;
            }
            $res_ctx->load_settings_raw( $style_atts_prefix . 'border_radius_a' . $style_atts_uid, $border_radius_a );
        }

        // Show caption
        $show_caption = $res_ctx->get_shortcode_att( 'show_caption' );
        $show_caption = $show_caption != '' ? $show_caption : 'block';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'show_caption' . $style_atts_uid, $show_caption );

        // Caption position
        $caption_pos = $res_ctx->get_shortcode_att( 'caption_pos' );
        $caption_pos = $caption_pos != '' ? $caption_pos : 'bottom';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'caption_pos_' . $caption_pos . $style_atts_uid, 1 );

        // Caption width
        $caption_width = $res_ctx->get_shortcode_att( 'caption_width' );
        $caption_width .= is_numeric( $caption_width ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'caption_width' . $style_atts_uid, $caption_width );

        // Caption padding
        $caption_padd = $res_ctx->get_shortcode_att( 'caption_padd' );
        $caption_padd .= is_numeric( $caption_padd ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'caption_padd' . $style_atts_uid, $caption_padd );


        /* -- Style -- */
        $all_border_color = $res_ctx->get_shortcode_att( 'all_border_color' );
        $all_border_color = $all_border_color != '' ? $all_border_color : '#000';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'all_border_color' . $style_atts_uid, $all_border_color );
        if( $res_ctx->get_shortcode_att( 'active_highlight' ) == 'yes' ) {
            $all_border_a_color = $res_ctx->get_shortcode_att( 'all_border_a_color' );
            $all_border_a_color = $all_border_a_color != '' ? $all_border_a_color : ( $all_border_color != '' ? $all_border_color : '#000' );
            $res_ctx->load_settings_raw( $style_atts_prefix . 'all_border_a_color' . $style_atts_uid, $all_border_a_color );
        }

        $res_ctx->load_settings_raw( $style_atts_prefix . 'caption_bg' . $style_atts_uid, $res_ctx->get_shortcode_att( 'caption_bg' ) );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'caption_color' . $style_atts_uid, $res_ctx->get_shortcode_att( 'caption_color' ) );


        /* -- Fonts -- */
        $res_ctx->load_font_settings( 'f_caption', '', $style_atts_prefix, $style_atts_uid );



        /*-- NAVIGATION -- */
        /* -- Click on select. -- */
        $res_ctx->load_settings_raw( $style_atts_prefix . 'enable_focus_on_click' . $style_atts_uid, $res_ctx->get_shortcode_att( 'enable_focus_on_click' ) != '' );


        /* -- Arrows layout -- */
        // Position
        $arrows_pos = $res_ctx->get_shortcode_att('arrows_pos');
        $arrows_pos = $arrows_pos != '' ? $arrows_pos : 'inside';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'arrows_pos_' . $arrows_pos . $style_atts_uid, 1 );

        // Padding
        $arrows_padding = $res_ctx->get_shortcode_att('arrows_padding');
        $arrows_padding .= $arrows_padding != '' && is_numeric( $arrows_padding ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'arrows_padding' . $style_atts_uid, $arrows_padding );

        // Space
        $arrows_space = $res_ctx->get_shortcode_att('arrows_space');
        $arrows_space .= $arrows_space != '' && is_numeric( $arrows_space ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'arrows_space_' . $arrows_pos . $style_atts_uid, $arrows_space );

        // Border radius
        $arrows_radius = $res_ctx->get_shortcode_att('arrows_radius');
        $arrows_radius .= $arrows_radius != '' && is_numeric( $arrows_radius ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'arrows_radius' . $style_atts_uid, $arrows_radius );

        // Size
        $icon_size = $res_ctx->get_shortcode_att( 'icon_size' );
        $icon_size .= is_numeric( $icon_size ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_size' . $style_atts_uid, $icon_size );


        /* -- Arrows style -- */
        $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_color' . $style_atts_uid, $res_ctx->get_shortcode_att( 'icon_color' ) );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_color_h' . $style_atts_uid, $res_ctx->get_shortcode_att( 'icon_color_h' ) );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_bg' . $style_atts_uid, $res_ctx->get_shortcode_att( 'icon_bg' ) );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'icon_bg_h' . $style_atts_uid, $res_ctx->get_shortcode_att( 'icon_bg_h' ) );
        $res_ctx->load_shadow_settings( 3, 0, 0, 0, 'rgba(0, 0, 0, 0.3)', 'icons_shadow', '', true, $style_atts_prefix, $style_atts_uid );
        $res_ctx->load_shadow_settings( 0, 0, 0, 0, 'rgba(0, 0, 0, 0.3)', 'icons_bg_shadow', '', true, $style_atts_prefix, $style_atts_uid );


        
        /* -- Dots layout -- */
        // Position
        $dots_pos = $res_ctx->get_shortcode_att('dots_pos');
        $dots_pos = $dots_pos != '' ? $dots_pos : 'inside';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'dots_pos_' . $dots_pos . $style_atts_uid, 1 );

        // Size
        $dots_size = $res_ctx->get_shortcode_att( 'dots_size' );
        $dots_size .= is_numeric( $dots_size ) ? 'px' : '';
        $res_ctx->load_settings_raw( $style_atts_prefix . 'dots_size' . $style_atts_uid, $dots_size );


        /* -- Dots style -- */
        $res_ctx->load_settings_raw( $style_atts_prefix . 'dots_color' . $style_atts_uid, $res_ctx->get_shortcode_att( 'dots_color' ) );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'dots_color_h' . $style_atts_uid, $res_ctx->get_shortcode_att( 'dots_color_h' ) );
        $res_ctx->load_settings_raw( $style_atts_prefix . 'dots_color_a' . $style_atts_uid, $res_ctx->get_shortcode_att( 'dots_color_a' ) );
        $res_ctx->load_shadow_settings( 3, 0, 0, 0, 'rgba(0, 0, 0, 0.3)', 'dots_shadow', '', true , $style_atts_prefix, $style_atts_uid );

    }


    function render( $atts, $content = null ) {

        parent::render($atts); // sets the live atts, $this->atts, $this->block_uid, $this->td_query (it runs the query)

        $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();

        // Template type
        $template_type = tdb_state_template::get_template_type();


        // Gallery source
        $source = $this->get_att('source');


        // ACF field
        $acf_field = $this->get_att('acf_field');


        // Images size
        $images_size = $this->get_att('images_size');
        $images_size = $images_size != '' ? $images_size : 'td_1068x0';


        // Modal images
        $modal_images = $this->get_att('modal_imgs') != '';
        $modal_images_size = $this->get_att('modal_imgs_size');
        $modal_images_size = $modal_images_size != '' ? $modal_images_size : 'td_1920x0';




        /* --
        -- Slider options
        -- */
        /* -- As nav for -- */
        $nav_for = $this->get_att('nav_for');
        if( $nav_for != '' ) {
            $this->slider_options['asNavFor'] = '.' . $nav_for . ' .tdb-gallery-wrap';
        }


        /* -- Infinite -- */
        // Enable
        $this->slider_options['infinite'] = $this->get_att('infinite') != '';


        /* -- Autoplay -- */
        // Enable
        $this->slider_options['autoplay'] = $this->get_att('autoplay') != '';
        
        // Autoplay speed
        $this->slider_options['autoplaySpeed'] = $this->get_att('autoplay_speed') != '' && is_numeric( $this->get_att('autoplay_speed') ) ? intval($this->get_att('autoplay_speed')) : 3000;

        
        /* -- Navigation -- */
        if( !$in_composer ) {
            $this->slider_options['swipe'] = $this->get_att('enable_swipe') != '';
            $this->slider_options['focusOnSelect'] = $this->get_att('enable_focus_on_click') != '';
        }
        $this->slider_options['arrows'] = $this->get_att('enable_arrows') != '';
        $this->slider_options['dots'] = $this->get_att('enable_dots') != '';

        // Arrows
        $icon_prev = $this->get_icon_att( 'tdicon_prev' );
        if ( !empty( $icon_prev ) ) {
            if( base64_encode( base64_decode( $icon_prev ) ) == $icon_prev ) {
                $icon_prev_data = '';
                if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
                    $icon_prev_data = 'data-td-svg-icon="' . $this->get_att('tdicon_prev') . '"';
                }
                $this->slider_options['prevArrow'] = '<button type="button" class="slick-prev" ' . $icon_prev_data . '>' . base64_decode( $icon_prev ) . '</button>';
            } else {
                $this->slider_options['prevArrow'] = '<button type="button" class="slick-prev"><i class="' . $icon_prev . '"></i></button>';
            }
        }

        $icon_next = $this->get_icon_att( 'tdicon_next' );
        if ( !empty( $icon_next ) ) {
            if( base64_encode( base64_decode( $icon_next ) ) == $icon_next ) {
                $icon_next_data = '';
                if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
                    $icon_next_data = 'data-td-svg-icon="' . $this->get_att('tdicon_next') . '"';
                }
                $this->slider_options['nextArrow'] = '<button type="button" class="slick-next" ' . $icon_next_data . '>' . base64_decode( $icon_next ) . '</button>';
            } else {
                $this->slider_options['nextArrow'] = '<button type="button" class="slick-next"><i class="' . $icon_next . '"></i></button>';
            }
        }


        /* -- Responsive options -- */
        // Media screens
        $media_screens = array(
            '1141' => 'landscape',
            '1019' => 'portrait',
            '768' => 'phone'
        );

        // Slides per row
        $per_row = $this->get_att('per_row');
        $per_row_default = 1;

        if( td_util::is_base64($per_row) ) {
            $per_row_decoded = json_decode( base64_decode( $per_row ) );

            if( property_exists($per_row_decoded, 'all') ) {
                $per_row_default = intval($per_row_decoded->all);
            }

            foreach( $this->slider_options['responsive'] as &$responsive_setting ) {
                $media_screen = $media_screens[$responsive_setting['breakpoint']];

                if( property_exists($per_row_decoded, $media_screen) ) {
                    $responsive_setting['settings']['slidesToShow'] = intval($per_row_decoded->{$media_screen});
                } else {
                    $responsive_setting['settings']['slidesToShow'] = $per_row_default;
                }
            }
        } else {
            if( $per_row != '' && is_numeric($per_row) ) {
                $per_row_default = intval($per_row);

                $this->slider_options['responsive'] = array_map(function( $responsive_setting ) use ( $per_row_default ) {
                    $responsive_setting['settings']['slidesToShow'] = $per_row_default;

                    return $responsive_setting;
                }, $this->slider_options['responsive']);
            }
        }

        $this->slider_options['slidesToShow'] = $per_row_default;

        // Slides to scroll
        $slides_to_scroll = $this->get_att('slides_to_scroll');
        $slides_to_scroll_default = 1;

        if( td_util::is_base64($slides_to_scroll) ) {
            $slides_to_scroll_decoded = json_decode( base64_decode( $slides_to_scroll ) );

            if( property_exists($slides_to_scroll_decoded, 'all') ) {
                $slides_to_scroll_default = intval($slides_to_scroll_decoded->all);
            }

            foreach( $this->slider_options['responsive'] as &$responsive_setting ) {
                $media_screen = $media_screens[$responsive_setting['breakpoint']];

                if( property_exists($slides_to_scroll_decoded, $media_screen) ) {
                    $responsive_setting['settings']['slidesToScroll'] = intval($slides_to_scroll_decoded->{$media_screen});
                } else {
                    $responsive_setting['settings']['slidesToScroll'] = $slides_to_scroll_default;
                }
            }
        } else {
            $slides_to_scroll_default = $slides_to_scroll != '' && is_numeric($slides_to_scroll) ? intval($slides_to_scroll) : $slides_to_scroll_default;
        }

        $this->slider_options['slidesToScroll'] = $slides_to_scroll_default;




        /* --
        -- RETRIEVE THE GALLERY IMAGES
        -- */
        /* -- Retrieve the gallery images -- */
        $gallery_images = array();
        $block_error = '';

        if( $source == '' ) {

            $block_error = td_util::get_block_error(
                'Gallery',
                'Please select a source for the gallery.');

        } else {

            global $tdb_module_template_params;

            if( $tdb_module_template_params !== NULL ) {
                $post_obj = $tdb_module_template_params['post_obj'];

                // Create an array with dummy images
                $dummy_gallery_images = array(
                    array(
                        'id' => 1,
                        'alt' => '',
                        'title' => 'Sample gallery image 1',
                        'url' => TDB_URL . '/assets/images/td_meta_replacement.png',
                        'url_modal' => TDB_URL . '/assets/images/td_meta_replacement.png',
                        'caption' => 'Sample caption'
                    ),
                    array(
                        'id' => 2,
                        'alt' => '',
                        'title' => 'Sample gallery image 2',
                        'url' => TDB_URL . '/assets/images/td_meta_replacement.png',
                        'url_modal' => TDB_URL . '/assets/images/td_meta_replacement.png',
                        'caption' => 'Sample caption'
                    ),
                    array(
                        'id' => 3,
                        'alt' => '',
                        'title' => 'Sample gallery image 3',
                        'url' => TDB_URL . '/assets/images/td_meta_replacement.png',
                        'url_modal' => TDB_URL . '/assets/images/td_meta_replacement.png',
                        'caption' => 'Sample caption'
                    ),
                    array(
                        'id' => 4,
                        'alt' => '',
                        'title' => 'Sample gallery image 4',
                        'url' => TDB_URL . '/assets/images/td_meta_replacement.png',
                        'url_modal' => TDB_URL . '/assets/images/td_meta_replacement.png',
                        'caption' => 'Sample caption'
                    ),
                    array(
                        'id' => 5,
                        'alt' => '',
                        'title' => 'Sample gallery image 5',
                        'url' => TDB_URL . '/assets/images/td_meta_replacement.png',
                        'url_modal' => TDB_URL . '/assets/images/td_meta_replacement.png',
                        'caption' => 'Sample caption'
                    ),
                    array(
                        'id' => 6,
                        'alt' => '',
                        'title' => 'Sample gallery image 6',
                        'url' => TDB_URL . '/assets/images/td_meta_replacement.png',
                        'url_modal' => TDB_URL . '/assets/images/td_meta_replacement.png',
                        'caption' => 'Sample caption'
                    )
                );

                if ( gettype($post_obj) === 'object' && get_class($post_obj) === 'WP_Post' ) {
                    $post_id = $post_obj->ID;
                    $gallery_images_ids = array();

                    switch( $source ) {
                        case 'post_gallery':
                            $post_theme_settings_meta = td_util::get_post_meta_array( $post_id, 'td_post_theme_settings' );
                            $post_gallery_imgs_ids_meta = isset( $post_theme_settings_meta['td_gallery_imgs'] ) ? $post_theme_settings_meta['td_gallery_imgs'] : '';

                            if( !empty( $post_gallery_imgs_ids_meta ) ) {
                                $gallery_images_ids = explode(',', $post_gallery_imgs_ids_meta);
                            }

                            break;

                        case 'acf_field':
                            if( !class_exists( 'ACF' ) ) {
                                $block_error = td_util::get_block_error(
                                    'Gallery',
                                    'The Advanced Custom Fields (ACF) plugin is disabled.');
                            } else if( empty( $acf_field ) ) {
                                $block_error = td_util::get_block_error(
                                    'Gallery',
                                    'Please select an ACF field first.');
                            } else {
                                $field_data = td_util::get_acf_field_data( $acf_field, $post_id );

                                if( $field_data['meta_exists'] ) {
                                    foreach( $field_data['value'] as $image ) {
                                        if( is_array( $image ) ) {
                                            $gallery_images_ids[] = $image['ID'];
                                        } else if( is_numeric( $image ) ) {
                                            $gallery_images_ids[] = $image;
                                        } else if( is_string( $image ) ) {
                                            $img_id = attachment_url_to_postid($image);

                                            if( $img_id ) {
                                                $gallery_images_ids[] = $img_id;
                                            }
                                        }
                                    }
                                } else {
                                    if( metadata_exists('post', $post_id, $acf_field ) ) {
                                        $gallery_images_ids = get_post_meta( $post_id, $acf_field, true );
                                    }
                                }
                            }

                            break;
                    }

                    if( !empty( $gallery_images_ids ) ) {
                        foreach( $gallery_images_ids as $gallery_image_id ) {
                            if( empty( $gallery_image_id ) ) {
                                continue;
                            }

                            $img_info = get_post( $gallery_image_id );

                            if( $img_info ) {
                                $gallery_image = array(
                                    'id' => $img_info->ID,
                                    'alt' => get_post_meta($gallery_image_id, '_wp_attachment_image_alt', true),
                                    'title' => $img_info->post_title,
                                    'caption' => $img_info->post_excerpt,
                                );

                                // Get the image URL
                                if( td_util::get_option('tds_thumb_' . $images_size ) != 'yes' ) {
                                    // The thumb size is disabled, so show a placeholder thumb
                                    $thumb_disabled_path = td_global::$get_template_directory_uri;
                                    if ( strpos( $images_size, 'td_' ) === 0 ) {
                                        $thumb_disabled_path = td_api_thumb::get_key( $images_size, 'no_image_path' );
                                    }

                                    $gallery_image['url'] = $thumb_disabled_path . '/images/thumb-disabled/' . $images_size . '.png';
                                } else {
                                    // The thumbnail size is enabled in the panel, try to get the image
                                    $image_info = td_util::attachment_get_full_info( $gallery_image_id, $images_size );

                                    $gallery_image['url'] = $image_info['src'];
                                }

                                // Get the modal image URL
                                if( td_util::get_option('tds_thumb_' . $modal_images_size ) != 'yes' ) {
                                    // The thumb size is disabled, so show a placeholder thumb
                                    $thumb_disabled_path = td_global::$get_template_directory_uri;
                                    if ( strpos( $images_size, 'td_' ) === 0 ) {
                                        $thumb_disabled_path = td_api_thumb::get_key( $images_size, 'no_image_path' );
                                    }

                                    $gallery_image['url_modal'] = $thumb_disabled_path . '/images/thumb-disabled/' . $modal_images_size . '.png';
                                } else {
                                    // The thumbnail size is enabled in the panel, try to get the image
                                    $image_info = td_util::attachment_get_full_info( $gallery_image_id, $modal_images_size );

                                    $gallery_image['url_modal'] = $image_info['src'];
                                }

                                $gallery_images[] = $gallery_image;
                            }
                        }
                    }

                    if( empty( $gallery_images ) && ( tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax() ) ) {
                        // If we are in composer, display dummy data only if we
                        // are editing the actual module
                        if( tdb_state_template::get_template_type() == 'module' ) {
                            $gallery_images = $dummy_gallery_images;
                        }
                    }
                } else {
                    $gallery_image = $dummy_gallery_images;
                }
            } else {
                switch ( $source ) {
                    case 'post_gallery':
                        // Throw an error if the shortcode is not in a post/cpt template
                        if( $template_type != 'single' && $template_type != 'cpt' ) {
                            $block_error = td_util::get_block_error(
                                'Gallery',
                                '\'Post gallery\' was selected as the source, but the current template is not a post or CPT.');
                        } else {
                            global $tdb_state_single;
                            $gallery_images = $tdb_state_single->post_gallery->__invoke( $atts );
                        }

                        break;

                    case 'acf_field':
                        // Throw an error if the ACF plugin is disabled
                        if( !class_exists( 'ACF' ) ) {
                            $block_error = td_util::get_block_error(
                                'Gallery',
                                'The Advanced Custom Fields (ACF) plugin is disabled.');
                        } else if( empty( $acf_field ) ) {
                            $block_error = td_util::get_block_error(
                                'Gallery',
                                'Please select an ACF field first.');
                        } else {

                            global $tdb_state_single, $tdb_state_category, $tdb_state_tag, $tdb_state_author, $tdb_state_attachment, $tdb_state_single_page;

                            switch( $template_type ) {
                                case 'cpt':
                                case 'single':
                                    $gallery_images = $tdb_state_single->post_gallery->__invoke( $atts );
                                    break;
                                case 'category':
                                    $gallery_images = $tdb_state_category->category_gallery->__invoke( $atts );
                                    break;
                                case 'cpt_tax':

                                    if ( $tdb_state_category->is_cpt_post_type_archive() ) {

                                        $buffy = '<div class="' . $this->get_block_classes() . '" ' . $this->get_block_html_atts() . '>';
                                            $buffy .= '<div class="tdb-block-inner td-fix-index">';
                                                $buffy .= td_util::get_block_error(
                                                    'Gallery',
                                                    'This shortcode is not supported by this template.'
                                                );
                                            $buffy .= '</div>';
                                        $buffy .= '</div>';

                                        return $buffy;

                                    } else {
                                        $tdb_state_category->set_tax();
                                        $gallery_images = $tdb_state_category->category_gallery->__invoke( $atts );
                                    }

                                    break;
                                case 'tag':
                                    $gallery_images = $tdb_state_tag->tag_gallery->__invoke( $atts );
                                    break;
                                case 'author':
                                    $gallery_images = $tdb_state_author->author_gallery->__invoke( $atts );
                                    break;
                                case 'attachment':
                                    $gallery_images = $tdb_state_attachment->attachment_gallery->__invoke( $atts );
                                    break;
                                default:
                                    $gallery_images = $tdb_state_single_page->page_gallery->__invoke( $atts );
                                    break;
                            }

                        }

                        break;
                }
            }

        }



        /* --
        -- RENDER THE SHORTCODE
        -- */
        /* -- Additional classes -- */
        $additional_classes_array = array();
        $this->gallery_uid = $this->block_uid;

        // Check to see if the element is being called into a tdb module template
        if( td_global::get_in_tdb_module_template() ) {
            $additional_classes_array[] = get_class($this) . '_' . self::$module_template_part_index;
            $this->gallery_uid = get_class($this) . '_' . self::$module_template_part_index;
        }


        $buffy = '';

        if( empty( $gallery_images ) && empty( $block_error ) ) {
            return $buffy;
        }

        $buffy .= '<div class="' . $this->get_block_classes($additional_classes_array) . '" ' . $this->get_block_html_atts() . '>';

            //get the block css
            $buffy .= $this->get_block_css();

            //get the js for this block
            $buffy .= $this->get_block_js();


            $buffy .= '<div class="tdb-block-inner td-fix-index">';

                if( !empty( $block_error ) ) {
                    $buffy .= $block_error;
                } else if( !empty( $gallery_images ) ) {
                    $buffy .= '<div class="tdb-gallery-wrap">';
                        foreach( $gallery_images as $gallery_image ) {
                            $buffy .= '<div class="tdb-gallery-image">';
                                $buffy .= '<' . ( $modal_images ? 'a href="' . $gallery_image['url_modal'] . '"' : 'div' ) . ' class="tdb-gi-inner" ' . ( $modal_images && !empty( $gallery_image['caption'] ) ? 'data-caption="' . $gallery_image['caption'] . '"' : '' ) . '>';
                                $buffy .= '<img src="' . $gallery_image['url'] . '"' .
                                    ( !empty( $gallery_image['alt'] ) ? ' alt="' . $gallery_image['alt'] . '"' : '' ) .
                                    ( !empty( $gallery_image['title'] ) ? ' title="' . $gallery_image['title'] . '"' : '' ) .
                                    ( $modal_images ? ' class="td-modal-image"' : '' ) .
                                ' />';

                                if( !empty( $gallery_image['caption'] ) ) {
                                    $buffy .= '<figcaption class="tdb-gi-caption">' . $gallery_image['caption'] . '</figcaption>';
                                }
                                $buffy .= '</' . ( $modal_images ? 'a' : 'div' ) . '>';
                            $buffy .= '</div>';
                        }
                    $buffy .= '</div>';
                }

            $buffy .= '</div>';


            /* -- Load the SlickJS script only if it has not already -- */
            /* -- been loaded -- */
            $buffy .= td_resources_load::render_script('//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', 'tdb-slick-js');


            if(
                !self::$slick_options_loaded ||
                (
                    self::$slick_options_loaded &&
                    $in_composer
                ) ||
                !td_global::get_in_tdb_module_template()
            ) {
                /* -- Initialize SlickJS on the gallery -- */
                ob_start();
                ?>
                <script>
                    /* global jQuery:{} */
                    jQuery(document).ready(function () {

                        let $blockObj = jQuery('.<?php echo $this->gallery_uid ?>'),
                            $galleryWrap = $blockObj.find('.tdb-gallery-wrap'),

                            modalImages = <?php echo json_encode($modal_images) ?>;

                        if( $galleryWrap.length ) {
                            if( modalImages ) {
                                $galleryWrap.on('init', function(event, slick) {
                                    jQuery(slick.$slider).find('.tdb-gallery-image.slick-cloned a').removeClass('td-modal-image');
                                });
                            }

                            $galleryWrap.slick(<?php echo json_encode($this->slider_options) ?>);
                        }

                    });
                </script>
                <?php
                td_js_buffer::add_to_footer( "\n" . td_util::remove_script_tag( ob_get_clean() ) );

                self::$slick_options_loaded = true;
            }

        $buffy .= '</div>';

        return $buffy;

    }

    
    function js_tdc_callback_ajax() {
        $buffy = '';

        // add a new composer block - that one has the delete callback
        $buffy .= $this->js_tdc_get_composer_block();

        ob_start();

        ?>
        <script>
            /* global jQuery:{} */
            (function () {

                let applySlick_<?php echo $this->gallery_uid ?> = function() {
                    let $blockObj = jQuery('.<?php echo $this->gallery_uid ?>'),
                        $galleryWrap = $blockObj.find('.tdb-gallery-wrap');

                    if( $galleryWrap.length ) {
                        $galleryWrap.slick(<?php echo json_encode($this->slider_options) ?>);
                    }
                };

                if( jQuery.fn.slick === undefined ) {
                    jQuery.getScript('//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', function() {
                        applySlick_<?php echo $this->gallery_uid ?>();
                    });
                } else {
                    applySlick_<?php echo $this->gallery_uid ?>();
                }

            })();
        </script>
        <?php

        return $buffy . td_util::remove_script_tag( ob_get_clean() );
    }

}