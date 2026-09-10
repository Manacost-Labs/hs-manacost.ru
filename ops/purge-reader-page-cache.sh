#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 1 || "$1" != 'staging' ]]; then
  echo 'Usage: ./ops/purge-reader-page-cache.sh staging' >&2
  exit 2
fi

readonly target_root='/var/www/koloda/data/www/test-hs-manacost-wordpress'
readonly host='test.hs-manacost.ru'
readonly account_url='https://test.hs-manacost.ru/account/'

[[ -f "$target_root/wp-config.php" ]] || {
  echo 'Refusing to purge: unexpected staging WordPress root.' >&2
  exit 1
}

sudo -n -u koloda env \
  REQUEST_URI='/account/' \
  HTTP_HOST="$host" \
  HTTPS='on' \
  SERVER_PORT='443' \
  /usr/local/bin/wp \
  --path="$target_root" \
  --url="https://$host" \
  eval '
    if ( ! function_exists( "rocket_clean_files" ) || ! function_exists( "rocket_clean_minify" ) ) {
      WP_CLI::error( "WP Rocket cache APIs are unavailable." );
    }
    $account_url = "https://test.hs-manacost.ru/account/";
    if ( wp_parse_url( home_url(), PHP_URL_HOST ) !== "test.hs-manacost.ru" ) {
      WP_CLI::error( "Refusing to purge: staging home URL host does not match." );
    }
    rocket_clean_minify();
    rocket_clean_files( array( $account_url ), null, false );
  '

echo "Purged WP Rocket optimized assets and page cache for $account_url"
