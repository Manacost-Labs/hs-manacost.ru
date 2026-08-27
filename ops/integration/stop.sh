#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
ENV_FILE="$ROOT_DIR/.artifacts/integration/runtime.env"
COMPOSE_FILE="$ROOT_DIR/ops/integration/compose.yml"

if [[ ! -f "$ENV_FILE" ]]; then
    exit 0
fi

docker_command=(docker)
if ! docker info >/dev/null 2>&1; then
    docker_command=(sudo -n docker)
fi

"${docker_command[@]}" compose \
    --project-name hs-manacost-integration \
    --env-file "$ENV_FILE" \
    -f "$COMPOSE_FILE" down --volumes --remove-orphans
