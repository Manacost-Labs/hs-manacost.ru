#!/usr/bin/env python3
"""Block new giant PHP files and growth of existing giant first-party files."""

from __future__ import annotations

import argparse
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
NEW_FILE_LIMIT = 500
LEGACY_GIANT_LIMIT = 1000


class Arguments(argparse.Namespace):
    def __init__(self) -> None:
        super().__init__()
        self.base: str = ""
        self.files: list[str] = []


def line_count(source: str) -> int:
    return len(source.splitlines())


def base_source(base: str, relative_path: str) -> str | None:
    result = subprocess.run(
        ["git", "show", f"{base}:{relative_path}"],
        cwd=ROOT,
        check=False,
        capture_output=True,
        text=True,
    )
    return result.stdout if result.returncode == 0 else None


def main() -> int:
    parser = argparse.ArgumentParser()
    _ = parser.add_argument("--base", required=True)
    _ = parser.add_argument("files", nargs="*")
    args = parser.parse_args(namespace=Arguments())
    failures: list[str] = []
    for raw_path in args.files:
        path = Path(raw_path)
        try:
            relative_path = (
                path.relative_to(ROOT).as_posix()
                if path.is_absolute()
                else path.as_posix()
            )
            current_lines = line_count(
                (ROOT / relative_path).read_text(encoding="utf-8")
            )
        except (OSError, ValueError) as error:
            print(str(error), file=sys.stderr)
            return 2
        previous = base_source(args.base, relative_path)
        if previous is None and current_lines > NEW_FILE_LIMIT:
            failures.append(
                f"{relative_path}: new PHP file has {current_lines} lines; limit is {NEW_FILE_LIMIT}"
            )
        elif previous is not None:
            previous_lines = line_count(previous)
            if current_lines > LEGACY_GIANT_LIMIT and current_lines > previous_lines:
                failures.append(
                    f"{relative_path}: giant file grew from {previous_lines} to {current_lines} lines"
                )
    if failures:
        print("PHP structure gate failed:", file=sys.stderr)
        for failure in failures:
            print(f"- {failure}", file=sys.stderr)
        return 1
    print(f"PHP structure gate: {len(args.files)} changed file(s) checked")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
