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
compose=("${docker_command[@]}" compose --project-name hs-manacost-integration --env-file "$ENV_FILE" -f "$COMPOSE_FILE")

"${compose[@]}" run --rm -e HS_MANACOST_S3_RESTORE=1 cli eval-file /var/www/html/.integration/wordpress-tests.php
post_id=$("${compose[@]}" run --rm cli option get hs_integration_post_id)
views_json=$(curl --fail --silent \
    --data-urlencode action=td_ajax_get_views \
    --data-urlencode "post_id=$post_id" \
    "http://127.0.0.1:${WP_TEST_PORT:-8888}/wp-admin/admin-ajax.php")
# PHP, not the shell, expands its argv expressions.
# shellcheck disable=SC2016
php -r '$d=json_decode($argv[1], true); $id=(string)$argv[2]; if (!is_array($d) || (int)($d[$id] ?? -1) !== 41) { fwrite(STDERR, "views endpoint contract failed\n"); exit(1); }' "$views_json" "$post_id"
printf 'WordPress integration behaviour: OK\n'
