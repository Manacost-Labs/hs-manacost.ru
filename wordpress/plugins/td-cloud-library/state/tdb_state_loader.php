<?php




class tdb_state_loader {


    /**
     * This is used for composer iframe and composer ajax calls to set the state.
     *  - The global wp_query is the template's
     *  - We have to get the content by making a new wp_query
     */
    static function on_tdc_loaded_load_state() {

        if ( tdc_state::is_live_editor_ajax() || tdc_state::is_live_editor_iframe() ) {

            global $tdb_state_single_page, $tdb_state_single, $tdb_state_category, $tdb_state_author, $tdb_state_search, $tdb_state_date, $tdb_state_tag, $tdb_state_attachment;

            // get the content id and content type
            $tdbLoadDataFromId = tdb_util::get_get_val('tdbLoadDataFromId');
            $tdbTemplateType = tdb_util::get_get_val('tdbTemplateType');

            // try to load the content, if we fail to load it, we will ship the default state... ? @todo ?
            if ( $tdbLoadDataFromId !== false && $tdbTemplateType !== false ) {
				switch ( $tdbTemplateType ) {
                    case 'single':
                        // get the content wp_query
                        $wp_query_content = new WP_Query( array(
                                'page_id' => $tdbLoadDataFromId,
                                'post_type' => 'post'
                            )
                        );
                        $tdb_state_single->set_wp_query($wp_query_content);
                    break;

	                case 'cpt':

						$loadedPost = get_post($tdbLoadDataFromId);

		                // get the content wp_query
		                $wp_query_content = new WP_Query( array(
				                'page_id' => $tdbLoadDataFromId,
				                'post_type' => $loadedPost->post_type
			                )
		                );
		                $tdb_state_single->set_wp_query($wp_query_content);
		                break;

                    case 'attachment':
                        // get the content wp_query
                        $wp_query_content = new WP_Query( array(
                                'page_id' => $tdbLoadDataFromId,
                                'post_type' => 'attachment'
                            )
                        );
                        $tdb_state_attachment->set_wp_query($wp_query_content);
                    break;

	                case 'cpt_tax':

		                add_action( 'init', function() {

							global $tdb_state_category;

							$template_id = '';
	                        $tem_content = '';

                            // can be a custom taxonomy term id or a cpt name
			                $tdbLoadDataFromId = tdb_util::get_get_val('tdbLoadDataFromId');

                            // if it's a cpt name
                            if ( is_string($tdbLoadDataFromId) && post_type_exists($tdbLoadDataFromId) ) {

                                $cpt_obj = get_post_type_object($tdbLoadDataFromId);

                                // stop here if cpt obj is not valid, we will load sample data
                                if ( empty($cpt_obj) || !$cpt_obj instanceof WP_Post_Type ) {
                                    return;
                                }

                                if ( tdc_state::is_live_editor_ajax() ) {
                                    $tem_content = stripcslashes( $_POST['shortcode'] );
                                } else {

                                    // get cpt settings
                                    $td_cpt = td_util::get_option('td_cpt');

                                    $tdb_post_type_archive_template = !empty($td_cpt[$cpt_obj->name]['archive_tpl']) ? $td_cpt[$cpt_obj->name]['archive_tpl'] : false;

                                    // if we find a template
                                    if ( $tdb_post_type_archive_template && td_global::is_tdb_template( $tdb_post_type_archive_template, true ) ) {
                                        $template_id = td_global::tdb_get_template_id( $tdb_post_type_archive_template );
                                    }

                                    // if we have a template build the tpl query
                                    if ( !empty($template_id) ) {

                                        // load the tdb template
                                        $wp_query_template = new WP_Query( array(
                                            'p' => $template_id,
                                            'post_type' => 'tdb_templates'
                                        ) );

                                    }

                                    if ( !empty( $wp_query_template ) && $wp_query_template->have_posts() ) {
                                        $tem_content = $wp_query_template->post->post_content;
                                    }

                                }

                                $args = array(
                                    'post_type' => $cpt_obj->name,
                                    'posts_per_page' => tdb_util::get_shortcode_att( $tem_content, 'tdb_loop', 'limit' ),
                                    'offset' => tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','offset' ),
                                );

                            } else {

                                $term_obj = get_term($tdbLoadDataFromId);

                                // stop here if term obj is not valid, we will load sample data
                                if ( empty($term_obj) || !$term_obj instanceof WP_Term ) {
                                    return;
                                }

                                // set term taxonomy
                                $term_obj_tax = $term_obj->taxonomy;

                                if ( tdc_state::is_live_editor_ajax() ) {
                                    $tem_content = stripcslashes( $_POST['shortcode'] );
                                } else {

                                    $td_cpt_tax = td_util::get_option('td_cpt_tax');

                                    $default_template_id = ( !empty($term_obj_tax) && isset( $td_cpt_tax[$term_obj_tax]['tdb_category_template'] ) ) ? $td_cpt_tax[$term_obj_tax]['tdb_category_template'] : '';

                                    // if we find an individual template..
                                    if ( td_global::is_tdb_template( $default_template_id, true ) ) {
                                        $template_id = td_global::tdb_get_template_id( $default_template_id );
                                    }

                                    // if we don't have a template do not build the query
                                    if ( !empty( $template_id ) ) {

                                        // load the tdb template
                                        $wp_query_template = new WP_Query( array(
                                            'p' => $template_id,
                                            'post_type' => 'tdb_templates'
                                        ) );
                                    }

                                    if ( !empty( $wp_query_template ) && $wp_query_template->have_posts() ) {
                                        $tem_content = $wp_query_template->post->post_content;
                                    }

                                }

                                $args = array(
                                    'tax_query' => [
                                        [ 'taxonomy' => $term_obj_tax, 'terms' => $tdbLoadDataFromId ]
                                    ],
                                    'posts_per_page' => tdb_util::get_shortcode_att( $tem_content, 'tdb_loop', 'limit' ),
                                    'offset' => tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','offset' ),
                                );

                            }

	                        // exclude or include certain posts or pages from your posts loop
	                        $posts_not_in = self::parse_shortcode_att( tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','post_ids' ), 'post__not_in' );
	                        $posts_in = self::parse_shortcode_att( tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','post_ids' ), 'post__in' );

	                        if ( !empty($posts_in) && is_array($posts_in) ) {
	                            $args['post__in'] = $posts_in;
	                            $args['orderby'] = 'post__in';
	                        }

	                        if ( !empty($posts_not_in) && is_array($posts_not_in) ) {
	                            $args['post__not_in'] = $posts_not_in;
	                        }

	                        // get post types from att
	                        $installed_post_types = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop', 'installed_post_types' );
	                        if ( !empty($installed_post_types) ) {
	                            $array_selected_post_types = array();
	                            $expl_installed_post_types = explode(',', $installed_post_types);
	                            foreach ($expl_installed_post_types as $val_this_post_type) {
	                                if (trim($val_this_post_type) != '') {
	                                    $array_selected_post_types[] = trim($val_this_post_type);
	                                }
	                            }
	                            $args['post_type'] = $array_selected_post_types;//$installed_post_types;
	                        }

                            // include/exclude posts with CF
                            $include_cf_posts = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop', 'include_cf_posts' );
                            $exclude_cf_posts = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop', 'exclude_cf_posts' );

                            if ( !empty($include_cf_posts) || !empty($exclude_cf_posts) ) {

                                $include_cf_posts = trim($include_cf_posts);
                                $exclude_cf_posts = trim($exclude_cf_posts);

                                // include or the same CF is filled
                                if ( $include_cf_posts === $exclude_cf_posts || ( !empty($include_cf_posts) && empty($exclude_cf_posts) )  ) {
                                    $args['meta_query'] = array(
                                        array(
                                            'key' => $include_cf_posts,
                                            'value' => '1',
                                        ),
                                    );
                                } else {
                                    if ( !empty($exclude_cf_posts) ) {  // exclude
                                        if ( empty($include_cf_posts) ) { // exclude, but show all posts without meta key
                                            $args['meta_query']['relation'] = 'OR';
                                            $args['meta_query'][] = array(
                                                array(
                                                    'key' => $exclude_cf_posts,
                                                    'compare' => 'NOT EXISTS'
                                                ),
                                            );
                                        }

                                        $args['meta_query'][] = array(
                                            array(
                                                'key' => $exclude_cf_posts,
                                                'value' => '1',
                                                'compare' => '!=',
                                            ),
                                        );
                                    }

                                    if ( !empty($include_cf_posts) ) {  // include

                                        if ( isset($args['meta_query']) ) {
                                            $args['meta_query']['relation'] = 'AND';
                                            $args['meta_query'][] = array(
                                                array(
                                                    'key' => $include_cf_posts,
                                                    'value' => '1',
                                                ),
                                            );
                                        } else {
                                            $args['meta_query'] = array(
                                                array(
                                                    'key' => $include_cf_posts,
                                                    'value' => '1',
                                                ),
                                            );
                                        }
                                    }
                                }
                            }

                            // sort posts
	                        $sort = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','sort' );
	                        switch ($sort) {
	                            case 'oldest_posts':
	                                $args['order'] = 'ASC';
	                                break;

	                            case 'modified_date':
	                                $args['orderby'] = 'post_modified';
	                                break;

	                            case 'alphabetical_order':
	                                $args['orderby'] = 'title';
	                                $args['order'] = 'ASC';

	                                break;

	                            case 'popular':
	                                $args['meta_key'] = td_page_views::$post_view_counter_key;
	                                $args['orderby'] = 'meta_value_num';
	                                $args['order'] = 'DESC';

	                                break;

	                            case 'popular7':
	                                $args['meta_query'] = 	array(
	                                    'relation' => 'AND',
	                                    array(
	                                        'key'     => td_page_views::$post_view_counter_7_day_total,
	                                        'type'    => 'numeric'
	                                    ),
	                                    array(
	                                        'key'     => td_page_views::$post_view_counter_7_day_last_date,
	                                        'value'   => (date('U') - 604800), // current date minus 7 days
	                                        'type'    => 'numeric',
	                                        'compare' => '>'
	                                    )
	                                );
	                                $args['orderby'] = td_page_views::$post_view_counter_7_day_total;
	                                $args['order'] = 'DESC';

	                                break;

	                            case 'review_high':
	                                $args['meta_key'] = 'td_review_key';
	                                $args['orderby'] = 'meta_value_num';
	                                $args['order'] = 'DESC';

	                                break;

	                            case 'comment_count':
	                                $args['orderby'] = 'comment_count';
	                                $args['order'] = 'DESC';

	                                break;
	                        }

		                    // locked content
		                    $locked_only = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','locked_only' );
		                    if ( defined('TD_SUBSCRIPTION') && !empty( $locked_only ) ) {
			                    $args['meta_key'] = 'tds_lock_content';
		                    }

	                        // build content wp_query from args
	                        $wp_query_content = new WP_Query($args);

                            // set cpt_tax template is_cpt_post_type_archive state
                            if ( !empty($cpt_obj) ) {
                                $tdb_state_category->set_cpt_post_type_archive();
                            }

                            $tdb_state_category->set_tax();
	                        $tdb_state_category->set_wp_query($wp_query_content);

						});

                        break;

                    case 'category':

                        $template_id = '';
                        $tem_content = '';

                        if ( tdc_state::is_live_editor_ajax() ) {
                            $tem_content = stripcslashes( $_POST['shortcode'] );
                        } else {

                            $current_category_obj = get_category( $tdbLoadDataFromId );
                            $current_category_id = $current_category_obj->cat_ID;

                            // read the individual cat template
                            $tdb_individual_category_template = td_util::get_category_option( $current_category_id, 'tdb_category_template' );

                            // read the global template
                            $tdb_category_template = td_options::get( 'tdb_category_template' );

                            // if we find an individual template..
                            if ( !empty( $tdb_individual_category_template ) && td_global::is_tdb_template( $tdb_individual_category_template, true ) ) {
                                $template_id = td_global::tdb_get_template_id( $tdb_individual_category_template );
                            } else {
                                // if we don't find an individual template go for a global one
                                if ( td_global::is_tdb_template( $tdb_category_template ) ) {
                                    $template_id = td_global::tdb_get_template_id( $tdb_category_template );
                                }
                            }

                            // if we don't have a template do not build the query
                            if ( !empty( $template_id ) ) {

                                // load the tdb template
                                $wp_query_template = new WP_Query( array(
                                        'p' => $template_id,
                                        'post_type' => 'tdb_templates'
                                    )
                                );
                            }

                            if ( !empty( $wp_query_template ) && $wp_query_template->have_posts() ) {
                                $tem_content = $wp_query_template->post->post_content;
                            }
                        }

                        $args = array(
                            'cat' => $tdbLoadDataFromId,
                            'posts_per_page' => tdb_util::get_shortcode_att($tem_content, 'tdb_loop', 'limit'),
                            'offset' => tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','offset' ),
                        );

                        // exclude or include certain posts or pages from your posts loop
                        $posts_not_in = self::parse_shortcode_att( tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','post_ids' ), 'post__not_in' );
                        $posts_in     = self::parse_shortcode_att( tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','post_ids' ), 'post__in' );

                        if ( !empty($posts_in) && is_array($posts_in) ) {
                            $args['post__in'] = $posts_in;
                            $args['orderby'] = 'post__in';
                        }

                        if ( !empty($posts_not_in) && is_array($posts_not_in) ) {
                            $args['post__not_in'] = $posts_not_in;
                        }

                        // get post types from att
                        $installed_post_types = tdb_util::get_shortcode_att($tem_content, 'tdb_loop', 'installed_post_types');
                        if (!empty($installed_post_types)) {
                            $array_selected_post_types = array();
                            $expl_installed_post_types = explode(',', $installed_post_types);
                            foreach ($expl_installed_post_types as $val_this_post_type) {
                                if (trim($val_this_post_type) != '') {
                                    $array_selected_post_types[] = trim($val_this_post_type);
                                }
                            }
                            $args['post_type'] = $array_selected_post_types;//$installed_post_types;
                        }


                        // include/exclude posts with CF
                        $include_cf_posts = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop', 'include_cf_posts' );
                        $exclude_cf_posts = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop', 'exclude_cf_posts' );

                        if ( !empty($include_cf_posts) || !empty($exclude_cf_posts) ) {

                            $include_cf_posts = trim($include_cf_posts);
                            $exclude_cf_posts = trim($exclude_cf_posts);

                            // include or the same CF is filled
                            if ( $include_cf_posts === $exclude_cf_posts || ( !empty($include_cf_posts) && empty($exclude_cf_posts) )  ) {
                                $args['meta_query'] = array(
                                    array(
                                        'key' => $include_cf_posts,
                                        'value' => '1',
                                    ),
                                );
                            } else {
                                if ( !empty($exclude_cf_posts) ) {  // exclude
                                    if ( empty($include_cf_posts) ) { // exclude, but show all posts without meta key
                                        $args['meta_query']['relation'] = 'OR';
                                        $args['meta_query'][] = array(
                                            array(
                                                'key' => $exclude_cf_posts,
                                                'compare' => 'NOT EXISTS'
                                            ),
                                        );
                                    }

                                    $args['meta_query'][] = array(
                                        array(
                                            'key' => $exclude_cf_posts,
                                            'value' => '1',
                                            'compare' => '!=',
                                        ),
                                    );
                                }

                                if ( !empty($include_cf_posts) ) {  // include

                                    if ( isset($args['meta_query']) ) {
                                        $args['meta_query']['relation'] = 'AND';
                                        $args['meta_query'][] = array(
                                            array(
                                                'key' => $include_cf_posts,
                                                'value' => '1',
                                            ),
                                        );
                                    } else {
                                        $args['meta_query'] = array(
                                            array(
                                                'key' => $include_cf_posts,
                                                'value' => '1',
                                            ),
                                        );
                                    }
                                }
                            }
                        }

                        // sort posts
                        $sort = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','sort' );
                        switch ($sort) {
                            case 'oldest_posts':
                                $args['order'] = 'ASC';
                                break;

                            case 'modified_date':
                                $args['orderby'] = 'post_modified';
                                break;

                            case 'alphabetical_order':
                                $args['orderby'] = 'title';
                                $args['order'] = 'ASC';

                                break;

                            case 'popular':
                                $args['meta_key'] = td_page_views::$post_view_counter_key;
                                $args['orderby'] = 'meta_value_num';
                                $args['order'] = 'DESC';

                                break;

                            case 'popular7':
                                $args['meta_query'] = 	array(
                                    'relation' => 'AND',
                                    array(
                                        'key'     => td_page_views::$post_view_counter_7_day_total,
                                        'type'    => 'numeric'
                                    ),
                                    array(
                                        'key'     => td_page_views::$post_view_counter_7_day_last_date,
                                        'value'   => (date('U') - 604800), // current date minus 7 days
                                        'type'    => 'numeric',
                                        'compare' => '>'
                                    )
                                );
                                $args['orderby'] = td_page_views::$post_view_counter_7_day_total;
                                $args['order'] = 'DESC';

                                break;

                            case 'review_high':
                                $args['meta_key'] = 'td_review_key';
                                $args['orderby'] = 'meta_value_num';
                                $args['order'] = 'DESC';

                                break;

                            case 'comment_count':
                                $args['orderby'] = 'comment_count';
                                $args['order'] = 'DESC';

                                break;
                        }

	                    // locked content
	                    $locked_only = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','locked_only' );
	                    if ( defined('TD_SUBSCRIPTION') && !empty( $locked_only ) ) {
		                    $args['meta_key'] = 'tds_lock_content';
	                    }

                        // get the cat wp_query
                        $wp_query_content = new WP_Query( $args );

                        $tdb_state_category->set_wp_query( $wp_query_content );
                    break;

                    case 'author':

                        $template_id = '';
                        $tem_content = '';

                        if ( tdc_state::is_live_editor_ajax() ) {
                            $tem_content = stripcslashes( $_POST['shortcode'] );
                        } else {

                            // read the template
                            $tdb_author_template = td_options::get( 'tdb_author_template' );
                            if ( td_global::is_tdb_template( $tdb_author_template ) ) {
                                $template_id = td_global::tdb_get_template_id( $tdb_author_template );
                            }

                            // load the tdb template
                            $wp_query_template = new WP_Query( array(
                                    'p' => $template_id,
                                    'post_type' => 'tdb_templates',
                                )
                            );

                            if ( !empty( $wp_query_template ) && $wp_query_template->have_posts() ) {
                                $tem_content = $wp_query_template->post->post_content;
                            }
                        }

                        $args = array(
                            'author' => $tdbLoadDataFromId,
                            'posts_per_page' => tdb_util::get_shortcode_att($tem_content, 'tdb_loop', 'limit'),
                            'offset' => tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','offset' ),
                        );

                        // exclude or include certain posts or pages from your posts loop
                        $posts_not_in = self::parse_shortcode_att( tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','post_ids' ), 'post__not_in' );
                        $posts_in     = self::parse_shortcode_att( tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','post_ids' ), 'post__in' );

                        if ( !empty($posts_in) && is_array($posts_in) ) {
                            $args['post__in'] = $posts_in;
                            $args['orderby'] = 'post__in';
                        }

                        if ( !empty($posts_not_in) && is_array($posts_not_in) ) {
                            $args['post__not_in'] = $posts_not_in;
                        }

                        // get post types from att
                        $installed_post_types = tdb_util::get_shortcode_att($tem_content, 'tdb_loop', 'installed_post_types');
                        if (!empty($installed_post_types)) {
                            $array_selected_post_types = array();
                            $expl_installed_post_types = explode(',', $installed_post_types);

                            foreach ($expl_installed_post_types as $val_this_post_type) {
                                if (trim($val_this_post_type) != '') {
                                    $array_selected_post_types[] = trim($val_this_post_type);
                                }
                            }

                            $args['post_type'] = $array_selected_post_types;//$installed_post_types;
                        }


                        // include/exclude posts with CF
                        $include_cf_posts = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop', 'include_cf_posts' );
                        $exclude_cf_posts = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop', 'exclude_cf_posts' );

                        if ( !empty($include_cf_posts) || !empty($exclude_cf_posts) ) {

                            $include_cf_posts = trim($include_cf_posts);
                            $exclude_cf_posts = trim($exclude_cf_posts);

                            // include or the same CF is filled
                            if ( $include_cf_posts === $exclude_cf_posts || ( !empty($include_cf_posts) && empty($exclude_cf_posts) )  ) {
                                $args['meta_query'] = array(
                                    array(
                                        'key' => $include_cf_posts,
                                        'value' => '1',
                                    ),
                                );
                            } else {
                                if ( !empty($exclude_cf_posts) ) {  // exclude
                                    if ( empty($include_cf_posts) ) { // exclude, but show all posts without meta key
                                        $args['meta_query']['relation'] = 'OR';
                                        $args['meta_query'][] = array(
                                            array(
                                                'key' => $exclude_cf_posts,
                                                'compare' => 'NOT EXISTS'
                                            ),
                                        );
                                    }

                                    $args['meta_query'][] = array(
                                        array(
                                            'key' => $exclude_cf_posts,
                                            'value' => '1',
                                            'compare' => '!=',
                                        ),
                                    );
                                }

                                if ( !empty($include_cf_posts) ) {  // include

                                    if ( isset($args['meta_query']) ) {
                                        $args['meta_query']['relation'] = 'AND';
                                        $args['meta_query'][] = array(
                                            array(
                                                'key' => $include_cf_posts,
                                                'value' => '1',
                                            ),
                                        );
                                    } else {
                                        $args['meta_query'] = array(
                                            array(
                                                'key' => $include_cf_posts,
                                                'value' => '1',
                                            ),
                                        );
                                    }
                                }
                            }
                        }

                        // sort posts
                        $sort = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','sort' );
                        switch ($sort) {
                            case 'oldest_posts':
                                $args['order'] = 'ASC';
                                break;

                            case 'modified_date':
                                $args['orderby'] = 'post_modified';
                                break;

                            case 'alphabetical_order':
                                $args['orderby'] = 'title';
                                $args['order'] = 'ASC';
                                break;

                            case 'popular':
                                $args['meta_key'] = td_page_views::$post_view_counter_key;
                                $args['orderby'] = 'meta_value_num';
                                $args['order'] = 'DESC';
                                break;

                            case 'popular7':
                                $args['meta_query'] = 	array(
                                    'relation' => 'AND',
                                    array(
                                        'key'     => td_page_views::$post_view_counter_7_day_total,
                                        'type'    => 'numeric'
                                    ),
                                    array(
                                        'key'     => td_page_views::$post_view_counter_7_day_last_date,
                                        'value'   => (date('U') - 604800), // current date minus 7 days
                                        'type'    => 'numeric',
                                        'compare' => '>'
                                    )
                                );
                                $args['orderby'] = td_page_views::$post_view_counter_7_day_total;
                                $args['order'] = 'DESC';
                                break;

                            case 'review_high':
                                $args['meta_key'] = 'td_review_key';
                                $args['orderby'] = 'meta_value_num';
                                $args['order'] = 'DESC';
                                break;

                            case 'comment_count':
                                $args['orderby'] = 'comment_count';
                                $args['order'] = 'DESC';
                                break;
                        }

	                    // locked content
	                    $locked_only = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','locked_only' );
	                    if ( defined('TD_SUBSCRIPTION') && !empty( $locked_only ) ) {
		                    $args['meta_key'] = 'tds_lock_content';
	                    }

                        // get the author wp_query
                        $wp_query_content = new WP_Query( $args );

                        $tdb_state_author->set_wp_query($wp_query_content);
                    break;

                    case 'search':

                        /**
                         *  the search query is made based on query strings not an id
                         *  @todo this may need a different implementation where we can pass multiple query args or the paged arg
                         */

                        $template_id = '';
                        $tem_content = '';

                        if ( tdc_state::is_live_editor_ajax() ) {
                            $tem_content = stripcslashes( $_POST['shortcode'] );
                        } else {

                            // read the template
                            $tdb_search_template = td_options::get( 'tdb_search_template' );
                            if ( td_global::is_tdb_template( $tdb_search_template ) ) {
                                $template_id = td_global::tdb_get_template_id( $tdb_search_template );
                            }

                            // load the tdb template
                            $wp_query_template = new WP_Query( array(
                                    'p' => $template_id,
                                    'post_type' => 'tdb_templates',
                                )
                            );

                            if ( !empty( $wp_query_template ) && $wp_query_template->have_posts() ) {
                                $tem_content = $wp_query_template->post->post_content;
                            }
                        }

                        $args = array(
                            's' => $tdbLoadDataFromId,
                            'posts_per_page' => tdb_util::get_shortcode_att( $tem_content, 'tdb_loop', 'limit' ),
                            'offset' => tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','offset' ),
                        );

                        // get the content post type
                        $tdbLoadDataPostType = tdb_util::get_get_val('tdbLoadDataPostType');
                        if ( $tdbLoadDataPostType ) {

                            $tdb_load_data_post_types = explode( ',', $tdbLoadDataPostType );
                            $args_post_types = [];
                            foreach ( $tdb_load_data_post_types as $load_data_post_type ) {

                                if ( post_type_exists($load_data_post_type) ) {
                                    $args_post_types[] = $load_data_post_type;
                                }

                            }

                            if ($args_post_types) {
                                $args['post_type'] = $args_post_types;
                            }

                        }

                        // exclude or include certain posts or pages from your posts loop
                        $posts_not_in = self::parse_shortcode_att( tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','post_ids' ), 'post__not_in' );
                        $posts_in     = self::parse_shortcode_att( tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','post_ids' ), 'post__in' );

                        if ( !empty($posts_in) && is_array($posts_in) ) {
                            $args['post__in'] = $posts_in;
                            $args['orderby'] = 'post__in';
                        }

                        if ( !empty($posts_not_in) && is_array($posts_not_in) ) {
                            $args['post__not_in'] = $posts_not_in;
                        }

                        // get post types from att
                        $installed_post_types = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop', 'installed_post_types' );
                        if ( !empty($installed_post_types) ) {
                            $array_selected_post_types = array();
                            $expl_installed_post_types = explode(',', $installed_post_types );

                            foreach ( $expl_installed_post_types as $val_this_post_type ) {
                                if ( trim( $val_this_post_type ) != '' ) {
                                    $array_selected_post_types[] = trim($val_this_post_type);
                                }
                            }

                            $args['post_type'] = $array_selected_post_types; // $installed_post_types;
                        }


                        // include/exclude posts with CF
                        $include_cf_posts = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop', 'include_cf_posts' );
                        $exclude_cf_posts = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop', 'exclude_cf_posts' );

                        if ( !empty($include_cf_posts) || !empty($exclude_cf_posts) ) {

                            $include_cf_posts = trim($include_cf_posts);
                            $exclude_cf_posts = trim($exclude_cf_posts);

                            // include or the same CF is filled
                            if ( $include_cf_posts === $exclude_cf_posts || ( !empty($include_cf_posts) && empty($exclude_cf_posts) )  ) {
                                $args['meta_query'] = array(
                                    array(
                                        'key' => $include_cf_posts,
                                        'value' => '1',
                                    ),
                                );
                            } else {
                                if ( !empty($exclude_cf_posts) ) {  // exclude
                                    if ( empty($include_cf_posts) ) { // exclude, but show all posts without meta key
                                        $args['meta_query']['relation'] = 'OR';
                                        $args['meta_query'][] = array(
                                            array(
                                                'key' => $exclude_cf_posts,
                                                'compare' => 'NOT EXISTS'
                                            ),
                                        );
                                    }

                                    $args['meta_query'][] = array(
                                        array(
                                            'key' => $exclude_cf_posts,
                                            'value' => '1',
                                            'compare' => '!=',
                                        ),
                                    );
                                }

                                if ( !empty($include_cf_posts) ) {  // include

                                    if ( isset($args['meta_query']) ) {
                                        $args['meta_query']['relation'] = 'AND';
                                        $args['meta_query'][] = array(
                                            array(
                                                'key' => $include_cf_posts,
                                                'value' => '1',
                                            ),
                                        );
                                    } else {
                                        $args['meta_query'] = array(
                                            array(
                                                'key' => $include_cf_posts,
                                                'value' => '1',
                                            ),
                                        );
                                    }
                                }
                            }
                        }

                        // sort posts
                        $sort = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','sort' );
                        switch ($sort) {
                            case 'oldest_posts':
                                $args['order'] = 'ASC';
                                break;

                            case 'modified_date':
                                $args['orderby'] = 'post_modified';
                                break;

                            case 'alphabetical_order':
                                $args['orderby'] = 'title';
                                $args['order'] = 'ASC';

                                break;

                            case 'popular':
                                $args['meta_key'] = td_page_views::$post_view_counter_key;
                                $args['orderby'] = 'meta_value_num';
                                $args['order'] = 'DESC';

                                break;

                            case 'popular7':
                                $args['meta_query'] = 	array(
                                    'relation' => 'AND',
                                    array(
                                        'key'     => td_page_views::$post_view_counter_7_day_total,
                                        'type'    => 'numeric'
                                    ),
                                    array(
                                        'key'     => td_page_views::$post_view_counter_7_day_last_date,
                                        'value'   => (date('U') - 604800), // current date minus 7 days
                                        'type'    => 'numeric',
                                        'compare' => '>'
                                    )
                                );
                                $args['orderby'] = td_page_views::$post_view_counter_7_day_total;
                                $args['order'] = 'DESC';

                                break;

                            case 'review_high':
                                $args['meta_key'] = 'td_review_key';
                                $args['orderby'] = 'meta_value_num';
                                $args['order'] = 'DESC';

                                break;

                            case 'comment_count':
                                $args['orderby'] = 'comment_count';
                                $args['order'] = 'DESC';

                                break;
                        }

	                    // locked content
	                    $locked_only = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','locked_only' );
	                    if ( defined('TD_SUBSCRIPTION') && !empty($locked_only) ) {
		                    $args['meta_key'] = 'tds_lock_content';
	                    }

                        // get the search wp_query
                        $wp_query_content = new WP_Query($args);

                        $tdb_state_search->set_wp_query($wp_query_content);

                    break;

                    case 'date':

                        /**
                         * the date query may need all year/month/day args while through the "$tdbLoadDataFromId" var we can pass just an id
                         * @todo this needs a different implementation where we can pass multiple query args
                         *  we may also need this for paginated(paged) pages, when loading content from page no 2,3,4...
                         */

                        $template_id = '';
                        $tem_content = '';

                        if ( tdc_state::is_live_editor_ajax() ) {
                            $tem_content = stripcslashes( $_POST['shortcode'] );
                        } else {

                            // read the template
                            $tdb_date_template = td_options::get( 'tdb_date_template' );
                            if ( td_global::is_tdb_template( $tdb_date_template ) ) {
                                $template_id = td_global::tdb_get_template_id( $tdb_date_template );
                            }

                            // load the tdb template
                            $wp_query_template = new WP_Query( array(
                                    'p' => $template_id,
                                    'post_type' => 'tdb_templates',
                                )
                            );

                            if ( !empty( $wp_query_template ) && $wp_query_template->have_posts() ) {
                                $tem_content = $wp_query_template->post->post_content;
                            }
                        }

                        $args = array(
                            'year' => $tdbLoadDataFromId,
                            'posts_per_page' => tdb_util::get_shortcode_att($tem_content, 'tdb_loop', 'limit'),
                            'offset' => tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','offset' ),
                        );

                        // exclude or include certain posts or pages from your posts loop
                        $posts_not_in = self::parse_shortcode_att( tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','post_ids' ), 'post__not_in' );
                        $posts_in     = self::parse_shortcode_att( tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','post_ids' ), 'post__in' );

                        if ( !empty($posts_in) && is_array($posts_in) ) {
                            $args['post__in'] = $posts_in;
                            $args['orderby'] = 'post__in';
                        }

                        if ( !empty($posts_not_in) && is_array($posts_not_in) ) {
                            $args['post__not_in'] = $posts_not_in;
                        }

                        // get post types from att
                        $installed_post_types = tdb_util::get_shortcode_att($tem_content, 'tdb_loop', 'installed_post_types');
                        if (!empty($installed_post_types)) {
                            $array_selected_post_types = array();
                            $expl_installed_post_types = explode(',', $installed_post_types);

                            foreach ($expl_installed_post_types as $val_this_post_type) {
                                if (trim($val_this_post_type) != '') {
                                    $array_selected_post_types[] = trim($val_this_post_type);
                                }
                            }

                            $args['post_type'] = $array_selected_post_types;//$installed_post_types;
                        }


                        // include/exclude posts with CF
                        $include_cf_posts = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop', 'include_cf_posts' );
                        $exclude_cf_posts = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop', 'exclude_cf_posts' );

                        if ( !empty($include_cf_posts) || !empty($exclude_cf_posts) ) {

                            $include_cf_posts = trim($include_cf_posts);
                            $exclude_cf_posts = trim($exclude_cf_posts);

                            // include or the same CF is filled
                            if ( $include_cf_posts === $exclude_cf_posts || ( !empty($include_cf_posts) && empty($exclude_cf_posts) )  ) {
                                $args['meta_query'] = array(
                                    array(
                                        'key' => $include_cf_posts,
                                        'value' => '1',
                                    ),
                                );
                            } else {
                                if ( !empty($exclude_cf_posts) ) {  // exclude
                                    if ( empty($include_cf_posts) ) { // exclude, but show all posts without meta key
                                        $args['meta_query']['relation'] = 'OR';
                                        $args['meta_query'][] = array(
                                            array(
                                                'key' => $exclude_cf_posts,
                                                'compare' => 'NOT EXISTS'
                                            ),
                                        );
                                    }
                                    $args['meta_query'][] = array(
                                        array(
                                            'key' => $exclude_cf_posts,
                                            'value' => '1',
                                            'compare' => '!=',
                                        ),
                                    );
                                }

                                if ( !empty($include_cf_posts) ) {  // include

                                    if ( isset($args['meta_query']) ) {
                                        $args['meta_query']['relation'] = 'AND';
                                        $args['meta_query'][] = array(
                                            array(
                                                'key' => $include_cf_posts,
                                                'value' => '1',
                                            ),
                                        );
                                    } else {
                                        $args['meta_query'] = array(
                                            array(
                                                'key' => $include_cf_posts,
                                                'value' => '1',
                                            ),
                                        );
                                    }
                                }
                            }
                        }

                        // sort posts
                        $sort = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','sort' );
                        switch ($sort) {
                            case 'oldest_posts':
                                $args['order'] = 'ASC';
                                break;

                            case 'modified_date':
                                $args['orderby'] = 'post_modified';
                                break;

                            case 'alphabetical_order':
                                $args['orderby'] = 'title';
                                $args['order'] = 'ASC';

                                break;

                            case 'popular':
                                $args['meta_key'] = td_page_views::$post_view_counter_key;
                                $args['orderby'] = 'meta_value_num';
                                $args['order'] = 'DESC';

                                break;

                            case 'popular7':
                                $args['meta_query'] = 	array(
                                    'relation' => 'AND',
                                    array(
                                        'key'     => td_page_views::$post_view_counter_7_day_total,
                                        'type'    => 'numeric'
                                    ),
                                    array(
                                        'key'     => td_page_views::$post_view_counter_7_day_last_date,
                                        'value'   => (date('U') - 604800), // current date minus 7 days
                                        'type'    => 'numeric',
                                        'compare' => '>'
                                    )
                                );
                                $args['orderby'] = td_page_views::$post_view_counter_7_day_total;
                                $args['order'] = 'DESC';

                                break;

                            case 'review_high':
                                $args['meta_key'] = 'td_review_key';
                                $args['orderby'] = 'meta_value_num';
                                $args['order'] = 'DESC';

                                break;

                            case 'comment_count':
                                $args['orderby'] = 'comment_count';
                                $args['order'] = 'DESC';

                                break;
                        }

	                    // locked content
	                    $locked_only = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','locked_only' );
	                    if ( defined('TD_SUBSCRIPTION') && !empty( $locked_only ) ) {
		                    $args['meta_key'] = 'tds_lock_content';
	                    }

                        // get the date wp_query
                        $wp_query_content = new WP_Query( $args );

                        $tdb_state_date->set_wp_query($wp_query_content);
                    break;

                    case 'tag':

                        $template_id = '';
                        $tem_content = '';

                        if ( tdc_state::is_live_editor_ajax() ) {
                            $tem_content = stripcslashes( $_POST['shortcode'] );
                        } else {

                            // read the template
                            $tdb_tag_template = td_options::get( 'tdb_tag_template' );
                            if ( td_global::is_tdb_template( $tdb_tag_template ) ) {
                                $template_id = td_global::tdb_get_template_id( $tdb_tag_template );
                            }

                            // load the tdb template
                            $wp_query_template = new WP_Query( array(
                                    'p' => $template_id,
                                    'post_type' => 'tdb_templates',
                                )
                            );

                            if ( !empty( $wp_query_template ) && $wp_query_template->have_posts() ) {
                                $tem_content = $wp_query_template->post->post_content;
                            }
                        }

                        $tag = get_tag( $tdbLoadDataFromId, OBJECT );

                        $args = array(
                            'tag' => $tag->slug,
                            'posts_per_page' => tdb_util::get_shortcode_att($tem_content, 'tdb_loop', 'limit'),
                            'offset' => tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','offset' ),
                        );

                        // exclude or include certain posts or pages from your posts loop
                        $posts_not_in = self::parse_shortcode_att( tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','post_ids' ), 'post__not_in' );
                        $posts_in     = self::parse_shortcode_att( tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','post_ids' ), 'post__in' );

                        if ( !empty($posts_in) && is_array($posts_in) ) {
                            $args['post__in'] = $posts_in;
                            $args['orderby'] = 'post__in';
                        }

                        if ( !empty($posts_not_in) && is_array($posts_not_in) ) {
                            $args['post__not_in'] = $posts_not_in;
                        }

                        // get post types from att
                        $installed_post_types = tdb_util::get_shortcode_att($tem_content, 'tdb_loop', 'installed_post_types');
                        if (!empty($installed_post_types)) {
                            $array_selected_post_types = array();
                            $expl_installed_post_types = explode(',', $installed_post_types);

                            foreach ($expl_installed_post_types as $val_this_post_type) {
                                if (trim($val_this_post_type) != '') {
                                    $array_selected_post_types[] = trim($val_this_post_type);
                                }
                            }

                            $args['post_type'] = $array_selected_post_types;//$installed_post_types;
                        }


                        // include/exclude posts with CF
                        $include_cf_posts = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop', 'include_cf_posts' );
                        $exclude_cf_posts = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop', 'exclude_cf_posts' );

                        if ( !empty($include_cf_posts) || !empty($exclude_cf_posts) ) {

                            $include_cf_posts = trim($include_cf_posts);
                            $exclude_cf_posts = trim($exclude_cf_posts);

                            // include or the same CF is filled
                            if ( $include_cf_posts === $exclude_cf_posts || ( !empty($include_cf_posts) && empty($exclude_cf_posts) )  ) {
                                $args['meta_query'] = array(
                                    array(
                                        'key' => $include_cf_posts,
                                        'value' => '1',
                                    ),
                                );
                            } else {
                                if ( !empty($exclude_cf_posts) ) {  // exclude
                                    if ( empty($include_cf_posts) ) { // exclude, but show all posts without meta key
                                        $args['meta_query']['relation'] = 'OR';
                                        $args['meta_query'][] = array(
                                            array(
                                                'key' => $exclude_cf_posts,
                                                'compare' => 'NOT EXISTS'
                                            ),
                                        );
                                    }

                                    $args['meta_query'][] = array(
                                        array(
                                            'key' => $exclude_cf_posts,
                                            'value' => '1',
                                            'compare' => '!=',
                                        ),
                                    );
                                }

                                if ( !empty($include_cf_posts) ) {  // include

                                    if ( isset($args['meta_query']) ) {
                                        $args['meta_query']['relation'] = 'AND';
                                        $args['meta_query'][] = array(
                                            array(
                                                'key' => $include_cf_posts,
                                                'value' => '1',
                                            ),
                                        );
                                    } else {
                                        $args['meta_query'] = array(
                                            array(
                                                'key' => $include_cf_posts,
                                                'value' => '1',
                                            ),
                                        );
                                    }
                                }
                            }
                        }

                        // sort posts
                        $sort = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','sort' );
                        switch ($sort) {
                            case 'oldest_posts':
                                $args['order'] = 'ASC';
                                break;

                            case 'modified_date':
                                $args['orderby'] = 'post_modified';
                                break;

                            case 'alphabetical_order':
                                $args['orderby'] = 'title';
                                $args['order'] = 'ASC';

                                break;

                            case 'popular':
                                $args['meta_key'] = td_page_views::$post_view_counter_key;
                                $args['orderby'] = 'meta_value_num';
                                $args['order'] = 'DESC';

                                break;

                            case 'popular7':
                                $args['meta_query'] = 	array(
                                    'relation' => 'AND',
                                    array(
                                        'key'     => td_page_views::$post_view_counter_7_day_total,
                                        'type'    => 'numeric'
                                    ),
                                    array(
                                        'key'     => td_page_views::$post_view_counter_7_day_last_date,
                                        'value'   => (date('U') - 604800), // current date minus 7 days
                                        'type'    => 'numeric',
                                        'compare' => '>'
                                    )
                                );
                                $args['orderby'] = td_page_views::$post_view_counter_7_day_total;
                                $args['order'] = 'DESC';

                                break;

                            case 'review_high':
                                $args['meta_key'] = 'td_review_key';
                                $args['orderby'] = 'meta_value_num';
                                $args['order'] = 'DESC';

                                break;

                            case 'comment_count':
                                $args['orderby'] = 'comment_count';
                                $args['order'] = 'DESC';

                                break;
                        }

	                    // locked content
	                    $locked_only = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','locked_only' );
	                    if ( defined('TD_SUBSCRIPTION') && !empty( $locked_only ) ) {
		                    $args['meta_key'] = 'tds_lock_content';
	                    }

                        // get the tag wp_query
                        $wp_query_content = new WP_Query( $args );

                        $tdb_state_tag->set_wp_query($wp_query_content);
                    break;
                }
            }

            // get the page id
            $post_id = tdb_util::get_get_val('post_id');

            if ( $tdbTemplateType === 'page' && $post_id !== false ) {

                $tem_content = '';

                $tdb_state_single_page->set_page_obj( get_post($post_id) );

                if ( tdc_state::is_live_editor_ajax() ) {
                    $tem_content = stripcslashes($_POST['shortcode']);
                } else {

                    // load the tdb template
                    $wp_query_template = new WP_Query( array(
                            'p' => $post_id,
                            'post_type' => 'page',
                        )
                    );

                    // do not set the template content if we don't find the template
                    if ( !empty( $wp_query_template ) && $wp_query_template->have_posts() ) {
                        $tem_content = $wp_query_template->post->post_content;
                    }
                }

                $args = array(
                    'post_type' => 'post',
                    'ignore_sticky_posts' => true,
                    'post_status' => 'publish',
                    'posts_per_page' => tdb_util::get_shortcode_att($tem_content, 'tdb_loop', 'limit'),
                    'offset' => tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','offset' ),
                    'paged' => 1,
                );

                // exclude or include certain posts or pages from your posts loop
                $posts_not_in = self::parse_shortcode_att( tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','post_ids' ), 'post__not_in' );
                $posts_in     = self::parse_shortcode_att( tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','post_ids' ), 'post__in' );

                if ( !empty($posts_in) && is_array($posts_in) ) {
                    $args['post__in'] = $posts_in;
                    $args['orderby'] = 'post__in';
                }

                if ( !empty($posts_not_in) && is_array($posts_not_in) ) {
                    $args['post__not_in'] = $posts_not_in;
                }

                // include/exclude posts with CF
                $include_cf_posts = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop', 'include_cf_posts' );
                $exclude_cf_posts = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop', 'exclude_cf_posts' );

                if ( !empty($include_cf_posts) || !empty($exclude_cf_posts) ) {

                    $include_cf_posts = trim($include_cf_posts);
                    $exclude_cf_posts = trim($exclude_cf_posts);

                    // include or the same CF is filled
                    if ( $include_cf_posts === $exclude_cf_posts || ( !empty($include_cf_posts) && empty($exclude_cf_posts) )  ) {
                        $args['meta_query'] = array(
                            array(
                                'key' => $include_cf_posts,
                                'value' => '1',
                            ),
                        );
                    } else {
                        if ( !empty($exclude_cf_posts) ) {  // exclude
                            if ( empty($include_cf_posts) ) { // exclude, but show all posts without meta key
                                $args['meta_query']['relation'] = 'OR';
                                $args['meta_query'][] = array(
                                    array(
                                        'key' => $exclude_cf_posts,
                                        'compare' => 'NOT EXISTS'
                                    ),
                                );
                            }
                            $args['meta_query'][] = array(
                                array(
                                    'key' => $exclude_cf_posts,
                                    'value' => '1',
                                    'compare' => '!=',
                                ),
                            );
                        }

                        if ( !empty($include_cf_posts) ) {  // include

                            if ( isset($args['meta_query']) ) {
                                $args['meta_query']['relation'] = 'AND';
                                $args['meta_query'][] = array(
                                    array(
                                        'key' => $include_cf_posts,
                                        'value' => '1',
                                    ),
                                );
                            } else {
                                $args['meta_query'] = array(
                                    array(
                                        'key' => $include_cf_posts,
                                        'value' => '1',
                                    ),
                                );
                            }
                        }
                    }
                }

                // sort posts
                $sort = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','sort' );
                switch ($sort) {
                    case 'oldest_posts':
                        $args['order'] = 'ASC';
                        break;

                    case 'modified_date':
                        $args['orderby'] = 'post_modified';
                        break;

                    case 'alphabetical_order':
                        $args['orderby'] = 'title';
                        $args['order'] = 'ASC';

                        break;

                    case 'popular':
                        $args['meta_key'] = td_page_views::$post_view_counter_key;
                        $args['orderby'] = 'meta_value_num';
                        $args['order'] = 'DESC';

                        break;

                    case 'popular7':
                        $args['meta_query'] = 	array(
                            'relation' => 'AND',
                            array(
                                'key'     => td_page_views::$post_view_counter_7_day_total,
                                'type'    => 'numeric'
                            ),
                            array(
                                'key'     => td_page_views::$post_view_counter_7_day_last_date,
                                'value'   => (date('U') - 604800), // current date minus 7 days
                                'type'    => 'numeric',
                                'compare' => '>'
                            )
                        );
                        $args['orderby'] = td_page_views::$post_view_counter_7_day_total;
                        $args['order'] = 'DESC';

                        break;

                    case 'review_high':
                        $args['meta_key'] = 'td_review_key';
                        $args['orderby'] = 'meta_value_num';
                        $args['order'] = 'DESC';

                        break;

                    case 'comment_count':
                        $args['orderby'] = 'comment_count';
                        $args['order'] = 'DESC';

                        break;
                }

	            // locked content
	            $locked_only = tdb_util::get_shortcode_att( $tem_content, 'tdb_loop','locked_only' );
	            if ( defined('TD_SUBSCRIPTION') && !empty( $locked_only ) ) {
		            $args['meta_key'] = 'tds_lock_content';
	            }

                $wp_query_content = new WP_Query( $args );

                $tdb_state_single_page->set_wp_query( $wp_query_content );

            }

            // module templates
            if( $tdbTemplateType === 'module' ) {
                global $tdb_module_template_params;

                if( empty($tdb_module_template_params['template_obj']) ) {
                    $template_id = tdb_util::get_get_val('post_id');
        
                    $tdb_module_template_params['template_obj'] = get_post( $template_id );
                    $tdb_module_template_params['template_class'] = '';
                    $tdb_module_template_params['post_obj'] = null;
                    $tdb_module_template_params['shortcodes'] = array();

                    // If the tdbLoadDataFromId url param is present, get the post from that ID, otherwise try to get the latest published post
                    if( $tdbLoadDataFromId !== false ) {
                        $tdb_module_template_params['post_obj'] = get_post( $tdbLoadDataFromId );

                        //var_dump($tdb_module_template_params['post_obj']);
                    } else {
                        // Try to get the latest published post
                        $last_published_post = get_posts(array(
                            'numberposts' => 1
                        ));

                        // If the post was found, store its data
                        if( !empty( $last_published_post ) ) {
                            $tdb_module_template_params['post_obj'] = $last_published_post[0];
                        }
                    }
                }
            }

        }

    }



    /**
     * Here we build the state for the single template when is accessed on the front end,
     *  - we have to do it on this hook because we want to use the wordpress wp_query from it's main query.
     *  - Why we use two hooks to store the state: when td-composer is editing a single template, the main query is the template's query
     *      so we have to make a new query, unlike here where we already have the global wp_query available
     *
     */
    static function on_template_redirect_load_state() {

        global $wp_query, $tdb_state_single_page, $tdb_state_single, $tdb_state_category, $tdb_state_author, $tdb_state_search, $tdb_state_date, $tdb_state_tag, $tdb_state_attachment;

        $cpts = td_util::get_cpts();
        $td_cpt = td_util::get_option('td_cpt');

        // we are on the front end on a custom post type
        foreach ( $cpts as $cpt ) {
            // removed global check to allow individual cloud template on cpt
            if ( is_singular( array( $cpt->name ) ) ) {
	            $tdb_state_single->set_wp_query( $wp_query );
	            break;
            }
        }

        // we are on the front end on a post
        if ( is_singular( array( 'post' ) ) ) {
            $tdb_state_single->set_wp_query($wp_query);
        }

        // we are on the front end on a page
        if ( is_singular( array( 'page' ) ) ) {
            $tdb_state_single_page->set_page_obj(get_queried_object());
            $tdb_state_single_page->set_wp_query($wp_query);
        }

        // we are on the front end on an attachment page
        if ( is_singular( array( 'attachment' ) ) ) {
            $tdb_state_attachment->set_wp_query($wp_query);
        }

        // if we are on the front end on a 404 page load the page state
        if ( is_404() ) {
            $tdb_state_single_page->set_wp_query($wp_query);
        }

        // we are on the front end on a category page
        if ( is_category() ) {
        	$tdb_state_category->set_wp_query($wp_query);
        }

        // we are on the front end on a taxonomy page
        if ( is_tax() ) {
        	$tdb_state_category->set_tax();
            $tdb_state_category->set_wp_query($wp_query);
        }

        // we are on the front end on a author page
        if ( is_author() ) {
            $tdb_state_author->set_wp_query($wp_query);
        }

        // we are on the front end on a search page
        if ( is_search() ) {
            $tdb_state_search->set_wp_query($wp_query);
        }

        // we are on the front end on a date archive page
        if ( is_date() ) {
            $tdb_state_date->set_wp_query($wp_query);
        }

        // we are on the front end on a tag page
        if ( is_tag() ) {
            $tdb_state_tag->set_wp_query($wp_query);
        }

	    // we are viewing a single post template
	    if ( is_singular( array( 'tdb_templates' ) ) && !td_util::is_mobile_theme() ) {
		    global $post;

			// get template type
		    $tdb_template_type = get_post_meta( $post->ID, 'tdb_template_type', true );
			if ( $tdb_template_type === 'module' ) {

                global $tdb_module_template_params;

                if( empty($tdb_module_template_params) ) {
                    $td_preview_post_id = tdb_util::get_get_val('td_preview_post_id');

                    $tdb_module_template_params['template_obj'] = $post;
                    $tdb_module_template_params['template_class'] = '';
                    $tdb_module_template_params['post_obj'] = null;
                    $tdb_module_template_params['shortcodes'] = array();

                    // If the td_preview_post_id url param is present, get the post from that ID, otherwise try to get the latest published post
                    if( $td_preview_post_id !== false ) {
                        $tdb_module_template_params['post_obj'] = get_post( $td_preview_post_id );
                    } else {
                        // Try to get the latest published post
                        $last_published_post = get_posts(array(
                            'numberposts' => 1
                        ));

                        // If the post was found, store its data
                        if( !empty( $last_published_post ) ) {
                            $tdb_module_template_params['post_obj'] = $last_published_post[0];
                        }
                    }
                }

			}

	    }

        // we are on the front end on a post type archive page
        if ( is_post_type_archive() && !is_search() ) {
            $tdb_state_category->set_cpt_post_type_archive();
            $tdb_state_category->set_wp_query($wp_query);
        }

    }

    /**
     *
     *  IN: the shortcode att value and the wp query param name
     *  OUT: the wp query param value
     *
     * @param $shortcode_att - the shortcode attribute value
     * @param $wp_query_param - the wp_query param type
     *
     * @return mixed - a wp query compatible parameter value
     */
    static function parse_shortcode_att( $shortcode_att, $wp_query_param ) {

        switch ($wp_query_param) {
            case 'post__not_in':

                $post_ids = $shortcode_att;
                $posts_not_in = array();

                if ( !empty($post_ids) ) {
                    // split posts ids string
                    $post_ids_array = explode(',', $post_ids);

                    // split ids
                    foreach ($post_ids_array as $post_id) {
                        $post_id = trim($post_id);

                        // check if the ID is actually a number
                        if (is_numeric($post_id)) {

                            if (intval($post_id) < 0) {
                                $posts_not_in[] = str_replace('-', '', $post_id);
                            }
                        }
                    }
                }

                return $posts_not_in;

                break;
            case 'post__in':

                $post_ids = $shortcode_att;
                $posts_in = array();

                if ( !empty($post_ids) ) {
                    // split posts ids string
                    $post_ids_array = explode(',', $post_ids);

                    // split ids
                    foreach ($post_ids_array as $post_id) {
                        $post_id = trim($post_id);

                        // check if the ID is actually a number
                        if (is_numeric($post_id)) {
                            if (intval($post_id) > 0) {
                                $posts_in[] = $post_id;
                            }
                        }
                    }
                }

                return $posts_in;

                break;
        }

        return '';
    }

}
