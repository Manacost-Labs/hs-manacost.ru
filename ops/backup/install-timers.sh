#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
ENV_FILE=/etc/hs-manacost/backup.env

[[ $(id -u) -eq 0 ]] || { echo "Run as root after creating the root-owned backup environment" >&2; exit 1; }
[[ -f "$ENV_FILE" ]] || { echo "Create $ENV_FILE from ops/backup/backup.env.example first" >&2; exit 1; }
[[ $(stat -c '%a' "$ENV_FILE") == 600 ]] || { echo "$ENV_FILE must have mode 0600" >&2; exit 1; }

[[ $(stat -c '%u' "$ENV_FILE") == 0 ]] || { echo "$ENV_FILE must be root-owned" >&2; exit 1; }
[[ -z $(git -C "$ROOT_DIR" status --porcelain) ]] || { echo "Install from a clean committed source checkout" >&2; exit 1; }
for service in hs-manacost-s3-backup.service hs-manacost-restore-drill.service; do
    if systemctl is-active --quiet "$service"; then
        echo "Wait for the active backup or restore drill before replacing scripts" >&2
        exit 1
    fi
done
install -d -m 0755 /usr/local/libexec/hs-manacost-backup
for script in s3-snapshot.sh restore-drill.sh storage-credentials.sh; do
    install -m 0755 "$ROOT_DIR/ops/backup/$script" /usr/local/libexec/hs-manacost-backup/
done
git -C "$ROOT_DIR" rev-parse HEAD >/usr/local/libexec/hs-manacost-backup/source-revision
chmod 0644 /usr/local/libexec/hs-manacost-backup/source-revision
for unit in hs-manacost-s3-backup.service hs-manacost-s3-backup.timer hs-manacost-restore-drill.service hs-manacost-restore-drill.timer; do
    install -m 0644 "$ROOT_DIR/ops/systemd/$unit" /etc/systemd/system/
done
systemctl daemon-reload
systemctl enable --now hs-manacost-s3-backup.timer hs-manacost-restore-drill.timer
systemctl list-timers hs-manacost-s3-backup.timer hs-manacost-restore-drill.timer --no-pager
