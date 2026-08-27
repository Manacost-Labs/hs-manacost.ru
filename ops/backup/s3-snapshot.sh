#!/usr/bin/env bash
set -euo pipefail

: "${HS_S3_SOURCE_REMOTE:?source rclone remote and bucket are required}"
: "${HS_S3_BACKUP_REMOTE:?independent backup rclone remote and bucket are required}"

source_remote=${HS_S3_SOURCE_REMOTE%/}
backup_remote=${HS_S3_BACKUP_REMOTE%/}
retention_days=${HS_S3_RETENTION_DAYS:-90}

[[ "$source_remote" == *:*/* ]] || { echo "S3 source must include remote, bucket and prefix" >&2; exit 2; }
[[ "$backup_remote" == *:*/* ]] || { echo "S3 backup must include remote, bucket and prefix" >&2; exit 2; }
if [[ ! "$retention_days" =~ ^[0-9]+$ ]] || (( retention_days < 7 )); then
    echo "S3 retention must be at least 7 days" >&2
    exit 2
fi

# The source and destination must not resolve to the same remote bucket. A second
# prefix in the production bucket is not a backup against bucket loss.
source_bucket=${source_remote%%/*}
backup_bucket=${backup_remote%%/*}
[[ "$source_bucket" != "$backup_bucket" ]] || { echo "S3 backup destination must use an independent bucket" >&2; exit 2; }

timestamp=$(date -u +%Y%m%dT%H%M%SZ)
current="$backup_remote/current"
versions="$backup_remote/versions/$timestamp"
temporary_directory=$(mktemp -d)
trap 'rm -rf "$temporary_directory"' EXIT

rclone sync "$source_remote" "$current" \
    --backup-dir "$versions" \
    --checkers 16 \
    --transfers 8 \
    --fast-list \
    --log-level NOTICE

rclone lsf "$current" --recursive --files-only --format pst \
    >"$temporary_directory/$timestamp.tsv"
rclone copyto "$temporary_directory/$timestamp.tsv" "$backup_remote/manifests/$timestamp.tsv"

cutoff=$(date -u -d "$retention_days days ago" +%Y%m%dT%H%M%SZ)
while IFS= read -r directory; do
    directory=${directory%/}
    if [[ "$directory" =~ ^[0-9]{8}T[0-9]{6}Z$ ]] && [[ "$directory" < "$cutoff" ]]; then
        rclone purge "$backup_remote/versions/$directory"
    fi
done < <(rclone lsf "$backup_remote/versions" --dirs-only --max-depth 1 2>/dev/null || true)

printf 'S3 snapshot verified: %s\n' "$timestamp"
