# Migration contract

Define these fields before writing:

| Field | Required content |
|---|---|
| Purpose | User-visible outcome and owning plugin/data model |
| Selector | Exact IDs, post types, keys, date/range or predicate |
| Estimate | Read-only affected count and expected tolerance |
| Transform | Deterministic before → after mapping |
| Invariants | Values/relationships that must not change |
| Idempotence | Result of running the same batch twice |
| Checkpoint | Resume position stored without sensitive payloads |
| Failure | Stop rule and partial-batch behavior |
| Rollback | Reverse transform or exact backup restore procedure |
| Verification | Database assertions plus real WordPress flow |

Prefer a dedicated WP-CLI command in a project-owned plugin/MU-plugin for complex migrations. Accept explicit `--dry-run`, `--limit`, `--after-id` or equivalent boundaries. Emit counts and opaque IDs, not post bodies or personal data.
