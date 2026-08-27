import json
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SKILL = ROOT / ".agents/skills/hs-manacost-project"


class HSManacostProjectSkillTests(unittest.TestCase):
    def test_project_skill_has_complete_progressive_context(self) -> None:
        required = (
            "SKILL.md",
            "agents/openai.yaml",
            "scripts/context-snapshot.sh",
            "references/architecture.md",
            "references/task-routing.md",
            "references/data-and-migrations.md",
            "references/delivery-and-incidents.md",
            "references/verification.md",
        )
        for relative_path in required:
            content = (SKILL / relative_path).read_text(encoding="utf-8")
            self.assertTrue(content.strip(), relative_path)
            self.assertNotIn("TODO", content, relative_path)

        skill_text = (SKILL / "SKILL.md").read_text(encoding="utf-8").lower()
        for requirement in (
            "source repository",
            "runtime",
            "staging",
            "production",
            "rollback",
            "database",
            "s3",
            "proxy",
            "wordpress-article-editor",
            "wordpress-runtime-stack",
        ):
            self.assertIn(requirement, skill_text)

    def test_project_skill_is_mandatory_and_routed(self) -> None:
        registry = json.loads(
            (ROOT / "config/ai-skills.json").read_text(encoding="utf-8")
        )
        local_skills = {item["name"] for item in registry["project_local"]}
        self.assertIn("hs-manacost-project", local_skills)
        self.assertIn(
            "hs-manacost-project", registry["baseline_for_project_tasks"]
        )
        self.assertIn("hs-manacost-project", registry["task_routes"]["wordpress"])

    def test_context_snapshot_is_read_only_and_useful(self) -> None:
        script = SKILL / "scripts/context-snapshot.sh"
        self.assertTrue(script.stat().st_mode & 0o111)
        result = subprocess.run(
            [str(script)],
            cwd=ROOT,
            check=True,
            capture_output=True,
            text=True,
        )
        for marker in (
            "hs-manacost.ru",
            "hs-manacost.com",
            "test.hs-manacost.ru",
            "WordPress",
            "Newspaper_new",
            "Active plugins",
            "Required project skills",
        ):
            self.assertIn(marker, result.stdout)
        self.assertNotIn('"ipv4"', result.stdout)

    def test_agent_entrypoints_point_to_canonical_rules(self) -> None:
        for relative_path in ("CLAUDE.md", ".github/copilot-instructions.md"):
            content = (ROOT / relative_path).read_text(encoding="utf-8")
            self.assertIn("AGENTS.md", content)
            self.assertIn("config/ai-skills.json", content)
            self.assertIn("hs-manacost-project", content)

    def test_project_skill_is_synchronized(self) -> None:
        canonical = {
            path.relative_to(SKILL): path.read_bytes()
            for path in SKILL.rglob("*")
            if path.is_file()
        }
        for agent_directory in (".codex", ".claude"):
            synchronized = ROOT / agent_directory / "skills/hs-manacost-project"
            synchronized_files = {
                path.relative_to(synchronized): path.read_bytes()
                for path in synchronized.rglob("*")
                if path.is_file()
            }
            self.assertEqual(canonical, synchronized_files)


if __name__ == "__main__":
    unittest.main()
