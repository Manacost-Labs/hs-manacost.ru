#!/usr/bin/env python3
"""Bounded Nginx incident counts, not an assertion of website availability.

Only aggregate counters leave this process: no IPs, request strings or queries.
Exit codes: 0 below thresholds, 1 incident, 2 incomplete/unknown evidence.
"""
import argparse
import datetime as dt
import gzip
import os
from pathlib import Path
import re
import stat
import time
import zlib

UTC = dt.timezone.utc
ACCESS = re.compile(r'^\S+ \S+ \S+ \[([^\]]+)\] "((?:\\.|[^"\\])*)" (\d{3}) ')
ERROR = re.compile(r'^(\d{4}/\d{2}/\d{2} \d{2}:\d{2}:\d{2}) ')
UPSTREAM = ("Resource temporarily unavailable", "recv() failed", "upstream timed out",
            "connect() failed", "upstream prematurely closed", "no live upstreams",
            "upstream sent too big header", "upstream sent invalid header")


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
        for line in lines:
            match = (ACCESS if kind == "access" else ERROR).match(line)
            if not match:
                raise Unknown(f"malformed_{kind}_record")
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


def category(uri):
    uri, _, query = uri.partition("?")
    keys = {item.partition("=")[0] for item in query.split("&")}
    if "view_counter" in keys or uri.startswith(("/api/event", "/views/hit", "/view-counter", "/views-counter", "/wp-json/hs-views")):
        return "analytics_5xx"
    if uri.startswith(("/wp-admin/", "/wp-login.php")):
        return "admin_5xx"
    if uri.startswith(("/wp-content/", "/wp-includes/")):
        return "media_5xx"
    return "page_5xx"


def collect(access_log, error_log, *, now=None, window=300, byte_limit=4 * 1024 * 1024):
    now = time.time() if now is None else now
    since = now - window
    counts = dict.fromkeys(("requests", "fivexx", "page_5xx", "admin_5xx", "media_5xx",
                            "analytics_5xx", "root403", "admin403", "upstream_errors"), 0)
    try:
        for match, _line in records(Path(access_log), "access", since, now, byte_limit):
            fields = match[2].split()
            uri = fields[1].split("?", 1)[0] if len(fields) >= 2 else ""
            code = int(match[3])
            counts["requests"] += 1
            if 500 <= code <= 599:
                counts["fivexx"] += 1
                counts[category(fields[1] if len(fields) >= 2 else "")] += 1
            if code == 403 and uri == "/":
                counts["root403"] += 1
            if code == 403 and uri.startswith("/wp-admin/") and uri not in {"/wp-admin/admin-ajax.php", "/wp-admin/css/"}:
                counts["admin403"] += 1
        for _match, line in records(Path(error_log), "error", since, now, byte_limit):
            if any(pattern in line for pattern in UPSTREAM):
                counts["upstream_errors"] += 1
    except (OSError, EOFError, zlib.error) as exc:
        # Exception text can contain paths or user-controlled input.
        raise Unknown(type(exc).__name__) from exc
    return counts


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--access-log", required=True, type=Path)
    parser.add_argument("--error-log", required=True, type=Path)
    args = parser.parse_args()
    try:
        counts = collect(args.access_log, args.error_log)
    except Unknown as exc:
        print(f"UNKNOWN nginx_recent reason={exc}")
        return 2
    failed = (counts["fivexx"] > 20 or counts["root403"] > 0 or
              counts["admin403"] > 0 or counts["upstream_errors"] > 0)
    level = "FAIL" if failed else "OK"
    print(f"{level} nginx_recent window_sec=300 " + " ".join(f"{key}={value}" for key, value in counts.items()))
    return int(failed)


if __name__ == "__main__":
    raise SystemExit(main())
