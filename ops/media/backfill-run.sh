#!/usr/bin/env bash
# One nightly sidecar backfill batch; JSON lines go to the journal.
# A "complete" event leaves a marker that makes the service disable its timer.
set -euo pipefail

site=${HS_MEDIA_BACKFILL_SITE:-/var/www/koloda/data/www/hs-manacost.ru}
runner=${HS_MEDIA_BACKFILL_RUNNER:-/usr/local/libexec/hs-manacost-media-backfill/backfill-sidecars.php}
state=${STATE_DIRECTORY:-/var/lib/hs-manacost-media-backfill}
wp_bin=${HS_MEDIA_BACKFILL_WP:-/usr/local/bin/wp}

# stdout is the journal under systemd; the stderr socket there cannot be reopened by path.
log="$state/last-run.jsonl"
"$wp_bin" --path="$site" eval-file "$runner" \
    years=2021-2025 limit=1000 max_load=10 min_free_gb=60 max_minutes=55 2>&1 \
    | grep --line-buffered -v 'Undefined array key "REQUEST_URI"' | tee "$log"

if grep -q '"event":"complete"' "$log"; then
    touch "$state/complete"
fi
