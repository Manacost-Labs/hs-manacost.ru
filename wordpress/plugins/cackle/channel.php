<?php

class ChannelHandler{

    public static function init() {
        global $wpdb, $cackle_api;
        if(get_option('cackle_sync') == 1){
            if (version_compare(get_bloginfo('version'), '2.9', '>=')) {
                $chans=range(1, 100);
                $now=time()*1000;
                $i=0;
                $manual_sync_trigger=get_option('cackle_channel_modified_trigger');
                if($manual_sync_trigger!=1){
                    $modified = get_option("cackle_channel_modified_first",$now);
                    if(get_option("cackle_channel_modified")==false){
                        update_option("cackle_channel_modified",$now);
                    }
                }
                else{
                    $modified = get_option("cackle_channel_modified");
                }

                while(is_array($chans) && sizeof($chans)==100){
                    $resp = json_decode($cackle_api->get_all_channels(100,$i,$modified),true);
                    $chans = isset($resp['chans'])?$resp['chans']:'undefined';
                    if(is_array($chans) || is_object($chans)){
                        foreach ($chans as $chan) {
                            $post_id = intval($chan['channel']);
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
                            //save max modified to channel modified when proccess
                            if ($chan['modify'] > get_option("cackle_channel_modified", 0)) {
                                update_option("cackle_channel_modified", $chan['modify']);
                            }

                            //check if post exist in channel's table, if not insert with 0 time marker(to invoke all_comments mode for sync)
//                            $get_last_time = $wpdb->get_results($wpdb->prepare("
//                                SELECT *
//                                FROM {$wpdb->prefix}cackle_channel
//                                WHERE id = %d
//                                ORDER BY ID ASC
//                                LIMIT 1
//                                ", $post_id));
//                            if (count($get_last_time)==0) {
//                                $sql = "INSERT INTO {$wpdb->prefix}cackle_channel (id, time) VALUES (%s,%s) ON DUPLICATE KEY UPDATE time = %s";
//                                $sql = $wpdb->prepare($sql,$post_id,0,0);
//                                $wpdb->query($sql);
//                            }
                              $posts_update = get_option('cackle_posts_update');
                              $posts_update->$post_id = 'm';
                              update_option('cackle_posts_update', $posts_update);
                        }

                    }
                    else{

                    }
                    $i++;
                }
                update_option('cackle_channel_modified_trigger',1);



            }
        }
    }
}
?>