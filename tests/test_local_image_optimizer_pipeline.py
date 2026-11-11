"""Speed, queue-safety and admin-load behaviour of the local image optimizer."""

from __future__ import annotations

import hashlib
import json
import os
import shutil
import struct
import subprocess
import tempfile
import time
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BRIDGE = ROOT / "wordpress/mu-plugins/hs-local-image-optimizer.php"
SRC = ROOT / "wordpress/mu-plugins/hs-local-image-optimizer/src"
PHP = shutil.which("php")

WP_STUBS = """
define('ABSPATH', '/');
define('HS_LOCAL_IMAGE_OPTIMIZER_ENABLED', true);
$GLOBALS['options'] = []; $GLOBALS['meta'] = []; $GLOBALS['scheduled'] = []; $GLOBALS['queued'] = [];
function add_filter(...$args) {}
function add_action(...$args) {}
function is_admin() { return false; }
function do_action(...$args) {}
function apply_filters($hook, $value, ...$args) { return $value; }
function get_post_type($id) { return 'attachment'; }
function get_post_mime_type($id) { return 'image/jpeg'; }
function wp_get_post_parent_id($id) { return 0; }
function wp_generate_uuid4() { return 'owned-test-token'; }
function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
function add_option($key, $value, ...$args) {
    if (isset($GLOBALS['options'][$key])) { return false; }
    $GLOBALS['options'][$key] = $value; return true;
}
function delete_option($key) { unset($GLOBALS['options'][$key]); }
function get_post_meta($id, $key, $single = false) { return $GLOBALS['meta'][$key] ?? ''; }
function update_post_meta($id, $key, $value) { $GLOBALS['meta'][$key] = $value; }
function delete_post_meta($id, $key) { unset($GLOBALS['meta'][$key]); }
function wp_json_encode($value, $flags = 0) { return json_encode($value, $flags); }
function as_schedule_single_action($time, $hook, $args, $group, $unique) {
    $GLOBALS['scheduled'][] = ['delay' => $time - time(), 'args' => $args];
}
function as_enqueue_async_action($hook, $args, $group, $unique) { $GLOBALS['queued'][] = $args; }
"""


def run_php(code: str) -> dict:
    result = subprocess.run([PHP, "-r", code], capture_output=True, text=True, timeout=120)
    if result.returncode != 0:
        raise AssertionError(result.stderr or result.stdout)
    return json.loads(result.stdout)


def fake_jpeg(path: Path, width: int, height: int, payload: int) -> None:
    """A header-valid JPEG of a chosen byte size; encoders here are fakes."""
    comment = b"\xff\xfe" + struct.pack(">H", payload + 2) + os.urandom(payload)
    frame = b"\xff\xc0" + struct.pack(">HBHHB", 17, 8, height, width, 3) + bytes(
        [1, 0x11, 0, 2, 0x11, 0, 3, 0x11, 0]
    )
    path.write_bytes(b"\xff\xd8" + comment + frame + b"\xff\xd9")


def fake_encoder(path: Path, candidate: Path, delay: float) -> None:
    """Stand-in for cwebp/avifenc: waits, then writes the last argument."""
    path.write_text(
        f'#!/bin/sh\nsleep {delay}\nfor arg in "$@"; do out=$arg; done\ncp {candidate} "$out"\n',
        encoding="utf-8",
    )
    path.chmod(0o755)


def digest(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


class EncoderCommandTest(unittest.TestCase):
    def test_webp_uses_one_analysis_pass_at_unchanged_quality(self) -> None:
        commands = run_php(
            f"""
            require {json.dumps(str(SRC / 'class-hs-local-image-encoder-command.php'))};
            echo json_encode([
                HS_Local_Image_Encoder_Command::webp('/usr/bin/cwebp', '/a.jpg', '/a.webp', ['quality' => 88, 'lossless' => false]),
                HS_Local_Image_Encoder_Command::webp('/usr/bin/cwebp', '/a.png', '/a.webp', ['quality' => 100, 'lossless' => true]),
                HS_Local_Image_Encoder_Command::avif('/usr/bin/avifenc', '/a.jpg', '/a.avif', ['quantizer' => 24]),
            ]);
            """
        )
        lossy, lossless, avif = commands

        self.assertNotIn("-pass", lossy)
        self.assertEqual(lossy[lossy.index("-q") + 1], "88")
        self.assertEqual(lossy[lossy.index("-m") + 1], "6")
        self.assertIn("-sharp_yuv", lossy)
        self.assertIn("-lossless", lossless)
        # AVIF quality settings are deliberately untouched.
        self.assertEqual(avif[avif.index("--speed") + 1], "4")
        self.assertEqual(avif[avif.index("--min") + 1], "24")
        self.assertEqual(avif[avif.index("--max") + 1], "24")


class ProcessRunnerTest(unittest.TestCase):
    def run_many(self, commands: str, concurrency: int, timeout: int = 30, niceness: int = 0) -> dict:
        return run_php(
            f"""
            require {json.dumps(str(SRC / 'class-hs-local-image-process-runner.php'))};
            $started = hrtime(true);
            $results = HS_Local_Image_Process_Runner::run_many({commands}, {concurrency}, {timeout}, {niceness});
            echo json_encode(['seconds' => (hrtime(true) - $started) / 1e9, 'results' => $results]);
            """
        )

    def test_commands_run_in_parallel_and_keep_their_keys(self) -> None:
        commands = "['a' => ['/bin/sleep', '0.5'], 'b' => ['/bin/sleep', '0.5'], 'c' => ['/bin/sleep', '0.5'], 'd' => ['/bin/sleep', '0.5']]"

        parallel = self.run_many(commands, 4)
        serial = self.run_many(commands, 1)

        self.assertEqual(list(parallel["results"]), ["a", "b", "c", "d"])
        self.assertEqual({r["status"] for r in parallel["results"].values()}, {"success"})
        self.assertLess(parallel["seconds"], 1.5)
        self.assertGreaterEqual(serial["seconds"], 2.0)

    def test_one_broken_command_does_not_fail_the_batch(self) -> None:
        outcome = self.run_many(
            "['ok' => ['/bin/true'], 'exit' => ['/bin/false'], 'missing' => ['/missing/encoder'], 'slow' => ['/bin/sleep', '20']]",
            4,
            timeout=1,
        )
        statuses = {key: result["status"] for key, result in outcome["results"].items()}

        self.assertEqual(
            statuses,
            {"ok": "success", "exit": "failed_exit", "missing": "failed_missing_binary", "slow": "failed_timeout"},
        )
        self.assertLess(outcome["seconds"], 5)

    def test_encoders_can_run_below_web_request_priority(self) -> None:
        normal = self.run_many("['nice' => ['/usr/bin/nice']]", 1)["results"]["nice"]
        lowered = self.run_many("['nice' => ['/usr/bin/nice']]", 1, niceness=10)["results"]["nice"]

        self.assertEqual(lowered["status"], "success")
        self.assertEqual(int(lowered["stdout"]), min(19, int(normal["stdout"]) + 10))


class WorkerBatchTest(unittest.TestCase):
    def test_batch_encodes_in_parallel_without_touching_sources(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            fake_jpeg(root / "webp-candidate", 64, 48, 2000)
            fake_jpeg(root / "avif-candidate", 64, 48, 200)
            fake_encoder(root / "cwebp", root / "webp-candidate", 0.4)
            fake_encoder(root / "avifenc", root / "avif-candidate", 0.4)
            sources = []
            for index in range(6):
                source = root / f"photo-{index}.jpg"
                fake_jpeg(source, 64, 48, 20000)
                sources.append(source)
            before = {str(source): digest(source) for source in sources}
            missing = str(root / "missing.jpg")

            outcome = run_php(
                f"""
                foreach (['ProfileSelector', 'CandidatePublisher', 'class-hs-local-image-encoder-command', 'class-hs-local-image-process-runner', 'class-hs-local-image-worker'] as $file) {{
                    require {json.dumps(str(SRC))} . '/' . $file . '.php';
                }}
                $worker = new HS_Local_Image_Worker({json.dumps(str(root / 'cwebp'))}, {json.dumps(str(root / 'avifenc'))}, 30);
                $sources = json_decode({json.dumps(json.dumps([*before, missing]))}, true);
                $started = hrtime(true);
                $results = $worker->process_many($sources, ['post_type' => 'post']);
                $seconds = (hrtime(true) - $started) / 1e9;
                $again = $worker->process_many($sources, ['post_type' => 'post']);
                echo json_encode(['seconds' => $seconds, 'results' => $results, 'again' => $again]);
                """
            )

            # Twelve 0.4 s encodes take 4.8 s one by one.
            self.assertLess(outcome["seconds"], 3.0)
            self.assertEqual(list(outcome["results"]), [*before, missing])
            for source in before:
                result = outcome["results"][source]
                self.assertEqual(result["profile"], "photo_art")
                self.assertEqual(result["webp"]["status"], "published")
                self.assertEqual(result["avif"]["status"], "published")
                self.assertEqual(outcome["again"][source]["webp"]["status"], "skipped_up_to_date")
                self.assertEqual(outcome["again"][source]["avif"]["status"], "skipped_up_to_date")
                self.assertEqual(digest(Path(source)), before[source])
                self.assertTrue(Path(source + ".webp").is_file())
                self.assertTrue(Path(source + ".avif").is_file())
            self.assertEqual(outcome["results"][missing], {"status": "failed_missing_source"})
            self.assertEqual(list(root.glob("*.tmp.*")), [])

    def test_avif_must_still_beat_the_published_webp(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            fake_jpeg(root / "candidate", 64, 48, 2000)
            fake_encoder(root / "cwebp", root / "candidate", 0)
            fake_encoder(root / "avifenc", root / "candidate", 0)
            source = root / "photo.jpg"
            fake_jpeg(source, 64, 48, 20000)

            result = run_php(
                f"""
                foreach (['ProfileSelector', 'CandidatePublisher', 'class-hs-local-image-encoder-command', 'class-hs-local-image-process-runner', 'class-hs-local-image-worker'] as $file) {{
                    require {json.dumps(str(SRC))} . '/' . $file . '.php';
                }}
                $worker = new HS_Local_Image_Worker({json.dumps(str(root / 'cwebp'))}, {json.dumps(str(root / 'avifenc'))}, 30);
                echo json_encode($worker->process({json.dumps(str(source))}, ['post_type' => 'post']));
                """
            )

            self.assertEqual(result["webp"]["status"], "published")
            self.assertEqual(result["avif"]["status"], "skipped_not_smaller")
            self.assertFalse(Path(str(source) + ".avif").exists())


class QueueSafetyTest(unittest.TestCase):
    def run_bridge(self, body: str) -> dict:
        return run_php(
            f"""
            {WP_STUBS}
            function get_attached_file($id, $unfiltered = false) {{ return $GLOBALS['attached_file'] ?? '/test/missing.jpg'; }}
            function wp_get_attachment_metadata($id) {{ return ['sizes' => []]; }}
            require {json.dumps(str(BRIDGE))};
            {body}
            echo json_encode([
                'scheduled' => $GLOBALS['scheduled'],
                'queued' => $GLOBALS['queued'],
                'meta' => $GLOBALS['meta'],
                'result' => json_decode($GLOBALS['meta']['_hs_local_image_optimizer_result'] ?? 'null', true),
            ]);
            """
        )

    def test_busy_worker_never_consumes_the_failure_budget(self) -> None:
        state = self.run_bridge(
            """
            $GLOBALS['options']['hs_local_image_optimizer_worker_lock'] = ['token' => 'other', 'created_at' => time()];
            $GLOBALS['meta']['_hs_local_image_optimizer_queued_at'] = time() - 600;
            HS_Local_Image_Optimizer_WordPress::process_attachment(42, 4);
            """
        )

        # Attempt 4 used to end as failed_retries: worker_busy.
        self.assertIsNone(state["result"])
        self.assertEqual(state["scheduled"], [{"delay": 30, "args": [42, 4]}])

    def test_busy_wait_is_bounded(self) -> None:
        state = self.run_bridge(
            """
            $GLOBALS['options']['hs_local_image_optimizer_worker_lock'] = ['token' => 'other', 'created_at' => time()];
            $GLOBALS['meta']['_hs_local_image_optimizer_queued_at'] = time() - 7300;
            HS_Local_Image_Optimizer_WordPress::process_attachment(42, 0);
            """
        )

        self.assertEqual(state["scheduled"], [])
        self.assertEqual(state["result"], {"status": "failed_retries", "reason": "worker_busy"})

    def recover(self, setup: str) -> dict:
        return self.run_bridge(
            f"""
            function as_next_scheduled_action($hook, $args, $group) {{ return $GLOBALS['pending'] ?? false; }}
            $GLOBALS['wpdb'] = new class {{
                public string $postmeta = 'wp_postmeta';
                public function prepare(string $query, ...$args): string {{ return $query; }}
                public function get_col(string $query): array {{ return ['42']; }}
            }};
            {setup}
            HS_Local_Image_Optimizer_WordPress::requeue_lost_attachments();
            """
        )

    def test_lost_job_is_queued_again(self) -> None:
        state = self.recover("")

        self.assertEqual(state["queued"], [[42, 0]])
        self.assertEqual(state["meta"]["_hs_local_image_optimizer_requeues"], 1)
        self.assertIsNone(state["result"])

    def test_lock_of_a_killed_worker_does_not_block_recovery(self) -> None:
        state = self.recover(
            "$GLOBALS['options']['hs_local_image_optimizer_worker_lock'] = ['token' => 'dead', 'created_at' => time() - 901];"
        )

        self.assertEqual(state["queued"], [[42, 0]])

    def test_expired_lock_is_taken_over_by_the_next_job(self) -> None:
        state = self.run_bridge(
            """
            $GLOBALS['options']['hs_local_image_optimizer_worker_lock'] = ['token' => 'dead', 'created_at' => time() - 901];
            HS_Local_Image_Optimizer_WordPress::process_attachment(42, 0);
            """
        )

        # The job ran (missing source -> ordinary retry) instead of waiting.
        self.assertEqual(state["scheduled"], [{"delay": 60, "args": [42, 1]}])

    def test_lost_job_recovery_is_bounded(self) -> None:
        state = self.recover("$GLOBALS['meta']['_hs_local_image_optimizer_requeues'] = 3;")

        self.assertEqual(state["queued"], [])
        self.assertEqual(state["result"], {"status": "failed_retries", "reason": "job_lost"})

    def test_waiting_jobs_are_not_duplicated_by_recovery(self) -> None:
        for setup in (
            "$GLOBALS['pending'] = true;",
            "$GLOBALS['options']['hs_local_image_optimizer_worker_lock'] = ['token' => 'other', 'created_at' => time()];",
        ):
            with self.subTest(setup=setup):
                state = self.recover(setup)
                self.assertEqual(state["queued"], [])
                self.assertNotIn("_hs_local_image_optimizer_requeues", state["meta"])


class DeploymentSafetyTest(unittest.TestCase):
    def test_half_deployed_plugin_stays_inactive_instead_of_failing(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            shutil.copy(BRIDGE, root / BRIDGE.name)
            shutil.copytree(SRC, root / "hs-local-image-optimizer/src")
            (root / "hs-local-image-optimizer/src/class-hs-local-image-worker.php").unlink()

            state = run_php(
                f"""
                define('ABSPATH', '/');
                $GLOBALS['hooks'] = 0;
                function add_filter(...$args) {{ $GLOBALS['hooks']++; }}
                function add_action(...$args) {{ $GLOBALS['hooks']++; }}
                function is_admin() {{ return false; }}
                require {json.dumps(str(root / BRIDGE.name))};
                echo json_encode(['hooks' => $GLOBALS['hooks'], 'worker' => class_exists('HS_Local_Image_Worker', false)]);
                """
            )

        self.assertEqual(state, {"hooks": 0, "worker": False})


class AdminLoadTest(unittest.TestCase):
    ADMIN_STUBS = """
    function esc_html($text) { return htmlspecialchars((string) $text); }
    function size_format($bytes, $decimals = 0) { return $bytes . ' B'; }
    function number_format_i18n($number, $decimals = 0) { return number_format((float) $number, $decimals); }
    """

    def test_finished_attachment_stores_its_sizes(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            source = Path(directory) / "cover.jpg"
            fake_jpeg(source, 64, 48, 20000)
            fake_jpeg(Path(str(source) + ".webp"), 64, 48, 2000)

            state = run_php(
                f"""
                {WP_STUBS}
                function get_attached_file($id, $unfiltered = false) {{ return {json.dumps(str(source))}; }}
                function wp_get_attachment_metadata($id) {{ return ['sizes' => []]; }}
                require {json.dumps(str(BRIDGE))};
                $finish = new ReflectionMethod('HS_Local_Image_Optimizer_WordPress', 'finish');
                $GLOBALS['meta']['_hs_local_image_optimizer_requeues'] = 2;
                $finish->invoke(null, 42, ['status' => 'processed']);
                echo json_encode($GLOBALS['meta']);
                """
            )

        stats = state["_hs_local_image_optimizer_stats"]
        self.assertEqual(stats["status"], "available")
        self.assertEqual(stats["primary"]["webp_files"], 1)
        self.assertEqual(stats["primary"]["webp_saved_percent"], 89.9)
        self.assertNotIn("_hs_local_image_optimizer_requeues", state)

    def test_media_list_reads_stored_sizes_without_the_filesystem(self) -> None:
        stored = {
            "status": "available", "files": 1, "source_bytes": 1000,
            "webp_bytes": 400, "webp_files": 1, "webp_saved_bytes": 600, "webp_saved_percent": 60.0,
            "avif_bytes": 400, "avif_files": 0, "avif_saved_bytes": 600, "avif_saved_percent": 60.0,
        }
        stored["primary"] = dict(stored)

        html = run_php(
            f"""
            {WP_STUBS}
            {self.ADMIN_STUBS}
            function get_attached_file($id, $unfiltered = false) {{ throw new RuntimeException('filesystem was read'); }}
            function wp_get_attachment_metadata($id) {{ throw new RuntimeException('metadata was read'); }}
            require {json.dumps(str(BRIDGE))};
            $GLOBALS['meta']['_hs_local_image_optimizer_stats'] = json_decode({json.dumps(json.dumps(stored))}, true);
            ob_start();
            HS_Local_Image_Admin_Media_UI::render_column('hs_image_savings', 42);
            echo json_encode(['html' => ob_get_clean()]);
            """
        )["html"]

        self.assertIn("WebP 400 B", html)
        self.assertIn("60.0%", html)

    def test_unmeasured_attachment_is_measured_once_and_stored(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            source = Path(directory) / "legacy.jpg"
            fake_jpeg(source, 64, 48, 20000)

            state = run_php(
                f"""
                {WP_STUBS}
                {self.ADMIN_STUBS}
                function get_attached_file($id, $unfiltered = false) {{ return {json.dumps(str(source))}; }}
                function wp_get_attachment_metadata($id) {{ return ['sizes' => []]; }}
                require {json.dumps(str(BRIDGE))};
                ob_start();
                HS_Local_Image_Admin_Media_UI::render_column('hs_image_savings', 42);
                ob_end_clean();
                echo json_encode($GLOBALS['meta']);
                """
            )

        self.assertEqual(state["_hs_local_image_optimizer_stats"]["status"], "available")

    def test_badge_repaints_are_coalesced_per_frame(self) -> None:
        source = (SRC / "class-hs-local-image-admin-media-ui.php").read_text(encoding="utf-8")

        self.assertIn("requestAnimationFrame", source)
        self.assertIn("new MutationObserver(schedulePaint)", source)
        self.assertNotIn("new MutationObserver(paintSavingsBadges)", source)


if __name__ == "__main__":
    unittest.main()
