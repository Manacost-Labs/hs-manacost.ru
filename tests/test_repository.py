import json
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]


class RepositoryPolicyTests(unittest.TestCase):
    def test_configuration_is_valid_json(self) -> None:
        for relative_path in (
            "config/site.json",
            "config/plugins.json",
            "config/network.json",
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
        )
        for relative_path in required:
            self.assertTrue((ROOT / relative_path).exists(), relative_path)

    def test_forbidden_runtime_files_are_not_tracked(self) -> None:
        forbidden_names = {"wp-config.php", ".env", ".htpasswd"}
        forbidden_suffixes = {".sql", ".dump", ".pem", ".key", ".p12", ".pfx"}
        failures: list[str] = []

        for path in ROOT.rglob("*"):
            if ".git" in path.parts or not path.is_file():
                continue
            relative_path = path.relative_to(ROOT)
            if path.name in forbidden_names or path.suffix.lower() in forbidden_suffixes:
                failures.append(str(relative_path))
            if "uploads" in relative_path.parts or "cache" in relative_path.parts:
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


if __name__ == "__main__":
    unittest.main()
