<?php
/**
 * Registration and conditional enqueueing of the front end script and style.
 *
 * @package WP-ShowHide
 */

/**
 * Covers registration and conditional enqueueing of the assets.
 */
class WP_ShowHide_Assets_Test extends WP_ShowHide_TestCase {

	/**
	 * Fire the hook the plugin registers its assets on.
	 *
	 * Everything in this file is about what that hook left behind, so running
	 * it once here keeps it out of every test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		do_action( 'wp_enqueue_scripts' );
	}

	public function test_script_and_style_are_registered() {
		$this->assertTrue( wp_script_is( 'wp-showhide', 'registered' ), 'The script is registered under the plugin slug.' );
		$this->assertTrue( wp_style_is( 'wp-showhide', 'registered' ), 'The stylesheet is registered under the plugin slug.' );
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
		$this->assertFileExists( dirname( __DIR__ ) . '/js/wp-showhide.js', 'The path the script is registered at is a file that actually ships.' );
	}

	/**
	 * The style is a real file under css/ for the same reason the script is:
	 * one cached request beats the same two rules inlined into every page.
	 */
	public function test_the_style_is_the_shipped_file() {
		$this->assertSame(
			plugins_url( 'css/wp-showhide.css', dirname( __DIR__ ) . '/wp-showhide.php' ),
			wp_styles()->registered['wp-showhide']->src
		);
		$this->assertFileExists( dirname( __DIR__ ) . '/css/wp-showhide.css', 'The path the stylesheet is registered at is a file that actually ships.' );
	}

	/**
	 * The plugin stopped depending on jQuery in 2.0.0 and must not quietly
	 * reacquire it.
	 */
	public function test_script_does_not_depend_on_jquery() {
		$this->assertSame( array(), wp_scripts()->registered['wp-showhide']->deps );

		do_shortcode( '[showhide]one two[/showhide]' );

		$this->assertFalse( wp_script_is( 'jquery', 'enqueued' ), 'Registering this plugin does not drag jQuery onto the page.' );
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
		$this->assertFalse( wp_scripts()->get_data( 'wp-showhide', 'after' ), 'Nothing is appended inline to the shipped script.' );
		$this->assertFalse( wp_scripts()->get_data( 'wp-showhide', 'before' ), 'Nothing is prepended inline to the shipped script.' );
	}

	/**
	 * The style ships in the head on every page so the toggle never flashes as
	 * a native button before the CSS arrives.
	 */
	public function test_style_is_enqueued_unconditionally() {
		$this->assertTrue( wp_style_is( 'wp-showhide', 'enqueued' ), 'The stylesheet is enqueued whether or not a shortcode ran.' );
	}

	/**
	 * Both rules are scoped under the .wp-showhide root class, and neither
	 * reaches for a physical property that would need mirroring in an RTL
	 * locale or for an !important a theme could not override.
	 */
	public function test_the_stylesheet_is_scoped_and_direction_neutral() {
		$css = file_get_contents( dirname( __DIR__ ) . '/css/wp-showhide.css' );

		$this->assertStringContainsString( '.wp-showhide .sh-toggle', $css );
		$this->assertStringContainsString( '.wp-showhide.sh-content[hidden]', $css );
		$this->assertStringNotContainsString( '!important', $css );

		foreach ( array( 'margin-left', 'margin-right', 'padding-left', 'padding-right', 'border-left', 'border-right', 'float:' ) as $physical ) {
			$this->assertStringNotContainsString(
				$physical,
				$css,
				$physical . ' is a physical property; use its logical equivalent (section 5.1).'
			);
		}
	}

	public function test_script_is_not_enqueued_without_the_shortcode() {
		$this->assertFalse( wp_script_is( 'wp-showhide', 'enqueued' ), 'With no shortcode on the page, the script stays unenqueued.' );
	}

	public function test_script_is_enqueued_by_the_shortcode() {
		do_shortcode( '[showhide]one two[/showhide]' );

		$this->assertTrue( wp_script_is( 'wp-showhide', 'enqueued' ), 'Rendering the shortcode is what enqueues the script.' );
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
