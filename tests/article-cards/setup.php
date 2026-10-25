<?php
require __DIR__ . '/article-cards-transport.php';
require_once WPMU_PLUGIN_DIR . '/hs-article-cards/remote.php';
require_once WPMU_PLUGIN_DIR . '/hs-article-cards/catalog.php';
require_once ABSPATH . 'wp-admin/includes/post.php';
function cards_check($value,$message) { if (!$value) throw new RuntimeException($message); }
wp_set_current_user(1);
$existing=get_option('hs_article_cards_fixture');
if (is_array($existing)) {
    wp_set_password(getenv('WP_CARDS_AUTHOR_PASSWORD'),$existing['author']);
    wp_set_password(getenv('WP_CARDS_SUBSCRIBER_PASSWORD'),$existing['subscriber']);
    wp_update_post(['ID'=>$existing['draft'],'post_status'=>'draft','post_content'=>'<p>Первый абзац.</p><p>Последний абзац.</p>']);
    echo wp_json_encode($existing)."\n";
    return;
}
$category=wp_insert_term('Гайды карточек','category');
$category_id=is_wp_error($category) ? (int)get_term_by('name','Гайды карточек','category')->term_id : $category['term_id'];
$source=wp_insert_post(['post_title'=>'Статья карточек: длинное русское название и советы по игре в Hearthstone','post_status'=>'publish','post_content'=>'<p>Исходный материал.</p>','post_category'=>[$category_id]]);
$private=wp_insert_post(['post_title'=>'Секретный материал карточек','post_status'=>'private']);
$protected=wp_insert_post(['post_title'=>'Защищённый материал карточек','post_status'=>'publish','post_password'=>'fixture-only']);
$draft=wp_insert_post(['post_title'=>'Черновик карточек','post_status'=>'draft','post_content'=>'<p>Первый абзац.</p><p>Последний абзац.</p>']);
$block_post=wp_insert_post(['post_title'=>'Карточка в блочном редакторе','post_status'=>'draft']);
update_option('classic-editor-allow-users','allow');
update_post_meta($block_post,'classic-editor-remember','block-editor');
$author=wp_insert_user(['user_login'=>'cards-author','user_pass'=>getenv('WP_CARDS_AUTHOR_PASSWORD'),'user_email'=>'cards-author@example.invalid','role'=>'author']);
$author_post=wp_insert_post(['post_author'=>$author,'post_status'=>'draft','post_title'=>'Проверка прав автора','post_content'=>'<p>Текст автора.</p>']);
$subscriber=wp_insert_user(['user_login'=>'cards-subscriber','user_pass'=>getenv('WP_CARDS_SUBSCRIBER_PASSWORD'),'user_email'=>'cards-subscriber@example.invalid','role'=>'subscriber']);
$local=HS_Article_Cards_Catalog::posts('manacost','карточек',$category_id,1);
cards_check(count($local['items'])===1 && $local['items'][0]['id']===$source,'Local category/search excludes non-public posts');
foreach ([$private,$protected,$draft] as $id) cards_check(is_wp_error(HS_Article_Cards_Catalog::article('manacost',$id)),'Cannot insert a non-public article');
cards_check(!is_wp_error(HS_Article_Cards_Catalog::article('manacost',$source)),'Public source resolves');
$remote=HS_Article_Cards_Catalog::posts('koloda','',14,1);
cards_check($remote['items'][0]['id']===101 && $remote['items'][0]['date']==='2026-10-23','Provider metadata normalizes');
cards_check(HS_Article_Cards_Catalog::categories('koloda',2)['items'][0]['id']===16,'Second category page is available');
$html=HS_Article_Cards_Render::html($remote['items'][0],'compact');
cards_check(wp_kses_post($html)===$html,'Author KSES preserves complete card');
cards_check(!str_contains(strip_tags($html),'kolodahearthstone.com'),'Visible card hides URL');
$revision_source=wp_insert_post(['post_title'=>'Проверка истории карточки','post_content'=>wp_slash($html),'post_status'=>'draft']);
wp_save_post_revision($revision_source);
$revisions=wp_get_post_revisions($revision_source);
$revision=reset($revisions);
cards_check($revision instanceof WP_Post,'Card revision exists');
wp_update_post(['ID'=>$revision_source,'post_content'=>'<p>Другое содержимое.</p>']);
wp_restore_post_revision($revision->ID);
cards_check(get_post($revision_source)->post_content===$html,'Revision restores exact card HTML');
$autosave=wp_create_post_autosave(['post_ID'=>$revision_source,'post_type'=>'post','post_content'=>$html.'<p>Автосохранение.</p>','post_title'=>'Проверка истории карточки']);
cards_check(!is_wp_error($autosave) && str_contains(wp_get_post_autosave($revision_source,1)->post_content,'hs-article-card'),'Autosave retains card');
update_option('hs_article_cards_fixture',compact('source','draft','author','author_post','subscriber','category_id','block_post'),false);
echo wp_json_encode(compact('source','draft','author','author_post','subscriber','category_id','block_post'))."\n";
