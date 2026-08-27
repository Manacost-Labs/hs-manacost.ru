#!/usr/bin/env bash
set -euo pipefail

repo_root=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
manifest="$repo_root/config/wp-cli-diagnostics.json"
environment=""

usage() {
    echo "Usage: $0 --environment local|staging" >&2
}

while (( $# > 0 )); do
    case "$1" in
        --environment)
            environment=${2:-}
            shift 2
            ;;
        *)
            usage
            exit 2
            ;;
    esac
done

if [[ -z "$environment" ]]; then
    usage
    exit 2
fi

python3 - "$manifest" "$environment" <<'PY'
import json
import sys

manifest_path, environment = sys.argv[1:]
with open(manifest_path, encoding="utf-8") as manifest_file:
    manifest = json.load(manifest_file)
if environment not in manifest["allowed_environments"]:
    raise SystemExit(f"Diagnostics are not allowed in environment: {environment}")
PY

if ! command -v wp >/dev/null 2>&1; then
    echo "wp-cli is required" >&2
    exit 2
fi

wp_packages_dir=$(wp package path)
composer --working-dir="$wp_packages_dir" config github-protocols https

while IFS=$'\t' read -r package version; do
    normalized_version=${version#v}
    specification="$package:$normalized_version"
    if wp package list --format=csv --fields=name,version 2>/dev/null \
        | awk -F, -v name="$package" -v version="$normalized_version" \
            'NR > 1 { sub(/^v/, "", $2) } $1 == name && $2 == version { found = 1 } END { exit !found }'; then
        echo "$specification already installed"
        continue
    fi
    composer --working-dir="$wp_packages_dir" require "$specification" \
        --prefer-dist --no-interaction
done < <(
    python3 - "$manifest" <<'PY'
import json
import sys

with open(sys.argv[1], encoding="utf-8") as manifest_file:
    packages = json.load(manifest_file)["packages"]
for package, version in sorted(packages.items()):
    print(f"{package}\t{version}")
PY
)

wp profile --info >/dev/null
wp doctor --info >/dev/null
wp core verify-checksums --help >/dev/null
echo "WP-CLI diagnostics installed for $environment"
