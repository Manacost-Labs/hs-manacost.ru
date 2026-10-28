<?php
// Minimal WordPress, WP-CLI and AIOSEO Lite stand-ins for ops/seo/social-and-archetypes.php.
namespace AIOSEO\Plugin\Common\Models {
	class Post {
		public static array $rows = array();
		public $post_id;
		public $description;
		public static function getPost( $id ) {
			$row              = new self();
			$row->description = self::$rows[ $id ] ?? null;
			return $row;
		}
		public function save() {
			self::$rows[ $this->post_id ] = $this->description;
		}
	}
}

namespace {
	define( 'ABSPATH', '/' );
	final class HS_Test_Cli_Error extends Exception {}
	final class WP_CLI {
		public static array $lines = array();
		public static function line( $text ) { self::$lines[] = json_decode( $text ); }
		public static function error( $text ) { throw new HS_Test_Cli_Error( $text ); }
	}
	final class HS_Test_Group {
		public function __construct( private array $values ) {}
		public function __get( $name ) { return $this->values[ $name ] ?? null; }
		public function __set( $name, $value ) {
			if ( array_key_exists( $name, $this->values ) ) {
				$this->values[ $name ] = $value;
			}
		}
	}
	$GLOBALS['hs_social'] = (object) array(
		'facebook' => (object) array( 'general' => new HS_Test_Group( array( 'enable' => false, 'defaultImageSourcePosts' => 'default', 'defaultImagePosts' => '' ) ) ),
		'twitter'  => (object) array( 'general' => new HS_Test_Group( array( 'enable' => false, 'useOgData' => false ) ) ),
	);
	$GLOBALS['hs_saves'] = 0;
	function aioseo() {
		return (object) array( 'options' => new class() {
			public function __get( $name ) { return 'social' === $name ? $GLOBALS['hs_social'] : null; }
			public function save( $force = false ) { ++$GLOBALS['hs_saves']; }
		} );
	}
	final class WP_Post { public $ID = 311393; public $post_title = ''; public $post_content = '[hs_deck_archetypes]'; }
	$GLOBALS['hs_page'] = new WP_Post();
	function get_page_by_path( $path ) { return 'archetypes' === $path ? $GLOBALS['hs_page'] : null; }
	function has_shortcode( $content, $tag ) { return str_contains( $content, '[' . $tag ); }
	function get_site_icon_url( $size ) { return 'https://hs-manacost.ru/icon-' . $size . '.png'; }
	function get_post_field( $field, $id ) { return $GLOBALS['hs_page']->$field; }
	function wp_update_post( $data, $wp_error ) { $GLOBALS['hs_page']->post_title = $data['post_title']; return $data['ID']; }
	function is_wp_error( $value ) { return false; }
	function clean_post_cache( $id ) {}
	function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }

	require $argv[1];
	$backup = $argv[2];
	$runner = 'HS_Seo_Social_And_Archetypes';
	$runner::run( array( 'mode' => 'dry_run', 'backup' => '' ) );
	$runner::run( array( 'mode' => 'apply', 'backup' => $backup ) );
	$runner::run( array( 'mode' => 'apply', 'backup' => $backup . '.second' ) );
	// An editor renames the page after apply; rollback must keep that edit.
	$GLOBALS['hs_page']->post_title = 'Архетипы Hearthstone';
	$runner::run( array( 'mode' => 'rollback', 'backup' => $backup ) );
	try {
		$runner::run( array( 'mode' => 'rollback', 'backup' => $backup . '.missing' ) );
	} catch ( HS_Test_Cli_Error $error ) {
		WP_CLI::$lines[] = array( 'error' => $error->getMessage() );
	}
	echo json_encode(
		array(
			'lines'         => WP_CLI::$lines,
			'second_backup' => file_exists( $backup . '.second' ),
			'saves'         => $GLOBALS['hs_saves'],
			'final'         => $runner::snapshot(),
		),
		JSON_UNESCAPED_UNICODE
	);
}
