<?php
namespace AIOSEO\Plugin\Common\Standalone {
	class DetailsColumn {
		public function registerColumnHooks() {}
	}
}

namespace {
	define( 'ABSPATH', __DIR__ );

	class WP_Hook {
		public $callbacks = [];
	}

	$GLOBALS['hs_test_hooks'] = [];
	$GLOBALS['wp_filter']     = [];

	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['hs_test_hooks'][ $hook ][ $priority ][] = $callback;

		if ( ! isset( $GLOBALS['wp_filter'][ $hook ] ) ) {
			$GLOBALS['wp_filter'][ $hook ] = new WP_Hook();
		}

		$GLOBALS['wp_filter'][ $hook ]->callbacks[ $priority ][] = [ 'function' => $callback ];
	}

	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		add_filter( $hook, $callback, $priority, $accepted_args );
	}

	function remove_action( $hook, $callback, $priority = 10 ) {
		foreach ( $GLOBALS['hs_test_hooks'][ $hook ][ $priority ] ?? [] as $index => $registered ) {
			if ( $registered === $callback ) {
				unset( $GLOBALS['hs_test_hooks'][ $hook ][ $priority ][ $index ] );
				foreach ( $GLOBALS['wp_filter'][ $hook ]->callbacks[ $priority ] ?? [] as $wp_index => $item ) {
					if ( ( $item['function'] ?? null ) === $callback ) {
						unset( $GLOBALS['wp_filter'][ $hook ]->callbacks[ $priority ][ $wp_index ] );
					}
				}

				return true;
			}
		}

		return false;
	}

	function is_admin() {
		return true;
	}

	function __return_false() {
		return false;
	}

	function __return_true() {
		return true;
	}

	function apply_test_filters( $hook, $value, ...$args ) {
		if ( empty( $GLOBALS['hs_test_hooks'][ $hook ] ) ) {
			return $value;
		}

		ksort( $GLOBALS['hs_test_hooks'][ $hook ] );
		foreach ( $GLOBALS['hs_test_hooks'][ $hook ] as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$value = $callback( $value, ...$args );
			}
		}

		return $value;
	}

	function run_test_action( $hook, ...$args ) {
		if ( empty( $GLOBALS['hs_test_hooks'][ $hook ] ) ) {
			return;
		}

		ksort( $GLOBALS['hs_test_hooks'][ $hook ] );
		foreach ( $GLOBALS['hs_test_hooks'][ $hook ] as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$callback( ...$args );
			}
		}
	}

	// Minimal WP_Scripts data store: only localized `data` extras are modelled.
	class HS_Test_Scripts {
		public $data = [];

		public function get_data( $handle, $key ) {
			return 'data' === $key ? ( $this->data[ $handle ] ?? false ) : false;
		}

		public function add_data( $handle, $key, $value ) {
			$this->data[ $handle ] = $value;
			return true;
		}
	}

	function wp_scripts() {
		return $GLOBALS['hs_test_scripts'];
	}

	$plugin = $argv[1] ?? '';
	if ( ! is_file( $plugin ) ) {
		fwrite( STDERR, "Plugin not found\n" );
		exit( 2 );
	}

	$aioseo = new \AIOSEO\Plugin\Common\Standalone\DetailsColumn();
	add_action( 'current_screen', [ $aioseo, 'registerColumnHooks' ], 1 );

	require $plugin;

	$rocket_excluded = apply_test_filters( 'rocket_insights_excluded_post_type', false, 'post' );
	run_test_action( 'current_screen', (object) [ 'base' => 'edit', 'post_type' => 'post' ] );

	$aioseo_callbacks = $GLOBALS['hs_test_hooks']['current_screen'][1] ?? [];
	if ( true !== $rocket_excluded || in_array( [ $aioseo, 'registerColumnHooks' ], $aioseo_callbacks, true ) ) {
		fwrite( STDERR, "Optional post-list columns remain enabled\n" );
		exit( 1 );
	}

	// AIOSEO localizes one large `aioseo` object onto three deferred module apps.
	$settings = 'var aioseo = {"page":"post","screen":{"base":"post"},"currentPost":{"id":7}};';
	$reordered = 'var aioseo = {"currentPost":{"id":7},"screen":{"base":"post"},"page":"post"};';
	$handles = [ 'aioseo/post-settings', 'aioseo/link-format', 'aioseo/primary-term', 'aioseo/app' ];

	$GLOBALS['hs_test_scripts'] = new HS_Test_Scripts();
	$GLOBALS['hs_test_scripts']->data = [
		'aioseo/post-settings' => $settings,
		'aioseo/link-format'   => $reordered,
		'aioseo/primary-term'  => $settings,
		'aioseo/app'           => 'var aioseoTranslations = {"translations":{}};',
	];
	$printed = apply_test_filters( 'print_scripts_array', $handles );
	$data = $GLOBALS['hs_test_scripts']->data;
	if (
		$printed !== $handles ||
		$settings !== $data['aioseo/post-settings'] ||
		'' !== $data['aioseo/link-format'] ||
		'' !== $data['aioseo/primary-term'] ||
		'var aioseoTranslations = {"translations":{}};' !== $data['aioseo/app']
	) {
		fwrite( STDERR, "Equivalent AIOSEO data is still printed more than once\n" );
		exit( 1 );
	}

	// A differing copy changes the value the modules finally read: leave all untouched.
	$GLOBALS['hs_test_scripts']->data = [
		'aioseo/post-settings' => $settings,
		'aioseo/link-format'   => 'var aioseo = {"page":"term"};',
		'aioseo/primary-term'  => $settings,
	];
	apply_test_filters( 'print_scripts_array', array_slice( $handles, 0, 3 ) );
	if ( in_array( '', $GLOBALS['hs_test_scripts']->data, true ) ) {
		fwrite( STDERR, "Differing AIOSEO data was removed\n" );
		exit( 1 );
	}

	echo "PASS\n";
}
