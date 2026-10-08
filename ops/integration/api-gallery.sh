#!/usr/bin/env bash
set -euo pipefail
ROOT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
cd "$ROOT_DIR"
# shellcheck source=ops/integration/scope.sh
source "$ROOT_DIR/ops/integration/scope.sh"
PROJECT_NAME=$(integration_project_name "$ROOT_DIR")
docker_command=(docker)
if ! docker info >/dev/null 2>&1; then docker_command=(sudo -n docker); fi
if [[ -n $("${docker_command[@]}" ps --filter "label=com.docker.compose.project=$PROJECT_NAME" --format '{{.ID}}') ]]; then
    echo 'This worktree already has an active disposable stack; stop it before the API gallery suite.' >&2
    exit 2
fi
trap '"$ROOT_DIR/ops/integration/stop.sh"' EXIT
"$ROOT_DIR/ops/integration/start.sh"
site="$ROOT_DIR/.artifacts/integration/site"
if [[ "${HS_GALLERY_PERF_PHASE:-after}" == before ]]; then
    baseline="${HS_GALLERY_BASELINE_COMMIT:?Specify the measured baseline commit}"
    if [[ ! "$baseline" =~ ^[0-9a-f]{40}$ ]]; then echo 'Baseline must be an exact commit SHA' >&2; exit 2; fi
    # Replace only this plugin inside the owned disposable site; source remains untouched.
    for asset in hs-api-gallery.php hs-api-gallery/class-admin.php hs-api-gallery/class-catalog.php hs-api-gallery/editor.css hs-api-gallery/editor.js; do
        git show "$baseline:wordpress/mu-plugins/$asset" > "$site/wp-content/mu-plugins/$asset"
    done
fi
mkdir -p "$ROOT_DIR/.artifacts/api-gallery"
cp tests/api-gallery/transport.php "$site/.integration/api-gallery-transport.php"
cp tests/fixtures/api-gallery-integration.php "$site/.integration/api-gallery.php"
cp tests/api-gallery/editor-integrity.php "$site/.integration/api-gallery-editor.php"
for plugin in td-composer td-standard-pack; do
    cp -a "wordpress/plugins/$plugin" "$site/wp-content/plugins/"
    chmod -R a+rX "$site/wp-content/plugins/$plugin"
done
compose=("${docker_command[@]}" compose --project-name "$PROJECT_NAME" --env-file .artifacts/integration/runtime.env -f ops/integration/compose.yml)
"${compose[@]}" run --rm cli plugin activate td-composer td-standard-pack --quiet
WP_TEST_AUTHOR_PASSWORD=$(openssl rand -hex 24)
export WP_TEST_AUTHOR_PASSWORD
printf 'WP_TEST_AUTHOR_USER=gallery-author\nWP_TEST_AUTHOR_PASSWORD=%s\n' "$WP_TEST_AUTHOR_PASSWORD" >> .artifacts/integration/runtime.env
"${compose[@]}" run --rm --env WP_TEST_AUTHOR_PASSWORD cli eval-file /var/www/html/.integration/api-gallery.php > .artifacts/api-gallery/integration-report.json
cp tests/api-gallery/bootstrap.php "$site/wp-content/mu-plugins/zz-api-gallery-fixture.php"
"${docker_command[@]}" run --rm --network host --ipc=host --cpus=1 --memory=1g --pids-limit=256 \
    --env HS_GALLERY_PERF_PHASE="${HS_GALLERY_PERF_PHASE:-after}" \
    --env HS_GALLERY_PERF_SAMPLES="${HS_GALLERY_PERF_SAMPLES:-5}" \
    -v "$ROOT_DIR:/work" -w /work \
    'mcr.microsoft.com/playwright@sha256:dcc5531e97840b9b5e794f2814476b21571c5124a3fca2267d73041f56e7580e' \
    node tests/api-gallery/browser.mjs
"${compose[@]}" run --rm cli eval-file /var/www/html/.integration/api-gallery-editor.php > .artifacts/api-gallery/editor-integrity.json
