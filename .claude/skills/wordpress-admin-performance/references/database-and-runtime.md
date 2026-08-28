# Database and runtime diagnosis

## SQL and data access

- Find N+1 queries across posts, users, terms, attachments and post meta.
- Select only fields needed for the visible page and paginate on the server.
- Bound `posts_per_page`, offsets, date ranges and search work. Avoid loading all IDs before pagination.
- Prefer WordPress query APIs and existing indexes. Add an index only with measured query plans, migration safety and rollback.
- Cache stable computed results with explicit keys, groups, TTL and targeted invalidation.

## Options and bootstrap

- Measure autoloaded option count and bytes; identify the owning plugin before changing data.
- Do not move an option out of autoload when every admin request still needs it.
- Avoid expensive work on `admin_init`, `init` or every admin request when a screen-specific hook is available.
- Scope plugin checks, migrations and remote license/update calls away from hot request paths.

## Object cache and invalidation

- Separate object-cache hits from page/edge cache behavior; authenticated wp-admin normally bypasses page cache.
- Use targeted invalidation and stable cache keys. Never rely on a global Redis flush for correctness.
- Verify permissions and user-specific values cannot leak through shared cache entries.

## Remote calls, cron and background work

- Do not perform synchronous third-party requests while rendering an admin screen when stale cached data or background refresh is acceptable.
- Set bounded timeouts, safe retries and circuit-breaking behavior for external services.
- Deduplicate cron events and move heavy batch work out of interactive requests.
- Show queued/background state in the UI and make retry/idempotency explicit.

## PHP memory and payload size

- Avoid constructing full post/media collections, giant localized-script objects or serialized blobs.
- Stream or batch exports and bulk work. Keep every batch bounded and resumable.
- Treat rising peak memory as a regression even when the current limit hides it.
