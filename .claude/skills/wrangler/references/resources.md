# Wrangler managed resources

Use this guide when a command creates, mutates, migrates or deletes a Cloudflare resource.

## Common contract

1. Determine the exact account, environment, resource name and binding owner.
2. List or inspect before mutation; do not guess IDs.
3. Decide whether the command is local or remote and make that choice explicit.
4. Capture backup/export or recreation steps when the service supports them.
5. Use a bounded test object/row/message for verification.
6. Update configuration and generated types only after the resource identity is confirmed.
7. Verify through the consuming Worker, not only the CLI exit code.

## Service-specific cautions

- KV is eventually consistent; do not use it as a strongly consistent lock or counter.
- R2 object deletion and overwrite can be irreversible without versioning or a separate backup.
- D1 migrations must be reviewed, applied to staging first and paired with a data rollback or forward-fix plan.
- Queue consumers require idempotency, bounded retries and dead-letter handling.
- Vectorize dimensions and metrics are part of the data contract and cannot be guessed.
- Hyperdrive credentials are secrets; do not put connection strings in configuration or shell arguments.
- Workers AI models, Containers, Workflows, Pipelines and Secrets Store are version-sensitive; retrieve their current Wrangler subcommands before use.

## Sources

- [Wrangler commands](https://developers.cloudflare.com/workers/wrangler/commands/)
- [Workers bindings](https://developers.cloudflare.com/workers/runtime-apis/bindings/)
- [Cloudflare storage products](https://developers.cloudflare.com/products/storage/)
