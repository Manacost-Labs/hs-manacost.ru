from __future__ import annotations

import shutil
import subprocess
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
FIXTURE = ROOT / "tests/fixtures/admin-meta-key-cache.php"
PLUGIN = ROOT / "wordpress/mu-plugins/hs-admin-meta-key-cache.php"


class AdminMetaKeyCacheTest(unittest.TestCase):
    def test_custom_field_choices_stay_correct_without_repeating_the_archive_scan(self):
        for scenario in (
            "warm", "empty", "upstream", "frontend", "limit", "invalid_limit",
            "add", "delete", "delete_all", "rename_private", "private_value",
            "rename_failure", "query_failure", "cache_failure", "race", "blog",
            "expiry", "failed_rename_filter", "public_value",
        ):
            with self.subTest(scenario=scenario):
                result = subprocess.run(
                    [shutil.which("php") or "/usr/bin/php", str(FIXTURE), str(PLUGIN), scenario],
                    text=True, capture_output=True, timeout=10, check=False,
                )
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual(result.stdout.strip(), "PASS")


if __name__ == "__main__":
    unittest.main()
