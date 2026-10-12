# Reserve the pending account controls

The account initially displays a short loading status and an empty action slot.
After the guest bootstrap returns 401, a two-line status and the 96 px login
panel appear, moving both account and theme footers. The change reserves the
existing action-panel height while the status is non-empty and two natural
line heights for status text. The pending panel is an empty visual placeholder. Longer messages can still grow.
An authenticated empty status releases the empty action slot as before.

This CSS-only change reserves the loading state and preserves the final mobile
guest geometry, fonts, text, links and controls. Desktop status text also reserves
two lines, so a one-line message has additional whitespace. PHP, JavaScript, authorization, private/no-store,
CSRF, sessions, API responses and profile/favorite data are unchanged.

Five paired browser replays at 390 × 844 with a controlled 3-second anonymous
bootstrap response measured CLS 0.099310 before and 0 after. All five retain the
same final footer coordinate (460.765625 px), status height (49.59375 px), action
height (96 px), loaded font faces and login target. This isolates the bootstrap
shift; the earlier live slow-network audit also included a separate font/theme
footer shift and measured total CLS ~0.135. Live staging verification remains
required; controlled replay does not establish field CWV or INP.

The existing reader-browser suite passes shared/standalone bootstrap, guest/auth,
responsive states, Unicode, CSRF refresh, avatar writes, account switches,
request/body/logout deadlines, mutation reconciliation and private-state races.
MCP OAuth mobile widths, 200% CSS zoom, keyboard approve/deny and denied role also
pass, along with comments/community flows. No visual reference was replaced.

Before promotion, verify five slow-mobile guest loads on staging, delayed401,
timeout/retry, authenticated transitions, widths 320/390/768/1024/1440 and 200%
zoom. Every guest sample must have CLS <=0.1; target <=0.05, without overflow,
clipped messages or inaccessible controls. Keep the authenticated action slot
empty and unreserved after the status clears.

Rollback the CSS commit through the normal workflow and invalidate only affected
page/asset caches. Existing user data and sessions require no rollback.
