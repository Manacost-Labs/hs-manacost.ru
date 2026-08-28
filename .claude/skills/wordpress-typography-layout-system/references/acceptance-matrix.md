# Typography and layout acceptance matrix

| Area | Required evidence |
| --- | --- |
| Roles | Every visible style maps to a semantic role; no unexplained one-off values |
| Fonts | License/source known, Cyrillic coverage proven, fallbacks intentional, `font-display` set |
| Runtime | Final computed styles checked anonymously and authenticated; `manacost-font-trim` reviewed |
| Grid | 1068/980/740/mobile geometry and article measure validated or deliberately superseded by contract |
| Rhythm | Approved spacing scale, alignment and visual rhythm hold for dense/sparse/error states |
| Responsive | 320, 390, 768, 1024, 1440 px and 200% zoom pass without clipping or white gutters |
| Performance | Font bytes/requests bounded, preload justified, CLS measured, media/ad space reserved |
| WordPress | Update-safe Newspaper owner, `.ru` canonical, `.com` noindex mirror and staging noindex preserved |
| Delivery | Visual diffs inspected, staging smoke passed, exact commit and rollback recorded |
