<?php

class CackleMonitor
{
    public static function check_monitor()
    {
        /* Check cackle_monitor for synchronizing process
        * Return post_id which needed to sync or -1 if not
        */

        $object = get_option('cackle_monitor');
        if ($object->mode == 'by_channel') {
//if sync is called by pages, we need pause for 30 sec from the last sync
            if ($object->time + 15 > time()) {
                return -1;
            }
            if ($object->status == 'inprocess' && $object->time + 120 > time()) {
//do nothing because in progress
                return -1;
            }
            if ($object->status == 'next_page') {
// do sync with the same post
                $ret_object = new stdClass();
                $ret_object->post_id = $object->post_id;
                $ret_object->mode = "";
                return $ret_object;
            }
            if ($object->status == 'finish' || $object->time + 120 < time()) {
//get next post for sync from cackle_posts_update object

                if (!isset($object->counter) || $object->counter > 10000) $object->counter = 0;

                if ($object->counter % 2) {
                    $mode = 'sync';
                } else {
                    $mode = 'updates';
                }
                switch ($mode) {
                    case 'sync':
                        //continue sync process
                        $object->counter = $object->counter+1;
                        update_option('cackle_monitor', $object);
                        break;
                    case 'updates':
                        //starting getting updates and return -1 to prevent Sync proccess
                        ChannelHandler::init();
                        $object->counter = $object->counter+1;
                        update_option('cackle_monitor', $object);
                        return -1;
                        break;
                }
                $posts_update = get_option('cackle_posts_update');
                $posts_update_counter = 0;
                foreach ($posts_update as $channel => $property) {
                    $next_post_id = $channel;
                    unset($posts_update->$channel); //delete updated channel
                    update_option('cackle_posts_update', $posts_update);
                    $posts_update_counter++;
                    break;
                }
                if ($posts_update_counter == 0) return -1;
                $ret_object = new stdClass();
                $ret_object->post_id = $next_post_id;
                $ret_object->mode = "";
                return $ret_object;

            }

        } elseif ($object->mode == 'all_comments') {
            if ($object->status == 'inprocess' && $object->time + 120 > time()) {
//don't start if all comments sync in progress
                return -1;
            } else {
//we can't handle all_comments sync from here because it handles ajax requests, so
//we should start sync again from the max

                $object->post_id = 1;
                $object->mode = '';
                update_option('cackle_monitor', $object);
            }
        }
    }
}


?>