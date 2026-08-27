# Editor compatibility guide

## Required coexistence

- Classic Editor determines the primary editing shell; Advanced Editor Tools configures TinyMCE.
- `hs-editor-workspace` changes layout and combines HS Tooltip panels only for its current rollout. A global CSS selector or meta-box removal can affect other users.
- AIOSEO and the project AIOSEO add-on both participate in metadata UI/save. Trace ownership before hiding or replacing fields.
- Inline deck, separator and spoiler integrations can register TinyMCE controls at different priorities. Registration must remain deterministic and duplicate-safe.
- Newspaper/tagDiv may read article content, metadata and featured media outside the standard single template. Use `newspaper-tagdiv` for coupled changes.
- WP Rocket and Perfmatters can delay frontend scripts used by shortcodes. Preserve documented exclusions when changing handles or URLs.
- Admin load trimming is performance-sensitive. Confirm that a missing editor dependency was not intentionally dequeued before adding another copy.

## Compatibility matrix

For an affected feature, test at least:

| Dimension | Cases |
|---|---|
| Role | Administrator; representative editor/author |
| Record | New post; existing draft; published article |
| Mode | Classic/TinyMCE; Gutenberg where supported |
| Viewport | 320, 768, 1024 and 1440 CSS px |
| Content | Empty; long article; legacy shortcodes; media-heavy article |
| Network | Normal; slow upload; failed AJAX/REST response |
| Lifecycle | Autosave; manual save; preview; revision restore; publish/update |

If the project intentionally supports only Classic Editor for a path, document that boundary and make Gutenberg fail safely rather than partially registering a broken UI.
