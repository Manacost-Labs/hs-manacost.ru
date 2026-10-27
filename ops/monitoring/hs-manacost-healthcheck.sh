#!/usr/bin/env bash
set -u
set -o pipefail
umask 027

DOMAIN="hs-manacost.ru"
# Cloudflare geo-steers the apex: CIS clients get both RU edges, everyone
# else gets the EU edge. Proxy URL checks cover every edge.
EXPECTED_A_RU=("194.67.92.242" "186.246.28.244")
EXPECTED_A_EU=("57.129.183.118")
PROXY_IPS=("${EXPECTED_A_RU[@]}" "${EXPECTED_A_EU[@]}")
# resolver:region. Public resolvers send no ECS, so Cloudflare answers for the
# resolver's own location: 1.1.1.1 seen from this OVH host is EU, Yandex is RU.
RESOLVERS=("1.1.1.1:eu" "77.88.8.8:ru")
# label:client-subnet:region, sent as ECS straight to the authoritative NS.
ECS_PROBES=(
    "ru-moscow:95.165.0.0/24:ru"
    "ru-novosibirsk:178.49.128.0/24:ru"
    "eu-germany:85.214.0.0/24:eu"
    "us:3.3.3.0/24:eu"
)
LOG="${HEALTHCHECK_LOG:-/var/log/hs-manacost-healthcheck.log}"
NGINX_HELPER="${HEALTHCHECK_NGINX_HELPER:-/usr/local/libexec/manacost-monitoring/nginx_recent.py}"
ATTRIBUTION_LOG="${HEALTHCHECK_ATTRIBUTION_LOG:-/var/www/httpd-logs/hs-manacost.ru.5xx-attribution.log}"
PLAUSIBLE_ACCESS_LOG="${HEALTHCHECK_PLAUSIBLE_ACCESS_LOG:-/var/www/httpd-logs/hs-manacost.ru.plausible.access.log}"
FPM_HELPER="${HEALTHCHECK_FPM_HELPER:-/usr/local/libexec/manacost-monitoring/fpm_status.py}"
FCGI_CLIENT="${HEALTHCHECK_FCGI_CLIENT:-/usr/bin/cgi-fcgi}"
FPM_STATE_DIR="${HEALTHCHECK_FPM_STATE_DIR:-/run/lock/manacost-monitoring}"
PHP84_MAX_CHILDREN="${HEALTHCHECK_PHP84_MAX_CHILDREN:-32}"
PHP81_MAX_CHILDREN="${HEALTHCHECK_PHP81_MAX_CHILDREN:-48}"
TMP_DIR="$(mktemp -d /tmp/hs-manacost-healthcheck.XXXXXX)" || exit 2
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

in_list() {
    local needle="$1" item
    shift
    for item in "$@"; do
        [[ "$item" == "$needle" ]] && return 0
    done
    return 1
}

# Check one A answer against the region's edges. Public resolvers may be
# served from another anycast location, so there an answer made only of the
# other region's edges is a WARN; for an ECS query to the authoritative NS it
# is a FAIL.
check_a_answer() {
    local source="$1" region="$2" answer="$3" strict="$4"
    local -a expected other got
    local ip unexpected=0 cross=0

    if [[ "$region" == "ru" ]]; then
        expected=("${EXPECTED_A_RU[@]}"); other=("${EXPECTED_A_EU[@]}")
    else
        expected=("${EXPECTED_A_EU[@]}"); other=("${EXPECTED_A_RU[@]}")
    fi

    if [[ -z "$answer" ]]; then
        fail "dns_a $source region=$region got=empty"
        return
    fi

    IFS=',' read -r -a got <<< "$answer"
    for ip in "${got[@]}"; do
        in_list "$ip" "${expected[@]}" && continue
        if [[ "$strict" -eq 0 ]] && in_list "$ip" "${other[@]}"; then
            cross=1
            continue
        fi
        unexpected=1
        fail "dns_a $source region=$region unexpected_ip=$ip all=$answer"
    done
    [[ "$unexpected" -eq 1 ]] && return

    if [[ "$cross" -eq 1 ]]; then
        warn "dns_a $source region=$region other_region_answer all=$answer"
        return
    fi

    ok "dns_a $source region=$region all=$answer"
    for ip in "${expected[@]}"; do
        in_list "$ip" "${got[@]}" || warn "dns_a $source region=$region missing_expected=$ip all=$answer"
    done
}

dig_a() {
    dig +time=3 +tries=1 +short "$@" A "$DOMAIN" \
        | grep -E '^[0-9]+(\.[0-9]+){3}$' | sort -u | tr '\n' ',' | sed 's/,$//'
}

check_dns() {
    local entry resolver region ns label subnet
    local -a nameservers

    for entry in "${RESOLVERS[@]}"; do
        resolver="${entry%%:*}"
        region="${entry##*:}"
        check_a_answer "resolver=$resolver" "$region" "$(dig_a @"$resolver")" 0
    done

    mapfile -t nameservers < <(dig +time=3 +tries=1 +short NS "$DOMAIN" @1.1.1.1 | sed 's/\.$//' | sed '/^$/d' | sort -u)
    if [[ "${#nameservers[@]}" -eq 0 ]]; then
        fail "dns_ns got=empty"
    fi
    for ns in "${nameservers[@]}"; do
        for entry in "${ECS_PROBES[@]}"; do
            IFS=':' read -r label subnet region <<< "$entry"
            check_a_answer "auth_ns=$ns ecs=$label subnet=$subnet" "$region" \
                "$(dig_a +norec +subnet="$subnet" @"$ns")" 1
        done
    done

    local aaaa
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

check_origin_services() {
    local svc
    for svc in nginx php-fpm84 php-fpm81 mariadb redis-server hs-manacost-origin-firewall.service; do
        if systemctl is-active --quiet "$svc"; then
            ok "origin_service service=$svc state=active"
        else
            fail "origin_service service=$svc state=inactive"
        fi
    done
}

check_swap_pressure() {
    local total used pct

    read -r total used < <(awk 'NR > 1 {total += $3; used += $4} END {print total + 0, used + 0}' /proc/swaps)

    if (( total <= 0 )); then
        warn "origin_swap unavailable"
        return
    fi

    pct=$(( used * 100 / total ))
    # A full swap of cold pages is harmless (vm.swappiness=1); fail only when
    # tasks are actually stalled on memory (PSI "full" avg60, percent).
    local psi_full
    psi_full=$(awk '/^full/{split($3,a,"="); print int(a[2])}' /proc/pressure/memory 2>/dev/null)
    psi_full=${psi_full:-0}
    if (( pct >= 80 && psi_full >= 5 )); then
        fail "origin_swap used_pct=$pct used_kib=$used total_kib=$total mem_psi_full_avg60=$psi_full"
    elif (( pct >= 20 || psi_full >= 5 )); then
        warn "origin_swap used_pct=$pct used_kib=$used total_kib=$total mem_psi_full_avg60=$psi_full"
    else
        ok "origin_swap used_pct=$pct used_kib=$used total_kib=$total mem_psi_full_avg60=$psi_full"
    fi
}

check_origin_firewall() {
    if /usr/sbin/iptables -C INPUT -p tcp -m multiport --dports 80,443 -j HS_MANACOST_WEB 2>/dev/null; then
        ok "origin_firewall ipv4_web_guard=present"
    else
        fail "origin_firewall ipv4_web_guard=missing"
    fi

    if /usr/sbin/ip6tables -C INPUT -p tcp -m multiport --dports 80,443 -j HS_MANACOST_WEB6 2>/dev/null; then
        ok "origin_firewall ipv6_web_guard=present"
    else
        fail "origin_firewall ipv6_web_guard=missing"
    fi
}

check_admin_monitor_freshness() {
    local file="/var/log/hs-manacost-admin-monitor.log"
    local mtime now age

    if [[ ! -s "$file" ]]; then
        fail "admin_monitor log_missing"
        return
    fi

    mtime="$(stat -c '%Y' "$file" 2>/dev/null || echo 0)"
    now="$(date +%s)"
    age=$(( now - mtime ))
    if (( age <= 180 )); then
        ok "admin_monitor log_fresh age_sec=$age"
    else
        fail "admin_monitor log_stale age_sec=$age"
    fi
}

check_rkn_checker_freshness() {
    local file="/var/log/hs-manacost-rkn-checker/origin_latest.json"
    local mtime now age verdict

    if [[ ! -s "$file" ]]; then
        fail "rkn_checker location=origin log_missing"
        return
    fi

    mtime="$(stat -c '%Y' "$file" 2>/dev/null || echo 0)"
    now="$(date +%s)"
    age=$(( now - mtime ))
    verdict="$(python3 - "$file" <<'PY' 2>/dev/null || true
import json
import sys

with open(sys.argv[1]) as fh:
    data = json.load(fh)

items = data.get("ad_hoc") or data.get("blacklist") or data.get("whitelist") or []
print((items[0].get("verdict") if items else data.get("runner", "UNKNOWN")) or "UNKNOWN")
PY
)"

    if (( age > 1800 )); then
        fail "rkn_checker location=origin log_stale age_sec=$age verdict=${verdict:-unknown}"
    elif [[ "${verdict:-UNKNOWN}" != "OK" ]]; then
        fail "rkn_checker location=origin verdict=${verdict:-unknown} age_sec=$age"
    else
        ok "rkn_checker location=origin verdict=OK age_sec=$age"
    fi
}

check_php_sockets() {
    local socket
    for socket in /var/www/php-fpm/hs-manacost-php84.sock /var/www/php-fpm/6.sock; do
        if [[ -S "$socket" ]]; then
            ok "php_socket path=$socket present=yes"
        else
            fail "php_socket path=$socket present=no"
        fi
    done
}

check_one_php_fpm_status() {
    local label="$1"
    local socket="$2"
    local max_children="$3"
    local output rc

    output="$(
        timeout --kill-after=1s 4s env \
            SCRIPT_NAME=/fpm-status-hs-manacost \
            SCRIPT_FILENAME=/fpm-status-hs-manacost \
            REQUEST_METHOD=GET \
            QUERY_STRING=json \
            "$FCGI_CLIENT" -bind -connect "$socket" 2>/dev/null \
        | timeout --kill-after=1s 4s python3 "$FPM_HELPER" \
            --pool "$label" \
            --max-children "$max_children" \
            --state-file "$FPM_STATE_DIR/hs-${label}-fpm.state" 2>/dev/null
    )"
    rc=$?
    local expected_level=""
    case "$rc" in
        0) expected_level="OK" ;;
        1) expected_level="FAIL" ;;
        2) expected_level="UNKNOWN" ;;
    esac
    if [[ -z "$expected_level" || ! "$output" =~ ^(OK|FAIL|UNKNOWN)\ fpm_status\ pool=$label\  \
          || "$output" == *$'\n'* || "$output" != "$expected_level fpm_status "* ]]; then
        STATUS=1
        log "UNKNOWN fpm_status pool=$label helper_exit=$rc"
    else
        [[ "$rc" -ne 0 ]] && STATUS=1
        log "$output"
    fi
}

check_php_fpm_status() {
    check_one_php_fpm_status "php84" "/var/www/php-fpm/hs-manacost-php84.sock" "$PHP84_MAX_CHILDREN"
    check_one_php_fpm_status "php81" "/var/www/php-fpm/6.sock" "$PHP81_MAX_CHILDREN"
}

check_recent_nginx_incidents() {
    local output rc
    output="$(timeout --kill-after=2s 20s python3 "$NGINX_HELPER" \
        --access-log "/var/www/httpd-logs/hs-manacost.ru.access.log" \
        --error-log "/var/www/httpd-logs/hs-manacost.ru.error.log" \
        --attribution-log "$ATTRIBUTION_LOG" \
        --additional-access-log "$PLAUSIBLE_ACCESS_LOG" 2>/dev/null)"
    rc=$?
    if [[ "$rc" -gt 2 || ! "$output" =~ ^(OK|FAIL|UNKNOWN)\ nginx_recent\  || "$output" == *$'\n'* || ( "$rc" -eq 0 && "$output" != "OK nginx_recent "* ) ]]; then
        STATUS=1
        log "UNKNOWN nginx_recent helper_exit=$rc"
    else
        [[ "$rc" -ne 0 ]] && STATUS=1
        log "$output"
    fi
}

curl_headers() {
    local ip="$1"
    local url="$2"
    local headers="$3"

    curl -4 -sSL --max-redirs 3 --compressed \
        --resolve "$DOMAIN:443:$ip" \
        -D "$headers" \
        -o /dev/null \
        -w 'code=%{http_code} ttfb=%{time_starttransfer} total=%{time_total} size=%{size_download}' \
        --connect-timeout 5 \
        --max-time 10 \
        "$url" 2>/dev/null
}

check_url_via_proxy() {
    local ip="$1"
    local name="$2"
    local path="$3"
    local headers="$TMP_DIR/${ip}_${name}.headers"
    local metrics code ttfb total size proxy_cache region

    if ! metrics="$(curl_headers "$ip" "https://$DOMAIN$path" "$headers")"; then
        fail "proxy_url ip=$ip name=$name transport_error=yes"
        return
    fi
    code="$(sed -n 's/^code=\([0-9]*\).*/\1/p' <<< "$metrics")"
    ttfb="$(sed -n 's/.*ttfb=\([0-9.]*\).*/\1/p' <<< "$metrics")"
    total="$(sed -n 's/.*total=\([0-9.]*\).*/\1/p' <<< "$metrics")"
    size="$(sed -n 's/.*size=\([0-9]*\).*/\1/p' <<< "$metrics")"
    proxy_cache="$(tr -d '\r' < "$headers" | awk 'tolower($1)=="x-proxy-cache:" {print $2}' | tail -n 1)"
    region="$(tr -d '\r' < "$headers" | awk 'tolower($1)=="x-proxy-region:" {print $2}' | tail -n 1)"

    if [[ "$code" != "200" ]]; then
        fail "proxy_url ip=$ip name=$name code=${code:-empty} path=$path"
        return
    fi

    ok "proxy_url ip=$ip region=${region:-na} name=$name code=$code ttfb=${ttfb:-na} total=${total:-na} size=${size:-na} proxy_cache=${proxy_cache:-na}"

    if awk "BEGIN {exit !(${ttfb:-0} > 2.0)}"; then
        warn "proxy_url_slow_ttfb ip=$ip name=$name ttfb=$ttfb path=$path"
    fi
    if awk "BEGIN {exit !(${total:-0} > 5.0)}"; then
        fail "proxy_url_slow_total ip=$ip name=$name total=$total path=$path"
    fi
}

discover_minified_js_path() {
    local ip="$1"
    local html="$TMP_DIR/${ip}_asset_source.html"
    local path

    curl -4 -sSL --max-redirs 3 --compressed \
        --resolve "$DOMAIN:443:$ip" \
        -o "$html" \
        --connect-timeout 5 \
        --max-time 10 \
        "https://$DOMAIN/" >/dev/null 2>&1 || true

    path="$(grep -oE "https://$DOMAIN/wp-content/cache/[^\"'<> ]+\\.js(\\?[^\"'<> ]*)?" "$html" 2>/dev/null | sed "s#https://$DOMAIN##" | head -n 1)"
    if [[ -z "$path" ]]; then
        path="$(grep -oE "/wp-content/cache/[^\"'<> ]+\\.js(\\?[^\"'<> ]*)?" "$html" 2>/dev/null | head -n 1)"
    fi

    if [[ -n "$path" ]]; then
        printf '%s' "$path"
        return
    fi

    printf '%s' "/wp-content/plugins/wp-manacost-decks_old/assets/js/hs-decks-front.js"
}

check_proxy_health() {
    local ip="$1"
    local headers="$TMP_DIR/${ip}_health.headers"
    local code body region

    body="$(curl -4 -sS --resolve "$DOMAIN:443:$ip" -D "$headers" --connect-timeout 5 --max-time 8 "https://$DOMAIN/_proxy_health" 2>/dev/null || true)"
    code="$(awk 'toupper($1)=="HTTP/1.1" || toupper($1)=="HTTP/2" {print $2}' "$headers" | tail -n 1)"
    region="$(tr -d '\r' < "$headers" | awk 'tolower($1)=="x-proxy-region:" {print $2}' | tail -n 1)"

    if [[ "$code" == "200" ]]; then
        ok "proxy_health ip=$ip region=${region:-na} body=$(tr '\n' ' ' <<< "$body" | sed 's/[[:space:]]*$//')"
    else
        fail "proxy_health ip=$ip code=${code:-empty} body=$(tr '\n' ' ' <<< "$body" | sed 's/[[:space:]]*$//')"
    fi
}

check_minify_404_not_cached() {
    local ip="$1"
    local token
    token="__health_missing_$(date +%s)_$RANDOM"
    local path="/wp-content/cache/min/1/${token}.js"
    local headers="$TMP_DIR/${ip}_minify404.headers"
    local first second second_cache

    curl -4 -sS --resolve "$DOMAIN:443:$ip" -D "$headers.1" -o /dev/null --connect-timeout 5 --max-time 6 "https://$DOMAIN$path" >/dev/null 2>&1 || true
    first="$(awk '/^HTTP/{print $2}' "$headers.1" | tail -n 1)"

    curl -4 -sS --resolve "$DOMAIN:443:$ip" -D "$headers.2" -o /dev/null --connect-timeout 5 --max-time 6 "https://$DOMAIN$path" >/dev/null 2>&1 || true
    second="$(awk '/^HTTP/{print $2}' "$headers.2" | tail -n 1)"
    second_cache="$(tr -d '\r' < "$headers.2" | awk 'tolower($1)=="x-proxy-cache:" {print $2}' | tail -n 1)"

    if [[ "$first" == "404" && "$second" == "404" && "$second_cache" != "HIT" ]]; then
        ok "minify_404_cache ip=$ip second_cache=${second_cache:-na}"
    else
        fail "minify_404_cache ip=$ip first=${first:-empty} second=${second:-empty} second_cache=${second_cache:-na}"
    fi
}

check_proxy_edge() {
    local ip="$1"
    local minified_js_path

    check_proxy_health "$ip"
    check_url_via_proxy "$ip" "home" "/"
    check_url_via_proxy "$ip" "top_decks" "/top-decks/"
    check_url_via_proxy "$ip" "hearthstone_top_decks" "/hearthstone-top-decks/"
    check_url_via_proxy "$ip" "article" "/novoe-dopolnenie-hearthstone-pobeg-iz-ametistovoj-kreposti/"
    minified_js_path="$(discover_minified_js_path "$ip")"
    check_url_via_proxy "$ip" "minified_js" "$minified_js_path"
    check_minify_404_not_cached "$ip"
    return "$STATUS"
}

main() {
    if [[ "${1:-}" == "--quick" ]]; then
        log "START mode=quick"
        check_origin_services
        check_php_sockets
        check_php_fpm_status
        check_swap_pressure
        check_recent_nginx_incidents
        log "END mode=quick status=$STATUS"
        return "$STATUS"
    fi
    log "START mode=full"
    check_dns
    check_origin_services
    check_php_sockets
    check_php_fpm_status
    check_swap_pressure
    check_origin_firewall
    check_admin_monitor_freshness
    check_rkn_checker_freshness
    check_recent_nginx_incidents

    # Edges are independent (per-IP temp files, one append per log line), so
    # probe them in parallel: an outage of every edge still fits the deadline.
    local ip pid
    local -a pids=()
    for ip in "${PROXY_IPS[@]}"; do
        check_proxy_edge "$ip" &
        pids+=("$!")
    done
    for pid in "${pids[@]}"; do
        wait "$pid" || STATUS=1
    done

    if [[ "$STATUS" -eq 0 ]]; then
        log "END status=ok"
    else
        log "END status=fail"
        logger -t hs-manacost-healthcheck "failure; see $LOG"
    fi

    exit "$STATUS"
}

if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    main "$@"
fi
