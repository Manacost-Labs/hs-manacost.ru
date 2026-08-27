# Media pipeline

## Ownership chain

1. WordPress assigns a collision-safe upload path and stores `_wp_attached_file` plus `_wp_attachment_metadata`.
2. WordPress creates registered image sizes.
3. `hs-local-image-optimizer` queues after metadata generation and processes the source plus all sub-sizes.
4. The optimizer writes adjacent sidecar candidates and atomically publishes only valid, smaller WebP/AVIF files.
5. `hs-manacost-s3-offload` copies and verifies `wp-content/uploads` and `uploads-webpc` in OVH Object Storage.
6. Nginx keeps the public original URL and selects AVIF, WebP, legacy `uploads-webpc`, or original based on `Accept` and availability.
7. Page, Cloudflare and regional proxy caches may retain an older selection.

## Local optimizer profiles

| Content | WebP | AVIF | Minimum saving |
|---|---|---|---|
| deck | quality 90 | disabled | 5% |
| transparent UI PNG | lossless | disabled | 5% |
| opaque PNG/text graphic | quality 90 | disabled | 5% |
| editorial photo/art | quality 88 | quantizer 24 | 8%, compared with best baseline |

The source image remains byte-for-byte intact. Candidate dimensions must equal source dimensions. Encoder commands use argv arrays, not shell interpolation.

## Required acceptance matrix

- same filename uploaded twice → distinct WordPress paths and unchanged first SHA256;
- AVIF-capable request → AVIF only when valid/current;
- WebP-capable request → WebP or safe fallback;
- legacy request → original MIME;
- missing sidecar → original remains available;
- missing local original but verified offload → S3 restoration/delivery succeeds;
- deletion of an attachment → only its own metadata and sidecars are removed;
- `.ru` and `.com` show the same intended image while retaining their SEO host policy.
