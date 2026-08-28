#!/usr/bin/env python3
"""Prevent new or expanded PHPStan baseline suppressions."""

from __future__ import annotations

import argparse
import collections
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
ENTRY = re.compile(
    r"\n\s*-\s*\n"
    + r"\s*message:\s*(?P<message>.+)\n"
    + r"\s*identifier:\s*(?P<identifier>.+)\n"
    + r"\s*count:\s*(?P<count>\d+)\n"
    + r"\s*path:\s*(?P<path>.+)(?=\n|$)"
)


class Arguments(argparse.Namespace):
    def __init__(self) -> None:
        super().__init__()
        self.base: str = "origin/main"
        self.reference: Path | None = None
        self.current: Path = ROOT / "phpstan-baseline.neon"


def entries(source: str) -> collections.Counter[tuple[str, str, str]]:
    result: collections.Counter[tuple[str, str, str]] = collections.Counter()
    for match in ENTRY.finditer(source):
        key = (
            match.group("message").strip(),
            match.group("identifier").strip(),
            match.group("path").strip(),
        )
        result[key] += int(match.group("count"))
    return result


def git_reference(base: str) -> str:
    result = subprocess.run(
        ["git", "show", f"{base}:phpstan-baseline.neon"],
        cwd=ROOT,
        check=False,
        capture_output=True,
        text=True,
    )
    if result.returncode != 0:
        raise ValueError(
            result.stderr.strip() or "cannot read reference PHPStan baseline"
        )
    return result.stdout


def main() -> int:
    parser = argparse.ArgumentParser()
    _ = parser.add_argument("--base", default="origin/main")
    _ = parser.add_argument("--reference", type=Path)
    _ = parser.add_argument(
        "--current", type=Path, default=ROOT / "phpstan-baseline.neon"
    )
    args = parser.parse_args(namespace=Arguments())
    try:
        reference_source = (
            args.reference.read_text(encoding="utf-8")
            if args.reference is not None
            else git_reference(args.base)
        )
        current_source = args.current.read_text(encoding="utf-8")
    except (OSError, ValueError) as error:
        print(str(error), file=sys.stderr)
        return 2

    reference = entries(reference_source)
    current = entries(current_source)
    additions = {
        key: count - reference[key]
        for key, count in current.items()
        if count > reference[key]
    }
    if additions:
        print("PHPStan baseline contains new baseline suppressions:", file=sys.stderr)
        for (_, identifier, path), count in sorted(additions.items()):
            print(f"- {path}: {identifier} (+{count})", file=sys.stderr)
        return 1
    print(
        f"PHPStan baseline ratchet: {sum(current.values())} <= {sum(reference.values())}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
