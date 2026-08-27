<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPQI_Store {

	const OPT_SEPARATORS   = 'spqi_separators';
	const OPT_PLACEHOLDERS = 'spqi_placeholders';
	const OPT_MISC         = 'spqi_misc';
	const OPT_PREVIEW      = 'spqi_preview';
	const OPT_DESCRIPTIONS = 'spqi_descriptions';

	public static function types() {
		return array( 'separators', 'placeholders', 'misc', 'preview' );
	}

	public static function option_name( $type ) {
		switch ( $type ) {
			case 'placeholders': return self::OPT_PLACEHOLDERS;
			case 'misc':         return self::OPT_MISC;
			case 'preview':      return self::OPT_PREVIEW;
			default:             return self::OPT_SEPARATORS;
		}
	}

	public static function get_descriptions() {
		$d = get_option( self::OPT_DESCRIPTIONS, array() );
		if ( ! is_array( $d ) ) {
			$d = array();
		}
		$out = array();
		foreach ( self::types() as $t ) {
			$out[ $t ] = isset( $d[ $t ] ) ? (string) $d[ $t ] : '';
		}
		return $out;
	}

	public static function get_description( $type ) {
		$all = self::get_descriptions();
		return isset( $all[ $type ] ) ? $all[ $type ] : '';
	}

	public static function set_description( $type, $text ) {
		if ( ! in_array( $type, self::types(), true ) ) {
			return;
		}
		$all = self::get_descriptions();
		$all[ $type ] = sanitize_textarea_field( (string) $text );
		update_option( self::OPT_DESCRIPTIONS, $all, false );
	}

	public static function get_ids( $type ) {
		$ids = get_option( self::option_name( $type ), array() );
		if ( ! is_array( $ids ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'absint', $ids ) ) );
	}

	public static function set_ids( $type, array $ids ) {
		$clean = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
		// Validate: only existing image attachments.
		$clean = array_values(
			array_filter(
				$clean,
				static function ( $id ) {
					$post = get_post( $id );
					return $post && 'attachment' === $post->post_type && 0 === strpos( (string) get_post_mime_type( $post ), 'image/' );
				}
			)
		);
		update_option( self::option_name( $type ), $clean, false );
		return $clean;
	}

	public static function get_items( $type ) {
		$items = array();
		foreach ( self::get_ids( $type ) as $id ) {
			$url = wp_get_attachment_url( $id );
			if ( ! $url ) {
				continue;
			}
			$thumb = wp_get_attachment_image_url( $id, 'medium' );
			$items[] = array(
				'id'    => (int) $id,
				'url'   => $url,
				'thumb' => $thumb ? $thumb : $url,
				'alt'   => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
				'title' => get_the_title( $id ),
			);
		}
		return $items;
	}
}
