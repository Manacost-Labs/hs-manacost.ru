<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization and hooks.
 *
 * @since        1.5.7
 * @package      Shortcodes_Ultimate_Extra
 * @subpackage   Shortcodes_Ultimate_Extra/includes
 */
final class Shortcodes_Ultimate_Extra {

	/**
	 * The path to the main plugin file.
	 *
	 * @since    1.5.7
	 * @access   private
	 * @var      string      $plugin_file   The path to the main plugin file.
	 */
	private $plugin_file;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.5.7
	 * @access   private
	 * @var      string      $plugin_version   The current version of the plugin.
	 */
	private $plugin_version;

	/**
	 * The path to the plugin folder.
	 *
	 * @since    1.5.7
	 * @access   private
	 * @var      string      $plugin_path   The path to the plugin folder.
	 */
	private $plugin_path;

	/**
	 * The URL of the plugin folder.
	 *
	 * @since    1.5.7
	 * @access   private
	 * @var      string    $plugin_url    The URL of the plugin folder.
	 */
	private $plugin_url;

	/**
	 * The text domain for i18n.
	 *
	 * @since    1.5.7
	 * @access   private
	 * @var      string    $textdomain    The text domain for i18n.
	 */
	private $textdomain;

	/**
	 * The ID of the add-on.
	 *
	 * @since    1.5.7
	 * @access   private
	 * @var      string    $addon_id   The ID of the add-on.
	 */
	private $addon_id;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since   1.5.7
	 * @param string  $plugin_file    The path to the main plugin file.
	 * @param string  $plugin_version The current version of the plugin.
	 */
	public function __construct( $plugin_file, $plugin_version ) {

		$this->plugin_file    = $plugin_file;
		$this->plugin_version = $plugin_version;
		$this->plugin_path    = plugin_dir_path( $plugin_file );
		$this->plugin_url     = plugin_dir_url( $plugin_file );
		$this->textdomain     = 'shortcodes-ultimate-extra';
		$this->addon_id       = 'extra-shortcodes';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_common_hooks();
		$this->define_admin_hooks();

	}

	/**
	 * Load the required dependencies for the plugin.
	 *
	 * @since    1.5.7
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		if ( ! class_exists( 'Shortcodes_Ultimate_Addon_i18n' ) ) {
			require_once $this->plugin_path . 'includes/class-shortcodes-ultimate-addon-i18n.php';
		}

		/**
		 * Classes responsible for displaying admin notices.
		 */
		if ( ! class_exists( 'Shortcodes_Ultimate_Addon_Notice' ) ) {
			require_once $this->plugin_path . 'admin/class-shortcodes-ultimate-addon-notice.php';
		}

		if ( ! class_exists( 'Shortcodes_Ultimate_Addon_License_Notice' ) ) {
			require_once $this->plugin_path . 'admin/class-shortcodes-ultimate-addon-license-notice.php';
		}

		if ( ! class_exists( 'Shortcodes_Ultimate_Addon_Core_Notice' ) ) {
			require_once $this->plugin_path . 'admin/class-shortcodes-ultimate-addon-core-notice.php';
		}

		/**
		 * The class responsible for the auto-updates functionality.
		 */
		if ( ! class_exists( 'Shortcodes_Ultimate_Addon_Updater' ) ) {
			require_once $this->plugin_path . 'admin/class-shortcodes-ultimate-addon-updater.php';
		}

		/**
		 * Functions.
		 */
		require_once $this->plugin_path . 'includes/functions.php';

		/**
		 * Static class containing shortcode handlers.
		 */
		if ( function_exists( 'su_add_shortcode' ) ) {
			require_once $this->plugin_path . 'includes/shortcodes/0-all.php';
		}

		/**
		 * Include the class responsible for plugin settings.
		 */
		require_once $this->plugin_path . 'admin/class-shortcodes-ultimate-extra-settings.php';

		/**
		 * Include the class responsible for plugin upgrade procedures.
		 */
		require_once $this->plugin_path . 'includes/class-shortcodes-ultimate-extra-upgrade.php';

	}

	/**
	 * Define the locale for the plugin for internationalization.
	 *
	 * @since    1.5.7
	 * @access   private
	 */
	private function set_locale() {

		$i18n = new Shortcodes_Ultimate_Addon_i18n( $this->plugin_file, $this->textdomain );

		$i18n->load_plugin_textdomain();

	}

	/**
	 * Register all of the hooks related to both admin and public areas of the
	 * site.
	 *
	 * @since    1.5.7
	 * @access   private
	 */
	private function define_common_hooks() {

		/**
		 * Register scripts and stylesheets.
		 */
		add_action( 'init', array( $this, 'register_assets' ) );

	}

	/**
	 * Register all of the hooks related to the admin area functionality of the
	 * plugin.
	 *
	 * @since    1.5.7
	 * @access   private
	 */
	private function define_admin_hooks() {

		/**
		 * Run upgrade procedures.
		 */
		$upgrade = new Shortcodes_Ultimate_Extra_Upgrade( $this->plugin_file, $this->plugin_version );

		add_action( 'admin_init', array( $upgrade, 'upgrade' ) );

		/**
		 * Register new shortcodes group.
		 */
		add_filter( 'su/data/groups', array( $this, 'register_group' ) );

		/**
		 * The 'Activate license key' notice.
		 */
		$license_notice = new Shortcodes_Ultimate_Addon_License_Notice(
			$this->addon_id,
			$this->plugin_path . 'admin/partials/notices/license.php'
		);

		add_action( 'admin_notices', array( $license_notice, 'display_notice' ) );
		add_action( 'admin_post_su_dismiss_notice', array( $license_notice, 'dismiss_notice' ) );

		/**
		 * The 'Install Core' notice.
		 */
		$core_notice = new Shortcodes_Ultimate_Addon_Core_Notice(
			$this->addon_id,
			$this->plugin_path . 'admin/partials/notices/core.php',
			'5.2.0'
		);

		add_action( 'admin_notices', array( $core_notice, 'display_notice' ) );
		add_action( 'admin_post_su_dismiss_notice', array( $core_notice, 'dismiss_notice' ) );

		/**
		 * Add plugin settings.
		 */
		$settings = new Shortcodes_Ultimate_Extra_Settings( $this->plugin_file );

		add_action( 'admin_init', array( $settings, 'register_settings' ) );
		add_action( 'current_screen', array( $settings, 'add_help_tab' ) );
		add_action( 'admin_enqueue_scripts', array( $settings, 'enqueue_scripts' ) );

		/**
		 * Configure updates.
		 */
		$updater = new Shortcodes_Ultimate_Addon_Updater(
			$this->plugin_file,
			$this->addon_id
		);

		add_filter(
			'puc_request_info_query_args-shortcodes-ultimate-extra',
			array( $updater, 'disable_puc_plugins_arg' ),
			10,
			1
		);

		add_filter(
			'puc_pre_inject_update-shortcodes-ultimate-extra',
			array( $updater, 'validate_license_before_updating' )
		);

		add_action(
			'puc_manual_check_link-shortcodes-ultimate-extra',
			'__return_empty_string'
		);

		add_filter(
			'plugin_action_links',
			array( $updater, 'activate_key_plugin_action_link' ),
			10,
			2
		);

	}

	/**
	 * Register add-on assets.
	 *
	 * @since  1.5.7
	 */
	public function register_assets() {

		wp_register_style(
			'shortcodes-ultimate-extra',
			plugins_url( 'css/shortcodes.css', __FILE__ ),
			false,
			filemtime( plugin_dir_path( __FILE__ ) . 'css/shortcodes.css' ),
			'all'
		);

		wp_register_script(
			'shortcodes-ultimate-extra',
			plugins_url( 'js/shortcodes/index.js', __FILE__ ),
			array( 'jquery' ),
			filemtime( plugin_dir_path( __FILE__ ) . 'js/shortcodes/index.js' ),
			true
		);

	}

	/**
	 * Register new shortcodes group.
	 *
	 * @since  1.5.7
	 * @param mixed   $groups Groups collection.
	 * @return mixed          Modified groups collection.
	 */
	public function register_group( $groups ) {

		$groups['extra'] = _x( 'Extra Shortcodes', 'Custom shortcodes group name', 'shortcodes-ultimate-extra' );

		return $groups;

	}

}
