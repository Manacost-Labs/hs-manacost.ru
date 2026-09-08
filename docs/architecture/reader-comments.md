# Comments using Manacost reader profiles — design, not activation

Status: proposed next stage for test.hs-manacost.ru. This document adds no
comment route, database migration, widget, Cackle integration or WordPress
comment output. Existing disabled-comments policy remains authoritative.

## Ownership

HearthPulse authenticates the reader. Manacost's BFF owns reader profiles and
will own comments. WordPress owns articles and editorial publication state.
Do not create `wp_users` for readers or use a WordPress admin cookie as a
reader identity. Do not use a display name, email or HP subject as a public ID.

```text
HearthPulse session → Manacost BFF session → reader_profile.id
                                              │
WordPress published article → validated thread ├─ author card
                                              ├─ comments and replies
                                              └─ own activity in cabinet
```

## First user flow

1. Under an eligible article, show a compact discussion section in the same
   content width. Guests can read published comments and see “Войти, чтобы
   ответить”; login returns to this article and discussion anchor.
2. A reader sees their current avatar, display name and favorite-class label,
   a plain-text input and “Отправить”. An initial notice explains that name,
   avatar and class will be public. Bio stays in the profile card, not every row.
3. Sending has explicit pending/success/error states. New/untrusted readers'
   messages initially await moderation and are visible only to their author
   and moderators; never claim “Опубликовано” before it is actually public.
4. Replies have at most one visual nesting level, with a quoted author/context
   link. Editing is permitted for 15 minutes by the author, before moderation
   locking; subsequent corrections can be a reply. A moderator retains authority.
5. The cabinet gains “Мои комментарии” with article links and moderation state.
   Existing saved-articles development is separate; no fake counters/history.

Proposed compact visual layout (labels describe future controls):

```text
Обсуждение · 12                        Сначала новые ▾
[аватар] Имя пользователя · Маг
         [ Написать комментарий…                     ]
         Публичный профиль Манакоста       Отправить
─────────────────────────────────────────────────────
[аватар] Имя · Жрец                         10 мин назад
         Текст комментария без декоративной карточки.
         Ответить · Изменить (свой) · Пожаловаться
         └─ Ответ: имя, текст, время
```

Use the cabinet's typography, navy/blue accents and restrained class colour.
No neon frames around comments, large avatar medallions, gamified scores or
colour-only moderation indicators. Desktop/mobile have the same actions;
touch targets ≥44px, visible focus, associated labels, live status and reduced
motion. Article covers remain the strongest artwork on the page.

## Data model (future migrations)

- `comment_threads`: id, site_key, wp_post_id (unique pair), status, created_at.
- `reader_comments`: UUID, thread_id, author_profile_id, parent_id nullable,
  plain body (2–3000 code points), status, version, created_at, updated_at,
  deleted_at. Index `(thread_id, status, created_at, id)` and author pagination.
- `comment_operations`: subject-scoped idempotency key, request digest,
  resulting comment ID, expiry; do not store full duplicate bodies in logs.
- `comment_reports`: reporter_profile_id/comment_id unique pair, reason enum,
  moderation state; bounded private detail if required.
- `comment_moderation_events`: moderator actor, action, reason, timestamp;
  restricted audit access, no public email/IP or authentication data.

Published comments join the current profile by stable UUID. A name/photo/class
change updates authorship display without changing ownership or losing history.
Do not preserve old photos in each comment. Display-name uniqueness is not
promised; impersonation reports and a stable opaque author reference are needed.
Moderation may mask an abusive profile name/photo independently from comment
text. Profile IDs are not authentication credentials.

## Contracts and security

Proposed API (not yet implemented): GET article comments with opaque cursor,
POST comment with Idempotency-Key, PATCH own comment with version, DELETE own
comment, POST report, GET own activity. Limit page size to 20 (max50), sort by
stable `(created_at,id)`, and prohibit arbitrary sort/SQL or public author lists.
All writes use the existing same-origin session + online HP check + CSRF.
Body validation rejects owner IDs, HTML and control characters; render only
escaped text. Do not fetch pasted URLs or provide link previews in the MVP.
Replies must belong to the same visible thread, cannot form cycles or reply to
private moderated content, and obey a bounded depth. Requests aborted/revoked
while pending cannot create comments.

Public comment responses must embed a separate allowlisted author DTO, never
reuse `/me`. The current owner-only avatar route cannot serve other authors:
add a moderated public thumbnail route with opaque versioned references only
after explicit public-profile notice. Do not expose subject/issuer/email or
allow enumeration of private profiles. Erasure/moderation must invalidate those
thumbnail references and their cache, rather than retaining old public photos.

Before creating/reading a public thread, the BFF's read-only editorial adapter
must verify the exact site and immutable WP post ID are published, public,
not password-protected or VIP/paywalled. Do not trust article URLs supplied by
the browser. Article deletion/unpublishing must invalidate public thread reads;
on verification failure hide the thread rather than serve stale private data.
This adapter needs a narrowly authenticated server-to-server contract, not
public exposure of all WP post metadata. Resolve that contract before coding.

Initial moderation defaults: first comments pending, subject/IP abuse limits
with privacy-preserving short-lived counters, duplicate-body cooldown, ≤5 writes
per minute and ≤50/day subject (tune from staging evidence), report deduplication.
No CAPTCHA dependency by default. Moderator permission must be explicitly
granted by a server-side role, never inferred from reader-supplied fields or
an “admin” display name. Moderation UI and permission audit are a separate slice.

Deletion replaces public text with a tombstone while preserving reply structure;
profile erasure anonymizes the author reference and removes name/bio/avatar.
Before release choose and document retention of deleted text/moderation evidence,
backup expiry, ownership-verified export and erasure, and provider-account
deletion signalling. No indefinite retention or silent Cackle import by default.

## Delivery plan and gates

1. Accept this UX/data contract and privacy/moderation decisions. Implement the
   editorial visibility adapter and isolated tests first.
2. Add comment storage/API behind a new server flag default OFF; test IDOR,
   CSRF, revoked sessions, replay/idempotency, concurrency and pagination.
3. Build the same-design widget and cabinet activity with synthetic fixtures;
   verify keyboard, screen reader, long Russian text, mobile, 200% zoom and
   network failures. Do not inject sample comments into actual articles.
4. Add moderation/reporting/export/deletion, test restore/rollback. Complete
   fresh security review and a real staging account flow.
5. Enable only on an explicit disposable staging article. Keep production,
   Cackle and native WP comments disabled. General activation requires a
   separate decision after moderation/visibility/abuse checks pass.
