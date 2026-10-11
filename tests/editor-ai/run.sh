#!/usr/bin/env bash
set -euo pipefail
task_root=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
task_env="$task_root/.artifacts/integration/runtime.env"
[[ -f "$task_env" ]] || { echo 'Start the disposable integration stack first' >&2; exit 2; }
docker_command=(docker)
if ! docker info >/dev/null 2>&1; then docker_command=(sudo -n docker); fi
# shellcheck source=ops/integration/scope.sh
source "$task_root/ops/integration/scope.sh"
compose=("${docker_command[@]}" compose --project-name "$(integration_project_name "$task_root")" --env-file "$task_env" -f "$task_root/ops/integration/compose.yml")
test_site="$task_root/.artifacts/integration/site"
test_fixture="$test_site/wp-content/mu-plugins/zz-editor-ai-test-fixture.php"
trap 'rm -f "$test_fixture"' EXIT
cp "$task_root/tests/editor-ai/fixture.php" "$test_fixture"
cp "$task_root/tests/editor-ai/integration.php" "$test_site/.integration/editor-ai.php"
"${compose[@]}" run --rm cli eval-file /var/www/html/.integration/editor-ai.php
post_id=$("${compose[@]}" run --rm cli option get hs_editor_ai_test_post)
"${compose[@]}" run --rm cli option update hs_editor_ai_test_delay_ms 1200 >/dev/null
"${docker_command[@]}" run --rm --network host --ipc=host --env-file "$task_env" \
    -e HS_EDITOR_AI_TEST_DISPOSABLE=1 -e "HS_EDITOR_AI_TEST_POST=$post_id" \
    -v "$task_root:/work" -w /work \
    'mcr.microsoft.com/playwright@sha256:dcc5531e97840b9b5e794f2814476b21571c5124a3fca2267d73041f56e7580e' \
    node tests/editor-ai/browser.mjs
"${compose[@]}" run --rm cli option delete hs_editor_ai_test_delay_ms >/dev/null
# Five queue samples with polling unable to run the worker: exercise real dispatch.
"${compose[@]}" run --rm cli option update hs_editor_ai_test_performance 1 >/dev/null
"${compose[@]}" run --rm cli option delete hs_editor_ai_budget_1 >/dev/null
"${docker_command[@]}" run --rm --network host --ipc=host --env-file "$task_env" \
    -e HS_EDITOR_AI_TEST_DISPOSABLE=1 -e HS_EDITOR_AI_PERF_PHASE=after -e "HS_EDITOR_AI_TEST_POST=$post_id" \
    -v "$task_root:/work" -w /work \
    'mcr.microsoft.com/playwright@sha256:dcc5531e97840b9b5e794f2814476b21571c5124a3fca2267d73041f56e7580e' \
    node tests/editor-ai/performance.mjs
"${compose[@]}" run --rm cli option delete hs_editor_ai_test_performance >/dev/null
"${compose[@]}" run --rm cli option delete hs_editor_ai_budget_1 >/dev/null
cp "$task_root/tests/editor-ai/revisions.php" "$test_site/.integration/editor-ai-revisions.php"
"${compose[@]}" run --rm cli eval-file /var/www/html/.integration/editor-ai-revisions.php
cp -a "$task_root/wordpress/plugins/hs-tooltip" "$test_site/wp-content/plugins/hs-tooltip"
cp "$task_root/tests/editor-ai/tooltip.php" "$test_site/.integration/editor-ai-tooltip.php"
"${compose[@]}" run --rm -e HS_EDITOR_AI_REAL_TOOLTIP=1 cli eval-file /var/www/html/.integration/editor-ai-tooltip.php
