# Manacost content lightbox

The site-wide image viewer is owned by the first-party MU-plugin
`wordpress/mu-plugins/hs-manacost-lightbox.php`. It replaces the Newspaper
`tdModalPostImages` frontend handler without modifying the Newspaper theme or
tagDiv plugins.

## Coverage

- Singular public posts, pages and custom post types only; archives and
  administration do not load it.
- Article/page content, WordPress galleries, tagDiv smart lists and Reader
  comment attachments.
- Linked full-size JPEG, PNG, WebP, AVIF and GIF files, plus explicitly marked
  image endpoints such as Reader attachments.
- Bare content images use their large/original/current source.

Advertisements, site logos, avatars, controls, small external icons and nodes
marked with `data-no-lightbox` or `.no-lightbox` are not intercepted. Modified
clicks keep the browser's native open-in-new-tab behavior.

## Interaction and accessibility

The dialog is created only after the first activation. It uses the native
`dialog` element, has named controls, moves focus to Close, restores focus to
the triggering image, closes with Escape or the backdrop, supports arrow keys
and swipe navigation, exposes a counter and keeps the original file link.
Controls remain at least 44 by 44 CSS pixels and the image is contained within
the dynamic viewport at the project viewport/zoom matrix.

## Performance and compatibility

The viewer has no library or jQuery dependency. JavaScript is deferred and the
dialog DOM, adjacent image preload and image decoding only start after user
activation. Newspaper's dedicated 8,892-byte `tdModalPostImages.js` is removed
on covered requests. The complete unminified first-party payload is 19,579
bytes (5,500 bytes with gzip in the release measurement). The CSS/JS remain
minifiable, but are excluded from delayed-JS and remove-unused-CSS transforms
because those optimizations would otherwise break the first click or remove
runtime-only dialog selectors.

Asset query versions are source hashes stored in the MU-plugin, so WP Rocket,
Perfmatters and proxy caches receive a new URL only when the asset changes.

## Release and rollback

Run `make lightbox-test`, `make lightbox-browser-test`, `make check`,
`make code-quality`, `make integration`, `make visual`, the staged security
scan and strict Newspaper audit. Deploy the merged exact SHA to staging first.
Rollback is the previous source SHA; no database, media or option migration is
involved.
