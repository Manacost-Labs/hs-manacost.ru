import json
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]


class RepositoryPolicyTests(unittest.TestCase):
    def test_configuration_is_valid_json(self) -> None:
        for relative_path in (
            "config/site.json",
            "config/plugins.json",
            "config/network.json",
            "config/ai-skills.json",
            "config/wordpress-plugins.json",
        ):
            with (ROOT / relative_path).open(encoding="utf-8") as config_file:
                self.assertIsInstance(json.load(config_file), dict)

    def test_required_source_trees_exist(self) -> None:
        required = (
            "wordpress/mu-plugins",
            "wordpress/plugins",
            "wordpress/themes/Newspaper_new",
            "ops/nginx/origin.conf",
            "ops/nginx/mirror.conf",
            "ops/nginx/staging.conf",
            "ops/smoke-check.sh",
            "AGENTS.md",
            ".agents/skills/wordpress-plugin-dev/SKILL.md",
            ".claude/skills/wordpress-plugin-dev/SKILL.md",
        )
        for relative_path in required:
            self.assertTrue((ROOT / relative_path).exists(), relative_path)

    def test_forbidden_runtime_files_are_not_tracked(self) -> None:
        forbidden_names = {"wp-config.php", ".env", ".htpasswd"}
        forbidden_suffixes = {".sql", ".dump", ".pem", ".key", ".p12", ".pfx"}
        public_verification_material = {
            "wordpress/plugins/wordfence/lib/noc1.key",
            "wordpress/plugins/wordfence/vendor/wordfence/wf-waf/src/cacert.pem",
            "wordpress/plugins/wordfence/vendor/wordfence/wf-waf/src/falsepositive.key",
            "wordpress/plugins/wordfence/vendor/wordfence/wf-waf/src/rules.key",
        }
        failures: list[str] = []

        tracked = subprocess.run(
            ["git", "ls-files", "--cached", "--others", "--exclude-standard"],
            cwd=ROOT,
            check=True,
            capture_output=True,
            text=True,
        ).stdout.splitlines()
        for relative in tracked:
            relative_path = Path(relative)
            path = ROOT / relative_path
            if not path.is_file():
                continue
            if path.name in forbidden_names or path.suffix.lower() in forbidden_suffixes:
                if relative not in public_verification_material:
                    failures.append(str(relative_path))
            if "uploads" in relative_path.parts:
                failures.append(str(relative_path))

        self.assertEqual([], sorted(set(failures)))

    def test_no_nested_git_repositories(self) -> None:
        nested = [
            str(path.relative_to(ROOT))
            for path in ROOT.rglob(".git")
            if path != ROOT / ".git"
        ]
        self.assertEqual([], nested)

    def test_all_domains_are_owned_by_this_repository(self) -> None:
        site = json.loads((ROOT / "config/site.json").read_text(encoding="utf-8"))
        self.assertEqual("https://hs-manacost.ru", site["site"]["primary_url"])
        self.assertEqual("https://hs-manacost.com", site["site"]["mirror_url"])
        self.assertEqual("https://test.hs-manacost.ru", site["site"]["staging_url"])

    def test_pipeline_keeps_production_manual(self) -> None:
        staging = (ROOT / ".github/workflows/deploy-staging.yml").read_text(encoding="utf-8")
        production = (ROOT / ".github/workflows/promote-production.yml").read_text(encoding="utf-8")
        self.assertIn("workflow_run:", staging)
        self.assertIn("workflow_run.event == 'push'", staging)
        self.assertIn("head_repository.full_name == github.repository", staging)
        self.assertIn("smoke-check.sh staging", staging)
        self.assertIn("workflow_dispatch:", production)
        self.assertIn("successful staging deployment", production)
        self.assertIn("smoke-check.sh production", production)

    def test_required_ai_skills_are_pinned(self) -> None:
        registry = json.loads((ROOT / "config/ai-skills.json").read_text(encoding="utf-8"))
        baseline = set(registry["baseline_for_code_changes"])
        self.assertTrue(
            {
                "agent-test-driven-development",
                "agent-code-review-and-quality",
                "agent-code-simplification",
                "agent-security-and-hardening",
                "agent-git-workflow-and-versioning",
            }.issubset(baseline)
        )
        routes = registry["task_routes"]
        self.assertIn("seo-technical", routes["seo"])
        self.assertIn("web-quality-core-web-vitals", routes["performance"])
        self.assertIn("frontend-design", routes["frontend_design"])

        local_skills = {item["name"]: item for item in registry["project_local"]}
        for name in (
            "newspaper-tagdiv",
            "wordpress-router",
            "wp-performance",
            "wp-phpstan",
            "wp-plugin-development",
            "wp-project-triage",
            "wp-rest-api",
            "wp-wpcli-and-ops",
        ):
            self.assertIn(name, local_skills)
            skill_path = ROOT / local_skills[name]["path"]
            self.assertTrue(skill_path.is_file(), str(skill_path))

        self.assertIn("newspaper-tagdiv", routes["newspaper_tagdiv"])


if __name__ == "__main__":
    unittest.main()
