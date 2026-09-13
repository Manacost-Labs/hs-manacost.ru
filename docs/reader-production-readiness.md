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
