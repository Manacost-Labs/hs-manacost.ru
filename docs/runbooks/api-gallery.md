# Native API galleries and reader ratings

`hs-api-gallery.php` is a project-owned MU plugin using the shared Koloda client
and public WordPress hooks. No Newspaper/tagDiv vendor code is changed.

## Editor workflow

In Classic Editor click **Создать галерею из API**, select a library/format,
search by name or ID, and choose static image variants (up to 40). Optionally
enable reader ratings, then click **Настроить галерею**. After importing each
image, the native gallery modal opens for captions, order, columns, size and
links. Confirm it to insert the normal `[gallery ids="…"]` shortcode.

Clicking a card image toggles selection. The separate selected list includes
thumbnails, individual removal and **Очистить**; filters and the next-step action
remain visible while browsing. Each new creation session starts with no selected
cards, including after cancelling native gallery settings. Library/format and
the ratings preference persist; search is cleared. Closing during import stops
future requests and cannot reopen stale native settings in a new session.
On short screens (up to 700 px high), the dialog itself also scrolls so the
catalog retains a usable height and all controls remain reachable.

The native gallery pencil reopens the same settings. `hs_ratings="1"` preserves
the ratings checkbox. Selecting TagDiv Slide Gallery disables ratings; enabling
ratings selects the ordinary gallery. The current scope is Classic Editor
gallery shortcodes; Gutenberg gallery blocks and TagDiv slider ratings are not
supported. Unrated galleries keep normal rendering and load no rating assets.

The 14 public image libraries advertised by [the provider](https://api.kolodahearthstone.com/api/v1)
are available, with the static variants each object actually exposes. Animated
video/sound assets are excluded. On 2026-10-08 the provider's `q` parameter
returned HTTP 500 and `search` was ignored. The picker searches normalized names
and IDs while fetching paginated libraries, showing progress and retaining
selection on failures. Article save, autosave, revisions and frontend rendering
perform no provider calls.

## Frozen media

Imports require `upload_files`, `edit_post` for the destination and an action
nonce. The server resolves library/object/variant to an image; arbitrary browser
URLs are never accepted. Allowed HTTPS hosts: `api.kolodahearthstone.com`,
`hearthstone.wiki.gg`, `art.hearthstonejson.com`, `d15f34w2p8l1cc.cloudfront.net`.
Safe streaming downloads allow at most two redirects within that host set,
eight seconds per request, 8 MiB and 24 megapixels. Actual image bytes determine
MIME/dimensions; SVG and HTML are rejected.

WordPress creates unique upload paths, attachments and normal sizes. Original
SHA256 and provenance are saved as attachment metadata. There is no automatic
refresh or replacement. Existing media/S3 processes continue owning optimization
and offload; their configuration is unchanged. Retries reuse the snapshot for
the same article/library/object/variant, serialized by a connection-scoped
MariaDB advisory lock that releases on disconnect. Partial successful imports
remain in the media library and can be reused; closing the picker stops future
requests, while an already executing request can finish. No silent media deletion.

## Voting and privacy

The additive version-1 migration creates `{prefix}hs_gallery_votes` on first
admin use, without altering existing WordPress tables. A unique
`(post_id,attachment_id,voter_key)` index permits one current vote per person,
article and image. Scores 1–5 atomically insert/update; 0 removes the current
person's vote. Writes require a published password-free post/page containing
that image in a rating-enabled gallery, an action nonce and the exact browser
origin. Limit: one write/second per voter/article.

Cached article HTML contains no average, personal vote or nonce. Non-cacheable
AJAX loads all article aggregates with one query and then supplies a fresh nonce.
Logged-in identities are salted HMACs of account IDs. Guest identities are random
signed cookies created only after an explicit vote, HttpOnly/SameSite=Lax and
Secure on HTTPS, with one-year expiry. No IP, fingerprint, name or email is stored
in the vote table; votes and voter identities are never sent to the provider.
Guest cookies are separate on `.ru`
and `.com`; account identities use the same WordPress database. Clearing cookies
allows a new anonymous identity: this is ordinary reader voting, not verified
person polling.

Readers can remove their own score. Account votes support WordPress privacy
export/erasure. Daily cleanup anonymizes up to 1,000 votes older than one year,
clearing identity and original timestamp while preserving averages/counts.
Deleted articles/attachments remove owned vote rows. Backup retention remains
the operator's responsibility.

## Layout and compatibility

The native figure, local image, caption and link remain intact. Compact 18 px
stars sit beneath the caption, with transparent background and inherited fonts.
Pointer targets are 28 × 32 px with 4 px gaps; devices with a coarse pointer keep
44 × 44 px targets. The ordinary article column fits three rated cards when three
columns are selected, with a 160 px desktop minimum; touch layouts retain 224 px.
Rated galleries retain the selected maximum column count, reducing columns when
necessary to fit the star row. CSS is scoped to `.hs-gallery-rated` and
`.hs-gallery-rating`, follows the existing 4 px spacing scale and permits zoom.
Preview renders disabled controls; votes require publication.

TagDiv's priority-10 `post_gallery` handler discards earlier filter output, so
the rating renderer runs at priority 20 and delegates image markup to core.
The site's lightbox still groups native galleries and ignores star buttons.

## Verification, deployment and rollback

Run `python3 -m unittest tests.test_api_gallery`, `make code-quality`,
`make contracts`, `make check`, `make integration`, `make visual` and the staged
secret scan. `ops/integration/api-gallery.sh` refuses an active owned stack,
creates a disposable WP 6.9.7 with full TagDiv, mocks only provider transport,
tests real media/SQL and browser flows, then removes its own stack.
Evidence is in `.artifacts/api-gallery`.

Before production, verify the exact SHA on staging, a fresh restore-verified
database backup, and a real provider import with matching source/original SHA256.
Verify article/media/voting through `.ru`, `.com`, origin and both regional
proxies. Production articles and accounts are not test fixtures.

`HS_API_GALLERY_ENABLED=false` disables the feature in approved runtime config.
`MANACOST_KOLODA_API_ENABLED=false` disables provider reads while retaining local
galleries/votes. Reverting the release preserves media, native gallery shortcodes
and the owned table; core ignores `hs_ratings`. Retiring the feature can clear
the single owned cron event and affected article caches. Never drop the table or
delete attachments automatically during rollback.

`hs_api_gallery_imported` and `hs_api_gallery_vote_saved` expose public article/
attachment IDs; provider outcome/cache age uses `manacost_koloda_api_result`.
Do not log identities, cookies, credentials or request bodies.
