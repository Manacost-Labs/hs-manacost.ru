from __future__ import annotations

import os
import subprocess
import tempfile
import time
import unittest
from pathlib import Path


CLEANUP = Path(__file__).resolve().parents[1] / "ops/s3-offload/verified_cleanup.py"


class VerifiedCleanupTest(unittest.TestCase):
    def test_deletion_requires_both_readable_matching_copies(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            source = root / "uploads"
            image = source / "2026/09/image.png"
            image.parent.mkdir(parents=True)
            image.write_bytes(b"source image")
            aged = time.time() - 9 * 86400
            os.utime(image, (aged, aged))

            primary = root / "primary"
            backup = root / "backup"
            for remote, content in ((primary, b"source image"), (backup, b"wrong image")):
                target = remote / "2026/09/image.png"
                target.parent.mkdir(parents=True)
                target.write_bytes(content)

            rclone = root / "rclone"
            rclone.write_text(
                "#!/usr/bin/env python3\n"
                "import pathlib, shutil, sys\n"
                "command, source = sys.argv[1:3]\n"
                "if command == 'cat':\n"
                "    sys.stdout.buffer.write(pathlib.Path(source).read_bytes())\n"
                "elif command == 'copyto':\n"
                "    shutil.copyfile(source, sys.argv[3])\n"
                "else:\n"
                "    sys.exit(2)\n"
            )
            rclone.chmod(0o755)
            environment = os.environ.copy()
            environment.update(
                HS_S3_DELETE_LOCAL="1",
                HS_S3_RCLONE_BIN=str(rclone),
                HS_S3_UPLOADS_DIR=str(source),
                HS_S3_WEBPC_DIR=str(root / "empty-webpc"),
                HS_S3_REMOTE_UPLOADS=str(primary),
                HS_S3_BACKUP_UPLOADS=str(backup),
            )

            def run_cleanup():
                return subprocess.run(
                    ["python3", str(CLEANUP)], env=environment, capture_output=True, text=True, timeout=20
                )

            result = run_cleanup()
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertTrue(image.exists(), "a mismatching backup must retain the local source")

            (backup / "2026/09/image.png").write_bytes(b"source image")
            (primary / "2026/09/image.png").write_bytes(b"wrong image")
            result = run_cleanup()
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertTrue(image.exists(), "a mismatching primary must retain the local source")

            (primary / "2026/09/image.png").write_bytes(b"source image")
            environment["HS_S3_DELETE_LOCAL"] = "0"
            result = run_cleanup()
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertTrue(image.exists(), "the cleanup switch must default to retaining files")

            environment["HS_S3_DELETE_LOCAL"] = "1"
            result = run_cleanup()
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertFalse(image.exists(), "a byte-exact primary and restored backup permit release")


if __name__ == "__main__":
    unittest.main()
