#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../.." && pwd)"
site_config="$repo_root/config/site.json"
plugins_config="$repo_root/config/wordpress-plugins.json"
skills_config="$repo_root/config/ai-skills.json"

for required in "$site_config" "$plugins_config" "$skills_config" "$repo_root/AGENTS.md"; do
  if [[ ! -f "$required" ]]; then
    echo "Missing required project file: $required" >&2
    exit 1
  fi
done

if ! command -v jq >/dev/null 2>&1; then
  echo "context-snapshot requires jq" >&2
  exit 1
fi

branch="$(git -C "$repo_root" branch --show-current)"
commit="$(git -C "$repo_root" rev-parse --short HEAD)"
dirty_count="$(git -C "$repo_root" status --short | wc -l | tr -d ' ')"

echo "HS Manacost project context"
echo "Source: $repo_root"
echo "Git: branch=${branch:-detached} commit=$commit changed_paths=$dirty_count"
echo
echo "Sites"
jq -r '.site | "- Primary: \(.primary_url)\n- Mirror: \(.mirror_url)\n- Staging: \(.staging_url)"' "$site_config"
echo
echo "Runtime"
jq -r '.runtime | "- WordPress \(.wordpress); PHP-FPM \(.php_fpm); MariaDB \(.mariadb)\n- Theme: \(.theme) \(.theme_version)"' "$site_config"
echo
echo "Source inventory"
jq -r '"- Active plugins: \(.plugins | length) (see config/wordpress-plugins.json)"' "$plugins_config"
printf '%s\n' "- MU-plugins: $(find "$repo_root/wordpress/mu-plugins" -maxdepth 1 -type f -name '*.php' | wc -l | tr -d ' ')"
echo
echo "Required project skills"
jq -r '.baseline_for_project_tasks[] | "- " + .' "$skills_config"
echo
echo "Next: read AGENTS.md, choose a task route in config/ai-skills.json, and inspect the owning source/test files."
