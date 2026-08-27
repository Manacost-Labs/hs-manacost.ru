<?php

class CackleAPI
{
    function to_i($number_to_format)
    {
        return number_format($number_to_format, 0, '', '');
    }

    function __construct()
    {
        $this->siteId = $siteId = get_option('cackle_apiId');
        $this->accountApiKey = $accountApiKey = get_option('cackle_accountApiKey');
        $this->siteApiKey = $siteApiKey = get_option('cackle_siteApiKey');
        $this->cackle_last_modified = $this->cackle_get_param('cackle_last_modified', 0);
        $this->get_url = $get_url = "http://cackle.me/api/3.0/comment/list.json?id=$siteId&accountApiKey=$accountApiKey&siteApiKey=$siteApiKey";
        $this->get_url2 = "http://cackle.me/api/2.0/site/info.json?id=$siteId&accountApiKey=$accountApiKey&siteApiKey=$siteApiKey";
        $this->update_url = $update_url = "http://cackle.me/api/wp115/setup?accountApiKey=$accountApiKey&siteApiKey=$siteApiKey";
        $this->last_error = null;
    }

    function cackle_set_param($param, $value)
    {
        $beg = "/";
        $value = $beg . $value;
        $eof = "/";
        $value .= $eof;
        return update_option($param, $value);
    }

    function cackle_get_param($param, $default)
    {
        $res = get_option($param, $default);
        $res = str_replace("/", "", $res);
        return $res;

    }

    /**
     * @param $cackle_last
     * @param $post_id
     * @param int $cackle_page
     * @return mixed
     */
    function get_comments($criteria, $cackle_last, $post_id, $cackle_page = 0){
        //$time_start = microtime(true);
        if ($criteria == 'last_comment') {
            $host = $this->get_url . "&commentId=" . $cackle_last . "&size=100&chan=" . $post_id;
        }
        if ($criteria == 'last_modified') {
            $host = $this->get_url . "&modified=" . $cackle_last . "&page=" . $cackle_page . "&size=100&chan=" . $post_id;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $host);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip, deflate");
        //curl_setopt($ch,CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-type: application/x-www-form-urlencoded; charset=utf-8',
            )
        );

        $result = curl_exec($ch);

        curl_close($ch);
        //$trace=debug_backtrace();
        //$function = $trace[0]["function"];
        //$mess='Function: ' . $function . ' execution_time' . (microtime(true) - $time_start)*1000 .PHP_EOL;
        //file_put_contents('execution_time.txt', $mess, FILE_APPEND);

        return $result;
    }

    function get_last_comment_by_channel($channel, $default)
    {
        global $wpdb;
        $result = $wpdb->get_results($wpdb->prepare("
                            SELECT last_comment
                            FROM {$wpdb->prefix}cackle_channel
                            WHERE id = %s
                            ORDER BY ID ASC
                            LIMIT 1
                            ", $channel));
        if (sizeof($result) > 0) {
            $result = $result[0]->last_comment;
            if (is_null($result)) {
                return $default;
            } else {
                return $result;
            }
        }
    }

    function set_last_comment_by_channel($channel, $last_comment)
    {
        //$time_start = microtime(true);
        global $wpdb;
        $sql = "UPDATE {$wpdb->prefix}cackle_channel SET last_comment = %s  WHERE id = %s";
        $sql = $wpdb->prepare($sql, $last_comment, $channel);
        $wpdb->query($sql);

        //Profiller
        //$trace=debug_backtrace();
        //$function = $trace[0]["function"];
        //$mess='Function: ' . $function . ' execution_time' . (microtime(true) - $time_start)*1000 .PHP_EOL;
        //file_put_contents('execution_time.txt', $mess, FILE_APPEND);

    }

    function set_monitor_status($status)
    {

    }

    function get_last_modified_by_channel($channel, $default)
    {
        //$time_start = microtime(true);
        global $wpdb;
        $result = $wpdb->get_results($wpdb->prepare("
                            SELECT modified
                            FROM {$wpdb->prefix}cackle_channel
                            WHERE id = %s
                            ORDER BY ID ASC
                            LIMIT 1
                            ", $channel));
        if (sizeof($result) > 0) {
            $result = $result[0]->modified;
            if (is_null($result)) {

                //$trace=debug_backtrace();
                //$function = $trace[0]["function"];
                //$mess='Function: ' . $function . ' execution_time' . (microtime(true) - $time_start)*1000 .PHP_EOL;
                //file_put_contents('execution_time.txt', $mess, FILE_APPEND);

                return $default;
            } else {
                //$trace=debug_backtrace();
                //$function = $trace[0]["function"];
                //$mess='Function: ' . $function . ' execution_time' . (microtime(true) - $time_start)*1000 .PHP_EOL;
                //file_put_contents('execution_time.txt', $mess, FILE_APPEND);

                return $result;
            }
        }
        $res = $result;
    }

    function set_last_modified_by_channel($channel, $last_modified)
    {
        //$time_start = microtime(true);

        global $wpdb;
        $sql = "UPDATE {$wpdb->prefix}cackle_channel SET modified = %s  WHERE id = %s";
        $sql = $wpdb->prepare($sql, $last_modified, $channel);
        $wpdb->query($sql);

        //$trace=debug_backtrace();
        //$function = $trace[0]["function"];
        //$mess='Function: ' . $function . ' execution_time' . (microtime(true) - $time_start)*1000 .PHP_EOL;
        //file_put_contents('execution_time.txt', $mess, FILE_APPEND);

    }

    function update_comments($update_request)
    {
        $http = new WP_Http();

        $blog_url = get_bloginfo('wpurl');
        $update_response = $http->request(
            $this->update_url,
            array(
                'method' => 'POST',
                'headers' => array("Content-type" => "application/x-www-form-urlencoded"),
                //'body' => "chan0=http://localhost:88/wordpress/?p=1&post0=1&count=1"
                'body' => $update_request
            )
        );

    }


    function key_validate($api, $site, $account)
    {
        $key_url = "http://cackle.me/api/2.0/site/info.json?id=$api&accountApiKey=$account&siteApiKey=$site";
        $http = new WP_Http();

        $blog_url = get_bloginfo('wpurl');
        $key_response = $http->request(
            $key_url,
            array(
                'headers' => array("referer" => $blog_url)
            )
        );
        return isset($key_response["body"]) ? $key_response["body"] : NULL;
    }

    function get_all_channels($size,$page,$modified=0){
        $siteId = $this->siteId;
        $accountApiKey = $this->accountApiKey;
        $siteApiKey = $this->siteApiKey;
        $url_base = "http://cackle.me/api/3.0/comment/chan/list.json?id=$siteId&siteApiKey=$siteApiKey&accountApiKey=$accountApiKey&size=$size&page=$page";
        if($modified){
            $url = $url_base . "&gtModify=$modified";
        }
        else{
            $url = $url_base;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip, deflate");
        //curl_setopt($ch,CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-type: application/x-www-form-urlencoded; charset=utf-8',
            )
        );
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;


    }

    function import_wordpress_comments($comments, $post_id, $eof = true){
        $data = array(
            'chan' => $post_id->ID,
            'url' => urlencode(wp_get_shortlink($post_id->ID)),
            'title' => $post_id->post_title,
            'comments' => $comments);
        $postfields = json_encode($data);
        $params = array(
            'id' => $this->siteId,
            'accountApiKey' => $this->accountApiKey,
            'siteApiKey' => $this->siteApiKey
        );
        $curl = curl_init('http://cackle.me/api/3.0/comment/post.json?'.http_build_query($params));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($postfields))
        );
        $response = curl_exec($curl);
        if(curl_errno($curl)){
            $result = '������ curl: ' . curl_error($curl);
            $response = compact('result');
            header('Content-type: text/javascript');
            $arr = array();
            $arr['responseApi']['status']= 'fail';
            $arr['responseApi']['error']='Cackle not responded';
            return json_encode($arr);
        }
        curl_close($curl);
//        $context = stream_context_create(array(
//            'http' => array(
//                // http://www.php.net/manual/en/context.http.php
//                'method' => 'POST',
//                'header' =>
//                    "Content-Type: application/json; charset=UTF-8\r\n" .
//                    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
//                    "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3\r\n" .
//                    "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:35.0) Gecko/20100101 Firefox/35.0\r\n" .
//                    "Accept-Encoding:gzip, deflate\r\n",
//                'content' => $postfields
//            )
//        ));
//        $response = file_get_contents('http://cackle.me/api/3.0/comment/post.json?'.http_build_query($params), FALSE, $context);
        return $response;
    }

    function get_last_error(){
        if (empty($this->last_error)) return;
        if (!is_string($this->last_error)) {
            return var_export($this->last_error);
        }
        return $this->last_error;
    }

}

?>