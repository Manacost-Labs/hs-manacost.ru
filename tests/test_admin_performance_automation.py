import json
import subprocess
import tempfile
import textwrap
import unittest
from pathlib import Path
from typing import cast


ROOT = Path(__file__).resolve().parents[1]
BUILDER = ROOT / "ops/performance/build-admin-performance-report.py"


def raw_evidence(*, ttfb_ms: float = 620.0) -> dict[str, object]:
    sample = {
        "ttfb_ms": ttfb_ms,
        "interactive_ms": 1200.0,
        "sql_queries": 72.0,
        "peak_memory_mb": 64.0,
        "long_tasks": 1.0,
    }
    return {
        "schema_version": 1,
        "environment": "integration",
        "screen": "dashboard",
        "authenticated_role": "administrator",
        "dataset_size": 100,
        "dataset_kind": "published_posts",
        "cache_state": "warm",
        "viewport": "desktop-1440",
        "samples": [dict(sample) for _ in range(5)],
        "functional_checks": {
            "behavior": True,
            "permissions": True,
            "desktop": True,
            "mobile": True,
            "error_path": True,
        },
    }


def budgets() -> dict[str, object]:
    return {
        "schema_version": 1,
        "screens": {
            "dashboard": {
                "ttfb_ms": {"baseline": 800, "budget": 1000, "unit": "ms"},
                "interactive_ms": {
                    "baseline": 1600,
                    "budget": 2000,
                    "unit": "ms",
                },
                "sql_queries": {
                    "baseline": 80,
                    "budget": 100,
                    "unit": "count",
                },
                "peak_memory_mb": {
                    "baseline": 80,
                    "budget": 128,
                    "unit": "MB",
                },
                "long_tasks": {
                    "baseline": 2,
                    "budget": 3,
                    "unit": "count",
                },
            }
        },
    }


class AdminPerformanceAutomationTests(unittest.TestCase):
    def test_navigation_recovery_is_bounded_and_specific(self) -> None:
        result = subprocess.run(["node", "--input-type=module", "-e", textwrap.dedent("""
            import assert from 'node:assert/strict';
            import { navigateWithNetworkRecovery } from './ops/performance/browser-fixture-request.mjs';
            const response = { status: () => 200 };
            function page(errors) {
                return { calls: 0, waits: [], async goto(url, options) {
                    this.calls++;
                    assert.equal(url, 'https://test.hs-manacost.ru/');
                    assert.equal(options.timeout, 30000);
                    const error = errors.shift(); if (error) throw error; return response;
                }, async waitForTimeout(ms) { this.waits.push(ms); } };
            }
            const network = () => new Error('page.goto: net::ERR_NETWORK_CHANGED');
            const recovered = page([network(), network()]);
            assert.deepEqual(await navigateWithNetworkRecovery(recovered, 'https://test.hs-manacost.ru/', {timeout:30000}), {response, retries:2});
            assert.deepEqual(recovered.waits, [1000, 2000]);
            const exhausted = page([network(), network(), network()]);
            await assert.rejects(navigateWithNetworkRecovery(exhausted, 'https://test.hs-manacost.ru/', {timeout:30000}), /ERR_NETWORK_CHANGED/);
            assert.equal(exhausted.calls, 3);
            for (const message of ['Timeout exceeded', 'net::ERR_CERT_AUTHORITY_INVALID', 'HTTP 403']) {
                const rejected = page([new Error(message)]);
                await assert.rejects(navigateWithNetworkRecovery(rejected, 'https://test.hs-manacost.ru/', {timeout:30000}));
                assert.equal(rejected.calls, 1); assert.equal(rejected.waits.length, 0);
            }
        """)], cwd=ROOT, capture_output=True, text=True, timeout=15)
        self.assertEqual(0, result.returncode, result.stdout + result.stderr)

    def test_diagnostic_error_labels_do_not_export_private_messages(self) -> None:
        script = """
import assert from 'node:assert/strict';
import { classifyDiagnosticFailure } from './ops/performance/browser-fixture-request.mjs';
for (const [message, expected] of [
  ['net::ERR_NETWORK_CHANGED https://private-token.example/', 'network-change'],
  ['Execution context was destroyed private-token', 'navigation-context-lost'],
  ['interrupted by another navigation private-token', 'navigation-interrupted'],
  ['Editor performance probe is unavailable private-token', 'probe-unavailable'],
  ['Article editor returned HTTP 500 private-token', 'http-error'],
  ['Saved title did not round-trip private-token', 'roundtrip-mismatch'],
  ['private-token', 'other'],
]) {
  assert.equal(classifyDiagnosticFailure(new Error(message)), expected);
}
assert.equal(classifyDiagnosticFailure({ name:'TimeoutError',message:'private-token' }), 'timeout');
assert.equal(classifyDiagnosticFailure(null), 'other');
console.log('PASS');
"""
        result = subprocess.run(
            ["node", "--input-type=module", "-e", script], cwd=ROOT,
            capture_output=True, text=True, check=False,
        )
        self.assertEqual(0, result.returncode, result.stderr)

    def test_fixture_transport_preserves_browser_routing_on_a_separate_document(self) -> None:
        script = """
import assert from 'node:assert/strict';
import { createBrowserFixtureRequest } from './ops/performance/browser-fixture-request.mjs';
const calls = [];
const page = {
  goto: async (url, options) => {
    calls.push(['goto', url, options]);
    return { ok: () => true, text: async () => 'abcdef1234' };
  },
  evaluate: async (fn, args) => {
    calls.push(['evaluate', args]);
    return { status: 404, body: '{"code":"missing"}' };
  },
};
const base = 'https://test.hs-manacost.ru';
const request = await createBrowserFixtureRequest(page, base);
assert.equal(await (await request.fetch(base + '/wp-admin/admin-ajax.php?action=rest-nonce', {method:'GET'})).text(), 'abcdef1234');
const response = await request.fetch(base + '/wp-json/wp/v2/posts/123?context=edit', {method:'GET',headers:{'X-WP-Nonce':'abcdef1234'},timeout:30000});
assert.equal(response.status(), 404);
assert.deepEqual(await response.json(), {code:'missing'});
assert.equal(calls.filter(c => c[0] === 'goto').length, 1);
assert.equal(calls.filter(c => c[0] === 'evaluate').length, 1);
assert.equal(calls[0][1], base + '/wp-admin/admin-ajax.php?action=rest-nonce');
assert.equal(calls[1][1].url, base + '/wp-json/wp/v2/posts/123?context=edit');
console.log('PASS');
"""
        result = subprocess.run(
            ["node", "--input-type=module", "-e", script], cwd=ROOT,
            capture_output=True, text=True, check=False,
        )
        self.assertEqual(0, result.returncode, result.stderr)

    def test_article_cleanup_uses_request_context_and_refuses_foreign_records(self) -> None:
        script = r"""
import assert from 'node:assert/strict';
import { deleteArticleFixture } from './ops/performance/delete-article-fixture.mjs';
const title = 'Admin performance probe 1791929000000';
for (const scenario of ['owned', 'foreign-author', 'foreign-title', 'foreign-content', 'lost-delete', 'denied-delete', 'bad-nonce', 'failed-get', 'already-absent', 'no-route-404', 'html-404']) {
  const calls = [];
  let deleted = false;
  const request = {
    async fetch(url, options) {
      calls.push(options.method);
      if (scenario === 'failed-get') throw new Error('private-cookie');
      let status = 200;
      let data = {};
      if (url.includes('rest-nonce')) {
        return { ok: () => true, text: async () => scenario === 'bad-nonce' ? 'login-required' : 'abcdef1234' };
      }
      if (url.includes('users/me')) data = { id: 7 };
      else if (options.method === 'DELETE') {
        if (scenario === 'denied-delete') status = 403;
        else {
          deleted = true;
          if (scenario === 'lost-delete') throw new Error('private-cookie');
        }
      } else if (deleted || ['already-absent','no-route-404','html-404'].includes(scenario)) { status = 404; data = {code:scenario === 'no-route-404' ? 'rest_no_route' : 'rest_post_invalid_id'}; }
      else data = {
        author: scenario === 'foreign-author' ? 8 : 7,
        title: { raw: scenario === 'foreign-title' ? title + ' unrelated' : title + ' 4' },
        content: { raw: scenario === 'foreign-content' ? 'human content' : 'Synthetic staging performance content. '.repeat(1750) },
      };
      return { status: () => status, ok: () => status === 200, json: async () => { if (scenario === 'html-404') throw new SyntaxError('private-token'); return data; } };
    },
  };
  const result = await deleteArticleFixture(request, 'https://test.hs-manacost.ru', 123, title);
  assert.equal(result, ['owned', 'lost-delete', 'already-absent'].includes(scenario), scenario);
  if (scenario.startsWith('foreign-') || ['bad-nonce', 'failed-get', 'already-absent', 'no-route-404', 'html-404'].includes(scenario)) {
    assert.ok(!calls.includes('DELETE'), scenario);
  }
}
console.log('PASS');
"""
        result = subprocess.run(
            ["node", "--input-type=module", "-e", script], cwd=ROOT,
            capture_output=True, text=True, check=False,
        )
        self.assertEqual(0, result.returncode, result.stderr)

    def test_upload_cleanup_reports_status_without_private_response(self) -> None:
        script = """
import assert from 'node:assert/strict';
import { deleteUploadFixture } from './ops/performance/delete-upload-fixture.mjs';
for (const scenario of ['lost-delete', 'denied-delete', 'failed-verify']) {
  const calls = [];
  const rest = async (route, method = 'GET') => {
    calls.push(method);
    if (method === 'DELETE') {
      if (scenario === 'lost-delete') throw new Error('private-token');
      return { status: 403, data: { private: 'private-token' } };
    }
    if (scenario === 'failed-verify') throw new TypeError('private-token');
    return { status: scenario === 'lost-delete' ? 404 : 200 };
  };
  let caught;
  try { await deleteUploadFixture(rest, 123); } catch (error) { caught = error; }
  assert.deepEqual(calls, ['DELETE', 'GET']);
  if (scenario === 'lost-delete') assert.equal(caught, undefined);
  else {
    assert.equal(caught.name, 'UploadFixtureCleanupError');
    assert.equal(caught.deleteStatus, 403);
    assert.equal(caught.verifyStatus, scenario === 'failed-verify' ? null : 200);
    assert.ok(!caught.message.includes('private-token'));
    assert.ok(!JSON.stringify(caught).includes('private-token'));
  }
}
console.log('PASS');
"""
        result = subprocess.run(
            ["node", "--input-type=module", "-e", script], cwd=ROOT,
            capture_output=True, text=True, check=False,
        )
        self.assertEqual(0, result.returncode, result.stderr)

    def run_staging_preflight(
        self, credentials: dict[str, str]
    ) -> tuple[subprocess.CompletedProcess[str], str, str]:
        workflow = (ROOT / ".github/workflows/admin-performance-staging.yml").read_text(
            encoding="utf-8"
        )
        step = workflow.split(
            "      - name: Check protected staging measurement credentials\n", 1
        )[1].split("      - name:", 1)[0]
        script = textwrap.dedent(step.split("        run: |\n", 1)[1])
        with tempfile.TemporaryDirectory() as directory:
            output = Path(directory) / "output"
            summary = Path(directory) / "summary"
            result = subprocess.run(
                [
                    "/bin/bash", "--noprofile", "--norc",
                    "-e", "-o", "pipefail", "-c", script,
                ],
                cwd=ROOT,
                env={
                    "PATH": "/usr/bin:/bin",
                    "GITHUB_OUTPUT": str(output),
                    "GITHUB_STEP_SUMMARY": str(summary),
                    **credentials,
                },
                check=False,
                capture_output=True,
                text=True,
                timeout=5,
            )
            return (
                result,
                output.read_text(encoding="utf-8") if output.exists() else "",
                summary.read_text(encoding="utf-8") if summary.exists() else "",
            )

    def test_staging_missing_credentials_fail_without_exposing_values(self) -> None:
        names = (
            "WP_TEST_ADMIN_USER", "WP_TEST_ADMIN_PASSWORD",
            "STAGING_HTTP_USER", "STAGING_HTTP_PASSWORD",
        )
        complete = {
            name: f"synthetic-private-value-{index}"
            for index, name in enumerate(names)
        }
        for missing in (None, *names):
            with self.subTest(missing=missing):
                credentials = {} if missing is None else {**complete, missing: ""}
                result, output, summary = self.run_staging_preflight(credentials)
                self.assertNotEqual(0, result.returncode, "Missing access must not pass CI")
                self.assertIn("enabled=false", output)
                self.assertIn("BLOCKED", summary)
                combined = result.stdout + result.stderr + output + summary
                for value in complete.values():
                    self.assertNotIn(value, combined)

    def test_staging_complete_credentials_only_enable_measurement(self) -> None:
        credentials = {
            name: f"synthetic-private-value-{index}"
            for index, name in enumerate((
                "WP_TEST_ADMIN_USER", "WP_TEST_ADMIN_PASSWORD",
                "STAGING_HTTP_USER", "STAGING_HTTP_PASSWORD",
            ))
        }
        result, output, summary = self.run_staging_preflight(credentials)
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual("enabled=true\n", output)
        combined = result.stdout + result.stderr + output + summary
        self.assertNotIn("PASS", combined)
        for value in credentials.values():
            self.assertNotIn(value, combined)

    def test_staging_artifact_upload_requires_evidence(self) -> None:
        workflow = (ROOT / ".github/workflows/admin-performance-staging.yml").read_text(
            encoding="utf-8"
        )
        upload = workflow.split("      - name: Upload staging performance evidence", 1)[1]
        self.assertIn("if-no-files-found: error", upload)

    def test_staging_credentials_are_scoped_to_measurement_steps(self) -> None:
        workflow = (ROOT / ".github/workflows/admin-performance-staging.yml").read_text(
            encoding="utf-8"
        )
        job_config, steps = workflow.split("    steps:\n", 1)
        self.assertNotIn("secrets.", job_config)
        for step_name in (
            "Check protected staging measurement credentials",
            "Verify article cards against live staging catalogs",
            "Collect five comparable warm staging samples",
            "Diagnose authenticated article save",
            "Diagnose authenticated image uploads",
        ):
            step = steps.split(f"      - name: {step_name}\n", 1)[1].split(
                "      - ", 1
            )[0]
            for secret_name in (
                "STAGING_DIAGNOSTIC_USER", "STAGING_DIAGNOSTIC_PASSWORD",
                "STAGING_HTTP_USER", "STAGING_HTTP_PASSWORD",
            ):
                self.assertIn(f"secrets.{secret_name}", step)
        self.assertLess(
            steps.index("Check protected staging measurement credentials"),
            steps.index("Install locked browser package"),
        )
        self.assertIn("run: npm ci --ignore-scripts", steps)
        frontend_name = "Verify five cold slow-mobile frontend samples per route"
        if f"      - name: {frontend_name}\n" in steps:
            frontend = steps.split(f"      - name: {frontend_name}\n", 1)[1].split("      - ", 1)[0]
            for secret_name in ("STAGING_HTTP_USER", "STAGING_HTTP_PASSWORD"):
                self.assertIn(f"secrets.{secret_name}", frontend)
            self.assertNotIn("STAGING_DIAGNOSTIC", frontend)
            self.assertEqual(22, steps.count("secrets."))
        else:
            self.assertEqual(20, steps.count("secrets."))

    def test_report_builder_uses_medians_and_approved_budgets(self) -> None:
        with tempfile.TemporaryDirectory() as temporary_directory:
            temporary = Path(temporary_directory)
            raw_path = temporary / "raw.json"
            budget_path = temporary / "budgets.json"
            _ = raw_path.write_text(json.dumps(raw_evidence()), encoding="utf-8")
            _ = budget_path.write_text(json.dumps(budgets()), encoding="utf-8")

            result = subprocess.run(
                [str(BUILDER), str(raw_path), str(budget_path)],
                cwd=ROOT,
                check=False,
                capture_output=True,
                text=True,
            )

            self.assertEqual(0, result.returncode, result.stderr)
            report = cast(dict[str, object], json.loads(result.stdout))
            self.assertEqual(5, report["sample_count"])
            self.assertEqual("published_posts", report["dataset_kind"])
            metrics = cast(list[dict[str, object]], report["metrics"])
            ttfb = next(metric for metric in metrics if metric["name"] == "ttfb_ms")
            self.assertEqual(620.0, ttfb["after"])
            self.assertEqual(800.0, ttfb["before"])
            self.assertEqual(1000.0, ttfb["budget"])

    def test_report_builder_rejects_sensitive_or_incomparable_input(self) -> None:
        invalid_reports = (
            {**raw_evidence(), "samples": cast(list[object], raw_evidence()["samples"])[:4]},
            {**raw_evidence(), "cache_state": "mixed"},
            {**raw_evidence(), "dataset_kind": "configured_guess"},
            {**raw_evidence(), "session_token": "unsafe"},
        )
        with tempfile.TemporaryDirectory() as temporary_directory:
            temporary = Path(temporary_directory)
            budget_path = temporary / "budgets.json"
            _ = budget_path.write_text(json.dumps(budgets()), encoding="utf-8")
            for index, raw_report in enumerate(invalid_reports):
                raw_path = temporary / f"raw-{index}.json"
                _ = raw_path.write_text(json.dumps(raw_report), encoding="utf-8")
                result = subprocess.run(
                    [str(BUILDER), str(raw_path), str(budget_path)],
                    cwd=ROOT,
                    check=False,
                    capture_output=True,
                    text=True,
                )
                self.assertEqual(2, result.returncode, result.stdout)

    def test_browser_collector_and_probe_are_safely_wired(self) -> None:
        collector = ROOT / "ops/performance/collect-admin-performance.mjs"
        probe = ROOT / "wordpress/mu-plugins/hs-admin-performance-probe.php"
        self.assertTrue(collector.is_file())
        self.assertTrue(probe.is_file())

        collector_source = collector.read_text(encoding="utf-8")
        self.assertIn("WP_TEST_ADMIN_USER", collector_source)
        self.assertIn("WP_TEST_ADMIN_PASSWORD", collector_source)
        self.assertIn("PLAYWRIGHT_EXECUTABLE_PATH", collector_source)
        self.assertIn("PLAYWRIGHT_HOST_RESOLVER_RULES", collector_source)
        self.assertIn("async function warmUp", collector_source)
        self.assertIn("await warmUp(page, screen)", collector_source)
        self.assertIn("sampleCount", collector_source)
        self.assertNotIn("console.log(password", collector_source)
        # Host Docker churn aborts Chromium navigations with ERR_NETWORK_CHANGED;
        # every idempotent admin navigation must use the bounded shared retry.
        self.assertIn("navigateWithNetworkRecovery", collector_source)
        self.assertNotIn("page.goto(", collector_source)
        self.assertIn("navigation_retries", collector_source)

        probe_source = probe.read_text(encoding="utf-8")
        self.assertIn("wp_get_environment_type", probe_source)
        self.assertIn("current_user_can", probe_source)
        self.assertIn("manage_options", probe_source)

        integration_runner = (ROOT / "ops/integration/run.sh").read_text(
            encoding="utf-8"
        )
        self.assertIn("RUN_PERFORMANCE", integration_runner)
        self.assertIn("collect-admin-performance.mjs", integration_runner)

        staging_workflow = (
            ROOT / ".github/workflows/admin-performance-staging.yml"
        ).read_text(encoding="utf-8")
        self.assertIn("PLAYWRIGHT_EXECUTABLE_PATH", staging_workflow)
        self.assertIn("PLAYWRIGHT_HOST_RESOLVER_RULES", staging_workflow)
        self.assertIn("node ops/performance/collect-admin-performance.mjs", staging_workflow)
        self.assertNotIn("docker run", staging_workflow)

    def test_sql_probe_requires_explicit_privileged_staging_request(self) -> None:
        php = r"""
<?php
const ABSPATH = '/synthetic/';
const MB_IN_BYTES = 1048576;
function add_action(...$args) {}
function add_filter(...$args) {}
function remove_filter(...$args) {}
function remove_action(...$args) {}
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function wp_get_environment_type() { return getenv('TEST_ENV'); }
function is_admin() { return getenv('TEST_ADMIN') === '1'; }
function wp_doing_ajax() { return getenv('TEST_AJAX') === '1'; }
function current_user_can($capability) { return getenv('TEST_CAP') === '1'; }
function wp_unslash($value) { return $value; }
$_SERVER['REQUEST_METHOD'] = getenv('TEST_METHOD');
$_SERVER['HTTP_X_HS_ADMIN_SQL_PROFILE'] = getenv('TEST_OPTIN');
class wpdb { public $num_queries = 42; public $queries = null; }
class Alternative_DB extends wpdb {}
$wpdb = getenv('TEST_DROPIN') === '1' ? new Alternative_DB() : new wpdb();
$GLOBALS['pagenow'] = getenv('TEST_PAGE') ?: 'index.php';
if (getenv('TEST_SAVE_DEFINED') === '1') define('SAVEQUERIES', false);
require 'wordpress/mu-plugins/hs-admin-performance-probe.php';
hs_admin_performance_probe_enable_sql_profile();
echo json_encode(['enabled' => defined('SAVEQUERIES') && SAVEQUERIES]);
"""
        import os
        valid = {"TEST_ENV": "staging", "TEST_ADMIN": "1", "TEST_AJAX": "0",
                 "TEST_CAP": "1", "TEST_METHOD": "GET", "TEST_OPTIN": "1"}
        cases = [({}, True), ({"TEST_ENV": "local"}, True),
                 ({"TEST_ENV": "production"}, False), ({"TEST_ADMIN": "0"}, False),
                 ({"TEST_AJAX": "1"}, False), ({"TEST_CAP": "0"}, False),
                 ({"TEST_METHOD": "POST"}, False), ({"TEST_OPTIN": ""}, False),
                 ({"TEST_DROPIN": "1"}, False), ({"TEST_SAVE_DEFINED": "1"}, False),
                 ({"TEST_PAGE": "admin-post.php"}, False)]
        for changes, enabled in cases:
            with self.subTest(changes=changes):
                result = subprocess.run(["php"], input=php, text=True, cwd=ROOT,
                    env={**os.environ, **valid, **changes}, capture_output=True, check=False)
                self.assertEqual(0, result.returncode, result.stderr)
                self.assertEqual(enabled, json.loads(result.stdout)["enabled"])

    def test_sql_probe_bounds_memory_and_never_exports_query_values(self) -> None:
        php = r"""
<?php
const ABSPATH = '/synthetic/';
const MB_IN_BYTES = 1048576;
function add_action(...$args) {}
function add_filter(...$args) {}
function remove_filter(...$args) {}
function remove_action(...$args) {}
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function wp_get_environment_type() { return 'staging'; }
function is_admin() { return true; }
function wp_doing_ajax() { return false; }
function current_user_can($capability) { return true; }
function wp_json_encode($value, $flags) { return json_encode($value, $flags); }
function wp_print_inline_script_tag($value, $attributes) { echo $value; }
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_X_HS_ADMIN_SQL_PROFILE'] = '1';
class wpdb { public $num_queries = 42; public $queries = null; }
$wpdb = new wpdb();
$GLOBALS['pagenow'] = 'index.php';
require 'wordpress/mu-plugins/hs-admin-performance-probe.php';
// DECLARE_CALLERS
hs_admin_performance_probe_enable_sql_profile();
for ($index = 0; $index < 1000; ++$index) {
  $sql = 'SELECT private@example.com secret-token';
  $stack = $index < 2 ? 'unsafe(arg=secret-token), hs_example_lookup, wpdb->query' : 'hs_caller_' . $index;
  if ($index === 1) $stack = 'unsafe(hook), HS_EXAMPLE_LOOKUP, wpdb->query';
  if ($index === 3) $stack = 'do_action(unsafe-hook, private_token, unsafe), wpdb->query';
  if ($index === 2) $stack = 'WP_Hook->apply_filters, get_option, wpdb->get_row';
  $returned = hs_admin_performance_probe_collect_query(['preserved' => 1], $sql, 0.001, $stack);
  if (($returned['preserved'] ?? null) !== 1 || ($returned['hs_sql_profile_seq'] ?? null) !== $index + 1 || !empty($wpdb->queries)) throw new RuntimeException('Log is unbounded or custom data lost');
  $wpdb->queries[] = [$sql, 0.001, $stack, microtime(true), $returned];
}
hs_admin_performance_probe_print_metrics();
if (!empty($wpdb->queries)) throw new RuntimeException('Last SQL tuple retained');
"""
        declarations = "\n".join(f"function hs_caller_{index}() {{}}" for index in range(1000))
        php = php.replace("// DECLARE_CALLERS", declarations + "\nfunction hs_example_lookup() {}\nfunction get_option() {}")
        result = subprocess.run(["php"], input=php, text=True, cwd=ROOT,
            capture_output=True, check=False)
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertNotIn("private_token", result.stdout)
        self.assertNotIn("secret-token", result.stdout)
        self.assertNotIn("private@example.com", result.stdout)
        measured = json.loads(result.stdout.split(" = ", 1)[1].rstrip(";"))["sql_profile"]
        self.assertEqual(1000, measured["profiled_queries"])
        self.assertEqual(42, measured["queries_before"])
        self.assertLessEqual(len(measured["callers"]), 64)
        self.assertAlmostEqual(1000, measured["total_ms"])
        callers = {item["caller"]: item for item in measured["callers"]}
        self.assertEqual(2, callers["hs_example_lookup"]["count"])
        self.assertEqual(1, callers["get_option"]["count"])

    def test_sql_probe_does_not_remove_foreign_query_logs(self) -> None:
        php = r"""
<?php
const ABSPATH = '/synthetic/';
function add_action(...$args) {}
function add_filter(...$args) {}
function remove_filter(...$args) {}
function remove_action(...$args) {}
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function wp_get_environment_type() { return 'staging'; }
function is_admin() { return true; }
function wp_doing_ajax() { return false; }
function current_user_can($capability) { return true; }
function wp_die(...$args) { throw new RuntimeException('diagnostic unavailable'); }
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_X_HS_ADMIN_SQL_PROFILE'] = '1';
$GLOBALS['pagenow'] = 'index.php';
class wpdb { public $num_queries = 42; public $queries = null; }
$wpdb = new wpdb();
require 'wordpress/mu-plugins/hs-admin-performance-probe.php';
hs_admin_performance_probe_enable_sql_profile();
$data = hs_admin_performance_probe_collect_query([], 'SELECT private-value', 0.001, 'hs_example');
$wpdb->queries[] = ['SELECT private-value', 0.001, 'hs_example', 0, $data];
$foreign = ['foreign-private-value', 0.002, 'foreign', 0, []];
$wpdb->queries[] = $foreign;
try {
  hs_admin_performance_probe_collect_query([], 'SELECT another-private-value', 0.002, 'hs_example');
  throw new LogicException('Incompatible diagnostic must stop');
} catch (RuntimeException $error) {
  if ($wpdb->queries[1] !== $foreign || count($wpdb->queries) !== 2) throw new LogicException('Foreign log was modified');
}
echo 'PASS';
"""
        result = subprocess.run(["php"], input=php, text=True, cwd=ROOT,
            capture_output=True, check=False)
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual("PASS", result.stdout.strip())

    def test_sql_abort_disarms_before_handler_and_preserves_foreign_log(self) -> None:
        php = r"""
<?php
const ABSPATH = '/synthetic/';
function add_action(...$args) {}
function add_filter(...$args) {}
function remove_filter(...$args) { $GLOBALS['removed_filter'] = true; }
function remove_action(...$args) { $GLOBALS['removed_shutdown'] = true; }
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function wp_get_environment_type() { return 'staging'; }
function is_admin() { return true; }
function wp_doing_ajax() { return false; }
function current_user_can($capability) { return true; }
function wp_die($message, ...$args) {
  if (!$GLOBALS['removed_filter'] || !$GLOBALS['removed_shutdown'] || $GLOBALS['hs_admin_sql_profile']['pending'] !== null) throw new LogicException('Not disarmed before handler');
  echo $message;
  // Deliberately return: the abort helper must still stop and preserve foreign data.
}
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_X_HS_ADMIN_SQL_PROFILE'] = '1';
$GLOBALS['pagenow'] = 'index.php';
$GLOBALS['removed_filter'] = false;
$GLOBALS['removed_shutdown'] = false;
class wpdb { public $num_queries = 42; public $queries = null; }
$wpdb = new wpdb();
require 'wordpress/mu-plugins/hs-admin-performance-probe.php';
hs_admin_performance_probe_enable_sql_profile();
$foreign = ['private-value', 0.001, 'foreign', 0, []];
$wpdb->queries = [$foreign];
if (getenv('TEST_ABORT') === 'limit') $GLOBALS['hs_admin_sql_profile']['profiled_queries'] = 2048;
else $GLOBALS['hs_admin_sql_profile']['pending'] = 1;
register_shutdown_function(function () use ($wpdb, $foreign) {
  hs_admin_performance_probe_release_pending();
  if ($wpdb->queries !== [$foreign]) throw new LogicException('Foreign data changed');
  echo '|shutdown clean';
});
hs_admin_performance_probe_collect_query([], 'SELECT private-query', 0.001, 'hs_example');
throw new LogicException('Returning handler continued the query');
"""
        import os
        for scenario, message in [("limit", "SQL diagnostic query limit reached."),
                                  ("mismatch", "SQL diagnostic unavailable: incompatible query logging.")]:
            with self.subTest(scenario=scenario):
                result = subprocess.run(["php"], input=php, text=True, cwd=ROOT,
                    env={**os.environ, "TEST_ABORT": scenario}, capture_output=True, check=False)
                self.assertEqual(0, result.returncode, result.stderr)
                self.assertEqual(message + "|shutdown clean", result.stdout.strip())

    def test_frontend_lcp_attribution_is_bounded_and_redacted(self) -> None:
        script = """
import assert from 'node:assert/strict';
import { collectFrontendProfile } from './ops/performance/browser-frontend-profile.mjs';
globalThis.__hsFrontend = { lcpEntry: {
  startTime: 9852, url: 'https://test.hs-manacost.ru/wp-content/uploads/cover.jpg?token=private-token#nonce',
  tag: 'IMG', kind: 'img', loading: 'eager', fetchpriority: 'high',
} };
globalThis.performance = { getEntriesByType: () => Array.from({ length: 30 }, (_, i) => ({
  name: `https://test.hs-manacost.ru/wp-content/uploads/${i}.jpg?token=private-token`,
  initiatorType: 'img', startTime: 25, responseEnd: 9700 + i,
  duration: 9675 + i, transferSize: 123000, encodedBodySize: 122770,
})).concat([
  { name: 'https://test.hs-manacost.ru/wp-content/uploads/cover.jpg?token=private-token#nonce', initiatorType: 'img',
    startTime: 30, responseEnd: 9800, duration: 9770, transferSize: 123070, encodedBodySize: 122770 },
  { name: 'https://test.hs-manacost.ru/wp-admin/admin-ajax.php?nonce=private-token', initiatorType: 'fetch', duration: 99999 },
  { name: 'data:image/png;private-token', initiatorType: 'img', duration: 99999 },
  { name: 'https://untrusted.example/secret.jpg', initiatorType: 'img', duration: 99999 },
]) };
globalThis.innerHeight = 900; globalThis.innerWidth = 390;
const image = (src, overrides = {}) => ({
  src, currentSrc: src, complete: true, naturalWidth: 0,
  getBoundingClientRect: () => ({ top: 10, bottom: 30, left: 0, right: 50, width:50, height:20 }), ...overrides,
});
globalThis.document = { querySelectorAll: () => [
  image('https://untrusted.example/private.jpg'),
  image('https://private-token@test.hs-manacost.ru/secret.jpg'),
  image('data:image/png;private-token'),
  image('https://test.hs-manacost.ru/offscreen.jpg', { getBoundingClientRect: () => ({top:1000,bottom:1100,left:0,right:50,width:50,height:100}) }),
  image('https://test.hs-manacost.ru/pending.jpg', {complete:false}),
  image('https://test.hs-manacost.ru/loaded.jpg', {naturalWidth:20}),
  ...Array.from({ length: 20 }, (_, i) => image(`https://test.hs-manacost.ru/wp-content/uploads/broken${i}.jpg?token=private-token#nonce`)),
] };
const result = collectFrontendProfile();
assert.equal(result.lcp_resource.path, '/wp-content/uploads/cover.jpg');
assert.equal(result.lcp_tag, 'IMG');
assert.equal(result.lcp_timing.response_end_ms, 9800);
assert.equal(result.lcp_timing.encoded_body_bytes, 122770);
assert.equal(result.slowest_resources.length, 12);
assert.equal(result.failed_visible_resources.length, 12);
assert.deepEqual(result.pending_visible_resources, [{origin:'https://test.hs-manacost.ru',path:'/pending.jpg'}]);
assert.equal(result.failed_visible_resources[0].path, '/wp-content/uploads/broken0.jpg');
assert.ok(!JSON.stringify(result.failed_visible_resources).includes('offscreen'));
assert.ok(!JSON.stringify(result.failed_visible_resources).includes('pending'));
assert.ok(!JSON.stringify(result.failed_visible_resources).includes('loaded'));
assert.ok(!JSON.stringify(result).includes('private'));
assert.ok(!JSON.stringify(result).includes('untrusted'));
globalThis.__hsFrontend = {};
assert.equal(collectFrontendProfile().lcp_resource, null);
console.log('PASS');
"""
        result = subprocess.run(
            ["node", "--input-type=module", "-e", script], cwd=ROOT,
            capture_output=True, text=True, check=False,
        )
        self.assertEqual(0, result.returncode, result.stderr)

    def test_browser_sql_profile_never_exports_query_text(self) -> None:
        script = """
import { collectSqlProfile } from './ops/performance/browser-sql-profile.mjs';
const unavailable = collectSqlProfile();
globalThis.QueryMonitorData = { data: { db_queries: { data: { rows: [
  { sql: 'SELECT private@example.com secret-token', ltime: 0.01,
    stack: ['wpdb->query', 'hs_example_lookup'] },
  { sql: 'UPDATE private@example.com', ltime: 0.005,
    stack: ['wpdb->query', 'hs_example_lookup'] },
  { sql: 'SELECT another secret-token', ltime: 0.003,
    stack: ['unsafe caller with arguments'] },
] } } } };
console.log(JSON.stringify({ unavailable, measured: collectSqlProfile() }));
"""
        result = subprocess.run(
            ["node", "--input-type=module", "-e", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            check=False,
        )
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertNotIn("private@example.com", result.stdout)
        self.assertNotIn("secret-token", result.stdout)
        profile = json.loads(result.stdout)
        self.assertFalse(profile["unavailable"]["available"])
        self.assertEqual(3, profile["measured"]["profiled_queries"])
        self.assertEqual("hs_example_lookup", profile["measured"]["top_callers"][0]["caller"])
        self.assertEqual(2, profile["measured"]["top_callers"][0]["count"])
        self.assertEqual("hs_example_lookup", profile["measured"]["top_by_count"][0]["caller"])


if __name__ == "__main__":
    _ = unittest.main()
