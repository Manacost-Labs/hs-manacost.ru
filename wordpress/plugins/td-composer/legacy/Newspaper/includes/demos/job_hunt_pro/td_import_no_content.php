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
        'months_in_cycle' => '',
        'trial_days' => '0',
        'is_free' => '1',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"98645dff9fba0b6";}',
        'publishing_limits' => 'a:1:{i:0;O:8:"stdClass":3:{s:9:"post_type";s:15:"tdcpt_companies";s:5:"limit";s:1:"1";s:5:"track";b:0;}}',
    )
);

$plan_basic_monthly_plan___companies___job_hunt_pro_id = td_demo_subscription::add_plan( array(
        'name' => 'Basic Monthly Plan - Companies - Job Hunt PRO',
        'price' => '25',
        'months_in_cycle' => '1',
        'trial_days' => '0',
        'is_free' => '0',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"11645dff9fba156";}',
        'publishing_limits' => 'a:2:{i:0;O:8:"stdClass":3:{s:9:"post_type";s:15:"tdcpt_companies";s:5:"limit";s:1:"4";s:5:"track";b:0;}i:1;O:8:"stdClass":3:{s:9:"post_type";s:10:"tdcpt_jobs";s:5:"limit";s:2:"15";s:5:"track";b:1;}}',
    )
);

$plan_unlimited_monthly_plan___companies___job_hunt_pro_id = td_demo_subscription::add_plan( array(
        'name' => 'Unlimited Monthly Plan - Companies - Job Hunt PRO',
        'price' => '100',
        'months_in_cycle' => '1',
        'trial_days' => '0',
        'is_free' => '0',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"32645dff9fba1d9";}',
        'publishing_limits' => 'a:2:{i:0;O:8:"stdClass":3:{s:9:"post_type";s:15:"tdcpt_companies";s:5:"limit";s:6:"100000";s:5:"track";b:1;}i:1;O:8:"stdClass":3:{s:9:"post_type";s:10:"tdcpt_jobs";s:5:"limit";s:6:"100000";s:5:"track";b:1;}}',
    )
);

$plan_free_plan___job_applicants___job_hunt_pro_id = td_demo_subscription::add_plan( array(
        'name' => 'Free Plan - Job Applicants - Job Hunt PRO',
        'price' => '',
        'months_in_cycle' => '',
        'trial_days' => '0',
        'is_free' => '1',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"98645dff9fba273";}',
        'publishing_limits' => 'a:1:{i:0;O:8:"stdClass":3:{s:9:"post_type";s:14:"tdcpt_job_apps";s:5:"limit";s:1:"5";s:5:"track";b:0;}}',
    )
);

$plan_beginner_plan___job_applicants___job_hunt_pro_id = td_demo_subscription::add_plan( array(
        'name' => 'Beginner Plan - Job Applicants - Job Hunt PRO',
        'price' => '10',
        'months_in_cycle' => '1',
        'trial_days' => '0',
        'is_free' => '0',
        'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"72645dff9fba4cd";}',
        'publishing_limits' => 'a:1:{i:0;O:8:"stdClass":3:{s:9:"post_type";s:14:"tdcpt_job_apps";s:5:"limit";s:2:"20";s:5:"track";b:1;}}',
    )
);

$plan_advanced_plan___job_applicants___job_hunt_pro_id = td_demo_subscription::add_plan( array(
        'name' => 'Advanced Plan - Job Applicants - Job Hunt PRO',
        'price' => '25',
        'months_in_cycle' => '1',
        'trial_days' => '0',
        'is_free' => '0',
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

$page_my_account_page_id_id = td_demo_content::add_page( array(
    'title' => 'My Account - Job Hunt PRO',
    'file' => 'my_account.txt',
    'template' => 'default',
));

td_demo_subscription::add_option( array(
        'name' => 'my_account_page_id',
        'value' => $page_my_account_page_id_id,
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


/*  ----------------------------------------------------------------------------
	CPTs
*/


/*  ----------------------------------------------------------------------------
	MENUS
*/


/*  ----------------------------------------------------------------------------
	CLOUD TEMPLATES
*/
$template_author_template_id = td_demo_content::add_cloud_template( array(
    'title' => 'Author Template - Job Hunt PRO',
    'file' => 'author_cloud_template.txt',
    'template_type' => 'author',
));
td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_id);


$template_custom_post_type_template_companies_id = td_demo_content::add_cloud_template( array(
    'title' => 'Custom Post Type Template - Companies - Job Hunt PRO',
    'file' => 'cpt_cloud_template.txt',
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


$template_custom_post_type_template_jobs_id = td_demo_content::add_cloud_template( array(
    'title' => 'Custom Post Type Template - Jobs - Job Hunt PRO',
    'file' => 'cpt_cloud_template.txt',
    'template_type' => 'cpt',
));
td_demo_misc::update_global_cpt_template( 'tdb_template_' . $template_custom_post_type_template_jobs_id, 'tdcpt_jobs' );


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
