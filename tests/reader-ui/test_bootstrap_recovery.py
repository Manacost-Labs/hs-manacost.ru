"""Exercise the real shared bootstrap with deterministic transport failures."""
from pathlib import Path
import subprocess
import unittest


class BootstrapRecoveryTests(unittest.TestCase):
    def test_shared_bootstrap_lifecycle(self):
        subprocess.run(["node", "--test", str(Path(__file__).with_name("bootstrap-recovery.mjs"))], check=True)
