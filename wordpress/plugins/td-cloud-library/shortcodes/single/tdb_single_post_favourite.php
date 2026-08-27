<?php

/**
 * Class tdb_single_post_favourite - shortcode for single post
 */
class tdb_single_post_favourite extends td_block {
    private $unique_block_class;
    private $shortcode_atts;

    public function get_custom_css() {
        // $unique_block_class
        $unique_block_class = $this->block_uid;

        $compiled_css = '';

        $raw_css =
            "<style>

                /* @general_style_tdb_single_post_favourite */
                .tdb_single_post_favourite .tdb-block-inner {
                    display: flex;
                }
                
                
                /* @make_inline */
                .$unique_block_class {
                    display: inline-block;
                }
                /* @horiz_align */
                .$unique_block_class .tdb-block-inner {
                    justify-content: @horiz_align;
                }
                
            </style>";

        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts() );

        $compiled_css .= $td_css_res_compiler->compile_css();
        return $compiled_css;
    }

    static function cssMedia( $res_ctx ) {

        /*-- GENERAL-- */
        $res_ctx->load_settings_raw('general_style_tdb_single_post_favourite', 1);



        /*-- LAYOUT -- */
        // make inline
        $res_ctx->load_settings_raw('make_inline', $res_ctx->get_shortcode_att('make_inline'));

        // horizontal align
        $horiz_align = $res_ctx->get_shortcode_att('horiz_align');
        if( $horiz_align == '' || $horiz_align == 'content-horiz-left' ) {
            $res_ctx->load_settings_raw('horiz_align', 'flex-start');
        } else if( $horiz_align == 'content-horiz-center' ) {
            $res_ctx->load_settings_raw('horiz_align', 'center');
        } else if( $horiz_align == 'content-horiz-right' ) {
            $res_ctx->load_settings_raw('horiz_align', 'flex-end');
        }

    }

    function __construct() {
        parent::disable_loop_block_features();
    }

    function render($atts, $content = null) {

        parent::render($atts);

        global $tdb_state_single;
        $post_id = $tdb_state_single->post_id->__invoke();

        $this->unique_block_class = $this->block_uid;

        $this->shortcode_atts = shortcode_atts(
            array_merge(
                td_global_blocks::get_mapped_atts( __CLASS__ ),
                td_api_style::get_style_group_params( 'tds_single_favorite' )
            ), $atts );

        $tds_single_favorite = $this->get_att('tds_single_favorite');
        if ( empty( $tds_single_favorite ) ) {
            $tds_single_favorite = td_util::get_option( 'tds_single_favorite', 'tds_single_favorite1' );
        }
        $tds_single_favorite_instance = new $tds_single_favorite( $this->shortcode_atts, $this->unique_block_class );


        $buffy = '';

        $buffy .= '<div class="' . $this->get_block_classes()  . '" ' . $this->get_block_html_atts() . '>';

	        //get the block css
	        $buffy .= $this->get_block_css();

	        //get the js for this block
	        $buffy .= $this->get_block_js();


            $buffy .= '<div class="tdb-block-inner td-fix-index">';

                $buffy .= $tds_single_favorite_instance->render('', $post_id);

            $buffy .= '</div>';

            td_resources_load::render_script( TDB_SCRIPTS_URL . '/tdbFavourites.js' . TDB_SCRIPTS_VER, 'tdbFavourites-js', '', 'footer' );

        $buffy .= '</div>';

        return $buffy;
    }
}

