<?php
/**
 * PHPUnit bootstrap for WP-ShowHide.
 *
 * Runs inside the wp-env "tests" container, where WP_TESTS_DIR is already
 * exported and the WordPress test library is present.
 *
 * @package WP-ShowHide
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = '/wordpress-phpunit';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find the WordPress test library at {$_tests_dir}." . PHP_EOL;
	echo 'Run the suite through wp-env: bash bin/test.sh' . PHP_EOL;
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Load the plugin under test before WordPress finishes booting.
 *
 * @return void
 */
function _wp_showhide_manually_load_plugin() {
	require dirname( __DIR__ ) . '/wp-showhide.php';
}
tests_add_filter( 'muplugins_loaded', '_wp_showhide_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

// After the test library, not before: the base class extends WP_UnitTestCase,
// which does not exist until the bootstrap above has run.
require_once __DIR__ . '/helper-source.php';
require_once __DIR__ . '/helper-testcase.php';

// The shared metadata base is byte-identical in all nineteen plugins, so it
// cannot name this plugin's fixture class. The alias is the one per-plugin line
// the mechanism needs.
class_alias( 'WP_ShowHide_TestCase', 'Plugin_TestCase' );
require_once __DIR__ . '/helper-metadata-testcase.php';
