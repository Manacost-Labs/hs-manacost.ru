# Native loading for Newspaper thumbnails

The public HTML optimizer retains the first fourteen Newspaper thumbnails and
converts later, simple background thumbnails into decorative `loading="lazy"`
images inside the same reserved boxes. Image URLs remain unchanged. Originals,
sidecars, font files, theme CSS files, tracking and access checks are untouched.

Fourteen is a conservative budget measured against the current homepage and
pagination at 1920 × 1080: the first fourteen include every visible thumbnail.
The original two eager LCP pictures retain their existing markup and priority.
Retina/custom classes, another image loader, nested content, multiple backgrounds,
unsupported inline sizing/positioning and unsafe URLs keep the existing markup.
Documents containing templates or lacking a closing head also remain unchanged.

Composer alignment in embedded CSS is shared through a custom property, preserving
selectors and media queries. Inline background alignment retains its higher
priority. The image occupies the same absolutely positioned thumbnail box;
its source is not resized or recompressed. `skip-lazy` and `data-no-lazy` keep
WP Rocket from taking ownership of the new native images.

## Validation on 12 October 2026

Real PHP regression fixtures cover preserved first-screen thumbnails, source URLs,
font/ad/script markup, CSS cascade, protected HTML, loader conflicts, idempotence,
public hosts, private contexts and rollback. WPCS, PHPCompatibilityWP, strict WPCS,
PHPStan level 7 and the PHP structure gate pass for both changed PHP files.
WordPress integration, visual and admin performance gates also pass.

The browser replay uses captured public homepage/page-2 HTML, the actual PHP
transformation and a shared cache of unchanged public assets. Five paired runs
per page at 390 × 844/DPR 3 and 1920 × 1080/DPR 1, plus paired widths
320/530/768/900/1024/1440 at DPR 2, produced 26 pairs with:

- identical loaded font faces, text metrics and thumbnail geometry;
- identical image source URLs and computed crop positions;
- no converted image visible in the initial viewport, duplicated background,
  horizontal page overflow or failed native image after scrolling;
- no meaningful LCP/CLS regression in the controlled replay.

Median requested asset body bytes on mobile fell from 9,086,576 to 3,343,790
(home) and from 9,095,468 to 3,352,682 (page 2). At 1920 px the respective
values were 9,319,850 → 6,683,678 and 9,328,742 → 6,692,570. These are
requested decoded asset body bytes, **not compressed network transfer or field
Core Web Vitals**. Live staging/origin/edge delivery must be measured separately.
Existing shifts at intermediate widths remain a separate diagnosis.

## Release and rollback

Only anonymous public `.ru`, `.com` and exact `test.hs-manacost.ru` requests
are eligible. Admin, authenticated, AJAX, preview, REST and private reader/account
routes retain the existing exclusion policy. The parent optimizer's public-host
allowlist includes staging so the feature can be validated before production.

Deploy through the normal staging pipeline. Verify initial view, fast scroll,
menus/search, keyboard, zoom, source dimensions and computed crop/typography on
staging, then the origin and both regional proxies after an authorized promotion.
Original PNG/JPEG and modern Accept responses must stay readable.

Set `MANACOST_NATIVE_THUMBNAILS_ENABLED` to `false` in operator-owned runtime
configuration and invalidate affected page caches to disable only this feature.
Alternatively roll back its commit through the release workflow. Never edit a
cached HTML file, image original or generated font directory to undo the change.
