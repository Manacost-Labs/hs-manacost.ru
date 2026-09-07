# Mirror headers on cached responses

## Cause and scope

WP Rocket can return cached HTML before MU-plugins execute. Thus `.com` cached
responses lacked both the PHP mirror marker and `X-Robots-Tag`, while a fresh
PHP response contained them. Keep the host policy in the mirror nginx vhost,
without changing `.ru`, staging, DNS, certificates, WordPress data or content.

Production nginx is 1.30.1. The narrow vhost policy requires >= 1.29.3 so
`add_header_inherit merge` preserves both mirror headers inside nested
asset/security locations that already define their own `add_header` values.
Existing same-value security headers may consequently repeat on those routes;
their restrictive values are unchanged. Hide the duplicate FastCGI mirror
marker, but retain upstream `X-Robots-Tag`: a stricter page-level `nofollow`
or `noarchive` must not be discarded.

Reference: https://nginx.org/en/docs/http/ngx_http_headers_module.html#add_header_inherit

## Verification and release

- `python3 -m unittest discover -s tests -p test_nginx_mirror_headers.py -v`
- `python3 ops/nginx/tests/check_mirror_headers.py`: isolated unprivileged nginx,
  real cold/warm HTTP assertions for cached HTML, redirects, errors and nested
  locations, plus primary/staging controls. No live content or secrets.
- `make check`, `make integration`, staged secret scan, independent review.
- Stage the exact Git revision through normal CI. Nginx is a separate config
  activation: ordinary WordPress deployment does not install this vhost.
- Before activation, verify the old live vhost still equals the recorded source
  revision, save it in a protected backup directory, and rehearse the candidate
  against isolated/staging nginx before `nginx -t` and a graceful reload.
- Validate the complete candidate with the real include chain in a private
  mount namespace: bind only the versioned mirror vhost over its live path
  within that namespace and run `nginx -t`. The host's mounted configuration
  and running nginx are unchanged by this syntax test.
- Install only the reviewed `ops/nginx/mirror.conf`; keep `.ru` and test vhosts
  untouched. If syntax or acceptance fails, restore only this vhost backup,
  run `nginx -t`, reload and recheck. Never restart PHP or restore WordPress DB.
- Refresh affected mirror delivery caches using their existing owner. Check
  normal and fresh HTML, robots, sitemap, errors and redirects on origin and
  both regional proxies; verify `.ru` canonical and indexability unchanged.
- Production smoke requires the mirror headers on every tested origin/edge
  response, including the top static image, repeated home/error/directory
  redirect requests. The primary must not gain a mirror marker or site-wide
  noindex; a legitimate page-level 404 noindex remains allowed.
- Only after that host gate is green, promote the exact staging-tested
  WordPress revision containing the already-reviewed comments policy.

The independent reader-login architecture is planning-only and is not part
of this infrastructure deployment.
