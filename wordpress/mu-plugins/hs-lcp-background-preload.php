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
        // WordPress's 768×480 WebP of this campaign supplies the atmospheric crop.
        $compact = preg_replace('~' . $asset . '\.(?:png(?:\.webp)?|webp)(?=\?|$)~', $asset . '-768x480.png.webp', $image, 1);
        return <<<CSS
<style id="manacost-partner-wallpaper">
@media screen {
    body.td-boxed-layout {
        background: #010101 radial-gradient(ellipse at top, #0b3028, #031510 60%, #010101) center top / 100% 280px no-repeat;
    }
}
@media screen and (min-width: 1165px) {
    body.td-boxed-layout { isolation: isolate; }
    body.td-boxed-layout::before {
        content: "";
        position: fixed;
        top: 0;
        inset-inline: 0;
        z-index: -1;
        pointer-events: none;
    }
}
@media screen and (min-width: 1165px) and (max-width: 1599.98px) {
    /* Only the landscape between the two printed panels fits these narrow gutters. */
    body.td-boxed-layout::before {
        bottom: 0;
        background-image: linear-gradient(to bottom, #00120c66, #01070580), url("$compact");
        background-size: cover, 275% auto;
        background-position: center, center 42%;
        background-repeat: no-repeat;
    }
}
@media screen and (min-width: 1600px) {
    body.td-boxed-layout::before {
        /* Scale the 720px wings uniformly. Only the empty sky and center can stretch. */
        --wallpaper-gutter: min(720px, calc(50vw - 582px));
        --wallpaper-sky: max(112px, calc(var(--wallpaper-gutter) * 168 / 720));
        max-width: 2604px;
        height: calc(var(--wallpaper-sky) + var(--wallpaper-gutter) * 1332 / 720);
        margin-inline: auto;
        border-image: url("$image") 168 720 0 fill / var(--wallpaper-sky) var(--wallpaper-gutter) 0 stretch;
    }
}
@media screen and (min-width: 2605px) {
    body.td-boxed-layout::before {
        mask-image: linear-gradient(to right, transparent, black 100px, black calc(100% - 100px), transparent);
    }
}
</style>
CSS;
    }
    return '';
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

        $head = hs_manacost_partner_wallpaper_css($html);

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
