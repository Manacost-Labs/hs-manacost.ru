<?php
/**
 * Plugin Name: HS Article Cards
 * Description: Поиск статей Manacost и Koloda и вставка карточек в редактор.
 * Version: 1.4.1
 * Text Domain: hs-article-cards
 *
 * @package HSManacost
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/hs-article-cards/render.php';

add_action( 'wp_enqueue_scripts', array( 'HS_Article_Cards_Render', 'enqueue' ) );
add_filter( 'mce_css', array( 'HS_Article_Cards_Render', 'editor_css' ) );
add_action( 'enqueue_block_assets', array( 'HS_Article_Cards_Render', 'block_editor_assets' ) );

if ( is_admin() ) {
	require_once __DIR__ . '/hs-article-cards/remote.php';
	require_once __DIR__ . '/hs-article-cards/catalog.php';
	require_once __DIR__ . '/hs-article-cards/admin.php';
	HS_Article_Cards_Admin::register();
}
