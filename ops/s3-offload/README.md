# S3 media offload worker

`worker.sh` is the versioned source for
`/usr/local/libexec/hs-manacost-s3-offload/worker.sh`. The existing systemd timer
runs the installed copy every five minutes. Credentials remain in the protected
server JSON file and must not enter Git.

The worker copies eligible images to the primary S3 bucket. It preserves local
files until an independent media backup and a restore drill prove that deleting
them is safe. The WordPress uploads directory must therefore be included in
disk-capacity monitoring. Do not switch back to `rclone move` as a space fix.

Verify the candidate with `python3 -m unittest tests.test_s3_offload_worker -v`,
`bash -n ops/s3-offload/worker.sh`, and `make check`. After the exact commit passes
staging, install the script with mode `0755` at the path above and compare its
SHA256 with the committed file. Keep a private copy of the previous installed
script for rollback. Let the next timer run, then check the service result,
local source, S3 object and public image on origin and both regional proxies.

Rollback restores the previous installed script from the private copy and runs
`bash -n` before the next timer event. If the previous script uses `rclone move`,
rollback resumes deletion of local originals, so use it only after reviewing the
data-loss risk.
