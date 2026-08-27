<?php

class td_block_page_content_renderer extends td_block {

    protected static $in_composer = false;

    protected $additional_classes = array();
    protected $additional_html_atts = array();




    /**
     * Class constructor.
     *
     * @return void
     */
    function __construct() {

        /* --
        -- Disable loop block features. This block does not use a loop and it doesn't need to run a query.
        -- */
        parent::disable_loop_block_features();


        /* --
        -- Set the 'in_composer' flag.
        -- */
        self::$in_composer = td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax();

    }




    /**
     * Compiles the shortcode's CSS rules.
     *
     * @return string
     */
	public function get_custom_css() {

        $compiled_css = '';

		/** @noinspection CssInvalidAtRule */
		$raw_css =
            "<style>

                /* @style_general_td_block_page_content_renderer */
                .td_block_page_content_renderer {
                    margin-bottom: 0;
                }
                .td_block_page_content_renderer .tdc-row:not([class*='stretch_row_']),
                .td_block_page_content_renderer .tdc-row-composer:not([class*='stretch_row_'])  {
                    width: auto !important;
                    max-width: 1240px;
                }
                
                /* @style_general_td_block_page_content_renderer_composer */
                .td_block_page_content_renderer:after {
                    content: 'Page content renderer';
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    padding: 5px 7px 5px;
                    background-color: #000;
                    font-size: 12px;
                    line-height: 1.15;
                    color: #fff;
                    pointer-events: none;
                    z-index: 99999;
                }
                .td_block_page_content_renderer .tdc-zone-composer {
                    position: relative;
                    opacity: .3;
                    filter: grayscale(1);
                    pointer-events: none;
                }

            </style>";

		$td_css_res_compiler = new td_css_res_compiler( $raw_css );
		$td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts() );

		$compiled_css .= $td_css_res_compiler->compile_css();

		return $compiled_css;

	}




    /**
     * Sets up the various CSS rules which will later be compiled.
     *
     * @param td_res_context $res_ctx
     * @return void
     */
	static function cssMedia( $res_ctx ) {

        /* --
        -- General style.
        -- */
        $res_ctx->load_settings_raw('style_general_td_block_page_content_renderer', 1);
        $res_ctx->load_settings_raw('style_general_td_block_page_content_renderer_composer', self::$in_composer);

	}




    /**
     * Renders the shortcode.
     *
     * @param array $atts
     * @param null $content
     * @return string
     */
	function render( $atts, $content = null ) {

        /* -- Call the parent render method. -- */
        parent::render( $atts );


        /* -- Prevent rendering inside another page content renderer block. -- */
        if ( td_global::get_td_block_page_content_renderer() ) {
            return '';
        }



        /* --
        -- Shortcode attributes.
        -- */
        /* -- Page ID. -- */
        $page_id = $this->get_att('page_id');

        // If no page was selected:
        // - return an empty string on front-end;
        // - return an error in TD Composer.
        if ( empty($page_id) ) {
            if ( self::$in_composer ) {
                return $this->get_block_error('Please select a page to be rendered.');
            }

            return '';
        }



        /* --
        -- Get the page contents.
        -- */
        $page_obj = get_post($page_id);

        // If page does not exist:
        // - return an empty string on front-end;
        // - return an error in TD Composer.
        if ( empty($page_obj) ) {
            if ( self::$in_composer ) {
                return $this->get_block_error('The selected page no longer exists.');
            }

            return '';
        }

        // If the ID is not for a page:
        // - return an empty string on front-end;
        // - return an error in TD Composer.
        if ( $page_obj->post_type != 'page' ) {
            if ( self::$in_composer ) {
                return $this->get_block_error('The selected ID is not a page.');
            }

            return '';
        }

        // If the selected page is the same as the one we are currently on:
        // - return an empty string on front-end;
        // - return an error in TD Composer.
        $current_page_id = is_page() ? get_the_ID() : null;

        if ( !empty($current_page_id) && $current_page_id == $page_id ) {
            if ( self::$in_composer ) {
                return $this->get_block_error('The selected page is the same as the one you are currently on. Rendering of this page is disabled to avoid an infinite loop.');
            }

            return '';
        }
        
        
        /* -- Filter the content. -- */
        td_global::set_in_element( true );
        td_global::set_td_block_page_content_renderer( true );
        $page_content = $page_obj->post_content;

        // If page content is empty:
        // - return an empty string on front-end;
        // - return an error in TD Composer.
        if ( empty($page_content) ) {
            if ( self::$in_composer ) {
                return $this->get_block_error('The selected page has no content that can be rendered.');
            }

            return '';
        }

        if ( is_plugin_active('td-subscription/td-subscription.php') && has_filter('the_content', array(tds_email_locker::instance(), 'lock_content')) ) {
            $has_content_filter = true;
            remove_filter( 'the_content', array(tds_email_locker::instance(), 'lock_content') );
        }

        $page_content = preg_replace('/\[tdm_block_popup.*?\]/i', '', $page_content);
        $page_content = apply_filters('the_content', $page_content);
        $page_content = str_replace(']]>', ']]&gt;', $page_content);

        // the has_filter check is made for plugins, like bbpress, who think it's okay to remove all filters on 'the_content'
        if ( !has_filter('the_content', 'do_shortcode') ) {
            $page_content = do_shortcode( $page_content );
        }

        if ( !empty($has_content_filter) ) {
            add_filter('the_content', array(tds_email_locker::instance(), 'lock_content'));
        }

        td_global::set_td_block_page_content_renderer( false );
        td_global::set_in_element( false );



        /* --
        -- Build the shortcode HTML.
        -- */
        $buffy = '<div class="' . $this->get_block_classes($this->additional_classes) . '" ' . $this->get_block_html_atts($this->additional_html_atts) . '>';

            // Get the block CSS.
			$buffy .= $this->get_block_css();

            // Get the block JS.
			$buffy .= $this->get_block_js();


            $buffy .= $page_content;

		$buffy .= '</div>';



        /* --
        -- Return the shortcode HTML.
        -- */
		return $buffy;

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

        $buffy = '<div class="' . $this->get_block_classes() . '" ' . $this->get_block_html_atts() . '>';
            $buffy .= td_util::get_block_error('Page content renderer', $message);
        $buffy .= '</div>';

        return $buffy;

    }

}
