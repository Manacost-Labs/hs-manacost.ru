# Reader cabinet geometry and comment identity — staging candidate

## Scope and ownership

- Base: `79a719ec11826d6774f41350025675486dd996af`; isolated worktree
  `hs-manacost-reader-profile-comments-20260910`, branch
  `fix/reader-profile-comments-20260910`.
- HIGH risk because public profile publication needs explicit, versioned consent.
- Own only Reader first-party UI, the two comments service modules, focused tests
  and this documentation. No theme/vendor/workflow/proxy changes, no production
  deployment, no session reset or bulk publication of personal data.
- Profile: WordPress. Project/impact/clean-code and required review/security/TDD
  guidance; focused typography/responsive/Newspaper/accessibility/browser and
  privacy/API guidance. More than the normal skill budget was necessary because
  the project explicitly requires the UI and publication cross-cutting rules;
  no new redesign direction, fonts, framework, external integration or dependency.

## Reproduction and fix

- Browser baseline at 1440: class right edge and edit-button left edge both
  `578.328125`; class height 58 and button height 44. Explicit flex grouping now
  provides a 16px gap, matching 56px controls and wrapping at narrower widths.
- The active staging BFF artifact was
  `421352b2224d159df7dc0395fb586d51d70e9b5f`. Runtime/source comparisons found only
  `comments-http.js` and `comments-store.js` stale. The private profile routes
  were current. The public pilot response returned an empty avatar and omitted
  platform flags, consistent with the reported old comment.
- An existing consented public snapshot is distinct from the private account.
  The new owner-only PUT refreshes the exact saved version after unchecked-by-
  default confirmation. No new comment is required. This does not automatically
  publish private profile edits or change other readers' stored data.
- Publication uses the existing public-profile editorial eligibility boundary
  (a bounded set of up to 20 distinct comment article IDs), not an unbounded
  historical scan. It cannot create a public profile before the first visible
  comment or resurrect erased community data.
- Small filled crowns use a square, softly rounded gold backing. Discussion
  fields have light background tokens; the account keeps its dark palette.
- Reader assets are versioned `0.7.7` in both account and article loaders.

## Candidate verification

- Regression first: new publication tests failed with 404; browser failed on
  touching class/edit geometry. After implementation both pass.
- `make check`: 148 repository Python, 87 Reader Node, 23 Reader UI Python tests;
  syntax, contracts, skills and shell gates pass.
- `make code-quality`: changed-file WPCS/PHPCompatibility/strict/PHPStan and
  full PHPStan pass; baseline unchanged. Locked npm dependencies audit clean.
- `make reader-browser-test`: account state/race tests, comments geometry,
  loaded avatar/marks, consent behavior and eight focused comment flows pass.
  Viewports 320/390/560/768/1024/1440 plus zoom-equivalent reflow. Fixtures use
  synthetic identity and the committed Newspaper CSS, not a live login proof.
- `make visual`: isolated WordPress behavior checks pass; 21 visual tests pass,
  one pre-existing mobile duplicate-overflow test intentionally skipped.
- Existing typography and responsive contracts validate unchanged; no font or
  outer-container changes. Read-only actual staging guest account and pilot
  screenshots also captured; no page errors or horizontal account overflow.

## Release boundary

Fresh-context Sol review, staged security scan and green CI are required before
release. WordPress staging deployment and the immutable BFF artifact activation
are separate operations. Back up the staging SQLite file consistently, retain
the previous binary link and restart only `manacost-reader-staging.service`.
The service source delta has no schema/dependency migration. Verify both live
asset bytes and the public response shape after activation. The user's own
avatar/Twitch publication needs confirmation from their authenticated editor;
fixtures or public GET checks alone do not establish that action happened.
