# Reader cabinet UI polish — staging

## Scope and design

Continue the existing dark Manacost direction on `/account/` only: navy
`#062638`, panels `#102f40`, readable text `#f4f8fa`, muted text `#bdd1db`,
blue actions `#6db9e8`, restrained gold `#f5cf6b`, borders `#365260`.
Use the existing Roboto/Arial stack, rem-based typography, 4/8px radii,
4–48px spacing, no shadows, new fonts, imagery, or animation dependencies.
The bright editorial covers and fantasy site header remain the site's identity.

Baseline inspection on 2026-09-08 found the account shell squeezed into a
696px desktop article column beside an editorial sidebar. Full-page guest
captures were 3081px tall at 1440px and 3668px at 390px, mostly unrelated
sidebar content. The account now uses an explicitly scoped page template with
the normal theme header/footer and `the_content()` pipeline. No page-template
database assignment, vendor theme edits, or CSS hiding of editorial blocks.

One H1, two H2 sections, a desktop two-column workspace and stacked mobile
layout. Keep native links/buttons, visible focus, 44px targets, live status,
safe long-name wrapping and reduced-motion support. Saved articles are clearly
labelled as in development; no pretend data or enabled save actions.

## Boundaries

- Template override requires the existing opt-in flag and the published,
  explicitly provisioned `/account/` page containing the reader shortcode.
- Existing article/homepage templates and ads, comments policy, WordPress
  identities, BFF, HearthPulse, sessions and authorization rules are unchanged.
- `reader.js` and API attributes retain the existing authentication contract.
- Public shell HTML contains no personal data; no-cache/noindex policy remains.
- This branch is a staging UI release, not authorization for production promotion.

## Verification contract

Behavioral PHP tests cover opt-in/provisioned template selection, other-page
passthrough and preservation of the content/header/footer pipeline. Browser
tests use the real shell, theme stylesheet and auth JS with synthetic API
responses: guest/authenticated/error states, 320/390/560/768/1024/1440 widths,
long Cyrillic/Latin names, enlarged text, focus, logout retry, request deadlines
and clearing private state. Authenticated fixtures are not real-account E2E.

Run `make check`, `make code-quality`, `make contracts`,
`READER_TEST_CHROMIUM=/usr/bin/chromium make reader-browser-test`, the Newspaper
change audit and the repository's disposable `make visual` integration suite.
Record actual results in the PR; do not treat this checklist as completed tests.
Fresh-context Sol review is required for this HIGH-risk plugin boundary.

Before staging validation, use an isolated browser with a temporary, authorized
staging BasicAuth principal and remove that principal afterward. Inspect guest
desktop/mobile captures and the real link to production HearthPulse; do not
claim a real user's complete login without that evidence.

## Release and rollback

Use the normal PR → Quality → main → Deploy staging pipeline. The reader BFF
release script does not ship WordPress UI. Revert only this branch's reader UI
files through the same pipeline to restore the old shell/template behavior.
No database or identity migration is involved. Keep existing authentication
and comments policy intact during rollback.

## Engineering method

Profile: WordPress; selected methods: scoped MU-plugin implementation,
responsive typography/accessibility, test-first regression and staging release
review. The project-mandated baseline and UI specialist skill chain exceeds
the normal skill budget; only applicable files/references were loaded, with no
broad skill-catalog or vendor-theme changes.
