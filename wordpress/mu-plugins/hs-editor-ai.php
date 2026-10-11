<?php
/**
 * Plugin Name: Manacost Editor AI
 * Description: Reviewed card shortcodes, EditorTeam editing and proofreading.
 * Version: 1.0.1
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

foreach ( array( 'content', 'catalog', 'provider', 'prompts', 'jobs', 'admin' ) as $hs_editor_ai_class ) {
	require_once __DIR__ . '/hs-editor-ai/class-' . $hs_editor_ai_class . '.php';
}
unset( $hs_editor_ai_class );

Manacost\EditorAi\Jobs::register();
Manacost\EditorAi\Admin::register();
