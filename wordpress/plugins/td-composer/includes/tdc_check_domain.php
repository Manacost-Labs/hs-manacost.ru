<?php

class tdc_check_domain {

    /**
     * The name of the transient used to store whether the registered theme key
     * is active on this domain.
     *
     * @var string
     */
    const TRANSIENT_NAME = 'TD_DOMAIN_ACTIVE';

    /**
     * Time until expiration of the transient that stores the active domain status.
     *
     * @var string
     */
    const TRANSIENT_EXPIRATION = 24 * 60 * 60;

    /**
     * Whether the theme is registered on this domain.
     *
     * @var boolean
     */
    private $is_theme_registered;

    /**
     * Whether the registered theme key is active on this domain.
     *
     * @var boolean
     */
    private $is_domain_active;

    /**
     * Class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->set_is_theme_registered();
        $this->set_is_domain_active();
    }

    /**
     * Initializes the class.
     *
     * @since 1.0.0
     *
     * @return tdc_check_domain
     *
     */
    public static function init() {
        static $instance;

        if ( !isset($instance) ) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Returns whether the theme is registered on this domain.
     *
     * @return bool
     */
    public function is_theme_registered() {
        return $this->is_theme_registered;
    }

    /**
     * Sets whether the theme is registered on this domain.
     */
    public function set_is_theme_registered() {
        if ( TD_DEPLOY_MODE == 'dev' ) {
            $this->is_theme_registered = true;
            return;
        }

        $this->is_theme_registered = td_util::get_option_('td_cake_status') == 2;
    }

    /**
     * Returns whether the theme key is active on the current domain.
     *
     * @return bool
     */
    public function is_domain_active() {
        return $this->is_domain_active;
    }

    /**
     * Sets whether the theme key is active on the current domain.
     */
    private function set_is_domain_active() {
        // In case of dev mode, always set the transient to true without pinging the server.
        if ( TD_DEPLOY_MODE == 'dev' ) {
            $this->is_domain_active = true;
            return;
        }

        if ( !current_user_can('publish_pages') ) {
            $this->is_domain_active = false;
            return;
        }

        $domain_active_transient = get_transient(static::TRANSIENT_NAME);
        $this->is_domain_active = $domain_active_transient !== false ? $domain_active_transient['value'] : $this->check_domain();
    }

    /**
     * Performs an API call to check whether the registered theme key is active on this domain.
     *
     * @return bool
     */
    public function check_domain() {
        // If the theme is not registered, then we can assume from the get-go that the domain
        // is not active.
        if ( !$this->is_theme_registered ) {
            self::set_transient(false);
            return false;
        }

        // Retrieve the Envato theme key. Just in case the previous check failed, make sure
        // the theme is actually registered.
        $envato_key = base64_decode(td_util::get_option('td_011'));
        if ( empty($envato_key) ) {
            self::set_transient(false);
            return false;
        }

        // Perform the API request.
        $api_url = tdc_util::get_api_url();
        $api_response = wp_remote_post($api_url . '/' . 'templates/check', array(
            'method'  => 'POST',
            'body'    => array(
                'envato_key'    => $envato_key,
                'theme_version' => TD_THEME_VERSION,
                'deploy_mode'   => 'deploy',
                'host'          => $_SERVER['HTTP_HOST']
            ),
            'timeout' => 14
        ));

        // Log any errors.
        if ( is_wp_error($api_response) ) {
            error_log(print_r(
                'TagDiv domain check: ERROR - Failed to contact the templates API server.' . PHP_EOL .
                $api_response,
                true))
            ;
            self::set_transient(false);
        }

        if ( isset($api_response['response']['code']) && $api_response['response']['code'] != 200 ) {
            error_log(print_r(
                'TagDiv domain check: ERROR - Received a response code != 200 while trying to contact the templates API server.' . PHP_EOL .
                $api_response,
                true)
            );
            self::set_transient(false);
        }

        $api_response_body = json_decode($api_response['body'], true);
        if ( empty($api_response_body) ) {
            error_log(print_r(
                'TagDiv domain check: ERROR - Received an empty response body while contacting the templates API server..' . PHP_EOL .
                $api_response,
                true)
            );
            self::set_transient(false);
        }

        // Update the transient and return whether the domain is active.
        $is_domain_active =
            (
                isset($api_response_body['api_reply']['key_is_valid']) &&
                $api_response_body['api_reply']['key_is_valid']
            ) &&
            (
                isset($api_response_body['api_reply']['key_status']) &&
                $api_response_body['api_reply']['key_status'] == 'active_key'
            );

        self::set_transient($is_domain_active);
        return $is_domain_active;
    }

    /**
     * Sets the transient holding the active domain status.
     *
     * @param boolean $value
     */
    public static function set_transient( $value ) {
        set_transient(static::TRANSIENT_NAME, array('value' => $value), static::TRANSIENT_EXPIRATION);
    }

    /**
     * Deletes the transient holding the active domain status.
     */
    public static function delete_transient() {
        delete_transient(static::TRANSIENT_NAME);
    }

}