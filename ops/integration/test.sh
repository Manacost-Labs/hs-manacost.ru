#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
ENV_FILE="$ROOT_DIR/.artifacts/integration/runtime.env"
COMPOSE_FILE="$ROOT_DIR/ops/integration/compose.yml"

[[ -f "$ENV_FILE" ]] || { echo "Run ops/integration/start.sh first" >&2; exit 1; }

docker_command=(docker)
if ! docker info >/dev/null 2>&1; then
    docker_command=(sudo -n docker)
fi
# shellcheck source=ops/integration/scope.sh
source "$ROOT_DIR/ops/integration/scope.sh"
PROJECT_NAME=$(integration_project_name "$ROOT_DIR")
compose=("${docker_command[@]}" compose --project-name "$PROJECT_NAME" --env-file "$ENV_FILE" -f "$COMPOSE_FILE")

"${compose[@]}" run --rm -e HS_MANACOST_S3_RESTORE=1 cli eval-file /var/www/html/.integration/wordpress-tests.php
# Match Apache's local-only URL constants before the domain bootstrap MU loads.
"${compose[@]}" run --rm -e WP_ENVIRONMENT_TYPE=local \
    -e "WORDPRESS_CONFIG_EXTRA=define('WP_HOME', 'http://127.0.0.1:${WP_TEST_PORT:-8888}'); define('WP_SITEURL', WP_HOME); define('DISABLE_WP_CRON', true);" \
    cli eval-file /var/www/html/.integration/admin-meta-key-cache.php
post_id=$("${compose[@]}" run --rm cli option get hs_integration_post_id)
printf 'WP_TEST_POST_ID=%s\nWP_TEST_DATASET_SIZE=1\n' "$post_id" >>"$ENV_FILE"
views_json=$(curl --fail --silent \
    --data-urlencode action=td_ajax_get_views \
    --data-urlencode "post_id=$post_id" \
    "http://127.0.0.1:${WP_TEST_PORT:-8888}/wp-admin/admin-ajax.php")
# PHP, not the shell, expands its argv expressions.
# shellcheck disable=SC2016
php -r '$d=json_decode($argv[1], true); $id=(string)$argv[2]; if (!is_array($d) || (int)($d[$id] ?? -1) !== 41) { fwrite(STDERR, "views endpoint contract failed\n"); exit(1); }' "$views_json" "$post_id"
printf 'WordPress integration behaviour: OK\n'

# Run the real native uploader, not media_handle_sideload or a mocked API.
# shellcheck disable=SC2016
"${compose[@]}" run --rm cli eval 'if ("local" !== wp_get_environment_type() || "127.0.0.1" !== wp_parse_url(home_url(), PHP_URL_HOST)) { throw new RuntimeException("Disposable loopback WordPress is required."); }'
"${docker_command[@]}" run --rm --network host --ipc=host \
    --env-file "$ENV_FILE" -e HS_MEDIA_TEST_DISPOSABLE=1 -v "$ROOT_DIR:/work" -w /work \
    'mcr.microsoft.com/playwright@sha256:dcc5531e97840b9b5e794f2814476b21571c5124a3fca2267d73041f56e7580e' \
    node ops/integration/media-upload.mjs
cp "$ROOT_DIR/.artifacts/integration/media-upload-report.json" "$ROOT_DIR/.artifacts/integration/site/.integration/"
cp "$ROOT_DIR/ops/integration/media-upload-check.php" "$ROOT_DIR/.artifacts/integration/site/.integration/"
"${compose[@]}" run --rm cli eval-file /var/www/html/.integration/media-upload-check.php
