"""Offline regression tests: never query a production service or log."""
import datetime as dt
import gzip
import fcntl
import importlib.util
import json
import os
from pathlib import Path
import subprocess
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[1]
HELPER = ROOT / "ops/monitoring/nginx_recent.py"
FPM_HELPER = ROOT / "ops/monitoring/fpm_status.py"
NOW = 1789041600  # 2026-09-10 12:00 UTC


def access(timestamp="10/Sep/2026:11:59:00 +0000", status=502, uri="/article/"):
    return f'127.0.0.1 - - [{timestamp}] "GET {uri} HTTP/1.1" {status} 100 "-" "test"\n'


def attribution(status=503, endpoint="login", owner="wordpress", upstream_status="503",
                retry_after_present=1, timestamp="2026-09-10T11:59:00+00:00"):
    return json.dumps({
        "time": timestamp,
        "status": status,
        "endpoint": endpoint,
        "owner": owner,
        "upstream_status": upstream_status,
        "retry_after_present": retry_after_present,
    }) + "\n"


class NginxRecentTests(unittest.TestCase):
    def setUp(self):
        spec = importlib.util.spec_from_file_location("nginx_recent", HELPER)
        self.monitor = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(self.monitor)
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.access = Path(self.temp.name) / "access.log"
        self.error = Path(self.temp.name) / "error.log"
        self.attribution = Path(self.temp.name) / "attribution.log"
        self.plausible_access = Path(self.temp.name) / "plausible.access.log"
        self.access.write_text("")
        self.error.write_text("")
        self.attribution.write_text("")
        self.plausible_access.write_text("")

    def collect(self, **kwargs):
        return self.monitor.collect(self.access, self.error, now=NOW, **kwargs)

    def test_counts_recent_errors_and_excludes_stale_and_future(self):
        self.access.write_text(access() + access("10/Sep/2026:11:54:59 +0000")
                               + access("10/Sep/2026:12:10:00 +0000"))
        result = self.collect()
        self.assertEqual(result["fivexx"], 1)
        self.assertEqual(result["page_5xx"], 1)

    def test_missing_log_is_unknown_not_zero(self):
        self.access.unlink()
        with self.assertRaises(self.monitor.Unknown):
            self.collect()

    def test_permission_error_is_unknown(self):
        with patch("os.open", side_effect=PermissionError):
            with self.assertRaises(self.monitor.Unknown):
                self.collect()

    def test_malformed_input_cannot_produce_ok(self):
        self.access.write_text("<html>permission denied</html>\n")
        with self.assertRaises(self.monitor.Unknown):
            self.collect()

    def test_empty_readable_logs_are_valid_no_observed_requests(self):
        self.assertEqual(self.collect()["requests"], 0)

    def test_recent_rotation_is_included(self):
        self.access.write_text(access(status=200))
        rotated = Path(str(self.access) + ".1")
        rotated.write_text(access())
        os.utime(rotated, (NOW, NOW))
        self.assertEqual(self.collect()["fivexx"], 1)

    def test_gzip_rotation_is_included(self):
        rotated = Path(str(self.access) + ".1.gz")
        rotated.write_bytes(gzip.compress(access().encode()))
        os.utime(rotated, (NOW, NOW))
        self.assertEqual(self.collect()["fivexx"], 1)

    def test_corrupt_gzip_is_unknown(self):
        rotated = Path(str(self.access) + ".1.gz")
        rotated.write_bytes(b"not gzip")
        os.utime(rotated, (NOW, NOW))
        with self.assertRaises(self.monitor.Unknown):
            self.collect()

    def test_large_compressed_rotation_does_not_expand_without_limit(self):
        rotated = Path(str(self.access) + ".1.gz")
        rotated.write_bytes(gzip.compress(access().encode() * 100))
        os.utime(rotated, (NOW, NOW))
        with self.assertRaises(self.monitor.Unknown):
            self.collect(byte_limit=512)

    def test_tail_cutting_into_window_is_unknown(self):
        self.access.write_text(access() * 100)
        with self.assertRaises(self.monitor.Unknown):
            self.collect(byte_limit=512)

    def test_large_old_prefix_does_not_force_full_file_read(self):
        old = access("10/Sep/2026:11:50:00 +0000", status=200)
        self.access.write_text(old * 10000 + access())
        self.assertEqual(self.collect(byte_limit=4096)["fivexx"], 1)

    def test_partial_last_line_is_unknown(self):
        self.access.write_text(access().rstrip("\n"))
        with self.assertRaises(self.monitor.Unknown):
            self.collect()

    def test_error_patterns_include_socket_refused_and_reset(self):
        self.error.write_text("2026/09/10 11:59:00 [error] 1#1: connect() failed (111: Connection refused) while connecting to upstream\n"
                              "2026/09/10 11:59:01 [error] 1#1: recv() failed (104: Connection reset by peer) while reading response header from upstream\n")
        self.assertEqual(self.collect()["upstream_errors"], 2)

    def test_php_stack_continuations_do_not_hide_an_upstream_error(self):
        self.error.write_text(
            "2026/09/10 11:59:00 [error] 1#1: upstream timed out while reading response header\n"
            "Stack trace:\n"
            "#0 fixture()\n"
            "  thrown in /fixture.php on line 1\n"
        )

        self.assertEqual(self.collect()["upstream_errors"], 1)

    def test_attribution_separates_wordfence_candidates_from_gateway_failures(self):
        self.access.write_text(
            access(status=503, uri="/wp-login.php")
            + access(status=502, uri="/article/")
            + access(status=500, uri="/mca/event")
        )
        self.attribution.write_text(
            attribution()
            + attribution(status=502, endpoint="page", owner="wordpress", upstream_status="502",
                          retry_after_present=0)
            + attribution(status=500, endpoint="analytics", owner="plausible", upstream_status="500",
                          retry_after_present=0)
        )

        result = self.collect(attribution_log=self.attribution)

        self.assertEqual(result["attributed_5xx"], 3)
        self.assertEqual(result["waf_503_candidate"], 1)
        self.assertEqual(result["gateway_5xx"], 1)
        self.assertEqual(result["non_waf_5xx"], 2)
        self.assertEqual(result["plausible_5xx"], 1)
        self.assertEqual(result["unattributed_5xx"], 0)

    def test_attribution_rejects_unbounded_or_malformed_fields(self):
        entry = json.loads(attribution())
        entry["request_uri"] = "/private?token=secret"
        self.attribution.write_text(json.dumps(entry) + "\n")

        with self.assertRaises(self.monitor.Unknown):
            self.collect(attribution_log=self.attribution)

    def test_stale_legacy_attribution_is_skipped_but_current_legacy_is_unknown(self):
        legacy = {
            "time": "2026-09-10T11:50:00+00:00",
            "status": 503,
            "endpoint": "login",
            "upstream_status": "503",
            "retry_after_present": 1,
        }
        self.access.write_text(access(status=503, uri="/wp-login.php"))
        self.attribution.write_text(json.dumps(legacy) + "\n" + attribution())

        self.assertEqual(
            self.collect(attribution_log=self.attribution)["waf_503_candidate"],
            1,
        )

        legacy["time"] = "2026-09-10T11:59:00+00:00"
        self.attribution.write_text(json.dumps(legacy) + "\n")
        with self.assertRaises(self.monitor.Unknown):
            self.collect(attribution_log=self.attribution)

    def test_plausible_access_log_reconciles_location_level_attribution(self):
        self.plausible_access.write_text(access(status=500, uri="/mca/event"))
        self.attribution.write_text(
            attribution(status=500, endpoint="analytics", owner="plausible",
                        upstream_status="500", retry_after_present=0)
        )

        result = self.collect(attribution_log=self.attribution,
                              additional_access_logs=[self.plausible_access])

        self.assertEqual(result["requests"], 1)
        self.assertEqual(result["fivexx"], 1)
        self.assertEqual(result["analytics_5xx"], 1)
        self.assertEqual(result["plausible_5xx"], 1)
        self.assertEqual(result["unattributed_5xx"], 0)

    def test_colon_separated_upstream_history_is_classified(self):
        self.access.write_text(access(status=503))
        self.attribution.write_text(
            attribution(status=503, endpoint="page", owner="wordpress",
                        upstream_status="404 : 503", retry_after_present=1)
        )

        result = self.collect(attribution_log=self.attribution)

        self.assertEqual(result["waf_503_candidate"], 1)

    def test_missing_attribution_log_is_unknown_when_requested(self):
        self.attribution.unlink()

        with self.assertRaises(self.monitor.Unknown):
            self.collect(attribution_log=self.attribution)

    def test_cli_does_not_page_for_a_bounded_wordfence_burst(self):
        timestamp = dt.datetime.now(dt.timezone.utc) - dt.timedelta(seconds=30)
        access_time = timestamp.strftime("%d/%b/%Y:%H:%M:%S %z")
        attribution_time = timestamp.isoformat(timespec="seconds")
        self.access.write_text(access(access_time, status=503, uri="/wp-login.php") * 25)
        self.attribution.write_text(attribution(timestamp=attribution_time) * 25)

        result = subprocess.run(
            ["python3", str(HELPER), "--access-log", str(self.access), "--error-log", str(self.error),
             "--attribution-log", str(self.attribution)],
            capture_output=True,
            text=True,
            timeout=5,
        )

        self.assertEqual(result.returncode, 0, result.stdout)
        self.assertIn("waf_503_candidate=25", result.stdout)

    def test_cli_pages_for_three_gateway_failures(self):
        timestamp = dt.datetime.now(dt.timezone.utc) - dt.timedelta(seconds=30)
        access_time = timestamp.strftime("%d/%b/%Y:%H:%M:%S %z")
        attribution_time = timestamp.isoformat(timespec="seconds")
        self.access.write_text(access(access_time, status=502) * 3)
        self.attribution.write_text(
            attribution(status=502, endpoint="page", owner="wordpress", upstream_status="502",
                        retry_after_present=0, timestamp=attribution_time) * 3
        )

        result = subprocess.run(
            ["python3", str(HELPER), "--access-log", str(self.access), "--error-log", str(self.error),
             "--attribution-log", str(self.attribution)],
            capture_output=True,
            text=True,
            timeout=5,
        )

        self.assertEqual(result.returncode, 1, result.stdout)
        self.assertIn("gateway_5xx=3", result.stdout)

    def test_cli_pages_when_wordfence_candidate_guard_is_exceeded(self):
        timestamp = dt.datetime.now(dt.timezone.utc) - dt.timedelta(seconds=30)
        access_time = timestamp.strftime("%d/%b/%Y:%H:%M:%S %z")
        attribution_time = timestamp.isoformat(timespec="seconds")
        self.access.write_text(access(access_time, status=503, uri="/wp-login.php") * 351)
        self.attribution.write_text(attribution(timestamp=attribution_time) * 351)

        result = subprocess.run(
            ["python3", str(HELPER), "--access-log", str(self.access), "--error-log", str(self.error),
             "--attribution-log", str(self.attribution)],
            capture_output=True,
            text=True,
            timeout=5,
        )

        self.assertEqual(result.returncode, 1, result.stdout)
        self.assertIn("waf_503_candidate=351", result.stdout)

    def test_cli_pages_for_three_unattributed_failures(self):
        timestamp = dt.datetime.now(dt.timezone.utc) - dt.timedelta(seconds=30)
        access_time = timestamp.strftime("%d/%b/%Y:%H:%M:%S %z")
        self.access.write_text(access(access_time, status=500) * 3)

        result = subprocess.run(
            ["python3", str(HELPER), "--access-log", str(self.access), "--error-log", str(self.error),
             "--attribution-log", str(self.attribution)],
            capture_output=True,
            text=True,
            timeout=5,
        )

        self.assertEqual(result.returncode, 1, result.stdout)
        self.assertIn("unattributed_5xx=3", result.stdout)

    def test_categories_do_not_hide_analytics_or_admin(self):
        self.access.write_text(access(uri="/wp-admin/post.php")
                               + access(uri="/wp-content/uploads/a.png")
                               + access(uri="/api/event?secret=do-not-log")
                               + access(uri="/mca/event")
                               + access(uri="/mca/script.js")
                               + access(uri="/views/hit")
                               + access(status=403, uri="/")
                               + access(status=403, uri="/wp-admin/edit.php"))
        result = self.collect()
        self.assertEqual((result["admin_5xx"], result["media_5xx"], result["analytics_5xx"]), (1, 1, 4))
        self.assertEqual((result["root403"], result["admin403"]), (1, 1))
        self.assertNotIn("secret", json.dumps(result))

    def test_cli_unknown_has_nonzero_exit_and_no_zero_counters(self):
        result = subprocess.run(["python3", str(HELPER), "--access-log", str(self.access / "absent"),
                                 "--error-log", str(self.error)], capture_output=True, text=True, timeout=5)
        self.assertEqual(result.returncode, 2)
        self.assertIn("UNKNOWN", result.stdout)
        self.assertNotIn("count=0", result.stdout)

    def test_proxy_denial_is_not_a_homepage_denial(self):
        self.access.write_text(access(status=403, uri="/?hs_tooltip_img=https%3A%2F%2Fexample.invalid%2Fimage.png")
                               + access(status=403, uri="/?utm_source=test")
                               + access(status=403, uri="/?hs_tooltip_img=")
                               + access(status=403, uri="/?hs_tooltip_img[]=image"))
        result = self.collect()
        self.assertEqual(result["image_proxy403"], 1)
        self.assertEqual(result["root403"], 3)
        self.assertEqual(result["requests"], 4)

    def test_encoded_and_repeated_proxy_parameters_match_php_last_value(self):
        self.access.write_text(access(status=403, uri="/?hs_tooltip_%69mg=image")
                               + access(status=403, uri="/?hs_tooltip_img=image&hs_tooltip_img=")
                               + access(status=403, uri="/?hs_tooltip_img=image&hs_tooltip_img[]=array")
                               + access(status=403, uri="/?hs_tooltip_img[]=array&hs_tooltip_img=image"))
        result = self.collect()
        self.assertEqual((result["image_proxy403"], result["root403"]), (2, 2))

    def test_proxy_5xx_is_counted_as_media_and_not_hidden(self):
        self.access.write_text(access(uri="/?hs_tooltip_img=image")
                               + access(uri="/wp-admin/post.php?hs_tooltip_img=image"))
        result = self.collect()
        self.assertEqual((result["fivexx"], result["media_5xx"], result["admin_5xx"]), (2, 1, 1))

    def test_php_normalized_keys_and_malformed_array_keys(self):
        for query in ("hs_tooltip_img%00=x", "hs.tooltip.img=x", "hs+tooltip+img=x",
                      "+hs_tooltip_img=x", "hs_tooltip_img=x&hs_tooltip_img%5B=x"):
            with self.subTest(query=query):
                self.assertTrue(self.monitor.is_root_image_proxy("/?" + query))
        for query in ("%09hs_tooltip_img=x", "hs_tooltip_img%5B=x",
                      "hs_tooltip_img=x&hs.tooltip.img%5B%5D=z"):
            with self.subTest(query=query):
                self.assertFalse(self.monitor.is_root_image_proxy("/?" + query))


class FpmStatusTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.state = Path(self.temp.name) / "php84.state"

    @staticmethod
    def payload(**overrides):
        values = {
            "pool": "hs-manacost.ru",
            "process manager": "dynamic",
            "start time": 1789009404,
            "accepted conn": 1000,
            "listen queue": 0,
            "max listen queue": 0,
            "idle processes": 15,
            "active processes": 1,
            "total processes": 16,
            "max active processes": 16,
            "max children reached": 0,
            "slow requests": 0,
        }
        values.update(overrides)
        return "Content-Type: application/json\r\n\r\n" + json.dumps(values)

    def run_helper(self, payload=None, *extra):
        return subprocess.run(
            ["python3", str(FPM_HELPER), "--pool", "php84", "--max-children", "32",
             "--state-file", str(self.state), *extra],
            input=self.payload() if payload is None else payload,
            capture_output=True,
            text=True,
            timeout=5,
        )

    def age_state(self, seconds=60):
        state = json.loads(self.state.read_text())
        state["sample_time"] -= seconds
        self.state.write_text(json.dumps(state))

    def test_healthy_status_emits_bounded_metrics_and_private_state(self):
        result = self.run_helper()

        self.assertEqual(result.returncode, 0, result.stdout)
        self.assertIn("OK fpm_status pool=php84", result.stdout)
        self.assertIn("listen_queue=0", result.stdout)
        self.assertIn("utilization_pct=3", result.stdout)
        self.assertEqual(self.state.stat().st_mode & 0o777, 0o600)

    def test_two_consecutive_queue_samples_fail(self):
        first = self.run_helper(self.payload(**{"listen queue": 1}))
        self.age_state()
        second = self.run_helper(self.payload(**{"listen queue": 1}))

        self.assertEqual(first.returncode, 0, first.stdout)
        self.assertEqual(second.returncode, 1, second.stdout)
        self.assertIn("queue_streak=2", second.stdout)

    def test_new_max_children_event_fails_but_restart_resets_delta(self):
        self.assertEqual(self.run_helper().returncode, 0)
        reached = self.run_helper(self.payload(**{"max children reached": 1}))
        restarted = self.run_helper(self.payload(**{"start time": 1789010000,
                                                     "max children reached": 0}))

        self.assertEqual(reached.returncode, 1, reached.stdout)
        self.assertIn("max_children_delta=1", reached.stdout)
        self.assertEqual(restarted.returncode, 0, restarted.stdout)

    def test_two_high_utilization_samples_fail(self):
        first = self.run_helper(self.payload(**{"active processes": 24, "idle processes": 8,
                                                "total processes": 32}))
        self.age_state()
        second = self.run_helper(self.payload(**{"active processes": 24, "idle processes": 8,
                                                 "total processes": 32}))

        self.assertEqual(first.returncode, 0, first.stdout)
        self.assertEqual(second.returncode, 1, second.stdout)
        self.assertIn("utilization_streak=2", second.stdout)

    def test_immediate_manual_rerun_does_not_create_sustained_queue(self):
        first = self.run_helper(self.payload(**{"listen queue": 1}))
        second = self.run_helper(self.payload(**{"listen queue": 1}))

        self.assertEqual(first.returncode, 0, first.stdout)
        self.assertEqual(second.returncode, 0, second.stdout)
        self.assertIn("queue_streak=1", second.stdout)

    def test_stale_sample_does_not_create_sustained_queue(self):
        first = self.run_helper(self.payload(**{"listen queue": 1}))
        self.age_state(3600)
        second = self.run_helper(self.payload(**{"listen queue": 1}))

        self.assertEqual(first.returncode, 0, first.stdout)
        self.assertEqual(second.returncode, 0, second.stdout)
        self.assertIn("queue_streak=1", second.stdout)

    def test_failed_measurement_breaks_queue_continuity(self):
        first = self.run_helper(self.payload(**{"listen queue": 1}))
        self.age_state()
        malformed = self.run_helper('{"token":"do-not-log"}')
        second = self.run_helper(self.payload(**{"listen queue": 1}))

        self.assertEqual(first.returncode, 0, first.stdout)
        self.assertEqual(malformed.returncode, 2, malformed.stdout)
        self.assertEqual(second.returncode, 0, second.stdout)
        self.assertIn("queue_streak=1", second.stdout)

    def test_malformed_payload_is_unknown_without_echoing_input(self):
        result = self.run_helper('{"token":"do-not-log"}')

        self.assertEqual(result.returncode, 2)
        self.assertIn("UNKNOWN fpm_status", result.stdout)
        self.assertNotIn("do-not-log", result.stdout)

    def test_state_symlink_is_rejected(self):
        target = Path(self.temp.name) / "target"
        target.write_text("unchanged")
        self.state.symlink_to(target)

        result = self.run_helper()

        self.assertEqual(result.returncode, 2)
        self.assertEqual(target.read_text(), "unchanged")

    def test_state_with_group_read_permission_is_rejected(self):
        self.assertEqual(self.run_helper().returncode, 0)
        self.state.chmod(0o640)

        result = self.run_helper()

        self.assertEqual(result.returncode, 2)
        self.assertIn("UNKNOWN fpm_status", result.stdout)

    def test_group_writable_state_directory_is_rejected(self):
        Path(self.temp.name).chmod(0o770)

        result = self.run_helper()

        self.assertEqual(result.returncode, 2)
        self.assertIn("UNKNOWN fpm_status", result.stdout)


class ShellHealthcheckTests(unittest.TestCase):

    def test_monitoring_rollback_backs_up_the_parser_it_restores(self):
        runbook = (ROOT / "docs/operations/availability-monitoring.md").read_text()

        self.assertIn("existing `/usr/local/libexec/manacost-monitoring/nginx_recent.py`", runbook)
        self.assertIn("recorded pre-state of `hs-manacost-healthcheck`", runbook)
        self.assertIn("`nginx_recent.py`", runbook)

    def test_monitoring_rollback_restores_every_scheduled_component(self):
        runbook = (ROOT / "docs/operations/availability-monitoring.md").read_text()

        for component in (
                "hs-manacost-healthcheck", "koloda-healthcheck.sh", "nginx_recent.py",
                "run-healthcheck.sh", "hs-manacost-healthcheck.cron", "koloda-healthcheck.cron"):
            self.assertIn(component, runbook)
        self.assertIn("recorded pre-state", runbook)

    def test_koloda_status_contracts_and_transport_failures(self):
        with tempfile.TemporaryDirectory() as temporary:
            directory = Path(temporary)
            fake = directory / "curl"
            fake.write_text('''#!/bin/bash
while [[ $# -gt 0 ]]; do
  if [[ "$1" == "-D" ]]; then printf 'HTTP/2 %s\\r\\n' "$CURL_STATUS" > "$2"; shift; fi
  shift
done
printf 'code=%s ttfb=0.1 total=0.2' "$CURL_STATUS"
exit "$CURL_EXIT"
''')
            fake.chmod(0o700)
            for expected, actual, curl_exit, success in (
                    (200, 200, 0, True), (200, 403, 0, False),
                    (403, 403, 0, True), (403, 200, 0, False),
                    (403, 403, 28, False), (200, 200, 28, False)):
                with self.subTest(expected=expected, actual=actual, curl_exit=curl_exit):
                    log = directory / "probe.log"
                    log.write_text("")
                    result = subprocess.run([
                        "bash", "-c", 'source "$1"; check_url fixture https://example.invalid/ dns "$2"; exit "$STATUS"',
                        "test", str(ROOT / "ops/monitoring/koloda-healthcheck.sh"), str(expected)],
                        env=dict(os.environ, PATH=str(directory) + ":" + os.environ["PATH"],
                                 HEALTHCHECK_LOG=str(log), CURL_STATUS=str(actual), CURL_EXIT=str(curl_exit)),
                        capture_output=True, text=True, timeout=5)
                    self.assertEqual(result.returncode, 0 if success else 1)
                    self.assertIn("OK url" if success else "FAIL url", log.read_text())

    def check_function(self, site, function, *, helper_output="UNKNOWN fixture", helper_rc=2, inactive=""):
        with tempfile.TemporaryDirectory() as temporary:
            directory = Path(temporary)
            helper = directory / "helper.py"
            helper.write_text(f"print({helper_output!r})\nraise SystemExit({helper_rc})\n")
            log = directory / "monitor.log"
            systemctl = directory / "systemctl"
            systemctl.write_text('#!/bin/bash\n[[ "$*" != *"$INACTIVE"* || -z "$INACTIVE" ]]\n')
            systemctl.chmod(0o700)
            environment = dict(os.environ, HEALTHCHECK_LOG=str(log), HEALTHCHECK_NGINX_HELPER=str(helper),
                               PATH=str(directory) + ":" + os.environ["PATH"], INACTIVE=inactive)
            result = subprocess.run(["bash", "-c", 'source "$1"; "$2"; exit "$STATUS"', "test",
                                     str(ROOT / "ops/monitoring" / site), function],
                                    capture_output=True, text=True, env=environment, timeout=5)
            return result.returncode, log.read_text()

    def test_wrapper_propagates_unknown(self):
        for site in ("hs-manacost-healthcheck.sh", "koloda-healthcheck.sh"):
            with self.subTest(site=site):
                code, log = self.check_function(site, "check_recent_nginx_incidents")
                self.assertEqual(code, 1)
                self.assertIn("UNKNOWN", log)

    def test_invalid_helper_output_is_unknown_even_with_exit_zero(self):
        code, log = self.check_function("hs-manacost-healthcheck.sh", "check_recent_nginx_incidents",
                                        helper_output="invalid", helper_rc=0)
        self.assertEqual(code, 1)
        self.assertIn("UNKNOWN", log)

    def test_hs_nginx_wrapper_requires_the_sanitized_attribution_log(self):
        with tempfile.TemporaryDirectory() as temporary:
            directory = Path(temporary)
            trace = directory / "args"
            helper = directory / "helper.py"
            helper.write_text(
                "import os, sys\n"
                "open(os.environ['ARGS_TRACE'], 'w').write('\\n'.join(sys.argv[1:]))\n"
                "print('OK nginx_recent fixture')\n"
            )
            log = directory / "monitor.log"
            result = subprocess.run(
                ["bash", "-c", 'source "$1"; check_recent_nginx_incidents; exit "$STATUS"', "test",
                 str(ROOT / "ops/monitoring/hs-manacost-healthcheck.sh")],
                env=dict(os.environ, HEALTHCHECK_LOG=str(log), HEALTHCHECK_NGINX_HELPER=str(helper),
                         HEALTHCHECK_ATTRIBUTION_LOG=str(directory / "attribution.log"),
                         HEALTHCHECK_PLAUSIBLE_ACCESS_LOG=str(directory / "plausible.access.log"),
                         ARGS_TRACE=str(trace)),
                capture_output=True,
                text=True,
                timeout=5,
            )

            self.assertEqual(result.returncode, 0, log.read_text())
            arguments = trace.read_text().splitlines()
            self.assertIn("--attribution-log", arguments)
            self.assertIn(str(directory / "attribution.log"), arguments)
            self.assertIn("--additional-access-log", arguments)
            self.assertIn(str(directory / "plausible.access.log"), arguments)

    def test_hs_fpm_wrapper_checks_both_pools_and_pages_on_sustained_queue(self):
        with tempfile.TemporaryDirectory() as temporary:
            directory = Path(temporary)
            fcgi = directory / "cgi-fcgi"
            fcgi.write_text('''#!/usr/bin/env python3
import json
print("Content-Type: application/json\\r\\n")
print(json.dumps({
    "pool": "fixture", "process manager": "dynamic", "start time": 1789009404,
    "accepted conn": 1, "listen queue": 1, "max listen queue": 1,
    "idle processes": 1, "active processes": 1, "total processes": 2,
    "max active processes": 1, "max children reached": 0, "slow requests": 0,
}))
''')
            fcgi.chmod(0o700)
            state = directory / "state"
            state.mkdir()
            state.chmod(0o700)
            ager = directory / "age-state.py"
            ager.write_text('''import json, pathlib, sys
for path in pathlib.Path(sys.argv[1]).glob("*.state"):
    state = json.loads(path.read_text())
    state["sample_time"] -= 60
    path.write_text(json.dumps(state))
''')
            log = directory / "monitor.log"
            result = subprocess.run(
                ["bash", "-c",
                 'source "$1"; check_php_fpm_status; python3 "$FPM_STATE_AGER" "$HEALTHCHECK_FPM_STATE_DIR"; check_php_fpm_status; exit "$STATUS"', "test",
                 str(ROOT / "ops/monitoring/hs-manacost-healthcheck.sh")],
                env=dict(os.environ, HEALTHCHECK_LOG=str(log), HEALTHCHECK_FCGI_CLIENT=str(fcgi),
                         HEALTHCHECK_FPM_HELPER=str(FPM_HELPER), HEALTHCHECK_FPM_STATE_DIR=str(state),
                         FPM_STATE_AGER=str(ager)),
                capture_output=True,
                text=True,
                timeout=5,
            )

            self.assertEqual(result.returncode, 1, log.read_text())
            self.assertRegex(log.read_text(), r"FAIL fpm_status pool=php84 .*queue_streak=2")
            self.assertRegex(log.read_text(), r"FAIL fpm_status pool=php81 .*queue_streak=2")

    def test_fpm_wrapper_does_not_log_ok_when_cgi_transport_fails(self):
        with tempfile.TemporaryDirectory() as temporary:
            directory = Path(temporary)
            fcgi = directory / "cgi-fcgi"
            fcgi.write_text("#!/bin/sh\nexit 1\n")
            fcgi.chmod(0o700)
            helper = directory / "helper.py"
            helper.write_text("print('OK fpm_status pool=php84 fixture')\n")
            state = directory / "state"
            state.mkdir(mode=0o700)
            log = directory / "monitor.log"

            result = subprocess.run(
                ["bash", "-c",
                 'source "$1"; check_one_php_fpm_status php84 /fixture.sock 32; exit "$STATUS"',
                 "test", str(ROOT / "ops/monitoring/hs-manacost-healthcheck.sh")],
                env=dict(os.environ, HEALTHCHECK_LOG=str(log), HEALTHCHECK_FCGI_CLIENT=str(fcgi),
                         HEALTHCHECK_FPM_HELPER=str(helper), HEALTHCHECK_FPM_STATE_DIR=str(state)),
                capture_output=True,
                text=True,
                timeout=5,
            )

            self.assertEqual(result.returncode, 1)
            self.assertIn("UNKNOWN fpm_status pool=php84", log.read_text())
            self.assertNotIn("OK fpm_status", log.read_text())

    def test_fpm_wrapper_timeout_breaks_persisted_queue_continuity(self):
        with tempfile.TemporaryDirectory() as temporary:
            directory = Path(temporary)
            state_dir = directory / "state"
            state_dir.mkdir(mode=0o700)
            state_file = state_dir / "hs-php84-fpm.state"
            payload = FpmStatusTests.payload(**{"listen queue": 1})
            helper_command = [
                "python3", str(FPM_HELPER), "--pool", "php84", "--max-children", "32",
                "--state-file", str(state_file),
            ]
            first = subprocess.run(helper_command, input=payload, capture_output=True, text=True, timeout=5)
            self.assertEqual(first.returncode, 0, first.stdout)

            fcgi = directory / "cgi-fcgi"
            fcgi.write_text("#!/bin/sh\nprintf '{'\nsleep 10\n")
            fcgi.chmod(0o700)
            log = directory / "monitor.log"
            timed_out = subprocess.run(
                ["bash", "-c",
                 'source "$1"; check_one_php_fpm_status php84 /fixture.sock 32; exit "$STATUS"',
                 "test", str(ROOT / "ops/monitoring/hs-manacost-healthcheck.sh")],
                env=dict(os.environ, HEALTHCHECK_LOG=str(log), HEALTHCHECK_FCGI_CLIENT=str(fcgi),
                         HEALTHCHECK_FPM_HELPER=str(FPM_HELPER), HEALTHCHECK_FPM_STATE_DIR=str(state_dir)),
                capture_output=True,
                text=True,
                timeout=7,
            )

            self.assertEqual(timed_out.returncode, 1, log.read_text())
            self.assertIn("UNKNOWN fpm_status pool=php84", log.read_text())
            self.assertEqual(0, json.loads(state_file.read_text())["sample_valid"])
            recovered = subprocess.run(
                helper_command, input=payload, capture_output=True, text=True, timeout=5)
            self.assertEqual(recovered.returncode, 0, recovered.stdout)
            self.assertIn("queue_streak=1", recovered.stdout)

    def test_actual_php84_failure_is_reported_by_both_sites(self):
        for site, function in (("hs-manacost-healthcheck.sh", "check_origin_services"),
                               ("koloda-healthcheck.sh", "check_services")):
            with self.subTest(site=site):
                code, log = self.check_function(site, function, inactive="php-fpm84")
                self.assertEqual(code, 1)
                self.assertRegex(log, r"FAIL .*php-fpm84")

    def test_legacy_login_php81_remains_monitored(self):
        code, log = self.check_function("hs-manacost-healthcheck.sh", "check_origin_services", inactive="php-fpm81")
        self.assertEqual(code, 1)
        self.assertRegex(log, r"FAIL .*php-fpm81")

    def test_all_failed_hs_probes_fit_full_deadline_with_local_headroom(self):
        with tempfile.TemporaryDirectory() as temporary:
            directory = Path(temporary)
            trace = directory / "curl-budget"
            fake = directory / "curl"
            fake.write_text('''#!/usr/bin/env python3
import os, sys
from pathlib import Path
args = sys.argv[1:]
with open(os.environ["CURL_TRACE"], "a") as output:
    output.write(args[args.index("--max-time") + 1] + "\\n")
for flag in ("-D", "-o"):
    if flag in args and args[args.index(flag) + 1] != "/dev/null":
        Path(args[args.index(flag) + 1]).write_text("")
raise SystemExit(28)
''')
            fake.chmod(0o700)
            logger = directory / "logger"
            logger.write_text("#!/bin/sh\nexit 0\n")
            logger.chmod(0o700)
            local_checks = ("check_dns", "check_origin_services", "check_php_sockets", "check_swap_pressure",
                            "check_origin_firewall", "check_admin_monitor_freshness", "check_rkn_checker_freshness",
                            "check_recent_nginx_incidents", "check_php_fpm_status")
            overrides = " ".join(f"{name}() {{ :; }};" for name in local_checks)
            result = subprocess.run(["bash", "-c", 'source "$1"; ' + overrides + ' main', "test",
                                     str(ROOT / "ops/monitoring/hs-manacost-healthcheck.sh")],
                                    env=dict(os.environ, PATH=str(directory) + ":" + os.environ["PATH"],
                                             HEALTHCHECK_LOG=str(directory / "log"), CURL_TRACE=str(trace)),
                                    capture_output=True, text=True, timeout=5)
            self.assertEqual(result.returncode, 1)
            budgets = [int(value) for value in trace.read_text().splitlines()]
            self.assertEqual(len(budgets), 18, "All routes on both edges must be attempted")
            # 3 DNS lookups x3s +20s parser +30s headroom for local checks.
            self.assertLessEqual(sum(budgets) + 9 + 20 + 30, 240)

    def test_koloda_full_run_keeps_positive_and_negative_media_contracts(self):
        with tempfile.TemporaryDirectory() as temporary:
            directory = Path(temporary)
            trace = directory / "trace"
            local_checks = ("check_dns", "check_services", "check_php_sockets", "check_recent_nginx_incidents",
                            "check_legacy_redirect", "check_redis", "check_ru_proxy")
            overrides = " ".join(f"{name}() {{ :; }};" for name in local_checks)
            result = subprocess.run([
                "bash", "-c", 'source "$1"; ' + overrides
                + ' check_url() { printf "%s %s %s\\n" "$1" "$2" "${4:-200}" >> "$TRACE"; }; main',
                "test", str(ROOT / "ops/monitoring/koloda-healthcheck.sh")],
                env=dict(os.environ, HEALTHCHECK_LOG=str(directory / "log"), TRACE=str(trace)),
                capture_output=True, text=True, timeout=5)
            self.assertEqual(result.returncode, 0)
            calls = [line.split() for line in trace.read_text().splitlines()]
            by_name = {name: (url, status) for name, url, status in calls}
            self.assertEqual(by_name["manacost_source"], (
                "https://hs-manacost.ru/wp-content/uploads/2026/03/bg-separator-2-optimized.png", "200"))
            self.assertEqual(by_name["bg_proxy"][1], "200")
            self.assertEqual(by_name["proxy_rejects_unlisted_host"][1], "403")
            # 8 HTTP x20s, DNS x3s, legacy 10s, SSH 15s, parser 20s, local 20s.
            self.assertEqual(len(calls), 8)
            self.assertLessEqual(len(calls) * 20 + 9 + 10 + 15 + 20 + 20, 240)


class CronRunnerTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.directory = Path(self.temp.name)
        self.log = self.directory / "log"
        self.probe = self.directory / "probe"
        self.probe.write_text('#!/bin/bash\nexit "${PROBE_EXIT:-0}"\n')
        self.probe.chmod(0o700)
        logger = self.directory / "logger"
        logger.write_text("#!/bin/sh\nexit 0\n")
        logger.chmod(0o700)
        source = (ROOT / "ops/monitoring/run-healthcheck.sh").read_text()
        # Relocate only fixed production paths into disposable fixtures.
        for name in ("hs-manacost-healthcheck", "koloda-healthcheck.sh"):
            source = source.replace("/usr/local/sbin/" + name, str(self.probe))
        for name in ("hs-manacost-healthcheck.log", "koloda-healthcheck.log"):
            source = source.replace("/var/log/" + name, str(self.log))
        source = source.replace("/run/lock/manacost-monitoring", str(self.directory))
        self.runner = self.directory / "runner.sh"
        self.runner.write_text(source)
        self.environment = dict(os.environ, PATH=str(self.directory) + ":" + os.environ["PATH"])

    def run_probe(self, site="hs", mode="quick", code=0):
        return subprocess.run(["bash", str(self.runner), site, mode],
                              env=dict(self.environment, PROBE_EXIT=str(code)),
                              capture_output=True, text=True, timeout=5)

    def test_success_and_failure_exit_status_propagate(self):
        self.assertEqual(self.run_probe().returncode, 0)
        self.assertEqual(self.run_probe(code=1).returncode, 1)
        self.assertIn("FAIL runner", self.log.read_text())

    def test_missing_executable_is_unknown(self):
        self.probe.unlink()
        self.assertEqual(self.run_probe().returncode, 127)
        self.assertIn("UNKNOWN runner", self.log.read_text())

    def test_actual_timeout_is_unknown(self):
        self.runner.write_text(self.runner.read_text().replace("limit=40s", "limit=0.1s"))
        self.probe.write_text("#!/bin/bash\nsleep 1\n")
        self.assertEqual(self.run_probe().returncode, 124)
        self.assertIn("UNKNOWN runner", self.log.read_text())

    def test_quick_and_full_share_one_lock_but_sites_are_independent(self):
        with (self.directory / "hs.lock").open("w") as locked:
            fcntl.flock(locked, fcntl.LOCK_EX | fcntl.LOCK_NB)
            self.assertEqual(self.run_probe(mode="quick").returncode, 75)
            self.assertEqual(self.run_probe(mode="full").returncode, 75)
            self.assertEqual(self.run_probe(site="koloda").returncode, 0)
        self.assertIn("WARN runner", self.log.read_text())
        self.assertEqual(self.run_probe().returncode, 0)

    def test_invalid_arguments_do_not_run_a_probe(self):
        self.assertEqual(self.run_probe(site="unrelated").returncode, 2)
        self.assertFalse(self.log.exists())

    def test_cron_has_exactly_one_expected_mode_per_minute(self):
        for site, name in (("hs", "hs-manacost-healthcheck"), ("koloda", "koloda-healthcheck")):
            lines = (ROOT / f"ops/monitoring/{name}.cron").read_text().splitlines()
            covered = []
            for line in lines:
                if not line or line.startswith("#") or "=" in line:
                    continue
                fields = line.split()
                self.assertEqual(fields[5:8], ["root", "/usr/local/libexec/manacost-monitoring/run-healthcheck.sh", site])
                if fields[0] == "*/5":
                    self.assertEqual(fields[8], "full")
                    covered.extend(range(0, 60, 5))
                else:
                    self.assertEqual(fields[8], "quick")
                    for interval in fields[0].split(","):
                        start, end = map(int, interval.split("-"))
                        covered.extend(range(start, end + 1))
            self.assertEqual(sorted(covered), list(range(60)))


if __name__ == "__main__":
    unittest.main()
