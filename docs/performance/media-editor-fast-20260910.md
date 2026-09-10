# Deferred media recovery — 2026-09-10

## Scope and release boundary

This slice repairs the existing asynchronous image pipeline on hs-manacost.ru.
It does not change original files, encoding profiles, upload limits, storage
retention, public URLs, authentication, or Koloda. It does not add eager primary
conversion or claim faster HTTP upload responses. Only three MU-plugin bridges
change. S3 bridge formatting/naming cleanup is required by the touched-file WPCS
gate; the Hydrator implementation and its fixed remote origin remain unchanged.

Source baseline: `767da55d5ed55064e731a1438c7d1ac90412c2a3`.
Last recorded production workflow: `79a719ec11826d6774f41350025675486dd996af`.
At preparation, main additionally contains PR 61 (Reader profile/comments),
outside the media request. Production promotion is held pending release-scope
coordination; PR 62 (admin navigation) is not part of this candidate.

## Behaviour

- A fresh worker loads WordPress's image helper itself.
- Incomplete sub-size metadata and encoder failures trigger bounded retries,
  not a false successful handoff/result.
- Actual owned media callbacks may hydrate locally absent sources from S3.
- Core image-editor and preview AJAX callbacks retain hydration; an arbitrary
  request `action` or generic cron context does not enable it.
- Converted WebP attachments can process the preserved JPEG/PNG original;
  recursive `.webp.webp` sidecars remain excluded.

## Local evidence

Pinned WordPress 6.9.7, PHP 8.2, Newspaper_new, isolated database, no production
content or credentials. The test namespace is `hs-media-editor-fast-20260910`,
loopback port 18888. No changes to production PHP configuration were made.

18 focused regression tests pass, including nested encoder failures. The
three runtime bridges passed WPCS, PHP compatibility, strict rules, PHPStan
level 7, full PHPStan, and structure/baseline checks. `make check` passed.
Fresh-context Astra reviewed the runtime diff without blocking findings.

Real browser uploads used a valid 3000x3000 PNG padded with a legal tEXt chunk
to exactly 52,428,800 bytes. This tests the request-size limit but is not a
high-entropy photographic workload. Six serial uploads per version: one warm-up
plus five measured samples. The main/source URL stayed PNG; original SHA256s
were identical and repeated names produced distinct files. Previews decoded on
desktop and remained visible at 390 px. No JavaScript page errors occurred.

| Metric, local warm runs | Baseline | Candidate |
|---|---:|---:|
| Upload response samples, ms | 619, 583, 568, 549, 621 | 599, 589, 593, 607, 567 |
| Median response, ms | 583 | 593 |
| Thumbnail-visible samples, ms | 652, 612, 598, 579, 651 | 630, 625, 622, 639, 597 |
| Median thumbnail-visible, ms | 612 | 625 |

These results establish no material upload regression, not an upload speedup.
They exclude WAN transfer, production plugin set, regional proxies and S3.
The harness field `preview_ms` records thumbnail visibility. Successful image
decode is asserted immediately afterward, but its completion time is not stored.

Fresh plain PHP + wp-load (not WP-CLI, which preloads image helpers) reproduced
the old worker's missing-helper failure. The fixed callback generated all six
registered local sub-sizes in 0.586 s, with no missing files and unchanged source.
A warm replay took 0.090 s without changing the original.

The offload fixture moved only its synthetic local source, intercepted the
fixed S3 HTTP boundary, and exercised the real Hydrator and real cwebp encoder.
It fetched the missing original exactly once, processed seven files in 6.994 s,
and produced a 38,372-byte WebP sidecar for the 27,046,391-byte synthetic PNG.
The original hash stayed unchanged. This is not live S3 delivery evidence and
the unusually large saving is specific to the synthetic gradient fixture.

The canonical PHP integration assertions also passed in the isolated runtime.
Full canonical integration/visual/performance CI and exact-SHA staging/live
checks remain required before production promotion.

## Reproduction and safety

`tests/integration/media-pipeline.php` refuses non-local environments and hosts
other than 127.0.0.1. Run its `create`, `cron`, and `offload` phases in separate
plain PHP processes after loading the disposable wp-load.php. It must not run
on production. `cron` intentionally asserts helpers were initially absent.
`offload` requires cwebp/avifenc in the disposable container and mocks only the
S3 HTTP boundary. It keeps a temporary source copy for recovery on failure.

`tests/integration/media-upload.mjs` requires HS_MEDIA_RUNTIME,
HS_MEDIA_BASE_URL (loopback only), and HS_MEDIA_LABEL=before|after. Credentials
are read only from the disposable integration runtime.env; never copy live
cookies/authentication into it. The browser is the pinned project Playwright
container. Numeric evidence is written under ignored `.artifacts/`.

## Remaining risks and rollback

Existing Hydrator blocking locks and a 120-second per-file timeout can exceed
the optimizer's 900-second lease during a broad S3 outage. No lease/timeout
redesign is included. Existing scheduled jobs are not drained or bulk-retried.

The release must retain a backup of the exact pre-change runtime files and
record the full promoted SHA. Rollback restores the previous verified code;
this change introduces no schema or source-file migration. New failure metadata
may remain as diagnostics; rollback must not delete attachments or S3 objects.
Authenticated staging/live editor upload, real S3 recovery and regional delivery
are not proven by the local fixture or general availability checks.
