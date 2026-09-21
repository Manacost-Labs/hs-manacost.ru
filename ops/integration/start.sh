#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
RUNTIME_DIR="$ROOT_DIR/.artifacts/integration"
SITE_DIR="$RUNTIME_DIR/site"
ENV_FILE="$RUNTIME_DIR/runtime.env"
COMPOSE_FILE="$ROOT_DIR/ops/integration/compose.yml"
PROJECT_NAME=hs-manacost-integration

if [[ ! "${WP_TEST_PORT:-8888}" =~ ^[0-9]+$ ]] \
    || (( ${WP_TEST_PORT:-8888} < 1024 || ${WP_TEST_PORT:-8888} > 65535 )); then
    echo "WP_TEST_PORT must be an unprivileged TCP port" >&2
    exit 2
fi

docker_command=(docker)
if ! docker info >/dev/null 2>&1; then
    docker_command=(sudo -n docker)
fi

mkdir -p "$RUNTIME_DIR"
rm -rf "$SITE_DIR"
mkdir -p "$SITE_DIR/wp-content/mu-plugins" "$SITE_DIR/wp-content/plugins" "$SITE_DIR/wp-content/themes"

wp core download --version=6.9.7 --skip-content --path="$SITE_DIR" --quiet
for plugin in classic-editor hs-manacost-inline-deck wp-kolodahearthstone-spoilers; do
    cp -a "$ROOT_DIR/wordpress/plugins/$plugin" "$SITE_DIR/wp-content/plugins/$plugin"
done
cp -a "$ROOT_DIR/wordpress/themes/Newspaper_new" "$SITE_DIR/wp-content/themes/Newspaper_new"
mkdir -p "$SITE_DIR/.integration"
cp "$ROOT_DIR/ops/integration/wordpress-tests.php" "$SITE_DIR/.integration/wordpress-tests.php"
cp "$ROOT_DIR/tests/fixtures/admin-meta-key-cache-integration.php" "$SITE_DIR/.integration/admin-meta-key-cache.php"
cp "$ROOT_DIR/tests/fixtures/article-cover-integration.php" "$SITE_DIR/.integration/article-cover.php"
cp "$ROOT_DIR/tests/fixtures/listing-thumbnails-integration.php" "$SITE_DIR/.integration/listing-thumbnails.php"
mkdir -p "$SITE_DIR/wp-content/uploads"
mkdir -p "$SITE_DIR/wp-content/cache"
find "$SITE_DIR" -type d -exec chmod 0755 {} +
find "$SITE_DIR" -type f -exec chmod 0644 {} +
chmod 0777 "$SITE_DIR/wp-content/uploads"
chmod 0777 "$SITE_DIR/wp-content/cache"

db_password=$(openssl rand -hex 24)
db_root_password=$(openssl rand -hex 24)
admin_password=$(openssl rand -base64 30 | tr -d '/+=' | head -c 30)
cat >"$ENV_FILE" <<EOF
WP_TEST_DB_PASSWORD=$db_password
WP_TEST_DB_ROOT_PASSWORD=$db_root_password
WP_TEST_ADMIN_USER=integration-admin
WP_TEST_ADMIN_PASSWORD=$admin_password
WP_TEST_PORT=${WP_TEST_PORT:-8888}
WP_TEST_SITE_PATH=$SITE_DIR
EOF
chmod 600 "$ENV_FILE"

compose=("${docker_command[@]}" compose --project-name "$PROJECT_NAME" --env-file "$ENV_FILE" -f "$COMPOSE_FILE")
"${compose[@]}" up -d database wordpress

for _ in $(seq 1 60); do
    if curl --fail --silent --output /dev/null "http://127.0.0.1:${WP_TEST_PORT:-8888}/wp-admin/install.php"; then
        break
    fi
    sleep 2
done
curl --fail --silent --output /dev/null "http://127.0.0.1:${WP_TEST_PORT:-8888}/wp-admin/install.php"

set -a
# shellcheck disable=SC1090
source "$ENV_FILE"
set +a
"${compose[@]}" run --rm cli core install \
    --url="http://127.0.0.1:${WP_TEST_PORT}" \
    --title="Manacost Integration" \
    --admin_user="$WP_TEST_ADMIN_USER" \
    --admin_password="$WP_TEST_ADMIN_PASSWORD" \
    --admin_email="integration@example.invalid" \
    --skip-email --quiet
cp -a "$ROOT_DIR/wordpress/mu-plugins/." "$SITE_DIR/wp-content/mu-plugins/"
find "$SITE_DIR/wp-content/mu-plugins" -type d -exec chmod 0755 {} +
find "$SITE_DIR/wp-content/mu-plugins" -type f -exec chmod 0644 {} +
"${compose[@]}" run --rm cli theme activate Newspaper_new --quiet
"${compose[@]}" run --rm cli plugin activate classic-editor hs-manacost-inline-deck wp-kolodahearthstone-spoilers --quiet
"${compose[@]}" run --rm cli option update permalink_structure '/%postname%/' --quiet
"${compose[@]}" run --rm cli rewrite flush --hard --quiet
# The CLI container cannot detect Apache's mod_rewrite. Generate the rules
# explicitly so browser tests exercise WordPress articles, not an Apache 404.
# PHP, not the shell, must expand $wp_rewrite.
# shellcheck disable=SC2016
"${compose[@]}" run --rm cli eval 'global $wp_rewrite; echo $wp_rewrite->mod_rewrite_rules();' >"$SITE_DIR/.htaccess"
chmod 0644 "$SITE_DIR/.htaccess"

printf 'Integration WordPress is ready at http://127.0.0.1:%s\n' "$WP_TEST_PORT"
