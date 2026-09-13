#!/usr/bin/env bash
set -euo pipefail

sha=''
apply=false
while [[ $# -gt 0 ]]; do
  case "$1" in
    --sha) shift; sha="${1:-}" ;;
    --apply) apply=true ;;
    *) echo 'Usage: bash ops/web-v2/release.sh --sha <40-char SHA> [--apply]' >&2; exit 2 ;;
  esac
  shift
done

[[ "$sha" =~ ^[0-9a-f]{40}$ ]] || { echo 'A full Git SHA is required.' >&2; exit 2; }
root="$(git rev-parse --show-toplevel)"
[[ "$(git -C "$root" rev-parse HEAD)" == "$sha" && -z "$(git -C "$root" status --porcelain)" ]] || {
  echo 'Exact clean source SHA required.' >&2
  exit 1
}
git -C "$root" cat-file -e "$sha:services/web-v2/package-lock.json"
git -C "$root" fetch --quiet --no-tags origin main
[[ "$(git -C "$root" rev-parse refs/remotes/origin/main)" == "$sha" ]] || {
  echo 'Only the fetched origin/main SHA can be released.' >&2
  exit 1
}

readonly app='/srv/hs-manacost-v2'
readonly release="$app/releases/$sha"
readonly build="$app/builds/$sha"

for command in git npm node install runuser systemctl curl; do command -v "$command" >/dev/null; done
[[ -d "$app" && ! -L "$app" && -d "$app/releases" && ! -L "$app/releases" && -d "$app/builds" && ! -L "$app/builds" ]] || {
  echo 'Provision root-owned /srv/hs-manacost-v2/{releases,builds} first.' >&2
  exit 1
}
[[ ! -e "$release" && ! -L "$release" && ! -e "$build" && ! -L "$build" ]] || {
  echo 'Release or build target already exists.' >&2
  exit 1
}

echo "Validated v2 candidate $sha"
if [[ "$apply" != true ]]; then echo 'Dry run: no changes.'; exit 0; fi

install -d -o hs-manacost-v2 -g hs-manacost-v2 -m 0750 "$build"
git -C "$root" archive "$sha:services/web-v2" | tar -x -C "$build"
chown -R hs-manacost-v2:hs-manacost-v2 "$build"
runuser -u hs-manacost-v2 -- npm ci --prefix "$build" --ignore-scripts
runuser -u hs-manacost-v2 -- env \
  WORDPRESS_API_URL='https://hs-manacost.ru/wp-json/wp/v2' \
  npm run build --prefix "$build"
standalone="$build/.next/standalone"
while IFS= read -r -d '' link; do
  resolved="$(readlink -f "$link")"
  [[ "$resolved" == "$standalone/"* ]] || { echo 'Standalone build contains an escaping symlink.' >&2; exit 1; }
done < <(find "$standalone" -type l -print0)

install -d -o root -g hs-manacost-v2 -m 0750 "$release"
cp -a "$standalone/." "$release/"
install -d -o root -g hs-manacost-v2 -m 0750 "$release/.next/static"
cp -a "$build/.next/static/." "$release/.next/static/"
printf '%s\n' "$sha" > "$release/REVISION"
chown -R root:hs-manacost-v2 "$release"
find "$release" -type d -exec chmod 0750 {} +
find "$release" -type f -exec chmod 0640 {} +
install -d -o hs-manacost-v2 -g hs-manacost-v2 -m 0750 "$release/.next/cache"
chown -R hs-manacost-v2:hs-manacost-v2 "$release/.next/cache"
chown -R root:hs-manacost-v2 "$build"
find "$build" -type d -exec chmod 0750 {} +
find "$build" -type f -exec chmod 0640 {} +

runuser -u hs-manacost-v2 -- test -r "$release/server.js"
runuser -u hs-manacost-v2 -- test -w "$release/.next/cache"
if runuser -u hs-manacost-v2 -- test -w "$release/server.js"; then
  echo 'Release code must remain immutable to the service user.' >&2
  exit 1
fi
if runuser -u hs-manacost-v2 -- test -w "$build"; then
  echo 'Completed build must remain immutable to the service user.' >&2
  exit 1
fi

old=''
if [[ -L "$app/current" ]]; then old="$(readlink -f "$app/current")"; fi
if [[ -n "$old" ]]; then ln -s "$old" "$app/previous.new"; mv -Tf "$app/previous.new" "$app/previous"; fi
ln -s "$release" "$app/current.new"
mv -Tf "$app/current.new" "$app/current"

if ! systemctl restart hs-manacost-v2.service; then
  [[ -n "$old" ]] && ln -sfn "$old" "$app/current" && systemctl restart hs-manacost-v2.service
  exit 1
fi

for _ in {1..20}; do
  if curl -fsS --max-time 2 http://127.0.0.1:3212/api/health >/dev/null; then
    echo "Activated $sha; rollback target: ${old:-none}"
    exit 0
  fi
  sleep 1
done

if [[ -n "$old" ]]; then
  ln -sfn "$old" "$app/current"
  systemctl restart hs-manacost-v2.service
fi
echo 'Health check failed; previous release restored when available.' >&2
exit 1
