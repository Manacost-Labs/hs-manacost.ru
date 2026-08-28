# Editor, AJAX and REST performance

## Article editor

Measure login-to-editor, editor-ready, autosave, media insertion, preview, draft save and publish independently. Preserve Classic Editor/Gutenberg behavior, revisions, shortcodes, Newspaper/tagDiv fields and stored `post_content`.

- Enqueue editor assets only where required and avoid duplicate libraries.
- Defer nonessential panels without delaying the title/body editing path.
- Bound metabox queries and avoid repeating the same post/meta/term lookup.
- Do not delay or disable autosave to make interaction traces look better.
- Verify navigation warnings and unsaved-state indicators after any asynchronous change.

## AJAX and REST

- Measure p50/p95 and error rate for the actual action, not only endpoint TTFB.
- Require capability and nonce/REST permission checks before optimization.
- Debounce search, cancel superseded reads, coalesce duplicate requests and paginate responses.
- Return only visible fields; avoid full rendered records when an ID and label are enough.
- Use optimistic UI only for reversible actions with automatic rollback and an actionable error.
- Add timeouts, backoff and a bounded retry policy. Never retry non-idempotent writes blindly.

## List tables and media library

- Keep filter/sort/page state in the URL and query only the visible page.
- Avoid expensive counts on every interaction when a safely cached or approximate secondary count is acceptable.
- Load thumbnails at appropriate dimensions and preserve S3/optimizer URL behavior.
- Test 100, 1,000 and 10,000-record fixtures when the screen's expected growth reaches those sizes.

## Perceived performance

Show immediate progress for operations over 300 ms, but never conceal a slow server behind an endless spinner. Preserve keyboard focus, accessible status announcements, entered values and retry actions during partial failures.
