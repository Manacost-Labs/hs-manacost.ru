<?php
require_once ABSPATH . 'wp-admin/includes/post.php';
function cc_check($value, $message) { if (!$value) throw new RuntimeException($message); }
wp_set_current_user(1);
$article_content = <<<'HTML'
<p>Патч 37.0.2: ослабленные карты.</p>
[hs_card_changes columns="2"]
[hs_card_change card="BE_036" date="2026-11-03 15:55"]
[hs_card_change card="JAIL_444" date="2026-11-03 15:55"]
[hs_card_change card="BE_285" date="2026-11-03 15:55"]
[hs_card_change card="BG36_362" mode="bg" date="2026-11-03 15:55"]
[/hs_card_changes]
<p>По одному в ряд, крупно и с подписями:</p>
[hs_card_changes columns="1" size="l" captions="yes"]
[hs_card_change card="BE_036" date="2026-11-03 15:55"]
[hs_card_change card="JAIL_444" date="2026-11-03 15:55"]
[/hs_card_changes]
<p>Одно изменение с ручными плашками:</p>
[hs_card_change card="BE_285" date="2026-11-03 15:55" plates="health:3:4;text"]
<p>Патча после этой даты нет — одна текущая карта:</p>
[hs_card_change card="BE_036" date="2026-11-20" plates="none"]
<p>Конец статьи.</p>
HTML;
$existing = get_option('hs_card_changes_fixture');
if (is_array($existing)) {
    wp_set_password(getenv('WP_CC_AUTHOR_PASSWORD'), $existing['author']);
    wp_set_password(getenv('WP_CC_SUBSCRIBER_PASSWORD'), $existing['subscriber']);
    wp_update_post(['ID' => $existing['draft'], 'post_status' => 'draft', 'post_content' => '<p>Первый абзац.</p><p>Последний абзац.</p>']);
    wp_update_post(['ID' => $existing['block_post'], 'post_content' => '']);
    wp_update_post(['ID' => $existing['article'], 'post_content' => wp_slash($article_content)]);
    do_action('hs_card_changes_warm', $existing['article']);
    echo wp_json_encode($existing) . "\n";
    return;
}
update_option('classic-editor-allow-users', 'allow');
$draft = wp_insert_post(['post_title' => 'Изменения карт: классический редактор', 'post_status' => 'draft', 'post_content' => '<p>Первый абзац.</p><p>Последний абзац.</p>']);
$block_post = wp_insert_post(['post_title' => 'Изменения карт: блочный редактор', 'post_status' => 'draft']);
update_post_meta($block_post, 'classic-editor-remember', 'block-editor');
$article = wp_insert_post(['post_title' => 'Патч 37.0.2: изменения карт', 'post_status' => 'publish', 'post_date_gmt' => '2026-11-03 15:55:00', 'post_date' => '2026-11-03 15:55:00', 'post_content' => wp_slash($article_content)]);
cc_check($article > 0, 'Article created');
// Page views only read the cache; the background warm-up (cron in production) fills it.
do_action('hs_card_changes_warm', $article);
$author = wp_insert_user(['user_login' => 'cc-author', 'user_pass' => getenv('WP_CC_AUTHOR_PASSWORD'), 'user_email' => 'cc-author@example.invalid', 'role' => 'author']);
$author_post = wp_insert_post(['post_author' => $author, 'post_status' => 'draft', 'post_title' => 'Проверка прав автора', 'post_content' => '<p>Текст автора.</p>']);
$subscriber = wp_insert_user(['user_login' => 'cc-subscriber', 'user_pass' => getenv('WP_CC_SUBSCRIBER_PASSWORD'), 'user_email' => 'cc-subscriber@example.invalid', 'role' => 'subscriber']);
update_option('hs_card_changes_fixture', compact('draft', 'block_post', 'article', 'author', 'author_post', 'subscriber'), false);
echo wp_json_encode(compact('draft', 'block_post', 'article', 'author', 'author_post', 'subscriber')) . "\n";
