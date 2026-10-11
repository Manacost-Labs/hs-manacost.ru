#!/usr/bin/env bash
# Opt-in five-sample benchmark. Does not contact Anthropic or production.
set -euo pipefail
phase=${1:?Use before or after}
[[ "$phase" == before || "$phase" == after ]] || exit 2
root=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
cd "$root"
source ops/integration/scope.sh
[[ -f .artifacts/integration/runtime.env ]] || { echo 'Start the disposable integration stack first'; exit 2; }
docker_command=(docker)
if ! docker info >/dev/null 2>&1; then docker_command=(sudo -n docker); fi
compose=("${docker_command[@]}" compose --project-name "$(integration_project_name "$root")" --env-file .artifacts/integration/runtime.env -f ops/integration/compose.yml)
fixture=.artifacts/integration/site/wp-content/mu-plugins/zz-editor-ai-test-fixture.php
cp tests/editor-ai/fixture.php "$fixture"
cron_pid=''
cleanup() {
 [[ -z "$cron_pid" ]] || kill "$cron_pid" 2>/dev/null || true
 "${compose[@]}" run --rm cli option delete hs_editor_ai_test_performance >/dev/null 2>&1 || true
 rm -f "$fixture"
}
trap cleanup EXIT
# Create a marked local draft without calling new routes on the old baseline.
post_id=$("${compose[@]}" run --rm cli eval 'if ("local" !== wp_get_environment_type() || "127.0.0.1" !== wp_parse_url(home_url(),PHP_URL_HOST)) {throw new RuntimeException("Local stack required");} update_option("hs_editor_ai_test_performance",true,false); delete_option("hs_editor_ai_budget_1"); echo wp_insert_post(array("post_title"=>"Disposable AI performance", "post_status"=>"draft", "post_author"=>1));')
if [[ "$phase" == before ]]; then
 (
  for attempt in 1 2 3 4 5 6; do
   sleep 60
   "${compose[@]}" run --rm cli cron event run hs_editor_ai_process --due-now >/dev/null 2>&1
  done
 ) &
 cron_pid=$!
fi
"${docker_command[@]}" run --rm --network host --ipc=host --env-file .artifacts/integration/runtime.env \
 -e HS_EDITOR_AI_TEST_DISPOSABLE=1 -e "HS_EDITOR_AI_PERF_PHASE=$phase" -e "HS_EDITOR_AI_TEST_POST=$post_id" \
 -v "$root:/work" -w /work \
 mcr.microsoft.com/playwright@sha256:dcc5531e97840b9b5e794f2814476b21571c5124a3fca2267d73041f56e7580e \
 node tests/editor-ai/performance.mjs
"${compose[@]}" run --rm cli post delete "$post_id" --force >/dev/null
