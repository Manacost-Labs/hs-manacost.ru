# Image pipeline: production reliability phase, 2026-09-10

## User contract and completion boundary

Both hs-manacost.ru and kolodahearthstone.com should accept valid images up to
50 MiB, including 3000×3000 pixels, promptly produce visually faithful web
images, and keep untouched originals in S3 without permanent local retention.
The user explicitly accepted visual fidelity plus a recoverable S3 original
and authorized implementation on the current production server.

**Only the reliability repair below is deployed. The complete new storage and
immediate optimization pipeline is not finished.** No production eager WebP
primary, new AVIF profile, manifest-based cleanup, Koloda S3 integration, Nginx
change, upload-limit change, or historical bulk processing was activated.

## Deployed narrow payload

Manual, user-authorized incident repair, not a whole-tree staging promotion.
An independent critical review approved the exact code and operator artifact.
Four files were extracted from immutable commits, backed up, hash-checked,
and atomically replaced while the existing cron/Action Scheduler/S3 worker
locks were held. Existing HS HTTP Action Scheduler execution is disabled by
the site's pre-existing AJAX guard; its production response was checked.

| Site | Source commit | Runtime change |
| --- | --- | --- |
| HS | `c24452d450257725bc0f8828fd193d7b747c6f8d` | S3 hydration bridge, optimizer bridge, then deferred-size accelerator |
| Koloda | `fac484c` | Its own deferred-size accelerator, preserving its theme-specific size policy |

The repaired callbacks load WordPress image helpers in fresh cron requests,
retry incomplete achievable image sizes, and do not call them successful.
HS background processing may recover missing sources from S3 only inside
owned processing callbacks; optimizer failures receive bounded retries and
retain per-file failure diagnostics. The optimizer's encoding profiles and
source-preservation rules are unchanged.

Canonical optimizer source commit: `64c4779`. Canonical S3 source commit:
`43a9a7d`. These two repositories have no remote configured. Their exact
bridge implementations are vendored in the published HS commit; the canonical
source commits themselves have not been pushed. HS draft PR #58 depends on
the existing performance branch/PR #50; Koloda draft PR #8 targets main.
Neither draft PR was merged or represented as a green production promotion.

## Verification evidence

### Isolated tests

- HS and Koloda `make check`: passed. Canonical optimizer and S3 checks passed.
- Canonical WordPress integration passed; real fresh-cron tests loaded the
  missing image helper and produced every possible registered size on both
  accelerator variants without changing the source hash.
- Gitleaks on each exact staged diff: passed.
- Changed HS accelerator/optimizer WPCS and PHPCompatibility: passed.
  Full PHPStan level 5: zero errors. The S3 bridge retains exactly its existing
  134 WPCS errors and 10 warnings; whole-project `make code-quality` is therefore
  **not green**. No standards configuration or baseline was weakened.
- Browser upload: valid PNG, exactly 52,428,800 bytes, 3000×3000; two uploads
  with the same filename obtained different URLs; source hashes were unchanged.
  The optional eager WebP experiment returned a 42,574-byte primary for this
  deliberately simple synthetic image. This compression ratio is **not a
  prediction for real photos/screenshots**. A 50 MiB + 1 byte image was rejected
  by the experimental guard; text media remained unchanged. Full scenario took
  13.555 seconds; this is not single-upload latency.
- The eager module is unpublished and absent from the production artifact.
  It is default-off; lossy PNG conversion additionally requires a separate
  explicit flag. PNG transparency/text fidelity and minimum-saving behavior
  remain gates before production enablement.
- Synthetic S3 PNG and WebP were independently downloaded and compared
  byte-for-byte. Actual WordPress recovery from missing local source changed
  from failing before the bridge repair to passing afterward.

### Production canaries

Each canary executed only its exact scheduled action/event, not the backlog.
Both preserve a 27,046,391-byte, 3000×3000 original with SHA-256
`b3a2dee6ee8e40c3dacd54d2bbfd7e3c1798a09fb6eb552b000720d96e3b2457`.

| Evidence | HS | Koloda |
| --- | --- | --- |
| Retained synthetic attachment | 316741 | 6858 |
| Scheduler | Action Scheduler IDs 697251 / 697252 | Exact WP-Cron hook + `[6858, 0]` |
| Deferred processing | 10.269 s, including S3 recovery | 0.446 s |
| Background optimization | 7.223 s | 5.956 s |
| Generated sizes | 29, none missing | 7, none missing |
| Original preserved | SHA-256 matches | SHA-256 matches |
| Optimizer result | processed, no nested encoder failure | processed, no nested encoder failure |
| Public explicit WebP | HTTP 200, 38,372 bytes | HTTP 200, scaled primary 5,038 bytes |
| Queue/worker lock afterward | No pending/in-progress media actions; lock released | Empty media event queue; lock released |

Koloda's existing WordPress scaling policy remains active: the preserved
original is 3000×3000; the display primary is scaled. No claim that the display
primary retains original dimensions is made.

After more than three minutes (FPM revalidation is 60 seconds), repeated
snapshots remained clean. Homepages and explicit optimized image URLs returned
HTTP 200 through both Moscow and Novosibirsk for HS .ru, HS .com, and Koloda.
Both origins also served the WebPs. TLS verification remained enabled; HS
origin used its configured self-signed public certificate as an explicit local
trust anchor, not an insecure verification bypass.

## Operator defects caught and corrected

1. First installer attempt stopped before any runtime replacement: opening an
   existing lock with `<>` hit Linux sticky-directory protected-regular policy.
   Read-only descriptors plus the same exclusive `flock` passed fresh review.
2. The successful installer's `umask 077` leaked into its canary child, creating
   a private synthetic media directory and subsize files. Backend verification
   passed but HTTP verification caught 403 responses. Only the named synthetic
   HS canary directory was changed to 0755 and its generated PNG/WebP/AVIF files
   to 0644. No source bytes or real media paths were changed. Nginx's 120-second
   negative file-cache entry expired naturally; no global cache purge/reload.

**Before reusing the operator canary, scope its child umask to 0022 and test
resulting file/directory modes.** The archived installer is evidence of the
executed revision, not a reusable ready-to-run release command.

## Recovery, protected state, and remaining work

Successful transaction backup:
`/var/backups/hs-manacost-deploy/20260910-media-reliability-S22qsS/`.
It contains exact previous files and immutable payloads. Reverse file order
restores accelerators first, then optimizer/S3. All hooks retain their names;
never delete originals/sidecars to roll back this repair. Canary cancellation
is restricted to the named synthetic ID and attempts 0–4. Synthetic production
attachments, S3 objects, state, and backups are intentionally retained.

Prior OPcache/cache-purge/admin-meta repairs, existing production content,
other dirty worktrees, themes, plugins, credentials, Redis, proxy settings,
S3 timers, and server image/PHP limits were not modified by this release.

Next dependency-ordered phases:

1. Prove quality and bounded latency on representative photos, text/card
   screenshots, alpha, orientation, duplicate names, and malformed uploads.
2. Return a verified smaller primary immediately; keep heavy optional AVIF
   work asynchronous and maintain a recoverable exact original.
3. Add per-site, per-attachment manifests and isolated Koloda S3 keys. Verify
   checksums/MIME/dimensions and restore before marking files cleanup-eligible.
4. Coordinate the legacy HS age-based mover with generation/optimization.
   Its current independent 15-minute age rule is unchanged by this repair.
5. Correct delivery before cleanup: HS original PNG URLs still serve PNG even
   with modern Accept; explicit WebP URLs work. Koloda already negotiates local
   WebP for modern clients with PNG fallback. Remote variant negotiation and
   Koloda S3 fallback are not implemented by this phase.
6. Run authenticated real-admin uploads on both sites, including the user's
   actual failing image. No production account credentials were supplied, so
   the production browser/editor upload flow remains unverified; backend
   canaries and isolated browser tests are not a substitute.

Selected profile: WordPress. Media-integrity, runtime-stack, external-integration,
editor, observability, and release skills determined the source-hash checks,
site-specific variants, retry/failure tests, and phased no-cleanup boundary.
