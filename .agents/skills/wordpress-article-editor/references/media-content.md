# Article content and media safety

## Stored content

Before changing the editor, save a fixture containing headings, links, lists, embeds, captions, HTML comments and every affected shortcode. Compare the stored `post_content` before and after a no-op save.

Known custom content paths include inline deck links, spoilers, separators and HS Tooltip content. Inventory the actual shortcode registry and existing articles before renaming an attribute or changing generated markup. Rendering may evolve, but existing stored syntax must continue to work.

Never apply a global conversion between TinyMCE HTML and block markup merely to support a new control. If a migration is explicitly required:

1. Back up the exact records and metadata.
2. Produce a read-only dry-run with IDs and before/after hashes.
3. Migrate a small staging sample.
4. Verify editor reopen, revision restore and frontend output.
5. Provide a deterministic rollback.

## Media and S3

- Upload through WordPress attachment APIs, not a direct anonymous S3 write.
- Preserve attachment ID, URL, MIME type, metadata, alt text, caption, parent, generated image sizes and object-store key.
- Keep unique-filename behavior. A new upload must never overwrite an older object with the same basename.
- Let the local image optimizer and S3 offload integration finish their lifecycle; do not invent a parallel upload pipeline for an editor button.
- Validate both the original and the size actually embedded by the editor.
- Test anonymous delivery through the `.ru` domain and regional proxies, not only an authenticated origin URL.
- Do not remove the local source until object existence, public delivery and restore/backup policy have been verified.

## User-facing behavior

Make uploads show progress, success and actionable failure. Retain the draft and field values when an upload or remote deck import fails. Images need meaningful alt text controls; decorative images should be explicitly marked according to the current WordPress capability rather than receiving meaningless filenames as alt text.
