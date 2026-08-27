<?php
/**
 * Plugin Name: Plausible Analytics
 * Description: Adds Plausible tracking, article metadata, read-depth events and Koloda redirects for hs-manacost.ru.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Manacost_Plausible_Analytics
{
    private const DOMAIN = 'hs-manacost.ru';
    private const TRACKER_SRC = 'https://hs-manacost.ru/mca/script.js';
    private const TRACKER_API = 'https://hs-manacost.ru/mca/event';
    private const LOCAL_EVENT_ENDPOINT = 'http://127.0.0.1:8000/api/event';
    private static bool $tracked_koloda_redirect = false;

    public static function bootstrap(): void
    {
        add_action('wp_head', [__CLASS__, 'render_tracker'], 20);
        add_action('redirection_visit', [__CLASS__, 'track_redirection_visit'], 20, 3);
        add_filter('wp_redirect', [__CLASS__, 'track_koloda_redirect'], 1, 2);
    }

    public static function render_tracker(): void
    {
        if (is_admin()) {
            return;
        }

        $props = self::content_props();
        $attrs = [
            'defer' => true,
            'data-domain' => self::DOMAIN,
            'data-api' => self::TRACKER_API,
            'src' => self::TRACKER_SRC,
        ];

        foreach ($props as $name => $value) {
            if ($value !== '') {
                $attrs['event-' . $name] = $value;
            }
        }

        echo "\n" . '<script' . self::html_attrs($attrs) . '></script>' . "\n";

        if (is_singular('post')) {
            self::render_article_events_script($props, true);
        }
    }

    public static function track_koloda_redirect($location, $status)
    {
        if (self::$tracked_koloda_redirect) {
            return $location;
        }

        self::maybe_track_koloda_redirect($location, $status);

        return $location;
    }

    public static function track_redirection_visit($redirect, $url, $target): void
    {
        $status = method_exists($redirect, 'get_action_code') ? (int) $redirect->get_action_code() : 0;
        $source_url = home_url($url ?: ($_SERVER['REQUEST_URI'] ?? '/'));

        self::maybe_track_koloda_redirect($target, $status, $source_url);
    }

    private static function maybe_track_koloda_redirect($location, $status, ?string $source_url = null): void
    {
        $host = wp_parse_url($location, PHP_URL_HOST);

        if (!$host && strpos($location, '//') === 0) {
            $host = wp_parse_url('https:' . $location, PHP_URL_HOST);
        }

        $host = strtolower((string) $host);

        if ($host !== 'kolodahearthstone.ru' && $host !== 'www.kolodahearthstone.ru') {
            return;
        }

        if ($source_url === null) {
            $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
            $source_url = home_url($request_uri ?: '/');
        }

        $path = wp_parse_url($source_url, PHP_URL_PATH) ?: '/';

        self::send_server_event('Koloda Redirect', $source_url, [
            'source_path' => self::clean_prop($path, 240),
            'target_host' => self::clean_prop($host, 120),
            'target_url' => self::clean_prop($location, 300),
            'redirect_status' => (string) absint($status),
        ]);

        self::$tracked_koloda_redirect = true;
    }

    private static function content_props(): array
    {
        $props = [
            'content_type' => self::content_type(),
            'post_type' => is_singular() ? get_post_type() : '',
            'author' => '',
            'primary_category' => '',
            'categories' => '',
            'tags' => '',
        ];

        if (!is_singular()) {
            return array_filter($props, static fn($value) => $value !== '');
        }

        $post_id = get_queried_object_id();
        if (!$post_id) {
            return array_filter($props, static fn($value) => $value !== '');
        }

        $author_id = (int) get_post_field('post_author', $post_id);
        if ($author_id) {
            $props['author'] = self::clean_prop(get_the_author_meta('display_name', $author_id), 120);
        }

        $categories = self::term_names($post_id, 'category', 8);
        $tags = self::term_names($post_id, 'post_tag', 16);

        if ($categories) {
            $props['primary_category'] = self::clean_prop($categories[0], 120);
            $props['categories'] = self::clean_prop(implode(', ', $categories), 240);
        }

        if ($tags) {
            $props['tags'] = self::clean_prop(implode(', ', $tags), 240);
        }

        return array_filter($props, static fn($value) => $value !== '');
    }

    private static function content_type(): string
    {
        if (is_singular('post')) {
            return 'article';
        }
        if (is_page()) {
            return 'page';
        }
        if (is_front_page() || is_home()) {
            return 'home';
        }
        if (is_category()) {
            return 'category_archive';
        }
        if (is_tag()) {
            return 'tag_archive';
        }
        if (is_search()) {
            return 'search';
        }
        if (is_404()) {
            return '404';
        }
        if (is_archive()) {
            return 'archive';
        }

        return 'other';
    }

    private static function term_names(int $post_id, string $taxonomy, int $limit): array
    {
        $terms = get_the_terms($post_id, $taxonomy);
        if (!$terms || is_wp_error($terms)) {
            return [];
        }

        $names = [];
        foreach ($terms as $term) {
            $names[] = self::clean_prop($term->name, 80);
        }

        return array_slice(array_values(array_filter(array_unique($names))), 0, $limit);
    }

    private static function render_article_events_script(array $props, bool $track_koloda_links): void
    {
        $payload = wp_json_encode($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $track_links = $track_koloda_links ? 'true' : 'false';

        echo "<script>\n";
        echo "(function(){\n";
        echo "var baseProps={$payload};\n";
        echo "var trackKolodaLinks={$track_links};\n";
        echo "window.plausible=window.plausible||function(){(window.plausible.q=window.plausible.q||[]).push(arguments)};\n";
        echo "function copy(extra){var out={},k;for(k in baseProps){if(Object.prototype.hasOwnProperty.call(baseProps,k)){out[k]=String(baseProps[k]);}}for(k in extra){if(Object.prototype.hasOwnProperty.call(extra,k)){out[k]=String(extra[k]);}}return out;}\n";
        echo "function send(name,props){window.plausible(name,{props:copy(props||{})});}\n";
        echo "function bucket(percent){if(percent>=100)return '100';if(percent>=90)return '90';if(percent>=75)return '75';if(percent>=50)return '50';if(percent>=25)return '25';return '0-24';}\n";
        echo "var start=Date.now(),maxDepth=0,readSent=false,depthSent=false;\n";
        echo "function articleRoot(){return document.querySelector('article')||document.querySelector('.entry-content')||document.querySelector('.post-content')||document.querySelector('.td-post-content')||document.body;}\n";
        echo "function depth(){var root=articleRoot(),doc=document.documentElement,body=document.body,top=window.pageYOffset||doc.scrollTop||body.scrollTop||0,view=window.innerHeight||doc.clientHeight||0;if(root&&root!==body){var rect=root.getBoundingClientRect(),startTop=top+rect.top,height=Math.max(root.scrollHeight,rect.height,1),seen=Math.max(0,Math.min(height,top+view-startTop));return Math.round(seen/height*100);}var full=Math.max(body.scrollHeight,body.offsetHeight,doc.clientHeight,doc.scrollHeight,doc.offsetHeight)-view;return full<=0?100:Math.round(top/full*100);}\n";
        echo "function check(){var current=Math.max(0,Math.min(100,depth()));if(current>maxDepth){maxDepth=current;}if(!readSent&&maxDepth>=90&&Date.now()-start>=15000){readSent=true;send('Article Read',{depth_bucket:bucket(maxDepth)});}}\n";
        echo "function sendDepth(){if(depthSent)return;check();depthSent=true;send('Scroll Depth',{depth_bucket:bucket(maxDepth)});}\n";
        echo "window.addEventListener('scroll',check,{passive:true});window.addEventListener('resize',check,{passive:true});window.addEventListener('pagehide',sendDepth);document.addEventListener('visibilitychange',function(){if(document.visibilityState==='hidden'){sendDepth();}else{check();}});setTimeout(check,1000);setInterval(check,5000);\n";
        echo "if(trackKolodaLinks){document.addEventListener('click',function(event){var link=event.target&&event.target.closest?event.target.closest('a[href]'):null;if(!link)return;var url;try{url=new URL(link.href,window.location.href);}catch(e){return;}if(url.hostname==='kolodahearthstone.ru'||url.hostname==='www.kolodahearthstone.ru'){send('Koloda Click',{source_path:window.location.pathname,target_host:url.hostname,target_url:url.href});}},true);}\n";
        echo "})();\n";
        echo "</script>\n";
    }

    private static function send_server_event(string $name, string $url, array $props): void
    {
        $body = [
            'n' => $name,
            'u' => $url,
            'd' => self::DOMAIN,
            'r' => isset($_SERVER['HTTP_REFERER']) ? self::clean_prop(wp_unslash($_SERVER['HTTP_REFERER']), 300) : null,
            'p' => $props,
        ];

        wp_remote_post(self::LOCAL_EVENT_ENDPOINT, [
            'blocking' => true,
            'timeout' => 1,
            'redirection' => 0,
            'headers' => self::event_headers(),
            'body' => wp_json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    private static function event_headers(): array
    {
        $headers = [
            'Content-Type' => 'text/plain',
            'Host' => 'stats.hs-manacost.ru',
        ];

        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $headers['User-Agent'] = self::clean_prop(wp_unslash($_SERVER['HTTP_USER_AGENT']), 300);
        }

        $ip = self::client_ip();
        if ($ip !== '') {
            $headers['X-Forwarded-For'] = $ip;
            $headers['X-Real-IP'] = $ip;
            $headers['CF-Connecting-IP'] = $ip;
        }

        return $headers;
    }

    private static function client_ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }

            $value = trim((string) wp_unslash($_SERVER[$key]));
            if ($key === 'HTTP_X_FORWARDED_FOR') {
                $value = trim(explode(',', $value)[0]);
            }

            if (filter_var($value, FILTER_VALIDATE_IP)) {
                return $value;
            }
        }

        return '';
    }

    private static function html_attrs(array $attrs): string
    {
        $html = '';
        foreach ($attrs as $name => $value) {
            if ($value === true) {
                $html .= ' ' . esc_attr($name);
                continue;
            }
            $html .= ' ' . esc_attr($name) . '="' . esc_attr((string) $value) . '"';
        }

        return $html;
    }

    private static function clean_prop($value, int $max): string
    {
        $value = html_entity_decode(wp_strip_all_tags((string) $value), ENT_QUOTES, get_bloginfo('charset') ?: 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', trim($value));

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max);
        }

        return substr($value, 0, $max);
    }
}

Manacost_Plausible_Analytics::bootstrap();
