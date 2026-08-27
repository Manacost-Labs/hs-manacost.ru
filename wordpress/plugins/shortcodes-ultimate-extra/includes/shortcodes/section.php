<?php

su_add_shortcode(
	array(
		'id'       => 'section',
		'name'     => __( 'Section', 'shortcodes-ultimate-extra' ),
		'type'     => 'wrap',
		'group'    => 'extra box',
		'content'  => __( 'Section content', 'shortcodes-ultimate-extra' ),
		'desc'     => __( 'Content section with customizable background, dimensions and optional parallax effect', 'shortcodes-ultimate-extra' ),
		'icon'     => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/section.svg',
		'image'    => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/section.svg',
		'callback' => 'su_shortcode_section',
		'atts'     => array(
			'background'          => array(
				'type'    => 'color',
				'default' => '#ffffff',
				'name'    => __( 'Background color', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Section background color', 'shortcodes-ultimate-extra' ),
			),
			'image'               => array(
				'type'    => 'upload',
				'default' => '',
				'name'    => __( 'Background image', 'shortcodes-ultimate-extra' ),
				'desc'    => sprintf(
					// Translators: %s – example value with HTML tags
					__( 'Select background image for this section. Example value: %s', 'shortcodes-ultimate-extra' ),
					'<b%value>http://lorempixel.com/1200/600/abstract/</b>'
				),
			),
			'background_position' => array(
				'type'    => 'select',
				'default' => 'left top',
				'values'  => array(
					'left top'      => __( 'Left Top', 'shortcodes-ultimate-extra' ),
					'center top'    => __( 'Center Top', 'shortcodes-ultimate-extra' ),
					'right top'     => __( 'Right Top', 'shortcodes-ultimate-extra' ),
					'left center'   => __( 'Left Center', 'shortcodes-ultimate-extra' ),
					'center center' => __( 'Center Center', 'shortcodes-ultimate-extra' ),
					'right center'  => __( 'Right Center', 'shortcodes-ultimate-extra' ),
					'left bottom'   => __( 'Left Bottom', 'shortcodes-ultimate-extra' ),
					'center bottom' => __( 'Center Bottom', 'shortcodes-ultimate-extra' ),
					'right bottom'  => __( 'Right Bottom', 'shortcodes-ultimate-extra' ),
				),
				'name'    => __( 'Background image position', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'This option determines position of the background image', 'shortcodes-ultimate-extra' ),
			),
			'fullwidth'           => array(
				'type'    => 'bool',
				'default' => 'no',
				'name'    => __( 'Fullwidth', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Set this option to Yes to stretch section to full page width (not just content part). This option requires single-column page template.', 'shortcodes-ultimate-extra' ),
			),
			'parallax'            => array(
				'type'    => 'bool',
				'default' => 'yes',
				'name'    => __( 'Parallax', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Enable parallax effect. Parallax effect may not work in Live preview mode', 'shortcodes-ultimate-extra' ),
			),
			'cover'               => array(
				'type'    => 'bool',
				'default' => 'yes',
				'name'    => __( 'Cover', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Enable cover mode for background image. Image will fit into the section', 'shortcodes-ultimate-extra' ),
			),
			'max_width'           => array(
				'type'    => 'slider',
				'min'     => 0,
				'max'     => 1600,
				'step'    => 10,
				'default' => 960,
				'name'    => __( 'Content width', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Maximum width for this section content (px)', 'shortcodes-ultimate-extra' ),
			),
			'margin'              => array(
				'default' => '0px 0px 0px 0px',
				'name'    => __( 'Margin', 'shortcodes-ultimate-extra' ),
				'desc'    => sprintf( '%s (px), [%s]', __( 'Section margin', 'shortcodes-ultimate-extra' ), __( 'top right bottom left', 'shortcodes-ultimate-extra' ) ),
			),
			'padding'             => array(
				'default' => '30px 0px 30px 0px',
				'name'    => __( 'Padding', 'shortcodes-ultimate-extra' ),
				'desc'    => sprintf( '%s (px), [%s]', __( 'Section padding', 'shortcodes-ultimate-extra' ), __( 'top right bottom left', 'shortcodes-ultimate-extra' ) ),
			),
			'border'              => array(
				'type'    => 'border',
				'default' => '1px solid #cccccc',
				'name'    => __( 'Border', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Top and bottom section borders', 'shortcodes-ultimate-extra' ),
			),
			'color'               => array(
				'type'    => 'color',
				'default' => '#333333',
				'name'    => __( 'Text color', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Section text color', 'shortcodes-ultimate-extra' ),
			),
			'text_align'          => array(
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
			'text_shadow'         => array(
				'type'    => 'shadow',
				'default' => '0 1px 10px #ffffff',
				'name'    => __( 'Text shadow', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Pick a shadow for section text', 'shortcodes-ultimate-extra' ),
			),
			'url'                 => array(
				'default' => '',
				'name'    => __( 'URL', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'You can type here any hyperlink to make this section clickable', 'shortcodes-ultimate-extra' ),
			),
			'target'              => array(
				'type'    => 'select',
				'default' => 'blank',
				'name'    => __( 'Link target', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Choose link target', 'shortcodes-ultimate-extra' ),
				'values'  => array(
					'self'  => __( 'Open link in same window/tab', 'shortcodes-ultimate-extra' ),
					'blank' => __( 'Open link in new window/tab', 'shortcodes-ultimate-extra' ),
				),
			),
			'class'               => array(
				'type'    => 'extra_css_class',
				'name'    => __( 'Extra CSS class', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Additional CSS class name(s) separated by space(s)', 'shortcodes-ultimate-extra' ),
				'default' => '',
			),
		),
	)
);

function su_shortcode_section( $atts = null, $content = null ) {
	$atts = shortcode_atts(
		array(
			'background'          => '#ffffff',
			'background_position' => 'left top',
			'cover'               => 'yes',
			'image'               => '',
			'repeat'              => 'repeat',
			'fullwidth'           => 'no',
			'parallax'            => 'yes',
			'speed'               => '10',
			'max_width'           => '960',
			'margin'              => '0px 0px 0px 0px',
			'padding'             => '30px 0px 30px 0px',
			'border'              => '1px solid #cccccc',
			'color'               => '#333333',
			'text_align'          => 'left',
			'text_shadow'         => '0 1px 10px #ffffff',
			'url'                 => '',
			'target'              => 'blank',
			'class'               => '',
		),
		$atts,
		'section'
	);

	// Make section clickable
	if ( $atts['url'] ) {
		$atts['class'] .= ' sue-panel-clickable';
		su_query_asset( 'js', 'jquery' );
		su_query_asset( 'js', 'shortcodes-ultimate-extra' );
	}

	// Apply background color
	$background = 'background-color:' . $atts['background'] . ';';

	// Apply background position
	$background .= sprintf(
		'background-position:%s;',
		preg_replace( '/[^a-z0-9\s]/i', '', $atts['background_position'] )
	);

	// Apply background-size: cover
	if ( 'yes' === $atts['cover'] ) {
		$background .= 'background-size:cover;';
	}

	// Apply background image
	if ( $atts['image'] ) {
		$background .= 'background-image:url(\'' . esc_url( $atts['image'] ) . '\');';
	}

	// Query parallax script and add special class
	if ( $atts['image'] && 'yes' === $atts['parallax'] ) {
		su_query_asset( 'js', 'jquery' );
		su_query_asset( 'js', 'shortcodes-ultimate-extra' );
		$atts['class'] .= ' sue-section-parallax';
	}

	if ( 'yes' === $atts['fullwidth'] ) {
		$atts['class'] .= ' sue-section-fullwidth';
	}

	// Query stylesheet
	su_query_asset( 'css', 'shortcodes-ultimate-extra' );

	// Output
	return '<div class="sue-section' . su_get_css_class( $atts ) . '" data-url="' . $atts['url'] . '" data-target="' . $atts['target'] . '" style="margin:' . $atts['margin'] . ';border-top:' . $atts['border'] . ';border-bottom:' . $atts['border'] . '"><div class="sue-section-background" style="' . $background . '"></div><div class="sue-section-content sue-content-wrap" style="padding:' . $atts['padding'] . ';max-width:' . $atts['max_width'] . 'px;text-align:' . $atts['text_align'] . ';color:' . $atts['color'] . ';text-shadow:' . $atts['text_shadow'] . '">' . do_shortcode( $content ) . '</div></div>';

}
