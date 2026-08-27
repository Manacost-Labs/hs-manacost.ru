#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
ENV_FILE=/etc/hs-manacost/backup.env

[[ $(id -u) -eq 0 ]] || { echo "Run as root after creating the root-owned backup environment" >&2; exit 1; }
[[ -f "$ENV_FILE" ]] || { echo "Create $ENV_FILE from ops/backup/backup.env.example first" >&2; exit 1; }
[[ $(stat -c '%a' "$ENV_FILE") == 600 ]] || { echo "$ENV_FILE must have mode 0600" >&2; exit 1; }

install -m 0644 "$ROOT_DIR"/ops/systemd/hs-manacost-*.service /etc/systemd/system/
install -m 0644 "$ROOT_DIR"/ops/systemd/hs-manacost-*.timer /etc/systemd/system/
systemctl daemon-reload
systemctl enable --now hs-manacost-s3-backup.timer hs-manacost-restore-drill.timer
systemctl list-timers hs-manacost-s3-backup.timer hs-manacost-restore-drill.timer --no-pager
