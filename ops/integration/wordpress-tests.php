<?php
/** Behaviour checks executed inside the disposable integration WordPress. */

function hs_integration_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function rocket_clean_minify(): void
{
    update_option('hs_integration_rocket_minify_called', 1, false);
}

function rocket_clean_cache_busting(): void
{
    update_option('hs_integration_rocket_busting_called', 1, false);
}

function rocket_clean_used_css(): void
{
    update_option('hs_integration_rocket_used_css_called', 1, false);
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/post.php';
wp_set_current_user(1);

$category = wp_insert_term('Integration category', 'category', ['slug' => 'integration-category']);
$categoryId = is_wp_error($category)
    ? (int) get_term_by('slug', 'integration-category', 'category')->term_id
    : (int) $category['term_id'];

$postId = wp_insert_post([
    'post_title' => 'Integration article',
    'post_name' => 'integration-article',
    'post_content' => '[spoiler]Hidden integration text[/spoiler]',
    'post_status' => 'publish',
    'post_category' => [$categoryId],
], true);
hs_integration_assert(!is_wp_error($postId), 'article publication failed');
$postId = (int) $postId;
update_option('hs_integration_post_id', $postId, false);

wp_update_post([
    'ID' => $postId,
    'post_content' => '[spoiler]Revised integration text[/spoiler]',
]);
$revisions = wp_get_post_revisions($postId);
hs_integration_assert(count($revisions) >= 1, 'wp_get_post_revisions returned no revision');

$autosaveId = wp_create_post_autosave([
    'post_ID' => $postId,
    'post_type' => 'post',
    'post_status' => 'publish',
    'post_title' => 'Integration article autosave',
    'post_content' => 'Autosaved integration content',
]);
hs_integration_assert(!is_wp_error($autosaveId), 'wp_create_post_autosave failed');
hs_integration_assert(wp_get_post_autosave($postId) instanceof WP_Post, 'autosave cannot be read back');

$fixtureA = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
$fixtureB = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=', true);
hs_integration_assert(is_string($fixtureA) && is_string($fixtureB), 'image fixtures are invalid');

$upload = static function (string $bytes) use ($postId): int {
    $directory = wp_tempnam('integration-image-directory');
    @unlink($directory);
    wp_mkdir_p($directory);
    $path = $directory . '/duplicate-image.png';
    file_put_contents($path, $bytes);
    $attachmentId = media_handle_sideload(
        ['name' => 'duplicate-image.png', 'tmp_name' => $path],
        $postId,
        'Integration image'
    );
    hs_integration_assert(
        !is_wp_error($attachmentId),
        'media_handle_sideload failed: ' . (is_wp_error($attachmentId) ? $attachmentId->get_error_message() : '')
    );
    return (int) $attachmentId;
};

$firstAttachment = $upload($fixtureA);
$firstRelative = (string) get_post_meta($firstAttachment, '_wp_attached_file', true);
$firstFile = get_attached_file($firstAttachment, true);
hs_integration_assert(is_string($firstFile) && file_exists($firstFile), 'first upload missing');
unlink($firstFile); // Simulate the offload worker removing the original from local SSD.

$secondAttachment = $upload($fixtureB);
$secondRelative = (string) get_post_meta($secondAttachment, '_wp_attached_file', true);
hs_integration_assert($firstRelative !== $secondRelative, 'duplicate image replaced an offloaded attachment');
hs_integration_assert(str_contains(basename($secondRelative), '-1.'), 'duplicate image has no numeric suffix');

add_filter(
    'pre_http_request',
    static function ($preempt, array $arguments, string $url) use ($fixtureA) {
        if (!str_contains($url, HS_MANACOST_S3_UPLOAD_PREFIX)) {
            return $preempt;
        }
        $target = $arguments['filename'] ?? '';
        hs_integration_assert(is_string($target) && $target !== '', 'S3 stream filename missing');
        file_put_contents($target, $fixtureA);
        return ['headers' => [], 'body' => '', 'response' => ['code' => 200, 'message' => 'OK'], 'cookies' => []];
    },
    10,
    3
);
$restoredFile = hs_manacost_s3_restore_file($firstFile);
hs_integration_assert(is_string($restoredFile) && file_exists($restoredFile), 'S3 hydration failed');
hs_integration_assert(hash_file('sha256', $restoredFile) === hash('sha256', $fixtureA), 'S3 restore bytes differ');

foreach (['spoiler', 'hs_deck_link'] as $shortcode) {
    hs_integration_assert(shortcode_exists($shortcode), "missing {$shortcode} shortcode");
}
hs_integration_assert(do_shortcode('[spoiler]Visible contract[/spoiler]') !== '', 'shortcode rendering failed');

update_post_meta($postId, 'post_views_count', 41);
hs_integration_assert((int) get_post_meta($postId, 'post_views_count', true) === 41, 'post_views_count write failed');
hs_integration_assert(
    str_contains((string) file_get_contents(get_template_directory() . '/functions.php'), '/views/hit'),
    'frontend views writer is not wired'
);

hs_integration_assert(class_exists('Manacost_Domain_Mirror'), 'mirror contract is not loaded');
$canonical = Manacost_Domain_Mirror::primary_url('https://hs-manacost.com/integration-article/');
hs_integration_assert($canonical === 'https://hs-manacost.ru/integration-article/', 'primary canonical is wrong');

$cacheFixture = WP_CONTENT_DIR . '/cache/wp-rocket/integration/cache.html';
wp_mkdir_p(dirname($cacheFixture));
file_put_contents($cacheFixture, 'stale integration cache');
hs_integration_assert(file_exists($cacheFixture), 'cache fixture was not created');
Manacost_Cache_Purge::run_async_purge('integration');
hs_integration_assert(!file_exists($cacheFixture), 'local WP Rocket cache was not purged');
foreach (['minify', 'busting', 'used_css'] as $rocketStep) {
    hs_integration_assert(
        (int) get_option("hs_integration_rocket_{$rocketStep}_called") === 1,
        "WP Rocket {$rocketStep} purge integration was not called"
    );
}
$purgeState = get_option('manacost_cache_purge_last_results');
hs_integration_assert(
    is_array($purgeState) && ($purgeState['source'] ?? '') === 'auto:integration',
    'manacost_cache_purge_last_results was not recorded'
);

echo "WordPress PHP integration assertions: OK\n";
