#!/usr/bin/env bash
# Explicit, root-only canary operation. No WordPress/media/edge mutations.
set -Eeuo pipefail
[[ $EUID == 0 ]] || { echo 'Run the reviewed operation as root.' >&2; exit 1; }
action=${1:-}
backup=${2:-}
artifact_dir=/etc/nginx/hs-media-negotiation
http_include=/etc/nginx/conf.d/hs-media-negotiation-canary.conf
server_include=/etc/nginx/vhosts-resources/hs-manacost.ru/00-media-negotiation-canary.conf
files=(headers.conf http.conf proxy.conf server.conf install-http.inc install-server.inc)

if [[ $action == prepare ]]; then
    [[ $# == 1 && ! -e $artifact_dir && ! -L $artifact_dir ]]
    [[ ! -e $http_include && ! -L $http_include && ! -e $server_include && ! -L $server_include ]]
    source_dir=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
    nginx -t
    backup=$(mktemp -d /var/backups/hs-manacost-deploy/20260910-media-negotiation.XXXXXX)
    install -d -m 0700 "$backup/artifacts"
    for name in "${files[@]}"; do
        [[ -f $source_dir/$name && ! -L $source_dir/$name ]]
        install -m 0644 "$source_dir/$name" "$backup/artifacts/$name"
        cmp "$source_dir/$name" "$backup/artifacts/$name"
    done
    (cd "$backup/artifacts" && sha256sum "${files[@]}") > "$backup/artifacts.sha256"
    install -d -m 0755 "$artifact_dir"
    for name in headers.conf http.conf proxy.conf server.conf; do
        install -m 0644 "$backup/artifacts/$name" "$artifact_dir/$name"
    done
    printf 'PREPARED_INACTIVE %s\n' "$backup"
    exit 0
fi

[[ $# == 2 && ( $action == enable || $action == disable ) ]]
[[ $backup =~ ^/var/backups/hs-manacost-deploy/20260910-media-negotiation\.[A-Za-z0-9]{6}$ ]]
[[ -d $backup && ! -L $backup ]]
(cd "$backup/artifacts" && sha256sum --check "$backup/artifacts.sha256" >/dev/null)
for name in headers.conf http.conf proxy.conf server.conf; do
    cmp "$backup/artifacts/$name" "$artifact_dir/$name"
done
# Validate all preconditions before arming the transaction rollback.
if [[ $action == enable ]]; then
    [[ ! -e $http_include && ! -L $http_include && ! -e $server_include && ! -L $server_include ]]
else
    cmp "$backup/artifacts/install-http.inc" "$http_include"
    cmp "$backup/artifacts/install-server.inc" "$server_include"
fi
transaction=$(mktemp -d "$backup/include-transaction.XXXXXX")
restore_previous_state() {
    failure=$?
    trap - ERR
    set +e
    restored=1
    if [[ $action == enable ]]; then
        # Both targets were absent before this transaction. Retain failed files.
        if [[ -e $server_include ]]; then mv "$server_include" "$transaction/failed-server.conf" || restored=0; fi
        if [[ -e $http_include ]]; then mv "$http_include" "$transaction/failed-http.conf" || restored=0; fi
    else
        install -m 0644 "$backup/artifacts/install-http.inc" "$http_include" || restored=0
        install -m 0644 "$backup/artifacts/install-server.inc" "$server_include" || restored=0
        cmp "$backup/artifacts/install-http.inc" "$http_include" || restored=0
        cmp "$backup/artifacts/install-server.inc" "$server_include" || restored=0
    fi
    if [[ $restored == 1 ]] && nginx -t && systemctl reload nginx; then
        echo 'Operation failed; previous include state restored and reloaded.' >&2
    else
        echo "RECOVERY NEEDS ATTENTION: exact artifacts retained at $backup" >&2
    fi
    exit "$failure"
}
trap restore_previous_state ERR
if [[ $action == enable ]]; then
    install -m 0644 "$backup/artifacts/install-http.inc" "$http_include"
    install -m 0644 "$backup/artifacts/install-server.inc" "$server_include"
else
    # Remove dependents before maps, also when a later move/reload fails.
    mv "$server_include" "$transaction/server.conf"
    mv "$http_include" "$transaction/http.conf"
fi
nginx -t
systemctl reload nginx
systemctl is-active --quiet nginx
trap - ERR
printf '%s %s; verify real fixture responses next.\n' "$action" "$backup"
