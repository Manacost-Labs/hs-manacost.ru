# Shared PHP 8.4 OPcache capacity

The `php-fpm84` master serves both WordPress pools. OPcache shared memory is allocated from the global PHP INI before pool settings take effect. The source setting is [`99-opcache-capacity.ini`](99-opcache-capacity.ini), installed as `/opt/php84/etc/php.d/99-hs-opcache-capacity.ini`.

On 2026-09-24 the global limit was 128 MiB while both pools advertised 256 MiB. The cache was full, with about 119 MiB of cached script bytecode, 8 MiB of interned strings, and increasing misses. Raising only the pool values to 512 and 1024 MiB still left the cache full at approximately the same script count. This confirmed that the global allocation was the limiting layer. The host had about 33 GiB available RAM. The target global allocation and both pool settings are 512 MiB. The global interned strings buffer was also full at its default 8 MiB, although both pools requested 32 MiB; set that value globally as well.

Before applying, save the two pool files and any existing target INI file to a private rollback location. Verify the pool setting appears exactly once in each file. Install the INI and replace only the setting named in [`pool-opcache-capacity.conf`](pool-opcache-capacity.conf) in these files:

- `/opt/php84/etc/php-fpm.d/site.d/hs-manacost.ru.conf`
- `/opt/php84/etc/php-fpm.d/site.d/kolodahearthstone.ru.conf`

Check `/opt/php84/sbin/php-fpm -i` for a global 512 MiB value, then run `/opt/php84/sbin/php-fpm -t --fpm-config /opt/php84/etc/php-fpm.conf` before `systemctl reload php-fpm84`. The reload clears OPcache; warm staging before comparing performance.

Accept only if both pools report `cache_full=false`, substantial free shared memory and a 32 MiB interned strings buffer after warming, PHP-FPM is active with no queue, five matching staging admin samples show no regression, and smoke checks pass for staging, the main and mirror domains, origin, both RU proxies, and Koloda. Do not purge WordPress, Redis, or edge caches for this change.

Rollback: restore both saved pool files and remove the installed INI if it did not previously exist, validate PHP-FPM syntax, reload `php-fpm84`, and repeat the health and smoke checks. Keep backups until the new cache has warmed and measurements pass.
