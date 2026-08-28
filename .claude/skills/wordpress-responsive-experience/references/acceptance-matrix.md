# Responsive acceptance matrix

| Area | Required evidence |
| --- | --- |
| Widths | Inspected 320, 390, 768, 1024, 1440 px screenshots and intermediate overflow sweep |
| Parity | Same content, actions, ads, SEO meaning, analytics and views on desktop/mobile |
| Access | Keyboard, visible focus, screen-reader order, touch targets and 200% zoom |
| Robustness | Long Cyrillic, missing/slow images, ads, embeds, loading, empty and error states |
| Geometry | No page-level overflow, clipped controls, horizontal white gutter or accidental overlap |
| Performance | Stable reserved geometry, inspected CLS, cold/warm comparison and bounded mobile payload |
| WordPress | Newspaper ownership is update-safe; cache/mobile variants and authenticated state are checked |
| Domains | `.ru` canonical, `hs-manacost.com` noindex mirror, staging noindex; no double analytics/views |
| Delivery | Visual diffs reviewed, staging smoke passed, exact commit and rollback recorded |
