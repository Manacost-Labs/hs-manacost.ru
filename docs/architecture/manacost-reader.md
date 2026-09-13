# Manacost reader: login and account first slice

Status: core login is activated on test.hs-manacost.ru only, using the production
HearthPulse issuer. See [editable reader profiles](../reader-profile.md) for the
next staging extension and [comments design](reader-comments.md) for the proposed
discussion layer. The staging Reader supports remembered login for up to 30 days
with encrypted server-side refresh tokens. Existing WordPress and Cackle
comments remain disabled; the separate Reader discussion layer is independent.

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
   Secure, HttpOnly, SameSite=Lax, Path=/, no Domain, maximum age 2,592,000 seconds
   when the exact staging client receives explicit `offline_access` consent.
   Login-attempt cookies still expire after 300 seconds; legacy/no-refresh
   sessions retain their original five-minute limit.
5. Every private profile read checks token activity and the canonical parent
   session online. No positive authorization cache. Parent logout, reset,
   deletion or block invalidates reader access. A fresh login is required after
   expiry. Remembered sessions remain bound to that original parent session;
   they do not survive its expiry or revocation.

### Remembered login

The staging client alone requests `openid profile offline_access` with explicit
consent. Access tokens still last five minutes. Thirty seconds before expiry,
the shared read/write verification path rotates credentials server-side and
introspects the new access token for the exact subject, client and active parent.
Activity never extends the local session's absolute deadline or resets its cookie.

`reader_session_tokens` is an additive companion table: encrypted refresh token,
access expiry and a durable one-shot claim. The original session/outbox schemas
stay compatible with the previous binary. A compare-and-swap transaction commits
new credentials only for the same active session and claim. Concurrent requests
share one rotation; a different process sees a temporary unavailable result.
An abandoned claim, ambiguous token response or rejected refresh ends local login
and queues family revocation instead of replaying a potentially consumed token.
Logout wins over in-flight rotation and clears the cookie even during an outage.
The outbox sends no token-type hint, so both access and refresh tokens are accepted.
Orphan credentials are removed by the existing bounded cleanup tick.

Deploy the reviewed provider policy before the BFF. Take a consistent private
SQLite backup first; no existing profiles, sessions or comments are rewritten.
Rollback preserves the database and keys and selects the previous binary.
Remembered sessions then fail closed when their short access token expires;
users can log in again. Never restore an old authentication database snapshot
over live state: that can undo logout/revocation. New duration requires a fresh
login; existing five-minute cookies are not silently extended.

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

### Surface asset boundary

`wordpress/mu-plugins/hs-manacost-reader/assets.php` is the only registry for
Reader handles, source files and dependencies. The loaders select logical assets
for one surface; they do not construct URLs or duplicate cache versions:

| Surface | CSS composition | JavaScript composition |
| --- | --- | --- |
| Private account | `ui.css`, `reader.css`, `tailwind.css` | profile editor, account runtime |
| Public profile | `ui.css`, `public-profile.css`, `tailwind.css` | public profile runtime |
| Eligible article | `ui.css`, `comments.css`, optional favorite CSS, `tailwind.css` | comments/community and optional favorite runtime |

`ui.css` owns tokens, controls and the account page frame. `reader.css`,
`comments.css`, `public-profile.css` and `article-favorite.css` each own one
composition boundary. Tailwind remains a build-time, zero-runtime refinement;
it cannot reintroduce shadows, oversized radii or a tile around favorite class.
All files retain content-hash versions and the existing scoped optimizer
exclusion. Public/private cache and authorization behavior are unchanged.

## Deployment prerequisites: not applied

The existing release workflows run checks but **do not deploy the BFF**. A
separately reviewed staging setup must provide all of the following:

- Node 22.22.2, locked dependencies, an unprivileged supervised process running
  `node services/reader/server.js`, and a private persistent SQLite directory.
- `READER_ORIGIN`, `READER_ISSUER`, `READER_CLIENT_ID`, `READER_CLIENT_SECRET`,
  `READER_DEPLOYMENT`, absolute `READER_DATABASE`, base64url 32-byte
  `READER_ENCRYPTION_KEY` and `READER_CSRF_KEY`; optional `READER_PORT` defaults
  to 18081. Inject secrets through managed deployment configuration, not source,
  WP options, public HTML or command-line arguments. Backup keys separately.
- Community data is disabled by default. Staging requires
  `READER_COMMENTS_ENABLED=1` and the exact staging tuple. Production additionally
  requires `READER_ALLOW_PRODUCTION_COMMUNITY=1`, the exact
  `https://hs-manacost.ru` origin, `production` deployment and
  `manacost-reader-production` client. WordPress mirrors the boundary with
  `HS_MANACOST_READER_COMMENTS_ENABLED=true` and production-only
  `HS_MANACOST_READER_ALLOW_PRODUCTION_COMMUNITY=true`; its signed editorial
  response is bound to the BFF's exact origin. Neither flag belongs in HTML.
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

Production activation is a separate release gate documented in
`docs/reader-production-readiness.md`. A stored `(site_id, wp_post_id)` is not
permission to reveal unpublished or paid content; every comments and favorites
read retains the authoritative WordPress article/VIP check.
