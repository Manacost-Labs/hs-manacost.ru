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
	POSTS
*/

/*  ---------------------------------------------------------------------------- 
	PRODUCTS
*/

/*  ---------------------------------------------------------------------------- 
	TAXONOMIES
*/
$tax_term_parking_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Parking',
	'taxonomy' => 'tdtax_amenity',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
	'filter_image' => 'tdx_pic_1',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_wifi_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'WiFi',
	'taxonomy' => 'tdtax_amenity',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
	'filter_image' => 'tdx_pic_2',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_printer_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Printer',
	'taxonomy' => 'tdtax_amenity',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
	'filter_image' => 'tdx_pic_3',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_ev_charger_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'EV Charger',
	'taxonomy' => 'tdtax_amenity',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
	'filter_image' => 'tdx_pic_4',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_coffee_bar_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Coffee Bar',
	'taxonomy' => 'tdtax_amenity',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
	'filter_image' => 'tdx_pic_5',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_board_games_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Board Games',
	'taxonomy' => 'tdtax_amenity',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
	'filter_image' => 'tdx_pic_6',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_security_cameras_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Security Cameras',
	'taxonomy' => 'tdtax_amenity',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
	'filter_image' => 'tdx_pic_7',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_cleaning_service_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Cleaning Service',
	'taxonomy' => 'tdtax_amenity',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
	'filter_image' => 'tdx_pic_8',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));


/*  ---------------------------------------------------------------------------- 
	CPTs
*/
$cpt_techtown_id = td_demo_content::add_cpt( array(
	'title' => 'TechTown',
	'type' => 'tdcpt_location',
	'file' => 'tdcpt_location_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_address' => 'MTIzIE1haW4gU3RyZWV0LCBTdWl0ZSAxODAsIE5ldyBZb3JrLCBOWSAxMjM0NQ==',
		'tdcf_phone_number' => 'KDEyMykgNDU2IC0gNzg5MQ==',
		'tdcf_operating_hours' => 'V2Vla2RheXMg4oCTIDA3OjAwLTIyOjAwDQpXZWVrZW5kcyDigJMgMDk6MDAtMTk6MDA=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_amenity' => array( $tax_term_board_games_id, $tax_term_cleaning_service_id, $tax_term_coffee_bar_id, $tax_term_printer_id, $tax_term_security_cameras_id, $tax_term_wifi_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('td_pic_1', 'tdx_pic_30'),
	),
));

$cpt_creative_lofts_id = td_demo_content::add_cpt( array(
	'title' => 'Creative Lofts',
	'type' => 'tdcpt_location',
	'file' => 'tdcpt_location_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_address' => 'MTIzIE1haW4gU3RyZWV0LCBTdWl0ZSAxODAsIE5ldyBZb3JrLCBOWSAxMjM0NQ==',
		'tdcf_phone_number' => 'KDEyMykgNDU2IC0gNzg5MQ==',
		'tdcf_operating_hours' => 'V2Vla2RheXMg4oCTIDA3OjAwLTIyOjAwDQpXZWVrZW5kcyDigJMgMDk6MDAtMTk6MDA=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_amenity' => array( $tax_term_board_games_id, $tax_term_cleaning_service_id, $tax_term_ev_charger_id, $tax_term_parking_id, $tax_term_security_cameras_id, $tax_term_wifi_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('td_pic_2', 'tdx_pic_30'),
	),
));

$cpt_capital_gateway_id = td_demo_content::add_cpt( array(
	'title' => 'Capital Gateway',
	'type' => 'tdcpt_location',
	'file' => 'tdcpt_location_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_address' => 'MTIzIE1haW4gU3RyZWV0LCBTdWl0ZSAxODAsIE5ldyBZb3JrLCBOWSAxMjM0NQ==',
		'tdcf_phone_number' => 'KDEyMykgNDU2IC0gNzg5MQ==',
		'tdcf_operating_hours' => 'V2Vla2RheXMg4oCTIDA3OjAwLTIyOjAwDQpXZWVrZW5kcyDigJMgMDk6MDAtMTk6MDA=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_amenity' => array( $tax_term_board_games_id, $tax_term_cleaning_service_id, $tax_term_coffee_bar_id, $tax_term_ev_charger_id, $tax_term_parking_id, $tax_term_printer_id, $tax_term_security_cameras_id, $tax_term_wifi_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('td_pic_3', 'tdx_pic_30'),
	),
));

$cpt_the_innovation_hub_id = td_demo_content::add_cpt( array(
	'title' => 'The Innovation Hub',
	'type' => 'tdcpt_location',
	'file' => 'tdcpt_location_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_address' => 'MTIzIE1haW4gU3RyZWV0LCBTdWl0ZSAxODAsIE5ldyBZb3JrLCBOWSAxMjM0NQ==',
		'tdcf_phone_number' => 'KDEyMykgNDU2IC0gNzg5MQ==',
		'tdcf_operating_hours' => 'V2Vla2RheXMg4oCTIDA3OjAwLTIyOjAwDQpXZWVrZW5kcyDigJMgMDk6MDAtMTk6MDA=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_amenity' => array( $tax_term_cleaning_service_id, $tax_term_ev_charger_id, $tax_term_printer_id, $tax_term_wifi_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('td_pic_4', 'tdx_pic_30'),
	),
));

$cpt_harborview_complex_id = td_demo_content::add_cpt( array(
	'title' => 'HarborView Complex',
	'type' => 'tdcpt_location',
	'file' => 'tdcpt_location_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_address' => 'MTIzIE1haW4gU3RyZWV0LCBTdWl0ZSAxODAsIE5ldyBZb3JrLCBOWSAxMjM0NQ==',
		'tdcf_phone_number' => 'KDEyMykgNDU2IC0gNzg5MQ==',
		'tdcf_operating_hours' => 'V2Vla2RheXMg4oCTIDA3OjAwLTIyOjAwDQpXZWVrZW5kcyDigJMgMDk6MDAtMTk6MDA=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_amenity' => array( $tax_term_board_games_id, $tax_term_coffee_bar_id, $tax_term_parking_id, $tax_term_security_cameras_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('td_pic_5', 'tdx_pic_30'),
	),
));

$cpt_greentech_park_id = td_demo_content::add_cpt( array(
	'title' => 'GreenTech Park',
	'type' => 'tdcpt_location',
	'file' => 'tdcpt_location_default.txt',
	'cpt_image_td_id' => 'td_pic_6',
	'post_meta' => array( 
		'tdcf_address' => 'MTIzIE1haW4gU3RyZWV0LCBTdWl0ZSAxODAsIE5ldyBZb3JrLCBOWSAxMjM0NQ==',
		'tdcf_phone_number' => 'KDEyMykgNDU2IC0gNzg5MQ==',
		'tdcf_operating_hours' => 'V2Vla2RheXMg4oCTIDA3OjAwLTIyOjAwDQpXZWVrZW5kcyDigJMgMDk6MDAtMTk6MDA=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_amenity' => array( $tax_term_board_games_id, $tax_term_cleaning_service_id, $tax_term_coffee_bar_id, $tax_term_ev_charger_id, $tax_term_parking_id, $tax_term_printer_id, $tax_term_security_cameras_id, $tax_term_wifi_id ),
	),
	'td_post_theme_settings' => array(
		'td_gallery_imgs' => array('td_pic_6', 'tdx_pic_30'),
	),
));

$cpt_virtual_office_id = td_demo_content::add_cpt( array(
	'title' => 'Virtual Office',
	'type' => 'tdcpt_service',
	'file' => 'tdcpt_service_default.txt',
	'cpt_image_td_id' => 'td_pic_7',
	'post_meta' => array( 
		'tdcf_price' => 'MjAw',
		'tdcf_price_interval' => 'cGVyX21vbnRo',
		'tdcf_highlights' => 'VXNlIG91ciBwcmltZSBidXNpbmVzcyBhZGRyZXNzIG9uIHlvdXIgY29tcGFueSdzIGNvcnJlc3BvbmRlbmNlLCB3ZWJzaXRlLCBhbmQgYnVzaW5lc3MgY2FyZHMuDQpCZW5lZml0IGZyb20gcHJvZmVzc2lvbmFsIG1haWwgbWFuYWdlbWVudCwgaW5jbHVkaW5nIHJlY2VpdmluZywgc3RvcmluZywgYW5kIGZvcndhcmRpbmcgeW91ciBtYWlsLg0KUmVjZWl2ZSBhIGRlZGljYXRlZCBsb2NhbCBwaG9uZSBudW1iZXIgZm9yIHlvdXIgYnVzaW5lc3MgYW5kIHByb2Zlc3Npb25hbCByZWNlcHRpb25pc3Qgc2VydmljZXMu',
		'tdcf_capacity' => 'MQ==',
		'tdcf_listing_icon' => 'dGR4X3BpY18yNA==',
	),
));

$cpt_office_space_id = td_demo_content::add_cpt( array(
	'title' => 'Office Space',
	'type' => 'tdcpt_service',
	'file' => 'tdcpt_service_default.txt',
	'cpt_image_td_id' => 'td_pic_8',
	'post_meta' => array( 
		'tdcf_price' => 'NTAw',
		'tdcf_price_interval' => 'cGVyX21vbnRo',
		'tdcf_highlights' => 'VGFpbG9yIHlvdXIgb2ZmaWNlIGxheW91dCB0byBtYXRjaCB5b3VyIGNvbXBhbnnigJlzIGN1bHR1cmUgYW5kIHdvcmtmbG93LiBPdXIgc3BhY2VzIGFyZSBkZXNpZ25lZCBmb3IgYWRhcHRhYmlsaXR5Lg0KU3RheSBhaGVhZCB3aXRoIG91ciBmdWxseSBlcXVpcHBlZCBvZmZpY2VzLCBmZWF0dXJpbmcgaGlnaC1zcGVlZCBpbnRlcm5ldCwgbW9kZXJuIElUIGluZnJhc3RydWN0dXJlLg0KQmVuZWZpdCBmcm9tIGEgZnVsbCBzdWl0ZSBvZiBhbWVuaXRpZXMgaW5jbHVkaW5nIHJlY2VwdGlvbiBzZXJ2aWNlcywgbWFpbCBoYW5kbGluZywgY2xlYW5pbmcgc2VydmljZXMu',
		'tdcf_capacity' => 'MS0xMDA=',
		'tdcf_listing_icon' => 'dGR4X3BpY18yNQ==',
	),
));

$cpt_event_space_id = td_demo_content::add_cpt( array(
	'title' => 'Event Space',
	'type' => 'tdcpt_service',
	'file' => 'tdcpt_service_default.txt',
	'cpt_image_td_id' => 'td_pic_9',
	'post_meta' => array( 
		'tdcf_price' => 'MjUw',
		'tdcf_price_interval' => 'cGVyX2RheQ==',
		'tdcf_highlights' => 'T3VyIGV2ZW50IHNwYWNlcyBhcmUgZGVzaWduZWQgdG8gYWNjb21tb2RhdGUgYSB3aWRlIHJhbmdlIG9mIGV2ZW50cywgZnJvbSB3b3Jrc2hvcHMgdG8gY29uZmVyZW5jZXMuDQpFcXVpcHBlZCB3aXRoIHRvcC1vZi10aGUtbGluZSBhdWRpby92aXN1YWwgZXF1aXBtZW50LCBpbmNsdWRpbmcgaGlnaC1kZWZpbml0aW9uIHByb2plY3RvcnMuDQpCZW5lZml0IGZyb20gb3VyIGRlZGljYXRlZCBldmVudCBwbGFubmluZyBhbmQgc3VwcG9ydCB0ZWFtLCBvZmZlcmluZyBhc3Npc3RhbmNlLg==',
		'tdcf_capacity' => 'MS0yMDA=',
		'tdcf_listing_icon' => 'dGR4X3BpY18yNg==',
	),
));

$cpt_meeting_room_id = td_demo_content::add_cpt( array(
	'title' => 'Meeting Room',
	'type' => 'tdcpt_service',
	'file' => 'tdcpt_service_default.txt',
	'cpt_image_td_id' => 'td_pic_10',
	'post_meta' => array( 
		'tdcf_price' => 'MzA=',
		'tdcf_price_interval' => 'cGVyX2hvdXI=',
		'tdcf_highlights' => 'RXF1aXBwZWQgd2l0aCB0aGUgbGF0ZXN0IGluIHZpZGVvIGNvbmZlcmVuY2luZyB0ZWNobm9sb2d5LCBoaWdoLXNwZWVkIGludGVybmV0Lg0KUmVzZXJ2ZSBtZWV0aW5nIHJvb21zIGJ5IHRoZSBob3VyIG9yIGZvciBmdWxsIGRheXMgd2l0aCBvdXIgZWFzeS10by11c2UgYm9va2luZyBzeXN0ZW0uDQpUYWlsb3IgdGhlIGxheW91dCBvZiB5b3VyIG1lZXRpbmcgcm9vbSB0byBtYXRjaCB0aGUgc3BlY2lmaWMgbmVlZHMgb2YgeW91ciBtZWV0aW5nLg==',
		'tdcf_capacity' => 'MS0yMA==',
		'tdcf_listing_icon' => 'dGR4X3BpY18yNw==',
	),
));

$cpt_dedicated_desk_id = td_demo_content::add_cpt( array(
	'title' => 'Dedicated Desk',
	'type' => 'tdcpt_service',
	'file' => 'tdcpt_service_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_price' => 'MzAw',
		'tdcf_price_interval' => 'cGVyX21vbnRo',
		'tdcf_highlights' => 'WW91ciBvd24gZGVkaWNhdGVkIGRlc2sgaW4gYSBzaGFyZWQgb2ZmaWNlIGVudmlyb25tZW50LCBjb21wbGV0ZSB3aXRoIGFuIGVyZ29ub21pYyBjaGFpci4NCldvcmsgaW4gYSBwcm9mZXNzaW9uYWwgc2V0dGluZyBzdXJyb3VuZGVkIGJ5IGxpa2UtbWluZGVkIGluZGl2aWR1YWxzIGFuZCBidXNpbmVzc2VzLg0KRW5qb3kgYWxsIHRoZSBhbWVuaXRpZXMgb2YgdGhlIGNvd29ya2luZyBzcGFjZSwgaW5jbHVkaW5nIGhpZ2gtc3BlZWQgaW50ZXJuZXQu',
		'tdcf_capacity' => 'MQ==',
		'tdcf_listing_icon' => 'dGR4X3BpY18yOA==',
	),
));

$cpt_coworking_space_id = td_demo_content::add_cpt( array(
	'title' => 'Coworking Space',
	'type' => 'tdcpt_service',
	'file' => 'tdcpt_service_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_price' => 'MjA=',
		'tdcf_price_interval' => 'cGVyX2RheQ==',
		'tdcf_highlights' => 'Q2hvb3NlIGZyb20gaG90IGRlc2tzLCBkZWRpY2F0ZWQgZGVza3MsIHByaXZhdGUgb2ZmaWNlcywgYW5kIGNvbGxhYm9yYXRpdmUgd29ya3NwYWNlcy4NCkVuam95IGFjY2VzcyB0byBoaWdoLXNwZWVkIGludGVybmV0LCBtb2Rlcm4gbWVldGluZyByb29tcyBlcXVpcHBlZCB3aXRoIHZpZGVvIGNvbmZlcmVuY2luZyB0b29scy4NCkJlIHBhcnQgb2YgYSB2aWJyYW50IGNvbW11bml0eSBvZiBwcm9mZXNzaW9uYWxzLiBSZWd1bGFyIG5ldHdvcmtpbmcgZXZlbnRzLCB3b3Jrc2hvcHMsIGFuZCBzZW1pbmFycy4=',
		'tdcf_capacity' => 'MTA=',
		'tdcf_listing_icon' => 'dGR4X3BpY18yOQ==',
	),
));

$cpt_sofia_alvarez_id = td_demo_content::add_cpt( array(
	'title' => 'Sofia Alvarez',
	'type' => 'tdcpt_testimonial',
	'file' => 'tdcpt_testimonial_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_position' => 'RGlyZWN0b3Igb2YgT3BlcmF0aW9ucywgR3JlZW5FYXJ0aCBOb25wcm9maXQ=',
	),
));

$cpt_marcus_johnson_id = td_demo_content::add_cpt( array(
	'title' => 'Marcus Johnson',
	'type' => 'tdcpt_testimonial',
	'file' => 'tdcpt_testimonial_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_position' => 'RnJlZWxhbmNlIEdyYXBoaWMgRGVzaWduZXI=',
	),
));

$cpt_emily_smith_id = td_demo_content::add_cpt( array(
	'title' => 'Emily Smith',
	'type' => 'tdcpt_testimonial',
	'file' => 'tdcpt_testimonial_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_position' => 'Q0VPLCBJbm5vdmF0ZVRlY2ggU29sdXRpb25z',
	),
));


/*  ---------------------------------------------------------------------------- 
	MENUS
*/
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
	'title' => 'Services',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_services_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_page(array(
	'title' => 'Locations',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_locations_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_page(array(
	'title' => 'About',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_about_id,
	'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_page(array(
	'title' => 'Contact',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_contact_id,
	'parent_id' => ''
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
