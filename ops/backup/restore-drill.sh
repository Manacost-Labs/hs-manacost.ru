#!/usr/bin/env bash
set -euo pipefail
umask 077
# The nested container shell expands its own password and positional arguments.
# shellcheck source=ops/backup/storage-credentials.sh
source "$(dirname "${BASH_SOURCE[0]}")/storage-credentials.sh"

: "${HS_RESTIC_REPOSITORY:?restic repository is required}"
: "${HS_RESTIC_PASSWORD_FILE:?restic password file is required}"
: "${HS_DB_BACKUP_GLOB:?database dump glob is required}"
: "${HS_S3_SOURCE_REMOTE:?original S3 remote is required for comparison}"
: "${HS_S3_BACKUP_REMOTE:?independent S3 backup remote is required}"

command -v restic >/dev/null
command -v rclone >/dev/null
command -v jq >/dev/null

docker_command=(docker)
if ! docker info >/dev/null 2>&1; then
    docker_command=(sudo -n docker)
fi

temporary_directory=$(mktemp -d)
container_name="hs-manacost-restore-drill-$$"
restore_database=${HS_DB_RESTORE_DATABASE:-restore_test}
[[ "$restore_database" =~ ^[A-Za-z0-9_]+$ ]] || { echo 'Unsafe restore database name' >&2; exit 2; }
printf 'MARIADB_ROOT_PASSWORD=%s\nMARIADB_DATABASE=%s\n' "$(openssl rand -hex 24)" "$restore_database" >"$temporary_directory/container.env"
cleanup() {
    "${docker_command[@]}" rm -f "$container_name" >/dev/null 2>&1 || true
    rm -rf "$temporary_directory"
}
trap cleanup EXIT

export RESTIC_REPOSITORY=$HS_RESTIC_REPOSITORY
export RESTIC_PASSWORD_FILE=$HS_RESTIC_PASSWORD_FILE

snapshot_arguments=(snapshots --json)
if [[ -n "${HS_RESTIC_SNAPSHOT_TAG:-}" ]]; then
    snapshot_arguments+=(--tag "$HS_RESTIC_SNAPSHOT_TAG")
fi
restic_command=(restic)
if [[ "$HS_RESTIC_REPOSITORY" == s3:* && -n ${AWS_DEFAULT_REGION:-} ]]; then
    restic_command+=(-o "s3.region=$AWS_DEFAULT_REGION" -o s3.bucket-lookup=path)
fi
snapshot_json=$("${restic_command[@]}" "${snapshot_arguments[@]}" | jq '[max_by(.time)]')
snapshot_time=$(jq -er '.[0].time' <<<"$snapshot_json")
snapshot_id=$(jq -er '.[0].id' <<<"$snapshot_json")
snapshot_epoch=$(date -u -d "$snapshot_time" +%s)
maximum_age_hours=${HS_DB_MAXIMUM_AGE_HOURS:-36}
(( $(date -u +%s) - snapshot_epoch <= maximum_age_hours * 3600 )) \
    || { echo "Latest database backup is older than ${maximum_age_hours}h" >&2; exit 1; }

db_path=''
while IFS= read -r candidate; do
    # Intentional shell glob matching against the operator-owned allowlist.
    # shellcheck disable=SC2053
    if [[ "$candidate" == $HS_DB_BACKUP_GLOB ]]; then
        db_path=$candidate
    fi
done < <("${restic_command[@]}" ls --json "$snapshot_id" | jq -r 'select(.struct_type == "node" and .type == "file") | .path')
[[ -n "$db_path" ]] || { echo "No database dump matched HS_DB_BACKUP_GLOB" >&2; exit 1; }

"${restic_command[@]}" restore "$snapshot_id" --target "$temporary_directory/restic" --include "$db_path"
restored_dump="$temporary_directory/restic/${db_path#/}"
[[ -s "$restored_dump" ]] || { echo "Restored database dump is empty" >&2; exit 1; }

"${docker_command[@]}" run -d --name "$container_name" --network none --cpus=1 --memory=12g --pids-limit=256 \
    --tmpfs /var/lib/mysql:rw,size=10g --env-file "$temporary_directory/container.env" \
    mariadb:10.11@sha256:ce66c7be32a03aabe7241d0a10993a2db827ef652a35d25727d92a832ac8ef73 >/dev/null
for _ in $(seq 1 60); do
    # shellcheck disable=SC2016
    if "${docker_command[@]}" exec "$container_name" sh -c 'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb-admin ping -uroot --silent' >/dev/null 2>&1; then
        break
    fi
    sleep 2
done
# shellcheck disable=SC2016
"${docker_command[@]}" exec "$container_name" sh -c 'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb-admin ping -uroot --silent' >/dev/null

# shellcheck disable=SC2016
if ! {
if [[ "$restored_dump" == *.gz ]]; then
    gzip -dc -- "$restored_dump"
else
    sed -n '1,$p' -- "$restored_dump"
fi
} | "${docker_command[@]}" exec -i "$container_name" sh -c \
    'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb -uroot "$1"' sh "$restore_database" \
    2>"$temporary_directory/import-error.log"; then
    echo 'Isolated database import failed; SQL output remains private' >&2
    exit 1
fi

table_prefix=${HS_DB_TABLE_PREFIX:-wp_}
[[ "$table_prefix" =~ ^[A-Za-z0-9_]+$ ]] || { echo "Unsafe WordPress table prefix" >&2; exit 2; }
for table in "${table_prefix}posts" "${table_prefix}postmeta" "${table_prefix}options"; do
    # shellcheck disable=SC2016
    count=$("${docker_command[@]}" exec "$container_name" sh -c \
        'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb -N -uroot "$1" -e "$2"' sh \
        "$restore_database" "SELECT COUNT(*) FROM ${table};")
    [[ "$count" =~ ^[0-9]+$ && "$count" -gt 0 ]] || { echo "Restored table check failed: $table" >&2; exit 1; }
done

backup_current=${HS_S3_BACKUP_REMOTE%/}/current
sample=${HS_S3_RESTORE_SAMPLE:-$(rclone cat "${HS_S3_BACKUP_REMOTE%/}/manifests/latest.tsv" | awk -F '\t' '$2 > 0 {print $1; exit}')}
[[ -n "$sample" ]] || { echo "Independent S3 backup is empty" >&2; exit 1; }
rclone copyto "${HS_S3_SOURCE_REMOTE%/}/$sample" "$temporary_directory/sample-a"
rclone copyto "$backup_current/$sample" "$temporary_directory/sample-b"
[[ -s "$temporary_directory/sample-a" ]] || { echo "S3 restore sample is empty" >&2; exit 1; }
cmp --silent "$temporary_directory/sample-a" "$temporary_directory/sample-b" \
    || { echo "S3 restored sample differs from original source" >&2; exit 1; }

printf 'Restore drill passed: database tables and independent S3 sample match the source\n'
