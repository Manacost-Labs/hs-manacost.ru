#!/usr/bin/env bash
set -euo pipefail

readonly SOURCE_ROOT="${HEARTHPULSE_IDENTITY_SOURCE_ROOT:-/srv/projects/tasks/hearthpulse-reader-staging-20260908}"
readonly APP_ROOT="${HEARTHPULSE_IDENTITY_STAGING_ROOT:-/srv/hearthpulse-identity-staging}"
readonly RELEASES_DIR="$APP_ROOT/releases"
readonly CURRENT_LINK="$APP_ROOT/current"
readonly PREVIOUS_LINK="$APP_ROOT/previous"
readonly ENV_FILE="${HEARTHPULSE_IDENTITY_ENV_FILE:-/etc/hearthpulse-identity-staging/service.env}"
readonly UNIT_NAME='hearthpulse-identity-staging.service'

usage() {
  cat <<'EOF'
Usage: ops/reader/release-identity-staging.sh --sha <full-commit-sha> [--apply]

Without --apply this validates an isolated HearthPulse identity artifact and
makes no writes. It never reads or changes the service environment file.
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

assert_no_symlink_components() {
  local path="$1" component='' part
  local -a parts
  IFS='/' read -r -a parts <<< "${path#/}"
  for part in "${parts[@]}"; do
    component+="/$part"
    [[ ! -L "$component" ]] || { echo "Refusing symlinked path component: $component" >&2; exit 1; }
  done
}

[[ "$SOURCE_ROOT" == */hearthpulse-reader-staging-20260908 ]] || { echo 'Refusing non-staging identity source checkout.' >&2; exit 1; }
[[ "$APP_ROOT" == */hearthpulse-identity-staging ]] || { echo 'Refusing non-staging identity release root.' >&2; exit 1; }
[[ "$APP_ROOT" != *production* && "$APP_ROOT" != '/srv/hearthpulse-identity' ]] || { echo 'Refusing a production-like identity release root.' >&2; exit 1; }
[[ "$ENV_FILE" == */hearthpulse-identity-staging/service.env ]] || { echo 'Refusing non-staging identity environment file path.' >&2; exit 1; }
assert_no_symlink_components "$SOURCE_ROOT"
assert_no_symlink_components "$APP_ROOT"
assert_no_symlink_components "$RELEASES_DIR"
assert_no_symlink_components "$ENV_FILE"
if [[ "$apply" == true && ( "$SOURCE_ROOT" != /srv/projects/tasks/hearthpulse-reader-staging-20260908 || "$APP_ROOT" != /srv/hearthpulse-identity-staging || "$ENV_FILE" != /etc/hearthpulse-identity-staging/service.env ) ]]; then
  echo 'Apply requires the exact canonical identity staging targets.' >&2; exit 1
fi
git -C "$SOURCE_ROOT" rev-parse --is-inside-work-tree >/dev/null 2>&1 || { echo 'Identity source checkout is unavailable.' >&2; exit 1; }
[[ ! -e "$APP_ROOT" || -d "$APP_ROOT" ]] || { echo 'Identity release root must be a directory.' >&2; exit 1; }
[[ ! -e "$RELEASES_DIR" || -d "$RELEASES_DIR" ]] || { echo 'Identity release store must be a directory.' >&2; exit 1; }
[[ -z "$(git -C "$SOURCE_ROOT" status --porcelain)" ]] || { echo 'Identity source checkout is dirty.' >&2; exit 1; }
head_sha="$(git -C "$SOURCE_ROOT" rev-parse HEAD)"
[[ "$head_sha" == "$expected_sha" ]] || { echo 'Expected SHA does not match identity source HEAD.' >&2; exit 1; }
sudo test -f "$ENV_FILE" || { echo 'Required identity environment file is absent.' >&2; exit 1; }
for required in dist/index.html build/server/index.js package.json package-lock.json; do
  [[ -f "$SOURCE_ROOT/$required" ]] || { echo "Identity source is incomplete: $required" >&2; exit 1; }
done
for command in install rsync npm node systemctl sudo find; do
  command -v "$command" >/dev/null || { echo "Required command is unavailable: $command" >&2; exit 1; }
done

release_dir="$RELEASES_DIR/$expected_sha"
[[ ! -e "$release_dir" && ! -L "$release_dir" ]] || { echo 'Identity release directory already exists; artifacts are immutable.' >&2; exit 1; }
validate_release_link() {
  local link="$1" target
  [[ ! -e "$link" && ! -L "$link" ]] && return 0
  [[ -L "$link" ]] || { echo "Identity release link must be a symlink: $link" >&2; exit 1; }
  target="$(readlink -f -- "$link")"
  if [[ "$target" != "$RELEASES_DIR/"* || -L "$target" ]] || ! sudo test -f "$target/dist/index.html" || ! sudo test -f "$target/build/server/index.js"; then
    echo "Refusing unsafe identity release link: $link" >&2; exit 1
  fi
}
validate_release_link "$CURRENT_LINK"
validate_release_link "$PREVIOUS_LINK"

echo "Validated identity staging SHA: $expected_sha"
if [[ "$apply" != true ]]; then
  echo 'Dry run complete; add --apply to create the identity artifact and install the unit. No writes were made.'
  exit 0
fi

sudo install -d -m 0755 -o root -g root "$APP_ROOT" "$RELEASES_DIR"
sudo install -d -m 0750 -o hearthpulse-identity-staging -g hearthpulse-identity-staging "$release_dir"
sudo install -d -m 0700 -o hearthpulse-identity-staging -g hearthpulse-identity-staging "$APP_ROOT/.npm-cache"
sudo -u hearthpulse-identity-staging rsync -a "$SOURCE_ROOT/dist/" "$release_dir/dist/"
sudo -u hearthpulse-identity-staging rsync -a "$SOURCE_ROOT/build/" "$release_dir/build/"
sudo -u hearthpulse-identity-staging install -m 0640 "$SOURCE_ROOT/package.json" "$release_dir/package.json"
sudo -u hearthpulse-identity-staging install -m 0640 "$SOURCE_ROOT/package-lock.json" "$release_dir/package-lock.json"
sudo -u hearthpulse-identity-staging env npm_config_cache="$APP_ROOT/.npm-cache" npm ci --ignore-scripts --omit=dev --prefix "$release_dir"
printf '{"sha":"%s"}\n' "$expected_sha" | sudo tee "$release_dir/release.json" >/dev/null
sudo chown -R root:hearthpulse-identity-staging "$release_dir"
sudo find "$release_dir" -mindepth 1 -type d -exec chmod 0550 {} +
sudo find "$release_dir" -type f -exec chmod 0440 {} +
sudo find "$release_dir/dist" -type d -exec chmod 0555 {} +
sudo find "$release_dir/dist" -type f -exec chmod 0444 {} +
sudo chmod 0755 "$release_dir"
sudo install -m 0644 "$(dirname "${BASH_SOURCE[0]}")/$UNIT_NAME" "/etc/systemd/system/$UNIT_NAME"

if [[ -L "$CURRENT_LINK" ]]; then
  current_target="$(readlink -f -- "$CURRENT_LINK")"
  sudo ln -s "$current_target" "${PREVIOUS_LINK}.new"
  sudo mv -Tf "${PREVIOUS_LINK}.new" "$PREVIOUS_LINK"
fi
sudo ln -s "$release_dir" "${CURRENT_LINK}.new"
sudo mv -Tf "${CURRENT_LINK}.new" "$CURRENT_LINK"
sudo systemctl daemon-reload
echo 'Identity artifact and unit installed. Service was deliberately not started or restarted.'
