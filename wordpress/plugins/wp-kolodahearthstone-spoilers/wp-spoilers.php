<?php
/*
Plugin Name: KolodaHearthstone: Spoilers
Description: Плагин для создания красивых спойлеров с разными цветами
Version: 1.0
Author: Manacost
*/

if (!defined('ABSPATH')) exit;

if (!class_exists('WP_Spoilers')) {

class WP_Spoilers {

    public function __construct() {
        add_shortcode('spoiler', [$this, 'render_spoiler']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('admin_init', [$this, 'add_tinymce_button']);
    }

    public function enqueue_styles() {
        // Стили встроены в шорткод для лучшей производительности
    }

    public function add_tinymce_button() {
        if (current_user_can('edit_posts') || current_user_can('edit_pages')) {
            add_filter('mce_external_plugins', [$this, 'add_tinymce_plugin']);
            add_filter('mce_buttons', [$this, 'register_tinymce_button']);
            add_filter('mce_css', [$this, 'add_tinymce_css']);
        }
    }

    public function add_tinymce_plugin($plugin_array) {
        $plugin_array['mtp_spoiler'] = plugins_url('tinymce-spoiler.js', __FILE__);
        return $plugin_array;
    }

    public function add_tinymce_css($mce_css) {
        if (!empty($mce_css)) $mce_css .= ',';
        $mce_css .= plugins_url('tinymce-spoiler.css', __FILE__);
        return $mce_css;
    }

    public function register_tinymce_button($buttons) {
        array_push($buttons, 'mtp_spoiler');
        return $buttons;
    }

    public function render_spoiler($atts, $content = null) {
        $atts = shortcode_atts([
            'title' => 'Спойлер',
            'color' => 'default', // default, blue, green, red, purple, orange, yellow, brown
            'icon' => '▼'
        ], $atts);

        $color_map = [
            'default' => ['bg' => 'rgba(139,117,95,0.15)', 'border' => 'rgba(139,117,95,0.3)', 'text' => '#2d1b0e', 'hover' => 'rgba(139,117,95,0.2)'],
            'blue' => ['bg' => 'rgba(49,130,206,0.15)', 'border' => 'rgba(49,130,206,0.3)', 'text' => '#1e3a5f', 'hover' => 'rgba(49,130,206,0.2)'],
            'green' => ['bg' => 'rgba(56,161,105,0.15)', 'border' => 'rgba(56,161,105,0.3)', 'text' => '#22543d', 'hover' => 'rgba(56,161,105,0.2)'],
            'red' => ['bg' => 'rgba(229,62,62,0.15)', 'border' => 'rgba(229,62,62,0.3)', 'text' => '#742a2a', 'hover' => 'rgba(229,62,62,0.2)'],
            'purple' => ['bg' => 'rgba(128,90,213,0.15)', 'border' => 'rgba(128,90,213,0.3)', 'text' => '#553c9a', 'hover' => 'rgba(128,90,213,0.2)'],
            'orange' => ['bg' => 'rgba(237,137,54,0.15)', 'border' => 'rgba(237,137,54,0.3)', 'text' => '#7c2d12', 'hover' => 'rgba(237,137,54,0.2)'],
            'yellow' => ['bg' => 'rgba(255,193,7,0.15)', 'border' => 'rgba(255,193,7,0.3)', 'text' => '#78350f', 'hover' => 'rgba(255,193,7,0.2)'],
            'brown' => ['bg' => 'rgba(139,90,60,0.15)', 'border' => 'rgba(139,90,60,0.3)', 'text' => '#3d2817', 'hover' => 'rgba(139,90,60,0.2)']
        ];

        $colors = isset($color_map[$atts['color']]) ? $color_map[$atts['color']] : $color_map['default'];
        $unique_id = 'spoiler-' . uniqid();
        
        $content = do_shortcode($content);
        $content = wpautop($content);
        
        return '<style>
        #'.$unique_id.'.mtp-spoiler-wrapper{margin:20px 0}
        #'.$unique_id.' .mtp-spoiler-toggle{background:'.$colors['bg'].';border:2px solid '.$colors['border'].';border-radius:12px;padding:14px 18px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;transition:all 0.3s ease;font-weight:800;color:'.$colors['text'].';font-size:1rem;letter-spacing:0.2px;box-shadow:0 2px 8px rgba(0,0,0,0.05)}
        #'.$unique_id.' .mtp-spoiler-toggle:hover{background:'.$colors['hover'].';transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,0.1)}
        #'.$unique_id.' .mtp-spoiler-toggle.active{border-bottom-left-radius:0;border-bottom-right-radius:0;border-bottom:1px solid '.$colors['border'].'}
        #'.$unique_id.' .mtp-spoiler-icon{transition:transform 0.3s ease;font-size:0.9rem;margin-left:12px;display:inline-block}
        #'.$unique_id.' .mtp-spoiler-toggle.active .mtp-spoiler-icon{transform:rotate(180deg)}
        #'.$unique_id.' .mtp-spoiler-content{background:rgba(250,245,240,0.95);border:2px solid '.$colors['border'].';border-top:none;border-radius:0 0 12px 12px;padding:20px 25px;display:none;box-shadow:0 4px 12px rgba(0,0,0,0.08);backdrop-filter:blur(10px)}
        #'.$unique_id.' .mtp-spoiler-content.active{display:block;animation:spoilerSlideDown 0.3s ease}
        #'.$unique_id.' .mtp-spoiler-content img{max-width:100%;height:auto;border-radius:8px;margin:15px 0;box-shadow:0 4px 12px rgba(0,0,0,0.1)}
        #'.$unique_id.' .mtp-spoiler-content p{margin:12px 0;line-height:1.7;color:#3d2f1f}
        #'.$unique_id.' .mtp-spoiler-content h1,#'.$unique_id.' .mtp-spoiler-content h2,#'.$unique_id.' .mtp-spoiler-content h3,#'.$unique_id.' .mtp-spoiler-content h4,#'.$unique_id.' .mtp-spoiler-content h5,#'.$unique_id.' .mtp-spoiler-content h6{color:#2d1b0e;margin-top:20px;margin-bottom:12px;font-weight:800}
        #'.$unique_id.' .mtp-spoiler-content ul,#'.$unique_id.' .mtp-spoiler-content ol{margin:15px 0;padding-left:25px;color:#3d2f1f}
        #'.$unique_id.' .mtp-spoiler-content li{margin:8px 0;line-height:1.6}
        #'.$unique_id.' .mtp-spoiler-content blockquote{border-left:4px solid '.$colors['border'].';padding-left:20px;margin:20px 0;font-style:italic;color:#6b5d4a;background:rgba(250,245,240,0.5);padding:15px 20px;border-radius:8px}
        #'.$unique_id.' .mtp-spoiler-content code{background:rgba(139,117,95,0.1);padding:3px 8px;border-radius:4px;font-family:monospace;font-size:0.9em;color:#2d1b0e}
        #'.$unique_id.' .mtp-spoiler-content pre{background:rgba(139,117,95,0.1);padding:15px;border-radius:8px;overflow-x:auto;margin:15px 0}
        #'.$unique_id.' .mtp-spoiler-content table{width:100%;border-collapse:collapse;margin:20px 0}
        #'.$unique_id.' .mtp-spoiler-content table th,#'.$unique_id.' .mtp-spoiler-content table td{padding:12px;border:1px solid rgba(139,117,95,0.2);text-align:left}
        #'.$unique_id.' .mtp-spoiler-content table th{background:'.$colors['bg'].';font-weight:800;color:'.$colors['text'].'}
        @keyframes spoilerSlideDown{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
        @media (max-width:600px){#'.$unique_id.' .mtp-spoiler-toggle{padding:12px 15px;font-size:0.95rem}#'.$unique_id.' .mtp-spoiler-content{padding:18px 20px}}
        </style>
        <div id="'.$unique_id.'" class="mtp-spoiler-wrapper">
            <div class="mtp-spoiler-toggle" onclick="this.classList.toggle(\'active\'); this.nextElementSibling.classList.toggle(\'active\');">
                <span>'.esc_html($atts['title']).'</span>
                <span class="mtp-spoiler-icon">'.esc_html($atts['icon']).'</span>
            </div>
            <div class="mtp-spoiler-content">
                '.$content.'
            </div>
        </div>';
    }
}

new WP_Spoilers();
}

