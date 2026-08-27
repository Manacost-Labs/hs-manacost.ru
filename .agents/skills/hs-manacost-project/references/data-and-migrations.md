# Data, database and migration safety

## Data that is not source

- WordPress database: posts, revisions, metadata, options, users, roles, comments, counters and plugin settings.
- Media: attachment rows, metadata, local upload lifecycle, generated sizes and S3/Object Storage objects.
- Runtime: caches, Redis data, logs, sessions, WAF state, backups and deployment releases.
- Secrets: configuration credentials, tokens, cookies, private keys, salts and commercial licenses.

Do not add these to Git. Document schemas and deterministic migration code, not production values or personal data.

## Read-only discovery

Use the exact environment path/user from project operations configuration. Prefer bounded WP-CLI commands and counts over dumps. Do not run option/config commands that print all values. Redact emails, Telegram handles, IP-associated records and other personal data from reports unless the user explicitly needs the exact record.

Before a write, identify:

1. Environment and exact table/object/attachment/post IDs.
2. Expected affected row/object count.
3. Source of truth and conflict behavior.
4. Backup and restore command/location without exposing credentials.
5. Dry-run output and acceptance query.
6. Idempotence/retry behavior.
7. Cache and search/index consequences.

## Database migration protocol

- Implement repeatable migrations in source when behavior must be reproduced; do not leave unexplained manual SQL as the only record.
- Use WordPress APIs when they preserve hooks/invariants. Use prepared, bounded SQL only when APIs are unsuitable and document why.
- Back up the exact scope before changing it. For a large migration, process batches and checkpoint progress.
- Preserve revisions and serialized structures. Never use blind SQL replacement inside serialized values.
- Run on staging data first, compare counts/hashes/sample records, then request production authority when required.
- Define rollback before applying. A backup that has never been located or restore-tested is not sufficient evidence.

## Media/S3 protocol

- Treat the attachment record and object key as one mapping.
- Preserve unique-filename behavior so a same-name upload never overwrites an older article image.
- Verify original plus used variants anonymously through intended public routes.
- Before local deletion, verify object existence, size/hash where available, attachment metadata, restore path and representative article rendering.
- Do not bulk delete local uploads, orphan records or S3 objects from filename heuristics alone.

## Staging isolation

Content created on staging stays on staging unless an explicit migration/export is designed. Code deployment does not copy the staging database or uploads into production. Never solve this boundary by pointing staging at the production database.

For production writes, report what changed, exact count, validation evidence, cache invalidation and rollback status.
