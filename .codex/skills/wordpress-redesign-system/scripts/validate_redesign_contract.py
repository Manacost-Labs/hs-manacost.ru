#!/usr/bin/env python3
"""Validate a redacted hs-manacost redesign contract."""

from __future__ import annotations

import argparse
import json
import re
from pathlib import Path
from typing import Any

REQUIRED_SECTIONS = {
    "project",
    "audience",
    "goals",
    "user_journeys",
    "pages",
    "direction",
    "tokens",
    "components",
    "responsive",
    "quality",
    "implementation",
    "rollout",
}
REQUIRED_VIEWPORTS = {320, 390, 768, 1024, 1440}
SUPPORTED_LAYERS = {
    "composer",
    "cloud-template",
    "mu-plugin",
    "public-hook",
    "theme-api",
    "child-theme",
}
HEX_COLOR = re.compile(r"^#[0-9a-fA-F]{6}$")
FULL_GIT_SHA = re.compile(r"^[0-9a-fA-F]{40}$")


def non_empty(value: Any) -> bool:
    if isinstance(value, str):
        return bool(value.strip())
    if isinstance(value, (list, dict)):
        return bool(value)
    return value is not None


def validate(contract: Any) -> list[str]:
    if not isinstance(contract, dict):
        return ["contract must be an object"]

    errors = [
        f"missing section: {section}"
        for section in sorted(REQUIRED_SECTIONS - contract.keys())
    ]
    for section in REQUIRED_SECTIONS & contract.keys():
        if not non_empty(contract[section]):
            errors.append(f"section must not be empty: {section}")

    direction = contract.get("direction")
    for field in ("name", "thesis", "signature", "rationale", "avoid"):
        if not isinstance(direction, dict) or not non_empty(direction.get(field)):
            errors.append(f"direction.{field} is required")

    goals = contract.get("goals")
    if not isinstance(goals, list) or any(
        not isinstance(goal, str) or not goal.strip() for goal in goals
    ):
        errors.append("goals must be a non-empty list of strings")

    for collection_name, required_fields in (
        ("user_journeys", ("name", "entry", "success")),
        ("pages", ("template", "job")),
        ("components", ("name", "states")),
    ):
        records = contract.get(collection_name)
        if not isinstance(records, list) or not records:
            errors.append(f"{collection_name} must be a non-empty list")
            continue
        for index, record in enumerate(records):
            if not isinstance(record, dict):
                errors.append(f"{collection_name}[{index}] must be an object")
                continue
            for field in required_fields:
                if not non_empty(record.get(field)):
                    errors.append(f"{collection_name}[{index}].{field} is required")

    tokens = contract.get("tokens")
    if not isinstance(tokens, dict):
        errors.append("tokens must be an object")
    else:
        for field in ("colors", "typography", "spacing", "radii", "shadows", "motion"):
            if not non_empty(tokens.get(field)):
                errors.append(f"tokens.{field} is required")
        colors = tokens.get("colors")
        if isinstance(colors, dict):
            for name in ("surface", "text", "accent", "border"):
                if name not in colors:
                    errors.append(f"tokens.colors.{name} is required")
            for name, value in colors.items():
                if not isinstance(value, str) or not HEX_COLOR.fullmatch(value):
                    errors.append(f"tokens.colors.{name} must be a six-digit hex color")
        typography = tokens.get("typography")
        for role in ("display", "body", "utility", "scale", "rationale"):
            if not isinstance(typography, dict) or not non_empty(typography.get(role)):
                errors.append(f"tokens.typography.{role} is required")

    responsive = contract.get("responsive")
    viewports = responsive.get("viewports", []) if isinstance(responsive, dict) else []
    viewport_values = (
        {
            value
            for value in viewports
            if isinstance(value, int) and not isinstance(value, bool)
        }
        if isinstance(viewports, list)
        else set()
    )
    if not REQUIRED_VIEWPORTS.issubset(viewport_values):
        errors.append("responsive.viewports must include 320, 390, 768, 1024, and 1440")
    if not isinstance(responsive, dict) or responsive.get("zoom_percent") != 200:
        errors.append("responsive.zoom_percent must be 200")
    if not isinstance(responsive, dict) or responsive.get("horizontal_overflow_px") != 0:
        errors.append("responsive.horizontal_overflow_px must be 0")

    quality = contract.get("quality")
    if not isinstance(quality, dict):
        errors.append("quality must be an object")
    else:
        for field in ("accessibility", "performance", "seo", "media", "ads", "analytics"):
            if not non_empty(quality.get(field)):
                errors.append(f"quality.{field} is required")

    implementation = contract.get("implementation")
    layers = (
        implementation.get("ownership_layers", [])
        if isinstance(implementation, dict)
        else []
    )
    if not isinstance(layers, list) or not layers:
        errors.append("implementation.ownership_layers is required")
    elif any(
        not isinstance(layer, str) or layer not in SUPPORTED_LAYERS
        for layer in layers
    ):
        errors.append("implementation.ownership_layers contains an unsupported layer")
    if not isinstance(implementation, dict) or not non_empty(
        implementation.get("vertical_slices")
    ):
        errors.append("implementation.vertical_slices is required")

    rollout = contract.get("rollout")
    for field in ("staging_evidence", "approval_owner", "rollback_commit", "template_restore"):
        if not isinstance(rollout, dict) or not non_empty(rollout.get(field)):
            errors.append(f"rollout.{field} is required")
    rollback_commit = (
        rollout.get("rollback_commit") if isinstance(rollout, dict) else None
    )
    if not isinstance(rollback_commit, str) or not FULL_GIT_SHA.fullmatch(rollback_commit):
        errors.append("rollout.rollback_commit must be a full 40-character Git SHA")

    invariants = json.dumps(contract.get("quality", {}), ensure_ascii=False).lower()
    for marker in ("hs-manacost.ru", "hs-manacost.com", "test.hs-manacost.ru", "noindex"):
        if marker not in invariants:
            errors.append(f"quality contract must mention {marker}")

    return errors


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("contract", type=Path)
    args = parser.parse_args()
    errors = validate(json.loads(args.contract.read_text(encoding="utf-8")))
    print(json.dumps({"errors": errors}, ensure_ascii=False, indent=2))
    return 1 if errors else 0


if __name__ == "__main__":
    raise SystemExit(main())
