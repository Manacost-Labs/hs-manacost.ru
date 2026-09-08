# Editable Manacost reader profile

Scope: test.hs-manacost.ru only. Production HearthPulse remains the OIDC issuer;
its user profile, WordPress users, Cackle and the disabled comments policy are
unchanged. This extends the [reader boundary](architecture/manacost-reader.md).
Code delivery and staging activation are recorded separately in task MC148.

## User experience

The existing navy cabinet stays inside Newspaper's `td-container-wrap`, with
the same outer container as the header/footer and the existing inner measure.
An authenticated reader gets a profile preview and a labelled editing form:

- Display name: 2–40 Unicode code points, trimmed; this can be a pseudonym.
- About me: optional, at most 280 code points; plain text, line breaks allowed.
- Favorite class: optional, one of the eleven supported Hearthstone classes.
- Photo: optional JPEG, PNG or WebP up to 4 MiB; replace or remove it.

Text is explicitly saved. A photo change is saved separately and updates the
shared revision without overwriting a dirty text draft. Focus/file selection
must not reset a draft. A conflicting save shows an explicit reload action;
it never silently overwrites another tab's version. Logout/pagehide clear
private DOM state and abort requests; no localStorage/analytics capture of drafts.
The current five-minute session limit remains unchanged: a fresh login can be
required, and sensitive drafts are not carried between different accounts.

## API contract

All routes are same-origin, private/no-store, no CORS, noindex and nosniff.
Authenticated calls verify the local session and the authoritative HP parent
session. Writes additionally require exact Origin and session-bound
`X-Reader-CSRF`; a request cannot choose an owner ID.

| Route | Request | Successful response |
| --- | --- | --- |
| GET /reader-api/v1/me | Session cookie | Existing fields plus `profile` |
| PATCH /reader-api/v1/profile | JSON: version, displayName, bio, favoriteClass | `{profile}` |
| PUT /reader-api/v1/profile/avatar | Raw image, Content-Type, X-Reader-Profile-Version | `{profile}` |
| DELETE /reader-api/v1/profile/avatar | X-Reader-Profile-Version | `{profile}` |
| GET /reader-api/v1/profile/avatar?v=version | Owner session | Normalized image/webp |

`profile` contains only `id` (random UUID), `displayName`, `bio`, `favoriteClass`
(slug/null), `version` (positive integer), `avatarUrl` (local path/null).
It never contains the HP subject/issuer, email, access token or session ID.
Existing `user.displayName`, `csrfToken`, `profileUrl` remain compatible.

Errors: 400 invalid_profile/invalid_avatar, 401 not_authenticated, 403 invalid
write boundary, 409 profile_conflict, 413 too_large, 429 upload rate limit,
503 identity_unavailable/avatar_busy. Do not automatically retry writes.
After an uncertain response, read current state before a user-confirmed retry.
No public avatar/author API is enabled in this phase.

## Persistence and privacy

`reader_profiles` is an additive table in the staging BFF's private SQLite DB,
not in WordPress. `(issuer, subject)` is unique; a random independent profile ID
is the future comment author key. Re-login/HP renaming do not reset local data.
Updates and avatar replacement use an atomic version compare-and-swap.
The current schema stores only one normalized image per profile as a BLOB;
there are no upload filenames, external image URL fetches, WP attachments or
S3 objects. Originals and EXIF/ICC/location metadata are not retained.

Sharp 0.35.4 is locked. Input is verified against decoded MIME, bounded to
4 MiB/16 MP and rejected if animated, SVG, GIF, corrupt or mismatched. Output
is centre-cropped and auto-oriented to 256×256 WebP, at most 128 KiB. At most
one native decoder runs at a time, with a native three-second processing
timeout and no waiting queue. Uploads are limited to ten per subject/minute.
The native adapter caps two in-flight image bodies and 4 KiB other bodies,
with a six-second request deadline. Origin Nginx adds a 4 MiB route cap and
ten-second client timeout; regional staging proxies already stream bodies.

Profile fields are private in this stage. Before public comments, explain which
fields become public and supply the moderation/export/erasure lifecycle in
the [comments design](architecture/reader-comments.md). For this pilot, removal
of a photo and clearing optional fields are available in the cabinet; complete
profile erasure/export requests require an ownership-verified operator flow.
Do not present this pilot as a complete self-service account deletion feature.
Retain pilot profiles until user deletion or pilot closure; delete abandoned
pilot data before any production migration. Restrict backup access and apply
the pilot's 30-day backup retention; do not copy staging profiles to production.

## Migration and release contract

Migration: one `CREATE TABLE IF NOT EXISTS reader_profiles`, no backfill, no
changes to login_attempts/reader_sessions/reader_revocations. Initial expected
profile count is zero. Repeat initialization must preserve all profile rows.
No WordPress tables, media, keys or provider users are touched. Before first
activation, take an online SQLite backup and validate a separate restored copy
with integrity check and the new initializer; compare counts without printing
rows. Never use the live DB as a restore-test target.

The WP pipeline installs only the MU-plugin/UI on staging after reviewed PR
checks and merge. It does not install the BFF. Use the separately reviewed
`ops/reader/release-staging.sh` with the exact clean merged SHA to prepare the
immutable staging BFF, then restart only `manacost-reader-staging.service`.
Fetch `origin/main` explicitly first: the helper requires HEAD and origin/main
to match the supplied SHA, without mutating refs during its dry run. Parent
directories/artifact ownership are root-controlled; the active runtime user
never receives write access while dependencies are being installed.
The staging runtime uses an explicit production-identity bridge only for the
exact staging host/client tuple. Preserve that existing opt-in and its environment
flag; do not infer the active BFF revision from the WordPress deployment SHA.
Compare the candidate against the actual current BFF artifact before replacing
it. The entrypoint regression starts through a real release symlink with both
isolated test identity and the exact production bridge, using synthetic keys and
a temporary database; neither test sends requests to an identity provider.
Apply the reader location from `ops/reader/origin-staging.conf` to the existing
origin vhost only after backup, exact diff review and `nginx -t`. It preserves
BasicAuth and auth headers; no production WP or HearthPulse proxy changes.

Rollback: restore the previous BFF release symlink and restart its service;
the old binary ignores the additive profile table. Preserve the DB, keys and
new session state. Roll back UI through the staging release's recorded backup
if needed. Restore the prior origin vhost and syntax-check/reload only if the
proxy slice fails. Do not roll back the database for an ordinary binary issue.

### Regional reader TLS boundary

The staging reader must not use the ordinary `hs_manacost_origin` keepalive
pool: ordinary pages disable upstream certificate verification while reader
routes require it. A reused connection is not a fresh verified TLS handshake.
Use the separate `hs_manacost_reader_origin` upstream without keepalive, with
`Connection close` and `proxy_ssl_session_reuse off`. Keep the staging SNI,
certificate verification/system CA bundle, BasicAuth forwarding and no-cache
boundary. Isolation alone does not fix the separately reproduced chain error.

Explicitly set `proxy_ssl_verify_depth 4`. The current staging certificate
chain has two untrusted intermediates (YE2 and Root YE) before the trusted ISRG
anchor; Nginx's default depth of one rejects this otherwise valid chain. A
separate loopback-only Nginx probe with no credentials reproduced five 502s
and `unable to get local issuer certificate`; adding depth four, with all
other settings unchanged, produced five expected 401s. Independent explicit
CA-only OpenSSL checks on both edges also reject depth one and accept four.
Four is a bounded allowance for chain length, not disabled verification or
trust of a leaf certificate. Keep SNI/hostname and root-CA checks enabled.
This proves the reproduced reader-edge failure, not every historical 502.

On each RU edge, install `ops/reader/proxy-staging-upstream.conf` as
`/etc/nginx/conf.d/manacost-reader-staging-upstream.conf` (HTTP context), and
`ops/reader/proxy-staging-reader.conf` as
`/etc/nginx/snippets/manacost-reader-staging.conf` (already included by the
active staging vhost). Resolve active paths with `nginx -T`; on Novosibirsk,
`sites-available` is a stale shadow, not the active staging configuration.
Never edit the production/shared upstream or other vhost settings in this slice.

Before mutation, verify exact current/candidate hashes and root-controlled
paths, seal candidates and save the original snippet in a private backup.
Fail if the new upstream name/path already exists. Install both candidates,
verify installed hashes, then `nginx -t` and reload; on any failure restore
the old snippet and move the newly owned upstream out of the include directory
before syntax-check/reload. Canary Novosibirsk before Moscow. Verify fresh TLS
requests for unauthenticated `/me` and avatar (401), oversized upload (413),
and guest login reaching the production HearthPulse form. Preserve credential
and auth-URL privacy. Remove temporary staging QA credentials after verification.

## Acceptance

Run `make check`, `make code-quality`, contracts, dependency/secret scans and
`make reader-browser-test`, then hosted WordPress integration/visual gates.
Backend tests cover persistent ownership, CAS, bad inputs, upstream failures,
CSRF, logout races, real image bytes and native HTTP body forwarding.
Browser checks cover six widths, 200% zoom, Cyrillic, drafts/focus, upload,
save/error/conflict/logout states and account-only assets. Validate the exact
staging UI files and guest→production-HP login independently. Synthetic users
and isolated test stores prove integration, not a real user's production login;
never copy real credentials/sessions into fixtures or fabricate provider users.

Profile: wordpress; skills: mandatory baseline plus API/security/privacy,
existing-design UI/typography/responsive/accessibility and release contracts.
The expanded skill count is required by project policy, not a redesign scope.
