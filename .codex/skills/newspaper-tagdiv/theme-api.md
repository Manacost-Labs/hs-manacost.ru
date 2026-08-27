# Newspaper Theme API

Official documentation: [The Theme API](https://forum.tagdiv.com/the-theme-api/).

The API exposes `add()`, `update()`, `update_key()`, and `delete()` operations through `td_api_*` classes. In this project, registrations live mainly under `wordpress/plugins/td-composer/legacy/Newspaper/includes/td_config.php`; supporting classes live under the Composer legacy common framework.

## Rules

- Use a unique Manacost-prefixed ID for new elements.
- Match the registered ID, shortcode base, class name, and file path exactly.
- Register only after the required tagDiv classes exist; fail gracefully if a coupled plugin is inactive.
- Prefer `update_key()` for a narrow supported parameter change instead of replacing an entire registration.
- Never delete or rename an existing ID without finding saved Composer/Cloud Template usage first.
- Put site-specific registration code in a dedicated plugin or MU-plugin, not in the vendor config file.

## Dependency search

Search all of these before changing an ID:

```bash
rg -n "the_exact_td_id" wordpress/themes/Newspaper_new wordpress/plugins/td-* wordpress/mu-plugins
```

Also inspect saved content on staging through WP-CLI using a bounded, read-only query. Do not dump the production database into the repository.

## Verification

- Registration succeeds with and without optional tagDiv components.
- Composer displays the element with correct controls.
- Existing saved pages still render.
- Invalid input is rejected or normalized; frontend output is escaped.
- The change does not add an unbounded query or remote request to every render.
