# Design QA — mobile article header, option 3

## Comparison target

- Source visual truth: `/home/debian/.codex/generated_images/01a0a50f-22a9-7973-a6c8-faa7f0f8b4a8/exec-65dc3909-8c38-4119-a61b-ffd06ce5d63c.png`
- Browser implementation: `.artifacts/article-clean-header/article-390-option-3-built.png`
- Additional narrow viewport: `.artifacts/article-clean-header/article-320-option-3-built.png`
- Full-bleed note at 320 px: `.artifacts/article-clean-header/article-320-option-3-full-bleed-note.png`
- Full-bleed note at 390 px: `.artifacts/article-clean-header/article-390-option-3-full-bleed-note.png`
- Full comparison: `.artifacts/design-qa/option-3-reference-vs-implementation-final.png`
- Focused header comparison: `.artifacts/design-qa/option-3-header-focus-final.png`
- Route: `https://hs-manacost.ru/novie-karty-hearthstone-temnaya-imperiya-sentyabr-16/`
- State: anonymous article view with the worktree stylesheet injected after the production CSS; analytics and `admin-ajax.php` were blocked to avoid production mutations.

## Normalization

- Source pixels: 783 × 2009 at approximately 2× mobile density.
- Normalized source: 392 × 1005 pixels.
- Implementation pixels: 390 × 1000 at `deviceScaleFactor: 1` and a 390 × 1000 CSS viewport.
- Focused comparison: both header regions cropped to 390 × 460 pixels and placed in one comparison image.

## Findings

- No actionable P0, P1, or P2 differences remain in the mobile article header.
- Fonts and typography: the implementation uses the existing site font, 24 px/600 centered title, 15 px italic subtitle, matching four-line and three-line wraps, and readable metadata hierarchy.
- Spacing and layout rhythm: category, title, 40 × 3 px divider, subtitle, and compact metadata row follow the source composition. The cover is absent only below 768 px; desktop remains unchanged.
- Shortcode note layout: the direct article-level `su-note` measures exactly 320/390 px at the corresponding viewport, starts at x=0, ends at the viewport edge, retains 20 px inner padding, and creates no horizontal overflow.
- Colors and tokens: white background, dark editorial text, muted subtitle/meta text, and the existing Newspaper theme blue for the divider match the selected direction.
- Image quality and assets: the existing Manacost logo and Playerok/Sirus assets are retained; no generated or substitute assets were introduced. Lower article media is outside this header-only comparison target.
- Copy and content: live article copy is preserved verbatim. The view count differs from the generated reference because it is live production data.
- Responsive evidence: no horizontal page overflow at 320, 390, 480, 768, 1024, or 1440 px. The title remains visible at 200% page scale with no horizontal page scroll.
- Primary interactions: mobile menu and search controls remain keyboard-focusable; activation still reaches the Newspaper menu/search states.
- Console/network note: existing production requests returned several HTTP 502 resource errors during capture. No change in this stylesheet creates network requests or script errors.

## Comparison history

1. P2: the initial implementation used a fluid title up to 26 px and inherited widely separated metadata. Fix: set the mobile title to 24 px and make the metadata row a centered flex row with a 20 px gap. Post-fix evidence: `.artifacts/design-qa/option-3-header-focus-final.png`.
2. P2: the initial 350 px text measure produced different title and subtitle wraps from the selected visual. Fix: constrain both title and subtitle to 300 px. Post-fix evidence: the focused comparison shows matching four-line title and three-line subtitle composition.
3. The user requested the beige Shortcodes Ultimate note to span the phone width. Fix: give only direct article-level notes a full-viewport measure below 768 px, remove edge borders/radii, and retain 20 px content padding. Post-fix evidence: the 320 px and 390 px full-bleed screenshots listed above.

## Residual gaps

- The source is an image-generated direction rather than a pixel-authoritative Figma specification, so small antialiasing and live-data differences are expected.
- The visual target defines only the article header; existing article-body asset delivery was not changed or accepted as part of this QA.

final result: passed
