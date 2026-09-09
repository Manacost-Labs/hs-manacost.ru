# Reader interface ownership

Scope: the independent Reader cabinet, its public profiles and pilot discussion
on test.hs-manacost.ru. No Newspaper templates, WordPress identities, provider
profiles, publication policy or backend contracts are changed by this UI slice.

## Design contract

The owner asked for modern, aligned forms that remain inexpensive to change.
This refines the previously selected Manacost navy/gold direction, not a new
site theme. Existing Cyrillic Roboto/Roboto Condensed and class emblems remain.
The signature is reader identity and the real class crest; no decorative
dashboard statistics, fake activity, new fonts or framework are introduced.

- `ui.css`: sole owner of Reader palette, control radius, font families,
  buttons, fields, labels, helper text, focus, disabled and reduced-motion states.
- `reader.css`: account container, overview, editor, photo tile and account menu.
- `comments.css`: discussion header/identity/replies/composer and public profile.
- PHP renders cache-safe semantic shells. Existing small JS controllers own
  state and safe DOM updates; CSS classes are presentation, data attributes are
  behavior. Neither layer consumes WordPress users or native comments.

All three surfaces opt in through `mc-reader-ui`. Both asset loaders enqueue
`ui.css` as an explicit dependency; this is essential for anonymous/cache views.
Do not append a second override layer or copy shared button/field declarations
into page styles. Modify the primitive, or add one named variant for a real need.
No page-wide reset or global overflow clipping is permitted.

## Interaction states

The account overview and editor are mutually exclusive. Closing the editor
preserves the text draft and returns keyboard focus; it does not imply saving.
Text changes save explicitly. Photo selection saves independently and has an
immediate local preview next to its control. Both preview instances are updated
from one state and cleared on expiry/logout/account switch. Upload failures
must not leave an unsaved image looking like a successful server update.

Keep loading, failure, conflict, refresh, retry, saved, image missing, disabled
and expired states visible. Preserve same-origin CSRF, version guards, uncertain
write reconciliation and object-URL cleanup. Public author titles are based only
on the verified paid flag; public-data consent/export/erasure remain available.

## Verification and release

Run `make check`, `make code-quality`, `make reader-browser-test`, and the
project visual/integration and security gates. Browser fixtures load actual
Newspaper CSS and verify 320/390/560/768/1024/1440 widths, enlarged text, zoom
reflow, keyboard focus, native Account disclosure, aligned fields, avatar
preview/upload/delete, long Cyrillic, replies, retry and private-state races.

Synthetic fixtures are not evidence of a user's authenticated live upload.
For the reported upload error, deployed/source decoder hashes matched and
synthetic JPEG/PNG/WebP passed decoder validation under the deployed service's
OS account (not an authenticated Reader account). Perimeter-authenticated
requests without a Reader session reached both regional BFF routes at 8 KiB,
128 KiB and 2 MiB (expected JSON 401). The actual failing file/status is still
needed; this release does not claim to fix an un-reproduced server upload error.

Candidate and live evidence are reported separately. Rollback is prior UI source
840b30a; do not roll back the Reader database, sessions or BFF for a CSS change.

Profile: wordpress. Skills: project baseline plus frontend-design, debugging,
responsive/typography/accessibility/privacy and UI ownership. Project-mandated
surface skills exceed the generic skill-count budget; they apply only to these
Reader surfaces. Independent HIGH-risk correctness review gates activation.
