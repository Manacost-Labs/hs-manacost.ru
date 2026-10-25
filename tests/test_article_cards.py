"""Exercise article cards against a controlled HTTP/cache boundary."""
import subprocess
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


class ArticleCardsTest(unittest.TestCase):
    def test_remote_catalog_and_rendering(self):
        for mode in ("normal", "disabled"):
            with self.subTest(mode=mode):
                result = subprocess.run(
                    ["php", "tests/fixtures/article-cards.php", mode], cwd=ROOT,
                    capture_output=True, text=True, timeout=15,
                )
                self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
                self.assertEqual(result.stdout.strip(), "PASS")
