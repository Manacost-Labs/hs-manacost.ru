# Admin performance diagnostics

`summarize-admin-timing.py` reads the existing JSON timing log and prints only
route-level counts and latency summaries. Use an explicit bounded UTC interval:

```bash
ops/performance/summarize-admin-timing.py \
  /var/www/httpd-logs/hs-manacost.ru.timing.access.log \
  --since 2026-09-24T19:13:00+00:00 \
  --until 2026-09-25T00:00:00+00:00
```

The log does not establish authentication or prove that a post was saved. A p95
is reported only for at least 20 responses with the expected route and status.
No IPs, user agents, referrers, query strings, SQL, or post data appear in the
summary. Keep the raw log outside Git and shared artifacts.

The staging workflow collects five warm samples for Dashboard, posts, media,
the editor, and diagnostic samples for Plugins, Users and General Settings.
The three diagnostic screens have no approved budgets yet, so their raw metric
reports are uploaded without a pass/fail evaluation. Existing screen budgets
remain unchanged.

The article diagnostic also records grouped script/style resource timing.
When Query Monitor is temporarily active on staging, the collector writes a
`diagnostic-sql-callers.json` artifact for all measured screens. It contains
only sanitized caller names with query counts and total durations; neither SQL
text nor Query Monitor's raw page data is exported. Query Monitor must remain
inactive after a manual profiling run. Its extra overhead means timing samples
from that run must not be compared with ordinary runs.
