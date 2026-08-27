<?php

$menu_td_demo_custom_menu_id = td_demo_menus::create_menu('td-demo-custom-menu', '');
$menu_td_demo_header_menu_id = td_demo_menus::create_menu('td-demo-header-menu', '');
$menu_td_demo_header_menu_extra_id = td_demo_menus::create_menu('td-demo-header-menu-extra', '');

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
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"4564a3d7cb976e5";}',
	)
);

$plan_yearly_plan_id = td_demo_subscription::add_plan( array(
		'name' => 'Yearly Plan',
		'price' => '100',
		'months_in_cycle' => '12',
		'trial_days' => '0',
		'is_free' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"9064a3d7cb9785a";}',
	)
);

$plan_monthly_plan_id = td_demo_subscription::add_plan( array(
		'name' => 'Monthly Plan',
		'price' => '10',
		'months_in_cycle' => '1',
		'trial_days' => '0',
		'is_free' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"6864a3d7cb978d7";}',
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
$cat_ai_id = td_demo_category::add_category(array(
	'category_name' => 'AI',
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

$cat_entertainment_id = td_demo_category::add_category(array(
	'category_name' => 'Entertainment',
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

$cat_social_good_id = td_demo_category::add_category(array(
	'category_name' => 'Social Good',
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

$cat_videos_id = td_demo_category::add_category(array(
	'category_name' => 'Videos',
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
$template_module_template_3_id = td_demo_content::add_cloud_template( array(
	'title' => 'Module Template 3',
	'file' => 'module_template_3_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '375',
));

$template_module_template_2_id = td_demo_content::add_cloud_template( array(
	'title' => 'Module Template 2',
	'file' => 'module_template_2_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '362',
));

$template_module_template_1_id = td_demo_content::add_cloud_template( array(
	'title' => 'Module Template 1',
	'file' => 'module_template_1_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '353',
));

$template_module_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Module Template',
	'file' => 'module_template_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '322',
));


/*  ---------------------------------------------------------------------------- 
	ATTACHMENTS
*/

/*  ---------------------------------------------------------------------------- 
	PAGES
*/
$page_modal_page_1_id = td_demo_content::add_page( array(
	'title' => 'Modal page 1',
	'file' => 'modal_page_1.txt',
	'demo_unique_id' => '1964a3d7cbb21ef',
));

$page_home_id = td_demo_content::add_page( array(
	'title' => 'Home',
	'file' => 'home.txt',
	'homepage' => true,
	'demo_unique_id' => '5164a3d7cbb27a6',
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
				'tds_title' => 'This Content Is Only For Subscribers',
				'tds_message' => 'Please subscribe to unlock this content.',
				'tds_submit_btn_text' => 'Subscribe to unlock',
				'tds_pp_msg' => 'I consent to processing of my data according to <a href=\"#\">Terms of Use</a> & <a href=\"#\">Privacy Policy</a>',
				'tds_locker_cf_1_name' => 'Custom field 1',
				'tds_locker_cf_2_name' => 'Custom field 2',
				'tds_locker_cf_3_name' => 'Custom field 3',
			),
	'tds_paid_subs_page_id' => '259',
	'tds_paid_subs_plan_ids' => [$plan_free_plan_id,$plan_yearly_plan_id,$plan_monthly_plan_id],
			'tds_locker_styles' => array(
				'tds_bg_color' => '#ffffff',
				'all_tds_border' => '2px',
				'all_tds_border_color' => '#cccccc',
				'tds_title_color' => '#000000',
				'tds_message_color' => '#000000',
				'tds_submit_btn_text_color' => '#ffffff',
				'tds_submit_btn_text_color_h' => '#ffffff',
				'tds_submit_btn_bg_color' => '#b33d72',
				'tds_submit_btn_bg_color_h' => '#8c2051',
				'tds_after_btn_text_color' => '#b33d72',
				'tds_pp_checked_color' => '#000000',
				'tds_pp_check_bg' => '#b3eb48',
				'tds_pp_check_bg_f' => '#b3eb48',
				'tds_pp_check_border_color' => '#000000',
				'tds_pp_check_border_color_f' => '#000000',
				'tds_pp_msg_color' => '#000000',
				'tds_pp_msg_links_color' => '#b3eb48',
				'tds_pp_msg_links_color_h' => '#000000',
				'tds_general_font_family' => 'insight-primary_global',
				'tds_title_font_family' => 'insight-primary_global',
				'tds_title_font_weight' => '700',
				'tds_message_font_family' => 'insight-primary_global',
				'tds_message_font_size' => '16',
				'tds_message_font_line_height' => '1.2',
				'tds_message_font_weight' => '600',
				'tds_submit_btn_text_font_family' => 'insight-primary_global',
				'tds_submit_btn_text_font_size' => '22',
				'tds_submit_btn_text_font_weight' => '700',
				'tds_submit_btn_text_font_transform' => 'uppercase',
				'tds_submit_btn_text_font_spacing' => '-1',
				'tds_after_btn_text_font_family' => 'insight-primary_global',
				'tds_pp_msg_font_family' => 'insight-primary_global',
				'tds_pp_msg_font_size' => '14',
				'tds_pp_msg_font_line_height' => '1.2',
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

td_util::update_option('tds_demo_options', 'a:1:{s:5:"plans";a:3:{i:0;a:2:{s:9:"unique_id";s:15:"4564a3d7cb976e5";s:4:"name";s:9:"Free Plan";}i:1;a:2:{s:9:"unique_id";s:15:"9064a3d7cb9785a";s:4:"name";s:11:"Yearly Plan";}i:2;a:2:{s:9:"unique_id";s:15:"6864a3d7cb978d7";s:4:"name";s:12:"Monthly Plan";}}}');


/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTION - end phase 2
*/

/*  ---------------------------------------------------------------------------- 
	POSTS
*/
$post_td_post_living_well_news_and_tips_for_a_balanced_and_fulfilling_lifestyle_id = td_demo_content::add_post( array(
	'title' => 'Living Well: News and Tips for a Balanced and Fulfilling Lifestyle',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'tds_locker' => '5',
	'categories_id_array' => array($cat_lifestyle_id,),
));

$post_td_post_the_art_of_living_news_and_features_on_culture_travel_and_experiences_id = td_demo_content::add_post( array(
	'title' => 'The Art of Living: News and Features on Culture, Travel, and Experiences',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_lifestyle_id,),
));

$post_td_post_travel_tales_exciting_destinations_tips_and_travel_inspiratio_id = td_demo_content::add_post( array(
	'title' => 'Travel Tales: Exciting Destinations, Tips, and Travel Inspiratio',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'tds_locker' => '5',
	'categories_id_array' => array($cat_lifestyle_id,),
));

$post_td_post_life_hacks_practical_tips_and_life_changing_advice_for_a_better_lifestyle_id = td_demo_content::add_post( array(
	'title' => 'Life Hacks: Practical Tips and Life-Changing Advice for a Better Lifestyle',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_lifestyle_id,),
));

$post_td_post_home_and_harmony_news_and_ideas_for_creating_a_cozy_living_space_id = td_demo_content::add_post( array(
	'title' => 'Home and Harmony: News and Ideas for Creating a Cozy Living Space',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_lifestyle_id,),
));

$post_td_post_living_in_style_latest_updates_and_stories_from_the_world_of_lifestyle_id = td_demo_content::add_post( array(
	'title' => 'Living in Style: Latest Updates and Stories from the World of Lifestyle',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_lifestyle_id,),
));

$post_td_post_building_bridges_connecting_communities_through_social_impact_news_id = td_demo_content::add_post( array(
	'title' => 'Building Bridges: Connecting Communities through Social Impact News',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_social_good_id,),
));

$post_td_post_empowering_humanity_news_and_stories_of_collective_goodness_id = td_demo_content::add_post( array(
	'title' => 'Empowering Humanity: News and Stories of Collective Goodness',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'tds_locker' => '5',
	'categories_id_array' => array($cat_social_good_id,),
));

$post_td_post_sustainable_solutions_news_and_trends_in_environmental_and_social_responsibility_id = td_demo_content::add_post( array(
	'title' => 'Sustainable Solutions: News and Trends in Environmental and Social Responsibility',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_social_good_id,),
));

$post_td_post_making_a_difference_daily_stories_of_hope_and_progress_in_the_social_good_realm_id = td_demo_content::add_post( array(
	'title' => 'Making a Difference Daily: Stories of Hope and Progress in the Social Good Realm',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_social_good_id,),
));

$post_td_post_the_good_news_gazette_reporting_on_the_bright_side_of_social_change_id = td_demo_content::add_post( array(
	'title' => 'The Good News Gazette: Reporting on the Bright Side of Social Change',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_social_good_id,),
));

$post_td_post_impact_spotlight_inspiring_stories_of_social_good_and_positive_change_id = td_demo_content::add_post( array(
	'title' => 'Impact Spotlight: Inspiring Stories of Social Good and Positive Change\"',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_social_good_id,),
));

$post_td_post_video_breakdown_expert_analysis_and_commentary_on_noteworthy_videos_id = td_demo_content::add_post( array(
	'title' => 'Video Breakdown: Expert Analysis and Commentary on Noteworthy Videos',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_videos_id,),
));

$post_td_post_on_screen_news_latest_developments_and_trends_in_the_video_landscape_id = td_demo_content::add_post( array(
	'title' => 'On-Screen News: Latest Developments and Trends in the Video Landscape',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_videos_id,),
));

$post_td_post_video_spotlight_unveiling_the_best_and_brightest_in_video_creation_id = td_demo_content::add_post( array(
	'title' => 'Video Spotlight: Unveiling the Best and Brightest in Video Creation\"',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_videos_id,),
));

$post_td_post_video_buzz_discover_the_most_shared_and_talked_about_videos_id = td_demo_content::add_post( array(
	'title' => 'Video Buzz: Discover the Most Shared and Talked About Videos',
	'file' => 'post_default.txt',
	'categories_id_array' => array($cat_videos_id,),
));

$post_td_post_viral_video_watch_trending_clips_and_memorable_moments_id = td_demo_content::add_post( array(
	'title' => 'Viral Video Watch: Trending Clips and Memorable Moments',
	'file' => 'post_default.txt',
	'categories_id_array' => array($cat_videos_id,),
));

$post_td_post_breaking_video_news_stay_up_to_date_with_the_latest_visual_stories_id = td_demo_content::add_post( array(
	'title' => 'Breaking Video News: Stay Up-to-Date with the Latest Visual Stories',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_videos_id,),
));

$post_td_post_curtain_call_behind_the_scenes_of_entertainments_greatest_moments_id = td_demo_content::add_post( array(
	'title' => 'Curtain Call: Behind the Scenes of Entertainment\'s Greatest Moments',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_entertainment_id,),
));

$post_td_post_the_entertainment_enigma_unraveling_the_mysteries_of_showbiz_id = td_demo_content::add_post( array(
	'title' => 'The Entertainment Enigma: Unraveling the Mysteries of Showbiz\"',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_entertainment_id,),
));

$post_td_post_spotlight_stories_captivating_tales_from_the_entertainment_industry_id = td_demo_content::add_post( array(
	'title' => 'Spotlight Stories: Captivating Tales from the Entertainment Industry',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'tds_locker' => '5',
	'categories_id_array' => array($cat_entertainment_id,),
));

$post_td_post_lights_camera_action_exploring_the_glamorous_world_of_entertainmen_id = td_demo_content::add_post( array(
	'title' => 'Lights, Camera, Action: Exploring the Glamorous World of Entertainmen',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_entertainment_id,),
));

$post_td_post_the_entertainment_escape_unveiling_the_magic_of_movies_music_and_more_id = td_demo_content::add_post( array(
	'title' => 'The Entertainment Escape: Unveiling the Magic of Movies, Music, and More',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_entertainment_id,),
));

$post_td_post_entertainment_chronicles_stories_from_the_world_of_fun_and_fantasy_id = td_demo_content::add_post( array(
	'title' => 'Entertainment Chronicles: Stories from the World of Fun and Fantasy',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_entertainment_id,),
));

$post_td_post_ethics_in_the_age_of_ai_navigating_the_moral_challenges_of_intelligent_machines_id = td_demo_content::add_post( array(
	'title' => 'Ethics in the Age of AI: Navigating the Moral Challenges of Intelligent Machines',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_ai_id,),
));

$post_td_post_ai_disrupted_reshaping_industries_with_intelligent_technology_id = td_demo_content::add_post( array(
	'title' => 'AI Disrupted: Reshaping Industries with Intelligent Technology',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_ai_id,),
));

$post_td_post_the_ai_equation_solving_tomorrows_problems_with_machine_learning_id = td_demo_content::add_post( array(
	'title' => 'The AI Equation: Solving Tomorrow\'s Problems with Machine Learning',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_ai_id,),
));

$post_td_post_intelligent_horizons_navigating_the_age_of_artificial_intelligence_id = td_demo_content::add_post( array(
	'title' => 'Intelligent Horizons: Navigating the Age of Artificial Intelligence',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_ai_id,),
));

$post_td_post_algorithmic_minds_understanding_the_world_of_ai_id = td_demo_content::add_post( array(
	'title' => 'Algorithmic Minds: Understanding the World of AI',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_ai_id,),
));

$post_td_post_cognitive_machines_the_rise_of_artificial_intelligence_id = td_demo_content::add_post( array(
	'title' => 'Cognitive Machines: The Rise of Artificial Intelligence',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_ai_id,),
));

$post_td_post_the_digital_renaissance_technology_in_the_modern_age_id = td_demo_content::add_post( array(
	'title' => 'The Digital Renaissance: Technology in the Modern Age',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_tech_id,),
));

$post_td_post_techgenius_unlocking_the_secrets_of_technological_brilliance_id = td_demo_content::add_post( array(
	'title' => 'TechGenius: Unlocking the Secrets of Technological Brilliance',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_tech_id,),
));

$post_td_post_techtalk_conversations_shaping_the_future_id = td_demo_content::add_post( array(
	'title' => 'TechTalk: Conversations Shaping the Future',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'tds_locker' => '5',
	'categories_id_array' => array($cat_tech_id,),
));

$post_td_post_innovation_unleashed_the_tech_frontier_id = td_demo_content::add_post( array(
	'title' => 'Innovation Unleashed: The Tech Frontier',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_tech_id,),
));

$post_td_post_codecraft_mastering_the_art_of_technology_id = td_demo_content::add_post( array(
	'title' => 'CodeCraft: Mastering the Art of Technology',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_tech_id,),
));

$post_td_post_techxplore_unraveling_the_future_of_technology_id = td_demo_content::add_post( array(
	'title' => 'TechXplore: Unraveling the Future of Technology',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_tech_id,),
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
	MENUS
*/


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_category( array(
	'title' => 'Tech',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'category_id' => $cat_tech_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_category( array(
	'title' => 'AI',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'category_id' => $cat_ai_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_category( array(
	'title' => 'Entertainment',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'category_id' => $cat_entertainment_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_category( array(
	'title' => 'Videos',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'category_id' => $cat_videos_id,
	'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_category( array(
	'title' => 'Social Good',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'category_id' => $cat_social_good_id,
	'parent_id' => ''
));

$menu_item_5_id = td_demo_menus::add_category( array(
	'title' => 'Lifestyle',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'category_id' => $cat_lifestyle_id,
	'parent_id' => ''
));



/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_category( array(
	'title' => 'Tech',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_tech_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_category( array(
	'title' => 'AI',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_ai_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_category( array(
	'title' => 'Entertainment',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_entertainment_id,
	'parent_id' => ''
));



/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Tech',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_tech_id,
	'parent_id' => ''
), true );

$menu_item_1_id = td_demo_menus::add_mega_menu( array(
	'title' => 'AI',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_ai_id,
	'parent_id' => ''
), true );

$menu_item_2_id = td_demo_menus::add_category( array(
	'title' => 'Entertainment',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_entertainment_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_category( array(
	'title' => 'Videos',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_videos_id,
	'parent_id' => $menu_item_2_id
));

$menu_item_4_id = td_demo_menus::add_category( array(
	'title' => 'Social Good',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_social_good_id,
	'parent_id' => $menu_item_3_id
));

$menu_item_5_id = td_demo_menus::add_category( array(
	'title' => 'Lifestyle',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_lifestyle_id,
	'parent_id' => $menu_item_3_id
));


/*  ---------------------------------------------------------------------------- 
	CLOUD TEMPLATES
*/
$template_tag_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Tag Template',
	'file' => 'tag_cloud_template.txt',
	'template_type' => 'tag',
));

td_demo_misc::update_global_tag_template( 'tdb_template_' . $template_tag_template_id );


$template_search_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Search Template',
	'file' => 'search_cloud_template.txt',
	'template_type' => 'search',
));

td_demo_misc::update_global_search_template( 'tdb_template_' . $template_search_template_id );


$template_date_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Date Template',
	'file' => 'date_cloud_template.txt',
	'template_type' => 'date',
));

td_demo_misc::update_global_date_template( 'tdb_template_' . $template_date_template_id );


$template_author_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Author Template',
	'file' => 'author_cloud_template.txt',
	'template_type' => 'author',
));

td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_id );


$template_category_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Category Template',
	'file' => 'cat_cloud_template.txt',
	'template_type' => 'category',
));

td_demo_misc::update_global_category_template( 'tdb_template_' . $template_category_template_id );


$template_404_template_id = td_demo_content::add_cloud_template( array(
	'title' => '404 Template',
	'file' => '404_cloud_template.txt',
	'template_type' => '404',
));

td_demo_misc::update_global_404_template( 'tdb_template_' . $template_404_template_id );


$template_single_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Single Template',
	'file' => 'post_cloud_template.txt',
	'template_type' => 'single',
));

td_util::update_option( 'td_default_site_post_template', 'tdb_template_' . $template_single_template_id );


$template_footer_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Footer Template',
	'file' => 'footer_template_cloud_template.txt',
	'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_template_id );


$template_header_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Header Template',
	'file' => 'header_template_cloud_template.txt',
	'template_type' => 'header',
));

td_demo_misc::update_global_header_template( 'tdb_template_' . $template_header_template_id );


update_post_meta( $template_header_template_id, 'header_mobile_menu_id', $menu_td_demo_header_menu_id );



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
td_demo_content::update_meta( $template_footer_template_id, 'tdc_footer_template_id', $template_footer_template_id );

td_demo_content::update_meta( $template_header_template_id, 'tdc_header_template_id', $template_header_template_id );

// pages metas