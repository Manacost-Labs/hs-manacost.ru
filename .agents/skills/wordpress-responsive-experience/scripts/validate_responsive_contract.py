#!/usr/bin/env python3
"""Validate the hs-manacost responsive experience contract."""

from __future__ import annotations

import argparse
import json
from pathlib import Path
from typing import Any

REQUIRED_WIDTHS = {320, 390, 768, 1024, 1440}
REQUIRED_DIMENSIONS = {
    "content",
    "actions",
    "brand",
    "seo",
    "ads",
    "analytics",
    "views",
}
REQUIRED_CASES = {
    "long-cyrillic",
    "missing-image",
    "slow-image",
    "ads",
    "embeds",
    "authenticated",
    "loading",
    "empty",
    "error",
}
ALLOWED_OWNERS = {"composer", "cloud-template", "mu-plugin", "child-theme"}


def _mapping(value: Any, path: str, errors: list[str]) -> dict[str, Any]:
    if not isinstance(value, dict):
        errors.append(f"{path} must be an object")
        return {}
    return value


def _integer_set(value: Any, path: str, errors: list[str]) -> set[int]:
    if not isinstance(value, list) or any(type(item) is not int for item in value):
        errors.append(f"{path} must be an array of integers")
        return set()
    return set(value)


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

    parity = _mapping(root.get("parity"), "parity", errors)
    if parity.get("allow_content_removal") is not False:
        errors.append("parity.allow_content_removal must be false")
    dimensions = _string_set(
        parity.get("required_dimensions"), "parity.required_dimensions", errors
    )
    missing_dimensions = REQUIRED_DIMENSIONS - dimensions
    if missing_dimensions:
        errors.append(
            f"parity.required_dimensions missing: {', '.join(sorted(missing_dimensions))}"
        )

    responsive = _mapping(root.get("responsive"), "responsive", errors)
    widths = _integer_set(
        responsive.get("required_widths"), "responsive.required_widths", errors
    )
    missing_widths = REQUIRED_WIDTHS - widths
    if missing_widths:
        errors.append(
            f"responsive.required_widths missing: {', '.join(map(str, sorted(missing_widths)))}"
        )
    if responsive.get("intermediate_sweep") is not True:
        errors.append("responsive.intermediate_sweep must be true")
    zoom_percent = responsive.get("zoom_percent")
    if type(zoom_percent) is not int or zoom_percent < 200:
        errors.append("responsive.zoom_percent must be at least 200")
    touch_target = responsive.get("minimum_touch_target_px")
    if type(touch_target) is not int or touch_target < 44:
        errors.append("responsive.minimum_touch_target_px must be at least 44")
    tolerance = responsive.get("overflow_tolerance_px")
    if type(tolerance) is not int or not 0 <= tolerance <= 1:
        errors.append("responsive.overflow_tolerance_px must be 0 or 1")
    orientations = _string_set(
        responsive.get("orientations"), "responsive.orientations", errors
    )
    if not {"portrait", "landscape"}.issubset(orientations):
        errors.append("responsive.orientations must include portrait and landscape")

    content_cases = _string_set(root.get("content_cases"), "content_cases", errors)
    missing_cases = REQUIRED_CASES - content_cases
    if missing_cases:
        errors.append(f"content_cases missing: {', '.join(sorted(missing_cases))}")

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
    breakpoints = _integer_set(
        implementation.get("newspaper_breakpoints"),
        "implementation.newspaper_breakpoints",
        errors,
    )
    if breakpoints != {767, 1018, 1140}:
        errors.append(
            "implementation.newspaper_breakpoints must be 767, 1018, and 1140"
        )

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
