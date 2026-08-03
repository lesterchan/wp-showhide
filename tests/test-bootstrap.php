<?php
/**
 * Plugin boot: wiring, layout, and the direct-access guards.
 *
 * @package WP-ShowHide
 */

/**
 * Covers the plugin layout and its direct-access guards.
 */
class WP_ShowHide_Bootstrap_Test extends WP_ShowHide_TestCase {

	/**
	 * Every shipped PHP file that holds code, and so needs an ABSPATH guard.
	 *
	 * The index.php silence guards are excluded: they contain nothing but a
	 * docblock, so there is no code for a direct request to reach. So is
	 * uninstall.php, which WordPress loads with ABSPATH already defined and
	 * which therefore carries a different guard -- asserted on its own below.
	 *
	 * @return array<int, array{0: string}>
	 */
	public function guarded_files() {
		$files = array();

		foreach ( wp_showhide_test_source_files() as $file ) {
			if ( in_array( basename( $file ), array( 'index.php', 'uninstall.php' ), true ) ) {
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

		$this->assertFileExists( $path, $file . ' does not exist, so the guard assertion below would pass on an empty string.' );
		$this->assertMatchesRegularExpression(
			"/defined\(\s*'ABSPATH'\s*\)\s*\|\|\s*exit;/",
			php_strip_whitespace( $path ),
			$file . ' must refuse to run when loaded directly.'
		);
	}

	/**
	 * The constants this plugin defines, in the plugin file and nowhere else.
	 *
	 * They are the one source of truth for the version, the slug and the paths,
	 * so a class reaching for __DIR__ or hard-coding "wp-showhide" is a bug
	 * even when it happens to work.
	 */
	public function test_the_five_php_constants_are_defined() {
		// Five, not six: there is no DB_VERSION, because there is no schema and
		// no stored row for one to describe. See STANDARDS.md 2.1.
		$this->assertSame( '3.0.0', WP_SHOWHIDE_VERSION, 'The version constant is the shipped version.' );
		$this->assertSame( 'wp-showhide', WP_SHOWHIDE_SLUG, 'The slug constant is the plugin slug.' );
		$this->assertSame(
			realpath( dirname( __DIR__ ) . '/wp-showhide.php' ),
			realpath( WP_SHOWHIDE_MAIN_FILE ),
			'The main file constant resolves to the plugin file itself.'
		);
		$this->assertSame( realpath( dirname( __DIR__ ) ), realpath( WP_SHOWHIDE_DIR ), 'The directory constant resolves to the plugin directory.' );
		$this->assertStringEndsWith( '/wp-showhide/', WP_SHOWHIDE_URL, 'The URL constant ends in the plugin directory with its trailing slash.' );
	}

	/**
	 * WordPress loads uninstall.php with the plugin inactive and ABSPATH
	 * already defined, so its guard is the constant WordPress sets just for
	 * that request. Running the file for any other reason would delete a live
	 * site's row.
	 */
	public function test_the_uninstaller_refuses_to_run_outside_an_uninstall() {
		$code = php_strip_whitespace( dirname( __DIR__ ) . '/uninstall.php' );

		$this->assertMatchesRegularExpression(
			"/!\s*defined\(\s*'WP_UNINSTALL_PLUGIN'\s*\)/",
			$code,
			'uninstall.php must exit unless WordPress is really uninstalling the plugin.'
		);
		$this->assertMatchesRegularExpression(
			"/delete_option\(\s*'wp_showhide_version'\s*\)/",
			$code,
			'The marker row is the one row there is to remove.'
		);
		$this->assertStringContainsString(
			'get_sites(',
			$code,
			'A network install needs every site visited, not just the current one.'
		);
	}

	/**
	 * Only the entry points belong in the plugin root; everything else lives in
	 * includes/, matching the rest of the collection.
	 */
	public function test_only_entry_points_live_in_the_plugin_root() {
		$root = array_map( 'basename', (array) glob( dirname( __DIR__ ) . '/*.php' ) );

		sort( $root );

		$this->assertSame( array( 'index.php', 'uninstall.php', 'wp-showhide.php' ), $root, 'Only entry points live in the root; everything else is in a subdirectory.' );
	}

	public function test_every_class_is_loaded() {
		$this->assertTrue( class_exists( 'WP_ShowHide' ), 'The main class is loaded by the bootstrap.' );
		$this->assertTrue( class_exists( 'WP_ShowHide_Template' ), 'The template class is loaded by the bootstrap.' );
	}

	public function test_get_instance_is_a_singleton() {
		$this->assertSame( WP_ShowHide::get_instance(), WP_ShowHide::get_instance(), 'get_instance() hands back the same object rather than building a second.' );
	}

	/**
	 * Every class carries the plugin's own prefix, so nothing this plugin
	 * declares can ever collide with another plugin's class of the same noun.
	 *
	 * The classes are read back out of PHP rather than listed here. A hardcoded
	 * list asserts that the names in it start with the prefix they were written
	 * with, which is true of any list anybody would type -- this test compared
	 * two literals and could not fail, so a new unprefixed class would have
	 * sailed past it. What it has to look at is what the plugin actually put in
	 * the global namespace, which is the thing that can collide.
	 */
	public function test_every_class_carries_the_plugin_prefix() {
		$declared = $this->classes_declared_by_the_plugin();

		// Without this the test passes by finding nothing -- a filter that
		// matches no files is exactly how the version before it went vacuous.
		$this->assertNotEmpty( $declared, 'No class was traced back to this plugin, so the check below looked at nothing.' );

		foreach ( $declared as $class ) {
			$this->assertStringStartsWith( 'WP_ShowHide', $class, $class . ' reaches the global namespace without the plugin prefix.' );
		}
	}

	/**
	 * The classes this plugin declared, found by asking each loaded class which
	 * file it came from.
	 *
	 * The suite's own classes live under tests/ and are not the plugin's to
	 * prefix, so they are left out.
	 *
	 * @return array<int, string> Class names.
	 */
	protected function classes_declared_by_the_plugin() {
		$root  = dirname( __DIR__ ) . '/';
		$tests = __DIR__ . '/';
		$ours  = array();

		foreach ( get_declared_classes() as $class ) {
			$file = ( new ReflectionClass( $class ) )->getFileName();

			if ( is_string( $file ) && str_starts_with( $file, $root ) && ! str_starts_with( $file, $tests ) ) {
				$ours[] = $class;
			}
		}

		return $ours;
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
		$this->assertStringNotContainsString( $function_name . '(', wp_showhide_test_source_code(), 'The withdrawn ' . $function_name . '() is still called somewhere in the source.' );
	}

	/**
	 * The plugin registers itself, so nothing is hooked by the old names.
	 */
	public function test_nothing_is_hooked_by_a_removed_function_name() {
		$this->assertFalse( has_action( 'wp_enqueue_scripts', 'showhide_scripts' ), 'Nothing is left hooked under the withdrawn function name.' );
		$this->assertSame(
			array( WP_ShowHide::get_instance(), 'shortcode' ),
			$GLOBALS['shortcode_tags']['showhide'],
			'The shortcode is registered against the instance method, not a loose function.'
		);
	}

	/**
	 * The main file boots the plugin and holds no logic of its own.
	 */
	public function test_main_file_declares_no_functions_or_classes() {
		$code = php_strip_whitespace( dirname( __DIR__ ) . '/wp-showhide.php' );

		$this->assertDoesNotMatchRegularExpression( '/\bfunction\s+\w+\s*\(/', $code, 'The main file declares no functions; they belong to the classes.' );
		$this->assertDoesNotMatchRegularExpression( '/\bclass\s+\w+/', $code, 'The main file declares no classes; each lives in its own file.' );
	}

	/**
	 * Since WordPress 6.7 an early textdomain load triggers _doing_it_wrong,
	 * and WordPress.org-hosted plugins have been served translations
	 * automatically since 4.6.
	 */
	public function test_no_textdomain_is_loaded_manually() {
		$this->assertStringNotContainsString(
			'load_plugin_textdomain',
			wp_showhide_test_source_code(),
			'No textdomain is loaded by hand; WordPress has done that since 4.6.'
		);
	}

	/**
	 * There is no upgrade check, because there is nothing to upgrade.
	 *
	 * The bootstrap used to register one on plugins_loaded to keep a version row
	 * current. The plugin stores nothing now (STANDARDS.md 2.1), so that hook
	 * would run on every request to compare two strings about a row nobody
	 * writes.
	 */
	public function test_the_bootstrap_registers_no_upgrade_check() {
		foreach ( $GLOBALS['wp_filter']['plugins_loaded']->callbacks ?? array() as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$class = is_array( $callback['function'] ) ? (string) $callback['function'][0] : '';

				$this->assertStringNotContainsString( 'WP_ShowHide', $class, 'The plugin still hooks an upgrade check onto plugins_loaded.' );
			}
		}
	}
}
