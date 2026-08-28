#!/usr/bin/env python3
"""Validate the hs-manacost typography and layout contract."""

from __future__ import annotations

import argparse
import json
from pathlib import Path
from typing import Any

SPACING_SCALE = [4, 8, 12, 16, 24, 32, 48, 64]
REQUIRED_WIDTHS = {320, 390, 768, 1024, 1440}
REQUIRED_ROLES = {
    "body",
    "navigation",
    "card-title",
    "article-title",
    "button",
    "form-control",
}
ALLOWED_OWNERS = {
    "website-manager",
    "composer",
    "cloud-template",
    "mu-plugin",
    "child-theme",
}


def _mapping(value: Any, path: str, errors: list[str]) -> dict[str, Any]:
    if not isinstance(value, dict):
        errors.append(f"{path} must be an object")
        return {}
    return value


def _integer_list(value: Any, path: str, errors: list[str]) -> list[int]:
    if not isinstance(value, list) or any(type(item) is not int for item in value):
        errors.append(f"{path} must be an array of integers")
        return []
    return value


def _string_set(value: Any, path: str, errors: list[str]) -> set[str]:
    if not isinstance(value, list) or any(not isinstance(item, str) for item in value):
        errors.append(f"{path} must be an array of strings")
        return set()
    return set(value)


def validate(contract: Any) -> list[str]:
    """Return contract errors. An empty list means the contract is valid."""
    errors: list[str] = []
    root = _mapping(contract, "contract", errors)

    if root.get("schema_version") != 1:
        errors.append("schema_version must be 1")
    if root.get("project") != "hs-manacost":
        errors.append("project must be hs-manacost")

    typography = _mapping(root.get("typography"), "typography", errors)
    if typography.get("cyrillic_required") is not True:
        errors.append("typography.cyrillic_required must be true")
    if typography.get("fallback_required") is not True:
        errors.append("typography.fallback_required must be true")
    roles = _string_set(typography.get("roles"), "typography.roles", errors)
    missing_roles = REQUIRED_ROLES - roles
    if missing_roles:
        errors.append(f"typography.roles missing: {', '.join(sorted(missing_roles))}")
    type_scale = _integer_list(
        typography.get("type_scale"), "typography.type_scale", errors
    )
    if type_scale != sorted(set(type_scale)) or any(
        size < 12 or size > 64 for size in type_scale
    ):
        errors.append(
            "typography.type_scale must be unique, ascending, and between 12 and 64"
        )

    fonts = _mapping(root.get("fonts"), "fonts", errors)
    if fonts.get("policy") != "system-or-licensed-local":
        errors.append("fonts.policy must be system-or-licensed-local")
    if fonts.get("remote_external") is not False:
        errors.append("fonts.remote_external must be false")
    displays = _string_set(
        fonts.get("allowed_display"), "fonts.allowed_display", errors
    )
    if not displays or not displays.issubset({"swap", "optional"}):
        errors.append("fonts.allowed_display may only contain swap and optional")
    maximum_weights = fonts.get("maximum_weights")
    if type(maximum_weights) is not int or not 1 <= maximum_weights <= 4:
        errors.append("fonts.maximum_weights must be between 1 and 4")

    spacing = _mapping(root.get("spacing"), "spacing", errors)
    if spacing.get("base_unit") != 4:
        errors.append("spacing.base_unit must be 4")
    scale = _integer_list(spacing.get("scale"), "spacing.scale", errors)
    if scale != SPACING_SCALE:
        errors.append(f"spacing.scale must equal {SPACING_SCALE}")

    containers = _mapping(root.get("containers"), "containers", errors)
    expected_containers = {
        "desktop_px": 1068,
        "landscape_px": 980,
        "tablet_px": 740,
        "mobile_gutter_px": 20,
    }
    for key, expected in expected_containers.items():
        if containers.get(key) != expected:
            errors.append(f"containers.{key} must be {expected}")
    measure = _mapping(
        containers.get("article_measure_ch"), "containers.article_measure_ch", errors
    )
    if measure.get("minimum") != 55 or measure.get("maximum") != 75:
        errors.append("containers.article_measure_ch must span 55 to 75")

    grid = _mapping(root.get("grid"), "grid", errors)
    if grid.get("columns") != 12:
        errors.append("grid.columns must be 12")
    breakpoints = set(
        _integer_list(
            grid.get("newspaper_breakpoints"), "grid.newspaper_breakpoints", errors
        )
    )
    if breakpoints != {767, 1018, 1140}:
        errors.append("grid.newspaper_breakpoints must be 767, 1018, and 1140")

    runtime = _mapping(root.get("runtime"), "runtime", errors)
    if runtime.get("manacost_font_trim_reviewed") is not True:
        errors.append("runtime.manacost_font_trim_reviewed must be true")
    if runtime.get("anonymous_computed_style_required") is not True:
        errors.append("runtime.anonymous_computed_style_required must be true")

    implementation = _mapping(root.get("implementation"), "implementation", errors)
    owners = _string_set(
        implementation.get("ownership_layers"),
        "implementation.ownership_layers",
        errors,
    )
    unsupported = owners - ALLOWED_OWNERS
    if unsupported:
        errors.append(
            f"implementation.ownership_layers contains unsupported layer: {', '.join(sorted(unsupported))}"
        )
    if not owners:
        errors.append("implementation.ownership_layers must not be empty")

    quality = _mapping(root.get("quality"), "quality", errors)
    maximum_cls = quality.get("maximum_cls")
    if type(maximum_cls) not in (int, float) or not 0 <= maximum_cls <= 0.1:
        errors.append("quality.maximum_cls must be at most 0.1")
    widths = set(
        _integer_list(quality.get("required_widths"), "quality.required_widths", errors)
    )
    if not REQUIRED_WIDTHS.issubset(widths):
        errors.append(
            "quality.required_widths must include 320, 390, 768, 1024, and 1440"
        )
    zoom_percent = quality.get("zoom_percent")
    if type(zoom_percent) is not int or zoom_percent < 200:
        errors.append("quality.zoom_percent must be at least 200")

    hosts = _mapping(root.get("hosts"), "hosts", errors)
    expected_hosts = {
        "canonical": "hs-manacost.ru",
        "mirror": "hs-manacost.com",
        "staging": "test.hs-manacost.ru",
    }
    for key, expected in expected_hosts.items():
        if hosts.get(key) != expected:
            errors.append(f"hosts.{key} must be {expected}")
    for key in ("mirror_noindex", "staging_noindex"):
        if hosts.get(key) is not True:
            errors.append(f"hosts.{key} must be true")

    return errors


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("contract", type=Path)
    args = parser.parse_args()
    try:
        contract = json.loads(args.contract.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as error:
        print(f"ERROR: {error}")
        return 2

    errors = validate(contract)
    if errors:
        for error in errors:
            print(f"ERROR: {error}")
        return 1
    print(f"OK: {args.contract}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
