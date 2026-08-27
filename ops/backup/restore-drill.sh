#!/usr/bin/env bash
set -euo pipefail

: "${HS_RESTIC_REPOSITORY:?restic repository is required}"
: "${HS_RESTIC_PASSWORD_FILE:?restic password file is required}"
: "${HS_DB_BACKUP_GLOB:?database dump glob is required}"
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
db_password=$(openssl rand -hex 24)
cleanup() {
    "${docker_command[@]}" rm -f "$container_name" >/dev/null 2>&1 || true
    rm -rf "$temporary_directory"
}
trap cleanup EXIT

export RESTIC_REPOSITORY=$HS_RESTIC_REPOSITORY
export RESTIC_PASSWORD_FILE=$HS_RESTIC_PASSWORD_FILE

snapshot_arguments=(snapshots --latest 1 --json)
if [[ -n "${HS_RESTIC_SNAPSHOT_TAG:-}" ]]; then
    snapshot_arguments+=(--tag "$HS_RESTIC_SNAPSHOT_TAG")
fi
snapshot_json=$(restic "${snapshot_arguments[@]}")
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
done < <(restic ls --json "$snapshot_id" | jq -r 'select(.struct_type == "node" and .type == "file") | .path')
[[ -n "$db_path" ]] || { echo "No database dump matched HS_DB_BACKUP_GLOB" >&2; exit 1; }

restic restore "$snapshot_id" --target "$temporary_directory/restic" --include "$db_path"
restored_dump="$temporary_directory/restic/${db_path#/}"
[[ -s "$restored_dump" ]] || { echo "Restored database dump is empty" >&2; exit 1; }

"${docker_command[@]}" run -d --name "$container_name" --network none --tmpfs /var/lib/mysql \
    -e MARIADB_ROOT_PASSWORD="$db_password" \
    -e MARIADB_DATABASE=restore_test \
    mariadb:10.11@sha256:ce66c7be32a03aabe7241d0a10993a2db827ef652a35d25727d92a832ac8ef73 >/dev/null
for _ in $(seq 1 60); do
    if "${docker_command[@]}" exec "$container_name" mariadb-admin ping -uroot -p"$db_password" --silent >/dev/null 2>&1; then
        break
    fi
    sleep 2
done
"${docker_command[@]}" exec "$container_name" mariadb-admin ping -uroot -p"$db_password" --silent >/dev/null

if [[ "$restored_dump" == *.gz ]]; then
    gzip -dc -- "$restored_dump"
else
    sed -n '1,$p' -- "$restored_dump"
fi | "${docker_command[@]}" exec -i "$container_name" mariadb -uroot -p"$db_password" restore_test

table_prefix=${HS_DB_TABLE_PREFIX:-wp_}
[[ "$table_prefix" =~ ^[A-Za-z0-9_]+$ ]] || { echo "Unsafe WordPress table prefix" >&2; exit 2; }
for table in "${table_prefix}posts" "${table_prefix}postmeta" "${table_prefix}options"; do
    count=$("${docker_command[@]}" exec "$container_name" mariadb -N -uroot -p"$db_password" restore_test \
        -e "SELECT COUNT(*) FROM ${table};")
    [[ "$count" =~ ^[0-9]+$ ]] || { echo "Restored table check failed: $table" >&2; exit 1; }
done

backup_current=${HS_S3_BACKUP_REMOTE%/}/current
sample=$(rclone lsf "$backup_current" --recursive --files-only | LC_ALL=C sort | sed -n '1p')
[[ -n "$sample" ]] || { echo "Independent S3 backup is empty" >&2; exit 1; }
rclone copyto "$backup_current/$sample" "$temporary_directory/sample-a"
rclone copyto "$backup_current/$sample" "$temporary_directory/sample-b"
[[ -s "$temporary_directory/sample-a" ]] || { echo "S3 restore sample is empty" >&2; exit 1; }
cmp --silent "$temporary_directory/sample-a" "$temporary_directory/sample-b" \
    || { echo "S3 restore sample is not reproducible" >&2; exit 1; }

printf 'Restore drill passed: database tables and independent S3 sample are readable\n'
