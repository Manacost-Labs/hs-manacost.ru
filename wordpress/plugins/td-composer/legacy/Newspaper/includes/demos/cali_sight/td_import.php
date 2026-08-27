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
        'interval' => 'month',
        'interval_count' => 1,
        'trial_days' => '0',
        'is_free' => '0',
        'is_unlimited' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"61645b8c06d67f2";}',
	)
);

$plan_free_plan_id = td_demo_subscription::add_plan( array(
		'name' => 'Free Plan',
		'price' => '',
        'interval' => '',
        'interval_count' => 0,
        'trial_days' => '0',
        'is_free' => '1',
        'is_unlimited' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"30645b8c06d68f4";}',
	)
);

$plan_yearly_plan_id = td_demo_subscription::add_plan( array(
		'name' => 'Yearly Plan',
		'price' => '100',
        'interval' => 'year',
        'interval_count' => 1,
        'trial_days' => '0',
        'is_free' => '0',
        'is_unlimited' => '0',
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
	CATEGORIES
*/

$cat_blog_id = td_demo_category::add_category(array(
    'category_name' => 'Blog',
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

$cat_advice_id = td_demo_category::add_category(array(
    'category_name' => 'Advice',
    'parent_id' => $cat_blog_id,
    'category_template' => '',
    'top_posts_style' => '',
    'description' => '',
    'background_td_pic_id' => '',
    'boxed_layout' => 'hide',
    'sidebar_id' => '',
    'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID
    'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar
));

$cat_cities_id = td_demo_category::add_category(array(
    'category_name' => 'Cities',
    'parent_id' => $cat_blog_id,
    'category_template' => '',
    'top_posts_style' => '',
    'description' => '',
    'background_td_pic_id' => '',
    'boxed_layout' => 'hide',
    'sidebar_id' => '',
    'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID
    'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar
));

$cat_exploration_id = td_demo_category::add_category(array(
    'category_name' => 'Exploration',
    'parent_id' => $cat_blog_id,
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
    'parent_id' => $cat_blog_id,
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
	ATTACHMENTS
*/

/*  ----------------------------------------------------------------------------
	MENUS
*/
$menu_td_demo_custom_menu_id = td_demo_menus::create_menu('td-demo-custom-menu', '');
$menu_td_demo_footer_menu_id = td_demo_menus::create_menu('td-demo-footer-menu', '');
$menu_td_demo_footer_menu_extra_id = td_demo_menus::create_menu('td-demo-footer-menu-extra', '');
$menu_td_demo_header_menu_id = td_demo_menus::create_menu('td-demo-header-menu', '');

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
	POSTS
*/
$post_td_post_the_5_best_hostels_in_san_diego_id = td_demo_content::add_post( array(
	'title' => 'The 5 Best Hostels in San Diego',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'tds_locker' => '341',
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));

$post_td_post_where_to_stay_in_dubrovnik_the_best_neighborhoods_for_your_visit_id = td_demo_content::add_post( array(
	'title' => 'Where to Stay in Dubrovnik: The Best Neighborhoods for Your Visit',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'tds_locker' => '341',
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));

$post_td_post_a_guide_to_exploring_colonial_new_york_city_id = td_demo_content::add_post( array(
	'title' => 'A Guide to Exploring Colonial New York City',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'tds_locker' => '341',
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));

$post_td_post_15_off_the_beaten_path_unique_things_to_do_in_prague_id = td_demo_content::add_post( array(
	'title' => '15 Off-The-Beaten-Path & Unique Things to Do in Prague',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));

$post_td_post_where_to_stay_in_singapore_the_best_neighborhoods_for_your_visit_id = td_demo_content::add_post( array(
	'title' => 'Where to Stay in Singapore: The Best Neighborhoods for Your Visit',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'tds_locker' => '341',
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));

$post_td_post_6_reasons_you_should_learn_the_local_language_before_your_trip_id = td_demo_content::add_post( array(
	'title' => '6 Reasons You Should Learn the Local Language Before Your Trip',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'tds_locker' => '341',
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));

$post_td_post_23_ways_to_cut_your_expenses_and_have_more_money_for_travel_id = td_demo_content::add_post( array(
	'title' => '23 Ways to Cut Your Expenses and Have More Money for Travel',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'tds_locker' => '341',
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));

$post_td_post_utah_hiking_guide_17_best_utah_hikes_trails_id = td_demo_content::add_post( array(
	'title' => 'Utah Hiking Guide: 17 Best Utah Hikes & Trails',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'tds_locker' => '341',
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));

$post_td_post_where_to_stay_in_auckland_the_best_neighborhoods_for_your_visit_id = td_demo_content::add_post( array(
	'title' => 'Where to Stay in Auckland: The Best Neighborhoods for Your Visit',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'tds_locker' => '341',
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));

$post_td_post_24_best_things_to_do_in_indonesia_what_to_do_in_the_islands_id = td_demo_content::add_post( array(
	'title' => '24 Best Things To Do In Indonesia: What To Do In The Islands',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'tds_locker' => '341',
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));

$post_td_post_the_13_best_things_to_see_do_on_the_oregon_coast_id = td_demo_content::add_post( array(
	'title' => 'The 13 Best Things to See & Do on the Oregon Coast',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));

$post_td_post_the_4_best_tour_companies_in_peru_id = td_demo_content::add_post( array(
	'title' => 'The 4 Best Tour Companies in Peru',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'tds_locker' => '341',
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));

$post_td_post_the_great_american_road_trip_a_4_month_itinerary_around_the_usa_id = td_demo_content::add_post( array(
	'title' => 'The Great American Road Trip: A 4-Month Itinerary Around the USA',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'tds_locker' => '341',
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));

$post_td_post_the_24_best_things_to_do_in_rome_id = td_demo_content::add_post( array(
	'title' => 'The 24 Best Things to Do in Rome',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'tds_locker' => '341',
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));

$post_td_post_should_travel_be_inexpensive_id = td_demo_content::add_post( array(
	'title' => 'Should Travel Be Inexpensive?',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'tds_locker' => '341',
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));

$post_td_post_tongariro_crossing_hike_alpine_national_park_in_new_zealand_id = td_demo_content::add_post( array(
	'title' => 'Tongariro Crossing Hike: Alpine National Park In New Zealand',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'tds_locker' => '341',
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));

$post_td_post_a_complete_guide_to_the_japan_rail_pass_id = td_demo_content::add_post( array(
	'title' => 'A Complete Guide to the Japan Rail Pass',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'tds_locker' => '341',
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));

$post_td_post_8_things_to_know_about_the_bilt_rewards_mastercard_id = td_demo_content::add_post( array(
	'title' => '8 Things to Know About the Bilt Rewards Mastercard',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'tds_locker' => '341',
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));

$post_td_post_10_travel_hacking_mistakes_to_avoid_id = td_demo_content::add_post( array(
	'title' => '10 Travel Hacking Mistakes to Avoid',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'tds_locker' => '341',
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));

$post_td_post_where_to_stay_in_bogota_the_best_neighborhoods_for_your_visit_id = td_demo_content::add_post( array(
	'title' => 'Where to Stay in Bogotá: The Best Neighborhoods for Your Visit',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'tds_locker' => '341',
	'categories_id_array' => array($cat_advice_id,$cat_blog_id,$cat_cities_id,$cat_exploration_id,$cat_travel_id,),
));


/*  ---------------------------------------------------------------------------- 
	PRODUCTS
*/

/*  ---------------------------------------------------------------------------- 
	TAXONOMIES
*/
$tax_term_entertainment_value_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Entertainment Value',
    'taxonomy' => 'tdc-review-criteria',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => 'Laoreet, montes porttitor. Habitasse pede phasellus sit sapien nonummy nulla taciti risus arcu nisi felis laoreet tellus Pellentesque per neque placerat sagittis dapibus cras cras turpis nec est condimentum placerat mauris eros cras quis dui fusce.',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_overall_experience_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Overall Experience',
    'taxonomy' => 'tdc-review-criteria',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => 'Adipiscing sociosqu duis ante. Erat nullam duis. Platea erat pretium sapien augue. Laoreet, montes porttitor. Habitasse pede phasellus sit sapien nonummy nulla taciti risus arcu nisi felis laoreet tellus Laoreet, montes porttitor. ',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_staff_rating_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Staff Rating',
    'taxonomy' => 'tdc-review-criteria',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => 'Laoreet, montes porttitor. Habitasse pede phasellus sit sapien nonummy nulla taciti risus arcu nisi felis laoreet tellus Pellentesque per neque placerat sagittis dapibus cras cras turpis nec est condimentum placerat mauris eros cras quis dui fusce. Accumsan pellentesque.',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_things_to_do_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Things to Do',
    'taxonomy' => 'tdtax_attractions',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => 'Euismod molestie class curabitur tempus lacus parturient malesuada libero lobortis mi facilisis sociis, lobortis nisi porttitor suscipit fames aliquet nibh magnis venenatis. Inceptos odio morbi scelerisque augue dui fusce habitasse vel non dictum curabitur hendrerit sodales nulla. ',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_adventure_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Adventure',
    'taxonomy' => 'tdtax_attractions',
    'taxonomy_template' => '',
    'parent_id' => $tax_term_things_to_do_id,
    'description' => 'Euismod molestie class curabitur tempus lacus parturient malesuada libero lobortis mi facilisis sociis, lobortis nisi porttitor suscipit fames aliquet nibh magnis venenatis. Inceptos odio morbi scelerisque augue dui fusce habitasse vel non dictum curabitur hendrerit sodales nulla. ',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_cruises_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Cruises',
    'taxonomy' => 'tdtax_attractions',
    'taxonomy_template' => '',
    'parent_id' => $tax_term_things_to_do_id,
    'description' => 'Euismod molestie class curabitur tempus lacus parturient malesuada libero lobortis mi facilisis sociis, lobortis nisi porttitor suscipit fames aliquet nibh magnis venenatis. Inceptos odio morbi scelerisque augue dui fusce habitasse vel non dictum curabitur hendrerit sodales nulla. ',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_food_drink_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Food &amp; Drink',
    'taxonomy' => 'tdtax_attractions',
    'taxonomy_template' => '',
    'parent_id' => $tax_term_things_to_do_id,
    'description' => 'Euismod molestie class curabitur tempus lacus parturient malesuada libero lobortis mi facilisis sociis, lobortis nisi porttitor suscipit fames aliquet nibh magnis venenatis. Inceptos odio morbi scelerisque augue dui fusce habitasse vel non dictum curabitur hendrerit sodales nulla. ',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_history_culture_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'History &amp; Culture',
    'taxonomy' => 'tdtax_attractions',
    'taxonomy_template' => '',
    'parent_id' => $tax_term_things_to_do_id,
    'description' => 'Euismod molestie class curabitur tempus lacus parturient malesuada libero lobortis mi facilisis sociis, lobortis nisi porttitor suscipit fames aliquet nibh magnis venenatis. Inceptos odio morbi scelerisque augue dui fusce habitasse vel non dictum curabitur hendrerit sodales nulla. ',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_seasonal_activities_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Seasonal Activities',
    'taxonomy' => 'tdtax_attractions',
    'taxonomy_template' => '',
    'parent_id' => $tax_term_things_to_do_id,
    'description' => 'Euismod molestie class curabitur tempus lacus parturient malesuada libero lobortis mi facilisis sociis, lobortis nisi porttitor suscipit fames aliquet nibh magnis venenatis. Inceptos odio morbi scelerisque augue dui fusce habitasse vel non dictum curabitur hendrerit sodales nulla. ',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_shows_concerts_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Shows &amp; Concerts',
    'taxonomy' => 'tdtax_attractions',
    'taxonomy_template' => '',
    'parent_id' => $tax_term_things_to_do_id,
    'description' => 'Euismod molestie class curabitur tempus lacus parturient malesuada libero lobortis mi facilisis sociis, lobortis nisi porttitor suscipit fames aliquet nibh magnis venenatis. Inceptos odio morbi scelerisque augue dui fusce habitasse vel non dictum curabitur hendrerit sodales nulla. ',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_tours_trips_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Tours &amp; Day Trips',
    'taxonomy' => 'tdtax_attractions',
    'taxonomy_template' => '',
    'parent_id' => $tax_term_things_to_do_id,
    'description' => 'Euismod molestie class curabitur tempus lacus parturient malesuada libero lobortis mi facilisis sociis, lobortis nisi porttitor suscipit fames aliquet nibh magnis venenatis. Inceptos odio morbi scelerisque augue dui fusce habitasse vel non dictum curabitur hendrerit sodales nulla. ',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_workshops_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Workshops',
    'taxonomy' => 'tdtax_attractions',
    'taxonomy_template' => '',
    'parent_id' => $tax_term_things_to_do_id,
    'description' => 'Euismod molestie class curabitur tempus lacus parturient malesuada libero lobortis mi facilisis sociis, lobortis nisi porttitor suscipit fames aliquet nibh magnis venenatis. Inceptos odio morbi scelerisque augue dui fusce habitasse vel non dictum curabitur hendrerit sodales nulla. ',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));


/*  ---------------------------------------------------------------------------- 
	CPTs
*/
$cpt_rock_city_night_id = td_demo_content::add_cpt( array(
	'title' => 'Rock City Night',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_price' => 'NjAtMTUwJA==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDEwNjIxOS41MjE3NjI4ODUxMiEyZC0xMTguMDU3NzgwNzgzNTkzNzYhM2QzMy42OTk2MzEwMDAwMDAwMDQhMm0zITFmMCEyZjAhM2YwITNtMiExaTEwMjQhMmk3NjghNGYxMy4xITNtMyExbTIhMXMweDgwZGNkOGE5NGVhMzMwZTclM0EweDhkNjZhY2MzZWRiOWMxYzEhMnNUaGUlMjBPYnNlcnZhdG9yeSE1ZTAhM20yITFzZW4hMnNybyE0djE2Nzg4ODk0NDI5OTYhNW0yITFzZW4hMnNybyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgc3R5bGU9ImJvcmRlcjowOyIgYWxsb3dmdWxsc2NyZWVuPSIiIGxvYWRpbmc9ImxhenkiIHJlZmVycmVycG9saWN5PSJuby1yZWZlcnJlci13aGVuLWRvd25ncmFkZSI+PC9pZnJhbWU+',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'MzUwMyBTIEhhcmJvciBCbHZkLCBTYW50YSBBbmEsIENhbGlmb3JuaWE=',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Nmhy',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'TW9iaWxlIFZvdWNoZXI=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6MzU6IlZlaGljbGVzIGNsZWFuZWQgd2l0aCBkaXNpbmZlY3RhbnRzIjt9',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'MjRIciBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YTozOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czo0ODoiUGFpZCBzdGF5LWF0LWhvbWUgcG9saWN5IGZvciBzdGFmZiB3aXRoIHN5bXB0b21zIjtpOjI7czo0OToiUHJvdGVjdGl2ZSBzaGllbGRzIGluIHBsYWNlIGF0IG1haW4gY29udGFjdCBhcmVhcyI7fQ==',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToyOntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjIzOiJIYW5kIHNhbml0aXplciBwcm92aWRlZCI7fQ==',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_shows_concerts_id, $tax_term_things_to_do_id ),
	),
));

$cpt_stand_up_comedy_night_id = td_demo_content::add_cpt( array(
	'title' => 'Stand Up Comedy Night',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_price' => 'MzAtNTAk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNCExbTEyITFtMyExZDEwNTc3NC4wNjcyMzAzNjE0MSEyZC0xMTguMjkyMDMwOTE2OTA1NzchM2QzNC4wNTgyNDM1OTM2NTUyMDYhMm0zITFmMCEyZjAhM2YwITNtMiExaTEwMjQhMmk3NjghNGYxMy4xITVlMCEzbTIhMXNlbiEyc3JvITR2MTY3ODg4OTMyMjU2MyE1bTIhMXNlbiEyc3JvIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBzdHlsZT0iYm9yZGVyOjA7IiBhbGxvd2Z1bGxzY3JlZW49IiIgbG9hZGluZz0ibGF6eSIgcmVmZXJyZXJwb2xpY3k9Im5vLXJlZmVycmVyLXdoZW4tZG93bmdyYWRlIj48L2lmcmFtZT4=',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'Mzc5MCBXaWxzaGlyZSBCbHZkLCBMb3MgQW5nZWxlcywgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mmhy',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'TW9iaWxlIFZvdWNoZXI=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'UGFpZCBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6NDA6IkdlYXIgYW5kIGVxdWlwbWVudCBzYW5pdGl6ZWQgYmV0d2VlbiB1c2UiO30=',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Mi0zIEJ1c2luZXNzIERheSBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czo0OToiUHJvdGVjdGl2ZSBzaGllbGRzIGluIHBsYWNlIGF0IG1haW4gY29udGFjdCBhcmVhcyI7fQ==',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToyOntpOjA7czoxOToiTWFza3Mgd29ybiBieSBzdGFmZiI7aToxO3M6NDY6IkNvbnRhY3RsZXNzIHBheW1lbnQgZm9yIGdyYXR1aXRpZXMgYW5kIGFkZC1vbnMiO30=',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_shows_concerts_id, $tax_term_things_to_do_id ),
	),
));

$cpt_kings_of_pop_id = td_demo_content::add_cpt( array(
	'title' => 'Kings of Pop',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_price' => 'NTAtMTUwJA==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDQyMjk5OS4yNTM2NzI2NzEyITJkLTExOC42NTYzNDY4NjI5OTM5MyEzZDM0LjA3NzY3MzcwMDAwMDAxITJtMyExZjAhMmYwITNmMCEzbTIhMWkxMDI0ITJpNzY4ITRmMTMuMSEzbTMhMW0yITFzMHg4MGMzMzE3ZmUwOTdmMGRkJTNBMHhhMTVjMTAwMmEwNGQ1OTI1ITJzVGhlJTIwQ2FueW9uJTIwLSUyME1vbnRjbGFpciE1ZTAhM20yITFzZW4hMnNybyE0djE2Nzg4ODkxNDUyMzkhNW0yITFzZW4hMnNybyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgc3R5bGU9ImJvcmRlcjowOyIgYWxsb3dmdWxsc2NyZWVuPSIiIGxvYWRpbmc9ImxhenkiIHJlZmVycmVycG9saWN5PSJuby1yZWZlcnJlci13aGVuLWRvd25ncmFkZSI+PC9pZnJhbWU+',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'NTA2MCBFIE4gTW9udGNsYWlyIFBsYXphIExuLCBNb250Y2xhaXIsIENhbGlmb3JuaWE=',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mmhy',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'TW9iaWxlIFZvdWNoZXI=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'Tm8gUGFya2luZyBhdmFpbGFibGU=',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6MzU6IlZlaGljbGVzIGNsZWFuZWQgd2l0aCBkaXNpbmZlY3RhbnRzIjt9',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Mi0zIEJ1c2luZXNzIERheSBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czo0MzoiSW5kaXZpZHVhbGx5IHdyYXBwZWQgZm9vZCBvcHRpb25zIGF2YWlsYWJsZSI7aToxO3M6NDk6IlByb3RlY3RpdmUgc2hpZWxkcyBpbiBwbGFjZSBhdCBtYWluIGNvbnRhY3QgYXJlYXMiO30=',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToyOntpOjA7czo0NjoiQ29udGFjdGxlc3MgcGF5bWVudCBmb3IgZ3JhdHVpdGllcyBhbmQgYWRkLW9ucyI7aToxO3M6MzU6IlNvY2lhbCBkaXN0YW5jaW5nIG1lYXN1cmVzIGluIHBsYWNlIjt9',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_shows_concerts_id, $tax_term_things_to_do_id ),
	),
));

$cpt_punk_rock_night_id = td_demo_content::add_cpt( array(
	'title' => 'Punk Rock Night',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_price' => 'MzUtMTUwJA==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDQyMjk5OS4yNTM2NzI2NzEyITJkLTExOC42NTYzNDY4NjI5OTM5MyEzZDM0LjA3NzY3MzcwMDAwMDAxITJtMyExZjAhMmYwITNmMCEzbTIhMWkxMDI0ITJpNzY4ITRmMTMuMSEzbTMhMW0yITFzMHg4MGMyYzcxYmJmYjUxZGVmJTNBMHg0OGE4ZTc2NmVlZTgyZGFhITJzVGhlJTIwRWNobyE1ZTAhM20yITFzZW4hMnNybyE0djE2Nzg4ODkwNDk5NTEhNW0yITFzZW4hMnNybyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgc3R5bGU9ImJvcmRlcjowOyIgYWxsb3dmdWxsc2NyZWVuPSIiIGxvYWRpbmc9ImxhenkiIHJlZmVycmVycG9saWN5PSJuby1yZWZlcnJlci13aGVuLWRvd25ncmFkZSI+PC9pZnJhbWU+',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'MTgyMiBTdW5zZXQgQmx2ZCwgTG9zIEFuZ2VsZXMsIENhbGlmb3JuaWE=',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mmhy',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'UGh5c2ljYWwgVm91Y2hlcg==',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'UGFpZCBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNToiVmVoaWNsZXMgY2xlYW5lZCB3aXRoIGRpc2luZmVjdGFudHMiO2k6MTtzOjQwOiJHZWFyIGFuZCBlcXVpcG1lbnQgc2FuaXRpemVkIGJldHdlZW4gdXNlIjt9',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'MjRIciBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToxOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjt9',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YTozOntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjIzOiJIYW5kIHNhbml0aXplciBwcm92aWRlZCI7aToyO3M6MTk6Ik1hc2tzIHdvcm4gYnkgc3RhZmYiO30=',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_shows_concerts_id, $tax_term_things_to_do_id ),
	),
));

$cpt_rb_night_id = td_demo_content::add_cpt( array(
	'title' => 'R&B Night',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_price' => 'MzUtMTAwJA==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDI5OTUxMS4wMjMwODU3NDk3NyEyZC0xMTcuNjU3NzgzODM2MTY2MTYhM2QzMy45NjI3MTI1Nzg5MzE1NyEybTMhMWYwITJmMCEzZjAhM20yITFpMTAyNCEyaTc2OCE0ZjEzLjEhM20zITFtMiExczB4ODBkYjUxYmI3NzU4Zjg0MyUzQTB4MTdiNjAyODM0MDAyNDVlNCEyc1l1Y2FpcGElMjBQZXJmb3JtaW5nJTIwQXJ0cyUyMENlbnRlciE1ZTAhM20yITFzZW4hMnNybyE0djE2Nzg4ODg5MzQxNzEhNW0yITFzZW4hMnNybyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgc3R5bGU9ImJvcmRlcjowOyIgYWxsb3dmdWxsc2NyZWVuPSIiIGxvYWRpbmc9ImxhenkiIHJlZmVycmVycG9saWN5PSJuby1yZWZlcnJlci13aGVuLWRvd25ncmFkZSI+PC9pZnJhbWU+',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'MTIwNjIgQ2FsaWZvcm5pYSBTdCwgWXVjYWlwYSwgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mmhy',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'TW9iaWxlIFZvdWNoZXI=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'UGFpZCBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YTozOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6Mzg6IlN0YWZmIHJlcXVpcmVkIHRvIHJlZ3VsYXJseSB3YXNoIGhhbmRzIjtpOjI7czo0MDoiR2VhciBhbmQgZXF1aXBtZW50IHNhbml0aXplZCBiZXR3ZWVuIHVzZSI7fQ==',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'SW5zdGFudCBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YTozOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czo0MzoiSW5kaXZpZHVhbGx5IHdyYXBwZWQgZm9vZCBvcHRpb25zIGF2YWlsYWJsZSI7aToyO3M6NDk6IlByb3RlY3RpdmUgc2hpZWxkcyBpbiBwbGFjZSBhdCBtYWluIGNvbnRhY3QgYXJlYXMiO30=',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToyOntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjIzOiJIYW5kIHNhbml0aXplciBwcm92aWRlZCI7fQ==',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_shows_concerts_id, $tax_term_things_to_do_id ),
	),
));

$cpt_van_rides_tour_id = td_demo_content::add_cpt( array(
	'title' => 'Van Rides Tour',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_6',
	'post_meta' => array( 
		'tdcf_price' => 'RnJlZQ==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNyExbTEyITFtMyExZDMyNjguODEzMDg2MTYxNjAxITJkLTExNi41OTQzNDQ2NjQ0ODg4MiEzZDM0Ljk4NjM0NjMxMjU2ODMxNSEybTMhMWYwITJmMCEzZjAhM20yITFpMTAyNCEyaTc2OCE0ZjEzLjEhM20yITFtMSEyek16VENzRFU1SnpFd0xqZ2lUaUF4TVRiQ3NETTFKekl6TGpVaVZ3ITVlMCEzbTIhMXNlbiEyc3JvITR2MTY3ODg4ODgyNTE4MCE1bTIhMXNlbiEyc3JvIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBzdHlsZT0iYm9yZGVyOjA7IiBhbGxvd2Z1bGxzY3JlZW49IiIgbG9hZGluZz0ibGF6eSIgcmVmZXJyZXJwb2xpY3k9Im5vLXJlZmVycmVyLXdoZW4tZG93bmdyYWRlIj48L2lmcmFtZT4=',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'U2lsdmVyIFZhbGxleSBVbmlmaWVkIFNjaG9vbCBEaXN0cmljdCwgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'My02ZA==',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'Tm8gVm91Y2hlciBuZWNlc3Nhcnk=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToxOntpOjA7czo0MDoiR2VhciBhbmQgZXF1aXBtZW50IHNhbml0aXplZCBiZXR3ZWVuIHVzZSI7fQ==',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'SW5zdGFudCBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToxOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjt9',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToxOntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO30=',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_adventure_id, $tax_term_things_to_do_id ),
	),
));

$cpt_cielo_balloons_temecula_id = td_demo_content::add_cpt( array(
	'title' => 'Cielo Balloons Temecula',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_7',
	'post_meta' => array( 
		'tdcf_price' => 'MTUwJA==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDg1MTM0OS4zMDAzMjUxNDc4ITJkLTExOC4xOTM5MDk4Njg3NSEzZDMzLjUzODIyMDU5OTk5OTk5NSEybTMhMWYwITJmMCEzZjAhM20yITFpMTAyNCEyaTc2OCE0ZjEzLjEhM20zITFtMiExczB4ODBkYjgxYmVkODhiYzUwMyUzQTB4M2JlMTE4YWFlY2Q1MjI4ITJzQ2llbG8lMjBCYWxsb29ucyUyMFRlbWVjdWxhITVlMCEzbTIhMXNlbiEyc3JvITR2MTY3ODg4ODY2OTU3OCE1bTIhMXNlbiEyc3JvIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBzdHlsZT0iYm9yZGVyOjA7IiBhbGxvd2Z1bGxzY3JlZW49IiIgbG9hZGluZz0ibGF6eSIgcmVmZXJyZXJwb2xpY3k9Im5vLXJlZmVycmVyLXdoZW4tZG93bmdyYWRlIj48L2lmcmFtZT4=',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'Mzk3MzAgQ2FsbGUgQ29udGVudG8sIFRlbWVjdWxhLCBDYWxpZm9ybmlh',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mmhy',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'TW9iaWxlIFZvdWNoZXI=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'Tm8gUGFya2luZyBhdmFpbGFibGU=',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6MzU6IlZlaGljbGVzIGNsZWFuZWQgd2l0aCBkaXNpbmZlY3RhbnRzIjt9',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'SW5zdGFudCBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czoxNDoiTWFza3MgcmVxdWlyZWQiO30=',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToyOntpOjA7czoxOToiTWFza3Mgd29ybiBieSBzdGFmZiI7aToxO3M6NDY6IkNvbnRhY3RsZXNzIHBheW1lbnQgZm9yIGdyYXR1aXRpZXMgYW5kIGFkZC1vbnMiO30=',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_adventure_id, $tax_term_things_to_do_id ),
	),
));

$cpt_california_canoe_kayak_brooklyn_basin_id = td_demo_content::add_cpt( array(
	'title' => 'California Canoe &amp; Kayak Brooklyn Basin',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_8',
	'post_meta' => array( 
		'tdcf_price' => 'MzAtNTAk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDc5ODUxMy4wNDIxOTEwNjcxITJkLTEyNC40NTIzOTE2MTU3MDEzMyEzZDM4LjU3NTQzNjA2NzgxOTY0ITJtMyExZjAhMmYwITNmMCEzbTIhMWkxMDI0ITJpNzY4ITRmMTMuMSEzbTMhMW0yITFzMHg4MDhmODcxNDk1NjhiNzhmJTNBMHhmODgwNTcxZTEyZWUwM2Q3ITJzQ2FsaWZvcm5pYSUyMENhbm9lJTIwJTI2JTIwS2F5YWslMjBCcm9va2x5biUyMEJhc2luITVlMCEzbTIhMXNlbiEyc3JvITR2MTY3ODg4ODQ0NTE4MCE1bTIhMXNlbiEyc3JvIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBzdHlsZT0iYm9yZGVyOjA7IiBhbGxvd2Z1bGxzY3JlZW49IiIgbG9hZGluZz0ibGF6eSIgcmVmZXJyZXJwb2xpY3k9Im5vLXJlZmVycmVyLXdoZW4tZG93bmdyYWRlIj48L2lmcmFtZT4=',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'Mjg4IDl0aCBBdmUgU3VpdGUgQywgT2FrbGFuZCwgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'Tm8gY2FuY2VsbGF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mi01aHI=',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'UGh5c2ljYWwgVm91Y2hlcg==',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'UGFpZCBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6Mzg6IlN0YWZmIHJlcXVpcmVkIHRvIHJlZ3VsYXJseSB3YXNoIGhhbmRzIjt9',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'SW5zdGFudCBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czo0MzoiSW5kaXZpZHVhbGx5IHdyYXBwZWQgZm9vZCBvcHRpb25zIGF2YWlsYWJsZSI7fQ==',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YTozOntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjE5OiJNYXNrcyB3b3JuIGJ5IHN0YWZmIjtpOjI7czo0NjoiQ29udGFjdGxlc3MgcGF5bWVudCBmb3IgZ3JhdHVpdGllcyBhbmQgYWRkLW9ucyI7fQ==',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_adventure_id, $tax_term_things_to_do_id ),
	),
));

$cpt_the_peak_of_fremont_id = td_demo_content::add_cpt( array(
	'title' => 'The Peak of Fremont',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_9',
	'post_meta' => array( 
		'tdcf_price' => 'MzAk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDc5ODUwNy4yMTQxNTE4ODUyITJkLTEyNC40NTIzODkyNDIyMTcwMSEzZDM4LjU3NTk2MDM3MDQ5NzkhMm0zITFmMCEyZjAhM2YwITNtMiExaTEwMjQhMmk3NjghNGYxMy4xITNtMyExbTIhMXMweDgwOGZjNzFlZTJjNmQxMzUlM0EweDc1MDE2MDI3MjQ3NGU4MzghMnNUaGUlMjBQZWFrJTIwb2YlMjBGcmVtb250ITVlMCEzbTIhMXNlbiEyc3JvITR2MTY3ODg4ODMwNTcxNCE1bTIhMXNlbiEyc3JvIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBzdHlsZT0iYm9yZGVyOjA7IiBhbGxvd2Z1bGxzY3JlZW49IiIgbG9hZGluZz0ibGF6eSIgcmVmZXJyZXJwb2xpY3k9Im5vLXJlZmVycmVyLXdoZW4tZG93bmdyYWRlIj48L2lmcmFtZT4=',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'NDAyMCBUZWNobm9sb2d5IFBsIFN1aXRlIDEsIEZyZW1vbnQsIENhbGlmb3JuaWE=',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'Tm8gY2FuY2VsbGF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'MS0yaHI=',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'UGh5c2ljYWwgVm91Y2hlcg==',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6NDA6IkdlYXIgYW5kIGVxdWlwbWVudCBzYW5pdGl6ZWQgYmV0d2VlbiB1c2UiO30=',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Tm8gY29uZmlybWF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czo0ODoiUGFpZCBzdGF5LWF0LWhvbWUgcG9saWN5IGZvciBzdGFmZiB3aXRoIHN5bXB0b21zIjtpOjE7czo0MzoiSW5kaXZpZHVhbGx5IHdyYXBwZWQgZm9vZCBvcHRpb25zIGF2YWlsYWJsZSI7fQ==',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToyOntpOjA7czoxOToiTWFza3Mgd29ybiBieSBzdGFmZiI7aToxO3M6NDY6IkNvbnRhY3RsZXNzIHBheW1lbnQgZm9yIGdyYXR1aXRpZXMgYW5kIGFkZC1vbnMiO30=',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_adventure_id, $tax_term_things_to_do_id ),
	),
));

$cpt_salt_point_state_park_id = td_demo_content::add_cpt( array(
	'title' => 'Salt Point State Park',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_10',
	'post_meta' => array( 
		'tdcf_price' => 'MTAtNTAk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDc5ODUwMS4zODU3NDc2NjYhMmQtMTI0LjQ1MjM4Njg2ODc1ITNkMzguNTc2NDg0Njk5OTk5OTk1ITJtMyExZjAhMmYwITNmMCEzbTIhMWkxMDI0ITJpNzY4ITRmMTMuMSEzbTMhMW0yITFzMHg4MDg2YjBlZWEyNzk5ZWYzJTNBMHhmZDM4MjljYWE4ZjQ0OGQzITJzU2FsdCUyMFBvaW50JTIwU3RhdGUlMjBQYXJrITVlMCEzbTIhMXNlbiEyc3JvITR2MTY3ODg4ODE5ODk4OSE1bTIhMXNlbiEyc3JvIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBzdHlsZT0iYm9yZGVyOjA7IiBhbGxvd2Z1bGxzY3JlZW49IiIgbG9hZGluZz0ibGF6eSIgcmVmZXJyZXJwb2xpY3k9Im5vLXJlZmVycmVyLXdoZW4tZG93bmdyYWRlIj48L2lmcmFtZT4=',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'MjUwNTAgQ0EtMSwgSmVubmVyLCBDYWxpZm9ybmlh',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mi01ZA==',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'Tm8gVm91Y2hlciBuZWNlc3Nhcnk=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YTozOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6MzU6IlZlaGljbGVzIGNsZWFuZWQgd2l0aCBkaXNpbmZlY3RhbnRzIjtpOjI7czozODoiU3RhZmYgcmVxdWlyZWQgdG8gcmVndWxhcmx5IHdhc2ggaGFuZHMiO30=',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'SW5zdGFudCBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czoxNDoiTWFza3MgcmVxdWlyZWQiO30=',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToyOntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjIzOiJIYW5kIHNhbml0aXplciBwcm92aWRlZCI7fQ==',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_adventure_id, $tax_term_things_to_do_id ),
	),
));

$cpt_river_city_queen_id = td_demo_content::add_cpt( array(
	'title' => 'River City Queen',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_price' => 'MjUwJA==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDgxMTk1My41Njg1MTA3NzExITJkLTEyMi4wNTI3MjIzMTM0MDc2MyEzZDM3LjM0OTc2MTM0ODMyNDA2ITJtMyExZjAhMmYwITNmMCEzbTIhMWkxMDI0ITJpNzY4ITRmMTMuMSEzbTMhMW0yITFzMHg4MDlhZDE4MzlkMGViNWNiJTNBMHg3YWYyZDQ3ZWQyMjk3M2Q4ITJzUml2ZXIlMjBDaXR5JTIwUXVlZW4hNWUwITNtMiExc2VuITJzcm8hNHYxNjc4ODg4MDUwMDg0ITVtMiExc2VuITJzcm8iIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHN0eWxlPSJib3JkZXI6MDsiIGFsbG93ZnVsbHNjcmVlbj0iIiBsb2FkaW5nPSJsYXp5IiByZWZlcnJlcnBvbGljeT0ibm8tcmVmZXJyZXItd2hlbi1kb3duZ3JhZGUiPjwvaWZyYW1lPg==',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'MTExMCBGcm9udCBTdCwgU2FjcmFtZW50bywgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mmhy',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'UGh5c2ljYWwgVm91Y2hlcg==',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'UGFpZCBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6NDA6IkdlYXIgYW5kIGVxdWlwbWVudCBzYW5pdGl6ZWQgYmV0d2VlbiB1c2UiO30=',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Mi0zIEJ1c2luZXNzIERheSBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czoxNDoiTWFza3MgcmVxdWlyZWQiO2k6MTtzOjQ5OiJQcm90ZWN0aXZlIHNoaWVsZHMgaW4gcGxhY2UgYXQgbWFpbiBjb250YWN0IGFyZWFzIjt9',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToyOntpOjA7czoxOToiTWFza3Mgd29ybiBieSBzdGFmZiI7aToxO3M6MzU6IlNvY2lhbCBkaXN0YW5jaW5nIG1lYXN1cmVzIGluIHBsYWNlIjt9',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_cruises_id, $tax_term_things_to_do_id ),
	),
));

$cpt_spa_on_the_water_cruises_id = td_demo_content::add_cpt( array(
	'title' => 'Spa On The Water Cruises',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_price' => 'NTAwJA==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDgxMTk1My41Njg1MTA3NzExITJkLTEyMi4wNTI3MjIzMTM0MDc2MyEzZDM3LjM0OTc2MTM0ODMyNDA2ITJtMyExZjAhMmYwITNmMCEzbTIhMWkxMDI0ITJpNzY4ITRmMTMuMSEzbTMhMW0yITFzMHg4MDkwMDM2YzgxZmU4MmZmJTNBMHg0ZjBhMzg0ZWUyNDg0YjM5ITJzU3BhJTIwT24lMjBUaGUlMjBXYXRlciUyMENydWlzZXMhNWUwITNtMiExc2VuITJzcm8hNHYxNjc4ODg3OTcxMzI0ITVtMiExc2VuITJzcm8iIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHN0eWxlPSJib3JkZXI6MDsiIGFsbG93ZnVsbHNjcmVlbj0iIiBsb2FkaW5nPSJsYXp5IiByZWZlcnJlcnBvbGljeT0ibm8tcmVmZXJyZXItd2hlbi1kb3duZ3JhZGUiPjwvaWZyYW1lPg==',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'NTg3OSBNYXJpbmEgUmQsIERpc2NvdmVyeSBCYXksIENhbGlmb3JuaWE=',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'Tm8gY2FuY2VsbGF0aW9uIGF2YWlsYWJsZQ==',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'M2hy',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'Tm8gVm91Y2hlciBuZWNlc3Nhcnk=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'UGFpZCBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNToiVmVoaWNsZXMgY2xlYW5lZCB3aXRoIGRpc2luZmVjdGFudHMiO2k6MTtzOjQwOiJHZWFyIGFuZCBlcXVpcG1lbnQgc2FuaXRpemVkIGJldHdlZW4gdXNlIjt9',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Tm8gY29uZmlybWF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czo0MzoiSW5kaXZpZHVhbGx5IHdyYXBwZWQgZm9vZCBvcHRpb25zIGF2YWlsYWJsZSI7fQ==',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToyOntpOjA7czoyMzoiSGFuZCBzYW5pdGl6ZXIgcHJvdmlkZWQiO2k6MTtzOjQ2OiJDb250YWN0bGVzcyBwYXltZW50IGZvciBncmF0dWl0aWVzIGFuZCBhZGQtb25zIjt9',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_cruises_id, $tax_term_things_to_do_id ),
	),
));

$cpt_city_cruises_sacramento_id = td_demo_content::add_cpt( array(
	'title' => 'City Cruises Sacramento',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_price' => 'NDUwJA==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDgxMTk1My41Njg1MTA3NzExITJkLTEyMi4wNTI3MjIzMTM0MDc2MyEzZDM3LjM0OTc2MTM0ODMyNDA2ITJtMyExZjAhMmYwITNmMCEzbTIhMWkxMDI0ITJpNzY4ITRmMTMuMSEzbTMhMW0yITFzMHg4MDlhZDEzMzM1NWIxYmU5JTNBMHhjYjUxOTQ1M2Y3OGRmNjI4ITJzQ2l0eSUyMENydWlzZXMlMjBTYWNyYW1lbnRvITVlMCEzbTIhMXNlbiEyc3JvITR2MTY3ODg4Nzg3MTM3MSE1bTIhMXNlbiEyc3JvIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBzdHlsZT0iYm9yZGVyOjA7IiBhbGxvd2Z1bGxzY3JlZW49IiIgbG9hZGluZz0ibGF6eSIgcmVmZXJyZXJwb2xpY3k9Im5vLXJlZmVycmVyLXdoZW4tZG93bmdyYWRlIj48L2lmcmFtZT4=',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'MTIwNiBGcm9udCBTdCwgU2FjcmFtZW50bywgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'My02aHI=',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'TW9iaWxlIFZvdWNoZXI=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'Tm8gUGFya2luZyBhdmFpbGFibGU=',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6Mzg6IlN0YWZmIHJlcXVpcmVkIHRvIHJlZ3VsYXJseSB3YXNoIGhhbmRzIjt9',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'MjRIciBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czo0ODoiUGFpZCBzdGF5LWF0LWhvbWUgcG9saWN5IGZvciBzdGFmZiB3aXRoIHN5bXB0b21zIjtpOjE7czo0OToiUHJvdGVjdGl2ZSBzaGllbGRzIGluIHBsYWNlIGF0IG1haW4gY29udGFjdCBhcmVhcyI7fQ==',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToyOntpOjA7czo0NjoiQ29udGFjdGxlc3MgcGF5bWVudCBmb3IgZ3JhdHVpdGllcyBhbmQgYWRkLW9ucyI7aToxO3M6MzU6IlNvY2lhbCBkaXN0YW5jaW5nIG1lYXN1cmVzIGluIHBsYWNlIjt9',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_cruises_id, $tax_term_things_to_do_id ),
	),
));

$cpt_the_cruise_experts_agency_id = td_demo_content::add_cpt( array(
	'title' => 'The Cruise Experts Agency',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_price' => 'MzQwJA==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDgxMTk1My41Njg1MTA3NzExITJkLTEyMi4wNTI3MjIzMTM0MDc2MyEzZDM3LjM0OTc2MTM0ODMyNDA2ITJtMyExZjAhMmYwITNmMCEzbTIhMWkxMDI0ITJpNzY4ITRmMTMuMSEzbTMhMW0yITFzMHg4MDk1MmY2NTA1YjAwMDAxJTNBMHg4YjJjNDE3MjJiZWJmZTU5ITJzVGhlJTIwQ3J1aXNlJTIwRXhwZXJ0cyUyMEFnZW5jeSE1ZTAhM20yITFzZW4hMnNybyE0djE2Nzg4ODc3MTYyMzUhNW0yITFzZW4hMnNybyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgc3R5bGU9ImJvcmRlcjowOyIgYWxsb3dmdWxsc2NyZWVuPSIiIGxvYWRpbmc9ImxhenkiIHJlZmVycmVycG9saWN5PSJuby1yZWZlcnJlci13aGVuLWRvd25ncmFkZSI+PC9pZnJhbWU+',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'MTExIFMgQ291cnQgU3QgIzEwMiwgVmlzYWxpYSwgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'My02aHI=',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'TW9iaWxlIFZvdWNoZXI=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'Tm8gUGFya2luZyBhdmFpbGFibGU=',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozODoiU3RhZmYgcmVxdWlyZWQgdG8gcmVndWxhcmx5IHdhc2ggaGFuZHMiO2k6MTtzOjQwOiJHZWFyIGFuZCBlcXVpcG1lbnQgc2FuaXRpemVkIGJldHdlZW4gdXNlIjt9',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Mi0zIEJ1c2luZXNzIERheSBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YTozOntpOjA7czoxNDoiTWFza3MgcmVxdWlyZWQiO2k6MTtzOjQzOiJJbmRpdmlkdWFsbHkgd3JhcHBlZCBmb29kIG9wdGlvbnMgYXZhaWxhYmxlIjtpOjI7czo0OToiUHJvdGVjdGl2ZSBzaGllbGRzIGluIHBsYWNlIGF0IG1haW4gY29udGFjdCBhcmVhcyI7fQ==',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YTozOntpOjA7czoxOToiTWFza3Mgd29ybiBieSBzdGFmZiI7aToxO3M6MzU6IlNvY2lhbCBkaXN0YW5jaW5nIG1lYXN1cmVzIGluIHBsYWNlIjtpOjI7czoyOToiQ29udGFjdGxlc3MgdGlja2V0IHJlZGVtcHRpb24iO30=',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_cruises_id, $tax_term_things_to_do_id ),
	),
));

$cpt_city_cruises_berkeley_id = td_demo_content::add_cpt( array(
	'title' => 'City Cruises Berkeley',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_price' => 'NDUwJA==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDgxMTk1My41Njg1MTA3NzExITJkLTEyMi4wNTI3MjIzMTM0MDc2MyEzZDM3LjM0OTc2MTM0ODMyNDA2ITJtMyExZjAhMmYwITNmMCEzbTIhMWkxMDI0ITJpNzY4ITRmMTMuMSEzbTMhMW0yITFzMHg4MDg1N2YyNmUzNjVkZDRkJTNBMHgyMTFmMzU5ODQ1YzY1YWU2ITJzQ2l0eSUyMENydWlzZXMlMjBCZXJrZWxleSE1ZTAhM20yITFzZW4hMnNybyE0djE2Nzg4ODc2MTQxODkhNW0yITFzZW4hMnNybyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgc3R5bGU9ImJvcmRlcjowOyIgYWxsb3dmdWxsc2NyZWVuPSIiIGxvYWRpbmc9ImxhenkiIHJlZmVycmVycG9saWN5PSJuby1yZWZlcnJlci13aGVuLWRvd25ncmFkZSI+PC9pZnJhbWU+',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'MjAwIE1hcmluYSBCbHZkLCBCZXJrZWxleSwgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'My03aHI=',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'UGh5c2ljYWwgVm91Y2hlcg==',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'UGFpZCBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6Mzg6IlN0YWZmIHJlcXVpcmVkIHRvIHJlZ3VsYXJseSB3YXNoIGhhbmRzIjt9',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'MjRIciBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czoxNDoiTWFza3MgcmVxdWlyZWQiO30=',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YTozOntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjIzOiJIYW5kIHNhbml0aXplciBwcm92aWRlZCI7aToyO3M6MTk6Ik1hc2tzIHdvcm4gYnkgc3RhZmYiO30=',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_cruises_id, $tax_term_things_to_do_id ),
	),
));

$cpt_nepenthe_cafe_id = td_demo_content::add_cpt( array(
	'title' => 'Nepenthe Cafe',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_6',
	'post_meta' => array( 
		'tdcf_price' => 'MTAtNjAk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNCExbTghMW0zITFkODExOTQ4LjU5NzMxODM0MzkhMmQtMTIyLjA1MjcxOTkhM2QzNy4zNTAyMjEhM20yITFpMTAyNCEyaTc2OCE0ZjEzLjEhM20zITFtMiExczB4ODA4ZDg0NWEwYWVkZmM0MyUzQTB4YmJjMTdkZDAwM2Q5NDI0NCEyc05lcGVudGhlITVlMCEzbTIhMXNlbiEyc3JvITR2MTY3ODg4NzQzMzUwMiE1bTIhMXNlbiEyc3JvIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBzdHlsZT0iYm9yZGVyOjA7IiBhbGxvd2Z1bGxzY3JlZW49IiIgbG9hZGluZz0ibGF6eSIgcmVmZXJyZXJwb2xpY3k9Im5vLXJlZmVycmVyLXdoZW4tZG93bmdyYWRlIj48L2lmcmFtZT4=',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'NDg1MTAgQ0EtMSwgQmlnIFN1ciwgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mmhy',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'TW9iaWxlIFZvdWNoZXI=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6Mzg6IlN0YWZmIHJlcXVpcmVkIHRvIHJlZ3VsYXJseSB3YXNoIGhhbmRzIjt9',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'SW5zdGFudCBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czo0OToiUHJvdGVjdGl2ZSBzaGllbGRzIGluIHBsYWNlIGF0IG1haW4gY29udGFjdCBhcmVhcyI7fQ==',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YTo0OntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjQ2OiJDb250YWN0bGVzcyBwYXltZW50IGZvciBncmF0dWl0aWVzIGFuZCBhZGQtb25zIjtpOjI7czozNToiU29jaWFsIGRpc3RhbmNpbmcgbWVhc3VyZXMgaW4gcGxhY2UiO2k6MztzOjI5OiJDb250YWN0bGVzcyB0aWNrZXQgcmVkZW1wdGlvbiI7fQ==',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_food_drink_id, $tax_term_things_to_do_id ),
	),
));

$cpt_harris_ranch_inn_restaurant_id = td_demo_content::add_cpt( array(
	'title' => 'Harris Ranch Inn &amp; Restaurant',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_7',
	'post_meta' => array( 
		'tdcf_price' => 'NDAtMTAwJA==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNCExbTghMW0zITFkODExOTQ4LjU5NzMxODM0MzkhMmQtMTIyLjA1MjcxOTkhM2QzNy4zNTAyMjEhM20yITFpMTAyNCEyaTc2OCE0ZjEzLjEhM20zITFtMiExczB4ODA5MzYwYjk4NGQ3MzVmZCUzQTB4OTgzMGM0OTI2MDVjZjlhYSEyc0hhcnJpcyUyMFJhbmNoJTIwSW5uJTIwJTI2JTIwUmVzdGF1cmFudCE1ZTAhM20yITFzZW4hMnNybyE0djE2Nzg4ODY4NDc0ODchNW0yITFzZW4hMnNybyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgc3R5bGU9ImJvcmRlcjowOyIgYWxsb3dmdWxsc2NyZWVuPSIiIGxvYWRpbmc9ImxhenkiIHJlZmVycmVycG9saWN5PSJuby1yZWZlcnJlci13aGVuLWRvd25ncmFkZSI+PC9pZnJhbWU+',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'MjQ1MDUgVyBEb3JyaXMgQXZlLCBDb2FsaW5nYSwgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mmhy',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'TW9iaWxlIFZvdWNoZXI=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6MzU6IlZlaGljbGVzIGNsZWFuZWQgd2l0aCBkaXNpbmZlY3RhbnRzIjt9',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Mi0zIEJ1c2luZXNzIERheSBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czo0ODoiUGFpZCBzdGF5LWF0LWhvbWUgcG9saWN5IGZvciBzdGFmZiB3aXRoIHN5bXB0b21zIjt9',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YTozOntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjIzOiJIYW5kIHNhbml0aXplciBwcm92aWRlZCI7aToyO3M6NDY6IkNvbnRhY3RsZXNzIHBheW1lbnQgZm9yIGdyYXR1aXRpZXMgYW5kIGFkZC1vbnMiO30=',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_food_drink_id, $tax_term_things_to_do_id ),
	),
));

$cpt_bridges_restaurant_and_bar_id = td_demo_content::add_cpt( array(
	'title' => 'Bridges Restaurant and Bar',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_8',
	'post_meta' => array( 
		'tdcf_price' => 'MTUkLTUwJA==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDgxMTk0OC41OTcxMzI3MDU3ITJkLTEyMi4wNTI3MTk4NzU0MDM1NSEzZDM3LjM1MDIyMTAxNzE2NDU4NiEybTMhMWYwITJmMCEzZjAhM20yITFpMTAyNCEyaTc2OCE0ZjEzLjEhM20zITFtMiExczB4ODA4ZjhjYWJkYjc1ZWIzOSUzQTB4OWNkOGI1NDU1OGVhYmY0MCEyc0JyaWRnZXMlMjBSZXN0YXVyYW50JTIwYW5kJTIwQmFyITVlMCEzbTIhMXNlbiEyc3JvITR2MTY3ODg2MTY3MDg3OSE1bTIhMXNlbiEyc3JvIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBzdHlsZT0iYm9yZGVyOjA7IiBhbGxvd2Z1bGxzY3JlZW49IiIgbG9hZGluZz0ibGF6eSIgcmVmZXJyZXJwb2xpY3k9Im5vLXJlZmVycmVyLXdoZW4tZG93bmdyYWRlIj48L2lmcmFtZT4=',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'NDQgQ2h1cmNoIFN0LCBEYW52aWxsZSwgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mmhy',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'TW9iaWxlIFZvdWNoZXI=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'Tm8gUGFya2luZyBhdmFpbGFibGU=',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6MzU6IlZlaGljbGVzIGNsZWFuZWQgd2l0aCBkaXNpbmZlY3RhbnRzIjt9',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Mi0zIEJ1c2luZXNzIERheSBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YTozOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czoxNDoiTWFza3MgcmVxdWlyZWQiO2k6MjtzOjQ4OiJQYWlkIHN0YXktYXQtaG9tZSBwb2xpY3kgZm9yIHN0YWZmIHdpdGggc3ltcHRvbXMiO30=',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YTo0OntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjIzOiJIYW5kIHNhbml0aXplciBwcm92aWRlZCI7aToyO3M6MzU6IlNvY2lhbCBkaXN0YW5jaW5nIG1lYXN1cmVzIGluIHBsYWNlIjtpOjM7czoyOToiQ29udGFjdGxlc3MgdGlja2V0IHJlZGVtcHRpb24iO30=',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_food_drink_id, $tax_term_things_to_do_id ),
	),
));

$cpt_lucky_buck_cafe_id = td_demo_content::add_cpt( array(
	'title' => 'Lucky Buck Cafe',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_9',
	'post_meta' => array( 
		'tdcf_price' => 'MTUtNDUk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDgxNjQ3My4xNTI4Njc3NDU1ITJkLTEyMi4xMTMxNDQ2ODAwOTEwNSEzZDM2LjkyOTg0NDUwMDY1NDg2NiEybTMhMWYwITJmMCEzZjAhM20yITFpMTAyNCEyaTc2OCE0ZjEzLjEhM20zITFtMiExczB4ODA5NmQ3MzdhNGE5MzgwNSUzQTB4NzM5NmUyZjQzMjU1YWRmMCEyc0x1Y2t5JTIwQnVjayUyMENhZmUhNWUwITNtMiExc2VuITJzcm8hNHYxNjc4ODYxNTA2OTQzITVtMiExc2VuITJzcm8iIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHN0eWxlPSJib3JkZXI6MDsiIGFsbG93ZnVsbHNjcmVlbj0iIiBsb2FkaW5nPSJsYXp5IiByZWZlcnJlcnBvbGljeT0ibm8tcmVmZXJyZXItd2hlbi1kb3duZ3JhZGUiPjwvaWZyYW1lPg==',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'NzY0NyBDQS0xMjAsIEdyb3ZlbGFuZCwgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mmhy',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'Tm8gVm91Y2hlciBuZWNlc3Nhcnk=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToxOntpOjA7czo0MDoiR2VhciBhbmQgZXF1aXBtZW50IHNhbml0aXplZCBiZXR3ZWVuIHVzZSI7fQ==',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'SW5zdGFudCBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czo0MzoiSW5kaXZpZHVhbGx5IHdyYXBwZWQgZm9vZCBvcHRpb25zIGF2YWlsYWJsZSI7fQ==',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YTozOntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjIzOiJIYW5kIHNhbml0aXplciBwcm92aWRlZCI7aToyO3M6MzU6IlNvY2lhbCBkaXN0YW5jaW5nIG1lYXN1cmVzIGluIHBsYWNlIjt9',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_food_drink_id, $tax_term_things_to_do_id ),
	),
));

$cpt_california_cocina_id = td_demo_content::add_cpt( array(
	'title' => 'California Cocina',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_10',
	'post_meta' => array( 
		'tdcf_price' => 'NDUk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDE0OTgzOS4yMjczODQyOTQ0NCEyZC0xMTguMTcxMTE1MzY1NjQ2NTkhM2QzMy45MTUxMzExMjUxOTIyOTYhMm0zITFmMCEyZjAhM2YwITNtMiExaTEwMjQhMmk3NjghNGYxMy4xITNtMyExbTIhMXMweDgwZGQyZjE2NGJiMTlkODElM0EweDhmMThhYjIyMDI3YjBiMDchMnNDYWxpZm9ybmlhJTIwQ29jaW5hITVlMCEzbTIhMXNlbiEyc3JvITR2MTY3ODg2MTMxNzExNiE1bTIhMXNlbiEyc3JvIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBzdHlsZT0iYm9yZGVyOjA7IiBhbGxvd2Z1bGxzY3JlZW49IiIgbG9hZGluZz0ibGF6eSIgcmVmZXJyZXJwb2xpY3k9Im5vLXJlZmVycmVyLXdoZW4tZG93bmdyYWRlIj48L2lmcmFtZT4=',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'MTA5MDAgTG9zIEFsYW1pdG9zIEJsdmQgdW5pdCAxMDEsIExvcyBBbGFtaXRvcywgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mmhy',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'Tm8gVm91Y2hlciBuZWNlc3Nhcnk=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToxOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7fQ==',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'MjRIciBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czo0OToiUHJvdGVjdGl2ZSBzaGllbGRzIGluIHBsYWNlIGF0IG1haW4gY29udGFjdCBhcmVhcyI7fQ==',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YTozOntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjIzOiJIYW5kIHNhbml0aXplciBwcm92aWRlZCI7aToyO3M6MTk6Ik1hc2tzIHdvcm4gYnkgc3RhZmYiO30=',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_food_drink_id, $tax_term_things_to_do_id ),
	),
));

$cpt_pacific_grove_museum_of_natural_history_id = td_demo_content::add_cpt( array(
	'title' => 'Pacific Grove Museum of Natural History',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_price' => 'MjUk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNCExbTghMW0zITFkMjU2MTcuNDk1MDIyOTk1Mzk3ITJkLTEyMS45NTIyMDAzMjA4OTg0MyEzZDM2LjYyMTg4ODEhM20yITFpMTAyNCEyaTc2OCE0ZjEzLjEhM20zITFtMiExczB4ODA4ZGUxNGVlYTgxODViNSUzQTB4NzQyMWE5OTg3NzA0YWViZSEyc1BhY2lmaWMlMjBHcm92ZSUyME11c2V1bSUyMG9mJTIwTmF0dXJhbCUyMEhpc3RvcnkhNWUwITNtMiExc2VuITJzcm8hNHYxNjc4ODYxMjAyMjUwITVtMiExc2VuITJzcm8iIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHN0eWxlPSJib3JkZXI6MDsiIGFsbG93ZnVsbHNjcmVlbj0iIiBsb2FkaW5nPSJsYXp5IiByZWZlcnJlcnBvbGljeT0ibm8tcmVmZXJyZXItd2hlbi1kb3duZ3JhZGUiPjwvaWZyYW1lPg==',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'MTY1IEZvcmVzdCBBdmUsIFBhY2lmaWMgR3JvdmUsIENhbGlmb3JuaWE=',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'MWhyIDMwbQ==',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'UGh5c2ljYWwgVm91Y2hlcg==',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToxOntpOjA7czo0MDoiR2VhciBhbmQgZXF1aXBtZW50IHNhbml0aXplZCBiZXR3ZWVuIHVzZSI7fQ==',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Tm8gY29uZmlybWF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czoxNDoiTWFza3MgcmVxdWlyZWQiO30=',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YTozOntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjQ2OiJDb250YWN0bGVzcyBwYXltZW50IGZvciBncmF0dWl0aWVzIGFuZCBhZGQtb25zIjtpOjI7czozNToiU29jaWFsIGRpc3RhbmNpbmcgbWVhc3VyZXMgaW4gcGxhY2UiO30=',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_history_culture_id, $tax_term_things_to_do_id ),
	),
));

$cpt_muzeo_museum_and_cultural_center_id = td_demo_content::add_cpt( array(
	'title' => 'Muzeo Museum and Cultural Center',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_price' => 'MzAk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNCExbTghMW0zITFkMjY1MTMuNDM2MjMyMzM1MDYhMmQtMTE3Ljk0OTEyMTkyMDg5ODQ1ITNkMzMuODMzNDgwNDk5OTk5OTg2ITNtMiExaTEwMjQhMmk3NjghNGYxMy4xITNtMyExbTIhMXMweDgwZGNkNjI1MmJjZjRkNWQlM0EweDkzODY4YjJhNDcyYjFjODUhMnNNdXplbyUyME11c2V1bSUyMGFuZCUyMEN1bHR1cmFsJTIwQ2VudGVyITVlMCEzbTIhMXNlbiEyc3JvITR2MTY3ODg2MTA4NjgxNiE1bTIhMXNlbiEyc3JvIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBzdHlsZT0iYm9yZGVyOjA7IiBhbGxvd2Z1bGxzY3JlZW49IiIgbG9hZGluZz0ibGF6eSIgcmVmZXJyZXJwb2xpY3k9Im5vLXJlZmVycmVyLXdoZW4tZG93bmdyYWRlIj48L2lmcmFtZT4=',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'MjQxIFMgQW5haGVpbSBCbHZkLCBBbmFoZWltLCBDYWxpZm9ybmlh',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'Tm8gY2FuY2VsbGF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mmhy',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'UGh5c2ljYWwgVm91Y2hlcg==',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'UGFpZCBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6NDA6IkdlYXIgYW5kIGVxdWlwbWVudCBzYW5pdGl6ZWQgYmV0d2VlbiB1c2UiO30=',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'SW5zdGFudCBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czoxNDoiTWFza3MgcmVxdWlyZWQiO30=',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToyOntpOjA7czo0NjoiQ29udGFjdGxlc3MgcGF5bWVudCBmb3IgZ3JhdHVpdGllcyBhbmQgYWRkLW9ucyI7aToxO3M6Mjk6IkNvbnRhY3RsZXNzIHRpY2tldCByZWRlbXB0aW9uIjt9',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_history_culture_id, $tax_term_things_to_do_id ),
	),
));

$cpt_blackhawk_museum_id = td_demo_content::add_cpt( array(
	'title' => 'Blackhawk Museum',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_price' => 'MTAtMjAk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNCExbTghMW0zITFkMjUyMjAuMTM3ODUxMzY3OTU2ITJkLTEyMS45MzgwMDk1NzQ5NDYwNSEzZDM3LjgwMTM1MjE5OTk5OTk5ITNtMiExaTEwMjQhMmk3NjghNGYxMy4xITNtMyExbTIhMXMweDgwOGY4Y2E5YTFlODc5NjUlM0EweDVhNmMxZmJkZTFiNjQ2ZTchMnNCbGFja2hhd2slMjBNdXNldW0hNWUwITNtMiExc2VuITJzcm8hNHYxNjc4ODYwNzY3ODc2ITVtMiExc2VuITJzcm8iIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHN0eWxlPSJib3JkZXI6MDsiIGFsbG93ZnVsbHNjcmVlbj0iIiBsb2FkaW5nPSJsYXp5IiByZWZlcnJlcnBvbGljeT0ibm8tcmVmZXJyZXItd2hlbi1kb3duZ3JhZGUiPjwvaWZyYW1lPg==',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'MzcwMCBCbGFja2hhd2sgUGxhemEgQ2lyLCBEYW52aWxsZSwgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'Tm8gY2FuY2VsbGF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'MmhyIDM1bQ==',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'UGh5c2ljYWwgVm91Y2hlcg==',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'UGFpZCBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YTozOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6MzU6IlZlaGljbGVzIGNsZWFuZWQgd2l0aCBkaXNpbmZlY3RhbnRzIjtpOjI7czo0MDoiR2VhciBhbmQgZXF1aXBtZW50IHNhbml0aXplZCBiZXR3ZWVuIHVzZSI7fQ==',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Tm8gY29uZmlybWF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czo0ODoiUGFpZCBzdGF5LWF0LWhvbWUgcG9saWN5IGZvciBzdGFmZiB3aXRoIHN5bXB0b21zIjt9',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YTo0OntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjIzOiJIYW5kIHNhbml0aXplciBwcm92aWRlZCI7aToyO3M6MTk6Ik1hc2tzIHdvcm4gYnkgc3RhZmYiO2k6MztzOjI5OiJDb250YWN0bGVzcyB0aWNrZXQgcmVkZW1wdGlvbiI7fQ==',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_history_culture_id, $tax_term_things_to_do_id ),
	),
));

$cpt_hollywood_museum_id = td_demo_content::add_cpt( array(
	'title' => 'The Hollywood Museum',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_price' => 'NS0xNSQ=',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNCExbTghMW0zITFkNjYwNy41MzU2OTYzMDkwOTIhMmQtMTE4LjM0NDU4MjMyOTEwOTI5ITNkMzQuMTAxMDg3MTk5OTk5OTghM20yITFpMTAyNCEyaTc2OCE0ZjEzLjEhM20zITFtMiExczB4ODBjMmJmMjM4ZTdmNTQ2YiUzQTB4ZjgxYjMwY2JlZjAwMzA1NiEyc1RoZSUyMEhvbGx5d29vZCUyME11c2V1bSE1ZTAhM20yITFzZW4hMnNybyE0djE2Nzg4NjA0OTY2MDAhNW0yITFzZW4hMnNybyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgc3R5bGU9ImJvcmRlcjowOyIgYWxsb3dmdWxsc2NyZWVuPSIiIGxvYWRpbmc9ImxhenkiIHJlZmVycmVycG9saWN5PSJuby1yZWZlcnJlci13aGVuLWRvd25ncmFkZSI+PC9pZnJhbWU+',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'MTY2MCBOIEhpZ2hsYW5kIEF2ZSwgSG9sbHl3b29kLCBDQSA5MDAyOCwgVW5pdGVkIFN0YXRlcw==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'MmhyIDMwbQ==',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'TW9iaWxlIFZvdWNoZXI=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6NDA6IkdlYXIgYW5kIGVxdWlwbWVudCBzYW5pdGl6ZWQgYmV0d2VlbiB1c2UiO30=',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Tm8gY29uZmlybWF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YTozOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czo0ODoiUGFpZCBzdGF5LWF0LWhvbWUgcG9saWN5IGZvciBzdGFmZiB3aXRoIHN5bXB0b21zIjtpOjI7czo0OToiUHJvdGVjdGl2ZSBzaGllbGRzIGluIHBsYWNlIGF0IG1haW4gY29udGFjdCBhcmVhcyI7fQ==',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YTo2OntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjIzOiJIYW5kIHNhbml0aXplciBwcm92aWRlZCI7aToyO3M6MTk6Ik1hc2tzIHdvcm4gYnkgc3RhZmYiO2k6MztzOjQ2OiJDb250YWN0bGVzcyBwYXltZW50IGZvciBncmF0dWl0aWVzIGFuZCBhZGQtb25zIjtpOjQ7czozNToiU29jaWFsIGRpc3RhbmNpbmcgbWVhc3VyZXMgaW4gcGxhY2UiO2k6NTtzOjI5OiJDb250YWN0bGVzcyB0aWNrZXQgcmVkZW1wdGlvbiI7fQ==',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_history_culture_id, $tax_term_things_to_do_id ),
	),
));

$cpt_natural_history_museum_of_la_id = td_demo_content::add_cpt( array(
	'title' => 'Natural History Museum of LA',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_price' => 'MTAtMjAk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNCExbTghMW0zITFkMTY1My41NDA3MjA3MjUwNjI2ITJkLTExOC4yODc3MDUzITNkMzQuMDE2MTIwNCEzbTIhMWkxMDI0ITJpNzY4ITRmMTMuMSEzbTMhMW0yITFzMHg4MGMyYzdmZDA1MTEzMDVmJTNBMHg2ZWY4OTU5NGI5ZmIyNDI3ITJzTmF0dXJhbCUyMEhpc3RvcnklMjBNdXNldW0lMjBvZiUyMExvcyUyMEFuZ2VsZXMlMjBDb3VudHkhNWUwITNtMiExc2VuITJzcm8hNHYxNjc4ODU5MjAxOTQ4ITVtMiExc2VuITJzcm8iIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHN0eWxlPSJib3JkZXI6MDsiIGFsbG93ZnVsbHNjcmVlbj0iIiBsb2FkaW5nPSJsYXp5IiByZWZlcnJlcnBvbGljeT0ibm8tcmVmZXJyZXItd2hlbi1kb3duZ3JhZGUiPjwvaWZyYW1lPg==',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'OTAwIEV4cG9zaXRpb24gQmx2ZCwgTG9zIEFuZ2VsZXMsIENhbGlmb3JuaWE=',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'Tm8gY2FuY2VsbGF0aW9uIGF2YWlsYWJsZQ==',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mi00aHI=',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'UGh5c2ljYWwgVm91Y2hlcg==',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'UGFpZCBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6NDA6IkdlYXIgYW5kIGVxdWlwbWVudCBzYW5pdGl6ZWQgYmV0d2VlbiB1c2UiO30=',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Tm8gY29uZmlybWF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YTo0OntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czoxNDoiTWFza3MgcmVxdWlyZWQiO2k6MjtzOjQ4OiJQYWlkIHN0YXktYXQtaG9tZSBwb2xpY3kgZm9yIHN0YWZmIHdpdGggc3ltcHRvbXMiO2k6MztzOjQ5OiJQcm90ZWN0aXZlIHNoaWVsZHMgaW4gcGxhY2UgYXQgbWFpbiBjb250YWN0IGFyZWFzIjt9',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YTo2OntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjIzOiJIYW5kIHNhbml0aXplciBwcm92aWRlZCI7aToyO3M6MTk6Ik1hc2tzIHdvcm4gYnkgc3RhZmYiO2k6MztzOjQ2OiJDb250YWN0bGVzcyBwYXltZW50IGZvciBncmF0dWl0aWVzIGFuZCBhZGQtb25zIjtpOjQ7czozNToiU29jaWFsIGRpc3RhbmNpbmcgbWVhc3VyZXMgaW4gcGxhY2UiO2k6NTtzOjI5OiJDb250YWN0bGVzcyB0aWNrZXQgcmVkZW1wdGlvbiI7fQ==',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_history_culture_id, $tax_term_things_to_do_id ),
	),
));

$cpt_keller_cliffs_id = td_demo_content::add_cpt( array(
	'title' => 'Keller Cliffs',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_6',
	'post_meta' => array( 
		'tdcf_price' => 'RnJlZQ==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDg0NjM1Ny41NjU1MDY5NTchMmQtMTE4LjMxODAxMTMzNTYxODg2ITNkMzQuMDQxNzEwMTQwOTg0ODghMm0zITFmMCEyZjAhM2YwITNtMiExaTEwMjQhMmk3NjghNGYxMy4xITNtMyExbTIhMXMweDgwYzRhY2MzNzg2ZjNmODklM0EweGI4ZDc3NzM4ZjFkMWIzYWYhMnNLZWxsZXIlMjBDbGlmZnMhNWUwITNtMiExc2VuITJzcm8hNHYxNjc4ODAzNjYyMDg4ITVtMiExc2VuITJzcm8iIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHN0eWxlPSJib3JkZXI6MDsiIGFsbG93ZnVsbHNjcmVlbj0iIiBsb2FkaW5nPSJsYXp5IiByZWZlcnJlcnBvbGljeT0ibm8tcmVmZXJyZXItd2hlbi1kb3duZ3JhZGUiPjwvaWZyYW1lPg==',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'S2VsbGVyIENsaWZmcywgQ2FsaWZvcm5pYSwgVW5pdGVkIFN0YXRlcw==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'Tm8gY2FuY2VsbGF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mi04aHI=',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'Tm8gVm91Y2hlciBuZWNlc3Nhcnk=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'Tm8gUGFya2luZyBhdmFpbGFibGU=',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToxOntpOjA7czo0MDoiR2VhciBhbmQgZXF1aXBtZW50IHNhbml0aXplZCBiZXR3ZWVuIHVzZSI7fQ==',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Tm8gY29uZmlybWF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToxOntpOjA7czo0OToiUHJvdGVjdGl2ZSBzaGllbGRzIGluIHBsYWNlIGF0IG1haW4gY29udGFjdCBhcmVhcyI7fQ==',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToyOntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjIzOiJIYW5kIHNhbml0aXplciBwcm92aWRlZCI7fQ==',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_things_to_do_id, $tax_term_tours_trips_id ),
	),
));

$cpt_white_point_beach_id = td_demo_content::add_cpt( array(
	'title' => 'White Point Beach',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_7',
	'post_meta' => array( 
		'tdcf_price' => 'NS0yNSQ=',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDI3OTAuNzU2ODY4OTA5NDI5ITJkLTExOC4zMTI2Njk1MzgzNDkxITNkMzMuNzE0NDM2MTY3ODA5NjIhMm0zITFmMCEyZjAhM2YwITNtMiExaTEwMjQhMmk3NjghNGYxMy4xITNtMyExbTIhMXMweDgwZGQ0ODMxMDZlMmVjMzklM0EweDdlZGI4YmU1NDk3ZTZlYWUhMnNXaGl0ZSUyMFBvaW50JTIwQmVhY2ghNWUwITNtMiExc2VuITJzcm8hNHYxNjc4ODAzNDc1NjA4ITVtMiExc2VuITJzcm8iIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHN0eWxlPSJib3JkZXI6MDsiIGFsbG93ZnVsbHNjcmVlbj0iIiBsb2FkaW5nPSJsYXp5IiByZWZlcnJlcnBvbGljeT0ibm8tcmVmZXJyZXItd2hlbi1kb3duZ3JhZGUiPjwvaWZyYW1lPg==',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'V2hpdGUgQmVhY2gsIExvcyBBbmdlbGVzLCBDYWxpZm9ybmlhIDkwNzMx',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'Tm8gY2FuY2VsbGF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'My03aHI=',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'TW9iaWxlIFZvdWNoZXI=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => '',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Tm8gY29uZmlybWF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToxOntpOjA7czo0OToiUHJvdGVjdGl2ZSBzaGllbGRzIGluIHBsYWNlIGF0IG1haW4gY29udGFjdCBhcmVhcyI7fQ==',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToxOntpOjA7czozNToiU29jaWFsIGRpc3RhbmNpbmcgbWVhc3VyZXMgaW4gcGxhY2UiO30=',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_things_to_do_id, $tax_term_tours_trips_id ),
	),
));

$cpt_waterfall_viewing_id = td_demo_content::add_cpt( array(
	'title' => 'Waterfall Viewing',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_8',
	'post_meta' => array( 
		'tdcf_price' => 'MzAk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDYzMTEuMTk0NDEzNDA3MDUhMmQtMTE5LjYzNzQ0NTA0OTU3NjU3ITNkMzcuNzI5MTMxNDQ1ODMwMSEybTMhMWYwITJmMCEzZjAhM20yITFpMTAyNCEyaTc2OCE0ZjEzLjEhM20zITFtMiExczB4ODA5NmVkZGQ4MmI0NjcxOSUzQTB4MjNhZGE5ZDcyMjJjZjNmOCEyc0hvcnNldGFpbCUyMEZhbGwhNWUwITNtMiExc2VuITJzcm8hNHYxNjc4ODAyODExNDQ2ITVtMiExc2VuITJzcm8iIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHN0eWxlPSJib3JkZXI6MDsiIGFsbG93ZnVsbHNjcmVlbj0iIiBsb2FkaW5nPSJsYXp5IiByZWZlcnJlcnBvbGljeT0ibm8tcmVmZXJyZXItd2hlbi1kb3duZ3JhZGUiPjwvaWZyYW1lPg==',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'SG9yc2V0YWlsIEZhbGwsIENhbGlmb3JuaWEgOTUzODk=',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'Tm8gY2FuY2VsbGF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'NC01aHI=',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'Tm8gVm91Y2hlciBuZWNlc3Nhcnk=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'Tm8gUGFya2luZyBhdmFpbGFibGU=',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToxOntpOjA7czozNToiVmVoaWNsZXMgY2xlYW5lZCB3aXRoIGRpc2luZmVjdGFudHMiO30=',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Tm8gY29uZmlybWF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToxOntpOjA7czo0ODoiUGFpZCBzdGF5LWF0LWhvbWUgcG9saWN5IGZvciBzdGFmZiB3aXRoIHN5bXB0b21zIjt9',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YTozOntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjIzOiJIYW5kIHNhbml0aXplciBwcm92aWRlZCI7aToyO3M6MzU6IlNvY2lhbCBkaXN0YW5jaW5nIG1lYXN1cmVzIGluIHBsYWNlIjt9',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_things_to_do_id, $tax_term_tours_trips_id ),
	),
));

$cpt_cabin_hunting_id = td_demo_content::add_cpt( array(
	'title' => 'Cabin Hunting',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_9',
	'post_meta' => array( 
		'tdcf_price' => 'MzUwJA==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNyExbTEyITFtMyExZDMzMjUuNTMwODA4NzY0Mzk5NCEyZC0xMTcuNTU5MDYxNjgzOTQzNzQhM2QzMy41Mzk1ODI0NTIwMTcwOCEybTMhMWYwITJmMCEzZjAhM20yITFpMTAyNCEyaTc2OCE0ZjEzLjEhM20yITFtMSEyek16UENzRE15SnpJeUxqVWlUaUF4TVRmQ3NETXpKekkwTGpjaVZ3ITVlMCEzbTIhMXNlbiEyc3JvITR2MTY3ODgwMjQ1NDczOCE1bTIhMXNlbiEyc3JvIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBzdHlsZT0iYm9yZGVyOjA7IiBhbGxvd2Z1bGxzY3JlZW49IiIgbG9hZGluZz0ibGF6eSIgcmVmZXJyZXJwb2xpY3k9Im5vLXJlZmVycmVyLXdoZW4tZG93bmdyYWRlIj48L2lmcmFtZT4=',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'Q2FzcGVycyBQYXJrIFJkLCBUcmFidWNvIENhbnlvbiwgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mi0zZA==',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'TW9iaWxlIFZvdWNoZXI=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'UGFpZCBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6MzU6IlZlaGljbGVzIGNsZWFuZWQgd2l0aCBkaXNpbmZlY3RhbnRzIjt9',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'MjRIciBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YTozOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czo0ODoiUGFpZCBzdGF5LWF0LWhvbWUgcG9saWN5IGZvciBzdGFmZiB3aXRoIHN5bXB0b21zIjtpOjI7czo0MzoiSW5kaXZpZHVhbGx5IHdyYXBwZWQgZm9vZCBvcHRpb25zIGF2YWlsYWJsZSI7fQ==',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YTo1OntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjIzOiJIYW5kIHNhbml0aXplciBwcm92aWRlZCI7aToyO3M6MTk6Ik1hc2tzIHdvcm4gYnkgc3RhZmYiO2k6MztzOjM1OiJTb2NpYWwgZGlzdGFuY2luZyBtZWFzdXJlcyBpbiBwbGFjZSI7aTo0O3M6Mjk6IkNvbnRhY3RsZXNzIHRpY2tldCByZWRlbXB0aW9uIjt9',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_things_to_do_id, $tax_term_tours_trips_id ),
	),
));

$cpt_trail_park_beach_id = td_demo_content::add_cpt( array(
	'title' => 'Trail Park Beach',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_10',
	'post_meta' => array( 
		'tdcf_price' => 'RnJlZQ==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNyExbTEyITFtMyExZDMzMjguODA1OTI1NDU5ODk4NCEyZC0xMTcuNjkzODY1NjgzOTQ0NzQhM2QzMy40NTQzNjI0NTY0NDQ2MzUhMm0zITFmMCEyZjAhM2YwITNtMiExaTEwMjQhMmk3NjghNGYxMy4xITNtMiExbTEhMnpNelBDc0RJM0p6RTFMamNpVGlBeE1UZkNzRFF4SnpNd0xqQWlWdyE1ZTAhM20yITFzZW4hMnNybyE0djE2Nzg4MDE4NTYzNzkhNW0yITFzZW4hMnNybyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgc3R5bGU9ImJvcmRlcjowOyIgYWxsb3dmdWxsc2NyZWVuPSIiIGxvYWRpbmc9ImxhenkiIHJlZmVycmVycG9saWN5PSJuby1yZWZlcnJlci13aGVuLWRvd25ncmFkZSI+PC9pZnJhbWU+',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'RGFuYSBQb2ludCwgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'Tm8gY2FuY2VsbGF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mmhy',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'Tm8gVm91Y2hlciBuZWNlc3Nhcnk=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'Tm8gUGFya2luZyBhdmFpbGFibGU=',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToxOntpOjA7czo0MDoiR2VhciBhbmQgZXF1aXBtZW50IHNhbml0aXplZCBiZXR3ZWVuIHVzZSI7fQ==',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Tm8gY29uZmlybWF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToxOntpOjA7czo0MzoiSW5kaXZpZHVhbGx5IHdyYXBwZWQgZm9vZCBvcHRpb25zIGF2YWlsYWJsZSI7fQ==',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToxOntpOjA7czozNToiU29jaWFsIGRpc3RhbmNpbmcgbWVhc3VyZXMgaW4gcGxhY2UiO30=',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_things_to_do_id, $tax_term_tours_trips_id ),
	),
));

$cpt_gardening_class_id = td_demo_content::add_cpt( array(
	'title' => 'Gardening Class',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_price' => 'MzUk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNyExbTEyITFtMyExZDMzMzYuNzYzNDcyMDU3MjE2NCEyZC0xMTcuMjc0NzkzNjgzOTQ3MjIhM2QzMy4yNDY0OTk0NjcyMDM3MyEybTMhMWYwITJmMCEzZjAhM20yITFpMTAyNCEyaTc2OCE0ZjEzLjEhM20yITFtMSEyek16UENzREUwSnpRM0xqUWlUaUF4TVRmQ3NERTJKekl4TGpRaVZ3ITVlMCEzbTIhMXNlbiEyc3JvITR2MTY3ODgwMTY1NzI1OSE1bTIhMXNlbiEyc3JvIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBzdHlsZT0iYm9yZGVyOjA7IiBhbGxvd2Z1bGxzY3JlZW49IiIgbG9hZGluZz0ibGF6eSIgcmVmZXJyZXJwb2xpY3k9Im5vLXJlZmVycmVyLXdoZW4tZG93bmdyYWRlIj48L2lmcmFtZT4=',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'MzAwMCBQYXRpZW5jZXMgUGwsIE9jZWFuc2lkZSwgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'MmhyIDMwbQ==',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'TW9iaWxlIFZvdWNoZXI=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToxOntpOjA7czo0MDoiR2VhciBhbmQgZXF1aXBtZW50IHNhbml0aXplZCBiZXR3ZWVuIHVzZSI7fQ==',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'SW5zdGFudCBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToxOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjt9',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YTo0OntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjIzOiJIYW5kIHNhbml0aXplciBwcm92aWRlZCI7aToyO3M6MTk6Ik1hc2tzIHdvcm4gYnkgc3RhZmYiO2k6MztzOjI5OiJDb250YWN0bGVzcyB0aWNrZXQgcmVkZW1wdGlvbiI7fQ==',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_things_to_do_id, $tax_term_workshops_id ),
	),
));

$cpt_wood_sculpting_101_id = td_demo_content::add_cpt( array(
	'title' => 'Wood Sculpting 101',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_price' => 'NDAk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNyExbTEyITFtMyExZDE2NzAuMTgzODMxMzY5MzUzMSEyZC0xMTcuMTkyODkwNjk5MzU5MjIhM2QzMy4xNTE5NzMzMzM1NTQ1ITJtMyExZjAhMmYwITNmMCEzbTIhMWkxMDI0ITJpNzY4ITRmMTMuMSEzbTIhMW0xITJ6TXpQQ3NEQTVKekEzTGpFaVRpQXhNVGZDc0RFeEp6TXlMakVpVnchNWUwITNtMiExc2VuITJzcm8hNHYxNjc4ODAwOTk5ODk4ITVtMiExc2VuITJzcm8iIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHN0eWxlPSJib3JkZXI6MDsiIGFsbG93ZnVsbHNjcmVlbj0iIiBsb2FkaW5nPSJsYXp5IiByZWZlcnJlcnBvbGljeT0ibm8tcmVmZXJyZXItd2hlbi1kb3duZ3JhZGUiPjwvaWZyYW1lPg==',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'MTUzNy0xNTAxIFBhbG9tYXJjb3MgQXZlLCBTYW4gTWFyY29zLCBDYWxpZm9ybmlh',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mmhy',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'Tm8gVm91Y2hlciBuZWNlc3Nhcnk=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozOToiR3VpZGVzIHJlcXVpcmVkIHRvIHJlZ3VsYXJseSB3YXNoIGhhbmRzIjtpOjE7czo0MDoiR2VhciBhbmQgZXF1aXBtZW50IHNhbml0aXplZCBiZXR3ZWVuIHVzZSI7fQ==',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Mi0zIEJ1c2luZXNzIERheSBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czoxNDoiTWFza3MgcmVxdWlyZWQiO30=',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YTozOntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjIzOiJIYW5kIHNhbml0aXplciBwcm92aWRlZCI7aToyO3M6MTk6Ik1hc2tzIHdvcm4gYnkgc3RhZmYiO30=',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_things_to_do_id, $tax_term_workshops_id ),
	),
));

$cpt_interactive_business_analysis_id = td_demo_content::add_cpt( array(
	'title' => 'Interactive Business Analysis',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_price' => 'NjAk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNyExbTEyITFtMyExZDMzNDEuMjc2NTEyMzcwODY3ITJkLTExNy4xNDQwMzc2ODM5NDg0OCEzZDMzLjEyODA5OTQ3MzMwNjM1ITJtMyExZjAhMmYwITNmMCEzbTIhMWkxMDI0ITJpNzY4ITRmMTMuMSEzbTIhMW0xITJ6TXpQQ3NEQTNKelF4TGpFaVRpQXhNVGZDc0RBNEp6TXdMamNpVnchNWUwITNtMiExc2VuITJzcm8hNHYxNjc4ODAwMjQ1ODUzITVtMiExc2VuITJzcm8iIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHN0eWxlPSJib3JkZXI6MDsiIGFsbG93ZnVsbHNjcmVlbj0iIiBsb2FkaW5nPSJsYXp5IiByZWZlcnJlcnBvbGljeT0ibm8tcmVmZXJyZXItd2hlbi1kb3duZ3JhZGUiPjwvaWZyYW1lPg==',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'OTUyLTk0NCBMYSBNb3JlZSBSZCwgU2FuIE1hcmNvcywgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'MWhyIDMwbQ==',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'Tm8gVm91Y2hlciBuZWNlc3Nhcnk=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToxOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7fQ==',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'SW5zdGFudCBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czoxNDoiTWFza3MgcmVxdWlyZWQiO30=',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YTozOntpOjA7czoxNDoiTWFza3MgcHJvdmlkZWQiO2k6MTtzOjIzOiJIYW5kIHNhbml0aXplciBwcm92aWRlZCI7aToyO3M6MTk6Ik1hc2tzIHdvcm4gYnkgc3RhZmYiO30=',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_things_to_do_id, $tax_term_workshops_id ),
	),
));

$cpt_marketing_strategies_class_id = td_demo_content::add_cpt( array(
	'title' => 'Marketing Strategies Class',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_price' => 'NDUk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNyExbTEyITFtMyExZDMzNDIuMTI2MjU0MTQzNjI4ITJkLTExNy4xMTMyNTk2ODM5NDg3MyEzZDMzLjEwNTc2NDQ3NDQ1NTU1ITJtMyExZjAhMmYwITNmMCEzbTIhMWkxMDI0ITJpNzY4ITRmMTMuMSEzbTIhMW0xITJ6TXpQQ3NEQTJKekl3TGpjaVRpQXhNVGZDc0RBMkp6TTVMamtpVnchNWUwITNtMiExc2VuITJzcm8hNHYxNjc4ODAwMDc1NTUzITVtMiExc2VuITJzcm8iIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHN0eWxlPSJib3JkZXI6MDsiIGFsbG93ZnVsbHNjcmVlbj0iIiBsb2FkaW5nPSJsYXp5IiByZWZlcnJlcnBvbGljeT0ibm8tcmVmZXJyZXItd2hlbi1kb3duZ3JhZGUiPjwvaWZyYW1lPg==',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_address' => 'MTQzNC0xMzc0IFMgSGFsZSBBdmUsIEVzY29uZGlkbywgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'MWhyIDMwbQ==',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'UGh5c2ljYWwgVm91Y2hlcg==',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToxOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7fQ==',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'MjRIciBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_social_distancing' => 'YTozOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czoxNDoiTWFza3MgcmVxdWlyZWQiO2k6MjtzOjQ4OiJQYWlkIHN0YXktYXQtaG9tZSBwb2xpY3kgZm9yIHN0YWZmIHdpdGggc3ltcHRvbXMiO30=',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToyOntpOjA7czozNToiU29jaWFsIGRpc3RhbmNpbmcgbWVhc3VyZXMgaW4gcGxhY2UiO2k6MTtzOjI5OiJDb250YWN0bGVzcyB0aWNrZXQgcmVkZW1wdGlvbiI7fQ==',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_things_to_do_id, $tax_term_workshops_id ),
	),
));

$cpt_arts_crafts_into_id = td_demo_content::add_cpt( array(
	'title' => 'Arts & Crafts Into',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_price' => 'MzUk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNyExbTEyITFtMyExZDE2NzEuMTc3NjY4MjE2OTExMSEyZC0xMTcuMTA0MzE5MjI3OTMxMDEhM2QzMy4wOTk3NDA4ODYwNjY0MiEybTMhMWYwITJmMCEzZjAhM20yITFpMTAyNCEyaTc2OCE0ZjEzLjEhM20yITFtMSEyek16UENzREExSnpVNUxqRWlUaUF4TVRmQ3NEQTJKekV5TGpJaVZ3ITVlMCEzbTIhMXNlbiEyc3JvITR2MTY3ODc5OTg3MjE3MiE1bTIhMXNlbiEyc3JvIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBzdHlsZT0iYm9yZGVyOjA7IiBhbGxvd2Z1bGxzY3JlZW49IiIgbG9hZGluZz0ibGF6eSIgcmVmZXJyZXJwb2xpY3k9Im5vLXJlZmVycmVyLXdoZW4tZG93bmdyYWRlIj48L2lmcmFtZT4=',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mmhy',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'TW9iaWxlIFZvdWNoZXI=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'Tm8gUGFya2luZyBhdmFpbGFibGU=',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6NDA6IkdlYXIgYW5kIGVxdWlwbWVudCBzYW5pdGl6ZWQgYmV0d2VlbiB1c2UiO30=',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Mi0zIEJ1c2luZXNzIERheSBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_address' => 'MTc0MCBTY2VuaWMgVHJhaWwgV2F5LCBFc2NvbmRpZG8sIENhbGlmb3JuaWE=',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_social_distancing' => 'YToxOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjt9',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToyOntpOjA7czoxOToiTWFza3Mgd29ybiBieSBzdGFmZiI7aToxO3M6Mjk6IkNvbnRhY3RsZXNzIHRpY2tldCByZWRlbXB0aW9uIjt9',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_things_to_do_id, $tax_term_workshops_id ),
	),
));

$cpt_kayak_races_id = td_demo_content::add_cpt( array(
	'title' => 'Kayak Races',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_6',
	'post_meta' => array( 
		'tdcf_price' => 'NDAk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNCExbTEyITFtMyExZDU3ODguMzgwMzIzNDg5OTg3ITJkLTExNy4wODA3NzU1Mzk4NzkwNyEzZDMzLjA2MTQ1NzI3NzQ5NDQwNSEybTMhMWYwITJmMCEzZjAhM20yITFpMTAyNCEyaTc2OCE0ZjEzLjEhNWUwITNtMiExc2VuITJzcm8hNHYxNjc4Nzk4NzAwMTU3ITVtMiExc2VuITJzcm8iIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHN0eWxlPSJib3JkZXI6MDsiIGFsbG93ZnVsbHNjcmVlbj0iIiBsb2FkaW5nPSJsYXp5IiByZWZlcnJlcnBvbGljeT0ibm8tcmVmZXJyZXItd2hlbi1kb3duZ3JhZGUiPjwvaWZyYW1lPg==',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'MmhyIDMwbQ==',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'TW9iaWxlIFZvdWNoZXI=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YTozOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6MzU6IlZlaGljbGVzIGNsZWFuZWQgd2l0aCBkaXNpbmZlY3RhbnRzIjtpOjI7czo0MDoiR2VhciBhbmQgZXF1aXBtZW50IHNhbml0aXplZCBiZXR3ZWVuIHVzZSI7fQ==',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'MjRIciBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_address' => 'RGF2aWQgS3JlaXR6ZXIgTGFrZSBIb2RnZXMgQmljeWNsZSBQZWRlc3RyaWFuIEJyaWRnZSwgRXNjb25kaWRvLCBDYWxpZm9ybmlh',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_social_distancing' => 'YTo0OntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czo0ODoiUGFpZCBzdGF5LWF0LWhvbWUgcG9saWN5IGZvciBzdGFmZiB3aXRoIHN5bXB0b21zIjtpOjI7czo0MzoiSW5kaXZpZHVhbGx5IHdyYXBwZWQgZm9vZCBvcHRpb25zIGF2YWlsYWJsZSI7aTozO3M6NDk6IlByb3RlY3RpdmUgc2hpZWxkcyBpbiBwbGFjZSBhdCBtYWluIGNvbnRhY3QgYXJlYXMiO30=',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToxOntpOjA7czoxOToiTWFza3Mgd29ybiBieSBzdGFmZiI7fQ==',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_adventure_id, $tax_term_cruises_id, $tax_term_food_drink_id, $tax_term_history_culture_id, $tax_term_seasonal_activities_id, $tax_term_shows_concerts_id, $tax_term_things_to_do_id, $tax_term_tours_trips_id, $tax_term_workshops_id ),
	),
));

$cpt_gardening_season_park_id = td_demo_content::add_cpt( array(
	'title' => 'Gardening Season Park',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_7',
	'post_meta' => array( 
		'tdcf_price' => 'MTAk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNCExbTEyITFtMyExZDI5MDMuMTU4MDI3ODU1MjA3NCEyZC0xMTcuMjMxNzgyMDQ5ODA3MjYhM2QzMi43ODc3MTIxNjMxMDAyMyEybTMhMWYwITJmMCEzZjAhM20yITFpMTAyNCEyaTc2OCE0ZjEzLjEhNWUwITNtMiExc2VuITJzcm8hNHYxNjc4Nzk4NTA3NDc4ITVtMiExc2VuITJzcm8iIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHN0eWxlPSJib3JkZXI6MDsiIGFsbG93ZnVsbHNjcmVlbj0iIiBsb2FkaW5nPSJsYXp5IiByZWZlcnJlcnBvbGljeT0ibm8tcmVmZXJyZXItd2hlbi1kb3duZ3JhZGUiPjwvaWZyYW1lPg==',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_cancellation' => 'Tm8gY2FuY2VsbGF0aW9uIGF2YWlsYWJsZQ==',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'M2hyIDMwbQ==',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'TW9iaWxlIFZvdWNoZXI=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'Tm8gUGFya2luZyBhdmFpbGFibGU=',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6NDA6IkdlYXIgYW5kIGVxdWlwbWVudCBzYW5pdGl6ZWQgYmV0d2VlbiB1c2UiO30=',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'SW5zdGFudCBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_address' => 'Q29yb25hIE9yaWVudGUgUmQsIFNhbiBEaWVnbywgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czo0ODoiUGFpZCBzdGF5LWF0LWhvbWUgcG9saWN5IGZvciBzdGFmZiB3aXRoIHN5bXB0b21zIjtpOjE7czo0MzoiSW5kaXZpZHVhbGx5IHdyYXBwZWQgZm9vZCBvcHRpb25zIGF2YWlsYWJsZSI7fQ==',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToxOntpOjA7czozNToiU29jaWFsIGRpc3RhbmNpbmcgbWVhc3VyZXMgaW4gcGxhY2UiO30=',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_adventure_id, $tax_term_cruises_id, $tax_term_food_drink_id, $tax_term_history_culture_id, $tax_term_seasonal_activities_id, $tax_term_shows_concerts_id, $tax_term_things_to_do_id, $tax_term_tours_trips_id, $tax_term_workshops_id ),
	),
));

$cpt_army_day_celebration_id = td_demo_content::add_cpt( array(
	'title' => 'Army Day Celebration',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_8',
	'post_meta' => array( 
		'tdcf_price' => 'RnJlZQ==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNyExbTEyITFtMyExZDMzNTQuODE2NTY2MTYyMjE5ITJkLTExNy4yMTQyNjE2ODM5NTI2ITNkMzIuNzcwNjAxNDkxNjE3NTU1ITJtMyExZjAhMmYwITNmMCEzbTIhMWkxMDI0ITJpNzY4ITRmMTMuMSEzbTIhMW0xITJ6TXpMQ3NEUTJKekUwTGpJaVRpQXhNVGZDc0RFeUp6UXpMalVpVnchNWUwITNtMiExc2VuITJzcm8hNHYxNjc4Nzk4NDAyMDYxITVtMiExc2VuITJzcm8iIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHN0eWxlPSJib3JkZXI6MDsiIGFsbG93ZnVsbHNjcmVlbj0iIiBsb2FkaW5nPSJsYXp5IiByZWZlcnJlcnBvbGljeT0ibm8tcmVmZXJyZXItd2hlbi1kb3duZ3JhZGUiPjwvaWZyYW1lPg==',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mi00aHI=',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'UGh5c2ljYWwgVm91Y2hlcg==',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YTo0OntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6MzU6IlZlaGljbGVzIGNsZWFuZWQgd2l0aCBkaXNpbmZlY3RhbnRzIjtpOjI7czozOToiR3VpZGVzIHJlcXVpcmVkIHRvIHJlZ3VsYXJseSB3YXNoIGhhbmRzIjtpOjM7czo0MDoiR2VhciBhbmQgZXF1aXBtZW50IHNhbml0aXplZCBiZXR3ZWVuIHVzZSI7fQ==',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'SW5zdGFudCBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_address' => 'TWlzc2lvbiBCYXksIFNhbiBEaWVnbywgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_social_distancing' => 'YTozOntpOjA7czozMzoiVGVtcGVyYXR1cmUgY2hlY2tzIGdpdmVuIHRvIHN0YWZmIjtpOjE7czoxNDoiTWFza3MgcmVxdWlyZWQiO2k6MjtzOjQ4OiJQYWlkIHN0YXktYXQtaG9tZSBwb2xpY3kgZm9yIHN0YWZmIHdpdGggc3ltcHRvbXMiO30=',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToyOntpOjA7czoxOToiTWFza3Mgd29ybiBieSBzdGFmZiI7aToxO3M6MzU6IlNvY2lhbCBkaXN0YW5jaW5nIG1lYXN1cmVzIGluIHBsYWNlIjt9',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_adventure_id, $tax_term_cruises_id, $tax_term_food_drink_id, $tax_term_history_culture_id, $tax_term_seasonal_activities_id, $tax_term_shows_concerts_id, $tax_term_things_to_do_id, $tax_term_tours_trips_id, $tax_term_workshops_id ),
	),
));

$cpt_barbecue_park_season_id = td_demo_content::add_cpt( array(
	'title' => 'Barbecue Park Season',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_9',
	'post_meta' => array( 
		'tdcf_price' => 'RnJlZQ==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNyExbTEyITFtMyExZDMzNTUuODYzNzg0NjA2ODg4NyEyZC0xMTcuMTU3MjQxNjgzOTUyNzYhM2QzMi43NDI4MDc0OTMwMzQyOCEybTMhMWYwITJmMCEzZjAhM20yITFpMTAyNCEyaTc2OCE0ZjEzLjEhM20yITFtMSEyek16TENzRFEwSnpNMExqRWlUaUF4TVRmQ3NEQTVKekU0TGpJaVZ3ITVlMCEzbTIhMXNlbiEyc3JvITR2MTY3ODc5ODA1Mjk0MyE1bTIhMXNlbiEyc3JvIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBzdHlsZT0iYm9yZGVyOjA7IiBhbGxvd2Z1bGxzY3JlZW49IiIgbG9hZGluZz0ibGF6eSIgcmVmZXJyZXJwb2xpY3k9Im5vLXJlZmVycmVyLXdoZW4tZG93bmdyYWRlIj48L2lmcmFtZT4=',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_cancellation' => 'Tm8gY2FuY2VsbGF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mi00aHI=',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'Tm8gVm91Y2hlciBuZWNlc3Nhcnk=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'Tm8gUGFya2luZyBhdmFpbGFibGU=',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6NDA6IkdlYXIgYW5kIGVxdWlwbWVudCBzYW5pdGl6ZWQgYmV0d2VlbiB1c2UiO30=',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'SW5zdGFudCBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_address' => 'SGlsbGNyZXN0LCBTYW4gRGllZ28sIENhbGlmb3JuaWE=',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_social_distancing' => 'YToxOntpOjA7czoxNDoiTWFza3MgcmVxdWlyZWQiO30=',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToxOntpOjA7czozNToiU29jaWFsIGRpc3RhbmNpbmcgbWVhc3VyZXMgaW4gcGxhY2UiO30=',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_adventure_id, $tax_term_cruises_id, $tax_term_food_drink_id, $tax_term_history_culture_id, $tax_term_seasonal_activities_id, $tax_term_shows_concerts_id, $tax_term_things_to_do_id, $tax_term_tours_trips_id, $tax_term_workshops_id ),
	),
));

$cpt_4th_of_july_celebration_id = td_demo_content::add_cpt( array(
	'title' => '4th of July Celebration',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_10',
	'post_meta' => array( 
		'tdcf_price' => 'RnJlZQ==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNyExbTEyITFtMyExZDMzNTYuNjM1MDIzOTc0ODAxNyEyZC0xMTcuMTIwMjUyNDU2MzU3MzghM2QzMi43MjIzMjQ3ODE1OTY4NCEybTMhMWYwITJmMCEzZjAhM20yITFpMTAyNCEyaTc2OCE0ZjEzLjEhM20yITFtMSEyek16TENzRFF6SnpJd0xqUWlUaUF4TVRmQ3NEQTNKekEyTGpNaVZ3ITVlMCEzbTIhMXNlbiEyc3JvITR2MTY3ODc5NzgwMjg3OSE1bTIhMXNlbiEyc3JvIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBzdHlsZT0iYm9yZGVyOjA7IiBhbGxvd2Z1bGxzY3JlZW49IiIgbG9hZGluZz0ibGF6eSIgcmVmZXJyZXJwb2xpY3k9Im5vLXJlZmVycmVyLXdoZW4tZG93bmdyYWRlIj48L2lmcmFtZT4=',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_cancellation' => 'Tm8gY2FuY2VsbGF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'MmhyIDMwbQ==',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'Tm8gVm91Y2hlciBuZWNlc3Nhcnk=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'UGFpZCBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToxOntpOjA7czozNToiVmVoaWNsZXMgY2xlYW5lZCB3aXRoIGRpc2luZmVjdGFudHMiO30=',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Tm8gY29uZmlybWF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_address' => 'Q2VkYXIgUmlkZ2UgUGFyaywgUGVudHVja2V0dCBBdmUsIFNhbiBEaWVnbywgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czoxNDoiTWFza3MgcmVxdWlyZWQiO2k6MTtzOjQ5OiJQcm90ZWN0aXZlIHNoaWVsZHMgaW4gcGxhY2UgYXQgbWFpbiBjb250YWN0IGFyZWFzIjt9',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToyOntpOjA7czoxOToiTWFza3Mgd29ybiBieSBzdGFmZiI7aToxO3M6MzU6IlNvY2lhbCBkaXN0YW5jaW5nIG1lYXN1cmVzIGluIHBsYWNlIjt9',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_adventure_id, $tax_term_cruises_id, $tax_term_food_drink_id, $tax_term_history_culture_id, $tax_term_seasonal_activities_id, $tax_term_shows_concerts_id, $tax_term_things_to_do_id, $tax_term_tours_trips_id, $tax_term_workshops_id ),
	),
));

$cpt_bike_races_and_tracks_id = td_demo_content::add_cpt( array(
	'title' => 'Bike Races and Tracks',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_price' => 'NDAtNTAk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNyExbTEyITFtMyExZDMzMjkuOTQ5MDU3NjM3Mjc0NiEyZC0xMTcuMTU0MjQ4NjgzOTQ1ITNkMzMuNDI0NTcyNDU3OTkwNDYhMm0zITFmMCEyZjAhM2YwITNtMiExaTEwMjQhMmk3NjghNGYxMy4xITNtMiExbTEhMnpNelBDc0RJMUp6STRMalFpVGlBeE1UZkNzREE1SnpBM0xqUWlWdyE1ZTAhM20yITFzZW4hMnNybyE0djE2Nzg3OTc2ODQ1MTIhNW0yITFzZW4hMnNybyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgc3R5bGU9ImJvcmRlcjowOyIgYWxsb3dmdWxsc2NyZWVuPSIiIGxvYWRpbmc9ImxhenkiIHJlZmVycmVycG9saWN5PSJuby1yZWZlcnJlci13aGVuLWRvd25ncmFkZSI+PC9pZnJhbWU+',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'NS0xMGhy',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'UGh5c2ljYWwgVm91Y2hlcg==',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'UGFpZCBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => '',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'SW5zdGFudCBDb25maXJtYXRpb24=',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_address' => 'UmFpbmJvdywgQ2FsaWZvcm5pYQ==',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_social_distancing' => 'YToyOntpOjA7czo0ODoiUGFpZCBzdGF5LWF0LWhvbWUgcG9saWN5IGZvciBzdGFmZiB3aXRoIHN5bXB0b21zIjtpOjE7czo0MzoiSW5kaXZpZHVhbGx5IHdyYXBwZWQgZm9vZCBvcHRpb25zIGF2YWlsYWJsZSI7fQ==',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YTozOntpOjA7czoxOToiTWFza3Mgd29ybiBieSBzdGFmZiI7aToxO3M6NDY6IkNvbnRhY3RsZXNzIHBheW1lbnQgZm9yIGdyYXR1aXRpZXMgYW5kIGFkZC1vbnMiO2k6MjtzOjM1OiJTb2NpYWwgZGlzdGFuY2luZyBtZWFzdXJlcyBpbiBwbGFjZSI7fQ==',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_seasonal_activities_id, $tax_term_things_to_do_id ),
	),
));

$cpt_fireworks_day_celebration_id = td_demo_content::add_cpt( array(
	'title' => 'Fireworks Day Celebration',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_price' => 'RnJlZQ==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNyExbTEyITFtMyExZDMxMjIuOTg1OTkxNjE1NDE3MyEyZC0xMjEuNTkzMTc4NjgzODgwMjQhM2QzOC40ODc5NjUxNzg1NzczITJtMyExZjAhMmYwITNmMCEzbTIhMWkxMDI0ITJpNzY4ITRmMTMuMSEzbTIhMW0xITJ6TXpqQ3NESTVKekUyTGpjaVRpQXhNakhDc0RNMUp6STNMallpVnchNWUwITNtMiExc2VuITJzcm8hNHYxNjc4Nzk3MzEyNTc3ITVtMiExc2VuITJzcm8iIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHN0eWxlPSJib3JkZXI6MDsiIGFsbG93ZnVsbHNjcmVlbj0iIiBsb2FkaW5nPSJsYXp5IiByZWZlcnJlcnBvbGljeT0ibm8tcmVmZXJyZXItd2hlbi1kb3duZ3JhZGUiPjwvaWZyYW1lPg==',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'My00aHI=',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'TW9iaWxlIFZvdWNoZXI=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => '',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Tm8gY29uZmlybWF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_address' => 'RGF2aXMgSm9pbnQgVW5pZmllZCBTY2hvb2wgRGlzdHJpY3QsIENhbGlmb3JuaWE=',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_social_distancing' => 'YToxOntpOjA7czo0MzoiSW5kaXZpZHVhbGx5IHdyYXBwZWQgZm9vZCBvcHRpb25zIGF2YWlsYWJsZSI7fQ==',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToyOntpOjA7czoxOToiTWFza3Mgd29ybiBieSBzdGFmZiI7aToxO3M6NDY6IkNvbnRhY3RsZXNzIHBheW1lbnQgZm9yIGdyYXR1aXRpZXMgYW5kIGFkZC1vbnMiO30=',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_seasonal_activities_id, $tax_term_things_to_do_id ),
	),
));

$cpt_veterans_day_celebration_id = td_demo_content::add_cpt( array(
	'title' => 'Veteran\'s Day Celebration',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_price' => 'RnJlZQ==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNyExbTEyITFtMyExZDMxNTYuMTY5NjYzOTU2NzQ2NyEyZC0xMjIuNDIwOTM0NjgzODkwNjkhM2QzNy43MTU2OTUyMjMzMjc4MDYhMm0zITFmMCEyZjAhM2YwITNtMiExaTEwMjQhMmk3NjghNGYxMy4xITNtMiExbTEhMnpNemZDc0RReUp6VTJMalVpVGlBeE1qTENzREkxSnpBM0xqVWlWdyE1ZTAhM20yITFzZW4hMnNybyE0djE2Nzg3OTY0OTMyNjIhNW0yITFzZW4hMnNybyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgc3R5bGU9ImJvcmRlcjowOyIgYWxsb3dmdWxsc2NyZWVuPSIiIGxvYWRpbmc9ImxhenkiIHJlZmVycmVycG9saWN5PSJuby1yZWZlcnJlci13aGVuLWRvd25ncmFkZSI+PC9pZnJhbWU+',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_cancellation' => 'Tm8gY2FuY2VsbGF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'NC01aHI=',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'Tm8gVm91Y2hlciBuZWNlc3Nhcnk=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNzoiRXZlcnkgc3VyZmFjZSBjbGVhbmVkIGFuZCBkaXNpbmZlY3RlZCI7aToxO3M6Mzk6Ikd1aWRlcyByZXF1aXJlZCB0byByZWd1bGFybHkgd2FzaCBoYW5kcyI7fQ==',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Tm8gY29uZmlybWF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_address' => 'TWNMYXJlbiBQYXJrLCBTYW4gRnJhbmNpc2NvLCBDYWxpZm9ybmlh',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_social_distancing' => '',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => '',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_seasonal_activities_id, $tax_term_things_to_do_id ),
	),
));

$cpt_pumpkin_patch_season_id = td_demo_content::add_cpt( array(
	'title' => 'Pumpkin Patch Season',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_price' => 'MTAk',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xNyExbTEyITFtMyExZDMxNjAuMjE0MTg5Njc5ODY4ITJkLTEyMi40NjUxNjY2ODM4OTIwNiEzZDM3LjYyMDY0OTIyODc4MzI4ITJtMyExZjAhMmYwITNmMCEzbTIhMWkxMDI0ITJpNzY4ITRmMTMuMSEzbTIhMW0xITJ6TXpmQ3NETTNKekUwTGpNaVRpQXhNakxDc0RJM0p6UTJMamNpVnchNWUwITNtMiExc2VuITJzcm8hNHYxNjc4NzAwODYzOTM4ITVtMiExc2VuITJzcm8iIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIHN0eWxlPSJib3JkZXI6MDsiIGFsbG93ZnVsbHNjcmVlbj0iIiBsb2FkaW5nPSJsYXp5IiByZWZlcnJlcnBvbGljeT0ibm8tcmVmZXJyZXItd2hlbi1kb3duZ3JhZGUiPjwvaWZyYW1lPg==',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_cancellation' => 'RnJlZSBjYW5jZWxsYXRpb24gYXZhaWxhYmxl',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mmhy',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'UGh5c2ljYWwgVm91Y2hlcg==',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'UGFpZCBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNToiVmVoaWNsZXMgY2xlYW5lZCB3aXRoIGRpc2luZmVjdGFudHMiO2k6MTtzOjM5OiJHdWlkZXMgcmVxdWlyZWQgdG8gcmVndWxhcmx5IHdhc2ggaGFuZHMiO30=',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Tm8gY29uZmlybWF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_address' => 'Tm90Y2ggVHJhaWwsIFBhY2lmaWNhLCBDYWxpZm9ybmlh',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_social_distancing' => '',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToyOntpOjA7czozNToiU29jaWFsIGRpc3RhbmNpbmcgbWVhc3VyZXMgaW4gcGxhY2UiO2k6MTtzOjI5OiJDb250YWN0bGVzcyB0aWNrZXQgcmVkZW1wdGlvbiI7fQ==',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_seasonal_activities_id, $tax_term_things_to_do_id ),
	),
));

$cpt_fishing_season_on_mayflower_id = td_demo_content::add_cpt( array(
	'title' => 'Fishing Season on Mayflower',
	'type' => 'tdcpt_tourism',
	'file' => 'tdcpt_tourism_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_price' => 'RnJlZQ==',
		'_tdcf_price' => 'ZmllbGRfNjQwZWUxMTA2YTcyNw==',
		'tdcf_location' => 'PGlmcmFtZSBzcmM9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9lbWJlZD9wYj0hMW0xOCExbTEyITFtMyExZDMxNTIuMjc4NTM5OTI2MDE4MyEyZC0xMjIuMjY3ODUzNTY0MTIwNTchM2QzNy44MDY5NDQyNTMzMjg5NiEybTMhMWYwITJmMCEzZjAhM20yITFpMTAyNCEyaTc2OCE0ZjEzLjEhM20zITFtMiExczB4ODA4Zjg3NGNlZDkzOTk4OSUzQTB4OWQ3MWYzYThmYjQ1ZWQ2ITJzMjYzLTI3OSUyMExha2VzaWRlJTIwRHIlMkMlMjBPYWtsYW5kJTJDJTIwQ0ElMjA5NDYxMiUyQyUyMFVTQSE1ZTAhM20yITFzZW4hMnNybyE0djE2Nzg3MDAwODc2MDEhNW0yITFzZW4hMnNybyIgd2lkdGg9IjYwMCIgaGVpZ2h0PSI0NTAiIHN0eWxlPSJib3JkZXI6MDsiIGFsbG93ZnVsbHNjcmVlbj0iIiBsb2FkaW5nPSJsYXp5IiByZWZlcnJlcnBvbGljeT0ibm8tcmVmZXJyZXItd2hlbi1kb3duZ3JhZGUiPjwvaWZyYW1lPg==',
		'_tdcf_location' => 'ZmllbGRfNjQwZWUwZDk2YTcyNg==',
		'tdcf_cancellation' => 'Tm8gY2FuY2VsbGF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_cancellation' => 'ZmllbGRfNjQwZWUxYjY2YTcyOA==',
		'tdcf_time_needed' => 'Mi00aHI=',
		'_tdcf_time_needed' => 'ZmllbGRfNjQwZWUxZjM2YTcyOQ==',
		'tdcf_voucher' => 'Tm8gVm91Y2hlciBuZWNlc3Nhcnk=',
		'_tdcf_voucher' => 'ZmllbGRfNjQwZWUyMzY2YTcyYQ==',
		'tdcf_parking' => 'RnJlZSBQYXJraW5n',
		'_tdcf_parking' => 'ZmllbGRfNjQwZWUyNTE2YTcyYg==',
		'tdcf_details' => 'PHVsPjxsaT5NYWduYSBub3N0cmEgc2VkIFVsbGFtY29ycGVyIHZ1bHB1dGF0ZSBlc3Qgc29jaWlzIGp1c3RvIGltcGVyZGlldCB0ZWxsdXMgc2FnaXR0aXMgbG9ib3J0aXMgcG9ydGEgbmVjIGhlbmRyZXJpdCBzb2xsaWNpdHVkaW4gY3JhcyB1bHRyaWNlcyBpZC48L2xpPg0KPGxpPlRpbmNpZHVudCBmYWNpbGlzaXMgbW9udGVzIHRlbXB1cy4gVGVsbHVzIHNvZGFsZXMgYWxpcXVhbSB0dXJwaXMgb3JuYXJlIHBoYXJldHJhLjwvbGk+DQo8bGk+TmF0b3F1ZSBkb25lYyBzYWdpdHRpcyBjb251YmlhIGZhdWNpYnVzLCBuaXNpIGFjLiBRdWlzIG9yY2kgc29kYWxlcyB1dCBxdWFtIG9kaW8gdHJpc3RpcXVlIHVsdHJpY2VzIHNvZGFsZXMsIG1ldHVzIFRhY2l0aS4gPC9saT4NCjxsaT5RdWlzcXVlIEV0IGVsZW1lbnR1bSBuaXNpIE11cywgdml2YW11cyBsYW9yZWV0IGVuaW0gcG9ydGEgZmFtZXMgZGlzIHF1aXMgbmlzaS48L2xpPg0KPGxpPlRyaXN0aXF1ZSB2ZXN0aWJ1bHVtLiBWZWwgZWxlaWZlbmQgb3JjaSBub24uIEltcGVyZGlldCBBbnRlIHNvZGFsZXMgc2l0IHRyaXN0aXF1ZSBub3N0cmEuPC9saT48L3VsPg==',
		'_tdcf_details' => 'ZmllbGRfNjQwZWUyN2E2YTcyYw==',
		'tdcf_cleaning' => 'YToyOntpOjA7czozNToiVmVoaWNsZXMgY2xlYW5lZCB3aXRoIGRpc2luZmVjdGFudHMiO2k6MTtzOjQwOiJHZWFyIGFuZCBlcXVpcG1lbnQgc2FuaXRpemVkIGJldHdlZW4gdXNlIjt9',
		'_tdcf_cleaning' => 'ZmllbGRfNjQwZWUyYTM2YTcyZA==',
		'tdcf_confirmation' => 'Tm8gY29uZmlybWF0aW9uIG5lY2Vzc2FyeQ==',
		'_tdcf_confirmation' => 'ZmllbGRfNjQwZWUzMjk2YTcyZQ==',
		'tdcf_address' => 'TWF5Zmxvd2VyIFJpdmVyYmVkLCBPYWtsYW5kLCBDYWxpZm9ybmlh',
		'_tdcf_address' => 'ZmllbGRfNjQwZWVkMWE3YmQzMA==',
		'tdcf_booking' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_booking' => 'ZmllbGRfNjQxMDEwNjE1ZjBiMw==',
		'tdcf_website' => 'aHR0cHM6Ly90aGVtZWZvcmVzdC5uZXQvaXRlbS9uZXdzcGFwZXIvNTQ4OTYwOQ==',
		'_tdcf_website' => 'ZmllbGRfNjQxMDEwYzg1ZjBiNA==',
		'tdcf_social_distancing' => '',
		'_tdcf_social_distancing' => 'ZmllbGRfNjQxMDU4MTE3MmI4NA==',
		'tdcf_safety_measures' => 'YToxOntpOjA7czozNToiU29jaWFsIGRpc3RhbmNpbmcgbWVhc3VyZXMgaW4gcGxhY2UiO30=',
		'_tdcf_safety_measures' => 'ZmllbGRfNjQxMDU4MzM3MmI4NQ==',
		'tdcf_email' => 'ZW1haWxAZW1haWwuY29t',
		'_tdcf_email' => 'ZmllbGRfNjQxMDYyMjIxYTU1Mg==',
		'tdcf_phone_number' => 'MDU1MjMzMjEzMzUy',
		'_tdcf_phone_number' => 'ZmllbGRfNjQxMDYyMzQxYTU1Mw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_attractions' => array( $tax_term_seasonal_activities_id, $tax_term_things_to_do_id ),
	),
));


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_page(array(
	'title' => 'Homepage',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'page_id' => $page_homepage_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_page(array(
	'title' => 'Login or Register',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'page_id' => $page_create_account_page_id_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_page(array(
	'title' => 'Your Account',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'page_id' => $page_my_account_page_id_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_page(array(
	'title' => 'Post Your Listing',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'page_id' => $page_calisight_post_your_listing_id,
	'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_page(array(
	'title' => 'Subscribe',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'page_id' => $page_tds_switching_plans_wizard_id,
	'parent_id' => ''
));


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Attractions',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'taxonomy' => 'tdtax_attractions',
	'tax_id' => $tax_term_things_to_do_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Tours & Day Trips',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'taxonomy' => 'tdtax_attractions',
	'tax_id' => $tax_term_tours_trips_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_taxonomy( array(
	'title' => 'History & Culture',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'taxonomy' => 'tdtax_attractions',
	'tax_id' => $tax_term_history_culture_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Adventure',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'taxonomy' => 'tdtax_attractions',
	'tax_id' => $tax_term_adventure_id,
	'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Food & Drink',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'taxonomy' => 'tdtax_attractions',
	'tax_id' => $tax_term_food_drink_id,
	'parent_id' => ''
));

$menu_item_5_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Cruises',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'taxonomy' => 'tdtax_attractions',
	'tax_id' => $tax_term_cruises_id,
	'parent_id' => ''
));

$menu_item_6_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Workshops',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'taxonomy' => 'tdtax_attractions',
	'tax_id' => $tax_term_workshops_id,
	'parent_id' => ''
));

$menu_item_7_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Shows & Concerts',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'taxonomy' => 'tdtax_attractions',
	'tax_id' => $tax_term_shows_concerts_id,
	'parent_id' => ''
));

$menu_item_8_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Seasonal Activities',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'taxonomy' => 'tdtax_attractions',
	'tax_id' => $tax_term_seasonal_activities_id,
	'parent_id' => ''
));


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_category( array(
	'title' => 'Blog',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'category_id' => $cat_blog_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_category( array(
	'title' => 'Advice',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'category_id' => $cat_advice_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_category( array(
	'title' => 'Exploration',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'category_id' => $cat_exploration_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_category( array(
	'title' => 'Travel',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'category_id' => $cat_travel_id,
	'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_category( array(
	'title' => 'Cities',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'category_id' => $cat_cities_id,
	'parent_id' => ''
));



/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_page(array(
	'title' => 'Post your Listing',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_calisight_post_your_listing_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Attractions',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'taxonomy' => 'tdtax_attractions',
	'tax_id' => $tax_term_things_to_do_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Adventure',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'taxonomy' => 'tdtax_attractions',
	'tax_id' => $tax_term_adventure_id,
	'parent_id' => $menu_item_1_id
));

$menu_item_3_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Cruises',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'taxonomy' => 'tdtax_attractions',
	'tax_id' => $tax_term_cruises_id,
	'parent_id' => $menu_item_1_id
));

$menu_item_4_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Food & Drink',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'taxonomy' => 'tdtax_attractions',
	'tax_id' => $tax_term_food_drink_id,
	'parent_id' => $menu_item_1_id
));

$menu_item_5_id = td_demo_menus::add_taxonomy( array(
	'title' => 'History & Culture',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'taxonomy' => 'tdtax_attractions',
	'tax_id' => $tax_term_history_culture_id,
	'parent_id' => $menu_item_1_id
));

$menu_item_6_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Seasonal Activities',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'taxonomy' => 'tdtax_attractions',
	'tax_id' => $tax_term_seasonal_activities_id,
	'parent_id' => $menu_item_1_id
));

$menu_item_7_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Shows & Concerts',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'taxonomy' => 'tdtax_attractions',
	'tax_id' => $tax_term_shows_concerts_id,
	'parent_id' => $menu_item_1_id
));

$menu_item_8_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Tours & Day Trips',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'taxonomy' => 'tdtax_attractions',
	'tax_id' => $tax_term_tours_trips_id,
	'parent_id' => $menu_item_1_id
));

$menu_item_9_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Workshops',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'taxonomy' => 'tdtax_attractions',
	'tax_id' => $tax_term_workshops_id,
	'parent_id' => $menu_item_1_id
));

$menu_item_10_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Blog',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_blog_id,
	'parent_id' => ''
), true );

$menu_item_11_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Travel',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_travel_id,
	'parent_id' => ''
), true );

$menu_item_12_id = td_demo_menus::add_page(array(
	'title' => 'Subscribe',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_tds_switching_plans_wizard_id,
	'parent_id' => ''
));


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
