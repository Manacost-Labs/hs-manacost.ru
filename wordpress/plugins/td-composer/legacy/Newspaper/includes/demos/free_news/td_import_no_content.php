<?php


/*  ----------------------------------------------------------------------------
	SUBSCRIPTION - start phase 1
*/
global $wpdb;

$disable_wizard = $wpdb->get_var("SELECT value FROM tds_options WHERE name = 'disable_wizard'");
if ( empty($disable_wizard) ) {

    td_demo_subscription::add_account_details([
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
    ]);

    td_demo_subscription::add_payment_bank([
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
    ]);

    td_demo_subscription::add_option([
        'name' => 'td_demo_content',
        'value' => '1',
    ]);

}

$plan_monthly_plan_id = td_demo_subscription::add_plan([
    'name' => 'Monthly Plan',
    'price' => '19.99',
    'interval' => 'month',
    'interval_count' => 1,
    'trial_days' => '0',
    'is_free' => '0',
    'is_unlimited' => '0',
    'is_with_credits' => '0',
    'credits' => '',
    'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:14:"2660cf571d001c";}',
    'publishing_limits' => 'a:0:{}',
    'automatic_delistings' => 'a:0:{}',
]);

$plan_yearly_plan_id = td_demo_subscription::add_plan([
    'name' => 'Yearly Plan',
    'price' => '199',
    'interval' => 'year',
    'interval_count' => 1,
    'trial_days' => '0',
    'is_free' => '0',
    'is_unlimited' => '0',
    'is_with_credits' => '0',
    'credits' => '',
    'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"28660cf571d009e";}',
    'publishing_limits' => 'a:0:{}',
    'automatic_delistings' => 'a:0:{}',
]);

$plan_10_credits_id = td_demo_subscription::add_plan([
    'name' => '10 Credits',
    'price' => '10',
    'interval' => '',
    'interval_count' => 0,
    'trial_days' => '0',
    'is_free' => '0',
    'is_unlimited' => '0',
    'is_with_credits' => '1',
    'credits' => '10',
    'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"55660cf571d00ff";}',
    'publishing_limits' => 'a:0:{}',
    'automatic_delistings' => 'a:0:{}',
]);

$page_payment_page_id_id = td_demo_content::add_page( array(
    'title' => 'Checkout - free_news',
    'file' => 'tds_checkout.txt',
));

td_demo_subscription::add_option([
    'name' => 'payment_page_id',
    'value' => $page_payment_page_id_id,
]);

$page_my_account_page_id_id = td_demo_content::add_page( array(
    'title' => 'My Account - free_news',
    'file' => 'tds_my_account.txt',
));

td_demo_subscription::add_option([
    'name' => 'my_account_page_id',
    'value' => $page_my_account_page_id_id,
]);

$page_create_account_page_id_id = td_demo_content::add_page( array(
    'title' => 'Login/Register - free_news',
    'file' => 'tds_login_register.txt',
));

td_demo_subscription::add_option([
    'name' => 'create_account_page_id',
    'value' => $page_create_account_page_id_id,
]);

td_demo_subscription::add_option([
    'name' => 'go_wizard',
    'value' => '1',
]);

td_demo_subscription::add_option([
    'name' => 'wizard_company_complete',
    'value' => '1',
]);

td_demo_subscription::add_option([
    'name' => 'wizard_payments_complete',
    'value' => '1',
]);

td_demo_subscription::add_option([
    'name' => 'wizard_plans_complete',
    'value' => '1',
]);

td_demo_subscription::add_option([
    'name' => 'wizard_locker_complete',
    'value' => '1',
]);

td_demo_subscription::add_option([
    'name' => 'disable_wizard',
    'value' => '1',
]);

td_demo_subscription::add_option([
    'name' => 'credits_settings',
    'value' => 'a:1:{i:0;a:2:{s:9:"post_type";s:4:"post";s:15:"default_credits";s:1:"2";}}',
]);


/*  ----------------------------------------------------------------------------
	SUBSCRIPTION - end phase 1
*/
/*  ----------------------------------------------------------------------------
	PAGES
*/
$page_my_articles_id = td_demo_content::add_page( array(
    'title' => 'My Articles',
    'file' => 'my_articles.txt',
    'demo_unique_id' => '14660cf571ed6e4',
));

$page_tds_switching_plans_wizard_id = td_demo_content::add_page( array(
    'title' => 'Tds switching plans wizard',
    'file' => 'tds_switching_plans_wizard.txt',
    'demo_unique_id' => '35660cf571edb2a',
));

$page_modal_mobile_id = td_demo_content::add_page( array(
    'title' => 'Modal Mobile',
    'file' => 'modal_mobile.txt',
    'demo_unique_id' => '38660cf571eded1',
));

$page_modal_desktop_id = td_demo_content::add_page( array(
    'title' => 'Modal Desktop',
    'file' => 'modal_desktop.txt',
    'demo_unique_id' => '8660cf571ee2ee',
));

$page_home_id = td_demo_content::add_page( array(
    'title' => 'Home',
    'file' => 'home.txt',
    'homepage' => true,
    'demo_unique_id' => '47660cf571eeb21',
));


/*  ----------------------------------------------------------------------------
	SUBSCRIPTION - start phase 2
*/

/*  ----------------------------------------------------------------------------
	LOCKERS
*/
// add locker
$post_tds_default_wizard_locker_id = td_demo_content::add_post([
    'post_type' => 'tds_locker',
    'title' => 'Wizard Locker (default)',
    'file' => '',
    'categories_id_array' => [],
    'tds_locker_settings' => [
        'tds_title' => 'Subscription required',
        'tds_message' => 'Please subscribe to gain full access to all our articles.',
        'tds_submit_btn_text' => 'Subscribe',
        'tds_locker_cf_1_name' => 'Custom field 1',
        'tds_locker_cf_2_name' => 'Custom field 2',
        'tds_locker_cf_3_name' => 'Custom field 3',
        'tds_locker_credits_title' => 'Buy this article',
        'tds_locker_credits_message' => 'Unlock this article and gain access to read it. <br> Unlock credits cost: <b>%unlock_credits_cost%</b><br> <span class=\'tds-not-logged-hide\'>Available credits: <b>%available_credits%</b><span>',
        'tds_locker_credits_btn_text' => 'Buy article',
    ],
    'tds_payable' => 'paid_subscription',
    'tds_paid_subs_page_id' => $page_tds_switching_plans_wizard_id,
    'tds_paid_subs_plan_ids' => [ $plan_monthly_plan_id,$plan_yearly_plan_id ],
    'tds_locker_credits_unlock' => '1',
    'tds_locker_styles' => [
        'tds_bg_color' => '#f1f4f7',
        'all_tds_border' => '1',
        'all_tds_border_color' => '#b1b4bc',
        'tds_title_color' => '#131f49',
        'tds_message_color' => '#131f49',
        'tds_submit_btn_text_color' => '#ffffff',
        'tds_submit_btn_text_color_h' => '#ffffff',
        'tds_submit_btn_bg_color' => '#e52e2e',
        'tds_submit_btn_bg_color_h' => '#ff4a4a',
        'tds_after_btn_text_color' => '#131f49',
        'tds_pp_msg_color' => '#131f49',
        'tds_pp_msg_links_color' => '#e52e2e',
        'tds_pp_msg_links_color_h' => '#ff4a4a',
        'tds_general_font_family' => 'global-font-1_global',
        'tds_title_font_size' => '26',
        'tds_title_font_line_height' => '1.2',
        'tds_title_font_weight' => '700',
        'tds_message_font_size' => '14',
        'tds_message_font_line_height' => '1.5',
        'tds_submit_btn_text_font_size' => '14',
        'tds_submit_btn_text_font_line_height' => '1.2',
        'tds_submit_btn_text_font_weight' => '500',
    ],
]);

td_util::update_option(
    'tds_demo_options',
    'a:1:{s:5:"plans";a:3:{i:0;a:2:{s:9:"unique_id";s:14:"2660cf571d001c";s:4:"name";s:12:"Monthly Plan";}i:1;a:2:{s:9:"unique_id";s:15:"28660cf571d009e";s:4:"name";s:11:"Yearly Plan";}i:2;a:2:{s:9:"unique_id";s:15:"55660cf571d00ff";s:4:"name";s:10:"10 Credits";}}}'
);


/*  ----------------------------------------------------------------------------
	SUBSCRIPTION - end phase 2
*/


/*  ----------------------------------------------------------------------------
	CLOUD TEMPLATES
*/
$template_tag_template_free_news_id = td_demo_content::add_cloud_template( array(
    'title' => 'Tag Template - Free News',
    'file' => 'tag_cloud_template.txt',
    'template_type' => 'tag',
));

td_demo_misc::update_global_tag_template( 'tdb_template_' . $template_tag_template_free_news_id );


$template_date_template_free_news_id = td_demo_content::add_cloud_template( array(
    'title' => 'Date Template - Free News',
    'file' => 'date_cloud_template.txt',
    'template_type' => 'date',
));

td_demo_misc::update_global_date_template( 'tdb_template_' . $template_date_template_free_news_id );


$template_search_template_free_news_id = td_demo_content::add_cloud_template( array(
    'title' => 'Search Template - Free News',
    'file' => 'search_cloud_template.txt',
    'template_type' => 'search',
));

td_demo_misc::update_global_search_template( 'tdb_template_' . $template_search_template_free_news_id );


$template_author_template_free_news_id = td_demo_content::add_cloud_template( array(
    'title' => 'Author Template - Free News',
    'file' => 'author_cloud_template.txt',
    'template_type' => 'author',
));

td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_free_news_id );


$template_404_template_free_news_id = td_demo_content::add_cloud_template( array(
    'title' => '404 Template - Free News',
    'file' => '404_cloud_template.txt',
    'template_type' => '404',
));

td_demo_misc::update_global_404_template( 'tdb_template_' . $template_404_template_free_news_id );


$template_category_template_free_news_id = td_demo_content::add_cloud_template( array(
    'title' => 'Category Template - Free News',
    'file' => 'cat_cloud_template.txt',
    'template_type' => 'category',
));

td_demo_misc::update_global_category_template( 'tdb_template_' . $template_category_template_free_news_id );


$template_single_post_template_free_news_id = td_demo_content::add_cloud_template( array(
    'title' => 'Single Post Template - Free News',
    'file' => 'post_cloud_template.txt',
    'template_type' => 'single',
));

td_util::update_option( 'td_default_site_post_template', 'tdb_template_' . $template_single_post_template_free_news_id );


$template_footer_free_news_id = td_demo_content::add_cloud_template( array(
    'title' => 'Footer - Free News',
    'file' => 'footer_free_news_cloud_template.txt',
    'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_free_news_id );


$template_header_template_free_news_id = td_demo_content::add_cloud_template( array(
    'title' => 'Header Template - Free News',
    'file' => 'header_template_free_news_cloud_template.txt',
    'template_type' => 'header',
));

td_demo_misc::update_global_header_template( 'tdb_template_' . $template_header_template_free_news_id );



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

// cloud templates metas
td_demo_content::update_meta( $template_footer_free_news_id, 'tdc_footer_template_id', $template_footer_free_news_id );

td_demo_content::update_meta( $template_header_template_free_news_id, 'tdc_header_template_id', $template_header_template_free_news_id );

// pages metas
td_demo_content::update_meta( $page_home_id, 'tdc_header_template_id', $template_header_template_free_news_id );

td_demo_content::update_meta( $page_home_id, 'tdc_footer_template_id', $template_footer_free_news_id );
