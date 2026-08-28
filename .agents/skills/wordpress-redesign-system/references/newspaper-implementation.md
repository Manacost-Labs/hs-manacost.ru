# Newspaper redesign implementation

Choose ownership in this order:

1. Existing Newspaper Composer/Website Manager control for global type, color or responsive settings.
2. Cloud Template for header, footer, single, category, search or other template layout.
3. Existing Manacost MU-plugin or a scoped first-party stylesheet/script for site behavior and design tokens.
4. Public WordPress/tagDiv hook or Theme API registration.
5. Child-theme override for supported PHP/template ownership.

Do not use another page builder alongside tagDiv. Do not patch `wordpress/themes/Newspaper_new`, `td-composer`, `td-standard-pack`, generated CSS or serialized template data directly.

Scope project CSS below a stable Manacost component/root class. Keep specificity shallow, avoid global resets and broad `!important`, give media dimensions/aspect ratios, and load assets only on relevant surfaces. Verify Cyrillic font files, licensing, preload behavior and fallback before adopting a font.

Preserve template IDs and assignments in the rollback record. A redesign slice is not complete until Composer/editor save, preview, revisions, frontend rendering, cache invalidation, mirror rewrite and the affected proxy routes behave correctly.
