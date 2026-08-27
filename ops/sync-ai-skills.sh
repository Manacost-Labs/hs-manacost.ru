#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
canonical="$repo_root/.agents/skills"

for target in "$repo_root/.claude/skills" "$repo_root/.codex/skills"; do
  mkdir -p "$target"
  rsync -a --delete "$canonical/" "$target/"
done

echo "AI skills synchronized from .agents/skills"
