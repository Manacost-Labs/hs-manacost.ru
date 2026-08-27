<div class="td-page-options-tab-wrap">
    <div class="td-page-options-tab td-page-options-tab-active" data-panel-class="td-post-option-general"><a href="#">General</a></div>
    <?php if( 'Newsmag' == TD_THEME_NAME || ( 'Newspaper' == TD_THEME_NAME && defined('TD_STANDARD_PACK') ) ) { ?>
        <div class="td-page-options-tab" data-panel-class="td-page-option-post-smart-list"><a href="#">Smart List</a></div>
    <?php } ?>
    <div class="td-page-options-tab" data-panel-class="td-page-option-post-review"><a href="#">Reviews</a></div>
    <div class="td-page-options-tab" data-panel-class="td-page-option-post-autoload-options">
        <a href="#">Autoload Options</a>
    </div>
    <?php

    $td_post_settings_tabs = apply_filters( 'td_post_settings_tabs', array() );

    if ( !empty($td_post_settings_tabs) && is_array($td_post_settings_tabs) ) {
        foreach ( $td_post_settings_tabs as $tab ) {

            // tabs id/name/file are required
            if ( !isset( $tab['id'], $tab['name'], $tab['file'] ) )
                continue;
            ?>

            <div class="td-page-options-tab" data-panel-class="td-page-option-post-<?php echo $tab['id'] ?>"><a href="#"><?php echo $tab['name'] ?></a></div>

	        <?php
        }
    }

    ?>
</div>

<div class="td-meta-box-inside">

    <!-- post option general -->
    <div class="td-page-option-panel td-post-option-general td-page-option-panel-active">

        <div class="td-meta-box-row">
            <span class="td-page-o-custom-label">
                Post template:
                <?php
                    td_util::tooltip_html('
                        <h3>Post templates:</h3>
                        <p>When this is set to "From panel" the theme will load the template that is set in the panel.</p>
                        <ul>
                            <li><strong>If set, this setting overrides</strong> the Theme panel setting from <i>Post settings > Default post template</i></li>
                        </ul>
                    ', 'right')
                ?>
            </span>
            <div class="td-inline-block-wrap td-post-templates-metabox">
                <?php
                echo td_panel_generator::visual_select_o(array(
                    'ds' => 'td_post_theme_settings',
                    'item_id' => '',
                    'option_id' => 'td_post_template',
                    'values' => td_api_single_template::_helper_td_global_list_to_metaboxes(),
                    'selected_value' => $mb->get_the_value('td_post_template')
                ));
                ?>
            </div>
        </div>

        <!-- primary category -->
        <div class="td-meta-box-row">
            <span class="td-page-o-custom-label">
                Primary category:
                <?php
                td_util::tooltip_html('
                        <h3>Primary category explained:</h3>
                        <p>In '. TD_THEME_NAME . ' theme each post has a <i>Primary category</i> and all the settings from that category will be trasfered to the post. The Primary category will
                        also be used as a category label that appears on the thumbs and the category breadcrumb</p>

                        <p>Here are the settings that are inherited from the <i>Primary category</i>: Custom sidebars, Sidebar position and Background</p>
                        <p>How the Primary category is picked</p>
                        <ul>
                            <li><strong>Manually</strong> - If you select it from this box, this post will inherit all the settings form the <i>Primary category</i>.</li>
                            <li><strong>If the post has only one category</strong> - that will be the <i>Primary category</i></li>
                            <li><strong>If the post has multiple categories and no manual Primary category</strong>, the theme will pick the first category from the categories of this post ordered alphabetically</li>

                        </ul>
                    ', 'right')
                ?>
            </span>
            <?php $mb->the_field('td_primary_cat'); ?>
            <div class="td-select-style-overwrite td-inline-block-wrap">
                <select name="<?php $mb->the_name(); ?>" class="td-panel-dropdown">
                    <option value="">Auto select a category</option>
                    <?php
                    $td_current_categories = td_util::get_category2id_array(false, false );

                    //print_r($td_current_categories);
                    //die;
                    foreach ($td_current_categories as $td_category => $td_category_id) {
                        $disabled = '';
                        if ( '__' === $td_category_id && false !== strpos($td_category, '--')) {
                            $disabled = 'disabled';
                        }
                        ?>
                        <option value="<?php echo $td_category_id?>"<?php $mb->the_select_state($td_category_id); ?> <?php echo $disabled ?>><?php echo $td_category?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>
            <span class="td-page-o-info">If the posts has multiple categories, the one selected here will be used for settings and it appears in the category labels.</span>
        </div>
<?php
//global $post;
//$post_id = $post->ID;
//
//$td_post_theme_settings = td_util::get_post_meta_array( $post_id, 'td_post_theme_settings' );
//$default_template_id = td_util::get_option( 'td_default_site_post_template' );
//$template_id = '';

//// check if we have a specific template set on the current post
//if ( !empty( $td_post_theme_settings[ 'td_post_template' ] ) ) {
//    $template_id = $td_post_theme_settings['td_post_template'];
//} else { //check global template
//    $template_id = td_util::get_option( 'td_default_site_post_template' );
//}
//if ( !td_global::is_tdb_template( $template_id, true ) ) { ?>
        <!-- sidebar position -->
        <div class="td-meta-box-row td-sidebar-box">
            <span class="td-page-o-custom-label">
                Sidebar position:
                <?php
                td_util::tooltip_html('
                        <h3>Sidebar position:</h3>
                        <p>For best results and easy to maintain websites we recommend that you set the sidebar from the <i>Primary category</i> of this post. That way if you have
                        multiple posts, when you change the category settings all the posts will match the category</p>
                        <ul>
                            <li><strong>This setting overrides</strong> the Theme panel setting from <i>Post settings > Default post template</i> and the <i>Category settings</i></li>
                            <li><strong>On default</strong> - the post will look at the primary category settings and it will try to get the position form there. If the primary category
                            does not have a custom sidebar position, the post will load the setting from <i>Template settings > Blog and posts template</i></li>

                        </ul>
                    ', 'right')
                ?>
            </span>
                <?php
                echo td_panel_generator::visual_select_o(array(
                    'ds' => 'td_post_theme_settings',
                    'item_id' => '',
                    'option_id' => 'td_sidebar_position',
                    'values' => array(
                        array('text' => '', 'title' => 'Sidebar Default', 'val' => '', 'class' => 'td-sidebar-position-default', 'img' => TDC_URL_LEGACY_COMMON . '/wp_booster/wp-admin/images/panel/sidebar/sidebar-default.png'),
                        array('text' => '', 'title' => 'Sidebar Left', 'val' => 'sidebar_left', 'class' => 'td-sidebar-position-left', 'img' => TDC_URL_LEGACY_COMMON . '/wp_booster/wp-admin/images/panel/sidebar/sidebar-left.png'),
                        array('text' => '', 'title' => 'No Sidebar', 'val' => 'no_sidebar', 'class' => 'td-no-sidebar', 'img' => TDC_URL_LEGACY_COMMON . '/wp_booster/wp-admin/images/panel/sidebar/sidebar-full.png'),
                        array('text' => '', 'title' => 'Sidebar Right', 'val' => 'sidebar_right', 'class' => 'td-sidebar-position-right','img' => TDC_URL_LEGACY_COMMON . '/wp_booster/wp-admin/images/panel/sidebar/sidebar-right.png')
                    ),
                    'selected_value' => $mb->get_the_value('td_sidebar_position')
                ));
                ?>
        </div>

        <!-- custom sidebar -->
        <div class="td-meta-box-row td-sidebar-box">
            <span class="td-page-o-custom-label">
                Custom sidebar:
                <?php
                td_util::tooltip_html('
                        <h3>Custom sidebar:</h3>
                        <p>For best results and easy to maintain websites we recommend that you set the sidebar from the <i>Primary category</i> of this post. That way if you have
                        multiple posts, when you change the category settings all the posts will match the category</p>
                        <ul>
                            <li><strong>This setting overrides</strong> the Theme panel setting from <i>Post settings > Default post template</i> and the <i>Category settings</i></li>
                            <li><strong>On default</strong> - the post will look at the primary category settings and it will try to get the sidebar form there. If the primary category
                            does not have a custom sidebar, the post will load the setting from <i>Template settings > Blog and posts template</i></li>
                        </ul>
                    ', 'right')
                ?>
            </span>
            <?php
            echo td_panel_generator::sidebar_pulldown(array(
                'ds' => 'td_post_theme_settings',
                'item_id' => '',
                'option_id' => 'td_sidebar',
                'selected_value' => $mb->get_the_value('td_sidebar')
            ));
            ?>
        </div>
<?php //} ?>
        <div class="td-meta-box-row">
            <?php $mb->the_field('td_subtitle'); ?>
            <span class="td-page-o-custom-label td_text_area_label">Subtitle:</span>
            <textarea name="<?php $mb->the_name(); ?>" class="td-textarea-subtitle"><?php $mb->the_value(); ?></textarea>
            <span class="td-page-o-info">This text will appear under the title</span>
        </div>

        <?php if( 'Newspaper' == TD_THEME_NAME && defined('TD_CLOUD_LIBRARY') ) { ?>
        <div class="td-meta-box-row td-meta-box-gallery-imgs">
            <?php $mb->the_field('td_gallery_imgs'); ?>
            <span class="td-page-o-custom-label td_text_area_label">Gallery:</span>

            <input type="hidden" class="td-gallery-imgs-ids" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>" />

            <div class="td-gallery-imgs-items">
                <div class="td-gipi-inner">
                    <?php 
                        $gallery_imgs_ids = $mb->get_the_value('td_gallery_imgs');

                        if( $gallery_imgs_ids ) {
                            $gallery_imgs_ids = explode(',', $gallery_imgs_ids);

                            $buffy = '';

                            foreach( $gallery_imgs_ids as $gallery_img_id ) {
                                $gallery_img_url = wp_get_attachment_image_src( $gallery_img_id );

                                if( $gallery_img_url ) {
                                    $buffy .= '<div class="td-gipi-item" data-img-id="' . $gallery_img_id . '">';
                                        $buffy .= '<div class="td-gipi-item-inner">';
                                            $buffy .= '<img src="' . $gallery_img_url[0] . '" />';

                                            $buffy .= '<div class="td-gipi-item-delete">X</div>';
                                        $buffy .= '</div>';
                                    $buffy .= '</div>';
                                }
                            }

                            echo $buffy;
                        }
                    ?>

                    <div class="td-gipi-item td-gipi-add">
                        <div class="td-gipi-item-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 512c141.4 0 256-114.6 256-256S397.4 0 256 0S0 114.6 0 256S114.6 512 256 512zM232 344V280H168c-13.3 0-24-10.7-24-24s10.7-24 24-24h64V168c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24H280v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            <span class="td-page-o-info">Add images which could be used to display a gallery on a post.</span>
        </div>
        <?php } ?>

        <div class="td-meta-box-row">
            <?php $mb->the_field('td_quote_on_blocks'); ?>
            <span class="td-page-o-custom-label">Quote on blocks:</span>
            <input class="td-input-text-post-settings" type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"/>
            <span class="td-page-o-info">Show a quote (only when this article shows up in blocks that support quote and only on blocks that are on one column)</span>
        </div>

        <div class="td-meta-box-row">
            <?php $mb->the_field('td_source'); ?>
            <span class="td-page-o-custom-label">Source name:</span>
            <input class="td-input-text-post-settings" type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"/>
            <span class="td-page-o-info">This name will appear at the end of the article in the "source" spot on single posts. It can also be used as a custom field within the Custom Field shortcode: td_source, in the <a href="https://forum.tagdiv.com/modules-builder-newspaper-theme/" target="_blank">Module Templates</a> </span>
        </div>

        <div class="td-meta-box-row">
            <?php $mb->the_field('td_source_url'); ?>
            <span class="td-page-o-custom-label">Source url:</span>
            <input class="td-input-text-post-settings" type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"/>
            <span class="td-page-o-info">Full url to the source. It can also be used as a custom field within the Custom Field shortcode: td_source_url, in the Module Templates</span>
        </div>

        <div class="td-meta-box-row">
            <?php $mb->the_field('td_via'); ?>
            <span class="td-page-o-custom-label">Via name:</span>
            <input class="td-input-text-post-settings" type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"/>
            <span class="td-page-o-info">Via (your source) name, this will appear at the end of the article in the "via" spot. It can also be used as a custom field within the Custom Field shortcode: td_via, in the <a href="https://forum.tagdiv.com/modules-builder-newspaper-theme/" target="_blank">Module Templates</a> </span>

        </div>

        <div class="td-meta-box-row">
            <?php $mb->the_field('td_via_url'); ?>
            <span class="td-page-o-custom-label">Via url:</span>
            <input class="td-input-text-post-settings" type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"/>
            <span class="td-page-o-info">Full url for via. It can also be used as a custom field within the Custom Field shortcode: td_via_url, in the Module Templates</span>
        </div>

        <div class="td-meta-box-row">
            <?php $mb->the_field('td_custom_cat_name'); ?>
            <span class="td-page-o-custom-label">Custom Label:</span>
            <input class="td-input-text-post-settings" type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"/>
            <span class="td-page-o-info">This label will appear on Flex modules/blocks as a category tag. It can also be used as a custom field within the Custom Field shortcode: td_custom_cat_name</span>

        </div>
        <div class="td-meta-box-row">
            <?php $mb->the_field('td_custom_cat_name_url'); ?>
            <span class="td-page-o-custom-label">Custom Label url:</span>
            <input class="td-input-text-post-settings" type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"/>
            <span class="td-page-o-info">Full url for Custom Label. It can also be used as a custom field within the Custom Field shortcode: td_custom_cat_name_url</span>
        </div>

    </div> <!-- /post option general -->

    <!-- post option smart list -->
    <?php if( 'Newsmag' == TD_THEME_NAME || ( 'Newspaper' == TD_THEME_NAME && defined('TD_STANDARD_PACK') ) ) { ?>
        <div class="td-page-option-panel td-page-option-post-smart-list">
                <!-- smart list -->
                <div class="td-meta-box-row">
                    <span class="td-page-o-custom-label">
                        Use a smart list? :
                        <?php
                        td_util::tooltip_html('
                            <h3>Smart Lists:</h3>
                            <p>Using <i>Smart lists</i> you can transform your article in a list of items. Each item must have a title, an image and a description</p>
                            <p>How to make an item:</p>
                            <ul>
                                <li><strong>add a text wrapped in H3</strong> - this will be the title of the item</li>
                                <li><strong>add any picture</strong> from the media library</li>
                                <li>in a new paragraph below the picture, <strong>add some text</strong></li>
                                <li><i>repeat the last 3 steps for each item that you want to add</i></li>
                            </ul>
    
                            <p>The system will use the H3 from the tiles to split your article and make each individual slide or numbered item</p>
                        ', 'right')
                        ?>
                    </span>

                    <div class="td-inline-block-wrap">
                        <?php
                        echo td_panel_generator::visual_select_o(array(
                            'ds' => 'td_post_theme_settings',
                            'item_id' => '',
                            'option_id' => 'smart_list_template',
                            'values' => td_api_smart_list::_helper_td_smart_list_api_to_panel_values(TDC_URL_LEGACY),
                            'selected_value' => $mb->get_the_value('smart_list_template')
                        ));
                        ?>
                    </div>
                </div>

                <!-- title tag -->
                <div class="td-meta-box-row">
                    <span class="td-page-o-custom-label">
                        Title tags:
                        <?php
                        td_util::tooltip_html('
                            <h3>Smart lists title tags:</h3>
                            <p>Customize what tag is used for <i>Title</i> lookup. This setting is useful if for example, you already have articles that use H2 for items</p>
                        ', 'right')
                        ?>
                    </span>
                    <?php $mb->the_field('td_smart_list_h'); ?>
                    <div class="td-select-style-overwrite td-inline-block-wrap">
                        <select name="<?php $mb->the_name(); ?>" class="td-panel-dropdown">
                            <option value="h1"<?php $mb->the_select_state('h1'); ?>>Heading 1 ( H1 tag )</option>
                            <option value="h2"<?php $mb->the_select_state('h2'); ?>>Heading 2 ( H2 tag )</option>
                            <option value="" <?php $mb->the_select_state(''); ?>>Heading 3 ( H3 tag )</option>
                            <option value="h4"<?php $mb->the_select_state('h4'); ?>>Heading 4 ( H4 tag )</option>
                            <option value="h5"<?php $mb->the_select_state('h5'); ?>>Heading 5 ( H5 tag )</option>
                            <option value="h6"<?php $mb->the_select_state('h6'); ?>>Heading 6 ( H6 tag )</option>
                        </select>
                    </div>
                    <span class="td-page-o-info">The tags that wrap the title of each Smart List item.</span>
                </div>

                <!-- smart list numbering -->
                <div class="td-meta-box-row">
                    <span class="td-page-o-custom-label">
                        Smart list numbering:
                        <?php
                        td_util::tooltip('Change the sort order of the items', 'right')
                        ?>
                    </span>
                    <?php $mb->the_field('td_smart_list_order'); ?>
                    <div class="td-select-style-overwrite td-inline-block-wrap">
                        <select name="<?php $mb->the_name(); ?>" class="td-panel-dropdown">
                            <option value=""<?php $mb->the_select_state(''); ?>>Descending (ex: 3, 2, 1)</option>
                            <option value="asc_1" <?php $mb->the_select_state('asc_1'); ?>>Ascending (ex: 1, 2, 3)</option>
                        </select>
                    </div>
                    <span class="td-page-o-info">The smart lists put a number on each item, select the counting method.</span>
                </div>
        </div> <!-- /post option smart list -->
    <?php } ?>

    <!-- post option review -->
    <div class="td-page-option-panel td-page-option-post-review">

        <div class="td-meta-box-row">
            <span class="td-page-o-custom-label">Is this a product review?</span>
            <?php $mb->the_field('has_review'); ?>
            <div class="td-select-style-overwrite td-inline-block-wrap">
                <select id="reviewSelector" name="<?php $mb->the_name(); ?>" class="td-panel-dropdown">
                    <option value="">No</option>
                    <option value="rate_stars"<?php $mb->the_select_state('rate_stars'); ?>>Stars</option>
                    <option value="rate_percent"<?php $mb->the_select_state('rate_percent'); ?>>Percentages</option>
                    <option value="rate_point"<?php $mb->the_select_state('rate_point'); ?>>Points</option>
                </select>
            </div>
        </div>

        <div class="rating_type rate_Stars">
            <p>
                <strong>Add star ratings for this product:</strong><br>
            </p>

            <?php while($mb->have_fields_and_multi('p_review_stars')): ?>
                <div class="td-meta-box-row">
                    <?php $mb->the_group_open(); ?>

                    <?php $mb->the_field('desc'); ?>
                    <span class="td-page-o-custom-label">Feature name:</span>
                    <input id="input-name-stars" style="width: 200px;" type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"/>

                    <?php $mb->the_field('rate'); ?>

                    <select name="<?php $mb->the_name(); ?>">
                        <option value="">Select rating</option>
                        <option value="5"<?php $mb->the_select_state('5'); ?>>5 stars</option>
                        <option value="4.5"<?php $mb->the_select_state('4.5'); ?>>4.5 stars</option>
                        <option value="4"<?php $mb->the_select_state('4'); ?>>4 stars</option>
                        <option value="3.5"<?php $mb->the_select_state('3.5'); ?>>3.5 stars</option>
                        <option value="3"<?php $mb->the_select_state('3'); ?>>3 stars</option>
                        <option value="2.5"<?php $mb->the_select_state('2.5'); ?>>2.5 stars</option>
                        <option value="2"<?php $mb->the_select_state('2'); ?>>2 stars</option>
                        <option value="1.5"<?php $mb->the_select_state('1.5'); ?>>1.5 stars</option>
                        <option value="1"<?php $mb->the_select_state('1'); ?>>1 stars</option>
                        <option value="0.5"<?php $mb->the_select_state('0.5'); ?>>0.5 stars</option>
                    </select>
                    <a href="#" class="dodelete button">Delete</a>

                    <?php $mb->the_group_close(); ?>
                </div>
            <?php endwhile; ?>

            <p><a href="#" class="docopy-p_review_stars button">Add rating category</a></p>
        </div>

        <div class="rating_type rate_Percentages">
            <p>
                <strong>Add percent ratings for this product:</strong><br>
                <strong>Note:</strong> The percent range is between 0 and 100 (do not add the %)
            </p>
            <?php while($mb->have_fields_and_multi('p_review_percents')): ?>
                <div class="td-meta-box-row">
                    <?php $mb->the_group_open(); ?>

                    <?php $mb->the_field('desc'); ?>
                    <span class="td-page-o-custom-label">Feature name: </span><input id="input-name-percent" style="width: 200px;" type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"/>

                    <?php $mb->the_field('rate'); ?>
                    - Percent:
                    <input style="width: 100px;" type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"/>


                    <a href="#" class="dodelete button">Delete</a>

                    <?php $mb->the_group_close(); ?>
                </div>
            <?php endwhile; ?>

            <p><a href="#" class="docopy-p_review_percents button">Add rating category</a></p>
        </div>

        <div class="rating_type rate_Points">
            <p>
                <strong>Add points ratings for this product:</strong><br>
                <strong>Note:</strong> The points range is between 1 and 10
            </p>
            <?php while($mb->have_fields_and_multi('p_review_points')): ?>
                <div class="td-meta-box-row">
                    <?php $mb->the_group_open(); ?>

                    <?php $mb->the_field('desc'); ?>
                    <span class="td-page-o-custom-label">Feature name: </span><input id="input-name-points" style="width: 200px;" type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"/>

                    <?php $mb->the_field('rate'); ?>
                    - Points:
                    <input style="width: 100px;" type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"/>


                    <a href="#" class="dodelete button">Delete</a>

                    <?php $mb->the_group_close(); ?>
                </div>
            <?php endwhile; ?>

            <p><a href="#" class="docopy-p_review_points button">Add rating category</a></p>
        </div>

        <div class="review_desc">
            <div><strong>Review description:</strong></div>
            <p class="td_help_section">
                <?php $mb->the_field('review'); ?>

                <textarea class="td-textarea-subtitle" type="text" name="<?php $mb->the_name(); ?>"><?php $mb->the_value(); ?></textarea>
            </p>
        </div>

        <script>
            jQuery().ready(function() {
                td_updateMetaboxes();

                jQuery('#reviewSelector').change(function() {
                    td_updateMetaboxes();
                });

                function td_updateMetaboxes() {
                    var cur_selection = jQuery('#reviewSelector option:selected').text();
                    var inputFields = {
                        'Stars': '#input-name-stars',
                        'Percentages': '#input-name-percent',
                        'Points': '#input-name-points'
                    };

                    if(cur_selection.indexOf("No") !== -1) {
                        //remove "required" from all feature name inputs in the reviews
                        jQuery.each(inputFields, function(selection, inputField) {
                            jQuery(inputField).removeAttr('required');
                        });

                        //alert('ra');
                        jQuery('.rating_type').hide();
                        jQuery('.review_desc').hide();

                    } else {
                        //make "Feature name" required for reviews
                        jQuery.each(inputFields, function(selection, inputField) {
                            if (cur_selection === selection) {
                                jQuery(inputField).prop('required', true);
                            } else {
                                jQuery(inputField).removeAttr('required');
                            }
                        });
                        jQuery('.rating_type').hide();
                        jQuery('.rate_' + cur_selection).show();
                        jQuery('.review_desc').show();
                        //alert(cur_selection);
                    }



                }
            }); //end on load
        </script>

    </div> <!-- /post option review -->

    <!-- post autoload options -->
    <div class="td-page-option-panel td-page-option-post-autoload-options">

        <div class="td-meta-box-row">
            <?php $mb->the_field('td_autoload_tag'); ?>
            <span class="td-page-o-custom-label">
                Autoload tag:
                <?php
                td_util::tooltip_html('
                    <h3>How autoload tag works:</h3>
                    
                    <ol>
                        <li>In this field set one of the post tags(use the tag slug) to be used to automatically load next posts.</li>
                        <li>On the Single Cloud Template used for this post edit screen <i>(Edit with TagDiv Composer)</i>, on top right you\'ll find the <i>Single Template Settings</i>, from there:
                            <ul>
                                <li>Enable auto loading.</li>
                                <li>Set the <b>Auto loading type</b> to <b>Post autoload tag</b> option.</li>
                            </ul>
                        </li>
                    </ol>
                    
                    <p>Please note that if no tag is set here or it\'s not a valid tag and <b>Post autoload tag</b> option is set on template\'s settings, autoload will try to load next posts related to all tags found on the post.</p>
                    ', 'right'
                );
                ?>
            </span>
            <input class="td-input-text-post-settings" type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"/>
            <span class="td-page-o-info">This post tag will be used to autoload next posts.</span>
        </div>

    </div> <!-- /post option autoload -->

    <!-- post settings from filters -->
	<?php

	if ( !empty($td_post_settings_tabs) && is_array($td_post_settings_tabs) ) {

		foreach ( $td_post_settings_tabs as $tab ) {

			// tabs id/name/file are required
			if ( !isset( $tab['id'], $tab['name'], $tab['file'] ) )
				continue;
			?>

            <div class="td-page-option-panel td-page-option-post-<?php echo $tab['id'] ?>">
	            <?php require_once( $tab['file'] ); ?>
            </div> <!-- /post option <?php echo $tab['id'] ?> -->

			<?php
		}

	}

	?>

</div>
