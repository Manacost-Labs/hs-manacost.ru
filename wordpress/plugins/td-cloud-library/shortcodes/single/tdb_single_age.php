<?php

/**
 * Class td_single_date
 */

class tdb_single_age extends td_block {

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

                /* @style_general_single_date */
                $general_block_class .tdb_single_age {
                  line-height: 30px;
                }
                $general_block_class .tdb_single_age a {
                  vertical-align: middle;
                }
                $general_block_class .tdb_single_age .tdb-date-icon-svg {
                  position: relative;
                  line-height: 0;
                }
                $general_block_class .tdb_single_age svg {
                  height: auto;
                }
                $general_block_class .tdb_single_age svg,
                $general_block_class .tdb_single_age svg * {
                  fill: #444;
                }

                
                
                /* @make_inline */
                .$unique_block_class {
                    display: inline-block;
                }
                /* @float_right */
                .$unique_block_class {
                    float: right;
                }
                
                /* @icon_size */
                .$unique_block_class i {
                    font-size: @icon_size;
                }
                /* @icon_svg_size */
                .$unique_block_class svg {
                    width: @icon_svg_size;
                }
                /* @icon_space */
                .$unique_block_class .tdb-date-icon {
                    margin-right: @icon_space;
                }
				/* @icon_align */
				.$unique_block_class .tdb-date-icon {
					position: relative;
					top: @icon_align;
				}
                
                /* @date_color */
				.$unique_block_class {
					color: @date_color;
				}
				.$unique_block_class svg,
				.$unique_block_class svg * {
					fill: @date_color;
				}
                /* @icon_color */
				.$unique_block_class i {
					color: @icon_color;
				}
				.$unique_block_class svg,
				.$unique_block_class svg * {
					fill: @icon_color;
				}
				
				/* @f_date */
				.$unique_block_class {
					@f_date
				}
				
			</style>";


        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts() );

        $compiled_css .= $td_css_res_compiler->compile_css();
        return $compiled_css;
    }

    static function cssMedia( $res_ctx ) {

        $res_ctx->load_settings_raw( 'style_general_post_meta', 1 );
        $res_ctx->load_settings_raw( 'style_general_single_date', 1 );

        // make inline
        $res_ctx->load_settings_raw( 'make_inline', $res_ctx->get_shortcode_att('make_inline') );

        // float right
        $res_ctx->load_settings_raw( 'float_right', $res_ctx->get_shortcode_att('float_right') );



        /*-- ICON -- */
        $icon = $res_ctx->get_icon_att('tdicon');
        // icon size
        $icon_size = $res_ctx->get_shortcode_att('icon_size');
        if( base64_encode( base64_decode( $icon ) ) == $icon ) {
            $res_ctx->load_settings_raw( 'icon_size', $icon_size );
            if( $icon_size != '' ) {
                if( is_numeric( $icon_size ) ) {
                    $res_ctx->load_settings_raw( 'icon_svg_size', $icon_size . 'px' );
                }
            } else {
                $res_ctx->load_settings_raw( 'icon_svg_size', '14px' );
            }
        } else {
            $res_ctx->load_settings_raw( 'icon_size', $icon_size );
            if( $icon_size != '' ) {
                if( is_numeric( $icon_size ) ) {
                    $res_ctx->load_settings_raw( 'icon_size', $icon_size . 'px' );
                }
            } else {
                $res_ctx->load_settings_raw( 'icon_size', '14px' );
            }
        }

        // icon space
        $icon_space = $res_ctx->get_shortcode_att('icon_space');
        $res_ctx->load_settings_raw( 'icon_space', $icon_space );
        if( $icon_space != '' ) {
            if( is_numeric( $icon_space ) ) {
                $res_ctx->load_settings_raw( 'icon_space', $icon_space . 'px' );
            }
        } else {
            $res_ctx->load_settings_raw( 'icon_space', '5px' );
        }

        // icon_align
        $icon_align = $res_ctx->get_shortcode_att('icon_align');
        if ( $icon_align != 0 || !empty($icon_align) ) {
            $res_ctx->load_settings_raw( 'icon_align', $icon_align . 'px' );
        }



        /*-- COLORS -- */
        $res_ctx->load_settings_raw( 'date_color', $res_ctx->get_shortcode_att('date_color') );
        $res_ctx->load_settings_raw( 'icon_color', $res_ctx->get_shortcode_att('icon_color') );



        /*-- FONTS -- */
        $res_ctx->load_font_settings( 'f_date' );

    }

    /**
     * Disable loop block features. This block does not use a loop and it doesn't need to run a query.
     */
    function __construct() {
        parent::disable_loop_block_features();
    }


    function render( $atts, $content = null ) {

        parent::render( $atts ); // sets the live atts, $this->atts, $this->block_uid, $this->td_query (it runs the query)


        /* -- Flag to determine if we are in composer. -- */
        $in_composer = tdc_state::is_live_editor_iframe() || tdc_state::is_live_editor_ajax();



        /* --
        -- Post data.
        -- */
        /* -- Retrieve post data. -- */
        global $tdb_state_single;
        $post_age_data = $tdb_state_single->post_age->__invoke();


        /* -- Timestamp. -- */
        $post_timestamp = $post_age_data['timestamp'];

        /* -- Calculate difference between current time and post time. -- */
        $current_time = current_time( 'timestamp' );
        $post_time_diff = (int) abs( $current_time - $post_timestamp );

        /* -- Post age. -- */
        $post_age = $post_age_data['age'];



        /* --
        -- Shortcode settings.
        -- */
        /* -- Hide conditions. -- */
        // Hide enabled.
        $hide_condition_enabled = $this->get_att( 'hide' ) == 'yes';

        // Hide condition.
        $hide_condition = $this->get_att( 'hide_cond' ) != '' ? $this->get_att( 'hide_cond' ) : 'newer';

        // Hide condition value.
        $hide_condition_value_att = $this->get_att( 'hide_cond_val' );
        $hide_condition_value = 7;

        if( !empty( $hide_condition_value_att ) && is_numeric( $hide_condition_value_att ) ) {
            $hide_condition_value = (int) $hide_condition_value_att;
        }


        /* -- Additional text. -- */
        // Text.
        $additional_text = $this->get_att( 'additional_text' );

        // Text position
        $additional_text_before = $this->get_att( 'additional_text_before' ) !== '';


        /* -- Icon -- */
        $date_icon = $this->get_icon_att( 'tdicon' );
        $date_icon_data = '';
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $date_icon_data = 'data-td-svg-icon="' . $this->get_att('tdicon') . '"';
        }
        $date_icon_html = '';
        if ( $date_icon != '' ) {
            if( base64_encode( base64_decode( $date_icon ) ) == $date_icon ) {
                $date_icon_html = '<span class="tdb-date-icon tdb-date-icon-svg" ' . $date_icon_data . '>' . base64_decode( $date_icon ) . '</span>';
            } else {
                $date_icon_html = '<i class="tdb-date-icon ' . $date_icon . '"></i>';
            }
        }


        $buffy = ''; //output buffer

        if(
            !$in_composer && $hide_condition_enabled &&
            (
                ( $hide_condition == 'newer' && $post_time_diff < ( $hide_condition_value * DAY_IN_SECONDS ) ) ||
                ( $hide_condition == 'older' && $post_time_diff > ( $hide_condition_value * DAY_IN_SECONDS ) )
            )
        ) {
            return $buffy;
        }

        $buffy .= '<div class="' . $this->get_block_classes() . ' tdb-post-meta" ' . $this->get_block_html_atts() . '>';

            //get the block css
            $buffy .= $this->get_block_css();

            //get the js for this block
            $buffy .= $this->get_block_js();


            $buffy .= '<div class="tdb-block-inner td-fix-index">';

                $buffy .= $date_icon_html;

                $buffy .= ( !empty( $additional_text ) && $additional_text_before ? $additional_text . ' ' : '' ) . $post_age . ( !empty( $additional_text ) && !$additional_text_before ? ' ' . $additional_text : '' );

            $buffy .= '</div>';

        $buffy .= '</div> <!-- ./block -->';

        return $buffy;

    }

}