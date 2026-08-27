<?php

class tdb_module_template {

	var $module_template_obj;
	var $template_class;
	var $post_obj;


	function __construct( $post_obj, $module_template_id, $flex_block_atts = array() ) {

        if ( is_array($post_obj) && !empty( $post_obj['post_id'] ) ) {
            $wp_post_obj = get_post( $post_obj['post_id'] );
        } else {
            $wp_post_obj = get_post( $post_obj );
        }

		$this->module_template_obj = get_post( $module_template_id );
		$this->template_class = 'tdb_module_template_' . $module_template_id;
		$this->post_obj = $wp_post_obj;


        // Get the current template type
        $tdb_template_type = null;
        if ( is_singular( array( 'tdb_templates' ) ) ) {
            global $post;
            if ( !empty( $post ) ) {
                $tdb_template_type = get_post_meta( $post->ID, 'tdb_template_type', true );
            }
        }

        // Only set the module template params if we are not currently
        // in a module template; this is to avoid overriding it in cases
        // in which the module is present in the header and we are editing/viewing
        // a module template
        if ( $tdb_template_type !== 'module' ) {
            global $tdb_module_template_params;
            $tdb_module_template_params = array(
                'template_obj' => $this->module_template_obj,
                'template_class' => $this->template_class,
                'post_obj' => $this->post_obj,
                'shortcodes' => array()
            );
        }

	}


	function render() {
		ob_start();
	
		td_global::set_in_tdb_module_template(true);

        $is_composer = false;
        if( td_util::tdc_is_live_editor_iframe() || td_util::tdc_is_live_editor_ajax() ) {
            $is_composer = true;
        }

		$module_classes = array(
			'td_module_wrap',
			$this->template_class,
			'td-animation-stack',
			'td-cpt-'. $this->post_obj->post_type
		);

		?>

		<div class="<?php echo implode(' ', $module_classes ); ?>">
			<div class="td-module-container">
                <?php

                // build module tpl edit btn
                $module_tpl_edit_url = add_query_arg(
	                array(
		                'post_id' => $this->module_template_obj->ID,
		                'td_action' => 'tdc',
		                'tdbTemplateType' => 'module',
		                'tdbLoadDataFromId' => $this->post_obj->ID,
		                'prev_url' => rawurlencode( tdc_util::get_current_url() ),
	                ),
	                admin_url( 'post.php' )
                );

                // add module tpl edit btn
                if ( current_user_can('edit_published_posts') && !$is_composer ) {
                    echo '<div class="tdb-module-tpl-edit-btns">';
                        echo '<a class="tdb-module-tpl-edit-btn" href="' . $module_tpl_edit_url . '" target="_blank">Edit template</a>';
                        echo '<a class="tdb-module-tpl-edit-btn" href="' . get_edit_post_link( $this->post_obj->ID ) . '" target="_blank">Edit post</a>';
                    echo '</div>';
                }

                if ( td_global::get_in_menu() ) {
	                echo do_shortcode( $this->module_template_obj->post_content );
                } else {
	                td_global::set_in_element( true );
	                echo do_shortcode( $this->module_template_obj->post_content );
	                td_global::set_in_element( false );
                }

                ?>
			</div>
		</div>

		<?php

		td_global::set_in_tdb_module_template(false);

        return ob_get_clean();
	}

}