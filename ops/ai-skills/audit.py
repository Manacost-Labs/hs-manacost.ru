#!/usr/bin/env python3
"""Audit project AI skills without loading secrets or runtime data."""

from __future__ import annotations

import argparse
import ast
import json
import os
import re
import shutil
import subprocess
from dataclasses import dataclass, field
from pathlib import Path
from typing import Any

ALLOWED_FRONTMATTER = {"name", "description", "license", "allowed-tools", "metadata"}
NAME_PATTERN = re.compile(r"^[a-z0-9]+(?:-[a-z0-9]+)*$")
GIT_SHA_PATTERN = re.compile(r"^[0-9a-f]{40}$")
MARKDOWN_LINK_PATTERN = re.compile(r"(?<!!)\[[^]]*]\(([^)]+)\)")
MAX_SKILL_LINES = 500


@dataclass
class AuditResult:
    errors: list[str] = field(default_factory=list)
    warnings: list[str] = field(default_factory=list)
    skills: int = 0
    scripts: int = 0

    def error(self, message: str) -> None:
        self.errors.append(message)

    def warning(self, message: str) -> None:
        self.warnings.append(message)


def parse_frontmatter(path: Path, result: AuditResult) -> tuple[dict[str, str], str]:
    text = path.read_text(encoding="utf-8")
    match = re.match(r"^---\n(.*?)\n---(?:\n|$)", text, re.DOTALL)
    if match is None:
        result.error(f"{path}: missing or malformed YAML frontmatter")
        return {}, text

    raw = match.group(1)
    keys = re.findall(r"(?m)^([A-Za-z0-9_-]+):(?:\s|$)", raw)
    unexpected = sorted(set(keys) - ALLOWED_FRONTMATTER)
    if unexpected:
        result.error(f"{path}: unsupported frontmatter keys: {', '.join(unexpected)}")

    values: dict[str, str] = {}
    lines = raw.splitlines()
    for index, line in enumerate(lines):
        field_match = re.match(r"^([A-Za-z0-9_-]+):\s*(.*)$", line)
        if field_match is None:
            continue
        key, value = field_match.groups()
        if value in {">", ">-", "|", "|-"}:
            continuation: list[str] = []
            for following in lines[index + 1 :]:
                if following and not following.startswith((" ", "\t")):
                    break
                if following.strip():
                    continuation.append(following.strip())
            value = " ".join(continuation)
        values[key] = value.strip().strip("\"'")

    return values, text[match.end() :]


def audit_openai_metadata(skill_dir: Path, skill_name: str, result: AuditResult) -> None:
    metadata_path = skill_dir / "agents/openai.yaml"
    if not metadata_path.is_file():
        result.error(f"{skill_name}: agents/openai.yaml is required")
        return

    text = metadata_path.read_text(encoding="utf-8")
    if not re.search(r"(?m)^interface:\s*$", text):
        result.error(f"{metadata_path}: interface section is required")

    fields: dict[str, str] = {}
    for name in ("display_name", "short_description", "default_prompt"):
        match = re.search(rf'(?m)^  {name}:\s*"([^"]+)"\s*$', text)
        if match is None:
            result.error(f"{metadata_path}: quoted interface.{name} is required")
            continue
        fields[name] = match.group(1)

    short_description = fields.get("short_description", "")
    if short_description and not 25 <= len(short_description) <= 64:
        result.error(
            f"{metadata_path}: short_description must contain 25-64 characters"
        )
    prompt = fields.get("default_prompt", "")
    if prompt and f"${skill_name}" not in prompt:
        result.error(f"{metadata_path}: default_prompt must mention ${skill_name}")


def audit_markdown_links(skill_dir: Path, result: AuditResult) -> None:
    for markdown in skill_dir.rglob("*.md"):
        text = markdown.read_text(encoding="utf-8")
        for raw_target in MARKDOWN_LINK_PATTERN.findall(text):
            target = raw_target.strip().strip("<>")
            if not target or target.startswith(("#", "http://", "https://", "mailto:")):
                continue
            target = target.split("#", 1)[0]
            if not target:
                continue
            resolved = (markdown.parent / target).resolve()
            if not resolved.exists():
                result.error(f"{markdown}: broken local link: {raw_target}")


def audit_scripts(skill_dir: Path, result: AuditResult) -> None:
    scripts_dir = skill_dir / "scripts"
    if not scripts_dir.is_dir():
        return

    for script in sorted(path for path in scripts_dir.rglob("*") if path.is_file()):
        result.scripts += 1
        data = script.read_bytes()
        if script.suffix in {".sh", ".py"} and b"\r\n" in data:
            result.error(f"{script}: executable source must use LF line endings")

        text = data.decode("utf-8", errors="replace")
        has_shebang = text.startswith("#!")
        if has_shebang and not os.access(script, os.X_OK):
            result.error(f"{script}: shebang script is not executable")

        if script.suffix == ".sh":
            completed = subprocess.run(
                ["bash", "-n", str(script)], capture_output=True, text=True, check=False
            )
            if completed.returncode:
                detail = completed.stderr.strip() or "bash -n failed"
                result.error(f"{script}: {detail}")
        elif script.suffix == ".py":
            try:
                ast.parse(text, filename=str(script))
            except SyntaxError as exc:
                result.error(f"{script}: Python syntax error: {exc}")
        elif script.suffix in {".js", ".mjs"}:
            node = shutil.which("node")
            if node is None:
                result.error(f"{script}: Node.js is required to validate this script")
                continue
            completed = subprocess.run(
                [node, "--check", str(script)], capture_output=True, text=True, check=False
            )
            if completed.returncode:
                detail = completed.stderr.strip() or "node --check failed"
                result.error(f"{script}: {detail}")


def comparable_files(root: Path) -> dict[Path, bytes]:
    comparable: dict[Path, bytes] = {}
    text_suffixes = {
        ".css",
        ".html",
        ".js",
        ".json",
        ".md",
        ".mjs",
        ".php",
        ".scss",
        ".sh",
        ".stub",
        ".txt",
        ".xml",
        ".yaml",
        ".yml",
    }
    for path in root.rglob("*"):
        if not path.is_file() or "__pycache__" in path.parts or path.suffix == ".pyc":
            continue
        data = path.read_bytes()
        if path.suffix in text_suffixes or path.name == "LICENSE":
            data = data.replace(b"\r\n", b"\n")
        comparable[path.relative_to(root)] = data
    return comparable


def audit_registry(repo_root: Path, skill_dirs: list[Path], result: AuditResult) -> None:
    registry_path = repo_root / "config/ai-skills.json"
    try:
        registry: dict[str, Any] = json.loads(registry_path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as exc:
        result.error(f"{registry_path}: invalid JSON: {exc}")
        return

    entries = registry.get("project_local")
    if not isinstance(entries, list):
        result.error(f"{registry_path}: project_local must be a list")
        return

    registered: dict[str, dict[str, Any]] = {}
    for entry in entries:
        if not isinstance(entry, dict) or not isinstance(entry.get("name"), str):
            result.error(f"{registry_path}: every project_local entry needs a name")
            continue
        name = entry["name"]
        if name in registered:
            result.error(f"{registry_path}: duplicate skill registration: {name}")
        registered[name] = entry

        expected_path = f".agents/skills/{name}/SKILL.md"
        if entry.get("path") != expected_path:
            result.error(f"{registry_path}: {name} path must be {expected_path}")
        if not isinstance(entry.get("required_for"), list) or not entry["required_for"]:
            result.error(f"{registry_path}: {name}.required_for must be non-empty")
        source_commit = entry.get("source_commit")
        if source_commit is not None and (
            not isinstance(source_commit, str) or not GIT_SHA_PATTERN.fullmatch(source_commit)
        ):
            result.error(f"{registry_path}: {name}.source_commit must be a full Git SHA")

    directory_names = {path.name for path in skill_dirs}
    registered_names = set(registered)
    if missing := sorted(directory_names - registered_names):
        result.error(f"{registry_path}: unregistered skill directories: {', '.join(missing)}")
    if missing := sorted(registered_names - directory_names):
        result.error(f"{registry_path}: registered skills without directories: {', '.join(missing)}")

    used = set(registry.get("baseline_for_project_tasks", []))
    used.update(registry.get("baseline_for_code_changes", []))
    routes = registry.get("task_routes", {})
    if not isinstance(routes, dict):
        result.error(f"{registry_path}: task_routes must be an object")
        routes = {}
    for route, skills in routes.items():
        if not isinstance(skills, list) or not skills:
            result.error(f"{registry_path}: task route {route} must be non-empty")
            continue
        if len(skills) != len(set(skills)):
            result.error(f"{registry_path}: task route {route} contains duplicates")
        used.update(skills)
    if unused := sorted(registered_names - used):
        result.error(f"{registry_path}: registered but unrouted skills: {', '.join(unused)}")

    global_skills = Path.home() / ".agents/skills"
    if global_skills.is_dir():
        external = sorted(used - registered_names)
        missing_external = [name for name in external if not (global_skills / name / "SKILL.md").is_file()]
        if missing_external:
            result.error(
                f"{registry_path}: routed global skills are unavailable: {', '.join(missing_external)}"
            )


def audit_sync(repo_root: Path, canonical: Path, result: AuditResult) -> None:
    canonical_files = comparable_files(canonical)
    for agent_dir in (".codex", ".claude"):
        synchronized = repo_root / agent_dir / "skills"
        if not synchronized.is_dir():
            result.error(f"{synchronized}: synchronized skill directory is missing")
            continue
        if canonical_files != comparable_files(synchronized):
            result.error(f"{synchronized}: skills are not synchronized with .agents/skills")


def audit(repo_root: Path) -> AuditResult:
    result = AuditResult()
    canonical = repo_root / ".agents/skills"
    skill_dirs = sorted(path for path in canonical.iterdir() if path.is_dir())
    result.skills = len(skill_dirs)

    for skill_dir in skill_dirs:
        skill_path = skill_dir / "SKILL.md"
        if not skill_path.is_file():
            result.error(f"{skill_dir}: SKILL.md is required")
            continue
        frontmatter, body = parse_frontmatter(skill_path, result)
        name = frontmatter.get("name", "")
        description = frontmatter.get("description", "")
        if name != skill_dir.name:
            result.error(f"{skill_path}: name must match directory {skill_dir.name}")
        if not NAME_PATTERN.fullmatch(name):
            result.error(f"{skill_path}: name must use lowercase hyphen-case")
        if not description:
            result.error(f"{skill_path}: description is required")
        elif len(description) > 1024 or "<" in description or ">" in description:
            result.error(f"{skill_path}: description violates skill metadata limits")
        if len(body.strip()) < 100:
            result.error(f"{skill_path}: instruction body is too small to be actionable")
        line_count = len(skill_path.read_text(encoding="utf-8").splitlines())
        if line_count > MAX_SKILL_LINES:
            result.error(
                f"{skill_path}: {line_count} lines exceeds the {MAX_SKILL_LINES}-line context budget"
            )

        audit_openai_metadata(skill_dir, skill_dir.name, result)
        audit_markdown_links(skill_dir, result)
        audit_scripts(skill_dir, result)

    audit_registry(repo_root, skill_dirs, result)
    audit_sync(repo_root, canonical, result)
    return result


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument(
        "--repo",
        type=Path,
        default=Path(__file__).resolve().parents[2],
        help="repository root",
    )
    parser.add_argument("--json", action="store_true", help="emit machine-readable output")
    args = parser.parse_args()

    result = audit(args.repo.resolve())
    payload = {
        "skills": result.skills,
        "scripts": result.scripts,
        "errors": sorted(result.errors),
        "warnings": sorted(result.warnings),
    }
    if args.json:
        print(json.dumps(payload, ensure_ascii=False, indent=2))
    else:
        for error in payload["errors"]:
            print(f"ERROR: {error}")
        for warning in payload["warnings"]:
            print(f"WARNING: {warning}")
        print(
            f"AI skills audit: {result.skills} skills, {result.scripts} scripts, "
            f"{len(result.errors)} errors, {len(result.warnings)} warnings"
        )
    return 1 if result.errors else 0


if __name__ == "__main__":
    raise SystemExit(main())
