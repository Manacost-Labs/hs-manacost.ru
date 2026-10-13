# Media picker month cache

`hs-admin-media-months-cache.php` avoids the repeated `wp_enqueue_media()` attachment-month aggregation. Five deployed staging profiles before this change showed about 28 ms in `wp_enqueue_media()` on the media, editor and settings screens. This is a specific database saving, not a promise that every administration action is instantaneous.

Only nonempty raw year/month string pairs are shared. Core still performs the cold SQL query and creates labels for the current locale. Permissions, attachment results, S3 paths, images, nonces and editor settings are not cached. The list-table month dropdown has different SQL and is deliberately outside this cache.

The cache requires the existing persistent object cache, an administration request, `upload_files`, the native `wpdb` driver, no `db.php` drop-in and no SQL filters except core's placeholder unescaper. Existing month providers and equal-priority filter collisions retain core behavior. Empty, invalid, expired or future-dated payloads also fall back to core. Two fixed keys (`epoch`, `payload`) live in a versioned group derived from the configured database and current table prefix. Multisite switches recompute that group.

Cryptographically random generation tokens do not depend on WordPress/plugin PRNG seeding. Attachment creation, editing, deletion and conversion out of the attachment type invalidate the generation before later callbacks. A late reader writes its original generation and pre-query timestamp, so its result cannot become a hit after successful invalidation. Timestamp freshness is strictly less than 600 seconds. A caller-owned database transaction can also allow a concurrent rebuild to observe pre-commit data; invalidate again after committing such transactions. Redis outages can leave a persisted pre-mutation result until that freshness bound expires; application-level successful cache writes do not prove Redis persistence. Do not stop shared Redis merely to test this feature.

## Verification

The isolated native-core fixture compares serialized cold and warm month lists, verifies one fewer query on warm renders and exercises date edits, deletion and type conversion using disposable records without media files. The unit harness also exercises late writers, provider overrides, roles, missing persistence, separate table prefixes, initialization/write failure and expired/future timestamps. Browser integration, visual checks and admin performance gates remain required; staging needs five comparable samples before/after with the same role, data and cache state.

## Targeted invalidation and rollback

Normal WordPress attachment APIs invalidate automatically. Direct SQL imports, `set_post_type()` and database restores must explicitly invalidate this cache after successful changes:

```php
HS_Admin_Media_Months_Cache::invalidate();
```

Run this in each restored site's WordPress context. This updates/deletes only the two cache keys; never flush all Redis. Before removing the plugin during rollback, invoke its invalidation method, then deploy the previous staging-verified commit through the normal release workflow. If the plugin is already absent, delete only `epoch` and `payload` in group `hs_media_months_` plus SHA256 of `"v1" . DB_NAME . "\0" . $wpdb->prefix`. Both keys must be discarded after a restored database is installed.

Main and mirror share entries only when their WordPress object-cache namespaces also match. Verify that configuration and cross-host visibility before production promotion; the database-derived group alone does not prove it. Source images, fonts and production data are unchanged by this code release.
