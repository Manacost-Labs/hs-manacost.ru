from __future__ import annotations

import json
import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/hs-manacost-s3-offload.php"


class S3RestoreContextTest(unittest.TestCase):
    def context(self, hook="", admin=False, action="", override=False):
        code = f"""
        define('ABSPATH', '/');
        putenv('HS_MANACOST_S3_RESTORE=' . ({json.dumps(override)} ? '1' : ''));
        function add_filter(...$args) {{}}
        function is_admin() {{ return {json.dumps(admin)}; }}
        function doing_action($hook) {{ return $hook === {json.dumps(hook)}; }}
        $_REQUEST['action'] = {json.dumps(action)};
        require {json.dumps(str(PLUGIN))};
        echo json_encode(hs_manacost_s3_restore_context());
        """
        result = subprocess.run([shutil.which("php"), "-r", code], capture_output=True, text=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        return json.loads(result.stdout)

    def test_owned_background_callbacks_can_restore(self):
        for hook in (
            "hs_media_upload_accelerator_generate_subsizes",
            "manacost_media_upload_accelerator_generate_subsizes",
            "hs_local_image_optimizer_process_attachment",
        ):
            with self.subTest(hook=hook):
                self.assertTrue(self.context(hook=hook))

    def test_core_editor_callbacks_can_restore(self):
        for hook in ("wp_ajax_image-editor", "wp_ajax_imgedit-preview"):
            with self.subTest(hook=hook):
                self.assertTrue(self.context(hook=hook, admin=True))

    def test_unrelated_requests_and_spoofed_actions_do_not_restore(self):
        for args in ({}, {"admin": True}, {"hook": "wp_cron"},
                     {"admin": True, "action": "image-editor"},
                     {"admin": True, "hook": "wp_ajax_nopriv_image-editor"}):
            with self.subTest(args=args):
                self.assertFalse(self.context(**args))

    def test_explicit_maintenance_override_is_preserved(self):
        self.assertTrue(self.context(override=True))


if __name__ == "__main__":
    unittest.main()
