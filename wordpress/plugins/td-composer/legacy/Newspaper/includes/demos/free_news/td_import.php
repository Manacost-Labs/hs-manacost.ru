<?php


/*  ----------------------------------------------------------------------------
	MENUS
*/
$menu_td_demo_custom_menu_id = td_demo_menus::create_menu('td-demo-custom-menu', '');

$menu_td_demo_header_menu_id = td_demo_menus::create_menu('td-demo-header-menu', '');

$menu_td_demo_top_menu_id = td_demo_menus::create_menu('td-demo-top-menu', '');

/*  ---------------------------------------------------------------------------- 
	EXTERNAL PLUGINS DATA IMPORT
*/

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
	CATEGORIES
*/
$cat_business_id = td_demo_category::add_category(array(
	'category_name' => 'Business',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_celebrity_id = td_demo_category::add_category(array(
	'category_name' => 'Celebrity',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_featured_id = td_demo_category::add_category(array(
	'category_name' => 'Featured',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_finance_id = td_demo_category::add_category(array(
	'category_name' => 'Finance',
	'parent_id' => $cat_business_id,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_food_id = td_demo_category::add_category(array(
	'category_name' => 'Food',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_lifestyle_id = td_demo_category::add_category(array(
	'category_name' => 'Lifestyle',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_make_up_id = td_demo_category::add_category(array(
	'category_name' => 'Make-up',
	'parent_id' => $cat_lifestyle_id,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_marketing_id = td_demo_category::add_category(array(
	'category_name' => 'Marketing',
	'parent_id' => $cat_business_id,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_music_id = td_demo_category::add_category(array(
	'category_name' => 'Music',
	'parent_id' => $cat_lifestyle_id,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_politics_id = td_demo_category::add_category(array(
	'category_name' => 'Politics',
	'parent_id' => $cat_business_id,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_strategy_id = td_demo_category::add_category(array(
	'category_name' => 'Strategy',
	'parent_id' => $cat_business_id,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_tech_id = td_demo_category::add_category(array(
	'category_name' => 'Tech',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_travel_id = td_demo_category::add_category(array(
	'category_name' => 'Travel',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_uncategorized_id = td_demo_category::add_category(array(
	'category_name' => 'Uncategorized',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_weird_id = td_demo_category::add_category(array(
	'category_name' => 'Weird',
	'parent_id' => $cat_lifestyle_id,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));


/*  ---------------------------------------------------------------------------- 
	 CLOUD TEMPLATES(MODULES)
*/

/*  ---------------------------------------------------------------------------- 
	ATTACHMENTS
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
	POSTS
*/


$post_td_post_discover_creative_writing_as_a_therapeutic_tool_for_depression_and_anxiety_id = td_demo_content::add_post( array(
    'title' => 'Discover Creative Writing as a Therapeutic Tool for Depression and Anxiety',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_6',
    'tds_locker' => '6',
    'categories_id_array' => array($cat_celebrity_id,$cat_finance_id,$cat_food_id,$cat_make_up_id,$cat_marketing_id,$cat_music_id,$cat_politics_id,$cat_strategy_id,$cat_tech_id,$cat_travel_id,$cat_weird_id,),
));

$post_td_post_travel_the_world_on_a_budget_and_dont_miss_the_most_popular_destinations_id = td_demo_content::add_post( array(
    'title' => 'Travel the World on a Budget and Don\'t Miss the Most Popular Destinations',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_7',
    'tds_locker' => '6',
    'categories_id_array' => array($cat_business_id,$cat_celebrity_id,$cat_finance_id,$cat_food_id,$cat_make_up_id,$cat_marketing_id,$cat_music_id,$cat_politics_id,$cat_strategy_id,$cat_tech_id,$cat_travel_id,$cat_weird_id,),
));

$post_td_post_the_benefits_of_outdoor_activities_proven_in_new_massive_real_world_study_id = td_demo_content::add_post( array(
    'title' => 'The Benefits of Outdoor Activities Proven in New Massive Real World Study',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_8',
    'tds_locker' => '6',
    'categories_id_array' => array($cat_business_id,$cat_celebrity_id,$cat_finance_id,$cat_food_id,$cat_make_up_id,$cat_marketing_id,$cat_music_id,$cat_politics_id,$cat_strategy_id,$cat_tech_id,$cat_travel_id,$cat_weird_id,),
));

$post_td_post_time_management_skills_for_success_will_give_you_50_more_productivity_id = td_demo_content::add_post( array(
    'title' => 'Time Management Skills for Success Will Give You 50% More Productivity',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_9',
    'tds_locker' => '6',
    'categories_id_array' => array($cat_business_id,$cat_celebrity_id,$cat_finance_id,$cat_food_id,$cat_make_up_id,$cat_marketing_id,$cat_music_id,$cat_politics_id,$cat_strategy_id,$cat_tech_id,$cat_travel_id,$cat_weird_id,),
));

$post_td_post_home_automation_for_beginners_smart_devices_you_can_buy_right_now_id = td_demo_content::add_post( array(
    'title' => 'Home Automation for Beginners: Smart Devices You Can Buy Right Now',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_10',
    'tds_locker' => '6',
    'categories_id_array' => array($cat_business_id,$cat_celebrity_id,$cat_finance_id,$cat_food_id,$cat_make_up_id,$cat_marketing_id,$cat_music_id,$cat_politics_id,$cat_strategy_id,$cat_tech_id,$cat_travel_id,$cat_weird_id,),
));

$post_td_post_the_art_of_mindfulness_and_health_new_meaning_and_happiness_in_life_id = td_demo_content::add_post( array(
    'title' => 'The Art of Mindfulness and Health: New Meaning and Happiness in Life',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_1',
    'tds_locker' => '6',
    'categories_id_array' => array($cat_business_id,$cat_celebrity_id,$cat_finance_id,$cat_food_id,$cat_make_up_id,$cat_marketing_id,$cat_music_id,$cat_politics_id,$cat_strategy_id,$cat_tech_id,$cat_travel_id,$cat_weird_id,),
));

$post_td_post_exploring_different_digital_hobbies_in_the_new_virtual_reality_games_id = td_demo_content::add_post( array(
    'title' => 'Exploring Different Digital Hobbies in the New Virtual Reality Games',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_2',
    'tds_locker' => '6',
    'categories_id_array' => array($cat_business_id,$cat_celebrity_id,$cat_finance_id,$cat_food_id,$cat_make_up_id,$cat_marketing_id,$cat_music_id,$cat_politics_id,$cat_strategy_id,$cat_tech_id,$cat_travel_id,$cat_weird_id,),
));

$post_td_post_plant_based_living_a_detailed_guide_what_to_eat_and_what_to_avoid_id = td_demo_content::add_post( array(
    'title' => 'Plant-Based Living: A Detailed Guide: What to Eat and What to Avoid',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_3',
    'tds_locker' => '6',
    'categories_id_array' => array($cat_business_id,$cat_celebrity_id,$cat_finance_id,$cat_food_id,$cat_make_up_id,$cat_marketing_id,$cat_music_id,$cat_politics_id,$cat_strategy_id,$cat_tech_id,$cat_travel_id,$cat_weird_id,),
));

$post_td_post_interior_design_trends_to_watch_out_for_when_planning_a_home_renovation_id = td_demo_content::add_post( array(
    'title' => 'Interior Design Trends to Watch Out for When Planning a Home Renovation',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_4',
    'tds_locker' => '6',
    'categories_id_array' => array($cat_business_id,$cat_celebrity_id,$cat_finance_id,$cat_food_id,$cat_make_up_id,$cat_marketing_id,$cat_music_id,$cat_politics_id,$cat_strategy_id,$cat_tech_id,$cat_travel_id,$cat_weird_id,),
));

$post_td_post_the_minimalism_movement_explained_in_great_detail_by_healh_experts_id = td_demo_content::add_post( array(
    'title' => 'The Minimalism Movement Explained in Great Detail by Healh Experts',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_5',
    'tds_locker' => $post_tds_default_wizard_locker_id,
    'tds_lock_content' => '1',
    'categories_id_array' => array($cat_business_id,$cat_celebrity_id,$cat_finance_id,$cat_food_id,$cat_make_up_id,$cat_marketing_id,$cat_music_id,$cat_politics_id,$cat_strategy_id,$cat_tech_id,$cat_travel_id,$cat_weird_id,),
));

$post_td_post_the_hottest_wearable_tech_and_smart_gadgets_of_the_year_will_amaze_you_id = td_demo_content::add_post( array(
	'title' => 'The Hottest Wearable Tech and Smart Gadgets of the Year Will Amaze You',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_tech_id,),
));

$post_td_post_new_technology_will_help_keep_your_smart_home_from_becoming_obsolete_id = td_demo_content::add_post( array(
	'title' => 'New Technology Will Help Keep Your Smart Home from Becoming Obsolete',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_tech_id,),
));

$post_td_post_apple_computers_climb_the_list_of_the_top_gadgets_in_forbes_magazine_id = td_demo_content::add_post( array(
	'title' => 'Apple Computers Climb the List of the Top Gadgets in Forbes Magazine',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_tech_id,),
));

$post_td_post_new_soundboard_from_bose_review_pricing_is_not_always_the_only_criteria_id = td_demo_content::add_post( array(
	'title' => 'New Soundboard from Bose Review: Pricing is Not Always the Only Criteria',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_tech_id,),
));

$post_td_post_discover_the_newest_waterproof_and_rugged_smartphones_that_come_on_sale_id = td_demo_content::add_post( array(
	'title' => 'Discover the Newest Waterproof and Rugged Smartphones that Come on Sale',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_tech_id,),
));

$post_easy_food_survey_pizza_voted_as_the_most_satisfying_meal_of_the_century_id = td_demo_content::add_post( array(
	'title' => 'Easy Food Survey: Pizza Voted As the Most Satisfying Meal of the Century',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'tds_locker' => '6',
	'categories_id_array' => array($cat_food_id,),
));

$post_td_post_this_week_in_houston_food_blogs_high_protein_recipes_and_low_fat_shakes_id = td_demo_content::add_post( array(
	'title' => 'This Week in Houston Food Blogs: High-Protein Recipes and Low Fat Shakes',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_food_id,),
));

$post_td_post_moroccan_salmon_with_garlic_mayonnaise_is_common_in_southern_spain_id = td_demo_content::add_post( array(
	'title' => 'Moroccan Salmon with Garlic Mayonnaise is Common in Southern Spain',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_food_id,),
));

$post_td_post_best_places_to_get_your_mexican_food_fix_when_you_visit_mexico_city_id = td_demo_content::add_post( array(
	'title' => 'Best Places to Get Your Mexican Food Fix When You Visit Mexico City',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_food_id,),
));

$post_td_post_the_best_pork_kebabs_with_grilled_plums_and_couscous_is_found_in_poland_id = td_demo_content::add_post( array(
	'title' => 'The Best Pork Kebabs With Grilled Plums and Couscous is Found in Poland',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_food_id,),
));

$post_td_post_10_things_you_should_know_before_you_visit_south_americas_jungles_id = td_demo_content::add_post( array(
	'title' => '10 Things You Should Know Before You Visit South America\'s Jungles',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_travel_id,),
));

$post_td_post_ultimate_guide_to_viennas_coffee_renaissance_packed_in_one_weekend_id = td_demo_content::add_post( array(
	'title' => 'Ultimate Guide to Vienna’s Coffee Renaissance Packed in One Weekend',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_travel_id,),
));

$post_td_post_vacation_bucket_list_the_top_10_trips_of_a_lifetime_you_should_take_id = td_demo_content::add_post( array(
	'title' => 'Vacation Bucket List: The Top 10 Trips of a Lifetime You Should Take',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_travel_id,),
));

$post_td_post_the_cliffs_of_moher_reach_1_million_visitors_every_year_since_2014_id = td_demo_content::add_post( array(
	'title' => 'The Cliffs of Moher Reach 1 Million Visitors Every Year Since 2014',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_travel_id,),
));

$post_td_post_the_25_best_cities_you_can_find_in_italy_to_satisfy_the_love_for_history_id = td_demo_content::add_post( array(
	'title' => 'The 25 Best Cities You Can Find in Italy to Satisfy the Love for History',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_travel_id,),
));

$post_td_post_the_car_insurance_catch_that_can_double_your_cover_in_two_months_id = td_demo_content::add_post( array(
	'title' => 'The Car Insurance Catch that can Double Your Cover in Two Months',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_weird_id,),
));

$post_td_post_the_weirdest_places_ashes_have_been_scattered_in_new_zeeland_id = td_demo_content::add_post( array(
	'title' => 'The Weirdest Places Ashes Have Been Scattered in New Zeeland',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_weird_id,),
));

$post_td_post_the_modern_man_study_part_of_the_next_wave_of_superheroes_id = td_demo_content::add_post( array(
	'title' => 'The Modern Man Study: Part of the Next Wave of Superheroes',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_weird_id,),
));

$post_td_post_watch_awesome_kate_halle_go_full_wiming_pro_in_the_bahamas_id = td_demo_content::add_post( array(
	'title' => 'Watch Awesome Kate Halle Go Full Wiming Pro in the Bahamas',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_weird_id,),
));

$post_td_post_silicon_valley_stunned_by_the_fulminant_slashed_investments_id = td_demo_content::add_post( array(
	'title' => 'Silicon Valley Stunned by the Fulminant Slashed Investments',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_weird_id,),
));

$post_td_post_take_a_stroll_through_the_pros_and_cons_of_permanent_eyebrows_tattoos_id = td_demo_content::add_post( array(
	'title' => 'Take a Stroll Through the Pros and Cons of Permanent Eyebrows Tattoos',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_make_up_id,),
));

$post_td_post_10_cool_startups_that_will_change_your_perspective_on_clothes_fashion_id = td_demo_content::add_post( array(
	'title' => '10 Cool Startups that Will Change Your Perspective on Clothes & Fashion',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_make_up_id,),
));

$post_td_post_10_outfits_inspired_by_famous_works_of_art_are_auctioned_in_london_id = td_demo_content::add_post( array(
	'title' => '10 Outfits Inspired by Famous Works of Art are Auctioned in London',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_make_up_id,),
));

$post_td_post_cover_girl_announces_star_shine_makeup_line_is_due_for_next_december_id = td_demo_content::add_post( array(
	'title' => 'Cover Girl Announces Star Shine Makeup Line is Due for Next December',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_make_up_id,),
));

$post_td_post_the_make_up_conference_in_new_york_this_winter_unveils_hot_innovations_id = td_demo_content::add_post( array(
	'title' => 'The Make-up Conference in New York this Winter Unveils Hot Innovations',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_make_up_id,),
));

$post_td_post_kristen_stewart_visits_the_toronto_film_festival_with_new_boyfriend_id = td_demo_content::add_post( array(
	'title' => 'Kristen Stewart Visits the Toronto Film Festival with New Boyfriend',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_celebrity_id,),
));

$post_td_post_celebrity_make_up_artist_gary_meyers_shows_you_his_beauty_tricks_id = td_demo_content::add_post( array(
	'title' => 'Celebrity Make-up Artist Gary Meyers Shows you His Beauty Tricks',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_celebrity_id,),
));

$post_td_post_the_biggest_hollywood_celebrities_visit_the_ranches_of_california_id = td_demo_content::add_post( array(
	'title' => 'The Biggest Hollywood Celebrities Visit the Ranches of California',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_celebrity_id,),
));

$post_td_post_fashion_finder_biggest_shows_parties_and_celebrity_for_new_years_id = td_demo_content::add_post( array(
	'title' => 'Fashion Finder: Biggest Shows, Parties and Celebrity for New Years',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_celebrity_id,),
));

$post_td_post_the_most_popular_celebrity_name_list_of_the_millennium_is_here_id = td_demo_content::add_post( array(
	'title' => 'The Most Popular Celebrity Name List of the Millennium is Here',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_celebrity_id,),
));

$post_td_post_itunes_is_now_the_second_biggest_name_in_music_world_giants_id = td_demo_content::add_post( array(
	'title' => 'iTunes is Now the Second Biggest Name in Music World Giants',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_music_id,),
));

$post_td_post_concert_shows_will_stream_on_netflix_amazon_and_hulu_this_year_id = td_demo_content::add_post( array(
	'title' => 'Concert Shows Will Stream on Netflix, Amazon and Hulu this Year',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_music_id,),
));

$post_td_post_pixar_brings_its_animated_movies_to_life_with_studio_music_id = td_demo_content::add_post( array(
	'title' => 'Pixar Brings it\'s Animated Movies to Life with Studio Music',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'tds_locker' => '6',
	'categories_id_array' => array($cat_music_id,),
));

$post_td_post_for_composer_drew_silva_music_is_all_about_embracing_life_id = td_demo_content::add_post( array(
	'title' => 'For Composer Drew Silva, Music is all About Embracing Life',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_music_id,),
));

$post_td_post_burberry_is_the_first_brand_to_get_an_apple_music_channel_line_id = td_demo_content::add_post( array(
	'title' => 'Burberry is the First Brand to get an Apple Music Channel Line',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_music_id,),
));

$post_td_post_the_politics_behind_maroccos_stock_market_turbulence_last_year_id = td_demo_content::add_post( array(
	'title' => 'The Politics Behind Marocco\'s Stock Market Turbulence Last Year',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_politics_id,),
));

$post_td_post_expanding_peacefull_political_climate_gears_up_for_this_election_id = td_demo_content::add_post( array(
	'title' => 'Expanding Peacefull Political Climate Gears up for this Election',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_politics_id,),
));

$post_td_post_things_you_didnt_know_about_the_american_past_politicians_id = td_demo_content::add_post( array(
	'title' => 'Things You Didn\'t Know About the American Past Politicians',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_politics_id,),
));

$post_td_post_new_presidential_candidates_presented_in_just_a_few_minutes_id = td_demo_content::add_post( array(
	'title' => 'New Presidential Candidates Presented in Just a Few Minutes',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_politics_id,),
));

$post_td_post_sanders_gets_respectful_welcome_at_conservative_college_id = td_demo_content::add_post( array(
	'title' => 'Sanders Gets Respectful Welcome at Conservative College',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_politics_id,),
));

$post_td_post_dell_will_invest_125_billion_in_chinas_tech_in_the_next_5_years_id = td_demo_content::add_post( array(
	'title' => 'Dell Will Invest $125 Billion in China\'s Tech in the Next 5 Years',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_finance_id,),
));

$post_td_post_boxtrade_lands_50_million_in_another_new_funding_round_with_ibm_id = td_demo_content::add_post( array(
	'title' => 'Boxtrade Lands $50 Million in Another New Funding Round with IBM',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_finance_id,),
));

$post_td_post_a_look_at_how_social_media_mobile_gaming_can_increase_sales_id = td_demo_content::add_post( array(
	'title' => 'A Look at How Social Media & Mobile Gaming Can Increase Sales',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_finance_id,),
));

$post_td_post_the_secret_to_your_companys_financial_health_is_very_important_id = td_demo_content::add_post( array(
	'title' => 'The Secret to Your Company\'s Financial Health is Very Important',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_finance_id,),
));

$post_td_post_things_to_look_for_in_a_financial_trading_platform_environment_id = td_demo_content::add_post( array(
	'title' => 'Things to Look For in a Financial Trading Platform Environment',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_finance_id,),
));

$post_td_post_customer_engagement_marketing_a_new_strategy_for_the_economy_id = td_demo_content::add_post( array(
	'title' => 'Customer Engagement Marketing: A New Strategy for the Economy',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_marketing_id,),
));

$post_td_post_social_media_marketing_for_franchises_is_meant_for_women_id = td_demo_content::add_post( array(
	'title' => 'Social Media Marketing for Franchises is Meant for Women',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_marketing_id,),
));

$post_td_post_mobile_marketing_is_said_to_be_the_future_of_e_commerce_id = td_demo_content::add_post( array(
	'title' => 'Mobile Marketing is Said to Be the Future of E-Commerce',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_marketing_id,),
));

$post_td_post_entrepreneurial_advertising_the_future_of_marketing_id = td_demo_content::add_post( array(
	'title' => 'Entrepreneurial Advertising: The Future Of Marketing',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_marketing_id,),
));

$post_td_post_how_mary_reagan_gave_glamour_and_class_to_the_elites_society_id = td_demo_content::add_post( array(
	'title' => 'How Mary Reagan Gave Glamour and Class to the Elites Society',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_marketing_id,),
));

$post_td_post_the_definitive_guide_to_marketing_your_business_on_instagram_id = td_demo_content::add_post( array(
	'title' => 'The Definitive Guide To Marketing Your Business On Instagram',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_strategy_id,),
));

$post_td_post_olimpic_athlete_reads_donald_trumps_mean_tweets_on_kimmel_id = td_demo_content::add_post( array(
	'title' => 'Olimpic Athlete Reads Donald Trump\'s Mean Tweets on Kimmel',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_strategy_id,),
));

$post_td_post_kansas_city_has_a_massive_array_of_big_national_companies_id = td_demo_content::add_post( array(
	'title' => 'Kansas City Has a Massive Array of Big National Companies',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_strategy_id,),
));

$post_td_post_program_will_lend_10m_to_detroit_minority_businesses_id = td_demo_content::add_post( array(
	'title' => 'Program Will Lend $10M to Detroit Minority Businesses',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_strategy_id,),
));

$post_td_post_now_is_the_time_to_think_about_your_small_business_success_id = td_demo_content::add_post( array(
	'title' => 'Now Is the Time to Think About Your Small-Business Success',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_strategy_id,),
));


/*  ---------------------------------------------------------------------------- 
	PRODUCTS
*/

/*  ---------------------------------------------------------------------------- 
	TAXONOMIES
*/

/*  ---------------------------------------------------------------------------- 
	CPTs
*/



/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_category( array(
	'title' => 'Celebrity',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'category_id' => $cat_celebrity_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_category( array(
	'title' => 'Make-up',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'category_id' => $cat_make_up_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_category( array(
	'title' => 'Music',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'category_id' => $cat_music_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_category( array(
	'title' => 'Weird',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'category_id' => $cat_weird_id,
	'parent_id' => ''
));



/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_link( array(
	'title' => 'Home',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'url' => 'http://localhost/wp_011_free_news/',
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Business',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_business_id,
	'parent_id' => ''
), true );

$menu_item_2_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Lifestyle',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_lifestyle_id,
	'parent_id' => ''
), true );

$menu_item_3_id = td_demo_menus::add_category( array(
	'title' => 'Travel',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_travel_id,
	'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_category( array(
	'title' => 'Food',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_food_id,
	'parent_id' => ''
));

$menu_item_5_id = td_demo_menus::add_category( array(
	'title' => 'Tech',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_tech_id,
	'parent_id' => ''
));



/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_link( array(
	'title' => 'About us',
	'add_to_menu_id' => $menu_td_demo_top_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_link( array(
	'title' => 'Contact',
	'add_to_menu_id' => $menu_td_demo_top_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_link( array(
	'title' => 'Careers',
	'add_to_menu_id' => $menu_td_demo_top_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_link( array(
	'title' => 'Privacy Policy',
	'add_to_menu_id' => $menu_td_demo_top_menu_id,
	'url' => '#',
	'parent_id' => ''
));


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
