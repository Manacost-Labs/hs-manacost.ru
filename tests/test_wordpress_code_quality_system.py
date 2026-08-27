import json
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SKILLS = ROOT / ".agents/skills"


class WordPressCodeQualitySystemTests(unittest.TestCase):
    def test_composer_tools_are_pinned_and_scoped_to_first_party_code(self) -> None:
        composer = json.loads((ROOT / "composer.json").read_text(encoding="utf-8"))
        required = composer["require-dev"]
        self.assertEqual("3.4.1", required["wp-coding-standards/wpcs"])
        self.assertEqual(
            "2.1.8", required["phpcompatibility/phpcompatibility-wp"]
        )
        self.assertEqual("2.0.3", required["szepeviktor/phpstan-wordpress"])
        self.assertTrue((ROOT / "composer.lock").is_file())

        phpcs = (ROOT / "phpcs.xml.dist").read_text(encoding="utf-8")
        compatibility = (ROOT / "phpcompat.xml.dist").read_text(encoding="utf-8")
        phpstan = (ROOT / "phpstan.neon.dist").read_text(encoding="utf-8")
        for configuration in (phpcs, compatibility, phpstan):
            self.assertIn("wordpress/mu-plugins", configuration)
            self.assertNotIn("wordpress/themes/Newspaper_new", configuration)
            self.assertNotIn("wordpress/plugins", configuration)

    def test_quality_gate_and_ci_install_locked_dependencies(self) -> None:
        makefile = (ROOT / "Makefile").read_text(encoding="utf-8")
        workflow = (ROOT / ".github/workflows/quality.yml").read_text(
            encoding="utf-8"
        )
        self.assertIn("code-quality:", makefile)
        self.assertIn("composer validate --strict", makefile)
        self.assertIn("composer install --no-interaction --prefer-dist", workflow)
        self.assertIn("make code-quality", workflow)

    def test_wp_cli_diagnostics_are_version_pinned_and_non_production(self) -> None:
        manifest = json.loads(
            (ROOT / "config/wp-cli-diagnostics.json").read_text(encoding="utf-8")
        )
        self.assertEqual("2.1.5", manifest["packages"]["wp-cli/profile-command"])
        self.assertEqual("2.3.1", manifest["packages"]["wp-cli/doctor-command"])
        self.assertEqual(["local", "staging"], manifest["allowed_environments"])
        self.assertNotIn("production", manifest["allowed_environments"])

        installer = (ROOT / "ops/wp-cli/install-diagnostics.sh").read_text(
            encoding="utf-8"
        )
        self.assertIn("allowed_environments", installer)
        self.assertIn("wp package path", installer)
        self.assertIn("composer --working-dir", installer)
        self.assertIn("--environment", installer)

        result = subprocess.run(
            [
                str(ROOT / "ops/wp-cli/install-diagnostics.sh"),
                "--environment",
                "production",
            ],
            capture_output=True,
            text=True,
        )
        self.assertNotEqual(0, result.returncode)
        self.assertIn("not allowed", result.stderr)

    def test_clean_code_skill_is_registered_and_synchronized(self) -> None:
        skill_name = "wordpress-clean-code"
        skill = SKILLS / skill_name
        required_resources = (
            "SKILL.md",
            "agents/openai.yaml",
            "references/php-wordpress.md",
            "references/review-gates.md",
        )
        for relative_path in required_resources:
            content = (skill / relative_path).read_text(encoding="utf-8")
            self.assertTrue(content.strip(), relative_path)
            self.assertNotIn("TODO", content, relative_path)

        skill_text = (skill / "SKILL.md").read_text(encoding="utf-8")
        for marker in (
            "WPCS",
            "PHPCompatibilityWP",
            "PHPStan",
            "nonce",
            "capability",
            "escape",
            "newspaper-tagdiv",
        ):
            self.assertIn(marker, skill_text)

        registry = json.loads(
            (ROOT / "config/ai-skills.json").read_text(encoding="utf-8")
        )
        self.assertIn(
            skill_name,
            {item["name"] for item in registry["project_local"]},
        )
        self.assertIn(skill_name, registry["baseline_for_code_changes"])
        self.assertIn(skill_name, registry["task_routes"]["code_quality"])

        canonical = {
            path.relative_to(skill): path.read_bytes()
            for path in skill.rglob("*")
            if path.is_file()
        }
        for agent_directory in (".codex", ".claude"):
            synchronized = ROOT / agent_directory / "skills" / skill_name
            copy = {
                path.relative_to(synchronized): path.read_bytes()
                for path in synchronized.rglob("*")
                if path.is_file()
            }
            self.assertEqual(canonical, copy)

    def test_newspaper_skill_has_update_safe_change_contract(self) -> None:
        skill = SKILLS / "newspaper-tagdiv"
        skill_text = (skill / "SKILL.md").read_text(encoding="utf-8")
        safety = (skill / "change-safety.md").read_text(encoding="utf-8")
        self.assertIn("change-safety.md", skill_text)
        for marker in (
            "parent theme",
            "child theme",
            "MU-plugin",
            "Cloud Template",
            "visual regression",
            "rollback",
        ):
            self.assertIn(marker, safety)


if __name__ == "__main__":
    unittest.main()
