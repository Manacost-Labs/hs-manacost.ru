<?php



/*  ----------------------------------------------------------------------------
	EXTERNAL PLUGINS DATA IMPORT
*/
/* -- ACF -- */
// Field groups
$field_group_case_studies_fields = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/momentum_pro/data/acf/group_63f5e077234e9.json' );
$field_group_custom_services_tax = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/momentum_pro/data/acf/group_63f865ea61284.json' );

// Post types
$post_type_case_studies = td_demo_data::acf_import_post_type( 'https://cloud.tagdiv.com/demos/Newspaper/momentum_pro/data/acf/post_type_6511455553873.json' );

// Taxonomies
$taxonomy_services = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/momentum_pro/data/acf/taxonomy_651145d76beeb.json' );



/*  ----------------------------------------------------------------------------
	SUBSCRIPTION - start phase 1
*/
global $wpdb;
$disable_wizard = $wpdb->get_var( "SELECT value FROM tds_options WHERE name = 'disable_wizard'");
if ( empty($disable_wizard) ) {

td_demo_subscription::add_option( array(
		'name' => 'td_demo_content',
		'value' => '1',
	)
);

}

$page_payment_page_id_id = td_demo_content::add_page( array(
	'title' => 'Checkout - momentum_pro',
	'file' => 'tds_checkout.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'payment_page_id',
	'value' => $page_payment_page_id_id,
	)
);

$page_my_account_page_id_id = td_demo_content::add_page( array(
	'title' => 'My Account - momentum_pro',
	'file' => 'tds_my_account.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'my_account_page_id',
	'value' => $page_my_account_page_id_id,
	)
);

$page_create_account_page_id_id = td_demo_content::add_page( array(
	'title' => 'Login/Register - momentum_pro',
	'file' => 'tds_login_register.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'create_account_page_id',
	'value' => $page_create_account_page_id_id,
	)
);


/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTION - end phase 1
*/


/*  ----------------------------------------------------------------------------
	MENUS
*/
$menu_td_demo_custom_menu_id = td_demo_menus::create_menu('td-demo-custom-menu', '');

$menu_td_demo_footer_menu_id = td_demo_menus::create_menu('td-demo-footer-menu', '');

$menu_td_demo_header_menu_id = td_demo_menus::create_menu('td-demo-header-menu', '');

$menu_td_demo_top_menu_id = td_demo_menus::create_menu('td-demo-top-menu', '');


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
$template_module_template_case_studies_id = td_demo_content::add_cloud_template( array(
	'title' => 'Module Template - Case Studies',
	'file' => 'module_template_case_studies_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '653',
));


/*  ---------------------------------------------------------------------------- 
	ATTACHMENTS
*/

/*  ---------------------------------------------------------------------------- 
	PAGES
*/
$page_mobile_modal_id = td_demo_content::add_page( array(
	'title' => 'Mobile Modal',
	'file' => 'mobile_modal.txt',
	'demo_unique_id' => '47645cd8f041c23',
));

$page_megamenu_services_id = td_demo_content::add_page( array(
	'title' => 'Megamenu - Services',
	'file' => 'megamenu_services.txt',
	'demo_unique_id' => '28645cd8f042526',
));

$page_about_page_momentum_pro_id = td_demo_content::add_page( array(
	'title' => 'About Page - Momentum PRO',
	'file' => 'about_page_momentum_pro.txt',
	'demo_unique_id' => '93645cd8f043b9a',
));

$page_contact_id = td_demo_content::add_page( array(
	'title' => 'Contact Page - Momentum PRO',
	'file' => 'contact.txt',
	'demo_unique_id' => '9645cd8f044521',
));

$page_case_studies_id = td_demo_content::add_page( array(
	'title' => 'Case Studies',
	'file' => 'case_studies.txt',
	'demo_unique_id' => '60645cd8f044eab',
));

$page_our_services_id = td_demo_content::add_page( array(
	'title' => 'Our Services',
	'file' => 'our_services.txt',
	'demo_unique_id' => '99645cd8f046380',
));

$page_home_id = td_demo_content::add_page( array(
	'title' => 'Home',
	'file' => 'home.txt',
	'homepage' => true,
	'demo_unique_id' => '22645cd8f047daf',
));


/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTION - start phase 2
*/

/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTIONS
*/
// add post meta for default locker
//td_demo_content::add_locker_meta( array(
//	'tds_locker_id' => (int) get_option( 'tds_default_locker_id' ),
//	'tds_locker_meta' => array(
//			'tds_locker_settings' => array(
//				'tds_title' => 'This Content Is Only For Subscribers',
//				'tds_message' => 'Please subscribe to unlock this content. Enter your email to get access.',
//				'tds_input_placeholder' => 'Please enter your email address.',
//				'tds_submit_btn_text' => 'Subscribe to unlock',
//				'tds_after_btn_text' => 'Your email address is 100% safe from spam!',
//				'tds_pp_msg' => 'I consent to processing of my data according to <a href=\"#\">Terms of Use</a> & <a href=\"#\">Privacy Policy</a>',
//			),
//		)
//	)
//);


/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTION - end phase 2
*/

/*  ---------------------------------------------------------------------------- 
	POSTS
*/
$post_td_post_the_hottest_wearable_tech_and_smart_gadgets_of_2022_will_blow_your_mind_id = td_demo_content::add_post( array(
	'title' => 'The Hottest Wearable Tech and Smart Gadgets of 2022 Will Blow Your Mind',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_new_technology_will_help_keep_your_smart_home_from_becoming_obsolete_id = td_demo_content::add_post( array(
	'title' => 'New Technology Will Help Keep Your Smart Home from Becoming Obsolete',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_blog_id,),
));

$post_discover_the_newest_waterproof_gadgets_that_come_on_sale_id = td_demo_content::add_post( array(
	'title' => 'Discover the Newest Waterproof Gadgets that Come on Sale',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'tds_locker' => '5',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_apple_electronics_climb_the_list_of_the_top_gadgets_in_forbes_magazine_id = td_demo_content::add_post( array(
	'title' => 'Apple Electronics Climb the List of the Top Gadgets in Forbes Magazine',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_customer_engagement_marketing_a_new_strategy_for_the_economy_id = td_demo_content::add_post( array(
	'title' => 'Customer Engagement Marketing: A New Strategy for the Economy',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_television_is_in_steep_decline_compared_to_social_network_marketing_id = td_demo_content::add_post( array(
	'title' => 'Television is in Steep Decline Compared to Social Network Marketing',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_social_networks_advertising_is_important_the_future_of_marketing_id = td_demo_content::add_post( array(
	'title' => 'Social Networks Advertising is Important the Future Of Marketing',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_mobile_marketing_is_the_future_of_e_commerce_real_world_study_finds_id = td_demo_content::add_post( array(
	'title' => 'Mobile Marketing is the Future of E-Commerce, Real-World Study Finds',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_how_can_influencers_show_glamour_and_class_selling_on_instagram_id = td_demo_content::add_post( array(
	'title' => 'How Can Influencers Show Glamour and Class Selling on Instagram',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_new_small_camera_review_pricing_is_not_always_the_only_criteria_id = td_demo_content::add_post( array(
	'title' => 'New Small Camera Review: Pricing is Not Always the Only Criteria',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_blog_id,),
));


/*  ---------------------------------------------------------------------------- 
	PRODUCTS
*/

/*  ---------------------------------------------------------------------------- 
	TAXONOMIES
*/
$tax_term_branding_visual_design_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Branding &amp; visual design',
	'taxonomy' => 'tdtax_services',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'We create a cohesive and recognizable brand image that resonates with your target audience',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdcf_service_summary' => '',
		'_tdcf_service_summary' => 'ZmllbGRfNjNmODY1ZWM2Yzc0MQ==',
		'tdcf_service_description' => 'Q3JlYXRpbmcgYSBtZW1vcmFibGUgYW5kIHJlY29nbml6YWJsZSBicmFuZCBpcyBrZXkgdG8gc3VjY2VzcyBpbiBhbnkgYnVzaW5lc3MuIE91ciBicmFuZGluZyBhbmQgdmlzdWFsIGRlc2lnbiBzZXJ2aWNlIHByb3ZpZGVzIGEgY29tcGxldGUgc29sdXRpb24gdG8gaGVscCB5b3VyIGJyYW5kIHN0YW5kIG91dCBpbiB0aGUgbWFya2V0cGxhY2UuDQo8aDM+Q29uY2VwdHVhbGl6YXRpb246PC9oMz4NClRoZSBmaXJzdCBzdGVwIGluIGNyZWF0aW5nIGEgc3VjY2Vzc2Z1bCBicmFuZCBpcyB1bmRlcnN0YW5kaW5nIHlvdXIgYnVzaW5lc3MgYW5kIHlvdXIgdGFyZ2V0IGF1ZGllbmNlLiBXZSB3aWxsIHdvcmsgY2xvc2VseSB3aXRoIHlvdSB0byBpZGVudGlmeSB5b3VyIGJyYW5kIHZhbHVlcyBhbmQgcGVyc29uYWxpdHksIGFuZCBkZXZlbG9wIGEgYnJhbmQgc3RyYXRlZ3kgdGhhdCB3aWxsIGhlbHAgeW91IGNvbm5lY3Qgd2l0aCB5b3VyIHRhcmdldCBhdWRpZW5jZS4gV2Ugd2lsbCBhbHNvIGNvbmR1Y3QgcmVzZWFyY2ggdG8gaWRlbnRpZnkgeW91ciBjb21wZXRpdG9ycyBhbmQgZGV0ZXJtaW5lIGhvdyB0byBkaWZmZXJlbnRpYXRlIHlvdXIgYnJhbmQgaW4gdGhlIG1hcmtldHBsYWNlLg0KPGgzPkRlc2lnbiBJbXBsZW1lbnRhdGlvbjo8L2gzPg0KT3VyIHRlYW0gb2Ygc2tpbGxlZCBkZXNpZ25lcnMgd2lsbCBicmluZyB5b3VyIGJyYW5kIGlkZW50aXR5IHRvIGxpZmUgYWNyb3NzIGFsbCBjaGFubmVscywgaW5jbHVkaW5nIGxvZ29zLCB0eXBvZ3JhcGh5LCBjb2xvciBzY2hlbWVzLCBhbmQgaW1hZ2VyeS4gV2UgdXNlIHRoZSBsYXRlc3QgZGVzaWduIHNvZnR3YXJlIGFuZCB0ZWNobmlxdWVzIHRvIGNyZWF0ZSB1bmlxdWUgYW5kIHZpc3VhbGx5IHN0dW5uaW5nIGRlc2lnbnMgdGhhdCB3aWxsIGhlbHAgeW91ciBicmFuZCBzdGFuZCBvdXQgZnJvbSB0aGUgY3Jvd2QuDQo8aDM+QnJhbmQgR3VpZGVsaW5lczo8L2gzPg0KVG8gZW5zdXJlIGNvbnNpc3RlbmN5IGluIHlvdXIgYnJhbmQgYWNyb3NzIGFsbCBwbGF0Zm9ybXMsIHdlIHdpbGwgZGV2ZWxvcCBicmFuZCBndWlkZWxpbmVzLiBUaGVzZSBndWlkZWxpbmVzIHdpbGwgc3BlY2lmeSBob3cgeW91ciBicmFuZCBzaG91bGQgYmUgcmVwcmVzZW50ZWQgYWNyb3NzIGRpZmZlcmVudCBtZWRpdW1zIGFuZCBwcm92aWRlIHJ1bGVzIGZvciB0aGUgdXNlIG9mIHlvdXIgbG9nbywgdHlwb2dyYXBoeSwgY29sb3JzLCBhbmQgaW1hZ2VyeS4=',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'MzQ4',
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
		'tdb_filter_color' => '',
		'tdcf_service_description' => 'Q3JlYXRpbmcgYSBtZW1vcmFibGUgYW5kIHJlY29nbml6YWJsZSBicmFuZCBpcyBrZXkgdG8gc3VjY2VzcyBpbiBhbnkgYnVzaW5lc3MuIE91ciBicmFuZGluZyBhbmQgdmlzdWFsIGRlc2lnbiBzZXJ2aWNlIHByb3ZpZGVzIGEgY29tcGxldGUgc29sdXRpb24gdG8gaGVscCB5b3VyIGJyYW5kIHN0YW5kIG91dCBpbiB0aGUgbWFya2V0cGxhY2UuDQo8aDM+Q29uY2VwdHVhbGl6YXRpb246PC9oMz4NClRoZSBmaXJzdCBzdGVwIGluIGNyZWF0aW5nIGEgc3VjY2Vzc2Z1bCBicmFuZCBpcyB1bmRlcnN0YW5kaW5nIHlvdXIgYnVzaW5lc3MgYW5kIHlvdXIgdGFyZ2V0IGF1ZGllbmNlLiBXZSB3aWxsIHdvcmsgY2xvc2VseSB3aXRoIHlvdSB0byBpZGVudGlmeSB5b3VyIGJyYW5kIHZhbHVlcyBhbmQgcGVyc29uYWxpdHksIGFuZCBkZXZlbG9wIGEgYnJhbmQgc3RyYXRlZ3kgdGhhdCB3aWxsIGhlbHAgeW91IGNvbm5lY3Qgd2l0aCB5b3VyIHRhcmdldCBhdWRpZW5jZS4gV2Ugd2lsbCBhbHNvIGNvbmR1Y3QgcmVzZWFyY2ggdG8gaWRlbnRpZnkgeW91ciBjb21wZXRpdG9ycyBhbmQgZGV0ZXJtaW5lIGhvdyB0byBkaWZmZXJlbnRpYXRlIHlvdXIgYnJhbmQgaW4gdGhlIG1hcmtldHBsYWNlLg0KPGgzPkRlc2lnbiBJbXBsZW1lbnRhdGlvbjo8L2gzPg0KT3VyIHRlYW0gb2Ygc2tpbGxlZCBkZXNpZ25lcnMgd2lsbCBicmluZyB5b3VyIGJyYW5kIGlkZW50aXR5IHRvIGxpZmUgYWNyb3NzIGFsbCBjaGFubmVscywgaW5jbHVkaW5nIGxvZ29zLCB0eXBvZ3JhcGh5LCBjb2xvciBzY2hlbWVzLCBhbmQgaW1hZ2VyeS4gV2UgdXNlIHRoZSBsYXRlc3QgZGVzaWduIHNvZnR3YXJlIGFuZCB0ZWNobmlxdWVzIHRvIGNyZWF0ZSB1bmlxdWUgYW5kIHZpc3VhbGx5IHN0dW5uaW5nIGRlc2lnbnMgdGhhdCB3aWxsIGhlbHAgeW91ciBicmFuZCBzdGFuZCBvdXQgZnJvbSB0aGUgY3Jvd2QuDQo8aDM+QnJhbmQgR3VpZGVsaW5lczo8L2gzPg0KVG8gZW5zdXJlIGNvbnNpc3RlbmN5IGluIHlvdXIgYnJhbmQgYWNyb3NzIGFsbCBwbGF0Zm9ybXMsIHdlIHdpbGwgZGV2ZWxvcCBicmFuZCBndWlkZWxpbmVzLiBUaGVzZSBndWlkZWxpbmVzIHdpbGwgc3BlY2lmeSBob3cgeW91ciBicmFuZCBzaG91bGQgYmUgcmVwcmVzZW50ZWQgYWNyb3NzIGRpZmZlcmVudCBtZWRpdW1zIGFuZCBwcm92aWRlIHJ1bGVzIGZvciB0aGUgdXNlIG9mIHlvdXIgbG9nbywgdHlwb2dyYXBoeSwgY29sb3JzLCBhbmQgaW1hZ2VyeS4=',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'dGR4X3BpY18z', // tdcf_demo_id:tdx_pic_3
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
	),
));

$tax_term_content_copywriting_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Content &amp; copywriting',
	'taxonomy' => 'tdtax_services',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'We provide high-quality content and copy that engages and informs your audience.',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdcf_service_summary' => '',
		'_tdcf_service_summary' => 'ZmllbGRfNjNmODY1ZWM2Yzc0MQ==',
		'tdcf_service_description' => 'Q3JlYXRpbmcgaGlnaC1xdWFsaXR5IGNvbnRlbnQgdGhhdCByZXNvbmF0ZXMgd2l0aCB5b3VyIGF1ZGllbmNlIGlzIGVzc2VudGlhbCBmb3Igc3VjY2VzcyBpbiBkaWdpdGFsIG1hcmtldGluZy4gT3VyIGNvbnRlbnQgYW5kIGNvcHl3cml0aW5nIHNlcnZpY2UgcHJvdmlkZXMgYSBjb21wcmVoZW5zaXZlIHNvbHV0aW9uIHRvIGhlbHAgeW91IGNyZWF0ZSBlbmdhZ2luZyBjb250ZW50IHRoYXQgZHJpdmVzIHJlc3VsdHMuDQo8aDM+U3RyYXRlZ3k6PC9oMz4NCk91ciB0ZWFtIG9mIGNvbnRlbnQgc3RyYXRlZ2lzdHMgd2lsbCB3b3JrIHdpdGggeW91IHRvIGRldmVsb3AgYSBjb250ZW50IHN0cmF0ZWd5IHRoYXQgYWxpZ25zIHdpdGggeW91ciBicmFuZCBhbmQgZ29hbHMuIFdlIHdpbGwgY29uZHVjdCBrZXl3b3JkIHJlc2VhcmNoIHRvIGlkZW50aWZ5IHRvcGljcyB0aGF0IGFyZSByZWxldmFudCB0byB5b3VyIGF1ZGllbmNlIGFuZCBvcHRpbWl6ZSB5b3VyIGNvbnRlbnQgZm9yIHNlYXJjaCBlbmdpbmVzLg0KPGgzPldyaXRpbmc6PC9oMz4NCk91ciB0ZWFtIG9mIGV4cGVyaWVuY2VkIHdyaXRlcnMgd2lsbCBwcm9kdWNlIGhpZ2gtcXVhbGl0eSwgZW5nYWdpbmcgY29udGVudCB0aGF0IHJlc29uYXRlcyB3aXRoIHlvdXIgYXVkaWVuY2UuIFdlIGNhbiBjcmVhdGUgYSB3aWRlIHJhbmdlIG9mIGNvbnRlbnQgdHlwZXMsIGluY2x1ZGluZyBibG9nIHBvc3RzLCBhcnRpY2xlcywgd2hpdGVwYXBlcnMsIGVib29rcywgYW5kIG1vcmUuIEFsbCBvZiBvdXIgY29udGVudCBpcyBvcHRpbWl6ZWQgZm9yIHNlYXJjaCBlbmdpbmVzIGFuZCBkZXNpZ25lZCB0byBkcml2ZSBlbmdhZ2VtZW50Lg0KPGgzPkVkaXRpbmc6PC9oMz4NCldlIHByb3ZpZGUgcHJvZmVzc2lvbmFsIGVkaXRpbmcgYW5kIHByb29mcmVhZGluZyBzZXJ2aWNlcyB0byBlbnN1cmUgdGhhdCB5b3VyIGNvbnRlbnQgaXMgZXJyb3ItZnJlZSBhbmQgcG9saXNoZWQuIE91ciBlZGl0b3JzIHdpbGwgcmV2aWV3IHlvdXIgY29udGVudCBmb3IgZ3JhbW1hciwgcHVuY3R1YXRpb24sIHNwZWxsaW5nLCBhbmQgc3R5bGUsIGFuZCBwcm92aWRlIGZlZWRiYWNrIHRvIGhlbHAgeW91IGltcHJvdmUgeW91ciBjb250ZW50Lg==',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'MzQ5',
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
		'tdb_filter_color' => '',
		'tdcf_service_description' => 'Q3JlYXRpbmcgaGlnaC1xdWFsaXR5IGNvbnRlbnQgdGhhdCByZXNvbmF0ZXMgd2l0aCB5b3VyIGF1ZGllbmNlIGlzIGVzc2VudGlhbCBmb3Igc3VjY2VzcyBpbiBkaWdpdGFsIG1hcmtldGluZy4gT3VyIGNvbnRlbnQgYW5kIGNvcHl3cml0aW5nIHNlcnZpY2UgcHJvdmlkZXMgYSBjb21wcmVoZW5zaXZlIHNvbHV0aW9uIHRvIGhlbHAgeW91IGNyZWF0ZSBlbmdhZ2luZyBjb250ZW50IHRoYXQgZHJpdmVzIHJlc3VsdHMuDQo8aDM+U3RyYXRlZ3k6PC9oMz4NCk91ciB0ZWFtIG9mIGNvbnRlbnQgc3RyYXRlZ2lzdHMgd2lsbCB3b3JrIHdpdGggeW91IHRvIGRldmVsb3AgYSBjb250ZW50IHN0cmF0ZWd5IHRoYXQgYWxpZ25zIHdpdGggeW91ciBicmFuZCBhbmQgZ29hbHMuIFdlIHdpbGwgY29uZHVjdCBrZXl3b3JkIHJlc2VhcmNoIHRvIGlkZW50aWZ5IHRvcGljcyB0aGF0IGFyZSByZWxldmFudCB0byB5b3VyIGF1ZGllbmNlIGFuZCBvcHRpbWl6ZSB5b3VyIGNvbnRlbnQgZm9yIHNlYXJjaCBlbmdpbmVzLg0KPGgzPldyaXRpbmc6PC9oMz4NCk91ciB0ZWFtIG9mIGV4cGVyaWVuY2VkIHdyaXRlcnMgd2lsbCBwcm9kdWNlIGhpZ2gtcXVhbGl0eSwgZW5nYWdpbmcgY29udGVudCB0aGF0IHJlc29uYXRlcyB3aXRoIHlvdXIgYXVkaWVuY2UuIFdlIGNhbiBjcmVhdGUgYSB3aWRlIHJhbmdlIG9mIGNvbnRlbnQgdHlwZXMsIGluY2x1ZGluZyBibG9nIHBvc3RzLCBhcnRpY2xlcywgd2hpdGVwYXBlcnMsIGVib29rcywgYW5kIG1vcmUuIEFsbCBvZiBvdXIgY29udGVudCBpcyBvcHRpbWl6ZWQgZm9yIHNlYXJjaCBlbmdpbmVzIGFuZCBkZXNpZ25lZCB0byBkcml2ZSBlbmdhZ2VtZW50Lg0KPGgzPkVkaXRpbmc6PC9oMz4NCldlIHByb3ZpZGUgcHJvZmVzc2lvbmFsIGVkaXRpbmcgYW5kIHByb29mcmVhZGluZyBzZXJ2aWNlcyB0byBlbnN1cmUgdGhhdCB5b3VyIGNvbnRlbnQgaXMgZXJyb3ItZnJlZSBhbmQgcG9saXNoZWQuIE91ciBlZGl0b3JzIHdpbGwgcmV2aWV3IHlvdXIgY29udGVudCBmb3IgZ3JhbW1hciwgcHVuY3R1YXRpb24sIHNwZWxsaW5nLCBhbmQgc3R5bGUsIGFuZCBwcm92aWRlIGZlZWRiYWNrIHRvIGhlbHAgeW91IGltcHJvdmUgeW91ciBjb250ZW50Lg==',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'dGR4X3BpY180', // tdcf_demo_id:tdx_pic_4
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
	),
));

$tax_term_e_commerce_development_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'E-commerce development',
	'taxonomy' => 'tdtax_services',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'We build customized e-commerce solutions that drive sales and improve user experience.',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdcf_service_summary' => '',
		'_tdcf_service_summary' => 'ZmllbGRfNjNmODY1ZWM2Yzc0MQ==',
		'tdcf_service_description' => 'T25saW5lIHNob3BwaW5nIGhhcyBiZWNvbWUgYSBwcmVmZXJyZWQgd2F5IG9mIHNob3BwaW5nIGZvciBtb3N0IGNvbnN1bWVycy4gSWYgeW91IHdhbnQgdG8gc3VjY2VlZCBpbiBlLWNvbW1lcmNlLCB5b3UgbmVlZCBhIHdlYnNpdGUgdGhhdCBpcyBub3Qgb25seSB2aXN1YWxseSBhcHBlYWxpbmcgYnV0IGFsc28gZWFzeSB0byBuYXZpZ2F0ZSwgc2FmZSwgYW5kIHVzZXItZnJpZW5kbHkuIE91ciBlLWNvbW1lcmNlIGRldmVsb3BtZW50IHNlcnZpY2UgcHJvdmlkZXMgYSBjb21wcmVoZW5zaXZlIHNvbHV0aW9uIHRvIGhlbHAgeW91IGNyZWF0ZSBhIHN1Y2Nlc3NmdWwgb25saW5lIHN0b3JlLg0KPGgzPkRlc2lnbjo8L2gzPg0KT3VyIHRlYW0gb2Ygc2tpbGxlZCBkZXNpZ25lcnMgd2lsbCB3b3JrIHdpdGggeW91IHRvIGNyZWF0ZSBhbiBlLWNvbW1lcmNlIHdlYnNpdGUgZGVzaWduIHRoYXQgaXMgdmlzdWFsbHkgYXBwZWFsaW5nIGFuZCBvbi1icmFuZC4gV2UgdXNlIHRoZSBsYXRlc3QgZGVzaWduIHNvZnR3YXJlIGFuZCB0ZWNobmlxdWVzIHRvIGNyZWF0ZSBhIHVuaXF1ZSBhbmQgdmlzdWFsbHkgc3R1bm5pbmcgZGVzaWduIHRoYXQgd2lsbCBoZWxwIHlvdXIgb25saW5lIHN0b3JlIHN0YW5kIG91dCBmcm9tIHRoZSBjb21wZXRpdGlvbi4NCjxoMz5EZXZlbG9wbWVudDo8L2gzPg0KV2UgdXNlIHRoZSBsYXRlc3QgZS1jb21tZXJjZSBwbGF0Zm9ybXMsIGluY2x1ZGluZyBXb29Db21tZXJjZSwgTWFnZW50bywgYW5kIFNob3BpZnksIHRvIGRldmVsb3AgeW91ciBvbmxpbmUgc3RvcmUuIFdlIHdpbGwgY3VzdG9taXplIHlvdXIgd2Vic2l0ZSB0byBmaXQgeW91ciBicmFuZCBhbmQgeW91ciBidXNpbmVzcyBuZWVkcywgYW5kIHByb3ZpZGUgaW50ZWdyYXRpb25zIHdpdGggcGF5bWVudCBnYXRld2F5cywgc2hpcHBpbmcgcHJvdmlkZXJzLCBhbmQgbW9yZS4NCjxoMz5NYWludGVuYW5jZTo8L2gzPg0KV2UgcHJvdmlkZSBvbmdvaW5nIG1haW50ZW5hbmNlIGFuZCBzdXBwb3J0IHRvIGVuc3VyZSB0aGF0IHlvdXIgb25saW5lIHN0b3JlIGlzIGFsd2F5cyB1cCB0byBkYXRlIGFuZCBydW5uaW5nIHNtb290aGx5LiBXZSB3aWxsIG1vbml0b3IgeW91ciB3ZWJzaXRlIGZvciBzZWN1cml0eSB2dWxuZXJhYmlsaXRpZXMsIHNvZnR3YXJlIHVwZGF0ZXMsIGFuZCBvdGhlciBpc3N1ZXMsIGFuZCBwcm92aWRlIHRpbWVseSBzdXBwb3J0IGlmIG5lZWRlZC4=',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'MzUw',
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
		'tdb_filter_color' => '',
		'tdcf_service_description' => 'T25saW5lIHNob3BwaW5nIGhhcyBiZWNvbWUgYSBwcmVmZXJyZWQgd2F5IG9mIHNob3BwaW5nIGZvciBtb3N0IGNvbnN1bWVycy4gSWYgeW91IHdhbnQgdG8gc3VjY2VlZCBpbiBlLWNvbW1lcmNlLCB5b3UgbmVlZCBhIHdlYnNpdGUgdGhhdCBpcyBub3Qgb25seSB2aXN1YWxseSBhcHBlYWxpbmcgYnV0IGFsc28gZWFzeSB0byBuYXZpZ2F0ZSwgc2FmZSwgYW5kIHVzZXItZnJpZW5kbHkuIE91ciBlLWNvbW1lcmNlIGRldmVsb3BtZW50IHNlcnZpY2UgcHJvdmlkZXMgYSBjb21wcmVoZW5zaXZlIHNvbHV0aW9uIHRvIGhlbHAgeW91IGNyZWF0ZSBhIHN1Y2Nlc3NmdWwgb25saW5lIHN0b3JlLg0KPGgzPkRlc2lnbjo8L2gzPg0KT3VyIHRlYW0gb2Ygc2tpbGxlZCBkZXNpZ25lcnMgd2lsbCB3b3JrIHdpdGggeW91IHRvIGNyZWF0ZSBhbiBlLWNvbW1lcmNlIHdlYnNpdGUgZGVzaWduIHRoYXQgaXMgdmlzdWFsbHkgYXBwZWFsaW5nIGFuZCBvbi1icmFuZC4gV2UgdXNlIHRoZSBsYXRlc3QgZGVzaWduIHNvZnR3YXJlIGFuZCB0ZWNobmlxdWVzIHRvIGNyZWF0ZSBhIHVuaXF1ZSBhbmQgdmlzdWFsbHkgc3R1bm5pbmcgZGVzaWduIHRoYXQgd2lsbCBoZWxwIHlvdXIgb25saW5lIHN0b3JlIHN0YW5kIG91dCBmcm9tIHRoZSBjb21wZXRpdGlvbi4NCjxoMz5EZXZlbG9wbWVudDo8L2gzPg0KV2UgdXNlIHRoZSBsYXRlc3QgZS1jb21tZXJjZSBwbGF0Zm9ybXMsIGluY2x1ZGluZyBXb29Db21tZXJjZSwgTWFnZW50bywgYW5kIFNob3BpZnksIHRvIGRldmVsb3AgeW91ciBvbmxpbmUgc3RvcmUuIFdlIHdpbGwgY3VzdG9taXplIHlvdXIgd2Vic2l0ZSB0byBmaXQgeW91ciBicmFuZCBhbmQgeW91ciBidXNpbmVzcyBuZWVkcywgYW5kIHByb3ZpZGUgaW50ZWdyYXRpb25zIHdpdGggcGF5bWVudCBnYXRld2F5cywgc2hpcHBpbmcgcHJvdmlkZXJzLCBhbmQgbW9yZS4NCjxoMz5NYWludGVuYW5jZTo8L2gzPg0KV2UgcHJvdmlkZSBvbmdvaW5nIG1haW50ZW5hbmNlIGFuZCBzdXBwb3J0IHRvIGVuc3VyZSB0aGF0IHlvdXIgb25saW5lIHN0b3JlIGlzIGFsd2F5cyB1cCB0byBkYXRlIGFuZCBydW5uaW5nIHNtb290aGx5LiBXZSB3aWxsIG1vbml0b3IgeW91ciB3ZWJzaXRlIGZvciBzZWN1cml0eSB2dWxuZXJhYmlsaXRpZXMsIHNvZnR3YXJlIHVwZGF0ZXMsIGFuZCBvdGhlciBpc3N1ZXMsIGFuZCBwcm92aWRlIHRpbWVseSBzdXBwb3J0IGlmIG5lZWRlZC4=',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'dGR4X3BpY181', // tdcf_demo_id:tdx_pic_5
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
	),
));

$tax_term_email_marketing_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Email marketing',
	'taxonomy' => 'tdtax_services',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'We develop effective email marketing campaigns that help you connect with your subscribers and boost conversions.',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdcf_service_summary' => '',
		'_tdcf_service_summary' => 'ZmllbGRfNjNmODY1ZWM2Yzc0MQ==',
		'tdcf_service_description' => 'RW1haWwgbWFya2V0aW5nIGlzIG9uZSBvZiB0aGUgbW9zdCBlZmZlY3RpdmUgd2F5cyB0byByZWFjaCB5b3VyIGF1ZGllbmNlIGFuZCBkcml2ZSBzYWxlcy4gT3VyIGVtYWlsIG1hcmtldGluZyBzZXJ2aWNlIHByb3ZpZGVzIGEgY29tcHJlaGVuc2l2ZSBzb2x1dGlvbiB0byBoZWxwIHlvdSBjcmVhdGUgYW5kIGV4ZWN1dGUgc3VjY2Vzc2Z1bCBlbWFpbCBjYW1wYWlnbnMuDQo8aDM+U3RyYXRlZ3k6PC9oMz4NCk91ciB0ZWFtIG9mIGVtYWlsIG1hcmtldGluZyBleHBlcnRzIHdpbGwgd29yayB3aXRoIHlvdSB0byBkZXZlbG9wIGFuIGVtYWlsIG1hcmtldGluZyBzdHJhdGVneSB0aGF0IGFsaWducyB3aXRoIHlvdXIgYnJhbmQgYW5kIGdvYWxzLiBXZSB3aWxsIGhlbHAgeW91IGlkZW50aWZ5IHlvdXIgdGFyZ2V0IGF1ZGllbmNlLCBjcmVhdGUgYSBtZXNzYWdpbmcgc3RyYXRlZ3ksIGFuZCBkZXNpZ24geW91ciBlbWFpbCB0ZW1wbGF0ZXMuDQo8aDM+Q2FtcGFpZ24gTWFuYWdlbWVudDo8L2gzPg0KV2Ugd2lsbCBtYW5hZ2UgeW91ciBlbWFpbCBjYW1wYWlnbnMgZnJvbSBzdGFydCB0byBmaW5pc2ggaW5jbHVkaW5nIGNyZWF0aW5nIGFuZCBkZXNpZ25pbmcgZW1haWwgdGVtcGxhdGVzLCB3cml0aW5nIGNvcHksIG1hbmFnaW5nIGVtYWlsIGxpc3RzLCBhbmQgbW9uaXRvcmluZyBwZXJmb3JtYW5jZSBtZXRyaWNzLiBXZSB3aWxsIGFsc28gaGVscCB5b3UgY29tcGx5IHdpdGggYWxsIGVtYWlsIG1hcmtldGluZyBsYXdzIGFuZCByZWd1bGF0aW9ucy4NCjxoMz5BdXRvbWF0aW9uOjwvaDM+DQpXZSBjYW4gY3JlYXRlIGF1dG9tYXRlZCBlbWFpbCBjYW1wYWlnbnMgdGhhdCBhcmUgdHJpZ2dlcmVkIGJ5IHNwZWNpZmljIHVzZXIgYWN0aW9ucyBvciBldmVudHMsIHN1Y2ggYXMgYWJhbmRvbmVkIGNhcnRzIG9yIHdlYnNpdGUgdmlzaXRzLiBUaGVzZSBhdXRvbWF0ZWQgY2FtcGFpZ25zIGNhbiBzYXZlIHlvdSB0aW1lIGFuZCBpbmNyZWFzZSBjb252ZXJzaW9ucy4NCjxoMz5NZXRyaWNzIGFuZCBSZXBvcnRpbmc6PC9oMz4NCldlIHdpbGwgdHJhY2sgdGhlIHBlcmZvcm1hbmNlIG9mIHlvdXIgZW1haWwgY2FtcGFpZ25zIGFuZCBwcm92aWRlIGRldGFpbGVkIHJlcG9ydGluZyBvbiBtZXRyaWNzIHN1Y2ggYXMgb3BlbiByYXRlcywgY2xpY2stdGhyb3VnaCByYXRlcywgYW5kIGNvbnZlcnNpb25zLiBXZSB3aWxsIGFsc28gcHJvdmlkZSByZWNvbW1lbmRhdGlvbnMgb24gaG93IHRvIGltcHJvdmUgeW91ciBlbWFpbCBjYW1wYWlnbnMgYmFzZWQgb24gdGhlIGRhdGEu',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'MzUx',
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
		'tdb_filter_color' => '',
		'tdcf_service_description' => 'RW1haWwgbWFya2V0aW5nIGlzIG9uZSBvZiB0aGUgbW9zdCBlZmZlY3RpdmUgd2F5cyB0byByZWFjaCB5b3VyIGF1ZGllbmNlIGFuZCBkcml2ZSBzYWxlcy4gT3VyIGVtYWlsIG1hcmtldGluZyBzZXJ2aWNlIHByb3ZpZGVzIGEgY29tcHJlaGVuc2l2ZSBzb2x1dGlvbiB0byBoZWxwIHlvdSBjcmVhdGUgYW5kIGV4ZWN1dGUgc3VjY2Vzc2Z1bCBlbWFpbCBjYW1wYWlnbnMuDQo8aDM+U3RyYXRlZ3k6PC9oMz4NCk91ciB0ZWFtIG9mIGVtYWlsIG1hcmtldGluZyBleHBlcnRzIHdpbGwgd29yayB3aXRoIHlvdSB0byBkZXZlbG9wIGFuIGVtYWlsIG1hcmtldGluZyBzdHJhdGVneSB0aGF0IGFsaWducyB3aXRoIHlvdXIgYnJhbmQgYW5kIGdvYWxzLiBXZSB3aWxsIGhlbHAgeW91IGlkZW50aWZ5IHlvdXIgdGFyZ2V0IGF1ZGllbmNlLCBjcmVhdGUgYSBtZXNzYWdpbmcgc3RyYXRlZ3ksIGFuZCBkZXNpZ24geW91ciBlbWFpbCB0ZW1wbGF0ZXMuDQo8aDM+Q2FtcGFpZ24gTWFuYWdlbWVudDo8L2gzPg0KV2Ugd2lsbCBtYW5hZ2UgeW91ciBlbWFpbCBjYW1wYWlnbnMgZnJvbSBzdGFydCB0byBmaW5pc2ggaW5jbHVkaW5nIGNyZWF0aW5nIGFuZCBkZXNpZ25pbmcgZW1haWwgdGVtcGxhdGVzLCB3cml0aW5nIGNvcHksIG1hbmFnaW5nIGVtYWlsIGxpc3RzLCBhbmQgbW9uaXRvcmluZyBwZXJmb3JtYW5jZSBtZXRyaWNzLiBXZSB3aWxsIGFsc28gaGVscCB5b3UgY29tcGx5IHdpdGggYWxsIGVtYWlsIG1hcmtldGluZyBsYXdzIGFuZCByZWd1bGF0aW9ucy4NCjxoMz5BdXRvbWF0aW9uOjwvaDM+DQpXZSBjYW4gY3JlYXRlIGF1dG9tYXRlZCBlbWFpbCBjYW1wYWlnbnMgdGhhdCBhcmUgdHJpZ2dlcmVkIGJ5IHNwZWNpZmljIHVzZXIgYWN0aW9ucyBvciBldmVudHMsIHN1Y2ggYXMgYWJhbmRvbmVkIGNhcnRzIG9yIHdlYnNpdGUgdmlzaXRzLiBUaGVzZSBhdXRvbWF0ZWQgY2FtcGFpZ25zIGNhbiBzYXZlIHlvdSB0aW1lIGFuZCBpbmNyZWFzZSBjb252ZXJzaW9ucy4NCjxoMz5NZXRyaWNzIGFuZCBSZXBvcnRpbmc6PC9oMz4NCldlIHdpbGwgdHJhY2sgdGhlIHBlcmZvcm1hbmNlIG9mIHlvdXIgZW1haWwgY2FtcGFpZ25zIGFuZCBwcm92aWRlIGRldGFpbGVkIHJlcG9ydGluZyBvbiBtZXRyaWNzIHN1Y2ggYXMgb3BlbiByYXRlcywgY2xpY2stdGhyb3VnaCByYXRlcywgYW5kIGNvbnZlcnNpb25zLiBXZSB3aWxsIGFsc28gcHJvdmlkZSByZWNvbW1lbmRhdGlvbnMgb24gaG93IHRvIGltcHJvdmUgeW91ciBlbWFpbCBjYW1wYWlnbnMgYmFzZWQgb24gdGhlIGRhdGEu',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'dGR4X3BpY182', // tdcf_demo_id:tdx_pic_6
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
	),
));

$tax_term_pay_per_click_management_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Pay per click management',
	'taxonomy' => 'tdtax_services',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'We manage and optimize your PPC campaigns to drive traffic and increase conversions.',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdcf_service_summary' => '',
		'_tdcf_service_summary' => 'ZmllbGRfNjNmODY1ZWM2Yzc0MQ==',
		'tdcf_service_description' => 'UGF5IHBlciBjbGljayAoUFBDKSBhZHZlcnRpc2luZyBpcyBhIGhpZ2hseSBlZmZlY3RpdmUgd2F5IHRvIGRyaXZlIHRyYWZmaWMgdG8geW91ciB3ZWJzaXRlIGFuZCBpbmNyZWFzZSBjb252ZXJzaW9ucy4gT3VyIFBQQyBtYW5hZ2VtZW50IHNlcnZpY2UgcHJvdmlkZXMgYSBjb21wcmVoZW5zaXZlIHNvbHV0aW9uIHRvIGhlbHAgeW91IGNyZWF0ZSBhbmQgbWFuYWdlIHN1Y2Nlc3NmdWwgUFBDIGNhbXBhaWducy4NCjxoMz5TdHJhdGVneTo8L2gzPg0KT3VyIHRlYW0gb2YgUFBDIGV4cGVydHMgd2lsbCB3b3JrIHdpdGggeW91IHRvIGRldmVsb3AgYSBQUEMgc3RyYXRlZ3kgdGhhdCBhbGlnbnMgd2l0aCB5b3VyIGJ1c2luZXNzIGdvYWxzIGFuZCBidWRnZXQuIFdlIHdpbGwgY29uZHVjdCBrZXl3b3JkIHJlc2VhcmNoLCBjcmVhdGUgYWQgY29weSwgYW5kIGRlc2lnbiBsYW5kaW5nIHBhZ2VzIHRoYXQgYXJlIG9wdGltaXplZCBmb3IgY29udmVyc2lvbnMuDQo8aDM+Q2FtcGFpZ24gTWFuYWdlbWVudDo8L2gzPg0KV2Ugd2lsbCBtYW5hZ2UgeW91ciBQUEMgY2FtcGFpZ25zIGZyb20gc3RhcnQgdG8gZmluaXNoLCBpbmNsdWRpbmcgY3JlYXRpbmcgYWQgZ3JvdXBzLCBzZXR0aW5nIHVwIHRhcmdldGluZywgYW5kIG1vbml0b3JpbmcgcGVyZm9ybWFuY2UuIFdlIHdpbGwgYWxzbyBwcm92aWRlIG9uZ29pbmcgb3B0aW1pemF0aW9uIHRvIGVuc3VyZSB0aGF0IHlvdXIgY2FtcGFpZ25zIGFyZSBydW5uaW5nIGF0IHBlYWsgcGVyZm9ybWFuY2UuDQo8aDM+TWV0cmljcyBhbmQgUmVwb3J0aW5nOjwvaDM+DQpXZSB3aWxsIHRyYWNrIHRoZSBwZXJmb3JtYW5jZSBvZiB5b3VyIFBQQyBjYW1wYWlnbnMgYW5kIHByb3ZpZGUgZGV0YWlsZWQgcmVwb3J0aW5nIG9uIG1ldHJpY3Mgc3VjaCBhcyBjbGljay10aHJvdWdoIHJhdGVzLCBjb3N0IHBlciBjbGljaywgYW5kIGNvbnZlcnNpb24gcmF0ZXMuIFdlIHdpbGwgYWxzbyBwcm92aWRlIHJlY29tbWVuZGF0aW9ucyBvbiBob3cgdG8gaW1wcm92ZSB5b3VyIGNhbXBhaWducyBiYXNlZCBvbiB0aGUgZGF0YS4=',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'MzU3',
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
		'tdb_filter_color' => '',
		'tdcf_service_description' => 'UGF5IHBlciBjbGljayAoUFBDKSBhZHZlcnRpc2luZyBpcyBhIGhpZ2hseSBlZmZlY3RpdmUgd2F5IHRvIGRyaXZlIHRyYWZmaWMgdG8geW91ciB3ZWJzaXRlIGFuZCBpbmNyZWFzZSBjb252ZXJzaW9ucy4gT3VyIFBQQyBtYW5hZ2VtZW50IHNlcnZpY2UgcHJvdmlkZXMgYSBjb21wcmVoZW5zaXZlIHNvbHV0aW9uIHRvIGhlbHAgeW91IGNyZWF0ZSBhbmQgbWFuYWdlIHN1Y2Nlc3NmdWwgUFBDIGNhbXBhaWducy4NCjxoMz5TdHJhdGVneTo8L2gzPg0KT3VyIHRlYW0gb2YgUFBDIGV4cGVydHMgd2lsbCB3b3JrIHdpdGggeW91IHRvIGRldmVsb3AgYSBQUEMgc3RyYXRlZ3kgdGhhdCBhbGlnbnMgd2l0aCB5b3VyIGJ1c2luZXNzIGdvYWxzIGFuZCBidWRnZXQuIFdlIHdpbGwgY29uZHVjdCBrZXl3b3JkIHJlc2VhcmNoLCBjcmVhdGUgYWQgY29weSwgYW5kIGRlc2lnbiBsYW5kaW5nIHBhZ2VzIHRoYXQgYXJlIG9wdGltaXplZCBmb3IgY29udmVyc2lvbnMuDQo8aDM+Q2FtcGFpZ24gTWFuYWdlbWVudDo8L2gzPg0KV2Ugd2lsbCBtYW5hZ2UgeW91ciBQUEMgY2FtcGFpZ25zIGZyb20gc3RhcnQgdG8gZmluaXNoLCBpbmNsdWRpbmcgY3JlYXRpbmcgYWQgZ3JvdXBzLCBzZXR0aW5nIHVwIHRhcmdldGluZywgYW5kIG1vbml0b3JpbmcgcGVyZm9ybWFuY2UuIFdlIHdpbGwgYWxzbyBwcm92aWRlIG9uZ29pbmcgb3B0aW1pemF0aW9uIHRvIGVuc3VyZSB0aGF0IHlvdXIgY2FtcGFpZ25zIGFyZSBydW5uaW5nIGF0IHBlYWsgcGVyZm9ybWFuY2UuDQo8aDM+TWV0cmljcyBhbmQgUmVwb3J0aW5nOjwvaDM+DQpXZSB3aWxsIHRyYWNrIHRoZSBwZXJmb3JtYW5jZSBvZiB5b3VyIFBQQyBjYW1wYWlnbnMgYW5kIHByb3ZpZGUgZGV0YWlsZWQgcmVwb3J0aW5nIG9uIG1ldHJpY3Mgc3VjaCBhcyBjbGljay10aHJvdWdoIHJhdGVzLCBjb3N0IHBlciBjbGljaywgYW5kIGNvbnZlcnNpb24gcmF0ZXMuIFdlIHdpbGwgYWxzbyBwcm92aWRlIHJlY29tbWVuZGF0aW9ucyBvbiBob3cgdG8gaW1wcm92ZSB5b3VyIGNhbXBhaWducyBiYXNlZCBvbiB0aGUgZGF0YS4=',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'dGR4X3BpY183', // tdcf_demo_id:tdx_pic_7
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
	),
));

$tax_term_search_engine_optimization_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Search engine optimization',
	'taxonomy' => 'tdtax_services',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'We improve your website\'s visibility and ranking on search engines to drive organic traffic and improve brand awareness.',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdcf_service_summary' => '',
		'_tdcf_service_summary' => 'ZmllbGRfNjNmODY1ZWM2Yzc0MQ==',
		'tdcf_service_description' => 'U2VhcmNoIGVuZ2luZSBvcHRpbWl6YXRpb24gKFNFTykgaXMgdGhlIHByYWN0aWNlIG9mIG9wdGltaXppbmcgeW91ciB3ZWJzaXRlIHRvIHJhbmsgaGlnaGVyIGluIHNlYXJjaCBlbmdpbmUgcmVzdWx0cyBwYWdlcy4gT3VyIFNFTyBzZXJ2aWNlIHByb3ZpZGVzIGEgY29tcHJlaGVuc2l2ZSBzb2x1dGlvbiB0byBoZWxwIHlvdSBpbXByb3ZlIHlvdXIgd2Vic2l0ZSdzIHZpc2liaWxpdHkgYW5kIGRyaXZlIG1vcmUgdHJhZmZpYy4NCjxoMz5Pbi1wYWdlIE9wdGltaXphdGlvbjo8L2gzPg0KV2Ugd2lsbCBvcHRpbWl6ZSB5b3VyIHdlYnNpdGUncyBvbi1wYWdlIGVsZW1lbnRzLCBpbmNsdWRpbmcgdGl0bGUgdGFncywgbWV0YSBkZXNjcmlwdGlvbnMsIGhlYWRlciB0YWdzLCBhbmQgY29udGVudCwgdG8gbWFrZSBzdXJlIHRoYXQgdGhleSBhcmUgb3B0aW1pemVkIGZvciBzZWFyY2ggZW5naW5lcy4NCjxoMz5PZmYtcGFnZSBPcHRpbWl6YXRpb246PC9oMz4NCldlIHdpbGwgZGV2ZWxvcCBhIGJhY2tsaW5rIHN0cmF0ZWd5IHRvIGluY3JlYXNlIHlvdXIgd2Vic2l0ZSdzIGF1dGhvcml0eSBhbmQgaW1wcm92ZSBpdHMgc2VhcmNoIGVuZ2luZSByYW5raW5ncy4gV2Ugd2lsbCBhbHNvIGNyZWF0ZSBjb250ZW50IHRoYXQgaXMgc2hhcmVhYmxlIGFuZCBlbmdhZ2luZyB0byBpbmNyZWFzZSB0aGUgbGlrZWxpaG9vZCBvZiBiYWNrbGlua3MuDQo8aDM+QW5hbHl0aWNzIGFuZCBSZXBvcnRpbmc6PC9oMz4NCldlIHdpbGwgdHJhY2sgdGhlIHBlcmZvcm1hbmNlIG9mIHlvdXIgU0VPIGNhbXBhaWducyBhbmQgcHJvdmlkZSBkZXRhaWxlZCByZXBvcnRpbmcgb24gbWV0cmljcyBzdWNoIGFzIGtleXdvcmQgcmFua2luZ3MsIHRyYWZmaWMsIGFuZCBjb252ZXJzaW9ucy4gV2Ugd2lsbCBhbHNvIHByb3ZpZGUgcmVjb21tZW5kYXRpb25zIG9uIGhvdyB0byBpbXByb3ZlIHlvdXIgU0VPIGNhbXBhaWducyBiYXNlZCBvbiB0aGUgZGF0YS4=',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'MzY0',
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
		'tdb_filter_color' => '',
		'tdcf_service_description' => 'U2VhcmNoIGVuZ2luZSBvcHRpbWl6YXRpb24gKFNFTykgaXMgdGhlIHByYWN0aWNlIG9mIG9wdGltaXppbmcgeW91ciB3ZWJzaXRlIHRvIHJhbmsgaGlnaGVyIGluIHNlYXJjaCBlbmdpbmUgcmVzdWx0cyBwYWdlcy4gT3VyIFNFTyBzZXJ2aWNlIHByb3ZpZGVzIGEgY29tcHJlaGVuc2l2ZSBzb2x1dGlvbiB0byBoZWxwIHlvdSBpbXByb3ZlIHlvdXIgd2Vic2l0ZSdzIHZpc2liaWxpdHkgYW5kIGRyaXZlIG1vcmUgdHJhZmZpYy4NCjxoMz5Pbi1wYWdlIE9wdGltaXphdGlvbjo8L2gzPg0KV2Ugd2lsbCBvcHRpbWl6ZSB5b3VyIHdlYnNpdGUncyBvbi1wYWdlIGVsZW1lbnRzLCBpbmNsdWRpbmcgdGl0bGUgdGFncywgbWV0YSBkZXNjcmlwdGlvbnMsIGhlYWRlciB0YWdzLCBhbmQgY29udGVudCwgdG8gbWFrZSBzdXJlIHRoYXQgdGhleSBhcmUgb3B0aW1pemVkIGZvciBzZWFyY2ggZW5naW5lcy4NCjxoMz5PZmYtcGFnZSBPcHRpbWl6YXRpb246PC9oMz4NCldlIHdpbGwgZGV2ZWxvcCBhIGJhY2tsaW5rIHN0cmF0ZWd5IHRvIGluY3JlYXNlIHlvdXIgd2Vic2l0ZSdzIGF1dGhvcml0eSBhbmQgaW1wcm92ZSBpdHMgc2VhcmNoIGVuZ2luZSByYW5raW5ncy4gV2Ugd2lsbCBhbHNvIGNyZWF0ZSBjb250ZW50IHRoYXQgaXMgc2hhcmVhYmxlIGFuZCBlbmdhZ2luZyB0byBpbmNyZWFzZSB0aGUgbGlrZWxpaG9vZCBvZiBiYWNrbGlua3MuDQo8aDM+QW5hbHl0aWNzIGFuZCBSZXBvcnRpbmc6PC9oMz4NCldlIHdpbGwgdHJhY2sgdGhlIHBlcmZvcm1hbmNlIG9mIHlvdXIgU0VPIGNhbXBhaWducyBhbmQgcHJvdmlkZSBkZXRhaWxlZCByZXBvcnRpbmcgb24gbWV0cmljcyBzdWNoIGFzIGtleXdvcmQgcmFua2luZ3MsIHRyYWZmaWMsIGFuZCBjb252ZXJzaW9ucy4gV2Ugd2lsbCBhbHNvIHByb3ZpZGUgcmVjb21tZW5kYXRpb25zIG9uIGhvdyB0byBpbXByb3ZlIHlvdXIgU0VPIGNhbXBhaWducyBiYXNlZCBvbiB0aGUgZGF0YS4=',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'dGR4X3BpY184', // tdcf_demo_id:tdx_pic_8
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
	),
));

$tax_term_social_media_marketing_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Social media marketing',
	'taxonomy' => 'tdtax_services',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'We develop and implement social media strategies that engage your audience and increase brand awareness.',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdcf_service_summary' => '',
		'_tdcf_service_summary' => 'ZmllbGRfNjNmODY1ZWM2Yzc0MQ==',
		'tdcf_service_description' => 'U29jaWFsIG1lZGlhIGlzIGEgcG93ZXJmdWwgdG9vbCBmb3IgYnVpbGRpbmcgeW91ciBicmFuZCwgZW5nYWdpbmcgd2l0aCB5b3VyIGF1ZGllbmNlLCBhbmQgZHJpdmluZyB0cmFmZmljIHRvIHlvdXIgd2Vic2l0ZS4gT3VyIHNvY2lhbCBtZWRpYSBtYXJrZXRpbmcgc2VydmljZSBwcm92aWRlcyBhIGNvbXByZWhlbnNpdmUgc29sdXRpb24gdG8gaGVscCB5b3UgY3JlYXRlIGFuZCBleGVjdXRlIHN1Y2Nlc3NmdWwgc29jaWFsIG1lZGlhIGNhbXBhaWducy4NCjxoMz5TdHJhdGVneTo8L2gzPg0KT3VyIHRlYW0gb2Ygc29jaWFsIG1lZGlhIGV4cGVydHMgd2lsbCB3b3JrIHdpdGggeW91IHRvIGRldmVsb3AgYSBzb2NpYWwgbWVkaWEgc3RyYXRlZ3kgdGhhdCBhbGlnbnMgd2l0aCB5b3VyIGJ1c2luZXNzIGdvYWxzIGFuZCB0YXJnZXQgYXVkaWVuY2UuIFdlIHdpbGwgaWRlbnRpZnkgdGhlIGJlc3Qgc29jaWFsIG1lZGlhIHBsYXRmb3JtcyBmb3IgeW91ciBidXNpbmVzcyBhbmQgY3JlYXRlIGEgbWVzc2FnaW5nIHN0cmF0ZWd5IHRoYXQgcmVzb25hdGVzIHdpdGggeW91ciBhdWRpZW5jZS4NCjxoMz5DYW1wYWlnbiBNYW5hZ2VtZW50OjwvaDM+DQpXZSB3aWxsIG1hbmFnZSB5b3VyIHNvY2lhbCBtZWRpYSBjYW1wYWlnbnMgZnJvbSBzdGFydCB0byBmaW5pc2gsIGluY2x1ZGluZyBjcmVhdGluZyBjb250ZW50LCBzY2hlZHVsaW5nIHBvc3RzLCBhbmQgbW9uaXRvcmluZyBwZXJmb3JtYW5jZS4gV2Ugd2lsbCBhbHNvIHByb3ZpZGUgb25nb2luZyBvcHRpbWl6YXRpb24gdG8gZW5zdXJlIHRoYXQgeW91ciBjYW1wYWlnbnMgYXJlIHJ1bm5pbmcgYXQgcGVhayBwZXJmb3JtYW5jZS4NCjxoMz5NZXRyaWNzIGFuZCBSZXBvcnRpbmc6PC9oMz4NCldlIHdpbGwgdHJhY2sgdGhlIHBlcmZvcm1hbmNlIG9mIHlvdXIgc29jaWFsIG1lZGlhIGNhbXBhaWducyBhbmQgcHJvdmlkZSBkZXRhaWxlZCByZXBvcnRpbmcgb24gbWV0cmljcyBzdWNoIGFzIGVuZ2FnZW1lbnQgcmF0ZXMsIHJlYWNoLCBhbmQgY29udmVyc2lvbnMuIFdlIHdpbGwgYWxzbyBwcm92aWRlIHJlY29tbWVuZGF0aW9ucyBvbiBob3cgdG8gaW1wcm92ZSB5b3VyIHNvY2lhbCBtZWRpYSBjYW1wYWlnbnMgYmFzZWQgb24gdGhlIGRhdGEu',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'MzYz',
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
		'tdb_filter_color' => '',
		'tdcf_service_description' => 'U29jaWFsIG1lZGlhIGlzIGEgcG93ZXJmdWwgdG9vbCBmb3IgYnVpbGRpbmcgeW91ciBicmFuZCwgZW5nYWdpbmcgd2l0aCB5b3VyIGF1ZGllbmNlLCBhbmQgZHJpdmluZyB0cmFmZmljIHRvIHlvdXIgd2Vic2l0ZS4gT3VyIHNvY2lhbCBtZWRpYSBtYXJrZXRpbmcgc2VydmljZSBwcm92aWRlcyBhIGNvbXByZWhlbnNpdmUgc29sdXRpb24gdG8gaGVscCB5b3UgY3JlYXRlIGFuZCBleGVjdXRlIHN1Y2Nlc3NmdWwgc29jaWFsIG1lZGlhIGNhbXBhaWducy4NCjxoMz5TdHJhdGVneTo8L2gzPg0KT3VyIHRlYW0gb2Ygc29jaWFsIG1lZGlhIGV4cGVydHMgd2lsbCB3b3JrIHdpdGggeW91IHRvIGRldmVsb3AgYSBzb2NpYWwgbWVkaWEgc3RyYXRlZ3kgdGhhdCBhbGlnbnMgd2l0aCB5b3VyIGJ1c2luZXNzIGdvYWxzIGFuZCB0YXJnZXQgYXVkaWVuY2UuIFdlIHdpbGwgaWRlbnRpZnkgdGhlIGJlc3Qgc29jaWFsIG1lZGlhIHBsYXRmb3JtcyBmb3IgeW91ciBidXNpbmVzcyBhbmQgY3JlYXRlIGEgbWVzc2FnaW5nIHN0cmF0ZWd5IHRoYXQgcmVzb25hdGVzIHdpdGggeW91ciBhdWRpZW5jZS4NCjxoMz5DYW1wYWlnbiBNYW5hZ2VtZW50OjwvaDM+DQpXZSB3aWxsIG1hbmFnZSB5b3VyIHNvY2lhbCBtZWRpYSBjYW1wYWlnbnMgZnJvbSBzdGFydCB0byBmaW5pc2gsIGluY2x1ZGluZyBjcmVhdGluZyBjb250ZW50LCBzY2hlZHVsaW5nIHBvc3RzLCBhbmQgbW9uaXRvcmluZyBwZXJmb3JtYW5jZS4gV2Ugd2lsbCBhbHNvIHByb3ZpZGUgb25nb2luZyBvcHRpbWl6YXRpb24gdG8gZW5zdXJlIHRoYXQgeW91ciBjYW1wYWlnbnMgYXJlIHJ1bm5pbmcgYXQgcGVhayBwZXJmb3JtYW5jZS4NCjxoMz5NZXRyaWNzIGFuZCBSZXBvcnRpbmc6PC9oMz4NCldlIHdpbGwgdHJhY2sgdGhlIHBlcmZvcm1hbmNlIG9mIHlvdXIgc29jaWFsIG1lZGlhIGNhbXBhaWducyBhbmQgcHJvdmlkZSBkZXRhaWxlZCByZXBvcnRpbmcgb24gbWV0cmljcyBzdWNoIGFzIGVuZ2FnZW1lbnQgcmF0ZXMsIHJlYWNoLCBhbmQgY29udmVyc2lvbnMuIFdlIHdpbGwgYWxzbyBwcm92aWRlIHJlY29tbWVuZGF0aW9ucyBvbiBob3cgdG8gaW1wcm92ZSB5b3VyIHNvY2lhbCBtZWRpYSBjYW1wYWlnbnMgYmFzZWQgb24gdGhlIGRhdGEu',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'dGR4X3BpY185', // tdcf_demo_id:tdx_pic_9
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
	),
));

$tax_term_software_as_a_service_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Software as a service',
	'taxonomy' => 'tdtax_services',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'We build and manage customized software solutions to streamline your business operations and improve efficiency.',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdcf_service_summary' => '',
		'_tdcf_service_summary' => 'ZmllbGRfNjNmODY1ZWM2Yzc0MQ==',
		'tdcf_service_description' => 'U29mdHdhcmUgYXMgYSBTZXJ2aWNlIChTYWFTKSBhbGxvd3MgYnVzaW5lc3NlcyB0byBhY2Nlc3Mgc29mdHdhcmUgYXBwbGljYXRpb25zIG92ZXIgdGhlIGludGVybmV0IHJhdGhlciB0aGFuIGluc3RhbGxpbmcgYW5kIG1haW50YWluaW5nIHNvZnR3YXJlIG9uIHRoZWlyIG93biBjb21wdXRlcnMuIE91ciBTYWFTIGRldmVsb3BtZW50IHNlcnZpY2UgcHJvdmlkZXMgYSBjb21wcmVoZW5zaXZlIHNvbHV0aW9uIHRvIGhlbHAgeW91IGNyZWF0ZSBhbmQgbGF1bmNoIGEgc3VjY2Vzc2Z1bCBTYWFTIHByb2R1Y3QuDQo8aDM+RGVzaWduOjwvaDM+DQpPdXIgdGVhbSBvZiBza2lsbGVkIGRlc2lnbmVycyB3aWxsIHdvcmsgd2l0aCB5b3UgdG8gY3JlYXRlIGEgU2FhUyBwcm9kdWN0IGRlc2lnbiB0aGF0IGlzIGJvdGggdmlzdWFsbHkgYXBwZWFsaW5nIGFuZCB1c2VyLWZyaWVuZGx5LiBXZSB3aWxsIGFsc28gY29uZHVjdCB1c2VyIHJlc2VhcmNoIGFuZCBjcmVhdGUgd2lyZWZyYW1lcyBhbmQgcHJvdG90eXBlcyB0byBlbnN1cmUgdGhhdCB0aGUgZGVzaWduIG1lZXRzIHRoZSBuZWVkcyBvZiB5b3VyIHRhcmdldCBhdWRpZW5jZS4NCjxoMz5EZXZlbG9wbWVudDo8L2gzPg0KT3VyIHRlYW0gb2YgZXhwZXJpZW5jZWQgZGV2ZWxvcGVycyB3aWxsIHVzZSB0aGUgbGF0ZXN0IHRlY2hub2xvZ2llcyBhbmQgZGV2ZWxvcG1lbnQgbWV0aG9kb2xvZ2llcyB0byBidWlsZCB5b3VyIFNhYVMgcHJvZHVjdC4gV2Ugd2lsbCBhbHNvIGVuc3VyZSB0aGF0IHlvdXIgcHJvZHVjdCBpcyBzY2FsYWJsZSwgc2VjdXJlLCBhbmQgZWFzeSB0byBtYWludGFpbi4NCjxoMz5UZXN0aW5nIGFuZCBRdWFsaXR5IEFzc3VyYW5jZTo8L2gzPg0KV2Ugd2lsbCBjb25kdWN0IHJpZ29yb3VzIHRlc3RpbmcgdG8gZW5zdXJlIHRoYXQgeW91ciBTYWFTIHByb2R1Y3QgaXMgYnVnLWZyZWUgYW5kIHBlcmZvcm1zIGZsYXdsZXNzbHkuIFdlIHdpbGwgYWxzbyBwZXJmb3JtIG9uZ29pbmcgbWFpbnRlbmFuY2UgYW5kIHVwZGF0ZXMgdG8gZW5zdXJlIHRoYXQgeW91ciBwcm9kdWN0IHJlbWFpbnMgdXAtdG8tZGF0ZSBhbmQgc2VjdXJlLg==',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'MzYy',
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
		'tdb_filter_color' => '',
		'tdcf_service_description' => 'U29mdHdhcmUgYXMgYSBTZXJ2aWNlIChTYWFTKSBhbGxvd3MgYnVzaW5lc3NlcyB0byBhY2Nlc3Mgc29mdHdhcmUgYXBwbGljYXRpb25zIG92ZXIgdGhlIGludGVybmV0IHJhdGhlciB0aGFuIGluc3RhbGxpbmcgYW5kIG1haW50YWluaW5nIHNvZnR3YXJlIG9uIHRoZWlyIG93biBjb21wdXRlcnMuIE91ciBTYWFTIGRldmVsb3BtZW50IHNlcnZpY2UgcHJvdmlkZXMgYSBjb21wcmVoZW5zaXZlIHNvbHV0aW9uIHRvIGhlbHAgeW91IGNyZWF0ZSBhbmQgbGF1bmNoIGEgc3VjY2Vzc2Z1bCBTYWFTIHByb2R1Y3QuDQo8aDM+RGVzaWduOjwvaDM+DQpPdXIgdGVhbSBvZiBza2lsbGVkIGRlc2lnbmVycyB3aWxsIHdvcmsgd2l0aCB5b3UgdG8gY3JlYXRlIGEgU2FhUyBwcm9kdWN0IGRlc2lnbiB0aGF0IGlzIGJvdGggdmlzdWFsbHkgYXBwZWFsaW5nIGFuZCB1c2VyLWZyaWVuZGx5LiBXZSB3aWxsIGFsc28gY29uZHVjdCB1c2VyIHJlc2VhcmNoIGFuZCBjcmVhdGUgd2lyZWZyYW1lcyBhbmQgcHJvdG90eXBlcyB0byBlbnN1cmUgdGhhdCB0aGUgZGVzaWduIG1lZXRzIHRoZSBuZWVkcyBvZiB5b3VyIHRhcmdldCBhdWRpZW5jZS4NCjxoMz5EZXZlbG9wbWVudDo8L2gzPg0KT3VyIHRlYW0gb2YgZXhwZXJpZW5jZWQgZGV2ZWxvcGVycyB3aWxsIHVzZSB0aGUgbGF0ZXN0IHRlY2hub2xvZ2llcyBhbmQgZGV2ZWxvcG1lbnQgbWV0aG9kb2xvZ2llcyB0byBidWlsZCB5b3VyIFNhYVMgcHJvZHVjdC4gV2Ugd2lsbCBhbHNvIGVuc3VyZSB0aGF0IHlvdXIgcHJvZHVjdCBpcyBzY2FsYWJsZSwgc2VjdXJlLCBhbmQgZWFzeSB0byBtYWludGFpbi4NCjxoMz5UZXN0aW5nIGFuZCBRdWFsaXR5IEFzc3VyYW5jZTo8L2gzPg0KV2Ugd2lsbCBjb25kdWN0IHJpZ29yb3VzIHRlc3RpbmcgdG8gZW5zdXJlIHRoYXQgeW91ciBTYWFTIHByb2R1Y3QgaXMgYnVnLWZyZWUgYW5kIHBlcmZvcm1zIGZsYXdsZXNzbHkuIFdlIHdpbGwgYWxzbyBwZXJmb3JtIG9uZ29pbmcgbWFpbnRlbmFuY2UgYW5kIHVwZGF0ZXMgdG8gZW5zdXJlIHRoYXQgeW91ciBwcm9kdWN0IHJlbWFpbnMgdXAtdG8tZGF0ZSBhbmQgc2VjdXJlLg==',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'dGR4X3BpY18xMA==', // tdcf_demo_id:tdx_pic_10
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
	),
));

$tax_term_strategy_consulting_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Strategy &amp; consulting',
	'taxonomy' => 'tdtax_services',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'We provide expert guidance and develop customized strategies to help you achieve your business goals.',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdcf_service_summary' => '',
		'_tdcf_service_summary' => 'ZmllbGRfNjNmODY1ZWM2Yzc0MQ==',
		'tdcf_service_description' => 'T3VyIHN0cmF0ZWd5IGFuZCBjb25zdWx0aW5nIHNlcnZpY2UgcHJvdmlkZXMgYSBjb21wcmVoZW5zaXZlIHNvbHV0aW9uIHRvIGhlbHAgeW91IGFjaGlldmUgeW91ciBidXNpbmVzcyBnb2Fscy4gV2Ugd2lsbCB3b3JrIHdpdGggeW91IHRvIGRldmVsb3AgYSBjdXN0b21pemVkIHN0cmF0ZWd5IHRoYXQgYWxpZ25zIHdpdGggeW91ciBidXNpbmVzcyBvYmplY3RpdmVzIGFuZCBoZWxwcyB5b3UgYWNoaWV2ZSBtZWFzdXJhYmxlIHJlc3VsdHMuDQo8aDM+QXNzZXNzbWVudDo8L2gzPg0KV2Ugd2lsbCBjb25kdWN0IGEgY29tcHJlaGVuc2l2ZSBhc3Nlc3NtZW50IG9mIHlvdXIgYnVzaW5lc3MgdG8gaWRlbnRpZnkgeW91ciBzdHJlbmd0aHMgYW5kIHdlYWtuZXNzZXMuIFdlIHdpbGwgYWxzbyBjb25kdWN0IG1hcmtldCByZXNlYXJjaCB0byBpZGVudGlmeSBvcHBvcnR1bml0aWVzIGFuZCB0aHJlYXRzIGluIHlvdXIgaW5kdXN0cnkuDQo8aDM+U3RyYXRlZ3kgRGV2ZWxvcG1lbnQ6PC9oMz4NCldlIHdpbGwgd29yayB3aXRoIHlvdSB0byBkZXZlbG9wIGEgc3RyYXRlZ3kgdGhhdCBhbGlnbnMgd2l0aCB5b3VyIGJ1c2luZXNzIGdvYWxzIGFuZCB0YXJnZXQgYXVkaWVuY2UuIFdlIHdpbGwgYWxzbyBkZXZlbG9wIGEgcm9hZG1hcCBhbmQgYWN0aW9uIHBsYW4gdGhhdCBvdXRsaW5lcyB0aGUgc3RlcHMgbmVlZGVkIHRvIGFjaGlldmUgeW91ciBvYmplY3RpdmVzLg0KPGgzPkltcGxlbWVudGF0aW9uOjwvaDM+DQpXZSB3aWxsIHdvcmsgd2l0aCB5b3UgdG8gaW1wbGVtZW50IHlvdXIgc3RyYXRlZ3kgYW5kIGVuc3VyZSB0aGF0IGl0IGlzIGV4ZWN1dGVkIGZsYXdsZXNzbHkuIFdlIHdpbGwgYWxzbyBtb25pdG9yIHBlcmZvcm1hbmNlIGFuZCBwcm92aWRlIG9uZ29pbmcgb3B0aW1pemF0aW9uIHRvIGVuc3VyZSB0aGF0IHlvdXIgc3RyYXRlZ3kgY29udGludWVzIHRvIGRlbGl2ZXIgcmVzdWx0cy4=',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'MzYx',
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
		'tdb_filter_color' => '',
		'tdcf_service_description' => 'T3VyIHN0cmF0ZWd5IGFuZCBjb25zdWx0aW5nIHNlcnZpY2UgcHJvdmlkZXMgYSBjb21wcmVoZW5zaXZlIHNvbHV0aW9uIHRvIGhlbHAgeW91IGFjaGlldmUgeW91ciBidXNpbmVzcyBnb2Fscy4gV2Ugd2lsbCB3b3JrIHdpdGggeW91IHRvIGRldmVsb3AgYSBjdXN0b21pemVkIHN0cmF0ZWd5IHRoYXQgYWxpZ25zIHdpdGggeW91ciBidXNpbmVzcyBvYmplY3RpdmVzIGFuZCBoZWxwcyB5b3UgYWNoaWV2ZSBtZWFzdXJhYmxlIHJlc3VsdHMuDQo8aDM+QXNzZXNzbWVudDo8L2gzPg0KV2Ugd2lsbCBjb25kdWN0IGEgY29tcHJlaGVuc2l2ZSBhc3Nlc3NtZW50IG9mIHlvdXIgYnVzaW5lc3MgdG8gaWRlbnRpZnkgeW91ciBzdHJlbmd0aHMgYW5kIHdlYWtuZXNzZXMuIFdlIHdpbGwgYWxzbyBjb25kdWN0IG1hcmtldCByZXNlYXJjaCB0byBpZGVudGlmeSBvcHBvcnR1bml0aWVzIGFuZCB0aHJlYXRzIGluIHlvdXIgaW5kdXN0cnkuDQo8aDM+U3RyYXRlZ3kgRGV2ZWxvcG1lbnQ6PC9oMz4NCldlIHdpbGwgd29yayB3aXRoIHlvdSB0byBkZXZlbG9wIGEgc3RyYXRlZ3kgdGhhdCBhbGlnbnMgd2l0aCB5b3VyIGJ1c2luZXNzIGdvYWxzIGFuZCB0YXJnZXQgYXVkaWVuY2UuIFdlIHdpbGwgYWxzbyBkZXZlbG9wIGEgcm9hZG1hcCBhbmQgYWN0aW9uIHBsYW4gdGhhdCBvdXRsaW5lcyB0aGUgc3RlcHMgbmVlZGVkIHRvIGFjaGlldmUgeW91ciBvYmplY3RpdmVzLg0KPGgzPkltcGxlbWVudGF0aW9uOjwvaDM+DQpXZSB3aWxsIHdvcmsgd2l0aCB5b3UgdG8gaW1wbGVtZW50IHlvdXIgc3RyYXRlZ3kgYW5kIGVuc3VyZSB0aGF0IGl0IGlzIGV4ZWN1dGVkIGZsYXdsZXNzbHkuIFdlIHdpbGwgYWxzbyBtb25pdG9yIHBlcmZvcm1hbmNlIGFuZCBwcm92aWRlIG9uZ29pbmcgb3B0aW1pemF0aW9uIHRvIGVuc3VyZSB0aGF0IHlvdXIgc3RyYXRlZ3kgY29udGludWVzIHRvIGRlbGl2ZXIgcmVzdWx0cy4=',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'dGR4X3BpY18xMQ==', // tdcf_demo_id:tdx_pic_11
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
	),
));

$tax_term_ux_ui_design_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'UX/UI design',
	'taxonomy' => 'tdtax_services',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'We design intuitive and user-friendly interfaces that improve the user experience and drive engagement.',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdcf_service_summary' => '',
		'_tdcf_service_summary' => 'ZmllbGRfNjNmODY1ZWM2Yzc0MQ==',
		'tdcf_service_description' => 'VXNlciBleHBlcmllbmNlIChVWCkgYW5kIHVzZXIgaW50ZXJmYWNlIChVSSkgZGVzaWduIGFyZSBlc3NlbnRpYWwgY29tcG9uZW50cyBvZiBhIHN1Y2Nlc3NmdWwgd2Vic2l0ZSBvciBkaWdpdGFsIHByb2R1Y3QuIE91ciBVWC9VSSBkZXNpZ24gc2VydmljZSBwcm92aWRlcyBhIGNvbXByZWhlbnNpdmUgc29sdXRpb24gdG8gaGVscCB5b3UgY3JlYXRlIGEgdmlzdWFsbHkgYXBwZWFsaW5nIGFuZCB1c2VyLWZyaWVuZGx5IGRpZ2l0YWwgcHJvZHVjdC4NCjxoMz5Vc2VyIFJlc2VhcmNoOjwvaDM+DQpXZSB3aWxsIGNvbmR1Y3QgdXNlciByZXNlYXJjaCB0byB1bmRlcnN0YW5kIHRoZSBuZWVkcyBhbmQgcHJlZmVyZW5jZXMgb2YgeW91ciB0YXJnZXQgYXVkaWVuY2UuIFRoaXMgcmVzZWFyY2ggd2lsbCBpbmZvcm0gdGhlIGRlc2lnbiBvZiB5b3VyIGRpZ2l0YWwgcHJvZHVjdCBhbmQgZW5zdXJlIHRoYXQgaXQgbWVldHMgdGhlIG5lZWRzIG9mIHlvdXIgdXNlcnMuDQo8aDM+V2lyZWZyYW1pbmcgYW5kIFByb3RvdHlwaW5nOjwvaDM+DQpXZSB3aWxsIGNyZWF0ZSB3aXJlZnJhbWVzIGFuZCBwcm90b3R5cGVzIHRvIHZpc3VhbGl6ZSB0aGUgZGVzaWduIG9mIHlvdXIgZGlnaXRhbCBwcm9kdWN0IGFuZCBlbnN1cmUgdGhhdCBpdCBtZWV0cyB0aGUgbmVlZHMgb2YgeW91ciB1c2Vycy4gV2Ugd2lsbCBhbHNvIGNvbmR1Y3QgdXNlciB0ZXN0aW5nIHRvIHZhbGlkYXRlIHRoZSBkZXNpZ24gYW5kIG1ha2UgbmVjZXNzYXJ5IGltcHJvdmVtZW50cy4NCjxoMz5WaXN1YWwgRGVzaWduOjwvaDM+DQpPdXIgdGVhbSBvZiBza2lsbGVkIGRlc2lnbmVycyB3aWxsIGNyZWF0ZSBhIHZpc3VhbGx5IGFwcGVhbGluZyBkZXNpZ24gZm9yIHlvdXIgZGlnaXRhbCBwcm9kdWN0IHRoYXQgYWxpZ25zIHdpdGggeW91ciBicmFuZCBhbmQgdGFyZ2V0IGF1ZGllbmNlLiBXZSB3aWxsIGFsc28gZW5zdXJlIHRoYXQgdGhlIGRlc2lnbiBpcyBjb25zaXN0ZW50IGFuZCBlYXN5IHRvIG5hdmlnYXRlLg==',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'MzYw',
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
		'tdb_filter_color' => '',
		'tdcf_service_description' => 'VXNlciBleHBlcmllbmNlIChVWCkgYW5kIHVzZXIgaW50ZXJmYWNlIChVSSkgZGVzaWduIGFyZSBlc3NlbnRpYWwgY29tcG9uZW50cyBvZiBhIHN1Y2Nlc3NmdWwgd2Vic2l0ZSBvciBkaWdpdGFsIHByb2R1Y3QuIE91ciBVWC9VSSBkZXNpZ24gc2VydmljZSBwcm92aWRlcyBhIGNvbXByZWhlbnNpdmUgc29sdXRpb24gdG8gaGVscCB5b3UgY3JlYXRlIGEgdmlzdWFsbHkgYXBwZWFsaW5nIGFuZCB1c2VyLWZyaWVuZGx5IGRpZ2l0YWwgcHJvZHVjdC4NCjxoMz5Vc2VyIFJlc2VhcmNoOjwvaDM+DQpXZSB3aWxsIGNvbmR1Y3QgdXNlciByZXNlYXJjaCB0byB1bmRlcnN0YW5kIHRoZSBuZWVkcyBhbmQgcHJlZmVyZW5jZXMgb2YgeW91ciB0YXJnZXQgYXVkaWVuY2UuIFRoaXMgcmVzZWFyY2ggd2lsbCBpbmZvcm0gdGhlIGRlc2lnbiBvZiB5b3VyIGRpZ2l0YWwgcHJvZHVjdCBhbmQgZW5zdXJlIHRoYXQgaXQgbWVldHMgdGhlIG5lZWRzIG9mIHlvdXIgdXNlcnMuDQo8aDM+V2lyZWZyYW1pbmcgYW5kIFByb3RvdHlwaW5nOjwvaDM+DQpXZSB3aWxsIGNyZWF0ZSB3aXJlZnJhbWVzIGFuZCBwcm90b3R5cGVzIHRvIHZpc3VhbGl6ZSB0aGUgZGVzaWduIG9mIHlvdXIgZGlnaXRhbCBwcm9kdWN0IGFuZCBlbnN1cmUgdGhhdCBpdCBtZWV0cyB0aGUgbmVlZHMgb2YgeW91ciB1c2Vycy4gV2Ugd2lsbCBhbHNvIGNvbmR1Y3QgdXNlciB0ZXN0aW5nIHRvIHZhbGlkYXRlIHRoZSBkZXNpZ24gYW5kIG1ha2UgbmVjZXNzYXJ5IGltcHJvdmVtZW50cy4NCjxoMz5WaXN1YWwgRGVzaWduOjwvaDM+DQpPdXIgdGVhbSBvZiBza2lsbGVkIGRlc2lnbmVycyB3aWxsIGNyZWF0ZSBhIHZpc3VhbGx5IGFwcGVhbGluZyBkZXNpZ24gZm9yIHlvdXIgZGlnaXRhbCBwcm9kdWN0IHRoYXQgYWxpZ25zIHdpdGggeW91ciBicmFuZCBhbmQgdGFyZ2V0IGF1ZGllbmNlLiBXZSB3aWxsIGFsc28gZW5zdXJlIHRoYXQgdGhlIGRlc2lnbiBpcyBjb25zaXN0ZW50IGFuZCBlYXN5IHRvIG5hdmlnYXRlLg==',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'dGR4X3BpY18xMg==', // tdcf_demo_id:tdx_pic_12
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
	),
));

$tax_term_web_design_development_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Web design &amp; development',
	'taxonomy' => 'tdtax_services',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'We build visually stunning and highly functional websites that improve your online presence.',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdcf_service_summary' => '',
		'_tdcf_service_summary' => 'ZmllbGRfNjNmODY1ZWM2Yzc0MQ==',
		'tdcf_service_description' => 'T3VyIHdlYiBkZXNpZ24gYW5kIGRldmVsb3BtZW50IHNlcnZpY2UgcHJvdmlkZXMgYSBjb21wcmVoZW5zaXZlIHNvbHV0aW9uIHRvIGhlbHAgeW91IGNyZWF0ZSBhIHdlYnNpdGUgdGhhdCBpcyB2aXN1YWxseSBhcHBlYWxpbmcsIHVzZXItZnJpZW5kbHksIGFuZCBvcHRpbWl6ZWQgZm9yIGNvbnZlcnNpb25zLg0KPGgzPkRlc2lnbjo8L2gzPg0KT3VyIHRlYW0gb2Ygc2tpbGxlZCBkZXNpZ25lcnMgd2lsbCB3b3JrIHdpdGggeW91IHRvIGNyZWF0ZSBhIHdlYnNpdGUgZGVzaWduIHRoYXQgYWxpZ25zIHdpdGggeW91ciBicmFuZCBhbmQgdGFyZ2V0IGF1ZGllbmNlLiBXZSB3aWxsIGFsc28gZW5zdXJlIHRoYXQgdGhlIGRlc2lnbiBpcyBjb25zaXN0ZW50IGFuZCBlYXN5IHRvIG5hdmlnYXRlLg0KPGgzPkRldmVsb3BtZW50OjwvaDM+DQpPdXIgdGVhbSBvZiBleHBlcmllbmNlZCBkZXZlbG9wZXJzIHdpbGwgdXNlIHRoZSBsYXRlc3QgdGVjaG5vbG9naWVzIGFuZCBkZXZlbG9wbWVudCBtZXRob2RvbG9naWVzIHRvIGJ1aWxkIHlvdXIgd2Vic2l0ZS4gV2Ugd2lsbCBhbHNvIGVuc3VyZSB0aGF0IHlvdXIgd2Vic2l0ZSBpcyBvcHRpbWl6ZWQgZm9yIHNlYXJjaCBlbmdpbmVzIGFuZCBjb252ZXJzaW9ucy4NCjxoMz5UZXN0aW5nIGFuZCBRdWFsaXR5IEFzc3VyYW5jZTo8L2gzPg0KV2Ugd2lsbCBjb25kdWN0IHJpZ29yb3VzIHRlc3RpbmcgdG8gZW5zdXJlIHRoYXQgeW91ciB3ZWJzaXRlIGlzIGJ1Zy1mcmVlIGFuZCBwZXJmb3JtcyBmbGF3bGVzc2x5LiBXZSB3aWxsIGFsc28gcGVyZm9ybSBvbmdvaW5nIG1haW50ZW5hbmNlIGFuZCB1cGRhdGVzIHRvIGVuc3VyZSB0aGF0IHlvdXIgd2Vic2l0ZSByZW1haW5zIHVwLXRvLWRhdGUgYW5kIHNlY3VyZS4=',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'MzU5',
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
		'tdb_filter_color' => '',
		'tdcf_service_description' => 'T3VyIHdlYiBkZXNpZ24gYW5kIGRldmVsb3BtZW50IHNlcnZpY2UgcHJvdmlkZXMgYSBjb21wcmVoZW5zaXZlIHNvbHV0aW9uIHRvIGhlbHAgeW91IGNyZWF0ZSBhIHdlYnNpdGUgdGhhdCBpcyB2aXN1YWxseSBhcHBlYWxpbmcsIHVzZXItZnJpZW5kbHksIGFuZCBvcHRpbWl6ZWQgZm9yIGNvbnZlcnNpb25zLg0KPGgzPkRlc2lnbjo8L2gzPg0KT3VyIHRlYW0gb2Ygc2tpbGxlZCBkZXNpZ25lcnMgd2lsbCB3b3JrIHdpdGggeW91IHRvIGNyZWF0ZSBhIHdlYnNpdGUgZGVzaWduIHRoYXQgYWxpZ25zIHdpdGggeW91ciBicmFuZCBhbmQgdGFyZ2V0IGF1ZGllbmNlLiBXZSB3aWxsIGFsc28gZW5zdXJlIHRoYXQgdGhlIGRlc2lnbiBpcyBjb25zaXN0ZW50IGFuZCBlYXN5IHRvIG5hdmlnYXRlLg0KPGgzPkRldmVsb3BtZW50OjwvaDM+DQpPdXIgdGVhbSBvZiBleHBlcmllbmNlZCBkZXZlbG9wZXJzIHdpbGwgdXNlIHRoZSBsYXRlc3QgdGVjaG5vbG9naWVzIGFuZCBkZXZlbG9wbWVudCBtZXRob2RvbG9naWVzIHRvIGJ1aWxkIHlvdXIgd2Vic2l0ZS4gV2Ugd2lsbCBhbHNvIGVuc3VyZSB0aGF0IHlvdXIgd2Vic2l0ZSBpcyBvcHRpbWl6ZWQgZm9yIHNlYXJjaCBlbmdpbmVzIGFuZCBjb252ZXJzaW9ucy4NCjxoMz5UZXN0aW5nIGFuZCBRdWFsaXR5IEFzc3VyYW5jZTo8L2gzPg0KV2Ugd2lsbCBjb25kdWN0IHJpZ29yb3VzIHRlc3RpbmcgdG8gZW5zdXJlIHRoYXQgeW91ciB3ZWJzaXRlIGlzIGJ1Zy1mcmVlIGFuZCBwZXJmb3JtcyBmbGF3bGVzc2x5LiBXZSB3aWxsIGFsc28gcGVyZm9ybSBvbmdvaW5nIG1haW50ZW5hbmNlIGFuZCB1cGRhdGVzIHRvIGVuc3VyZSB0aGF0IHlvdXIgd2Vic2l0ZSByZW1haW5zIHVwLXRvLWRhdGUgYW5kIHNlY3VyZS4=',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'dGR4X3BpY18xMw==', // tdcf_demo_id:tdx_pic_13
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
	),
));

$tax_term_wordpress_web_design_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Wordpress web design',
	'taxonomy' => 'tdtax_services',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'We build customized WordPress websites that are optimized for search engines and deliver a great user experience.',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdcf_service_summary' => '',
		'_tdcf_service_summary' => 'ZmllbGRfNjNmODY1ZWM2Yzc0MQ==',
		'tdcf_service_description' => 'T3VyIFdvcmRQcmVzcyB3ZWIgZGVzaWduIHNlcnZpY2UgcHJvdmlkZXMgYSBjb21wcmVoZW5zaXZlIHNvbHV0aW9uIHRvIGhlbHAgeW91IGNyZWF0ZSBhIHZpc3VhbGx5IGFwcGVhbGluZyBhbmQgdXNlci1mcmllbmRseSBXb3JkUHJlc3Mgd2Vic2l0ZS4NCjxoMz5EZXNpZ246PC9oMz4NCk91ciB0ZWFtIG9mIHNraWxsZWQgZGVzaWduZXJzIHdpbGwgd29yayB3aXRoIHlvdSB0byBjcmVhdGUgYSBXb3JkUHJlc3Mgd2Vic2l0ZSBkZXNpZ24gdGhhdCBhbGlnbnMgd2l0aCB5b3VyIGJyYW5kIGFuZCB0YXJnZXQgYXVkaWVuY2UuIFdlIHdpbGwgYWxzbyBlbnN1cmUgdGhhdCB0aGUgZGVzaWduIGlzIGNvbnNpc3RlbnQgYW5kIGVhc3kgdG8gbmF2aWdhdGUuDQo8aDM+RGV2ZWxvcG1lbnQ6PC9oMz4NCk91ciB0ZWFtIG9mIGV4cGVyaWVuY2VkIGRldmVsb3BlcnMgd2lsbCB1c2UgdGhlIGxhdGVzdCBXb3JkUHJlc3MgZGV2ZWxvcG1lbnQgbWV0aG9kb2xvZ2llcyBhbmQgcGx1Z2lucyB0byBidWlsZCB5b3VyIHdlYnNpdGUuIFdlIHdpbGwgYWxzbyBlbnN1cmUgdGhhdCB5b3VyIHdlYnNpdGUgaXMgb3B0aW1pemVkIGZvciBzZWFyY2ggZW5naW5lcyBhbmQgY29udmVyc2lvbnMuDQo8aDM+VGVzdGluZyBhbmQgUXVhbGl0eSBBc3N1cmFuY2U6PC9oMz4NCldlIHdpbGwgY29uZHVjdCByaWdvcm91cyB0ZXN0aW5nIHRvIGVuc3VyZSB0aGF0IHlvdXIgV29yZFByZXNzIHdlYnNpdGUgaXMgYnVnLWZyZWUgYW5kIHBlcmZvcm1zIGZsYXdsZXNzbHkuIFdlIHdpbGwgYWxzbyBwZXJmb3JtIG9uZ29pbmcgbWFpbnRlbmFuY2UgYW5kIHVwZGF0ZXMgdG8gZW5zdXJlIHRoYXQgeW91ciB3ZWJzaXRlIHJlbWFpbnMgdXAtdG8tZGF0ZSBhbmQgc2VjdXJlLg0KPGgzPlBsdWdpbnMgYW5kIEN1c3RvbWl6YXRpb246PC9oMz4NCldlIHdpbGwgaGVscCB5b3UgY2hvb3NlIHRoZSByaWdodCBwbHVnaW5zIGFuZCBjdXN0b21pemF0aW9uIG9wdGlvbnMgdG8gZW5oYW5jZSB0aGUgZnVuY3Rpb25hbGl0eSBvZiB5b3VyIFdvcmRQcmVzcyB3ZWJzaXRlLiBUaGlzIHdpbGwgaW5jbHVkZSBmZWF0dXJlcyBzdWNoIGFzIGNvbnRhY3QgZm9ybXMsIHNvY2lhbCBtZWRpYSBpbnRlZ3JhdGlvbiwgYW5kIGUtY29tbWVyY2UgY2FwYWJpbGl0aWVzLg0KPGgzPlNFTyBPcHRpbWl6YXRpb246PC9oMz4NCldlIHdpbGwgb3B0aW1pemUgeW91ciBXb3JkUHJlc3Mgd2Vic2l0ZSBmb3Igc2VhcmNoIGVuZ2luZXMgdG8gZW5zdXJlIHRoYXQgeW91ciB3ZWJzaXRlIGlzIHZpc2libGUgdG8geW91ciB0YXJnZXQgYXVkaWVuY2UuIFRoaXMgd2lsbCBpbmNsdWRlIGtleXdvcmQgcmVzZWFyY2gsIG9uLXBhZ2Ugb3B0aW1pemF0aW9uLCBhbmQgYmFja2xpbmsgYnVpbGRpbmcu',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'MzU4',
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
		'tdb_filter_color' => '',
		'tdcf_service_description' => 'T3VyIFdvcmRQcmVzcyB3ZWIgZGVzaWduIHNlcnZpY2UgcHJvdmlkZXMgYSBjb21wcmVoZW5zaXZlIHNvbHV0aW9uIHRvIGhlbHAgeW91IGNyZWF0ZSBhIHZpc3VhbGx5IGFwcGVhbGluZyBhbmQgdXNlci1mcmllbmRseSBXb3JkUHJlc3Mgd2Vic2l0ZS4NCjxoMz5EZXNpZ246PC9oMz4NCk91ciB0ZWFtIG9mIHNraWxsZWQgZGVzaWduZXJzIHdpbGwgd29yayB3aXRoIHlvdSB0byBjcmVhdGUgYSBXb3JkUHJlc3Mgd2Vic2l0ZSBkZXNpZ24gdGhhdCBhbGlnbnMgd2l0aCB5b3VyIGJyYW5kIGFuZCB0YXJnZXQgYXVkaWVuY2UuIFdlIHdpbGwgYWxzbyBlbnN1cmUgdGhhdCB0aGUgZGVzaWduIGlzIGNvbnNpc3RlbnQgYW5kIGVhc3kgdG8gbmF2aWdhdGUuDQo8aDM+RGV2ZWxvcG1lbnQ6PC9oMz4NCk91ciB0ZWFtIG9mIGV4cGVyaWVuY2VkIGRldmVsb3BlcnMgd2lsbCB1c2UgdGhlIGxhdGVzdCBXb3JkUHJlc3MgZGV2ZWxvcG1lbnQgbWV0aG9kb2xvZ2llcyBhbmQgcGx1Z2lucyB0byBidWlsZCB5b3VyIHdlYnNpdGUuIFdlIHdpbGwgYWxzbyBlbnN1cmUgdGhhdCB5b3VyIHdlYnNpdGUgaXMgb3B0aW1pemVkIGZvciBzZWFyY2ggZW5naW5lcyBhbmQgY29udmVyc2lvbnMuDQo8aDM+VGVzdGluZyBhbmQgUXVhbGl0eSBBc3N1cmFuY2U6PC9oMz4NCldlIHdpbGwgY29uZHVjdCByaWdvcm91cyB0ZXN0aW5nIHRvIGVuc3VyZSB0aGF0IHlvdXIgV29yZFByZXNzIHdlYnNpdGUgaXMgYnVnLWZyZWUgYW5kIHBlcmZvcm1zIGZsYXdsZXNzbHkuIFdlIHdpbGwgYWxzbyBwZXJmb3JtIG9uZ29pbmcgbWFpbnRlbmFuY2UgYW5kIHVwZGF0ZXMgdG8gZW5zdXJlIHRoYXQgeW91ciB3ZWJzaXRlIHJlbWFpbnMgdXAtdG8tZGF0ZSBhbmQgc2VjdXJlLg0KPGgzPlBsdWdpbnMgYW5kIEN1c3RvbWl6YXRpb246PC9oMz4NCldlIHdpbGwgaGVscCB5b3UgY2hvb3NlIHRoZSByaWdodCBwbHVnaW5zIGFuZCBjdXN0b21pemF0aW9uIG9wdGlvbnMgdG8gZW5oYW5jZSB0aGUgZnVuY3Rpb25hbGl0eSBvZiB5b3VyIFdvcmRQcmVzcyB3ZWJzaXRlLiBUaGlzIHdpbGwgaW5jbHVkZSBmZWF0dXJlcyBzdWNoIGFzIGNvbnRhY3QgZm9ybXMsIHNvY2lhbCBtZWRpYSBpbnRlZ3JhdGlvbiwgYW5kIGUtY29tbWVyY2UgY2FwYWJpbGl0aWVzLg0KPGgzPlNFTyBPcHRpbWl6YXRpb246PC9oMz4NCldlIHdpbGwgb3B0aW1pemUgeW91ciBXb3JkUHJlc3Mgd2Vic2l0ZSBmb3Igc2VhcmNoIGVuZ2luZXMgdG8gZW5zdXJlIHRoYXQgeW91ciB3ZWJzaXRlIGlzIHZpc2libGUgdG8geW91ciB0YXJnZXQgYXVkaWVuY2UuIFRoaXMgd2lsbCBpbmNsdWRlIGtleXdvcmQgcmVzZWFyY2gsIG9uLXBhZ2Ugb3B0aW1pemF0aW9uLCBhbmQgYmFja2xpbmsgYnVpbGRpbmcu',
		'_tdcf_service_description' => 'ZmllbGRfNjNmZTE5ZDZjOTU4ZQ==',
		'tdcf_service_icon' => 'dGR4X3BpY18xNA==', // tdcf_demo_id:tdx_pic_14
		'_tdcf_service_icon' => 'ZmllbGRfNjNmODY2MzlkM2MzNg==',
	),
));


/*  ---------------------------------------------------------------------------- 
	CPTs
*/
$cpt_kids_fashion_store_id = td_demo_content::add_cpt( array(
	'title' => 'Kids Fashion Store',
	'type' => 'tdcpt_case_studies',
	'file' => 'tdcpt_case_studies_default.txt',
	'cpt_image_td_id' => 'tdx_pic_15',
	'post_meta' => array( 
		'tdcf_services' => '',
		'_tdcf_services' => 'ZmllbGRfNjNmNWUwNzk0ZWM2MQ==',
		'tdcf_url' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vbmV3c3BhcGVyX3Nob3Bfa2lkc19zdG9yZS8=',
		'_tdcf_url' => 'ZmllbGRfNjNmNWUyMWI1MjE1OQ==',
		'tdcf_image_1' => 'dGR4X3BpY18xNQ==', // tdcf_demo_id:tdx_pic_15
		'_tdcf_image_1' => 'ZmllbGRfNjNmNWUyNmU3NGIzZg==',
		'tdcf_image_2' => 'dGR4X3BpY18xNg==', // tdcf_demo_id:tdx_pic_16
		'_tdcf_image_2' => 'ZmllbGRfNjNmNWUyZTM1NjdmMQ==',
		'tdcf_image_3' => 'dGR4X3BpY18xNw==', // tdcf_demo_id:tdx_pic_17
		'_tdcf_image_3' => 'ZmllbGRfNjNmNWUyZWY1NjcyMw==',
		'tdcf_summary' => 'T3VyIHNvbHV0aW9uIGZvciB0aGlzIGZyaWVuZGx5IGUtY29tbWVyY2UgcGxhdGZvcm0gaGVscHMgdGhlIHRlYW0gY3JlYXRlIGFuZCBtYW5hZ2UgcHJvZHVjdHMgJmFtcDsgY29udGVudCBhbmQgYWxzbyBkZWxpdmVycyBhIGJlYXV0aWZ1bCBzaG9wcGluZyBleHBlcmllbmNlIGZvciB0aGUgdmlzaXRvcnMgZnJvbSBhbGwgZGV2aWNlcy4=',
		'_tdcf_summary' => 'ZmllbGRfNjNmNWUzMDliMDlkMw==',
		'tdcf_logo' => 'dGR4X3BpY18xOA==', // tdcf_demo_id:tdx_pic_18
		'_tdcf_logo' => 'ZmllbGRfNjNmNWUzNDkzMDVlOQ==',
		'tdcf_testimonial' => 'TW9tZW50dW0gaXMgdGhlIGJlc3QgbWFya2V0aW5nIGFuZCB3ZWIgZGVzaWduIGFnZW5jeSBJJ3ZlIHdvcmtlZCB3aXRoLiBUaGVpciB3ZWJzaXRlIGV4Y2VlZGVkIGV4cGVjdGF0aW9ucywgYW5kIHRoZWlyIG9uZ29pbmcgbWFya2V0aW5nIHNlcnZpY2VzIGhhdmUgYmVlbiBpbnN0cnVtZW50YWwgaW4gZ3Jvd2luZyBvdXIgYnVzaW5lc3Mu',
		'_tdcf_testimonial' => 'ZmllbGRfNjNmNWUzYmRlMDg3Zg==',
		'tdcf_scribble' => 'dGR4X3BpY18xOQ==', // tdcf_demo_id:tdx_pic_19
		'_tdcf_scribble' => 'ZmllbGRfNjQwNzQxY2I3ZjhhYw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_services' => array( $tax_term_branding_visual_design_id, $tax_term_e_commerce_development_id, $tax_term_pay_per_click_management_id, $tax_term_ux_ui_design_id ),
	),
));

$cpt_web3_defi_platform_id = td_demo_content::add_cpt( array(
	'title' => 'Web3 DeFi Platform',
	'type' => 'tdcpt_case_studies',
	'file' => 'tdcpt_case_studies_default.txt',
	'cpt_image_td_id' => 'tdx_pic_20',
	'post_meta' => array( 
		'tdcf_services' => '',
		'_tdcf_services' => 'ZmllbGRfNjNmNWUwNzk0ZWM2MQ==',
		'tdcf_url' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vbmV3c3BhcGVyX2Jsb2NrY2hhaW5fcHJvLw==',
		'_tdcf_url' => 'ZmllbGRfNjNmNWUyMWI1MjE1OQ==',
		'tdcf_image_1' => 'dGR4X3BpY18yMA==', // tdcf_demo_id:tdx_pic_20
		'_tdcf_image_1' => 'ZmllbGRfNjNmNWUyNmU3NGIzZg==',
		'tdcf_image_2' => 'dGR4X3BpY18yMQ==', // tdcf_demo_id:tdx_pic_21
		'_tdcf_image_2' => 'ZmllbGRfNjNmNWUyZTM1NjdmMQ==',
		'tdcf_image_3' => 'dGR4X3BpY18yMg==', // tdcf_demo_id:tdx_pic_22
		'_tdcf_image_3' => 'ZmllbGRfNjNmNWUyZWY1NjcyMw==',
		'tdcf_summary' => 'QmxvY2tDaGFpbiBpcyBhIGR5bmFtaWMgY29udGVudCB3ZWJzaXRlIHBlcmZlY3QgZm9yIHN0YXJ0LXVwcywgYnVzaW5lc3NlcywgQ3J5cHRvLCBORlRzLCBuZXcgdGVjaG5vbG9naWVzLCBhbmQgbW9yZS4gU28gZGVzaWduIHlvdXIgd2Vic2l0ZSB3aXRoIGEgZmV3IGNsaWNrcyBhbmQgc3RhcnQgZ3Jvd2luZyB5b3VyIGJ1c2luZXNzIQ==',
		'_tdcf_summary' => 'ZmllbGRfNjNmNWUzMDliMDlkMw==',
		'tdcf_logo' => 'dGR4X3BpY18yMw==', // tdcf_demo_id:tdx_pic_23
		'_tdcf_logo' => 'ZmllbGRfNjNmNWUzNDkzMDVlOQ==',
		'tdcf_testimonial' => 'TW9tZW50dW0ncyB0ZWFtIGlzIGV4Y2VwdGlvbmFsISBUaGV5IGFyZSByZXNwb25zaXZlLCBwcm9mZXNzaW9uYWwsIGFuZCBkZWRpY2F0ZWQgdG8gdW5kZXJzdGFuZGluZyBvdXIgYnVzaW5lc3MgYW5kIGdvYWxzLiBUaGVpciB3ZWIgZGVzaWduIGFuZCBtYXJrZXRpbmcgc2VydmljZXMgaGF2ZSBiZWVuIGluc3RydW1lbnRhbCBpbiBvdXIgc3VjY2Vzcy4=',
		'_tdcf_testimonial' => 'ZmllbGRfNjNmNWUzYmRlMDg3Zg==',
		'tdcf_scribble' => 'dGR4X3BpY18yNA==', // tdcf_demo_id:tdx_pic_24
		'_tdcf_scribble' => 'ZmllbGRfNjQwNzQxY2I3ZjhhYw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_services' => array( $tax_term_branding_visual_design_id, $tax_term_social_media_marketing_id, $tax_term_ux_ui_design_id, $tax_term_wordpress_web_design_id ),
	),
));

$cpt_iflow_attendance_app_id = td_demo_content::add_cpt( array(
	'title' => 'HRiFlow Attendance App',
	'type' => 'tdcpt_case_studies',
	'file' => 'tdcpt_case_studies_default.txt',
	'cpt_image_td_id' => 'tdx_pic_27',
	'post_meta' => array( 
		'tdcf_services' => '',
		'_tdcf_services' => 'ZmllbGRfNjNmNWUwNzk0ZWM2MQ==',
		'tdcf_url' => 'aHR0cHM6Ly9ocmlmbG93LnJvL2Vu',
		'_tdcf_url' => 'ZmllbGRfNjNmNWUyMWI1MjE1OQ==',
		'tdcf_image_1' => 'dGR4X3BpY18yNQ==', // tdcf_demo_id:tdx_pic_25
		'_tdcf_image_1' => 'ZmllbGRfNjNmNWUyNmU3NGIzZg==',
		'tdcf_image_2' => 'dGR4X3BpY18yNg==', // tdcf_demo_id:tdx_pic_26
		'_tdcf_image_2' => 'ZmllbGRfNjNmNWUyZTM1NjdmMQ==',
		'tdcf_image_3' => 'dGR4X3BpY18yNw==', // tdcf_demo_id:tdx_pic_27
		'_tdcf_image_3' => 'ZmllbGRfNjNmNWUyZWY1NjcyMw==',
		'tdcf_summary' => 'VGhlIGlGbG93IGFwcCBhbmQgcHJlc2VudGF0aW9uIHdlYnNpdGUgd2FzIGEgbmV3IGNvbmNlcHQgZm9yIHRoZSBjbGllbnQuIFRoZXkgbmVlZGVkIGEgZnVsbHkgZnVuY3Rpb25hbCB3ZWItYmFzZWQgYXBwbGljYXRpb24gYW5kIGEgc3R1bm5pbmdseSBiZWF1dGlmdWwsIGVhc3kgdG8gbmF2aWdhdGUgd2Vic2l0ZS4=',
		'_tdcf_summary' => 'ZmllbGRfNjNmNWUzMDliMDlkMw==',
		'tdcf_logo' => 'dGR4X3BpY18yOA==', // tdcf_demo_id:tdx_pic_28
		'_tdcf_logo' => 'ZmllbGRfNjNmNWUzNDkzMDVlOQ==',
		'tdcf_testimonial' => 'TW9tZW50dW0ncyB3b3JrIGlzIHNlY29uZCB0byBub25lLiBUaGV5IGNyZWF0ZWQgYSB3ZWJzaXRlIHRoYXQgYmxldyB1cyBhd2F5LCBhbmQgdGhlaXIgbWFya2V0aW5nIHNlcnZpY2VzIGhhdmUgdGFrZW4gb3VyIGJ1c2luZXNzIHRvIHRoZSBuZXh0IGxldmVsLg==',
		'_tdcf_testimonial' => 'ZmllbGRfNjNmNWUzYmRlMDg3Zg==',
		'tdcf_scribble' => 'dGR4X3BpY18yOQ==', // tdcf_demo_id:tdx_pic_29
		'_tdcf_scribble' => 'ZmllbGRfNjQwNzQxY2I3ZjhhYw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_services' => array( $tax_term_content_copywriting_id, $tax_term_software_as_a_service_id, $tax_term_ux_ui_design_id, $tax_term_web_design_development_id ),
	),
));

$cpt_medical_directory_id = td_demo_content::add_cpt( array(
	'title' => 'Medical Directory',
	'type' => 'tdcpt_case_studies',
	'file' => 'tdcpt_case_studies_default.txt',
	'cpt_image_td_id' => 'tdx_pic_31',
	'post_meta' => array( 
		'tdcf_services' => '',
		'_tdcf_services' => 'ZmllbGRfNjNmNWUwNzk0ZWM2MQ==',
		'tdcf_url' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vbmV3c3BhcGVyX2RvY3RvcnNfcHJvLw==',
		'_tdcf_url' => 'ZmllbGRfNjNmNWUyMWI1MjE1OQ==',
		'tdcf_image_1' => 'dGR4X3BpY18zMA==', // tdcf_demo_id:tdx_pic_30
		'_tdcf_image_1' => 'ZmllbGRfNjNmNWUyNmU3NGIzZg==',
		'tdcf_image_2' => 'dGR4X3BpY18zMQ==', // tdcf_demo_id:tdx_pic_31
		'_tdcf_image_2' => 'ZmllbGRfNjNmNWUyZTM1NjdmMQ==',
		'tdcf_image_3' => 'dGR4X3BpY18zMg==', // tdcf_demo_id:tdx_pic_32
		'_tdcf_image_3' => 'ZmllbGRfNjNmNWUyZWY1NjcyMw==',
		'tdcf_summary' => 'Q3JlYXRlZCBhIHBvd2VyZnVsIHlldCBmbGV4aWJsZSBkaXJlY3RvcnkgaW4gd2l0Y2ggdXNlcnMgY2FuIGFkZCBtZWRpY2FsIHNwZWNpYWxpc3RzIGluIHRoZSBkYXRhYmFzZSwgc29ydGVkIGJ5IHNwZWNpYWx0aWVzLCBsb2NhdGlvbnMgYW5kIG1vcmUuIFN1YnNjcmlwdGlvbiBwbGFucyBhbGxvdyBtb3JlIGluZm9ybWF0aW9uIG9uIHRoZSBwcm9maWxlcy4=',
		'_tdcf_summary' => 'ZmllbGRfNjNmNWUzMDliMDlkMw==',
		'tdcf_logo' => 'dGR4X3BpY18zMw==', // tdcf_demo_id:tdx_pic_33
		'_tdcf_logo' => 'ZmllbGRfNjNmNWUzNDkzMDVlOQ==',
		'tdcf_testimonial' => 'TW9tZW50dW0gaGFzIGJlZW4gYW4gaW5jcmVkaWJsZSBwYXJ0bmVyIGZvciBvdXIgYnVzaW5lc3MuIFRoZWlyIHdlYiBkZXNpZ24gYW5kIG1hcmtldGluZyBzdHJhdGVnaWVzIGhhdmUgaGVscGVkIHVzIHJlYWNoIG5ldyBoZWlnaHRzIGFuZCBhY2hpZXZlIG91ciBnb2Fscy4=',
		'_tdcf_testimonial' => 'ZmllbGRfNjNmNWUzYmRlMDg3Zg==',
		'tdcf_scribble' => 'dGR4X3BpY18zNA==', // tdcf_demo_id:tdx_pic_34
		'_tdcf_scribble' => 'ZmllbGRfNjQwNzQxY2I3ZjhhYw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_services' => array( $tax_term_branding_visual_design_id, $tax_term_email_marketing_id, $tax_term_social_media_marketing_id, $tax_term_web_design_development_id ),
	),
));

$cpt_real_estate_listings_id = td_demo_content::add_cpt( array(
	'title' => 'Real Estate Listings',
	'type' => 'tdcpt_case_studies',
	'file' => 'tdcpt_case_studies_default.txt',
	'cpt_image_td_id' => 'tdx_pic_37',
	'post_meta' => array( 
		'tdcf_services' => '',
		'_tdcf_services' => 'ZmllbGRfNjNmNWUwNzk0ZWM2MQ==',
		'tdcf_url' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vbmV3c3BhcGVyX3JlYWxfZXN0YXRlX3Byby8=',
		'_tdcf_url' => 'ZmllbGRfNjNmNWUyMWI1MjE1OQ==',
		'tdcf_image_1' => 'dGR4X3BpY18zNQ==', // tdcf_demo_id:tdx_pic_35
		'_tdcf_image_1' => 'ZmllbGRfNjNmNWUyNmU3NGIzZg==',
		'tdcf_image_2' => 'dGR4X3BpY18zNg==', // tdcf_demo_id:tdx_pic_36
		'_tdcf_image_2' => 'ZmllbGRfNjNmNWUyZTM1NjdmMQ==',
		'tdcf_image_3' => 'dGR4X3BpY18zNw==', // tdcf_demo_id:tdx_pic_37
		'_tdcf_image_3' => 'ZmllbGRfNjNmNWUyZWY1NjcyMw==',
		'tdcf_summary' => 'Q3JlYXRlZCBhbiBhYnNvbHV0ZWx5IGJlYXV0aWZ1bCByZWFsIGVzdGF0ZSBkaXJlY3RvcnkgbGlzdGluZyB3ZWJzaXRlLCB3aGVyZSBwZW9wbGUgY2FuIHNlbGwsIGJ1eSwgcmVudCBvciBzaGFyZSBwcm9wZXJ0aWVzLiBFdmVyeXRoaW5nIGRvbmUgb24gdGhlIGZyb250IGVuZCwgd2l0aCBlYXN5IHRvIHVzZSBpbnRlcmZhY2VzIGZvciB0aGUgdXNlcnMu',
		'_tdcf_summary' => 'ZmllbGRfNjNmNWUzMDliMDlkMw==',
		'tdcf_logo' => 'dGR4X3BpY18zOA==', // tdcf_demo_id:tdx_pic_38
		'_tdcf_logo' => 'ZmllbGRfNjNmNWUzNDkzMDVlOQ==',
		'tdcf_testimonial' => 'V2UgY291bGRuJ3QgYmUgaGFwcGllciB3aXRoIHRoZSB3b3JrIE1vbWVudHVtIGhhcyBkb25lIGZvciB1cy4gVGhlaXIgd2ViIGRlc2lnbiBhbmQgbWFya2V0aW5nIHNlcnZpY2VzIGhhdmUgYmVlbiBnYW1lLWNoYW5naW5nIGZvciBvdXIgYnVzaW5lc3Mu',
		'_tdcf_testimonial' => 'ZmllbGRfNjNmNWUzYmRlMDg3Zg==',
		'tdcf_scribble' => 'dGR4X3BpY18zOQ==', // tdcf_demo_id:tdx_pic_39
		'_tdcf_scribble' => 'ZmllbGRfNjQwNzQxY2I3ZjhhYw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_services' => array( $tax_term_search_engine_optimization_id, $tax_term_software_as_a_service_id, $tax_term_strategy_consulting_id, $tax_term_web_design_development_id ),
	),
));

$cpt_personal_trainer_shop_id = td_demo_content::add_cpt( array(
	'title' => 'Personal Trainer Shop',
	'type' => 'tdcpt_case_studies',
	'file' => 'tdcpt_case_studies_default.txt',
	'cpt_image_td_id' => 'tdx_pic_40',
	'post_meta' => array( 
		'tdcf_services' => '',
		'_tdcf_services' => 'ZmllbGRfNjNmNWUwNzk0ZWM2MQ==',
		'tdcf_url' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vbmV3c3BhcGVyX3BlcnNvbmFsX3RyYWluZXJfcHJvLw==',
		'_tdcf_url' => 'ZmllbGRfNjNmNWUyMWI1MjE1OQ==',
		'tdcf_image_1' => 'dGR4X3BpY180MA==', // tdcf_demo_id:tdx_pic_40
		'_tdcf_image_1' => 'ZmllbGRfNjNmNWUyNmU3NGIzZg==',
		'tdcf_image_2' => 'dGR4X3BpY180MQ==', // tdcf_demo_id:tdx_pic_41
		'_tdcf_image_2' => 'ZmllbGRfNjNmNWUyZTM1NjdmMQ==',
		'tdcf_image_3' => 'dGR4X3BpY180Mg==', // tdcf_demo_id:tdx_pic_42
		'_tdcf_image_3' => 'ZmllbGRfNjNmNWUyZWY1NjcyMw==',
		'tdcf_summary' => 'VGhpcyBmcmllbmRseSBhbmQgdXNlZnVsIHdlYnNpdGUgd2UgYnVpbHQgZm9yIGEgd29ybGQgcmVub3duZWQgcGVyc29uYWwgdHJhaW5lciAvIGluZmx1ZW5jZXIgaGVscHMgcGVvcGxlIGdldCBpbiBzaGFwZSBhbmQgc3RheSBoZWFsdGh5LiBJdCBmZWF0dXJlcyBzdWJzY3JpcHRpb24gYW5kIGVib29rIHB1cmNoYXNpbmcgY2FwYWJpbGl0aWVzLg==',
		'_tdcf_summary' => 'ZmllbGRfNjNmNWUzMDliMDlkMw==',
		'tdcf_logo' => 'dGR4X3BpY180Mw==', // tdcf_demo_id:tdx_pic_43
		'_tdcf_logo' => 'ZmllbGRfNjNmNWUzNDkzMDVlOQ==',
		'tdcf_testimonial' => 'TW9tZW50dW0gaXMgYSBmYW50YXN0aWMgYWdlbmN5IHRoYXQgdHJ1bHkgdW5kZXJzdGFuZHMgdGhlIG5lZWRzIG9mIHRoZWlyIGNsaWVudHMuIFRoZWlyIHdlYiBkZXNpZ24gYW5kIG1hcmtldGluZyBzdHJhdGVnaWVzIGhhdmUgaGVscGVkIG91ciBidXNpbmVzcyBncm93IGV4cG9uZW50aWFsbHku',
		'_tdcf_testimonial' => 'ZmllbGRfNjNmNWUzYmRlMDg3Zg==',
		'tdcf_scribble' => 'dGR4X3BpY180NA==', // tdcf_demo_id:tdx_pic_44
		'_tdcf_scribble' => 'ZmllbGRfNjQwNzQxY2I3ZjhhYw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_services' => array( $tax_term_content_copywriting_id, $tax_term_email_marketing_id, $tax_term_web_design_development_id, $tax_term_wordpress_web_design_id ),
	),
));

$cpt_tech_gadgets_shop_id = td_demo_content::add_cpt( array(
	'title' => 'Tech & Gadgets Shop',
	'type' => 'tdcpt_case_studies',
	'file' => 'tdcpt_case_studies_default.txt',
	'cpt_image_td_id' => 'tdx_pic_45',
	'post_meta' => array( 
		'tdcf_url' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vbmV3c3BhcGVyX3Nob3BfYmxvZ19nYWRnZXRzLw==',
		'_tdcf_url' => 'ZmllbGRfNjNmNWUyMWI1MjE1OQ==',
		'tdcf_image_1' => 'dGR4X3BpY180NQ==', // tdcf_demo_id:tdx_pic_45
		'_tdcf_image_1' => 'ZmllbGRfNjNmNWUyNmU3NGIzZg==',
		'tdcf_image_2' => 'dGR4X3BpY180Ng==', // tdcf_demo_id:tdx_pic_46
		'_tdcf_image_2' => 'ZmllbGRfNjNmNWUyZTM1NjdmMQ==',
		'tdcf_image_3' => 'dGR4X3BpY180Nw==', // tdcf_demo_id:tdx_pic_47
		'_tdcf_image_3' => 'ZmllbGRfNjNmNWUyZWY1NjcyMw==',
		'tdcf_summary' => 'V2UgZGV2ZWxvcGVkIGEgY3VzdG9tIGVDb21tZXJjZSB3ZWJzaXRlIGZvciBhIHRlY2ggcmV0YWlsZXIgdGhhdCBmZWF0dXJlZCBhIG1vZGVybiBhbmQgdXNlci1mcmllbmRseSBkZXNpZ24sIHdpdGggYSBzaW1wbGUgYW5kIGludHVpdGl2ZSBjaGVja291dCBwcm9jZXNzLiBUaGUgd2Vic2l0ZSBpbmNsdWRlZCBkZXRhaWxlZCBwcm9kdWN0IHBhZ2VzIGFuZCB3YXMgaW50ZWdyYXRlZCB3aXRoIHRoZSBjbGllbnQncyBpbnZlbnRvcnkgbWFuYWdlbWVudCBzeXN0ZW0sIHJlc3VsdGluZyBpbiBpbmNyZWFzZWQgc2FsZXMgYW5kIHJldmVudWUu',
		'_tdcf_summary' => 'ZmllbGRfNjNmNWUzMDliMDlkMw==',
		'tdcf_logo' => 'dGR4X3BpY180OA==', // tdcf_demo_id:tdx_pic_48
		'_tdcf_logo' => 'ZmllbGRfNjNmNWUzNDkzMDVlOQ==',
		'tdcf_testimonial' => 'V2UndmUgc2VlbiBhIHNpZ25pZmljYW50IGluY3JlYXNlIGluIG9ubGluZSBzYWxlcyBzaW5jZSBsYXVuY2hpbmcgdGhlIG5ldyBzaXRlLiBUaGUgY29udGVudCBtYXJrZXRpbmcgc3RyYXRlZ3kgdGhhdCB0aGlzIGFnZW5jeSBkZXZlbG9wZWQgZm9yIHVzIGhhcyBhbHNvIGJlZW4gZWZmZWN0aXZlIGluIGRyaXZpbmcgZW5nYWdlbWVudCBhbmQgc2FsZXMu',
		'_tdcf_testimonial' => 'ZmllbGRfNjNmNWUzYmRlMDg3Zg==',
		'tdcf_scribble' => 'dGR4X3BpY18yNA==', // tdcf_demo_id:tdx_pic_24
		'_tdcf_scribble' => 'ZmllbGRfNjQwNzQxY2I3ZjhhYw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_services' => array( $tax_term_e_commerce_development_id, $tax_term_pay_per_click_management_id, $tax_term_search_engine_optimization_id, $tax_term_strategy_consulting_id ),
	),
));

$cpt_beauty_makeup_shop_id = td_demo_content::add_cpt( array(
	'title' => 'Beauty & Makeup Shop',
	'type' => 'tdcpt_case_studies',
	'file' => 'tdcpt_case_studies_default.txt',
	'cpt_image_td_id' => 'tdx_pic_49',
	'post_meta' => array( 
		'tdcf_url' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vbmV3c3BhcGVyX3Nob3BfbWFrZXVwLw==',
		'_tdcf_url' => 'ZmllbGRfNjNmNWUyMWI1MjE1OQ==',
		'tdcf_image_1' => 'dGR4X3BpY180OQ==', // tdcf_demo_id:tdx_pic_49
		'_tdcf_image_1' => 'ZmllbGRfNjNmNWUyNmU3NGIzZg==',
		'tdcf_image_2' => 'dGR4X3BpY181MA==', // tdcf_demo_id:tdx_pic_50
		'_tdcf_image_2' => 'ZmllbGRfNjNmNWUyZTM1NjdmMQ==',
		'tdcf_image_3' => 'dGR4X3BpY181MQ==', // tdcf_demo_id:tdx_pic_51
		'_tdcf_image_3' => 'ZmllbGRfNjNmNWUyZWY1NjcyMw==',
		'tdcf_summary' => 'V2UgZGV2ZWxvcGVkIGEgY3VzdG9tIGVDb21tZXJjZSB3ZWJzaXRlIGZvciBhIGJlYXV0eSByZXRhaWxlciB0aGF0IGZlYXR1cmVkIGEgbW9kZXJuIGFuZCB1c2VyLWZyaWVuZGx5IGRlc2lnbiwgd2l0aCBhIHNpbXBsZSBhbmQgaW50dWl0aXZlIGNoZWNrb3V0IHByb2Nlc3MuIFRoZSB3ZWJzaXRlIGluY2x1ZGVkIGRldGFpbGVkIHByb2R1Y3QgcGFnZXMgYW5kIHdhcyBpbnRlZ3JhdGVkIHdpdGggdGhlIGNsaWVudCdzIGludmVudG9yeSBtYW5hZ2VtZW50IHN5c3RlbSwgcmVzdWx0aW5nIGluIGluY3JlYXNlZCBzYWxlcyBhbmQgcmV2ZW51ZS4=',
		'_tdcf_summary' => 'ZmllbGRfNjNmNWUzMDliMDlkMw==',
		'tdcf_logo' => 'dGR4X3BpY181Mg==', // tdcf_demo_id:tdx_pic_52
		'_tdcf_logo' => 'ZmllbGRfNjNmNWUzNDkzMDVlOQ==',
		'tdcf_testimonial' => 'V2Ugd2VyZSBsb29raW5nIGZvciBhIG1vZGVybiBhbmQgdXNlci1mcmllbmRseSBlQ29tbWVyY2UgcGxhdGZvcm0gdG8gc2VsbCBvdXIgcHJvZHVjdHMgYW5kIHRoaXMgYWdlbmN5IGRlbGl2ZXJlZC4gVGhlaXIgdGVhbSB3YXMgZWFzeSB0byB3b3JrIHdpdGggYW5kIHRoZSBlbmQgcmVzdWx0IHdhcyBiZXlvbmQgb3VyIGV4cGVjdGF0aW9ucy4=',
		'_tdcf_testimonial' => 'ZmllbGRfNjNmNWUzYmRlMDg3Zg==',
		'tdcf_scribble' => 'dGR4X3BpY180NA==', // tdcf_demo_id:tdx_pic_44
		'_tdcf_scribble' => 'ZmllbGRfNjQwNzQxY2I3ZjhhYw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_services' => array( $tax_term_e_commerce_development_id, $tax_term_email_marketing_id, $tax_term_social_media_marketing_id, $tax_term_ux_ui_design_id ),
	),
));

$cpt_newsweek_magazine_id = td_demo_content::add_cpt( array(
	'title' => 'NewsWeek Magazine',
	'type' => 'tdcpt_case_studies',
	'file' => 'tdcpt_case_studies_default.txt',
	'cpt_image_td_id' => 'tdx_pic_53',
	'post_meta' => array( 
		'tdcf_url' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vbmV3c3BhcGVyX3dlZWtfcHJvLw==',
		'_tdcf_url' => 'ZmllbGRfNjNmNWUyMWI1MjE1OQ==',
		'tdcf_image_1' => 'dGR4X3BpY181Mw==', // tdcf_demo_id:tdx_pic_53
		'_tdcf_image_1' => 'ZmllbGRfNjNmNWUyNmU3NGIzZg==',
		'tdcf_image_2' => 'dGR4X3BpY181NA==', // tdcf_demo_id:tdx_pic_54
		'_tdcf_image_2' => 'ZmllbGRfNjNmNWUyZTM1NjdmMQ==',
		'tdcf_image_3' => 'dGR4X3BpY181NQ==', // tdcf_demo_id:tdx_pic_55
		'_tdcf_image_3' => 'ZmllbGRfNjNmNWUyZWY1NjcyMw==',
		'tdcf_summary' => 'V2UgZGV2ZWxvcGVkIGEgY3VzdG9tIGVNYWdhemluZSB3ZWJzaXRlIGZvciBhIGxlYWRpbmcgbmV3cyBtYWdhemluZSB0aGF0IGZlYXR1cmVkIGEgY2xlYW4gYW5kIG1pbmltYWxpc3QgZGVzaWduLCB3aXRoIGEgZm9jdXMgb24gaGlnaC1xdWFsaXR5IGNvbnRlbnQgYW5kIHVzZXIgZXhwZXJpZW5jZS4gVGhlIHdlYnNpdGUgaW5jbHVkZWQgYSBwb3dlcmZ1bCBjb250ZW50IG1hbmFnZW1lbnQgc3lzdGVtIGFuZCB3YXMgaW50ZWdyYXRlZCB3aXRoIHNvY2lhbCBtZWRpYSBwbGF0Zm9ybXMgYW5kIGVtYWlsIG1hcmtldGluZyB0b29scywgcmVzdWx0aW5nIGluIGluY3JlYXNlZCByZWFkZXJzaGlwIGFuZCBlbmdhZ2VtZW50Lg==',
		'_tdcf_summary' => 'ZmllbGRfNjNmNWUzMDliMDlkMw==',
		'tdcf_logo' => 'dGR4X3BpY181Ng==', // tdcf_demo_id:tdx_pic_56
		'_tdcf_logo' => 'ZmllbGRfNjNmNWUzNDkzMDVlOQ==',
		'tdcf_testimonial' => 'V2Ugd2VyZSBibG93biBhd2F5IGJ5IHRoZSBjdXN0b20gZU1hZ2F6aW5lIHdlYnNpdGUgdGhhdCB0aGlzIGFnZW5jeSBjcmVhdGVkIGZvciB1cy4gVGhlaXIgdGVhbSBpcyBpbmNyZWRpYmx5IHRhbGVudGVkIGFuZCB3YXMgYWJsZSB0byBicmluZyBvdXIgdmlzaW9uIHRvIGxpZmUgaW4gYSB3YXkgdGhhdCBleGNlZWRlZCBvdXIgZXhwZWN0YXRpb25zLg==',
		'_tdcf_testimonial' => 'ZmllbGRfNjNmNWUzYmRlMDg3Zg==',
		'tdcf_scribble' => 'dGR4X3BpY18zOQ==', // tdcf_demo_id:tdx_pic_39
		'_tdcf_scribble' => 'ZmllbGRfNjQwNzQxY2I3ZjhhYw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_services' => array( $tax_term_content_copywriting_id, $tax_term_pay_per_click_management_id, $tax_term_search_engine_optimization_id, $tax_term_wordpress_web_design_id ),
	),
));

$cpt_compass_music_platform_id = td_demo_content::add_cpt( array(
	'title' => 'Compass Music Platform',
	'type' => 'tdcpt_case_studies',
	'file' => 'tdcpt_case_studies_default.txt',
	'cpt_image_td_id' => 'tdx_pic_57',
	'post_meta' => array( 
		'tdcf_url' => 'aHR0cHM6Ly9kZW1vLnRhZ2Rpdi5jb20vbmV3c3BhcGVyX2NvbXBhc3NfcHJvLw==',
		'_tdcf_url' => 'ZmllbGRfNjNmNWUyMWI1MjE1OQ==',
		'tdcf_image_1' => 'dGR4X3BpY181Nw==', // tdcf_demo_id:tdx_pic_57
		'_tdcf_image_1' => 'ZmllbGRfNjNmNWUyNmU3NGIzZg==',
		'tdcf_image_2' => 'dGR4X3BpY181OA==', // tdcf_demo_id:tdx_pic_58
		'_tdcf_image_2' => 'ZmllbGRfNjNmNWUyZTM1NjdmMQ==',
		'tdcf_image_3' => 'dGR4X3BpY181OQ==', // tdcf_demo_id:tdx_pic_59
		'_tdcf_image_3' => 'ZmllbGRfNjNmNWUyZWY1NjcyMw==',
		'tdcf_summary' => 'V2UgZGV2ZWxvcGVkIGEgY3VzdG9tIG11c2ljIGRpcmVjdG9yeSB3ZWJzaXRlIHRoYXQgYWxsb3dlZCB1c2VycyB0byBzZWFyY2ggZm9yIGJhbmRzIGFuZCBtdXNpY2lhbnMgYnkgZ2VucmUsIGxvY2F0aW9uLCBhbmQgYXZhaWxhYmlsaXR5LiBUaGUgd2Vic2l0ZSBpbmNsdWRlZCBkZXRhaWxlZCBtdXNpY2lhbiBwcm9maWxlcyBhbmQgcmVzdWx0ZWQgaW4gaW5jcmVhc2VkIHdlYnNpdGUgdHJhZmZpYyBhbmQgbXVzaWNpYW4gc2lnbi11cHMu',
		'_tdcf_summary' => 'ZmllbGRfNjNmNWUzMDliMDlkMw==',
		'tdcf_logo' => 'dGR4X3BpY182MA==', // tdcf_demo_id:tdx_pic_60
		'_tdcf_logo' => 'ZmllbGRfNjNmNWUzNDkzMDVlOQ==',
		'tdcf_testimonial' => 'VGhlIGN1c3RvbSBtdXNpYyBkaXJlY3Rvcnkgd2Vic2l0ZSB0aGF0IHRoaXMgYWdlbmN5IGNyZWF0ZWQgZm9yIHVzIGhhcyBiZWVuIGEgZ2FtZS1jaGFuZ2VyLiBXZSd2ZSBzZWVuIGEgc2lnbmlmaWNhbnQgaW5jcmVhc2UgaW4gdHJhZmZpYyBhbmQgdXNlciBlbmdhZ2VtZW50IHNpbmNlIGxhdW5jaGluZyB0aGUgbmV3IHNpdGUu',
		'_tdcf_testimonial' => 'ZmllbGRfNjNmNWUzYmRlMDg3Zg==',
		'tdcf_scribble' => 'dGR4X3BpY18zNA==', // tdcf_demo_id:tdx_pic_34
		'_tdcf_scribble' => 'ZmllbGRfNjQwNzQxY2I3ZjhhYw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_services' => array( $tax_term_content_copywriting_id, $tax_term_search_engine_optimization_id, $tax_term_software_as_a_service_id, $tax_term_strategy_consulting_id ),
	),
));


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS custom
*/
$menu_item_0_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Branding & visual design',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'taxonomy' => 'tdtax_services',
	'tax_id' => $tax_term_branding_visual_design_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_taxonomy( array(
	'title' => 'E-commerce development',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'taxonomy' => 'tdtax_services',
	'tax_id' => $tax_term_e_commerce_development_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Software as a service',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'taxonomy' => 'tdtax_services',
	'tax_id' => $tax_term_software_as_a_service_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_taxonomy( array(
	'title' => 'UX/UI design',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'taxonomy' => 'tdtax_services',
	'tax_id' => $tax_term_ux_ui_design_id,
	'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Web design & development',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'taxonomy' => 'tdtax_services',
	'tax_id' => $tax_term_web_design_development_id,
	'parent_id' => ''
));

$menu_item_5_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Wordpress web design',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'taxonomy' => 'tdtax_services',
	'tax_id' => $tax_term_wordpress_web_design_id,
	'parent_id' => ''
));


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS footer
*/
$menu_item_0_id = td_demo_menus::add_taxonomy( array(
	'title' => 'UX/UI design',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'taxonomy' => 'tdtax_services',
	'tax_id' => $tax_term_ux_ui_design_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Branding',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'taxonomy' => 'tdtax_services',
	'tax_id' => $tax_term_branding_visual_design_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Copywriting',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'taxonomy' => 'tdtax_services',
	'tax_id' => $tax_term_content_copywriting_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_taxonomy( array(
	'title' => 'E-commerce',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'taxonomy' => 'tdtax_services',
	'tax_id' => $tax_term_e_commerce_development_id,
	'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Web design',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'taxonomy' => 'tdtax_services',
	'tax_id' => $tax_term_web_design_development_id,
	'parent_id' => ''
));


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS header
*/

$menu_item_0_id = td_demo_menus::add_mega_menu(array(
    'title' => 'Services',
    'add_to_menu_id' => $menu_td_demo_header_menu_id,
    'page_id' => $page_megamenu_services_id,
    'parent_id' => ''
), true);

$menu_item_1_id = td_demo_menus::add_page(array(
	'title' => 'Work',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_case_studies_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_category( array(
	'title' => 'Blog',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_blog_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_page(array(
	'title' => 'About',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_about_page_momentum_pro_id,
	'parent_id' => ''
));


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS top
*/
$menu_item_0_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Content & copywriting',
	'add_to_menu_id' => $menu_td_demo_top_menu_id,
	'taxonomy' => 'tdtax_services',
	'tax_id' => $tax_term_content_copywriting_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Email marketing',
	'add_to_menu_id' => $menu_td_demo_top_menu_id,
	'taxonomy' => 'tdtax_services',
	'tax_id' => $tax_term_email_marketing_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Pay per click management',
	'add_to_menu_id' => $menu_td_demo_top_menu_id,
	'taxonomy' => 'tdtax_services',
	'tax_id' => $tax_term_pay_per_click_management_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Search engine optimization',
	'add_to_menu_id' => $menu_td_demo_top_menu_id,
	'taxonomy' => 'tdtax_services',
	'tax_id' => $tax_term_search_engine_optimization_id,
	'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Social media marketing',
	'add_to_menu_id' => $menu_td_demo_top_menu_id,
	'taxonomy' => 'tdtax_services',
	'tax_id' => $tax_term_social_media_marketing_id,
	'parent_id' => ''
));

$menu_item_5_id = td_demo_menus::add_taxonomy( array(
	'title' => 'Strategy & consulting',
	'add_to_menu_id' => $menu_td_demo_top_menu_id,
	'taxonomy' => 'tdtax_services',
	'tax_id' => $tax_term_strategy_consulting_id,
	'parent_id' => ''
));


/*  ---------------------------------------------------------------------------- 
	CLOUD TEMPLATES
*/
$template_tag_template_momentum_pro_id = td_demo_content::add_cloud_template( array(
	'title' => 'Tag Template - Momentum PRO',
	'file' => 'tag_cloud_template.txt',
	'template_type' => 'tag',
));

td_demo_misc::update_global_tag_template( 'tdb_template_' . $template_tag_template_momentum_pro_id);


$template_date_template_momentum_pro_id = td_demo_content::add_cloud_template( array(
	'title' => 'Date Template - Momentum PRO',
	'file' => 'date_cloud_template.txt',
	'template_type' => 'date',
));

td_demo_misc::update_global_date_template( 'tdb_template_' . $template_date_template_momentum_pro_id);


$template_author_template_momentum_pro_id = td_demo_content::add_cloud_template( array(
	'title' => 'Author Template - Momentum PRO',
	'file' => 'author_cloud_template.txt',
	'template_type' => 'author',
));

td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_momentum_pro_id);


$template_search_template_momentum_pro_id = td_demo_content::add_cloud_template( array(
	'title' => 'Search Template - Momentum PRO',
	'file' => 'search_cloud_template.txt',
	'template_type' => 'search',
));

td_demo_misc::update_global_search_template( 'tdb_template_' . $template_search_template_momentum_pro_id);


$template_404_template_momentum_pro_id = td_demo_content::add_cloud_template( array(
	'title' => '404 Template - Momentum PRO',
	'file' => '404_cloud_template.txt',
	'template_type' => '404',
));

td_demo_misc::update_global_404_template( 'tdb_template_' . $template_404_template_momentum_pro_id);


$template_category_template_momentum_pro_id = td_demo_content::add_cloud_template( array(
	'title' => 'Category Template - Momentum PRO',
	'file' => 'cat_cloud_template.txt',
	'template_type' => 'category',
));

td_demo_misc::update_global_category_template( 'tdb_template_' . $template_category_template_momentum_pro_id);


$template_single_post_template_momentum_pro_id = td_demo_content::add_cloud_template( array(
	'title' => 'Single Post Template - Momentum PRO',
	'file' => 'post_cloud_template.txt',
	'template_type' => 'single',
));

td_util::update_option('td_default_site_post_template', 'tdb_template_' . $template_single_post_template_momentum_pro_id);


$template_custom_taxonomy_services_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Taxonomy - Services',
	'file' => 'custom_taxonomy_services_cloud_template.txt',
	'template_type' => 'cpt_tax',
));

td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_services_id, 'tdtax_services' );


$template_footer_template_momentum_pro_id = td_demo_content::add_cloud_template( array(
	'title' => 'Footer Template - Momentum PRO',
	'file' => 'footer_cloud_template.txt',
	'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_template_momentum_pro_id);


$template_custom_post_type_case_studies_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Post Type - Case Studies',
	'file' => 'cpt_cloud_template.txt',
	'template_type' => 'cpt',
));

td_demo_misc::update_global_cpt_template( 'tdb_template_' . $template_custom_post_type_case_studies_id, 'tdcpt_case_studies' );


$template_header_template_momentum_pro_id = td_demo_content::add_cloud_template( array(
	'title' => 'Header Template - Momentum PRO',
	'file' => 'header_cloud_template.txt',
	'template_type' => 'header',
));

td_demo_misc::update_global_header_template( 'tdb_template_' . $template_header_template_momentum_pro_id);


update_post_meta( $template_header_template_momentum_pro_id, 'header_mobile_menu_id', $menu_td_demo_footer_menu_id);



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
