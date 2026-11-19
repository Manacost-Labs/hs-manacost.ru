#!/usr/bin/env bash
set -euo pipefail
ROOT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
cd "$ROOT_DIR"
# shellcheck source=ops/integration/scope.sh
source "$ROOT_DIR/ops/integration/scope.sh"
PROJECT_NAME=$(integration_project_name "$ROOT_DIR")
docker_command=(docker)
if ! docker info >/dev/null 2>&1; then docker_command=(sudo -n docker); fi
if [[ "${1:-}" != --reuse ]]; then
    if [[ -n $("${docker_command[@]}" ps --filter "label=com.docker.compose.project=$PROJECT_NAME" --format '{{.ID}}') ]]; then
        echo 'This worktree already has an active integration stack; use --reuse only for an owned fixture.' >&2
        exit 2
    fi
    trap '"$ROOT_DIR/ops/integration/stop.sh"' EXIT
    "$ROOT_DIR/ops/integration/start.sh"
fi
site="$ROOT_DIR/.artifacts/integration/site"
mkdir -p .artifacts/card-changes/screenshots
cp wordpress/mu-plugins/hs-card-changes.php wordpress/mu-plugins/hs-manacost-lightbox.php "$site/wp-content/mu-plugins/"
cp -a wordpress/mu-plugins/hs-card-changes wordpress/mu-plugins/hs-manacost-lightbox "$site/wp-content/mu-plugins/"
# The editor searches Russian names through the same catalog the [hs_card] shortcode uses.
rm -rf "$site/wp-content/plugins/hs-tooltip"
cp -a wordpress/plugins/hs-tooltip "$site/wp-content/plugins/hs-tooltip"
cp tests/card-changes/transport.php "$site/wp-content/mu-plugins/zz-card-changes-fixture.php"
cp tests/card-changes/setup.php "$site/.integration/card-changes-setup.php"
cp tests/card-changes/integrity.php "$site/.integration/card-changes-integrity.php"
mkdir -p "$site/.integration/card-changes"
cp tests/fixtures/card-changes/*.json "$site/.integration/card-changes/"
find "$site/.integration" "$site/wp-content/plugins/hs-tooltip" "$site/wp-content/mu-plugins" -type d -exec chmod 0755 {} +
find "$site/.integration" "$site/wp-content/plugins/hs-tooltip" "$site/wp-content/mu-plugins" -type f -exec chmod 0644 {} +
compose=("${docker_command[@]}" compose --project-name "$PROJECT_NAME" --env-file .artifacts/integration/runtime.env -f ops/integration/compose.yml)
"${compose[@]}" run --rm cli plugin activate hs-tooltip --quiet
WP_CC_AUTHOR_PASSWORD=$(openssl rand -hex 24)
WP_CC_SUBSCRIBER_PASSWORD=$(openssl rand -hex 24)
export WP_CC_AUTHOR_PASSWORD WP_CC_SUBSCRIBER_PASSWORD
"${compose[@]}" run --rm --env WP_CC_AUTHOR_PASSWORD --env WP_CC_SUBSCRIBER_PASSWORD cli eval-file /var/www/html/.integration/card-changes-setup.php > .artifacts/card-changes/fixture.json
images=()
if [[ -n "${CARD_CHANGES_IMAGES:-}" ]]; then images=(-v "$CARD_CHANGES_IMAGES:/card-art:ro" --env CARD_CHANGES_IMAGES=/card-art); fi
"${docker_command[@]}" run --rm --network host --ipc=host --cpus=1 --memory=1g --pids-limit=256 \
    --env WP_CC_AUTHOR_PASSWORD --env WP_CC_SUBSCRIBER_PASSWORD "${images[@]}" \
    -v "$ROOT_DIR:/work" -w /work \
    'mcr.microsoft.com/playwright@sha256:dcc5531e97840b9b5e794f2814476b21571c5124a3fca2267d73041f56e7580e' \
    node tests/card-changes/browser.mjs
"${compose[@]}" run --rm cli eval-file /var/www/html/.integration/card-changes-integrity.php > .artifacts/card-changes/integrity.json
