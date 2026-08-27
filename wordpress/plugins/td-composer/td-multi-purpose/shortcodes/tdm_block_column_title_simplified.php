<?php
class tdm_block_column_title_simplified extends td_block {

    protected $atts_with_values = array();


    /**
     * Disable loop block features. This block does not use a loop and it dosn't need to run a query.
     */
    function __construct() {
        parent::disable_loop_block_features();
    }


    /**
     * Renders the shortcode.
     *
     * @param array $atts
     * @param null $content
     * @return string
     */
	function render($atts, $content = null) {

        /* -- Call the parent render method. -- */
		parent::render($atts);



        /* --
        -- Shortcode attributes.
        -- */
        /* -- Retrieve the values for the shortcode atts, include those -- */
        /* -- for the title styles. -- */
        $this->atts_with_values = shortcode_atts(
			array_merge(
				td_api_multi_purpose::get_mapped_atts( __CLASS__ ),
				td_api_style::get_style_group_params( 'tds_title_simplified' )
            ),
            $atts
        );


        /* -- Title text. -- */
        // Check whether the title text field is empty. If so:
        // * in composer: return a block error;
        // * on front-end: return an empty string;
        if ( $this->get_att('title_text') == '' ) {
            // If in composer output warning, otherwise return an empty string.
            if ( td_util::tdc_is_live_editor_ajax() || td_util::tdc_is_live_editor_iframe() ) {
                return $this->get_block_error('The title text field is empty.');
            }
            
            return '';
        }



        /* --
        -- Build the shortcode HTML.
        -- */
		$buffy = '';


        /* -- Render the selected title style instance. -- */
        $tds_title_simplified_style = !empty($this->get_att('tds_title_simplified')) ? $this->get_att('tds_title_simplified') : td_util::get_option( 'tds_title_simplified', 'tds_title_simplified_1' );
        $tds_title_simplified_instance = new $tds_title_simplified_style( $this->atts_with_values, $this->block_uid, $this->get_block_css(), $this->get_block_classes(), $this->get_block_html_atts() );

        $buffy .= $tds_title_simplified_instance->render();



        /* --
        -- Return the shortcode HTML.
        -- */
		return $buffy;

	}





    /**
     * Builds block error for composer.
     *
     * @param string $message
     * @return string
     */
    protected function get_block_error( $message ) {

        $buffy = '<div class="' . $this->get_block_classes() . '" ' . $this->get_block_html_atts() . '>';
            $buffy .= td_util::get_block_error('Column title', $message);
        $buffy .= '</div>';

        return $buffy;

    }

}