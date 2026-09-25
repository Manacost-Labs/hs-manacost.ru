#!/usr/bin/env python3
"""Release aged local media only after both S3 copies can be read back."""

from __future__ import annotations

import hashlib
import os
import subprocess
import sys
import tempfile
import time
from pathlib import Path


EXTENSIONS = {".avif", ".bmp", ".gif", ".heic", ".heif", ".ico", ".jpeg", ".jpg", ".png", ".svg", ".tif", ".tiff", ".webp"}


def digest_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def digest_remote(rclone: str, remote: str) -> str | None:
    digest = hashlib.sha256()
    with subprocess.Popen(
        [rclone, "cat", remote, "--contimeout", "10s", "--timeout", "60s", "--retries", "2"],
        stdout=subprocess.PIPE,
        stderr=subprocess.DEVNULL,
    ) as process:
        if process.stdout is None:
            return None
        for chunk in iter(lambda: process.stdout.read(1024 * 1024), b""):
            digest.update(chunk)
        if process.wait() != 0:
            return None
    return digest.hexdigest()


def eligible_files(directory: Path, minimum_age: int):
    cutoff = time.time() - minimum_age
    for root, folders, files in os.walk(directory, followlinks=False):
        folders[:] = [name for name in folders if not (Path(root) / name).is_symlink()]
        for name in files:
            path = Path(root) / name
            if path.suffix.lower() not in EXTENSIONS or path.is_symlink():
                continue
            try:
                stat = path.stat()
            except FileNotFoundError:
                continue
            if stat.st_mtime < cutoff and stat.st_size > 0:
                yield stat.st_mtime, path


def clean_one(path: Path, source: Path, primary: str, backup: str, rclone: str) -> bool:
    relative = path.relative_to(source).as_posix()
    primary_object = f"{primary.rstrip('/')}/{relative}"
    backup_object = f"{backup.rstrip('/')}/{relative}"
    try:
        original_stat = path.stat(follow_symlinks=False)
        if not path.is_file() or path.is_symlink():
            return False
        local_digest = digest_file(path)
        if digest_remote(rclone, primary_object) != local_digest:
            print(f"held: primary S3 differs or is unreadable: {relative}", file=sys.stderr)
            return False
        with tempfile.TemporaryDirectory(prefix="hs-s3-restore-") as temporary:
            restored = Path(temporary) / "image"
            result = subprocess.run(
                [rclone, "copyto", backup_object, str(restored), "--contimeout", "10s", "--timeout", "60s", "--retries", "2"],
                stdout=subprocess.DEVNULL,
                stderr=subprocess.DEVNULL,
                check=False,
                timeout=180,
            )
            if result.returncode != 0 or not restored.is_file() or digest_file(restored) != local_digest:
                print(f"held: backup restore differs or failed: {relative}", file=sys.stderr)
                return False
        current_stat = path.stat(follow_symlinks=False)
        if (
            original_stat.st_ino != current_stat.st_ino
            or original_stat.st_dev != current_stat.st_dev
            or original_stat.st_size != current_stat.st_size
            or original_stat.st_mtime_ns != current_stat.st_mtime_ns
            or original_stat.st_ctime_ns != current_stat.st_ctime_ns
            or digest_file(path) != local_digest
        ):
            print(f"held: local file changed during verification: {relative}", file=sys.stderr)
            return False
        path.unlink()
        return True
    except (OSError, ValueError, subprocess.TimeoutExpired) as error:
        print(f"held: local verification failed: {relative}: {error}", file=sys.stderr)
        return False


def main() -> int:
    if os.environ.get("HS_S3_DELETE_LOCAL") != "1":
        return 0
    rclone = os.environ.get("HS_S3_RCLONE_BIN", "/usr/bin/rclone")
    minimum_age = int(os.environ.get("HS_S3_RETENTION_DAYS", "7")) * 86400
    limit = int(os.environ.get("HS_S3_CLEANUP_LIMIT", "100"))
    if minimum_age < 86400 or not 1 <= limit <= 500:
        print("invalid media retention or cleanup limit", file=sys.stderr)
        return 2
    mappings = (
        ("HS_S3_UPLOADS_DIR", "HS_S3_REMOTE_UPLOADS", "HS_S3_BACKUP_UPLOADS", "/var/www/koloda/data/www/hs-manacost.ru/wp-content/uploads", "ovh:hs-manacost-media-3az/wp-content/uploads", "ovh:hs-manacost-backups-3az/media-offload/wp-content/uploads"),
        ("HS_S3_WEBPC_DIR", "HS_S3_REMOTE_WEBPC", "HS_S3_BACKUP_WEBPC", "/var/www/koloda/data/www/hs-manacost.ru/wp-content/uploads-webpc", "ovh:hs-manacost-media-3az/wp-content/uploads-webpc", "ovh:hs-manacost-backups-3az/media-offload/wp-content/uploads-webpc"),
    )
    candidates = []
    for source_key, primary_key, backup_key, source_default, primary_default, backup_default in mappings:
        source = Path(os.environ.get(source_key, source_default))
        if not source.is_dir() or source.is_symlink():
            continue
        primary = os.environ.get(primary_key, primary_default)
        backup = os.environ.get(backup_key, backup_default)
        primary_bucket = primary.split(":", 1)[1].split("/", 1)[0] if ":" in primary else primary
        backup_bucket = backup.split(":", 1)[1].split("/", 1)[0] if ":" in backup else backup
        if primary_bucket == backup_bucket:
            print("primary and backup must use different buckets", file=sys.stderr)
            return 2
        candidates.extend((mtime, path, source, primary, backup) for mtime, path in eligible_files(source, minimum_age))
    removed = 0
    for _, path, source, primary, backup in sorted(candidates)[:limit]:
        removed += clean_one(path, source, primary, backup, rclone)
    print(f"verified local media removed={removed} checked={min(len(candidates), limit)}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
