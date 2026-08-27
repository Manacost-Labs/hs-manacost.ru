<?php
/*
Plugin Name: KolodaHearthstone: Banner
Description: Плагин для создания красивых баннеров с иконкой телеграма и кастомным шрифтом
Version: 1.0
Author: Manacost
*/

if (!defined('ABSPATH')) exit;

if (!class_exists('WP_Banner')) {

class WP_Banner {

    public function __construct() {
        add_shortcode('banner', [$this, 'render_banner']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('admin_init', [$this, 'add_tinymce_button']);
    }

    public function enqueue_styles() {
        // Стили встроены в шорткод для лучшей производительности
    }

    public function add_tinymce_button() {
        if (current_user_can('edit_posts') || current_user_can('edit_pages')) {
            add_filter('mce_external_plugins', [$this, 'add_tinymce_plugin'], 10);
            add_filter('mce_buttons', [$this, 'register_tinymce_button'], 10);
            add_filter('mce_buttons_2', [$this, 'register_tinymce_button'], 10);
            add_filter('mce_css', [$this, 'add_tinymce_css'], 10);
        }
    }

    public function add_tinymce_plugin($plugin_array) {
        $plugin_path = plugins_url('tinymce-banner.js', __FILE__);
        if (file_exists(plugin_dir_path(__FILE__) . 'tinymce-banner.js')) {
            $plugin_array['mtp_banner'] = $plugin_path . '?v=' . filemtime(plugin_dir_path(__FILE__) . 'tinymce-banner.js');
        }
        return $plugin_array;
    }

    public function add_tinymce_css($mce_css) {
        if (!empty($mce_css)) $mce_css .= ',';
        $mce_css .= plugins_url('tinymce-banner.css', __FILE__);
        return $mce_css;
    }

    public function register_tinymce_button($buttons) {
        array_push($buttons, 'mtp_banner');
        return $buttons;
    }

    public function render_banner($atts, $content = null) {
        $atts = shortcode_atts([
            'title' => 'Баннер',
            'color' => 'default', // default, blue, green, red, purple, orange, yellow, brown
            'url' => '',
            'icon' => 'false' // больше не используется
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
        $unique_id = 'banner-' . uniqid();
        
        // Обрабатываем контент без добавления HTML тегов
        $content = trim($content);
        if (!empty($content)) {
            $content = do_shortcode($content);
            // Убираем лишние переносы строк и пробелы, но не добавляем <p> теги
            $content = preg_replace('/\n+/', ' ', $content);
            $content = trim($content);
        }
        
        // Путь к шрифту
        $font_url = plugins_url('../wp-kolodahearthstone-decks/font/2318-font.otf', __FILE__);
        
        // Определяем, есть ли ссылка
        $has_link = !empty($atts['url']);
        $wrapper_tag = $has_link ? 'a' : 'div';
        $wrapper_attrs = $has_link ? ' href="' . esc_url($atts['url']) . '" target="_blank" rel="noopener noreferrer"' : '';
        
        return '<style>
        @font-face {
            font-family: "BannerFont";
            src: url("' . esc_url($font_url) . '") format("opentype");
            font-weight: normal;
            font-style: normal;
        }
        #'.$unique_id.'.mtp-banner-wrapper{margin:25px 0;width:100%;display:block}
        #'.$unique_id.' .mtp-banner-container{background:'.$colors['bg'].';border:2px solid '.$colors['border'].';border-radius:12px;padding:20px 25px;display:block;transition:all 0.3s ease;box-shadow:0 2px 8px rgba(0,0,0,0.05);position:relative;overflow:hidden;text-decoration:none;color:inherit}
        #'.$unique_id.' .mtp-banner-container:hover{background:'.$colors['hover'].';transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,0.1);text-decoration:none}
        #'.$unique_id.' .mtp-banner-content{width:100%}
        #'.$unique_id.' .mtp-banner-title{font-family:"BannerFont",sans-serif;font-weight:800;color:'.$colors['text'].';font-size:1.3rem;margin:0 0 12px 0;letter-spacing:0.3px;line-height:1.3;display:block}
        #'.$unique_id.' .mtp-banner-text{margin:0;line-height:1.7;color:#3d2f1f;font-size:1rem;display:block;word-wrap:break-word}
        #'.$unique_id.' a.mtp-banner-container{text-decoration:none;display:block;cursor:pointer;color:inherit}
        #'.$unique_id.' a.mtp-banner-container:hover{text-decoration:none;color:inherit}
        #'.$unique_id.' a.mtp-banner-container:visited{color:inherit}
        #'.$unique_id.' a.mtp-banner-container:focus{outline:2px solid '.$colors['border'].';outline-offset:2px}
        @media (max-width:600px){
            #'.$unique_id.'.mtp-banner-wrapper{padding:0 15px;margin:20px 0}
            #'.$unique_id.' .mtp-banner-container{padding:18px 20px}
            #'.$unique_id.' .mtp-banner-title{font-size:1.15rem;margin-bottom:8px}
            #'.$unique_id.' .mtp-banner-text{font-size:0.95rem}
        }
        </style>
        <div id="'.$unique_id.'" class="mtp-banner-wrapper">
            <'.$wrapper_tag.$wrapper_attrs.' class="mtp-banner-container">
                <div class="mtp-banner-content">
                    <h3 class="mtp-banner-title">'.esc_html($atts['title']).'</h3>
                    <div class="mtp-banner-text">'.wp_kses_post($content).'</div>
                </div>
            </'.$wrapper_tag.'>
        </div>';
    }
}

new WP_Banner();
}

