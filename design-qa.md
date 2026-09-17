# Design QA — mobile article cover overlay

## Comparison target

- Source visual truth: `/home/debian/.codex/attachments/3c27a702-ebd5-47a6-a199-f3267593b89a/codex-clipboard-44f0f3e1-36bc-4642-85ec-d7c37767b5c6.png`
- Browser implementation at 390 px: `.artifacts/article-clean-header/article-390-cover-overlay.png`
- Browser implementation at 320 px: `.artifacts/article-clean-header/article-320-cover-overlay.png`
- Combined comparison: `.artifacts/design-qa/cover-overlay-reference-vs-built.png`
- Route: `https://hs-manacost.ru/novie-karty-hearthstone-temnaya-imperiya-sentyabr-16/`
- State: anonymous article view with the worktree stylesheet injected after production CSS; analytics and `admin-ajax.php` were blocked to avoid production mutations.

## Normalization

- Source pixels: 495 × 1104. The source is a directional mobile mock rather than an exact CSS viewport capture.
- Normalized source: 390 × 870 pixels.
- Implementation: 390 × 1000 pixels at a 390 × 1000 CSS viewport and `deviceScaleFactor: 1`.
- The images were placed in a single comparison canvas at equal width. Exact vertical alignment is not asserted because the source viewport height and live production content differ.

## Findings

- No actionable P0, P1, or P2 differences remain for the requested article header treatment.
- Fonts and typography: the implementation preserves the site font, uses a 24 px/600 white heading with 1.18 line height, a 15 px italic subtitle, and text shadows that remain readable over detailed artwork.
- Spacing and layout rhythm: the text container grows with content. It is 280 px high at 390 px and 329.8 px at 320 px, with the title and subtitle fully contained at both widths.
- Colors and visual tokens: a controlled three-stop dark gradient increases from 42% near the top to 90% near the text-heavy bottom. White and translucent-white text retain clear hierarchy.
- Image quality and assets: the real WordPress featured image is used with `object-fit: cover`; no generated, duplicated, or substitute image is introduced.
- Copy and content: live title, subtitle, date, and view count are preserved. The reference omits metadata, but retaining the compact metadata row is an intentional content-preservation decision.
- Shortcode note: the direct article-level `su-note` remains exactly viewport-wide at 320 and 390 px, with 20 px inner padding and no horizontal overflow.
- Responsive evidence: no horizontal page overflow at 320, 390, 480, 767, 768, 1024, or 1440 px. The title remains visible at 200% page scale with no horizontal page scroll.
- Primary interactions: mobile menu and search controls remain outside the changed article surface and retain their previously verified keyboard focus and activation states.
- Console evidence: no page JavaScript errors occurred in the 320 or 390 px captures.

## Focused comparison

A separate crop was not needed: the normalized combined comparison renders the complete header text at readable size and also shows the Sirus placement and full-bleed note boundary.

## Comparison history

1. The earlier selected direction removed the mobile cover and used a white editorial header. The user replaced that direction with a cover-overlay reference.
2. The cover was restored as an absolutely positioned real featured image; a three-stop overlay and content-sized title container were added.
3. Post-fix browser evidence confirms the 320 px heading grows the header instead of clipping, while the 390 px version keeps the compact 280 px composition shown in the reference.

## Follow-up polish

- P3: per-article focal-point metadata could improve crops for unusually composed future cover images. It is not required for the current article and would be a separate content/editor feature.

final result: passed
