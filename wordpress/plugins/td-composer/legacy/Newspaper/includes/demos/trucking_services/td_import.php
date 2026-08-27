<?php 



/*  ---------------------------------------------------------------------------- 
	EXTERNAL PLUGINS DATA IMPORT
*/

/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTION - start phase 1
*/
global $wpdb;

$disable_wizard = $wpdb->get_var("SELECT value FROM tds_options WHERE name = 'disable_wizard'");
if ( empty($disable_wizard) ) {

	td_demo_subscription::add_option([
		'name' => 'td_demo_content',
		'value' => '1',
	]);

}

$page_payment_page_id_id = td_demo_content::add_page( array(
	'title' => 'Checkout - trucking_services',
	'file' => 'tds_checkout.txt',
));

td_demo_subscription::add_option([
	'name' => 'payment_page_id',
	'value' => $page_payment_page_id_id,
]);

$page_my_account_page_id_id = td_demo_content::add_page( array(
	'title' => 'My Account - trucking_services',
	'file' => 'tds_my_account.txt',
));

td_demo_subscription::add_option([
	'name' => 'my_account_page_id',
	'value' => $page_my_account_page_id_id,
]);

$page_create_account_page_id_id = td_demo_content::add_page( array(
	'title' => 'Login/Register - trucking_services',
	'file' => 'tds_login_register.txt',
));

td_demo_subscription::add_option([
	'name' => 'create_account_page_id',
	'value' => $page_create_account_page_id_id,
]);


/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTION - end phase 1
*/

/*  ---------------------------------------------------------------------------- 
	CATEGORIES
*/

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


/*  ---------------------------------------------------------------------------- 
	 CLOUD TEMPLATES(MODULES)
*/

/*  ---------------------------------------------------------------------------- 
	ATTACHMENTS
*/

/*  ---------------------------------------------------------------------------- 
	PAGES
*/
$page_contact_id = td_demo_content::add_page( array(
	'title' => 'Contact',
	'file' => 'contact.txt',
	'demo_unique_id' => '49660c0a588fa63',
));

$page_services_id = td_demo_content::add_page( array(
	'title' => 'Services',
	'file' => 'services.txt',
	'demo_unique_id' => '52660c0a58900ec',
));

$page_about_id = td_demo_content::add_page( array(
	'title' => 'About',
	'file' => 'about.txt',
	'demo_unique_id' => '84660c0a5890675',
));

$page_home_id = td_demo_content::add_page( array(
	'title' => 'Home',
	'file' => 'home.txt',
	'homepage' => true,
	'demo_unique_id' => '75660c0a5890e07',
));


/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTION - start phase 2
*/

/*  ---------------------------------------------------------------------------- 
	LOCKERS
*/
// add post meta for default locker
td_demo_content::add_locker_meta([
	'tds_locker_id' => (int) get_option( 'tds_default_locker_id' ),
	'tds_locker_meta' => [
		'tds_locker_settings' => [
			'tds_title' => 'This Content Is Only For Subscribers',
			'tds_message' => 'Please subscribe to unlock this content. Enter your email to get access.',
			'tds_input_placeholder' => 'Please enter your email address.',
			'tds_submit_btn_text' => 'Subscribe to unlock',
			'tds_after_btn_text' => 'Your email address is 100% safe from spam!',
			'tds_pp_msg' => 'I consent to processing of my data according to <a href=\"#\">Terms of Use</a> & <a href=\"#\">Privacy Policy</a>',
		],
	]
]);


/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTION - end phase 2
*/

/*  ---------------------------------------------------------------------------- 
	POSTS
*/
$post_safety_first_how_our_advanced_training_programs_are_setting_new_standards_id = td_demo_content::add_post( array(
	'title' => 'Safety First: How Our Advanced Training Programs Are Setting New Standards',
	'file' => 'post_default.txt',
	'tds_locker' => '6',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_news_id,),
));

$post_from_local_to_national_scaling_your_distribution_with_strategic_trucking_id = td_demo_content::add_post( array(
	'title' => 'From Local to National: Scaling Your Distribution with Strategic Trucking',
	'file' => 'post_default.txt',
	'tds_locker' => '6',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_news_id,),
));

$post_behind_the_scenes_the_technology_driving_todays_trucking_efficiency_id = td_demo_content::add_post( array(
	'title' => 'Behind the Scenes: The Technology Driving Today’s Trucking Efficiency',
	'file' => 'post_default.txt',
	'tds_locker' => '6',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_news_id,),
));

$post_the_future_of_trucking_trends_shaping_our_industry_in_2024_and_beyond_id = td_demo_content::add_post( array(
	'title' => 'The Future of Trucking: Trends Shaping Our Industry in 2024 and Beyond',
	'file' => 'post_default.txt',
	'tds_locker' => '6',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_news_id,),
));

$post_the_ultimate_guide_to_choosing_the_right_logistics_partner_for_you_id = td_demo_content::add_post( array(
	'title' => 'The Ultimate Guide to Choosing the Right Logistics Partner for You',
	'file' => 'post_default.txt',
	'tds_locker' => '6',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_news_id,),
));

$post_sustainability_on_the_move_how_green_logistics_is_changing_the_game_id = td_demo_content::add_post( array(
	'title' => 'Sustainability on the Move: How Green Logistics Is Changing the Game',
	'file' => 'post_default.txt',
	'tds_locker' => '6',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_news_id,),
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
$menu_td_demo_footer_menu_id = td_demo_menus::create_menu('td-demo-footer-menu', ''); 


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_link( array(
	'title' => 'Privacy policy',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_link( array(
	'title' => 'Terms & conditions',
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

$menu_td_demo_header_menu_id = td_demo_menus::create_menu('td-demo-header-menu', ''); 


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_link( array(
	'title' => 'Home',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'url' => 'http://localhost/wp_011_trucking_services/',
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_page(array(
	'title' => 'About',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_about_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_page(array(
	'title' => 'Services',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_services_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_category( array(
	'title' => 'News',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_news_id,
	'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_page(array(
	'title' => 'Contact',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_contact_id,
	'parent_id' => ''
));


/*  ---------------------------------------------------------------------------- 
	CLOUD TEMPLATES
*/
$template_tag_template_trucking_id = td_demo_content::add_cloud_template( array(
	'title' => 'Tag Template - Trucking',
	'file' => 'tag_cloud_template.txt',
	'template_type' => 'tag',
));

td_demo_misc::update_global_tag_template( 'tdb_template_' . $template_tag_template_trucking_id );


$template_date_template_trucking_id = td_demo_content::add_cloud_template( array(
	'title' => 'Date Template - Trucking',
	'file' => 'date_cloud_template.txt',
	'template_type' => 'date',
));

td_demo_misc::update_global_date_template( 'tdb_template_' . $template_date_template_trucking_id );


$template_search_template_trucking_id = td_demo_content::add_cloud_template( array(
	'title' => 'Search Template - Trucking',
	'file' => 'search_cloud_template.txt',
	'template_type' => 'search',
));

td_demo_misc::update_global_search_template( 'tdb_template_' . $template_search_template_trucking_id );


$template_author_template_trucking_id = td_demo_content::add_cloud_template( array(
	'title' => 'Author Template - Trucking',
	'file' => 'author_cloud_template.txt',
	'template_type' => 'author',
));

td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_trucking_id );


$template_404_template_trucking_id = td_demo_content::add_cloud_template( array(
	'title' => '404 Template - Trucking',
	'file' => '404_cloud_template.txt',
	'template_type' => '404',
));

$template_category_template_trucking_id = td_demo_content::add_cloud_template( array(
	'title' => 'Category Template - Trucking',
	'file' => 'cat_cloud_template.txt',
	'template_type' => 'category',
));

td_demo_misc::update_global_category_template( 'tdb_template_' . $template_category_template_trucking_id );


$template_single_post_template_trucking_id = td_demo_content::add_cloud_template( array(
	'title' => 'Single Post Template - Trucking',
	'file' => 'post_cloud_template.txt',
	'template_type' => 'single',
));

td_util::update_option( 'td_default_site_post_template', 'tdb_template_' . $template_single_post_template_trucking_id );


$template_footer_trucking_id = td_demo_content::add_cloud_template( array(
	'title' => 'Footer - Trucking',
	'file' => 'footer_trucking_cloud_template.txt',
	'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_trucking_id );


$template_header_template_main_trucking_id = td_demo_content::add_cloud_template( array(
	'title' => 'Header Template Main - Trucking',
	'file' => 'header_template_main_trucking_cloud_template.txt',
	'template_type' => 'header',
));

td_demo_misc::update_global_header_template( 'tdb_template_' . $template_header_template_main_trucking_id );


$template_header_template_trucking_id = td_demo_content::add_cloud_template( array(
	'title' => 'Header Template - Trucking',
	'file' => 'header_template_trucking_cloud_template.txt',
	'template_type' => 'header',
));


/*  ---------------------------------------------------------------------------- 
	GENERAL SETTINGS
*/
td_demo_misc::update_background('', false);

td_demo_misc::update_background_mobile('tdx_pic_2');

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
td_demo_content::update_meta( $template_single_post_template_trucking_id, 'tdc_header_template_id', $template_header_template_trucking_id );

td_demo_content::update_meta( $template_footer_trucking_id, 'tdc_footer_template_id', $template_footer_trucking_id );

td_demo_content::update_meta( $template_header_template_main_trucking_id, 'tdc_header_template_id', $template_header_template_main_trucking_id );

td_demo_content::update_meta( $template_header_template_trucking_id, 'tdc_header_template_id', $template_header_template_trucking_id );

// pages metas
td_demo_content::update_meta( $page_contact_id, 'tdc_header_template_id', $template_header_template_trucking_id );

td_demo_content::update_meta( $page_about_id, 'tdc_header_template_id', $template_header_template_trucking_id );

td_demo_content::update_meta( $page_home_id, 'tdc_header_template_id', $template_header_template_trucking_id );

td_demo_content::update_meta( $page_home_id, 'tdc_footer_template_id', $template_footer_trucking_id );
