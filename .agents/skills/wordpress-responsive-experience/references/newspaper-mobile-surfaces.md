# Newspaper mobile surfaces

Identify ownership before editing: tagDiv Composer content, Cloud Template header/footer/single/category templates, Website Manager theme settings, first-party MU plugins, and any child theme. Inspect the rendered DOM and final computed CSS because Newspaper can emit template-specific and responsive rules.

Preferred ownership order:

1. Existing Composer or Cloud Template control when it expresses the design safely.
2. Scoped first-party MU plugin for a project behavior or stylesheet already owned there.
3. Child theme for theme-level presentation when the project introduces one.

Never patch the Newspaper parent theme, tagDiv vendor packages, generated CSS, minified bundles, cache artifacts, or uploads. Never rely on a hidden desktop duplicate plus a separate mobile duplicate unless semantics, IDs, ads, analytics, accessibility, cache variation, and maintenance cost have been explicitly reviewed.
