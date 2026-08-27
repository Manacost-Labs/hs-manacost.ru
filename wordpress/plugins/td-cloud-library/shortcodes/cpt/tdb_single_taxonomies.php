<?php

/**
 * Class tdb_single_taxonomies
 */


class tdb_single_taxonomies extends td_block {

	private static $display = 'inline';
	private static $images_enabled = false;
	private static $cf_enabled = false;


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

        $compiled_css = '';

		$raw_css =
			"<style>

                /* @style_general_tdb_single_taxonomies */
                .tdb_single_taxonomies {
                  margin: 0 0 10px 0;
                  line-height: 1;
                  font-family: var(--td_default_google_font_1, 'Open Sans', 'Open Sans Regular', sans-serif);
                }
                .tdb_single_taxonomies .tdb-category {
                    display: flex;
                    flex-wrap: wrap;
                }
                .tdb_single_taxonomies a {
                    display: flex;
                    flex-wrap: wrap;
                    pointer-events: auto;
                    font-size: 10px;
                    margin: 0 5px 5px 0;
                    line-height: 1;
                    color: #fff;
                    padding: 3px 6px 4px 6px;
                    white-space: nowrap;
                    position: relative;
                    vertical-align: middle;
                }
                .tdb_single_taxonomies a:hover .tdb-cat-bg {
                  opacity: 0.9;
                }
                .tdb_single_taxonomies a:hover .tdb-cat-bg:before {
                  opacity: 1;
                }
                .tdb_single_taxonomies .tdb-cat-img {	
					position: relative;
				}
                .tdb_single_taxonomies .tdb-cat-img img {
					display: block;
					height: auto;
				}
                .tdb_single_taxonomies .tdb-category i:last-of-type {
                  	display: none;
                }
                .tdb_single_taxonomies .tdb-cat-text {
					display: inline-block;
					vertical-align: middle;
					margin-right: 10px;
                }
                .tdb_single_taxonomies .tdb-cat-sep {
					font-size: 14px;
					vertical-align: middle;
					position: relative;
                }
                .tdb_single_taxonomies .tdb-cat-sep-svg {
                  	line-height: 0;
                }
                .tdb_single_taxonomies .tdb-cat-sep-svg svg {
					width: 14px;
					height: auto;
                }
                .tdb_single_taxonomies .tdb-cat-bg {
					position: absolute;
					background-color: #222;
					border: 1px solid #222;
					width: 100%;
					height: 100%;
					top: 0;
					left: 0;
					z-index: -1;
                }
                .tdb_single_taxonomies .tdb-cat-bg:before {
					content: '';
					width: 100%;
					height: 100%;
					left: 0;
					top: 0;
					position: absolute;
					z-index: -1;
					opacity: 0;
					-webkit-transition: opacity 0.3s ease;
					transition: opacity 0.3s ease;
                }
                .tdb_single_taxonomies .tdb-cat-style2 .tdb-cat-bg {
                  	background-color: rgba(34, 34, 34, 0.85);
                }
                .tdb_single_taxonomies .tdb-cat-cfs {
                    display: flex;
                    flex-wrap: wrap;
                    width: 100%;
                    margin-left: -6px;
                    margin-right: -6px;
                }
                .tdb_single_taxonomies .tdb-cat-cf {
                    margin-top: 6px;
                    padding-left: 6px;
                    padding-right: 6px;
                    font-size: .9em;
                }

                
                
				/* @display_columns */
				body .$unique_block_class a {
					flex-wrap: nowrap;
					white-space: normal;
				}
				/* @columns */
				.$unique_block_class .tdb-st-term-wrap {
					width: @columns;
				}
				/* @col_gap */
				.$unique_block_class .tdb-category {
					margin-left: -@col_gap;
					margin-right: -@col_gap;
				}
				.$unique_block_class .tdb-st-term-wrap {
					padding-left: @col_gap;
					padding-right: @col_gap;
				}
				/* @col_bot_space */
				.$unique_block_class .tdb-category {
					row-gap: @col_bot_space;
				}

				
				/* @horiz_align */
				.td-theme-wrap .$unique_block_class .tdb-category {
					justify-content: @horiz_align;
				}
				/* @vert_align */
				.td-theme-wrap .$unique_block_class .tdb-category {
					align-items: @vert_align;
				}
				
				
                /* @add_space */
				.$unique_block_class .tdb-cat-text {
					margin-right: @add_space;
				}
                /* @txt_color */
				.$unique_block_class .tdb-cat-text {
					color: @txt_color;
				}
				
				/* @f_txt */
				.$unique_block_class .tdb-cat-text {
					@f_txt
				}
                
                
                /* @cat_padding */
				.$unique_block_class .tdb-entry-category {
					padding: @cat_padding;
				}
                /* @cat_space */
				.$unique_block_class .tdb-entry-category {
					margin: @cat_space;
				}
                /* @cat_border */
				.$unique_block_class .tdb-cat-bg {
					border-width: @cat_border;
				}
                /* @cat_radius */
				.$unique_block_class .tdb-cat-bg,
				.$unique_block_class .tdb-cat-bg:before {
					border-radius: @cat_radius;
				}
				/* @cat_skew */
				.$unique_block_class .tdb-cat-bg {
					transform: skew(@cat_skew);
                    -webkit-transform: skew(@cat_skew);
				}

                /* @text_color */
				.$unique_block_class .tdb-entry-category {
					color: @text_color !important;
				}
				/* @text_hover_color */
				.$unique_block_class .tdb-entry-category:hover {
					color: @text_hover_color !important;
				}
				.$unique_block_class .tdb-entry-category:hover .tdb-cat-cf {
					color: @text_hover_color;
				}
                /* @bg_solid */
				.$unique_block_class .tdb-cat-bg {
					background-color: @bg_solid !important;
				}
                /* @bg_gradient */
				.$unique_block_class .tdb-cat-bg {
					@bg_gradient;
				}
				/* @bg_hover_solid */
				.$unique_block_class .tdb-cat-bg:before {
					background-color: @bg_hover_solid;
				}
				/* @bg_hover_gradient */
				.$unique_block_class .tdb-cat-bg:before {
					@bg_hover_gradient
				}
				.$unique_block_class .tdb-entry-category:hover .tdb-cat-bg:before {
					opacity: 1;
				}
				/* @border_color_solid */
				.$unique_block_class .tdb-cat-bg {
					border-color: @border_color_solid !important;
				}
				/* @border_color_params */
				.$unique_block_class .tdb-cat-bg {
				    border-image: linear-gradient(@border_color_params);
				    border-image: -webkit-linear-gradient(@border_color_params);
				    border-image-slice: 1;
				    transition: none;
				}
				.$unique_block_class .tdb-entry-category:hover .tdb-cat-bg {
				    border-image: linear-gradient(@border_hover_color, @border_hover_color);
				    border-image: -webkit-linear-gradient(@border_hover_color, @border_hover_color);
				    border-image-slice: 1;
				    transition: none;
				}
				/* @border_hover_color */
				.$unique_block_class .tdb-entry-category:hover .tdb-cat-bg {
					border-color: @border_hover_color !important;
				}
				
				/* @f_tags */
				.$unique_block_class .tdb-entry-category {
					@f_tags
				}
				
				
				/* @image_size */
				body .$unique_block_class .tdb-cat-img {
					width: @image_size;
				}
				/* @image_space */
				body .$unique_block_class .tdb-cat-img {
					margin-right: @image_space;
				}
				/* @image_vert_align */
				body .$unique_block_class .tdb-cat-img {
					top: @image_vert_align;
				}
				
				
				/* @cf_gap */
				body .$unique_block_class .tdb-cat-cfs {
				    margin-left: calc(-@cf_gap / 2);
				    margin-right: calc(-@cf_gap / 2);
				}
				body .$unique_block_class .tdb-cat-cf {
				    padding-left: calc(@cf_gap / 2);
				    padding-right: calc(@cf_gap / 2);
				}
				/* @cf_space */
				body .$unique_block_class .tdb-cat-cf {
				    margin-top: @cf_space;
				}
				
				/* @cf_color */
				body .$unique_block_class .tdb-cat-cf {
				    color: @cf_color;
				}
				/* @cf_color_h */
				body .$unique_block_class .tdb-entry-category:hover .tdb-cat-cf {
				    color: @cf_color_h;
				}
				
				/* @f_cf */
				body .$unique_block_class .tdb-cat-cf {
				    @f_cf
				}
				

                /* @icon_size */
				.$unique_block_class .tdb-cat-sep {
					font-size: @icon_size;
				}
                /* @icon_svg_size */
				.$unique_block_class .tdb-cat-sep-svg svg {
					width: @icon_svg_size;
				}
                /* @icon_space */
				.$unique_block_class .tdb-cat-sep {
					margin: 0 @icon_space;
				}
                /* @icon_align */
				.$unique_block_class .tdb-cat-sep {
					top: @icon_align;
				}
                /* @i_color */
				.$unique_block_class .tdb-cat-sep {
					color: @i_color;
				}
				.$unique_block_class .tdb-cat-sep-svg svg,
				.$unique_block_class .tdb-cat-sep-svg svg * {
					fill: @i_color;
				}

			</style>";


		$td_css_res_compiler = new td_css_res_compiler( $raw_css );
		$td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts() );

		$compiled_css .= $td_css_res_compiler->compile_css();
		return $compiled_css;
	}

	static function cssMedia( $res_ctx ) {

        /* --
        -- GENERAL
        -- */
        $res_ctx->load_settings_raw( 'style_general_tdb_single_taxonomies', 1 );




        /* --
        -- ADDITIONAL TEXT
        -- */
        /* -- Layout -- */
        // Space
        $add_space = $res_ctx->get_shortcode_att('add_space');
        if ( $add_space != 0 || !empty($add_space) ) {
            $res_ctx->load_settings_raw( 'add_space', $add_space . 'px' );
        }


        /* -- Colors -- */
        $res_ctx->load_settings_raw( 'txt_color', $res_ctx->get_shortcode_att('txt_color') );


        /* -- Fonts -- */
        $res_ctx->load_font_settings( 'f_txt' );




        /* --
        -- TAGS GENERAL
        -- */
        /* -- Layout -- */
		// Display
		$res_ctx->load_settings_raw( 'display_' . self::$display, 1 );

		if ( self::$display == 'columns' ) {
			// Columns count.
			$columns = $res_ctx->get_shortcode_att('columns');
			$columns = $columns != '' ? $columns : '100%';
			$res_ctx->load_settings_raw( 'columns', $columns );

			// Columns gap.
			$col_gap = (int)$res_ctx->get_shortcode_att('col_gap');
			if ( !empty($col_gap) ) {
				if ( is_numeric($col_gap) ) {
					$col_gap .= 'px';
				} else if ( strpos($col_gap, '%') === false ) {
					$col_gap = $col_gap / 2;
				}
				$res_ctx->load_settings_raw( 'col_gap', $col_gap );
			}

			// Columns bottom space.
			$col_bot_space = $res_ctx->get_shortcode_att('col_bot_space');
			if ( !empty($col_gap) ) {
				$col_bot_space .= is_numeric($col_bot_space) ? 'px' : '';
				$res_ctx->load_settings_raw( 'col_bot_space', $col_bot_space );
			}
		}

        // Horizontal align
        $horiz_align = $res_ctx->get_shortcode_att('content_align_horizontal');
        if ( $horiz_align == 'content-horiz-left' || $horiz_align == '' ) {
            $res_ctx->load_settings_raw( 'horiz_align', 'flex-start' );
        } if ( $horiz_align == 'content-horiz-center' ) {
            $res_ctx->load_settings_raw( 'horiz_align', 'center' );
        } else if ( $horiz_align == 'content-horiz-right' ) {
            $res_ctx->load_settings_raw( 'horiz_align', 'flex-end' );
        }

        // Vertical align
        $vert_align = $res_ctx->get_shortcode_att('content_align_vertical');
        if ( $vert_align == 'content-vert-top' || $vert_align == '' ) {
            $res_ctx->load_settings_raw( 'vert_align', 'flex-start' );
        } if ( $vert_align == 'content-vert-center' ) {
            $res_ctx->load_settings_raw( 'vert_align', 'center' );
        } else if ( $vert_align == 'content-vert-bottom' ) {
            $res_ctx->load_settings_raw( 'vert_align', 'flex-end' );
        }

		// Padding
		$cat_padding = $res_ctx->get_shortcode_att('cat_padding');
		$res_ctx->load_settings_raw( 'cat_padding', $cat_padding );
		if ( is_numeric( $cat_padding ) ) {
			$res_ctx->load_settings_raw( 'cat_padding', $cat_padding . 'px' );
		}

		// Spacing
		$cat_space = $res_ctx->get_shortcode_att('cat_space');
		$res_ctx->load_settings_raw( 'cat_space', $cat_space );
		if ( is_numeric( $cat_space ) ) {
			$res_ctx->load_settings_raw( 'cat_space', $cat_space . 'px' );
		}

        // Border size
        $res_ctx->load_settings_raw( 'cat_border', $res_ctx->get_shortcode_att('cat_border') . 'px' );

        // Border radius
        $cat_radius = $res_ctx->get_shortcode_att('cat_radius');
        if ( $cat_radius != 0 || !empty($cat_radius) ) {
            $res_ctx->load_settings_raw( 'cat_radius', $cat_radius . 'px' );
        }

		// Skew
		$cat_skew = $res_ctx->get_shortcode_att('cat_skew');
		if ( $cat_skew != 0 || !empty($cat_skew) ) {
			$res_ctx->load_settings_raw( 'cat_skew', $cat_skew . 'deg' );
		}


        /* -- Colors -- */
        $res_ctx->load_settings_raw( 'text_color', $res_ctx->get_shortcode_att('text_color') );
        $res_ctx->load_color_settings( 'bg_color', 'bg_solid', 'bg_gradient', '', '' );
        $res_ctx->load_color_settings( 'border_color', 'border_color_solid', 'border_color_gradient', 'border_color_gradient_1', 'border_color_params', '' );
        $res_ctx->load_color_settings( 'bg_hover_color', 'bg_hover_solid', 'bg_hover_gradient', '', '', '' );
        $res_ctx->load_settings_raw( 'text_hover_color', $res_ctx->get_shortcode_att('text_hover_color') );
        $res_ctx->load_settings_raw( 'border_hover_color', $res_ctx->get_shortcode_att('border_hover_color') );


        /* -- Fonts -- */
        $res_ctx->load_font_settings( 'f_tags' );




        /* --
        -- TERMS IMAGES
        -- */
        if( self::$images_enabled ) {
			/* -- Layout -- */
			// Enabled
			$res_ctx->load_settings_raw( 'images_enabled', 1 );

			// Size
            $image_size = $res_ctx->get_shortcode_att( 'image_size' );
			$image_size = $image_size != '' ? $image_size : '20px';
            $image_size .= $image_size != '' && is_numeric( $image_size ) ? 'px' : '';
            $res_ctx->load_settings_raw( 'image_size', $image_size );

			// Space
            $image_space = $res_ctx->get_shortcode_att( 'image_space' );
			$image_space = $image_space != '' ? $image_space : '10px';
            $image_space .= $image_space != '' && is_numeric( $image_space ) ? 'px' : '';
            $res_ctx->load_settings_raw( 'image_space', $image_space );

			// Vertical align
			$res_ctx->load_settings_raw( 'image_vert_align', $res_ctx->get_shortcode_att( 'image_vert_align' ) . 'px' );
		}




        /* --
        -- CUSTOM FIELDS
        -- */
        if( self::$cf_enabled ) {
            /* -- Layout -- */
            // Gap
            $cf_gap = $res_ctx->get_shortcode_att( 'cf_gap' );
            $cf_gap .= $cf_gap != '' && is_numeric( $cf_gap ) ? 'px' : '';
            $res_ctx->load_settings_raw( 'cf_gap', $cf_gap );

            // Bottom space
            $cf_space = $res_ctx->get_shortcode_att( 'cf_space' );
            $cf_space .= $cf_space != '' && is_numeric( $cf_space ) ? 'px' : '';
            $res_ctx->load_settings_raw( 'cf_space', $cf_space );


            /* -- Colors -- */
            $res_ctx->load_settings_raw( 'cf_color', $res_ctx->get_shortcode_att('cf_color') );
            $res_ctx->load_settings_raw( 'cf_color_h', $res_ctx->get_shortcode_att('cf_color_h') );


            /* -- Fonts -- */
            $res_ctx->load_font_settings( 'f_cf' );
        }




        /* --
        -- ICON SEPARATOR
        -- */
		if ( self::$display == 'inline' ) {
			/* -- Layout -- */
			// Icon size
			$res_ctx->load_settings_raw( 'icon_size', $res_ctx->get_shortcode_att('icon_size') . 'px' );

			// Icon space
			$res_ctx->load_settings_raw( 'icon_space', $res_ctx->get_shortcode_att('icon_space') . 'px' );

			// Icon vertical align
			$res_ctx->load_settings_raw( 'icon_align', $res_ctx->get_shortcode_att('icon_align') . 'px' );


			/* -- Colors -- */
			$res_ctx->load_settings_raw( 'i_color', $res_ctx->get_shortcode_att('i_color') );
		}

	}

	/**
	 * Disable loop block features. This block does not use a loop and it doesn't need to run a query.
	 */
	function __construct() {
		parent::disable_loop_block_features();
	}

	function render( $atts, $content = null ) {
		parent::render($atts); // sets the live atts, $this->atts, $this->block_uid, $this->td_query (it runs the query)



        /* --
        -- Taxonomy terms data.
        -- */
		global $tdb_state_single;
		$post_categories_data = $tdb_state_single->post_taxonomies->__invoke( $this->get_all_atts() );



        /* --
        -- Block atts.
        -- */
		/* -- Additional text. -- */
		$add_text = rawurldecode( base64_decode( strip_tags ( $this->get_att( 'add_text' ) ) ) );
		$add_text_html = '';
		if ( ! empty( $add_text ) && ! empty($post_categories_data) ) {
			$add_text_html = '<div class="tdb-cat-text">' . $add_text . '</div>';
		}


		/* -- Terms limit. -- */
		$cat_count = 0;
		$cat_limit = $this->get_att( 'cat_limit' );
		if( $cat_limit == '' || !is_numeric( $cat_limit ) ) {
			$cat_limit = 30;
		}


		/* -- Display. -- */
		self::$display = $this->get_att( 'display' ) != '' ? $this->get_att( 'display' ) : 'inline';


		/* -- Terms images. -- */
        // Enable terms images.
		self::$images_enabled = $this->get_att( 'image_enabled' ) != '';


		/* -- Custom fields. -- */
        // Enable custom fields.
        self::$cf_enabled = $this->get_att( 'cf_enabled' ) != '' && class_exists( 'ACF' );


		/* -- Icon separator. -- */
		// Icon HTML; only generate if display is set as 'inline'.
		$tdicon_html = '';

		if ( self::$display == 'inline' ) {
			$tdicon = $this->get_icon_att( 'tdicon' );
			$tdicon_data = '';
			if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
				$tdicon_data = 'data-td-svg-icon="' . $this->get_att('tdicon') . '"';
			}
			if( $tdicon != '' ) {
				if( base64_encode( base64_decode( $tdicon ) ) == $tdicon ) {
					$tdicon_html = '<span class="tdb-cat-sep tdb-cat-sep-svg" ' . $tdicon_data . '>' . base64_decode( $tdicon ) . '</span>';
				} else {
					$tdicon_html = '<i class="tdb-cat-sep ' . $tdicon . '"></i>';
				}
			}
		}


		/* -- Terms style. -- */
		$cat_text_color = '';
		$cat_style = $this->get_att( 'cat_style' );



		/* --
		-- Output the shortcode HTML.
		-- */
		$buffy = ''; // output buffer

		$buffy .= '<div class="' . $this->get_block_classes( array( 'tdb_single_categories' ) ) . ' ' . $cat_style . '"  ' . $this->get_block_html_atts() . '>';

			// get the block css
			$buffy .= $this->get_block_css();

			// get the js for this block
			$buffy .= $this->get_block_js();


			/* -- Bail if taxonomy is not set or the taxonomy has no terms -- */
			/* -- for this current post/cpt. -- */
	        if ( empty($post_categories_data) or !is_array($post_categories_data) ) {

	            if ( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {

	                $buffy .= '<div id=' . $this->block_uid . ' class="td_block_inner td-fix-index">';
	                    $buffy .= td_util::get_block_error( 'Single Post Taxonomy', 'Fill up the taxonomy name.' );
	                $buffy .= '</div>';

	                $buffy .= '</div>';

	                return $buffy;

	            }

	        }


			/* -- Render the terms. -- */
			// If display is set as 'columns', the we need an extra wrap 
			// for each term.
			$term_wrap_start = '';
			$term_wrap_end = '';
			if ( self::$display == 'columns' ) {
				$term_wrap_start .= '<div class="tdb-st-term-wrap">';
				$term_wrap_end .= '</div>';
			}

			$buffy .= '<div class="tdb-category td-fix-index">';

				$buffy .= $add_text_html;

				foreach ( $post_categories_data as $category_name => $category_params ) {

					if ( $category_params['hide_on_post'] == 'hide' ) {
						continue;
					}
					$cat_count++;
					if( $cat_limit < $cat_count ) {
						break;
					}

					// Set the term's color properties.
					$td_cat_bg = '';
					$td_cat_color = '';
					$cat_text_color = '';

					if ( ! empty( $category_params['color'] ) ) {
						// set title color based on background color contrast
						$td_cat_title_color = td_util::readable_colour( $category_params['color'], 200, 'rgba(0, 0, 0, 0.9)', '#fff' );
						$td_cat_bg = ' style="background-color:' . $category_params['color'] . '; border-color:' . $category_params['color']  . ';"';

						if ( $td_cat_title_color == '#fff' ) {
							$td_cat_color = '';
						} else {
							$td_cat_color = ' style="color:' . $td_cat_title_color . ';"';
						}

						if( $cat_style == 'tdb-cat-style2' ) {
							$td_cat_bg = ' style="background-color:' . td_util::hex2rgba($category_params['color'], 0.85) . '; border-color:' . $category_params['color'] . ';"';
						}

						if( $cat_style == 'tdb-cat-style3' ) {
							$td_cat_bg = ' style="background-color:' . td_util::hex2rgba($category_params['color'], 0.2) . '; border-color:' . td_util::hex2rgba($category_params['color'], 0.05) . ';"';
							$cat_text_color = ' style="color:' . $category_params['color'] . ';"';
						}
					}

					// Set the term's ID as a class.
                    $term_id_class = !empty($category_params['id']) ? 'tdb-term-' . $category_params['id'] : '';

					$buffy .= $term_wrap_start;
						$buffy .= '<a class="tdb-entry-category ' . $term_id_class . '" ' . $td_cat_color . ' href="' . $category_params['link'] . '" ' . $cat_text_color . '>';
							$buffy .= '<span class="tdb-cat-bg"' . $td_cat_bg . '></span>';

							if ( self::$images_enabled && !empty($category_params['image']) ) {
								$buffy .= '<span class="tdb-cat-img">'; 
									$buffy .= '<img src="' . $category_params['image'] . '" />';
								$buffy .= '</span>';
							}
							
							$buffy .= $category_name;

							if( self::$cf_enabled && !empty( $category_params['custom_fields'] ) ) {
								$buffy .= '<span class="tdb-cat-cfs">';
									foreach( $category_params['custom_fields'] as $field_info ) {
										$buffy .= '<span class="tdb-cat-cf">' . $field_info['label'] . ': ' . $field_info['value'] . '</span>';
									}
								$buffy .= '</span>';
							}
						$buffy .= '</a>' . $tdicon_html;
					$buffy .= $term_wrap_end;

				}

			$buffy .= '</div>';

		$buffy .= '</div>';

		return $buffy;
	}

}
