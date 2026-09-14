# Admin navigation: bounded meta-key cache

## Scope and observed baseline

This release only avoids repeating Classic Editor's Custom Fields key-list query.
It does not cache authenticated pages, editor HTML, metadata values or permissions.
It does not change uploads, S3, autosave, revisions, security or cache purging.

The production source baseline is `79a719ec11826d6774f41350025675486dd996af`.
On 2026-09-10 the production MU directory did not contain this module. The earlier
candidate was still in draft PR #50; deploying current main did not retain it.
This small independent branch avoids coupling the cache to that PR's cache-purge
changes or to the separate media reliability candidate in PR #58.

Read-only production observations from 09:21–09:30 UTC:

- Five sequential direct executions of the native bounded meta-key SQL took
  449.97, 442.54, 438.58, 447.08 and 439.63 ms including the CLI client round trip.
  This is a component measurement, not proof of its frequency in a browser.
- Eleven observed `GET /wp-admin/post.php` requests had median 881 ms, all HTTP200.
  Roles, cache state and document data were not controlled. Three observed POSTs
  returned HTTP302 in 0.497–4.054 s; redirects alone do not prove successful saves.
- Current PHP8.4 pool queue and max-children-reached counters were zero. The old
  monitor's PHP8.1 slowlog and 48-worker threshold did not match the active pool.

## Contract

The MU filter caches only the same bounded key list requested by core `meta_form()`
for 300 seconds in the site's object cache. Warm reads omit that query. With no
persistent object cache this helps only repeated reads in a single request.
Public-key insertion/deletion and successful by-ID renames change the generation;
value-only updates, including view counters, do not. The limit is part of the cache
key. Earlier filter overrides, core permission checks, query errors and unusual
limits retain native behavior. No global flush is performed.

The one WPCS DirectQuery annotation documents the unavoidable exact core key-list
query; no high-level WordPress API provides that projection without loading values.
It is not a repository-wide lint suppression or a change to the quality baseline.

## Verification

- Test-first: the restored 19-scenario regression fixture had ten failures without
  the module and all scenarios passed with it.
- `make check`, `make code-quality`, `make contracts` and `make change-impact`
  passed before final integration wiring. Contracts had no generated diff;
  PHPStan's existing 32-error baseline was unchanged.
- Isolated WordPress6.9.7 integration passed. Existing browser suite: 21 passed,
  one intentional mobile matrix skip. Dashboard, posts list, media library and
  editor synthetic budget checks passed. Their fixed baseline is not measured
  before/after production speed and must not be reported as an improvement ratio.
- The additional real-core local fixture passed output parity, warm-query removal,
  insert/delete/rename/value-update behavior, upstream override and permission
  checks. It deletes only its disposable draft and refuses nonlocal environments.
  CLI receives explicit local URL constants because the domain-bootstrap MU would
  otherwise choose the production hostname even with the disposable database.
- That fixture is now wired into the canonical integration scripts. A local full
  rerun collided with another task's identically named Docker project and was
  cancelled, not counted as a pass. The final CI run must verify the wiring.

## Release and rollback

Require fresh independent review, security scan and green CI, followed by exact-SHA
staging verification before promotion. Record actual deployment evidence separately;
this document alone does not assert staging or production activation.

Rollback is a source revert removing this one MU module followed by the normal
staging/production workflow. No content or schema rollback is required. Abandoned
cache entries expire after five minutes. Do not deploy the old combined PR merely
to activate this independent change.

## Remaining editor-wait work

1. Measure five comparable authenticated navigations per affected screen on the
   real dataset, separating network, PHP/SQL and browser work; preserve role and
   cold/warm state. Component SQL timings cannot replace that evidence.
2. Restore the missing deferred-media worker helpers, completeness checks and
   worker-owned S3 hydration through a separately reviewed green candidate.
3. Measure upload response versus thumbnail/WebP/AVIF/S3 completion using immutable
   disposable images, including 3000×3000 and the agreed 50 MB limit. The current
   request still creates thumbnail, medium and medium_large before replying.
4. Keep originals intact in S3 and preserve early insertability. Do not add eager
   AVIF conversion to the upload response or claim instantaneous compression.
5. Validate real editor draft/autosave/revision/preview/publish and media insertion
   on staging. Production login pages and public smoke checks are not that journey.
