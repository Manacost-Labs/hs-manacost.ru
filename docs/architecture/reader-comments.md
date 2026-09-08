# Reader comments — isolated staging pilot

Status: implemented behind default-OFF switches. Source, fixtures and a merged
release do not establish live activation. Record deployed SHAs, pilot article IDs
and actual runtime checks in the release task.

## Ownership and public identity

HearthPulse authenticates readers; the Manacost BFF owns local sessions, private
profiles, comments and moderated public profiles. WordPress supplies fresh
editorial visibility and an identity-free shell inside the article container.
No reader wp_users, WordPress auth cookies, native comments, Cackle import or
moderation HTTP endpoint. Production WordPress stays off.

Public IDs are local random profile UUIDs, never email or HearthPulse subjects.
Approved comments show an avatar, name, profile link and, when verified, the title
**«Платный подписчик»**. The public screen at `/account/?reader=<UUID>` includes
approved name, bio, favorite class and avatar, never another reader's editor.

## User flow and moderation

1. Guests read published comments. Login through production HearthPulse returns
   to the same article and `#reader-comments`.
2. A reader enters 2–1000 Unicode code points of plain text and explicitly agrees
   to publish profile fields/title (unchecked by default). Every new message is
   pending, visible only to its author, without a public avatar/profile link.
3. An operator inspects the body/profile and approves exact comment, profile and
   avatar versions. Profile edits cannot silently bypass review. Published authors
   use one moderated snapshot; a later approval updates that public snapshot.
4. Replies target a published root in the same article; deeper replies fail.
   Lists are oldest first, 20 items/page with a cursor.
5. Owners can delete messages and export/erase their community data. Erasure
   keeps their private account and login session.

Operator entrypoint: `services/reader/comments-admin.js`, commands `pending`,
`inspect`, `review`, `takedown`. Requires an existing absolute staging DB and
exact staging origin/issuer/enabled environment. Publishing requires inspected
`--expected-profile-version`, `--expected-avatar-version` (literal `null` when
absent), comment `--version`, `--decision publish` and explicit `--actor`.
Use `--include-avatar` only for deliberate private inspection. Never paste
inspection output into public issues/logs. Reader input cannot grant moderation.

## API contracts

| Boundary | Contract |
| --- | --- |
| GET/POST `/reader-api/v1/threads/{postId}/comments` | GET cursor page; POST exact `{body,parentId,operationId,profileVersion,publicConsent:true}` |
| DELETE `/reader-api/v1/comments/{UUID}` | Owner only, exact `{version}`, compare-and-swap |
| GET `/reader-api/v1/readers/{UUID}` | Allowlisted public profile |
| GET `/reader-api/v1/readers/{UUID}/avatar?v={version}` | Exact current 32-character version; moderated WebP |
| GET `/reader-api/v1/community/export` | Online authenticated owner, 100 rows/page |
| DELETE `/reader-api/v1/community/profile` | Online owner, exact `{profileId,confirm:"erase-community"}` |
| POST WP `/wp-json/manacost-reader/v1/threads` | Private server-only `{ids:[1..20 unique positive integers]}` |
| POST HP `/identity/reader-entitlements` | Confidential staging client, `{subjects:[1..20 unique opaque subjects]}` |

Writes use existing same-origin session, CSRF and online HearthPulse verification.
Wrong owners, unknown fields, stale versions, controls and oversized inputs fail.
Idempotency binds operation UUID to owner and exact input. Ambiguous failures
lock the draft until explicit byte-identical retry. HTTP 401 wipes private state;
409 retains text but requires fresh profile/consent. Request deadlines include
response bodies. Page hide aborts/clears state; restored pages reload identity.
Rendering uses text nodes and exact same-origin avatar/profile paths, not HTML.

Public responses are private/no-store and noindex, with no email, HP subject,
subscription details, tokens or private-avatar URL. The BFF rechecks local
deletion/visibility/session state after upstream awaits. Public profile/avatar
access requires a published comment in a currently allowed article. Editorial
failures hide data; payment-service failures only hide the title.

## Paid title

Only current, confirmed **Boosty OR Patreon** entitlement counts. Manual access,
Telegram, grace, stale/failed checks, blocked or malformed evidence do not.
HearthPulse reads its existing cache (up to 30 minutes, capped by provider expiry),
without provider HTTP calls while rendering comments. Response fields:
`subject`, strict boolean `paid`, `checkedAt`, `validUntil`. The BFF independently
validates freshness and does not persist the title. This is decoration, not
access control. Existing identity/login behavior is unchanged.

## Editorial and nginx boundary

An explicit reviewed article-ID allowlist and feature flag are both required.
The current post must be published, password-free and have an exact staging URL.
Content containing `[` and encoded/unsupported paths fail closed in this pilot.
This is not a general legacy VIP/paywall classifier. Browser URLs are not trusted.

The BFF sends a dedicated staging Basic principal plus HMAC-SHA256 over
`POST + "\\n" + "/manacost-reader/v1/threads" + "\\n" + unixSeconds + "\\n" + rawBody`.
WordPress requires a valid signature within 60 seconds; Origin/cross-site fails.
The principal is not a WP user. The inherited shared staging Basic gate intentionally
accepts any valid staging credential, including the separately provisioned BFF
credential; it is not the endpoint's BFF authorization. Only the separate HMAC
permits the WordPress operation. On the exact route nginx validates that Basic
gate, then clears Authorization, PHP_AUTH_USER and PHP_AUTH_PW
before FastCGI; otherwise WP application-password authentication may reject it
before HMAC permission. HMAC identifies the BFF, not a reader.
Use reviewed `services/reader/editorial-staging.nginx.conf`. Normal WP/BFF
deployment does not apply it. Preserve all other PHP and regional TLS rules.

## Storage, retention and pilot limits

Additive transactional SQLite tables: comments, moderated profile snapshots,
audit, erased-operation hashes and pseudonymous rate events. No existing profile
or session schema is dropped. Identity queries are issuer-scoped.

- Published content remains until owner deletion, erasure or takedown. Removal
  clears body immediately; tombstones preserve reply structure.
- Pending/rejected bodies expire after 30 days; audit entries after 90 days.
- Erased-operation hashes/rate events expire after 24 hours to prevent retry
  resurrection and erase-to-reset abuse.
- New-message limits: 5/minute and 50/day per issuer/subject.
- Erasure is atomic up to 5000 rows; larger accounts fail before mutation and
  need a separately implemented batched/operator path before general rollout.
- Browser export is complete-only, up to 5000 rows. Repeated/malformed cursors
  or service failure never generate a partial download.
- After the last qualifying comment, snapshots are inaccessible publicly but
  remain privately stored until community erasure. Private-profile edits do not
  automatically erase/replace an approved snapshot; the privacy notice must say so.
- Backups can retain old content/avatars. Before accepting real community data,
  record backup expiry, access ownership and deletion replay after restore.
  Do not promise physical deletion from backups or silently delete existing backups.

## Release and rollback

Required before pilot activation:

1. Canonical `make check`, `make code-quality`, contracts, staged secret check,
   full reader browser target, isolated `make visual`, focused tests, independent
   HIGH-risk review and green exact-SHA CI on both repositories.
2. Real ephemeral nginx/FPM Basic-to-PHP proof, then actual `nginx -t` and exact
   staging-route checks. No production WP routing changes.
3. Fresh consistent BFF DB backup, integrity check and isolated restore drill;
   record current/previous immutable artifacts and separately retained rollback DB.
4. Provision server-only keys outside Git/logs. BFF:
   `READER_COMMENTS_ENABLED=1`, `READER_EDITORIAL_KEY`,
   `READER_EDITORIAL_USERNAME`, `READER_EDITORIAL_PASSWORD`.
   WP: `HS_MANACOST_READER_COMMENTS_ENABLED`, `HS_MANACOST_READER_EDITORIAL_KEY`,
   `HS_MANACOST_READER_COMMENT_POSTS`. HP: `READER_ENTITLEMENTS_ENABLED=1`,
   existing browser-identity flag and confidential staging client registration.
5. Enable only a disposable reviewed plain staging article; verify login return,
   pending owner visibility, moderation, avatar/profile, paid/nonpaid fixtures,
   deletion/unpublishing/erasure, mobile and keyboard. Distinguish synthetic
   evidence from a real user's completed login/subscription flow.

Rollback: disable comment/title flags, restore the exact backed-up staging nginx
location, validate/reload, switch BFF to the recorded previous immutable artifact
and restart only its staging service. Additive tables can remain unused. Never
restore an old DB over newer user data just to roll back binaries. HearthPulse
release/rollback follows its own immutable deployment workflow.

Deferred: self-service editing, reactions, reporting/moderator UI, profile
directory, cabinet activity tab, notifications, legacy import, general paywall
mapping, large-account erasure and automatic snapshot-retention cleanup.
Saved articles and the approved private-account redesign remain separate work;
never replace the actual account editor with prototype/demo JavaScript.
