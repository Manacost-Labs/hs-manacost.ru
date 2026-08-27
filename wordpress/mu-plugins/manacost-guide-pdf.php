<?php
/**
 * Plugin Name: Manacost Guide PDF
 * Description: Adds administrator-only PDF and clean TXT guide exports for single Manacost articles.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Manacost_Guide_PDF {
    private const PDF_QUERY_VAR = 'manacost_guide_pdf';
    private const TXT_QUERY_VAR = 'manacost_guide_txt';
    private const CACHE_DIR = 'manacost-guide-pdf';

    public static function boot(): void {
        add_filter('the_content', [__CLASS__, 'add_download_button'], 12);
        add_action('template_redirect', [__CLASS__, 'maybe_render_export'], 0);
    }

    public static function add_download_button(string $content): string {
        if (is_admin() || is_feed() || wp_doing_ajax() || defined('MANACOST_GUIDE_PDF_RENDERING')) {
            return $content;
        }

        if (!is_singular('post') || !in_the_loop() || !is_main_query() || !current_user_can('manage_options')) {
            return $content;
        }

        $post_id = get_the_ID();
        if (!$post_id) {
            return $content;
        }

        $pdf_url = add_query_arg(self::PDF_QUERY_VAR, '1', get_permalink($post_id));
        $txt_url = add_query_arg(self::TXT_QUERY_VAR, '1', get_permalink($post_id));

        $buttons = sprintf(
            '<div class="manacost-guide-export-wrap" aria-label="Экспорт гайда для администраторов"><a class="manacost-guide-export-button" href="%s" rel="nofollow"><span class="manacost-guide-export-icon" aria-hidden="true">PDF</span><span>Скачать PDF-гайд</span></a><a class="manacost-guide-export-button manacost-guide-export-button-secondary" href="%s" rel="nofollow"><span class="manacost-guide-export-icon" aria-hidden="true">TXT</span><span>Скачать чистый TXT</span></a></div>',
            esc_url($pdf_url),
            esc_url($txt_url)
        );

        return self::button_css() . $buttons . $content;
    }

    public static function maybe_render_export(): void {
        $is_pdf = !empty($_GET[self::PDF_QUERY_VAR]);
        $is_txt = !empty($_GET[self::TXT_QUERY_VAR]);

        if ((!$is_pdf && !$is_txt) || !is_singular('post')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            status_header(403);
            nocache_headers();
            header('Content-Type: text/plain; charset=UTF-8');
            header('X-Robots-Tag: noindex, nofollow', true);
            echo 'Only administrators can download guide exports.';
            exit;
        }

        $post = get_queried_object();
        if (!$post instanceof WP_Post || $post->post_status !== 'publish') {
            status_header(404);
            exit;
        }

        if ($is_txt) {
            self::send_txt($post, self::build_txt($post));
        }

        if (!self::chromium_path()) {
            status_header(500);
            wp_die('Chromium is not available for PDF rendering.');
        }

        $cache_file = self::cache_file($post);
        if (!$cache_file) {
            status_header(500);
            wp_die('PDF cache directory is not writable.');
        }

        if (!file_exists($cache_file)) {
            $ok = self::generate_pdf($post, $cache_file);
            if (!$ok || !file_exists($cache_file) || filesize($cache_file) < 1024) {
                status_header(500);
                wp_die('Could not generate PDF guide.');
            }
        }

        self::send_pdf($post, $cache_file);
    }

    private static function button_css(): string {
        static $printed = false;
        if ($printed) {
            return '';
        }
        $printed = true;

        return '<style>
            .manacost-guide-export-wrap{display:flex;flex-wrap:wrap;gap:10px;margin:28px 0 10px;padding:16px;border:1px solid #d9e5f5;border-radius:10px;background:#f7fbff}
            .manacost-guide-export-button{display:inline-flex;align-items:center;gap:10px;padding:11px 16px;border-radius:8px;background:#0b5ed7;color:#fff!important;font-weight:800;text-decoration:none!important;box-shadow:0 8px 22px rgba(11,94,215,.18)}
            .manacost-guide-export-button:hover{background:#084db3;color:#fff!important}
            .manacost-guide-export-button-secondary{background:#12213a}
            .manacost-guide-export-button-secondary:hover{background:#0b1424}
            .manacost-guide-export-icon{display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:24px;border-radius:5px;background:rgba(255,255,255,.18);font-size:12px;letter-spacing:.04em}
            @media(max-width:520px){.manacost-guide-export-button{width:100%;justify-content:center}}
        </style>';
    }

    private static function cache_file(WP_Post $post): ?string {
        $upload = wp_upload_dir();
        if (empty($upload['basedir'])) {
            return null;
        }

        $dir = trailingslashit($upload['basedir']) . self::CACHE_DIR;
        if (!wp_mkdir_p($dir) || !is_writable($dir)) {
            return null;
        }

        $version = substr(md5($post->post_modified_gmt . '|' . $post->post_title . '|v5'), 0, 12);
        return trailingslashit($dir) . 'guide-' . absint($post->ID) . '-' . $version . '.pdf';
    }

    private static function generate_pdf(WP_Post $post, string $target_pdf): bool {
        $tmp_dir = trailingslashit(get_temp_dir()) . 'manacost-guide-pdf-' . $post->ID . '-' . wp_generate_password(8, false, false);
        if (!wp_mkdir_p($tmp_dir)) {
            return false;
        }

        $html_file = trailingslashit($tmp_dir) . 'guide.html';
        $profile_dir = trailingslashit($tmp_dir) . 'chrome-profile';
        wp_mkdir_p($profile_dir);

        $html = self::build_html($post);
        if (file_put_contents($html_file, $html) === false) {
            self::cleanup_dir($tmp_dir);
            return false;
        }

        $tmp_pdf = trailingslashit($tmp_dir) . 'guide.pdf';
        $cmd = [
            self::chromium_path(),
            '--headless',
            '--no-sandbox',
            '--disable-gpu',
            '--disable-dev-shm-usage',
            '--disable-extensions',
            '--no-first-run',
            '--no-default-browser-check',
            '--hide-scrollbars',
            '--run-all-compositor-stages-before-draw',
            '--virtual-time-budget=7000',
            '--user-data-dir=' . $profile_dir,
            '--print-to-pdf=' . $tmp_pdf,
            '--no-pdf-header-footer',
            '--print-to-pdf-no-header',
            'file://' . $html_file,
        ];

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $env = [
            'HOME' => $tmp_dir,
            'TMPDIR' => $tmp_dir,
            'PATH' => getenv('PATH') ?: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        ];

        $process = proc_open(self::escape_command($cmd), $descriptors, $pipes, ABSPATH, $env);
        if (!is_resource($process)) {
            self::cleanup_dir($tmp_dir);
            return false;
        }

        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit_code = proc_close($process);

        $ok = $exit_code === 0 && file_exists($tmp_pdf) && filesize($tmp_pdf) > 1024;
        if ($ok) {
            wp_mkdir_p(dirname($target_pdf));
            $ok = @rename($tmp_pdf, $target_pdf);
            if (!$ok) {
                $ok = @copy($tmp_pdf, $target_pdf);
            }
        }

        self::cleanup_dir($tmp_dir);
        return (bool) $ok;
    }

    private static function build_html(WP_Post $post): string {
        if (!defined('MANACOST_GUIDE_PDF_RENDERING')) {
            define('MANACOST_GUIDE_PDF_RENDERING', true);
        }

        setup_postdata($post);
        $content = apply_filters('the_content', $post->post_content);
        wp_reset_postdata();

        $content = self::prepare_content($content);
        $toc = self::build_toc($content);
        $featured = self::featured_image_html($post);
        $cats = get_the_category($post->ID);
        $cat_names = array_map(static fn($cat) => $cat->name, is_array($cats) ? $cats : []);
        $meta = trim(implode(' · ', array_filter([
            get_the_date('d.m.Y', $post),
            get_the_author_meta('display_name', (int) $post->post_author),
            implode(', ', $cat_names),
        ])));

        return '<!doctype html><html lang="ru"><head><meta charset="utf-8">' .
            '<title>' . esc_html(get_the_title($post)) . '</title>' .
            '<style>' . self::pdf_css() . '</style></head><body>' .
            '<main class="guide">' .
            '<header class="guide-cover">' .
            '<div class="brand-row"><div class="brand-mark">M</div><div><div class="brand-title">Manacost</div><div class="brand-subtitle">Hearthstone guide</div></div></div>' .
            '<h1>' . esc_html(get_the_title($post)) . '</h1>' .
            ($meta ? '<div class="guide-meta">' . esc_html($meta) . '</div>' : '') .
            '<a class="source-link" href="' . esc_url(get_permalink($post)) . '">' . esc_html(get_permalink($post)) . '</a>' .
            $featured .
            '</header>' .
            $toc .
            '<article class="guide-content">' . $content . '</article>' .
            '<footer class="guide-footer">Manacost · ' . esc_html(home_url('/')) . '</footer>' .
            '</main></body></html>';
    }

    private static function build_txt(WP_Post $post): string {
        if (!defined('MANACOST_GUIDE_PDF_RENDERING')) {
            define('MANACOST_GUIDE_PDF_RENDERING', true);
        }

        setup_postdata($post);
        $content = apply_filters('the_content', $post->post_content);
        wp_reset_postdata();

        $content = self::prepare_content($content);
        $cats = get_the_category($post->ID);
        $cat_names = array_map(static fn($cat) => $cat->name, is_array($cats) ? $cats : []);

        $header = [
            'Title: ' . get_the_title($post),
            'URL: ' . get_permalink($post),
            'Date: ' . get_the_date('Y-m-d', $post),
            'Author: ' . get_the_author_meta('display_name', (int) $post->post_author),
        ];

        if ($cat_names) {
            $header[] = 'Categories: ' . implode(', ', $cat_names);
        }

        return self::normalize_plain_text(implode("\n", $header) . "\n\n--- TEXT ---\n\n" . self::html_to_plain_text($content)) . "\n";
    }

    private static function prepare_content(string $content): string {
        $content = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $content);
        $content = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $content);
        $content = preg_replace('#<noscript\b[^>]*>.*?</noscript>#is', '', $content);
        $content = preg_replace('#<iframe\b[^>]*>.*?</iframe>#is', '', $content);
        $content = preg_replace('#<form\b[^>]*>.*?</form>#is', '', $content);
        $content = preg_replace('#<button\b[^>]*>.*?</button>#is', '', $content);
        $content = preg_replace('#<div[^>]+class=["\'][^"\']*(?:ad|ads|advert|banner|ya-share|share|cackle)[^"\']*["\'][^>]*>.*?</div>#is', '', $content);

        if (!class_exists('DOMDocument')) {
            return $content;
        }

        libxml_use_internal_errors(true);
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML('<?xml encoding="utf-8" ?><div id="mcpdf-root">' . $content . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($doc);

        // Tooltip icons are tiny inline markers on the site, but Chromium can
        // expand SVGs without intrinsic dimensions into huge standalone images
        // when article classes are stripped for the PDF. Keep the card names,
        // remove only those decorative icons.
        $tooltip_icon_nodes = [];
        foreach ($xpath->query('//*[contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"hs-set-icon") or contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"hs-bg-tier")]') as $node) {
            if ($node instanceof DOMNode && $node->parentNode) {
                $tooltip_icon_nodes[] = $node;
            }
        }
        foreach ($tooltip_icon_nodes as $node) {
            $node->parentNode->removeChild($node);
        }

        $remove_query = '//*[self::script or self::style or self::noscript or self::iframe or self::form or self::button or self::source or contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"adsbygoogle") or contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"td-a-rec") or contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"advert") or contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"banner") or contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"ya-share") or contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"share") or contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"cackle") or contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"comments") or contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"related") or contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"newsletter") or contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"subscribe") or contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"manacost-guide-export")]';
        $remove_nodes = [];
        foreach ($xpath->query($remove_query) as $node) {
            if ($node instanceof DOMNode && $node->parentNode) {
                $remove_nodes[] = $node;
            }
        }
        foreach ($remove_nodes as $node) {
            $node->parentNode->removeChild($node);
        }

        foreach ($xpath->query('//img') as $img) {
            if (!$img instanceof DOMElement) {
                continue;
            }

            $src = $img->getAttribute('src');
            if (!$src || self::starts_with($src, 'data:')) {
                foreach (['data-src', 'data-lazy-src', 'data-original', 'data-orig-file'] as $attr) {
                    $candidate = $img->getAttribute($attr);
                    if ($candidate) {
                        $img->setAttribute('src', self::absolute_url($candidate));
                        break;
                    }
                }
            }

            $src = $img->getAttribute('src');
            if ((!$src || self::starts_with($src, 'data:')) && $img->hasAttribute('srcset')) {
                $src = self::src_from_srcset($img->getAttribute('srcset'));
                if ($src) {
                    $img->setAttribute('src', self::absolute_url($src));
                    $src = $img->getAttribute('src');
                }
            }

            if ($src) {
                $img->setAttribute('src', self::absolute_url($src));
            }
            if ($img->hasAttribute('srcset')) {
                $img->removeAttribute('srcset');
            }
            $img->setAttribute('loading', 'eager');
            $img->setAttribute('decoding', 'sync');
        }

        foreach ($xpath->query('//*[@style or @class or @width or @height or @sizes]') as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            $node->removeAttribute('style');
            $node->removeAttribute('class');
            $node->removeAttribute('width');
            $node->removeAttribute('height');
            $node->removeAttribute('sizes');
        }

        foreach ($xpath->query('//a[@href]') as $link) {
            if ($link instanceof DOMElement) {
                $link->setAttribute('href', self::absolute_url($link->getAttribute('href')));
            }
        }

        foreach ($xpath->query('//h2 | //h3') as $index => $heading) {
            if ($heading instanceof DOMElement && !$heading->getAttribute('id')) {
                $heading->setAttribute('id', 'section-' . ($index + 1));
            }
        }

        $root = $doc->getElementById('mcpdf-root');
        $html = '';
        if ($root) {
            foreach ($root->childNodes as $child) {
                $html .= $doc->saveHTML($child);
            }
        }

        libxml_clear_errors();
        return $html ?: $content;
    }

    private static function html_to_plain_text(string $content): string {
        $content = preg_replace('#<br\s*/?>#i', "\n", $content);
        $content = preg_replace('#</(p|div|section|article|header|footer|blockquote|figure|figcaption|h[1-6]|ul|ol|table|tr)>#i', "\n", $content);
        $content = preg_replace('#<li[^>]*>#i', "\n- ", $content);
        $content = preg_replace('#</(td|th)>#i', "\t", $content);
        $text = wp_strip_all_tags($content, false);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return self::normalize_plain_text($text);
    }

    private static function normalize_plain_text(string $text): string {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = preg_replace('/ *\n */u', "\n", $text);
        $text = preg_replace('/\n{3,}/u', "\n\n", $text);
        return trim((string) $text);
    }

    private static function build_toc(string $content): string {
        if (!class_exists('DOMDocument')) {
            return '';
        }

        libxml_use_internal_errors(true);
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML('<?xml encoding="utf-8" ?><div>' . $content . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($doc);
        $items = [];

        foreach ($xpath->query('//h2 | //h3') as $heading) {
            if (!$heading instanceof DOMElement) {
                continue;
            }
            $text = trim($heading->textContent);
            $id = $heading->getAttribute('id');
            if ($text && $id) {
                $items[] = [
                    'level' => strtolower($heading->tagName),
                    'text' => $text,
                    'id' => $id,
                ];
            }
            if (count($items) >= 18) {
                break;
            }
        }

        libxml_clear_errors();
        if (count($items) < 3) {
            return '';
        }

        $out = '<nav class="guide-toc"><div class="toc-title">Содержание</div><ol>';
        foreach ($items as $item) {
            $class = $item['level'] === 'h3' ? ' class="toc-sub"' : '';
            $out .= '<li' . $class . '><a href="#' . esc_attr($item['id']) . '">' . esc_html($item['text']) . '</a></li>';
        }
        return $out . '</ol></nav>';
    }

    private static function featured_image_html(WP_Post $post): string {
        $image_id = get_post_thumbnail_id($post);
        if (!$image_id) {
            return '';
        }

        $src = wp_get_attachment_image_url($image_id, 'full');
        if (!$src) {
            return '';
        }

        $caption = wp_get_attachment_caption($image_id);
        return '<figure class="cover-image"><img src="' . esc_url($src) . '" alt="' . esc_attr(get_the_title($post)) . '">' .
            ($caption ? '<figcaption>' . esc_html($caption) . '</figcaption>' : '') .
            '</figure>';
    }

    private static function pdf_css(): string {
        return '
            @page{size:A4;margin:13mm 12mm 14mm}
            *{box-sizing:border-box}
            html{background:#fff}
            body{margin:0;background:#fff;color:#172033;font-family:Arial,"DejaVu Sans",sans-serif;font-size:13.5px;line-height:1.58;-webkit-print-color-adjust:exact;print-color-adjust:exact}
            .guide{width:100%;margin:0;background:#fff;min-height:100vh;padding:0 0 14px}
            .guide-cover{padding:24px 28px 18px;border-radius:12px;border:1px solid #dbe5f2;border-top:6px solid #0b5ed7;background:#f7fbff;color:#10203a;break-inside:avoid}
            .brand-row{display:flex;align-items:center;gap:12px;margin-bottom:18px}
            .brand-mark{width:40px;height:40px;border-radius:9px;display:flex;align-items:center;justify-content:center;background:#0b5ed7;color:#fff;font-size:22px;font-weight:900}
            .brand-title{font-size:18px;font-weight:900;letter-spacing:0}
            .brand-subtitle{font-size:10px;text-transform:uppercase;color:#64748b;letter-spacing:.08em}
            h1{margin:0 0 10px;font-size:30px;line-height:1.14;font-weight:900;letter-spacing:0;color:#10203a}
            .guide-meta{font-size:12.5px;color:#475569;margin-bottom:5px}
            .source-link{display:block;color:#0b5ed7;font-size:10.5px;text-decoration:none;word-break:break-all}
            .cover-image{margin:18px 0 0}
            .cover-image img{display:block;width:100%;max-height:340px;object-fit:contain;border-radius:10px;border:1px solid #d9e2ef;background:#fff}
            figcaption{font-size:10.5px;color:#64748b;margin-top:6px;text-align:center}
            .guide-toc{margin:18px 0 18px;padding:16px 18px;border-radius:10px;border:1px solid #d9e2ef;background:#f8fafc;break-inside:avoid}
            .toc-title{font-weight:900;font-size:16px;margin-bottom:8px;color:#10203a}
            .guide-toc ol{margin:0;padding-left:20px;columns:2;column-gap:24px}
            .guide-toc li{margin:0 0 5px;break-inside:avoid}
            .guide-toc .toc-sub{font-size:12px;color:#526176}
            .guide-toc a{color:#0b5ed7;text-decoration:none}
            .guide-content{padding:0}
            .guide-content h2,.guide-content h3,.guide-content h4{color:#10203a;line-height:1.2;break-after:avoid}
            .guide-content h2{font-size:22px;margin:24px 0 11px;padding-bottom:8px;border-bottom:2px solid #dbe7f6}
            .guide-content h3{font-size:18px;margin:20px 0 9px}
            .guide-content h4{font-size:15px;margin:16px 0 8px}
            .guide-content p{margin:0 0 12px}
            .guide-content a{color:#0b5ed7;text-decoration:none}
            .guide-content img{max-width:100%;height:auto!important;max-height:620px;object-fit:contain;border-radius:8px;border:1px solid #dbe2ec;display:block;margin:14px auto;background:#fff;break-inside:avoid}
            .guide-content figure{margin:16px 0;text-align:center;break-inside:avoid}
            .guide-content ul,.guide-content ol{padding-left:23px;margin:0 0 14px}
            .guide-content li{margin-bottom:5px}
            .guide-content blockquote{margin:18px 0;padding:14px 18px;border-left:5px solid #0b5ed7;background:#f4f8ff;color:#24324a;break-inside:avoid}
            .guide-content table{width:100%;border-collapse:collapse;margin:16px 0;break-inside:avoid;font-size:11.5px}
            .guide-content th,.guide-content td{border:1px solid #d8e1ef;padding:8px;vertical-align:top}
            .guide-content th{background:#eef5ff;color:#10203a}
            .guide-footer{margin:18px 0 0;padding-top:12px;border-top:1px solid #dbe2ec;color:#64748b;font-size:10.5px;text-align:center}
            @media print{.guide-toc ol{columns:2}a{color:#0b5ed7}}
        ';
    }

    private static function chromium_path(): string {
        static $path = null;
        if ($path !== null) {
            return $path;
        }

        foreach (['/usr/bin/chromium', '/usr/bin/chromium-browser', '/usr/bin/google-chrome', '/usr/bin/google-chrome-stable'] as $candidate) {
            if (is_executable($candidate)) {
                $path = $candidate;
                return $path;
            }
        }

        $path = '';
        return '';
    }

    private static function escape_command(array $parts): string {
        return implode(' ', array_map('escapeshellarg', $parts));
    }

    private static function absolute_url(string $url): string {
        $url = trim($url);
        if ($url === '' || self::starts_with($url, 'data:') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
            return $url;
        }
        if (self::starts_with($url, '//')) {
            return is_ssl() ? 'https:' . $url : 'http:' . $url;
        }
        if (self::starts_with($url, '/')) {
            return home_url($url);
        }
        return home_url('/' . ltrim($url, '/'));
    }

    private static function src_from_srcset(string $srcset): string {
        $best_url = '';
        $best_width = 0;

        foreach (explode(',', $srcset) as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '') {
                continue;
            }

            $parts = preg_split('/\s+/', $candidate);
            $url = $parts[0] ?? '';
            $width = 0;

            foreach ($parts as $part) {
                if (preg_match('/^(\d+)w$/', $part, $match)) {
                    $width = (int) $match[1];
                    break;
                }
            }

            if (!$best_url || $width > $best_width) {
                $best_url = $url;
                $best_width = $width;
            }
        }

        return $best_url;
    }

    private static function starts_with(string $value, string $prefix): bool {
        return substr($value, 0, strlen($prefix)) === $prefix;
    }

    private static function download_base_name(WP_Post $post): string {
        $download_name = sanitize_file_name(get_the_title($post));
        return $download_name ?: 'manacost-guide-' . $post->ID;
    }

    private static function send_pdf(WP_Post $post, string $file): void {
        while (ob_get_level()) {
            ob_end_clean();
        }

        $download_name = self::download_base_name($post);
        $ascii_fallback = 'manacost-guide-' . $post->ID . '.pdf';
        $utf8_name = rawurlencode($download_name . '.pdf');

        nocache_headers();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $ascii_fallback . '"; filename*=UTF-8\'\'' . $utf8_name);
        header('Content-Length: ' . filesize($file));
        header('X-Robots-Tag: noindex, nofollow', true);
        readfile($file);
        exit;
    }

    private static function send_txt(WP_Post $post, string $text): void {
        while (ob_get_level()) {
            ob_end_clean();
        }

        $download_name = self::download_base_name($post);
        $ascii_fallback = 'manacost-guide-' . $post->ID . '.txt';
        $utf8_name = rawurlencode($download_name . '.txt');

        nocache_headers();
        header('Content-Type: text/plain; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $ascii_fallback . '"; filename*=UTF-8\'\'' . $utf8_name);
        header('Content-Length: ' . strlen($text));
        header('X-Robots-Tag: noindex, nofollow', true);
        echo $text;
        exit;
    }

    private static function cleanup_dir(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if (!is_array($items)) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                self::cleanup_dir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}

Manacost_Guide_PDF::boot();
