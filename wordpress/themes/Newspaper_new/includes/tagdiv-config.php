<?php

/**
 * Theme configuration
 *
 */

define("TD_THEME_NAME", "Newspaper");
define("TD_THEME_VERSION", "12.7.3");
define("TD_THEME_OPTIONS_NAME", "td_011");

//if no deploy mode is selected, we use the final deploy built
if (!defined('TD_DEPLOY_MODE')) {
    define("TD_DEPLOY_MODE", 'deploy');
}

$td_theme_version = get_transient( 'TD_THEME_VERSION' );
if ( false === $td_theme_version || TD_THEME_VERSION != $td_theme_version) {
   set_transient('TD_THEME_VERSION', TD_THEME_VERSION, 0);
   delete_transient('TD_CHECKED_LICENSE');
}

switch (TD_DEPLOY_MODE) {
    default:
        //deploy version - this is the version that we ship!
        define("TD_DEBUG_LIVE_THEME_STYLE", false);
        define("TD_DEBUG_IOS_REDIRECT", false);
        define("TD_DEBUG_USE_LESS", false);
        break;

    case 'dev':
        //dev version
        define("TD_DEBUG_LIVE_THEME_STYLE", true);
        define("TD_DEBUG_IOS_REDIRECT", false);
        define("TD_DEBUG_USE_LESS", true); //use less on dev
        break;

    case 'demo':
        //demo version
        define("TD_DEBUG_LIVE_THEME_STYLE", true);
		// remove themeforest iframe from ios devices on demo only!
		// not used anymore - live preview goes on select demos
        define("TD_DEBUG_IOS_REDIRECT", false);
        define("TD_DEBUG_USE_LESS", false);
        break;
}

do_action('td_config');

class tagdiv_config {

	/**
	 * setup the global theme specific variables
	 * @depends tagdiv_global
	 */
	static function on_tagdiv_global_after_config() {
//var_dump(defined('TD_COMPOSER') && td_util::get_option('tds_white_label'));
        $td_brand = ( defined('TD_WL_PLUGIN_DIR') && td_util::get_option('tds_white_label') !== '' ) ? td_util::get_wl_val('tds_wl_brand', 'tagDiv') : 'tagDiv';
		/**
		 * theme plugins
		 */
		tagdiv_global::$theme_plugins_for_info_list = array (
			array(
				'name' => 'Revolution Slider',
				'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/rev-slider.png',
				'text' => 'Build amazing slide presentations for your website with ease<br><a href="https://forum.tagdiv.com/how-to-install-revolution-slider-v5/" target="_blank">How to install v5</a>',
				'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
				'slug' => 'revslider',
				'td_show_in_theme_plugins' => false, // custom field used to show the plugin in theme's plugins list
			),
			//array(
			//	'name' => 'Visual Composer',
			//	'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/visual-composer.png',
			//	'text' => 'Customize your pages and posts with this popular page builder<br><a href="https://forum.tagdiv.com/how-to-use-visual-composer/" target="_blank">Read more</a>',
			//	'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
			//	'slug' => 'js_composer'
			//)
		);
		tagdiv_global::$theme_plugins_list = array(
			array(
				'name' => $td_brand . ' Composer', // The plugin name
				'slug' => 'td-composer', // The plugin slug (typically the folder name)
				'source' => 'https://cloud.tagdiv.com/td_plugins/td-composer/7cd248d7ca13c255207c3f8b916c3f00/td-composer.zip', // The plugin source
				'required' => true, // If false, the plugin is only 'recommended' instead of required
				'version' => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
				'force_activation' => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
				'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
				'external_url' => '', // If set, overrides default API URL and points to an external URL
				'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/td-composer.png',
				'text' => 'Create beautiful pages with this custom frontend drag and drop builder<br><a href="https://forum.tagdiv.com/tagdiv-composer-tutorial/" target="_blank">Read more</a>',
				'required_label' => 'required', //the text for required/recommended label - used also as a class for label bg color
				'td_activate' => false, // custom field used to activate the plugin
				'td_install' => false, // custom field used to install the plugin
				'td_class' => 'tdc_version_check', // class used to recognize the plugin is activated
				'td_install_in_welcome' => true, // custom field used to install/update/activate the plugin from theme welcome panel
				'td_show_in_theme_plugins' => true, // custom field used to show the plugin in theme's plugins list
                'td_demo_plugin' => false, // custom field used to show the plugin in theme's plugins list
            ),
			array(
				'name' => $td_brand . ' Cloud Library', // The plugin name
				'slug' => 'td-cloud-library', // The plugin slug (typically the folder name)
				'source' => 'https://cloud.tagdiv.com/td_plugins/td-cloud-library/6127d3b6131f900b2e62bb855b13dc3b/td-cloud-library.zip', // The plugin source
				'required' => true, // If false, the plugin is only 'recommended' instead of required
				'version' => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
				'force_activation' => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
				'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
				'external_url' => '', // If set, overrides default API URL and points to an external URL
				'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/cloud-templates.jpg',
				'text' => 'Access a huge collection of fully editable templates and elements',
				'required_label' => 'required', //the text for required/recommended label - used also as a class for label bg color
				'td_activate' => false, // custom field used to activate the plugin
				'td_install' => false, // custom field used to install the plugin
				'td_class' => 'tdb_version_check', // class used to recognize the plugin is activated
				'td_install_in_welcome' => true, // custom field used to install/update/activate the plugin from theme welcome panel
				'td_show_in_theme_plugins' => true, // custom field used to show the plugin in theme's plugins list
                'td_demo_plugin' => false, // custom field used to show the plugin in theme's plugins list
            ),
			array(
				'name' => $td_brand . ' Social Counter', // The plugin name
				'slug' => 'td-social-counter', // The plugin slug (typically the folder name)
				'source' => 'https://cloud.tagdiv.com/td_plugins/td-social-counter/d22b2a0838669bf1b54d37e889125e0c/td-social-counter.zip', // The plugin source
				'required' => true, // If false, the plugin is only 'recommended' instead of required
				'version' => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
				'force_activation' => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
				'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
				'external_url' => '', // If set, overrides default API URL and points to an external URL
				'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/social.png',
				'text' => 'Display your activity on social networks with style using this cool feature<br><a href="https://forum.tagdiv.com/tagdiv-social-counter-tutorial/" target="_blank">Read more</a>',
				'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
				'td_activate' => false, // custom field used to activate the plugin
				'td_install' => false, // custom field used to install the plugin
				'td_class' => 'td_social_counter_plugin', // class used to recognize the plugin is activated
				'td_install_in_welcome' => false, // custom field used to install/update/activate the plugin from theme welcome panel
				'td_show_in_theme_plugins' => true, // custom field used to show the plugin in theme's plugins list
                'td_demo_plugin' => false, // custom field used to show the plugin in theme's plugins list
            ),
			array(
				'name' => $td_brand . ' Newsletter', // The plugin name
				'slug' => 'td-newsletter', // The plugin slug (typically the folder name)
				'source' => 'https://cloud.tagdiv.com/td_plugins/td-newsletter/795e74d53223156068c4e29e39e0ed3e/td-newsletter.zip', // The plugin source
				'required' => false, // If false, the plugin is only 'recommended' instead of required
				'version' => '1.0.0', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
				'force_activation' => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
				'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
				'external_url' => '', // If set, overrides default API URL and points to an external URL
				'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/tagdiv-newsletter.png',
				'text' => 'Newsletter plugin, beautifully designed with over 8 styles',
				'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
				'td_activate' => false, // custom field used to activate the plugin
				'td_install' => false, // custom field used to install the plugin
				'td_class' => 'td_newsletter_version_check', // class used to recognize the plugin is activated
				'td_install_in_welcome' => false, // custom field used to install/update/activate the plugin from theme welcome panel
				'td_show_in_theme_plugins' => true, // custom field used to show the plugin in theme's plugins list
                'td_demo_plugin' => false, // custom field used to show the plugin in theme's plugins list
            ),
            array(
                'name' => $td_brand . ' Opt-In Builder', // The plugin name
                'slug' => 'td-subscription', // The plugin slug (typically the folder name)
                'source' => 'https://cloud.tagdiv.com/td_plugins/td-subscription/td-subscription.zip', // The plugin source
                'required' => false, // If false, the plugin is only 'recommended' instead of required
                'version' => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
                'force_activation' => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
                'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
                'external_url' => '', // If set, overrides default API URL and points to an external URL
                'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/tagdiv-optin.png',
                'text' => 'Generate leads & convert visitors to subscribers with opt-in content lockers',
                'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
                'td_activate' => false, // custom field used to activate the plugin
                'td_install' => false, // custom field used to install the plugin
                'td_class' => 'tds_version_check', // class used to recognize the plugin is activated
                'td_install_in_welcome' => false, // custom field used to install/update/activate the plugin from theme welcome panel
                'td_show_in_theme_plugins' => true, // custom field used to show the plugin in theme's plugins list
                'td_demo_plugin' => false, // custom field used to show the plugin in theme's plugins list
            ),
            array(
                'name' => $td_brand . ' Shop', // The plugin name
                'slug' => 'td-woo', // The plugin slug (typically the folder name)
                'source' => 'https://cloud.tagdiv.com/td_plugins/td-woo/ec3a32d3501ab8f8aa53cdde009f24fc/td-woo.zip', // The plugin source
                'required' => false, // If false, the plugin is only 'recommended' instead of required
                'version' => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
                'force_activation' => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
                'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
                'external_url' => '', // If set, overrides default API URL and points to an external URL
                'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/tagdiv-shop.png',
                'text' => 'Activate for super powers and features on your WooCommerce website',
                'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
                'td_activate' => false, // custom field used to activate the plugin
                'td_install' => false, // custom field used to install the plugin
                'td_class' => 'td_woo_version_check', // class used to recognize the plugin is activated
                'td_install_in_welcome' => false, // custom field used to install/update/activate the plugin from theme welcome panel
                'td_show_in_theme_plugins' => true, // custom field used to show the plugin in theme's plugins list
                'td_demo_plugin' => false, // custom field used to show the plugin in theme's plugins list
            ),
			array(
				'name' => $td_brand . ' Mobile Theme', // The plugin name
				'slug' => 'td-mobile-plugin', // The plugin slug (typically the folder name)
				'source' => 'https://cloud.tagdiv.com/td_plugins/td-mobile-plugin/cca29e6e1f21dd83ab5a5f38860d1fd9/td-mobile-plugin.zip', // The plugin source
				'required' => false, // If false, the plugin is only 'recommended' instead of required
				'version' => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
				'force_activation' => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
				'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
				'external_url' => '', // If set, overrides default API URL and points to an external URL
				'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/mobile.png',
				'text' => 'Make your website lighter and faster on all mobile devices<br><a href="https://forum.tagdiv.com/the-mobile-theme/" target="_blank">Read more</a>',
				'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
				'td_activate' => false, // custom field used to activate the plugin
				'td_install' => false, // custom field used to install the plugin
				'td_class' => 'td_mobile_theme', // class used to recognize the plugin is activated
				'td_install_in_welcome' => false, // custom field used to install/update/activate the plugin from theme welcome panel
				'td_show_in_theme_plugins' => true, // custom field used to show the plugin in theme's plugins list
                'td_demo_plugin' => false, // custom field used to show the plugin in theme's plugins list
            ),
			array(
				'name' => 'Official AMP for WP', // The plugin name
				'slug' => 'amp', // The plugin slug (typically the folder name)
				'required' => false, // If false, the plugin is only 'recommended' instead of required
				'version' => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
				'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/tagdiv-amp.png',
				'text' => 'Mobile Theme requires the AMP plugin to give your site the best results<br><a href="https://tagdiv.com/amp-newspaper-theme/" target="_blank">Read more</a>',
				'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
				'td_class' => 'AMP_Autoloader', // class used to recognize the plugin is activated
				'td_install_in_welcome' => false, // custom field used to install/update/activate the plugin from theme welcome panel
				'td_show_in_theme_plugins' => true, // custom field used to show the plugin in theme's plugins list
                'td_demo_plugin' => false, // custom field used to show the plugin in theme's plugins list
            ),
            array(
                'name' => $td_brand . ' Standard Pack', // The plugin name
                'slug' => 'td-standard-pack', // The plugin slug (typically the folder name)
                'source' => 'https://cloud.tagdiv.com/td_plugins/td-standard-pack/c12e6da63ed2f212e87e44e5e9b9a302/td-standard-pack.zip', // The plugin source
                'required' => false, // If false, the plugin is only 'recommended' instead of required
                'version' => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
                'force_activation' => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
                'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
                'external_url' => '', // If set, overrides default API URL and points to an external URL
                'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/standard-pack.jpg',
                'text' => 'Build your website fast and effortless without code. Perfect for beginners',
                'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
                'td_activate' => false, // custom field used to activate the plugin
                'td_install' => false, // custom field used to install the plugin
                'td_class' => 'tdsp_version_check', // class used to recognize the plugin is activated
                'td_install_in_welcome' => false, // custom field used to install/update/activate the plugin from theme welcome panel
                'td_show_in_theme_plugins' => true, // custom field used to show the plugin in theme's plugins list
                'td_demo_plugin' => false, // custom field used to show the plugin in theme's plugins list
            ),
			array(
				'name' => 'WooCommerce', // The plugin name
				'slug' => 'woocommerce', // The plugin slug (typically the folder name)
				'required' => false, // If false, the plugin is only 'recommended' instead of required
				'version' => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
				'img' => '',
				'text' => 'Read more',
				'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
				'td_class' => 'WooCommerce', // class used to recognize the plugin is activated
				'td_install_in_welcome' => false, // custom field used to install/update/activate the plugin from theme welcome panel
				'td_show_in_theme_plugins' => false, // custom field used to show the plugin in theme's plugins list
                'td_demo_plugin' => false, // custom field used to show the plugin in theme's plugins list
            ),
			array(
				'name' => 'Advanced Custom Fields', // The plugin name
				'slug' => 'advanced-custom-fields', // The plugin slug (typically the folder name)
				'required' => false, // If false, the plugin is only 'recommended' instead of required
				'version' => '6.1.0', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
				'img' => get_template_directory_uri() . '/images/no-thumb/medium_large.png',
				'text' => 'Read more',
				'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
				'td_class' => 'ACF', // class used to recognize the plugin is activated
				'td_install_in_welcome' => false, // custom field used to install/update/activate the plugin from theme welcome panel
				'td_show_in_theme_plugins' => false, // custom field used to show the plugin in theme's plugins list
                'td_demo_plugin' => false, // custom field used to show the plugin in theme's plugins list
            ),
			array(
				'name' => 'Real Estate PRO Plugin', // The plugin name
				'slug' => 'td-demo-real-estate-pro', // The plugin slug (typically the folder name)
				'source' => 'https://cloud.tagdiv.com/td-demo-plugins/real-estate-pro/td-demo-real-estate-pro.zip', // The plugin source
				'required' => false, // If false, the plugin is only 'recommended' instead of required
				'version' => '1.0.0', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
				'force_activation' => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
				'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
				'external_url' => '', // If set, overrides default API URL and points to an external URL
				'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/tagdiv-realestate.jpg',
				'text' => "$td_brand plugin with custom post types, taxonomies & custom fields and more for Real Estate PRO Demo.",
				'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
				'td_activate' => false, // custom field used to activate the plugin
				'td_install' => false, // custom field used to install the plugin
				'td_class' => 'td_real_estate_pro_demo_plugin', // class used to recognize the plugin is activated
				'td_install_in_welcome' => false, // custom field used to install/update/activate the plugin from theme welcome panel
				'td_show_in_theme_plugins' => false, // custom field used to show the plugin in theme's plugins list
				'td_demo_plugin' => true, // custom field used to show the plugin in theme's plugins list
			),
			array(
				'name' => 'Compass PRO Plugin', // The plugin name
				'slug' => 'td-demo-compass-pro', // The plugin slug (typically the folder name)
				'source' => 'https://cloud.tagdiv.com/td-demo-plugins/compass-pro/td-demo-compass-pro.zip', // The plugin source
				'required' => false, // If false, the plugin is only 'recommended' instead of required
				'version' => '1.0.0', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
				'force_activation' => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
				'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
				'external_url' => '', // If set, overrides default API URL and points to an external URL
				'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/tagdiv-compass.jpg',
				'text' => "$td_brand plugin with custom post types, taxonomies & custom fields and more for Compass PRO Demo.",
				'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
				'td_activate' => false, // custom field used to activate the plugin
				'td_install' => false, // custom field used to install the plugin
				'td_class' => 'td_compass_pro_demo_plugin', // class used to recognize the plugin is activated
				'td_install_in_welcome' => false, // custom field used to install/update/activate the plugin from theme welcome panel
				'td_show_in_theme_plugins' => false, // custom field used to show the plugin in theme's plugins list
				'td_demo_plugin' => true, // custom field used to show the plugin in theme's plugins list
			),
            array(
                'name' => 'Eastcoast Check PRO Plugin', // The plugin name
                'slug' => 'td-demo-eastcoast-check-pro', // The plugin slug (typically the folder name)
                'source' => 'https://cloud.tagdiv.com/td-demo-plugins/eastcoast-check-pro/td-demo-eastcoast-check-pro.zip', // The plugin source
                'required' => false, // If false, the plugin is only 'recommended' instead of required
                'version' => '1.0.0', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
                'force_activation' => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
                'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
                'external_url' => '', // If set, overrides default API URL and points to an external URL
                'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/tagdiv-eastcoast.jpg',
                'text' => "$td_brand plugin with custom post types, taxonomies & custom fields and more for EastCoast Check PRO Demo.",
                'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
                'td_activate' => false, // custom field used to activate the plugin
                'td_install' => false, // custom field used to install the plugin
                'td_class' => 'td_eastcoast_check_pro_demo_plugin', // class used to recognize the plugin is activated
                'td_install_in_welcome' => false, // custom field used to install/update/activate the plugin from theme welcome panel
                'td_show_in_theme_plugins' => false, // custom field used to show the plugin in theme's plugins list
                'td_demo_plugin' => true, // custom field used to show the plugin in theme's plugins list
            ),
            array(
                'name' => 'Doctors PRO Plugin', // The plugin name
                'slug' => 'td-demo-doctors-pro', // The plugin slug (typically the folder name)
                'source' => 'https://cloud.tagdiv.com/td-demo-plugins/doctors-pro/td-demo-doctors-pro.zip', // The plugin source
                'required' => false, // If false, the plugin is only 'recommended' instead of required
                'version' => '1.0.0', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
                'force_activation' => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
                'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
                'external_url' => '', // If set, overrides default API URL and points to an external URL
                'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/tagdiv-doctors.jpg',
                'text' => "$td_brand plugin with custom post types, taxonomies & custom fields and more for Doctors PRO Demo.",
                'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
                'td_activate' => false, // custom field used to activate the plugin
                'td_install' => false, // custom field used to install the plugin
                'td_class' => 'td_doctors_pro_demo_plugin', // class used to recognize the plugin is activated
                'td_install_in_welcome' => false, // custom field used to install/update/activate the plugin from theme welcome panel
                'td_show_in_theme_plugins' => false, // custom field used to show the plugin in theme's plugins list
                'td_demo_plugin' => true, // custom field used to show the plugin in theme's plugins list
            ),
            array(
                'name' => 'Job Hunt PRO Plugin', // The plugin name
                'slug' => 'td-demo-job-hunt-pro', // The plugin slug (typically the folder name)
                'source' => 'https://cloud.tagdiv.com/td-demo-plugins/job-hunt-pro/td-demo-job-hunt-pro.zip', // The plugin source
                'required' => false, // If false, the plugin is only 'recommended' instead of required
                'version' => '1.0.0', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
                'force_activation' => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
                'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
                'external_url' => '', // If set, overrides default API URL and points to an external URL
                'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/tagdiv-jobs.jpg',
                'text' => "$td_brand plugin with custom post types, taxonomies, custom fields and more for the Job Hunt PRO Demo.",
                'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
                'td_activate' => false, // custom field used to activate the plugin
                'td_install' => false, // custom field used to install the plugin
                'td_class' => 'td_job_hunt_pro_demo_plugin', // class used to recognize the plugin is activated
                'td_install_in_welcome' => false, // custom field used to install/update/activate the plugin from theme welcome panel
                'td_show_in_theme_plugins' => false, // custom field used to show the plugin in theme's plugins list
                'td_demo_plugin' => true, // custom field used to show the plugin in theme's plugins list
            ),
            array(
                'name' => 'Coaching PRO Plugin', // The plugin name
                'slug' => 'td-demo-coaching-pro', // The plugin slug (typically the folder name)
                'source' => 'https://cloud.tagdiv.com/td-demo-plugins/coaching-pro/td-demo-coaching-pro.zip', // The plugin source
                'required' => false, // If false, the plugin is only 'recommended' instead of required
                'version' => '1.0.0', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
                'force_activation' => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
                'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
                'external_url' => '', // If set, overrides default API URL and points to an external URL
                'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/tagdiv-coaching.jpg',
                'text' => "$td_brand plugin with custom post types, taxonomies, custom fields and more for Coaching PRO Demo.",
                'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
                'td_activate' => false, // custom field used to activate the plugin
                'td_install' => false, // custom field used to install the plugin
                'td_class' => 'td_coaching_pro_demo_plugin', // class used to recognize the plugin is activated
                'td_install_in_welcome' => false, // custom field used to install/update/activate the plugin from theme welcome panel
                'td_show_in_theme_plugins' => false, // custom field used to show the plugin in theme's plugins list
                'td_demo_plugin' => true, // custom field used to show the plugin in theme's plugins list
            ),
            array(
                'name' => 'Momentum PRO Plugin', // The plugin name
                'slug' => 'td-demo-momentum-pro', // The plugin slug (typically the folder name)
                'source' => 'https://cloud.tagdiv.com/td-demo-plugins/momentum-pro/td-demo-momentum-pro.zip', // The plugin source
                'required' => false, // If false, the plugin is only 'recommended' instead of required
                'version' => '1.0.0', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
                'force_activation' => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
                'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
                'external_url' => '', // If set, overrides default API URL and points to an external URL
                'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/tagdiv-momentum.jpg',
                'text' => "$td_brand plugin with custom post types, taxonomies, custom fields and more for Momentum PRO Demo.",
                'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
                'td_activate' => false, // custom field used to activate the plugin
                'td_install' => false, // custom field used to install the plugin
                'td_class' => 'td_momentum_pro_demo_plugin', // class used to recognize the plugin is activated
                'td_install_in_welcome' => false, // custom field used to install/update/activate the plugin from theme welcome panel
                'td_show_in_theme_plugins' => false, // custom field used to show the plugin in theme's plugins list
                'td_demo_plugin' => true, // custom field used to show the plugin in theme's plugins list
            ),
            array(
                'name' => 'Cali Sight Plugin', // The plugin name
                'slug' => 'td-demo-cali-sight-pro', // The plugin slug (typically the folder name)
                'source' => 'https://cloud.tagdiv.com/td-demo-plugins/cali-sight/td-demo-cali-sight-pro.zip', // The plugin source
                'required' => false, // If false, the plugin is only 'recommended' instead of required
                'version' => '1.0.0', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
                'force_activation' => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
                'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
                'external_url' => '', // If set, overrides default API URL and points to an external URL
                'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/tagdiv-cali.jpg',
                'text' => 'tagDiv plugin for demos with custom post types & taxonomies',
                'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
                'td_activate' => false, // custom field used to activate the plugin
                'td_install' => false, // custom field used to install the plugin
                'td_class' => 'td_cali_sight_demo_plugin', // class used to recognize the plugin is activated
                'td_install_in_welcome' => false, // custom field used to install/update/activate the plugin from theme welcome panel
                'td_show_in_theme_plugins' => false, // custom field used to show the plugin in theme's plugins list
                'td_demo_plugin' => true, // custom field used to show the plugin in theme's plugins list
            ),
            array(
                'name' => 'APP Find PRO Plugin', // The plugin name
                'slug' => 'td-demo-app-find-pro', // The plugin slug (typically the folder name)
                'source' => 'https://cloud.tagdiv.com/td-demo-plugins/app-find-pro/td-demo-app-find-pro.zip', // The plugin source
                'required' => false, // If false, the plugin is only 'recommended' instead of required
                'version' => '1.0.0', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
                'force_activation' => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
                'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
                'external_url' => '', // If set, overrides default API URL and points to an external URL
                'img' => get_template_directory_uri() . '/includes/wp-booster/wp-admin/images/plugins/tagdiv-jobs.jpg',
                'text' => "$td_brand plugin with custom post types, taxonomies, custom fields and more for the APP Find PRO Demo.",
                'required_label' => 'optional', //the text for required/recommended label - used also as a class for label bg color
                'td_activate' => false, // custom field used to activate the plugin
                'td_install' => false, // custom field used to install the plugin
                'td_class' => 'td_app_find_pro_demo_plugin', // class used to recognize the plugin is activated
                'td_install_in_welcome' => false, // custom field used to install/update/activate the plugin from theme welcome panel
                'td_show_in_theme_plugins' => false, // custom field used to show the plugin in theme's plugins list
                'td_demo_plugin' => true, // custom field used to show the plugin in theme's plugins list
            ),
		);
	}
}
