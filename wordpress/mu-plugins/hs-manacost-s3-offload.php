<?php
/**
 * Plugin Name: HS Manacost S3 Offload
 * Description: Restores offloaded WordPress image originals from OVH Object Storage when administrative tools need a local file.
 * Version: 1.0.0
 */

declare(strict_types=1);

use HsManacost\S3Offload\Hydrator;

if (!defined('ABSPATH')) {
    exit;
}

$hsManacostS3ClassDirectory = defined('HS_MANACOST_S3_CLASS_DIR')
    ? HS_MANACOST_S3_CLASS_DIR
    : __DIR__ . '/hs-manacost-s3-offload/src';

require_once $hsManacostS3ClassDirectory . '/PathPolicy.php';
require_once $hsManacostS3ClassDirectory . '/Hydrator.php';

const HS_MANACOST_S3_PUBLIC_BASE_URL = 'https://hs-manacost-media-3az.s3.eu-west-par.io.cloud.ovh.net';
const HS_MANACOST_S3_UPLOAD_PREFIX = 'wp-content/uploads';

function hs_manacost_s3_restore_context(): bool
{
    if (getenv('HS_MANACOST_S3_RESTORE') === '1') {
        return true;
    }

    if (!is_admin()) {
        return false;
    }

    $action = isset($_REQUEST['action']) && is_string($_REQUEST['action'])
        ? $_REQUEST['action']
        : '';

    return in_array($action, ['image-editor', 'imgedit-preview'], true);
}

function hs_manacost_s3_hydrator(): Hydrator
{
    static $hydrator = null;

    if ($hydrator instanceof Hydrator) {
        return $hydrator;
    }

    $hydrator = new Hydrator(
        static function (string $url, string $temporaryFile): bool {
            $response = wp_safe_remote_get(
                $url,
                [
                    'timeout' => 120,
                    'redirection' => 0,
                    'stream' => true,
                    'filename' => $temporaryFile,
                ]
            );

            if (is_wp_error($response)) {
                return false;
            }

            return wp_remote_retrieve_response_code($response) === 200;
        }
    );

    return $hydrator;
}

function hs_manacost_s3_restore_file(string|false $file): string|false
{
    if ($file === false || $file === '' || file_exists($file) || !hs_manacost_s3_restore_context()) {
        return $file;
    }

    $uploads = wp_get_upload_dir();
    $baseDirectory = isset($uploads['basedir']) ? (string) $uploads['basedir'] : '';

    if ($baseDirectory !== '') {
        hs_manacost_s3_hydrator()->restore(
            $file,
            $baseDirectory,
            HS_MANACOST_S3_PUBLIC_BASE_URL,
            HS_MANACOST_S3_UPLOAD_PREFIX
        );
    }

    return $file;
}

add_filter(
    'get_attached_file',
    static fn (string|false $file, int $attachmentId): string|false => hs_manacost_s3_restore_file($file),
    20,
    2
);

add_filter(
    'wp_get_original_image_path',
    static fn (string $file, int $attachmentId): string => hs_manacost_s3_restore_file($file),
    20,
    2
);
