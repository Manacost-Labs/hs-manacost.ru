# Wrangler configuration and development

Use this guide for `wrangler.jsonc`, environments, bindings, generated types and local development. Verify fields against the installed Wrangler schema because binding shapes change.

## Configuration review

- Confirm `name`, `main`, `compatibility_date` and any required compatibility flags.
- Prefer `wrangler.jsonc` unless the repository already owns a supported TOML configuration.
- Keep non-secret environment values under `vars`; store secrets with Wrangler or the approved CI provider.
- Define staging and production overrides explicitly. Remember that non-inheritable bindings must be repeated per environment.
- Preserve existing routes, migrations, placement, observability and limits unless the task explicitly changes them.
- Never commit real namespace, database or account identifiers copied from an unrelated environment.

## Bindings and types

After changing bindings, run the project-pinned equivalent of:

```bash
npx wrangler types
npx tsc --noEmit
```

Review the generated diff. A type-generation success does not prove that the bound remote resource exists or belongs to the correct account.

## Local development

- Prefer local simulation for ordinary development.
- Use remote bindings only when the behavior cannot be reproduced locally and the user authorized interaction with that environment.
- Use `.dev.vars` or the repository's ignored local-secret mechanism; confirm it is excluded from Git before adding values.
- Exercise scheduled handlers and queues with documented local triggers rather than invoking production jobs.

## Sources

- [Wrangler configuration](https://developers.cloudflare.com/workers/wrangler/configuration/)
- [Environments](https://developers.cloudflare.com/workers/wrangler/environments/)
- [Type generation](https://developers.cloudflare.com/workers/languages/typescript/#generate-types)
