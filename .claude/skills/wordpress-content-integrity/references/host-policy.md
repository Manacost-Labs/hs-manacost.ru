# Host policy

| Host | Content | Canonical | Robots/indexing |
|---|---|---|---|
| `hs-manacost.ru` | production | self-referencing `.ru` | indexable according to WordPress/SEO settings |
| `hs-manacost.com` | same production content/media | matching `.ru` URL | noindex mirror |
| `test.hs-manacost.ru` | isolated staging content/database | staging-safe policy | fully noindex and access protected |

The mirror must preserve article rendering, images and view behavior without becoming a second SEO competitor. Staging content must not enter production automatically. Compare host-dependent metadata separately from body equivalence.

After content or domain changes, verify canonical, robots, sitemap exposure, Open Graph URL, redirects and cache separately on the primary, mirror and staging hosts.
