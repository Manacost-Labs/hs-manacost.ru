# Newspaper typography ownership

Inspect the final cascade across Website Manager, tagDiv Composer, Cloud Templates, first-party MU plugins, a possible child theme, and runtime optimization. The anonymous homepage currently receives a style tagged `manacost-font-trim`; review it explicitly whenever typography changes so editor settings and anonymous rendering cannot drift.

Preferred ownership order:

1. Newspaper setting or Cloud Template for a stable editorial/template concern.
2. Scoped first-party MU plugin for project-owned runtime behavior.
3. Child theme for reusable theme presentation when available.

Never edit the Newspaper parent theme, tagDiv vendor code, generated CSS, cache files, minified output, or uploads. Avoid global `!important`; if an existing runtime override requires a narrow counter-rule, document the cascade and add a computed-style regression check.
