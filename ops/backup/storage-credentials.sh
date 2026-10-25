#!/usr/bin/env bash
# Optional credential-file references; credentials never become command arguments.
configure_storage_remote() {
    local remote=$1 credentials=$2 prefix endpoint region access_key secret_key
    [[ -f "$credentials" && -r "$credentials" && $(stat -c '%a' "$credentials") == 600 ]] \
        || { echo 'Storage credential file must be readable and private (0600)' >&2; return 1; }
    prefix="RCLONE_CONFIG_${remote^^}"
    endpoint=$(jq -er '.endpoint | select(startswith("https://"))' "$credentials")
    region=$(jq -er '.region // "eu-west-par"' "$credentials")
    access_key=$(jq -er '.access_key | select(type == "string" and length > 0)' "$credentials")
    secret_key=$(jq -er '.secret_key | select(type == "string" and length > 0)' "$credentials")
    printf -v "${prefix}_TYPE" '%s' s3
    printf -v "${prefix}_PROVIDER" '%s' Other
    printf -v "${prefix}_ACCESS_KEY_ID" '%s' "$access_key"
    printf -v "${prefix}_SECRET_ACCESS_KEY" '%s' "$secret_key"
    printf -v "${prefix}_ENDPOINT" '%s' "$endpoint"
    printf -v "${prefix}_REGION" '%s' "${region,,}"
    printf -v "${prefix}_FORCE_PATH_STYLE" '%s' true
    export "${prefix}_TYPE" "${prefix}_PROVIDER" "${prefix}_ACCESS_KEY_ID" \
        "${prefix}_SECRET_ACCESS_KEY" "${prefix}_ENDPOINT" "${prefix}_REGION" "${prefix}_FORCE_PATH_STYLE"
}

if [[ -n ${HS_S3_CREDENTIALS_FILE:-} ]]; then
    configure_storage_remote hssource "$HS_S3_CREDENTIALS_FILE"
    configure_storage_remote hsbackup "${HS_S3_BACKUP_CREDENTIALS_FILE:-$HS_S3_CREDENTIALS_FILE}"
fi
if [[ -n ${HS_RESTIC_CREDENTIALS_FILE:-} ]]; then
    [[ -f "$HS_RESTIC_CREDENTIALS_FILE" && -r "$HS_RESTIC_CREDENTIALS_FILE" && $(stat -c '%a' "$HS_RESTIC_CREDENTIALS_FILE") == 600 ]] || { echo 'Restic credential file must be readable and private (0600)' >&2; return 1; }
    export AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY AWS_DEFAULT_REGION
    AWS_ACCESS_KEY_ID=$(jq -er '.access_key' "$HS_RESTIC_CREDENTIALS_FILE")
    AWS_SECRET_ACCESS_KEY=$(jq -er '.secret_key' "$HS_RESTIC_CREDENTIALS_FILE")
    AWS_DEFAULT_REGION=$(jq -er '.region // "eu-west-par"' "$HS_RESTIC_CREDENTIALS_FILE")
    AWS_DEFAULT_REGION=${AWS_DEFAULT_REGION,,}
fi
