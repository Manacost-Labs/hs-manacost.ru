<?php



/*  ----------------------------------------------------------------------------
	EXTERNAL PLUGINS DATA IMPORT
*/
/* -- ACF -- */
// Field groups
$field_group_build_parts = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/pc_forge_pro/data/acf/group_64d612c0e9673.json' );
$field_group_case_details = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/pc_forge_pro/data/acf/group_64d6126957f5c.json' );
$field_group_cooling_details = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/pc_forge_pro/data/acf/group_64d6128ed8c90.json' );
$field_group_cpu_details = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/pc_forge_pro/data/acf/group_64d610bd3c248.json' );
$field_group_graphics_details = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/pc_forge_pro/data/acf/group_64d61196a4e26.json' );
$field_group_memory_details = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/pc_forge_pro/data/acf/group_64d61169b184b.json' );
$field_group_motherboard_details = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/pc_forge_pro/data/acf/group_64d6112f6cf2a.json' );
$field_group_psu_details = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/pc_forge_pro/data/acf/group_64d6122648b16.json' );
$field_group_storage_details = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/pc_forge_pro/data/acf/group_64d611f3b061b.json' );
$field_group_user_details = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/pc_forge_pro/data/acf/group_64d612fecf6a5.json' );

// Post types
$post_type_pc_builds = td_demo_data::acf_import_post_type( 'https://cloud.tagdiv.com/demos/Newspaper/pc_forge_pro/data/acf/post_type_64d608bcf2a3d.json' );

// Taxonomies
$taxonomy_cases = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/pc_forge_pro/data/acf/taxonomy_64d60db1aeec6.json' );
$taxonomy_cooling = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/pc_forge_pro/data/acf/taxonomy_64d60dc7c7075.json' );
$taxonomy_cpus = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/pc_forge_pro/data/acf/taxonomy_64d60cbd0c9cc.json' );
$taxonomy_graphics = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/pc_forge_pro/data/acf/taxonomy_64d60de29d1fa.json' );
$taxonomy_memory = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/pc_forge_pro/data/acf/taxonomy_64d60d74bd005.json' );
$taxonomy_motherboards = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/pc_forge_pro/data/acf/taxonomy_64d60d46008de.json' );
$taxonomy_psus = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/pc_forge_pro/data/acf/taxonomy_64d60dff71895.json' );
$taxonomy_storage = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/pc_forge_pro/data/acf/taxonomy_64d60e128cb94.json' );



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
		'name' => 'Free Plan - PC Forge PRO',
		'price' => '',
		'interval' => '',
		'interval_count' => 0,
		'trial_days' => '0',
		'is_free' => '1',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"596502f2b43a3d9";}',
		'publishing_limits' => 'a:1:{i:0;O:8:"stdClass":3:{s:9:"post_type";s:15:"tdcpt_pc_builds";s:5:"limit";s:1:"2";s:5:"track";b:0;}}',
		'automatic_delistings' => 'a:0:{}',
	)
);

$plan_membership___yearly_plan_id = td_demo_subscription::add_plan( array(
		'name' => 'Membership - Yearly Plan - PC Forge PRO',
		'price' => '50',
		'interval' => 'year',
		'interval_count' => 1,
		'trial_days' => '0',
		'is_free' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"146502f2b43a46f";}',
		'publishing_limits' => 'a:1:{i:0;O:8:"stdClass":3:{s:9:"post_type";s:15:"tdcpt_pc_builds";s:5:"limit";s:7:"1000000";s:5:"track";b:1;}}',
		'automatic_delistings' => 'a:0:{}',
	)
);

$plan_membership___monthly_plan_id = td_demo_subscription::add_plan( array(
		'name' => 'Membership - Monthly Plan - PC Forge PRO',
		'price' => '5',
		'interval' => 'month',
		'interval_count' => 1,
		'trial_days' => '0',
		'is_free' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"496502f2b43a4ee";}',
		'publishing_limits' => 'a:1:{i:0;O:8:"stdClass":3:{s:9:"post_type";s:15:"tdcpt_pc_builds";s:5:"limit";s:7:"1000000";s:5:"track";b:1;}}',
		'automatic_delistings' => 'a:0:{}',
	)
);

$page_payment_page_id_id = td_demo_content::add_page( array(
	'title' => 'Checkout',
	'file' => 'checkout.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'payment_page_id',
	'value' => $page_payment_page_id_id,
	)
);

$page_create_account_page_id_id = td_demo_content::add_page( array(
	'title' => 'Login',
	'file' => 'login.txt',
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
$template_module_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Module Template',
	'file' => 'module_template_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '683',
));


/*  ---------------------------------------------------------------------------- 
	ATTACHMENTS
*/

/*  ---------------------------------------------------------------------------- 
	PAGES
*/
$page_favorite_builds_id = td_demo_content::add_page( array(
	'title' => 'Favorite Builds',
	'file' => 'favorite_builds.txt',
	'demo_unique_id' => '486502f2b44d117',
));

$page_builds_cpt_reviews_tab_id = td_demo_content::add_page( array(
    'title' => 'Builds CPT - Reviews Tab',
    'file' => 'builds_cpt_reviews_tab.txt',
    'demo_unique_id' => '676502f2b44e654',
));

$page_builds_cpt_comments_tab_id = td_demo_content::add_page( array(
	'title' => 'Builds CPT - Comments Tab',
	'file' => 'builds_cpt_comments_tab.txt',
	'demo_unique_id' => '46502f2b44e216',
));

$page_modal_menu_id = td_demo_content::add_page( array(
	'title' => 'Modal - Menu',
	'file' => 'modal_menu.txt',
	'demo_unique_id' => '626502f2b44ea86',
));

$page_modal_search_id = td_demo_content::add_page( array(
	'title' => 'Modal - Search',
	'file' => 'modal_search.txt',
	'demo_unique_id' => '456502f2b44eeab',
));

$page_modal_sign_up_id = td_demo_content::add_page( array(
	'title' => 'Modal - Sign Up',
	'file' => 'modal_sign_up.txt',
	'demo_unique_id' => '536502f2b44f2db',
));

$page_completed_builds_id = td_demo_content::add_page( array(
	'title' => 'Completed Builds',
	'file' => 'completed_builds.txt',
	'demo_unique_id' => '776502f2b44f727',
));

$page_builder_id = td_demo_content::add_page( array(
	'title' => 'Builder',
	'file' => 'builder.txt',
    'demo_unique_id' => '726502f2b44fce0',
));

$page_my_account_account_details_page_id = td_demo_content::add_page( array(
    'title' => 'My Account - Account Details Page',
    'file' => 'my_account_account_details_page.txt',
    'demo_unique_id' => '596502f2b44d579',
));

$page_my_account_builds_page_id = td_demo_content::add_page( array(
    'title' => 'My Account - Builds Page',
    'file' => 'my_account_builds_page.txt',
    'demo_unique_id' => '156502f2b44dde7',
));

$page_my_account_my_reviews_page_id = td_demo_content::add_page( array(
    'title' => 'My Account - My Reviews Page',
    'file' => 'my_account_my_reviews_page.txt',
    'template' => 'default',
    'demo_unique_id' => '06502f2b44d9b2',
));

$page_my_account_page_id_id = td_demo_content::add_page( array(
    'title' => 'My Account',
    'file' => 'my_account.txt',
    'demo_unique_id' => '17262c2d45d9b1',
));
td_demo_subscription::add_option( array(
        'name' => 'my_account_page_id',
        'value' => $page_my_account_page_id_id,
    )
);

$page_home_id = td_demo_content::add_page( array(
	'title' => 'Home',
	'file' => 'home.txt',
	'homepage' => true,
	'demo_unique_id' => '606502f2b4501f5',
));

$page_membership_id = td_demo_content::add_page( array(
	'title' => 'Membership',
	'file' => 'membership.txt',
	'demo_unique_id' => '476502f2b450722',
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
	'tds_paid_subs_plan_ids' => [$plan_free_plan_id,$plan_membership___monthly_plan_id,$plan_membership___yearly_plan_id],
	'tds_paid_subs_page_id' => $page_membership_id,
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

td_util::update_option('tds_demo_options', 'a:1:{s:5:"plans";a:3:{i:0;a:2:{s:9:"unique_id";s:15:"596502f2b43a3d9";s:4:"name";s:9:"Free Plan";}i:1;a:2:{s:9:"unique_id";s:15:"146502f2b43a46f";s:4:"name";s:24:"Membership - Yearly Plan";}i:2;a:2:{s:9:"unique_id";s:15:"496502f2b43a4ee";s:4:"name";s:25:"Membership - Monthly Plan";}}}');


/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTION - end phase 2
*/


/*  ---------------------------------------------------------------------------- 
	CLOUD TEMPLATES
*/
$template_custom_taxonomy_template_storage_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Taxonomy Template - Storage - PC Forge PRO',
	'file' => 'custom_taxonomy_template_storage_cloud_template.txt',
	'template_type' => 'cpt_tax',
));
td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_storage_id, 'tdtax_pc_builds_storage' );


$template_custom_taxonomy_template_psus_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Taxonomy Template - PSUs - PC Forge PRO',
	'file' => 'custom_taxonomy_template_psus_cloud_template.txt',
	'template_type' => 'cpt_tax',
));
td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_psus_id, 'tdtax_pc_builds_psus' );


$template_custom_taxonomy_template_motherboards_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Taxonomy Template - Motherboards - PC Forge PRO',
	'file' => 'custom_taxonomy_template_motherboards_cloud_template.txt',
	'template_type' => 'cpt_tax',
));
td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_motherboards_id, 'tdtax_pc_builds_motherboards' );


$template_custom_taxonomy_template_memory_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Taxonomy Template - Memory - PC Forge PRO',
	'file' => 'custom_taxonomy_template_memory_cloud_template.txt',
	'template_type' => 'cpt_tax',
));
td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_memory_id, 'tdtax_pc_builds_memory' );


$template_custom_taxonomy_template_cooling_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Taxonomy Template - Cooling - PC Forge PRO',
	'file' => 'custom_taxonomy_template_cooling_cloud_template.txt',
	'template_type' => 'cpt_tax',
));
td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_cooling_id, 'tdtax_pc_builds_cooling' );


$template_custom_taxonomy_template_cases_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Taxonomy Template - Cases - PC Forge PRO',
	'file' => 'custom_taxonomy_template_cases_cloud_template.txt',
	'template_type' => 'cpt_tax',
));
td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_cases_id, 'tdtax_pc_builds_cases' );


$template_custom_taxonomy_template_graphics_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Taxonomy Template - Graphics - PC Forge PRO',
	'file' => 'custom_taxonomy_template_graphics_cloud_template.txt',
	'template_type' => 'cpt_tax',
));
td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_graphics_id, 'tdtax_pc_builds_grahics' );


$template_404_template_id = td_demo_content::add_cloud_template( array(
	'title' => '404 Template - PC Forge PRO',
	'file' => '404_cloud_template.txt',
	'template_type' => '404',
));
td_demo_misc::update_global_404_template( 'tdb_template_' . $template_404_template_id );


$template_custom_taxonomy_template_cpus_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Taxonomy Template - CPUs - PC Forge PRO',
	'file' => 'custom_taxonomy_template_cpus_cloud_template.txt',
	'template_type' => 'cpt_tax',
));
td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_cpus_id, 'tdtax_pc_builds_cpus' );


$template_custom_post_type_archive_template_pc_builds_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Post Type Archive Template - PC Builds - PC Forge PRO',
	'file' => 'custom_post_type_archive_template_pc_builds_cloud_template.txt',
	'template_type' => 'cpt_tax',
));
td_demo_misc::update_global_cpt_template( 'tdb_template_' . $template_custom_post_type_archive_template_pc_builds_id, 'tdcpt_pc_builds', 'archive_tpl' );


$template_search_template_pc_builds_id = td_demo_content::add_cloud_template( array(
	'title' => 'Search Template - PC Builds - PC Forge PRO',
	'file' => 'search_cloud_template.txt',
	'template_type' => 'search',
));
td_demo_misc::update_global_cpt_template( 'tdb_template_' . $template_search_template_pc_builds_id, 'tdcpt_pc_builds', 'search_tpl' );


$template_custom_post_type_template_pc_builds_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Post Type Template - PC Builds - PC Forge PRO',
	'file' => 'cpt_cloud_template.txt',
	'template_type' => 'cpt',
));
td_demo_misc::update_global_cpt_template( 'tdb_template_' . $template_custom_post_type_template_pc_builds_id, 'tdcpt_pc_builds' );


$template_author_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Author Template - PC Forge PRO',
	'file' => 'author_cloud_template.txt',
	'template_type' => 'author',
));
td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_id );


$template_footer_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Footer Template - PC Forge PRO',
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
td_demo_content::update_meta( $page_modal_menu_id, 'tdc_header_template_id', 'no_header' );
td_demo_content::update_meta( $page_modal_menu_id, 'tdc_footer_template_id', 'no_footer' );
td_demo_content::update_meta( $page_modal_search_id, 'tdc_header_template_id', 'no_header' );
td_demo_content::update_meta( $page_modal_search_id, 'tdc_footer_template_id', 'no_footer' );
td_demo_content::update_meta( $page_modal_sign_up_id, 'tdc_header_template_id', 'no_header' );
td_demo_content::update_meta( $page_modal_sign_up_id, 'tdc_footer_template_id', 'no_footer' );
