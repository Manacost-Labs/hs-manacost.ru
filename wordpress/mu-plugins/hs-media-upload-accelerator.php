<?php
/**
 * Plugin Name: HS Media Upload Accelerator
 * Description: Keeps media uploads responsive by trimming duplicate image sizes and skipping disabled upload optimizers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'intermediate_image_sizes_advanced', 'hs_media_upload_accelerator_sizes', 20, 2 );
add_filter( 'big_image_size_threshold', '__return_false', 20 );

function hs_media_upload_accelerator_sizes( array $sizes, array $metadata ): array {
    if ( defined( 'HS_MEDIA_UPLOAD_ALL_SIZES' ) && HS_MEDIA_UPLOAD_ALL_SIZES ) {
        return $sizes;
    }

    $keep = [
        'thumbnail',
        'medium',
        'medium_large',
        'large',
        'td_80x60',
        'td_150x0',
        'td_218x150',
        'td_300x0',
        'td_485x360',
        'td_696x0',
        'td_1068x0',
        'td_324x160',
        'td_324x235',
        'td_356x220',
        'td_533x261',
        'td_534x462',
        'td_696x385',
        'td_741x486',
        'td_1068x580',
        'hs_archetype_card',
        'hs_archetype_hero',
    ];

    return array_intersect_key( $sizes, array_flip( $keep ) );
}

add_action( 'plugins_loaded', 'hs_media_upload_accelerator_prune_upload_hooks', PHP_INT_MAX );

function hs_media_upload_accelerator_prune_upload_hooks(): void {
    hs_media_upload_accelerator_remove_class_callbacks(
        'add_attachment',
        [
            'AIOSEO\\Plugin\\Lite\\Admin\\PostSettings',
            'AIOSEO\\Plugin\\Common\\Admin\\PostSettings',
        ]
    );

    $imagify_settings = get_option( 'imagify_settings', [] );

    if ( is_array( $imagify_settings ) && empty( $imagify_settings['auto_optimize'] ) ) {
        foreach ( [ 'add_attachment', 'wp_generate_attachment_metadata', 'wp_update_attachment_metadata' ] as $hook ) {
            hs_media_upload_accelerator_remove_class_callbacks( $hook, [ 'Imagify_Auto_Optimization' ] );
        }
    }
}

function hs_media_upload_accelerator_remove_class_callbacks( string $hook, array $classes ): void {
    global $wp_filter;

    if ( empty( $wp_filter[ $hook ] ) || ! is_object( $wp_filter[ $hook ] ) ) {
        return;
    }

    foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
        foreach ( $callbacks as $callback ) {
            $function = $callback['function'] ?? null;

            if ( ! is_array( $function ) || empty( $function[0] ) ) {
                continue;
            }

            $class = is_object( $function[0] ) ? get_class( $function[0] ) : (string) $function[0];

            if ( in_array( $class, $classes, true ) ) {
                remove_filter( $hook, $function, $priority );
            }
        }
    }
}
