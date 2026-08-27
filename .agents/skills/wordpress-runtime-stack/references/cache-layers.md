# Cache layers and invalidation

## Request path

Think from the requester inward:

1. Browser cache.
2. Public/regional proxy and Cloudflare edge behavior.
3. Web server/PHP delivery.
4. WP Rocket page cache and optimized assets.
5. WordPress plus Redis object cache.
6. Database, S3/media and other sources of truth.

A correct origin response does not prove a correct public response. A stale browser response does not prove the origin is stale.

## Targeted content purge

For a changed article:

1. Confirm the database/source update succeeded and the uncached origin can produce the intended result.
2. Let `manacost-cache-purge` and WP Rocket invalidate the article and known dependent archives.
3. Purge the exact HTML URLs from Cloudflare and configured regional proxy caches when their normal integration did not do so.
4. Purge/version only affected asset URLs if the issue is an asset, not article HTML.
5. Warm the canonical public URL and required archive URLs through the intended public paths.
6. Compare a cold miss and subsequent warm hit on origin/public/proxy routes.

Do not flush Redis for an ordinary post update. Clear a narrow object group/key only after proving that object data is stale; use a full flush only for a separately authorized incident with understood impact.

## Safe diagnostics

- Use unique query values only to distinguish browser/proxy observations; do not assume they bypass every configured cache.
- Compare status, final URL, cache-related response headers, age, content hash and canonical/robots output.
- Test anonymous and authenticated/editor requests separately. Preview, login, cart-like personalization, AJAX and REST writes must bypass public page cache appropriately.
- Avoid copying secret cookies or authorization headers into logs or tickets.

## Official references

- [WP Rocket cache preloading](https://docs.wp-rocket.me/article/8-preload-cache)
- [WP Rocket cache lifespan](https://docs.wp-rocket.me/article/78-how-often-is-the-cache-updated)
- [WP Rocket programmatic cache clearing](https://docs.wp-rocket.me/article/1801-how-to-programmatically-clear-the-cache-and-optimizations)
- [Cloudflare cache purge](https://developers.cloudflare.com/cache/how-to/purge-cache/)
- [Cloudflare single-file purge](https://developers.cloudflare.com/cache/how-to/purge-cache/purge-by-single-file/)

Prefer URL/tag/host-level purge when available. Purge Everything has a large performance and availability blast radius and is not a default troubleshooting step.
