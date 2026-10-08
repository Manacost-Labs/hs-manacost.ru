<?php
/** Verify saved database content, revisions, autosave and immutable media bytes. */
if (wp_get_environment_type()!=='local'||wp_parse_url(home_url(),PHP_URL_HOST)!=='127.0.0.1') { throw new RuntimeException('Disposable site required'); }
$id=(int)get_option('hs_api_gallery_test_post');$post=get_post($id);
if (!$post||$post->post_status!=='publish'||str_contains($post->post_content,'Проверка автосохранения галереи')) { throw new RuntimeException('Revision restore content'); }
$autosave=wp_get_post_autosave($id,1);
if (!$autosave||!str_contains($autosave->post_content,'Проверка автосохранения галереи')||!str_contains($autosave->post_content,'hs_ratings="1"')) { throw new RuntimeException('Autosave gallery persisted'); }
$images=Manacost\ApiGallery\Gallery::eligible($id);
if (count($images)!==2) { throw new RuntimeException('Published native gallery preserved'); }
foreach ($images as $image) {
    if (hash_file('sha256',wp_get_original_image_path($image))!==get_post_meta($image,'_hs_api_gallery_sha256',true)) { throw new RuntimeException('Frozen original changed'); }
}
echo wp_json_encode(['ok'=>true,'saved_gallery'=>true,'autosave_gallery'=>true,'revision_restored'=>true,'immutable_originals'=>count($images)])."\n";
