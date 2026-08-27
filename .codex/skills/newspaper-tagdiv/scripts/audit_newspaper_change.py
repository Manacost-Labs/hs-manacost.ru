#!/usr/bin/env python3
"""Report protected Newspaper/tagDiv files affected by a Git change."""

from __future__ import annotations

import argparse
import json
import subprocess
from pathlib import Path


PROTECTED_PREFIXES = {
    "theme": "wordpress/themes/Newspaper_new/",
    "composer": "wordpress/plugins/td-composer/",
    "standard_pack": "wordpress/plugins/td-standard-pack/",
    "cloud_library": "wordpress/plugins/td-cloud-library/",
    "mobile_plugin": "wordpress/plugins/td-mobile-plugin/",
}

RELATED_FILES = {
    "wordpress/mu-plugins/hs-admin-ajax-guard.php",
    "wordpress/mu-plugins/hs-admin-load-trim.php",
    "wordpress/mu-plugins/hs-lcp-background-preload.php",
    "wordpress/mu-plugins/hs-manacost-newspaper-trim.php",
    "wordpress/mu-plugins/manacost-ad-polish.php",
    "wordpress/mu-plugins/manacost-cache-purge.php",
    "wordpress/mu-plugins/manacost-performance-optimizer.php",
}


def git_paths(repo: Path, args: argparse.Namespace) -> list[str]:
    command = ["git", "-C", str(repo), "diff", "--name-only"]
    if args.staged:
        command.append("--cached")
    elif args.base:
        command.append(args.base)
    command.append("--")
    result = subprocess.run(command, check=True, capture_output=True, text=True)
    paths = {line.strip() for line in result.stdout.splitlines() if line.strip()}
    if args.include_untracked:
        untracked = subprocess.run(
            ["git", "-C", str(repo), "ls-files", "--others", "--exclude-standard"],
            check=True,
            capture_output=True,
            text=True,
        )
        paths.update(line.strip() for line in untracked.stdout.splitlines() if line.strip())
    return sorted(paths)


def analyze(paths: list[str]) -> dict[str, object]:
    groups = {
        name: [path for path in paths if path.startswith(prefix)]
        for name, prefix in PROTECTED_PREFIXES.items()
    }
    related = sorted(path for path in paths if path in RELATED_FILES)
    protected = sorted({path for values in groups.values() for path in values})
    direct_vendor_change = bool(protected)
    theme_and_plugins = bool(groups["theme"] and any(groups[name] for name in groups if name != "theme"))
    minified_only = []
    path_set = set(paths)
    for path in protected:
        if ".min." not in path:
            continue
        source_path = path.replace(".min.", ".")
        if source_path not in path_set:
            minified_only.append(path)

    risks = []
    if direct_vendor_change:
        risks.append("direct Newspaper/tagDiv vendor change")
    if theme_and_plugins:
        risks.append("theme and coupled tagDiv plugins changed together")
    if minified_only:
        risks.append("minified assets changed without source counterparts")
    if "wordpress/themes/Newspaper_new/functions.php" in path_set:
        risks.append("parent theme functions.php changed")

    return {
        "protected_groups": groups,
        "related_project_files": related,
        "direct_vendor_change": direct_vendor_change,
        "theme_and_plugins_changed_together": theme_and_plugins,
        "minified_without_source": minified_only,
        "risks": risks,
        "required_checks": [
            "make check",
            "ai-security-check staged",
            "staging deploy and smoke-check",
            "desktop/mobile browser verification",
            "Composer save and public render when affected",
        ] if protected or related else [],
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--repo", type=Path, default=Path.cwd())
    parser.add_argument("--base", default=None)
    parser.add_argument("--staged", action="store_true")
    parser.add_argument("--include-untracked", action="store_true")
    parser.add_argument("--strict", action="store_true")
    parser.add_argument("--format", choices=("json", "text"), default="json")
    args = parser.parse_args()
    report = analyze(git_paths(args.repo.resolve(), args))
    if args.format == "json":
        print(json.dumps(report, ensure_ascii=False, indent=2, sort_keys=True))
    else:
        print("Newspaper/tagDiv audit")
        for risk in report["risks"]:
            print(f"- RISK: {risk}")
        if not report["risks"]:
            print("- no protected Newspaper/tagDiv source changed")
    return 1 if args.strict and report["risks"] else 0


if __name__ == "__main__":
    raise SystemExit(main())
