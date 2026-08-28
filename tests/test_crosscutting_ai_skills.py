import importlib.util
import json
import sys
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SKILLS = ROOT / ".agents/skills"
sys.dont_write_bytecode = True


def load_script(name: str, path: Path):
    spec = importlib.util.spec_from_file_location(name, path)
    if spec is None or spec.loader is None:
        raise RuntimeError(f"Cannot load {path}")
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


def skill_files(path: Path) -> dict[Path, bytes]:
    return {
        item.relative_to(path): item.read_bytes()
        for item in path.rglob("*")
        if item.is_file()
        and "__pycache__" not in item.parts
        and item.suffix != ".pyc"
    }


class CrosscuttingAISkillTests(unittest.TestCase):
    SKILL_RESOURCES = {
        "wordpress-editorial-publish-gate": (
            "agents/openai.yaml",
            "references/gate-matrix.md",
            "references/evidence-contract.md",
            "scripts/evaluate_publish_gate.py",
        ),
        "wordpress-privacy-consent": (
            "agents/openai.yaml",
            "references/data-map.md",
            "references/consent-matrix.md",
        ),
        "wordpress-accessibility": (
            "agents/openai.yaml",
            "references/test-matrix.md",
            "references/newspaper-admin-patterns.md",
            "scripts/audit_accessibility_html.py",
        ),
        "wordpress-external-integrations": (
            "agents/openai.yaml",
            "references/contract-schema.md",
            "references/failure-matrix.md",
            "scripts/validate_integration_contract.py",
        ),
    }

    def test_skills_are_complete(self) -> None:
        for name, resources in self.SKILL_RESOURCES.items():
            for relative_path in ("SKILL.md", *resources):
                content = (SKILLS / name / relative_path).read_text(encoding="utf-8")
                self.assertTrue(content.strip(), f"{name}/{relative_path}")
                self.assertNotIn("TODO", content, f"{name}/{relative_path}")

    def test_publish_gate_blocks_missing_evidence(self) -> None:
        gate = load_script(
            "evaluate_publish_gate",
            SKILLS / "wordpress-editorial-publish-gate/scripts/evaluate_publish_gate.py",
        )
        blocked = gate.evaluate({"subject": "post 1", "target": "staging", "checks": {}})
        self.assertEqual("BLOCKED", blocked["result"])
        self.assertTrue(any("missing baseline check" in item for item in blocked["errors"]))

        checks = {
            check_id: {"status": "pass", "evidence": f"observed {check_id}"}
            for check_id in gate.BASELINE_CHECKS
        }
        ready = gate.evaluate(
            {
                "subject": "staging post 1",
                "target": "test.hs-manacost.ru",
                "checks": checks,
                "rollback": {
                    "revision": "2",
                    "commit": "0123456789abcdef0123456789abcdef01234567",
                },
            }
        )
        self.assertEqual({"result": "READY", "errors": []}, ready)

    def test_accessibility_auditor_detects_structural_errors(self) -> None:
        accessibility = load_script(
            "audit_accessibility_html",
            SKILLS / "wordpress-accessibility/scripts/audit_accessibility_html.py",
        )
        good = '<html lang="ru"><h1>Материал</h1><label for="q">Поиск</label><input id="q"><img alt="" src="x"></html>'
        self.assertEqual([], accessibility.audit(good))
        errors = accessibility.audit("<html><h1>A</h1><h3>B</h3><input><img src='x'></html>")
        self.assertTrue(any("lang" in item for item in errors))
        self.assertTrue(any("heading level" in item for item in errors))
        self.assertTrue(any("accessible label" in item for item in errors))
        self.assertTrue(any("alt" in item for item in errors))

    def test_integration_contract_validator_requires_resilience(self) -> None:
        integrations = load_script(
            "validate_integration_contract",
            SKILLS / "wordpress-external-integrations/scripts/validate_integration_contract.py",
        )
        contract = {
            "name": "telegram-alert",
            "owner": "editorial operations",
            "purpose": "notify about a stored submission",
            "direction": "outbound",
            "endpoints": ["api.telegram.org"],
            "authentication": {"method": "bearer", "variable": "TELEGRAM_BOT_TOKEN"},
            "data_classes": ["operational metadata"],
            "timeout_seconds": 5,
            "retry": {"max_attempts": 3, "conditions": [429, 500]},
            "idempotency": {"key": "submission_id"},
            "rate_limit": {"on_429": "bounded retry-after"},
            "observability": {"metric": "delivery outcome"},
            "degradation": {"behavior": "submission remains stored"},
            "disable_switch": {"flag": "TELEGRAM_ALERTS_ENABLED"},
        }
        self.assertEqual([], integrations.validate(contract))
        contract["retry"]["max_attempts"] = True
        self.assertTrue(any("positive integer" in item for item in integrations.validate(contract)))
        contract["retry"]["max_attempts"] = 3
        del contract["timeout_seconds"]
        self.assertTrue(any("timeout_seconds" in item for item in integrations.validate(contract)))

    def test_skills_are_registered_routed_and_synchronized(self) -> None:
        registry = json.loads((ROOT / "config/ai-skills.json").read_text(encoding="utf-8"))
        self.assertGreaterEqual(registry["version"], 5)
        registered = {item["name"]: item for item in registry["project_local"]}
        routes = {
            "wordpress-editorial-publish-gate": "editorial_publish_gate",
            "wordpress-privacy-consent": "privacy_consent",
            "wordpress-accessibility": "accessibility",
            "wordpress-external-integrations": "external_integrations",
        }
        for name, route in routes.items():
            self.assertEqual("project", registered[name]["source"])
            self.assertIn(name, registry["task_routes"][route])
            canonical = skill_files(SKILLS / name)
            for agent_directory in (".codex", ".claude"):
                synchronized = ROOT / agent_directory / "skills" / name
                self.assertEqual(canonical, skill_files(synchronized))

    def test_project_routing_mentions_all_new_skills(self) -> None:
        routing = (
            (ROOT / "AGENTS.md").read_text(encoding="utf-8")
            + (SKILLS / "hs-manacost-project/SKILL.md").read_text(encoding="utf-8")
        )
        for name in self.SKILL_RESOURCES:
            self.assertIn(name, routing)

    def test_editor_snapshot_removes_only_dynamic_autosave_notice(self) -> None:
        visual_spec = (ROOT / "tests/visual/wordpress.spec.ts").read_text(
            encoding="utf-8"
        )
        self.assertIn("removeDynamicEditorNotices", visual_spec)
        self.assertIn(".notice-warning", visual_spec)
        self.assertIn('a[href*="revision.php"]', visual_spec)
        self.assertNotIn("updateSnapshots", visual_spec)


if __name__ == "__main__":
    unittest.main()
