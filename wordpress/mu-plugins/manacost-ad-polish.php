<?php
/**
 * Plugin Name: Manacost Ad Polish
 * Description: Improves the visual treatment of the bottom Boosty banner on single posts.
 */

defined('ABSPATH') || exit;

add_action('wp_head', static function (): void {
    if (is_admin() || !is_singular('post')) {
        return;
    }

    ?>
    <style id="manacost-bottom-ad-polish">
        body.single-post .td-post-content .td-a-rec-id-content_bottom {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            max-width: 860px;
            min-height: 0;
            margin: 26px auto 30px !important;
            padding: 12px 14px;
            border: 1px solid rgba(42, 119, 191, .18);
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 10px 26px rgba(15, 40, 70, .08);
            overflow: hidden;
            text-align: center !important;
            transform: none !important;
        }

        body.single-post .td-post-content .td-a-rec-id-content_bottom::before {
            content: none !important;
            display: none !important;
        }

        body.single-post .td-post-content .td-a-rec-id-content_bottom > style,
        body.single-post .td-post-content .td-a-rec-id-content_bottom .td-element-style {
            display: none !important;
        }

        body.single-post .td-post-content .td-a-rec-id-content_bottom > a[href*="boosty.to"] {
            display: block;
            width: min(100%, 728px);
            line-height: 0;
            border-radius: 9px;
            overflow: hidden;
            background: transparent;
            box-shadow: none;
            transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
        }

        body.single-post .td-post-content .td-a-rec-id-content_bottom > a[href*="boosty.to"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(2, 8, 18, .14);
            filter: saturate(1.05);
        }

        body.single-post .td-post-content .td-a-rec-id-content_bottom img[src*="reklamnyj-banner"] {
            display: block !important;
            width: 100% !important;
            max-width: 728px !important;
            height: auto !important;
            max-height: 90px !important;
            margin: 0 auto !important;
            border: 0 !important;
            border-radius: 0 !important;
            object-fit: contain;
            box-shadow: none !important;
        }

        @media (max-width: 767px) {
            body.single-post .td-post-content .td-a-rec-id-content_bottom {
                margin: 20px 0 24px !important;
                padding: 8px;
                border-radius: 10px;
            }

            body.single-post .td-post-content .td-a-rec-id-content_bottom::before {
                content: none !important;
                display: none !important;
            }

            body.single-post .td-post-content .td-a-rec-id-content_bottom > a[href*="boosty.to"] {
                width: 100%;
                border-radius: 7px;
            }
        }
    </style>
    <?php
}, 40);
