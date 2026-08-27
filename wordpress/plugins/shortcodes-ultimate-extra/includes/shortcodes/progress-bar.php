<?php

su_add_shortcode(
	array(
		'id'       => 'progress_bar',
		'name'     => __( 'Progress bar', 'shortcodes-ultimate-extra' ),
		'type'     => 'single',
		'group'    => 'extra other',
		'desc'     => __( 'Customizable progress bar', 'shortcodes-ultimate-extra' ),
		'icon'     => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/progress_bar.svg',
		'image'    => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/progress_bar.svg',
		'callback' => 'su_shortcode_progress_bar',
		'atts'     => array(
			'style'      => array(
				'type'    => 'select',
				'values'  => array(
					'default' => __( 'Default', 'shortcodes-ultimate-extra' ),
					'fancy'   => __( 'Fancy', 'shortcodes-ultimate-extra' ),
					'thin'    => __( 'Thin', 'shortcodes-ultimate-extra' ),
				),
				'default' => 'default',
				'name'    => __( 'Style', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Select progress bar style', 'shortcodes-ultimate-extra' ),
			),
			'percent'    => array(
				'type'    => 'slider',
				'min'     => 0,
				'max'     => 100,
				'step'    => 1,
				'default' => 75,
				'name'    => __( 'Percent', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Specify percentage', 'shortcodes-ultimate-extra' ),
			),
			'text'       => array(
				'default' => '',
				'name'    => __( 'Text', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'You can show custom text instead of percent. Leave this field empty to show the percent', 'shortcodes-ultimate-extra' ),
			),
			'bar_color'  => array(
				'type'    => 'color',
				'default' => '#f0f0f0',
				'name'    => __( 'Bar color', 'su' ),
				'desc'    => __( 'Unfilled bar background color', 'shortcodes-ultimate-extra' ),
			),
			'fill_color' => array(
				'type'    => 'color',
				'default' => '#97daed',
				'name'    => __( 'Fill color', 'su' ),
				'desc'    => __( 'Filled bar background color', 'shortcodes-ultimate-extra' ),
			),
			'text_color' => array(
				'type'    => 'color',
				'default' => '#555555',
				'name'    => __( 'Text color', 'su' ),
				'desc'    => __( 'Select bar text color', 'shortcodes-ultimate-extra' ),
			),
			'animation'  => array(
				'type'    => 'bool',
				'default' => 'yes',
				'name'    => __( 'Animation', 'su' ),
				'desc'    => __( 'Enables progress bar animation', 'shortcodes-ultimate-extra' ),
			),
			'class'      => array(
				'type'    => 'extra_css_class',
				'name'    => __( 'Extra CSS class', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Additional CSS class name(s) separated by space(s)', 'shortcodes-ultimate-extra' ),
				'default' => '',
			),
		),
	)
);

function su_shortcode_progress_bar( $atts = null, $content = null ) {

	$atts = shortcode_atts(
		array(
			'style'      => 'default',
			'percent'    => 75,
			'text'       => '',
			'bar_color'  => '#f0f0f0',
			'fill_color' => '#97daed',
			'text_color' => '#555555',
			'animation'  => 'yes',
			'class'      => '',
		),
		$atts,
		'progress_bar'
	);

	su_query_asset( 'css', 'shortcodes-ultimate-extra' );
	su_query_asset( 'js', 'jquery' );
	su_query_asset( 'js', 'jquery-inview' );
	su_query_asset( 'js', 'shortcodes-ultimate-extra' );

	if ( ! $atts['text'] ) {
		$atts['text'] = $atts['percent'];
	}

	$initial_width = $atts['percent'] . '%';

	if ( 'yes' === $atts['animation'] ) {

		$atts['class'] .= ' sue-progress-bar-animation';

		$initial_width = 0;

	}

	$return = '<div class="sue-progress-bar sue-progress-bar-style-' . $atts['style'] . su_get_css_class( $atts ) . '" style="background-color:' . $atts['bar_color'] . ';border-color:' . su_adjust_brightness( $atts['bar_color'], -10 ) . '" data-percent="' . $atts['percent'] . '"><span style="width:' . $initial_width . ';background-color:' . $atts['fill_color'] . ';color:' . $atts['text_color'] . '"><span>' . $atts['text'] . '</span></span></div>';

	return $return;

}
