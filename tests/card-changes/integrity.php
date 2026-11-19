<?php
if (wp_get_environment_type() !== 'local' || wp_parse_url(home_url(), PHP_URL_HOST) !== '127.0.0.1') throw new RuntimeException('Disposable site required');
function cc_check($value, $message) { if (!$value) throw new RuntimeException($message); }
require_once ABSPATH . 'wp-admin/includes/post.php';
wp_set_current_user(1);
$fixture = get_option('hs_card_changes_fixture');
$draft = get_post($fixture['draft']);
cc_check($draft->post_status === 'publish', 'Draft was published through the editor');
cc_check(substr_count($draft->post_content, '[hs_card_change ') === 4 && str_contains($draft->post_content, '[hs_card_changes columns="2"'), 'Stored post_content keeps the shortcodes');
cc_check(!str_contains($draft->post_content, 'api.kolodahearthstone.com') && !str_contains($draft->post_content, '<img'), 'Stored content holds no markup or URLs, only hashes');
cc_check(str_contains($draft->post_content, 'Первый абзац') && str_contains($draft->post_content, 'Последний абзац'), 'Surrounding text untouched');
// Author role: KSES keeps the shortcode text byte for byte.
cc_check(wp_kses_post($draft->post_content) === $draft->post_content, 'KSES does not alter the shortcodes');
// Revisions and autosaves restore the exact text.
$copy = wp_insert_post(['post_title' => 'История изменений карт', 'post_status' => 'draft', 'post_content' => wp_slash($draft->post_content)]);
wp_save_post_revision($copy);
$revision = current(wp_get_post_revisions($copy));
cc_check($revision instanceof WP_Post, 'Revision exists');
wp_update_post(['ID' => $copy, 'post_content' => '<p>Другое.</p>']);
wp_restore_post_revision($revision->ID);
cc_check(get_post($copy)->post_content === $draft->post_content, 'Revision restores the exact shortcode text');
$autosave = wp_create_post_autosave(['post_ID' => $copy, 'post_type' => 'post', 'post_content' => $draft->post_content . '<p>Автосохранение.</p>', 'post_title' => 'История изменений карт']);
cc_check(!is_wp_error($autosave) && str_contains(wp_get_post_autosave($copy, 1)->post_content, '[hs_card_change '), 'Autosave keeps the shortcodes');
// Provider outage: a never-cached card renders plates without a fatal; cached pairs keep rendering.
update_option('hs_cc_fixture_mode', 'down', false);
delete_transient('hs_cc_cooldown');
$offline = do_shortcode('[hs_card_change card="BE_999" date="2026-11-03" plates="mana:1:2"]');
cc_check(str_contains($offline, 'hs-cc__item--text') && str_contains($offline, '<b>2</b>') && !str_contains($offline, '<img'), 'Outage renders plates without images');
$cached = do_shortcode('[hs_card_change card="BE_036" date="2026-11-03 15:55"]');
cc_check(substr_count($cached, '<img') === 2, 'Cached pair keeps rendering during an outage');
cc_check(get_transient('hs_cc_cooldown') !== false, 'Failure starts the cooldown');
delete_option('hs_cc_fixture_mode');
delete_transient('hs_cc_cooldown');
$article = get_post($fixture['article']);
$content = apply_filters('the_content', $article->post_content);
cc_check(substr_count($content, '<figure class="hs-cc__item') === 8, 'Article renders eight changes');
cc_check(!str_contains($content, '<p><div') && !str_contains($content, '</div></p>'), 'No invalid paragraph nesting around the blocks');
echo wp_json_encode(['ok' => true, 'figures' => 8, 'revision_exact' => true, 'outage_degrades' => true]) . "\n";
