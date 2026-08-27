<?php

su_add_shortcode(
	array(
		'id'       => 'testimonial',
		'name'     => __( 'Testimonial', 'shortcodes-ultimate-extra' ),
		'type'     => 'wrap',
		'group'    => 'extra box',
		'desc'     => __( 'Styled testimonial box', 'shortcodes-ultimate-extra' ),
		'icon'     => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/testimonial.svg',
		'image'    => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/testimonial.svg',
		'callback' => 'su_shortcode_testimonial',
		'atts'     => array(
			'name'    => array(
				'default' => '',
				'name'    => __( 'Person name', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Type here a testimonial author name', 'shortcodes-ultimate-extra' ),
			),
			'photo'   => array(
				'type'    => 'upload',
				'default' => '',
				'name'    => __( 'Person photo', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Choose testimonial author photo', 'shortcodes-ultimate-extra' ),
			),
			'company' => array(
				'default' => '',
				'name'    => __( 'Company', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Type here a company name. Leave this field empty to hide company name', 'shortcodes-ultimate-extra' ),
			),
			'url'     => array(
				'default' => '',
				'name'    => __( 'Company URL', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Type here a company URL. Leave this field empty to disable link', 'shortcodes-ultimate-extra' ),
			),
			'target'  => array(
				'type'    => 'select',
				'default' => 'blank',
				'name'    => __( 'Link target', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Choose link target', 'shortcodes-ultimate-extra' ),
				'values'  => array(
					'self'  => __( 'Open link in same window/tab', 'shortcodes-ultimate-extra' ),
					'blank' => __( 'Open link in new window/tab', 'shortcodes-ultimate-extra' ),
				),
			),
			'border'  => array(
				'type'    => 'bool',
				'default' => 'yes',
				'name'    => __( 'Show border', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Show grey border around this testimonial', 'shortcodes-ultimate-extra' ),
			),
			'class'   => array(
				'type'    => 'extra_css_class',
				'name'    => __( 'Extra CSS class', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Additional CSS class name(s) separated by space(s)', 'shortcodes-ultimate-extra' ),
				'default' => '',
			),
		),
		'content'  => __( 'Testimonial text', 'shortcodes-ultimate-extra' ),
	)
);

function su_shortcode_testimonial( $atts = null, $content = null ) {
	$atts = shortcode_atts(
		array(
			'name'    => '',
			'photo'   => '',
			'company' => '',
			'url'     => '',
			'target'  => 'blank',
			'border'  => 'yes',
			'class'   => '',
		),
		$atts,
		'testimonial'
	);
	// Query stylesheet
	su_query_asset( 'css', 'shortcodes-ultimate-extra' );
	// Add photo
	if ( $atts['photo'] ) {
		// Add class
		$atts['class'] .= ' sue-testimonial-has-photo';
		// Add markup
		$atts['photo'] = '<img src="' . $atts['photo'] . '" alt="' . esc_attr( $atts['name'] ) . '" class="sue-testimonial-photo" width="40" height="40" />';
	}
	// Prepare company name
	if ( $atts['company'] ) {
		// Add hyperlink
		$atts['company'] = ( $atts['url'] )
		// Company name with hyperlink
		? '<a href="' . $atts['url'] . '" class="sue-testimonial-company" target="_' . $atts['target'] . '">' . $atts['company'] . '</a>'
		// Company name without link
		: '<span class="sue-testimonial-company">' . $atts['company'] . '</span>';
		// Add hyphen
		if ( $atts['name'] ) {
			$atts['company'] = ', ' . $atts['company'];
		}
	}
	// Add border class
	if ( 'yes' === $atts['border'] ) {
		$atts['class'] .= ' sue-testimonial-bordered';
	}
	// Return result
	return '<div class="sue-testimonial' . su_get_css_class( $atts ) . '"><div class="sue-testimonial-text sue-content-wrap">' . do_shortcode( $content ) . '</div><div class="sue-testimonial-cite"><span class="sue-testimonial-name">' . $atts['name'] . '</span>' . $atts['company'] . '</div>' . $atts['photo'] . '</div>';
}
