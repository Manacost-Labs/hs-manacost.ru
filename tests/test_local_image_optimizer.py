from __future__ import annotations

import json
import shutil
import subprocess
import tempfile
import unittest
from pathlib import Path


PLUGIN = Path(__file__).resolve().parents[1] / "wordpress/mu-plugins/hs-local-image-optimizer.php"


class OptimizerRecoveryTest(unittest.TestCase):
    def test_nested_encoder_failure_is_not_success(self):
        code = f"""
        define('ABSPATH', '/');
        function add_filter(...$args) {{}}
        function add_action(...$args) {{}}
        function is_admin() {{ return false; }}
        require {json.dumps(str(PLUGIN))};
        $method = new ReflectionMethod('HS_Local_Image_Optimizer_WordPress', 'has_processing_failure');
        echo json_encode([
          $method->invoke(null, ['image.jpg' => ['status' => 'processed', 'webp' => ['status' => 'failed_timeout']]]),
          $method->invoke(null, ['image.jpg' => ['status' => 'processed', 'avif' => ['status' => 'failed_exit']]]),
          $method->invoke(null, ['image.png' => ['status' => 'processed', 'webp' => ['status' => 'skipped_up_to_date'], 'avif' => ['status' => 'skipped_profile']]]),
        ]);
        """
        result = subprocess.run([shutil.which('php'), '-r', code], capture_output=True, text=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(json.loads(result.stdout), [True, True, False])

    def run_worker(self, source: str, attempt=0, source_filter="return $file;", metadata=None, mime="image/png"):
        code = f"""
        define('ABSPATH', '/');
        define('HS_LOCAL_IMAGE_OPTIMIZER_ENABLED', true);
        function add_filter(...$args) {{}}
        function add_action(...$args) {{}}
        function is_admin() {{ return false; }}
        function do_action(...$args) {{}}
        function wp_attachment_is_image($id) {{ return true; }}
        function get_post_type($id) {{ return 'attachment'; }}
        function get_post_mime_type($id) {{ return {json.dumps(mime)}; }}
        function get_attached_file($id, $unfiltered=false) {{ return {json.dumps(source)}; }}
        function wp_get_attachment_metadata($id) {{ return json_decode({json.dumps(json.dumps(metadata or {"sizes": {}}))}, true); }}
        function wp_get_post_parent_id($id) {{ return 0; }}
        function wp_generate_uuid4() {{ return 'owned-test-token'; }}
        function get_option($key, $default=false) {{ return $GLOBALS['options'][$key] ?? $default; }}
        function add_option($key, $value, ...$args) {{ $GLOBALS['options'][$key] = $value; return true; }}
        function delete_option($key) {{ unset($GLOBALS['options'][$key]); }}
        function update_post_meta($id, $key, $value) {{ $GLOBALS['meta'][$key] = $value; }}
        function wp_json_encode($value, $flags=0) {{ return json_encode($value, $flags); }}
        function as_schedule_single_action($time, $hook, $args, $group, $unique) {{ $GLOBALS['retry'] = $args; }}
        function apply_filters($hook, $file, ...$args) {{ {source_filter} }}
        require {json.dumps(str(PLUGIN))};
        HS_Local_Image_Optimizer_WordPress::process_attachment(42, {attempt});
        echo json_encode([
            'retry' => $GLOBALS['retry'] ?? null,
            'result' => json_decode($GLOBALS['meta']['_hs_local_image_optimizer_result'] ?? 'null', true),
            'lock' => $GLOBALS['options']['hs_local_image_optimizer_worker_lock'] ?? null,
        ]);
        """
        result = subprocess.run([shutil.which("php"), "-r", code], capture_output=True, text=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        return json.loads(result.stdout)

    def test_missing_source_retries_without_recording_success(self):
        with tempfile.TemporaryDirectory() as directory:
            result = self.run_worker(str(Path(directory) / "offloaded.png"))
        self.assertEqual(result, {"retry": [42, 1], "result": None, "lock": None})

    def test_exhausted_failure_preserves_per_file_evidence(self):
        with tempfile.TemporaryDirectory() as directory:
            result = self.run_worker(str(Path(directory) / "offloaded.png"), attempt=4)
        self.assertIsNone(result["retry"])
        self.assertIsNone(result["lock"])
        self.assertEqual(result["result"]["status"], "failed_retries")
        self.assertEqual(result["result"]["files"]["offloaded.png"]["status"], "failed_missing_source")

    def test_source_hook_cannot_redirect_to_another_file(self):
        result = self.run_worker("/test/missing.png", attempt=4, source_filter="return '/other/image.png';")
        self.assertEqual(result["result"]["files"]["missing.png"]["status"], "failed_source_path")

    def test_source_restoration_exception_retries_and_releases_lock(self):
        result = self.run_worker("/test/missing.png", source_filter="throw new RuntimeException('private failure');")
        self.assertEqual(result, {"retry": [42, 1], "result": None, "lock": None})

    def test_converted_webp_uses_original_jpeg_without_recursive_sidecars(self):
        result = self.run_worker("/test/image.webp", mime="image/webp", metadata={"original_image": "image.jpg"})
        self.assertEqual(result, {"retry": [42, 1], "result": None, "lock": None})


if __name__ == "__main__":
    unittest.main()
