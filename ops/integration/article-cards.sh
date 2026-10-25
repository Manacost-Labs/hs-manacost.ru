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
mkdir -p .artifacts/article-cards/screenshots
cp wordpress/mu-plugins/hs-article-cards.php "$site/wp-content/mu-plugins/"
cp -a wordpress/mu-plugins/hs-article-cards "$site/wp-content/mu-plugins/"
cp tests/article-cards/transport.php "$site/.integration/article-cards-transport.php"
cp tests/article-cards/setup.php "$site/.integration/article-cards-setup.php"
cp tests/article-cards/integrity.php "$site/.integration/article-cards-integrity.php"
cp tests/article-cards/transport.php "$site/wp-content/mu-plugins/zz-article-cards-fixture.php"
compose=("${docker_command[@]}" compose --project-name "$PROJECT_NAME" --env-file .artifacts/integration/runtime.env -f ops/integration/compose.yml)
WP_CARDS_AUTHOR_PASSWORD=$(openssl rand -hex 24)
WP_CARDS_SUBSCRIBER_PASSWORD=$(openssl rand -hex 24)
export WP_CARDS_AUTHOR_PASSWORD WP_CARDS_SUBSCRIBER_PASSWORD
"${compose[@]}" run --rm --env WP_CARDS_AUTHOR_PASSWORD --env WP_CARDS_SUBSCRIBER_PASSWORD cli eval-file /var/www/html/.integration/article-cards-setup.php > .artifacts/article-cards/fixture.json
"${docker_command[@]}" run --rm --network host --ipc=host --cpus=1 --memory=1g --pids-limit=256 \
    --env WP_CARDS_AUTHOR_PASSWORD --env WP_CARDS_SUBSCRIBER_PASSWORD \
    -v "$ROOT_DIR:/work" -w /work \
    'mcr.microsoft.com/playwright@sha256:dcc5531e97840b9b5e794f2814476b21571c5124a3fca2267d73041f56e7580e' \
    node tests/article-cards/browser.mjs
"${compose[@]}" run --rm cli eval-file /var/www/html/.integration/article-cards-integrity.php > .artifacts/article-cards/integrity.json
