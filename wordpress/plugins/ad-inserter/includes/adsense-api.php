<?php

//ini_set ('display_errors', 1);
//error_reporting (E_ALL);

if (defined ('AI_CI_STRING') && get_option (AI_ADSENSE_OWN_IDS) === false) {
  define ('AI_ADSENSE_CLIENT_ID',     base64_decode (AI_CI_STRING));
  define ('AI_ADSENSE_CLIENT_SECRET', base64_decode (AI_CS_STRING));
}
elseif (($adsense_client_ids = get_option (AI_ADSENSE_CLIENT_IDS)) !== false) {
  define ('AI_ADSENSE_CLIENT_ID',     $adsense_client_ids ['ID']);
  define ('AI_ADSENSE_CLIENT_SECRET', $adsense_client_ids ['SECRET']);
}

if (($adsense_auth_code = get_option (AI_ADSENSE_AUTH_CODE)) !== false && get_option (AI_ADSENSE_ACCESS_TOKEN) !== false) {
  define ('AI_ADSENSE_AUTHORIZATION_CODE', $adsense_auth_code);
}

class AI_AdSense_OAuth {

  private $client_id;
  private $client_secret;
  private $redirect_uri;

  public function __construct () {
    $this->client_id     = AI_ADSENSE_CLIENT_ID;
    $this->client_secret = AI_ADSENSE_CLIENT_SECRET;
    $this->redirect_uri  = 'https://a.adinserter.pro/';
  }

  public function get_auth_url () {
      $adsense_api_state = array (
        'nonce'      => base64_encode (wp_create_nonce ("adinserter_data")),
        'return-url' => base64_encode (admin_url ('options-general.php?page=ad-inserter.php')),
        );

      $params = http_build_query ([
          'client_id'     => $this->client_id,
          'redirect_uri'  => $this->redirect_uri,
          'response_type' => 'code',
          'scope'         => 'https://www.googleapis.com/auth/adsense.readonly',
          'access_type'   => 'offline',
          'prompt'        => 'consent',
          'state'         => base64_encode (serialize ($adsense_api_state)),
      ]);
      return 'https://accounts.google.com/o/oauth2/auth?' . $params;
  }

  public function exchange_code_for_tokens ($code) {
      $response = wp_remote_post ('https://oauth2.googleapis.com/token', [
        'body' => [
            'code'          => $code,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri'  => $this->redirect_uri,
            'grant_type'    => 'authorization_code',
        ],
      ]);

      if (is_wp_error ($response)) return false;

      $tokens = json_decode (wp_remote_retrieve_body ($response), true);

      if (isset ($tokens['access_token'])) {
        update_option( AI_ADSENSE_ACCESS_TOKEN,  $tokens['access_token'] );
        update_option( AI_ADSENSE_REFRESH_TOKEN, $tokens['refresh_token'] ?? '' );
        update_option( AI_ADSENSE_TOKEN_EXPIRES,  time() + $tokens['expires_in'] );
        return true;
      }
      return false;
  }

  public function get_valid_access_token () {
      $access_token = get_option (AI_ADSENSE_ACCESS_TOKEN);
      $expires      = get_option (AI_ADSENSE_TOKEN_EXPIRES, 0);

      // Refresh if expired
      if (time () >= $expires) {
        $access_token = $this->refresh_access_token ();
      }
      return $access_token;
  }

  private function refresh_access_token () {
    $response = wp_remote_post( 'https://oauth2.googleapis.com/token', [
      'body' => [
        'client_id'     => $this->client_id,
        'client_secret' => $this->client_secret,
        'refresh_token' => get_option (AI_ADSENSE_REFRESH_TOKEN),
        'grant_type'    => 'refresh_token',
      ],
    ]);

    $tokens = json_decode (wp_remote_retrieve_body ($response), true);

    if (isset ($tokens ['access_token'])) {
        update_option (AI_ADSENSE_ACCESS_TOKEN, $tokens ['access_token'] );
        update_option (AI_ADSENSE_TOKEN_EXPIRES, time() + $tokens ['expires_in']);
        return $tokens ['access_token'];
    }
    return false;
  }
}


class AI_AdSense_API {

  private $oauth;
  private $base_url = 'https://adsense.googleapis.com/v2';

  private $error;
  private $publisher_id;
  private $client_id;

  public function __construct () {
    $this->oauth = new AI_AdSense_OAuth ();
  }

  // Get all AdSense accounts
  public function get_accounts() {
    return $this->request ('/accounts');
  }

  // Get all ad clients for an account
  public function get_ad_clients ($account_id) {
    return $this->request ("/{$account_id}/adclients");
  }

  // Get all ad units (with their ad codes)
  public function get_ad_units ($ad_client_id ) {
    return $this->request ("/{$ad_client_id}/adunits");
  }

  // Get the ad code for a specific ad unit
  public function get_ad_code($ad_unit_id ) {
    return $this->request ("/{$ad_unit_id}/adcode");
  }

  private function request( $endpoint ) {
    $token    = $this->oauth->get_valid_access_token ();
    $response = wp_remote_get ($this->base_url . $endpoint, [
      'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Accept'        => '  application/json',
      ],
    ]);

    if (is_wp_error ($response)) return null;

    return json_decode (wp_remote_retrieve_body( $response ), true);
  }

  public function get_publisher_id () {
    return $this->publisher_id;
  }

  public function get_client_id () {
    return $this->client_id;
  }

  public function get_error () {
    return $this->error;
  }

  public function ai_get_ad_units () {
    $this->error = '';

    $adsense_data = array ();

    $accounts = $this->get_accounts ();

    if ($accounts && isset ($accounts ['accounts'])) {
      foreach ( $accounts ['accounts'] as $account) {
        $account_id = $account ['name']; // e.g. "accounts/pub-XXXXXXXX"

        $account_data = explode ('/', $account_id);
        if (isset ($account_data [1])) {
          $this->publisher_id = $account_data [1];
        } else $this->publisher_id = '';

        $clients = $this->get_ad_clients ($account_id);

        if ($clients && isset ($clients ['adClients'])) {

          $ai_client = null;
          foreach ($clients ['adClients'] as $client) {
            if ($client ['productCode'] == 'AFC') {
              $ai_client = $client;
              break;
            }
          }

          $this->client_id = '';
          if ($ai_client) {
            $client_id = $ai_client ['name'];
            $this->client_id = $client_id;

            $units     = $this->get_ad_units ($client_id);

            if ($units && isset ($units ['adUnits'])) {
              $adsense_adunits = array ();
              foreach ($units ['adUnits'] as $adsense_adunit) {
                $name_elements = explode ('/', $adsense_adunit ['name']);
                $adsense_data [] = array (
                  'id'      => $adsense_adunit ['name'],
                  'name'    => $adsense_adunit ['displayName'],
                  'code'    => end ($name_elements),
                  'type'    => $adsense_adunit ['contentAdsSettings']['type'],
                  'size'    => str_replace (array ('1x3'), array (''), $adsense_adunit ['contentAdsSettings']['size']),
                  'active'  => $adsense_adunit ['state'] == 'ACTIVE',
                  );
              }
            } else $this->error = 'No valid AdSense ad units';
          } else $this->error = 'No valid AdSense ad client for AFC product';
        } else $this->error = 'No valid AdSense ad client';
      }
    } else $this->error = 'No valid AdSense account';

    if ($this->error != '') return array ();

    return $adsense_data;
  }

  public function ai_get_ad_code ($unit_id = '') {
    $this->error = '';

    $adsense_data = '';

    $ad_code = $this->get_ad_code ($unit_id);
    $adsense_data = $ad_code ['adCode'];

    if ($this->error != '') return '';

    return $adsense_data;
  }

}
