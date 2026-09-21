import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]


class TooltipLoadingTests(unittest.TestCase):
    def test_tooltip_image_loading_budget(self):
        result = subprocess.run(
            ["node", "--test", "tests/tooltip/loading.test.js"],
            cwd=ROOT,
            capture_output=True,
            text=True,
            timeout=30,
        )
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
