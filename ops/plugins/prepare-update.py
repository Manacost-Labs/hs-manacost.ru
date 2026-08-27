#!/usr/bin/env python3
"""Validate one plugin archive and stage it as a source-only review change."""

from __future__ import annotations

import argparse
import json
import shutil
import stat
import sys
import tempfile
import zipfile
from pathlib import Path, PurePosixPath


ROOT = Path(__file__).resolve().parents[2]


def safe_members(archive: zipfile.ZipFile) -> list[zipfile.ZipInfo]:
    members: list[zipfile.ZipInfo] = []
    total_size = 0
    if len(archive.infolist()) > 20_000:
        raise ValueError("archive contains too many members")
    for member in archive.infolist():
        path = PurePosixPath(member.filename)
        mode = member.external_attr >> 16
        if path.is_absolute() or ".." in path.parts or stat.S_ISLNK(mode):
            raise ValueError(f"unsafe archive member: {member.filename}")
        if member.file_size > 100 * 1024 * 1024:
            raise ValueError(f"archive member is too large: {member.filename}")
        total_size += member.file_size
        if total_size > 500 * 1024 * 1024:
            raise ValueError("archive expands beyond the 500 MiB review limit")
        members.append(member)
    return members


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--slug", required=True)
    parser.add_argument("--archive", type=Path, required=True)
    parser.add_argument("--allow-manual-commercial", action="store_true")
    args = parser.parse_args()

    inventory = json.loads((ROOT / "config/wordpress-plugins.json").read_text(encoding="utf-8"))
    matches = [plugin for plugin in inventory["plugins"] if plugin["slug"] == args.slug]
    if len(matches) != 1:
        print("Slug must identify one active production plugin", file=sys.stderr)
        return 2
    plugin = matches[0]
    if plugin["origin"] in {"commercial", "tagdiv"} and not args.allow_manual_commercial:
        print("Commercial and tagDiv updates require explicit manual review", file=sys.stderr)
        return 2
    if not args.archive.is_file() or not zipfile.is_zipfile(args.archive):
        print("A valid ZIP archive is required", file=sys.stderr)
        return 2

    destination = ROOT / "wordpress/plugins" / args.slug
    with tempfile.TemporaryDirectory(prefix="hs-plugin-update-") as temporary:
        extraction = Path(temporary) / "archive"
        with zipfile.ZipFile(args.archive) as archive:
            members = safe_members(archive)
            archive.extractall(extraction, members)
        roots = [path for path in extraction.iterdir()]
        source = roots[0] if len(roots) == 1 and roots[0].is_dir() else extraction
        php_files = list(source.rglob("*.php"))
        if not php_files or not any("Plugin Name:" in path.read_text(errors="ignore") for path in php_files):
            print("Archive has no WordPress plugin header", file=sys.stderr)
            return 2

        review_copy = ROOT / ".artifacts/plugin-update" / args.slug
        if review_copy.exists():
            shutil.rmtree(review_copy)
        review_copy.parent.mkdir(parents=True, exist_ok=True)
        shutil.copytree(source, review_copy)
        print(f"Validated review copy: {review_copy}")
        print(f"Source destination after review: {destination}")
        print("No production or staging files were changed.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
