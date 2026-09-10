#!/usr/bin/env bash
set -u
set -o pipefail
umask 027

DOMAIN="kolodahearthstone.com"
EXPECTED_A=("194.67.92.242" "186.246.28.244" "162.19.220.14")
SSH_KEY="/home/debian/.ssh/koloda_proxy_ed25519"
RU_PROXY="root@194.67.92.242"
LOG="${HEALTHCHECK_LOG:-/var/log/koloda-healthcheck.log}"
NGINX_HELPER="${HEALTHCHECK_NGINX_HELPER:-/usr/local/libexec/manacost-monitoring/nginx_recent.py}"
TMP_DIR="$(mktemp -d /tmp/koloda-healthcheck.XXXXXX)" || exit 2
STATUS=0

cleanup() {
  [[ -d "$TMP_DIR" ]] && rm -r -- "$TMP_DIR"
}
trap cleanup EXIT

log() {
  printf '%s %s\n' "$(date -u '+%Y-%m-%dT%H:%M:%SZ')" "$*" >> "$LOG"
}

fail() {
  STATUS=1
  log "FAIL $*"
}

warn() {
  log "WARN $*"
}

ok() {
  log "OK $*"
}

check_dns() {
  local resolver answer aaaa unexpected expected found ip
  for resolver in 1.1.1.1 77.88.8.8; do
    answer="$(dig +time=3 +tries=1 +short A "$DOMAIN" @"$resolver" | sed '/^$/d' | sort -u | tr '\n' ',' | sed 's/,$//')"
    if [[ -z "$answer" ]]; then
      fail "dns_a resolver=$resolver expected=${EXPECTED_A[*]} got=empty"
      continue
    fi

    unexpected=0
    IFS=',' read -r -a got_ips <<< "$answer"
    for ip in "${got_ips[@]}"; do
      found=0
      for expected in "${EXPECTED_A[@]}"; do
        [[ "$ip" == "$expected" ]] && found=1
      done
      if [[ "$found" -eq 0 ]]; then
        unexpected=1
        fail "dns_a resolver=$resolver unexpected_ip=$ip all=$answer"
      fi
    done

    if [[ "$unexpected" -eq 0 ]]; then
      ok "dns_a resolver=$resolver all=$answer"
    fi

    for expected in "${EXPECTED_A[@]}"; do
      found=0
      for ip in "${got_ips[@]}"; do
        [[ "$ip" == "$expected" ]] && found=1
      done
      if [[ "$found" -eq 0 ]]; then
        warn "dns_a resolver=$resolver missing_expected=$expected all=$answer"
      fi
    done
  done

  if ! aaaa="$(dig +time=3 +tries=1 +short AAAA "$DOMAIN" @1.1.1.1)"; then
    fail "dns_aaaa lookup_error"
    return
  fi
  if [[ -z "$aaaa" ]]; then
    ok "dns_aaaa empty"
  else
    fail "dns_aaaa expected_empty got=$aaaa"
  fi
}

check_url() {
  local name="$1"
  local url="$2"
  local ip="${3:-dns}" route_args=()
  local expected_code="${4:-200}"
  local headers="$TMP_DIR/${name}_${ip}.headers"
  local metrics code ttfb total proxy_cache hs_cache
  [[ "$ip" != "dns" ]] && route_args=(--resolve "$DOMAIN:443:$ip")

  if ! metrics="$(curl -4 -sSL --max-redirs 4 --compressed -D "$headers" -o /dev/null \
    -w 'code=%{http_code} ttfb=%{time_starttransfer} total=%{time_total}' \
    --connect-timeout 5 --max-time 20 "${route_args[@]}" "$url" 2>/dev/null)"; then
    fail "url name=$name ip=$ip transport_error=yes"
    return
  fi
  code="$(sed -n 's/^code=\([0-9]*\).*/\1/p' <<< "$metrics")"
  ttfb="$(sed -n 's/.*ttfb=\([0-9.]*\).*/\1/p' <<< "$metrics")"
  total="$(sed -n 's/.*total=\([0-9.]*\).*/\1/p' <<< "$metrics")"
  proxy_cache="$(tr -d '\r' < "$headers" | awk 'tolower($1)=="x-proxy-cache:" {print $2}' | tail -n 1)"
  hs_cache="$(tr -d '\r' < "$headers" | awk 'tolower($1)=="x-hs-tooltip-cache:" {print $2}' | tail -n 1)"

  if [[ "$code" != "$expected_code" ]]; then
    fail "url name=$name ip=$ip expected=$expected_code code=${code:-empty} url=$url"
    return
  fi
  ok "url name=$name ip=$ip expected=$expected_code code=$code ttfb=${ttfb:-na} total=${total:-na} proxy_cache=${proxy_cache:-na} hs_cache=${hs_cache:-na}"

  if awk "BEGIN {exit !(${ttfb:-0} > 2.0)}"; then
    warn "url_slow_ttfb name=$name ttfb=$ttfb url=$url"
  fi
}

check_services() {
  local svc
  for svc in nginx php-fpm84 mariadb redis-server koloda-ru-proxy-tunnel@18443 koloda-ru-proxy-tunnel@18444 koloda-ru-proxy-tunnel@18445; do
    if systemctl is-active --quiet "$svc"; then
      ok "service_active service=$svc"
    else
      fail "service_inactive service=$svc"
    fi
  done
}

check_redis() {
  local used max
  if ! command -v redis-cli >/dev/null 2>&1; then
    warn "redis_cli_missing"
    return
  fi
  used="$(redis-cli INFO memory 2>/dev/null | awk -F: '/^used_memory:/ {gsub(/\r/,"",$2); print $2}')"
  max="$(redis-cli CONFIG GET maxmemory 2>/dev/null | awk 'NR==2 {print $1}')"
  ok "redis_memory used_bytes=${used:-unknown} maxmemory=${max:-unknown}"
  if [[ -n "${used:-}" ]] && awk "BEGIN {exit !($used > 1073741824)}"; then
    warn "redis_memory_high used_bytes=$used"
  fi
}

check_ru_proxy() {
  local output rc
  # This single-quoted command must expand variables on the remote host.
  # shellcheck disable=SC2016
  output="$(timeout --kill-after=2s 15s ssh -i "$SSH_KEY" -o BatchMode=yes -o ConnectTimeout=8 -o StrictHostKeyChecking=yes "$RU_PROXY" '
    set -u
    nginx_state="$(systemctl is-active nginx 2>/dev/null || true)"
    disk_pct="$(df -P /var/cache/nginx/koloda 2>/dev/null | awk "NR==2 {print \$5}")"
    cache_size="$(du -sh /var/cache/nginx/koloda 2>/dev/null | awk "{print \$1}")"
    cache_files="$(find /var/cache/nginx/koloda -type f 2>/dev/null | wc -l)"
    listeners="$(ss -ltn 2>/dev/null | awk "{print \$4}" | grep -E ":(80|443|18443|18444|18445)$" | sort | tr "\n" ",")"
    printf "nginx=%s disk=%s cache_size=%s cache_files=%s listeners=%s\n" "$nginx_state" "$disk_pct" "$cache_size" "$cache_files" "$listeners"
  ' 2>&1)"
  rc=$?
  if [[ $rc -ne 0 ]]; then
    fail "ru_proxy_ssh rc=$rc output=$output"
    return
  fi
  ok "ru_proxy $output"
  if ! grep -q 'nginx=active' <<< "$output"; then
    fail "ru_proxy_nginx_not_active output=$output"
  fi
}

check_php_sockets() {
    local socket="/var/www/php-fpm/1.sock"
        if [[ -S "$socket" ]]; then
            ok "php_socket path=$socket present=yes"
        else
            fail "php_socket path=$socket present=no"
        fi
}

check_recent_nginx_incidents() {
    local output rc
    output="$(timeout --kill-after=2s 20s python3 "$NGINX_HELPER" \
        --access-log "/var/www/httpd-logs/kolodahearthstone.com.access.log" \
        --error-log "/var/www/httpd-logs/kolodahearthstone.com.error.log" 2>/dev/null)"
    rc=$?
    if [[ "$rc" -gt 2 || ! "$output" =~ ^(OK|FAIL|UNKNOWN)\ nginx_recent\  || "$output" == *$'\n'* || ( "$rc" -eq 0 && "$output" != "OK nginx_recent "* ) ]]; then
        STATUS=1
        log "UNKNOWN nginx_recent helper_exit=$rc"
    else
        [[ "$rc" -ne 0 ]] && STATUS=1
        log "$output"
    fi
}

check_legacy_redirect() {
    local headers="$TMP_DIR/legacy.headers" code location
    if ! curl -4 -sS --connect-timeout 5 --max-time 10 -D "$headers" -o /dev/null \
        "https://kolodahearthstone.ru/" 2>/dev/null; then
        fail "legacy_redirect transport_error"
        return
    fi
    code="$(awk '/^HTTP/{print $2}' "$headers" | tail -n 1)"
    location="$(tr -d '\r' < "$headers" | awk 'tolower($1)=="location:" {print $2}' | tail -n 1)"
    if [[ "$code" == "301" && "$location" == "https://kolodahearthstone.com/" ]]; then
        ok "legacy_redirect code=301 canonical_com=yes"
    else
        fail "legacy_redirect expected=301_to_canonical_com code=${code:-unknown}"
    fi
}

main() {
  if [[ "${1:-}" == "--quick" ]]; then
    log "START mode=quick"
    check_services
    check_php_sockets
    check_recent_nginx_incidents
    log "END mode=quick status=$STATUS"
    return "$STATUS"
  fi
  log "START mode=full"
  check_dns
  check_services
  check_php_sockets
  check_recent_nginx_incidents
  check_legacy_redirect
  check_redis
  check_ru_proxy
  check_url "home" "https://$DOMAIN/"
  local ip
  for ip in "${EXPECTED_A[@]}"; do
    check_url "regional_home" "https://$DOMAIN/" "$ip"
  done
  check_url "article" "https://$DOMAIN/nezhit-snova-vosstala-iz-mertvyh-meta-otchet-polej-srazhenij-6/"
  check_url "bg_proxy" "https://$DOMAIN/?hs_tooltip_img=https%3A%2F%2Fart.hearthstonejson.com%2Fv1%2Fbgs%2Flatest%2FruRU%2F256x%2FBG34_690.png"
  check_url "manacost_source" "https://hs-manacost.ru/wp-content/uploads/2026/03/bg-separator-2-optimized.png"
  # hs-manacost.ru is deliberately outside the image proxy's host allowlist.
  # Test that denial explicitly; do not weaken the plugin's fetch restrictions.
  check_url "proxy_rejects_unlisted_host" "https://$DOMAIN/?hs_tooltip_img=https%3A%2F%2Fhs-manacost.ru%2Fwp-content%2Fuploads%2F2026%2F03%2Fbg-separator-2-optimized.png" dns 403

  if [[ $STATUS -eq 0 ]]; then
    log "END status=ok"
  else
    log "END status=fail"
    logger -t koloda-healthcheck "failure; see $LOG"
  fi
  exit "$STATUS"
}

if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    main "$@"
fi
