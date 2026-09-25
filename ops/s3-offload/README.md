# S3 media offload worker

`worker.sh` is the versioned source for
`/usr/local/libexec/hs-manacost-s3-offload/worker.sh`. The existing systemd timer
runs the installed copy every five minutes. Credentials remain in the protected
server JSON file and must not enter Git.

The worker copies eligible images to the public primary S3 bucket and to the
private `hs-manacost-backups-3az/media-offload` bucket. The second copy is in a
different bucket but uses the same storage account. Both copies retain the exact
source bytes; WebP/AVIF remain adjacent sidecars. WordPress creates responsive
sizes before the optimizer processes the attachment.

Local cleanup is disabled unless the service has `HS_S3_DELETE_LOCAL=1`. When
enabled, `verified_cleanup.py` considers at most 100 files per run that are at
least seven days old. For each file it hashes the local bytes, streams the
primary object, restores the backup object into a temporary file, compares both
SHA256 digests, checks that the local file stayed unchanged, and only then
unlinks it. A failed upload, unreadable bucket or checksum mismatch retains the
local file. WordPress can hydrate an offloaded source in the supported editor
and optimizer contexts. Disk-capacity monitoring remains necessary for the
seven-day working set and temporary restorations.

Before enabling cleanup on production, confirm a fresh database backup and a
successful restore drill, test one disposable image through primary delivery
and backup restoration, and verify that its attachment metadata remains
readable. `HS_S3_DELETE_LOCAL=0` is the immediate rollback switch; it leaves
both S3 copies intact. Do not switch back to `rclone move`.

Verify the candidate with `python3 -m unittest tests.test_s3_offload_worker tests.test_s3_verified_cleanup -v`,
`bash -n ops/s3-offload/worker.sh`, and `make check`. After the exact commit passes
staging, install both scripts in the runtime directory and compare their SHA256
with the committed files. Keep a private copy of the previous installed files
for rollback. Let the next timer run, then check the service result, local
source, both S3 objects and public image on origin and both regional proxies.

Rollback first disables `HS_S3_DELETE_LOCAL`, then restores the previous scripts
from the private copy and runs `bash -n` before the next timer event. If the
previous script uses `rclone move`, rollback resumes deletion of local originals,
so use it only after reviewing the data-loss risk.
