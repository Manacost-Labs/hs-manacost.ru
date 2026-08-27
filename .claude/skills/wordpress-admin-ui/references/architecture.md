# Architecture for WordPress administration screens

## Choose the integration surface

Prefer an existing core screen when the object already maps to posts, terms, users, comments, media, or settings. Add a focused column, meta box, filter, row action, bulk action, or editor sidebar instead of recreating the complete workflow.

For a single configuration page, use a submenu under Settings or Tools. WordPress recommends an existing top-level menu for a single option page. Use a dedicated top-level menu only when the feature is a frequent multi-screen product area.

Use the Settings API for conventional options. It supplies consistent admin markup and standard form handling, while the application still owns validation and authorization decisions.

Official references:

- [Administration Menus](https://developer.wordpress.org/plugins/administration-menus/)
- [Settings API](https://developer.wordpress.org/plugins/settings/settings-api/)
- [Block editor components](https://developer.wordpress.org/block-editor/reference-guides/components/)

## Select rendering complexity

### Server-rendered by default

Use PHP plus small progressive enhancements for forms, settings, reports, filters, pagination, and most CRUD screens. Keep a working submit path when JavaScript fails if the workflow allows it.

### JavaScript island

Mount JavaScript only inside a namespaced root on the owned screen for searchable selectors, media pickers, reorder controls, asynchronous previews, or an interaction-heavy section. Pass boot data through a JSON script or REST preload only after capability checks and output encoding.

### Full application

Use a full client application only for dense workflows such as drag-and-drop planning, multi-step visual builders, or live dashboards. Require:

- an existing maintained build pipeline and lockfile;
- route-level capability checks and REST schemas;
- deep-linkable filters or entity IDs;
- recoverable loading and error states;
- code splitting when the bundle is large;
- a server-rendered permission/error shell rather than an unexplained blank page.

## Separate responsibilities

Keep registration, authorization, validation, persistence, queries, view models, rendering, and assets independently testable. Rendering must receive presentation-ready data and must not perform hidden writes.

Use WordPress APIs before custom storage. For custom tables, version the schema, use `dbDelta()` carefully, provide migrations, and index actual filter/sort columns. Never query an unbounded result set for an admin list.

## Scope assets

Capture the hook suffix returned by `add_menu_page()` or `add_submenu_page()`. In `admin_enqueue_scripts`, return immediately unless it is the owned screen. Version assets from the build manifest or file modification time during development.

Official reference: [admin_enqueue_scripts](https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/).

## Project-specific boundaries

- Put site-specific behavior in a scoped MU-plugin when it must always run.
- Put reusable product behavior in its owning plugin.
- Do not patch Newspaper/tagDiv admin source directly until `newspaper-tagdiv` confirms no supported extension exists.
- Keep production database content, options, generated templates, uploads, secrets, and caches outside Git.
