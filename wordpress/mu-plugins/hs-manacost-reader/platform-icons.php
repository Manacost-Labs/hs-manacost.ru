<?php
/**
 * Shared, decorative platform marks for the account and discussion shells.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return a fixed SVG; the caller supplies a visible or accessible link label.
 *
 * @param string $service Supported platform identifier.
 */
function hs_reader_platform_icon( string $service ): string {
	$paths = array(
		'twitch'  => 'M6 2 2 6v14h5v4l4-4h4l7-7V2H6Zm14 10-4 4h-4l-4 4v-4H5V4h15v8ZM15 7h2v6h-2V7ZM10 7h2v6h-2V7Z',
		'youtube' => 'M21.3 7.1a2.77 2.77 0 0 0-1.95-1.96C17.63 4.67 12 4.67 12 4.67s-5.63 0-7.35.47A2.77 2.77 0 0 0 2.7 7.1C2.23 8.82 2.23 12 2.23 12s0 3.18.47 4.9a2.77 2.77 0 0 0 1.95 1.96c1.72.47 7.35.47 7.35.47s5.63 0 7.35-.47a2.77 2.77 0 0 0 1.95-1.96c.47-1.72.47-4.9.47-4.9s0-3.18-.47-4.9ZM10 15.5l5-3.5-5-3.5v7Z',
	);
	return isset( $paths[ $service ] ) ? '<svg class="mc-reader__icon mc-platform-icon" viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path fill-rule="evenodd" d="' . esc_attr( $paths[ $service ] ) . '"></path></svg>' : '';
}
