# Deck events indexes (2026-10-27)

`wp_ksoo_manacost_deck_events` receives every anonymous deck view, copy and
image open (about 90k rows a day). Production had 9.7M rows: 882 MB of data
and 1743 MB of secondary indexes.

The `Unified_HS_Deck_Events` read paths filter only by time, event type and
post:

| Query | Index it can use |
| --- | --- |
| `summary_counts()`, `counts_by_post()` | `event_time`, `event_type_time` |
| `daily_counts()` | `event_type_time`, `event_time` |
| `daily_counts_for_archetype()` | `post_event_time` |

Nothing reads `visitor_hash`, yet `visitor_event (visitor_hash, event_type,
event_time)` was the largest index at 1138 MB. Schema version 2 drops it; the
`visitor_hash` column stays.

## Rollout

`install()` runs from `Unified_HS_Installer` on admin and WP-CLI loads only.
`dbDelta()` never drops indexes, so the upgrade issues
`ALTER TABLE ... DROP INDEX visitor_event, ALGORITHM=INPLACE, LOCK=NONE`.
Dropping a secondary InnoDB index changes metadata only, but it needs a brief
exclusive metadata lock: the session waits at most 3 s for it so frontend
inserts never queue behind a long wait. If the drop fails, the stored version
stays at 1 and the next admin load retries.

Rollback: `ALTER TABLE wp_ksoo_manacost_deck_events ADD INDEX visitor_event
(visitor_hash, event_type, event_time), ALGORITHM=INPLACE, LOCK=NONE` (online,
minutes on 10M rows). Nothing in the code depends on the index.

## Retention: 90 days of raw rows plus a daily rollup

Schema version 3 adds `wp_ksoo_manacost_deck_event_days (event_day, post_id,
event_type, events)`. Raw rows keep only `RETENTION_DAYS = 90` days; reports
over older days read the rollup. In production 4.7M of 9.7M rows were older
than 90 days on 2026-10-27.

The option `unified_hs_deck_events_rolled_until` is the boundary date:

- days before it are read only from the rollup;
- days from it on are read only from raw rows (`event_time >= boundary`).

`count_rows()` reads both halves with `UNION ALL`, so `summary_counts()`,
`counts_by_post()`, `daily_counts()` and `daily_counts_for_archetype()` keep
their result shape and totals. The archetype report now resolves the term's
posts with `get_objects_in_term()` and filters `post_id IN (...)`, which still
uses `post_event_time`.

`run_retention()` runs from the hourly WP-cron hook
`unified_hs_deck_events_retention`, with a 20 s budget per run. It holds
`GET_LOCK('unified_hs_deck_events_retention')`, so a second runner (WP-cron's
own lock expires after 60 s) exits instead of racing it.

1. For each day before `CURDATE() - 90 days`, `INSERT ... SELECT ... GROUP BY
   post_id, event_type ... ON DUPLICATE KEY UPDATE events = GREATEST(...)`
   writes that day's counts, then the boundary moves past it (only ever
   forwards). Old days never gain events, so a rerun after a crash cannot
   shrink a correct count even if some raw rows are already gone.
2. Raw rows of a day more than one day before the boundary are deleted in
   batches of 5000 (`ORDER BY event_time, id LIMIT`) only when
   `wp_ksoo_manacost_deck_event_archives` holds that day and the day has no
   more raw rows than the archive recorded (see *S3 archive* below). The one-day lag keeps a report that
   read the previous boundary correct. The first production backlog takes
   several hourly runs; afterwards one day rolls up each night.

If the option is lost, the boundary is restored as `MAX(event_day) + 1` from
the rollup instead of re-rolling from the oldest raw row. Failures go to the
PHP error log.

No report reads `visitor_hash`, `source` or `context` (`context` in the admin
activity log belongs to a different table), so the database keeps only the
counts; the full rows stay recoverable from the S3 archive.

## S3 archive of raw days

`ops/deck-events/raw-archive.py archive` (root, hourly
`hs-manacost-deck-events-archive.timer`, at most 10 days a run) exports every
day older than 90 days that has raw rows and no archive record:

1. `SELECT JSON_OBJECT(...) ... ORDER BY id` → JSONL (one row per line, all
   seven columns) → gzip; the line count must equal `COUNT(*)` of the day.
2. `rclone copyto --immutable` to the crypt remote
   `hs-manacost-backups-3az/deck-events/` as
   `<environment>/raw/YYYY/MM/DD.jsonl.gz` (names and contents encrypted).
3. The object is read back; its SHA-256 must equal the local one.
4. Only then `INSERT INTO wp_ksoo_manacost_deck_event_archives (event_day,
   row_count, bytes, sha256, object_key, archived_at)`.

Any failure leaves the day unrecorded, so WordPress keeps its raw rows and the
next run retries. Objects are never deleted or replaced: the prefix
`deck-events` is outside `hs-data-platform`, the only prefix the
hs-data-platform archive prunes (`S3_REMOTE_KEEP_DAYS`; it lists only
`hs-data-platform-*-hs_data.dump`), and the script refuses a configuration
whose prefix contains or sits inside a prefix in
`DECK_ARCHIVE_PROTECTED_PREFIXES`.

Files:

- `/etc/hs-manacost/deck-events-archive.env` — non-secret settings, see
  `ops/deck-events/deck-events-archive.env.example`.
- `/home/debian/server/secrets/deck-events-archive/crypt.env` (root:root 600)
  — the rclone crypt password and salt. **Without this file the archive
  cannot be decrypted**; keep an offline copy with the other server secrets.
- S3 keys: `DECK_ARCHIVE_S3_CREDENTIALS_FILE`, the same keys the
  hs-data-platform archive uses.

Install (root):

```bash
install -D -m 755 ops/deck-events/raw-archive.py /usr/local/libexec/hs-manacost-deck-events-archive/raw-archive.py
install -m 600 ops/deck-events/deck-events-archive.env.example /etc/hs-manacost/deck-events-archive.env
install -m 644 ops/systemd/hs-manacost-deck-events-archive.{service,timer} /etc/systemd/system/
HOME=/root python3 -I /usr/local/libexec/hs-manacost-deck-events-archive/raw-archive.py check-config
systemctl daemon-reload && systemctl enable --now hs-manacost-deck-events-archive.timer
```

Restore one day (rows come back with their original `id`; `INSERT IGNORE`
skips rows that are still present, and the archived SHA-256 is checked first):

```bash
HOME=/root python3 -I /usr/local/libexec/hs-manacost-deck-events-archive/raw-archive.py restore --day 2026-06-01
```

Restored rows older than the boundary are invisible to reports (the rollup
already counts them) and the next retention run deletes them again, because
the archive record still exists. To keep them, delete that day's archive
record first.

Tests: `tests/test_deck_events_raw_archive.py` (failed upload or mismatching
read-back records nothing; restore decodes every value; prefix guard), the
WordPress fixture `deck-events-retention-integration.php` (unarchived days and
days with more rows than recorded are kept) and
`ops/integration/deck-events-archive-cycle.sh` (archive → WordPress deletes →
restore → identical rows, against the integration MariaDB).

Rollback: deploy the previous plugin version and unschedule the hook. That
code reads raw rows only, so its reports miss days older than 90 until the raw
rows are restored from the pre-release export or the regular database backups;
the rollup table keeps their counts either way.
