# Narrow production media release — 2026-09-10

## Actual outcome and authority

The user approved an immediate, backed-up deployment of only three media MU
modules, excluding the unrelated Reader/main release. The three files were
deployed to staging, exercised with real S3, then deployed to production and
exercised again. No Nginx, PHP service, scheduler configuration, theme, Reader,
existing article or existing media-library attachment was changed by this task.
The mirror hs-manacost.com shares the runtime; Koloda was not modified.

This is **not a full release of main**, nor proof that the entire runtime matches
commit 7716cd4e20b53f5dde11d16a44bd6565c5d3ad99.

## Source and deployed hashes

Base: reviewed PR64 / commit 7716cd4e20b53f5dde11d16a44bd6565c5d3ad99.
The live staging canary discovered one additional runtime mismatch. Its fix is
in the separate worktree `hs-media-worker-runtime-20260910`, branch
`fix/media-worker-runtime-20260910`, on that base. This report accompanies the
source reconciliation commit containing the additional patch, regression test
and regenerated contract offsets. PR64 must include this commit before a full
release; the original 7716cd4 alone does not contain the runtime correction.

| File under wp-content/mu-plugins | Deployed SHA256 |
| --- | --- |
| hs-media-upload-accelerator.php | f83ef2e2c7c2c997445a1ec7e5ff101c3a291fcc7765284a465af55f36f417d9 |
| hs-local-image-optimizer.php | 7aaf486321a6bc07d3995e1a8d456f25f07bf6d14d2bb4f2e303680d619c998b |
| hs-manacost-s3-offload.php | 44fc1f5f50e7a507f8b76ad340da19330cdac044762c98e924ed34634c80b806 |

SHA256 of the additional accelerator diff against base:
`521009e3cac0c2adbaee03132ba27c1c82eaf80de4777e7e6d461e48793f4683`.

## Additional root cause caught by staging

The production AS runner uses `/usr/bin/php` (8.2, GD available, Imagick absent),
while FPM and the upload path use `/opt/php84/bin/php` (8.4, Imagick available).
WordPress 6.9.7 caches its chosen image-editor implementation before checking
capabilities on the next request. A cached Imagick selection from PHP8.4 can
therefore fail in PHP8.2 with `Class "Imagick" not found`.

The accelerator now temporarily removes core Imagick from the implementation
list when that extension is unavailable, only around deferred-size generation.
This changes the cache key without flushing shared caches. Custom editor order
is preserved and `finally` removes the filter on both success and exception.
No fourth server/configuration file was changed.

The first staging canary (315981) correctly failed the release gate before
production deployment. After the fix, its exact retry695274 and optimizer695279
completed; its 29 sizes and intact original were verified. It was not abandoned
with broken previews. Historical canaries316741/697251/697252 were not touched.

## Real backend and S3 canaries

Each is a newly created, synthetic, unattached image in its own unique prefix.
The ordinary native media sideload path generated only three immediate preview
sizes. The original and these previews were backed up, copied to their exact S3
keys, fetched publicly and compared byte-for-byte, then moved recoverably out
of the local upload directory. A fresh PHP8.2 process ran only the canary's exact
AS actions, at the actual runner's 512MiB/90s limits. It loaded the missing core
helper, recovered sources from S3, generated the remaining sizes and optimized.

| Environment / attachment | Source | Initial native processing | Worker processing | Sizes / result |
| --- | --- | ---: | ---: | --- |
| Staging315982 | PNG3000x3000, 50MiB | 1747.5ms | 10778.1ms | 29, processed |
| Staging315983 | JPEG1600x1600, 98165bytes | 412.6ms | 8983.6ms | 26, processed |
| Production316765 | PNG3000x3000, 50MiB | 770.6ms | 12466.9ms | 29, processed |
| Production316766 | JPEG1600x1600, 98165bytes | 444.1ms | 9723.4ms | 26, processed |

JPEG has fewer eligible sizes because WordPress does not upscale. PNG produced
57 physical image files including accepted WebP variants; JPEG produced76,
including24 AVIF variants. A 768px PNG WebP candidate saved only3.71%, below its
5% threshold, and was correctly not published.

The 50MiB PNG uses legal tEXt padding, not photo-like entropy. GD emitted a
padding-specific libpng warning but decoded it successfully; the original
remained intact. These are single native backend measurements, **not HTTP
upload-limit tests, browser timings, latency percentiles or realistic compression
ratios**. Authenticated production editor upload was not measured. Staging's
outer BasicAuth remained in place (401); no credentials/session bypass occurred.

Successful source hashes before and after S3 recovery:

- PNG: `3c8608e4532d8a234e211825dcb1d7711f53dab01175d53cde026dd1def2c58e`.
- JPEG: `3ac2e45c6a97364bcee2e95f8655eda48cfecc66635b1d216d70def9eda8dfbe`.

Production AS actions697322–697325 completed. A later read-only recheck found
both attachments processed, no pending owned attempts0–5, and no missing
metadata sizes. Existing worker lock files were opened read-only and respected;
no global AS/WP-Cron/offload worker was manually drained or run.

## Delivery: verified availability, missing automatic selection

The canaries' 696px canonical images and accepted variants were copied to S3,
fetched and byte-verified, then moved to private quarantine. While absent
locally, canonical and explicit WebP/AVIF URLs were tested through seven routes:
primary public DNS, mirror public DNS, pinned-TLS origin, Moscow and Novosibirsk
for each domain. First/repeat passes checked HTTP200, exact MIME and exact bytes.
PNG:42 requests passed. JPEG:56 requests passed. First/repeat is not a claim of
independent cold caches; shared caches can warm between routes.

**Automatic format negotiation is disabled in current Nginx configuration.**
`01-image-variants.conf` deliberately uses `try_files $uri` followed by canonical
S3 fallback, to avoid missing-variant404s. A PNG URL returns PNG even with modern
Accept headers. Explicit `.png.webp` and `.jpg.avif` URLs work, but that does not
prove visitors receive those smaller formats through existing article URLs.

Example from the production696px fixtures: PNG4762bytes / explicitWebP842bytes;
JPEG14623bytes / explicitWebP4074bytes / explicitAVIF961bytes. These synthetic
values are delivery checks, not representative savings estimates.

Enabling safe remote-aware format selection, fallback and cache variation needs
a separately scoped Nginx/proxy change. This task did not enable it. Fully
instant optimization, zero temporary local image retention and a measured
production editor upload speedup are not claimed.

## Checks, backups, rollback, protected state

- New regression failed before the patch and passed afterward;9 accelerator tests.
- Full `make check`:162 Python tests,87 Node tests,23 Reader UI tests, PHP/shell
  syntax, contracts and skill audit passed. Earlier prerequisite failures were
  resolved by regenerating three contract line offsets and installing locked
  dependencies in this isolated worktree; no test was disabled.
- `make code-quality` passed WPCS/strict/compatibility, PHPStan level7 and full
  analysis, baseline ratchet and structure checks.
- Independent Astra review approved the runtime hash and bounded operation scripts.
- Production/staging target hashes and koloda:koloda0644 ownership were checked.
  All70 other production MU-file content hashes matched before/after deployment.
- Homepages on both domains and the production wp-login endpoint returned200;
  FPM8.4 and the S3 timer stayed active. No service restart was needed.
- Production rollback copies:
  `/var/backups/hs-manacost-deploy/20260910-media-7716-production.Ikv3Vm`.
- Staging rollback chain: `20260910-media-7716-staging.x8NfqS` (pre-release) and
  `20260910-media-7716-staging.9S7Qvh` (before the extra accelerator correction).
- Copies were restored into private test directories and their original hashes,
  mode and ownership checked. This is a rollback-copy check, not a live rollback
  or database restore drill. No production rollback was required.
- Operational scripts, exact canary state and JSON/HTTP evidence remain in
  `/tmp/hs-media-release-7716.DFxxBV`; original/variant backups and quarantine
  remain under the named `20260910-media-7716-*` backup directories.
- Nothing material was deleted. New test attachments remain unattached and
  labelled; their selected files are available through S3 and recoverable copies.
- Selected profile:wordpress. Release/media-integrity skills required backups,
  exact scope, recovery and regional checks; TDD required the new regression.

The source worktree contains only the accelerator fix, its regression test,
generated contract line offsets and this report. Main/other worktrees, Reader,
server configuration and the separate optimizer repository were preserved.
