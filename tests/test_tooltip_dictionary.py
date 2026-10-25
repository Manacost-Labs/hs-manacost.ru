import subprocess
import unittest
import json
import importlib.util
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

    def test_non_collectible_token_rebuild_search_and_shortcode(self):
        self.run_fixture("tokens")
        dictionary = json.loads((ROOT / "wordpress/plugins/hs-tooltip/hs_dictionary.json").read_text())
        self.assertEqual(dictionary["by_id"]["BE_222t"]["name"], "Безднокрыл")
        self.assertEqual(dictionary["by_id"]["130406"], dictionary["by_id"]["BE_222t"])

    def test_offline_builders_keep_non_collectible_tokens(self):
        cards = [
            {"id": "BE_222t", "dbfId": 130406, "name": "Безднокрыл", "type": "MINION", "set": "BE"},
            {"id": "TOKEN_1", "name": "Совпавшее имя", "type": "MINION", "rarity": "LEGENDARY", "set": "BE"},
            {"id": "CARD_1", "name": "Совпавшее имя", "type": "MINION", "rarity": "COMMON", "set": "BE", "collectible": True},
        ]
        for name, builder in [("rebuild_dict", "build_dictionary"), ("build_dictionaries", "build_main")]:
            with self.subTest(builder=name):
                path = ROOT / "wordpress/plugins/hs-tooltip/tools" / (name + ".py")
                spec = importlib.util.spec_from_file_location(name, path)
                module = importlib.util.module_from_spec(spec)
                spec.loader.exec_module(module)
                result = getattr(module, builder)(cards)
                self.assertEqual(result["by_id"]["BE_222t"]["name"], "Безднокрыл")
                self.assertTrue(result["by_name"]["совпавшее имя"]["img"].endswith("/CARD_1.png"))

    def test_koloda_cards_search_and_shortcode(self):
        self.run_fixture("success")

    def test_failed_import_preserves_dictionary(self):
        for scenario in ("timeout", "unauthorized", "rate_limit", "server_error",
                         "malformed", "stale", "partial", "duplicate", "wrong_page",
                         "unsafe_image", "disabled", "empty"):
            with self.subTest(scenario=scenario):
                self.run_fixture(scenario)

    def test_bgs_sha_search_shortcode_and_failure(self):
        dictionary = json.loads((ROOT / "wordpress/plugins/hs-tooltip/hs_bgs_dictionary.json").read_text())
        card = json.loads((ROOT / "tests/fixtures/tooltip-koloda-bgs-cards.json").read_text())[0]
        self.assertEqual(dictionary["by_id"][card["card_id"]]["img"], card["images"]["card"])
        for scenario in ("success", "timeout", "unauthorized", "rate_limit", "server_error",
                         "malformed", "stale", "partial", "duplicate", "wrong_page", "unsafe_image", "disabled", "empty"):
            with self.subTest(scenario=scenario):
                self.run_fixture("bg_" + scenario)
        result = subprocess.run(["php", "tests/fixtures/tooltip-dictionary.php", "proxy", card["images"]["card"]],
                                cwd=ROOT, capture_output=True, timeout=20)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertTrue(result.stdout.startswith(b"\x89PNG"))

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
