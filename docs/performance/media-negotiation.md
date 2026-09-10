# S3-aware media negotiation: bounded rollout

## Scope and current status

The new configuration is deliberately separate from the active resources. It is
not included by the normal WordPress deployment. The initial route matches only
the four synthetic `hs-media-7716-dfxxbv2-{staging,production}-{png,jpg}` prefixes
under uploads/2026/09. Arbitrary articles, old images, GIF, native WebP/AVIF,
upload handlers and the separate Koloda WordPress are outside this phase.

The previous media worker correction is committed as fd89f0a in PR64. It is
already present in the three-file production hotfix; a full runtime release
must include that commit. This Nginx change does not deploy WordPress/main.

## Delivery contract

1. Explicit AVIF support with positive quality: try the adjacent local sidecar,
   then the exact S3 sidecar key.
2. Unavailable AVIF and explicit WebP support: try local WebP, then S3 WebP.
3. No acceptable/available variant: try the original local path, then original
   S3 key. A failure of the canonical object retains its real failure status.

Only optimizer-published sidecars are eligible; the optimizer already validates
dimensions, decoding and savings before publication. Nginx does not encode,
modify, copy or delete images. S3 is a fixed HTTPS destination, with certificate
verification. Cookies, Authorization, client bodies and S3 request identifiers
are not forwarded/exposed. Negotiated canonical URLs return full 200 responses
for Range/If-Range requests: S3 conditional range support and date validators
shared across formats must not splice bytes of different representations.
Explicit sidecar URLs retain the existing range behavior.

Absent/wildcard/malformed Accept values conservatively select the original.
Explicit q=0 excludes a format, including contradictory duplicates. Relative
quality weights do not override server preference among explicitly supported
formats; this is not a general-purpose Accept parser.

All negotiated branches emit Vary: Accept. Successful public results have a
five-minute cache lifetime, not immutable/year-long caching. Errors are no-store;
staging is private/no-store. A local mtime/length ETag cannot alias two formats:
local ETags and If-Modified-Since comparisons are disabled in these branches.

## Regression checks

`make nginx-media-test` starts an unprivileged isolated Nginx, fake S3 and two
independent loopback cache proxies. It is part of `make check` and CI. It covers
local/remote AVIF/WebP/original, missing 403/404, 429/5xx, unsupported Accept,
explicit-format URLs, HEAD/Range, encoded names/query strings, anonymous and
BasicAuth states, upload rejection, credential isolation and cache variation.
The initial assertion failed against the previous JPEG-only route before the
new configuration was written.

This test does not prove the live regional cache configuration, real upload
latency, realistic compression ratios or authenticated production editor flows.

## Activation gates and rollback

Install reviewed immutable copies of these four files under
`/etc/nginx/hs-media-negotiation/`. Include `http.conf` at HTTP context and
`server.conf` before general upload regexes in the target virtual host. Staging
has its own media locations: changing production resources alone cannot test
staging. Retain BasicAuth and each host's robots policy throughout.

Before any live activation: independent review, passing full checks, exact-file
backups, `nginx -t`, and a named restore path. Ordinarily activate staging first.
On 2026-09-10 the user explicitly directed immediate production optimization
instead of supplying access through staging BasicAuth. For this phase that
authorizes only the bounded production fixture canary after isolated tests;
it does not authorize bypassing authentication or widening the rollout blindly.
Compare
real S3 fixture bytes/MIME/status for modern/WebP/legacy Accept, missing variants
and first/repeated requests. Rehearse exact config rollback. Then activate only
the canary on primary and mirror; repeat pinned-TLS origin, public DNS, Moscow
and Novosibirsk comparisons, alternating formats through the same URL/cache.

Rollback removes only the new include(s) and restores their recorded previous
files, runs `nginx -t`, gracefully reloads and repeats the same fixture checks.
No cache purge-all, Redis flush, source deletion or broad media processing is
part of activation or rollback.

## Before broadening beyond the canary

- Confirm both actual regional cache layers honor Vary or use representation
  keys; otherwise fix that owning layer first.
- Measure cold/warm timings and S3 request amplification. This bounded canary
  adds up to two sidecar probes per cold original request. It intentionally adds
  no permanent image cache on the application server; do not widen it blindly.
- Select a verified-availability index or bounded cache strategy if missing
  variants add unacceptable latency/load. Do not infer existence from filenames.
- Check original source hashes and dimensions/visual quality on real deck/text,
  alpha and photographic samples; do not use padded synthetic size reductions
  as advertised savings.
- Only then expand to new uploads and separately inventory older images.

## First production attempts and corrected boundary

The first immediate post-reload request reached an old worker (original MIME,
year-long cache header). Automatic disable succeeded. Subsequent activation
waited for an actual new Vary/300-second response before checking the matrix.

The next attempt correctly delivered the selected origin formats and multiple
regional responses, but one mirror WebP request fell back to JPEG. The image
remained available and the canary was disabled. The origin recorded S3 TLS
verification error19; three independent SNI/CA handshakes verified successfully.
Session reuse from the legacy unverified implicit S3 upstream is a suspected
cause, not a conclusively traced handshake. The corrected candidate uses a
dedicated named verified upstream to isolate its peer/session state while
retaining certificate verification and the same fixed S3 hostname.

No article, image source, object or regional configuration changed. Both failed
attempts exercised the restore path; their evidence and retained inactive
configuration are preserved under the named production backup directory.

## Completed production canary, 2026-09-10

Runtime configuration revision: `e0a8833` (PR69). The worker hotfix PR64 was
merged as `a1eb727`; its main Quality and staging deployment passed. No full
WordPress production deployment was performed during this phase.

The TLS mechanism was independently reproduced with a disposable CA and real
loopback Nginx: unverified legacy request200, verified shared upstream502 with
exact certificate error19, dedicated verified upstream200 twice. This is now a
durable `make nginx-media-test` regression using the actual source proxy/maps.

| Final v2 operation | Real HTTP checks | Result |
| --- | ---: | --- |
| Enable verified upstream | 84 | Expected bytes, MIME, Vary and five-minute TTL |
| Disable / rollback | 12 | Original formats and exact original fixture bytes |
| Re-enable same artifacts | 84 | Expected formats and bytes on all seven routes |

Each enabled matrix covered PNG/JPEG, modern/WebP/legacy Accept, first/repeat,
origin, public DNS, mirror DNS and both regional nodes for both hosts. Direct
edge responses included verified cache HITs without changing representation.
Queries were unique per operation/node and retained by the configured cache
key; this avoids confusing an old original-only cache entry with new behavior.
Existing no-query image cache entries were not purged. No TLS error recurred
during the two final matrices; this is a bounded observation, not a lifetime
availability guarantee.

The synthetic696px PNG was4762bytes original /842WebP; JPEG14623original /
4074WebP /961AVIF. These prove delivery, not representative compression savings
or an authenticated editor/page-speed improvement.

Final configuration files live under `/etc/nginx/hs-media-negotiation/`; only
two new autoload includes were added. All29 pre-existing production resource
files and allthree existing vhost files matched their before-hashes afterward.
Nginx, PHP84 and the S3 timer remained active. No database, image, S3 object,
article, credential, regional configuration or existing WordPress runtime file
was edited.

- Current exact artifacts and reversible operation state:
  `/var/backups/hs-manacost-deploy/20260910-media-negotiation.3oJvCT`.
- Superseded inactive artifacts remain recoverable in
  `/var/backups/hs-manacost-deploy/20260910-media-negotiation.KauCFD/inactive-v1`.
- HTTP evidence: `/tmp/hs-media-negotiation-live.rvAQoC`.
- Executable rollback: run the committed `deploy-canary.sh disable` with the
  current backup path, wait for an actual original-only response, then verify.
  `systemctl reload` alone is not proof that new workers are answering yet.

Current activation is still **only the four synthetic prefixes**, not all new
uploads or the whole library. Broadening requires the availability/cache work
listed above, real-content quality sampling, public cache Range/If-Range checks,
and an upload/editor benchmark. Persisted local cleanup, independent restore,
old-media backfill and the50MiB authenticated HTTP/editor flow remain separate
unfinished plan items. No production optimization of Koloda was included here.
