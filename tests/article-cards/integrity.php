<?php
if (wp_get_environment_type()!=='local'||wp_parse_url(home_url(),PHP_URL_HOST)!=='127.0.0.1') throw new RuntimeException('Disposable site required');
$fixture=get_option('hs_article_cards_fixture');
$post=get_post($fixture['draft']);
if ($post->post_status!=='publish' || substr_count($post->post_content,'class="hs-article-card ')!==6) throw new RuntimeException('Published cards missing');
foreach (['Первый абзац.','Последний абзац.','target="_blank"','noopener','hs-article-card--cover','hs-article-card--compact','hs-article-card--grid','hs-article-card--list','hs-article-cards--columns-3','hs-article-card--radius-square','hs-article-cards--width-narrow','2026'] as $value) {
    if (!str_contains($post->post_content,$value)) throw new RuntimeException('Stored content lost '.$value);
}
if (get_post($fixture['source'])->post_content!=='<p>Исходный материал.</p>') throw new RuntimeException('Source article changed');
echo wp_json_encode(['ok'=>true,'published_cards'=>6,'source_unchanged'=>true,'draft_autosave_revision'=>'passed in setup'])."\n";
