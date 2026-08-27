# Release gates

| Change | Required gate beyond `make check` |
|---|---|
| publishing/editor/media/S3/views/canonical/cache | `make integration` |
| Newspaper/CSS/frontend/wp-admin | `make visual` and reviewed diffs |
| plugin update | one plugin, vulnerability/compatibility report, integration staging |
| database migration | dry-run, verified backup restore, bounded staging run |
| Nginx/proxy | configuration syntax, staging first, every edge |
| SEO/domain | canonical, robots, sitemap and redirect matrix |
| performance | before/after cold and warm measurement |

Every release requires security scanning of the staged diff and a rollback whose commands/targets are known before promotion.

The staging SHA, production promotion SHA and recorded deployed SHA must be identical. Treat mismatch as a failed release.
