<?php
/**
 * Shared base class for the WP-ShowHide test cases.
 *
 * @package WP-ShowHide
 */

/**
 * Puts a post in the loop and hands every test a clean asset registry.
 */
abstract class WP_ShowHide_TestCase extends WP_UnitTestCase {

	/**
	 * Post the shortcode renders against.
	 *
	 * @var int
	 */
	protected $post_id;

	/**
	 * Create the post the shortcode renders against and reset the registries.
	 *
	 * Element ids are built from the id of the post in the loop, so a post has
	 * to be there for the markup assertions to have anything to be about.
	 *
	 * The two registries are rebuilt rather than reused because enqueueing is
	 * sticky for the whole request: one test's shortcode would otherwise
	 * satisfy the next test's assertion that nothing had been enqueued.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->post_id   = self::factory()->post->create();
		$GLOBALS['post'] = get_post( $this->post_id );

		$GLOBALS['wp_scripts'] = new WP_Scripts();
		$GLOBALS['wp_styles']  = new WP_Styles();
	}

	/**
	 * Drop the loop global, so one test cannot leak into the next.
	 *
	 * @return void
	 */
	public function tear_down() {
		unset( $GLOBALS['post'] );

		parent::tear_down();
	}

	/**
	 * Run the uninstaller, however many times a suite asks for it.
	 *
	 * The uninstaller declares a global function, so a second require would
	 * fatal on redeclare and a require_once that has already fired proves
	 * nothing. Calling the function directly once it exists is the repeatable
	 * form. Nothing here touches schema, so including the file is safe for the
	 * first caller.
	 *
	 * @return void
	 */
	protected function run_uninstall() {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'wp-showhide/wp-showhide.php' );
		}

		if ( function_exists( 'wp_showhide_uninstall_site' ) ) {
			wp_showhide_uninstall_site();

			return;
		}

		require dirname( __DIR__ ) . '/uninstall.php';
	}

	/**
	 * Render a shortcode string through the full shortcode pipeline.
	 *
	 * Goes through do_shortcode() rather than calling the callback directly, so
	 * the attribute parsing and the nesting behaviour are exercised too.
	 *
	 * @param string $shortcode Shortcode text.
	 * @return string Rendered markup.
	 */
	protected function render( $shortcode ) {
		return do_shortcode( $shortcode );
	}
}
