"""Account assets are isolated without changing other pages or personalization."""
import json
from pathlib import Path
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[2]


class AccountAssetsTest(unittest.TestCase):
    def check_route(self, route):
        return json.loads(subprocess.check_output([
            "php", str(Path(__file__).with_name("account-assets-fixture.php")), route,
            str(ROOT / "wordpress/mu-plugins/hs-manacost-reader/account-assets.php"),
        ], text=True))

    def test_account_has_no_ads_trackers_or_unused_article_scripts(self):
        actual = self.check_route("account")
        self.assertEqual(actual["code"], "")
        self.assertFalse(actual["insert"])
        self.assertEqual(actual["footer"], "Theme footer")
        self.assertEqual(actual["removed_actions"], [
            "wp_footer:ai_wp_footer_hook:9999999",
            "wp_head:td_header_analytics_code:40",
            "wp_footer:td_footer_script_code:40",
            "wp_head:Manacost_Plausible_Analytics::render_tracker:20",
        ])
        self.assertEqual(actual["scripts"], ["tdMenu", "tdAjaxSearch", "jquery-core", "hs-manacost-reader"])

    def test_article_other_page_preview_admin_and_unprovisioned_account_are_unchanged(self):
        for route in ["article", "other", "preview", "admin", "missing"]:
            with self.subTest(route=route):
                actual = self.check_route(route)
                self.assertEqual(actual["code"], "fixture advertising")
                self.assertTrue(actual["insert"])
                self.assertEqual(actual["removed_actions"], [])
                self.assertIn("tdPostImages", actual["scripts"])
                self.assertIn("comment-reply", actual["scripts"])
                self.assertIn('id="ai-functions"', actual["footer"])
                self.assertIn("Theme footer", actual["footer"])
