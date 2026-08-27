<?php


/*  ----------------------------------------------------------------------------
	MENUS
*/
$menu_td_demo_custom_menu_id = td_demo_menus::create_menu('td-demo-custom-menu', '');
$menu_td_demo_footer_menu_id = td_demo_menus::create_menu('td-demo-footer-menu', '');
$menu_td_demo_header_menu_id = td_demo_menus::create_menu('td-demo-header-menu', '');


/*  ---------------------------------------------------------------------------- 
	EXTERNAL PLUGINS DATA IMPORT
*/

/*  ---------------------------------------------------------------------------- 
	CATEGORIES
*/
$cat_blog_id = td_demo_category::add_category(array(
	'category_name' => 'Blog',
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

$cat_case_studies_id = td_demo_category::add_category(array(
	'category_name' => 'Case Studies',
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

$cat_colors_and_patterns_id = td_demo_category::add_category(array(
	'category_name' => 'Colors and Patterns',
	'parent_id' => $cat_blog_id,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_design_basics_id = td_demo_category::add_category(array(
	'category_name' => 'Design Basics',
	'parent_id' => $cat_blog_id,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_design_styles_id = td_demo_category::add_category(array(
	'category_name' => 'Design Styles',
	'parent_id' => $cat_blog_id,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_home_decor_trends_id = td_demo_category::add_category(array(
	'category_name' => 'Home Decor Trends',
	'parent_id' => $cat_blog_id,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_room_designs_id = td_demo_category::add_category(array(
	'category_name' => 'Room Designs',
	'parent_id' => $cat_blog_id,
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

/*  ---------------------------------------------------------------------------- 
	ATTACHMENTS
*/

/*  ---------------------------------------------------------------------------- 
	PAGES
*/
$page_contact_id = td_demo_content::add_page( array(
	'title' => 'Contact',
	'file' => 'contact.txt',
	'template' => 'default',
	'demo_unique_id' => '7565719754ca346',
));

$page_services_id = td_demo_content::add_page( array(
	'title' => 'Services',
	'file' => 'services.txt',
	'template' => 'default',
	'demo_unique_id' => '2765719754cb5f9',
));

$page_about_id = td_demo_content::add_page( array(
	'title' => 'About',
	'file' => 'about.txt',
	'template' => 'default',
	'demo_unique_id' => '4165719754cc4a2',
));

$page_home_id = td_demo_content::add_page( array(
	'title' => 'Home',
	'file' => 'home.txt',
	'template' => 'default',
	'homepage' => true,
	'demo_unique_id' => '2065719754cd6ef',
));


/*  ---------------------------------------------------------------------------- 
	POSTS
*/
$post_td_post_texture_talk_mixing_fabrics_and_materials_for_depth_and_interest_id = td_demo_content::add_post( array(
	'title' => 'Texture Talk: Mixing Fabrics and Materials for Depth and Interest',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_home_decor_trends_id,),
));

$post_td_post_cultural_inspirations_in_home_decor_a_global_tour_id = td_demo_content::add_post( array(
	'title' => 'Cultural Inspirations in Home Decor: A Global Tour',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_home_decor_trends_id,),
));

$post_td_post_seasonal_decor_refreshing_your_space_for_each_season_id = td_demo_content::add_post( array(
	'title' => 'Seasonal Decor: Refreshing Your Space for Each Season',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_home_decor_trends_id,),
));

$post_td_post_smart_home_decor_integrating_technology_elegantly_id = td_demo_content::add_post( array(
	'title' => 'Smart Home Decor: Integrating Technology Elegantly',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_home_decor_trends_id,),
));

$post_td_post_diy_decor_projects_that_add_your_personal_touch_id = td_demo_content::add_post( array(
	'title' => 'DIY Decor Projects that Add Your Personal Touch',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_home_decor_trends_id,),
));

$post_td_post_color_psychology_in_home_decor_choosing_the_right_palette_id = td_demo_content::add_post( array(
	'title' => 'Color Psychology in Home Decor: Choosing the Right Palette',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_home_decor_trends_id,),
));

$post_td_post_maximizing_small_spaces_creative_ideas_and_tips_id = td_demo_content::add_post( array(
	'title' => 'Maximizing Small Spaces: Creative Ideas and Tips',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_home_decor_trends_id,),
));

$post_td_post_eco_and_sustainable_decor_ideas_for_your_bathroom_id = td_demo_content::add_post( array(
	'title' => 'Eco and Sustainable Decor Ideas for Your Bathroom',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_home_decor_trends_id,),
));

$post_td_post_vintage_meets_modern_blending_styles_for_unique_interiors_id = td_demo_content::add_post( array(
	'title' => 'Vintage Meets Modern: Blending Styles for Unique Interiors',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_home_decor_trends_id,),
));

$post_td_post_top_10_emerging_home_decor_trends_for_next_year_id = td_demo_content::add_post( array(
	'title' => 'Top 10 Emerging Home Decor Trends for Next Year',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_home_decor_trends_id,),
));

$post_td_post_accessorizing_your_space_the_final_touches_id = td_demo_content::add_post( array(
	'title' => 'Accessorizing Your Space: The Final Touches',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_design_basics_id,),
));

$post_td_post_creating_flow_and_cohesion_in_your_homes_design_id = td_demo_content::add_post( array(
	'title' => 'Creating Flow and Cohesion in Your Home’s Design',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_design_basics_id,),
));

$post_td_post_balancing_high_end_and_budget_friendly_in_your_design_id = td_demo_content::add_post( array(
	'title' => 'Balancing High-End and Budget-Friendly in Your Design',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_design_basics_id,),
));

$post_td_post_the_importance_of_focal_points_in_room_design_id = td_demo_content::add_post( array(
	'title' => 'The Importance of Focal Points in Room Design',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_design_basics_id,),
));

$post_td_post_wall_treatments_paint_wallpaper_and_more_id = td_demo_content::add_post( array(
	'title' => 'Wall Treatments: Paint, Wallpaper, and More',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_design_basics_id,),
));

$post_td_post_flooring_options_pros_and_cons_of_different_materials_id = td_demo_content::add_post( array(
	'title' => 'Flooring Options: Pros and Cons of Different Materials',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_design_basics_id,),
));

$post_td_post_choosing_the_right_furniture_style_and_functionality_id = td_demo_content::add_post( array(
	'title' => 'Choosing the Right Furniture: Style and Functionality',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_design_basics_id,),
));

$post_td_post_lighting_101_how_to_properly_light_your_space_id = td_demo_content::add_post( array(
	'title' => 'Lighting 101: How to Properly Light Your Space',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_design_basics_id,),
));

$post_td_post_the_art_of_space_planning_tips_for_efficient_layouts_id = td_demo_content::add_post( array(
	'title' => 'The Art of Space Planning: Tips for Efficient Layouts',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_design_basics_id,),
));

$post_td_post_understanding_the_fundamentals_of_interior_design_id = td_demo_content::add_post( array(
	'title' => 'Understanding the Fundamentals of Interior Design',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_design_basics_id,),
));

$post_td_post_multipurpose_rooms_combining_functionality_with_style_id = td_demo_content::add_post( array(
	'title' => 'Multipurpose Rooms: Combining Functionality with Style',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_room_designs_id,),
));

$post_td_post_outdoor_living_spaces_bringing_comfort_outside_id = td_demo_content::add_post( array(
	'title' => 'Outdoor Living Spaces: Bringing Comfort Outside',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_room_designs_id,),
));

$post_td_post_transforming_your_basement_into_a_cozy_living_space_id = td_demo_content::add_post( array(
	'title' => 'Transforming Your Basement into a Cozy Living Space',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_room_designs_id,),
));

$post_td_post_dining_room_designs_for_memorable_family_gatherings_id = td_demo_content::add_post( array(
	'title' => 'Dining Room Designs for Memorable Family Gatherings',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_room_designs_id,),
));

$post_td_post_efficient_kitchen_layouts_for_every_cooking_style_id = td_demo_content::add_post( array(
	'title' => 'Efficient Kitchen Layouts for Every Cooking Style',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_room_designs_id,),
));

$post_td_post_creating_a_dreamy_and_relaxing_bedroom_retreat_id = td_demo_content::add_post( array(
	'title' => 'Creating a Dreamy and Relaxing Bedroom Retreat',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_room_designs_id,),
));

$post_td_post_kid_friendly_room_designs_that_grow_with_them_id = td_demo_content::add_post( array(
	'title' => 'Kid-Friendly Room Designs That Grow with Them',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_room_designs_id,),
));

$post_td_post_small_bathroom_big_impact_design_tips_for_tiny_spaces_id = td_demo_content::add_post( array(
	'title' => 'Small Bathroom, Big Impact: Design Tips for Tiny Spaces',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_room_designs_id,),
));

$post_td_post_designing_a_functional_and_stylish_home_office_id = td_demo_content::add_post( array(
	'title' => 'Designing a Functional and Stylish Home Office',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_room_designs_id,),
));

$post_td_post_revamping_your_living_room_ideas_for_a_fresh_look_id = td_demo_content::add_post( array(
	'title' => 'Revamping Your Living Room: Ideas for a Fresh Look',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_room_designs_id,),
));

$post_td_post_wallpapers_bringing_walls_to_life_with_patterns_id = td_demo_content::add_post( array(
	'title' => 'Wallpapers: Bringing Walls to Life with Patterns',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_colors_and_patterns_id,),
));

$post_td_post_accent_colors_adding_pops_of_interest_id = td_demo_content::add_post( array(
	'title' => 'Accent Colors: Adding Pops of Interest',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_colors_and_patterns_id,),
));

$post_td_post_color_trends_of_the_year_and_how_to_incorporate_them_id = td_demo_content::add_post( array(
	'title' => 'Color Trends of the Year and How to Incorporate Them',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_colors_and_patterns_id,),
));

$post_td_post_patterns_in_small_spaces_making_a_statement_id = td_demo_content::add_post( array(
	'title' => 'Patterns in Small Spaces: Making a Statement',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_colors_and_patterns_id,),
));

$post_td_post_neutral_tones_creating_warmth_and_sophistication_id = td_demo_content::add_post( array(
	'title' => 'Neutral Tones: Creating Warmth and Sophistication',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_colors_and_patterns_id,),
));

$post_td_post_using_bold_colors_the_dos_and_donts_id = td_demo_content::add_post( array(
	'title' => 'Using Bold Colors: The Dos and Don\'ts',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_colors_and_patterns_id,),
));

$post_td_post_the_psychology_of_color_in_interior_design_id = td_demo_content::add_post( array(
	'title' => 'The Psychology of Color in Interior Design',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_colors_and_patterns_id,),
));

$post_td_post_monochromatic_interiors_playing_with_shades_and_textures_id = td_demo_content::add_post( array(
	'title' => 'Monochromatic Interiors: Playing with Shades and Textures',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_colors_and_patterns_id,),
));

$post_td_post_mixing_and_matching_patterns_without_clashing_id = td_demo_content::add_post( array(
	'title' => 'Mixing and Matching Patterns Without Clashing',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_colors_and_patterns_id,),
));

$post_td_post_choosing_a_color_palette_tips_and_tricks_id = td_demo_content::add_post( array(
	'title' => 'Choosing a Color Palette: Tips and Tricks',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_colors_and_patterns_id,),
));

$post_td_post_the_elegance_of_traditional_design_a_timeless_approach_id = td_demo_content::add_post( array(
	'title' => 'The Elegance of Traditional Design: A Timeless Approach',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_design_styles_id,),
));

$post_td_post_coastal_vibes_creating_a_beach_inspired_home_id = td_demo_content::add_post( array(
	'title' => 'Coastal Vibes: Creating a Beach-Inspired Home',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_design_styles_id,),
));

$post_td_post_eclectic_interiors_mastering_the_mix_and_match_id = td_demo_content::add_post( array(
	'title' => 'Eclectic Interiors: Mastering the Mix-and-Match',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_design_styles_id,),
));

$post_td_post_contemporary_vs_modern_understanding_the_differences_id = td_demo_content::add_post( array(
	'title' => 'Contemporary vs. Modern: Understanding the Differences',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_design_styles_id,),
));

$post_td_post_country_and_rustic_charm_bringing_nature_indoors_id = td_demo_content::add_post( array(
	'title' => 'Country and Rustic Charm: Bringing Nature Indoors',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_design_styles_id,),
));

$post_td_post_art_deco_influence_in_modern_interiors_id = td_demo_content::add_post( array(
	'title' => 'Art Deco Influence in Modern Interiors',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_design_styles_id,),
));

$post_td_post_industrial_design_unfinished_rawness_meets_modern_living_id = td_demo_content::add_post( array(
	'title' => 'Industrial Design: Unfinished Rawness Meets Modern Living',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_design_styles_id,),
));

$post_td_post_minimalism_explained_the_art_of_less_is_more_id = td_demo_content::add_post( array(
	'title' => 'Minimalism Explained: The Art of Less is More',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_design_styles_id,),
));

$post_td_post_exploring_bohemian_chic_a_world_of_color_and_texture_id = td_demo_content::add_post( array(
	'title' => 'Exploring Bohemian Chic: A World of Color and Texture',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_design_styles_id,),
));

$post_td_post_scandinavian_style_simplicity_and_functionality_in_design_id = td_demo_content::add_post( array(
	'title' => 'Scandinavian Style: Simplicity and Functionality in Design',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_design_styles_id,),
));

$post_td_post_the_lakeside_redesign_a_rustic_house_for_modern_living_id = td_demo_content::add_post( array(
	'title' => 'The Lakeside Redesign: a Rustic House for Modern Living',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_case_studies_id,),
));

$post_td_post_minimalist_magic_a_contemporary_studio_apartment_id = td_demo_content::add_post( array(
	'title' => 'Minimalist Magic: A Contemporary Studio Apartment',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_case_studies_id,),
));

$post_td_post_the_heritage_home_restoring_charm_in_a_victorian_house_id = td_demo_content::add_post( array(
	'title' => 'The Heritage Home: Restoring Charm in a Victorian House',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_case_studies_id,),
));

$post_td_post_the_downtown_penthouse_luxury_apartment_redesign_id = td_demo_content::add_post( array(
	'title' => 'The Downtown Penthouse: Luxury Apartment Redesign',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_case_studies_id,),
));

$post_td_post_eco_elegant_a_sustainable_house_renovation_project_id = td_demo_content::add_post( array(
	'title' => 'Eco-Elegant: A Sustainable House Renovation Project',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_case_studies_id,),
));

$post_td_post_the_family_haven_redesigning_a_spacious_country_house_id = td_demo_content::add_post( array(
	'title' => 'The Family Haven: Redesigning a Spacious Country House',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_case_studies_id,),
));

$post_td_post_studio_chic_maximizing_style_in_a_small_musicians_loft_id = td_demo_content::add_post( array(
	'title' => 'Studio Chic: Maximizing Style in a Small Musician\'s Loft',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_case_studies_id,),
));

$post_td_post_the_coastal_retreat_a_beachfront_house_renovation_id = td_demo_content::add_post( array(
	'title' => 'The Coastal Retreat: A Beachfront House Renovation',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_case_studies_id,),
));

$post_td_post_urban_oasis_transforming_a_compact_city_apartment_id = td_demo_content::add_post( array(
	'title' => 'Urban Oasis: Transforming a Compact City Apartment',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_case_studies_id,),
));

$post_td_post_the_modern_makeover_revitalizing_a_1960s_suburban_house_id = td_demo_content::add_post( array(
	'title' => 'The Modern Makeover: Revitalizing a 1960s Suburban House',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_case_studies_id,),
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
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_link( array(
	'title' => 'Space Planning',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_link( array(
	'title' => 'Room-Specific Design',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_link( array(
	'title' => 'Home Renovation',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_link( array(
	'title' => 'Furniture Selection',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_link( array(
	'title' => 'Lighting Design',
	'add_to_menu_id' => $menu_td_demo_custom_menu_id,
	'url' => '#',
	'parent_id' => ''
));




/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_page(array(
	'title' => 'About me',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'page_id' => $page_about_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_category( array(
	'title' => 'Case Studies',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'category_id' => $cat_case_studies_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_page(array(
	'title' => 'Services',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'page_id' => $page_services_id,
	'parent_id' => ''
));



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
	'title' => 'About me',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_about_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_page(array(
	'title' => 'Services',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_services_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_category( array(
	'title' => 'Case Studies',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_case_studies_id,
	'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_category( array(
	'title' => 'Blog',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_blog_id,
	'parent_id' => ''
));

$menu_item_5_id = td_demo_menus::add_page(array(
	'title' => 'Contact',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_contact_id,
	'parent_id' => ''
));


/*  ---------------------------------------------------------------------------- 
	CLOUD TEMPLATES
*/
$template_single_case_study_id = td_demo_content::add_cloud_template( array(
	'title' => 'Single Case Study',
	'file' => 'post_cloud_template_case_study.txt',
	'template_type' => 'single',
));

$template_category_case_studies_id = td_demo_content::add_cloud_template( array(
	'title' => 'Category Case Studies',
	'file' => 'cat_cloud_template_case_studies.txt',
	'template_type' => 'category',
));

td_demo_misc::update_individual_category_template( $cat_case_studies_id, 'tdb_template_' . $template_category_case_studies_id );


$template_tag_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Tag Template',
	'file' => 'tag_cloud_template.txt',
	'template_type' => 'tag',
));

td_demo_misc::update_global_tag_template( 'tdb_template_' . $template_tag_template_id );


$template_search_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Search Template',
	'file' => 'search_cloud_template.txt',
	'template_type' => 'search',
));

td_demo_misc::update_global_search_template( 'tdb_template_' . $template_search_template_id );


$template_date_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Date Template',
	'file' => 'date_cloud_template.txt',
	'template_type' => 'date',
));

td_demo_misc::update_global_date_template( 'tdb_template_' . $template_date_template_id );


$template_author_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Author Template',
	'file' => 'author_cloud_template.txt',
	'template_type' => 'author',
));

td_demo_misc::update_global_author_template( 'tdb_template_' . $template_author_template_id );


$template_single_post_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Single Post Template',
	'file' => 'post_cloud_template.txt',
	'template_type' => 'single',
));

td_util::update_option( 'td_default_site_post_template', 'tdb_template_' . $template_single_post_template_id );


$template_category_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Category Template',
	'file' => 'cat_cloud_template.txt',
	'template_type' => 'category',
));

td_demo_misc::update_global_category_template( 'tdb_template_' . $template_category_template_id );


$template_404_template_id = td_demo_content::add_cloud_template( array(
	'title' => '404 Template',
	'file' => '404_cloud_template.txt',
	'template_type' => '404',
));

td_demo_misc::update_global_404_template( 'tdb_template_' . $template_404_template_id );


$template_header_template_main_id = td_demo_content::add_cloud_template( array(
	'title' => 'Header Template Main',
	'file' => 'header_template_main_cloud_template.txt',
	'template_type' => 'header',
));

td_demo_misc::update_global_header_template( 'tdb_template_' . $template_header_template_main_id );


$template_footer_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Footer Template',
	'file' => 'footer_template_cloud_template.txt',
	'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_footer_template_id );


$template_header_template_id = td_demo_content::add_cloud_template( array(
	'title' => 'Header Template',
	'file' => 'header_template_cloud_template.txt',
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
td_demo_content::update_meta( $template_single_case_study_id, 'tdc_header_template_id', $template_header_template_id );

td_demo_content::update_meta( $template_tag_template_id, 'tdc_header_template_id', $template_header_template_main_id );

td_demo_content::update_meta( $template_tag_template_id, 'tdc_footer_template_id', $template_footer_template_id );

td_demo_content::update_meta( $template_search_template_id, 'tdc_header_template_id', $template_header_template_main_id );

td_demo_content::update_meta( $template_search_template_id, 'tdc_footer_template_id', $template_footer_template_id );

td_demo_content::update_meta( $template_date_template_id, 'tdc_header_template_id', $template_header_template_main_id );

td_demo_content::update_meta( $template_date_template_id, 'tdc_footer_template_id', $template_footer_template_id );

td_demo_content::update_meta( $template_author_template_id, 'tdc_header_template_id', $template_header_template_main_id );

td_demo_content::update_meta( $template_author_template_id, 'tdc_footer_template_id', $template_footer_template_id );

td_demo_content::update_meta( $template_single_post_template_id, 'tdc_header_template_id', $template_header_template_main_id );

td_demo_content::update_meta( $template_single_post_template_id, 'tdc_footer_template_id', $template_footer_template_id );

td_demo_content::update_meta( $template_category_template_id, 'tdc_header_template_id', $template_header_template_main_id );

td_demo_content::update_meta( $template_category_template_id, 'tdc_footer_template_id', $template_footer_template_id );

td_demo_content::update_meta( $template_404_template_id, 'tdc_header_template_id', $template_header_template_main_id );

td_demo_content::update_meta( $template_404_template_id, 'tdc_footer_template_id', $template_footer_template_id );

td_demo_content::update_meta( $template_header_template_main_id, 'tdc_header_template_id', $template_header_template_main_id );

td_demo_content::update_meta( $template_footer_template_id, 'tdc_footer_template_id', $template_footer_template_id );

td_demo_content::update_meta( $template_header_template_id, 'tdc_header_template_id', $template_header_template_id );

// pages metas
td_demo_content::update_meta( $page_contact_id, 'tdc_header_template_id', $template_header_template_main_id );

td_demo_content::update_meta( $page_contact_id, 'tdc_footer_template_id', $template_footer_template_id );

td_demo_content::update_meta( $page_services_id, 'tdc_header_template_id', $template_header_template_main_id );

td_demo_content::update_meta( $page_services_id, 'tdc_footer_template_id', $template_footer_template_id );

td_demo_content::update_meta( $page_about_id, 'tdc_header_template_id', $template_header_template_main_id );

td_demo_content::update_meta( $page_about_id, 'tdc_footer_template_id', $template_footer_template_id );

td_demo_content::update_meta( $page_home_id, 'tdc_header_template_id', $template_header_template_id );

td_demo_content::update_meta( $page_home_id, 'tdc_footer_template_id', $template_footer_template_id );
