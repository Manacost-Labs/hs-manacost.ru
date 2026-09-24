import json
import subprocess
import unittest
from pathlib import Path
from typing import cast


ROOT = Path(__file__).resolve().parents[1]
SKILL = ROOT / ".agents/skills/wordpress-change-impact"
ANALYZER = SKILL / "scripts/analyze_change_impact.py"


def analyze(*paths: str, output_format: str = "json") -> subprocess.CompletedProcess[str]:
    return subprocess.run(
        [str(ANALYZER), "--format", output_format, "--paths", *paths],
        cwd=ROOT,
        check=False,
        capture_output=True,
        text=True,
    )


class WordPressChangeImpactSkillTests(unittest.TestCase):
    def test_skill_is_registered_routed_and_synchronized(self) -> None:
        for relative_path in (
            "SKILL.md",
            "agents/openai.yaml",
            "references/impact-model.md",
            "references/release-usage.md",
            "scripts/analyze_change_impact.py",
        ):
            content = (SKILL / relative_path).read_text(encoding="utf-8")
            self.assertTrue(content.strip(), relative_path)
            self.assertNotIn("TODO", content, relative_path)

        registry = cast(
            dict[str, object],
            json.loads((ROOT / "config/ai-skills.json").read_text(encoding="utf-8")),
        )
        registered = {
            cast(str, item["name"])
            for item in cast(list[dict[str, object]], registry["project_local"])
        }
        routes = cast(dict[str, list[str]], registry["task_routes"])
        self.assertIn("wordpress-change-impact", registered)
        self.assertIn("wordpress-change-impact", routes["change_impact"])

        canonical = {
            path.relative_to(SKILL): path.read_bytes()
            for path in SKILL.rglob("*")
            if path.is_file()
        }
        for agent_directory in (".codex", ".claude"):
            synchronized = ROOT / agent_directory / "skills/wordpress-change-impact"
            copy = {
                path.relative_to(synchronized): path.read_bytes()
                for path in synchronized.rglob("*")
                if path.is_file()
            }
            self.assertEqual(canonical, copy)

    def test_analyzer_maps_admin_change_to_required_checks_and_contracts(self) -> None:
        result = analyze("wordpress/mu-plugins/hs-admin-performance-probe.php")
        self.assertEqual(0, result.returncode, result.stderr)
        report = cast(dict[str, object], json.loads(result.stdout))
        self.assertIn(report["risk"], {"medium", "high"})
        self.assertIn("wp-admin", cast(list[str], report["surfaces"]))
        self.assertIn("wordpress-admin-performance", cast(list[str], report["skills"]))
        self.assertIn("make admin-performance", cast(list[str], report["checks"]))
        self.assertIn("test.hs-manacost.ru", cast(list[str], report["domains"]))
        self.assertFalse(report["manual_review_required"])

    def test_analyzer_escalates_new_unclassified_first_party_php(self) -> None:
        result = analyze("wordpress/mu-plugins/unclassified-feature.php")
        self.assertEqual(1, result.returncode, result.stderr)
        report = cast(dict[str, object], json.loads(result.stdout))
        self.assertEqual("high", report["risk"])
        self.assertTrue(report["manual_review_required"])
        self.assertIn(
            "wordpress/mu-plugins/unclassified-feature.php",
            cast(list[str], report["unclassified_first_party"]),
        )

    def test_dashboard_deck_widget_has_explicit_admin_owner(self) -> None:
        result = analyze("wordpress/mu-plugins/hs-dashboard-latest-decks.php")
        self.assertEqual(0, result.returncode, result.stderr)
        report = cast(dict[str, object], json.loads(result.stdout))
        self.assertFalse(report["manual_review_required"])
        self.assertIn("wordpress-admin-performance", cast(list[str], report["skills"]))
        self.assertIn("make admin-performance", cast(list[str], report["checks"]))

    def test_analyzer_maps_newspaper_and_infrastructure_changes(self) -> None:
        result = analyze(
            "wordpress/themes/Newspaper_new/style.css",
            "ops/nginx/staging.conf",
            output_format="markdown",
        )
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertIn("Risk: high", result.stdout)
        self.assertIn("newspaper-tagdiv", result.stdout)
        self.assertIn("nginx -t", result.stdout)
        self.assertIn("make visual", result.stdout)


if __name__ == "__main__":
    _ = unittest.main()
