# Migration verification

## Before

- Confirm exact environment/database name without printing credentials.
- Confirm free disk and backup freshness.
- Run dry-run twice; counts must be deterministic.
- Validate a recent backup by restoring to a temporary, non-networked database.

## After each batch

- Read, changed, skipped, failed and remaining counts reconcile.
- No duplicate keys/rows were introduced.
- Serialization decodes and unknown fields remain present.
- Referential attachment/post relationships remain valid.
- Error rate and query duration remain inside the stop threshold.

## Application proof

Verify affected frontend pages, admin listing/editing, draft/autosave/revision/preview/publish where relevant, REST/AJAX consumers, canonical/noindex policy, media/S3 links, counters and targeted cache invalidation.

Keep the backup until the agreed observation window has passed.
