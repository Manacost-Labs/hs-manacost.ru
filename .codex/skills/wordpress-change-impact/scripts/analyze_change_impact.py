#!/usr/bin/env python3
"""Map hs-manacost Git paths to project risks, contracts and checks."""

from __future__ import annotations

import argparse
import fnmatch
import json
import subprocess
import sys
from pathlib import Path, PurePosixPath
from typing import Any


ROOT = Path(__file__).resolve().parents[4]
MAP_PATH = ROOT / "config/change-impact-map.json"
CONTRACTS_PATH = ROOT / "config/wordpress-contracts.json"
RISK_ORDER = {"low": 0, "medium": 1, "high": 2, "critical": 3}


def fail(message: str) -> None:
    raise ValueError(message)


def load_object(path: Path) -> dict[str, Any]:
    value = json.loads(path.read_text(encoding="utf-8"))
    if not isinstance(value, dict):
        fail(f"{path.name} root must be an object")
    return value


def normalize_path(raw_path: str) -> str:
    path = PurePosixPath(raw_path.strip().replace("\\", "/"))
    if not raw_path.strip() or path.is_absolute() or ".." in path.parts:
        fail(f"unsafe or empty path: {raw_path!r}")
    return path.as_posix().removeprefix("./")


def git_paths(base: str, head: str) -> list[str]:
    command = ["git", "diff", "--name-only", "--diff-filter=ACMR", base, head, "--"]
    result = subprocess.run(command, cwd=ROOT, check=False, capture_output=True, text=True)
    if result.returncode != 0:
        fail(result.stderr.strip() or "git diff failed")
    paths = [normalize_path(line) for line in result.stdout.splitlines() if line.strip()]
    if head == "HEAD":
        working_tree = subprocess.run(
            ["git", "diff", "--name-only", "--diff-filter=ACMR", "HEAD", "--"],
            cwd=ROOT,
            check=False,
            capture_output=True,
            text=True,
        )
        if working_tree.returncode != 0:
            fail(working_tree.stderr.strip() or "working tree diff failed")
        paths.extend(
            normalize_path(line)
            for line in working_tree.stdout.splitlines()
            if line.strip()
        )
    untracked = subprocess.run(
        ["git", "ls-files", "--others", "--exclude-standard"],
        cwd=ROOT,
        check=False,
        capture_output=True,
        text=True,
    )
    if untracked.returncode == 0:
        paths.extend(normalize_path(line) for line in untracked.stdout.splitlines() if line.strip())
    return sorted(set(paths))


def string_list(value: object, path: str) -> list[str]:
    if not isinstance(value, list) or any(not isinstance(item, str) for item in value):
        fail(f"{path} must be a list of strings")
    return list(value)


def matching_contracts(paths: set[str]) -> tuple[list[dict[str, str]], int]:
    inventory = load_object(CONTRACTS_PATH)
    groups = inventory.get("contracts")
    if not isinstance(groups, dict):
        fail("wordpress contract inventory is invalid")
    matched: dict[tuple[str, str, str, str], dict[str, str]] = {}
    for contract_type, entries in groups.items():
        if not isinstance(entries, list):
            continue
        for entry in entries:
            if not isinstance(entry, dict) or entry.get("file") not in paths:
                continue
            normalized = {
                "type": str(contract_type),
                "name": str(entry.get("name", "dynamic")),
                "api": str(entry.get("api", "unknown")),
                "file": str(entry["file"]),
            }
            key = (
                normalized["type"],
                normalized["name"],
                normalized["api"],
                normalized["file"],
            )
            matched[key] = normalized
    all_contracts = [matched[key] for key in sorted(matched)]
    return all_contracts[:100], max(0, len(all_contracts) - 100)


def analyze(paths: list[str], config: dict[str, Any]) -> dict[str, Any]:
    if config.get("schema_version") != 1:
        fail("change impact map schema_version must be 1")
    defaults = config.get("defaults")
    rules = config.get("rules")
    if not isinstance(defaults, dict) or not isinstance(rules, list):
        fail("change impact map requires defaults and rules")

    risk = str(defaults.get("risk", "low"))
    if risk not in RISK_ORDER:
        fail("invalid default risk")
    domains = set(string_list(defaults.get("domains"), "defaults.domains"))
    skills = set(string_list(defaults.get("skills"), "defaults.skills"))
    checks = set(string_list(defaults.get("checks"), "defaults.checks"))
    surfaces: set[str] = set()
    owners: set[str] = set()
    unclassified: list[str] = []
    path_details: list[dict[str, Any]] = []

    for changed_path in paths:
        matched_rules: list[dict[str, Any]] = []
        classified = not changed_path.startswith("wordpress/mu-plugins/")
        for raw_rule in rules:
            if not isinstance(raw_rule, dict):
                fail("every change impact rule must be an object")
            patterns = string_list(raw_rule.get("patterns"), "rule.patterns")
            if not any(fnmatch.fnmatchcase(changed_path, pattern) for pattern in patterns):
                continue
            matched_rules.append(raw_rule)
            classified = classified or raw_rule.get("classified") is True
            rule_risk = str(raw_rule.get("risk", "low"))
            if rule_risk not in RISK_ORDER:
                fail(f"invalid risk for {changed_path}: {rule_risk}")
            if RISK_ORDER[rule_risk] > RISK_ORDER[risk]:
                risk = rule_risk
            owner = str(raw_rule.get("owner", "unowned"))
            owners.add(owner)
            domains.update(string_list(raw_rule.get("domains", []), "rule.domains"))
            skills.update(string_list(raw_rule.get("skills", []), "rule.skills"))
            checks.update(string_list(raw_rule.get("checks", []), "rule.checks"))
            surfaces.update(string_list(raw_rule.get("surfaces", []), "rule.surfaces"))
        if not classified:
            unclassified.append(changed_path)
            risk = "high" if RISK_ORDER[risk] < RISK_ORDER["high"] else risk
        path_details.append(
            {
                "path": changed_path,
                "owners": sorted({str(rule.get("owner", "unowned")) for rule in matched_rules}),
                "classified": classified,
            }
        )

    contracts, truncated_contracts = matching_contracts(set(paths))
    if contracts:
        checks.add("make contracts")
        checks.add("make integration")
        surfaces.add("wordpress-contracts")

    return {
        "schema_version": 1,
        "status": "REVIEW" if unclassified else "PASS",
        "risk": risk,
        "changed_paths": paths,
        "path_details": path_details,
        "owners": sorted(owners),
        "surfaces": sorted(surfaces),
        "domains": sorted(domains),
        "skills": sorted(skills),
        "checks": sorted(checks),
        "contracts": contracts,
        "truncated_contract_count": truncated_contracts,
        "manual_review_required": bool(unclassified),
        "unclassified_first_party": sorted(unclassified),
    }


def render_markdown(report: dict[str, Any]) -> str:
    lines = [
        "# WordPress change impact",
        "",
        f"- Status: {report['status']}",
        f"- Risk: {report['risk']}",
        f"- Manual review: {'required' if report['manual_review_required'] else 'not required'}",
    ]
    for title, key in (
        ("Changed paths", "changed_paths"),
        ("Surfaces", "surfaces"),
        ("Domains", "domains"),
        ("Required skills", "skills"),
        ("Required checks", "checks"),
    ):
        lines.extend(("", f"## {title}", ""))
        values = report[key]
        lines.extend(f"- `{value}`" for value in values) if values else lines.append("- None")
    if report["contracts"]:
        lines.extend(("", "## Affected WordPress contracts", ""))
        for contract in report["contracts"]:
            lines.append(
                f"- `{contract['type']}:{contract['name']}` via `{contract['api']}` "
                f"in `{contract['file']}`"
            )
    if report["unclassified_first_party"]:
        lines.extend(("", "## Unclassified first-party paths", ""))
        lines.extend(f"- `{value}`" for value in report["unclassified_first_party"])
    return "\n".join(lines) + "\n"


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--base", default="origin/main")
    parser.add_argument("--head", default="HEAD")
    parser.add_argument("--format", choices=("json", "markdown"), default="json")
    parser.add_argument("--paths", nargs="*")
    args = parser.parse_args()
    try:
        paths = (
            sorted({normalize_path(path) for path in args.paths})
            if args.paths is not None
            else git_paths(args.base, args.head)
        )
        report = analyze(paths, load_object(MAP_PATH))
    except (OSError, json.JSONDecodeError, ValueError) as error:
        print(json.dumps({"status": "INVALID", "error": str(error)}, ensure_ascii=False))
        return 2
    if args.format == "markdown":
        print(render_markdown(report), end="")
    else:
        print(json.dumps(report, ensure_ascii=False, indent=2, sort_keys=True))
    return 1 if report["manual_review_required"] else 0


if __name__ == "__main__":
    raise SystemExit(main())
