#!/usr/bin/env bash
# Cron entrypoint: no overlapping runs and no silent timeout/exec failures.
set -u
umask 027
case "${1:-}" in
    hs)
        command_path=/usr/local/sbin/hs-manacost-healthcheck
        log_path=/var/log/hs-manacost-healthcheck.log
        ;;
    koloda)
        command_path=/usr/local/sbin/koloda-healthcheck.sh
        log_path=/var/log/koloda-healthcheck.log
        ;;
    *) exit 2 ;;
esac
case "${2:-}" in
    quick) limit=40s; arguments=(--quick) ;;
    full) limit=240s; arguments=() ;;
    *) exit 2 ;;
esac
# Directory is provisioned root:root 0700; never use a predictable /tmp file
# as a root writer. Independent site locks also prevent full/quick overlap.
flock -n -E 75 "/run/lock/manacost-monitoring/$1.lock" \
    timeout --kill-after=5s "$limit" "$command_path" "${arguments[@]}"
rc=$?
case "$rc" in
    0) exit 0 ;;
    75) level=WARN; reason=overlap_skipped ;;
    1) level=FAIL; reason=check_failed ;;
    *) level=UNKNOWN; reason=runner_failed ;;
esac
printf '%s %s runner mode=%s reason=%s exit=%s\n' \
    "$(date -u '+%Y-%m-%dT%H:%M:%SZ')" "$level" "$2" "$reason" "$rc" >> "$log_path"
logger -t manacost-healthcheck "$level site=$1 mode=$2 reason=$reason exit=$rc"
exit "$rc"
