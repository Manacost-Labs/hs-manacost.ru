<?php
// Exercise the editor asset with a fresh content directory from the test runner.
define( 'ABSPATH', __DIR__ );
define( 'WP_CONTENT_DIR', $argv[2] );

function add_filter( ...$args ) {}
function add_action( ...$args ) {}
function wp_json_encode( $value, $flags ) { return json_encode( $value, $flags ); }
function wp_mkdir_p( $path ) { return is_dir( $path ) || mkdir( $path ); }
function wp_is_writable( $path ) { return is_writable( $path ); }
function trailingslashit( $path ) { return rtrim( $path, '/' ) . '/'; }
function wp_tempnam( $filename, $dir ) { return str_ends_with( $dir, '/' ) ? tempnam( $dir, 'hs-' ) : false; }
function wp_delete_file( $path ) { unlink( $path ); }
class WP_Filesystem_Direct {
	public function put_contents( $path, $content, $mode ) {
		return strlen( $content ) === file_put_contents( $path, $content ) && chmod( $path, $mode );
	}
}
function content_url( $path ) { return 'https://example.invalid/wp-content/' . $path; }
function wp_register_script( $name, $src, $deps, $version, $footer ) {
	$GLOBALS['asset'] = array( $name, $src, $footer );
	return true;
}
function wp_scripts() { return $GLOBALS['scripts']; }
function aioseo() { return $GLOBALS['aioseo']; }

function check( $condition, $label ) {
	if ( ! $condition ) { throw new RuntimeException( $label ); }
}
require $argv[1];

$handle = 'aioseo/js/src/vue/standalone/app/main.js';
$GLOBALS['scripts'] = new class() {
	public $registered = array();
	public $data = '';
	public function get_data( $handle, $key ) { return 'group' === $key ? 1 : $this->data; }
	public function add_data( $handle, $key, $value ) { $this->data = $value; }
};
$GLOBALS['aioseo'] = (object) array( 'helpers' => new class() {
	public $calls = 0;
	public function getJedLocaleData( $domain ) {
		++$this->calls;
		return array( '' => array( 'domain' => $domain, 'lang' => 'ru_RU' ), 'title' => array( str_repeat( 'Перевод </tag> / ', 4000 ) ) );
	}
} );
$scripts = wp_scripts();
$scripts->registered[ $handle ] = (object) array( 'deps' => array( 'original' ) );
$catalog = array( 'translations' => aioseo()->helpers->getJedLocaleData( 'all-in-one-seo-pack' ) );
$original = 'var aioseoTranslations = ' . wp_json_encode( $catalog, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ) . ';';
check( strlen( $original ) >= 131072, 'Fixture must exercise the large dictionary' );

$scripts->data = $original . "\nvar editorPrivate = {nonce:'fixture-only'};";
hs_admin_load_trim_editor_dictionary( 'post.php' );
check( ! is_dir( WP_CONTENT_DIR . '/hs-editor-assets' ) && str_contains( $scripts->data, 'editorPrivate' ), 'Private data must stay inline' );

$scripts->data = $original;
hs_admin_load_trim_editor_dictionary( 'upload.php' );
check( $scripts->data === $original, 'Only post editors use the dictionary' );
hs_admin_load_trim_editor_dictionary( 'post.php' );
$expected = 'aioseo-' . hash( 'sha256', $original ) . '.js';
$files = glob( WP_CONTENT_DIR . '/hs-editor-assets/*' );
check( 1 === count( $files ) && basename( $files[0] ) === $expected && $original === file_get_contents( $files[0] ), 'Publish the complete, exact dictionary' );
check( '' === $scripts->data && array( 'hs-aioseo-editor-dictionary', 'original' ) === $scripts->registered[ $handle ]->deps, 'Retain script dependency order' );
check( true === $GLOBALS['asset'][2] && str_ends_with( $GLOBALS['asset'][1], $expected ), 'Keep the module in the original footer group' );

$calls = aioseo()->helpers->calls;
$scripts->data = $original;
hs_admin_load_trim_editor_dictionary( 'post-new.php' );
check( $calls === aioseo()->helpers->calls && count( glob( WP_CONTENT_DIR . '/hs-editor-assets/*' ) ) === 1, 'Reuse the immutable asset' );
echo "PASS\n";
