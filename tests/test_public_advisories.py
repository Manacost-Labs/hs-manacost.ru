import importlib.util
import json
from pathlib import Path
import tempfile
import unittest

SPEC = importlib.util.spec_from_file_location("advisories", Path(__file__).resolve().parents[1] / "ops/integration/advisories.py")
a = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(a)


class PublicAdvisoryTests(unittest.TestCase):
    def payload(self, **changes):
        d = {"error": 0, "data": {"plugin": "sample", "name": "Sample", "closed": 0, "vulnerability": []}, "updated": "123"}
        d["data"].update(changes)
        return d

    def test_php_version_boundaries_and_prereleases(self):
        bounds = {"min_version": "2.0", "min_operator": "ge", "max_version": "3.1.5", "max_operator": "lt"}
        for version, expected in [("1.9", False), ("2.0", True), ("3.1.5RC1", True), ("3.1.5", False), ("3.2", False)]:
            self.assertEqual(expected, a.version_matches(version, bounds))
        with self.assertRaises(ValueError):
            a.version_matches("1.0", {})
        with self.assertRaises(ValueError):
            a.version_matches("1.0", {"max_version": "2.0", "max_operator": "invented"})

    def test_affected_and_fixed_versions_and_core_dedup(self):
        entry = {"uuid": "issue", "name": "An issue", "operator": {"max_version": "2.0", "max_operator": "lt"}, "source": []}
        p = self.payload(vulnerability=[entry])
        self.assertEqual(1, len(a.classify("plugin", "sample", "1.0", p)["findings"]))
        self.assertEqual([], a.classify("plugin", "sample", "2.0", p)["findings"])
        core = {"error": 0, "data": {"core": "6.9", "vulnerability": [entry, entry]}}
        self.assertEqual(1, len(a.classify("core", "6.9", "6.9", core)["findings"]))

    def test_absent_malformed_or_failed_data_is_not_a_pass(self):
        inventory = [{"kind": "plugin", "slug": "sample", "version": "1.0"}]
        for payload in [{"error": 1}, self.payload(plugin="other"), self.payload(vulnerability="invalid"), {"error": 0, "data": []}]:
            with tempfile.TemporaryDirectory() as d:
                report = a.check_inventory(inventory, d, fetcher=lambda *args: json.dumps(payload).encode())
                self.assertEqual("partial", report["status"])
                self.assertFalse(report["wpscan_database_checked"])
        def unavailable(*args):
            raise OSError("offline")
        with tempfile.TemporaryDirectory() as d:
            self.assertEqual("partial", a.check_inventory(inventory, d, fetcher=unavailable)["status"])

    def test_closed_and_known_findings_fail_unknown_version_is_partial(self):
        inventory = [{"kind": "plugin", "slug": "sample", "version": "1.0"}]
        with tempfile.TemporaryDirectory() as d:
            report = a.check_inventory(inventory, d, fetcher=lambda *args: json.dumps(self.payload(closed=1)).encode())
            self.assertEqual("failed", report["status"])
            self.assertEqual(1, report["closed_components"])
            self.assertTrue((Path(d) / "plugin-sample.json").is_file())
            inventory[0]["version"] = ""
            self.assertEqual("partial", a.check_inventory(inventory, d, fetcher=lambda *args: self.fail("must not fetch"))["status"])

    def test_exhausted_budget_and_invalid_inventory_fail_closed(self):
        inventory = [{"kind": "plugin", "slug": "sample", "version": "1.0"}]
        with tempfile.TemporaryDirectory() as d:
            self.assertEqual("partial", a.check_inventory(inventory, d, budget_seconds=0, fetcher=lambda *args: self.fail("budget exhausted"))["status"])
            inventory[0]["slug"] = "../../secret"
            with self.assertRaises(ValueError):
                a.check_inventory(inventory, d)

    def test_documented_null_is_empty_but_unknown_component_is_partial(self):
        self.assertEqual([], a.classify("plugin", "sample", "1.0", self.payload(vulnerability=None))["findings"])
        with self.assertRaises(ValueError):
            a.classify("plugin", "sample", "1.0", self.payload(name=None, vulnerability=None))

    def test_verified_vendor_alias_preserves_installed_identity(self):
        inventory = [{"kind": "theme", "slug": "Newspaper_new", "version": "12.7.3"}]
        def fetcher(kind, slug, timeout):
            self.assertEqual((kind, slug), ("theme", "newspaper"))
            return json.dumps({"error": 0, "data": {"theme": slug, "name": "Newspaper", "vulnerability": None}}).encode()
        with tempfile.TemporaryDirectory() as d:
            report = a.check_inventory(inventory, d, fetcher=fetcher)
        self.assertEqual("passed", report["status"])
        self.assertEqual("Newspaper_new", report["components"][0]["installed_slug"])
        self.assertEqual("Newspaper_new", inventory[0]["slug"])
