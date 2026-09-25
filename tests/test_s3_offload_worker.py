from __future__ import annotations

import json
import os
import subprocess
import tempfile
import unittest
from pathlib import Path


WORKER = Path(__file__).resolve().parents[1] / "ops/s3-offload/worker.sh"


class S3OffloadWorkerTest(unittest.TestCase):
    def test_uploaded_images_remain_local_after_copy(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            uploads = root / "uploads"
            webpc = root / "uploads-webpc"
            primary = root / "primary"
            secondary = root / "secondary"
            for source, name in ((uploads, "original.jpg"), (webpc, "variant.webp")):
                image = source / "2026/09" / name
                image.parent.mkdir(parents=True)
                image.write_bytes(b"test image bytes")

            credentials = root / "credentials.json"
            credentials.write_text(json.dumps({"access_key": "test", "secret_key": "test", "endpoint": "example.test"}))
            rclone = root / "rclone"
            rclone.write_text(
                "#!/usr/bin/env python3\n"
                "import pathlib, shutil, sys\n"
                "operation, source, destination = sys.argv[1:4]\n"
                "destination = pathlib.Path(destination)\n"
                "for image in pathlib.Path(source).rglob('*'):\n"
                "    if image.is_file():\n"
                "        target = destination / image.relative_to(source)\n"
                "        target.parent.mkdir(parents=True, exist_ok=True)\n"
                "        shutil.copy2(image, target)\n"
                "        if operation == 'move':\n"
                "            image.unlink()\n"
            )
            rclone.chmod(0o755)
            environment = os.environ.copy()
            environment.update(
                HS_S3_CREDENTIALS_FILE=str(credentials),
                HS_S3_RCLONE_BIN=str(rclone),
                HS_S3_UPLOADS_DIR=str(uploads),
                HS_S3_WEBPC_DIR=str(webpc),
                HS_S3_REMOTE_UPLOADS=str(primary),
                HS_S3_REMOTE_WEBPC=str(secondary),
                HS_S3_BACKUP_UPLOADS=str(root / "backup-uploads"),
                HS_S3_BACKUP_WEBPC=str(root / "backup-webpc"),
                HS_S3_HEALTHCHECK_URL="",
                HS_S3_LOCK_FILE=str(root / "worker.lock"),
                HS_S3_MIN_AGE="0s",
            )
            result = subprocess.run([str(WORKER)], env=environment, capture_output=True, text=True, timeout=20)
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertEqual((uploads / "2026/09/original.jpg").read_bytes(), b"test image bytes")
            self.assertEqual((webpc / "2026/09/variant.webp").read_bytes(), b"test image bytes")
            self.assertEqual((primary / "2026/09/original.jpg").read_bytes(), b"test image bytes")
            self.assertEqual((secondary / "2026/09/variant.webp").read_bytes(), b"test image bytes")
            self.assertEqual((root / "backup-uploads/2026/09/original.jpg").read_bytes(), b"test image bytes")
            self.assertEqual((root / "backup-webpc/2026/09/variant.webp").read_bytes(), b"test image bytes")


if __name__ == "__main__":
    unittest.main()
