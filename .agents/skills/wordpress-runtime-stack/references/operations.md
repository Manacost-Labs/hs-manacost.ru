# Safe runtime operations

## Read-only discovery first

Run WP-CLI as the configured site user and with the exact staging/production path from project operations documentation. Useful read-only commands include:

```bash
wp core version
wp plugin list --status=active --fields=name,status,version,update
wp theme list --status=active --fields=name,status,version
wp redis status
wp help redis
```

Do not run commands that display all options/constants or configuration values. Do not copy `.env`, `wp-config.php` or credential-bearing command history into diagnostics.

## Plugin update procedure

1. Confirm the plugin is active and identify whether its source is WordPress.org, commercial or project-owned.
2. Read the vendor changelog and compatibility requirements from the official source.
3. Record current version and exact rollback artifact without committing licenses or credentials.
4. Update one plugin or tightly coupled family in source/staging; update lock/manifest metadata consistently.
5. Run code/security checks and the plugin-owned scenarios from `testing.md`.
6. Deploy to staging and compare cold/warm, editor/admin and public behavior.
7. Promote the exact tested commit. Roll back to the recorded version if acceptance checks fail.

Do not use `wp plugin update --all`, deactivate-all, reset commands or production-first experiments.

## Configuration changes

Prefer committed filters/MU-plugin integration when behavior belongs to code. Runtime settings that intentionally stay in the database still require an exported/redacted before-state, staging rehearsal, precise change record and rollback instruction.

For Cloudflare, nginx and regional proxy changes, follow the repository infrastructure workflow and validate configuration before reload. For Wordfence optimized firewall or Redis drop-in changes, treat the bootstrap/drop-in lifecycle as a separate maintenance operation.

## Incident rollback

A rollback must name the exact changed file/commit, plugin version or setting and the health checks that decide whether to apply it. Cache purge alone is not rollback: restore the source/config first, then invalidate only URLs needed to expose the restored result.
