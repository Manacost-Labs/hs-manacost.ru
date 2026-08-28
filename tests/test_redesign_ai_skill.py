import importlib.util
import json
import sys
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SKILLS = ROOT / ".agents/skills"
SKILL = SKILLS / "wordpress-redesign-system"
sys.dont_write_bytecode = True


def load_validator():
    path = SKILL / "scripts/validate_redesign_contract.py"
    spec = importlib.util.spec_from_file_location("validate_redesign_contract", path)
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


def valid_contract() -> dict:
    return {
        "project": "hs-manacost editorial redesign",
        "audience": "Russian-speaking Hearthstone readers and editors",
        "goals": ["make news and guides easier to scan"],
        "user_journeys": [
            {"name": "read guide", "entry": "homepage", "success": "article read"}
        ],
        "pages": [
            {"template": "homepage", "job": "prioritize current editorial material"}
        ],
        "direction": {
            "name": "Tavern desk",
            "thesis": "Editorial clarity shaped by Hearthstone play surfaces",
            "signature": "A restrained live-meta rail",
            "rationale": "Supports daily scanning without hiding articles",
            "avoid": ["generic card grid", "purple gradient"],
        },
        "tokens": {
            "colors": {
                "surface": "#071923",
                "text": "#F7F4E8",
                "accent": "#E8A735",
                "border": "#36505E",
            },
            "typography": {
                "display": "approved Cyrillic display",
                "body": "approved Cyrillic body",
                "utility": "approved Cyrillic utility",
                "scale": [14, 16, 20, 28, 40],
                "rationale": "Editorial hierarchy with dense utility metadata",
            },
            "spacing": [4, 8, 12, 16, 24, 32],
            "radii": [0, 4, 8],
            "shadows": {"raised": "0 2px 8px rgb(0 0 0 / 20%)"},
            "motion": {"standard_ms": 160, "reduced": "none"},
        },
        "components": [
            {"name": "article-card", "states": ["default", "missing-image"]}
        ],
        "responsive": {
            "viewports": [320, 390, 768, 1024, 1440],
            "zoom_percent": 200,
            "horizontal_overflow_px": 0,
        },
        "quality": {
            "accessibility": "keyboard, axe and reduced motion",
            "performance": "measure cold and warm LCP INP CLS",
            "seo": "hs-manacost.ru canonical; hs-manacost.com noindex; test.hs-manacost.ru noindex",
            "media": "S3, aspect ratio, fallback and CLS",
            "ads": "preserve current slots",
            "analytics": "one production tracker; staging disabled",
        },
        "implementation": {
            "ownership_layers": ["cloud-template", "mu-plugin"],
            "vertical_slices": ["navigation", "homepage", "article"],
        },
        "rollout": {
            "staging_evidence": "screenshots and flows",
            "approval_owner": "site owner",
            "rollback_commit": "0123456789abcdef0123456789abcdef01234567",
            "template_restore": "restore recorded template assignments",
        },
    }


class RedesignAISkillTests(unittest.TestCase):
    def test_skill_is_complete_and_specific(self) -> None:
        required = (
            "SKILL.md",
            "agents/openai.yaml",
            "references/design-contract.md",
            "references/component-system.md",
            "references/newspaper-implementation.md",
            "references/acceptance-matrix.md",
            "scripts/validate_redesign_contract.py",
        )
        for relative_path in required:
            content = (SKILL / relative_path).read_text(encoding="utf-8")
            self.assertTrue(content.strip(), relative_path)
            self.assertNotIn("TODO", content)

        content = "\n".join(
            path.read_text(encoding="utf-8") for path in SKILL.rglob("*.md")
        ).lower()
        for marker in (
            "newspaper",
            "cloud template",
            "real content",
            "cyrillic",
            "320",
            "1440",
            "200% zoom",
            "visual regression",
            "hs-manacost.com",
            "noindex",
            "rollback",
        ):
            self.assertIn(marker, content)

    def test_contract_validator_accepts_complete_project_contract(self) -> None:
        validator = load_validator()
        self.assertEqual([], validator.validate(valid_contract()))

    def test_contract_validator_blocks_generic_or_unsafe_contracts(self) -> None:
        validator = load_validator()
        contract = valid_contract()
        contract["direction"]["signature"] = ""
        contract["responsive"]["viewports"] = [1440]
        contract["implementation"]["ownership_layers"] = ["parent-theme"]
        errors = validator.validate(contract)
        self.assertTrue(any("direction.signature" in error for error in errors))
        self.assertTrue(any("responsive.viewports" in error for error in errors))
        self.assertTrue(any("unsupported layer" in error for error in errors))

    def test_skill_is_registered_routed_and_synchronized(self) -> None:
        registry = json.loads((ROOT / "config/ai-skills.json").read_text(encoding="utf-8"))
        self.assertGreaterEqual(registry["version"], 5)
        registered = {item["name"]: item for item in registry["project_local"]}
        self.assertEqual("project", registered["wordpress-redesign-system"]["source"])
        self.assertIn(
            "wordpress-redesign-system", registry["task_routes"]["wordpress_redesign"]
        )
        self.assertIn(
            "wordpress-redesign-system", registry["task_routes"]["frontend_design"]
        )
        canonical = skill_files(SKILL)
        for agent_directory in (".codex", ".claude"):
            synchronized = ROOT / agent_directory / "skills/wordpress-redesign-system"
            self.assertEqual(canonical, skill_files(synchronized))

    def test_project_rules_require_the_redesign_route(self) -> None:
        routing = (
            (ROOT / "AGENTS.md").read_text(encoding="utf-8")
            + (SKILLS / "hs-manacost-project/SKILL.md").read_text(encoding="utf-8")
        )
        self.assertIn("wordpress-redesign-system", routing)
        self.assertIn("2–3", routing)
        self.assertIn("design contract", routing.lower())


if __name__ == "__main__":
    unittest.main()
