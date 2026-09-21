from __future__ import annotations

import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
FIXTURE = ROOT / "tests/fixtures/admin-load-trim.php"
PLUGIN = ROOT / "wordpress/mu-plugins/hs-admin-load-trim.php"


class AdminLoadTrimTest(unittest.TestCase):
    def test_expensive_optional_post_list_columns_are_disabled(self):
        result = subprocess.run(
            [shutil.which("php") or "/usr/bin/php", str(FIXTURE), str(PLUGIN)],
            text=True,
            capture_output=True,
            timeout=10,
            check=False,
        )

        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout.strip(), "PASS")


if __name__ == "__main__":
    unittest.main()
