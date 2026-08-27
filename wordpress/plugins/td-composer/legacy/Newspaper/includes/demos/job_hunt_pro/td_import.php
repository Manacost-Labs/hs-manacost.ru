<?php



/*  ----------------------------------------------------------------------------
	EXTERNAL PLUGINS DATA IMPORT
*/
/* -- ACF -- */
// Field groups
$field_group_applicant_profile = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/job_hunt_pro/data/acf/group_63ab0e280ce1a.json' );
$field_group_company_details = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/job_hunt_pro/data/acf/group_639c4de073221.json' );
$field_group_job_application_details = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/job_hunt_pro/data/acf/group_639c61367dcbd.json' );
$field_group_job_details = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/job_hunt_pro/data/acf/group_63a06838b805d.json' );

// Post types
$post_type_companies = td_demo_data::acf_import_post_type( 'https://cloud.tagdiv.com/demos/Newspaper/job_hunt_pro/data/acf/post_type_65113ae30f578.json' );
$post_type_job_applications = td_demo_data::acf_import_post_type( 'https://cloud.tagdiv.com/demos/Newspaper/job_hunt_pro/data/acf/post_type_65113aa5827c1.json' );
$post_type_jobs = td_demo_data::acf_import_post_type( 'https://cloud.tagdiv.com/demos/Newspaper/job_hunt_pro/data/acf/post_type_65113a13f2af8.json' );

// Taxonomies
$taxonomy_benefits = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/job_hunt_pro/data/acf/taxonomy_65113bcf0ba07.json' );
$taxonomy_benefits = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/job_hunt_pro/data/acf/taxonomy_65113cbb6e650.json' );
$taxonomy_categories = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/job_hunt_pro/data/acf/taxonomy_65113beca6c52.json' );
$taxonomy_countries = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/job_hunt_pro/data/acf/taxonomy_65113c637c8d2.json' );
$taxonomy_markets = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/job_hunt_pro/data/acf/taxonomy_65113c0de255c.json' );
$taxonomy_salaries = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/job_hunt_pro/data/acf/taxonomy_65113b2c36828.json' );
$taxonomy_skills = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/job_hunt_pro/data/acf/taxonomy_65113bacd1e0d.json' );
$taxonomy_technologies = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/job_hunt_pro/data/acf/taxonomy_65113c8a63cd4.json' );
$taxonomy_types_of_work = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/job_hunt_pro/data/acf/taxonomy_65113b5c8e64d.json' );



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



$plan_free_plan___companies___job_hunt_pro_id = td_demo_subscription::add_plan( array(
        'name' => 'Free Plan - Companies - Job Hunt PRO',
        'price' => '',
        'interval' => '',
        'interval_count' => 0,
        'trial_days' => '0',
        'is_free' => '1',
        'is_unlimited' => '0',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"98645dff9fba0b6";}',
        'publishing_limits' => 'a:1:{i:0;O:8:"stdClass":3:{s:9:"post_type";s:15:"tdcpt_companies";s:5:"limit";s:1:"1";s:5:"track";b:0;}}',
    )
);

$plan_basic_monthly_plan___companies___job_hunt_pro_id = td_demo_subscription::add_plan( array(
        'name' => 'Basic Monthly Plan - Companies - Job Hunt PRO',
        'price' => '25',
        'interval' => 'month',
        'interval_count' => 1,
        'trial_days' => '0',
        'is_free' => '0',
        'is_unlimited' => '0',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"11645dff9fba156";}',
        'publishing_limits' => 'a:2:{i:0;O:8:"stdClass":3:{s:9:"post_type";s:15:"tdcpt_companies";s:5:"limit";s:1:"4";s:5:"track";b:0;}i:1;O:8:"stdClass":3:{s:9:"post_type";s:10:"tdcpt_jobs";s:5:"limit";s:2:"15";s:5:"track";b:1;}}',
    )
);

$plan_unlimited_monthly_plan___companies___job_hunt_pro_id = td_demo_subscription::add_plan( array(
        'name' => 'Unlimited Monthly Plan - Companies - Job Hunt PRO',
        'price' => '100',
        'interval' => 'month',
        'interval_count' => 1,
        'trial_days' => '0',
        'is_free' => '0',
        'is_unlimited' => '0',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"32645dff9fba1d9";}',
        'publishing_limits' => 'a:2:{i:0;O:8:"stdClass":3:{s:9:"post_type";s:15:"tdcpt_companies";s:5:"limit";s:6:"100000";s:5:"track";b:1;}i:1;O:8:"stdClass":3:{s:9:"post_type";s:10:"tdcpt_jobs";s:5:"limit";s:6:"100000";s:5:"track";b:1;}}',
    )
);

$plan_free_plan___job_applicants___job_hunt_pro_id = td_demo_subscription::add_plan( array(
        'name' => 'Free Plan - Job Applicants - Job Hunt PRO',
        'price' => '',
        'interval' => '',
        'interval_count' => 0,
        'trial_days' => '0',
        'is_free' => '1',
        'is_unlimited' => '0',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"98645dff9fba273";}',
        'publishing_limits' => 'a:1:{i:0;O:8:"stdClass":3:{s:9:"post_type";s:14:"tdcpt_job_apps";s:5:"limit";s:1:"5";s:5:"track";b:0;}}',
    )
);

$plan_beginner_plan___job_applicants___job_hunt_pro_id = td_demo_subscription::add_plan( array(
        'name' => 'Beginner Plan - Job Applicants - Job Hunt PRO',
        'price' => '10',
        'interval' => 'month',
        'interval_count' => 1,
        'trial_days' => '0',
        'is_free' => '0',
        'is_unlimited' => '0',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"72645dff9fba4cd";}',
        'publishing_limits' => 'a:1:{i:0;O:8:"stdClass":3:{s:9:"post_type";s:14:"tdcpt_job_apps";s:5:"limit";s:2:"20";s:5:"track";b:1;}}',
    )
);

$plan_advanced_plan___job_applicants___job_hunt_pro_id = td_demo_subscription::add_plan( array(
        'name' => 'Advanced Plan - Job Applicants - Job Hunt PRO',
        'price' => '25',
        'interval' => 'month',
        'interval_count' => 1,
        'trial_days' => '0',
        'is_free' => '0',
        'is_unlimited' => '0',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:14:"0645dff9fba56d";}',
        'publishing_limits' => 'a:1:{i:0;O:8:"stdClass":3:{s:9:"post_type";s:14:"tdcpt_job_apps";s:5:"limit";s:2:"50";s:5:"track";b:1;}}',
    )
);

$page_payment_page_id_id = td_demo_content::add_page( array(
    'title' => 'Checkout - Job Hunt PRO',
    'file' => 'checkout.txt',
    'template' => 'default',
));

td_demo_subscription::add_option( array(
        'name' => 'payment_page_id',
        'value' => $page_payment_page_id_id,
    )
);

$page_create_account_page_id_id = td_demo_content::add_page( array(
    'title' => 'Login/Register - Job Hunt PRO',
    'file' => 'login.txt',
    'template' => 'default',
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
$template_module_template_companies_large_id = td_demo_content::add_cloud_template( array(
    'title' => 'Module Template - Companies - Large - Job Hunt PRO',
    'file' => 'module_template_companies_large_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '1497',
));

$template_module_template_jobs_large_id = td_demo_content::add_cloud_template( array(
    'title' => 'Module Template - Jobs - Large - Job Hunt PRO',
    'file' => 'module_template_jobs_large_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '1192',
));

$template_module_template_companies_small_id = td_demo_content::add_cloud_template( array(
    'title' => 'Module Template - Companies - Small - Job Hunt PRO',
    'file' => 'module_template_companies_small_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '984',
));

$template_module_template_header_menu_id = td_demo_content::add_cloud_template( array(
    'title' => 'Module Template - Header Menu - Job Hunt PRO',
    'file' => 'module_template_header_menu_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '519',
));


/*  ----------------------------------------------------------------------------
	ATTACHMENTS
*/


/*  ----------------------------------------------------------------------------
	MENUS
*/
$menu_td_demo_custom_menu_id = td_demo_menus::create_menu('td-demo-custom-menu', '');
$menu_td_demo_footer_menu_id = td_demo_menus::create_menu('td-demo-footer-menu', '');
$menu_td_demo_footer_menu_extra_id = td_demo_menus::create_menu('td-demo-footer-menu-extra', '');
$menu_td_demo_header_menu_id = td_demo_menus::create_menu('td-demo-header-menu', '');
$menu_td_demo_header_menu_id = td_demo_menus::create_menu('td-demo-header-menu', '');
$menu_td_demo_sidebar_menu_id = td_demo_menus::create_menu('td-demo-sidebar-menu', '');




/*  ----------------------------------------------------------------------------
	PAGES
*/

$page_pricing_id = td_demo_content::add_page( array(
    'title' => 'Pricing',
    'file' => 'pricing.txt',
    'template' => 'default',
    'demo_unique_id' => '73645dff9fd62c5',
));

$page_my_account_bookmarked_jobs_id = td_demo_content::add_page( array(
    'title' => 'My Account - Bookmarked jobs',
    'file' => 'my_account_bookmarked_jobs.txt',
    'demo_unique_id' => '36645dff9fd13ea',
));

$page_my_account_account_details_id = td_demo_content::add_page( array(
    'title' => 'My Account - Account details',
    'file' => 'my_account_account_details.txt',
    'demo_unique_id' => '49645dff9fd185b',
));

$page_my_account_job_applications_id = td_demo_content::add_page( array(
    'title' => 'My Account – Job applications',
    'file' => 'my_account_job_applications.txt',
    'demo_unique_id' => '97645dff9fd1cd3',
));

$page_submit_job_id = td_demo_content::add_page( array(
    'title' => 'Post a job',
    'file' => 'submit_job.txt',
    'template' => 'default',
    'demo_unique_id' => '29645dff9fd5725',
));

$page_my_account_jobs_id = td_demo_content::add_page( array(
    'title' => 'My Account – Jobs',
    'file' => 'my_account_jobs.txt',
    'demo_unique_id' => '96645dff9fd210f',
));

$page_create_a_company_id = td_demo_content::add_page( array(
    'title' => 'Create a company',
    'file' => 'create_a_company.txt',
    'demo_unique_id' => '16645dff9fd262b',
));

$page_my_account_companies_id = td_demo_content::add_page( array(
    'title' => 'My Account - Companies',
    'file' => 'my_account_companies.txt',
    'demo_unique_id' => '81645dff9fd2a55',
));

$page_my_account_page_id_id = td_demo_content::add_page( array(
    'title' => 'My Account - Job Hunt PRO',
    'file' => 'my_account.txt',
    'template' => 'default',
    'demo_unique_id' => '36645dff9fd13eb',
));

td_demo_subscription::add_option( array(
        'name' => 'my_account_page_id',
        'value' => $page_my_account_page_id_id,
    )
);

$page_company_cpt_template_jobs_id = td_demo_content::add_page( array(
    'title' => 'Company CPT Template - Jobs',
    'file' => 'company_cpt_template_jobs.txt',
    'demo_unique_id' => '54645dff9fd2e9f',
));

$page_company_cpt_template_overview_id = td_demo_content::add_page( array(
    'title' => 'Company CPT Template - Overview',
    'file' => 'company_cpt_template_overview.txt',
    'demo_unique_id' => '64645dff9fd3346',
));

$page_job_application_form_id = td_demo_content::add_page( array(
    'title' => 'Job application form',
    'file' => 'job_application_form.txt',
    'demo_unique_id' => '34645dff9fd3818',
));

$page_menu_page_mobile_id = td_demo_content::add_page( array(
    'title' => 'Menu page - Mobile',
    'file' => 'menu_page_mobile.txt',
    'demo_unique_id' => '88645dff9fd3c92',
));

$page_jobs_id = td_demo_content::add_page( array(
    'title' => 'Jobs',
    'file' => 'jobs.txt',
    'demo_unique_id' => '11645dff9fd5c32',
));

$page_menu_page_jobs_id = td_demo_content::add_page( array(
    'title' => 'Menu page - Jobs',
    'file' => 'menu_page_jobs.txt',
    'demo_unique_id' => '69645dff9fd4144',
));

$page_companies_id = td_demo_content::add_page( array(
    'title' => 'Companies',
    'file' => 'companies.txt',
    'demo_unique_id' => '75645dff9fd5147',
));

$page_menu_page_companies_id = td_demo_content::add_page( array(
    'title' => 'Menu page - Companies',
    'file' => 'menu_page_companies.txt',
    'demo_unique_id' => '91645dff9fd4984',
));

$page_home_id = td_demo_content::add_page( array(
    'title' => 'Home',
    'file' => 'home.txt',
    'homepage' => true,
    'demo_unique_id' => '58645dff9fd6aa7',
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
        'tds_paid_subs_plan_ids' => [$plan_free_plan___companies___job_hunt_pro_id],
        'tds_paid_subs_page_id' => $page_pricing_id,
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

td_util::update_option('tds_demo_options', 'a:1:{s:5:"plans";a:6:{i:0;a:2:{s:9:"unique_id";s:15:"98645dff9fba0b6";s:4:"name";s:36:"Free Plan - Companies - Job Hunt PRO";}i:1;a:2:{s:9:"unique_id";s:15:"11645dff9fba156";s:4:"name";s:45:"Basic Monthly Plan - Companies - Job Hunt PRO";}i:2;a:2:{s:9:"unique_id";s:15:"32645dff9fba1d9";s:4:"name";s:49:"Unlimited Monthly Plan - Companies - Job Hunt PRO";}i:3;a:2:{s:9:"unique_id";s:15:"98645dff9fba273";s:4:"name";s:41:"Free Plan - Job Applicants - Job Hunt PRO";}i:4;a:2:{s:9:"unique_id";s:15:"72645dff9fba4cd";s:4:"name";s:45:"Beginner Plan - Job Applicants - Job Hunt PRO";}i:5;a:2:{s:9:"unique_id";s:14:"0645dff9fba56d";s:4:"name";s:45:"Advanced Plan - Job Applicants - Job Hunt PRO";}}}');


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
$tax_term_0_25000_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => '0 - 25.000',
    'taxonomy' => 'tdtax_job_salaries',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_25000_50000_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => '25.000 - 50.000',
    'taxonomy' => 'tdtax_job_salaries',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_50000_75000_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => '50.000 - 75.000',
    'taxonomy' => 'tdtax_job_salaries',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_75000_100000_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => '75.000 - 100.000',
    'taxonomy' => 'tdtax_job_salaries',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_150000_200000_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => '150.000 - 200.000',
    'taxonomy' => 'tdtax_job_salaries',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_100_000_150_000_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => '100.000 - 150.000',
    'taxonomy' => 'tdtax_job_salaries',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_full_time_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Full time',
    'taxonomy' => 'tdtax_job_work_types',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_part_time_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Part time',
    'taxonomy' => 'tdtax_job_work_types',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_temporary_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Temporary',
    'taxonomy' => 'tdtax_job_work_types',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_intern_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Intern',
    'taxonomy' => 'tdtax_job_work_types',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_other_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Other',
    'taxonomy' => 'tdtax_job_work_types',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_volunteer_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Volunteer',
    'taxonomy' => 'tdtax_job_work_types',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_software_development_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Software development',
    'taxonomy' => 'tdtax_job_skills',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_back_end_development_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Back end development',
    'taxonomy' => 'tdtax_job_skills',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_front_end_development_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Front end development',
    'taxonomy' => 'tdtax_job_skills',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_javascript_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'JavaScript',
    'taxonomy' => 'tdtax_job_skills',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_php_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'PHP',
    'taxonomy' => 'tdtax_job_skills',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_software_engineering_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Software engineering',
    'taxonomy' => 'tdtax_job_skills',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_react_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'React',
    'taxonomy' => 'tdtax_job_skills',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_css_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'CSS',
    'taxonomy' => 'tdtax_job_skills',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_company_meals_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Company meals',
    'taxonomy' => 'tdtax_job_benefits',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
));

$tax_term_equity_benefits_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Equity benefits',
    'taxonomy' => 'tdtax_job_benefits',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
));

$tax_term_flexible_working_hours_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Flexible working hours',
    'taxonomy' => 'tdtax_job_benefits',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
));

$tax_term_life_insurance_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Life insurance',
    'taxonomy' => 'tdtax_job_benefits',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
));

$tax_term_generous_vacation_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Generous vacation',
    'taxonomy' => 'tdtax_job_benefits',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
));

$tax_term_healthcare_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Healthcare',
    'taxonomy' => 'tdtax_job_benefits',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
));

$tax_term_paid_parental_leave_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Paid parental leave',
    'taxonomy' => 'tdtax_job_benefits',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
));

$tax_term_paid_vacation_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Paid vacation',
    'taxonomy' => 'tdtax_job_benefits',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
));

$tax_term_software_engineer_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Software engineer',
    'taxonomy' => 'tdtax_job_categories',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_ux_designer_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'UX Designer',
    'taxonomy' => 'tdtax_job_categories',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_product_designer_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Product designer',
    'taxonomy' => 'tdtax_job_categories',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_customer_support_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Customer support',
    'taxonomy' => 'tdtax_job_categories',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_back_end_developer_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Back end developer',
    'taxonomy' => 'tdtax_job_categories',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_front_end_developer_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Front end developer',
    'taxonomy' => 'tdtax_job_categories',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_saas_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'SaaS',
    'taxonomy' => 'tdtax_company_markets',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_enterprise_software_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Enterprise Software',
    'taxonomy' => 'tdtax_company_markets',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_software_development_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Software Development',
    'taxonomy' => 'tdtax_company_markets',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_web_development_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Web Development',
    'taxonomy' => 'tdtax_company_markets',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_fintech_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Fintech',
    'taxonomy' => 'tdtax_company_markets',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_ecommerce_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'eCommerce',
    'taxonomy' => 'tdtax_company_markets',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_payments_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Payments',
    'taxonomy' => 'tdtax_company_markets',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_marketplace_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Marketplace',
    'taxonomy' => 'tdtax_company_markets',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_romania_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Romania',
    'taxonomy' => 'tdtax_company_countries',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_canada_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Canada',
    'taxonomy' => 'tdtax_company_countries',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_germany_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Germany',
    'taxonomy' => 'tdtax_company_countries',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_spain_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Spain',
    'taxonomy' => 'tdtax_company_countries',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_brazil_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Brazil',
    'taxonomy' => 'tdtax_company_countries',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_india_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'India',
    'taxonomy' => 'tdtax_company_countries',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_australia_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Australia',
    'taxonomy' => 'tdtax_company_countries',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_south_africa_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'South Africa',
    'taxonomy' => 'tdtax_company_countries',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_javascript_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'JavaScript',
    'taxonomy' => 'tdtax_company_technologies',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_c_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'C++',
    'taxonomy' => 'tdtax_company_technologies',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_php_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'PHP',
    'taxonomy' => 'tdtax_company_technologies',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_html_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'HTML',
    'taxonomy' => 'tdtax_company_technologies',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_css_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'CSS',
    'taxonomy' => 'tdtax_company_technologies',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_figma_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Figma',
    'taxonomy' => 'tdtax_company_technologies',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_java_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Java',
    'taxonomy' => 'tdtax_company_technologies',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_vue_js_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Vue.js',
    'taxonomy' => 'tdtax_company_technologies',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_company_meals_2_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Company meals',
    'taxonomy' => 'tdtax_company_benefits',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_equity_benefits_2_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Equity benefits',
    'taxonomy' => 'tdtax_company_benefits',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_flexible_working_hours_2_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Flexible working hours',
    'taxonomy' => 'tdtax_company_benefits',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_generous_vacation_2_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Generous vacation',
    'taxonomy' => 'tdtax_company_benefits',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_healthcare_2_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Healthcare',
    'taxonomy' => 'tdtax_company_benefits',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_life_insurance_2_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Life insurance',
    'taxonomy' => 'tdtax_company_benefits',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_paid_parental_leave_2_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Paid parental leave',
    'taxonomy' => 'tdtax_company_benefits',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));

$tax_term_paid_vacation_2_id = td_demo_tax::add_taxonomy( array(
    'taxonomy_name' => 'Paid vacation',
    'taxonomy' => 'tdtax_company_benefits',
    'taxonomy_template' => '',
    'parent_id' => 0,
    'description' => '',
    'filter_image' => '',
    'tax_term_meta' => array(
        'tdb_filter_color' => '',
    ),
));


/*  ----------------------------------------------------------------------------
	CPTs
*/
$cpt_strategic_finance_lead_2_id = td_demo_content::add_cpt( array(
    'title' => 'Strategic Finance Lead',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_1',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_0_25000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_temporary_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_machine_learning_engineer_id = td_demo_content::add_cpt( array(
    'title' => 'Machine Learning Engineer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_2',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_0_25000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_software_engineer_frontend_2_id = td_demo_content::add_cpt( array(
    'title' => 'Software Engineer (Frontend)',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_3',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_0_25000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_part_time_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_channel_account_manager_id = td_demo_content::add_cpt( array(
    'title' => 'Channel Account Manager',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_4',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_0_25000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_other_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_binance_identity_service_lead_3_id = td_demo_content::add_cpt( array(
    'title' => 'Binance Identity Service Lead',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_5',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_25000_50000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_smart_contract_developer_2_id = td_demo_content::add_cpt( array(
    'title' => 'Smart Contract Developer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_6',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_25000_50000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_part_time_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_enterprise_support_engineer_2_id = td_demo_content::add_cpt( array(
    'title' => 'Enterprise Support Engineer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_7',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_25000_50000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_part_time_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_it_compliance_lead_2_id = td_demo_content::add_cpt( array(
    'title' => 'IT Compliance Lead',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_8',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_25000_50000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_temporary_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_machine_learning_engineer_3_id = td_demo_content::add_cpt( array(
    'title' => 'Machine Learning Engineer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_2',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_50000_75000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_partner_growth_manager_2_id = td_demo_content::add_cpt( array(
    'title' => 'Partner Growth Manager',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_9',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_50000_75000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_android_engineer_payments_id = td_demo_content::add_cpt( array(
    'title' => 'Android Engineer - Payments',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_6',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_50000_75000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_part_time_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_junior_conflict_risk_analyst_3_id = td_demo_content::add_cpt( array(
    'title' => 'Junior Conflict Risk Analyst',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_10',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_50000_75000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_part_time_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_channel_account_manager_2_id = td_demo_content::add_cpt( array(
    'title' => 'Channel Account Manager',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_1',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_75000_100000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_other_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_founding_fullstack_engineer_2_id = td_demo_content::add_cpt( array(
    'title' => 'Founding FullStack Engineer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_2',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_75000_100000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_other_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_binance_identity_service_lead_2_id = td_demo_content::add_cpt( array(
    'title' => 'Binance Identity Service Lead',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_3',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_75000_100000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_machine_learning_engineer_2_id = td_demo_content::add_cpt( array(
    'title' => 'Machine Learning Engineer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_4',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_75000_100000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_lead_product_designer_2_id = td_demo_content::add_cpt( array(
    'title' => 'Lead Product Designer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_5',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_100_000_150_000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_part_time_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_strategic_finance_analyst_id = td_demo_content::add_cpt( array(
    'title' => 'Strategic Finance Analyst',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_6',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_100_000_150_000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_part_time_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_binance_identity_service_lead_id = td_demo_content::add_cpt( array(
    'title' => 'Binance Identity Service Lead',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_7',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_100_000_150_000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_talent_strategy_manager_id = td_demo_content::add_cpt( array(
    'title' => 'Talent Strategy Manager',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_8',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_100_000_150_000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_part_time_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_founding_fullstack_engineer_id = td_demo_content::add_cpt( array(
    'title' => 'Founding FullStack Engineer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_9',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_150000_200000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_other_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_senior_staff_data_scientist_2_id = td_demo_content::add_cpt( array(
    'title' => 'Senior Staff Data Scientist',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_10',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_150000_200000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_azure_cloud_architect_id = td_demo_content::add_cpt( array(
    'title' => 'Azure Cloud Architect',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_1',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_150000_200000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_part_time_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_senior_product_manager_id = td_demo_content::add_cpt( array(
    'title' => 'Senior Product Manager',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_2',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_150000_200000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_part_time_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_enterprise_support_engineer_id = td_demo_content::add_cpt( array(
    'title' => 'Enterprise Support Engineer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_3',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_0_25000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_part_time_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_partnerships_manager_id = td_demo_content::add_cpt( array(
    'title' => 'Partnerships Manager',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_4',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_0_25000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_part_time_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_senior_product_manager_2_id = td_demo_content::add_cpt( array(
    'title' => 'Senior Product Manager',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_5',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_0_25000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_part_time_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_full_stack_web_engineer_2_id = td_demo_content::add_cpt( array(
    'title' => 'Full Stack Web Engineer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_6',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_0_25000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_other_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_growth_marketing_manager_id = td_demo_content::add_cpt( array(
    'title' => 'Growth Marketing Manager',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_7',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_25000_50000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_part_time_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_senior_marketing_manager_2_id = td_demo_content::add_cpt( array(
    'title' => 'Senior Marketing Manager',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_8',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_25000_50000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_temporary_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_talent_strategy_manager_2_id = td_demo_content::add_cpt( array(
    'title' => 'Talent Strategy Manager',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_9',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_25000_50000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_part_time_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_developer_advocate_id = td_demo_content::add_cpt( array(
    'title' => 'Developer Advocate',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_10',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_25000_50000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_part_time_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_cloud_security_engineer_id = td_demo_content::add_cpt( array(
    'title' => 'Cloud Security Engineer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_1',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_50000_75000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_other_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_global_travel_manager_id = td_demo_content::add_cpt( array(
    'title' => 'Global Travel Manager',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_2',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_50000_75000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_other_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_partner_growth_manager_id = td_demo_content::add_cpt( array(
    'title' => 'Partner Growth Manager',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_3',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_50000_75000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_resume_specialist_part_time_2_id = td_demo_content::add_cpt( array(
    'title' => 'Resume Specialist (Part-time)',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_4',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_50000_75000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_temporary_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_director_of_engineering_id = td_demo_content::add_cpt( array(
    'title' => 'Director of Engineering',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_5',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_75000_100000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_junior_conflict_risk_analyst_id = td_demo_content::add_cpt( array(
    'title' => 'Junior Conflict Risk Analyst',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_6',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_75000_100000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_part_time_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_smart_contract_developer_id = td_demo_content::add_cpt( array(
    'title' => 'Smart Contract Developer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_7',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_75000_100000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_part_time_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_growth_marketing_manager_2_id = td_demo_content::add_cpt( array(
    'title' => 'Growth Marketing Manager',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_8',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_75000_100000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_part_time_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_junior_conflict_risk_analyst_2_id = td_demo_content::add_cpt( array(
    'title' => 'Junior Conflict Risk Analyst',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_9',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_150000_200000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_part_time_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_senior_software_engineer_id = td_demo_content::add_cpt( array(
    'title' => 'Senior Software Engineer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_10',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_150000_200000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_part_time_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_lead_product_designer_id = td_demo_content::add_cpt( array(
    'title' => 'Lead Product Designer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_1',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_150000_200000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_part_time_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_senior_marketing_manager_id = td_demo_content::add_cpt( array(
    'title' => 'Senior Marketing Manager',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_2',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_150000_200000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_temporary_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_technical_program_manager_id = td_demo_content::add_cpt( array(
    'title' => 'Technical Program Manager',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_3',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_100_000_150_000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_part_time_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_business_development_officer_id = td_demo_content::add_cpt( array(
    'title' => 'Business Development Officer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_4',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_100_000_150_000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_part_time_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_full_stack_web_engineer_id = td_demo_content::add_cpt( array(
    'title' => 'Full Stack Web Engineer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_5',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_100_000_150_000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_other_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_it_compliance_lead_id = td_demo_content::add_cpt( array(
    'title' => 'IT Compliance Lead',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_6',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_100_000_150_000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_temporary_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_technical_support_engineer_id = td_demo_content::add_cpt( array(
    'title' => 'Technical Support Engineer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_7',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_0_25000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_part_time_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_senior_backend_developer_id = td_demo_content::add_cpt( array(
    'title' => 'Senior Backend Developer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_8',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_0_25000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_part_time_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_cloud_security_engineer_2_id = td_demo_content::add_cpt( array(
    'title' => 'Cloud Security Engineer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_9',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_0_25000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_other_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_staff_product_researcher_id = td_demo_content::add_cpt( array(
    'title' => 'Staff Product Researcher',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_10',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_0_25000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_customer_education_specialist_id = td_demo_content::add_cpt( array(
    'title' => 'Customer Education Specialist',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_1',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_25000_50000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_other_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_software_engineer_frontend_id = td_demo_content::add_cpt( array(
    'title' => 'Software Engineer (Frontend)',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_2',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_25000_50000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_part_time_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_strategic_finance_lead_id = td_demo_content::add_cpt( array(
    'title' => 'Strategic Finance Lead',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_3',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_25000_50000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_temporary_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_mid_market_account_executive_id = td_demo_content::add_cpt( array(
    'title' => 'Mid-Market Account Executive',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_4',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_25000_50000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_other_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_resume_specialist_part_time_id = td_demo_content::add_cpt( array(
    'title' => 'Resume Specialist (Part-time)',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_5',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_50000_75000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_temporary_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_generous_vacation_id, $tax_term_healthcare_id, $tax_term_paid_parental_leave_id, $tax_term_paid_vacation_id ),
        'tdtax_job_categories' => array( $tax_term_front_end_developer_id, $tax_term_product_designer_id, $tax_term_ux_designer_id ),
    ),
));

$cpt_junior_software_engineer_id = td_demo_content::add_cpt( array(
    'title' => 'Junior Software Engineer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_6',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_50000_75000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_part_time_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_back_end_development_id, $tax_term_php_id, $tax_term_software_development_id, $tax_term_software_engineering_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_staff_software_engineer_id = td_demo_content::add_cpt( array(
    'title' => 'Staff Software Engineer',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_7',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_50000_75000_id ),
        'tdtax_job_work_types' => array( $tax_term_intern_id, $tax_term_other_id, $tax_term_volunteer_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_senior_staff_data_scientist_id = td_demo_content::add_cpt( array(
    'title' => 'Senior Staff Data Scientist',
    'type' => 'tdcpt_jobs',
    'file' => 'tdcpt_jobs_default.txt',
    'cpt_image_td_id' => 'td_pic_8',
    'post_meta' => array(
        'tdcf_job_phone_number' => 'MTIxMjM2NTQ4NTE=',
        '_tdcf_job_phone_number' => 'ZmllbGRfNjNhMDY4M2EzNmVmMw==',
        'tdcf_job_email_address' => 'am9ic0BleGFtcGxlLmNvbQ==',
        '_tdcf_job_email_address' => 'ZmllbGRfNjNhMDY4NjEzNmVmNA==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_job_salaries' => array( $tax_term_50000_75000_id ),
        'tdtax_job_work_types' => array( $tax_term_full_time_id, $tax_term_other_id, $tax_term_temporary_id ),
        'tdtax_job_skills' => array( $tax_term_css_id, $tax_term_front_end_development_id, $tax_term_javascript_id, $tax_term_react_id ),
        'tdtax_job_benefits' => array( $tax_term_company_meals_id, $tax_term_equity_benefits_id, $tax_term_flexible_working_hours_id, $tax_term_life_insurance_id ),
        'tdtax_job_categories' => array( $tax_term_back_end_developer_id, $tax_term_customer_support_id, $tax_term_software_engineer_id ),
    ),
));

$cpt_fusion_technologies_id = td_demo_content::add_cpt( array(
    'title' => 'Fusion Technologies',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_9',
    'post_meta' => array(
        'tdcf_company_size' => 'MTAwMA==',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MjAwOA==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'RGF2aXPCoElyYQ==',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_ecommerce_id, $tax_term_enterprise_software_id, $tax_term_software_development_id, $tax_term_web_development_id ),
        'tdtax_company_countries' => array( $tax_term_australia_id, $tax_term_brazil_id, $tax_term_canada_id, $tax_term_south_africa_id ),
        'tdtax_company_technologies' => array( $tax_term_css_id, $tax_term_html_id, $tax_term_javascript_id, $tax_term_php_id ),
        'tdtax_company_benefits' => array( $tax_term_generous_vacation_2_id, $tax_term_healthcare_2_id, $tax_term_paid_parental_leave_2_id, $tax_term_paid_vacation_2_id ),
    ),
));

$cpt_interlock_id = td_demo_content::add_cpt( array(
    'title' => 'Interlock',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_10',
    'post_meta' => array(
        'tdcf_company_size' => 'NTYw',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MjAyMg==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'QXZpYW5hwqBBbmdlbGluYQ==',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_fintech_id, $tax_term_marketplace_id, $tax_term_payments_id, $tax_term_saas_id ),
        'tdtax_company_countries' => array( $tax_term_australia_id, $tax_term_brazil_id, $tax_term_canada_id, $tax_term_south_africa_id ),
        'tdtax_company_technologies' => array( $tax_term_c_id, $tax_term_figma_id, $tax_term_java_id, $tax_term_vue_js_id ),
        'tdtax_company_benefits' => array( $tax_term_company_meals_2_id, $tax_term_equity_benefits_2_id, $tax_term_flexible_working_hours_2_id, $tax_term_life_insurance_2_id ),
    ),
));

$cpt_ui_logic_id = td_demo_content::add_cpt( array(
    'title' => 'UI Logic',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_1',
    'post_meta' => array(
        'tdcf_company_size' => 'MjQ1',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MTk4OQ==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'Qm9iIFJpY2hhcmRz',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_fintech_id, $tax_term_marketplace_id, $tax_term_payments_id, $tax_term_saas_id ),
        'tdtax_company_countries' => array( $tax_term_australia_id, $tax_term_brazil_id, $tax_term_canada_id, $tax_term_south_africa_id ),
        'tdtax_company_technologies' => array( $tax_term_c_id, $tax_term_figma_id, $tax_term_java_id, $tax_term_vue_js_id ),
        'tdtax_company_benefits' => array( $tax_term_company_meals_2_id, $tax_term_equity_benefits_2_id, $tax_term_flexible_working_hours_2_id, $tax_term_life_insurance_2_id ),
    ),
));

$cpt_inovate_id = td_demo_content::add_cpt( array(
    'title' => 'Inovate',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_2',
    'post_meta' => array(
        'tdcf_company_size' => 'MzAwMg==',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MjAyMQ==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'Um9iZXJ0IFdpbGRl',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_ecommerce_id, $tax_term_enterprise_software_id, $tax_term_software_development_id, $tax_term_web_development_id ),
        'tdtax_company_countries' => array( $tax_term_australia_id, $tax_term_brazil_id, $tax_term_canada_id, $tax_term_south_africa_id ),
        'tdtax_company_technologies' => array( $tax_term_css_id, $tax_term_html_id, $tax_term_javascript_id, $tax_term_php_id ),
        'tdtax_company_benefits' => array( $tax_term_company_meals_2_id, $tax_term_equity_benefits_2_id, $tax_term_flexible_working_hours_2_id, $tax_term_life_insurance_2_id ),
    ),
));

$cpt_technique_pro_id = td_demo_content::add_cpt( array(
    'title' => 'Technique Pro',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_3',
    'post_meta' => array(
        'tdcf_company_size' => 'MjU0',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MjAyMA==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'QW5kcmV3IFNtaXR0ZXI=',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_ecommerce_id, $tax_term_enterprise_software_id, $tax_term_software_development_id, $tax_term_web_development_id ),
        'tdtax_company_countries' => array( $tax_term_australia_id, $tax_term_brazil_id, $tax_term_canada_id, $tax_term_south_africa_id ),
        'tdtax_company_technologies' => array( $tax_term_css_id, $tax_term_html_id, $tax_term_javascript_id, $tax_term_php_id ),
        'tdtax_company_benefits' => array( $tax_term_company_meals_2_id, $tax_term_equity_benefits_2_id, $tax_term_flexible_working_hours_2_id, $tax_term_life_insurance_2_id ),
    ),
));

$cpt_nexera_id = td_demo_content::add_cpt( array(
    'title' => 'Nexera',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_4',
    'post_meta' => array(
        'tdcf_company_size' => 'MTI1',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MjAxMQ==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'T3NjYXIgUm9i',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_ecommerce_id, $tax_term_enterprise_software_id, $tax_term_software_development_id, $tax_term_web_development_id ),
        'tdtax_company_countries' => array( $tax_term_australia_id, $tax_term_brazil_id, $tax_term_canada_id, $tax_term_south_africa_id ),
        'tdtax_company_technologies' => array( $tax_term_css_id, $tax_term_html_id, $tax_term_javascript_id, $tax_term_php_id ),
        'tdtax_company_benefits' => array( $tax_term_company_meals_2_id, $tax_term_equity_benefits_2_id, $tax_term_flexible_working_hours_2_id, $tax_term_life_insurance_2_id ),
    ),
));

$cpt_codesea_id = td_demo_content::add_cpt( array(
    'title' => 'Codesea',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_5',
    'post_meta' => array(
        'tdcf_company_size' => 'MTI1MA==',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MjAwNg==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'TWFyayBTbWl0aA==',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_ecommerce_id, $tax_term_enterprise_software_id, $tax_term_software_development_id, $tax_term_web_development_id ),
        'tdtax_company_countries' => array( $tax_term_australia_id, $tax_term_brazil_id, $tax_term_canada_id, $tax_term_south_africa_id ),
        'tdtax_company_technologies' => array( $tax_term_c_id, $tax_term_figma_id, $tax_term_java_id, $tax_term_vue_js_id ),
        'tdtax_company_benefits' => array( $tax_term_company_meals_2_id, $tax_term_equity_benefits_2_id, $tax_term_flexible_working_hours_2_id, $tax_term_life_insurance_2_id ),
    ),
));

$cpt_neopixel_id = td_demo_content::add_cpt( array(
    'title' => 'NeoPixel',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_6',
    'post_meta' => array(
        'tdcf_company_size' => 'MjAwMA==',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MTk5OA==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'SmFuZSBEb2U=',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_ecommerce_id, $tax_term_enterprise_software_id, $tax_term_software_development_id, $tax_term_web_development_id ),
        'tdtax_company_countries' => array( $tax_term_australia_id, $tax_term_brazil_id, $tax_term_canada_id, $tax_term_south_africa_id ),
        'tdtax_company_technologies' => array( $tax_term_c_id, $tax_term_figma_id, $tax_term_java_id, $tax_term_vue_js_id ),
        'tdtax_company_benefits' => array( $tax_term_company_meals_2_id, $tax_term_equity_benefits_2_id, $tax_term_flexible_working_hours_2_id, $tax_term_life_insurance_2_id ),
    ),
));

$cpt_invisible_technologies_id = td_demo_content::add_cpt( array(
    'title' => 'Invisible Technologies',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_7',
    'post_meta' => array(
        'tdcf_company_size' => 'MTAwMA==',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MjAwMg==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'Sm9obiBEb2U=',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_ecommerce_id, $tax_term_enterprise_software_id, $tax_term_software_development_id, $tax_term_web_development_id ),
        'tdtax_company_countries' => array( $tax_term_australia_id, $tax_term_brazil_id, $tax_term_canada_id, $tax_term_south_africa_id ),
        'tdtax_company_technologies' => array( $tax_term_c_id, $tax_term_figma_id, $tax_term_java_id, $tax_term_vue_js_id ),
        'tdtax_company_benefits' => array( $tax_term_company_meals_2_id, $tax_term_equity_benefits_2_id, $tax_term_flexible_working_hours_2_id, $tax_term_life_insurance_2_id ),
    ),
));

$cpt_darkware_development_id = td_demo_content::add_cpt( array(
    'title' => 'Darkware Development',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_8',
    'post_meta' => array(
        'tdcf_company_size' => 'MTI1',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MTk5OA==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'TWF5YcKgRG9taW5pYw==',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_fintech_id, $tax_term_marketplace_id, $tax_term_payments_id, $tax_term_saas_id ),
        'tdtax_company_countries' => array( $tax_term_germany_id, $tax_term_india_id, $tax_term_romania_id, $tax_term_spain_id ),
        'tdtax_company_technologies' => array( $tax_term_c_id, $tax_term_figma_id, $tax_term_java_id, $tax_term_vue_js_id ),
        'tdtax_company_benefits' => array( $tax_term_generous_vacation_2_id, $tax_term_healthcare_2_id, $tax_term_paid_parental_leave_2_id, $tax_term_paid_vacation_2_id ),
    ),
));

$cpt_novisys_id = td_demo_content::add_cpt( array(
    'title' => 'Novisys',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_9',
    'post_meta' => array(
        'tdcf_company_size' => 'MTI1MA==',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MjAwMg==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'TGVhaMKgRWxpc2FiZXRo',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_fintech_id, $tax_term_marketplace_id, $tax_term_payments_id, $tax_term_saas_id ),
        'tdtax_company_countries' => array( $tax_term_germany_id, $tax_term_india_id, $tax_term_romania_id, $tax_term_spain_id ),
        'tdtax_company_technologies' => array( $tax_term_css_id, $tax_term_html_id, $tax_term_javascript_id, $tax_term_php_id ),
        'tdtax_company_benefits' => array( $tax_term_generous_vacation_2_id, $tax_term_healthcare_2_id, $tax_term_paid_parental_leave_2_id, $tax_term_paid_vacation_2_id ),
    ),
));

$cpt_touch_sense_id = td_demo_content::add_cpt( array(
    'title' => 'Touch Sense',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_10',
    'post_meta' => array(
        'tdcf_company_size' => 'MjAwMA==',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MjAyMg==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'U3RldmllwqBUeWxhcg==',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_ecommerce_id, $tax_term_enterprise_software_id, $tax_term_software_development_id, $tax_term_web_development_id ),
        'tdtax_company_countries' => array( $tax_term_australia_id, $tax_term_brazil_id, $tax_term_canada_id, $tax_term_south_africa_id ),
        'tdtax_company_technologies' => array( $tax_term_css_id, $tax_term_html_id, $tax_term_javascript_id, $tax_term_php_id ),
        'tdtax_company_benefits' => array( $tax_term_generous_vacation_2_id, $tax_term_healthcare_2_id, $tax_term_paid_parental_leave_2_id, $tax_term_paid_vacation_2_id ),
    ),
));

$cpt_omnibus_software_id = td_demo_content::add_cpt( array(
    'title' => 'Omnibus Software',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_1',
    'post_meta' => array(
        'tdcf_company_size' => 'NTYw',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MTk4OQ==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'Um93bGV5wqBLZWl0aA==',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_fintech_id, $tax_term_marketplace_id, $tax_term_payments_id, $tax_term_saas_id ),
        'tdtax_company_countries' => array( $tax_term_germany_id, $tax_term_india_id, $tax_term_romania_id, $tax_term_spain_id ),
        'tdtax_company_technologies' => array( $tax_term_css_id, $tax_term_html_id, $tax_term_javascript_id, $tax_term_php_id ),
        'tdtax_company_benefits' => array( $tax_term_company_meals_2_id, $tax_term_equity_benefits_2_id, $tax_term_flexible_working_hours_2_id, $tax_term_life_insurance_2_id ),
    ),
));

$cpt_xyptom_id = td_demo_content::add_cpt( array(
    'title' => 'Xyptom',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_2',
    'post_meta' => array(
        'tdcf_company_size' => 'MTAwMA==',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MjAxOA==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'QWxleGluYcKgRWxsaWFuYQ==',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_fintech_id, $tax_term_marketplace_id, $tax_term_payments_id, $tax_term_saas_id ),
        'tdtax_company_countries' => array( $tax_term_germany_id, $tax_term_india_id, $tax_term_romania_id, $tax_term_spain_id ),
        'tdtax_company_technologies' => array( $tax_term_css_id, $tax_term_html_id, $tax_term_javascript_id, $tax_term_php_id ),
        'tdtax_company_benefits' => array( $tax_term_generous_vacation_2_id, $tax_term_healthcare_2_id, $tax_term_paid_parental_leave_2_id, $tax_term_paid_vacation_2_id ),
    ),
));

$cpt_zendash_apps_id = td_demo_content::add_cpt( array(
    'title' => 'Zendash Apps',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_3',
    'post_meta' => array(
        'tdcf_company_size' => 'MzAwMg==',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MjAxMQ==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'SmVycm9sZMKgUm9taWxseQ==',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_fintech_id, $tax_term_marketplace_id, $tax_term_payments_id, $tax_term_saas_id ),
        'tdtax_company_countries' => array( $tax_term_germany_id, $tax_term_india_id, $tax_term_romania_id, $tax_term_spain_id ),
        'tdtax_company_technologies' => array( $tax_term_c_id, $tax_term_figma_id, $tax_term_java_id, $tax_term_vue_js_id ),
        'tdtax_company_benefits' => array( $tax_term_generous_vacation_2_id, $tax_term_healthcare_2_id, $tax_term_paid_parental_leave_2_id, $tax_term_paid_vacation_2_id ),
    ),
));

$cpt_proxi_logic_id = td_demo_content::add_cpt( array(
    'title' => 'Proxi Logic',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_4',
    'post_meta' => array(
        'tdcf_company_size' => 'NTAw',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MjAyMQ==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'TGF1cmVuY2XCoFdlc3Rvbg==',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_fintech_id, $tax_term_marketplace_id, $tax_term_payments_id, $tax_term_saas_id ),
        'tdtax_company_countries' => array( $tax_term_germany_id, $tax_term_india_id, $tax_term_romania_id, $tax_term_spain_id ),
        'tdtax_company_technologies' => array( $tax_term_css_id, $tax_term_html_id, $tax_term_javascript_id, $tax_term_php_id ),
        'tdtax_company_benefits' => array( $tax_term_company_meals_2_id, $tax_term_equity_benefits_2_id, $tax_term_flexible_working_hours_2_id, $tax_term_life_insurance_2_id ),
    ),
));

$cpt_digital_verse_id = td_demo_content::add_cpt( array(
    'title' => 'Digital Verse',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_5',
    'post_meta' => array(
        'tdcf_company_size' => 'NTAw',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MjAwOA==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'U2FtYW50aGEgUm9iZXJ0c29u',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_fintech_id, $tax_term_marketplace_id, $tax_term_payments_id, $tax_term_saas_id ),
        'tdtax_company_countries' => array( $tax_term_australia_id, $tax_term_brazil_id, $tax_term_canada_id, $tax_term_south_africa_id ),
        'tdtax_company_technologies' => array( $tax_term_c_id, $tax_term_figma_id, $tax_term_java_id, $tax_term_vue_js_id ),
        'tdtax_company_benefits' => array( $tax_term_company_meals_2_id, $tax_term_equity_benefits_2_id, $tax_term_flexible_working_hours_2_id, $tax_term_life_insurance_2_id ),
    ),
));

$cpt_digivox_id = td_demo_content::add_cpt( array(
    'title' => 'Digivox',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_6',
    'post_meta' => array(
        'tdcf_company_size' => 'MjQ1',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MjAyMA==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'Q29ud2F5wqBDb2xieQ==',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_fintech_id, $tax_term_marketplace_id, $tax_term_payments_id, $tax_term_saas_id ),
        'tdtax_company_countries' => array( $tax_term_germany_id, $tax_term_india_id, $tax_term_romania_id, $tax_term_spain_id ),
        'tdtax_company_technologies' => array( $tax_term_css_id, $tax_term_html_id, $tax_term_javascript_id, $tax_term_php_id ),
        'tdtax_company_benefits' => array( $tax_term_company_meals_2_id, $tax_term_equity_benefits_2_id, $tax_term_flexible_working_hours_2_id, $tax_term_life_insurance_2_id ),
    ),
));

$cpt_interapp_id = td_demo_content::add_cpt( array(
    'title' => 'Interapp',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_7',
    'post_meta' => array(
        'tdcf_company_size' => 'MjU0',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MjAwNg==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'Q2F0aGxlZW7CoEthcnNvbg==',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_fintech_id, $tax_term_marketplace_id, $tax_term_payments_id, $tax_term_saas_id ),
        'tdtax_company_countries' => array( $tax_term_germany_id, $tax_term_india_id, $tax_term_romania_id, $tax_term_spain_id ),
        'tdtax_company_technologies' => array( $tax_term_c_id, $tax_term_figma_id, $tax_term_java_id, $tax_term_vue_js_id ),
        'tdtax_company_benefits' => array( $tax_term_generous_vacation_2_id, $tax_term_healthcare_2_id, $tax_term_paid_parental_leave_2_id, $tax_term_paid_vacation_2_id ),
    ),
));

$cpt_nextscope_id = td_demo_content::add_cpt( array(
    'title' => 'NextScope',
    'type' => 'tdcpt_companies',
    'file' => 'tdcpt_companies_default.txt',
    'cpt_image_td_id' => 'td_pic_8',
    'post_meta' => array(
        'tdcf_company_size' => 'MjAwMA==',
        '_tdcf_company_size' => 'ZmllbGRfNjM5YzRlZjgxNjkxZQ==',
        'tdcf_company_founded' => 'MjAxNQ==',
        '_tdcf_company_founded' => 'ZmllbGRfNjM5YzRmMzYxNjkxZg==',
        'tdcf_company_ceo' => 'QnJhbmTCoEphbmVsZQ==',
        '_tdcf_company_ceo' => 'ZmllbGRfNjM5YzUyMzUyYTE5OQ==',
    ),
    'cpt_taxonomies' => array(
        'tdtax_company_markets' => array( $tax_term_fintech_id, $tax_term_marketplace_id, $tax_term_payments_id, $tax_term_saas_id ),
        'tdtax_company_countries' => array( $tax_term_germany_id, $tax_term_india_id, $tax_term_romania_id, $tax_term_spain_id ),
        'tdtax_company_technologies' => array( $tax_term_css_id, $tax_term_html_id, $tax_term_javascript_id, $tax_term_php_id ),
        'tdtax_company_benefits' => array( $tax_term_generous_vacation_2_id, $tax_term_healthcare_2_id, $tax_term_paid_parental_leave_2_id, $tax_term_paid_vacation_2_id ),
    ),
));


/*  ----------------------------------------------------------------------------
	LINKED POSTS/CPTs
*/
td_demo_content::link_posts( array(
    $cpt_machine_learning_engineer_id => array(
        'parent_post_id' => $cpt_omnibus_software_id,
    ),
    $cpt_software_engineer_frontend_2_id => array(
        'parent_post_id' => $cpt_nextscope_id,
    ),
    $cpt_channel_account_manager_id => array(
        'parent_post_id' => $cpt_digital_verse_id,
    ),
    $cpt_binance_identity_service_lead_3_id => array(
        'parent_post_id' => $cpt_touch_sense_id,
    ),
    $cpt_smart_contract_developer_2_id => array(
        'parent_post_id' => $cpt_novisys_id,
    ),
    $cpt_enterprise_support_engineer_2_id => array(
        'parent_post_id' => $cpt_zendash_apps_id,
    ),
    $cpt_it_compliance_lead_2_id => array(
        'parent_post_id' => $cpt_nexera_id,
    ),
    $cpt_machine_learning_engineer_3_id => array(
        'parent_post_id' => $cpt_omnibus_software_id,
    ),
    $cpt_partner_growth_manager_2_id => array(
        'parent_post_id' => $cpt_fusion_technologies_id,
    ),
    $cpt_android_engineer_payments_id => array(
        'parent_post_id' => $cpt_novisys_id,
    ),
    $cpt_junior_conflict_risk_analyst_3_id => array(
        'parent_post_id' => $cpt_interapp_id,
    ),
    $cpt_channel_account_manager_2_id => array(
        'parent_post_id' => $cpt_digital_verse_id,
    ),
    $cpt_founding_fullstack_engineer_2_id => array(
        'parent_post_id' => $cpt_inovate_id,
    ),
    $cpt_binance_identity_service_lead_2_id => array(
        'parent_post_id' => $cpt_touch_sense_id,
    ),
    $cpt_machine_learning_engineer_2_id => array(
        'parent_post_id' => $cpt_omnibus_software_id,
    ),
    $cpt_lead_product_designer_2_id => array(
        'parent_post_id' => $cpt_xyptom_id,
    ),
    $cpt_strategic_finance_analyst_id => array(
        'parent_post_id' => $cpt_interlock_id,
    ),
    $cpt_binance_identity_service_lead_id => array(
        'parent_post_id' => $cpt_touch_sense_id,
    ),
    $cpt_talent_strategy_manager_id => array(
        'parent_post_id' => $cpt_digivox_id,
    ),
    $cpt_founding_fullstack_engineer_id => array(
        'parent_post_id' => $cpt_inovate_id,
    ),
    $cpt_senior_staff_data_scientist_2_id => array(
        'parent_post_id' => $cpt_proxi_logic_id,
    ),
    $cpt_azure_cloud_architect_id => array(
        'parent_post_id' => $cpt_digivox_id,
    ),
    $cpt_senior_product_manager_id => array(
        'parent_post_id' => $cpt_interlock_id,
    ),
    $cpt_enterprise_support_engineer_id => array(
        'parent_post_id' => $cpt_zendash_apps_id,
    ),
    $cpt_partnerships_manager_id => array(
        'parent_post_id' => $cpt_nextscope_id,
    ),
    $cpt_senior_product_manager_2_id => array(
        'parent_post_id' => $cpt_interlock_id,
    ),
    $cpt_full_stack_web_engineer_2_id => array(
        'parent_post_id' => $cpt_technique_pro_id,
    ),
    $cpt_growth_marketing_manager_id => array(
        'parent_post_id' => $cpt_darkware_development_id,
    ),
    $cpt_senior_marketing_manager_2_id => array(
        'parent_post_id' => $cpt_codesea_id,
    ),
    $cpt_talent_strategy_manager_2_id => array(
        'parent_post_id' => $cpt_digivox_id,
    ),
    $cpt_developer_advocate_id => array(
        'parent_post_id' => $cpt_zendash_apps_id,
    ),
    $cpt_cloud_security_engineer_id => array(
        'parent_post_id' => $cpt_ui_logic_id,
    ),
    $cpt_global_travel_manager_id => array(
        'parent_post_id' => $cpt_technique_pro_id,
    ),
    $cpt_partner_growth_manager_id => array(
        'parent_post_id' => $cpt_fusion_technologies_id,
    ),
    $cpt_resume_specialist_part_time_2_id => array(
        'parent_post_id' => $cpt_neopixel_id,
    ),
    $cpt_director_of_engineering_id => array(
        'parent_post_id' => $cpt_fusion_technologies_id,
    ),
    $cpt_junior_conflict_risk_analyst_id => array(
        'parent_post_id' => $cpt_interapp_id,
    ),
    $cpt_smart_contract_developer_id => array(
        'parent_post_id' => $cpt_novisys_id,
    ),
    $cpt_growth_marketing_manager_2_id => array(
        'parent_post_id' => $cpt_darkware_development_id,
    ),
    $cpt_junior_conflict_risk_analyst_2_id => array(
        'parent_post_id' => $cpt_interapp_id,
    ),
    $cpt_senior_software_engineer_id => array(
        'parent_post_id' => $cpt_neopixel_id,
    ),
    $cpt_lead_product_designer_id => array(
        'parent_post_id' => $cpt_xyptom_id,
    ),
    $cpt_senior_marketing_manager_id => array(
        'parent_post_id' => $cpt_codesea_id,
    ),
    $cpt_technical_program_manager_id => array(
        'parent_post_id' => $cpt_xyptom_id,
    ),
    $cpt_business_development_officer_id => array(
        'parent_post_id' => $cpt_darkware_development_id,
    ),
    $cpt_full_stack_web_engineer_id => array(
        'parent_post_id' => $cpt_technique_pro_id,
    ),
    $cpt_it_compliance_lead_id => array(
        'parent_post_id' => $cpt_nexera_id,
    ),
    $cpt_technical_support_engineer_id => array(
        'parent_post_id' => $cpt_nexera_id,
    ),
    $cpt_senior_backend_developer_id => array(
        'parent_post_id' => $cpt_invisible_technologies_id,
    ),
    $cpt_cloud_security_engineer_2_id => array(
        'parent_post_id' => $cpt_ui_logic_id,
    ),
    $cpt_staff_product_researcher_id => array(
        'parent_post_id' => $cpt_proxi_logic_id,
    ),
    $cpt_customer_education_specialist_id => array(
        'parent_post_id' => $cpt_inovate_id,
    ),
    $cpt_software_engineer_frontend_id => array(
        'parent_post_id' => $cpt_nextscope_id,
    ),
    $cpt_strategic_finance_lead_id => array(
        'parent_post_id' => $cpt_invisible_technologies_id,
    ),
    $cpt_mid_market_account_executive_id => array(
        'parent_post_id' => $cpt_ui_logic_id,
    ),
    $cpt_resume_specialist_part_time_id => array(
        'parent_post_id' => $cpt_neopixel_id,
    ),
    $cpt_junior_software_engineer_id => array(
        'parent_post_id' => $cpt_codesea_id,
    ),
    $cpt_staff_software_engineer_id => array(
        'parent_post_id' => $cpt_digital_verse_id,
    ),
    $cpt_senior_staff_data_scientist_id => array(
        'parent_post_id' => $cpt_proxi_logic_id,
    ),
    $cpt_fusion_technologies_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_partner_growth_manager_id,
                $cpt_director_of_engineering_id,
                $cpt_partner_growth_manager_2_id,
            ),
        )
    ),
    $cpt_interlock_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_senior_product_manager_id,
                $cpt_strategic_finance_analyst_id,
                $cpt_senior_product_manager_2_id,
            ),
        )
    ),
    $cpt_ui_logic_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_cloud_security_engineer_id,
                $cpt_mid_market_account_executive_id,
                $cpt_cloud_security_engineer_2_id,
            ),
        )
    ),
    $cpt_inovate_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_founding_fullstack_engineer_id,
                $cpt_customer_education_specialist_id,
                $cpt_founding_fullstack_engineer_2_id,
            ),
        )
    ),
    $cpt_technique_pro_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_full_stack_web_engineer_id,
                $cpt_global_travel_manager_id,
                $cpt_full_stack_web_engineer_2_id,
            ),
        )
    ),
    $cpt_nexera_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_technical_support_engineer_id,
                $cpt_it_compliance_lead_id,
                $cpt_it_compliance_lead_2_id,
            ),
        )
    ),
    $cpt_codesea_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_junior_software_engineer_id,
                $cpt_senior_marketing_manager_id,
                $cpt_senior_marketing_manager_2_id,
            ),
        )
    ),
    $cpt_neopixel_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_senior_software_engineer_id,
                $cpt_resume_specialist_part_time_id,
                $cpt_resume_specialist_part_time_2_id,
            ),
        )
    ),
    $cpt_invisible_technologies_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_senior_backend_developer_id,
                $cpt_strategic_finance_lead_id,
            ),
        )
    ),
    $cpt_darkware_development_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_growth_marketing_manager_id,
                $cpt_business_development_officer_id,
                $cpt_growth_marketing_manager_2_id,
            ),
        )
    ),
    $cpt_novisys_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_smart_contract_developer_id,
                $cpt_android_engineer_payments_id,
                $cpt_smart_contract_developer_2_id,
            ),
        )
    ),
    $cpt_touch_sense_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_binance_identity_service_lead_id,
                $cpt_binance_identity_service_lead_2_id,
                $cpt_binance_identity_service_lead_3_id,
            ),
        )
    ),
    $cpt_omnibus_software_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_machine_learning_engineer_id,
                $cpt_machine_learning_engineer_2_id,
                $cpt_machine_learning_engineer_3_id,
            ),
        )
    ),
    $cpt_xyptom_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_lead_product_designer_id,
                $cpt_technical_program_manager_id,
                $cpt_lead_product_designer_2_id,
            ),
        )
    ),
    $cpt_zendash_apps_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_enterprise_support_engineer_id,
                $cpt_developer_advocate_id,
                $cpt_enterprise_support_engineer_2_id,
            ),
        )
    ),
    $cpt_proxi_logic_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_senior_staff_data_scientist_id,
                $cpt_staff_product_researcher_id,
                $cpt_senior_staff_data_scientist_2_id,
            ),
        )
    ),
    $cpt_digital_verse_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_channel_account_manager_id,
                $cpt_staff_software_engineer_id,
                $cpt_channel_account_manager_2_id,
            ),
        )
    ),
    $cpt_digivox_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_talent_strategy_manager_id,
                $cpt_azure_cloud_architect_id,
                $cpt_talent_strategy_manager_2_id,
            ),
        )
    ),
    $cpt_interapp_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_junior_conflict_risk_analyst_id,
                $cpt_junior_conflict_risk_analyst_2_id,
                $cpt_junior_conflict_risk_analyst_3_id,
            ),
        )
    ),
    $cpt_nextscope_id => array(
        'linked_posts' => array(
            'tdcpt_jobs' => array(
                $cpt_software_engineer_frontend_id,
                $cpt_partnerships_manager_id,
                $cpt_software_engineer_frontend_2_id,
            ),
        )
    ),
));



/*  ----------------------------------------------------------------------------
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_link( array(
    'title' => 'Advice',
    'add_to_menu_id' => $menu_td_demo_custom_menu_id,
    'url' => '#',
    'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_link( array(
    'title' => 'Rewards',
    'add_to_menu_id' => $menu_td_demo_custom_menu_id,
    'url' => '#',
    'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_link( array(
    'title' => 'Pricing',
    'add_to_menu_id' => $menu_td_demo_custom_menu_id,
    'url' => '#',
    'parent_id' => ''
));


$menu_item_0_id = td_demo_menus::add_link( array(
    'title' => 'FAQ',
    'add_to_menu_id' => $menu_td_demo_footer_menu_id,
    'url' => '#',
    'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_link( array(
    'title' => 'About',
    'add_to_menu_id' => $menu_td_demo_footer_menu_id,
    'url' => '#',
    'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_link( array(
    'title' => 'Contact us',
    'add_to_menu_id' => $menu_td_demo_footer_menu_id,
    'url' => '#',
    'parent_id' => ''
));


$menu_item_0_id = td_demo_menus::add_link( array(
    'title' => 'Terms & conditions',
    'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
    'url' => '#',
    'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_link( array(
    'title' => 'Privacy policy',
    'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
    'url' => '#',
    'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_link( array(
    'title' => 'Partners',
    'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
    'url' => '#',
    'parent_id' => ''
));


$menu_item_0_id = td_demo_menus::add_page(array(
    'title' => 'Jobs',
    'add_to_menu_id' => $menu_td_demo_header_menu_id,
    'page_id' => $page_jobs_id,
    'parent_id' => '',
    'mega_menu_page_id' => $page_menu_page_jobs_id
));

$menu_item_1_id = td_demo_menus::add_page(array(
    'title' => 'Companies',
    'add_to_menu_id' => $menu_td_demo_header_menu_id,
    'page_id' => $page_companies_id,
    'parent_id' => '',
    'mega_menu_page_id' => $page_menu_page_companies_id
));

$menu_item_2_id = td_demo_menus::add_page(array(
    'title' => 'Pricing',
    'add_to_menu_id' => $menu_td_demo_header_menu_id,
    'page_id' => $page_pricing_id,
    'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_link( array(
    'title' => 'Tips',
    'add_to_menu_id' => $menu_td_demo_header_menu_id,
    'url' => '#',
    'parent_id' => ''
));


$menu_item_0_id = td_demo_menus::add_link( array(
    'title' => 'JobHunt academy',
    'add_to_menu_id' => $menu_td_demo_sidebar_menu_id,
    'url' => '#',
    'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_link( array(
    'title' => 'Get paid',
    'add_to_menu_id' => $menu_td_demo_sidebar_menu_id,
    'url' => '#',
    'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_link( array(
    'title' => 'Community & forums',
    'add_to_menu_id' => $menu_td_demo_sidebar_menu_id,
    'url' => '#',
    'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_link( array(
    'title' => 'Help center',
    'add_to_menu_id' => $menu_td_demo_sidebar_menu_id,
    'url' => '#',
    'parent_id' => ''
));


/*  ----------------------------------------------------------------------------
	CLOUD TEMPLATES
*/
$template_author_template_id = td_demo_content::add_cloud_template( array(
    'title' => 'Author Template - Job Hunt PRO',
    'file' => 'author_cloud_template.txt',
    'template_type' => 'author',
));
td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_id);


$template_custom_post_type_template_jobs_id = td_demo_content::add_cloud_template( array(
    'title' => 'Custom Post Type Template - Jobs - Job Hunt PRO',
    'file' => 'cpt_cloud_template_jobs.txt',
    'template_type' => 'cpt',
));
td_demo_misc::update_global_cpt_template( 'tdb_template_' . $template_custom_post_type_template_jobs_id, 'tdcpt_jobs' );


$template_custom_post_type_template_companies_id = td_demo_content::add_cloud_template( array(
    'title' => 'Custom Post Type Template - Companies - Job Hunt PRO',
    'file' => 'cpt_cloud_template_companies.txt',
    'template_type' => 'cpt',
));
td_demo_misc::update_global_cpt_template( 'tdb_template_' . $template_custom_post_type_template_companies_id, 'tdcpt_companies' );


$template_custom_taxonomy_template_company_benefits_id = td_demo_content::add_cloud_template( array(
    'title' => 'Custom Taxonomy Template - Company Benefits - Job Hunt PRO',
    'file' => 'custom_taxonomy_template_company_benefits_cloud_template.txt',
    'template_type' => 'cpt_tax',
));
td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_company_benefits_id, 'tdtax_company_benefits' );


$template_custom_taxonomy_template_company_technologies_id = td_demo_content::add_cloud_template( array(
    'title' => 'Custom Taxonomy Template - Company Technologies - Job Hunt PRO',
    'file' => 'custom_taxonomy_template_company_technologies_cloud_template.txt',
    'template_type' => 'cpt_tax',
));
td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_company_technologies_id, 'tdtax_company_technologies' );


$template_custom_taxonomy_template_company_countries_id = td_demo_content::add_cloud_template( array(
    'title' => 'Custom Taxonomy Template - Company Countries - Job Hunt PRO',
    'file' => 'custom_taxonomy_template_company_countries_cloud_template.txt',
    'template_type' => 'cpt_tax',
));
td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_company_countries_id, 'tdtax_company_countries' );


$template_custom_taxonomy_template_company_markets_id = td_demo_content::add_cloud_template( array(
    'title' => 'Custom Taxonomy Template - Company Markets - Job Hunt PRO',
    'file' => 'custom_taxonomy_template_company_markets_cloud_template.txt',
    'template_type' => 'cpt_tax',
));
td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_company_markets_id, 'tdtax_company_markets' );


$template_custom_taxonomy_template_job_categories_id = td_demo_content::add_cloud_template( array(
    'title' => 'Custom Taxonomy Template - Job Categories - Job Hunt PRO',
    'file' => 'custom_taxonomy_template_job_categories_cloud_template.txt',
    'template_type' => 'cpt_tax',
));
td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_job_categories_id, 'tdtax_job_categories' );


$template_custom_taxonomy_template_job_benefits_id = td_demo_content::add_cloud_template( array(
    'title' => 'Custom Taxonomy Template - Job Benefits - Job Hunt PRO',
    'file' => 'custom_taxonomy_template_job_benefits_cloud_template.txt',
    'template_type' => 'cpt_tax',
));
td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_job_benefits_id, 'tdtax_job_benefits' );


$template_custom_taxonomy_template_job_skills_id = td_demo_content::add_cloud_template( array(
    'title' => 'Custom Taxonomy Template - Job Skills - Job Hunt PRO',
    'file' => 'custom_taxonomy_template_job_skills_cloud_template.txt',
    'template_type' => 'cpt_tax',
));
td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_job_skills_id, 'tdtax_job_skills' );


$template_custom_taxonomy_template_job_salaries_id = td_demo_content::add_cloud_template( array(
    'title' => 'Custom Taxonomy Template - Job Salaries - Job Hunt PRO',
    'file' => 'custom_taxonomy_template_job_salaries_cloud_template.txt',
    'template_type' => 'cpt_tax',
));
td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_job_salaries_id, 'tdtax_job_salaries' );


$template_custom_taxonomy_template_job_type_id = td_demo_content::add_cloud_template( array(
    'title' => 'Custom Taxonomy Template - Job Type - Job Hunt PRO',
    'file' => 'custom_taxonomy_template_job_type_cloud_template.txt',
    'template_type' => 'cpt_tax',
));
td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_job_type_id, 'tdtax_job_work_types' );


$template_footer_template_job_hunt_pro_id = td_demo_content::add_cloud_template( array(
    'title' => 'Footer Template - Job Hunt PRO',
    'file' => 'footer_cloud_template.txt',
    'template_type' => 'footer',
));
td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_template_job_hunt_pro_id);


$template_header_template_job_hunt_pro_id = td_demo_content::add_cloud_template( array(
    'title' => 'Header Template - Job Hunt PRO',
    'file' => 'header_cloud_template.txt',
    'template_type' => 'header',
));
td_demo_misc::update_global_header_template( 'tdb_template_' . $template_header_template_job_hunt_pro_id);



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
