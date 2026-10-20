#!/usr/bin/env bash
# One nightly sidecar backfill batch; JSON lines go to the journal.
# A "complete" event leaves a marker that makes the service disable its timer.
set -euo pipefail

site=${HS_MEDIA_BACKFILL_SITE:-/var/www/koloda/data/www/hs-manacost.ru}
runner=${HS_MEDIA_BACKFILL_RUNNER:-/usr/local/libexec/hs-manacost-media-backfill/backfill-sidecars.php}
state=${STATE_DIRECTORY:-/var/lib/hs-manacost-media-backfill}
wp_bin=${HS_MEDIA_BACKFILL_WP:-/usr/local/bin/wp}

output=$("$wp_bin" --path="$site" eval-file "$runner" \
    years=2021-2025 limit=1000 max_load=10 min_free_gb=60 max_minutes=55 2>&1 \
    | grep -v 'Undefined array key "REQUEST_URI"' | tee /dev/stderr)

if grep -q '"event":"complete"' <<<"$output"; then
    touch "$state/complete"
fi
