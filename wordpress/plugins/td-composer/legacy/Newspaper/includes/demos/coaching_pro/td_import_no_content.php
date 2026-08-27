<?php



/*  ----------------------------------------------------------------------------
	EXTERNAL PLUGINS DATA IMPORT
*/
/* -- ACF -- */
// Field groups
$field_group_course_fields = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/coaching_pro/data/acf/group_646b6bdd7cc7f.json' );

// Post types
$post_type_courses = td_demo_data::acf_import_post_type( 'https://cloud.tagdiv.com/demos/Newspaper/coaching_pro/data/acf/post_type_646b67dd79321.json' );

// Taxonomies
$taxonomy_domains = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/coaching_pro/data/acf/taxonomy_646b686c902bd.json' );
$taxonomy_prices = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/coaching_pro/data/acf/taxonomy_646f1505c1f36.json' );



$menu_td_demo_header_menu_id = td_demo_menus::create_menu('td-demo-header-menu', '');
$menu_td_demo_custom_menu_id = td_demo_menus::create_menu('td-demo-custom-menu', '');
$menu_td_demo_top_menu_id = td_demo_menus::create_menu('td-demo-top-menu', '');

/*  ----------------------------------------------------------------------------
	 CLOUD TEMPLATES(MODULES)
*/
$template_module_template_2_coaching_pro_id = td_demo_content::add_cloud_template( array(
    'title' => 'Module Template 2 - Coaching PRO',
    'file' => 'module_template_2_coaching_pro_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '741',
));

$template_module_template_1_coaching_pro_id = td_demo_content::add_cloud_template( array(
    'title' => 'Module Template 1 - Coaching PRO',
    'file' => 'module_template_1_coaching_pro_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '434',
));


/*  ----------------------------------------------------------------------------
	PAGES
*/
$page_megamenu_courses_id = td_demo_content::add_page( array(
    'title' => 'Megamenu Courses',
    'file' => 'megamenu_courses.txt',
    'demo_unique_id' => '2864be215966afd',
));

$page_mobile_menu_modal_id = td_demo_content::add_page( array(
    'title' => 'Mobile Menu Modal',
    'file' => 'mobile_menu_modal.txt',
    'demo_unique_id' => '364be215966f49',
));

$page_courses_id = td_demo_content::add_page( array(
    'title' => 'Courses',
    'file' => 'courses.txt',
    'demo_unique_id' => '5164be215967472',
));

$page_contact_id = td_demo_content::add_page( array(
    'title' => 'Contact',
    'file' => 'contact.txt',
    'demo_unique_id' => '9964be2159678e9',
));

$page_about_id = td_demo_content::add_page( array(
    'title' => 'About',
    'file' => 'about.txt',
    'demo_unique_id' => '7764be215967e82',
));

$page_home_id = td_demo_content::add_page( array(
    'title' => 'Home',
    'file' => 'home.txt',
    'homepage' => true,
    'demo_unique_id' => '5664be215968656',
));

$page_pricing_id = td_demo_content::add_page( array(
    'title' => 'Pricing',
    'file' => 'pricing.txt',
    'demo_unique_id' => '6764be215968b0d',
));


/*  ----------------------------------------------------------------------------
	CLOUD TEMPLATES
*/
$template_blank_custom_post_type_id = td_demo_content::add_cloud_template( array(
    'title' => 'Blank - Custom Post Type',
    'file' => 'cpt_cloud_template.txt',
    'template_type' => 'cpt',
));

$template_404_template_coaching_pro_id = td_demo_content::add_cloud_template( array(
    'title' => '404 Template - Coaching PRO',
    'file' => '404_cloud_template.txt',
    'template_type' => '404',
));

td_demo_misc::update_global_404_template( 'tdb_template_' . $template_404_template_coaching_pro_id );


$template_tag_template_coaching_pro_id = td_demo_content::add_cloud_template( array(
    'title' => 'Tag Template -  Coaching PRO',
    'file' => 'tag_cloud_template.txt',
    'template_type' => 'tag',
));

td_demo_misc::update_global_tag_template( 'tdb_template_' . $template_tag_template_coaching_pro_id );


$template_search_template_coaching_pro_id = td_demo_content::add_cloud_template( array(
    'title' => 'Search Template - Coaching PRO',
    'file' => 'search_cloud_template.txt',
    'template_type' => 'search',
));

td_demo_misc::update_global_search_template( 'tdb_template_' . $template_search_template_coaching_pro_id );


$template_date_template_coaching_pro_id = td_demo_content::add_cloud_template( array(
    'title' => 'Date Template - Coaching PRO',
    'file' => 'date_cloud_template.txt',
    'template_type' => 'date',
));

td_demo_misc::update_global_date_template( 'tdb_template_' . $template_date_template_coaching_pro_id );


$template_author_template_coaching_pro_id = td_demo_content::add_cloud_template( array(
    'title' => 'Author Template - Coaching PRO',
    'file' => 'author_cloud_template.txt',
    'template_type' => 'author',
));

td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_coaching_pro_id );


$template_category_template_coaching_pro_demo_id = td_demo_content::add_cloud_template( array(
    'title' => 'Category Template - Coaching PRO Demo',
    'file' => 'cat_cloud_template.txt',
    'template_type' => 'category',
));

td_demo_misc::update_global_category_template( 'tdb_template_' . $template_category_template_coaching_pro_demo_id );


$template_single_post_template_coaching_pro_id = td_demo_content::add_cloud_template( array(
    'title' => 'Single Post Template - Coaching PRO',
    'file' => 'post_cloud_template.txt',
    'template_type' => 'single',
));

td_util::update_option( 'td_default_site_post_template', 'tdb_template_' . $template_single_post_template_coaching_pro_id );


$template_custom_taxonomy_template_coaching_pro_id = td_demo_content::add_cloud_template( array(
    'title' => 'Custom Taxonomy Template - Coaching PRO',
    'file' => 'custom_taxonomy_template_coaching_pro_cloud_template.txt',
    'template_type' => 'cpt_tax',
));

td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_coaching_pro_id, 'tdtax_domains' );


td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_coaching_pro_id, 'tdtax_price' );


$template_custom_post_type_cpt_coaching_pro_id = td_demo_content::add_cloud_template( array(
    'title' => 'Custom Post Type CPT - Coaching PRO',
    'file' => 'cpt_cloud_template.txt',
    'template_type' => 'cpt',
));

td_demo_misc::update_global_cpt_template( 'tdb_template_' . $template_custom_post_type_cpt_coaching_pro_id, 'tdcpt_courses' );


$template_header_template_main_coaching_pro_id = td_demo_content::add_cloud_template( array(
    'title' => 'Header Template Main - Coaching PRO',
    'file' => 'header_template_main_coaching_pro_cloud_template.txt',
    'template_type' => 'header',
));

td_demo_misc::update_global_header_template( 'tdb_template_' . $template_header_template_main_coaching_pro_id );


$template_footer_template_coaching_pro_id = td_demo_content::add_cloud_template( array(
    'title' => 'Footer Template - Coaching PRO',
    'file' => 'footer_template_coaching_pro_cloud_template.txt',
    'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_template_coaching_pro_id );


$template_header_template_coaching_pro_id = td_demo_content::add_cloud_template( array(
    'title' => 'Header Template - Coaching PRO',
    'file' => 'header_template_coaching_pro_cloud_template.txt',
    'template_type' => 'header',
));


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
td_demo_content::update_meta( $template_header_template_main_coaching_pro_id, 'tdc_header_template_id', $template_header_template_main_coaching_pro_id );

td_demo_content::update_meta( $template_footer_template_coaching_pro_id, 'tdc_footer_template_id', $template_footer_template_coaching_pro_id );

td_demo_content::update_meta( $template_header_template_coaching_pro_id, 'tdc_header_template_id', $template_header_template_coaching_pro_id );

// pages metas
td_demo_content::update_meta( $page_megamenu_courses_id, 'tdc_header_template_id', 'no_header' );

td_demo_content::update_meta( $page_megamenu_courses_id, 'tdc_footer_template_id', 'no_footer' );

td_demo_content::update_meta( $page_mobile_menu_modal_id, 'tdc_header_template_id', 'no_header' );

td_demo_content::update_meta( $page_mobile_menu_modal_id, 'tdc_footer_template_id', 'no_footer' );

td_demo_content::update_meta( $page_home_id, 'tdc_header_template_id', $template_header_template_coaching_pro_id );

td_demo_content::update_meta( $page_home_id, 'tdc_footer_template_id', $template_footer_template_coaching_pro_id );