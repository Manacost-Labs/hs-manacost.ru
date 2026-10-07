# Koloda Hearthstone data access

`wordpress/mu-plugins/manacost-koloda-api.php` is loaded automatically by
WordPress after deployment. Future PHP features call `Manacost_Koloda_API::get()`.
The client performs no requests just by loading, adds no public proxy or admin UI,
and requires no login for the public endpoints verified on 2026-10-07.

Provider sources: [statistics OpenAPI](https://api.kolodahearthstone.com/docs),
[card-library route index](https://api.kolodahearthstone.com/api/v1).
The root homepage redirects to GitHub login; this is separate from public data.
Premium/meta-intelligence and administrative endpoints are excluded.

```php
$cards = Manacost_Koloda_API::get(
    '/api/v1/constructed-cards',
    array( 'format' => 'standard', 'page' => 1, 'per_page' => 20 )
);
$archetypes = Manacost_Koloda_API::get(
    '/v1/constructed/archetypes', array( 'limit' => 20, 'offset' => 0 )
);
$heroes = Manacost_Koloda_API::get('/v1/bg/heroes', array( 'limit' => 20 ));
$arena = Manacost_Koloda_API::get('/v1/arena/classes', array( 'limit' => 20 ));
```

On success the returned array has `data` (the original provider JSON),
`fetched_at` (Unix time), `stale` and `error` (null or a safe error code).
For lists, rows are usually in `$cards['data']['data']`. Keep provider metadata,
source timestamps and pagination: cache time is not the gameplay sample time.
Individual cards use `/api/v1/constructed-cards/{card_id}` or
`/api/v1/constructed-cards/by-dbf/{dbf}`. Battlegrounds cards use `/api/v1/cards`;
other allowed libraries include heroes, trinkets, quests, rewards, anomalies,
timewarped cards, hero skins, pets and coins. `limit`/`offset` belong to statistics;
card-library pagination uses `page`/`per_page` (the provider ignores `limit`).

Callers must check `is_wp_error()`, validate the fields they consume and escape
all remote text/URLs at rendering. On `stale=true`, show data age or a clear
fallback. Transport/schema/429/5xx failures can reuse cached data up to 24 hours;
401/403/404 and redirects return an error. No failure overwrites successful data.
The client does not import cards/media into WordPress or rewrite article content.

Fresh responses are cached for five minutes with query keys sorted; different
pages/filters remain separate. Filters are scalar and bounded to 200 bytes,
pagination to 100 rows. Only documented public routes on the fixed HTTPS host
are allowed; redirects and administrative/authentication paths are rejected.
The client reads at most 2 MiB, waits at most five seconds, and does not sleep or
retry during a request. Failed requests back off for 30 seconds or Retry-After
(at most one hour); 429 blocks new fetches across resources. Known fresh data
remains available during that cooldown. Transients are per WordPress site, so
staging caches stay separate; the production mirror shares its WordPress cache.

The `manacost_koloda_api_result` action supplies `(path, outcome, age_seconds)`
for local monitoring, without query values or response bodies. No secrets or
personal data are collected. The provider sees the server's normal outbound
network identity. There is no scheduled synchronization: a future feature that
needs prewarming should add and test its own bounded background job, keeping cold
network access away from critical page/editor flows.

Disable access with `MANACOST_KOLODA_API_ENABLED=false` in approved runtime
configuration, before cached data and HTTP are used. Credentials are unnecessary
for the current public contract; if the provider changes authorization, update
this integration explicitly rather than reusing browser/GitHub sessions.

Verification: `python3 -m unittest tests.test_koloda_api -v`, `make code-quality`,
`make check`, `make contracts` and the staged secret scan. After staging deployment,
use WP-CLI `eval` to invoke the four examples and report only row count, freshness
and duration. A second identical read should use the cache. Roll back by reverting
the integration commit through the usual pipeline; cached public data expires on
its own and no database/content migration needs reversing.
