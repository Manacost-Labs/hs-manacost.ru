# Measurement contracts

## Plausible

- One tracker element per rendered page.
- One configured reporting domain: `hs-manacost.ru`.
- One pageview attempt per real navigation. Client-side custom events must not recreate a pageview.
- Mirror traffic may use the same reporting property, but must retain its actual page URL so mirror usage can be segmented without a second beacon.
- Staging returns an inert analytics script and does not send production events.
- Article event properties must remain bounded, sanitized, and free of secrets or personal data.

Use browser request interception to count attempts without delivering events. Assert URL, method, domain, event name and payload shape; abort the request before it reaches Plausible.

## Newspaper views

The authoritative stored counter is `post_views_count`. Recent-window metadata is derived behavior and must remain consistent with the accepted increment.

Validate on staging with a disposable post:

1. Read the authoritative counter through WP-CLI.
2. Capture all requests matching `td_ajax_update_post_views`, `td_ajax_update_views`, `td_ajax_get_views`, and `manacost_monitoring_hit`.
3. Perform exactly one navigation/action.
4. Require only the documented write action to increment; read-only actions must not mutate.
5. Repeat a retry/reload scenario and verify deduplication expectations.
6. Test `.com` routing separately and ensure a single action cannot trigger an additional `.ru` beacon.
7. Delete the disposable fixture and record the result.

Never execute this mutation test on a real production article.
