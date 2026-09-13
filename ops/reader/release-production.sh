#!/usr/bin/env bash
# Prepare one immutable production BFF artifact; never activate or restart it.
set -euo pipefail
sha=''
prepare=false
while [[ $# -gt 0 ]]; do
  case "$1" in
    --sha) shift; sha="${1:-}" ;;
    --prepare) prepare=true ;;
    *) echo 'Usage: bash ops/reader/release-production.sh --sha <40-char SHA> [--prepare]' >&2; exit 2 ;;
  esac
  shift
done
[[ "$sha" =~ ^[0-9a-f]{40}$ ]] || exit 2
root="$(git rev-parse --show-toplevel)"
[[ "$(git -C "$root" remote get-url origin)" == 'https://github.com/Manacost-Labs/hs-manacost.ru.git' ]] || exit 1
[[ "$(git -C "$root" rev-parse HEAD)" == "$sha" && -z "$(git -C "$root" status --porcelain)" ]] || {
  echo 'Exact clean source SHA required.' >&2; exit 1;
}
[[ "$(git -C "$root" rev-parse origin/main)" == "$sha" ]] || {
  echo 'Fetch explicitly first; exact merged origin/main SHA required.' >&2; exit 1;
}
readonly app='/srv/manacost-reader'
readonly release="$app/releases/$sha"
for path in /srv "$app" "$app/releases"; do
  [[ -d "$path" && ! -L "$path" ]] || { echo 'Unexpected production directory.' >&2; exit 1; }
  owner="$(stat -c %u "$path")"
  mode="$(stat -c %a "$path")"
  if [[ "$owner" != 0 ]] || (( (8#$mode & 022) != 0 )); then
    echo 'Release parents must be root-owned and not group/other writable.' >&2; exit 1
  fi
done
[[ ! -e "$release" && ! -L "$release" ]] || { echo 'Release already exists.' >&2; exit 1; }
for link in "$app/current" "$app/previous"; do
  if [[ -e "$link" || -L "$link" ]]; then
    [[ -L "$link" ]] || { echo 'Unexpected release pointer.' >&2; exit 1; }
    target="$(readlink -f "$link")"
    [[ "$target" =~ ^/srv/manacost-reader/releases/[0-9a-f]{40}$ && ! -L "$target" ]] || exit 1
  fi
done
for command in git npm node install sudo; do command -v "$command" >/dev/null; done
getent group manacost-reader >/dev/null
echo "Validated production BFF candidate $sha"
if [[ "$prepare" != true ]]; then echo 'Dry run: no changes.'; exit 0; fi
sudo install -d -m 0750 -o root -g manacost-reader "$release"
git -C "$root" archive "$sha:services/reader" | sudo tar -x -C "$release"
sudo npm ci --ignore-scripts --omit=dev --prefix "$release"
sudo chown -R root:manacost-reader "$release"
sudo find "$release" -type d -exec chmod 0550 {} +
sudo find "$release" -type f -exec chmod 0440 {} +
[[ -z "$(sudo find "$release" ! -user root -print -quit)" ]] || exit 1
[[ -z "$(sudo find "$release" \( -type f -o -type d \) -perm /022 -print -quit)" ]] || exit 1
sudo -u manacost-reader node --input-type=module -e 'import(process.argv[1]).then(() => process.stdout.write("Image runtime ready\n"))' "$release/avatars.js"
echo "Prepared immutable artifact $release."
echo 'No service, environment, database, current pointer, proxy or feature flag was changed.'
