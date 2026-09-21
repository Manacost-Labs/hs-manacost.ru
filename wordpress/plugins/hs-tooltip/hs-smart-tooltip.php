<?php
/**
 * Plugin Name: HS Smart Tooltip
 * Description: Adds Hearthstone card tooltips using a prebuilt dictionary.
 * Version: 1.1.9
 * Requires at least: 5.9
 * Requires PHP: 8.0
 * Text Domain: hs-smart-tooltip
 * Domain Path: /languages
 *
 * @package HS_Smart_Tooltip
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HS_SMART_TOOLTIP_VERSION', '1.1.9');
define('HS_SMART_TOOLTIP_FRONTEND_SCRIPT', 'hs-tooltip-v115.js');

const HS_TOOLTIP_CACHE_GROUP = 'hs_smart_tooltip';
const HS_TOOLTIP_PROXY_CACHE_GROUP = 'hs_smart_tooltip_img';
const HS_TOOLTIP_BGS_API_URL = 'https://api.hearthstonejson.com/v1/latest/ruRU/cards.json';
const HS_TOOLTIP_BGS_ART_BASE = 'https://art.hearthstonejson.com/v1/bgs/latest/ruRU/256x/';

// Image proxy hard limits.
const HS_TOOLTIP_PROXY_MAX_BYTES = 3145728; // 3 MiB per image
const HS_TOOLTIP_PROXY_ALLOWED_MIME = [
    'image/png',
    'image/jpeg',
    'image/webp',
    'image/gif',
];

// Admin dictionary upload hard limit.
const HS_TOOLTIP_UPLOAD_MAX_BYTES = 67108864; // 64 MiB

// AJAX search: consistent result cap across main and BG endpoints.
const HS_TOOLTIP_SEARCH_LIMIT = 15;

require_once __DIR__ . '/includes/auto-update.php';

/**
 * Build dictionary index for fast lookups.
 *
 * @param array<string, mixed> $dictionary
 * @return array{first_words: array<string, bool>, max_words: int}
 */
function hs_smart_tooltip_build_dictionary_index(array $dictionary): array
{
    $first_words = [];
    $max_words = 1;
    $word_pattern = '/\\p{L}[\\p{L}\\p{M}\\p{N}]*|\\p{N}+/u';

    foreach ($dictionary as $key => $_value) {
        if (!is_string($key) || $key === '') {
            continue;
        }
        if (preg_match($word_pattern, $key, $match) === 1) {
            $first = mb_strtolower($match[0], 'UTF-8');
            $first_words[$first] = true;
        }
        $count = preg_match_all($word_pattern, $key);
        if ($count && $count > $max_words) {
            $max_words = (int) $count;
        }
    }

    if ($max_words > 5) {
        $max_words = 5;
    }

    return [
        'first_words' => $first_words,
        'max_words' => $max_words,
    ];
}

/**
 * Normalize confusable Cyrillic/Latin lookalikes for lookup fallback.
 */
function hs_smart_tooltip_normalize_confusables(string $text): string
{
    $map = [
        'а' => 'a',
        'е' => 'e',
        'о' => 'o',
        'р' => 'p',
        'с' => 'c',
        'х' => 'x',
        'у' => 'y',
        'к' => 'k',
        'м' => 'm',
        'т' => 't',
        'н' => 'h',
        'в' => 'b',
        'і' => 'i',
        'ї' => 'i',
        'ё' => 'e',
        'й' => 'i',
    ];
    return strtr($text, $map);
}

/**
 * Normalize a string for search: lowercase, fold cyrillic/latin confusables,
 * and reduce every non-letter/digit run to a single space. The result is a
 * space-separated bag of word tokens used both for the search blob and for the
 * incoming query, so the two are always compared on the same footing.
 */
function hs_smart_tooltip_search_normalize(string $s): string
{
    $s = mb_strtolower($s, 'UTF-8');
    $s = hs_smart_tooltip_normalize_confusables($s);
    $s = preg_replace('/[^\\p{L}\\p{N}]+/u', ' ', $s);
    return trim($s ?? '');
}

/**
 * Split a raw query into a normalized string plus its word tokens. Tokens
 * shorter than 2 chars are dropped: they match almost every entry and would
 * force a full-table scan on degenerate queries like "а а".
 *
 * @return array{norm: string, tokens: array<int, string>}
 */
function hs_smart_tooltip_search_tokens(string $term): array
{
    $norm = hs_smart_tooltip_search_normalize($term);
    if ($norm === '') {
        return ['norm' => '', 'tokens' => []];
    }
    $tokens = preg_split('/\\s+/u', $norm, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $tokens = array_values(array_filter($tokens, static fn($t) => mb_strlen($t, 'UTF-8') >= 2));
    return ['norm' => $norm, 'tokens' => $tokens];
}

/**
 * Rank an HSJSON card id so dedupe can keep one canonical entry per display
 * name. Lower is more canonical: vanilla (VAN_) reprints lose to everything,
 * Core reprints lose slightly, then shorter ids win.
 */
function hs_smart_tooltip_search_id_rank(string $id): int
{
    $rank = mb_strlen($id);
    if (str_starts_with($id, 'VAN_')) {
        $rank += 1000;
    }
    if (str_starts_with($id, 'CORE_')) {
        $rank += 10;
    }
    return $rank;
}

/**
 * Build a flat, ranked-search-ready catalog from the main dictionary.
 *
 * One entry per distinct display name (reprints deduped). Each entry carries a
 * `_blob` of normalized searchable text: the canonical name, the same name with
 * separators stripped (so apostrophe names like "Гул'дан" match "гулдан"), the
 * id, and every morphological form from `by_name` that resolves to this card.
 *
 * @param array<string, mixed> $by_id
 * @param array<string, mixed> $by_name
 * @return array<int, array{id: string, name: string, img: string, rarity: string, _nl: string, _blob: string}>
 */
function hs_smart_tooltip_build_search_catalog(array $by_id, array $by_name): array
{
    // 1) One canonical id per display name.
    $best = [];
    foreach ($by_id as $id => $entry) {
        if (!is_string($id) || $id === '' || ctype_digit($id) || !is_array($entry)) {
            continue;
        }
        $name = isset($entry['name']) && is_string($entry['name']) ? $entry['name'] : '';
        if ($name === '') {
            continue;
        }
        $key = mb_strtolower($name, 'UTF-8');
        if (isset($best[$key]) && hs_smart_tooltip_search_id_rank($id) >= hs_smart_tooltip_search_id_rank($best[$key]['id'])) {
            continue;
        }
        $best[$key] = [
            'id' => $id,
            'name' => $name,
            'img' => isset($entry['img']) && is_string($entry['img']) ? $entry['img'] : '',
            'rarity' => isset($entry['rarity']) && is_string($entry['rarity']) ? $entry['rarity'] : 'common',
        ];
    }

    // 2) Collect morphological forms per canonical name.
    $aliases = [];
    foreach ($by_name as $form => $entry) {
        if (!is_string($form) || $form === '' || !is_array($entry)) {
            continue;
        }
        $cname = isset($entry['name']) && is_string($entry['name']) ? mb_strtolower($entry['name'], 'UTF-8') : '';
        if ($cname === '' || !isset($best[$cname])) {
            continue;
        }
        $fn = hs_smart_tooltip_search_normalize($form);
        if ($fn !== '') {
            $aliases[$cname][$fn] = true;
        }
    }

    // 3) Assemble the flat catalog.
    $catalog = [];
    foreach ($best as $key => $card) {
        $nl = hs_smart_tooltip_search_normalize($card['name']);
        $parts = [$nl, str_replace(' ', '', $nl), hs_smart_tooltip_search_normalize($card['id'])];
        if (!empty($aliases[$key])) {
            $parts[] = implode(' ', array_keys($aliases[$key]));
        }
        $card['_nl'] = $nl;
        $card['_blob'] = trim(implode(' ', array_filter($parts, static fn($p) => $p !== '')));
        $catalog[] = $card;
    }
    return $catalog;
}

/**
 * Get the cached card search catalog (object cache keyed by dictionary mtime,
 * plus an in-request static). Built from the full dictionary (with forms) only
 * on a cold cache — admin/AJAX context, never the front-end hot path.
 *
 * @return array<int, array{id: string, name: string, img: string, rarity: string, _nl: string, _blob: string}>
 */
function hs_smart_tooltip_get_search_catalog(): array
{
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }
    $dict_path = plugin_dir_path(__FILE__) . 'hs_dictionary.json';
    $mtime = is_readable($dict_path) ? (filemtime($dict_path) ?: 0) : 0;
    $cache_key = 'search_catalog_v3_' . $mtime;

    $mem = wp_cache_get($cache_key, HS_TOOLTIP_CACHE_GROUP, false, $found);
    if ($found && is_array($mem)) {
        return $cached = $mem;
    }
    $bundle = hs_smart_tooltip_get_dictionary(true);
    $catalog = hs_smart_tooltip_build_search_catalog($bundle['by_id'] ?? [], $bundle['by_name'] ?? []);
    wp_cache_set($cache_key, $catalog, HS_TOOLTIP_CACHE_GROUP, 6 * HOUR_IN_SECONDS);
    return $cached = $catalog;
}

/**
 * True when every token starts a word inside the (space-separated) blob.
 *
 * @param array<int, string> $tokens
 */
function hs_smart_tooltip_all_word_starts(string $blob, array $tokens): bool
{
    foreach ($tokens as $tok) {
        $pos = strpos($blob, $tok);
        $at_word_start = false;
        while ($pos !== false) {
            if ($pos === 0 || $blob[$pos - 1] === ' ') {
                $at_word_start = true;
                break;
            }
            $pos = strpos($blob, $tok, $pos + 1);
        }
        if (!$at_word_start) {
            return false;
        }
    }
    return true;
}

/**
 * Run a ranked, multi-token AND search over a prepared catalog.
 *
 * Every query token must appear in an entry's `_blob`. Results are ordered:
 * exact name → name starts with query → every token starts a word → substring,
 * then by name length and alphabetically. Returns the full catalog entries
 * (the caller projects the public fields it needs).
 *
 * @param array<int, array{name: string, _nl: string, _blob: string}> $catalog
 * @return array<int, array<string, mixed>>
 */
function hs_smart_tooltip_run_search(array $catalog, string $term, int $limit): array
{
    $parsed = hs_smart_tooltip_search_tokens($term);
    $norm = $parsed['norm'];
    $tokens = $parsed['tokens'];
    if ($norm === '' || mb_strlen($norm, 'UTF-8') < 2 || !$tokens) {
        return [];
    }

    $scored = [];
    foreach ($catalog as $entry) {
        $blob = $entry['_blob'] ?? '';
        if ($blob === '') {
            continue;
        }
        $matches_all = true;
        foreach ($tokens as $tok) {
            if (strpos($blob, $tok) === false) {
                $matches_all = false;
                break;
            }
        }
        if (!$matches_all) {
            continue;
        }
        $nl = $entry['_nl'] ?? '';
        if ($nl === $norm) {
            $score = 0;
        } elseif (str_starts_with($nl, $norm)) {
            $score = 1;
        } elseif (hs_smart_tooltip_all_word_starts($blob, $tokens)) {
            $score = 2;
        } else {
            $score = 3;
        }
        $name = isset($entry['name']) && is_string($entry['name']) ? $entry['name'] : '';
        $scored[] = ['s' => $score, 'len' => mb_strlen($name, 'UTF-8'), 'name' => $name, 'entry' => $entry];
    }

    usort($scored, static function (array $a, array $b): int {
        return [$a['s'], $a['len'], $a['name']] <=> [$b['s'], $b['len'], $b['name']];
    });

    $out = [];
    foreach ($scored as $row) {
        $out[] = $row['entry'];
        if (count($out) >= $limit) {
            break;
        }
    }
    return $out;
}

/**
 * Load dictionary bundle: by_name/by_id + index.
 *
 * @return array{
 *   by_name: array<string, mixed>,
 *   by_id: array<string, mixed>,
 *   first_words: array<string, bool>,
 *   max_words: int,
 *   mtime: int
 * }
 */
/**
 * Load the main card dictionary.
 *
 * @param bool $with_forms When false (default), the `by_name` map of
 *                         morphological forms is dropped before the bundle
 *                         is cached — shaving ~10 MB off the common path
 *                         (shortcode rendering, AJAX search), which only
 *                         needs `by_id`. Pass true from the auto-replace
 *                         pipeline where all declined forms matter.
 * @return array{by_name: array, by_id: array, first_words: array, max_words: int, mtime: int}
 */
function hs_smart_tooltip_get_dictionary(bool $with_forms = false): array
{
    static $slim = null;
    static $full = null;
    if ($with_forms && is_array($full)) {
        return $full;
    }
    if (!$with_forms && is_array($slim)) {
        return $slim;
    }

    $empty = ['by_name' => [], 'by_id' => [], 'first_words' => [], 'max_words' => 1, 'mtime' => 0];

    $dict_path = plugin_dir_path(__FILE__) . 'hs_dictionary.json';
    if (!is_readable($dict_path)) {
        return $with_forms ? ($full = $empty) : ($slim = $empty);
    }
    $mtime = filemtime($dict_path) ?: 0;
    $cache_key = $with_forms ? 'dictionary_full' : 'dictionary_slim';

    $cached = wp_cache_get($cache_key, HS_TOOLTIP_CACHE_GROUP, false, $found);
    if ($found && is_array($cached) && ($cached['mtime'] ?? 0) === $mtime) {
        return $with_forms ? ($full = $cached) : ($slim = $cached);
    }

    $raw = file_get_contents($dict_path);
    $data = $raw !== false ? json_decode($raw, true) : null;
    unset($raw);
    if (!is_array($data)) {
        $empty['mtime'] = $mtime;
        return $with_forms ? ($full = $empty) : ($slim = $empty);
    }

    if (isset($data['by_name']) && is_array($data['by_name'])) {
        $by_name = $data['by_name'];
        $by_id = isset($data['by_id']) && is_array($data['by_id']) ? $data['by_id'] : [];
    } else {
        // Legacy flat dictionary = by_name only.
        $by_name = $data;
        $by_id = [];
    }
    unset($data);

    if ($with_forms) {
        $index = hs_smart_tooltip_build_dictionary_index($by_name);
        $bundle = [
            'by_name' => $by_name,
            'by_id' => $by_id,
            'first_words' => $index['first_words'],
            'max_words' => $index['max_words'],
            'mtime' => $mtime,
        ];
    } else {
        // Discard forms aggressively to keep process and object cache small.
        unset($by_name);
        $bundle = [
            'by_name' => [],
            'by_id' => $by_id,
            'first_words' => [],
            'max_words' => 1,
            'mtime' => $mtime,
        ];
    }

    wp_cache_set($cache_key, $bundle, HS_TOOLTIP_CACHE_GROUP);
    return $with_forms ? ($full = $bundle) : ($slim = $bundle);
}

function hs_smart_tooltip_tokenize(string $text): array
{
    $pattern = '/\\p{L}[\\p{L}\\p{M}\\p{N}]*|\\p{N}+|[^\\p{L}\\p{N}\\s]+|\\s+/u';
    preg_match_all($pattern, $text, $matches);
    $tokens = [];

    foreach ($matches[0] as $token) {
        $type = preg_match('/^[\\p{L}\\p{M}\\p{N}]+$/u', $token) ? 'word' : 'other';
        if (preg_match('/^\\s+$/u', $token)) {
            $type = 'space';
        }
        $tokens[] = ['value' => $token, 'type' => $type];
    }

    return $tokens;
}

/**
 * Normalize dictionary entry to {img, rarity, set_icon}. Supports legacy formats.
 *
 * @param mixed $entry Dictionary entry (string URL or array).
 * @return array{img: string, rarity: string, set_icon: string, name: string}
 */
function hs_smart_tooltip_normalize_entry($entry): array
{
    if (is_string($entry)) {
        return ['img' => $entry, 'rarity' => 'common', 'set_icon' => '', 'name' => ''];
    }
    if (is_array($entry) && isset($entry['img'])) {
        $img = is_string($entry['img']) ? $entry['img'] : '';
        $rarity = $entry['rarity'] ?? 'common';
        $valid = ['legendary', 'epic', 'rare', 'common'];
        if (!in_array($rarity, $valid, true)) {
            $rarity = 'common';
        }
        $set_icon = isset($entry['set_icon']) && is_string($entry['set_icon']) ? $entry['set_icon'] : '';
        $name = isset($entry['name']) && is_string($entry['name']) ? $entry['name'] : '';
        return ['img' => $img, 'rarity' => $rarity, 'set_icon' => $set_icon, 'name' => $name];
    }
    return ['img' => '', 'rarity' => 'common', 'set_icon' => '', 'name' => ''];
}

/**
 * Build full URL for set icon (filename relative to set_icon/).
 */
function hs_smart_tooltip_set_icon_url(string $filename): string
{
    if ($filename === '') {
        return '';
    }
    $base = plugin_dir_url(__FILE__) . 'set_icon/';
    return $base . sanitize_file_name($filename);
}

/**
 * Build proxy URL for external card image to avoid CORS issues.
 */
function hs_smart_tooltip_image_proxy_url(string $image_url): string
{
    if ($image_url === '') {
        return '';
    }
    $base = home_url('/');
    $separator = str_contains($base, '?') ? '&' : '?';
    $encoded = rawurlencode($image_url);
    return $base . $separator . 'hs_tooltip_img=' . $encoded;
}

/**
 * Allowlist of inline tags permitted inside tooltip labels.
 *
 * @return array<string, array<string, bool>>
 */
function hs_smart_tooltip_allowed_label_tags(): array
{
    return [
        'strong' => [],
        'b'      => [],
        'em'     => [],
        'i'      => [],
        'u'      => [],
        's'      => [],
        'mark'   => [],
        'small'  => [],
        'sub'    => [],
        'sup'    => [],
        'span'   => ['class' => true, 'style' => true],
        'br'     => [],
    ];
}

/**
 * Clean user-supplied shortcode content into safe inline HTML.
 *
 * Strips block-level junk that wpautop/wptexturize may inject
 * (paragraphs, stray <br> at edges, shortcode leftovers), preserves
 * allowed inline formatting (<strong>, <em>, ...), and trims wrapping
 * whitespace. Falls back to plain escape if input is empty.
 */
function hs_smart_tooltip_sanitize_label_html(?string $raw): string
{
    if ($raw === null || $raw === '') {
        return '';
    }
    // Remove block wrappers wpautop inserts between shortcode tags.
    $cleaned = preg_replace('#</?p[^>]*>#i', '', $raw) ?? $raw;
    // Collapse <br> at the very start/end (wpautop artefact).
    $cleaned = preg_replace('#^(\s*<br\s*/?>\s*)+|(\s*<br\s*/?>\s*)+$#i', '', $cleaned) ?? $cleaned;
    $cleaned = trim($cleaned);
    if ($cleaned === '') {
        return '';
    }
    return wp_kses($cleaned, hs_smart_tooltip_allowed_label_tags());
}

/**
 * Render tooltip span for a card.
 *
 * @param string $label_html Already-sanitized inline HTML label.
 * @param array{img: string, rarity: string, set_icon: string, name: string} $entry
 */
function hs_smart_tooltip_render_span(string $label_html, array $entry): string
{
    if ($label_html === '') {
        return '';
    }
    if ($entry['img'] === '') {
        return $label_html;
    }
    $image_raw = esc_url($entry['img']);
    $image = esc_url(hs_smart_tooltip_image_proxy_url($entry['img']));
    $rarity = in_array($entry['rarity'], ['common', 'rare', 'epic', 'legendary'], true) ? $entry['rarity'] : 'common';
    $icon_html = '';
    if ($entry['set_icon'] !== '') {
        $icon_url = hs_smart_tooltip_set_icon_url($entry['set_icon']);
        if ($icon_url !== '') {
            $icon_html = '<img class="hs-set-icon" src="' . esc_url($icon_url) . '" alt="" loading="lazy" aria-hidden="true">';
        }
    }
    return '<span class="hs-card-tooltip hs-rarity-' . esc_attr($rarity) .
        '" data-image="' . $image . '" data-image-raw="' . $image_raw . '">' .
        $icon_html . $label_html . '</span>';
}

function hs_smart_tooltip_wrap_text(
    string $text,
    array $dictionary,
    array $first_words,
    int $max_words
): string
{
    if ($text === '' || empty($dictionary)) {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    $tokens = hs_smart_tooltip_tokenize($text);
    $result = [];
    $count = count($tokens);
    $i = 0;

    while ($i < $count) {
        if ($tokens[$i]['type'] !== 'word') {
            $result[] = htmlspecialchars($tokens[$i]['value'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $i++;
            continue;
        }

        $current_word = mb_strtolower($tokens[$i]['value'], 'UTF-8');
        $alt_word = hs_smart_tooltip_normalize_confusables($current_word);
        if (!isset($first_words[$current_word]) && !isset($first_words[$alt_word])) {
            $result[] = htmlspecialchars($tokens[$i]['value'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $i++;
            continue;
        }

        $matched = false;
        $max_words = max(1, min(5, $max_words));
        for ($words = $max_words; $words >= 1; $words--) {
            $word_count = 0;
            $end = $i;
            while ($end < $count && $word_count < $words) {
                if ($tokens[$end]['type'] === 'word') {
                    $word_count++;
                }
                if ($word_count === $words) {
                    break;
                }
                $end++;
            }

            if ($word_count < $words) {
                continue;
            }

            $phrase = '';
            for ($k = $i; $k <= $end; $k++) {
                $phrase .= $tokens[$k]['value'];
            }

            $lookup = mb_strtolower($phrase, 'UTF-8');
            $entry = null;
            if (isset($dictionary[$lookup])) {
                $entry = hs_smart_tooltip_normalize_entry($dictionary[$lookup]);
            } else {
                $lookup_alt = hs_smart_tooltip_normalize_confusables($lookup);
                if (isset($dictionary[$lookup_alt])) {
                    $entry = hs_smart_tooltip_normalize_entry($dictionary[$lookup_alt]);
                }
            }

            if (is_array($entry)) {
                if ($entry['img'] === '') {
                    for ($k = $i; $k <= $end; $k++) {
                        $result[] = htmlspecialchars($tokens[$k]['value'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    }
                    $i = $end + 1;
                    $matched = true;
                    break;
                }
                $phrase_html = htmlspecialchars($phrase, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $result[] = hs_smart_tooltip_render_span($phrase_html, $entry);
                $i = $end + 1;
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            $result[] = htmlspecialchars($tokens[$i]['value'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $i++;
        }
    }

    return implode('', $result);
}

function hs_smart_tooltip_parse_and_wrap(string $content, array $bundle): string
{
    if (empty($bundle['by_name'])) {
        return $content;
    }
    $dictionary = $bundle['by_name'];
    $first_words = $bundle['first_words'] ?? [];
    $max_words = $bundle['max_words'] ?? 5;

    $dom = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8" ?>' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $query = '//text()[not(ancestor::a or ancestor::img or ancestor::script or ancestor::iframe'
        . ' or ancestor::*[contains(concat(" ", normalize-space(@class), " "), " hs-card-tooltip ")]'
        . ' or ancestor::*[contains(concat(" ", normalize-space(@class), " "), " hs-single-deck-container ")]'
        . ' or ancestor::*[contains(concat(" ", normalize-space(@class), " "), " deck-card ")]'
        . ' or ancestor::*[contains(concat(" ", normalize-space(@class), " "), " deck-header ")]'
        . ' or ancestor::*[contains(concat(" ", normalize-space(@class), " "), " deck-meta ")]'
        . ' or ancestor::*[contains(concat(" ", normalize-space(@class), " "), " deck-meta-secondary ")]'
        . ' or ancestor::*[contains(concat(" ", normalize-space(@class), " "), " deck-image ")]'
        . ' or ancestor::*[contains(concat(" ", normalize-space(@class), " "), " deck-actions ")]'
        . ')]';
    $text_nodes = $xpath->query($query);

    if ($text_nodes !== false) {
        foreach ($text_nodes as $text_node) {
            $text = $text_node->nodeValue ?? '';
            if (trim($text) === '') {
                continue;
            }
            $replacement = hs_smart_tooltip_wrap_text($text, $dictionary, $first_words, $max_words);
            if ($replacement === htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) {
                continue;
            }
            $parent = $text_node->parentNode;
            if ($parent === null) {
                continue;
            }
            try {
                $fragment = $dom->createDocumentFragment();
                $fragment->appendXML($replacement);
                $parent->replaceChild($fragment, $text_node);
            } catch (DOMException $e) {
                $parent->replaceChild($dom->createTextNode($text), $text_node);
            }
        }
    }

    $html = $dom->saveHTML();
    if ($html === false || $html === '') {
        return $content;
    }
    // Strip the XML prolog we injected to force UTF-8 parsing.
    $html = preg_replace('/^\s*<\?xml[^>]*\?>\s*/', '', $html) ?? $html;
    return $html;
}

function hs_smart_tooltip_content_has_shortcodes(string $content): bool
{
    return str_contains($content, '[hs_card') || str_contains($content, '[hs_bg');
}

function hs_smart_tooltip_autoreplace_enabled(string $content): bool
{
    $auto = defined('HS_SMART_TOOLTIP_AUTOREPLACE') ? (bool) HS_SMART_TOOLTIP_AUTOREPLACE : false;
    return (bool) apply_filters('hs_smart_tooltip_autoreplace', $auto, $content);
}

function hs_smart_tooltip_post_enabled(?WP_Post $post = null): bool
{
    static $cache = [];

    $post = $post ?: get_post();
    if (!$post instanceof WP_Post) {
        return false;
    }
    if (array_key_exists($post->ID, $cache)) {
        return $cache[$post->ID];
    }

    $enabled = get_post_meta($post->ID, '_hs_tooltips_enabled', true);
    $cache[$post->ID] = ($enabled !== '0');
    return $cache[$post->ID];
}

function hs_smart_tooltip_post_needs_assets(?WP_Post $post = null): bool
{
    $post = $post ?: get_post();
    if (!$post instanceof WP_Post) {
        return false;
    }
    if (!hs_smart_tooltip_post_enabled($post)) {
        return false;
    }

    $content = is_string($post->post_content) ? $post->post_content : '';
    if ($content === '') {
        return false;
    }
    if (str_contains($content, 'hs-card-tooltip') || hs_smart_tooltip_content_has_shortcodes($content)) {
        return true;
    }

    return hs_smart_tooltip_autoreplace_enabled($content);
}

/**
 * Apply tooltip transformations to HTML content.
 *
 * Shortcodes are always processed. Optional auto-replace (DOM-walk over the
 * dictionary's declined forms) is opt-in via either the
 * HS_SMART_TOOLTIP_AUTOREPLACE constant or the filter of the same name —
 * disabled by default to keep the common path fast.
 */
function hs_smart_tooltip_process_html(string $content): string
{
    static $cache = [];

    if ($content === '') {
        return $content;
    }

    $auto = hs_smart_tooltip_autoreplace_enabled($content);
    $has_shortcodes = hs_smart_tooltip_content_has_shortcodes($content);
    if (!$auto && !$has_shortcodes) {
        return $content;
    }

    $cache_key = md5(($auto ? '1' : '0') . "\n" . $content);
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    $processed = $content;
    if ($auto) {
        $bundle = hs_smart_tooltip_get_dictionary(true);
        if (!empty($bundle['by_name'])) {
            $processed = hs_smart_tooltip_parse_and_wrap($processed, $bundle);
        }
    }
    if ($has_shortcodes) {
        $processed = do_shortcode($processed);
    }

    if (count($cache) >= 16) {
        array_shift($cache);
    }
    $cache[$cache_key] = $processed;
    return $processed;
}

function hs_smart_tooltip_process_content(string $content): string
{
    if (is_admin() || is_feed() || !is_singular() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $post = get_post();
    if (!$post instanceof WP_Post) {
        return $content;
    }
    if (!hs_smart_tooltip_post_enabled($post)) {
        return $content;
    }

    return hs_smart_tooltip_process_html($content);
}

add_filter('the_content', 'hs_smart_tooltip_process_content', 20);
add_filter('get_the_content', 'hs_smart_tooltip_process_html', 20);

/**
 * Proxy remote Hearthstone images to bypass CORS.
 */
function hs_smart_tooltip_handle_image_proxy(): void
{
    $raw = isset($_GET['hs_tooltip_img']) ? wp_unslash($_GET['hs_tooltip_img']) : '';
    if (!is_string($raw) || $raw === '') {
        return;
    }
    $url = trim($raw);
    for ($i = 0; $i < 2; $i++) {
        if (str_contains($url, '%3A') || str_contains($url, '%2F')) {
            $url = rawurldecode($url);
        }
    }
    $parts = wp_parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        status_header(400);
        exit;
    }
    $scheme = strtolower($parts['scheme']);
    if ($scheme !== 'http' && $scheme !== 'https') {
        status_header(400);
        exit;
    }
    $host = strtolower($parts['host']);
    // Allowlist Blizzard/CloudFront domains used by Hearthstone images.
    $allowed = [
        'd15f34w2p8l1cc.cloudfront.net',
        'api.hearthstonejson.com',
        'art.hearthstonejson.com',
        'static.wikia.nocookie.net',
    ];
    if (!in_array($host, $allowed, true)) {
        status_header(403);
        exit;
    }

    $cache_key = md5($url);
    $cached = wp_cache_get($cache_key, HS_TOOLTIP_PROXY_CACHE_GROUP, false, $found);
    if ($found && is_array($cached) && isset($cached['body']) && is_string($cached['body'])) {
        $cached_type = isset($cached['content_type']) && is_string($cached['content_type']) && $cached['content_type'] !== ''
            ? $cached['content_type']
            : 'image/png';
        header('Content-Type: ' . $cached_type);
        header('Cache-Control: public, max-age=2592000');
        header('X-HS-Tooltip-Cache: HIT');
        echo $cached['body'];
        exit;
    }

    $response = wp_safe_remote_get($url, [
        'timeout' => 15,
        'redirection' => 3,
        'user-agent' => 'HS-Smart-Tooltip/' . HS_SMART_TOOLTIP_VERSION,
    ]);
    if (is_wp_error($response)) {
        status_header(502);
        exit;
    }
    $code = (int) wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        status_header(502);
        exit;
    }

    // Validate Content-Type against allowlist before buffering body.
    $raw_type = wp_remote_retrieve_header($response, 'content-type');
    if (is_array($raw_type)) {
        $raw_type = reset($raw_type);
    }
    $content_type = is_string($raw_type) ? strtolower(trim(explode(';', $raw_type)[0])) : '';
    if (!in_array($content_type, HS_TOOLTIP_PROXY_ALLOWED_MIME, true)) {
        status_header(415);
        exit;
    }

    $body = wp_remote_retrieve_body($response);
    if ($body === '' || strlen($body) > HS_TOOLTIP_PROXY_MAX_BYTES) {
        status_header(502);
        exit;
    }

    header('Content-Type: ' . $content_type);
    header('Cache-Control: public, max-age=2592000');
    header('X-HS-Tooltip-Cache: MISS');
    wp_cache_set(
        $cache_key,
        [
            'body' => $body,
            'content_type' => $content_type,
        ],
        HS_TOOLTIP_PROXY_CACHE_GROUP,
        30 * DAY_IN_SECONDS
    );
    echo $body;
    exit;
}

add_action('template_redirect', 'hs_smart_tooltip_handle_image_proxy');

/**
 * Shortcode: [hs_card id="EX1_116"]Optional label[/hs_card]
 */
function hs_smart_tooltip_shortcode($atts, $content = null): string
{
    $atts = shortcode_atts(['id' => ''], $atts, 'hs_card');
    $card_id = trim((string) $atts['id']);
    if ($card_id === '') {
        return $content !== null ? $content : '';
    }

    $bundle = hs_smart_tooltip_get_dictionary();
    $by_id = $bundle['by_id'] ?? [];
    $resolved = hs_smart_tooltip_resolve_card_id($by_id, $card_id);

    if ($resolved === null) {
        hs_smart_tooltip_record_missing_id($card_id);
        $fallback = ($content !== null && $content !== '')
            ? $content
            : htmlspecialchars($card_id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<span class="hs-card-missing" title="' .
            esc_attr(sprintf(__('hs_card: карта не найдена — %s', 'hs-smart-tooltip'), $card_id)) .
            '">' . $fallback . '</span>';
    }
    $card_id = $resolved;

    $entry = hs_smart_tooltip_normalize_entry($by_id[$card_id]);
    if ($content !== null && $content !== '') {
        $label_html = hs_smart_tooltip_sanitize_label_html($content);
        if ($label_html === '') {
            $label_html = htmlspecialchars(
                $entry['name'] !== '' ? $entry['name'] : $card_id,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
        }
    } else {
        $label_html = htmlspecialchars(
            $entry['name'] !== '' ? $entry['name'] : $card_id,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }

    return hs_smart_tooltip_render_span($label_html, $entry);
}

add_shortcode('hs_card', 'hs_smart_tooltip_shortcode');

/**
 * Универсальный резолвер ID карты. Терпим к разным форматам, которые
 * могли появиться в постах за время жизни плагина:
 *
 *   1. Каноническая форма HSJSON: `EX1_116`, `CATA_190h`, `Core_CS2_200`.
 *   2. Старый dbfId-числовой алиас: `106638`.
 *   3. Слаг-форма прежней админки: `106638-RANGER-GILLY`,
 *      `120074-BROXIGAR`, `120999-THE-ETERNAL-HOLD` (dbfId + английское имя
 *      в верхнем регистре через дефисы). Слаги в файл не пишем — извлекаем
 *      дефис-отрезанный dbfId-префикс.
 *   4. Любой регистр: `cata_190h`, `EX1_116`, `Cata_190H`.
 *   5. Whitespace внутри/по краям, табы (бывает после копипаста).
 *
 * Возвращает оригинальный ключ из $by_id, либо null если не нашли.
 *
 * @param array<string, mixed> $by_id
 */
function hs_smart_tooltip_resolve_card_id(array $by_id, string $needle): ?string
{
    $needle = trim($needle);
    if ($needle === '') {
        return null;
    }
    // Уберём внутренние пробелы / табы — они не должны быть в ID.
    $needle = preg_replace('/\s+/u', '', $needle) ?? $needle;
    if ($needle === '') {
        return null;
    }

    // 1. Точное совпадение — самый частый случай.
    if (isset($by_id[$needle])) {
        return $needle;
    }
    // 2. Uppercase — для постов со строчными ID (`ex1_116`).
    $upper = strtoupper($needle);
    if (isset($by_id[$upper])) {
        return $upper;
    }
    // 3. Слаг-форма «{dbfId}-...» или «{dbfId}_...» — берём числовой префикс.
    if (preg_match('/^(\d+)[-_]/', $needle, $m) && isset($by_id[$m[1]])) {
        return $m[1];
    }
    // 4. Case-insensitive (ленивый индекс) — для редких mixed-case ID.
    return hs_smart_tooltip_find_id_ci($by_id, $needle);
}

/**
 * Case-insensitive lookup в by_id. Строит map (lower → original) лениво
 * один раз за запрос и кэширует через static. Возвращает оригинальный
 * ключ или null.
 *
 * @param array<string, mixed> $by_id
 */
function hs_smart_tooltip_find_id_ci(array $by_id, string $needle): ?string
{
    static $ci_index = null;
    static $signature = '';

    // Пересобираем индекс, если словарь сменился (count + first key).
    $first = '';
    foreach ($by_id as $k => $_) { $first = (string) $k; break; }
    $sig = count($by_id) . '|' . $first;
    if ($ci_index === null || $signature !== $sig) {
        $ci_index = [];
        foreach ($by_id as $k => $_) {
            if (is_string($k)) {
                $ci_index[strtolower($k)] = $k;
            }
        }
        $signature = $sig;
    }
    return $ci_index[strtolower($needle)] ?? null;
}

/**
 * Запоминает ID-ы шорткодов, которых нет в словаре, чтобы автор увидел
 * проблему в админке. Хранится в transient на сутки, не более 50 уникальных.
 * Запись в БД происходит ТОЛЬКО при первом появлении ID (cheap по перфу).
 */
function hs_smart_tooltip_record_missing_id(string $id): void
{
    if ($id === '' || strlen($id) > 64) {
        return;
    }
    $key = 'hs_tooltip_missing_ids';
    $list = get_transient($key);
    if (!is_array($list)) {
        $list = [];
    }
    if (isset($list[$id])) {
        return; // уже знаем — не пишем в БД
    }
    if (count($list) >= 50) {
        array_shift($list);
    }
    $list[$id] = time();
    set_transient($key, $list, DAY_IN_SECONDS);
}

/**
 * Register meta boxes for Classic Editor.
 */
function hs_smart_tooltip_register_meta_boxes(): void
{
    add_meta_box(
        'hs_tooltip_toggle',
        'HS Smart Tooltip',
        'hs_smart_tooltip_render_toggle_box',
        ['post', 'page'],
        'side',
        'high'
    );

    add_meta_box(
        'hs_tooltip_search',
        'HS Card Search',
        'hs_smart_tooltip_render_search_box',
        ['post', 'page'],
        'side',
        'default'
    );
}

add_action('add_meta_boxes', 'hs_smart_tooltip_register_meta_boxes');

function hs_smart_tooltip_render_toggle_box(WP_Post $post): void
{
    $enabled = get_post_meta($post->ID, '_hs_tooltips_enabled', true);
    $checked = $enabled === '' || $enabled === '1';
    wp_nonce_field('hs_tooltip_toggle', 'hs_tooltip_toggle_nonce');
    echo '<label><input type="checkbox" name="hs_tooltips_enabled" value="1"' . checked($checked, true, false) . '> ' .
        esc_html__('Включить подсказки карт для этой записи', 'hs-smart-tooltip') . '</label>';
    echo '<p style="margin-top:6px; color:#666;">' .
        esc_html__('Отключите, если не хотите показывать тултипы карт в этой записи.', 'hs-smart-tooltip') .
        '</p>';
}

function hs_smart_tooltip_render_search_box(WP_Post $post): void
{
    wp_nonce_field('hs_tooltip_search', 'hs_tooltip_search_nonce');
    echo '<input type="text" id="hs-tooltip-search" class="widefat" placeholder="' .
        esc_attr__('Введите название карты (например, Сильвана)...', 'hs-smart-tooltip') . '">';
    echo '<div id="hs-tooltip-search-results" style="margin-top:8px; max-height:180px; overflow:auto;"></div>';
    echo '<button type="button" class="button button-primary" id="hs-tooltip-insert" style="margin-top:8px;">' .
        esc_html__('Вставить шорткод', 'hs-smart-tooltip') . '</button>';
    echo '<p style="margin-top:6px; color:#666;">' .
        esc_html__('Выберите карту и нажмите кнопку: выделенный текст обернется в шорткод, либо будет использовано имя карты.', 'hs-smart-tooltip') .
        '</p>';
}

function hs_smart_tooltip_save_post(int $post_id): void
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id)) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (!isset($_POST['hs_tooltip_toggle_nonce']) || !wp_verify_nonce(
        sanitize_text_field(wp_unslash($_POST['hs_tooltip_toggle_nonce'])),
        'hs_tooltip_toggle'
    )) {
        return;
    }

    $enabled = isset($_POST['hs_tooltips_enabled']) ? '1' : '0';
    update_post_meta($post_id, '_hs_tooltips_enabled', $enabled);
}

add_action('save_post', 'hs_smart_tooltip_save_post');

function hs_smart_tooltip_enqueue_assets(): void
{
    if (!is_singular() || !hs_smart_tooltip_post_needs_assets()) {
        return;
    }

    $base_url = plugin_dir_url(__FILE__) . 'assets/';
    wp_enqueue_style(
        'hs-smart-tooltip',
        $base_url . 'hs-tooltip.css',
        [],
        HS_SMART_TOOLTIP_VERSION,
        'all'
    );
    wp_enqueue_script(
        'hs-smart-tooltip',
        $base_url . HS_SMART_TOOLTIP_FRONTEND_SCRIPT,
        [],
        HS_SMART_TOOLTIP_VERSION,
        true
    );
}

add_action('wp_enqueue_scripts', 'hs_smart_tooltip_enqueue_assets');

/**
 * Fallback loader for themes that strip wp_head/wp_footer output.
 *
 * Only emits the inline bootstrap if the regular enqueue was NOT printed —
 * otherwise we would fire a redundant network request for the same asset.
 */
function hs_smart_tooltip_fallback_loader(): void
{
    if (!is_singular() || !hs_smart_tooltip_post_needs_assets()) {
        return;
    }
    if (wp_script_is('hs-smart-tooltip', 'done') || wp_script_is('hs-smart-tooltip', 'enqueued')) {
        return;
    }
    $src = esc_url(add_query_arg('ver', HS_SMART_TOOLTIP_VERSION, plugin_dir_url(__FILE__) . 'assets/' . HS_SMART_TOOLTIP_FRONTEND_SCRIPT));
    echo "<script>(function(){if(window.HS_TOOLTIP_LOADED)return;var s=document.createElement('script');s.src='{$src}';s.defer=true;document.head.appendChild(s);})();</script>";
}

add_action('wp_head', 'hs_smart_tooltip_fallback_loader', 99);

/**
 * Enqueue admin assets for Classic Editor meta boxes.
 */
function hs_smart_tooltip_enqueue_admin_assets(string $hook): void
{
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }
    wp_enqueue_script(
        'hs-smart-tooltip-search',
        plugin_dir_url(__FILE__) . 'assets/hs-tooltip-search.js',
        [],
        HS_SMART_TOOLTIP_VERSION,
        true
    );
    wp_enqueue_script(
        'hs-smart-tooltip-admin',
        plugin_dir_url(__FILE__) . 'assets/hs-tooltip-admin.js',
        ['jquery', 'hs-smart-tooltip-search'],
        HS_SMART_TOOLTIP_VERSION,
        true
    );
    wp_localize_script('hs-smart-tooltip-admin', 'HSTooltipAdmin', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('hs_tooltip_search'),
        'bgsAction' => 'hs_tooltip_search_bgs',
        'bgsCatalogUrl' => hs_smart_tooltip_get_bgs_client_catalog_url(),
    ]);
}

add_action('admin_enqueue_scripts', 'hs_smart_tooltip_enqueue_admin_assets');

/**
 * Store log entries for admin actions.
 *
 * @param string $message
 * @param string $level
 */
function hs_smart_tooltip_add_log(string $message, string $level = 'info'): void
{
    $logs = get_option('hs_tooltip_logs', []);
    if (!is_array($logs)) {
        $logs = [];
    }
    $user = wp_get_current_user();
    $logs[] = [
        'time' => current_time('mysql'),
        'level' => $level,
        'user' => $user instanceof WP_User ? $user->user_login : 'system',
        'message' => $message,
    ];
    if (count($logs) > 50) {
        $logs = array_slice($logs, -50);
    }
    update_option('hs_tooltip_logs', $logs, false);
}

/**
 * Register settings page under Settings menu.
 */
function hs_smart_tooltip_register_settings_page(): void
{
    add_options_page(
        'HS Smart Tooltip',
        'HS Smart Tooltip',
        'manage_options',
        'hs-smart-tooltip',
        'hs_smart_tooltip_render_settings_page'
    );
}

add_action('admin_menu', 'hs_smart_tooltip_register_settings_page');

/**
 * Render settings page for dictionary updates.
 */
function hs_smart_tooltip_render_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $dict_path = plugin_dir_path(__FILE__) . 'hs_dictionary.json';
    $is_writable = is_writable($dict_path);
    $bundle = hs_smart_tooltip_get_dictionary();
    $mtime = isset($bundle['mtime']) ? (int) $bundle['mtime'] : 0;
    $last_update = $mtime > 0 ? date_i18n('Y-m-d H:i', $mtime) : esc_html__('неизвестно', 'hs-smart-tooltip');
    $logs = get_option('hs_tooltip_logs', []);
    if (!is_array($logs)) {
        $logs = [];
    }
    $active_tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'main';
    if (!in_array($active_tab, ['main', 'bgs'], true)) {
        $active_tab = 'main';
    }
    $base_url = admin_url('options-general.php?page=hs-smart-tooltip');
    $status = isset($_GET['hs_tooltip_status']) ? sanitize_key((string) $_GET['hs_tooltip_status']) : '';
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('HS Smart Tooltip', 'hs-smart-tooltip'); ?></h1>
        <?php hs_smart_tooltip_render_status_notice($status); ?>
        <h2 class="nav-tab-wrapper">
            <a href="<?php echo esc_url($base_url . '&tab=main'); ?>" class="nav-tab <?php echo $active_tab === 'main' ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html__('Основной словарь', 'hs-smart-tooltip'); ?>
            </a>
            <a href="<?php echo esc_url($base_url . '&tab=bgs'); ?>" class="nav-tab <?php echo $active_tab === 'bgs' ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html__('Поля сражений', 'hs-smart-tooltip'); ?>
            </a>
        </h2>
        <?php if ($active_tab === 'bgs'): ?>
            <?php hs_smart_tooltip_render_bgs_settings_tab(); ?>
            <h2 style="margin-top:24px;"><?php echo esc_html__('Логи обновлений', 'hs-smart-tooltip'); ?></h2>
            <?php hs_smart_tooltip_render_logs_table($logs); ?>
            </div><?php return; ?>
        <?php endif; ?>
        <?php hs_smart_tooltip_render_main_autoupdate_section(); ?>
        <h2><?php echo esc_html__('Ручная загрузка основного словаря', 'hs-smart-tooltip'); ?></h2>
        <p><?php echo esc_html__('Текущий словарь карт обновлен:', 'hs-smart-tooltip'); ?>
            <strong><?php echo esc_html($last_update); ?></strong></p>
        <?php if (!$is_writable): ?>
            <div class="notice notice-error"><p>
                <?php echo esc_html__('Файл hs_dictionary.json недоступен для записи. Проверьте права доступа.', 'hs-smart-tooltip'); ?>
            </p></div>
        <?php endif; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
            <?php wp_nonce_field('hs_tooltip_dictionary_update', 'hs_tooltip_dictionary_nonce'); ?>
            <input type="hidden" name="action" value="hs_tooltip_dictionary_update">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('JSON словарь', 'hs-smart-tooltip'); ?></th>
                    <td>
                        <input type="file" name="hs_dictionary" accept="application/json,.json" required>
                        <p class="description"><?php echo esc_html__('Загрузите новый hs_dictionary.json для добавления карт.', 'hs-smart-tooltip'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Обновить словарь', 'hs-smart-tooltip')); ?>
        </form>

        <?php hs_smart_tooltip_render_missing_ids_section(); ?>

        <h2 style="margin-top:24px;"><?php echo esc_html__('Логи обновлений', 'hs-smart-tooltip'); ?></h2>
        <?php hs_smart_tooltip_render_logs_table($logs); ?>
    </div>
    <?php
}

/**
 * UI-блок: ID шорткодов, которых нет в словаре.
 */
function hs_smart_tooltip_render_missing_ids_section(): void
{
    $list = get_transient('hs_tooltip_missing_ids');
    if (!is_array($list) || empty($list)) {
        return;
    }
    ?>
    <h2 style="margin-top:24px;"><?php echo esc_html__('Шорткоды с ненайденными ID (за 24 ч)', 'hs-smart-tooltip'); ?></h2>
    <p style="color:#666;">
        <?php echo esc_html__('Эти ID встретились в постах, но отсутствуют в словаре. Возможно, опечатка в шорткоде или карта удалена из Hearthstone.', 'hs-smart-tooltip'); ?>
    </p>
    <table class="widefat striped" style="max-width:760px;">
        <thead><tr>
            <th><?php echo esc_html__('ID', 'hs-smart-tooltip'); ?></th>
            <th><?php echo esc_html__('Впервые увиден', 'hs-smart-tooltip'); ?></th>
        </tr></thead>
        <tbody>
            <?php foreach ($list as $missing_id => $ts): ?>
                <tr>
                    <td><code><?php echo esc_html((string) $missing_id); ?></code></td>
                    <td><?php echo esc_html(date_i18n('Y-m-d H:i', (int) $ts)); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:8px;">
        <?php wp_nonce_field('hs_tooltip_missing_clear', 'hs_tooltip_missing_nonce'); ?>
        <input type="hidden" name="action" value="hs_tooltip_missing_clear">
        <button type="submit" class="button"><?php echo esc_html__('Очистить список', 'hs-smart-tooltip'); ?></button>
    </form>
    <?php
}

function hs_smart_tooltip_handle_missing_clear(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Недостаточно прав.', 'hs-smart-tooltip'));
    }
    if (!isset($_POST['hs_tooltip_missing_nonce']) || !wp_verify_nonce(
        sanitize_text_field(wp_unslash($_POST['hs_tooltip_missing_nonce'])),
        'hs_tooltip_missing_clear'
    )) {
        wp_die(esc_html__('Неверный nonce.', 'hs-smart-tooltip'));
    }
    delete_transient('hs_tooltip_missing_ids');
    wp_safe_redirect(admin_url('options-general.php?page=hs-smart-tooltip'));
    exit;
}

add_action('admin_post_hs_tooltip_missing_clear', 'hs_smart_tooltip_handle_missing_clear');

/**
 * Handle dictionary upload from settings page.
 */
function hs_smart_tooltip_handle_dictionary_update(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Недостаточно прав.', 'hs-smart-tooltip'));
    }
    if (!isset($_POST['hs_tooltip_dictionary_nonce']) || !wp_verify_nonce(
        sanitize_text_field(wp_unslash($_POST['hs_tooltip_dictionary_nonce'])),
        'hs_tooltip_dictionary_update'
    )) {
        wp_die(esc_html__('Неверный nonce.', 'hs-smart-tooltip'));
    }
    if (!isset($_FILES['hs_dictionary']) || !is_array($_FILES['hs_dictionary'])) {
        hs_smart_tooltip_add_log('Не выбран файл словаря.', 'error');
        wp_safe_redirect(add_query_arg('hs_tooltip_status', 'nofile', wp_get_referer() ?: admin_url('options-general.php?page=hs-smart-tooltip')));
        exit;
    }
    $file = $_FILES['hs_dictionary'];
    if (!empty($file['error'])) {
        hs_smart_tooltip_add_log('Ошибка загрузки файла: ' . (string) $file['error'], 'error');
        wp_safe_redirect(add_query_arg('hs_tooltip_status', 'upload_error', wp_get_referer() ?: admin_url('options-general.php?page=hs-smart-tooltip')));
        exit;
    }
    // Guard against oversized uploads before reading into memory.
    $size = isset($file['size']) ? (int) $file['size'] : 0;
    if ($size <= 0 || $size > HS_TOOLTIP_UPLOAD_MAX_BYTES) {
        hs_smart_tooltip_add_log(sprintf('Файл словаря отклонён: размер %d байт.', $size), 'error');
        wp_safe_redirect(add_query_arg('hs_tooltip_status', 'too_large', wp_get_referer() ?: admin_url('options-general.php?page=hs-smart-tooltip')));
        exit;
    }
    $tmp = isset($file['tmp_name']) && is_string($file['tmp_name']) ? $file['tmp_name'] : '';
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        hs_smart_tooltip_add_log('Загруженный файл недоступен (is_uploaded_file).', 'error');
        wp_safe_redirect(add_query_arg('hs_tooltip_status', 'upload_error', wp_get_referer() ?: admin_url('options-general.php?page=hs-smart-tooltip')));
        exit;
    }

    $contents = file_get_contents($tmp);
    if ($contents === false || $contents === '') {
        hs_smart_tooltip_add_log('Файл словаря пустой.', 'error');
        wp_safe_redirect(add_query_arg('hs_tooltip_status', 'empty', wp_get_referer() ?: admin_url('options-general.php?page=hs-smart-tooltip')));
        exit;
    }
    $data = json_decode($contents, true);
    if (!is_array($data)) {
        hs_smart_tooltip_add_log('Файл словаря не является валидным JSON.', 'error');
        wp_safe_redirect(add_query_arg('hs_tooltip_status', 'invalid_json', wp_get_referer() ?: admin_url('options-general.php?page=hs-smart-tooltip')));
        exit;
    }
    // Minimal schema validation: must expose either by_name or by_id map.
    $has_by_name = isset($data['by_name']) && is_array($data['by_name']);
    $has_by_id   = isset($data['by_id']) && is_array($data['by_id']);
    // Legacy flat dict is treated as by_name.
    $looks_flat  = !$has_by_name && !$has_by_id && !empty($data) && is_string(array_key_first($data));
    if (!$has_by_name && !$has_by_id && !$looks_flat) {
        hs_smart_tooltip_add_log('Структура словаря не распознана (ожидались by_id / by_name).', 'error');
        wp_safe_redirect(add_query_arg('hs_tooltip_status', 'invalid_schema', wp_get_referer() ?: admin_url('options-general.php?page=hs-smart-tooltip')));
        exit;
    }
    $dict_path = plugin_dir_path(__FILE__) . 'hs_dictionary.json';
    if (!is_writable($dict_path)) {
        hs_smart_tooltip_add_log('Нет прав на запись hs_dictionary.json.', 'error');
        wp_safe_redirect(add_query_arg('hs_tooltip_status', 'not_writable', wp_get_referer() ?: admin_url('options-general.php?page=hs-smart-tooltip')));
        exit;
    }
    $written = file_put_contents($dict_path, $contents);
    if ($written === false) {
        hs_smart_tooltip_add_log('Не удалось записать hs_dictionary.json.', 'error');
        wp_safe_redirect(add_query_arg('hs_tooltip_status', 'write_error', wp_get_referer() ?: admin_url('options-general.php?page=hs-smart-tooltip')));
        exit;
    }
    wp_cache_delete('dictionary_slim', HS_TOOLTIP_CACHE_GROUP);
    wp_cache_delete('dictionary_full', HS_TOOLTIP_CACHE_GROUP);
    hs_smart_tooltip_add_log('Словарь обновлен через настройки.', 'info');
    wp_safe_redirect(add_query_arg('hs_tooltip_status', 'success', wp_get_referer() ?: admin_url('options-general.php?page=hs-smart-tooltip')));
    exit;
}

add_action('admin_post_hs_tooltip_dictionary_update', 'hs_smart_tooltip_handle_dictionary_update');

/**
 * AJAX handler: search cards by name.
 */
function hs_smart_tooltip_ajax_search(): void
{
    check_ajax_referer('hs_tooltip_search', 'nonce');
    if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
        wp_send_json_error(['message' => 'forbidden'], 403);
    }

    $raw_term = $_GET['term'] ?? '';
    $term = is_scalar($raw_term) ? sanitize_text_field((string) wp_unslash($raw_term)) : '';
    if (trim($term) === '' || mb_strlen(trim($term), 'UTF-8') < 2) {
        wp_send_json_success([]);
    }

    $catalog = hs_smart_tooltip_get_search_catalog();
    if (empty($catalog)) {
        wp_send_json_success([]);
    }

    $results = [];
    foreach (hs_smart_tooltip_run_search($catalog, $term, HS_TOOLTIP_SEARCH_LIMIT) as $entry) {
        $results[] = [
            'id' => (string) ($entry['id'] ?? ''),
            'name' => (string) ($entry['name'] ?? ''),
            'img' => (string) ($entry['img'] ?? ''),
            'rarity' => (string) ($entry['rarity'] ?? 'common'),
        ];
    }

    wp_send_json_success($results);
}

add_action('wp_ajax_hs_tooltip_search', 'hs_smart_tooltip_ajax_search');

/**
 * Add defer attribute for non-blocking load (WP < 6.3).
 * Skip if WP Rocket or Perfmatters already add defer/delay attributes.
 */
function hs_smart_tooltip_script_tag(string $tag, string $handle, string $src): string
{
    if (is_admin()) {
        return $tag;
    }
    if ($handle !== 'hs-smart-tooltip') {
        return $tag;
    }
    if (str_contains($tag, 'defer') || str_contains($tag, 'data-rocket') || str_contains($tag, 'data-perfmatters')) {
        return $tag;
    }
    return str_replace(' src=', ' defer src=', $tag);
}

add_filter('script_loader_tag', 'hs_smart_tooltip_script_tag', 10, 3);

/**
 * Exclude from WP Rocket JS minify/combine to avoid stale minified copies.
 */
function hs_smart_tooltip_rocket_minify_exclusions(array $exclusions): array
{
    $exclusions[] = 'hs-tooltip.js';
    $exclusions[] = HS_SMART_TOOLTIP_FRONTEND_SCRIPT;
    return $exclusions;
}

add_filter('rocket_exclude_js', 'hs_smart_tooltip_rocket_minify_exclusions');

/**
 * Exclude from WP Rocket "Delay JavaScript" so tooltips work on first hover.
 */
function hs_smart_tooltip_rocket_delay_exclusions(array $exclusions): array
{
    $exclusions[] = 'hs-tooltip.js';
    $exclusions[] = HS_SMART_TOOLTIP_FRONTEND_SCRIPT;
    return $exclusions;
}

add_filter('rocket_delay_js_exclusions', 'hs_smart_tooltip_rocket_delay_exclusions');

/**
 * Exclude from Perfmatters "Delay JavaScript" so tooltips work on first hover.
 */
function hs_smart_tooltip_perfmatters_delay_exclusions(array $exclusions): array
{
    $exclusions[] = 'hs-tooltip.js';
    $exclusions[] = HS_SMART_TOOLTIP_FRONTEND_SCRIPT;
    return $exclusions;
}

add_filter('perfmatters_delay_js_exclusions', 'hs_smart_tooltip_perfmatters_delay_exclusions');

function hs_smart_tooltip_load_textdomain(): void
{
    load_plugin_textdomain(
        'hs-smart-tooltip',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}

add_action('init', 'hs_smart_tooltip_load_textdomain');

/* ======================================================================
 * Battlegrounds (BG) support.
 * Separate dictionary hs_bgs_dictionary.json with { by_id: { ID: {img,name,rarity,techLevel,type} } }.
 * Images served directly from art.hearthstonejson.com (no proxy) to keep
 * the site off the hot path.
 * ====================================================================== */

function hs_smart_tooltip_get_bgs_dictionary(): array
{
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }
    $path = plugin_dir_path(__FILE__) . 'hs_bgs_dictionary.json';
    if (!is_readable($path)) {
        $cached = ['by_id' => [], 'mtime' => 0];
        return $cached;
    }
    $mtime = filemtime($path) ?: 0;
    $mem = wp_cache_get('bgs_dictionary', HS_TOOLTIP_CACHE_GROUP, false, $found);
    if ($found && is_array($mem) && ($mem['mtime'] ?? 0) === $mtime) {
        $cached = $mem;
        return $cached;
    }
    $raw = file_get_contents($path);
    $data = $raw !== false ? json_decode($raw, true) : null;
    $by_id = (is_array($data) && isset($data['by_id']) && is_array($data['by_id'])) ? $data['by_id'] : [];
    $cached = ['by_id' => $by_id, 'mtime' => $mtime];
    wp_cache_set('bgs_dictionary', $cached, HS_TOOLTIP_CACHE_GROUP);
    return $cached;
}

/**
 * Return a versioned URL for the compact BG catalog used by the editor.
 *
 * The catalog is regenerated only when the source dictionary changes. Keeping
 * it as a static file lets the CDN and browser cache it, while post.php stays
 * small and private.
 */
function hs_smart_tooltip_get_bgs_client_catalog_url(): string
{
    $plugin_path = plugin_dir_path(__FILE__);
    $dictionary_path = $plugin_path . 'hs_bgs_dictionary.json';
    $catalog_path = $plugin_path . 'assets/hs-tooltip-bgs-catalog.json';
    $source_mtime = is_readable($dictionary_path) ? (int) (filemtime($dictionary_path) ?: 0) : 0;
    if ($source_mtime === 0) {
        return '';
    }

    $catalog_mtime = is_readable($catalog_path) ? (int) (filemtime($catalog_path) ?: 0) : 0;
    if ($catalog_mtime < $source_mtime) {
        $best = [];
        $bundle = hs_smart_tooltip_get_bgs_dictionary();
        foreach (($bundle['by_id'] ?? []) as $id => $entry) {
            if (!is_string($id) || $id === '' || !is_array($entry)) {
                continue;
            }
            $name = isset($entry['name']) && is_string($entry['name']) ? $entry['name'] : '';
            if ($name === '') {
                continue;
            }
            $tech = isset($entry['techLevel']) ? (int) $entry['techLevel'] : 0;
            $type = isset($entry['type']) && is_string($entry['type']) ? $entry['type'] : '';
            $key = mb_strtolower($name, 'UTF-8') . '|' . $tech . '|' . $type;
            if (isset($best[$key]) && mb_strlen($id, 'UTF-8') >= mb_strlen($best[$key]['id'], 'UTF-8')) {
                continue;
            }
            $best[$key] = [
                'id' => $id,
                'name' => $name,
                'tech' => $tech,
                'type' => $type,
            ];
        }

        $encoded = wp_json_encode(array_values($best), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (is_string($encoded) && $encoded !== '') {
            $temporary_path = $catalog_path . '.tmp-' . getmypid();
            $written = file_put_contents($temporary_path, $encoded, LOCK_EX);
            if ($written === strlen($encoded) && @rename($temporary_path, $catalog_path)) {
                @chmod($catalog_path, 0644);
            } else {
                @unlink($temporary_path);
            }
        }
        clearstatcache(true, $catalog_path);
        $catalog_mtime = is_readable($catalog_path) ? (int) (filemtime($catalog_path) ?: 0) : 0;
    }

    if ($catalog_mtime === 0) {
        return '';
    }
    return (string) add_query_arg(
        'ver',
        (string) $catalog_mtime,
        plugin_dir_url(__FILE__) . 'assets/hs-tooltip-bgs-catalog.json'
    );
}

/**
 * Build the cached BG search catalog (one entry per distinct card; golden and
 * token variants deduped to the base card). Uses the same flat `_blob` shape as
 * the main catalog so hs_smart_tooltip_run_search() can rank both identically.
 *
 * @return array<int, array{id: string, name: string, img: string, rarity: string, tech: int, type: string, _nl: string, _blob: string}>
 */
function hs_smart_tooltip_get_bgs_search_catalog(): array
{
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }
    $bundle = hs_smart_tooltip_get_bgs_dictionary();
    $by_id = $bundle['by_id'] ?? [];
    $mtime = (int) ($bundle['mtime'] ?? 0);
    $cache_key = 'bgs_search_catalog_v3_' . $mtime;

    $mem = wp_cache_get($cache_key, HS_TOOLTIP_CACHE_GROUP, false, $found);
    if ($found && is_array($mem)) {
        return $cached = $mem;
    }

    $best = [];
    foreach ($by_id as $id => $entry) {
        if (!is_string($id) || $id === '' || !is_array($entry)) {
            continue;
        }
        $name = isset($entry['name']) && is_string($entry['name']) ? $entry['name'] : '';
        if ($name === '') {
            continue;
        }
        $tech = isset($entry['techLevel']) ? (int) $entry['techLevel'] : 0;
        $type = isset($entry['type']) && is_string($entry['type']) ? $entry['type'] : '';
        // Golden (_G) / token (_Gt) variants share name+tech+type — keep the shortest (base) id.
        $key = mb_strtolower($name, 'UTF-8') . '|' . $tech . '|' . $type;
        if (isset($best[$key]) && mb_strlen($id) >= mb_strlen($best[$key]['id'])) {
            continue;
        }
        $best[$key] = [
            'id' => $id,
            'name' => $name,
            'img' => isset($entry['img']) && is_string($entry['img']) ? $entry['img'] : '',
            'rarity' => isset($entry['rarity']) && is_string($entry['rarity']) ? $entry['rarity'] : 'common',
            'tech' => $tech,
            'type' => $type,
        ];
    }

    $catalog = [];
    foreach ($best as $card) {
        $nl = hs_smart_tooltip_search_normalize($card['name']);
        $card['_nl'] = $nl;
        $card['_blob'] = trim($nl . ' ' . str_replace(' ', '', $nl) . ' ' . hs_smart_tooltip_search_normalize($card['id']));
        $catalog[] = $card;
    }

    wp_cache_set($cache_key, $catalog, HS_TOOLTIP_CACHE_GROUP, 6 * HOUR_IN_SECONDS);
    return $cached = $catalog;
}

function hs_smart_tooltip_normalize_bgs_entry($entry): array
{
    $img = (is_array($entry) && isset($entry['img']) && is_string($entry['img'])) ? $entry['img'] : '';
    $name = (is_array($entry) && isset($entry['name']) && is_string($entry['name'])) ? $entry['name'] : '';
    $rarity = (is_array($entry) && isset($entry['rarity']) && is_string($entry['rarity'])) ? $entry['rarity'] : 'common';
    if (!in_array($rarity, ['common', 'rare', 'epic', 'legendary'], true)) {
        $rarity = 'common';
    }
    $tech = is_array($entry) && isset($entry['techLevel']) ? (int) $entry['techLevel'] : 0;
    if ($tech < 0 || $tech > 7) {
        $tech = 0;
    }
    $type = is_array($entry) && isset($entry['type']) && is_string($entry['type']) ? $entry['type'] : '';
    return ['img' => $img, 'name' => $name, 'rarity' => $rarity, 'tech' => $tech, 'type' => $type];
}

/**
 * URL для иконки уровня таверны (bg_icon/tier{N}.png).
 * Возвращает пустую строку, если файла нет.
 */
function hs_smart_tooltip_bg_tier_icon_url(int $tier): string
{
    if ($tier < 1 || $tier > 7) {
        return '';
    }
    static $cache = [];
    if (isset($cache[$tier])) {
        return $cache[$tier];
    }
    $filename = 'tier' . $tier . '.png';
    $path = plugin_dir_path(__FILE__) . 'bg_icon/' . $filename;
    if (!is_file($path)) {
        return $cache[$tier] = '';
    }
    return $cache[$tier] = plugin_dir_url(__FILE__) . 'bg_icon/' . $filename;
}

function hs_smart_tooltip_render_bgs_span(string $label_html, array $entry): string
{
    if ($label_html === '') {
        return '';
    }
    if ($entry['img'] === '') {
        return $label_html;
    }
    $img = esc_url($entry['img']);
    $tier_html = '';
    $tier = isset($entry['tech']) ? (int) $entry['tech'] : 0;
    $type = isset($entry['type']) ? (string) $entry['type'] : '';
    // Иконка уровня таверны — только для существ и заклинаний.
    // У героев, аномалий, безделушек и наград квестов уровня нет.
    $tier_eligible_types = ['MINION', 'SPELL', 'BATTLEGROUND_SPELL'];
    if ($tier >= 1 && $tier <= 7 && in_array($type, $tier_eligible_types, true)) {
        $tier_url = hs_smart_tooltip_bg_tier_icon_url($tier);
        if ($tier_url !== '') {
            $tier_html = '<img class="hs-bg-tier" src="' . esc_url($tier_url) .
                '" alt="" loading="lazy" aria-hidden="true" data-tier="' . $tier . '">';
        }
    }
    return '<span class="hs-card-tooltip hs-card-tooltip-bg hs-rarity-' . esc_attr($entry['rarity']) .
        '" data-image="' . $img . '" data-image-raw="' . $img . '">' .
        $tier_html . $label_html . '</span>';
}

/**
 * Shortcode: [hs_bg id="BG19_010"]label[/hs_bg]
 */
function hs_smart_tooltip_bgs_shortcode($atts, $content = null): string
{
    $atts = shortcode_atts(['id' => ''], $atts, 'hs_bg');
    $card_id = trim((string) $atts['id']);
    if ($card_id === '') {
        return $content !== null ? $content : '';
    }
    $bundle = hs_smart_tooltip_get_bgs_dictionary();
    $by_id = $bundle['by_id'];
    $resolved = hs_smart_tooltip_resolve_card_id($by_id, $card_id);
    if ($resolved === null) {
        hs_smart_tooltip_record_missing_id('bg:' . $card_id);
        $fallback = ($content !== null && $content !== '')
            ? $content
            : htmlspecialchars($card_id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<span class="hs-card-missing" title="' .
            esc_attr(sprintf(__('hs_bg: карта не найдена — %s', 'hs-smart-tooltip'), $card_id)) .
            '">' . $fallback . '</span>';
    }
    $card_id = $resolved;
    $entry = hs_smart_tooltip_normalize_bgs_entry($by_id[$card_id]);
    if ($content !== null && $content !== '') {
        $label_html = hs_smart_tooltip_sanitize_label_html($content);
        if ($label_html === '') {
            $label_html = htmlspecialchars(
                $entry['name'] !== '' ? $entry['name'] : $card_id,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
        }
    } else {
        $label_html = htmlspecialchars(
            $entry['name'] !== '' ? $entry['name'] : $card_id,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
    return hs_smart_tooltip_render_bgs_span($label_html, $entry);
}

add_shortcode('hs_bg', 'hs_smart_tooltip_bgs_shortcode');

/**
 * AJAX search for BG cards (admin meta box).
 */
function hs_smart_tooltip_ajax_search_bgs(): void
{
    check_ajax_referer('hs_tooltip_search', 'nonce');
    if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
        wp_send_json_error(['message' => 'forbidden'], 403);
    }
    $raw_term = $_GET['term'] ?? '';
    $term = is_scalar($raw_term) ? sanitize_text_field((string) wp_unslash($raw_term)) : '';
    if (trim($term) === '' || mb_strlen(trim($term), 'UTF-8') < 2) {
        wp_send_json_success([]);
    }
    $catalog = hs_smart_tooltip_get_bgs_search_catalog();
    if (empty($catalog)) {
        wp_send_json_success([]);
    }

    $results = [];
    foreach (hs_smart_tooltip_run_search($catalog, $term, HS_TOOLTIP_SEARCH_LIMIT) as $entry) {
        $results[] = [
            'id' => (string) ($entry['id'] ?? ''),
            'name' => (string) ($entry['name'] ?? ''),
            'img' => (string) ($entry['img'] ?? ''),
            'rarity' => (string) ($entry['rarity'] ?? 'common'),
            'tech' => (int) ($entry['tech'] ?? 0),
            'type' => (string) ($entry['type'] ?? ''),
        ];
    }
    wp_send_json_success($results);
}

add_action('wp_ajax_hs_tooltip_search_bgs', 'hs_smart_tooltip_ajax_search_bgs');

/**
 * Admin meta box: BG card search.
 */
function hs_smart_tooltip_register_bgs_meta_box(): void
{
    add_meta_box(
        'hs_tooltip_bgs_search',
        'HS Battlegrounds Search',
        'hs_smart_tooltip_render_bgs_search_box',
        ['post', 'page'],
        'side',
        'default'
    );
}

add_action('add_meta_boxes', 'hs_smart_tooltip_register_bgs_meta_box');

function hs_smart_tooltip_render_bgs_search_box(WP_Post $post): void
{
    echo '<input type="text" id="hs-tooltip-bgs-search" class="widefat" placeholder="' .
        esc_attr__('Имя BG-карты или BG-ID...', 'hs-smart-tooltip') . '">';
    echo '<div id="hs-tooltip-bgs-results" style="margin-top:8px; max-height:220px; overflow:auto;"></div>';
    echo '<button type="button" class="button button-primary" id="hs-tooltip-bgs-insert" style="margin-top:8px;">' .
        esc_html__('Вставить шорткод BG', 'hs-smart-tooltip') . '</button>';
    echo '<p style="margin-top:6px; color:#666;">' .
        esc_html__('Вставит [hs_bg id="..."] с выделенным текстом или именем карты.', 'hs-smart-tooltip') . '</p>';
}

/**
 * Rebuild hs_bgs_dictionary.json from HearthstoneJSON API.
 *
 * @return array{ok: bool, count: int, message: string}
 */
function hs_smart_tooltip_rebuild_bgs_dictionary(): array
{
    $response = wp_safe_remote_get(HS_TOOLTIP_BGS_API_URL, [
        'timeout' => 60,
        'user-agent' => 'HS-Smart-Tooltip/' . HS_SMART_TOOLTIP_VERSION,
    ]);
    if (is_wp_error($response)) {
        return ['ok' => false, 'count' => 0, 'message' => $response->get_error_message()];
    }
    $code = (int) wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        return ['ok' => false, 'count' => 0, 'message' => 'HTTP ' . $code];
    }
    $body = wp_remote_retrieve_body($response);
    unset($response);
    if ($body === '') {
        return ['ok' => false, 'count' => 0, 'message' => 'empty body'];
    }
    $cards = json_decode($body, true);
    unset($body);
    if (!is_array($cards)) {
        return ['ok' => false, 'count' => 0, 'message' => 'invalid JSON'];
    }
    $keep_types = [
        'MINION' => true,
        'HERO' => true,
        'SPELL' => true,
        'BATTLEGROUND_SPELL' => true,
        'BATTLEGROUND_ANOMALY' => true,
        'BATTLEGROUND_TRINKET' => true,
        'BATTLEGROUND_QUEST_REWARD' => true,
    ];
    $tech_to_rarity = [1 => 'common', 2 => 'common', 3 => 'rare', 4 => 'rare', 5 => 'epic', 6 => 'legendary', 7 => 'legendary'];
    $by_id = [];
    foreach ($cards as $c) {
        if (!is_array($c)) {
            continue;
        }
        if (($c['set'] ?? '') !== 'BATTLEGROUNDS') {
            continue;
        }
        $type = $c['type'] ?? '';
        if (!isset($keep_types[$type])) {
            continue;
        }
        $id = $c['id'] ?? '';
        $name = $c['name'] ?? '';
        if (!is_string($id) || $id === '' || !is_string($name) || $name === '') {
            continue;
        }
        $tech = isset($c['techLevel']) ? (int) $c['techLevel'] : 0;
        $rarity = $tech_to_rarity[$tech] ?? 'common';
        $by_id[$id] = [
            'img' => HS_TOOLTIP_BGS_ART_BASE . $id . '.png',
            'rarity' => $rarity,
            'name' => $name,
            'type' => $type,
            'techLevel' => $tech,
        ];
    }
    if (empty($by_id)) {
        return ['ok' => false, 'count' => 0, 'message' => 'no BG cards in API response'];
    }
    $path = plugin_dir_path(__FILE__) . 'hs_bgs_dictionary.json';
    $payload = wp_json_encode(['by_id' => $by_id], JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        return ['ok' => false, 'count' => 0, 'message' => 'json_encode failed'];
    }
    if (file_put_contents($path, $payload) === false) {
        return ['ok' => false, 'count' => 0, 'message' => 'write failed (check permissions)'];
    }
    wp_cache_delete('bgs_dictionary', HS_TOOLTIP_CACHE_GROUP);
    return ['ok' => true, 'count' => count($by_id), 'message' => ''];
}

function hs_smart_tooltip_handle_bgs_rebuild(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Недостаточно прав.', 'hs-smart-tooltip'));
    }
    if (!isset($_POST['hs_tooltip_bgs_nonce']) || !wp_verify_nonce(
        sanitize_text_field(wp_unslash($_POST['hs_tooltip_bgs_nonce'])),
        'hs_tooltip_bgs_rebuild'
    )) {
        wp_die(esc_html__('Неверный nonce.', 'hs-smart-tooltip'));
    }

    // Опциональный toggle авто-обновления BG-словаря.
    if (isset($_POST['hs_tooltip_bgs_auto'])) {
        $auto = $_POST['hs_tooltip_bgs_auto'] === '1';
        hs_smart_tooltip_bgs_settings_update(['auto_update' => $auto]);
        hs_smart_tooltip_bgs_reschedule_cron($auto);
    }

    $do_rebuild = isset($_POST['hs_tooltip_bgs_run']) && $_POST['hs_tooltip_bgs_run'] === '1';
    $status = 'bgs_saved';
    if ($do_rebuild) {
        $result = hs_smart_tooltip_rebuild_bgs_dictionary();
        hs_smart_tooltip_bgs_settings_update([
            'last_run'    => time(),
            'last_status' => $result['ok'] ? 'ok' : 'err',
            'last_count'  => (int) $result['count'],
        ]);
        if ($result['ok']) {
            hs_smart_tooltip_add_log(sprintf('BG-словарь обновлён из API: %d карт.', $result['count']), 'info');
            $status = 'bgs_ok';
        } else {
            hs_smart_tooltip_add_log('Ошибка обновления BG-словаря: ' . $result['message'], 'error');
            $status = 'bgs_err';
        }
    }
    $redirect = admin_url('options-general.php?page=hs-smart-tooltip&tab=bgs&hs_tooltip_status=' . $status);
    wp_safe_redirect($redirect);
    exit;
}

add_action('admin_post_hs_tooltip_bgs_rebuild', 'hs_smart_tooltip_handle_bgs_rebuild');

function hs_smart_tooltip_render_status_notice(string $status): void
{
    if ($status === '') {
        return;
    }
    $map = [
        'success'        => ['updated', __('Словарь обновлён.', 'hs-smart-tooltip')],
        'bgs_ok'         => ['updated', __('BG-словарь обновлён из API.', 'hs-smart-tooltip')],
        'bgs_saved'      => ['updated', __('Настройки автообновления BG сохранены.', 'hs-smart-tooltip')],
        'main_ok'        => ['updated', __('Основной словарь обновлён из HearthstoneJSON.', 'hs-smart-tooltip')],
        'main_saved'     => ['updated', __('Настройки автообновления сохранены.', 'hs-smart-tooltip')],
        'main_err'       => ['error',   __('Не удалось обновить основной словарь (см. лог ниже).', 'hs-smart-tooltip')],
        'main_locked'    => ['error',   __('Обновление уже выполняется. Повторите позже.', 'hs-smart-tooltip')],
        'main_health_fail' => ['error', __('Health-check не пройден: API вернул подозрительно мало карт. Файл не перезаписан.', 'hs-smart-tooltip')],
        'nofile'         => ['error',   __('Файл не выбран.', 'hs-smart-tooltip')],
        'upload_error'   => ['error',   __('Ошибка загрузки файла.', 'hs-smart-tooltip')],
        'too_large'      => ['error',   __('Файл слишком большой.', 'hs-smart-tooltip')],
        'empty'          => ['error',   __('Файл пустой.', 'hs-smart-tooltip')],
        'invalid_json'   => ['error',   __('Невалидный JSON.', 'hs-smart-tooltip')],
        'invalid_schema' => ['error',   __('Структура словаря не распознана (нужны by_id / by_name).', 'hs-smart-tooltip')],
        'not_writable'   => ['error',   __('Файл hs_dictionary.json недоступен для записи.', 'hs-smart-tooltip')],
        'write_error'    => ['error',   __('Не удалось записать hs_dictionary.json.', 'hs-smart-tooltip')],
        'bgs_err'        => ['error',   __('Не удалось обновить BG-словарь (см. лог ниже).', 'hs-smart-tooltip')],
    ];
    if (!isset($map[$status])) {
        return;
    }
    [$type, $text] = $map[$status];
    printf(
        '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
        esc_attr($type),
        esc_html($text)
    );
}

function hs_smart_tooltip_render_logs_table(array $logs): void
{
    if (empty($logs)) {
        echo '<p>' . esc_html__('Логов пока нет.', 'hs-smart-tooltip') . '</p>';
        return;
    }
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Время', 'hs-smart-tooltip') . '</th>';
    echo '<th>' . esc_html__('Уровень', 'hs-smart-tooltip') . '</th>';
    echo '<th>' . esc_html__('Пользователь', 'hs-smart-tooltip') . '</th>';
    echo '<th>' . esc_html__('Сообщение', 'hs-smart-tooltip') . '</th>';
    echo '</tr></thead><tbody>';
    foreach (array_reverse($logs) as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        echo '<tr>';
        echo '<td>' . esc_html((string) ($entry['time'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($entry['level'] ?? 'info')) . '</td>';
        echo '<td>' . esc_html((string) ($entry['user'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($entry['message'] ?? '')) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}

function hs_smart_tooltip_render_bgs_settings_tab(): void
{
    $path = plugin_dir_path(__FILE__) . 'hs_bgs_dictionary.json';
    $exists = file_exists($path);
    $is_writable = $exists ? is_writable($path) : is_writable(plugin_dir_path(__FILE__));
    $mtime = $exists ? (filemtime($path) ?: 0) : 0;
    $last_update = $mtime > 0 ? date_i18n('Y-m-d H:i', $mtime) : esc_html__('нет файла', 'hs-smart-tooltip');
    $bundle = hs_smart_tooltip_get_bgs_dictionary();
    $count = is_array($bundle['by_id']) ? count($bundle['by_id']) : 0;
    ?>
    <h2><?php echo esc_html__('Поля сражений (Battlegrounds)', 'hs-smart-tooltip'); ?></h2>
    <p>
        <?php echo esc_html__('Словарь BG-карт:', 'hs-smart-tooltip'); ?>
        <strong><?php echo esc_html((string) $count); ?></strong>
        <?php echo esc_html__('карт, обновлён:', 'hs-smart-tooltip'); ?>
        <strong><?php echo esc_html($last_update); ?></strong>
    </p>
    <?php if (!$is_writable): ?>
        <div class="notice notice-error"><p>
            <?php echo esc_html__('Файл hs_bgs_dictionary.json или папка плагина недоступны для записи.', 'hs-smart-tooltip'); ?>
        </p></div>
    <?php endif; ?>
    <p><?php echo esc_html__('Картинки для BG-тултипов тянутся напрямую с art.hearthstonejson.com без прокси.', 'hs-smart-tooltip'); ?></p>

    <?php
    $bgs_settings = hs_smart_tooltip_bgs_settings();
    $bgs_auto = !empty($bgs_settings['auto_update']);
    $bgs_last_run = (int) ($bgs_settings['last_run'] ?? 0);
    $bgs_last_run_text = $bgs_last_run > 0 ? date_i18n('Y-m-d H:i', $bgs_last_run) : esc_html__('никогда', 'hs-smart-tooltip');
    $bgs_next = wp_next_scheduled(HS_TOOLTIP_BGS_CRON_HOOK);
    $bgs_next_text = $bgs_next ? date_i18n('Y-m-d H:i', $bgs_next) : esc_html__('не запланировано', 'hs-smart-tooltip');
    ?>
    <table class="widefat striped" style="max-width:760px; margin-bottom:12px;">
        <tbody>
            <tr>
                <td style="width:240px;"><?php echo esc_html__('Авто-обновление BG', 'hs-smart-tooltip'); ?></td>
                <td><strong><?php echo $bgs_auto ? esc_html__('включено', 'hs-smart-tooltip') : esc_html__('выключено', 'hs-smart-tooltip'); ?></strong></td>
            </tr>
            <tr>
                <td><?php echo esc_html__('Последний автозапуск', 'hs-smart-tooltip'); ?></td>
                <td><?php echo esc_html($bgs_last_run_text); ?>
                    <?php if (!empty($bgs_settings['last_status'])): ?>
                        — <em><?php echo esc_html((string) $bgs_settings['last_status']); ?></em>
                        (<?php echo (int) $bgs_settings['last_count']; ?> <?php echo esc_html__('карт', 'hs-smart-tooltip'); ?>)
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><?php echo esc_html__('Следующий запуск (cron)', 'hs-smart-tooltip'); ?></td>
                <td><?php echo esc_html($bgs_next_text); ?></td>
            </tr>
        </tbody>
    </table>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('hs_tooltip_bgs_rebuild', 'hs_tooltip_bgs_nonce'); ?>
        <input type="hidden" name="action" value="hs_tooltip_bgs_rebuild">
        <p>
            <label>
                <input type="hidden" name="hs_tooltip_bgs_auto" value="0">
                <input type="checkbox" name="hs_tooltip_bgs_auto" value="1" <?php checked($bgs_auto); ?>>
                <?php echo esc_html__('Автообновление BG раз в сутки (WP-Cron) — ловит изменения уровней таверны', 'hs-smart-tooltip'); ?>
            </label>
        </p>
        <p>
            <button type="submit" class="button">
                <?php echo esc_html__('Сохранить настройки', 'hs-smart-tooltip'); ?>
            </button>
            <button type="submit" name="hs_tooltip_bgs_run" value="1" class="button button-primary">
                <?php echo esc_html__('Сохранить и обновить сейчас', 'hs-smart-tooltip'); ?>
            </button>
        </p>
    </form>
    <p style="margin-top:8px;color:#666;">
        <?php echo esc_html__('Источник:', 'hs-smart-tooltip'); ?>
        <code><?php echo esc_html(HS_TOOLTIP_BGS_API_URL); ?></code>
    </p>
    <p><?php echo esc_html__('Использование:', 'hs-smart-tooltip'); ?>
        <code>[hs_bg id="BG19_010"]Сточная крыса[/hs_bg]</code>
    </p>
    <?php
}

/**
 * UI-блок автообновления основного словаря.
 */
function hs_smart_tooltip_render_main_autoupdate_section(): void
{
    $settings = hs_smart_tooltip_main_settings();
    $last_run = (int) ($settings['last_run'] ?? 0);
    $last_run_text = $last_run > 0
        ? date_i18n('Y-m-d H:i', $last_run)
        : esc_html__('никогда', 'hs-smart-tooltip');
    $next = wp_next_scheduled(HS_TOOLTIP_MAIN_CRON_HOOK);
    $next_text = $next ? date_i18n('Y-m-d H:i', $next) : esc_html__('не запланировано', 'hs-smart-tooltip');
    $auto = !empty($settings['auto_update']);
    ?>
    <h2><?php echo esc_html__('Автообновление из HearthstoneJSON', 'hs-smart-tooltip'); ?></h2>
    <p>
        <?php echo esc_html__('Источник:', 'hs-smart-tooltip'); ?>
        <code><?php echo esc_html(HS_TOOLTIP_HSJSON_API_URL); ?></code>
    </p>
    <table class="widefat striped" style="max-width:760px;">
        <tbody>
            <tr>
                <td style="width:240px;"><?php echo esc_html__('Авто-обновление', 'hs-smart-tooltip'); ?></td>
                <td><strong><?php echo $auto ? esc_html__('включено', 'hs-smart-tooltip') : esc_html__('выключено', 'hs-smart-tooltip'); ?></strong></td>
            </tr>
            <tr>
                <td><?php echo esc_html__('Последний запуск', 'hs-smart-tooltip'); ?></td>
                <td>
                    <?php echo esc_html($last_run_text); ?>
                    <?php if (!empty($settings['last_status'])): ?>
                        — <em><?php echo esc_html((string) $settings['last_status']); ?></em>
                        <?php if (!empty($settings['last_count'])): ?>
                            (<?php echo (int) $settings['last_count']; ?> <?php echo esc_html__('карт', 'hs-smart-tooltip'); ?>)
                        <?php endif; ?>
                        <?php if (!empty($settings['last_message'])): ?>
                            <span style="color:#a00;"> — <?php echo esc_html((string) $settings['last_message']); ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><?php echo esc_html__('Следующий запуск (cron)', 'hs-smart-tooltip'); ?></td>
                <td><?php echo esc_html($next_text); ?></td>
            </tr>
        </tbody>
    </table>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px;">
        <?php wp_nonce_field('hs_tooltip_main_rebuild', 'hs_tooltip_main_nonce'); ?>
        <input type="hidden" name="action" value="hs_tooltip_main_rebuild">
        <p>
            <label>
                <input type="hidden" name="hs_tooltip_main_auto" value="0">
                <input type="checkbox" name="hs_tooltip_main_auto" value="1" <?php checked($auto); ?>>
                <?php echo esc_html__('Запускать обновление автоматически раз в сутки (WP-Cron)', 'hs-smart-tooltip'); ?>
            </label>
        </p>
        <p>
            <button type="submit" class="button">
                <?php echo esc_html__('Сохранить настройки', 'hs-smart-tooltip'); ?>
            </button>
            <button type="submit" name="hs_tooltip_main_run" value="1" class="button button-primary">
                <?php echo esc_html__('Сохранить и обновить сейчас', 'hs-smart-tooltip'); ?>
            </button>
        </p>
        <p style="color:#666;">
            <?php echo esc_html__('Перед перезаписью текущий hs_dictionary.json копируется в hs_dictionary.bak.json. Если API вернёт меньше 80% карт от прошлого раза или меньше 2000 — файл не перезапишется.', 'hs-smart-tooltip'); ?>
        </p>
    </form>
    <?php
}
