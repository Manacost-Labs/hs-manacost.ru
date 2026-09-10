# Shared origin availability monitoring — release 1

Owner: shared-origin-availability-monitoring. Source is `ops/monitoring` in
the HS repository because both WordPress sites use this origin. This package
is NOT WordPress runtime code and is NOT installed by `ops/deploy.sh`.
Do not promote unrelated WordPress changes to install these host tools.

## Scope and acceptance

- HS checks php-fpm84 plus php-fpm81 (still serves the login override),
  MariaDB, Redis and both PHP sockets. It also reads bounded aggregate status
  from both HS pools through their local Unix sockets. Koloda checks php-fpm84
  and its socket.
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
When the sanitized attribution stream is configured, likely Wordfence blocks
are `wordpress` 503 responses with an upstream 503 and `Retry-After`. They are
reported separately from gateway and non-WAF failures; this remains an
attribution candidate rather than proof of the originating plugin.
The two `/mca` locations retain their separate standard access log and attach
the sanitized attribution format at the same location level. The monitor reads
that standard log as an additional input so its total and attributed counts
remain comparable without copying analytics requests into the main access log.

Return 0 means below the thresholds, not proof that the site works. Return 1
means at least three 502/504 responses, more than 20 attributed non-WAF 5xx,
more than two unattributed 5xx, more than 350 likely WAF 503 responses, any
observed root/admin 403, or any matched upstream error. The 350 threshold is a
bounded security-surge guard above the measured seven-day five-minute maximum;
ordinary WAF blocks do not page availability on-call. Without an attribution
argument, the helper retains the legacy >20 total-5xx threshold for Koloda and
offline compatibility. The HS wrapper requires its dedicated attribution log.
Return 2 (`UNKNOWN`) means logs are missing, unreadable, malformed, rotating
during the read, incomplete or too large to cover the window within budget.
Shell wrappers make either nonzero result fail the overall check.

`fpm_status.py` accepts only the fixed PHP-FPM status fields and persists a
root-private aggregate state under `/run/lock/manacost-monitoring`. It returns
failure when a nonempty listen queue or at least 75% pool utilization persists
for two consecutive samples 30–150 seconds apart, or when `max children reached`
increments. A PHP master restart resets deltas by `start time`. Slow-request
deltas are reported for diagnosis but do not page by themselves. A failed,
too-early or stale measurement breaks streak continuity. The helper does not
retain process rows, request paths or client data.

Activation requires verifying the live pool contracts before installation:
PHP 8.4 must use `/var/www/php-fpm/hs-manacost-php84.sock`,
`pm.status_path=/fpm-status-hs-manacost` and `pm.max_children=32`; PHP 8.1 must
use `/var/www/php-fpm/6.sock`, the same status path and `pm.max_children=48`.
These values are explicit monitor inputs, not auto-discovered limits. Any drift
holds the release until the source defaults and tests are deliberately updated.

Reads are bounded to a 4 MiB tail per regular log, plus only recent `.1`–`.7`
plain/gzip rotations. Compressed input has a 4 MiB decompressed limit; exceeding
it is UNKNOWN, not a full-file decompression. A timestamp before the requested
window must be present at a clipped boundary. Worst case: 15 files per log,
bounded individually, and an independent 20s process deadline.
Current host error-log timezone is UTC. Rotated-file mtime must retain last-write
time (current logrotate behavior); a different rotation scheme requires adapting
the tests. No request records means no observed traffic, not proven health.

## Alert triage

- `gateway_5xx>=3`: inspect the bounded owner counters, both FPM lines and the
  matching Nginx error category. Treat this as an availability incident; do not
  increase PHP workers until queue, CPU, memory and database evidence agree.
- `unattributed_5xx>2` or `UNKNOWN nginx_recent`: verify that the attribution
  include, vhost resource, log permissions and rotation are the exact deployed
  revision. Do not silence the alert by dropping the required log argument.
- `waf_503_candidate>350`: handle as a security surge and correlate aggregate
  Wordfence evidence. The signal is not proof that Wordfence caused every 503;
  do not disable the firewall to make the availability check green.
- `FAIL fpm_status`: distinguish a sustained queue, sustained 75% utilization
  and a new `max_children_delta`. Preserve the state file for the next sample.
  `UNKNOWN fpm_status` requires checking the local socket, CGI client, helper
  and root-private state directory before interpreting utilization.

## Verification and release

1. Run `python3 -m unittest discover -s tests -p test_availability_monitoring.py -v`,
   `python3 -m unittest discover -s tests -p test_nginx_wordfence_attribution.py -v`,
   `make check`, staged security scan and independent HIGH-risk review.
2. Shadow-run the exact candidate scripts with `HEALTHCHECK_LOG` pointing into
   a root-owned temporary directory and `HEALTHCHECK_NGINX_HELPER` pointing at
   the candidate helper. This writes no production configuration. Fixtures
   simulate inactive PHP, unreadable logs and corrupt rotations; never stop the
   shared production PHP service as a fault test.
3. Back up the exact two sbin scripts, two cron files, libexec runner and the
   existing `/usr/local/libexec/manacost-monitoring/nginx_recent.py`, retaining
   modes and ownership. Record whether `fpm_status.py` already exists, plus the
   candidate Git SHA and source/live SHA-256 per file.
4. Install `nginx_recent.py`, `fpm_status.py` and the runner into
   `/usr/local/libexec/manacost-monitoring`;
   scripts into `/usr/local/sbin/{hs-manacost-healthcheck,koloda-healthcheck.sh}`;
   tmpfiles definition into `/etc/tmpfiles.d/manacost-monitoring.conf`.
   Create only `/run/lock/manacost-monitoring` root:root 0700 with tmpfiles.
   Use same-directory temporary files and atomic rename, then install the exact
   two cron files `/etc/cron.d/{hs-manacost-healthcheck,koloda-healthcheck}` last.
5. Exercise installed quick/full runs, confirm cron-produced timestamps, compare
   real homepage/article HTML from origin and regional delivery paths. An origin
   self-signed certificate is a separate TLS boundary, not a reason to disable
   certificate verification in public probes.

The HS Nginx attribution include, server resource and both location-level
Plausible directives must be installed and validated before the updated HS
healthcheck and cron are activated. After the graceful reload, wait more than
the 300-second observation window before the first candidate monitor run. The
parser skips pre-window legacy attribution records but treats a legacy record
inside the active window as `UNKNOWN`; this makes schema transition explicit
without deleting history. Shadow-run both FPM pools with a temporary root-owned
state directory; never stop or saturate a production pool as a fault test.

Rollback: disable the two candidate cron entries first. Restore the exact
recorded pre-state of `hs-manacost-healthcheck`, `koloda-healthcheck.sh`,
`nginx_recent.py`, `run-healthcheck.sh`, `hs-manacost-healthcheck.cron` and
`koloda-healthcheck.cron`; an originally absent target is moved into the release
backup rather than left active. Then restore the named Nginx files in the order
defined by the attribution runbook, run `nginx -t` and reload gracefully. This
prevents the new parser or runner from reading newly produced legacy-schema
records. No PHP/DB reload is needed. The inert new `fpm_status.py` and tmpfiles
definition may remain; do not delete logs or backups. Verify original hashes,
cron contents and every scheduled entrypoint against the recorded pre-state.
Do not roll back accurate failure reporting merely because an old hidden problem
becomes visible. Retain shadow and post-install evidence outside Git (no raw
production logs committed).

## Remaining stages — not part of this release

1. Independent outside-origin probes and notification delivery/heartbeat tests.
   Current syslog emission is not a tested phone/Telegram/pager alert.
2. Investigate retained firewall and asset-proxy failures; distinguish access
   policy denials from real reader failures before changing thresholds.
3. Measure FPM queue/utilization history before changing worker limits or cgroup
   limits for browser-heavy background work. Keep current PHP worker limits
   until CPU, RSS and DB evidence justify a change.
4. Thirty-day incident retention with measured disk budget, including PHP84
   slowlogs; attribution of slow cron/SQL queries and bounded background work.
5. Remove daily reboot only after replacement safety measures are verified;
   preserve regional stale-cache privacy and independently test tunnel paths.
6. Observe at least 24h then seven days before claiming a reduced incident rate.
   A short successful probe is not proof that intermittent 502s are eliminated.
