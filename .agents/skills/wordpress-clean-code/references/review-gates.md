# Review and quality gates

## Before implementation

1. Resolve the first-party owner and list the affected hooks, contracts, users, hosts, and rollback.
2. Confirm the source is not vendor, generated, cached, uploaded, backed up, or a production runtime copy.
3. Capture the failing test or reproducible check.

## Before commit

1. Review the diff for unrelated formatting, duplicate helpers, dead code, leaked secrets, unbounded work, and missing error states.
2. Run `make code-quality`; inspect each WPCS, PHPCompatibilityWP, and PHPStan finding rather than suppressing it.
3. Run `make check` and `/home/debian/server/tools/ai-quality/bin/ai-security-check staged`.
4. For contracts run `make contracts`; for WordPress behavior run `make integration`; for Newspaper/UI changes run `make visual`.
5. Stage only the intended files and run the Newspaper strict audit when its protected surface is involved.

## Existing debt

- `phpstan-baseline.neon` records the initial legacy debt and must not grow.
- A changed legacy file may expose pre-existing WPCS debt because PHPCS checks the whole file. Clean only the touched file in a separate reviewable commit when needed.
- Do not convert real findings into broad exclusions. If a third-party type is genuinely unavailable, add a narrow stub or a precisely documented exception.
