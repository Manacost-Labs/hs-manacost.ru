# Reader comment avatars and composer — staging candidate

## Scope and diagnosis

- Base: `3880540beea04ea02907b27652552b75409d155c` (current `origin/main`).
- Only the staging Reader comment shell, client assets and regression tests change.
  No BFF, database, authentication TTL, dependency, theme or production changes.
- A bounded staging read found the affected author's private profile at version 9
  with an avatar and Twitch, but the previously consented public snapshot at version
  1 without either. No private values, image bytes or credentials were recorded.
- Guest pilot HTML also referenced a deleted WP Rocket `cache/min/1/.../comments.js`
  file (404), leaving the loading status visible. The deployment helper clears the
  minify cache but only explicitly purges the account page HTML.

## Implementation

- Preview the signed-in author's own name, avatar and platform marks in the composer.
- When a loaded old comment differs, offer **Обновить профиль в комментариях**.
  Require explicit unchecked publication consent and the current exact profile
  version; use the existing CSRF-protected community profile PUT. Refetch the public
  list after success. Do not create a comment or optimistically expose private data.
- Preserve drafts on refresh/error; clear private identity on 401/pagehide; require
  fresh consent after a version conflict. Accept only the existing same-origin,
  versioned private avatar route in the private preview.
- Keep the white/transparent canvas, use lighter dividers, consistent spacing,
  a separate composer heading/profile link, live character count and aligned actions.
  Existing public profile links, badges, immediate publishing and data tools remain.
- Exclude only the Reader asset directory from WP Rocket minify, delayed JS and
  external RUCSS processing, behind the existing explicit staging/comments guard.
  Bump community bundles to `0.7.8`; shared UI/account files remain unchanged.

## Verification

- `make check`: pass (151 Python, 87 Reader Node, 23 Reader UI tests; PHP/shell and
  repository contract/skill checks).
- `make code-quality`: pass (WPCS, PHPCompatibilityWP, PHPStan for owned PHP).
- `make contracts`: pass, no inventory drift. Change impact: high, known owners.
- `make reader-browser-test`: pass; account regressions, optimizer boundaries,
  comment layout/security and 9 focused comment flows. Refresh covers success,
  conflict, server error, expiry, draft retention and malformed avatar rejection.
- Browser geometry: 320/390/560/768/1024/1440, zoom/reflow, keyboard controls,
  contrast/touch targets and actual image load; 390/1440 captures manually reviewed.
- `make visual`: integration assertions and 21 visual tests pass; one expected
  shared-matrix skip. First run captured a login page instead of mobile admin and
  failed; an unchanged rerun passed. No snapshots/tolerances were modified.
- Root and Reader `npm audit --audit-level=high`: zero vulnerabilities.
- Final staged secret scan, fresh independent review and GitHub checks are release
  gates; record their actual completion in the PR rather than assuming a pass.

## Release and rollback

Merge through a green PR and allow the normal WordPress staging workflow. Then
invalidate only `https://test.hs-manacost.ru/comments-pilot-20260908/` HTML with
`rocket_clean_files`, and verify cold/warm direct `0.7.8` assets, public comments,
noindex and mobile rendering. This does not need a BFF restart or reader-data write.

The affected user must explicitly confirm **Обновить профиль в комментариях** once;
anonymous viewing must not publish their private profile. Guest live checks do not
prove a real user's consented mutation; controlled browser flows cover that boundary.

Rollback: revert this change through a reviewed staging PR and repeat targeted
pilot HTML invalidation and smoke checks. Preserve the current BFF release and
SQLite database; do not rewrite or remove public snapshots as a code rollback.

Profile/skills: WordPress; privacy, bounded Reader UI/accessibility, runtime cache
ownership and staging release. The project-mandated specialization/baseline chain
exceeds the general skill budget because this crosses UI, public identity and cache
delivery; no unrelated design system or optimizer settings are changed.

## Post-deployment optimizer correction

PR #66 deployed as `fe2bb89eb65657a0a0d926cabc4c27cf9fef2c17`; the pilot returned
200 with comments, no page errors, noindex and zero overflow at 390/1440. However,
its CSS still used Rocket minify URLs and its script used a Perfmatters minify URL.
Therefore the intended stable-source-URL release criterion was not yet met.

The generic regex fixture had missed two actual vendor contracts: Rocket CSS
anchors the complete pathname, and Perfmatters independently rewrites script URLs.
The follow-up uses a dedicated full-path Rocket CSS pattern and Reader-directory
exclusions for Perfmatters minify/delay/RUCSS. Other hooks keep literal substrings;
RUCSS/Perfmatters must not receive the regex wildcard. No bootstrap timing change
is needed: the actual Rocket minifiers are constructed during buffer processing.

The replacement regression fixture loads the real vendored Rocket matchers and
Perfmatters exclusion matching after WordPress init. It covers five host/environment/
feature boundaries, retained existing rules and non-Reader theme assets. This
fixture fails against the original exclusion and is required to pass for the
follow-up. Browser release checks now inspect Reader element IDs, not just URLs
containing the original directory, and reject rewritten asset URLs explicitly.
