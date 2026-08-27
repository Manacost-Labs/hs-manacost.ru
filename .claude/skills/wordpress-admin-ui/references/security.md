# Security and data integrity

## Authorization and request integrity

Check `current_user_can()` with the narrowest object-aware capability both before rendering privileged data and inside every state-changing handler. Hiding a control is not authorization.

Use a purpose-specific nonce for forms, action URLs, AJAX, and REST writes. Verify it before processing. A nonce mitigates CSRF but does not replace capability checks or authentication.

Official references:

- [User roles and capabilities](https://developer.wordpress.org/apis/security/user-roles-and-capabilities/)
- [Nonces](https://developer.wordpress.org/apis/security/nonces/)

## Input and output

- Read only expected request keys and `wp_unslash()` WordPress request data before sanitizing.
- Validate finite choices against a strict safelist before performing any action.
- Sanitize by meaning: key, text, textarea, email, URL, integer, HTML allowlist, or attachment ID.
- Reject structurally invalid input rather than silently converting it into a different action.
- Use `$wpdb->prepare()` for variable SQL and safelist identifiers that cannot be placeholders.
- Escape at output for its context with `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses()`, or JSON encoding.

Official references:

- [Validating data](https://developer.wordpress.org/apis/security/data-validation/)
- [Escaping data](https://developer.wordpress.org/apis/security/escaping/)

## REST and AJAX

Register REST routes on `rest_api_init`, define `permission_callback`, specify methods, and give writable arguments `validate_callback`/`sanitize_callback` or a schema. Return `WP_Error` with useful safe status codes; do not leak queries, paths, stack traces, tokens, or private metadata.

For AJAX, use authenticated hooks unless the action is intentionally public. Verify `check_ajax_referrer()`, check capability, validate every field, and return structured JSON with an appropriate status.

Official reference: [register_rest_route()](https://developer.wordpress.org/reference/functions/register_rest_route/).

## Sensitive and destructive workflows

- Never put secrets, private applicant data, access tokens, or unrestricted object dumps in localized JavaScript or HTML attributes.
- Apply file type, size, extension, MIME, and capability checks to uploads; store through WordPress media/filesystem APIs.
- Make repeated submissions idempotent where network retry could duplicate data.
- Use database transactions or compensating rollback when several writes must succeed together.
- Log identifiers and outcomes needed for support, not request bodies or secrets.
- Prefer trash/archive and documented retention over permanent deletion.
