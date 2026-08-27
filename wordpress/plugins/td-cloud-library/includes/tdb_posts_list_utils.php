<?php

class tdb_posts_list_utils {

	/* ---------
	---- FUNCTION USED TO RENDER THE POSTS LIST BASED ON RENDER OPTIONS AND FILTERS
	--------- */
    static function render_list( $options, $active_filters ) {

		$buffy = '';


		/* --
		-- FLAG TO CHECK IF WE ARE IN COMPOSER
		-- */
		$is_composer = false;
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $is_composer = true;
        }



		/* --
		-- VARIOUS SETTINGS
		-- */
		/* -- Columns order icons -- */
		$column_order_icons = '<div class="tdb-s-table-col-order-icons">';
			$column_order_icons .= '<svg xmlns="http://www.w3.org/2000/svg" width="8" height="4.571" viewBox="0 0 8 4.571"><path id="Path_2" data-name="Path 2" d="M4,2,8,6.571H0Z" transform="translate(0 -2)"/></svg>';
			$column_order_icons .= '<svg xmlns="http://www.w3.org/2000/svg" width="8" height="4.571" viewBox="0 0 8 4.571"><path id="Path_1" data-name="Path 1" d="M4,2,8,6.571H0Z" transform="translate(8 6.571) rotate(180)"/></svg>';
		$column_order_icons .= '</div>';
	
	
		/* -- Rating stars -- */
		$full_star_icon = tdb_util::get_icon_att($options['fullStarIcon']);
		$full_star_icon_data = '';
		if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
			$full_star_icon_data = 'data-td-svg-icon="' . $options['fullStarIcon'] . '"';
		}
		$full_star_icon_html = '';
		if ( !empty( $full_star_icon ) ) {
			if( base64_encode( base64_decode( $full_star_icon ) ) == $full_star_icon ) {
				$full_star_icon_html = base64_decode( $full_star_icon ) ;
			} else {
				$full_star_icon_html = '<i class="' . $full_star_icon . '"></i>';
			}
		}
	
		$half_star_icon = tdb_util::get_icon_att($options['halfStarIcon']);
		$half_star_icon_data = '';
		if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
			$half_star_icon_data = 'data-td-svg-icon="' . $options['halfStarIcon'] . '"';
		}
		$half_star_icon_html = '';
		if ( !empty( $half_star_icon ) ) {
			if( base64_encode( base64_decode( $half_star_icon ) ) == $half_star_icon ) {
				$half_star_icon_html = base64_decode( $half_star_icon ) ;
			} else {
				$half_star_icon_html = '<i class="' . $half_star_icon . '"></i>';
			}
		}
	
		$empty_star_icon = tdb_util::get_icon_att($options['emptyStarIcon']);
		$empty_star_icon_data = '';
		if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
			$empty_star_icon_data = 'data-td-svg-icon="' . $options['emptyStarIcon'] . '"';
		}
		$empty_star_icon_html = '';
		if ( !empty( $empty_star_icon ) ) {
			if( base64_encode( base64_decode( $empty_star_icon ) ) == $empty_star_icon ) {
				$empty_star_icon_html = base64_decode( $empty_star_icon ) ;
			} else {
				$empty_star_icon_html = '<i class="' . $empty_star_icon . '"></i>';
			}
		}
	
	
		/* -- Forms URLs -- */
		$main_form_url = $options['mainFormURL'];
		$main_form_add_txt = $options['mainFormAddTxt'];
		$main_form_edit_txt = $options['mainFormEditTxt'];
	
		$extra_form_1_url = $options['extraForm1URL'];
		$extra_form_1_edit_txt = $options['extraForm1EditTxt'];

		$extra_form_2_url = $options['extraForm2URL'];
		$extra_form_2_edit_txt = $options['extraForm2EditTxt'];

        /* -- Post childs forms --*/
        $child1_form_url = $options['child1FormURL'];
        $child1_form_edit_txt = $options['child1FormEditTxt'];

        $child2_form_url = $options['child2FormURL'];
        $child2_form_edit_txt = $options['child2FormEditTxt'];


		/* -- Limit notification text -- */
		$limit_notif = rawurldecode( base64_decode( strip_tags( $options['limitNotifTxt'] ) ) );


	
		/* --
		-- COLUMNS TO DISPLAY
		-- */
		$columns_to_display = $options['columns'];
		if( $columns_to_display != '' ) {
			$columns_to_display = explode( "\n", rawurldecode( base64_decode( strip_tags( $columns_to_display ) ) ) );
		} else {
			$columns_to_display = array();
		}
		$columns_to_display = array_map('trim', $columns_to_display);
		$columns_to_display = array_filter($columns_to_display, function($value) { return !is_null($value) && $value !== ''; });

        $columns_to_display_labels = array();
        foreach( $columns_to_display as $key => $column_to_display ) {
            if( preg_match("/\[.*\]/s", $column_to_display, $matches) ) {
                $columns_to_display_labels[] = str_replace( ['[', ']'], '', $matches )[0];
                $columns_to_display[$key] = trim( str_replace( $matches, '', $column_to_display ) );

                continue;
            }

            $columns_to_display_labels[] = '';
        }

		$predefined_columns = array('id', 'overall_rating', 'title', 'featured_image', 'categories', 'tags', 'date', 'author', 'source_title', 'credits_cost');

		

		/* --
		-- CURRENTLY LOGGED IN USER INFO
		-- */
		$current_user = wp_get_current_user();
		$is_current_user_admin = in_array('administrator', $current_user->roles );

		// get user locker credits meta
		$tds_user_locker_credits = get_user_meta( $current_user->ID, 'tds_locker_credits', true );

		// ids of posts user bought with credits
		$tds_bought_posts_ids = ( $tds_user_locker_credits && is_array($tds_user_locker_credits) ) ? array_keys($tds_user_locker_credits) : [];
	
	

		/* --
		-- GET THE POSTS
		-- */
		$allowed_post_statuses = array('publish', 'draft', 'private', 'pending');
	
		$args = array(
			'post_type' => $options['postType'],
			'post_status' => $allowed_post_statuses,
			'numberposts' => -1
		);
	

		/* -- If the user is not an admin or the show all posts for admins -- */
		/* -- option is disabled, get the posts for the currently logged in user only -- */
		if ( ( !$is_current_user_admin || !$options['showAllPosts'] || $options['showAllPosts'] === 'false' ) && !$options['creditsPostsFilter'] ) {
            $args['author'] = $current_user->ID;
        }

		/* -- Show only posts user bought with credits -- */
		if( $options['creditsPostsFilter'] && $options['creditsPostsFilter'] !== 'false' ) {
            if ( !empty($tds_bought_posts_ids) ) {
                $args['post__in'] = $tds_bought_posts_ids;
                $current_user_initial_posts = get_posts($args);
            } else {
                $current_user_initial_posts = [];
            }
		} else {
            $current_user_initial_posts = get_posts($args);
        }
	
		$current_user_posts = array();

		/* -- Check for linked posts -- */
		$linked_post_type = $options['linkedPostType'];
		if( $linked_post_type != '' && post_type_exists( $linked_post_type ) && !empty($current_user_initial_posts) ) {
			foreach ( $current_user_initial_posts as $current_user_initial_post ) {
				$post_linked_posts = get_post_meta( $current_user_initial_post->ID, 'tdc-post-linked-posts', true );
	
				if( !empty( $post_linked_posts ) ) {
					if( isset( $post_linked_posts[$linked_post_type] ) ) {
						foreach ( $post_linked_posts[$linked_post_type] as $post_linked_post_id ) {
							$post_linked_post = get_post($post_linked_post_id);
	
							if( !is_null( $post_linked_post ) ) {
								if( in_array( $post_linked_post->post_status, $allowed_post_statuses ) ) {
									$current_user_posts[] = $post_linked_post;
								}
							}
						}
					}
				}
			}
		} else {
			$current_user_posts = $current_user_initial_posts;
		}
	
	
		/* -- Build the posts array -- */
		$posts = array();
		if( !empty( $current_user_posts ) ) {
			foreach ( $current_user_posts as $current_user_post ) {
				$post = array(
					'ID' => $current_user_post->ID,
                    'featured_image' => '',
					'title' => $current_user_post->post_title,
					'author' => get_the_author_meta('display_name', $current_user_post->post_author),
                    'author_url' => get_author_posts_url($current_user_post->post_author),
					'publish_date' => get_the_time(get_option('date_format'), $current_user_post->ID),
					'parent' => !empty( $current_user_post->post_parent ) ? $current_user_post->post_parent : '',
					'tdc_parent' => get_post_meta( $current_user_post->ID, 'tdc-parent-post-id', true ),
				);
	
				if( $current_user_post->post_status == 'publish' ) {
					$post['status'] = 'Published';
				} else {
					$post['status'] = ucfirst($current_user_post->post_status);
				}

				$td_post_theme_settings = td_util::get_post_meta_array( $post['ID'], 'td_post_theme_settings' );

                foreach( $columns_to_display as $column ) {
                    if( $column == 'id' || $column == 'title' || $column == 'date' || $column == 'author' ) {
                        continue;
                    }

                    switch( $column ) {
                        case 'overall_rating':
                            $post_type = get_post_type($post['ID']);
	
                            if( $post_type == 'tdc-review' ) {
                                $overall_rating = td_util::get_overall_review_rating($post['ID']);
                            } else {
                                $overall_rating = td_util::get_overall_post_rating($post['ID']);
                            }

							$post['overall_rating'] = $overall_rating ? $overall_rating : floatval(0);

                            break;

                        case 'featured_image':
                            $featured_image = '';
	
                            if ( has_post_thumbnail( $post['ID'] ) ) {
                                $post_thumbnail_id = get_post_thumbnail_id( $post['ID'] );

                                if ( !empty( $post_thumbnail_id ) ) {
                                    $featured_image = wp_get_attachment_image_src( $post_thumbnail_id, 'full' )[0];
                                }
                            }

                            $post['featured_image'] = $featured_image;

                            break;

                        case 'categories':
                            $post['categories'] = self::list_terms($post['ID'], 'category');
                            break;

                        case 'tags':
                            $post['tags'] = self::list_terms($post['ID'], 'post_tag');
                            break;

                        case 'source_title':
                            $source_post_id = get_post_meta($post['ID'], 'tdc-parent-post-id', true);
                            $source_post_title = '';
                            if ('' !== $source_post_id) {
                                $source_post_title = get_the_title($source_post_id);
                            }

                            $post['source_title'] = $source_post_title;
                            $post['source_url'] = esc_url( get_permalink( $source_post_id ) );

                            break;

                        case 'credits_cost':
							$post['credits_cost'] = '-';

                            if ( $options['creditsPostsFilter'] && is_array($tds_user_locker_credits) && !empty($tds_user_locker_credits[$post['ID']]) ) {
								$post['credits_cost'] = $tds_user_locker_credits[$post['ID']];
							} else if ( !empty($td_post_theme_settings['tds_locker_credits']) ) {
								$post['credits_cost'] = $td_post_theme_settings['tds_locker_credits'];
							}

                            break;

                        default:
                            $post[$column] = '';

                            if( !empty( $taxonomy = self::get_taxonomy_from_string($column) ) ) {
                                $post[$column] = self::list_terms( $post['ID'], $taxonomy );
                            } else {
                                $custom_field_data = td_util::get_acf_field_data($column, $post['ID']);

                                if( !$custom_field_data['meta_exists'] ) {
                                    if( metadata_exists('post', $post['ID'], $column) ) {
                                        $custom_field_data['value'] = get_post_meta($post['ID'], $column, true);
                                        $custom_field_data['type'] = 'text';
                                        $custom_field_data['meta_exists'] = true;
                                    }
                                }

                                if( !empty( $custom_field_data['value'] ) ) {
                                    if( $custom_field_data['type'] == 'image' ) {
                                        $img_url = '';

                                        if( is_array( $custom_field_data['value'] ) ) {
                                            $img_url = $custom_field_data['value']['url'];
                                        } else if( is_string( $custom_field_data['value'] ) ) {
                                            $img_url = $custom_field_data['value'];
                                        } else if ( is_numeric( $custom_field_data['value'] ) ) {
                                            $img_id = $custom_field_data['value'];
                                            $img_info = get_post( $img_id );

                                            if( $img_info ) {
                                                $img_url = $img_info->guid;
                                            }
                                        }

                                        $post[$column] = '<div class="tdb-pl-img" ' . ( $img_url != '' ? 'style="background-image:url(' . $img_url . ')"' : '' ) . '></div>';
                                    } else if( $custom_field_data['type'] == 'taxonomy' ) {
                                        $field_values = $custom_field_data['value'];

                                        foreach ( $field_values as $key => $field_value ) {
                                            $term_type = $custom_field_data['taxonomy'];
                                            $term_data = $field_value;
                                            if( is_numeric( $field_value ) ) {
                                                $term_data = get_term_by('term_id', $field_value, $term_type);
                                            }

                                            if( $term_data ) {
                                                $post[$column] .= $term_data->name;

                                                if( $key != array_key_last( $field_values ) ) {
                                                    $post[$column] .= ', ';
                                                }
                                            }
                                        }
                                    } else if ( $custom_field_data['type'] == 'user' ) {
                                        $field_values = $custom_field_data['value'];
                                        $field_users = array();

                                        if ( $custom_field_data['multiple'] ) {
                                            foreach ( $field_values as $field_value ) {
                                                $field_users[] = $field_value;
                                            }
                                        } else {
                                            $field_users[] = $field_values;
                                        }

                                        foreach ( $field_users as $key => $field_user ) {
                                            $user_display_name = '';

                                            if ( is_array($field_user) ) {
                                                $user_display_name = $field_user['display_name'];
                                            } else if ( is_object($field_user) ) {
                                                $user_display_name = $field_user->display_name;
                                            } else if ( is_numeric($field_user) ) {
                                                $user_info = get_userdata($field_user);
                                                $user_display_name = $user_info->display_name;
                                            }

                                            $post[$column] .= $user_display_name;

                                            if( $key != array_key_last( $field_users ) ) {
                                                $post[$column] .= ', ';
                                            }
                                        }
                                    } else if ( $custom_field_data['type'] == 'post_object' ) {
                                        $field_values = $custom_field_data['value'];
                                        $field_posts = array();

                                        if ( $custom_field_data['multiple'] ) {
                                            foreach ( $field_values as $field_value ) {
                                                $field_posts[] = $field_value;
                                            }
                                        } else {
                                            $field_posts[] = $field_values;
                                        }

                                        foreach ( $field_posts as $key => $field_post ) {
                                            $post_title = '';

                                            if ( is_object($field_post) ) {
                                                $post_title = $field_post->post_title;
                                            } else if ( is_numeric($field_post) ) {
                                                $post_title = get_the_title($field_post);
                                            }

                                            $post[$column] .= $post_title;

                                            if( $key != array_key_last( $field_posts ) ) {
                                                $post[$column] .= ', ';
                                            }
                                        }
                                    } else {
                                        $field_value = $custom_field_data['value'];

                                        if( is_array( $field_value ) ) {
                                            foreach ( $field_value as $key => $value ) {
                                                if( is_array( $value ) ) {
                                                    $post[$column] .= $value['label'];
                                                } else if( td_util::isAssocArray( $field_value ) ) {
                                                    if( $key == 'label' ) {
                                                        $post[$column] .= $value;
                                                    }
                                                } else {
                                                    $post[$column] .= $value;
                                                }

                                                if( $key != array_key_last( $field_value ) ) {
                                                    $post[$column] .= ', ';
                                                }
                                            }
                                        } else {
                                            $post[$column] .= $field_value;
                                        }
                                    }
                                }
                            }

                            break;
                    }
                }
	
				$posts[] = $post;
			}
		}

		/* -- Check for filters and apply them -- */
		$search_keyword = '';
		$search_in = 'title';
		$sorted_column_name = '';
		$sorted_column_order = '';

	    // create a hierarchical array of parent-child relationships
	    $sorted_posts = self::sort_posts( $posts );

		if( !empty($active_filters) ) {

			// Search by keyword
			if( isset( $active_filters['search'] ) ) {
				$search = $active_filters['search'];
				$search_keyword = $search['keyword'];
				$search_in = $search['in'];

				if( $search_keyword != '' ) {

					$posts = array_filter( $posts, function($post) use ( $search_keyword, $search_in ) {

						if( $search_in == 'id' ) {
							return $post['ID'] == $search_keyword;
						}

						return stripos( $post[$search_in], $search_keyword ) !== false;

					});

					// process search results posts to add parents from sorted posts
					$search_results_posts = array();
					foreach ( $posts as $post ) {
						$parent_posts = self::get_parent_posts( $sorted_posts, $post['ID'] );
						$search_results_posts = array_merge( $search_results_posts, $parent_posts );
						$search_results_posts[] = $post;
					}

					// sort posts hierarchically
                    $sorted_posts_from_keywords = self::sort_posts( $search_results_posts );
					$posts = self::build_posts_array( $sorted_posts_from_keywords, td_util::array_key_first( $sorted_posts_from_keywords ) );

				}
			}

			// Sort by column
			if( isset( $active_filters['columnSort'] ) ) {
				$column_sort = $active_filters['columnSort'];
                $column_sort_name = $sorted_column_name = $column_sort['name'];
                $column_sort_order = $sorted_column_order = $column_sort['order'];

                usort($posts, function($a, $b) use ( $search_keyword, $column_sort_name, $column_sort_order) {

                    switch( $column_sort_name ) {
                        case 'id':
                            if( $column_sort_order == 'DESC' ) {
                                return $b['ID'] - $a['ID'];
                            }
                
                            return $a['ID'] - $b['ID'];

                        case 'overall_rating':
                            if( $column_sort_order == 'DESC' ) {
                                return $b[$column_sort_name] - $a[$column_sort_name];
                            }
                
                            return $a[$column_sort_name] - $b[$column_sort_name];

                        case 'credits_cost':
							$a[$column_sort_name] = $a[$column_sort_name] != '-' ? $a[$column_sort_name] : 0;
							$b[$column_sort_name] = $b[$column_sort_name] != '-' ? $b[$column_sort_name] : 0;

                            if( $column_sort_order == 'DESC' ) {
                                return $b[$column_sort_name] - $a[$column_sort_name];
                            }
                
                            return $a[$column_sort_name] - $b[$column_sort_name];

                        case 'title':
                        case 'author':
                        case 'source_title':

		                    if( $search_keyword != '' ) {

								// sort only first level (main) parents posts
								if ( empty($a['depth']) && empty($b['depth']) ) {
									if( $column_sort_order == 'DESC' ) {
										return strcasecmp($b[$column_sort_name], $a[$column_sort_name]);
									}

									return strcasecmp($a[$column_sort_name], $b[$column_sort_name]);
								}

		                    } else {
			                    if( $column_sort_order == 'DESC' ) {
				                    return strcasecmp($b[$column_sort_name], $a[$column_sort_name]);
			                    }

			                    return strcasecmp($a[$column_sort_name], $b[$column_sort_name]);
		                    }

                        case 'date':
                            if( $column_sort_order == 'DESC' ) {
                                return strtotime($b['publish_date']) - strtotime($a['publish_date']);
                            }
                
                            return strtotime($a['publish_date']) - strtotime($b['publish_date']);
                        
                    }

                });

				// sort posts hierarchically only after a title/author/source_title sort
				if ( in_array( $column_sort_name, array( 'title', 'author', 'source_title' ) ) ) {
                    $sorted_posts_from_cols = self::sort_posts( $posts );
					$posts = self::build_posts_array( $sorted_posts_from_cols, td_util::array_key_first( $sorted_posts_from_cols ) );
				}

			}

		} else {

			// build a flat array of posts with depth in the display order
			$posts = self::build_posts_array($sorted_posts, td_util::array_key_first( $sorted_posts ));

		}


		/* -- Apply pagination settings -- */
		$enable_pag = $options['enablePagination'];
		$per_page = $options['perPage'];
		$num_pages = 3;
		$current_page = $options['currentPage'];
	
		if( !empty( $posts ) ) {
			if( $enable_pag ) {
				$posts_count = count($posts);
				$num_pages = ceil($posts_count / $per_page);
	
				$offset = ( $current_page - 1 ) * $per_page;
	
				$posts = array_slice($posts, $offset, $per_page);
			}
		} else {
            if( $is_composer ) {
                for ( $i = 1; $i < 6; $i++ ) {
                    $posts[] = array(
                        'ID' => $i,
                        'featured_image' => TDB_URL . '/assets/images/td_meta_replacement.png',
                        'title' => 'Sample post ' . $i,
                        'author' => 'John Doe',
                        'author_url' => '#',
                        'publish_date' =>  date( get_option( 'date_format' ), time() ),
                        'status' => 'Published',
                        'overall_rating' => $i,
                    );
                }
            }
        }
	
	

		/* --
		-- BUILD THE LIST HTML
		-- */
		if( empty( $columns_to_display ) ) {
			/* -- The user needs to fill in the columns option -- */
			$buffy .= td_util::get_block_error('Posts List', 'You have not selected any <strong>columns</strong> to display.' );
		} else {

			/* -- Render the search input -- */
			$buffy .= '<div class="tdb-s-form tdb-plist-search">';
				$buffy .= '<div class="tdb-s-form-content">';
					$buffy .= '<div class="tdb-s-fc-inner">';
						$buffy .= '<div class="tdb-s-form-group tdb-s-form-group-sm tdb-s-form-group-keyword">';
							$buffy .= '<input type="text" class="tdb-s-form-input tdb-plist-search-keyword" placeholder="Search by keyword..." value="' . $search_keyword . '">';
						$buffy .= '</div>';

						$buffy .= '<div class="tdb-s-form-group tdb-s-form-group-sm tdb-s-form-group-in">';
							$buffy .= '<div class="tdb-s-form-select-wrap">';
								$buffy .= '<select class="tdb-s-form-input tdb-plist-search-in">';
									foreach ( $columns_to_display as $key => $column ) {
										if( $column == 'featured_image' || $column == 'date' || $column == 'overall_rating' ) {
											continue;
										}

										$buffy .= '<option value="' . $column . '" ' . ( $search_in == $column ? 'selected' : '' ) . '>';
											if( in_array( $column, $predefined_columns ) ) {
												$buffy .= self::display_column_name($column, $columns_to_display_labels[$key]);
											} else {
												$buffy .= self::display_custom_column_name($column, $columns_to_display_labels[$key]);
											}
										$buffy .= '</option>';
									}
								$buffy .= '</select>';

								$buffy .= '<svg class="tdb-s-form-select-icon" xmlns="http://www.w3.org/2000/svg" width="8.947" height="12.578" viewBox="0 0 8.947 12.578"><g transform="translate(7.947 1) rotate(90)"><path d="M0,7.947A1,1,0,0,1-.58,7.761,1,1,0,0,1-.815,6.366l2.06-2.893L-.815.58A1,1,0,0,1-.58-.815,1,1,0,0,1,.815-.58L3.288,2.893a1,1,0,0,1,0,1.16L.815,7.527A1,1,0,0,1,0,7.947Z" transform="translate(8.104 0)"/><path d="M2.474,7.947a1,1,0,0,1-.815-.42L-.815,4.053a1,1,0,0,1,0-1.16L1.659-.58A1,1,0,0,1,3.053-.815,1,1,0,0,1,3.288.58L1.228,3.473l2.06,2.893a1,1,0,0,1-.814,1.58Z" transform="translate(0 0)"/></g></svg>';
							$buffy .= '</div>';
						$buffy .= '</div>';

						$buffy .= '<div class="tdb-s-form-group tdb-s-form-group-sm tdb-s-form-group-button">';
							$buffy .= '<button class="tdb-s-btn tdb-s-btn-sm">Search</button>';
						$buffy .= '</div>';
					$buffy .= '</div>';
				$buffy .= '</div>';
			$buffy .= '</div>';


			/* -- Check if we have any posts to display -- */
			if( empty( $posts ) || ( $is_composer && $options['showInComposer'] == 'no_posts' ) ) {
				$buffy .= '<div class="tdb-s-notif tdb-s-notif-info"><div class="tdb-s-notif-descr">';
					if( $search_keyword != '' ) {
						$buffy .= __td( 'No search results.', TD_THEME_NAME );
					} elseif ( $options['creditsPostsFilter'] ) {
						$buffy .= __td( 'You have not bought any posts.', TD_THEME_NAME );
					} else {
						$buffy .= __td( 'You have not created any posts.', TD_THEME_NAME );
					}
				$buffy .= '</div></div>';
			} else {
				// Posts list
				$buffy .= '<table class="tdb-s-table tdb-s-content">';
					$buffy .= '<thead class="tdb-s-table-header">';
						$buffy .= '<tr class="tdb-s-table-row tdb-s-table-row-h">';
							// Predefined columns headings
							foreach ( $columns_to_display as $key => $column ) {
								if ( $column == 'credits_cost' && ( !$options['creditsPostsFilter'] || $options['creditsPostsFilter'] === 'false' ) ) {
									continue;
								}

								$column_name = '';
								$column_sortable = false;

								if( in_array( $column, $predefined_columns ) ) {
									if( $column != 'featured_image' && $column != 'categories' && $column != 'tags' ) {
										$column_sortable = true;
									}

									$column_name = self::display_column_name($column, $columns_to_display_labels[$key]);
								} else {
									$column_name = self::display_custom_column_name($column, $columns_to_display_labels[$key]);
								}

								$buffy .= '<th class="tdb-s-table-col" data-column="' . $column . '" ' . ( $column_sortable ? ( $sorted_column_name == $column ? 'data-order="' . $sorted_column_order . '"' : '' ) : '' ) . '>';
									if( $column_sortable ) {
										$buffy .= '<div class="tdb-s-table-col-order">';
									}
										$buffy .= $column_name;

										if( $column_sortable ) {
											$buffy .= $column_order_icons;
										}
									if( $column_sortable ) {
										$buffy .= '</div>';
									}
								$buffy .= '</th>';
							}
	
							$buffy .= '<th class="tdb-s-table-col tdb-s-table-col-options"></th>';
						$buffy .= '</tr>';
					$buffy .= '</thead>';
	
					$buffy .= '<tbody class="tdb-s-table-body">';
						foreach ($posts as $post) {

							$buffy .= '<tr class="tdb-s-table-row tdb-plist-post" data-post-id="' . $post['ID'] . '">';
								// Columns values
								foreach ( $columns_to_display as $key => $column ) {
									if ( $column == 'credits_cost' && ( !$options['creditsPostsFilter'] || $options['creditsPostsFilter'] === 'false' ) ) {
										continue;
									}

									switch ($column) {
										case 'id':
											$buffy .= '<td class="tdb-s-table-col">';
												$buffy .= '<div class="tdb-s-table-col-label">' . self::display_column_name($column, $columns_to_display_labels[$key]) . '</div>';
												$buffy .= '#' . $post['ID'];
											$buffy .= '</td>';
											break;
	
										case 'overall_rating':
											$buffy .= '<td class="tdb-s-table-col">';
												$buffy .= '<div class="tdb-s-table-col-label">' . self::display_column_name($column, $columns_to_display_labels[$key]) . '</div>';

												if( $post['overall_rating'] ) {
													$buffy .= self::display_rating_stars( $post['overall_rating'], $full_star_icon_html, $full_star_icon_data, $half_star_icon_html, $half_star_icon_data, $empty_star_icon_html, $empty_star_icon_data );
												} else {
													$buffy .= __td( 'No rating', TD_THEME_NAME );
												}
												
											$buffy .= '</td>';
											break;
	
										case 'title':
											$buffy .= '<td class="tdb-s-table-col tdb-s-table-col-title">';
												$buffy .= '<div class="tdb-s-table-col-label">' . self::display_column_name($column, $columns_to_display_labels[$key]) . '</div>';
												$buffy .= $post['title'];
												if( $post['status'] != 'Published' ) {
													$buffy .= '<span class="tdb-plist-title-status"> (' . $post['status'] . ')</span>';
												}
											$buffy .= '</td>';
											break;
	
										case 'featured_image':
											$buffy .= '<td class="tdb-s-table-col">';
												$buffy .= '<div class="tdb-s-table-col-label">' . self::display_column_name($column, $columns_to_display_labels[$key]) . '</div>';
												$buffy .= '<div class="tdb-pl-img" ' . ( $post['featured_image'] != '' ? 'style="background-image:url(' . $post['featured_image'] . ')"' : '' ) . '></div>';
											$buffy .= '</td>';
											break;

										case 'categories':
										case 'tags':
                                            $buffy .= '<td class="tdb-s-table-col">';
                                                $buffy .= '<div class="tdb-s-table-col-label">' . self::display_column_name($column, $columns_to_display_labels[$key]) . '</div>';
                                                $buffy .= $post[$column];
                                            $buffy .= '</td>';
                                            break;

										case 'date':
											$buffy .= '<td class="tdb-s-table-col">';
												$buffy .= '<div class="tdb-s-table-col-label">' . self::display_column_name($column, $columns_to_display_labels[$key]) . '</div>';
												$buffy .= $post['publish_date'];
											$buffy .= '</td>';
											break;
	
										case 'author':
											$buffy .= '<td class="tdb-s-table-col">';
												$buffy .= '<div class="tdb-s-table-col-label">' . self::display_column_name($column, $columns_to_display_labels[$key]) . '</div>';
												$buffy .= '<a href="' . $post['author_url'] . '">' . $post['author'] . '</a>';
											$buffy .= '</td>';
											break;
	
										case 'source_title':
											$buffy .= '<td class="tdb-s-table-col">';
												$buffy .= '<div class="tdb-s-table-col-label">' . self::display_column_name($column, $columns_to_display_labels[$key]) . '</div>';
												$buffy .= !empty($post['source_title']) ? '<a href="' . $post['source_url'] . '">' . $post['source_title'] . '</a>' : '';
											$buffy .= '</td>';
											break;

                                        default:
                                            $buffy .= '<td class="tdb-s-table-col">';
                                                $buffy .= '<div class="tdb-s-table-col-label">' . self::display_custom_column_name($column, $columns_to_display_labels[$key]) . '</div>';
                                                $buffy .= !empty($post[$column]) ? $post[$column] : '';
                                            $buffy .= '</td>';
                                            break;
									}
								}
	
								// Options list
								$buffy .= '<td class="tdb-s-table-col tdb-s-table-col-options">';
									$buffy .= '<svg class="tdb-s-table-options-toggle" xmlns="http://www.w3.org/2000/svg" width="4.001" height="16" viewBox="0 0 4.001 16"><g transform="translate(-1210.999 -335)"><path d="M-10898,14a2,2,0,0,1,2-2,2,2,0,0,1,2,2,2,2,0,0,1-2,2A2,2,0,0,1-10898,14Zm0-6a2,2,0,0,1,2-2,2,2,0,0,1,2,2,2,2,0,0,1-2,2A2,2,0,0,1-10898,8Zm0-6a2,2,0,0,1,2-2,2,2,0,0,1,2,2,2,2,0,0,1-2,2A2,2,0,0,1-10898,2Z" transform="translate(12109 335)"/></g></svg>';
	
									$buffy .= '<div class="tdb-s-table-options-list">';
										$buffy .= '<a class="tdb-s-tol-item" href="' . esc_url(get_permalink($post['ID'])) . '" target="blank">' . __td( 'View', TD_THEME_NAME ) . '</a>';

                                        if( $main_form_url != '' || $extra_form_1_url != '' || $extra_form_2_url != '' || $child1_form_url != '' || $child2_form_url != '' ) {
//                                            $buffy .= '<div class="tdb-s-tol-sep"></div>';

                                            $parent_id = wp_get_post_parent_id( $post['ID'] );
                                            $parent_post = false;
                                            $post_level = 0;

                                            if ( $parent_id ) {
                                                $post_level = count( get_post_ancestors( $post['ID'] ) ) + 1;
                                            } else {
                                                $parent_post = true;
                                            }

                                            if ( $parent_post ) {
                                                if ($main_form_url != '') {
                                                    $buffy .= '<div class="tdb-s-tol-sep"></div>';
                                                    $buffy .= '<a class="tdb-s-tol-item" href="' . esc_url(add_query_arg('post_id', $post['ID'], $main_form_url)) . '">' . $main_form_edit_txt . '</a>';
                                                }
                                            }
                                            if( $extra_form_1_url != '' ) {
                                                $buffy .= '<div class="tdb-s-tol-sep"></div>';
                                                $buffy .= '<a class="tdb-s-tol-item" href="' . esc_url(add_query_arg('post_id', $post['ID'], $extra_form_1_url) ) . '">' . $extra_form_1_edit_txt . '</a>';
                                            }
                                            if( $extra_form_2_url != '' ) {
                                                $buffy .= '<div class="tdb-s-tol-sep"></div>';
                                                $buffy .= '<a class="tdb-s-tol-item" href="' . esc_url(add_query_arg('post_id', $post['ID'], $extra_form_2_url) ) . '">' . $extra_form_2_edit_txt . '</a>';
                                            }
                                            if( $child1_form_url != '' || $child2_form_url != '' ) {
                                                if( $child1_form_url != '' && $post_level == 2 ) {
                                                    $buffy .= '<div class="tdb-s-tol-sep"></div>';
                                                    $buffy .= '<a class="tdb-s-tol-item" href="' . esc_url(add_query_arg('post_id', $post['ID'], $child1_form_url) ) . '">' . $child1_form_edit_txt . '</a>';
                                                }
                                                if( $child2_form_url != '' && $post_level > 2 ) {
                                                    $buffy .= '<div class="tdb-s-tol-sep"></div>';
                                                    $buffy .= '<a class="tdb-s-tol-item" href="' . esc_url(add_query_arg('post_id', $post['ID'], $child2_form_url) ) . '">' . $child2_form_edit_txt . '</a>';
                                                }
                                            }
                                        }

	
										if( ( $is_current_user_admin || $options['allowPublish'] || $options['allowPublish'] === 'true' ) &&
											( $post['status'] == 'Pending' || $post['status'] == 'Draft' || $post['status'] == 'Private' )
										) {
											$buffy .= '<div class="tdb-s-tol-sep"></div>';
											$buffy .= '<a class="tdb-s-tol-item tdb-plist-publish-post" href="#" data-post-id="' . $post['ID'] . '" data-post-title="' . $post['title'] . '">' . __td( 'Publish', TD_THEME_NAME ) . '</a>';
										}
	
										if( $is_current_user_admin || $options['allowDelete'] || $options['allowDelete'] === 'true' ) {
											$buffy .= '<div class="tdb-s-tol-sep"></div>';
											$buffy .= '<a class="tdb-s-tol-item tdb-s-tol-item-red tdb-plist-delete-post" data-post-id="' . $post['ID'] . '" data-post-title="' . $post['title'] . '" href="#">' . __td( 'Delete', TD_THEME_NAME ) . '</a>';
										}
									$buffy .= '</div>';
								$buffy .= '</td>';
							$buffy .= '</tr>';
						}
					$buffy .= '</tbody>';
				$buffy .= '</table>';
	
				// Pagination
				if( $enable_pag != '' ) {
					$buffy .= tdc_util::get_custom_pagination(
						$current_page,
						$num_pages,
						'tdb_posts_list_page',
						3,
						array(
							'wrapper' => 'tdb-s-pagination',
							'item' => 'tdb-s-pagination-item',
							'active' => 'tdb-s-pagination-active',
							'dots' => 'tdb-s-pagination-dots'
						)
					);
				}
			}

		}
	

		/* -- Render the add new post button -- */
		if( $main_form_url != '' ) {
			if( ( $is_current_user_admin || !$options['addNewPostLimitReached'] ) && !( $is_composer && $options['showNotifInComposer'] == 'yes' ) ) {
				$buffy .= '<a class="tdb-s-btn tdb-plst-add" href="' . esc_url($main_form_url) . '">' . $main_form_add_txt . '</a>';
			} else {
				$buffy .= $limit_notif;
			}
		} else if( $is_composer && $options['showNotifInComposer'] == 'yes' ) {
            $buffy .= $limit_notif;
        }


		return $buffy;

	}




	/* ---------
	---- FORMAT PREDEFINED COLUMN NAME
	--------- */
	static function display_column_name( $column, $label = '' ) {

		$column_name = '';

		switch ( $column ) {
			case 'id':
				$column_name = !empty( $label ) ? $label : __td( 'ID', TD_THEME_NAME );
				break;

			case 'overall_rating':
				$column_name = !empty( $label ) ? $label : __td( 'Rating', TD_THEME_NAME );
				break;

			case 'title':
				$column_name = !empty( $label ) ? $label : __td( 'Title', TD_THEME_NAME );
				break;

			case 'featured_image':
				$column_name = !empty( $label ) ? $label : __td( 'Post image', TD_THEME_NAME );
				break;

			case 'categories':
				$column_name = !empty( $label ) ? $label : __td( 'Categories', TD_THEME_NAME );
				break;

			case 'tags':
				$column_name = !empty( $label ) ? $label : __td( 'Tags', TD_THEME_NAME );
				break;

			case 'date':
				$column_name = !empty( $label ) ? $label : __td( 'Date', TD_THEME_NAME );
				break;

			case 'author':
				$column_name = !empty( $label ) ? $label : __td( 'Author', TD_THEME_NAME );
				break;

			case 'source_title':
				$column_name = !empty( $label ) ? $label : __td( 'Source title', TD_THEME_NAME );
				break;

			case 'credits_cost':
				$column_name = !empty( $label ) ? $label : __td( 'Credits cost', TD_THEME_NAME );
				break;
		}

		return $column_name;

	}




	/* ---------
	---- FUNCTION USED TO DISPLAY A CUSTOM FIELD COLUMN'S NAME
	--------- */
    static function display_custom_column_name( $custom_column, $label ) {

        if( !empty( $label ) ) {
            return $label;
        }

        if( !empty( $taxonomy = self::get_taxonomy_from_string($custom_column) ) ) {
            $taxonomy_labels = get_taxonomy_labels(get_taxonomy($taxonomy));

            return $taxonomy_labels->name;
        } else if( class_exists('ACF') ) {
            $acf_field_data = acf_get_raw_field($custom_column);

            if( $acf_field_data ) {
                return $acf_field_data['label'];
            }
        }

        return $custom_column;

    }




	/* ---------
	---- GET A TAXONOMY NAME FROM A PREDEFINED FIELD
	--------- */
    static function get_taxonomy_from_string( $string ) {

        $taxonomy = '';
        preg_match_all('/ctax_{(\S*)}/', $string, $taxonomy_matches);

        if( !empty($taxonomy_matches) &&
            is_array($taxonomy_matches) &&
            count($taxonomy_matches) >= 2 &&
            is_array($taxonomy_matches[1]) && !empty($taxonomy_matches[1]) 
        ) {
            if( taxonomy_exists( $taxonomy_matches[1][0] ) ) {
                $taxonomy = $taxonomy_matches[1][0];
            }
        }

        return $taxonomy;
    }



	/* ---------
	---- RENDER TEMRS IN A STRING LIST FORMAT
	--------- */
    static function list_terms( $post_id, $taxonomy ) {

        $buffy = '';
        $terms = get_the_terms( $post_id, $taxonomy );

        if( !is_wp_error( $terms ) && $terms !== false ) {
            $buffy .= '<div class="tdb-pl-terms">';
                foreach( $terms as $key => $term ) {
                    if( $key !== key($terms) ) {
                        $buffy .= ', ';
                    }

                    $buffy .= '<a href="' . esc_url( get_term_link( $term ) ) . '">' . $term->name . '</a>';
                }
            $buffy .= '</div>';
        }

        return $buffy;

    }




	/* ---------
	---- RENDER RATING STARS BASED ON RATING AVERAGE
	--------- */
    static function display_rating_stars( $rating_average, $full_star_icon, $full_star_icon_data, $half_star_icon, $half_star_icon_data, $empty_star_icon, $empty_star_icon_data ) {

        $rating_average_floor = floor($rating_average);
        $rating_average_ceil = ceil($rating_average);

        if( $empty_star_icon == '' ) {
            $empty_star_icon = '<i class="td-icon-user-rev-star-empty"></i>';
        }
        if( $half_star_icon == '' ) {
            $half_star_icon = '<i class="td-icon-user-rev-star-half"></i>';
        }
        if( $full_star_icon == '' ) {
            $full_star_icon = '<i class="td-icon-user-rev-star-full"></i>';
        }

        $buffy = '<div class="tdb-plist-stars">';
            for( $i = 0; $i < $rating_average_floor; $i++ ) {
                $buffy .= '<div class="tdb-plist-star tdb-plist-star-full" ' . $full_star_icon_data . '>' . $full_star_icon . '</div>';
            }
            if( $rating_average_floor != $rating_average ) {
                $buffy .= '<div class="tdb-plist-star tdb-plist-star-half" ' . $half_star_icon_data . '>' . $half_star_icon . '</div>';
            }
            for( $i = 5; $i > $rating_average_ceil; $i-- ) {
                $buffy .= '<div class="tdb-plist-star tdb-plist-star-empty" ' . $empty_star_icon_data . '>' . $empty_star_icon . '</div>';
            }
        $buffy .= '</div>';

        return $buffy;

    }

	// create a hierarchical array to store parent-child relationships
	static function sort_posts( $posts ) {

		$sorted_posts_array = array();

		foreach ( $posts as $post ) {

			$parent_id = 0;

			// get parent meta
			$parent_post_id_meta = get_post_meta( $post['ID'], 'tdc-parent-post-id', true );

			// set parent from meta
			if( !empty( $parent_post_id_meta ) ) {
				$parent_id = $parent_post_id_meta;

			// set parent from post data
			} elseif ( !empty( $post['parent'] ) ) {
				$parent_id = $post['parent'];
			}

			if ( !isset($sorted_posts_array[$parent_id]) ) {
				$sorted_posts_array[$parent_id] = array();
			}

			$sorted_posts_array[$parent_id][$post['ID']] = $post;

		}

		return $sorted_posts_array;

	}

	// recursive function to process the hierarchical posts list
	static function build_posts_array( $posts, $parent_id, $depth = 0, &$result = array() ) {

		if ( isset($posts[$parent_id]) && is_array($posts[$parent_id]) ) {
			foreach ($posts[$parent_id] as $post_id => $post) {
				$post['depth'] = $depth;

				$posts_title_prefix = str_repeat( '-', $depth );
				if( $posts_title_prefix != '' ) {
					$posts_title_prefix .= ' ';
				}
				$post['title'] = !empty( $posts_title_prefix ) ? $posts_title_prefix . $post['title'] : $post['title'];

				$result[] = $post;
				self::build_posts_array( $posts, $post_id, $depth + 1, $result );
			}
		}

		return $result;

	}

	// function to get parent posts for a specific post
	static function get_parent_posts( $sorted_posts, $post_id, &$result = array() ) {

		foreach ( $sorted_posts as $parent_id => $child_posts ) {
			if ( isset($child_posts[$post_id]) ) {
				$result[] = $child_posts[$post_id];
				self::get_parent_posts( $sorted_posts, $parent_id, $result );
				break;
			}
		}

		return $result;

	}

}