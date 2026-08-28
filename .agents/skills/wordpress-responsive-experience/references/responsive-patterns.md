# Responsive patterns

Use intrinsic layout first: wrapping flex/grid, `minmax()`, `min()`, `max()`, `clamp()`, `aspect-ratio`, logical margins/padding, and media that cannot exceed its container. Add media queries where content demonstrates a need.

The current Newspaper geometry uses a 1068 px desktop container, approximately 980 px from 1019–1140, 740 px from 768–1018, and 20 px mobile gutters at 767 and below. Preserve these boundaries unless a validated redesign contract deliberately replaces them.

Test 320, 390, 768, 1024, and 1440 px, then sweep intermediate widths. A breakpoint can pass while a nearby width produces a white gutter. Treat `document.documentElement.scrollWidth > clientWidth + 1` as a failure and identify the exact element; do not apply global `overflow-x: hidden` as a repair.

Let cards change column count, allow controls to wrap, make dense tables locally scrollable, wrap long URLs/deck codes, and give images stable aspect ratios. Keep touch targets at least 44 CSS px, visible focus, and the same action names across modes.
