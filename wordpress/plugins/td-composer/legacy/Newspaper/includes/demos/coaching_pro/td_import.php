<?php



/*  ----------------------------------------------------------------------------
	EXTERNAL PLUGINS DATA IMPORT
*/
/* -- ACF -- */
// Field groups
$field_group_course_fields = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/coaching_pro/data/acf/group_646b6bdd7cc7f.json' );

// Post types
$post_type_courses = td_demo_data::acf_import_post_type( 'https://cloud.tagdiv.com/demos/Newspaper/coaching_pro/data/acf/post_type_646b67dd79321.json' );

// Taxonomies
$taxonomy_domains = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/coaching_pro/data/acf/taxonomy_646b686c902bd.json' );
$taxonomy_prices = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/coaching_pro/data/acf/taxonomy_646f1505c1f36.json' );



/*  ----------------------------------------------------------------------------
	MENUS
*/

$menu_td_demo_header_menu_id = td_demo_menus::create_menu('td-demo-header-menu', '');
$menu_td_demo_custom_menu_id = td_demo_menus::create_menu('td-demo-custom-menu', '');
$menu_td_demo_top_menu_id = td_demo_menus::create_menu('td-demo-top-menu', '');

/*  ----------------------------------------------------------------------------
	 CLOUD TEMPLATES(MODULES)
*/
$template_module_template_2_coaching_pro_id = td_demo_content::add_cloud_template( array(
    'title' => 'Module Template 2 - Coaching PRO',
    'file' => 'module_template_2_coaching_pro_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '741',
));

$template_module_template_1_coaching_pro_id = td_demo_content::add_cloud_template( array(
    'title' => 'Module Template 1 - Coaching PRO',
    'file' => 'module_template_1_coaching_pro_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '434',
));


/*  ----------------------------------------------------------------------------
	PAGES
*/
$page_megamenu_courses_id = td_demo_content::add_page( array(
    'title' => 'Megamenu Courses',
    'file' => 'megamenu_courses.txt',
    'demo_unique_id' => '2864be215966afd',
));

$page_mobile_menu_modal_id = td_demo_content::add_page( array(
    'title' => 'Mobile Menu Modal',
    'file' => 'mobile_menu_modal.txt',
    'demo_unique_id' => '364be215966f49',
));

$page_courses_id = td_demo_content::add_page( array(
    'title' => 'Courses',
    'file' => 'courses.txt',
    'demo_unique_id' => '5164be215967472',
));

$page_contact_id = td_demo_content::add_page( array(
    'title' => 'Contact',
    'file' => 'contact.txt',
    'demo_unique_id' => '9964be2159678e9',
));

$page_about_id = td_demo_content::add_page( array(
    'title' => 'About',
    'file' => 'about.txt',
    'demo_unique_id' => '7764be215967e82',
));

$page_home_id = td_demo_content::add_page( array(
    'title' => 'Home',
    'file' => 'home.txt',
    'homepage' => true,
    'demo_unique_id' => '5664be215968656',
));

$page_pricing_id = td_demo_content::add_page( array(
    'title' => 'Pricing',
    'file' => 'pricing.txt',
    'demo_unique_id' => '6764be215968b0d',
));


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

$plan_yearly_plan_id = td_demo_subscription::add_plan( array(
		'name' => 'Yearly Plan',
		'price' => '99',
        'interval' => 'year',
        'interval_count' => 1,
        'trial_days' => '0',
        'is_free' => '0',
        'is_unlimited' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"7264be215944925";}',
	)
);

$plan_monthly_plan_id = td_demo_subscription::add_plan( array(
		'name' => 'Monthly Plan',
		'price' => '9',
        'interval' => 'month',
        'interval_count' => 1,
        'trial_days' => '0',
        'is_free' => '0',
        'is_unlimited' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"5564be21594496f";}',
	)
);

$page_payment_page_id_id = td_demo_content::add_page( array(
	'title' => 'Checkout - coaching_pro',
	'file' => 'tds_checkout.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'payment_page_id',
	'value' => $page_payment_page_id_id,
	)
);

$page_my_account_page_id_id = td_demo_content::add_page( array(
	'title' => 'My Account - coaching_pro',
	'file' => 'tds_my_account.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'my_account_page_id',
	'value' => $page_my_account_page_id_id,
	)
);

$page_create_account_page_id_id = td_demo_content::add_page( array(
	'title' => 'Login/Register - coaching_pro',
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

$cat_goals_id = td_demo_category::add_category(array(
	'category_name' => 'Goals',
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

$cat_growth_id = td_demo_category::add_category(array(
	'category_name' => 'Growth',
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

$cat_mindset_id = td_demo_category::add_category(array(
	'category_name' => 'Mindset',
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

$cat_motivation_id = td_demo_category::add_category(array(
	'category_name' => 'Motivation',
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
	ATTACHMENTS
*/


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
				'tds_title' => 'This Course is Only for Subscribers',
				'tds_message' => 'Please subscribe to unlock this course',
				'tds_submit_btn_text' => 'Unlock This Course',
				'tds_pp_msg' => 'I consent to processing of my data according to <a href=\"#\">Terms of Use</a> & <a href=\"#\">Privacy Policy</a>',
				'tds_locker_cf_1_name' => 'Custom field 1',
				'tds_locker_cf_2_name' => 'Custom field 2',
				'tds_locker_cf_3_name' => 'Custom field 3',
			),
	'tds_payable' => 'paid_subscription',
	'tds_paid_subs_page_id' => $page_pricing_id,
	'tds_paid_subs_plan_ids' => [$plan_yearly_plan_id,$plan_monthly_plan_id],
			'tds_locker_styles' => array(
				'tds_bg_color' => '#ffffff',
				'all_tds_border' => '3px',
				'all_tds_border_color' => '#000000',
				'tds_submit_btn_bg_color' => '#2fc48d',
				'tds_submit_btn_bg_color_h' => '#34a86e',
				'tds_pp_checked_color' => '#2fc48d',
				'tds_pp_msg_links_color' => '#2fc48d',
				'tds_pp_msg_links_color_h' => '#34a86e',
				'tds_general_font_family' => 'global-2_global',
				'tds_title_font_family' => 'global-1_global',
				'tds_title_font_size' => '30',
				'tds_title_font_weight' => '700',
				'tds_title_font_spacing' => '-1',
				'tds_message_font_size' => '15',
				'tds_submit_btn_text_font_size' => '14',
				'tds_submit_btn_text_font_weight' => '500',
				'tds_submit_btn_text_font_transform' => 'uppercase',
				'tds_after_btn_text_font_size' => '15',
				'tds_pp_msg_font_size' => '14',
			),
	)
);

td_util::update_option('tds_demo_options', 'a:1:{s:5:"plans";a:2:{i:0;a:2:{s:9:"unique_id";s:15:"7264be215944925";s:4:"name";s:11:"Yearly Plan";}i:1;a:2:{s:9:"unique_id";s:15:"5564be21594496f";s:4:"name";s:12:"Monthly Plan";}}}');


/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTION - end phase 2
*/

/*  ---------------------------------------------------------------------------- 
	POSTS
*/

$post_td_post_from_startup_to_success_the_secrets_of_thriving_businesses_id = td_demo_content::add_post( array(
    'title' => 'From Startup to Success: The Secrets of Thriving Businesses',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_1',
    'categories_id_array' => array($cat_goals_id,$cat_growth_id,$cat_mindset_id,$cat_motivation_id,),
));

$post_td_post_business_agility_adapting_in_an_ever_changing_marketplace_id = td_demo_content::add_post( array(
    'title' => 'Business Agility: Adapting in an Ever-Changing Marketplace',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_2',
    'categories_id_array' => array($cat_goals_id,$cat_growth_id,$cat_mindset_id,$cat_motivation_id,),
));

$post_td_post_the_entrepreneurs_toolbox_essential_skills_for_excellence_id = td_demo_content::add_post( array(
    'title' => 'The Entrepreneur\'s Toolbox: Essential Skills for Excellence',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_3',
    'categories_id_array' => array($cat_goals_id,$cat_growth_id,$cat_mindset_id,$cat_motivation_id,),
));

$post_td_post_thriving_in_the_business_jungle_key_principles_for_growth_id = td_demo_content::add_post( array(
    'title' => 'Thriving in the Business Jungle: Key Principles for Growth',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_4',
    'categories_id_array' => array($cat_goals_id,$cat_growth_id,$cat_mindset_id,$cat_motivation_id,),
));

$post_td_post_navigating_the_business_landscape_strategies_for_success_id = td_demo_content::add_post( array(
    'title' => 'Navigating the Business Landscape: Strategies for Success',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_5',
    'tds_lock_content' => '1',
    'tds_locker' => $post_tds_default_wizard_locker_id,
    'categories_id_array' => array($cat_goals_id,$cat_growth_id,$cat_mindset_id,$cat_motivation_id,),
));

$post_td_post_the_growth_mindset_advantage_embracing_change_and_innovation_for_success_id = td_demo_content::add_post( array(
	'title' => 'The Growth Mindset Advantage: Embracing Change and Innovation for Success',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_growth_id,),
));

$post_td_post_scaling_new_heights_strategies_for_scaling_and_expanding_your_business_id = td_demo_content::add_post( array(
	'title' => 'Scaling New Heights: Strategies for Scaling and Expanding Your Business',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_growth_id,),
));

$post_td_post_cultivating_growth_mindset_fueling_continuous_learning_and_improvement_id = td_demo_content::add_post( array(
	'title' => 'Cultivating Growth Mindset: Fueling Continuous Learning and Improvement',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_growth_id,),
));

$post_td_post_from_small_steps_to_giant_leaps_fostering_sustainable_business_growth_id = td_demo_content::add_post( array(
	'title' => 'From Small Steps to Giant Leaps: Fostering Sustainable Business Growth',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_growth_id,),
));

$post_td_post_unleashing_your_growth_potential_strategies_for_personal_development_id = td_demo_content::add_post( array(
	'title' => 'Unleashing Your Growth Potential: Strategies for Personal Development',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_growth_id,),
));

$post_td_post_unleashing_the_power_of_positivity_how_a_positive_mindset_fuels_success_id = td_demo_content::add_post( array(
	'title' => 'Unleashing the Power of Positivity: How a Positive Mindset Fuels Success',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_mindset_id,),
));

$post_td_post_the_entrepreneurial_mindset_thriving_in_the_face_of_uncertainty_and_change_id = td_demo_content::add_post( array(
	'title' => 'The Entrepreneurial Mindset: Thriving in the Face of Uncertainty and Change',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_mindset_id,),
));

$post_td_post_cultivating_a_growth_mindset_embracing_challenges_for_personal_growth_id = td_demo_content::add_post( array(
	'title' => 'Cultivating a Growth Mindset: Embracing Challenges for Personal Growth',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_mindset_id,),
));

$post_td_post_mastering_the_entrepreneurial_mindset_building_blocks_for_success_id = td_demo_content::add_post( array(
	'title' => 'Mastering the Entrepreneurial Mindset: Building Blocks for Success',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_mindset_id,),
));

$post_td_post_the_power_of_mindset_shaping_your_path_to_business_excellence_id = td_demo_content::add_post( array(
	'title' => 'The Power of Mindset: Shaping Your Path to Business Excellence',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_mindset_id,),
));

$post_td_post_goal_clarity_paving_the_way_to_entrepreneurial_triumph_id = td_demo_content::add_post( array(
	'title' => 'Goal Clarity: Paving the Way to Entrepreneurial Triumph',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_goals_id,),
));

$post_td_post_setting_smart_goals_fueling_productivity_and_progress_id = td_demo_content::add_post( array(
	'title' => 'Setting SMART Goals: Fueling Productivity and Progress',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_goals_id,),
));

$post_td_post_dream_plan_achieve_the_transformative_power_of_goal_setting_id = td_demo_content::add_post( array(
	'title' => 'Dream, Plan, Achieve: The Transformative Power of Goal Setting',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_goals_id,),
));

$post_td_post_the_art_of_goal_setting_mapping_your_journey_to_achievement_id = td_demo_content::add_post( array(
	'title' => 'The Art of Goal Setting: Mapping Your Journey to Achievement',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_goals_id,),
));

$post_td_post_from_dream_to_reality_motivation_as_the_catalyst_for_triumph_id = td_demo_content::add_post( array(
	'title' => 'From Dream to Reality: Motivation as the Catalyst for Triumph',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_motivation_id,),
));

$post_td_post_goal_setting_unlocking_the_pathway_to_business_success_id = td_demo_content::add_post( array(
	'title' => 'Goal Setting: Unlocking the Pathway to Business Success',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_goals_id,),
));

$post_td_post_motivation_matters_unlocking_your_potential_for_great_success_id = td_demo_content::add_post( array(
	'title' => 'Motivation Matters: Unlocking Your Potential for Great Success',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_motivation_id,),
));

$post_td_post_fueling_success_harnessing_unwavering_motivation_for_achievement_id = td_demo_content::add_post( array(
	'title' => 'Fueling Success: Harnessing Unwavering Motivation for Achievement',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_motivation_id,),
));

$post_td_post_empowerment_in_motion_the_power_of_motivation_for_goal_attainment_id = td_demo_content::add_post( array(
	'title' => 'Empowerment in Motion: The Power of Motivation for Goal Attainment',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_motivation_id,),
));

$post_td_post_igniting_the_fire_within_unleashing_your_intrinsic_motivation_id = td_demo_content::add_post( array(
	'title' => 'Igniting the Fire Within: Unleashing Your Intrinsic Motivation',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_motivation_id,),
));


/*  ---------------------------------------------------------------------------- 
	PRODUCTS
*/

/*  ---------------------------------------------------------------------------- 
	TAXONOMIES
*/
$tax_term_business_mastery_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Business Mastery',
	'taxonomy' => 'tdtax_domains',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'Master the art of business with courses that cover essential skills for success. Learn to build a profitable online business, develop strategic plans, implement effective marketing strategies, and enhance your leadership and team-building abilities. Gain the expertise needed to thrive in today\'s competitive business environment.',
	'filter_image' => 'tdx_pic_1',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_motivation_and_goal_setting_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Motivation and Goal Setting',
	'taxonomy' => 'tdtax_domains',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'Ignite your motivation and achieve your goals with courses that provide practical techniques. Overcome procrastination, cultivate productivity, and develop a positive mindset for growth. Create a personalized vision board and learn the secrets to staying motivated and focused on your path to success.',
	'filter_image' => 'tdx_pic_2',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_mindset_and_mindfulness_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Mindset and Mindfulness',
	'taxonomy' => 'tdtax_domains',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'Harness the power of mindset and mindfulness through courses that foster personal growth and resilience. Cultivate a growth mindset, manage stress effectively, and develop emotional intelligence. Gain strategies to overcome challenges, enhance well-being, and promote a balanced and fulfilling life.',
	'filter_image' => 'tdx_pic_3',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_personal_development_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Personal Development',
	'taxonomy' => 'tdtax_domains',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'Invest in your personal growth with courses that empower you to unlock your potential. Discover your life\'s purpose, master time management, and develop self-discipline and habits for success. Boost creativity, build healthy relationships, and become the best version of yourself in all areas of life.',
	'filter_image' => 'tdx_pic_4',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_professional_growth_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Professional Growth',
	'taxonomy' => 'tdtax_domains',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'Accelerate your career with courses designed to advance your professional journey. Build a personal brand, learn networking strategies, and enhance your presentation and communication skills. Develop negotiation expertise and gain the knowledge and confidence to navigate career transitions and seize new opportunities.',
	'filter_image' => 'tdx_pic_5',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_financial_intelligence_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Financial Intelligence',
	'taxonomy' => 'tdtax_domains',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'Take charge of your financial future with courses that focus on wealth creation and financial intelligence. Learn personal finance management, investment strategies for stocks and real estate, and the essentials of entrepreneurship and business start-up funding. Discover methods for generating multiple income streams and effective tax planning for wealth preservation.',
	'filter_image' => 'tdx_pic_6',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_free_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Free',
	'taxonomy' => 'tdtax_price',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'Access valuable information through my Free Courses',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '#2fc48d',
	),
));

$tax_term_paid_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Paid',
	'taxonomy' => 'tdtax_price',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'Access Premium courses with incredible value / cost ratio',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '#000000',
	),
));


/*  ---------------------------------------------------------------------------- 
	CPTs
*/
$cpt_professional_networking_building_connections_for_career_advancement_id = td_demo_content::add_cpt( array(
	'title' => 'Professional Networking: Building Connections for Career Advancement',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_6',
	'post_meta' => array( 
		'tdcf_summary' => 'UGFydGljaXBhbnRzIHdpbGwgbGVhcm4gZWZmZWN0aXZlIG5ldHdvcmtpbmcgdGVjaG5pcXVlcywgaG93IHRvIGJ1aWxkIHJlbGF0aW9uc2hpcCwgYW5kIGV4cGFuZGluZyBwcm9mZXNzaW9uYWwgb3Bwb3J0dW5pdGllcywgZm9zdGVyaW5nIGNhcmVlciBncm93dGggYW5kIGFkdmFuY2VtZW50Lg==',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MTJo',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'NQ==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_professional_growth_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
    'tds_lock_content' => '1',
    'tds_locker' => $post_tds_default_wizard_locker_id,
));

$cpt_mastering_time_management_maximizing_productivity_and_efficiency_id = td_demo_content::add_cpt( array(
	'title' => 'Mastering Time Management: Maximizing Productivity and Efficiency',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_7',
	'post_meta' => array( 
		'tdcf_summary' => 'TWF4aW1pemluZyBQcm9kdWN0aXZpdHkgYW5kIEVmZmljaWVuY3k6IFRoaXMgY291cnNlIGVxdWlwcyBpbmRpdmlkdWFscyB3aXRoIHByYWN0aWNhbCBzdHJhdGVnaWVzIHRvIG1hbmFnZSB0aGVpciB0aW1lIGVmZmVjdGl2ZWx5LCBwcmlvcml0aXplIHRhc2tzLCBhbmQgYWNoaWV2ZSBvcHRpbWFsIHByb2R1Y3Rpdml0eSwgZW5oYW5jaW5nIHRoZWlyIHByb2Zlc3Npb25hbCBncm93dGggYW5kIHN1Y2Nlc3Mu',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MTBo',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'Ng==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'bm8=',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => '',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_professional_growth_id ),
		'tdtax_price' => array( $tax_term_free_id ),
	),
));

$cpt_building_a_personal_brand_establishing_a_great_professional_identity_id = td_demo_content::add_cpt( array(
	'title' => 'Building a Personal Brand: Establishing a Great Professional Identity',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_8',
	'post_meta' => array( 
		'tdcf_summary' => 'UGFydGljaXBhbnRzIGxlYXJuIGhvdyB0byBidWlsZCBhIHN0cm9uZyBwZXJzb25hbCBicmFuZCwgaW5jbHVkaW5nIG9ubGluZSBwcmVzZW5jZSwgbmV0d29ya2luZywgYW5kIHNob3djYXNpbmcgc2tpbGxzIGFuZCBleHBlcnRpc2UsIGVuYWJsaW5nIHRoZW0gdG8gc3RhbmQgb3V0IGluIHRoZWlyIGZpZWxkIGFuZCBhZHZhbmNlIHRoZWlyIHByb2Zlc3Npb25hbCBncm93dGgu',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MjZo',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'MTA=',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_professional_growth_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_leadership_development_nurturing_effective_leadership_skills_id = td_demo_content::add_cpt( array(
	'title' => 'Leadership Development: Nurturing Effective Leadership Skills',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_9',
	'post_meta' => array( 
		'tdcf_summary' => 'VGhpcyBjb3Vyc2UgZm9jdXNlcyBvbiBkZXZlbG9waW5nIGVmZmVjdGl2ZSBsZWFkZXJzaGlwIHF1YWxpdGllcywgaW5jbHVkaW5nIGRlY2lzaW9uLW1ha2luZywgdGVhbSBidWlsZGluZywgYW5kIHN0cmF0ZWdpYyB0aGlua2luZywgZW1wb3dlcmluZyBpbmRpdmlkdWFscyB0byBiZWNvbWUgaW5mbHVlbnRpYWwgbGVhZGVycyBhbmQgZHJpdmUgcHJvZmVzc2lvbmFsIGdyb3d0aCBhbmQgc3VjY2Vzcy4=',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'U3BhbmlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MTNo',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'MTE=',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'bm8=',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => '',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_professional_growth_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_effective_communication_skills_mastering_professional_interactions_id = td_demo_content::add_cpt( array(
	'title' => 'Effective Communication Skills: Mastering Professional Interactions',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_10',
	'post_meta' => array( 
		'tdcf_summary' => 'TWFzdGVyaW5nIFByb2Zlc3Npb25hbCBJbnRlcmFjdGlvbnM6IFBhcnRpY2lwYW50cyBsZWFybiBlc3NlbnRpYWwgY29tbXVuaWNhdGlvbiBza2lsbHMsIHN1Y2ggYXMgYWN0aXZlIGxpc3RlbmluZywgY29uZmxpY3QgcmVzb2x1dGlvbiwgYW5kIGFzc2VydGl2ZW5lc3MsIGVuYWJsaW5nIHRoZW0gdG8gZXhjZWwgaW4gcHJvZmVzc2lvbmFsIGVudmlyb25tZW50cywgYnVpbGQgc3Ryb25nIHJlbGF0aW9uc2hpcHMsIGFuZCBhY2hpZXZlIHByb2Zlc3Npb25hbCBncm93dGgu',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MTVo',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'NQ==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_professional_growth_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_advancing_your_career_unlocking_professional_growth_and_success_id = td_demo_content::add_cpt( array(
	'title' => 'Advancing Your Career: Unlocking Professional Growth and Success',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_summary' => 'VW5sb2NraW5nIFByb2Zlc3Npb25hbCBHcm93dGggYW5kIFN1Y2Nlc3M6IFRoaXMgY291cnNlIHByb3ZpZGVzIGluZGl2aWR1YWxzIHdpdGggdGhlIHRvb2xzIGFuZCBzdHJhdGVnaWVzIHRvIGVuaGFuY2UgdGhlaXIgcHJvZmVzc2lvbmFsIGdyb3d0aCwgaW5jbHVkaW5nIHNraWxsIGRldmVsb3BtZW50LCBuZXR3b3JraW5nLCBhbmQgZWZmZWN0aXZlIGNhcmVlciBwbGFubmluZywgbGVhZGluZyB0byBpbmNyZWFzZWQgb3Bwb3J0dW5pdGllcyBhbmQgYWR2YW5jZW1lbnQu',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MTFo',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'Ng==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_professional_growth_id ),
		'tdtax_price' => array( $tax_term_free_id ),
	),
));

$cpt_building_resilience_embracing_change_and_overcoming_obstacles_id = td_demo_content::add_cpt( array(
	'title' => 'Building Resilience: Embracing Change and Overcoming Obstacles',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_summary' => 'UGFydGljaXBhbnRzIGxlYXJuIHN0cmF0ZWdpZXMgdG8gYnVpbGQgcmVzaWxpZW5jZSwgYWRhcHQgdG8gY2hhbmdlLCBhbmQgb3ZlcmNvbWUgb2JzdGFjbGVzLCBlcXVpcHBpbmcgdGhlbSB3aXRoIHRoZSB0b29scyB0byBib3VuY2UgYmFjaywgc3RheSBtb3RpdmF0ZWQsIGFuZCB0aHJpdmUgaW4gdGhlIGZhY2Ugb2YgYWR2ZXJzaXR5IG9uIHRoZWlyIHBlcnNvbmFsIGRldmVsb3BtZW50IGpvdXJuZXku',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MTVo',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'NA==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'bm8=',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => '',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_personal_development_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_cultivating_a_growth_mindset_thriving_in_the_face_of_challenges_id = td_demo_content::add_cpt( array(
	'title' => 'Cultivating a Growth Mindset: Thriving in the Face of Challenges',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_summary' => 'VGhyaXZpbmcgaW4gdGhlIEZhY2Ugb2YgQ2hhbGxlbmdlczogVGhpcyBjb3Vyc2UgZm9jdXNlcyBvbiBkZXZlbG9waW5nIGEgZ3Jvd3RoIG1pbmRzZXQsIGVuYWJsaW5nIGluZGl2aWR1YWxzIHRvIGVtYnJhY2UgY2hhbGxlbmdlcywgbGVhcm4gZnJvbSBmYWlsdXJlcywgYW5kIHBlcnNpc3QgaW4gdGhlIHB1cnN1aXQgb2YgcGVyc29uYWwgZ3Jvd3RoLCBsZWFkaW5nIHRvIGluY3JlYXNlZCByZXNpbGllbmNlIGFuZCBzdWNjZXNzLg==',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'OGg=',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'MTI=',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_personal_development_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_goal_setting_and_action_planning_transforming_dreams_into_reality_id = td_demo_content::add_cpt( array(
	'title' => 'Goal Setting and Action Planning: Transforming Dreams into Reality',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_summary' => 'UGFydGljaXBhbnRzIGdhaW4gdGhlIHNraWxscyB0byBzZXQgbWVhbmluZ2Z1bCBnb2FscywgY3JlYXRlIGFjdGlvbmFibGUgcGxhbnMsIGFuZCBzdGF5IG1vdGl2YXRlZCBhbmQgZm9jdXNlZCwgZW5hYmxpbmcgdGhlbSB0byB0dXJuIHRoZWlyIGRyZWFtcyBpbnRvIHRhbmdpYmxlIHJlc3VsdHMgYW5kIGFjaGlldmUgdGhlaXIgZGVzaXJlZCBvdXRjb21lcy4=',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaCwgU3BhbmlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MTJo',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'Nw==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_personal_development_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_lifelong_learning_the_pathway_to_continuous_personal_growth_id = td_demo_content::add_cpt( array(
	'title' => 'Lifelong Learning: The Pathway to Continuous Personal Growth',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_summary' => 'VGhpcyBjb3Vyc2UgZW1waGFzaXplcyB0aGUgaW1wb3J0YW5jZSBvZiBjb250aW51b3VzIGxlYXJuaW5nIGFuZCBwcm92aWRlcyBpbmRpdmlkdWFscyB3aXRoIHRvb2xzIGFuZCBzdHJhdGVnaWVzIHRvIGZvc3RlciBhIG1pbmRzZXQgb2YgY3VyaW9zaXR5LCBleHBhbmQgdGhlaXIga25vd2xlZGdlLCBhbmQgY29udGludW91c2x5IGdyb3cgYW5kIGFkYXB0IGluIGFuIGV2ZXItY2hhbmdpbmcgd29ybGQu',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'OWg=',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'Nw==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'bm8=',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => '',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_personal_development_id ),
		'tdtax_price' => array( $tax_term_free_id ),
	),
));

$cpt_emotional_intelligence_nurturing_relationships_and_self_awareness_id = td_demo_content::add_cpt( array(
	'title' => 'Emotional Intelligence: Nurturing Relationships and Self-Awareness',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_6',
	'post_meta' => array( 
		'tdcf_summary' => 'UGFydGljaXBhbnRzIGxlYXJuIHRvIGN1bHRpdmF0ZSBlbW90aW9uYWwgaW50ZWxsaWdlbmNlLCBlbmhhbmNpbmcgdGhlaXIgYWJpbGl0eSB0byB1bmRlcnN0YW5kIGFuZCBtYW5hZ2UgZW1vdGlvbnMsIGJ1aWxkIHN0cm9uZyByZWxhdGlvbnNoaXBzLCBhbmQgZGV2ZWxvcCBzZWxmLWF3YXJlbmVzcyBmb3IgcGVyc29uYWwgYW5kIHByb2Zlc3Npb25hbCBzdWNjZXNzLg==',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'Nmg=',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'Ng==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_personal_development_id ),
		'tdtax_price' => array( $tax_term_free_id ),
	),
));

$cpt_discovering_your_potential_unleashing_personal_development_for_success_id = td_demo_content::add_cpt( array(
	'title' => 'Discovering Your Potential: Unleashing Personal Development for Success',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_7',
	'post_meta' => array( 
		'tdcf_summary' => 'VGhpcyBjb3Vyc2UgZW1wb3dlcnMgaW5kaXZpZHVhbHMgdG8gdW5sb2NrIHRoZWlyIHRydWUgcG90ZW50aWFsIGJ5IGV4cGxvcmluZyBzZWxmLWRpc2NvdmVyeSwgc2V0dGluZyBtZWFuaW5nZnVsIGdvYWxzLCBhbmQgZGV2ZWxvcGluZyBlZmZlY3RpdmUgc3RyYXRlZ2llcyBmb3IgcGVyc29uYWwgZ3Jvd3RoIGFuZCBzdWNjZXNzLg==',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'NWg=',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'Mw==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'bm8=',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => '',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_personal_development_id ),
		'tdtax_price' => array( $tax_term_free_id ),
	),
));

$cpt_ignite_your_inner_drive_mastering_unstoppable_motivation_for_success_id = td_demo_content::add_cpt( array(
	'title' => 'Ignite Your Inner Drive: Mastering Unstoppable Motivation for Success',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_8',
	'post_meta' => array( 
		'tdcf_summary' => 'VGhpcyBjb3Vyc2UgZW1wb3dlcnMgaW5kaXZpZHVhbHMgdG8gdGFwIGludG8gdGhlaXIgaW5uZXIgZHJpdmUsIHVuZGVyc3RhbmQgZGlmZmVyZW50IHR5cGVzIG9mIG1vdGl2YXRpb24sIGFuZCB1dGlsaXplIHN0cmF0ZWdpZXMgdG8gc3RheSBtb3RpdmF0ZWQgb24gdGhlaXIgam91cm5leSB0byBzdWNjZXNzLg==',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MjBo',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'MTU=',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_motivation_and_goal_setting_id ),
		'tdtax_price' => array( $tax_term_free_id ),
	),
));

$cpt_setting_and_achieving_smart_goals_the_blueprint_to_accomplishment_id = td_demo_content::add_cpt( array(
	'title' => 'Setting and Achieving SMART Goals: The Blueprint to Accomplishment',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_9',
	'post_meta' => array( 
		'tdcf_summary' => 'VGhlIEJsdWVwcmludCB0byBBY2NvbXBsaXNobWVudDogUGFydGljaXBhbnRzIGxlYXJuIGhvdyB0byBzZXQgY2xlYXIgYW5kIGFjdGlvbmFibGUgZ29hbHMgdXNpbmcgdGhlIFNNQVJUIGZyYW1ld29yaywgZW5zdXJpbmcgZm9jdXNlZCBlZmZvcnRzLCBtZWFzdXJhYmxlIHByb2dyZXNzLCBhbmQgaW5jcmVhc2VkIGxpa2VsaWhvb2Qgb2YgYWNoaWV2aW5nIGRlc2lyZWQgb3V0Y29tZXMu',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'U3BhbmlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MTVo',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'MTI=',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_motivation_and_goal_setting_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_creating_a_motivating_environment_building_support_and_trust_id = td_demo_content::add_cpt( array(
	'title' => 'Creating a Motivating Environment: Building Support and Trust',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_10',
	'post_meta' => array( 
		'tdcf_summary' => 'QnVpbGRpbmcgU3VwcG9ydCBhbmQgQWNjb3VudGFiaWxpdHkgZm9yIEdvYWwgQXR0YWlubWVudDogVGhpcyBjb3Vyc2UgdGVhY2hlcyBpbmRpdmlkdWFscyBob3cgdG8gY3VsdGl2YXRlIGFuIGVudmlyb25tZW50IHRoYXQgZm9zdGVycyBtb3RpdmF0aW9uLCBpbmNsdWRpbmcgc2Vla2luZyBzdXBwb3J0LCBlc3RhYmxpc2hpbmcgYWNjb3VudGFiaWxpdHkgc3lzdGVtcywgYW5kIHN1cnJvdW5kaW5nIHRoZW1zZWx2ZXMgd2l0aCBwb3NpdGl2ZSBpbmZsdWVuY2VzLg==',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MTRo',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'MTE=',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'bm8=',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => '',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_motivation_and_goal_setting_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_overcoming_obstacles_maintaining_motivation_in_the_face_of_challenges_id = td_demo_content::add_cpt( array(
	'title' => 'Overcoming Obstacles: Maintaining Motivation in the Face of Challenges',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_summary' => 'U3RyYXRlZ2llcyBmb3IgTWFpbnRhaW5pbmcgTW90aXZhdGlvbiBpbiB0aGUgRmFjZSBvZiBDaGFsbGVuZ2VzOiBQYXJ0aWNpcGFudHMgZGV2ZWxvcCByZXNpbGllbmNlLCBsZWFybiB0ZWNobmlxdWVzIHRvIG92ZXJjb21lIG9ic3RhY2xlcywgYW5kIGN1bHRpdmF0ZSBhIGdyb3d0aCBtaW5kc2V0IHRvIHN0YXkgbW90aXZhdGVkIGR1cmluZyBjaGFsbGVuZ2luZyB0aW1lcyBvbiB0aGVpciBwYXRoIHRvIHN1Y2Nlc3Mu',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaCwgU3BhbmlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'OGg=',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'MTY=',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'bm8=',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => '',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_motivation_and_goal_setting_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_motivation_mastery_cultivating_consistency_and_momentum_id = td_demo_content::add_cpt( array(
	'title' => 'Motivation Mastery: Cultivating Consistency and Momentum',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_summary' => 'Q3VsdGl2YXRpbmcgQ29uc2lzdGVuY3kgYW5kIE1vbWVudHVtIG9uIFlvdXIgSm91cm5leTogVGhpcyBjb3Vyc2UgcHJvdmlkZXMgc3RyYXRlZ2llcyBmb3Igc3VzdGFpbmluZyBtb3RpdmF0aW9uIGJ5IHJldmlld2luZyBnb2FscywgY2VsZWJyYXRpbmcgbWlsZXN0b25lcywgcHJhY3RpY2luZyBzZWxmLWNhcmUsIGFuZCBzZWVraW5nIGluc3BpcmF0aW9uIHRvIG1haW50YWluIGNvbnNpc3RlbnQgcHJvZ3Jlc3MgYW5kIG1vbWVudHVtLg==',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'Mjho',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'MjI=',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_motivation_and_goal_setting_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_mindset_harnessing_the_power_of_motivation_and_goal_setting_id = td_demo_content::add_cpt( array(
	'title' => 'Mindset: Harnessing the Power of Motivation and Goal Setting',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_summary' => 'SGFybmVzc2luZyB0aGUgUG93ZXIgb2YgTW90aXZhdGlvbiBhbmQgR29hbCBTZXR0aW5nIGZvciBQZXJzb25hbCBHcm93dGg6IFBhcnRpY2lwYW50cyBleHBsb3JlIHRoZSBjb25uZWN0aW9uIGJldHdlZW4gbWluZHNldCwgbW90aXZhdGlvbiwgYW5kIGdvYWwgc2V0dGluZywgZXF1aXBwaW5nIHRoZW0gd2l0aCB0aGUgdG9vbHMgdG8gY3VsdGl2YXRlIGEgcG9zaXRpdmUgbWluZHNldCBhbmQgbGV2ZXJhZ2UgbW90aXZhdGlvbiBmb3IgcGVyc29uYWwgZ3Jvd3RoIGFuZCB0cmFuc2Zvcm1hdGlvbi4=',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'NWg=',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'Ng==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'bm8=',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => '',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_motivation_and_goal_setting_id ),
		'tdtax_price' => array( $tax_term_free_id ),
	),
));

$cpt_mastering_the_growth_mindset_unleashing_your_full_potential_for_success_id = td_demo_content::add_cpt( array(
	'title' => 'Mastering the Growth Mindset: Unleashing Your Full Potential for Success',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_summary' => 'VGhpcyBjb3Vyc2UgZW1wb3dlcnMgaW5kaXZpZHVhbHMgdG8gZGV2ZWxvcCBhIGdyb3d0aCBtaW5kc2V0LCBlbmFibGluZyB0aGVtIHRvIGVtYnJhY2UgY2hhbGxlbmdlcywgcGVyc2lzdCBpbiB0aGUgZmFjZSBvZiBzZXRiYWNrcywgYW5kIHVubG9jayB0aGVpciBmdWxsIHBvdGVudGlhbCBmb3Igc3VjY2Vzcy4=',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'U3BhbmlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'Nmg=',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'Ng==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'bm8=',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => '',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_mindset_and_mindfulness_id ),
		'tdtax_price' => array( $tax_term_free_id ),
	),
));

$cpt_the_power_of_positivity_cultivating_a_mindset_of_optimism_and_abundance_id = td_demo_content::add_cpt( array(
	'title' => 'The Power of Positivity: Cultivating a Mindset of Optimism and Abundance',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_summary' => 'Q3VsdGl2YXRpbmcgYSBwb3NpdGl2ZSBtaW5kc2V0IGlzIHRoZSBmb2N1cyBvZiB0aGlzIGNvdXJzZSwgZXF1aXBwaW5nIGluZGl2aWR1YWxzIHdpdGggdGhlIHRvb2xzIHRvIGZvc3RlciBvcHRpbWlzbSwgZ3JhdGl0dWRlLCBhbmQgYWJ1bmRhbmNlLCBsZWFkaW5nIHRvIGluY3JlYXNlZCBoYXBwaW5lc3MgYW5kIHN1Y2Nlc3MgaW4gYWxsIGFyZWFzIG9mIGxpZmUu',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MTJo',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'MTU=',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_mindset_and_mindfulness_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_mindfulness_in_everyday_life_embracing_the_moment_for_inner_peace_id = td_demo_content::add_cpt( array(
	'title' => 'Mindfulness in Everyday Life: Embracing the Moment for Inner Peace',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_6',
	'post_meta' => array( 
		'tdcf_summary' => 'VGhpcyBjb3Vyc2UgdGVhY2hlcyBpbmRpdmlkdWFscyB0aGUgcHJhY3RpY2Ugb2YgbWluZGZ1bG5lc3MsIGVuYWJsaW5nIHRoZW0gdG8gZmluZCBjbGFyaXR5IGFuZCBpbm5lciBwZWFjZSBieSBiZWluZyBmdWxseSBwcmVzZW50LCBlbmhhbmNpbmcgZm9jdXMsIHJlZHVjaW5nIHN0cmVzcywgYW5kIGltcHJvdmluZyBvdmVyYWxsIHdlbGwtYmVpbmcu',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'Nmg=',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'NQ==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'bm8=',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => '',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_mindset_and_mindfulness_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_resilient_mindset_building_inner_strength_to_overcome_challenges_id = td_demo_content::add_cpt( array(
	'title' => 'Resilient Mindset: Building Inner Strength to Overcome Challenges',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_7',
	'post_meta' => array( 
		'tdcf_summary' => 'QnVpbGRpbmcgcmVzaWxpZW5jZSBpcyB0aGUgZ29hbCBvZiB0aGlzIGNvdXJzZSwgZW1wb3dlcmluZyBpbmRpdmlkdWFscyB0byBib3VuY2UgYmFjayBmcm9tIHNldGJhY2tzLCBhZGFwdCB0byBjaGFuZ2UsIGFuZCB0aHJpdmUgaW4gdGhlIGZhY2Ugb2YgYWR2ZXJzaXR5LCBmb3N0ZXJpbmcgcGVyc2V2ZXJhbmNlLCBwcm9ibGVtLXNvbHZpbmcgc2tpbGxzLCBhbmQgZW1vdGlvbmFsIHdlbGwtYmVpbmcu',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaCwgRnJlbmNo',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'Nmg=',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'NA==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'bm8=',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => '',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_mindset_and_mindfulness_id ),
		'tdtax_price' => array( $tax_term_free_id ),
	),
));

$cpt_goal_achievement_through_mindset_mastery_aligning_your_thoughts_for_success_id = td_demo_content::add_cpt( array(
	'title' => 'Goal Achievement through Mindset Mastery: Aligning Your Thoughts for Success',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_8',
	'post_meta' => array( 
		'tdcf_summary' => 'VGhpcyBjb3Vyc2UgZm9jdXNlcyBvbiBhbGlnbmluZyB0aG91Z2h0cyB3aXRoIHN1Y2Nlc3MsIHByb3ZpZGluZyBpbmRpdmlkdWFscyB3aXRoIHN0cmF0ZWdpZXMgdG8gc2V0IGFuZCBhY2hpZXZlIGdvYWxzIGJ5IGRldmVsb3BpbmcgYSBnb2FsLW9yaWVudGVkIG1pbmRzZXQsIG92ZXJjb21pbmcgc2VsZi1kb3VidCwgYW5kIG1haW50YWluaW5nIG1vdGl2YXRpb24u',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'OGg=',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'Ng==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_mindset_and_mindfulness_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_mindfulness_for_stress_reduction_techniques_for_cultivating_well_being_id = td_demo_content::add_cpt( array(
	'title' => 'Mindfulness for Stress Reduction: Techniques for Cultivating Well-being',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_9',
	'post_meta' => array( 
		'tdcf_summary' => 'VGVjaG5pcXVlcyBmb3IgQ3VsdGl2YXRpbmcgQ2FsbSBhbmQgRW1vdGlvbmFsIFdlbGwtYmVpbmc6IFRoaXMgY291cnNlIGVxdWlwcyBpbmRpdmlkdWFscyB3aXRoIG1pbmRmdWxuZXNzIHRlY2huaXF1ZXMgdG8gcmVkdWNlIHN0cmVzcywgbWFuYWdlIGVtb3Rpb25zLCBhbmQgY3VsdGl2YXRlIGEgc2Vuc2Ugb2YgY2FsbSwgbGVhZGluZyB0byBpbXByb3ZlZCBtZW50YWwgYW5kIGVtb3Rpb25hbCB3ZWxsLWJlaW5nLg==',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaCwgU3BhbmlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MTZo',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'Nw==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_mindset_and_mindfulness_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_cash_flow_management_optimizing_your_financial_resources_to_perfection_id = td_demo_content::add_cpt( array(
	'title' => 'Cash Flow Management: Optimizing Your Financial Resources to Perfection',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_10',
	'post_meta' => array( 
		'tdcf_summary' => 'U3R1ZGVudHMgd2lsbCBhY3F1aXJlIHNraWxscyB0byBlZmZlY3RpdmVseSBtYW5hZ2UgdGhlaXIgY2FzaCBmbG93LCBvcHRpbWl6ZSBzYXZpbmdzLCBjb250cm9sIGV4cGVuc2VzLCBhbmQgbWFrZSB3aXNlIGZpbmFuY2lhbCBkZWNpc2lvbnMgdG8gYWNoaWV2ZSB0aGVpciBmaW5hbmNpYWwgZ29hbHMu',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MTA=',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'Ng==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'bm8=',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => '',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_financial_intelligence_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_building_a_strong_financial_foundation_the_basics_of_financial_intelligence_id = td_demo_content::add_cpt( array(
	'title' => 'Building a Strong Financial Foundation: The Basics of Financial Intelligence',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_summary' => 'U3R1ZGVudHMgbGVhcm4gcHJpbmNpcGxlcyBhbmQgcHJhY3RpY2VzIHRvIGVzdGFibGlzaCBzb2xpZCBmaW5hbmNpYWwgZm91bmRhdGlvbnMsIGluY2x1ZGluZyBzYXZpbmcgc3RyYXRlZ2llcywgZW1lcmdlbmN5IGZ1bmRzLCBhbmQgZGVidCBtYW5hZ2VtZW50IHRlY2huaXF1ZXMu',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaCwgU3BhbmlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MTY=',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'OA==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_financial_intelligence_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_risk_management_and_insurance_essentials_safeguarding_your_financial_future_id = td_demo_content::add_cpt( array(
	'title' => 'Risk Management and Insurance Essentials: Safeguarding Your Financial Future',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_summary' => 'U3R1ZGVudHMgd2lsbCBsZWFybiB0byBhc3Nlc3MgYW5kIG1hbmFnZSBmaW5hbmNpYWwgcmlza3MsIHVuZGVyc3RhbmQgaW5zdXJhbmNlIG9wdGlvbnMsIGFuZCBkZXZlbG9wIHN0cmF0ZWdpZXMgdG8gcHJvdGVjdCB0aGVpciBhc3NldHMgYW5kIGZpbmFuY2lhbCB3ZWxsLWJlaW5nLg==',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MjU=',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'MTA=',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_financial_intelligence_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_investing_strategies_for_wealth_unleashing_the_power_of_financial_intelligence_id = td_demo_content::add_cpt( array(
	'title' => 'Investing Strategies for Wealth: Unleashing the Power of Financial Intelligence',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_summary' => 'U3R1ZGVudHMgd2lsbCBsZWFybiB2YXJpb3VzIGludmVzdG1lbnQgc3RyYXRlZ2llcywgdW5kZXJzdGFuZCByaXNrIGFuYWx5c2lzLCBhbmQgZ2FpbiBpbnNpZ2h0cyBpbnRvIGFzc2V0IGFsbG9jYXRpb24sIGVuYWJsaW5nIHRoZW0gdG8gbWFrZSBpbmZvcm1lZCBpbnZlc3RtZW50IGRlY2lzaW9ucyB0byBncm93IHRoZWlyIHdlYWx0aC4=',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaCwgRnJlbmNo',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MjA=',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'MzY=',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_financial_intelligence_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_budgeting_mastery_taking_control_of_your_finances_for_financial_intelligence_id = td_demo_content::add_cpt( array(
	'title' => 'Budgeting Mastery: Taking Control of Your Finances for Financial Intelligence',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_summary' => 'U3R1ZGVudHMgd2lsbCBhY3F1aXJlIHNraWxscyBpbiBjcmVhdGluZyBhbmQgbWFuYWdpbmcgYnVkZ2V0cywgdHJhY2tpbmcgZXhwZW5zZXMsIGFuZCBwcmlvcml0aXppbmcgc3BlbmRpbmcsIGVuYWJsaW5nIHRoZW0gdG8gdGFrZSBjb250cm9sIG9mIHRoZWlyIGZpbmFuY2VzIGFuZCBhY2hpZXZlIGZpbmFuY2lhbCBzdGFiaWxpdHku',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MTU=',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'NQ==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_financial_intelligence_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_financial_literacy_101_learn_how_to_master_the_basics_of_personal_finance_id = td_demo_content::add_cpt( array(
	'title' => 'Financial Literacy 101: Learn how to Master the Basics of Personal Finance',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_summary' => 'U3R1ZGVudHMgd2lsbCBsZWFybiB0aGUgZnVuZGFtZW50YWwgY29uY2VwdHMgb2YgcGVyc29uYWwgZmluYW5jZSwgaW5jbHVkaW5nIGJ1ZGdldGluZywgc2F2aW5nLCBpbnZlc3RpbmcsIGFuZCBkZWJ0IG1hbmFnZW1lbnQsIGVxdWlwcGluZyB0aGVtIHdpdGggdGhlIGtub3dsZWRnZSB0byBtYWtlIGluZm9ybWVkIGZpbmFuY2lhbCBkZWNpc2lvbnMu',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'Ng==',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_sections' => 'MTA=',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_financial_intelligence_id ),
		'tdtax_price' => array( $tax_term_free_id ),
	),
));

$cpt_data_driven_decision_making_analytics_for_business_mastery_id = td_demo_content::add_cpt( array(
	'title' => 'Data-Driven Decision Making: Analytics for Business Mastery',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_6',
	'post_meta' => array( 
		'tdcf_summary' => 'U3R1ZGVudHMgd2lsbCBoYXJuZXNzIHRoZSBwb3dlciBvZiBkYXRhIGFuYWx5dGljcyB0byBtYWtlIGluZm9ybWVkIGRlY2lzaW9ucywgaWRlbnRpZnkgb3Bwb3J0dW5pdGllcywgYW5kIG9wdGltaXplIGJ1c2luZXNzIHByb2Nlc3NlcyBmb3IgZW5oYW5jZWQgcGVyZm9ybWFuY2UgYW5kIHByb2ZpdGFiaWxpdHku',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_bestseller' => '',
		'_tdcf_bestseller' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MTZo',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_diploma' => 'eWVz',
		'_tdcf_diploma' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_sections' => 'Nw==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_business_mastery_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_innovation_and_adaptability_thriving_in_a_rapidly_changing_business_landscape_id = td_demo_content::add_cpt( array(
	'title' => 'Innovation and Adaptability: Thriving in a Rapidly Changing Business Landscape',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_7',
	'post_meta' => array( 
		'tdcf_summary' => 'U3R1ZGVudHMgd2lsbCBsZWFybiB0byBlbWJyYWNlIGlubm92YXRpb24sIGFudGljaXBhdGUgaW5kdXN0cnkgdHJlbmRzLCBhbmQgZGV2ZWxvcCBhZGFwdGFibGUgc3RyYXRlZ2llcyB0byBzdGF5IGFoZWFkIG9mIHRoZSBjb21wZXRpdGlvbiBpbiBhIHJhcGlkbHkgZXZvbHZpbmcgYnVzaW5lc3MgbGFuZHNjYXBlLg==',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_bestseller' => '',
		'_tdcf_bestseller' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'OGgzMG1pbg==',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_diploma' => 'eWVz',
		'_tdcf_diploma' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_sections' => 'Nw==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_resources' => 'bm8=',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_business_mastery_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_financial_management_mastering_numbers_for_profitability_and_sustainable_growth_id = td_demo_content::add_cpt( array(
	'title' => 'Financial Management: Mastering Numbers for Profitability and Sustainable Growth',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_8',
	'post_meta' => array( 
		'tdcf_summary' => 'U3R1ZGVudHMgd2lsbCBnYWluIGV4cGVydGlzZSBpbiBmaW5hbmNpYWwgcHJpbmNpcGxlcywgYnVkZ2V0aW5nLCBmb3JlY2FzdGluZywgYW5kIHJlc291cmNlIGFsbG9jYXRpb24sIGVuYWJsaW5nIHRoZW0gdG8gb3B0aW1pemUgZmluYW5jaWFsIHBlcmZvcm1hbmNlLCBjb250cm9sIGNvc3RzLCBhbmQgbWFrZSBpbmZvcm1lZCBmaW5hbmNpYWwgZGVjaXNpb25zLg==',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_bestseller' => '',
		'_tdcf_bestseller' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MjFo',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_diploma' => 'eWVz',
		'_tdcf_diploma' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_sections' => 'Nw==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_business_mastery_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_marketing_mastery_strategies_for_effective_customer_engagement_id = td_demo_content::add_cpt( array(
	'title' => 'Marketing Mastery: Strategies for Effective Customer Engagement',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_9',
	'post_meta' => array( 
		'tdcf_summary' => 'U3R1ZGVudHMgd2lsbCBtYXN0ZXIgbWFya2V0aW5nIHN0cmF0ZWdpZXMsIGN1c3RvbWVyIGVuZ2FnZW1lbnQgdGVjaG5pcXVlcywgYW5kIGRpZ2l0YWwgcGxhdGZvcm1zIHRvIGNyZWF0ZSBpbXBhY3RmdWwgbWFya2V0aW5nIGNhbXBhaWducywgYnVpbGQgc3Ryb25nIGJyYW5kcywgYW5kIGRyaXZlIGJ1c2luZXNzIGdyb3d0aC4=',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_bestseller' => '',
		'_tdcf_bestseller' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'Ng==',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_diploma' => 'eWVz',
		'_tdcf_diploma' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_sections' => 'MTA=',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_language' => 'RW5nbGlzaCwgU3BhbmlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_resources' => 'bm8=',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => '',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_business_mastery_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_leadership_excellence_unlocking_your_leadership_potential_for_business_mastery_id = td_demo_content::add_cpt( array(
	'title' => 'Leadership Excellence: Unlocking Your Leadership Potential for Business Mastery',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_10',
	'post_meta' => array( 
		'tdcf_summary' => 'U3R1ZGVudHMgd2lsbCBhY3F1aXJlIGVzc2VudGlhbCBsZWFkZXJzaGlwIHNraWxscywgaW5jbHVkaW5nIGVmZmVjdGl2ZSBjb21tdW5pY2F0aW9uLCBlbW90aW9uYWwgaW50ZWxsaWdlbmNlLCBhbmQgZGVjaXNpb24tbWFraW5nLCBlbmFibGluZyB0aGVtIHRvIGluc3BpcmUgdGVhbXMsIGZvc3RlciBhIHBvc2l0aXZlIG9yZ2FuaXphdGlvbmFsIGN1bHR1cmUsIGFuZCBkcml2ZSBleGNlcHRpb25hbCBidXNpbmVzcyByZXN1bHRzLg==',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_bestseller' => '',
		'_tdcf_bestseller' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'MTU=',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_diploma' => 'eWVz',
		'_tdcf_diploma' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_sections' => 'OA==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_language' => 'RW5nbGlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_resources' => 'eWVz',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_file' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vc2VsZWN0X2RlbW8vd3AtY29udGVudC91cGxvYWRzLzIwMjIvMDIvc2QtaGVyby1pbWdhZ2UucG5n',
		'_tdcf_file' => 'ZmllbGRfNjQ2ZjViNjc0MGYxZA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_business_mastery_id ),
		'tdtax_price' => array( $tax_term_paid_id ),
	),
));

$cpt_strategic_vision_mastering_long_term_planning_for_business_success_id = td_demo_content::add_cpt( array(
	'title' => 'Strategic Vision: Mastering Long-Term Planning for Business Success',
	'type' => 'tdcpt_courses',
	'file' => 'tdcpt_courses_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_summary' => 'U3R1ZGVudHMgd2lsbCBsZWFybiB0byBkZXZlbG9wIGEgY29tcGVsbGluZyBsb25nLXRlcm0gc3RyYXRlZ2ljIHZpc2lvbiwgc2V0IGdvYWxzLCBhbmQgY3JlYXRlIGFjdGlvbmFibGUgcGxhbnMgdG8gZHJpdmUgYnVzaW5lc3Mgc3VjY2VzcyBhbmQgYWRhcHQgdG8gY2hhbmdpbmcgbWFya2V0IGR5bmFtaWNzLg==',
		'_tdcf_summary' => 'ZmllbGRfNjQ2YjZiZGU1NWU5OA==',
		'tdcf_bestseller' => '',
		'_tdcf_bestseller' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_time' => 'OA==',
		'_tdcf_time' => 'ZmllbGRfNjQ2YjcyYTg3YzEyMA==',
		'tdcf_diploma' => 'eWVz',
		'_tdcf_diploma' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
		'tdcf_sections' => 'NQ==',
		'_tdcf_sections' => 'ZmllbGRfNjQ2YjczMDk3YzEyMg==',
		'tdcf_language' => 'RW5nbGlzaCwgU3BhbmlzaA==',
		'_tdcf_language' => 'ZmllbGRfNjQ2YjZjMWE4YmUzOA==',
		'tdcf_resources' => 'bm8=',
		'_tdcf_resources' => 'ZmllbGRfNjQ2YjcyZjU3YzEyMQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_domains' => array( $tax_term_business_mastery_id ),
		'tdtax_price' => array( $tax_term_free_id ),
	),
));


/*  ---------------------------------------------------------------------------- 
	CLOUD TEMPLATES
*/
$template_blank_custom_post_type_id = td_demo_content::add_cloud_template( array(
	'title' => 'Blank - Custom Post Type',
	'file' => 'cpt_cloud_template.txt',
	'template_type' => 'cpt',
));

$template_404_template_coaching_pro_id = td_demo_content::add_cloud_template( array(
	'title' => '404 Template - Coaching PRO',
	'file' => '404_cloud_template.txt',
	'template_type' => '404',
));

td_demo_misc::update_global_404_template( 'tdb_template_' . $template_404_template_coaching_pro_id );


$template_tag_template_coaching_pro_id = td_demo_content::add_cloud_template( array(
	'title' => 'Tag Template -  Coaching PRO',
	'file' => 'tag_cloud_template.txt',
	'template_type' => 'tag',
));

td_demo_misc::update_global_tag_template( 'tdb_template_' . $template_tag_template_coaching_pro_id );


$template_search_template_coaching_pro_id = td_demo_content::add_cloud_template( array(
	'title' => 'Search Template - Coaching PRO',
	'file' => 'search_cloud_template.txt',
	'template_type' => 'search',
));

td_demo_misc::update_global_search_template( 'tdb_template_' . $template_search_template_coaching_pro_id );


$template_date_template_coaching_pro_id = td_demo_content::add_cloud_template( array(
	'title' => 'Date Template - Coaching PRO',
	'file' => 'date_cloud_template.txt',
	'template_type' => 'date',
));

td_demo_misc::update_global_date_template( 'tdb_template_' . $template_date_template_coaching_pro_id );


$template_author_template_coaching_pro_id = td_demo_content::add_cloud_template( array(
	'title' => 'Author Template - Coaching PRO',
	'file' => 'author_cloud_template.txt',
	'template_type' => 'author',
));

td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_coaching_pro_id );


$template_category_template_coaching_pro_demo_id = td_demo_content::add_cloud_template( array(
	'title' => 'Category Template - Coaching PRO Demo',
	'file' => 'cat_cloud_template.txt',
	'template_type' => 'category',
));

td_demo_misc::update_global_category_template( 'tdb_template_' . $template_category_template_coaching_pro_demo_id );


$template_single_post_template_coaching_pro_id = td_demo_content::add_cloud_template( array(
	'title' => 'Single Post Template - Coaching PRO',
	'file' => 'post_cloud_template.txt',
	'template_type' => 'single',
));

td_util::update_option( 'td_default_site_post_template', 'tdb_template_' . $template_single_post_template_coaching_pro_id );


$template_custom_taxonomy_template_coaching_pro_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Taxonomy Template - Coaching PRO',
	'file' => 'custom_taxonomy_template_coaching_pro_cloud_template.txt',
	'template_type' => 'cpt_tax',
));

td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_coaching_pro_id, 'tdtax_domains' );


td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_coaching_pro_id, 'tdtax_price' );


$template_custom_post_type_cpt_coaching_pro_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Post Type CPT - Coaching PRO',
	'file' => 'cpt_cloud_template.txt',
	'template_type' => 'cpt',
));

td_demo_misc::update_global_cpt_template( 'tdb_template_' . $template_custom_post_type_cpt_coaching_pro_id, 'tdcpt_courses' );


$template_header_template_main_coaching_pro_id = td_demo_content::add_cloud_template( array(
	'title' => 'Header Template Main - Coaching PRO',
	'file' => 'header_template_main_coaching_pro_cloud_template.txt',
	'template_type' => 'header',
));

td_demo_misc::update_global_header_template( 'tdb_template_' . $template_header_template_main_coaching_pro_id );


$template_footer_template_coaching_pro_id = td_demo_content::add_cloud_template( array(
	'title' => 'Footer Template - Coaching PRO',
	'file' => 'footer_template_coaching_pro_cloud_template.txt',
	'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_template_coaching_pro_id );


$template_header_template_coaching_pro_id = td_demo_content::add_cloud_template( array(
	'title' => 'Header Template - Coaching PRO',
	'file' => 'header_template_coaching_pro_cloud_template.txt',
	'template_type' => 'header',
));


/*  ----------------------------------------------------------------------------
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_taxonomy( array(
    'title' => 'Business Mastery',
    'add_to_menu_id' => $menu_td_demo_custom_menu_id,
    'taxonomy' => 'tdtax_domains',
    'tax_id' => $tax_term_business_mastery_id,
    'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_taxonomy( array(
    'title' => 'Financial Intelligence',
    'add_to_menu_id' => $menu_td_demo_custom_menu_id,
    'taxonomy' => 'tdtax_domains',
    'tax_id' => $tax_term_financial_intelligence_id,
    'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_taxonomy( array(
    'title' => 'Mindset and Mindfulness',
    'add_to_menu_id' => $menu_td_demo_custom_menu_id,
    'taxonomy' => 'tdtax_domains',
    'tax_id' => $tax_term_mindset_and_mindfulness_id,
    'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_taxonomy( array(
    'title' => 'Motivation and Goal Setting',
    'add_to_menu_id' => $menu_td_demo_custom_menu_id,
    'taxonomy' => 'tdtax_domains',
    'tax_id' => $tax_term_motivation_and_goal_setting_id,
    'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_taxonomy( array(
    'title' => 'Personal Development',
    'add_to_menu_id' => $menu_td_demo_custom_menu_id,
    'taxonomy' => 'tdtax_domains',
    'tax_id' => $tax_term_personal_development_id,
    'parent_id' => ''
));

$menu_item_5_id = td_demo_menus::add_taxonomy( array(
    'title' => 'Professional Growth',
    'add_to_menu_id' => $menu_td_demo_custom_menu_id,
    'taxonomy' => 'tdtax_domains',
    'tax_id' => $tax_term_professional_growth_id,
    'parent_id' => ''
));


/*  ----------------------------------------------------------------------------
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_mega_menu(array(
    'title' => 'Courses',
    'add_to_menu_id' => $menu_td_demo_header_menu_id,
    'page_id' => $page_megamenu_courses_id,
    'parent_id' => ''
), true);

$menu_item_1_id = td_demo_menus::add_page(array(
    'title' => 'About',
    'add_to_menu_id' => $menu_td_demo_header_menu_id,
    'page_id' => $page_about_id,
    'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_category( array(
    'title' => 'Blog',
    'add_to_menu_id' => $menu_td_demo_header_menu_id,
    'category_id' => $cat_blog_id,
    'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_page(array(
    'title' => 'Contact',
    'add_to_menu_id' => $menu_td_demo_header_menu_id,
    'page_id' => $page_contact_id,
    'parent_id' => ''
));


/*  ----------------------------------------------------------------------------
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_page(array(
    'title' => 'Pricing',
    'add_to_menu_id' => $menu_td_demo_top_menu_id,
    'page_id' => $page_pricing_id,
    'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_page(array(
    'title' => 'About',
    'add_to_menu_id' => $menu_td_demo_top_menu_id,
    'page_id' => $page_about_id,
    'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_page(array(
    'title' => 'Contact',
    'add_to_menu_id' => $menu_td_demo_top_menu_id,
    'page_id' => $page_contact_id,
    'parent_id' => ''
));


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
td_demo_content::update_meta( $template_header_template_main_coaching_pro_id, 'tdc_header_template_id', $template_header_template_main_coaching_pro_id );

td_demo_content::update_meta( $template_footer_template_coaching_pro_id, 'tdc_footer_template_id', $template_footer_template_coaching_pro_id );

td_demo_content::update_meta( $template_header_template_coaching_pro_id, 'tdc_header_template_id', $template_header_template_coaching_pro_id );

// pages metas
td_demo_content::update_meta( $page_megamenu_courses_id, 'tdc_header_template_id', 'no_header' );

td_demo_content::update_meta( $page_megamenu_courses_id, 'tdc_footer_template_id', 'no_footer' );

td_demo_content::update_meta( $page_mobile_menu_modal_id, 'tdc_header_template_id', 'no_header' );

td_demo_content::update_meta( $page_mobile_menu_modal_id, 'tdc_footer_template_id', 'no_footer' );

td_demo_content::update_meta( $page_home_id, 'tdc_header_template_id', $template_header_template_coaching_pro_id );

td_demo_content::update_meta( $page_home_id, 'tdc_footer_template_id', $template_footer_template_coaching_pro_id );
