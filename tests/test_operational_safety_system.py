import json
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]


class OperationalSafetySystemTests(unittest.TestCase):
    def test_wordpress_contract_inventory_is_current(self) -> None:
        inventory = json.loads(
            (ROOT / "config/wordpress-contracts.json").read_text(encoding="utf-8")
        )
        self.assertEqual(1, inventory["schema_version"])
        for contract_type in (
            "post_meta",
            "options",
            "shortcodes",
            "ajax_actions",
            "rest_routes",
            "cron_hooks",
            "capabilities",
        ):
            self.assertIn(contract_type, inventory["contracts"])

        subprocess.run(
            ["python3", "ops/contracts/scan-wordpress-contracts.py", "--check"],
            cwd=ROOT,
            check=True,
        )

    def test_integration_stack_covers_critical_wordpress_behaviour(self) -> None:
        compose = (ROOT / "ops/integration/compose.yml").read_text(encoding="utf-8")
        suite = (ROOT / "ops/integration/wordpress-tests.php").read_text(encoding="utf-8")
        workflow = (ROOT / ".github/workflows/quality.yml").read_text(
            encoding="utf-8"
        )

        for service in ("database:", "wordpress:", "cli:"):
            self.assertIn(service, compose)
        self.assertIn("mariadb:10.11", compose)
        self.assertIn("wordpress:php8.2-apache", compose)
        self.assertIn("6.9.7", (ROOT / "ops/integration/start.sh").read_text(encoding="utf-8"))

        for behaviour in (
            "wp_create_post_autosave",
            "wp_get_post_revisions",
            "media_handle_sideload",
            "duplicate-image.png",
            "hs_manacost_s3_restore_file",
            "hs_deck_link",
            "spoiler",
            "post_views_count",
            "Manacost_Domain_Mirror",
            "manacost_cache_purge_last_results",
        ):
            self.assertIn(behaviour, suite)

        self.assertIn("ops/integration/run.sh", workflow)
        self.assertIn("timeout-minutes:", workflow)

    def test_visual_regression_has_required_pages_and_viewports(self) -> None:
        config = (ROOT / "playwright.config.ts").read_text(encoding="utf-8")
        suite = (ROOT / "tests/visual/wordpress.spec.ts").read_text(encoding="utf-8")

        self.assertIn("390", config)
        self.assertIn("1440", config)
        for page in ("home", "article", "category", "editor", "admin"):
            self.assertIn(page, suite)
        self.assertIn("toHaveScreenshot", suite)
        self.assertTrue((ROOT / "tests/visual/screenshot.css").is_file())

    def test_backup_policy_requires_independent_s3_destination_and_restore_drill(self) -> None:
        policy = json.loads((ROOT / "config/backup-policy.json").read_text(encoding="utf-8"))
        self.assertGreaterEqual(policy["database"]["retention"]["daily"], 14)
        self.assertGreaterEqual(policy["database"]["retention"]["monthly"], 6)
        self.assertEqual("monthly", policy["restore_drill"]["schedule"])
        self.assertTrue(policy["object_storage"]["independent_destination_required"])

        snapshot = (ROOT / "ops/backup/s3-snapshot.sh").read_text(encoding="utf-8")
        restore = (ROOT / "ops/backup/restore-drill.sh").read_text(encoding="utf-8")
        self.assertIn("HS_S3_BACKUP_REMOTE", snapshot)
        self.assertIn("--backup-dir", snapshot)
        self.assertIn("mariadb:10.11", restore)
        self.assertIn("mktemp -d", restore)
        self.assertIn("trap", restore)
        self.assertNotIn("wp db import", restore)

    def test_plugin_updates_are_report_only_and_commercial_plugins_stay_manual(self) -> None:
        policy = json.loads(
            (ROOT / "config/plugin-update-policy.json").read_text(encoding="utf-8")
        )
        self.assertEqual(["commercial", "tagdiv"], policy["manual_only_origins"])
        self.assertEqual(1, policy["maximum_plugins_per_change"])
        self.assertFalse(policy["production_auto_update"])

        workflow = (ROOT / ".github/workflows/plugin-audit.yml").read_text(
            encoding="utf-8"
        )
        self.assertIn("schedule:", workflow)
        self.assertIn("permissions:\n  contents: read", workflow)
        for mutation in ("plugin update", "deploy", "promote-production"):
            self.assertNotIn(mutation, workflow.lower())


if __name__ == "__main__":
    unittest.main()
