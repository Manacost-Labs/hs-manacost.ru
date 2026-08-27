<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPQI_Assets {

	public function register() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_classic' ) );
		add_filter( 'mce_external_plugins', array( $this, 'mce_plugin' ) );
		add_filter( 'mce_buttons', array( $this, 'mce_button' ) );
	}

	public function enqueue() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		wp_enqueue_script(
			'spqi-editor',
			SPQI_URL . 'assets/editor.js',
			array(
				'wp-plugins',
				'wp-edit-post',
				'wp-element',
				'wp-components',
				'wp-data',
				'wp-blocks',
				'wp-i18n',
				'wp-api-fetch',
			),
			SPQI_VERSION,
			true
		);
		wp_set_script_translations( 'spqi-editor', 'spqi' );
		wp_enqueue_style(
			'spqi-editor',
			SPQI_URL . 'assets/editor.css',
			array(),
			SPQI_VERSION
		);
	}

	/**
	 * Footer JS for the Classic Editor (TinyMCE) modal — pure JS, no jQuery dialog dependency.
	 */
	public function enqueue_classic( $hook ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'page.php', 'page-new.php' ), true ) ) {
			return;
		}
		if ( ! user_can_richedit() ) {
			return;
		}
		wp_enqueue_style(
			'spqi-classic',
			SPQI_URL . 'assets/classic.css',
			array(),
			SPQI_VERSION
		);
		wp_enqueue_script(
			'spqi-classic',
			SPQI_URL . 'assets/classic.js',
			array( 'wp-api-fetch' ),
			SPQI_VERSION,
			true
		);
		wp_localize_script(
			'spqi-classic',
			'SPQI_CLASSIC',
			array(
				'restUrl'      => esc_url_raw( rest_url( 'spqi/v1/items' ) ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'i18n'         => array(
					'title'        => __( 'Вставить изображение', 'spqi' ),
					'separators'   => __( 'Разделители', 'spqi' ),
					'placeholders' => __( 'Заглушки', 'spqi' ),
					'misc'         => __( 'Разное', 'spqi' ),
					'preview'      => __( 'Превью', 'spqi' ),
					'empty'        => __( 'Здесь пока пусто. Добавьте изображения в Инструменты → Разделители и заглушки.', 'spqi' ),
					'loading'      => __( 'Загрузка…', 'spqi' ),
					'close'        => __( 'Закрыть', 'spqi' ),
					'buttonTitle'  => __( 'Разделители и заглушки', 'spqi' ),
				),
			)
		);
	}

	public function mce_plugin( $plugins ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return $plugins;
		}
		$plugins['spqi'] = SPQI_URL . 'assets/mce-plugin.js?v=' . SPQI_VERSION;
		return $plugins;
	}

	public function mce_button( $buttons ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return $buttons;
		}
		$buttons[] = 'spqi_picker';
		return $buttons;
	}
}
