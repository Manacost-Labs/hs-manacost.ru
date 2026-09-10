# Shared origin availability monitoring — release 1

Owner: shared-origin-availability-monitoring. Source is `ops/monitoring` in
the HS repository because both WordPress sites use this origin. This package
is NOT WordPress runtime code and is NOT installed by `ops/deploy.sh`.
Do not promote unrelated WordPress changes to install these host tools.

## Scope and acceptance

- HS checks php-fpm84 plus php-fpm81 (still serves the login override),
  MariaDB, Redis and both PHP sockets. Koloda checks php-fpm84 and its socket.
- Koloda checks canonical `.com`, all three configured A records, and the
  legacy `.ru` redirect separately. Public HTTPS verifies certificates.
- Quick local service/socket/log checks each minute, except full-check minutes.
  Existing detailed DNS, pages, assets, firewall and cache checks remain at 5m.
- One shared full/quick lock per site. Quick deadline 40s, full deadline 240s,
  followed by a 5s hard-kill bound. Lock contention, exec failure, timeout and
  check failures are written to existing logs and syslog.
- HS full-run HTTP budget is 160s across both edges (18 attempts), plus 9s DNS,
  20s log parser and 30s local headroom: 219s within the 240s runner deadline.
  An offline failure-path test verifies every attempt and this budget. A
  pathological local stall can still hit the outer deadline and emits UNKNOWN.
- No new secrets, packages, listeners, database writes, cache purges, service
  restarts, firewall rules or changes to user authentication.

## Evidence semantics

`nginx_recent.py` counts a 300s window and emits aggregate counters only.
It never logs raw IPs, URLs, query values, headers or exception text.
Return 0 means below the existing thresholds, not proof that the site works.
Return 1 means >20 observed 5xx, any observed root/admin 403, or any matched
upstream error. Categories distinguish page/API, media, admin and analytics.
Return 2 (`UNKNOWN`) means logs are missing, unreadable, malformed, rotating
during the read, incomplete or too large to cover the window within budget.
Shell wrappers make either nonzero result fail the overall check.

Reads are bounded to a 4 MiB tail per regular log, plus only recent `.1`–`.7`
plain/gzip rotations. Compressed input has a 4 MiB decompressed limit; exceeding
it is UNKNOWN, not a full-file decompression. A timestamp before the requested
window must be present at a clipped boundary. Worst case: 15 files per log,
bounded individually, and an independent 20s process deadline.
Current host error-log timezone is UTC. Rotated-file mtime must retain last-write
time (current logrotate behavior); a different rotation scheme requires adapting
the tests. No request records means no observed traffic, not proven health.

## Verification and release

1. Run `python3 -m unittest discover -s tests -p test_availability_monitoring.py -v`,
   `make check`, staged security scan and independent HIGH-risk review.
2. Shadow-run the exact candidate scripts with `HEALTHCHECK_LOG` pointing into
   a root-owned temporary directory and `HEALTHCHECK_NGINX_HELPER` pointing at
   the candidate helper. This writes no production configuration. Fixtures
   simulate inactive PHP, unreadable logs and corrupt rotations; never stop the
   shared production PHP service as a fault test.
3. Back up the exact two sbin scripts and two cron files, retaining modes and
   ownership. Record candidate Git SHA plus source/live SHA-256 per file.
4. Install helper and runner into `/usr/local/libexec/manacost-monitoring`;
   scripts into `/usr/local/sbin/{hs-manacost-healthcheck,koloda-healthcheck.sh}`;
   tmpfiles definition into `/etc/tmpfiles.d/manacost-monitoring.conf`.
   Create only `/run/lock/manacost-monitoring` root:root 0700 with tmpfiles.
   Use same-directory temporary files and atomic rename, then install the exact
   two cron files `/etc/cron.d/{hs-manacost-healthcheck,koloda-healthcheck}` last.
5. Exercise installed quick/full runs, confirm cron-produced timestamps, compare
   real homepage/article HTML from origin and regional delivery paths. An origin
   self-signed certificate is a separate TLS boundary, not a reason to disable
   certificate verification in public probes.

Rollback: atomically restore just the four backed-up scripts/cron files. No
Nginx/PHP/DB reload is needed. The inert helper and tmpfiles definition may remain;
do not delete logs or backups. Verify original hashes and scheduled checks.
Do not roll back accurate failure reporting merely because an old hidden problem
becomes visible. Retain shadow and post-install evidence outside Git (no raw
production logs committed).

## Remaining stages — not part of this release

1. Independent outside-origin probes and notification delivery/heartbeat tests.
   Current syslog emission is not a tested phone/Telegram/pager alert.
2. Investigate retained firewall and asset-proxy failures; distinguish access
   policy denials from real reader failures before changing thresholds.
3. FPM independent status listener/queue metrics and restart backoff, then
   measured cgroup limits for browser-heavy background work. Keep 32/20 PHP
   worker limits until CPU, RSS and DB evidence justify a change.
4. Thirty-day incident retention with measured disk budget, including PHP84
   slowlogs; attribution of slow cron/SQL queries and bounded background work.
5. Remove daily reboot only after replacement safety measures are verified;
   preserve regional stale-cache privacy and independently test tunnel paths.
6. Observe at least 24h then seven days before claiming a reduced incident rate.
   A short successful probe is not proof that intermittent 502s are eliminated.
