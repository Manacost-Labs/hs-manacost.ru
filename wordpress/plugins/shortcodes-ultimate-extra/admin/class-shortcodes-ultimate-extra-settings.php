<?php

/**
 * The class responsible for add-on settings.
 *
 * @since        1.5.7
 * @package      Shortcodes_Ultimate_Extra
 * @subpackage   Shortcodes_Ultimate_Extra/admin
 */
final class Shortcodes_Ultimate_Extra_Settings {

	/**
	 * The path to the main plugin file.
	 *
	 * @since    1.5.7
	 * @access   private
	 * @var      string      $plugin_file   The path to the main plugin file.
	 */
	private $plugin_file;

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
	 * @access   protected
	 * @var      string      $plugin_url   The URL of the plugin folder.
	 */
	protected $plugin_url;

	/**
	 * The add-on ID.
	 *
	 * @since    1.5.7
	 * @access   private
	 * @var      string      $addon_id   The add-on ID.
	 */
	private $addon_id;

	/**
	 * The add-on name.
	 *
	 * @since    1.5.7
	 * @access   private
	 * @var      string      $addon_name   The add-on name.
	 */
	private $addon_name;

	/**
	 * The pattern to verify license keys.
	 *
	 * @since    1.5.7
	 * @access   private
	 * @var      string      $license_key_pattern   The pattern to verify license keys.
	 */
	private $license_key_pattern;

	/**
	 * The option name for license key.
	 *
	 * @since    1.5.7
	 * @access   private
	 * @var      string      $license_key_option   The option name for license key.
	 */
	private $license_key_option;

	/**
	 * The mask to hide actual license key at settings page.
	 *
	 * @since    1.5.8
	 * @access   private
	 * @var      string      $license_key_mask   The mask to hide actual license key at settings page.
	 */
	private $license_key_mask;

	/**
	 * License key (de)activation messages.
	 *
	 * @since    1.5.7
	 * @access   private
	 * @var      string      $license_key_activation_messages   License key (de)activation messages.
	 */
	private $license_key_activation_messages;

	/**
	 * Initialize the class.
	 *
	 * @since   1.5.7
	 * @param string  $plugin_file The path to the main plugin file.
	 */
	public function __construct( $plugin_file ) {

		$this->plugin_file = $plugin_file;
		$this->plugin_path = plugin_dir_path( $plugin_file );
		$this->plugin_url  = plugin_dir_url( $plugin_file );

		$this->addon_id   = 'extra-shortcodes';
		$this->addon_name = __( 'Extra Shortcodes', 'shortcodes-ultimate-extra' );

		$this->license_key_api_url = 'https://getshortcodes.com/api/v1';
		$this->license_key_pattern = '/^([A-Z0-9]{4}-){3}[A-Z0-9]{4}$/';
		$this->license_key_option  = "su_option_{$this->addon_id}_license";
		$this->license_key_mask    = '****-****-****-%s';

		$this->license_key_activation_messages = array(

			// Activation messages
			'activated-202'             => __( 'License key successfully activated', 'shortcodes-ultimate-extra' ),
			'not-activated-400'         => __( 'Invalid license key', 'shortcodes-ultimate-extra' ),
			'not-activated-409'         => __( 'Invalid license key', 'shortcodes-ultimate-extra' ),
			'not-activated-500'         => __( 'Invalid license key', 'shortcodes-ultimate-extra' ),
			'not-activated-limit-403'   => __( 'License key reached it\'s activation limit. Deactivate this license key on other sites', 'shortcodes-ultimate-extra' ),
			'not-activated-product-403' => __( 'This license key is intended to use with another add-on', 'shortcodes-ultimate-extra' ),
			'not-activated-503'         => __( 'Unable to connect to activation server, please try again later', 'shortcodes-ultimate-extra' ),
			'not-activated-001'         => __( 'Unknown error', 'shortcodes-ultimate-extra' ),

			// Deactivation messages
			'deactivated-202'           => __( 'License key successfully deactivated', 'shortcodes-ultimate-extra' ),
			'not-deactivated-400'       => __( 'Invalid license key', 'shortcodes-ultimate-extra' ),
			'not-deactivated-409'       => __( 'Invalid license key', 'shortcodes-ultimate-extra' ),
			'not-deactivated-500'       => __( 'Invalid license key', 'shortcodes-ultimate-extra' ),
			'not-deactivated-503'       => __( 'Unable to connect to activation server, please try again later', 'shortcodes-ultimate-extra' ),
			'not-deactivated-001'       => __( 'Unknown error', 'shortcodes-ultimate-extra' ),

		);

	}

	/**
	 * Register plugin settings.
	 *
	 * @since 1.5.7
	 */
	public function register_settings() {

		add_settings_section(
			'shortcodes-ultimate-' . $this->addon_id . '-general',
			false,
			array( $this, 'display_settings_section' ),
			'shortcodes-ultimate-settings'
		);

		add_settings_field(
			$this->license_key_option,
			__( 'License key', 'shortcodes-ultimate-extra' ),
			array( $this, 'display_settings_field' ),
			'shortcodes-ultimate-settings',
			'shortcodes-ultimate-' . $this->addon_id . '-general',
			array(
				'id'   => $this->license_key_option,
				'type' => 'license-key',
			)
		);

		register_setting(
			'shortcodes-ultimate',
			$this->license_key_option,
			array( $this, 'sanitize_license_key' )
		);

	}

	/**
	 * Add help tab and set help sidebar at Add-ons page.
	 *
	 * @since  1.5.7
	 * @param WP_Screen $screen WP_Screen instance.
	 */
	public function add_help_tab( $screen ) {

		if ( ! $this->is_settings_page() ) {
			return;
		}

		$screen->add_help_tab(
			array(
				'id'      => 'shortcodes-ultimate-' . $this->addon_id,
				'title'   => $this->addon_name,
				'content' => $this->get_template( 'help/general' ),
			)
		);

	}

	public function enqueue_scripts() {

		if ( ! $this->is_settings_page() ) {
			return;
		}

		wp_enqueue_style(
			'shortcodes-ultimate-extra-settings',
			plugins_url( 'css/settings.css', __FILE__ ),
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . 'css/settings.css' ),
			'all'
		);

		wp_enqueue_script(
			'shortcodes-ultimate-extra-settings',
			plugins_url( 'js/settings/index.js', __FILE__ ),
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . 'js/settings/index.js' ),
			true
		);

	}

	/**
	 * Display settings section.
	 *
	 * @since  1.5.7
	 * @param mixed   $args The field data.
	 */
	public function display_settings_section( $args ) {

		$section = str_replace( 'shortcodes-ultimate-' . $this->addon_id . '-', '', $args['id'] );

		$this->the_template( 'settings/sections/' . $section, $args );

	}

	/**
	 * Display settings field.
	 *
	 * @since  1.5.7
	 * @param mixed   $args The field data.
	 */
	public function display_settings_field( $args ) {
		$this->the_template( 'settings/fields/' . $args['type'], $args );
	}

	/**
	 * Sanitize license key.
	 *
	 * Activate/deactivate license key depending on submitted value.
	 *
	 * @since  1.5.7
	 * @param string  $value License key.
	 * @return string        Sanitized value.
	 */
	public function sanitize_license_key( $value ) {

		$value      = trim( $value );
		$prev_value = get_option( $this->license_key_option, '' );

		// Value is not changed - do nothing
		if (
			$value === $prev_value ||
			$value === $this->mask_license_key( $prev_value )
		) {
			return $prev_value;
		}

		// Deactivation

		if ( empty( $value ) ) {

			$status          = $this->remote_deactivate_license_key( $prev_value );
			$message         = $this->get_license_key_activation_message( $status, false );
			$message['type'] = 'updated';

			// Not deactivated
			if ( 'deactivated-202' !== $status ) {

				$message['text'] .= sprintf(
					'&nbsp;&nbsp;<code><small>%s: %s</small></code>',
					__( 'error code', 'shortcodes-ultimate-extra' ),
					$message['id']
				);

				$message['type'] = 'error';
				$value           = $prev_value;

			}

			add_settings_error(
				$this->license_key_option,
				$this->license_key_option,
				sprintf( '%s: %s', $this->addon_name, $message['text'] ),
				$message['type']
			);

			return $value;

		}

		// Activation

		$status          = $this->remote_activate_license_key( $value );
		$message         = $this->get_license_key_activation_message( $status );
		$message['type'] = 'updated';

		// Not activated
		if ( 'activated-202' !== $status ) {

			$message['text'] .= sprintf(
				'&nbsp;&nbsp;<code><small>%s: %s</small></code>',
				__( 'error code', 'shortcodes-ultimate-extra' ),
				$message['id']
			);

			$message['type'] = 'error';
			$value           = $prev_value;

		}

		add_settings_error(
			$this->license_key_option,
			$this->license_key_option,
			sprintf( '%s: %s', $this->addon_name, $message['text'] ),
			$message['type']
		);

		return $value;

	}

	/**
	 * Activate license key remotely.
	 *
	 * @since  1.5.7
	 * @access private
	 * @param string  $key License key to be activated.
	 * @return string      Error code.
	 */
	private function remote_activate_license_key( $key ) {

		if ( ! $this->is_valid_license_key( $key ) ) {
			return 'not-activated-409';
		}

		$response = wp_remote_post(
			$this->license_key_api_url . '/activate-license-key',
			array(
				'timeout' => 10,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'site'    => $this->get_domain_name(),
						'key'     => $key,
						'product' => $this->addon_id,
					)
				),
			)
		);

		$response = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $response['code'] ) ) {
			return 'not-activated-503';
		}

		return $response['code'];

	}

	/**
	 * Deactivate license key remotely.
	 *
	 * @since  1.5.7
	 * @access private
	 * @param string  $key License key to be deactivated.
	 * @return string      Error code.
	 */
	private function remote_deactivate_license_key( $key ) {

		if ( ! $this->is_valid_license_key( $key ) ) {
			return 'not-deactivated-409';
		}

		$response = wp_remote_post(
			$this->license_key_api_url . '/deactivate-license-key',
			array(
				'timeout' => 10,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'site'    => $this->get_domain_name(),
						'key'     => $key,
						'product' => $this->addon_id,
					)
				),
			)
		);

		$response = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $response['code'] ) ) {
			return 'not-deactivated-503';
		}

		return $response['code'];

	}

	/**
	 * Validate license key using regex.
	 *
	 * @since  1.5.7
	 * @access private
	 * @param string  $key License key to be validated.
	 * @return boolean      True if license key match pattern. False otherwise.
	 */
	private function is_valid_license_key( $key ) {
		return is_string( $key ) && preg_match( $this->license_key_pattern, $key ) === 1;
	}

	/**
	 * Retrieve license (de)activation message by it's ID.
	 *
	 * @since  1.5.7
	 * @access private
	 * @param string  $id       Message ID.
	 * @param boolean $activate True for activation messages, False for deactivation messages.
	 * @return array            Array with message ID and text array( 'id' => '', 'text' => '' ).
	 */
	private function get_license_key_activation_message( $id, $activate = true ) {

		if ( ! isset( $this->license_key_activation_messages[ $id ] ) ) {
			$id = $activate ? 'not-activated-001' : 'not-deactivated-001';
		}

		return array(
			'id'   => $id,
			'text' => $this->license_key_activation_messages[ $id ],
		);

	}

	/**
	 * Retrieve domain name of the current site.
	 *
	 * @since  1.5.7
	 * @access private
	 * @return string Site's domain name.
	 */
	private function get_domain_name() {
		return wp_parse_url( home_url(), PHP_URL_HOST );
	}

	/**
	 * Helper to hide license key at settings page.
	 *
	 * @since  1.5.8
	 * @return string Masked license key.
	 */
	public function mask_license_key( $value ) {

		if ( ! $this->is_valid_license_key( $value ) ) {
			return $value;
		}

		return sprintf( $this->license_key_mask, substr( $value, -4, 4 ) );

	}

	/**
	 * Utility function to get specified template by it's name.
	 *
	 * @since 1.5.7
	 * @access private
	 * @param string  $name Template name (without extension).
	 * @param mixed   $data Template data to be passed to the template.
	 * @return string       Template content.
	 */
	private function get_template( $name, $data = null ) {

		// Sanitize name
		$name = preg_replace( '/[^A-Za-z0-9\/_-]/', '', $name );

		// Trim slashes
		$name = trim( $name, '/' );

		// The full template path
		$template = $this->plugin_path . '/admin/partials/' . $name . '.php';

		// Look for a specified file
		if ( file_exists( $template ) ) {

			ob_start();
			include $template;
			$output = ob_get_contents();
			ob_end_clean();

		}

		return ( isset( $output ) ) ? $output : '';

	}

	/**
	 * Utility function to display specified template by it's name.
	 *
	 * @since 1.5.7
	 * @access protected
	 * @param string  $name Template name (without extension).
	 * @param mixed   $data Template data to be passed to the template.
	 */
	protected function the_template( $name, $data = null ) {
		echo $this->get_template( $name, $data );
	}

	private function is_settings_page() {
		return isset( $_GET['page'] ) && 'shortcodes-ultimate-settings' === $_GET['page'];
	}

}
