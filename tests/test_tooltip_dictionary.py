import subprocess
import unittest
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


class TooltipDictionaryTests(unittest.TestCase):
    def test_shipped_dictionary_contains_all_requested_cards(self):
        dictionary = json.loads((ROOT / "wordpress/plugins/hs-tooltip/hs_dictionary.json").read_text())
        cards = json.loads((ROOT / "tests/fixtures/tooltip-koloda-cards.json").read_text())
        for card in cards:
            entry = dictionary["by_id"][card["card_id"]]
            self.assertEqual(entry["name"], card["name"]["ru"])
            self.assertEqual(entry["img"], card["images"]["card"])
            self.assertEqual(dictionary["by_id"][str(card["dbf"])], entry)

    def test_koloda_cards_search_and_shortcode(self):
        self.run_fixture("success")

    def test_failed_import_preserves_dictionary(self):
        for scenario in ("timeout", "unauthorized", "rate_limit", "server_error",
                         "malformed", "stale", "partial", "duplicate", "wrong_page",
                         "unsafe_image", "disabled", "empty"):
            with self.subTest(scenario=scenario):
                self.run_fixture(scenario)

    def test_koloda_image_proxy(self):
        image = json.loads((ROOT / "tests/fixtures/tooltip-koloda-cards.json").read_text())[0]["images"]["card"]
        result = subprocess.run(["php", "tests/fixtures/tooltip-dictionary.php", "proxy", image],
                                cwd=ROOT, capture_output=True, timeout=20)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertTrue(result.stdout.startswith(b"\x89PNG"))
        for url in ("https://api.kolodahearthstone.com/api/v1/cards",
                    "https://api.kolodahearthstone.com/uploads/constructed-related/../private.png",
                    "http://api.kolodahearthstone.com/uploads/constructed-related/BE_036.png",
                    "https://api.kolodahearthstone.com:8443/uploads/constructed-related/BE_036.png"):
            with self.subTest(url=url):
                result = subprocess.run(["php", "tests/fixtures/tooltip-dictionary.php", "proxy_reject", url],
                                        cwd=ROOT, capture_output=True, timeout=20)
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual(result.stdout, b"")

    def run_fixture(self, scenario):
        result = subprocess.run(
            ["php", "tests/fixtures/tooltip-dictionary.php", scenario],
            cwd=ROOT, capture_output=True, text=True, timeout=20,
        )
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        self.assertIn("PASS", result.stdout)
