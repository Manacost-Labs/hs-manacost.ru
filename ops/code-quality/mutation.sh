#!/usr/bin/env bash
set -euo pipefail
root=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
cd "$root"
if ! php -r 'exit(extension_loaded("xdebug") || extension_loaded("pcov") ? 0 : 1);'; then
    echo 'Mutation testing requires CLI-only Xdebug or PCOV; production PHP is not modified.' >&2
    exit 2
fi
export XDEBUG_MODE=coverage
coverage_dir="$root/.artifacts/quality-coverage"
vendor/bin/phpunit -c config/phpunit.xml --coverage-xml="$coverage_dir/coverage-xml" --log-junit="$coverage_dir/junit.xml"
vendor/bin/infection --configuration=config/infection-quality.json --coverage="$coverage_dir" \
    --skip-initial-tests --filter=wordpress/mu-plugins/hs-admin-meta-key-cache.php --threads=1 --no-progress
