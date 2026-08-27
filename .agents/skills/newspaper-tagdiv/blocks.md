# Blocks

Official documentation: [API – Blocks – Introduction](https://forum.tagdiv.com/api-blocks-introduction/).

Blocks are Composer shortcodes and module containers. Their registration ID, shortcode base, PHP class, and source file must remain aligned.

## Rules

- Do not change an existing block ID or shortcode base casually; saved page content depends on it.
- Keep query arguments bounded and paginated. Avoid `posts_per_page=-1` and per-card queries.
- Preserve Composer mapping and parameter defaults when extending an existing block.
- Validate shortcode attributes, coerce numeric limits, allowlist sort/filter values, and escape render output.
- Separate query construction, data preparation, and HTML rendering when adding behavior.
- Make AJAX pagination/filter endpoints enforce nonce/capability where appropriate and return consistent JSON.

## Verification

- Existing saved shortcodes render without “component is not set” errors.
- Composer preview and public render match.
- Pagination/load-more/filter state works twice in succession, not only on the first request.
- Empty and error states remain readable.
- Cache keys include every input that changes public output and exclude user-private data.
