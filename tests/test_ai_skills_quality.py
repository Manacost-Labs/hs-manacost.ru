import importlib.util
import sys
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
AUDITOR_PATH = ROOT / "ops/ai-skills/audit.py"
sys.dont_write_bytecode = True


def load_auditor():
    spec = importlib.util.spec_from_file_location("ai_skills_audit", AUDITOR_PATH)
    if spec is None or spec.loader is None:
        raise RuntimeError(f"Cannot load {AUDITOR_PATH}")
    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module
    spec.loader.exec_module(module)
    return module


class AISkillsQualityTests(unittest.TestCase):
    def test_every_project_skill_passes_the_complete_audit(self) -> None:
        auditor = load_auditor()
        result = auditor.audit(ROOT)
        self.assertEqual(35, result.skills)
        self.assertGreaterEqual(result.scripts, 25)
        self.assertEqual([], result.errors)


if __name__ == "__main__":
    unittest.main()
