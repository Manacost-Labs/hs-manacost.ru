<?php


/*  ----------------------------------------------------------------------------
	MENUS
*/

$menu_td_demo_footer_menu_extra_id = td_demo_menus::create_menu('td-demo-footer-menu-extra', '');
$menu_td_demo_header_menu_id = td_demo_menus::create_menu('td-demo-header-menu', '');
$menu_td_demo_custom_menu_id = td_demo_menus::create_menu('td-demo-custom-menu', '');
$menu_td_demo_footer_menu_id = td_demo_menus::create_menu('td-demo-footer-menu', '');



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
		'price' => '15',
		'months_in_cycle' => '1',
		'trial_days' => '0',
		'is_free' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"6764fae1a31e0e1";}',
	)
);

$plan_yearly_plan_id = td_demo_subscription::add_plan( array(
		'name' => 'Yearly Plan',
		'price' => '150',
		'months_in_cycle' => '12',
		'trial_days' => '0',
		'is_free' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"9964fae1a31e186";}',
	)
);

$plan_free_plan_id = td_demo_subscription::add_plan( array(
		'name' => 'Free Plan',
		'price' => '',
		'months_in_cycle' => '',
		'trial_days' => '0',
		'is_free' => '1',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:14:"464fae1a31e221";}',
	)
);

$page_payment_page_id_id = td_demo_content::add_page( array(
	'title' => 'Checkout - towntalk_pro',
	'file' => 'tds_checkout.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'payment_page_id',
	'value' => $page_payment_page_id_id,
	)
);

$page_my_account_page_id_id = td_demo_content::add_page( array(
	'title' => 'My Account - towntalk_pro',
	'file' => 'tds_my_account.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'my_account_page_id',
	'value' => $page_my_account_page_id_id,
	)
);

$page_create_account_page_id_id = td_demo_content::add_page( array(
	'title' => 'Login/Register - towntalk_pro',
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
	CATEGORIES
*/
$cat_art_id = td_demo_category::add_category(array(
	'category_name' => 'Art',
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

$cat_education_id = td_demo_category::add_category(array(
	'category_name' => 'Education',
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

$cat_innovation_id = td_demo_category::add_category(array(
	'category_name' => 'Innovation',
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

$cat_local_news_id = td_demo_category::add_category(array(
	'category_name' => 'Local News',
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

$cat_marketing_id = td_demo_category::add_category(array(
	'category_name' => 'Marketing',
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

$cat_weather_id = td_demo_category::add_category(array(
	'category_name' => 'Weather',
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


/*  ---------------------------------------------------------------------------- 
	 CLOUD TEMPLATES(MODULES)
*/

/*  ---------------------------------------------------------------------------- 
	ATTACHMENTS
*/

/*  ---------------------------------------------------------------------------- 
	PAGES
*/
$page_megamenu_modal_id = td_demo_content::add_page( array(
	'title' => 'Megamenu Modal',
	'file' => 'megamenu_modal.txt',
	'demo_unique_id' => '2864fae1a34d657',
));

$page_newsletter_id = td_demo_content::add_page( array(
	'title' => 'Newsletter',
	'file' => 'newsletter.txt',
	'demo_unique_id' => '2064fae1a34deda',
));

$page_bookmark_page_id = td_demo_content::add_page( array(
	'title' => 'Bookmark page',
	'file' => 'bookmark_page.txt',
	'demo_unique_id' => '9064fae1a34e726',
));

$page_tds_switching_plans_wizard_id = td_demo_content::add_page( array(
	'title' => 'Switching plans wizard',
	'file' => 'tds_switching_plans_wizard.txt',
	'demo_unique_id' => '2664fae1a34f163',
));

$page_tabbed_content_recommended_id = td_demo_content::add_page( array(
	'title' => 'Tabbed content - Recommended',
	'file' => 'tabbed_content_recommended.txt',
	'template' => 'default',
	'demo_unique_id' => '1164fae1a350318',
));

$page_tabbed_content_latest_id = td_demo_content::add_page( array(
	'title' => 'Tabbed content - Latest',
	'file' => 'tabbed_content_latest.txt',
	'template' => 'default',
	'demo_unique_id' => '7464fae1a350b4a',
));

$page_homepage_id = td_demo_content::add_page( array(
	'title' => 'Homepage',
	'file' => 'homepage.txt',
	'template' => 'default',
	'homepage' => true,
	'demo_unique_id' => '6564fae1a351835',
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
				'tds_title' => ' Access to this content is exclusively reserved for subscribers.',
				'tds_message' => 'To access this content, please subscribe.',
				'tds_submit_btn_text' => 'Subscribe to unlock',
				'tds_pp_msg' => 'I consent to processing of my data according to <a href=\"#\">Terms of Use</a> & <a href=\"#\">Privacy Policy</a>',
				'tds_locker_cf_1_name' => 'Custom field 1',
				'tds_locker_cf_2_name' => 'Custom field 2',
				'tds_locker_cf_3_name' => 'Custom field 3',
			),
	'tds_payable' => 'paid_subscription',
	'tds_paid_subs_page_id' => $page_tds_switching_plans_wizard_id,
	'tds_paid_subs_plan_ids' => [$plan_monthly_plan_id,$plan_yearly_plan_id,$plan_free_plan_id],
			'tds_locker_styles' => array(
				'tds_bg_color' => '#1b1b1b',
				'all_tds_shadow' => '20',
				'all_tds_shadow_color' => '#2fa6df',
				'tds_title_color' => '#ffffff',
				'tds_message_color' => '#ffffff',
				'tds_submit_btn_text_color' => '#ffffff',
				'tds_submit_btn_text_color_h' => '#1b1b1b',
				'tds_submit_btn_bg_color' => '#2fa6df',
				'tds_submit_btn_bg_color_h' => '#f6f6f6',
				'tds_pp_checked_color' => '#2fa6df',
				'tds_pp_check_bg' => '#ffffff',
				'tds_pp_msg_color' => '#ffffff',
				'tds_pp_msg_links_color' => '#2fa6df',
				'tds_pp_msg_links_color_h' => '#f6f6f6',
				'tds_general_font_family' => 'tt-primary-font_global',
				'tds_title_font_size' => '30',
				'tds_title_font_line_height' => '1.1',
				'tds_title_font_weight' => '900',
				'tds_message_font_size' => '14',
				'tds_submit_btn_text_font_family' => 'tt-extra_global',
				'tds_submit_btn_text_font_size' => '20',
				'tds_submit_btn_text_font_line_height' => '1.6',
				'tds_submit_btn_text_font_weight' => '900',
				'tds_pp_msg_font_size' => '12',
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

td_util::update_option('tds_demo_options', 'a:1:{s:5:"plans";a:3:{i:0;a:2:{s:9:"unique_id";s:15:"6764fae1a31e0e1";s:4:"name";s:12:"Monthly Plan";}i:1;a:2:{s:9:"unique_id";s:15:"9964fae1a31e186";s:4:"name";s:11:"Yearly Plan";}i:2;a:2:{s:9:"unique_id";s:14:"464fae1a31e221";s:4:"name";s:9:"Free Plan";}}}');


/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTION - end phase 2
*/

/*  ---------------------------------------------------------------------------- 
	POSTS
*/


$post_td_post_the_definitive_guide_to_marketing_your_business_on_instagram_id = td_demo_content::add_post( array(
    'title' => 'The Definitive Guide To Marketing Your Business On Instagram',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_6',
    'tds_locker' => '211',
    'categories_id_array' => array($cat_art_id,$cat_celebrity_id,$cat_education_id,$cat_food_id,$cat_innovation_id,$cat_local_news_id,$cat_marketing_id,$cat_travel_id,$cat_weather_id,),
));

$post_td_post_olimpic_athlete_reads_donald_trumps_mean_tweets_on_kimmel_id = td_demo_content::add_post( array(
    'title' => 'Olimpic Athlete Reads Donald Trump\'s Mean Tweets on Kimmel',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_7',
    'tds_locker' => '211',
    'categories_id_array' => array($cat_art_id,$cat_celebrity_id,$cat_education_id,$cat_food_id,$cat_innovation_id,$cat_local_news_id,$cat_marketing_id,$cat_travel_id,$cat_weather_id,),
));

$post_td_post_program_will_lend_10m_to_detroit_minority_businesses_id = td_demo_content::add_post( array(
    'title' => 'Program Will Lend $10M to Detroit Minority Businesses',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_8',
    'tds_locker' => '211',
    'categories_id_array' => array($cat_art_id,$cat_celebrity_id,$cat_education_id,$cat_food_id,$cat_innovation_id,$cat_local_news_id,$cat_marketing_id,$cat_travel_id,$cat_weather_id,),
));

$post_td_post_kansas_city_has_a_massive_array_of_big_national_companies_id = td_demo_content::add_post( array(
    'title' => 'Kansas City Has a Massive Array of Big National Companies',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_9',
    'tds_locker' => '211',
    'categories_id_array' => array($cat_art_id,$cat_celebrity_id,$cat_education_id,$cat_food_id,$cat_innovation_id,$cat_local_news_id,$cat_marketing_id,$cat_travel_id,$cat_weather_id,),
));

$post_td_post_now_is_the_time_to_think_about_your_small_business_success_id = td_demo_content::add_post( array(
    'title' => 'Now Is the Time to Think About Your Small-Business Success',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_10',
    'tds_locker' => '211',
    'categories_id_array' => array($cat_art_id,$cat_celebrity_id,$cat_education_id,$cat_food_id,$cat_innovation_id,$cat_local_news_id,$cat_marketing_id,$cat_travel_id,$cat_weather_id,),
));

$post_td_post_capturing_the_majestic_power_and_beauty_of_natures_elements_id = td_demo_content::add_post( array(
	'title' => 'Capturing the Majestic Power and Beauty of Nature\'s Elements',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_weather_id,),
));

$post_td_post_advances_in_predicting_and_monitoring_atmospheric_conditions_id = td_demo_content::add_post( array(
	'title' => 'Advances in Predicting and Monitoring Atmospheric Conditions',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'tds_locker' => '211',
	'categories_id_array' => array($cat_weather_id,),
));

$post_td_post_embracing_the_season_with_skiing_snowboarding_and_ice_skating_id = td_demo_content::add_post( array(
	'title' => 'Embracing the Season with Skiing, Snowboarding, and Ice Skating',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'tds_locker' => '211',
	'categories_id_array' => array($cat_weather_id,),
));

$post_td_post_sunny_skies_and_mild_temperatures_for_the_week_ahead_id = td_demo_content::add_post( array(
	'title' => 'Sunny Skies and Mild Temperatures for the Week Ahead',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_weather_id,),
));

$post_td_post_tips_to_stay_safe_and_beat_the_heatwave_id = td_demo_content::add_post( array(
	'title' => 'Tips to Stay Safe and Beat the Heatwave',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_weather_id,),
));

$post_td_post_customer_engagement_marketing_a_new_strategy_for_the_economy_id = td_demo_content::add_post( array(
	'title' => 'Customer Engagement Marketing: A New Strategy for the Economy',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_marketing_id,),
));

$post_td_post_television_is_in_steep_decline_compared_to_social_network_marketing_id = td_demo_content::add_post( array(
	'title' => 'Television is in Steep Decline Compared to Social Network Marketing',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_marketing_id,),
));

$post_td_post_social_networks_advertising_is_important_the_future_of_marketing_id = td_demo_content::add_post( array(
	'title' => 'Social Networks Advertising is Important the Future Of Marketing',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_marketing_id,),
));

$post_td_post_how_can_influencers_show_glamour_and_class_selling_on_instagram_id = td_demo_content::add_post( array(
	'title' => 'How Can Influencers Show Glamour and Class Selling on Instagram',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'tds_locker' => '211',
	'categories_id_array' => array($cat_marketing_id,),
));

$post_td_post_mobile_marketing_is_the_future_of_e_commerce_real_world_study_finds_id = td_demo_content::add_post( array(
	'title' => 'Mobile Marketing is the Future of E-Commerce, Real-World Study Finds',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_marketing_id,),
));

$post_td_post_the_journey_of_transformation_through_innovation_id = td_demo_content::add_post( array(
	'title' => 'The Journey of Transformation through Innovation',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_innovation_id,),
));

$post_td_post_redefining_industries_and_empowering_change_in_the_age_of_transformation_id = td_demo_content::add_post( array(
	'title' => 'Redefining Industries and Empowering Change in the Age of Transformation',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_innovation_id,),
));

$post_td_post_from_idea_to_impact_nurturing_innovation_for_a_better_tomorrow_id = td_demo_content::add_post( array(
	'title' => 'From Idea to Impact Nurturing Innovation for a Better Tomorrow',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'tds_locker' => '211',
	'categories_id_array' => array($cat_innovation_id,),
));

$post_td_post_where_technology_meets_human_ingenuity_id = td_demo_content::add_post( array(
	'title' => 'Where Technology Meets Human Ingenuity',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_innovation_id,),
));

$post_td_post_engaging_the_community_through_interactive_art_installations_id = td_demo_content::add_post( array(
	'title' => 'Engaging the Community through Interactive Art Installations',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_art_id,),
));

$post_td_post_redefining_the_future_with_game_changing_breakthrough_id = td_demo_content::add_post( array(
	'title' => 'Redefining the Future with Game-Changing Breakthrough',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_innovation_id,),
));

$post_td_post_a_mosaic_of_music_dance_and_art_celebrating_diversity_id = td_demo_content::add_post( array(
	'title' => 'A Mosaic of Music, Dance, and Art Celebrating Diversity',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'tds_locker' => '211',
	'categories_id_array' => array($cat_art_id,),
));

$post_td_post_art_therapy_healing_and_empowering_through_creative_expression_id = td_demo_content::add_post( array(
	'title' => 'Art Therapy Healing and Empowering through Creative Expression',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'tds_locker' => '211',
	'categories_id_array' => array($cat_art_id,),
));

$post_td_post_local_art_exhibition_showcases_the_vibrant_talent_of_the_community_id = td_demo_content::add_post( array(
	'title' => 'Local Art Exhibition Showcases the Vibrant Talent of the Community',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_art_id,),
));

$post_td_post_community_theater_production_receives_standing_ovation_for_stellar_performances_id = td_demo_content::add_post( array(
	'title' => 'Community Theater Production Receives Standing Ovation for Stellar Performances',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_art_id,),
));

$post_td_post_10_things_you_should_know_before_you_stay_in_the_carribean_islands_id = td_demo_content::add_post( array(
	'title' => '10 Things You Should Know Before You Stay in the Carribean Islands',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_travel_id,),
));

$post_td_post_ultimate_guide_to_istanbul_top_attractions_packed_in_one_weekend_id = td_demo_content::add_post( array(
	'title' => 'Ultimate Guide to Istanbul Top Attractions, Packed in One Weekend',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'tds_locker' => '211',
	'categories_id_array' => array($cat_travel_id,),
));

$post_td_post_vacation_bucket_list_the_top_10_trips_you_should_take_with_your_kids_id = td_demo_content::add_post( array(
	'title' => 'Vacation Bucket List: The Top 10 Trips You Should Take with Your Kids',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_travel_id,),
));

$post_td_post_the_mayan_piramids_reach_1_million_visitors_every_year_since_2014_id = td_demo_content::add_post( array(
	'title' => 'The Mayan Piramids Reach 1 Million Visitors Every Year Since 2014',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'tds_locker' => '211',
	'categories_id_array' => array($cat_travel_id,),
));

$post_td_post_the_best_cities_you_can_find_in_italy_to_satisfy_the_love_for_fruits_id = td_demo_content::add_post( array(
	'title' => 'The Best Cities You Can Find in Italy to Satisfy the Love for Fruits',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_travel_id,),
));

$post_td_post_easy_food_survey_pizza_voted_as_one_of_the_most_satisfying_meals_ever_id = td_demo_content::add_post( array(
	'title' => 'Easy Food Survey: Pizza Voted As One of the Most Satisfying Meals Ever',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'tds_locker' => '211',
	'categories_id_array' => array($cat_food_id,),
));

$post_td_post_this_week_in_houston_food_blogs_high_protein_recipes_and_low_fat_shakes_id = td_demo_content::add_post( array(
	'title' => 'This Week in Houston Food Blogs: High-Protein Recipes and Low Fat Shakes',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_food_id,),
));

$post_td_post_moroccan_shrimp_with_garlic_mayonnaise_paella_dish_in_southern_spain_id = td_demo_content::add_post( array(
	'title' => 'Moroccan Shrimp with Garlic Mayonnaise Paella Dish in Southern Spain',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_food_id,),
));

$post_td_post_best_places_to_get_your_mexican_food_fix_when_you_visit_mexico_city_id = td_demo_content::add_post( array(
	'title' => 'Best Places to Get Your Mexican Food Fix When You Visit Mexico City',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_food_id,),
));

$post_td_post_kristen_stewart_was_seen_having_lunch_in_toronto_with_boyfriend_id = td_demo_content::add_post( array(
	'title' => 'Kristen Stewart Was Seen Having Lunch in Toronto with Boyfriend',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_celebrity_id,),
));

$post_td_post_the_best_pork_kebabs_with_grilled_plums_and_couscous_is_found_in_poland_id = td_demo_content::add_post( array(
	'title' => 'The Best Pork Kebabs With Grilled Plums and Couscous is Found in Poland',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_food_id,),
));

$post_td_post_celebrity_make_up_artist_gary_meyers_shows_you_his_beauty_tricks_id = td_demo_content::add_post( array(
	'title' => 'Celebrity Make-up Artist Gary Meyers Shows you His Beauty Tricks',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_celebrity_id,),
));

$post_td_post_the_biggest_hollywood_celebrities_visit_the_jungles_of_thailand_id = td_demo_content::add_post( array(
	'title' => 'The Biggest Hollywood Celebrities Visit the Jungles of Thailand',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_celebrity_id,),
));

$post_td_post_fashion_finder_biggest_shows_parties_and_celebrity_for_new_years_id = td_demo_content::add_post( array(
	'title' => 'Fashion Finder: Biggest Shows, Parties and Celebrity for New Years',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'tds_locker' => '211',
	'categories_id_array' => array($cat_celebrity_id,),
));

$post_td_post_exploring_extracurricular_opportunities_for_student_id = td_demo_content::add_post( array(
	'title' => 'Exploring Extracurricular Opportunities for  Student',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_education_id,),
));

$post_td_post_the_most_popular_celebrity_name_list_of_the_millennium_is_here_id = td_demo_content::add_post( array(
	'title' => 'The Most Popular Celebrity Name List of the Millennium is Here',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'tds_locker' => '211',
	'categories_id_array' => array($cat_celebrity_id,),
));

$post_td_post_promoting_student_well_being_and_resilience_id = td_demo_content::add_post( array(
	'title' => 'Promoting Student Well-being and Resilience',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_education_id,),
));

$post_td_post_expert_tips_for_high_school_seniors_id = td_demo_content::add_post( array(
	'title' => 'Expert Tips for  High School Seniors',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_education_id,),
));

$post_td_post_innovative_teaching_methods_transforming_classrooms_id = td_demo_content::add_post( array(
	'title' => 'Innovative Teaching Methods Transforming  Classrooms',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_education_id,),
));

$post_td_post_exploring_career_pathways_schools_prepare_students_for_the_future_id = td_demo_content::add_post( array(
	'title' => 'Exploring Career Pathways:  Schools Prepare Students for the Future',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'tds_locker' => '211',
	'categories_id_array' => array($cat_education_id,),
));

$post_td_post_town_celebrates_grand_opening_of_new_public_library_with_festive_ribbon_cutting_id = td_demo_content::add_post( array(
	'title' => 'Town Celebrates Grand Opening of New Public Library with Festive Ribbon Cutting',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_local_news_id,),
));

$post_td_post_community_unites_to_support_families_affected_by_recent_natural_disaster_id = td_demo_content::add_post( array(
	'title' => 'Community Unites to Support  Families Affected by Recent Natural Disaster',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_local_news_id,),
));

$post_td_post_community_rallies_together_to_rebuild_playground_destroyed_in_devastating_fire_id = td_demo_content::add_post( array(
	'title' => 'Community Rallies Together to Rebuild  Playground Destroyed in Devastating Fire',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_local_news_id,),
));

$post_td_post_road_expansion_project_set_to_ease_commute_time_in_town_id = td_demo_content::add_post( array(
	'title' => 'Road Expansion Project Set to Ease Commute Time in Town',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'tds_locker' => '211',
	'categories_id_array' => array($cat_local_news_id,),
));

$post_td_post_uncovers_corruption_scandal_involving_local_officials_and_misappropriation_of_funds_id = td_demo_content::add_post( array(
	'title' => 'Uncovers Corruption Scandal Involving Local Officials and Misappropriation of Funds',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'tds_locker' => '211',
	'categories_id_array' => array($cat_local_news_id,),
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
$menu_item_0_id = td_demo_menus::add_link( array(
	'title' => 'About Us',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_link( array(
	'title' => 'Partner with Us',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_link( array(
	'title' => 'Careers',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_link( array(
	'title' => 'Contact us',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'url' => '#',
	'parent_id' => ''
));


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_link( array(
	'title' => 'Terms and conditions',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_link( array(
	'title' => 'Privacy policy',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_link( array(
	'title' => 'Sitemap',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'url' => '#',
	'parent_id' => ''
));


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Local',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_local_news_id,
	'parent_id' => ''
), true );

$menu_item_1_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Art',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_art_id,
	'parent_id' => ''
), true );

$menu_item_2_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Weather',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_weather_id,
	'parent_id' => ''
), true );

$menu_item_3_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Food',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_food_id,
	'parent_id' => ''
), true );

$menu_item_4_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Travel',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_travel_id,
	'parent_id' => ''
), true );

$menu_item_5_id = td_demo_menus::add_link( array(
	'title' => 'More',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_6_id = td_demo_menus::add_category( array(
	'title' => 'Celebrity',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_celebrity_id,
	'parent_id' => $menu_item_5_id
));

$menu_item_7_id = td_demo_menus::add_category( array(
	'title' => 'Education',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_education_id,
	'parent_id' => $menu_item_5_id
));

$menu_item_8_id = td_demo_menus::add_category( array(
	'title' => 'Innovation',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_innovation_id,
	'parent_id' => $menu_item_5_id
));


/*  ---------------------------------------------------------------------------- 
	CLOUD TEMPLATES
*/
$template_tag_template_towntalk_id = td_demo_content::add_cloud_template( array(
	'title' => 'Tag Template - TownTalk',
	'file' => 'tag_cloud_template.txt',
	'template_type' => 'tag',
));

td_demo_misc::update_global_tag_template( 'tdb_template_' . $template_tag_template_towntalk_id );


$template_date_template_towntalk_id = td_demo_content::add_cloud_template( array(
	'title' => 'Date Template - TownTalk',
	'file' => 'date_cloud_template.txt',
	'template_type' => 'date',
));

td_demo_misc::update_global_date_template( 'tdb_template_' . $template_date_template_towntalk_id );


$template_author_template_towntalk_id = td_demo_content::add_cloud_template( array(
	'title' => 'Author Template - TownTalk',
	'file' => 'author_cloud_template.txt',
	'template_type' => 'author',
));

td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_towntalk_id );


$template_search_template_towntalk_id = td_demo_content::add_cloud_template( array(
	'title' => 'Search Template - TownTalk',
	'file' => 'search_cloud_template.txt',
	'template_type' => 'search',
));

td_demo_misc::update_global_search_template( 'tdb_template_' . $template_search_template_towntalk_id );


$template_category_template_towntalk_id = td_demo_content::add_cloud_template( array(
	'title' => 'Category Template - TownTalk',
	'file' => 'cat_cloud_template.txt',
	'template_type' => 'category',
));

td_demo_misc::update_global_category_template( 'tdb_template_' . $template_category_template_towntalk_id );


$template_404_template_towntalk_id = td_demo_content::add_cloud_template( array(
	'title' => '404 Template - TownTalk',
	'file' => '404_cloud_template.txt',
	'template_type' => '404',
));

td_demo_misc::update_global_404_template( 'tdb_template_' . $template_404_template_towntalk_id );


$template_single_template_towntalk_id = td_demo_content::add_cloud_template( array(
	'title' => 'Single Template - TownTalk',
	'file' => 'post_cloud_template.txt',
	'template_type' => 'single',
));

td_util::update_option( 'td_default_site_post_template', 'tdb_template_' . $template_single_template_towntalk_id );


$template_footer_template_towntalk_id = td_demo_content::add_cloud_template( array(
	'title' => 'Footer Template - TownTalk',
	'file' => 'footer_template_towntalk_cloud_template.txt',
	'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_template_towntalk_id );


$template_header_template_towntalk_id = td_demo_content::add_cloud_template( array(
	'title' => 'Header Template - TownTalk',
	'file' => 'header_template_towntalk_cloud_template.txt',
	'template_type' => 'header',
));

td_demo_misc::update_global_header_template( 'tdb_template_' . $template_header_template_towntalk_id );


update_post_meta( $template_header_template_towntalk_id, 'header_mobile_menu_id', $menu_td_demo_header_menu_id );



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
td_demo_content::update_meta( $template_footer_template_towntalk_id, 'tdc_footer_template_id', $template_footer_template_towntalk_id );

td_demo_content::update_meta( $template_header_template_towntalk_id, 'tdc_header_template_id', $template_header_template_towntalk_id );

// pages metas
td_demo_content::update_meta( $page_newsletter_id, 'tdc_footer_template_id', 'no_footer' );

td_demo_content::update_meta( $page_tabbed_content_recommended_id, 'tdc_header_template_id', 'no_header' );

td_demo_content::update_meta( $page_tabbed_content_recommended_id, 'tdc_footer_template_id', 'no_footer' );

td_demo_content::update_meta( $page_tabbed_content_latest_id, 'tdc_header_template_id', 'no_header' );

td_demo_content::update_meta( $page_tabbed_content_latest_id, 'tdc_footer_template_id', 'no_footer' );

td_demo_content::update_meta( $page_homepage_id, 'tdc_header_template_id', $template_header_template_towntalk_id );
