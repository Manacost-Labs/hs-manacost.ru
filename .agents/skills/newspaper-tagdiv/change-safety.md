# Update-safe Newspaper change contract

## Ownership order

1. Use an existing Manacost MU-plugin when the behavior is site-specific.
2. Use a documented WordPress hook or tagDiv Theme API registration when an extension point exists.
3. Use a Cloud Template for layout ownership and keep its template ID/assignment contract explicit.
4. Use a child theme for supported PHP/template overrides that must survive a Newspaper update.
5. Edit the parent theme only with explicit user acceptance, a documented upstream limitation, a minimal patch, and a rebase plan for the next update.

Do not copy the parent theme wholesale. Never patch generated CSS, minified output alone, caches, uploads, or runtime files as source.

## Before changing code

- Record the active Newspaper, tagDiv Composer, Standard Pack, and Cloud Library versions.
- Search the exact hook, `td_api_*` registration, template ID, module ID, block ID, option, and callers in committed source.
- Decide which layer owns the change: MU-plugin, hook/API, Cloud Template, child theme, or exceptional parent patch.
- Capture desktop/mobile visual regression baselines and the functional flow that proves save/render behavior.

## Verification matrix

- Homepage, article, category, search, header/footer, menus, responsive breakpoints, and keyboard navigation when affected.
- tagDiv Composer or editor save, preview, revision/autosave, and persisted content/template assignment.
- Images/S3, ads, article views, canonical/robots, anonymous/authenticated cache behavior, and console/network errors.
- `test.hs-manacost.ru` first; then the exact verified SHA through the release workflow.

## Update and rollback proof

- Reapply or simulate the committed Newspaper version over the integration fixture and confirm the override still loads outside the parent theme.
- Run the strict Newspaper audit, `make check`, `make integration`, and `make visual` for UI/template work.
- Define rollback as a source commit or Cloud Template assignment restore; never rely only on clearing cache.
- Keep the parent-theme baseline unchanged unless the approved exception is the purpose of the change.
