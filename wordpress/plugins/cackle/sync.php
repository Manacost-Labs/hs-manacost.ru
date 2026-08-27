<?php

class Sync
{
    function __construct(){
    }

    function has_next($size_comments, $size_pagination = 100) {
        return $size_comments == $size_pagination;
    }

    function push_next_comments($mode, $size_comments, $channel, $cackle_last_modified) {
        //$time_start = microtime(true);
        $apix = new CackleAPI();
        $cackle_last_comment = $apix->get_last_comment_by_channel($channel, 0);
        $i = 1;
        while ($this->has_next($size_comments)) {
            $cackle_last_comment = $apix->get_last_comment_by_channel($channel, 0);
            $response = $apix->get_comments('last_comment', $cackle_last_comment, $channel, $i);
            $size_comments = $this->process_comments($response, $channel); // get comment from array and insert it to wp db
            $i++;
        }
        //$trace=debug_backtrace();
        //$function = $trace[0]["function"];
        //$mess='Function: ' . $function . ' execution_time' . (microtime(true) - $time_start)*1000 .PHP_EOL;
        //file_put_contents('execution_time.txt', $mess, FILE_APPEND);
    }

    function init($channel, $mode = ""){
        $apix = new CackleAPI();
        if ($mode == "") {
            $object = get_option('cackle_monitor');
            $object->post_id = $channel;
            $object->status = 'inprocess';
            $object->mode = 'by_channel';
            $object->time = time();
            update_option('cackle_monitor', $object);
            $modified_triger = get_option('cackle_modified_trigger');

            //Determine sync's criteria
            if (!isset($modified_triger->$channel) || (isset($modified_triger->$channel) && $modified_triger->$channel == 'c')) {
                $cackle_last_comment = $apix->get_last_comment_by_channel($channel, 0);
                $response = $apix->get_comments('last_comment', $cackle_last_comment, $channel);
            }
            if (isset($modified_triger->$channel) && $modified_triger->$channel == 'm') {
                $cackle_last_modified = $apix->get_last_modified_by_channel($channel, 0);
                $response = $apix->get_comments('last_modified', $cackle_last_modified, $channel);
            }
//get comments from Cackle Api for sync
            if ($response == NULL) {
                return false;
            }
            $size_comments = $this->process_comments($response, $channel); // get comment from array and insert it to wp db, and return size
            if ($this->has_next($size_comments)) {
                $object->status = 'next_page';
                $object->time = time();
                //$object->counter = $object->counter + 1;
                update_option('cackle_monitor', $object);
            } else {
                $modified_triger->$channel = 'm';
                update_option('cackle_modified_trigger', $modified_triger);

                //Update monitor status
                $object->status = 'finish';
                $object->time = time();
                //$object->counter = $object->counter + 1;
                update_option('cackle_monitor', $object);
            }
        }

        if ($mode == "all_comments") {
            $cackle_last_comment = $apix->get_last_comment_by_channel($channel, 0);
            //we need to set all_comments mode to  monitor to prevent there starting
            $object = get_option('cackle_monitor');
            $object->post_id = $channel;
            $object->status = 'inprocess';
            $object->mode = 'all_comments';
            $object->time = time();
            update_option('cackle_monitor', $object);

            $response = $apix->get_comments('last_comment', $cackle_last_comment, $channel);

            //get comments from Cackle Api for sync
            if ($response == NULL) {
                return false;
            }
            $size_comments = $this->process_comments($response, $channel); // get comment from array and insert it to wp db, and return size
            if ($this->has_next($size_comments)) {
                $this->push_next_comments($mode, $size_comments, $channel, $cackle_last_modified);
                $modified_triger = get_option('cackle_modified_trigger');
                $modified_triger->$channel = 'm';
                update_option('cackle_modified_trigger', $modified_triger);
            } else {
                //Initial sync completed
                $modified_triger = get_option('cackle_modified_trigger');
                $modified_triger->$channel = 'm';
                update_option('cackle_modified_trigger', $modified_triger);
            }
        }
        return "success";
    }

    /**
     * Decodes json to array
     * @return $obj
     */
    function cackle_json_decodes($response)
    {
        $obj = json_decode($response, true);
        return $obj;
    }

    /**
     * Get one comment from array $response and insert it to wp_comments throught insert_comm function
     * @return $obj
     */
    function process_comments($response, $channel)
    {
        //$time_start = microtime(true);
        global $wpdb;
        $apix = new CackleAPI();
        $obj = $this->cackle_json_decodes($response, true);
        $obj = isset($obj['comments']) ? $obj['comments'] : array();
        $comments_size = count($obj);
        if ($comments_size != 0) {
            //@mysqli_query("BEGIN", $wpdb->dbh);
            $parent_list = array();
            foreach ($obj as $comment) {

                if ($comment['id'] > $apix->get_last_comment_by_channel($channel, 0)) {
                    $comment_id = $comment['id'];
                    $count = $wpdb->get_results($wpdb->prepare("SELECT count(comment_ID) as count from $wpdb->comments  WHERE comment_agent = %s", "Cackle:{$comment_id}"));
                    if (isset($count[0]->count) && $count[0]->count == 0) {

                        if ($this->startsWith($comment['chan']['channel'], 'http')) {
                            $postid = url_to_postid($comment['chan']['channel']);
                        } else {
                            $postid = $comment['chan']['channel'];
                        }

                        $commentdata = $this->insert_comm($comment, $this->comment_status_decoder($comment));
                        $commentdata['comment_ID'] = wp_insert_comment($commentdata);
                        $comment_id = $commentdata['comment_ID'];
                        update_comment_meta($comment_id, 'cackle_parent_post_id', $comment['parentId']);
                        update_comment_meta($comment_id, 'cackle_post_id', $comment['id']);
                        if (isset($comment['parentId']) && $comment['parentId']) {
                            $parent_id = $wpdb->get_var($wpdb->prepare("SELECT comment_id FROM $wpdb->commentmeta WHERE meta_key = 'cackle_post_id' AND meta_value = %s LIMIT 1", $comment['parentId']));
                            
                            if ($parent_id) {
                                $wpdb->query(
                                    "
                                                UPDATE $wpdb->comments
                                                SET comment_parent = $parent_id
                                                WHERE comment_ID = $comment_id 
                                                    
                                                "
                                );
                            }
                        }


                        $apix->set_last_comment_by_channel($postid, $comment['id']);
                        if ($comment['modified'] > $apix->get_last_modified_by_channel($postid, 0)) {
                            $apix->set_last_modified_by_channel($postid, $comment['modified']);

                        }
                    }

                } else {
                    // if ($comment['modified'] > $apix->cackle_get_param('cackle_last_modified', 0)) {
                    $this->update_comment_status($comment['id'], $this->comment_status_decoder($comment), $comment['modified'], $comment['message'], $channel);
                    // }
                }
            }


            //mysqli_query("COMMIT", $wpdb->dbh);

        }
        //$trace=debug_backtrace();
        //$function = $trace[0]["function"];
        //$mess='Function: ' . $function . ' execution_time' . (microtime(true) - $time_start)*1000 .PHP_EOL;
        //file_put_contents('execution_time.txt', $mess, FILE_APPEND);

        return $comments_size;
    }

    function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    function comment_status_decoder($comment)
    {

        if (strtolower($comment['status']) == "approved") {
            $status = 1;
        } elseif (strtolower($comment['status'] == "pending") || strtolower($comment['status']) == "rejected") {
            $status = 0;
        } elseif (strtolower($comment['status']) == "spam") {
            $status = "spam";
        } elseif (strtolower($comment['status']) == "deleted") {
            $status = "trash";
        }
        return $status;
    }

    function update_comment_status($comment_id, $status, $modified, $comment_content, $channel)
    {
        //$time_start = microtime(true);

        $apix = new CackleAPI();
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE $wpdb->comments SET comment_approved = '$status' WHERE comment_agent = %s", "Cackle:{$comment_id}"));
        $wpdb->query($wpdb->prepare("UPDATE $wpdb->comments SET comment_content = %s WHERE comment_agent = %s", array($comment_content, "Cackle:{$comment_id}")));
        if ($modified > $apix->get_last_modified_by_channel($channel, 0)) {
            $apix->set_last_modified_by_channel($channel, $modified);

        }
        //$trace=debug_backtrace();
        //$function = $trace[0]["function"];
        //$mess='Function: ' . $function . ' execution_time' . (microtime(true) - $time_start)*1000 .PHP_EOL;
        //file_put_contents('execution_time.txt', $mess, FILE_APPEND);

    }

    function insert_comm($comment, $status) {
        //$time_start = microtime(true);
        $apix = new CackleAPI();
        global $wpdb;
        if ($this->startsWith($comment['chan']['channel'], 'http')) {
            $postid = url_to_postid($comment['chan']['channel']);
        } else {
            $postid = $comment['chan']['channel'];
        }

        if (isset($comment['author']) && $comment['author'] != null) {
            $comment_author = isset($comment['author']['name']) ? $comment['author']['name'] : "";
            $comment_author_email = isset($comment['author']['email']) ? $comment['author']['email'] : "";
            $comment_author_url = isset($comment['author']['www']) ? $comment['author']['www'] : "";
            if (isset($comment['author']['provider']) && $comment['author']['provider'] == 'sso') {
                if (isset($comment['author']['openId'])) {
                    $openId = $comment['author']['openId'];
                    $user_id = (int)substr($openId, strpos($openId, "_") + 1);
                }

            } else {
                $user_id = 0;
            }
        } else {
            $comment_author = isset($comment['anonym']['name']) ? $comment['anonym']['name'] : "";
            if (!isset($comment['anonym']['email'])) {
                $comment_author_email = NULL;
            } else {
                $comment_author_email = $comment['anonym']['email'];
            }
            $comment_author_url = isset($comment['anonym']['www']) ? $comment['anonym']['www'] : "";
        }
        $commentdata = array(
            'comment_post_ID' => $postid,
            'comment_author' => $comment_author,
            'comment_karma' => $comment['rating'],
            'comment_author_email' => $comment_author_email,
            'comment_date' => strftime("%Y-%m-%d %H:%M:%S", $comment['created'] / 1000 + (get_option('gmt_offset') * 3600)),
            'comment_date_gmt' => strftime("%Y-%m-%d %H:%M:%S", $comment['created'] / 1000 + (get_option('gmt_offset') * 3600)),
            'comment_author_url' => $comment_author_url,
            'comment_author_IP' => $comment['ip'],
            'comment_content' => apply_filters('pre_comment_content', $comment['message']),
            'comment_approved' => $status,
            'comment_agent' => 'Cackle:' . $comment['id'],
            'comment_type' => '',
            'user_id' => isset($user_id) ? $user_id : 0
        );
        return $commentdata;

    }
}

?>