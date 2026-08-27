# WordPress operational safety system

This repository treats a backup as healthy only after a restore test and treats a UI change as safe only after behaviour and screenshot checks.

## Isolated WordPress integration

`ops/integration/run.sh` downloads WordPress 6.9.7, starts a disposable MariaDB 10.11 and Apache WordPress stack, installs the Newspaper theme plus the small set of plugins needed by the scenarios, and destroys the database after the run.

The behaviour suite covers article publication, update revisions, autosave, duplicate offloaded image names, simulated S3 hydration, critical shortcodes, the views read endpoint, the `.ru` canonical and cache-purge scheduling. It must never connect to the production database or production uploads.

Useful commands:

```bash
make integration
make visual
```

Visual baselines live beside `tests/visual/wordpress.spec.ts`. Regenerate them only in the pinned Playwright container after reviewing the UI change. CI compares home, article, category, dashboard and editor at 1440×900 and 390×844.

## WordPress contracts

`config/wordpress-contracts.json` records statically resolvable post meta, options, shortcodes, AJAX actions, REST routes, cron hooks and capabilities owned by this project. Third-party WordPress.org/commercial internals are intentionally excluded.

After intentionally changing a contract:

```bash
make contracts
git diff -- config/wordpress-contracts.json
```

The normal quality gate fails if source and inventory differ.

## Database and S3 backup

The existing encrypted `server-backup-core.timer` remains the daily database/code backup. `server-backup-check.timer` is the existing monthly repository check. The project adds an explicit WordPress database restore into disposable MariaDB and an S3 restore sample.

S3 media needs an independent bucket or account. A second prefix in `hs-manacost-media-3az` is rejected because it does not protect against bucket deletion or account loss. Create `/etc/hs-manacost/backup.env` from `ops/backup/backup.env.example`, set root ownership and mode `0600`, test both scripts manually, then run:

```bash
sudo ops/backup/install-timers.sh
sudo systemctl start hs-manacost-s3-backup.service
sudo systemctl start hs-manacost-restore-drill.service
ops/backup/audit-status.sh
```

The S3 job maintains `current`, moves replaced/deleted objects to dated `versions`, writes a manifest and expires version directories after 90 days. The restore drill restores the newest matched SQL dump into a temporary MariaDB 10.11 container and downloads a deterministic object from the independent S3 copy twice to verify reproducible bytes. It never imports into production.

## Plugin updates

The weekly workflow produces compatibility and known-vulnerability artifacts; it never mutates the repository or a server. `config/plugin-update-policy.json` permits one plugin per change. Commercial plugins, WP Rocket, Perfmatters, Shortcodes Ultimate add-ons and all tagDiv/Newspaper packages are manual-only.

```bash
python3 ops/plugins/audit-updates.py --output-dir .artifacts/plugin-audit
python3 ops/plugins/prepare-update.py --slug classic-editor --archive /trusted/path/classic-editor.zip
```

The preparation command validates paths, symlinks, size and a WordPress plugin header, then writes only a review copy under ignored `.artifacts`. The reviewed package is moved into source in a dedicated branch, tested in the isolated stack, merged to staging and promoted manually by exact tested commit SHA. Git provides the rollback; retain the previous licensed archive outside Git for commercial packages.
