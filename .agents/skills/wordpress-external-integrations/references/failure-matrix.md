# Failure matrix

| Failure | Expected behavior |
|---|---|
| timeout / DNS / TLS | bounded wait, preserved local action, visible operational failure |
| 401 / 403 | no blind retry, credential/config alert, secret remains redacted |
| 429 | honor bounded `Retry-After`, queue safely, no request storm |
| 5xx | bounded exponential backoff only for safe/idempotent action |
| malformed response | reject schema, retain recoverable state, log redacted reason |
| duplicate inbound delivery | one resulting WordPress action via replay/idempotency key |
| provider disabled | core reading/editing/submission remains usable where contract promises degradation |
| recovery | queued safe work resumes once, without duplicate Telegram messages, uploads or analytics |

For user-facing paths, verify latency both with the provider healthy and deliberately unavailable. For production incidents, disable only the affected integration using its documented switch.
