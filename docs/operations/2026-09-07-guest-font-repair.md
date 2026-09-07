# Guest typography and article cleanup

## Scope and cause

The homepage optimizer removed both Newspaper's Google-font stylesheets and
WP Rocket's same-host font stylesheets and noscript fallbacks. It then forced
Arial on only some ancestors. Descendant titles still requested unavailable
Oswald, Fira Sans and Ubuntu; Chromium rendered them with Liberation Serif.
Logged-in requests bypassed this optimizer and were unaffected.

Remove the font-trimming branch entirely, including its legacy feature flag.
Keep mobile first-view assets, the top banner rotator and third-party deferral.
The executable regression exercises anonymous desktop/mobile requests, the old
flag, authenticated bypass and the remaining default optimizations.

## Separate runtime settings

These settings are not deployed by copying source. Apply first to staging,
then to production as part of the exact-SHA promotion. Target the explicit
runtime path and URL; never the `.com` mirror as a separate database.

- Disable `cackle` with `wp plugin deactivate cackle`, without `--uninstall`.
  Its files, WordPress comments, metadata and settings must remain on runtime.
  The package is excluded from the active-only source inventory; deployment
  synchronizes only present plugin directories and does not delete inactive
  runtime plugin directories. Git retains the prior package for rollback.
- Clear only `td_011` → `td_ads` → `content_bottom` → `ad_code` with
  `wp option patch update td_011 td_ads content_bottom ad_code ''`.
  Preflight confirmed a Boosty link and `2026/05/banner-scaled.png` in this slot.
  Do not change header, sidebar, inline ads, Ad Inserter, post bodies or media.

Before each operation, back up the two affected option rows (`td_011` and
`active_plugins`) outside Git in protected storage and restore-test that
scoped backup in an isolated database. Compare all other nested theme options
and active plugins before/after. Record aggregate comment counts only; never
export comment bodies, identities, credentials or private settings to logs.
The existing independent-S3 backup setup is incomplete; this operation does
not alter media or claim that the full-site backup gate is healthy.

The operation is idempotent: an empty bottom slot and inactive Cackle are
already the desired result. Stop if the slot contains a different creative,
the site is multisite, any other settings change, or comment rows are lost.

## Rollback and verification

Roll back code through the release workflow using the prior verified SHA.
For settings, restore only the saved bottom-slot `ad_code` using the WordPress
option API. If Cackle was previously active, first verify its retained
`CACKLE_VERSION` and stored `cackle_plugin_version` both equal `4.28` (the
package header is separately `4.33`). Then use native
`activate_plugin( 'cackle/cackle.php', '', false, true )` and check for WP_Error.
The final `true` skips the install hook: ordinary activation unconditionally
alters the Cackle table, while a mismatched stored version also runs the
installer on file inclusion. Stop on a version mismatch; do not invoke the
installer as part of this rollback. Do not
import the complete option table into production or overwrite unrelated
settings changed by an editor during the operation.

Invalidate affected HTML caches through the existing cache owner. Verify a
fresh and warm homepage and representative article on `.ru`, `.com`, origin,
Moscow and Novosibirsk. Confirm real Cyrillic font rendering, no font-trim
override, no article-bottom Boosty creative, no Cackle widget/counter scripts,
existing native comments and unchanged other ad slots. Preserve canonical and
noindex policy. Passive browser verification must block analytics/view writes.
