#!/usr/bin/env bash
# Archive one deck events day with ops/deck-events/raw-archive.py against the
# integration MariaDB, let WordPress delete it, restore it, and compare rows.
# A local directory stands in for the encrypted S3 remote.
set -euo pipefail

ROOT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
ENV_FILE="$ROOT_DIR/.artifacts/integration/runtime.env"
COMPOSE_FILE="$ROOT_DIR/ops/integration/compose.yml"
WORK_DIR="$ROOT_DIR/.artifacts/integration/deck-events-archive"

docker_command=(docker)
if ! docker info >/dev/null 2>&1; then
    docker_command=(sudo -n docker)
fi
# shellcheck source=ops/integration/scope.sh
source "$ROOT_DIR/ops/integration/scope.sh"
PROJECT_NAME=$(integration_project_name "$ROOT_DIR")
compose=("${docker_command[@]}" compose --project-name "$PROJECT_NAME" --env-file "$ENV_FILE" -f "$COMPOSE_FILE")
db_password=$(sed -n 's/^WP_TEST_DB_PASSWORD=//p' "$ENV_FILE")

rm -rf "$WORK_DIR"
mkdir -p "$WORK_DIR/store"
printf '{"access_key":"integration","secret_key":"integration","endpoint":"http://127.0.0.1:9"}\n' >"$WORK_DIR/credentials.json"
printf 'DECK_ARCHIVE_CRYPT_PASSWORD=integration\nDECK_ARCHIVE_CRYPT_PASSWORD2=integration\n' >"$WORK_DIR/crypt.env"
cat >"$WORK_DIR/archive.env" <<CONFIG
DECK_ARCHIVE_DATABASE=wordpress
DECK_ARCHIVE_TABLE_PREFIX=wp_
DECK_ARCHIVE_ENVIRONMENT=integration
DECK_ARCHIVE_S3_BUCKET=hs-manacost-backups-3az
DECK_ARCHIVE_S3_PREFIX=deck-events
DECK_ARCHIVE_PROTECTED_PREFIXES=hs-data-platform
DECK_ARCHIVE_S3_CREDENTIALS_FILE=$WORK_DIR/credentials.json
DECK_ARCHIVE_CRYPT_ENV=$WORK_DIR/crypt.env
CONFIG

php_step() {
    "${compose[@]}" run --rm -e "HS_ARCHIVE_STEP=$1" cli eval-file /var/www/html/.integration/deck-events-archive-cycle.php
}
archive_tool() {
    DECK_ARCHIVE_MARIADB="${compose[*]} exec -T database mariadb -uwordpress -p$db_password" \
    DECK_ARCHIVE_RCLONE="$ROOT_DIR/ops/integration/fake-rclone.py" \
    DECK_ARCHIVE_FAKE_STORE="$WORK_DIR/store" \
        python3 -I "$ROOT_DIR/ops/deck-events/raw-archive.py" --config "$WORK_DIR/archive.env" "$@"
}

day=$(php_step prepare | tail -n 1)
[[ "$day" =~ ^[0-9]{4}-[0-9]{2}-[0-9]{2}$ ]] || { echo "unexpected prepare output: $day" >&2; exit 1; }
archive_tool archive --day "$day"
php_step delete
archive_tool restore --day "$day"
php_step verify
echo "Deck events archive cycle: OK"
