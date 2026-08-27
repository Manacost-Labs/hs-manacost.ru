# tagDiv Composer

Official documentation: [tagDiv Composer tutorial](https://forum.tagdiv.com/tagdiv-composer-tutorial/) and [Website Manager](https://forum.tagdiv.com/tagdiv-composer-website-manager/).

## Project rules

- `td-composer` is required for Newspaper; treat its activation and compatible version as a release invariant.
- Do not use Gutenberg, WPBakery, or another page builder to rewrite a page already authored with Composer.
- Prefer Composer rows, columns, elements, device visibility, global colors, and global fonts for editorial layout changes.
- Use code only when Composer cannot express the behavior or when repeated content/query logic needs a tested implementation.
- Do not edit generated Composer markup in the database by hand.

## Before changing Composer code

1. Locate the owning registration or renderer in `wordpress/plugins/td-composer`.
2. Search for the same ID in `td-standard-pack`, the active theme, MU-plugins, and saved template references.
3. Determine whether the change affects editor mode, frontend mode, mobile mode, or all three.
4. Check whether source and minified assets both exist. Change source first and rebuild; if no build pipeline is available, document why paired files must be updated.

## Verification

- Open an existing Composer page and a Cloud Template on staging.
- Enter editor mode, change a harmless field, save, reload, and confirm the change persists.
- Check desktop/tablet/mobile viewports and the public rendered page.
- Confirm no console error, failed AJAX/REST request, duplicate element, or lost shortcode content.
- Confirm cache purge makes the saved layout visible without deleting unrelated cache state.
