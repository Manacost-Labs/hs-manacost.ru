from __future__ import annotations

import gzip
import hashlib
import json
import os
import re
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SCRIPT = ROOT / "ops/deck-events/raw-archive.py"
SECRET = "never-print-this-secret"

ROWS = [
    {"id": 7, "post_id": 42, "event_type": "deck_view", "event_time": "2026-06-01 12:00:00",
     "visitor_hash": "a" * 64, "source": "rest", "context": None},
    {"id": 9, "post_id": 0, "event_type": "filter_apply", "event_time": "2026-06-01 23:59:59",
     "visitor_hash": "", "source": "it's \\ \"quoted\"\n", "context": '{"class":"Маг"}'},
]

FAKE_MARIADB = r'''#!/usr/bin/env python3
import json, os, re, sys
from pathlib import Path
sql = sys.stdin.read()
state = Path(os.environ["STATE"])
with open(state / "calls.log", "a") as log:
    log.write(sql + "\n--\n")
rows = json.loads(os.environ["ROWS"])
record = state / "record"
if "SELECT 1 FROM" in sql:
    if record.exists():
        print(1)
elif "SELECT DISTINCT DATE" in sql:
    print("2026-06-01")
elif "SELECT COUNT(*)" in sql:
    print(len(rows))
elif "SELECT JSON_OBJECT" in sql:
    for row in rows:
        print(json.dumps(row, ensure_ascii=False))
elif "INSERT INTO" in sql and "archives" in sql:
    record.write_text(sql)
elif "SELECT row_count" in sql:
    if record.exists():
        m = re.search(r"VALUES \('([^']+)', (\d+), (\d+), '([0-9a-f]+)', '([^']+)'", record.read_text())
        print("\t".join([m.group(2), m.group(4), m.group(5)]))
'''

FAKE_RCLONE = r'''#!/usr/bin/env python3
import os, shutil, sys
from pathlib import Path
args = sys.argv[1:]
store = Path(os.environ["STATE"]) / "s3"
with open(Path(os.environ["STATE"]) / "rclone.log", "a") as log:
    log.write(" ".join(args) + "\n")
def target(remote):
    assert remote.startswith("deckarchive:"), remote
    return store / remote.split(":", 1)[1]
if args[0] == "copyto":
    if os.environ.get("UPLOAD_FAIL"):
        sys.exit(1)
    path = target(args[2])
    path.parent.mkdir(parents=True, exist_ok=True)
    shutil.copyfile(args[1], path)
elif args[0] == "cat":
    data = target(args[1]).read_bytes()
    sys.stdout.buffer.write(b"corrupt" + data if os.environ.get("CORRUPT") else data)
'''


class DeckEventsRawArchiveTest(unittest.TestCase):
    def setUp(self) -> None:
        temp = tempfile.TemporaryDirectory()
        self.addCleanup(temp.cleanup)
        self.state = Path(temp.name)
        for name, body in (("mariadb", FAKE_MARIADB), ("rclone", FAKE_RCLONE)):
            path = self.state / name
            path.write_text(body)
            path.chmod(0o755)
        (self.state / "credentials.json").write_text(json.dumps(
            {"access_key": SECRET, "secret_key": SECRET, "endpoint": "https://s3.example.invalid"}))
        (self.state / "crypt.env").write_text(
            f"DECK_ARCHIVE_CRYPT_PASSWORD={SECRET}\nDECK_ARCHIVE_CRYPT_PASSWORD2={SECRET}\n")
        self.write_config("deck-events")

    def write_config(self, prefix: str, protected: str = "hs-data-platform") -> None:
        (self.state / "archive.env").write_text("\n".join([
            "DECK_ARCHIVE_DATABASE=wordpress",
            "DECK_ARCHIVE_TABLE_PREFIX=wp_",
            "DECK_ARCHIVE_ENVIRONMENT=production",
            "DECK_ARCHIVE_S3_BUCKET=hs-manacost-backups-3az",
            f"DECK_ARCHIVE_S3_PREFIX={prefix}",
            f"DECK_ARCHIVE_PROTECTED_PREFIXES={protected}",
            f"DECK_ARCHIVE_S3_CREDENTIALS_FILE={self.state / 'credentials.json'}",
            f"DECK_ARCHIVE_CRYPT_ENV={self.state / 'crypt.env'}",
        ]) + "\n")

    def run_archive(self, *args: str, **env: str) -> subprocess.CompletedProcess[str]:
        return subprocess.run(
            [sys.executable, "-I", str(SCRIPT), "--config", str(self.state / "archive.env"), *args],
            env={**os.environ, "STATE": str(self.state), "ROWS": json.dumps(ROWS, ensure_ascii=False),
                 "DECK_ARCHIVE_MARIADB": str(self.state / "mariadb"),
                 "DECK_ARCHIVE_RCLONE": str(self.state / "rclone"), **env},
            capture_output=True, text=True,
        )

    def stored_object(self) -> Path:
        return self.state / "s3/production/raw/2026/06/01.jsonl.gz"

    def test_records_a_day_only_after_the_readback_matches(self) -> None:
        result = self.run_archive("archive")
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        payload = self.stored_object().read_bytes()
        archived = [json.loads(line) for line in gzip.decompress(payload).decode().splitlines()]
        self.assertEqual(archived, ROWS)
        record = (self.state / "record").read_text()
        self.assertIn(f"'{hashlib.sha256(payload).hexdigest()}'", record)
        self.assertIn("VALUES ('2026-06-01', 2, ", record)
        self.assertIn("--immutable", (self.state / "rclone.log").read_text())
        self.assertNotIn(SECRET, result.stdout + result.stderr + (self.state / "rclone.log").read_text())

    def test_failed_upload_keeps_the_day_unrecorded(self) -> None:
        result = self.run_archive("archive", UPLOAD_FAIL="1")
        self.assertEqual(result.returncode, 1)
        self.assertFalse((self.state / "record").exists())
        self.assertIn("deck_events_archive_failed", result.stdout)

    def test_corrupt_readback_keeps_the_day_unrecorded(self) -> None:
        result = self.run_archive("archive", CORRUPT="1")
        self.assertEqual(result.returncode, 1)
        self.assertFalse((self.state / "record").exists())

    def test_archived_day_is_not_uploaded_again(self) -> None:
        (self.state / "record").write_text("existing")
        result = self.run_archive("archive", "--day", "2026-06-01")
        self.assertEqual(result.returncode, 0, result.stdout)
        self.assertFalse((self.state / "rclone.log").exists())

    def test_prefix_must_stay_outside_pruned_prefixes(self) -> None:
        self.assertEqual(self.run_archive("check-config").returncode, 0)
        for prefix, protected in (("hs-data-platform", "hs-data-platform"),
                                  ("hs-data-platform/deck-events", "hs-data-platform"),
                                  ("deck-events", "deck-events/raw"),
                                  ("../deck-events", "hs-data-platform")):
            with self.subTest(prefix=prefix, protected=protected):
                self.write_config(prefix, protected)
                result = self.run_archive("check-config")
                self.assertEqual(result.returncode, 1, result.stdout)

    def test_restore_inserts_every_archived_value_unchanged(self) -> None:
        self.assertEqual(self.run_archive("archive").returncode, 0)
        result = self.run_archive("restore", "--day", "2026-06-01")
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        calls = (self.state / "calls.log").read_text()
        statement = next(part for part in calls.split("\n--\n") if "INSERT IGNORE" in part)
        restored = [bytes.fromhex(value).decode() for value in re.findall(r"X'([0-9a-f]*)'", statement)]
        for row in ROWS:
            for column in ("event_type", "visitor_hash", "source", "context"):
                if row[column]:
                    self.assertIn(row[column], restored)
        self.assertIn("(9, 0, ", statement)
        self.assertIn("'2026-06-01 23:59:59', '', ", statement)
        self.assertIn("NULL)", statement)

    def test_restore_refuses_an_object_that_does_not_match_the_record(self) -> None:
        self.assertEqual(self.run_archive("archive").returncode, 0)
        result = self.run_archive("restore", "--day", "2026-06-01", CORRUPT="1")
        self.assertEqual(result.returncode, 1)
        self.assertNotIn("INSERT IGNORE", (self.state / "calls.log").read_text())


if __name__ == "__main__":
    unittest.main()
