# Task routing

Use `config/ai-skills.json` as the machine-readable source. This guide explains selection; it does not replace the registry.

| Task signal | Required specialist route |
|---|---|
| Any project task | `hs-manacost-project` |
| General WordPress/PHP ownership | `wordpress`, then the exact WP specialization |
| First-party code, refactor, lint or static analysis | `code_quality`, then the owning WordPress specialization |
| Classic/TinyMCE/Gutenberg article editing | `wordpress_article_editor` |
| wp-admin screen/form/list/settings | `wordpress_admin_ui` |
| Newspaper/tagDiv/theme template | `newspaper_tagdiv` |
| WP Rocket/Redis/Cloudflare/Perfmatters/AIOSEO/Wordfence/Redirection | `wordpress_runtime_stack` |
| Plugin/MU-plugin or hooks | `plugin_development` |
| REST/AJAX API | `rest_api`, plus admin/security route when applicable |
| WP-CLI/database/operational inspection | `wordpress_operations` |
| Frontend UI/CSS/responsive work | `frontend_design` |
| SEO/canonical/schema/sitemap | `seo` |
| Cache/performance/Core Web Vitals | `performance` and usually `wordpress_runtime_stack` |
| Production failure | `incident`, then the owning functional route |
| Missing/wrong/heavy image, duplicate filename, WebP/AVIF/S3 | `media_integrity` |
| Database/postmeta/options/serialized bulk change | `database_migration` |
| PR, staging deployment, production promotion or rollback | `release` |
| Health, latency, cron, queue, backup or capacity report | `observability` |
| Article/link/shortcode/media/canonical audit | `content_integrity` |
| Pipeline/nginx/proxy/deployment | `infrastructure` |

All code changes also load the baseline skills in `baseline_for_code_changes`. Do not omit testing, security, review or Git workflow because a specialist skill already mentions them.

## Context selection

1. Load this project skill.
2. Load one primary route and only directly coupled specializations.
3. Read current source plus one local implementation example.
4. Read external documentation only for version-sensitive APIs; prefer official primary sources.
5. Add broader context only when evidence shows the issue crosses a boundary.

Examples:

- “Add an editor button”: project + article editor + admin UI; add Newspaper only if rendering/template code changes.
- “Images stale in Moscow”: project + media integrity + runtime stack + incident; do not load editor UI unless uploads themselves fail.
- “Change article layout”: project + Newspaper + frontend; add SEO if headings/schema/canonical output changes.
- “Migrate post metadata”: project + database migration + WP operations + plugin development; read data/migration rules before any write.

When two rules conflict, follow system/developer/user scope first, then the nearest `AGENTS.md`, then project skills. Surface unresolved product or data ambiguity instead of silently choosing.
