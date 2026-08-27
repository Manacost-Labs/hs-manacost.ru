# Modules

Official documentation: [API – Modules – Introduction](https://forum.tagdiv.com/api-modules-introduction/).

Modules render individual post cards and are reused by loops, blocks, archives, and templates. A small module change can therefore alter many pages.

## Rules

- Find `used_on_blocks`, loop use, and Cloud Template use before editing a module.
- Prefer a new Manacost module or a supported child-theme override over changing a shared vendor module.
- Preserve the expected module class contract, column behavior, image sizing, excerpt controls, and lazy-loading semantics.
- Use WordPress image functions and responsive attributes; do not hardcode upload URLs.
- Escape titles, links, attributes, excerpts, and metadata for their output context.
- Keep queries outside per-item rendering to avoid N+1 behavior.

## Verification matrix

- Every block that consumes the module.
- Homepage and category loops at desktop and mobile sizes.
- Missing featured image, long Cyrillic title, empty excerpt, sponsored content, and restricted content.
- Image aspect ratio, `srcset`, lazy loading, link target, author/date/views, and hover/focus states.
