# Redesign acceptance matrix

| Surface | Functional evidence | Design evidence |
|---|---|---|
| Header/navigation/search | keyboard and touch journeys, focus return, open/close/search states | hierarchy, long labels, 320 px, zoom, sticky behavior |
| Homepage | real sections, ads, images, links and cache behavior | scanning order, long titles, slow/missing image, CLS |
| Article | stored/rendered content, shortcodes, embeds, views, comments, canonical/schema | reading measure, headings, captions, tables, related content |
| Category/search | pagination/filter/query and empty results | density, result hierarchy, empty/error state, mobile list |
| Editor/admin | save/autosave/revision/preview and least-privileged role | labels, focus, notices, dense desktop and usable phone layout |
| Mirror/staging | `.com` functional noindex and staging fully noindex | no host-specific layout drift or missing assets |

For every affected surface require:

- screenshots at 320, 390, 768, 1024 and 1440 px with expected differences reviewed;
- no horizontal page overflow or white gutter at 200% zoom;
- manual keyboard path plus automated accessibility scan;
- reduced-motion behavior and touch alternatives for drag/hover interactions;
- console/network review, current banner, one intended tracker on production, no staging analytics;
- cold/warm performance comparison with LCP/INP/CLS and image transfer evidence;
- origin, Moscow and Novosibirsk checks after production promotion.
