# Performance

Official documentation: [Newspaper theme documentation](https://forum.tagdiv.com/newspaper-theme-documentation/).

Use this file with the official WordPress `wp-performance` skill and measured browser traces. Newspaper-specific optimization must preserve rendering, cache coherence, counters, ads, and editor behavior.

## Known coupled surfaces

- `td-composer` and `td-standard-pack` frontend assets.
- Newspaper queries, modules, blocks, related posts, and AJAX view endpoints.
- `manacost-performance-optimizer.php`, `hs-manacost-newspaper-trim.php`, `hs-admin-load-trim.php`, `hs-admin-ajax-guard.php`, and `manacost-cache-purge.php`.
- WP Rocket, Redis Object Cache, Perfmatters, S3 media fallback, and RU proxy caches.

## Rules

- Measure before and after; separate TTFB, LCP, INP, CLS, PHP time, DB time, and transfer size.
- Do not dequeue an asset until its dependency and runtime use are proven absent on the tested route.
- Never optimize editor/admin traffic with frontend-only assumptions.
- Bound queries and pagination; avoid remote requests during render.
- Cache only public deterministic output with explicit TTL and targeted invalidation.
- Do not cache nonce-bearing or user-specific HTML publicly.
- Preserve view-count requests and prevent proxy/cache duplication.
- Purge the smallest affected keys/routes after content or layout changes.

## Required evidence

- Before/after URL, environment, viewport, cache state, and measurement tool.
- Staging origin plus public/proxy behavior when infrastructure is involved.
- Browser console/network and PHP error log free of new issues.
- No regression in article images, ads, canonical tags, or Composer save flows.
