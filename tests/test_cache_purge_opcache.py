from __future__ import annotations

import json
import shutil
import subprocess
import tempfile
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/manacost-cache-purge.php"
FIXTURE = ROOT / "tests/fixtures/cache-purge-opcache.php"
DEPLOY_SCRIPT = ROOT / "ops/ci/hs-manacost-ci-deploy"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"

EXPECTED_NON_OPCACHE_STEPS = {
    "WP Rocket",
    "W3 Total Cache",
    "Autoptimize",
    "Perfmatters",
    "Known local cache folders",
    "WordPress object cache",
    "Reverse proxy cache",
    "Cloudflare",
}


class CachePurgeOpcacheTest(unittest.TestCase):
    def run_scenario(self, scenario: str) -> dict:
        with tempfile.TemporaryDirectory() as temporary_directory:
            completed = subprocess.run(
                [
                    PHP_BINARY,
                    "-d",
                    "disable_functions=opcache_reset",
                    str(FIXTURE),
                    str(PLUGIN),
                    scenario,
                    temporary_directory,
                ],
                check=False,
                capture_output=True,
                text=True,
            )
        self.assertEqual(completed.returncode, 0, completed.stderr)
        return json.loads(completed.stdout)

    def assert_full_purge_steps_remain(self, result: dict) -> None:
        self.assertEqual(result["object_cache"], 0)
        self.assertEqual(result["rocket"], ["minify", "cache-busting", "used-css"])
        self.assertEqual(result["actions"], ["perfmatters_clear_cache", "perfmatters_clear_used_css"])
        self.assertEqual(len(result["remote_posts"]), 2)
        self.assertFalse(result["local_cache_marker_exists"])
        self.assertTrue(EXPECTED_NON_OPCACHE_STEPS.issubset(result["result_names"]))
        self.assertEqual(result["failed"], 0)

    def test_purges_keep_tls_verification_and_target_only_the_canonical_home_url(self) -> None:
        result = self.run_scenario("manual")
        self.assertTrue(all(request.get("sslverify") is not False for request in result["remote_post_args"]))
        cloudflare_request = next(
            request
            for url, request in zip(result["remote_posts"], result["remote_post_args"])
            if "cloudflare.com" in url
        )
        self.assertEqual(json.loads(cloudflare_request["body"]), {"files": ["https://example.test/"]})

    def test_automatic_post_lifecycle_purges_skip_opcache_but_keep_other_purge_steps(self) -> None:
        for source in (
            "content_post",
            "updated_post",
            "status_post_publish_to_draft",
            "after_update_post",
            "after_publish_post",
            "delete_post",
            "status_post",
            "content_page",
            "content_custom",
        ):
            with self.subTest(source=source):
                result = self.run_scenario(source)
                self.assertEqual(result["opcache"], 0)
                self.assertNotIn("PHP OPcache", result["result_names"])
                self.assert_full_purge_steps_remain(result)

    def test_non_post_automatic_sources_and_scheduled_and_manual_purges_keep_opcache_reset(self) -> None:
        for source in ("async_ci_deploy", "async_unknown", "async_default", "scheduled", "manual"):
            with self.subTest(source=source):
                result = self.run_scenario(source)
                self.assertEqual(result["opcache"], 1)
                self.assertIn("PHP OPcache", result["result_names"])
                self.assert_full_purge_steps_remain(result)

    def test_deployment_purge_reports_its_own_results_without_reading_shared_option_state(self) -> None:
        result = self.run_scenario("async_ci_deploy")
        deploy_script = DEPLOY_SCRIPT.read_text(encoding="utf-8")

        self.assertEqual(result["direct_failed"], 0)
        self.assertNotIn("option pluck manacost_cache_purge_last_results", deploy_script)

    def test_deployment_purge_returns_a_failure_to_its_caller(self) -> None:
        result = self.run_scenario("async_ci_deploy_reverse_failure")

        self.assertEqual(result["direct_failed"], 1)

    def test_automatic_post_purge_records_a_reverse_proxy_failure_and_runs_later_steps(self) -> None:
        result = self.run_scenario("content_post_reverse_failure")
        self.assertEqual(result["opcache"], 0)
        self.assertEqual(result["failed"], 1)
        self.assertEqual(len(result["remote_posts"]), 2)
        self.assertTrue(result["remote_posts"][-1].startswith("https://api.cloudflare.com/"))
        self.assertIn("Cloudflare", result["result_names"])


if __name__ == "__main__":
    unittest.main()
