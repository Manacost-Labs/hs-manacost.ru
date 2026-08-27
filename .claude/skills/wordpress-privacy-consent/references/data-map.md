# Personal-data map

Record one row per field or derived identifier:

| Field | Purpose | Required | Source | Storage | Recipient | Retention | Access role | Export | Deletion |
|---|---|---:|---|---|---|---|---|---|---|

Include names, Telegram handles, form answers, comments, email, IP/rate-limit keys, cookies, user IDs, attachment metadata, logs and backup copies. Record hashed or pseudonymous identifiers too; hashing does not automatically make data anonymous.

For each change, identify the WordPress option/postmeta/table, REST/AJAX endpoint, cron/queue hook, webhook and external service. Update `config/wordpress-contracts.json` when a code contract changes.
