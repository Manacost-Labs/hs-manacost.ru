"""Deck pages inherit site/editorial robots policy instead of a forced noindex."""
import json
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/plugins/wp-manacost-decks_old/hs-deck-manager/hs-decks-manager.php"


def robots(context, wp=None, aioseo=None):
    script = r'''<?php
    define('ABSPATH', '/');
    $head_callbacks = array();
    function add_action($hook, $callback, ...$args) {
        if ($hook === 'wp_head') $GLOBALS['head_callbacks'][] = $callback;
    }
    function add_filter(...$args) {}
    function add_shortcode(...$args) {}
    function is_admin() { return false; }
    function is_singular($type) { return $type === 'hs_deck' && $GLOBALS['context'] === 'deck'; }
    function is_tax($type) { return $type === 'deck_archetype' && $GLOBALS['context'] === 'archetype'; }
    '''
    script += "$context = " + json.dumps(context) + ";\n"
    script += "require " + json.dumps(str(PLUGIN)) + ";\n"
    script += "$manager = new HS_Decks_Manager();\n"
    script += "$wp = json_decode(" + json.dumps(json.dumps(wp or {})) + ", true);\n"
    script += "$aioseo = json_decode(" + json.dumps(json.dumps(aioseo or {})) + ", true);\n"
    script += r'''
    ob_start();
    if ($context === 'deck') {
        foreach ($head_callbacks as $callback) call_user_func($callback);
    }
    $html = ob_get_clean();
    echo json_encode(array(
        'wp' => (object) $manager->filter_wp_robots($wp),
        'aioseo' => (object) $manager->filter_aioseo_robots_meta($aioseo),
        'html' => $html,
    ));
    '''
    result = subprocess.run(["php"], input=script, text=True, capture_output=True, check=True)
    return json.loads(result.stdout)


class DeckRobotsTests(unittest.TestCase):
    def test_public_deck_does_not_force_noindex_or_duplicate_meta(self):
        result = robots("deck", {"max-image-preview": "large"}, {"index": "index"})
        self.assertEqual(result["wp"], {"max-image-preview": "large"})
        self.assertEqual(result["aioseo"], {"index": "index"})
        self.assertNotIn('name="robots"', result["html"])

    def test_deck_preserves_site_or_editorial_restrictions(self):
        wp = {"noindex": True, "nofollow": True}
        aioseo = {"noindex": "noindex", "nofollow": "nofollow"}
        result = robots("deck", wp, aioseo)
        self.assertEqual(result["wp"], wp)
        self.assertEqual(result["aioseo"], aioseo)

    def test_other_content_is_unchanged(self):
        result = robots("article", {"noindex": True}, {"nofollow": "nofollow"})
        self.assertEqual(result["wp"], {"noindex": True})
        self.assertEqual(result["aioseo"], {"nofollow": "nofollow"})

    def test_archetype_archive_policy_is_preserved(self):
        result = robots("archetype", {"noindex": True}, {"noindex": "noindex"})
        self.assertEqual(result["wp"], {"index": True, "follow": True})
        self.assertEqual(result["aioseo"], {"index": "index", "follow": "follow"})


if __name__ == "__main__":
    unittest.main()
