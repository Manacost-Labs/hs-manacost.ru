<?php
/**
 * Isolated WordPress surface for manacost-cache-purge regression tests.
 *
 * Run with: php -d disable_functions=opcache_reset fixture.php plugin.php scenario content-dir
 */
declare( strict_types=1 );

if ( $argc < 4 ) {
	fwrite( STDERR, "usage: fixture.php plugin.php scenario content-dir\n" );
	exit( 2 );
}

define( 'ABSPATH', '/fixture/' );
define( 'WP_CONTENT_DIR', $argv[3] );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'MANACOST_REVERSE_PROXY_PURGE_ENDPOINTS', 'https://edge.fixture/purge' );
define( 'MANACOST_REVERSE_PROXY_PURGE_TOKEN', 'fixture-token' );
define( 'MANACOST_REVERSE_PROXY_PURGE_HOST', 'example.test' );
define( 'MANACOST_CLOUDFLARE_ZONE_ID', 'fixture-zone' );
define( 'MANACOST_CLOUDFLARE_API_TOKEN', 'fixture-token' );

final class WP_Post {
	public function __construct( public string $post_type, public string $post_status ) {}
}

$GLOBALS['fixture'] = [
	'opcache' => 0,
	'object_cache' => 0,
	'rocket' => [],
	'actions' => [],
	'remote_posts' => [],
	'remote_post_args' => [],
	'reverse_should_fail' => false,
	'options' => [],
	'posts' => [],
];

function add_action( ...$args ): void {}
function add_filter( ...$args ): void {}
function do_action( string $action, ...$args ): void { $GLOBALS['fixture']['actions'][] = $action; }
function wp_installing(): bool { return false; }
function get_transient( string $key ): bool { return false; }
function set_transient( ...$args ): bool { return true; }
function wp_schedule_single_event( ...$args ): bool { return false; }
function wp_next_scheduled( ...$args ): bool { return false; }
function sanitize_key( string $value ): string { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', $value ) ?? '' ); }
function wp_is_post_revision( int $post_id ): bool { return false; }
function wp_is_post_autosave( int $post_id ): bool { return false; }
function get_post( int $post_id ): ?WP_Post { return $GLOBALS['fixture']['posts'][ $post_id ] ?? null; }
function update_option( string $key, $value, bool $autoload = false ): bool { $GLOBALS['fixture']['options'][ $key ] = $value; return true; }
function home_url( string $path = '' ): string { return 'https://example.test' . $path; }
function wp_parse_url( string $url, int $component = -1 ) { return parse_url( $url, $component ); }
function esc_url_raw( string $url ): string { return $url; }
function wp_json_encode( $value ): string { return json_encode( $value, JSON_THROW_ON_ERROR ); }
function wp_cache_flush(): bool { $GLOBALS['fixture']['object_cache']++; return true; }
function wp_delete_file( string $file ): bool { return unlink( $file ); }
function rocket_clean_minify(): void { $GLOBALS['fixture']['rocket'][] = 'minify'; }
function rocket_clean_cache_busting(): void { $GLOBALS['fixture']['rocket'][] = 'cache-busting'; }
function rocket_clean_used_css(): void { $GLOBALS['fixture']['rocket'][] = 'used-css'; }
function opcache_reset(): bool { $GLOBALS['fixture']['opcache']++; return true; }
function wp_remote_post( string $url, array $args ): array {
	$GLOBALS['fixture']['remote_posts'][] = $url;
	$GLOBALS['fixture']['remote_post_args'][] = $args;
	if ( str_contains( $url, 'cloudflare.com' ) ) {
		return [ 'code' => 200, 'body' => '{"success":true}' ];
	}
	if ( $GLOBALS['fixture']['reverse_should_fail'] ) {
		return [ 'code' => 502, 'body' => '{"ok":false,"error":"fixture failure"}' ];
	}
	return [ 'code' => 200, 'body' => '{"ok":true,"removed":2}' ];
}
function is_wp_error( $value ): bool { return false; }
function wp_remote_retrieve_response_code( array $response ): int { return $response['code']; }
function wp_remote_retrieve_body( array $response ): string { return $response['body']; }
function get_option( string $key, $default = false ) { return $default; }
function absint( $value ): int { return abs( (int) $value ); }

foreach ( [ 'wp-rocket', 'min', 'busting', 'critical-css', 'used-css', 'background-css', 'perfmatters', 'autoptimize', 'wpfc-minified', 'tmp', 'tmpWpfc', 'page_enhanced' ] as $folder ) {
	$path = WP_CONTENT_DIR . '/cache/' . $folder;
	mkdir( $path, 0777, true );
	file_put_contents( $path . '/marker', 'fixture' );
}

require $argv[1];

$scenario = $argv[2];
$published_post = new WP_Post( 'post', 'publish' );
$published_page = new WP_Post( 'page', 'publish' );
$published_custom = new WP_Post( 'event', 'publish' );
$draft_post = new WP_Post( 'post', 'draft' );
$direct_results = null;

switch ( $scenario ) {
	case 'content_post':
		Manacost_Cache_Purge::purge_after_content_change( 11, $published_post, true );
		break;
	case 'content_post_reverse_failure':
		$GLOBALS['fixture']['reverse_should_fail'] = true;
		Manacost_Cache_Purge::purge_after_content_change( 11, $published_post, true );
		break;
	case 'updated_post':
		Manacost_Cache_Purge::purge_after_post_update( 11, $published_post, $draft_post );
		break;
	case 'status_post_publish_to_draft':
		Manacost_Cache_Purge::purge_after_status_transition( 'draft', 'publish', $draft_post );
		break;
	case 'after_update_post':
		Manacost_Cache_Purge::purge_after_inserted_post( 11, $published_post, true, $published_post );
		break;
	case 'after_publish_post':
		Manacost_Cache_Purge::purge_after_inserted_post( 11, $published_post, false, null );
		break;
	case 'delete_post':
		Manacost_Cache_Purge::purge_after_post_delete( 11, $published_post );
		break;
	case 'status_post':
		$GLOBALS['fixture']['posts'][11] = $published_post;
		Manacost_Cache_Purge::purge_after_post_id_change( 11 );
		break;
	case 'content_page':
		Manacost_Cache_Purge::purge_after_content_change( 12, $published_page, true );
		break;
	case 'content_custom':
		Manacost_Cache_Purge::purge_after_content_change( 13, $published_custom, true );
		break;
	case 'async_ci_deploy':
		$direct_results = Manacost_Cache_Purge::run_async_purge( 'ci_deploy' );
		break;
	case 'async_ci_deploy_reverse_failure':
		$GLOBALS['fixture']['reverse_should_fail'] = true;
		$direct_results = Manacost_Cache_Purge::run_async_purge( 'ci_deploy' );
		break;
	case 'async_unknown':
		Manacost_Cache_Purge::run_async_purge( 'unrecognised_origin' );
		break;
	case 'async_default':
		Manacost_Cache_Purge::run_async_purge();
		break;
	case 'scheduled':
		Manacost_Cache_Purge::run_scheduled_purge();
		break;
	case 'manual':
		$method = new ReflectionMethod( Manacost_Cache_Purge::class, 'purge_all' );
		$method->setAccessible( true );
		$results = $method->invoke( null );
		$GLOBALS['fixture']['options']['manual_results'] = $results;
		break;
	default:
		throw new InvalidArgumentException( 'Unknown scenario: ' . $scenario );
}

$last = $GLOBALS['fixture']['options']['manacost_cache_purge_last_results'] ?? [];
$results = $last['results'] ?? $GLOBALS['fixture']['options']['manual_results'] ?? [];
echo json_encode( [
	'opcache' => $GLOBALS['fixture']['opcache'],
	'object_cache' => $GLOBALS['fixture']['object_cache'],
	'rocket' => $GLOBALS['fixture']['rocket'],
	'actions' => $GLOBALS['fixture']['actions'],
	'remote_posts' => $GLOBALS['fixture']['remote_posts'],
	'remote_post_args' => $GLOBALS['fixture']['remote_post_args'],
	'result_names' => array_column( $results, 'name' ),
	'failed' => $last['failed'] ?? 0,
	'direct_failed' => is_array( $direct_results )
		? count( array_filter( $direct_results, static fn( array $result ): bool => ( $result['status'] ?? '' ) !== 'ok' ) )
		: null,
	'local_cache_marker_exists' => file_exists( WP_CONTENT_DIR . '/cache/wp-rocket/marker' ),
], JSON_THROW_ON_ERROR );
