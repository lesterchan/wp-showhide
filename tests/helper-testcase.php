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
	 * The uninstaller does its work in the file body, and PHP will not run a
	 * file body twice -- so the first caller in a process gets the real thing
	 * and any later one would silently get nothing at all. The require is
	 * therefore only there to guarantee the function exists, and the fan-out is
	 * driven from here: the same loop the file itself runs, with the same
	 * arguments.
	 *
	 * @return void
	 */
	protected function run_uninstall() {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'wp-showhide/wp-showhide.php' );
		}

		require_once dirname( __DIR__ ) . '/uninstall.php';

		if ( is_multisite() ) {
			$site_ids = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );
				wp_showhide_uninstall_site();
				restore_current_blog();
			}

			return;
		}

		wp_showhide_uninstall_site();
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
