#!/usr/bin/env python3
"""Validate a redacted external-integration contract."""

from __future__ import annotations

import argparse
import json
import re
from pathlib import Path
from typing import Any

REQUIRED = {
    "name",
    "owner",
    "purpose",
    "direction",
    "endpoints",
    "authentication",
    "data_classes",
    "timeout_seconds",
    "retry",
    "idempotency",
    "rate_limit",
    "observability",
    "degradation",
    "disable_switch",
}
SECRET_PATTERN = re.compile(r"(token|secret|password|private[_ -]?key)\s*[:=]\s*['\"]?[A-Za-z0-9_+/=-]{12,}", re.I)


def validate(contract: dict[str, Any]) -> list[str]:
    errors = [f"missing field: {name}" for name in sorted(REQUIRED - contract.keys())]
    if contract.get("direction") not in {"inbound", "outbound", "bidirectional"}:
        errors.append("direction must be inbound, outbound, or bidirectional")
    timeout = contract.get("timeout_seconds")
    if not isinstance(timeout, (int, float)) or isinstance(timeout, bool) or timeout <= 0:
        errors.append("timeout_seconds must be positive")
    retry = contract.get("retry")
    attempts = retry.get("max_attempts") if isinstance(retry, dict) else None
    if not isinstance(attempts, int) or isinstance(attempts, bool) or attempts < 1:
        errors.append("retry.max_attempts must be a positive integer")
    serialized = json.dumps(contract, ensure_ascii=False)
    if SECRET_PATTERN.search(serialized):
        errors.append("contract appears to contain a secret value")
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
