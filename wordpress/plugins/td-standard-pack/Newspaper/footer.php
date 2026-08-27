<?php

$tdb_template_type = td_util::get_tdb_template_type();
$hide_footer = $tdb_template_type == 'header' || ( $tdb_template_type == 'footer' && !td_util::tdc_is_live_editor_iframe() ) || $tdb_template_type == 'module';

// footer loading type option
$footer_ui_delay = td_util::get_option('tds_footer_ui_delay');

if ( td_util::tdc_is_live_editor_iframe() || ( ! td_util::is_template_footer() && ! td_util::is_no_footer() ) ) {

    $hide_class = '';
    $tdbTemplateType = tdc_util::get_get_val('tdbTemplateType');
    if ( td_util::is_template_footer() || td_util::is_no_footer() || 'header' === $tdbTemplateType || $hide_footer ) {
        $hide_class = 'tdc-zone-invisible';
    }

	?>

	<?php if ( td_util::get_option( 'tds_footer_instagram' ) == 'show' ) { ?>

        <div class="td-main-content-wrap td-footer-instagram-container td-container-wrap <?php echo td_util::get_option( 'td_full_footer_instagram' ) ?><?php echo esc_attr( $hide_class ) ?>">
			<?php

			// get the instagram id from the panel
			$tds_footer_instagram_id = td_instagram::strip_instagram_user( td_util::get_option( 'tds_footer_instagram_id' ) );

            if ( !empty($tds_footer_instagram_id) ) {

			?>

            <div class="td-instagram-user">
                <h4 class="td-footer-instagram-title">
					<?php echo __td( 'Follow us on Instagram', TD_THEME_NAME ); ?>
                    <a class="td-footer-instagram-user-link" href="https://www.instagram.com/<?php echo esc_attr( $tds_footer_instagram_id ) ?>" target="_blank">@<?php printf( '%1$s', $tds_footer_instagram_id ) ?></a>
                </h4>
            </div>

			<?php

            }

			// get the other panel settings
			$tds_footer_instagram_data             = base64_encode( td_util::get_option( 'tds_footer_instagram_data' ) );
			$tds_footer_instagram_nr_of_row_images = intval( td_util::get_option( 'tds_footer_instagram_on_row_images_number' ) );
			$tds_footer_instagram_nr_of_rows       = intval( td_util::get_option( 'tds_footer_instagram_rows_number' ) );
			$tds_footer_instagram_img_size         = td_util::get_option( 'tds_footer_instagram_image_size' );
			$tds_footer_instagram_img_gap          = td_util::get_option( 'tds_footer_instagram_image_gap' );
			$tds_footer_instagram_header           = td_util::get_option( 'tds_footer_instagram_header_section' );

			// show the instagram block
			echo td_global_blocks::get_instance( 'td_block_instagram' )->render(
				array(
                    'instagram_account_source' => !empty($tds_footer_instagram_id) ? 'ig_business_' . $tds_footer_instagram_id : '',
					'instagram_demo_data'      => $tds_footer_instagram_data,
					'instagram_header'         => 1,
					'instagram_images_per_row' => $tds_footer_instagram_nr_of_row_images,
					'instagram_number_of_rows' => $tds_footer_instagram_nr_of_rows,
					'instagram_images_size'    => $tds_footer_instagram_img_size,
					'instagram_margin'         => $tds_footer_instagram_img_gap
				)
			);

			?>
        </div>

	<?php } ?>

	<?php

	$tds_footer_page = td_util::get_option( 'tds_footer_page' );
	$footer_page     = null;

	if ( $tds_footer_page !== '' && intval( $tds_footer_page ) !== get_the_ID() ) {
		$footer_page = get_post( $tds_footer_page );
	}

	if ( $footer_page instanceof WP_Post ) {

		?>

        <div class="td-footer-page td-footer-container td-container-wrap <?php echo esc_attr( $hide_class ) ?>">
			<?php

			// Add suffix class to tdc_zone shortcode, to avoid wrapper in composer.
			// For the moment, it's necessary to not have more than 5 zones in composer (1 from content and 4 from header template)
			if ( td_util::tdc_is_live_editor_iframe() ) {
				tdc_zone::set_suffix_class( '-in-footer' );
			}

			// This action must be removed, because it's added by TagDiv Composer, and it affects footers with custom content
			remove_action( 'the_content', 'tdc_on_the_content', 10000 );
			remove_filter( 'the_content', 'wpautop' );

			/**
			 * reset post data before getting the post content
			 * fix for attachment img appearing before footer content on composer iframe when when editing an attachment template whit real data
			 * @since 12.02.2019
			 */
			if ( td_util::tdc_is_live_editor_iframe() ) {
				wp_reset_postdata();
			}

			$content = apply_filters( 'the_content', $footer_page->post_content );
			$content = str_replace( ']]>', ']]&gt;', $content );

			//bbpress removes all the filters
			//if is bbpress template run do_shortcode()
			if ( td_global::$current_template == 'bbpress' ) {
				echo do_shortcode( $content );
			} else {
				echo '<!-- footer content -->' . $content;
			}

			wp_reset_query();

			// Reset previous modified tdc_zone suffix class
			if ( td_util::tdc_is_live_editor_iframe() ) {
				tdc_zone::set_suffix_class( '' );
			}

			?>
        </div>

		<?php

	} else {

		if ( td_util::tdc_is_live_editor_iframe() || ( ! td_util::is_template_footer() && ! td_util::is_no_footer() ) ) {

			?>

            <div class="tdc-footer-wrap <?php echo esc_attr( $hide_class ) ?>">

                <!-- Footer -->
				<?php
				if ( td_util::get_option( 'tds_footer' ) != 'no' ) {
					td_api_footer_template::_helper_show_footer();
				}
				?>

                <!-- Sub Footer -->
				<?php
				if ( td_util::get_option( 'tds_sub_footer' ) != 'no' ) {
					td_api_sub_footer_template::_helper_show_sub_footer();
				}


				?>
            </div><!--close td-footer-wrap-->
			<?php
		}
	}
}



if ( td_util::tdc_is_live_editor_iframe() || td_util::is_template_footer() ) {

    $tdc_footer_template_content = td_util::get_footer_template_content();

    $hide_class = '';
    $tdbTemplateType = tdc_util::get_get_val('tdbTemplateType');
    if ( 'header' === $tdbTemplateType ) {
        $hide_class = 'tdc-zone-invisible';
    }

    ?>
    <div class="td-footer-template-wrap" style="position: relative<?php echo $hide_footer ? ';display:none' : '' ?>">
        <?php

        if ( empty( $tdc_footer_template_content ) ) {
            $shortcode = '[tdc_zone type="tdc_footer"][vc_row][vc_column][/vc_column][/vc_row][/tdc_zone]';
            $hide_class = 'tdc-zone-invisible';
        } else {
            $shortcode = $tdc_footer_template_content;
        }

        ?>
        <div class="td-footer-wrap <?php echo esc_attr( $hide_class ) ?>">
            <?php

            if ( $footer_ui_delay === 'on' && !tdc_state::is_live_editor_iframe() && !$hide_footer ) {
                // do nothing, footer content will be rendered on ui interaction
            } else {
                echo do_shortcode( shortcode_unautop($shortcode) );
            }

            ?>
        </div>

    </div>
    <?php
}

?>

<?php if ( $footer_ui_delay === 'on' && !tdc_state::is_live_editor_iframe() && !$hide_footer ) { ?>

    <script id="td-footer-delay-script">

        <?php
        global $post;
        $post_id = !empty($post) ? $post->ID : '';
        ?>

        // jQuery(document).ready( function() {
        document.addEventListener( 'DOMContentLoaded', function() {
            'use strict';

            /* global jQuery:{} */
            /* global td_ajax_url, td_res_context_registered_atts */

            const tdFooterWrap = jQuery('.td-footer-wrap');
            const tdPostID = '<?php echo $post_id; ?>';

            // on ui_delayed_load event
            tdFooterWrap.on( 'ui_delayed_load', function () {

                jQuery.ajax({
                    type: 'POST',
                    url: td_ajax_url,
                    data: {
                        action: 'tdb_get_footer',
                        postID: tdPostID,
                        td_res_context_registered_atts: td_res_context_registered_atts
                    },
                    success: function( data, textStatus, XMLHttpRequest ) {

                        // console.groupCollapsed('%c footerLoadContent/tdb_get_footer: success', 'color: mediumseagreen;' );
                        //     console.log( 'Post: ', { postId: tdPostID } );
                        //     console.log( 'Reply: ', data );
                        // console.groupEnd();

                        // decode data
                        var decodedData = jQuery.parseJSON(data);

                        // process data content
                        if ( 'undefined' !== typeof decodedData.content ) {
                            jQuery(decodedData.content).appendTo(tdFooterWrap);

                            // add custom classes
                            if ( 'undefined' !== typeof decodedData.classes ) {
                                tdFooterWrap.addClass(decodedData.classes);
                            }

                            // reinit lazy load
                            if ( ( 'undefined' !== typeof window.tdAnimationStack ) && ( true === window.tdAnimationStack.activated ) ) {
                                window.tdAnimationStack.reinit();
                            }

                        }

                    },
                    error: function( MLHttpRequest, textStatus, errorThrown ) {
                        // console.group('%c footerLoadContent/tdb_get_footer: error', 'color: orangered;' );
                        // console.log( 'Post: ', { postId: tdPostID, } );
                        // console.log( 'Error data: ', {
                        //     errorThrown: errorThrown,
                        //     textStatus: textStatus,
                        //     status: MLHttpRequest.status !== undefined ? MLHttpRequest.status : '',
                        //     MLHttpRequest: MLHttpRequest,
                        // });
                        // console.groupEnd();
                    }
                });

            });

            // ui events
            const uiEvents = [
                'mouseover',
                'click',
                'keydown',
                'wheel',
                "touchmove",
                "touchstart",
            ];

            // ui events handler
            function uiEventsHandler(e) {

                // console.log( '%c delayed footer load', 'color: white; background-color: #7ad03a' );
                // console.log( 'event type:', e.type );

                // trigger delayed footer loading
                tdFooterWrap.trigger('ui_delayed_load');

                // remove ui_events
                uiEvents.forEach( e => {
                    //console.log( 'removeEventListener:', e );
                    window.removeEventListener( e, uiEventsHandler );
                });

            }

            // utility function to check if an element is in viewport
            function isElementInViewport(el) {

                var rect = el.getBoundingClientRect();

                // var header_menu_affix = jQuery('.td-header-menu-wrap.td-affix'),
                //     header_menu_affix_height = header_menu_affix.length ? header_menu_affix.outerHeight() : 0;

                // var admin_bar = jQuery('#wpadminbar'),
                //     admin_bar_height = admin_bar.length ? admin_bar.outerHeight() : 0;

                // var setTop = tdFooterWrap.offset().top/* - ( header_menu_affix_height + admin_bar_height )*/,
                //     setHeight = tdFooterWrap.outerHeight(true),
                //     setBottom = setTop + setHeight; // set the bottom by adding its height to the scroll position of its top

                // var win = jQuery(window),
                //     windowTop = win.scrollTop(),
                //     windowBottom = windowTop + win.height();

                /* rest */
                // console.log( 'rect', rect );
                // console.log( 'rect:top', rect.top );
                // console.log( 'rect:bottom', rect.bottom );
                // console.log( 'window.innerHeight', window.innerHeight );

                /* element */
                // console.log( 'top', setTop );
                // console.log( 'height', setHeight );
                // console.log( 'bottom', setBottom );

                /* window */
                // console.log( 'window top', windowTop );
                // console.log( 'window bottom', windowBottom );

                return (
                    rect.bottom >= 0 &&
                    rect.top <= ( window.innerHeight || document.documentElement.clientHeight )
                );
            }

            // check if footer element is in viewport
            function checkFooterVisibility() {

                if ( isElementInViewport(tdFooterWrap[0]) ) {
                    //console.log('footer el is in the viewport!');

                    // trigger delayed footer loading
                    tdFooterWrap.trigger('ui_delayed_load');

                } else {
                    // console.log('footer el is NOT in the viewport!');

                    // add ui events
                    uiEvents.forEach( e => {
                        //console.log( 'footer ui delayed load addEventListener: ', e );
                        window.addEventListener( e, uiEventsHandler, { passive: true } );
                    });

                }

            }

            // initial load, check footer visibility after a short delay
            setTimeout( checkFooterVisibility, 100 );

        });

    </script>

<?php } ?>

</div><!--close td-outer-wrap-->


<?php wp_footer(); ?>

</body>
</html>