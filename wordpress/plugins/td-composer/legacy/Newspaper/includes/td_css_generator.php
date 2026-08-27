<?php

/**
 * @big_grid_large_image is put after @big_grid_small_images so that it will overwrite small posts style
 */

function td_css_generator() {

    $raw_css = "
    <style>
    /* ------------------------------------------------------ */
    /* Newspaper */
    /* @excl_label */
    :root {
        --td_excl_label: '@excl_label';
    }

    /* ------------------------------------------------------ */
    /* GENERAL Theme Colors */

    /* THEME ACCENT COLOR */
    /* @theme_color */
    :root {
        --td_theme_color: @theme_color;
    }


    /* @slider_text */
    :root {
        --td_slider_text: @slider_text;
    }


    /* @container_transparent */
    :root {
        --td_container_transparent: transparent;
    }
    

    /* @header_color */
    :root {
        --td_header_color: @header_color;
    }

    /* @text_header_color */
    :root {
        --td_text_header_color: @text_header_color;
    }


    /* ------------------------------------------------------ */
    /* Main Menu */
    /* @mobile_menu_color */
    :root {
        --td_mobile_menu_color: @mobile_menu_color;
    }

    /* @mobile_icons_color */
    :root {
        --td_mobile_icons_color: @mobile_icons_color;
    }

    /* @mobile_gradient_one_mob */
    :root {
        --td_mobile_gradient_one_mob: @mobile_gradient_one_mob;
        --td_mobile_gradient_two_mob: @mobile_gradient_two_mob;
    }

    /* @mobile_text_active_color */
    :root {
        --td_mobile_text_active_color: @mobile_text_active_color;
    }

    /* @mobile_button_background_mob */
    :root {
        --td_mobile_button_background_mob: @mobile_button_background_mob;
    }

    /* @mobile_button_color_mob */
    :root {
        --td_mobile_button_color_mob: @mobile_button_color_mob;
    }

    /* @mobile_text_color */
    :root {
        --td_mobile_text_color: @mobile_text_color;
    }
    
    
    
    
    /* ------------------------------------------------------ */
    /* Content */
    /* @page_title_color */
    :root {
        --td_page_title_color: @page_title_color;
    }

    /* @page_content_color */
    :root {
        --td_page_content_color: @page_content_color;
    }

    /* @page_h_color */
    :root {
        --td_page_h_color: @page_h_color;
    }
    .td-page-content .widgettitle {
        color: #fff;
    }

    /* @thumb_placeholder */
    .td_module_wrap .td-image-wrap:before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url(@thumb_placeholder);
        background-repeat: no-repeat;
        background-size: cover;
        background-position: center;
        z-index: -1;
    }



    /* @mobile_background_image */
    .td-menu-background,
    .td-search-background {
        background-image: url('@mobile_background_image');
    }

    /* @mobile_background_repeat */
    :root {
        --td_mobile_background_repeat: @mobile_background_repeat;
    }

    /* @mobile_background_size */
    :root {
        --td_mobile_background_size: @mobile_background_size;
    }

    /* @mobile_background_position */
    :root {
        --td_mobile_background_position: @mobile_background_position;
    }


    /* ------------------------------------------------------ */
    /* @custom_def_googl_f_1 */
    :root {
        --td_default_google_font_1: @custom_def_googl_f_1;
    }
    /* @custom_def_googl_f_2 */
    :root {
        --td_default_google_font_2: @custom_def_googl_f_2;
    }



    /* @main_menu */
    ul.sf-menu > .menu-item > a {
        @main_menu
    }
    /* @main_sub_menu */
    .sf-menu ul .menu-item a {
        @main_sub_menu
    }
    /* @mobile_menu */
    .td-mobile-content .td-mobile-main-menu > li > a {
        @mobile_menu
    }
    /* @mobile_sub_menu */
    .td-mobile-content .sub-menu a {
        @mobile_sub_menu
    }
    /* @text_logo */
    .td-header-wrap .td-logo-text-container .td-logo-text {
        @text_logo
    }
    /* @text_logo_tagline */
    .td-header-wrap .td-logo-text-container .td-tagline-text {
        @text_logo_tagline
    }



    /* mobile_general */
	#td-mobile-nav,
	#td-mobile-nav .wpb_button,
	.td-search-wrap-mob {
		@mobile_general
	}


	/* @page_title */
    .td-page-title,
    .td-category-title-holder .td-page-title {
    	@page_title
    }
    /* @page_content */
    .td-page-content p,
    .td-page-content li,
    .td-page-content .td_block_text_with_title,
    .wpb_text_column p {
    	@page_content
    }
    /* @page_h1 */
    .td-page-content h1,
    .wpb_text_column h1 {
    	@page_h1
    }
    /* @page_h2 */
    .td-page-content h2,
    .wpb_text_column h2 {
    	@page_h2
    }
    /* @page_h3 */
    .td-page-content h3,
    .wpb_text_column h3 {
    	@page_h3
    }
    /* @page_h4 */
    .td-page-content h4,
    .wpb_text_column h4 {
    	@page_h4
    }
    /* @page_h5 */
    .td-page-content h5,
    .wpb_text_column h5 {
    	@page_h5
    }
    /* @page_h6 */
    .td-page-content h6,
    .wpb_text_column h6 {
    	@page_h6
    }



	/* @body_text */
    body, p {
    	@body_text
    }




    /* @bbpress_header */
    #bbpress-forums .bbp-header .bbp-forums,
    #bbpress-forums .bbp-header .bbp-topics,
    #bbpress-forums .bbp-header {
    	@bbpress_header
    }
    /* @bbpress_titles */
    #bbpress-forums .hentry .bbp-forum-title,
    #bbpress-forums .hentry .bbp-topic-permalink {
    	@bbpress_titles
    }
    /* @bbpress_subcategories */
    #bbpress-forums .bbp-forums-list li {
    	@bbpress_subcategories
    }
    /* @bbpress_description */
    #bbpress-forums .bbp-forum-info .bbp-forum-content {
    	@bbpress_description
    }
    /* @bbpress_author */
    #bbpress-forums div.bbp-forum-author a.bbp-author-name,
    #bbpress-forums div.bbp-topic-author a.bbp-author-name,
    #bbpress-forums div.bbp-reply-author a.bbp-author-name,
    #bbpress-forums div.bbp-search-author a.bbp-author-name,
    #bbpress-forums .bbp-forum-freshness .bbp-author-name,
    #bbpress-forums .bbp-topic-freshness a:last-child {
    	@bbpress_author
    }
    /* @bbpress_replies */
    #bbpress-forums .hentry .bbp-topic-content p,
    #bbpress-forums .hentry .bbp-reply-content p {
    	@bbpress_replies
    }
    /* @bbpress_notices */
    #bbpress-forums div.bbp-template-notice p {
    	@bbpress_notices
    }
    /* @bbpress_pagination */
    #bbpress-forums .bbp-pagination-count,
    #bbpress-forums .page-numbers {
    	@bbpress_pagination
    }
    /* @bbpress_topic */
    #bbpress-forums .bbp-topic-started-by,
    #bbpress-forums .bbp-topic-started-by a,
    #bbpress-forums .bbp-topic-started-in,
    #bbpress-forums .bbp-topic-started-in a {
    	@bbpress_topic
    }
    
    /* @login_text_color */
    :root {
        --td_login_text_color: @login_text_color;
    }
    /* @login_button_background */
    :root {
        --td_login_button_background: @login_button_background;
    }
    /* @login_button_color */
    :root {
        --td_login_button_color: @login_button_color;
    }
    /* @login_hover_background */
    :root {
        --td_login_hover_background: @login_hover_background;
    }
    /* @login_hover_color */
    :root {
        --td_login_hover_color: @login_hover_color;
    }
    /* @login_gradient_one */
    :root {
        --td_login_gradient_one: @login_gradient_one;
        --td_login_gradient_two: @login_gradient_two;
    }


    /* @login_background_image */
    .white-popup-block:before {
        background-image: url('@login_background_image');
    }

    /* @login_background_repeat */
    :root {
        --td_login_background_repeat: @login_background_repeat;
    }

    /* @login_background_size */
    :root {
        --td_login_background_size: @login_background_size;
    }

    /* @login_background_position */
    :root {
        --td_login_background_position: @login_background_position;
    }

    /* @login_background_opacity */
    :root {
        --td_login_background_opacity: @login_background_opacity;
    }
    
    /* @login_general */
	.white-popup-block,
	.white-popup-block .wpb_button {
		@login_general
	}
    
    /* @woo_general */
	.woocommerce {
		@woo_general
	}
	
    /* @tds_page_layout_size */
    .td_cl .td-container {
        width: 100%;
    }
    @media (min-width: 768px) and (max-width: 1018px) {
        .td_cl {
            padding: 0 14px;
        }
    }
    @media (max-width: 767px) {
        .td_cl .td-container {
            padding: 0;
        }
    }
    @media (min-width: 1019px) and (max-width: 1140px) {
        .td_cl.stretch_row_content_no_space {
            padding-left: 20px;
            padding-right: 20px;
        }
    }
    @media (min-width: 1141px) {
        .td_cl.stretch_row_content_no_space {
            padding-left: 24px;
            padding-right: 24px;
        }
    }
        
    /* @loader_background_image */
    .td-loader-gif::before {
        content: '';
        width: 100%;
        height: 100%;
        position: absolute;
        top: 0;
        left: 0;
        background-image: url('@loader_background_image');
        background-size: contain;
        animation: pulse 0.8s infinite;
        transition: none !important;
        background-repeat: no-repeat;
        background-position: center center;
    }
    @keyframes pulse {
        0% {opacity: 0.6;}
        50% {opacity: 1.0;}
        100% {opacity: 0.6;}
    }
     .td-lb-box {
        display: none !important;
    }
    
    /* @loader_image_bg_color */
    .td-loader-gif {
        background-color: @loader_image_bg_color;
    }


    /* @to_top_style2_fill_color */
    .td-scroll-up[data-style='style2'] .td-scroll-up-tooltip {
        background-color: @to_top_style2_fill_color;
    }
    .td-scroll-up[data-style='style2'] .td-scroll-up-tt-arrow,
    .td-scroll-up[data-style='style2'] .td-scroll-up-arrow {
        fill: @to_top_style2_fill_color;
    }
    .td-scroll-up[data-style='style2'] .td-scroll-up-progress-circle path {
        stroke: @to_top_style2_fill_color;
    }

    /* @to_top_style2_empty_color */
    .td-scroll-up[data-style='style2'] {
        box-shadow: inset  0 0 0 2px @to_top_style2_empty_color;
    }

    </style>
    ";


    $td_css_compiler = new td_css_compiler($raw_css);
    //the template directory uri
    $td_css_compiler->load_setting_raw('get_template_directory_uri', get_template_directory_uri());


    //get $typography array from db and added to generated css
    $td_typography_array = td_fonts::td_get_typography_sections_from_db();
    if(is_array($td_typography_array) and !empty($td_typography_array)) {
        foreach ($td_typography_array as $section_id => $section_css_array) {
            switch ( $section_id ) {
                case 'custom_def_googl_f_1':
                case 'custom_def_googl_f_2':
                    $td_css_compiler->load_setting_raw($section_id, $td_typography_array[$section_id]['font_family']);
                    break;

                default:
                    $td_css_compiler->load_setting_array(array($section_id => $section_css_array));
                    break;
            }
        }
    }

    // mobile menu/search background
    $td_css_compiler->load_setting('mobile_background_image');
    $td_css_compiler->load_setting('mobile_background_repeat');
    $td_css_compiler->load_setting('mobile_background_size');
    $td_css_compiler->load_setting('mobile_background_position');


    // sign in/join background
    $td_css_compiler->load_setting('login_background_image');
    $td_css_compiler->load_setting('login_background_repeat');
    $td_css_compiler->load_setting('login_background_size');
    $td_css_compiler->load_setting('login_background_position');
    $td_css_compiler->load_setting('login_background_opacity');

    // sign in/join color
    $td_css_compiler->load_setting('login_text_color');
    $td_css_compiler->load_setting('login_button_background');
    $td_css_compiler->load_setting('login_button_color');
    $td_css_compiler->load_setting('login_hover_background');
    $td_css_compiler->load_setting('login_hover_color');
    // login gradient color
    $td_css_compiler->load_setting('login_gradient_one');
    $td_css_compiler->load_setting('login_gradient_two');
    //color one is empty
    if (empty($td_css_compiler->settings['login_gradient_one']) && !empty($td_css_compiler->settings['login_gradient_two'])) {
        $td_css_compiler->load_setting_raw('login_gradient_one', 'rgba(42, 128, 203, 0.8)');
    }
    //color two is empty
    if (!empty($td_css_compiler->settings['login_gradient_one']) && empty($td_css_compiler->settings['login_gradient_two'])) {
        $td_css_compiler->load_setting_raw('login_gradient_two', 'rgba(66, 189, 205, 0.8)');
    }
    // loader custom image
    $td_css_compiler->load_setting('loader_background_image');
    // loader custom image
    $td_css_compiler->load_setting('loader_image_bg_color');
    //load the user settings
    // general
    $td_css_compiler->load_setting('theme_color');
    if( td_util::get_option('tds_site_boxed') == 'hide' && 'Newspaper' == TD_THEME_NAME && td_global::is_tdb_registered() ) {
        $td_css_compiler->load_setting_raw('container_transparent', 1);
    }
    $td_css_compiler->load_setting('header_color');
    $td_css_compiler->load_setting('text_header_color');

    $excl_label_translation = __td('EXCLUSIVE', TD_THEME_NAME);
    if( $excl_label_translation != 'EXCLUSIVE' ) {
        $td_css_compiler->load_setting_raw('excl_label', $excl_label_translation);
    }

    // mobile menu
    $td_css_compiler->load_setting('mobile_menu_color');
    $td_css_compiler->load_setting('mobile_icons_color');
    $td_css_compiler->load_setting('mobile_text_color');
    $td_css_compiler->load_setting('mobile_text_active_color');

    // menu gradient color
    $td_css_compiler->load_setting('mobile_gradient_one_mob');
    $td_css_compiler->load_setting('mobile_gradient_two_mob');
    //color one is empty
    if (empty($td_css_compiler->settings['mobile_gradient_one_mob']) && !empty($td_css_compiler->settings['mobile_gradient_two_mob'])) {
        $td_css_compiler->load_setting_raw('mobile_gradient_one_mob', '#333145');
    }
    //color two is empty
    if (!empty($td_css_compiler->settings['mobile_gradient_one_mob']) && empty($td_css_compiler->settings['mobile_gradient_two_mob'])) {
        $td_css_compiler->load_setting_raw('mobile_gradient_two_mob', '#b8333e');
    }


    $td_css_compiler->load_setting('mobile_button_background_mob');
    $td_css_compiler->load_setting('mobile_button_color_mob');

	// pages ---------
	$td_css_compiler->load_setting('page_title_color');
	$td_css_compiler->load_setting('page_content_color');
	$td_css_compiler->load_setting('page_h_color');

	// custom layout for page
    $custom_layout = td_util::get_option('tds_page_layout_size');
    if( $custom_layout != '' ) {
        $td_css_compiler->load_setting_raw('tds_page_layout_size', $custom_layout);
    }

    // thumb placeholder ---------
    $thumb_placeholder = td_util::get_option('tds_thumb_placeholder');
    if( $thumb_placeholder != '' ) {
        $td_css_compiler->load_setting_raw('thumb_placeholder', $thumb_placeholder);
    }


    //load the selection color
    $tds_theme_color = td_util::get_option('tds_theme_color');
    if (!empty($tds_theme_color)) {
        //the select
        $td_css_compiler->load_setting_raw('select_color', td_util::adjustBrightness($tds_theme_color, 50));

        //the sliders text
        if ( td_util::is_rgba( $tds_theme_color ) ) {
            $td_css_compiler->load_setting_raw('slider_text', $tds_theme_color);
        } else {
            $td_css_compiler->load_setting_raw('slider_text', td_util::hex2rgba($tds_theme_color, 0.7));
        }
    }


    /**
     * add td_fonts_css_buffer from database into the source of the page
     *
     * td_fonts_css_buffer : used to store the css generated for custom font files in the database
     */
    $td_fonts_css_buffer = td_fonts::td_add_fonts_css_buffer();



    /* add block styles */
    $td_block_styles = td_options::get_array('td_block_styles');

    //check if we have something set by the user
    if(!empty($td_block_styles)) {
        foreach($td_block_styles as $style_name => $array_style_options) {
            foreach($array_style_options as $option_key => $option_val){
                if(!empty($td_block_styles[$style_name][$option_key])) {

                    $option_name_generator = str_replace('tds_', $style_name . '_', $option_key);

                    switch ($option_key) {
                        case 'tds_block_drop_down_background_color':
                            $td_css_compiler->load_setting_raw($option_name_generator, td_util::hex2rgba($td_block_styles[$style_name][$option_key], 0.95));
                            $td_css_compiler->load_setting_raw($option_name_generator . '_ie8' , $td_block_styles[$style_name][$option_key]);
                            break;

                        case 'tds_block_module_post_comments_box_background_color':
                            $td_css_compiler->load_setting_raw($option_name_generator , $td_block_styles[$style_name][$option_key]);

                            //converting hex color to rgb
                            $rgb_color = td_util::html2rgb($td_block_styles[$style_name][$option_key]);

                            //converting rgb to hsl
                            $hsl_color = td_util::rgb2Hsl($rgb_color[0], $rgb_color[1], $rgb_color[2]);

                            //this is a hack for HLS color: red is 0 in HLS and no output is generated
                            if(intval($hsl_color[0] == 0)) {
                                $hsl_color[0] = 1;
                            }

                            $td_css_compiler->load_setting_raw($option_name_generator . '_after' , $hsl_color[0]);
                            break;

                        default:
                            $td_css_compiler->load_setting_raw($option_name_generator, $td_block_styles[$style_name][$option_key]);
                    }
                }
            }
        }
    }


    /* back to top button style */
    if ( td_util::get_option('tds_to_top') != 'hide' ) {
        $tds_to_top_style = td_util::get_option('tds_to_top_style');
        $tds_to_top_style = !empty($tds_to_top_style) ? $tds_to_top_style : 'style1';

        $td_css_compiler->load_setting_raw('tds_to_top_style_general', 1);
        $td_css_compiler->load_setting_raw('tds_to_top_' . $tds_to_top_style, 1);

        // Load style specific rules
        switch ( $tds_to_top_style ) {
            case 'style2':
                $td_css_compiler->load_setting('to_top_style2_fill_color');
                $td_css_compiler->load_setting('to_top_style2_empty_color');
                break;
        }
    }


    // output the style
    return td_resources_optimize::css_minifier($td_fonts_css_buffer . $td_css_compiler->compile_css());

}

