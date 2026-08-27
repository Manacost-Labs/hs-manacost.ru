# Incident matrix

| Symptom | First comparison | Likely owner | Do not start with |
|---|---|---|---|
| `DNS_PROBE` / no resolution | authoritative DNS, public resolvers, DNSSEC | registrar or Cloudflare DNS | changing origin application |
| TLS or redirect loop | certificate/SNI, redirect chain, host headers | Cloudflare/Nginx/domain mirror | disabling HTTPS |
| 502/504 | proxy versus origin, PHP-FPM state, upstream timing | proxy/Nginx/PHP-FPM | rebooting all nodes |
| WordPress fatal/blank page | PHP status, redacted error log, last deployment | plugin/theme/PHP | deactivate-all |
| images missing | HTML URL, content type, origin/S3/sidecar existence | media pipeline/S3/Nginx | deleting local uploads |
| wrong image | attachment metadata, object checksum, duplicate-name history | media integrity | overwriting the object again |
| stale page/banner | origin cold/warm versus each edge | WP Rocket/Cloudflare/proxy | global cache purge |
| admin only failure | authenticated bypass, nonce, WAF, PHP | WordPress/Wordfence/cache policy | weakening public security |
| one region fails | origin and each proxy health endpoint | regional proxy/network | changing WordPress data |
| slow site | DNS/connect/TTFB, PHP, SQL, cache hit, image weight | first slow layer | speculative optimization |

Use timestamps and exact URLs. A later layer cannot repair an earlier failed layer.
