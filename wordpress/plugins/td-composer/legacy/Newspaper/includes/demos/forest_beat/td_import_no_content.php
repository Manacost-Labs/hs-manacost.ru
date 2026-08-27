<?php


/*  ---------------------------------------------------------------------------- 
	 CLOUD TEMPLATES(MODULES)
*/
$template_module_template_1_id = td_demo_content::add_cloud_template( array(
    'title' => 'Module Template 1 - Forest Beat',
    'file' => 'module_template_1_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '410',
));

$template_module_template_2_id = td_demo_content::add_cloud_template( array(
    'title' => 'Module Template 2 - Forest Beat',
    'file' => 'module_template_2_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '531',
));

$template_module_template_3_id = td_demo_content::add_cloud_template( array(
    'title' => 'Module Template 3 - Forest Beat',
    'file' => 'module_template_3_cloud_template.txt',
    'template_type' => 'module',
    'module_template_id' => '549',
));

$template_module_template_4_id = td_demo_content::add_cloud_template( array(
	'title' => 'Module Template 4 - Forest Beat',
	'file' => 'module_template_4_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '592',
));


/*  ---------------------------------------------------------------------------- 
	PAGES
*/
$page_mobile_menu_modal_id = td_demo_content::add_page( array(
	'title' => 'Mobile Menu Modal - Forest Beat',
	'file' => 'mobile_menu_modal.txt',
	'demo_unique_id' => '2065b8d9fbe78c1',
));

$page_search_modal_id = td_demo_content::add_page( array(
	'title' => 'Search Modal - Forest Beat',
	'file' => 'search_modal.txt',
	'demo_unique_id' => '3065b8d9fbe7c75',
));

$page_home_id = td_demo_content::add_page( array(
	'title' => 'Home',
	'file' => 'home.txt',
	'homepage' => true,
	'demo_unique_id' => '2865b8d9fbe80b8',
));


/*  ---------------------------------------------------------------------------- 
	CLOUD TEMPLATES
*/
$template_tag_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Tag Template - Forest Beat',
	'file' => 'tag_cloud_template.txt',
	'template_type' => 'tag',
));

td_demo_misc::update_global_tag_template( 'tdb_template_' . $template_tag_template_id );


$template_search_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Search Template - Forest Beat',
	'file' => 'search_cloud_template.txt',
	'template_type' => 'search',
));

td_demo_misc::update_global_search_template( 'tdb_template_' . $template_search_template_id );


$template_date_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Date Template - Forest Beat',
	'file' => 'date_cloud_template.txt',
	'template_type' => 'date',
));

td_demo_misc::update_global_date_template( 'tdb_template_' . $template_date_template_id );


$template_author_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Author Template - Forest Beat',
	'file' => 'author_cloud_template.txt',
	'template_type' => 'author',
));

td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_id );


$template_category_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Category Template - Forest Beat',
	'file' => 'cat_cloud_template.txt',
	'template_type' => 'category',
));

td_demo_misc::update_global_category_template( 'tdb_template_' . $template_category_template_id );


$template_single_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Single Template - Forest Beat',
	'file' => 'post_cloud_template.txt',
	'template_type' => 'single',
));

td_util::update_option( 'td_default_site_post_template', 'tdb_template_' . $template_single_template_id );


$template_404_template_id = td_demo_content::add_cloud_template( array(
	'title' => '404 Template - Forest Beat',
	'file' => '404_cloud_template.txt',
	'template_type' => '404',
));

td_demo_misc::update_global_404_template( 'tdb_template_' . $template_404_template_id );


$template_footer_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Footer Template - Forest Beat',
	'file' => 'footer_template_cloud_template.txt',
	'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_template_id );


$template_header_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Header Template - Forest Beat',
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
td_demo_content::update_meta( $page_mobile_menu_modal_id, 'tdc_header_template_id', 'no_header' );

td_demo_content::update_meta( $page_mobile_menu_modal_id, 'tdc_footer_template_id', 'no_footer' );

td_demo_content::update_meta( $page_search_modal_id, 'tdc_header_template_id', 'no_header' );

td_demo_content::update_meta( $page_search_modal_id, 'tdc_footer_template_id', 'no_footer' );
