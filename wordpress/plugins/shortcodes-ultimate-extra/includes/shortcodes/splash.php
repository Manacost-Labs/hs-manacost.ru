<?php

su_add_shortcode(
	array(
		'id'       => 'splash',
		'name'     => __( 'Splash screen', 'shortcodes-ultimate-extra' ),
		'type'     => 'wrap',
		'group'    => 'extra box',
		'content'  => __( 'Splash screen content', 'shortcodes-ultimate-extra' ),
		'desc'     => __( 'Fully customizable splash screen', 'shortcodes-ultimate-extra' ),
		'icon'     => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/splash.svg',
		'image'    => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/splash.svg',
		'callback' => 'su_shortcode_splash',
		'atts'     => array(
			'style'   => array(
				'type'    => 'select',
				'default' => 'dark',
				'name'    => __( 'Style', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Choose splash screen style', 'shortcodes-ultimate-extra' ),
				'values'  => array(
					'dark'               => __( 'Dark', 'shortcodes-ultimate-extra' ),
					'dark-boxed'         => __( 'Dark boxed', 'shortcodes-ultimate-extra' ),
					'light'              => __( 'Light', 'shortcodes-ultimate-extra' ),
					'light-boxed'        => __( 'Light boxed', 'shortcodes-ultimate-extra' ),
					'blue-boxed'         => __( 'Blue boxed', 'shortcodes-ultimate-extra' ),
					'light-boxed-blue'   => __( 'Light boxed blue', 'shortcodes-ultimate-extra' ),
					'light-boxed-green'  => __( 'Light boxed green', 'shortcodes-ultimate-extra' ),
					'light-boxed-orange' => __( 'Light boxed orange', 'shortcodes-ultimate-extra' ),
					'maintenance'        => __( 'Maintenance', 'shortcodes-ultimate-extra' ),
				),
			),
			'width'   => array(
				'type'    => 'slider',
				'min'     => 100,
				'max'     => 1600,
				'step'    => 20,
				'default' => 480,
				'name'    => __( 'Width', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Width of splash screen content', 'shortcodes-ultimate-extra' ),
			),
			'opacity' => array(
				'type'    => 'slider',
				'min'     => 0,
				'max'     => 100,
				'step'    => 5,
				'default' => 80,
				'name'    => __( 'Opacity', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Background opacity in percents', 'shortcodes-ultimate-extra' ),
			),
			'onclick' => array(
				'type'    => 'select',
				'default' => 'close-bg',
				'name'    => __( 'Action on click', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Choose splash screen behavior when it is clicked', 'shortcodes-ultimate-extra' ),
				'values'  => array(
					'none'     => __( 'Do nothing', 'shortcodes-ultimate-extra' ),
					'close'    => __( 'Close splash screen (click anywhere)', 'shortcodes-ultimate-extra' ),
					'close-bg' => __( 'Close on background click', 'shortcodes-ultimate-extra' ),
					'url'      => __( 'Go to specified url', 'shortcodes-ultimate-extra' ),
				),
			),
			'url'     => array(
				'name'    => __( 'URL', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Enter url to go when splash screen is clicked (this option must selected in dropdown list above)', 'shortcodes-ultimate-extra' ),
				'default' => get_bloginfo( 'url' ),
			),
			'delay'   => array(
				'type'    => 'slider',
				'min'     => 0,
				'max'     => 120,
				'step'    => 1,
				'default' => 0,
				'name'    => __( 'Delay', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Specify the time in which the splash screen will be shown (in seconds)', 'shortcodes-ultimate-extra' ),
			),
			'esc'     => array(
				'type'    => 'bool',
				'default' => 'yes',
				'name'    => __( 'Esc hotkey', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Close the screen by pressing Esc', 'shortcodes-ultimate-extra' ),
			),
			'close'   => array(
				'type'    => 'bool',
				'default' => 'yes',
				'name'    => __( 'Close button', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Show Close button', 'shortcodes-ultimate-extra' ),
			),
			'once'    => array(
				'type'    => 'bool',
				'default' => 'no',
				'name'    => __( 'Show once', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Show this splash screen only once on this page', 'shortcodes-ultimate-extra' ),
			),
			'class'   => array(
				'type'    => 'extra_css_class',
				'name'    => __( 'Extra CSS class', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Additional CSS class name(s) separated by space(s)', 'shortcodes-ultimate-extra' ),
				'default' => '',
			),
		),
	)
);

function su_shortcode_splash( $atts = null, $content = null ) {
	$atts = shortcode_atts(
		array(
			'style'   => 'dark',
			'width'   => 480,
			'opacity' => 80,
			'onclick' => 'close-bg',
			'url'     => get_bloginfo( 'url' ),
			'delay'   => 0,
			'esc'     => 'yes',
			'close'   => 'yes',
			'once'    => 'no',
			'class'   => '',
		),
		$atts,
		'splash'
	);
	// Don't show splash screen in preview mode
	if ( did_action( 'su/generator/preview/before' ) ) {
		return __( 'This shortcode doesn\'t work in live preview. Please insert it into editor and preview on the site.', 'shortcodes-ultimate-extra' );
	}
	// Prepare cookie name for current page
	$cookie = 'sue_splash_' . md5( $_SERVER['REQUEST_URI'] );
	// Don't show splash screen twice
	if ( 'yes' === $atts['once'] && isset( $_COOKIE[ $cookie ] ) ) {
		return;
	}
	// Prepare opacity
	$atts['opacity'] = ( ! is_numeric( $atts['opacity'] ) || $atts['opacity'] > 100 || $atts['opacity'] < 0 ) ? 0.8 : $atts['opacity'] / 100;
	// Request assets
	su_query_asset( 'css', 'magnific-popup' );
	su_query_asset( 'css', 'shortcodes-ultimate-extra' );
	su_query_asset( 'js', 'jquery' );
	su_query_asset( 'js', 'magnific-popup' );
	su_query_asset( 'js', 'shortcodes-ultimate-extra' );
	return '<div class="sue-splash" data-once="' . $atts['once'] . '" data-cookie="' . $cookie . '" data-esc="' . $atts['esc'] . '" data-close="' . $atts['close'] . '" data-onclick="' . $atts['onclick'] . '" data-url="' . $atts['url'] . '" data-opacity="' . (string) $atts['opacity'] . '" data-width="' . $atts['width'] . '" data-style="sue-splash-style-' . $atts['style'] . '" data-delay="' . (string) $atts['delay'] . '"><div class="sue-splash-screen sue-content-wrap' . su_get_css_class( $atts ) . '">' . do_shortcode( $content ) . '</div></div>';
}
