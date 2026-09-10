# Wordfence 503 attribution log

## Purpose and boundary

Wordfence uses HTTP 503 with `Retry-After` for several temporary security
blocks. This signal provides a bounded attribution candidate alongside an
Nginx/PHP availability failure; `Retry-After` alone is not proof of
Wordfence. It does not alter Wordfence, its IP policy, the HTTP status returned
to a blocked client, cache behavior, or user access.

The JSONL log contains only:

- ISO-8601 timestamp (with the origin's configured offset);
- HTTP status;
- bounded endpoint class: `page`, `login`, `admin`, `rest`, `analytics`,
  `views`, or `media`;
- bounded owner class: `wordpress`, `plausible`, `views`, `media_fallback`,
  or `nginx`;
- upstream status; and
- a boolean telling whether the upstream sent `Retry-After`.

It deliberately excludes addresses, URI/query values, cookies, referrers,
user agents, response bodies and Wordfence reason text. Do not add those fields
to this log. The host's existing `/etc/logrotate.d/httpd-logs` pattern rotates
`/var/www/httpd-logs/*.log`; the new filename retains the `.log` suffix so no
separate retention rule is needed.

## Source files

- `ops/nginx/wordfence-503-attribution-http.conf` belongs in Nginx's `http`
  context, before a vhost refers to its maps and log format.
- `ops/nginx/resources/31-wordfence-503-attribution.conf` is an additive
  server-context resource. It is shared with the mirror but its condition only
  writes records for canonical `hs-manacost.ru` hosts.
- `ops/nginx/staging.conf` writes the isolated staging signal.
- `ops/monitoring/nginx_recent.py` consumes the signal and keeps likely
  Wordfence blocks separate from actionable availability failures.
- `ops/nginx/resources/plausible-first-party.conf` repeats the sanitized log at
  its two location scopes because their standard access log overrides inherited
  server logging. Because the resource is shared with the `.com` mirror, the
  standard analytics logs are split by host before the monitor reads the `.ru`
  stream for count parity. Mirror failures cannot become unattributed canonical
  failures. The original URI is reduced to a fixed endpoint/owner class before
  internal rewrites; neither that URI nor its query is written to this log.

`ops/deploy.sh` does not install Nginx configuration. Treat this as a separate,
reviewed infrastructure release; do not copy the entire runtime vhost over an
unrelated ISP-managed configuration.

## Staging release

1. Record the exact Git SHA, whether the Nginx `http` include already exists,
   and a root-owned backup of the staging vhost. If the include exists, back it
   up too; otherwise record its absence so rollback removes the new include.
2. Install the source HTTP-context file as
   `/etc/nginx/conf.d/31-hs-manacost-503-attribution.conf`.
3. Apply only the new `access_log` directive from `ops/nginx/staging.conf` to
   the staging vhost, using its `test.hs-manacost.ru` log path. Do not replace
   the whole vhost: the active file has independently owned runtime changes.
4. Run `nginx -t`; on failure restore the staging-vhost backup and either
   restore the prior include or remove the newly created include, according to
   the recorded pre-state. Test again. On success, perform a graceful Nginx
   reload.
5. Make a bounded ordinary staging request. Verify HTTP 200 and that no
   attribution entry is written for it. Verify a deliberately controlled,
   disposable staging 5xx only if an existing incident fixture is available;
   never create a login attack to test this log.
6. Shadow-run the candidate `nginx_recent.py` against the staging attribution
   log before installing any scheduled healthcheck. A missing, malformed or
   privacy-expanded attribution record must return `UNKNOWN`, never zero.

## Production promotion and rollback

After the exact SHA has passed staging and the user authorizes promotion:

1. Record whether each target exists, then back up
   `/etc/nginx/conf.d/31-hs-manacost-503-attribution.conf` and
   `/etc/nginx/vhosts-resources/hs-manacost.ru/31-wordfence-503-attribution.conf`,
   plus `/etc/nginx/vhosts-resources/hs-manacost.ru/plausible-first-party.conf`
   when present to a timestamped, root-owned directory. An absent target has no
   backup: rollback must remove the file that this release created.
2. Atomically install the HTTP-context file, additive server-context resource
   and exact Plausible location resource. Do not replace the active production
   vhost: it has independently owned runtime changes.
3. Run `nginx -t` before a graceful reload. If either step fails, restore each
   pre-existing target from its backup and remove each target that was absent
   before the release, then retest; do not restart PHP-FPM or alter Wordfence.
4. Verify ordinary public and login-form requests on `.ru`, `.com`, origin,
   Moscow and Novosibirsk. They must remain HTTP 200. Confirm the new log has
   no raw client/request fields and that its file rotates under the existing
   wildcard policy.
5. Wait more than the 300-second observation window after the Nginx reload.
   Old-schema entries outside the window are ignored; any old-schema entry still
   inside it intentionally returns `UNKNOWN`. Do not truncate or delete history.
6. Only after the production log is readable, atomically install the candidate
   monitoring helper and healthcheck described in `availability-monitoring.md`.
   Installing the healthcheck first is intentionally fail-closed and would
   report the missing attribution stream as `UNKNOWN`.

Rollback first restores the pre-change Plausible location resource and additive
server resource. Only then restore the prior HTTP include or remove it when it
did not exist before this release; remove any other named target recorded absent
before the release. Follow with `nginx -t`, graceful reload, and the same route
matrix. The log file may remain as historical evidence; do not delete incident
logs during rollback.
