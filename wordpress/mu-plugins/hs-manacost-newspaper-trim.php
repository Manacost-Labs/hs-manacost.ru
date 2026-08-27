<?php
/**
 * Plugin Name: HS Manacost Newspaper Asset Trim
 * Description: Keeps heavy Newspaper article scripts off listing pages where they are not used.
 */

defined('ABSPATH') || exit;

if (! is_admin() && ! (defined('DOING_AJAX') && DOING_AJAX) && ! defined('AI_EXTERNAL_JS')) {
    define('AI_EXTERNAL_JS', true);
}

function hs_manacost_newspaper_is_listing_front()
{
    return ! is_admin()
        && ! is_feed()
        && ! is_preview()
        && (is_front_page() || is_home());
}

function hs_manacost_newspaper_is_public_single()
{
    return ! is_admin()
        && ! is_feed()
        && ! is_preview()
        && is_single();
}

function hs_manacost_newspaper_should_guard_public_search()
{
    return ! is_admin()
        && ! is_feed()
        && ! is_preview()
        && ! is_user_logged_in();
}

function hs_manacost_newspaper_disable_native_ajax_counter()
{
    if (! hs_manacost_newspaper_is_public_single() || ! class_exists('tagdiv_options')) {
        return;
    }

    tagdiv_options::get('tds_ajax_post_view_count');
    if (is_array(tagdiv_options::$td_options)) {
        tagdiv_options::$td_options['tds_ajax_post_view_count'] = '';
    }
}

add_action('wp', 'hs_manacost_newspaper_disable_native_ajax_counter', 0);
add_filter('hs_manacost_disable_td_ajax_post_view_count', '__return_true');

add_action('wp_enqueue_scripts', function () {
    if (! hs_manacost_newspaper_is_listing_front()) {
        return;
    }

    foreach (array('tdPostImages', 'tdSocialSharing', 'tdModalPostImages') as $handle) {
        wp_dequeue_script($handle);
        wp_deregister_script($handle);
    }
}, 1000);

add_action('template_redirect', function () {
    if (! hs_manacost_newspaper_is_listing_front() && ! hs_manacost_newspaper_is_public_single()) {
        return;
    }

    hs_manacost_newspaper_disable_native_ajax_counter();
    ob_start('hs_manacost_newspaper_trim_front_html');
}, 0);

add_action('wp_footer', function () {
    if (! hs_manacost_newspaper_should_guard_public_search()) {
        return;
    }
    ?>
<script id="hs-newspaper-search-guard">
(function () {
    var minChars = 3;
    var delayMs = 450;

    function normalize(value) {
        return (value || '').replace(/\s+/g, ' ').trim();
    }

    function inputValue(selector) {
        var node = document.querySelector(selector);
        return node ? normalize(node.value) : '';
    }

    function guardMethod(objectName, methodName, selector) {
        var timer = null;

        function install() {
            var target = window[objectName];
            if (!target || typeof target[methodName] !== 'function' || target[methodName].hsGuarded) {
                return;
            }

            var original = target[methodName];
            target[methodName] = function () {
                var context = this;
                var args = arguments;
                var query = selector ? inputValue(selector) : '';

                if (query && query.length < minChars) {
                    return;
                }

                window.clearTimeout(timer);
                timer = window.setTimeout(function () {
                    original.apply(context, args);
                }, delayMs);
            };
            target[methodName].hsGuarded = true;
        }

        install();
        document.addEventListener('DOMContentLoaded', install);
        window.setTimeout(install, 1500);
    }

    guardMethod('tdAjaxSearch', 'do_ajax_call', '#td-header-search');
    guardMethod('tdAjaxSearch', 'do_ajax_call_mob', '#td-header-search-mob');
})();
</script>
    <?php
}, 1000);

function hs_manacost_newspaper_trim_front_html($html)
{
    if (! is_string($html) || $html === '') {
        return $html;
    }

    if (hs_manacost_newspaper_is_public_single()) {
        $html = hs_manacost_newspaper_strip_ajax_count($html);
    }

    foreach (array('tdToTop-js', 'tdSmartSidebar-js') as $script_id) {
        $pattern = '#<script\b(?=[^>]*\bid=["\']' . preg_quote($script_id, '#') . '["\'])[^>]*>\s*</script>\s*#i';
        $html = preg_replace($pattern, '', $html);
    }

    if (strpos($html, 'yandex-metrica-watch/watch.js') !== false) {
        $replacement = <<<'JS'
if (w.opera == "[object Opera]") {
            d.addEventListener("DOMContentLoaded", function () { w.setTimeout(f, 15000); }, false);
        } else if (d.readyState === "complete") {
            w.setTimeout(f, 15000);
        } else {
            w.addEventListener("load", function () { w.setTimeout(f, 15000); }, false);
        }
JS;

        $html = preg_replace(
            '#if\s*\(\s*w\.opera\s*==\s*"\[object Opera\]"\s*\)\s*\{\s*d\.addEventListener\("DOMContentLoaded",\s*f,\s*false\);\s*\}\s*else\s*\{\s*f\(\);\s*\}#',
            $replacement,
            $html,
            1
        );
    }

    return $html;
}

function hs_manacost_newspaper_strip_ajax_count($html)
{
    $html = preg_replace(
        '#<script\b(?=[^>]*\bid=["\']tdAjaxCount-js["\'])[^>]*>\s*</script>\s*#i',
        '',
        $html
    );

    return preg_replace(
        '#<script\b(?=[^>]*\bid=["\']td-generated-footer-js["\'])[^>]*>.*?tdAjaxCount\.tdGetViewsCountsAjax\s*\([^;]*;\s*.*?</script>\s*#is',
        '',
        $html
    );
}
