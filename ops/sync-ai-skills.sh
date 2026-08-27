#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
canonical="$repo_root/.agents/skills"

for target in "$repo_root/.claude/skills" "$repo_root/.codex/skills"; do
  mkdir -p "$target"
  rsync -a --delete --delete-excluded \
    --exclude='__pycache__/' \
    --exclude='*.pyc' \
    "$canonical/" "$target/"

  # Imported skills can contain CRLF files. The repository enforces LF, so
  # normalize known text resources after copying to keep `git status` clean.
  find "$target" -type f \
    \( -name '*.css' -o -name '*.html' -o -name '*.js' -o -name '*.json' \
    -o -name '*.md' -o -name '*.mjs' -o -name '*.php' -o -name '*.scss' \
    -o -name '*.sh' -o -name '*.stub' -o -name '*.txt' -o -name '*.xml' \
    -o -name '*.yaml' -o -name '*.yml' -o -name 'LICENSE' \) \
    -exec sed -i 's/\r$//' {} +
done

echo "AI skills synchronized from .agents/skills"
