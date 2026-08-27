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
	MENUS
*/
$menu_td_demo_header_menu_id = td_demo_menus::create_menu('td-demo-header-menu', '');

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
	POSTS
*/
$post_td_post_holiday_baking_traditions_sweet_treats_from_around_the_world_id = td_demo_content::add_post( array(
	'title' => 'Holiday Baking Traditions: Sweet Treats from Around the World',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_the_ultimate_guide_to_cooking_oils_when_and_how_to_use_them_id = td_demo_content::add_post( array(
	'title' => 'The Ultimate Guide to Cooking Oils: When and How to Use Them',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_breakfast_boost_energizing_recipes_to_kickstart_your_day_id = td_demo_content::add_post( array(
	'title' => 'Breakfast Boost: Energizing Recipes to Kickstart Your Day',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_sous_vide_cooking_achieving_restaurant_quality_results_at_home_id = td_demo_content::add_post( array(
	'title' => 'Sous Vide Cooking: Achieving Restaurant-Quality Results at Home',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_vegan_delights_tasty_and_satisfying_plant_based_meals_id = td_demo_content::add_post( array(
	'title' => 'Vegan Delights: Tasty and Satisfying Plant-Based Meals',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_sizzling_summer_bbq_top_recipes_for_outdoor_grilling_success_id = td_demo_content::add_post( array(
	'title' => 'Sizzling Summer BBQ: Top Recipes for Outdoor Grilling Success',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_the_art_of_fermentation_simple_steps_to_fermenting_at_home_id = td_demo_content::add_post( array(
	'title' => 'The Art of Fermentation: Simple Steps to Fermenting at Home',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_zero_waste_cooking_innovative_recipes_to_use_up_leftovers_id = td_demo_content::add_post( array(
	'title' => 'Zero Waste Cooking: Innovative Recipes to Use Up Leftovers',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_global_flavors_at_home_exploring_world_cuisines_in_your_kitchen_id = td_demo_content::add_post( array(
	'title' => 'Global Flavors at Home: Exploring World Cuisines in Your Kitchen',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_spice_up_your_life_how_to_use_spices_to_elevate_every_meal_id = td_demo_content::add_post( array(
	'title' => 'Spice Up Your Life: How to Use Spices to Elevate Every Meal',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_decadent_desserts_easy_recipes_for_chocolate_lovers_id = td_demo_content::add_post( array(
	'title' => 'Decadent Desserts: Easy Recipes for Chocolate Lovers',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_from_novice_to_chef_essential_cooking_skills_everyone_should_know_id = td_demo_content::add_post( array(
	'title' => 'From Novice to Chef: Essential Cooking Skills Everyone Should Know',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_seasonal_eats_delicious_recipes_to_make_the_most_of_fresh_produce_id = td_demo_content::add_post( array(
	'title' => 'Seasonal Eats: Delicious Recipes to Make the Most of Fresh Produce',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_mastering_sourdough_a_beginners_guide_to_baking_bread_at_home_id = td_demo_content::add_post( array(
	'title' => 'Mastering Sourdough: A Beginner’s Guide to Baking Bread at Home',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_effortless_entertaining_simple_yet_impressive_dinner_party_dishes_id = td_demo_content::add_post( array(
	'title' => 'Effortless Entertaining: Simple Yet Impressive Dinner Party Dishes',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_kitchen_hacks_5_ingenious_ways_to_make_cooking_easier_id = td_demo_content::add_post( array(
	'title' => 'Kitchen Hacks: 5 Ingenious Ways to Make Cooking Easier',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_secrets_to_flawless_pasta_from_boiling_to_saucing_id = td_demo_content::add_post( array(
	'title' => 'Secrets to Flawless Pasta: From Boiling to Saucing',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_the_ultimate_guide_to_meal_prep_save_time_and_eat_healthier_id = td_demo_content::add_post( array(
	'title' => 'The Ultimate Guide to Meal Prep: Save Time and Eat Healthier',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_baking_101_essential_tips_for_perfect_cakes_and_cookies_every_time_id = td_demo_content::add_post( array(
	'title' => 'Baking 101: Essential Tips for Perfect Cakes and Cookies Every Time',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_blog_id,),
));

$post_td_post_10_must_try_quick_and_easy_dinner_recipes_for_busy_weeknights_id = td_demo_content::add_post( array(
	'title' => '10 Must-Try Quick and Easy Dinner Recipes for Busy Weeknights',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_blog_id,),
));


/*  ---------------------------------------------------------------------------- 
	PRODUCTS
*/

/*  ---------------------------------------------------------------------------- 
	TAXONOMIES
*/
$tax_term_overall_experience_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Overall Experience',
	'taxonomy' => 'tdc-review-criteria',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'The Overall experience you had dining.',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_staff_service_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Staff &amp; Service',
	'taxonomy' => 'tdc-review-criteria',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'How pleasant were the staff members treating you? Have the services provided met your expectations?',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_food_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Food',
	'taxonomy' => 'tdc-review-criteria',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'How was the food? How was your meal?',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_ambiance_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Ambiance',
	'taxonomy' => 'tdc-review-criteria',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => 'How was the overall environment and ambiance in the locale?',
	'filter_image' => '',
	'tax_term_meta' => array( 
		'tdb_filter_color' => '',
	),
));

$tax_term_hot_chocolate_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Hot Chocolate',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_milk_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Milk',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_cocoa_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Cocoa',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_whipped_cream_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Whipped Cream',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_sugar_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Sugar',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_guava_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Guava',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_lemon_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Lemon',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_banana_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Banana',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_strawberries_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Strawberries',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_syrup_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Syrup',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_tea_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Tea',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_water_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Water',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_tapioca_pearls_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Tapioca Pearls',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_orange_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Orange',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_apples_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Apples',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_coffee_beans_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Coffee Beans',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_soda_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Soda',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_absinthe_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Absinthe',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_ice_cubes_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Ice Cubes',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_vodka_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Vodka',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_rum_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Rum',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_whiskey_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Whiskey',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_brandy_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Brandy',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_gin_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Gin',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_lime_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Lime',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_cider_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Cider',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_white_wine_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'White Wine',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_red_wine_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Red Wine',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_beer_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Beer',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_tagliatelle_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Tagliatelle',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_ground_beef_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Ground Beef',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_ground_pork_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Ground Pork',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_ham_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Ham',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_tomato_paste_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Tomato Paste',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_carrot_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Carrot',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_smoked_salmon_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Smoked Salmon',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_sour_cream_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Sour Cream',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_peas_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Peas',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_parmigiano_regiano_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Parmigiano Regiano',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_butter_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Butter',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_spaghetti_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Spaghetti',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_broccoli_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Broccoli',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_zucchini_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Zucchini',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_asparagus_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Asparagus',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_green_beans_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Green Beans',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_mushrooms_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Mushrooms',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_hot_peppers_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Hot Peppers',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_tomato_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Tomato',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_shallots_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Shallots',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_parsley_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Parsley',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_black_pepper_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Black Pepper',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_olive_oil_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Olive Oil',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_garlic_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Garlic',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_penne_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Penne',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_fettuccine_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Fettuccine',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_salt_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Salt',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_pecorino_romano_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Pecorino Romano',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_eggs_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Eggs',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_bacon_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Bacon',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_potatoes_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Potatoes',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_emmentaler_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Emmentaler',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_onion_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Onion',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_vinegar_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Vinegar',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_mustard_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Mustard',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_chives_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Chives',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_beef_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Beef',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_eggplant_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Eggplant',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_tomato_sauce_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Tomato Sauce',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_bechamel_sauce_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Bechamel Sauce',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_chicken_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Chicken',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_chestnuts_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Chestnuts',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_ginger_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Ginger',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_scallions_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Scallions',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_dark_soy_sauce_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Dark Soy Sauce',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_light_soy_sauce_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Light Soy Sauce',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_veal_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Veal',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_flour_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Flour',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_rice_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Rice',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_shrimps_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Shrimps',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_sausage_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Sausage',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_celery_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Celery',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_oregano_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Oregano',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_duck_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Duck',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_bay_leaves_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Bay Leaves',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_stock_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Stock',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_clams_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Clams',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_squid_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Squid',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_mussels_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Mussels',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_bell_pepper_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Bell Pepper',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_cauliflower_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Cauliflower',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_beans_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Beans',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_lard_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Lard',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_beet_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Beet',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_beet_kvass_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Beet Kvass',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_cabbage_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Cabbage',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_noodles_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Noodles',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_thyme_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Thyme',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_bread_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Bread',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_white_beans_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'White Beans',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_chard_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Chard',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_parsnip_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Parsnip',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_peppers_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Peppers',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_croutons_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Croutons',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_pork_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Pork',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_mozzarella_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Mozzarella',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_breadcrumbs_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Breadcrumbs',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_avocado_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Avocado',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_coriander_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Coriander',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_sea_bass_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Sea Bass',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_flounder_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Flounder',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_cumin_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Cumin',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_chickpeas_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Chickpeas',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_mayonnaise_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Mayonnaise',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_worcestshire_sauce_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Worcestshire Sauce',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_lettuce_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Lettuce',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_dill_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Dill',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_basil_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Basil',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_offal_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Offal',
	'taxonomy' => 'tdtax_ingredients_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_non_alcoholic_beverages_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Non-Alcoholic Beverages',
	'taxonomy' => 'tdtax_menu_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_alcoholic_beverages_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Alcoholic Beverages',
	'taxonomy' => 'tdtax_menu_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_pasta_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Pasta',
	'taxonomy' => 'tdtax_menu_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_main_dishes_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Main Dishes',
	'taxonomy' => 'tdtax_menu_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_soups_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Soups',
	'taxonomy' => 'tdtax_menu_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));

$tax_term_appetizers_id = td_demo_tax::add_taxonomy( array(
	'taxonomy_name' => 'Appetizers',
	'taxonomy' => 'tdtax_menu_list',
	'taxonomy_template' => '',
	'parent_id' => 0,
	'description' => '',
));


/*  ---------------------------------------------------------------------------- 
	CPTs
*/
$cpt_hot_chocolate_id = td_demo_content::add_cpt( array(
	'title' => 'Hot Chocolate',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_item_price' => 'MTU=',
		'tdcf_weight_of_item' => 'MjUwZw==',
		'tdcf_menu_number' => 'NjA=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_cocoa_id, $tax_term_milk_id, $tax_term_sugar_id, $tax_term_whipped_cream_id ),
		'tdtax_menu_list' => array( $tax_term_non_alcoholic_beverages_id ),
	),
));

$cpt_smoothie_id = td_demo_content::add_cpt( array(
	'title' => 'Smoothie',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzA=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'NTk=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_banana_id, $tax_term_guava_id, $tax_term_lemon_id, $tax_term_strawberries_id ),
		'tdtax_menu_list' => array( $tax_term_non_alcoholic_beverages_id ),
	),
));

$cpt_milkshake_id = td_demo_content::add_cpt( array(
	'title' => 'Milkshake',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzU=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'NTg=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_banana_id, $tax_term_milk_id, $tax_term_strawberries_id ),
		'tdtax_menu_list' => array( $tax_term_non_alcoholic_beverages_id ),
	),
));

$cpt_bubble_tea_id = td_demo_content::add_cpt( array(
	'title' => 'Bubble Tea',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzU=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'NTc=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_syrup_id, $tax_term_tapioca_pearls_id, $tax_term_tea_id, $tax_term_water_id ),
		'tdtax_menu_list' => array( $tax_term_non_alcoholic_beverages_id ),
	),
));

$cpt_freshly_squeezed_juice_id = td_demo_content::add_cpt( array(
	'title' => 'Freshly Squeezed Juice',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_item_price' => 'MjA=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'NTY=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_apples_id, $tax_term_banana_id, $tax_term_orange_id, $tax_term_strawberries_id ),
		'tdtax_menu_list' => array( $tax_term_non_alcoholic_beverages_id ),
	),
));

$cpt_tea_id = td_demo_content::add_cpt( array(
	'title' => 'Tea',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_6',
	'post_meta' => array( 
		'tdcf_item_price' => 'MTA=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'NTU=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_tea_id, $tax_term_water_id ),
		'tdtax_menu_list' => array( $tax_term_non_alcoholic_beverages_id ),
	),
));

$cpt_lemonade_id = td_demo_content::add_cpt( array(
	'title' => 'Lemonade',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_7',
	'post_meta' => array( 
		'tdcf_item_price' => 'MTU=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'NTQ=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_lemon_id, $tax_term_sugar_id, $tax_term_water_id ),
		'tdtax_menu_list' => array( $tax_term_non_alcoholic_beverages_id ),
	),
));

$cpt_bubbly_soda_id = td_demo_content::add_cpt( array(
	'title' => 'Bubbly Soda',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_8',
	'post_meta' => array( 
		'tdcf_item_price' => 'MjA=',
		'tdcf_weight_of_item' => 'MjAwZ2A=',
		'tdcf_menu_number' => 'NTM=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_soda_id ),
		'tdtax_menu_list' => array( $tax_term_non_alcoholic_beverages_id ),
	),
));

$cpt_water_id = td_demo_content::add_cpt( array(
	'title' => 'Water',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_9',
	'post_meta' => array( 
		'tdcf_item_price' => 'MTA=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'NTI=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_water_id ),
		'tdtax_menu_list' => array( $tax_term_non_alcoholic_beverages_id ),
	),
));

$cpt_coffee_id = td_demo_content::add_cpt( array(
	'title' => 'Coffee',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_10',
	'post_meta' => array( 
		'tdcf_item_price' => 'MTU=',
		'tdcf_weight_of_item' => 'MjUwZw==',
		'tdcf_menu_number' => 'NTE=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_coffee_beans_id, $tax_term_sugar_id, $tax_term_water_id ),
		'tdtax_menu_list' => array( $tax_term_non_alcoholic_beverages_id ),
	),
));

$cpt_absinthe_id = td_demo_content::add_cpt( array(
	'title' => 'Absinthe',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDA=',
		'tdcf_weight_of_item' => 'MjAwZw==',
		'tdcf_menu_number' => 'NTA=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_absinthe_id, $tax_term_ice_cubes_id ),
		'tdtax_menu_list' => array( $tax_term_alcoholic_beverages_id ),
	),
));

$cpt_vodka_id = td_demo_content::add_cpt( array(
	'title' => 'Vodka',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDU=',
		'tdcf_weight_of_item' => 'MjAwZw==',
		'tdcf_menu_number' => 'NDk=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_ice_cubes_id, $tax_term_vodka_id ),
		'tdtax_menu_list' => array( $tax_term_alcoholic_beverages_id ),
	),
));

$cpt_rum_id = td_demo_content::add_cpt( array(
	'title' => 'Rum',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDU=',
		'tdcf_weight_of_item' => 'MjAwZw==',
		'tdcf_menu_number' => 'NDg=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_ice_cubes_id, $tax_term_lemon_id, $tax_term_rum_id ),
		'tdtax_menu_list' => array( $tax_term_alcoholic_beverages_id ),
	),
));

$cpt_whiskey_id = td_demo_content::add_cpt( array(
	'title' => 'Whiskey',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDc=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'NDc=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_ice_cubes_id, $tax_term_whiskey_id ),
		'tdtax_menu_list' => array( $tax_term_alcoholic_beverages_id ),
	),
));

$cpt_brandy_id = td_demo_content::add_cpt( array(
	'title' => 'Brandy',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDU=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'NDY=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_brandy_id, $tax_term_ice_cubes_id ),
		'tdtax_menu_list' => array( $tax_term_alcoholic_beverages_id ),
	),
));

$cpt_gin_id = td_demo_content::add_cpt( array(
	'title' => 'Gin',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_6',
	'post_meta' => array( 
		'tdcf_item_price' => 'NjA=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'NDU=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_gin_id, $tax_term_ice_cubes_id, $tax_term_lime_id ),
		'tdtax_menu_list' => array( $tax_term_alcoholic_beverages_id ),
	),
));

$cpt_cider_id = td_demo_content::add_cpt( array(
	'title' => 'Cider',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_7',
	'post_meta' => array( 
		'tdcf_item_price' => 'MjU=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'NDQ=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_cider_id ),
		'tdtax_menu_list' => array( $tax_term_alcoholic_beverages_id ),
	),
));

$cpt_white_wine_id = td_demo_content::add_cpt( array(
	'title' => 'White Wine',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_8',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzA=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'NDM=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_white_wine_id ),
		'tdtax_menu_list' => array( $tax_term_alcoholic_beverages_id ),
	),
));

$cpt_red_wine_id = td_demo_content::add_cpt( array(
	'title' => 'Red Wine',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_9',
	'post_meta' => array( 
		'tdcf_item_price' => 'MjU=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'NDI=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_red_wine_id ),
		'tdtax_menu_list' => array( $tax_term_alcoholic_beverages_id ),
	),
));

$cpt_beer_id = td_demo_content::add_cpt( array(
	'title' => 'Beer',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_10',
	'post_meta' => array( 
		'tdcf_item_price' => 'MTU=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'NDE=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_beer_id ),
		'tdtax_menu_list' => array( $tax_term_alcoholic_beverages_id ),
	),
));

$cpt_tagliatelle_al_ragu_alla_bolognese_id = td_demo_content::add_cpt( array(
	'title' => 'Tagliatelle al ragu alla Bolognese',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzU=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'NDA=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_carrot_id, $tax_term_ground_beef_id, $tax_term_ground_pork_id, $tax_term_ham_id, $tax_term_tagliatelle_id, $tax_term_tomato_paste_id, $tax_term_white_wine_id ),
		'tdtax_menu_list' => array( $tax_term_pasta_id ),
	),
));

$cpt_tagliatelle_al_salmone_id = td_demo_content::add_cpt( array(
	'title' => 'Tagliatelle al Salmone',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzU=',
		'tdcf_weight_of_item' => 'MzUwZw==',
		'tdcf_menu_number' => 'Mzk=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_peas_id, $tax_term_smoked_salmon_id, $tax_term_sour_cream_id, $tax_term_tagliatelle_id, $tax_term_white_wine_id ),
		'tdtax_menu_list' => array( $tax_term_pasta_id ),
	),
));

$cpt_tagliatelle_con_prosciutto_id = td_demo_content::add_cpt( array(
	'title' => 'Tagliatelle col Prosciutto',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDA=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'Mzg=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_butter_id, $tax_term_ham_id, $tax_term_parmigiano_regiano_id, $tax_term_tagliatelle_id ),
		'tdtax_menu_list' => array( $tax_term_pasta_id ),
	),
));

$cpt_pasta_primavera_id = td_demo_content::add_cpt( array(
	'title' => 'Pasta Primavera',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzU=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'Mzc=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_asparagus_id, $tax_term_broccoli_id, $tax_term_green_beans_id, $tax_term_hot_peppers_id, $tax_term_mushrooms_id, $tax_term_spaghetti_id, $tax_term_tomato_id, $tax_term_zucchini_id ),
		'tdtax_menu_list' => array( $tax_term_pasta_id ),
	),
));

$cpt_tagliatelle_ai_funghi_id = td_demo_content::add_cpt( array(
	'title' => 'Tagliatelle ai Funghi',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDU=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'MzY=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_black_pepper_id, $tax_term_mushrooms_id, $tax_term_parsley_id, $tax_term_shallots_id, $tax_term_tagliatelle_id, $tax_term_white_wine_id ),
		'tdtax_menu_list' => array( $tax_term_pasta_id ),
	),
));

$cpt_penne_allarrabbiata_id = td_demo_content::add_cpt( array(
	'title' => 'Penne All\'Arrabbiata',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_6',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzU=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'MzU=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_garlic_id, $tax_term_hot_peppers_id, $tax_term_olive_oil_id, $tax_term_parsley_id, $tax_term_penne_id, $tax_term_tomato_id ),
		'tdtax_menu_list' => array( $tax_term_pasta_id ),
	),
));

$cpt_fettuccine_alfredo_id = td_demo_content::add_cpt( array(
	'title' => 'Fettuccine Alfredo',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_7',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzU=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'MzQ=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_butter_id, $tax_term_fettuccine_id, $tax_term_parmigiano_regiano_id, $tax_term_salt_id ),
		'tdtax_menu_list' => array( $tax_term_pasta_id ),
	),
));

$cpt_spaghetti_amatriciana_id = td_demo_content::add_cpt( array(
	'title' => 'Spaghetti Amatriciana',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_8',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzU=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'MzM=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_ham_id, $tax_term_hot_peppers_id, $tax_term_pecorino_romano_id, $tax_term_salt_id, $tax_term_spaghetti_id, $tax_term_white_wine_id ),
		'tdtax_menu_list' => array( $tax_term_pasta_id ),
	),
));

$cpt_spaghetti_carbonara_id = td_demo_content::add_cpt( array(
	'title' => 'Spaghetti Carbonara',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_9',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDU=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'MzI=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_black_pepper_id, $tax_term_eggs_id, $tax_term_ham_id, $tax_term_pecorino_romano_id, $tax_term_spaghetti_id ),
		'tdtax_menu_list' => array( $tax_term_pasta_id ),
	),
));

$cpt_cacio_e_pepe_id = td_demo_content::add_cpt( array(
	'title' => 'Cacio e Pepe',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_10',
	'post_meta' => array( 
		'tdcf_item_price' => 'MjU=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'MzE=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_black_pepper_id, $tax_term_pecorino_romano_id, $tax_term_spaghetti_id ),
		'tdtax_menu_list' => array( $tax_term_pasta_id ),
	),
));

$cpt_rakkot_krumpli_id = td_demo_content::add_cpt( array(
	'title' => 'Rakkot Krumpli',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDU=',
		'tdcf_weight_of_item' => 'NDUwZw==',
		'tdcf_menu_number' => 'MzA=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_bacon_id, $tax_term_eggs_id, $tax_term_potatoes_id, $tax_term_sour_cream_id ),
		'tdtax_menu_list' => array( $tax_term_main_dishes_id ),
	),
));

$cpt_risotto_ai_frutti_di_mare_id = td_demo_content::add_cpt( array(
	'title' => 'Risotto ai Frutti di Mare',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDU=',
		'tdcf_weight_of_item' => 'NDUwZw==',
		'tdcf_menu_number' => 'Mjk=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_butter_id, $tax_term_clams_id, $tax_term_garlic_id, $tax_term_mussels_id, $tax_term_rice_id, $tax_term_shrimps_id, $tax_term_squid_id ),
		'tdtax_menu_list' => array( $tax_term_main_dishes_id ),
	),
));

$cpt_risotto_ai_funghi_id = td_demo_content::add_cpt( array(
	'title' => 'Risotto ai Funghi',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDA=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'Mjg=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_butter_id, $tax_term_mushrooms_id, $tax_term_parmigiano_regiano_id, $tax_term_rice_id, $tax_term_shallots_id, $tax_term_stock_id, $tax_term_white_wine_id ),
		'tdtax_menu_list' => array( $tax_term_main_dishes_id ),
	),
));

$cpt_arroz_de_pato_id = td_demo_content::add_cpt( array(
	'title' => 'Arroz de Pato',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDA=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'Mjc=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_bay_leaves_id, $tax_term_carrot_id, $tax_term_duck_id, $tax_term_garlic_id, $tax_term_ham_id, $tax_term_onion_id, $tax_term_orange_id, $tax_term_rice_id ),
		'tdtax_menu_list' => array( $tax_term_main_dishes_id ),
	),
));

$cpt_jambalaya_id = td_demo_content::add_cpt( array(
	'title' => 'Jambalaya',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_item_price' => 'NjA=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'MjY=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_celery_id, $tax_term_chicken_id, $tax_term_onion_id, $tax_term_oregano_id, $tax_term_rice_id, $tax_term_sausage_id, $tax_term_shrimps_id, $tax_term_tomato_id ),
		'tdtax_menu_list' => array( $tax_term_main_dishes_id ),
	),
));

$cpt_ossobuco_alla_milanese_id = td_demo_content::add_cpt( array(
	'title' => 'Ossobuco alla Milanese',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_6',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDU=',
		'tdcf_weight_of_item' => 'NDUwZw==',
		'tdcf_menu_number' => 'MjU=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_butter_id, $tax_term_flour_id, $tax_term_garlic_id, $tax_term_lemon_id, $tax_term_onion_id, $tax_term_parsley_id, $tax_term_veal_id, $tax_term_white_wine_id ),
		'tdtax_menu_list' => array( $tax_term_main_dishes_id ),
	),
));

$cpt_stir_fried_spring_chicken_with_chestnuts_id = td_demo_content::add_cpt( array(
	'title' => 'Stir-fried Spring Chicken with chestnuts',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_7',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDU=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'MjQ=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_chestnuts_id, $tax_term_chicken_id, $tax_term_dark_soy_sauce_id, $tax_term_ginger_id, $tax_term_light_soy_sauce_id, $tax_term_onion_id, $tax_term_scallions_id, $tax_term_white_wine_id ),
		'tdtax_menu_list' => array( $tax_term_main_dishes_id ),
	),
));

$cpt_moussaka_id = td_demo_content::add_cpt( array(
	'title' => 'Moussaka',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_8',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDU=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'MjM=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_bechamel_sauce_id, $tax_term_beef_id, $tax_term_butter_id, $tax_term_eggplant_id, $tax_term_tomato_sauce_id ),
		'tdtax_menu_list' => array( $tax_term_main_dishes_id ),
	),
));

$cpt_potato_salad_id = td_demo_content::add_cpt( array(
	'title' => 'Potato Salad',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_9',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzU=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'MjI=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_black_pepper_id, $tax_term_chives_id, $tax_term_mustard_id, $tax_term_onion_id, $tax_term_potatoes_id, $tax_term_vinegar_id ),
		'tdtax_menu_list' => array( $tax_term_main_dishes_id ),
	),
));

$cpt_frittata_al_formaggio_id = td_demo_content::add_cpt( array(
	'title' => 'Frittata al formaggio',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_10',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzU=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'MjE=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_black_pepper_id, $tax_term_butter_id, $tax_term_eggs_id, $tax_term_emmentaler_id, $tax_term_parmigiano_regiano_id, $tax_term_salt_id ),
		'tdtax_menu_list' => array( $tax_term_main_dishes_id ),
	),
));

$cpt_beef_soup_id = td_demo_content::add_cpt( array(
	'title' => 'Beef Soup',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzU=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'MjA=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_beef_id, $tax_term_bell_pepper_id, $tax_term_carrot_id, $tax_term_cauliflower_id, $tax_term_celery_id, $tax_term_onion_id, $tax_term_potatoes_id ),
		'tdtax_menu_list' => array( $tax_term_soups_id ),
	),
));

$cpt_meatball_soup_id = td_demo_content::add_cpt( array(
	'title' => 'Meatball Soup',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDU=',
		'tdcf_weight_of_item' => 'NTAwZw==',
		'tdcf_menu_number' => 'MTk=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_carrot_id, $tax_term_celery_id, $tax_term_onion_id, $tax_term_parsley_id, $tax_term_peppers_id, $tax_term_pork_id, $tax_term_rice_id ),
		'tdtax_menu_list' => array( $tax_term_soups_id ),
	),
));

$cpt_tomato_soup_id = td_demo_content::add_cpt( array(
	'title' => 'Tomato Soup',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDU=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'MTg=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_croutons_id, $tax_term_eggs_id, $tax_term_flour_id, $tax_term_garlic_id, $tax_term_olive_oil_id, $tax_term_onion_id, $tax_term_tomato_id ),
		'tdtax_menu_list' => array( $tax_term_soups_id ),
	),
));

$cpt_chicken_soup_id = td_demo_content::add_cpt( array(
	'title' => 'Chicken Soup',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzU=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'MTc=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_carrot_id, $tax_term_chicken_id, $tax_term_onion_id, $tax_term_parsnip_id, $tax_term_peppers_id, $tax_term_tomato_id ),
		'tdtax_menu_list' => array( $tax_term_soups_id ),
	),
));

$cpt_ribollita_id = td_demo_content::add_cpt( array(
	'title' => 'Ribollita',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzU=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'MTY=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_bread_id, $tax_term_cabbage_id, $tax_term_chard_id, $tax_term_potatoes_id, $tax_term_thyme_id, $tax_term_tomato_paste_id, $tax_term_white_beans_id ),
		'tdtax_menu_list' => array( $tax_term_soups_id ),
	),
));

$cpt_potato_soup_id = td_demo_content::add_cpt( array(
	'title' => 'Potato Soup',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_6',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzU=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'MTU=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_black_pepper_id, $tax_term_olive_oil_id, $tax_term_onion_id, $tax_term_potatoes_id, $tax_term_salt_id, $tax_term_stock_id, $tax_term_thyme_id ),
		'tdtax_menu_list' => array( $tax_term_soups_id ),
	),
));

$cpt_rosol_id = td_demo_content::add_cpt( array(
	'title' => 'Rosol',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_7',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzU=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'MTQ=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_bay_leaves_id, $tax_term_celery_id, $tax_term_chicken_id, $tax_term_garlic_id, $tax_term_noodles_id, $tax_term_onion_id, $tax_term_parsley_id ),
		'tdtax_menu_list' => array( $tax_term_soups_id ),
	),
));

$cpt_new_england_clam_chowder_id = td_demo_content::add_cpt( array(
	'title' => 'New England Clam Chowder',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_8',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzU=',
		'tdcf_weight_of_item' => 'NTAwZw==',
		'tdcf_menu_number' => 'MTM=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_bacon_id, $tax_term_butter_id, $tax_term_celery_id, $tax_term_clams_id, $tax_term_milk_id, $tax_term_onion_id, $tax_term_potatoes_id, $tax_term_salt_id ),
		'tdtax_menu_list' => array( $tax_term_soups_id ),
	),
));

$cpt_borscht_id = td_demo_content::add_cpt( array(
	'title' => 'Borscht',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_9',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDU=',
		'tdcf_weight_of_item' => 'NDUwZw==',
		'tdcf_menu_number' => 'MTI=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_beet_id, $tax_term_beet_kvass_id, $tax_term_cabbage_id, $tax_term_carrot_id, $tax_term_celery_id, $tax_term_onion_id, $tax_term_potatoes_id, $tax_term_stock_id ),
		'tdtax_menu_list' => array( $tax_term_soups_id ),
	),
));

$cpt_minestrone_id = td_demo_content::add_cpt( array(
	'title' => 'Minestrone',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_10',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDA=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'MTE=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_beans_id, $tax_term_carrot_id, $tax_term_celery_id, $tax_term_lard_id, $tax_term_onion_id, $tax_term_potatoes_id, $tax_term_rice_id ),
		'tdtax_menu_list' => array( $tax_term_soups_id ),
	),
));

$cpt_mozzarella_sticks_id = td_demo_content::add_cpt( array(
	'title' => 'Mozzarella Sticks',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_1',
	'post_meta' => array( 
		'tdcf_item_price' => 'MTU=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'MTA=',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_breadcrumbs_id, $tax_term_eggs_id, $tax_term_flour_id, $tax_term_mozzarella_id, $tax_term_olive_oil_id ),
		'tdtax_menu_list' => array( $tax_term_appetizers_id ),
	),
));

$cpt_olivier_salad_id = td_demo_content::add_cpt( array(
	'title' => 'Olivier Salad',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_2',
	'post_meta' => array( 
		'tdcf_item_price' => 'MTU=',
		'tdcf_weight_of_item' => 'MzUwZw==',
		'tdcf_menu_number' => 'OQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_dill_id, $tax_term_eggs_id, $tax_term_mayonnaise_id, $tax_term_peas_id, $tax_term_potatoes_id, $tax_term_scallions_id ),
		'tdtax_menu_list' => array( $tax_term_appetizers_id ),
	),
));

$cpt_caprese_salad_id = td_demo_content::add_cpt( array(
	'title' => 'Caprese Salad',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_3',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzU=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'OA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_basil_id, $tax_term_mozzarella_id, $tax_term_olive_oil_id, $tax_term_tomato_id ),
		'tdtax_menu_list' => array( $tax_term_appetizers_id ),
	),
));

$cpt_onion_rings_id = td_demo_content::add_cpt( array(
	'title' => 'Onion Rings',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_4',
	'post_meta' => array( 
		'tdcf_item_price' => 'MTI=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'Nw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_beer_id, $tax_term_eggs_id, $tax_term_flour_id, $tax_term_lard_id, $tax_term_milk_id, $tax_term_onion_id, $tax_term_salt_id, $tax_term_sour_cream_id ),
		'tdtax_menu_list' => array( $tax_term_appetizers_id ),
	),
));

$cpt_caesar_salad_id = td_demo_content::add_cpt( array(
	'title' => 'Caesar Salad',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_5',
	'post_meta' => array( 
		'tdcf_item_price' => 'MjU=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'Ng==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_croutons_id, $tax_term_eggs_id, $tax_term_garlic_id, $tax_term_lemon_id, $tax_term_lettuce_id, $tax_term_olive_oil_id, $tax_term_parmigiano_regiano_id, $tax_term_worcestshire_sauce_id ),
		'tdtax_menu_list' => array( $tax_term_appetizers_id ),
	),
));

$cpt_carpacio_id = td_demo_content::add_cpt( array(
	'title' => 'Carpacio',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_6',
	'post_meta' => array( 
		'tdcf_item_price' => 'MjA=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'NQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_beef_id, $tax_term_black_pepper_id, $tax_term_lemon_id, $tax_term_mayonnaise_id, $tax_term_milk_id, $tax_term_salt_id, $tax_term_worcestshire_sauce_id ),
		'tdtax_menu_list' => array( $tax_term_appetizers_id ),
	),
));

$cpt_foie_gras_id = td_demo_content::add_cpt( array(
	'title' => 'Foie Gras',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_7',
	'post_meta' => array( 
		'tdcf_item_price' => 'MjU=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'NA==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_black_pepper_id, $tax_term_offal_id, $tax_term_salt_id, $tax_term_white_wine_id ),
		'tdtax_menu_list' => array( $tax_term_appetizers_id ),
	),
));

$cpt_falafel_id = td_demo_content::add_cpt( array(
	'title' => 'Falafel',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_8',
	'post_meta' => array( 
		'tdcf_item_price' => 'MjU=',
		'tdcf_weight_of_item' => 'MzAwZw==',
		'tdcf_menu_number' => 'Mw==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_chickpeas_id, $tax_term_coriander_id, $tax_term_cumin_id, $tax_term_flour_id, $tax_term_garlic_id, $tax_term_onion_id, $tax_term_parsley_id ),
		'tdtax_menu_list' => array( $tax_term_appetizers_id ),
	),
));

$cpt_ceviche_id = td_demo_content::add_cpt( array(
	'title' => 'Ceviche',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_9',
	'post_meta' => array( 
		'tdcf_item_price' => 'NDU=',
		'tdcf_weight_of_item' => 'NTAwZw==',
		'tdcf_menu_number' => 'Mg==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_flounder_id, $tax_term_lime_id, $tax_term_onion_id, $tax_term_sea_bass_id ),
		'tdtax_menu_list' => array( $tax_term_appetizers_id ),
	),
));

$cpt_guacamole_id = td_demo_content::add_cpt( array(
	'title' => 'Guacamole',
	'type' => 'tdcpt_menu',
	'file' => 'tdcpt_menu_default.txt',
	'cpt_image_td_id' => 'td_pic_10',
	'post_meta' => array( 
		'tdcf_item_price' => 'MzU=',
		'tdcf_weight_of_item' => 'NDAwZw==',
		'tdcf_menu_number' => 'MQ==',
	),
	'cpt_taxonomies' => array( 
		'tdtax_ingredients_list' => array( $tax_term_avocado_id, $tax_term_coriander_id, $tax_term_lime_id, $tax_term_onion_id, $tax_term_salt_id ),
		'tdtax_menu_list' => array( $tax_term_appetizers_id ),
	),
));




/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_page(array(
	'title' => 'About',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_about_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_page(array(
	'title' => 'Menu',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_menu_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_page(array(
	'title' => 'Reserve',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_reserve_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_mega_menu( array(
	'title' => 'Blog',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_blog_id,
	'parent_id' => ''
), true );

$menu_item_4_id = td_demo_menus::add_page(array(
	'title' => 'Contact',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'page_id' => $page_contact_id,
	'parent_id' => ''
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
