# Independent media recovery before performance changes

Production originals must remain byte-identical. The existing private OVH backup
bucket is separate from the public media bucket. Do not create a new paid service,
replace originals, or delete prior backups for this task.

## Snapshot and restore contract

Configure root-owned `/etc/hs-manacost/backup.env`, mode 0600, using the example.
Only credential-file paths belong in configuration; the referenced JSON files
must be 0600. Never commit keys, dumps, manifests or downloaded media.
The source includes both `wp-content/uploads` and `wp-content/uploads-webpc`.
A second prefix or rclone alias in the source bucket is rejected.

Run `HS_S3_DRY_RUN=1 ops/backup/s3-snapshot.sh` before the first copy. It lists
objects and evaluates sync without changing storage or publishing a success
manifest. The normal run preserves replaced/deleted destination objects under
`versions/<timestamp>`, checks every current object (hash where available,
otherwise size), downloads a representative original from source and backup,
and compares bytes. Only then are timestamped and latest manifests published.
This is a metadata/hash check for all objects plus a byte restore sample, not a
claim that every object was downloaded. Retention is disabled by default.

The restore drill selects the newest snapshot across changing dump paths,
requires freshness, restores the configured dump and imports it into a pinned
MariaDB 10.11 container with no network, host database volume or exposed password
argument. Specify the actual database name for dumps containing CREATE/USE and
the actual WordPress table prefix. SQL failures remain private. Check non-empty
posts, postmeta and options, then compare a restored S3 sample with the source.
The 10 GiB temporary MariaDB data directory is RAM-backed, with a 12 GiB memory
limit; ensure headroom before running. Production is never the restore target.

Install only the backup and restore-drill timers after verification. The installer requires a clean committed checkout, waits for existing jobs to
finish, copies the three scripts into the owned runtime directory and records
the exact source revision. Services run those copies, independently of subsequent
checkout changes. Do not replace unrelated offload units. The snapshot is not
complete until the full listing/copy/check and manifest publication succeed.

## Observed evidence, 2026-10-12

The existing legacy offload backup was incomplete: September 2026 source
contained 43,112 objects / 5,043,901,123 bytes; its old backup prefix contained
10,317 / 1,236,594,917. These month counts do not establish full-library coverage.
A separate private pilot copied one original PNG, downloaded both copies and
verified SHA256 c686c8a445a071606303e8ccfca899010ea489fcb721d4b1dc3119896ed818c2
(1,335,086 bytes). The pilot is not a completed library backup.

An actual isolated restore from encrypted core snapshot
7e1c9220af2b5217e34f5c77b21e1ff09c649b31304182ee6b4ba5defd623fdc
passed: posts 210,759; postmeta 2,052,303; options 2,181. A 6 GiB RAM-backed
restore directory was insufficient; the verified retry used 10 GiB. This proves
that snapshot's database recovery, not completion of the new combined timer.
Full media backup, combined restore and capacity gates remain required before
bulk image processing. No automatic deletion is a capacity remedy.

Regression tests execute the real scripts with mocked storage/container tools:
check failure cannot publish success, empty source cannot archive an existing
backup, same-bucket aliases fail, missing credentials fail before I/O, dry-run
cannot copy, corrupt restoration fails, newest snapshot/actual database/password
boundaries work, and the installer touches only four owned units.
