#!/usr/bin/env python3
"""Build evaluator-compatible wp-admin evidence from raw browser samples."""

from __future__ import annotations

import json
import statistics
import sys
from pathlib import Path
from typing import Never, TypeAlias, cast

JsonScalar: TypeAlias = None | bool | int | float | str
JsonValue: TypeAlias = JsonScalar | list["JsonValue"] | dict[str, "JsonValue"]
JsonObject: TypeAlias = dict[str, JsonValue]


METRICS = (
    "ttfb_ms",
    "interactive_ms",
    "sql_queries",
    "peak_memory_mb",
    "long_tasks",
)
CHECKS = ("behavior", "permissions", "desktop", "mobile", "error_path")
FORBIDDEN_KEY_PARTS = (
    "authorization",
    "cookie",
    "nonce",
    "password",
    "secret",
    "token",
)


def fail(message: str) -> Never:
    raise ValueError(message)


def load_json(path: Path) -> JsonValue:
    return cast(JsonValue, json.loads(path.read_text(encoding="utf-8")))


def reject_sensitive_keys(value: object, path: str = "root") -> None:
    if isinstance(value, dict):
        mapping = cast(dict[object, object], value)
        for raw_key, child in mapping.items():
            key = str(raw_key).lower()
            if any(part in key for part in FORBIDDEN_KEY_PARTS):
                fail(f"sensitive field is forbidden: {path}.{raw_key}")
            reject_sensitive_keys(child, f"{path}.{raw_key}")
    elif isinstance(value, list):
        sequence = cast(list[object], value)
        for index, child in enumerate(sequence):
            reject_sensitive_keys(child, f"{path}[{index}]")


def require_string(value: JsonObject, key: str) -> str:
    result = value.get(key)
    if not isinstance(result, str) or not result.strip():
        fail(f"{key} must be a non-empty string")
    return result.strip()


def require_number(value: object, path: str) -> float:
    if isinstance(value, bool) or not isinstance(value, (int, float)):
        fail(f"{path} must be a number")
    result = float(value)
    if result < 0:
        fail(f"{path} must be non-negative")
    return result


def validate_raw(raw: object) -> JsonObject:
    if not isinstance(raw, dict):
        fail("raw evidence root must be an object")
    raw_object = cast(JsonObject, raw)
    reject_sensitive_keys(raw_object)
    if raw_object.get("schema_version") != 1:
        fail("raw schema_version must be 1")
    if require_string(raw_object, "environment") not in {
        "local",
        "integration",
        "staging",
    }:
        fail("environment must be local, integration, or staging")
    _ = require_string(raw_object, "screen")
    _ = require_string(raw_object, "authenticated_role")
    if require_string(raw_object, "cache_state") not in {"cold", "warm"}:
        fail("cache_state must be cold or warm")
    _ = require_string(raw_object, "viewport")

    dataset_size = raw_object.get("dataset_size")
    if (
        isinstance(dataset_size, bool)
        or not isinstance(dataset_size, int)
        or dataset_size < 1
    ):
        fail("dataset_size must be a positive integer")

    samples = raw_object.get("samples")
    if not isinstance(samples, list) or len(samples) < 5:
        fail("samples must contain at least five entries")
    for sample_index, sample_value in enumerate(samples):
        sample = sample_value
        if not isinstance(sample, dict):
            fail(f"samples[{sample_index}] must be an object")
        sample_object = cast(JsonObject, sample)
        for metric in METRICS:
            _ = require_number(
                sample_object.get(metric), f"samples[{sample_index}].{metric}"
            )

    checks = raw_object.get("functional_checks")
    if not isinstance(checks, dict):
        fail("functional_checks must be an object")
    check_object = cast(JsonObject, checks)
    for check in CHECKS:
        if not isinstance(check_object.get(check), bool):
            fail(f"functional_checks.{check} must be a boolean")
    return raw_object


def validate_budgets(
    value: object, screen: str, environment: str
) -> dict[str, JsonObject]:
    if not isinstance(value, dict):
        fail("budget schema_version must be 1")
    budget_object = cast(JsonObject, value)
    if budget_object.get("schema_version") != 1:
        fail("budget schema_version must be 1")
    profiles = budget_object.get("profiles")
    screens_value: JsonValue = None
    if isinstance(profiles, dict):
        environment_profile = profiles.get(environment)
        if isinstance(environment_profile, dict):
            screens_value = environment_profile.get("screens")
    if screens_value is None:
        screens_value = budget_object.get("screens")
    if not isinstance(screens_value, dict):
        fail(f"no approved budgets for screen: {screen}")
    screens = cast(JsonObject, screens_value)
    screen_value = screens.get(screen)
    if not isinstance(screen_value, dict):
        fail(f"no approved budgets for screen: {screen}")
    screen_budgets = cast(JsonObject, screen_value)
    validated: dict[str, JsonObject] = {}
    for metric in METRICS:
        config_value = screen_budgets.get(metric)
        if not isinstance(config_value, dict):
            fail(f"missing budget for {screen}.{metric}")
        config = cast(JsonObject, config_value)
        _ = require_number(config.get("baseline"), f"{screen}.{metric}.baseline")
        _ = require_number(config.get("budget"), f"{screen}.{metric}.budget")
        _ = require_string(config, "unit")
        validated[metric] = config
    return validated


def build_report(raw_value: object, budget_value: object) -> dict[str, object]:
    raw = validate_raw(raw_value)
    screen = require_string(raw, "screen")
    screen_budgets = validate_budgets(
        budget_value, screen, require_string(raw, "environment")
    )
    samples = cast(list[JsonValue], raw["samples"])

    metrics: list[dict[str, object]] = []
    for metric in METRICS:
        config = screen_budgets[metric]
        values: list[float] = []
        for sample_value in samples:
            sample = cast(JsonObject, sample_value)
            values.append(require_number(sample[metric], metric))
        metrics.append(
            {
                "name": metric,
                "unit": config["unit"],
                "before": require_number(config["baseline"], f"{metric}.baseline"),
                "after": float(statistics.median(values)),
                "budget": require_number(config["budget"], f"{metric}.budget"),
            }
        )

    return {
        "schema_version": 1,
        "environment": raw["environment"],
        "screen": screen,
        "authenticated_role": raw["authenticated_role"],
        "dataset_size": raw["dataset_size"],
        "sample_count": len(samples),
        "cache_state": raw["cache_state"],
        "metrics": metrics,
        "functional_checks": raw["functional_checks"],
    }


def main() -> int:
    if len(sys.argv) != 3:
        print(
            "usage: build-admin-performance-report.py RAW.json BUDGETS.json",
            file=sys.stderr,
        )
        return 2
    try:
        report = build_report(
            load_json(Path(sys.argv[1])), load_json(Path(sys.argv[2]))
        )
    except (OSError, json.JSONDecodeError, ValueError) as error:
        print(
            json.dumps({"status": "INVALID", "error": str(error)}, ensure_ascii=False)
        )
        return 2
    print(json.dumps(report, ensure_ascii=False, indent=2, sort_keys=True))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
