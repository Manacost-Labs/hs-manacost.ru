import json
import subprocess
import tempfile
import unittest
from pathlib import Path
from typing import cast


ROOT = Path(__file__).resolve().parents[1]
BUILDER = ROOT / "ops/performance/build-admin-performance-report.py"


def raw_evidence(*, ttfb_ms: float = 620.0) -> dict[str, object]:
    sample = {
        "ttfb_ms": ttfb_ms,
        "interactive_ms": 1200.0,
        "sql_queries": 72.0,
        "peak_memory_mb": 64.0,
        "long_tasks": 1.0,
    }
    return {
        "schema_version": 1,
        "environment": "integration",
        "screen": "dashboard",
        "authenticated_role": "administrator",
        "dataset_size": 100,
        "cache_state": "warm",
        "viewport": "desktop-1440",
        "samples": [dict(sample) for _ in range(5)],
        "functional_checks": {
            "behavior": True,
            "permissions": True,
            "desktop": True,
            "mobile": True,
            "error_path": True,
        },
    }


def budgets() -> dict[str, object]:
    return {
        "schema_version": 1,
        "screens": {
            "dashboard": {
                "ttfb_ms": {"baseline": 800, "budget": 1000, "unit": "ms"},
                "interactive_ms": {
                    "baseline": 1600,
                    "budget": 2000,
                    "unit": "ms",
                },
                "sql_queries": {
                    "baseline": 80,
                    "budget": 100,
                    "unit": "count",
                },
                "peak_memory_mb": {
                    "baseline": 80,
                    "budget": 128,
                    "unit": "MB",
                },
                "long_tasks": {
                    "baseline": 2,
                    "budget": 3,
                    "unit": "count",
                },
            }
        },
    }


class AdminPerformanceAutomationTests(unittest.TestCase):
    def test_report_builder_uses_medians_and_approved_budgets(self) -> None:
        with tempfile.TemporaryDirectory() as temporary_directory:
            temporary = Path(temporary_directory)
            raw_path = temporary / "raw.json"
            budget_path = temporary / "budgets.json"
            _ = raw_path.write_text(json.dumps(raw_evidence()), encoding="utf-8")
            _ = budget_path.write_text(json.dumps(budgets()), encoding="utf-8")

            result = subprocess.run(
                [str(BUILDER), str(raw_path), str(budget_path)],
                cwd=ROOT,
                check=False,
                capture_output=True,
                text=True,
            )

            self.assertEqual(0, result.returncode, result.stderr)
            report = cast(dict[str, object], json.loads(result.stdout))
            self.assertEqual(5, report["sample_count"])
            metrics = cast(list[dict[str, object]], report["metrics"])
            ttfb = next(metric for metric in metrics if metric["name"] == "ttfb_ms")
            self.assertEqual(620.0, ttfb["after"])
            self.assertEqual(800.0, ttfb["before"])
            self.assertEqual(1000.0, ttfb["budget"])

    def test_report_builder_rejects_sensitive_or_incomparable_input(self) -> None:
        invalid_reports = (
            {**raw_evidence(), "samples": cast(list[object], raw_evidence()["samples"])[:4]},
            {**raw_evidence(), "cache_state": "mixed"},
            {**raw_evidence(), "session_token": "unsafe"},
        )
        with tempfile.TemporaryDirectory() as temporary_directory:
            temporary = Path(temporary_directory)
            budget_path = temporary / "budgets.json"
            _ = budget_path.write_text(json.dumps(budgets()), encoding="utf-8")
            for index, raw_report in enumerate(invalid_reports):
                raw_path = temporary / f"raw-{index}.json"
                _ = raw_path.write_text(json.dumps(raw_report), encoding="utf-8")
                result = subprocess.run(
                    [str(BUILDER), str(raw_path), str(budget_path)],
                    cwd=ROOT,
                    check=False,
                    capture_output=True,
                    text=True,
                )
                self.assertEqual(2, result.returncode, result.stdout)

    def test_browser_collector_and_probe_are_safely_wired(self) -> None:
        collector = ROOT / "ops/performance/collect-admin-performance.mjs"
        probe = ROOT / "wordpress/mu-plugins/hs-admin-performance-probe.php"
        self.assertTrue(collector.is_file())
        self.assertTrue(probe.is_file())

        collector_source = collector.read_text(encoding="utf-8")
        self.assertIn("WP_TEST_ADMIN_USER", collector_source)
        self.assertIn("WP_TEST_ADMIN_PASSWORD", collector_source)
        self.assertIn("sampleCount", collector_source)
        self.assertNotIn("console.log(password", collector_source)

        probe_source = probe.read_text(encoding="utf-8")
        self.assertIn("wp_get_environment_type", probe_source)
        self.assertIn("current_user_can", probe_source)
        self.assertIn("manage_options", probe_source)

        integration_runner = (ROOT / "ops/integration/run.sh").read_text(
            encoding="utf-8"
        )
        self.assertIn("RUN_PERFORMANCE", integration_runner)
        self.assertIn("collect-admin-performance.mjs", integration_runner)


if __name__ == "__main__":
    _ = unittest.main()
