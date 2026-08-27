<?php

/**
 * Class tdb_location_finder
 */

class tdb_location_finder extends td_block {

    private $api_service;


    public function get_custom_css() {
        // $unique_block_class - the unique class that is on the block. use this to target the specific instance via css
        $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();
        $in_element = td_global::get_in_element();
        $unique_block_class_prefix = '';
        if( $in_element || $in_composer ) {
            $unique_block_class_prefix = 'tdc-row';

            if( $in_element && $in_composer ) {
                $unique_block_class_prefix = 'tdc-row-composer';
            }
        }
        $general_block_class = $unique_block_class_prefix ? '.' . $unique_block_class_prefix : '';
        $unique_block_class = ( $unique_block_class_prefix ? $unique_block_class_prefix . ' .' : '' ) . ( $in_composer ? 'tdc-column' . ( $in_element ? '-composer' : '' ) . ' .' : '' ) . $this->block_uid;

        $compiled_css = '';

        $raw_css =
            "<style>

                /* @style_general_tdb_location_finder */
                .tdb_location_finder {
                    transform: translateZ(0);
                    margin-bottom: 28px;
                    font-family: -apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Oxygen-Sans,Ubuntu,Cantarell,\"Helvetica Neue\",sans-serif;
                    font-size: 14px;
                }
                .tdb_location_finder .tdb-s-fc-inner {
                    flex: 1;
                }
                .tdb_location_finder .tdb-s-form-content {
                    display: flex;
                }
                .tdb_location_finder .tdb-s-form-group-lf-search {
                    position: relative;
                }
                .tdb_location_finder .tdb-lf-curr-loc {
                    position: absolute;
                    top: 18px;
                    left: 28px;
                    width: 1.286em;
                    height: auto;
                    cursor: pointer;
                }
                .tdb_location_finder .tdb-lf-curr-loc:after {
                    content: '';
                    position: absolute;
                    top: 1px;
                    left: 1px;
                    width: 16px;
                    height: 16px;
                    border: 2px solid #444;
                    border-left-color: transparent;
                    border-right-color: transparent;
                    border-radius: 50%;
                    -webkit-animation: tdb-fullspin-anim 1s infinite ease-out;
                    animation: tdb-fullspin-anim 1s infinite ease-out;
                    opacity: 0; 
                }
                .tdb_location_finder .tdb-lf-curr-loc svg {
                    display: block;
                    stroke: #444;
                    opacity: .5;
                }
                .tdb_location_finder .tdb-lf-curr-loc:hover svg {
                    opacity: 1;
                }
                .tdb_location_finder .tdb-s-form-label-optional {
                    opacity: .5;
                    font-size: .846em;
                }
                .tdb_location_finder .tdb-s-form-group-lf-search input {
                    height: 54px;
                    min-height: 54px;
                    padding-left: calc(26px + 1.286em);
                    border: none !important;
                    border-bottom-left-radius: 0 !important;
                    border-bottom-right-radius: 0 !important;
                    outline: none !important;
                }
                .tdb_location_finder .tdb-s-form-group-lf-search input:focus {
                    border-color: transparent !important;
                }
                .tdb_location_finder .tdb-s-form-group-lf-search input.loading + .tdb-lf-search-outline + .tdb-lf-map + .tdb-lf-curr-loc:after {
                    opacity: 1;
                }
                .tdb_location_finder .tdb-s-form-group-lf-search input.loading + .tdb-lf-search-outline + .tdb-lf-map + .tdb-lf-curr-loc svg {
                    opacity: 0;
                }
                .tdb_location_finder .tdb-lf-search-outline {
                    position: absolute;
                    top: 0;
                    left: 13px;
                    width: calc(100% - 26px);
                    height: 100%;
                    outline: 3px solid transparent;
                    border: 2px solid #D7D8DE;
                    border-radius: 5px;
                    transition: border-color .2s ease-in-out, outline-color .2s ease-in-out;
                    pointer-events: none;
                    z-index: 10;
                }
                .tdb_location_finder .tdb-s-form-group-lf-search:not(.tdb-s-fg-error) input:focus + .tdb-lf-search-outline {
                    border-color: #0489FC;
                    outline-color: rgba(4, 137, 252, .1);
                }
                .tdb_location_finder .tdb-s-fg-error .tdb-lf-search-outline {
                    border-color: #FF0000 !important;
                    outline: 3px solid rgba(255, 0, 0, .1);
                }
                .tdb_location_finder .tdb-lf-map {
                    position: relative;
                    background-color: #D7D8DE;
                    height: 400px;
                    border-width: 0 2px 2px 2px;
                    border-style: solid;
                    border-color: #D7D8DE;
                    border-radius: 0 0 5px 5px;
                    overflow: hidden;
                }
                html body .tdb_location_finder .tdb-block-inner .tdb-sml-map .gm-style img {
                    opacity: 1;
                }
                .tdb_location_finder .tdb-lf-map-loading {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                }
                .tdb_location_finder .tdb-lf-map-loading:before {
                    content: '';
                    width: 40px;
                    height: 40px;
                    border: 3px solid #888;
                    border-left-color: transparent;
                    border-right-color: transparent;
                    border-radius: 50%;
                    -webkit-animation: tdb-fullspin-anim 1s infinite ease-out;
                    animation: tdb-fullspin-anim 1s infinite ease-out;
                }
                .tdb_location_finder .tdb-lf-map-loading:after {
                    content: 'Loading the map...';
                    margin-top: 15px;
                    font-size: 1em;
                    font-weight: 500;
                    color: #666;
                }
                body .tdb_location_finder .tdb-s-form-content .tdb-notif-location {
                    display: none;
                    margin: 0 13px 28px;
                    width: 100%;
                }
                @media (min-width: 1019px) {
                    body .tdb_location_finder .tdb-s-form .tdb-s-form-group-lf-city,
                    body .tdb_location_finder .tdb-s-form .tdb-s-form-group-lf-state,
                    body .tdb_location_finder .tdb-s-form .tdb-s-form-group-lf-country {
                        width: 33.3333%;
                        margin-bottom: 0;
                    }
                }
                @media (max-width: 1018px) {
                    body .tdb_location_finder .tdb-s-form .tdb-s-form-group-lf-city,
                    body .tdb_location_finder .tdb-s-form .tdb-s-form-group-lf-state,
                    body .tdb_location_finder .tdb-s-form .tdb-s-form-group-lf-country {
                        width: 50%;
                    }
                    body .tdb_location_finder .tdb-s-form .tdb-s-form-group-lf-state,
                    body .tdb_location_finder .tdb-s-form .tdb-s-form-group-lf-country {
                        margin-bottom: 0;
                    }
                }
                @media (min-width: 1019px) {
                    body .tdb_location_finder .tdb-s-form .tdb-s-form-group-lf-address {
                        width: 78%;
                    }
                }
                body .tdb_location_finder .tdb-s-form .tdb-s-form-group-lf-postal-code {
                    width: 22%;
                }
                @media (max-width: 1018px) {
                    body .tdb_location_finder .tdb-s-form .tdb-s-form-group-lf-postal-code {
                        width: 50%;
                    }
                }
                .pac-container,
                .tdb-lf-search-results {
                    padding: 5px 15px 7px;
                    font-family: -apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Oxygen-Sans,Ubuntu,Cantarell,\"Helvetica Neue\",sans-serif;
                    border: 2px solid #0489FC;
                    border-radius: 0;
                    box-shadow: none;
                    z-index: 10005;
                }
                .tdb-lf-search-results {
                    position: absolute;
                    left: 13px;
                    background-color: #fff;
                    width: calc(100% - 26px);
                    max-height: 195px;
                    overflow-y: auto;
                }
                .pac-container:after {
                    display: none;
                }
                .pac-item,
                .tdb-lf-search-result {
                    padding: 11px 0 12px;
                    font-size: 12px;
                    line-height: 1;
                    cursor: pointer;
                    border-top-color: #ececec;
                }
                .tdb-lf-search-result {
                    text-overflow: ellipsis;
                    overflow: hidden;
                    white-space: nowrap;
                    color: #000;
                    border-top: 1px solid #e6e6e6;
                }
                .tdb-lf-search-result span {
                    font-weight: 700;
                }
                .pac-item:first-child,
                .tdb-lf-search-result:first-child {
                    border-top: 0;
                }
                .pac-item:hover,
                .tdb-lf-search-result:hover {
                    background-color: transparent;
                    text-decoration: underline; 
                }
                .pac-icon {
                    display: none;
                }
                .pac-item-query {
                    font-size: 1em;
                }
                .pac-item span:nth-child(3) {
                    font-size: 10px;
                }
                
                /* @style_general_tdb_location_finder_composer */
                .tdb_location_finder .tdb-block-inner {
                    pointer-events: none; 
                }
                .tdb_location_finder .tdb-lf-map {
                    background-size: cover;
                    background-position: center;
                    background-repeat: no-repeat;
                }
                .tdb_location_finder[data-api-service='google'] .tdb-lf-map {
                    background-image: url(" . TDB_URL . "/assets/images/location_finder_google.jpg);
                }
                .tdb_location_finder[data-api-service='bing'] .tdb-lf-map {
                    background-image: url(" . TDB_URL . "/assets/images/location_finder_bing.jpg);
                }
                
                /* @show_notif */
                body .$unique_block_class .tdb-s-form-content .tdb-s-notif {
                    display: block;
                }
                
                
                /* @map_height */
                body .$unique_block_class .tdb-lf-map  {
                    height: @map_height;
                }
                
                
                
                /* @all_input_display_row */
                body .$unique_block_class .tdb-s-form-content {
                    flex-direction: column;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-lf-main-label {
                    width: 100%;
                    margin: 0 0 8px;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-label-descr {
                    margin-bottom: 2px;
                }
                
                /* @all_input_display_columns */
                body .$unique_block_class .tdb-s-form-content {
                    flex-direction: row;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-lf-main-label {
                    width: @all_label_width;
                    margin: 0 24px 0 0;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-label-descr {
                    margin-bottom: 0;
                }
                
                
                /* @all_input_border */
                body .$unique_block_class .tdb-s-form .tdb-s-form-input,
                body .$unique_block_class .tdb-lf-search-outline {
                    border: @all_input_border @all_input_border_style @all_input_border_color;
                }           
                body .$unique_block_class .tdb-s-form .tdb-s-form-group:not(.tdb-s-fg-error) .tdb-s-form-input[readonly]:not(.tdb-s-form-input-date),
                body .$unique_block_class .tdb-s-form .tdb-s-form-group:not(.tdb-s-fg-error) .tdb-s-form-input:disabled {
                    border-color: @all_input_border_color;
                }
                /* @input_border_color_r */
                body .$unique_block_class .tdb-s-form .tdb-s-form-group:not(.tdb-s-fg-error) .tdb-s-form-input[readonly]:not(.tdb-s-form-input-date),
                body .$unique_block_class .tdb-s-form .tdb-s-form-group:not(.tdb-s-fg-error) .tdb-s-form-input:disabled {
                    border-color: @input_border_color_r;
                }
                /* @input_radius */
                body .$unique_block_class .tdb-s-form .tdb-s-form-input,
                body .$unique_block_class .tdb-lf-search-outline {
                    border-radius: @input_radius;
                }
                body .$unique_block_class .tdb-lf-map {
                    border-radius: 0 0 @input_radius @input_radius;
                }
                
                /* @notif_radius */
                body .$unique_block_class .tdb-s-notif {
                    border-radius: @notif_radius;
                }
                
                
                /* @accent_color */
                body .$unique_block_class .tdb-s-form .tdb-s-form-group:not(.tdb-s-fg-error) .tdb-s-form-input:focus:not([readonly]) {
                    border-color: @accent_color !important;
                }
                body .$unique_block_class .tdb-s-form-group-lf-search input:focus + .tdb-lf-search-outline {
                    border-color: @accent_color !important;
                }
                /* @input_outline_accent_color */
                body .$unique_block_class .tdb-s-form .tdb-s-form-group:not(.tdb-s-fg-error) .tdb-s-form-input:focus:not([readonly]),
                body .$unique_block_class .tdb-s-form-group-lf-search input:focus + .tdb-lf-search-outline {
                    outline-color: @input_outline_accent_color !important;
                }
                
                /* @label_color */
                body .$unique_block_class .tdb-s-form .tdb-s-form-label {
                    color: @label_color;
                }
                /* @descr_color */
                body .$unique_block_class .tdb-s-form .tdb-s-form-label-descr {
                    color: @descr_color;
                }
                /* @input_color */
                body .$unique_block_class .tdb-s-form .tdb-s-form-input,
                body .$unique_block_class .tdb-s-form .tdb-s-form-group:not(.tdb-s-fg-error) .tdb-s-form-input[readonly]:not(.tdb-s-form-input-date),
                body .$unique_block_class .tdb-s-form .tdb-s-form-group:not(.tdb-s-fg-error) .tdb-s-form-input:disabled {
                    color: @input_color;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill,
                body .$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill:hover,
                body .$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill:focus,
                body .$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill:active {
                    -webkit-text-fill-color: @input_color;
                }
                body .$unique_block_class .tdb-lf-curr-loc svg {
                    stroke: @input_color;
                }
                body .$unique_block_class .tdb-lf-curr-loc:after {
                    border-top-color: @input_color;
                    border-bottom-color: @input_color;
                }
                /* @input_color_r */
                html body .$unique_block_class .tdb-s-form .tdb-s-form-group:not(.tdb-s-fg-error) .tdb-s-form-input[readonly]:not(.tdb-s-form-input-date),
                html body .$unique_block_class .tdb-s-form .tdb-s-form-group:not(.tdb-s-fg-error) .tdb-s-form-input:disabled {
                    color: @input_color_r;
                }
                /* @input_place */
                body .$unique_block_class .tdb-s-form .tdb-s-form-input::placeholder {
                    color: @input_place;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-input::-webkit-input-placeholder {
                    color: @input_place;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-input::-moz-placeholder {
                    color: @input_place;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-input:-ms-input-placeholder {
                    color: @input_place;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-input:-moz-placeholder {
                    color: @input_place;
                }
                /* @input_bg */
                body .$unique_block_class .tdb-s-form .tdb-s-form-input,
                body .$unique_block_class .tdb-s-form .tdb-s-form-group:not(.tdb-s-fg-error) .tdb-s-form-input[readonly] {
                    background-color: @input_bg;
                }
                body .$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill,
                body .$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill:hover,
                body .$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill:focus,
                body .$unique_block_class .tdb-s-form .tdb-s-form-input:-webkit-autofill:active {
                    -webkit-box-shadow: 0 0 0 1000px @input_bg inset !important;
                }
                
                /* @notif_warn_color */
                body .$unique_block_class .tdb-s-notif-warning {
                    color: @notif_warn_color;
                }
                /* @notif_warn_bg */
                body .$unique_block_class .tdb-s-notif-warning {
                    background-color: @notif_warn_bg;
                }
                
                
                /* @f_text */
                body .$unique_block_class {
                    @f_text
                }
                
			</style>";


        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts() );

        $compiled_css .= $td_css_res_compiler->compile_css();
        return $compiled_css;
    }

    static function cssMedia( $res_ctx ) {

        /*-- GENERAL STYLES -- */
        $res_ctx->load_settings_raw( 'style_general_tdb_location_finder', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_location_finder_composer', 1 );
            $res_ctx->load_settings_raw( 'show_notif', $res_ctx->get_shortcode_att('show_notif') );
        }



        /*-- LAYOUT -- */
        // map height
        $map_height = $res_ctx->get_shortcode_att('map_height');
        $res_ctx->load_settings_raw( 'map_height', $map_height );
        if( $map_height != '' && is_numeric( $map_height ) ) {
            $res_ctx->load_settings_raw( 'map_height', $map_height . 'px' );
        }

        // inputs display
        $all_input_display = $res_ctx->get_shortcode_att('all_input_display');
        if( $all_input_display == '' || $all_input_display == 'row' ) {
            $res_ctx->load_settings_raw( 'all_input_display_row', 1 );
        } else {
            $res_ctx->load_settings_raw( 'all_input_display_columns', 1 );
        }

        // labels width
        $all_label_width = $res_ctx->get_shortcode_att('all_label_width');
        $res_ctx->load_settings_raw( 'all_label_width', $all_label_width );
        if( $all_label_width != '' ) {
            if( is_numeric( $all_label_width ) ) {
                $res_ctx->load_settings_raw( 'all_label_width', $all_label_width . 'px' );
            }
        } else {
            $res_ctx->load_settings_raw( 'all_label_width', '30%' );
        }


        // inputs border size
        $all_input_border = $res_ctx->get_shortcode_att('all_input_border');
        $res_ctx->load_settings_raw( 'all_input_border', $all_input_border );
        if( $all_input_border == '' ) {
            $res_ctx->load_settings_raw( 'all_input_border', '2px' );
        } else {
            if( is_numeric( $all_input_border ) ) {
                $res_ctx->load_settings_raw( 'all_input_border', $all_input_border . 'px' );
            }
        }

        // inputs border style
        $all_input_border_style = $res_ctx->get_shortcode_att('all_input_border_style');
        if( $all_input_border_style != '' ) {
            $res_ctx->load_settings_raw( 'all_input_border_style', $all_input_border_style );
        } else {
            $res_ctx->load_settings_raw( 'all_input_border_style', 'solid' );
        }

        // inputs border radius
        $input_radius = $res_ctx->get_shortcode_att('input_radius');
        $res_ctx->load_settings_raw( 'input_radius', $input_radius );
        if( $input_radius != '' && is_numeric( $input_radius ) ) {
            $res_ctx->load_settings_raw( 'input_radius', $input_radius . 'px' );
        }


        // notifications border radius
        $notif_radius = $res_ctx->get_shortcode_att('notif_radius');
        $res_ctx->load_settings_raw( 'notif_radius', $notif_radius );
        if( $notif_radius != '' && is_numeric( $notif_radius ) ) {
            $res_ctx->load_settings_raw( 'notif_radius', $notif_radius . 'px' );
        }



        /*-- COLORS -- */
        $accent_color = $res_ctx->get_shortcode_att('accent_color');
        $res_ctx->load_settings_raw( 'accent_color', $accent_color );
        if( !empty( $accent_color ) ) {
            $res_ctx->load_settings_raw( 'input_outline_accent_color', td_util::hex2rgba($accent_color, 0.1) );
        }

        $res_ctx->load_settings_raw( 'label_color', $res_ctx->get_shortcode_att('label_color') );
        $res_ctx->load_settings_raw( 'descr_color', $res_ctx->get_shortcode_att('descr_color') );
        $res_ctx->load_settings_raw( 'input_color', $res_ctx->get_shortcode_att('input_color') );
        $res_ctx->load_settings_raw( 'input_color_r', $res_ctx->get_shortcode_att('input_color_r') );
        $res_ctx->load_settings_raw( 'input_place', $res_ctx->get_shortcode_att('input_place') );
        $res_ctx->load_settings_raw( 'input_bg', $res_ctx->get_shortcode_att('input_bg') );
        $all_input_border_color = $res_ctx->get_shortcode_att('all_input_border_color');
        if( $all_input_border_color != '' ) {
            $res_ctx->load_settings_raw( 'all_input_border_color', $all_input_border_color );
        } else {
            $res_ctx->load_settings_raw( 'all_input_border_color', '#D7D8DE' );
        }
        $res_ctx->load_settings_raw( 'input_border_color_r', $res_ctx->get_shortcode_att('input_border_color_r') );

        $notif_warn_color = $res_ctx->get_shortcode_att('notif_warn_color');
        $res_ctx->load_settings_raw( 'notif_warn_color', $notif_warn_color );
        if( !empty( $notif_warn_color ) ) {
            $res_ctx->load_settings_raw('notif_warn_bg', td_util::hex2rgba($notif_warn_color, 0.08));
        }



        /*-- FONTS -- */
        $res_ctx->load_font_settings( 'f_text' );

    }

    /**
     * Disable loop block features. This block does not use a loop and it doesn't need to run a query.
     */
    function __construct() {
        parent::disable_loop_block_features();
    }


    function render( $atts, $content = null ) {
        parent::render( $atts ); // sets the live atts, $this->atts, $this->block_uid, $this->td_query (it runs the query)

        $buffy = ''; //output buffer


        // in composer flag
        $in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();


        // currently logged in user
        $current_user = wp_get_current_user();
        $current_user_id = $current_user->ID;
        $is_current_user_admin = in_array('administrator', $current_user->roles);


        // maps API service
        $this->api_service = $this->get_att('service') != '' ? $this->get_att('service') : 'google';


        // enable/disable create terms
        $create_terms = $this->get_att('create_terms') != '';


        // tax type
        $tax_type = '';
        if( $create_terms ) {
            $tax_type = $this->get_att('tax_type') != '' ? $this->get_att('tax_type') : 'category';
        }


        // complete location meta
        $location_meta = $this->get_att('location_meta') != '' ? $this->get_att('location_meta') : 'tdb-location-complete';
        


        // required field
        $required = $this->get_att('required');
        if( $required != '' ) {
            $required = 1;
        } else {
            $required = 0;
        }


        // Enable only for authenticated users
        $authenticated_users = $this->get_att('authenticated_users');
        $disable_for_guests = $authenticated_users != '' && !is_user_logged_in();


        // fields values
        $searchValue = '';
        $addressValue = '';
        $postalCodeValue = '';
        $cityValue = '';
        $stateValue = '';
        $countryValue = '';

        $notifMessage = '';

        $curr_post_id = '';
        if ( isset($_GET['post_id']) && !( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) ) {
            $post = get_post($_GET['post_id']);

            if( $post && ( $post->post_author == $current_user_id || $is_current_user_admin ) ) {
                $curr_post_id = $_GET['post_id'];
            }
        }

        if ( !empty($curr_post_id) ) {

            $currPostCityName = '';
            $currPostStateName = '';
            $currPostCountryName = '';


            $post_terms = get_the_terms( $curr_post_id, $tax_type );

            if( $create_terms && !empty( $post_terms ) && !is_wp_error($post_terms) ) {
                foreach ( $post_terms as $post_term ) {
                    if( metadata_exists('term', $post_term->term_id, 'tdb-location-type') ) {
                        $location_type = get_term_meta($post_term->term_id, 'tdb-location-type', true);

                        if( $location_type == 'city' ) {
                            $currPostCityName = $post_term->name;
                        } else if ( $location_type == 'state' ) {
                            $currPostStateName = $post_term->name;
                        } else if ( $location_type == 'country' ) {
                            $currPostCountryName = $post_term->name;
                        }
                    }
                }
            } else {
                $locationCompleteMeta = get_post_meta($curr_post_id, $location_meta, true);

                if( !empty( $locationCompleteMeta ) ) {
                    $locationCompleteArray = explode(',', $locationCompleteMeta);

                    $currPostCityName = $locationCompleteArray[1];
                    $currPostStateName = $locationCompleteArray[2];
                    $currPostCountryName = $locationCompleteArray[3];
                }
            }

            if( $currPostCityName != '' && $currPostStateName != '' && $currPostCountryName != '' ) {
                $cityValue = $currPostCityName;
                $stateValue = $currPostStateName;
                $countryValue = $currPostCountryName;

                $addressValue = get_post_meta($curr_post_id, 'tdb-location-address', true);
                $postalCodeValue = get_post_meta($curr_post_id, 'tdb-location-postal-code', true);

                $searchValue = $addressValue;
                $searchValue .= $cityValue != '' ? ( $searchValue != '' ? ', ' : '' ) . $cityValue : '';
                $searchValue .= $stateValue != '' ? ( $searchValue != '' ? ', ' : '' ) . $stateValue : '';
                $searchValue .= $countryValue != '' ? ( $searchValue != '' ? ', ' : '' ) . $countryValue : '';
            }

        }

        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $searchValue = '8208 Hooper Avenue, Los Angeles, CA, USA';
            $addressValue = 'Hooper Avenue 8208';
            $postalCodeValue = '90001';
            $cityValue = 'Los Angeles';
            $stateValue = 'California';
            $countryValue = 'United States';

            $notifMessage = 'Sample notification message.';
        }


        // Label text
        $label_txt = $this->get_att('label_txt');

        // Label description
        $label_descr_txt = rawurldecode( base64_decode( strip_tags( $this->get_att('descr_txt') ) ) );


        $buffy .= '<div class="' . $this->get_block_classes() . ' ' . ( $disable_for_guests ? 'tdb-disabled' : '' ) . '" ' . $this->get_block_html_atts() . ' data-tax-type="' . $tax_type  .'" data-required="' . $required . '" data-location-meta="' . $location_meta . '" data-api-service="' . $this->api_service . '">';

            //get the block css
            $buffy .= $this->get_block_css();

            //get the js for this block
            $buffy .= $this->get_block_js();

            // Check for API keys, based on service provider
            $api_key_exists = true;
            switch( $this->api_service ) {
                case 'google':
                    if( td_util::get_gm_api_key() == '' ) {
                        $buffy .= td_util::get_block_error('Posts Form Location Finder', '<strong>A Google Maps API key</strong> has not been provided. Go to <strong>Theme Panel > Social/APIs > Google Maps API Configuration</strong>');

                        $api_key_exists = false;
                    }
                    break;
                case 'bing':
                    if( td_util::get_bm_api_key() == '' ) {
                        $buffy .= td_util::get_block_error('Posts Form Location Finder', '<strong>A Bing Maps API key</strong> has not been provided. Go to <strong>Theme Panel > Social/APIs > Bing Maps API Configuration</strong>');

                        $api_key_exists = false;
                    }
                    break;
            }

            // Proceed only if the API key exists
            if( $api_key_exists ) {
                $buffy .= '<div class="tdb-block-inner td-fix-index tdb-s-form tdb-s-content" id="test">';
                    $buffy .= '<input type="hidden" id="tdb-lf-type-' . $this->block_uid . '" value="' . $tax_type . '">';

                    $buffy .= '<div class="tdb-s-form-content">';
                        if( $label_txt != '' ) {
                            $buffy .= '<label class="tdb-s-form-label tdb-s-lf-main-label">';
                                $buffy .= $label_txt;
                                if( $required ) {
                                    $buffy .= '<span class="tdb-s-form-label-required"> *</span>';
                                }

                                if( $label_descr_txt != '' ) {
                                    $buffy .= '<span class="tdb-s-form-label-descr">' . $label_descr_txt . '</span>';
                                }
                            $buffy .= '</label>';
                        }

                        $buffy .= '<div class="tdb-s-fc-inner">';
                            $buffy .= '<div class="tdb-s-form-group tdb-s-form-group-lf-search tdb-s-content">';
                                $buffy .= '<input class="tdb-s-form-input" id="tdb-lf-search-' . $this->block_uid . '" type="text" ' . ( $searchValue ? 'value="' . $searchValue . '"' : '' ) . ' placeholder="' . __td( 'Search for a location', TD_THEME_NAME ) . '" ' . ( $disable_for_guests ? 'disabled' : '' ) . '>';

                                $buffy .= '<div class="tdb-lf-search-outline"></div>';

                                $buffy .= '<div class="tdb-lf-map" id="map">';
                                    if( !$in_composer ) {
                                        $buffy .= '<div class="tdb-lf-map-loading"></div>';
                                    }
                                $buffy .= '</div>';

                                $buffy .= '<div class="tdb-lf-curr-loc" title="Get current location"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-compass"><circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon></svg></div>';
                            $buffy .= '</div>';

                            $buffy .= '<div class="tdb-s-notif tdb-s-notif-sm tdb-s-notif-warning tdb-notif-location"><div class="tdb-s-notif-descr">' . $notifMessage . '</div></div>';

                            $buffy .= '<div class="tdb-s-form-group tdb-s-form-group-lf-address tdb-s-content">';
                                $buffy .= '<label class="tdb-s-form-label" for="tdb-lf-address-' . $this->block_uid . '"> ' . __td( 'Address line', TD_THEME_NAME ) . ' <span class="tdb-s-form-label-optional">' . __td( '(Optional)', TD_THEME_NAME ) . '</span></label>';
                                $buffy .= '<input class="tdb-s-form-input" id="tdb-lf-address-' . $this->block_uid . '" name="tdb-location-address-' . $this->block_uid . '" type="text" ' . ( $addressValue ? 'value="' . $addressValue . '"' : '' ) . ' ' . ( $disable_for_guests ? 'disabled' : '' ) . '>';
                            $buffy .= '</div>';

                            $buffy .= '<div class="tdb-s-form-group tdb-s-form-group-lf-postal-code tdb-s-content">';
                                $buffy .= '<label class="tdb-s-form-label" for="tdb-lf-postal-code-' . $this->block_uid . '">' . __td( 'Postal code', TD_THEME_NAME ) . ' <span class="tdb-s-form-label-optional">' . __td( '(Optional)', TD_THEME_NAME ) . '</span></label>';
                                $buffy .= '<input class="tdb-s-form-input" id="tdb-lf-postal-code-' . $this->block_uid . '" name="tdb-location-postal-code-' . $this->block_uid . '" type="text" ' . ( $postalCodeValue ? 'value="' . $postalCodeValue . '"' : '' ) . ' ' . ( $disable_for_guests ? 'disabled' : '' ) . '>';
                            $buffy .= '</div>';

                            $buffy .= '<div class="tdb-s-form-group tdb-s-form-group-lf-city tdb-s-content">';
                                $buffy .= '<label class="tdb-s-form-label" for="tdb-lf-city-' . $this->block_uid . '">' . __td( 'City', TD_THEME_NAME ) . '</label>';
                                $buffy .= '<input class="tdb-s-form-input" id="tdb-lf-city-' . $this->block_uid . '" name="tdb-location-city-' . $this->block_uid . '" type="text" ' . ( $cityValue ? 'value="' . $cityValue . '"' : '' ) . ' readonly>';
                            $buffy .= '</div>';

                            $buffy .= '<div class="tdb-s-form-group tdb-s-form-group-lf-state tdb-s-content">';
                                $buffy .= '<label class="tdb-s-form-label" for="tdb-lf-state-' . $this->block_uid . '">' . __td( 'State', TD_THEME_NAME ) . '</label>';
                                $buffy .= '<input class="tdb-s-form-input" id="tdb-lf-state-' . $this->block_uid . '" name="tdb-location-state-' . $this->block_uid . '" type="text" ' . ( $stateValue ? 'value="' . $stateValue . '"' : '' ) . ' readonly>';
                            $buffy .= '</div>';

                            $buffy .= '<div class="tdb-s-form-group tdb-s-form-group-lf-country tdb-s-content">';
                                $buffy .= '<label class="tdb-s-form-label" for="tdb-lf-country-' . $this->block_uid . '">' . __td( 'Country', TD_THEME_NAME ) . '</label>';
                                $buffy .= '<input class="tdb-s-form-input" id="tdb-lf-country-' . $this->block_uid . '" name="tdb-location-country-' . $this->block_uid . '" type="text" ' . ( $countryValue ? 'value="' . $countryValue . '"' : '' ) . ' readonly>';
                            $buffy .= '</div>';
                        $buffy .= '</div>';
                    $buffy .= '</div>';
                $buffy .= '</div>';


                if( !$in_composer ) {

                    if( $this->api_service == 'google' ) {
                        td_resources_load::render_script( 'https://maps.googleapis.com/maps/api/js?key=' . td_util::get_gm_api_key() . '&libraries=places&callback=Function.prototype', 'tdb_js_google_maps_api', '', 'footer' );
                    }
                    td_resources_load::render_script( TDB_SCRIPTS_URL . '/tdbLocationFinder.js' . TDB_SCRIPTS_VER, 'tdbLocationFinder-js', '', 'footer' );

                    ob_start();
                    ?>
                    <script>

                        function tdbLFLoadMap_<?php echo $this->block_uid ?>() {

                            let uid = '<?php echo $this->block_uid ?>',
                                $blockObj = jQuery('.' + uid),
                                defLat = 0, defLong = 0, defZoom = 3;

                            let tdbLocationFinderItem = new tdbLocationFinder.item();
                            // block uid
                            tdbLocationFinderItem.uid = uid;
                            // block object
                            tdbLocationFinderItem.blockObj = $blockObj;
                            // maps API service
                            tdbLocationFinderItem.APIService = '<?php echo $this->api_service ?>';
                            // default coords
                            tdbLocationFinderItem.defLat = defLat;
                            tdbLocationFinderItem.defLong = defLong;
                            tdbLocationFinderItem.defZoom = defZoom;
                            // default address
                            tdbLocationFinderItem.defAddress = '<?php echo $searchValue ?>';
                            // in composer
                            tdbLocationFinderItem._in_composer = false;

                            tdbLocationFinder.addItem(tdbLocationFinderItem);

                        }

                        switch( '<?php echo $this->api_service ?>' ) {
                            case 'google':
                                tdbLFLoadMap_<?php echo $this->block_uid ?>();
                                break;
                            case 'bing':
                                if( typeof tdbLocationFinderBingElements !== 'undefined' ) {
                                    tdbLocationFinderBingElements.push(tdbLFLoadMap_<?php echo $this->block_uid ?>);
                                } else {
                                    tdbLocationFinderBingElements = [tdbLFLoadMap_<?php echo $this->block_uid ?>];
                                }
                                break;
                        }

                    </script>
                    <?php
                    td_js_buffer::add_to_footer( "\n" . ob_get_clean(), true );


                    if( $this->api_service == 'bing' ) {
                        ob_start();
                        ?>
                        <script>

                            if( window.tdbLoadBingMaps === undefined ) {
                                function tdbLoadBingMaps() {
                                    if( typeof tdbLocationFinderBingElements !== 'undefined' ) {
                                        tdbLocationFinderBingElements.forEach(function (fn) {
                                            fn();
                                        });
                                    }
                                }
                                jQuery.getScript('//www.bing.com/api/maps/mapcontrol?key=<?php echo td_util::get_bm_api_key() ?>&callback=tdbLoadBingMaps');
                            }

                        </script>
                        <?php
                        td_js_buffer::add_to_footer( "\n" . ob_get_clean(), true, 'end' );
                    }

                }

            }

        $buffy .= '</div> <!-- ./block -->';

        return $buffy;
    }

}