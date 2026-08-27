# Verification and evidence

## Repository gate

For every completed source change:

```bash
make check
/home/debian/server/tools/ai-quality/bin/ai-security-check staged
```

Also run the narrow test/linter closest to the change before the full gate. Do not silence a failing check; distinguish new failures from documented baseline debt.

## Task-specific acceptance

| Area | Minimum evidence |
|---|---|
| Article editor | Draft, autosave, revision, preview, publish/update, stored content, role, mobile/keyboard, media/S3 |
| Admin UI | Create/edit/filter/paginate/error/delete, capabilities/nonces, loading/empty/error, 320–1440 px and keyboard |
| Newspaper/frontend | Homepage/article/archive/search, mobile menu/layout, images, ads, console/network errors and counters |
| Cache/performance | Cold/warm, anonymous/authenticated, targeted invalidation, origin/public/both proxy paths and before/after metrics |
| SEO | `.ru` canonical/indexable, `.com` mirror noindex, staging noindex, metadata/schema/sitemap/redirects |
| Data migration | Dry-run count, backup, sample/hash comparison, idempotence, post-check and tested rollback |
| Infrastructure | Config validation, staging first, exact SHA, TLS/redirect/host behavior and rollback |

## Browser verification

Use a real browser for interaction and responsive work. Check console/network, focus, keyboard operation, long/empty/error content and an anonymous/incognito path when public cache or media is involved. Screenshots are supporting evidence, not proof of save, accessibility or correctness.

## Delivery verification

- `./ops/smoke-check.sh staging` verifies staging after its Git-based deployment.
- `./ops/smoke-check.sh production` verifies the production topology after authorized promotion.
- Automated smoke success does not replace the feature-specific user flow.

## Completion evidence

Provide the exact commit/PR, checks executed and results, staging deployment result, whether production changed, affected data/cache/SEO/security, and remaining uncertainty. Do not report “all works” when a required route or workflow was not tested.
