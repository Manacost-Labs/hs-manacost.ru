# Public navigation and image delivery

The shared navigation stylesheet opts public documents into a 160 ms native
cross-document fade. Links still perform ordinary document navigations; browser
history, scroll restoration and server rendering retain their owners. Browsers
without this API keep normal navigation. Reduced-motion preferences disable
the fade. No speculative article rendering or analytics gating was introduced.

Boosty creatives retain their original files and dimensions. The first-party
renderer offers 400, 800 and original-width WebP files, with native lazy loading
and `sizes="auto"` so the browser selects a rendition from the rendered width.
`data-no-lazy` prevents WP Rocket from introducing a second lazy loader. The
fallback sizes support browsers without automatic image sizing.

## Selected background maintenance, 2026-10-12

The existing background `2026/09/temnaya-versiya-v2.png` is a fully opaque
2400×1500 RGBA PNG. Its original SHA256 is
`ecbe045a9fefe38df780cc58f627b2cf4b71a6928948da0c5aa06eec183452e2`.
Do not apply this maintenance profile to another file or a changed original.

The installed `HS_Local_Image_Worker` can rebuild this one image with the
existing opaque-PNG profile:

```php
$worker->process($working_copy_png, array('has_alpha' => false));
```

Use a temporary working copy, validate the known original hash, 2400×1500
dimensions and full opacity, then publish only the WebP candidate through
`HS_Local_Image_Candidate_Publisher::publish`. Never replace the PNG. Test in
staging first and retain the previous sidecar in a private backup. This profile
produced a 179,258-byte WebP, SHA256
`dbb69dab33b50f9124ee3bca50454b1e97bfe81694ab991b67a2446fd3172e2c`.
The previously delivered WebP was 1,111,292 bytes.

The normal S3 worker uses `--ignore-existing`; it cannot replace an existing
sidecar. An authorized maintenance run must back up and copy the exact one
sidecar to the primary and independent backup buckets, verify both restored
hashes, and preserve all original and unrelated objects. Credentials stay in
the existing private configuration and never enter commands or output.

The public `option_td_011` filter adds `mc_background=opaque-20261012` only to
this image's delivery URL on project hosts. It leaves stored options, other
backgrounds, other settings, other themes and wp-admin unchanged. The new URL
avoids an old immutable browser/edge response. Release cache invalidation must
refresh the affected public HTML; verify both Accept variants, primary, mirror,
origin, Moscow and Novosibirsk. Old browser URLs remain valid.

Rollback: promote the previously staged production SHA for code. Restore the
precise previous WebP from the private backup for media, verify both S3 copies,
then invalidate the affected asset URL. The original PNG stays available.

## Measurement

Compare home, `/hearthstone-top-decks/` and a free article with five samples per
viewport before/after. Record network and CPU emulation, browser version,
document cache state, navigation order, TTFB, LCP, layout shifts, transferred
bytes and errors. Block synthetic analytics/view mutations while allowing
read-only application requests; state this limitation with lab results. Do not
present these lab samples as real-user INP or a production CWV percentile.
