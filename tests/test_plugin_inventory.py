import json
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]


class PluginInventoryTests(unittest.TestCase):
    def test_inventory_matches_every_regular_plugin_directory(self) -> None:
        inventory = json.loads(
            (ROOT / "config/wordpress-plugins.json").read_text(encoding="utf-8")
        )
        recorded = {plugin["slug"] for plugin in inventory["plugins"]}
        actual = {
            path.name
            for path in (ROOT / "wordpress/plugins").iterdir()
            if path.is_dir()
        }
        self.assertEqual(actual, recorded)

        for plugin in inventory["plugins"]:
            self.assertIn(plugin["status"], {"active", "inactive"})
            self.assertTrue(plugin["version"])
            self.assertIn(
                plugin["origin"],
                {"custom", "legacy", "wordpress.org", "commercial", "tagdiv"},
            )

    def test_runtime_and_nested_repository_artifacts_are_excluded(self) -> None:
        forbidden_parts = {".git", ".svn", ".hg", ".claude", ".codegraph"}
        failures = []
        for path in (ROOT / "wordpress/plugins").rglob("*"):
            relative = path.relative_to(ROOT / "wordpress/plugins")
            if forbidden_parts.intersection(relative.parts):
                failures.append(str(relative))
            if path.is_file() and (
                path.name.startswith(".env")
                or ".bak" in path.name
                or path.suffix == ".orig"
                or path.suffix == ".log"
            ):
                failures.append(str(relative))
        self.assertEqual([], sorted(set(failures)))


if __name__ == "__main__":
    unittest.main()
