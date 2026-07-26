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

		$this->assertSame( array( 'index.php', 'wp-showhide.php' ), $root );
	}

	public function test_every_class_is_loaded() {
		$this->assertTrue( class_exists( 'ShowHide' ) );
		$this->assertTrue( class_exists( 'ShowHide_Template' ) );
	}

	public function test_get_instance_is_a_singleton() {
		$this->assertSame( ShowHide::get_instance(), ShowHide::get_instance() );
	}

	/**
	 * The plugin must not prefix with WP_, which core reserves.
	 */
	public function test_classes_are_not_prefixed_with_wp() {
		foreach ( array( 'ShowHide', 'ShowHide_Template' ) as $class ) {
			$this->assertStringStartsNotWith( 'WP_', $class );
		}
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
	 * The plugin has no settings, no tables and no options, so there is
	 * nothing for an uninstaller to remove. Shipping an empty one would be a
	 * standing invitation to add cleanup for state that does not exist.
	 */
	public function test_plugin_stores_no_persistent_state() {
		$code = showhide_test_source_code();

		$this->assertStringNotContainsString( 'update_option', $code );
		$this->assertStringNotContainsString( 'add_option', $code );
		$this->assertFileDoesNotExist( dirname( __DIR__ ) . '/uninstall.php' );
	}
}
