<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPQI_REST {

	const NAMESPACE_V1 = 'spqi/v1';

	public function register() {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	public function routes() {
		register_rest_route(
			self::NAMESPACE_V1,
			'/items',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	public function get_items() {
		return rest_ensure_response(
			array(
				'separators'   => SPQI_Store::get_items( 'separators' ),
				'placeholders' => SPQI_Store::get_items( 'placeholders' ),
				'misc'         => SPQI_Store::get_items( 'misc' ),
				'preview'      => SPQI_Store::get_items( 'preview' ),
				'descriptions' => SPQI_Store::get_descriptions(),
			)
		);
	}
}
