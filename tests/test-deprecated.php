<?php
/**
 * The pre-2.1.0 global functions, kept as forwarding shims.
 *
 * @package WP-ShowHide
 */

/**
 * Covers the deprecated global functions.
 */
class Test_ShowHide_Deprecated extends WP_UnitTestCase {

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
	}

	public function tear_down() {
		unset( $GLOBALS['post'] );

		parent::tear_down();
	}

	public function test_the_old_global_functions_still_exist() {
		$this->assertTrue( function_exists( 'showhide_scripts' ) );
		$this->assertTrue( function_exists( 'showhide_js' ) );
		$this->assertTrue( function_exists( 'showhide_shortcode' ) );
	}

	/**
	 * @expectedDeprecated showhide_shortcode
	 */
	public function test_showhide_shortcode_still_renders() {
		$html = showhide_shortcode( array( 'type' => 'legacy' ), 'one two' );

		$this->assertSame(
			'legacy-link-' . $this->post_id,
			showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'id' )
		);
		$this->assertStringContainsString( 'Show Press Release (2 More Words)', $html );
	}

	/**
	 * The signature has a default for $content, and a caller relying on it
	 * must not trip the PHP 8.1+ null-to-string deprecation the suite converts
	 * into a failure.
	 *
	 * @expectedDeprecated showhide_shortcode
	 */
	public function test_showhide_shortcode_tolerates_a_missing_content_argument() {
		$html = showhide_shortcode( array( 'type' => 'legacynull' ) );

		$this->assertStringContainsString( '(0 More Words)', $html );
	}

	/**
	 * @expectedDeprecated showhide_js
	 */
	public function test_showhide_js_returns_the_toggle_handler() {
		$this->assertSame( ShowHide_Template::script(), showhide_js() );
	}

	/**
	 * @expectedDeprecated showhide_scripts
	 */
	public function test_showhide_scripts_registers_the_assets() {
		$GLOBALS['wp_scripts'] = new WP_Scripts();
		$GLOBALS['wp_styles']  = new WP_Styles();

		showhide_scripts();

		$this->assertTrue( wp_script_is( 'wp-showhide', 'registered' ) );
		$this->assertTrue( wp_style_is( 'wp-showhide', 'enqueued' ) );
	}

	/**
	 * The shims exist for callers, not for WordPress: the live hooks point at
	 * the class, so nothing routes through the deprecated names during a
	 * normal request and no visitor ever sees a deprecation notice.
	 */
	public function test_nothing_is_hooked_to_the_deprecated_functions() {
		$this->assertFalse( has_action( 'wp_enqueue_scripts', 'showhide_scripts' ) );
		$this->assertSame(
			array( ShowHide::get_instance(), 'shortcode' ),
			$GLOBALS['shortcode_tags']['showhide']
		);
	}
}
