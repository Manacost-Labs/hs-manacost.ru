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
	POSTS
*/


/*  ---------------------------------------------------------------------------- 
	PRODUCTS
*/

/*  ---------------------------------------------------------------------------- 
	TAXONOMIES
*/
$tax_term_design_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Design',
	'taxonomy' => 'tdc-review-criteria',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_creativity_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Creativity',
	'taxonomy' => 'tdc-review-criteria',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_components_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Components',
	'taxonomy' => 'tdc-review-criteria',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_nzxt_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'NZXT',
	'taxonomy' => 'tdtax_pc_builds_cases',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_corsair_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Corsair',
	'taxonomy' => 'tdtax_pc_builds_cases',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_cooler_master_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Cooler Master',
	'taxonomy' => 'tdtax_pc_builds_cases',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_h510_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'H510',
	'taxonomy' => 'tdtax_pc_builds_cases',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_nzxt_id,
	'description' => '',
	'tax_term_meta' => array(
		'tdcf_case_size' => 'TWlkLVRvd2Vy',
		'_tdcf_case_size' => 'ZmllbGRfNjRkNjEyNmFkMmVmZA==',
	),
));

$tax_term_h710_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'H710',
	'taxonomy' => 'tdtax_pc_builds_cases',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_nzxt_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_case_size' => 'TWlkLVRvd2Vy',
		'_tdcf_case_size' => 'ZmllbGRfNjRkNjEyNmFkMmVmZA==',
	),
));

$tax_term_4000d_airflow_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => '4000D Airflow',
	'taxonomy' => 'tdtax_pc_builds_cases',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_corsair_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_case_size' => 'TWlkLVRvd2Vy',
		'_tdcf_case_size' => 'ZmllbGRfNjRkNjEyNmFkMmVmZA==',
	),
));

$tax_term_crystal_series_680x_rgb_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Crystal Series 680X RGB',
	'taxonomy' => 'tdtax_pc_builds_cases',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_corsair_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_case_size' => 'TWlkLVRvd2Vy',
		'_tdcf_case_size' => 'ZmllbGRfNjRkNjEyNmFkMmVmZA==',
	),
));

$tax_term_master_masterbox_mb311l_argb_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Master MasterBox MB311L ARGB',
	'taxonomy' => 'tdtax_pc_builds_cases',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_cooler_master_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_case_size' => 'TWljcm8tQVRYIFRvd2Vy',
		'_tdcf_case_size' => 'ZmllbGRfNjRkNjEyNmFkMmVmZA==',
	),
));

$tax_term_mastercase_h500p_mesh_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'MasterCase H500P Mesh',
	'taxonomy' => 'tdtax_pc_builds_cases',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_cooler_master_id,
	'description' => '',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdcf_case_size' => 'TWlkLVRvd2Vy',
		'_tdcf_case_size' => 'ZmllbGRfNjRkNjEyNmFkMmVmZA==',
		'tdb_filter_color' => '',
	),
));

$tax_term_noctua_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Noctua',
	'taxonomy' => 'tdtax_pc_builds_cooling',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_arctic_freezer_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Arctic Freezer',
	'taxonomy' => 'tdtax_pc_builds_cooling',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_nh_d15_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'NH-D15',
	'taxonomy' => 'tdtax_pc_builds_cooling',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_noctua_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_cooling_type' => 'QWlyIENvb2xlcg==',
		'_tdcf_cooling_type' => 'ZmllbGRfNjRkNjEyOGZhYjA5MQ==',
	),
));

$tax_term_nh_u12s_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'NH-U12S',
	'taxonomy' => 'tdtax_pc_builds_cooling',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_noctua_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_cooling_type' => 'QWlyIENvb2xlcg==',
		'_tdcf_cooling_type' => 'ZmllbGRfNjRkNjEyOGZhYjA5MQ==',
	),
));

$tax_term_nh_l12s_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'NH-L12S',
	'taxonomy' => 'tdtax_pc_builds_cooling',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_noctua_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_cooling_type' => 'TG93LVByb2ZpbGUgQWlyIENvb2xlcg==',
		'_tdcf_cooling_type' => 'ZmllbGRfNjRkNjEyOGZhYjA5MQ==',
	),
));

$tax_term_34_esports_duo_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => '34 eSports DUO',
	'taxonomy' => 'tdtax_pc_builds_cooling',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_arctic_freezer_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_cooling_type' => 'QWlyIENvb2xlcg==',
		'_tdcf_cooling_type' => 'ZmllbGRfNjRkNjEyOGZhYjA5MQ==',
	),
));

$tax_term_7_x_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => '7 X',
	'taxonomy' => 'tdtax_pc_builds_cooling',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_arctic_freezer_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_cooling_type' => 'QWlyIENvb2xlcg==',
		'_tdcf_cooling_type' => 'ZmllbGRfNjRkNjEyOGZhYjA5MQ==',
	),
));

$tax_term_liquid_ii_240_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Liquid II 240',
	'taxonomy' => 'tdtax_pc_builds_cooling',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_arctic_freezer_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_cooling_type' => 'TGlxdWlkIEFJTyBDb29sZXI=',
		'_tdcf_cooling_type' => 'ZmllbGRfNjRkNjEyOGZhYjA5MQ==',
	),
));

$tax_term_intel_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Intel',
	'taxonomy' => 'tdtax_pc_builds_cpus',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_amd_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'AMD',
	'taxonomy' => 'tdtax_pc_builds_cpus',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdcf_cpu_socket' => '',
		'_tdcf_cpu_socket' => 'ZmllbGRfNjRkNjEwYmUzYTkxYg==',
		'tdcf_cpu_cores' => '',
		'_tdcf_cpu_cores' => 'ZmllbGRfNjRkNjEwZjQzYTkxYw==',
		'tdb_filter_color' => '',
	),
));

$tax_term_i7_11700k_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'i7-11700K',
	'taxonomy' => 'tdtax_pc_builds_cpus',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_intel_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_cpu_socket' => 'TEdBIDEyMDA=',
		'_tdcf_cpu_socket' => 'ZmllbGRfNjRkNjEwYmUzYTkxYg==',
		'tdcf_cpu_cores' => 'OA==',
		'_tdcf_cpu_cores' => 'ZmllbGRfNjRkNjEwZjQzYTkxYw==',
	),
));

$tax_term_i9_11900k_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'i9-11900K',
	'taxonomy' => 'tdtax_pc_builds_cpus',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_intel_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_cpu_socket' => 'TEdBIDEyMDA=',
		'_tdcf_cpu_socket' => 'ZmllbGRfNjRkNjEwYmUzYTkxYg==',
		'tdcf_cpu_cores' => 'OA==',
		'_tdcf_cpu_cores' => 'ZmllbGRfNjRkNjEwZjQzYTkxYw==',
	),
));

$tax_term_i5_11600k_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'i5-11600K',
	'taxonomy' => 'tdtax_pc_builds_cpus',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_intel_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_cpu_socket' => 'TEdBIDEyMDA=',
		'_tdcf_cpu_socket' => 'ZmllbGRfNjRkNjEwYmUzYTkxYg==',
		'tdcf_cpu_cores' => 'Ng==',
		'_tdcf_cpu_cores' => 'ZmllbGRfNjRkNjEwZjQzYTkxYw==',
	),
));

$tax_term_ryzen_7_5800xk_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Ryzen 7 5800XK',
	'taxonomy' => 'tdtax_pc_builds_cpus',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_amd_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_cpu_socket' => 'QU00',
		'_tdcf_cpu_socket' => 'ZmllbGRfNjRkNjEwYmUzYTkxYg==',
		'tdcf_cpu_cores' => 'OA==',
		'_tdcf_cpu_cores' => 'ZmllbGRfNjRkNjEwZjQzYTkxYw==',
	),
));

$tax_term_ryzen_9_5900x_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Ryzen 9 5900X',
	'taxonomy' => 'tdtax_pc_builds_cpus',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_amd_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_cpu_socket' => 'QU00',
		'_tdcf_cpu_socket' => 'ZmllbGRfNjRkNjEwYmUzYTkxYg==',
		'tdcf_cpu_cores' => 'MTI=',
		'_tdcf_cpu_cores' => 'ZmllbGRfNjRkNjEwZjQzYTkxYw==',
	),
));

$tax_term_ryzen_5_5600x_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Ryzen 5 5600X',
	'taxonomy' => 'tdtax_pc_builds_cpus',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_amd_id,
	'description' => '',
	'filter_image' => 'tdx_pic_1',
	'tax_term_meta' => array( 
		'tdcf_cpu_socket' => 'QU00',
		'_tdcf_cpu_socket' => 'ZmllbGRfNjRkNjEwYmUzYTkxYg==',
		'tdcf_cpu_cores' => 'Ng==',
		'_tdcf_cpu_cores' => 'ZmllbGRfNjRkNjEwZjQzYTkxYw==',
		'tdb_filter_color' => '',
	),
));

$tax_term_nvidia_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'NVIDIA',
	'taxonomy' => 'tdtax_pc_builds_grahics',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_amd_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'AMD',
	'taxonomy' => 'tdtax_pc_builds_grahics',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_geforce_rtx_3080_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'GeForce RTX 3080',
	'taxonomy' => 'tdtax_pc_builds_grahics',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_nvidia_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_graphic_chip' => 'R0ExMDI=',
		'_tdcf_graphic_chip' => 'ZmllbGRfNjRkNjExOTc3ZTEzMw==',
		'tdcf_graphic_interface' => 'UENJZSA0LjA=',
		'_tdcf_graphic_interface' => 'ZmllbGRfNjRkNjExYTk3ZTEzNA==',
		'tdcf_graphic_vram' => 'MTBHQiBHRERSNlg=',
		'_tdcf_graphic_vram' => 'ZmllbGRfNjRkOWNmN2JiODlmOQ==',
	),
));

$tax_term_geforce_gtx_1660_ti_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'GeForce GTX 1660 Ti',
	'taxonomy' => 'tdtax_pc_builds_grahics',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_nvidia_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_graphic_chip' => 'VFUxMTY=',
		'_tdcf_graphic_chip' => 'ZmllbGRfNjRkNjExOTc3ZTEzMw==',
		'tdcf_graphic_interface' => 'UENJZSAzLjA=',
		'_tdcf_graphic_interface' => 'ZmllbGRfNjRkNjExYTk3ZTEzNA==',
		'tdcf_graphic_vram' => 'NkdCIEdERFI2',
		'_tdcf_graphic_vram' => 'ZmllbGRfNjRkOWNmN2JiODlmOQ==',
	),
));

$tax_term_geforce_rtx_3070_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'GeForce RTX 3070',
	'taxonomy' => 'tdtax_pc_builds_grahics',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_nvidia_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_graphic_chip' => 'R0ExMDQ=',
		'_tdcf_graphic_chip' => 'ZmllbGRfNjRkNjExOTc3ZTEzMw==',
		'tdcf_graphic_interface' => 'UENJZSAzLjA=',
		'_tdcf_graphic_interface' => 'ZmllbGRfNjRkNjExYTk3ZTEzNA==',
		'tdcf_graphic_vram' => 'OEdCIEdERFI2',
		'_tdcf_graphic_vram' => 'ZmllbGRfNjRkOWNmN2JiODlmOQ==',
	),
));

$tax_term_radeon_rx_6900_xt_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Radeon RX 6900 XT',
	'taxonomy' => 'tdtax_pc_builds_grahics',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_amd_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_graphic_chip' => 'TmF2aSAyMQ==',
		'_tdcf_graphic_chip' => 'ZmllbGRfNjRkNjExOTc3ZTEzMw==',
		'tdcf_graphic_interface' => 'UENJZSA0LjA=',
		'_tdcf_graphic_interface' => 'ZmllbGRfNjRkNjExYTk3ZTEzNA==',
		'tdcf_graphic_vram' => 'MTZHQiBHRERSNg==',
		'_tdcf_graphic_vram' => 'ZmllbGRfNjRkOWNmN2JiODlmOQ==',
	),
));

$tax_term_radeon_rx_6700_xt_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Radeon RX 6700 XT',
	'taxonomy' => 'tdtax_pc_builds_grahics',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_amd_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_graphic_chip' => 'TmF2aSAyMg==',
		'_tdcf_graphic_chip' => 'ZmllbGRfNjRkNjExOTc3ZTEzMw==',
		'tdcf_graphic_interface' => 'UENJZSA0LjA=',
		'_tdcf_graphic_interface' => 'ZmllbGRfNjRkNjExYTk3ZTEzNA==',
		'tdcf_graphic_vram' => 'MTJHQiBHRERSNg==',
		'_tdcf_graphic_vram' => 'ZmllbGRfNjRkOWNmN2JiODlmOQ==',
	),
));

$tax_term_radeon_rx_5600_xt_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Radeon RX 5600 XT',
	'taxonomy' => 'tdtax_pc_builds_grahics',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_amd_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_graphic_chip' => 'TmF2aSAxMA==',
		'_tdcf_graphic_chip' => 'ZmllbGRfNjRkNjExOTc3ZTEzMw==',
		'tdcf_graphic_interface' => 'UENJZSA0LjA=',
		'_tdcf_graphic_interface' => 'ZmllbGRfNjRkNjExYTk3ZTEzNA==',
		'tdcf_graphic_vram' => 'NkdCIEdERFI2',
		'_tdcf_graphic_vram' => 'ZmllbGRfNjRkOWNmN2JiODlmOQ==',
	),
));

$tax_term_corsair_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Corsair',
	'taxonomy' => 'tdtax_pc_builds_memory',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_kingston_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Kingston',
	'taxonomy' => 'tdtax_pc_builds_memory',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_vengeance_lpx_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Vengeance LPX',
	'taxonomy' => 'tdtax_pc_builds_memory',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_corsair_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_memory_type' => 'RERSNA==',
		'_tdcf_memory_type' => 'ZmllbGRfNjRkNjExNmE1NTA5MA==',
		'tdcf_memory_capacity' => 'MTZHQiAoMiB4IDhHQik=',
		'_tdcf_memory_capacity' => 'ZmllbGRfNjRkNjExN2U1NTA5MQ==',
	),
));

$tax_term_dominator_platinum_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Dominator Platinum',
	'taxonomy' => 'tdtax_pc_builds_memory',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_corsair_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_memory_type' => 'RERSNA==',
		'_tdcf_memory_type' => 'ZmllbGRfNjRkNjExNmE1NTA5MA==',
		'tdcf_memory_capacity' => 'MzJHQiAoNCB4IDhHQik=',
		'_tdcf_memory_capacity' => 'ZmllbGRfNjRkNjExN2U1NTA5MQ==',
	),
));

$tax_term_vengeance_rgb_pro_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Vengeance RGB Pro',
	'taxonomy' => 'tdtax_pc_builds_memory',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_corsair_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_memory_type' => 'RERSNA==',
		'_tdcf_memory_type' => 'ZmllbGRfNjRkNjExNmE1NTA5MA==',
		'tdcf_memory_capacity' => 'NjRHQiAoMiB4IDMyR0Ip',
		'_tdcf_memory_capacity' => 'ZmllbGRfNjRkNjExN2U1NTA5MQ==',
	),
));

$tax_term_hyperx_fury_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'HyperX Fury',
	'taxonomy' => 'tdtax_pc_builds_memory',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_kingston_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_memory_type' => 'RERSMw==',
		'_tdcf_memory_type' => 'ZmllbGRfNjRkNjExNmE1NTA5MA==',
		'tdcf_memory_capacity' => 'OEdHQiAoMSB4IDhHQik=',
		'_tdcf_memory_capacity' => 'ZmllbGRfNjRkNjExN2U1NTA5MQ==',
	),
));

$tax_term_hyperx_predator_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'HyperX Predator',
	'taxonomy' => 'tdtax_pc_builds_memory',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_kingston_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_memory_type' => 'RERSNA==',
		'_tdcf_memory_type' => 'ZmllbGRfNjRkNjExNmE1NTA5MA==',
		'tdcf_memory_capacity' => 'MTZHQiAoMiB4IDhHQik=',
		'_tdcf_memory_capacity' => 'ZmllbGRfNjRkNjExN2U1NTA5MQ==',
	),
));

$tax_term_hyperx_impac_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'HyperX Impact',
	'taxonomy' => 'tdtax_pc_builds_memory',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_kingston_id,
	'description' => '',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdcf_memory_type' => 'RERSNA==',
		'_tdcf_memory_type' => 'ZmllbGRfNjRkNjExNmE1NTA5MA==',
		'tdcf_memory_capacity' => 'MTZHQiAoMSB4IDE2R0Ip',
		'_tdcf_memory_capacity' => 'ZmllbGRfNjRkNjExN2U1NTA5MQ==',
		'tdb_filter_color' => '',
	),
));

$tax_term_msi_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'MSI',
	'taxonomy' => 'tdtax_pc_builds_motherboards',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_asus_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'ASUS',
	'taxonomy' => 'tdtax_pc_builds_motherboards',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_asrock_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'ASRock',
	'taxonomy' => 'tdtax_pc_builds_motherboards',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_b550_tomahawk_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'B550 Tomahawk',
	'taxonomy' => 'tdtax_pc_builds_motherboards',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_msi_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_motherboard_chipset' => 'QU1EIEI1NTA=',
		'_tdcf_motherboard_chipset' => 'ZmllbGRfNjRkNjExMzA3MmMwZQ==',
		'tdcf_motherboard_cpu_socket' => 'QU00',
		'_tdcf_motherboard_cpu_socket' => 'ZmllbGRfNjRkNjExNDE3MmMwZg==',
		'tdcf_motherboard_size' => 'QVRY',
		'_tdcf_motherboard_size' => 'ZmllbGRfNjRkNjExNGI3MmMxMA==',
	),
));

$tax_term_mpg_x570_gaming_plus_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'MPG X570 Gaming Plus',
	'taxonomy' => 'tdtax_pc_builds_motherboards',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_msi_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_motherboard_chipset' => 'QU1EIFg1NzA=',
		'_tdcf_motherboard_chipset' => 'ZmllbGRfNjRkNjExMzA3MmMwZQ==',
		'tdcf_motherboard_cpu_socket' => 'QU00',
		'_tdcf_motherboard_cpu_socket' => 'ZmllbGRfNjRkNjExNDE3MmMwZg==',
		'tdcf_motherboard_size' => 'QVRY',
		'_tdcf_motherboard_size' => 'ZmllbGRfNjRkNjExNGI3MmMxMA==',
	),
));

$tax_term_rog_strix_z590_e_gaming_wifi_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'ROG Strix Z590-E Gaming WiFi',
	'taxonomy' => 'tdtax_pc_builds_motherboards',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_asus_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_motherboard_chipset' => 'SW50ZWwgWjU5MA==',
		'_tdcf_motherboard_chipset' => 'ZmllbGRfNjRkNjExMzA3MmMwZQ==',
		'tdcf_motherboard_cpu_socket' => 'TEdBIDEyMDA=',
		'_tdcf_motherboard_cpu_socket' => 'ZmllbGRfNjRkNjExNDE3MmMwZg==',
		'tdcf_motherboard_size' => 'QVRY',
		'_tdcf_motherboard_size' => 'ZmllbGRfNjRkNjExNGI3MmMxMA==',
	),
));

$tax_term_tuf_gaming_b550m_plus_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'TUF Gaming B550M-Plus',
	'taxonomy' => 'tdtax_pc_builds_motherboards',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_asus_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_motherboard_chipset' => 'QU1EIEI1NTA=',
		'_tdcf_motherboard_chipset' => 'ZmllbGRfNjRkNjExMzA3MmMwZQ==',
		'tdcf_motherboard_cpu_socket' => 'QU00',
		'_tdcf_motherboard_cpu_socket' => 'ZmllbGRfNjRkNjExNDE3MmMwZg==',
		'tdcf_motherboard_size' => 'TWljcm8gQVRY',
		'_tdcf_motherboard_size' => 'ZmllbGRfNjRkNjExNGI3MmMxMA==',
	),
));

$tax_term_b450m_pro4_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'B450M Pro4',
	'taxonomy' => 'tdtax_pc_builds_motherboards',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_asrock_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_motherboard_chipset' => 'QU1EIEI0NTA=',
		'_tdcf_motherboard_chipset' => 'ZmllbGRfNjRkNjExMzA3MmMwZQ==',
		'tdcf_motherboard_cpu_socket' => 'QU00',
		'_tdcf_motherboard_cpu_socket' => 'ZmllbGRfNjRkNjExNDE3MmMwZg==',
		'tdcf_motherboard_size' => 'TWljcm8gQVRY',
		'_tdcf_motherboard_size' => 'ZmllbGRfNjRkNjExNGI3MmMxMA==',
	),
));

$tax_term_z590_extreme_wifi_6_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Z590 Extreme WiFi 6',
	'taxonomy' => 'tdtax_pc_builds_motherboards',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_asrock_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_motherboard_chipset' => 'SW50ZWwgWjU5MA==',
		'_tdcf_motherboard_chipset' => 'ZmllbGRfNjRkNjExMzA3MmMwZQ==',
		'tdcf_motherboard_cpu_socket' => 'TEdBIDEyMDA=',
		'_tdcf_motherboard_cpu_socket' => 'ZmllbGRfNjRkNjExNDE3MmMwZg==',
		'tdcf_motherboard_size' => 'QVRY',
		'_tdcf_motherboard_size' => 'ZmllbGRfNjRkNjExNGI3MmMxMA==',
	),
));

$tax_term_evga_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'EVGA',
	'taxonomy' => 'tdtax_pc_builds_psus',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_thermaltake_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Thermaltake',
	'taxonomy' => 'tdtax_pc_builds_psus',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_supernova_850_g3_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Supernova 850 G3',
	'taxonomy' => 'tdtax_pc_builds_psus',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_evga_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_psu_wattage' => 'ODUwVw==',
		'_tdcf_psu_wattage' => 'ZmllbGRfNjRkNjEyMjdmZjEwNA==',
		'tdcf_psu_form_factor' => 'QVRY',
		'_tdcf_psu_form_factor' => 'ZmllbGRfNjRkNjEyMzRmZjEwNQ==',
	),
));

$tax_term_supernova_750_p2_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Supernova 750 P2',
	'taxonomy' => 'tdtax_pc_builds_psus',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_evga_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_psu_wattage' => 'NzUwVw==',
		'_tdcf_psu_wattage' => 'ZmllbGRfNjRkNjEyMjdmZjEwNA==',
		'tdcf_psu_form_factor' => 'QVRY',
		'_tdcf_psu_form_factor' => 'ZmllbGRfNjRkNjEyMzRmZjEwNQ==',
	),
));

$tax_term_supernova_1000_g5_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Supernova 1000 G5',
	'taxonomy' => 'tdtax_pc_builds_psus',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_evga_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_psu_wattage' => 'MTAwMFc=',
		'_tdcf_psu_wattage' => 'ZmllbGRfNjRkNjEyMjdmZjEwNA==',
		'tdcf_psu_form_factor' => 'QVRY',
		'_tdcf_psu_form_factor' => 'ZmllbGRfNjRkNjEyMzRmZjEwNQ==',
	),
));

$tax_term_smart_bm2_600w_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Smart BM2 600W',
	'taxonomy' => 'tdtax_pc_builds_psus',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_thermaltake_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_psu_wattage' => 'NjUwVw==',
		'_tdcf_psu_wattage' => 'ZmllbGRfNjRkNjEyMjdmZjEwNA==',
		'tdcf_psu_form_factor' => 'QVRY',
		'_tdcf_psu_form_factor' => 'ZmllbGRfNjRkNjEyMzRmZjEwNQ==',
	),
));

$tax_term_toughpower_gf1_650w_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Toughpower GF1 650W',
	'taxonomy' => 'tdtax_pc_builds_psus',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_thermaltake_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_psu_wattage' => 'NjUwVw==',
		'_tdcf_psu_wattage' => 'ZmllbGRfNjRkNjEyMjdmZjEwNA==',
		'tdcf_psu_form_factor' => 'QVRY',
		'_tdcf_psu_form_factor' => 'ZmllbGRfNjRkNjEyMzRmZjEwNQ==',
	),
));

$tax_term_toughpower_grand_rgb_750w_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Toughpower Grand RGB 750W',
	'taxonomy' => 'tdtax_pc_builds_psus',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_thermaltake_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_psu_wattage' => 'NzUwVw==',
		'_tdcf_psu_wattage' => 'ZmllbGRfNjRkNjEyMjdmZjEwNA==',
		'tdcf_psu_form_factor' => 'QVRY',
		'_tdcf_psu_form_factor' => 'ZmllbGRfNjRkNjEyMzRmZjEwNQ==',
	),
));

$tax_term_seagate_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Seagate',
	'taxonomy' => 'tdtax_pc_builds_storage',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_wd_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'WD',
	'taxonomy' => 'tdtax_pc_builds_storage',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_samsung_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Samsung',
	'taxonomy' => 'tdtax_pc_builds_storage',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_firecuda_520_nvme_ssd_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'FireCuda 520 NVMe SSD',
	'taxonomy' => 'tdtax_pc_builds_storage',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_seagate_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_storage_form_factor' => 'TS4yIDIyODA=',
		'_tdcf_storage_form_factor' => 'ZmllbGRfNjRkNjExZjVlNWI5Yg==',
		'tdcf_storage_interface' => 'UENJZSA0LjAgeDQ=',
		'_tdcf_storage_interface' => 'ZmllbGRfNjRkNjEyMDhlNWI5Yw==',
		'tdcf_storage_capacity' => 'NTAwR0I=',
		'_tdcf_storage_capacity' => 'ZmllbGRfNjRkNjEyMGVlNWI5ZA==',
	),
));

$tax_term_barracuda_q5_nvme_ssd_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Barracuda Q5 NVMe SSD',
	'taxonomy' => 'tdtax_pc_builds_storage',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_seagate_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_storage_form_factor' => 'TS4yIDIyODA=',
		'_tdcf_storage_form_factor' => 'ZmllbGRfNjRkNjExZjVlNWI5Yg==',
		'tdcf_storage_interface' => 'UENJZSAzLjAgeDQ=',
		'_tdcf_storage_interface' => 'ZmllbGRfNjRkNjEyMDhlNWI5Yw==',
		'tdcf_storage_capacity' => 'NTAwR0I=',
		'_tdcf_storage_capacity' => 'ZmllbGRfNjRkNjEyMGVlNWI5ZA==',
	),
));

$tax_term_blue_sn550_nvme_ssd_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Blue SN550 NVMe SSD',
	'taxonomy' => 'tdtax_pc_builds_storage',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_wd_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_storage_form_factor' => 'TS4yIDIyODA=',
		'_tdcf_storage_form_factor' => 'ZmllbGRfNjRkNjExZjVlNWI5Yg==',
		'tdcf_storage_interface' => 'UENJZSAzLjAgeDQ=',
		'_tdcf_storage_interface' => 'ZmllbGRfNjRkNjEyMDhlNWI5Yw==',
		'tdcf_storage_capacity' => 'MjUwR0I=',
		'_tdcf_storage_capacity' => 'ZmllbGRfNjRkNjEyMGVlNWI5ZA==',
	),
));

$tax_term_black_sn750_nvme_ssd_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Black SN750 NVMe SSD',
	'taxonomy' => 'tdtax_pc_builds_storage',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_wd_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_storage_form_factor' => 'TS4yIDIyODA=',
		'_tdcf_storage_form_factor' => 'ZmllbGRfNjRkNjExZjVlNWI5Yg==',
		'tdcf_storage_interface' => 'UENJZSAzLjAgeDQ=',
		'_tdcf_storage_interface' => 'ZmllbGRfNjRkNjEyMDhlNWI5Yw==',
		'tdcf_storage_capacity' => 'NTAwR0I=',
		'_tdcf_storage_capacity' => 'ZmllbGRfNjRkNjEyMGVlNWI5ZA==',
	),
));

$tax_term_980_pro_nvme_ssd_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => '980 PRO NVMe SSD',
	'taxonomy' => 'tdtax_pc_builds_storage',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_samsung_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_storage_form_factor' => 'TS4yIDIyODA=',
		'_tdcf_storage_form_factor' => 'ZmllbGRfNjRkNjExZjVlNWI5Yg==',
		'tdcf_storage_interface' => 'UENJZSA0LjAgeDQ=',
		'_tdcf_storage_interface' => 'ZmllbGRfNjRkNjEyMDhlNWI5Yw==',
		'tdcf_storage_capacity' => 'MjUwR0I=',
		'_tdcf_storage_capacity' => 'ZmllbGRfNjRkNjEyMGVlNWI5ZA==',
	),
));

$tax_term_970_evo_plus_nvme_ssd_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => '970 EVO Plus NVMe SSD',
	'taxonomy' => 'tdtax_pc_builds_storage',
	'taxonomy_template' => '',
	'parent_id' => $tax_term_samsung_id,
	'description' => '',
	'tax_term_meta' => array( 
		'tdcf_storage_form_factor' => 'TS4yIDIyODA=',
		'_tdcf_storage_form_factor' => 'ZmllbGRfNjRkNjExZjVlNWI5Yg==',
		'tdcf_storage_interface' => 'UENJZSAzLjAgeDQ=',
		'_tdcf_storage_interface' => 'ZmllbGRfNjRkNjEyMDhlNWI5Yw==',
		'tdcf_storage_capacity' => 'NTAwR0I=',
		'_tdcf_storage_capacity' => 'ZmllbGRfNjRkNjEyMGVlNWI5ZA==',
	),
));


/*  ---------------------------------------------------------------------------- 
	CPTs
*/
$cpt_td_post_omega_fusion_matrix_id = td_demo_content::add_cpt( array(
	'title' => 'Omega Fusion Matrix',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_cooler_master_id, $tax_term_mastercase_h500p_mesh_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_arctic_freezer_id, $tax_term_liquid_ii_240_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_amd_id, $tax_term_ryzen_5_5600x_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_amd_id, $tax_term_radeon_rx_5600_xt_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_hyperx_impac_id, $tax_term_kingston_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asrock_id, $tax_term_z590_extreme_wifi_6_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_thermaltake_id, $tax_term_toughpower_grand_rgb_750w_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_970_evo_plus_nvme_ssd_id, $tax_term_samsung_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_nova_valor_nexus_id = td_demo_content::add_cpt( array(
	'title' => 'Nova Valor Nexus',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_cooler_master_id, $tax_term_master_masterbox_mb311l_argb_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_nh_l12s_id, $tax_term_noctua_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_i5_11600k_id, $tax_term_intel_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_geforce_rtx_3070_id, $tax_term_nvidia_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_corsair_id, $tax_term_vengeance_rgb_pro_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asrock_id, $tax_term_b450m_pro4_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_evga_id, $tax_term_supernova_1000_g5_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_980_pro_nvme_ssd_id, $tax_term_samsung_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_voyager_astral_ascent_id = td_demo_content::add_cpt( array(
	'title' => 'Voyager Astral Ascent',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_corsair_id, $tax_term_crystal_series_680x_rgb_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_7_x_id, $tax_term_arctic_freezer_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_amd_id, $tax_term_ryzen_9_5900x_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_amd_id, $tax_term_radeon_rx_6700_xt_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_hyperx_predator_id, $tax_term_kingston_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asus_id, $tax_term_tuf_gaming_b550m_plus_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_thermaltake_id, $tax_term_toughpower_gf1_650w_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_black_sn750_nvme_ssd_id, $tax_term_wd_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_ignite_nexus_beacon_id = td_demo_content::add_cpt( array(
	'title' => 'Ignite Nexus Beacon',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_h710_id, $tax_term_nzxt_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_nh_u12s_id, $tax_term_noctua_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_i9_11900k_id, $tax_term_intel_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_geforce_gtx_1660_ti_id, $tax_term_nvidia_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_corsair_id, $tax_term_dominator_platinum_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_mpg_x570_gaming_plus_id, $tax_term_msi_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_evga_id, $tax_term_supernova_750_p2_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_barracuda_q5_nvme_ssd_id, $tax_term_seagate_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_radiant_ascension_spectrum_id = td_demo_content::add_cpt( array(
	'title' => 'Radiant Ascension Spectrum',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_6',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_4000d_airflow_id, $tax_term_corsair_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_34_esports_duo_id, $tax_term_arctic_freezer_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_amd_id, $tax_term_ryzen_7_5800xk_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_amd_id, $tax_term_radeon_rx_6900_xt_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_hyperx_fury_id, $tax_term_kingston_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asus_id, $tax_term_rog_strix_z590_e_gaming_wifi_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_smart_bm2_600w_id, $tax_term_thermaltake_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_blue_sn550_nvme_ssd_id, $tax_term_wd_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_apex_resonance_realm_id = td_demo_content::add_cpt( array(
	'title' => 'Apex Resonance Realm',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_7',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_h510_id, $tax_term_nzxt_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_nh_d15_id, $tax_term_noctua_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_i7_11700k_id, $tax_term_intel_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_geforce_rtx_3080_id, $tax_term_nvidia_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_corsair_id, $tax_term_vengeance_lpx_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_b550_tomahawk_id, $tax_term_msi_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_evga_id, $tax_term_supernova_850_g3_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_firecuda_520_nvme_ssd_id, $tax_term_seagate_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_echelon_genesis_synthesis_id = td_demo_content::add_cpt( array(
	'title' => 'Echelon Genesis Synthesis',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_8',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_cooler_master_id, $tax_term_mastercase_h500p_mesh_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_arctic_freezer_id, $tax_term_liquid_ii_240_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_amd_id, $tax_term_ryzen_5_5600x_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_amd_id, $tax_term_radeon_rx_5600_xt_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_hyperx_impac_id, $tax_term_kingston_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asrock_id, $tax_term_z590_extreme_wifi_6_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_thermaltake_id, $tax_term_toughpower_grand_rgb_750w_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_970_evo_plus_nvme_ssd_id, $tax_term_samsung_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_stratosphere_phoenix_pulse_id = td_demo_content::add_cpt( array(
	'title' => 'Stratosphere Phoenix Pulse',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_9',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_cooler_master_id, $tax_term_master_masterbox_mb311l_argb_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_nh_l12s_id, $tax_term_noctua_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_i5_11600k_id, $tax_term_intel_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_geforce_rtx_3070_id, $tax_term_nvidia_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_corsair_id, $tax_term_vengeance_rgb_pro_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asrock_id, $tax_term_b450m_pro4_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_evga_id, $tax_term_supernova_1000_g5_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_980_pro_nvme_ssd_id, $tax_term_samsung_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_nebula_ignition_ensemble_id = td_demo_content::add_cpt( array(
	'title' => 'Nebula Ignition Ensemble',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_10',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_corsair_id, $tax_term_crystal_series_680x_rgb_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_7_x_id, $tax_term_arctic_freezer_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_amd_id, $tax_term_ryzen_9_5900x_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_amd_id, $tax_term_radeon_rx_6700_xt_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_hyperx_predator_id, $tax_term_kingston_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asus_id, $tax_term_tuf_gaming_b550m_plus_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_thermaltake_id, $tax_term_toughpower_gf1_650w_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_black_sn750_nvme_ssd_id, $tax_term_wd_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_voyager_vanguard_genesis_id = td_demo_content::add_cpt( array(
	'title' => 'Voyager Vanguard Genesis',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_h710_id, $tax_term_nzxt_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_nh_u12s_id, $tax_term_noctua_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_i9_11900k_id, $tax_term_intel_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_geforce_gtx_1660_ti_id, $tax_term_nvidia_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_corsair_id, $tax_term_dominator_platinum_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_mpg_x570_gaming_plus_id, $tax_term_msi_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_evga_id, $tax_term_supernova_750_p2_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_barracuda_q5_nvme_ssd_id, $tax_term_seagate_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_omega_nexus_odyssey_id = td_demo_content::add_cpt( array(
	'title' => 'Omega Nexus Odyssey',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_4000d_airflow_id, $tax_term_corsair_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_34_esports_duo_id, $tax_term_arctic_freezer_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_amd_id, $tax_term_ryzen_7_5800xk_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_amd_id, $tax_term_radeon_rx_6900_xt_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_hyperx_fury_id, $tax_term_kingston_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asus_id, $tax_term_rog_strix_z590_e_gaming_wifi_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_smart_bm2_600w_id, $tax_term_thermaltake_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_blue_sn550_nvme_ssd_id, $tax_term_wd_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_zenith_horizon_voyager_id = td_demo_content::add_cpt( array(
	'title' => 'Zenith Horizon Voyager',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_h510_id, $tax_term_nzxt_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_nh_d15_id, $tax_term_noctua_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_i7_11700k_id, $tax_term_intel_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_geforce_rtx_3080_id, $tax_term_nvidia_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_corsair_id, $tax_term_vengeance_lpx_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_b550_tomahawk_id, $tax_term_msi_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_evga_id, $tax_term_supernova_850_g3_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_firecuda_520_nvme_ssd_id, $tax_term_seagate_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_aegis_envision_masterpiece_id = td_demo_content::add_cpt( array(
	'title' => 'Aegis Envision Masterpiece',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_cooler_master_id, $tax_term_mastercase_h500p_mesh_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_arctic_freezer_id, $tax_term_liquid_ii_240_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_amd_id, $tax_term_ryzen_5_5600x_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_amd_id, $tax_term_radeon_rx_5600_xt_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_hyperx_impac_id, $tax_term_kingston_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asrock_id, $tax_term_z590_extreme_wifi_6_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_thermaltake_id, $tax_term_toughpower_grand_rgb_750w_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_970_evo_plus_nvme_ssd_id, $tax_term_samsung_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_luminous_stellar_sentinel_id = td_demo_content::add_cpt( array(
	'title' => 'Luminous Stellar Sentinel',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_cooler_master_id, $tax_term_master_masterbox_mb311l_argb_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_nh_l12s_id, $tax_term_noctua_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_i5_11600k_id, $tax_term_intel_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_geforce_rtx_3070_id, $tax_term_nvidia_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_corsair_id, $tax_term_vengeance_rgb_pro_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asrock_id, $tax_term_b450m_pro4_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_evga_id, $tax_term_supernova_1000_g5_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_980_pro_nvme_ssd_id, $tax_term_samsung_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_chronos_apex_aegis_id = td_demo_content::add_cpt( array(
	'title' => 'Chronos Apex Aegis',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_6',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_corsair_id, $tax_term_crystal_series_680x_rgb_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_7_x_id, $tax_term_arctic_freezer_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_amd_id, $tax_term_ryzen_9_5900x_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_amd_id, $tax_term_radeon_rx_6700_xt_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_hyperx_predator_id, $tax_term_kingston_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asus_id, $tax_term_tuf_gaming_b550m_plus_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_thermaltake_id, $tax_term_toughpower_gf1_650w_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_black_sn750_nvme_ssd_id, $tax_term_wd_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_quantum_catalyst_prime_id = td_demo_content::add_cpt( array(
	'title' => 'Quantum Catalyst Prime',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_7',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_h710_id, $tax_term_nzxt_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_nh_u12s_id, $tax_term_noctua_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_i9_11900k_id, $tax_term_intel_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_geforce_gtx_1660_ti_id, $tax_term_nvidia_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_corsair_id, $tax_term_dominator_platinum_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_mpg_x570_gaming_plus_id, $tax_term_msi_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_evga_id, $tax_term_supernova_750_p2_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_barracuda_q5_nvme_ssd_id, $tax_term_seagate_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_echelon_versatility_suite_id = td_demo_content::add_cpt( array(
	'title' => 'Echelon Versatility Suite',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_8',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_4000d_airflow_id, $tax_term_corsair_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_34_esports_duo_id, $tax_term_arctic_freezer_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_amd_id, $tax_term_ryzen_7_5800xk_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_amd_id, $tax_term_radeon_rx_6900_xt_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_hyperx_fury_id, $tax_term_kingston_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asus_id, $tax_term_rog_strix_z590_e_gaming_wifi_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_smart_bm2_600w_id, $tax_term_thermaltake_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_blue_sn550_nvme_ssd_id, $tax_term_wd_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_phoenix_resonance_forge_id = td_demo_content::add_cpt( array(
	'title' => 'Phoenix Resonance Forge',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_9',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_h510_id, $tax_term_nzxt_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_nh_d15_id, $tax_term_noctua_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_i7_11700k_id, $tax_term_intel_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_geforce_rtx_3080_id, $tax_term_nvidia_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_corsair_id, $tax_term_vengeance_lpx_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_b550_tomahawk_id, $tax_term_msi_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_evga_id, $tax_term_supernova_850_g3_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_firecuda_520_nvme_ssd_id, $tax_term_seagate_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_ignite_aurora_ascent_id = td_demo_content::add_cpt( array(
	'title' => 'Ignite Aurora Ascent',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_10',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_cooler_master_id, $tax_term_mastercase_h500p_mesh_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_arctic_freezer_id, $tax_term_liquid_ii_240_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_amd_id, $tax_term_ryzen_5_5600x_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_amd_id, $tax_term_radeon_rx_5600_xt_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_hyperx_impac_id, $tax_term_kingston_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asrock_id, $tax_term_z590_extreme_wifi_6_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_thermaltake_id, $tax_term_toughpower_grand_rgb_750w_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_970_evo_plus_nvme_ssd_id, $tax_term_samsung_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_apex_valor_vanguard_id = td_demo_content::add_cpt( array(
	'title' => 'Apex Valor Vanguard',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_cooler_master_id, $tax_term_master_masterbox_mb311l_argb_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_nh_l12s_id, $tax_term_noctua_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_i5_11600k_id, $tax_term_intel_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_geforce_rtx_3070_id, $tax_term_nvidia_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_corsair_id, $tax_term_vengeance_rgb_pro_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asrock_id, $tax_term_b450m_pro4_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_evga_id, $tax_term_supernova_1000_g5_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_980_pro_nvme_ssd_id, $tax_term_samsung_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_nova_fusion_fortress_id = td_demo_content::add_cpt( array(
	'title' => 'Nova Fusion Fortress',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_corsair_id, $tax_term_crystal_series_680x_rgb_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_7_x_id, $tax_term_arctic_freezer_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_amd_id, $tax_term_ryzen_9_5900x_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_amd_id, $tax_term_radeon_rx_6700_xt_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_hyperx_predator_id, $tax_term_kingston_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asus_id, $tax_term_tuf_gaming_b550m_plus_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_thermaltake_id, $tax_term_toughpower_gf1_650w_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_black_sn750_nvme_ssd_id, $tax_term_wd_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_epic_horizon_engine_id = td_demo_content::add_cpt( array(
	'title' => 'Epic Horizon Engine',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_h710_id, $tax_term_nzxt_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_nh_u12s_id, $tax_term_noctua_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_i9_11900k_id, $tax_term_intel_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_geforce_gtx_1660_ti_id, $tax_term_nvidia_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_corsair_id, $tax_term_dominator_platinum_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_mpg_x570_gaming_plus_id, $tax_term_msi_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_evga_id, $tax_term_supernova_750_p2_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_barracuda_q5_nvme_ssd_id, $tax_term_seagate_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_nebulaera_masterpiece_id = td_demo_content::add_cpt( array(
	'title' => 'NebulaEra Masterpiece',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_4000d_airflow_id, $tax_term_corsair_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_34_esports_duo_id, $tax_term_arctic_freezer_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_amd_id, $tax_term_ryzen_7_5800xk_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_amd_id, $tax_term_radeon_rx_6900_xt_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_hyperx_fury_id, $tax_term_kingston_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asus_id, $tax_term_rog_strix_z590_e_gaming_wifi_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_smart_bm2_600w_id, $tax_term_thermaltake_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_blue_sn550_nvme_ssd_id, $tax_term_wd_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_genesis_gaming_beacon_id = td_demo_content::add_cpt( array(
	'title' => 'Genesis Gaming Beacon',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_h510_id, $tax_term_nzxt_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_nh_d15_id, $tax_term_noctua_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_i7_11700k_id, $tax_term_intel_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_geforce_rtx_3080_id, $tax_term_nvidia_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_corsair_id, $tax_term_vengeance_lpx_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_b550_tomahawk_id, $tax_term_msi_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_evga_id, $tax_term_supernova_850_g3_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_firecuda_520_nvme_ssd_id, $tax_term_seagate_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_radiant_future_proof_rig_id = td_demo_content::add_cpt( array(
	'title' => 'Radiant Future-Proof Rig',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_6',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_cooler_master_id, $tax_term_mastercase_h500p_mesh_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_arctic_freezer_id, $tax_term_liquid_ii_240_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_amd_id, $tax_term_ryzen_5_5600x_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_amd_id, $tax_term_radeon_rx_5600_xt_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_hyperx_impac_id, $tax_term_kingston_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asrock_id, $tax_term_z590_extreme_wifi_6_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_thermaltake_id, $tax_term_toughpower_grand_rgb_750w_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_970_evo_plus_nvme_ssd_id, $tax_term_samsung_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_zenith_infinity_pc_build_id = td_demo_content::add_cpt( array(
	'title' => 'Zenith Infinity PC Build',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_7',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_cooler_master_id, $tax_term_master_masterbox_mb311l_argb_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_nh_l12s_id, $tax_term_noctua_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_i5_11600k_id, $tax_term_intel_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_geforce_rtx_3070_id, $tax_term_nvidia_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_corsair_id, $tax_term_vengeance_rgb_pro_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asrock_id, $tax_term_b450m_pro4_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_evga_id, $tax_term_supernova_1000_g5_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_980_pro_nvme_ssd_id, $tax_term_samsung_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_pinnacle_of_performance_id = td_demo_content::add_cpt( array(
	'title' => 'Pinnacle of Performance',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_8',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_corsair_id, $tax_term_crystal_series_680x_rgb_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_7_x_id, $tax_term_arctic_freezer_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_amd_id, $tax_term_ryzen_9_5900x_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_amd_id, $tax_term_radeon_rx_6700_xt_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_hyperx_predator_id, $tax_term_kingston_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asus_id, $tax_term_tuf_gaming_b550m_plus_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_thermaltake_id, $tax_term_toughpower_gf1_650w_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_black_sn750_nvme_ssd_id, $tax_term_wd_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_stratosphere_titan_build_id = td_demo_content::add_cpt( array(
	'title' => 'Stratosphere Titan Build',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_9',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_h710_id, $tax_term_nzxt_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_nh_u12s_id, $tax_term_noctua_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_i9_11900k_id, $tax_term_intel_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_geforce_gtx_1660_ti_id, $tax_term_nvidia_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_corsair_id, $tax_term_dominator_platinum_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_mpg_x570_gaming_plus_id, $tax_term_msi_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_evga_id, $tax_term_supernova_750_p2_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_barracuda_q5_nvme_ssd_id, $tax_term_seagate_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_hyperion_enigma_system_id = td_demo_content::add_cpt( array(
	'title' => 'Hyperion Enigma System',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_10',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_4000d_airflow_id, $tax_term_corsair_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_34_esports_duo_id, $tax_term_arctic_freezer_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_amd_id, $tax_term_ryzen_7_5800xk_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_amd_id, $tax_term_radeon_rx_6900_xt_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_hyperx_fury_id, $tax_term_kingston_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asus_id, $tax_term_rog_strix_z590_e_gaming_wifi_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_smart_bm2_600w_id, $tax_term_thermaltake_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_blue_sn550_nvme_ssd_id, $tax_term_wd_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_innovators_multitasking_forge_id = td_demo_content::add_cpt( array(
	'title' => 'Innovator\'s Multitasking Forge',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_h510_id, $tax_term_nzxt_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_nh_d15_id, $tax_term_noctua_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_i7_11700k_id, $tax_term_intel_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_geforce_rtx_3080_id, $tax_term_nvidia_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_corsair_id, $tax_term_vengeance_lpx_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_b550_tomahawk_id, $tax_term_msi_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_evga_id, $tax_term_supernova_850_g3_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_firecuda_520_nvme_ssd_id, $tax_term_seagate_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_sovereign_gaming_citadel_id = td_demo_content::add_cpt( array(
	'title' => 'Sovereign Gaming Citadel',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_cooler_master_id, $tax_term_mastercase_h500p_mesh_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_arctic_freezer_id, $tax_term_liquid_ii_240_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_amd_id, $tax_term_ryzen_5_5600x_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_amd_id, $tax_term_radeon_rx_5600_xt_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_hyperx_impac_id, $tax_term_kingston_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asrock_id, $tax_term_z590_extreme_wifi_6_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_thermaltake_id, $tax_term_toughpower_grand_rgb_750w_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_970_evo_plus_nvme_ssd_id, $tax_term_samsung_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_quantum_velocity_rig_id = td_demo_content::add_cpt( array(
	'title' => 'Quantum Velocity Rig',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_cooler_master_id, $tax_term_master_masterbox_mb311l_argb_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_nh_l12s_id, $tax_term_noctua_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_i5_11600k_id, $tax_term_intel_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_geforce_rtx_3070_id, $tax_term_nvidia_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_corsair_id, $tax_term_vengeance_rgb_pro_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asrock_id, $tax_term_b450m_pro4_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_evga_id, $tax_term_supernova_1000_g5_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_980_pro_nvme_ssd_id, $tax_term_samsung_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_elite_performance_arsenal_id = td_demo_content::add_cpt( array(
	'title' => 'Elite Performance Arsenal',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_corsair_id, $tax_term_crystal_series_680x_rgb_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_7_x_id, $tax_term_arctic_freezer_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_amd_id, $tax_term_ryzen_9_5900x_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_amd_id, $tax_term_radeon_rx_6700_xt_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_hyperx_predator_id, $tax_term_kingston_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asus_id, $tax_term_tuf_gaming_b550m_plus_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_thermaltake_id, $tax_term_toughpower_gf1_650w_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_black_sn750_nvme_ssd_id, $tax_term_wd_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_limitless_power_station_id = td_demo_content::add_cpt( array(
	'title' => 'Limitless Power Station',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_h710_id, $tax_term_nzxt_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_nh_u12s_id, $tax_term_noctua_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_i9_11900k_id, $tax_term_intel_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_geforce_gtx_1660_ti_id, $tax_term_nvidia_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_corsair_id, $tax_term_dominator_platinum_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_mpg_x570_gaming_plus_id, $tax_term_msi_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_evga_id, $tax_term_supernova_750_p2_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_barracuda_q5_nvme_ssd_id, $tax_term_seagate_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_futuristic_gaming_nexus_id = td_demo_content::add_cpt( array(
	'title' => 'Futuristic Gaming Nexus',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_6',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_4000d_airflow_id, $tax_term_corsair_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_34_esports_duo_id, $tax_term_arctic_freezer_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_amd_id, $tax_term_ryzen_7_5800xk_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_amd_id, $tax_term_radeon_rx_6900_xt_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_hyperx_fury_id, $tax_term_kingston_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_asus_id, $tax_term_rog_strix_z590_e_gaming_wifi_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_smart_bm2_600w_id, $tax_term_thermaltake_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_blue_sn550_nvme_ssd_id, $tax_term_wd_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));

$cpt_td_post_unleashed_dominator_build_id = td_demo_content::add_cpt( array(
	'title' => 'Unleashed Dominator Build',
	'type' => 'tdcpt_pc_builds',
	'file' => 'tdcpt_pc_builds_default.txt',
	'cpt_image_td_id' => 'td_pic_7',
	'post_meta' => array( 
		'tdcf_build_other' => 'QVNVUyBYb25hciBBRSBTb3VuZCBDYXJkDQpBU1VTIFRodW5kZXJib2x0RVggMyBFeHBhbnNpb24gQ2FyZA0KRWxnYXRvIEdhbWUgQ2FwdHVyZSA0SzYwIFBybyBNSy4yDQpSR0IgTEVEIFN0cmlwcyBmb3IgQ3VzdG9tIExpZ2h0aW5nIEVmZmVjdHM=',
		'_tdcf_build_other' => 'ZmllbGRfNjRkNjEyYzFmZjBiYQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_pc_builds_cases' => array( $tax_term_h510_id, $tax_term_nzxt_id ),
		'tdtax_pc_builds_cooling' => array( $tax_term_nh_d15_id, $tax_term_noctua_id ),
		'tdtax_pc_builds_cpus' => array( $tax_term_i7_11700k_id, $tax_term_intel_id ),
		'tdtax_pc_builds_grahics' => array( $tax_term_geforce_rtx_3080_id, $tax_term_nvidia_id ),
		'tdtax_pc_builds_memory' => array( $tax_term_corsair_id, $tax_term_vengeance_lpx_id ),
		'tdtax_pc_builds_motherboards' => array( $tax_term_b550_tomahawk_id, $tax_term_msi_id ),
		'tdtax_pc_builds_psus' => array( $tax_term_evga_id, $tax_term_supernova_850_g3_id ),
		'tdtax_pc_builds_storage' => array( $tax_term_firecuda_520_nvme_ssd_id, $tax_term_seagate_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('tdx_pic_2', 'tdx_pic_3', 'tdx_pic_4'),
	),
));


/*  ---------------------------------------------------------------------------- 
	MENUS
*/
$menu_td_demo_footer_menu_id = td_demo_menus::create_menu('td-demo-footer-menu', ''); 


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_link( array(
	'title' => 'Terms & Conditions',
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

$menu_td_demo_footer_menu_extra_id = td_demo_menus::create_menu('td-demo-footer-menu-extra', ''); 


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_link( array(
	'title' => 'Help Center',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_link( array(
	'title' => 'Guides',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_link( array(
	'title' => 'Forums',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_link( array(
	'title' => 'Blog',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_td_demo_header_menu_id = td_demo_menus::create_menu('td-demo-header-menu', ''); 


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_page(array(
	'title' => 'Home',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_home_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_page(array(
	'title' => 'Builder',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_builder_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_page(array(
	'title' => 'Completed Builds',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_completed_builds_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_page(array(
	'title' => '<span style="color:#ffc95a">Membership</span>',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_membership_id,
	'parent_id' => ''
));

$menu_td_demo_sub_footer_menu_id = td_demo_menus::create_menu('td-demo-sub-footer-menu', ''); 


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_link( array(
	'title' => 'About',
	'add_to_menu_id' => $menu_td_demo_sub_footer_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_link( array(
	'title' => 'Contact',
	'add_to_menu_id' => $menu_td_demo_sub_footer_menu_id,
	'url' => '#',
	'parent_id' => ''
));


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
