<?php
/**
 * Created by PhpStorm.
 * User: tagdiv
 * Date: 19.08.2016
 * Time: 13:54
 */

class td_block_list_menu extends td_block {

	private static $menu_display;


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

                /* @style_general_list_menu */
                .td_block_list_menu ul {
                  flex-wrap: wrap;
                  margin-left: 12px;
                }
                .td_block_list_menu ul li {
					margin-left: 0;
				}
                .td_block_list_menu ul li a {
					display: flex;
                  	margin-left: 0;
                }
                .td_block_list_menu .td-blm-menu-item-txt {
					display: flex;
					align-items: center;
					flex-grow: 1;
				}
                .td_block_list_menu .sub-menu {
                  padding-left: 22px;
                }
                .td_block_list_menu .sub-menu li {
                  font-size: 13px;
                }
                .td_block_list_menu li.current-menu-item > a,
				.td_block_list_menu li.current-menu-ancestor > a,
				.td_block_list_menu li.current-category-ancestor > a,
				.td_block_list_menu li.current-page-ancestor > a {
				    color: var(--td_theme_color, #4db2ec);
				}
				.td_block_list_menu .td-blm-sub-icon {
					display: flex;
					align-items: center;
					justify-content: center;
					margin-left: .6em;
					padding: 0 .6em;
					transition: transform .2s ease-in-out;
				}
				.td_block_list_menu .td-blm-sub-icon svg {
					display: block;
					width: 1em;
					height: auto;
				}
				.td_block_list_menu .td-blm-sub-icon svg,
				.td_block_list_menu .td-blm-sub-icon svg * {
					fill: currentColor;
				}

				/* @style_specific_list_menu_accordion */
				.td_block_list_menu.td-blm-display-accordion .menu-item-has-children ul {
					display: none;
				}
				.td_block_list_menu.td-blm-display-accordion .menu-item-has-children-open > a > .td-blm-sub-icon {
					transform: rotate(180deg);
				}

				/* @style_specific_list_menu_horizontal */
				.td_block_list_menu.td-blm-display-horizontal ul {
					display: flex;
				}

                

				/* @item_horiz_center */
				body .$unique_block_class ul {
					text-align: center;
					justify-content: center;
				}
                body .$unique_block_class ul li a {
					justify-content: center;
				}
				body .$unique_block_class .td-blm-menu-item-txt {
					flex-grow: unset;
				}
				/* @item_horiz_right */
				body .$unique_block_class ul {
					text-align: right;
					justify-content: flex-end;
				}
                body .$unique_block_class ul li a {
					justify-content: flex-end;
				}
				body .$unique_block_class .td-blm-menu-item-txt {
					flex-grow: unset;
				}
				/* @item_horiz_left */
				body .$unique_block_class ul {
					text-align: left;
					justify-content: flex-start;
				}
                body .$unique_block_class ul li a {
					justify-content: flex-start;
				}
				body .$unique_block_class .td-blm-menu-item-txt {
					flex-grow: 1;
				}
				
				/* @list_padding */
				body .$unique_block_class ul {
					margin: @list_padding;
				}
				/* @item_space_right */
				body .$unique_block_class ul li {
					margin-right: @item_space_right;
				}
				body .$unique_block_class ul li:last-child {
					margin-right: 0;
				}
				/* @item_space_bottom */
				body .$unique_block_class ul li {
					margin-bottom: @item_space_bottom;
				}
				body .$unique_block_class ul li:last-child {
					margin-bottom: 0;
				}

				/* @sub_indent */
				body .$unique_block_class .sub-menu {
					padding-left: @sub_indent;
				}
				/* @sub_padd */
				body .$unique_block_class .sub-menu {
					margin: @sub_padd;
				}
				/* @sub_item_space_right */
				body .$unique_block_class .sub-menu li {
					margin-right: @sub_item_space_right;
				}
				body .$unique_block_class .sub-menu li:last-child {
					margin-right: 0;
				}
				/* @sub_item_space_bottom */
				body .$unique_block_class .sub-menu li {
					margin-bottom: @sub_item_space_bottom;
				}
				body .$unique_block_class .sub-menu li:last-child {
					margin-bottom: 0;
				}
				


                /* @menu_color */
				body .$unique_block_class a,
				body .$unique_block_class .td-blm-sub-icon {
					color: @menu_color;
				}
				/* @menu_hover_color */
				body .$unique_block_class li.current-menu-item > a,
				body .$unique_block_class li.current-menu-ancestor > a,
				body .$unique_block_class li.current-category-ancestor > a,
				body .$unique_block_class li.current-page-ancestor > a,
				body .$unique_block_class a:hover,
				body .$unique_block_class li.current-menu-item > a > .td-blm-sub-icon,
				body .$unique_block_class li.current-menu-ancestor > a > .td-blm-sub-icon,
				body .$unique_block_class li.current-category-ancestor > a > .td-blm-sub-icon,
				body .$unique_block_class li.current-page-ancestor > a > .td-blm-sub-icon,
				body .$unique_block_class a:hover > .td-blm-sub-icon {
					color: @menu_hover_color;
				}
				
				/* @submenu_color */
				body .$unique_block_class .sub-menu a,
				body .$unique_block_class .sub-menu .td-blm-sub-icon {
					color: @submenu_color;
				}
				/* @submenu_hover_color */
				body .$unique_block_class .sub-menu li.current-menu-item > a,
				body .$unique_block_class .sub-menu li.current-menu-ancestor > a,
				body .$unique_block_class .sub-menu li.current-category-ancestor > a,
				body .$unique_block_class .sub-menu li.current-page-ancestor > a,
				body .$unique_block_class .sub-menu a:hover,
				body .$unique_block_class .sub-menu li.current-menu-item > a > .td-blm-sub-icon,
				body .$unique_block_class .sub-menu li.current-menu-ancestor > a > .td-blm-sub-icon,
				body .$unique_block_class .sub-menu li.current-category-ancestor > a > .td-blm-sub-icon,
				body .$unique_block_class .sub-menu li.current-page-ancestor > a > .td-blm-sub-icon,
				body .$unique_block_class .sub-menu a:hover > .td-blm-sub-icon {
					color: @submenu_hover_color;
				}


				/* @sub_ico_size */
				body .$unique_block_class .td-blm-sub-icon {
					font-size: @sub_ico_size;
				}
				/* @sub_sub_ico_size */
				body .$unique_block_class .sub-menu .td-blm-sub-icon {
					font-size: @sub_sub_ico_size;
				}
                /* @sub_ico_color */
				body .$unique_block_class .td-blm-sub-icon {
					color: @sub_ico_color;
				}
				/* @sub_ico_color_h */
				body .$unique_block_class li.current-menu-item > a > .td-blm-sub-icon,
				body .$unique_block_class li.current-menu-ancestor > a > .td-blm-sub-icon,
				body .$unique_block_class li.current-category-ancestor > a > .td-blm-sub-icon,
				body .$unique_block_class li.current-page-ancestor > a > .td-blm-sub-icon,
				body .$unique_block_class a:hover > .td-blm-sub-icon {
					color: @sub_ico_color_h;
				} 
                /* @sub_sub_ico_color */
				body .$unique_block_class .sub-menu .td-blm-sub-icon {
					color: @sub_sub_ico_color;
				}
				/* @sub_sub_ico_color_h */
				body .$unique_block_class .sub-menu li.current-menu-item > a > .td-blm-sub-icon,
				body .$unique_block_class .sub-menu li.current-menu-ancestor > a > .td-blm-sub-icon,
				body .$unique_block_class .sub-menu li.current-category-ancestor > a > .td-blm-sub-icon,
				body .$unique_block_class .sub-menu li.current-page-ancestor > a > .td-blm-sub-icon,
				body .$unique_block_class .sub-menu a:hover > .td-blm-sub-icon {
					color: @sub_sub_ico_color_h;
				}
				


                /* @f_header */
				body .$unique_block_class .td-block-title a,
				body .$unique_block_class .td-block-title span {
					@f_header
				}
				/* @f_list */
				body .$unique_block_class li {
					@f_list
				}
				/* @f_sublist */
				body .$unique_block_class li .sub-menu li {
					@f_sublist
				}
				
			</style>";


        $td_css_res_compiler = new td_css_res_compiler( $raw_css );
        $td_css_res_compiler->load_settings( __CLASS__ . '::cssMedia', $this->get_all_atts() );

        $compiled_css .= $td_css_res_compiler->compile_css();
        return $compiled_css;

    }

    static function cssMedia( $res_ctx ) {

        /*-- GENERAL-- */
        $res_ctx->load_settings_raw( 'style_general_list_menu', 1 );
		
		// load a specific style, depending on menu display
		$res_ctx->load_settings_raw( 'style_specific_list_menu_vertical', 1 );
		$res_ctx->load_settings_raw( 'style_specific_list_menu_accordion', 1 );
		$res_ctx->load_settings_raw( 'style_specific_list_menu_horizontal', 1 );



        /*-- BLOCK HEADER -- */
        // *- fonts -* //
		$res_ctx->load_font_settings( 'f_header' );



        /*-- ALL MENUS -- */
        // *- layout -* //
		// horizontal align
        $item_horiz_align = $res_ctx->get_shortcode_att('item_horiz_align');
        if( $item_horiz_align == 'content-horiz-center' ) {
            $res_ctx->load_settings_raw( 'item_horiz_center', 1 );
        }else if( $item_horiz_align == 'content-horiz-right' ) {
            $res_ctx->load_settings_raw( 'item_horiz_right', 1 );
        } else if( $item_horiz_align == 'content-horiz-left' ) {
            $res_ctx->load_settings_raw( 'item_horiz_left', 1 );
        }  



        /*-- MAIN MENU -- */
        // *- layout -* //
        // list padding
        $padding = $res_ctx->get_shortcode_att('list_padding');
		$padding .= $padding != '' && is_numeric( $padding ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'list_padding', $padding );

        // list items space
        $item_space = $res_ctx->get_shortcode_att('item_space');
		$item_space .= $item_space != '' && is_numeric( $item_space ) ? 'px' : '';
        if( self::$menu_display == 'horizontal' ) {
            $res_ctx->load_settings_raw( 'item_space_right', $item_space );
        } else {
            $res_ctx->load_settings_raw( 'item_space_bottom', $item_space );
        }
		
        // sub menu icon size
		$sub_ico_size = $res_ctx->get_shortcode_att('sub_ico_size');
		$sub_ico_size .= $sub_ico_size != '' && is_numeric( $sub_ico_size ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'sub_ico_size', $sub_ico_size );


        // *- colors -* //
		$res_ctx->load_settings_raw( 'menu_color', $res_ctx->get_shortcode_att('menu_color') );
        $res_ctx->load_settings_raw( 'menu_hover_color', $res_ctx->get_shortcode_att('menu_hover_color') );
		
		$res_ctx->load_settings_raw( 'sub_ico_color', $res_ctx->get_shortcode_att('sub_ico_color') );
        $res_ctx->load_settings_raw( 'sub_ico_color_h', $res_ctx->get_shortcode_att('sub_ico_color_h') );


        // *- fonts -* //
		$res_ctx->load_font_settings( 'f_list' );



        /*-- SUB MENUS -- */
        // *- layout -* //
        // list indent
        $sub_indent = $res_ctx->get_shortcode_att('sub_indent');
		$sub_indent .= $sub_indent != '' && is_numeric( $sub_indent ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'sub_indent', $sub_indent );
		
        // list padding
        $sub_padd = $res_ctx->get_shortcode_att('sub_padd');
		$sub_padd .= $sub_padd != '' && is_numeric( $sub_padd ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'sub_padd', $sub_padd );

        // list items space
        $sub_item_space = $res_ctx->get_shortcode_att('sub_item_space');
		$sub_item_space .= $sub_item_space != '' && is_numeric( $sub_item_space ) ? 'px' : '';
        if( self::$menu_display == 'horizontal' ) {
            $res_ctx->load_settings_raw( 'sub_item_space_right', $sub_item_space );
        } else {
            $res_ctx->load_settings_raw( 'sub_item_space_bottom', $sub_item_space );
        }
		
        // sub menu icon size
		$sub_sub_ico_size = $res_ctx->get_shortcode_att('sub_sub_ico_size');
		$sub_sub_ico_size .= $sub_ico_size != '' && is_numeric( $sub_sub_ico_size ) ? 'px' : '';
		$res_ctx->load_settings_raw( 'sub_sub_ico_size', $sub_sub_ico_size );


        // *- colors -* //
		$res_ctx->load_settings_raw( 'submenu_color', $res_ctx->get_shortcode_att('submenu_color') );
        $res_ctx->load_settings_raw( 'submenu_hover_color', $res_ctx->get_shortcode_att('submenu_hover_color') );
		
		$res_ctx->load_settings_raw( 'sub_sub_ico_color', $res_ctx->get_shortcode_att('sub_sub_ico_color') );
        $res_ctx->load_settings_raw( 'sub_sub_ico_color_h', $res_ctx->get_shortcode_att('sub_sub_ico_color_h') );


        // *- fonts -* //
		$res_ctx->load_font_settings( 'f_sublist' );

    }


	function render($atts, $content = null) {

		self::disable_loop_block_features();

		parent::render($atts); // sets the live atts, $this->atts, $this->block_uid, $this->td_query (it runs the query)

        if ( td_global::get_in_menu() && ( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) ) {

            $buffy = '';

                $buffy .= '<div class="' . $this->get_block_classes() . ' widget" ' . $this->get_block_html_atts() . '>';

                    $buffy .= $this->get_block_css();
                    $buffy .= $this->get_block_js();

                    $buffy .= '<div id=' . $this->block_uid . ' class="td_block_inner td-fix-index">';
                        $buffy .= td_util::get_block_error('List Menu', 'Render stopped in Composer - you are already in a menu' );
                    $buffy .= '</div>';

                $buffy .= '</div>';

            return $buffy;

        }

		/* -- Block atts -- */
		// Menu ID
		$menu_id = $this->get_att( 'menu_id' );

		// Menu display
		$display = $this->get_att( 'inline' );
		self::$menu_display = $display  == 'yes' ? 'horizontal' : ( $display  != '' ? $display  : 'vertical' );

		// Depth
		$depth = $this->get_att( 'depth' );

        td_global::set_in_menu(true);
        $state_menu = false;
        global $tdb_state_single, $tdb_state_category, $tdb_state_tag, $tdb_state_single_page, $td_woo_state_archive_product_page, $td_woo_state_single_product_page, $td_woo_state_shop_base_page;

        if ( defined( 'TD_CLOUD_LIBRARY' ) ) {

            switch (tdb_state_template::get_template_type()) {
                case 'cpt':
                case 'single':
                    $state_menu = $tdb_state_single->list_menu->__invoke($this->get_all_atts());
                    break;

                case 'category':
                case 'cpt_tax':
                    $state_menu = $tdb_state_category->list_menu->__invoke($this->get_all_atts());
                    break;

                case 'tag':
                    $state_menu = $tdb_state_tag->list_menu->__invoke( $this->get_all_atts() );
                    break;

                case 'page':
                    $state_menu = $tdb_state_single_page->list_menu->__invoke($this->get_all_atts());
                    break;

                case 'woo_archive':
                    $state_menu = $td_woo_state_archive_product_page->list_menu->__invoke($this->get_all_atts());
                    break;

                case 'woo_product':
                    $state_menu = $td_woo_state_single_product_page->list_menu->__invoke($this->get_all_atts());
                    break;

                case 'woo_shop_base':
                    $state_menu = $td_woo_state_shop_base_page->list_menu->__invoke($this->get_all_atts());
                    break;
            }

        }

		// Additional classes
		$additional_classes_array = array('td-blm-display-' . self::$menu_display);

		$buffy = ''; //output buffer

		$buffy .= '<div class="' . $this->get_block_classes($additional_classes_array) . ' widget" ' . $this->get_block_html_atts() . '>';

			//get the block css
			$buffy .= $this->get_block_css();

			//get the js for this block
			$buffy .= $this->get_block_js();

			// For tagDiv composer add a placeholder element
			if ( empty( $menu_id ) ) {
					$buffy .= '<div id=' . $this->block_uid . ' class="td_block_inner td-fix-index">';
						$buffy .= td_util::get_block_error('List Menu', 'Render failed - please select a menu' );
					$buffy .= '</div>';

				$buffy .= '</div>';

                td_global::set_in_menu( false );

				return $buffy;
			}


			// Menu atts
			$menu_atts = array(
				'menu' => $menu_id,
				'walker' => new td_block_list_menu_accordion($this->get_all_atts()),
				'depth' => self::$menu_display == 'horizontal' ? 1 : ( $depth != '' ? $depth : 0 )
			);


			// block title wrap
			$buffy .= '<div class="td-block-title-wrap">';
				$buffy .= $this->get_block_title(); //get the block title
				$buffy .= $this->get_pull_down_filter(); //get the sub category filter for this block
			$buffy .= '</div>';


			$buffy .= '<div id=' . $this->block_uid . ' class="td_block_inner td-fix-index">';

                // if the menu was built and comes from a state and we just need to add it to the buffer
                if ( ! empty( $state_menu ) ) {
                    $buffy .= $state_menu;
                } else { // otherwise, we use the menu id and call the wp_nav_menu @see $this->inner()
                    ob_start();
                    $td_menu_instance = td_menu::get_instance();
                    remove_filter( 'wp_nav_menu_objects', array($td_menu_instance, 'hook_wp_nav_menu_objects'), 99999 );
                    wp_nav_menu( $menu_atts );
                    add_filter( 'wp_nav_menu_objects', array($td_menu_instance, 'hook_wp_nav_menu_objects'), 99999, 2 );
                    $buffy .= ob_get_clean();
                }

                $buffy .= '</div>';


			if( self::$menu_display == 'accordion' && !( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) ) {
                td_resources_load::render_script( TDC_SCRIPTS_URL . '/tdListMenu.js' . TDC_SCRIPTS_VER, 'tdListMenu-js', '', 'footer' );
				ob_start();
				?>
				<script>
					/* global jQuery:{} */
					jQuery().ready(function () {

						let uid = '<?php echo $this->block_uid ?>',
							$blockObj = jQuery('.<?php echo $this->block_uid ?>');

						let tdListMenuItem = new tdListMenu.item();
						// block uid
						tdListMenuItem.uid = uid;
						// block object
						tdListMenuItem.blockObj = $blockObj;

						tdListMenu.addItem(tdListMenuItem);

					});
				</script>
				<?php
				td_js_buffer::add_to_footer( "\n" . td_util::remove_script_tag( ob_get_clean() ) );
			}

		$buffy .= '</div>';

        td_global::set_in_menu( false );

		return $buffy;

	}

}



class td_block_list_menu_accordion extends Walker_Nav_Menu {
	private static $atts;
	private static $menu_display;
	private static $is_first_sub_menu;
	private static $sub_menu_open = false;

    public function __construct($atts = array())
    {
        self::$atts = $atts;

		$display = isset( $atts['inline'] ) ? $atts['inline'] : '';
		self::$menu_display = $display  == 'yes' ? 'horizontal' : ( $display  != '' ? $display  : 'vertical' );

		self::$is_first_sub_menu = self::$menu_display == 'accordion';
    }

    public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
        if ( ! $element ) {
            return;
        }

        //var_dump($element);

        $id_field = $this->db_fields['id'];
        $id       = $element->$id_field;

        // Display this element.
        $this->has_children = ! empty( $children_elements[ $id ] );
        if ( isset( $args[0] ) && is_array( $args[0] ) ) {
            $args[0]['has_children'] = $this->has_children; // Back-compat.
        }

        $this->start_el( $output, $element, $depth, ...array_values( $args ) );

        // Descend only when the depth is right and there are children for this element.
        if ( ( 0 == $max_depth || $max_depth > $depth + 1 ) && isset( $children_elements[ $id ] ) ) {

            foreach ( $children_elements[ $id ] as $child ) {

                if ( ! isset( $newlevel ) ) {
                    $newlevel = true;
                    // Start the child delimiter.
                    $this->start_lvl( $output, $depth, ...array_values( $args ) );
                }
                $this->display_element( $child, $children_elements, $max_depth, $depth + 1, $args, $output );
            }
            unset( $children_elements[ $id ] );
        }

        if ( isset( $newlevel ) && $newlevel ) {
            // End the child delimiter.
            $this->end_lvl( $output, $depth, ...array_values( $args ) );
        }

        // End this element.
        $this->end_el( $output, $element, $depth, ...array_values( $args ) );
    }

	/**
	 * Starts the list before the elements are added.
	 *
	 * @since 3.0.0
	 *
	 * @see Walker::start_lvl()
	 *
	 * @param string   $output Used to append additional content (passed by reference).
	 * @param int      $depth  Depth of menu item. Used for padding.
	 * @param stdClass $args   An object of wp_nav_menu() arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
			$t = '';
			$n = '';
		} else {
			$t = "\t";
			$n = "\n";
		}
		$indent = str_repeat( $t, $depth );

		// Default class.
		$classes = array( 'sub-menu' );

		// Place sub-menu in an open state
		$style = '';
		if( self::$sub_menu_open ) {
			$style = ' style="display:block"';
		}

		/**
		 * Filters the CSS class(es) applied to a menu list element.
		 *
		 * @since 4.8.0
		 *
		 * @param string[] $classes Array of the CSS classes that are applied to the menu `<ul>` element.
		 * @param stdClass $args    An object of `wp_nav_menu()` arguments.
		 * @param int      $depth   Depth of menu item. Used for padding.
		 */
		$class_names = implode( ' ', apply_filters( 'nav_menu_submenu_css_class', $classes, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		$output .= "{$n}{$indent}<ul$class_names $style>{$n}";
	}

	/**
	 * Starts the element output.
	 *
	 * @since 3.0.0
	 * @since 4.4.0 The {@see 'nav_menu_item_args'} filter was added.
	 * @since 5.9.0 Renamed `$item` to `$data_object` and `$id` to `$current_object_id`
	 *              to match parent class for PHP 8 named parameter support.
	 *
	 * @see Walker::start_el()
	 *
	 * @param string   $output            Used to append additional content (passed by reference).
	 * @param WP_Post  $data_object       Menu item data object.
	 * @param int      $depth             Depth of menu item. Used for padding.
	 * @param stdClass $args              An object of wp_nav_menu() arguments.
	 * @param int      $current_object_id Optional. ID of the current menu item. Default 0.
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		// Restores the more descriptive, specific name for use within this method.
		$menu_item = $data_object;
        $menu_item_has_children = false;

        if( is_array( $args ) ) {
            $menu_item_has_children = $args['has_children'];
        } else {
            $menu_item_has_children = $args->walker->has_children;
        }

		if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
			$t = '';
			$n = '';
		} else {
			$t = "\t";
			$n = "\n";
		}
		$indent = ( $depth ) ? str_repeat( $t, $depth ) : '';

		$classes   = empty( $menu_item->classes ) ? array() : (array) $menu_item->classes;
		$classes[] = 'menu-item-' . $menu_item->ID;


		// If the current menu item is active and the option to
		// open the active menu item's sub-menu is enabled,
		// then set the class and enabled the 'sub_menu_open' flag
		self::$sub_menu_open = false;

		if( self::$menu_display == 'accordion' &&
			$menu_item_has_children &&
			(
				(
					( in_array('current-menu-ancestor', $classes) || in_array('current-menu-item', $classes) ) &&
					( isset( self::$atts['curr_submenu_open'] ) && self::$atts['curr_submenu_open'] == 'yes' )
				) ||
				(
					( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) &&
					self::$is_first_sub_menu
				)
			)
		) {
			$classes[] = 'menu-item-has-children-open';

			self::$sub_menu_open = true;
			self::$is_first_sub_menu = false;
		}


		/**
		 * Filters the arguments for a single nav menu item.
		 *
		 * @since 4.4.0
		 *
		 * @param stdClass $args      An object of wp_nav_menu() arguments.
		 * @param WP_Post  $menu_item Menu item data object.
		 * @param int      $depth     Depth of menu item. Used for padding.
		 */
		$args = apply_filters( 'nav_menu_item_args', $args, $menu_item, $depth );

		/**
		 * Filters the CSS classes applied to a menu item's list item element.
		 *
		 * @since 3.0.0
		 * @since 4.1.0 The `$depth` parameter was added.
		 *
		 * @param string[] $classes   Array of the CSS classes that are applied to the menu item's `<li>` element.
		 * @param WP_Post  $menu_item The current menu item object.
		 * @param stdClass $args      An object of wp_nav_menu() arguments.
		 * @param int      $depth     Depth of menu item. Used for padding.
		 */
		$class_names = implode( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $menu_item, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		/**
		 * Filters the ID attribute applied to a menu item's list item element.
		 *
		 * @since 3.0.1
		 * @since 4.1.0 The `$depth` parameter was added.
		 *
		 * @param string   $menu_item_id The ID attribute applied to the menu item's `<li>` element.
		 * @param WP_Post  $menu_item    The current menu item.
		 * @param stdClass $args         An object of wp_nav_menu() arguments.
		 * @param int      $depth        Depth of menu item. Used for padding.
		 */
		$id = apply_filters( 'nav_menu_item_id', 'menu-item-' . $menu_item->ID, $menu_item, $args, $depth );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

		$output .= $indent . '<li' . $id . $class_names . '>';

		$atts           = array();
		$atts['title']  = ! empty( $menu_item->attr_title ) ? $menu_item->attr_title : '';
		$atts['target'] = ! empty( $menu_item->target ) ? $menu_item->target : '';
		if ( '_blank' === $menu_item->target && empty( $menu_item->xfn ) ) {
			$atts['rel'] = 'noopener';
		} else {
			$atts['rel'] = $menu_item->xfn;
		}
		$atts['href']         = ! empty( $menu_item->url ) ? $menu_item->url : '';
		$atts['aria-current'] = $menu_item->current ? 'page' : '';

		/**
		 * Filters the HTML attributes applied to a menu item's anchor element.
		 *
		 * @since 3.6.0
		 * @since 4.1.0 The `$depth` parameter was added.
		 *
		 * @param array $atts {
		 *     The HTML attributes applied to the menu item's `<a>` element, empty strings are ignored.
		 *
		 *     @type string $title        Title attribute.
		 *     @type string $target       Target attribute.
		 *     @type string $rel          The rel attribute.
		 *     @type string $href         The href attribute.
		 *     @type string $aria-current The aria-current attribute.
		 * }
		 * @param WP_Post  $menu_item The current menu item object.
		 * @param stdClass $args      An object of wp_nav_menu() arguments.
		 * @param int      $depth     Depth of menu item. Used for padding.
		 */
		$atts = apply_filters( 'nav_menu_link_attributes', $atts, $menu_item, $args, $depth );

		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( is_scalar( $value ) && '' !== $value && false !== $value ) {
				$value       = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}

		/** This filter is documented in wp-includes/post-template.php */
		$title = apply_filters( 'the_title', $menu_item->title, $menu_item->ID );

		/**
		 * Filters a menu item's title.
		 *
		 * @since 4.4.0
		 *
		 * @param string   $title     The menu item's title.
		 * @param WP_Post  $menu_item The current menu item object.
		 * @param stdClass $args      An object of wp_nav_menu() arguments.
		 * @param int      $depth     Depth of menu item. Used for padding.
		 */
		$title = apply_filters( 'nav_menu_item_title', $title, $menu_item, $args, $depth );

		$item_output = '<a' . $attributes . '>';
		$item_output .= '<span class="td-blm-menu-item-txt">' . $title . '</span>';

		// Sub-menu icon
		if( $menu_item_has_children && ( self::$menu_display == 'accordion' ) ) {
			$main_sub_menu_icon = isset( self::$atts['sub_tdicon'] ) ? self::$atts['sub_tdicon'] : '';
			$sub_sub_menu_icon = isset( self::$atts['sub_sub_tdicon'] ) ? self::$atts['sub_sub_tdicon'] : '';

			$sub_menu_icon = $menu_item->menu_item_parent == 0 ? $main_sub_menu_icon : $sub_sub_menu_icon;

			if( $sub_menu_icon != '' ) {
				$svg_list = td_global::$svg_theme_font_list;

				if( array_key_exists( $sub_menu_icon, $svg_list ) ) {
					$sub_menu_icon_data = '';
					if( $sub_menu_icon != '' && ( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) ) {
						$sub_menu_icon_data = 'data-td-svg-icon="' . $sub_menu_icon_data . '"';
					}

					$item_output .= '<span class="td-blm-sub-icon" ' . $sub_menu_icon_data . '>' . base64_decode($svg_list[$sub_menu_icon]) . '</span>';
				} else {
					$item_output .= '<i class="td-blm-sub-icon ' . $sub_menu_icon . '"></i>';
				}
			}
		}

		$item_output .= '</a>';


		/**
		 * Filters a menu item's starting output.
		 *
		 * The menu item's starting output only includes `$args->before`, the opening `<a>`,
		 * the menu item's title, the closing `</a>`, and `$args->after`. Currently, there is
		 * no filter for modifying the opening and closing `<li>` for a menu item.
		 *
		 * @since 3.0.0
		 *
		 * @param string   $item_output The menu item's starting HTML output.
		 * @param WP_Post  $menu_item   Menu item data object.
		 * @param int      $depth       Depth of menu item. Used for padding.
		 * @param stdClass $args        An object of wp_nav_menu() arguments.
		 */
		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $menu_item, $depth, $args );
	}

}