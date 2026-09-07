#!/usr/bin/env bash
set -euo pipefail

mode="${1:-all}"
case "$mode" in
  staging|production|all) ;;
  *) echo 'Usage: ./ops/smoke-check.sh [staging|production|all]' >&2; exit 2 ;;
esac

origin_ip='151.80.21.140'
edge_ips=('194.67.92.242' '186.246.28.244')
edge_regions=('ru-moscow' 'ru-novosibirsk')
image_path='/wp-content/uploads/2026/07/728x90.jpg'
temporary_directory="$(mktemp -d)"
trap 'rm -rf "$temporary_directory"' EXIT

request() {
  local domain="$1"
  local ip="$2"
  local path="$3"
  local expected_status="$4"
  local insecure="${5:-false}"
  local headers_file="$temporary_directory/headers"
  local body_file="$temporary_directory/body"
  local curl_args=(
    --noproxy '*'
    --silent
    --show-error
    --connect-timeout 7
    --max-time 25
    --resolve "$domain:443:$ip"
    --dump-header "$headers_file"
    --output "$body_file"
    --write-out '%{http_code}'
  )

  if [[ "$insecure" == true ]]; then
    curl_args+=(--insecure)
  fi

  local status
  status="$(curl "${curl_args[@]}" "https://$domain$path")"
  if [[ "$status" != "$expected_status" ]]; then
    echo "FAIL: $domain via $ip$path returned $status, expected $expected_status" >&2
    return 1
  fi

  echo "OK: $domain via $ip$path -> $status"
  if [[ "$path" != '/_proxy_health' ]]; then
    assert_host_policy "$domain" "$path"
  fi
}

header_value() {
  local name="$1"
  awk -F': *' -v wanted="$name" '
    tolower($1) == tolower(wanted) {
      gsub("\r", "", $2)
      value = $2
    }
    END { print value }
  ' "$temporary_directory/headers"
}

assert_host_policy() {
  local domain="$1" path="$2" robots marker
  robots="$(header_value 'X-Robots-Tag')"
  marker="$(header_value 'X-Manacost-Mirror')"
  if [[ "$domain" == 'hs-manacost.com' ]]; then
    if [[ "$marker" != 'active' || ! ${robots,,} =~ (^|[[:space:],])noindex($|[[:space:],]) ]]; then
      echo "FAIL: $domain$path is missing the mirror marker or noindex header" >&2
      return 1
    fi
  elif [[ "$domain" == 'hs-manacost.ru' ]]; then
    if [[ -n "$marker" ]]; then
      echo "FAIL: primary $domain$path received the mirror marker" >&2
      return 1
    fi
    # A page-level 404 noindex is valid; public HTML/assets/redirects must not
    # inherit a site-wide mirror robots policy.
    if [[ "$path" != '/__manacost_header_check_missing__/' && ${robots,,} =~ (^|[[:space:],])noindex($|[[:space:],]) ]]; then
      echo "FAIL: primary $domain$path received noindex" >&2
      return 1
    fi
  fi
}

check_edge() {
  local domain="$1"
  local ip="$2"
  local region="$3"
  local root_status="$4"

  request "$domain" "$ip" '/_proxy_health' '200'
  local actual_region
  actual_region="$(header_value 'X-Proxy-Region')"
  if [[ "$actual_region" != "$region" ]]; then
    echo "FAIL: $domain via $ip reports region '$actual_region', expected '$region'" >&2
    return 1
  fi

  request "$domain" "$ip" '/' "$root_status"
}

check_staging() {
  local domain='test.hs-manacost.ru'
  request "$domain" "$origin_ip" '/_proxy_health' '200' true
  request "$domain" "$origin_ip" '/' '401' true

  for index in "${!edge_ips[@]}"; do
    check_edge "$domain" "${edge_ips[$index]}" "${edge_regions[$index]}" '401'
  done
}

check_production_domain() {
  local domain="$1"
  request "$domain" "$origin_ip" '/' '200' true
  request "$domain" "$origin_ip" "$image_path" '200' true

  for index in "${!edge_ips[@]}"; do
    check_edge "$domain" "${edge_ips[$index]}" "${edge_regions[$index]}" '200'
    request "$domain" "${edge_ips[$index]}" "$image_path" '200'
    if [[ "$(header_value 'Content-Type')" != image/* ]]; then
      echo "FAIL: $domain via ${edge_ips[$index]} did not return an image content type" >&2
      return 1
    fi
  done

  local ip insecure _attempt
  for ip in "$origin_ip" "${edge_ips[@]}"; do
    insecure=false
    [[ "$ip" == "$origin_ip" ]] && insecure=true
    for _attempt in 1 2; do
      request "$domain" "$ip" '/' '200' "$insecure"
      request "$domain" "$ip" '/__manacost_header_check_missing__/' '404' "$insecure"
      request "$domain" "$ip" '/wp-content' '301' "$insecure"
    done
  done
}

if [[ "$mode" == staging || "$mode" == all ]]; then
  check_staging
fi

if [[ "$mode" == production || "$mode" == all ]]; then
  check_production_domain 'hs-manacost.ru'
  check_production_domain 'hs-manacost.com'
fi

echo "Smoke checks passed: $mode"
