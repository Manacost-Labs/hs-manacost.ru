#!/usr/bin/env bash
# Prepare a reviewed immutable BFF artifact; never change the unit/env/database.
set -euo pipefail
sha=''
apply=false
while [[ $# -gt 0 ]]; do
  case "$1" in
    --sha) shift; sha="${1:-}" ;;
    --apply) apply=true ;;
    *) echo 'Usage: bash ops/reader/release-staging.sh --sha <40-char SHA> [--apply]' >&2; exit 2 ;;
  esac
  shift
done
[[ "$sha" =~ ^[0-9a-f]{40}$ ]] || exit 2
root="$(git rev-parse --show-toplevel)"
[[ "$(git -C "$root" remote get-url origin)" == 'https://github.com/Manacost-Labs/hs-manacost.ru.git' ]] || exit 1
[[ "$(git -C "$root" rev-parse HEAD)" == "$sha" && -z "$(git -C "$root" status --porcelain)" ]] || { echo 'Exact clean source SHA required.' >&2; exit 1; }
[[ "$(git -C "$root" rev-parse origin/main)" == "$sha" ]] || { echo 'Fetch explicitly first; exact merged origin/main SHA required.' >&2; exit 1; }
readonly app='/srv/manacost-reader-staging'
readonly release="$app/releases/$sha"
for path in /srv "$app" "$app/releases"; do
  [[ -d "$path" && ! -L "$path" ]] || { echo 'Unexpected staging directory.' >&2; exit 1; }
  owner="$(stat -c %u "$path")"
  mode="$(stat -c %a "$path")"
  if [[ "$owner" != 0 ]] || (( (8#$mode & 022) != 0 )); then
    echo 'Release parents must be root-owned and not group/other writable.' >&2; exit 1
  fi
done
[[ ! -e "$release" && ! -L "$release" && ! -e "$app/current.new" && ! -L "$app/current.new" && ! -e "$app/previous.new" && ! -L "$app/previous.new" ]] || exit 1
validate_link() {
  local target
  [[ -L "$1" ]] || return 1
  target="$(readlink -f "$1")"
  [[ "$target" =~ ^/srv/manacost-reader-staging/releases/[0-9a-f]{40}$ && ! -L "$target" ]] || return 1
  [[ "$(stat -c %u "$1")" == 0 && "$(sudo stat -c %u "$target")" == 0 ]] || return 1
  [[ -z "$(sudo find "$target" ! -user root -print -quit)" ]] || return 1
  [[ -z "$(sudo find "$target" \( -type f -o -type d \) -perm /022 -print -quit)" ]] || return 1
  local entry resolved
  while IFS= read -r -d '' entry; do
    resolved="$(sudo readlink -f "$entry")"
    [[ "$resolved" == "$target/"* ]] || return 1
  done < <(sudo find "$target" -type l -print0)
  sudo test -f "$target/server.js"
}
validate_link "$app/current"
validate_link "$app/previous"
for command in git npm node install sudo systemctl; do command -v "$command" >/dev/null; done
sudo systemctl is-active --quiet manacost-reader-staging.service
echo "Validated staging-only BFF candidate $sha"
if [[ "$apply" != true ]]; then echo 'Dry run: no changes.'; exit 0; fi
sudo install -d -m 0750 -o root -g manacost-reader-staging "$release"
git -C "$root" archive "$sha:services/reader" | sudo tar -x -C "$release"
# The running service must never have write access during artifact preparation.
sudo npm ci --ignore-scripts --omit=dev --prefix "$release"
sudo chown -R root:manacost-reader-staging "$release"
sudo find "$release" -type d -exec chmod 0550 {} +
sudo find "$release" -type f -exec chmod 0440 {} +
sudo -u manacost-reader-staging node --input-type=module -e 'import(process.argv[1]).then(() => process.stdout.write("Image runtime ready\n"))' "$release/avatars.js"
old="$(readlink -f "$app/current")"
sudo ln -s "$old" "$app/previous.new"
sudo mv -Tf "$app/previous.new" "$app/previous"
sudo ln -s "$release" "$app/current.new"
sudo mv -Tf "$app/current.new" "$app/current"
echo "Prepared $release; rollback binary: $old"
echo 'Service NOT restarted. Validate backup/migration/proxy, then restart only manacost-reader-staging.service.'
