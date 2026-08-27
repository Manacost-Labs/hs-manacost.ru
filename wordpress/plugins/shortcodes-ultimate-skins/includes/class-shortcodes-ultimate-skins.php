<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization and hooks.
 *
 * @since        1.5.1
 * @package      Shortcodes_Ultimate_Skins
 * @subpackage   Shortcodes_Ultimate_Skins/includes
 */
final class Shortcodes_Ultimate_Skins {

	/**
	 * The path to the main plugin file.
	 *
	 * @since    1.5.1
	 * @access   private
	 * @var      string      $plugin_file   The path to the main plugin file.
	 */
	private $plugin_file;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.5.1
	 * @access   private
	 * @var      string      $plugin_version   The current version of the plugin.
	 */
	private $plugin_version;

	/**
	 * The path to the plugin folder.
	 *
	 * @since    1.5.1
	 * @access   private
	 * @var      string      $plugin_path   The path to the plugin folder.
	 */
	private $plugin_path;

	/**
	 * The URL of the plugin folder.
	 *
	 * @since    1.5.1
	 * @access   private
	 * @var      string    $plugin_url    The URL of the plugin folder.
	 */
	private $plugin_url;

	/**
	 * The text domain for i18n.
	 *
	 * @since    1.5.1
	 * @access   private
	 * @var      string    $textdomain    The text domain for i18n.
	 */
	private $textdomain;

	/**
	 * The ID of the add-on.
	 *
	 * @since    1.5.1
	 * @access   private
	 * @var      string    $addon_id   The ID of the add-on.
	 */
	private $addon_id;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since   1.5.1
	 * @param string  $plugin_file    The path to the main plugin file.
	 * @param string  $plugin_version The current version of the plugin.
	 */
	public function __construct( $plugin_file, $plugin_version ) {

		$this->plugin_file    = $plugin_file;
		$this->plugin_version = $plugin_version;
		$this->plugin_path    = plugin_dir_path( $plugin_file );
		$this->plugin_url     = plugin_dir_url( $plugin_file );
		$this->textdomain     = 'shortcodes-ultimate-skins';
		$this->addon_id       = 'additional-skins';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_common_hooks();
		$this->define_admin_hooks();

	}

	/**
	 * Load the required dependencies for the plugin.
	 *
	 * @since    1.5.1
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
		 * Include the class responsible for plugin settings.
		 */
		require_once $this->plugin_path . 'admin/class-shortcodes-ultimate-skins-settings.php';

		/**
		 * Include the class responsible for plugin upgrade procedures.
		 */
		require_once $this->plugin_path . 'includes/class-shortcodes-ultimate-skins-upgrade.php';

	}

	/**
	 * Define the locale for the plugin for internationalization.
	 *
	 * @since    1.5.1
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
	 * @since    1.5.1
	 * @access   private
	 */
	private function define_common_hooks() {

		/**
		 * Register scripts and stylesheets.
		 */
		add_action( 'su/assets/register', array( $this, 'register_assets' ) );

		/**
		 * Query skins css during shortcode parsing.
		 */
		add_filter( 'shortcode_atts_heading', array( $this, 'add_skin_css' ), 10, 4 );
		add_filter( 'shortcode_atts_tabs', array( $this, 'add_skin_css' ), 10, 4 );
		add_filter( 'shortcode_atts_spoiler', array( $this, 'add_skin_css' ), 10, 4 );
		add_filter( 'shortcode_atts_quote', array( $this, 'add_skin_css' ), 10, 4 );

	}

	/**
	 * Register all of the hooks related to the admin area functionality of the
	 * plugin.
	 *
	 * @since    1.5.1
	 * @access   private
	 */
	private function define_admin_hooks() {

		/**
		 * Run upgrade procedures.
		 */
		$upgrade = new Shortcodes_Ultimate_Skins_Upgrade( $this->plugin_file, $this->plugin_version );

		add_action( 'admin_init', array( $upgrade, 'upgrade' ) );

		/**
		 * Register new skins.
		 */
		add_filter( 'su/data/shortcodes', array( $this, 'register_skins' ) );

		/**
		 * The 'Activate license key' notice.
		 */
		$license_notice = new Shortcodes_Ultimate_Addon_License_Notice( $this->addon_id, $this->plugin_path . 'admin/partials/notices/license.php' );

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
		$settings = new Shortcodes_Ultimate_Skins_Settings( $this->plugin_file );

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
			'puc_request_info_query_args-shortcodes-ultimate-skins',
			array( $updater, 'disable_puc_plugins_arg' ),
			10,
			1
		);

		add_filter(
			'puc_pre_inject_update-shortcodes-ultimate-skins',
			array( $updater, 'validate_license_before_updating' )
		);

		add_action(
			'puc_manual_check_link-shortcodes-ultimate-skins',
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
	 * @since  1.5.1
	 */
	public function register_assets() {

		wp_register_style(
			'shortcodes-ultimate-skins',
			$this->plugin_url . 'includes/css/shortcodes.css',
			array( 'su-shortcodes' ),
			$this->plugin_version,
			'all'
		);

	}

	/**
	 * Inject skins css during shortcode parsing.
	 *
	 * @since  1.5.1
	 */
	public function add_skin_css( $out, $pairs, $atts, $shortcode ) {

		if ( isset( $out['style'] ) && 'default' !== $out['style'] ) {
			su_query_asset( 'css', 'shortcodes-ultimate-skins' );
		}

		return $out;

	}

	/**
	 * Register new skins.
	 *
	 * @since  1.5.1
	 * @param mixed   $shortcodes Shortcodes collection.
	 * @return mixed               Modified shortcodes collection.
	 */
	public function register_skins( $shortcodes ) {

		/**
		 * Heading skins.
		 */
		$shortcodes['heading']['atts']['style']['values']['modern-1-dark']      = sprintf( '%s 1: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Dark', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['modern-1-light']     = sprintf( '%s 1: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Light', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['modern-1-blue']      = sprintf( '%s 1: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Blue', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['modern-1-orange']    = sprintf( '%s 1: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Orange', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['modern-1-violet']    = sprintf( '%s 1: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Violet', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['modern-2-dark']      = sprintf( '%s 2: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Dark', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['modern-2-light']     = sprintf( '%s 2: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Light', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['modern-2-blue']      = sprintf( '%s 2: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Blue', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['modern-2-orange']    = sprintf( '%s 2: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Orange', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['modern-2-violet']    = sprintf( '%s 2: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Violet', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['line-dark']          = sprintf( '%s: %s', __( 'Line', 'shortcodes-ultimate-skins' ), __( 'Dark', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['line-light']         = sprintf( '%s: %s', __( 'Line', 'shortcodes-ultimate-skins' ), __( 'Light', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['line-blue']          = sprintf( '%s: %s', __( 'Line', 'shortcodes-ultimate-skins' ), __( 'Blue', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['line-orange']        = sprintf( '%s: %s', __( 'Line', 'shortcodes-ultimate-skins' ), __( 'Orange', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['line-violet']        = sprintf( '%s: %s', __( 'Line', 'shortcodes-ultimate-skins' ), __( 'Violet', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['dotted-line-dark']   = sprintf( '%s: %s', __( 'Dotted line', 'shortcodes-ultimate-skins' ), __( 'Dark', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['dotted-line-light']  = sprintf( '%s: %s', __( 'Dotted line', 'shortcodes-ultimate-skins' ), __( 'Light', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['dotted-line-blue']   = sprintf( '%s: %s', __( 'Dotted line', 'shortcodes-ultimate-skins' ), __( 'Blue', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['dotted-line-orange'] = sprintf( '%s: %s', __( 'Dotted line', 'shortcodes-ultimate-skins' ), __( 'Orange', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['dotted-line-violet'] = sprintf( '%s: %s', __( 'Dotted line', 'shortcodes-ultimate-skins' ), __( 'Violet', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['flat-dark']          = sprintf( '%s: %s', __( 'Flat', 'shortcodes-ultimate-skins' ), __( 'Dark', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['flat-light']         = sprintf( '%s: %s', __( 'Flat', 'shortcodes-ultimate-skins' ), __( 'Light', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['flat-blue']          = sprintf( '%s: %s', __( 'Flat', 'shortcodes-ultimate-skins' ), __( 'Blue', 'shortcodes-ultimate-skins' ) );
		$shortcodes['heading']['atts']['style']['values']['flat-green']         = sprintf( '%s: %s', __( 'Flat', 'shortcodes-ultimate-skins' ), __( 'Green', 'shortcodes-ultimate-skins' ) );

		/**
		 * Spoiler skins.
		 */
		$shortcodes['spoiler']['atts']['style']['values']['carbon']        = __( 'Carbon', 'shortcodes-ultimate-skins' );
		$shortcodes['spoiler']['atts']['style']['values']['sharp']         = __( 'Sharp', 'shortcodes-ultimate-skins' );
		$shortcodes['spoiler']['atts']['style']['values']['grid']          = __( 'Grid', 'shortcodes-ultimate-skins' );
		$shortcodes['spoiler']['atts']['style']['values']['wood']          = __( 'Wood', 'shortcodes-ultimate-skins' );
		$shortcodes['spoiler']['atts']['style']['values']['fabric']        = __( 'Fabric', 'shortcodes-ultimate-skins' );
		$shortcodes['spoiler']['atts']['style']['values']['modern-dark']   = sprintf( '%s: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Dark', 'shortcodes-ultimate-skins' ) );
		$shortcodes['spoiler']['atts']['style']['values']['modern-light']  = sprintf( '%s: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Light', 'shortcodes-ultimate-skins' ) );
		$shortcodes['spoiler']['atts']['style']['values']['modern-violet'] = sprintf( '%s: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Violet', 'shortcodes-ultimate-skins' ) );
		$shortcodes['spoiler']['atts']['style']['values']['modern-orange'] = sprintf( '%s: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Orange', 'shortcodes-ultimate-skins' ) );
		$shortcodes['spoiler']['atts']['style']['values']['glass-dark']    = sprintf( '%s: %s', __( 'Glass', 'shortcodes-ultimate-skins' ), __( 'Dark', 'shortcodes-ultimate-skins' ) );
		$shortcodes['spoiler']['atts']['style']['values']['glass-light']   = sprintf( '%s: %s', __( 'Glass', 'shortcodes-ultimate-skins' ), __( 'Light', 'shortcodes-ultimate-skins' ) );
		$shortcodes['spoiler']['atts']['style']['values']['glass-blue']    = sprintf( '%s: %s', __( 'Glass', 'shortcodes-ultimate-skins' ), __( 'Blue', 'shortcodes-ultimate-skins' ) );
		$shortcodes['spoiler']['atts']['style']['values']['glass-green']   = sprintf( '%s: %s', __( 'Glass', 'shortcodes-ultimate-skins' ), __( 'Green', 'shortcodes-ultimate-skins' ) );
		$shortcodes['spoiler']['atts']['style']['values']['glass-gold']    = sprintf( '%s: %s', __( 'Glass', 'shortcodes-ultimate-skins' ), __( 'Gold', 'shortcodes-ultimate-skins' ) );

		/**
		 * Tabs skins.
		 */
		$shortcodes['tabs']['atts']['style']['values']['carbon']        = __( 'Carbon', 'shortcodes-ultimate-skins' );
		$shortcodes['tabs']['atts']['style']['values']['sharp']         = __( 'Sharp', 'shortcodes-ultimate-skins' );
		$shortcodes['tabs']['atts']['style']['values']['grid']          = __( 'Grid', 'shortcodes-ultimate-skins' );
		$shortcodes['tabs']['atts']['style']['values']['wood']          = __( 'Wood', 'shortcodes-ultimate-skins' );
		$shortcodes['tabs']['atts']['style']['values']['fabric']        = __( 'Fabric', 'shortcodes-ultimate-skins' );
		$shortcodes['tabs']['atts']['style']['values']['modern-dark']   = sprintf( '%s: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Dark', 'shortcodes-ultimate-skins' ) );
		$shortcodes['tabs']['atts']['style']['values']['modern-light']  = sprintf( '%s: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Light', 'shortcodes-ultimate-skins' ) );
		$shortcodes['tabs']['atts']['style']['values']['modern-blue']   = sprintf( '%s: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Blue', 'shortcodes-ultimate-skins' ) );
		$shortcodes['tabs']['atts']['style']['values']['modern-orange'] = sprintf( '%s: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Orange', 'shortcodes-ultimate-skins' ) );
		$shortcodes['tabs']['atts']['style']['values']['flat-dark']     = sprintf( '%s: %s', __( 'Flat', 'shortcodes-ultimate-skins' ), __( 'Dark', 'shortcodes-ultimate-skins' ) );
		$shortcodes['tabs']['atts']['style']['values']['flat-light']    = sprintf( '%s: %s', __( 'Flat', 'shortcodes-ultimate-skins' ), __( 'Light', 'shortcodes-ultimate-skins' ) );
		$shortcodes['tabs']['atts']['style']['values']['flat-blue']     = sprintf( '%s: %s', __( 'Flat', 'shortcodes-ultimate-skins' ), __( 'Blue', 'shortcodes-ultimate-skins' ) );
		$shortcodes['tabs']['atts']['style']['values']['flat-green']    = sprintf( '%s: %s', __( 'Flat', 'shortcodes-ultimate-skins' ), __( 'Green', 'shortcodes-ultimate-skins' ) );

		/**
		 * Quote skins.
		 */
		$shortcodes['quote']['atts']['style']['values']['carbon']        = __( 'Carbon', 'shortcodes-ultimate-skins' );
		$shortcodes['quote']['atts']['style']['values']['sharp']         = __( 'Sharp', 'shortcodes-ultimate-skins' );
		$shortcodes['quote']['atts']['style']['values']['grid']          = __( 'Grid', 'shortcodes-ultimate-skins' );
		$shortcodes['quote']['atts']['style']['values']['wood']          = __( 'Wood', 'shortcodes-ultimate-skins' );
		$shortcodes['quote']['atts']['style']['values']['fabric']        = __( 'Fabric', 'shortcodes-ultimate-skins' );
		$shortcodes['quote']['atts']['style']['values']['modern-dark']   = sprintf( '%s: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Dark', 'shortcodes-ultimate-skins' ) );
		$shortcodes['quote']['atts']['style']['values']['modern-light']  = sprintf( '%s: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Light', 'shortcodes-ultimate-skins' ) );
		$shortcodes['quote']['atts']['style']['values']['modern-blue']   = sprintf( '%s: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Blue', 'shortcodes-ultimate-skins' ) );
		$shortcodes['quote']['atts']['style']['values']['modern-orange'] = sprintf( '%s: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Orange', 'shortcodes-ultimate-skins' ) );
		$shortcodes['quote']['atts']['style']['values']['modern-violet'] = sprintf( '%s: %s', __( 'Modern', 'shortcodes-ultimate-skins' ), __( 'Violet', 'shortcodes-ultimate-skins' ) );
		$shortcodes['quote']['atts']['style']['values']['flat-dark']     = sprintf( '%s: %s', __( 'Flat', 'shortcodes-ultimate-skins' ), __( 'Dark', 'shortcodes-ultimate-skins' ) );
		$shortcodes['quote']['atts']['style']['values']['flat-light']    = sprintf( '%s: %s', __( 'Flat', 'shortcodes-ultimate-skins' ), __( 'Light', 'shortcodes-ultimate-skins' ) );
		$shortcodes['quote']['atts']['style']['values']['flat-blue']     = sprintf( '%s: %s', __( 'Flat', 'shortcodes-ultimate-skins' ), __( 'Blue', 'shortcodes-ultimate-skins' ) );
		$shortcodes['quote']['atts']['style']['values']['flat-green']    = sprintf( '%s: %s', __( 'Flat', 'shortcodes-ultimate-skins' ), __( 'Green', 'shortcodes-ultimate-skins' ) );

		return $shortcodes;

	}

}
