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
2. Raw rows more than one day before the boundary are deleted in batches of
   5000 (`ORDER BY event_time, id LIMIT`). The one-day lag keeps a report that
   read the previous boundary correct. The first production backlog takes
   several hourly runs; afterwards one day rolls up each night.

If the option is lost, the boundary is restored as `MAX(event_day) + 1` from
the rollup instead of re-rolling from the oldest raw row. Failures go to the
PHP error log.

Only aggregate counts survive: `visitor_hash`, `source` and `context` of rows
older than 90 days are deleted. No report reads them (`context` in the admin
activity log belongs to a different table).

Rollback: deploy the previous plugin version and unschedule the hook. That
code reads raw rows only, so its reports miss days older than 90 until the raw
rows are restored from the pre-release export or the regular database backups;
the rollup table keeps their counts either way.
