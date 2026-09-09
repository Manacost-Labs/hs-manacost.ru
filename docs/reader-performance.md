# Reader compact UI and loading — staging, 2026-09-09

## Scope and baseline

User-approved compact dark Manacost direction; the working account/editor and
comments remain independent from WordPress users and native comments.
This release targets `test.hs-manacost.ru`, not production or HearthPulse.
Baseline source/runtime: `39a70dc357e9733c9290e4df89e96a825a333ed7`.

Guest account lab: Chromium on the server, 1440×900, no CPU/network throttling,
five fresh browser contexts with one repeat visit each, staging BasicAuth,
six-second observation window. “Cold” means browser cache, not server cache.

| Signal | Cold median | Warm median |
| --- | ---: | ---: |
| Login link usable | 3.097 s | 2.765 s |
| Document TTFB | 1.136 s | 0.848 s |
| FCP | 1.984 s | 1.532 s |
| Requests observed | 63 | 60 |
| Encoded transfer observed | 1.843 MB | 1.839 MB |
| JavaScript transfer observed | 940 KB | 939 KB |

Reader scripts themselves contributed about 7.8 KB. There was one `/me`
request per navigation: no initial duplicate was reproduced. No >50 ms main
thread task was seen under these conditions; this is not proof of field INP.
Load had not completed within the window, so transfer totals are bounded
observations, not final page totals. No eligible field/RUM measurements are
available for this private staging route.

A separate attribution run recorded a 49 px main-content shift alongside
narrowing navigation links; six-second layout-shift sum was approximately
0.337. Holding/releasing font requests confirmed that the live nav uses
**PT Sans**, with narrower loaded metrics; it is not the account's declared
Roboto Condensed. Preserve that distinction when diagnosing remaining shifts.

Pilot comments, one baseline sample: `/me` started at 2.229 s and took 0.696 s;
the list started at 2.927 s and finished at 4.435 s. A visible login link is not
evidence that the discussion is ready.

## Changes and boundaries

- Only the provisioned, non-preview frontend account removes Ad Inserter code,
  Newspaper analytics/footer tracking callbacks and Plausible initialization.
  Supported hooks are used; no vendor files or runtime options are changed.
- Only that account dequeues article image modals, social sharing and native
  comment replies. Ad Inserter prints its script outside WordPress's queue,
  so its footer output callback is removed; buffer cleanup is retained.
  Newspaper's body callback is retained (its current staging option is empty),
  not blanket-removed because that extension point can own consent UI.
  Theme navigation/search,
  jQuery and reader dependencies remain. Articles, other pages, admin and
  Composer previews keep their existing integrations.
- Comments request `/me` and the initial list concurrently. The list is not
  rendered until identity resolution settles; ownership checks for legacy
  pending comments are unchanged. An initial anonymous 401 is not treated as
  an expired session. Abort/generation checks still discard stale results.
- The BFF checks current paid status and editorial availability concurrently
  on public reads. It still checks availability and re-reads current data after
  awaits, so takedown/erasure wins. No new caching or database schema is added.
- UI asset version is bumped to 0.6.0. Authentication, consent, rate limits,
  CSRF, paid-source verification, upload validation and publication policy are
  not relaxed.

## Verification and release

Behavioral regressions hold each independent request and prove overlap rather
than asserting source strings or relying on artificial speed thresholds. They
also erase a profile during held requests and verify that private data cannot
return. Route fixtures verify that account-only integration filtering cannot
affect an article, other page, admin, preview or missing account.

Run `make check`, `make code-quality`, `make visual`, and
`READER_TEST_CHROMIUM=/usr/bin/chromium make reader-browser-test`, followed by
fresh-context Sol review, secret check, PR Quality and staging deployment.
The normal pipeline deploys WordPress only. Prepare the exact merged clean SHA
with `ops/reader/release-staging.sh`, then restart only the staging reader
service after validating the current/previous artifacts and rollback.

After deployment, verify the actual asset versions, guest/login/profile/pilot
flows, menu/search, no account ad/tracker requests, unchanged article surface,
and equivalent cold/warm measurements. Record results separately: fixtures do
not prove real-account OAuth/save/publish latency, and new lab results do not
establish field improvements.

Rollback is code-only to the baseline WP/BFF artifacts. Keep the live SQLite
database, current sessions, profiles, comments and protected release history;
do not restore an old database over new user activity. No production promotion
is authorized by this task.

Profile: WordPress. The project-mandated baseline plus UI/accessibility,
performance and release skills exceed the generic skill-count budget. They
were applied in bounded phases; no catalog/policy edits or unbounded indexes.

## Live first release and 0.6.1 follow-up

The compact UI and BFF were deployed to staging at `36390c00ab914e8790aa1a6e29dfe36c7552cd9e`.
Account requests no longer contained the selected advertising/analytics hosts.
First-release diagnostic medians fell to approximately 100 KB JavaScript and
974 KB observed transfer (about 89% and 47% below the original cold baseline).
These are provisional diagnostics: the first samples overlapped a separate
browser verification. Repeat the final benchmark without concurrent browsers.

The lighter page now completed its load within the observation window. This
exposed an initial `pageshow` handler restarting `/me`: two calls in all ten
desktop observations. Version 0.6.1 refreshes on persisted BFCache restoration,
not the initial `pageshow`; the initial request and focus revalidation remain.
A browser regression failed with two calls before this fix and passes with one;
the existing restoration test explicitly exercises `PageTransitionEvent.persisted`.

A separate alternating control/preload experiment (three fresh contexts each,
1440×900, six seconds) isolated the navigation font. Median layout-shift sum
fell from 0.25866 to 0.01093 when the already-used Latin/Cyrillic PT Sans files
were preloaded. Both variants fetched/fulfilled the same document route; only
the preload variant inserted links. This was an in-browser HTML experiment,
not a deployed result.
The follow-up uses WordPress resource preloads only on the account, and only
while the queued Google stylesheet still includes PT Sans 400. It does not
change the theme font family, weights, local-font configuration or article
routes. The v18 URLs are pinned to the currently observed Google CSS; recheck
them if the upstream stylesheet changes. Existing third-party preloads remain.

This follow-up changes only the WordPress UI; the reviewed staging BFF artifact
at `36390c0` does not require a restart. Its rollback is the preceding WordPress
artifact, with database and sessions untouched. Real OAuth/profile-save/comment
publication latency remains separate from anonymous and synthetic UI checks.
