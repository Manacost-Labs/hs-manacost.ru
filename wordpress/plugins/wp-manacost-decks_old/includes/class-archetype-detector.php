<?php
/**
 * Automatic archetype detection for Hearthstone decks.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Unified_HS_Archetype_Detector {

    const TAXONOMY = 'deck_archetype';
    const META_KEY = '_hs_archetype_key';
    const META_SCORE = '_hs_archetype_score';

    public static function init() {
        add_action('save_post', array(__CLASS__, 'assign_on_save'), 80, 3);
    }

    public static function assign_on_save($post_id, $post, $update) {
        if (!$post || $post->post_type !== 'hs_deck') {
            return;
        }
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        self::assign_deck($post_id);
    }

    public static function assign_deck($post_id) {
        $post_id = absint($post_id);
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'hs_deck' || trim($post->post_title) === '') {
            return array();
        }
        if (!taxonomy_exists(self::TAXONOMY)) {
            return array();
        }

        $overwrite = (bool) apply_filters('unified_hs_archetype_overwrite_terms', false, $post_id, $post);
        $existing = wp_get_object_terms($post_id, self::TAXONOMY, array('fields' => 'ids'));
        if (!$overwrite && !is_wp_error($existing) && !empty($existing)) {
            return array();
        }

        $key = self::normalize_title($post->post_title);
        if ($key === '') {
            return array();
        }

        $label = self::label_from_title($post->post_title);
        $match = self::find_or_create_archetype($key, $label);
        if (empty($match['term_id'])) {
            return array();
        }

        wp_set_object_terms($post_id, array(absint($match['term_id'])), self::TAXONOMY, false);
        update_post_meta($post_id, self::META_KEY, $key);
        update_post_meta($post_id, self::META_SCORE, round((float) $match['score'], 3));

        return $match;
    }

    public static function assign_all_existing($batch_size = 300) {
        $updated = 0;
        $paged = 1;
        $batch_size = max(50, min(1000, absint($batch_size)));

        do {
            $post_ids = get_posts(array(
                'post_type' => 'hs_deck',
                'post_status' => array('publish', 'private', 'draft', 'pending', 'future'),
                'posts_per_page' => $batch_size,
                'paged' => $paged,
                'fields' => 'ids',
                'orderby' => 'ID',
                'order' => 'ASC',
                'no_found_rows' => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'ignore_sticky_posts' => true,
            ));

            if (empty($post_ids)) {
                break;
            }

            foreach ($post_ids as $post_id) {
                $result = self::assign_deck($post_id);
                if (!empty($result['term_id'])) {
                    $updated++;
                }
            }

            $paged++;
        } while (count($post_ids) === $batch_size);

        return $updated;
    }

    public static function normalize_title($title) {
        $title = wp_strip_all_tags(html_entity_decode((string) $title, ENT_QUOTES, 'UTF-8'));
        $title = str_replace(array('ё', 'Ё'), array('е', 'е'), hs_mb_lower($title));
        $title = preg_replace('/[\[\(\{].*?[\]\)\}]/u', ' ', $title);
        $title = preg_replace('/[^0-9\p{L}]+/u', ' ', $title);
        if (!is_string($title)) {
            return '';
        }

        $stop_words = apply_filters('unified_hs_archetype_stop_words', array(
            'deck', 'decks', 'hs', 'hearthstone', 'manacost', 'meta', 'new', 'best',
            'standard', 'wild', 'classic', 'brawl', 'arena',
            'колода', 'колоды', 'гайд', 'топ', 'лучший', 'новая', 'новый', 'мета',
            'стандарт', 'вольный', 'классический', 'потасовка', 'арена',
            'legend', 'легенда', 'rank', 'ранг', 'v', 'ver', 'version',
        ));

        $tokens = array();
        foreach (preg_split('/\s+/u', trim($title)) as $token) {
            $token = trim($token);
            if ($token === '' || in_array($token, $stop_words, true)) {
                continue;
            }
            if (preg_match('/^(v|ver|version)?\d{1,4}$/', $token)) {
                continue;
            }
            $tokens[] = $token;
        }

        return trim(implode(' ', $tokens));
    }

    private static function label_from_title($title) {
        $label = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags((string) $title)));
        if ($label === '') {
            return 'Архетип';
        }

        return wp_trim_words($label, 8, '');
    }

    private static function find_or_create_archetype($key, $label) {
        $exact_terms = get_terms(array(
            'taxonomy' => self::TAXONOMY,
            'hide_empty' => false,
            'number' => 1,
            'meta_query' => array(
                array(
                    'key' => self::META_KEY,
                    'value' => $key,
                    'compare' => '=',
                ),
            ),
        ));

        if (!is_wp_error($exact_terms) && !empty($exact_terms)) {
            return array('term_id' => $exact_terms[0]->term_id, 'score' => 1, 'matched' => 'exact');
        }

        $best = self::find_similar_archetype($key);
        $threshold = (float) apply_filters('unified_hs_archetype_similarity_threshold', 0.9, $key, $label);
        if (!empty($best['term_id']) && $best['score'] >= $threshold) {
            return $best;
        }

        $slug = self::slug_from_key($key);
        $result = wp_insert_term($label, self::TAXONOMY, array('slug' => $slug));
        if (is_wp_error($result) && $result->get_error_code() === 'term_exists') {
            $term_id = absint($result->get_error_data());
        } elseif (is_wp_error($result) || empty($result['term_id'])) {
            return array();
        } else {
            $term_id = absint($result['term_id']);
        }

        update_term_meta($term_id, self::META_KEY, $key);
        return array('term_id' => $term_id, 'score' => 1, 'matched' => 'created');
    }

    private static function find_similar_archetype($key) {
        $terms = get_terms(array(
            'taxonomy' => self::TAXONOMY,
            'hide_empty' => false,
            'number' => 500,
        ));
        if (is_wp_error($terms) || empty($terms)) {
            return array();
        }

        $best = array('term_id' => 0, 'score' => 0, 'matched' => 'similar');
        foreach ($terms as $term) {
            $term_key = get_term_meta($term->term_id, self::META_KEY, true);
            if ($term_key === '') {
                $term_key = self::normalize_title($term->name);
            }
            if ($term_key === '') {
                continue;
            }

            $score = self::similarity_score($key, $term_key);
            if ($score > $best['score']) {
                $best = array('term_id' => $term->term_id, 'score' => $score, 'matched' => 'similar');
            }
        }

        return $best;
    }

    private static function similarity_score($a, $b) {
        if ($a === $b) {
            return 1;
        }

        $tokens_a = array_unique(array_filter(explode(' ', $a)));
        $tokens_b = array_unique(array_filter(explode(' ', $b)));
        $union = array_unique(array_merge($tokens_a, $tokens_b));
        $intersection = array_intersect($tokens_a, $tokens_b);
        $jaccard = !empty($union) ? count($intersection) / count($union) : 0;

        $max_len = max(strlen($a), strlen($b));
        $levenshtein_score = $max_len > 0 ? 1 - (levenshtein($a, $b) / $max_len) : 0;
        similar_text($a, $b, $similar_percent);

        return max($jaccard, ($levenshtein_score * 0.55) + (($similar_percent / 100) * 0.45));
    }

    private static function slug_from_key($key) {
        $slug = sanitize_title($key);
        if ($slug === '') {
            $slug = 'archetype-' . substr(md5($key), 0, 12);
        }
        return $slug;
    }
}
