<?php

/**
 * Class tdb_module_taxonomies - shortcode for cloud template modules (renders post title)
 */
class tdb_module_taxonomies extends tdb_module_template_part {

    public function get_custom_css() {

		$style_selector = self::$style_selector;
		$style_atts_uid = self::$style_atts_uid;
		

        $compiled_css = '';

        $raw_css = "<style>
		
			/* @style_general_tdb_module_taxonomies */
			.tdb_module_taxonomies {
				display: flex;
				flex-wrap: wrap;
				position: relative;
				margin: 0;
			}
			.tdb_module_taxonomies .td-element-style {
			    z-index: -1;
			}
			.tdb_module_taxonomies .tdb-module-term {
				padding: 3px 6px 4px;
				background-color: #222222;
				font-family: var(--td_default_google_font_1, 'Open Sans', 'Open Sans Regular', sans-serif);
				font-size: 10px;
				font-weight: 600;
				line-height: 1;
				color: #fff;
				transition: all 0.2s ease;
				-webkit-transition: all 0.2s ease;
			}
			.tdb_module_taxonomies .tdb-module-term:hover {
				background-color: var(--td_theme_color, #4db2ec);
			}
			.tdb_module_taxonomies .tdb-module-term-sep {
				align-self: center;
				position: relative;
				font-size: 14px;
				color: red;
			}
			.tdb_module_taxonomies .tdb-module-term-sep svg {
				display: block;
				width: 1em;
				height: auto;
			}
			.tdb_module_taxonomies .tdb-module-term-sep svg,
			.tdb_module_taxonomies .tdb-module-term-sep svg * {
				fill: currentColor;
			}		
			.tdb_module_taxonomies .tdb-module-tax-icon {
				align-self: center;
				position: relative;
				font-size: 14px;
			}
			.tdb_module_taxonomies .tdb-module-tax-icon svg {
				display: block;
				width: 1em;
				height: auto;
			}
			.tdb_module_taxonomies .tdb-module-tax-icon svg,
			.tdb_module_taxonomies .tdb-module-tax-icon svg * {
				fill: currentColor;
			}
			
			/* @style_general_tdb_module_taxonomies_composer */
			.tdb_module_taxonomies a {
			    pointer-events: none;
			}



			/* @tdb_mts_align_horiz_$style_atts_uid */
			.$style_selector {
				justify-content: @tdb_mts_align_horiz_$style_atts_uid;
			}

			/* @tdb_mts_tax_space_$style_atts_uid */
			.$style_selector .tdb-module-term-last-in-tax:not(:last-child) {
				margin-right: @tdb_mts_tax_space_$style_atts_uid;
			}
			/* @tdb_mts_space_$style_atts_uid */
			.$style_selector {
				gap: @tdb_mts_space_$style_atts_uid;
			}

			/* @tdb_mts_padding_$style_atts_uid */
			.$style_selector .tdb-module-term {
				padding: @tdb_mts_padding_$style_atts_uid;
			}
			/* @tdb_mts_all_border_$style_atts_uid */
			.$style_selector .tdb-module-term {
				border: @tdb_mts_all_border_$style_atts_uid @tdb_mts_all_border_style_$style_atts_uid @tdb_mts_all_border_color_$style_atts_uid;
			}
			/* @tdb_mts_radius_$style_atts_uid */
			.$style_selector .tdb-module-term {
				border-radius: @tdb_mts_radius_$style_atts_uid;
			}
			
			/* @tdb_mts_tax_ico_size_$style_atts_uid */
			.$style_selector .tdb-module-tax-icon {
				font-size: @tdb_mts_tax_ico_size_$style_atts_uid;
			}
			/* @tdb_mts_tax_ico_space_$style_atts_uid */
			.$style_selector .tdb-module-tax-icon {
				margin-right: @tdb_mts_tax_ico_space_$style_atts_uid;
			}
			/* @tdb_mts_tax_ico_align_$style_atts_uid */
			.$style_selector .tdb-module-tax-icon {
				top: @tdb_mts_tax_ico_align_$style_atts_uid;
			}

			/* @tdb_mts_ico_size_$style_atts_uid */
			.$style_selector .tdb-module-term-sep {
				font-size: @tdb_mts_ico_size_$style_atts_uid;
			}
			/* @tdb_mts_ico_align_$style_atts_uid */
			.$style_selector .tdb-module-term-sep {
				top: @tdb_mts_ico_align_$style_atts_uid;
			}

 

			/* @tdb_mts_bg_$style_atts_uid */
			.$style_selector .tdb-module-term {
				background-color: @tdb_mts_bg_$style_atts_uid !important;
			}
			/* @tdb_mts_bg_h_$style_atts_uid */
			.$style_selector .tdb-module-term:hover {
				background-color: @tdb_mts_bg_h_$style_atts_uid !important;
			}
			/* @tdb_mts_color_$style_atts_uid */
			.$style_selector .tdb-module-term {
				color: @tdb_mts_color_$style_atts_uid !important;
			}
			/* @tdb_mts_color_h_$style_atts_uid */
			.$style_selector .tdb-module-term:hover {
				color: @tdb_mts_color_h_$style_atts_uid !important;
			}
			/* @tdb_mts_border_color_h_$style_atts_uid */
			.$style_selector .tdb-module-term:hover {
				border-color: @tdb_mts_border_color_h_$style_atts_uid;
			}

            /* @tdb_mts_tax_ico_color_$style_atts_uid */
			.$style_selector .tdb-module-tax-icon {
				color: @tdb_mts_tax_ico_color_$style_atts_uid;
			}
			
			/* @tdb_mts_ico_color_$style_atts_uid */
			.$style_selector .tdb-module-term-sep {
				color: @tdb_mts_ico_color_$style_atts_uid;
			}



			/* @tdb_mts_f_txt_$style_atts_uid */
			.$style_selector .tdb-module-term {
				@tdb_mts_f_txt_$style_atts_uid
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
		$res_ctx->load_settings_raw( 'style_general_tdb_module_taxonomies', 1 );
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $res_ctx->load_settings_raw( 'style_general_tdb_module_taxonomies_composer', 1 );
        }




		/* --
		-- AUTHOR NAME
		-- */
		/* -- Layout -- */
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

        // Icon tax spacing
        $tax_ico_space = $res_ctx->get_shortcode_att( 'tax_ico_space' );
        $tax_ico_space .= is_numeric( $tax_ico_space ) ? 'px' : '';
        $res_ctx->load_settings_raw( 'tdb_mts_tax_ico_space_' . $style_atts_uid, $tax_ico_space );

        //
        $tax_ico_size = $res_ctx->get_shortcode_att( 'tax_ico_size' );
        $tax_ico_size .= is_numeric( $tax_ico_size ) ? 'px' : '';
        $res_ctx->load_settings_raw( 'tdb_mts_tax_ico_size_' . $style_atts_uid, $tax_ico_size );

        // Taxicon align
        $res_ctx->load_settings_raw( 'tdb_mts_tax_ico_align_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'tax_ico_align' ) . 'px' );


        // Space between tag groups
        $tax_space = $res_ctx->get_shortcode_att( 'tax_space' );
        $tax_space .= ( $tax_space != '' && is_numeric( $tax_space ) ) ? 'px' : '';
        $res_ctx->load_settings_raw( 'tdb_mts_tax_space_' . $style_atts_uid, $tax_space );

		// Space between tags
        $space = $res_ctx->get_shortcode_att( 'space' );
        $space .= ( $space != '' && is_numeric( $space ) ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'tdb_mts_space_' . $style_atts_uid, $space );

		// Padding
		$padding = $res_ctx->get_shortcode_att( 'padding' );
		$padding .= ( $padding != '' && is_numeric( $padding ) ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'tdb_mts_padding_' . $style_atts_uid, $padding );

		// Border size
		$all_border = $res_ctx->get_shortcode_att( 'all_border' );
		$all_border .= $all_border != '' && is_numeric( $all_border ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'tdb_mts_all_border_' . $style_atts_uid, $all_border );

		// Border style
		$all_border_style = $res_ctx->get_shortcode_att( 'all_border_style' );
		$all_border_style = !empty( $all_border_style ) ? $all_border_style : 'solid';
		$res_ctx->load_settings_raw( 'tdb_mts_all_border_style_' . $style_atts_uid, $all_border_style );

		// Border radius
		$radius = $res_ctx->get_shortcode_att( 'radius' );
		$radius .= is_numeric( $radius ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'tdb_mts_radius_' . $style_atts_uid, $radius );


		// Separator icon size
		$ico_size = $res_ctx->get_shortcode_att( 'ico_size' );
		$ico_size .= is_numeric( $ico_size ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'tdb_mts_ico_size_' . $style_atts_uid, $ico_size );
		
		// Separator icon align
		$res_ctx->load_settings_raw( 'tdb_mts_ico_align_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'ico_align' ) . 'px' );
		


		/* -- Colors -- */
		$res_ctx->load_settings_raw( 'tdb_mts_bg_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'bg' ) );
		$res_ctx->load_settings_raw( 'tdb_mts_bg_h_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'bg_h' ) );

		$res_ctx->load_settings_raw( 'tdb_mts_color_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'color' ) );
		$res_ctx->load_settings_raw( 'tdb_mts_color_h_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'color_h' ) );

        $all_border_color = $res_ctx->get_shortcode_att( 'all_border_color' );
        $all_border_color = !empty( $all_border_color ) ? $all_border_color : '#000';
		$res_ctx->load_settings_raw( 'tdb_mts_all_border_color_' . $style_atts_uid, $all_border_color );
		$res_ctx->load_settings_raw( 'tdb_mts_border_color_h_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'border_color_h' ) );

		$res_ctx->load_settings_raw( 'tdb_mts_tax_ico_color_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'tax_ico_color' ) );
		$res_ctx->load_settings_raw( 'tdb_mts_ico_color_' . $style_atts_uid, $res_ctx->get_shortcode_att( 'ico_color' ) );



		/* -- Fonts -- */
		$res_ctx->load_font_settings( 'f_txt', '', 'tdb_mts_', '_' . $style_atts_uid );

	}


    function render( $atts, $content = null ) {

		$additional_classes_array = array();


		/* -- Call the parent render method -- */
        parent::render($atts);



		/* -- Block atts -- */
		// Taxonomy
		$taxonomies = $this->get_att( 'taxonomy' ) != '' ? $this->get_att( 'taxonomy' ) : 'category';

		// Tags limit
		$limit = $this->get_att( 'terms_limit' ) != '' ? $this->get_att( 'terms_limit' ) : 1;

        // Tags level
        $terms_level = $this->get_att( 'terms_level' );

		// Open link in new tab
		$open_in_new_tab = $this->get_att( 'open_in_new_tab' );
		$link_target = $open_in_new_tab != '' ? ' target="blank"' : '';

        // Tax icon
        $icon_tax = $this->get_icon_att( 'tdicon_tax' );
        $icon_tax_data = '';
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $icon_tax_data = 'data-td-svg-icon="' . $this->get_att('tdicon_tax') . '"';
        }
        $buffy_icon_tax = '';
        if ( !empty( $icon_tax ) ) {
            if( base64_encode( base64_decode( $icon_tax ) ) == $icon_tax ) {
                $buffy_icon_tax .= '<span class="tdb-module-tax-icon" ' . $icon_tax_data . '>' . base64_decode( $icon_tax ) . '</span>';
            } else {
                $buffy_icon_tax .= '<i class="tdb-module-tax-icon ' . $icon_tax . '"></i>';
            }
        }

        // Enable terms' color
        $enable_terms_color = $this->get_att( 'enable_terms_color' ) != '';
        $terms_color_style = $this->get_att( 'terms_color_style' ) != '' ? $this->get_att( 'terms_color_style' ) : 'style1';

		// Separator icon
		$icon_sep = $this->get_icon_att( 'tdicon_sep' );
        $icon_sep_data = '';
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $icon_sep_data = 'data-td-svg-icon="' . $this->get_att('tdicon_sep') . '"';
        }
        $buffy_icon_sep = '';
        if ( !empty( $icon_sep ) ) {
            if( base64_encode( base64_decode( $icon_sep ) ) == $icon_sep ) {
                $buffy_icon_sep .= '<span class="tdb-module-term-sep" ' . $icon_sep_data . '>' . base64_decode( $icon_sep ) . '</span>';
            } else {
                $buffy_icon_sep .= '<i class="tdb-module-term-sep ' . $icon_sep . '"></i>';
            }
        }



		/* -- Retrieve the module post data -- */
		$post_obj = self::$post_obj;

		// Set a flag to determine if the selected taxonomy exists
		$taxonomies_exist = false;

		// Create an array of dummy terms
		$terms_list_dummy = array();
        $dummy_terms_limit = $limit > 0 ? $limit : 5;
		for( $i = 1; $i <= $dummy_terms_limit; $i++ ) {
			$terms_list_dummy[] = array(
				'id' => $i,
				'name' => 'Sample term ' . $i,
                'url' => '#',
                'color' => '',
                'taxonomy' => '',
                );
		}

		$terms_list = array();

        if ( gettype($post_obj) === 'object' && get_class($post_obj) === 'WP_Post' ) {
            $post_terms = array();

            $td_post_theme_settings = self::$post_theme_settings_meta;
            $taxonomies_array = explode(',', $taxonomies);

            foreach( $taxonomies_array as $taxonomy ) {
                $taxonomy = trim($taxonomy);

                if( !taxonomy_exists($taxonomy) ) {
                    continue;
                }

                $taxonomy_terms = get_terms(array(
                    'taxonomy' => $taxonomy,
                    'object_ids' => $post_obj->ID,
                ));

                if( $taxonomy == 'category' ) {
                    $featured_cat_name = TD_FEATURED_CAT;
                    $taxonomy_terms = array_filter( $taxonomy_terms, function( $term ) use ( $featured_cat_name ) {
                        return $term->name != $featured_cat_name;
                    } );
                }

                $i = 0;
                foreach( $taxonomy_terms as $term ) {
                    if( $limit > 0 && ( $i + 1 ) > $limit ) {
                        break;
                    }

                    $term_id = $term->term_id;
                    $term_name = $term->name;
                    $term_taxonomy = $term->taxonomy;

                    if( $terms_level != '' ) {
                        $term_ancestors = get_ancestors( $term_id, $term_taxonomy );

                        if( ( count( $term_ancestors ) + 1 ) != $terms_level ) {
                            continue;
                        }
                    }

                    // get term color
                    if( $term_taxonomy === 'category' ) {
                        // get the category color from theme panel
                        $term_meta_color = td_util::get_category_option($term_id, 'tdc_color');
                    } else {
                        $term_meta_color = get_term_meta($term_id, 'tdb_filter_color', true);
                    }

                    // sanitize hex color
                    $sanitized_hex_color = sanitize_hex_color($term_meta_color);

                    $terms_list[] = array(
                        'id' => $term_id,
                        'name' => $term_name,
                        'url' => get_term_link( $term_id ),
                        'color' => !empty($sanitized_hex_color) ? $sanitized_hex_color : '',
                        'taxonomy' => $term_taxonomy
                    );

                    $i++;
                }

                $taxonomies_exist = true;
            }

            if( !empty( $terms_list ) ) {
                if ( !empty($td_post_theme_settings['td_primary_cat']) ) {
                    //we have a custom category selected
                    $selected_term_obj = get_term($td_post_theme_settings['td_primary_cat']);
                    // unset primary if it is found in the term list array
                    foreach( $terms_list as $key => $term ) {
                        if ( $selected_term_obj->term_id === $term['id'] ) {
                            unset($terms_list[$key]);
                            array_unshift($terms_list, $term);
                            break;
                        }
                    }
                }
            } else if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
                // If we are in composer, display dummy data only if we
                // are editing the actual module
                if( tdb_state_template::get_template_type() == 'module' ) {
                    $terms_list = $terms_list_dummy;
                }
            }
		} else {
			$terms_list = $terms_list_dummy;
		}


		/* -- Output the module element HTML -- */
        $buffy = '';

		$buffy .= '<div class="' . $this->get_block_classes($additional_classes_array) . '" ' . $this->get_block_html_atts() . '>';
            // get the block css
            $buffy .= $this->get_block_css();

            // get the js for this block
            $buffy .= $this->get_block_js();

			// If the selected taxonomy doesn't exist, then display a warning;
			// otherwise proceed with trying to display the post terms
			if( !$taxonomies_exist ) {
					$buffy .= td_util::get_block_error('Module Taxonomies', 'The selected taxonomies do not exist.');

				$buffy .= '</div>';

				return $buffy;
			}

            if ( !empty($terms_list) ) {
                $buffy .= $buffy_icon_tax;
            }


        $i = 0;

			foreach( $terms_list as $key => $term ) {
                $term_color = $term['color'];
                $term_color_style_attr = '';

                if( $enable_terms_color && !empty( $term_color ) ) {
                    $bg_color = $term_color;
                    $border_color = $term_color;
                    $readable_text_color = td_util::readable_colour( $term_color, 200, 'rgba(0, 0, 0, 0.9)', '#fff' );
                    $text_color = $readable_text_color;

                    if( $terms_color_style == 'style2' ) {
                        $bg_color = td_util::hex2rgba( $term_color, 0.85 );
                    } else if( $terms_color_style == 'style3' ) {
                        $bg_color = td_util::hex2rgba( $term_color, 0.2 );
                        $border_color = td_util::hex2rgba( $term_color, 0.05 );
                        $text_color = $term_color;
                    }

                    $term_color_style_attr = ' style="background-color:' . $bg_color . ';border-color:' . $border_color . ';' . ( $text_color != '' ? 'color:' . $text_color . ';' : '' ) . '"';
                }

				if( $key != key($terms_list) ) {
					$buffy .= $buffy_icon_sep;
				}

                $last_in_taxonomy = false;
                if( !empty($terms_list[$i + 1]) ) {
                    if( $terms_list[$i + 1]['taxonomy'] != $term['taxonomy'] ) {
                        $last_in_taxonomy = true;
                    }
                }

				$buffy .= '<a class="tdb-module-term tdb-term-' . $term['id'] . ( $last_in_taxonomy ? 'tdb-module-term-last-in-tax' : '' ) . '" href="' . $term['url'] . '"' . $link_target . $term_color_style_attr . ' data-taxonomy="' . $term['taxonomy'] . '">' . $term['name'] . '</a>';

                $i++;
			}
		$buffy .= '</div>';


        return $buffy;

    }

}