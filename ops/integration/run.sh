#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
trap '"$ROOT_DIR/ops/integration/stop.sh"' EXIT

"$ROOT_DIR/ops/integration/start.sh"
"$ROOT_DIR/ops/integration/test.sh"

if [[ "${RUN_VISUAL:-0}" == "1" ]]; then
    docker_command=(docker)
    if ! docker info >/dev/null 2>&1; then
        docker_command=(sudo -n docker)
    fi
    playwright_image='mcr.microsoft.com/playwright@sha256:dcc5531e97840b9b5e794f2814476b21571c5124a3fca2267d73041f56e7580e'
    "${docker_command[@]}" run --rm --network host --ipc=host \
        --env-file "$ROOT_DIR/.artifacts/integration/runtime.env" \
        -v "$ROOT_DIR:/work" -w /work \
        "$playwright_image" npx playwright test tests/visual
fi
