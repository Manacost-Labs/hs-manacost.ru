<?php
/**
 * Full-width archive template for deck archetypes.
 *
 * Keeps archetype pages focused on copy-ready deck cards instead of the theme sidebar.
 */

defined('ABSPATH') || exit;

get_header();

$term = get_queried_object();
$slug = $term && !is_wp_error($term) ? $term->slug : '';
?>

<div class="td-main-content-wrap td-container-wrap hs-archetype-theme-wrap">
    <div class="td-container">
        <div class="td-crumb-container">
            <?php
            if (class_exists('tagdiv_page_generator')) {
                echo tagdiv_page_generator::get_breadcrumbs(array(
                    'template' => 'archive',
                ));
            }
            ?>
        </div>

        <div class="td-pb-row">
            <div class="td-pb-span12 td-main-content">
                <div class="td-ss-main-content">
                    <main class="hs-archetype-template hs-archetype-template--full" role="main">
                        <div class="hs-archetype-template__inner">
                            <?php
                            if ($slug !== '') {
                                echo do_shortcode('[hs_deck_archetype slug="' . esc_attr($slug) . '"]');
                            } else {
                                echo '<p class="no-decks">Архетип не найден.</p>';
                            }
                            ?>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
get_footer();
