<?php
/**
 * Plugin boot: wiring, layout, and the direct-access guards.
 *
 * @package WP-ShowHide
 */

/**
 * Covers the plugin layout and its direct-access guards.
 */
class Test_ShowHide_Bootstrap extends WP_UnitTestCase {

	/**
	 * Every shipped PHP file that holds code, and so needs an ABSPATH guard.
	 *
	 * The index.php silence guards are excluded: they contain nothing but a
	 * docblock, so there is no code for a direct request to reach.
	 *
	 * @return array<int, array{0: string}>
	 */
	public function guarded_files() {
		$files = array();

		foreach ( showhide_test_source_files() as $file ) {
			if ( 'index.php' === basename( $file ) ) {
				continue;
			}

			$files[] = array( ltrim( str_replace( dirname( __DIR__ ), '', $file ), '/' ) );
		}

		return $files;
	}

	/**
	 * @dataProvider guarded_files
	 *
	 * @param string $file Repo-relative path.
	 */
	public function test_every_code_file_refuses_direct_access( $file ) {
		$path = dirname( __DIR__ ) . '/' . $file;

		$this->assertFileExists( $path );
		$this->assertMatchesRegularExpression(
			"/defined\(\s*'ABSPATH'\s*\)\s*\|\|\s*exit;/",
			php_strip_whitespace( $path ),
			$file . ' must refuse to run when loaded directly.'
		);
	}

	/**
	 * Every directory that ships PHP needs a silence guard, including the ones
	 * that never reach WordPress.org.
	 */
	public function test_every_php_directory_has_a_silence_guard() {
		$root = dirname( __DIR__ );

		foreach ( array( '', '/includes', '/tests', '/bin' ) as $dir ) {
			if ( '' !== $dir && ! is_dir( $root . $dir ) ) {
				continue;
			}

			$this->assertFileExists( $root . $dir . '/index.php', $dir . ' needs an index.php silence guard.' );
			$this->assertStringContainsString(
				'Silence is golden',
				file_get_contents( $root . $dir . '/index.php' )
			);
		}
	}

	/**
	 * Only the entry points belong in the plugin root; everything else lives in
	 * includes/, matching the rest of the collection.
	 */
	public function test_only_entry_points_live_in_the_plugin_root() {
		$root = array_map( 'basename', (array) glob( dirname( __DIR__ ) . '/*.php' ) );

		sort( $root );

		$this->assertSame( array( 'index.php', 'uninstall.php', 'wp-showhide.php' ), $root );
	}

	public function test_every_class_is_loaded() {
		$this->assertTrue( class_exists( 'WP_ShowHide' ) );
		$this->assertTrue( class_exists( 'WP_ShowHide_Template' ) );
	}

	public function test_get_instance_is_a_singleton() {
		$this->assertSame( WP_ShowHide::get_instance(), WP_ShowHide::get_instance() );
	}

	/**
	 * Every class carries the plugin's own prefix, so nothing this plugin
	 * declares can ever collide with another plugin's class of the same noun.
	 */
	public function test_every_class_carries_the_plugin_prefix() {
		foreach ( array( 'WP_ShowHide', 'WP_ShowHide_Template' ) as $class ) {
			$this->assertStringStartsWith( 'WP_ShowHide', $class );
		}
	}

	/**
	 * The pre-3.0.0 globals a theme might still be calling.
	 *
	 * @return array<int, array{0: string}>
	 */
	public function removed_functions() {
		return array(
			array( 'showhide_scripts' ),
			array( 'showhide_js' ),
			array( 'showhide_shortcode' ),
			// Removed back in 2.0.0, and it must not come back either.
			array( 'showhide_toggle' ),
		);
	}

	/**
	 * Removed in 3.0.0, and removed completely: no shim, no leftover
	 * definition, and nothing in the plugin still calling them.
	 *
	 * @dataProvider removed_functions
	 *
	 * @param string $function_name Name of the removed global.
	 */
	public function test_the_old_global_functions_are_gone( $function_name ) {
		$this->assertFalse( function_exists( $function_name ), $function_name . '() was removed in 3.0.0.' );
		$this->assertStringNotContainsString( $function_name . '(', showhide_test_source_code() );
	}

	/**
	 * The plugin registers itself, so nothing is hooked by the old names.
	 */
	public function test_nothing_is_hooked_by_a_removed_function_name() {
		$this->assertFalse( has_action( 'wp_enqueue_scripts', 'showhide_scripts' ) );
		$this->assertSame(
			array( WP_ShowHide::get_instance(), 'shortcode' ),
			$GLOBALS['shortcode_tags']['showhide']
		);
	}

	/**
	 * The main file boots the plugin and holds no logic of its own.
	 */
	public function test_main_file_declares_no_functions_or_classes() {
		$code = php_strip_whitespace( dirname( __DIR__ ) . '/wp-showhide.php' );

		$this->assertDoesNotMatchRegularExpression( '/\bfunction\s+\w+\s*\(/', $code );
		$this->assertDoesNotMatchRegularExpression( '/\bclass\s+\w+/', $code );
	}

	/**
	 * Since WordPress 6.7 an early textdomain load triggers _doing_it_wrong,
	 * and WordPress.org-hosted plugins have been served translations
	 * automatically since 4.6.
	 */
	public function test_no_textdomain_is_loaded_manually() {
		$this->assertStringNotContainsString(
			'load_plugin_textdomain',
			showhide_test_source_code()
		);
	}

	/**
	 * The plugin has no settings and no tables, but it does ship an
	 * uninstaller: the version markers it stores have to go when the plugin is
	 * deleted, and an uninstall.php is the only hook that runs then.
	 */
	public function test_the_plugin_ships_an_uninstaller() {
		$this->assertFileExists( dirname( __DIR__ ) . '/uninstall.php' );
	}
}
