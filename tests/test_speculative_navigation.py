import json
import re
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/manacost-speculative-navigation.php"


def run_php(body: str, *defines: str) -> dict:
    script = "define('ABSPATH', '/');" + "".join(defines) + r'''
    $GLOBALS['filters'] = [];
    function add_filter($name, $callback) { $GLOBALS['filters'][$name] = $callback; }
    require $argv[1];
    ''' + body
    result = subprocess.run(["php", "-r", script, str(PLUGIN)], check=True, text=True, capture_output=True)
    return json.loads(result.stdout)


class SpeculativeNavigationTest(unittest.TestCase):
    def test_anonymous_rules_prerender_on_moderate_intent(self):
        data = run_php(r'''
        $configure = $GLOBALS['filters']['wp_speculation_rules_configuration'];
        echo json_encode([
            'anonymous' => $configure(['mode' => 'auto', 'eagerness' => 'auto']),
            'disabled' => $configure(null),
        ]);
        ''')
        self.assertEqual({"mode": "prerender", "eagerness": "moderate"}, data["anonymous"])
        # Core disables rules for logged-in users and plain permalinks; that must stay off.
        self.assertIsNone(data["disabled"])

    def test_kill_switch_keeps_core_configuration(self):
        data = run_php(r'''
        echo json_encode($GLOBALS['filters']['wp_speculation_rules_configuration'](['mode' => 'auto', 'eagerness' => 'auto']));
        ''', "define('MANACOST_PRERENDER_ENABLED', false);")
        self.assertEqual({"mode": "auto", "eagerness": "auto"}, data)

    def test_private_and_side_effect_paths_are_never_speculated(self):
        data = run_php(r'''
        $exclude = $GLOBALS['filters']['wp_speculation_rules_href_exclude_paths'];
        echo json_encode(['once' => $exclude(['/custom/*']), 'twice' => $exclude($exclude(['/custom/*']))]);
        ''')
        for path in ["/custom/*", "/account*", "/reader*", "/profile*", "/community*", "/login*",
                     "/logout*", "/search*", "/views/*", "/mca/*", "/feed*", "/*/feed*"]:
            self.assertIn(path, data["once"])
        self.assertEqual(data["once"], data["twice"])


class PrerenderSideEffectGuardsTest(unittest.TestCase):
    """Anything that counts a view, an ad or a reading event waits for activation."""

    def test_views_counter_counts_only_shown_articles(self):
        source = (ROOT / "wordpress/themes/Newspaper_new/functions.php").read_bytes().decode("latin-1")
        hit = source.find("/views/hit")
        block = source[hit - 600:hit + 1200]
        self.assertRegex(block, re.compile(r'if \(document\.prerendering\) \{\s*document\.addEventListener\("prerenderingchange", countView', re.S))
        self.assertIn('document.addEventListener("DOMContentLoaded", countView)', block)

    def test_yandex_ads_load_only_after_activation(self):
        source = (ROOT / "wordpress/mu-plugins/manacost-rsya-inline.php").read_text()
        gate = source[source.find("window.manacostRsyaReady = (async function"):]
        self.assertLess(gate.find("prerenderingchange"), gate.find("/reader-api/v1/ad-status"))
        self.assertLess(gate.find("prerenderingchange"), gate.find("yandex.ru/ads/system/context.js"))

    def test_article_events_and_web_vitals_start_on_activation(self):
        for name in ["plausible-analytics.php", "manacost-web-vitals.php"]:
            with self.subTest(name=name):
                source = (ROOT / "wordpress/mu-plugins" / name).read_text()
                self.assertIn("if(document.prerendering){document.addEventListener('prerenderingchange'", source)
        vitals = (ROOT / "wordpress/mu-plugins/manacost-web-vitals.php").read_text()
        self.assertIn("entry.startTime-activation", vitals)
        self.assertIn("activation>0?'prerender'", vitals)


if __name__ == "__main__":
    unittest.main()
