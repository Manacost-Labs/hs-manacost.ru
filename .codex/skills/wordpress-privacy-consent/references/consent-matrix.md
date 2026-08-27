# Consent and verification matrix

| Surface | Default | Required verification |
|---|---|---|
| Essential session/security cookie | allowed when necessary | purpose, secure attributes, lifetime, no marketing reuse |
| Plausible | production policy only | one request/page, staging disabled, no accidental personal properties |
| Third-party embed | blocked or privacy-preserving until allowed | clear provider/purpose, refusal path, no pre-consent request |
| Editorial/contact/job form | explicit submission action | notice at collection, minimal fields, server validation, retention/deletion |
| Telegram admin alert | minimum metadata | authenticated admin link, no full private payload |
| Comments | explicit submission action | moderation, public-field clarity, deletion/export route |
| Anti-spam/rate limiting | necessary security | bounded identifiers/lifetime, legitimate retry and cross-vacancy behavior |

Test browser requests and cookie storage, not only the visible banner. Capture redacted request names, domains and purposes; never save authentication state in Git.
