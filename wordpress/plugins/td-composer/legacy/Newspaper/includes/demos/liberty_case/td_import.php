<?php 



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

$plan_free_plan_id = td_demo_subscription::add_plan( array(
		'name' => 'Free Plan',
		'price' => '',
		'months_in_cycle' => '',
		'trial_days' => '0',
		'is_free' => '1',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"14645b3d44e78cb";}',
	)
);

$plan_monthly_plan_id = td_demo_subscription::add_plan( array(
		'name' => 'Monthly Plan',
		'price' => '25',
		'months_in_cycle' => '1',
		'trial_days' => '0',
		'is_free' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"20645b3d44e79d4";}',
	)
);

$plan_yearly_plan_id = td_demo_subscription::add_plan( array(
		'name' => 'Yearly Plan',
		'price' => '300',
		'months_in_cycle' => '12',
		'trial_days' => '0',
		'is_free' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"47645b3d44e7a80";}',
	)
);

$page_payment_page_id_id = td_demo_content::add_page( array(
	'title' => 'Checkout - liberty_case',
	'file' => 'tds_checkout.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'payment_page_id',
	'value' => $page_payment_page_id_id,
	)
);

$page_my_account_page_id_id = td_demo_content::add_page( array(
	'title' => 'My Account - liberty_case',
	'file' => 'tds_my_account.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'my_account_page_id',
	'value' => $page_my_account_page_id_id,
	)
);

$page_create_account_page_id_id = td_demo_content::add_page( array(
	'title' => 'Login/Register - liberty_case',
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

$cat_news_id = td_demo_category::add_category(array(
    'category_name' => 'News',
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

$cat_art_culture_id = td_demo_category::add_category(array(
    'category_name' => 'Art &amp; Culture',
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

$cat_asia_id = td_demo_category::add_category(array(
    'category_name' => 'Asia',
    'parent_id' => $cat_news_id,
    'category_template' => '',
    'top_posts_style' => '',
    'description' => '',
    'background_td_pic_id' => '',
    'boxed_layout' => 'hide',
    'sidebar_id' => '',
    'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID
    'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar
));

$cat_business_id = td_demo_category::add_category(array(
    'category_name' => 'Business',
    'parent_id' => $cat_news_id,
    'category_template' => '',
    'top_posts_style' => '',
    'description' => '',
    'background_td_pic_id' => '',
    'boxed_layout' => 'hide',
    'sidebar_id' => '',
    'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID
    'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar
));

$cat_economy_id = td_demo_category::add_category(array(
    'category_name' => 'Economy',
    'parent_id' => $cat_news_id,
    'category_template' => '',
    'top_posts_style' => '',
    'description' => '',
    'background_td_pic_id' => '',
    'boxed_layout' => 'hide',
    'sidebar_id' => '',
    'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID
    'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar
));

$cat_entertainment_id = td_demo_category::add_category(array(
    'category_name' => 'Entertainment',
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

$cat_family_relationships_id = td_demo_category::add_category(array(
    'category_name' => 'Family &amp; Relationships',
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

$cat_fashion_beauty_id = td_demo_category::add_category(array(
    'category_name' => 'Fashion &amp; Beauty',
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

$cat_health_id = td_demo_category::add_category(array(
    'category_name' => 'Health',
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

$cat_public_opinion_id = td_demo_category::add_category(array(
    'category_name' => 'Public Opinion',
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

$cat_sport_id = td_demo_category::add_category(array(
    'category_name' => 'Sport',
    'parent_id' => $cat_news_id,
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
    'parent_id' => $cat_news_id,
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

$cat_world_id = td_demo_category::add_category(array(
    'category_name' => 'World',
    'parent_id' => $cat_news_id,
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
	MENUS
*/
$menu_td_demo_footer_menu_extra_id = td_demo_menus::create_menu('td-demo-footer-menu-extra', '');
$menu_td_demo_header_menu_id = td_demo_menus::create_menu('td-demo-header-menu', '');
$menu_td_demo_header_menu_extra_id = td_demo_menus::create_menu('td-demo-header-menu-extra', '');
$menu_td_demo_top_menu_id = td_demo_menus::create_menu('td-demo-top-menu', '');


/*  ---------------------------------------------------------------------------- 
	PAGES
*/
$page_pricing_plans_modal_id = td_demo_content::add_page( array(
	'title' => 'Pricing Plans Modal',
	'file' => 'pricing_plans_modal.txt',
	'demo_unique_id' => '94645b3d45542f3',
));

$page_tds_switching_plans_wizard_id = td_demo_content::add_page( array(
	'title' => 'Switching plans wizard',
	'file' => 'tds_switching_plans_wizard.txt',
	'demo_unique_id' => '15645b3d455498c',
));

$page_modal_menu_id = td_demo_content::add_page( array(
	'title' => 'Modal Menu',
	'file' => 'modal_menu.txt',
	'demo_unique_id' => '78645b3d4554fd2',
));

$page_lifestyle_menu_id = td_demo_content::add_page( array(
	'title' => 'Lifestyle Menu',
	'file' => 'lifestyle_menu.txt',
	'demo_unique_id' => '46645b3d4556024',
));

$page_news_menu_id = td_demo_content::add_page( array(
	'title' => 'News Menu',
	'file' => 'news_menu.txt',
	'demo_unique_id' => '95645b3d4556781',
));

$page_homepage_id = td_demo_content::add_page( array(
	'title' => 'Homepage',
	'file' => 'homepage.txt',
	'homepage' => true,
	'demo_unique_id' => '45645b3d4557124',
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
				'tds_title' => 'Unlock this content',
				'tds_message' => 'With an online subscription you get access to daily and weekly updates with news from all across the globe. Stay ahead of the curve with Liberty Case.',
				'tds_submit_btn_text' => 'Subscribe',
				'tds_after_btn_text' => 'You can rest assured we won\'t be spamming you, and we will cater the email updates to your interests.',
				'tds_pp_msg' => 'I consent to processing of my data according to <a href=\"#\">Terms of Use</a> & <a href=\"#\">Privacy Policy</a>.',
				'tds_locker_cf_1_name' => 'Custom field 1',
				'tds_locker_cf_2_name' => 'Custom field 2',
				'tds_locker_cf_3_name' => 'Custom field 3',
			),
	'tds_payable' => 'paid_subscription',
	'tds_paid_subs_page_id' => $page_tds_switching_plans_wizard_id,
	'tds_paid_subs_plan_ids' => [$plan_free_plan_id,$plan_monthly_plan_id,$plan_yearly_plan_id],
			'tds_locker_styles' => array(
				'tds_bg_color' => '#ffffff',
				'tds_title_color' => '#18242c',
				'tds_message_color' => '#18242c',
				'tds_submit_btn_text_color' => '#ffffff',
				'tds_submit_btn_text_color_h' => '#18242c',
				'tds_submit_btn_bg_color' => '#48b0f2',
				'tds_submit_btn_bg_color_h' => '#94d5ff',
				'tds_pp_checked_color' => '#48b0f2',
				'tds_pp_check_bg' => '#ffffff',
				'tds_pp_check_bg_f' => '#ffffff',
				'tds_pp_check_border_color' => '#48b0f2',
				'tds_pp_check_border_color_f' => '#48b0f2',
				'tds_pp_msg_color' => '#18242c',
				'tds_pp_msg_links_color' => '#48b0f2',
				'tds_pp_msg_links_color_h' => '#94d5ff',
				'tds_general_font_family' => 'sans-serif_global',
				'tds_title_font_family' => 'serif_global',
				'tds_title_font_size' => '30',
				'tds_title_font_line_height' => '1.2',
				'tds_title_font_weight' => '700',
				'tds_message_font_family' => 'sans-serif_global',
				'tds_message_font_size' => '20',
				'tds_message_font_line_height' => '1.4',
				'tds_message_font_weight' => '500',
				'tds_submit_btn_text_font_family' => 'sans-serif_global',
				'tds_submit_btn_text_font_size' => '16',
				'tds_submit_btn_text_font_line_height' => '1.2',
				'tds_submit_btn_text_font_weight' => '700',
				'tds_submit_btn_text_font_transform' => 'uppercase',
				'tds_submit_btn_text_font_spacing' => '1',
				'tds_after_btn_text_font_family' => 'sans-serif_global',
				'tds_after_btn_text_font_size' => '14',
				'tds_after_btn_text_font_line_height' => '1.2',
				'tds_after_btn_text_font_weight' => '500',
				'tds_pp_msg_font_family' => 'sans-serif_global',
				'tds_pp_msg_font_size' => '18',
				'tds_pp_msg_font_line_height' => '1.2',
				'tds_pp_msg_font_weight' => '500',
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

td_util::update_option('tds_demo_options', 'a:1:{s:5:"plans";a:3:{i:0;a:2:{s:9:"unique_id";s:15:"14645b3d44e78cb";s:4:"name";s:9:"Free Plan";}i:1;a:2:{s:9:"unique_id";s:15:"20645b3d44e79d4";s:4:"name";s:12:"Monthly Plan";}i:2;a:2:{s:9:"unique_id";s:15:"47645b3d44e7a80";s:4:"name";s:11:"Yearly Plan";}}}');


/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTION - end phase 2
*/

/*  ---------------------------------------------------------------------------- 
	POSTS
*/
$post_td_post_droughts_leave_cargo_riverboats_high_and_dry_how_to_prepare_for_the_worst_id = td_demo_content::add_post( array(
	'title' => 'Droughts leave cargo riverboats high and dry, how to prepare for the worst',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_public_opinion_id,),
));

$post_td_post_japan_arrests_alleged_ringleaders_of_luffy_crime_spree_today_in_a_situational_moment_id = td_demo_content::add_post( array(
	'title' => 'Japan arrests alleged ringleaders of \'Luffy\' crime spree today in a situational moment',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_public_opinion_id,),
));

$post_td_post_pakistan_seeks_imf_bailout_to_stave_off_economic_collapse_using_this_brilliant_scheme_id = td_demo_content::add_post( array(
	'title' => 'Pakistan seeks IMF bailout to stave off economic collapse using this brilliant scheme',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'tds_locker' => '472',
	'categories_id_array' => array($cat_public_opinion_id,),
));

$post_td_post_ukraine_war_elon_musks_spacex_firm_bars_kyiv_from_using_starlink_tech_for_drone_control_id = td_demo_content::add_post( array(
	'title' => 'Ukraine war: Elon Musk\'s SpaceX firm bars Kyiv from using Starlink tech for drone control',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_public_opinion_id,),
));

$post_td_post_chinese_surveillance_balloon_part_of_massive_program_over_5_continents_blinken_id = td_demo_content::add_post( array(
	'title' => 'Chinese surveillance balloon part of massive program over 5 continents: Blinken',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_public_opinion_id,),
));

$post_td_post_an_earthquake_has_killed_thousands_in_turkey_and_syria_heres_how_to_help_survivors_id = td_demo_content::add_post( array(
	'title' => 'An Earthquake Has Killed Thousands in Turkey and Syria—Here’s How to Help Survivors',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_public_opinion_id,),
));

$post_td_post_yale_discriminates_against_students_like_me_with_mental_health_disabilities_thats_why_im_suing_id = td_demo_content::add_post( array(
	'title' => 'Yale Discriminates Against Students Like Me With Mental Health Disabilities, That’s Why I’m Suing',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_public_opinion_id,),
));

$post_td_post_the_best_bathroom_paint_colors_thatll_make_your_space_feel_incredibly_luxurious_id = td_demo_content::add_post( array(
	'title' => 'The Best Bathroom Paint Colors That’ll Make Your Space Feel Incredibly Luxurious',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_public_opinion_id,),
));

$post_td_post_heres_where_to_get_a_heart_shaped_pizza_for_v_day_this_year_id = td_demo_content::add_post( array(
	'title' => 'Here’s Where to Get a Heart-Shaped Pizza for V-Day This Year',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_public_opinion_id,),
));

$post_td_post_how_to_watch_the_fabelmans_aka_steven_spielbergs_latest_masterpiece_id = td_demo_content::add_post( array(
	'title' => 'How to Watch ‘The Fabelmans,’ AKA Steven Spielberg’s Latest Masterpiece',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_public_opinion_id,),
));

$post_td_post_alec_baldwin_seeks_to_disqualify_special_prosecutor_in_rust_shooting_case_id = td_demo_content::add_post( array(
	'title' => 'Alec Baldwin Seeks to Disqualify Special Prosecutor in \'Rust\' Shooting Case',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_entertainment_id,),
));

$post_td_post_milo_ventimiglia_explains_his_physical_transformation_from_this_is_us_to_the_company_you_keep_id = td_demo_content::add_post( array(
	'title' => 'Milo Ventimiglia Explains His Physical Transformation from \'This is Us\' to \'The Company You Keep\'',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_entertainment_id,),
));

$post_td_post_paul_rudd_shares_update_on_jeremy_renner_amid_recovery_from_his_snowplow_accident_id = td_demo_content::add_post( array(
	'title' => 'Paul Rudd Shares Update on Jeremy Renner Amid Recovery From His Snowplow Accident',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_entertainment_id,),
));

$post_td_post_king_charles_hole_in_his_sock_revealed_after_royal_takes_off_shoes_for_mosque_visit_id = td_demo_content::add_post( array(
	'title' => 'King Charles\' Hole in His Sock Revealed After Royal Takes Off Shoes for Mosque Visit',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_entertainment_id,),
));

$post_td_post_scream_6_star_jasmin_savoy_brown_struggles_to_escape_ghostface_in_terrifying_super_bowl_ad_id = td_demo_content::add_post( array(
	'title' => 'Scream 6 Star Jasmin Savoy Brown Struggles to Escape Ghostface in Terrifying Super Bowl Ad',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_entertainment_id,),
));

$post_td_post_from_the_archives_wangechi_mutu_dresses_cultural_critique_in_freakishly_beautiful_disguises_id = td_demo_content::add_post( array(
	'title' => 'From the Archives: Wangechi Mutu Dresses Cultural Critique in Freakishly Beautiful Disguises',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_art_culture_id,),
));

$post_td_post_a_kandinsky_recently_restituted_to_its_original_german_jewish_owners_expected_to_fetch_45_m_at_auction_id = td_demo_content::add_post( array(
	'title' => 'A Kandinsky Recently Restituted To Its Original German-Jewish Owners Expected to Fetch $45 M. at Auction',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_art_culture_id,),
));

$post_td_post_us_defense_department_lifts_ban_on_release_of_art_by_guantanamo_prisoners_but_details_are_hazy_id = td_demo_content::add_post( array(
	'title' => 'US Defense Department Lifts Ban on Release of Art by Guantánamo Prisoners — But Details Are Hazy',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_art_culture_id,),
));

$post_td_post_brueghel_painting_heads_to_auction_in_france_after_being_discovered_during_a_familys_property_review_id = td_demo_content::add_post( array(
	'title' => 'Brueghel Painting Heads to Auction in France after Being Discovered During a Family’s Property Review',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_art_culture_id,),
));

$post_td_post_11_shows_to_see_in_mexico_city_during_zona_maco_whenever_youre_visiting_id = td_demo_content::add_post( array(
	'title' => '11 Shows to See in Mexico City During Zona Maco whenever you\'re visiting',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_art_culture_id,),
));

$post_td_post_germanys_new_visa_is_aimed_at_foreign_workers_when_will_it_launch_and_whos_eligible_id = td_demo_content::add_post( array(
	'title' => 'Germany\'s new visa is aimed at foreign workers: When will it launch and who’s eligible?',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_travel_id,),
));

$post_td_post_worlds_longest_underwater_rail_and_road_tunnel_will_connect_germany_and_denmark_by_2029_id = td_demo_content::add_post( array(
	'title' => 'World’s longest underwater rail and road tunnel will connect Germany and Denmark by 2029',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_travel_id,),
));

$post_td_post_second_european_airline_in_less_than_a_week_cancels_all_flights_after_going_bust_id = td_demo_content::add_post( array(
	'title' => 'Second European airline in less than a week cancels all flights after going bust',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_travel_id,),
));

$post_td_post_french_ski_resorts_how_a_snowless_town_put_itself_back_on_the_map_id = td_demo_content::add_post( array(
	'title' => 'French ski resorts: How a snowless town put itself back on the map',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_travel_id,),
));

$post_td_post_hong_kong_flights_500000_free_airline_tickets_up_for_grabs_from_march_in_bid_to_boost_tourism_id = td_demo_content::add_post( array(
	'title' => 'Hong Kong flights: 500,000 free airline tickets up for grabs from March in bid to boost tourism',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_travel_id,),
));

$post_td_post_after_damar_hamlins_cardiac_arrest_attention_turns_to_chest_pads_for_young_athletes_id = td_demo_content::add_post( array(
	'title' => 'After Damar Hamlin\'s cardiac arrest, attention turns to chest pads for young athletes',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_health_id,),
));

$post_td_post_people_with_diabetes_struggle_to_find_ozempic_as_it_soars_in_popularity_as_a_weight_loss_aid_id = td_demo_content::add_post( array(
	'title' => 'People with diabetes struggle to find Ozempic as it soars in popularity as a weight loss aid',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_health_id,),
));

$post_td_post_some_workers_at_u_s_hospital_giant_hca_say_it_puts_profits_above_patient_care_id = td_demo_content::add_post( array(
	'title' => 'Some workers at U.S. hospital giant HCA say it puts profits above patient care',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_health_id,),
));

$post_td_post_if_youre_sitting_all_day_science_shows_how_to_undo_the_health_risks_take_activity_snacks_id = td_demo_content::add_post( array(
	'title' => 'If you\'re sitting all day, science shows how to undo the health risks. Take activity snacks',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_health_id,),
));

$post_td_post_help_during_childbirth_has_declined_survey_finds_what_this_means_for_new_mothers_id = td_demo_content::add_post( array(
	'title' => 'Help during childbirth has declined, survey finds - what this means for new mothers',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_health_id,),
));

$post_td_post_clean_beauty_is_booming_and_black_consumers_fear_being_left_behind_id = td_demo_content::add_post( array(
	'title' => 'Clean Beauty Is Booming, and Black Consumers Fear Being Left Behind',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_fashion_beauty_id,),
));

$post_td_post_how_to_make_a_difficult_to_market_product_cool_id = td_demo_content::add_post( array(
	'title' => 'How to Make a Difficult-to-Market Product Cool',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_fashion_beauty_id,),
));

$post_td_post_olaplexs_broke_my_hair_problem_is_a_tiktok_cautionary_tale_id = td_demo_content::add_post( array(
	'title' => 'Olaplex’s ‘Broke My Hair’ Problem Is a TikTok Cautionary Tale',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'tds_locker' => '472',
	'categories_id_array' => array($cat_fashion_beauty_id,),
));

$post_td_post_why_its_so_hard_for_fashion_retailers_to_succeed_in_beauty_id = td_demo_content::add_post( array(
	'title' => 'Why It’s So Hard for Fashion Retailers to Succeed in Beauty',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_fashion_beauty_id,),
));

$post_td_post_selling_lipstick_and_stockpiling_cash_how_beauty_brands_are_preparing_for_a_recession_id = td_demo_content::add_post( array(
	'title' => 'Selling Lipstick and Stockpiling Cash: How Beauty Brands Are Preparing for a Recession',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_fashion_beauty_id,),
));

$post_td_post_relationship_quality_affects_depressive_symptoms_in_african_american_couples_id = td_demo_content::add_post( array(
	'title' => 'Relationship quality affects depressive symptoms in African American couples',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_family_relationships_id,),
));

$post_td_post_poor_diet_household_chaos_may_impair_young_childrens_cognitive_skills_id = td_demo_content::add_post( array(
	'title' => 'Poor diet, household chaos may impair young children\'s cognitive skills',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_family_relationships_id,),
));

$post_td_post_parents_are_too_hard_on_themselves_teens_more_positive_about_their_parenting_id = td_demo_content::add_post( array(
	'title' => 'Parents are too hard on themselves: Teens more positive about their parenting',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_family_relationships_id,),
));

$post_td_post_fathers_who_drink_heavily_report_less_positive_involvement_with_their_children_id = td_demo_content::add_post( array(
	'title' => 'Fathers who drink heavily report less positive involvement with their children',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_family_relationships_id,),
));

$post_td_post_better_sleep_for_kids_starts_with_better_sleep_for_parents_especially_after_holiday_disruptions_to_routines_id = td_demo_content::add_post( array(
	'title' => 'Better sleep for kids starts with better sleep for parents, especially after holiday disruptions to routines',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_family_relationships_id,),
));

$post_td_post_astronomers_discover_milky_way_galaxys_most_distant_stars_id = td_demo_content::add_post( array(
	'title' => 'Astronomers discover Milky Way galaxy\'s most-distant stars',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_world_id,),
));

$post_td_post_swedens_lkab_finds_europes_biggest_deposit_of_rare_earth_metals_id = td_demo_content::add_post( array(
	'title' => 'Sweden\'s LKAB finds Europe\'s biggest deposit of rare earth metals',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_world_id,),
));

$post_td_post_romania_conducts_raids_in_probe_of_influencer_andrew_tate_id = td_demo_content::add_post( array(
	'title' => 'Romania conducts raids in probe of influencer Andrew Tate',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_world_id,),
));

$post_td_post_peru_closes_tourist_hub_airport_as_nationwide_protests_persist_id = td_demo_content::add_post( array(
	'title' => 'Peru closes tourist hub airport as nationwide protests persist',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_world_id,),
));

$post_td_post_what_we_know_about_sun_cable_and_its_collapsed_australia_singapore_solar_energy_project_id = td_demo_content::add_post( array(
	'title' => 'What we know about Sun Cable and its collapsed Australia-Singapore solar energy project',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_world_id,),
));

$post_td_post_elon_musk_says_he_will_resign_as_twitters_ceo_when_he_finds_a_replacement_id = td_demo_content::add_post( array(
	'title' => 'Elon Musk says he will resign as Twitter\'s CEO when he finds a replacement',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_tech_id,),
));

$post_td_post_biden_signs_tiktok_ban_for_government_devices_setting_up_a_chaotic_2023_for_the_app_id = td_demo_content::add_post( array(
	'title' => 'Biden signs TikTok ban for government devices, setting up a chaotic 2023 for the app',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_tech_id,),
));

$post_td_post_video_game_workers_form_microsofts_first_u_s_labor_union_id = td_demo_content::add_post( array(
	'title' => 'Video game workers form Microsoft’s first U.S. labor union',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_tech_id,),
));

$post_td_post_hackers_leak_sensitive_files_after_attack_on_san_francisco_transit_police_id = td_demo_content::add_post( array(
	'title' => 'Hackers leak sensitive files after attack on San Francisco transit police',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_tech_id,),
));

$post_td_post_aviation_system_that_caused_widespread_flight_delays_traces_back_to_the_1860s_id = td_demo_content::add_post( array(
	'title' => 'Aviation system that caused widespread flight delays traces back to the 1860s',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_tech_id,),
));

$post_td_post_mercedes_benz_recalls_324000_vehicles_over_water_intrusion_issue_that_could_stall_engines_id = td_demo_content::add_post( array(
	'title' => 'Mercedes-Benz recalls 324,000 vehicles over water-intrusion issue that could stall engines',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_business_id,),
));

$post_td_post_bed_bath_beyond_reports_wider_than_expected_loss_as_possible_bankruptcy_looms_id = td_demo_content::add_post( array(
	'title' => 'Bed Bath & Beyond reports wider-than-expected loss as possible bankruptcy looms',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_business_id,),
));

$post_td_post_cvs_sued_by_a_fired_nurse_practitioner_who_refused_to_prescribe_birth_control_due_to_her_religious_beliefs_id = td_demo_content::add_post( array(
	'title' => 'CVS sued by a fired nurse practitioner who refused to prescribe birth control due to her religious beliefs',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_business_id,),
));

$post_td_post_soaring_u_s_egg_prices_put_pressure_on_consumers_businesses_id = td_demo_content::add_post( array(
	'title' => 'Soaring U.S. egg prices put pressure on consumers, businesses',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_business_id,),
));

$post_td_post_emerging_market_governments_raise_40bn_in_january_borrowing_binge_id = td_demo_content::add_post( array(
	'title' => 'Emerging market governments raise $40bn in January borrowing binge',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_business_id,),
));

$post_td_post_latest_buzz_on_open_nfl_head_coach_jobs_candidates_to_watch_and_everything_weve_heard_id = td_demo_content::add_post( array(
	'title' => 'Latest buzz on open NFL head-coach jobs: Candidates to watch and everything we\'ve heard',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_sport_id,),
));

$post_td_post_what_to_know_for_the_nfls_wild_card_games_score_picks_bold_predictions_key_matchups_for_all_six_games_id = td_demo_content::add_post( array(
	'title' => 'What to know for the NFL\'s wild-card games: Score picks, bold predictions, key matchups for all six games',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_sport_id,),
));

$post_td_post_james_vowles_williams_appoint_former_mercedes_strategy_chief_as_team_principal_id = td_demo_content::add_post( array(
	'title' => 'James Vowles: Williams appoint former Mercedes strategy chief as team principal',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_sport_id,),
));

$post_td_post_the_huge_stars_steve_cohen_could_chase_next_after_carlos_correa_disappointment_id = td_demo_content::add_post( array(
	'title' => 'The huge stars Steve Cohen could chase next after Carlos Correa disappointment',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_sport_id,),
));

$post_td_post_why_former_nfl_coach_compares_purdy_to_49ers_legend_montana_id = td_demo_content::add_post( array(
	'title' => 'Why former NFL coach compares Purdy to 49ers legend Montana',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_sport_id,),
));

$post_td_post_large_pay_gains_outpace_state_minimum_wage_boosts_for_many_workers_id = td_demo_content::add_post( array(
	'title' => 'Large Pay Gains Outpace State Minimum-Wage Boosts for Many Workers',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_economy_id,),
));

$post_td_post_adp_private_payroll_growth_slowed_to_106000_in_january_as_weather_hit_hiring_id = td_demo_content::add_post( array(
	'title' => 'ADP: Private payroll growth slowed to 106,000 in January as weather hit hiring',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_economy_id,),
));

$post_td_post_world_bank_cuts_2023_global_growth_projection_as_inflation_persists_id = td_demo_content::add_post( array(
	'title' => 'World Bank Cuts 2023 Global Growth Projection as Inflation Persists',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_economy_id,),
));

$post_td_post_europes_mild_winter_cushions_economic_blow_of_ukraine_war_id = td_demo_content::add_post( array(
	'title' => 'Europe’s Mild Winter Cushions Economic Blow of Ukraine War',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_economy_id,),
));

$post_td_post_inflation_weary_americans_find_some_relief_as_prices_fall_for_dozens_of_products_id = td_demo_content::add_post( array(
	'title' => 'Inflation Weary Americans Find Some Relief as Prices Fall for Dozens of Products',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_economy_id,),
));

$post_td_post_japan_prosecutors_indict_abe_murder_suspect_after_psychiatric_review_id = td_demo_content::add_post( array(
	'title' => 'Japan prosecutors indict Abe murder suspect after psychiatric review',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_asia_id,),
));

$post_td_post_sri_lanka_church_seeks_criminal_justice_for_easter_bombings_id = td_demo_content::add_post( array(
	'title' => 'Sri Lanka church seeks criminal justice for Easter bombings',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_asia_id,),
));

$post_td_post_japans_emperor_wishes_for_peaceful_2023_in_first_live_new_year_address_since_pandemic_began_id = td_demo_content::add_post( array(
	'title' => 'Japan\'s emperor wishes for peaceful 2023 in first live New Year address since pandemic began',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_asia_id,),
));

$post_td_post_cramped_shelters_fill_up_fast_as_indias_himalayan_town_sinks_id = td_demo_content::add_post( array(
	'title' => 'Cramped shelters fill up fast as India\'s Himalayan town sinks',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_asia_id,),
));

$post_td_post_jokowi_acknowledges_indonesias_past_human_rights_violations_id = td_demo_content::add_post( array(
	'title' => 'Jokowi acknowledges Indonesia\'s past human rights violations',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_asia_id,),
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
	'title' => 'Art & Culture',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'category_id' => $cat_art_culture_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_category( array(
	'title' => 'Entertainment',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'category_id' => $cat_entertainment_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_category( array(
	'title' => 'Family & Relationships',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'category_id' => $cat_family_relationships_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_category( array(
	'title' => 'Fashion & Beauty',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'category_id' => $cat_fashion_beauty_id,
	'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_category( array(
	'title' => 'Health',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'category_id' => $cat_health_id,
	'parent_id' => ''
));

$menu_item_5_id = td_demo_menus::add_category( array(
	'title' => 'Travel',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'category_id' => $cat_travel_id,
	'parent_id' => ''
));


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_mega_menu( array(
	'title' => 'News',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
    'page_id' => $page_news_menu_id,
	'parent_id' => ''
), true);

$menu_item_1_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Lifestyle',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
    'page_id' => $page_lifestyle_menu_id,
	'parent_id' => ''
), true);

$menu_item_2_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Public Opinion',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_public_opinion_id,
	'parent_id' => ''
), true);



/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_category( array(
	'title' => 'Asia',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_asia_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_category( array(
	'title' => 'Business',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_business_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_category( array(
	'title' => 'Economy',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_economy_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_category( array(
	'title' => 'Sport',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_sport_id,
	'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_category( array(
	'title' => 'Tech',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_tech_id,
	'parent_id' => ''
));

$menu_item_5_id = td_demo_menus::add_category( array(
	'title' => 'World',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_world_id,
	'parent_id' => ''
));


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_category( array(
	'title' => 'Art & Culture',
	'add_to_menu_id' => $menu_td_demo_top_menu_id,
	'category_id' => $cat_art_culture_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_category( array(
	'title' => 'Entertainment',
	'add_to_menu_id' => $menu_td_demo_top_menu_id,
	'category_id' => $cat_entertainment_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_category( array(
	'title' => 'Family & Relationships',
	'add_to_menu_id' => $menu_td_demo_top_menu_id,
	'category_id' => $cat_family_relationships_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_category( array(
	'title' => 'Fashion & Beauty',
	'add_to_menu_id' => $menu_td_demo_top_menu_id,
	'category_id' => $cat_fashion_beauty_id,
	'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_category( array(
	'title' => 'Health',
	'add_to_menu_id' => $menu_td_demo_top_menu_id,
	'category_id' => $cat_health_id,
	'parent_id' => ''
));

$menu_item_5_id = td_demo_menus::add_category( array(
	'title' => 'Travel',
	'add_to_menu_id' => $menu_td_demo_top_menu_id,
	'category_id' => $cat_travel_id,
	'parent_id' => ''
));


/*  ---------------------------------------------------------------------------- 
	CLOUD TEMPLATES
*/
$template_tag_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'LC - Tag Template',
	'file' => 'tag_cloud_template.txt',
	'template_type' => 'tag',
));

td_demo_misc::update_global_tag_template( 'tdb_template_' . $template_tag_template_id);


$template_404_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'LC - 404 Template',
	'file' => '404_cloud_template.txt',
	'template_type' => '404',
));

td_demo_misc::update_global_404_template( 'tdb_template_' . $template_404_template_id);


$template_date_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'LC - Date Template',
	'file' => 'date_cloud_template.txt',
	'template_type' => 'date',
));

td_demo_misc::update_global_date_template( 'tdb_template_' . $template_date_template_id);


$template_search_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'LC - Search Template',
	'file' => 'search_cloud_template.txt',
	'template_type' => 'search',
));

td_demo_misc::update_global_search_template( 'tdb_template_' . $template_search_template_id);


$template_author_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'LC - Author Template',
	'file' => 'author_cloud_template.txt',
	'template_type' => 'author',
));

td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_id);


$template_category_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'LC - Category Template',
	'file' => 'cat_cloud_template.txt',
	'template_type' => 'category',
));

td_demo_misc::update_global_category_template( 'tdb_template_' . $template_category_template_id);


$template_single_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'LC - Single Template',
	'file' => 'post_cloud_template.txt',
	'template_type' => 'single',
));

td_util::update_option('td_default_site_post_template', 'tdb_template_' . $template_single_template_id);


$template_footer_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'LC - Footer Template',
	'file' => 'footer_cloud_template.txt',
	'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_template_id);


$template_header_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'LC - Header Template',
	'file' => 'header_cloud_template.txt',
	'template_type' => 'header',
));

td_demo_misc::update_global_header_template( 'tdb_template_' . $template_header_template_id);



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
