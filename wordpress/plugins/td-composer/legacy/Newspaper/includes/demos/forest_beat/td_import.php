<?php 



/*  ---------------------------------------------------------------------------- 
	EXTERNAL PLUGINS DATA IMPORT
*/

/*  ---------------------------------------------------------------------------- 
	CATEGORIES
*/
$cat_biodiversity_id = td_demo_category::add_category(array(
	'category_name' => 'Biodiversity',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_climate_id = td_demo_category::add_category(array(
	'category_name' => 'Climate',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_conservation_id = td_demo_category::add_category(array(
	'category_name' => 'Conservation',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_featured_id = td_demo_category::add_category(array(
	'category_name' => 'Featured',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_forestry_id = td_demo_category::add_category(array(
	'category_name' => 'Forestry',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_heritage_id = td_demo_category::add_category(array(
	'category_name' => 'Heritage',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_uncategorized_id = td_demo_category::add_category(array(
	'category_name' => 'Uncategorized',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));


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
	ATTACHMENTS
*/

/*  ---------------------------------------------------------------------------- 
	PAGES
*/
$menu_td_demo_header_menu_id = td_demo_menus::create_menu('td-demo-header-menu', '');

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
	POSTS
*/
$post_td_post_forest_management_balancing_ecology_and_economy_id = td_demo_content::add_post( array(
	'title' => 'Forest Management: Balancing Ecology and Economy',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_forestry_id,),
));

$post_td_post_the_interplay_of_forests_and_snow_id = td_demo_content::add_post( array(
	'title' => 'The Interplay of Forests and Snow',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_climate_id,),
));

$post_td_post_restoration_turning_barren_lands_back_to_green_id = td_demo_content::add_post( array(
	'title' => 'Restoration: Turning Barren Lands Back to Green',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_conservation_id,),
));

$post_td_post_canopy_connections_understanding_tree_tops_id = td_demo_content::add_post( array(
	'title' => 'Canopy Connections: Understanding Tree-Tops',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_biodiversity_id,),
));

$post_td_post_depicting_forests_in_art_and_photography_id = td_demo_content::add_post( array(
	'title' => 'Depicting Forests in Art and Photography',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_heritage_id,),
));

$post_td_post_the_future_of_bamboo_sustainable_forest_alternative_id = td_demo_content::add_post( array(
	'title' => 'The Future of Bamboo: Sustainable Forest Alternative',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_forestry_id,),
));

$post_td_post_forests_at_war_historical_battlefields_id = td_demo_content::add_post( array(
	'title' => 'Forests at War: Historical Battlefields',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_heritage_id,),
));

$post_td_post_the_secret_world_of_forest_floor_organisms_id = td_demo_content::add_post( array(
	'title' => 'The Secret World of Forest Floor Organisms',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_biodiversity_id,),
));

$post_td_post_understanding_the_causes_and_consequences_id = td_demo_content::add_post( array(
	'title' => 'Understanding the Causes and Consequences',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_climate_id,),
));

$post_td_post_invasive_species_a_forests_fight_for_survival_id = td_demo_content::add_post( array(
	'title' => 'Invasive Species: A Forest\'s Fight for Survival',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_conservation_id,),
));

$post_td_post_indigenous_knowledge_and_forest_preservation_id = td_demo_content::add_post( array(
	'title' => 'Indigenous Knowledge and Forest Preservation',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_conservation_id,),
));

$post_td_post_tomorrows_forests_todays_reforestation_efforts_id = td_demo_content::add_post( array(
	'title' => 'Tomorrow’s Forests: Today’s Reforestation Efforts',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_forestry_id,),
));

$post_td_post_enchanted_forests_in_literature_and_fantasy_id = td_demo_content::add_post( array(
	'title' => 'Enchanted Forests in Literature and Fantasy',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_heritage_id,),
));

$post_td_post_adapting_forests_to_climate_induced_droughts_id = td_demo_content::add_post( array(
	'title' => 'Adapting Forests to Climate-Induced Droughts',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_climate_id,),
));

$post_td_post_aquatic_life_in_the_forests_river_systems_id = td_demo_content::add_post( array(
	'title' => 'Aquatic Life in the Forest’s River Systems',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_biodiversity_id,),
));

$post_td_post_sacred_groves_forests_in_spiritual_context_id = td_demo_content::add_post( array(
	'title' => 'Sacred Groves: Forests in Spiritual Context',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_heritage_id,),
));

$post_td_post_local_actions_global_impact_community_forestry_id = td_demo_content::add_post( array(
	'title' => 'Local Actions, Global Impact: Community Forestry',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_conservation_id,),
));

$post_td_post_fungi_unsung_heroes_of_forest_ecosystems_id = td_demo_content::add_post( array(
	'title' => 'Fungi: Unsung Heroes of Forest Ecosystems',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_biodiversity_id,),
));

$post_td_post_coastal_mangroves_and_the_rising_sea_levels_id = td_demo_content::add_post( array(
	'title' => 'Coastal Mangroves and the Rising Sea Levels',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_climate_id,),
));

$post_td_post_exploring_non_timber_forest_product_potential_id = td_demo_content::add_post( array(
	'title' => 'Exploring Non-Timber Forest Product Potential',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_forestry_id,),
));

$post_td_post_exploring_the_worlds_oldest_forests_id = td_demo_content::add_post( array(
	'title' => 'Exploring the World\'s Oldest Forests',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_heritage_id,),
));

$post_td_post_forest_carnivores_predators_facing_peril_id = td_demo_content::add_post( array(
	'title' => 'Forest Carnivores: Predators Facing Peril',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_biodiversity_id,),
));

$post_td_post_sustainable_harvesting_in_rubber_production_id = td_demo_content::add_post( array(
	'title' => 'Sustainable Harvesting in Rubber Production',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_forestry_id,),
));

$post_td_post_linking_habitats_wildlife_corridors_for_species_id = td_demo_content::add_post( array(
	'title' => 'Linking Habitats: Wildlife Corridors for Species',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_conservation_id,),
));

$post_td_post_global_climate_effects_of_tropical_deforestation_id = td_demo_content::add_post( array(
	'title' => 'Global Climate Effects of Tropical Deforestation',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_climate_id,),
));

$post_td_post_ancient_lore_trees_in_world_mythology_id = td_demo_content::add_post( array(
	'title' => 'Ancient Lore: Trees in World Mythology',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_heritage_id,),
));

$post_td_post_eco_friendly_paper_from_forest_to_production_id = td_demo_content::add_post( array(
	'title' => 'Eco-Friendly Paper: From Forest to Production',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_forestry_id,),
));

$post_td_post_insect_diversity_the_forests_tiny_custodians_id = td_demo_content::add_post( array(
	'title' => 'Insect Diversity: The Forest\'s Tiny Custodians',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_biodiversity_id,),
));

$post_td_post_from_melting_glaciers_to_growing_forests_id = td_demo_content::add_post( array(
	'title' => 'From Melting Glaciers to Growing Forests',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_biodiversity_id,$cat_climate_id,$cat_conservation_id,),
));

$post_td_post_the_rise_of_urban_forests_in_city_landscapes_id = td_demo_content::add_post( array(
	'title' => 'The Rise of Urban Forests in City Landscapes',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_conservation_id,),
));

$post_td_post_ancient_forests_prehistoric_secrets_id = td_demo_content::add_post( array(
	'title' => 'Ancient Forests: Prehistoric Secrets',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_heritage_id,),
));

$post_td_post_migratory_patterns_of_canopy_dwelling_birds_id = td_demo_content::add_post( array(
	'title' => 'Migratory Patterns of Canopy-Dwelling Birds',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_biodiversity_id,),
));

$post_td_post_protecting_ancient_forests_from_modern_logging_id = td_demo_content::add_post( array(
	'title' => 'Protecting Ancient Forests from Modern Logging',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_conservation_id,),
));

$post_td_post_forests_as_carbon_sinks_in_climate_battle_id = td_demo_content::add_post( array(
	'title' => 'Forests as Carbon Sinks in Climate Battle',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_climate_id,),
));

$post_td_post_blending_agriculture_with_forest_stewardship_id = td_demo_content::add_post( array(
	'title' => 'Blending Agriculture with Forest Stewardship',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_forestry_id,),
));

$post_td_post_forests_in_cinema_wilderness_on_screen_id = td_demo_content::add_post( array(
	'title' => 'Forests in Cinema: Wilderness on Screen',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_heritage_id,),
));

$post_td_post_green_logging_timber_harvests_future_id = td_demo_content::add_post( array(
	'title' => 'Green Logging: Timber Harvest\'s Future',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_forestry_id,),
));

$post_td_post_the_rising_threat_of_forest_fires_globally_id = td_demo_content::add_post( array(
	'title' => 'The Rising Threat of Forest Fires Globally',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_climate_id,),
));

$post_td_post_unveiling_the_lives_of_forest_elephants_id = td_demo_content::add_post( array(
	'title' => 'Unveiling the Lives of Forest Elephants',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_biodiversity_id,),
));

$post_td_post_new_strategies_in_reviving_tropical_rainforests_id = td_demo_content::add_post( array(
	'title' => 'New Strategies in Reviving Tropical Rainforests',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_conservation_id,),
));


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
$menu_td_demo_footer_menu_id = td_demo_menus::create_menu('td-demo-footer-menu', ''); 


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_link( array(
	'title' => 'Who We Are',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_link( array(
	'title' => 'Press',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_link( array(
	'title' => 'Advertising',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_link( array(
	'title' => 'Legal Notice',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_td_demo_footer_menu_extra_id = td_demo_menus::create_menu('td-demo-footer-menu-extra', ''); 


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_link( array(
	'title' => 'Privacy Policy',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_link( array(
	'title' => 'Terms & Conditions',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_link( array(
	'title' => 'FAQ',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_link( array(
	'title' => 'Contact',
	'add_to_menu_id' => $menu_td_demo_footer_menu_extra_id,
	'url' => '#',
	'parent_id' => ''
));


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Conservation',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_conservation_id,
	'parent_id' => ''
), true );

$menu_item_1_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Climate',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_climate_id,
	'parent_id' => ''
), true );

$menu_item_2_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Biodiversity',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_biodiversity_id,
	'parent_id' => ''
), true );

$menu_item_3_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Forestry',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_forestry_id,
	'parent_id' => ''
), true );

$menu_item_4_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Heritage',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_heritage_id,
	'parent_id' => ''
), true );


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
