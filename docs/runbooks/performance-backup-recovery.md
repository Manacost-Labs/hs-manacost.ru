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

## Independent review and memory gate

Claude Opus 5.5 max reviewed the bounded snapshot/installer design through the
subscription CLI. Its three concrete blockers are corrected: manifest uploads
do not require CreateBucket, Git status/revision errors stop installation before
writes, and activating oneshot jobs block replacement. Manifests enumerate the
checked destination. Source changes during a run can fail verification; retry
an incremental run, never label a failed run successful. Bucket independence
within one provider is not protection against compromised credentials.

The full-library inventory contained 2,199,069 objects / 186,837,880,747 bytes.
The initial global fast-list dry-run reached about 6 GiB RSS and was stopped
without remote writes. Directory-based listing replaces fast-list, and the S3
service has a 6 GiB memory cap and stops on OOM. Test the bounded version before
activating timers. Do not put HS_S3_DRY_RUN in the permanent environment: a
dry-run exits successfully without creating a backup. The first current prefix
is empty; previous offload backups use a separate prefix and remain untouched.

## Initial-copy throughput and bounded recovery

The initial two-transfer run copied only 18,620 objects / 2,248,335,647 bytes
when observed against the 2,199,069-object inventory. A 24-hour timeout cannot
be assumed sufficient. A separate private pilot made 16 copies of the same
unchanged 1,335,086-byte PNG per concurrency level. All 48 copies succeeded;
elapsed times were 49.945 seconds with two workers, 35.615 with four and 6.236
with eight. This small test supports a bounded increase; it does not predict
whole-library completion time or establish sustained provider capacity.

`HS_S3_WORKERS` defaults to eight and accepts only integers 1–16. The copy and
full check limit API transactions to 16 per second, with a bounded burst equal
to the worker count. The service keeps its 6 GiB memory cap, directory-based
listing and OOM stop. Aggregate copy progress is logged every five minutes;
one-line statistics do not include individual active object paths. Initial
copies may run up to seven days. Daily timer triggers do not start a second
invocation of an already active service. No success is reported until the full
check, byte restore sample and manifest publication pass.

For an operator-approved restart, stop only the project snapshot service and
its invocation-bound initial-restore watcher before installing from a clean
committed checkout. Retain `current` and all version prefixes. Restarting sync
checks existing objects and continues the incomplete copy; a stopped invocation
is not a completed backup. Recreate the initial-restore watcher bound to the new
service invocation and installed source revision. Start the snapshot with
`systemctl start --no-block` and allow the watcher nine days: the seven-day
snapshot timeout, the restore service's eight-hour timeout and additional time
for the capacity gate. A failed snapshot never proves restoration; after any
scheduled incremental retry, re-arm the watcher against that invocation and the
same installed revision. Media uploads remain available during copying, so live
source drift may legitimately fail the full check and require this retry.

Before starting, check the reverse ordering dependencies with
`systemctl list-dependencies --before hs-manacost-s3-backup.service`.
No DB, deploy or other backup job may be ordered after this long oneshot or share
its application lock. The installed
credential helper uses static access-key references rather than expiring session
tokens. Request throttling applies to copy and check, not the separate directory
listings. Reduce throughput to four workers first; `HS_S3_WORKERS=2` remains
available, but a first copy may then require multiple interrupted/incremental
runs. Stop the active invocation before changing installed helper files.

Claude Opus 5.5 max approved the concurrency change subject to the dependency
check above. The installed unit's only reverse ordering dependency was
`shutdown.target`, and the owned backup helpers contain no shared lock. A real
September-only dry-run against a separate private pilot prefix passed with the
new limits, without copying objects or publishing manifests. These checks do
not establish full backup completion.
