# Runtime stack ownership map

The production result is produced by WordPress plus several caching, optimization, SEO, security and delivery layers. Change the owner, not every layer that can mask the symptom.

| Owner | Active component | Responsibility | Common overlap/risk |
|---|---|---|---|
| Page cache | WP Rocket | Cached HTML, preload, CSS/JS optimization | Duplicated optimization with Perfmatters; stale HTML at edges |
| Object cache | Redis Cache | Persistent WordPress object cache/drop-in | Flush has site-wide blast radius and does not clear edge HTML |
| CDN/edge | Cloudflare plugin/account | Edge cache and purge integration | Outer stale copy; WAF/cache rules outside Git |
| Regional delivery | Moscow and Novosibirsk proxies | Stable RF access to the same origin | One proxy can retain different HTML/assets |
| Frontend optimization | Perfmatters | Script/style controls, delay and other selected optimizations | Double minify/delay/lazy-load with WP Rocket |
| SEO | All in One SEO Pack | Titles, descriptions, canonical/schema/sitemap behavior | Must not override `.ru`/`.com` mirror policy |
| Redirects | Redirection | Managed URL redirects and logs | Loops/chains; conflict with nginx or canonical logic |
| Security | Wordfence | WAF, login protection and scans | False positives on editor AJAX/REST; optimized firewall bootstrap |
| Content purge | `manacost-cache-purge.php` | Targeted invalidation after content changes | Broad hooks can cause purge storms |
| Domain policy | `manacost-domain-mirror.php` | `.ru` canonical, `.com` noindex mirror | SEO duplication if bypassed by plugin settings |
| Media | S3/offload and image MU-plugins | Object delivery, optimization and filenames | Cache can hide overwritten or missing objects |

Other active plugins are enumerated in `config/wordpress-plugins.json`; versions and source metadata live in `config/plugins.json`. Do not infer that an installed plugin is active, and do not index inactive plugin code as production behavior.

## Conflict review

Before changing an option, search committed source for hooks/constants touching the same behavior. Pay particular attention to cache exclusions, delayed script exclusions, host/canonical filters, upload URLs, preview cookies, REST/AJAX endpoints and content save/status hooks.
