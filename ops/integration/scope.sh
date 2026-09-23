#!/usr/bin/env bash
# Stable per-worktree Compose ownership keeps cleanup away from other sessions.
integration_project_name() {
    local integration_root integration_hash
    integration_root=$(cd -- "${1:?project root required}" && pwd -P) || return
    integration_hash=$(printf '%s' "$integration_root" | sha256sum)
    printf 'hs-manacost-integration-%s\n' "${integration_hash:0:12}"
}
