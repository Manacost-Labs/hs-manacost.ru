import os
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SCRIPT = ROOT / "ops/performance/diagnose-media-upload.mjs"


class StagingMediaDiagnosticTests(unittest.TestCase):
    def test_email_prompt_is_postponed_only_on_staging(self):
        result = subprocess.run(
            ["node", "--input-type=module", "-e", r'''
import assert from 'node:assert/strict';
import { finishAdminLogin } from './ops/performance/browser-admin-login.mjs';
function fakePage(origin, prompt) {
  let url = origin + (prompt ? '/wp-login.php?action=confirm_admin_email' : '/wp-admin/');
  const clicks = [];
  return {
    clicks, url: () => url,
    waitForURL: async predicate => assert.ok(predicate(new URL(url))),
    locator: selector => ({ click: async () => {
      clicks.push(selector); url = origin + '/wp-admin/';
    } }),
  };
}
const staging = fakePage('https://test.hs-manacost.ru', true);
await finishAdminLogin(staging, 'https://test.hs-manacost.ru');
assert.deepEqual(staging.clicks, ['a[href*="remind_me_later="]']);
const ordinary = fakePage('http://127.0.0.1:8888', false);
await finishAdminLogin(ordinary, 'http://127.0.0.1:8888');
assert.deepEqual(ordinary.clicks, []);
const production = fakePage('https://hs-manacost.ru', true);
await assert.rejects(finishAdminLogin(production, 'https://hs-manacost.ru'), /staging/);
assert.deepEqual(production.clicks, []);
'''], cwd=ROOT, capture_output=True, text=True, timeout=10,
        )
        self.assertEqual(0, result.returncode, result.stderr)

    def run_probe(self, url, credentials):
        return subprocess.run(
            ["node", str(SCRIPT)],
            cwd=ROOT,
            env={**os.environ, "WP_TEST_BASE_URL": url, **credentials},
            capture_output=True,
            text=True,
            timeout=10,
        )

    def test_refuses_production_and_lookalike_hosts_before_login(self):
        credentials = {
            key: "private-probe-value-" + str(index)
            for index, key in enumerate(
                ("WP_TEST_ADMIN_USER", "WP_TEST_ADMIN_PASSWORD",
                 "STAGING_HTTP_USER", "STAGING_HTTP_PASSWORD")
            )
        }
        for url in (
            "https://hs-manacost.ru", "https://kolodahearthstone.com",
            "http://test.hs-manacost.ru", "https://test.hs-manacost.ru.example.org",
            "https://user:private-url-value@test.hs-manacost.ru",
        ):
            with self.subTest(url=url):
                result = self.run_probe(url, credentials)
                output = result.stdout + result.stderr
                self.assertNotEqual(0, result.returncode)
                self.assertIn("Only HTTPS staging is allowed", output)
                for secret in (*credentials.values(), "private-url-value"):
                    self.assertNotIn(secret, output)

    def test_requires_all_credentials_before_opening_a_browser(self):
        result = self.run_probe("https://test.hs-manacost.ru", {
            key: "" for key in ("WP_TEST_ADMIN_USER", "WP_TEST_ADMIN_PASSWORD",
                               "STAGING_HTTP_USER", "STAGING_HTTP_PASSWORD")
        })
        self.assertNotEqual(0, result.returncode)
        self.assertIn("All four staging credentials are required", result.stderr)


if __name__ == "__main__":
    unittest.main()
