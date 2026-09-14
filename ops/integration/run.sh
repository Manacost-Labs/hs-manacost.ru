#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
trap '"$ROOT_DIR/ops/integration/stop.sh"' EXIT

"$ROOT_DIR/ops/integration/start.sh"
"$ROOT_DIR/ops/integration/test.sh"

if [[ "${RUN_VISUAL:-0}" == "1" || "${RUN_PERFORMANCE:-0}" == "1" ]]; then
    docker_command=(docker)
    if ! docker info >/dev/null 2>&1; then
        docker_command=(sudo -n docker)
    fi
    playwright_image='mcr.microsoft.com/playwright@sha256:dcc5531e97840b9b5e794f2814476b21571c5124a3fca2267d73041f56e7580e'
    if [[ "${RUN_VISUAL:-0}" == "1" ]]; then
        # Authenticated cases share one WordPress user and its session-token store.
        visual_command=(npx playwright test tests/visual --workers=1)
        if [[ "${UPDATE_VISUAL:-0}" == "1" ]]; then
            visual_command+=(--update-snapshots)
        fi
        "${docker_command[@]}" run --rm --network host --ipc=host \
            --env CI \
            --env-file "$ROOT_DIR/.artifacts/integration/runtime.env" \
            -v "$ROOT_DIR:/work" -w /work \
            "$playwright_image" "${visual_command[@]}"
    fi

    if [[ "${RUN_PERFORMANCE:-0}" == "1" ]]; then
        raw_directory="$ROOT_DIR/.artifacts/admin-performance/raw"
        report_directory="$ROOT_DIR/.artifacts/admin-performance/reports"
        rm -rf "$raw_directory" "$report_directory"
        mkdir -p "$raw_directory" "$report_directory"
        "${docker_command[@]}" run --rm --network host --ipc=host \
            --env CI \
            --env-file "$ROOT_DIR/.artifacts/integration/runtime.env" \
            -v "$ROOT_DIR:/work" -w /work \
            "$playwright_image" node ops/performance/collect-admin-performance.mjs \
                /work/.artifacts/admin-performance/raw

        for raw_report in "$raw_directory"/*.json; do
            report_name=$(basename "$raw_report")
            report_path="$report_directory/$report_name"
            "$ROOT_DIR/ops/performance/build-admin-performance-report.py" \
                "$raw_report" "$ROOT_DIR/config/admin-performance-budgets.json" \
                >"$report_path"
            "$ROOT_DIR/.agents/skills/wordpress-admin-performance/scripts/evaluate_admin_performance.py" \
                "$report_path"
        done
    fi
fi
