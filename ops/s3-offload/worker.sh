#!/usr/bin/env bash
set -euo pipefail

umask 077

credentials_file=${HS_S3_CREDENTIALS_FILE:-/home/debian/.config/hs-manacost/object-storage-media.json}
rclone_bin=${HS_S3_RCLONE_BIN:-/usr/bin/rclone}
curl_bin=${HS_S3_CURL_BIN:-/usr/bin/curl}
uploads_dir=${HS_S3_UPLOADS_DIR:-/var/www/koloda/data/www/hs-manacost.ru/wp-content/uploads}
webpc_dir=${HS_S3_WEBPC_DIR:-/var/www/koloda/data/www/hs-manacost.ru/wp-content/uploads-webpc}
remote_uploads=${HS_S3_REMOTE_UPLOADS:-ovh:hs-manacost-media-3az/wp-content/uploads}
remote_webpc=${HS_S3_REMOTE_WEBPC:-ovh:hs-manacost-media-3az/wp-content/uploads-webpc}
backup_uploads=${HS_S3_BACKUP_UPLOADS:-ovh:hs-manacost-backups-3az/media-offload/wp-content/uploads}
backup_webpc=${HS_S3_BACKUP_WEBPC:-ovh:hs-manacost-backups-3az/media-offload/wp-content/uploads-webpc}
minimum_age=${HS_S3_MIN_AGE:-15m}
lock_file=${HS_S3_LOCK_FILE:-/run/lock/hs-manacost-s3-offload.lock}
healthcheck_path=/wp-content/uploads/2025/12/cropped-hs-manacost.ru_-1-32x32.png
if [[ ${HS_S3_HEALTHCHECK_URL+x} ]]; then
    healthcheck_urls=("$HS_S3_HEALTHCHECK_URL")
else
    healthcheck_urls=(
        "https://hs-manacost.ru${healthcheck_path}"
        "https://hs-manacost.com${healthcheck_path}"
    )
fi

if [[ ! -r "$credentials_file" ]]; then
    echo "S3 credentials are not readable: $credentials_file" >&2
    exit 1
fi

if [[ ! -x "$rclone_bin" ]]; then
    echo "rclone is not executable: $rclone_bin" >&2
    exit 1
fi

verify_remote_delivery() {
    local headers
    local healthcheck_url
    local attempted=false

    if [[ ! -x "$curl_bin" ]]; then
        echo "curl is not executable: $curl_bin" >&2
        exit 1
    fi

    for healthcheck_url in "${healthcheck_urls[@]}"; do
        [[ -n "$healthcheck_url" ]] || continue
        attempted=true

        if headers=$(
            "$curl_bin" --silent --show-error --fail --head \
                --max-time 20 \
                --header 'Cache-Control: no-cache' \
                "${healthcheck_url}?hs-s3-health=$(date +%s)"
        ) && grep -Eiq '^content-type:[[:space:]]*image/' <<<"$headers"; then
            return 0
        fi
    done

    if [[ "$attempted" == false ]]; then
        return 0
    fi

    echo 'S3 delivery health checks failed on both domains; refusing to remove local images.' >&2
    exit 1
}

mkdir -p "$(dirname "$lock_file")"
exec 9>"$lock_file"
if ! flock -n 9; then
    echo 'Another S3 offload worker is already running.'
    exit 0
fi

export RCLONE_CONFIG_OVH_TYPE=s3
export RCLONE_CONFIG_OVH_PROVIDER=Other
export RCLONE_CONFIG_OVH_ACCESS_KEY_ID
export RCLONE_CONFIG_OVH_SECRET_ACCESS_KEY
export RCLONE_CONFIG_OVH_ENDPOINT
export RCLONE_CONFIG_OVH_REGION=eu-west-par
export RCLONE_CONFIG_OVH_FORCE_PATH_STYLE=true

RCLONE_CONFIG_OVH_ACCESS_KEY_ID=$(jq -er '.access_key' "$credentials_file")
RCLONE_CONFIG_OVH_SECRET_ACCESS_KEY=$(jq -er '.secret_key' "$credentials_file")
RCLONE_CONFIG_OVH_ENDPOINT=$(jq -er '.endpoint' "$credentials_file")

verify_remote_delivery

image_filter=(
    --ignore-case
    --include '*.{avif,bmp,gif,heic,heif,ico,jpeg,jpg,png,svg,tif,tiff,webp}'
)

copy_images_to_primary() {
    local source_dir=$1
    local destination=$2

    if [[ ! -d "$source_dir" ]]; then
        return 0
    fi

    "$rclone_bin" copy "$source_dir" "$destination" \
        --s3-no-check-bucket \
        --s3-acl public-read \
        --no-traverse \
        --ignore-existing \
        "${image_filter[@]}" \
        --min-age "$minimum_age" \
        --transfers 16 \
        --checkers 32 \
        --s3-upload-concurrency 2 \
        --s3-chunk-size 16M \
        --bwlimit 70M \
        --retries 10 \
        --low-level-retries 20 \
        --stats 1m \
        --stats-one-line-date \
        --stats-log-level NOTICE \
        --log-level NOTICE
}

copy_images_to_primary "$uploads_dir" "$remote_uploads"
copy_images_to_primary "$webpc_dir" "$remote_webpc"

copy_images_to_backup() {
    local source_dir=$1
    local destination=$2

    [[ -d "$source_dir" ]] || return 0
    "$rclone_bin" copy "$source_dir" "$destination" \
        --s3-no-check-bucket \
        --s3-acl private \
        --no-traverse \
        --ignore-existing \
        "${image_filter[@]}" \
        --min-age "$minimum_age" \
        --transfers 8 \
        --checkers 16 \
        --s3-upload-concurrency 2 \
        --s3-chunk-size 16M \
        --bwlimit 35M \
        --retries 10 \
        --low-level-retries 20 \
        --stats 1m \
        --stats-one-line-date \
        --stats-log-level NOTICE \
        --log-level NOTICE
}

copy_images_to_backup "$uploads_dir" "$backup_uploads"
copy_images_to_backup "$webpc_dir" "$backup_webpc"

HS_S3_BACKUP_UPLOADS="$backup_uploads" \
HS_S3_BACKUP_WEBPC="$backup_webpc" \
    python3 "$(dirname "$0")/verified_cleanup.py"
