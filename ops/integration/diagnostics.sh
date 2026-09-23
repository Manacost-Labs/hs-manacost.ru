#!/usr/bin/env bash
set -euo pipefail
root=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
# shellcheck source=ops/integration/scope.sh
source "$root/ops/integration/scope.sh"
project=$(integration_project_name "$root")
if [[ -n $(docker ps -q --filter "label=com.docker.compose.project=$project") ]]; then
    echo 'This worktree already has an integration stack. Use quality-diagnostics.py explicitly against it.' >&2
    exit 2
fi
trap '"$root/ops/integration/stop.sh"' EXIT
"$root/ops/integration/start.sh"
python3 "$root/ops/integration/quality-diagnostics.py" "$@"
