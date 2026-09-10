from __future__ import annotations

import json
import shutil
import subprocess
import tempfile
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/hs-media-upload-accelerator.php"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"
REQUEST_HELPERS = """
function wp_unslash($value) { return $value; }
function sanitize_text_field($value) { return (string) $value; }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\\-]/', '', strtolower((string) $value)); }
"""


class MediaUploadAcceleratorTest(unittest.TestCase):
    def test_worker_does_not_reuse_cached_imagick_from_another_php_runtime(self) -> None:
        script = f"""
        define('ABSPATH', '/');
        $filters = [];
        function add_filter($tag, $callback, $priority = 10, ...$args) {{ $GLOBALS['filters'][$tag][$priority][] = $callback; }}
        function remove_filter($tag, $callback, $priority = 10) {{
            foreach ($GLOBALS['filters'][$tag][$priority] ?? [] as $key => $registered) {{
                if ($callback === $registered) {{ unset($GLOBALS['filters'][$tag][$priority][$key]); }}
            }}
        }}
        function apply_editor_filters($editors) {{
            $filters = $GLOBALS['filters']['wp_image_editors'] ?? [];
            ksort($filters);
            foreach ($filters as $callbacks) {{ foreach ($callbacks as $callback) {{ $editors = $callback($editors); }} }}
            return $editors;
        }}
        function add_action(...$args) {{}}
        function wp_attachment_is_image($id) {{ return true; }}
        function is_wp_error($value) {{ return false; }}
        function as_schedule_single_action($time, $hook, $args, ...$rest) {{ $GLOBALS['retry'] = $args; }}
        function wp_update_image_subsizes($id) {{
            $defaults = ['WP_Image_Editor_Imagick', 'WP_Image_Editor_GD'];
            // Core keys its cached choice by args and the filtered implementation list.
            $cache = [serialize($defaults) => 'WP_Image_Editor_Imagick'];
            $editors = apply_editor_filters($defaults);
            $editor = $cache[serialize($editors)] ?? $editors[0];
            if ($editor === 'WP_Image_Editor_Imagick') {{ throw new Error('Class Imagick not found'); }}
            $GLOBALS['selected'] = $editor;
            $GLOBALS['custom'] = apply_editor_filters(['Vendor_Editor', ...$defaults]);
            if ($id === 43) {{ throw new RuntimeException('Unrelated resize error'); }}
            return ['sizes' => ['large' => []]];
        }}
        class HS_Local_Image_Optimizer_WordPress {{ public static function queue_attachment($id) {{ $GLOBALS['optimized'][] = $id; }} }}
        require {json.dumps(str(PLUGIN))};
        HS_Media_Upload_Accelerator::generate_deferred_subsizes(42);
        $afterSuccess = apply_editor_filters(['WP_Image_Editor_Imagick', 'WP_Image_Editor_GD']);
        HS_Media_Upload_Accelerator::generate_deferred_subsizes(43);
        echo json_encode([
            'imagick_loaded' => extension_loaded('imagick'),
            'selected' => $GLOBALS['selected'] ?? null,
            'optimized' => $GLOBALS['optimized'] ?? [],
            'retry' => $GLOBALS['retry'] ?? null,
            'custom' => $GLOBALS['custom'] ?? [],
            'after_success' => $afterSuccess,
            'after_error' => apply_editor_filters(['WP_Image_Editor_Imagick', 'WP_Image_Editor_GD']),
        ]);
        """
        completed = subprocess.run([PHP_BINARY, '-n', '-r', script], capture_output=True, text=True)
        self.assertEqual(completed.returncode, 0, completed.stderr)
        self.assertEqual(json.loads(completed.stdout), {
            'imagick_loaded': False,
            'selected': 'WP_Image_Editor_GD',
            'optimized': [42],
            'retry': [43, 1],
            'custom': ['Vendor_Editor', 'WP_Image_Editor_GD'],
            'after_success': ['WP_Image_Editor_Imagick', 'WP_Image_Editor_GD'],
            'after_error': ['WP_Image_Editor_Imagick', 'WP_Image_Editor_GD'],
        })

    def test_silent_partial_size_failure_is_retried_without_optimizer_handoff(self) -> None:
        result = self.run_php(f"""
        define('ABSPATH', '/');
        function add_filter(...$args) {{}}
        function add_action(...$args) {{}}
        function wp_attachment_is_image($id) {{ return true; }}
        function wp_update_image_subsizes($id) {{ return ['sizes' => []]; }}
        function wp_get_missing_image_subsizes($id) {{ return ['large' => []]; }}
        function is_wp_error($value) {{ return false; }}
        function as_schedule_single_action($time, $hook, $args, $group, $unique) {{ $GLOBALS['retry'] = $args; }}
        class HS_Local_Image_Optimizer_WordPress {{ public static function queue_attachment($id) {{ $GLOBALS['optimized'] = true; }} }}
        require {json.dumps(str(PLUGIN))};
        HS_Media_Upload_Accelerator::generate_deferred_subsizes(42);
        echo json_encode(['retry' => $GLOBALS['retry'] ?? null, 'optimized' => $GLOBALS['optimized'] ?? false]);
        """)
        self.assertEqual(result, {'retry': [42, 1], 'optimized': False})

    def run_php(self, script: str) -> dict | list[str] | bool:
        script = "function remove_filter(...$args) {}\n" + script
        completed = subprocess.run(
            [PHP_BINARY, "-r", script],
            check=False,
            capture_output=True,
            text=True,
        )
        self.assertEqual(completed.returncode, 0, completed.stderr)
        return json.loads(completed.stdout)

    def test_newspaper_upload_keeps_editor_preview_sizes_and_defers_the_rest(self) -> None:
        script = f"""
        define('ABSPATH', '/');
        {REQUEST_HELPERS}
        function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {{}}
        function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {{}}
        function wp_doing_ajax() {{ return false; }}
        require {json.dumps(str(PLUGIN))};
        $_SERVER['SCRIPT_FILENAME'] = '/srv/www/wp-admin/async-upload.php';
        $sizes = [
            'thumbnail' => [],
            'medium' => [],
            'large' => [],
            'td_485x360' => [],
            'td_696x0' => [],
            'td_1068x0' => [],
            'td_218x150' => [],
            'td_741x486' => [],
        ];
        echo json_encode(array_keys(HS_Media_Upload_Accelerator::filter_sizes($sizes, [], 42)));
        """
        result = self.run_php(script)
        self.assertEqual(
            result,
            ["thumbnail", "medium"],
        )

    def test_non_upload_metadata_generation_keeps_every_registered_size(self) -> None:
        script = f"""
        define('ABSPATH', '/');
        {REQUEST_HELPERS}
        function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {{}}
        function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {{}}
        function wp_doing_ajax() {{ return false; }}
        require {json.dumps(str(PLUGIN))};
        $_SERVER['SCRIPT_FILENAME'] = '/srv/www/wp-admin/post.php';
        $sizes = ['thumbnail' => [], 'td_218x150' => [], 'td_741x486' => []];
        echo json_encode(array_keys(HS_Media_Upload_Accelerator::filter_sizes($sizes, [], 42)));
        """
        self.assertEqual(
            self.run_php(script),
            ["thumbnail", "td_218x150", "td_741x486"],
        )

    def test_burst_upload_queues_each_deferred_attachment_without_global_uniqueness(self) -> None:
        script = f"""
        define('ABSPATH', '/');
        {REQUEST_HELPERS}
        $queued = [];
        function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {{}}
        function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {{}}
        function wp_doing_ajax() {{ return false; }}
        function wp_attachment_is_image($id) {{ return true; }}
        function wp_get_registered_image_subsizes() {{ return [
            'thumbnail' => [],
            'td_696x0' => [],
            'td_218x150' => [],
        ]; }}
        function as_enqueue_async_action($hook, $args, $group, $unique) {{
            $GLOBALS['queued'][] = [$hook, $args, $group, $unique];
            return count($GLOBALS['queued']);
        }}
        require {json.dumps(str(PLUGIN))};
        $_SERVER['SCRIPT_FILENAME'] = '/srv/www/wp-admin/async-upload.php';
        $metadata = ['sizes' => ['thumbnail' => [], 'td_696x0' => []]];
        HS_Media_Upload_Accelerator::queue_after_metadata($metadata, 42, 'create');
        HS_Media_Upload_Accelerator::queue_after_metadata($metadata, 43, 'create');
        HS_Media_Upload_Accelerator::flush_pending_queue();
        echo json_encode($GLOBALS['queued']);
        """
        result = self.run_php(script)
        self.assertEqual(len(result), 2)
        self.assertEqual([action[1][0] for action in result], [42, 43])
        self.assertTrue(all(action[3] is False for action in result))

    def test_deferred_worker_hands_complete_attachment_to_local_optimizer(self) -> None:
        script = f"""
        define('ABSPATH', '/');
        {REQUEST_HELPERS}
        function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {{}}
        function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {{}}
        function wp_doing_ajax() {{ return false; }}
        function wp_attachment_is_image($id) {{ return true; }}
        function wp_update_image_subsizes($id) {{
            $GLOBALS['optimizer_decision_during_subsizes'] =
                HS_Media_Upload_Accelerator::filter_local_optimizer_queue(true, [], $id, 'update');
            return ['sizes' => ['td_218x150' => []]];
        }}
        function is_wp_error($value) {{ return false; }}
        final class HS_Local_Image_Optimizer_WordPress {{
            public static function queue_attachment($id) {{ $GLOBALS['optimized'][] = $id; }}
        }}
        require {json.dumps(str(PLUGIN))};
        HS_Media_Upload_Accelerator::generate_deferred_subsizes(42);
        echo json_encode([
            'optimizer_decision_during_subsizes' => $GLOBALS['optimizer_decision_during_subsizes'] ?? null,
            'optimized' => $GLOBALS['optimized'] ?? [],
        ]);
        """
        self.assertEqual(
            self.run_php(script),
            {"optimizer_decision_during_subsizes": False, "optimized": [42]},
        )

    def test_deferred_worker_loads_core_image_helper_before_optimizer_handoff(self) -> None:
        with tempfile.TemporaryDirectory() as temp_dir:
            helper = Path(temp_dir) / "wp-admin/includes/image.php"
            helper.parent.mkdir(parents=True)
            helper.write_text(
                "<?php\n"
                "$GLOBALS['image_helper_loaded'] = true;\n"
                "function wp_update_image_subsizes($id) { return ['sizes' => ['td_218x150' => []]]; }\n",
                encoding="utf-8",
            )
            script = f"""
            define('ABSPATH', {json.dumps(str(Path(temp_dir)) + '/')});
            {REQUEST_HELPERS}
            function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {{}}
            function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {{}}
            function wp_doing_ajax() {{ return false; }}
            function wp_attachment_is_image($id) {{ return true; }}
            function is_wp_error($value) {{ return false; }}
            final class HS_Local_Image_Optimizer_WordPress {{
                public static function queue_attachment($id) {{ $GLOBALS['optimized'][] = $id; }}
            }}
            require {json.dumps(str(PLUGIN))};
            HS_Media_Upload_Accelerator::generate_deferred_subsizes(42);
            echo json_encode([
                'helper_loaded' => $GLOBALS['image_helper_loaded'] ?? false,
                'optimized' => $GLOBALS['optimized'] ?? [],
            ]);
            """
            self.assertEqual(
                self.run_php(script),
                {"helper_loaded": True, "optimized": [42]},
            )

    def test_deferred_worker_retries_when_image_editor_throws(self) -> None:
        script = f"""
        define('ABSPATH', '/');
        {REQUEST_HELPERS}
        $scheduled = [];
        function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {{}}
        function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {{}}
        function wp_doing_ajax() {{ return false; }}
        function wp_attachment_is_image($id) {{ return true; }}
        function wp_update_image_subsizes($id) {{ throw new RuntimeException('image editor unavailable'); }}
        function as_schedule_single_action($timestamp, $hook, $args, $group, $unique) {{
            $GLOBALS['scheduled'][] = [$hook, $args, $group, $unique];
            return 1;
        }}
        require {json.dumps(str(PLUGIN))};
        HS_Media_Upload_Accelerator::generate_deferred_subsizes(42);
        echo json_encode([
            'scheduled' => $GLOBALS['scheduled'],
            'optimizer_is_reenabled' => HS_Media_Upload_Accelerator::filter_local_optimizer_queue(
                true,
                [],
                42,
                'update'
            ),
        ]);
        """
        self.assertEqual(
            self.run_php(script),
            {
                "scheduled": [
                    [
                        "hs_media_upload_accelerator_generate_subsizes",
                        [42, 1],
                        "hs-media-upload-accelerator",
                        False,
                    ]
                ],
                "optimizer_is_reenabled": True,
            },
        )

    def test_deferred_worker_records_final_image_editor_failure(self) -> None:
        script = f"""
        define('ABSPATH', '/');
        {REQUEST_HELPERS}
        function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {{}}
        function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {{}}
        function wp_doing_ajax() {{ return false; }}
        function wp_attachment_is_image($id) {{ return true; }}
        function wp_update_image_subsizes($id) {{ throw new RuntimeException('image editor unavailable'); }}
        function update_post_meta($id, $key, $message) {{ $GLOBALS['recorded'] = [$id, $key, $message]; }}
        require {json.dumps(str(PLUGIN))};
        HS_Media_Upload_Accelerator::generate_deferred_subsizes(42, 2);
        echo json_encode($GLOBALS['recorded'] ?? null);
        """
        self.assertEqual(
            self.run_php(script),
            [42, "_hs_media_upload_accelerator_error", "image editor unavailable"],
        )


if __name__ == "__main__":
    unittest.main()
