#!/usr/bin/env python3
"""Validate a redacted pre-publication evidence manifest."""

from __future__ import annotations

import argparse
import json
from pathlib import Path
from typing import Any

BASELINE_CHECKS = {
    "editor_save_revision",
    "rendered_content",
    "media_s3",
    "seo_hosts",
    "mobile_accessibility",
    "ads_analytics",
    "cache_delivery",
    "rollback",
}
VALID_STATES = {"pass", "fail", "not_applicable"}


def evaluate(manifest: dict[str, Any]) -> dict[str, Any]:
    errors: list[str] = []
    checks = manifest.get("checks")
    if not str(manifest.get("subject", "")).strip():
        errors.append("subject is required")
    if not str(manifest.get("target", "")).strip():
        errors.append("target is required")
    if not isinstance(checks, dict):
        return {"result": "BLOCKED", "errors": [*errors, "checks must be an object"]}

    for check_id in sorted(BASELINE_CHECKS - checks.keys()):
        errors.append(f"missing baseline check: {check_id}")

    blocking_failure = False
    notes = False
    for check_id, check in checks.items():
        if not isinstance(check, dict):
            errors.append(f"{check_id}: check must be an object")
            blocking_failure = True
            continue
        status = check.get("status")
        blocking = check.get("blocking", True)
        if status not in VALID_STATES:
            errors.append(f"{check_id}: invalid status")
            blocking_failure = True
        if not str(check.get("evidence", "")).strip():
            errors.append(f"{check_id}: evidence is required")
            blocking_failure = True
        if status == "not_applicable" and not str(check.get("reason", "")).strip():
            errors.append(f"{check_id}: not_applicable requires reason")
            blocking_failure = True
        if status == "fail":
            if blocking:
                blocking_failure = True
            else:
                notes = True

    rollback = manifest.get("rollback")
    if not isinstance(rollback, dict):
        errors.append("rollback must be an object")
        blocking_failure = True
    else:
        if not str(rollback.get("revision", "")).strip():
            errors.append("rollback.revision is required")
            blocking_failure = True
        commit = str(rollback.get("commit", ""))
        if len(commit) != 40 or any(char not in "0123456789abcdef" for char in commit.lower()):
            errors.append("rollback.commit must be a full 40-character Git SHA")
            blocking_failure = True

    if errors or blocking_failure:
        result = "BLOCKED"
    elif notes:
        result = "READY_WITH_NOTES"
    else:
        result = "READY"
    return {"result": result, "errors": errors}


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("manifest", type=Path)
    args = parser.parse_args()
    result = evaluate(json.loads(args.manifest.read_text(encoding="utf-8")))
    print(json.dumps(result, ensure_ascii=False, indent=2))
    return 0 if result["result"] != "BLOCKED" else 1


if __name__ == "__main__":
    raise SystemExit(main())
