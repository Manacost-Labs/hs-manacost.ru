# Project architecture

## Ownership zones

| Zone | Location | Contents | Change policy |
|---|---|---|---|
| Source | `/srv/projects/wordpress/hs-manacost.ru` | Theme, active plugin source, MU-plugins, nginx/pipeline configuration, tests and AI rules | Normal work happens here through Git |
| Staging runtime | Path from `config/site.json` | Isolated WordPress/database used by `test.hs-manacost.ru` | Deployed automatically from successful `main` Quality |
| Production runtime | Path from `config/site.json` | Shared `.ru`/`.com` WordPress runtime | Manual promotion of an exact staging-verified SHA |
| Database | Runtime-managed | Posts, metadata, options, users, plugin configuration and counters | Never committed; writes need scope, backup and rollback |
| Media | WordPress attachments plus Object Storage/S3 | Upload originals, variants and attachment metadata | Never committed; preserve mapping and unique filenames |
| Delivery | Origin, Cloudflare and Moscow/Novosibirsk proxies | TLS, routing and cached public responses | No independent WordPress on edges |

Read current paths, versions and domain policy from `config/site.json`; read network topology from `config/network.json`. Do not duplicate changing IPs or versions into new instructions.

## Source map

- `wordpress/themes/Newspaper_new`: active Newspaper parent theme. Prefer supported hooks, Cloud Templates, an MU-plugin or child-theme strategy over direct parent edits.
- `wordpress/plugins`: only regular plugins active on production, as indexed by `config/wordpress-plugins.json`.
- `wordpress/mu-plugins`: project behavior loaded automatically, including domain policy, editor workspace, S3/media, cache purge, performance and analytics.
- `ops`: deployment, smoke checks, nginx source configuration and AI skill synchronization.
- `config`: non-secret project inventory and policy. It does not contain runtime plugin settings or credentials.
- `tests`: repository policy and regression checks.
- `.agents/skills`: canonical project skill source; `.codex/skills` and `.claude/skills` are generated synchronized copies.

## Request path

Public `.ru` and `.com` requests normally traverse a regional edge/reverse tunnel to the same origin. Staging uses its own host and isolated WordPress data. A visible response may therefore be influenced by browser, proxy/Cloudflare, WP Rocket, Redis, WordPress, database and S3 layers.

When diagnosing, compare the earliest trustworthy layer and move outward. Do not infer a database or origin defect from one stale browser/edge response.

## Coupled behavior

Treat these as cross-cutting acceptance concerns when affected:

- `.ru` canonical, `.com` noindex mirror, staging noindex;
- article view counting and public cache bypass/invalidation;
- Classic Editor/TinyMCE, Newspaper/tagDiv rendering and editor roles;
- unique media filenames, generated sizes, S3 availability and recovery;
- ads, analytics and frontend script delay exclusions;
- authenticated editor/admin privacy and Wordfence enforcement;
- both regional proxy paths.
