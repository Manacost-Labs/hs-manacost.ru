import json
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SKILLS = ROOT / ".agents/skills"


class OperationalAISkillTests(unittest.TestCase):
    SKILL_FILES = {
        "wordpress-incident-response": (
            "references/incident-matrix.md",
            "references/evidence-and-recovery.md",
        ),
        "wordpress-media-integrity": (
            "references/media-pipeline.md",
            "references/recovery.md",
            "scripts/media-pipeline-status.sh",
        ),
        "wordpress-database-migrations": (
            "references/migration-contract.md",
            "references/verification.md",
        ),
        "wordpress-release-manager": (
            "references/release-gates.md",
            "references/rollback.md",
        ),
        "wordpress-observability": (
            "references/signals.md",
            "references/health-report.md",
            "scripts/read-only-health.sh",
        ),
        "wordpress-content-integrity": (
            "references/content-checks.md",
            "references/host-policy.md",
        ),
    }

    def test_operational_skills_are_complete(self) -> None:
        for name, resources in self.SKILL_FILES.items():
            skill = SKILLS / name
            for relative_path in ("SKILL.md", "agents/openai.yaml", *resources):
                content = (skill / relative_path).read_text(encoding="utf-8")
                self.assertTrue(content.strip(), f"{name}/{relative_path}")
                self.assertNotIn("TODO", content, f"{name}/{relative_path}")

            skill_text = (skill / "SKILL.md").read_text(encoding="utf-8")
            self.assertIn(f"name: {name}", skill_text)
            self.assertIn("test.hs-manacost.ru", skill_text)
            self.assertIn("rollback", skill_text.lower())

    def test_media_skill_models_optimizer_and_s3_contract(self) -> None:
        media_skill = "\n".join(
            path.read_text(encoding="utf-8")
            for path in (SKILLS / "wordpress-media-integrity").rglob("*.md")
        ).lower()
        for requirement in (
            "hs-local-image-optimizer",
            "source image",
            "sidecar",
            "webp",
            "avif",
            "uploads-webpc",
            "hs-manacost-s3-offload",
            "sha256",
            "same filename",
        ):
            self.assertIn(requirement, media_skill)

        status_script = SKILLS / "wordpress-media-integrity/scripts/media-pipeline-status.sh"
        self.assertTrue(status_script.stat().st_mode & 0o111)
        result = subprocess.run(
            [str(status_script)],
            cwd=ROOT,
            check=True,
            capture_output=True,
            text=True,
        )
        self.assertIn("hs-local-image-optimizer", result.stdout)
        self.assertIn("hs-manacost-s3-offload.timer", result.stdout)
        self.assertNotIn("password", result.stdout.lower())

    def test_observability_snapshot_is_read_only_and_domain_aware(self) -> None:
        health_script = SKILLS / "wordpress-observability/scripts/read-only-health.sh"
        self.assertTrue(health_script.stat().st_mode & 0o111)
        result = subprocess.run(
            [str(health_script)],
            cwd=ROOT,
            check=True,
            capture_output=True,
            text=True,
        )
        for marker in (
            "hs-manacost.ru",
            "hs-manacost.com",
            "test.hs-manacost.ru",
            "Moscow proxy",
            "Novosibirsk proxy",
        ):
            self.assertIn(marker, result.stdout)

    def test_skills_are_registered_routed_and_synchronized(self) -> None:
        registry = json.loads(
            (ROOT / "config/ai-skills.json").read_text(encoding="utf-8")
        )
        registered = {item["name"] for item in registry["project_local"]}
        for name in self.SKILL_FILES:
            self.assertIn(name, registered)

        routes = registry["task_routes"]
        self.assertIn("wordpress-incident-response", routes["incident"])
        self.assertIn("wordpress-media-integrity", routes["media_integrity"])
        self.assertIn("wordpress-database-migrations", routes["database_migration"])
        self.assertIn("wordpress-release-manager", routes["release"])
        self.assertIn("wordpress-observability", routes["observability"])
        self.assertIn("wordpress-content-integrity", routes["content_integrity"])

        for name in self.SKILL_FILES:
            canonical = {
                path.relative_to(SKILLS / name): path.read_bytes()
                for path in (SKILLS / name).rglob("*")
                if path.is_file()
            }
            for agent_directory in (".codex", ".claude"):
                synchronized = ROOT / agent_directory / "skills" / name
                copy = {
                    path.relative_to(synchronized): path.read_bytes()
                    for path in synchronized.rglob("*")
                    if path.is_file()
                }
                self.assertEqual(canonical, copy)


if __name__ == "__main__":
    unittest.main()
