import json
import subprocess
import tempfile
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SKILL = ROOT / ".agents/skills/newspaper-tagdiv"
AUDIT = SKILL / "scripts/audit_newspaper_change.py"


class NewspaperTagdivSkillTests(unittest.TestCase):
    def test_requested_guides_exist_and_link_official_documentation(self) -> None:
        required = (
            "SKILL.md",
            "composer.md",
            "cloud-templates.md",
            "theme-api.md",
            "modules.md",
            "blocks.md",
            "css-rules.md",
            "performance.md",
            "child-theme.md",
        )
        for filename in required:
            content = (SKILL / filename).read_text(encoding="utf-8")
            self.assertTrue(content.strip(), filename)
            if filename != "SKILL.md":
                self.assertIn("https://forum.tagdiv.com/", content, filename)

    def test_audit_rejects_staged_parent_theme_change_in_strict_mode(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            repo = Path(directory)
            protected = repo / "wordpress/themes/Newspaper_new/functions.php"
            protected.parent.mkdir(parents=True)
            protected.write_text("<?php\n", encoding="utf-8")
            subprocess.run(["git", "init", "-q"], cwd=repo, check=True)
            subprocess.run(["git", "add", "."], cwd=repo, check=True)
            subprocess.run(
                [
                    "git",
                    "-c",
                    "user.name=Test",
                    "-c",
                    "user.email=test@example.invalid",
                    "commit",
                    "-qm",
                    "baseline",
                ],
                cwd=repo,
                check=True,
            )
            protected.write_text("<?php\n// direct vendor edit\n", encoding="utf-8")
            subprocess.run(["git", "add", "."], cwd=repo, check=True)

            result = subprocess.run(
                ["python3", str(AUDIT), "--repo", str(repo), "--staged", "--strict"],
                capture_output=True,
                text=True,
            )

            self.assertEqual(1, result.returncode)
            report = json.loads(result.stdout)
            self.assertTrue(report["direct_vendor_change"])
            self.assertIn("parent theme functions.php changed", report["risks"])


if __name__ == "__main__":
    unittest.main()
