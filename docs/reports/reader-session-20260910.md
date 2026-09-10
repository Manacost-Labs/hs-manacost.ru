# Reader remembered login — 2026-09-10

## Scope and contract

- Request: persist the test Manacost login cookie for 30 days, using HearthPulse.
- Reader base: `767da55d5ed55064e731a1438c7d1ac90412c2a3`; owned branch
  `feat/reader-session-30-days-20260910` in an isolated worktree.
- Provider base: `351ac926ebb8445c6f98fadf186079c7d6da73f8`, separate isolated
  HearthPulse worktree; only the browserIdentity module and its tests/docs.
- HIGH authentication risk. WordPress project/impact/clean-code/privacy and
  external-integration guidance, plus HP API/security/TDD/release baselines.
  Cross-repository mandatory baselines exceed the usual skill budget; no new
  framework, dependency upgrade, global browser-auth change or WordPress cookie.
- Protect theme, plugins, workflow/proxy configuration, canonical users and
  browser sessions, unrelated worktrees, encryption keys and existing DB data.

## Design

The previous callback capped both cookie and local session to the 300-second
access token. Merely changing Max-Age would not keep login working. The staging
client now requests explicit offline consent. The provider retains short access
tokens and rotating refresh tokens, with a maximum original 30-day grant/binding
deadline; canonical parent expiry/logout/reset/block still ends access earlier.

The browser gets only a 30-day opaque HttpOnly host cookie. Refresh credentials
are encrypted on the BFF. A durable claim plus an atomic generation/claim check
prevents refresh replay and logout resurrection. Ambiguous rotation ends login
with a cleared cookie and durable family revocation. No positive auth cache,
no automatic token retry, no rolling 30-day extension, no WP reader account.
Legacy sessions remain short until the user logs in again.

## Verification and release evidence

Regression tests first reproduced seven failures, including the 300-second
cookie, absent offline scope, missing rotation and missing persistence table.
Final `make check` passes: 148 repository Python, 105 Reader Node and 23 Reader
UI Python tests, plus syntax/contracts/skills/shell gates. Reader dependency
audit and redacted gitleaks scan pass. Eighteen new focused tests cover cookie
duration, restart, concurrency, malformed inputs, partial token/introspection
outage, revocation, fixed expiry and legacy behavior. All use synthetic accounts.

HearthPulse `npm run verify:release` passes (canonical lint/architecture, full
suite, build, recovery and budget/docs gates), as do strict Semgrep and gitleaks.
Fresh-context Sol final review approved both diffs after the callback validation
and missing race tests were added. Independent rerun: 18/18 focused tests pass.
The consent template was checked with Chrome DevTools at 390 and 1440 pixels:
no horizontal overflow or console errors; readable disclosure and named buttons.
An initially selected random port was blocked by the browser allowlist; testing
used the project's approved localhost development port without changing policy.

Fresh-context Sol architecture review required an absolute deadline, exact
offline-consent allowlist, transactional refresh/logout state machine and terminal
handling of ambiguous outcomes. These are explicit implementation/test criteria.
Provider revocation of an already consumed refresh token without a type hint is
covered by a real endpoint test, not assumed from the outbox API.

HearthPulse has an unchanged baseline high-severity advisory for `sharp@0.35.3`
(GHSA-rgj7-g3m4-5g8c). The canonical npm critical gate passes, but the stricter
Trivy CI workflow fails. Provider PR 37 is published but must not be merged or
deployed around that red gate. A separate permission question requests the
bounded image-library patch and image regression checks. No dependency override,
ignore rule, production restart or partial BFF activation has been performed.

## Activation and rollback

Provider first, BFF second, only exact reviewed immutable source revisions.
The verified production provider is `hs-arena.service`, at source base351ac926
behind origin port3101. The older isolated `hearthpulse-identity-staging.service`
serves only test.hearthpulse.net and is not the deployment target. Retain both
previous release links. Take a consistent private staging Reader
SQLite backup before its additive table is created. Restart only the scoped
identity and Reader services; never clear canonical sessions or browser cookies
for other sites. Reverse by selecting the previous binaries while keeping the
current authentication DB/keys (no revocation rollback). Re-login is an acceptable
rollback consequence; losing profiles/comments or reviving logged-out sessions
is not. Synthetic and public guest checks are separate from real user SSO.
