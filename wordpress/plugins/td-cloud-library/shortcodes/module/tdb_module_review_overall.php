<?php

/**
 * Class tdb_module_review_overall - shortcode for cloud template modules (renders post title)
 */
class tdb_module_review_overall extends tdb_module_template_part {

    public function get_custom_css() {

		$style_selector = self::$style_selector;
		$style_atts_uid = self::$style_atts_uid;
		

        $compiled_css = '';

        $raw_css = "<style>
		
			/* @style_general_tdb_module_review_overall */
			.tdb_module_review_overall {
				display: flex;
				align-items: center;
				position: relative;
				margin: 0;
			}
			.tdb_module_review_overall .td-element-style {
			    z-index: -1;
			}
			.tdb_module_review_overall .tdb-muro-label {
				margin-left: 12px;
				font-family: -apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Oxygen-Sans,Ubuntu,Cantarell,\"Helvetica Neue\",sans-serif;
				font-size: 13px;
				line-height: 1;
				font-weight: 600;
				color: #7A828B;
			}



			/* @tdb_mts_align_horiz_$style_atts_uid */
			.$style_selector {
				justify-content: @tdb_mts_align_horiz_$style_atts_uid;
			}

			/* @tdb_mts_excerpt_col_$style_atts_uid */
			.$style_selector {
				column-count: @tdb_mts_excerpt_col_$style_atts_uid;
			}
			/* @tdb_mts_excerpt_gap_$style_atts_uid */
			.$style_selector {
				column-gap: @tdb_mts_excerpt_gap_$style_atts_uid;
			}



			/* @tdb_mts_show_stars_$style_atts_uid */
			.$style_selector .td-user-rev-stars {
				display: @tdb_mts_show_stars_$style_atts_uid;
			}
			/* @tdb_mts_stars_v_alignment_$style_atts_uid */
			.$style_selector .td-user-rev-stars {
				position: relative;
				top: @tdb_mts_stars_v_alignment_$style_atts_uid;
			}
			/* @tdb_mts_space_btw_stars_$style_atts_uid */
			.$style_selector .td-user-rev-star:not(:last-child) {
				margin-right: @tdb_mts_space_btw_stars_$style_atts_uid;
			}
			/* @tdb_mts_icons_size_$style_atts_uid */
			.$style_selector .td-user-rev-star {
				font-size: @tdb_mts_icons_size_$style_atts_uid;
			}

			/* @tdb_mts_show_label_$style_atts_uid */
			.$style_selector .tdb-muro-label {
				display: @tdb_mts_show_label_$style_atts_uid;
			}
			/* @tdb_mts_label_space_$style_atts_uid */
			.$style_selector .tdb-muro-label {
				margin-left: @tdb_mts_label_space_$style_atts_uid;
			}
			
                
                
			/* @tdb_mts_empty_color_$style_atts_uid */
			.$style_selector .td-user-rev-star-empty {
				color: @tdb_mts_empty_color_$style_atts_uid;
			}
			.$style_selector .td-user-rev-star-empty svg {
				fill: @tdb_mts_empty_color_$style_atts_uid;
			}
			/* @tdb_mts_full_color_$style_atts_uid */
			.$style_selector .td-user-rev-star-full,
			.$style_selector .td-user-rev-star-half {
				color: @tdb_mts_full_color_$style_atts_uid;
			}
			.$style_selector .td-user-rev-star-full svg,
			.$style_selector .td-user-rev-star-half svg {
				fill: @tdb_mts_full_color_$style_atts_uid;
			}

			/* @tdb_mts_label_color_$style_atts_uid */
			.$style_selector .tdb-muro-label {
				color: @tdb_mts_label_color_$style_atts_uid;
			}



			/* @tdb_mts_f_label_$style_atts_uid */
			.$style_selector .tdb-muro-label {
				@tdb_mts_f_label_$style_atts_uid
			}
		
		</style>";

        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts() );

        $compiled_css .= $td_css_res_compiler->compile_css();
        return $compiled_css;

    }

    static function cssMedia( $res_ctx ) {

		$style_atts_uid = self::$style_atts_uid;




		/* --
		-- GENERAL
		-- */
		$res_ctx->load_settings_raw( 'style_general_tdb_module_review_overall', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_module_review_overall_composer', 1 );
        }

		
		// Horizontal align
		$align_horiz = $res_ctx->get_shortcode_att( 'align_horiz' );
		switch( $align_horiz ) {
			case '':
			case 'content-horiz-left':
				$align_horiz = 'flex-start';
				break;
			case 'content-horiz-center':
				$align_horiz = 'center';
				break;
			case 'content-horiz-right':
				$align_horiz = 'flex-end';
				break;
		}
		$res_ctx->load_settings_raw( 'tdb_mts_align_horiz_' . $style_atts_uid, $align_horiz );




		/* --
		-- STARS
		-- */
		/* -- Layout -- */
		// show stars
		$show_stars = $res_ctx->get_shortcode_att('show_stars');
		$show_stars = $show_stars != '' ? $show_stars : 'flex';
		$res_ctx->load_settings_raw('tdb_mts_show_stars_' . $style_atts_uid, $show_stars);

		// icons size
		$icons_size = $res_ctx->get_shortcode_att('icons_size');
		$icons_size .= $icons_size != '' && is_numeric( $icons_size ) ? 'px' : '';
		$res_ctx->load_settings_raw('tdb_mts_icons_size_' . $style_atts_uid, $icons_size);

		// icons (stars) vertical alignment
		$res_ctx->load_settings_raw('tdb_mts_stars_v_alignment_' . $style_atts_uid, $res_ctx->get_shortcode_att('stars_v_alignment') . 'px');

		// space between stars
		$space_btw_stars = $res_ctx->get_shortcode_att('space_btw_stars');
		$space_btw_stars .= $space_btw_stars != '' && is_numeric( $space_btw_stars ) ? 'px' : '';
		$res_ctx->load_settings_raw('tdb_mts_space_btw_stars_' . $style_atts_uid, $space_btw_stars);


		/* -- Colors -- */
		$res_ctx->load_settings_raw('tdb_mts_full_color_' . $style_atts_uid, $res_ctx->get_shortcode_att('full_color'));
        $res_ctx->load_settings_raw('tdb_mts_empty_color_' . $style_atts_uid, $res_ctx->get_shortcode_att('empty_color'));




		/* --
		-- LABEL
		-- */
		/* -- Layout -- */
		// Show label
		$show_label = $res_ctx->get_shortcode_att('show_label');
		$show_label = $show_label != '' ? $show_label : 'block';
		$res_ctx->load_settings_raw('tdb_mts_show_label_' . $style_atts_uid, $show_label);

        // Label space
        $label_space = $res_ctx->get_shortcode_att('label_space');
		$label_space .= $label_space != '' && is_numeric( $label_space ) ? 'px' : '';
        $res_ctx->load_settings_raw('tdb_mts_label_space_' . $style_atts_uid, $label_space);


		/* -- Colors -- */
        $res_ctx->load_settings_raw('tdb_mts_label_color_' . $style_atts_uid, $res_ctx->get_shortcode_att('label_color'));


		/* -- Fonts -- */
        $res_ctx->load_font_settings( 'f_label', '', 'tdb_mts_', '_' . $style_atts_uid );

	}


    function render( $atts, $content = null ) {

		$additional_classes_array = array();


		/* -- Call the parent render method -- */
        parent::render($atts);



		/* -- Block atts -- */
		// Reviews source
		$source = $this->get_att('source') != '' ? $this->get_att('source') : 'author';

		// Hide empty
		$hide_empty = $this->get_att('hide_empty');

		// Rating stars
		$full_star_icon = $this->get_icon_att( 'tdicon_full' );
        $full_star_icon_data = '';
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $full_star_icon_data = 'data-td-svg-icon="' . $this->get_att( 'tdicon_full' ) . '"';
        }
        $full_star_icon_html = '';
        if ( !empty( $full_star_icon ) ) {
            if( base64_encode( base64_decode( $full_star_icon ) ) == $full_star_icon ) {
                $full_star_icon_html = base64_decode( $full_star_icon ) ;
            } else {
                $full_star_icon_html = '<i class="' . $full_star_icon . '"></i>';
            }
        }

        $half_star_icon = $this->get_icon_att( 'tdicon_half' );
        $half_star_icon_data = '';
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $half_star_icon_data = 'data-td-svg-icon="' . $this->get_att( 'tdicon_half' ) . '"';
        }
        $half_star_icon_html = '';
        if ( !empty( $half_star_icon ) ) {
            if( base64_encode( base64_decode( $half_star_icon ) ) == $half_star_icon ) {
                $half_star_icon_html = base64_decode( $half_star_icon ) ;
            } else {
                $half_star_icon_html = '<i class="' . $half_star_icon . '"></i>';
            }
        }

        $empty_star_icon = $this->get_icon_att( 'tdicon_empty' );
        $empty_star_icon_data = '';
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $empty_star_icon_data = 'data-td-svg-icon="' . $this->get_att( 'tdicon_empty' ) . '"';
        }
        $empty_star_icon_html = '';
        if ( !empty( $empty_star_icon ) ) {
            if( base64_encode( base64_decode( $empty_star_icon ) ) == $empty_star_icon ) {
                $empty_star_icon_html = base64_decode( $empty_star_icon ) ;
            } else {
                $empty_star_icon_html = '<i class="' . $empty_star_icon . '"></i>';
            }
        }



		/* -- Retrieve the module post data -- */
		$post_obj = self::$post_obj;

		$reviews_average = '';

		if ( gettype($post_obj) === 'object' && get_class($post_obj) === 'WP_Post' ) {

			$post_obj_id = $post_obj->ID;

			if( $source == 'author' ) {
				$post_has_review_meta = self::read_post_theme_settings_meta( 'has_review' );

				if( !empty( $post_has_review_meta ) ) {
					$post_review_type_meta = '';

					switch( $post_has_review_meta ) {
						case 'rate_stars':
							$post_review_type_meta = self::read_post_theme_settings_meta( 'p_review_stars' );
							break;
						case 'rate_percent':
							$post_review_type_meta = self::read_post_theme_settings_meta( 'p_review_percents' );
							break;
						case 'rate_point':
							$post_review_type_meta = self::read_post_theme_settings_meta( 'p_review_points' );
							break;
					}

					if( !empty( $post_review_type_meta ) ) {
						$post_review_total = 0;
						$post_review_count = 0;

						foreach ( $post_review_type_meta as $section ) {
							if ( !empty( $section['rate'] ) ) {
								$post_review_total = $post_review_total + $section['rate'];
								$post_review_count++;
							}
						}

						$reviews_average = ( ( $post_review_total / $post_review_count ) * 2 ) / 2;
					}
				}
			} else {
				if( get_post_type($post_obj_id) == 'tdc-review' ) {
					$review_overall_rating = td_util::get_overall_review_rating($post_obj_id);
	
					if( $review_overall_rating ) {
						$reviews_average = $review_overall_rating;
					}
				} else {
					$post_reviews_overall_rating = td_util::get_overall_post_rating($post_obj_id);
	
					if( $post_reviews_overall_rating ) {
						$reviews_average = $post_reviews_overall_rating;
					}
				}
			}

			if( empty( $reviews_average ) ) {
				if( 
					( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) &&
					tdb_state_template::get_template_type() == 'module' 
				) {
					$reviews_average = '3.5';
				} else if( $hide_empty == '' ) {
					$reviews_average = '0';
				}
			}

		} else {
			$reviews_average = '3.5';
		}



		/* -- Output the module element HTML -- */
        $buffy = '';

		if( $reviews_average == '' ) {
			return $buffy;
		}


		$buffy .= '<div class="' . $this->get_block_classes($additional_classes_array) . '" ' . $this->get_block_html_atts() . '>';
            // get the block css
            $buffy .= $this->get_block_css();

            // get the js for this block
            $buffy .= $this->get_block_js();

			$buffy .= td_util::display_user_ratings_stars($reviews_average, $full_star_icon_html, $full_star_icon_data, $half_star_icon_html, $half_star_icon_data, $empty_star_icon_html, $empty_star_icon_data);
			$buffy .= '<div class="tdb-muro-label">' . $reviews_average . ' ' . __td( 'out of 5', TD_THEME_NAME )  . '</div>';
		$buffy .= '</div>';


        return $buffy;

    }

}