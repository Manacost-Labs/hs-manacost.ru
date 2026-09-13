# hs-manacost v2 sandbox

`v2.hs-manacost.ru` is a persistent, independent Next.js frontend for gradual
product and visual experiments. WordPress remains the editorial CMS and the
canonical public site. The first iteration is deliberately read-only.

## Boundaries

- `services/web-v2` owns rendering, routes, components, tokens and the public
  WordPress response adapter.
- `https://hs-manacost.ru/wp-json/wp/v2` is the only content source. The app
  sends GET requests only and uses no WordPress credentials.
- Category `2006` (`vip`) is excluded at the request, normalization and route
  boundaries. Auth, profiles, comments, views, analytics and writes are absent.
- WordPress HTML is allowlist-sanitized before rendering. Script, event-handler,
  advertising/paywall fragments and unsafe protocols are removed.
- Every response carries `X-Robots-Tag: noindex, nofollow, noarchive`; metadata
  is noindex and `/robots.txt` disallows the entire host.
- `wordpress/**`, `services/reader/**`, production theme assignments, database,
  uploads and the existing `.ru`/`.com`/`test` virtual hosts are not modified.

## Runtime

The standalone Next.js server listens on `127.0.0.1:3212`. Nginx owns HTTPS and
proxies only the `v2.hs-manacost.ru` host. Immutable releases live at
`/srv/hs-manacost-v2/releases/<git-sha>` with atomic `current` and `previous`
links. The application process runs as `hs-manacost-v2` and may write only its
Next fetch-cache directory. Rendered pages are dynamic, so Next does not need a
writable ISR page-output tree. The media proxy is deliberately excluded from
Next's upstream fetch cache; the bounded response is cached by browser/proxy
headers only after validation.

Deployment is split into reviewable actions:

1. Merge an exact clean SHA to `origin/main` after `make web-v2-check` and review;
   the release script fetches and requires that exact remote revision.
2. Provision the dedicated user/directories and install the reviewed systemd
   unit; do not restart unrelated services.
3. Prepare an immutable release with `ops/web-v2/release.sh --sha <SHA> --apply`;
   the build pins `WORDPRESS_API_URL` to the public production REST endpoint.
4. Verify `127.0.0.1:3212/api/health` before installing the dedicated vhost.
5. Provision DNS/TLS, validate `nginx -t`, gracefully reload nginx, and verify
   HTTPS headers, robots, homepage, category, article and missing route.

`ops/web-v2/release.sh` prepares and activates only this service. If health
fails it restores the previous symlink and restarts only `hs-manacost-v2`.
For a manual rollback, point `current` to the validated `previous` target and
restart only that unit. Removing the v2 vhost/DNS does not change WordPress.

## Local development

```bash
npm ci --prefix services/web-v2 --ignore-scripts
npm run dev --prefix services/web-v2
```

Override the content source only with a reviewed public URL:

```bash
WORDPRESS_API_URL=https://hs-manacost.ru/wp-json/wp/v2 npm run dev --prefix services/web-v2
```
