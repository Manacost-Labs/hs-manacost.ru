# Shared PHP 8.4 OPcache capacity

The `php-fpm84` master serves `hs-manacost.ru` and `kolodahearthstone.ru` pools. Both pool files must use the value in `opcache-capacity.conf`:

- `/opt/php84/etc/php-fpm.d/site.d/hs-manacost.ru.conf`
- `/opt/php84/etc/php-fpm.d/site.d/kolodahearthstone.ru.conf`

On 2026-09-24 the effective shared cache was full at 256 MiB: `cache_full=true`, 24 bytes free, 3029 cached scripts, and increasing misses. Both pools had `php_admin_value[opcache.memory_consumption] = 256`; the host had about 33 GiB available RAM. The target is 512 MiB. Other pool options, including security and request limits, stay as they are.

Before applying, save both exact pool files to a private rollback location and verify that each contains the expected 256 MiB setting exactly once. Replace only that line in both files. Run `/opt/php84/sbin/php-fpm -t --fpm-config /opt/php84/etc/php-fpm.conf` before `systemctl reload php-fpm84`. The reload clears OPcache; warm staging before comparing performance.

Accept only if both pools report an effective 512 MiB cache with `cache_full=false`, PHP-FPM is active with no queue, five matching staging admin samples show no regression, and smoke checks pass for staging, the main and mirror domains, origin, both RU proxies, and Koloda. Do not purge WordPress, Redis, or edge caches for this change.

Rollback: restore the two saved pool files, validate PHP-FPM syntax, reload `php-fpm84`, and repeat the health and smoke checks. Keep the old files until the new cache has warmed and the follow-up measurements pass.
