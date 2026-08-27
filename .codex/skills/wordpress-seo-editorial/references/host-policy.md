# Host policy

## Contract matrix

| Surface | `hs-manacost.ru` | `hs-manacost.com` | `test.hs-manacost.ru` |
|---|---|---|---|
| Purpose | Primary production and SEO source | Emergency functional mirror | Isolated integration/staging |
| Indexing | Indexable unless page-level editorial policy says otherwise | Always `noindex, follow` | Always `noindex, nofollow, noarchive` |
| Canonical | Matching `.ru` URL | Matching `.ru` URL | Must never become a production canonical |
| Sitemap | Production sitemap on `.ru` | Do not advertise a mirror sitemap | Do not advertise an indexable sitemap |
| Internal public links | Prefer `.ru` | May be rewritten to `.com` so the mirror remains usable | Stay inside staging during tests |
| Structured-data URLs | `.ru` | Equivalent graph must still identify `.ru` canonical entities | Never leak into production output |

## Audit rules

1. Inspect both the HTTP `X-Robots-Tag` and HTML robots meta. A protective header is mandatory for mirror/staging even if cached HTML is malformed.
2. Require exactly one canonical on normal HTML documents. On the mirror it must use `https`, `.ru`, and the matching path/query policy.
3. Compare cold and warm responses because WP Rocket, Redis, Cloudflare, and RU proxy caches can preserve different metadata.
4. Check redirects separately. A redirect response must not escape to staging or create a `.ru`/`.com` loop.
5. Check robots and sitemap endpoints separately from page HTML. The mirror must not expose a self-promoting sitemap directive.
6. Inspect rendered JSON-LD as JSON. Entity URLs, image URLs, author and dates must agree with visible content and the canonical host.

## Failure severity

- Block release: mirror/staging becomes indexable; mirror self-canonical; `.ru` canonical points away; multiple canonicals; staging URL leaks into production schema/sitemap.
- Fix before editorial publication: missing title/description, invalid JSON-LD, missing representative image, broken internal link, image without meaningful alt where the image conveys content.
- Advisory: title length, keyword density, automated SEO score, or non-critical alt text on decorative images.
