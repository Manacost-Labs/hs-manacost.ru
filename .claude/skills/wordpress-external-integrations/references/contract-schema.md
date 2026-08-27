# Integration contract schema

Store redacted contracts as JSON outside secret storage. Required fields:

- `name`, `owner`, `purpose`, `direction` (`inbound`, `outbound`, or `bidirectional`)
- `endpoints`: logical names/hosts without credentials
- `authentication`: method and secret variable names only
- `data_classes`: categories such as public content, operational metadata or personal data
- `timeout_seconds`: positive connect/read budget
- `retry`: maximum attempts, retryable conditions and backoff
- `idempotency`: key/source or explicit `not_supported` with compensation
- `rate_limit`: expected limit and 429 behavior
- `observability`: metric, structured outcome and redacted correlation identifier
- `degradation`: user-visible fallback and data-preservation behavior
- `disable_switch`: reversible configuration flag and owner

Do not include sample secret values, signed URLs, tokens, request payloads containing personal data, or production identifiers.
