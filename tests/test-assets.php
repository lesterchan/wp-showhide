<?php
/**
 * Registration and conditional enqueueing of the front end script and style.
 *
 * @package WP-ShowHide
 */

/**
 * Covers registration and conditional enqueueing of the assets.
 */
class Test_ShowHide_Assets extends WP_UnitTestCase {

	/**
	 * Post the shortcode renders against.
	 *
	 * @var int
	 */
	protected $post_id;

	public function set_up() {
		parent::set_up();

		$this->post_id   = self::factory()->post->create();
		$GLOBALS['post'] = get_post( $this->post_id );

		// A fresh registry per test: enqueueing is sticky for the whole
		// request, so one test's shortcode would otherwise satisfy the next
		// test's assertion that nothing was enqueued.
		$GLOBALS['wp_scripts'] = new WP_Scripts();
		$GLOBALS['wp_styles']  = new WP_Styles();

		do_action( 'wp_enqueue_scripts' );
	}

	public function tear_down() {
		unset( $GLOBALS['post'] );

		parent::tear_down();
	}

	public function test_script_and_style_are_registered() {
		$this->assertTrue( wp_script_is( 'wp-showhide', 'registered' ) );
		$this->assertTrue( wp_style_is( 'wp-showhide', 'registered' ) );
	}

	/**
	 * The script is a real file under js/, so a browser can cache it across
	 * pages instead of re-reading it inline with every post that toggles.
	 */
	public function test_the_script_is_the_shipped_file() {
		$this->assertSame(
			plugins_url( 'js/wp-showhide.js', dirname( __DIR__ ) . '/wp-showhide.php' ),
			wp_scripts()->registered['wp-showhide']->src
		);
		$this->assertFileExists( dirname( __DIR__ ) . '/js/wp-showhide.js' );
	}

	/**
	 * The style is srcless: it is printed inline, so there is no file to
	 * request and no 404 to serve.
	 */
	public function test_the_style_requests_no_file() {
		$this->assertFalse( wp_styles()->registered['wp-showhide']->src );
	}

	/**
	 * The plugin stopped depending on jQuery in 2.0.0 and must not quietly
	 * reacquire it.
	 */
	public function test_script_does_not_depend_on_jquery() {
		$this->assertSame( array(), wp_scripts()->registered['wp-showhide']->deps );

		do_shortcode( '[showhide]one two[/showhide]' );

		$this->assertFalse( wp_script_is( 'jquery', 'enqueued' ) );
	}

	/**
	 * The shipped file is the toggle handler, and it reaches for nothing that
	 * is not already on the page.
	 */
	public function test_the_script_file_carries_the_toggle_handler() {
		$script = file_get_contents( dirname( __DIR__ ) . '/js/wp-showhide.js' );

		$this->assertStringContainsString( 'sh-toggle', $script );
		$this->assertStringContainsString( 'aria-expanded', $script );
		$this->assertStringNotContainsString( 'jQuery', $script );
		$this->assertStringNotContainsString( '$(', $script );
	}

	/**
	 * Nothing is added inline to the script any more; the file is the whole of
	 * it, so a Content-Security-Policy without 'unsafe-inline' is enough.
	 */
	public function test_the_script_carries_no_inline_addition() {
		$this->assertFalse( wp_scripts()->get_data( 'wp-showhide', 'after' ) );
		$this->assertFalse( wp_scripts()->get_data( 'wp-showhide', 'before' ) );
	}

	/**
	 * The style ships in the head on every page so the toggle never flashes as
	 * a native button before the CSS arrives.
	 */
	public function test_style_is_enqueued_unconditionally() {
		$this->assertTrue( wp_style_is( 'wp-showhide', 'enqueued' ) );

		$inline = implode( '', (array) wp_styles()->get_data( 'wp-showhide', 'after' ) );

		$this->assertStringContainsString( '.sh-toggle', $inline );
		$this->assertStringContainsString( '.sh-content[hidden]', $inline );
	}

	public function test_script_is_not_enqueued_without_the_shortcode() {
		$this->assertFalse( wp_script_is( 'wp-showhide', 'enqueued' ) );
	}

	public function test_script_is_enqueued_by_the_shortcode() {
		do_shortcode( '[showhide]one two[/showhide]' );

		$this->assertTrue( wp_script_is( 'wp-showhide', 'enqueued' ) );
	}

	/**
	 * Late enqueueing only works from the footer, because the shortcode does
	 * not run until the template is already rendering the body.
	 */
	public function test_script_is_printed_in_the_footer() {
		// wp_register_script()'s $in_footer argument is recorded as the
		// dependency's "group" data, not as a property on the registration.
		$this->assertSame( 1, wp_scripts()->get_data( 'wp-showhide', 'group' ) );
	}

	public function test_asset_versions_track_the_plugin_version() {
		$version = get_file_data(
			dirname( __DIR__ ) . '/wp-showhide.php',
			array( 'Version' => 'Version' )
		)['Version'];

		$this->assertSame( $version, wp_scripts()->registered['wp-showhide']->ver );
		$this->assertSame( $version, wp_styles()->registered['wp-showhide']->ver );
	}
}
