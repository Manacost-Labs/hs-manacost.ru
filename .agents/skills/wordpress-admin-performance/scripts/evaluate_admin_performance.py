#!/usr/bin/env python3
"""Evaluate non-secret before/after wp-admin performance evidence."""

from __future__ import annotations

import json
import sys
from pathlib import Path
from typing import Any

REQUIRED_METRICS = {
    "ttfb_ms",
    "interactive_ms",
    "sql_queries",
    "peak_memory_mb",
    "long_tasks",
}
MAX_BUDGETS = {
    "ttfb_ms": 1500.0,
    "interactive_ms": 2500.0,
    "sql_queries": 120.0,
    "peak_memory_mb": 160.0,
    "long_tasks": 4.0,
}
REQUIRED_CHECKS = {"behavior", "permissions", "desktop", "mobile", "error_path"}
ALLOWED_ENVIRONMENTS = {"local", "integration", "staging"}
ALLOWED_CACHE_STATES = {"cold", "warm"}
FORBIDDEN_KEY_PARTS = {"authorization", "cookie", "nonce", "password", "secret", "token"}


def fail(message: str) -> None:
    raise ValueError(message)


def is_number(value: object) -> bool:
    return isinstance(value, (int, float)) and not isinstance(value, bool)


def reject_sensitive_keys(value: object, path: str = "root") -> None:
    if isinstance(value, dict):
        for raw_key, child in value.items():
            key = str(raw_key).lower()
            if any(part in key for part in FORBIDDEN_KEY_PARTS):
                fail(f"sensitive field is forbidden: {path}.{raw_key}")
            reject_sensitive_keys(child, f"{path}.{raw_key}")
    elif isinstance(value, list):
        for index, child in enumerate(value):
            reject_sensitive_keys(child, f"{path}[{index}]")


def require_string(report: dict[str, Any], key: str) -> str:
    value = report.get(key)
    if not isinstance(value, str) or not value.strip():
        fail(f"{key} must be a non-empty string")
    return value.strip()


def validate_context(report: dict[str, Any]) -> None:
    if report.get("schema_version") != 1:
        fail("schema_version must be 1")
    environment = require_string(report, "environment")
    if environment not in ALLOWED_ENVIRONMENTS:
        fail("environment must be local, integration, or staging")
    require_string(report, "screen")
    require_string(report, "authenticated_role")
    cache_state = require_string(report, "cache_state")
    if cache_state not in ALLOWED_CACHE_STATES:
        fail("cache_state must be cold or warm")

    dataset_size = report.get("dataset_size")
    if not isinstance(dataset_size, int) or isinstance(dataset_size, bool) or dataset_size < 1:
        fail("dataset_size must be a positive integer")
    sample_count = report.get("sample_count")
    if not isinstance(sample_count, int) or isinstance(sample_count, bool) or sample_count < 5:
        fail("sample_count must be an integer of at least 5")


def evaluate_metrics(report: dict[str, Any]) -> tuple[list[dict[str, Any]], list[str]]:
    raw_metrics = report.get("metrics")
    if not isinstance(raw_metrics, list) or not raw_metrics:
        fail("metrics must be a non-empty list")

    normalized: list[dict[str, Any]] = []
    reasons: list[str] = []
    names: set[str] = set()
    for index, raw_metric in enumerate(raw_metrics):
        if not isinstance(raw_metric, dict):
            fail(f"metrics[{index}] must be an object")
        name = require_string(raw_metric, "name")
        if name in names:
            fail(f"metric names must be unique: {name}")
        names.add(name)
        unit = require_string(raw_metric, "unit")

        values: dict[str, float] = {}
        for field in ("before", "after", "budget"):
            raw_value = raw_metric.get(field)
            if not is_number(raw_value) or float(raw_value) < 0:
                fail(f"{name}.{field} must be a non-negative number")
            values[field] = float(raw_value)

        maximum_budget = MAX_BUDGETS.get(name)
        if maximum_budget is not None and values["budget"] > maximum_budget:
            fail(f"{name}.budget exceeds the approved maximum {maximum_budget:g}")
        if values["after"] > values["budget"]:
            reasons.append(
                f"{name}: after {values['after']:g} {unit} exceeds budget "
                f"{values['budget']:g} {unit}"
            )
        if values["after"] > values["before"] * 1.05:
            reasons.append(
                f"{name}: after {values['after']:g} {unit} regresses more than 5% "
                f"from {values['before']:g} {unit}"
            )

        normalized.append({"name": name, "unit": unit, **values})

    missing = sorted(REQUIRED_METRICS - names)
    if missing:
        fail(f"missing required metrics: {', '.join(missing)}")
    return normalized, reasons


def evaluate_checks(report: dict[str, Any]) -> list[str]:
    checks = report.get("functional_checks")
    if not isinstance(checks, dict):
        fail("functional_checks must be an object")
    missing = sorted(REQUIRED_CHECKS - set(checks))
    if missing:
        fail(f"missing functional checks: {', '.join(missing)}")
    invalid = sorted(key for key in REQUIRED_CHECKS if checks.get(key) is not True)
    return [f"functional check failed: {key}" for key in invalid]


def evaluate(report: object) -> dict[str, Any]:
    if not isinstance(report, dict):
        fail("report root must be an object")
    reject_sensitive_keys(report)
    validate_context(report)
    metrics, reasons = evaluate_metrics(report)
    reasons.extend(evaluate_checks(report))
    return {
        "status": "BLOCKED" if reasons else "PASS",
        "environment": report["environment"],
        "screen": report["screen"],
        "sample_count": report["sample_count"],
        "metrics": metrics,
        "reasons": reasons,
    }


def main() -> int:
    if len(sys.argv) != 2:
        print("usage: evaluate_admin_performance.py REPORT.json", file=sys.stderr)
        return 2
    try:
        report = json.loads(Path(sys.argv[1]).read_text(encoding="utf-8"))
        result = evaluate(report)
    except (OSError, json.JSONDecodeError, ValueError) as error:
        print(json.dumps({"status": "INVALID", "error": str(error)}, ensure_ascii=False))
        return 2
    print(json.dumps(result, ensure_ascii=False, indent=2, sort_keys=True))
    return 0 if result["status"] == "PASS" else 1


if __name__ == "__main__":
    raise SystemExit(main())
