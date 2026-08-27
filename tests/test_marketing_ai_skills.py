import importlib.util
import json
import sys
import unittest
from pathlib import Path
from unittest.mock import patch


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


class MarketingAISkillTests(unittest.TestCase):
    CUSTOM_SKILLS = {
        "wordpress-seo-editorial": (
            "agents/openai.yaml",
            "references/host-policy.md",
            "references/editorial-checklist.md",
            "scripts/audit_seo_hosts.py",
        ),
        "wordpress-ads-analytics": (
            "agents/openai.yaml",
            "references/measurement-contracts.md",
            "references/delivery-matrix.md",
            "scripts/audit_ads_analytics.py",
        ),
    }

    EXTERNAL_SKILLS = {
        "cloudflare": ("cloudflare/skills", "f96bff754e428838818017f75817f0f9428acd48"),
        "web-perf": ("cloudflare/skills", "f96bff754e428838818017f75817f0f9428acd48"),
        "workers-best-practices": ("cloudflare/skills", "f96bff754e428838818017f75817f0f9428acd48"),
        "wrangler": ("cloudflare/skills", "f96bff754e428838818017f75817f0f9428acd48"),
        "turnstile-spin": ("cloudflare/skills", "f96bff754e428838818017f75817f0f9428acd48"),
        "playwright": ("magnus919/agent-skills", "531ff6753784823c878c92b988c6e55266ce09a9"),
        "update-to-wordpress-6-9": ("gaambo/wp-upgrade-skills", "21146338c82e626af31abbcc3b49d339c58df90d"),
        "wordpress-audit-handoff": ("courtneyr-dev/wp-release-audit-method", "3d9a3cee8ae244ee5ac00543359a3bc585f34bf3"),
    }

    def test_custom_skills_are_complete(self) -> None:
        for name, resources in self.CUSTOM_SKILLS.items():
            for relative_path in ("SKILL.md", *resources):
                content = (SKILLS / name / relative_path).read_text(encoding="utf-8")
                self.assertTrue(content.strip(), f"{name}/{relative_path}")
                self.assertNotIn("TODO", content, f"{name}/{relative_path}")

    def test_seo_skill_preserves_host_contract(self) -> None:
        content = "\n".join(
            path.read_text(encoding="utf-8")
            for path in (SKILLS / "wordpress-seo-editorial").rglob("*.md")
        ).lower()
        for marker in (
            "hs-manacost.ru",
            "hs-manacost.com",
            "test.hs-manacost.ru",
            "noindex, follow",
            "canonical",
            "schema",
            "sitemap",
            "internal links",
            "image",
        ):
            self.assertIn(marker, content)

    def test_ads_skill_models_banner_analytics_and_views(self) -> None:
        content = "\n".join(
            path.read_text(encoding="utf-8")
            for path in (SKILLS / "wordpress-ads-analytics").rglob("*.md")
        ).lower()
        for marker in (
            "playerok",
            "plausible",
            "post_views_count",
            "one tracker",
            "disposable staging",
            "moscow",
            "novosibirsk",
            "heartstone_sajt.png.webp",
            "728x90.jpg",
        ):
            self.assertIn(marker, content)

    def test_passive_auditors_enforce_core_contracts(self) -> None:
        seo = load_script(
            "audit_seo_hosts",
            SKILLS / "wordpress-seo-editorial/scripts/audit_seo_hosts.py",
        )
        seo_html = """
            <html><head><title>Article</title>
            <link rel="canonical" href="https://hs-manacost.ru/article/">
            <script type="application/ld+json">{"@type":"Article"}</script>
            </head><body><img src="cover.jpg" alt="Cover"></body></html>
        """
        with patch.object(
            seo,
            "fetch",
            return_value=(
                200,
                "https://hs-manacost.com/article/",
                {"X-Robots-Tag": "noindex, follow", "X-Manacost-Mirror": "active"},
                seo_html,
            ),
        ):
            self.assertEqual([], seo.audit("mirror", "https://hs-manacost.com/article/", 1)["errors"])

        ads = load_script(
            "audit_ads_analytics",
            SKILLS / "wordpress-ads-analytics/scripts/audit_ads_analytics.py",
        )
        ads_html = """
            <script src="/mca/script.js" data-domain="hs-manacost.ru"></script>
            <a href="https://plrk.co/p/example"><img src="/wp-content/uploads/2026/07/728x90.jpg"></a>
        """
        with patch.object(
            ads,
            "fetch",
            return_value=(200, "https://hs-manacost.ru/", {}, ads_html),
        ):
            result = ads.audit(
                "primary",
                "https://hs-manacost.ru/",
                "/wp-content/uploads/2026/07/728x90.jpg",
                "/wp-content/uploads/2026/03/heartstone_sajt.png.webp",
                1,
            )
            self.assertEqual([], result["errors"])
            self.assertEqual(1, result["tracker_count"])

    def test_external_skills_are_safely_adapted_for_this_project(self) -> None:
        playwright = "\n".join(
            path.read_text(encoding="utf-8")
            for path in (SKILLS / "playwright").rglob("*")
            if path.is_file() and path.suffix in {".md", ".json"}
        ).lower()
        self.assertNotIn("flaresolverr", playwright)
        self.assertIn("do not bypass", playwright)

        handoff = (SKILLS / "wordpress-audit-handoff/SKILL.md").read_text(
            encoding="utf-8"
        )
        self.assertNotIn("../", handoff)
        self.assertIn("test.hs-manacost.ru", handoff)
        self.assertIn("READY WITH CONDITIONS", handoff)

    def test_skills_are_registered_pinned_routed_and_synchronized(self) -> None:
        registry = json.loads((ROOT / "config/ai-skills.json").read_text(encoding="utf-8"))
        registered = {item["name"]: item for item in registry["project_local"]}

        for name in self.CUSTOM_SKILLS:
            self.assertEqual("project", registered[name]["source"])
        for name, (source, commit) in self.EXTERNAL_SKILLS.items():
            self.assertEqual(source, registered[name]["source"])
            self.assertEqual(commit, registered[name]["source_commit"])
        self.assertIn("adaptation", registered["playwright"])
        self.assertIn("adaptation", registered["wordpress-audit-handoff"])

        self.assertIn("wordpress-seo-editorial", registry["task_routes"]["seo"])
        self.assertIn("wordpress-ads-analytics", registry["task_routes"]["ads_analytics"])
        self.assertIn("cloudflare", registry["task_routes"]["cloudflare"])
        self.assertIn("playwright", registry["task_routes"]["ads_analytics"])

        for name in (*self.CUSTOM_SKILLS, *self.EXTERNAL_SKILLS):
            canonical = skill_files(SKILLS / name)
            for agent_directory in (".codex", ".claude"):
                synchronized = ROOT / agent_directory / "skills" / name
                copy = skill_files(synchronized)
                self.assertEqual(canonical, copy)


if __name__ == "__main__":
    unittest.main()
