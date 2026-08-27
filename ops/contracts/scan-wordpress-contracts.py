#!/usr/bin/env python3
"""Build the reviewable contract inventory for project-owned WordPress code."""

from __future__ import annotations

import argparse
import json
import re
import sys
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[2]
INVENTORY = ROOT / "config/wordpress-contracts.json"

CONSTANT_RE = re.compile(
    r"(?:private\s+|protected\s+|public\s+)?const\s+([A-Z][A-Z0-9_]*)\s*=\s*(['\"])(.*?)\2\s*;"
)
CALL_RE = re.compile(r"\b([A-Za-z_][A-Za-z0-9_]*)\s*\(")
STRING_RE = re.compile(r"^\s*(['\"])(.*?)\1\s*$", re.DOTALL)
SELF_CONST_RE = re.compile(r"^\s*(?:self|static)::([A-Z][A-Z0-9_]*)\s*$")
CLASS_CONST_RE = re.compile(r"^\s*([A-Za-z_\\][A-Za-z0-9_\\]*)::([A-Z][A-Z0-9_]*)\s*$")
BARE_CONST_RE = re.compile(r"^\s*([A-Z][A-Z0-9_]*)\s*$")


def source_roots() -> list[Path]:
    manifest = json.loads((ROOT / "config/wordpress-plugins.json").read_text(encoding="utf-8"))
    roots = [ROOT / "wordpress/mu-plugins"]
    for plugin in manifest["plugins"]:
        if plugin["origin"] in {"custom", "legacy"}:
            roots.append(ROOT / "wordpress/plugins" / plugin["slug"])
    return roots


def split_arguments(source: str, open_paren: int) -> tuple[list[str], int] | None:
    args: list[str] = []
    start = open_paren + 1
    depth = 1
    quote = ""
    escaped = False
    index = start
    while index < len(source):
        char = source[index]
        if quote:
            if escaped:
                escaped = False
            elif char == "\\":
                escaped = True
            elif char == quote:
                quote = ""
        elif char in {"'", '"'}:
            quote = char
        elif char in "([{" :
            depth += 1
        elif char in ")]}":
            depth -= 1
            if depth == 0:
                args.append(source[start:index].strip())
                return args, index
        elif char == "," and depth == 1:
            args.append(source[start:index].strip())
            start = index + 1
        index += 1
    return None


def resolve(expression: str, constants: dict[str, str]) -> str | None:
    string_match = STRING_RE.match(expression)
    if string_match:
        return string_match.group(2)
    constant_match = SELF_CONST_RE.match(expression)
    if constant_match:
        name = constant_match.group(1)
        return constants.get(name, f"@constant:self::{name}")
    class_constant_match = CLASS_CONST_RE.match(expression)
    if class_constant_match:
        return "@constant:" + class_constant_match.group(1) + "::" + class_constant_match.group(2)
    bare_constant_match = BARE_CONST_RE.match(expression)
    if bare_constant_match and bare_constant_match.group(1) not in {"TRUE", "FALSE", "NULL"}:
        return "@constant:" + bare_constant_match.group(1)
    return None


def occurrence(path: Path, source: str, offset: int, name: str, api: str) -> dict[str, Any]:
    return {
        "name": name,
        "resolved": not name.startswith("@constant:"),
        "api": api,
        "file": path.relative_to(ROOT).as_posix(),
        "line": source.count("\n", 0, offset) + 1,
    }


def scan_file(path: Path) -> dict[str, list[dict[str, Any]]]:
    source = path.read_text(encoding="utf-8", errors="replace")
    constants = {match.group(1): match.group(3) for match in CONSTANT_RE.finditer(source)}
    found: dict[str, list[dict[str, Any]]] = {
        "post_meta": [],
        "options": [],
        "shortcodes": [],
        "ajax_actions": [],
        "rest_routes": [],
        "cron_hooks": [],
        "capabilities": [],
    }
    metadata_apis = {
        "get_post_meta",
        "add_post_meta",
        "update_post_meta",
        "delete_post_meta",
    }
    option_apis = {"get_option", "add_option", "update_option", "delete_option"}
    cron_apis = {"wp_schedule_event": 2, "wp_schedule_single_event": 1}

    for call in CALL_RE.finditer(source):
        parsed = split_arguments(source, source.find("(", call.start()))
        if not parsed:
            continue
        args, _ = parsed
        api = call.group(1)
        value: str | None = None
        kind: str | None = None

        if api in metadata_apis and len(args) > 1:
            kind, value = "post_meta", resolve(args[1], constants)
        elif api in option_apis and args:
            kind, value = "options", resolve(args[0], constants)
        elif api == "add_shortcode" and args:
            kind, value = "shortcodes", resolve(args[0], constants)
        elif api == "current_user_can" and args:
            kind, value = "capabilities", resolve(args[0], constants)
        elif api == "add_action" and args:
            hook = resolve(args[0], constants)
            if hook and (hook.startswith("wp_ajax_") or hook.startswith("wp_ajax_nopriv_")):
                kind, value = "ajax_actions", hook
        elif api == "register_rest_route" and len(args) > 1:
            namespace = resolve(args[0], constants)
            route = resolve(args[1], constants)
            if namespace and route:
                kind, value = "rest_routes", namespace.rstrip("/") + "/" + route.lstrip("/")
        elif api in cron_apis and len(args) > cron_apis[api]:
            kind, value = "cron_hooks", resolve(args[cron_apis[api]], constants)

        if kind and value:
            found[kind].append(occurrence(path, source, call.start(), value, api))
    return found


def build_inventory() -> dict[str, Any]:
    contracts: dict[str, list[dict[str, Any]]] = {
        key: []
        for key in (
            "post_meta",
            "options",
            "shortcodes",
            "ajax_actions",
            "rest_routes",
            "cron_hooks",
            "capabilities",
        )
    }
    scanned: list[str] = []
    for root in source_roots():
        if not root.is_dir():
            continue
        scanned.append(root.relative_to(ROOT).as_posix())
        for path in sorted(root.rglob("*.php")):
            for kind, entries in scan_file(path).items():
                contracts[kind].extend(entries)

    for entries in contracts.values():
        unique = {
            (entry["name"], entry["api"], entry["file"], entry["line"]): entry
            for entry in entries
        }
        entries[:] = sorted(
            unique.values(),
            key=lambda item: (item["name"], item["file"], item["line"], item["api"]),
        )

    return {
        "schema_version": 1,
        "scope": {
            "description": "Project-owned MU plugins and active custom/legacy plugins only",
            "source_roots": sorted(scanned),
            "dynamic_contracts": "Expressions that cannot be resolved statically require an explicit integration test.",
        },
        "contracts": contracts,
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    group = parser.add_mutually_exclusive_group(required=True)
    group.add_argument("--write", action="store_true")
    group.add_argument("--check", action="store_true")
    args = parser.parse_args()
    rendered = json.dumps(build_inventory(), ensure_ascii=False, indent=2) + "\n"
    if args.write:
        INVENTORY.write_text(rendered, encoding="utf-8")
        return 0
    if not INVENTORY.is_file() or INVENTORY.read_text(encoding="utf-8") != rendered:
        print("WordPress contract inventory is stale. Run: make contracts", file=sys.stderr)
        return 1
    print("WordPress contracts: current")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
