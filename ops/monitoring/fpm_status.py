#!/usr/bin/env python3
"""Evaluate bounded PHP-FPM status JSON and persist only aggregate counters."""
import argparse
import json
import os
from pathlib import Path
import re
import secrets
import stat
import sys


MAX_INPUT = 64 * 1024
POOL_LABEL = re.compile(r"^[a-z0-9_-]{1,24}$")
STATE_FIELDS = {"start_time", "queue_streak", "utilization_streak",
                "max_children_reached", "slow_requests"}


class Unknown(Exception):
    """The status or private state could not be validated."""


def integer(status_payload, name):
    value = status_payload.get(name)
    if isinstance(value, bool) or not isinstance(value, int) or not 0 <= value <= 10**12:
        raise Unknown("invalid_status_value")
    return value


def parse_payload(raw, max_children):
    if len(raw) > MAX_INPUT:
        raise Unknown("status_too_large")
    if b"\r\n\r\n" in raw:
        raw = raw.split(b"\r\n\r\n", 1)[1]
    elif b"\n\n" in raw:
        raw = raw.split(b"\n\n", 1)[1]
    try:
        payload = json.loads(raw)
    except (TypeError, ValueError) as exc:
        raise Unknown("malformed_status") from exc
    if not isinstance(payload, dict):
        raise Unknown("invalid_status_shape")
    if not isinstance(payload.get("pool"), str) or payload.get("process manager") not in {
            "dynamic", "ondemand", "static"}:
        raise Unknown("invalid_status_identity")

    values = {
        "start_time": integer(payload, "start time"),
        "listen_queue": integer(payload, "listen queue"),
        "idle": integer(payload, "idle processes"),
        "active": integer(payload, "active processes"),
        "total": integer(payload, "total processes"),
        "max_children_reached": integer(payload, "max children reached"),
        "slow_requests": integer(payload, "slow requests"),
    }
    if values["idle"] + values["active"] != values["total"] or values["total"] > max_children:
        raise Unknown("inconsistent_process_counts")
    return values


def read_state(path):
    try:
        descriptor = os.open(path, os.O_RDONLY | os.O_NOFOLLOW)
    except FileNotFoundError:
        return None
    except OSError as exc:
        raise Unknown("state_unreadable") from exc
    with os.fdopen(descriptor, "rb") as stream:
        metadata = os.fstat(stream.fileno())
        if (not stat.S_ISREG(metadata.st_mode) or metadata.st_size > 4096
                or metadata.st_uid != os.geteuid() or metadata.st_nlink != 1
                or stat.S_IMODE(metadata.st_mode) != 0o600):
            raise Unknown("invalid_state_file")
        raw = stream.read(4097)
    try:
        state = json.loads(raw)
    except (TypeError, ValueError) as exc:
        raise Unknown("malformed_state") from exc
    if not isinstance(state, dict) or set(state) != STATE_FIELDS:
        raise Unknown("invalid_state_shape")
    for value in state.values():
        if isinstance(value, bool) or not isinstance(value, int) or value < 0:
            raise Unknown("invalid_state_value")
    return state


def write_state(path, state):
    parent = path.parent
    try:
        directory = os.open(parent, os.O_RDONLY | os.O_DIRECTORY | os.O_NOFOLLOW)
    except OSError as exc:
        raise Unknown("state_directory_unavailable") from exc
    directory_metadata = os.fstat(directory)
    if (directory_metadata.st_uid != os.geteuid()
            or stat.S_IMODE(directory_metadata.st_mode) & 0o077):
        os.close(directory)
        raise Unknown("state_directory_not_private")
    temporary = f".{path.name}.{os.getpid()}.{secrets.token_hex(6)}.tmp"
    descriptor = None
    try:
        descriptor = os.open(temporary, os.O_WRONLY | os.O_CREAT | os.O_EXCL | os.O_NOFOLLOW,
                             0o600, dir_fd=directory)
        payload = (json.dumps(state, sort_keys=True, separators=(",", ":")) + "\n").encode()
        with os.fdopen(descriptor, "wb") as stream:
            descriptor = None
            stream.write(payload)
            stream.flush()
            os.fsync(stream.fileno())
        os.replace(temporary, path.name, src_dir_fd=directory, dst_dir_fd=directory)
    except OSError as exc:
        if descriptor is not None:
            os.close(descriptor)
        try:
            os.unlink(temporary, dir_fd=directory)
        except OSError:
            pass
        raise Unknown("state_write_failed") from exc
    finally:
        os.close(directory)


def evaluate(values, previous, max_children):
    same_process = previous is not None and previous["start_time"] == values["start_time"]
    if same_process and (values["max_children_reached"] < previous["max_children_reached"]
                         or values["slow_requests"] < previous["slow_requests"]):
        raise Unknown("counter_regressed")

    utilization_pct = values["active"] * 100 // max_children
    queue_streak = ((previous["queue_streak"] if same_process else 0) + 1
                    if values["listen_queue"] > 0 else 0)
    utilization_streak = ((previous["utilization_streak"] if same_process else 0) + 1
                          if utilization_pct >= 75 else 0)
    max_children_delta = (values["max_children_reached"] - previous["max_children_reached"]
                          if same_process else 0)
    slow_delta = (values["slow_requests"] - previous["slow_requests"] if same_process else 0)
    state = {
        "start_time": values["start_time"],
        "queue_streak": queue_streak,
        "utilization_streak": utilization_streak,
        "max_children_reached": values["max_children_reached"],
        "slow_requests": values["slow_requests"],
    }
    failed = queue_streak >= 2 or utilization_streak >= 2 or max_children_delta > 0
    metrics = {
        "active": values["active"],
        "idle": values["idle"],
        "total": values["total"],
        "max_children": max_children,
        "utilization_pct": utilization_pct,
        "listen_queue": values["listen_queue"],
        "queue_streak": queue_streak,
        "utilization_streak": utilization_streak,
        "max_children_delta": max_children_delta,
        "slow_delta": slow_delta,
    }
    return failed, state, metrics


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--pool", required=True)
    parser.add_argument("--max-children", required=True, type=int)
    parser.add_argument("--state-file", required=True, type=Path)
    args = parser.parse_args()
    if not POOL_LABEL.fullmatch(args.pool) or not 1 <= args.max_children <= 10000:
        print("UNKNOWN fpm_status reason=invalid_arguments")
        return 2
    try:
        raw = sys.stdin.buffer.read(MAX_INPUT + 1)
        values = parse_payload(raw, args.max_children)
        previous = read_state(args.state_file)
        failed, state, metrics = evaluate(values, previous, args.max_children)
        write_state(args.state_file, state)
    except (OSError, Unknown):
        print("UNKNOWN fpm_status reason=invalid_or_unavailable_status")
        return 2

    level = "FAIL" if failed else "OK"
    print(f"{level} fpm_status pool={args.pool} "
          + " ".join(f"{name}={value}" for name, value in metrics.items()))
    return int(failed)


if __name__ == "__main__":
    raise SystemExit(main())
