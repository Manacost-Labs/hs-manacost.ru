import json
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SKILLS = ROOT / ".agents/skills"


class SpecializedWordPressSkillTests(unittest.TestCase):
    def test_article_editor_skill_is_complete(self) -> None:
        skill = SKILLS / "wordpress-article-editor"
        required = (
            "SKILL.md",
            "agents/openai.yaml",
            "references/editor-stack.md",
            "references/extensions.md",
            "references/media-content.md",
            "references/compatibility.md",
            "references/testing.md",
        )
        self._assert_complete_skill(skill, required)

        content = (skill / "SKILL.md").read_text(encoding="utf-8").lower()
        for requirement in (
            "classic editor",
            "tinymce",
            "gutenberg",
            "autosave",
            "revision",
            "s3",
            "shortcode",
            "hs-editor-workspace",
            "newspaper-tagdiv",
            "wordpress-admin-ui",
        ):
            self.assertIn(requirement, content)

    def test_runtime_stack_skill_is_complete(self) -> None:
        skill = SKILLS / "wordpress-runtime-stack"
        required = (
            "SKILL.md",
            "agents/openai.yaml",
            "references/stack-map.md",
            "references/cache-layers.md",
            "references/seo-security.md",
            "references/operations.md",
            "references/testing.md",
        )
        self._assert_complete_skill(skill, required)

        content = (skill / "SKILL.md").read_text(encoding="utf-8").lower()
        for requirement in (
            "wp rocket",
            "redis",
            "cloudflare",
            "perfmatters",
            "all in one seo",
            "wordfence",
            "redirection",
            "manacost-cache-purge",
            "purge",
            "rollback",
            "staging",
            "proxy",
        ):
            self.assertIn(requirement, content)

    def test_skills_are_registered_and_routed(self) -> None:
        registry = json.loads(
            (ROOT / "config/ai-skills.json").read_text(encoding="utf-8")
        )
        local_skills = {item["name"] for item in registry["project_local"]}
        routes = registry["task_routes"]

        self.assertIn("wordpress-article-editor", local_skills)
        self.assertIn("wordpress-runtime-stack", local_skills)
        self.assertIn("wordpress-article-editor", routes["wordpress_article_editor"])
        self.assertIn("wordpress-runtime-stack", routes["wordpress_runtime_stack"])

    def test_skills_are_synchronized_for_supported_agents(self) -> None:
        for skill_name in ("wordpress-article-editor", "wordpress-runtime-stack"):
            canonical = SKILLS / skill_name
            canonical_files = {
                path.relative_to(canonical): path.read_bytes()
                for path in canonical.rglob("*")
                if path.is_file()
            }
            for agent_directory in (".codex", ".claude"):
                synchronized = ROOT / agent_directory / "skills" / skill_name
                synchronized_files = {
                    path.relative_to(synchronized): path.read_bytes()
                    for path in synchronized.rglob("*")
                    if path.is_file()
                }
                self.assertEqual(canonical_files, synchronized_files)

    def _assert_complete_skill(
        self, skill: Path, required_paths: tuple[str, ...]
    ) -> None:
        for relative_path in required_paths:
            content = (skill / relative_path).read_text(encoding="utf-8")
            self.assertTrue(content.strip(), relative_path)
            self.assertNotIn("TODO", content, relative_path)


if __name__ == "__main__":
    unittest.main()
