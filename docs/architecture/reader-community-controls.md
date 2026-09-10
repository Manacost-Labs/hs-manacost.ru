# Reader community controls

## Objective and boundaries

Improve Twitch/YouTube identity marks; add persistent reactions and Reader-only
administrator moderation on `test.hs-manacost.ru`. HearthPulse's current persisted
`users.role = 'admin'` with no `blocked_at` is authoritative. WordPress users,
roles, native comments and production Manacost remain out of scope.

The separate reviewed 30-day session changes (Reader PR65, HearthPulse PR37)
remain their own release slice. Do not replace their fixed deadline, encrypted
refresh rotation or parent-session revocation. Their release gate is resolved
before enabling a persistent Reader cookie.

## Contract and architecture

1. HearthPulse adds a server-only POST `/identity/reader-permissions`, enabled
   with the existing explicit Reader bridge. Authenticate the exact staging
   static client using its server-held secret; reject browser Origin/cross-site
   requests, unbounded bodies and invalid/duplicate subjects. Request:
   `{ subjects: string[] }`, 1–20. Response in requested order:
   `{ permissions: [{ subject, canModerateComments: boolean }] }`.
   Read canonical role/block state for every request; no subscription, stale
   cache, browser-supplied role, WordPress role or long-lived token role grants
   authority. Missing/blocked users return false; query errors return 503.
2. Reader verifies its existing upstream session and CSRF/Origin for mutations,
   then fetches current permission before any admin operation. Provider failure
   denies moderation without breaking public reading. Public administrator
   badges use the same fresh batch check for already-public authors and never
   expose upstream subjects. Current-session UI permissions are not authority.
   Capture session ID, subject and upstream-token tuple; re-read and match the
   exact tuple after every awaited permission/editorial check immediately before
   the synchronous mutation transaction. Logout, rotation/replacement or expiry
   denies the in-flight mutation. Refresh and verification precede capture.
3. Reactions are `like`, `thanks`, `fire`, one per Reader/comment; explicit PUT
   of a reaction or null is idempotent. Public counts and the signed-in reader's
   selection are returned without exposing voter identities. Only published,
   editorially eligible comments accept reactions. Authentication, CSRF, bounded
   input and durable abuse limits apply. Erasure removes owned reactions;
   takedown/owner deletion removes reactions on the removed comment.
   Limits: 30 changed reactions/minute, 200/day per Reader, 1,000 active reactions
   per Reader and 5,000 per comment. Same desired state is a no-op; no duplicate
   count or rate charge. All keys, counts, joins, export and delete predicates
   include issuer; deployment is isolated by the staging-only community guard.
   Bound combined erasure work to 5,000 reaction rows, otherwise return the
   existing `erasure_needs_operator` path before making any changes.
4. Admin deletion preserves the existing tombstone/version and audit contract.
   A Reader-local commenting ban prevents new comments/replies, not reading or
   private-profile editing. Ban/unban uses version checks and server-derived
   actor identifiers; there is no client-supplied role/actor. Reversible bans
   persist until unblocked; a bounded admin-only ban list keeps unblocking
   possible after the offending comment was deleted.
   The stable key is `(issuer, SHA256(origin + NUL + issuer + NUL + subject))`;
   the hash is a pseudonymous security identifier, not anonymous information.
   A random ban UUID is the only ban identifier returned to the administrator.
   Do not delete the key during community/private-profile erasure. Keep no
   duplicated name/avatar in ban records; a missing profile gets a generic label.
   Check the ban inside the same write transaction as comment insertion. Unban
   retains version state but clears enforcement, with a 90-day audit window.
   Admin list uses a UUID cursor, fixed maximum page size 20 and issuer filtering.
   Admin deletion handles published/pending/rejected comments and replies:
   published becomes deleted; pending/rejected becomes rejected; body is erased,
   reactions removed, version advanced and actor audit written atomically.
   Already body-erased terminal states are idempotent for the current or directly
   previous version; other version mismatches are conflicts. Descendant replies
   remain independently readable beneath a tombstone, not recursively deleted.
5. Forms stay white/transparent. Existing profile/private/public consent stays
   explicit and versioned. Platform logos are recognizable, aligned and have
   accessible names, focus and touch targets; they are not verification marks.
   Administrator badge is distinct from the paid crown.

## Implementation slices and verification

- Role bridge: owning HearthPulse module + composition, API/abuse/revocation
  tests, identity spec and changelog. No new global authentication behavior.
- Reader persistence and HTTP: additive SQLite tables, transitions, reactions,
  bans, owner/foreign-user/admin tests and erasure checks.
- UI integration: current identity marks, reaction controls, moderation menu and
  ban list; pending/error/expired/forbidden/conflict states keep user drafts.
- Delivery: fresh independent HIGH review, green PRs, provider first when
  required, then exact Reader/WordPress staging versions with rollback evidence.

Commands: `make check`, `make code-quality`, `make contracts`,
`make reader-browser-test`, `make visual`, and staged secret scan in the Reader
repository; `npm run verify:release`, security scans and targeted bridge tests
in HearthPulse. Browser checks include 320/390/768/1024/1440 and intermediate
widths, zoom, keyboard, contrast, no overflow, actual API and static-asset delivery.
Use only disposable test actors/content for mutation tests.

## Data and failure policy

Reactions store Reader/profile/comment IDs, chosen enum and update time for the
feature lifetime, until withdrawal, erasure or comment removal. Counts are public;
voter identifiers are not. Bans store the minimal Reader identifier, flag, version
and update time for abuse prevention until unblocked. Moderation audit retains
actor/target/action/time for the existing 90-day window; no bodies, IPs or tokens.
Backups follow the existing Reader expiry policy; code rollback must not restore
an older authentication database or erase moderation records.

The permission bridge uses exact HTTPS, a two-second outbound budget, no redirects,
no automatic unsafe retries and a bounded JSON response. Missing configuration or
unavailable upstream fails closed for admin actions and hides unverified badges.

## Documentation and scope evidence

Profiles: WordPress and HearthPulse. Skills: project context, privacy/integration,
security/API, TDD, bounded UI/accessibility and release checks. Project-required
specializations exceed the generic skill-count budget because this task spans two
repositories, session security, personal-data mutation and browser controls; no
unrelated catalog, theme, vendor or optimizer changes are included.

Tracking: https://app.notion.com/p/3d7047e8a87d81ce8ac9d2bc00bde545
Miro is unavailable in the active tool catalog; existing user screenshots and
components are sufficient for this small visual refinement, not a new redesign.
