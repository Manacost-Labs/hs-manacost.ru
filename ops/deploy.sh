#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'EOF'
Usage:
  ./ops/deploy.sh test [--apply]
  ./ops/deploy.sh production [--apply --confirm-production]

Without --apply, rsync runs in dry-run mode.
EOF
}

if [[ $# -lt 1 ]]; then
  usage
  exit 2
fi

environment="$1"
shift
apply=false
confirm_production=false

for argument in "$@"; do
  case "$argument" in
    --apply) apply=true ;;
    --confirm-production) confirm_production=true ;;
    *) usage; exit 2 ;;
  esac
done

case "$environment" in
  test) target_root='/var/www/koloda/data/www/test-hs-manacost-wordpress' ;;
  production) target_root='/var/www/koloda/data/www/hs-manacost.ru' ;;
  *) usage; exit 2 ;;
esac

if [[ "$environment" == production && "$apply" == true && "$confirm_production" != true ]]; then
  echo 'Production deployment requires --confirm-production.' >&2
  exit 2
fi

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
if [[ ! -f "$target_root/wp-config.php" || ! -d "$target_root/wp-content" ]]; then
  echo "Refusing to deploy: target is not a WordPress installation: $target_root" >&2
  exit 1
fi

rsync_args=(-a --itemize-changes)
if [[ "$apply" != true ]]; then
  rsync_args+=(--dry-run)
fi

if [[ "$apply" == true ]]; then
  backup_root="/var/backups/hs-manacost-deploy/${environment}/$(date -u +%Y%m%dT%H%M%SZ)"
  sudo mkdir -p "$backup_root"
  backup_paths=(wp-content/mu-plugins wp-content/themes/Newspaper_new)
  for plugin_dir in "$repo_root"/wordpress/plugins/*; do
    [[ -d "$plugin_dir" ]] || continue
    backup_paths+=("wp-content/plugins/$(basename "$plugin_dir")")
  done
  for relative_path in "${backup_paths[@]}"; do
    if [[ -e "$target_root/$relative_path" ]]; then
      sudo mkdir -p "$backup_root/$(dirname "$relative_path")"
      sudo cp -a "$target_root/$relative_path" "$backup_root/$relative_path"
    fi
  done
  echo "Backup: $backup_root"
fi

sync_tree() {
  local source="$1"
  local destination="$2"
  if [[ "$apply" == true ]]; then
    sudo mkdir -p "$destination"
  fi
  sudo rsync "${rsync_args[@]}" "$source/" "$destination/"
}

sync_tree "$repo_root/wordpress/mu-plugins" "$target_root/wp-content/mu-plugins"

for plugin_dir in "$repo_root"/wordpress/plugins/*; do
  [[ -d "$plugin_dir" ]] || continue
  sync_tree "$plugin_dir" "$target_root/wp-content/plugins/$(basename "$plugin_dir")"
done

sync_tree "$repo_root/wordpress/themes/Newspaper_new" "$target_root/wp-content/themes/Newspaper_new"
sync_tree "$repo_root/public" "$target_root"

if [[ "$apply" == true ]]; then
  sudo chown -R koloda:koloda \
    "$target_root/wp-content/mu-plugins" \
    "$target_root/wp-content/themes/Newspaper_new"
  for plugin_dir in "$repo_root"/wordpress/plugins/*; do
    [[ -d "$plugin_dir" ]] || continue
    sudo chown -R koloda:koloda "$target_root/wp-content/plugins/$(basename "$plugin_dir")"
  done
  for public_file in "$repo_root"/public/*; do
    [[ -f "$public_file" ]] || continue
    sudo chown koloda:koloda "$target_root/$(basename "$public_file")"
  done
  echo "Deployment to $environment completed. Run the smoke checks before proceeding."
else
  echo "Dry run for $environment completed. Add --apply to deploy."
fi
