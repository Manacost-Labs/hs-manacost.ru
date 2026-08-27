<?php
function channel_timer($cron_time, $id){
    //timer for each channel
    $cackle_api = new CackleAPI();
    global $wpdb;

    $get_last_time = $wpdb->get_results($wpdb->prepare("
                            SELECT *
                            FROM {$wpdb->prefix}cackle_channel
                            WHERE id = %d
                            ORDER BY ID ASC
                            LIMIT 1
                            ", $id));
    //print_r($get_last_time);die();
    //$get_last_time = $cackle_api->cackle_get_param("last_time_" . $schedule . "_" . $_SERVER['HTTP_HOST'],0);
    $now = time();
    if (count($get_last_time)==0) {
        $sql = "INSERT INTO {$wpdb->prefix}cackle_channel (id, time) VALUES (%s,%s) ON DUPLICATE KEY UPDATE time = %s";
        $sql = $wpdb->prepare($sql,$id,$now,$now);
        $wpdb->query($sql);
        return $now;
    } else {
        $get_last_time = $get_last_time['0']->time;
        if ($get_last_time + $cron_time > $now) {
            return false;
        }
        if ($get_last_time + $cron_time < $now) {
            $sql = "INSERT INTO {$wpdb->prefix}cackle_channel (id, time) VALUES (%s,%s) ON DUPLICATE KEY UPDATE time = %s";
            $sql = $wpdb->prepare($sql,$id,$now,$now);
            $wpdb->query($sql);
            return $cron_time;
        }
    }

}
class SyncHandler{

    public static function init() {
        if(get_option('cackle_sync') == 1){
            if (version_compare(get_bloginfo('version'), '2.9', '>=')) {
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
                // INITIALIZE POSTS UPDATE OBJECT
                if(!get_option('cackle_posts_update')){
                    $posts_update = new stdClass();
                    update_option('cackle_posts_update',$posts_update);
                }

                $monitor = CackleMonitor::check_monitor();
                if(is_object($monitor)){
                    if ($monitor->post_id == NULL) return;
                    channel_timer(time(),$monitor->post_id);
                    $sync = new Sync();
                    $sync->init($monitor->post_id,$monitor->mode);
                }



            }
        }
    }
}
?>