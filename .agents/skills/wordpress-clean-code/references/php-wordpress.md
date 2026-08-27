# First-party WordPress implementation rules

## Boundaries

- Read explicit request keys after `wp_unslash()`; validate type and allowed values before sanitizing.
- Check the capability for the resource/action and verify the nonce on every browser-originated write.
- Register every REST route with an explicit `permission_callback`; use public access only for intentionally public data.
- Use `$wpdb->prepare()` for variable SQL and bound/paginated queries for lists.
- Escape at output time with the context-specific function: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`, or JSON helpers.

## Structure

- Keep bootstrap files declarative: constants, includes, and hook registration.
- Keep callbacks thin. Put parsing, policy, storage, and rendering in separately testable units.
- Prefer a named value object or documented array shape over an undocumented associative array crossing several functions.
- Prefer early returns over deep nesting; split functions when they coordinate unrelated concerns.
- Use WordPress APIs and existing project helpers before introducing a dependency or a new abstraction.

## Performance

- Attach code to the narrowest hook and screen; avoid unconditional admin/frontend assets.
- Bound queries, avoid N+1 access, disable found rows when totals are unused, and request IDs when only IDs are needed.
- Cache only public or correctly user-scoped data with an explicit TTL and invalidation owner.
- Never add uncached remote HTTP calls to page rendering; set a timeout and safe fallback.

## Compatibility

- Support PHP 8.2-8.4 and the committed WordPress 6.9.7 runtime.
- Feature-detect optional plugin/theme APIs and fail gracefully when the dependency is absent.
- Preserve Classic Editor, Newspaper/tagDiv, S3 media, view counters, canonical hosts, cache purge, and proxy behavior when a change crosses those contracts.
