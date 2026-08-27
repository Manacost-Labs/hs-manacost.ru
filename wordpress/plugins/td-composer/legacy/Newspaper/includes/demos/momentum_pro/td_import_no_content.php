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
	MENUS
*/
$menu_td_demo_custom_menu_id = td_demo_menus::create_menu('td-demo-custom-menu', '');
$menu_td_demo_footer_menu_id = td_demo_menus::create_menu('td-demo-footer-menu', '');
$menu_td_demo_header_menu_id = td_demo_menus::create_menu('td-demo-header-menu', '');
$menu_td_demo_top_menu_id = td_demo_menus::create_menu('td-demo-top-menu', '');



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
	PAGES
*/
$page_mobile_modal_id = td_demo_content::add_page( array(
    'title' => 'Mobile Modal',
    'file' => 'mobile_modal.txt',
    'demo_unique_id' => '28642c176809931',
));

$page_megamenu_services_id = td_demo_content::add_page( array(
    'title' => 'Megamenu - Services',
    'file' => 'megamenu_services.txt',
    'demo_unique_id' => '32642c17680a1ca',
));

$page_about_page_momentum_pro_id = td_demo_content::add_page( array(
    'title' => 'About Page - Momentum PRO',
    'file' => 'about_page_momentum_pro.txt',
    'demo_unique_id' => '93642c17680ba33',
));

$page_contact_id = td_demo_content::add_page( array(
    'title' => 'Contact Page - Momentum PRO',
    'file' => 'contact.txt',
    'demo_unique_id' => '42642c17680c493',
));

$page_case_studies_id = td_demo_content::add_page( array(
    'title' => 'Case Studies',
    'file' => 'case_studies.txt',
    'demo_unique_id' => '42642c17680cf14',
));

$page_tdtax_services_id = td_demo_content::add_page( array(
    'title' => 'Our Services',
    'file' => 'tdtax_services.txt',
    'demo_unique_id' => '18642c17680e4b8',
));

$page_home_id = td_demo_content::add_page( array(
    'title' => 'Home',
    'file' => 'home.txt',
    'homepage' => true,
    'demo_unique_id' => '5642c17680fe8f',
));



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
	SUBSCRIPTION - start phase 2
*/

/*  ----------------------------------------------------------------------------
	SUBSCRIPTIONS
*/
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


/*  ----------------------------------------------------------------------------
	SUBSCRIPTION - end phase 2
*/


/*  ----------------------------------------------------------------------------
	CLOUD TEMPLATES
*/

$template_header_template_momentum_pro_id = td_demo_content::add_cloud_template( array(
    'title' => 'Header Template - Momentum PRO',
    'file' => 'header_cloud_template.txt',
    'template_type' => 'header',
));

td_demo_misc::update_global_header_template( 'tdb_template_' . $template_header_template_momentum_pro_id);


$template_footer_template_momentum_pro_id = td_demo_content::add_cloud_template( array(
    'title' => 'Footer Template - Momentum PRO',
    'file' => 'footer_cloud_template.txt',
    'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_template_momentum_pro_id);


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
    'file' => 'cpt_tax_cloud_template.txt',
    'template_type' => 'cpt_tax',
));

td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_services_id, 'tdtax_services' );


$template_custom_post_type_case_studies_id = td_demo_content::add_cloud_template( array(
    'title' => 'Custom Post Type - Case Studies',
    'file' => 'cpt_cloud_template.txt',
    'template_type' => 'cpt',
));

td_demo_misc::update_global_cpt_template( 'tdb_template_' . $template_custom_post_type_case_studies_id, 'tdcpt_case_studies' );



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
