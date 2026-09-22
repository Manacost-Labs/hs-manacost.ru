from __future__ import annotations

import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
FIXTURE = ROOT / "tests/fixtures/admin-theme-update-guard.php"
PLUGIN = ROOT / "wordpress/mu-plugins/hs-admin-theme-update-guard.php"


class AdminThemeUpdateGuardTest(unittest.TestCase):
    def test_newspaper_update_cache_is_preserved_without_hiding_real_checks(self) -> None:
        for scenario in (
            "fresh",
            "stale",
            "absent",
            "newer",
            "frontend",
            "ajax",
            "cron",
            "cli",
            "no_capability",
            "other_theme",
            "not_deleted",
            "later_manual_check",
            "invalid_last_checked",
        ):
            with self.subTest(scenario=scenario):
                result = subprocess.run(
                    [
                        shutil.which("php") or "/usr/bin/php",
                        str(FIXTURE),
                        str(PLUGIN),
                        scenario,
                    ],
                    text=True,
                    capture_output=True,
                    timeout=10,
                    check=False,
                )

                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual(result.stdout.strip(), "PASS")


if __name__ == "__main__":
    unittest.main()
