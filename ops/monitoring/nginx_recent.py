#!/usr/bin/env python3
"""Bounded Nginx incident counts, not an assertion of website availability.

Only aggregate counters leave this process: no IPs, request strings or queries.
Exit codes: 0 below thresholds, 1 incident, 2 incomplete/unknown evidence.
"""
import argparse
import datetime as dt
import gzip
import json
import os
from pathlib import Path
import re
import stat
import time
from urllib.parse import parse_qsl
import zlib

UTC = dt.timezone.utc
ACCESS = re.compile(r'^\S+ \S+ \S+ \[([^\]]+)\] "((?:\\.|[^"\\])*)" (\d{3}) ')
ERROR = re.compile(r'^(\d{4}/\d{2}/\d{2} \d{2}:\d{2}:\d{2}) ')
ERROR_CONTINUATION = re.compile(r'^(?:Stack trace:|#\d+\s|\s)')
UPSTREAM_STATUS = re.compile(r'^(?:|[1-5]\d\d(?:(?:,\s*|\s+:\s+)[1-5]\d\d)*)$')
UPSTREAM = ("Resource temporarily unavailable", "recv() failed", "upstream timed out",
            "connect() failed", "upstream prematurely closed", "no live upstreams",
            "upstream sent too big header", "upstream sent invalid header")
ATTRIBUTION_FIELDS = {"time", "status", "endpoint", "owner", "upstream_status",
                      "retry_after_present"}
ENDPOINTS = {"page", "login", "admin", "rest", "analytics", "views", "media"}
OWNERS = {"wordpress", "plausible", "views", "media_fallback", "nginx"}
GATEWAY_5XX_FAIL_AT = 3
NON_WAF_5XX_FAIL_ABOVE = 20
UNATTRIBUTED_5XX_FAIL_ABOVE = 2
WAF_503_FAIL_ABOVE = 350


class Unknown(Exception):
    """The requested observation window could not be read completely."""


def bounded_tail(path, byte_limit):
    # O_NONBLOCK avoids hanging on an accidentally configured FIFO/device.
    fd = os.open(path, os.O_RDONLY | os.O_NONBLOCK | os.O_NOFOLLOW)
    with os.fdopen(fd, "rb") as stream:
        before = os.fstat(stream.fileno())
        if not stat.S_ISREG(before.st_mode):
            raise Unknown("not_regular_file")
        clipped = False
        if path.suffix == ".gz":
            with gzip.GzipFile(fileobj=stream, mode="rb") as archive:
                blob = archive.read(byte_limit + 1)
            if len(blob) > byte_limit:
                raise Unknown("compressed_rotation_exceeds_budget")
        else:
            start = max(0, before.st_size - byte_limit)
            stream.seek(start)
            blob = stream.read(min(before.st_size, byte_limit))
            if start:
                clipped = True
                # Discard the first fragment; the following full record must
                # predate the window to prove that no recent records were cut.
                blob = blob.partition(b"\n")[2]
        after = path.stat()
        if (before.st_dev, before.st_ino) != (after.st_dev, after.st_ino) or after.st_size < before.st_size:
            raise Unknown("rotation_during_read")
    if blob and not blob.endswith(b"\n"):
        raise Unknown("partial_record")
    return blob.decode("utf-8", errors="replace").splitlines(), clipped


def records(path, kind, since, now, byte_limit):
    paths = [path]
    # Seven daily/size rotations; mtime is preserved by this host's logrotate.
    # Only archives changed within the window can contain recent requests.
    for index in range(1, 8):
        for suffix in (f".{index}", f".{index}.gz"):
            candidate = Path(str(path) + suffix)
            try:
                modified = candidate.stat().st_mtime
            except FileNotFoundError:
                continue
            if modified >= since:
                paths.append(candidate)
    for source in paths:
        lines, clipped = bounded_tail(source, byte_limit)
        first_timestamp = None
        seen_primary = False
        for line in lines:
            match = (ACCESS if kind == "access" else ERROR).match(line)
            if not match:
                if kind == "error" and seen_primary and ERROR_CONTINUATION.match(line):
                    continue
                raise Unknown(f"malformed_{kind}_record")
            seen_primary = True
            try:
                if kind == "access":
                    timestamp = dt.datetime.strptime(match[1], "%d/%b/%Y:%H:%M:%S %z").timestamp()
                else:
                    # Origin timezone is UTC; do not reuse on a non-UTC node.
                    timestamp = dt.datetime.strptime(match[1], "%Y/%m/%d %H:%M:%S").replace(tzinfo=UTC).timestamp()
            except ValueError as exc:
                raise Unknown(f"malformed_{kind}_timestamp") from exc
            if first_timestamp is None:
                first_timestamp = timestamp
            if since <= timestamp <= now:
                yield match, line
        if clipped and (first_timestamp is None or first_timestamp >= since):
            raise Unknown("window_exceeds_read_budget")


def attribution_records(path, since, now, byte_limit):
    paths = [path]
    for index in range(1, 8):
        for suffix in (f".{index}", f".{index}.gz"):
            candidate = Path(str(path) + suffix)
            try:
                modified = candidate.stat().st_mtime
            except FileNotFoundError:
                continue
            if modified >= since:
                paths.append(candidate)

    for source in paths:
        lines, clipped = bounded_tail(source, byte_limit)
        first_timestamp = None
        for line in lines:
            try:
                entry = json.loads(line)
            except (TypeError, ValueError) as exc:
                raise Unknown("malformed_attribution_record") from exc
            if not isinstance(entry, dict):
                raise Unknown("invalid_attribution_fields")
            try:
                timestamp = dt.datetime.fromisoformat(entry.get("time"))
            except (TypeError, ValueError) as exc:
                raise Unknown("malformed_attribution_timestamp") from exc
            if timestamp.tzinfo is None:
                raise Unknown("naive_attribution_timestamp")
            timestamp = timestamp.timestamp()
            if first_timestamp is None:
                first_timestamp = timestamp
            # The previous log schema did not have `owner`. Permit only records
            # that are already outside the requested observation window; any
            # current legacy/malformed entry remains fail-closed.
            if timestamp < since:
                continue
            if set(entry) != ATTRIBUTION_FIELDS:
                raise Unknown("invalid_attribution_fields")
            status = entry["status"]
            retry_after = entry["retry_after_present"]
            if (isinstance(status, bool) or not isinstance(status, int) or not 500 <= status <= 599
                    or entry["endpoint"] not in ENDPOINTS or entry["owner"] not in OWNERS
                    or not isinstance(entry["upstream_status"], str)
                    or not UPSTREAM_STATUS.fullmatch(entry["upstream_status"])
                    or isinstance(retry_after, bool) or retry_after not in (0, 1)):
                raise Unknown("invalid_attribution_value")
            if since <= timestamp <= now:
                yield entry
        if clipped and (first_timestamp is None or first_timestamp >= since):
            raise Unknown("window_exceeds_read_budget")


def is_root_image_proxy(uri):
    path, _, query = uri.partition("?")
    # The plugin routes a nonempty scalar parameter before the home template.
    # Limit this distinction to its monitored root route; admin URLs are not
    # image endpoints. Empty/array-only parameters must not hide home denials.
    value = ""
    for key, item in parse_qsl(query, keep_blank_values=True):
        # PHP normalizes GET variable names, not only percent-encoding.
        key = key.split("\x00", 1)[0].lstrip(" ").replace(" ", "_").replace(".", "_")
        if key == "hs_tooltip_img":
            value = item
        elif key.startswith("hs_tooltip_img[") and "]" in key:
            value = ""  # PHP replaces the scalar with an array.
    return path == "/" and bool(value)


def category(uri):
    if is_root_image_proxy(uri):
        return "media_5xx"
    uri, _, query = uri.partition("?")
    keys = {item.partition("=")[0] for item in query.split("&")}
    if "view_counter" in keys or uri.startswith(("/api/event", "/mca/", "/views/hit", "/view-counter", "/views-counter", "/wp-json/hs-views")):
        return "analytics_5xx"
    if uri.startswith(("/wp-admin/", "/wp-login.php")):
        return "admin_5xx"
    if uri.startswith(("/wp-content/", "/wp-includes/")):
        return "media_5xx"
    return "page_5xx"


def collect(access_log, error_log, *, attribution_log=None, additional_access_logs=(),
            now=None, window=300, byte_limit=4 * 1024 * 1024):
    now = time.time() if now is None else now
    since = now - window
    counts = dict.fromkeys(("requests", "fivexx", "page_5xx", "admin_5xx", "media_5xx",
                            "analytics_5xx", "root403", "image_proxy403", "admin403",
                            "upstream_errors", "attribution_enabled", "attributed_5xx",
                            "waf_503_candidate", "gateway_5xx", "non_waf_5xx",
                            "unattributed_5xx", "wordpress_5xx", "plausible_5xx", "views_5xx",
                            "media_fallback_5xx", "nginx_5xx"), 0)
    try:
        access_logs = (Path(access_log), *(Path(item) for item in additional_access_logs))
        for access_path in access_logs:
            for match, _line in records(access_path, "access", since, now, byte_limit):
                fields = match[2].split()
                target = fields[1] if len(fields) >= 2 else ""
                uri = target.split("?", 1)[0]
                code = int(match[3])
                counts["requests"] += 1
                if 500 <= code <= 599:
                    counts["fivexx"] += 1
                    counts[category(target)] += 1
                if code == 403 and uri == "/":
                    counts["image_proxy403" if is_root_image_proxy(target) else "root403"] += 1
                if (code == 403 and uri.startswith("/wp-admin/")
                        and uri not in {"/wp-admin/admin-ajax.php", "/wp-admin/css/"}):
                    counts["admin403"] += 1
        for _match, line in records(Path(error_log), "error", since, now, byte_limit):
            if any(pattern in line for pattern in UPSTREAM):
                counts["upstream_errors"] += 1
        if attribution_log is not None:
            counts["attribution_enabled"] = 1
            for entry in attribution_records(Path(attribution_log), since, now, byte_limit):
                counts["attributed_5xx"] += 1
                counts[entry["owner"] + "_5xx"] += 1
                upstream_codes = {int(code) for code in re.findall(r"[1-5]\d\d",
                                                                   entry["upstream_status"])}
                is_waf_candidate = (entry["status"] == 503 and entry["owner"] == "wordpress"
                                    and 503 in upstream_codes and entry["retry_after_present"] == 1)
                if is_waf_candidate:
                    counts["waf_503_candidate"] += 1
                else:
                    counts["non_waf_5xx"] += 1
                if entry["status"] in (502, 504) or upstream_codes.intersection((502, 504)):
                    counts["gateway_5xx"] += 1
            if counts["attributed_5xx"] - counts["fivexx"] > 2:
                raise Unknown("attribution_ahead_of_access")
            counts["unattributed_5xx"] = max(0, counts["fivexx"] - counts["attributed_5xx"])
    except (OSError, EOFError, zlib.error) as exc:
        # Exception text can contain paths or user-controlled input.
        raise Unknown(type(exc).__name__) from exc
    return counts


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--access-log", required=True, type=Path)
    parser.add_argument("--error-log", required=True, type=Path)
    parser.add_argument("--attribution-log", type=Path)
    parser.add_argument("--additional-access-log", action="append", default=[], type=Path)
    args = parser.parse_args()
    try:
        counts = collect(args.access_log, args.error_log, attribution_log=args.attribution_log,
                         additional_access_logs=args.additional_access_log)
    except Unknown as exc:
        print(f"UNKNOWN nginx_recent reason={exc}")
        return 2
    if counts["attribution_enabled"]:
        error_rate_failed = (counts["gateway_5xx"] >= GATEWAY_5XX_FAIL_AT
                             or counts["non_waf_5xx"] > NON_WAF_5XX_FAIL_ABOVE
                             or counts["unattributed_5xx"] > UNATTRIBUTED_5XX_FAIL_ABOVE
                             or counts["waf_503_candidate"] > WAF_503_FAIL_ABOVE)
    else:
        error_rate_failed = counts["fivexx"] > 20
    failed = (error_rate_failed or counts["root403"] > 0 or counts["admin403"] > 0
              or counts["upstream_errors"] > 0)
    level = "FAIL" if failed else "OK"
    print(f"{level} nginx_recent window_sec=300 " + " ".join(f"{key}={value}" for key, value in counts.items()))
    return int(failed)


if __name__ == "__main__":
    raise SystemExit(main())
