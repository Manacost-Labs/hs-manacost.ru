#!/usr/bin/env bash
set -euo pipefail

optimizer_repo=/srv/projects/wordpress/hs-local-image-optimizer

echo "Media pipeline status (read-only)"
if [[ -d "$optimizer_repo/.git" ]]; then
  optimizer_commit="$(git -C "$optimizer_repo" rev-parse --short HEAD 2>/dev/null || true)"
  optimizer_changes="$(git -C "$optimizer_repo" status --short 2>/dev/null | wc -l)"
  echo "hs-local-image-optimizer: present commit=${optimizer_commit:-unknown} changed_paths=$optimizer_changes"
else
  echo "hs-local-image-optimizer: unavailable on this host"
fi

for binary in cwebp avifenc; do
  if command -v "$binary" >/dev/null 2>&1; then
    echo "$binary: available"
  else
    echo "$binary: unavailable"
  fi
done

for unit in hs-manacost-s3-offload.timer hs-manacost-s3-offload.service; do
  if command -v systemctl >/dev/null 2>&1 && systemctl show "$unit" >/dev/null 2>&1; then
    state="$(systemctl show "$unit" --property=ActiveState --value 2>/dev/null || true)"
    result="$(systemctl show "$unit" --property=Result --value 2>/dev/null || true)"
    echo "$unit: state=${state:-unknown} result=${result:-n/a}"
  else
    echo "$unit: unavailable on this host"
  fi
done

echo "Safety: source images are immutable; verify SHA256, dimensions, MIME and S3 before cleanup."
