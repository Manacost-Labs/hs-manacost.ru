# Content checks

## Stored data

- Published status/date/author/category and expected visibility.
- Non-empty title/content, revision availability and featured attachment.
- Referenced attachment IDs and URLs resolve to the intended media.
- Custom post meta and options conform to `config/wordpress-contracts.json`.

## Rendered article

- No unresolved project shortcode or empty critical block.
- Heading order, links, deck codes, embeds and image alt text remain meaningful.
- Images/srcset return decodable expected content with correct MIME and dimensions.
- Article views update only under the intended public rules and survive cache delivery correctly.
- Desktop/mobile layout has no horizontal overflow or hidden essential actions.

## Archives and discovery

- Category/home cards use the intended featured image/title/link.
- Published article appears in expected archives and sitemap.
- Internal links use the primary domain unless an explicit external/mirror link is intended.
- Redirects are one hop and do not form loops.

For bulk audits, output machine-readable findings with stable codes, post ID and URL. Do not include full post bodies.
