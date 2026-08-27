<?php 



/*  ---------------------------------------------------------------------------- 
	EXTERNAL PLUGINS DATA IMPORT
*/


$menu_td_demo_header_menu_id = td_demo_menus::create_menu('td-demo-header-menu', '');
$menu_td_demo_top_menu_id = td_demo_menus::create_menu('td-demo-top-menu', '');
$menu_td_demo_custom_menu_id = td_demo_menus::create_menu('td-demo-custom-menu', '');

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
		'price' => '4.99',
		'interval' => 'month',
		'interval_count' => 1,
		'trial_days' => '0',
		'is_free' => '0',
		'is_unlimited' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"876548ebf9c344a";}',
		'publishing_limits' => 'a:0:{}',
		'automatic_delistings' => 'a:0:{}',
	)
);

$plan_yearly_plan_id = td_demo_subscription::add_plan( array(
		'name' => 'Yearly Plan',
		'price' => '49.99',
		'interval' => 'year',
		'interval_count' => 1,
		'trial_days' => '0',
		'is_free' => '0',
		'is_unlimited' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"946548ebf9c3525";}',
		'publishing_limits' => 'a:0:{}',
		'automatic_delistings' => 'a:0:{}',
	)
);

$plan_free_plan_id = td_demo_subscription::add_plan( array(
		'name' => 'Free Plan',
		'price' => '',
		'interval' => 'month',
		'interval_count' => 1,
		'trial_days' => '0',
		'is_free' => '1',
		'is_unlimited' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"256548ebf9c35b2";}',
		'publishing_limits' => 'a:0:{}',
		'automatic_delistings' => 'a:0:{}',
	)
);

$page_payment_page_id_id = td_demo_content::add_page( array(
	'title' => 'Checkout - urban_observer',
	'file' => 'tds_checkout.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'payment_page_id',
	'value' => $page_payment_page_id_id,
	)
);

$page_my_account_page_id_id = td_demo_content::add_page( array(
	'title' => 'My Account - urban_observer',
	'file' => 'tds_my_account.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'my_account_page_id',
	'value' => $page_my_account_page_id_id,
	)
);

$page_create_account_page_id_id = td_demo_content::add_page( array(
	'title' => 'Login/Register - urban_observer',
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

$cat_drama_id = td_demo_category::add_category(array(
	'category_name' => 'Drama',
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

$cat_health_id = td_demo_category::add_category(array(
	'category_name' => 'Health',
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

$cat_movies_id = td_demo_category::add_category(array(
	'category_name' => 'Movies',
	'parent_id' => $cat_entertainment_id,
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
	'parent_id' => $cat_entertainment_id,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_scandals_id = td_demo_category::add_category(array(
	'category_name' => 'Scandals',
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

$cat_technology_id = td_demo_category::add_category(array(
	'category_name' => 'Technology',
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

$cat_tv_shows_id = td_demo_category::add_category(array(
	'category_name' => 'TV Shows',
	'parent_id' => $cat_entertainment_id,
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


/*  ---------------------------------------------------------------------------- 
	 CLOUD TEMPLATES(MODULES)
*/

/*  ---------------------------------------------------------------------------- 
	ATTACHMENTS
*/

/*  ---------------------------------------------------------------------------- 
	PAGES
*/
$page_tds_switching_plans_wizard_id = td_demo_content::add_page( array(
	'title' => 'Switching plans wizard',
	'file' => 'tds_switching_plans_wizard.txt',
	'demo_unique_id' => '686548ebfa41409',
));

$page_modal_mobile_menu_id = td_demo_content::add_page( array(
	'title' => 'Modal Mobile Menu',
	'file' => 'modal_mobile_menu.txt',
	'demo_unique_id' => '06548ebfa41cad',
));

$page_modal_newsletter_id = td_demo_content::add_page( array(
	'title' => 'Modal Newsletter',
	'file' => 'modal_newsletter.txt',
	'demo_unique_id' => '106548ebfa424a4',
));

$page_modal_desktop_id = td_demo_content::add_page( array(
	'title' => 'Modal Desktop',
	'file' => 'modal_desktop.txt',
	'demo_unique_id' => '386548ebfa42fa3',
));

$page_homepage_publication_pro_id = td_demo_content::add_page( array(
	'title' => 'Home',
	'file' => 'homepage.txt',
	'homepage' => true,
	'demo_unique_id' => '226548ebfa43f59',
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
				'tds_input_placeholder' => '',
				'tds_submit_btn_text' => 'Subscribe to unlock',
				'tds_after_btn_text' => '',
				'tds_pp_msg' => 'I consent to processing of my data according to <a href=\"#\">Terms of Use</a> & <a href=\"#\">Privacy Policy</a>',
			),
	'tds_payable' => 'paid_subscription',
	'tds_paid_subs_plan_ids' => [$plan_free_plan_id,$plan_monthly_plan_id,$plan_yearly_plan_id],
	'tds_paid_subs_page_id' => $page_tds_switching_plans_wizard_id,
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

td_util::update_option('tds_demo_options', 'a:1:{s:5:"plans";a:3:{i:0;a:2:{s:9:"unique_id";s:15:"876548ebf9c344a";s:4:"name";s:12:"Monthly Plan";}i:1;a:2:{s:9:"unique_id";s:15:"946548ebf9c3525";s:4:"name";s:11:"Yearly Plan";}i:2;a:2:{s:9:"unique_id";s:15:"256548ebf9c35b2";s:4:"name";s:9:"Free Plan";}}}');


/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTION - end phase 2
*/

/*  ---------------------------------------------------------------------------- 
	POSTS
*/

$post_td_post_debunking_movie_myths_what_hollywood_gets_wrong_id = td_demo_content::add_post( array(
    'title' => 'Debunking Movie Myths: What Hollywood Gets Wrong',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_1',
    'tds_locker' => '6',
    'categories_id_array' => array($cat_celebrity_id,$cat_drama_id,$cat_health_id,$cat_lifestyle_id,$cat_movies_id,$cat_music_id,$cat_scandals_id,$cat_technology_id,$cat_tv_shows_id,),
));

$post_td_post_secrets_to_award_winning_performances_id = td_demo_content::add_post( array(
    'title' => 'Secrets to Award-Winning Performances',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_2',
    'tds_locker' => '6',
    'categories_id_array' => array($cat_celebrity_id,$cat_featured_id,$cat_health_id,$cat_lifestyle_id,$cat_movies_id,$cat_music_id,$cat_scandals_id,$cat_technology_id,$cat_tv_shows_id,),
));

$post_td_post_an_in_depth_look_at_animation_studios_id = td_demo_content::add_post( array(
    'title' => 'An In-Depth Look at Animation Studios',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_3',
    'tds_locker' => '6',
    'categories_id_array' => array($cat_celebrity_id,$cat_drama_id,$cat_health_id,$cat_lifestyle_id,$cat_movies_id,$cat_music_id,$cat_scandals_id,$cat_technology_id,$cat_tv_shows_id,),
));

$post_td_post_immersive_cinema_the_new_age_of_movie_going_id = td_demo_content::add_post( array(
    'title' => 'Immersive Cinema: The New Age of Movie-Going',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_4',
    'tds_locker' => '6',
    'categories_id_array' => array($cat_celebrity_id,$cat_drama_id,$cat_health_id,$cat_lifestyle_id,$cat_movies_id,$cat_music_id,$cat_scandals_id,$cat_technology_id,$cat_tv_shows_id,),
));

$post_td_post_why_film_scores_are_more_important_than_you_think_id = td_demo_content::add_post( array(
    'title' => 'Why Film Scores are More Important Than You Think',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_5',
    'tds_locker' => '6',
    'categories_id_array' => array($cat_celebrity_id,$cat_drama_id,$cat_health_id,$cat_lifestyle_id,$cat_movies_id,$cat_music_id,$cat_scandals_id,$cat_technology_id,$cat_tv_shows_id,),
));

$post_td_post_from_script_to_screen_the_life_of_a_screenplay_id = td_demo_content::add_post( array(
    'title' => 'From Script to Screen: The Life of a Screenplay',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_6',
    'tds_locker' => '6',
    'categories_id_array' => array($cat_celebrity_id,$cat_drama_id,$cat_health_id,$cat_lifestyle_id,$cat_movies_id,$cat_music_id,$cat_scandals_id,$cat_technology_id,$cat_tv_shows_id,),
));

$post_td_post_5_directors_who_changed_the_face_of_cinema_id = td_demo_content::add_post( array(
    'title' => '5 Directors Who Changed the Face of Cinema',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_7',
    'tds_locker' => '6',
    'categories_id_array' => array($cat_celebrity_id,$cat_drama_id,$cat_health_id,$cat_lifestyle_id,$cat_movies_id,$cat_music_id,$cat_scandals_id,$cat_technology_id,$cat_tv_shows_id,),
));

$post_td_post_the_evolution_of_movie_theaters_in_a_digital_age_id = td_demo_content::add_post( array(
    'title' => 'The Evolution of Movie Theaters in a Digital Age',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_8',
    'tds_locker' => '6',
    'categories_id_array' => array($cat_celebrity_id,$cat_drama_id,$cat_health_id,$cat_lifestyle_id,$cat_movies_id,$cat_music_id,$cat_scandals_id,$cat_technology_id,$cat_tv_shows_id,),
));

$post_td_post_how_indie_films_are_shaking_up_hollywood_id = td_demo_content::add_post( array(
    'title' => 'How Indie Films Are Shaking Up Hollywood',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_9',
    'tds_locker' => '6',
    'categories_id_array' => array($cat_celebrity_id,$cat_drama_id,$cat_health_id,$cat_lifestyle_id,$cat_movies_id,$cat_music_id,$cat_scandals_id,$cat_technology_id,$cat_tv_shows_id,),
));

$post_td_post_unveiling_the_top_10_blockbusters_of_the_year_id = td_demo_content::add_post( array(
    'title' => 'Unveiling the Top 10 Blockbusters of the Year',
    'file' => 'post_default.txt',
    'featured_image_td_id' => 'td_pic_10',
    'tds_lock_content' => '1',
    'tds_locker' => $post_tds_default_wizard_locker_id,
    'categories_id_array' => array($cat_celebrity_id,$cat_drama_id,$cat_health_id,$cat_lifestyle_id,$cat_movies_id,$cat_music_id,$cat_scandals_id,$cat_technology_id,$cat_tv_shows_id,),
));

$post_td_post_creative_writing_as_a_therapeutic_tool_id = td_demo_content::add_post( array(
	'title' => 'Creative Writing as a Therapeutic Tool',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_lifestyle_id,),
));

$post_traveling_the_world_on_a_small_budget_id = td_demo_content::add_post( array(
	'title' => 'Traveling the World on a Small Budget',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'tds_locker' => '6',
	'categories_id_array' => array($cat_lifestyle_id,),
));

$post_td_post_the_benefits_of_outdoor_activities_id = td_demo_content::add_post( array(
	'title' => 'The Benefits of Outdoor Activities',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'tds_locker' => '6',
	'categories_id_array' => array($cat_lifestyle_id,),
));

$post_td_post_time_management_skills_for_success_id = td_demo_content::add_post( array(
	'title' => 'Time Management Skills for Success',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'tds_locker' => '6',
	'categories_id_array' => array($cat_lifestyle_id,),
));

$post_td_post_home_automation_for_beginners_id = td_demo_content::add_post( array(
	'title' => 'Home Automation for Beginners',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_lifestyle_id,),
));

$post_td_post_the_art_of_mindfulness_and_health_id = td_demo_content::add_post( array(
	'title' => 'The Art of Mindfulness and Health',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_lifestyle_id,),
));

$post_td_post_the_science_of_sleep_and_productivity_id = td_demo_content::add_post( array(
	'title' => 'The Science of Sleep and Productivity',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_health_id,),
));

$post_td_post_self_care_tips_for_stressful_times_id = td_demo_content::add_post( array(
	'title' => 'Self-Care Tips for Stressful Times',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'tds_locker' => '6',
	'categories_id_array' => array($cat_health_id,),
));

$post_td_post_the_psychological_effects_of_social_media_id = td_demo_content::add_post( array(
	'title' => 'The Psychological Effects of Social Media',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_health_id,),
));

$post_td_post_understanding_different_diet_trends_id = td_demo_content::add_post( array(
	'title' => 'Understanding Different Diet Trends',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_health_id,),
));

$post_td_post_youth_mental_health_in_the_digital_age_id = td_demo_content::add_post( array(
	'title' => 'Youth Mental Health in the Digital Age',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_health_id,),
));

$post_td_post_alternative_medicine_pros_and_cons_id = td_demo_content::add_post( array(
	'title' => 'Alternative Medicine: Pros and Cons',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_health_id,),
));

$post_td_post_internet_privacy_know_your_rights_id = td_demo_content::add_post( array(
	'title' => 'Internet Privacy: Know Your Rights',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'tds_locker' => '6',
	'categories_id_array' => array($cat_technology_id,),
));

$post_td_post_augmented_reality_in_everyday_life_id = td_demo_content::add_post( array(
	'title' => 'Augmented Reality in Everyday Life',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_technology_id,),
));

$post_td_post_the_revolution_of_electric_airplanes_id = td_demo_content::add_post( array(
	'title' => 'The Revolution of Electric Airplanes',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'tds_locker' => '6',
	'categories_id_array' => array($cat_technology_id,),
));

$post_td_post_cybersecurity_protecting_yourself_online_id = td_demo_content::add_post( array(
	'title' => 'Cybersecurity: Protecting Yourself Online',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_technology_id,),
));

$post_td_post_the_environmental_impact_of_e_waste_id = td_demo_content::add_post( array(
	'title' => 'The Environmental Impact of E-Waste',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_technology_id,),
));

$post_td_post_the_role_of_big_data_in_modern_businesses_id = td_demo_content::add_post( array(
	'title' => 'The Role of Big Data in Modern Businesses',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_technology_id,),
));

$post_td_post_are_reality_tv_relationships_doomed_to_fail_id = td_demo_content::add_post( array(
	'title' => 'Are Relationships from TV Doomed to Fail?',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'tds_locker' => '6',
	'categories_id_array' => array($cat_drama_id,),
));

$post_td_post_when_reality_tv_blurs_the_line_with_real_life_id = td_demo_content::add_post( array(
	'title' => 'When Reality TV Blurs the Line with Real Life',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'tds_locker' => '6',
	'categories_id_array' => array($cat_drama_id,),
));

$post_td_post_reality_shows_accused_of_exploitation_id = td_demo_content::add_post( array(
	'title' => 'Reality Shows Accused of Exploitation',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_drama_id,),
));

$post_td_post_the_ethical_dilemmas_of_reality_tv_production_id = td_demo_content::add_post( array(
	'title' => 'The Ethical Dilemmas of Reality TV Production',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_drama_id,),
));

$post_reality_stars_who_created_a_huge_empire_id = td_demo_content::add_post( array(
	'title' => 'Reality Stars Who Created a Huge Empire',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'tds_locker' => '6',
	'categories_id_array' => array($cat_drama_id,),
));

$post_td_post_why_viewers_cant_resist_reality_tv_villains_id = td_demo_content::add_post( array(
	'title' => 'Why Viewers Can’t Resist Reality TV Villains',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'tds_locker' => '6',
	'categories_id_array' => array($cat_drama_id,),
));

$post_td_post_the_fall_from_grace_socialites_who_lost_it_all_id = td_demo_content::add_post( array(
	'title' => 'The Fall from Grace: Socialites Who Lost It All',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_scandals_id,),
));

$post_10_scandalous_love_triangles_captivating_the_public_id = td_demo_content::add_post( array(
	'title' => '10 Scandalous Love Triangles Captivating the Public',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'tds_locker' => '6',
	'categories_id_array' => array($cat_scandals_id,),
));

$post_the_excessive_lifestyles_of_calis_tech_moguls_id = td_demo_content::add_post( array(
	'title' => 'The Excessive Lifestyles of Cali\'s Tech Moguls',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'tds_locker' => '6',
	'categories_id_array' => array($cat_scandals_id,),
));

$post_td_post_divorce_settlements_that_made_headlines_id = td_demo_content::add_post( array(
	'title' => 'Divorce Settlements that Made Headlines',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_scandals_id,),
));

$post_td_post_high_society_parties_where_everything_goes_id = td_demo_content::add_post( array(
	'title' => 'High Society Parties: Where Everything Goes',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_scandals_id,),
));

$post_td_post_secret_lives_of_billionaire_heirs_and_heiresses_id = td_demo_content::add_post( array(
	'title' => 'Secret Lives of Billionaire Heirs and Heiresses',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'tds_locker' => '6',
	'categories_id_array' => array($cat_scandals_id,),
));

$post_td_post_how_social_media_ended_careers_a_retrospective_id = td_demo_content::add_post( array(
	'title' => 'How Social Media Ended Careers: A Retrospective',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'tds_locker' => '6',
	'categories_id_array' => array($cat_celebrity_id,),
));

$post_td_post_infamous_celebrity_meltdowns_caught_on_camera_id = td_demo_content::add_post( array(
	'title' => 'Infamous Celebrity Meltdowns Caught on Camera',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_celebrity_id,),
));

$post_td_post_bankrupt_stars_where_did_all_the_money_go_id = td_demo_content::add_post( array(
	'title' => 'Bankrupt Stars: Where Did All the Money Go?',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_celebrity_id,),
));

$post_td_post_the_price_of_fame_celebrities_and_substance_abuse_id = td_demo_content::add_post( array(
	'title' => 'The Price of Fame: Celebrities and Substance Abuse',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_celebrity_id,),
));

$post_td_post_exposing_fake_celebrity_philanthropy_id = td_demo_content::add_post( array(
	'title' => 'Exposing Fake Celebrity Philanthropy',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_celebrity_id,),
));

$post_td_post_the_lavish_lives_of_celebrity_kids_exposed_id = td_demo_content::add_post( array(
	'title' => 'The Lavish Lives of Celebrity Kids Exposed',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_celebrity_id,),
));

$post_td_post_music_therapy_how_it_works_and_benefits_id = td_demo_content::add_post( array(
	'title' => 'Music Therapy: How It Works and Benefits',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_music_id,),
));

$post_td_post_the_power_of_music_in_advertising_id = td_demo_content::add_post( array(
	'title' => 'The Power of Music in Advertising',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_music_id,),
));

$post_td_post_understanding_music_royalties_id = td_demo_content::add_post( array(
	'title' => 'Understanding Music Royalties',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_music_id,),
));

$post_td_post_instrumental_music_and_its_importance_id = td_demo_content::add_post( array(
	'title' => 'Instrumental Music and Its Importance',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_music_id,),
));

$post_td_post_world_music_a_global_journey_id = td_demo_content::add_post( array(
	'title' => 'World Music: A Global Journey',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_music_id,),
));

$post_td_post_live_concerts_the_experience_of_a_lifetime_id = td_demo_content::add_post( array(
	'title' => 'Live Concerts: The Experience of a Lifetime',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_music_id,),
));

$post_td_post_tv_shows_that_accurately_depict_mental_health_id = td_demo_content::add_post( array(
	'title' => 'TV Shows That Accurately Depict Mental Health',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_tv_shows_id,),
));

$post_td_post_writing_for_television_an_insiders_guide_id = td_demo_content::add_post( array(
	'title' => 'Writing for Television: An Insider\'s Guide',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_tv_shows_id,),
));

$post_td_post_streaming_wars_which_platform_offers_more_id = td_demo_content::add_post( array(
	'title' => 'Streaming Wars: Which Platform Offers More',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_tv_shows_id,),
));

$post_td_post_the_art_of_tv_show_revivals_hits_and_misses_id = td_demo_content::add_post( array(
	'title' => 'The Art of TV Show Revivals: Hits and Misses',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_tv_shows_id,),
));

$post_td_post_why_tv_series_finales_are_so_hard_to_get_right_id = td_demo_content::add_post( array(
	'title' => 'Why TV Series Finales Are So Hard to Get Right',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_tv_shows_id,),
));

$post_td_post_5_iconic_tv_show_sets_you_can_actually_visit_id = td_demo_content::add_post( array(
	'title' => '5 Iconic TV Show Sets You Can Actually Visit',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_tv_shows_id,),
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
$menu_item_0_id = td_demo_menus::add_link( array(
	'title' => 'About',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_link( array(
	'title' => 'Contact',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_link( array(
	'title' => 'Privacy Policy',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_link( array(
	'title' => 'Newsletter',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'url' => '#',
	'parent_id' => ''
));



/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Entertainment',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_entertainment_id,
	'parent_id' => ''
), true );

$menu_item_1_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Celebrity',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_celebrity_id,
	'parent_id' => ''
), true );

$menu_item_2_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Scandals',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_scandals_id,
	'parent_id' => ''
), true );

$menu_item_3_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Drama',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_drama_id,
	'parent_id' => ''
), true );

$menu_item_4_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Lifestyle',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_lifestyle_id,
	'parent_id' => ''
), true );

$menu_item_5_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Technology',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_technology_id,
	'parent_id' => ''
), true );

$menu_item_6_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Health',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_health_id,
	'parent_id' => ''
), true );


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_link( array(
	'title' => 'About',
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


/*  ---------------------------------------------------------------------------- 
	CLOUD TEMPLATES
*/
$template_tag_template_urban_observer_id = td_demo_content::add_cloud_template( array(
	'title' => 'Tag Template - Urban Observer',
	'file' => 'tag_cloud_template.txt',
	'template_type' => 'tag',
));

td_demo_misc::update_global_tag_template( 'tdb_template_' . $template_tag_template_urban_observer_id );


$template_search_template_urban_observer_id = td_demo_content::add_cloud_template( array(
	'title' => 'Search Template - Urban Observer',
	'file' => 'search_cloud_template.txt',
	'template_type' => 'search',
));

td_demo_misc::update_global_search_template( 'tdb_template_' . $template_search_template_urban_observer_id );


$template_date_template_urban_observer_id = td_demo_content::add_cloud_template( array(
	'title' => 'Date Template - Urban Observer',
	'file' => 'date_cloud_template.txt',
	'template_type' => 'date',
));

td_demo_misc::update_global_date_template( 'tdb_template_' . $template_date_template_urban_observer_id );


$template_author_template_urban_observer_id = td_demo_content::add_cloud_template( array(
	'title' => 'Author Template - Urban Observer',
	'file' => 'author_cloud_template.txt',
	'template_type' => 'author',
));

td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_urban_observer_id );


$template_category_template_urban_observer_id = td_demo_content::add_cloud_template( array(
	'title' => 'Category Template - Urban Observer',
	'file' => 'cat_cloud_template.txt',
	'template_type' => 'category',
));

td_demo_misc::update_global_category_template( 'tdb_template_' . $template_category_template_urban_observer_id );


$template_single_post_template_urban_observer_id = td_demo_content::add_cloud_template( array(
	'title' => 'Single Post Template - Urban Observer',
	'file' => 'post_cloud_template.txt',
	'template_type' => 'single',
));

td_util::update_option( 'td_default_site_post_template', 'tdb_template_' . $template_single_post_template_urban_observer_id );


$template_404_template_urban_observer_id = td_demo_content::add_cloud_template( array(
	'title' => '404 Template - Urban Observer',
	'file' => '404_cloud_template.txt',
	'template_type' => '404',
));

td_demo_misc::update_global_404_template( 'tdb_template_' . $template_404_template_urban_observer_id );


$template_footer_urban_observer_id = td_demo_content::add_cloud_template( array(
	'title' => 'Footer - Urban Observer',
	'file' => 'footer_urban_observer_cloud_template.txt',
	'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_urban_observer_id );


$template_header_template_urban_observer_id = td_demo_content::add_cloud_template( array(
	'title' => 'Header Template - Urban Observer',
	'file' => 'header_template_urban_observer_cloud_template.txt',
	'template_type' => 'header',
));

td_demo_misc::update_global_header_template( 'tdb_template_' . $template_header_template_urban_observer_id );



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
td_demo_content::update_meta( $template_footer_urban_observer_id, 'tdc_footer_template_id', $template_footer_urban_observer_id );

td_demo_content::update_meta( $template_header_template_urban_observer_id, 'tdc_header_template_id', $template_header_template_urban_observer_id );

// pages metas
td_demo_content::update_meta( $page_modal_mobile_menu_id, 'tdc_header_template_id', 'no_header' );

td_demo_content::update_meta( $page_modal_mobile_menu_id, 'tdc_footer_template_id', 'no_footer' );

td_demo_content::update_meta( $page_modal_newsletter_id, 'tdc_header_template_id', 'no_header' );

td_demo_content::update_meta( $page_modal_desktop_id, 'tdc_header_template_id', 'no_header' );

td_demo_content::update_meta( $page_modal_desktop_id, 'tdc_footer_template_id', 'no_footer' );

td_demo_content::update_meta( $page_homepage_publication_pro_id, 'tdc_footer_template_id', $template_footer_urban_observer_id );
