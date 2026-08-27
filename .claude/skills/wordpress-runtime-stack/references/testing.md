# Runtime stack test matrix

## Routes

Verify the affected URL through:

- staging (`test.hs-manacost.ru`);
- production canonical (`hs-manacost.ru`);
- mirror (`hs-manacost.com`), preserving noindex and `.ru` canonical;
- origin using the project-supported host-resolution method;
- Moscow proxy path;
- Novosibirsk proxy path.

Do not bypass TLS/host validation casually. Do not expose origin-only addresses or credentials in public reports.

## Cache checks

For each relevant route capture cold and warm requests: status, redirect chain, content marker/hash, canonical, robots, cache headers/age and selected asset URLs. Confirm a content update becomes visible without unrelated full purge.

Test anonymous and authenticated behavior separately when the feature includes editor preview, AJAX, REST, admin or personalization. Confirm public cache never stores privileged output.

## Plugin-owned scenarios

| Change owner | Minimum acceptance test |
|---|---|
| WP Rocket | Updated article/archive, cache miss then hit, preload, critical JS/CSS and mobile layout |
| Redis | `wp redis status`, representative reads/writes, no stale options/posts and acceptable fallback behavior |
| Cloudflare/proxy | Exact URL invalidation, both RF edges, images/assets, TLS and redirect parity |
| Perfmatters | Console/network errors, interactive menus/ads/shortcodes, LCP/INP/CLS comparison |
| All in One SEO | Titles/descriptions, canonical/robots, schema and sitemap on `.ru`/`.com`/staging |
| Redirection | One hop, final canonical, no loop, expected query/slash handling |
| Wordfence | Login, admin save, upload, AJAX/REST action and no new false-positive block |
| `manacost-cache-purge` | Publish/update/status change and only intended URL/dependent archive invalidation |

## Release gate

Run `make check`, project security checks, staging deployment and staging smoke-check. Production promotion requires the exact staging-tested commit. After promotion, repeat public, mirror, origin and both proxy smoke checks and retain the rollback decision criteria.
