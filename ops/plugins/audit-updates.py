#!/usr/bin/env python3
"""Create a read-only WordPress plugin update and compatibility report."""

from __future__ import annotations

import argparse
import json
import sys
import urllib.parse
import urllib.request
from urllib.error import HTTPError
from datetime import datetime, timezone
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[2]
API = "https://api.wordpress.org/plugins/info/1.2/"


def plugin_info(slug: str) -> dict[str, Any]:
    query = urllib.parse.urlencode(
        {
            "action": "plugin_information",
            "request[slug]": slug,
            "request[fields][sections]": 0,
            "request[fields][description]": 0,
            "request[fields][icons]": 0,
            "request[fields][banners]": 0,
        }
    )
    request = urllib.request.Request(
        f"{API}?{query}",
        headers={"User-Agent": "hs-manacost-plugin-audit/1.0"},
    )
    with urllib.request.urlopen(request, timeout=20) as response:
        return json.load(response)


def version_parts(value: str) -> tuple[int, ...]:
    parts: list[int] = []
    for part in value.split("."):
        digits = "".join(character for character in part if character.isdigit())
        if not digits:
            break
        parts.append(int(digits))
    return tuple(parts)


def audit(offline: bool) -> dict[str, Any]:
    inventory = json.loads((ROOT / "config/wordpress-plugins.json").read_text(encoding="utf-8"))
    runtime = json.loads((ROOT / "config/site.json").read_text(encoding="utf-8"))["runtime"]
    rows: list[dict[str, Any]] = []
    for plugin in inventory["plugins"]:
        row: dict[str, Any] = {
            "slug": plugin["slug"],
            "installed": plugin["version"],
            "origin": plugin["origin"],
            "decision": "manual" if plugin["origin"] in {"commercial", "tagdiv"} else "review",
        }
        if plugin["origin"] != "wordpress.org" or offline:
            row["status"] = "manual-source-check" if plugin["origin"] != "wordpress.org" else "offline"
            rows.append(row)
            continue
        try:
            info = plugin_info(plugin["slug"])
            latest = str(info.get("version", ""))
            requires_wordpress = str(info.get("requires", ""))
            requires_php = str(info.get("requires_php", ""))
            compatibility = "candidate-review"
            if version_parts(requires_wordpress) > version_parts(runtime["wordpress"]):
                compatibility = "blocked-by-wordpress"
            elif version_parts(requires_php) > version_parts(runtime["php_fpm"]):
                compatibility = "blocked-by-php"
            row.update(
                {
                    "latest": latest,
                    "requires_wordpress": requires_wordpress,
                    "tested_wordpress": str(info.get("tested", "")),
                    "requires_php": requires_php,
                    "last_updated": str(info.get("last_updated", "")),
                    "compatibility": compatibility,
                    "status": "update-available"
                    if version_parts(latest) > version_parts(plugin["version"])
                    else "current",
                }
            )
        except HTTPError as error:
            if error.code == 404:
                row.update(
                    {
                        "status": "unavailable-wordpress-org",
                        "decision": "manual",
                        "error": "HTTP 404",
                    }
                )
            else:
                row.update({"status": "lookup-failed", "error": f"HTTP {error.code}"})
        except (OSError, ValueError, json.JSONDecodeError) as error:
            row.update({"status": "lookup-failed", "error": type(error).__name__})
        rows.append(row)

    return {
        "schema_version": 1,
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "runtime": {"wordpress": runtime["wordpress"], "php_fpm": runtime["php_fpm"]},
        "notice": "This report never installs or updates plugins.",
        "plugins": rows,
    }


def markdown(report: dict[str, Any]) -> str:
    lines = [
        "# WordPress plugin compatibility report",
        "",
        f"Generated: {report['generated_at']}",
        "",
        "| Plugin | Installed | Latest | Origin | Status | Compatibility | WP tested | PHP required |",
        "|---|---:|---:|---|---|---|---:|---:|",
    ]
    for row in report["plugins"]:
        lines.append(
            "| {slug} | {installed} | {latest} | {origin} | {status} | {compatibility} | {tested} | {php} |".format(
                slug=row["slug"],
                installed=row["installed"],
                latest=row.get("latest", "manual"),
                origin=row["origin"],
                status=row["status"],
                compatibility=row.get("compatibility", "manual"),
                tested=row.get("tested_wordpress", "manual"),
                php=row.get("requires_php", "manual"),
            )
        )
    lines.extend(
        [
            "",
            "Commercial and tagDiv/Newspaper packages are manual-only. Update one plugin per change,",
            "run integration and visual tests, deploy to staging, and keep the previous Git commit as rollback.",
            "",
        ]
    )
    return "\n".join(lines)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--output-dir", type=Path, required=True)
    parser.add_argument("--offline", action="store_true")
    args = parser.parse_args()
    report = audit(args.offline)
    args.output_dir.mkdir(parents=True, exist_ok=True)
    (args.output_dir / "plugin-audit.json").write_text(
        json.dumps(report, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )
    (args.output_dir / "plugin-audit.md").write_text(markdown(report), encoding="utf-8")
    failed = [row for row in report["plugins"] if row["status"] == "lookup-failed"]
    if failed:
        print(f"Plugin metadata lookups failed: {len(failed)}", file=sys.stderr)
        return 1
    print(f"Plugin audit complete: {len(report['plugins'])} active plugins")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
