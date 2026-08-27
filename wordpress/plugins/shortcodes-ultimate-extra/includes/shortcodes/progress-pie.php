<?php

su_add_shortcode(
	array(
		'id'       => 'progress_pie',
		'name'     => __( 'Progress pie', 'shortcodes-ultimate-extra' ),
		'type'     => 'single',
		'group'    => 'extra other',
		'desc'     => __( 'Customizable pie chart with counter', 'shortcodes-ultimate-extra' ),
		'icon'     => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/progress_pie.svg',
		'image'    => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/progress_pie.svg',
		'callback' => 'su_shortcode_progress_pie',
		'atts'     => array(
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
			'before'     => array(
				'default' => '',
				'name'    => __( 'Before', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'This content will be shown before the percent', 'shortcodes-ultimate-extra' ),
			),
			'after'      => array(
				'default' => '',
				'name'    => __( 'After', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'This content will be shown after the percent', 'shortcodes-ultimate-extra' ),
			),
			'size'       => array(
				'type'    => 'slider',
				'min'     => 20,
				'max'     => 1200,
				'step'    => 20,
				'default' => 200,
				'name'    => __( 'Size', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Pie size (pixels)', 'shortcodes-ultimate-extra' ),
			),
			'pie_width'  => array(
				'type'    => 'slider',
				'min'     => 0,
				'max'     => 100,
				'step'    => 5,
				'default' => 30,
				'name'    => __( 'Pie width', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Pie border width (percents)', 'shortcodes-ultimate-extra' ),
			),
			'text_size'  => array(
				'type'    => 'slider',
				'min'     => 10,
				'max'     => 120,
				'step'    => 5,
				'default' => 40,
				'name'    => __( 'Text size', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Pie text size (pixels)', 'shortcodes-ultimate-extra' ),
			),
			'align'      => array(
				'type'    => 'select',
				'values'  => array(
					'none'   => __( 'None', 'shortcodes-ultimate-extra' ),
					'left'   => __( 'Left', 'shortcodes-ultimate-extra' ),
					'center' => __( 'Center', 'shortcodes-ultimate-extra' ),
					'right'  => __( 'Right', 'shortcodes-ultimate-extra' ),
				),
				'default' => 'center',
				'name'    => __( 'Align', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Pie alignment', 'shortcodes-ultimate-extra' ),
			),
			'pie_color'  => array(
				'type'    => 'color',
				'default' => '#f0f0f0',
				'name'    => __( 'Pie color', 'su' ),
				'desc'    => __( 'Unfilled pie background color', 'shortcodes-ultimate-extra' ),
			),
			'fill_color' => array(
				'type'    => 'color',
				'default' => '#97daed',
				'name'    => __( 'Fill color', 'su' ),
				'desc'    => __( 'Filled pie background color', 'shortcodes-ultimate-extra' ),
			),
			'text_color' => array(
				'type'    => 'color',
				'default' => '#cccccc',
				'name'    => __( 'Text color', 'su' ),
				'desc'    => __( 'Select pie text color', 'shortcodes-ultimate-extra' ),
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

function su_shortcode_progress_pie( $atts = null, $content = null ) {
	$atts = shortcode_atts(
		array(
			'percent'    => 75,
			'text'       => '',
			'before'     => '',
			'after'      => '',
			'size'       => 200,
			'pie_width'  => 30,
			'text_size'  => 40,
			'align'      => 'center',
			'pie_color'  => '#f0f0f0',
			'fill_color' => '#97daed',
			'text_color' => '#cccccc',
			'class'      => '',
		),
		$atts,
		'progress_pie'
	);
	su_query_asset( 'css', 'shortcodes-ultimate-extra' );
	su_query_asset( 'js', 'jquery' );
	su_query_asset( 'js', 'chartjs' );
	su_query_asset( 'js', 'jquery-inview' );
	su_query_asset( 'js', 'shortcodes-ultimate-extra' );
	if ( ! $atts['text'] ) {
		$atts['text'] = $atts['percent'];
	}
	$return = '<div class="sue-progress-pie sue-progress-pie-align-' . $atts['align'] . su_get_css_class( $atts ) . '" style="width:' . $atts['size'] . 'px;height:' . $atts['size'] . 'px" data-percent="' . $atts['percent'] . '" data-size="' . $atts['size'] . '" data-pie_width="' . $atts['pie_width'] . '" data-pie_color="' . $atts['pie_color'] . '" data-fill_color="' . $atts['fill_color'] . '"><canvas width="' . $atts['size'] . '" height="' . $atts['size'] . '"></canvas><div style="color:' . $atts['text_color'] . ';line-height:' . $atts['size'] . 'px;font-size:' . $atts['text_size'] . 'px"><span class="sue-progress-pie-before">' . $atts['before'] . '</span><span class="sue-progress-pie-text">' . $atts['text'] . '</span><span class="sue-progress-pie-after">' . $atts['after'] . '</span></div></div>';
	return $return;
}
