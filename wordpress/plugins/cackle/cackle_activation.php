<?php
class CackleActivation{
    public static function check($activation_fields){
        $k_validate = new CackleApi();
        $k_req = $k_validate->key_validate(
            $activation_fields["siteId"],
            $activation_fields["siteApiKey"],
            $activation_fields["accountApiKey"]);
        if($k_req==NULL) return Array('connected' => false);
        $k_req = json_decode( $k_req, true);
        $k_req = $k_req["siteInfo"];
        if ($k_req['correctKey'] == "true") {
            update_option('cackle_apiId', $activation_fields["siteId"]);
            update_option('cackle_siteApiKey', $activation_fields["siteApiKey"]);
            update_option('cackle_accountApiKey', $activation_fields["accountApiKey"]);
            update_option("cackle_lang",$k_req["lang"]);
            update_option("cackle_whitelabel",(($k_req["whitelabel"])? 1 : 0));
            update_option("cackle_sso",(($activation_fields["sso"])? 1 : 0));
            update_option("cackle_counter",(($activation_fields["counter"])? 1 : 0));
            update_option("cackle_counter_rubrics",(($activation_fields["counter_rubrics"])? 1 : 0));
            update_option("cackle_sync",(($activation_fields["sync"])? 1 : 0));
            update_option("cackle_correctKey",1);
            $arr[]=Array(
                'whitelabel' => $k_req["whitelabel"],
                'lang' => $k_req["lang"],
                'sso' => $k_req["sso"],
                'correctKey' => $k_req['correctKey']

            );
            return $arr;
        }
        else{
            update_option("cackle_correctKey",0);
            $arr[]=Array('correctKey' => false);
            return $arr;
        }


    }
}

?>