<?php


/*  ----------------------------------------------------------------------------
	MENUS
*/
$menu_td_demo_header_menu_id = td_demo_menus::create_menu('td-demo-header-menu', '');
$menu_td_demo_header_menu_extra_id = td_demo_menus::create_menu('td-demo-header-menu-extra', '');


/*  ---------------------------------------------------------------------------- 
	EXTERNAL PLUGINS DATA IMPORT
*/

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
		'price' => '100',
		'interval' => 'year',
		'interval_count' => 1,
		'trial_days' => '0',
		'is_free' => '0',
		'is_unlimited' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"63654390addde9e";}',
		'publishing_limits' => 'a:0:{}',
		'automatic_delistings' => 'a:0:{}',
	)
);

$plan_monthly_plan_id = td_demo_subscription::add_plan( array(
		'name' => 'Monthly Plan',
		'price' => '10',
		'interval' => 'month',
		'interval_count' => 1,
		'trial_days' => '0',
		'is_free' => '0',
		'is_unlimited' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"93654390adddf7a";}',
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
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"26654390adddfb9";}',
		'publishing_limits' => 'a:0:{}',
		'automatic_delistings' => 'a:0:{}',
	)
);

$page_payment_page_id_id = td_demo_content::add_page( array(
	'title' => 'Checkout - echoverse',
	'file' => 'tds_checkout.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'payment_page_id',
	'value' => $page_payment_page_id_id,
	)
);

$page_my_account_page_id_id = td_demo_content::add_page( array(
	'title' => 'My Account - echoverse',
	'file' => 'tds_my_account.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'my_account_page_id',
	'value' => $page_my_account_page_id_id,
	)
);

$page_create_account_page_id_id = td_demo_content::add_page( array(
	'title' => 'Login/Register - echoverse',
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

$cat_nature_id = td_demo_category::add_category(array(
    'category_name' => 'Nature',
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

$cat_animals_id = td_demo_category::add_category(array(
	'category_name' => 'Animals',
	'parent_id' => $cat_nature_id,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

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

$cat_events_id = td_demo_category::add_category(array(
	'category_name' => 'Events',
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

$cat_gaming_id = td_demo_category::add_category(array(
	'category_name' => 'Gaming',
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

$cat_gardening_id = td_demo_category::add_category(array(
	'category_name' => 'Gardening',
	'parent_id' => $cat_nature_id,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_harvest_id = td_demo_category::add_category(array(
	'category_name' => 'Harvest',
	'parent_id' => $cat_nature_id,
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

$cat_news_id = td_demo_category::add_category(array(
	'category_name' => 'News',
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
$template_module_template_buttonimg_id = td_demo_content::add_cloud_template( array(
	'title' => 'EV - Button Image – Module Template',
	'file' => 'module_template_buttonimg_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '677',
));

$template_module_template_ev_round_button_id = td_demo_content::add_cloud_template( array(
	'title' => 'EV - Round Button – Module Template',
	'file' => 'module_template_ev_round_button_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '574',
));

$template_blank_module_template_2_id = td_demo_content::add_cloud_template( array(
	'title' => 'EV - Side by Side – Module Template',
	'file' => 'blank_module_template_2_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '512',
));

$template_blank_module_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'EV - Long – Module Template',
	'file' => 'blank_module_template_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '433',
));


/*  ---------------------------------------------------------------------------- 
	ATTACHMENTS
*/

/*  ---------------------------------------------------------------------------- 
	PAGES
*/
$page_tds_switching_plans_wizard_id = td_demo_content::add_page( array(
	'title' => 'Switching plans wizard',
	'file' => 'tds_switching_plans_wizard.txt',
	'demo_unique_id' => '84654390ae1f26e',
));

$page_about_us_id = td_demo_content::add_page( array(
	'title' => 'About Us',
	'file' => 'about_us.txt',
	'demo_unique_id' => '91654390ae1ff91',
));

$page_menu_popup_id = td_demo_content::add_page( array(
	'title' => 'EchoVerse Menu Popup',
	'file' => 'menu_popup.txt',
	'demo_unique_id' => '20654390ae204cb',
));

$page_homepage_id = td_demo_content::add_page( array(
	'title' => 'Homepage',
	'file' => 'homepage.txt',
	'homepage' => true,
	'demo_unique_id' => '36654390ae20d19',
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
				'tds_title' => 'Unlock to continue reading',
				'tds_message' => 'Support EchoVerse by subscribing today and unlocking unlimited content. Additionally, you get updated with the latest articles hours before they\'re published!',
				'tds_submit_btn_text' => 'Subscribe Today',
				'tds_pp_msg' => 'I consent to processing of my data according to <a href=\"#\">Terms of Use</a> & <a href=\"#\">Privacy Policy</a>.',
				'tds_locker_cf_1_name' => 'Custom field 1',
				'tds_locker_cf_2_name' => 'Custom field 2',
				'tds_locker_cf_3_name' => 'Custom field 3',
			),
	'tds_payable' => 'paid_subscription',
	'tds_paid_subs_page_id' => $page_tds_switching_plans_wizard_id,
	'tds_paid_subs_plan_ids' => [$plan_yearly_plan_id,$plan_monthly_plan_id,$plan_free_plan_id],
			'tds_locker_styles' => array(
				'tds_bg_color' => '#f3f4f6',
				'all_tds_border' => '1px',
				'all_tds_border_color' => '#dbdcdf',
				'tds_title_color' => '#0c1b1a',
				'tds_message_color' => '#0c1b1a',
				'tds_submit_btn_text_color' => '#ffffff',
				'tds_submit_btn_text_color_h' => '#0c1b1a',
				'tds_submit_btn_bg_color' => '#47cac5',
				'tds_submit_btn_bg_color_h' => '#38ebe7',
				'tds_after_btn_text_color' => '#0c1b1a',
				'tds_pp_checked_color' => '#ffffff',
				'tds_pp_check_bg' => '#47cac5',
				'tds_pp_check_bg_f' => '#47cac5',
				'tds_pp_check_border_color' => '#47cac5',
				'tds_pp_check_border_color_f' => '#47cac5',
				'tds_pp_msg_color' => '#0c1b1a',
				'tds_pp_msg_links_color' => '#47cac5',
				'tds_pp_msg_links_color_h' => '#38ebe7',
				'tds_general_font_family' => 'ev-primary-font_global',
				'tds_title_font_family' => 'ev-primary-font_global',
				'tds_title_font_size' => '26',
				'tds_title_font_line_height' => '1.2',
				'tds_title_font_weight' => '700',
				'tds_title_font_transform' => 'uppercase',
				'tds_message_font_family' => 'ev-primary-font_global',
				'tds_message_font_size' => '16',
				'tds_message_font_line_height' => '1.6',
				'tds_message_font_weight' => '400',
				'tds_submit_btn_text_font_family' => 'ev-primary-font_global',
				'tds_submit_btn_text_font_size' => '16',
				'tds_submit_btn_text_font_line_height' => '1.2',
				'tds_submit_btn_text_font_weight' => '700',
				'tds_submit_btn_text_font_transform' => 'uppercase',
				'tds_after_btn_text_font_family' => 'ev-primary-font_global',
				'tds_after_btn_text_font_size' => '16',
				'tds_after_btn_text_font_line_height' => '1.2',
				'tds_pp_msg_font_family' => 'ev-primary-font_global',
				'tds_pp_msg_font_size' => '16',
				'tds_pp_msg_font_line_height' => '1.6',
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

td_util::update_option('tds_demo_options', 'a:1:{s:5:"plans";a:3:{i:0;a:2:{s:9:"unique_id";s:15:"63654390addde9e";s:4:"name";s:11:"Yearly Plan";}i:1;a:2:{s:9:"unique_id";s:15:"93654390adddf7a";s:4:"name";s:12:"Monthly Plan";}i:2;a:2:{s:9:"unique_id";s:15:"26654390adddfb9";s:4:"name";s:9:"Free Plan";}}}');


/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTION - end phase 2
*/

/*  ---------------------------------------------------------------------------- 
	POSTS
*/
$post_td_post_essential_tips_for_harvesting_herbal_flowers_for_medicinal_purposes_id = td_demo_content::add_post( array(
	'title' => 'Essential Tips for Harvesting Herbal Flowers for Medicinal Purposes',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_harvest_id,$cat_nature_id,),
));

$post_td_post_embrace_the_power_of_nature_with_39_common_weeds_for_edible_and_medicinal_use_id = td_demo_content::add_post( array(
	'title' => 'Embrace the Power of Nature with 39 Common Weeds for Edible and Medicinal Use',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_harvest_id,$cat_nature_id,),
));

$post_td_post_getting_in_touch_with_nature_and_your_spiritual_self_merging_as_one_id = td_demo_content::add_post( array(
	'title' => 'Getting in Touch with Nature and Your Spiritual Self, Merging as One',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_harvest_id,$cat_nature_id,),
));

$post_td_post_exploring_the_world_of_medicinal_flowers_how_to_grow_and_use_them_id = td_demo_content::add_post( array(
	'title' => 'Exploring the World of Medicinal Flowers: How to Grow and Use Them',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_harvest_id,$cat_nature_id,),
));

$post_td_post_discover_25_medicinal_trees_that_are_great_to_harvest_for_homemade_medicine_id = td_demo_content::add_post( array(
	'title' => 'Discover 25 Medicinal Trees that are Great to Harvest for Homemade Medicine',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_harvest_id,$cat_nature_id,),
));

$post_td_post_unveil_the_majestic_plumage_of_a_peacock_dance_with_colors_and_wonder_id = td_demo_content::add_post( array(
	'title' => 'Unveil the Majestic Plumage of a Peacock Dance with Colors and Wonder',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_animals_id,$cat_nature_id,),
));

$post_td_post_behold_the_enchanting_hummingbird_natures_delicate_and_dazzling_jewel_id = td_demo_content::add_post( array(
	'title' => 'Behold the Enchanting Hummingbird, Nature\'s Delicate and Dazzling Jewel',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_animals_id,$cat_nature_id,),
));

$post_td_post_revamp_your_style_unleashing_the_power_of_vintage_inspired_fashion_trends_id = td_demo_content::add_post( array(
	'title' => 'Revamp Your Style: Unleashing the Power of Vintage-Inspired Fashion Trends',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_animals_id,$cat_nature_id,),
));

$post_td_post_which_parrot_captivates_you_discover_natures_colorful_chatterboxes_id = td_demo_content::add_post( array(
	'title' => 'Which Parrot Captivates You? Discover Nature\'s Colorful Chatterboxes!',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_animals_id,$cat_nature_id,),
));

$post_td_post_embrace_the_spirit_of_the_wild_encounter_the_untamed_beauty_of_a_roaming_wolf_id = td_demo_content::add_post( array(
	'title' => 'Embrace the Spirit of the Wild, Encounter the Untamed Beauty of a Roaming Wolf',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_animals_id,$cat_nature_id,),
));

$post_td_post_can_you_identify_these_rare_plants_test_your_botanical_knowledge_and_impress_id = td_demo_content::add_post( array(
	'title' => 'Can You Identify These Rare Plants? Test Your Botanical Knowledge and Impress!',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_gardening_id,$cat_nature_id,),
));

$post_td_post_delight_in_the_symphony_of_blooms_a_masterclass_in_floral_arrangements_id = td_demo_content::add_post( array(
	'title' => 'Delight in the Symphony of Blooms: A Masterclass in Floral Arrangements!',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_gardening_id,$cat_nature_id,),
));

$post_td_post_indulge_your_senses_experience_the_fragrant_joys_of_cultivating_an_herbal_garden_id = td_demo_content::add_post( array(
	'title' => 'Indulge Your Senses: Experience the Fragrant Joys of Cultivating an Herbal Garden',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_gardening_id,$cat_nature_id,),
));

$post_td_post_green_therapy_how_gardening_nurtures_well_being_and_fosters_connection_id = td_demo_content::add_post( array(
	'title' => 'Green Therapy: How Gardening Nurtures Well-being and Fosters Connection!',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_gardening_id,$cat_nature_id,),
));

$post_td_post_what_secrets_lie_in_your_soil_exploring_the_wonders_of_gardening_id = td_demo_content::add_post( array(
	'title' => 'What Secrets Lie in Your Soil? Exploring the Wonders of Gardening!',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_gardening_id,$cat_nature_id,),
));

$post_td_post_cultivate_meaningful_connections_with_friends_and_build_bridges_of_support_id = td_demo_content::add_post( array(
	'title' => 'Cultivate Meaningful Connections with Friends and Build Bridges of Support',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_all_id,$cat_lifestyle_id,),
));

$post_td_post_unwind_relax_and_create_memorable_experiences_with_beach_getaways_id = td_demo_content::add_post( array(
	'title' => 'Unwind, Relax, and Create Memorable Experiences with Beach Getaways',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_all_id,$cat_lifestyle_id,),
));

$post_td_post_dive_into_serenity_exploring_the_joys_of_swimming_and_its_health_benefits_id = td_demo_content::add_post( array(
	'title' => 'Dive into Serenity: Exploring the Joys of Swimming and Its Health Benefits',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_all_id,$cat_lifestyle_id,),
));

$post_td_post_explore_the_surprising_benefits_of_sunshine_and_embrace_the_sun_for_your_well_being_id = td_demo_content::add_post( array(
	'title' => 'Explore the Surprising Benefits of Sunshine and Embrace the Sun for Your Well-being',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_all_id,$cat_lifestyle_id,),
));

$post_td_post_let_art_and_history_be_revealed_as_you_explore_the_treasures_within_museums_id = td_demo_content::add_post( array(
	'title' => 'Let Art and History be Revealed as You Explore the Treasures within Museums',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_all_id,$cat_lifestyle_id,),
));

$post_td_post_unforgettable_melodies_a_concert_experience_that_will_rock_your_world_id = td_demo_content::add_post( array(
	'title' => 'Unforgettable Melodies: A Concert Experience That Will Rock Your World',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_all_id,$cat_events_id,),
));

$post_td_post_rhythms_and_revelry_a_guide_to_the_ultimate_festival_experience_id = td_demo_content::add_post( array(
	'title' => 'Rhythms and Revelry: A Guide to the Ultimate Festival Experience',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_all_id,$cat_events_id,),
));

$post_td_post_karaoke_extravaganza_singing_the_night_away_in_style_id = td_demo_content::add_post( array(
	'title' => 'Karaoke Extravaganza: Singing the Night Away in Style',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_all_id,$cat_events_id,),
));

$post_td_post_from_vows_to_joy_the_journey_of_a_picture_perfect_wedding_id = td_demo_content::add_post( array(
	'title' => 'From Vows to Joy: The Journey of a Picture-Perfect Wedding',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_all_id,$cat_events_id,),
));

$post_td_post_page_turner_gatherings_uniting_book_lovers_at_author_meetups_and_readings_id = td_demo_content::add_post( array(
	'title' => 'Page-Turner Gatherings: Uniting Book Lovers at Author Meetups and Readings',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_all_id,$cat_events_id,),
));

$post_td_post_must_have_gadget_enthusiasts_rejoice_essential_tech_for_every_geeks_wishlist_id = td_demo_content::add_post( array(
	'title' => 'Must-Have Gadget Enthusiasts Rejoice - Essential Tech for Every Geek\'s Wishlist',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_all_id,$cat_tech_id,),
));

$post_td_post_the_future_revolution_latest_breakthroughs_in_tech_and_gadgets_id = td_demo_content::add_post( array(
	'title' => 'The Future Revolution - Latest Breakthroughs in Tech and Gadgets',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_all_id,$cat_tech_id,),
));

$post_td_post_from_sci_fi_dreams_to_reality_coolest_tech_and_gadgets_of_the_modern_era_id = td_demo_content::add_post( array(
	'title' => 'From Sci-Fi Dreams to Reality - Coolest Tech and Gadgets of the Modern Era',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_all_id,$cat_tech_id,),
));

$post_td_post_on_the_cutting_edge_latest_tech_and_gadgets_pushing_boundaries_id = td_demo_content::add_post( array(
	'title' => 'On the Cutting Edge - Latest Tech and Gadgets Pushing Boundaries',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_all_id,$cat_tech_id,),
));

$post_td_post_tech_trends_2023_exploring_the_hottest_gadgets_and_innovations_id = td_demo_content::add_post( array(
	'title' => 'Tech Trends 2023: Exploring the Hottest Gadgets and Innovations',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_all_id,$cat_tech_id,),
));

$post_td_post_pc_gaming_unleashed_exploring_the_ultimate_gaming_experience_without_consoles_id = td_demo_content::add_post( array(
	'title' => 'PC Gaming Unleashed, Exploring the Ultimate Gaming Experience without Consoles',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_all_id,$cat_gaming_id,),
));

$post_td_post_gaming_on_the_go_the_growing_popularity_of_mobile_gaming_in_the_industry_id = td_demo_content::add_post( array(
	'title' => 'Gaming on the Go: The Growing Popularity of Mobile Gaming in the Industry',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_all_id,$cat_gaming_id,),
));

$post_td_post_the_evolution_of_gaming_exploring_the_rise_of_pc_mobile_and_console_experiences_id = td_demo_content::add_post( array(
	'title' => 'The Evolution of Gaming: Exploring the Rise of PC, Mobile, and Console Experiences',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_all_id,$cat_gaming_id,),
));

$post_td_post_cross_platform_gaming_bridging_the_gap_between_pc_mobile_and_console_players_id = td_demo_content::add_post( array(
	'title' => 'Cross-Platform Gaming: Bridging the Gap Between PC, Mobile, and Console Players',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_all_id,$cat_gaming_id,),
));

$post_td_post_console_wars_examining_the_battle_for_dominance_in_the_gaming_industry_id = td_demo_content::add_post( array(
	'title' => 'Console Wars: Examining the Battle for Dominance in the Gaming Industry',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_all_id,$cat_gaming_id,),
));

$post_td_post_the_power_of_people_worldwide_protests_shaping_the_future_of_human_rights_id = td_demo_content::add_post( array(
	'title' => 'The Power of People: Worldwide Protests Shaping the Future of Human Rights',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_all_id,$cat_news_id,),
));

$post_td_post_in_the_eye_of_the_storm_exploring_the_intersection_of_global_protests_and_press_freedom_id = td_demo_content::add_post( array(
	'title' => 'In the Eye of the Storm: Exploring the Intersection of Global Protests and Press Freedom',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_all_id,$cat_news_id,),
));

$post_td_post_unrest_unleashed_exploring_global_protests_and_their_impact_on_human_rights_id = td_demo_content::add_post( array(
	'title' => 'Unrest Unleashed: Exploring Global Protests and their Impact on Human Rights',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_all_id,$cat_news_id,),
));

$post_td_post_from_streets_to_headlines_global_protests_and_the_fight_for_human_rights_id = td_demo_content::add_post( array(
	'title' => 'From Streets to Headlines: Global Protests and the Fight for Human Rights',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_all_id,$cat_news_id,),
));

$post_td_post_the_fourth_estate_on_the_frontlines_newspapers_and_their_role_in_covering_global_protests_id = td_demo_content::add_post( array(
	'title' => 'The Fourth Estate on the Frontlines: Newspapers and their Role in Covering Global Protests',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_all_id,$cat_news_id,),
));

$post_td_post_gardening_for_the_soul_how_connecting_with_nature_nurtures_inner_growth_id = td_demo_content::add_post( array(
	'title' => 'Gardening for the Soul: How Connecting with Nature Nurtures Inner Growth',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_finding_balance_in_a_fast_paced_world_nurturing_your_mental_and_emotional_well_being_id = td_demo_content::add_post( array(
	'title' => 'Finding Balance in a Fast-Paced World: Nurturing Your Mental and Emotional Well-being',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_the_science_of_happiness_discovering_the_keys_to_a_fulfilling_life_id = td_demo_content::add_post( array(
	'title' => 'The Science of Happiness: Discovering the Keys to a Fulfilling Life',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_unleashing_creativity_embarking_on_a_journey_of_artistic_expression_id = td_demo_content::add_post( array(
	'title' => 'Unleashing Creativity: Embarking on a Journey of Artistic Expression',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_unlocking_the_secrets_of_herbal_remedies_natures_healing_power_revealed_id = td_demo_content::add_post( array(
	'title' => 'Unlocking the Secrets of Herbal Remedies: Nature\'s Healing Power Revealed',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_embracing_a_sustainable_lifestyle_simple_steps_to_reduce_your_environmental_footprint_id = td_demo_content::add_post( array(
	'title' => 'Embracing a Sustainable Lifestyle: Simple Steps to Reduce Your Environmental Footprint',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_unveiling_the_hidden_world_of_botanical_wonders_exploring_the_diversity_of_plant_life_id = td_demo_content::add_post( array(
	'title' => 'Unveiling the Hidden World of Botanical Wonders: Exploring the Diversity of Plant Life',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_rediscovering_ancient_remedies_traditional_medicine_in_the_modern_world_id = td_demo_content::add_post( array(
	'title' => 'Rediscovering Ancient Remedies: Traditional Medicine in the Modern World',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_elevating_your_interior_spaces_design_tips_for_a_stylish_and_harmonious_home_id = td_demo_content::add_post( array(
	'title' => 'Elevating Your Interior Spaces: Design Tips for a Stylish and Harmonious Home',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_the_power_of_positive_habits_cultivating_productivity_and_success_id = td_demo_content::add_post( array(
	'title' => 'The Power of Positive Habits: Cultivating Productivity and Success',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'tds_lock_content' => '1',
	'tds_locker' => $post_tds_default_wizard_locker_id,
	'categories_id_array' => array($cat_blog_id,),
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
$menu_item_0_id = td_demo_menus::add_page(array(
	'title' => 'Homepage',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_homepage_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_mega_menu( array(
	'title' => 'All',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_all_id,
	'parent_id' => ''
), true );

$menu_item_2_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Blog',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_blog_id,
	'parent_id' => ''
), true );

$menu_item_3_id = td_demo_menus::add_category( array(
	'title' => 'Nature',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_nature_id,
	'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_category( array(
	'title' => 'Animals',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_animals_id,
	'parent_id' => $menu_item_3_id
));

$menu_item_5_id = td_demo_menus::add_category( array(
	'title' => 'Gardening',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_gardening_id,
	'parent_id' => $menu_item_3_id
));

$menu_item_6_id = td_demo_menus::add_category( array(
	'title' => 'Harvest',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_harvest_id,
	'parent_id' => $menu_item_3_id
));

$menu_item_7_id = td_demo_menus::add_page(array(
	'title' => 'About Us',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_about_us_id,
	'parent_id' => ''
));



/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_link( array(
	'title' => 'Terms & Conditions',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_link( array(
	'title' => 'Privacy Policy',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_link( array(
	'title' => 'Newsletter',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_link( array(
	'title' => 'DMCA',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_page(array(
	'title' => 'About Us',
	'add_to_menu_id' => $menu_td_demo_header_menu_extra_id,
	'page_id' => $page_about_us_id,
	'parent_id' => ''
));


/*  ---------------------------------------------------------------------------- 
	CLOUD TEMPLATES
*/
$template_date_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'EchoVerse - Date Template',
	'file' => 'date_cloud_template.txt',
	'template_type' => 'date',
));

td_demo_misc::update_global_date_template( 'tdb_template_' . $template_date_template_id );


$template_author_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'EchoVerse - Author Template',
	'file' => 'author_cloud_template.txt',
	'template_type' => 'author',
));

td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_id );


$template_404_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'EchoVerse - 404 Template',
	'file' => '404_cloud_template.txt',
	'template_type' => '404',
));

td_demo_misc::update_global_404_template( 'tdb_template_' . $template_404_template_id );


$template_search_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'EchoVerse - Search Template',
	'file' => 'search_cloud_template.txt',
	'template_type' => 'search',
));

td_demo_misc::update_global_search_template( 'tdb_template_' . $template_search_template_id );


$template_tag_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'EchoVerse - Tag Template',
	'file' => 'tag_cloud_template.txt',
	'template_type' => 'tag',
));

td_demo_misc::update_global_tag_template( 'tdb_template_' . $template_tag_template_id );


$template_category_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'EchoVerse - Category Template',
	'file' => 'cat_cloud_template.txt',
	'template_type' => 'category',
));

td_demo_misc::update_global_category_template( 'tdb_template_' . $template_category_template_id );


$template_single_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'EchoVerse - Single Template',
	'file' => 'post_cloud_template.txt',
	'template_type' => 'single',
));

td_util::update_option( 'td_default_site_post_template', 'tdb_template_' . $template_single_template_id );


$template_footer_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'EchoVerse - Footer Template',
	'file' => 'footer_template_cloud_template.txt',
	'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_template_id );


$template_header_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'EchoVerse - Header Template',
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
