#!/usr/bin/env python3
"""Stands in for rclone in integration tests: deckarchive:<key> maps to a local directory."""
import os
import shutil
import sys
from pathlib import Path

args = sys.argv[1:]
store = Path(os.environ["DECK_ARCHIVE_FAKE_STORE"])


def target(remote: str) -> Path:
    if not remote.startswith("deckarchive:"):
        sys.exit(f"unexpected remote {remote}")
    return store / remote.split(":", 1)[1]


if args[0] == "copyto":
    path = target(args[2])
    if path.exists() and path.read_bytes() != Path(args[1]).read_bytes():
        sys.exit("immutable object differs")
    path.parent.mkdir(parents=True, exist_ok=True)
    shutil.copyfile(args[1], path)
elif args[0] == "cat":
    sys.stdout.buffer.write(target(args[1]).read_bytes())
else:
    sys.exit(f"unsupported rclone command {args[0]}")
