<?php



/*  ----------------------------------------------------------------------------
	EXTERNAL PLUGINS DATA IMPORT
*/
/* -- ACF -- */
// Field groups
$field_group_app_listing_details = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/app_find_pro/data/acf/group_63d140a2cfadd.json' );

// Post types
$post_type_app_listings = td_demo_data::acf_import_post_type( 'https://cloud.tagdiv.com/demos/Newspaper/app_find_pro/data/acf/post_type_6463338eba68c.json' );

// Taxonomies
$taxonomy_categories = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/app_find_pro/data/acf/taxonomy_6463344f1c7d0.json' );
$taxonomy_locations = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/app_find_pro/data/acf/taxonomy_646334dea7c02.json' );
$taxonomy_tags = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/app_find_pro/data/acf/taxonomy_646334b0352f1.json' );



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

$plan_basic___yearly_plan_id = td_demo_subscription::add_plan( array(
        'name' => 'Basic - Yearly Plan - APP Find PRO',
        'price' => '50',
        'months_in_cycle' => '12',
        'trial_days' => '0',
        'is_free' => '0',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"396492b0e8f1646";}',
        'publishing_limits' => 'a:1:{i:0;O:8:"stdClass":3:{s:9:"post_type";s:17:"tdcpt_app_listing";s:5:"limit";s:2:"20";s:5:"track";b:1;}}',
    )
);

$plan_free_plan_id = td_demo_subscription::add_plan( array(
        'name' => 'Free Plan - APP Find PRO',
        'price' => '',
        'months_in_cycle' => '',
        'trial_days' => '0',
        'is_free' => '1',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"726492b0e8f16a0";}',
        'publishing_limits' => 'a:1:{i:0;O:8:"stdClass":3:{s:9:"post_type";s:17:"tdcpt_app_listing";s:5:"limit";s:1:"1";s:5:"track";b:0;}}',
    )
);

$plan_basic___monthly_plan_id = td_demo_subscription::add_plan( array(
        'name' => 'Basic - Monthly Plan - APP Find PRO',
        'price' => '10',
        'months_in_cycle' => '1',
        'trial_days' => '0',
        'is_free' => '0',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"196492b0e8f16f7";}',
        'publishing_limits' => 'a:1:{i:0;O:8:"stdClass":3:{s:9:"post_type";s:17:"tdcpt_app_listing";s:5:"limit";s:1:"2";s:5:"track";b:1;}}',
    )
);

$plan_pro___monthly_plan_id = td_demo_subscription::add_plan( array(
        'name' => 'Pro - Monthly Plan - APP Find PRO',
        'price' => '15',
        'months_in_cycle' => '1',
        'trial_days' => '0',
        'is_free' => '0',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"986492b0e8f174c";}',
        'publishing_limits' => 'a:1:{i:0;O:8:"stdClass":3:{s:9:"post_type";s:17:"tdcpt_app_listing";s:5:"limit";s:1:"5";s:5:"track";b:1;}}',
    )
);

$plan_pro___yearly_plan_id = td_demo_subscription::add_plan( array(
        'name' => 'Pro - Yearly Plan - APP Find PRO',
        'price' => '100',
        'months_in_cycle' => '12',
        'trial_days' => '0',
        'is_free' => '0',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"246492b0e8f17a9";}',
        'publishing_limits' => 'a:1:{i:0;O:8:"stdClass":3:{s:9:"post_type";s:17:"tdcpt_app_listing";s:5:"limit";s:2:"50";s:5:"track";b:1;}}',
    )
);

$page_payment_page_id_id = td_demo_content::add_page( array(
	'title' => 'Checkout',
	'file' => 'tds_checkout.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'payment_page_id',
	'value' => $page_payment_page_id_id,
	)
);

$page_create_account_page_id_id = td_demo_content::add_page( array(
	'title' => 'Login/Register',
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


/*  ----------------------------------------------------------------------------
	 CLOUD TEMPLATES(MODULES)
*/
$template_module_template_app_listings_3_id = td_demo_content::add_cloud_template( array(
	'title' => 'Module Template - APP Listings - 3 - APP Find PRO',
	'file' => 'module_template_app_listings_3_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '1491',
));

$template_module_template_posts_id = td_demo_content::add_cloud_template( array(
	'title' => 'Module Template - Posts - APP Find PRO',
	'file' => 'module_template_posts_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '1143',
));

$template_module_template_app_listings_2_id = td_demo_content::add_cloud_template( array(
	'title' => 'Module Template - APP Listings - 2 - APP Find PRO',
	'file' => 'module_template_app_listings_2_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '1003',
));

$template_module_template_app_listings_1_id = td_demo_content::add_cloud_template( array(
	'title' => 'Module Template - APP Listings - 1 - APP Find PRO',
	'file' => 'module_template_app_listings_1_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '824',
));


/*  ----------------------------------------------------------------------------
	ATTACHMENTS
*/

/*  ----------------------------------------------------------------------------
	PAGES
*/
$page_tds_switching_plans_wizard_id = td_demo_content::add_page( array(
    'title' => 'Subscribe',
    'file' => 'subscribe.txt',
    'demo_unique_id' => '576492b0e91aba2',
));

$page_new_listing_id = td_demo_content::add_page( array(
    'title' => 'New APP Listing',
    'file' => 'new_listing.txt',
    'demo_unique_id' => '966492b0e91b8cc',
));

$page_my_account_app_listings_id = td_demo_content::add_page( array(
    'title' => 'My Account - APP Listings',
    'file' => 'my_account_app_listings.txt',
    'demo_unique_id' => '636492b0e9199a8',
));

$page_my_account_favorite_applications_id = td_demo_content::add_page( array(
    'title' => 'My Account - Favorite Applications',
    'file' => 'my_account_favorite_applications.txt',
    'demo_unique_id' => '725892c0e9109a8',
));

$page_favorite_applications_id = td_demo_content::add_page( array(
    'title' => 'Favorite Applications',
    'file' => 'favorite_applications.txt',
    'demo_unique_id' => '183453b0e61a8eb',
));

$page_my_account_page_id_id = td_demo_content::add_page( array(
    'title' => 'My Account',
    'file' => 'tds_my_account.txt',
    'demo_unique_id' => '36632fff9fo21ac',
));

td_demo_subscription::add_option( array(
        'name' => 'my_account_page_id',
        'value' => $page_my_account_page_id_id,
    )
);

$page_blog_id = td_demo_content::add_page( array(
    'title' => 'Blog',
    'file' => 'blog.txt',
    'demo_unique_id' => '746492b0e91a1d9',
));

$page_software_applications_id = td_demo_content::add_page( array(
    'title' => 'Software Applications',
    'file' => 'software_applications.txt',
    'demo_unique_id' => '616492b0e91a621',
));

$page_home_id = td_demo_content::add_page( array(
    'title' => 'Home',
    'file' => 'home.txt',
    'homepage' => true,
    'demo_unique_id' => '536492b0e91c329',
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
	'tds_paid_subs_plan_ids' => [$plan_free_plan_id,$plan_basic___monthly_plan_id,$plan_basic___yearly_plan_id],
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

td_util::update_option('tds_demo_options', 'a:1:{s:5:"plans";a:5:{i:0;a:2:{s:9:"unique_id";s:15:"396492b0e8f1646";s:4:"name";s:19:"Basic - Yearly Plan - APP Find PRO";}i:1;a:2:{s:9:"unique_id";s:15:"726492b0e8f16a0";s:4:"name";s:9:"Free Plan - APP Find PRO";}i:2;a:2:{s:9:"unique_id";s:15:"196492b0e8f16f7";s:4:"name";s:20:"Basic - Monthly Plan - APP Find PRO";}i:3;a:2:{s:9:"unique_id";s:15:"986492b0e8f174c";s:4:"name";s:18:"Pro - Monthly Plan - APP Find PRO";}i:4;a:2:{s:9:"unique_id";s:15:"246492b0e8f17a9";s:4:"name";s:17:"Pro - Yearly Plan - APP Find PRO";}}}');



/*  ----------------------------------------------------------------------------
	SUBSCRIPTION - end phase 2
*/

/*  ----------------------------------------------------------------------------
	POSTS
*/


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
	CLOUD TEMPLATES
*/
$template_search_template_app_listings_id = td_demo_content::add_cloud_template( array(
	'title' => 'Search Template - APP Listings - APP Find PRO',
	'file' => 'search_cloud_template.txt',
	'template_type' => 'search',
));

$template_404_template_id = td_demo_content::add_cloud_template( array(
	'title' => '404 Template - APP Find PRO',
	'file' => '404_cloud_template.txt',
	'template_type' => '404',
));

td_demo_misc::update_global_404_template( 'tdb_template_' . $template_404_template_id );


$template_single_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Single Template - APP Find PRO',
	'file' => 'post_cloud_template.txt',
	'template_type' => 'single',
));

td_util::update_option( 'td_default_site_post_template', 'tdb_template_' . $template_single_template_id );


$template_tag_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Tag Template - APP Find PRO',
	'file' => 'tag_cloud_template.txt',
	'template_type' => 'tag',
));

td_demo_misc::update_global_tag_template( 'tdb_template_' . $template_tag_template_id );


$template_date_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Date Template - APP Find PRO',
	'file' => 'date_cloud_template.txt',
	'template_type' => 'date',
));

td_demo_misc::update_global_date_template( 'tdb_template_' . $template_date_template_id );


$template_category_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Category Template - APP Find PRO',
	'file' => 'cat_cloud_template.txt',
	'template_type' => 'category',
));

td_demo_misc::update_global_category_template( 'tdb_template_' . $template_category_template_id );


$template_custom_taxonomy_template_app_listing_taxonomies_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Taxonomy Template - APP Listing Taxonomies - APP Find PRO',
	'file' => 'custom_taxonomy_template_app_listing_taxonomies_cloud_template.txt',
	'template_type' => 'cpt_tax',
));

td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_app_listing_taxonomies_id, 'app_listing_category' );


td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_app_listing_taxonomies_id, 'tdtax_app_category' );


td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_app_listing_taxonomies_id, 'tdtax_app_tag' );


td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_app_listing_taxonomies_id, 'tdtax_app_location' );


$template_custom_post_type_template_app_listing_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Post Type Template - APP Listing - APP Find PRO',
	'file' => 'cpt_cloud_template.txt',
	'template_type' => 'cpt',
));

td_demo_misc::update_global_cpt_template( 'tdb_template_' . $template_custom_post_type_template_app_listing_id, 'app_listing' );


td_demo_misc::update_global_cpt_template( 'tdb_template_' . $template_custom_post_type_template_app_listing_id, 'tdcpt_app_listing' );


$template_footer_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Footer Template - APP Find PRO',
	'file' => 'footer_template_cloud_template.txt',
	'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_template_id );


$template_header_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Header Template - APP Find PRO',
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
