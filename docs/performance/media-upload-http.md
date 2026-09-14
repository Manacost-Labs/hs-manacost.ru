# Native HTTP upload regression, 2026-09-10

## Scope and current production boundary

This phase adds a real-browser regression to `make integration` and the existing
Quality integration job. No WordPress production file, PHP/Nginx production
setting, real attachment, S3 object or article is changed by this patch. The
previous S3-aware negotiation remains limited to its four synthetic prefixes.

The production browser connector rejected `/wp-admin/upload.php` with an
allowlist/blocklist error. Access was requested, not bypassed. This test uses a
separate disposable WordPress 6.9.7/Apache/PHP 8.2/MariaDB database on loopback,
not a copied production session or database. It cannot accept a remote base URL.
Production has FPM 8.4, real editors, traffic, Action Scheduler and S3: those are
not proven by this regression.

Read-only production inspection found site-level PHP settings: upload 1024 MiB,
POST 1100 MiB, memory 1024 MiB, input/execution 600 s; the FPM pool has a 120 s hard
request timeout, and origin Nginx accepts 1100 MiB. Generic PHP CLI defaults of
2 MiB/8 MiB are not the effective site-FPM limits. No limit was raised on production.
All three media MU-plugin hashes still match the prior reviewed live release.

## What the test actually checks

- Native login and Classic Editor media modal, not a simulated sideload.
- Anonymous uploads redirect to the same-origin login; an authenticated upload
  with an invalid nonce returns 403. Redirects are checked, not followed.
- A 3000x3000 PNG of exactly 52,428,800 bytes passes the native multipart endpoint.
- Thumbnail and medium previews are available, decode, and insert into TinyMCE.
- Two different images with the same filename get different attachment IDs/URLs;
  both originals match SHA256 and the first is rechecked after the second upload.
- Draft save and reload retain both decoded images; stored `post_content` is
  checked independently through WordPress APIs.
- Metadata contains exactly thumbnail/medium/medium_large; remaining sizes have
  an exact deferred WP-Cron event for each attachment. The production AS runner
  and S3 processing are intentionally not invoked.
- Only after all ownership/hash/draft checks pass are those two attachments,
  their exact deferred events and their draft removed from the disposable site.
  Existing visual/performance fixtures remain for the following suites.

The 50 MiB PNG uses a valid private ancillary chunk for padding. Its decoded
pixels are simple, so this is a byte-limit/integrity test, not a photo compression
benchmark, visual-quality assessment, network simulation or production speedup.
Single local timings in the JSON report are diagnostic observations, not five
comparable baseline/after samples or p95 measurements.

## Reproduction and isolation fixes

The initial browser check correctly failed at the disposable uploader's 2 MiB
limit. A test-only INI now permits 50 MiB files, 64 MiB requests and 512 MiB memory.
The CLI and web containers share the local environment configuration; previously
the CLI lacked it and the local-only guard rejected the run. Both changes are
confined to the integration Compose configuration.

Playwright's in-memory file API rejects 50 MiB buffers. The fixture is therefore
generated in the disposable browser container and uploaded through its file-path
API. No generated image or auth state is committed. External browser requests
are blocked; original fetches are same-origin with redirects disabled.

The first full visual run used four workers and one authenticated dashboard case
captured the login form. Concurrent tests share a single WordPress user; core
rewrites that user's entire session-token array, so lost session updates are a
plausible cause, not a proven production defect. The integration visual command
now uses one worker. Assertions, snapshots and retries are unchanged. Historical
PR #69's CI flake was a different WebKit navigation error, not this login failure.

## Verification and remaining work

Run `npm ci`, then `make integration`; CI's existing integration job also runs
the visual and admin-performance suites. The known shared Compose project must
not already be owned by another run. Use this worktree's exact start/stop paths;
do not stop another task's containers. `run.sh` tears down only its disposable
stack on exit. Native `test.sh` expects a fresh instance, not a rerun over old
duplicate-name fixtures.

The final `RUN_VISUAL=1 RUN_PERFORMANCE=1 make integration` passed: native HTTP
upload and backend integrity checks passed; 21 visual tests passed without a
retry. The one existing skipped test is the duplicate mobile execution of the
responsive matrix, which already runs at all five widths in the desktop project.
All four local admin-performance screen reports passed their existing budgets
with five samples each. Their existing baseline is not a measured production
before/after comparison for this patch. The disposable stack was removed by the
normal exit trap. An independent fresh-context Astra review approved the eight
test/documentation paths, including single-worker session isolation.

`make check`, `make code-quality`, PHP/Node syntax, ShellCheck and whitespace
checks passed. Staged secret-scan and branch publication results are recorded in
the handoff.
The non-secret report is `.artifacts/integration/media-upload-report.json`.

Production acceptance still requires authorized browser access, real-role
upload/insertion and repeated timing samples, then exact original/sidecar S3
verification. Broad modern-format delivery, verified local cleanup, real visual
quality sampling and restore/backfill remain separate uncompleted gates. The
skills for media integrity, editor testing and safe release prevent a local
green test from being described as production acceptance.
