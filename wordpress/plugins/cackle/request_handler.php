<?php
function cackle_i($text, $params = null) {
    if (!is_array($params)) {
        $params = func_get_args();
        $params = array_slice($params, 1);
    }
    return vsprintf(__($text, 'cackle'), $params);
}
function get_comment_status($status){
    if ($status == "1") {
        $status = "approved";
    } elseif ($status == "0") {
        $status = "pending";
    } elseif ($status == "spam") {
        $status = "spam";
    } elseif ($status == "trash") {
        $status = "deleted";
    }
    return $status;
}
if(!isset($_POST['data'])) return;
if(!is_string($_POST['data'])) return;

$post_req=json_decode(stripslashes($_POST['data']),true);
function render_json($array){
    ob_start();
    header('Content-type: text/javascript');
    $debug = ob_get_clean();
    echo json_encode($array);
    die();
}
switch ($post_req['cackleApi']) {

    case 'export':
        //implemented main cycle for posts
        //internal offset
        if (current_user_can('manage_options')) {
            global $wpdb, $cackle_api;
            $limit = 100;
            $offset = intval($post_req['offset']);
            $timestamp = intval($post_req['timestamp']);
            $action = $post_req['action'];
            $manual_export = get_option('cackle_manual_export','');
            if($manual_export==''){
                $manual_export = new stdClass();
                $manual_export->status='export';
            }
            $post_id = intval($post_req['post_id']);

            switch ($action) {
                case 'export_start':
                    if($manual_export->status == 'stop'){
                        $result = 'fail';
                        ob_start();
                        $response = compact('result', 'timestamp', 'status', 'post_id', 'msg', 'eof', 'response', 'debug');
                        header('Content-type: text/javascript');
                        echo json_encode($response);
                        $manual_export->status = 'export'; //revert trigger for initial state
                        update_option('cackle_manual_export',$manual_export);
                        die();

                    }
                    break;
            }
            $post = $wpdb->get_results($wpdb->prepare("
                            SELECT *
                            FROM $wpdb->posts
                            WHERE post_type != 'revision'
                            AND post_status = 'publish'
                            AND comment_count > 0
                            AND ID > %d
                            ORDER BY ID ASC
                            LIMIT 1
                            ", $post_id));
            $post = $post[0];
            $post_id = $post->ID;
            $max_post_id = $wpdb->get_var($wpdb->prepare("
                            SELECT MAX(ID)
                            FROM $wpdb->posts
                            WHERE post_type != 'revision'
                            AND post_status = 'publish'
                            AND comment_count > 0
                            ", $post_id));
            $eof = (int)($post_id == $max_post_id);
            if ($eof) {
                $status = 'complete';
                $msg = 'Your comments have been sent to Cackle and queued for import!<br/>';
            } else {
                $status = 'partial';
                $manual_export->finish=false;
                update_option('cackle_manual_export',$manual_export);
            }
            $result = 'fail';
            ob_start();
            $response = null;
            if ($post) {
                $comms = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d
                AND comment_agent NOT LIKE 'Cackle:%%' order by comment_date asc limit %d offset %d", array($post->ID,100, $offset)));

                if(sizeof($comms)==0){
                    $response = "success";
                    $comments_pack_status = 'complete';
                    $comments_prepared = null;
                }
                else{
                    $comments=array();
                    foreach ($comms as $comment) {
                        $created=new DateTime($comment->comment_date);
                        $comments[]=Array(
                            'id' => $comment->comment_ID,
                            'ip' => $comment->comment_author_IP,
                            'status' => get_comment_status($comment->comment_approved),
                            'msg'=> $comment->comment_content,
                            'created' => $created->getTimestamp()*1000,
                            'user' => ($comment->user_id > 0) ? array(
                                'id' => $comment->user_id,
                                'name' => $comment->comment_author,
                                'email' => $comment->comment_author_email
                            ) : null,
                            'parent' => $comment->comment_parent,
                            'name' => ($comment->user_id == 0) ? $comment->comment_author : null,
                            'email' => ($comment->user_id == 0) ? $comment->comment_author_email : null
                        );

                    }
                    $response = $cackle_api->import_wordpress_comments($comments,$post,$eof);
                    $response = json_decode($response,true);
                    $fail_response = $response;
                    $response = (isset($response['responseApi']['status']) && $response['responseApi']['status'] == "ok" ) ? "success" : "fail";
                    $comments_pack_status = 'partial';
                    $comments_prepared = sizeof($comments);
                }

                if (!($response == "success")) {
                    $result = 'fail';
                } else {
                    if ($eof) {
                        $manual_export->finish=true;
                        update_option('cackle_manual_export',$manual_export);
                    }
                    $result = 'success';
                }
            }
            //AJAX response
            $debug = ob_get_clean();
            $export = 'export';
            $response = compact('result', 'timestamp', 'status', 'post_id', 'eof', 'response', 'debug','export','fail_response','comments_pack_status','comments_prepared');
            header('Content-type: text/javascript');
            echo json_encode($response);
            //Update last post id exported only if it was exported
            if($result=='success'){
                $manual_export->last_post_id=$post_id;
                if($comments_pack_status == 'complete'){
                    $manual_export->last_offset=0;
                }
                else{
                    $manual_export->last_offset=$offset;
                }

                update_option('cackle_manual_export',$manual_export);
            }
            die();
        }
        break;
    case 'import_prepare':
        //Import prepare action is for clearing previous comments sync information and create
        if (current_user_can('manage_options')) {
            global $wpdb, $cackle_api;
            //$timestamp = intval($post_req['timestamp']);
            $page = $post_req['offset']/100;

            $manual_sync = get_option('cackle_manual_sync', '');
            if ($manual_sync == '') {
                $manual_sync = new stdClass();
                $manual_sync->status = 'sync';
            }
            $post_id = intval($post_req['post_id']);
            if ($post_req['offset'] == 0) {
                $wpdb->query("DELETE FROM `" . $wpdb->prefix . "commentmeta` WHERE meta_key IN ('cackle_post_id', 'cackle_parent_post_id')");
                $wpdb->query("DELETE FROM `" . $wpdb->prefix . "comments` WHERE comment_agent LIKE 'Cackle:%%'");
                $wpdb->query("DELETE FROM `" . $wpdb->prefix . "cackle_channel`");
                delete_option("cackle_monitor");
                delete_option("cackle_monitor_short");
                delete_option("cackle_modified_trigger");
                //delete for first channel requests
                delete_option("cackle_posts_update");
                delete_option('cackle_channel_modified_trigger');

            }
            //initialize monitor object if not exist
            if( !get_option('cackle_monitor') ) {
                $object = new stdClass();
                $object->post_id = 0;
                $object->time = 0;
                $object->mode = "by_channel";
                $object->status = "finish";
                $object->counter = 0;
                update_option('cackle_monitor',$object);
            }

            if( !get_option('cackle_monitor_short') ) {
                $object = new stdClass();
                $object->post_id = 0;
                $object->time = 0;
                $object->mode = "by_channel";
                $object->status = "finish";
                update_option('cackle_monitor_short',$object);
            }

            //initialize modified triger object if not exist
            if(!get_option('cackle_modified_trigger')){
                $modified_triger = new stdClass();
                update_option('cackle_modified_trigger',$modified_triger);
            }

            if(!get_option('cackle_posts_update')){
                $posts_update = new stdClass();
                update_option('cackle_posts_update',$posts_update);
            }

            //Get all chans for site, check if posts with these chans exist in WP and then add to channels table
            $resp = json_decode($cackle_api->get_all_channels(100,$page),true);
            $chans = isset($resp['chans'])?$resp['chans']:'undefined';

            $object = get_option('cackle_monitor');
            $object->post_id = 0;
            $object->status = 'inprocess';
            $object->mode = 'all_comments';
            $object->time = time();
            update_option('cackle_monitor', $object);

            if(isset($chans[0])){
                foreach ($chans as $chan) {
                    $post_id = intval($chan);
                    if($post_id==0) continue;
                    $post = $wpdb->get_results($wpdb->prepare("
                            SELECT *
                            FROM $wpdb->posts
                            WHERE post_type != 'revision'
                            AND post_status = 'publish'
                            AND ID = %d
                            ORDER BY ID ASC
                            LIMIT 1
                            ", $chan['channel']));
                    if(!isset($post[0])) continue;
                    $post_id = $post[0]->ID;

                    //check if post exist in channel's table, if not insert with 0 time marker(to invoke all_comments mode for sync)
                    $get_last_time = $wpdb->get_results($wpdb->prepare("
                            SELECT *
                            FROM {$wpdb->prefix}cackle_channel
                            WHERE id = %d
                            ORDER BY ID ASC
                            LIMIT 1
                            ", $post_id));
                    if (count($get_last_time)==0) {
                        $sql = "INSERT INTO {$wpdb->prefix}cackle_channel (id, time) VALUES (%s,%s) ON DUPLICATE KEY UPDATE time = %s";
                        $sql = $wpdb->prepare($sql,$post_id,0,0);
                        $wpdb->query($sql);
                    }
                }
                $arr['status'] = 'partial';
                $arr['channels_prepared'] = sizeof($chans);
            }
            else{
                $arr['status'] = 'complete';
            }
            render_json($arr);
            //TODO: create angular controller
        }
        break;
    case 'import':
        if (current_user_can('manage_options')) {
            global $wpdb, $cackle_api;
            $post_id = $post_req['post_id'];
            $action = $post_req['action'];

            $manual_sync = get_option('cackle_manual_sync','');
            if($manual_sync==''){
                $manual_sync = new stdClass();
                $manual_sync->status='sync';
            }
            if($post_id==0){
                update_option("cackle_channel_modified_first",time()*1000);
            }
            //starting sync for post > last_synced post_id
            $post = $wpdb->get_results($wpdb->prepare("
                            SELECT *
                            FROM {$wpdb->prefix}cackle_channel
                            WHERE  id > %d
                            ORDER BY ID ASC
                            LIMIT 1
                            ", $post_id));
            $post = $post[0];
            $post_id = $post->id;

            $max_post_id = $wpdb->get_var($wpdb->prepare("
                            SELECT MAX(id)
                            FROM {$wpdb->prefix}cackle_channel
                            ", $post_id));
            $eof = (int)($post_id == $max_post_id);
            if ($eof) {
                $status = 'complete';
                $msg = 'Your comments have been resynchronized!<br/>';
            } else {
                $status = 'partial';
                //require_once(dirname(__FILE__) . '/manage.php');
                $msg = cackle_i('Processed comments on post #%s&hellip;', $post_id);
                $manual_sync->finish=false;
                update_option('cackle_manual_sync',$manual_sync);
            }
            $result = 'fail';
            ob_start();
            $response = null;
            if ($post) {
                $sync = new Sync();
                $response = $sync->init($post_id,'all_comments');
                $fail_response = $response;
                if (!($response == "success")) {
                    $result = 'fail';
                    $msg = '<p class="status cackle-export-fail">' . cackle_i('Sorry, something  happened with the export. Please <a href="#" id="cackle_export_retry">try again</a></p><p>If your API key has changed, you may need to reinstall Cackle (deactivate the plugin and then reactivate it). If you are still having issues, refer to the <a href="%s" onclick="window.open(this.href); return false">WordPress help page</a>.', 'http://cackle.me/help/') . '</p>';
                    $response = $cackle_api->get_last_error();
                } else {
                    if ($eof) {
                        //we need to switch monitor to by_channel
                        $object = new stdClass();
                        $object->mode = "by_channel";
                        $object->post_id = 0;
                        $object->status = 'finish';
                        $object->time = time();
                        update_option('cackle_monitor',$object);
                        $manual_sync->finish=true;
                        update_option('cackle_manual_sync',$manual_sync);

                        $msg = cackle_i('Your comments have been synchronized with Cackle!');
                    }
                    $result = 'success';
                }
            }
            //AJAX response
            $debug = ob_get_clean();
            $import='import';
            $response = compact('result', 'timestamp', 'status', 'post_id', 'msg', 'eof', 'response', 'debug','import','fail_response');
            header('Content-type: text/javascript');
            echo json_encode($response);
            if($result=='success') {
                $manual_sync->last_post_id = $post_id;
                update_option('cackle_manual_sync', $manual_sync);
            }

            die();
        }
        break;

}
switch ($post_req['cackleApi']) {
    case 'checkKeys':
        if (current_user_can('manage_options')) {
            require_once(dirname(__FILE__) . '/cackle_activation.php');
            //$activation_fields = stripslashes();
            //$activation_fields = json_decode($activation_fields);
            $resp = CackleActivation::check($post_req['value']);
            echo json_encode($resp);
            die();
        }

}

?>