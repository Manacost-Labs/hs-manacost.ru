<?php
class CackleCounter{
    public static function init() {


        if (is_single() || is_page()){
        }
        else{

            //define('ICL_LANGUAGE_CODE','de');
            if (defined('ICL_LANGUAGE_CODE')) {
                switch (ICL_LANGUAGE_CODE){
                    case 'uk':
                        $lang_for_cackle = 'uk';
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
                        $lang_for_cackle = null;
                }

            } else {
                $lang_for_cackle = null;
            }

            ?>
            <script type="text/javascript">
                // <![CDATA[
                var nodes = document.getElementsByTagName('span');
                for (var i = 0, url; i < nodes.length; i++) {
                    if (nodes[i].className.indexOf('cackle-postid') != -1) {
                        var c_id = nodes[i].getAttribute('id').split('c');
                        nodes[i].parentNode.setAttribute('cackle-channel', c_id[1] );
                        url = nodes[i].parentNode.href.split('#', 1);
                        if (url.length == 1) url = url[0];
                        else url = url[1]
                        nodes[i].parentNode.href = url + '#mc-container';
                    }
                }


                cackle_widget = window.cackle_widget || [];
                cackle_widget.push({widget: 'CommentCount', <?php if(get_option('cackle_counter_rubrics', 1)==0) { ?> no: ' 0', one: ' 1', mult: ' {num}', <?php } ?> id: '<?php echo get_option('cackle_apiId') ?>'<?php if ($lang_for_cackle != null) : ?>, lang: '<?php print_r($lang_for_cackle) ?>'<?php endif;?>});
                (function() {
                    var mc = document.createElement('script');
                    mc.type = 'text/javascript';
                    mc.async = true;
                    mc.src = ('https:' == document.location.protocol ? 'https' : 'http') + '://cackle.me/widget.js';
                    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(mc, s.nextSibling);
                })();
                //]]>
            </script>

        <?php
        }
    }
}


?>