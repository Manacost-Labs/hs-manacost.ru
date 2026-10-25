#!/usr/bin/env bash
set -euo pipefail
umask 077
# shellcheck source=ops/backup/storage-credentials.sh
source "$(dirname "${BASH_SOURCE[0]}")/storage-credentials.sh"

: "${HS_S3_SOURCE_REMOTE:?source rclone remote and bucket are required}"
: "${HS_S3_BACKUP_REMOTE:?independent backup rclone remote and bucket are required}"

source_remote=${HS_S3_SOURCE_REMOTE%/}
backup_remote=${HS_S3_BACKUP_REMOTE%/}
retention_days=${HS_S3_RETENTION_DAYS:-90}
workers=${HS_S3_WORKERS:-8}

[[ "$workers" =~ ^([1-9]|1[0-6])$ ]] \
    || { echo 'S3 workers must be an integer from 1 to 16' >&2; exit 2; }

[[ "$source_remote" == *:*/* ]] || { echo "S3 source must include remote, bucket and prefix" >&2; exit 2; }
[[ "$backup_remote" == *:*/* ]] || { echo "S3 backup must include remote, bucket and prefix" >&2; exit 2; }
if [[ ! "$retention_days" =~ ^[0-9]+$ ]] || (( retention_days < 7 )); then
    echo "S3 retention must be at least 7 days" >&2
    exit 2
fi

# The source and destination must not resolve to the same remote bucket. A second
# prefix in the production bucket is not a backup against bucket loss.
source_bucket=${source_remote#*:}
source_bucket=${source_bucket%%/*}
backup_bucket=${backup_remote#*:}
backup_bucket=${backup_bucket%%/*}
[[ "$source_bucket" != "$backup_bucket" ]] || { echo "S3 backup destination must use an independent bucket" >&2; exit 2; }

timestamp=$(date -u +%Y%m%dT%H%M%SZ)
current="$backup_remote/current"
versions="$backup_remote/versions/$timestamp"
temporary_directory=$(mktemp -d)
trap 'rm -rf "$temporary_directory"' EXIT

rclone lsf "$source_remote" --recursive --files-only --format ps --separator $'\t' \
    >"$temporary_directory/$timestamp.tsv"
[[ -s "$temporary_directory/$timestamp.tsv" ]] || { echo 'S3 source is empty; refusing snapshot' >&2; exit 1; }

sync_options=(--log-level NOTICE --stats 5m --stats-one-line --stats-log-level NOTICE)
if [[ ${HS_S3_DRY_RUN:-0} == 1 ]]; then
    sync_options=(--dry-run --log-level ERROR)
fi
rclone sync "$source_remote" "$current" \
    --backup-dir "$versions" \
    --s3-acl private \
    --s3-no-check-bucket \
    --server-side-across-configs \
    --checksum \
    --checkers "$workers" \
    --transfers "$workers" \
    --tpslimit 16 \
    --tpslimit-burst "$workers" \
    "${sync_options[@]}"

if [[ ${HS_S3_DRY_RUN:-0} == 1 ]]; then
    printf 'S3 snapshot dry-run complete; no objects or manifests changed\n'
    exit 0
fi

# Check every object (hash where available, otherwise size), then restore a
# representative original and compare bytes before publishing the manifest.
rclone check "$source_remote" "$current" --checkers "$workers" --tpslimit 16 --tpslimit-burst "$workers"
# Describe the verified destination, rather than the earlier live-source listing.
rclone lsf "$current" --recursive --files-only --format ps --separator $'\t' \
    >"$temporary_directory/$timestamp.tsv"
sample=${HS_S3_RESTORE_SAMPLE:-$(awk -F '\t' '$2 > 0 {print $1; exit}' "$temporary_directory/$timestamp.tsv")}
[[ -n "$sample" ]] || { echo 'No non-empty S3 restore sample' >&2; exit 1; }
rclone copyto "$source_remote/$sample" "$temporary_directory/source-sample"
rclone copyto "$current/$sample" "$temporary_directory/restored-sample"
[[ -s "$temporary_directory/source-sample" && -s "$temporary_directory/restored-sample" ]] || exit 1
cmp --silent "$temporary_directory/source-sample" "$temporary_directory/restored-sample" \
    || { echo 'S3 restore sample differs from source' >&2; exit 1; }
rclone copyto "$temporary_directory/$timestamp.tsv" "$backup_remote/manifests/$timestamp.tsv" --s3-acl private --s3-no-check-bucket
rclone copyto "$temporary_directory/$timestamp.tsv" "$backup_remote/manifests/latest.tsv" --s3-acl private --s3-no-check-bucket

# Retention is a separate operator decision, never enabled by default.
if [[ ${HS_S3_APPLY_RETENTION:-0} == 1 ]]; then
cutoff=$(date -u -d "$retention_days days ago" +%Y%m%dT%H%M%SZ)
while IFS= read -r directory; do
    directory=${directory%/}
    if [[ "$directory" =~ ^[0-9]{8}T[0-9]{6}Z$ ]] && [[ "$directory" < "$cutoff" ]]; then
        rclone purge "$backup_remote/versions/$directory"
    fi
done < <(rclone lsf "$backup_remote/versions" --dirs-only --max-depth 1 2>/dev/null || true)

fi

printf 'S3 snapshot verified: %s\n' "$timestamp"
