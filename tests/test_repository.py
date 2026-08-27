import json
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]


class RepositoryPolicyTests(unittest.TestCase):
    def test_configuration_is_valid_json(self) -> None:
        for relative_path in ("config/site.json", "config/plugins.json"):
            with (ROOT / relative_path).open(encoding="utf-8") as config_file:
                self.assertIsInstance(json.load(config_file), dict)

    def test_required_source_trees_exist(self) -> None:
        required = (
            "wordpress/mu-plugins",
            "wordpress/plugins",
            "wordpress/themes/Newspaper_new",
            "ops/nginx/origin.conf",
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


if __name__ == "__main__":
    unittest.main()

