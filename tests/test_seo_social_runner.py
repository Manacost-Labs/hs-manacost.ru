from __future__ import annotations

import json
import shutil
import subprocess
import tempfile
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
RUNNER = ROOT / "ops/seo/social-and-archetypes.php"
FIXTURE = ROOT / "tests/fixtures/seo-social-runner.php"


@unittest.skipUnless(shutil.which("php"), "php is required")
class SeoSocialRunnerTest(unittest.TestCase):
    def test_apply_is_idempotent_and_rollback_keeps_later_edits(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            backup = Path(directory) / "seo.json"
            result = subprocess.run(
                ["php", str(FIXTURE), str(RUNNER), str(backup)],
                text=True, capture_output=True, check=True,
            )
            report = json.loads(result.stdout)
            stored = json.loads(backup.read_text(encoding="utf-8"))

        dry_run, apply, second, rollback, missing = report["lines"]
        self.assertFalse(dry_run["written"])
        self.assertEqual(set(dry_run["changes"]), set(apply["changes"]))
        self.assertEqual(
            {
                "facebook.enable", "facebook.defaultImageSourcePosts", "facebook.defaultImagePosts",
                "twitter.enable", "twitter.useOgData", "archetypes.title", "archetypes.description",
            },
            set(apply["changes"]),
        )
        self.assertEqual("https://hs-manacost.ru/icon-512.png", apply["changes"]["facebook.defaultImagePosts"]["to"])
        self.assertEqual("Архетипы колод Hearthstone", stored["after"]["archetypes.title"])
        self.assertEqual("", stored["before"]["archetypes.title"])

        self.assertEqual({}, second["changes"])
        self.assertFalse(second["written"])
        self.assertFalse(report["second_backup"])

        self.assertEqual(["archetypes.title"], rollback["kept"])
        final = report["final"]
        self.assertEqual("Архетипы Hearthstone", final["archetypes.title"])
        self.assertFalse(final["facebook.enable"])
        self.assertEqual("default", final["facebook.defaultImageSourcePosts"])
        self.assertEqual("", final["archetypes.description"])
        self.assertEqual(2, report["saves"])
        self.assertIn("backup file is missing", missing["error"])


if __name__ == "__main__":
    unittest.main()
