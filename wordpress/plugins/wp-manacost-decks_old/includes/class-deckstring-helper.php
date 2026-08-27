<?php
/**
 * Hearthstone deckstring decoding helpers.
 *
 * Reads the HearthSim deckstring header locally: reserved byte, version,
 * format and hero DBF IDs. The class/mode terms are then assigned from that
 * metadata without loading a JS runtime in wp-admin.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Unified_HS_Deckstring_Helper {

    public static function init() {
        add_action('added_post_meta', array(__CLASS__, 'maybe_assign_on_meta_change'), 10, 4);
        add_action('updated_post_meta', array(__CLASS__, 'maybe_assign_on_meta_change'), 10, 4);
    }

    public static function maybe_assign_on_meta_change($meta_id, $post_id, $meta_key, $meta_value) {
        if ($meta_key !== '_deck_code' || get_post_type($post_id) !== 'hs_deck') {
            return;
        }

        self::assign_terms_from_code($post_id, $meta_value);
    }

    public static function assign_terms_from_code($post_id, $deck_code) {
        $post_id = absint($post_id);
        if (!$post_id || get_post_type($post_id) !== 'hs_deck') {
            return array();
        }

        $decoded = self::decode($deck_code);
        if (is_wp_error($decoded)) {
            return array();
        }

        $assigned = array();
        $overwrite = (bool) apply_filters('unified_hs_deckstring_overwrite_terms', false, $post_id, $decoded);

        if (!empty($decoded['class_term']) && taxonomy_exists('deck_class')) {
            $existing = wp_get_object_terms($post_id, 'deck_class', array('fields' => 'ids'));
            if ($overwrite || empty($existing) || is_wp_error($existing)) {
                $term_id = self::ensure_term('deck_class', $decoded['class_term']);
                if ($term_id) {
                    wp_set_object_terms($post_id, array($term_id), 'deck_class', false);
                    $assigned['deck_class'] = $decoded['class_term'];
                }
            }
        }

        if (!empty($decoded['mode_term']) && taxonomy_exists('deck_mode')) {
            $existing = wp_get_object_terms($post_id, 'deck_mode', array('fields' => 'ids'));
            if ($overwrite || empty($existing) || is_wp_error($existing)) {
                $term_id = self::ensure_term('deck_mode', $decoded['mode_term']);
                if ($term_id) {
                    wp_set_object_terms($post_id, array($term_id), 'deck_mode', false);
                    $assigned['deck_mode'] = $decoded['mode_term'];
                }
            }
        }

        return $assigned;
    }

    public static function decode($deck_code) {
        $candidates = self::extract_candidates($deck_code);
        if (empty($candidates)) {
            return new WP_Error('hs_deckstring_empty', 'Deckstring is empty.');
        }

        foreach ($candidates as $candidate) {
            $decoded = self::decode_candidate($candidate);
            if (!is_wp_error($decoded)) {
                $decoded['deckstring'] = $candidate;
                return $decoded;
            }
        }

        return new WP_Error('hs_deckstring_invalid', 'Deckstring could not be decoded.');
    }

    private static function extract_candidates($raw_code) {
        $raw_code = trim((string) $raw_code);
        if ($raw_code === '') {
            return array();
        }

        $candidates = array();
        $lines = preg_split('/\R+/', $raw_code);
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }

            $line = preg_replace('/\s+/', '', $line);
            if (preg_match('/^[A-Za-z0-9+\/=]+$/', $line) && strlen($line) >= 8) {
                $candidates[] = $line;
            }
        }

        $compact = preg_replace('/\s+/', '', $raw_code);
        if (preg_match('/^[A-Za-z0-9+\/=]+$/', $compact) && strlen($compact) >= 8) {
            array_unshift($candidates, $compact);
        }

        return array_values(array_unique($candidates));
    }

    private static function decode_candidate($candidate) {
        $candidate = trim((string) $candidate);
        $remainder = strlen($candidate) % 4;
        if ($remainder > 0) {
            $candidate .= str_repeat('=', 4 - $remainder);
        }

        $binary = base64_decode($candidate, true);
        if (!is_string($binary) || strlen($binary) < 4) {
            return new WP_Error('hs_deckstring_base64', 'Invalid base64 deckstring.');
        }

        $offset = 0;
        if (ord($binary[$offset++]) !== 0) {
            return new WP_Error('hs_deckstring_header', 'Invalid deckstring header.');
        }

        $version = ord($binary[$offset++]);
        if ($version !== 1) {
            return new WP_Error('hs_deckstring_version', 'Unsupported deckstring version.');
        }

        $format = self::read_varint($binary, $offset);
        if (!in_array($format, array(1, 2, 3, 4), true)) {
            return new WP_Error('hs_deckstring_format', 'Unsupported deckstring format.');
        }

        $heroes_count = self::read_varint($binary, $offset);
        if ($heroes_count === null || $heroes_count < 1 || $heroes_count > 8) {
            return new WP_Error('hs_deckstring_heroes', 'Invalid hero block.');
        }

        $heroes = array();
        for ($i = 0; $i < $heroes_count; $i++) {
            $hero_id = self::read_varint($binary, $offset);
            if ($hero_id === null || $hero_id <= 0) {
                return new WP_Error('hs_deckstring_hero', 'Invalid hero DBF ID.');
            }
            $heroes[] = $hero_id;
        }

        $class_key = self::class_from_heroes($heroes);
        $class_terms = self::class_term_names();
        $mode_terms = self::format_term_names();
        $cards = self::read_card_blocks($binary, $offset);

        return array(
            'version' => $version,
            'format' => $format,
            'heroes' => $heroes,
            'cards' => $cards,
            'hero_class' => $class_key,
            'class_term' => $class_key && isset($class_terms[$class_key]) ? $class_terms[$class_key] : '',
            'mode_term' => isset($mode_terms[$format]) ? $mode_terms[$format] : '',
        );
    }

    private static function read_card_blocks($binary, &$offset) {
        $cards = array();

        foreach (array(1, 2) as $quantity) {
            $count = self::read_varint($binary, $offset);
            if ($count === null || $count < 0 || $count > 200) {
                return $cards;
            }
            for ($i = 0; $i < $count; $i++) {
                $dbf_id = self::read_varint($binary, $offset);
                if ($dbf_id === null || $dbf_id <= 0) {
                    return $cards;
                }
                self::add_card_count($cards, $dbf_id, $quantity);
            }
        }

        $multi_count = self::read_varint($binary, $offset);
        if ($multi_count === null || $multi_count < 0 || $multi_count > 200) {
            return $cards;
        }
        for ($i = 0; $i < $multi_count; $i++) {
            $dbf_id = self::read_varint($binary, $offset);
            $quantity = self::read_varint($binary, $offset);
            if ($dbf_id === null || $dbf_id <= 0 || $quantity === null || $quantity <= 0 || $quantity > 30) {
                return $cards;
            }
            self::add_card_count($cards, $dbf_id, $quantity);
        }

        return $cards;
    }

    private static function add_card_count(array &$cards, $dbf_id, $quantity) {
        $dbf_id = (int) $dbf_id;
        $quantity = (int) $quantity;
        if ($dbf_id <= 0 || $quantity <= 0) {
            return;
        }
        if (!isset($cards[$dbf_id])) {
            $cards[$dbf_id] = 0;
        }
        $cards[$dbf_id] += $quantity;
    }

    private static function read_varint($binary, &$offset) {
        $result = 0;
        $shift = 0;
        $length = strlen($binary);

        while ($offset < $length) {
            $byte = ord($binary[$offset++]);
            $result |= (($byte & 0x7f) << $shift);
            if (($byte & 0x80) === 0) {
                return $result;
            }
            $shift += 7;
            if ($shift > 35) {
                return null;
            }
        }

        return null;
    }

    private static function class_from_heroes(array $heroes) {
        $map = self::hero_class_map();
        foreach ($heroes as $hero_id) {
            if (isset($map[$hero_id])) {
                return $map[$hero_id];
            }
        }
        return '';
    }

    private static function format_term_names() {
        return apply_filters('unified_hs_deckstring_format_terms', array(
            1 => 'Вольный',
            2 => 'Стандарт',
            3 => 'Классический',
            4 => 'Потасовка',
        ));
    }

    private static function class_term_names() {
        return apply_filters('unified_hs_deckstring_class_terms', array(
            'DEATHKNIGHT' => 'Рыцарь смерти',
            'DEMONHUNTER' => 'Охотник на демонов',
            'DRUID' => 'Друид',
            'HUNTER' => 'Охотник',
            'MAGE' => 'Маг',
            'PALADIN' => 'Паладин',
            'PRIEST' => 'Жрец',
            'ROGUE' => 'Разбойник',
            'SHAMAN' => 'Шаман',
            'WARLOCK' => 'Чернокнижник',
            'WARRIOR' => 'Воин',
        ));
    }

    private static function hero_class_map() {
        static $map = null;
        if (is_array($map)) {
            return apply_filters('unified_hs_deckstring_hero_class_map', $map);
        }

        // Generated from HearthstoneJSON collectible HERO cards; filters below let site owners override new IDs.
        $raw = array(
            'DEATHKNIGHT' => '78065,78066,93448,97869,98722,100115,101677,101700,103252,103272,103763,103788,103789,104637,105067,106274,107725,107728,107739,108419,110718,111243,111247,112708,112711,112741,112782,114333,116070,116071,116078,116176,119017,119037,119053,119059,119727,120228,120469,121601,121606,121625,123702,123709,123764,127654,127666,127682,130386',
            'DEMONHUNTER' => '56550,60238,62491,64697,66951,71061,71077,71078,71079,71331,73711,77246,78177,79972,80101,80185,83410,83829,84097,84150,89939,93980,97872,98361,101663,101679,101770,103598,103766,105070,106271,107734,107741,107743,108382,108423,110719,112723,112748,112763,114192,114339,116068,116098,116107,116137,119039,119040,119500,121596,121618,121649,121713,123713,123714,123734,125340,127681,127748,127944,130788',
            'DRUID' => '274,43417,50484,56358,57761,60375,64698,66953,69988,71062,71328,73709,73763,73764,73765,73766,77252,78180,79974,80095,80187,83398,83406,83831,84153,93491,93966,93979,95724,97875,98368,101652,101669,101768,103767,105088,106276,107746,109338,110728,110729,110816,110817,111232,112712,112749,114329,114338,116079,116106,116166,119034,119049,119086,119476,119878,121600,121607,121633,121658,123707,123708,123761,126110,127669,127680,127759,131396,131947',
            'HUNTER' => '31,2826,43398,49819,57759,60335,61597,64699,67803,71063,71080,73710,73772,73773,77030,77247,77315,79930,79971,80100,80191,83409,84155,89942,93762,93981,95687,97862,97879,98367,101676,103770,105081,107733,108384,108416,110712,110726,110812,110813,111233,112517,112715,112793,116061,116103,116158,119023,119029,119058,119087,119730,121587,121603,121611,121621,121636,121716,123705,123733,123758,123774,126208,127663,127678,127758,130591,130592',
            'MAGE' => '637,2829,39117,43419,56076,57765,60157,61598,62772,64844,64845,64846,64847,66848,71064,71723,77253,77318,79949,79950,79951,79952,79963,80099,80188,82519,83403,83830,84094,84141,89931,93490,93788,93972,93976,95704,97876,98729,101653,101678,101703,101775,103595,103768,103769,106273,107742,108389,108417,109336,110724,110808,110809,111241,112702,112722,112751,112753,116069,116080,116101,116143,119032,119038,119057,121590,121604,121610,121614,121655,121660,122001,122993,123715,123762,123771,123776,127119,127657,127684,127751,127935,131395',
            'PALADIN' => '671,2827,43406,46116,53187,57757,61596,61886,64732,67040,67519,71065,72682,72729,77249,77319,79953,79954,79955,79956,79970,83395,83402,83826,84152,89938,93971,93984,94026,95774,97873,98725,101673,101704,103764,103765,105085,106270,107723,107724,107735,108385,109337,109343,110720,111242,112720,112745,114323,114332,116074,116075,116108,119033,119088,119482,119488,121602,121612,121630,121663,122991,123135,123699,123730,123775,127675,127985,129950',
            'PRIEST' => '813,41887,43408,54816,57416,57767,64733,67048,67523,67710,71066,71074,71075,71076,73741,77251,77320,79975,80182,83408,84096,84145,89937,93789,93970,93983,95749,97868,97878,98369,101648,101671,101701,101767,103772,103777,105082,107730,107740,108418,110727,110814,110815,111231,112705,112785,114189,114321,116076,116077,116159,119035,119036,119056,119473,121598,121622,121626,121646,123712,123763,125684,127672,127683,128792,129582',
            'ROGUE' => '930,40195,43392,57419,57755,64734,66939,67522,67846,71067,73742,73743,73744,73745,77316,78586,79973,80183,83401,84090,84147,89941,93787,93987,95775,97870,97880,98724,101654,101680,101699,103773,103776,105069,107722,107748,108383,109340,110725,110810,111240,112526,112717,112794,113345,114183,114322,116067,116102,116155,116169,119030,119031,119089,119479,121605,121623,121643,122992,123706,123768,127676,127745,129318',
            'SHAMAN' => '1066,40183,42987,46887,53237,55963,57427,57753,60673,64736,64848,64849,64850,67779,71058,71068,74586,77248,77321,79957,79958,79959,79960,79976,80098,83407,83411,83832,84151,89932,89933,93790,95766,97874,98370,101641,101674,101675,102634,103596,103762,105071,105083,107729,107738,107751,108420,110722,111234,111235,112713,112726,112742,112762,116064,116081,116149,116173,119026,119060,119080,119483,119489,121599,121637,123134,123695,123717,123740,123861,125686,127686,127938,129676,131113',
            'WARLOCK' => '777,893,43415,47817,51834,57329,57763,64685,64738,67760,69637,71069,71324,73768,73769,73770,73771,77254,77314,79969,80102,80189,83404,84095,84154,89935,93724,93969,93986,95644,97865,97877,98723,101670,101766,103104,103774,103775,105084,107744,107745,107750,107752,108386,109339,110715,110723,110730,112724,112750,112795,114186,114324,116072,116152,116172,119020,119041,119083,119437,119733,121597,121608,121724,122983,123711,123737,123742,124589,127679,127754,129094',
            'WARRIOR' => '7,2828,43423,48146,57413,57751,58787,61595,61923,64739,66876,67521,71070,71071,71072,71073,73740,77250,77317,78174,79966,80190,83405,84146,84156,89927,89934,93985,95745,97871,98727,101655,101666,101672,101702,103597,103771,105068,106272,107736,107737,107747,107749,108415,110721,111238,112718,112719,112788,114180,116073,116082,116140,116146,116160,117487,119027,119028,119076,120231,121593,121638,121659,123710,123741,123765,127651,127677,127757,127941,129325',
        );

        $map = array();
        foreach ($raw as $class_key => $ids) {
            foreach (explode(',', $ids) as $id) {
                $map[(int) $id] = $class_key;
            }
        }

        return apply_filters('unified_hs_deckstring_hero_class_map', $map);
    }

    private static function ensure_term($taxonomy, $term_name) {
        $term = term_exists($term_name, $taxonomy);
        if (!$term) {
            $term = wp_insert_term($term_name, $taxonomy);
        }
        if (is_wp_error($term) || !$term) {
            return 0;
        }
        if (is_array($term) && isset($term['term_id'])) {
            return absint($term['term_id']);
        }
        return absint($term);
    }
}
