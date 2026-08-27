#!/usr/bin/env bash
set -euo pipefail

check_unit() {
    local timer=$1 service=$2 maximum_age_hours=$3
    systemctl is-enabled --quiet "$timer"
    systemctl is-active --quiet "$timer"
    [[ $(systemctl show "$service" -p Result --value) == success ]] \
        || { echo "$service last result is not successful" >&2; return 1; }
    local started epoch age
    started=$(systemctl show "$service" -p ExecMainStartTimestamp --value)
    [[ -n "$started" && "$started" != n/a ]] || { echo "$service has never run" >&2; return 1; }
    epoch=$(date -d "$started" +%s)
    age=$(( ($(date +%s) - epoch) / 3600 ))
    (( age <= maximum_age_hours )) \
        || { echo "$service is stale (${age}h > ${maximum_age_hours}h)" >&2; return 1; }
    printf '%s: successful, %sh old\n' "$service" "$age"
}

check_unit server-backup-core.timer server-backup-core.service 36
check_unit server-backup-check.timer server-backup-check.service 840

if [[ -f /etc/hs-manacost/backup.env ]]; then
    check_unit hs-manacost-s3-backup.timer hs-manacost-s3-backup.service 48
    check_unit hs-manacost-restore-drill.timer hs-manacost-restore-drill.service 840
else
    echo "Independent S3 backup destination is not configured at /etc/hs-manacost/backup.env" >&2
    exit 2
fi
