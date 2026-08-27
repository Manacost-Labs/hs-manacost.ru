<?php

su_add_shortcode(
	array(
		'id'       => 'icon_panel',
		'name'     => __( 'Icon panel', 'shortcodes-ultimate-extra' ),
		'type'     => 'wrap',
		'group'    => 'extra box',
		'content'  => __( 'Panel content', 'shortcodes-ultimate-extra' ),
		'desc'     => __( 'Colorful box with icon', 'shortcodes-ultimate-extra' ),
		'icon'     => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/icon_panel.svg',
		'image'    => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/icon_panel.svg',
		'callback' => 'su_shortcode_icon_panel',
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
			'shadow'     => array(
				'type'    => 'shadow',
				'default' => '0 1px 2px #eeeeee',
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
				'default' => 'center',
				'name'    => __( 'Text align', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Text alignment for panel content', 'shortcodes-ultimate-extra' ),
				'values'  => array(
					'left'   => __( 'Left', 'shortcodes-ultimate-extra' ),
					'center' => __( 'Center', 'shortcodes-ultimate-extra' ),
					'right'  => __( 'Right', 'shortcodes-ultimate-extra' ),
				),
			),
			'icon'       => array(
				'type'    => 'icon',
				'default' => 'icon: heart',
				'name'    => __( 'Icon', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Select the icon for this panel', 'shortcodes-ultimate-extra' ),
			),
			'icon_color' => array(
				'type'    => 'color',
				'default' => '#333333',
				'name'    => __( 'Icon color', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Select icon color. This color will be aplied only to built-in icons. Does not works for uploaded icons', 'shortcodes-ultimate-extra' ),
			),
			'icon_size'  => array(
				'type'    => 'slider',
				'min'     => 10,
				'max'     => 320,
				'step'    => 1,
				'default' => 24,
				'name'    => __( 'Icon size', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Select icon size (px)', 'shortcodes-ultimate-extra' ),
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

function su_shortcode_icon_panel( $atts = null, $content = null ) {
	$atts = shortcode_atts(
		array(
			'background' => '#ffffff',
			'color'      => '#333333',
			'shadow'     => '0 1px 2px #eeeeee',
			'border'     => '1px solid #cccccc',
			'radius'     => '0',
			'text_align' => 'center',
			'icon'       => 'icon: heart',
			'icon_color' => '#333333',
			'icon_size'  => 24,
			'url'        => '',
			'target'     => 'self',
			'class'      => '',
		),
		$atts,
		'icon_panel'
	);
	if ( $atts['url'] ) {
		$atts['class'] .= ' sue-panel-clickable';
		su_query_asset( 'js', 'shortcodes-ultimate-extra' );
	}
	if ( 'self' !== $atts['target'] ) {
		$atts['target'] = 'blank';
	}
	if ( strpos( $atts['icon'], 'icon:' ) !== false ) {
		$atts['icon'] = '<i class="sui sui-' . trim( str_replace( 'icon:', '', $atts['icon'] ) ) . '" style="font-size:' . $atts['icon_size'] . 'px;color:' . $atts['icon_color'] . '"></i>';
		su_query_asset( 'css', 'su-icons' );
	} else {
		$atts['icon'] = '<img src="' . $atts['icon'] . '" style="width:' . $atts['icon_size'] . 'px" alt="" />';
	}
	su_query_asset( 'css', 'shortcodes-ultimate-extra' );
	$return = '<div class="sue-icon-panel' . su_get_css_class( $atts ) . '" data-url="' . $atts['url'] . '" data-target="' . $atts['target'] . '" style="background-color:' . $atts['background'] . ';color:' . $atts['color'] . ';border-radius:' . $atts['radius'] . 'px;-moz-border-radius:' . $atts['radius'] . 'px;-webkit-border-radius:' . $atts['radius'] . 'px;box-shadow:' . $atts['shadow'] . ';-moz-box-shadow:' . $atts['shadow'] . ';-webkit-box-shadow:' . $atts['shadow'] . ';border:' . $atts['border'] . '"><div class="sue-icon-panel-head">' . $atts['icon'] . '</div><div class="sue-icon-panel-content sue-content-wrap" style="text-align:' . $atts['text_align'] . '">' . do_shortcode( $content ) . '</div></div>';
	return $return;
}
