# Editorial stack map

Use this map to find the owner before changing the article editor.

| Component | Project location | Responsibility | Main risk |
|---|---|---|---|
| Classic Editor | `wordpress/plugins/classic-editor` | Primary classic editing mode and editor selection | Changing mode can alter markup and operator workflow |
| Advanced Editor Tools | `wordpress/plugins/tinymce-advanced` | TinyMCE toolbar and editing capabilities | Button duplication and serialization differences |
| Editor workspace | `wordpress/mu-plugins/hs-editor-workspace.php` | Scoped screen cleanup and HS Tooltip tabs | Rollout currently targets specific users; do not widen silently |
| AIOSEO add-on | `wordpress/mu-plugins/manacost-aioseo-addon.php` | Editorial SEO metadata | Save-order, capability and duplicate-field conflicts |
| AIOSEO | `wordpress/plugins/all-in-one-seo-pack` | SEO panel and metadata | Heavy panel and metadata integrity |
| Tooltip tooling | `wordpress/plugins/hs-tooltip` | Article-specific tooltip controls | Existing metadata and shortcode compatibility |
| Separator quick insert | `wordpress/plugins/separator-placeholder-quick-insert` | Classic and block editor separator insertion | Nonce/REST and dual-editor behavior |
| Inline deck | `wordpress/plugins/hs-manacost-inline-deck` | TinyMCE button, deck import, attachment and shortcode | Remote data, media import and delayed-script exclusions |
| Spoilers | `wordpress/plugins/wp-kolodahearthstone-spoilers` | TinyMCE spoiler button and shortcode | Legacy article rendering |
| Newspaper/tagDiv | `wordpress/themes/Newspaper_new`, `td-composer`, `td-standard-pack` | Article templates and builder integration | Vendor coupling and upgrade safety |
| Admin load trim | `wordpress/mu-plugins/hs-admin-load-trim.php` | Removes unnecessary editor/admin work | Removing a required dependency by mistake |
| Media pipeline | `hs-media-upload-accelerator.php`, `hs-local-image-optimizer`, `hs-manacost-s3-offload`, `manacost-media-unique-filenames.php` | Upload, optimize, uniquely name and offload media | Missing variants, overwrite, or inaccessible object |
| Cache purge | `wordpress/mu-plugins/manacost-cache-purge.php` | Invalidate updated content | Stale preview/frontend or excessive purge |

## Discovery checklist

1. Record `get_current_screen()`, post type and editor mode.
2. Identify roles/capabilities and any user-ID rollout condition.
3. Trace enqueue, meta-box, TinyMCE, REST/AJAX and save hooks.
4. Check whether an owning plugin can be inactive or unavailable.
5. Inspect the saved article and metadata before changing presentation.

Do not assume every author sees the same editor. Verify an administrator and a representative editorial role.
