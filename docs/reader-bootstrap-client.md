# Shared account bootstrap lifecycle

The existing same-origin `GET /reader-api/v1/bootstrap` DTO is unchanged. On an
account page the shared script starts the request before `reader.js` initializes;
the account consumes the same promise. Article favorites and comments continue
sharing their existing post-scoped request. `/v1/me` remains available, including
the standalone account fallback for cached pages without the shared helper.
Roles and subscription decoration remain optional, separate reads after the
profile is usable. This patch does not change server identity verification.

On the private account route only, `wp_head` priority 2 starts the same small
bootstrap client inline, before styles and parser-blocking theme scripts. This
contains static source code only, never server-rendered profile/session data.
The deferred copy recognizes the initialized client and retains its existing
promise and cancellation handlers. Route-scoped Rocket/Perfmatters exclusions
prevent moving or delaying this one starter. The account controller and editor
remain deferred but move into the head, before unrelated footer scripts.
Article and public-profile loading order is unchanged. Browser regressions delay
the parser, editor and footer separately and require exactly one initial identity
request, no `/me` waterfall and account initialization ahead of the slow footer.

## Data and failure contract

Profile fields and CSRF token live only in the shared promise and the existing
private DOM/editor state on the same origin. No persistent browser storage,
analytics payload, new cookie or third-party recipient is added. Existing
30-day session, consent, export/deletion and server retention rules are unchanged.
Requests use same-origin credentials and no-store; server responses retain
private/no-store. The `.com` boundary is unchanged.

An HTTP 401 is a valid anonymous result, reused until explicit refresh so a fast
guest response does not create a duplicate initial request. Network, parse,
oversize, 429 and 5xx failures clear the memoized request; no automatic write or
retry loop is introduced. Explicit refresh replaces the current request.
The five-second shared deadline uses `TimeoutError`, distinct from `AbortError`
for supersession, logout, profile mutation or pagehide. Aborted transports which
nevertheless return data are rejected before delivering their old identity.
Failures and explicit invalidation clear only the matching entry, never a newer
request. The account retains its generation guard and preserves a dirty draft
on temporary failure.

`tests/reader-ui/bootstrap-recovery.mjs` tests the shipped script in a controlled
VM. `tests/reader-ui/browser.mjs` loads the real shared helper, asserts that the
request begins before the account controller and that initial navigation sends
exactly one identity request, and retains editing, conflicts, expiry, account
switches, responsive/keyboard, native socket deadlines and standalone fallback
checks. Transport timing improvement is measured separately from these
deterministic lifecycle guarantees; a URL substitution alone is not a speed claim.
