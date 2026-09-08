"""Keep the standalone reader security suite in every canonical quality gate."""

import json
from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]


class ReaderFoundationContractTests(unittest.TestCase):
    def test_canonical_check_includes_reader_suite(self):
        makefile = (ROOT / "Makefile").read_text()
        check = next(line for line in makefile.splitlines() if line.startswith("check:"))
        self.assertIn("reader-test", check)
        self.assertIn("node --test services/reader/test/*.test.js", makefile)

    def test_every_quality_gate_provisions_node_before_running(self):
        for filename in ("quality.yml", "deploy-staging.yml", "promote-production.yml"):
            workflow = (ROOT / ".github/workflows" / filename).read_text()
            self.assertLess(workflow.index("actions/setup-node@"), workflow.index("run: make check"))
            self.assertIn("node-version: '22.22.2'", workflow)

    def test_reader_has_explicit_security_owner(self):
        rules = json.loads((ROOT / "config/change-impact-map.json").read_text())["rules"]
        reader = next(rule for rule in rules if "services/reader/**" in rule["patterns"])
        self.assertTrue(reader["classified"])
        self.assertEqual(reader["risk"], "high")
        self.assertIn("make reader-test", reader["checks"])
