<?php
/**
 * Reduce white first paint and preload only the leading LCP image.
 */
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('font_awesome');
    wp_deregister_style('font_awesome');
}, 100);

add_action('wp_head', function () {
    echo '<style id="hs-fontawesome-fallback">.tdc-font-fa-check:before{content:"\2713";font-family:Arial,sans-serif;font-weight:700;line-height:1}.tdc-font-fa{font-family:Arial,sans-serif}</style>' . "\n";
}, 1);

function hs_manacost_webp_url_for_upload($url) {
    if (!preg_match('/\.(?:jpe?g|png)(?:[?#].*)?$/i', $url)) {
        return false;
    }

    $parts = wp_parse_url($url);
    $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
    if (empty($parts['host']) || strcasecmp($parts['host'], $site_host) !== 0 || empty($parts['path'])) {
        return false;
    }

    $uploads = wp_get_upload_dir();
    $base_path = wp_parse_url($uploads['baseurl'], PHP_URL_PATH);
    if (!$base_path || strpos($parts['path'], $base_path . '/') !== 0) {
        return false;
    }

    $relative_path = rawurldecode(substr($parts['path'], strlen($base_path)));
    $source_path = $uploads['basedir'] . $relative_path;
    $webp_path = preg_replace('/\.(?:jpe?g|png)$/i', '.webp', $source_path);
    if ($webp_path && is_readable($webp_path)) {
        return preg_replace('/\.(?:jpe?g|png)([?#].*)?$/i', '.webp$1', $url);
    }

    $webp_path = $source_path . '.webp';
    if (is_readable($webp_path)) {
        return preg_replace('/([?#].*)?$/', '.webp$1', $url);
    }

    return false;
}

function hs_manacost_rewrite_backgrounds_to_webp($html) {
    return preg_replace_callback(
        '/<span\b(?=[^>]+class=["\'][^"\']*entry-thumb[^"\']*["\'])[^>]*>/i',
        function ($matches) {
            return preg_replace_callback(
                '/background-image:\s*url\((?:&quot;|["\']?)(https?:\/\/[^)"\']+\.(?:jpe?g|png))(?:&quot;|["\']?)\)/i',
                function ($url_matches) {
                    $webp_url = hs_manacost_webp_url_for_upload(html_entity_decode($url_matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    if (!$webp_url) {
                        return $url_matches[0];
                    }
                    return 'background-image: url(&quot;' . esc_url($webp_url) . '&quot;)';
                },
                $matches[0]
            );
        },
        $html
    );
}

function hs_manacost_rewrite_css_backgrounds_to_webp($html) {
    return preg_replace_callback(
        '/background-image:\s*url\((&quot;|["\']?)(https?:\/\/[^)"\'&]+?\.(?:jpe?g|png)(?:[?#][^)"\'&]*)?)\1?\)/i',
        function ($matches) {
            $webp_url = hs_manacost_webp_url_for_upload(html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (!$webp_url) {
                return $matches[0];
            }

            return 'background-image: url(' . $matches[1] . esc_url($webp_url) . $matches[1] . ')';
        },
        $html
    );
}

function hs_manacost_rewrite_retina_uploads_to_webp($html) {
    return preg_replace_callback(
        '/\b(data-retina)=(["\'])(https?:\/\/[^"\']+?\.(?:jpe?g|png)(?:[?#][^"\']*)?)\2/i',
        function ($matches) {
            $webp_url = hs_manacost_webp_url_for_upload(html_entity_decode($matches[3], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (!$webp_url) {
                return $matches[0];
            }

            return $matches[1] . '=' . $matches[2] . esc_url($webp_url) . $matches[2];
        },
        $html
    );
}

/**
 * Fit the Neverest artwork to the gutters of Newspaper's 1164px boxed layout.
 * Use the theme's rendered body rule so category backgrounds and later campaigns
 * retain their own styling, including when a signed-in visitor opens the page.
 */
function hs_manacost_partner_wallpaper_css($html) {
    $head_end = stripos($html, '</head>');
    $asset = 'neverest_manacost_wallpaper_2400x1500';
    $offset = strpos($html, $asset);
    if ($head_end === false || $offset === false || $offset >= $head_end || strpos($html, 'id="manacost-partner-wallpaper"') !== false) {
        return '';
    }

    $head = new WP_HTML_Tag_Processor(substr($html, 0, $head_end));
    while ($head->next_tag('STYLE')) {
        $css = $head->get_modifiable_text();
        if (strpos($css, $asset) === false) {
            continue;
        }
        // Match only a body background declaration, never a thumbnail or preload.
        $pattern = '~(?:^|\}|\*/)\s*body\s*\{[^{}]*?\bbackground-image\s*:\s*url\(\s*(["\']?)'
            . '((?:https?://[^"\'()<>\s\\\\]+)?/wp-content/uploads/\d{4}/\d{2}/'
            . $asset . '\.(?:png(?:\.webp)?|webp)(?:\?[^"\'()<>\s\\\\]*)?)\1\s*\)~i';
        if (!preg_match($pattern, $css, $match)) {
            continue;
        }
        $image = $match[2];
        // Use the smaller derivative for the landscape and portraits on standard-density laptops.
        $compact = preg_replace('~' . $asset . '\.(?:png(?:\.webp)?|webp)(?=\?|$)~', $asset . '-768x480.png.webp', $image, 1);
        $layout = file_get_contents(__DIR__ . '/manacost-partner-placements/neverest-wallpaper.css');
        if ($layout === false) {
            return '';
        }
        return '<style id="manacost-partner-wallpaper">body.td-boxed-layout{--mc-full-art:url("' . $image
            . '");--mc-compact-art:image-set(url("' . $compact . '") 1x,var(--mc-full-art) 2x)}'
            . $layout . '</style>';
    }
    return '';
}

/** Attach one native sponsored link to the active Newspaper wallpaper. */
function hs_manacost_apply_partner_wallpaper($html) {
    $css = hs_manacost_partner_wallpaper_css($html);
    if ($css === '') {
        return $html;
    }
    $link = '<a class="mc-wallpaper-link" href="https://sirus.one/registration?ref=3586993&amp;utm_source=hsmanacost&amp;utm_medium=twitch"'
        . ' target="_blank" rel="sponsored noopener noreferrer" aria-label="Регистрация на Sirus — откроется в новой вкладке">'
        . '<span class="mc-wallpaper-gutter mc-wallpaper-gutter-left"></span><span class="mc-wallpaper-gutter mc-wallpaper-gutter-right"></span>'
        . '<span class="mc-wallpaper-wing mc-wallpaper-wing-left"></span><span class="mc-wallpaper-wing mc-wallpaper-wing-right"></span></a>';
    $head_end = stripos($html, '</head>');
    $body = substr($html, $head_end);
    $tags = new WP_HTML_Tag_Processor($body);
    while ($tags->next_tag('DIV')) {
        if ($tags->get_attribute('id') === 'td-outer-wrap') {
            $tags->set_bookmark('boxed-layout');
            break;
        }
    }
    if (!$tags->has_bookmark('boxed-layout')) {
        return $html;
    }
    // Keep the portraits in the header's own stacking context, below its menu.
    if (!$tags->next_tag(array('tag_name' => 'DIV', 'class_name' => 'td-header-wrap'))) {
        $tags->seek('boxed-layout');
    }
    // The HTML API can mark the actual tag but cannot append a child element.
    $marker = 'data-mc-wallpaper-mount';
    if (stripos($body, $marker) !== false || !$tags->set_attribute($marker, true)) {
        return $html;
    }
    $attributes = '(?:[^"\'<>]|"[^"]*"|\'[^\']*\')*';
    $updated = preg_replace_callback('~ ' . $marker . '(' . $attributes . '>)~', static fn($m) => $m[1] . $link, $tags->get_updated_html(), 1, $count);
    if ($count !== 1 || $updated === null) {
        return $html;
    }
    return substr($html, 0, $head_end) . $css . $updated;
}

function hs_manacost_remove_rocket_image_preloads($html) {
    return preg_replace('/<link(?=[^>]+\bdata-rocket-preload\b)(?=[^>]+\bas=["\']image["\'])[^>]*>\s*/i', '', $html);
}

add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax() || is_feed() || is_robots() || is_trackback()) {
        return;
    }

    $preload_home_thumbs = is_front_page() || is_home();

    ob_start(function ($html) use ($preload_home_thumbs) {
        if (stripos($html, '<html') === false || stripos($html, '</head>') === false) {
            return $html;
        }

        $html = hs_manacost_rewrite_backgrounds_to_webp($html);
        $html = hs_manacost_rewrite_css_backgrounds_to_webp($html);
        $html = hs_manacost_rewrite_retina_uploads_to_webp($html);
        if ($preload_home_thumbs) {
            $html = hs_manacost_remove_rocket_image_preloads($html);
        }
        $html = preg_replace('/<link(?=[^>]+rel=["\'](?:preconnect|dns-prefetch)["\'])(?=[^>]+href=["\'](?:https?:)?\/\/fonts\.(?:gstatic|googleapis)\.com["\'])[^>]*>\s*/i', '', $html);

        $html = hs_manacost_apply_partner_wallpaper($html);
        $head = '';

        if (stripos($html, 'id="hs-early-paint"') === false && stripos($html, "id='hs-early-paint'") === false) {
            $head .= '<style id="hs-early-paint">html{background:#010101;}body{background-color:#010101;}</style>' . "\n";
        }

        if ($preload_home_thumbs && preg_match_all('/<span[^>]+class=["\'][^"\']*entry-thumb[^"\']*["\'][^>]+style=["\'][^"\']*background-image:\s*url\((?:&quot;|["\']?)(https?:\/\/[^)"\']+?\.(?:jpe?g|png|webp))(?:&quot;|["\']?)\)/i', $html, $matches)) {
            $urls = [];
            foreach ($matches[1] as $url) {
                $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (isset($urls[$url])) {
                    continue;
                }
                $urls[$url] = true;
                break;
            }

            $i = 0;
            foreach (array_keys($urls) as $url) {
                if (strpos($html, 'href="' . esc_url($url) . '"') !== false || strpos($html, "href='" . esc_url($url) . "'") !== false) {
                    $i++;
                    continue;
                }
                $head .= '<link rel="preload" as="image" href="' . esc_url($url) . '"' . ($i === 0 ? ' fetchpriority="high"' : '') . '>' . "\n";
                $i++;
            }
        }

        if ($head === '') {
            return $html;
        }

        return preg_replace_callback('/<head(?:\s[^>]*)?>/i', static fn($match) => $match[0] . $head, $html, 1);
    });
}, 0);
