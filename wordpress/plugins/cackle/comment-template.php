<?php
if (!function_exists('is_comments_close')) {
    function is_comments_close() {
        global $wpdb;
        global $post;
        $post_id = $post->ID;
        $status = $wpdb->get_results($wpdb->prepare("
                SELECT comment_status
                FROM $wpdb->posts
                WHERE ID = %d
                ", $post_id));
        $status = $status[0];
        $comment_status = $status->comment_status;
        if ($comment_status == "closed") {
            $status = true;
        } else {
            $status = false;
        }
        return $status;
    }
}
if (!is_comments_close()) {
    do_action('comment_form_before');
}
if (version_compare(get_bloginfo('version'), '4.0', '>=')) {
?>
    <style>
    #mc-container{
        padding: 10px;
    }
</style>
<?php

}


function get_avatar_path($id) {
    $avatar_path = get_avatar($id);
    $avatar_path = str_replace("&#038;", "&", $avatar_path);
    preg_match("/src=(\'|\")(.*)(\'|\")/Uis", $avatar_path, $matches);
    $avatar_src = substr(trim($matches[0]), 5, strlen($matches[0]) - 6);
    if (strpos($avatar_src, 'http') === false) {
        $avatar_src = get_option('siteurl') . $avatar_src;
    }
    //var_dump($avatar_src);
    return $avatar_src;
}


function cackle_auth() {
    global $current_user;
    get_currentuserinfo();
    $timestamp = time();
    $siteApiKey = get_option('cackle_siteApiKey');
    if (is_user_logged_in()) {
        $user = array(
            'id' => $current_user->ID,
            'name' => $current_user->display_name,
            'email' => $current_user->user_email,
            'avatar' => get_avatar_path($current_user->ID)
        );
        $user_data = base64_encode(json_encode($user));
    } else {
        $user = '{}';
        $user_data = base64_encode($user);
    }
    $sign = md5($user_data . $siteApiKey . $timestamp);
    return "$user_data $sign $timestamp";
}

$api_id = get_option('cackle_apiId', '');
if (!is_comments_close()) {

    require_once(dirname(__FILE__) . '/cackle_api.php');
    require_once(dirname(__FILE__) . '/sync.php');
    ?>

<?php  function cackle_comment($comment, $args, $depth) {
        $GLOBALS['comment'] = $comment;
        ?><li <?php comment_class(); ?> id="cackle-comment-<?php echo comment_ID(); ?>">
                    <div id="cackle-comment-header-<?php echo comment_ID(); ?>" class="cackle-comment-header">
                        <cite id="cackle-cite-<?php echo comment_ID(); ?>">
                            <?php if (comment_author_url()) : ?>
                            <a id="cackle-author-user-<?php echo comment_ID(); ?>"
                               href="<?php echo comment_author_url(); ?>" target="_blank"
                               rel="nofollow"><?php echo comment_author(); ?></a>
                            <?php else : ?>
                            <span id="cackle-author-user-<?php echo comment_ID(); ?>"><?php echo comment_author(); ?></span>
                            <?php endif; ?>
                        </cite>
                    </div>
        <div id="cackle-comment-body-<?php echo comment_ID(); ?>" class="cackle-comment-body">
            <div id="cackle-comment-message-<?php echo comment_ID(); ?>"
                 class="cackle-comment-message"><?php echo wp_filter_kses(comment_text()); ?></div>
        </div>
<?php } ?>
<div class="comments-area">
    <div id="mc-container">
        <div id="mc-content">

            <?php
            if (get_option('cackle_sync') == 1) { ?>
                <ul id="cackle-comments">
                    <?php
                    wp_list_comments(array('callback' => 'cackle_comment'));
                    ?>
                </ul>
                <?php } ?>
        </div>
    </div>
    <?php if (get_option("cackle_whitelabel", 0)==0): ?><a id="mc-link" href="http://cackle.me">Комментарии для сайта <b style="color:#4FA3DA">Cackl</b><b style="color:#F65077">e</b></a><?php endif; ?>
</div>

    <?php
    //define('ICL_LANGUAGE_CODE','xx');
    if (defined('ICL_LANGUAGE_CODE')) {
        switch (ICL_LANGUAGE_CODE){
            case 'uk':
                $lang_for_cackle = 'uk';
                break;
            case 'pt-br':
                $lang_for_cackle = 'pt';
                break;
            case 'be':
                $lang_for_cackle = 'be';
                break;
            case 'kk':
                $lang_for_cackle = 'kk';
            case 'en':
                $lang_for_cackle = 'en';
                break;
            case 'es':
                $lang_for_cackle = 'es';
                break;
            case 'de':
                $lang_for_cackle = 'de';
                break;
            case 'lv':
                $lang_for_cackle = 'lv';
                break;
            case 'el':
                $lang_for_cackle = 'el';
                break;
            case 'fr':
                $lang_for_cackle = 'fr';
            case 'ro':
                $lang_for_cackle = 'ro';
                break;
            case 'it':
                $lang_for_cackle = 'it';
                break;
            case 'ru':
                $lang_for_cackle = 'ru';
                break;
            default:
                $lang_for_cackle = NULL;
        }

    } else {
        $lang_for_cackle = NULL;
    }
    ?>

<script type="text/javascript">
    cackle_widget = window.cackle_widget || [];
    cackle_widget.push({
        widget: 'Comment', countContainer: '<?php print_r("c" . $post->ID)?>',
        id: '<?php echo $api_id?>',
        channel: '<?php echo $post->ID?>'
    <?php if (get_option('cackle_sso') == 1) : ?>, ssoAuth: '<?php print_r(cackle_auth()) ?>'<?php endif;?><?php if ($lang_for_cackle != NULL) : ?>,
        lang: '<?php print_r($lang_for_cackle) ?>'<?php endif;?>
        <?php if (get_option('cackle_counter') == 1){ ?>,
        callback: {
            ready: [function() {
                var count = document.getElementById('<?php print_r("c" . $post->ID)?>');
                if(count!=null){
                    var val = isNaN(parseInt(count.innerHTML))? 0: parseInt(count.innerHTML);
                    count.innerHTML=Cackle.Comment.lang[cackle_widget[0].lang].commentCount(val);
                }

                }]
            }
        <?php } ?>
        });
            document.getElementById('mc-container').innerHTML = '';
            (function() {
                var mc = document.createElement('script');
                mc.type = 'text/javascript';
                mc.async = true;
                mc.src = ('https:' == document.location.protocol ? 'https' : 'http') + '://cackle.me/widget.js';
                var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(mc, s.nextSibling);
            })();
    </script>

    
    

    <?php do_action('comment_form_after'); } ?>
<?php if ($api_id == ''): ?>API ID not specified<?php endif; ?>

