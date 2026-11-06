# Advertising and cache delivery matrix

## Required dimensions

| Dimension | Cases |
|---|---|
| Host | `.ru`, `.com`, staging when authorized |
| Route | origin, Moscow edge, Novosibirsk edge, normal public DNS |
| Cache | cold diagnostic request, warm repeated request |
| Browser | anonymous/incognito, authenticated editor when the slot differs |
| Device | desktop and mobile viewport/user agent |
| Asset | creative URL, target URL, status, MIME, dimensions |

## Stale banner triage

1. Compare source configuration with rendered origin HTML.
2. Compare referenced creative bytes or validators (`ETag`, `Last-Modified`, length) at origin and each edge.
3. Identify whether stale data belongs to Newspaper/Composer, WordPress object cache, WP Rocket page cache, Cloudflare, or an RU edge.
4. Invalidate only the affected page/asset keys at the first divergent layer.
5. Recheck cold and warm requests through every route.

The current expected top creative is `/wp-content/uploads/2026/07/728x90.jpg`. The known obsolete Playerok creative `/wp-content/uploads/2026/03/heartstone_sajt.png.webp` must not reappear.

## Direct partner header

Playerok uses `/site-media/primary-mark/`; Sirus uses `/site-media/secondary-mark/`.
Both routes fetch only their fixed creative source and retain the original campaign destinations.
The Playerok source above remains the expected artwork, not its public image URL.

Verify loaded, slow, blocked and missing images; CSS image hiding; JavaScript disabled;
keyboard focus, pause, manual next and reduced motion. Automatic rotation requires
two successfully loaded images, pauses while hidden/hovered/focused, and crossfades
only opacity. Failed images retain a readable partner link. Filters hiding the whole
placement can still suppress it; do not add reinsertion loops or blocking gates.
