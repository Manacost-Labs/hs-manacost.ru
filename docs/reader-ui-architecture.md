# Reader UI architecture

The Reader interface is an isolated first-party layer on top of Newspaper. It
must stay replaceable without changing the parent theme, WordPress identities,
the Reader API, or cached article HTML.

## Ownership

- PHP shell files render cache-safe structure and public copy only. They never
  embed a Reader session or private profile data.
- `comments.js` owns activation, request lifecycle, DTO validation and the
  public/private visibility boundary.
- `community-ui.js` owns reactions and moderation controls after it receives
  already validated visible rows from the controller.
- `ui.css` owns shared primitives. `reader.css` and `comments.css` own page and
  component layout; Newspaper and generated optimizer CSS are not source.
- The Reader BFF remains the authority for authentication, storage,
  entitlements, bans and comment publication policy.

## Discussion state flow

1. The cache-safe shell loads with no private data and does not call the API
   until it is within 640 CSS pixels of the viewport.
2. The public thread and `/me` start concurrently.
3. A thread response is structurally validated before entering local state.
4. Published and deleted rows may render immediately. A pending row is visible
   only after `/me` validates and its author ID matches the viewer ID.
5. Identity completion re-renders the same validated row set; it does not
   refetch or create a second presentation store.
6. `pagehide`, session expiry and request generations abort stale work before
   it can restore private state.

This separates DTO validity from viewer visibility. Public reading is not held
behind an identity request, while pending data keeps a fail-closed rendering
rule.

## Visual and performance contracts

- Comments use the article's white editorial canvas, one restrained gold
  thread accent and the shared Reader type/spacing tokens.
- The composer has one action toolbar on desktop and ordered full-width actions
  on narrow screens. Loading, empty, error, authenticated and profile-sync
  states retain accessible text and native controls.
- No framework or remote font is added. Static asset budgets and the deferred
  request behavior are enforced in `tests/reader-ui`.
- Browser checks cover 320, 390, 560, 768, 1024 and 1440 CSS pixels plus a 200%
  zoom equivalent, keyboard order, overflow and delayed request ordering.

Rollback is a Git revert of the Reader MU-plugin slice followed by the normal
staging workflow. It does not require a database migration, cache-wide purge,
theme rollback or Reader BFF restart.
