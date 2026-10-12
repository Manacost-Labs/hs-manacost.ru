# Preserve sidebar keyboard reveal across pending scroll events

The compositor keeps a fixed sidebar at its viewport edge while the document
scrolls. Before the queued JavaScript scroll callback runs, its stored document
position can therefore lag the actual DOM. Keyboard reveal previously added the
focus adjustment to that stale position. A later notification could clamp the
focused link back behind the viewport or fixed navigation.

Calculate the focus adjustment from the actual sidebar rectangle plus scrollY,
and retain that revealed position while the same scroll coordinate and sidebar
focus remain. A genuine subsequent scroll, disabled breakpoint or lost focus
returns to normal following; changed document boundaries are still clamped.
No layout read is added to the normal scroll path. CSS, fonts, images, advertising,
Newspaper offsets and breakpoints are unchanged.

The existing browser case now navigates with real Tab. A new regression keeps
keyboard focus unobscured by a fixed menu after a delayed scroll notification,
then verifies that subsequent scrolling still releases it at the footer. The
previous controller failed four of five repeated long-sidebar cases; the fix
passes all 50 cases (ten scenarios, five repetitions) in the pinned Playwright
container with one CPU. This covers fractional widths, reversal, tall/short and
Composer panels, admin offset, dynamic growth, 200% zoom, mobile document flow,
visible focus and delayed scroll handling. Assertions and visual references are
retained. Full integration and staging remain required before promotion.

Rollback this commit through the normal deployment workflow and invalidate only
the changed sidebar script. No data, sessions or media require restoration.
