#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../.." && pwd)"
probe=${1:-}

echo "HS Manacost read-only health snapshot"
echo "Primary: hs-manacost.ru"
echo "Mirror: hs-manacost.com"
echo "Staging: test.hs-manacost.ru"
echo "Moscow proxy: configured production route"
echo "Novosibirsk proxy: configured production route"
echo "Source commit: $(git -C "$repo_root" rev-parse --short HEAD 2>/dev/null || echo unavailable)"

if command -v systemctl >/dev/null 2>&1; then
  for unit in hs-manacost-s3-offload.timer server-backup-core.timer server-backup-check.timer; do
    state="$(systemctl show "$unit" --property=ActiveState --value 2>/dev/null || true)"
    echo "$unit: ${state:-unavailable}"
  done
else
  echo "systemd: unavailable"
fi

if [[ "$probe" == "--probe" ]]; then
  "$repo_root/ops/smoke-check.sh" staging
  "$repo_root/ops/smoke-check.sh" production
elif [[ -n "$probe" ]]; then
  echo "Usage: $0 [--probe]" >&2
  exit 2
else
  echo "HTTP probes: skipped (pass --probe to run bounded project smoke checks)"
fi
