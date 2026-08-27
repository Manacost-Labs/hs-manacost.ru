<?php

if ( !defined( 'ABSPATH' ) )
    exit;

class td_twitter {

	private static $instance = null;

	public static function init() {

		if ( is_null(self::$instance) ) {
			self::$instance = new td_twitter();
		}

	}

	public function __construct() {

		if ( is_admin() ) {
			add_action( 'wp_ajax_td_save_twitter_account', array( $this, 'td_save_twitter_account' ) );
			add_action( 'wp_ajax_td_remove_twitter_account', array( $this, 'td_remove_twitter_account' ) );
		}

	}

	/*
	 * used to save twitter account data via ajax
	 */
	function td_save_twitter_account() {

		$reply = [];

        // permission check
        if ( !current_user_can('switch_themes') ) {
            $reply['errors'][] = 'You have no permission to access this endpoint.';
        }

        if ( !empty($reply['errors']) ) {
            $reply['status'] = 'error';
            die( json_encode($reply) );
        }

        // twitter account post data
        $post_account_data = $_POST['accountData'];

        // twitter account data
        $tw_account_data = [
            'oauth_token' => sanitize_text_field( $post_account_data['oauth_token'] ),
            'oauth_token_secret' => sanitize_text_field( $post_account_data['oauth_token_secret'] ),
            'user_id' => is_numeric($post_account_data['user_id']) ? (int) $post_account_data['user_id'] : 0,
            'screen_name' => sanitize_text_field( $post_account_data['screen_name'] ),
        ];

        foreach ( $tw_account_data as $key => $val ) {
            if ( empty($val) ) {
                $reply['errors'][] = 'Account ' . $key . ' data is missing and it\'s required.';
            }
        }

        if ( !empty($reply['errors']) ) {
            $reply['status'] = 'error';
            die( json_encode($reply) );
        }

        // save twitter account data
        td_options::update_array('td_twitter_connected_account', $tw_account_data );

        // set reply status
        $reply['status'] = 'success';
		
		die( json_encode($reply) );

	}

	/*
	 * used to remove a connected twitter account data via ajax
	 */
	function td_remove_twitter_account() {

		$reply = [];

		// die if user doesn't have permission
		if ( !current_user_can('switch_themes') ) {
			$reply['status'] = 'error';
            $reply['errors'][] = 'You have no permission to access this endpoint.';
			die( json_encode($reply) );
		}

        // update twitter account data option
        td_options::update_array('td_twitter_connected_account', [] );

        // set reply status
        $reply['status'] = 'success';

		die( json_encode($reply) );

	}

}

td_twitter::init();