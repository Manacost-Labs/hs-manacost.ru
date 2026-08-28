# Profiling workflow

## Reproduce

Define one screen and one action. Record the role, row count, relevant filters, cache state, viewport and network profile. Use a disposable staging record for writes.

Capture at least five runs after one warm-up run. Keep cold and warm groups separate. Use consistent browser and WordPress versions.

## Browser evidence

- Record navigation timing, request waterfall, transferred bytes, long tasks, console errors and the moment the primary control becomes usable.
- Inspect duplicate REST/AJAX requests, polling, redirects, blocking assets and large localized payloads.
- Scope traces to the measured journey and redact cookies, authorization headers, nonces, query values and content.
- Do not commit Playwright storage state, HAR files with credentials or unrestricted DevTools profiles.

## Backend evidence

- Use `wp-performance` for WP-CLI profile/doctor and headless Query Monitor guidance.
- Prefer staging for Query Monitor, `SAVEQUERIES`, Xdebug, XHProf or APM instrumentation.
- Attribute server time to bootstrap stage, hooks/callbacks, SQL, options, object cache, remote HTTP and cron.
- Record query count and peak memory with the same request context as the browser sample.

## Noise control

- Pause unrelated staging jobs when safe; record any unavoidable cron/queue activity.
- Do not discard slow samples without a documented measurement error.
- Compare medians and p95, not the best single request.
- Confirm that caching did not serve stale or unauthorized content.

## Production safety

Production inspection is read-only by default: bounded timing requests, existing metrics and protected logs. Do not generate concurrency, install profilers, enable query recording, expose `Server-Timing` to untrusted users, or flush caches without explicit authorization and rollback.
