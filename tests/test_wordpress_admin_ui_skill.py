import json
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SKILL = ROOT / ".agents/skills/wordpress-admin-ui"


class WordPressAdminUISkillTests(unittest.TestCase):
    def test_skill_is_complete_and_registered(self) -> None:
        required = (
            "SKILL.md",
            "agents/openai.yaml",
            "references/architecture.md",
            "references/design-system.md",
            "references/patterns.md",
            "references/security.md",
            "references/testing.md",
        )
        for relative_path in required:
            content = (SKILL / relative_path).read_text(encoding="utf-8")
            self.assertTrue(content.strip(), relative_path)
            self.assertNotIn("TODO", content, relative_path)

        registry = json.loads(
            (ROOT / "config/ai-skills.json").read_text(encoding="utf-8")
        )
        local_skills = {item["name"]: item for item in registry["project_local"]}
        self.assertIn("wordpress-admin-ui", local_skills)
        self.assertIn(
            "wordpress-admin-ui", registry["task_routes"]["wordpress_admin_ui"]
        )

    def test_skill_requires_secure_accessible_responsive_work(self) -> None:
        skill = (SKILL / "SKILL.md").read_text(encoding="utf-8").lower()
        for requirement in (
            "capability",
            "nonce",
            "keyboard",
            "mobile",
            "loading",
            "empty",
            "error",
            "staging",
        ):
            self.assertIn(requirement, skill)

    def test_skill_is_synchronized_for_supported_agents(self) -> None:
        canonical = (SKILL / "SKILL.md").read_text(encoding="utf-8")
        for agent_directory in (".codex", ".claude"):
            synchronized = (
                ROOT / agent_directory / "skills/wordpress-admin-ui/SKILL.md"
            ).read_text(encoding="utf-8")
            self.assertEqual(canonical, synchronized)


if __name__ == "__main__":
    unittest.main()
