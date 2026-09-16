# Reader transport reuse candidate

This change only reuses already verified HTTP/1.1 connections. TLS session
resumption remains disabled. Production and staging keep separate upstreams,
hostnames and trust stores; neither borrows the ordinary unverified WP pool.
No session, cookie, CSRF, database, entitlement or public DTO changes.

Each worker retains at most eight **idle** connections per Reader pool, for up
to 15 seconds, with 100 requests per connection. This is not a total concurrency
limit. HTTP/1.1 and an empty Connection header permit reuse; no
`non_idempotent` retries are enabled. See the official
[Nginx upstream contract](https://nginx.org/en/docs/http/ngx_http_upstream_module.html#keepalive)
and [retry contract](https://nginx.org/en/docs/http/ngx_http_proxy_module.html#proxy_next_upstream).

## Gates

`python3 -m unittest tests.test_reader_proxy_tls tests.test_reader_proxy_runtime`
checks the source configuration with a real disposable Nginx and three TLS
peers. The behavioral test alternates four synthetic cookie states on the same
connections, verifies Host/Authorization boundaries and private no-store,
rejects untrusted/incorrect-hostname TLS after priming an unverified pool,
recovers reads when one peer drops, and does not replay an accepted POST whose
response was lost. It runs in `make check`; no live certificates or user sessions
are loaded by the fixture. These are transport tests, not an OIDC end-to-end test.

Before activation, compare deployed source hashes and validate all three live
tunnels with the exact environment hostname/trust store. Capture baseline
guest bootstrap timings separately from edge connection/TLS time. Change only
staging's two files, syntax-check, reload, then repeat the same bounded series
and smoke checks. Production promotion requires the exact tested commit,
positive measured benefit and no auth/privacy regression. Guest measurements
must never be described as authenticated profile latency or capacity results.

Canary order: staging, Novosibirsk production, at least 30 minutes of observation,
then Moscow production and another observation window. Preserve the previous
two files for each environment/node. Rollback restores only those two files,
runs `nginx -t` and reloads Nginx; no database, cache namespace, certificate,
Reader artifact or user state is restored. Do not stop live tunnels to test
failure: the disposable fixture owns that destructive scenario.
