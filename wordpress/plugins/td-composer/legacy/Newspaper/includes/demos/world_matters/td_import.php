<?php

/*  ----------------------------------------------------------------------------
	MENUS
*/
$menu_td_demo_custom_menu_id = td_demo_menus::create_menu('td-demo-custom-menu', '');
$menu_td_demo_footer_menu_id = td_demo_menus::create_menu('td-demo-footer-menu', '');
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

$plan_monthly_plan_id = td_demo_subscription::add_plan( array(
		'name' => 'Monthly Plan',
		'price' => '10',
		'months_in_cycle' => '1',
		'trial_days' => '0',
		'is_free' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"2964a7fa5998f6b";}',
	)
);

$plan_free_plan_id = td_demo_subscription::add_plan( array(
		'name' => 'Free Plan',
		'price' => '',
		'months_in_cycle' => '',
		'trial_days' => '0',
		'is_free' => '1',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"9264a7fa599908a";}',
	)
);

$plan_yearly_plan_id = td_demo_subscription::add_plan( array(
		'name' => 'Yearly Plan',
		'price' => '100',
		'months_in_cycle' => '12',
		'trial_days' => '0',
		'is_free' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"2064a7fa59990fa";}',
	)
);

$page_payment_page_id_id = td_demo_content::add_page( array(
	'title' => 'Checkout - world_matters',
	'file' => 'tds_checkout.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'payment_page_id',
	'value' => $page_payment_page_id_id,
	)
);

$page_my_account_page_id_id = td_demo_content::add_page( array(
	'title' => 'My Account - world_matters',
	'file' => 'tds_my_account.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'my_account_page_id',
	'value' => $page_my_account_page_id_id,
	)
);

$page_create_account_page_id_id = td_demo_content::add_page( array(
	'title' => 'Login/Register - world_matters',
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
$cat_all_id = td_demo_category::add_category(array(
	'category_name' => 'All',
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

$cat_art_id = td_demo_category::add_category(array(
	'category_name' => 'Art',
	'parent_id' => $cat_all_id,
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
	'parent_id' => $cat_all_id,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_culture_id = td_demo_category::add_category(array(
	'category_name' => 'Culture',
	'parent_id' => $cat_all_id,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_eco_id = td_demo_category::add_category(array(
	'category_name' => 'Eco',
	'parent_id' => $cat_all_id,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_fashion_id = td_demo_category::add_category(array(
	'category_name' => 'Fashion',
	'parent_id' => $cat_all_id,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_insight_id = td_demo_category::add_category(array(
	'category_name' => 'Insight',
	'parent_id' => $cat_all_id,
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
	'parent_id' => $cat_all_id,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_opinions_id = td_demo_category::add_category(array(
	'category_name' => 'Opinions',
	'parent_id' => $cat_all_id,
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
	'parent_id' => $cat_all_id,
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
$template_module_template_wm_box_id = td_demo_content::add_cloud_template( array(
	'title' => 'WM – Box - Module Template',
	'file' => 'module_template_wm_box_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '472',
));

$template_module_template_wm_standard_id = td_demo_content::add_cloud_template( array(
	'title' => 'WM – Standard - Module Template',
	'file' => 'module_template_wm_standard_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '379',
));


/*  ---------------------------------------------------------------------------- 
	ATTACHMENTS
*/

/*  ---------------------------------------------------------------------------- 
	PAGES
*/
$page_search_modal_menu_id = td_demo_content::add_page( array(
	'title' => 'Search Modal Menu',
	'file' => 'search_modal_menu.txt',
	'demo_unique_id' => '3164a7fa59d7268',
));

$page_tds_switching_plans_wizard_id = td_demo_content::add_page( array(
	'title' => 'Switching plans wizard',
	'file' => 'tds_switching_plans_wizard.txt',
	'demo_unique_id' => '5364a7fa59d7a12',
));

$page_homepage_id = td_demo_content::add_page( array(
	'title' => 'Homepage',
	'file' => 'homepage.txt',
	'homepage' => true,
	'demo_unique_id' => '8064a7fa59d8c71',
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
				'tds_title' => 'Exclusive Content',
				'tds_message' => 'Join our community of passionate readers and gain access to an entire library of exclusive content. Subscribe now to unlock articles, videos, and resources that will fuel your curiosity and expand your knowledge.',
				'tds_submit_btn_text' => 'Subscribe',
				'tds_pp_msg' => 'I consent to processing of my data according to <a href=\"#\">Terms of Use</a> & <a href=\"#\">Privacy Policy</a>.',
				'tds_locker_cf_1_name' => 'Custom field 1',
				'tds_locker_cf_2_name' => 'Custom field 2',
				'tds_locker_cf_3_name' => 'Custom field 3',
			),
	'tds_payable' => 'paid_subscription',
	'tds_paid_subs_page_id' => $page_tds_switching_plans_wizard_id,
	'tds_paid_subs_plan_ids' => [$plan_monthly_plan_id,$plan_free_plan_id,$plan_yearly_plan_id],
			'tds_locker_styles' => array(
				'tds_bg_color' => '#000000',
				'tds_title_color' => '#ffffff',
				'tds_message_color' => '#ffffff',
				'tds_submit_btn_text_color' => '#ffffff',
				'tds_submit_btn_text_color_h' => '#ffffff',
				'tds_submit_btn_bg_color' => '#ff5f45',
				'tds_submit_btn_bg_color_h' => '#ea3c1f',
				'tds_after_btn_text_color' => '#ffffff',
				'tds_pp_checked_color' => '#ff5f45',
				'tds_pp_check_bg' => '#ffffff',
				'tds_pp_check_bg_f' => '#ffffff',
				'tds_pp_check_border_color' => '#ffffff',
				'tds_pp_check_border_color_f' => '#ffffff',
				'tds_pp_msg_color' => '#ffffff',
				'tds_pp_msg_links_color' => '#ff5f45',
				'tds_pp_msg_links_color_h' => '#ea3c1f',
				'tds_general_font_family' => 'sans-serif_global',
				'tds_title_font_family' => '831',
				'tds_title_font_size' => '30',
				'tds_title_font_line_height' => '1.2',
				'tds_title_font_weight' => '500',
				'tds_message_font_family' => 'sans-serif_global',
				'tds_message_font_size' => '16',
				'tds_message_font_line_height' => '1.2',
				'tds_message_font_weight' => '500',
				'tds_submit_btn_text_font_family' => 'sans-serif_global',
				'tds_submit_btn_text_font_size' => '14',
				'tds_submit_btn_text_font_line_height' => '1.2',
				'tds_submit_btn_text_font_weight' => '500',
				'tds_submit_btn_text_font_transform' => 'uppercase',
				'tds_submit_btn_text_font_spacing' => '1',
				'tds_after_btn_text_font_family' => 'sans-serif_global',
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

td_util::update_option('tds_demo_options', 'a:1:{s:5:"plans";a:3:{i:0;a:2:{s:9:"unique_id";s:15:"2964a7fa5998f6b";s:4:"name";s:12:"Monthly Plan";}i:1;a:2:{s:9:"unique_id";s:15:"9264a7fa599908a";s:4:"name";s:9:"Free Plan";}i:2;a:2:{s:9:"unique_id";s:15:"2064a7fa59990fa";s:4:"name";s:11:"Yearly Plan";}}}');


/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTION - end phase 2
*/

/*  ---------------------------------------------------------------------------- 
	POSTS
*/
$post_td_post_global_spotlight_uncovering_the_latest_developments_and_trends_shaping_world_news_id = td_demo_content::add_post( array(
	'title' => 'Global Spotlight: Uncovering the Latest Developments and Trends Shaping World News',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'tds_locker' => '279',
	'categories_id_array' => array($cat_all_id,$cat_insight_id,),
));

$post_td_post_breaking_borders_exploring_the_impact_of_international_relations_on_global_affairs_id = td_demo_content::add_post( array(
	'title' => 'Breaking Borders: Exploring the Impact of International Relations on Global Affairs',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_all_id,$cat_insight_id,),
));

$post_td_post_diplomatic_dialogues_decoding_complex_foreign_relations_and_diplomacy_strategies_id = td_demo_content::add_post( array(
	'title' => 'Diplomatic Dialogues: Decoding Complex Foreign Relations and Diplomacy Strategies',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_all_id,$cat_insight_id,),
));

$post_td_post_emerging_economies_tracking_the_growth_and_influence_of_rising_global_powers_id = td_demo_content::add_post( array(
	'title' => 'Emerging Economies: Tracking the Growth and Influence of Rising Global Powers',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_all_id,$cat_insight_id,),
));

$post_td_post_geopolitical_shifts_examining_power_dynamics_and_changing_alliances_in_todays_world_id = td_demo_content::add_post( array(
	'title' => 'Geopolitical Shifts: Examining Power Dynamics and Changing Alliances in Today\'s World',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_all_id,$cat_insight_id,),
));

$post_td_post_masters_of_modernism_celebrating_influential_artists_and_designers_of_the_20th_century_id = td_demo_content::add_post( array(
	'title' => 'Masters of Modernism: Celebrating Influential Artists and Designers of the 20th Century',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_all_id,$cat_art_id,),
));

$post_td_post_sculpting_spaces_the_fusion_of_architecture_and_art_in_contemporary_design_id = td_demo_content::add_post( array(
	'title' => 'Sculpting Spaces: The Fusion of Architecture and Art in Contemporary Design',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_all_id,$cat_art_id,),
));

$post_td_post_reviving_the_past_exploring_the_resurgence_of_traditional_art_and_craftsmanship_id = td_demo_content::add_post( array(
	'title' => 'Reviving the Past: Exploring the Resurgence of Traditional Art and Craftsmanship',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_all_id,$cat_art_id,),
));

$post_td_post_the_language_of_colors_understanding_the_psychology_and_symbolism_in_design_id = td_demo_content::add_post( array(
	'title' => 'The Language of Colors: Understanding the Psychology and Symbolism in Design',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_all_id,$cat_art_id,),
));

$post_td_post_from_concept_to_creation_unveiling_the_creative_process_behind_remarkable_art_and_design_id = td_demo_content::add_post( array(
	'title' => 'From Concept to Creation: Unveiling the Creative Process behind Remarkable Art and Design',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_all_id,$cat_art_id,),
));

$post_td_post_the_art_of_self_care_prioritizing_your_well_being_in_a_busy_lifestyle_id = td_demo_content::add_post( array(
	'title' => 'The Art of Self-Care: Prioritizing Your Well-being in a Busy Lifestyle',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_all_id,$cat_lifestyle_id,),
));

$post_td_post_finding_inner_harmony_exploring_mindfulness_and_meditation_for_a_fulfilling_lifestyle_id = td_demo_content::add_post( array(
	'title' => 'Finding Inner Harmony: Exploring Mindfulness and Meditation for a Fulfilling Lifestyle',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_all_id,$cat_lifestyle_id,),
));

$post_td_post_healthy_habits_for_a_balanced_life_nurturing_your_mind_body_and_spirit_id = td_demo_content::add_post( array(
	'title' => 'Healthy Habits for a Balanced Life: Nurturing Your Mind, Body, and Spirit',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_all_id,$cat_lifestyle_id,),
));

$post_td_post_the_power_of_positive_thinking_cultivating_optimism_and_resilience_in_everyday_life_id = td_demo_content::add_post( array(
	'title' => 'The Power of Positive Thinking: Cultivating Optimism and Resilience in Everyday Life',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_all_id,$cat_lifestyle_id,),
));

$post_td_post_embracing_work_life_balance_strategies_for_thriving_in_both_personal_and_professional_spheres_id = td_demo_content::add_post( array(
	'title' => 'Embracing Work-Life Balance: Strategies for Thriving in Both Personal and Professional Spheres',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_all_id,$cat_lifestyle_id,),
));

$post_td_post_unlocking_innovation_fostering_creativity_and_driving_business_growth_id = td_demo_content::add_post( array(
	'title' => 'Unlocking Innovation: Fostering Creativity and Driving Business Growth',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_all_id,$cat_business_id,),
));

$post_td_post_the_entrepreneurial_journey_from_idea_to_launching_a_successful_business_id = td_demo_content::add_post( array(
	'title' => 'The Entrepreneurial Journey: From Idea to Launching a Successful Business',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_all_id,$cat_business_id,),
));

$post_td_post_creating_a_winning_brand_building_a_strong_identity_and_connecting_with_customers_id = td_demo_content::add_post( array(
	'title' => 'Creating a Winning Brand: Building a Strong Identity and Connecting with Customers',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_all_id,$cat_business_id,),
));

$post_td_post_the_art_of_negotiation_strategies_for_achieving_win_win_business_deals_id = td_demo_content::add_post( array(
	'title' => 'The Art of Negotiation: Strategies for Achieving Win-Win Business Deals',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_all_id,$cat_business_id,),
));

$post_td_post_building_a_resilient_business_strategies_for_thriving_in_times_of_uncertainty_id = td_demo_content::add_post( array(
	'title' => 'Building a Resilient Business: Strategies for Thriving in Times of Uncertainty',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_all_id,$cat_business_id,),
));

$post_td_post_the_power_of_recycling_transforming_waste_into_valuable_resources_id = td_demo_content::add_post( array(
	'title' => 'The Power of Recycling: Transforming Waste into Valuable Resources',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_all_id,$cat_eco_id,),
));

$post_td_post_natures_guardians_preserving_biodiversity_and_ecosystems_for_future_generations_id = td_demo_content::add_post( array(
	'title' => 'Nature\'s Guardians: Preserving Biodiversity and Ecosystems for Future Generations',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_all_id,$cat_eco_id,),
));

$post_td_post_climate_action_now_strategies_for_mitigating_the_effects_of_global_warming_id = td_demo_content::add_post( array(
	'title' => 'Climate Action Now: Strategies for Mitigating the Effects of Global Warming',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_all_id,$cat_eco_id,),
));

$post_td_post_sustainable_living_made_easy_practical_tips_for_an_eco_friendly_lifestyle_id = td_demo_content::add_post( array(
	'title' => 'Sustainable Living Made Easy: Practical Tips for an Eco-Friendly Lifestyle',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_all_id,$cat_eco_id,),
));

$post_td_post_the_green_revolution_exploring_innovations_in_renewable_energy_id = td_demo_content::add_post( array(
	'title' => 'The Green Revolution: Exploring Innovations in Renewable Energy',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_all_id,$cat_eco_id,),
));

$post_td_post_preserving_heritage_the_importance_of_cultural_conservation_in_a_globalized_world_id = td_demo_content::add_post( array(
	'title' => 'Preserving Heritage: The Importance of Cultural Conservation in a Globalized World',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_all_id,$cat_culture_id,),
));

$post_td_post_the_power_of_art_how_creativity_shapes_and_reflects_culture_id = td_demo_content::add_post( array(
	'title' => 'The Power of Art: How Creativity Shapes and Reflects Culture',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_all_id,$cat_culture_id,),
));

$post_td_post_tradition_meets_modernity_navigating_the_evolving_cultural_landscape_id = td_demo_content::add_post( array(
	'title' => 'Tradition Meets Modernity: Navigating the Evolving Cultural Landscape',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_all_id,$cat_culture_id,),
));

$post_td_post_culinary_journeys_exploring_the_flavors_and_stories_behind_global_cuisines_id = td_demo_content::add_post( array(
	'title' => 'Culinary Journeys: Exploring the Flavors and Stories Behind Global Cuisines',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_all_id,$cat_culture_id,),
));

$post_td_post_cultural_exchange_through_travel_broadening_perspectives_and_bridging_differences_id = td_demo_content::add_post( array(
	'title' => 'Cultural Exchange Through Travel: Broadening Perspectives and Bridging Differences',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_all_id,$cat_culture_id,),
));

$post_td_post_exploring_the_world_of_quantum_computing_unlocking_unprecedented_computing_power_id = td_demo_content::add_post( array(
	'title' => 'Exploring the World of Quantum Computing: Unlocking Unprecedented Computing Power',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_all_id,$cat_tech_id,),
));

$post_td_post_mastering_the_digital_age_essential_tech_skills_for_the_modern_professional_id = td_demo_content::add_post( array(
	'title' => 'Mastering the Digital Age: Essential Tech Skills for the Modern Professional',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_all_id,$cat_tech_id,),
));

$post_td_post_cybersecurity_101_protecting_your_digital_life_in_an_increasingly_connected_world_id = td_demo_content::add_post( array(
	'title' => 'Cybersecurity 101: Protecting Your Digital Life in an Increasingly Connected World',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_all_id,$cat_tech_id,),
));

$post_td_post_revolutionizing_healthcare_the_impact_of_technology_on_medical_advancements_id = td_demo_content::add_post( array(
	'title' => 'Revolutionizing Healthcare: The Impact of Technology on Medical Advancements',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_all_id,$cat_tech_id,),
));

$post_td_post_tech_gadgets_of_tomorrow_a_glimpse_into_the_exciting_world_of_wearable_tech_id = td_demo_content::add_post( array(
	'title' => 'Tech Gadgets of Tomorrow: A Glimpse into the Exciting World of Wearable Tech',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_all_id,$cat_tech_id,),
));

$post_td_post_fashionable_and_functional_the_rise_of_athleisure_wear_for_active_lifestyles_id = td_demo_content::add_post( array(
	'title' => 'Fashionable and Functional: The Rise of Athleisure Wear for Active Lifestyles',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_all_id,$cat_fashion_id,),
));

$post_td_post_effortless_elegance_mastering_the_art_of_minimalist_fashion_for_a_sophisticated_look_id = td_demo_content::add_post( array(
	'title' => 'Effortless Elegance: Mastering the Art of Minimalist Fashion for a Sophisticated Look',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_all_id,$cat_fashion_id,),
));

$post_td_post_revamp_your_style_unleashing_the_power_of_vintage_inspired_fashion_trends_id = td_demo_content::add_post( array(
	'title' => 'Revamp Your Style: Unleashing the Power of Vintage-Inspired Fashion Trends',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_all_id,$cat_fashion_id,),
));

$post_td_post_sustainable_chic_exploring_eco_friendly_fashion_choices_for_a_greener_wardrobe_id = td_demo_content::add_post( array(
	'title' => 'Sustainable Chic: Exploring Eco-Friendly Fashion Choices for a Greener Wardrobe',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_all_id,$cat_fashion_id,),
));

$post_td_post_fashion_forward_unlocking_the_secrets_to_express_your_personal_style_with_confidence_id = td_demo_content::add_post( array(
	'title' => 'Fashion Forward: Unlocking the Secrets to Express Your Personal Style with Confidence',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_all_id,$cat_fashion_id,),
));

$post_td_post_the_power_of_art_how_creativity_transforms_perspectives_and_ignites_change_id = td_demo_content::add_post( array(
	'title' => 'The Power of Art: How Creativity Transforms Perspectives and Ignites Change',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_all_id,$cat_art_id,$cat_business_id,$cat_culture_id,$cat_eco_id,$cat_fashion_id,$cat_insight_id,$cat_lifestyle_id,$cat_opinions_id,$cat_tech_id,),
));

$post_td_post_navigating_the_digital_age_balancing_innovation_with_ethical_concerns_id = td_demo_content::add_post( array(
	'title' => 'Navigating the Digital Age: Balancing Innovation with Ethical Concerns',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'tds_locker' => '279',
	'categories_id_array' => array($cat_all_id,$cat_art_id,$cat_business_id,$cat_culture_id,$cat_eco_id,$cat_fashion_id,$cat_insight_id,$cat_lifestyle_id,$cat_opinions_id,$cat_tech_id,),
));

$post_td_post_climate_crisis_time_for_bold_action_and_global_cooperation_id = td_demo_content::add_post( array(
	'title' => 'Climate Crisis: Time for Bold Action and Global Cooperation',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'tds_locker' => '279',
	'categories_id_array' => array($cat_all_id,$cat_art_id,$cat_business_id,$cat_culture_id,$cat_eco_id,$cat_fashion_id,$cat_insight_id,$cat_lifestyle_id,$cat_opinions_id,$cat_tech_id,),
));

$post_td_post_reimagining_education_fostering_innovation_and_empowering_future_generations_id = td_demo_content::add_post( array(
	'title' => 'Reimagining Education: Fostering Innovation and Empowering Future Generations',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'tds_locker' => '279',
	'categories_id_array' => array($cat_all_id,$cat_art_id,$cat_business_id,$cat_culture_id,$cat_eco_id,$cat_fashion_id,$cat_insight_id,$cat_lifestyle_id,$cat_opinions_id,$cat_tech_id,),
));

$post_td_post_the_future_of_work_embracing_automation_and_reskilling_for_a_sustainable_workforce_id = td_demo_content::add_post( array(
	'title' => 'The Future of Work: Embracing Automation and Reskilling for a Sustainable Workforce',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_all_id,$cat_art_id,$cat_business_id,$cat_culture_id,$cat_eco_id,$cat_fashion_id,$cat_insight_id,$cat_lifestyle_id,$cat_opinions_id,$cat_tech_id,),
));

$post_td_post_redefining_success_challenging_conventional_metrics_in_business_and_life_id = td_demo_content::add_post( array(
	'title' => 'Redefining Success: Challenging Conventional Metrics in Business and Life',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_all_id,$cat_opinions_id,),
));

$post_td_post_crisis_of_trust_restoring_confidence_in_institutions_and_leaders_id = td_demo_content::add_post( array(
	'title' => 'Crisis of Trust: Restoring Confidence in Institutions and Leaders',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_all_id,$cat_opinions_id,),
));

$post_td_post_art_as_activism_harnessing_creativity_to_drive_social_and_political_change_id = td_demo_content::add_post( array(
	'title' => 'Art as Activism: Harnessing Creativity to Drive Social and Political Change',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_all_id,$cat_opinions_id,),
));

$post_td_post_revitalizing_urban_spaces_designing_cities_for_sustainable_living_and_community_well_being_id = td_demo_content::add_post( array(
	'title' => 'Revitalizing Urban Spaces: Designing Cities for Sustainable Living and Community Well-being',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_all_id,$cat_opinions_id,),
));

$post_td_post_beyond_profit_embracing_corporate_social_responsibility_for_a_better_world_id = td_demo_content::add_post( array(
	'title' => 'Beyond Profit: Embracing Corporate Social Responsibility for a Better World',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_all_id,$cat_opinions_id,),
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
	'title' => 'Fashion',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'category_id' => $cat_fashion_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_category( array(
	'title' => 'Insight',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'category_id' => $cat_insight_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_category( array(
	'title' => 'Opinions',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'category_id' => $cat_opinions_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_category( array(
	'title' => 'Tech',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'category_id' => $cat_tech_id,
	'parent_id' => ''
));



/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_link( array(
	'title' => 'Terms and Conditions',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_link( array(
	'title' => 'Privacy Policy',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_link( array(
	'title' => 'FAQ',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'url' => '#',
	'parent_id' => ''
));



/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_mega_menu( array(
	'title' => 'All',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_all_id,
	'parent_id' => ''
), true );

$menu_item_1_id = td_demo_menus::add_category( array(
	'title' => 'Art',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_art_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_category( array(
	'title' => 'Business',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_business_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_category( array(
	'title' => 'Culture',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_culture_id,
	'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_category( array(
	'title' => 'Eco',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_eco_id,
	'parent_id' => ''
));

$menu_item_5_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Lifestyle',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_lifestyle_id,
	'parent_id' => ''
), true );



/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_category( array(
	'title' => 'All',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_all_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_category( array(
	'title' => 'Art',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_art_id,
	'parent_id' => $menu_item_0_id
));

$menu_item_2_id = td_demo_menus::add_category( array(
	'title' => 'Business',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_business_id,
	'parent_id' => $menu_item_0_id
));

$menu_item_3_id = td_demo_menus::add_category( array(
	'title' => 'Culture',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_culture_id,
	'parent_id' => $menu_item_0_id
));

$menu_item_4_id = td_demo_menus::add_category( array(
	'title' => 'Eco',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_eco_id,
	'parent_id' => $menu_item_0_id
));

$menu_item_5_id = td_demo_menus::add_category( array(
	'title' => 'Fashion',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_fashion_id,
	'parent_id' => $menu_item_0_id
));

$menu_item_6_id = td_demo_menus::add_category( array(
	'title' => 'Insight',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_insight_id,
	'parent_id' => ''
));

$menu_item_7_id = td_demo_menus::add_category( array(
	'title' => 'Lifestyle',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_lifestyle_id,
	'parent_id' => ''
));

$menu_item_8_id = td_demo_menus::add_category( array(
	'title' => 'Opinions',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_opinions_id,
	'parent_id' => ''
));

$menu_item_9_id = td_demo_menus::add_category( array(
	'title' => 'Tech',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'category_id' => $cat_tech_id,
	'parent_id' => ''
));

$menu_item_10_id = td_demo_menus::add_page(array(
	'title' => 'Subscribe',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'page_id' => $page_tds_switching_plans_wizard_id,
	'parent_id' => ''
));


/*  ---------------------------------------------------------------------------- 
	CLOUD TEMPLATES
*/
$template_404_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'WM - 404 Template',
	'file' => '404_cloud_template.txt',
	'template_type' => '404',
));

td_demo_misc::update_global_404_template( 'tdb_template_' . $template_404_template_id );


$template_author_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'WM - Author Template',
	'file' => 'author_cloud_template.txt',
	'template_type' => 'author',
));

td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_id );


$template_search_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'WM - Search Template',
	'file' => 'search_cloud_template.txt',
	'template_type' => 'search',
));

td_demo_misc::update_global_search_template( 'tdb_template_' . $template_search_template_id );


$template_date_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'WM - Date Template',
	'file' => 'date_cloud_template.txt',
	'template_type' => 'date',
));

td_demo_misc::update_global_date_template( 'tdb_template_' . $template_date_template_id );


$template_tag_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'WM - Tag Template',
	'file' => 'tag_cloud_template.txt',
	'template_type' => 'tag',
));

td_demo_misc::update_global_tag_template( 'tdb_template_' . $template_tag_template_id );


$template_single_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'WM - Single Template',
	'file' => 'post_cloud_template.txt',
	'template_type' => 'single',
));

td_util::update_option( 'td_default_site_post_template', 'tdb_template_' . $template_single_template_id );


$template_category_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'WM - Category Template',
	'file' => 'cat_cloud_template.txt',
	'template_type' => 'category',
));

td_demo_misc::update_global_category_template( 'tdb_template_' . $template_category_template_id );


$template_footer_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'WM - Footer Template',
	'file' => 'footer_template_cloud_template.txt',
	'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_template_id );


$template_header_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'WM - Header Template',
	'file' => 'header_template_cloud_template.txt',
	'template_type' => 'header',
));

td_demo_misc::update_global_header_template( 'tdb_template_' . $template_header_template_id );



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
td_demo_content::update_meta( $page_homepage_id, 'tdc_header_template_id', $template_header_template_id );

td_demo_content::update_meta( $page_homepage_id, 'tdc_footer_template_id', $template_footer_template_id );
