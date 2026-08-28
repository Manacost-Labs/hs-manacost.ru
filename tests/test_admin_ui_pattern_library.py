import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/hs-admin-ui-patterns.php"
ASSETS = ROOT / "wordpress/mu-plugins/hs-admin-ui-patterns"


class AdminUiPatternLibraryTests(unittest.TestCase):
    def test_pattern_library_is_staging_only_and_screen_scoped(self) -> None:
        source = PLUGIN.read_text(encoding="utf-8") + (
            ASSETS / "class-hs-admin-ui-patterns.php"
        ).read_text(encoding="utf-8")
        self.assertIn("wp_get_environment_type", source)
        self.assertIn("current_user_can", source)
        self.assertIn("manage_options", source)
        self.assertIn("admin_enqueue_scripts", source)
        self.assertIn("$hook_suffix", source)
        self.assertNotIn("wp_enqueue_scripts", source)

        css = (ASSETS / "patterns.css").read_text(encoding="utf-8")
        javascript = (ASSETS / "patterns.js").read_text(encoding="utf-8")
        self.assertIn(".hs-ui-patterns", css)
        self.assertIn("--hs-ui-space-", css)
        self.assertIn("prefers-reduced-motion", css)
        self.assertNotIn("innerHTML", javascript)
        self.assertIn("showModal", javascript)
        self.assertIn("aria-live", source)

    def test_pattern_contract_is_documented_and_used_by_admin_skill(self) -> None:
        documentation = (ROOT / "docs/admin-ui-pattern-library.md").read_text(
            encoding="utf-8"
        )
        for requirement in (
            "Design contract",
            "loading",
            "empty",
            "error",
            "permission",
            "320",
            "1440",
            "пагинац",
        ):
            self.assertIn(requirement, documentation)

        admin_skill = (ROOT / ".agents/skills/wordpress-admin-ui/SKILL.md").read_text(
            encoding="utf-8"
        )
        self.assertIn("docs/admin-ui-pattern-library.md", admin_skill)
        self.assertIn("hs-admin-ui-patterns", admin_skill)

    def test_visual_suite_covers_pattern_page_and_dialog(self) -> None:
        visual_spec = (ROOT / "tests/visual/wordpress.spec.ts").read_text(
            encoding="utf-8"
        )
        self.assertIn("admin UI pattern library", visual_spec)
        self.assertIn("hs-ui-open-dialog", visual_spec)
        self.assertIn("toHaveScreenshot('admin-ui-patterns.png')", visual_spec)


if __name__ == "__main__":
    _ = unittest.main()
