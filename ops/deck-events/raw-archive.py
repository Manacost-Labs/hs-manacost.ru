#!/usr/bin/env python3
"""Archive raw deck events to encrypted S3 objects before WordPress deletes them.

`archive` writes one gzip JSONL object per day older than the retention window,
reads it back to prove the upload, and only then records the day in
`<prefix>manacost_deck_event_archives`. WordPress deletes a day's raw rows only
when that record exists (see Unified_HS_Deck_Events::run_retention()).
`restore` reads a day back into the raw table with INSERT IGNORE.

Secrets (S3 keys, crypt passwords) are passed to rclone through the
environment only and are never printed.
"""

from __future__ import annotations

import argparse
import datetime as dt
import gzip
import hashlib
import json
import os
import re
import shlex
import subprocess
import sys
import tempfile
from pathlib import Path

DAY = re.compile(r"^\d{4}-\d{2}-\d{2}$")
DATETIME = re.compile(r"^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(?:\.\d+)?$")
NAME = re.compile(r"^[A-Za-z0-9_]+$")
BUCKET = re.compile(r"^[a-z0-9][a-z0-9.-]{1,61}[a-z0-9]$")
PREFIX = re.compile(r"^[a-z0-9][a-z0-9-]*(?:/[a-z0-9][a-z0-9-]*)*$")
REQUIRED = (
    "DECK_ARCHIVE_DATABASE",
    "DECK_ARCHIVE_TABLE_PREFIX",
    "DECK_ARCHIVE_ENVIRONMENT",
    "DECK_ARCHIVE_S3_BUCKET",
    "DECK_ARCHIVE_S3_PREFIX",
    "DECK_ARCHIVE_PROTECTED_PREFIXES",
    "DECK_ARCHIVE_S3_CREDENTIALS_FILE",
    "DECK_ARCHIVE_CRYPT_ENV",
)
COLUMNS = ("id", "post_id", "event_type", "event_time", "visitor_hash", "source", "context")
RESTORE_BATCH = 500


class ArchiveError(RuntimeError):
    pass


def log(event: str, **fields: object) -> None:
    print(json.dumps({"event": event, **fields}, sort_keys=True), flush=True)


def read_env_file(path: str) -> dict[str, str]:
    values: dict[str, str] = {}
    for number, line in enumerate(Path(path).read_text().splitlines(), 1):
        line = line.strip()
        if not line or line.startswith("#"):
            continue
        key, sep, value = line.partition("=")
        if not sep or not re.fullmatch(r"[A-Z][A-Z0-9_]*", key):
            raise ArchiveError(f"{path}:{number}: expected KEY=VALUE")
        if len(value) >= 2 and value[0] == value[-1] and value[0] in "'\"":
            value = value[1:-1]
        values[key] = value
    return values


def load_config(path: str) -> dict[str, str]:
    config = read_env_file(path)
    missing = [key for key in REQUIRED if not config.get(key)]
    if missing:
        raise ArchiveError("missing configuration: " + ",".join(missing))
    if not NAME.fullmatch(config["DECK_ARCHIVE_DATABASE"]) or not NAME.fullmatch(config["DECK_ARCHIVE_TABLE_PREFIX"]):
        raise ArchiveError("unsafe database or table prefix")
    if not NAME.fullmatch(config["DECK_ARCHIVE_ENVIRONMENT"]):
        raise ArchiveError("unsafe environment name")
    if not BUCKET.fullmatch(config["DECK_ARCHIVE_S3_BUCKET"]):
        raise ArchiveError("unsafe bucket name")
    check_prefix(config["DECK_ARCHIVE_S3_PREFIX"], config["DECK_ARCHIVE_PROTECTED_PREFIXES"].split(","))
    return config


def check_prefix(prefix: str, protected: list[str]) -> None:
    """The archive must never sit inside, or contain, a prefix another job prunes."""
    if not PREFIX.fullmatch(prefix):
        raise ArchiveError("unsafe archive prefix")
    for other in (item.strip().strip("/") for item in protected):
        if not other:
            continue
        if prefix == other or prefix.startswith(other + "/") or other.startswith(prefix + "/"):
            raise ArchiveError(f"archive prefix overlaps pruned prefix {other}")


class Store:
    """rclone crypt remote rooted at <bucket>/<archive prefix>."""

    def __init__(self, config: dict[str, str]) -> None:
        self.binary = os.environ.get("DECK_ARCHIVE_RCLONE", "/usr/bin/rclone")
        credentials = json.loads(Path(config["DECK_ARCHIVE_S3_CREDENTIALS_FILE"]).read_text())
        crypt = read_env_file(config["DECK_ARCHIVE_CRYPT_ENV"])
        for key in ("DECK_ARCHIVE_CRYPT_PASSWORD", "DECK_ARCHIVE_CRYPT_PASSWORD2"):
            if not crypt.get(key):
                raise ArchiveError(f"missing {key}")
        self.env = {
            **os.environ,
            "RCLONE_CONFIG_DECKOVH_TYPE": "s3",
            "RCLONE_CONFIG_DECKOVH_PROVIDER": "Other",
            "RCLONE_CONFIG_DECKOVH_REGION": "eu-west-par",
            "RCLONE_CONFIG_DECKOVH_FORCE_PATH_STYLE": "true",
            "RCLONE_CONFIG_DECKOVH_ACCESS_KEY_ID": credentials["access_key"],
            "RCLONE_CONFIG_DECKOVH_SECRET_ACCESS_KEY": credentials["secret_key"],
            "RCLONE_CONFIG_DECKOVH_ENDPOINT": credentials["endpoint"],
            "RCLONE_CONFIG_DECKARCHIVE_TYPE": "crypt",
            "RCLONE_CONFIG_DECKARCHIVE_REMOTE": f"deckovh:{config['DECK_ARCHIVE_S3_BUCKET']}/{config['DECK_ARCHIVE_S3_PREFIX']}",
            "RCLONE_CONFIG_DECKARCHIVE_FILENAME_ENCRYPTION": "standard",
            "RCLONE_CONFIG_DECKARCHIVE_DIRECTORY_NAME_ENCRYPTION": "true",
            "RCLONE_CONFIG_DECKARCHIVE_PASSWORD": crypt["DECK_ARCHIVE_CRYPT_PASSWORD"],
            "RCLONE_CONFIG_DECKARCHIVE_PASSWORD2": crypt["DECK_ARCHIVE_CRYPT_PASSWORD2"],
        }
        self.flags = ["--s3-no-check-bucket", "--retries", "3", "--low-level-retries", "10", "--log-level", "ERROR"]

    def upload(self, source: Path, key: str) -> None:
        # --immutable refuses to replace an existing object with different bytes.
        self._run(["copyto", str(source), f"deckarchive:{key}", "--immutable", *self.flags])

    def read(self, key: str) -> bytes:
        return self._run(["cat", f"deckarchive:{key}", *self.flags]).stdout

    def _run(self, args: list[str]) -> subprocess.CompletedProcess[bytes]:
        result = subprocess.run([self.binary, *args], env=self.env, capture_output=True)
        if result.returncode != 0:
            # rclone errors name the object, never the credentials.
            raise ArchiveError(f"rclone {args[0]} failed: {result.stderr.decode(errors='replace').strip()[-300:]}")
        return result


class Database:
    def __init__(self, config: dict[str, str]) -> None:
        command = os.environ.get("DECK_ARCHIVE_MARIADB", "mariadb")
        self.command = [*shlex.split(command), "--batch", "--raw", "--skip-column-names", "--default-character-set=utf8mb4"]
        self.database = config["DECK_ARCHIVE_DATABASE"]
        prefix = config["DECK_ARCHIVE_TABLE_PREFIX"]
        self.events = f"`{prefix}manacost_deck_events`"
        self.archives = f"`{prefix}manacost_deck_event_archives`"

    def query(self, sql: str) -> list[str]:
        return self._run(sql).splitlines()

    def execute(self, sql: str) -> None:
        self._run(sql)

    def _run(self, sql: str) -> str:
        result = subprocess.run(
            [*self.command, self.database], input=sql.encode(), capture_output=True
        )
        if result.returncode != 0:
            raise ArchiveError("mariadb failed: " + result.stderr.decode(errors="replace").strip()[-300:])
        return result.stdout.decode()


def day_range(day: str) -> tuple[str, str]:
    start = dt.date.fromisoformat(day)
    return f"{start} 00:00:00", f"{start + dt.timedelta(days=1)} 00:00:00"


def object_key(config: dict[str, str], day: str) -> str:
    year, month, date = day.split("-")
    return f"{config['DECK_ARCHIVE_ENVIRONMENT']}/raw/{year}/{month}/{date}.jsonl.gz"


def pending_days(db: Database, retention_days: int, limit: int) -> list[str]:
    rows = db.query(
        f"""SELECT DISTINCT DATE(e.event_time) AS day FROM {db.events} e
            LEFT JOIN {db.archives} a ON a.event_day = DATE(e.event_time)
            WHERE e.event_time < CURDATE() - INTERVAL {int(retention_days)} DAY AND a.event_day IS NULL
            ORDER BY day LIMIT {int(limit)}"""
    )
    return [row for row in rows if DAY.fullmatch(row)]


def archive_day(config: dict[str, str], db: Database, store: Store, day: str) -> None:
    if not DAY.fullmatch(day):
        raise ArchiveError("invalid day")
    start, end = day_range(day)
    if db.query(f"SELECT 1 FROM {db.archives} WHERE event_day = '{day}'"):
        log("deck_events_day_already_archived", day=day)
        return

    where = f"event_time >= '{start}' AND event_time < '{end}'"
    expected = int(db.query(f"SELECT COUNT(*) FROM {db.events} WHERE {where}")[0])
    if expected == 0:
        log("deck_events_day_empty", day=day)
        return
    json_columns = ", ".join(f"'{column}', {column}" for column in COLUMNS)
    lines = db.query(f"SELECT JSON_OBJECT({json_columns}) FROM {db.events} WHERE {where} ORDER BY id")
    if len(lines) != expected:
        raise ArchiveError(f"{day}: exported {len(lines)} rows, expected {expected}")
    for line in lines:
        json.loads(line)

    payload = gzip.compress(("\n".join(lines) + "\n").encode(), mtime=0)
    digest = hashlib.sha256(payload).hexdigest()
    key = object_key(config, day)
    with tempfile.TemporaryDirectory() as work:
        path = Path(work) / "day.jsonl.gz"
        path.write_bytes(payload)
        store.upload(path, key)
    stored = store.read(key)
    if hashlib.sha256(stored).hexdigest() != digest:
        raise ArchiveError(f"{day}: uploaded object does not match its checksum")

    db.execute(
        f"""INSERT INTO {db.archives} (event_day, row_count, bytes, sha256, object_key, archived_at)
            VALUES ('{day}', {expected}, {len(payload)}, '{digest}', '{key}', UTC_TIMESTAMP())"""
    )
    log("deck_events_day_archived", day=day, rows=expected, bytes=len(payload))


def sql_text(value: object) -> str:
    if value is None:
        return "NULL"
    if not isinstance(value, str):
        raise ArchiveError("expected a string column")
    # Hex literals need no escaping, whatever the archived text contains.
    return "_utf8mb4 X'" + value.encode().hex() + "'" if value else "''"


def restore_statements(db: Database, lines: list[str]) -> list[str]:
    values = []
    for line in lines:
        row = json.loads(line)
        event_time = str(row["event_time"])
        if not DATETIME.fullmatch(event_time):
            raise ArchiveError("invalid event_time in archive")
        values.append(
            "({}, {}, {}, '{}', {}, {}, {})".format(
                int(row["id"]),
                int(row["post_id"]),
                sql_text(row["event_type"]),
                event_time,
                sql_text(row["visitor_hash"]),
                sql_text(row["source"]),
                sql_text(row["context"]),
            )
        )
    columns = ", ".join(COLUMNS)
    return [
        f"INSERT IGNORE INTO {db.events} ({columns}) VALUES " + ",\n".join(values[i : i + RESTORE_BATCH]) + ";"
        for i in range(0, len(values), RESTORE_BATCH)
    ]


def restore_day(db: Database, store: Store, day: str) -> None:
    if not DAY.fullmatch(day):
        raise ArchiveError("invalid day")
    record = db.query(f"SELECT row_count, sha256, object_key FROM {db.archives} WHERE event_day = '{day}'")
    if not record:
        raise ArchiveError(f"{day}: no archive record")
    row_count, digest, key = record[0].split("\t")
    payload = store.read(key)
    if hashlib.sha256(payload).hexdigest() != digest:
        raise ArchiveError(f"{day}: archived object does not match the recorded checksum")
    lines = [line for line in gzip.decompress(payload).decode().splitlines() if line]
    if len(lines) != int(row_count):
        raise ArchiveError(f"{day}: archive holds {len(lines)} rows, recorded {row_count}")
    db.execute("\n".join(restore_statements(db, lines)))
    start, end = day_range(day)
    present = int(db.query(f"SELECT COUNT(*) FROM {db.events} WHERE event_time >= '{start}' AND event_time < '{end}'")[0])
    if present < int(row_count):
        raise ArchiveError(f"{day}: restored {present} rows, archive holds {row_count}")
    log("deck_events_day_restored", day=day, rows=int(row_count))


def main(argv: list[str]) -> int:
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument("--config", default="/etc/hs-manacost/deck-events-archive.env")
    commands = parser.add_subparsers(dest="command", required=True)
    archive = commands.add_parser("archive")
    archive.add_argument("--day", help="archive exactly this day (YYYY-MM-DD) regardless of age")
    archive.add_argument("--retention-days", type=int, default=90)
    archive.add_argument("--max-days", type=int, default=10)
    restore = commands.add_parser("restore")
    restore.add_argument("--day", required=True)
    commands.add_parser("check-config")
    args = parser.parse_args(argv)

    try:
        config = load_config(args.config)
        if args.command == "check-config":
            log("deck_events_archive_config_ok", prefix=config["DECK_ARCHIVE_S3_PREFIX"])
            return 0
        db = Database(config)
        store = Store(config)
        if args.command == "restore":
            restore_day(db, store, args.day)
            return 0
        days = [args.day] if args.day else pending_days(db, args.retention_days, args.max_days)
        for day in days:
            archive_day(config, db, store, day)
        return 0
    except (ArchiveError, OSError, ValueError, KeyError) as error:
        log("deck_events_archive_failed", error=str(error))
        return 1


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
