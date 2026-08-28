import importlib.util
import json
import sys
import unittest
from pathlib import Path
from typing import Protocol, cast

ROOT = Path(__file__).resolve().parents[1]
SKILLS = ROOT / ".agents/skills"
RESPONSIVE = SKILLS / "wordpress-responsive-experience"
TYPOGRAPHY = SKILLS / "wordpress-typography-layout-system"
sys.dont_write_bytecode = True


class ValidatorModule(Protocol):
    def validate(self, contract: object) -> list[str]: ...


def load_script(name: str, path: Path) -> ValidatorModule:
    spec = importlib.util.spec_from_file_location(name, path)
    if spec is None or spec.loader is None:
        raise RuntimeError(f"Cannot load {path}")
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return cast(ValidatorModule, cast(object, module))


def skill_files(path: Path) -> dict[Path, bytes]:
    return {
        item.relative_to(path): item.read_bytes()
        for item in path.rglob("*")
        if item.is_file() and "__pycache__" not in item.parts and item.suffix != ".pyc"
    }


class ResponsiveTypographySkillTests(unittest.TestCase):
    def test_responsive_skill_is_complete_and_project_specific(self) -> None:
        required = (
            "SKILL.md",
            "agents/openai.yaml",
            "references/parity-contract.md",
            "references/responsive-patterns.md",
            "references/newspaper-mobile-surfaces.md",
            "references/content-test-cases.md",
            "references/acceptance-matrix.md",
            "scripts/validate_responsive_contract.py",
        )
        for relative_path in required:
            content = (RESPONSIVE / relative_path).read_text(encoding="utf-8")
            self.assertTrue(content.strip(), relative_path)
            self.assertNotIn("TODO", content)

        content = "\n".join(
            path.read_text(encoding="utf-8") for path in RESPONSIVE.rglob("*.md")
        ).lower()
        for marker in (
            "functional parity",
            "newspaper",
            "320",
            "1440",
            "200% zoom",
            "long cyrillic",
            "white gutter",
            "touch",
            "hs-manacost.com",
            "noindex",
        ):
            self.assertIn(marker, content)

    def test_responsive_contract_accepts_project_baseline(self) -> None:
        validator = load_script(
            "validate_responsive_contract",
            RESPONSIVE / "scripts/validate_responsive_contract.py",
        )
        contract = cast(
            dict[str, object],
            json.loads(
                (ROOT / "config/responsive-experience-contract.json").read_text(
                    encoding="utf-8"
                )
            ),
        )
        self.assertEqual([], validator.validate(contract))

    def test_responsive_contract_rejects_hidden_features_and_unsafe_zoom(self) -> None:
        validator = load_script(
            "validate_responsive_contract_unsafe",
            RESPONSIVE / "scripts/validate_responsive_contract.py",
        )
        contract = cast(
            dict[str, object],
            json.loads(
                (ROOT / "config/responsive-experience-contract.json").read_text(
                    encoding="utf-8"
                )
            ),
        )
        cast(dict[str, object], contract["parity"])["allow_content_removal"] = True
        cast(dict[str, object], contract["responsive"])["zoom_percent"] = 100
        cast(dict[str, object], contract["implementation"])["ownership_layers"] = [
            "parent-theme"
        ]
        errors = validator.validate(contract)
        self.assertTrue(any("allow_content_removal" in error for error in errors))
        self.assertTrue(any("zoom_percent" in error for error in errors))
        self.assertTrue(any("unsupported layer" in error for error in errors))

    def test_typography_skill_is_complete_and_handles_runtime_font_override(
        self,
    ) -> None:
        required = (
            "SKILL.md",
            "agents/openai.yaml",
            "references/typography-roles.md",
            "references/font-loading.md",
            "references/cyrillic-quality.md",
            "references/container-grid-system.md",
            "references/spacing-vertical-rhythm.md",
            "references/newspaper-ownership.md",
            "references/acceptance-matrix.md",
            "scripts/validate_visual_system.py",
        )
        for relative_path in required:
            content = (TYPOGRAPHY / relative_path).read_text(encoding="utf-8")
            self.assertTrue(content.strip(), relative_path)
            self.assertNotIn("TODO", content)

        content = "\n".join(
            path.read_text(encoding="utf-8") for path in TYPOGRAPHY.rglob("*.md")
        ).lower()
        for marker in (
            "manacost-font-trim",
            "cyrillic",
            "font-display",
            "1068",
            "740",
            "visual rhythm",
            "article measure",
            "spacing scale",
            "cls",
        ):
            self.assertIn(marker, content)

    def test_visual_system_accepts_baseline_and_rejects_design_drift(self) -> None:
        validator = load_script(
            "validate_visual_system",
            TYPOGRAPHY / "scripts/validate_visual_system.py",
        )
        contract = cast(
            dict[str, object],
            json.loads(
                (ROOT / "config/typography-layout-contract.json").read_text(
                    encoding="utf-8"
                )
            ),
        )
        self.assertEqual([], validator.validate(contract))

        cast(dict[str, object], contract["typography"])["cyrillic_required"] = False
        cast(dict[str, object], contract["spacing"])["scale"] = [4, 8, 13, 19]
        cast(dict[str, object], contract["runtime"])["manacost_font_trim_reviewed"] = (
            False
        )
        cast(dict[str, object], contract["implementation"])["ownership_layers"] = [
            "generated-css"
        ]
        errors = validator.validate(contract)
        self.assertTrue(any("cyrillic_required" in error for error in errors))
        self.assertTrue(any("spacing.scale" in error for error in errors))
        self.assertTrue(any("manacost_font_trim_reviewed" in error for error in errors))
        self.assertTrue(any("unsupported layer" in error for error in errors))

    def test_contract_validators_report_malformed_input_without_crashing(self) -> None:
        validators = (
            load_script(
                "validate_responsive_contract_malformed",
                RESPONSIVE / "scripts/validate_responsive_contract.py",
            ),
            load_script(
                "validate_visual_system_malformed",
                TYPOGRAPHY / "scripts/validate_visual_system.py",
            ),
        )
        for validator in validators:
            self.assertGreater(len(validator.validate([])), 0)
            self.assertGreater(len(validator.validate({"schema_version": 1})), 0)

    def test_skills_are_registered_routed_and_synchronized(self) -> None:
        registry = cast(
            dict[str, object],
            json.loads((ROOT / "config/ai-skills.json").read_text(encoding="utf-8")),
        )
        project_local = cast(list[dict[str, object]], registry["project_local"])
        registered = {cast(str, item["name"]): item for item in project_local}
        task_routes = cast(dict[str, list[str]], registry["task_routes"])
        routes = {
            "wordpress-responsive-experience": "responsive_frontend",
            "wordpress-typography-layout-system": "typography_layout",
        }
        for name, route in routes.items():
            self.assertEqual("project", registered[name]["source"])
            self.assertIn(name, task_routes[route])
            canonical = skill_files(SKILLS / name)
            for agent_directory in (".codex", ".claude"):
                synchronized = ROOT / agent_directory / "skills" / name
                self.assertEqual(canonical, skill_files(synchronized))

        redesign = task_routes["wordpress_redesign"]
        self.assertIn("wordpress-responsive-experience", redesign)
        self.assertIn("wordpress-typography-layout-system", redesign)

    def test_project_rules_and_visual_suite_enforce_both_systems(self) -> None:
        routing = (ROOT / "AGENTS.md").read_text(encoding="utf-8") + (
            SKILLS / "hs-manacost-project/SKILL.md"
        ).read_text(encoding="utf-8")
        self.assertIn("wordpress-responsive-experience", routing)
        self.assertIn("wordpress-typography-layout-system", routing)

        visual_spec = (ROOT / "tests/visual/wordpress.spec.ts").read_text(
            encoding="utf-8"
        )
        self.assertIn("RESPONSIVE_WIDTHS", visual_spec)
        for width in (320, 390, 768, 1024, 1440):
            self.assertIn(str(width), visual_spec)
        self.assertIn("responsive overflow sweep", visual_spec)


if __name__ == "__main__":
    _ = unittest.main()
