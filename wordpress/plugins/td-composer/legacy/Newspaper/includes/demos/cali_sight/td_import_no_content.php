<?php



/*  ----------------------------------------------------------------------------
	EXTERNAL PLUGINS DATA IMPORT
*/
/* -- ACF -- */
// Field groups
$field_group_tourism_fields = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/cali_sight/data/acf/group_640ee0d8bf2de.json' );

// Post types
$post_type_tourism = td_demo_data::acf_import_post_type( 'https://cloud.tagdiv.com/demos/Newspaper/cali_sight/data/acf/post_type_651148dd73920.json' );

// Taxonomies
$taxonomy_attractions = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/cali_sight/data/acf/taxonomy_6511491ac9b60.json' );



/*  ----------------------------------------------------------------------------
	SUBSCRIPTION - start phase 1
*/
global $wpdb;
$disable_wizard = $wpdb->get_var( "SELECT value FROM tds_options WHERE name = 'disable_wizard'");
if ( empty($disable_wizard) ) {

    td_demo_subscription::add_account_details( array(
            'company_name' => 'Demo Company',
            'billing_cui' => '75864589',
            'billing_j' => '10/120/2021',
            'billing_address' => '2656 Farm Meadow Drive',
            'billing_city' => 'Tucson',
            'billing_country' => 'Arizona',
            'billing_email' => 'yourcompany@website.com',
            'billing_bank_account' => 'NL43INGB4186520410',
            'billing_post_code' => '85712',
            'billing_vat_number' => '75864589',
            'options' => 'a:1:{s:15:"td_demo_content";i:1;}',
        )
    );

    td_demo_subscription::add_payment_bank( array(
            'account_name' => 'Alpha Bank Account',
            'account_number' => '123456',
            'bank_name' => 'Alpha Bank',
            'routing_number' => '123456',
            'iban' => 'NL43INGB4186520410',
            'bic_swift' => '123456',
            'description' => 'Make your payment directly into our bank account. Please use your Subscription ID as the payment reference. Your subscription will be activated when the funds are cleared in our account.',
            'instruction' => 'Payment method instructions go here.',
            'is_active' => '1',
            'options' => 'a:1:{s:15:"td_demo_content";i:1;}',
        )
    );

    td_demo_subscription::add_option( array(
            'name' => 'td_demo_content',
            'value' => '1',
        )
    );

}

$plan_monthly_plan_id = td_demo_subscription::add_plan( array(
        'name' => 'Monthly Plan',
        'price' => '10',
        'months_in_cycle' => '1',
        'trial_days' => '0',
        'is_free' => '0',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"61645b8c06d67f2";}',
    )
);

$plan_free_plan_id = td_demo_subscription::add_plan( array(
        'name' => 'Free Plan',
        'price' => '',
        'months_in_cycle' => '',
        'trial_days' => '0',
        'is_free' => '1',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"30645b8c06d68f4";}',
    )
);

$plan_yearly_plan_id = td_demo_subscription::add_plan( array(
        'name' => 'Yearly Plan',
        'price' => '100',
        'months_in_cycle' => '12',
        'trial_days' => '0',
        'is_free' => '0',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"67645b8c06d697b";}',
    )
);

$page_payment_page_id_id = td_demo_content::add_page( array(
    'title' => 'Checkout - cali_sight',
    'file' => 'tds_checkout.txt',
));

td_demo_subscription::add_option( array(
        'name' => 'payment_page_id',
        'value' => $page_payment_page_id_id,
    )
);

$page_my_account_page_id_id = td_demo_content::add_page( array(
    'title' => 'My Account - cali_sight',
    'file' => 'tds_my_account.txt',
));

td_demo_subscription::add_option( array(
        'name' => 'my_account_page_id',
        'value' => $page_my_account_page_id_id,
    )
);

$page_create_account_page_id_id = td_demo_content::add_page( array(
    'title' => 'Login/Register - cali_sight',
    'file' => 'tds_login_register.txt',
));

td_demo_subscription::add_option( array(
        'name' => 'create_account_page_id',
        'value' => $page_create_account_page_id_id,
    )
);

td_demo_subscription::add_option( array(
        'name' => 'go_wizard',
        'value' => '1',
    )
);

td_demo_subscription::add_option( array(
        'name' => 'wizard_company_complete',
        'value' => '1',
    )
);

td_demo_subscription::add_option( array(
        'name' => 'wizard_payments_complete',
        'value' => '1',
    )
);

td_demo_subscription::add_option( array(
        'name' => 'wizard_plans_complete',
        'value' => '1',
    )
);

td_demo_subscription::add_option( array(
        'name' => 'wizard_locker_complete',
        'value' => '1',
    )
);

td_demo_subscription::add_option( array(
        'name' => 'disable_wizard',
        'value' => '1',
    )
);


/*  ----------------------------------------------------------------------------
	SUBSCRIPTION - end phase 1
*/

/*  ----------------------------------------------------------------------------
	 CLOUD TEMPLATES(MODULES)
*/
$template_module_template_cs_small_img_id = td_demo_content::add_cloud_template( array(
    'title' => 'CS Small Image – Module Template',
    'file' => 'module_template_cs_small_img_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '1106',
));

$template_cs_background_module_template_small_id = td_demo_content::add_cloud_template( array(
    'title' => 'CS Background Small – Module Template',
    'file' => 'cs_background_module_template_small_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '1057',
));

$template_module_template_cs_small_id = td_demo_content::add_cloud_template( array(
    'title' => 'CS Small – Module Template',
    'file' => 'module_template_cs_small_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '803',
));

$template_module_template_cs_border_standard_id = td_demo_content::add_cloud_template( array(
    'title' => 'CS Standard Border – Module Template',
    'file' => 'module_template_cs_border_standard_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '695',
));

$template_module_template_border_style_id = td_demo_content::add_cloud_template( array(
    'title' => 'CS Border Style – Module Template',
    'file' => 'module_template_border_style_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '637',
));

$template_module_template_id = td_demo_content::add_cloud_template( array(
    'title' => 'CS Background – Module Template',
    'file' => 'module_template_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '596',
));

$template_module_template_cs_buttons_id = td_demo_content::add_cloud_template( array(
    'title' => 'CS Buttons – Module Template',
    'file' => 'module_template_cs_buttons_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '576',
));


/*  ----------------------------------------------------------------------------
	PAGES
*/
$page_cali_sight_mobile_menu_id = td_demo_content::add_page( array(
    'title' => 'Cali Sight Mobile Menu',
    'file' => 'cali_sight_mobile_menu.txt',
    'demo_unique_id' => '67645b8c072f82b',
));

$page_calisight_modal_search_menu_id = td_demo_content::add_page( array(
    'title' => 'Cali Sight Modal Search Menu',
    'file' => 'calisight_modal_search_menu.txt',
    'demo_unique_id' => '23645b8c072fcb0',
));

$page_cali_sight_reviews_id = td_demo_content::add_page( array(
    'title' => 'Cali Sight Reviews',
    'file' => 'cali_sight_reviews.txt',
    'demo_unique_id' => '28645b8c07300b7',
));

$page_tds_switching_plans_wizard_id = td_demo_content::add_page( array(
    'title' => 'Switching plans wizard',
    'file' => 'tds_switching_plans_wizard.txt',
    'demo_unique_id' => '94645b8c073059b',
));

$page_homepage_id = td_demo_content::add_page( array(
    'title' => 'Homepage',
    'file' => 'homepage.txt',
    'homepage' => true,
    'demo_unique_id' => '32645b8c0730e16',
));

$page_cali_sight_posts_list_id = td_demo_content::add_page( array(
    'title' => 'Cali Sight Posts List',
    'file' => 'cali_sight_posts_list.txt',
    'demo_unique_id' => '90645b8c0731466',
));

$page_calisight_post_your_listing_id = td_demo_content::add_page( array(
    'title' => 'Cali Sight Post your Listing',
    'file' => 'calisight_post_your_listing.txt',
    'demo_unique_id' => '59645b8c0731ca1',
));



/*  ----------------------------------------------------------------------------
	SUBSCRIPTION - start phase 2
*/

/*  ----------------------------------------------------------------------------
	SUBSCRIPTIONS
*/
// add locker
$post_tds_default_wizard_locker_id = td_demo_content::add_post( array(
        'post_type' => 'tds_locker',
        'title' => 'Wizard Locker (default)',
        'file' => '',
        'categories_id_array' => [],
        'tds_locker_settings' => array(
            'tds_title' => 'Locked',
            'tds_message' => 'Please subscribe to unlock this content.',
            'tds_submit_btn_text' => 'Sign Up',
            'tds_pp_msg' => 'I consent to processing of my data according to the GDPR rules and guidelines. Moreover, I agree to the <a href=\"#\">Terms of Use</a> & <a href=\"#\">Privacy Policy</a>.',
            'tds_locker_cf_1_name' => 'Custom field 1',
            'tds_locker_cf_2_name' => 'Custom field 2',
            'tds_locker_cf_3_name' => 'Custom field 3',
        ),
        'tds_payable' => 'paid_subscription',
        'tds_paid_subs_page_id' => $page_tds_switching_plans_wizard_id,
        'tds_paid_subs_plan_ids' => [$plan_monthly_plan_id,$plan_free_plan_id,$plan_yearly_plan_id],
        'tds_locker_styles' => array(
            'tds_bg_color' => '#f8f5f4',
            'tds_title_color' => '#0d1f2d',
            'tds_message_color' => '#0d1f2d',
            'tds_submit_btn_text_color' => '#ffffff',
            'tds_submit_btn_text_color_h' => '#ffffff',
            'tds_submit_btn_bg_color' => '#2639e2',
            'tds_submit_btn_bg_color_h' => '#1a28a3',
            'tds_after_btn_text_color' => '#a7afb5',
            'tds_pp_checked_color' => '#2639e2',
            'tds_pp_check_bg' => '#f8f5f4',
            'tds_pp_check_bg_f' => '#f8f5f4',
            'tds_pp_check_border_color' => '#2639e2',
            'tds_pp_check_border_color_f' => '#2639e2',
            'tds_pp_msg_color' => '#a7afb5',
            'tds_pp_msg_links_color' => '#2639e2',
            'tds_pp_msg_links_color_h' => '#1a28a3',
            'tds_general_font_family' => 'sans-serif_global',
            'tds_title_font_family' => 'sans-serif_global',
            'tds_title_font_size' => '30',
            'tds_title_font_line_height' => '1.2',
            'tds_title_font_weight' => '700',
            'tds_message_font_family' => 'sans-serif_global',
            'tds_message_font_size' => '16',
            'tds_message_font_line_height' => '1.2',
            'tds_message_font_weight' => '400',
            'tds_submit_btn_text_font_family' => 'sans-serif_global',
            'tds_submit_btn_text_font_size' => '16',
            'tds_submit_btn_text_font_line_height' => '1.2',
            'tds_submit_btn_text_font_weight' => '400',
            'tds_after_btn_text_font_family' => 'sans-serif_global',
            'tds_after_btn_text_font_size' => '14',
            'tds_after_btn_text_font_line_height' => '1.2',
            'tds_after_btn_text_font_weight' => '500',
            'tds_pp_msg_font_family' => 'sans-serif_global',
            'tds_pp_msg_font_size' => '14',
            'tds_pp_msg_font_line_height' => '1.2',
            'tds_pp_msg_font_weight' => '400',
        ),
    )
);

// add post meta for default locker
td_demo_content::add_locker_meta( array(
        'tds_locker_id' => (int) get_option( 'tds_default_locker_id' ),
        'tds_locker_meta' => array(
            'tds_locker_settings' => array(
                'tds_title' => 'This Content Is Only For Subscribers',
                'tds_message' => 'Please subscribe to unlock this content. Enter your email to get access.',
                'tds_input_placeholder' => 'Please enter your email address.',
                'tds_submit_btn_text' => 'Subscribe to unlock',
                'tds_after_btn_text' => 'Your email address is 100% safe from spam!',
                'tds_pp_msg' => 'I consent to processing of my data according to <a href=\"#\">Terms of Use</a> & <a href=\"#\">Privacy Policy</a>',
            ),
        )
    )
);

td_util::update_option('tds_demo_options', 'a:1:{s:5:"plans";a:3:{i:0;a:2:{s:9:"unique_id";s:15:"61645b8c06d67f2";s:4:"name";s:12:"Monthly Plan";}i:1;a:2:{s:9:"unique_id";s:15:"30645b8c06d68f4";s:4:"name";s:9:"Free Plan";}i:2;a:2:{s:9:"unique_id";s:15:"67645b8c06d697b";s:4:"name";s:11:"Yearly Plan";}}}');


/*  ----------------------------------------------------------------------------
	SUBSCRIPTION - end phase 2
*/


/*  ----------------------------------------------------------------------------
	CLOUD TEMPLATES
*/
$template_custom_taxonomy_global_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cali Sight  - Custom Taxonomy Global',
    'file' => 'cpt_tax_cloud_template.txt',
    'template_type' => 'cpt_tax',
));

td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_global_id, 'tdtax_attractions' );


td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_global_id, 'tdc-review-criteria' );


$template_search_template_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cali Sight  - Search Template',
    'file' => 'search_cloud_template.txt',
    'template_type' => 'search',
));

td_demo_misc::update_global_search_template( 'tdb_template_' . $template_search_template_id);


$template_tag_template_calisight_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cali Sight - Tag Template',
    'file' => 'tag_cloud_template.txt',
    'template_type' => 'tag',
));

td_demo_misc::update_global_tag_template( 'tdb_template_' . $template_tag_template_calisight_id);


$template_date_template_calisight_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cali Sight - Date Template',
    'file' => 'date_cloud_template.txt',
    'template_type' => 'date',
));

td_demo_misc::update_global_date_template( 'tdb_template_' . $template_date_template_calisight_id);


$template_author_template_calisight_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cali Sight - Author Template',
    'file' => 'author_cloud_template.txt',
    'template_type' => 'author',
));

td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_calisight_id);


$template_category_template_calisight_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cali Sight - Category Template',
    'file' => 'cat_cloud_template.txt',
    'template_type' => 'category',
));

td_demo_misc::update_global_category_template( 'tdb_template_' . $template_category_template_calisight_id);


$template_404_template_calisight_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cali Sight - 404 Template',
    'file' => '404_cloud_template.txt',
    'template_type' => '404',
));

td_demo_misc::update_global_404_template( 'tdb_template_' . $template_404_template_calisight_id);


$template_custom_taxonomy_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cali Sight  - Custom Taxonomy',
    'file' => 'cpt_tax_cloud_template_global.txt',
    'template_type' => 'cpt_tax',
));

$template_single_post_template_calisight_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cali Sight - Single Post Template',
    'file' => 'post_cloud_template.txt',
    'template_type' => 'single',
));

td_util::update_option('td_default_site_post_template', 'tdb_template_' . $template_single_post_template_calisight_id);


$template_custom_post_type_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cali Sight  - Custom Post Type',
    'file' => 'cpt_cloud_template.txt',
    'template_type' => 'cpt',
));

td_demo_misc::update_global_cpt_template( 'tdb_template_' . $template_custom_post_type_id, 'tdcpt_tourism' );


td_demo_misc::update_global_cpt_template( 'tdb_template_' . $template_custom_post_type_id, 'tdc-review' );


$template_footer_template_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cali Sight - Footer Template',
    'file' => 'footer_cloud_template.txt',
    'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_template_id);


$template_header_template_calisight_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cali Sight - Header Template',
    'file' => 'header_cloud_template.txt',
    'template_type' => 'header',
));

td_demo_misc::update_global_header_template( 'tdb_template_' . $template_header_template_calisight_id);



/*  ----------------------------------------------------------------------------
	GENERAL SETTINGS
*/
td_demo_misc::update_background('', false);

td_demo_misc::update_background_mobile('');

td_demo_misc::update_background_login('');

td_demo_misc::update_background_header('');

td_demo_misc::update_background_footer('');

td_demo_misc::update_footer_text('');

td_demo_misc::update_logo(array('normal' => '','retina' => '','mobile' => '',));

td_demo_misc::update_footer_logo(array('normal' => '','retina' => '',));

td_demo_misc::add_social_buttons(array());

$generated_css = td_css_generator();
if ( function_exists('tdsp_css_generator') ) {
    $generated_css .= tdsp_css_generator();
}
td_util::update_option( 'tds_user_compile_css', $generated_css );
