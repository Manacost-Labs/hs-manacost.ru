#!/usr/bin/env bash
set -euo pipefail

readonly APP_ROOT="${MANACOST_READER_STAGING_ROOT:-/srv/manacost-reader-staging}"
readonly RELEASES_DIR="$APP_ROOT/releases"
readonly CURRENT_LINK="$APP_ROOT/current"
readonly PREVIOUS_LINK="$APP_ROOT/previous"
readonly ENV_FILE="${MANACOST_READER_STAGING_ENV_FILE:-/etc/manacost-reader-staging/service.env}"
readonly UNIT_NAME='manacost-reader-staging.service'

usage() {
  cat <<'EOF'
Usage: ops/reader/release-staging.sh --sha <full-commit-sha> [--apply]

Builds only the isolated reader staging artifact. Without --apply it performs
validation only and makes no filesystem or service-manager changes.
EOF
}

apply=false
expected_sha=''
while [[ $# -gt 0 ]]; do
  case "$1" in
    --apply) apply=true ;;
    --sha) shift; expected_sha="${1:-}" ;;
    -h|--help) usage; exit 0 ;;
    *) usage; exit 2 ;;
  esac
  shift
done

[[ "$expected_sha" =~ ^[0-9a-f]{40}$ ]] || { echo 'A full lowercase 40-character --sha is required.' >&2; exit 2; }
repo_root="$(git rev-parse --show-toplevel)"
head_sha="$(git rev-parse HEAD)"
[[ "$repo_root" == */hs-manacost-reader-activation ]] || { echo 'Refusing non-staging source checkout.' >&2; exit 1; }
[[ "$APP_ROOT" == */manacost-reader-staging ]] || { echo 'Refusing non-staging release root.' >&2; exit 1; }
[[ "$APP_ROOT" != *production* && "$APP_ROOT" != '/srv/manacost-reader' ]] || { echo 'Refusing a production-like release root.' >&2; exit 1; }
[[ "$ENV_FILE" == */manacost-reader-staging/service.env ]] || { echo 'Refusing non-staging environment file path.' >&2; exit 1; }

assert_no_symlink_components() {
  local path="$1" component=''
  IFS='/' read -r -a parts <<< "${path#/}"
  for part in "${parts[@]}"; do
    component+="/$part"
    [[ ! -L "$component" ]] || { echo "Refusing symlinked path component: $component" >&2; exit 1; }
  done
}
assert_no_symlink_components "$APP_ROOT"
assert_no_symlink_components "$RELEASES_DIR"
assert_no_symlink_components "$ENV_FILE"
if [[ "$apply" == true && ( "$APP_ROOT" != /srv/manacost-reader-staging || "$ENV_FILE" != /etc/manacost-reader-staging/service.env ) ]]; then
  echo 'Apply requires the exact canonical staging targets.' >&2; exit 1
fi
[[ ! -e "$APP_ROOT" || -d "$APP_ROOT" ]] || { echo 'Release root must be a directory.' >&2; exit 1; }
[[ ! -e "$RELEASES_DIR" || -d "$RELEASES_DIR" ]] || { echo 'Release store must be a directory.' >&2; exit 1; }
[[ -z "$(git status --porcelain)" ]] || { echo 'Source checkout is dirty.' >&2; exit 1; }
[[ "$head_sha" == "$expected_sha" ]] || { echo 'Expected SHA does not match HEAD.' >&2; exit 1; }
sudo test -f "$ENV_FILE" || { echo 'Required environment file is absent.' >&2; exit 1; }

source_dir="$repo_root/services/reader"
[[ -f "$source_dir/package-lock.json" && -f "$source_dir/server.js" ]] || { echo 'Reader source is incomplete.' >&2; exit 1; }
for command in install rsync npm node systemctl sudo; do
  command -v "$command" >/dev/null || { echo "Required command is unavailable: $command" >&2; exit 1; }
done

release_dir="$RELEASES_DIR/$expected_sha"
[[ ! -e "$release_dir" && ! -L "$release_dir" ]] || { echo 'Release directory already exists; artifacts are immutable.' >&2; exit 1; }

validate_release_link() {
  local link="$1" target
  [[ ! -e "$link" && ! -L "$link" ]] && return 0
  [[ -L "$link" ]] || { echo "Release link must be a symlink: $link" >&2; exit 1; }
  target="$(readlink -f -- "$link")"
  if [[ "$target" != "$RELEASES_DIR/"* || -L "$target" ]] || ! sudo test -f "$target/server.js" || ! sudo test ! -L "$target/server.js"; then
    echo "Refusing unsafe release link: $link" >&2; exit 1
  fi
}
validate_release_link "$CURRENT_LINK"
validate_release_link "$PREVIOUS_LINK"

echo "Validated staging reader SHA: $expected_sha"
if [[ "$apply" != true ]]; then
  echo 'Dry run complete; add --apply to create the artifact and install the unit. No writes were made.'
  exit 0
fi

sudo install -d -m 0755 -o root -g root "$APP_ROOT" "$RELEASES_DIR"
sudo install -d -m 0750 -o manacost-reader-staging -g manacost-reader-staging "$release_dir"
sudo -u manacost-reader-staging rsync -a --chmod=Du=rwx,Dg=rx,Do=,Fu=rw,Fg=r,Fo= "$source_dir/" "$release_dir/"
sudo -u manacost-reader-staging npm ci --ignore-scripts --omit=dev --prefix "$release_dir"
sudo chown -R root:manacost-reader-staging "$release_dir"
sudo find "$release_dir" -type d -exec chmod 0550 {} +
sudo find "$release_dir" -type f -exec chmod 0440 {} +
sudo install -m 0644 "$repo_root/ops/reader/$UNIT_NAME" "/etc/systemd/system/$UNIT_NAME"

if [[ -L "$CURRENT_LINK" ]]; then
  current_target="$(readlink -f -- "$CURRENT_LINK")"
  sudo ln -s "$current_target" "${PREVIOUS_LINK}.new"
  sudo mv -Tf "${PREVIOUS_LINK}.new" "$PREVIOUS_LINK"
fi
sudo ln -s "$release_dir" "${CURRENT_LINK}.new"
sudo mv -Tf "${CURRENT_LINK}.new" "$CURRENT_LINK"
sudo systemctl daemon-reload
echo 'Artifact and unit installed. Service was deliberately not started or restarted.'
