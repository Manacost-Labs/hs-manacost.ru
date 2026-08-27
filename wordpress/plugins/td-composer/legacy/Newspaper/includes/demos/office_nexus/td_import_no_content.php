<?php 



/*  ---------------------------------------------------------------------------- 
	EXTERNAL PLUGINS DATA IMPORT
*/
/* -- ACF -- */
// Field groups
$field_group_inquiry_information = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/office_nexus/data/acf/group_6602a092d4dc7.json' );
$field_group_location_details = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/office_nexus/data/acf/group_65fbeea98d4a3.json' );
$field_group_service_details = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/office_nexus/data/acf/group_65fbefe2ee348.json' );
$field_group_testimonial_details = td_demo_data::acf_import_field_group( 'https://cloud.tagdiv.com/demos/Newspaper/office_nexus/data/acf/group_65fd6a3b7ff80.json' );

// Post types
$post_type_inquiries = td_demo_data::acf_import_post_type( 'https://cloud.tagdiv.com/demos/Newspaper/office_nexus/data/acf/post_type_66029fdc05367.json' );
$post_type_locations = td_demo_data::acf_import_post_type( 'https://cloud.tagdiv.com/demos/Newspaper/office_nexus/data/acf/post_type_65fbeda37e343.json' );
$post_type_services = td_demo_data::acf_import_post_type( 'https://cloud.tagdiv.com/demos/Newspaper/office_nexus/data/acf/post_type_65fbed690f5cd.json' );
$post_type_testimonials = td_demo_data::acf_import_post_type( 'https://cloud.tagdiv.com/demos/Newspaper/office_nexus/data/acf/post_type_65fd69f36e8cc.json' );

// Taxonomies
$taxonomy_amenities = td_demo_data::acf_import_taxonomy( 'https://cloud.tagdiv.com/demos/Newspaper/office_nexus/data/acf/taxonomy_65fbee6556d51.json' );


/*  ---------------------------------------------------------------------------- 
	 CLOUD TEMPLATES(MODULES)
*/
$template_module_template_office_nexus_locations_title_only_id = td_demo_content::add_cloud_template( array(
	'title' => 'Module Template - Office Nexus - Locations - Title only',
	'file' => 'module_template_office_nexus_locations_title_only_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '846',
));

$template_module_template_office_nexus_locations_id = td_demo_content::add_cloud_template( array(
	'title' => 'Module Template - Office Nexus - Locations',
	'file' => 'module_template_office_nexus_locations_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '746',
));

$template_module_template_office_nexus_locations_large_id = td_demo_content::add_cloud_template( array(
	'title' => 'Module Template - Office Nexus - Locations - Large',
	'file' => 'module_template_office_nexus_locations_large_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '517',
));

$template_module_template_office_nexus_services_detailed_id = td_demo_content::add_cloud_template( array(
	'title' => 'Module Template - Office Nexus - Services - Detailed',
	'file' => 'module_template_office_nexus_services_detailed_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '482',
));

$template_module_template_office_nexus_testimonials_id = td_demo_content::add_cloud_template( array(
	'title' => 'Module Template - Office Nexus - Testimonials',
	'file' => 'module_template_office_nexus_testimonials_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '240',
));

$template_module_template_office_nexus_services_id = td_demo_content::add_cloud_template( array(
	'title' => 'Module Template - Office Nexus - Services',
	'file' => 'module_template_office_nexus_services_cloud_template.txt',
	'template_type' => 'module',
	'module_template_id' => '141',
));


/*  ---------------------------------------------------------------------------- 
	ATTACHMENTS
*/

/*  ---------------------------------------------------------------------------- 
	PAGES
*/

$page_contact_id = td_demo_content::add_page( array(
	'title' => 'Contact',
	'file' => 'contact.txt',
	'demo_unique_id' => '8766056cf03983b',
));

$page_locations_id = td_demo_content::add_page( array(
	'title' => 'Locations',
	'file' => 'locations.txt',
	'demo_unique_id' => '4366056cf03a299',
));

$page_services_id = td_demo_content::add_page( array(
	'title' => 'Services',
	'file' => 'services.txt',
	'demo_unique_id' => '3966056cf03a61e',
));

$page_about_id = td_demo_content::add_page( array(
	'title' => 'About',
	'file' => 'about.txt',
	'demo_unique_id' => '5066056cf039df0',
));

$page_mobile_menu_modal_office_nexus_id = td_demo_content::add_page( array(
	'title' => 'Mobile Menu Modal - Office Nexus',
	'file' => 'mobile_menu_modal_office_nexus.txt',
	'demo_unique_id' => '1366056cf0392d1',
));

$page_home_id = td_demo_content::add_page( array(
	'title' => 'Home',
	'file' => 'home.txt',
	'template' => 'default',
	'homepage' => true,
	'demo_unique_id' => '2166056cf03aa47',
));


/*  ---------------------------------------------------------------------------- 
	CLOUD TEMPLATES
*/
$template_404_template_office_nexus_id = td_demo_content::add_cloud_template( array(
	'title' => '404 Template - Office Nexus',
	'file' => '404_cloud_template.txt',
	'template_type' => '404',
));

td_demo_misc::update_global_404_template( 'tdb_template_' . $template_404_template_office_nexus_id );


$template_custom_post_type_template_office_nexus_testimonials_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Post Type Template - Office Nexus - Testimonials',
	'file' => 'cpt_cloud_template_testimonials.txt',
	'template_type' => 'cpt',
));

td_demo_misc::update_global_cpt_template( 'tdb_template_' . $template_custom_post_type_template_office_nexus_testimonials_id, 'tdcpt_testimonial', 'td_default_site_post_template' );


$template_custom_taxonomy_template_office_nexus_amenities_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Taxonomy Template - Office Nexus - Amenities',
	'file' => 'custom_taxonomy_template_office_nexus_amenities_cloud_template.txt',
	'template_type' => 'cpt_tax',
));

td_demo_misc::update_global_cpt_tax_template( 'tdb_template_' . $template_custom_taxonomy_template_office_nexus_amenities_id, 'tdtax_amenity' );


$template_custom_post_type_template_office_nexus_locations_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Post Type Template - Office Nexus - Locations',
	'file' => 'cpt_cloud_template_locations.txt',
	'template_type' => 'cpt',
));

td_demo_misc::update_global_cpt_template( 'tdb_template_' . $template_custom_post_type_template_office_nexus_locations_id, 'tdcpt_location', 'td_default_site_post_template' );


$template_custom_post_type_template_office_nexus_services_id = td_demo_content::add_cloud_template( array(
	'title' => 'Custom Post Type Template - Office Nexus - Services',
	'file' => 'cpt_cloud_template_services.txt',
	'template_type' => 'cpt',
));

td_demo_misc::update_global_cpt_template( 'tdb_template_' . $template_custom_post_type_template_office_nexus_services_id, 'tdcpt_service', 'td_default_site_post_template' );


$template_footer_template_office_nexus_id = td_demo_content::add_cloud_template( array(
	'title' => 'Footer Template - Office Nexus',
	'file' => 'footer_template_office_nexus_cloud_template.txt',
	'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_template_office_nexus_id );


$template_header_template_office_nexus_id = td_demo_content::add_cloud_template( array(
	'title' => 'Header Template - Office Nexus',
	'file' => 'header_template_office_nexus_cloud_template.txt',
	'template_type' => 'header',
));

td_demo_misc::update_global_header_template( 'tdb_template_' . $template_header_template_office_nexus_id );



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
td_demo_content::update_meta( $template_footer_template_office_nexus_id, 'tdc_footer_template_id', $template_footer_template_office_nexus_id );

td_demo_content::update_meta( $template_header_template_office_nexus_id, 'tdc_header_template_id', $template_header_template_office_nexus_id );

// pages metas
td_demo_content::update_meta( $page_mobile_menu_modal_office_nexus_id, 'tdc_footer_template_id', 'no_footer' );

td_demo_content::update_meta( $page_mobile_menu_modal_office_nexus_id, 'tdc_header_template_id', 'no_header' );
