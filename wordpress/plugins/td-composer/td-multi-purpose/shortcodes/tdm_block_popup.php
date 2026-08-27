<?php
class tdm_block_popup extends td_block {

    protected $shortcode_atts = array(); //the atts used for rendering the current block
    private $unique_block_class;

    public function get_custom_css() {
        // $unique_block_class - the unique class that is on the block. use this to target the specific instance via css
        $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();
        $in_element = td_global::get_in_element();
        $unique_block_class_prefix = '';
        if( $in_element || $in_composer ) {
            $unique_block_class_prefix = 'tdc-row .';

            if( $in_element && $in_composer ) {
                $unique_block_class_prefix = 'tdc-row-composer .';
            }
        }
        $unique_block_class = $unique_block_class_prefix . $this->block_uid;

        $unique_modal_id = $this->get_att( 'modal_id' );

        $compiled_css = '';

        $raw_css =
            "<style>
                
                /* @style_general_popup_modal */
                .tdm-popup-modal-prevent-scroll {
                    overflow: hidden;
                }
                .tdm-popup-modal-wrap,
                .tdm-popup-modal-bg {
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                }
                .tdm-popup-modal-wrap {
                    position: fixed;
                    display: flex;
                    z-index: 10002;
                }
                @media (min-width: 783px) {
                    .admin-bar .tdm-popup-modal-wrap {
                        padding-top: 32px;
                    }
                }
                @media (max-width: 782px) {
                    .admin-bar .tdm-popup-modal-wrap {
                        padding-top: 46px;
                    }
                }
                .tdm-popup-modal-bg {
                    position: absolute;
                }
                .tdm-popup-modal {
                    display: flex;
                    flex-direction: column;
                    position: relative;
                    background-color: #fff;
                    width: 700px;
                    max-width: 100%;
                    max-height: 100%;
                    border-radius: 3px;
                    overflow: hidden;
                    z-index: 1;
                }
                .tdm-pm-header {
                    display: flex;
                    align-items: center;
                    width: 100%;
                    padding: 19px 25px 16px;
                    z-index: 10;
                }
                .tdm-pmh-title {
                    margin: 0;
                    padding: 0;
                    font-size: 18px;
                    line-height: 1.2;
                    font-weight: 600;
                    color: #1D2327;
                }
                a.tdm-pmh-title:hover {
                    color: var(--td_theme_color, #4db2ec);
                }
                .tdm-pmh-close {
                    position: relative;
                    margin-left: auto;
                    font-size: 14px;
                    color: #878d93;
                    cursor: pointer;
                }
                .tdm-pmh-close * {
                    pointer-events: none; 
                }
                .tdm-pmh-close svg {
                    width: 14px;
                    fill: #878d93;
                }
                .tdm-pmh-close:hover {
                    color: #000;
                }
                .tdm-pmh-close:hover svg {
                    fill: #000;
                }
                .tdm-pm-body {
                    flex: 1;
                    padding: 30px 25px;
                    overflow: auto;
                    overflow-x: hidden;
                }
                .tdm-pm-body > p:empty {
                    display: none;
                }
                .tdm-pm-body .tdc-row:not([class*='stretch_row_']),
                .tdm-pm-body .tdc-row-composer:not([class*='stretch_row_'])  {
                    width: auto !important;
                    max-width: 1240px;
                }
                @media (min-width: 1141px) {
                    .tdm-pm-body .tdc-row:not([class*='stretch_row_']),
                    .tdm-pm-body .tdc-row-composer:not([class*='stretch_row_']) {
                        padding-left: 24px;
                        padding-right: 24px;
                    }
                }
                @media (min-width: 1019px) and (max-width: 1140px) {
                    .tdm-pm-body .tdc-row:not([class*='stretch_row_']),
                    .tdm-pm-body .tdc-row-composer:not([class*='stretch_row_']) {
                        padding-left: 20px;
                        padding-right: 20px;
                    }
                }
                @media (max-width: 767px) {
                    .tdm-pm-body .tdc-row:not([class*='stretch_row_']) {
                        padding-left: 20px;
                        padding-right: 20px;
                    }
                }
                .tdm-popup-modal-over-screen,
                .tdm-popup-modal-over-screen .tdm-pm-body .tdb_header_search .tdb-search-form,
                .tdm-popup-modal-over-screen .tdm-pm-body .tdb_header_logo .tdb-logo-a,
                .tdm-popup-modal-over-screen .tdm-pm-body .tdb_header_logo h1 {
                    pointer-events: none;
                }
                .tdm-popup-modal-over-screen .tdm-btn {
                    pointer-events: none !important;
                }
                .tdm-popup-modal-over-screen .tdm-popup-modal-bg {
                    opacity: 0;
                    transition: opacity .2s ease-in;
                }
                .tdm-popup-modal-over-screen.tdm-popup-modal-open .tdm-popup-modal-bg {
                    opacity: 1;
                    transition: opacity .2s ease-out;
                }
                .tdm-popup-modal-over-screen.tdm-popup-modal-open .tdm-popup-modal,
                .tdm-popup-modal-over-screen.tdm-popup-modal-open .tdm-pm-body .tdb_header_search .tdb-search-form,
                .tdm-popup-modal-over-screen.tdm-popup-modal-open .tdm-pm-body .tdb_header_logo .tdb-logo-a,
                .tdm-popup-modal-over-screen.tdm-popup-modal-open .tdm-pm-body .tdb_header_logo h1 {
                    pointer-events: auto; 
                }
                .tdm-popup-modal-over-screen.tdm-popup-modal-open .tdm-btn {
                    pointer-events: auto !important;
                }
                .tdm-popup-modal:hover .td-admin-edit {
                  display: block;
                  z-index: 11;
                }
                
                
                /* @style_general_popup_modal_composer */
                .tdm_block_popup .tdm-bp-no-btn-placeholder {
                    font-size: 13px;
                    font-weight: normal;
                    text-align: left;
                    padding: 20px;
                    border: 1px solid rgba(190, 190, 190, 0.35);
                    color: rgba(125, 125, 125, 0.8);
                }
                
                
                
                /* @btn_display_inline */
                body .$unique_block_class {
                    display: inline-block;
                }
                /* @btn_display_full */
                body .$unique_block_class .tdm-btn {
                    width: 100%;
                }
                
                /* @btn_float_right */
                body .$unique_block_class {
                    display: inline-block;
                    float: right;
                    clear: none;
                }
                
                /* @btn_align_horiz_center */
                body .$unique_block_class .tds-button {
                    text-align: center;
                }
                /* @btn_align_horiz_right */
                body .$unique_block_class .tds-button {
                    text-align: right;
                }
                /* @icon_align */
                body .$unique_block_class .tds-button .tdm-btn-icon {
                    position: relative;
                    top: @icon_align;
                }
                
                
                
                /* @modal_align_vert */
                #tdm-popup-modal-$unique_modal_id {
                    align-items: @modal_align_vert;
                }
                /* @modal_align_horiz */
                #tdm-popup-modal-$unique_modal_id {
                    justify-content: @modal_align_horiz;
                }
                
                /* @modal_transition_def */
                #tdm-popup-modal-$unique_modal_id.tdm-popup-modal-over-screen .tdm-popup-modal {
                    opacity: 0;
                    transform: scale(.95);
                    transition: opacity .2s ease-in, transform .2s ease-in;
                }
                #tdm-popup-modal-$unique_modal_id.tdm-popup-modal-over-screen.tdm-popup-modal-open .tdm-popup-modal {
                    opacity: 1;
                    transform: scale(1);
                    transition: opacity .2s ease-out, transform .2s ease-out;
                }
                
                /* @modal_transition_slide_from_top */
                #tdm-popup-modal-$unique_modal_id.tdm-popup-modal-over-screen .tdm-popup-modal {
                    opacity: 0;
                    transform: translateY(calc(-100% - 1px));
                    transition: opacity .2s ease-in, transform .2s ease-in;
                }
                #tdm-popup-modal-$unique_modal_id.tdm-popup-modal-over-screen.tdm-popup-modal-open .tdm-popup-modal {
                    opacity: 1;
                    transform: translateY(0);
                    transition: opacity .2s ease-in, transform .2s ease-out;
                }
                /* @modal_transition_slide_from_top_center */
                #tdm-popup-modal-$unique_modal_id.tdm-popup-modal-over-screen .tdm-popup-modal {
                    opacity: 0;
                    transform: translateY(calc(-100% - 1px));
                    transition: opacity .2s ease-in, transform .2s ease-in;
                }
                #tdm-popup-modal-$unique_modal_id.tdm-popup-modal-over-screen.tdm-popup-modal-open .tdm-popup-modal {
                    opacity: 1;
                    transform: translateY(0);
                    transition: opacity .2s ease-out, transform .2s ease-out;
                }
                /* @modal_transition_slide_from_bottom */
                #tdm-popup-modal-$unique_modal_id.tdm-popup-modal-over-screen .tdm-popup-modal {
                    opacity: 0;
                    transform: translateY(calc(100% + 1px));
                    transition: opacity .2s ease-in,  transform .2s ease-in;
                }
                #tdm-popup-modal-$unique_modal_id.tdm-popup-modal-over-screen.tdm-popup-modal-open .tdm-popup-modal {
                    opacity: 1;
                    transform: translateY(0);
                    transition: opacity .2s ease-out, transform .2s ease-out;
                }
                /* @modal_transition_slide_from_left */
                #tdm-popup-modal-$unique_modal_id.tdm-popup-modal-over-screen .tdm-popup-modal {
                    opacity: 0;
                    transform: translateX(calc(-100% - 1px));
                    transition: opacity .2s ease-in,  transform .2s ease-in;
                }
                #tdm-popup-modal-$unique_modal_id.tdm-popup-modal-over-screen.tdm-popup-modal-open .tdm-popup-modal {
                    opacity: 1;
                    transform: translateX(0);
                    transition: opacity .2s ease-out,  transform .2s ease-out;
                }
                /* @modal_transition_slide_from_right */
                #tdm-popup-modal-$unique_modal_id.tdm-popup-modal-over-screen .tdm-popup-modal {
                    opacity: 0;
                    transform: translateX(calc(100% + 1px));
                    transition: opacity .2s ease-in, transform .2s ease-in;
                }
                #tdm-popup-modal-$unique_modal_id.tdm-popup-modal-over-screen.tdm-popup-modal-open .tdm-popup-modal {
                    opacity: 1;
                    transform: translateX(0);
                    transition: opacity .2s ease-out, transform .2s ease-out;
                }
                
                /* @modal_transition_none */
                #tdm-popup-modal-$unique_modal_id.tdm-popup-modal-over-screen .tdm-popup-modal {
                    opacity: 0;
                }
                #tdm-popup-modal-$unique_modal_id.tdm-popup-modal-over-screen.tdm-popup-modal-open .tdm-popup-modal {
                    opacity: 1;
                }
                
                /* @modal_space */
                #tdm-popup-modal-$unique_modal_id .tdm-popup-modal {
                    margin: @modal_space;
                }
                /* @modal_width */
                #tdm-popup-modal-$unique_modal_id .tdm-popup-modal {
                    width: @modal_width;
                }
                /* @modal_height */
                #tdm-popup-modal-$unique_modal_id .tdm-popup-modal {
                    height: @modal_height;
                }
                /* @all_modal_border */
                #tdm-popup-modal-$unique_modal_id .tdm-popup-modal {
                    border-width: @all_modal_border;
                    border-style:  @all_modal_border_style;
                    border-color: @all_modal_border_color;
                }
                /* @modal_border_radius */
                #tdm-popup-modal-$unique_modal_id .tdm-popup-modal {
                    border-radius: @modal_border_radius;
                }
                /* @modal_shadow */
                #tdm-popup-modal-$unique_modal_id .tdm-popup-modal {
                    box-shadow: @modal_shadow;
                }
                
                /* @show_header */
                #tdm-popup-modal-$unique_modal_id .tdm-pm-header {
                    display: @show_header;
                }
                /* @header_pos */
                #tdm-popup-modal-$unique_modal_id .tdm-pm-header {
                    position: absolute;
                    top: 0;
                    left: 0;
                    pointer-events: none;
                }
                #tdm-popup-modal-$unique_modal_id.tdm-popup-modal-over-screen.tdm-popup-modal-open .tdm-pmh-close {
                    pointer-events: auto;
                }
                /* @head_icon_size */
                #tdm-popup-modal-$unique_modal_id .tdm-pmh-close {
                    font-size: @head_icon_size;
                }
                #tdm-popup-modal-$unique_modal_id .tdm-pmh-close svg {
                    width: @head_icon_size;
                }
                /* @head_icon_align */
                #tdm-popup-modal-$unique_modal_id .tdm-pmh-close {
                    top: @head_icon_align;
                }
                /* @head_padd */
                #tdm-popup-modal-$unique_modal_id .tdm-pm-header {
                    padding: @head_padd;
                }
                /* @all_head_border */
                #tdm-popup-modal-$unique_modal_id .tdm-pm-header {
                    border-width: @all_head_border;
                    border-style: @all_head_border_style;
                    border-color: @all_head_border_color;
                }
                
                /* @body_padd */
                #tdm-popup-modal-$unique_modal_id .tdm-pm-body {
                    padding: @body_padd;
                }
                
                
                /* @overlay_bg_solid */
                #tdm-popup-modal-$unique_modal_id.tdm-popup-modal-open {
                    pointer-events: auto;
                }
                #tdm-popup-modal-$unique_modal_id .tdm-popup-modal-bg {
                    background: @overlay_bg_solid;
                }
                /* @overlay_bg_gradient */
                #tdm-popup-modal-$unique_modal_id.tdm-popup-modal-open {
                    pointer-events: auto;
                }
                #tdm-popup-modal-$unique_modal_id .tdm-popup-modal-bg {
                    @overlay_bg_gradient
                }
                
                /* @modal_bg */
                #tdm-popup-modal-$unique_modal_id .tdm-popup-modal {
                    background-color: @modal_bg;
                }
                
                /* @head_title_color */
                #tdm-popup-modal-$unique_modal_id .tdm-pmh-title {
                    color: @head_title_color;
                }
                
                /* @head_title_url_color */
                #tdm-popup-modal-$unique_modal_id a.tdm-pmh-title {
                    color: @head_title_url_color;
                }
                /* @head_title_url_color_h */
                #tdm-popup-modal-$unique_modal_id a.tdm-pmh-title:hover {
                    color: @head_title_url_color_h;
                }
                
                /* @head_icon_color */
                #tdm-popup-modal-$unique_modal_id .tdm-pmh-close {
                    color: @head_icon_color;
                }
                #tdm-popup-modal-$unique_modal_id .tdm-pmh-close svg {
                    fill: @head_icon_color;
                }
                /* @head_icon_color_h */
                #tdm-popup-modal-$unique_modal_id .tdm-pmh-close:hover {
                    color: @head_icon_color_h;
                }
                #tdm-popup-modal-$unique_modal_id .tdm-pmh-close:hover svg {
                    fill: @head_icon_color_h;
                }
                /* @head_bg */
                #tdm-popup-modal-$unique_modal_id .tdm-pm-header {
                    background-color: @head_bg;
                }
                
                /* @f_head */
                #tdm-popup-modal-$unique_modal_id .tdm-pmh-title {
                    @f_head
                }

                /* @f_body */
                #tdm-popup-modal-$unique_modal_id .tdm-pm-body {
                    @f_body
                }

                /* @body_cst_modal_bg */
                #tdm-popup-modal-$unique_modal_id .tdm-pm-body{
                    background: @body_cst_modal_bg;
                }

                /* @body_cst_modal_bg_h */
                #tdm-popup-modal-$unique_modal_id .tdm-pm-body:hover{
                    background: @body_cst_modal_bg_h;
                }

                /* @text_cst_modal */
                #tdm-popup-modal-$unique_modal_id .tdm-pm-body{
                    color: @text_cst_modal;
                }

                /* @text_cst_modal_h */
                #tdm-popup-modal-$unique_modal_id .tdm-pm-body:hover{
                    color: @text_cst_modal_h;
                }
				
			</style>";


        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts() );

        $compiled_css .= $td_css_res_compiler->compile_css();
        return $compiled_css;
    }

    static function cssMedia( $res_ctx ) {

        $res_ctx->load_settings_raw( 'style_general_popup_modal', 1 );

        if( tdc_state::is_live_editor_ajax() || tdc_state::is_live_editor_iframe() ) {
            $res_ctx->load_settings_raw( 'style_general_popup_modal_composer', 1 );
        }

        /*-- LAYOUT -- */
        // button display
        $btn_align_horiz = $res_ctx->get_shortcode_att('btn_display');
        if( $btn_align_horiz == 'inline' ) {
            $res_ctx->load_settings_raw('btn_display_inline', 1);
        } else if ( $btn_align_horiz == 'full' ) {
            $res_ctx->load_settings_raw('btn_display_full', 1);
        }

        // button align right
        $res_ctx->load_settings_raw('btn_float_right', $res_ctx->get_shortcode_att('btn_float_right'));

        // button horizontal align
        $btn_align_horiz = $res_ctx->get_shortcode_att('btn_align_horiz');
        if( $btn_align_horiz == 'content-horiz-center' ) {
            $res_ctx->load_settings_raw('btn_align_horiz_center', 1);
        } else if( $btn_align_horiz == 'content-horiz-right' ) {
            $res_ctx->load_settings_raw('btn_align_horiz_right', 1);
        }

        // button icon align
        $icon_align = $res_ctx->get_shortcode_att( 'icon_align' );
        if ( $icon_align != '0' ) {
            $res_ctx->load_settings_raw( 'icon_align', $icon_align . 'px');
        }

        // modal align & transition effect
        $modal_align = $res_ctx->get_shortcode_att( 'modal_align' );
        $modal_transition_effect = $res_ctx->get_shortcode_att( 'modal_transition' );
        switch ( $modal_transition_effect ) {
            case '':
                $res_ctx->load_settings_raw( 'modal_transition_def', 1 );
                break;
            case 'none':
                $res_ctx->load_settings_raw( 'modal_transition_none', 1 );
                break;
        }

        switch ( $modal_align ) {
            case '':
            case 'top-left':
                $res_ctx->load_settings_raw( 'modal_align_vert', 'flex-start' );
                $res_ctx->load_settings_raw( 'modal_align_horiz', 'flex-start' );

                if( $modal_transition_effect == 'slide' ) {
                    $res_ctx->load_settings_raw( 'modal_transition_slide_from_left', 1 );
                }

                break;
            case 'top-center':
                $res_ctx->load_settings_raw( 'modal_align_vert', 'flex-start' );
                $res_ctx->load_settings_raw( 'modal_align_horiz', 'center' );

                if( $modal_transition_effect == 'slide' ) {
                    $res_ctx->load_settings_raw( 'modal_transition_slide_from_top', 1 );
                }

                break;
            case 'top-right':
                $res_ctx->load_settings_raw( 'modal_align_vert', 'flex-start' );
                $res_ctx->load_settings_raw( 'modal_align_horiz', 'flex-end' );

                if( $modal_transition_effect == 'slide' ) {
                    $res_ctx->load_settings_raw( 'modal_transition_slide_from_right', 1 );
                }

                break;
            case 'center-left':
                $res_ctx->load_settings_raw( 'modal_align_vert', 'center' );
                $res_ctx->load_settings_raw( 'modal_align_horiz', 'flex-start' );

                if( $modal_transition_effect == 'slide' ) {
                    $res_ctx->load_settings_raw( 'modal_transition_slide_from_left', 1 );
                }

                break;
            case 'center-center':
                $res_ctx->load_settings_raw( 'modal_align_vert', 'center' );
                $res_ctx->load_settings_raw( 'modal_align_horiz', 'center' );

                if( $modal_transition_effect == 'slide' ) {
                    $res_ctx->load_settings_raw( 'modal_transition_slide_from_top_center', 1 );
                }

                break;
            case 'center-right':
                $res_ctx->load_settings_raw( 'modal_align_vert', 'center' );
                $res_ctx->load_settings_raw( 'modal_align_horiz', 'flex-end' );

                if( $modal_transition_effect == 'slide' ) {
                    $res_ctx->load_settings_raw( 'modal_transition_slide_from_right', 1 );
                }

                break;
            case 'bottom-left':
                $res_ctx->load_settings_raw( 'modal_align_vert', 'flex-end' );
                $res_ctx->load_settings_raw( 'modal_align_horiz', 'flex-start' );

                if( $modal_transition_effect == 'slide' ) {
                    $res_ctx->load_settings_raw( 'modal_transition_slide_from_left', 1 );
                }

                break;
            case 'bottom-center':
                $res_ctx->load_settings_raw( 'modal_align_vert', 'flex-end' );
                $res_ctx->load_settings_raw( 'modal_align_horiz', 'center' );

                if( $modal_transition_effect == 'slide' ) {
                    $res_ctx->load_settings_raw( 'modal_transition_slide_from_bottom', 1 );
                }

                break;
            case 'bottom-right':
                $res_ctx->load_settings_raw( 'modal_align_vert', 'flex-end' );
                $res_ctx->load_settings_raw( 'modal_align_horiz', 'flex-end' );

                if( $modal_transition_effect == 'slide' ) {
                    $res_ctx->load_settings_raw( 'modal_transition_slide_from_right', 1 );
                }

                break;
        }

        // modal space
        $modal_space = $res_ctx->get_shortcode_att( 'modal_space' );
        $res_ctx->load_settings_raw( 'modal_space', $modal_space );
        if( $modal_space != '' && is_numeric( $modal_space ) ) {
            $res_ctx->load_settings_raw( 'modal_space', $modal_space . 'px' );
        }

        // modal width
        $modal_width = $res_ctx->get_shortcode_att( 'modal_width' );
        $res_ctx->load_settings_raw( 'modal_width', $modal_width );
        if( $modal_width != '' && is_numeric( $modal_width ) ) {
            $res_ctx->load_settings_raw( 'modal_width', $modal_width . 'px' );
        }

        // modal height
        $modal_height = $res_ctx->get_shortcode_att( 'modal_height' );
        $res_ctx->load_settings_raw( 'modal_height', $modal_height );
        if( $modal_height != '' && is_numeric( $modal_height ) ) {
            $res_ctx->load_settings_raw( 'modal_height', $modal_height . 'px' );
        }

        // modal border size
        $all_modal_border = $res_ctx->get_shortcode_att( 'all_modal_border' );
        $res_ctx->load_settings_raw( 'all_modal_border', $all_modal_border );
        if( $all_modal_border != '' && is_numeric( $all_modal_border ) ) {
            $res_ctx->load_settings_raw( 'all_modal_border', $all_modal_border . 'px' );
        }
        // modal border style
        $all_modal_border_style = $res_ctx->get_shortcode_att( 'all_modal_border_style' );
        $res_ctx->load_settings_raw( 'all_modal_border_style', 'solid' );
        if( $all_modal_border_style != '' ) {
            $res_ctx->load_settings_raw( 'all_modal_border_style', $all_modal_border_style );
        }
        // modal border radius
        $modal_border_radius = $res_ctx->get_shortcode_att( 'modal_border_radius' );
        $res_ctx->load_settings_raw( 'modal_border_radius', $modal_border_radius );
        if( $modal_border_radius != '' && is_numeric( $modal_border_radius ) ) {
            $res_ctx->load_settings_raw( 'modal_border_radius', $modal_border_radius . 'px' );
        }

        // show header
        $show_header = $res_ctx->get_shortcode_att( 'show_header' );
        $show_header = $show_header != '' ? $show_header : 'flex';
        $res_ctx->load_settings_raw( 'show_header', $show_header );

        // header position
        $res_ctx->load_settings_raw( 'header_pos', $res_ctx->get_shortcode_att( 'header_pos' ) );

        // header icon size
        $head_icon_size = $res_ctx->get_shortcode_att( 'head_icon_size' );
        $res_ctx->load_settings_raw( 'head_icon_size', $head_icon_size );
        if( $head_icon_size != '' && is_numeric( $head_icon_size ) ) {
            $res_ctx->load_settings_raw( 'head_icon_size', $head_icon_size . 'px' );
        }

        // header icon align
        $res_ctx->load_settings_raw( 'head_icon_align', $res_ctx->get_shortcode_att( 'head_icon_align' ) . 'px' );

        // header padding
        $head_padd = $res_ctx->get_shortcode_att( 'head_padd' );
        $res_ctx->load_settings_raw( 'head_padd', $head_padd );
        if( $head_padd != '' && is_numeric( $head_padd ) ) {
            $res_ctx->load_settings_raw( 'head_padd', $head_padd . 'px' );
        }

        // header border size
        $all_head_border = $res_ctx->get_shortcode_att( 'all_head_border' );
        $res_ctx->load_settings_raw( 'all_head_border', '1px' );
        if( $all_head_border != '' ) {
            $res_ctx->load_settings_raw( 'all_head_border', $all_head_border );

            if( is_numeric( $all_head_border ) ) {
                $res_ctx->load_settings_raw( 'all_head_border', $all_head_border . 'px' );
            }
        }
        // modal border style
        $all_head_border_style = $res_ctx->get_shortcode_att( 'all_head_border_style' );
        $res_ctx->load_settings_raw( 'all_head_border_style', 'solid' );
        if( $all_head_border_style != '' ) {
            $res_ctx->load_settings_raw( 'all_head_border_style', $all_head_border_style );
        }

        // content padding
        $body_padd = $res_ctx->get_shortcode_att( 'body_padd' );
        $res_ctx->load_settings_raw( 'body_padd', $body_padd );
        if( $body_padd != '' && is_numeric( $body_padd ) ) {
            $res_ctx->load_settings_raw( 'body_padd', $body_padd . 'px' );
        }

        /*-- STYLE -- */
        $res_ctx->load_color_settings( 'overlay_bg', 'overlay_bg_solid', 'overlay_bg_gradient' );
        $res_ctx->load_settings_raw( 'modal_bg', $res_ctx->get_shortcode_att( 'modal_bg' ) );
        $all_modal_border_color = $res_ctx->get_shortcode_att( 'all_modal_border_color' );
        $res_ctx->load_settings_raw( 'all_modal_border_color', '#000' );
        if( $all_modal_border_color != '' ) {
            $res_ctx->load_settings_raw( 'all_modal_border_color', $all_modal_border_color );
        }
        $res_ctx->load_shadow_settings( 4, 0, 2, 0, 'rgba(0, 0, 0, 0.2)', 'modal_shadow' );

        $res_ctx->load_settings_raw( 'head_title_color', $res_ctx->get_shortcode_att( 'head_title_color' ) );
        $res_ctx->load_settings_raw( 'head_title_url_color', $res_ctx->get_shortcode_att( 'head_title_url_color' ) );
        $res_ctx->load_settings_raw( 'head_title_url_color_h', $res_ctx->get_shortcode_att( 'head_title_url_color_h' ) );
        $res_ctx->load_settings_raw( 'head_icon_color', $res_ctx->get_shortcode_att( 'head_icon_color' ) );
        $res_ctx->load_settings_raw( 'head_icon_color_h', $res_ctx->get_shortcode_att( 'head_icon_color_h' ) );
        $res_ctx->load_settings_raw( 'head_bg', $res_ctx->get_shortcode_att( 'head_bg' ) );
        $all_head_border_color = $res_ctx->get_shortcode_att( 'all_head_border_color' );
        $res_ctx->load_settings_raw( 'all_head_border_color', '#EBEBEB' );
        if( $all_head_border_color != '' ) {
            $res_ctx->load_settings_raw( 'all_head_border_color', $all_head_border_color );
        }

        /*-- BACKGROUND COLLOR & HOVER BACKGROUND COLLOR MODAL CONTENT-- */
        $res_ctx->load_settings_raw( 'body_cst_modal_bg', $res_ctx->get_shortcode_att( 'body_cst_modal_bg' ) );
        $res_ctx->load_settings_raw( 'body_cst_modal_bg_h', $res_ctx->get_shortcode_att( 'body_cst_modal_bg_h' ) );

        /*-- TEXT COLOR  & HOVER TEXT COLOR MODAL CONTENT -- */
        $res_ctx->load_settings_raw( 'text_cst_modal', $res_ctx->get_shortcode_att( 'text_cst_modal' ) );
        $res_ctx->load_settings_raw( 'text_cst_modal_h', $res_ctx->get_shortcode_att( 'text_cst_modal_h' ) );

        /*-- FONTS -- */
        $res_ctx->load_font_settings( 'f_head' );
        $res_ctx->load_font_settings( 'f_body' );

    }

    /**
     * Disable loop block features. This block does not use a loop and it dosn't need to run a query.
     */
    function __construct() {
        parent::disable_loop_block_features();
    }

    function render( $atts, $content = null ) {
        parent::render($atts);

        // $unique_block_class - the unique class that is on the block. use this to target the specific instance via css
        $this->unique_block_class = $this->block_uid;

        $this->shortcode_atts = shortcode_atts(
			array_merge(
				td_api_multi_purpose::get_mapped_atts( __CLASS__ ),
                td_api_style::get_style_group_params( 'tds_button' ))
			, $atts);

        // in composer flag
        $in_composer = tdc_state::is_live_editor_ajax() || tdc_state::is_live_editor_iframe();

        // in modal flag
        $in_modal = td_global::get_in_tdm_block_popup();

        // modal unique ID
        $modal_id = $this->get_att('modal_id');

        // modal opening position
        $open_position = $this->get_att('open_position') == '' ? 'over-screen' : $this->get_att('open_position');
        $open_position_class = 'tdm-popup-modal-' . $open_position;

        // close modals unique IDs
        $close_modals = !empty( $this->get_att('close_modals') ) ? explode( ',', $this->get_att('close_modals') ) : array();
        $close_modals = array_map('trim', $close_modals);

        // content source
        $content_source = $this->get_att( 'content_source' );

	    // content load type ( delayed or preloaded, default > preloaded )
	    $content_load = $this->get_att( 'content_load' );

        // page id
        $page_id = $this->get_att('page_id');

        // modal triggers
        $modal_triggers = array();
        $trigg_btn_enable = $this->get_att( 'trigg_btn_enable' );
        $trigg_page_load_enable = $this->get_att( 'trigg_page_load_enable' );
        $trigg_cursor_area_enable = $this->get_att( 'trigg_cursor_area_enable' );
        $trigg_scroll_enable = $this->get_att( 'trigg_scroll_enable' );

        if ( $trigg_btn_enable != '' ) {
            $modal_triggers['button'] = array();
        }
        if ( $trigg_page_load_enable != '' ) {
            $modal_triggers['page_load'] = array(
                'modal_open_when_others_close' => $this->get_att('trigg_page_open_when_others_close') != '',
                'modal_open_delay' => 1000,
                'modal_close_after' => 0,
                'modal_prevent_open' => 0
            );

            // modal open delay
            if ( $this->get_att('modal_open_delay') != '' && is_numeric( $this->get_att('modal_open_delay') ) ) {
                $modal_triggers['page_load']['modal_open_delay'] = $this->get_att('modal_open_delay') * 1000;
            }

            // modal close after
            if ( $this->get_att('modal_close_after') != '' && is_numeric( $this->get_att('modal_close_after') ) ) {
                $modal_triggers['page_load']['modal_close_after'] = $this->get_att('modal_close_after') * 1000;
            }

            // modal open again after
            if ( $this->get_att('modal_prevent_open') != '' && is_numeric( $this->get_att('modal_prevent_open') ) ) {
                $modal_triggers['page_load']['modal_prevent_open'] = $this->get_att('modal_prevent_open') * 60 * 60 * 1000;
            }
        }
        if ( $trigg_cursor_area_enable != '' ) {
            $modal_triggers['cursor_area'] = array(
                'area_vertical_space' => $this->get_att( 'area_vertical_space' ),
                'modal_area_prevent_open' => 0
            );
            $modal_area_prevent_open = $this->get_att('modal_area_prevent_open');
            if ( '' !== $modal_area_prevent_open && is_numeric( $modal_area_prevent_open ) ) {
                $modal_triggers['cursor_area']['modal_area_prevent_open'] = $modal_area_prevent_open * 60 * 60 * 1000;
            }
        }
        if ( $trigg_scroll_enable != '' ) {
            $modal_triggers['scroll'] = array(
                'scroll_distance' => $this->get_att( 'scroll_distance' ),
                'modal_scroll_prevent_open' => 0
            );
            $modal_scroll_prevent_open = trim($this->get_att('modal_scroll_prevent_open'));
            if ( !empty($modal_scroll_prevent_open) && is_numeric( $modal_scroll_prevent_open ) ) {
                $modal_triggers['scroll']['modal_scroll_prevent_open'] = $modal_scroll_prevent_open * 60 * 60 * 1000;
            }
        }

        // disable site scroll on modal open
        $site_scroll = true;
        if( $this->get_att('site_scroll') != '' ) {
            $site_scroll = false;
        }

        // modal header title
        $modal_head_title = $this->get_att( 'head_title' );
        $modal_head_title_url = $this->get_att( 'head_title_url' );
        $url_target = $this->get_att( 'head_title_url_target' ) !== '' ? 'target="_blank"' : '';


        // modal close icon
        $modal_close_icon = $this->get_icon_att( 'head_tdicon' );
        $modal_close_icon_data = '';
        if ( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $modal_close_icon_data = 'data-td-svg-icon="' . $this->get_att('head_tdicon') . '"';
        }
        $buffy_modal_close_icon = '<i class="td-icon-modal-close"></i>';
        if ( !empty( $modal_close_icon ) ) {
            if ( base64_encode( base64_decode( $modal_close_icon ) ) == $modal_close_icon ) {
                $buffy_modal_close_icon = base64_decode( $modal_close_icon );
            } else {
                $buffy_modal_close_icon = '<i class="' . $modal_close_icon . '"></i>';
            }
        }

	    // source modal id
	    $source_modal_id = $this->get_att( 'source_modal_id' );


        $buffy = '';

        // display restrictions
        $hide_for_user_type = $this->get_shortcode_att( 'hide_for_user_type' );

        if ( $hide_for_user_type != '' ) {
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

            if ( !td_util::plan_limit($author_plan_ids, $all_users_plan_ids) ) {
                return $buffy;
            }
        }

        $buffy .= '<div class="tdm_block ' . $this->get_wrapper_class() . ' ' . $this->get_block_classes() . '" ' . $this->get_block_html_atts() . '>';
            // get the block css
            $buffy .= $this->get_block_css();

            // get the js for this block
            $buffy .= $this->get_block_js();

            // get button
            if( $trigg_btn_enable != '' ) {
                if ( $content_source == 'modal_id' && empty($source_modal_id) ) {
                    // don't show the btn in this case, we will display a block error
                } else {
	                $tds_button = $this->get_shortcode_att('tds_button');
	                if ( empty( $tds_button ) ) {
		                $tds_button = td_util::get_option( 'tds_button', 'tds_button1' );
	                }
	                $tds_button_instance = new $tds_button( $this->shortcode_atts, '', $this->unique_block_class );
	                $buffy .= $tds_button_instance->render();
                }
            } else {
                if ( tdc_state::is_live_editor_ajax() || tdc_state::is_live_editor_iframe() ) {
                    if ( $content_source !== 'modal_id' ) {
	                    $buffy .= '<div class="tdm-bp-no-btn-placeholder">Popup Modal</div>';
                    }
                }
            }

            // display the modal html
            if ( $content_source == 'modal_id' ) { // modal id content
                if ( !empty($source_modal_id) ) {
                    $modal_id = $source_modal_id;

                    if ( $trigg_btn_enable === '' ) {
                        if ( is_user_logged_in() ) {
                            $buffy .= td_util::get_block_error(
                                'Popup modal',
                                'Modal id source can only be used with button trigger. Please enable and configure BUTTON in Triggers settings tab.'
                            );
                        }
                    }
                } else {
                    if ( is_user_logged_in() ) {
                        $buffy .= td_util::get_block_error(
                            'Popup modal',
                            'You have not set a modal ID in order to display its contents. Please set the Modal ID in MODAL CONTENT settings.'
                        );
                    }
                }
            } else {
                // modal content
                $modal_body_content = '';
                $is_page = false;
                if ( ( $content_source == '' || $content_source == 'page' ) && !empty($page_id) ) {
                    $page = get_post($page_id);

                    if ( null !== $page ) {
                        // edit with composer just for pages
                        $is_page = $page->post_type == 'page';

                        // don't render delayed page content
                        if ( $content_load !== 'ui_delayed' ) {
                            if ( !td_global::get_in_menu() && !$in_modal ) {
                                td_global::set_in_element(true);
                            }
                            td_global::set_in_tdm_block_popup( true );

                            $modal_body_content = $page->post_content;

                            if ( is_plugin_active('td-subscription/td-subscription.php') && has_filter('the_content', array( tds_email_locker::instance(), 'lock_content' ) ) ) {
                                $has_content_filter = true;
                                remove_filter( 'the_content', array( tds_email_locker::instance(), 'lock_content' ) );
                            }

                            // If the Events Manager plugin is active, remove 'the_content' specific filters,
                            // which might cause some issues while rendering the page content of the modal.
                            if( defined( 'EM_VERSION' ) ) {
                                remove_filter('the_content', 'em_content');
                                remove_filter('the_content', array('EM_Event_Post', 'the_content'));
                                remove_filter('the_content', array('EM_Location_Post', 'the_content'));
                                remove_filter('the_content', array('EM_Taxonomy_Frontend', 'the_content'));
                                remove_filter('the_content', array('EM_Categories_Frontend', 'the_content'));
                                remove_filter('the_content', array('EM_Tags_Frontend', 'the_content'));
                            }
                            $modal_body_content = apply_filters('the_content', $modal_body_content);
                            // If the Events Manager plugin is active, re-add 'the_content' specific filters.
                            if( defined( 'EM_VERSION' ) ) {
                                add_filter('the_content', 'em_content');
                                add_filter('the_content', array('EM_Event_Post', 'the_content'));
                                add_filter('the_content', array('EM_Location_Post', 'the_content'));
                                add_filter('the_content', array('EM_Taxonomy_Frontend', 'the_content'));
                                add_filter('the_content', array('EM_Categories_Frontend', 'the_content'));
                                add_filter('the_content', array('EM_Tags_Frontend', 'the_content'));
                            }
                            $modal_body_content = str_replace(']]>', ']]&gt;', $modal_body_content);

                            // the has_filter check is made for plugins, like bbpress, who think it's okay to remove all filters on 'the_content'
                            if ( !has_filter( 'the_content', 'do_shortcode' ) ) {
                                $modal_body_content = do_shortcode( $modal_body_content );
                            }

                            if ( !empty($has_content_filter) ) {
                                add_filter( 'the_content', array( tds_email_locker::instance(), 'lock_content' ) );
                            }

                            td_global::set_in_tdm_block_popup( false );
                            if ( !td_global::get_in_menu() && !$in_modal ) {
                                td_global::set_in_element(false);
                            }
                        }
                    }
                }

                // custom code content
                if ( $content_source == 'custom_code' ) {
                    $modal_body_content = rawurldecode( base64_decode( strip_tags( $this->get_att('custom_code') ) ) );
                }

                // set content load data
                $content_load_data = array();
                if ( $content_load === 'ui_delayed' ) {
                    $content_load_data['content_source'] = $content_source;

                    switch ($content_source) {
                        case 'custom_code':
                            $content_load_data['content'] = $modal_body_content;
                            break;
                        case 'page':
                            $content_load_data['page_id'] = !empty($page_id) ? $page_id : '';
                            break;
                    }
                }

                // the modal
                $buffy .= '<div id="tdm-popup-modal-' . $modal_id . '" class="tdm-popup-modal-wrap ' . $open_position_class . '" style="display:none;" >';
                    $buffy .= '<div class="tdm-popup-modal-bg"></div>';

                    $buffy .= '<div class="tdm-popup-modal td-theme-wrap">';
                        // the edit link
                        if ( $is_page && current_user_can( 'edit_published_posts' ) ) {
                            $edit_page_url = '#';
                            if( !$in_composer ) {
                                $edit_page_url = admin_url( 'post.php?post_id=' . $page_id . '&td_action=tdc&tdbTemplateType=page&prev_url=' . rawurlencode( tdc_util::get_current_url() ) );
                            }

                            $buffy .= '<a class="td-admin-edit" href="' . $edit_page_url . '" target="blank" title="edit post">edit</a>';
                        }

                        $buffy .= '<div class="tdm-pm-header">';
                            if( $modal_head_title != '' ) {
                                if ($modal_head_title_url !== '') {
                                    $buffy .= '<a href="' . $modal_head_title_url . '" ' . $url_target . ' class="tdm-pmh-title">' . $modal_head_title . '</a>';
                                } else {
                                    $buffy .= '<h3 class="tdm-pmh-title">' . $modal_head_title . '</h3>';
                                }
                            }
                            $buffy .= '<div class="tdm-pmh-close" ' . ( $modal_close_icon != '' && ( base64_encode( base64_decode( $modal_close_icon ) ) == $modal_close_icon ) ? $modal_close_icon_data : '' ) . '>';
                                $buffy .= $buffy_modal_close_icon;
                            $buffy .= '</div>';
                        $buffy .= '</div>';

                        $buffy .= '<div class="tdm-pm-body">';
                            if ( $modal_body_content != '' ) {
                                if ( $content_load === 'ui_delayed' ) {
                                    // do nothing ... the modal body content will be loaded via js on user interaction
                                } else {
                                    $buffy .= do_shortcode($modal_body_content);
                                }
                            } else {
                                if ( $content_load === 'ui_delayed' && ( $content_source == '' || $content_source == 'page' ) && !empty($page_id) && !empty($page) ) {
                                    // do nothing ... the modal body content will be loaded via js on user interaction
                                } else {
                                    if ( is_user_logged_in() ) {
                                        $buffy .= td_util::get_block_error(
                                            'Popup modal',
                                            'You have not selected a page or another modal in order to display its contents.'
                                        );
                                    }
                                }
                            }
                        $buffy .= '</div>';
                    $buffy .= '</div>';
                $buffy .= '</div>';
            }

            td_resources_load::render_script( TDC_SCRIPTS_URL . '/tdPopupModal.js' . TDC_SCRIPTS_VER, 'tdPopupModal-js', '', 'footer' );
            ob_start();

            if ( $content_source == 'modal_id' && !empty($source_modal_id) ) {
                ?>
                <script>

                    // this needs to run on window load event to make sure that all tdPopupModal items are initialized
                    // modified from " jQuery(window).on( 'load' " to " setTimout " for Firefox issue
                    setTimeout( function () {

                        var blockUid = '<?php echo $this->block_uid ?>',
                            $blockObj = jQuery('.<?php echo $this->block_uid ?>'),
                            modalUid = '<?php echo $source_modal_id; ?>',
                            $triggerBtnObj = $blockObj.find('> .tds-button > a.tdm-btn'),
                            in_composer = <?php echo ( tdc_state::is_live_editor_ajax() || tdc_state::is_live_editor_iframe() ) ? 'true' : 'false' ?>,
                            show_modal_in_composer = '<?php echo $this->get_att("show_modal_composer") ?>';

                        if( modalUid !== '' ) {
                            var tdPopupModalItem = tdPopupModal.getItem(modalUid);

                            // if item was found
                            if ( tdPopupModalItem ) {
                                // console.log( 'tdPopupModalItem OK !' );

                                if ( $triggerBtnObj.length && !in_composer ) {

                                    // add button click event .triggerBtnObj
                                    $triggerBtnObj.click( function (e) {
                                        e.preventDefault();
                                        //console.log( 'source modal id btn click', tdPopupModalItem );
                                        tdPopupModal.modalOpen(tdPopupModalItem);
                                    });

                                }

                            } else {
                                // console.log( 'tdPopupModalItem not found !' );
                            }

                        }

                    }, 700);

                </script>
                <?php
            } else {
                ?>
                <script>
                    /* global jQuery:{} */
                    jQuery().ready( function () {
                        var blockUid = '<?php echo $this->block_uid ?>',
                            $blockObj = jQuery('.<?php echo $this->block_uid ?>'),
                            modalUid = '<?php echo $modal_id; ?>',
                            $modalObj = $blockObj.find('.tdm-popup-modal-wrap'),
                            show_modal_in_composer = '<?php echo $this->get_att("show_modal_composer") ?>';

                        if( $modalObj.length && modalUid !== '' ) {
                            var tdPopupModalItem = new tdPopupModal.item(),
                                $triggerBtnObj = $blockObj.find('> .tds-button > a.tdm-btn');

                            // modal uid
                            tdPopupModalItem.uid = modalUid;
                            // block uid
                            tdPopupModalItem.blockUid = blockUid;
                            // modal object
                            tdPopupModalItem.modalObj = $modalObj;
                            // modal object
                            tdPopupModalItem.closeModals = <?php echo json_encode($close_modals) ?>;
                            // modal open position
                            tdPopupModalItem._modal_open_position = '<?php echo $open_position; ?>';
                            // modal open disable site scroll
                            tdPopupModalItem._site_scroll = '<?php echo $site_scroll; ?>';

                            // check to see whether we are in composer or not
                            <?php if( tdc_state::is_live_editor_ajax() || tdc_state::is_live_editor_iframe() ) { ?>
                                // we are in composer, so set the specific flags

                                // item is in composer
                                tdPopupModalItem._in_composer = true;

                                // modal should be open in composer
                                tdPopupModalItem._show_modal_in_composer = show_modal_in_composer !== '';

                            <?php } else { ?>
                                // we are not in composer
                                // trigger button object
                                if( $triggerBtnObj.length ) {
                                    tdPopupModalItem.triggerBtnObj = $triggerBtnObj;
                                }

                                // trigger types
                                tdPopupModalItem._modal_trigger_types = <?php echo json_encode($modal_triggers); ?>;

                                // modal content load
                                tdPopupModalItem._modal_content_load = '<?php echo $content_load; ?>';

                                <?php if( !empty($content_load_data) ) { ?>
                                // modal content load data
                                tdPopupModalItem._modal_content_load_data = JSON.stringify( <?php echo json_encode($content_load_data); ?> );
                                <?php } ?>
                            <?php } ?>

                            tdPopupModal.addItem(tdPopupModalItem);
                        }
                    });
                </script>
                <?php
            }

            td_js_buffer::add_to_footer( "\n" . td_util::remove_script_tag( ob_get_clean() ) );
        $buffy .= '</div>';

        return $buffy;
    }

    function js_tdc_callback_ajax() {

	    // the modal id
        $modal_id = $this->get_att('modal_id');

	    // content source
	    $content_source = $this->get_att( 'content_source' );

	    // get source modal id
	    $source_modal_id = $this->get_att( 'source_modal_id' );

        // modal opening position
        $open_position = $this->get_att('open_position') == '' ? 'over-screen' : $this->get_att('open_position');

        // disable site scroll on modal open
        $site_scroll = true;
        if( $this->get_att('site_scroll') != '' ) {
            $site_scroll = false;
        }

        $buffy = '';

        // add a new composer block - that one has the delete callback
        $buffy .= $this->js_tdc_get_composer_block();

        ob_start();

	    if ( $content_source == 'modal_id' && !empty($source_modal_id) ) {

		    ?>
            <script>

                ( function () {
                    var blockUid = '<?php echo $this->block_uid ?>',
                        $blockObj = jQuery('.<?php echo $this->block_uid ?>'),
                        modalUid = '<?php echo $source_modal_id; ?>',
                        show_modal_in_composer = '<?php echo $this->get_att("show_modal_composer") ?>';

                    if( modalUid !== '' ) {

                        tdPopupModal.items.forEach( function (item) {
                            if( item.blockUid === blockUid ) {
                                tdPopupModal.deleteItem(blockUid);
                            }
                        });

                        var tdPopupModalItem = tdPopupModal.getItem(modalUid);

                        // if item was found
                        if ( tdPopupModalItem ) {
                            //console.log( 'js_tdc_callback_ajax > tdPopupModalItem OK !' );

                            // check to see whether we are in composer or not
                            <?php if( tdc_state::is_live_editor_ajax() || tdc_state::is_live_editor_iframe() ) { ?>

                            <?php } ?>

                        } else {
                            //console.log( 'js_tdc_callback_ajax > tdPopupModalItem not found !' );
                        }

                    }

                })();

            </script>
		    <?php

        } else {

        ?>
        <script>

            /* global jQuery:{} */
            ( function () {
                var blockUid = '<?php echo $this->block_uid ?>',
                    $blockObj = jQuery('.<?php echo $this->block_uid ?>'),
                    modalUid = '<?php echo $modal_id; ?>',
                    $modalObj = $blockObj.find('.tdm-popup-modal-wrap'),

                    show_modal_in_composer = '<?php echo $this->get_att("show_modal_composer") ?>';

                if( $modalObj.length && modalUid !== '' ) {

                    var tdPopupModalItem = new tdPopupModal.item();

                    tdPopupModal.items.forEach( function (item) {
                        if( item.blockUid === blockUid ) {
                            tdPopupModal.deleteItem(blockUid);
                        }
                    });

                    // modal uid
                    tdPopupModalItem.uid = modalUid;
                    // block uid
                    tdPopupModalItem.blockUid = blockUid;
                    // modal object
                    tdPopupModalItem.modalObj = $modalObj;
                    // modal open position
                    tdPopupModalItem._modal_open_position = '<?php echo $open_position; ?>';
                    // modal open disable site scroll
                    tdPopupModalItem._site_scroll = '<?php echo $site_scroll; ?>';
                    // item is in composer
                    tdPopupModalItem._in_composer = true;
                    // modal should be open in composer
                    tdPopupModalItem._show_modal_in_composer = show_modal_in_composer !== '';

                    tdPopupModal.addItem(tdPopupModalItem);
                }

            })();

        </script>
        <?php

	    }

        return $buffy . td_util::remove_script_tag( ob_get_clean() );
    }

}
