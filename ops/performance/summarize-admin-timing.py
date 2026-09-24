#!/usr/bin/env python3
"""Aggregate bounded wp-admin Nginx timings without exposing request metadata."""

from __future__ import annotations

import argparse
import json
import math
import re
import statistics
from collections import defaultdict
from datetime import datetime
from pathlib import Path


ROUTES = {
    ("GET", "/wp-admin/index.php"): "dashboard",
    ("GET", "/wp-admin/edit.php"): "posts-list",
    ("GET", "/wp-admin/post.php"): "article-open",
    ("POST", "/wp-admin/post.php"): "article-save",
    ("GET", "/wp-admin/post-new.php"): "article-create",
    ("GET", "/wp-admin/upload.php"): "media-library",
    ("GET", "/wp-admin/plugins.php"): "plugins",
    ("GET", "/wp-admin/users.php"): "users",
    ("GET", "/wp-admin/options-general.php"): "settings",
    ("GET", "/wp-admin/admin.php"): "plugin-settings",
}
REQUEST = re.compile(r"^(GET|POST) (/wp-admin/[a-z-]+\.php) HTTP/[0-9.]+$")
MIN_P95_SAMPLES = 20


def timestamp(value: str) -> datetime:
    try:
        parsed = datetime.fromisoformat(value)
    except ValueError as error:
        raise argparse.ArgumentTypeError("expected an ISO 8601 timestamp") from error
    if parsed.tzinfo is None:
        raise argparse.ArgumentTypeError("timestamp must include a UTC offset")
    return parsed


def percentile(values: list[float], fraction: float) -> int:
    return round(values[math.ceil(fraction * len(values)) - 1] * 1000)


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("log", type=Path, help="JSON timing access log")
    parser.add_argument("--since", required=True, type=timestamp)
    parser.add_argument("--until", required=True, type=timestamp)
    args = parser.parse_args()
    if args.since >= args.until:
        parser.error("--since must precede --until")

    durations: dict[str, list[float]] = defaultdict(list)
    total: dict[str, int] = defaultdict(int)
    errors: dict[str, int] = defaultdict(int)
    malformed = 0
    with args.log.open(encoding="utf-8", errors="replace") as stream:
        for line in stream:
            try:
                entry = json.loads(line)
                recorded = datetime.fromisoformat(entry["time"])
                request = REQUEST.fullmatch(entry["request"])
                status = int(entry["status"])
                elapsed = float(entry["request_time"])
                if (
                    recorded.tzinfo is None
                    or request is None
                    or not math.isfinite(elapsed)
                    or elapsed < 0
                ):
                    continue
                if not args.since <= recorded < args.until:
                    continue
                route = ROUTES.get((request.group(1), request.group(2)))
                if route is None:
                    continue
            except (KeyError, TypeError, ValueError, json.JSONDecodeError):
                malformed += 1
                continue
            total[route] += 1
            if status >= 400:
                errors[route] += 1
            elif status == (302 if route == "article-save" else 200):
                durations[route].append(elapsed)

    routes = {}
    for route in sorted(total):
        samples = sorted(durations[route])
        routes[route] = {
            "total_requests": total[route],
            "successful_samples": len(samples),
            "error_responses": errors[route],
            "median_ms": round(statistics.median(samples) * 1000) if samples else None,
            "p95_ms": percentile(samples, 0.95)
            if len(samples) >= MIN_P95_SAMPLES
            else None,
            "max_ms": round(samples[-1] * 1000) if samples else None,
        }
    print(
        json.dumps(
            {
                "since": args.since.isoformat(),
                "until": args.until.isoformat(),
                "min_p95_samples": MIN_P95_SAMPLES,
                "scope": "route and status only; authentication and saved content are not recorded",
                "malformed_lines": malformed,
                "routes": routes,
            },
            indent=2,
            sort_keys=True,
        )
    )


if __name__ == "__main__":
    main()
