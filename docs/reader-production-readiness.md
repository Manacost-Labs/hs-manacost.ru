# Manacost Reader production-readiness gate

Status: candidate preparation only. Production is not activated by this
document or by merging its source changes.

## Release boundary

The Reader remains independent from WordPress users and native WordPress
comments. WordPress owns the anonymous shell, eligible-article decision and
hashed static assets. The private Node service owns HearthPulse OIDC sessions,
profiles, comments, reactions, favorites and image normalization. HearthPulse
owns identity, paid entitlement and administrator truth.

Production community features fail closed unless both environment gates are
present:

- `READER_COMMENTS_ENABLED=1` enables the community store;
- `READER_ALLOW_PRODUCTION_COMMUNITY=1` acknowledges production activation.

The WordPress half has the same two-key boundary:

- `HS_MANACOST_READER_COMMENTS_ENABLED=true` enables editorial routes and UI;
- `HS_MANACOST_READER_ALLOW_PRODUCTION_COMMUNITY=true` is required only on the
  exact production environment and exact `https://hs-manacost.ru` home URL.

The signed editorial client chooses only the exact staging or production
origin and rejects a response whose `site` does not match that origin.

The service additionally requires the exact tuple
`READER_DEPLOYMENT=production`, `READER_ORIGIN=https://hs-manacost.ru`,
`READER_ISSUER=https://hearthpulse.net/identity` and
`READER_CLIENT_ID=manacost-reader-production`. Near matches, the `.com` mirror,
staging ids and isolated test issuers are rejected before community schema
creation. The UI flag `HS_MANACOST_READER_ENABLED` is independent and is enabled
last.

## Preconditions

1. Merge a clean reviewed SHA. Quality, integration, browser, security and
   source-size checks must pass on that exact revision.
2. Deploy the same SHA to staging through the normal workflow. Prepare the BFF
   with `ops/reader/release-staging.sh`; the helper must not restart the service.
3. Complete a real staging flow: login, 30-day remembered session, profile edit,
   avatar upload, favorite add/remove, comment image from file and Ctrl+V,
   publication, reaction, public profile, administrator badge/delete/ban,
   logout and expired-session recovery.
4. Provision a separate production service account, absolute private database,
   encryption/CSRF keys, OIDC client secret and editorial credential. Do not
   copy staging SQLite data, sessions or keys.
5. Confirm HearthPulse accepts the production Reader client for OIDC,
   `/reader-entitlements` and `/reader-permissions`. Failure must deny privileged
   decoration/actions without exposing tokens or provider subjects.
6. Review the production reverse proxy independently. `/reader-auth/`,
   `/reader-api/` and `/account/` must bypass page, edge and optimizer caches;
   preserve `private, no-store`, `Set-Cookie`, bounded request bodies and the
   exact Host; keep the Node port loopback-only.
7. Take a recoverable database and key backup before the first community-enabled
   boot. Verify backup ownership/mode and restore procedure without replacing
   newer live data.

## Prepared production artifacts

The repository contains candidate-only operational contracts; none installs or
activates itself:

- `ops/reader/manacost-reader-production.service` isolates the production user,
  environment and SQLite state and reserves loopback port `18183`;
- `ops/reader/origin-production.conf` bypasses PHP and origin caches for only
  `/reader-auth/` and `/reader-api/`;
- `ops/reader/proxy-production-reader.conf` and
  `proxy-production-upstream.conf` define a separate TLS-verified edge pool;
- `ops/reader/release-production.sh` accepts only a clean merged `origin/main`
  SHA and prepares an immutable artifact without changing `current`,
  `previous`, systemd, Nginx, environment, database or feature flags.

Recheck the reserved port, the host resolver from `/etc/resolv.conf`,
HearthPulse edge addresses and all three loopback tunnels immediately before
installation. The service network allowlist must include the current resolver
as well as every required edge. A green source contract is not proof that the
live topology is unchanged.

### Production origin TLS pin

The live production origin currently presents a private self-signed
`hs-manacost.ru` certificate through the reverse tunnels. The system CA bundle
does not trust it, so the dedicated Reader edge route must use an explicit
public-certificate trust anchor at
`/etc/nginx/ssl/hs-manacost-reader-origin-ca.pem`. This file contains no private
key and must not be committed to Git.

Before changing either edge, read the public certificate from the active origin
vhost and verify its subject/hostname, validity window and SHA-256 fingerprint.
Copy that public certificate through the existing administrative channel, seal
it as root-owned mode `0644`, and verify every loopback tunnel with both the
pinned file and `-verify_hostname hs-manacost.ru`. The certificate fingerprint
observed through all three tunnels must equal the origin file fingerprint. Stop
if any listener differs, the hostname check fails, or expiry is too close for
the planned observation window. Never copy the origin private key and never
replace this contract with `proxy_ssl_verify off`.

Install the pinned public certificate and production upstream before including
the Reader location in the canonical production HTTPS vhost. Run `nginx -t`
before reload, then canary Novosibirsk before Moscow. The normal WordPress
upstream remains unchanged.

Certificate rotation is a two-phase operation: first deploy a trust bundle
containing both the current and next public origin certificates to both edges,
verify and reload them, then rotate the origin certificate. After all six
tunnels present the new fingerprint and regional Reader canaries pass, remove
the old certificate from the bundle in a separate reviewed change. Keep the
dual-trust bundle for the full observation window.

Rollback is phase-aware. Before the origin certificate changes, the previous
single-certificate bundle is a valid rollback target. After origin switches to
the next certificate, never restore a bundle that does not trust the certificate
the origin is actually presenting. To roll the certificate itself back, keep
dual trust on both edges, restore the previous origin certificate, verify all
six tunnels, and only then remove the next certificate from the bundle. To roll
back only the Reader route while leaving the new origin certificate active,
restore the prior edge vhost/snippet and upstream but retain a trust bundle that
still trusts the active origin. Do not roll back the Reader database or keys for
a TLS routing failure.

## WordPress administrator integration

There is currently no Reader comment queue inside `wp-admin` and Reader data is
not stored in native `wp_comments`. Existing moderation consists of the
HearthPulse-authorized controls on the article and a staging-only direct CLI.

The production admin integration is a separate release slice. It will add one
WordPress administration screen gated by `moderate_comments` and a WordPress
nonce. WordPress will call a dedicated HMAC-authenticated Reader admin API from
the server; the browser will never receive the service credential and PHP will
never open the Reader SQLite database. The screen needs paginated filtering,
comment/attachment inspection, deletion, reader ban/unban, conflict feedback
and an audit actor derived on the server. It must not create WordPress users,
copy comments into `wp_comments`, or expose HearthPulse subjects and tokens.

## Activation order

1. Prepare an immutable BFF artifact from the staging-verified SHA without
   changing `current`, the service or environment.
2. Validate the production environment with community flags absent. Start or
   restart the profile-only service and check guest/401/no-store behavior.
3. Apply proxy routing and verify direct origin plus every normal delivery path.
4. Enable `READER_COMMENTS_ENABLED=1` and
   `READER_ALLOW_PRODUCTION_COMMUNITY=1` together only after the database backup
   and HearthPulse private endpoints pass. Recheck schema ownership and service
   health before exposing UI.
5. Promote the same SHA through the protected `Promote production` workflow;
   it requires a successful staging deployment. Set both WordPress community
   constants only after the BFF canaries pass, enable the Reader UI last, then
   purge only affected HTML/assets and warm their content-hash URLs.
6. Run the manual flow again on `.ru`. The `.com` mirror must not become a
   second private application origin.

## Canary and performance evidence

Required canaries: `/account/`, auth start/callback/cancel/logout, `/me`, one
eligible article thread, attachment upload/removal, reaction write, favorite
write/list and public profile/avatar. Record status, cache headers, server
request id and bounded latency without cookies, tokens, query strings, profile
text or image bytes.

Compare cold and warm account navigation, profile visible time, comments visible
time, publication feedback, reaction feedback, request count, transferred JS/CSS,
FCP, LCP, CLS and long tasks. Synthetic results are release diagnostics; they do
not replace field data. Alert on 5xx/429 rate, timeout/abort rate, image rejection,
OIDC refresh/revocation failures and SQLite write errors using aggregate labels
only.

## Rollback

Disable the WordPress UI first, then both production community flags. Restore
the preceding immutable BFF and WordPress artifacts; validate origin and regional
delivery before reopening UI. Preserve the live SQLite database, current
sessions, encryption keys and backups. Never restore an older database over
comments, reactions, favorites or profile changes created after activation.

Rollback of code is not erasure. User-data removal continues through the Reader
API and its existing tombstone/retention rules. If HearthPulse or editorial
dependencies fail, keep public articles available and fail private writes closed.
