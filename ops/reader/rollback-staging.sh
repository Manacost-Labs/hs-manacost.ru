#!/usr/bin/env bash
set -euo pipefail

readonly APP_ROOT='/srv/manacost-reader-staging'
readonly RELEASES_DIR="$APP_ROOT/releases"
readonly CURRENT_LINK="$APP_ROOT/current"
readonly PREVIOUS_LINK="$APP_ROOT/previous"

[[ "${1:-}" == '--apply' ]] || { echo 'Dry run: pass --apply to restore the known previous release link; no writes were made.'; exit 0; }
[[ $# -eq 1 ]] || { echo 'Usage: ops/reader/rollback-staging.sh [--apply]' >&2; exit 2; }
[[ -L "$PREVIOUS_LINK" ]] || { echo 'No known previous release link exists.' >&2; exit 1; }
previous_target="$(readlink -f -- "$PREVIOUS_LINK")"
if [[ "$previous_target" != "$RELEASES_DIR/"* ]] || ! sudo test -f "$previous_target/server.js"; then
  echo 'Previous link is not a validated staging release.' >&2; exit 1
fi

sudo ln -s "$previous_target" "${CURRENT_LINK}.rollback"
sudo mv -Tf "${CURRENT_LINK}.rollback" "$CURRENT_LINK"
echo 'Current release link restored. Database, keys, and service state were not changed.'
