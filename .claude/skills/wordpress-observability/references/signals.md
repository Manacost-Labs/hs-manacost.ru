# Signals

| Layer | Minimum signal | Useful warning |
|---|---|---|
| DNS/TLS | resolution and handshake by public region | expiry, SERVFAIL, inconsistent answers |
| Edge/proxy | status, TTFB, cache marker, content identity | one region diverges from origin |
| WordPress/PHP | response errors, PHP-FPM queue/saturation | rising 5xx or worker exhaustion |
| MariaDB | connectivity, slow query/lock trend | sustained latency or disk pressure |
| Cache | cold/warm timing and correctness | high latency or stale/private response |
| Cron/queue | due/failed action age and count | job exceeds its expected interval |
| Media | missing/wrong MIME, optimizer failures, S3 freshness | source/sidecar/object mismatch |
| Backup | last successful copy and restore drill | backup without recent verified restore |
| Capacity | disk bytes, inodes, memory and load trend | forecasted exhaustion before response time |

Use percentiles/trends for latency where possible. Averages hide user-visible tails. Keep label cardinality bounded; do not use full URLs, post titles, user IDs or IP addresses as metric labels.
