<?php

class td_resources_load {

    /**
     * Maintains a list of all styles loaded.
     */
    private static $styles_loaded = array();




    /**
     * Maintains a list of all scripts loaded.
     */
    private static $scripts_loaded = array();


    /**
     * Render a style.
     *
     * @param string $style_href
     * @param string $tag_id
     *
     * @return string
     */
    public static function render_style( string $style_href, string $tag_id = '' ) : string {

        if( in_array( $style_href, self::$styles_loaded ) ) {
            return '';
        }

        self::$styles_loaded[] = $style_href;

        return '<link rel="stylesheet"' . ( $tag_id != '' ? ' id="' . $tag_id . '"' : '' ) . ' href="' . $style_href . '" type="text/css" />';

    }


    /**
     * Render a script.
     *
     * @param string $script_src
     * @param string $tag_id
     * @param string $load_options
     * @param string $position
     *
     * @return string
     */
    public static function render_script( string $script_src, string $tag_id = '', string $load_options = '', string $position = 'inline' ) : string {

        $in_composer = tdc_state::is_live_editor_ajax() || tdc_state::is_live_editor_iframe();
        $buffy = '';


        /* -- Bail if the script has already been loaded. -- */
        if( in_array( $script_src, self::$scripts_loaded ) ) {
            if( $position == 'footer' ) {
                return false;
            }

            return $buffy;
        }


        /* -- Bail if the script is set to load in the footer -- */
        /* -- and we are in composer. -- */
        if( $position == 'footer' && $in_composer ) {
            return false;
        }


        /* -- Build the script element. -- */
        self::$scripts_loaded[] = $script_src;

        $buffy .= '<script type="text/javascript" ';
            $buffy .= 'src="' . $script_src . '"';
            $buffy .= $tag_id != '' ? ' id="' . $tag_id . '"' : '';
            $buffy .= $load_options !== '' ? ' ' . $load_options : '';
        $buffy .= '></script>';


        /* -- Load the script in the specified location. -- */
        switch( $position ) {
            // Set inline, simply return the script element.
            case 'inline';
                return $buffy;

            // Set in footer, try adding the script.
            case 'footer':
                try {
                    td_js_buffer::add_to_footer( "\n" . $buffy, true );
                } catch ( ErrorException $e ) {
                    return false;
                }
        }


        return true;

    }

}