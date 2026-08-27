<?php

su_add_shortcode(
	array(
		'id'       => 'shadow',
		'name'     => __( 'Shadow', 'shortcodes-ultimate-extra' ),
		'type'     => 'wrap',
		'group'    => 'extra other',
		'desc'     => __( 'Adds shadow to any nested element', 'shortcodes-ultimate-extra' ),
		'icon'     => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/shadow.svg',
		'image'    => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/shadow.svg',
		'callback' => 'su_shortcode_shadow',
		'atts'     => array(
			'style'  => array(
				'type'    => 'select',
				'default' => 'default',
				'name'    => __( 'Style', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Choose shadow style', 'shortcodes-ultimate-extra' ),
				'values'  => array(
					'default'    => __( 'Default', 'shortcodes-ultimate-extra' ),
					'left'       => __( 'Left corner', 'shortcodes-ultimate-extra' ),
					'right'      => __( 'Right corner', 'shortcodes-ultimate-extra' ),
					'horizontal' => __( 'Horizontal', 'shortcodes-ultimate-extra' ),
					'vertical'   => __( 'Vertical', 'shortcodes-ultimate-extra' ),
					'bottom'     => __( 'Bottom', 'shortcodes-ultimate-extra' ),
					'simple'     => __( 'Simple', 'shortcodes-ultimate-extra' ),
				),
			),
			'inline' => array(
				'type'    => 'bool',
				'default' => 'no',
				'name'    => __( 'Inline', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Display shadow container as an inline element. This option can be useful for small images and other inline elements', 'shortcodes-ultimate-extra' ),
			),
			'class'  => array(
				'type'    => 'extra_css_class',
				'name'    => __( 'Extra CSS class', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Additional CSS class name(s) separated by space(s)', 'shortcodes-ultimate-extra' ),
				'default' => '',
			),
		),
	)
);

function su_shortcode_shadow( $atts = null, $content = null ) {
	$atts = shortcode_atts(
		array(
			'style'  => 'default',
			'inline' => 'no',
			'class'  => '',
		),
		$atts,
		'shadow'
	);
	su_query_asset( 'css', 'shortcodes-ultimate-extra' );
	return '<div class="sue-shadow-wrap sue-content-wrap sue-shadow-inline-' . $atts['inline'] . su_get_css_class( $atts ) . '"><div class="sue-shadow sue-shadow-style-' . $atts['style'] . '">' . do_shortcode( $content ) . '</div></div>';
}
