"""Offline contract tests; production PHP must never be stopped as a fixture."""
import configparser
from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]


class PhpRecoveryTests(unittest.TestCase):
    def test_bounded_failure_only_policy_preserves_vendor_service(self):
        policy = configparser.ConfigParser()
        policy.optionxform = str
        with (ROOT / "ops/monitoring/php-fpm-recovery.conf").open() as stream:
            policy.read_file(stream)
        self.assertEqual(set(policy.sections()), {"Unit", "Service"})
        self.assertEqual(dict(policy["Unit"]), {
            "StartLimitIntervalSec": "300", "StartLimitBurst": "5"})
        self.assertEqual(dict(policy["Service"]), {
            "Restart": "on-failure", "RestartSec": "5s"})
        # Exact sections also forbid ExecStart, memory caps, security overrides,
        # and destructive StartLimitAction/FailureAction reboot policies.


if __name__ == "__main__":
    unittest.main()
