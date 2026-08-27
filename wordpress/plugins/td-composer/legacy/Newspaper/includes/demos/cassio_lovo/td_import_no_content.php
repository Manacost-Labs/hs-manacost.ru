<?php


/*  ----------------------------------------------------------------------------
	EXTERNAL PLUGINS DATA IMPORT
*/
/* -- ACF -- */
// Field groups
$field_group_menu_group = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/cassio_lovo/data/acf/group_65eeae1d33aca.json' );

// Post types
$post_type_menu = td_demo_data::acf_import_post_type( 'https://cloud.tagdiv.com/demos/Newspaper/cassio_lovo/data/acf/post_type_660e8177515e9.json' );

// Taxonomies
$taxonomy_ingredients_list = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/cassio_lovo/data/acf/taxonomy_660e8177580d6.json' );
$taxonomy_menu_list = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/cassio_lovo/data/acf/taxonomy_660e81775a7d1.json' );



/*  ----------------------------------------------------------------------------
	 CLOUD TEMPLATES(MODULES)
*/
$template_module_template_simple_menu_id = td_demo_content::add_cloud_template( array(
    'title' => 'Menu Simple – Module Template',
    'file' => 'module_template_simple_menu_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '841',
));

$template_module_template_menu_id = td_demo_content::add_cloud_template( array(
    'title' => 'Menu Icons – Module Template',
    'file' => 'module_template_menu_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '714',
));


/*  ----------------------------------------------------------------------------
	ATTACHMENTS
*/

/*  ----------------------------------------------------------------------------
	PAGES
*/
$page_reserve_a_table_form_id = td_demo_content::add_page( array(
    'title' => 'Reserve a Table Form',
    'file' => 'reserve_a_table_form.txt',
    'demo_unique_id' => '3660ea6220d7e3',
));

$page_contact_methods_id = td_demo_content::add_page( array(
    'title' => 'Contact Methods',
    'file' => 'contact_methods.txt',
    'demo_unique_id' => '99660ea6220db63',
));

$page_contact_form_id = td_demo_content::add_page( array(
    'title' => 'Contact Form',
    'file' => 'contact_form.txt',
    'demo_unique_id' => '70660ea6220deb8',
));

$page_modal_menu_id = td_demo_content::add_page( array(
    'title' => 'Modal Menu',
    'file' => 'modal_menu.txt',
    'demo_unique_id' => '82660ea6220fa6c',
));

$page_modal_search_id = td_demo_content::add_page( array(
    'title' => 'Modal Search',
    'file' => 'modal_search.txt',
    'demo_unique_id' => '0660ea6220fdcb',
));

$page_simple_menu_id = td_demo_content::add_page( array(
    'title' => 'Simple Menu',
    'file' => 'simple_menu.txt',
    'demo_unique_id' => '35660ea6220e22f',
));

$page_about_id = td_demo_content::add_page( array(
    'title' => 'About',
    'file' => 'about.txt',
    'demo_unique_id' => '5660ea6220e656',
));

$page_reserve_id = td_demo_content::add_page( array(
    'title' => 'Reserve',
    'file' => 'reserve.txt',
    'demo_unique_id' => '77660ea6220e9de',
));

$page_menu_id = td_demo_content::add_page( array(
    'title' => 'Menu',
    'file' => 'menu.txt',
    'demo_unique_id' => '48660ea6220ee3d',
));

$page_contact_id = td_demo_content::add_page( array(
    'title' => 'Contact',
    'file' => 'contact.txt',
    'demo_unique_id' => '22660ea6220f261',
));

$page_homepage_id = td_demo_content::add_page( array(
    'title' => 'Homepage',
    'file' => 'homepage.txt',
    'homepage' => true,
    'demo_unique_id' => '68660ea6220f70c',
));


/*  ----------------------------------------------------------------------------
	CLOUD TEMPLATES
*/
$template_tag_template_cl_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cassio Lovo - Tag Template',
    'file' => 'tag_cloud_template.txt',
    'template_type' => 'tag',
));

$template_search_template_cl_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cassio Lovo - Search Template',
    'file' => 'search_cloud_template.txt',
    'template_type' => 'search',
));

td_demo_misc::update_global_search_template( 'tdb_template_' . $template_search_template_cl_id );


$template_404_template_cl_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cassio Lovo - 404 Template',
    'file' => '404_cloud_template.txt',
    'template_type' => '404',
));

td_demo_misc::update_global_404_template( 'tdb_template_' . $template_404_template_cl_id );


$template_date_template_cl_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cassio Lovo - Date Template',
    'file' => 'date_cloud_template.txt',
    'template_type' => 'date',
));

td_demo_misc::update_global_date_template( 'tdb_template_' . $template_date_template_cl_id );


$template_author_template_cl_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cassio Lovo - Author Template',
    'file' => 'author_cloud_template.txt',
    'template_type' => 'author',
));

td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_cl_id );


$template_category_template_cl_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cassio Lovo - Category Template',
    'file' => 'cat_cloud_template.txt',
    'template_type' => 'category',
));

td_demo_misc::update_global_category_template( 'tdb_template_' . $template_category_template_cl_id );


$template_single_template_cl_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cassio Lovo - Single Template',
    'file' => 'post_cloud_template.txt',
    'template_type' => 'single',
));

td_util::update_option( 'td_default_site_post_template', 'tdb_template_' . $template_single_template_cl_id );


$template_custom_post_type_cl_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cassio Lovo - Custom Post Type',
    'file' => 'cpt_cloud_template.txt',
    'template_type' => 'cpt',
));


td_demo_misc::update_global_cpt_template( 'tdb_template_' . $template_custom_post_type_cl_id, 'tdcpt_menu', 'td_default_site_post_template' );


$template_custom_taxonomy_cl_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cassio Lovo - Custom Taxonomy',
    'file' => 'custom_taxonomy_cl_cloud_template.txt',
    'template_type' => 'cpt_tax',
));



td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_cl_id, 'tdtax_menu_list' );


td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_cl_id, 'tdtax_ingredients_list' );


$template_footer_template_cl_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cassio Lovo - Footer Template',
    'file' => 'footer_template_cl_cloud_template.txt',
    'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_template_cl_id );


$template_header_template_cl_id = td_demo_content::add_cloud_template( array(
    'title' => 'Cassio Lovo - Header Template',
    'file' => 'header_template_cl_cloud_template.txt',
    'template_type' => 'header',
));

td_demo_misc::update_global_header_template( 'tdb_template_' . $template_header_template_cl_id );



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
td_demo_content::update_meta( $template_footer_template_cl_id, 'tdc_footer_template_id', $template_footer_template_cl_id );

td_demo_content::update_meta( $template_header_template_cl_id, 'tdc_header_template_id', $template_header_template_cl_id );

// pages metas
td_demo_content::update_meta( $page_homepage_id, 'tdc_header_template_id', $template_header_template_cl_id );

td_demo_content::update_meta( $page_homepage_id, 'tdc_footer_template_id', $template_footer_template_cl_id );
