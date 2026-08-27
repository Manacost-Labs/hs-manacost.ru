<?php

su_add_shortcode(
	array(
		'id'       => 'panel',
		'name'     => __( 'Panel', 'shortcodes-ultimate-extra' ),
		'type'     => 'wrap',
		'group'    => 'extra box',
		'content'  => __( 'Panel content', 'shortcodes-ultimate-extra' ),
		'desc'     => __( 'Colorful box with custom content', 'shortcodes-ultimate-extra' ),
		'icon'     => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/panel.svg',
		'image'    => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/panel.svg',
		'callback' => 'su_shortcode_panel',
		'atts'     => array(
			'background' => array(
				'type'    => 'color',
				'default' => '#ffffff',
				'name'    => __( 'Background', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Panel background color', 'shortcodes-ultimate-extra' ),
			),
			'color'      => array(
				'type'    => 'color',
				'default' => '#333333',
				'name'    => __( 'Color', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Panel text color', 'shortcodes-ultimate-extra' ),
			),
			'border'     => array(
				'type'    => 'border',
				'default' => '1px solid #cccccc',
				'name'    => __( 'Border', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Panel border', 'shortcodes-ultimate-extra' ),
			),
			'padding'    => array(
				'type'    => 'slider',
				'min'     => 0,
				'max'     => 150,
				'step'    => 5,
				'default' => 15,
				'name'    => __( 'Padding', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Panel padding (in pixels)', 'shortcodes-ultimate-extra' ),
			),
			'shadow'     => array(
				'type'    => 'shadow',
				'default' => '0px 1px 2px #eeeeee',
				'name'    => __( 'Shadow', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Panel shadow', 'shortcodes-ultimate-extra' ),
			),
			'radius'     => array(
				'type'    => 'slider',
				'min'     => 0,
				'max'     => 60,
				'step'    => 1,
				'default' => 0,
				'name'    => __( 'Border radius', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Panel border radius (px)', 'shortcodes-ultimate-extra' ),
			),
			'text_align' => array(
				'type'    => 'select',
				'default' => 'left',
				'values'  => array(
					'left'   => __( 'Left', 'shortcodes-ultimate-extra' ),
					'center' => __( 'Center', 'shortcodes-ultimate-extra' ),
					'right'  => __( 'Right', 'shortcodes-ultimate-extra' ),
				),
				'name'    => __( 'Text align', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Text alignment for panel content', 'shortcodes-ultimate-extra' ),
			),
			'url'        => array(
				'default' => '',
				'name'    => __( 'URL', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'You can type here any hyperlink to make this panel clickable', 'shortcodes-ultimate-extra' ),
			),
			'target'     => array(
				'type'    => 'select',
				'default' => 'self',
				'name'    => __( 'Link target', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Choose link target', 'shortcodes-ultimate-extra' ),
				'values'  => array(
					'self'  => __( 'Open link in same window/tab', 'shortcodes-ultimate-extra' ),
					'blank' => __( 'Open link in new window/tab', 'shortcodes-ultimate-extra' ),
				),
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

function su_shortcode_panel( $atts = null, $content = null ) {

	$atts = su_parse_shortcode_atts( 'panel', $atts );

	if ( $atts['url'] ) {
		$atts['class'] .= ' sue-panel-clickable';
		su_query_asset( 'js', 'shortcodes-ultimate-extra' );
	}

	if ( is_numeric( $atts['padding'] ) ) {
		$atts['padding'] .= 'px';
	}

	su_query_asset( 'css', 'shortcodes-ultimate-extra' );

	return '<div class="sue-panel' . su_get_css_class( $atts ) . '" data-url="' . $atts['url'] . '" data-target="' . sanitize_key( $atts['target'] ) . '" style="background-color:' . $atts['background'] . ';color:' . $atts['color'] . ';border-radius:' . $atts['radius'] . 'px;-moz-border-radius:' . $atts['radius'] . 'px;-webkit-border-radius:' . $atts['radius'] . 'px;box-shadow:' . $atts['shadow'] . ';-moz-box-shadow:' . $atts['shadow'] . ';-webkit-box-shadow:' . $atts['shadow'] . ';border:' . $atts['border'] . '"><div class="sue-panel-content sue-content-wrap" style="padding:' . esc_attr( $atts['padding'] ) . ';text-align:' . $atts['text_align'] . '">' . do_shortcode( $content ) . '</div></div>';

}
