# Manacost reader: login and account first slice

Status: WordPress shell deployed disabled; isolated login activation prepared. This is
not the complete reader v1: sessions last at most five minutes, without refresh
or saved articles. Existing WordPress and Cackle comments remain disabled.

## Boundaries

HearthPulse owns reader identity. WordPress continues to own editorial HTML,
theme and article access policy, but never creates a reader WP user, sets a WP
authentication cookie or receives an upstream token. The small Node BFF under
`services/reader` owns a separate, host-only Manacost session. .ru, .com and
staging need distinct client registrations, cookies and configuration.

1. `/reader-auth/start` stores a browser-bound, single-use state, nonce and PKCE
   verifier, and redirects to the configured HearthPulse issuer.
2. HearthPulse uses its existing login and asks for explicit consent. The grant
   is bound to that exact canonical browser session, not an email match.
3. `/reader-auth/callback` uses pinned `openid-client` to validate the signed ID
   token, issuer, audience, nonce, state and PKCE exchange. Cancellation returns
   to the stored safe relative page without creating a session.
4. The browser receives only an opaque `__Host-manacost_reader` cookie:
   Secure, HttpOnly, SameSite=Lax, Path=/, no Domain, maximum age 300 seconds.
5. Every private profile read checks token activity and the canonical parent
   session online. No positive authorization cache. Parent logout, reset,
   deletion or block invalidates reader access. A fresh login is required after
   expiry. Independent long-lived sessions need a later security-epoch design.

State/session keys are hashed; tokens and login payloads use AES-256-GCM with
deployment-managed keys and record-bound associated data. Callback rotation and
logout are transactional: an in-flight re-login cannot restore a logged-out
session. Local logout succeeds even if HearthPulse is down; encrypted pending
revocations are retried by a bounded worker every 15 seconds. Request budgets,
body/header limits, Origin/CSRF checks and separate authenticated rate buckets
protect the private boundary. Tokens, codes and cookies must never be logged.

## Account UI contract

The opt-in MU module registers `[hs_manacost_reader_account]` only when
`HS_MANACOST_READER_ENABLED` is explicitly `true`. It does not create pages or
take over an existing `/account/`. Provision a published page with that slug
and shortcode deliberately on staging. Only then does it add a `Кабинет` link
to Newspaper's `header-menu` and load scoped CSS/JS on the account page.

The anonymous shell inherits the site header and layout. The navy panel,
local Roboto/Arial stack, blue actions, keyboard focus and 44-pixel targets are
scoped under `mc-reader`; no theme files, external fonts or optimization
settings are changed. Typography selectors override Newspaper's page-content
styles without touching other pages. The markup contains no private identity.
The future saved articles section is explicitly labelled unavailable.

| Endpoint | Result |
| --- | --- |
| `GET /reader-api/v1/me` | 200: `{user:{displayName},csrfToken,profileUrl}` |
| Same endpoint | 401: guest/expired; 503: identity unavailable |
| `POST /reader-auth/logout` | Origin and `X-Reader-CSRF`; 204 clears cookie |

`profileUrl` is a verified HearthPulse link in production and `null` in staging.
No internal user ID or token is exposed in the DTO. UI updates use text nodes,
clear private data on failure/pagehide, discard stale responses, and show a
retry action on network failure or after a seven-second browser deadline.
Logout retry retries logout, not a profile read. There is no localStorage auth.

## Isolated staging deployment

The staging identity is `https://test.hearthpulse.net/identity`, with the sole
callback `https://test.hs-manacost.ru/reader-auth/callback`. It uses new keys and
an empty canonical HearthPulse database, not production accounts. Email/password
and mail-code verification remain the existing HearthPulse flow.

Source-controlled tooling is under `ops/reader/`. Provisioning defaults to a
read-only dry run and refuses existing keys/state. Create dedicated system users
`hearthpulse-identity-staging` and `manacost-reader-staging` with their matching
`/var/lib/` home directories and no login shell. Run provisioning with `--apply`
only once. Secret env files are root-only 0600; application state is 0700.

Both release scripts require a clean exact source SHA, publish immutable
artifacts under their distinct `/srv/*-staging/releases/` roots and retain a
previous link. They install units but never start/restart them. Identity artifacts
contain only built client/server files and locked runtime dependencies, no source
environment or production data. The frontend dist is public-readable; server
code is readable only by root and the service group.

The identity unit binds loopback 18182, denies production data roots and external
egress, and disables startup jobs and Redis. The reader binds loopback 18181;
its egress permits only loopback and the pinned HTTPS origin 151.80.21.140. A
service-private read-only hosts file resolves the issuer directly to the origin,
retaining its hostname, SNI and certificate verification; the host's global
resolver/hosts file are unchanged. Public browsers use Cloudflare because the
existing firewall blocks non-proxy ingress. Revalidate this mapping if the origin
address changes. Nginx sanitizes forwarding headers,
preserves OIDC Basic/Bearer headers at the provider, strips WordPress BasicAuth
only toward the BFF, and disables both access and error query logging for auth.
The WordPress staging password remains mandatory for reader routes.

Synthetic acceptance uses loopback SMTP 18183 accepting only the disposable QA
recipient. Codes remain in a private runtime file, never in logs/screenshots.
After synthetic acceptance, SMTP 25 may send user-requested verification codes
through the existing MTA with sender `noreply@hs-manacost.ru`; this reuses a sender
address, not production credentials/users. Mail links point to the test host.
Actual mailbox receipt is a separate check; successful SMTP acceptance is not
proof of delivery. Background newsletters remain disabled.

First activation rollback: disable `HS_MANACOST_READER_ENABLED`, restore the
backed-up staging nginx file, validate/reload nginx, and stop only the two new
staging services. Preserve the new databases, keys, releases and account page
(draft it if necessary); do not restore production databases. Rehearse service
stop/restart and unchanged key/database retention before exposing the entrypoint.
Subsequent binary rollback scripts switch only a validated previous symlink and
still require an explicit service restart. HTTPS/DNS belong solely to the new
test hostname and can remain available while login is disabled.

## Deployment prerequisites

### Explicit production identity bridge

The user selected real `hearthpulse.net` accounts for the test cabinet. The BFF
accepts this only with `READER_ALLOW_PRODUCTION_IDENTITY_FOR_STAGING=1` and the
exact tuple: staging, `https://test.hs-manacost.ru`,
`https://hearthpulse.net/identity`, `manacost-reader-staging`. Other combinations
still fail closed. The provider separately requires its exact-client bridge flag.

`ops/reader/provision-production-bridge.mjs` creates fresh root-only configuration
without reading existing production keys, environment or users. It refuses
existing targets, keeps the provider disabled, and selects a new reader database
`production-identity.sqlite` plus independent encryption/CSRF/client keys.
Do not merge old subjects, sessions or revocation queues across issuers.

After reviewed production provider deployment and proxy verification, install
the corresponding provider/reader systemd drop-ins and private hosts file.
The BFF reaches the pinned public Moscow edge with normal HTTPS verification;
its egress remains explicitly limited. Stop the BFF before switching config,
restart, then prove the public redirect targets HearthPulse and complete browser
acceptance with a user-approved account. Existing WordPress accounts and comments
are untouched. Rollback stops the BFF and removes only the bridge drop-in to
restore the old config/database; preserve both sets of state and keys.

### General requirements

The existing release workflows run checks but **do not deploy the BFF**. A
separately reviewed staging setup must provide all of the following:

- Node 22.22.2, locked dependencies, an unprivileged supervised process running
  `node services/reader/server.js`, and a private persistent SQLite directory.
- `READER_ORIGIN`, `READER_ISSUER`, `READER_CLIENT_ID`, `READER_CLIENT_SECRET`,
  `READER_DEPLOYMENT`, absolute `READER_DATABASE`, base64url 32-byte
  `READER_ENCRYPTION_KEY` and `READER_CSRF_KEY`; optional `READER_PORT` defaults
  to 18081. Inject secrets through managed deployment configuration, not source,
  WP options, public HTML or command-line arguments. Backup keys separately.
- A matching opt-in HearthPulse provider with persistent signing/encryption and
  cookie keys, an exact HTTPS callback and an isolated staging issuer/client.
- A reviewed reverse proxy for `/reader-auth/` and `/reader-api/` to loopback
  BFF, preserving the exact configured Host. No cache, redirects to other
  origins, access-log query strings or credentials. Keep port 18081 private.
- Bypass every edge/page/optimizer cache for private routes and `/account/`;
  preserve `private, no-store` and Set-Cookie on successful and failed
  responses. Public articles remain anonymous/cacheable. Exclude this account
  bundle from delay/combine/unused-CSS transforms until guest testing confirms
  correct loading; do not disable optimization site-wide.
- Consent/privacy text, private DB/backup permissions, retention and provider
  tombstone/session-binding cleanup, supervised restart and tested key recovery.

Enable the UI flag last, after BFF/proxy/provider checks. Validate guest and
authenticated browser flows on real staging HTTPS, cache warm and cold,
desktop/mobile, cancellation, expiry, parent reset/block, outage, and logout
during callback. A local fixture is not evidence of successful live SSO.

Both RU staging edges additionally include `ops/reader/proxy-staging-reader.conf`
inside the existing HTTPS vhost using the single-include companion patch. This
keeps their BasicAuth gate, suppresses callback queries in access/error logs,
and enables CA-verified origin TLS for the reader routes (the pre-existing
general proxy configuration is not changed). Back up each exact vhost before
patching, run patch dry-run and `nginx -t`, then reload; rollback restores that
vhost and reloads without touching its shared production configuration.

Rollback hides the UI by disabling its flag first, disables new authorization
at the provider, and stops routing the BFF. Preserve its encrypted DB and keys;
do not delete users, historical comments or editorial data. Existing WP admin
login and public article rendering remain independent.

## Verification and ownership

Install `npm ci --prefix services/reader --ignore-scripts`; run `make reader-test`
for focused tests, `make check` for canonical static/unit checks,
`make code-quality` for PHPStan/WPCS and `make integration` for disposable
WordPress integration. CI provisions the same Node version and locked client.
Run `npm ci` and `npx playwright install chromium`, then
`make reader-browser-test` for the self-contained browser regression using the
real Newspaper CSS and synthetic API responses. CI runs that same target.
An optional `READER_TEST_CHROMIUM` selects an already-installed local browser.
The first-party `reader-identity` boundary is classified high security risk in
`config/change-impact-map.json`. No verification command uses live accounts.

Next slices are staged end-to-end activation, longer-lived session policy, and
bookmarks with authoritative WordPress article/VIP checks. A stored
`(site_id, wp_post_id)` is not permission to reveal unpublished or paid content.
Comments are not part of this activation and must remain disabled.
