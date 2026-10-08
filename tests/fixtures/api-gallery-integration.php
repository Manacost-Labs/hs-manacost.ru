<?php
/** Real WordPress, media files, SQL and shortcode behavior; owned local data only. */
if (wp_get_environment_type() !== 'local' || wp_parse_url(home_url(), PHP_URL_HOST) !== '127.0.0.1') {
    throw new RuntimeException('Disposable loopback site required');
}
require ABSPATH . '.integration/api-gallery-transport.php';
use Manacost\ApiGallery\{Catalog,Importer,Votes,Identity,Gallery};
function hs_gallery_assert($condition, $label) { if (!$condition) { throw new RuntimeException($label); } }
$image=imagecreatetruecolor(300,430);
imagealphablending($image,false);imagesavealpha($image,true);
$transparent=imagecolorallocatealpha($image,0,0,0,127);imagefill($image,0,0,$transparent);
$blue=imagecolorallocate($image,31,72,120);imagefilledrectangle($image,15,20,285,410,$blue);
imagepng($image,WP_CONTENT_DIR.'/uploads/hs-gallery-fixture.png');
delete_option('hs_api_gallery_schema');
Votes::install();
Votes::install();
hs_gallery_assert(get_option('hs_api_gallery_schema')==='1','Idempotent schema setup');
wp_set_current_user(1);
foreach (get_posts(['post_type'=>'post','post_status'=>'any','s'=>'Оценки карт — галерея API','posts_per_page'=>20]) as $old) { if ($old->post_title==='Оценки карт — галерея API' && (int)$old->post_author===1) { wp_delete_post($old->ID,true); } }
$post_id=wp_insert_post(['post_title'=>'Оценки карт — галерея API','post_status'=>'draft','post_type'=>'post','post_author'=>1]);
foreach (['TEST_HTML'=>'gallery_file','TEST_LARGE'=>'gallery_file','TEST_REDIRECT'=>'gallery_download'] as $object=>$code) {
    $failure=Importer::import($post_id,'constructed-cards',$object,'card');
    hs_gallery_assert(is_wp_error($failure)&&$failure->get_error_code()===$code,'Unsafe provider image rejected: '.$object);
}
hs_gallery_assert(get_children(['post_parent'=>$post_id,'post_type'=>'attachment'])===[],'Rejected imports leave no attachment');
$ids=[];
foreach (['TEST_CARD_1','TEST_CARD_2'] as $object) {
    $id=Importer::import($post_id,'constructed-cards',$object,'card');
    if (is_wp_error($id)) { throw new RuntimeException('Image import: '.$id->get_error_code().' '.$id->get_error_message()); }
    hs_gallery_assert(Importer::import($post_id,'constructed-cards',$object,'card')===$id,'Retry reuses immutable snapshot');
    $ids[]=$id;
    $original=wp_get_original_image_path($id);
    hs_gallery_assert(hash_file('sha256',$original)===hash_file('sha256',WP_CONTENT_DIR.'/uploads/hs-gallery-fixture.png'),'Original bytes unchanged');
    hs_gallery_assert(get_post_meta($id,'_hs_api_gallery_sha256',true)===hash_file('sha256',$original),'Snapshot digest saved');
    hs_gallery_assert(get_post_meta($id,'_hs_api_gallery_source',true)['library']==='constructed-cards','Provider library retained for future object features');
}
hs_gallery_assert(get_attached_file($ids[0])!==get_attached_file($ids[1]),'Unique media paths');
$content='<h2>Оцените новые карты</h2><p>Обычная галерея с локальными изображениями.</p>[gallery ids="'.implode(',',$ids).'" columns="2" size="medium" link="file" hs_ratings="1"]';
wp_update_post(['ID'=>$post_id,'post_content'=>$content]);
hs_gallery_assert(Gallery::eligible($post_id)===[],'Drafts cannot receive votes');
wp_update_post(['ID'=>$post_id,'post_status'=>'publish']);
hs_gallery_assert(Gallery::eligible($post_id)===$ids,'Only enabled article attachments eligible');
$GLOBALS['post']=get_post($post_id);
setup_postdata($GLOBALS['post']);
$html=do_shortcode($content);
hs_gallery_assert(substr_count($html,'class="hs-gallery-rating"')===2,'Rating beneath each native image');
hs_gallery_assert(str_contains($html,'gallery-columns-2')&&str_contains($html,'gallery-item'),'Core gallery and settings preserved');
hs_gallery_assert(!str_contains($html,'api.kolodahearthstone.com'),'Rendered gallery has no provider dependency');
$key=Identity::user_key(1);
hs_gallery_assert(Votes::save($post_id,$ids[0],$key,5),'First vote');
hs_gallery_assert(Votes::save($post_id,$ids[0],$key,3),'Update own vote');
hs_gallery_assert(Votes::summary($post_id,$ids[0],$key)===['average'=>3.0,'count'=>1,'mine'=>3],'Vote update does not add a second voter');
hs_gallery_assert(!Votes::save($post_id,$ids[0],$key,6),'Reject out of range score');
hs_gallery_assert(Votes::save($post_id,$ids[0],Identity::user_key(2),5),'Independent second voter');
hs_gallery_assert(Votes::summary($post_id,$ids[0],$key)['average']===4.0,'Average persisted');
$export=Votes::export('integration@example.invalid');
hs_gallery_assert(count($export['data'])===1,'Privacy export');
hs_gallery_assert(Votes::save($post_id,$ids[0],$key,0),'Own vote removal');
hs_gallery_assert(Votes::summary($post_id,$ids[0],$key)['count']===1,'Removal affects only own score');
Votes::save($post_id,$ids[0],$key,2);
hs_gallery_assert(Votes::erase('integration@example.invalid')['items_removed'],'Privacy erasure');
Votes::save($post_id,$ids[1],$key,4);
wp_delete_attachment($ids[1],true);
hs_gallery_assert(Votes::summary($post_id,$ids[1],$key)['count']===0,'Deleted attachment votes removed');
// Anonymization retains the historical score but drops its identity and timestamp.
global $wpdb;
$wpdb->update($wpdb->prefix.'hs_gallery_votes',['updated_at'=>'2000-01-01 00:00:00'],['post_id'=>$post_id,'attachment_id'=>$ids[0]]);
Votes::expire();
hs_gallery_assert(Votes::summary($post_id,$ids[0],Identity::user_key(2))===['average'=>5.0,'count'=>1,'mine'=>0],'Retention preserves historical averages');
Votes::delete_post($post_id);
// Leave a clean editor fixture with no removed attachment or historical votes.
$second=Importer::import($post_id,'constructed-cards','TEST_CARD_2','card');
$ids[1]=$second;
Votes::save($post_id,$ids[0],Identity::user_key(2),0);
wp_update_post(['ID'=>$post_id,'post_content'=>'<h2>Оцените новые карты</h2><p>Тестовый черновик для редактора.</p>','post_status'=>'draft']);
update_option('hs_api_gallery_test_post',$post_id,false);
$author=get_user_by('login','gallery-author');
if (!$author) {
    $password=getenv('WP_TEST_AUTHOR_PASSWORD');
    if (!$password) { throw new RuntimeException('Private test author password required'); }
    $author_id=wp_insert_user(['user_login'=>'gallery-author','user_email'=>'gallery-author@integration.invalid','user_pass'=>$password,'role'=>'author']);
    if (is_wp_error($author_id)) { throw new RuntimeException('Test author creation'); }
    $author=get_user_by('id',$author_id);
}
$author_post=wp_insert_post(['post_title'=>'API gallery author fixture','post_author'=>$author->ID,'post_type'=>'post','post_status'=>'draft']);
echo wp_json_encode(['ok'=>true,'post_id'=>$post_id,'attachment_ids'=>$ids,'author_post_id'=>$author_post])."\n";
