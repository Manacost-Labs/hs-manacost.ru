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
