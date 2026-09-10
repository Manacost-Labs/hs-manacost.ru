# Shared origin stability — phase 2

Scope: host monitoring and PHP service recovery only. No WordPress code, data,
authentication, firewall rules, pool sizing, memory caps or production restarts.
These artifacts are not installed by the WordPress staging/promotion workflow;
use a reviewed host-only rollout after offline checks and isolated canaries.

## Findings and decisions (2026-09-10)

- Both PHP84 and PHP81 have `Restart=no`. PHP84 serves both sites; PHP81 still
  handles the HS login override. A failed master currently needs intervention.
- Koloda's old image probe requested an intentionally disallowed host. The
  tooltip plugin permits Blizzard/CloudFront image hosts, not hs-manacost.ru.
  Preserve that allowlist: test the source image directly (200), the permitted
  card proxy (200), and the unlisted-host rejection explicitly (403).
- That proxy denial was also counted as homepage 403 because the log parser
  discarded its query. Count nonempty scalar root image-proxy requests separately
  as `image_proxy403`; empty/array parameters and normal homepage query strings
  still count as `root403`. Proxy 5xx remain in total and media counters. No
  IP/user-agent whitelist, request values in logs, or threshold relaxation.
- The missing HS firewall chains are confirmed using native nft, despite the
  oneshot unit showing active. Cause of removal is unproven. The old script
  restricts all origin TCP 80/443, including colocated services. Do not rerun it
  without an explicit shared-host policy review. Keep the failure signal visible.
  Per-vhost Nginx origin restrictions remain configured; that is not proof of an
  external negative-access test.
- The recent sampled errors were intermittent 503 on login/users-me, not proof
  of the historical 502 cause. PHP failure recovery does not cure slow requests,
  exhausted workers, OOM pressure or these unidentified 503s.

## Recovery policy and limits

Install `ops/monitoring/php-fpm-recovery.conf` as
`/etc/systemd/system/php-fpm{84,81}.service.d/60-manacost-recovery.conf`.
It adds only on-failure restart with a 5s delay and a burst of five starts within
300s. Vendor ExecStart, hardening and OOM policy remain unchanged. A clean exit
and manual stop do not restart. After repeated failure hits the start limit,
**systemd does not automatically resume retries after five minutes**. Inspect
the cause, correct it, then reset-failed/start the affected service deliberately.
Existing minute checks report the failed service; remote alert delivery is not
yet verified. No StartLimitAction reboot or unbounded restart loop is added.

## Verification, deployment and rollback

1. Offline regression tests: `test_availability_monitoring.py` and
   `test_php_recovery.py`; then `make check`, staged secret scan, independent
   HIGH-risk Sol review. Koloda's eight URL checks fit the existing 240s deadline:
   160s HTTP +9s DNS +10s redirect +15s SSH +20s parser +20s local headroom =234s.
2. Run `sudo python3 ops/monitoring/verify-php-recovery.py`. It validates the exact
   candidate policy with systemd and exercises abnormal exit, clean exit, manual
   stop and restart-rate limiting on uniquely named disposable transient units.
   It never stops or changes production PHP. Remove only its own temporary
   fixtures; keep captured aggregate results as release evidence.
3. Shadow-run the candidate Koloda monitor under a 240s outer timeout with a
   root-owned separate HEALTHCHECK_LOG and candidate HEALTHCHECK_NGINX_HELPER.
   Confirm the three media status contracts; do not mislabel other WARN/FAIL as
   resolved. Confirm real origin/regional home HTML and login status separately.
4. Record source SHA/hashes, effective PHP policies, both main PIDs and a new
   root-only backup of the exact existing helper, Koloda script and any matching
   drop-ins. Record absent drop-ins explicitly. Do not overwrite unknown drift.
5. Atomically install the helper and Koloda script. Install both drop-ins with
   root:root 0644, validate complete units with systemd-analyze verify, then run
   **daemon-reload only**. Assert main PIDs unchanged, services active, Restart
   on-failure, RestartUSec 5s, interval 300s, burst 5. No service restart/reload.
6. Run installed quick/full checks; confirm cron sees the new helper and the
   retained firewall failure. Keep public TLS verification and regional paths.

Rollback: atomically restore the two backed-up monitoring files. Restore prior
drop-ins if present; if absent, move only the newly installed two drop-ins into
the release backup (do not delete unrelated files or directories). Daemon-reload,
verify previous effective properties and unchanged PHP PIDs. Do not revert
accurate failure signals merely to make a dashboard green.

## Next bounded phases

1. Collect PHP84/81 queue and slow-request evidence, correlate 5xx timestamps
   with FPM, Nginx and resource pressure without storing raw user requests.
2. Measure Deckview worker/browser concurrency and memory over representative
   jobs before choosing limits. At ~13:07 UTC worker anonymous memory was ~3.56
   GiB, current ~3.61 GiB, peak ~3.70 GiB; cgroup OOM events zero since boot.
   A single snapshot does not establish a leak or justify a memory cap.
3. Reconcile shared-host firewall ownership with ISPmanager/Docker and inventory
   dependent services before any networking change; test allowed/denied paths
   from an independent external location, with a timed rollback ready.
4. Test independent external alert delivery; retain daily reboot until its
   replacement safeguards and recovery are verified. Observe 24h then seven
   days before claiming a lower incident rate.
