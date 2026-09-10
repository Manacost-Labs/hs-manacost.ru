#!/usr/bin/env bash
set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
  echo 'Run this installer as root.' >&2
  exit 1
fi

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd -P)"
source_path="$repo_root/ops/ci/hs-manacost-ci-deploy"
target_path='/usr/local/sbin/hs-manacost-ci-deploy'

[[ -f "$source_path" ]] || {
  echo 'Deployment helper source is missing.' >&2
  exit 1
}

install -o root -g root -m 0755 "$source_path" "$target_path"
echo "Installed $target_path from $source_path"
