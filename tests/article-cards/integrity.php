<?php
if (wp_get_environment_type()!=='local'||wp_parse_url(home_url(),PHP_URL_HOST)!=='127.0.0.1') throw new RuntimeException('Disposable site required');
$fixture=get_option('hs_article_cards_fixture');
$post=get_post($fixture['draft']);
if ($post->post_status!=='publish' || substr_count($post->post_content,'class="hs-article-card ')!==8) throw new RuntimeException('Published cards missing');
foreach (['Первый абзац.','Последний абзац.','hs-inline-image','Символ маны','target="_blank"','noopener','hs-article-card--cover','hs-article-card--compact','hs-article-card--grid','hs-article-card--list','hs-article-cards--columns-3','hs-article-card--radius-square','hs-article-cards--width-narrow','2026'] as $value) {
    if (!str_contains($post->post_content,$value)) throw new RuntimeException('Stored content lost '.$value);
}
if (get_post($fixture['source'])->post_content!=='<p>Исходный материал.</p>') throw new RuntimeException('Source article changed');
if (substr_count($post->post_content,'class="hs-article-component ')!==6) throw new RuntimeException('Published components missing');
require_once ABSPATH.'wp-admin/includes/post.php';
wp_set_current_user(1);
$component_draft=wp_insert_post(['post_title'=>'История компонентов','post_status'=>'draft','post_content'=>wp_slash($post->post_content)]);
wp_save_post_revision($component_draft);
$component_revisions=wp_get_post_revisions($component_draft);
$component_revision=reset($component_revisions);
if (!$component_revision) throw new RuntimeException('Component revision missing');
wp_update_post(['ID'=>$component_draft,'post_content'=>'<p>Изменение.</p>']);
wp_restore_post_revision($component_revision->ID);
if (get_post($component_draft)->post_content!==$post->post_content) throw new RuntimeException('Component revision changed HTML');
$component_autosave=wp_create_post_autosave(['post_ID'=>$component_draft,'post_type'=>'post','post_content'=>$post->post_content.'<p>Автосохранение.</p>','post_title'=>'История компонентов']);
if (is_wp_error($component_autosave)||substr_count(wp_get_post_autosave($component_draft,1)->post_content,'class="hs-article-component ')!==6) throw new RuntimeException('Component autosave changed HTML');
echo wp_json_encode(['ok'=>true,'published_cards'=>8,'published_components'=>6,'component_autosave_revision'=>true,'source_unchanged'=>true,'draft_autosave_revision'=>'passed in setup'])."\n";
