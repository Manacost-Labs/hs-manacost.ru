#!/usr/bin/env bash
set -euo pipefail

repo_root=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
cd "$repo_root"

if [[ ! -x vendor/bin/phpcs || ! -x vendor/bin/phpstan ]]; then
    echo "Quality tools are missing. Run: composer install --no-interaction --prefer-dist" >&2
    exit 2
fi

base_ref=${CODE_QUALITY_BASE:-}
if [[ -z "$base_ref" ]] && git rev-parse --verify origin/main >/dev/null 2>&1; then
    base_ref=$(git merge-base HEAD origin/main)
fi
if [[ -z "$base_ref" ]] && git rev-parse --verify HEAD^ >/dev/null 2>&1; then
    base_ref=HEAD^
fi
if [[ -z "$base_ref" ]]; then
    base_ref=HEAD
fi
if ! git rev-parse --verify "$base_ref^{commit}" >/dev/null 2>&1; then
    echo "Cannot resolve CODE_QUALITY_BASE=$base_ref" >&2
    exit 2
fi

mapfile -t changed_php < <(
    {
        git diff --diff-filter=ACMR --name-only "$base_ref" -- wordpress/mu-plugins
        git ls-files --others --exclude-standard -- wordpress/mu-plugins
    } | awk '/\.php$/ && !seen[$0]++'
)

if (( ${#changed_php[@]} > 0 )); then
    echo "WPCS and PHP compatibility: ${#changed_php[@]} changed first-party PHP file(s)"
    vendor/bin/phpcs --standard=phpcs.xml.dist "${changed_php[@]}"
    vendor/bin/phpcs --standard=phpcompat.xml.dist "${changed_php[@]}"
else
    echo "WPCS and PHP compatibility: no changed first-party PHP files"
fi

vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress
