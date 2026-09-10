<?php
/** Disposable WordPress image-pipeline integration. Never run on production. */
declare(strict_types=1);

if (wp_get_environment_type() !== 'local' || parse_url(home_url(), PHP_URL_HOST) !== '127.0.0.1') {
    throw new RuntimeException('This fixture requires the isolated local WordPress.');
}

function media_expect(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$phase = getenv('HS_MEDIA_TEST_PHASE') ?: 'create';
if ($phase === 'create') {
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    $temporary = wp_tempnam('media-pipeline-fixture.png');
    $image = imagecreatetruecolor(3000, 3000);
    for ($row = 0; $row < 3000; ++$row) {
        $color = imagecolorallocate($image, (int) ($row * 255 / 3000), 88, 160);
        imageline($image, 0, $row, 2999, $row, $color);
    }
    imagestring($image, 5, 100, 100, 'MANACOST IMAGE PIPELINE 3000 x 3000', imagecolorallocate($image, 255, 255, 255));
    imagepng($image, $temporary, 0);
    imagedestroy($image);
    $sourceHash = hash_file('sha256', $temporary);
    $_SERVER['SCRIPT_FILENAME'] = ABSPATH . 'wp-admin/async-upload.php';
    $started = microtime(true);
    $id = media_handle_sideload(['name' => 'media-pipeline-fixture.png', 'tmp_name' => $temporary], 0);
    media_expect(!is_wp_error($id), 'WordPress must accept the 3000x3000 input.');
    $metadata = wp_get_attachment_metadata($id);
    $primary = get_attached_file($id, true);
    $original = wp_get_original_image_path($id, true);
    media_expect(is_file($original) && hash_file('sha256', $original) === $sourceHash, 'Original bytes must remain unchanged.');
    update_option('hs_media_pipeline_fixture', ['id' => $id, 'original_hash' => $sourceHash], false);
    echo json_encode(['phase' => $phase, 'id' => $id, 'seconds' => round(microtime(true) - $started, 3), 'primary_mime' => wp_get_image_mime($primary), 'primary_bytes' => filesize($primary), 'original_bytes' => filesize($original), 'dimensions' => [$metadata['width'], $metadata['height']], 'sizes' => array_keys($metadata['sizes']), 'original_unchanged' => true]) . "\n";
} elseif ($phase === 'cron') {
    media_expect(!function_exists('wp_update_image_subsizes'), 'Cron regression requires core image helpers absent before the callback.');
    $state = get_option('hs_media_pipeline_fixture');
    $id = (int) $state['id'];
    $started = microtime(true);
    $hook = class_exists('HS_Media_Upload_Accelerator') ? 'hs_media_upload_accelerator_generate_subsizes' : 'manacost_media_upload_accelerator_generate_subsizes';
    do_action($hook, $id, 0);
    media_expect(function_exists('wp_update_image_subsizes'), 'Worker must load the real core helper.');
    media_expect(wp_get_missing_image_subsizes($id) === [], 'All possible registered sizes must be present.');
    $metadata = wp_get_attachment_metadata($id);
    $base = dirname(get_attached_file($id, true));
    foreach ($metadata['sizes'] as $size) {
        media_expect(is_file($base . '/' . $size['file']), 'Metadata must reference an existing generated file.');
    }
    media_expect(hash_file('sha256', wp_get_original_image_path($id, true)) === $state['original_hash'], 'Deferred work must preserve original bytes.');
    echo json_encode(['phase' => $phase, 'id' => $id, 'seconds' => round(microtime(true) - $started, 3), 'sizes' => count($metadata['sizes']), 'missing' => 0, 'original_unchanged' => true]) . "\n";
} else {
    throw new RuntimeException('Unknown fixture phase.');
}
