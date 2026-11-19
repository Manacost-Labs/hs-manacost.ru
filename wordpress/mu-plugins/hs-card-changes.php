<?php
/**
 * Plugin Name: HS Card Changes
 * Description: Hearthstone balance changes in articles: card before → arrow and change plates → card after, with shortcodes and an editor dialog.
 * Version: 1.0.0
 * Text Domain: hs-card-changes
 *
 * @package HSManacost
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/hs-card-changes/pairs.php';
require_once __DIR__ . '/hs-card-changes/cache.php';
require_once __DIR__ . '/hs-card-changes/source.php';
require_once __DIR__ . '/hs-card-changes/render.php';
require_once __DIR__ . '/hs-card-changes/warm.php';

HS_Card_Changes_Render::register();
HS_Card_Changes_Source::register();
HS_Card_Changes_Warm::register();

if ( is_admin() ) {
	require_once __DIR__ . '/hs-card-changes/admin.php';
	HS_Card_Changes_Admin::register();
}
