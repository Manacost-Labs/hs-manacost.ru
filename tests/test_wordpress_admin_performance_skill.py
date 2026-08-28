import json
import subprocess
import tempfile
import unittest
from pathlib import Path
from typing import cast

ROOT = Path(__file__).resolve().parents[1]
SKILL = ROOT / ".agents/skills/wordpress-admin-performance"


def evidence(*, interactive_after: float = 1700) -> dict[str, object]:
    return {
        "schema_version": 1,
        "environment": "staging",
        "screen": "post-editor",
        "authenticated_role": "editor",
        "dataset_size": 1000,
        "sample_count": 5,
        "cache_state": "warm",
        "metrics": [
            {
                "name": "ttfb_ms",
                "unit": "ms",
                "before": 980,
                "after": 720,
                "budget": 1200,
            },
            {
                "name": "interactive_ms",
                "unit": "ms",
                "before": 2400,
                "after": interactive_after,
                "budget": 2000,
            },
            {
                "name": "sql_queries",
                "unit": "count",
                "before": 126,
                "after": 88,
                "budget": 100,
            },
            {
                "name": "peak_memory_mb",
                "unit": "MB",
                "before": 118,
                "after": 96,
                "budget": 128,
            },
            {
                "name": "long_tasks",
                "unit": "count",
                "before": 6,
                "after": 2,
                "budget": 3,
            },
        ],
        "functional_checks": {
            "behavior": True,
            "permissions": True,
            "desktop": True,
            "mobile": True,
            "error_path": True,
        },
    }


class WordPressAdminPerformanceSkillTests(unittest.TestCase):
    def test_skill_is_complete_registered_routed_and_synchronized(self) -> None:
        required = (
            "SKILL.md",
            "agents/openai.yaml",
            "references/budgets.md",
            "references/profiling.md",
            "references/database-and-runtime.md",
            "references/editor-and-ajax.md",
            "references/testing-and-release.md",
            "scripts/evaluate_admin_performance.py",
        )
        for relative_path in required:
            content = (SKILL / relative_path).read_text(encoding="utf-8")
            self.assertTrue(content.strip(), relative_path)
            self.assertNotIn("TODO", content, relative_path)

        registry = cast(
            dict[str, object],
            json.loads(
                (ROOT / "config/ai-skills.json").read_text(encoding="utf-8")
            ),
        )
        registry_entries = cast(list[dict[str, object]], registry["project_local"])
        registered = {cast(str, item["name"]) for item in registry_entries}
        routes = cast(dict[str, list[str]], registry["task_routes"])
        self.assertIn("wordpress-admin-performance", registered)
        self.assertIn(
            "wordpress-admin-performance",
            routes["wordpress_admin_performance"],
        )
        project_rules = (ROOT / "AGENTS.md").read_text(encoding="utf-8")
        project_skill = (
            ROOT / ".agents/skills/hs-manacost-project/SKILL.md"
        ).read_text(encoding="utf-8")
        self.assertIn("wordpress-admin-performance", project_rules)
        self.assertIn("wordpress-admin-performance", project_skill)

        canonical = {
            path.relative_to(SKILL): path.read_bytes()
            for path in SKILL.rglob("*")
            if path.is_file()
        }
        for agent_directory in (".codex", ".claude"):
            synchronized = ROOT / agent_directory / "skills/wordpress-admin-performance"
            copy = {
                path.relative_to(synchronized): path.read_bytes()
                for path in synchronized.rglob("*")
                if path.is_file()
            }
            self.assertEqual(canonical, copy)

    def test_evaluator_accepts_complete_evidence_and_blocks_regression(self) -> None:
        evaluator = SKILL / "scripts/evaluate_admin_performance.py"
        self.assertTrue(evaluator.stat().st_mode & 0o111)

        with tempfile.TemporaryDirectory() as temporary_directory:
            report = Path(temporary_directory) / "report.json"
            _ = report.write_text(json.dumps(evidence()), encoding="utf-8")
            passed = subprocess.run(
                [str(evaluator), str(report)],
                cwd=ROOT,
                check=False,
                capture_output=True,
                text=True,
            )
            self.assertEqual(0, passed.returncode, passed.stderr)
            passed_result = cast(dict[str, object], json.loads(passed.stdout))
            self.assertEqual("PASS", passed_result["status"])

            _ = report.write_text(
                json.dumps(evidence(interactive_after=2600)), encoding="utf-8"
            )
            blocked = subprocess.run(
                [str(evaluator), str(report)],
                cwd=ROOT,
                check=False,
                capture_output=True,
                text=True,
            )
            self.assertEqual(1, blocked.returncode, blocked.stderr)
            result = cast(dict[str, object], json.loads(blocked.stdout))
            self.assertEqual("BLOCKED", result["status"])
            reasons = cast(list[str], result["reasons"])
            self.assertTrue(
                any("interactive_ms" in reason for reason in reasons)
            )

    def test_evaluator_rejects_unsafe_or_incomparable_evidence(self) -> None:
        evaluator = SKILL / "scripts/evaluate_admin_performance.py"
        failed_functional_check = evidence()
        functional_checks = cast(
            dict[str, bool], failed_functional_check["functional_checks"]
        )
        functional_checks["permissions"] = False
        invalid_reports: tuple[dict[str, object], ...] = (
            {**evidence(), "sample_count": 1},
            {**evidence(), "cache_state": "mixed"},
            {**evidence(), "auth_cookie": "must-not-be-recorded"},
            failed_functional_check,
        )

        with tempfile.TemporaryDirectory() as temporary_directory:
            report = Path(temporary_directory) / "report.json"
            for invalid_report in invalid_reports[:3]:
                _ = report.write_text(json.dumps(invalid_report), encoding="utf-8")
                result = subprocess.run(
                    [str(evaluator), str(report)],
                    cwd=ROOT,
                    check=False,
                    capture_output=True,
                    text=True,
                )
                self.assertEqual(2, result.returncode, result.stdout)
                self.assertEqual("INVALID", json.loads(result.stdout)["status"])

            _ = report.write_text(json.dumps(invalid_reports[3]), encoding="utf-8")
            blocked = subprocess.run(
                [str(evaluator), str(report)],
                cwd=ROOT,
                check=False,
                capture_output=True,
                text=True,
            )
            self.assertEqual(1, blocked.returncode, blocked.stdout)
            self.assertIn("permissions", blocked.stdout)


if __name__ == "__main__":
    _ = unittest.main()
